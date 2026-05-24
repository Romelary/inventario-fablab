# Documentación General del Proyecto - FABLAB UC

Este documento describe el funcionamiento, la arquitectura, el modelo de datos y las políticas de seguridad del sistema de control de inventario y préstamos de **FABLAB UC**. Ha sido diseñado para servir de guía rápida tanto para desarrolladores como para agentes de IA en futuras intervenciones.

---

## 1. Arquitectura y Tecnologías
El proyecto está construido bajo una pila de desarrollo web ligera y eficiente:
*   **Lenguaje:** PHP 7+ nativo (sin frameworks).
*   **Base de Datos:** SQLite3 (archivo único almacenado en `data/inventario.sqlite`).
*   **Diseño Visual:** HTML5, Vanilla CSS personalizado con variables CSS (`css/style.css`), y la tipografía premium *Outfit* de Google Fonts.
*   **Interactividad:** Vanilla Javascript mediante peticiones asíncronas (`fetch` API) e intercambio de datos en formato JSON.

---

## 2. Estructura de Directorios

```text
/ (Raíz del Proyecto)
│
├── api/                       # Endpoints backend que procesan AJAX (JSON)
│   ├── buscar.php             # Buscador de componentes
│   ├── dashboard_stats.php    # Estadísticas para el panel de control
│   ├── eliminar_categoria.php # Elimina categorías de inventario
│   ├── exportar_csv.php       # Generación de reportes CSV (Solo Admin)
│   ├── guardar_categoria.php  # Registro de nuevas categorías
│   ├── guardar_componente.php # API alternativa de guardado de componentes
│   ├── listar_categorias.php  # Obtiene la lista de áreas/categorías
│   ├── listar_componentes.php # Lista y filtra los elementos del inventario
│   ├── obtener_historial.php  # Historial global de movimientos
│   ├── procesar_bitacora.php  # Registro de uso de máquinas
│   ├── procesar_consumo.php   # Consumos de insumos
│   ├── procesar_importacion.php# Carga masiva mediante CSV (Solo Admin)
│   ├── procesar_inventario.php # CRUD de inventario (Solo Admin)
│   ├── procesar_mantenimiento.php# Registro de mantenimientos correctivos/preventivos
│   ├── procesar_prestamo.php   # Creación de préstamos a alumnos
│   ├── procesar_recepcion.php  # Devolución de préstamos
│   └── procesar_usuarios.php   # CRUD de usuarios (Solo Admin)
│
├── config/                    # Configuración interna y ayudantes
│   ├── auth.php               # Control de sesiones y autenticación RBAC
│   ├── database.php           # Conexión PDO a SQLite e inicializador
│   ├── image_helper.php       # Ayudante de optimización y borrado de imágenes
│   └── setup_db.php           # Script de creación del esquema de base de datos
│
├── css/
│   └── style.css              # Estilos visuales de la aplicación
│
├── data/                      # Almacén de base de datos (Protegido por HTTP)
│   ├── .htaccess              # Bloquea descargas web directas de la base de datos
│   └── inventario.sqlite      # Base de datos SQLite
│
├── uploads/                   # Almacén de imágenes de componentes subidas
│
├── index.php                  # Menú principal y dashboard del sistema
├── inventario.php             # Control y catálogo de inventario
├── consumos.php               # Despacho de insumos de un solo uso
├── entregar.php               # Préstamo de herramientas a alumnos (DNI/Nombre)
├── recibir.php                # Recepción y devolución de préstamos activos
├── mantenimientos.php         # Gestión y bitácora de mantenimientos
├── maquinas.php               # Panel de uso y horas acumuladas de máquinas
├── usuarios.php               # Panel de personal y roles (Solo Admin)
├── login.php                  # Pantalla de acceso (Glassmorphism)
└── logout.php                 # Cierre de sesión seguro
```

---

## 3. Modelo de Datos (SQLite)
El archivo de base de datos contiene las siguientes tablas principales:

1.  **`usuarios`**: Registra al personal autorizado para usar el sistema.
    *   *Roles:* `admin` (Administrador) y `usuario` (Operador).
    *   El usuario con `id = 1` y username `admin` es el administrador principal del sistema.
2.  **`categorias`**: Listado de áreas de catalogación (ej. Impresión 3D, Electrónica, Carpintería).
3.  **`componentes`**: Catálogo unificado de inventario.
    *   *Tipos (`tipo_item`):* `Herramienta`, `Insumo`, `Maquina`, `Equipos Menores`.
    *   *Estados (`estado`):* `Operativo`, `En Mantenimiento`, `Baja`, `Requiere Mantenimiento`.
    *   Valida en inserciones/actualizaciones que no existan componentes duplicados con el mismo nombre en la misma categoría.
