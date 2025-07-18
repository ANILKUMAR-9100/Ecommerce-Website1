<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
$user_id = $_SESSION['user_id'];

// Handle "Add to Cart"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Check if product is already in the cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        // Update quantity if the product is already in the cart
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $cart_item['id']]);
    } else {
        // Insert new product into the cart
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }
    header("Location: cart.php");
    exit();
}

// Handle "Update Quantity"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$quantity, $cart_id]);
    }
    header("Location: cart.php");
    exit();
}

// Handle "Remove from Cart"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_from_cart'])) {
    $cart_id = $_POST['cart_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
    $stmt->execute([$cart_id]);
    header("Location: cart.php");
    exit();
}

// Fetch cart items
$stmt = $conn->prepare("SELECT cart.id AS cart_id, products.id AS product_id, products.name, products.image, products.price, cart.quantity 
                        FROM cart 
                        JOIN products ON cart.product_id = products.id 
                        WHERE cart.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cost = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            font-size: 2em;
            margin-bottom: 20px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        .cart-item img {
            max-width: 100px;
            border-radius: 8px;
            margin-right: 20px;
        }
        .item-details {
            flex-grow: 1;
        }
        .item-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #343a40;
        }
        .item-price {
            font-size: 1.1em;
            color: #495057;
        }
        .item-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .item-actions button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            margin-left: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .item-actions button:hover {
            background-color: #0056b3;
        }
        .quantity {
            width: 60px;
            padding: 5px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .cart-actions a {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1.1em;
            transition: background-color 0.3s;
        }
        .cart-actions a:hover {
            background-color: #218838;
        }
        .total-cost {
            font-size: 1.6em;
            font-weight: bold;
            color: #343a40;
            text-align: center;
            margin-top: 20px;
        }
        .empty-cart {
            text-align: center;
            font-size: 1.2em;
            color: #6c757d;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Your Cart</h2>

    <?php if (empty($cart_items)): ?>
        <p class="empty-cart">Your cart is empty.</p>
    <?php else: ?>
        <?php foreach ($cart_items as $item): 
            $subtotal = $item['price'] * $item['quantity'];
            $total_cost += $subtotal;
        ?>
            <div class="cart-item">
                <img src="../images/<?= htmlspecialchars($item['image']); ?>" alt="<?= htmlspecialchars($item['name']); ?>" class="item-image">
                <div class="item-details">
                    <div class="item-name"><?= htmlspecialchars($item['name']); ?></div>
                    <div class="item-price">$<?= number_format($item['price'], 2); ?> x <?= $item['quantity']; ?></div>
                </div>
                <div class="item-actions">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="cart_id" value="<?= $item['cart_id']; ?>">
                        <input type="number" name="quantity" value="<?= $item['quantity']; ?>" class="quantity" min="1">
                        <button type="submit" name="update_quantity">Update</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="cart_id" value="<?= $item['cart_id']; ?>">
                        <button type="submit" name="remove_from_cart">Remove</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="total-cost">
            Total: $<?= number_format($total_cost, 2); ?>
        </div>
    <?php endif; ?>

    <div class="cart-actions">
        <a href="../index.php">Back to Shop</a>
        <a href="checkout.php">Proceed to Checkout</a>
    </div>
</div>

</body>
</html>
