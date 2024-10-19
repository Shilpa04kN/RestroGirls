<?php
// Include database connection or any other necessary files
require('backends/connection-pdo.php');

// Handle adding item to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $food_id = $_POST['food_id'];
    $quantity = $_POST['quantity'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    // Check if item already exists in cart
    if (array_key_exists($food_id, $_SESSION['cart'])) {
        $_SESSION['cart'][$food_id]['quantity'] += $quantity;
    } else {
        // Fetch food details including price
        $stmt = $pdoconn->prepare("SELECT * FROM food WHERE id = ?");
        $stmt->execute([$food_id]);
        $food = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if 'price' key exists to avoid warnings
        $food_price = isset($food['price']) ? $food['price'] : 0;

        $_SESSION['cart'][$food_id] = [
            'quantity' => $quantity,
            'price' => $food_price
        ];
    }

    $_SESSION['msg'] = "Item added to cart!";
}

// Fetch food items from the database
if (isset($_REQUEST['id'])) {
    $sql = 'SELECT * FROM food WHERE cat_id = "' . $_REQUEST['id'] . '" ORDER BY fname ASC';
} else {
    $sql = 'SELECT * FROM food ORDER BY fname ASC';
}

$query  = $pdoconn->prepare($sql);
$query->execute();
$arr_all = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foods</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- SweetAlert library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>
<section class="fcategories">
<div class="container">
    <?php
    // Loop through the food items
    foreach ($arr_all as $index => $food) {
        // Open a new row for every third item
        if ($index % 3 == 0) {
            echo '<div class="row">';
        }
        ?>
        <div class="col s12 m4">
            <div class="card">
                <!-- Adjusted card-image section -->
                <div class="card-image waves-effect waves-block waves-light">
                    <!-- Use a common placeholder image for all items -->
                    <img class="activator" src="images/banner1.jpg">
                </div>

                <div class="card-content">
                    <span class="card-title activator grey-text text-darken-4">
                        <a class="black-text" href="#">
                            <?php echo $food['fname']; ?>
                        </a>
                        <i class="material-icons right">more_vert</i>
                    </span>
                    <div class="card-content">
                        <p>This is a popular Food of India. Order Now to Grab a bite of it!</p>
                    </div>
                    <div class="card-content center">
    <!-- Display price, check if price exists -->
    <p>Price: $<?php echo isset($food['price']) && !empty($food['price']) ? $food['price'] : '0.00'; ?></p>
    <form method="post" action="">
        <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
        <input type="number" name="quantity" value="1" min="1">
        <button type="submit" name="add_to_cart" class="btn waves-effect waves-light">Add to Cart</button>
    </form>
</div>

                </div>
                <div class="card-reveal">
                    <span class="card-title grey-text text-darken-4">
                        <?php echo $food['fname']; ?>
                        <i class="material-icons right">close</i>
                    </span>
                    <p><?php echo $food['description']; ?></p>
                </div>
            </div>
        </div>
        <?php
        // Close the row after every third item or if it's the last item
        if (($index + 1) % 3 == 0 || ($index + 1) == count($arr_all)) {
            echo '</div>'; // Close row
        }
    }
    ?>
</div>

</section>

<!-- Cart Icon -->
<div class="fixed-action-btn">
    <a class="btn-floating btn-large red modal-trigger" href="#cartModal">
        <i class="large material-icons">shopping_cart</i>
    </a>
</div>

<!-- Cart Modal -->
<div id="cartModal" class="modal bottom-sheet">
    <div class="modal-content">
        <h4>Shopping Cart</h4>
        <ul>
            <?php
            $totalAmount = 0;
            foreach ($_SESSION['cart'] ?? [] as $food_id => $item): 
                $subtotal = $item['quantity'] * $item['price'];
                $totalAmount += $subtotal;
            ?>
                <li>
                    Food ID: <?php echo $food_id; ?>, 
                    Quantity: <?php echo $item['quantity']; ?>, 
                    Price: $<?php echo $item['price']; ?>, 
                    Subtotal: $<?php echo $subtotal; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <p>Total Amount: $<?php echo $totalAmount; ?></p>
        <button class="btn waves-effect waves-light" id="clearCartBtn">Clear Cart</button>
        <a class="btn waves-effect waves-light" id="orderNowBtn">Order Now</a>

           <!-- Order Form -->
           <div id="orderForm" style="display: none;">
            <h5>Enter Your Details</h5>
            <form id="orderForm" action="place_order.php" method="post">
                <div class="input-field">
                    <input id="name" name="name" type="text" class="validate" required>
                    <label for="name">Name</label>
                </div>
                <div class="input-field">
                    <input id="phone" name="phone" type="tel" class="validate" required>
                    <label for="phone">Phone Number</label>
                </div>
                <div class="input-field">
                    <input id="email" name="email" type="email" class="validate" required>
                    <label for="email">Email Address</label>
                </div>
                <button type="button" class="btn waves-effect waves-light" id="submitOrderBtn">Submit Order</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var clearCartBtn = document.getElementById('clearCartBtn');
    clearCartBtn.addEventListener('click', function() {
        fetch('chunks/clear_cart.php') // Adjust the path as per your directory structure
            .then(response => {
                if (response.ok) {
                    return response.text();
                }
                throw new Error('Network response was not ok.');
            })
            .then(data => {
                // Update cart modal content if needed
                var cartModalContent = document.querySelector('#cartModal .modal-content');
                cartModalContent.innerHTML = data; // Replace content with response text
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });


    // Add event listener to order now button
    var orderNowBtn = document.getElementById('orderNowBtn');
    orderNowBtn.addEventListener('click', function() {
        var orderForm = document.getElementById('orderForm');
        orderForm.style.display = 'block';
    });

    // Add event listener to submit order button
    var submitOrderBtn = document.getElementById('submitOrderBtn');
    submitOrderBtn.addEventListener('click', function() {
        // Your code for form validation (if needed)

        // Display SweetAlert confirmation
        Swal.fire({
            icon: 'success',
            title: 'Order Placed!',
            text: 'Thank you for your order.',
            confirmButtonText: 'OK'
        }).then((result) => {
        
        });
    });
});
</script>

</body>
</html>
