<?php
/**
 * Proveedor de autenticación de Google
 *
 * @package Social_Login
 */

class Google_Provider extends Auth_Provider {
    /**
     * ID de cliente de Google
     *
     * @var string
     */
    private $client_id;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('google');
        
        // Obtener credenciales de las opciones de WordPress
        $this->client_id = get_option('social_login_google_client_id');
    }

    /**
     * Valida el token de Google
     *
     * @param string $token Token de Google a validar.
     * @return array|WP_Error Array con datos del usuario si es válido, WP_Error si no.
     */
    public function validate_token($token) {
        // URL de la API de Google para validar tokens
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $token;

        // Realizar la petición a Google
        $response = wp_remote_get($url);

        // Verificar si hubo error en la petición
        if (is_wp_error($response)) {
            return new WP_Error(
                'invalid_token',
                'Error al validar el token de Google',
                array('status' => 401)
            );
        }

        // Obtener el cuerpo de la respuesta
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Verificar si el token es válido
        if (empty($data['sub'])) {
            return new WP_Error(
                'invalid_token',
                'Token de Google inválido',
                array('status' => 401)
            );
        }

        // Verificar que el token fue emitido para nuestra aplicación
        if ($data['aud'] !== $this->client_id) {
            return new WP_Error(
                'invalid_token',
                'El token no fue emitido para esta aplicación',
                array('status' => 401)
            );
        }

        // Devolver los datos del usuario
        return array(
            'provider_id' => $data['sub'],
            'email' => $data['email'],
            'name' => $data['name'],
            'given_name' => $data['given_name'],
            'family_name' => $data['family_name'],
            'picture' => $data['picture']
        );
    }
} 