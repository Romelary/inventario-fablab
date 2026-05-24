<?php
// api/procesar_recepcion.php
header('Content-Type: application/json');
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

// Parches silenciosos para la base de datos (por si no existen)
try { $pdo->exec("ALTER TABLE prestamos ADD COLUMN fecha_prestamo DATETIME DEFAULT CURRENT_TIMESTAMP"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE detalle_prestamo ADD COLUMN devueltos INTEGER DEFAULT 0"); } catch (Exception $e) {}

$datos = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$accion = $datos['accion'] ?? '';

try {
    // 1. Listar préstamos que aún no se devuelven por completo
    if ($accion === 'listar_pendientes') {
        $stmt = $pdo->query("SELECT id, dni_alumno, nombre_apellido, proyecto, estado, fecha_prestamo 
                             FROM prestamos 
                             WHERE estado IN ('Pendiente', 'Parcial') 
                             ORDER BY fecha_prestamo DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Traer las herramientas exactas que debe ese préstamo
    if ($accion === 'obtener_detalles') {
        $prestamo_id = $datos['prestamo_id'];
        $stmt = $pdo->prepare("
            SELECT dp.id as detalle_id, c.id as componente_id, c.nombre, c.imagen_path, c.etiqueta, dp.cantidad, dp.devueltos 
            FROM detalle_prestamo dp 
            JOIN componentes c ON dp.componente_id = c.id 
            WHERE dp.prestamo_id = ? AND dp.cantidad > dp.devueltos
        ");
        $stmt->execute([$prestamo_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 3. Procesar lo que el alumno está entregando en mesa
    if ($accion === 'procesar_devolucion') {
        $prestamo_id = $datos['prestamo_id'];
        $items = $datos['items']; // Lista de lo que está devolviendo

        $pdo->beginTransaction();

        foreach ($items as $item) {
            $cant_devuelta = (int)$item['cantidad_devuelta'];
            if ($cant_devuelta <= 0) continue;

            // A. Actualizar la tabla de detalle (marcar cuántos devolvió)
            $stmtDet = $pdo->prepare("UPDATE detalle_prestamo SET devueltos = devueltos + ? WHERE id = ?");
            $stmtDet->execute([$cant_devuelta, $item['detalle_id']]);

            // B. Regresar el stock físico al inventario
            $stmtStock = $pdo->prepare("UPDATE componentes SET stock = stock + ? WHERE id = ?");
            $stmtStock->execute([$cant_devuelta, $item['componente_id']]);

            // C. Dejar huella en el Kardex
            $motivo = "Devolución de Préstamo #$prestamo_id - Reg: " . $_SESSION['usuario_username'];
            $stmtK = $pdo->prepare("INSERT INTO kardex (componente_id, tipo_movimiento, cantidad, motivo) VALUES (?, 'Ingreso por Devolución', ?, ?)");
            $stmtK->execute([$item['componente_id'], $cant_devuelta, $motivo]);
        }

        // 4. Verificar si con esta entrega ya canceló toda su deuda
        $stmtCheck = $pdo->prepare("SELECT SUM(cantidad) as total, SUM(devueltos) as devueltos FROM detalle_prestamo WHERE prestamo_id = ?");
        $stmtCheck->execute([$prestamo_id]);
        $totales = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($totales['total'] <= $totales['devueltos']) {
            $pdo->prepare("UPDATE prestamos SET estado = 'Devuelto' WHERE id = ?")->execute([$prestamo_id]);
        } else {
            $pdo->prepare("UPDATE prestamos SET estado = 'Parcial' WHERE id = ?")->execute([$prestamo_id]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'mensaje' => 'Herramientas devueltas al inventario correctamente.']);
        exit;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error de BD en procesar_recepcion.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>