<?php
// config/database.php

$db_folder = __DIR__ . '/../data';
$db_file = $db_folder . '/inventario.sqlite';

if (!is_dir($db_folder)) {
    mkdir($db_folder, 0777, true);
}

$is_new_db = !file_exists($db_file);

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Habilitar soporte de llaves foráneas para esta conexión en SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");

    if ($is_new_db) {
        require_once __DIR__ . '/setup_db.php';
        setup_database($pdo);
    } else {
        // Ejecutar migración incremental para Kardex ON DELETE SET NULL si no existe
        $has_migration = false;
        try {
            $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='_migrations'")->fetchColumn();
            if ($tableExists) {
                $stmt = $pdo->prepare("SELECT 1 FROM _migrations WHERE version = ?");
                $stmt->execute(['1.0.1']);
                $has_migration = (bool)$stmt->fetchColumn();
            }
        } catch (Exception $migEx) {
            $has_migration = false;
        }

        if (!$has_migration) {
            require_once __DIR__ . '/migrar_kardex_fk.php';
            ejecutar_migracion_kardex($pdo);
        }
    }

    // Asegurarse de que la tabla de usuarios exista (para BDs existentes)
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetchColumn();
    if (!$tableExists) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            nombre TEXT,
            rol TEXT DEFAULT 'admin' CHECK(rol IN ('admin', 'usuario')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_usuarios_username ON usuarios(username);");
    }

    // Crear usuario admin principal por defecto
    $stmtCheck1 = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = 1 OR username = 'admin'");
    $stmtCheck1->execute();
    if ($stmtCheck1->fetchColumn() == 0) {
        $username = 'admin';
        $password = 'admin12345';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $nombre = 'Administrador Principal';
        $rol = 'admin';
        
        $stmtInsert = $pdo->prepare("INSERT INTO usuarios (id, username, password_hash, nombre, rol) VALUES (1, ?, ?, ?, ?)");
        $stmtInsert->execute([$username, $hash, $nombre, $rol]);
    }

    // Crear usuario admin secundario por defecto
    $stmtCheck2 = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = 2 OR username = 'admin2'");
    $stmtCheck2->execute();
    if ($stmtCheck2->fetchColumn() == 0) {
        $username2 = 'admin2';
        $password2 = 'admin54321';
        $hash2 = password_hash($password2, PASSWORD_DEFAULT);
        $nombre2 = 'Administrador Auxiliar';
        $rol2 = 'admin';
        
        $stmtInsert2 = $pdo->prepare("INSERT INTO usuarios (id, username, password_hash, nombre, rol) VALUES (2, ?, ?, ?, ?)");
        $stmtInsert2->execute([$username2, $hash2, $nombre2, $rol2]);
    }
} catch (PDOException $e) {
    error_log("Error de conexión de base de datos SQLite: " . $e->getMessage());
    die("Error interno al procesar la solicitud.");
}
?>