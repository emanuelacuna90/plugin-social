<?php
/*
Plugin Name: Social Login para WooCommerce
Plugin URI: https://tusitio.com
Description: Plugin para validación de tokens de Google y Apple ID para WooCommerce
Version: 1.0
Author: Emanuel
Author URI: https://tusitio.com
Text Domain: social-login-wc
*/

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('SOCIAL_LOGIN_VERSION', '1.0.0');
define('SOCIAL_LOGIN_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Asegurarse de que WooCommerce está activo
function social_login_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="error">
                <p><?php _e('Social Login requiere que WooCommerce esté instalado y activado.', 'social-login-wc'); ?></p>
            </div>
            <?php
        });
        return;
    }

    // Cargar las clases del plugin
    require_once SOCIAL_LOGIN_PLUGIN_DIR . 'includes/providers/class-auth-provider.php';
    require_once SOCIAL_LOGIN_PLUGIN_DIR . 'includes/providers/class-google-provider.php';
    require_once SOCIAL_LOGIN_PLUGIN_DIR . 'includes/providers/class-apple-provider.php';
    require_once SOCIAL_LOGIN_PLUGIN_DIR . 'includes/api/class-api-auth.php';
    require_once SOCIAL_LOGIN_PLUGIN_DIR . 'includes/api/class-api-customers.php';

    // Inicializar las clases
    $api_auth = new API_Auth();
    $api_customers = new API_Customers();

    // Registrar endpoints
    add_action('rest_api_init', array($api_auth, 'register_routes'));
}
add_action('plugins_loaded', 'social_login_check_woocommerce');

// Inicializar el plugin
function social_login_init() {
    // Cargar traducciones
    load_plugin_textdomain('social-login-wc', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'social_login_init');
