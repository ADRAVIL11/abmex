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
    
            $order = wc_get_order( $order_id );
            
            // Obtenha suas credenciais de autenticação API a partir de algum lugar
            $consumer_key = 'pk_live_VG6U9a3FLSLeyRKE6VaFIIF9cl37Ag';
            $consumer_secret = 'sk_live_ahVs6my0MCdnsAEMxAyjkiSE0YXfOj';
            
            // Codifique as credenciais adequadamente para que possam ser incluídas na solicitação POST
            $encoded_credentials = base64_encode( $consumer_key . ':' . $consumer_secret );
            
            // Construa o corpo da solicitação POST
            $body = array(
                'amount' => $order->get_total(),
                'currency' => get_woocommerce_currency(),
                // etc ...
            );
            
            // Cabeçalho da solicitação com as informações de autenticação
            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $encoded_credentials
            );
            
            // Envie a solicitação POST para obter o token de acesso
            $response = wp_remote_post( 'https://api.abmexpay.com/v1/token', array(
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode( $body )
            ) );
            
            if ( is_wp_error( $response ) ) {
                throw new Exception( 'Erro ao enviar uma solicitação para obter o token de acesso: ' . $response->get_error_message() );
            }
            
            // Decodifique a resposta JSON para obter o token de acesso
            $token_data = json_decode( wp_remote_retrieve_body( $response ), true );
            
            if ( ! isset( $token_data['token'] ) ) {
                throw new Exception( 'Nenhum token de acesso retornado pela API ABMEX Pay.' );
            }
            
            // Use o token de acesso para enviar a solicitação POST real para o endpoint /orders
            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token_data['token']
            );
            
            $body = array(
                // Inclua outros detalhes do pedido aqui, como SKU, nome do produto, etc ...
                'amount' => $order->get_total(),
                'currency' => get_woocommerce_currency(),
                // etc ...
            );
            
            // Envie a solicitação POST real
            $response = wp_remote_post( 'https://api.abmexpay.com/v1/orders', array(
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode( $body )
            ) );
            
            if ( is_wp_error( $response ) ) {
                throw new Exception( 'Erro ao enviar uma solicitação de pagamento: ' . $response->get_error_message() );
            }
            
            // Decodifique a resposta JSON para confirmar que o pagamento foi processado com êxito
            $payment_result = json_decode( wp_remote_retrieve_body( $response ), true );
            
            if ( ! isset( $payment_result['success'] ) || ! $payment_result['success'] ) {
                throw new Exception( 'O pagamento falhou. Detalhes: ' . print_r( $payment_result, true ) );
            }
            
            // O pagamento foi bem-sucedido, então defina o status do pedido como pago
            $order->payment_complete();
            
            // Limpe o carrinho de compras
            WC()->cart->empty_cart();
            
            // Redirecione para a página de confirmação do pedido
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }        
        
       
    }
}