4.  **`prestamos`** y **`detalle_prestamo`**: Registra las herramientas prestadas de manera temporal a alumnos mediante DNI, Nombre y Proyecto.
5.  **`consumos`**: Registra las salidas de materiales clasificados como `Insumo`.
    *   *Tolerancia especial:* Muestra insumos con stock `0` marcados como `(Agotado)` en el formulario en lugar de ocultarlos, garantizando la visibilidad del catálogo en todo momento.
6.  **`kardex`**: Log de auditoría de inventario (entradas, salidas, ajustes de stock y eliminaciones).
    *   Tiene relación `ON DELETE SET NULL` para mantener la auditoría del historial de movimientos históricos si un componente es eliminado.
7.  **`bitacora_maquinas`**: Bitácora de tiempos de uso y acumulado de horas para máquinas.
8.  **`mantenimientos`**: Historial de costos, tiempos y descripción de mantenimientos preventivos o correctivos.

---

## 4. Sistema de Seguridad y Roles (RBAC)

### Protección de Sesión:
*   Inicialización centralizada en `config/auth.php` con configuraciones restrictivas de cookies (`httponly = true`, `SameSite = Lax`).
*   Regeneración de ID de sesión (`session_regenerate_id()`) al autenticarse en `login.php` para mitigar ataques de fijación de sesión.
*   Bloqueo por directiva `.htaccess` en `data/` para impedir la descarga pública de `inventario.sqlite`.

### Niveles de Acceso:
*   **Administrador (`admin`):**
    *   Control total.
    *   Permiso para crear, editar, importar CSV, y eliminar componentes en inventario.
    *   Permiso para acceder a la descarga de reportes y al panel de personal/usuarios (`usuarios.php`).
*   **Operador (`usuario`):**
    *   Permisos limitados de registro de operaciones cotidianas (préstamos, consumos, mantenimientos, bitácoras de máquinas).
    *   Acceso al catálogo en modo lectura (los botones de modificar, agregar, eliminar, importar CSV y descargar reportes son ocultados dinámicamente tanto en frontend como protegidos en backend con errores `403 Forbidden` / `401 Unauthorized`).

### Reglas Críticas del Administrador Principal (ID = 1):
Para evitar bloqueos accidentales o sabotaje por parte de otros administradores secundarios:
1.  **No se puede eliminar:** La cuenta con `ID = 1` no puede eliminarse bajo ninguna circunstancia.
2.  **No se puede demotar:** Su rol no puede ser modificado de `admin` a `usuario`.
3.  **No se puede renombrar:** Su nombre de usuario siempre debe ser `'admin'`.
4.  **No editable por terceros:** Si un administrador conectado tiene un ID diferente de `1` (ej. `admin2`), no podrá modificar ningún dato de la cuenta del Administrador Principal. Toda la interfaz se deshabilitará visualmente al seleccionarlo en `usuarios.php` y el backend arrojará una excepción inmediata ante cualquier intento de alteración.
5.  **Autodegradación y Autoeliminación bloqueada:** Ningún administrador activo puede eliminar su propia cuenta o degradar sus propios privilegios a operador mientras tenga la sesión iniciada.

---

## 5. Gestión y Optimización de Imágenes
La subida y eliminación de imágenes de componentes se centraliza en [image_helper.php](file:///c:/xampp/htdocs/Nueva%20carpeta/Inventario_comp-main/config/image_helper.php):
*   **Validación Estricta:** Revisa las extensiones permitidas (`jpg, jpeg, png, gif, webp`) y valida el MIME type real mediante la librería `finfo` para evitar la subida de ejecutables maliciosos disfrazados de imágenes.
*   **Optimización de Tamaño (GD Library):** Si la extensión `GD` está instalada en el servidor PHP:
    *   Redimensiona imágenes que superen los 1000px en su lado más largo para conservar espacio de disco.
    *   Preserva la transparencia en formato PNG y WebP.
    *   Convierte las imágenes preferentemente a formato WebP comprimido (80% calidad) o en su defecto JPEG, reduciendo el ancho de banda del hosting.
*   **Independencia de Directorio de Trabajo (CWD):** El helper de imágenes utiliza rutas físicas absolutas mediante `dirname(__DIR__)`. Esto asegura que al eliminar un componente o actualizar su imagen, el archivo de imagen anterior se elimine de forma permanente en el disco mediante `unlink()`, evitando archivos huérfanos sin importar desde qué script o nivel jerárquico de carpetas se invoque la API.

---

## 6. Credenciales de Siembra por Defecto
Al inicializar la base de datos por primera vez (o si la tabla de usuarios se encuentra vacía), se siembran las siguientes cuentas de administración:
1.  **Administrador Principal:**
    *   *Usuario:* `admin`
    *   *Contraseña:* `admin12345`
2.  **Administrador Auxiliar:**
    *   *Usuario:* `admin2`
    *   *Contraseña:* `admin54321`

*Nota: Se recomienda cambiar las contraseñas al realizar el despliegue final en producción.*
