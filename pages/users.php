<?php
$pageTitle = 'Users';
require_once '../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    die('<div style="padding:40px; text-align:center;"><h3>Access Denied</h3><p>Admin access required.</p></div>');
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'cashier';
    $full_name = $_POST['full_name'] ?? '';

    if (empty($username) || empty($password)) {
        $message = 'Username and password are required.';
        $messageType = 'error';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?,?,?,?)")
                ->execute([$username, $hash, $role, $full_name]);
            $message = 'User added successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Username already exists.';
            $messageType = 'error';
        }
    }
}

if (isset($_GET['delete']) && $_SESSION['user_id'] != $_GET['delete']) {
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_GET['delete']]);
    header('Location: users.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) {
    $message = 'User deleted.';
    $messageType = 'success';
}

$users = $pdo->query("SELECT * FROM users ORDER BY id")->fetchAll();
?>
<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="table-header" style="margin-bottom:20px;">
    <h3>User Management</h3>
    <button class="btn btn-primary" onclick="document.getElementById('addForm').style.display='block'"><i class="fa-solid fa-user-plus"></i> Add User</button>
</div>

<div id="addForm" class="form-card" style="margin-bottom:30px; display:none;">
    <h3><i class="fa-solid fa-user-plus" style="margin-right:8px; color:#059669;"></i>Add New User</h3>
    <form method="POST" action="">
        <input type="hidden" name="add_user" value="1">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" placeholder="John Doe">
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control">
                <option value="cashier">Cashier</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Add User</button>
        <button type="button" class="btn btn-outline" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo htmlspecialchars($u['full_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><span class="badge badge-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                <td>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <a href="users.php?delete=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">Delete</a>
                    <?php else: ?>
                    <span class="btn btn-outline btn-sm" style="cursor:default;">You</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>