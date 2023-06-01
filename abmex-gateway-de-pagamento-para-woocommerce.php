<?php
/**
 * Plugin Name: ABMEX Payment Gateway WooCommerce
 * Plugin URI: 
 * Description: gateway de pagamento ABMEX WooCommerce.
 * Version: 1.0.0
 * Author: TAKYON
 * Author URI: 
 */
 
 // Adicionar o gateway de pagamento
 add_filter('woocommerce_payment_gateways', 'abmex_add_gateway');
 
 function abmex_add_gateway($gateways) {
     $gateways[] = 'WC_ABMEX_Gateway';
     return $gateways;
 }
 
 // Define a classe do gateway de pagamento
 add_action('plugins_loaded', 'abmex_init_gateway_class');
 
 function abmex_init_gateway_class() {
 
     class WC_ABMEX_Gateway extends WC_Payment_Gateway {
 
         public function __construct() {
 
             // Define as propriedades do gateway
             $this->id = 'abmex_gateway';
             $this->icon = '';
             $this->has_fields = false;
             $this->method_title = 'ABMEX Gateway';
             $this->method_description = 'Integração com o gateway de pagamento ABMEX para WooCommerce.';
 
             // Define os campos de configuração
             $this->init_form_fields();
 
             // Define as opções de configuração
             $this->init_settings();
 
             // Define as variáveis de instancia das opções de configuração
             foreach ($this->settings as $setting_key => $value) {
                 $this->$setting_key = $value;
             }
 
             // Define as ações necessárias
             add_action('woocommerce_receipt_abmex_gateway', array($this, 'receipt_page'));
             add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
         }
 
         // Define os campos de configuração adicionais
         public function init_form_fields() {
             $this->form_fields = array(
                 'enabled' => array(
                     'title'   => 'Ativar/Desativar',
                     'type'    => 'checkbox',
                     'label'   => 'Ativar o gateway de pagamento ABMEX',
                     'default' => 'yes'
                 ),
                 'title' => array(
                     'title'       => 'Título',
                     'type'        => 'text',
                     'description' => 'Título a ser exibido para o método de pagamento durante o checkout.',
                     'default'     => 'ABMEX Gateway'
                 ),
                 'description' => array(
                     'title'       => 'Descrição',
                     'type'        => 'textarea',
                     'description' => 'Descrição do método de pagamento que será exibida durante o checkout.',
                     'default'     => 'Pague com o gateway de pagamento ABMEX.'
                 )
             );
         }
 
         // Processa o pagamento e exibe a página de confirmação
         public function process_payment($order_id) {
             $order = new WC_Order($order_id);
             $order->update_status('processing', 'Aguardando pagamento através do gateway de pagamento ABMEX.');
             $order->reduce_order_stock();
             WC()->cart->empty_cart();
             return array(
                 'result'   => 'success',
                 'redirect' => $this->get_return_url($order)
             );
         }
 
         // Exibe a página de confirmação de pagamento
         public function receipt_page($order) {
             echo '<p>O pagamento foi encaminhado para o gateway ABMEX.</p>';
         }
     }
 }
 
?>