<?php
// Load the shared PDO connection ($pdo) from the core folder
require_once __DIR__ . '/../core/dbConfig.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Item 9: UPDATE records</title>
    </head>
<body>
    <?php
    // Settings for the update (change these to try different results)
    $days      = 7;      // items expiring within this many days get a discount
    $discount  = 0.20;   // 20% off
    $threshold = 50;     // restock items with less stock than this
    $restock   = 100;    // number of units to add

    // Safety switch: open Item9.php?apply=1 to SAVE the changes.
    // Without it the page is a dry run (changes are shown, then rolled back),
    // so refreshing the page can never discount the same items twice.
    $apply = isset($_GET['apply']);

    try {
        $pdo->beginTransaction();

        // 1) BEFORE snapshot of the rows this update is going to touch
        $snapshot = $pdo->prepare(
            "SELECT item_id, item_name, price, quantity_in_stock, expiration_date
             FROM grocery_items
             WHERE (expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY))
                OR quantity_in_stock < :threshold
             ORDER BY item_id"
        );
        $snapshot->bindValue(':days',      $days,      PDO::PARAM_INT);
        $snapshot->bindValue(':threshold', $threshold, PDO::PARAM_INT);
        $snapshot->execute();
        $before = $snapshot->fetchAll(PDO::FETCH_ASSOC);
        $ids    = array_column($before, 'item_id');
        $ids[]  = 94;   // also show the record renamed in step 3
        $ids    = array_values(array_unique($ids));

        // 2) ONE UPDATE with CASE: discount the price of items that expire soon
        //    AND add stock to items that are running low
        $update = $pdo->prepare(
            "UPDATE grocery_items
             SET price = CASE
                     WHEN expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                     THEN ROUND(price * (1 - :discount), 2)
                     ELSE price END,
                 quantity_in_stock = CASE
                     WHEN quantity_in_stock < :threshold
                     THEN quantity_in_stock + :restock
                     ELSE quantity_in_stock END
             WHERE (expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days2 DAY))
                OR quantity_in_stock < :threshold2"
        );
        $update->bindValue(':days',       $days,      PDO::PARAM_INT);
        $update->bindValue(':days2',      $days,      PDO::PARAM_INT);
        $update->bindValue(':discount',   $discount);
        $update->bindValue(':threshold',  $threshold, PDO::PARAM_INT);
        $update->bindValue(':threshold2', $threshold, PDO::PARAM_INT);
        $update->bindValue(':restock',    $restock,   PDO::PARAM_INT);
        $update->execute();
        $changedRows = $update->rowCount();   // rows whose values really changed

        // 3) Update ONE specific record (fix a product name) using named placeholders
        $rename = $pdo->prepare(
            "UPDATE grocery_items SET item_name = :new_name WHERE item_id = :item_id"
        );
        $rename->execute([':new_name' => 'Haribo Goldbären Klassiker 200 g', ':item_id' => 94]);

        // 4) AFTER snapshot of the same rows (read inside the transaction), to compare with BEFORE
        $after = [];
        if ($ids) {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT item_id, item_name, price, quantity_in_stock, expiration_date
                 FROM grocery_items WHERE item_id IN ($marks) ORDER BY item_id"
            );
            $stmt->execute($ids);
            $after = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 5) Save for real only when ?apply=1 was used, otherwise undo everything
        if ($apply) {
            $pdo->commit();     // save both updates together
        } else {
            $pdo->rollBack();   // dry run: nothing is saved
        }

        echo "<pre>";
        echo $apply ? "APPLIED: changes were saved.\n\n"
                    : "DRY RUN: nothing was saved. Open Item9.php?apply=1 to save.\n\n";
        echo "Rows matched by the discount/restock rule: " . count($before) . "\n";
        echo "Rows changed by the discount/restock UPDATE: {$changedRows}\n";
        echo "Rows changed by the rename UPDATE: " . $rename->rowCount() . "\n\n";
        echo "BEFORE:\n"; print_r($before);
        echo "AFTER:\n";  print_r($after);
        echo "</pre>";

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();   // undo everything if anything fails
        }
        echo "<pre>Update failed and was rolled back: " . htmlspecialchars($e->getMessage()) . "</pre>";
    }
    ?>
</body>
</html>
