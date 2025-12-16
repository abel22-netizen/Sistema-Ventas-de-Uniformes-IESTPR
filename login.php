<?php
include 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_programa'] = $user['programa_estudio'];
            
            if ($remember) {
                setcookie('user_email', $email, time() + (86400 * 30), "/");
            }
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Email o contraseña incorrectos.";
        }
    } else {
        $error = "Por favor, complete todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - IESTP Recuay</title>
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

        .container-wrapper {
            width: 100%;
            max-width: 900px;  /* REDUCIDO de 1100px */
            height: 550px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            position: relative;
        }

        .container-inner {
            display: flex;
            width: 200%;
            height: 100%;
            transition: transform 0.8s ease-in-out;
        }

        .container-inner.active {
            transform: translateX(-50%);
        }

        .form-container {
            width: 50%;
            padding: 25px 30px; /* Ajustado */
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        .welcome-container {
            width: 50%;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            background: transparent;
            backdrop-filter: blur(5px);
        }

        .welcome-container h1,
        .welcome-container p,
        .welcome-container a {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .form-title {
            font-size: 2rem; /* AUMENTADO de 1.8rem */
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
            line-height: 1.2;
        }

        .form-subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 1rem; /* AUMENTADO de 0.95rem */
            line-height: 1.4;
        }

        .form-group {
            position: relative;
            margin-bottom: 18px; /* Reducido un poco */
        }

        .form-control {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 15px; /* AUMENTADO de 14px */
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
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 17px; /* AUMENTADO de 16px */
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 0.95rem; /* AUMENTADO de 0.9rem */
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem; /* AUMENTADO de 0.9rem */
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .btn-primary {
            width: 100%;
            padding: 13px; /* Aumentado de 12px */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 15px; /* AUMENTADO de 14px */
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
            margin: 20px 0;
            color: #666;
            font-size: 0.95rem; /* AUMENTADO de 0.9rem */
        }

        .social-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 12px;
        }

        .social-btn {
            width: 45px;
            height: 45px;
            border: 2px solid #e1e1e1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 17px; /* AUMENTADO de 16px */
        }

        .social-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }

        .toggle-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.95rem; /* AUMENTADO de 0.9rem */
        }

        .toggle-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem; /* AUMENTADO de 0.9rem */
        }

        .toggle-link:hover {
            text-decoration: underline;
        }

        .welcome-title {
            font-size: 2.2rem; /* AUMENTADO de 2rem */
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .welcome-subtitle {
            font-size: 1.1rem; /* AUMENTADO de 1rem */
            margin-bottom: 15px; /* Reducido de 20px */
            opacity: 0.9;
            line-height: 1.4;
        }

        .btn-outline-light {
            padding: 10px 25px;
            border: 2px solid white;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem; /* AUMENTADO de 0.9rem */
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .btn-outline-light:hover {
            background: white;
            color: #667eea;
            text-shadow: none;
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

        .logo {
            width: 140px; /* Reducido para ajustar */
            height: 140px; /* Reducido para ajustar */
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .logo img {
            width: 120px; /* Reducido para ajustar */
            height: 120px; /* Reducido para ajustar */
            border-radius: 50%;
            object-fit: cover;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 10px 15px;
            font-size: 0.95rem; /* AUMENTADO de 0.9rem */
        }

        select.form-control {
            padding-left: 40px;
            font-size: 15px; /* AUMENTADO de 14px */
        }

        /* Ajuste específico para el formulario de registro */
        .form-container:nth-child(3) {
            padding: 20px 30px; /* Ajustado */
        }

        .form-container:nth-child(3) .form-title {
            font-size: 2rem; /* Aumentado */
        }

        .form-container:nth-child(3) .form-group {
            margin-bottom: 16px; /* Más compacto */
        }

        /* Ajustes para que todo quepa mejor */
        .form-container > div {
            max-height: 100%;
            overflow-y: auto;
            padding-right: 5px;
        }

        .form-container > div::-webkit-scrollbar {
            width: 4px;
        }

        .form-container > div::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.3);
            border-radius: 2px;
        }

        @media (max-height: 700px) {
            .container-wrapper {
                height: 500px;
                max-width: 850px; /* Ajustado */
            }
            
            .form-container {
                padding: 20px 25px;
            }
            
            .welcome-container {
                padding: 15px;
            }
            
            .welcome-title {
                font-size: 2rem;
                margin-bottom: 10px;
            }
            
            .welcome-subtitle {
                font-size: 1rem;
                margin-bottom: 12px;
            }
            
            .logo {
                width: 170px;
                height: 170px;
                margin-bottom: 15px;
            }
            
            .logo img {
                width: 150px;
                height: 150px;
            }
            
            .form-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .container-wrapper {
                height: auto;
                max-height: 90vh;
                overflow-y: auto;
                max-width: 95%;
            }
            
            .form-container, .welcome-container {
                padding: 20px;
            }
        }

        /* Ajuste para pantallas muy pequeñas */
        @media (max-width: 480px) {
            .container-wrapper {
                height: auto;
                max-height: 95vh;
            }
            
            .form-title {
                font-size: 1.6rem;
            }
            
            .welcome-title {
                font-size: 1.8rem;
            }
            
            .logo {
                width: 100px;
                height: 100px;
            }
            
            .logo img {
                width: 85px;
                height: 85px;
            }
        }
    </style>
</head>
<body>
    <div class="bubbles" id="bubbles"></div>
    
    <div class="container-wrapper">
        <div class="container-inner" id="containerInner">
            <!-- Login Form -->
            <div class="form-container">
                <div>
                    <h2 class="form-title">Inicia sesión aquí</h2>
                    <p class="form-subtitle">Si tienes una cuenta, inicia sesión aquí</p>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="login" value="1">
                        <div class="form-group">
                            <i class="fas fa-envelope form-icon"></i>
                            <input type="email" class="form-control" name="email" placeholder="Correo electrónico" required
                                   value="<?php echo isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <i class="fas fa-lock form-icon"></i>
                            <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                        </div>

                        <div class="remember-forgot">
                            <div class="remember-me">
                                <input type="checkbox" id="remember" name="remember" <?php echo isset($_COOKIE['user_email']) ? 'checked' : ''; ?>>
                                <label for="remember">Recuérdame</label>
                            </div>
                            <a href="#" class="forgot-password">¿Olvidaste la contraseña?</a>
                        </div>

                        <button type="submit" class="btn-primary">Iniciar Sesión</button>
                    </form>

                    <div class="social-login">
                        <p>O usa tu cuenta</p>
                        <div class="social-buttons">
                            <a href="#" class="social-btn">
                                <i class="fab fa-google"></i>
                            </a>
                            <a href="#" class="social-btn">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a href="#" class="social-btn">
                                <i class="fab fa-github"></i>
                            </a>
                        </div>
                    </div>

                    <div class="toggle-text">
                        ¿No tienes una cuenta? <span class="toggle-link" onclick="toggleForm()">Regístrate aquí</span>
                    </div>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="welcome-container">
                <div class="logo">
                    <img src="IESTP-LOGO.png" alt="ISTP Recuay">
                </div>   
                <h1 class="welcome-title">IESTP RECUAY</h1>
                <p class="welcome-subtitle">Instituto de Educación Superior Tecnológico Público de Recuay</p>
                <p class="welcome-subtitle">Venta de Uniformes</p>
                <a href="#" class="btn-outline-light" onclick="toggleForm()">Registrate</a>
            </div>

            <!-- Register Form -->
            <div class="form-container">
                <div>
                    <h2 class="form-title">Regístrate aquí</h2>
                    <p class="form-subtitle">Si aún no tienes una cuenta, únete a nosotros y comienza tu compra</p>

                    <form method="POST" action="registro.php">
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
                            <select class="form-control" name="programa_estudio" required>
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
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a href="#" class="social-btn">
                                <i class="fab fa-github"></i>
                            </a>
                        </div>
                    </div>

                    <div class="toggle-text">
                        ¿Ya tienes una cuenta? <span class="toggle-link" onclick="toggleForm()">Inicia sesión aquí</span>
                    </div>
                </div>
            </div>

            <!-- Welcome Section for Register -->
            <div class="welcome-container">
                <div class="logo">
                    <img src="IESTP-LOGO.png" alt="IESTP Recuay">
                </div>
                <h1 class="welcome-title">¡Hola Alumno!</h1>
                <p class="welcome-subtitle">Bienvenido al sistema de ventas de uniformes del IESTP Recuay</p>
                <p class="welcome-subtitle">Únete a nuestra comunidad educativa</p>
                <a href="#" class="btn-outline-light" onclick="toggleForm()">Iniciar Sesión</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleForm() {
            const container = document.getElementById('containerInner');
            container.classList.toggle('active');
        }

        // Crear burbujas animadas
        function createBubbles() {
            const bubblesContainer = document.getElementById('bubbles');
            const colors = [
                'rgba(255, 255, 255, 0.1)',
                'rgba(255, 255, 255, 0.05)',
                'rgba(255, 255, 255, 0.08)'
            ];

            for (let i = 0; i < 50; i++) { // Reducido de 12
                const bubble = document.createElement('div');
                bubble.className = 'bubble';
                
                const size = Math.random() * 90 + 60; // Tamaños más pequeños
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