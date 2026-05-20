<?php
session_start();
 
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header("Location: ../index.php");
    exit;
}
 
$prices = [
    "Beef"    => 350,
    "Pork"    => 280,
    "Chicken" => 180,
];
 
$icons = [
    "Beef"    => "🥩",
    "Pork"    => "🍖",
    "Chicken" => "🍗",
];
 
require_once "../DatabaseConnection/database.php";

$db = new Database();
$conn = $db->conn;

$imageColumnStmt = $conn->prepare("SHOW COLUMNS FROM product LIKE 'ProductImage'");
$imageColumnStmt->execute();
if (!$imageColumnStmt->fetch(PDO::FETCH_ASSOC)) {
    $conn->exec("ALTER TABLE product ADD ProductImage VARCHAR(255) NULL");
}

$partColumnStmt = $conn->prepare("SHOW COLUMNS FROM product LIKE 'ProductPart'");
$partColumnStmt->execute();
if (!$partColumnStmt->fetch(PDO::FETCH_ASSOC)) {
    $conn->exec("ALTER TABLE product ADD ProductPart VARCHAR(100) NULL AFTER ProductName");
}

$typeColumnStmt = $conn->prepare("SHOW COLUMNS FROM product LIKE 'ProductType'");
$typeColumnStmt->execute();
if (!$typeColumnStmt->fetch(PDO::FETCH_ASSOC)) {
    $conn->exec("ALTER TABLE product ADD ProductType VARCHAR(50) NULL AFTER ProductPart");
}

function productSearchText($product) {
    return strtolower(implode(' ', [
        $product['ProductType'] ?? '',
        $product['CategoryName'] ?? '',
        $product['ProductName'] ?? '',
        $product['ProductPart'] ?? '',
    ]));
}

function productDisplayName($product) {
    $category = trim($product['CategoryName'] ?? '');
    $part = trim($product['ProductPart'] ?? '');
    $name = trim($product['ProductName'] ?? '');
    $type = trim($product['ProductType'] ?? '');

    if ($type === 'Processed Foods' || $category === 'Processed Foods') {
        return $part !== '' ? $part : $name;
    }

    if ($category !== '' && $part !== '') {
        return $category . ' - ' . $part;
    }

    if ($part !== '') {
        return $part;
    }

    return $name;
}

function productIcon($product) {
    $lower = productSearchText($product);
    if (strpos($lower, 'beef') !== false) return '🥩';
    if (strpos($lower, 'pork') !== false) return '🍖';
    if (strpos($lower, 'chicken') !== false) return '🍗';
    if (strpos($lower, 'processed') !== false || strpos($lower, 'hotdog') !== false || strpos($lower, 'longganisa') !== false || strpos($lower, 'tocino') !== false || strpos($lower, 'bacon') !== false || strpos($lower, 'ham') !== false) return '🛒';
    return '🥩';
}

function productImage($product) {
    if (!empty($product['ProductImage'])) {
        return '../' . $product['ProductImage'];
    }

    $lower = productSearchText($product);
    if (strpos($lower, 'beef') !== false) return '../uploads/products/beef.jpg';
    if (strpos($lower, 'pork') !== false) return '../uploads/products/pork.jpg';
    if (strpos($lower, 'chicken') !== false) return '../uploads/products/chicken.jpg';
    return '../uploads/products/pork.jpg';
}

function productClass($product) {
    $lower = productSearchText($product);
    if (strpos($lower, 'beef') !== false) return 'beef';
    if (strpos($lower, 'pork') !== false) return 'pork';
    if (strpos($lower, 'chicken') !== false) return 'chicken';
    if (strpos($lower, 'processed') !== false) return 'processed';
    return 'pork';
}

