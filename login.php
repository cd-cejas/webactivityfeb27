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

$redirect = ($_POST["redirect"] ?? "") === "checkout" ? "checkout" : "";

function redirect_home($auth, $messageKey = "", $message = "", $redirect = "") {
    $query = "auth=" . urlencode($auth);
    if (!empty($messageKey) && !empty($message)) {
        $query .= "&" . $messageKey . "=" . urlencode($message);
    }
    if (!empty($redirect)) {
        $query .= "&redirect=" . urlencode($redirect);
    }
    header("Location: homepage.html?" . $query);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect_home("signin");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username)) {
        $conn->close();
        redirect_home("signin", "error", "Please enter your username.", $redirect);
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
                $stmt->close();
                $conn->close();
                redirect_home("signin", "error", "Please enter your password.", $redirect);
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
                    if ($redirect === "checkout") {
                        header("Location: homepage.html?success=" . urlencode("Signed in successfully. You can now proceed to checkout.") . "&redirect=checkout");
                        exit();
                    }
                    header("Location: homepage.html");
                    exit();
                }
            } else {
                $stmt->close();
                $conn->close();
                redirect_home("signin", "error", "Invalid username or password.", $redirect);
            }
        } else {
            $stmt->close();
            $conn->close();
            redirect_home("signin", "error", "Invalid username or password.", $redirect);
        }
        $stmt->close();
    }
}

$conn->close();
redirect_home("signin");
