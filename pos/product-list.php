<?php
    include 'db-connect.php';

    if (isset($_POST['saveproduct'])) {
        $barcode = $_POST['bcode'];
        $prod_name = $_POST['pname'];
        $prod_desc = $_POST['description'];
        $u_price = $_POST['uprice'];

        $insert = "INSERT INTO products 
        (barcode, product_name, description, unit_price)
        VALUES ('$barcode', '$prod_name', '$prod_desc', '$u_price')";
        mysqli_query($conn, $insert);

        //unset($_POST['saveproduct']);
    }

    $products = mysqli_query($conn, "SELECT * FROM products 
    ORDER BY product_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Products</title>
</head>
<body>
    <h2>New Product</h2>
    <form method="POST">
        <table width="30%">
            <tr>
                <td>Barcode</td>
                <td><input type="text" name="bcode"></td>
            </tr>
            <tr>
                <td>Name</td>
                <td><input type="text" name="pname"></td>
            </tr>
            <tr>
                <td>Description</td>
                <td><input type="text" name="description"></td>
            </tr>
            <tr>
                <td>Unit Price</td>
                <td><input type="number" name="uprice"></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input type="submit" value="Save Product" name="saveproduct">
                </td>
            </tr>
        </table>
    </form>
    <h2>Manage Products</h2>
    <table border="1" width="70%">
        <tr>
            <th>Barcode</th>
            <th>Name</th>
            <th>Description</th>
            <th>Unit Price</th>
            <th>Actions</th>
        </tr>
        <tbody>
            <?php while($row = mysqli_fetch_array($products)) { ?>
            <tr>
                <td><?php echo  $row['barcode']; ?></td>
                <td><?php echo  $row['product_name']; ?></td>
                <td><?php echo  $row['description']; ?></td>
                <td><?php echo  $row['unit_price']; ?></td>
                <td>Edit | Delete
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>