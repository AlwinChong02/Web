<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "javaroma_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Proceed to Checkout action
if (isset($_POST['checkout'])) {
    if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {

        // Step 1: Insert into the 'orders' table
        $userID = 1; // Replace with actual user ID if available
        $orderDate = date('Y-m-d H:i:s');

        $sqlOrder = "INSERT INTO orders (userID, orderDate) VALUES (?, ?)";
        $stmtOrder = $conn->prepare($sqlOrder);
        $stmtOrder->bind_param("is", $userID, $orderDate);
        $stmtOrder->execute();

        // Get the last inserted orderID
        $orderID = $conn->insert_id;

        // Step 2: Insert into 'orderItems' table for each product in the cart
        foreach ($_SESSION['cart'] as &$item) {
            // Set the selected temperature in the session for each item
            $item['temperature'] = $_POST['temperature'][$item['id']] ?? '';  // Retrieve the temperature from the form

            // Now save to the database
            $productID = $item['id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $temperature = $item['temperature'];  // Get the selected temperature

            $sqlOrderItems = "INSERT INTO orderItems (orderID, productID, quantity, price, temperature) 
                              VALUES (?, ?, ?, ?, ?)";
            $stmtOrderItems = $conn->prepare($sqlOrderItems);
            $stmtOrderItems->bind_param("iiids", $orderID, $productID, $quantity, $price, $temperature);
            $stmtOrderItems->execute();
        }

        // Clear the cart session
        $_SESSION['cart'] = array();

        // Redirect to payment page
        header("Location: payment.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <style>
        .cart-table {
            width: 80%;
            margin: auto;
            border-collapse: collapse;
        }

        .cart-table th,
        .cart-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .cart-table th {
            background-color: #f4f4f4;
        }

        .cart-buttons {
            margin-top: 20px;
            text-align: center;
        }

        .cart-buttons button {
            padding: 10px 20px;
            background-color: #004080;
            color: white;
            border: none;
            cursor: pointer;
            margin: 5px;
        }

        .cart-buttons button:hover {
            background-color: #002d66;
        }

        /* Flexbox layout for buttons */
        .cart-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            align-items: center;
        }

        .cart-actions form {
            display: inline;
        }

        .quantity-input {
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <h2>Your Cart</h2>

    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
        <table class="cart-table">
            <tr>
                <th>Product Name</th>
                <th>Temperature</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
            <?php
            $totalPrice = 0;
            foreach ($_SESSION['cart'] as $key => $item):
                $itemTotal = $item['quantity'] * $item['price'];
                $totalPrice += $itemTotal;
            ?>
                <tr>
                    <td><?php echo $item['name']; ?></td>
                    <td>
                        <select name="temperature[<?php echo $item['id']; ?>]" required form="checkout-form">
                            <option value="" disabled selected hidden>Hot/Cold</option>
                            <option value="Hot" <?php if ($item['temperature'] == 'Hot') echo 'selected'; ?>>Hot</option>
                            <option value="Cold" <?php if ($item['temperature'] == 'Cold') echo 'selected'; ?>>Cold</option>
                        </select>
                    </td>
                    <td>
                        <!-- Quantity input -->
                        <form action="update_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                            <input type="number" class="quantity-input" name="new_quantity" value="<?php echo $item['quantity']; ?>" min="1">
                        
                            <div class="cart-actions">
                                <!-- Update cart form -->
                                <button type="submit">Update</button>
                        </form>

                        <!-- Remove from cart form -->
                        <form action="remove_from_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                            <button type="submit">Remove</button>
                        </form>
                            </div>
                    </td>
                    <td>RM <?php echo $item['price']; ?></td>
                    <td>RM <?php echo number_format($itemTotal, 2); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3">Total Price</td>
                <td colspan="2">RM <?php echo number_format($totalPrice, 2); ?></td>
            </tr>
        </table>

        <div class="cart-buttons">
            <!-- Separate form for checkout -->
            <form action="cart.php" method="POST" id="checkout-form">
                <button type="submit" name="checkout">Proceed to Checkout</button>
            </form>
            <button type="button" onclick="window.location.href='index.php'">Continue Shopping</button>
        </div>
    <?php else: ?>
        <p>Your cart is empty.</p>
        <button onclick="window.location.href='index.php'">Continue Shopping</button>
    <?php endif; ?>
</body>

</html>

