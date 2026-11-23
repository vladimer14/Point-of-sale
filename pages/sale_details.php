<?php
$pageTitle = 'Sale Details';
require_once '../includes/header.php';

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT s.*, u.username 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    die('<div style="padding:40px; text-align:center;"><h3>Sale not found</h3></div>');
}

$stmt2 = $pdo->prepare("
    SELECT si.*, p.name 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmt2->execute([$id]);
$items = $stmt2->fetchAll();
?>
<div class="table-header">
    <h3>Order #<?php echo $sale['id']; ?></h3>
    <a href="sales.php" class="btn btn-outline btn-sm">Back</a>
</div>
<div class="table-container">
    <table>
        <tr><td><strong>Cashier:</strong></td><td><?php echo htmlspecialchars($sale['username']); ?></td></tr>
        <tr><td><strong>Date:</strong></td><td><?php echo date('M j, Y g:i A', strtotime($sale['created_at'])); ?></td></tr>
        <tr><td><strong>Payment Method:</strong></td><td><?php echo ucfirst($sale['payment_method']); ?></td></tr>
        <tr><td><strong>Amount Received:</strong></td><td>₱<?php echo number_format($sale['payment_amount'], 2); ?></td></tr>
        <tr><td><strong>Change:</strong></td><td>₱<?php echo number_format($sale['change_amount'], 2); ?></td></tr>
        <tr><td><strong>Total:</strong></td><td>₱<?php echo number_format($sale['total'], 2); ?></td></tr>
    </table>
</div>

<div class="table-container" style="margin-top:20px;">
    <h3 style="padding:20px 24px; margin:0; border-bottom:1px solid #e2e8f0;">Items</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>