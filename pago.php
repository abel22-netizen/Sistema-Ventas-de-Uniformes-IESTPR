<?php
include 'config.php';
requireLogin();
$user = getCurrentUser($pdo);

$venta_id = $_GET['venta_id'] ?? '';

if (empty($venta_id)) {
    header("Location: carrito.php");
    exit();
}

// Verificar que la venta pertenece al usuario
$stmt = $pdo->prepare("SELECT * FROM ventas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$venta_id, $user['id']]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    header("Location: carrito.php");
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

$carrito_count = getCarritoCount($pdo, $user['id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Pago - Sistema de Ventas de Uniformes</title>
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
        
        .success-card {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .payment-info {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
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
        
        .qr-code {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }
        
        .step.active .step-number {
            background: var(--primary);
            color: white;
        }
        
        .step.completed .step-number {
            background: #27ae60;
            color: white;
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
                    <a class="nav-link" href="carrito.php">
                        <i class="fas fa-shopping-cart"></i> Mi Carrito
                        <?php if ($carrito_count > 0): ?>
                            <span class="badge-cart"><?php echo $carrito_count; ?></span>
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
                            <i class="fas fa-credit-card me-2"></i>Procesar Pago
                        </span>
                        <div class="d-flex align-items-center">
                            <a href="ventas.php" class="btn btn-outline-primary">
                                <i class="fas fa-receipt me-1"></i> Ver Mis Compras
                            </a>
                        </div>
                    </div>
                </nav>
                
                <!-- Indicador de Pasos -->
                <div class="step-indicator">
                    <div class="step completed">
                        <div class="step-number">1</div>
                        <div>Carrito</div>
                    </div>
                    <div class="step completed">
                        <div class="step-number">2</div>
                        <div>Información</div>
                    </div>
                    <div class="step active">
                        <div class="step-number">3</div>
                        <div>Pago</div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div>Confirmación</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2 text-primary"></i>Instrucciones de Pago
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($venta['metodo_pago'] == 'yape' || $venta['metodo_pago'] == 'plin'): ?>
                                    <div class="alert alert-info">
                                        <h5>
                                            <i class="fab fa-<?php echo $venta['metodo_pago'] == 'yape' ? 'whatsapp' : 'mobile-alt'; ?> me-2 text-<?php echo $venta['metodo_pago'] == 'yape' ? 'success' : 'info'; ?>"></i>
                                            Pago con <?php echo ucfirst($venta['metodo_pago']); ?>
                                        </h5>
                                        <p>Sigue estos pasos para completar tu pago:</p>
                                        <ol>
                                            <li>Abre tu aplicación de <?php echo $venta['metodo_pago'] == 'yape' ? 'Yape' : 'Plin'; ?></li>
                                            <li>Realiza el pago al número: <strong>971 673 855</strong></li>
                                            <li>Nombre: <strong>Abel Valentin Torres Mallqui</strong></li>
                                            <li>Monto: <strong><?php echo formatPrice($venta['total']); ?></strong></li>
                                            <li>Toma una captura de pantalla del comprobante</li>
                                            <li>Envía el comprobante a nuestro WhatsApp: <strong>971 673 855</strong></li>
                                        </ol>
                                    </div>
                                    
                                    <div class="qr-code">
    <img src="QR YAPE.png" alt="QR Yape" style="width: 200px; height: 200px; border-radius: 10px;">
    <p class="text-muted mt-3">Escanea el código QR con Yape</p>
    <p class="small text-muted">Número: 971 673 855</p>
    <p class="small text-muted">Nombre de: Abel Valentin Torres Mallqui </p>
</div>
                                    
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Importante</h6>
                                        <p class="mb-0">
                                            Tu pedido será procesado una vez que verifiquemos tu pago. 
                                            Recibirás una confirmación por correo electrónico.
                                        </p>
                                    </div>
                                    
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <h5><i class="fas fa-credit-card me-2 text-warning"></i>Pago con Tarjeta</h5>
                                        <p>Serás redirigido a nuestra pasarela de pagos segura.</p>
                                        <div class="text-center">
                                            <button class="btn btn-primary btn-lg" onclick="processCardPayment()">
                                                <i class="fas fa-lock me-2"></i>Ir a Pasarela de Pago
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-4">
                                    <h6>¿Problemas con el pago?</h6>
                                    <p>Si tienes alguna dificultad, contáctanos:</p>
                                    <ul>
                                        <li><i class="fab fa-whatsapp me-2 text-success"></i> WhatsApp: 971 673 855</li>
                                        <li><i class="fas fa-phone me-2 text-primary"></i> Teléfono: (01) 123-4567</li>
                                        <li><i class="fas fa-envelope me-2 text-info"></i> Email: ventas@iestprecuay.edu.pe</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card payment-info">
                            <div class="card-body">
                                <h5 class="card-title text-white mb-4">Resumen de Pedido</h5>
                                
                                <div class="mb-3">
                                    <small class="opacity-8">Número de Pedido</small>
                                    <h6>#<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></h6>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="opacity-8">Fecha</small>
                                    <h6><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></h6>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="opacity-8">Método de Pago</small>
                                    <h6>
                                        <i class="fas fa-<?php 
                                            echo $venta['metodo_pago'] == 'yape' ? 'whatsapp' : 
                                                 ($venta['metodo_pago'] == 'plin' ? 'mobile-alt' : 'credit-card'); 
                                        ?> me-2"></i>
                                        <?php echo ucfirst($venta['metodo_pago']); ?>
                                    </h6>
                                </div>
                                
                                <hr style="border-color: rgba(255,255,255,0.3);">
                                
                                <div class="mb-3">
                                    <small class="opacity-8">Productos</small>
                                    <?php foreach ($detalles as $detalle): ?>
                                    <div class="d-flex justify-content-between small">
                                        <span><?php echo $detalle['nombre']; ?> (x<?php echo $detalle['cantidad']; ?>)</span>
                                        <span><?php echo formatPrice($detalle['subtotal']); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <hr style="border-color: rgba(255,255,255,0.3);">
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span><?php echo formatPrice($venta['total']); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Envío:</span>
                                    <span class="text-success">GRATIS</span>
                                </div>
                                
                                <hr style="border-color: rgba(255,255,255,0.3);">
                                
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong><?php echo formatPrice($venta['total']); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-body text-center">
                                <h6>¿Ya realizaste el pago?</h6>
                                <p class="small text-muted">Marca tu pedido como pagado</p>
                                <button class="btn btn-success w-100" onclick="markAsPaid()">
                                    <i class="fas fa-check me-2"></i> Marcar como Pagado
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processCardPayment() {
            // Simulación de procesamiento de tarjeta
            alert('Serías redirigido a una pasarela de pagos segura. Esta es una simulación.');
            markAsPaid();
        }
        
        function markAsPaid() {
            // Simulación de marcado como pagado
            alert('¡Gracias! Tu pago ha sido registrado. Te contactaremos para coordinar la entrega.');
            window.location.href = 'ventas.php?compra_exitosa=1';
        }
    </script>
</body>
</html>