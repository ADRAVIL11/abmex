<?php
/**
 * Funções para processar pagamentos utilizando a API da Abmex.
 */

function abmex_create_token( $public_key, $secret_key, $amount ) {
    // Monta os dados para enviar à API
    $data = array(
        'amount' => $amount,
        'currency' => 'BRL'
    );

    // Converte os dados em JSON
    $json_data = json_encode( $data );

    // Cria um hash HMAC SHA256 para autenticação
    $timestamp = time();
    $nonce = uniqid();
    $http_method = 'POST';
    $request_path = '/v1/payments/tokens';
    $signature = base64_encode(hash_hmac('sha256', $http_method."\n".$request_path."\n".$timestamp."\n".$nonce."\n".$json_data."\n", $secret_key, true));

    // Envia uma solicitação POST para a API para criar um novo token de pagamento
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n" .
                         "Authorization: Bearer ".$public_key.":".$signature.":".$nonce.":".$timestamp."\r\n",
            'method'  => 'POST',
            'content' => $json_data
        )
    );
    $context  = stream_context_create( $options );
    $response = file_get_contents( 'https://api.abmex.com.br/v1/payments/tokens', false, $context );
    $result = json_decode( $response, true );

    if ( isset( $result['token'] ) ) {
        return $result['token'];
    } else {
        return '';
    }
}
