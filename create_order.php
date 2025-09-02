<?php
require_once 'config.php';
checkRole(['admin', 'manager', 'staff']);

// Initialize variables
$customer_id = null;
$selected_products = [];
$payment_method = 'cash';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_item'])) {
        // Add item to cart
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        $product = getProductById($conn, $product_id);
        
        if ($product && $quantity > 0 && $quantity <= $product['quantity']) {
            $selected_products[$product_id] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
            
            // Store in session
            $_SESSION['cart'] = $selected_products;
            $_SESSION['customer_id'] = $_POST['customer_id'];
        }
    } elseif (isset($_POST['remove_item'])) {
        // Remove item from cart
        $product_id = $_POST['product_id'];
        
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $selected_products = $_SESSION['cart'];
        }
    } elseif (isset($_POST['create_order'])) {
        // Create the order
        $customer_id = $_POST['customer_id'];
        $payment_method = $_POST['payment_method'];
        
        if (!empty($_SESSION['cart']) && $customer_id) {
            $items = [];
            foreach ($_SESSION['cart'] as $item) {
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ];
            }
            
            $order_id = createOrder($conn, $customer_id, $items, $payment_method);
            
            if ($order_id) {
                // Clear cart
                unset($_SESSION['cart']);
                unset($_SESSION['customer_id']);
                
                $_SESSION['message'] = "Order #$order_id created successfully!";
                $_SESSION['message_type'] = 'success';
                redirect("view_order.php?id=$order_id");
            } else {
                $_SESSION['message'] = "Error creating order";
                $_SESSION['message_type'] = 'danger';
            }
        }
    }
}

// Get cart from session if exists
if (isset($_SESSION['cart'])) {
    $selected_products = $_SESSION['cart'];
    $customer_id = $_SESSION['customer_id'] ?? null;
}

// Get all products and customers
$products = getProducts($conn);
$customers = getCustomers($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retail Store - Create Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Create New Order</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Select Products</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="product_id" class="form-label">Product</label>
                                    <select class="form-select" id="product_id" name="product_id" required>
                                        <option value="">Select a product</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['product_id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['quantity']; ?>">
                                                <?php echo $product['name']; ?> ($<?php echo number_format($product['price'], 2); ?>) - Stock: <?php echo $product['quantity']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                                    <small id="stockInfo" class="form-text text-muted"></small>
                                </div>
                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                    <button type="submit" name="add_item" class="btn btn-primary w-100">Add</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Order Items</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($selected_products)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $subtotal = 0;
                                            foreach ($selected_products as $item): 
                                                $item_total = $item['price'] * $item['quantity'];
                                                $subtotal += $item_total;
                                        ?>
                                            <tr>
                                                <td><?php echo $item['name']; ?></td>
                                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>$<?php echo number_format($item_total, 2); ?></td>
                                                <td>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Subtotal:</th>
                                            <th>$<?php echo number_format($subtotal, 2); ?></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No items added to the order yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Customer & Payment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer</label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value="">Select a customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" <?php echo $customer_id == $customer['customer_id'] ? 'selected' : ''; ?>>
                                            <?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="cash" <?php echo $payment_method == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="credit_card" <?php echo $payment_method == 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                    <option value="debit_card" <?php echo $payment_method == 'debit_card' ? 'selected' : ''; ?>>Debit Card</option>
                                    <option value="bank_transfer" <?php echo $payment_method == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                </select>
                            </div>
                            
                            <?php if (!empty($selected_products)): ?>
                                <button type="submit" name="create_order" class="btn btn-success w-100 mt-3">Create Order</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-success w-100 mt-3" disabled>Add items to create order</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update stock info when product is selected
        document.getElementById('product_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const stock = selectedOption.getAttribute('data-stock');
            const price = selectedOption.getAttribute('data-price');
            
            document.getElementById('stockInfo').textContent = `Available: ${stock}`;
            document.getElementById('quantity').setAttribute('max', stock);
        });
    </script>
</body>
</html>