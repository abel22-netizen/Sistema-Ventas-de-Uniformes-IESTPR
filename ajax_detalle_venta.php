<?php
include 'config.php';

if (!isLoggedIn()) {
    echo '<div class="alert alert-danger">Debe iniciar sesión</div>';
    exit();
}

$venta_id = $_GET['venta_id'] ?? '';

if (empty($venta_id)) {
    echo '<div class="alert alert-danger">Venta no especificada</div>';
    exit();
}

// Verificar que la venta pertenece al usuario
$stmt = $pdo->prepare("SELECT * FROM ventas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$venta_id, $_SESSION['user_id']]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    echo '<div class="alert alert-danger">Venta no encontrada</div>';
    exit();
}

// Obtener detalles de la venta
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
        <h6>Información de la Compra</h6>
        <p><strong>Número:</strong> #<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></p>
        <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></p>
        <p><strong>Estado:</strong> 
            <span class="badge bg-<?php 
                echo $venta['estado'] == 'completado' ? 'success' : 
                     ($venta['estado'] == 'pendiente' ? 'warning' : 'danger'); 
            ?>">
                <?php echo ucfirst($venta['estado']); ?>
            </span>
        </p>
        <p><strong>Método de Pago:</strong>
            <span class="badge bg-info">
                <i class="fas fa-<?php 
                    echo $venta['metodo_pago'] == 'yape' ? 'Yape' : 
                         ($venta['metodo_pago'] == 'plin' ? 'Plin' : 'credit-card'); 
                ?> me-1"></i>
                <?php echo ucfirst($venta['metodo_pago']); ?>
            </span>
        </p>
    </div>
    <div class="col-md-6">
        <h6>Información de Entrega</h6>
        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($venta['direccion_entrega'] ?? 'No especificada'); ?></p>
        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($venta['telefono_contacto'] ?? 'No especificado'); ?></p>
        <p><strong>Total:</strong> <span class="fw-bold text-success"><?php echo formatPrice($venta['total']); ?></span></p>
    </div>
</div>

<hr>

<h6>Productos Comprados</h6>
<div class="table-responsive">
    <table class="table table-striped">
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
                <td><?php echo formatPrice($detalle['precio_unitario']); ?></td>
                <td><?php echo formatPrice($detalle['subtotal']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end"><strong>Total:</strong></td>
                <td><strong><?php echo formatPrice($venta['total']); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php if ($venta['estado'] == 'pendiente'): ?>
<div class="alert alert-warning">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Pedido pendiente:</strong> Tu pedido está siendo procesado. Te contactaremos para coordinar la entrega.
</div>
<?php elseif ($venta['estado'] == 'completado'): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i>
    <strong>Pedido completado:</strong> Tu pedido ha sido entregado exitosamente.
</div>
<?php else: ?>
<div class="alert alert-danger">
    <i class="fas fa-times-circle me-2"></i>
    <strong>Pedido entregado:</strong> Este pedido ha sido entregado.
</div>
<?php endif; ?>