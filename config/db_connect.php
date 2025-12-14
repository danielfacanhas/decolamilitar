<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "decola_militar"; // coloque aqui o nome do seu banco

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
  die("Erro na conexão: " . mysqli_error($conn));
}
?>