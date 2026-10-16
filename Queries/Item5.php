<?php
// Load the shared PDO connection ($pdo) from the core folder
require_once __DIR__ . '/../core/dbConfig.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ALL grocery items (Item 5: fetchAll)</title>
    </head>
<body>
    <?php
    // Prepare the SQL statement (prepared statements protect against SQL injection)
    $stmt = $pdo->prepare("SELECT * FROM grocery_items");

    // execute() runs the query and returns true when it succeeds
    if ($stmt->execute()) {
        echo "<pre>";                      // <pre> keeps print_r's line breaks and indentation
        // fetchAll() returns EVERY row of the result set as an array of arrays
        print_r($stmt->fetchAll());
        echo "</pre>";                     // close the <pre> tag
    }
    ?>
</body>
</html>
