<?php
session_start();

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_ventas_uniformes');

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: No se pudo conectar. " . $e->getMessage());
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para redireccionar si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Función para obtener información del usuario actual
function getCurrentUser($pdo) {
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

// Función para obtener productos del carrito
function getCarritoCount($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ? $result['total'] : 0;
}

// Función para formatear precio
function formatPrice($price) {
    return 'S/ ' . number_format($price, 2);
}

// Función para verificar si es administrador
function isAdmin($pdo) {
    if (!isLoggedIn()) return false;
    
    $stmt = $pdo->prepare("SELECT es_admin FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user && $user['es_admin'] == 1;
}

// Función para requerir permisos de administrador
function requireAdmin($pdo) {
    if (!isAdmin($pdo)) {
        header("Location: dashboard.php");
        exit();
    }
}
?>