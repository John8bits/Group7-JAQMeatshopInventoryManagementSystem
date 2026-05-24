<?php
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;

$backupDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'database';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

$databaseName = (string)$conn->query("SELECT DATABASE()")->fetchColumn();
$message = null;
$messageType = 'success';
$pendingDeleteFile = $backupDir . DIRECTORY_SEPARATOR . '.delete-pending.json';

function backupSafeName($name) {
    $name = trim((string)$name);
    $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', $name);
    $name = trim($name, '_');
    return $name !== '' ? $name : 'backup';
}

function pendingDeleteFiles($pendingDeleteFile) {
    if (!is_file($pendingDeleteFile)) {
        return [];
    }

    $pending = json_decode((string)file_get_contents($pendingDeleteFile), true);
    return is_array($pending) ? $pending : [];
}

function savePendingDeleteFiles($pendingDeleteFile, array $pending) {
    $pending = array_values(array_unique(array_filter($pending)));

    if (empty($pending)) {
        if (is_file($pendingDeleteFile)) {
            @unlink($pendingDeleteFile);
        }
        return;
    }

    file_put_contents($pendingDeleteFile, json_encode($pending, JSON_PRETTY_PRINT));
}

function scheduleWindowsDelete($filePath) {
    if (stripos(PHP_OS_FAMILY, 'Windows') !== 0) {
        return;
    }

    $quotedPath = "'" . str_replace("'", "''", $filePath) . "'";
    $script = "Start-Sleep -Seconds 2; Remove-Item -LiteralPath {$quotedPath} -Force -ErrorAction SilentlyContinue";
    $encodedScript = function_exists('mb_convert_encoding')
        ? mb_convert_encoding($script, 'UTF-16LE', 'UTF-8')
        : iconv('UTF-8', 'UTF-16LE', $script);
    $encoded = base64_encode($encodedScript);
    $cmd = 'start /B powershell -NoProfile -WindowStyle Hidden -EncodedCommand ' . $encoded;
    @pclose(@popen($cmd, 'r'));
}

function cleanupPendingDeletes($backupDir, $pendingDeleteFile) {
    $pending = pendingDeleteFiles($pendingDeleteFile);
    if (empty($pending)) {
        return;
    }

    $remaining = [];
    foreach ($pending as $fileName) {
        $fileName = basename($fileName);
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        clearstatcache(true, $filePath);
        if (!is_file($filePath)) {
            continue;
        }

        if (!@unlink($filePath)) {
            scheduleWindowsDelete($filePath);
            $remaining[] = $fileName;
        }
    }

    savePendingDeleteFiles($pendingDeleteFile, $remaining);
}

function backupFiles($backupDir, array $pendingDeletes = []) {
    $files = glob($backupDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    if (!empty($pendingDeletes)) {
        $pendingDeletes = array_flip($pendingDeletes);
        $files = array_values(array_filter($files, fn($file) => !isset($pendingDeletes[basename($file)])));
    }
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    return $files;
}

function deleteBackupFile($filePath, $pendingDeleteFile) {
    clearstatcache(true, $filePath);

    if (!is_file($filePath)) {
        throw new RuntimeException('Backup file was not found.');
    }

    if (!is_writable($filePath)) {
        throw new RuntimeException('Backup file cannot be deleted because it is not writable.');
    }

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        if (@unlink($filePath)) {
            clearstatcache(true, $filePath);
            return true;
        }

        usleep(200000);
        clearstatcache(true, $filePath);

        if (!is_file($filePath)) {
            return true;
        }
    }

    $fileName = basename($filePath);
    $pending = pendingDeleteFiles($pendingDeleteFile);
    $pending[] = $fileName;
    savePendingDeleteFiles($pendingDeleteFile, $pending);
    scheduleWindowsDelete($filePath);

    return false;
}

