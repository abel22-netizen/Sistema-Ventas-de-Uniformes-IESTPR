<?php
include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $programa_estudio = $_POST['programa_estudio'];
    
    // Validaciones
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Por favor, complete todos los campos.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "El email ya está registrado.";
        } else {
            // Crear nuevo usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, programa_estudio) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$nombre, $email, $hashed_password, $programa_estudio])) {
                $success = "¡Registro exitoso! Ahora puedes iniciar sesión.";
            } else {
                $error = "Error al registrar el usuario. Intente nuevamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - IESTP Recuay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .register-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            position: relative;
        }

        .form-section {
            padding: 40px;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
            text-align: center;
        }

        .form-subtitle {
            color: #666;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
            background: white;
        }

        .form-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }

        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .social-login {
            text-align: center;
            margin: 25px 0;
            color: #666;
        }

        .social-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border: 2px solid #e1e1e1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }

        .toggle-text {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }

        .toggle-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .toggle-link:hover {
            text-decoration: underline;
        }

        .bubbles {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .bubble {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 15s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            33% {
                transform: translateY(-20px) rotate(120deg);
            }
            66% {
                transform: translateY(10px) rotate(240deg);
            }
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="bubbles" id="bubbles"></div>
    
    <div class="register-container">
        <div class="form-section">
            <h2 class="form-title">Regístrate aquí</h2>
            <p class="form-subtitle">Si aún no tienes una cuenta, únete a nosotros y comienza tu viaje</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <div class="mt-2">
                        <a href="login.php" class="btn btn-success btn-sm">Iniciar Sesión</a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <i class="fas fa-user form-icon"></i>
                    <input type="text" class="form-control" name="nombre" placeholder="Nombre completo" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-envelope form-icon"></i>
                    <input type="email" class="form-control" name="email" placeholder="Correo electrónico" required>
                </div>

                <div class="form-group">
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                </div>

                <div class="form-group">
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirmar contraseña" required>
                </div>

                <div class="form-group">
                    <i class="fas fa-book form-icon"></i>
                    <select class="form-control" name="programa_estudio" required style="padding-left: 45px;">
                        <option value="">Selecciona tu programa</option>
                        <option value="Topografia">Topografía Superficial y Minera</option>
                        <option value="Arquitectura">Arquitectura de Plataformas y Servicios de TI</option>
                        <option value="Enfermeria">Enfermería Técnica</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">Registrar</button>
            </form>

            <div class="social-login">
                <p>O usa tu cuenta</p>
                <div class="social-buttons">
                    <a href="#" class="social-btn">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-btn">
                        <i class="fab fa-microsoft"></i>
                    </a>
                    <a href="#" class="social-btn">
                        <i class="fab fa-apple"></i>
                    </a>
                </div>
            </div>

            <div class="toggle-text">
                ¿Ya tienes una cuenta? <a href="login.php" class="toggle-link">Inicia sesión aquí</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Crear burbujas animadas
        function createBubbles() {
            const bubblesContainer = document.getElementById('bubbles');
            const colors = [
                'rgba(255, 255, 255, 0.1)',
                'rgba(255, 255, 255, 0.05)',
                'rgba(255, 255, 255, 0.08)'
            ];

            for (let i = 0; i < 15; i++) {
                const bubble = document.createElement('div');
                bubble.className = 'bubble';
                
                const size = Math.random() * 100 + 50;
                const left = Math.random() * 100;
                const delay = Math.random() * 15;
                const duration = Math.random() * 10 + 15;
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                bubble.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    left: ${left}%;
                    top: ${Math.random() * 100}%;
                    background: ${color};
                    animation-delay: ${delay}s;
                    animation-duration: ${duration}s;
                `;
                
                bubblesContainer.appendChild(bubble);
            }
        }

        createBubbles();
    </script>
</body>
</html>