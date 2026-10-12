<?php

$host = "localhost";
$user = "root";
$password = "";
$dbname = "Macailan";
$dsn = "mysql:host={$host};dbname={$dbname}";

$pdo = new PDO($dsn, $user, $password);
$pdo -> exec("SET time_zone = '+8:00';");

?>
