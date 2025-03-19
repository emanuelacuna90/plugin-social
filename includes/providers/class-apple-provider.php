<?php
/**
 * Proveedor de autenticación de Apple
 *
 * @package Social_Login
 */

class Apple_Provider extends Auth_Provider {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('apple');
    }

    /**
     * Valida el token de Apple
     *
     * @param string $token Token de Apple a validar.
     * @return array|WP_Error Array con datos del usuario si es válido, WP_Error si no.
     */
    public function validate_token($token) {
        // URL de la API de Apple para validar tokens
        $url = 'https://appleid.apple.com/auth/token';

        // Realizar la petición a Apple
        $response = wp_remote_post($url, array(
            'body' => array(
                'client_id' => get_option('social_login_apple_client_id'),
                'client_secret' => get_option('social_login_apple_client_secret'),
                'grant_type' => 'authorization_code',
                'code' => $token
            )
        ));

        // Verificar si hubo error en la petición
        if (is_wp_error($response)) {
            return new WP_Error(
                'invalid_token',
                'Error al validar el token de Apple',
                array('status' => 401)
            );
        }

        // Obtener el cuerpo de la respuesta
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Verificar si el token es válido
        if (empty($data['id_token'])) {
            return new WP_Error(
                'invalid_token',
                'Token de Apple inválido',
                array('status' => 401)
            );
        }

        // Decodificar el JWT de Apple
        $jwt_parts = explode('.', $data['id_token']);
        if (count($jwt_parts) !== 3) {
            return new WP_Error(
                'invalid_token',
                'Token de Apple malformado',
                array('status' => 401)
            );
        }

        $payload = json_decode(base64_decode($jwt_parts[1]), true);

        // Devolver los datos del usuario
        return array(
            'provider_id' => $payload['sub'],
            'email' => $payload['email'],
            // Apple no proporciona nombre por defecto, estos campos podrían venir
            // en la primera autenticación y deberían guardarse
            'name' => '',
            'given_name' => '',
            'family_name' => ''
        );
    }
} 