<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
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

// Check if user has a password set
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// If user has a password, redirect to dashboard
if (!empty($user) && $user["password"] !== null && $user["password"] !== "") {
    if ($_SESSION["role"] === "admin") {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: homepage.html");
    }
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Hash the new password and save it
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed_password, $_SESSION["user_id"]);
        $update->execute();
        $update->close();

        // Set cookie for homepage.html to read username
        setcookie("web_system_user", $_SESSION["username"], time() + 3600, "/");

        $success = "Password changed successfully! Redirecting...";
        
        // Redirect after 2 seconds
        echo "
        <!DOCTYPE html>
        <html lang=\"en\">
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>Password Changed — Web System</title>
            <link rel=\"stylesheet\" href=\"style.css\">
        </head>
        <body>
            <div class=\"container\">
                <div class=\"card\">
                    <div class=\"alert alert-success\">
                        Password changed successfully!
                    </div>
                    <p style=\"margin-bottom: 20px; color: #666; text-align: center;\">Redirecting you to your dashboard...</p>
                </div>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '" . ($_SESSION["role"] === "admin" ? "admin_dashboard.php" : "homepage.html") . "';
                }, 2000);
            </script>
        </body>
        </html>
        ";
        $conn->close();
        exit();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — Crossover Apparel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="overlay-backdrop"></div>
    <div class="container">
        <div class="card">
            <div class="brand">
                <img src="images/crossoverlogo.png" alt="Crossover" class="brand-icon">
                <h1>Change Password</h1>
                <p>Your password needs to be set before you can continue</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" disabled readonly>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Set Password</button>
            </form>
        </div>
    </div>
</body>
</html>
