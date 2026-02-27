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

$error = "";
$user = null;

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: admin_dashboard.php");
    exit();
}

$id = intval($_GET["id"]);

$stmt = $conn->prepare("SELECT id, fullname, email, username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User — Web System</title>
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

        <div class="edit-container">
            <div class="edit-card">
                <h2>Edit User #<?php echo $user["id"]; ?></h2>

                <form method="POST" action="update_user.php">
                    <input type="hidden" name="id" value="<?php echo $user["id"]; ?>">

                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user["fullname"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user["email"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user["username"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="user" <?php echo ($user["role"] === "user") ? "selected" : ""; ?>>User</option>
                            <option value="admin" <?php echo ($user["role"] === "admin") ? "selected" : ""; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="btn-row">
                        <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
