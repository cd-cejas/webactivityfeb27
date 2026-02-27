<?php
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
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validate empty fields
    if (empty($fullname) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }
    // Validate username length
    elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    }
    // Validate password length
    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    }
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already taken. Please choose another.";
        } else {
            // Check if email already exists
            $stmt2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $stmt2->store_result();

            if ($stmt2->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = "user";

                $insert = $conn->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
                $insert->bind_param("sssss", $fullname, $email, $username, $hashed_password, $role);

                if ($insert->execute()) {
                    $success = "Registration successful! You can now sign in.";
                } else {
                    $error = "Something went wrong. Please try again.";
                }
                $insert->close();
            }
            $stmt2->close();
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
    <title>Registration — Web System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="brand">
                <div class="brand-icon">W</div>
                <?php if (!empty($success)): ?>
                    <h1>Success!</h1>
                    <p>Your account has been created</p>
                <?php else: ?>
                    <h1>Registration Failed</h1>
                    <p>Something went wrong</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <a href="login.html" class="btn btn-primary">Go to Sign In</a>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <a href="signup.html" class="btn btn-secondary">Try Again</a>
            <?php else: ?>
                <div class="alert alert-error">
                    Invalid request.
                </div>
                <a href="signup.html" class="btn btn-secondary">Go to Sign Up</a>
            <?php endif; ?>

            <div class="form-footer">
                <a href="login.html">Back to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
