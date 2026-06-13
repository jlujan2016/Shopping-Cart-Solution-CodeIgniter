<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // Forzar carga de BD directamente
        $this->load->database('default', TRUE);
        $this->db = $this->load->database('default', TRUE);

        $this->load->helper('jwt');

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json_response(false, 'Método no permitido', null, 405);
        }

        $usuario  = $this->input->post('usuario');
        $password = $this->input->post('password');

        if (empty($usuario) || empty($password)) {
            $this->json_response(false, 'Usuario y contraseña requeridos', null, 400);
        }

        $user = $this->db->get_where('usuarios', [
            'usuario' => $usuario,
            'activo'  => 1
        ])->row();

        if (!$user || !password_verify($password, $user->password)) {
            $this->json_response(false, 'Credenciales incorrectas', null, 401);
        }

        $token = generar_jwt([
            'id'      => $user->id,
            'usuario' => $user->usuario,
            'rol'     => $user->rol,
            'exp'     => time() + (8 * 3600)
        ]);

        $this->json_response(true, 'Login exitoso', [
            'token'   => $token,
            'usuario' => [
                'id'      => (int) $user->id,
                'nombre'  => $user->nombre,
                'usuario' => $user->usuario,
                'rol'     => $user->rol,
            ]
        ]);
    }

    private function json_response($success, $message, $data = null, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}