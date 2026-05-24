<?php
// api/buscar.php
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

try {
    if (isset($_GET['q'])) {
        $busqueda = '%' . $_GET['q'] . '%';
        
        $stmt = $pdo->prepare("SELECT * FROM componentes WHERE nombre LIKE :q OR codigo LIKE :q LIMIT 10");
        $stmt->bindParam(':q', $busqueda);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($resultados);
    }
} catch (PDOException $e) {
    error_log("Error de BD en buscar.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error interno al procesar la solicitud.']);
}
?>