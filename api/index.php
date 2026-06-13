<?php
define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');

// Rutas — apuntan al mismo system/ y application/ del e-commerce
$system_path    = '../system';
$application_folder = '../application';

// Directorio de trabajo
if (($_temp = realpath($system_path)) !== FALSE) {
    $system_path = $_temp.DIRECTORY_SEPARATOR;
} else {
    $system_path = rtrim($system_path, '/\\').DIRECTORY_SEPARATOR;
}

if ( ! is_dir($system_path)) {
    header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
    echo 'Your system folder path does not appear to be set correctly.';
    exit(3);
}

define('SELF',        pathinfo(__FILE__, PATHINFO_BASENAME));
define('BASEPATH',    $system_path);
define('FCPATH',      dirname(__FILE__).DIRECTORY_SEPARATOR);
define('SYSPATH',     $system_path);
define('APPPATH',     realpath($application_folder).DIRECTORY_SEPARATOR);
define('VIEWPATH',    APPPATH.'views'.DIRECTORY_SEPARATOR);

// ===== CLAVE: Sobreescribir autoload ANTES de cargar CI =====
// Definir una constante que usaremos en autoload.php
define('IS_API', TRUE);


// Agrega esto en api/index.php para debug
// justo antes de require_once BASEPATH.'core/CodeIgniter.php'
if (!file_exists(APPPATH . 'config/database.php')) {
    die('No se encuentra database.php en: ' . APPPATH . 'config/database.php');
}

// Cargar variables de entorno
require_once APPPATH . 'helpers/env_helper.php';
cargar_env();


require_once BASEPATH.'core/CodeIgniter.php';
