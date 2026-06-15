<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tomas extends CI_Controller {

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
        
        // Normalizamos las llaves a minúsculas igual que en Joyas.php
        $headers = array_change_key_case($headers, CASE_LOWER);
        $auth = isset($headers['authorization']) ? $headers['authorization'] : '';

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


    // GET /api/tomas
    public function index() {
        $tomas = $this->db
            ->order_by('fecha', 'DESC')
            ->limit(50)
            ->get('tomas')
            ->result();

        $this->json_response(true, 'Tomas obtenidas', $tomas);
    }

    // POST /api/tomas/sync
    public function sync() {
        $input = json_decode($this->input->raw_input_stream, true);

        if (empty($input['numero']) || empty($input['fecha'])) {
            $this->json_response(false, 'Datos incompletos', null, 400);
        }

        $this->db->trans_start();

        $toma_data = array(
            'numero'             => $input['numero'],
            'fecha'              => $input['fecha'],
            'ubicacion'          => isset($input['ubicacion']) ? $input['ubicacion'] : 'Todas',
            'total_escaneadas'   => isset($input['total_escaneadas']) ? $input['total_escaneadas'] : 0,
            'total_ok'           => isset($input['total_ok']) ? $input['total_ok'] : 0,
            'total_faltantes'    => isset($input['total_faltantes']) ? $input['total_faltantes'] : 0,
            'total_no_esperadas' => isset($input['total_no_esperadas']) ? $input['total_no_esperadas'] : 0,
            'duracion_min'       => isset($input['duracion_min']) ? $input['duracion_min'] : 0,
            'usuario_id'         => isset($this->usuario_actual['id']) ? $this->usuario_actual['id'] : null,
        );

        $this->db->insert('tomas', $toma_data);
        $toma_id = $this->db->insert_id();

        $tags = isset($input['tags']) ? $input['tags'] : array();

        foreach ($tags as $tag) {
            $this->db->insert('toma_tags', array(
                'toma_id'    => $toma_id,
                'epc'        => $tag['epc'],
                'rssi'       => isset($tag['rssi']) ? $tag['rssi'] : null,
                'scanned_at' => isset($tag['scanned_at']) ? $tag['scanned_at'] : date('Y-m-d H:i:s'),
            ));
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            $this->json_response(false, 'Error guardando toma', null, 500);
        }

        $this->json_response(true, 'Toma sincronizada', array(
            'toma_id'        => $toma_id,
            'tags_guardados' => count($tags),
        ));
    }

    // GET /api/tomas/detalle/ID
    public function detalle($id = null) {
        if (!$id) {
            $this->json_response(false, 'ID requerido', null, 400);
        }

        $toma = $this->db->get_where('tomas', array('id' => $id))->row();

        if (!$toma) {
            $this->json_response(false, 'Toma no encontrada', null, 404);
        }

        $tags = $this->db->get_where('toma_tags', array('toma_id' => $id))->result();

        $this->json_response(true, 'Detalle obtenido', array(
            'toma' => $toma,
            'tags' => $tags,
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