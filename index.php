<?php
require_once 'config/auth.php';
requiere_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio FABLAB - UC</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { 
            background-color: #f4f6f8; 
            margin: 0; 
            padding: 20px; 
            font-family: 'Segoe UI', sans-serif; 
            box-sizing: border-box;
        }
        
        .dashboard-layout {
            display: grid;
            grid-template-columns: 1fr 1.3fr; 
            gap: 30px;
            max-width: 1300px;
            margin: 0 auto; 
            margin-top: 30px;
            align-items: stretch;
        }

        /* --- COLUMNA IZQUIERDA --- */
        .left-column {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .header-box {
            background: var(--uc-purple);
            color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(102,0,153,0.15);
        }
        .header-box h1 { margin: 0 0 5px 0; font-size: 1.8em; line-height: 1.2; }
        .header-box p { margin: 0; opacity: 0.9; font-size: 0.95em; }

        /* --- BUSCADOR GENERAL --- */
        .search-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
        }
        .search-container h3 { margin-top: 0; color: #333; margin-bottom: 15px; }
        #buscador-maestro {
            width: 100%;
            padding: 15px;
            font-size: 1.05em;
            border: 2px solid var(--uc-purple);
            border-radius: 8px;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
        }
        #buscador-maestro:focus { box-shadow: 0 0 8px rgba(102,0,153,0.2); }
        
        #resultados-maestro {
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        .res-card {
            border: 1px solid #eee;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #fdfdfd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.2s;
        }
        .res-card:hover { background: #f4f6f8; border-color: #ccc; }

        /* --- MÉTRICAS (AHORA DENTRO DE COLUMNA IZQUIERDA) --- */
        .metrics-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .metric-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
            border-top: 5px solid var(--uc-purple);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            cursor: pointer;
            transition: 0.2s;
            text-align: center;
        }
        .metric-card:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.08); }
        .metric-card h4 { margin: 0; color: #666; font-size: 0.85em; text-transform: uppercase; }
        .metric-card .value { font-size: 2.2em; font-weight: bold; color: #333; margin-top: 5px; }

        .metric-card.danger { border-top-color: #e74c3c; }
        .metric-card.danger .value { color: #e74c3c; }
        .metric-card.warning { border-top-color: #f39c12; }
        .metric-card.warning .value { color: #f39c12; }
        .metric-card.info { border-top-color: #3498db; }
        .metric-card.success { border-top-color: #2ecc71; }

        /* --- COLUMNA DERECHA --- */
        .right-column {
            background: white;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .right-column h2 { margin-top: 0; color: #333; border-bottom: 2px solid #f4f6f8; padding-bottom: 15px; margin-bottom: 25px; }

        .modules-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; 
            gap: 20px;
            margin-bottom: 30px;
        }

        .module-btn {
            background: #f8f9fa;
            border: 1px solid #eee;
            padding: 20px;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: 0.3s;
        }
        .module-btn:hover { background: white; border-color: var(--uc-purple); box-shadow: 0 5px 15px rgba(102,0,153,0.1); transform: translateY(-2px); }
        .module-btn .icon { font-size: 2em; min-width: 50px; height: 50px; background: white; border-radius: 10px; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .module-btn div { display: flex; flex-direction: column; }
        .module-btn h3 { margin: 0; color: var(--uc-purple); font-size: 1.1em; }
        .module-btn p { margin: 3px 0 0 0; color: #777; font-size: 0.8em; line-height: 1.3; }

        /* --- BOTONES DE REPORTES --- */
        .btn-reporte {
            flex: 1; 
            min-width: 220px;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.05em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-reporte:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); filter: brightness(1.1); }

        /* Responsivo */
        @media (max-width: 900px) {
            .dashboard-layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .modules-grid, .metrics-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    
    <!-- ========== COLUMNA IZQUIERDA ========== -->
    <div class="left-column">
        
        <!-- 1. Encabezado -->
        <div class="header-box">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <img src="img/logo-uc.png" alt="UC Logo" style="width: 60px; height: 60px; object-fit: contain; background: white; border-radius: 10px; padding: 5px;">
                    <div>
                        <h1 style="margin: 0 0 5px 0; font-size: 1.8em; line-height: 1.2;">FABLAB</h1>
                        <p style="margin: 0; opacity: 0.9; font-size: 0.95em;">Universidad Continental</p>
                    </div>
                </div>
                <div id="reloj" style="font-size: 1.3em; font-weight: bold; color: #e8d0ff;"></div>
            </div>
            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2); display: flex; justify-content: space-between; align-items: center; font-size: 0.9em;">
                <span>👤 Usuario: <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></span>
                <a href="logout.php" style="color: #e8d0ff; text-decoration: none; font-weight: bold; border: 1px solid #e8d0ff; padding: 5px 10px; border-radius: 6px; transition: 0.2s; background: rgba(255,255,255,0.1);" onmouseover="this.style.background='white'; this.style.color='var(--uc-purple)';" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.color='#e8d0ff';">Cerrar Sesión</a>
            </div>
        </div>

        <!-- 2. Buscador -->
        <div class="search-container">
            <h3>🔍 Buscador Rápido de Inventario</h3>
            <input type="text" id="buscador-maestro" placeholder="Buscar herramienta, insumo, etiqueta, área..." autocomplete="off">
            <div id="resultados-maestro">
                <p style="text-align: center; color: #999; margin-top: 40px;">Usa la barra para encontrar al instante cualquier equipo registrado en la base de datos.</p>
            </div>
        </div>

        <!-- 3. Métricas (AHORA AQUÍ) -->
        <div class="metrics-row">
            <div class="metric-card danger" onclick="location.href='mantenimiento.php'" title="Ir a Mantenimiento">
                <h4>🚨 Alertas</h4>
                <div class="value" id="val_maquinas">0</div>
            </div>
            <div class="metric-card warning" onclick="location.href='recibir.php'" title="Ir a Recibir Herramientas">
                <h4>📥 Préstamos</h4>
                <div class="value" id="val_prestamos">0</div>
            </div>
            <div class="metric-card info" onclick="location.href='inventario.php?filtro=stock_bajo'" title="Ir a Inventario">
                <h4>⚠️ Stock Bajo</h4>
                <div class="value" id="val_stock">0</div>
            </div>
            <div class="metric-card success" onclick="location.href='inventario.php'" title="Ir a Inventario">
                <h4>📦  total de elementos</h4>
                <div class="value" id="val_total">0</div>
            </div>
        </div>
    </div>

    <!-- ========== COLUMNA DERECHA ========== -->
    <div class="right-column">
        <div>
            <h2>Módulos del Sistema</h2>
            <div class="modules-grid">
                <a href="inventario.php" class="module-btn">
                    <div class="icon">📦</div>
                    <div><h3>Inventario</h3><p>Gestión de equipos</p></div>
                </a>
                <a href="consumos.php" class="module-btn">
                    <div class="icon">🛒</div>
                    <div><h3>Consumos</h3><p>Salida de insumos</p></div>
                </a>
                <a href="entregar.php" class="module-btn">
                    <div class="icon">📤</div>
                    <div><h3>Prestar</h3><p>Generar pedidos</p></div>
                </a>
                <a href="recibir.php" class="module-btn">
                    <div class="icon">📥</div>
                    <div><h3>Recibir</h3><p>Devolución de equipos</p></div>
                </a>
                <a href="maquinas.php" class="module-btn">
                    <div class="icon">⏱️</div>
                    <div><h3>Bitácora</h3><p>Uso de máquinas</p></div>
                </a>
                <a href="mantenimiento.php" class="module-btn">
                    <div class="icon">🛠️</div>
                    <div><h3>Mantenimiento</h3><p>Servicio técnico</p></div>
                </a>
                <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin'): ?>
                <a href="usuarios.php" class="module-btn" style="border-color: #d4b2f7; background: #faf5ff;">
                    <div class="icon" style="background: #e8d0ff;">👥</div>
                    <div><h3>Usuarios</h3><p>Control de accesos</p></div>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin'): ?>
        <div>
            <h3 style="color: #333; border-bottom: 2px solid #f4f6f8; padding-bottom: 10px; font-size: 1.2em; margin-top:0;">📑 Descarga de Reportes</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="api/exportar_csv.php?tabla=componentes" class="btn-reporte" style="background: #1edd9a;">📊 Inventario Actual</a>
                <a href="api/exportar_csv.php?tabla=kardex" class="btn-reporte" style="background: #41c94c;">📜 Kardex Movimientos</a>
                <a href="api/exportar_csv.php?tabla=mantenimientos" class="btn-reporte" style="background: #e67e22;">🔧 Mantenimientos</a>
                <a href="api/exportar_csv.php?tabla=bitacora_maquinas" class="btn-reporte" style="background: #8e44ad;">⏱️ Uso de Máquinas</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // --- 1. LÓGICA DEL RELOJ Y MÉTRICAS ---
    function mostrarHora() {
        document.getElementById('reloj').textContent = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(mostrarHora, 1000);
    mostrarHora();

    function actualizarStats() {
        fetch('api/dashboard_stats.php')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                const s = data.data;
                document.getElementById('val_maquinas').textContent = s.maquinas_alerta;
                document.getElementById('val_prestamos').textContent = s.prestamos_pendientes;
                document.getElementById('val_stock').textContent = s.stock_bajo;
                document.getElementById('val_total').textContent = s.total_equipos;
            }
        });
    }
    actualizarStats();

    // --- 2. LÓGICA DEL BUSCADOR MAESTRO ---
    let catalogoGlobal = [];

    function cargarCatalogoCompleto() {
        fetch('api/listar_componentes.php')
        .then(res => res.json())
        .then(data => {
            catalogoGlobal = data;
        });
    }

    document.getElementById('buscador-maestro').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const contenedorResultados = document.getElementById('resultados-maestro');
        
        if (query.length === 0) {
            contenedorResultados.innerHTML = '<p style="text-align: center; color: #999; margin-top: 40px;">Usa la barra para encontrar al instante cualquier equipo registrado en la base de datos.</p>';
            return;
        }

        const filtrados = catalogoGlobal.filter(item => 
            (item.nombre && item.nombre.toLowerCase().includes(query)) ||
            (item.categoria && item.categoria.toLowerCase().includes(query)) ||
            (item.etiqueta && item.etiqueta.toLowerCase().includes(query)) ||
            (item.marca && item.marca.toLowerCase().includes(query)) ||
            (item.descripcion && item.descripcion.toLowerCase().includes(query)) ||
            (item.codigo && item.codigo.toLowerCase().includes(query)) ||
            (item.tipo_item && item.tipo_item.toLowerCase().includes(query)) ||
            (item.estado && item.estado.toLowerCase().includes(query))
        );

        contenedorResultados.innerHTML = '';

        if (filtrados.length === 0) {
            contenedorResultados.innerHTML = '<p style="text-align: center; color: #e74c3c; margin-top: 40px;">No se encontraron componentes con esa información.</p>';
            return;
        }

        filtrados.slice(0, 15).forEach(item => {
            let colorStock = parseInt(item.stock) <= (parseInt(item.stock_minimo) || 0) ? '#e74c3c' : '#2ecc71';
            let etiqueta = item.etiqueta ? item.etiqueta : 'Sin Etiqueta';
            let area = item.categoria ? item.categoria : 'Área General';

            contenedorResultados.innerHTML += `
                <div class="res-card">
                    <div>
                        <strong style="color: var(--uc-dark); font-size: 1.1em;">${escapeHTML(item.nombre)}</strong>
                        <div style="color: #666; font-size: 0.85em; margin-top: 4px;">
                            <span style="background: #eee; padding: 2px 6px; border-radius: 4px; margin-right: 5px;">${escapeHTML(area)}</span>
                            <span style="background: #e8d0ff; color: var(--uc-purple); padding: 2px 6px; border-radius: 4px;">${escapeHTML(etiqueta)}</span>
                        </div>
                    </div>
                    <div style="text-align: center; min-width: 60px;">
                        <span style="font-size: 0.85em; color: #888; display: block;">Stock</span>
                        <b style="color: ${colorStock}; font-size: 1.3em;">${item.stock}</b>
                    </div>
                </div>
            `;
        });
    });

    cargarCatalogoCompleto();
</script>

</body>
</html>