<?php
// api/exportar_csv.php
require_once '../config/auth.php';
requiere_rol('admin');
require_once '../config/database.php';

$tabla = $_GET['tabla'] ?? 'componentes';

// Whitelist de tablas permitidas
$allowed_tables = ['componentes', 'kardex', 'mantenimientos', 'bitacora_maquinas'];
if (!in_array($tabla, $allowed_tables)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'mensaje' => 'Tabla no permitida o no existe.']);
    exit;
}

$nombre_archivo = "Reporte_" . ucfirst($tabla) . "_" . date('Y-m-d') . ".csv";

try {
    $stmt = $pdo->query("SELECT * FROM $tabla ORDER BY id DESC");
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($datos)) {
        die("No hay datos para exportar en la tabla $tabla.");
    }

    // 👇 MAGIA DE LIMPIEZA PARA EL INVENTARIO 👇
    if ($tabla === 'componentes') {
        foreach ($datos as &$fila) {
            // 1. Eliminamos columnas que no sirven en el reporte gerencial
            unset($fila['horas_uso']);
            unset($fila['familia']);
            unset($fila['imagen_path']); // Tampoco sirve exportar la ruta de la imagen
            
            // 2. Renombramos la columna Categoria a Area para el Excel
            $fila['area'] = $fila['categoria'];
            unset($fila['categoria']);
        }
        unset($fila); // Romper la referencia
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Cabeceras
    fputcsv($output, array_keys($datos[0]), ";");

    // Datos
    foreach ($datos as $fila) {
        fputcsv($output, $fila, ";");
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    error_log("Error de BD en exportar_csv.php: " . $e->getMessage());
    http_response_code(500);
    die("Error interno al procesar la solicitud.");
} catch (Exception $e) {
    http_response_code(500);
    die("Error al exportar: " . $e->getMessage());
}
?>