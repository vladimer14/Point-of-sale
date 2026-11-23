<?php
$pageTitle = 'Dashboard';
require_once '../includes/header.php';

// Stats
$totalSales = $pdo->query("SELECT COALESCE(SUM(total),0) FROM sales")->fetchColumn();
$todaySales = $pdo->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5")->fetchColumn();

// Recent sales
$recentSales = $pdo->query("
    SELECT s.id, s.total, s.created_at, u.username 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-wallet"></i></div>
        <div class="stat-info">
            <h3>₱<?php echo number_format($totalSales, 2); ?></h3>
            <p>Total Sales</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="stat-info">
            <h3>₱<?php echo number_format($todaySales, 2); ?></h3>
            <p>Sales Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-box"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($totalProducts); ?></h3>
            <p>Total Products</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?php echo $lowStock > 0 ? 'red' : 'orange'; ?>"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($lowStock); ?></h3>
            <p>Low Stock Items</p>
        </div>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Recent Sales</h3>
        <a href="sales.php" class="btn btn-primary btn-sm">View All Sales</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Cashier</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentSales as $sale): ?>
            <tr>
                <td>#<?php echo $sale['id']; ?></td>
                <td><?php echo htmlspecialchars($sale['username']); ?></td>
                <td>₱<?php echo number_format($sale['total'], 2); ?></td>
                <td><?php echo date('M j, Y g:i A', strtotime($sale['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentSales)): ?>
            <tr><td colspan="4" style="text-align:center; color:#64748b;">No sales yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>