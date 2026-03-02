<?php
session_start();

// Check if user is logged in and password was reset by admin
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["password_reset_by_admin"])) {
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
$success = false;

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

        // Clear the reset flag
        unset($_SESSION["password_reset_by_admin"]);

        $success = true;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password — Web System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <?php if ($success): ?>
                <div class="brand">
                    <div class="brand-icon">W</div>
                    <h1>Password Changed!</h1>
                    <p>Your new password has been saved successfully</p>
                </div>

                <div class="alert alert-success">
                    Your password has been updated. You can now sign in with your new password.
                </div>

                <a href="login.html" class="btn btn-primary" style="width: 100%;">Sign In</a>
            <?php else: ?>
                <div class="brand">
                    <div class="brand-icon">W</div>
                    <h1>Create New Password</h1>
                    <p>Your password was reset by an admin. Please create a new password for your account.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Change Password</button>
                </form>
            <?php endif; ?>

            <div class="form-footer">
                <a href="login.html">Back to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
