<?php
include 'config.php';
requireAdmin($pdo);
$user = getCurrentUser($pdo);

// ==================== FUNCIONALIDADES DE ADMINISTRADOR ====================

// 1. FILTROS AVANZADOS PARA VENTAS
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_fecha = $_GET['fecha'] ?? 'todos';
$filtro_programa = $_GET['programa'] ?? 'todos';
$buscar = $_GET['buscar'] ?? '';

// 2. EXPORTAR A EXCEL
if (isset($_GET['exportar']) && $_GET['exportar'] == 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ventas_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    fputcsv($output, ['ID', 'Cliente', 'Email', 'Programa', 'Total', 'Estado', 'Fecha', 'Método Pago']);
    
    $stmt = $pdo->query("
        SELECT v.*, u.nombre, u.email, u.programa_estudio 
        FROM ventas v 
        JOIN usuarios u ON v.usuario_id = u.id 
        ORDER BY v.fecha_venta DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['nombre'],
            $row['email'],
            $row['programa_estudio'],
            $row['total'],
            $row['estado'],
            $row['fecha_venta'],
            $row['metodo_pago']
        ]);
    }
    
    fclose($output);
    exit();
}

// 3. EXPORTAR REPORTE MENSUAL A PDF
if (isset($_GET['exportar_mensual']) && $_GET['exportar_mensual'] == 'pdf') {
    // Aquí iría la lógica para generar PDF (necesitarías una librería como TCPDF o Dompdf)
    // Por ahora solo mostramos un mensaje
    header('Location: admin.php?mensaje=reporte_mensual_generado');
    exit();
}

// 4. GENERAR REPORTE DE INVENTARIO
if (isset($_GET['reporte_inventario'])) {
    // Esto activaría la descarga del reporte de inventario
    // Por ahora redirigimos con un parámetro
    header('Location: admin.php?mensaje=inventario_generado&seccion=reportes');
    exit();
}

// 5. CAMBIO MASIVO DE ESTADO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambio_masivo'])) {
    $ventas_seleccionadas = $_POST['ventas_seleccionadas'] ?? [];
    $nuevo_estado_masivo = $_POST['nuevo_estado_masivo'];
    
    if (!empty($ventas_seleccionadas)) {
        $placeholders = str_repeat('?,', count($ventas_seleccionadas) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE ventas SET estado = ? WHERE id IN ($placeholders)");
        $params = array_merge([$nuevo_estado_masivo], $ventas_seleccionadas);
        
        if ($stmt->execute($params)) {
            $success = count($ventas_seleccionadas) . " ventas actualizadas correctamente.";
        }
    }
}

// 6. AGREGAR/EDITAR PRODUCTOS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_producto'])) {
    $producto_id = $_POST['producto_id'] ?? null;
    $nombre = $_POST['nombre_producto'];
    $descripcion = $_POST['descripcion_producto'];
    $programa = $_POST['programa_producto'];
    $precio = $_POST['precio_producto'];
    $talla = $_POST['talla_producto'];
    $stock = $_POST['stock_producto'];
    
    if ($producto_id) {
        // Actualizar
        $stmt = $pdo->prepare("UPDATE productos SET nombre=?, descripcion=?, programa_estudio=?, precio=?, talla=?, stock=? WHERE id=?");
        $stmt->execute([$nombre, $descripcion, $programa, $precio, $talla, $stock, $producto_id]);
        $success = "Producto actualizado correctamente.";
    } else {
        // Crear nuevo
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, programa_estudio, precio, talla, stock, activo) VALUES (?,?,?,?,?,?,1)");
        $stmt->execute([$nombre, $descripcion, $programa, $precio, $talla, $stock]);
        $success = "Producto creado correctamente.";
    }
}

// 7. ELIMINAR/DESACTIVAR PRODUCTO
if (isset($_GET['desactivar_producto'])) {
    $stmt = $pdo->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
    $stmt->execute([$_GET['desactivar_producto']]);
    $success = "Producto desactivado correctamente.";
}

// 8. PROCESAR CAMBIO DE ESTADO INDIVIDUAL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_estado'])) {
    $venta_id = $_POST['venta_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $stmt = $pdo->prepare("UPDATE ventas SET estado = ? WHERE id = ?");
    if ($stmt->execute([$nuevo_estado, $venta_id])) {
        $success = "Estado de venta actualizado correctamente.";
    } else {
        $error = "Error al actualizar el estado de la venta.";
    }
}

// CONSTRUIR CONSULTA CON FILTROS
$sql = "
    SELECT v.*, u.nombre as cliente_nombre, u.email as cliente_email, u.programa_estudio,
           COUNT(dv.id) as total_items,
           DATE_FORMAT(v.fecha_venta, '%d/%m/%Y %H:%i') as fecha_formateada
    FROM ventas v 
    JOIN usuarios u ON v.usuario_id = u.id 
    LEFT JOIN detalle_venta dv ON v.id = dv.venta_id 
    WHERE 1=1
