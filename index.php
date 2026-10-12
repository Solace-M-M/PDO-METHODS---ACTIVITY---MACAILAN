<?php require_once 'core/dbConfig.php'; ?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, intial-scale=1.0">
        <title>Document</title>
    </head>
<body>
    <?php 
    $stmt = $pdo->prepare("SELECT * FROM grocery_items
    WHERE item_id = 53");

if ($stmt->execute()){
    echo "<pre>";
    print_r($stmt->fetchAll());
    echo "<pre>";
}
?>
</body>
</html>

