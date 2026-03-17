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

$redirect = ($_POST["redirect"] ?? "") === "checkout" ? "checkout" : "";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect_home("signup");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validate empty fields
    if (empty($fullname) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $conn->close();
        redirect_home("signup", "error", "All fields are required.", $redirect);
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn->close();
        redirect_home("signup", "error", "Please enter a valid email address.", $redirect);
    }
    // Validate username length
    elseif (strlen($username) < 3) {
        $conn->close();
        redirect_home("signup", "error", "Username must be at least 3 characters.", $redirect);
    }
    // Validate password length
    elseif (strlen($password) < 6) {
        $conn->close();
        redirect_home("signup", "error", "Password must be at least 6 characters.", $redirect);
    }
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $conn->close();
        redirect_home("signup", "error", "Passwords do not match.", $redirect);
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            $conn->close();
            redirect_home("signup", "error", "Username already taken. Please choose another.", $redirect);
        } else {
            // Check if email already exists
            $stmt2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $stmt2->store_result();

            if ($stmt2->num_rows > 0) {
                $stmt2->close();
                $stmt->close();
                $conn->close();
                redirect_home("signup", "error", "Email already registered.", $redirect);
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = "user";

                $insert = $conn->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
                $insert->bind_param("sssss", $fullname, $email, $username, $hashed_password, $role);

                if ($insert->execute()) {
                    $insert->close();
                    $stmt2->close();
                    $stmt->close();
                    $conn->close();
                    redirect_home("signin", "success", "Registration successful! You can now sign in.", $redirect);
                } else {
                    $insert->close();
                    $stmt2->close();
                    $stmt->close();
                    $conn->close();
                    redirect_home("signup", "error", "Something went wrong. Please try again.", $redirect);
                }
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}

$conn->close();
redirect_home("signup");
