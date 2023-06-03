<?php
/**
 * Plugin Name: Abmex Gateway de Pagamento para WooCommerce 2
 * Plugin URI: https://github.com/TAKYONMUSIC/abmex2
 * Description: Um gateway de pagamento personalizado para o WooCommerce que utiliza a API da Abmex.
 * Version: 6.0.1
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
    class WC_Abmex_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'abmex';
            $this->icon = '';
            $this->method_title = 'Abmex';
            $this->method_description = 'Pague com a Abmex';
            $this->supports = array(
                'products',
                'refunds'
            );
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->public_key = $this->get_option( 'public_key' );
            $this->secret_key = $this->get_option( 'secret_key' );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );            
        }
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Ativar/Desativar',
                    'type'    => 'checkbox',
                    'label'   => 'Ativar pagamento por Abmex',
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => 'Título',
                    'type'        => 'text',
                    'description' => 'Título que o usuário vê durante o checkout.',
                    'default'     => 'Abmex'
                ),
                'description' => array(
                    'title'       => 'Descrição',
                    'type'        => 'textarea',
                    'description' => 'Descrição que o usuário vê durante o checkout.',
                    'default'     => 'Pague com a Abmex.'
                ),
                'public_key' => array(
                    'title'       => 'Chave Pública (Public Key)',
                    'type'        => 'text',
                    'description' => 'Chave pública fornecida pela Abmex.',
                    'default'     => ''
                ),
                'secret_key' => array(
                    'title'       => 'Chave Secreta (Secret Key)',
                    'type'        => 'text',
                    'description' => 'Chave secreta fornecida pela Abmex.',
                    'default'     => ''
                )
            );
        }
        public function process_payment( $order_id ) {

            // recupera o pedido atual
            $order = wc_get_order( $order_id );
        
            // define suas credenciais de API
            $client_id = 'pk_live_VG6U9a3FLSLeyRKE6VaFIIF9cl37Ag';
            $client_secret = 'sk_live_ahVs6my0MCdnsAEMxAyjkiSE0YXfOj';
        
            // envie uma solicitação POST para o endpoint de autenticação para obter o access token
            $auth_endpoint = 'https://api.abmexpay.com/token';
            $response = wp_remote_post( $auth_endpoint, array(
                'body' => array(
                    'grant_type' => 'client_credentials',
                    'client_id' => $client_id,
                    'client_secret' => $client_secret
                )
            ) );
        
            // analise a resposta JSON para recuperar o access token
            $access_token = json_decode( wp_remote_retrieve_body( $response ), true )['access_token'];
        
            // use o access token para fazer solicitações autenticadas na API do ABMEX Pay
            $transaction_status_endpoint = 'https://api.abmexpay.com/transactions/' . $order_id;
            $transaction_response = wp_remote_get( $transaction_status_endpoint, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token
                )
            ) );
        
            // analisar a resposta JSON para obter o status da transação
            $transaction_status = json_decode( wp_remote_retrieve_body( $transaction_response ), true )['status'];
        
            // verificar o status da transação e atualizar o status do pedido em conformidade
            if ( $transaction_status == 'capturado' ) {
                // atualiza o status do pedido para "Concluído"
                $order->update_status( 'completed' );
            } elseif ( $transaction_status == 'autorizado' ) {
                // atualiza o status do pedido para "Processando Pagamento"
                $order->update_status( 'processing_payment' );
            } else {
                // atualiza o status do pedido para "Falha no Pagamento"
                $order->update_status( 'failed' );
            }
        }
        
       
    }
}