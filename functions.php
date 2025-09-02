<?php
// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function checkRole($allowedRoles) {
    if (!isLoggedIn() || !in_array($_SESSION['role'], $allowedRoles)) {
        redirect('login.php');
    }
}

// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Get all products
function getProducts($conn, $category = null) {
    $sql = "SELECT * FROM products";
    if ($category) {
        $sql .= " WHERE category = :category";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':category', $category);
    } else {
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product by ID
function getProductById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add new product
function addProduct($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, quantity, category) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$data['name'], $data['description'], $data['price'], $data['quantity'], $data['category']]);
}

// Update product
function updateProduct($conn, $id, $data) {
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, category = ? WHERE product_id = ?");
    return $stmt->execute([$data['name'], $data['description'], $data['price'], $data['quantity'], $data['category'], $id]);
}

// Delete product
function deleteProduct($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    return $stmt->execute([$id]);
}

// Get all customers
function getCustomers($conn) {
    $stmt = $conn->prepare("SELECT * FROM customers");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get customer by ID
function getCustomerById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add new customer
function addCustomer($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['address']]);
}

// Update customer
function updateCustomer($conn, $id, $data) {
    $stmt = $conn->prepare("UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE customer_id = ?");
    return $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['address'], $id]);
}

// Get all orders
function getOrders($conn) {
    $stmt = $conn->prepare("SELECT o.*, c.first_name, c.last_name FROM orders o JOIN customers c ON o.customer_id = c.customer_id ORDER BY o.order_date DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get order by ID
function getOrderById($conn, $id) {
    $stmt = $conn->prepare("SELECT o.*, c.first_name, c.last_name, c.email, c.phone, c.address 
                          FROM orders o JOIN customers c ON o.customer_id = c.customer_id 
                          WHERE o.order_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get order items
function getOrderItems($conn, $order_id) {
    $stmt = $conn->prepare("SELECT oi.*, p.name, p.description 
                           FROM order_items oi JOIN products p ON oi.product_id = p.product_id 
                           WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Create new order
function createOrder($conn, $customer_id, $items, $payment_method) {
    try {
        $conn->beginTransaction();
        
        // Calculate total amount
        $total = 0;
        foreach ($items as $item) {
            $product = getProductById($conn, $item['product_id']);
            $total += $product['price'] * $item['quantity'];
        }
        
        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, payment_method) VALUES (?, ?, ?)");
        $stmt->execute([$customer_id, $total, $payment_method]);
        $order_id = $conn->lastInsertId();
        
        // Insert order items and update product quantities
        foreach ($items as $item) {
            $product = getProductById($conn, $item['product_id']);
            
            // Insert order item
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $product['price']]);
            
            // Update product quantity
            $new_quantity = $product['quantity'] - $item['quantity'];
            $stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
            $stmt->execute([$new_quantity, $item['product_id']]);
        }
        
        $conn->commit();
        return $order_id;
    } catch (Exception $e) {
        $conn->rollBack();
        return false;
    }
}

// Update order status
function updateOrderStatus($conn, $order_id, $status) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    return $stmt->execute([$status, $order_id]);
}

// User login
function login($conn, $username, $password) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// Get sales report
function getSalesReport($conn, $start_date = null, $end_date = null) {
    $sql = "SELECT DATE(o.order_date) as date, COUNT(o.order_id) as orders, SUM(o.total_amount) as revenue 
            FROM orders o";
    
    $params = [];
    
    if ($start_date && $end_date) {
        $sql .= " WHERE DATE(o.order_date) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    } elseif ($start_date) {
        $sql .= " WHERE DATE(o.order_date) >= ?";
        $params[] = $start_date;
    } elseif ($end_date) {
        $sql .= " WHERE DATE(o.order_date) <= ?";
        $params[] = $end_date;
    }
    
    $sql .= " GROUP BY DATE(o.order_date) ORDER BY date";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>