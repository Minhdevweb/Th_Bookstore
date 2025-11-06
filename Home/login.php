<?php
session_start();
include "config.php";

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($user_id, $hash, $role);

if ($stmt->fetch() && password_verify($password, $hash)) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    echo json_encode(['status' => 'success', 'role' => $role]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
}

$stmt->close();
$conn->close();
?>
