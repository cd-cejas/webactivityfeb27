<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please sign in to continue."]);
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

$conn->query("CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'Instapay',
    items_json LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (created_at)
)");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$cart = [];
if (is_array($data) && isset($data["cart"]) && is_array($data["cart"])) {
    $cart = $data["cart"];
} elseif (isset($_POST["cart"])) {
    $decoded = json_decode($_POST["cart"], true);
    if (is_array($decoded)) {
        $cart = $decoded;
    }
}

if (empty($cart)) {
    $conn->close();
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Cart is empty."]);
    exit();
}

$total = 0.0;
$cleanItems = [];
foreach ($cart as $item) {
    $name = trim((string)($item["name"] ?? ""));
    $price = (float)($item["price"] ?? 0);
    $quantity = (int)($item["quantity"] ?? 0);

    if ($name === "" || $price <= 0 || $quantity <= 0) {
        continue;
    }

    $subtotal = $price * $quantity;
    $total += $subtotal;
    $cleanItems[] = [
        "name" => $name,
        "price" => round($price, 2),
        "quantity" => $quantity,
        "subtotal" => round($subtotal, 2)
    ];
}

if (empty($cleanItems)) {
    $conn->close();
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid cart data."]);
    exit();
}

$stmtUser = $conn->prepare("SELECT fullname, email, username FROM users WHERE id = ? LIMIT 1");
$stmtUser->bind_param("i", $_SESSION["user_id"]);
$stmtUser->execute();
$userRes = $stmtUser->get_result();
$user = $userRes->fetch_assoc();
$stmtUser->close();

if (!$user) {
    $conn->close();
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "User account not found."]);
    exit();
}

$totalRounded = round($total, 2);
$paymentMethod = "Instapay";
$itemsJson = json_encode($cleanItems, JSON_UNESCAPED_UNICODE);

$stmtSale = $conn->prepare("INSERT INTO sales (user_id, fullname, email, username, total_amount, payment_method, items_json) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtSale->bind_param(
    "isssdss",
    $_SESSION["user_id"],
    $user["fullname"],
    $user["email"],
    $user["username"],
    $totalRounded,
    $paymentMethod,
    $itemsJson
);

if (!$stmtSale->execute()) {
    $stmtSale->close();
    $conn->close();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Unable to save payment."]);
    exit();
}

$saleId = $stmtSale->insert_id;
$stmtSale->close();
$conn->close();

echo json_encode([
    "success" => true,
    "receipt" => [
        "sale_id" => $saleId,
        "fullname" => $user["fullname"],
        "email" => $user["email"],
        "username" => $user["username"],
        "total_paid" => number_format($totalRounded, 2, ".", ""),
        "payment_method" => $paymentMethod,
        "paid_at" => date("Y-m-d H:i:s")
    ]
]);
