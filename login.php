<?php
// login.php
// Processes the login form submitted from frontend/index.php.
// Checks the admission number and password against the database,
// then starts a session for the student or administrator.

session_start();
require "../database/db.php";

// Only accept POST requests (form submissions)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../frontend/index.php");
    exit();
}

$admission_number = trim($_POST["admission_number"]);
$password = $_POST["password"];

// Basic server-side validation
if ($admission_number === "" || $password === "") {
    header("Location: ../frontend/index.php?error=" . urlencode("Please enter both your admission number and password."));
    exit();
}

// Look up the user using a prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT id, admission_number, password, full_name, role FROM users WHERE admission_number = ?");
$stmt->bind_param("s", $admission_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verify the submitted password against the stored hash
    if (password_verify($password, $user["password"])) {
        // Correct login: store user details in the session
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["admission_number"] = $user["admission_number"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "admin") {
            header("Location: ../frontend/admin_dashboard.php");
        } else {
            header("Location: ../frontend/dashboard.php");
        }
        exit();
    }
}

// If we reach this point, login failed
header("Location: ../frontend/index.php?error=" . urlencode("Incorrect admission number or password."));
exit();
?>
