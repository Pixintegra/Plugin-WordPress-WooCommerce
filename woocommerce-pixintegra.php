<?php
/**
 * Plugin Name:       Pixintegra Gateway de Pagamentos
 * Plugin URI:        https://pixintegra.com.br
 * Description:       Este plugin integra gateways de pagamento do Pixintegra ao WordPress, garantindo uma gestão segura e eficiente das transações.
 * Version:           0.1.0
 * Author:            Pixintegra Soluções e Intermediação de Negócios LTDA
 * Author URI:        https://pixintegra.com.br
 * License:           GPL-2.0 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pixintegra
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PIXINTEGRA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PIXINTEGRA_PLUGIN_VERSION', '0.1.0');

class PixintegraGateway {

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('admin_notices', [$this, 'check_woocommerce_active']);
        add_action('init', [$this, 'register_pixintegra_endpoint']);
        add_action('template_redirect', [$this, 'pixintegra_endpoint_template_redirect']);
        add_action('admin_init', [$this, 'check_for_updates']);
        $this->includes();
        $this->register_ajax_actions();
        check_pixintegra_table_checkout();
        check_pixintegra_table_order();
        check_pixintegra_table();
    }

    public function activate() {
        $this->register_pixintegra_endpoint();
        flush_rewrite_rules();
    }

    public function check_woocommerce_active() {
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error is-dismissible"><p>Para que o plugin Pixintegra funcione corretamente, é necessário que o WooCommerce esteja ativo.</p></div>';
            deactivate_plugins(plugin_basename(__FILE__));
        }
    }

    public function register_pixintegra_endpoint() {
        add_rewrite_endpoint('pixintegra-pagamento', EP_ROOT);
    }

    public function pixintegra_endpoint_template_redirect() {
        if (get_query_var('pixintegra-pagamento', false) !== false) {
            include PIXINTEGRA_PLUGIN_DIR . 'admin/pagamento_pix.php';
            exit;
        }
    }

    private function includes() {
        require_once PIXINTEGRA_PLUGIN_DIR . 'includes/db_wordpress.php';
        require_once PIXINTEGRA_PLUGIN_DIR . 'includes/auxiliar.php';
        require_once PIXINTEGRA_PLUGIN_DIR . 'includes/funcoes_pixintegra.php';
        require_once PIXINTEGRA_PLUGIN_DIR . 'admin/menu_wordpress.php';
        require_once PIXINTEGRA_PLUGIN_DIR . 'admin/cadastra_getway_wordpress.php';
    }

    private function register_ajax_actions() {
        add_action('wp_ajax_check_pix_payment_status', [$this, 'check_pix_payment_status']);
        add_action('wp_ajax_nopriv_check_pix_payment_status', [$this, 'check_pix_payment_status']);
    }

    public function check_for_updates() {
        $update_url = 'https://pixintegra.com.br/wp/update.json';
        $response = wp_remote_get($update_url);

        if (is_wp_error($response)) {
            error_log('Update check failed: ' . $response->get_error_message());
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($response));

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Update check failed: Invalid JSON response'); 
            return;
        }

        if (version_compare(PIXINTEGRA_PLUGIN_VERSION, $data->new_version, '<')) {
            add_action('admin_notices', function() use ($data) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p>Existe uma nova versão do plugin Pixintegra disponível. <a href="' . $data->package . '">Atualizar agora</a></p>';
                echo '</div>';
            });
        }
    }



    public function check_pix_payment_status() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'check_pix_payment_status')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        if (!isset($_POST['order_id'])) {
            wp_send_json_error(['message' => 'Order ID not provided'], 400);
        }

        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(['message' => 'Order not found'], 404);
        }

        $transaction_id = obter_transacao_id($order_id);
        $verificar_pagamento = consultar_se_foi_pago(obter_api_token(), obter_api_key(), $transaction_id);
        if($verificar_pagamento == 'concluido')
        {
            $order->update_status('completed');
            $redirect_url = $order->get_checkout_order_received_url();
            wp_send_json_success(['status' => "pago", 'redirect_url' => $redirect_url]);
        }
        else if($verificar_pagamento == 'cancelado')
        {
              $order->update_status('cancelled');
              $redirect_url = home_url();
              wp_send_json_success(['status' => "cancelado", 'redirect_url' => $redirect_url]);
        }
    }
}


new PixintegraGateway();
