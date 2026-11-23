<?php
$pageTitle = 'Point of Sale';
require_once '../includes/header.php';

$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.name")->fetchAll();

$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $items = json_decode($_POST['cart_items'] ?? '[]', true);
    $paymentAmount = floatval($_POST['payment_amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';

    if (empty($items)) {
        $message = 'Cart is empty.';
        $messageType = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            $total = 0;
            foreach ($items as $item) {
                $productId = intval($item['id']);
                $qty = intval($item['qty']);
                $prod = $pdo->prepare("SELECT price, stock FROM products WHERE id = ?");
                $prod->execute([$productId]);
                $p = $prod->fetch();
                if (!$p || $p['stock'] < $qty) {
                    throw new Exception("Insufficient stock for product ID $productId.");
                }
                $subtotal = $p['price'] * $qty;
                $total += $subtotal;
                $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$qty, $productId]);
            }
            $change = $paymentAmount - $total;
            $stmt = $pdo->prepare("INSERT INTO sales (user_id, total, payment_method, payment_amount, change_amount) VALUES (?,?,?,?,?)");
            $stmt->execute([$_SESSION['user_id'], $total, $paymentMethod, $paymentAmount, $change]);
            $saleId = $pdo->lastInsertId();
            foreach ($items as $item) {
                $productId = intval($item['id']);
                $qty = intval($item['qty']);
                $prod = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $prod->execute([$productId]);
                $p = $prod->fetch();
                $subtotal = $p['price'] * $qty;
                $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal) VALUES (?,?,?,?,?)")
                    ->execute([$saleId, $productId, $qty, $p['price'], $subtotal]);
            }
            $pdo->commit();
            header("Location: pos.php?sale_success=1");
            exit;
            $message = 'Sale completed successfully!';
            $messageType = 'success';
            
            // Prepare receipt data for JavaScript
            $receiptItems = [];
            foreach ($items as $item) {
                $receiptItems[] = [
                    'name' => $item['name'],
                    'qty' => intval($item['qty']),
                    'subtotal' => floatval($item['price']) * intval($item['qty'])
                ];
            }
            $receiptData = json_encode([
                'orderId' => intval($saleId),
                'date' => date('M j, Y g:i A'),
                'cashier' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Vladimer',
                'items' => $receiptItems,
                'total' => floatval($total),
                'payment' => floatval($paymentAmount),
                'change' => floatval($change)
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<?php if ($messageType === 'success' && isset($receiptData)): ?>
<script>
    const receiptData = <?php echo $receiptData; ?>;
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            showReceipt(
                receiptData.orderId,
                receiptData.date,
                receiptData.cashier,
                receiptData.items,
                receiptData.total,
                receiptData.payment,
                receiptData.change
            );
        }, 500);
    });
</script>
<?php endif; ?>

<div class="products-header">
    <h2>Point of Sale</h2>
    <div class="search-box"><input type="text" id="productSearch" placeholder="Search products..."></div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="pos-layout">
    <div>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['stock']; ?>">
                <div class="product-image"><img src="../assets/uploads/products/<?php echo htmlspecialchars($product['image'] ?? 'placeholder.png'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="pos-product-img"></div>
                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                <div class="product-stock <?php echo $product['stock'] <= 5 ? 'low' : ''; ?>">Stock: <?php echo $product['stock']; ?></div>
                <?php if ($product['stock'] > 0): ?>
                <button class="btn btn-primary btn-sm" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['name'])); ?>', <?php echo $product['price']; ?>, <?php echo $product['stock']; ?>)">
                    <i class="fa-solid fa-plus"></i> Add
                </button>
                <?php else: ?>
                <button class="btn btn-outline btn-sm" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <div style="grid-column:1/-1; text-align:center; color:#64748b; padding:40px;">No products available.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="cart-panel">
        <h3><i class="fa-solid fa-cart-shopping" style="margin-right:8px; color:#059669;"></i>Cart</h3>
        <div id="cartItems" class="cart-items">
            <div class="empty-cart"><p>Your cart is empty</p><p style="font-size:12px;">Add products to start selling</p></div>
        </div>
        <div class="cart-total"><span>Total:</span><span id="cartTotal" class="cart-total-value">₱0.00</span></div>
        <form method="POST" action="" onsubmit="return validateCheckout()">
            <input type="hidden" name="cart_items" id="cartItemsInput">
            <input type="hidden" name="checkout" value="1">
            <div class="checkout-form">
                <label>Payment Method</label>
                <select name="payment_method" style="width:100%; padding:10px 14px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px; margin-bottom:0;">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                </select>
                <label>Amount Received</label>
                <input type="number" name="payment_amount" id="paymentAmount" step="0.01" min="0" placeholder="0.00">
                <div class="change-display" style="margin-top:10px; font-size:13px; color:#64748b;">₱0.00</div>
            </div>
            <button type="submit" class="btn btn-success" style="width:100%; margin-top:16px; padding:12px;">Complete Sale</button>
        </form>
    </div>
</div>
<script>
let cart = [];

function addToCart(id, name, price, stock) {
    const existing = cart.find(item => item.id == id);
    if (existing) {
        if (existing.qty >= stock) { alert('Maximum stock reached.'); return; }
        existing.qty++;
    } else {
        cart.push({ id, name, price: parseFloat(price), stock, qty: 1 });
    }
    renderCart();
}

function removeFromCart(index) { cart.splice(index, 1); renderCart(); }

