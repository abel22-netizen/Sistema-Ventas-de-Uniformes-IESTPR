<?php
include 'config.php';
requireLogin();
$user = getCurrentUser($pdo);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $dni = trim($_POST['dni']);
    $direccion = trim($_POST['direccion']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $programa_estudio = $_POST['programa_estudio'];
    
    // Validaciones
    if (empty($nombre) || empty($email)) {
        $error = "Por favor, complete los campos obligatorios.";
    } else {
        // Verificar si el email ya existe (excluyendo el usuario actual)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        
        if ($stmt->rowCount() > 0) {
            $error = "El email ya está registrado por otro usuario.";
        } else {
            // Actualizar usuario
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, dni = ?, direccion = ?, fecha_nacimiento = ?, programa_estudio = ? WHERE id = ?");
            
            if ($stmt->execute([$nombre, $email, $telefono, $dni, $direccion, $fecha_nacimiento, $programa_estudio, $user['id']])) {
                $success = "Perfil actualizado correctamente.";
                // Actualizar datos en sesión
                $_SESSION['user_name'] = $nombre;
                $_SESSION['user_programa'] = $programa_estudio;
                $user = getCurrentUser($pdo); // Recargar datos del usuario
            } else {
                $error = "Error al actualizar el perfil. Intente nuevamente.";
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
    <title>Mi Perfil - Sistema de Ventas de Uniformes</title>
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
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9 0%, #21618c 100%);
        }
        
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .form-control {
            padding-left: 45px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .form-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 16px;
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
                    <a class="nav-link active" href="perfil.php">
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
                        <span class="navbar-brand fw-bold text-primary">Mi Perfil</span>
                        <div class="d-flex">
                            <span class="navbar-text me-3">
                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($user['nombre']); ?>
                            </span>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
                            </a>
                        </div>
                    </div>
                </nav>
                
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="card">
                            <div class="profile-header">
                                <i class="fas fa-user-circle fa-4x mb-3"></i>
                                <h4><?php echo htmlspecialchars($user['nombre']); ?></h4>
                                <p class="mb-0">Miembro desde <?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?></p>
                            </div>
                            
                            <div class="card-body p-4">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>
                                
                                <?php if ($success): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <i class="fas fa-user form-icon"></i>
                                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                                       value="<?php echo htmlspecialchars($user['nombre']); ?>" required
                                                       placeholder="Nombre completo">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <i class="fas fa-envelope form-icon"></i>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($user['email']); ?>" required
                                                       placeholder="Correo electrónico">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <i class="fas fa-phone form-icon"></i>
                                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                                       value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>"
                                                       placeholder="Teléfono">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <i class="fas fa-id-card form-icon"></i>
                                                <input type="text" class="form-control" id="dni" name="dni" 
                                                       value="<?php echo htmlspecialchars($user['dni'] ?? ''); ?>"
                                                       placeholder="DNI">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <i class="fas fa-home form-icon"></i>
                                        <textarea class="form-control" id="direccion" name="direccion" rows="3" 
                                                  placeholder="Dirección de entrega"><?php echo htmlspecialchars($user['direccion'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <i class="fas fa-calendar form-icon"></i>
                                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                                       value="<?php echo $user['fecha_nacimiento'] ?? ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <i class="fas fa-book form-icon"></i>
                                                <select class="form-control" id="programa_estudio" name="programa_estudio" required style="padding-left: 45px;">
                                                    <option value="Topografia" <?php echo $user['programa_estudio'] == 'Topografia' ? 'selected' : ''; ?>>Topografía Superficial y Minera</option>
                                                    <option value="Arquitectura" <?php echo $user['programa_estudio'] == 'Arquitectura' ? 'selected' : ''; ?>>Arquitectura de Plataformas y Servicios de TI</option>
                                                    <option value="Enfermeria" <?php echo $user['programa_estudio'] == 'Enfermeria' ? 'selected' : ''; ?>>Enfermería Técnica</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calendar me-2"></i>Fecha de Registro
                                        </label>
                                        <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($user['fecha_registro'])); ?>" readonly>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save me-2"></i>Guardar Cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>