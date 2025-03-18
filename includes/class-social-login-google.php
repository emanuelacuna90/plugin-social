<?php
if (!defined('ABSPATH')) {
    exit;
}

class Social_Login_Google {
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    public function __construct() {
        $this->client_id = get_option('social_login_google_client_id');
        $this->client_secret = get_option('social_login_google_client_secret');
        
        // Usar HTTPS para el dominio de desarrollo
        $this->redirect_uri = home_url('wp-json/social-login/v1/google/callback');
        
        // Forzar HTTPS si no está ya configurado
        if (strpos($this->redirect_uri, 'https://') === false) {
            $this->redirect_uri = str_replace('http://', 'https://', $this->redirect_uri);
        }

        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_menu_page'));
    }

    public function register_routes() {
        register_rest_route('social-login/v1', '/google/auth', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_auth_request'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('social-login/v1', '/google/callback', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_callback'),
            'permission_callback' => '__return_true'
        ));
    }

    public function get_auth_url() {
        $base_url = 'https://accounts.google.com/o/oauth2/v2/auth';
        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'offline',
            'state' => wp_create_nonce('google_auth')
        );

        return $base_url . '?' . http_build_query($params);
    }

    public function handle_auth_request() {
        wp_redirect($this->get_auth_url());
        exit;
    }

    public function handle_callback($request) {
        $code = $request->get_param('code');
        $state = $request->get_param('state');

        if (!wp_verify_nonce($state, 'google_auth')) {
            return new WP_Error('invalid_state', 'Estado inválido', array('status' => 403));
        }

        // Obtener el token de acceso
        $token_response = $this->get_access_token($code);
        if (is_wp_error($token_response)) {
            return $token_response;
        }

        // Obtener información del usuario
        $user_info = $this->get_user_info($token_response['access_token']);
        if (is_wp_error($user_info)) {
            return $user_info;
        }

        // Crear o actualizar usuario
        $user_id = $this->create_or_update_user($user_info);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Iniciar sesión
        wp_set_auth_cookie($user_id);
        wp_redirect(home_url());
        exit;
    }

    private function get_access_token($code) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code' => $code,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => $this->redirect_uri,
                'grant_type' => 'authorization_code'
            )
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['access_token'])) {
            return new WP_Error('invalid_token', 'Token inválido');
        }

        return $body;
    }

    private function get_user_info($access_token) {
        $response = wp_remote_get('https://www.googleapis.com/oauth2/v2/userinfo', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token
            )
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private function create_or_update_user($user_info) {
        $email = $user_info['email'];
        $user = get_user_by('email', $email);

        if ($user) {
            return $user->ID;
        }

        $username = $this->generate_unique_username($user_info['given_name']);
        $user_id = wp_create_user($username, wp_generate_password(), $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        update_user_meta($user_id, 'first_name', $user_info['given_name']);
        update_user_meta($user_id, 'last_name', $user_info['family_name']);
        update_user_meta($user_id, 'google_id', $user_info['id']);

        return $user_id;
    }

    private function generate_unique_username($base) {
        $username = sanitize_user($base, true);
        $i = 1;
        
        while (username_exists($username)) {
            $username = $base . $i;
            $i++;
        }

        return $username;
    }

    public function register_settings() {
        register_setting('social_login_settings', 'social_login_google_client_id');
        register_setting('social_login_settings', 'social_login_google_client_secret');
    }

    public function add_menu_page() {
        add_submenu_page(
            'woocommerce',
            'Social Login Settings',
            'Social Login',
            'manage_options',
            'social-login-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h2>Social Login Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('social_login_settings');
                do_settings_sections('social_login_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Google Client ID</th>
                        <td>
                            <input type="text" name="social_login_google_client_id" 
                                   value="<?php echo esc_attr(get_option('social_login_google_client_id')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Google Client Secret</th>
                        <td>
                            <input type="password" name="social_login_google_client_secret" 
                                   value="<?php echo esc_attr(get_option('social_login_google_client_secret')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
} 