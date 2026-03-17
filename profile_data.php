<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not authenticated."]);
    exit();
}

$host = "localhost";
$dbname = "web_system";
$db_username = "root";
$db_password = "";

$conn = new mysqli($host, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit();
}

$stmt = $conn->prepare("SELECT fullname, email, username, role FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $conn->close();
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "User not found."]);
    exit();
}

$order_history = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'sales'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $stmtOrders = $conn->prepare("SELECT id, total_amount, payment_method, created_at FROM sales WHERE user_id = ? ORDER BY id DESC LIMIT 20");
    $stmtOrders->bind_param("i", $_SESSION["user_id"]);
    $stmtOrders->execute();
    $ordersResult = $stmtOrders->get_result();

    while ($order = $ordersResult->fetch_assoc()) {
        $order_history[] = [
            "id" => (int)$order["id"],
            "total_amount" => (float)$order["total_amount"],
            "payment_method" => $order["payment_method"],
            "created_at" => $order["created_at"]
        ];
    }

    $stmtOrders->close();
}

$conn->close();

echo json_encode([
    "success" => true,
    "profile" => [
        "fullname" => $user["fullname"],
        "email" => $user["email"],
        "username" => $user["username"],
        "role" => ucfirst($user["role"])
    ],
    "order_history" => $order_history
]);
