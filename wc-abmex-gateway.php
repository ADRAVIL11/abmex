<?php

/**
 * Classe que implementa o gateway de pagamento da Abmex.
 */
class WC_Abmex_Gateway extends WC_Payment_Gateway {

    private $abmex_api;

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
        $public_key = $this->get_option( 'public_key' );
        $secret_key = $this->get_option( 'secret_key' );
        $this->abmex_api = new Abmex_API( $public_key, $secret_key );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );            
    }

    /**
     * Exibe os campos de configuração do plugin no painel de administração do WooCommerce.
     */
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

    /**
     * Processa o pagamento quando o botão "Finalizar compra" é clicado.
     */
    public function process_payment( $order_id ) {

        $order = wc_get_order( $order_id );

        try {
            // Crie um pagamento usando a API da Abmex
            $payment_result = $this->abmex_api->create_payment(
                $order->get_total(),
                get_woocommerce_currency(),
                // etc ...
            );

            // O pagamento foi bem-sucedido, então defina o status do pedido como pago
            $order->payment_complete();

            // Limpe o carrinho de compras
            WC()->cart->empty_cart();

            // Redirecione para a página de confirmação do pedido
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        } catch ( Exception $ex ) {
            wc_add_notice( $ex->getMessage(), 'error' );
        }

    }        

}