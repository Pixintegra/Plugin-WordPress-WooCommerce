<?php

if (!defined('ABSPATH')) {
    exit; 
}

function consultar_saldos_pixintegra($api_token, $api_key)
{
    $post_data = [
        'api_token' => $api_token,
        'api_key' => $api_key,
    ];
    $url = 'https://pixintegra.com.br/api/consultar_saldo';
    $args = [
        'body' => json_encode($post_data),
        'timeout' => '60',
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ];
    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return false;
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        if ($data && isset($data->resultado) && $data->resultado === 'sucesso') {
            return $data;
        } else {
            return false;
        }
    }
}

function consultar_se_foi_pago($api_key, $api_token, $identificador_cliente)
{
    $post_data = [
        'api_token' => $api_token,
        'api_key' => $api_key,
        'identificador_cliente' => $identificador_cliente,
    ];
    $url = 'https://pixintegra.com.br/api/consultar_pagamento';
    $args = [
        'body' => json_encode($post_data),
        'timeout' => '60',
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ];
    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return false;
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        if ($data && isset($data->resultado) && $data->status_da_venda == 'concluido') {
            return 'concluido';
        } else {
            if( $data->status_da_venda == 'cancelado')
            {
                return 'cancelado';
            }
            else
            {
                return 'pendente';
            }  
        }
    }
}


function gerar_cobranca_pix($api_token, $api_key, $nome_produto, $valor_produto, $nome_cliente, $email_cliente, $cpf_cnpj_cliente)
{
    $post_data = [
        'action' => 'gerar_pix',
        'api_token' => $api_token,
        'api_key' => $api_key,
        'nome_do_produto' => $nome_produto,
        'valor_do_produto' => $valor_produto,
        'nome_do_cliente' => $nome_cliente,
        'email_do_cliente' => $email_cliente,
        'cpf_ou_cnpj_do_cliente' => $cpf_cnpj_cliente,
    ];
    $url = 'https://pixintegra.com.br/api/gerar_pix';
    $args = [
        'body' => json_encode($post_data),
        'timeout' => '60',
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ];
    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return false;
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        return $data;
    }
}

function realizar_saque_pixintegra($api_token, $api_key)
{
    $post_data = [
        'api_token' => $api_token,
        'api_key' => $api_key,
    ];
    $url = 'https://pixintegra.com.br/api/realizar_saque';
    $args = [
        'body' => json_encode($post_data),
        'timeout' => '60',
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ];
    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return 'Erro ao se conectar com a API. Por favor, tente novamente mais tarde.';
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        if ($data && isset($data->resultado) && isset($data->message)) {
            switch ($data->resultado) {
                case 'sucesso':
                    return 'Saque realizado com sucesso.';
                case 'erro':
                    return 'Erro ao realizar o saque: ' . $data->message;
                default:
                    return 'Erro desconhecido ao realizar o saque.';
            }
        } else {
            return 'Resposta inválida da API ao realizar o saque.';
        }
    }
}




