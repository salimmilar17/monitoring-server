<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireAdmin();

$message = '';
$error = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    if ($username && $password && $email) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO t_users (username, password, email, full_name, role) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$username, $hashedPassword, $email, $full_name, $role]);
            $message = 'User created successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to create user. Username or email may already exist.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    // Don't allow deleting own account
    if ($userId !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM t_users WHERE id = ?");
        $stmt->execute([$userId]);
        $message = 'User deleted successfully!';
    } else {
        $error = 'You cannot delete your own account.';
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM t_users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM t_alerts WHERE acknowledged = FALSE");
$activeAlertCount = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Fire Detection System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Include sidebar here (same as dashboard) -->
       <nav class="sidebar">
            <div class="sidebar-header">
               <a href="home.php"><h3>Server Monitor</h3></a> 
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
                        <i class="icon">📊</i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="sensor-data.php">
                        <i class="icon">📈</i> Sensor Data
                    </a>
                </li>
                <li>
                    <a href="alerts.php">
                        <i class="icon">🚨</i> Alerts
                        <?php if ($activeAlertCount > 0): ?>
                            <span class="badge"><?php echo $activeAlertCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="active">
                    <a href="users.php">
                        <i class="icon">👥</i> Users
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="logout.php">
                        <i class="icon">🚪</i> Logout
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="main-content">
            <header class="header">
                <h1>User Management</h1>
                <button onclick="openModal('addUserModal')" class="btn btn-primary">Add New User</button>
            </header>
            
            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                <td>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn btn-danger btn-sm">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New User</h2>
                <span class="modal-close" onclick="closeModal('addUserModal')">&times;</span>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Create User</button>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>