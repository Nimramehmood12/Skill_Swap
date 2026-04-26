<?php
include('config/db.php');

echo "============================================<br>";
echo "SKILLSWAP ADMIN & MODERATOR ACCOUNT SETUP<br>";
echo "============================================<br><br>";

// 1. Ensure skill categories exist
echo "Step 1: Creating skill categories...<br>";
$categories = [
    ['Languages', 'Learn and teach languages'],
    ['Music', 'Musical instruments and vocal training'],
    ['Arts', 'Painting, drawing, design, and creative skills'],
    ['Sports', 'Physical activities and fitness'],
    ['Technology', 'Programming, web design, IT skills'],
    ['Business', 'Entrepreneurship, marketing, finance'],
    ['Cooking', 'Culinary skills and recipes'],
    ['Photography', 'Photography and videography'],
];

foreach ($categories as $cat) {
    $check = mysqli_query($conn, "SELECT category_id FROM skill_categories WHERE category_name='{$cat[0]}'");
    if (!$check || mysqli_num_rows($check) == 0) {
        $insert = mysqli_query($conn, "INSERT INTO skill_categories (category_name, description) VALUES ('{$cat[0]}', '{$cat[1]}')");
        if ($insert) {
            echo "✓ Created category: {$cat[0]}<br>";
        }
    } else {
        echo "✓ Category already exists: {$cat[0]}<br>";
    }
}

echo "<br>Step 2: Creating admin account...<br>";

// 2. Create Admin Account
$admin_email = 'mehmoodnimra773@gmail.com';
$admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
$admin_name = 'Admin User';

// Check if admin exists
$check_admin = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$admin_email'");
if (mysqli_num_rows($check_admin) > 0) {
    echo "✗ Admin account already exists!<br>";
    // Update to ensure role is admin
    mysqli_query($conn, "UPDATE users SET role='admin' WHERE email='$admin_email'");
    echo "✓ Verified admin role<br>";
} else {
    $insert_admin = mysqli_query(
        $conn,
        "INSERT INTO users (name, email, password, role, points) VALUES ('$admin_name', '$admin_email', '$admin_pass', 'admin', 100)"
    );
    if ($insert_admin) {
        echo "✓ Admin account created!<br>";
        echo "  Email: $admin_email<br>";
        echo "  Password: admin123<br>";
    } else {
        echo "✗ Error creating admin: " . mysqli_error($conn) . "<br>";
    }
}

echo "<br>Step 3: Creating moderator account...<br>";

// 3. Create Moderator Account
$mod_email = 'iqbal.shorouq@gmail.com';
$mod_pass = password_hash('shoruq', PASSWORD_BCRYPT);
$mod_name = 'Moderator';

// Check if moderator exists
$check_mod = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$mod_email'");
if (mysqli_num_rows($check_mod) > 0) {
    echo "✗ Moderator account already exists!<br>";
    // Update to ensure role is moderator
    mysqli_query($conn, "UPDATE users SET role='moderator' WHERE email='$mod_email'");
    echo "✓ Verified moderator role<br>";
} else {
    $insert_mod = mysqli_query(
        $conn,
        "INSERT INTO users (name, email, password, role, points) VALUES ('$mod_name', '$mod_email', '$mod_pass', 'moderator', 50)"
    );
    if ($insert_mod) {
        echo "✓ Moderator account created!<br>";
        echo "  Email: $mod_email<br>";
        echo "  Password: shoruq<br>";
    } else {
        echo "✗ Error creating moderator: " . mysqli_error($conn) . "<br>";
    }
}

echo "<br>============================================<br>";
echo "✓ Setup complete!<br>";
echo "============================================<br>";
echo "<br>";
echo "<strong>Admin Login:</strong><br>";
echo "Email: mehmoodnimra773@gmail.com<br>";
echo "Password: admin123<br>";
echo "<br>";
echo "<strong>Moderator Login:</strong><br>";
echo "Email: iqbal.shorouq@gmail.com<br>";
echo "Password: shoruq<br>";
echo "<br>";
echo "<a href='index.php' class='btn btn-primary'>Go to Home</a> | ";
echo "<a href='auth/login.php' class='btn btn-success'>Go to Login</a><br>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        background-color: #f5f5f5;
    }

    a {
        padding: 10px 15px;
        margin: 5px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn {
        text-decoration: none;
        padding: 10px 15px;
        border-radius: 5px;
        color: white;
    }

    .btn-primary {
        background-color: #007bff;
    }

    .btn-success {
        background-color: #28a745;
    }

    .btn:hover {
        opacity: 0.8;
    }
</style>