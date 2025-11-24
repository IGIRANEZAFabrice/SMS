<?php
require_once __DIR__ . '/config/db.php';

try {
    // Add the new_price column if it doesn't exist
    $sql = "ALTER TABLE `tbl_progress` 
            ADD COLUMN IF NOT EXISTS `new_price` DOUBLE DEFAULT NULL AFTER `end_qty`";
    
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added new_price column to tbl_progress table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
    
    // Verify the column was added
    $result = $conn->query("SHOW COLUMNS FROM `tbl_progress` LIKE 'new_price'");
    if ($result->num_rows > 0) {
        echo "Verified: new_price column exists in tbl_progress table.\n";
    } else {
        echo "Warning: Failed to verify new_price column. Please check the table structure manually.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    $conn->close();
}
?>
