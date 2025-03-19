<?php
/**
 * Clase para manejar el endpoint de autenticación
 *
 * @package Social_Login
 */

class API_Auth {
    /**
     * Registra las rutas de la API
     */
    public function register_routes() {
        register_rest_route('wc/v3', '/customers/(?P<id>[\d]+)/auth', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'authenticate'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'provider' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('password', 'google', 'apple')
                ),
                'token' => array(
                    'required' => true,
                    'type' => 'string'
                )
            )
        ));
    }

    /**
     * Maneja la autenticación
     *
     * @param WP_REST_Request $request Objeto de la petición.
     * @return WP_REST_Response|WP_Error Respuesta de la API.
     */
    public function authenticate($request) {
        $user_id = $request->get_param('id');
        $provider = $request->get_param('provider');
        $token = $request->get_param('token');

        // Verificar que el usuario existe
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                'Usuario no encontrado',
                array('status' => 404)
            );
        }

        // Autenticar según el proveedor
        switch ($provider) {
            case 'password':
                $result = $this->authenticate_password($user, $token);
                break;
            case 'google':
                $result = $this->authenticate_google($user, $token);
                break;
            case 'apple':
                $result = $this->authenticate_apple($user, $token);
                break;
            default:
                return new WP_Error(
                    'invalid_provider',
                    'Proveedor de autenticación inválido',
                    array('status' => 400)
                );
        }

        if (is_wp_error($result)) {
            return $result;
        }

        // Generar JWT
        $jwt = $this->generate_jwt($user);
        if (is_wp_error($jwt)) {
            return $jwt;
        }

        return rest_ensure_response(array(
            'token' => $jwt,
            'user_id' => $user->ID
        ));
    }

    /**
     * Autentica usando contraseña
     *
     * @param WP_User $user Usuario a autenticar.
     * @param string  $password Contraseña a verificar.
     * @return true|WP_Error True si la autenticación es exitosa, WP_Error si no.
     */
    private function authenticate_password($user, $password) {
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            return new WP_Error(
                'invalid_password',
                'Contraseña incorrecta',
                array('status' => 401)
            );
        }
        return true;
    }

    /**
     * Autentica usando Google
     *
     * @param WP_User $user Usuario a autenticar.
     * @param string  $token Token de Google.
     * @return true|WP_Error True si la autenticación es exitosa, WP_Error si no.
     */
    private function authenticate_google($user, $token) {
        $provider = new Google_Provider();
        $result = $provider->validate_token($token);

        if (is_wp_error($result)) {
            return $result;
        }

        // Verificar que el ID de Google coincide con el usuario
        $stored_id = get_user_meta($user->ID, '_social_login_google_id', true);
        if (empty($stored_id) || $stored_id !== $result['provider_id']) {
            return new WP_Error(
                'invalid_user',
                'El token no corresponde a este usuario',
                array('status' => 401)
            );
        }

        return true;
    }

    /**
     * Autentica usando Apple
     *
     * @param WP_User $user Usuario a autenticar.
     * @param string  $token Token de Apple.
     * @return true|WP_Error True si la autenticación es exitosa, WP_Error si no.
     */
    private function authenticate_apple($user, $token) {
        $provider = new Apple_Provider();
        $result = $provider->validate_token($token);

        if (is_wp_error($result)) {
            return $result;
        }

        // Verificar que el ID de Apple coincide con el usuario
        $stored_id = get_user_meta($user->ID, '_social_login_apple_id', true);
        if (empty($stored_id) || $stored_id !== $result['provider_id']) {
            return new WP_Error(
                'invalid_user',
                'El token no corresponde a este usuario',
                array('status' => 401)
            );
        }

        return true;
    }

    /**
     * Genera un JWT para el usuario
     *
     * @param WP_User $user Usuario para el que generar el token.
     * @return string|WP_Error Token JWT si es exitoso, WP_Error si no.
     */
    private function generate_jwt($user) {
        // Aquí integraremos con SimpleJWTLogin
        // Por ahora, devolvemos un error
        return new WP_Error(
            'not_implemented',
            'Generación de JWT no implementada',
            array('status' => 501)
        );
    }
} 