<?php
/**
 * Define os endpoints da API do plugin.
 */

// Adiciona o endpoint "/pay" para processar pagamentos
add_action( 'rest_api_init', 'abmex_register_pay_endpoint' );
function abmex_register_pay_endpoint() {
    register_rest_route(
        'abmex/v1',
        '/pay',
        array(
            'methods' => 'POST',
            'callback' => 'abmex_process_payment',
            'permission_callback' => '__return_true'
        )
    );
}

function abmex_process_payment( $request ) {
      // Obtém os dados enviados na solicitação POST
    $parameters = $request->get_params();
    $order_id = $parameters['order_id'];
    $public_key = $parameters['public_key'];
    $secret_key = $parameters['secret_key'];
    $amount = $parameters['amount'];
    
    // Processa o pagamento e retorna um token
    $token = abmex_create_token( $public_key, $secret_key, $amount );

    if ( ! empty( $token ) ) {
        // Atualiza o status do pedido para "Processando pagamento"
        $order = wc_get_order( $order_id );
        $order->update_status( 'processing', __( 'Pagamento em processamento pela Abmex.', 'woocommerce' ) );

        // Retorna o token gerado
        return array(
            'token' => $token
        );
    } else {
        // Retorna um erro
        return new WP_Error( 'abmex_error', 'O pagamento não pôde ser processado. Por favor, tente novamente mais tarde.' );
    }
}
