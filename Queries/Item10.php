<?php
// Load the shared PDO connection ($pdo) from the core folder
require_once __DIR__ . '/../core/dbConfig.php';

// ---- 1) Read and clean the optional URL filters (?q=milram&status=Expiring+soon&limit=20) ----
$search = trim($_GET['q'] ?? '');                         // text to look for in item_name
$status = $_GET['status'] ?? '';                          // stock status filter
$limit  = max(1, min(200, (int) ($_GET['limit'] ?? 20))); // between 1 and 200 rows

// Whitelist: only these status values are accepted
$allowedStatus = ['Expired', 'Expiring soon', 'Fresh', 'No expiry'];
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}

// ---- 2) Main query: computed columns + filters + sorting ----
// The inner query calculates total_value, days_left and a readable status with CASE;
// the outer query filters on those calculated columns.
$stmt = $pdo->prepare(
    "SELECT * FROM (
         SELECT item_id,
                item_name,
                price,
                quantity_in_stock,
                price * quantity_in_stock AS total_value,
                expiration_date,
                DATEDIFF(expiration_date, CURDATE()) AS days_left,
                CASE
                    WHEN expiration_date IS NULL                              THEN 'No expiry'
                    WHEN expiration_date < CURDATE()                          THEN 'Expired'
                    WHEN expiration_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Expiring soon'
                    ELSE 'Fresh'
                END AS status
         FROM grocery_items
     ) AS items
     WHERE item_name LIKE :search
       AND (:status_empty = '' OR status = :status)
     ORDER BY (expiration_date IS NULL), expiration_date ASC, item_name ASC
     LIMIT :limit"
);
$stmt->bindValue(':search',       '%' . $search . '%');
$stmt->bindValue(':status_empty', $status);
$stmt->bindValue(':status',       $status);
$stmt->bindValue(':limit',        $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- 3) Summary query: totals for the whole table ----
$summary = $pdo->query(
    "SELECT COUNT(*)                          AS total_items,
            SUM(quantity_in_stock)            AS total_units,
            SUM(price * quantity_in_stock)    AS inventory_value,
            AVG(price)                        AS average_price,
            SUM(expiration_date < CURDATE())  AS expired_items
     FROM grocery_items"
)->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Item 10: Result set in an HTML table</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; }
            th { background: #222; color: #fff; }
            .num { text-align: right; }
            /* row colors depend on the status calculated in SQL */
            .Expired        { background: #f8d7da; }
            .Expiring-soon  { background: #fff3cd; }
            .Fresh          { background: #d4edda; }
            form { margin-bottom: 15px; }
        </style>
    </head>
<body>
    <h2>Grocery Items (Germany)</h2>

    <!-- Filter form: sends q, status and limit to this same page with GET -->
    <form method="get">
        <input type="text" name="q" placeholder="Search item or brand"
               value="<?= htmlspecialchars($search) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach ($allowedStatus as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>" <?= $s === $status ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="limit" min="1" max="200" value="<?= $limit ?>">
        <button type="submit">Filter</button>
    </form>

    <!-- Summary of the whole table -->
    <p>
        <strong>Items:</strong> <?= (int) $summary['total_items'] ?> |
        <strong>Units in stock:</strong> <?= number_format((int) $summary['total_units']) ?> |
        <strong>Inventory value:</strong> €<?= number_format((float) $summary['inventory_value'], 2) ?> |
        <strong>Average price:</strong> €<?= number_format((float) $summary['average_price'], 2) ?> |
        <strong>Expired items:</strong> <?= (int) $summary['expired_items'] ?>
    </p>

    <p>Showing <?= count($rows) ?> row(s)</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Item name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Total value</th>
                <th>Expiration date</th>
                <th>Days left</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8">No items found.</td></tr>
            <?php endif; ?>

            <!-- One <tr> per row of the result set -->
            <?php foreach ($rows as $row): ?>
                <tr class="<?= str_replace(' ', '-', $row['status']) ?>">
                    <td><?= (int) $row['item_id'] ?></td>
                    <!-- htmlspecialchars() prevents HTML injection from database text -->
                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                    <td class="num">€<?= number_format((float) $row['price'], 2) ?></td>
                    <td class="num"><?= (int) $row['quantity_in_stock'] ?></td>
                    <td class="num">€<?= number_format((float) $row['total_value'], 2) ?></td>
                    <td><?= $row['expiration_date'] ?? '—' ?></td>
                    <td class="num"><?= $row['days_left'] ?? '—' ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
