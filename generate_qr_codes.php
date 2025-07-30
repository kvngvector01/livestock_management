<?php
include 'db.php';

$sql = "SELECT * FROM products WHERE qr_data IS NULL";
$result = $conn->query($sql);

while($product = $result->fetch_assoc()) {
    $qr_data = json_encode([
        'product_id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'farmer_id' => $product['farmer_id']
    ]);
    
    $conn->query("UPDATE products SET qr_data = '".$conn->real_escape_string($qr_data)."' WHERE id = ".$product['id']);
    echo "Updated product #".$product['id']."<br>";
}
echo "Done!";
?>