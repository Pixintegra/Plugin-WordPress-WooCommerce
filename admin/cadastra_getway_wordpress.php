<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'add_pixintegra_gateway');


function add_pixintegra_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Pixintegra_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'pixintegra';
            $this->icon = plugin_dir_url(__FILE__) . '../assets/img/plogo.ico';
            $this->method_title = 'PIX INTEGRA';
            $this->method_description = 'Receba com PIX de forma totalmente segura';
            $this->title = 'PIX INTEGRA';
            $this->description = 'Receba com PIX de forma totalmente segura';
            $this->has_fields = true;
            $this->init_form_fields();
            $this->init_settings();
            $this->enabled = $this->get_option('enabled');
            $this->supports = ['products', 'subscriptions'];
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);;
        }

        public function payment_fields() {
            if (!isset($this->fields_added)) {
                echo '<h6>Pague com PIX de forma totalmente segura</h6>';
                woocommerce_form_field(
                    'cpf',
                    [
                        'type' => 'text',
                        'class' => ['cpf-field-class form-row-wide'],
                        'label' => 'CPF',
                        'style' => 'padding: 5px 0 5px 5px;',
                        'placeholder' => 'Informe seu CPF',
                        'required' => true,
                    ],
                    WC()->checkout->get_value('cpf')
                );
                $this->fields_added = true;
            }
        }

        public function init_form_fields() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'pixintegra_auth';
            $api_token = $wpdb->get_var($wpdb->prepare("SELECT api_token FROM $table_name LIMIT 1"));
            $api_key = $wpdb->get_var($wpdb->prepare("SELECT api_key FROM $table_name LIMIT 1"));

            $this->form_fields = [
                'enabled' => [
                    'title' => __('Habilitar/Desabilitar', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Habilitar Pixintegra', 'woocommerce'),
                    'default' => 'no',
                ],
                'api_token' => [
                    'title' => __('API Token', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Insira o API Token disponivel no site pixintega.com.br > dados da conta > API.', 'woocommerce'),
                    'default' => $api_token ? $api_token : '',
                ],
                'api_key' => [
                    'title' => __('API Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Insira o API Key disponivel no site pixintega.com.br > dados da conta > API.', 'woocommerce'),
                    'default' => $api_key ? $api_key : '',
                ],
                'Rota_do_checkout' => [
                    'title' => __('Tipo de checkout', 'woocommerce'),
                    'type' => 'select',
                    'description' => __('Se estiver utilizando os plugins Wordfence ou Hide My WP, utilize a opção Arquivo.', 'woocommerce'),
                    'options' => [
                        'rota' => 'Redirecionar (Endpoint)',
                        'arquivo' => 'Arquivo',
                    ],
                    'default' => 'rota',
                ],

            ];
        }

        public function admin_options() {
            ?>
            <h3><?php echo esc_html($this->method_title); ?></h3>
            <p><?php echo esc_html__('Insira o API TOKEN e API KEY.', 'woocommerce'); ?></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table>
            <?php
        }

        public function process_admin_options() {
            parent::process_admin_options();
            global $wpdb;
            $table_name = $wpdb->prefix . 'pixintegra_auth';
            $api_token = sanitize_text_field($this->get_option('api_token'));
            $api_key = sanitize_text_field($this->get_option('api_key'));

            $existing_record = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

            if ($existing_record) {
                $wpdb->update(
                    $table_name,
                    ['api_token' => $api_token, 'api_key' => $api_key],
                    ['id' => $existing_record->id],
                    ['%s', '%s'],
                    ['%d']
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    ['api_token' => $api_token, 'api_key' => $api_key],
                    ['%s', '%s']
                );
            }


            $table_name_checkout = $wpdb->prefix . 'pixintegra_checkout';
            $mode = sanitize_text_field($this->get_option('Rota_do_checkout'));
            $existing_record_checkout = $wpdb->get_row("SELECT * FROM $table_name_checkout LIMIT 1");

            if ($existing_record_checkout) {
                $wpdb->update(
                    $table_name_checkout,
                    ['mode' => $mode],
                    ['id' => $existing_record_checkout->id],
                    ['%s'],
                    ['%d']
                );
            } else {
                $wpdb->insert(
                    $table_name_checkout,
                    ['mode' => $mode],
                    ['%s']
                );
            }

            if( $mode == 'arquivo' ){
                $file = ABSPATH . 'rota-px.php';
                $content = '<?php require_once "wp-content/plugins/pixintegra-gateway/admin/pagamento_pix.php" ?>';
                file_put_contents($file, $content);
            }
            else
            {
                $file = ABSPATH . 'rota-px.php';
                if (file_exists($file)) {
                    unlink($file);
                }
            }


        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'pixintegra') {
                $cpf = sanitize_text_field($_POST['cpf']);

                if (empty($cpf) || !valida_cpf($cpf)) {
                    wc_add_notice(__('CPF inválido ou não informado.', 'woocommerce'), 'error');
                    return;
                }

                $nome_do_produto = obter_nome_do_produto($order_id);
                $valor_do_produto = $order->get_total();
                $nome_e_sobrenome = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                $email_do_cliente = $order->get_billing_email();
                $api_token = $this->get_option('api_token');
                $api_key = $this->get_option('api_key');
                $cpf_tratado = remove_caracteres_cpf($cpf);

                $response = gerar_cobranca_pix($api_token, $api_key, $nome_do_produto, $valor_do_produto, $nome_e_sobrenome, $email_do_cliente, $cpf_tratado);

                if (isset($response)) {
                    WC()->cart->empty_cart();
                    inserir_dados_na_tabela_order($order_id, $nome_do_produto, $valor_do_produto, $response->identificador_cliente, $response->url_qrcode);
                    atualizar_transacao_id($order_id, $response->identificador_cliente);

                    if(obter_modo_checkout() == "arquivo")
                    {
                        $redirect_url = home_url('rota-px.php?order_id=' . $order_id);
                    }
                    else
                    {
                        $redirect_url = home_url('/pixintegra-pagamento/?order_id=' . $order_id);
                    }
                    
                    return [
                        'result'   => 'success',
                        'redirect' => $redirect_url,
                    ];
                }
            }

            return ['result' => 'failure'];
        }
    }

    function add_pixintegra_gateway_callback($methods) {
        $methods[] = 'WC_Pixintegra_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_pixintegra_gateway_callback');
}
?>
