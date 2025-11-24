<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$query = "
    SELECT 
        i.item_id,
        i.item_name,
        u.unit_name
    FROM tbl_items i
    LEFT JOIN tbl_units u ON i.unit_id = u.unit_id
    WHERE i.item_status = 1
    ORDER BY i.item_name
";

$result = $conn->query($query);
$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

echo json_encode($items);
