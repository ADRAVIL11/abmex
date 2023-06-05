<?php
/**
 * Plugin Name: Abmex Gateway de Pagamento para WooCommerce 2
 * Plugin URI: https://github.com/TAKYONMUSIC/abmex
 * Description: Um gateway de pagamento personalizado para o WooCommerce que utiliza a API da Abmex.
 * Version: 6.0.2
 * Author: TAKYONMUSIC
 * Author URI: https://github.com/TAKYONMUSIC
 * License: GPL2
 */

// Carrega as funções da API
require_once( plugin_dir_path( __FILE__ ) . 'abmex-api.php' );

// Adiciona o gateway de pagamento ao WooCommerce
add_filter( 'woocommerce_payment_gateways', 'abmex_add_gateway' );
function abmex_add_gateway( $gateways ) {
    $gateways[] = 'WC_Abmex_Gateway';
    return $gateways;
}

// Define a classe do gateway de pagamento
add_action( 'plugins_loaded', 'abmex_gateway_init' );
function abmex_gateway_init() {
    require_once( plugin_dir_path( __FILE__ ) . 'wc-abmex-gateway.php' );
    new WC_Abmex_Gateway();
}