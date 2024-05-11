<?php
if (!defined('ABSPATH')) {
    exit;
}


function obter_modo_checkout() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_checkout';
    $modo_checkout = $wpdb->get_var("SELECT mode FROM $table_name LIMIT 1");
    return $modo_checkout;
}


function inserir_dados_na_tabela_checkout($mode) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_checkout';
    $existing_record = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
    if ($existing_record) {
        $wpdb->update(
            $table_name,
            array('mode' => sanitize_text_field($mode)),
            array('id' => $existing_record->id),
            array('%s'),
            array('%d')
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'mode' => sanitize_text_field($mode),
            ),
            array('%s')
        );
    }
}

function obter_qrcode_url($order_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_order';
    $query = $wpdb->prepare("SELECT qrcode_url FROM $table_name WHERE order_id = %d", intval($order_id));
    $qrcode_url = $wpdb->get_var($query);
    return $qrcode_url;
}

function inserir_dados_na_tabela_order($order_id, $product_name, $price, $transaction_id, $qrcode_url) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_order';
    $wpdb->insert(
        $table_name,
        array(
            'order_id' => intval($order_id),
            'product_name' => sanitize_text_field($product_name),
            'price' => floatval($price),
            'transaction_id' => sanitize_text_field($transaction_id),
            'qrcode_url' => esc_url_raw($qrcode_url),
        ),
        array('%d', '%s', '%f', '%s', '%s')
    );
}
function check_pixintegra_table_checkout() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_checkout';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        mode varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


function check_pixintegra_table_order() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_order';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        order_id mediumint(9) NOT NULL,
        product_name varchar(255) NOT NULL,
        price decimal(10,2) NOT NULL,
        transaction_id varchar(255) NOT NULL,
        qrcode_url varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function check_pixintegra_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_auth';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        api_token varchar(255) NOT NULL,
        api_key varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function atualizar_transacao_id($id, $transacao_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_orders';
    $wpdb->update(
        $table_name,
        array('transaction_id' => sanitize_text_field($transacao_id)),
        array('id' => intval($id)),
        array('%s'),
        array('%d')
    );
}

function obter_api_token() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_auth';
    $api_token = $wpdb->get_var("SELECT api_token FROM $table_name LIMIT 1");
    return $api_token;
}


function obter_api_key() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_auth';
    $api_key = $wpdb->get_var("SELECT api_key FROM $table_name LIMIT 1");
    return $api_key;
}

function obter_nome_do_produto($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'woocommerce_order_items';
    $query = $wpdb->prepare("SELECT order_item_name FROM $table_name WHERE order_id = %d", intval($id));
    $nome_produto = $wpdb->get_var($query);
    return $nome_produto;
}


function obter_transacao_id($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pixintegra_order';
    $query = $wpdb->prepare("SELECT transaction_id FROM $table_name WHERE order_id = %d", intval($id));
    $transacao_id = $wpdb->get_var($query);
    return $transacao_id;
}

