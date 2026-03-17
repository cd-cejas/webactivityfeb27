<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: homepage.html?auth=signin");
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

// Fetch all users
$result = $conn->query("SELECT id, fullname, email, username, role, date_registered FROM users ORDER BY id ASC");

// Count stats
$total_users = $result->num_rows;
$admin_count = 0;
$user_count = 0;
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    if ($row["role"] === "admin") $admin_count++;
    else $user_count++;
}

$salesSummary = $conn->query("SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_amount), 0) AS total_sales FROM sales");
$salesData = $salesSummary ? $salesSummary->fetch_assoc() : ["total_orders" => 0, "total_sales" => 0];
$total_orders = (int)($salesData["total_orders"] ?? 0);
$total_sales = (float)($salesData["total_sales"] ?? 0);

$salesRows = [];
$salesResult = $conn->query("SELECT id, fullname, email, username, total_amount, payment_method, created_at FROM sales ORDER BY id DESC");
if ($salesResult) {
    while ($sale = $salesResult->fetch_assoc()) {
        $salesRows[] = $sale;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Crossover Apparel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page">
    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <a href="admin_dashboard.php" class="nav-brand"><img src="images/crossoverlogo.png" alt="Logo"> Crossover Apparel</a>
            <div class="nav-right">
                <span class="nav-user">Signed in as <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></span>
                <a href="logout.php" class="btn-logout">Sign Out</a>
            </div>
        </header>

        <div class="dashboard-body">
            <h1 class="dashboard-title">Dashboard</h1>
            <p class="dashboard-subtitle">Manage all registered users</p>

            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Admins</div>
                    <div class="stat-value"><?php echo $admin_count; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Regular Users</div>
                    <div class="stat-value"><?php echo $user_count; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Sales</div>
                    <div class="stat-value">&#8369;<?php echo number_format($total_sales, 2); ?></div>
                </div>
            </div>

            <?php if (isset($_GET["success"])): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    User updated successfully.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["password_reset"])): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    User password has been reset. User will be prompted to change password on next login.
                </div>
            <?php endif; ?>

            <div class="table-card">
                <div class="table-card-header">
                    <h3>All Users</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Date Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows) > 0): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?php echo $row["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($row["fullname"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["email"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["username"]); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $row["role"]; ?>">
                                            <?php echo ucfirst($row["role"]); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row["date_registered"]; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_user.php?id=<?php echo $row["id"]; ?>" class="btn-edit">
                                                &#9998; Edit
                                            </a>
                                            <?php if ($row["role"] !== "admin"): ?>
                                            <a href="reset_user_password.php?id=<?php echo $row["id"]; ?>" class="btn-reset" onclick="return confirm('Are you sure you want to reset this user\'s password?');">
                                                &#128273; Reset
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: rgba(255,255,255,0.3); padding: 40px 0;">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-card sales-table-card">
                <div class="table-card-header">
                    <h3>Sales History</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Total Paid</th>
                            <th>Paid Through</th>
                            <th>Date Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($salesRows) > 0): ?>
                            <?php foreach ($salesRows as $sale): ?>
                                <tr>
                                    <td>#<?php echo $sale["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($sale["fullname"]); ?></td>
                                    <td><?php echo htmlspecialchars($sale["email"]); ?></td>
                                    <td><?php echo htmlspecialchars($sale["username"]); ?></td>
                                    <td>&#8369;<?php echo number_format((float)$sale["total_amount"], 2); ?></td>
                                    <td><?php echo htmlspecialchars($sale["payment_method"]); ?></td>
                                    <td><?php echo $sale["created_at"]; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: rgba(255,255,255,0.3); padding: 40px 0;">No sales recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
