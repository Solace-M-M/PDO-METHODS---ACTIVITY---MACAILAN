<?php
// Load the shared PDO connection ($pdo) from the core folder
require_once __DIR__ . '/../core/dbConfig.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ONE grocery item (Item 6: fetch)</title>
    </head>
<body>
    <?php
    // Prepare the SQL statement (prepared statements protect against SQL injection)
    $stmt = $pdo->prepare("SELECT * FROM grocery_items WHERE item_id = 110");

    // execute() runs the query and returns true when it succeeds
    if ($stmt->execute()) {
        echo "<pre>";                      // <pre> keeps print_r's line breaks and indentation
        // fetch() returns only the NEXT single row (one array), not the whole list
        print_r($stmt->fetch());
        echo "</pre>";                     // close the <pre> tag
    }
    ?>
</body>
</html>
