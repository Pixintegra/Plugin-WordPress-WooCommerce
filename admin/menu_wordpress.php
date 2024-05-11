<?php

if (!defined('ABSPATH')) {
    exit;
}

class PixintegraAdminMenu {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_pixintegra_menu']);
    }

    
    public function add_pixintegra_menu() {
        $icon_url = plugin_dir_url(__FILE__) . '../assets/img/plogo_menu.ico';
        add_menu_page(
            'Pixintegra',
            'Pixintegra',
            'manage_options',
            'pixintegra_menu',
            [$this, 'pixintegra_menu_callback'],
            $icon_url
        );
    }

    public function pixintegra_menu_callback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pixintegra_auth';
        $api_token = $wpdb->get_var($wpdb->prepare("SELECT api_token FROM $table_name LIMIT 1"));
        $api_key = $wpdb->get_var($wpdb->prepare("SELECT api_key FROM $table_name LIMIT 1"));

        if (!$api_token || !$api_key) {
            echo '<div class="notice notice-error"><p>As operações só podem ser realizadas se as chaves de API estiverem configuradas. Para configurar, acesse o WooCommerce > Pagamentos > PIXINTEGRA soluções avançadas e clique no botão "Gerenciar".</p></div>';
            return;
        }

        $this->render_admin_page($api_token, $api_key);
    }

    private function render_admin_page($api_token, $api_key) {
        ?>
        <div class="wrap">
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/logo_completa.png'); ?>" style="max-width: 150px; height: auto;">
            <nav class="nav-tab-wrapper">
                <a href="?page=pixintegra_menu&tab=exibir_saldo" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'exibir_saldo' ? 'nav-tab-active' : ''; ?>">Exibir saldo</a>
                <a href="?page=pixintegra_menu&tab=realizar_saque" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'realizar_saque' ? 'nav-tab-active' : ''; ?>">Realizar saque</a>
            </nav>
            <div id="tab_content" class="metabox-holder">
                <?php
                $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'exibir_saldo';
                if ($current_tab === 'exibir_saldo') {
                    $this->display_balance($api_token, $api_key);
                } elseif ($current_tab === 'realizar_saque') {
                    $this->process_withdrawal($api_token, $api_key);
                }
                ?>
            </div>
        </div>
        <link rel="stylesheet" type="text/css" href="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/css/styles.css'); ?>">
        <?php
    }

    private function display_balance($api_token, $api_key) {
        $response = consultar_saldos_pixintegra($api_token, $api_key);
        if ($response) {
            $saldo_disponivel = esc_html($response->saldo_disponivel);
            $saldo_a_receber = esc_html($response->saldo_a_receber);
            $saldo_bloqueado = esc_html($response->saldo_bloqueado);
            ?>
            <div class="postbox">
                <h2 class="hndle"><span>Saldo</span></h2>
                <div class="inside">
                    <ul>
                        <li><strong>Saldo Disponível:</strong> <?php echo $saldo_disponivel; ?></li>
                        <li><strong>Saldo a Receber:</strong> <?php echo $saldo_a_receber; ?></li>
                        <li><strong>Saldo Bloqueado:</strong> <?php echo $saldo_bloqueado; ?></li>
                    </ul>
                </div>
            </div>
            <?php
        } else {
            echo "<div class='notice notice-error'><p>Erro ao obter saldo.</p></div>";
        }
    }

    private function process_withdrawal($api_token, $api_key) {
        ?>
        <div class="postbox">
            <h2 class="hndle"><span>Realizar Saque</span></h2>
            <div class="inside">
             <?php
               $response = consultar_saldos_pixintegra($api_token, $api_key);
               $response = consultar_saldos_pixintegra($api_token, $api_key);
               $saldo_filtrado = str_replace(["R$"], "", $response->saldo_disponivel);
               $saldo_filtrado = str_replace(",", ".", $saldo_filtrado); 
               $saldo_decimal = number_format((float) $saldo_filtrado, 2, '.', '');
               $saldo_decimal_float = (float) $saldo_decimal;
               if ($response && $saldo_decimal_float >= 10) {
                  echo "<p>Saldo disponível para saque: " . $response->saldo_disponivel . "</p>";
                   echo '<form method="post" action="">' . wp_nonce_field("realizar_saque_nonce", "realizar_saque_nonce_field", true, false) . '
                   <input type="hidden" name="action" value="realizar_saque">
                   <input type="submit" class="button button-primary" value="Realizar Saque">
                   </form>';
                } else {
                   echo "<p>Saldo insuficiente para realizar saque.</p>";
                }
            ?>
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'realizar_saque') {
                    if (isset($_POST['realizar_saque_nonce_field']) && wp_verify_nonce($_POST['realizar_saque_nonce_field'], 'realizar_saque_nonce')) {
                        $response = realizar_saque_pixintegra($api_token, $api_key);
                        if ($response) {
                            echo "<p>" . esc_html($response) . "</p>";
                        } else {
                            echo "<p>Erro ao realizar saque.</p>";
                        }
                    } else {
                        echo "<p>Falha na verificação de segurança. Tente novamente.</p>";
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }
}

new PixintegraAdminMenu();
?>
