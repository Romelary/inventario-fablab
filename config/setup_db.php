<?php
// config/setup_db.php

function setup_database(PDO $pdo) {
    // Enable foreign keys in SQLite for this connection
    $pdo->exec("PRAGMA foreign_keys = ON;");

    $sql = "
    -- 1. Tabla para control de migraciones
    CREATE TABLE IF NOT EXISTS _migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        version TEXT NOT NULL UNIQUE,
        executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    -- 2. Tabla para categorías
    CREATE TABLE IF NOT EXISTS categorias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT UNIQUE NOT NULL
    );

    -- 3. Tabla de componentes (Herramientas, Insumos, Máquinas, Equipos Menores)
    CREATE TABLE IF NOT EXISTS componentes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo TEXT UNIQUE,
        nombre TEXT NOT NULL,
        categoria TEXT NOT NULL,
        marca TEXT,
        descripcion TEXT,
        stock INTEGER DEFAULT 0 CHECK(stock >= 0),
        stock_minimo INTEGER DEFAULT 0 CHECK(stock_minimo >= 0),
        imagen_path TEXT,
        etiqueta TEXT,
        tipo_item TEXT DEFAULT 'Herramienta' CHECK(tipo_item IN ('Herramienta', 'Insumo', 'Maquina', 'Equipos Menores')),
        estado TEXT DEFAULT 'Operativo' CHECK(estado IN ('Operativo', 'En Mantenimiento', 'Baja', 'Requiere Mantenimiento')),
        limite_mantenimiento REAL DEFAULT 100.0,
        horas_uso REAL DEFAULT 0.0 CHECK(horas_uso >= 0.0),
        fecha_vencimiento DATE DEFAULT NULL,
        fecha_recepcion DATE DEFAULT NULL
    );

    -- 4. Tabla de préstamos (Cabecera)
    CREATE TABLE IF NOT EXISTS prestamos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        dni_alumno TEXT NOT NULL,
        nombre_apellido TEXT NOT NULL,
        proyecto TEXT,
        fecha_prestamo DATETIME DEFAULT CURRENT_TIMESTAMP,
        estado TEXT DEFAULT 'Pendiente' CHECK(estado IN ('Pendiente', 'Parcial', 'Devuelto'))
    );

    -- 5. Tabla de detalle de préstamos
    CREATE TABLE IF NOT EXISTS detalle_prestamo (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        prestamo_id INTEGER NOT NULL,
        componente_id INTEGER NOT NULL,
        cantidad INTEGER NOT NULL CHECK(cantidad > 0),
        devueltos INTEGER DEFAULT 0 CHECK(devueltos >= 0),
        observacion TEXT,
        FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
        FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE CASCADE
    );

    -- 6. Tabla de consumos (Insumos)
    CREATE TABLE IF NOT EXISTS consumos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        componente_id INTEGER NOT NULL,
        cantidad INTEGER NOT NULL CHECK(cantidad > 0),
        solicitante TEXT NOT NULL,
        proyecto TEXT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        usuario_registro TEXT DEFAULT 'Admin',
        FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE CASCADE
    );

    -- 7. Tabla de kardex (Movimientos)
    CREATE TABLE IF NOT EXISTS kardex (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        componente_id INTEGER,
        tipo_movimiento TEXT NOT NULL,
        cantidad INTEGER NOT NULL CHECK(cantidad >= 0),
        motivo TEXT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE SET NULL
    );

    -- 8. Tabla de bitácora de uso de máquinas
    CREATE TABLE IF NOT EXISTS bitacora_maquinas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        maquina_id INTEGER NOT NULL,
        solicitante TEXT NOT NULL,
        hora_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
        hora_fin DATETIME,
        observaciones TEXT,
        usuario_registro TEXT DEFAULT 'Admin',
        tiempo_horas REAL DEFAULT 0.0 CHECK(tiempo_horas >= 0.0),
        FOREIGN KEY (maquina_id) REFERENCES componentes(id) ON DELETE CASCADE
    );

    -- 9. Tabla de mantenimientos
    CREATE TABLE IF NOT EXISTS mantenimientos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        componente_id INTEGER NOT NULL,
        tipo_mantenimiento TEXT NOT NULL CHECK(tipo_mantenimiento IN ('Preventivo', 'Correctivo')),
        descripcion TEXT NOT NULL,
        costo DECIMAL(10,2) DEFAULT 0.00 CHECK(costo >= 0.00),
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        usuario_registro TEXT DEFAULT 'Admin',
        tiempo_trabajado REAL DEFAULT 0.00 CHECK(tiempo_trabajado >= 0.00),
        FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE CASCADE
    );

    -- Índices para optimizar las consultas frecuentes
    CREATE INDEX IF NOT EXISTS idx_componentes_categoria ON componentes(categoria);
    CREATE INDEX IF NOT EXISTS idx_componentes_tipo_item ON componentes(tipo_item);
    CREATE INDEX IF NOT EXISTS idx_componentes_estado ON componentes(estado);
    
    CREATE INDEX IF NOT EXISTS idx_prestamos_estado ON prestamos(estado);
    
    CREATE INDEX IF NOT EXISTS idx_detalle_prestamo_prestamo_id ON detalle_prestamo(prestamo_id);
    CREATE INDEX IF NOT EXISTS idx_detalle_prestamo_componente_id ON detalle_prestamo(componente_id);
    
    CREATE INDEX IF NOT EXISTS idx_consumos_componente_id ON consumos(componente_id);
    
    CREATE INDEX IF NOT EXISTS idx_kardex_componente_id ON kardex(componente_id);
    
    CREATE INDEX IF NOT EXISTS idx_bitacora_maquinas_maquina_id ON bitacora_maquinas(maquina_id);
    
    CREATE INDEX IF NOT EXISTS idx_mantenimientos_componente_id ON mantenimientos(componente_id);

    -- 10. Tabla de usuarios
    CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        nombre TEXT,
        rol TEXT DEFAULT 'admin' CHECK(rol IN ('admin', 'usuario')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE INDEX IF NOT EXISTS idx_usuarios_username ON usuarios(username);
    ";

    $pdo->exec($sql);

    // Registrar la migración inicial de la base de datos
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO _migrations (version) VALUES ('1.0.0')");
    $stmt->execute();
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO _migrations (version) VALUES ('1.0.1')");
    $stmt->execute();

    // Crear usuario admin por defecto si la tabla está vacía
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmtCount->fetchColumn() == 0) {
        // Principal
        $username = 'admin';
        $password = 'admin12345';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $nombre = 'Administrador Principal';
        $rol = 'admin';
        
        $stmtInsert = $pdo->prepare("INSERT INTO usuarios (id, username, password_hash, nombre, rol) VALUES (1, ?, ?, ?, ?)");
        $stmtInsert->execute([$username, $hash, $nombre, $rol]);

        // Secundario
        $username2 = 'admin2';
        $password2 = 'admin54321';
        $hash2 = password_hash($password2, PASSWORD_DEFAULT);
        $nombre2 = 'Administrador Auxiliar';
        $rol2 = 'admin';

        $stmtInsert2 = $pdo->prepare("INSERT INTO usuarios (id, username, password_hash, nombre, rol) VALUES (2, ?, ?, ?, ?)");
        $stmtInsert2->execute([$username2, $hash2, $nombre2, $rol2]);
    }
}
