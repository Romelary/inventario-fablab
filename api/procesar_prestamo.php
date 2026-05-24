<?php
// parte de la ventana de prestamos 
// api/procesar_prestamo.php
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

function sanitizar($texto) {
    if ($texto === null) return '';
    return htmlspecialchars(trim($texto), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibimos los datos en formato JSON porque enviaremos un "carrito" completo
    $datos = json_decode(file_get_contents("php://input"), true);
    
    $dni = sanitizar($datos['dni_alumno'] ?? '');
    $nombre_alumno = sanitizar($datos['nombre_apellido'] ?? '');
    $proyecto = sanitizar($datos['proyecto'] ?? '');
    $carrito = $datos['carrito'] ?? []; // Es un array con las herramientas

    if (empty($dni) || empty($nombre_alumno) || empty($carrito)) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos del alumno o el carrito está vacío.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Crear el Préstamo General (Cabecera)
        $stmtPrestamo = $pdo->prepare("INSERT INTO prestamos (dni_alumno, nombre_apellido, proyecto, estado) VALUES (?, ?, ?, 'Pendiente')");
        $stmtPrestamo->execute([$dni, $nombre_alumno, $proyecto]);
        $prestamo_id = $pdo->lastInsertId();

        // 2. Procesar cada ítem del carrito
        foreach ($carrito as $item) {
            $comp_id = $item['id'];
            $cantidad = (int)$item['cantidad'];

            // A. Validar stock actual (por si alguien más lo prestó un segundo antes)
            $stmtInfo = $pdo->prepare("SELECT nombre, stock FROM componentes WHERE id = ?");
            $stmtInfo->execute([$comp_id]);
            $comp = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if (!$comp || $comp['stock'] < $cantidad) {
                throw new Exception("No hay suficiente stock para: " . ($comp['nombre'] ?? 'Ítem desconocido'));
            }

            // B. Guardar en detalle_prestamo
            $stmtDetalle = $pdo->prepare("INSERT INTO detalle_prestamo (prestamo_id, componente_id, cantidad) VALUES (?, ?, ?)");
            $stmtDetalle->execute([$prestamo_id, $comp_id, $cantidad]);

            // C. Descontar stock (Se resta porque sale del almacén temporalmente)
            $stmtUpdate = $pdo->prepare("UPDATE componentes SET stock = stock - ? WHERE id = ?");
            $stmtUpdate->execute([$cantidad, $comp_id]);

            // D. Registrar en el Kardex
            $motivo = "Préstamo #$prestamo_id - $proyecto (DNI: $dni) - Reg: " . $_SESSION['usuario_username'];
            $stmtKardex = $pdo->prepare("INSERT INTO kardex (componente_id, tipo_movimiento, cantidad, motivo) VALUES (?, 'Salida por Préstamo', ?, ?)");
            $stmtKardex->execute([$comp_id, $cantidad, $motivo]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'mensaje' => "Préstamo #$prestamo_id registrado correctamente."]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error de BD en procesar_prestamo.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
?>