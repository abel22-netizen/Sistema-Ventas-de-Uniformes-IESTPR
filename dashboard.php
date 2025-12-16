<?php
include 'config.php';
requireLogin();
$user = getCurrentUser($pdo);
$carrito_count = getCarritoCount($pdo, $user['id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Ventas de Uniformes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2980b9;
            --success: #27ae60;
            --info: #17a2b8;
            --warning: #f39c12;
            --danger: #e74c3c;
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
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .stat-card {
            text-align: center;
            padding: 25px 15px;
            border-radius: 15px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transform: rotate(30deg);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .bg-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)) !important; }
        .bg-success { background: linear-gradient(135deg, var(--success), #2ecc71) !important; }
        .bg-info { background: linear-gradient(135deg, var(--info), #6f42c1) !important; }
        .bg-warning { background: linear-gradient(135deg, var(--warning), #fd7e14) !important; }
        
        .navbar {
            background: rgba(255,255,255,0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 25px;
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
        
        .programa-info {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .user-welcome {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
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
    <img src="IESTP-LOGO.png" alt="IESTP Recuay" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover;">
</div>
                    <h5>IESTP RECUAY</h5>
                    <small class="opacity-8">Venta de Uniformes</small>
                </div>
                
                <nav class="nav flex-column mt-4">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <?php if (isAdmin($pdo)): ?>
<a class="nav-link" href="admin.php">
    <i class="fas fa-cog"></i> Administración
</a>
<?php endif; ?>
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
                <!-- User Welcome -->
                <div class="user-welcome">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-2">¡Bienvenido de vuelta, <?php echo htmlspecialchars($user['nombre']); ?>! 👋</h4>
                            <p class="mb-0 opacity-8">
                                <i class="fas fa-book me-1"></i>
                                <?php 
                                $programas = [
                                    'Topografia' => 'Topografía Superficial y Minera',
                                    'Arquitectura' => 'Arquitectura de Plataformas y Servicios de TI',
                                    'Enfermeria' => 'Enfermería Técnica'
                                ];
                                echo $programas[$user['programa_estudio']];
                                ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="dropdown">
                                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-1"></i> Mi Cuenta
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                                    <li><a class="dropdown-item" href="carrito.php"><i class="fas fa-shopping-cart me-2"></i>Carrito</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body stat-card">
                                <i class="fas fa-tshirt"></i>
                                <h3>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE programa_estudio = ? AND activo = 1");
                                    $stmt->execute([$user['programa_estudio']]);
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h3>
                                <p class="mb-0">Productos Disponibles</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body stat-card">
                                <i class="fas fa-shopping-cart"></i>
                                <h3>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE usuario_id = ?");
                                    $stmt->execute([$user['id']]);
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h3>
                                <p class="mb-0">Mis Compras</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body stat-card">
                                <i class="fas fa-users"></i>
                                <h3>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE programa_estudio = ?");
                                    $stmt->execute([$user['programa_estudio']]);
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h3>
                                <p class="mb-0">Compañeros</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body stat-card">
                                <i class="fas fa-star"></i>
                                <h3>4.8</h3>
                                <p class="mb-0">Calificación</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones Rápidas -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-white border-0">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2 text-warning"></i>Acciones Rápidas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <a href="productos.php" class="text-decoration-none">
                                            <div class="feature-icon mx-auto">
                                                <i class="fas fa-tshirt fa-2x text-primary"></i>
                                            </div>
                                            <h6>Ver Productos</h6>
                                            <small class="text-muted">Explora nuestro catálogo</small>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="carrito.php" class="text-decoration-none">
                                            <div class="feature-icon mx-auto">
                                                <i class="fas fa-shopping-cart fa-2x text-success"></i>
                                            </div>
                                            <h6>Mi Carrito</h6>
                                            <small class="text-muted">Revisa tus items</small>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="ventas.php" class="text-decoration-none">
                                            <div class="feature-icon mx-auto">
                                                <i class="fas fa-receipt fa-2x text-info"></i>
                                            </div>
                                            <h6>Mis Compras</h6>
                                            <small class="text-muted">Historial de pedidos</small>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="perfil.php" class="text-decoration-none">
                                            <div class="feature-icon mx-auto">
                                                <i class="fas fa-user-cog fa-2x text-warning"></i>
                                            </div>
                                            <h6>Mi Perfil</h6>
                                            <small class="text-muted">Configura tu cuenta</small>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Productos Recomendados -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-white border-0">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-fire me-2 text-danger"></i>Productos Recomendados para Ti
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT * FROM productos WHERE programa_estudio = ? AND activo = 1 LIMIT 4");
                                    $stmt->execute([$user['programa_estudio']]);
                                    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($productos as $producto):
                                    ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 border">
                                            <div class="card-body text-center p-4">
                                                <div class="text-primary mb-3">
                                                    <i class="fas fa-tshirt fa-3x"></i>
                                                </div>
                                                <h6 class="card-title fw-bold"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                                <p class="text-muted small mb-2">Talla: <?php echo $producto['talla']; ?></p>
                                                <h5 class="text-success fw-bold"><?php echo formatPrice($producto['precio']); ?></h5>
                                                <div class="mt-3">
                                                    <button class="btn btn-primary btn-sm add-to-cart" data-product-id="<?php echo $producto['id']; ?>">
                                                        <i class="fas fa-cart-plus me-1"></i> Agregar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información del Programa -->
                <div class="row">
                    <div class="col-12">
                        <div class="programa-info">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle me-2"></i>Información de tu Programa - 
                                <?php echo $programas[$user['programa_estudio']]; ?>
                            </h5>
                            <?php if ($user['programa_estudio'] == 'Topografia'): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Uniforme requerido:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check me-2"></i>Chaleco rojo</li>
                                            <li><i class="fas fa-check me-2"></i>Camisa blanca</li>
                                            <li><i class="fas fa-check me-2"></i>Pantalón de uniforme color Azulino</li>
                                            <li><i class="fas fa-check me-2"></i>Casco blanco</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Características:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-shield-alt me-2"></i>Material de seguridad</li>
                                            <li><i class="fas fa-compress-arrows-alt me-2"></i>Tallas S, M, L</li>
                                            <li><i class="fas fa-award me-2"></i>Calidad certificada</li>
                                        </ul>
                                    </div>
                                </div>
                            <?php elseif ($user['programa_estudio'] == 'Arquitectura'): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Uniforme requerido:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check me-2"></i>Camisa manga larga blanca</li>
                                            <li><i class="fas fa-check me-2"></i>Chompa color Azulino con logo</li>
                                            <li><i class="fas fa-check me-2"></i>Corbata color Azulino</li>
                                            <li><i class="fas fa-check me-2"></i>Pantalón de uniforme color Azulino</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Características:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-user-tie me-2"></i>Estilo profesional</li>
                                            <li><i class="fas fa-tshirt me-2"></i>Tallas S, M, L</li>
                                            <li><i class="fas fa-gem me-2"></i>Alta calidad</li>
                                        </ul>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Uniforme requerido:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check me-2"></i>Blusa color blanco</li>
                                            <li><i class="fas fa-check me-2"></i>Pantalón color blanco</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Características:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-plus-circle me-2"></i>Material médico</li>
                                            <li><i class="fas fa-tshirt me-2"></i>Tallas S, M, L</li>
                                            <li><i class="fas fa-hand-sparkles me-2"></i>Fácil esterilización</li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Agregar al carrito
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                
                fetch('ajax_agregar_carrito.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'producto_id=' + productId + '&cantidad=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar contador del carrito
                        const cartBadge = document.querySelector('.badge-cart');
                        if (cartBadge) {
                            cartBadge.textContent = parseInt(cartBadge.textContent) + 1;
                        } else {
                            // Crear badge si no existe
                            const cartLink = document.querySelector('a[href="carrito.php"]');
                            const badge = document.createElement('span');
                            badge.className = 'badge-cart';
                            badge.textContent = '1';
                            cartLink.appendChild(badge);
                        }
                        
                        // Mostrar notificación
                        showNotification('Producto agregado al carrito', 'success');
                    } else {
                        showNotification('Error al agregar producto', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Agregado al Carrito', 'error');
                });
            });
        });
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</body>
</html>