<?php
// api/listar_componentes.php
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

$categoria = $_GET['categoria'] ?? '';

try {
    if ($categoria !== '') {
        $stmt = $pdo->prepare("SELECT * FROM componentes WHERE categoria = :categoria ORDER BY nombre ASC");
        $stmt->bindParam(':categoria', $categoria);
        $stmt->execute();
    } else {
        $stmt = $pdo->query("SELECT * FROM componentes ORDER BY id DESC");
    }
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 👇 EL ANTÍDOTO: Forzamos la conversión a UTF-8 si no está en UTF-8 para que JSON no crachee con las tildes de Excel
    array_walk_recursive($resultados, function(&$item){
        if (is_string($item)) {
            if (!mb_check_encoding($item, 'UTF-8')) {
                // Convierte caracteres de Windows a estándar Web
                $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1252'); 
            }
        }
    });
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Verificamos si JSON logra codificar
    $json = json_encode($resultados);
    if ($json === false) {
        echo json_encode([['nombre' => 'Error de codificación', 'categoria' => 'Error', 'stock' => 0, 'etiqueta' => json_last_error_msg()]]);
        exit;
    }
    
    echo $json;

} catch (PDOException $e) {
    error_log("Error de BD en listar_componentes.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error interno al procesar la solicitud.']);
}
?>