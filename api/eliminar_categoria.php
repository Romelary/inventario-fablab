<?php
require_once '../config/auth.php';
requiere_login_api();
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