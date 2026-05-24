<?php
// config/image_helper.php

/**
 * Valida, optimiza (redimensiona si supera 1000px y comprime) y guarda una imagen subida.
 * Utiliza la extensión GD si está disponible. Si no, hace un guardado directo seguro.
 * 
 * @param array $file El elemento de $_FILES correspondiente (ej. $_FILES['imagen_upload'])
 * @param string $destino_dir Ruta física de la carpeta destino (ej. '../uploads/')
 * @return string Nombre seguro del archivo creado (sin el prefijo del directorio)
 * @throws Exception Si hay problemas con el archivo o la validación
 */
function optimizarYGuardarImagen($file, $destino_dir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error al subir el archivo.");
    }

    $nombre_original = $file['name'];
    $ruta_temporal = $file['tmp_name'];

    // 1. Verificar extensión
    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $extensiones_permitidas)) {
        throw new Exception("Extensión de archivo no permitida. Solo se admiten: jpg, jpeg, png, gif, webp.");
    }

    // 2. Verificar MIME type real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $ruta_temporal);
    finfo_close($finfo);

    $mimes_permitidos = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    if (!in_array($mime_type, $mimes_permitidos)) {
        throw new Exception("Tipo de archivo real no permitido (MIME: $mime_type).");
    }

    // Si el destino empieza con '../', lo convertimos a una ruta absoluta basada en la ubicación del helper
    if (strpos($destino_dir, '../') === 0) {
        $destino_dir = dirname(__DIR__) . '/' . substr($destino_dir, 3);
    }

    // Asegurar que el directorio de destino exista
    if (!is_dir($destino_dir)) {
        mkdir($destino_dir, 0777, true);
    }

    $nombre_base = md5(uniqid() . time());

    // Intentar optimizar con GD
    if (extension_loaded('gd')) {
        try {
            $img = false;
            switch ($mime_type) {
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($ruta_temporal);
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($ruta_temporal);
                    break;
                case 'image/webp':
                    $img = imagecreatefromwebp($ruta_temporal);
                    break;
                case 'image/gif':
                    $img = imagecreatefromgif($ruta_temporal);
                    break;
            }

            if ($img !== false) {
                $width = imagesx($img);
                $height = imagesy($img);
                $max_size = 1000; // Máximo tamaño permitido para ancho o alto

                // Redimensionar proporcionalmente si excede max_size
                if ($width > $max_size || $height > $max_size) {
                    if ($width > $height) {
                        $new_width = $max_size;
                        $new_height = floor($height * ($max_size / $width));
                    } else {
                        $new_height = $max_size;
                        $new_width = floor($width * ($max_size / $height));
                    }

                    $tmp_img = imagecreatetruecolor($new_width, $new_height);

                    // Preservar transparencia para PNG y WebP
                    if ($mime_type === 'image/png' || $mime_type === 'image/webp') {
                        imagealphablending($tmp_img, false);
                        imagesavealpha($tmp_img, true);
                        $transparent = imagecolorallocatealpha($tmp_img, 255, 255, 255, 127);
                        imagefilledrectangle($tmp_img, 0, 0, $new_width, $new_height, $transparent);
                    }

                    imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagedestroy($img);
                    $img = $tmp_img;
                }

                // Guardar la imagen optimizada
                // Para GIFs, los guardamos tal cual (para no romper animaciones si las hay) o usamos imagegif
                if ($mime_type === 'image/gif') {
                    $nombre_seguro = $nombre_base . '.gif';
                    $ruta_destino = rtrim($destino_dir, '/') . '/' . $nombre_seguro;
                    imagegif($img, $ruta_destino);
                } elseif (function_exists('imagewebp')) {
                    // Convertir a WebP
                    $nombre_seguro = $nombre_base . '.webp';
                    $ruta_destino = rtrim($destino_dir, '/') . '/' . $nombre_seguro;
                    imagewebp($img, $ruta_destino, 80); // 80% de calidad
                } else {
                    // Si no soporta WebP, guardar como JPEG
                    $nombre_seguro = $nombre_base . '.jpg';
                    $ruta_destino = rtrim($destino_dir, '/') . '/' . $nombre_seguro;
                    imagejpeg($img, $ruta_destino, 80); // 80% de calidad
                }

                imagedestroy($img);
                return $nombre_seguro;
            }
        } catch (Exception $e) {
            // Si la optimización falla, registramos el error y hacemos fallback al move_uploaded_file
            error_log("Error al procesar imagen con GD: " . $e->getMessage());
        }
    }

    // Fallback: Guardado directo sin optimizar si no está GD disponible o si ocurrió un error
    $nombre_seguro = $nombre_base . '.' . $extension;
    $ruta_destino = rtrim($destino_dir, '/') . '/' . $nombre_seguro;
    if (move_uploaded_file($ruta_temporal, $ruta_destino)) {
        return $nombre_seguro;
    } else {
        throw new Exception("No se pudo mover el archivo subido al destino final.");
    }
}

/**
 * Elimina físicamente un archivo de imagen si existe en el disco.
 * 
 * @param string|null $imagen_path_relativa Ruta guardada en base de datos (ej. 'uploads/xxxx.webp')
 * @param string $root_dir Directorio raíz relativo al script que lo ejecuta (ej. '../')
 * @return bool True si se eliminó con éxito, False de lo contrario
 */
function eliminarImagenFisica($imagen_path_relativa, $root_dir = '../') {
    if (empty($imagen_path_relativa)) {
        return false;
    }

    // Sanitización básica para evitar directory traversal
    $imagen_path_relativa = str_replace(['../', '..\\'], '', $imagen_path_relativa);
    
    // Si la ruta del root es relativa '../', la hacemos absoluta con el directorio de este helper
    if ($root_dir === '../' || $root_dir === '..') {
        $root_dir = dirname(__DIR__);
    }
    
    // Armar la ruta física completa de forma absoluta y limpia
    $ruta_completa = rtrim($root_dir, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagen_path_relativa);

    if (file_exists($ruta_completa) && is_file($ruta_completa)) {
        return @unlink($ruta_completa);
    }

    return false;
}
