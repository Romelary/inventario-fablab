<?php
// usuarios.php
require_once 'config/auth.php';
requiere_rol('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - FABLAB UC</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            display: inline-block;
        }
        .role-admin {
            background: #e8d0ff;
            color: var(--uc-purple);
            border: 1px solid #d4b2f7;
        }
        .role-user {
            background: #e8f8f5;
            color: #2ecc71;
            border: 1px solid #a3e4d7;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 25px;
            width: 100%;
        }

        .header-section h2 {
            margin: 0;
            color: var(--uc-purple);
            font-weight: 700;
        }

        .help-text {
            font-size: 0.85em;
            color: var(--text-muted);
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>

<div class="grid-container" style="max-width: 1100px; grid-template-columns: 1.2fr 1fr; gap: 30px; align-items: stretch;">
    
    <!-- Columna Izquierda: Listado -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
        <div class="header-section" style="border:none; margin-bottom:15px; padding-bottom:0;">
            <h3>👥 Personal Registrado</h3>
        </div>
        
        <input type="text" id="buscador-usuarios" placeholder="🔍 Buscar por nombre o usuario..." style="width: 100%; padding: 12px; border: 1px solid #CCC; border-radius: 6px; margin-bottom: 15px; box-sizing: border-box; font-size: 0.95em;">

        <div class="tabla-contenedor" style="flex: 1; max-height: 480px; border: 1px solid var(--border-color); border-radius: 6px; overflow-y: auto;">
            <table width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th style="text-align: center;">Rol</th>
                    </tr>
                </thead>
                <tbody id="tabla-usuarios">
                    <tr><td colspan="3" style="text-align:center; padding:20px; color:#999;">Cargando personal...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Columna Derecha: Formulario -->
    <div style="background: #F8F9FA; padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 id="titulo-form" style="margin-top: 0; color: #333; margin-bottom: 20px; border-bottom: 2px solid #EEE; padding-bottom: 10px;">👤 Registrar Nuevo Usuario</h3>
            
            <form id="form-usuario" autocomplete="off">
                <input type="hidden" id="usuario_id" name="id">
                
                <div class="form-group">
                    <label for="username">Usuario (Login):</label>
                    <input type="text" id="username" name="username" placeholder="Ej. juan.perez" required>
                </div>

                <div class="form-group">
                    <label for="nombre">Nombre Completo:</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ej. Juan Pérez Medina" required>
                </div>

                <div class="form-group">
                    <label for="rol">Rol de Acceso:</label>
                    <select id="rol" name="rol" style="font-weight: bold; color: var(--uc-purple);">
                        <option value="usuario">Operador / Colaborador (Lectura y Préstamos)</option>
                        <option value="admin">Administrador (Control Total del Sistema)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" placeholder="Min. 6 caracteres">
                    <span class="help-text" id="pass-help">Establece la clave para ingresar al sistema.</span>
                </div>
            </form>
        </div>

        <div style="margin-top: 30px;">
            <div class="botones-accion">
                <button type="button" id="btn-guardar" style="flex: 2; height: 45px;">Guardar Usuario</button>
                <button type="button" id="btn-limpiar" class="btn-secundario" style="flex: 1;">Limpiar</button>
            </div>
            <button type="button" id="btn-eliminar" class="btn-eliminar" style="width: 100%; margin-top: 10px; display: none; height: 40px;">Eliminar Cuenta</button>
            
            <a href="index.php" style="display:block; text-align:center; margin-top: 25px; color: var(--uc-purple); text-decoration:none; font-weight:bold; font-size: 0.95em;">← Volver al Menú Principal</a>
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

    let usuariosBD = [];
    const tbody = document.getElementById('tabla-usuarios');
    const form = document.getElementById('form-usuario');

    // 1. CARGAR USUARIOS
    function cargarUsuarios() {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:20px;">Cargando...</td></tr>';
        
        fetch('api/procesar_usuarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'listar' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'error') {
                tbody.innerHTML = `<tr><td colspan="3" style="text-align:center; padding:20px; color:red;">${data.mensaje}</td></tr>`;
                return;
            }
            usuariosBD = data;
            renderizarTabla(usuariosBD);
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:20px; color:red;">Error de conexión.</td></tr>';
        });
    }

    // 2. RENDERIZAR TABLA
    function renderizarTabla(lista) {
        tbody.innerHTML = '';
        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:20px; color:#999;">No hay usuarios que coincidan.</td></tr>';
            return;
        }

        lista.forEach(user => {
            let tr = document.createElement('tr');
            tr.onclick = () => cargarEnFormulario(user);
            
            let badgeClass = user.rol === 'admin' ? 'role-badge role-admin' : 'role-badge role-user';
            let badgeText = user.rol === 'admin' ? 'Admin' : 'Operador';
            
            tr.innerHTML = `
                <td style="font-weight:bold; color:var(--uc-purple);">${escapeHTML(user.username)}</td>
                <td>${escapeHTML(user.nombre)}</td>
                <td style="text-align:center;"><span class="${badgeClass}">${badgeText}</span></td>
            `;
            tbody.appendChild(tr);
        });
    }

    // 3. BUSCADOR EN VIVO
    document.getElementById('buscador-usuarios').addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        const filtrados = usuariosBD.filter(u => 
            u.username.toLowerCase().includes(q) || 
            u.nombre.toLowerCase().includes(q)
        );
        renderizarTabla(filtrados);
    });

    const usuarioActivoId = <?php echo json_encode($_SESSION['usuario_id']); ?>;

    // 4. CARGAR EN FORMULARIO PARA EDITAR
    function cargarEnFormulario(user) {
        document.getElementById('usuario_id').value = user.id;
        document.getElementById('username').value = user.username;
        document.getElementById('nombre').value = user.nombre;
        document.getElementById('rol').value = user.rol;
        document.getElementById('password').value = '';
        
        document.getElementById('titulo-form').textContent = '📝 Editar Datos de Usuario';
        document.getElementById('pass-help').textContent = 'Deja la contraseña en blanco para mantener la actual.';
        document.getElementById('password').placeholder = '•••••• (Opcional)';
        
        // Si el usuario seleccionado es el administrador principal (id = 1)
        // y el usuario activo NO es el administrador principal, deshabilitamos todo el formulario y el botón de guardar
        if (user.id == 1 && usuarioActivoId != 1) {
            document.getElementById('username').disabled = true;
            document.getElementById('nombre').disabled = true;
            document.getElementById('rol').disabled = true;
            document.getElementById('password').disabled = true;
            document.getElementById('btn-guardar').disabled = true;
            document.getElementById('btn-guardar').textContent = 'No Editable (Admin Principal)';
        } else {
            // Re-habilitar los campos por si estaban deshabilitados
            document.getElementById('nombre').disabled = false;
            document.getElementById('password').disabled = false;
            document.getElementById('btn-guardar').disabled = false;
            document.getElementById('btn-guardar').textContent = 'Guardar Usuario';

            // Deshabilitar la edición de rol y username según corresponda
            if (user.id == 1) {
                document.getElementById('rol').disabled = true;
                document.getElementById('username').disabled = true;
            } else {
                document.getElementById('username').disabled = false;
                if (user.id == usuarioActivoId) {
                    document.getElementById('rol').disabled = true;
                } else {
                    document.getElementById('rol').disabled = false;
                }
            }
        }
        
        // No permitir eliminar al administrador principal (id = 1) ni al usuario activo
        if (user.id == usuarioActivoId || user.id == 1) {
            document.getElementById('btn-eliminar').style.display = 'none';
        } else {
            document.getElementById('btn-eliminar').style.display = 'block';
        }
    }

    // 5. LIMPIAR FORMULARIO (NUEVO)
    document.getElementById('btn-limpiar').onclick = () => {
        form.reset();
        document.getElementById('rol').disabled = false;
        document.getElementById('username').disabled = false;
        document.getElementById('nombre').disabled = false;
        document.getElementById('password').disabled = false;
        document.getElementById('btn-guardar').disabled = false;
        document.getElementById('btn-guardar').textContent = 'Guardar Usuario';
        document.getElementById('usuario_id').value = '';
        document.getElementById('titulo-form').textContent = '👤 Registrar Nuevo Usuario';
        document.getElementById('pass-help').textContent = 'Establece la clave para ingresar al sistema.';
        document.getElementById('password').placeholder = 'Min. 6 caracteres';
        document.getElementById('btn-eliminar').style.display = 'none';
    };

    // 6. GUARDAR / ACTUALIZAR
    document.getElementById('btn-guardar').onclick = () => {
        const id = document.getElementById('usuario_id').value;
        const username = document.getElementById('username').value.trim();
        const nombre = document.getElementById('nombre').value.trim();
        const rol = document.getElementById('rol').value;
        const password = document.getElementById('password').value;

        if (!username || !nombre) {
            return alert("❌ Por favor complete los campos obligatorios: Usuario y Nombre.");
        }

        if (!id && !password) {
            return alert("❌ La contraseña es obligatoria para nuevos registros.");
        }

        if (password && password.length < 6) {
            return alert("❌ La contraseña debe tener al menos 6 caracteres por seguridad.");
        }

        const payload = {
            accion: 'guardar',
            id: id,
            username: username,
            nombre: nombre,
            rol: rol,
            password: password
        };

        const btn = document.getElementById('btn-guardar');
        btn.disabled = true;
        btn.textContent = 'Procesando...';

        fetch('api/procesar_usuarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Guardar Usuario';
            
            if (data.status === 'success') {
                alert("✅ " + data.mensaje);
                document.getElementById('btn-limpiar').click();
                cargarUsuarios();
            } else {
                alert("❌ Error: " + data.mensaje);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Guardar Usuario';
            alert("❌ Error de comunicación con la API.");
        });
    };

    // 7. ELIMINAR
    document.getElementById('btn-eliminar').onclick = () => {
        const id = document.getElementById('usuario_id').value;
        const username = document.getElementById('username').value;
        if (!id) return;

        if (confirm(`⚠️ ¿Está seguro de eliminar de forma permanente la cuenta del usuario "${username}"?\nEsta acción no se puede deshacer.`)) {
            fetch('api/procesar_usuarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'eliminar', id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("✅ " + data.mensaje);
                    document.getElementById('btn-limpiar').click();
                    cargarUsuarios();
                } else {
                    alert("❌ Error: " + data.mensaje);
                }
            })
            .catch(err => {
                alert("❌ Error al intentar eliminar el usuario.");
            });
        }
    };

    // Arrancar al cargar
    cargarUsuarios();
</script>

</body>
</html>
