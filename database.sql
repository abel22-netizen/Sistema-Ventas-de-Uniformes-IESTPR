-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS sistema_ventas_uniformes;
USE sistema_ventas_uniformes;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    programa_estudio ENUM('Topografia', 'Arquitectura', 'Enfermeria') NOT NULL,
    telefono VARCHAR(15),
    direccion TEXT,
    dni VARCHAR(8),
    fecha_nacimiento DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) DEFAULT 1
);

-- Tabla de productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    programa_estudio ENUM('Topografia', 'Arquitectura', 'Enfermeria') NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    talla ENUM('S', 'M', 'L', 'Única') NOT NULL,
    stock INT NOT NULL,
    imagen VARCHAR(255),
    activo TINYINT(1) DEFAULT 1
);

-- Tabla de carrito
CREATE TABLE carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    producto_id INT,
    cantidad INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Tabla de ventas
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    fecha_venta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'completado', 'cancelado') DEFAULT 'pendiente',
    metodo_pago ENUM('yape', 'plin', 'tarjeta', 'efectivo'),
    comprobante VARCHAR(255),
    direccion_entrega TEXT,
    telefono_contacto VARCHAR(15),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de detalles de venta
CREATE TABLE detalle_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT,
    producto_id INT,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- INSERTAR TODOS LOS PRODUCTOS COMPLETOS
INSERT INTO productos (nombre, descripcion, programa_estudio, precio, talla, stock) VALUES
-- TOPOGRAFÍA (14 productos)
('Chaleco Rojo', 'Chaleco de seguridad color rojo para topografía', 'Topografia', 45.00, 'S', 50),
('Chaleco Rojo', 'Chaleco de seguridad color rojo para topografía', 'Topografia', 45.00, 'M', 50),
('Chaleco Rojo', 'Chaleco de seguridad color rojo para topografía', 'Topografia', 45.00, 'L', 50),
('Camisa Blanca', 'Camisa blanca para uniforme de topografía', 'Topografia', 35.00, 'S', 50),
('Camisa Blanca', 'Camisa blanca para uniforme de topografía', 'Topografia', 35.00, 'M', 50),
('Camisa Blanca', 'Camisa blanca para uniforme de topografía', 'Topografia', 35.00, 'L', 50),
('Pantalón Azulino', 'Pantalón de uniforme color azulino para topografía', 'Topografia', 40.00, 'S', 50),
('Pantalón Azulino', 'Pantalón de uniforme color azulino para topografía', 'Topografia', 40.00, 'M', 50),
('Pantalón Azulino', 'Pantalón de uniforme color azulino para topografía', 'Topografia', 40.00, 'L', 50),
('Casco Blanco', 'Casco de seguridad color blanco para topografía', 'Topografia', 55.00, 'Única', 50),
('Pantalón Azulino Dama', 'Pantalón azulino corte para dama - Topografía', 'Topografia', 42.00, 'S', 20),
('Pantalón Azulino Dama', 'Pantalón azulino corte para dama - Topografía', 'Topografia', 42.00, 'M', 20),
('Pantalón Azulino Dama', 'Pantalón azulino corte para dama - Topografía', 'Topografia', 42.00, 'L', 20),
('Corbata Azulino', 'Corbata color azulino para topografía', 'Topografia', 22.00, 'Única', 25),

