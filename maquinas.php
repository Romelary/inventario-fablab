<?php
require_once 'config/auth.php';
requiere_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uso de Máquinas - UC</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .contenedor-centrado { display: flex; justify-content: center; margin-top: 20px; }
        .card-maquina { background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); display: flex; flex-direction: column; width: 100%; max-width: 500px; }
        .estado-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8em; font-weight: bold; float: right; }
        .bg-operativo { background: #e8f8f5; color: #2ecc71; border: 1px solid #2ecc71; }
        .bg-mantenimiento { background: #fdedec; color: #e74c3c; border: 1px solid #e74c3c; }
        .progress-container { width: 100%; background: #eee; height: 12px; border-radius: 6px; margin-top: 10px; overflow: hidden; }
        .progress-bar { height: 100%; transition: width 0.5s ease; }
        .registro-box { background: #F8F9FA; border: 1px solid #DDD; border-radius: 6px; padding: 15px; margin-top: 20px; }
        .input-group { display: flex; gap: 10px; margin-bottom: 15px; }
        
        /* Pequeño ajuste para que el input del buscador se vea bien */
        input::-webkit-calendar-picker-indicator { opacity: 1; cursor: pointer; color: var(--uc-purple); }
    </style>
</head>
<body>

<div style="max-width: 1300px; margin: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 15px;">
        <h2 style="color: var(--uc-purple); margin: 0;">⚙️ Bitácora de Máquinas</h2>
        <div>
            <button type="button" class="btn-modal" onclick="abrirHistorial()" style="background: #333; margin-right: 10px;">📋 Ver Historial</button>
            <a href="index.php" style="display:block; text-align:center; margin-top: 20px; color: var(--uc-purple); text-decoration:none; font-weight:bold;">← Menú Principal</a>
        </div>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid #ddd; text-align: center;">
        <label style="font-weight: bold; color: var(--uc-purple); font-size: 1.2em; display: block; margin-bottom: 15px;">🔍 Buscar o seleccionar máquina:</label>
        
        <input list="lista-maquinas" id="buscador-maquina" onchange="mostrarMaquinaSeleccionada()" onmousedown="this.value=''" onfocus="this.value=''" placeholder="Haz clic aquí para ver la lista o empieza a escribir..." autocomplete="off" style="width: 100%; max-width: 500px; padding: 12px; border-radius: 6px; border: 2px solid var(--uc-purple); font-size: 1.05em; box-sizing: border-box; cursor: pointer;">
        
        <datalist id="lista-maquinas">
            </datalist>
    </div>

    <div class="contenedor-centrado" id="contenedor-maquinas">
        <div style="text-align: center; color: #888; padding: 40px; font-size: 1.1em;">
            ☝️ Selecciona una máquina arriba para ver sus detalles.
        </div>
    </div>
</div>

<div id="modalHistorial" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 2000;">
    <div style="background: white; padding: 25px; border-radius: 8px; width: 80%; max-width: 900px; text-align: left; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <h3 style="color: var(--uc-purple); margin-top:0;">Registro Histórico de Trabajos</h3>
        
        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; margin-top: 15px; border-radius:4px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9em;">
                <thead style="position: sticky; top: 0; z-index: 10; background: var(--uc-purple); color: white;">
                    <tr>
                        <th style="padding: 12px 10px;">Fecha y Hora</th>
                        <th style="padding: 12px 10px;">Máquina</th>
                        <th style="padding: 12px 10px;">Solicitante</th>
                        <th style="padding: 12px 10px;">Detalle del Trabajo</th>
                    </tr>
                </thead>
                <tbody id="tabla-historial">
                </tbody>
            </table>
        </div>

        <button onclick="document.getElementById('modalHistorial').style.display = 'none'" class="btn-modal btn-secundario" style="margin-top: 20px; padding: 10px 30px; width: 100%;">Cerrar</button>
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

    // Variable global
    let maquinasGuardadas = []; 

    // 1. CARGAR DATOS
    function cargarMaquinas() {
        let formData = new FormData();
        formData.append('accion', 'listar');

        fetch('api/procesar_bitacora.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            maquinasGuardadas = data;
            construirDesplegable(maquinasGuardadas);
        });
    }

    // 2. LLENAR EL DATALIST
    function construirDesplegable(lista) {
        const datalist = document.getElementById('lista-maquinas');
        datalist.innerHTML = ''; // Limpiamos opciones anteriores

        lista.forEach(m => {
            let opcion = document.createElement('option');
            // En un datalist, el value es lo que se autocompleta en la caja de texto
            opcion.value = `${m.nombre} - ${m.etiqueta || 'Sin etiqueta'}`;
            datalist.appendChild(opcion);
        });
    }

    // 3. MOSTRAR LA TARJETA AL SELECCIONAR
    function mostrarMaquinaSeleccionada() {
        const textoIngresado = document.getElementById('buscador-maquina').value;
        const contenedor = document.getElementById('contenedor-maquinas');

        // Si el usuario borra el texto, ocultamos la tarjeta
        if (!textoIngresado) {
            contenedor.innerHTML = '<div style="text-align: center; color: #888; padding: 40px; font-size: 1.1em;">☝️ Selecciona una máquina arriba para ver sus detalles.</div>';
            return;
        }

        // Buscamos cuál de nuestras máquinas guardadas coincide exactamente con el texto que eligió el usuario
        const m = maquinasGuardadas.find(maquina => 
            `${maquina.nombre} - ${maquina.etiqueta || 'Sin etiqueta'}` === textoIngresado
        );

        // Si escribió algo que no está en la lista, no hacemos nada
        if (!m) return;

        let limite = parseFloat(m.limite_mantenimiento) || 100;
        let horas = parseFloat(m.horas_uso) || 0;
        let porcentaje = Math.min((horas / limite) * 100, 100);
        
        let colorBarra = '#2ecc71'; 
        let estadoBadge = '<span class="estado-badge bg-operativo">Óptima</span>';
        let alertaMensaje = '';

        if (porcentaje >= 100) {
            colorBarra = '#e74c3c'; 
            estadoBadge = '<span class="estado-badge bg-mantenimiento">Mantenimiento Sugerido</span>';
            alertaMensaje = `<div style="background:#fdedec; padding:8px; border-radius:4px; margin-bottom:10px; color:#e74c3c; font-size:0.85em; text-align:center; font-weight:bold;">⚠️ Ha superado el límite recomendado de horas.</div>`;
        } else if (porcentaje >= 75) {
            colorBarra = '#f1c40f'; 
            estadoBadge = '<span class="estado-badge" style="background:#fef9e7; color:#f39c12; border: 1px solid #f1c40f;">Revisión Próxima</span>';
        }

        contenedor.innerHTML = `
            <div class="card-maquina">
                <div>
                    ${estadoBadge}
                    <h3 style="color: var(--uc-dark); margin:0 0 5px 0; font-size: 1.3em;">${escapeHTML(m.nombre)}</h3>
                    <p style="color: #666; font-size: 0.9em; margin: 0;">${escapeHTML(m.marca || '')} - ${escapeHTML(m.etiqueta || 'Sin etiqueta')}</p>
                </div>
                
                <div style="margin-top: 20px;">
                    <div style="display:flex; justify-content:space-between; font-size:0.9em; color:#555; font-weight: bold;">
                        <span>Uso Acumulado: ${horas.toFixed(2)}h</span>
                        <span>Límite: ${limite}h</span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: ${porcentaje}%; background-color: ${colorBarra};"></div>
                    </div>
                </div>
                
                <div class="registro-box">
                    ${alertaMensaje}
                    <h4 style="margin: 0 0 10px 0; color: #555; font-size: 0.9em;">Registrar Tiempo de Uso</h4>
                    
                    <div class="input-group" style="justify-content: center; align-items: center; background: #fff; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                        <input type="number" id="h_${m.id}" placeholder="00" min="0" style="width: 60px; text-align: center; border: 1px solid #ccc; border-radius: 4px; padding: 8px;"> <strong style="color: #555; margin-right: 10px;">h</strong>
                        <input type="number" id="m_${m.id}" placeholder="00" min="0" max="59" style="width: 60px; text-align: center; border: 1px solid #ccc; border-radius: 4px; padding: 8px;"> <strong style="color: #555; margin-right: 10px;">m</strong>
                        <input type="number" id="s_${m.id}" placeholder="00" min="0" max="59" style="width: 60px; text-align: center; border: 1px solid #ccc; border-radius: 4px; padding: 8px;"> <strong style="color: #555;">s</strong>
                    </div>

                    <input type="text" id="solicitante_${m.id}" placeholder="DNI del estudiante..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 10px; box-sizing: border-box;">
                    <input type="text" id="obs_${m.id}" placeholder="Descripción de la actividad (Opcional)..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 10px; box-sizing: border-box;">
                    
                    <button onclick="registrarTiempo(${m.id})" class="btn-modal" style="width: 100%; background: var(--uc-purple);">Registrar Trabajo</button>
                </div>
            </div>
        `;
    }

    // 4. ENVIAR DATOS A PHP
    function registrarTiempo(maquinaId) {
        const solicitante = document.getElementById(`solicitante_${maquinaId}`).value.trim();
        const h = parseInt(document.getElementById(`h_${maquinaId}`).value) || 0;
        const m = parseInt(document.getElementById(`m_${maquinaId}`).value) || 0;
        const s = parseInt(document.getElementById(`s_${maquinaId}`).value) || 0;
        const obs = document.getElementById(`obs_${maquinaId}`).value.trim();

        if(!solicitante) return alert("❌ Ingrese el DNI del estudiante.");
        if(h === 0 && m === 0 && s === 0) return alert("❌ Ingrese un tiempo de uso mayor a cero.");

        let fd = new FormData();
        fd.append('accion', 'registrar_uso'); 
        fd.append('maquina_id', maquinaId); 
        fd.append('solicitante', solicitante);
        fd.append('horas', h);
        fd.append('minutos', m);
        fd.append('segundos', s);
        fd.append('observaciones', obs);

        fetch('api/procesar_bitacora.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.mantenimiento_requerido) {
                    alert(`✅ Trabajo registrado.\n\n🔔 AVISO: La máquina superó su límite de horas. Se ha generado un recordatorio de mantenimiento en el sistema.`);
                } else {
                    alert(`✅ Tiempo registrado correctamente.`);
                }
                cargarMaquinas(); // Volvemos a pedir datos actualizados al servidor
                setTimeout(() => mostrarMaquinaSeleccionada(), 300); // Recargamos la tarjeta para ver la barra subir
            } else {
                alert("❌ Error: " + data.mensaje);
            }
        });
    }

    // 5. HISTORIAL DE TRABAJOS
    function abrirHistorial() {
        document.getElementById('modalHistorial').style.display = 'flex';
        const tbody = document.getElementById('tabla-historial');
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px;">Cargando historial...</td></tr>';

        let fd = new FormData();
        fd.append('accion', 'historial');

        fetch('api/procesar_bitacora.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'error') {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px; color:red;">Error: ${data.mensaje}</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px;">No hay registros de uso todavía.</td></tr>';
                return;
            }
            
            data.forEach(reg => {
                tbody.innerHTML += `
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px 10px; font-size: 0.85em;">${reg.fecha}</td>
                    <td style="padding: 12px 10px;">
                        <strong style="color: var(--uc-purple);">${escapeHTML(reg.maquina)}</strong><br>
                        <small style="color: #888;">${escapeHTML(reg.maquina_desc || 'Sin desc. técnica')}</small>
                    </td>
                    <td style="padding: 12px 10px; font-weight: bold;">${escapeHTML(reg.solicitante)}</td>
                    <td style="padding: 12px 10px; color: #444; background: #f9f9f9; border-left: 3px solid var(--uc-purple);">
                        ${escapeHTML(reg.observaciones || 'Sin detalles del trabajo')}
                    </td>
                </tr>`;
            });
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px; color:red;">Error de conexión.</td></tr>';
        });
    }

    // Arrancamos el proceso al cargar la página
    cargarMaquinas();
</script>
</body>
</html>