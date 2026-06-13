<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_Controller extends CI_Controller {

    protected $usuario_actual;

    public function __construct() {
        parent::__construct();

        // Descargar librerías del e-commerce que no necesita la API
        // Esto evita el error de Session y otras librerías
        $libs = ['session', 'loop', 'ShoppingCart', 'Language', 'SendMail'];
        foreach ($libs as $lib) {
            $lower = strtolower($lib);
            if (isset($this->$lower)) {
                unset($this->$lower);
            }
        }

        // Cargar solo lo que necesita la API
        $this->load->database();
        $this->load->helper('jwt');
        $this->load->helper('env');
        cargar_env();

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $this->validar_token();
    }

    private function validar_token() {
        $headers = $this->input->request_headers();
        $auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (strpos($auth, 'Bearer ') !== 0) {
            $this->json_response(false, 'Token no proporcionado', null, 401);
        }

        $token = substr($auth, 7);
        $data = verificar_jwt($token);

        if (!$data) {
            $this->json_response(false, 'Token inválido o expirado', null, 401);
        }

        $this->usuario_actual = $data;
    }

    protected function json_response($success, $message, $data = null, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}