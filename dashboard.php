<?php
require_once 'config.php';
checkRole(['admin', 'manager', 'staff']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Tienda</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Tablero Principal</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="bi bi-box-seam"></i> Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customers.php"><i class="bi bi-people"></i> Clientes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php"><i class="bi bi-cart"></i> Ordenes</a>
                    </li>
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reportes</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Dashboard</h2>
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Total de Productos</h5>
                                <h2 class="card-text">
                                    <?php 
                                        $stmt = $conn->query("SELECT COUNT(*) FROM products");
                                        echo $stmt->fetchColumn(); 
                                    ?>
                                </h2>
                            </div>
                            <i class="bi bi-box-seam" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Total De Clientes</h5>
                                <h2 class="card-text">
                                    <?php 
                                        $stmt = $conn->query("SELECT COUNT(*) FROM customers");
                                        echo $stmt->fetchColumn(); 
                                    ?>
                                </h2>
                            </div>
                            <i class="bi bi-people" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Total de Ordenes</h5>
                                <h2 class="card-text">
                                    <?php 
                                        $stmt = $conn->query("SELECT COUNT(*) FROM orders");
                                        echo $stmt->fetchColumn(); 
                                    ?>
                                </h2>
                            </div>
                            <i class="bi bi-cart" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Ingresos</h5>
                                <h2 class="card-text">
                                    $<?php 
                                        $stmt = $conn->query("SELECT SUM(total_amount) FROM orders");
                                        echo number_format($stmt->fetchColumn(), 2); 
                                    ?>
                                </h2>
                            </div>
                            <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Ordenes Recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Orden ID</th>
                                        <th>Cliente</th>
                                        <th>Monto</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $stmt = $conn->query("SELECT o.order_id, o.total_amount, o.status, c.first_name, c.last_name 
                                                             FROM orders o JOIN customers c ON o.customer_id = c.customer_id 
                                                             ORDER BY o.order_date DESC LIMIT 5");
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<tr>
                                                <td>{$row['order_id']}</td>
                                                <td>{$row['first_name']} {$row['last_name']}</td>
                                                <td>$" . number_format($row['total_amount'], 2) . "</td>
                                                <td><span class='badge bg-" . getStatusColor($row['status']) . "'>{$row['status']}</span></td>
                                            </tr>";
                                        }
                                        
                                        function getStatusColor($status) {
                                            switch ($status) {
                                                case 'pending': return 'warning';
                                                case 'processing': return 'info';
                                                case 'shipped': return 'primary';
                                                case 'delivered': return 'success';
                                                case 'cancelled': return 'danger';
                                                default: return 'secondary';
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">Ver Todas Las Ordenes</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Productos con baja cantidad en inventario</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th>Inventario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $stmt = $conn->query("SELECT * FROM products WHERE quantity < 10 ORDER BY quantity ASC LIMIT 5");
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $stockClass = $row['quantity'] < 5 ? 'text-danger fw-bold' : 'text-warning';
                                            echo "<tr>
                                                <td>{$row['name']}</td>
                                                <td>$" . number_format($row['price'], 2) . "</td>
                                                <td class='$stockClass'>{$row['quantity']}</td>
                                            </tr>";
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="products.php" class="btn btn-sm btn-outline-primary">Ver Todos Los Products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>