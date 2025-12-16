<?php
include 'config.php';
requireLogin();
$user = getCurrentUser($pdo);
$carrito_count = getCarritoCount($pdo, $user['id']);

// Filtros
$programa_filtro = $user['programa_estudio'];
$talla_filtro = $_GET['talla'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta
$sql = "SELECT * FROM productos WHERE programa_estudio = ? AND activo = 1";
$params = [$programa_filtro];

if (!empty($busqueda)) {
    $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if (!empty($talla_filtro)) {
    $sql .= " AND talla = ?";
    $params[] = $talla_filtro;
}

$sql .= " ORDER BY nombre, talla";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
// ... código existente ...

// Función para obtener imagen del producto
// Función para obtener imagen del producto - VERSIÓN MEJORADA
function obtenerImagenProducto($nombreProducto) {
    $nombreLower = strtolower($nombreProducto);
    
    // Detectar tipo específico de pantalón primero
    if (strpos($nombreLower, 'pantalón') !== false || strpos($nombreLower, 'pantalon') !== false) {
        if (strpos($nombreLower, 'blanco') !== false) {
            return 'pantalon-blanco.png';
        } elseif (strpos($nombreLower, 'dama') !== false && strpos($nombreLower, 'azulino') !== false) {
            return 'pantalon-azulino-dama.png';
        } elseif (strpos($nombreLower, 'azulino') !== false) {
            return 'pantalon-azulino.png';
        } else {
            return 'pantalon.png';
        }
    }
    
    // Para otros productos
    $imagenes = [
        'chaleco' => 'chaleco.png',
        'camisa' => 'camisa.png',
        'casco' => 'casco.png',
        'chompa' => 'chompa.png',
        'corbata' => 'corbata.png',
        'blusa' => 'blusa.png',
        'falda' => 'falda.png',
        'pañoleta' => 'pañoleta.png'
    ];
    
    foreach ($imagenes as $palabra => $imagen) {
        if (strpos($nombreLower, $palabra) !== false) {
            return $imagen;
        }
    }
    
    return 'default.png';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Sistema de Ventas de Uniformes</title>
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
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .product-card {
            height: 100%;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px 10px 0 0;
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
        
        .navbar {
            background: rgba(255,255,255,0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .filter-section {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .talla-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
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
    <img src="IESTP-LOGO.png" alt="IESTP Recuay" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover;">
</div>
                    <h5>IESTP RECUAY</h5>
                    <small class="opacity-8">Venta de Uniformes</small>
                </div>
                
                <nav class="nav flex-column mt-4">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="productos.php">
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
                            <i class="fas fa-tshirt me-2"></i>Catálogo de Productos
                        </span>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-3">
                                <i class="fas fa-book me-1"></i> 
                                <?php 
                                $programas = [
                                    'Topografia' => 'Topografía',
                                    'Arquitectura' => 'Arquitectura TI',
                                    'Enfermeria' => 'Enfermería'
                                ];
                                echo $programas[$user['programa_estudio']];
                                ?>
                            </span>
                            <a href="carrito.php" class="btn btn-primary position-relative">
                                <i class="fas fa-shopping-cart me-1"></i> Carrito
                                <?php if ($carrito_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $carrito_count; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </nav>
                
                <!-- Filtros -->
                <div class="filter-section">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-3">Filtrar Productos</h5>
                            <form method="GET" class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Talla</label>
                                    <select name="talla" class="form-select" onchange="this.form.submit()">
                                        <option value="">Todas las tallas</option>
                                        <option value="S" <?php echo $talla_filtro == 'S' ? 'selected' : ''; ?>>S - Small</option>
                                        <option value="M" <?php echo $talla_filtro == 'M' ? 'selected' : ''; ?>>M - Medium</option>
                                        <option value="L" <?php echo $talla_filtro == 'L' ? 'selected' : ''; ?>>L - Large</option>
                                        <option value="Única" <?php echo $talla_filtro == 'Única' ? 'selected' : ''; ?>>Única</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Buscar</label>
                                    <div class="input-group">
                                        <input type="text" name="busqueda" class="form-control" placeholder="Buscar productos..." value="<?php echo htmlspecialchars($busqueda); ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="text-end">
                                <h6 class="text-muted"><?php echo count($productos); ?> productos encontrados</h6>
                                <?php if (!empty($talla_filtro) || !empty($busqueda)): ?>
                                    <a href="productos.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times me-1"></i> Limpiar filtros
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Productos -->
                <div class="row">
                    <?php if (count($productos) > 0): ?>
                        <?php foreach ($productos as $producto): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card product-card">
                              <div class="product-image">
    <img src="<?php echo obtenerImagenProducto($producto['nombre']); ?>" 
         alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
         style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px;">
</div>
                                <div class="card-body position-relative">
                                    <?php if ($producto['stock'] < 10): ?>
                                        <span class="badge bg-warning stock-badge">Stock bajo</span>
                                    <?php endif; ?>
                                    
                                    <h6 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                    <p class="card-text small text-muted mb-2">
                                        <?php echo htmlspecialchars($producto['descripcion']); ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="badge bg-primary talla-badge">
                                            Talla: <?php echo $producto['talla']; ?>
                                        </span>
                                        <span class="badge bg-secondary">
                                            Stock: <?php echo $producto['stock']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="text-success mb-0"><?php echo formatPrice($producto['precio']); ?></h5>
                                        <button class="btn btn-primary btn-sm add-to-cart" 
                                                data-product-id="<?php echo $producto['id']; ?>"
                                                <?php echo $producto['stock'] == 0 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-cart-plus me-1"></i>
                                            <?php echo $producto['stock'] > 0 ? 'Agregar' : 'Sin Stock'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No se encontraron productos</h4>
                                <p class="text-muted">Intenta con otros filtros de búsqueda</p>
                                <a href="productos.php" class="btn btn-primary">Ver todos los productos</a>
                            </div>
                        </div>
                    <?php endif; ?>
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
                
                if (this.disabled) return;
                
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
                        // Actualizar contador del carrito en el navbar
                        updateCartCount(1);
                        showNotification('Producto agregado al carrito', 'success');
                    } else {
                        showNotification(data.message || 'Error al agregar producto', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Agregado al Carrito', 'error');
                });
            });
        });
        
        function updateCartCount(increment) {
            const cartBadge = document.querySelector('.badge-cart');
            const navbarBadge = document.querySelector('.navbar .badge');
            
            if (cartBadge) {
                cartBadge.textContent = parseInt(cartBadge.textContent) + increment;
            }
            
            if (navbarBadge) {
                navbarBadge.textContent = parseInt(navbarBadge.textContent) + increment;
            }
        }
        
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