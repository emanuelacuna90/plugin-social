<?php
/**
 * Clase para manejar el endpoint de customers
 *
 * @package Social_Login
 */

class API_Customers {
    /**
     * Registra los filtros para modificar el comportamiento de WooCommerce
     */
    public function __construct() {
        // Filtrar los datos del customer antes de crear/actualizar
        add_filter('woocommerce_rest_pre_insert_customer_object', array($this, 'prepare_customer_data'), 10, 3);
        
        // Filtrar la respuesta del customer
        add_filter('woocommerce_rest_prepare_customer', array($this, 'prepare_customer_response'), 10, 3);
    }

    /**
     * Prepara los datos del customer antes de crear/actualizar
     *
     * @param WC_Customer      $customer Objeto del customer.
     * @param WP_REST_Request $request  Objeto de la petición.
     * @param bool            $creating Si se está creando un nuevo customer.
     * @return WC_Customer
     */
    public function prepare_customer_data($customer, $request, $creating) {
        // Obtener los datos de la petición
        $params = $request->get_params();

        // Si se proporciona un provider_id, guardarlo como meta
        if (!empty($params['provider']) && !empty($params['provider_id'])) {
            $provider = sanitize_text_field($params['provider']);
            $provider_id = sanitize_text_field($params['provider_id']);
            
            // Guardar el ID del proveedor como meta
            update_user_meta($customer->get_id(), "_social_login_{$provider}_id", $provider_id);
        }

        return $customer;
    }

    /**
     * Modifica la respuesta del customer
     *
     * @param WP_REST_Response $response Objeto de respuesta.
     * @param WC_Customer      $customer Objeto del customer.
     * @param WP_REST_Request  $request  Objeto de la petición.
     * @return WP_REST_Response
     */
    public function prepare_customer_response($response, $customer, $request) {
        $data = $response->get_data();

        // Agregar los IDs de proveedores sociales
        $data['social_providers'] = array(
            'google' => get_user_meta($customer->get_id(), '_social_login_google_id', true),
            'apple' => get_user_meta($customer->get_id(), '_social_login_apple_id', true)
        );

        $response->set_data($data);
        return $response;
    }
} 