function splitSqlStatements($sql) {
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $quote = null;
    $escape = false;

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $len ? $sql[$i + 1] : '';

        if ($quote === null && $char === '-' && $next === '-') {
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
            continue;
        }

        if ($quote === null && $char === '/' && $next === '*') {
            $i += 2;
            while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                $i++;
            }
            $i++;
            continue;
        }

        if ($quote !== null) {
            $current .= $char;
            if ($escape) {
                $escape = false;
            } elseif ($char === '\\') {
                $escape = true;
            } elseif ($char === $quote) {
                $quote = null;
            }
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $current .= $char;
            continue;
        }

        if ($char === ';') {
            $statement = trim($current);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    $statement = trim($current);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

function createDatabaseBackup(PDO $conn, $backupDir, $databaseName, $backupName) {
    $safeName = backupSafeName($backupName);
    $fileName = $safeName . '_' . date('Ymd_His') . '.sql';
    $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

    $tables = $conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
    $dump = [];
    $dump[] = "-- JAQ Meatshop database backup";
    $dump[] = "-- Database: `{$databaseName}`";
    $dump[] = "-- Created: " . date('Y-m-d H:i:s');
    $dump[] = "SET FOREIGN_KEY_CHECKS=0;";
    $dump[] = "";

    foreach ($tables as $tableRow) {
        $table = $tableRow[0];
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';

        $createStmt = $conn->query("SHOW CREATE TABLE {$quotedTable}")->fetch(PDO::FETCH_ASSOC);
        $createSql = $createStmt['Create Table'] ?? array_values($createStmt)[1];

        $dump[] = "DROP TABLE IF EXISTS {$quotedTable};";
        $dump[] = $createSql . ';';
        $dump[] = "";

        $rows = $conn->query("SELECT * FROM {$quotedTable}", PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $columns = array_map(fn($column) => '`' . str_replace('`', '``', $column) . '`', array_keys($row));
            $values = array_map(fn($value) => $value === null ? 'NULL' : $conn->quote((string)$value), array_values($row));
            $dump[] = "INSERT INTO {$quotedTable} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");";
        }

        $dump[] = "";
    }

    $dump[] = "SET FOREIGN_KEY_CHECKS=1;";
    file_put_contents($filePath, implode("\n", $dump));

    return $fileName;
}

function recoverDatabaseBackup(PDO $conn, $backupFile) {
    $sql = file_get_contents($backupFile);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('Backup file is empty or cannot be read.');
    }

    try {
        $conn->exec('SET FOREIGN_KEY_CHECKS=0');
        $tables = $conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        foreach ($tables as $tableRow) {
            $table = '`' . str_replace('`', '``', $tableRow[0]) . '`';
            $conn->exec("DROP TABLE IF EXISTS {$table}");
        }

        foreach (splitSqlStatements($sql) as $statement) {
            $conn->exec($statement);
        }

        $conn->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Throwable $e) {
        $conn->exec('SET FOREIGN_KEY_CHECKS=1');
        throw $e;
    }
}

cleanupPendingDeletes($backupDir, $pendingDeleteFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $createdFile = createDatabaseBackup($conn, $backupDir, $databaseName, $_POST['backup_name'] ?? $databaseName);
            $message = "Backup {$createdFile} was saved successfully.";
        }

        if ($action === 'recover') {
            $fileName = basename($_POST['backup_file'] ?? '');
            $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;
            if (!is_file($filePath)) {
                throw new RuntimeException('Backup file was not found.');
            }
            $currentBackupFile = createDatabaseBackup($conn, $backupDir, $databaseName, 'before_recover_' . $databaseName);
            recoverDatabaseBackup($conn, $filePath);
            $message = "Current database was saved as {$currentBackupFile}, then recovered from {$fileName}.";
        }

        if ($action === 'delete') {
            $fileName = basename($_POST['backup_file'] ?? '');
            $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;
            if (deleteBackupFile($filePath, $pendingDeleteFile)) {
                $message = "Backup {$fileName} was deleted permanently.";
            } else {
                $message = "Backup {$fileName} is being deleted in the background and was removed from this list.";
            }
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

cleanupPendingDeletes($backupDir, $pendingDeleteFile);
$files = backupFiles($backupDir, pendingDeleteFiles($pendingDeleteFile));
?>

<style>
.backup-page {
    display: grid;
    gap: 18px;
}

.backup-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
}

.backup-header h2 {
    margin: 0;
    color: #1A0F0A;
}

.backup-header p {
    margin: 5px 0 0;
    color: #6B4C3B;
    font-size: 0.9rem;
}

