<?php
include "config.php";

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT password FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($hash);

if ($stmt->fetch() && password_verify($password, $hash)) {
  echo "success";
} else {
  echo "invalid";
}

$stmt->close();
$conn->close();
?>
