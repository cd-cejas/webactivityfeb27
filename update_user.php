<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_dashboard.php");
    exit();
}

// Database connection
$host = "localhost";
$dbname = "web_system";
$db_username = "root";
$db_password = "";

$conn = new mysqli($host, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = intval($_POST["id"] ?? 0);
$fullname = trim($_POST["fullname"] ?? "");
$email = trim($_POST["email"] ?? "");
$username = trim($_POST["username"] ?? "");
$role = trim($_POST["role"] ?? "");

// Validate
if ($id <= 0 || empty($fullname) || empty($email) || empty($username) || empty($role)) {
    header("Location: edit_user.php?id=" . $id);
    exit();
}

// Validate role value
if ($role !== "admin" && $role !== "user") {
    header("Location: edit_user.php?id=" . $id);
    exit();
}

// Check if username is taken by another user
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->bind_param("si", $username, $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    header("Location: edit_user.php?id=" . $id . "&error=username_taken");
    exit();
}
$stmt->close();

// Check if email is taken by another user
$stmt2 = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt2->bind_param("si", $email, $id);
$stmt2->execute();
$stmt2->store_result();

if ($stmt2->num_rows > 0) {
    $stmt2->close();
    $conn->close();
    header("Location: edit_user.php?id=" . $id . "&error=email_taken");
    exit();
}
$stmt2->close();

// Update user
$update = $conn->prepare("UPDATE users SET fullname = ?, email = ?, username = ?, role = ? WHERE id = ?");
$update->bind_param("ssssi", $fullname, $email, $username, $role, $id);
$update->execute();
$update->close();

$conn->close();

// Redirect back to admin dashboard with success message
header("Location: admin_dashboard.php?success=1");
exit();
?>
