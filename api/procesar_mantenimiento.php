<?php
// api/procesar_mantenimiento.php
header('Content-Type: application/json');
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

// Forzamos a PHP a usar la hora local
date_default_timezone_set('America/Lima');

try { 
    $pdo->exec("CREATE TABLE IF NOT EXISTS mantenimientos (id INTEGER PRIMARY KEY AUTOINCREMENT, componente_id INTEGER, tipo_mantenimiento TEXT, descripcion TEXT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, usuario_registro TEXT)"); 
    $pdo->exec("ALTER TABLE mantenimientos ADD COLUMN tiempo_trabajado DECIMAL(10,2) DEFAULT 0.00");
} catch (Exception $e) {}

$datos = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$accion = $datos['accion'] ?? '';

try {
    if ($accion === 'listar_maquinas') {
        $sql = "SELECT id, nombre, etiqueta, estado, horas_uso, limite_mantenimiento 
                FROM componentes 
                WHERE tipo_item = 'Maquina' 
                ORDER BY CASE WHEN estado = 'Requiere Mantenimiento' THEN 1 ELSE 2 END, horas_uso DESC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($accion === 'registrar_mantenimiento') {
        $hora_actual = date('Y-m-d H:i:s'); // Capturamos la hora exacta
        
        $maquina_id = $datos['maquina_id'];
        $tecnico = trim($datos['tecnico']);
        $descripcion = trim($datos['descripcion']);
        $tipo = $datos['tipo']; 
        $insumos = $datos['insumos'] ?? []; 
        
        $h = isset($datos['horas']) ? (int)$datos['horas'] : 0;
        $m = isset($datos['minutos']) ? (int)$datos['minutos'] : 0;
        $s = isset($datos['segundos']) ? (int)$datos['segundos'] : 0;

        if (!$maquina_id || empty($tecnico) || empty($descripcion)) {
            throw new Exception("Faltan datos obligatorios del reporte.");
        }

        $pdo->beginTransaction();

        $tiempo_decimal = $h + ($m / 60) + ($s / 3600);

        // A. Guardar el reporte con la FECHA EXPLÍCITA
        $stmtMaint = $pdo->prepare("INSERT INTO mantenimientos (componente_id, tipo_mantenimiento, descripcion, usuario_registro, tiempo_trabajado, fecha) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtMaint->execute([$maquina_id, $tipo, $descripcion, $tecnico, $tiempo_decimal, $hora_actual]);
        $reporte_id = $pdo->lastInsertId();

        // B. Reseteo de máquina
        $stmtReset = $pdo->prepare("UPDATE componentes SET estado = 'Operativo', horas_uso = 0 WHERE id = ?");
        $stmtReset->execute([$maquina_id]);

        $stmtNom = $pdo->prepare("SELECT nombre FROM componentes WHERE id = ?");
        $stmtNom->execute([$maquina_id]);
        $nomMaq = $stmtNom->fetchColumn();

        // C. Procesar insumos con la FECHA EXPLÍCITA
        foreach ($insumos as $item) {
            $insumo_id = $item['id'];
            $cantidad = (int)$item['cantidad'];

            $pdo->prepare("UPDATE componentes SET stock = stock - ? WHERE id = ?")->execute([$cantidad, $insumo_id]);

            $pdo->prepare("INSERT INTO consumos (componente_id, cantidad, solicitante, proyecto, fecha, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$insumo_id, $cantidad, $tecnico, "Mantenimiento: $nomMaq (Rep #$reporte_id)", $hora_actual, $_SESSION['usuario_username']]);

            $motivo = "Mantenimiento de $nomMaq (Téc: $tecnico) - Reg: " . $_SESSION['usuario_username'];
            $pdo->prepare("INSERT INTO kardex (componente_id, tipo_movimiento, cantidad, motivo, fecha) VALUES (?, 'Salida por Mantenimiento', ?, ?, ?)")
                ->execute([$insumo_id, $cantidad, $motivo, $hora_actual]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'mensaje' => 'Mantenimiento registrado con éxito. Reloj de máquina reiniciado a cero.']);
        exit;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error de BD en procesar_mantenimiento.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>