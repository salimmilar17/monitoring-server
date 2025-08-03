<?php
require_once 'config/database.php';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    if ($username && $password && $email) {
        try {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $pdo->prepare("
                INSERT INTO t_users (username, password, email, full_name, role) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$username, $hashedPassword, $email, $full_name, $role]);
            
            echo "<div style='color: green;'>User created successfully!</div>";
            echo "<p>Username: $username</p>";
            echo "<p>Password: $password</p>";
            echo "<p>You can now <a href='login.php'>login here</a></p>";
            
        } catch (PDOException $e) {
            echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create User - Fire Detection System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h2>Create New User</h2>
    
    <form method="POST">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="full_name" required>
        </div>
        
        <div class="form-group">
            <label>Role:</label>
            <select name="role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        
        <button type="submit">Create User</button>
    </form>
    
    <hr>
    <p><a href="login.php">Back to Login</a></p>
</body>
</html>