function updateQty(index, newQty) {
    const product = cart[index];
    if (newQty < 1) { removeFromCart(index); return; }
    if (newQty > product.stock) { alert('Maximum stock reached.'); return; }
    cart[index].qty = newQty;
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const totalEl = document.getElementById('cartTotal');
    const input = document.getElementById('cartItemsInput');
    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-cart"><p>Your cart is empty</p><p style="font-size:12px;">Add products to start selling</p></div>';
        totalEl.textContent = '₱0.00'; input.value = '[]'; return;
    }
    let total = 0, html = '';
    cart.forEach((item, index) => {
        const subtotal = item.price * item.qty;
        total += subtotal;
        html += `<div class="cart-item" data-product-id="${item.id}">
            <div class="cart-item-info"><h4>${item.name}</h4><p>₱${item.price.toFixed(2)} x ${item.qty} = ₱${subtotal.toFixed(2)}</p></div>
            <div class="cart-item-actions">
                <button class="btn btn-outline btn-sm" onclick="updateQty(${index}, ${item.qty - 1})">-</button>
                <input type="number" class="qty-input" value="${item.qty}" min="1" max="${item.stock}" onchange="updateQty(${index}, this.value)">
                <button class="btn btn-outline btn-sm" onclick="updateQty(${index}, ${item.qty + 1})">+</button>
                <button class="btn btn-danger btn-sm" onclick="removeFromCart(${index})" style="margin-left:8px;">Remove</button>
            </div></div>`;
    });
    container.innerHTML = html;
    totalEl.textContent = '₱' + total.toFixed(2);
    input.value = JSON.stringify(cart);
}

function validateCheckout() {
    if (cart.length === 0) { alert('Cart is empty.'); return false; }
    const payment = parseFloat(document.getElementById('paymentAmount').value) || 0;
    const total = parseFloat(document.getElementById('cartTotal').textContent.replace('₱',''));
    if (payment < total) { alert('Payment amount is less than total.'); return false; }
    return true;
}

const paymentInput = document.getElementById('paymentAmount');
const totalEl = document.getElementById('cartTotal');
const changeDisplay = document.querySelector('.change-display');
if (paymentInput && totalEl && changeDisplay) {
    paymentInput.addEventListener('input', function() {
        const paid = parseFloat(this.value) || 0;
        const total = parseFloat(totalEl.textContent.replace('₱','')) || 0;
        const change = paid - total;
        changeDisplay.textContent = (change >= 0 ? '₱' : 'Short: ₱') + change.toFixed(2);
        changeDisplay.style.color = change >= 0 ? '#10b981' : '#dc2626';
    });
}

document.getElementById('productSearch').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.querySelector('.product-name').textContent.toLowerCase();
        card.style.display = name.includes(term) ? 'block' : 'none';
    });
});

// Receipt Modal Functions
function showReceipt(orderId, date, cashier, items, total, payment, change) {
    document.getElementById('receiptOrderId').textContent = orderId;
    document.getElementById('receiptDate').textContent = date;
    document.getElementById('receiptCashier').textContent = cashier;

    let itemsHtml = '';
    items.forEach(item => {
        itemsHtml += `
            <div class="receipt-item">
                <span class="receipt-item-name">${item.name}</span>
                <span class="receipt-item-qty">${item.qty}</span>
                <span class="receipt-item-price">₱${parseFloat(item.subtotal).toFixed(2)}</span>
            </div>`;
    });
    document.getElementById('receiptItems').innerHTML = itemsHtml;

    let totalsHtml = `
        <div class="receipt-item">
            <span class="receipt-item-name">Subtotal</span>
            <span class="receipt-item-price">₱${parseFloat(total).toFixed(2)}</span>
        </div>`;
    document.getElementById('receiptTotals').innerHTML = totalsHtml + `
        <div class="receipt-total-line">
            <span>TOTAL</span>
            <span>₱${parseFloat(total).toFixed(2)}</span>
        </div>`;

    let paymentHtml = `
        <div><span>Payment:</span><span>₱${parseFloat(payment).toFixed(2)}</span></div>
        <div><span>Change:</span><span>₱${parseFloat(change).toFixed(2)}</span></div>`;
    document.getElementById('receiptPayment').innerHTML = paymentHtml;

    document.getElementById('receiptModal').classList.add('active');
}

function closeReceipt() {
    document.getElementById('receiptModal').classList.remove('active');
    cart = [];
    renderCart();
    document.getElementById('paymentAmount').value = '';
    document.querySelector('.change-display').textContent = '₱0.00';
}

function printReceipt() {
    window.print();
}

// Close modal when clicking outside
document.getElementById('receiptModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReceipt();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReceipt();
    }
});
</script>
<!-- Receipt Modal -->
<div id="receiptModal" class="receipt-modal">
    <div class="receipt-print">
        <div class="receipt-header">
            <div class="store-name">Point of Sale Version 1</div>
            <div class="store-addr">123 Business Street, City, Country</div>
            <div class="receipt-title">OFFICIAL RECEIPT</div>
        </div>
        <div class="receipt-info" id="receiptInfo">
            <div><span>Order #:</span><span id="receiptOrderId">001</span></div>
            <div><span>Date:</span><span id="receiptDate">--</span></div>
            <div><span>Cashier:</span><span id="receiptCashier">Vladimer</span></div>
        </div>
        <div class="receipt-items" id="receiptItems">
            <!-- Items will be injected here -->
        </div>
        <div class="receipt-totals" id="receiptTotals">
            <!-- Totals will be injected here -->
        </div>
        <div class="receipt-payment" id="receiptPayment">
            <!-- Payment info will be injected here -->
        </div>
        <div class="receipt-footer">
            <div class="thank-you">Thank You For Your Purchase!</div>
            <div>This is a computer-generated receipt.</div>
        </div>
        <div class="receipt-actions">
            <button class="btn-print-receipt" onclick="printReceipt()"><i class="fa-solid fa-print"></i> Print</button>
            <button class="btn-close-receipt" onclick="closeReceipt()"><i class="fa-solid fa-times"></i> Close</button>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>


