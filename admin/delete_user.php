<?php 
include('../config/db.php');
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') die("Access Denied");

$id = (int)$_GET['id'];
if($id != $_SESSION['user_id']){ // Prevent self-deletion
    mysqli_query($conn, "DELETE FROM users WHERE user_id=$id");
}
header("Location: dashboard.php");
?>