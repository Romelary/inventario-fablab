<?php
// api/procesar_consumo.php
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

function sanitizar($texto) {
    if ($texto === null) return '';
    return htmlspecialchars(trim($texto), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $componente_id = $_POST['componente_id'] ?? null;
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $solicitante = sanitizar($_POST['solicitante'] ?? '');
    $proyecto = sanitizar($_POST['proyecto'] ?? '');

    if (!$componente_id || $cantidad <= 0 || empty($solicitante)) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos obligatorios o la cantidad es inválida.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Verificar stock actual
        $stmtInfo = $pdo->prepare("SELECT nombre, stock FROM componentes WHERE id = ?");
        $stmtInfo->execute([$componente_id]);
        $item = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new Exception("El ítem seleccionado no existe.");
        }
        if ($item['stock'] < $cantidad) {
            throw new Exception("No hay suficiente stock. Stock actual: " . $item['stock']);
        }

        // 2. Descontar el stock del inventario
        $stmtUpdate = $pdo->prepare("UPDATE componentes SET stock = stock - ? WHERE id = ?");
        $stmtUpdate->execute([$cantidad, $componente_id]);

        // 3. Registrar en la tabla de consumos
        $stmtConsumo = $pdo->prepare("INSERT INTO consumos (componente_id, cantidad, solicitante, proyecto, usuario_registro) VALUES (?, ?, ?, ?, ?)");
        $stmtConsumo->execute([$componente_id, $cantidad, $solicitante, $proyecto, $_SESSION['usuario_username']]);

        // 4. Registrar en el Kardex (Historial global)
        $motivo = "Consumo: $proyecto (Solicita: $solicitante)";
        $stmtKardex = $pdo->prepare("INSERT INTO kardex (componente_id, tipo_movimiento, cantidad, motivo) VALUES (?, 'Salida por Consumo', ?, ?)");
        $stmtKardex->execute([$componente_id, $cantidad, $motivo]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'mensaje' => 'Consumo registrado y stock descontado correctamente.']);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error de BD en procesar_consumo.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
?>