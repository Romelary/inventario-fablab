<?php
// api/obtener_historial.php
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

try {
    $sql = "SELECT 
                k.id, 
                COALESCE(c.nombre, '--- Ítem Eliminado ---') AS componente, 
                k.tipo_movimiento, 
                k.cantidad, 
                k.motivo, -- <--- Agregamos esta columna
                k.fecha 
            FROM kardex k
            LEFT JOIN componentes c ON k.componente_id = c.id
            ORDER BY k.fecha DESC 
            LIMIT 100";
            
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    error_log("Error de BD en obtener_historial.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno al procesar la solicitud.']);
}
?>