-- ARQUITECTURA (17 productos)
('Camisa Manga Larga Blanca', 'Camisa manga larga blanca para arquitectura', 'Arquitectura', 38.00, 'S', 50),
('Camisa Manga Larga Blanca', 'Camisa manga larga blanca para arquitectura', 'Arquitectura', 38.00, 'M', 50),
('Camisa Manga Larga Blanca', 'Camisa manga larga blanca para arquitectura', 'Arquitectura', 38.00, 'L', 50),
('Chompa Azulino', 'Chompa color azulino con logo del instituto', 'Arquitectura', 60.00, 'S', 50),
('Chompa Azulino', 'Chompa color azulino con logo del instituto', 'Arquitectura', 60.00, 'M', 50),
('Chompa Azulino', 'Chompa color azulino con logo del instituto', 'Arquitectura', 60.00, 'L', 50),
('Corbata Azulino', 'Corbata color azulino para arquitectura', 'Arquitectura', 25.00, 'Única', 50),
('Pantalón Azulino', 'Pantalón de uniforme color azulino para arquitectura', 'Arquitectura', 42.00, 'S', 50),
('Pantalón Azulino', 'Pantalón de uniforme color azulino para arquitectura', 'Arquitectura', 42.00, 'M', 50),
('Pantalón Azulino', 'Pantalón de uniforme color azulino para arquitectura', 'Arquitectura', 42.00, 'L', 50),
('Pantalón Azulino Dama', 'Pantalón azulino corte para dama', 'Arquitectura', 45.00, 'S', 25),
('Pantalón Azulino Dama', 'Pantalón azulino corte para dama', 'Arquitectura', 45.00, 'M', 25),
('Pantalón Azulino Dama', 'Pantalón azulino corte para dama', 'Arquitectura', 45.00, 'L', 25),
('Falda Azulino Dama', 'Falda azulino para dama', 'Arquitectura', 38.00, 'S', 20),
('Falda Azulino Dama', 'Falda azulino para dama', 'Arquitectura', 38.00, 'M', 20),
('Falda Azulino Dama', 'Falda azulino para dama', 'Arquitectura', 38.00, 'L', 20),
('Pañoleta Dama', 'Pañoleta color azulino para dama', 'Arquitectura', 18.00, 'Única', 30),

-- ENFERMERÍA (16 productos)
('Blusa Blanca', 'Blusa color blanco para enfermería técnica', 'Enfermeria', 32.00, 'S', 50),
('Blusa Blanca', 'Blusa color blanco para enfermería técnica', 'Enfermeria', 32.00, 'M', 50),
('Blusa Blanca', 'Blusa color blanco para enfermería técnica', 'Enfermeria', 32.00, 'L', 50),
('Pantalón Blanco', 'Pantalón color blanco para enfermería técnica', 'Enfermeria', 38.00, 'S', 50),
('Pantalón Blanco', 'Pantalón color blanco para enfermería técnica', 'Enfermeria', 38.00, 'M', 50),
('Pantalón Blanco', 'Pantalón color blanco para enfermería técnica', 'Enfermeria', 38.00, 'L', 50),
('Pantalón Azulino Dama', 'Pantalón azulino corte para dama - Enfermería', 'Enfermeria', 40.00, 'S', 20),
('Pantalón Azulino Dama', 'Pantalón azulino corte para dama - Enfermería', 'Enfermeria', 40.00, 'M', 20),
('Pantalón Azulino Dama', 'Pantalón azulino corte para dama - Enfermería', 'Enfermeria', 40.00, 'L', 20),
('Chompa Azulino', 'Chompa color azulino unisex', 'Enfermeria', 55.00, 'S', 25),
('Chompa Azulino', 'Chompa color azulino unisex', 'Enfermeria', 55.00, 'M', 25),
('Chompa Azulino', 'Chompa color azulino unisex', 'Enfermeria', 55.00, 'L', 25),
('Camisa Manga Larga', 'Camisa manga larga blanca para enfermería', 'Enfermeria', 35.00, 'S', 30),
('Camisa Manga Larga', 'Camisa manga larga blanca para enfermería', 'Enfermeria', 35.00, 'M', 30),
('Camisa Manga Larga', 'Camisa manga larga blanca para enfermería', 'Enfermeria', 35.00, 'L', 30);

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, email, password, programa_estudio, telefono, dni) VALUES 
('Administrador', 'admin@istprecuay.edu.pe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Topografia', '971673855', '12345678');
-- La contraseña es "password"