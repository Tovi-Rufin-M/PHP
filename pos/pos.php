<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h2>Point of Sale</h2>
    <form id="pos-form">
        <label for="barcode">Barcode</label>
        <input type="text" name="barcode" id="barcode">
        <button type="button" onclick="addToCart()">Add to Cart</button>
    </form>
    <h2>Cart</h2>
    <table border="1" width="70%" id="cart-table">
        <thead>
            <th>QTY</th>
            <th>PRODUCT NAME</th>
            <th>UNIT PRICE</th>
            <th>SUBTOTAL</th>
        </thead>
        <tbody id="cart-items">
        </tbody>
        <tfoot>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td align="right" 
                style="font-weight: bold; size: 18pt;">Total:</td>
                <td id="total"
                style="font-weight: bold; size: 18pt;"></td>
            </tr>
        </tfoot>
    </table>

    <script>
        let cart = [];
        let total = 0;
        function addToCart() {
            var code;
            let input = document.getElementById('barcode').value;
            let [quantity, barcode] = input.split('*');
           
            if(barcode == undefined) {
                code = quantity;
                quantity = 1;
            } else {
                code = barcode;
            }

            fetch(`fetch-product.php?bcode=${code}`)
                .then(response => response.json())
                .then(product => {
                   
                    if (!product) {
                        alert("Product not found!");
                        return;
                    }

                    const stotal = quantity * product.unit_price;
                    total += stotal; // total = total + stotal;
        cart.push(
            { 
                qty: quantity,
                pname: product.prod_name + "-" + product.description,
                price: product.unit_price,
                subtotal: stotal
            }
        ); //end of cart.push

        document.getElementById('cart-items').innerHTML += 
        `
            <tr>
                <td>${quantity}</td>
                <td>${product.product_name + "-" + product.description}</td>
                <td>${product.unit_price}</td>
                <td>${stotal}</td>
            </tr>
        `;
        document.getElementById('total').innerHTML = total;
                });
        }
    </script>
</body>
</html>