<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "config.php";

$result = $conn->query("SELECT * FROM products");
$products = [];

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $products[] = $row;
  }
  echo json_encode($products);
} else {
  echo json_encode(["error" => $conn->error]);
}

$conn->close();
?>