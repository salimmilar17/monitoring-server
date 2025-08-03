<?php
// File: generate-hash.php
// Gunakan file ini untuk generate password hash

$password = 'safety1771'; // Password yang ingin di-hash

// Generate hash
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
echo "\n";
echo "SQL Query:\n";
echo "UPDATE t_users SET password = '" . $hash . "' WHERE username = 'milardi';\n";
?>