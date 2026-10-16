<?php
// Load the shared PDO connection ($pdo) from the core folder
require_once __DIR__ . '/../core/dbConfig.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Item 7: INSERT records</title>
    </head>
<body>
    <?php
    // New German grocery items to insert (item_id is generated below)
    $newItems = [
        ['item_name' => 'Dr. Oetker Ristorante Pizza Margherita 340 g', 'price' => 3.29, 'quantity_in_stock' => 70, 'expiration_date' => '2027-06-30'],
        ['item_name' => 'Haribo Color-Rado 200 g',                      'price' => 1.49, 'quantity_in_stock' => 150, 'expiration_date' => '2027-08-31'],
        ['item_name' => 'Bionade Holunder 0,33 L',                      'price' => 1.39, 'quantity_in_stock' => 90, 'expiration_date' => '2027-03-31'],
        ['item_name' => 'Pfanni Kartoffelknödel halb & halb 12 Stück',  'price' => 2.19, 'quantity_in_stock' => 65, 'expiration_date' => '2027-09-30'],
        ['item_name' => '',                                             'price' => -5,   'quantity_in_stock' => 10, 'expiration_date' => '2027-13-45'], // invalid on purpose: shows validation
    ];

    $insertedIds = [];   // item_ids that were really inserted
    $skipped     = [];   // rows rejected, with the reason

    try {
        // Transaction: either ALL valid rows are saved or NONE (rollback on error)
        $pdo->beginTransaction();

        // 1) Reusable prepared statements with named placeholders
        $exists = $pdo->prepare("SELECT COUNT(*) FROM grocery_items WHERE item_name = :item_name");
        // item_id is not AUTO_INCREMENT, so the next id is MAX + 1; FOR UPDATE locks the table
        // read so two people running this at the same time cannot get the same id
        $nextId = $pdo->prepare("SELECT COALESCE(MAX(item_id), 0) + 1 FROM grocery_items FOR UPDATE");
        $insert = $pdo->prepare(
            "INSERT INTO grocery_items (item_id, item_name, price, quantity_in_stock, expiration_date)
             VALUES (:item_id, :item_name, :price, :quantity_in_stock, :expiration_date)"
        );

        foreach ($newItems as $item) {
            // 2) Validate the data before touching the database
            $date = DateTime::createFromFormat('Y-m-d', $item['expiration_date']);
            $validDate = $date && $date->format('Y-m-d') === $item['expiration_date'];

            if ($item['item_name'] === '' || strlen($item['item_name']) > 100) {
                $skipped[] = ['row' => $item, 'reason' => 'item_name must be 1-100 characters'];
                continue;
            }
            if (!is_numeric($item['price']) || $item['price'] <= 0) {
                $skipped[] = ['row' => $item, 'reason' => 'price must be greater than 0'];
                continue;
            }
            if (!$validDate) {
                $skipped[] = ['row' => $item, 'reason' => 'expiration_date must be a real date (YYYY-MM-DD)'];
                continue;
            }

            // 3) Skip duplicates so reloading the page does not insert the same item twice
            $exists->execute([':item_name' => $item['item_name']]);
            if ($exists->fetchColumn() > 0) {
                $skipped[] = ['row' => $item, 'reason' => 'already exists'];
                continue;
            }

            // 4) Get the next id and insert the row
            $nextId->execute();
            $id = (int) $nextId->fetchColumn();

            $insert->execute([
                ':item_id'           => $id,
                ':item_name'         => $item['item_name'],
                ':price'             => $item['price'],
                ':quantity_in_stock' => $item['quantity_in_stock'],
                ':expiration_date'   => $item['expiration_date'],
            ]);
            $insertedIds[] = $id;
        }

        $pdo->commit();   // save everything

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();   // undo the partial work on any SQL error
        }
        $insertedIds = [];
        echo "<pre>Insert failed and was rolled back: " . htmlspecialchars($e->getMessage()) . "</pre>";
    }

    echo "<pre>";
    echo "Inserted: " . count($insertedIds) . " row(s)\n";
    echo "Skipped:  " . count($skipped) . " row(s)\n\n";

    // 5) Read the new rows back to prove they are in the database
    if ($insertedIds) {
        // build "?, ?, ?" with one placeholder per inserted id
        $marks = implode(',', array_fill(0, count($insertedIds), '?'));
        $stmt = $pdo->prepare("SELECT * FROM grocery_items WHERE item_id IN ($marks) ORDER BY item_id");
        $stmt->execute($insertedIds);
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    print_r($skipped);
    echo "</pre>";
    ?>
</body>
</html>
