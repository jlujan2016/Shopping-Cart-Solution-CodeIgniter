<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function desactivar_libs_api() {
    // Detectar si la URL es una ruta de API
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';


    if (strpos($uri, '/api/') !== false) {
        // Apuntar CI3 al autoload de la API
        define('ENVIRONMENT_API', true);
    }
}

function add_filter_autoload() {
    // Ruta al autoload
    $autoload_path = APPPATH . 'config/autoload.php';

    if (!file_exists($autoload_path)) return;

    // Cargar el autoload original
    include($autoload_path);

    // Remover librerías que no necesita la API
    $libs_a_remover = ['session', 'loop', 'ShoppingCart', 'Language', 'SendMail'];

    if (isset($autoload['libraries'])) {
        $autoload['libraries'] = array_diff(
            $autoload['libraries'],
            $libs_a_remover
        );
    }

    // Redefinir la constante de autoload en memoria
    // CI3 lee $autoload después de pre_system
    // Necesitamos modificar el archivo temporalmente en memoria
    
    // La forma más confiable en CI3 es via $_SERVER
    $_SERVER['CI_API_REQUEST'] = true;
}