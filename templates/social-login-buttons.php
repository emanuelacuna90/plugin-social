<?php
/**
 * Template para los botones de inicio de sesión social
 *
 * @package Social_Login
 */

defined('ABSPATH') || exit;
?>

<div class="social-login-buttons">
    <button class="social-login-button google-login" onclick="window.location.href='<?php echo esc_url(rest_url('social-login/v1/google/auth')); ?>'">
        <?php esc_html_e('Iniciar sesión con Google', 'social-login-wc'); ?>
    </button>
    <button class="social-login-button apple-login" onclick="window.location.href='<?php echo esc_url(rest_url('social-login/v1/apple/auth')); ?>'">
        <?php esc_html_e('Iniciar sesión con Apple', 'social-login-wc'); ?>
    </button>
</div> 