<?php
require_once 'config.php';
checkRole(['admin', 'manager']);

if (!isset($_GET['id'])) {
    redirect('products.php');
}

$id = $_GET['id'];
$product = getProductById($conn, $id);

if (!$product) {
    redirect('products.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'name' => sanitize($_POST['name']),
        'description' => sanitize($_POST['description']),
        'price' => sanitize($_POST['price']),
        'quantity' => sanitize($_POST['quantity']),
        'category' => sanitize($_POST['category'])
    ];
    
    if (updateProduct($conn, $id, $data)) {
        $_SESSION['message'] = 'Product updated successfully';
        $_SESSION['message_type'] = 'success';
        redirect('products.php');
    } else {
        $_SESSION['message'] = 'Error updating product';
        $_SESSION['message_type'] = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda - Editar Datos De Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>Editar Producto</h2>
        
        <div class="card mt-4">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del Producto</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $product['name']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripcion</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo $product['description']; ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Precio</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Cantidad En Inventario</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="<?php echo $product['quantity']; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Categoria</label>
                        <input type="text" class="form-control" id="category" name="category" value="<?php echo $product['category']; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Actualizar Producto</button>
                    <a href="products.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>