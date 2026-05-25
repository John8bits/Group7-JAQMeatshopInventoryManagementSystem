<?php
    class Database{

        private $host ="localhost";
        private $user = "root";
        private $password = "";
        private $dbname = "InformationManagement";

        public $conn;

        public function __construct(){
            
            try{
                $this->conn = new PDO("mysql:host=".$this->host . ";dbname=".$this->dbname,
                $this->user,$this->password);

                $this->conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
                $this->runAutomaticDailyBackup();
                #echo "Connection success";
            }catch(PDOException $e){
    $recoveryMessage = $this->handleManualRecoveryRequest();
    if ($recoveryMessage === 'recovered') {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    $message = htmlspecialchars($e->getMessage(), ENT_QUOTES);
    $recoverNotice = $recoveryMessage !== null
        ? "<div class=\"db-recover-notice\">" . htmlspecialchars($recoveryMessage, ENT_QUOTES) . "</div>"
        : "";
    echo "<!doctype html>
<html lang=\"en\">
<head>
  <meta charset=\"utf-8\">
  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">
  <title>No Database Connection</title>
  <style>
    body{margin:0;font-family:Segoe UI,Roboto,Arial,sans-serif;background:#111;color:#fff}
    .db-overlay{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.65);z-index:9999}
    .db-modal{background:#0f1720;color:#e6eef6;padding:28px 32px;border-radius:14px;max-width:540px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,0.8);text-align:center}
    .db-icon{width:120px;height:80px;margin:0 auto 16px;display:block;overflow:visible}
    .db-title{font-size:20px;margin:8px 0 4px;font-weight:600;color:#f1f5f9}
    .db-msg{font-size:14px;color:#94a3b8;margin:8px 0 0;line-height:1.6}
    .db-btn{margin-top:18px;display:inline-block;padding:10px 22px;border-radius:8px;background:#e74c3c;color:#fff;text-decoration:none;font-size:14px;font-weight:500;transition:background .2s}
    .db-btn:hover{background:#c0392b}
    .db-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px}
    .db-btn{margin-top:0;border:0;cursor:pointer;font-weight:700}
    .db-btn.recover{background:#16a34a}
    .db-btn.recover:hover{background:#15803d}
    .db-recover-panel{display:none;margin-top:16px;background:#080e14;border:1px solid #1f2a37;border-radius:10px;padding:14px;text-align:left}
    .db-recover-panel.open{display:block}
    .db-recover-panel label{display:block;color:#94a3b8;font-size:12px;font-weight:700;margin-top:10px}
    .db-recover-panel input{width:100%;box-sizing:border-box;margin-top:6px;padding:10px 12px;border-radius:8px;border:1px solid #263241;background:#0f1720;color:#e6eef6}
    .db-recover-panel .db-btn{width:100%;margin-top:14px}
    .db-recover-notice{margin-top:14px;color:#fecaca;background:#450a0a;border:1px solid #7f1d1d;padding:10px 12px;border-radius:8px;font-size:12px;text-align:left}
    .db-details{margin-top:14px;font-size:11px;color:#64748b;word-break:break-word;font-family:monospace;background:#080e14;padding:8px 12px;border-radius:6px;text-align:left}
    @keyframes flicker{0%,100%{opacity:1}25%{opacity:.5}50%{opacity:.9}75%{opacity:.4}}
    @keyframes spark{0%{opacity:1;transform:translate(0,0)}100%{opacity:0;transform:translate(var(--tx),var(--ty))}}
    @media(prefers-reduced-motion:no-preference){
      .arc{animation:flicker .2s steps(1) infinite}
      .s1{animation:spark .7s ease-out infinite;--tx:10px;--ty:-18px}
      .s2{animation:spark .9s ease-out .15s infinite;--tx:-12px;--ty:-22px}
      .s3{animation:spark .6s ease-out .3s infinite;--tx:16px;--ty:12px}
      .s4{animation:spark .8s ease-out .05s infinite;--tx:-14px;--ty:16px}
    }
  </style>
</head>
<body>
<div class=\"db-overlay\">
  <div class=\"db-modal\">

    <!-- Broken wire SVG icon -->
    <svg class=\"db-icon\" viewBox=\"0 0 200 100\" xmlns=\"http://www.w3.org/2000/svg\" aria-hidden=\"true\">
      <defs>
        <linearGradient id=\"cl\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"0\">
          <stop offset=\"0%\" stop-color=\"#2a2a3a\"/><stop offset=\"100%\" stop-color=\"#444\"/>
        </linearGradient>
        <linearGradient id=\"cr\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"0\">
          <stop offset=\"0%\" stop-color=\"#444\"/><stop offset=\"100%\" stop-color=\"#2a2a3a\"/>
        </linearGradient>
        <filter id=\"g\"><feGaussianBlur stdDeviation=\"1.5\" result=\"b\"/><feMerge><feMergeNode in=\"b\"/><feMergeNode in=\"SourceGraphic\"/></feMerge></filter>
      </defs>

      <!-- Left cable -->
      <rect x=\"4\" y=\"40\" width=\"58\" height=\"20\" rx=\"10\" fill=\"url(#cl)\"/>
      <!-- Left plug body -->
      <rect x=\"58\" y=\"33\" width=\"22\" height=\"34\" rx=\"4\" fill=\"#1e2535\" stroke=\"#3a4a5a\" stroke-width=\".8\"/>
      <circle cx=\"65\" cy=\"41\" r=\"3\" fill=\"#111\" stroke=\"#3a4a5a\" stroke-width=\".6\"/>
      <circle cx=\"65\" cy=\"59\" r=\"3\" fill=\"#111\" stroke=\"#3a4a5a\" stroke-width=\".6\"/>

      <!-- Left frayed wires -->
      <path d=\"M80 42 C88 40, 90 35, 96 30\" fill=\"none\" stroke=\"#b5651d\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M96 30 C98 28, 101 26, 103 23\" fill=\"none\" stroke=\"#daa520\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M80 45 C89 43, 92 39, 99 36\" fill=\"none\" stroke=\"#8b4513\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M99 36 C102 34, 105 32, 107 29\" fill=\"none\" stroke=\"#cd853f\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M80 50 C90 49, 94 47, 101 45\" fill=\"none\" stroke=\"#a0522d\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M101 45 C104 43, 107 42, 110 40\" fill=\"none\" stroke=\"#f4a460\" stroke-width=\"1.2\" stroke-linecap=\"round\"/>
      <path d=\"M80 55 C89 55, 92 58, 99 62\" fill=\"none\" stroke=\"#8b4513\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M99 62 C102 64, 105 67, 107 70\" fill=\"none\" stroke=\"#cd853f\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M80 58 C88 60, 90 65, 95 70\" fill=\"none\" stroke=\"#6b3a2a\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M95 70 C97 73, 100 76, 102 78\" fill=\"none\" stroke=\"#daa520\" stroke-width=\"1.2\" stroke-linecap=\"round\"/>

      <!-- Right cable -->
      <rect x=\"138\" y=\"40\" width=\"58\" height=\"20\" rx=\"10\" fill=\"url(#cr)\"/>
      <!-- Right plug body -->
      <rect x=\"120\" y=\"33\" width=\"22\" height=\"34\" rx=\"4\" fill=\"#1e2535\" stroke=\"#3a4a5a\" stroke-width=\".8\"/>
      <circle cx=\"135\" cy=\"41\" r=\"3\" fill=\"#111\" stroke=\"#3a4a5a\" stroke-width=\".6\"/>
      <circle cx=\"135\" cy=\"59\" r=\"3\" fill=\"#111\" stroke=\"#3a4a5a\" stroke-width=\".6\"/>

      <!-- Right frayed wires -->
      <path d=\"M120 42 C112 40, 110 35, 104 30\" fill=\"none\" stroke=\"#b5651d\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M104 30 C102 28, 99 26, 97 23\" fill=\"none\" stroke=\"#daa520\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M120 45 C111 43, 108 39, 101 36\" fill=\"none\" stroke=\"#8b4513\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M101 36 C98 34, 95 32, 93 29\" fill=\"none\" stroke=\"#cd853f\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M120 50 C110 49, 106 47, 99 45\" fill=\"none\" stroke=\"#a0522d\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M99 45 C96 43, 93 42, 90 40\" fill=\"none\" stroke=\"#f4a460\" stroke-width=\"1.2\" stroke-linecap=\"round\"/>
      <path d=\"M120 55 C111 55, 108 58, 101 62\" fill=\"none\" stroke=\"#8b4513\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M101 62 C98 64, 95 67, 93 70\" fill=\"none\" stroke=\"#cd853f\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M120 58 C112 60, 110 65, 105 70\" fill=\"none\" stroke=\"#6b3a2a\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M105 70 C103 73, 100 76, 98 78\" fill=\"none\" stroke=\"#daa520\" stroke-width=\"1.2\" stroke-linecap=\"round\"/>

      <!-- Lightning bolt arc in gap -->
      <g class=\"arc\" filter=\"url(#g)\">
        <path d=\"M97 28 L104 45 L99 50 L106 72\" fill=\"none\" stroke=\"#FFD700\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>
        <path d=\"M97 28 L104 45 L99 50 L106 72\" fill=\"none\" stroke=\"#FFFDE7\" stroke-width=\".8\" stroke-linecap=\"round\" stroke-linejoin=\"round\" opacity=\".9\"/>
      </g>

      <!-- Spark particles -->
      <circle class=\"s1\" cx=\"101\" cy=\"42\" r=\"1.8\" fill=\"#FFD700\"/>
      <circle class=\"s2\" cx=\"99\" cy=\"52\" r=\"1.4\" fill=\"#FFA500\"/>
      <circle class=\"s3\" cx=\"103\" cy=\"60\" r=\"1.6\" fill=\"#FFD700\"/>
      <circle class=\"s4\" cx=\"100\" cy=\"35\" r=\"1.2\" fill=\"#FFFDE7\"/>
    </svg>

    <div class=\"db-title\">No Database Connection</div>
    <div class=\"db-msg\">The application could not connect to the database.<br>Please check your database server and configuration.</div>
    <div class=\"db-actions\">
      <a class=\"db-btn\" href=\"#\" onclick=\"location.reload();return false;\">&#8635; Retry</a>
      <button class=\"db-btn recover\" type=\"button\" onclick=\"document.getElementById('dbRecoverPanel').classList.toggle('open')\">Recover Database</button>
    </div>
    " . $recoverNotice . "
    <form id=\"dbRecoverPanel\" class=\"db-recover-panel\" method=\"POST\">
      <input type=\"hidden\" name=\"db_recover_action\" value=\"recover_latest_backup\">
      <label>Admin Username
        <input type=\"text\" name=\"db_admin_username\" autocomplete=\"username\" required>
      </label>
      <label>Admin Password
        <input type=\"password\" name=\"db_admin_password\" autocomplete=\"current-password\" required>
      </label>
      <button class=\"db-btn recover\" type=\"submit\">Recover Latest Backup</button>
    </form>
    <div class=\"db-details\">Error: " . $message . "</div>
  </div>
</div>
</body>
</html>";
    exit;
}

        }

        private function handleManualRecoveryRequest() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['db_recover_action'] ?? '') !== 'recover_latest_backup') {
                return null;
            }

            $backupFile = $this->latestBackupFile();
            if ($backupFile === null) {
                return 'No database backup file was found.';
            }

            $username = trim((string)($_POST['db_admin_username'] ?? ''));
            $password = (string)($_POST['db_admin_password'] ?? '');

            if (!$this->adminCredentialsMatchBackup($backupFile, $username, $password)) {
                return 'Only an admin account from the latest backup can recover the database.';
            }

            try {
                $this->recoverBackupFile($backupFile);
                return 'recovered';
            } catch (Throwable $restoreError) {
                return 'Recovery failed: ' . $restoreError->getMessage();
            }
        }

        private function recoverBackupFile($backupFile) {
            $backupSql = file_get_contents($backupFile);
            if ($backupSql === false || trim($backupSql) === '') {
                throw new RuntimeException('Backup file is empty or cannot be read.');
            }

            $serverConn = new PDO("mysql:host=".$this->host, $this->user, $this->password);
            $serverConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $serverConn->exec("CREATE DATABASE IF NOT EXISTS `".$this->escapeIdentifier($this->dbname)."` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $serverConn->exec("USE `".$this->escapeIdentifier($this->dbname)."`");

            try {
                $serverConn->exec("SET FOREIGN_KEY_CHECKS=0");
                foreach ($this->splitSqlStatements($backupSql) as $statement) {
                    $serverConn->exec($statement);
                }
                $serverConn->exec("SET FOREIGN_KEY_CHECKS=1");
            } catch (Throwable $restoreError) {
                $serverConn->exec("SET FOREIGN_KEY_CHECKS=1");
                throw $restoreError;
            }

            $this->conn = new PDO("mysql:host=".$this->host . ";dbname=".$this->dbname, $this->user, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        private function latestBackupFile() {
            $backupDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'database';
            $files = glob($backupDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];

            if (empty($files)) {
                return null;
            }

            usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
            return $files[0];
        }

        private function runAutomaticDailyBackup() {
            try {
                $now = new DateTime('now', new DateTimeZone('Asia/Singapore'));
                $backupTime = new DateTime($now->format('Y-m-d') . ' 17:30:00', new DateTimeZone('Asia/Singapore'));

                if ($now < $backupTime) {
                    return;
                }

                $backupDir = $this->backupDirectory();
                $markerFile = $backupDir . DIRECTORY_SEPARATOR . '.auto-backup-last';
                $today = $now->format('Y-m-d');

                if (is_file($markerFile) && trim((string)file_get_contents($markerFile)) === $today) {
                    return;
                }

                $this->createDatabaseBackup($backupDir, 'auto_' . $this->dbname);
                file_put_contents($markerFile, $today);
            } catch (Throwable $backupError) {
                $backupDir = $this->backupDirectory();
                @file_put_contents(
                    $backupDir . DIRECTORY_SEPARATOR . '.auto-backup-error.log',
                    date('Y-m-d H:i:s') . ' ' . $backupError->getMessage() . PHP_EOL,
                    FILE_APPEND
                );
            }
        }

        private function backupDirectory() {
            $backupDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'database';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0775, true);
            }

            return $backupDir;
        }

        private function backupSafeName($name) {
            $name = trim((string)$name);
            $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', $name);
            $name = trim($name, '_');
            return $name !== '' ? $name : 'backup';
        }

        private function createDatabaseBackup($backupDir, $backupName) {
            $safeName = $this->backupSafeName($backupName);
            $createdAt = new DateTime('now', new DateTimeZone('Asia/Singapore'));
            $fileName = $safeName . '_' . $createdAt->format('Ymd_His') . '.sql';
            $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;
            $databaseName = (string)$this->conn->query("SELECT DATABASE()")->fetchColumn();

            $tables = $this->conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
            $dump = [];
            $dump[] = "-- JAQ Meatshop automatic database backup";
            $dump[] = "-- Database: `{$databaseName}`";
            $dump[] = "-- Created: " . $createdAt->format('Y-m-d H:i:s');
            $dump[] = "SET FOREIGN_KEY_CHECKS=0;";
            $dump[] = "";

            foreach ($tables as $tableRow) {
                $table = $tableRow[0];
                $quotedTable = '`' . str_replace('`', '``', $table) . '`';

                $createStmt = $this->conn->query("SHOW CREATE TABLE {$quotedTable}")->fetch(PDO::FETCH_ASSOC);
                $createSql = $createStmt['Create Table'] ?? array_values($createStmt)[1];

                $dump[] = "DROP TABLE IF EXISTS {$quotedTable};";
                $dump[] = $createSql . ';';
                $dump[] = "";

                $rows = $this->conn->query("SELECT * FROM {$quotedTable}", PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $columns = array_map(fn($column) => '`' . str_replace('`', '``', $column) . '`', array_keys($row));
                    $values = array_map(fn($value) => $value === null ? 'NULL' : $this->conn->quote((string)$value), array_values($row));
                    $dump[] = "INSERT INTO {$quotedTable} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");";
                }

                $dump[] = "";
            }

            $dump[] = "SET FOREIGN_KEY_CHECKS=1;";
            file_put_contents($filePath, implode("\n", $dump));

            return $fileName;
        }

        private function adminCredentialsMatchBackup($backupFile, $username, $password) {
            if ($username === '' || $password === '') {
                return false;
            }

            $sql = file_get_contents($backupFile);
            if ($sql === false || trim($sql) === '') {
                return false;
            }

            preg_match_all('/INSERT\s+INTO\s+`?users`?\s*\((.*?)\)\s*VALUES\s*\((.*?)\)/is', $sql, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $columns = array_map(function($column) {
                    return trim($column, " `\t\n\r\0\x0B");
                }, explode(',', $match[1]));
                $values = $this->splitSqlValueList($match[2]);

                if (count($columns) !== count($values)) {
                    continue;
                }

                $row = array_combine($columns, array_map([$this, 'unquoteSqlValue'], $values));
                $deletedAt = $row['DeletedAt'] ?? null;

                if (($row['role'] ?? '') === 'admin'
                    && ($row['username'] ?? '') === $username
                    && ($deletedAt === null || strtoupper((string)$deletedAt) === 'NULL')
                    && password_verify($password, (string)($row['password'] ?? ''))) {
                    return true;
                }
            }

            return false;
        }

        private function splitSqlValueList($sql) {
            $values = [];
            $current = '';
            $length = strlen($sql);
            $quote = null;
            $escape = false;

            for ($i = 0; $i < $length; $i++) {
                $char = $sql[$i];

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

                if ($char === "'" || $char === '"') {
                    $quote = $char;
                    $current .= $char;
                    continue;
                }

                if ($char === ',') {
                    $values[] = trim($current);
                    $current = '';
                    continue;
                }

                $current .= $char;
            }

            $values[] = trim($current);
            return $values;
        }

        private function unquoteSqlValue($value) {
            $value = trim((string)$value);
            if (strtoupper($value) === 'NULL') {
                return null;
            }

            if (strlen($value) >= 2 && (($value[0] === "'" && substr($value, -1) === "'") || ($value[0] === '"' && substr($value, -1) === '"'))) {
                $value = substr($value, 1, -1);
                $value = str_replace(["\\'", '\\"', '\\\\', "''"], ["'", '"', '\\', "'"], $value);
            }

            return $value;
        }

        private function escapeIdentifier($identifier) {
            return str_replace('`', '``', $identifier);
        }

        private function splitSqlStatements($sql) {
            $statements = [];
            $current = '';
            $length = strlen($sql);
            $quote = null;
            $escape = false;

            for ($i = 0; $i < $length; $i++) {
                $char = $sql[$i];
                $next = $i + 1 < $length ? $sql[$i + 1] : '';

                if ($quote === null && $char === '-' && $next === '-') {
                    while ($i < $length && $sql[$i] !== "\n") {
                        $i++;
                    }
                    continue;
                }

                if ($quote === null && $char === '/' && $next === '*') {
                    $i += 2;
                    while ($i + 1 < $length && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
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

    }

?>
