<?php

/**
 * Classe que implementa as funções de API da Abmex.
 */
class Abmex_API {

    private $public_key;
    private $secret_key;

    public function __construct( $public_key, $secret_key ) {
        $this->public_key = $public_key;
        $this->secret_key = $secret_key;
    }

    /**
     * Obtém um token de acesso da API da Abmex.
     */
    public function get_access_token() {

        // Codifique as credenciais adequadamente para que possam ser incluídas na solicitação POST
        $encoded_credentials = base64_encode( $this->public_key . ':' . $this->secret_key );
        
        // Construa o corpo da solicitação POST
        $body = array(
            'grant_type' => 'client_credentials'
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
        
        if ( ! isset( $token_data['access_token'] ) ) {
            throw new Exception( 'Nenhum token de acesso retornado pela API ABMEX Pay.' );
        }
        
        return $token_data['access_token'];
    }

    /**
     * Cria um pagamento usando a API da Abmex.
     */
    public function create_payment( $amount, $currency, $description ) {

        // Obtenha um token de acesso
        $access_token = $this->get_access_token();
        
        // Use o token de acesso para enviar a solicitação POST real para o endpoint /payments
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token
        );
        
        $body = array(
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            // etc ...
        );
        
        // Envie a solicitação POST real
        $response = wp_remote_post( 'https://api.abmexpay.com/v1/payments', array(
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
        
        return $payment_result;
    }

}