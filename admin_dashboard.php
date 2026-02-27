<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.html");
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Web System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: block;">
    <div class="dashboard-wrapper">
        <header class="dashboard-header">
            <span class="nav-brand">Web System &mdash; Admin</span>
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
            </div>

            <?php if (isset($_GET["success"])): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    User updated successfully.
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
                                        <a href="edit_user.php?id=<?php echo $row["id"]; ?>" class="btn-edit">
                                            &#9998; Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #7B7F85;">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
