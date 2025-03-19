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
                        return is_numeric($param) || $param === '0';
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

        // Si el ID es 0, significa que el usuario no está logueado
        // y necesitamos buscar o crear el usuario basado en el token
        if ($user_id === '0' || $user_id === 0) {
            switch ($provider) {
                case 'google':
                    $result = $this->authenticate_or_create_google($token);
                    break;
                case 'apple':
                    $result = $this->authenticate_or_create_apple($token);
                    break;
                default:
                    return new WP_Error(
                        'invalid_auth_method',
                        'Método de autenticación no válido para usuarios no logueados',
                        array('status' => 400)
                    );
            }

            if (is_wp_error($result)) {
                return $result;
            }

            $user_id = $result['user_id'];
            $user = get_user_by('id', $user_id);
        } else {
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
     * Autentica o crea un usuario usando Google
     *
     * @param string $token Token de Google.
     * @return array|WP_Error Array con user_id si es exitoso, WP_Error si no.
     */
    private function authenticate_or_create_google($token) {
        $provider = new Google_Provider();
        $result = $provider->validate_token($token);

        if (is_wp_error($result)) {
            return $result;
        }

        // Buscar usuario por el ID de Google
        $users = get_users(array(
            'meta_key' => '_social_login_google_id',
            'meta_value' => $result['provider_id'],
            'number' => 1
        ));

        if (!empty($users)) {
            return array('user_id' => $users[0]->ID);
        }

        // Si no existe, buscar por email
        $existing_user = get_user_by('email', $result['email']);
        if ($existing_user) {
            // Vincular el ID de Google al usuario existente
            update_user_meta($existing_user->ID, '_social_login_google_id', $result['provider_id']);
            return array('user_id' => $existing_user->ID);
        }

        // Crear nuevo usuario
        $username = sanitize_user($result['email']);
        $user_data = array(
            'user_login' => $username,
            'user_email' => $result['email'],
            'user_pass' => wp_generate_password(),
            'first_name' => $result['given_name'],
            'last_name' => $result['family_name'],
            'display_name' => $result['name'],
            'role' => 'customer'
        );

        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Guardar el ID de Google
        update_user_meta($user_id, '_social_login_google_id', $result['provider_id']);

        return array('user_id' => $user_id);
    }

    /**
     * Autentica o crea un usuario usando Apple
     *
     * @param string $token Token de Apple.
     * @return array|WP_Error Array con user_id si es exitoso, WP_Error si no.
     */
    private function authenticate_or_create_apple($token) {
        $provider = new Apple_Provider();
        $result = $provider->validate_token($token);

        if (is_wp_error($result)) {
            return $result;
        }

        // Buscar usuario por el ID de Apple
        $users = get_users(array(
            'meta_key' => '_social_login_apple_id',
            'meta_value' => $result['provider_id'],
            'number' => 1
        ));

        if (!empty($users)) {
            return array('user_id' => $users[0]->ID);
        }

        // Si no existe, buscar por email
        $existing_user = get_user_by('email', $result['email']);
        if ($existing_user) {
            // Vincular el ID de Apple al usuario existente
            update_user_meta($existing_user->ID, '_social_login_apple_id', $result['provider_id']);
            return array('user_id' => $existing_user->ID);
        }

        // Crear nuevo usuario
        $username = sanitize_user($result['email']);
        $user_data = array(
            'user_login' => $username,
            'user_email' => $result['email'],
            'user_pass' => wp_generate_password(),
            'display_name' => $result['email'], // Apple no proporciona nombre
            'role' => 'customer'
        );

        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Guardar el ID de Apple
        update_user_meta($user_id, '_social_login_apple_id', $result['provider_id']);

        return array('user_id' => $user_id);
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