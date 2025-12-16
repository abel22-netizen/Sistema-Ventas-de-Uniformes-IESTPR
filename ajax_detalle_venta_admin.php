<?php
include 'config.php';
requireAdmin($pdo);

$venta_id = $_GET['venta_id'] ?? '';

if (empty($venta_id)) {
    echo '<div class="alert alert-danger">Venta no especificada</div>';
    exit();
}

// Obtener detalles de la venta (admin puede ver todas)
$stmt = $pdo->prepare("
    SELECT v.*, u.nombre as cliente_nombre, u.email, u.telefono, u.direccion,
           DATE_FORMAT(v.fecha_venta, '%d/%m/%Y %H:%i') as fecha_formateada
    FROM ventas v 
    JOIN usuarios u ON v.usuario_id = u.id 
    WHERE v.id = ?
");
$stmt->execute([$venta_id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    echo '<div class="alert alert-danger">Venta no encontrada</div>';
    exit();
}

// Obtener detalles de productos
$stmt = $pdo->prepare("
    SELECT dv.*, p.nombre, p.descripcion, p.talla 
    FROM detalle_venta dv 
    JOIN productos p ON dv.producto_id = p.id 
    WHERE dv.venta_id = ?
");
$stmt->execute([$venta_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-6">
        <h6>Información del Cliente</h6>
        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($venta['cliente_nombre']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($venta['email']); ?></p>
        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($venta['telefono'] ?? 'No especificado'); ?></p>
        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($venta['direccion_entrega'] ?? 'No especificada'); ?></p>
    </div>
    <div class="col-md-6">
        <h6>Información de la Venta</h6>
        <p><strong>Número:</strong> #<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></p>
        <p><strong>Fecha:</strong> <?php echo $venta['fecha_formateada']; ?></p>
        <p><strong>Estado:</strong> 
            <span class="badge bg-<?php 
                echo $venta['estado'] == 'pendiente' ? 'warning' : 
                     ($venta['estado'] == 'entregado' ? 'success' : 
                     ($venta['estado'] == 'completado' ? 'info' : 'danger')); 
            ?>">
                <?php echo ucfirst($venta['estado']); ?>
            </span>
        </p>
        <p><strong>Total:</strong> <span class="fw-bold text-success">S/ <?php echo number_format($venta['total'], 2); ?></span></p>
    </div>
</div>

<hr>

<h6>Productos Comprados</h6>
<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Talla</th>
                <th>Cantidad</th>
                <th>Precio Unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $detalle): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($detalle['nombre']); ?></strong><br>
                    <small class="text-muted"><?php echo htmlspecialchars($detalle['descripcion']); ?></small>
                </td>
                <td><?php echo $detalle['talla']; ?></td>
                <td><?php echo $detalle['cantidad']; ?></td>
                <td>S/ <?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                <td>S/ <?php echo number_format($detalle['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end"><strong>Total:</strong></td>
                <td><strong>S/ <?php echo number_format($venta['total'], 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>