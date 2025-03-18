<?php
if (!defined('ABSPATH')) {
    exit;
}

class Social_Login_User_Meta {
    public function __construct() {
        add_action('init', array($this, 'register_user_meta'));
    }

    public function register_user_meta() {
        register_meta('user', 'google_id', array(
            'type' => 'string',
            'description' => 'ID de usuario de Google',
            'single' => true,
            'show_in_rest' => true,
        ));

        register_meta('user', 'apple_id', array(
            'type' => 'string',
            'description' => 'ID de usuario de Apple',
            'single' => true,
            'show_in_rest' => true,
        ));

        // Campos de dirección
        $address_fields = array(
            'street' => 'Calle',
            'number' => 'Número',
            'apartment' => 'Departamento',
            'city' => 'Ciudad',
            'state' => 'Estado/Provincia',
            'postal_code' => 'Código Postal',
            'country' => 'País'
        );

        foreach ($address_fields as $field => $description) {
            register_meta('user', $field, array(
                'type' => 'string',
                'description' => $description,
                'single' => true,
                'show_in_rest' => true,
            ));
        }
    }
} 