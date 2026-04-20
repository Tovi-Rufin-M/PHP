<?php
    include 'db-connect.php';

    if (isset($_GET['bcode'])) {
        $barcode = $_GET['bcode'];

        $select = "SELECT * FROM products 
        WHERE barcode = '$barcode' LIMIT 1";

        $result = mysqli_query($conn, $select);

        if (mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
            echo json_encode($product);
        } else {
            echo json_encode(null); //product not found return empty
        }
    } else {
        echo json_encode(null); //no barcode provided.
    }
?>