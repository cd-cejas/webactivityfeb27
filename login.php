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

    if (empty($username)) {
        $error = "Please enter your username.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check if password is NULL (reset by admin)
            if ($user["password"] === null || $user["password"] === "") {
                // Store user info in session for password change
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["fullname"] = $user["fullname"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["password_reset_by_admin"] = true;

                // Redirect directly to force password change page
                header("Location: force_password_change.php");
                exit();
            } elseif (empty($password)) {
                $error = "Please enter your password.";
            } elseif (password_verify($password, $user["password"])) {
                // Set session variables
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["fullname"] = $user["fullname"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                // Set cookie for homepage.html to read username
                setcookie("web_system_user", $user["username"], time() + 3600, "/");

                // Redirect based on role
                if ($user["role"] === "admin") {
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    header("Location: homepage.html");
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
    <title>Sign In — Crossover Apparel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="overlay-backdrop"></div>
    <div class="container">
        <div class="card">
            <div class="brand">
                <img src="images/crossoverlogo.png" alt="Crossover" class="brand-icon">
                <h1>Sign In Failed</h1>
                <p>We couldn't verify your credentials</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <a href="login.html" class="btn btn-primary">Try Again</a>
            <?php endif; ?>

            <div class="form-footer">
                Don't have an account? <a href="signup.html">Create one</a>
            </div>
        </div>
    </div>
</body>
</html>
