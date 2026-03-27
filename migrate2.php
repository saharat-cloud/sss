<?php
$pdo = new PDO('sqlite:database.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN sku TEXT");
}
catch (Exception $e) {
}
try {
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_products_sku ON products(sku)");
}
catch (Exception $e) {
}
echo "Done\n";
