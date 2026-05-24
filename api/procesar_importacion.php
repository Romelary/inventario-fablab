<?php
// api/procesar_importacion.php
header('Content-Type: application/json');
error_reporting(0); 
require_once '../config/auth.php';
requiere_login_api();
require_once '../config/database.php';

function sanitizar($texto) {
    if ($texto === null) return '';
    return htmlspecialchars(trim($texto), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

try { 
    $pdo->exec("CREATE TABLE IF NOT EXISTS categorias (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT UNIQUE)"); 
} catch (Exception $e) {}

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado. Se requieren permisos de administrador para realizar importación masiva.']);
    exit;
}

if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['archivo_csv']['tmp_name'];
    
    // Auto-detectar delimitador (, o ;)
    $first_line = '';
    $f_temp = fopen($archivo, "r");
    if ($f_temp) {
        $first_line = fgets($f_temp);
        fclose($f_temp);
    }
    $comma_count = substr_count($first_line, ',');
    $semicolon_count = substr_count($first_line, ';');
    $delimiter = ($semicolon_count >= $comma_count) ? ';' : ',';

    $handle = fopen($archivo, "r");

    if ($handle !== FALSE) {
        try {
            $pdo->beginTransaction();
            fgetcsv($handle, 1000, $delimiter); // Salta cabecera
            
            $contador = 0;
            $categorias_nuevas = []; 

            while (($raw_data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                
                // 👇 MAGIA: Limpia y convierte cada celda del Excel a formato web UTF-8 si no es ya UTF-8
                $data = array_map(function($val) {
                    $trimmed = trim($val);
                    if (mb_check_encoding($trimmed, 'UTF-8')) {
                        return $trimmed;
                    }
                    return mb_convert_encoding($trimmed, 'UTF-8', 'Windows-1252');
                }, $raw_data);

                $data = array_pad($data, 8, '');

                $nombre = sanitizar($data[0]);
                if (empty($nombre)) continue; 
                
                $categoria = sanitizar($data[1] ?: 'General');
                if (!in_array($categoria, $categorias_nuevas)) $categorias_nuevas[] = $categoria;

                $marca = sanitizar($data[2]);
                // $data[3] es la familia, la cual ignoramos permanentemente
                $descripcion = sanitizar($data[4]);
                $stock = (int)($data[5] ?: 0); 
                $etiqueta = sanitizar($data[6]);
                
                $tipo_item = sanitizar(ucfirst(strtolower($data[7])));
                if (!in_array($tipo_item, ['Herramienta', 'Insumo', 'Maquina', 'Equipos Menores'])) $tipo_item = 'Herramienta';
                
                $limite = ($tipo_item === 'Maquina') ? 100 : null;

                // familia removida permanentemente del INSERT
                $stmt = $pdo->prepare("INSERT INTO componentes (nombre, categoria, marca, descripcion, stock, etiqueta, tipo_item, limite_mantenimiento) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nombre, $categoria, $marca, $descripcion, $stock, $etiqueta, $tipo_item, $limite]);
                
                if ($stock > 0) {
                    $stmtK = $pdo->prepare("INSERT INTO kardex (componente_id, tipo_movimiento, cantidad, motivo) VALUES (?, 'Ingreso Inicial', ?, 'Importación CSV')");
                    $stmtK->execute([$pdo->lastInsertId(), $stock]);
                }
                $contador++;
            }
            
            // Inyectar categorías al menú
            if (count($categorias_nuevas) > 0) {
                $stmtCat = $pdo->prepare("INSERT OR IGNORE INTO categorias (nombre) VALUES (?)");
                foreach ($categorias_nuevas as $cat) { $stmtCat->execute([$cat]); }
            }

            fclose($handle);
            $pdo->commit();
            echo json_encode(['status' => 'success', 'mensaje' => "¡Éxito! Se importaron $contador ítems y se actualizaron las categorías."]);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            fclose($handle);
            error_log("Error de BD en procesar_importacion.php: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'mensaje' => 'Error interno al procesar la solicitud.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            fclose($handle);
            echo json_encode(['status' => 'error', 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo leer el archivo.']);
    }
} else {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error al subir.']);
}
?>