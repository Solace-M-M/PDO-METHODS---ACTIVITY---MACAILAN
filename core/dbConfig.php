<?php

// ---------------------------------------------------------------
// dbConfig.php - database connection settings (shared by all items)
// ---------------------------------------------------------------

// Connection details for the local XAMPP MySQL/MariaDB server
$host     = "localhost";   // the database server runs on this computer
$user     = "root";        // default XAMPP username
$password = "";            // default XAMPP password is empty
$dbname   = "Macailan";    // database that holds the grocery_items table

// DSN (Data Source Name): driver, host, database and character set.
// utf8mb4 makes sure German characters (ä, ö, ü, ß) are read correctly.
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

// Create the PDO connection object used by every query in this project
$pdo = new PDO($dsn, $user, $password);

// Throw exceptions on SQL errors so try/catch blocks can handle them
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Make date functions such as CURDATE() use UTC+8
$pdo->exec("SET time_zone = '+8:00';");

?>
