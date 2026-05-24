# ⚙️ FABLAB UC - Sistema de Gestión de Inventario, Préstamos y Maquinaria

Este proyecto es una plataforma web ligera y segura diseñada para la administración de laboratorios de fabricación (FABLAB). Permite controlar el stock de herramientas y consumibles, gestionar préstamos temporales a alumnos, llevar una bitácora detallada del uso de maquinaria y programar mantenimientos preventivos o correctivos.

---

## 🚀 Características Principales

*   **Autenticación y Roles de Acceso (RBAC):**
    *   **Administrador (`admin`):** Control total del inventario, gestión de usuarios, carga masiva por CSV y exportación de reportes de auditoría.
    *   **Operador (`usuario`):** Permiso limitado para registrar préstamos, consumos, bitácora de uso de máquinas y mantenimientos cotidianos (interfaz de solo lectura para el catálogo de inventario).
*   **Seguridad Mejorada:**
    *   Sesiones protegidas con directivas de cookies seguras (`HttpOnly`, `SameSite=Lax`).
    *   Protección contra robo de sesiones mediante regeneración automática de ID al loguear.
    *   Bloqueo de descargas externas del archivo de base de datos SQLite mediante configuración `.htaccess`.
    *   Protección estricta para el **Administrador Principal (ID = 1)**: no puede ser eliminado, degradado, renombrado ni editado por otros administradores secundarios.
*   **Gestión Inteligente de Imágenes:**
    *   Ayudante de imágenes nativo con validación estricta de MIME-type (evita la subida de ejecutables maliciosos).
    *   Optimización automática (redimensionamiento a un máximo de 1000px y compresión WebP al 80%) para optimizar espacio en el servidor de hosting.
    *   Eliminación física automática de archivos antiguos al actualizar o borrar elementos del inventario.
*   **Auditoría Completa (Kardex):** Registro automático de ingresos iniciales, ajustes de stock y borrado de elementos identificando qué usuario realizó cada acción.

---

## 🛠️ Requisitos de Instalación

1.  **Servidor Web:** Apache, Nginx o IIS con soporte para **PHP 7.4 o superior**.
2.  **Base de Datos:** Soporte para **PDO SQLite** (`pdo_sqlite` habilitado en `php.ini`).
3.  **Librería Gráfica:** Extensión **GD de PHP** activa (requerida para la optimización automática de imágenes subidas).
4.  **Permisos de Escritura:** Permisos de escritura (`755` o `777`) en los directorios `data/` y `uploads/` para que el servidor web pueda modificar la base de datos y guardar archivos de imagen.

---

## ⚙️ Configuración Inicial e Inicio Rápido

1.  **Descarga:** Descarga o clona el código fuente en la carpeta pública de tu servidor (ej. `htdocs` en XAMPP).
2.  **Inicialización:** Abre la URL del sistema en tu navegador (ej. `http://localhost/Inventario_comp-main/`). La base de datos y la estructura de tablas se inicializarán automáticamente en el primer acceso.
3.  **Credenciales de Siembra por Defecto:**
    *   **Administrador Principal:**
        *   **Usuario:** `admin` (La contraseña por defecto se encuentra configurada en el archivo local `config/database.php`).
    *   **Administrador Auxiliar:**
        *   **Usuario:** `admin2` (La contraseña por defecto se encuentra configurada en el archivo local `config/database.php`).

> [!IMPORTANT]
> **Cambio de Contraseña:** Se recomienda enfáticamente cambiar las contraseñas predeterminadas inmediatamente después de iniciar sesión por primera vez a través del menú **Personal** en el panel de administración.

---

## 📄 Licencia y Propiedad

Este software es propiedad exclusiva de su autor. Queda prohibida la reproducción, distribución o modificación no autorizada de este código fuera de los fines académicos e institucionales acordados.

Desarrollado y adaptado para **FABLAB UC**.
