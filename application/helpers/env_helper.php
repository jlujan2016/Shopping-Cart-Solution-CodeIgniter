<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function cargar_env() {
    // Buscar .env en múltiples ubicaciones posibles
    $posibles_rutas = [
        FCPATH . '.env',           // raíz del entry point actual
        FCPATH . '../.env',        // un nivel arriba
        APPPATH . '../.env',       // raíz del proyecto
        dirname(APPPATH) . '/.env' // alternativa
    ];

    $ruta = null;
    foreach ($posibles_rutas as $r) {
        if (file_exists($r)) {
            $ruta = $r;
            break;
        }
    }

    if (!$ruta) return; // no hay .env, usar valores por defecto

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if (strpos($linea, '#') === 0) continue;
        if (strpos($linea, '=') === false) continue;

        list($key, $value) = explode('=', $linea, 2);
        $key   = trim($key);
        $value = trim($value);

        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}