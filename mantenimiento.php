<?php
require_once 'config/auth.php';
requiere_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento Técnico - UC</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .maquina-item { padding: 12px; border-bottom: 1px solid #eee; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: 0.2s;}
        .maquina-item:hover { background: #f4f6f8; }
        .maquina-item.alerta { border-left: 4px solid #e74c3c; background: #fdf2f1; }
        .maquina-item.alerta:hover { background: #fadbd8; }
        .maquina-item.active { background: var(--uc-purple); color: white; }
        .maquina-item.active small { color: #ddd !important; }
        
        .insumo-tabla th { background: #555; color: white; padding: 8px; text-align: left; }
        .insumo-tabla td { padding: 8px; border-bottom: 1px solid #EEE; }
        
        #buscador-insumos { width: 100%; padding: 10px; border: 1px solid var(--uc-purple); border-radius: 4px; box-sizing: border-box; }
        .resultado-insumo { padding: 8px; border-bottom: 1px solid #eee; cursor: pointer; }
        .resultado-insumo:hover { background: #eee; }
    </style>
</head>
<body>

<div class="grid-container" style="max-width: 1200px; grid-template-columns: 1fr 1.5fr; gap: 30px; align-items: stretch; box-sizing: border-box;">
    
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
        <h3 style="color: var(--uc-purple); margin-top: 0; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">🚨 Estado de Máquinas</h3>
        
        <div id="lista-maquinas" style="flex: 1; overflow-y: auto; max-height: 550px; border: 1px solid #ddd; border-radius: 4px;">
            <p style="text-align:center; color:#999; margin-top: 20px;">Cargando equipos...</p>
        </div>
    </div>

    <div style="background: #F8F9FA; padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
        <h2 style="margin-top: 0; color: #333;">🛠️ Reporte de Mantenimiento</h2>
        
        <div id="form-mantenimiento" style="opacity: 0.5; pointer-events: none; transition: 0.3s;">
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label>Máquina Seleccionada:</label>
                    <input type="text" id="lbl-maquina" readonly style="width:100%; font-weight:bold; color:var(--uc-purple); background:#eee;">
                </div>
                <div style="flex: 1;">
                    <label>Tipo:</label>
                    <select id="tipo_servicio" style="width:100%; font-weight:bold;">
                        <option value="Preventivo">Preventivo (Limpieza/Ajuste)</option>
                        <option value="Correctivo">Correctivo (Reparación/Falla)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 2;">
                    <label>Técnico o Encargado:</label>
                    <input type="text" id="tecnico" placeholder="Nombre de quien realiza el servicio..." style="width:100%;">
                </div>
                <div style="flex: 1;">
                    <label>Tiempo Invertido:</label>
                    <div style="display: flex; justify-content: center; align-items: center; background: #fff; padding: 4px; border-radius: 4px; border: 1px solid #ccc;">
                        <input type="number" id="h_mant" placeholder="00" min="0" style="width: 45px; text-align: center; border: none; background: transparent; outline: none;"> <b>h</b>
                        <span style="margin: 0 5px; color: #ccc;">|</span>
                        <input type="number" id="m_mant" placeholder="00" min="0" max="59" style="width: 45px; text-align: center; border: none; background: transparent; outline: none;"> <b>m</b>
                        <span style="margin: 0 5px; color: #ccc;">|</span>
                        <input type="number" id="s_mant" placeholder="00" min="0" max="59" style="width: 45px; text-align: center; border: none; background: transparent; outline: none;"> <b>s</b>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label>Descripción del Trabajo Realizado:</label>
                <textarea id="descripcion" rows="3" placeholder="Ej. Cambio de lente, lubricación de ejes, calibración de cama..." style="width:100%;"></textarea>
            </div>

            <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 15px;">
            
            <h4 style="margin:0 0 10px 0; color:#444;">📦 Insumos Utilizados (Opcional)</h4>
            <div style="position: relative; margin-bottom: 15px;">
                <input type="text" id="buscador-insumos" placeholder="🔍 Buscar insumos consumidos (Ej. Alcohol, Tornillos)..." autocomplete="off">
                <div id="resultados-insumos" style="position:absolute; width:100%; background:white; border:1px solid #ccc; z-index:100; max-height:150px; overflow-y:auto; display:none; box-shadow:0 4px 6px rgba(0,0,0,0.1);"></div>
            </div>

            <div class="insumo-tabla" style="border: 1px solid #DDD; background: white; border-radius: 4px; overflow:hidden; margin-bottom: 20px;">
                <table width="100%" cellspacing="0">
                    <thead><tr><th>Insumo</th><th width="80">Cant.</th><th width="50"></th></tr></thead>
                    <tbody id="tabla-insumos">
                        <tr><td colspan="3" style="text-align: center; color: #999; padding: 15px;">Sin insumos registrados.</td></tr>
                    </tbody>
                </table>
            </div>

            <button type="button" id="btn-procesar" class="btn-modal" style="width: 100%; padding: 15px; font-size: 1.1em; font-weight: bold;">✅ Finalizar Mantenimiento</button>
        </div>
        
        <a href="index.php" style="display:block; text-align:center; margin-top: 15px; color: var(--uc-purple); text-decoration:none; font-weight:bold;">← Volver al Menú Principal</a>
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

    let maquinaActual = null;
    let insumosBD = [];
    let insumosUsados = [];

    // 1. Cargar datos iniciales
    function inicializar() {
        // Cargar Máquinas
        fetch('api/procesar_mantenimiento.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ accion: 'listar_maquinas' }) })
        .then(res => res.json()).then(data => renderizarMaquinas(data));

        // Cargar Insumos del Inventario
        fetch('api/listar_componentes.php').then(res => res.json()).then(data => {
            insumosBD = data.filter(item => item.tipo_item === 'Insumo' && item.stock > 0);
        });
    }

    // 2. Renderizar lista de máquinas
    function renderizarMaquinas(lista) {
        const cont = document.getElementById('lista-maquinas');
        cont.innerHTML = '';
        lista.forEach(m => {
            let esAlerta = m.estado === 'Requiere Mantenimiento';
            let div = document.createElement('div');
            div.className = `maquina-item ${esAlerta ? 'alerta' : ''}`;
            div.onclick = () => seleccionarMaquina(m, div);
            
            let indicador = esAlerta ? '🚨 ' : '✅ ';
            div.innerHTML = `
                <div>
                    <strong>${indicador}${escapeHTML(m.nombre)}</strong><br>
                    <small style="color:#666;">${escapeHTML(m.etiqueta || '')}</small>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:0.8em; font-weight:bold; color:${esAlerta ? '#e74c3c' : '#2ecc71'};">${m.estado}</span><br>
                    <small style="color:#666;">Uso: ${parseFloat(m.horas_uso).toFixed(1)}h / ${m.limite_mantenimiento}h</small>
                </div>
            `;
            cont.appendChild(div);
        });
    }

    // 3. Seleccionar máquina
    function seleccionarMaquina(m, el) {
        maquinaActual = m.id;
        document.querySelectorAll('.maquina-item').forEach(c => c.classList.remove('active'));
        el.classList.add('active');

        // Habilitar formulario
        const form = document.getElementById('form-mantenimiento');
        form.style.opacity = '1';
        form.style.pointerEvents = 'auto';
        document.getElementById('lbl-maquina').value = m.nombre;
    }

    // 4. Lógica del buscador de insumos
    const buscadorInsumos = document.getElementById('buscador-insumos');
    const resInsumos = document.getElementById('resultados-insumos');

    buscadorInsumos.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        resInsumos.innerHTML = '';
        if (q.length > 0) {
            const filtrados = insumosBD.filter(i => i.nombre.toLowerCase().includes(q));
            if (filtrados.length > 0) {
                resInsumos.style.display = 'block';
                filtrados.slice(0,5).forEach(i => {
                    let d = document.createElement('div');
                    d.className = 'resultado-insumo';
                    d.innerHTML = `<b>${escapeHTML(i.nombre)}</b> <small>(Disp: ${i.stock})</small>`;
                    d.onclick = () => agregarInsumo(i);
                    resInsumos.appendChild(d);
                });
            }
        } else {
            resInsumos.style.display = 'none';
        }
    });

    function agregarInsumo(item) {
        const existe = insumosUsados.find(i => i.id === item.id);
        if (existe) {
            if (existe.cantidad < item.stock) existe.cantidad++;
        } else {
            insumosUsados.push({ id: item.id, nombre: item.nombre, cantidad: 1, stock: item.stock });
        }
        buscadorInsumos.value = '';
        resInsumos.style.display = 'none';
        renderizarInsumos();
    }

    function cambiarCantidad(id, delta) {
        const i = insumosUsados.find(x => x.id === id);
        if(i) {
            i.cantidad += delta;
            if(i.cantidad <= 0) insumosUsados = insumosUsados.filter(x => x.id !== id);
            else if(i.cantidad > i.stock) i.cantidad = i.stock;
            renderizarInsumos();
        }
    }

    function renderizarInsumos() {
        const tbody = document.getElementById('tabla-insumos');
        tbody.innerHTML = '';
        if(insumosUsados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; color: #999; padding: 15px;">Sin insumos registrados.</td></tr>';
            return;
        }
        insumosUsados.forEach(i => {
            tbody.innerHTML += `
                <tr>
                    <td style="font-weight:bold; color:var(--uc-dark);">${escapeHTML(i.nombre)}</td>
                    <td>
                        <div style="display:flex; gap:5px;">
                            <button onclick="cambiarCantidad(${i.id}, -1)" style="padding:2px 5px;">-</button>
                            <span style="width:20px; text-align:center;">${i.cantidad}</span>
                            <button onclick="cambiarCantidad(${i.id}, 1)" style="padding:2px 5px;">+</button>
                        </div>
                    </td>
                    <td><button onclick="cambiarCantidad(${i.id}, -100)" style="background:#e74c3c; color:white; border:none; padding:2px 5px; border-radius:3px; cursor:pointer;">X</button></td>
                </tr>`;
        });
    }

    // 5. Procesar formulario final
    document.getElementById('btn-procesar').onclick = () => {
        const tecnico = document.getElementById('tecnico').value.trim();
        const desc = document.getElementById('descripcion').value.trim();
        const tipo = document.getElementById('tipo_servicio').value;
        
        // Capturar los tiempos
        const h = parseInt(document.getElementById('h_mant').value) || 0;
        const m = parseInt(document.getElementById('m_mant').value) || 0;
        const s = parseInt(document.getElementById('s_mant').value) || 0;

        if(!tecnico || !desc) return alert("❌ Debe ingresar el nombre del técnico y la descripción del trabajo.");
        if(h === 0 && m === 0 && s === 0) return alert("❌ Debe registrar el tiempo invertido en el mantenimiento.");

        const btn = document.getElementById('btn-procesar');
        btn.disabled = true; btn.textContent = "Guardando...";

        const payload = {
            accion: 'registrar_mantenimiento',
            maquina_id: maquinaActual,
            tecnico: tecnico,
            descripcion: desc,
            tipo: tipo,
            horas: h,
            minutos: m,
            segundos: s,
            insumos: insumosUsados
        };

        fetch('api/procesar_mantenimiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                alert("✅ " + data.mensaje);
                location.reload();
            } else {
                alert("❌ Error: " + data.mensaje);
                btn.disabled = false; btn.textContent = "✅ Finalizar Mantenimiento";
            }
        });
    };

    // Cerrar buscador al hacer clic fuera
    document.addEventListener('click', e => { if(!buscadorInsumos.contains(e.target)) resInsumos.style.display='none'; });

    inicializar();
</script>
</body>
</html>