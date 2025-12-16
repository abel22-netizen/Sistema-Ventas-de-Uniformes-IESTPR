<?php
include 'config.php';
requireLogin();
$user = getCurrentUser($pdo);

// Obtener items del carrito
$stmt = $pdo->prepare("
    SELECT c.*, p.nombre, p.descripcion, p.precio, p.stock, p.talla 
    FROM carrito c 
    JOIN productos p ON c.producto_id = p.id 
    WHERE c.usuario_id = ?
");
$stmt->execute([$user['id']]);
$carrito_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total
$total = 0;
foreach ($carrito_items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

// Eliminar item desde GET (para enlaces)
if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM carrito WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$_GET['eliminar'], $user['id']]);
    header("Location: carrito.php");
    exit();
}

// Actualizar cantidad con AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_update'])) {
    $item_id = $_POST['item_id'];
    $cantidad = intval($_POST['cantidad']);
    
    if ($cantidad > 0) {
        $stmt = $pdo->prepare("UPDATE carrito SET cantidad = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$cantidad, $item_id, $user['id']]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM carrito WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$item_id, $user['id']]);
    }
    
    // Recalcular total
    $total_actualizado = 0;
    foreach ($carrito_items as $item) {
        if ($item['id'] == $item_id) {
            $item['cantidad'] = $cantidad;
        }
        $total_actualizado += $item['precio'] * $item['cantidad'];
    }
    
    echo json_encode([
        'success' => true,
        'nueva_cantidad' => $cantidad,
        'subtotal' => formatPrice($carrito_items[array_search($item_id, array_column($carrito_items, 'id'))]['precio'] * $cantidad),
        'total' => formatPrice($total_actualizado),
        'carrito_count' => getCarritoCount($pdo, $user['id'])
    ]);
    exit();
}

// Procesar acciones POST normales
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['actualizar_carrito'])) {
        foreach ($_POST['cantidad'] as $item_id => $cantidad) {
            $cantidad = intval($cantidad);
            if ($cantidad > 0) {
                $stmt = $pdo->prepare("UPDATE carrito SET cantidad = ? WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$cantidad, $item_id, $user['id']]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM carrito WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$item_id, $user['id']]);
            }
        }
        header("Location: carrito.php");
        exit();
    }
    
    if (isset($_POST['realizar_compra'])) {
        if (count($carrito_items) > 0) {
            try {
                $pdo->beginTransaction();
                
                // Crear venta
                $stmt = $pdo->prepare("INSERT INTO ventas (usuario_id, total, estado, metodo_pago, direccion_entrega, telefono_contacto) VALUES (?, ?, 'pendiente', ?, ?, ?)");
                $stmt->execute([
                    $user['id'], 
                    $total, 
                    $_POST['metodo_pago'],
                    $_POST['direccion_entrega'],
                    $_POST['telefono_contacto']
                ]);
                $venta_id = $pdo->lastInsertId();
                
                // Crear detalles de venta y actualizar stock
                foreach ($carrito_items as $item) {
                    $subtotal = $item['precio'] * $item['cantidad'];
                    
                    $stmt = $pdo->prepare("INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$venta_id, $item['producto_id'], $item['cantidad'], $item['precio'], $subtotal]);
                    
                    // Actualizar stock
                    $stmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['cantidad'], $item['producto_id']]);
                }
                
                // Vaciar carrito
                $stmt = $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ?");
                $stmt->execute([$user['id']]);
                
                $pdo->commit();
                
                $_SESSION['compra_exitosa'] = true;
                header("Location: pago.php?venta_id=" . $venta_id);
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error al procesar la compra: " . $e->getMessage();
            }
        }
    }
}

