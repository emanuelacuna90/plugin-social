<?php
/**
 * Clase base para proveedores de autenticación
 *
 * @package Social_Login
 */

abstract class Auth_Provider {
    /**
     * ID único del proveedor
     *
     * @var string
     */
    protected $provider_id;

    /**
     * Constructor
     *
     * @param string $provider_id ID del proveedor.
     */
    public function __construct($provider_id) {
        $this->provider_id = $provider_id;
    }

    /**
     * Valida el token del proveedor
     *
     * @param string $token Token a validar.
     * @return array|WP_Error Array con datos del usuario si es válido, WP_Error si no.
     */
    abstract public function validate_token($token);

    /**
     * Obtiene el ID del usuario de WordPress basado en el ID del proveedor
     *
     * @param string $provider_user_id ID del usuario en el proveedor.
     * @return int|null ID del usuario de WordPress o null si no existe.
     */
    protected function get_wordpress_user_id($provider_user_id) {
        global $wpdb;
        
        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} 
                WHERE meta_key = %s AND meta_value = %s",
                "_social_login_{$this->provider_id}_id",
                $provider_user_id
            )
        );

        return $user_id ? (int) $user_id : null;
    }

    /**
     * Vincula un ID de proveedor con un usuario de WordPress
     *
     * @param int    $user_id ID del usuario de WordPress.
     * @param string $provider_user_id ID del usuario en el proveedor.
     * @return bool True si se vinculó correctamente, false si no.
     */
    protected function link_provider_id($user_id, $provider_user_id) {
        return update_user_meta(
            $user_id,
            "_social_login_{$this->provider_id}_id",
            $provider_user_id
        );
    }
} 