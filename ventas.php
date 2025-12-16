<?php
include 'config.php';
requireLogin();
$user = getCurrentUser($pdo);

// Obtener historial de compras
$stmt = $pdo->prepare("
    SELECT v.*, 
           COUNT(dv.id) as items,
           DATE_FORMAT(v.fecha_venta, '%d/%m/%Y %H:%i') as fecha_formateada
    FROM ventas v 
    LEFT JOIN detalle_venta dv ON v.id = dv.venta_id 
    WHERE v.usuario_id = ? 
    GROUP BY v.id 
    ORDER BY v.fecha_venta DESC
");
$stmt->execute([$user['id']]);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$carrito_count = getCarritoCount($pdo, $user['id']);

// Mostrar mensaje de compra exitosa
$compra_exitosa = isset($_GET['compra_exitosa']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Compras - Sistema de Ventas de Uniformes</title>
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
        
        .sale-card {
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .sale-card:hover {
            transform: translateX(5px);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
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
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #21618c);
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
                    <a class="nav-link active" href="ventas.php">
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
                            <i class="fas fa-receipt me-2"></i>Historial de Compras
                        </span>
                        <div class="d-flex align-items-center">
                            <a href="productos.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva Compra
                            </a>
                        </div>
                    </div>
                </nav>
                
                <?php if ($compra_exitosa): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>¡Compra realizada exitosamente!</strong> Tu pedido ha sido procesado y será entregado en el instituto o a domicilio.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (count($ventas) > 0): ?>
                    <div class="row">
                        <?php foreach ($ventas as $venta): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card sale-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <h6 class="card-title mb-1">Compra #<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></h6>
                                            <p class="text-muted small mb-2">
                                                <i class="fas fa-calendar me-1"></i> <?php echo $venta['fecha_formateada']; ?>
                                            </p>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-<?php 
                                                    echo $venta['estado'] == 'completado' ? 'success' : 
                                                         ($venta['estado'] == 'pendiente' ? 'warning' : 'danger'); 
                                                ?> status-badge me-2">
                                                    <?php echo ucfirst($venta['estado']); ?>
                                                </span>
                                                <span class="badge bg-primary status-badge">
                                                    <i class="fas fa-box me-1"></i> <?php echo $venta['items']; ?> items
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-4 text-end">
                                            <h5 class="text-success mb-0"><?php echo formatPrice($venta['total']); ?></h5>
                                            <button class="btn btn-outline-primary btn-sm mt-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detalleModal"
                                                    onclick="cargarDetalleVenta(<?php echo $venta['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i> Ver Detalle
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <h3 class="text-muted">Aún no tienes compras</h3>
                            <p class="text-muted mb-4">Realiza tu primera compra en nuestro catálogo de productos</p>
                            <a href="productos.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-tshirt me-2"></i> Explorar Productos
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para detalle de venta -->
    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleVentaContent">
                    <!-- Contenido cargado por JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cargarDetalleVenta(ventaId) {
            fetch('ajax_detalle_venta.php?venta_id=' + ventaId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detalleVentaContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('detalleVentaContent').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar el detalle</div>';
                });
        }
    </script>
</body>
</html>