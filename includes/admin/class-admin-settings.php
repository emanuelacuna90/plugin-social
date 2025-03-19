<?php
/**
 * Clase para manejar la configuración del plugin
 *
 * @package Social_Login
 */

class Social_Login_Admin_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Agrega la página de configuración al menú
     */
    public function add_settings_page() {
        add_options_page(
            'Social Login',
            'Social Login',
            'manage_options',
            'social-login',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Registra las opciones de configuración
     */
    public function register_settings() {
        register_setting('social_login_settings', 'social_login_google_client_id');
        register_setting('social_login_settings', 'social_login_google_client_secret');
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Social Login Settings</h1>
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