<?php
require 'auth_check.php';
require '../config/db.php';

$message = '';

// Handle Role Update, Status Toggle, or Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetUserId = (int)$_POST['user_id'];

    // Prevent admin from modifying or deleting their own active session account
    if ($targetUserId === (int)$_SESSION['user_id']) {
        $message = "You cannot modify or delete your own account while logged in.";
    } else {
        if (isset($_POST['update_role'])) {
            $newRole = $_POST['role'] === 'admin' ? 'admin' : 'customer';
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $targetUserId]);
            $message = "User role updated successfully.";
        }

        if (isset($_POST['toggle_status'])) {
            $newStatus = $_POST['status'] === 'active' ? 'suspended' : 'active';
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $targetUserId]);
            $message = "User status updated to " . htmlspecialchars($newStatus) . ".";
        }

        if (isset($_POST['delete_user'])) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $message = "User account deleted.";
        }
    }
}

// Fetch all users
$users = $pdo->query("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .alert { padding: 10px; background: #e7f3fe; border-left: 4px solid #2196F3; margin-bottom: 15px; }
        form.inline { display: inline; }
    </style>
</head>
<body>

<h2>User Management</h2>
<p><a href="dashboard.php">&larr; Back to Dashboard</a></p>

<?php if ($message): ?>
    <div class="alert"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td>
                    <form method="POST" class="inline">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <select name="role" onchange="this.form.submit()" <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                            <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <input type="hidden" name="update_role" value="1">
                    </form>
                </td>
                <td><?= ucfirst(htmlspecialchars($user['status'])) ?></td>
                <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                <td>
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <!-- Suspend / Activate -->
                        <form method="POST" class="inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="status" value="<?= $user['status'] ?>">
                            <button type="submit" name="toggle_status">
                                <?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                            </button>
                        </form>

                        <!-- Delete -->
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this user permanently?');">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete_user" style="color: red;">Delete</button>
                        </form>
                    <?php else: ?>
                        <em>Current Session</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>