<?php
include 'config.php';
session_start();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $producto_id = $_POST['producto_id'] ?? '';
    $cantidad = $_POST['cantidad'] ?? 1;
    
    if (empty($producto_id)) {
        echo json_encode(['success' => false, 'message' => 'Producto no especificado']);
        exit();
    }
    
    try {
        // Verificar si el producto existe y tiene stock
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            exit();
        }
        
        if ($producto['stock'] < $cantidad) {
            echo json_encode(['success' => false, 'message' => 'Stock insuficiente']);
            exit();
        }
        
        // Verificar si ya está en el carrito
        $stmt = $pdo->prepare("SELECT * FROM carrito WHERE usuario_id = ? AND producto_id = ?");
        $stmt->execute([$_SESSION['user_id'], $producto_id]);
        $item_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item_existente) {
            // Actualizar cantidad
            $nueva_cantidad = $item_existente['cantidad'] + $cantidad;
            if ($nueva_cantidad > $producto['stock']) {
                echo json_encode(['success' => false, 'message' => 'Stock insuficiente para la cantidad solicitada']);
                exit();
            }
            
            $stmt = $pdo->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
            $stmt->execute([$nueva_cantidad, $item_existente['id']]);
        } else {
            // Agregar nuevo item
            $stmt = $pdo->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $producto_id, $cantidad]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>