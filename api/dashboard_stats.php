<?php
// api/dashboard_stats.php
header('Content-Type: application/json');
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

try {
    $stats = [];

    // 1. Préstamos sin devolver (Pendientes o Parciales)
    $stmtPrestamos = $pdo->query("SELECT COUNT(*) as total FROM prestamos WHERE estado IN ('Pendiente', 'Parcial')");
    $stats['prestamos_pendientes'] = $stmtPrestamos->fetchColumn();

    // 2. Máquinas bloqueadas o en alerta roja
    $stmtMaquinas = $pdo->query("SELECT COUNT(*) as total FROM componentes WHERE tipo_item = 'Maquina' AND estado = 'Requiere Mantenimiento'");
    $stats['maquinas_alerta'] = $stmtMaquinas->fetchColumn();

    // 3. Herramientas o Insumos que se están agotando (Stock <= stock_minimo)
    $stmtStock = $pdo->query("SELECT COUNT(*) as total FROM componentes WHERE tipo_item IN ('Herramienta', 'Insumo') AND stock <= stock_minimo");
    $stats['stock_bajo'] = $stmtStock->fetchColumn();

    // 4. Total de ítems registrados en el sistema
    $stmtEquipos = $pdo->query("SELECT COUNT(*) as total FROM componentes");
    $stats['total_equipos'] = $stmtEquipos->fetchColumn();

    echo json_encode(['status' => 'success', 'data' => $stats]);
} catch (PDOException $e) {
    error_log("Error de BD en dashboard_stats.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>