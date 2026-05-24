<?php
require_once 'config/auth.php';
requiere_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepción de Equipos - UC</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .prestamo-card { background: white; border: 1px solid #DDD; padding: 15px; border-radius: 6px; margin-bottom: 10px; cursor: pointer; transition: 0.2s; border-left: 4px solid var(--uc-purple); }
        .prestamo-card:hover { background: #F4F6F8; transform: translateX(5px); }
        .prestamo-card.active { background: #F4F6F8; border-color: var(--uc-purple); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .bg-pendiente { background: #fef9e7; color: #f39c12; border: 1px solid #f1c40f; }
        .bg-parcial { background: #eaf2f8; color: #3498db; border: 1px solid #3498db; }
        
        .carrito-tabla th { background: #333; color: white; padding: 12px; text-align: left; }
        .carrito-tabla td { padding: 12px; border-bottom: 1px solid #EEE; vertical-align: middle; }
    </style>
</head>
<body>

<div class="grid-container" style="max-width: 1200px; grid-template-columns: 1fr 1.3fr; gap: 30px; align-items: stretch; box-sizing: border-box;">
    
    <div style="display: flex; flex-direction: column; background: white; padding: 20px; border-radius: 8px; border: 1px solid var(--border-color);">
        <h2 style="color: var(--uc-purple); margin-top: 0; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">📥 Préstamos Activos</h2>
        
        <input type="text" id="buscador-dni" placeholder="🔎 Buscar por DNI o Nombre..." style="width: 100%; padding: 12px; border: 1px solid #CCC; border-radius: 4px; margin-bottom: 15px; box-sizing: border-box; font-size: 1.05em;">

        <div id="lista-prestamos" style="flex: 1; overflow-y: auto; max-height: 500px; padding-right: 5px;">
            <p style="text-align:center; color:#999; margin-top: 20px;">Cargando préstamos...</p>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; background: #F8F9FA; padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box;">
        <h3 style="margin-top: 0; color: #333;">📋 Bandeja de Devolución</h3>
        
        <div id="info-alumno" style="background: white; padding: 15px; border-radius: 6px; border: 1px dashed #CCC; margin-bottom: 15px; display: none;">
            <h4 id="lbl-nombre" style="margin: 0 0 5px 0; color: var(--uc-purple);">---</h4>
            <p style="margin: 0; font-size: 0.9em; color: #666;">DNI: <b id="lbl-dni">---</b> | Proyecto: <span id="lbl-proyecto">---</span></p>
        </div>

        <div class="carrito-tabla" style="flex: 1; overflow-y: auto; max-height: 400px; min-height: 250px; border: 1px solid #DDD; background: white; border-radius: 4px;">
            <table width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="60" style="text-align: center;">Foto</th>
                        <th>Equipo Adeudado</th>
                        <th width="100" style="text-align: center;">A Devolver</th>
                    </tr>
                </thead>
                <tbody id="tabla-detalles">
                    <tr><td colspan="3" style="text-align: center; color: #999; padding: 40px 20px;">Seleccione un préstamo de la lista izquierda.</td></tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            <button type="button" id="btn-procesar" class="btn-modal" style="width: 100%; padding: 15px; font-size: 1.1em; font-weight: bold; background: #2ecc71;" disabled>Confirmar Recepción</button>
            <a href="index.php" style="display:block; text-align:center; margin-top: 15px; color: var(--uc-purple); text-decoration:none; font-weight:bold;">← Volver al Menú Principal</a>
        </div>
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

    let prestamosBD = [];
    let prestamoActual = null;
    let itemsAdeudados = [];

    // 1. Cargar Préstamos Activos
    function cargarPrestamos() {
        let fd = new FormData(); fd.append('accion', 'listar_pendientes');
        fetch('api/procesar_recepcion.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            prestamosBD = data;
            renderizarPrestamos(prestamosBD);
        });
    }

    // 2. Renderizar lista izquierda
    function renderizarPrestamos(lista) {
        const contenedor = document.getElementById('lista-prestamos');
        contenedor.innerHTML = '';

        if (lista.length === 0) {
            contenedor.innerHTML = '<p style="text-align:center; color:#999; padding: 20px;">No hay préstamos pendientes de devolución. 🎉</p>';
            return;
        }

        lista.forEach(p => {
            let badge = p.estado === 'Parcial' ? '<span class="badge bg-parcial">Dev. Parcial</span>' : '<span class="badge bg-pendiente">Pendiente</span>';
            
            let div = document.createElement('div');
            div.className = `prestamo-card ${prestamoActual === p.id ? 'active' : ''}`;
            div.onclick = () => seleccionarPrestamo(p.id, p, div);
            div.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 5px;">
                    <strong style="color: var(--uc-dark); font-size: 1.1em;">${escapeHTML(p.nombre_apellido)}</strong>
                    ${badge}
                </div>
                <div style="font-size: 0.85em; color: #666;">
                    DNI: <b>${escapeHTML(p.dni_alumno)}</b><br>
                    Fecha: ${p.fecha_prestamo || 'Reciente'}
                </div>
            `;
            contenedor.appendChild(div);
        });
    }

    // 3. Buscador en vivo
    document.getElementById('buscador-dni').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        const filtrados = prestamosBD.filter(p => (p.dni_alumno && p.dni_alumno.includes(q)) || (p.nombre_apellido && p.nombre_apellido.toLowerCase().includes(q)));
        renderizarPrestamos(filtrados);
    });

    // 4. Seleccionar un Préstamo y ver sus detalles
    function seleccionarPrestamo(id, dataAlumno, elementoVisual) {
        prestamoActual = id;
        document.querySelectorAll('.prestamo-card').forEach(c => c.classList.remove('active'));
        elementoVisual.classList.add('active');

        // Mostrar Info
        document.getElementById('info-alumno').style.display = 'block';
        document.getElementById('lbl-nombre').textContent = dataAlumno.nombre_apellido;
        document.getElementById('lbl-dni').textContent = dataAlumno.dni_alumno;
        document.getElementById('lbl-proyecto').textContent = dataAlumno.proyecto || 'General';

        // Cargar los items que debe
        document.getElementById('tabla-detalles').innerHTML = '<tr><td colspan="3" style="text-align:center;">Cargando herramientas...</td></tr>';
        
        let fd = new FormData(); 
        fd.append('accion', 'obtener_detalles'); 
        fd.append('prestamo_id', id);

        fetch('api/procesar_recepcion.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            itemsAdeudados = data;
            renderizarDetalles();
        });
    }

    // 5. Renderizar tabla de devoluciones
    function renderizarDetalles() {
        const tbody = document.getElementById('tabla-detalles');
        tbody.innerHTML = '';
        const btn = document.getElementById('btn-procesar');

        if (itemsAdeudados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#2ecc71; font-weight:bold;">Este alumno ya no debe herramientas.</td></tr>';
            btn.disabled = true;
            return;
        }

        btn.disabled = false;

        itemsAdeudados.forEach((item, index) => {
            const debe = parseInt(item.cantidad) - parseInt(item.devueltos);
            const rutaImg = item.imagen_path ? item.imagen_path : 'https://cdn-icons-png.flaticon.com/512/107/107817.png';
            
            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align: center;">
                    <img src="${rutaImg}" style="width: 45px; height: 45px; object-fit: contain; border-radius: 4px; border: 1px solid #DDD; padding:2px;">
                </td>
                <td>
                    <strong style="color: var(--uc-dark);">${escapeHTML(item.nombre)}</strong><br>
                    <small style="color: #666;">Faltan entregar: <b>${debe}</b></small>
                </td>
                <td style="text-align: center;">
                    <input type="number" id="dev_${index}" value="${debe}" min="0" max="${debe}" style="width: 60px; padding: 8px; text-align: center; border: 2px solid var(--uc-purple); border-radius: 4px; font-weight: bold; font-size: 1.1em;">
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // 6. Procesar y guardar en BD
    document.getElementById('btn-procesar').onclick = () => {
        if (!prestamoActual) return;

        let itemsAEnviar = [];
        itemsAdeudados.forEach((item, index) => {
            let cantidadInput = parseInt(document.getElementById(`dev_${index}`).value) || 0;
            if (cantidadInput > 0) {
                itemsAEnviar.push({
                    detalle_id: item.detalle_id,
                    componente_id: item.componente_id,
                    cantidad_devuelta: cantidadInput
                });
            }
        });

        if (itemsAEnviar.length === 0) return alert("❌ No ha ingresado ninguna cantidad a devolver.");

        const btn = document.getElementById('btn-procesar');
        btn.disabled = true; btn.textContent = "Procesando...";

        fetch('api/procesar_recepcion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'procesar_devolucion', prestamo_id: prestamoActual, items: itemsAEnviar })
        })
        .then(res => res.json()).then(data => {
            if (data.status === 'success') {
                alert("✅ " + data.mensaje);
                location.reload(); 
            } else {
                alert("❌ Error: " + data.mensaje);
                btn.disabled = false; btn.textContent = "Confirmar Recepción";
            }
        });
    };

    cargarPrestamos();
</script>
</body>
</html>