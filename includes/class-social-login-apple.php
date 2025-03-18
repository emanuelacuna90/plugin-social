<?php
if (!defined('ABSPATH')) {
    exit;
}

class Social_Login_Apple {
    private $client_id;
    private $team_id;
    private $key_id;
    private $private_key;
    private $redirect_uri;

    public function __construct() {
        $this->client_id = get_option('social_login_apple_client_id');
        $this->team_id = get_option('social_login_apple_team_id');
        $this->key_id = get_option('social_login_apple_key_id');
        $this->private_key = get_option('social_login_apple_private_key');
        $this->redirect_uri = home_url('wp-json/social-login/v1/apple/callback');

        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_routes() {
        register_rest_route('social-login/v1', '/apple/auth', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_auth_request'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('social-login/v1', '/apple/callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_callback'),
            'permission_callback' => '__return_true'
        ));
    }

    public function register_settings() {
        register_setting('social_login_settings', 'social_login_apple_client_id');
        register_setting('social_login_settings', 'social_login_apple_team_id');
        register_setting('social_login_settings', 'social_login_apple_key_id');
        register_setting('social_login_settings', 'social_login_apple_private_key');
    }
} 