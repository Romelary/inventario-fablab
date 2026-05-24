<?php
require_once 'config/auth.php';
requiere_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salida de Insumos - UC</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .preview-card {
            background: #FFF; border: 1px solid var(--border-color);
            border-radius: 8px; padding: 20px; text-align: center;
            min-height: 320px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        #image-display {
            width: 100%; height: 220px; background: #f9f9f9; 
            border-radius: 4px; display: flex; align-items: center; 
            justify-content: center; margin-bottom: 15px; border: 1px dashed #DDD;
        }
        #image-display img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .chip-container { display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; }
        .chip { padding: 6px 12px; background: #eee; border-radius: 15px; cursor: pointer; font-size: 0.8em; }
        .chip.active { background: var(--uc-purple); color: white; }
    </style>
</head>
<body>

<div class="grid-container" style="max-width: 1000px;">
    <div>
        <h2 style="color: var(--uc-purple); margin-top: 0;">Registro de Consumo</h2>
        
        <div class="chip-container" id="filtros-categorias">
            <div class="chip active" onclick="cambiarFiltro('', this)">Todos</div>
        </div>

        <form id="form-consumo">
            <input type="hidden" id="componente_id" name="componente_id">
            
            <div class="form-group">
                <label>Nombre / Uso:</label>
                <input type="text" id="buscador" placeholder="Busque el componente..." autocomplete="off">
                <div id="resultados-busqueda"></div>
            </div>

            <div class="form-group">
                <label>Solicitante (DNI):</label>
                <input type="text" id="solicitante" name="solicitante" placeholder="DNI del estudiante">
            </div>

            <div class="form-group">
                <label>Proyecto:</label>
                <input type="text" id="proyecto" name="proyecto" placeholder="Ej: Brazo Hidráulico">
            </div>

            <div class="form-group">
                <label>Cantidad:</label>
                <input type="number" id="cantidad" name="cantidad" min="1" value="1" style="width: 100px;">
            </div>
        </form>
    </div>

    <div>
        <div class="preview-card">
            <div id="image-display">
                <span style="color: #bbb;">Seleccione un ítem</span>
            </div>
            <h3 id="display_nombre" style="margin: 10px 0; color: var(--uc-purple);">---</h3>
            <p id="display_info" style="font-size: 0.9em; color: #666;"></p>
            <div style="background: #F4F6F8; padding: 10px; border-radius: 4px; margin-top: 10px;">
                <small>Stock disponible:</small><br>
                <strong id="display_stock" style="font-size: 1.2em;">0</strong>
            </div>
        </div>

        <button type="button" id="btn-registrar" class="btn-modal" style="width: 100%; margin-top: 15px; height: 50px;">Confirmar Entrega</button>
        <button type="button" onclick="location.reload()" class="btn-modal btn-secundario" style="width: 100%; margin-top: 10px;">Limpiar</button>
        <a href="index.php" style="display:block; text-align:center; margin-top: 20px; color: var(--uc-purple); text-decoration:none; font-weight:bold;">← Menú Principal</a>
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

    let datosBD = [];
    let filtroActual = '';
    const buscador = document.getElementById('buscador');
    const resultados = document.getElementById('resultados-busqueda');

    function inicializar() {
        // 1. Cargar Categorías
        fetch('api/listar_categorias.php').then(res => res.json()).then(cats => {
            const cont = document.getElementById('filtros-categorias');
            cats.forEach(c => {
                let div = document.createElement('div');
                div.className = 'chip';
                div.textContent = c.nombre;
                div.onclick = () => cambiarFiltro(c.nombre, div);
                cont.appendChild(div);
            });
        });

        // 2. Cargar Inventario
        fetch('api/listar_componentes.php').then(res => res.json()).then(data => {
            datosBD = data;
        });
    }

    function cambiarFiltro(cat, el) {
        document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        filtroActual = cat;
        buscador.value = '';
        resultados.style.display = 'none';
    }

    buscador.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        resultados.innerHTML = '';
        if (query.length > 0 || filtroActual !== '') {
            const filtrados = datosBD.filter(item => {
                const matchNombre = item.nombre.toLowerCase().includes(query) || (item.descripcion && item.descripcion.toLowerCase().includes(query));
                const matchCat = filtroActual === '' || item.categoria === filtroActual;
                const esInsumo = item.tipo_item && item.tipo_item.toLowerCase().trim() === 'insumo';
                return matchNombre && matchCat && esInsumo;
            });

            if (filtrados.length > 0) {
                resultados.style.display = 'block';
                filtrados.slice(0, 8).forEach(item => {
                    let div = document.createElement('div');
                    div.className = 'resultado-item';
                    div.style.display = 'flex'; div.style.alignItems = 'center'; div.style.gap = '10px';
                    if (parseInt(item.stock) <= 0) {
                        div.style.opacity = '0.6';
                    }
                    const img = item.imagen_path ? `<img src="${item.imagen_path}" width="30">` : `<div style="width:30px;height:30px;background:#eee"></div>`;
                    const stockLabel = parseInt(item.stock) <= 0 ? ' <span style="color:#e74c3c;font-size:0.8em;font-weight:bold;">(Agotado)</span>' : ` <span style="color:#2ecc71;font-size:0.8em;">(Stock: ${item.stock})</span>`;
                    div.innerHTML = `${img} <div><strong>${escapeHTML(item.nombre)}</strong>${stockLabel}</div>`;
                    div.onclick = () => seleccionarItem(item);
                    resultados.appendChild(div);
                });
            }
        } else {
            resultados.style.display = 'none';
        }
    });

    function seleccionarItem(item) {
        // Completar datos del formulario
        document.getElementById('componente_id').value = item.id;
        document.getElementById('buscador').value = item.nombre;
        
        // Actualizar panel visual
        document.getElementById('display_nombre').textContent = item.nombre;
        document.getElementById('display_info').textContent = `${item.marca || ''}`;
        document.getElementById('display_stock').textContent = item.stock;
        
        // Mostrar imagen (La clave del problema)
        const display = document.getElementById('image-display');
        if (item.imagen_path) {
            display.innerHTML = `<img src="${item.imagen_path}">`;
        } else {
            display.innerHTML = '<span style="color:#bbb;">Sin Imagen</span>';
        }
        
        resultados.style.display = 'none';
        document.getElementById('solicitante').focus();
    }

    document.getElementById('btn-registrar').onclick = () => {
        const id = document.getElementById('componente_id').value;
        const cant = parseInt(document.getElementById('cantidad').value);
        const stock = parseInt(document.getElementById('display_stock').textContent);

        if(!id) return alert("Seleccione un componente");
        if(cant > stock) return alert("No hay suficiente stock");
        if(!document.getElementById('solicitante').value) return alert("Ingrese el DNI");

        fetch('api/procesar_consumo.php', {
            method: 'POST',
            body: new FormData(document.getElementById('form-consumo'))
        }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                alert("✅ Registro exitoso");
                location.reload();
            } else {
                alert("❌ " + data.mensaje);
            }
        });
    };

    inicializar();
</script>
</body>
</html>