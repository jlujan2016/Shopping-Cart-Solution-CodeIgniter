<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Clave secreta — cámbiala por algo único y guárdala segura
define('JWT_SECRET', 'cambia_esto_por_una_clave_larga_y_aleatoria_2026');

function generar_jwt($payload) {
    $header = base64_url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload_encoded = base64_url_encode(json_encode($payload));

    $signature = hash_hmac('sha256', "$header.$payload_encoded", JWT_SECRET, true);
    $signature_encoded = base64_url_encode($signature);

    return "$header.$payload_encoded.$signature_encoded";
}

function verificar_jwt($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    list($header, $payload, $signature) = $parts;

    $valid_signature = base64_url_encode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    );

    if ($signature !== $valid_signature) return false;

    $data = json_decode(base64_url_decode($payload), true);

    if (isset($data['exp']) && $data['exp'] < time()) return false; // expirado

    return $data;
}

function base64_url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64_url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}