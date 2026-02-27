<?php
session_start();

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {
                // Set session variables
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["fullname"] = $user["fullname"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                // Set cookie for menu.html to read username
                setcookie("web_system_user", $user["username"], time() + 3600, "/");

                // Redirect based on role
                if ($user["role"] === "admin") {
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    header("Location: menu.html");
                    exit();
                }
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Web System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="brand">
                <div class="brand-icon">W</div>
                <h1>Sign In Failed</h1>
                <p>We couldn't verify your credentials</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <a href="login.html" class="btn btn-primary">Try Again</a>

            <div class="form-footer">
                Don't have an account? <a href="signup.html">Create one</a>
            </div>
        </div>
    </div>
</body>
</html>