.backup-form {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) auto;
    gap: 12px;
    align-items: end;
    background: #FDF8F5;
    border: 1px solid #E8D5C8;
    border-radius: 12px;
    padding: 16px;
}

.backup-form label {
    display: grid;
    gap: 6px;
    color: #6B4C3B;
    font-size: 13px;
    font-weight: 700;
}

.backup-form input {
    width: 100%;
    border: 1px solid #E8D5C8;
    border-radius: 8px;
    padding: 11px 12px;
    font-size: 14px;
    color: #1A0F0A;
    background: #fff;
}

.backup-btn {
    border: none;
    border-radius: 8px;
    padding: 11px 15px;
    color: #fff;
    background: #D53E0F;
    cursor: pointer;
    font-family: Georgia, serif;
    font-weight: 700;
}

.backup-btn:hover {
    background: #b7320c;
}

.backup-btn.recover {
    background: #1f6f55;
}

.backup-btn.recover:hover {
    background: #175640;
}

.backup-btn.delete {
    background: #D93025;
}

.backup-btn.delete:hover {
    background: #B3261E;
}

.backup-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
}

.backup-table th {
    background: #091413;
    color: #fff;
    padding: 11px;
    text-align: left;
}

.backup-table td {
    padding: 11px;
    border-bottom: 1px solid #eee;
    color: #1A0F0A;
}

.backup-table tr:last-child td {
    border-bottom: none;
}

.backup-actions {
    display: flex;
    gap: 8px;
}

.backup-empty {
    text-align: center;
    color: #6B4C3B;
    padding: 24px;
}
</style>

<div class="backup-page">
    <div class="backup-header">
        <div>
            <h2>Database Backup</h2>
            <p>Current database: <strong><?= htmlspecialchars($databaseName) ?></strong></p>
        </div>
    </div>

    <form class="backup-form" method="POST">
        <input type="hidden" name="action" value="create">
        <label>
            Database Name / Backup Name
            <input type="text" name="backup_name" value="<?= htmlspecialchars($databaseName) ?>" required>
        </label>
        <button class="backup-btn" type="submit">Save Backup</button>
    </form>

    <table class="backup-table">
        <tr>
            <th>Database Name</th>
            <th>Backup File</th>
            <th>Date Saved</th>
            <th>Size</th>
            <th>Action</th>
        </tr>
        <?php if (empty($files)): ?>
            <tr>
                <td class="backup-empty" colspan="5">No database backups yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($files as $file): ?>
                <?php $fileName = basename($file); ?>
                <tr>
                    <td><?= htmlspecialchars($databaseName) ?></td>
                    <td><?= htmlspecialchars($fileName) ?></td>
                    <td><?= htmlspecialchars(date('M d, Y h:i A', filemtime($file))) ?></td>
                    <td><?= number_format(filesize($file) / 1024, 2) ?> KB</td>
                    <td>
                        <div class="backup-actions">
                            <form method="POST" class="recover-form">
                                <input type="hidden" name="action" value="recover">
                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars($fileName) ?>">
                                <button class="backup-btn recover" type="submit">Recover</button>
                            </form>
                            <form method="POST" class="delete-form">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars($fileName) ?>">
                                <button class="backup-btn delete" type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>

<script>
document.querySelectorAll('.recover-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (typeof Swal === 'undefined') {
            form.submit();
            return;
        }
        Swal.fire({
            title: 'Recover this backup?',
            text: 'The current database will be saved first, then overwritten with the selected backup.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, recover',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#1f6f55',
            cancelButtonColor: '#6B4C3B'
        }).then(function(result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

document.querySelectorAll('.delete-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (typeof Swal === 'undefined') {
            form.submit();
            return;
        }
        Swal.fire({
            title: 'Delete backup permanently?',
            text: 'This backup file will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#D93025',
            cancelButtonColor: '#6B4C3B'
        }).then(function(result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

<?php if ($message): ?>
if (typeof Swal !== 'undefined') {
    Swal.fire({
        title: <?= json_encode($messageType === 'success' ? 'Success' : 'Action failed') ?>,
        text: <?= json_encode($message) ?>,
        icon: <?= json_encode($messageType) ?>,
        confirmButtonColor: '#B85C38'
    });
}
<?php endif; ?>
</script>
