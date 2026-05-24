<?php
require_once 'config/auth.php';
requiere_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="css/style.css">
    <meta charset="UTF-8">
    <title>Inventario - UC</title>
   
</head>
<body>

<div class="grid-container">
    <div>
        <form id="form-componente">
            <input type="hidden" id="componente_id" name="componente_id">
            
            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" id="nombre" name="nombre" placeholder="Escriba para buscar o crear..." autocomplete="off">
                <div id="resultados-busqueda"></div>
            </div>

            <div class="form-group">
                <label>Area:</label>
                <select id="categoria" name="categoria" onchange="filtrarTabla()">
                    <option value="">Seleccione...</option>
                    <option value="Herramientas">Herramientas</option>
                    <option value="Componentes Electrónicos">Componentes Electrónicos</option>
                </select>
                <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                <button type="button" onclick="abrirModal()" style="margin-left:5px; padding: 8px 12px; cursor:pointer;">+</button>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Marca:</label>
                <input type="text" id="marca" name="marca" placeholder="Ej. Weller, Truper...">
            </div>

            
            <div class="form-group">
                <label>Tipo de Ítem:</label>
                <select id="tipo_item" name="tipo_item" style="font-weight: bold; color: var(--uc-purple);">
                    <option value="Herramienta">Herramienta (Para préstamos)</option>
                    <option value="Insumo">Insumo (Consumible)</option>
                    <option value="Maquina">Máquina (Uso en laboratorio)</option>
                    <option value="Equipos Menores">Equipos Menores</option>
                </select>
            </div>
            <div class="form-group" id="grupo_limite_mantenimiento" style="display: none; background: #e8f8f5; padding: 10px; border-radius: 4px; border: 1px solid #2ecc71;">
                <label style="color: #2ecc71;">Límite Uso (Horas):</label>
                <input type="number" id="limite_mantenimiento" name="limite_mantenimiento" value="100" placeholder="Ej. 100" style="border-color: #2ecc71;">
                <small style="margin-left:10px; color:#666; font-size:0.8em;">(Solo para máquinas)</small>
            </div>

            

            <div class="form-group">
                <label>Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="3" placeholder="Especificaciones: 12V 5Ah, Punta Fina, etc..."></textarea>
            </div>
            <div class="form-group">
                <label>Fecha de Recepción:</label>
                <input type="date" id="fecha_recepcion" name="fecha_recepcion" style="padding: 5px; width: 100%; border-radius: 4px; border: 1px solid var(--border-color);">
                <small style="color:#666; font-size:0.8em;"></small>
            </div>
          

<!-- botones de filtrado -->
             
            <style>