$productStmt = $conn->prepare("
    SELECT p.ProductID, p.ProductName, p.ProductPart, p.ProductType, p.PricePerKg, p.StockWeight, p.ProductImage, c.CategoryName
    FROM product p
    JOIN category c ON p.CategoryID = c.CategoryID
    WHERE p.Status = 'Available'
    ORDER BY COALESCE(p.ProductType, 'Meat'), c.CategoryName, p.ProductPart, p.ProductName
");
$productStmt->execute();
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

$productsById = [];
$sellableProductCount = 0;
foreach ($products as $productRow) {
    $productsById[(int)$productRow['ProductID']] = $productRow;
    if ((float)$productRow['StockWeight'] > 0) {
        $sellableProductCount++;
    }
}

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $productId = (int)$_POST['product'];
        $weight  = floatval($_POST['weight']);
        if (isset($productsById[$productId]) && $weight > 0) {
            $product = $productsById[$productId];
            $displayName = productDisplayName($product);
            $price = (float)$product['PricePerKg'];
            $availableStock = (float)$product['StockWeight'];
            if ($availableStock <= 0) {
                $_SESSION['cart_error'] = $displayName . ' is out of stock.';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
            $currentCartWeight = 0;
            foreach ($_SESSION['cart'] as $item) {
                if ((int)$item['id'] === $productId) {
                    $currentCartWeight += (float)$item['weight'];
                }
            }
            if (($currentCartWeight + $weight) > $availableStock) {
                $_SESSION['cart_error'] = 'Not enough stock for ' . $displayName . '.';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ((int)$item['id'] === $productId) {
                    $item['weight'] = round($item['weight'] + $weight, 2);
                    $item['total']  = round($price * $item['weight'], 2);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id'    => $productId,
                    'name'  => $displayName,
                    'icon'  => productIcon($product),
                    'price' => $price,
                    'weight'=> $weight,
                    'total' => round($price * $weight, 2),
                ];
            }
        }
    } elseif ($_POST['action'] === 'remove') {
        $remove = (int)$_POST['product'];
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'] ?? [], fn($i) => (int)$i['id'] !== $remove));
    } elseif ($_POST['action'] === 'clear') {
        $_SESSION['cart'] = [];
    } elseif ($_POST['action'] === 'checkout') {
        $cart = $_SESSION['cart'] ?? [];
        if (!empty($cart)) {
            try {
                $conn->beginTransaction();
                $stockStmt = $conn->prepare("SELECT StockWeight FROM product WHERE ProductID = ? FOR UPDATE");
                $saleStmt = $conn->prepare("
                    INSERT INTO transactions (ProductID, WeightSold, TotalPrice, DateTime)
                    VALUES (?, ?, ?, NOW())
                ");
                $updateStockStmt = $conn->prepare("
                    UPDATE product
                    SET StockWeight = StockWeight - ?
                    WHERE ProductID = ?
                ");

                foreach ($cart as $item) {
                    $stockStmt->execute([$item['id']]);
                    $stock = (float)($stockStmt->fetchColumn() ?? 0);
                    if ($stock < (float)$item['weight']) {
                        throw new Exception('Not enough stock for ' . $item['name'] . '.');
                    }
                    $saleStmt->execute([$item['id'], $item['weight'], $item['total']]);
                    $updateStockStmt->execute([$item['weight'], $item['id']]);
                }

                $receiptItems = $cart;
                $receiptTotal = array_sum(array_column($cart, 'total'));
                $receiptWeight = array_sum(array_column($cart, 'weight'));
                $receiptDate = date('Y-m-d H:i:s');
                $cashReceived = (float)($_POST['cash_received'] ?? 0);

                $conn->commit();
                $_SESSION['cart'] = [];
                $_SESSION['last_receipt'] = [
                    'items' => $receiptItems,
                    'total' => $receiptTotal,
                    'weight' => $receiptWeight,
                    'cash_received' => $cashReceived,
                    'change' => max(0, $cashReceived - $receiptTotal),
                    'date' => $receiptDate,
                    'cashier' => $_SESSION['username'],
                ];
            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $_SESSION['cart_error'] = $e->getMessage();
            }
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
 
$cart = $_SESSION['cart'] ?? [];
$cart = array_values(array_filter($cart, fn($item) => isset($item['id'])));
$_SESSION['cart'] = $cart;
$grand_total = array_sum(array_column($cart, 'total'));
$total_weight = array_sum(array_column($cart, 'weight'));
$cart_count = count($cart);
$lastReceipt = $_SESSION['last_receipt'] ?? null;
unset($_SESSION['last_receipt']);
$cartError = $_SESSION['cart_error'] ?? null;
unset($_SESSION['cart_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cashier — JAQ Meatshop</title>
<link rel="stylesheet" href="../css/cashier_style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
 
<div class="pos-wrap">
  <!-- LEFT PANEL -->
  <div class="left-panel">
    <div class="header-top">
      <div>
        <div class="shop-name">JAQ<span>Meatshop</span></div>
        
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end">
        <div class="cashier-badge">Cashier: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>
        <a href="../Authentication/logout.php" class="logout-link">↪ Sign out</a>
      </div>
    </div>
 
    <div class="select-product-panel">
      <div class="section-label">Select Product</div>
      <div class="products-scroll">
        <div class="products-grid">
          <?php if (empty($products)): ?>
            <div class="empty-cart" style="grid-column:1/-1">
              <div style="font-size:0.9rem;font-style:italic">No available products.</div>
            </div>
          <?php else: ?>
            <?php foreach ($products as $index => $product): ?>
              <?php $isOutOfStock = (float)$product['StockWeight'] <= 0; ?>
              <?php $isProcessed = ($product['ProductType'] ?? '') === 'Processed Foods' || $product['CategoryName'] === 'Processed Foods'; ?>
              <div
                class="product-card <?= productClass($product) ?> <?= $isOutOfStock ? 'out-of-stock' : '' ?>"
                <?php if (!$isOutOfStock): ?>
                  onclick="pickProduct('<?= htmlspecialchars($product['ProductID']) ?>')"
                <?php endif; ?>>
                <div class="product-tag <?= $isOutOfStock ? 'stock-out' : '' ?>">
                  <?= $isOutOfStock ? 'Out of stock' : 'Available' ?>
                </div>
                <img src="<?= htmlspecialchars(productImage($product)) ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>" class="product-img">
                <div class="product-category"><?= htmlspecialchars($isProcessed ? 'Processed Foods' : $product['CategoryName']) ?></div>
                <div class="product-name"><?= htmlspecialchars($product['ProductPart'] ?: $product['ProductName']) ?></div>
                <?php if (!$isProcessed && !empty($product['ProductPart']) && $product['ProductName'] !== $product['ProductPart']): ?>
                  <div class="product-base-name"><?= htmlspecialchars($product['ProductName']) ?></div>
                <?php endif; ?>
                <div class="product-price">₱<strong><?= number_format((float)$product['PricePerKg'], 2) ?></strong>/kg</div>
                <div class="product-stock <?= $isOutOfStock ? 'stock-empty' : '' ?>">
                  <?= $isOutOfStock ? 'Availability: ' : 'Available: ' ?>
                  <strong><?= $isOutOfStock ? 'Out of stock' : number_format((float)$product['StockWeight'], 2) . ' kg' ?></strong>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
 
    <div class="add-cart-panel">
      <div class="section-label">Add to Cart</div>
      <div class="add-form">
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="form-row">
            <div class="field-group">
              <div class="field-label">Weight (kg)</div>
              <input class="weight-input" type="number" name="weight" id="weightInput" min="0.01" step="0.01" required>
            </div>
            <div class="field-group">
              <div class="field-label">Product</div>
              <select class="product-select" name="product" id="productSelect" required>
                <?php foreach ($products as $product): ?>
                  <?php $isOutOfStock = (float)$product['StockWeight'] <= 0; ?>
                  <option value="<?= htmlspecialchars($product['ProductID']) ?>" <?= $isOutOfStock ? 'disabled' : '' ?>>
                    <?= productIcon($product) ?> <?= htmlspecialchars(productDisplayName($product)) ?> — ₱<?= number_format((float)$product['PricePerKg'], 2) ?>/kg — <?= $isOutOfStock ? 'Out of stock' : 'Stock: ' . number_format((float)$product['StockWeight'], 2) . ' kg' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field-group">
              <div class="field-label">&nbsp;</div>
              <button type="submit" class="btn-add" <?= $sellableProductCount === 0 ? 'disabled' : '' ?>>+ Add</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
 
  <!-- RIGHT PANEL -->
  <div class="right-panel">
    <div class="cart-header">
      <div class="cart-title">
        Order <span class="cart-count"><?= $cart_count ?></span>
      </div>
      <div class="cart-subtitle">Today's transaction</div>
    </div>
 
    <div class="cart-items">
      <?php if (empty($cart)): ?>
        <div class="empty-cart">
          <div class="empty-icon">🛒</div>
          <div style="font-size:0.9rem;font-style:italic">No items yet.<br>Select a product and add weight.</div>
        </div>
      <?php else: ?>
        <?php foreach ($cart as $item): ?>
        <div class="cart-item">
          <div>
            <div class="item-name"><?= $item['icon'] ?> <?= htmlspecialchars($item['name']) ?></div>
            <div class="item-detail"><?= number_format($item['weight'], 2) ?> kg × ₱<?= $item['price'] ?>/kg</div>
          </div>
          <div class="item-right">
            <div class="item-total">₱<?= number_format($item['total'], 2) ?></div>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="product" value="<?= htmlspecialchars($item['id']) ?>">
              <button type="submit" class="btn-remove" title="Remove">✕</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
 
    <div class="cart-footer">
      <div class="summary-row">
        <span class="summary-label">Items</span>
        <span class="summary-val"><?= $cart_count ?> item<?= $cart_count !== 1 ? 's' : '' ?></span>
      </div>
      <div class="summary-row">
        <span class="summary-label">Total weight</span>
        <span class="summary-val"><?= number_format($total_weight, 2) ?> kg</span>
      </div>
      <hr class="divider">
      <div class="total-row">
        <span class="total-label">Total</span>
        <span class="total-amount">₱<?= number_format($grand_total, 2) ?></span>
      </div>
      <button class="btn-checkout" <?= empty($cart) ? 'disabled' : '' ?> onclick="document.getElementById('modalOverlay').classList.add('open')">
        Proceed to Checkout
      </button>
      <form method="POST">
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="btn-clear">✕ Clear Order</button>
      </form>
    </div>
  </div>
</div>
 
<!-- CHECKOUT MODAL -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-header">
      <div class="receipt-title">🧾 Order Summary</div>
      <div class="receipt-subtitle"><?= date('l, F j, Y · h:i A') ?></div>
    </div>
    <div>
      <?php foreach ($cart as $item): ?>
      <div class="receipt-item">
        <span><?= $item['icon'] ?> <?= htmlspecialchars($item['name']) ?> (<?= number_format($item['weight'], 2) ?> kg)</span>
        <span>₱<?= number_format($item['total'], 2) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="receipt-total">
      <span>Total Due</span>
      <span class="receipt-total-amount">₱<?= number_format($grand_total, 2) ?></span>
    </div>
    <div class="payment-section">
      <div class="payment-label">Cash Received (₱)</div>
      <input class="payment-input" type="number" id="cashInput" placeholder="0.00" oninput="computeChange(<?= $grand_total ?>)" autofocus>
      <div class="change-display" id="changeDisplay">
        <span class="change-label">Change</span>
        <span class="change-amount" id="changeAmount">₱0.00</span>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="document.getElementById('modalOverlay').classList.remove('open')">← Back</button>
      <form method="POST" style="flex:1">
        <input type="hidden" name="action" value="checkout">
        <input type="hidden" name="cash_received" id="cashReceivedInput">
        <button type="submit" class="btn-confirm" id="confirmBtn" disabled style="width:100%">✓ Confirm Sale</button>
      </form>
    </div>
  </div>
</div>

<?php if ($lastReceipt): ?>
<div id="printReceipt" class="print-receipt" style="display:none" aria-hidden="true">
  <div class="print-shop">JAQ Meatshop</div>
  <div class="print-sub">Official Receipt</div>
  <div class="print-meta">Cashier: <?= htmlspecialchars($lastReceipt['cashier']) ?></div>
  <div class="print-meta">Date: <?= htmlspecialchars(date('M d, Y h:i A', strtotime($lastReceipt['date']))) ?></div>
  <div class="print-line"></div>
  <?php foreach ($lastReceipt['items'] as $item): ?>
    <div class="print-row">
      <span><?= htmlspecialchars($item['name']) ?> (<?= number_format((float)$item['weight'], 2) ?> kg)</span>
      <span>₱<?= number_format((float)$item['total'], 2) ?></span>
    </div>
  <?php endforeach; ?>
  <div class="print-line"></div>
  <div class="print-row">
    <strong>Total Weight</strong>
    <strong><?= number_format((float)$lastReceipt['weight'], 2) ?> kg</strong>
  </div>
  <div class="print-row">
    <strong>Total</strong>
    <strong>₱<?= number_format((float)$lastReceipt['total'], 2) ?></strong>
  </div>
  <div class="print-row">
    <span>Cash Received</span>
    <span>₱<?= number_format((float)$lastReceipt['cash_received'], 2) ?></span>
  </div>
  <div class="print-row">
    <span>Change</span>
    <span>₱<?= number_format((float)$lastReceipt['change'], 2) ?></span>
  </div>
  <div class="print-thanks">Thank you!</div>
</div>
<?php endif; ?>
 
<script>
function pickProduct(name, price) {
  if (event.currentTarget.classList.contains('out-of-stock')) return;
  document.querySelectorAll('.product-card').forEach(c => c.classList.remove('active'));
  event.currentTarget.classList.add('active');
  document.getElementById('productSelect').value = name;
  document.getElementById('weightInput').focus();
}
 
function computeChange(total) {
  const cash = parseFloat(document.getElementById('cashInput').value) || 0;
  const change = cash - total;
  const disp = document.getElementById('changeDisplay');
  const amt = document.getElementById('changeAmount');
  const btn = document.getElementById('confirmBtn');
  const cashInputHidden = document.getElementById('cashReceivedInput');
  if (cashInputHidden) cashInputHidden.value = cash.toFixed(2);
  if (cash <= 0) { amt.textContent = '₱0.00'; disp.className = 'change-display'; btn.disabled = true; return; }
  if (change < 0) {
    amt.textContent = '-₱' + Math.abs(change).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    disp.className = 'change-display insufficient';
    btn.disabled = true;
  } else {
    amt.textContent = '₱' + change.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    disp.className = 'change-display';
    btn.disabled = false;
  }
}
 
document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});

const productSelect = document.getElementById('productSelect');
if (productSelect) {
  const firstAvailableOption = productSelect.querySelector('option:not(:disabled)');
  if (firstAvailableOption) {
    productSelect.value = firstAvailableOption.value;
  }
}

<?php if ($cartError): ?>
if (typeof Swal !== 'undefined') {
  Swal.fire({
    title: 'Unable to add item',
    text: <?= json_encode($cartError) ?>,
    icon: 'error',
    confirmButtonColor: '#B85C38'
  });
}
<?php endif; ?>

<?php if ($lastReceipt): ?>
if (typeof Swal !== 'undefined') {
  Swal.fire({
    title: 'Sale completed',
    text: 'Transaction saved successfully.',
    icon: 'success',
    showCancelButton: true,
    confirmButtonText: 'Print Receipt',
    cancelButtonText: 'Close',
    confirmButtonColor: '#2D7A4F',
    cancelButtonColor: '#6B4C3B'
  }).then(function(result) {
    if (result.isConfirmed) {
      document.body.classList.add('receipt-printing');
      window.print();
    }
  });
}
<?php endif; ?>

window.addEventListener('afterprint', function() {
  document.body.classList.remove('receipt-printing');
});
</script>
</body>
</html>
