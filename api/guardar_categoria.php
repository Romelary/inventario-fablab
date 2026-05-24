<?php
// api/guardar_categoria.php
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

function sanitizar($texto) {
    if ($texto === null) return '';
    return htmlspecialchars(trim($texto), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nombre'])) {
    $nombre = sanitizar($_POST['nombre']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->execute();
        
        echo json_encode(['status' => 'success', 'mensaje' => 'Categoría agregada']);
    } catch (PDOException $e) {
        error_log("Error de BD en guardar_categoria.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
?>