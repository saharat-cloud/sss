<?php
$pdo = new PDO('sqlite:database.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN sku TEXT UNIQUE");
    echo "Added sku.\n";
}
catch (Exception $e) {
    echo $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN image_url TEXT");
    echo "Added image_url.\n";
}
catch (Exception $e) {
    echo $e->getMessage() . "\n";
}

echo "Done\n";
