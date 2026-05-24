<?php
require_once 'config/auth.php';
requiere_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Pedido de Préstamo - UC</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .carrito-tabla { margin-top: 20px; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden; background: white;}
        .carrito-tabla th { background: var(--uc-purple); color: white; padding: 12px; text-align: left; }
        .carrito-tabla td { padding: 12px; border-bottom: 1px solid #EEE; vertical-align: middle; }
        .btn-quitar { background: #E74C3C; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: 0.2s;}
        .btn-quitar:hover { background: #c0392b; }
        .buscador-herramientas { position: relative; margin-top: 15px; }
        #resultados-busqueda { position: absolute; z-index: 1000; background: white; width: 100%; border: 1px solid var(--border-color); border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-height: 250px; overflow-y: auto; display: none; }
        
        /* Estilos para las tarjetas de resultado y carrito */
        .resultado-item { padding: 10px; border-bottom: 1px solid #EEE; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: 0.2s;}
        .resultado-item:hover { background: #F4F6F8; }
        .img-miniatura { width: 40px; height: 40px; object-fit: contain; border-radius: 4px; border: 1px solid #DDD; background: #FFF; padding: 2px;}
        .item-info { display: flex; flex-direction: column; }
    </style>
</head>
<body>

<div class="grid-container" style="max-width: 1200px; grid-template-columns: 1fr 1.2fr; gap: 30px; align-items: stretch; box-sizing: border-box;">
    
    <div>
        <h2 style="color: var(--uc-purple); margin-top: 0; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">Nuevo Préstamo de Equipos</h2>
        
        <div style="background: #F8F9FA; padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 25px;">
            <h4 style="margin-top: 0; color: #333; margin-bottom: 15px;">👤 Datos del Solicitante</h4>
            <div class="form-group" style="margin-bottom: 15px;">
                <input type="text" id="dni_alumno" placeholder="DNI del Alumno" style="flex: 0.4; margin-right: 15px;">
                <input type="text" id="nombre_alumno" placeholder="Nombre completo" style="flex: 1;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <input type="text" id="proyecto" placeholder="Proyecto o curso (Ej. Taller de Robótica)" style="width: 100%;">
            </div>
        </div>

        <div class="buscador-herramientas">
            <h4 style="margin-top: 0; color: #333; margin-bottom: 10px;">🛠️ Agregar Herramientas al Pedido</h4>
            <input type="text" id="buscador" placeholder="🔎 Buscar por nombre, descripción o etiqueta..." autocomplete="off" style="width: 100%; font-size: 1.1em; padding: 15px; border: 2px solid var(--uc-purple); border-radius: 6px;">
            <div id="resultados-busqueda"></div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; background: #F8F9FA; padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box;">
        <h3 style="margin-top: 0; color: #333;">📦 Resumen del Pedido</h3>
        
        <div class="carrito-tabla" style="flex: 1; overflow-y: auto; max-height: 450px; min-height: 250px; border: 1px solid #DDD;">
            <table width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="70" style="text-align: center;">Foto</th>
                        <th>Equipo</th>
                        <th width="110" style="text-align: center;">Cant.</th>
                        <th width="60" style="text-align: center;">Quitar</th>
                    </tr>
                </thead>
                <tbody id="tabla-carrito">
                    <tr><td colspan="4" style="text-align: center; color: #999; padding: 40px 20px;">Seleccione herramientas en el buscador para agregarlas aquí.</td></tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            <button type="button" id="btn-procesar" class="btn-modal" style="width: 100%; padding: 15px; font-size: 1.1em; font-weight: bold; box-shadow: 0 4px 6px rgba(102, 0, 153, 0.2);">Confirmar Préstamo</button>
            <button type="button" onclick="location.reload()" class="btn-modal btn-secundario" style="width: 100%; margin-top: 10px; padding: 12px;">Cancelar / Limpiar Todo</button>
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

    let inventarioBD = [];
    let carrito = []; 
    const buscador = document.getElementById('buscador');
    const resultados = document.getElementById('resultados-busqueda');

   
    // 1. Cargar el Inventario (Herramientas y Equipos Menores)
    function cargarInventario() {
        fetch('api/listar_componentes.php')
        .then(res => res.json())
        .then(data => {
            // Ampliamos el filtro: Ahora acepta Herramientas O Equipos Menores (siempre que haya stock)
            inventarioBD = data.filter(item => 
                (item.tipo_item === 'Herramienta' || item.tipo_item === 'Equipos Menores') && item.stock > 0
            );
        });
    }

    // 2. Buscador en Vivo (Mejorado para buscar en todos los campos)
    buscador.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        resultados.innerHTML = '';
        
        if (query.length > 0) {
            const filtrados = inventarioBD.filter(item => {
                // Buscamos coincidencia en nombre, etiqueta O descripción
                return (item.nombre && item.nombre.toLowerCase().includes(query)) || 
                       (item.etiqueta && item.etiqueta.toLowerCase().includes(query)) ||
                       (item.descripcion && item.descripcion.toLowerCase().includes(query));
            });

            if (filtrados.length > 0) {
                resultados.style.display = 'block';
                // Mostramos un máximo de 8 resultados para no hacer una lista infinita
                filtrados.slice(0, 8).forEach(item => {
                    let div = document.createElement('div');
                    div.className = 'resultado-item';
                    
                    // Comprobamos si tiene imagen
                    const rutaImg = item.imagen_path ? item.imagen_path : 'https://cdn-icons-png.flaticon.com/512/107/107817.png'; // Icono por defecto si no hay foto
                    
                    div.innerHTML = `
                        <img src="${rutaImg}" class="img-miniatura" onerror="this.src='https://cdn-icons-png.flaticon.com/512/107/107817.png'">
                        <div class="item-info">
                            <strong style="color: var(--uc-dark);">${escapeHTML(item.nombre)}</strong>
                            <span style="font-size: 0.85em; color: #666;">Stock Disp: <b>${item.stock}</b></span>
                        </div>
                    `;
                    div.onclick = () => agregarAlCarrito(item);
                    resultados.appendChild(div);
                });
            } else {
                resultados.style.display = 'block';
                resultados.innerHTML = '<div style="padding: 15px; color: #E74C3C; text-align:center;">No se encontraron herramientas con esos datos.</div>';
            }
        } else {
            resultados.style.display = 'none';
        }
    });

    // 3. Lógica del Carrito
    function agregarAlCarrito(item) {
        const existe = carrito.find(c => c.id === item.id);
        
        if (existe) {
            if (existe.cantidad < item.stock) {
                existe.cantidad++;
            } else {
                alert(`⚠️ Solo hay ${item.stock} unidades de "${item.nombre}" disponibles para préstamo.`);
            }
        } else {
            carrito.push({ 
                id: item.id, 
                nombre: item.nombre, 
                imagen: item.imagen_path, // Guardamos la ruta de la imagen en el carrito
                cantidad: 1, 
                stock_maximo: item.stock 
            });
        }
        
        buscador.value = '';
        resultados.style.display = 'none';
        buscador.focus(); // Devuelve el cursor al buscador para seguir agregando rápido
        renderizarCarrito();
    }

    function cambiarCantidad(id, delta) {
        const item = carrito.find(c => c.id === id);
        if (item) {
            item.cantidad += delta;
            if (item.cantidad <= 0) {
                carrito = carrito.filter(c => c.id !== id);
            } else if (item.cantidad > item.stock_maximo) {
                item.cantidad = item.stock_maximo;
                alert(`Has alcanzado el límite de stock de este ítem (${item.stock_maximo}).`);
            }
            renderizarCarrito();
        }
    }

    function renderizarCarrito() {
        const tbody = document.getElementById('tabla-carrito');
        tbody.innerHTML = '';

        if (carrito.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #999; padding: 30px;">Seleccione herramientas en el buscador para agregarlas aquí.</td></tr>';
            return;
        }

        carrito.forEach(item => {
            let tr = document.createElement('tr');
            const rutaImg = item.imagen ? item.imagen : 'https://cdn-icons-png.flaticon.com/512/107/107817.png';
            
            tr.innerHTML = `
                <td style="text-align: center;">
                    <img src="${rutaImg}" style="width: 45px; height: 45px; object-fit: contain; border-radius: 4px; border: 1px solid #DDD; background:#FFF; padding:2px;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/107/107817.png'">
                </td>
                <td style="font-weight: bold; color: var(--uc-dark);">${escapeHTML(item.nombre)}</td>
                <td style="text-align: center;">
                    <div style="display:flex; align-items:center; justify-content:center; gap:8px; background:#F4F6F8; padding:5px; border-radius:4px; border:1px solid #DDD;">
                        <button onclick="cambiarCantidad(${item.id}, -1)" style="padding:2px 8px; cursor:pointer; font-weight:bold; border:1px solid #CCC; border-radius:3px;">-</button>
                        <span style="width:25px; text-align:center; font-weight:bold;">${item.cantidad}</span>
                        <button onclick="cambiarCantidad(${item.id}, 1)" style="padding:2px 8px; cursor:pointer; font-weight:bold; border:1px solid #CCC; border-radius:3px;">+</button>
                    </div>
                </td>
                <td style="text-align: center;">
                    <button onclick="cambiarCantidad(${item.id}, -100)" class="btn-quitar" title="Eliminar ítem">✖</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // 4. Procesar el Pedido y Enviar a PHP
    document.getElementById('btn-procesar').onclick = () => {
        const dni = document.getElementById('dni_alumno').value.trim();
        const nombre = document.getElementById('nombre_alumno').value.trim();
        const proyecto = document.getElementById('proyecto').value.trim();

        if (!dni || !nombre) return alert("❌ Debe ingresar el DNI y Nombre del solicitante.");
        if (carrito.length === 0) return alert("❌ El carrito está vacío. Agregue al menos una herramienta.");

        const btn = document.getElementById('btn-procesar');
        btn.disabled = true;
        btn.textContent = "Procesando...";

        const datosEnvio = {
            dni_alumno: dni,
            nombre_apellido: nombre,
            proyecto: proyecto,
            carrito: carrito
        };

        fetch('api/procesar_prestamo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datosEnvio)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("✅ " + data.mensaje);
                location.reload(); 
            } else {
                alert("❌ Error: " + data.mensaje);
                btn.disabled = false;
                btn.textContent = "Confirmar Préstamo";
            }
        }).catch(err => {
            alert("❌ Error de red al procesar el préstamo.");
            btn.disabled = false;
            btn.textContent = "Confirmar Préstamo";
        });
    };

    // Cerrar buscador si se hace clic fuera de él
    document.addEventListener('click', (e) => { 
        if(!buscador.contains(e.target) && !resultados.contains(e.target)) {
            resultados.style.display = 'none'; 
        }
    });
    
    // Volver a abrir buscador si se hace clic en el input y hay texto
    buscador.addEventListener('focus', function() {
        if(this.value.trim().length > 0 && resultados.innerHTML !== '') {
            resultados.style.display = 'block';
        }
    });

    // Iniciar
    cargarInventario();
</script>
</body>
</html>