$carrito_count = getCarritoCount($pdo, $user['id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - Sistema de Ventas de Uniformes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2980b9;
        }
        
        body {
            background: linear-gradient(rgba(255,255,255,0.9), rgba(255,255,255,0.9)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="%233498db" fill-opacity="0.03" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
            background-attachment: fixed;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            min-height: 100vh;
            box-shadow: 3px 0 20px rgba(0,0,0,0.2);
            position: fixed;
            width: 280px;
            z-index: 1000;
        }
        
        .sidebar .logo {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            background: rgba(0,0,0,0.1);
        }
        
        .logo-img {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .logo-img i {
            font-size: 2.5rem;
            color: var(--primary);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 15px 20px;
            margin: 3px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
            font-size: 1.1em;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }
        
        .navbar {
            background: rgba(255,255,255,0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .quantity-input {
            width: 80px;
            text-align: center;
        }
        
        .summary-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .badge-cart {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: var(--primary);
        }
        
        .payment-method.selected {
            border-color: var(--primary);
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        /* Spinner para loading */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="logo">
                    <div class="logo-img">
                        <img src="IESTP-LOGO.png" alt="ISTP Recuay" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h5>IESTP RECUAY</h5>
                    <small class="opacity-8">Venta de Uniformes</small>
                </div>
                
                <nav class="nav flex-column mt-4">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="productos.php">
                        <i class="fas fa-tshirt"></i> Productos
                    </a>
                    <a class="nav-link active" href="carrito.php">
                        <i class="fas fa-shopping-cart"></i> Mi Carrito
                        <?php if ($carrito_count > 0): ?>
                            <span id="cart-badge" class="badge-cart"><?php echo $carrito_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link" href="ventas.php">
                        <i class="fas fa-receipt"></i> Mis Compras
                    </a>
                    <a class="nav-link" href="perfil.php">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>
                    <div class="mt-4 pt-3 border-top border-white-10">
                        <a class="nav-link text-warning" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <span class="navbar-brand fw-bold text-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Mi Carrito de Compras
                        </span>
                        <div class="d-flex align-items-center">
                            <a href="productos.php" class="btn btn-outline-primary me-3">
                                <i class="fas fa-arrow-left me-1"></i> Seguir Comprando
                            </a>
                            <span class="badge bg-primary">
                                <i class="fas fa-shopping-cart me-1"></i> 
                                <span id="cart-count"><?php echo count($carrito_items); ?></span> items
                            </span>
                        </div>
                    </div>
                </nav>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (count($carrito_items) > 0): ?>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Items en el Carrito</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="cart-form">
                                    <?php foreach ($carrito_items as $item): ?>
                                    <div class="cart-item" id="item-<?php echo $item['id']; ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-2">
                                                <div class="text-center">
                                                    <i class="fas fa-tshirt fa-2x text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['nombre']); ?></h6>
                                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($item['descripcion']); ?></p>
                                                <span class="badge bg-secondary">Talla: <?php echo $item['talla']; ?></span>
                                            </div>
                                            <div class="col-md-2">
                                                <h6 class="text-success"><?php echo formatPrice($item['precio']); ?></h6>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="input-group">
                                                    <input type="number" 
                                                           data-item-id="<?php echo $item['id']; ?>"
                                                           value="<?php echo $item['cantidad']; ?>" 
                                                           min="1" max="<?php echo $item['stock']; ?>"
                                                           class="form-control quantity-input">
                                                    <div class="spinner ms-2" id="spinner-<?php echo $item['id']; ?>"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="text-end">
                                                    <h6 class="text-primary subtotal" id="subtotal-<?php echo $item['id']; ?>">
                                                        <?php echo formatPrice($item['precio'] * $item['cantidad']); ?>
                                                    </h6>
                                                    <a href="carrito.php?eliminar=<?php echo $item['id']; ?>" 
                                                       onclick="return confirm('¿Estás seguro de eliminar este item del carrito?')"
                                                       class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between">
                                                <button type="submit" name="actualizar_carrito" class="btn btn-outline-primary">
                                                    <i class="fas fa-sync me-1"></i> Actualizar Carrito
                                                </button>
                                                <a href="productos.php" class="btn btn-primary">
                                                    <i class="fas fa-plus me-1"></i> Agregar Más Productos
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Formulario de Pago -->
                        <div class="card mt-4">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-credit-card me-2"></i>Información de Pago y Entrega
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="payment-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Dirección de Entrega</label>
                                                <textarea class="form-control" name="direccion_entrega" rows="3" required placeholder="Ingresa tu dirección completa"><?php echo htmlspecialchars($user['direccion'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Teléfono de Contacto</label>
                                                <input type="tel" class="form-control" name="telefono_contacto" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>" required placeholder="Tu número de teléfono">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Método de Pago</label>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="payment-method text-center" onclick="selectPaymentMethod('yape')">
                                                     <img src="YAPE.png" alt="Yape" style="width: 90px; height: 60px; margin-bottom: 10px;">
                                                    <h6>Yape</h6>
                                                    <small class="text-muted">Pago con Yape</small>
                                                    <input type="radio" name="metodo_pago" value="yape" style="display: none;">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="payment-method text-center" onclick="selectPaymentMethod('plin')">
                                                    <img src="PLIN.png" alt="Plin" style="width: 90px; height: 60px; margin-bottom: 10px;">
                                                    <h6>Plin</h6>
                                                    <small class="text-muted">Pago con Plin</small>
                                                    <input type="radio" name="metodo_pago" value="plin" style="display: none;">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="payment-method text-center" onclick="selectPaymentMethod('tarjeta')">
                                                    <img src="Tarjeta.png" alt="Tarjeta crédito/débito" style="width: 110px; height: 60px; margin-bottom: 10px;">
                                                    <h6>Tarjeta</h6>
                                                    <small class="text-muted">Tarjeta crédito/débito</small>
                                                    <input type="radio" name="metodo_pago" value="tarjeta" style="display: none;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="payment-details" style="display: none;">
                                        <!-- Aquí se mostrarán los detalles específicos de cada método de pago -->
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card summary-card">
                            <div class="card-body">
                                <h5 class="card-title text-white mb-4">Resumen de Compra</h5>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Subtotal:</span>
                                    <span id="cart-subtotal"><?php echo formatPrice($total); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Envío:</span>
                                    <span class="text-success">GRATIS</span>
                                </div>
                                
                                <hr style="border-color: rgba(255,255,255,0.3);">
                                
                                <div class="d-flex justify-content-between mb-4">
                                    <strong>Total:</strong>
                                    <strong id="cart-total"><?php echo formatPrice($total); ?></strong>
                                </div>
                                
                                <button type="submit" form="payment-form" name="realizar_compra" class="btn btn-warning btn-lg w-100" id="pay-button" disabled>
                                    <i class="fas fa-credit-card me-2"></i> Proceder al Pago
                                </button>
                                
                                <div class="mt-3 text-center">
                                    <small class="opacity-8">
                                        <i class="fas fa-lock me-1"></i> Pago seguro y encriptado
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-body">
                                <h6 class="card-title">Información de Entrega</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-check text-success me-2"></i> Entrega previa coordinación</li>
                                    <li><i class="fas fa-check text-success me-2"></i> 2-3 días hábiles</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Horario: 8:00 AM - 4:00 PM</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Sin costo de envío</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3 class="text-muted">Tu carrito está vacío</h3>
                        <p class="text-muted mb-4">Agrega algunos productos para continuar</p>
                        <a href="productos.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-tshirt me-2"></i> Explorar Productos
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedMethod = '';
        
        function selectPaymentMethod(method) {
            selectedMethod = method;
            
            // Remover selección anterior
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Agregar selección actual
            event.currentTarget.classList.add('selected');
            
            // Marcar el radio button
            document.querySelector(`input[value="${method}"]`).checked = true;
            
            // Mostrar detalles del método de pago
            showPaymentDetails(method);
            
            // Habilitar botón de pago
            document.getElementById('pay-button').disabled = false;
        }
        
        function showPaymentDetails(method) {
            const detailsContainer = document.getElementById('payment-details');
            let html = '';
            
            switch(method) {
                case 'yape':
                    html = `
                        <div class="alert alert-info">
                            <h6><i class="fab fa-whatsapp me-2 text-success"></i>Pago con Yape</h6>
                            <p class="mb-2">Realiza el pago al número: <strong>971 673 855</strong></p>
                            <p class="mb-1">Nombre: <strong>Abel V. Torres Mallqui</strong></p>
                            <p class="mb-0">Envía el comprobante a nuestro WhatsApp después de realizar el pago.</p>
                        </div>
                    `;
                    break;
                    
                case 'plin':
                    html = `
                        <div class="alert alert-info">
                            <h6><i class="fas fa-mobile-alt me-2 text-info"></i>Pago con Plin</h6>
                            <p class="mb-2">Realiza el pago al número: <strong>971 673 855</strong></p>
                            <p class="mb-1">Nombre: <strong>Abel V. Torres Mallqui</strong></p>
                            <p class="mb-0">Envía el comprobante a nuestro WhatsApp después de realizar el pago.</p>
                        </div>
                    `;
                    break;
                    
                case 'tarjeta':
                    html = `
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-credit-card me-2 text-warning"></i>Pago con Tarjeta</h6>
                            <p>Serás redirigido a nuestra pasarela de pagos segura.</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Número de Tarjeta</label>
                                        <input type="text" class="form-control" placeholder="1234 5678 9012 3456">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">CVV</label>
                                        <input type="text" class="form-control" placeholder="123">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Expira</label>
                                        <input type="text" class="form-control" placeholder="MM/AA">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
            }
            
            detailsContainer.innerHTML = html;
            detailsContainer.style.display = 'block';
        }
        
        // Actualizar cantidad con AJAX
        document.querySelectorAll('.quantity-input').forEach(input => {
            // Guardar el valor inicial
            let oldValue = input.value;
            
            input.addEventListener('change', function() {
                const itemId = this.getAttribute('data-item-id');
                const newValue = this.value;
                
                // Validar que el valor sea un número positivo
                if (newValue <= 0 || newValue > this.max) {
                    this.value = oldValue;
                    alert('La cantidad debe estar entre 1 y ' + this.max);
                    return;
                }
                
                // Mostrar spinner
                const spinner = document.getElementById('spinner-' + itemId);
                spinner.style.display = 'inline-block';
                
                // Enviar solicitud AJAX
                fetch('carrito.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ajax_update=1&item_id=' + itemId + '&cantidad=' + newValue
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar subtotal del item
                        document.getElementById('subtotal-' + itemId).textContent = data.subtotal;
                        
                        // Actualizar total del carrito
                        document.getElementById('cart-subtotal').textContent = data.total;
                        document.getElementById('cart-total').textContent = data.total;
                        
                        // Actualizar contador del carrito
                        document.getElementById('cart-count').textContent = data.carrito_count;
                        if (document.getElementById('cart-badge')) {
                            document.getElementById('cart-badge').textContent = data.carrito_count;
                        }
                        
                        // Actualizar valor guardado
                        oldValue = newValue;
                        
                        // Mostrar mensaje de éxito
                        showNotification('Cantidad actualizada correctamente', 'success');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar la cantidad');
                    this.value = oldValue; // Revertir al valor anterior
                })
                .finally(() => {
                    // Ocultar spinner
                    spinner.style.display = 'none';
                });
            });
        });
        
        function showNotification(message, type) {
            // Crear notificación
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
            `;
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Agregar al documento
            document.body.appendChild(notification);
            
            // Auto-eliminar después de 3 segundos
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</body>
</html>