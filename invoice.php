<?php
require_once 'config.php';
checkRole(['admin', 'manager', 'staff']);

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$id = $_GET['id'];
$order = getOrderById($conn, $id);
$items = getOrderItems($conn, $id);

if (!$order) {
    redirect('orders.php');
}

// Generate invoice number (you might want to store this in the database)
$invoice_number = 'INV-' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda - Factura #<?php echo $invoice_number; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 20px;
            }
        }
        .invoice-header {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        .table th {
            border-top: none;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <h1 class="invoice-title">Tienda</h1>
                    <p>123 Store Street<br>City, State 12345<br>Phone: (123) 456-7890</p>
                </div>
                <div class="col-md-6 text-end">
                    <h2 class="invoice-title">FACTURA</h2>
                    <p class="mb-1"><strong>Factura #:</strong> <?php echo $invoice_number; ?></p>
                    <p class="mb-1"><strong>Fecha:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
                    <p class="mb-1"><strong>Orden #:</strong> <?php echo $order['order_id']; ?></p>
                    <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="row invoice-details">
            <div class="col-md-6">
                <h5>Factura para:</h5>
                <p>
                    <strong><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></strong><br>
                    <?php echo nl2br($order['address']); ?><br>
                    <?php echo $order['email']; ?><br>
                    <?php echo $order['phone']; ?>
                </p>
            </div>
            <div class="col-md-6 text-end">
                <h5>Metodo de Pago:</h5>
                <p>
                    <?php 
                        $payment_methods = [
                            'cash' => 'Cash',
                            'credit_card' => 'Credit Card',
                            'debit_card' => 'Debit Card',
                            'bank_transfer' => 'Bank Transfer'
                        ];
                        echo $payment_methods[$order['payment_method']] ?? ucfirst($order['payment_method']); 
                    ?>
                </p>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Descripcion</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Cant</th>
                        <th class="text-end">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $counter = 1;
                        $subtotal = 0;
                        foreach ($items as $item): 
                            $item_total = $item['unit_price'] * $item['quantity'];
                            $subtotal += $item_total/1.16;
                    ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td>
                                <strong><?php echo $item['name']; ?></strong><br>
                                <small><?php echo $item['description']; ?></small>
                            </td>
                            <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="text-end"><?php echo $item['quantity']; ?></td>
                            <td class="text-end">$<?php echo number_format($item_total, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                        <td class="text-end">$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end"><strong>IVA (16%):</strong></td>
                        <td class="text-end">$<?php echo number_format($order['total_amount']-$subtotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                        <td class="text-end">$<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="mt-5 pt-4 border-top">
            <p>Gracias por su compra!</p>
            <p class="text-muted">Porfavor valide sus datos y su forma de pago</p>
        </div>
        
        <div class="mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Imprimir factura</button>
            <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-secondary">Regresar a ordenes</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>