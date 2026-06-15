<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Joyas extends CI_Controller {

    protected $usuario_actual;

    public function __construct() {
        parent::__construct();
        $this->db = $this->load->database('default', TRUE);
        $this->load->helper('jwt');

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

    // GET /api/joyas
    public function index() {
        $joyas = $this->db->order_by('nombre')->get('joyas')->result();
        $this->json_response(true, 'Catálogo obtenido', $joyas);
    }

    // POST /api/joyas/crear
    public function crear() {
        $input = json_decode($this->input->raw_input_stream, true);

        if (empty($input['nombre'])) {
            $this->json_response(false, 'Nombre requerido', null, 400);
        }

        $data = array(
            'nombre'    => $input['nombre'],
            'categoria' => isset($input['categoria']) ? $input['categoria'] : '',
            'metal'     => isset($input['metal']) ? $input['metal'] : '',
            'peso_g'    => isset($input['peso_g']) ? $input['peso_g'] : 0,
            'precio'    => isset($input['precio']) ? $input['precio'] : 0,
            'ubicacion' => isset($input['ubicacion']) ? $input['ubicacion'] : 'Tienda',
            'estado'    => isset($input['estado']) ? $input['estado'] : 'En stock',
            'epc'       => !empty($input['epc']) ? $input['epc'] : null,
            'foto'      => isset($input['foto']) ? $input['foto'] : null,
        );

        if (!empty($data['epc'])) {
            $existe = $this->db->get_where('joyas', array('epc' => $data['epc']))->row();
            if ($existe) {
                $this->json_response(false, "EPC ya existe en '{$existe->nombre}'", null, 409);
            }
        }

        $this->db->insert('joyas', $data);
        $id = $this->db->insert_id();

        $this->json_response(true, 'Joya creada', array('id' => $id));
    }

    // POST /api/joyas/sync
    public function sync() {
        $input = json_decode($this->input->raw_input_stream, true);
        $joyas = isset($input['joyas']) ? $input['joyas'] : array();

        if (empty($joyas)) {
            $this->json_response(false, 'No hay joyas para sincronizar', null, 400);
        }

        $insertadas   = 0;
        $actualizadas = 0;
        $errores      = array();

        foreach ($joyas as $j) {
            if (empty($j['nombre'])) continue;

            $epc    = !empty($j['epc']) ? $j['epc'] : null;
            $existe = $epc ? $this->db->get_where('joyas', array('epc' => $epc))->row() : null;

            $data = array(
                'nombre'    => $j['nombre'],
                'categoria' => isset($j['categoria']) ? $j['categoria'] : '',
                'metal'     => isset($j['metal']) ? $j['metal'] : '',
                'peso_g'    => isset($j['peso_g']) ? $j['peso_g'] : 0,
                'precio'    => isset($j['precio']) ? $j['precio'] : 0,
                'ubicacion' => isset($j['ubicacion']) ? $j['ubicacion'] : 'Tienda',
                'estado'    => isset($j['estado']) ? $j['estado'] : 'En stock',
                'epc'       => $epc,
                'foto'      => isset($j['foto']) ? $j['foto'] : null,
            );

            if ($existe) {
                $this->db->where('id', $existe->id)->update('joyas', $data);
                $actualizadas++;
            } else {
                $this->db->insert('joyas', $data);
                $insertadas++;
            }
        }

        $this->json_response(true, 'Sincronización completa', array(
            'insertadas'   => $insertadas,
            'actualizadas' => $actualizadas,
            'errores'      => $errores,
        ));
    }

    private function json_response($success, $message, $data = null, $code = 200) {
        http_response_code($code);
        echo json_encode(array(
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }
}