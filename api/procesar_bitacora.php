<?php
header('Content-Type: application/json'); 
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

function sanitizar($texto) {
    if ($texto === null) return '';
    return htmlspecialchars(trim($texto), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Forzamos a PHP a usar la hora local globalmente
date_default_timezone_set('America/Lima');

$accion = $_POST['accion'] ?? '';

try {
    if ($accion === 'listar') {
        $sql = "SELECT id, nombre, marca, etiqueta, estado, horas_uso, limite_mantenimiento, descripcion 
                FROM componentes WHERE tipo_item = 'Maquina' ORDER BY nombre ASC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($accion === 'historial') {
        $sql = "SELECT b.id, c.nombre AS maquina, c.descripcion AS maquina_desc, b.solicitante, b.hora_inicio AS fecha, b.observaciones 
                FROM bitacora_maquinas b
                JOIN componentes c ON b.maquina_id = c.id
                ORDER BY b.hora_inicio DESC 
                LIMIT 100";
        $stmt = $pdo->query($sql);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultados);
        exit;
    }

    if ($accion === 'registrar_uso') {
        $pdo->beginTransaction();
        $hora_local_peru = date('Y-m-d H:i:s'); // Hora exacta
        
        $maquina_id = $_POST['maquina_id'];
        $solicitante = sanitizar($_POST['solicitante']);
        $h = isset($_POST['horas']) ? (int)$_POST['horas'] : 0;
        $m = isset($_POST['minutos']) ? (int)$_POST['minutos'] : 0;
        $s = isset($_POST['segundos']) ? (int)$_POST['segundos'] : 0;
        $observaciones = sanitizar($_POST['observaciones'] ?? '');

        if (!$maquina_id || empty($solicitante) || ($h === 0 && $m === 0 && $s === 0)) {
            throw new Exception("Datos incompletos o tiempo en cero.");
        }

        $horas_sumar = $h + ($m / 60) + ($s / 3600);
        $tiempo_texto = sprintf("%02dh %02dm %02ds", $h, $m, $s);
        $detalle_final = "Uso: $tiempo_texto. Nota: $observaciones";

        // 1. Guardar uso con fecha explícita y registrando el usuario activo
        $stmt = $pdo->prepare("INSERT INTO bitacora_maquinas (maquina_id, solicitante, hora_inicio, hora_fin, observaciones, tiempo_horas, usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$maquina_id, $solicitante, $hora_local_peru, $hora_local_peru, $detalle_final, $horas_sumar, $_SESSION['usuario_username']]);

        $stmtUpdate = $pdo->prepare("UPDATE componentes SET horas_uso = horas_uso + ? WHERE id = ?");
        $stmtUpdate->execute([$horas_sumar, $maquina_id]);

        $stmtMaint = $pdo->prepare("SELECT estado, horas_uso, limite_mantenimiento FROM componentes WHERE id = ?");
        $stmtMaint->execute([$maquina_id]);
        $m_data = $stmtMaint->fetch(PDO::FETCH_ASSOC);

        $alerta = false;
        if ($m_data['horas_uso'] >= $m_data['limite_mantenimiento'] && $m_data['estado'] !== 'Requiere Mantenimiento') {
            $pdo->prepare("UPDATE componentes SET estado = 'Requiere Mantenimiento' WHERE id = ?")->execute([$maquina_id]);
            $msg = "ALERTA: Se alcanzó el límite de " . $m_data['limite_mantenimiento'] . " horas. (Acumulado actual: " . number_format($m_data['horas_uso'], 2) . "h).";
            
            // 2. Aquí también forzamos la fecha explícita para la alerta
            $pdo->prepare("INSERT INTO mantenimientos (componente_id, tipo_mantenimiento, descripcion, usuario_registro, fecha) VALUES (?, 'Preventivo', ?, 'Sistema', ?)")
                ->execute([$maquina_id, $msg, $hora_local_peru]);
            $alerta = true;
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'mantenimiento_requerido' => $alerta]);
        exit;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error de BD en procesar_bitacora.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>