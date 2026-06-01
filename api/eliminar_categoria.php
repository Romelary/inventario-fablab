<?php
require_once '../config/auth.php';
requiere_login_api();

// Solo los administradores pueden eliminar categorías
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado. Se requieren permisos de administrador.']);
    exit;
}

require_once '../config/database.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = json_decode(file_get_contents("php://input"), true);
    $nombre = $datos['nombre'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE nombre = ?");
        $stmt->execute([$nombre]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error']);
    }
}
?>