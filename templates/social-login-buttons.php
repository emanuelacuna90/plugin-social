<?php
/**
 * Template para los botones de inicio de sesión social
 *
 * @package Social_Login
 */

defined('ABSPATH') || exit;

// Obtener el ID del usuario actual o 0 si no está logueado
$user_id = get_current_user_id();
?>

<div class="social-login-buttons">
    <button class="social-login-button google-login" onclick="window.location.href='<?php echo esc_url(rest_url("wc/v3/customers/{$user_id}/auth?provider=google")); ?>'">
        <?php esc_html_e('Iniciar sesión con Google', 'social-login-wc'); ?>
    </button>
    <button class="social-login-button apple-login" onclick="window.location.href='<?php echo esc_url(rest_url("wc/v3/customers/{$user_id}/auth?provider=apple")); ?>'">
        <?php esc_html_e('Iniciar sesión con Apple', 'social-login-wc'); ?>
    </button>
</div> 