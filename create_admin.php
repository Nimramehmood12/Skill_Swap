<?php
include('config/db.php');

$email = 'mehmoodnimra773@gmail.com';
$password = 'nimra';
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Update existing account to admin
$query = "UPDATE users SET password='$hashed_password', role='admin' WHERE email='$email'";

if(mysqli_query($conn, $query)) {
    echo "✓ Account updated to admin successfully!<br><br>";
    echo "<strong>Login Details:</strong><br>";
    echo "Email: $email<br>";
    echo "Password: $password<br>";
    echo "Role: admin<br><br>";
    echo "You can now:<br>";
    echo "1. Login with these credentials<br>";
    echo "2. Click 'Verify' in top navigation<br>";
    echo "3. Approve pending teaching skills<br>";
} else {
    echo "Error updating account: " . mysqli_error($conn);
}
?>
