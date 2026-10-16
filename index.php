<?php
// Simple menu that links to the file of each activity item
$items = [
    'Item 5'  => ['Queries/Item5.php',  'fetchAll() with print_r()'],
    'Item 6'  => ['Queries/Item6.php',  'fetch() with print_r()'],
    'Item 7'  => ['Queries/Item7.php',  'INSERT records'],
    'Item 8'  => ['Queries/Item8.php',  'DELETE records'],
    'Item 9'  => ['Queries/Item9.php',  'UPDATE records'],
    'Item 10' => ['Queries/Item10.php', 'Result set in an HTML table'],
];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PDO Methods Activity</title>
    </head>
<body>
    <h2>PDO Methods Activity - Macailan</h2>
    <ul>
        <?php foreach ($items as $label => [$file, $text]): ?>
            <li><a href="<?= $file ?>"><?= $label ?></a> - <?= $text ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
