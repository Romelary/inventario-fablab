<?php
// config/migrar_kardex_fk.php

function ejecutar_migracion_kardex(PDO $pdo) {
    try {
        $pdo->beginTransaction();

        // 1. Crear tabla nueva temporal
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kardex_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                componente_id INTEGER,
                tipo_movimiento TEXT NOT NULL,
                cantidad INTEGER NOT NULL CHECK(cantidad >= 0),
                motivo TEXT,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE SET NULL
            );
        ");

        // 2. Copiar registros si existe la tabla original
        $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='kardex'")->fetchColumn();
        if ($tableExists) {
            $pdo->exec("INSERT INTO kardex_new (id, componente_id, tipo_movimiento, cantidad, motivo, fecha) 
                        SELECT id, componente_id, tipo_movimiento, cantidad, motivo, fecha FROM kardex");
            $pdo->exec("DROP TABLE kardex");
        }

        // 3. Renombrar la tabla nueva a kardex
        $pdo->exec("ALTER TABLE kardex_new RENAME TO kardex");

        // 4. Crear índice en componente_id
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_kardex_componente_id ON kardex(componente_id)");

        // 5. Registrar migración
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO _migrations (version) VALUES ('1.0.1')");
        $stmt->execute();

        $pdo->commit();
        error_log("Migración 1.0.1 de Kardex FK ejecutada con éxito.");
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error de BD al ejecutar la migración 1.0.1: " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error general al ejecutar la migración 1.0.1: " . $e->getMessage());
        throw $e;
    }
}
?>
