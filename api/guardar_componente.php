<?php
// api/guardar_componente.php
require_once '../config/auth.php';
requiere_login_api();

// Solo los administradores pueden guardar o modificar componentes
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado. Se requieren permisos de administrador.']);
    exit;
}

require_once '../config/database.php';
require_once '../config/image_helper.php';

function sanitizar($texto) {
    if ($texto === null) return '';
    return htmlspecialchars(trim($texto), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Función validarImagen removida - se utiliza config/image_helper.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['componente_id'] ?? null;
    $codigo = sanitizar($_POST['codigo'] ?? '');
    $categoria = sanitizar($_POST['categoria'] ?? '');
    $marca = sanitizar($_POST['marca'] ?? '');
    $descripcion = sanitizar($_POST['descripcion'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $stock_minimo = (int)($_POST['stock_minimo'] ?? 0);
    
    $imagen_path = null;

    // Lógica para subir la imagen
    if (isset($_FILES['imagen_upload']) && $_FILES['imagen_upload']['error'] === UPLOAD_ERR_OK) {
        try {
            $nombre_archivo = optimizarYGuardarImagen($_FILES['imagen_upload'], '../uploads/');
            $imagen_path = 'uploads/' . $nombre_archivo;
        } catch (Exception $imgEx) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Error en la imagen: ' . $imgEx->getMessage()]);
            exit;
        }
    }

    try {
        if (empty($id)) {
            // ES UN NUEVO COMPONENTE (INSERT) - familia eliminada, stock_minimo añadido
            $sql = "INSERT INTO componentes (codigo, categoria, marca, descripcion, stock, stock_minimo, imagen_path) 
                    VALUES (:codigo, :categoria, :marca, :descripcion, :stock, :stock_minimo, :imagen_path)";
            $stmt = $pdo->prepare($sql);
        } else {
            // ESTÁ ACTUALIZANDO UN COMPONENTE EXISTENTE (UPDATE) - familia eliminada, stock_minimo añadido
            if ($imagen_path) {
                // Obtener y eliminar la imagen anterior para no dejarla huérfana
                $stmtOld = $pdo->prepare("SELECT imagen_path FROM componentes WHERE id = ?");
                $stmtOld->execute([$id]);
                $old_image = $stmtOld->fetchColumn();
                if ($old_image) {
                    eliminarImagenFisica($old_image, '../');
                }
            }

            $sql_img = $imagen_path ? ", imagen_path = :imagen_path" : "";
            $sql = "UPDATE componentes SET codigo = :codigo, categoria = :categoria, marca = :marca, 
                    descripcion = :descripcion, stock = :stock, stock_minimo = :stock_minimo $sql_img WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
        }

        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':marca', $marca);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':stock_minimo', $stock_minimo);
        
        if ($imagen_path || empty($id)) {
            $stmt->bindParam(':imagen_path', $imagen_path);
        }

        $stmt->execute();
        echo json_encode(['status' => 'success', 'mensaje' => 'Guardado correctamente']);

    } catch (PDOException $e) {
        error_log("Error de BD en guardar_componente.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
    }
}
?>