<?php

require_once('abmex/abmex_config.php');
require_once('abmex/abmex_functions.php');

add_filter('woocommerce_payment_gateways','abmex_add_gateway_class');
function abmex_add_gateway_class($gateways)
{
    $gateways[] = 'WC_ABMEX_Gateway'; 
    return $gateways;
}
 
add_action('plugins_loaded', 'abmex_init_gateway_class');
function abmex_init_gateway_class()
{
    class WC_ABMEX_Gateway extends WC_Payment_Gateway
    {
 
        public function __construct()
        {
            $this->id = 'abmex_gateway';
            $this->icon = '';
            $this->has_fields = false;
            $this->method_title = 'ABMEX Gateway de Pagamento';
            $this->method_description = 'Aceite pagamentos com a ABMEX.';
         
            $this->init_form_fields();
            $this->init_settings();
         
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
         
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_api_abmex_gateway', array($this, 'handle_callback'));
        }
     
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Ativar/Desativar', 'abmex_gateway'),
                    'type' => 'checkbox',
                    'label' => __('Ativar ABMEX Gateway', 'abmex_gateway'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Título', 'abmex_gateway'),
                    'type' => 'text',
                    'description' => __('Controle o título que o usuário vê durante o checkout.', 'abmex_gateway'),
                    'default' => __('Pagamento via ABMEX Gateway', 'abmex_gateway'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Descrição', 'abmex_gateway'),
                    'type' => 'textarea',
                    'description' => __('Controle a descrição que o usuário vê durante o checkout.', 'abmex_gateway'),
                    'default' => __('Pague com segurança usando seu cartão de crédito via ABMEX Gateway.', 'abmex_gateway'),
                ),
            );
        }
     
        public function process_payment($order_id)
        {
            global $woocommerce;
 
            $order = new WC_Order($order_id);
 
            $payment_result = abmex_do_payment($order);
 
            if ($payment_result) {
                $order->payment_complete();
                $woocommerce->cart->empty_cart();
 
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                wc_add_notice(__('O pagamento falhou. Por favor, tente novamente.', 'abmex_gateway'), 'error');
                 
                return;
            }
        }
     
        public function handle_callback()
        {
            // aqui vai a lógica para processar um callback do pagamento
        }
    }
}
?>