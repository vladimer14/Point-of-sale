<?php
$pageTitle = 'Sales';
require_once '../includes/header.php';

$sales = $pdo->query("
    SELECT s.id, s.total, s.payment_method, s.payment_amount, s.change_amount, s.created_at, u.username 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.created_at DESC
")->fetchAll();
?>
<div class="table-header">
    <h3>Sales History</h3>
</div>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Cashier</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Change</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $s): ?>
            <tr>
                <td>#<?php echo $s['id']; ?></td>
                <td><?php echo htmlspecialchars($s['username']); ?></td>
                <td>₱<?php echo number_format($s['total'], 2); ?></td>
                <td><?php echo ucfirst($s['payment_method']); ?></td>
                <td>₱<?php echo number_format($s['change_amount'], 2); ?></td>
                <td><?php echo date('M j, Y g:i A', strtotime($s['created_at'])); ?></td>
                <td>
                    <a href="sale_details.php?id=<?php echo $s['id']; ?>" class="btn btn-outline btn-sm">Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sales)): ?>
            <tr><td colspan="7" style="text-align:center; color:#64748b;">No sales recorded.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>