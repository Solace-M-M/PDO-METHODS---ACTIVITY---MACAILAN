<?php
// Load the shared PDO connection ($pdo) from the core folder
require_once __DIR__ . '/../core/dbConfig.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Item 8: DELETE records</title>
    </head>
<body>
    <?php
    // Business rule: remove products that are already past their expiration date.
    // Safety limit: never delete more than this many rows in one run.
    $maxDeletes = 10;

    try {
        $pdo->beginTransaction();

        // 1) PREVIEW: show exactly which rows the DELETE is going to remove
        $preview = $pdo->prepare(
            "SELECT item_id, item_name, expiration_date,
                    DATEDIFF(CURDATE(), expiration_date) AS days_expired
             FROM grocery_items
             WHERE expiration_date IS NOT NULL
               AND expiration_date < CURDATE()
             ORDER BY expiration_date, item_id"
        );
        $preview->execute();
        $expired = $preview->fetchAll(PDO::FETCH_ASSOC);

        echo "<pre>";
        echo "Rows about to be deleted: " . count($expired) . "\n";
        print_r($expired);

        // 2) SAFETY CHECK: if the rule matches too many rows, cancel everything
        if (count($expired) > $maxDeletes) {
            $pdo->rollBack();
            echo "Cancelled: more than {$maxDeletes} rows matched, nothing was deleted.\n";
        } else {
            // 3) DELETE the same rows (named placeholders keep it injection-safe)
            $delete = $pdo->prepare(
                "DELETE FROM grocery_items
                 WHERE expiration_date IS NOT NULL
                   AND expiration_date < :today"
            );
            $delete->execute([':today' => date('Y-m-d')]);

            // rowCount() = how many rows the DELETE removed
            echo "Deleted rows: " . $delete->rowCount() . "\n";
            $pdo->commit();   // make the deletion permanent
        }

        // 4) VERIFY: count what is left and prove the expired rows are gone
        $left = $pdo->query(
            "SELECT COUNT(*) AS total_items,
                    SUM(expiration_date < CURDATE()) AS still_expired
             FROM grocery_items"
        )->fetch(PDO::FETCH_ASSOC);
        echo "After deletion:\n";
        print_r($left);
        echo "</pre>";

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();   // undo everything if anything fails
        }
        echo "<pre>Delete failed and was rolled back: " . htmlspecialchars($e->getMessage()) . "</pre>";
    }
    ?>
</body>
</html>
