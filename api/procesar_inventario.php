<?php
// api/procesar_inventario.php
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';
require_once '../config/image_helper.php';

function sanitizar($texto) {
    if ($texto === null) return '';
    return htmlspecialchars(trim($texto), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Función validarImagen removida - se utiliza config/image_helper.php

if ($_POST) {
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado. Se requieren permisos de administrador para modificar el inventario.']);
        exit;
    }

    $id = $_POST['id'] ?? null;
    $nombre = sanitizar($_POST['nombre'] ?? '');
    $ajuste = isset($_POST['ajuste_stock']) ? (int)$_POST['ajuste_stock'] : 0;
    $stock_minimo = isset($_POST['stock_minimo']) ? (int)$_POST['stock_minimo'] : 0;
    $tipo_accion = $_POST['accion'] ?? '';
    
    $categoria = sanitizar($_POST['categoria'] ?? '');
    $descripcion = sanitizar($_POST['descripcion'] ?? '');
    $marca = sanitizar($_POST['marca'] ?? '');
    $etiqueta = sanitizar($_POST['etiqueta'] ?? ''); 
    $tipo_item = sanitizar($_POST['tipo_item'] ?? 'Herramienta'); 
    $limite_mantenimiento = isset($_POST['limite_mantenimiento']) ? (float)$_POST['limite_mantenimiento'] : 100; 
    
    $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $fecha_recepcion = !empty($_POST['fecha_recepcion']) ? $_POST['fecha_recepcion'] : null;

    try {
        $pdo->beginTransaction();

        if ($tipo_accion === 'eliminar') {
            if (!$id) throw new Exception("No se proporcionó un ID para eliminar.");
            $stmtInfo = $pdo->prepare("SELECT nombre, stock, imagen_path FROM componentes WHERE id = ?");
            $stmtInfo->execute([$id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            
            if ($info) {
                // Eliminar archivo físico de la imagen si existe
                if (!empty($info['imagen_path'])) {
                    eliminarImagenFisica($info['imagen_path'], '../');
                }

                $movimiento = "❌ Eliminado permanentemente: " . $info['nombre'];
                $motivo = "Eliminado por: " . $_SESSION['usuario_username'];
                $pdo->prepare("INSERT INTO kardex (componente_id, tipo_movimiento, cantidad, motivo) VALUES (?, ?, ?, ?)")->execute([$id, $movimiento, $info['stock'], $motivo]);
            }
            
            $pdo->prepare("DELETE FROM componentes WHERE id = ?")->execute([$id]);
            $pdo->commit();
            echo json_encode(['status' => 'success', 'mensaje' => 'Ítem eliminado correctamente']);
            exit;
        }

        $imagen_path = null;
        if (isset($_FILES['imagen_upload']) && $_FILES['imagen_upload']['error'] === UPLOAD_ERR_OK) {
            $nombre_img = optimizarYGuardarImagen($_FILES['imagen_upload'], '../uploads/');
            $imagen_path = 'uploads/' . $nombre_img;
        }

        if ($tipo_accion === 'nuevo') {
            // Validar si ya existe un ítem con el mismo nombre y área (categoría)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM componentes WHERE LOWER(nombre) = LOWER(?) AND LOWER(categoria) = LOWER(?)");
            $stmtCheck->execute([$nombre, $categoria]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Ya existe un ítem registrado con el nombre '$nombre' en el área '$categoria'.");
            }

            $stmt = $pdo->prepare("INSERT INTO componentes (nombre, categoria, marca, descripcion, stock, stock_minimo, imagen_path, etiqueta, tipo_item, limite_mantenimiento, fecha_vencimiento, fecha_recepcion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $categoria, $marca, $descripcion, $ajuste, $stock_minimo, $imagen_path, $etiqueta, $tipo_item, $limite_mantenimiento, $fecha_vencimiento, $fecha_recepcion]);
            $componente_id = $pdo->lastInsertId();
            
            if ($ajuste != 0) {
                $motivo = "Creado por: " . $_SESSION['usuario_username'];
                $pdo->prepare("INSERT INTO kardex (componente_id, tipo_movimiento, cantidad, motivo) VALUES (?, 'Ingreso Inicial', ?, ?)")->execute([$componente_id, abs($ajuste), $motivo]);
            }

        } else if ($tipo_accion === 'actualizar') {
            if (!$id) throw new Exception("No se proporcionó un ID para actualizar.");
            $componente_id = $id;

            // Validar duplicados si cambia el nombre a uno existente en la misma categoría
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM componentes WHERE LOWER(nombre) = LOWER(?) AND LOWER(categoria) = LOWER(?) AND id <> ?");
            $stmtCheck->execute([$nombre, $categoria, $componente_id]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Ya existe otro ítem con el nombre '$nombre' en el área '$categoria'.");
            }

            if ($imagen_path) {
                // Obtener y eliminar la imagen anterior para no dejarla huérfana
                $stmtOld = $pdo->prepare("SELECT imagen_path FROM componentes WHERE id = ?");
                $stmtOld->execute([$componente_id]);
                $old_image = $stmtOld->fetchColumn();
                if ($old_image) {
                    eliminarImagenFisica($old_image, '../');
                }

                $stmt = $pdo->prepare("UPDATE componentes SET nombre=?, categoria=?, marca=?, descripcion=?, stock=stock+?, stock_minimo=?, imagen_path=?, etiqueta=?, tipo_item=?, limite_mantenimiento=?, fecha_vencimiento=?, fecha_recepcion=? WHERE id=?");
                $stmt->execute([$nombre, $categoria, $marca, $descripcion, $ajuste, $stock_minimo, $imagen_path, $etiqueta, $tipo_item, $limite_mantenimiento, $fecha_vencimiento, $fecha_recepcion, $componente_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE componentes SET nombre=?, categoria=?, marca=?, descripcion=?, stock=stock+?, stock_minimo=?, etiqueta=?, tipo_item=?, limite_mantenimiento=?, fecha_vencimiento=?, fecha_recepcion=? WHERE id=?");
                $stmt->execute([$nombre, $categoria, $marca, $descripcion, $ajuste, $stock_minimo, $etiqueta, $tipo_item, $limite_mantenimiento, $fecha_vencimiento, $fecha_recepcion, $componente_id]);
            }

            if ($ajuste != 0) {
                $tipo_mov = ($ajuste > 0) ? 'Ajuste Positivo' : 'Ajuste Negativo';
                $motivo = "Ajustado por: " . $_SESSION['usuario_username'];
                $pdo->prepare("INSERT INTO kardex (componente_id, tipo_movimiento, cantidad, motivo) VALUES (?, ?, ?, ?)")->execute([$componente_id, $tipo_mov, abs($ajuste), $motivo]);
            }
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'mensaje' => 'Operación completada con éxito']);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error de BD en procesar_inventario.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
    }
}
?>