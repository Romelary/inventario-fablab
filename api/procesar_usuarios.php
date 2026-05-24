<?php
// api/procesar_usuarios.php
header('Content-Type: application/json');
require_once '../config/auth.php';
requiere_login_api();

// Solo los administradores pueden gestionar usuarios
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado. Se requieren permisos de administrador.']);
    exit;
}

require_once '../config/database.php';

function sanitizar($texto) {
    if ($texto === null) return '';
    return htmlspecialchars(trim($texto), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$datos = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$accion = $datos['accion'] ?? '';

try {
    if ($accion === 'listar') {
        $stmt = $pdo->query("SELECT id, username, nombre, rol, created_at FROM usuarios ORDER BY username ASC");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($usuarios);
        exit;
    }

    if ($accion === 'guardar') {
        $id = $datos['id'] ?? null;
        $username = strtolower(sanitizar($datos['username'] ?? ''));
        $nombre = sanitizar($datos['nombre'] ?? '');
        $rol = sanitizar($datos['rol'] ?? 'usuario');
        $password = $datos['password'] ?? '';

        if (empty($username) || empty($nombre) || !in_array($rol, ['admin', 'usuario'])) {
            throw new Exception("Por favor, complete todos los campos obligatorios y proporcione un rol válido.");
        }

        if (empty($id)) {
            // NUEVO USUARIO
            if (empty($password)) {
                throw new Exception("La contraseña es obligatoria para nuevos usuarios.");
            }

            // Verificar si el username ya existe
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ?");
            $stmtCheck->execute([$username]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("El nombre de usuario '$username' ya está registrado.");
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password_hash, nombre, rol) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $nombre, $rol]);

            echo json_encode(['status' => 'success', 'mensaje' => 'Usuario registrado exitosamente.']);
            exit;
        } else {
            // ACTUALIZAR USUARIO
            // Impedir que otros administradores modifiquen al administrador principal (id = 1)
            if ($id == 1 && $_SESSION['usuario_id'] != 1) {
                throw new Exception("El administrador principal (id = 1) no puede ser modificado por otros usuarios.");
            }

            // Impedir que el administrador activo se degrade a sí mismo
            if ($id == $_SESSION['usuario_id'] && $rol !== 'admin') {
                throw new Exception("No puedes cambiar tu propio rol o degradar tus privilegios de administrador.");
            }

            // Impedir degradar o cambiar el rol o el username del administrador principal (id = 1)
            if ($id == 1) {
                if ($rol !== 'admin') {
                    throw new Exception("El administrador principal (id = 1) no puede ser degradado de rol.");
                }
                if ($username !== 'admin') {
                    throw new Exception("El nombre de usuario del administrador principal ('admin') no puede ser modificado.");
                }
            }

            // Verificar si el username ya existe en otro usuario
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ? AND id <> ?");
            $stmtCheck->execute([$username, $id]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("El nombre de usuario '$username' ya está siendo usado por otra cuenta.");
            }

            if (!empty($password)) {
                // Si cambiaron la contraseña, actualizarla
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, password_hash = ?, nombre = ?, rol = ? WHERE id = ?");
                $stmt->execute([$username, $password_hash, $nombre, $rol, $id]);
            } else {
                // Si no se proporcionó contraseña, conservar la actual
                $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nombre = ?, rol = ? WHERE id = ?");
                $stmt->execute([$username, $nombre, $rol, $id]);
            }

            // Si el propio administrador actualizó sus datos de rol a 'usuario', advertir o impedir si no hay otros admins
            // Para simplicidad, solo actualizamos los datos de sesión del usuario activo si es él mismo
            if ($id == $_SESSION['usuario_id']) {
                $_SESSION['usuario_username'] = $username;
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_rol'] = $rol;
            }

            echo json_encode(['status' => 'success', 'mensaje' => 'Usuario actualizado correctamente.']);
            exit;
        }
    }

    if ($accion === 'eliminar') {
        $id = $datos['id'] ?? null;
        if (!$id) {
            throw new Exception("No se proporcionó el ID de usuario.");
        }

        // Impedir que el administrador principal (id = 1) sea eliminado
        if ($id == 1) {
            throw new Exception("El administrador principal (id = 1) no puede ser eliminado del sistema.");
        }

        // Impedir que un administrador se elimine a sí mismo
        if ($id == $_SESSION['usuario_id']) {
            throw new Exception("No puedes eliminar tu propia cuenta de administrador.");
        }

        // Opcional: Impedir eliminar si es el único administrador del sistema
        $stmtCheckAdmin = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
        $stmtCheckAdmin->execute();
        $totalAdmins = $stmtCheckAdmin->fetchColumn();

        $stmtTarget = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $stmtTarget->execute([$id]);
        $targetRol = $stmtTarget->fetchColumn();

        if ($targetRol === 'admin' && $totalAdmins <= 1) {
            throw new Exception("No se puede eliminar al único administrador del sistema.");
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'mensaje' => 'Usuario eliminado correctamente.']);
        exit;
    }

    throw new Exception("Acción no válida.");

} catch (PDOException $e) {
    error_log("Error de BD en procesar_usuarios.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la base de datos.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>