.dashboard-panel { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
/* Añadimos efecto hover y cursor pointer para que parezcan botones */
.dash-card { background: #fff; padding: 15px; border-radius: 8px; border-left: 5px solid #ccc; box-shadow: 0 2px 5px rgba(0,0,0,0.05); cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
.dash-card:hover { transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.dash-card.danger { border-left-color: #e74c3c; }
.dash-card.warning { border-left-color: #f39c12; }
.dash-card.info { border-left-color: #3498db; }
/* Clase para cuando el filtro está activo */
.dash-card.activo { outline: 2px solid var(--uc-purple); background: #fdfefe; }
.dash-card h4 { margin: 0 0 5px 0; font-size: 0.85em; color: #777; text-transform: uppercase; }
.dash-card .valor { font-size: 2em; font-weight: bold; margin: 0; color: #333; }
.dash-card .detalle { font-size: 0.8em; color: #999; margin-top: 5px; }
</style>

<div class="dashboard-panel">
    <div class="dash-card danger" id="card-criticos" onclick="aplicarFiltroRapido('criticos')" title="Clic para filtrar tabla">
        <h4>Insumos Críticos</h4>
        <p class="valor" id="dash-criticos">0</p>
        <p class="detalle">Sin stock o Vencidos</p>
    </div>
    <div class="dash-card warning" id="card-alertas" onclick="aplicarFiltroRapido('alertas')" title="Clic para filtrar tabla">
        <h4>Alertas Tempranas</h4>
        <p class="valor" id="dash-alertas">0</p>
        <p class="detalle">Vencen en &lt; 30 días </p>
    </div>
    <div class="dash-card info" id="card-antiguos" onclick="aplicarFiltroRapido('antiguos')" title="Clic para ordenar tabla">
        <h4>Rotación de Inventario</h4>
        <p class="valor" style="font-size: 1.2em; margin-top:10px;">📋 Ver Lista</p>
        <p class="detalle">Ordenar por más antiguos </p>
    </div>
</div>
<!-- fin -->
            
            
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Area</th>
                            <th>Etiqueta</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-componentes">
                        </tbody>
                </table>
            </div>

            <div class="stock-box">
                <div>
                    <label style="color:var(--uc-yellow); font-size: 0.9em;">Stock Actual:</label><br>
                    <input type="number" id="stock_actual" readonly value="0" style="width: 80px; background:#222; border:none; color:white; font-size:1.2em; text-align:center;">
                </div>
                <div>
                    <label style="color:var(--uc-yellow); font-size: 0.9em;">Ajustar Stock (+ o -):</label><br>
                    <input type="number" id="ajuste_stock" value="0" style="width: 120px; padding: 5px;">
                </div>

                <div style="align-self: flex-end; font-size: 0.8em; color: gray;">
                    *(Se sumará/restará al actualizar)
                </div>
                <div>
                    <label style="color:var(--text-muted); font-size: 0.85em; font-weight:bold;">Etiqueta:</label><br>
                    <input type="text" id="etiqueta" name="etiqueta" placeholder="Ej. Elec-007" style="width: 100px; padding: 8px; border:1px solid var(--border-color); border-radius:4px;">
                </div>
                <div>
                    <label style="color:var(--uc-yellow); font-size: 0.95em; font-weight:bold;">Stock Mínimo:</label><br>
                    <input type="number" id="stock_minimo" name="stock_minimo" value="0" style="width: 80px; padding: 8px; border:1px solid var(--border-color); border-radius:4px;">
                </div>

                <div class="form-group">
                <label>Fecha de Vencimiento:</label>
                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" style="padding: 5px; width: 100%; border-radius: 4px; border: 1px solid var(--border-color);">
                <small style="color:#666; font-size:0.8em;">(Opcional)</small>
                </div>
            </div>
        </form>
    </div>

    <div>
        <div class="image-preview" id="image-container"><span>Sin Imagen</span></div>
        <input type="file" id="imagen_upload" style="display:none;" accept="image/*">
        <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
        <button type="button" onclick="document.getElementById('imagen_upload').click()" style="width: 100%; padding: 10px; cursor: pointer;">Subir Imagen</button>
        <?php endif; ?>
        
        <div class="botones-accion" style="margin-top: 20px;">
            <button type="button" id="btn-nuevo">Limpiar / Nuevo</button>
            <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
            <button type="button" id="btn-agregar">Agregar Ítem</button>
            <button type="button" id="btn-actualizar">Actualizar</button>
            <button type="button" id="btn-eliminar" class="btn-eliminar">Eliminar</button>
            <?php endif; ?>
            <button type="button" onclick="abrirHistorial()" style=" margin-top: 15px;">Ver Historial de Movimientos</button>
            <a href="index.php" style="display:block; text-align:center; margin-top: 20px; color: var(--uc-purple); text-decoration:none; font-weight:bold;">← Menú Principal</a>
            <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
            <hr style="margin: 20px 0; border: 1px solid var(--border-color);">
            <h4 style="color: #666; margin-top: 0;">Carga Masiva</h4>
            <input type="file" id="csv_upload" style="display:none;" accept=".csv">
            <button type="button" onclick="document.getElementById('csv_upload').click()" style="background: #2ecc71; color: white; width: 100%;">📥 Subir CSV (Importar)</button>
            <?php endif; ?>
        </div>
    </div>
</div>



<div id="modalCategoria" class="modal">
    <div class="modal-content" style="width: 320px;">
        <h3 style="color: var(--uc-yellow);">Gestión de Areas</h3>
        
        <div style="display: flex; gap: 5px; margin-bottom: 15px;">
            <input type="text" id="nuevaCategoria" placeholder="Nueva categoría..." style="padding:8px; flex:1; background: #333; color: white; border: 1px solid var(--uc-blue);">
            <button onclick="guardarCategoria()" style="padding:8px; cursor:pointer;  border: none; border-radius: 4px;">Añadir</button>
        </div>

        <div style="max-height: 150px; overflow-y: auto; text-align: left; border: 1px solid var(--uc-blue); padding: 5px; background: #111; border-radius: 4px;" id="lista-categorias-modal">
            </div>

        <button onclick="cerrarModal()" style="margin-top: 15px; padding:10px; cursor:pointer; background: #444; color:white; border:none; width: 100%; border-radius: 4px;">Cerrar</button>
    </div>
    
</div>




<div id="modalHistorial" class="modal">
    <div class="modal-content" style="width: 80%; max-width: 800px;">
        <h3 style="color: var(--uc-yellow);">Registro de Movimientos (Kardex)</h3>
        
        <div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--uc-blue); margin-top: 15px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9em;">
                <thead style="background: var(--uc-blue); position: sticky; top: 0;">
                    <tr>
                        <th style="padding: 10px; color: white;">Fecha y Hora</th>
                        <th style="padding: 10px; color: white;">Componente</th>
                        <th style="padding: 10px; color: white;">Tipo de Movimiento</th>
                        <th style="padding: 10px; color: white;">Cantidad</th>
                        <th style="padding: 10px; color: white;">Motivo / Proyecto</th> </tr>
                    </tr>
                </thead>
                <tbody id="tabla-historial">
                    </tbody>
            </table>
        </div>

        <button onclick="document.getElementById('modalHistorial').style.display = 'none'" style="margin-top: 20px; padding: 10px 30px; cursor: pointer; background: #444; color: white; border: none; border-radius: 4px;">Cerrar Historial</button>
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

    // Mostrar/ocultar el límite si es máquina
    document.getElementById('tipo_item').addEventListener('change', function() {
        document.getElementById('grupo_limite_mantenimiento').style.display = this.value === 'Maquina' ? 'block' : 'none';
    });

    // Variables globales


    /////
    // Variable global para saber qué botón está presionado
    let filtroActivoDashboard = ''; 

    function aplicarFiltroRapido(tipo) {
        // Si hace clic en el mismo filtro, lo apaga. Si no, lo enciende.
        if (filtroActivoDashboard === tipo) {
            filtroActivoDashboard = '';
            document.getElementById('card-' + tipo).classList.remove('activo');
        } else {
            // Limpiar selección anterior
            document.getElementById('card-criticos').classList.remove('activo');
            document.getElementById('card-alertas').classList.remove('activo');
            document.getElementById('card-antiguos').classList.remove('activo');
            // Activar el nuevo
            filtroActivoDashboard = tipo;
            document.getElementById('card-' + tipo).classList.add('activo');
        }
        filtrarTabla(); // Mandamos a recargar la tabla con el nuevo filtro
    }

    // Función simplificada (ya no buscamos un solo nombre antiguo)
    function actualizarDashboard(datos) {
        let criticos = 0;
        let alertas = 0;
        const hoy = new Date();

        datos.forEach(item => {
            let esCritico = false;
            if (item.fecha_vencimiento) {
                const fechaVenc = new Date(item.fecha_vencimiento + "T00:00:00");
                const diasRestantes = Math.ceil((fechaVenc - hoy) / (1000 * 60 * 60 * 24));
                if (diasRestantes <= 0) { esCritico = true; criticos++; } 
                else if (diasRestantes <= 30) { alertas++; }
            }
            if (item.stock <= 0 && !esCritico) { criticos++; }
        });

        document.getElementById('dash-criticos').innerText = criticos;
        document.getElementById('dash-alertas').innerText = alertas;
    }
    /////
    let datosBD = []; 
    const buscador = document.getElementById('nombre');
    const resultados = document.getElementById('resultados-busqueda');

    buscador.addEventListener('input', function() {
        const texto = this.value.toLowerCase();
        resultados.innerHTML = '';
        
        if (texto.length > 1) {
            const filtrados = datosBD.filter(item => item.nombre.toLowerCase().includes(texto));
            if (filtrados.length > 0) {
                resultados.style.display = 'block';
                filtrados.forEach(item => {
                    let div = document.createElement('div');
                    div.className = 'resultado-item';
                    div.textContent = `${item.nombre} - ${item.categoria || ''}`;
                    div.onclick = () => cargarDatos(item); 
                    resultados.appendChild(div);
                });
            }
        } else {
            resultados.style.display = 'none';
        }
    });

    function filtrarTabla() {
        const cat = document.getElementById('categoria').value;
        const tbody = document.getElementById('tabla-componentes');
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Cargando...</td></tr>';

        fetch(`api/listar_componentes.php?categoria=${encodeURIComponent(cat)}`)
        .then(res => res.json())
        .then(data => {
            if(data.error) return tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:red;">Error al cargar</td></tr>';
            
            datosBD = data; 
            
            // 1. ACTUALIZAMOS LAS TARJETAS CON LOS DATOS REALES (ANTES DE FILTRAR)
            actualizarDashboard(datosBD);

            let datosMostrar = [...datosBD]; // Creamos una copia para manipular

            // 2. FILTROS DEL DASHBOARD (¡Aquí ocurre la magia de los clics!)
            const hoyFiltro = new Date();
            if (filtroActivoDashboard === 'criticos') {
                datosMostrar = datosMostrar.filter(item => {
                    let esVencido = false;
                    if (item.fecha_vencimiento) {
                        const diff = new Date(item.fecha_vencimiento + "T00:00:00") - hoyFiltro;
                        if (Math.ceil(diff / (1000 * 60 * 60 * 24)) <= 0) esVencido = true;
                    }
                    return esVencido || parseInt(item.stock) <= 0;
                });
            } else if (filtroActivoDashboard === 'alertas') {
                datosMostrar = datosMostrar.filter(item => {
                    if (!item.fecha_vencimiento) return false;
                    const diff = new Date(item.fecha_vencimiento + "T00:00:00") - hoyFiltro;
                    const dias = Math.ceil(diff / (1000 * 60 * 60 * 24));
                    return dias > 0 && dias <= 30;
                });
            } else if (filtroActivoDashboard === 'antiguos') {
                // Ordenar tabla por fecha de recepción (de más viejo a más nuevo)
                datosMostrar.sort((a, b) => {
                    let fA = a.fecha_recepcion ? new Date(a.fecha_recepcion) : new Date('2099-12-31');
                    let fB = b.fecha_recepcion ? new Date(b.fecha_recepcion) : new Date('2099-12-31');
                    return fA - fB;
                });
            }

            tbody.innerHTML = '';
            if(datosMostrar.length === 0) return tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No hay ítems con esas características.</td></tr>';

            datosMostrar.forEach(item => {
                let tr = document.createElement('tr');
                tr.onclick = () => cargarDatos(item); 
                
                let colorStock = parseInt(item.stock) <= (parseInt(item.stock_minimo) || 0) ? '#e74c3c' : 'inherit';
                
                // 👇 LÓGICA DEL SEMÁFORO 👇
                let alertaVencimiento = '';
                if (item.fecha_vencimiento) {
                    const hoy = new Date();
                    const fechaVenc = new Date(item.fecha_vencimiento + "T00:00:00");
                    const diffTime = fechaVenc - hoy;
                    const diasRestantes = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                    if (diasRestantes <= 0) {
                        alertaVencimiento = `<br><span style="color:#e74c3c; font-size:0.8em; font-weight:bold;">❌ Vencido</span>`;
                    } else if (diasRestantes <= 30) {
                        alertaVencimiento = `<br><span style="color:#f39c12; font-size:0.8em; font-weight:bold;">⚠️ Vence en ${diasRestantes} d.</span>`;
                    } else {
                        alertaVencimiento = `<br><span style="color:#27ae60; font-size:0.8em;">📅 Vigente</span>`;
                    }
                }
                
                tr.innerHTML = `<td>${escapeHTML(item.nombre)} ${alertaVencimiento}</td><td>${escapeHTML(item.categoria || '-')}</td><td>${escapeHTML(item.etiqueta || '-')}</td><td><b style="color:${colorStock}; font-size:1.1em;">${item.stock}</b></td>`;
                tbody.appendChild(tr);
                
            });
            
        });
    }

    function cargarDatos(item) {
        resultados.style.display = 'none'; 
        document.getElementById('componente_id').value = item.id;
        document.getElementById('nombre').value = item.nombre;
        document.getElementById('categoria').value = item.categoria;
        document.getElementById('marca').value = item.marca;
        
        document.getElementById('descripcion').value = item.descripcion;
        document.getElementById('stock_actual').value = item.stock;
        document.getElementById('stock_minimo').value = item.stock_minimo || 0;
        document.getElementById('ajuste_stock').value = 0; 
        document.getElementById('etiqueta').value = item.etiqueta || '';
        
        //fecha
        document.getElementById('fecha_vencimiento').value = item.fecha_vencimiento || '';
        document.getElementById('fecha_recepcion').value = item.fecha_recepcion || '';
        // 👇 NUEVO: Cargar los datos visuales de las Máquinas 👇
        document.getElementById('tipo_item').value = item.tipo_item || 'Herramienta';
        document.getElementById('limite_mantenimiento').value = item.limite_mantenimiento || 100;
        document.getElementById('grupo_limite_mantenimiento').style.display = (item.tipo_item === 'Maquina') ? 'block' : 'none';

        const imgContainer = document.getElementById('image-container');
        if (item.imagen_path) {
            imgContainer.innerHTML = `<img src="${item.imagen_path}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
        } else {
            imgContainer.innerHTML = '<span>Sin Imagen</span>';
        }
    }

    document.getElementById('imagen_upload').onchange = function(e) {
        if(this.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('image-container').innerHTML = `<img src="${e.target.result}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
            }
            reader.readAsDataURL(this.files[0]);
        }
    };

    let categoriasBD = [];
    function cargarCategorias() {
        fetch('api/listar_categorias.php').then(res => res.json()).then(data => {
            if(!data.error) {
                categoriasBD = data.map(c => c.nombre);
                actualizarSelectCategorias();
                if(document.getElementById('modalCategoria').style.display === 'flex') renderizarListaCategorias();
            }
        });
    }

    function abrirModal() { document.getElementById('modalCategoria').style.display = 'flex'; renderizarListaCategorias(); }
    function cerrarModal() { document.getElementById('modalCategoria').style.display = 'none'; document.getElementById('nuevaCategoria').value = ''; }
    
    function renderizarListaCategorias() {
        const contenedor = document.getElementById('lista-categorias-modal');
        contenedor.innerHTML = '';
        categoriasBD.forEach((cat) => {
            let div = document.createElement('div');
            div.style.padding = '8px 5px';
            div.style.borderBottom = '1px solid #333';
            div.innerHTML = `<span style="color: white;">${escapeHTML(cat)}</span> <button onclick="eliminarCategoria(decodeURIComponent('${encodeURIComponent(cat)}'))" style="background:#ff4c4c; border:none; color:white; cursor:pointer; padding:2px 8px; border-radius: 3px; float:right;">X</button>`;
            contenedor.appendChild(div);
        });
    }

    function actualizarSelectCategorias() {
        const select = document.getElementById('categoria');
        const valorActual = select.value;
        select.innerHTML = '<option value="">Seleccione...</option>';
        categoriasBD.forEach(cat => select.add(new Option(cat, cat)));
        select.value = valorActual;
    }

    function guardarCategoria() {
        const nuevoNom = document.getElementById('nuevaCategoria').value.trim();
        if (nuevoNom && !categoriasBD.includes(nuevoNom)) {
            let formData = new FormData(); formData.append('nombre', nuevoNom);
            fetch('api/guardar_categoria.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                if(data.status === 'success') { document.getElementById('nuevaCategoria').value = ''; cargarCategorias(); }
            });
        }
    }

    function eliminarCategoria(nombreCat) {
        if(confirm(`¿Seguro que deseas eliminar la categoría "${nombreCat}"?`)) {
            fetch('api/eliminar_categoria.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nombre: nombreCat }) })
            .then(res => res.json()).then(data => { if(data.status === 'success') cargarCategorias(); });
        }
    }

    document.getElementById('btn-nuevo').onclick = () => {
        document.getElementById('form-componente').reset();
        document.getElementById('componente_id').value = '';
        document.getElementById('image-container').innerHTML = '<span>Sin Imagen</span>';
        document.getElementById('stock_actual').value = 0;
        document.getElementById('stock_minimo').value = 0;
        document.getElementById('ajuste_stock').value = 0;
        document.getElementById('etiqueta').value = '';
//fechas
        document.getElementById('fecha_vencimiento').value = '';
        document.getElementById('fecha_recepcion').value = '';
        // 👇 Limpiar y ocultar el tipo de ítem 👇
        document.getElementById('tipo_item').value = 'Herramienta';
        document.getElementById('limite_mantenimiento').value = 100;
        document.getElementById('grupo_limite_mantenimiento').style.display = 'none';
        
        filtrarTabla(); 
    };

    document.getElementById('btn-agregar').onclick = () => {
        const nombreIngresado = document.getElementById('nombre').value.trim();
        if (!nombreIngresado) return alert("❌ Escribe un nombre para el ítem.");

        const datos = {
            accion: 'nuevo',
            nombre: nombreIngresado,
            categoria: document.getElementById('categoria').value,
            marca: document.getElementById('marca').value,
            
            descripcion: document.getElementById('descripcion').value,
            ajuste_stock: document.getElementById('ajuste_stock').value || 0,
            stock_minimo: document.getElementById('stock_minimo').value || 0,
            etiqueta: document.getElementById('etiqueta').value.trim(),

            fecha_vencimiento: document.getElementById('fecha_vencimiento').value,
            fecha_recepcion: document.getElementById('fecha_recepcion').value,
            tipo_item: document.getElementById('tipo_item').value,
            // 👇 NUEVO: Lo mandamos al backend 👇
            limite_mantenimiento: document.getElementById('limite_mantenimiento').value 
        };
        enviarDatosAPI(datos);
    };

   document.getElementById('btn-actualizar').onclick = () => {
        const id = document.getElementById('componente_id').value;
        const ajuste = parseInt(document.getElementById('ajuste_stock').value) || 0;
        if (!id) return alert("❌ Selecciona un ítem de la tabla primero.");

        const datos = { 
            accion: 'actualizar', 
            id: id, 
            nombre: document.getElementById('nombre').value.trim(),
            categoria: document.getElementById('categoria').value,
            marca: document.getElementById('marca').value,
            
            descripcion: document.getElementById('descripcion').value,
            ajuste_stock: ajuste,
            stock_minimo: document.getElementById('stock_minimo').value || 0,
            etiqueta: document.getElementById('etiqueta').value.trim(),
            tipo_item: document.getElementById('tipo_item').value,
            // 👇 NUEVO: Lo mandamos al backend 👇
            limite_mantenimiento: document.getElementById('limite_mantenimiento').value,
            fecha_vencimiento: document.getElementById('fecha_vencimiento').value,
            fecha_recepcion: document.getElementById('fecha_recepcion').value
        };
        enviarDatosAPI(datos);
    };

    document.getElementById('btn-eliminar').onclick = () => {
        const id = document.getElementById('componente_id').value;
        if (!id) return alert("❌ Selecciona un ítem de la tabla primero.");
        if (confirm("⚠️ ¿Estás seguro de eliminar este ítem?")) {
            enviarDatosAPI({ accion: 'eliminar', id: id });
        }
    };

    function enviarDatosAPI(datos) {
        const formData = new FormData();
        for (const key in datos) formData.append(key, datos[key]);

        const inputImagen = document.getElementById('imagen_upload');
        if (inputImagen.files[0]) formData.append('imagen_upload', inputImagen.files[0]);

        fetch('api/procesar_inventario.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('✅ ' + data.mensaje);
                document.getElementById('btn-nuevo').click(); 
            } else {
                alert('❌ Error: ' + data.mensaje);
            }
        });
    }

    function abrirHistorial() {
        document.getElementById('modalHistorial').style.display = 'flex';
        const tbody = document.getElementById('tabla-historial');
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">Cargando...</td></tr>';

        fetch('api/obtener_historial.php').then(res => res.json()).then(data => {
            tbody.innerHTML = '';
            if (data.length === 0) return tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">No hay movimientos.</td></tr>';
            
            data.forEach(mov => {
                let color = mov.tipo_movimiento.includes('Positivo') || mov.tipo_movimiento.includes('Ingreso') ? '#2ECC71' : '#E74C3C';
                let signo = mov.tipo_movimiento.includes('Positivo') || mov.tipo_movimiento.includes('Ingreso') ? '+' : (mov.tipo_movimiento.includes('Eliminado') ? '' : '-');
                
                tbody.innerHTML += `<tr style="border-bottom: 1px solid #E0E0E0;">
                    <td style="padding: 10px;">${mov.fecha}</td>
                    <td style="padding: 10px; color: var(--uc-purple); font-weight: 500;">${escapeHTML(mov.componente)}</td>
                    <td style="padding: 10px; color: ${color}; font-weight: 500;">${escapeHTML(mov.tipo_movimiento)}</td>
                    <td style="padding: 10px; color: ${color}; font-weight: bold;">${signo}${mov.cantidad}</td>
                    <td style="padding: 10px; color: #666; font-style: italic;">${escapeHTML(mov.motivo || '---')}</td>
                </tr>`;
            });
        });
    }
    // Lógica para atrapar el archivo CSV y enviarlo
    document.getElementById('csv_upload').addEventListener('change', function(e) {
        const archivo = this.files[0];
        if (!archivo) return;

        if (!confirm(`¿Estás seguro de importar los datos del archivo: ${archivo.name}?`)) {
            this.value = ''; // Limpia el input
            return;
        }

        const formData = new FormData();
        formData.append('archivo_csv', archivo);

        // Cambiar el botón para que parezca que está cargando
        const btn = e.target.nextElementSibling; 
        const textoOriginal = btn.innerHTML;
        btn.innerHTML = '⏳ Procesando...';
        btn.disabled = true;

        fetch('api/procesar_importacion.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('✅ ' + data.mensaje);
                filtrarTabla(); // Recarga la tabla para mostrar los datos nuevos
            } else {
                alert('❌ ' + data.mensaje);
            }
            // Restaurar botón
            btn.innerHTML = textoOriginal;
            btn.disabled = false;
            document.getElementById('csv_upload').value = '';
        })
        .catch(err => {
            alert('❌ Error de conexión al importar.');
            btn.innerHTML = textoOriginal;
            btn.disabled = false;
            document.getElementById('csv_upload').value = '';
        });
    });
    cargarCategorias();
    filtrarTabla(); 
</script>
</body>
</html>