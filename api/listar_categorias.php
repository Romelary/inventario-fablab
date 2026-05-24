<?php
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';
try {
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    error_log("Error de BD en listar_categorias.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno al procesar la solicitud.']);
}
?>