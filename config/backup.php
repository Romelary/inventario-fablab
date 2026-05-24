<?php
// config/backup.php

function generarBackup() {
    $db_file = __DIR__ . '/../data/inventario.sqlite';
    $backup_folder = __DIR__ . '/../backups';

    if (!is_dir($backup_folder)) {
        mkdir($backup_folder, 0777, true);
    }

    if (file_exists($db_file)) {
        $fecha = date('Y-m-d_H-i-s');
        $destino = $backup_folder . '/backup_' . $fecha . '.sqlite';
        
        if (copy($db_file, $destino)) {
            return "Backup generado exitosamente: " . basename($destino);
        } else {
            return "Error al generar el backup.";
        }
    }
    return "No hay base de datos para respaldar.";
}

// prueba de generación de backup
//echo generarBackup();
?>