";

$params = [];

if ($filtro_estado != 'todos') {
    $sql .= " AND v.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_programa != 'todos') {
    $sql .= " AND u.programa_estudio = ?";
    $params[] = $filtro_programa;
}

if ($filtro_fecha != 'todos') {
    switch($filtro_fecha) {
        case 'hoy':
            $sql .= " AND DATE(v.fecha_venta) = CURDATE()";
            break;
        case 'semana':
            $sql .= " AND YEARWEEK(v.fecha_venta) = YEARWEEK(NOW())";
            break;
        case 'mes':
            $sql .= " AND MONTH(v.fecha_venta) = MONTH(NOW()) AND YEAR(v.fecha_venta) = YEAR(NOW())";
            break;
    }
}

if (!empty($buscar)) {
    $sql .= " AND (u.nombre LIKE ? OR u.email LIKE ? OR v.id LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$sql .= " GROUP BY v.id ORDER BY v.fecha_venta DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ESTADÍSTICAS MEJORADAS
$total_ventas = $pdo->query("SELECT COUNT(*) FROM ventas")->fetchColumn();
$ventas_pendientes = $pdo->query("SELECT COUNT(*) FROM ventas WHERE estado = 'pendiente'")->fetchColumn();
$ventas_completadas = $pdo->query("SELECT COUNT(*) FROM ventas WHERE estado = 'completado'")->fetchColumn();
$ventas_entregadas = $pdo->query("SELECT COUNT(*) FROM ventas WHERE estado = 'entregado'")->fetchColumn();
$ingresos_totales = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ventas WHERE estado IN ('completado', 'entregado')")->fetchColumn();
$ingresos_pendientes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ventas WHERE estado = 'pendiente'")->fetchColumn();

// Top productos más vendidos
$stmt = $pdo->query("
    SELECT p.nombre, p.programa_estudio, SUM(dv.cantidad) as total_vendido, SUM(dv.subtotal) as ingresos
    FROM detalle_venta dv
    JOIN productos p ON dv.producto_id = p.id
    GROUP BY p.id
    ORDER BY total_vendido DESC
    LIMIT 5
");
$top_productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ventas por programa
$stmt = $pdo->query("
    SELECT u.programa_estudio, COUNT(v.id) as total, SUM(v.total) as ingresos
    FROM ventas v
    JOIN usuarios u ON v.usuario_id = u.id
    GROUP BY u.programa_estudio
");
$ventas_programa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los productos para gestión
$stmt = $pdo->query("SELECT * FROM productos ORDER BY programa_estudio, nombre");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ESTADÍSTICAS MENSUALES PARA GRÁFICOS
$mes_actual = date('m');
$anio_actual = date('Y');

// Ventas por día del mes actual
$stmt = $pdo->prepare("
    SELECT DAY(fecha_venta) as dia, COUNT(*) as cantidad, SUM(total) as ingresos
    FROM ventas 
    WHERE MONTH(fecha_venta) = ? AND YEAR(fecha_venta) = ? 
    AND estado IN ('completado', 'entregado')
    GROUP BY DAY(fecha_venta)
    ORDER BY dia
");
$stmt->execute([$mes_actual, $anio_actual]);
$ventas_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Productos con stock bajo
$stmt = $pdo->query("
    SELECT nombre, programa_estudio, stock, precio
    FROM productos 
    WHERE stock <= 10 AND activo = 1
    ORDER BY stock ASC
    LIMIT 10
");
$stock_bajo = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas del mes
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_ventas_mes,
        COALESCE(SUM(total), 0) as ingresos_mes,
        AVG(total) as promedio_venta
    FROM ventas 
    WHERE MONTH(fecha_venta) = ? AND YEAR(fecha_venta) = ? 
    AND estado IN ('completado', 'entregado')
");
$stmt->execute([$mes_actual, $anio_actual]);
$estadisticas_mes = $stmt->fetch(PDO::FETCH_ASSOC);

// Ventas por estado este mes
$stmt = $pdo->prepare("
    SELECT estado, COUNT(*) as cantidad
    FROM ventas 
    WHERE MONTH(fecha_venta) = ? AND YEAR(fecha_venta) = ?
    GROUP BY estado
");
$stmt->execute([$mes_actual, $anio_actual]);
$ventas_por_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar datos para gráficos
$labels_dias = [];
$datos_ventas = [];
$datos_ingresos = [];

for ($i = 1; $i <= 31; $i++) {
    $labels_dias[] = $i;
    $datos_ventas[$i] = 0;
    $datos_ingresos[$i] = 0;
}

foreach ($ventas_por_dia as $venta) {
    $dia = (int)$venta['dia'];
    $datos_ventas[$dia] = (int)$venta['cantidad'];
    $datos_ingresos[$dia] = (float)$venta['ingresos'];
}

// Datos para gráfico de estados
$estados_labels = [];
$estados_data = [];
foreach ($ventas_por_estado as $item) {
    $estados_labels[] = ucfirst($item['estado']);
    $estados_data[] = (int)$item['cantidad'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - IESTP Recuay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2980b9;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        /* NAVBAR HORIZONTAL SUPERIOR */
        .admin-navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand-admin {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        
        .nav-link-admin {
            color: rgba(255,255,255,0.9) !important;
            padding: 10px 15px !important;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-link-admin:hover, .nav-link-admin.active {
            color: white !important;
            background: rgba(255,255,255,0.15);
        }
        
        .main-content {
            padding: 20px;
            margin-top: 0;
        }

        /* Agrega esto al CSS */
        .navbar-brand-admin img {
            height: 40px;
            width: auto;
            border-radius: 4px;
            object-fit: contain;
        }

        /* CARDS DE ESTADÍSTICAS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .stat-primary { border-left: 4px solid var(--primary); }
        .stat-warning { border-left: 4px solid var(--warning); }
        .stat-info { border-left: 4px solid var(--info); }
        .stat-success { border-left: 4px solid var(--success); }
        .stat-danger { border-left: 4px solid var(--danger); }
        
        .stat-card h3 {
            font-size: 1.8rem;
            margin: 10px 0;
            font-weight: bold;
        }
        
        .stat-card p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }

        /* CARD GENERAL */
        .admin-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            border: none;
        }
        
        .admin-card-header {
            background: transparent;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            margin-bottom: 15px;
        }

        /* TABLA */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        
        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
        }

        /* BADGES */
        .badge-custom {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* BOTONES */
        .btn-admin {
            border-radius: 8px;
            font-weight: 500;
            padding: 8px 16px;
        }

        /* FILTROS */
        .filter-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        
        /* TABS */
        .nav-tabs-admin {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .nav-tabs-admin .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
        }
        
        .nav-tabs-admin .nav-link.active {
            color: var(--primary);
            background: white;
            border-bottom: 3px solid var(--primary);
        }
        
        /* GRÁFICOS */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .mini-chart {
            height: 200px;
        }
        
        /* CARD DE REPORTES */
        .report-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            height: 100%;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        
        .report-card .card-body {
            padding: 30px 20px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .nav-tabs-admin .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .chart-container {
                height: 250px;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .chart-container {
                height: 200px;
            }
        }

        /* SCROLL PERSONALIZADO */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary);
        }
        
        /* MENSAJES DE ÉXITO */
        .mensaje-reporte {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        /* QUITAR SUBRAYADOS DEL NAVBAR SUPERIOR */
.navbar-nav .nav-link-admin,
.navbar-nav .nav-link-admin:hover,
.navbar-nav .nav-link-admin.active,
.navbar-nav .nav-link-admin:focus {
    text-decoration: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    outline: none !important;
}

/* También quita el subrayado del dropdown */
.nav-link-admin.dropdown-toggle::after {
    display: inline-block;
    margin-left: 0.255em;
    vertical-align: 0.255em;
    content: "";
    border-top: 0.3em solid;
    border-right: 0.3em solid transparent;
    border-bottom: 0;
    border-left: 0.3em solid transparent;
}

/* Asegurar que no haya subrayados en hover */
.navbar-nav .nav-link-admin:hover {
    background: rgba(255,255,255,0.15);
    text-decoration: none !important;
}
/* QUITAR SUBRAYADO DEL LOGO/TÍTULO */
.navbar-brand-admin,
.navbar-brand-admin:hover,
.navbar-brand-admin:focus {
    text-decoration: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    outline: none !important;
}

/* También asegúrate de que la imagen no tenga bordes */
.navbar-brand-admin img {
    text-decoration: none !important;
    border: none !important;
    outline: none !important;
}
    </style>
</head>
<body>
    <!-- NAVBAR HORIZONTAL SUPERIOR -->
    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand-admin" href="admin.php">
                <img src="IESTP-LOGO.png" alt="IESTP Logo" style="height: 70px; margin-right: 10px;">
                IESTP Recuay - Panel Admin
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link-admin active" href="#ventas" data-tab="ventas">
                            <i class="fas fa-shopping-cart me-1"></i> Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-admin" href="#productos" data-tab="productos">
                            <i class="fas fa-tshirt me-1"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-admin" href="#estadisticas" data-tab="estadisticas">
                            <i class="fas fa-chart-line me-1"></i> Estadísticas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-admin" href="#reportes" data-tab="reportes">
                            <i class="fas fa-file-alt me-1"></i> Reportes
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link-admin dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo $user['nombre']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="dashboard.php">
                                    <i class="fas fa-arrow-left me-2"></i> Volver al Dashboard
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="main-content">
        <!-- ESTADÍSTICAS PRINCIPALES (SE HA QUITADO EL CUADRO "COMPLETADAS") -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <i class="fas fa-shopping-cart text-primary"></i>
                <h3><?php echo $total_ventas; ?></h3>
                <p>Total Ventas</p>
            </div>
            <div class="stat-card stat-warning">
                <i class="fas fa-clock text-warning"></i>
                <h3><?php echo $ventas_pendientes; ?></h3>
                <p>Pendientes</p>
            </div>
            <div class="stat-card stat-info">
                <i class="fas fa-box text-info"></i>
                <h3><?php echo $ventas_entregadas; ?></h3>
                <p>Entregadas</p>
            </div>
            <div class="stat-card stat-success">
                <i class="fas fa-money-bill-wave text-success"></i>
                <h3>S/ <?php echo number_format($ingresos_totales, 2); ?></h3>
                <p>Ingresos Totales</p>
            </div>
            <div class="stat-card stat-danger">
                <i class="fas fa-chart-line text-danger"></i>
                <h3>S/ <?php echo number_format($ingresos_pendientes, 2); ?></h3>
                <p>Ingresos Pendientes</p>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['mensaje'])): ?>
            <?php if ($_GET['mensaje'] == 'reporte_mensual_generado'): ?>
                <div class="mensaje-reporte alert-dismissible fade show">
                    <i class="fas fa-file-pdf text-danger me-2"></i>
                    <strong>¡Reporte mensual generado!</strong> El reporte del mes <?php echo date('F Y'); ?> ha sido generado exitosamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['mensaje'] == 'inventario_generado'): ?>
                <div class="mensaje-reporte alert-dismissible fade show">
                    <i class="fas fa-clipboard-check text-success me-2"></i>
                    <strong>¡Reporte de inventario generado!</strong> Puedes descargarlo desde el panel de reportes.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- TABS DE CONTENIDO -->
        <ul class="nav nav-tabs nav-tabs-admin" id="adminTabs">
            <li class="nav-item">
                <a class="nav-link active" href="#ventas-tab" data-bs-toggle="tab">
                    <i class="fas fa-shopping-cart me-2"></i>Gestión de Ventas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#productos-tab" data-bs-toggle="tab">
                    <i class="fas fa-tshirt me-2"></i>Gestión de Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#estadisticas-tab" data-bs-toggle="tab">
                    <i class="fas fa-chart-line me-2"></i>Estadísticas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#reportes-tab" data-bs-toggle="tab">
                    <i class="fas fa-file-alt me-2"></i>Reportes
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB: GESTIÓN DE VENTAS -->
            <div class="tab-pane fade show active" id="ventas-tab">
                <!-- FILTROS AVANZADOS -->
                <div class="filter-container">
                    <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtros Avanzados</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="todos">Todos los estados</option>
                                <option value="pendiente" <?php echo $filtro_estado=='pendiente'?'selected':''; ?>>Pendiente</option>
                                <option value="completado" <?php echo $filtro_estado=='completado'?'selected':''; ?>>Completado</option>
                                <option value="entregado" <?php echo $filtro_estado=='entregado'?'selected':''; ?>>Entregado</option>
                                <option value="cancelado" <?php echo $filtro_estado=='cancelado'?'selected':''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="fecha" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="todos">Todas las fechas</option>
                                <option value="hoy" <?php echo $filtro_fecha=='hoy'?'selected':''; ?>>Hoy</option>
                                <option value="semana" <?php echo $filtro_fecha=='semana'?'selected':''; ?>>Esta semana</option>
                                <option value="mes" <?php echo $filtro_fecha=='mes'?'selected':''; ?>>Este mes</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="programa" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="todos">Todos los programas</option>
                                <option value="Topografia" <?php echo $filtro_programa=='Topografia'?'selected':''; ?>>Topografía</option>
                                <option value="Arquitectura" <?php echo $filtro_programa=='Arquitectura'?'selected':''; ?>>Arquitectura TI</option>
                                <option value="Enfermeria" <?php echo $filtro_programa=='Enfermeria'?'selected':''; ?>>Enfermería</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="buscar" class="form-control form-control-sm" 
                                   placeholder="Buscar por nombre, email o ID..." 
                                   value="<?php echo htmlspecialchars($buscar); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-3">
                        <a href="?exportar=excel" class="btn btn-success btn-sm me-2">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </a>
                        <button type="button" class="btn btn-info btn-sm me-2" onclick="imprimirReporte()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#cambioMasivoModal">
                            <i class="fas fa-edit"></i> Cambio Masivo
                        </button>
                        <?php if(!empty($buscar) || $filtro_estado!='todos' || $filtro_fecha!='todos'): ?>
                        <a href="admin.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TABLA DE VENTAS -->
                <div class="admin-card">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Lista de Ventas
                            <span class="badge bg-primary ms-2"><?php echo count($ventas); ?></span>
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="30"><input type="checkbox" id="selectAll"></th>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Programa</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Pago</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventas as $venta): ?>
                                <tr>
                                    <td><input type="checkbox" class="venta-check" value="<?php echo $venta['id']; ?>"></td>
                                    <td><strong>#<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($venta['cliente_nombre']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($venta['cliente_email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo substr($venta['programa_estudio'], 0, 4); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $venta['fecha_formateada']; ?></td>
                                    <td><strong class="text-success">S/ <?php echo number_format($venta['total'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-info badge-custom">
                                            <?php echo ucfirst($venta['metodo_pago'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $estado_colores = [
                                            'pendiente' => 'warning',
                                            'completado' => 'info', 
                                            'entregado' => 'success',
                                            'cancelado' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $estado_colores[$venta['estado']]; ?> badge-custom">
                                            <?php echo ucfirst($venta['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detalleModal"
                                                    onclick="cargarDetalleVenta(<?php echo $venta['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($venta['estado'] != 'entregado' && $venta['estado'] != 'cancelado'): ?>
                                            <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split" 
                                                    data-bs-toggle="dropdown">
                                                <span class="visually-hidden">Opciones</span>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="venta_id" value="<?php echo $venta['id']; ?>">
                                                        <input type="hidden" name="nuevo_estado" value="entregado">
                                                        <button type="submit" name="cambiar_estado" class="dropdown-item">
                                                            <i class="fas fa-check text-success me-2"></i>Marcar Entregado
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="venta_id" value="<?php echo $venta['id']; ?>">
                                                        <input type="hidden" name="nuevo_estado" value="cancelado">
                                                        <button type="submit" name="cambiar_estado" class="dropdown-item">
                                                            <i class="fas fa-times text-danger me-2"></i>Cancelar
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: GESTIÓN DE PRODUCTOS -->
            <div class="tab-pane fade" id="productos-tab">
                <div class="admin-card">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-tshirt me-2"></i>Gestión de Productos</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#productoModal" onclick="limpiarFormProducto()">
                            <i class="fas fa-plus me-1"></i> Nuevo Producto
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Programa</th>
                                    <th>Precio</th>
                                    <th>Talla</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                <tr class="<?php echo $producto['stock'] < 10 ? 'table-warning' : ''; ?>">
                                    <td><strong>#<?php echo $producto['id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($producto['descripcion']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo substr($producto['programa_estudio'], 0, 4); ?>
                                        </span>
                                    </td>
                                    <td><strong class="text-success">S/ <?php echo number_format($producto['precio'], 2); ?></strong></td>
                                    <td><span class="badge bg-info"><?php echo $producto['talla']; ?></span></td>
                                    <td>
                                        <?php if ($producto['stock'] < 10): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-exclamation-triangle"></i> <?php echo $producto['stock']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?php echo $producto['stock']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($producto['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                                onclick="editarProducto(<?php echo htmlspecialchars(json_encode($producto)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($producto['activo']): ?>
                                            <a href="?desactivar_producto=<?php echo $producto['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('¿Desactivar este producto?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: ESTADÍSTICAS -->
            <div class="tab-pane fade" id="estadisticas-tab">
                <div class="row">
                    <!-- TOP PRODUCTOS -->
                    <div class="col-md-6 mb-4">
                        <div class="admin-card">
                            <h5 class="mb-3"><i class="fas fa-trophy me-2"></i>Top 5 Productos Más Vendidos</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Programa</th>
                                            <th>Cantidad</th>
                                            <th>Ingresos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_productos as $prod): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($prod['nombre']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo substr($prod['programa_estudio'], 0, 4); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $prod['total_vendido']; ?> unidades</span>
                                            </td>
                                            <td><strong class="text-success">S/ <?php echo number_format($prod['ingresos'], 2); ?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- VENTAS POR PROGRAMA -->
                    <div class="col-md-6 mb-4">
                        <div class="admin-card">
                            <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Ventas por Programa</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Programa</th>
                                            <th>Total Ventas</th>
                                            <th>Ingresos</th>
                                            <th>Promedio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ventas_programa as $vp): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?php 
                                                    $nombres = [
                                                        'Topografia' => 'Topografía',
                                                        'Arquitectura' => 'Arquitectura TI',
                                                        'Enfermeria' => 'Enfermería'
                                                    ];
                                                    echo $nombres[$vp['programa_estudio']] ?? $vp['programa_estudio'];
                                                    ?>
                                                </strong>
                                            </td>
                                            <td><span class="badge bg-info"><?php echo $vp['total']; ?></span></td>
                                            <td><strong class="text-success">S/ <?php echo number_format($vp['ingresos'], 2); ?></strong></td>
                                            <td>
                                                <small class="text-muted">
                                                    S/ <?php echo $vp['total'] > 0 ? number_format($vp['ingresos'] / $vp['total'], 2) : '0.00'; ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RESUMEN DE INGRESOS -->
                <div class="admin-card">
                    <h5 class="mb-3"><i class="fas fa-money-bill-wave me-2"></i>Resumen de Ingresos</h5>
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-success">S/ <?php echo number_format($ingresos_totales, 2); ?></h3>
                                <p class="mb-0 text-muted">Ingresos Confirmados</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-warning">S/ <?php echo number_format($ingresos_pendientes, 2); ?></h3>
                                <p class="mb-0 text-muted">Ingresos Pendientes</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-primary">S/ <?php echo number_format($ingresos_totales + $ingresos_pendientes, 2); ?></h3>
                                <p class="mb-0 text-muted">Total Proyectado</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: REPORTES -->
            <div class="tab-pane fade" id="reportes-tab">
                <!-- ANÁLISIS MENSUAL COMPLETO -->
                <div class="admin-card mb-4">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Análisis Mensual - <?php echo date('F Y'); ?></h5>
                        <a href="?exportar_mensual=pdf" class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i> Exportar PDF
                        </a>
                    </div>
                    
                    <div class="row">
                        <!-- RESUMEN DEL MES -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-3">Resumen del Mes</h6>
                                    <div class="mb-3">
                                        <h2 class="text-primary"><?php echo $estadisticas_mes['total_ventas_mes'] ?? 0; ?></h2>
                                        <p class="text-muted mb-0">Ventas Totales</p>
                                    </div>
                                    <div class="mb-3">
                                        <h2 class="text-success">S/ <?php echo number_format($estadisticas_mes['ingresos_mes'] ?? 0, 2); ?></h2>
                                        <p class="text-muted mb-0">Ingresos Totales</p>
                                    </div>
                                    <div>
                                        <h2 class="text-info">S/ <?php echo number_format($estadisticas_mes['promedio_venta'] ?? 0, 2); ?></h2>
                                        <p class="text-muted mb-0">Promedio por Venta</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GRÁFICO DE VENTAS POR DÍA -->
                        <div class="col-md-8 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">Ventas por Día - Mes Actual</h6>
                                    <div class="chart-container">
                                        <canvas id="ventasPorDiaChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <!-- GRÁFICO DE ESTADOS -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">Distribución por Estado</h6>
                                    <div class="chart-container">
                                        <canvas id="estadosChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GRÁFICO DE INGRESOS POR DÍA -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">Ingresos por Día</h6>
                                    <div class="chart-container">
                                        <canvas id="ingresosPorDiaChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- INVENTARIO -->
                <div class="admin-card">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-box me-2"></i>Inventario - Estado Actual del Stock</h5>
                        <a href="?reporte_inventario=1" class="btn btn-warning btn-sm">
                            <i class="fas fa-download me-1"></i> Exportar Reporte
                        </a>
                    </div>
                    
                    <!-- RESUMEN DE INVENTARIO -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-primary"><?php echo count($productos); ?></h3>
                                    <p class="text-muted mb-0">Productos Totales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-success"><?php echo count(array_filter($productos, fn($p) => $p['activo'] == 1)); ?></h3>
                                    <p class="text-muted mb-0">Productos Activos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <?php 
                                    $total_stock = array_sum(array_column($productos, 'stock'));
                                    $valor_inventario = array_sum(array_map(fn($p) => $p['stock'] * $p['precio'], $productos));
                                    ?>
                                    <h3 class="text-info"><?php echo $total_stock; ?></h3>
                                    <p class="text-muted mb-0">Unidades en Stock</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-danger">S/ <?php echo number_format($valor_inventario, 2); ?></h3>
                                    <p class="text-muted mb-0">Valor del Inventario</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PRODUCTOS CON STOCK BAJO -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Productos con Stock Bajo (≤ 10 unidades)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Programa</th>
                                        <th>Stock Actual</th>
                                        <th>Precio Unitario</th>
                                        <th>Valor en Stock</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($stock_bajo) > 0): ?>
                                        <?php foreach ($stock_bajo as $producto): ?>
                                        <tr class="table-warning">
                                            <td><strong><?php echo htmlspecialchars($producto['nombre']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo substr($producto['programa_estudio'], 0, 4); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $producto['stock']; ?></span>
                                            </td>
                                            <td>S/ <?php echo number_format($producto['precio'], 2); ?></td>
                                            <td>S/ <?php echo number_format($producto['stock'] * $producto['precio'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">¡Reabastecer!</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                ¡Excelente! No hay productos con stock bajo.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- DISTRIBUCIÓN POR PROGRAMA -->
                    <div>
                        <h6 class="text-muted mb-3">Distribución del Inventario por Programa</h6>
                        <div class="row">
                            <?php 
                            $stock_por_programa = [];
                            foreach ($productos as $producto) {
                                if ($producto['activo']) {
                                    $programa = $producto['programa_estudio'];
                                    if (!isset($stock_por_programa[$programa])) {
                                        $stock_por_programa[$programa] = [
                                            'total' => 0,
                                            'valor' => 0,
                                            'productos' => 0
                                        ];
                                    }
                                    $stock_por_programa[$programa]['total'] += $producto['stock'];
                                    $stock_por_programa[$programa]['valor'] += $producto['stock'] * $producto['precio'];
                                    $stock_por_programa[$programa]['productos']++;
                                }
                            }
                            ?>
                            <?php foreach ($stock_por_programa as $programa => $datos): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <?php 
                                            $nombres = [
                                                'Topografia' => 'Topografía',
                                                'Arquitectura' => 'Arquitectura TI',
                                                'Enfermeria' => 'Enfermería'
                                            ];
                                            echo $nombres[$programa] ?? $programa;
                                            ?>
                                        </h6>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Productos:</span>
                                            <strong><?php echo $datos['productos']; ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Unidades:</span>
                                            <strong class="text-primary"><?php echo $datos['total']; ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Valor:</span>
                                            <strong class="text-success">S/ <?php echo number_format($datos['valor'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- TARJETAS DE ACCIÓN RÁPIDA -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-4">
                        <div class="card report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                <h5>Reporte de Ventas</h5>
                                <p class="text-muted">Exportar todas las ventas a Excel</p>
                                <a href="?exportar=excel" class="btn btn-success">
                                    <i class="fas fa-download me-2"></i> Descargar
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-pie fa-3x text-info mb-3"></i>
                                <h5>Análisis Mensual</h5>
                                <p class="text-muted">Estadísticas detalladas del mes actual</p>
                                <a href="#reportes-tab" class="btn btn-info">
                                    <i class="fas fa-chart-bar me-2"></i> Ver Análisis
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card report-card">
                            <div class="card-body text-center">
                                <i class="fas fa-box fa-3x text-warning mb-3"></i>
                                <h5>Inventario</h5>
                                <p class="text-muted">Estado actual del stock y alertas</p>
                                <a href="#reportes-tab" class="btn btn-warning">
                                    <i class="fas fa-warehouse me-2"></i> Ver Inventario
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Detalle de Venta -->
    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleVentaContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Cambio Masivo -->
    <div class="modal fade" id="cambioMasivoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambio Masivo de Estado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Selecciona las ventas en la tabla y elige el nuevo estado
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nuevo Estado:</label>
                            <select name="nuevo_estado_masivo" class="form-select" required>
                                <option value="">Seleccionar estado...</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="completado">Completado</option>
                                <option value="entregado">Entregado</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                        <div id="ventasSeleccionadas"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cambio_masivo" class="btn btn-warning">
                            <i class="fas fa-check me-1"></i> Aplicar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: Producto -->
    <div class="modal fade" id="productoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productoModalLabel">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="producto_id" id="producto_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre del Producto*</label>
                            <input type="text" class="form-control" name="nombre_producto" id="nombre_producto" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion_producto" id="descripcion_producto" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Programa*</label>
                                <select class="form-select" name="programa_producto" id="programa_producto" required>
                                    <option value="Topografia">Topografía</option>
                                    <option value="Arquitectura">Arquitectura TI</option>
                                    <option value="Enfermeria">Enfermería</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Talla*</label>
                                <select class="form-select" name="talla_producto" id="talla_producto" required>
                                    <option value="S">S - Small</option>
                                    <option value="M">M - Medium</option>
                                    <option value="L">L - Large</option>
                                    <option value="Única">Única</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio (S/)*</label>
                                <input type="number" class="form-control" name="precio_producto" id="precio_producto" 
                                       step="0.01" min="0" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock*</label>
                                <input type="number" class="form-control" name="stock_producto" id="stock_producto" 
                                       min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_producto" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sistema de tabs con navegación desde navbar
        document.querySelectorAll('.nav-link-admin[data-tab]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const tab = this.getAttribute('data-tab');
                
                // Activar tab correspondiente
                document.querySelectorAll('.nav-link-admin').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Activar contenido del tab
                document.querySelectorAll('.nav-tabs-admin .nav-link').forEach(t => t.classList.remove('active'));
                document.querySelector(`.nav-tabs-admin a[href="#${tab}-tab"]`).classList.add('active');
                
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
                document.getElementById(`${tab}-tab`).classList.add('show', 'active');
                
                // Si es el tab de reportes, inicializar gráficos
                if (tab === 'reportes') {
                    setTimeout(initializeCharts, 300);
                }
            });
        });

        // Cargar detalle de venta
        function cargarDetalleVenta(ventaId) {
            fetch('ajax_detalle_venta_admin.php?venta_id=' + ventaId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detalleVentaContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('detalleVentaContent').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar el detalle</div>';
                });
        }

        // Selección múltiple de ventas
        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.venta-check').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Actualizar modal de cambio masivo
        const cambioMasivoModal = document.getElementById('cambioMasivoModal');
        if (cambioMasivoModal) {
            cambioMasivoModal.addEventListener('show.bs.modal', function() {
                const seleccionadas = Array.from(document.querySelectorAll('.venta-check:checked'))
                    .map(cb => cb.value);
                
                const container = document.getElementById('ventasSeleccionadas');
                
                if (seleccionadas.length === 0) {
                    container.innerHTML = '<div class="alert alert-warning">No hay ventas seleccionadas</div>';
                } else {
                    container.innerHTML = `
                        <div class="alert alert-success">
                            <strong>${seleccionadas.length}</strong> ventas seleccionadas
                        </div>
                        ${seleccionadas.map(id => 
                            `<input type="hidden" name="ventas_seleccionadas[]" value="${id}">`
                        ).join('')}
                    `;
                }
            });
        }

        // Editar producto
        function editarProducto(producto) {
            document.getElementById('productoModalLabel').textContent = 'Editar Producto';
            document.getElementById('producto_id').value = producto.id;
            document.getElementById('nombre_producto').value = producto.nombre;
            document.getElementById('descripcion_producto').value = producto.descripcion;
            document.getElementById('programa_producto').value = producto.programa_estudio;
            document.getElementById('precio_producto').value = producto.precio;
            document.getElementById('talla_producto').value = producto.talla;
            document.getElementById('stock_producto').value = producto.stock;
            
            new bootstrap.Modal(document.getElementById('productoModal')).show();
        }

        // Limpiar formulario producto
        function limpiarFormProducto() {
            document.getElementById('productoModalLabel').textContent = 'Nuevo Producto';
            document.getElementById('producto_id').value = '';
            document.getElementById('nombre_producto').value = '';
            document.getElementById('descripcion_producto').value = '';
            document.getElementById('precio_producto').value = '';
            document.getElementById('stock_producto').value = '';
        }

        // Imprimir reporte
        function imprimirReporte() {
            window.print();
        }

        // Inicializar gráficos
        function initializeCharts() {
            // Gráfico de ventas por día
            const ventasPorDiaCtx = document.getElementById('ventasPorDiaChart')?.getContext('2d');
            if (ventasPorDiaCtx) {
                new Chart(ventasPorDiaCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($labels_dias); ?>,
                        datasets: [{
                            label: 'Ventas por Día',
                            data: <?php echo json_encode(array_values($datos_ventas)); ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de ingresos por día
            const ingresosPorDiaCtx = document.getElementById('ingresosPorDiaChart')?.getContext('2d');
            if (ingresosPorDiaCtx) {
                new Chart(ingresosPorDiaCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels_dias); ?>,
                        datasets: [{
                            label: 'Ingresos (S/)',
                            data: <?php echo json_encode(array_values($datos_ingresos)); ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'S/ ' + value;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de estados
            const estadosCtx = document.getElementById('estadosChart')?.getContext('2d');
            if (estadosCtx) {
                new Chart(estadosCtx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode($estados_labels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($estados_data); ?>,
                            backgroundColor: [
                                'rgba(255, 206, 86, 0.7)',  // amarillo - pendiente
                                'rgba(54, 162, 235, 0.7)',  // azul - completado
                                'rgba(75, 192, 192, 0.7)',  // verde azulado - entregado
                                'rgba(255, 99, 132, 0.7)'   // rojo - cancelado
                            ],
                            borderColor: [
                                'rgba(255, 206, 86, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(255, 99, 132, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }

        // Inicializar gráficos cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            // Si estamos en el tab de reportes, inicializar gráficos
            if (window.location.hash === '#reportes' || 
                document.querySelector('.nav-tabs-admin .nav-link.active').getAttribute('href') === '#reportes-tab') {
                setTimeout(initializeCharts, 500);
            }
        });

        // Generar reportes
        function generarReporteMensual() {
            // Redirigir para generar el reporte PDF
            window.location.href = '?exportar_mensual=pdf';
        }

        function generarReporteInventario() {
            // Redirigir para generar el reporte de inventario
            window.location.href = '?reporte_inventario=1';
        }
    </script>
</body>
</html>