<?php
require_once 'config/database.php';

echo "<h1>🔐 Database Security Upgrade</h1>";
echo "<p>Hashing existing plain text passwords...</p>";

$result = $conn->query("SELECT user_id, password FROM users");

if ($result) {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $userId = $row['user_id'];
        $plainPassword = $row['password'];

        // Check if already hashed (typical BCRYPT hash starts with $2y$)
        if (strpos($plainPassword, '$2y$') !== 0) {
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            if ($stmt->execute()) {
                $count++;
                echo "✅ User ID $userId password hashed.<br>";
            } else {
                echo "❌ Failed to update User ID $userId.<br>";
            }
        } else {
            echo "ℹ️ User ID $userId already has a hashed password.<br>";
        }
    }
    echo "<h2>🎉 Success! $count passwords have been hashed.</h2>";
    echo "<p>Please delete this file (<code>secure_passwords.php</code>) immediately for security.</p>";
} else {
    echo "❌ Error fetching users: " . $conn->error;
}
?>

