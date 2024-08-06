<?php

if (!defined('ABSPATH')) {
    exit;
}

class PixintegraAdminMenu {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_pixintegra_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_font_awesome']);
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']); // Adiciona o widget ao painel do WordPress
    }

    public function add_pixintegra_menu() {
        add_menu_page(
            'Pixintegra',
            'Pixintegra',
            'manage_options',
            'pixintegra_menu',
            [$this, 'pixintegra_menu_callback'],
            'dashicons-store'
        );
    }

    public function enqueue_font_awesome() {
        wp_enqueue_style('pixintegra-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
        wp_enqueue_script('pixintegra-accordion-js', 'https://code.jquery.com/jquery-3.6.0.min.js');
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
        <div class="wrap pixintegra-wrap">
            <style>
                .pixintegra-wrap {
                    max-width: 800px;
                    margin: 0 auto;
                    text-align: center;
                    animation: fadeIn 0.5s ease-in-out;
                }
                .pixintegra-logo-container {
                    text-align: center;
                    margin: 10% 0 20px 0;
                    animation: fadeInDown 1s ease-in-out;
                }
                .pixintegra-logo {
                    width: 150px;
                    height: auto;
                }
                .pixintegra-nav-tab-wrapper {
                    display: flex;
                    justify-content: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #00a6bf;
                    animation: fadeIn 1s ease-in-out;
                }
                .pixintegra-nav-tab {
                    margin: 0 10px;
                    padding: 10px 20px;
                    text-decoration: none;
                    color: #00a6bf;
                    font-weight: bold;
                    transition: background-color 0.3s ease, color 0.3s ease;
                }
                .pixintegra-nav-tab-active {
                    background-color: #00a6bf;
                    color: #fff;
                    border-radius: 10px 10px 0 0;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .pixintegra-nav-tab:hover {
                    background-color: #007c96;
                    color: white;
                }
                .pixintegra-tab-content {
                    padding: 20px;
                    background-color: white;
                    border: 2px solid #00a6bf;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    animation: fadeInUp 0.7s ease-in-out;
                }
                .pixintegra-saldo-box {
                    display: flex;
                    justify-content: flex-start;
                    align-items: center;
                    background-color: #f9f9f9;
                    padding: 15px;
                    border: 1px solid #00a6bf;
                    border-radius: 8px;
                    margin-bottom: 15px;
                    transition: transform 0.3s ease;
                }
                .pixintegra-saldo-box:hover {
                    transform: scale(1.02);
                }
                .pixintegra-saldo-box i {
                    font-size: 24px;
                    color: #00a6bf;
                    margin-right: 15px;
                }
                .pixintegra-saldo-box div {
                    text-align: left;
                }
                .pixintegra-saldo-box div h3 {
                    margin: 0;
                    font-size: 1.2em;
                    color: #007c96;
                }
                .pixintegra-saldo-box div p {
                    margin: 5px 0 0;
                    font-size: 1.1em;
                    color: #333;
                }
                .pixintegra-withdrawal-button {
                    display: inline-block;
                    background-color: #00a6bf;
                    color: #fff;
                    padding: 10px 20px;
                    font-size: 1.2em;
                    text-decoration: none;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    transition: background-color 0.3s ease, transform 0.2s ease;
                    border: none; /* Remove a borda */
                    cursor: pointer;
                }
                .pixintegra-withdrawal-button:disabled {
                    background-color: #cccccc;
                    cursor: not-allowed;
                }
                .pixintegra-withdrawal-button:hover:not(:disabled) {
                    background-color: #007c96;
                    transform: translateY(-2px);
                }
                .pixintegra-withdrawal-warning {
                    background-color: #f1f1f1;
                    color: #333;
                    padding: 10px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    font-size: 0.9em;
                    text-align: left;
                    border-left: 4px solid #00a6bf;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .pixintegra-withdrawal-warning i {
                    margin-right: 10px;
                    color: #007c96;
                }
                .accordion {
                    margin-top: 10px;
                    background-color: #00a6bf;
                    color: white;
                    cursor: pointer;
                    padding: 15px;
                    width: 100%;
                    border: none;
                    text-align: left;
                    outline: none;
                    font-size: 1.2em;
                    transition: background-color 0.3s ease;
                    border-radius: 5px;
                    margin-bottom: 5px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .accordion .version-text {
                    display: flex;
                    align-items: center;
                }
                .accordion .version-text i {
                    margin-right: 10px;
                }
                .accordion i.chevron {
                    transition: transform 0.3s ease;
                }
                .accordion:hover, .accordion.active {
                    background-color: #007c96;
                }
                .accordion.active i.chevron {
                    transform: rotate(180deg);
                }
                .panel {
                    padding: 0 18px;
                    background-color: white;
                    max-height: 0;
                    overflow: hidden;
                    transition: max-height 0.2s ease-out;
                    margin-bottom: 10px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                }
                .panel ul {
                    list-style: disc;
                    padding-left: 20px;
                }
                .panel ul li {
                    text-align: left;
                    margin: 10px 0;
                    font-size: 1.1em;
                    color: #333;
                }
                .pixintegra-checkbox-group {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-start;
                    margin-top: 20px;
                    text-align: left;
                }
                .pixintegra-checkbox-group label {
                    display: flex;
                    align-items: center;
                    margin-bottom: 15px;
                    font-size: 1.1em;
                    color: #333;
                    cursor: pointer;
                }
                .pixintegra-checkbox-group input[type="checkbox"] {
                    width: 20px;
                    height: 20px;
                    margin-right: 10px;
                    cursor: pointer;
                }
                .pixintegra-checkbox-group input[type="checkbox"]:checked {
                    accent-color: #00a6bf;
                }
                .pixintegra-submit-button {
                    background-color: #00a6bf;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 1.2em;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    transition: background-color 0.3s ease, transform 0.2s ease;
                }
                .pixintegra-submit-button:hover {
                    background-color: #007c96;
                    transform: translateY(-2px);
                }
                .form-table {
                  margin-left: auto;
                  margin-right: auto;
                  text-align: center;
                }
                .form-table td, .form-table th {
                  text-align: center;
                }
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                @keyframes fadeInDown {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>
            <div class="pixintegra-logo-container">
                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/logo_completa.png'); ?>" class="pixintegra-logo">
            </div>
            <nav class="pixintegra-nav-tab-wrapper">
            <a href="?page=pixintegra_menu&tab=registro_de_atualizacoes" class="pixintegra-nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'registro_de_atualizacoes' ? 'pixintegra-nav-tab-active' : ''; ?>">Registro de Atualizações</a>
                <a href="?page=pixintegra_menu&tab=exibir_saldo" class="pixintegra-nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'exibir_saldo' ? 'pixintegra-nav-tab-active' : ''; ?>">Exibir saldo</a>
                <a href="?page=pixintegra_menu&tab=realizar_saque" class="pixintegra-nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'realizar_saque' ? 'pixintegra-nav-tab-active' : ''; ?>">Solicitar saque</a>
                <a href="?page=pixintegra_menu&tab=configuracoes_widget" class="pixintegra-nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'configuracoes_widget' ? 'pixintegra-nav-tab-active' : ''; ?>">Configurações do Widget</a>
            </nav>
            <div id="tab_content" class="pixintegra-tab-content">
            <?php
                $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'exibir_saldo';
                if ($current_tab === 'exibir_saldo') {
                    $this->display_balance($api_token, $api_key);
                } elseif ($current_tab === 'realizar_saque') {
                    $this->process_withdrawal($api_token, $api_key);
                } elseif ($current_tab === 'registro_de_atualizacoes') {
                    $this->display_update_log();
                } elseif ($current_tab === 'configuracoes_widget') {
                    $this->display_widget_settings();
                }
                ?>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var acc = document.getElementsByClassName("accordion");
                for (var i = 0; i < acc.length; i++) {
                    acc[i].addEventListener("click", function() {
                        this.classList.toggle("active");
                        var panel = this.nextElementSibling;
                        if (panel.style.maxHeight) {
                            panel.style.maxHeight = null;
                        } else {
                            panel.style.maxHeight = panel.scrollHeight + "px";
                        }
                    });
                }
            });
        </script>
        <?php
    }

    private function display_balance($api_token, $api_key) {
        $response = consultar_saldos_pixintegra($api_token, $api_key);
        if ($response) {
            $saldo_disponivel = esc_html($response->saldo_disponivel);
            $saldo_a_receber = esc_html($response->saldo_a_receber);
            $saldo_bloqueado = esc_html($response->saldo_bloqueado);
            ?>
            <div class="pixintegra-saldo-box">
                <i class="fas fa-wallet"></i>
                <div>
                    <h3>Saldo Disponível</h3>
                    <p><?php echo $saldo_disponivel; ?></p>
                </div>
            </div>
            <div class="pixintegra-saldo-box">
                <i class="fas fa-hand-holding-usd"></i>
                <div>
                    <h3>Saldo a Receber</h3>
                    <p><?php echo $saldo_a_receber; ?></p>
                </div>
            </div>
            <div class="pixintegra-saldo-box">
                <i class="fas fa-lock"></i>
                <div>
                    <h3>Saldo Bloqueado</h3>
                    <p><?php echo $saldo_bloqueado; ?></p>
                </div>
            </div>
            <?php
        } else {
            echo "<div class='notice notice-error'><p>Erro ao obter saldo.</p></div>";
        }
    }

  

    private function process_withdrawal($api_token, $api_key) {
        $response = consultar_saldos_pixintegra($api_token, $api_key);
        if ($response) {
            function convert_to_number($value) {
                $value = str_replace(['R$', '.'], '', $value);
                $value = str_replace(',', '.', $value);
                return (float)$value;
            }
            $saldo_disponivel = esc_html($response->saldo_disponivel);
            $saldo_disponivel_num = convert_to_number($saldo_disponivel);
            ?>
            <div class="postbox">
                <h2 class="hndle"><span>Solicitar Saque</span></h2>
                <div class="inside">
                    <div class="pixintegra-saldo-box">
                        <i class="fas fa-wallet"></i>
                        <div>
                            <h3>Saldo Disponível</h3>
                            <p><?php echo $saldo_disponivel; ?></p>
                        </div>
                    </div>
                    <?php if ($saldo_disponivel_num >= 50000): ?>
                        <div class="pixintegra-withdrawal-warning">
                            <i class="fas fa-info-circle"></i>
                            <span>O valor máximo de saque por vez é de R$ 50.000,00.</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($saldo_disponivel_num > 10): ?>
                        <div class="pixintegra-withdrawal-warning">
                            <i class="fas fa-info-circle"></i>
                            <span>O limite diário para saques é de R$ 50.000,00.</span>
                        </div>
                        <form method="post" action="">
                            <?php wp_nonce_field('realizar_saque_nonce', 'realizar_saque_nonce_field'); ?>
                            <input type="hidden" name="action" value="realizar_saque">
                            <input type="submit" class="pixintegra-withdrawal-button" value="Solicitar Saque">
                        </form>
                    <?php else: ?>
                        <div class="notice notice-error">
                            <p>Saldo insuficiente para realizar saque. O saldo mínimo necessário é de R$ 10,00.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
        } else {
            echo "<div class='notice notice-error'><p>Erro ao obter saldo.</p></div>";
        }
    }

    private function display_update_log() {
        $response = wp_remote_get('https://pixintegra.com.br/plugin-wordpress/update.json');
        
        if (is_wp_error($response)) {
            echo "<div class='notice notice-error'><p>Não foi possível obter as atualizações no momento.</p></div>";
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Verifica se o JSON foi decodificado corretamente
        if (!is_array($data) || !isset($data['versions'])) {
            echo "<div class='notice notice-error'><p>Erro ao processar as atualizações.</p></div>";
            return;
        }

        // Filtrar para exibir apenas as últimas três versões
        $versions = array_slice($data['versions'], 0, 3);

        foreach ($versions as $version) {
            if (isset($version['version']) && isset($version['changes'])) {
                echo '<button class="accordion"><span class="version-text"><i class="fas fa-code-branch"></i>Versão ' . esc_html($version['version']) . '</span><i class="fas fa-chevron-down chevron"></i></button>';
                echo '<div class="panel"><ul>';
                foreach ($version['changes'] as $change) {
                    echo '<li>' . esc_html($change) . '</li>';
                }
                echo '</ul></div>';
            } else {
                echo "<p>Formato de atualização inválido.</p>";
            }
        }
    }

    private function display_widget_settings() {
        if (isset($_POST['pixintegra_widget_enabled'])) {
            update_option('pixintegra_widget_enabled', sanitize_text_field($_POST['pixintegra_widget_enabled']));
            echo '<div class="notice notice-success"><p>Configurações salvas com sucesso!</p></div>';
        }

        $widget_enabled = get_option('pixintegra_widget_enabled', '1');
        ?>
        <form method="post">
            <h2>Configurações do Widget</h2>
            <table class="form-table" >
                <tr>
                    <th scope="row"><label for="pixintegra_widget_enabled">Ativar Widget "Exibir saldos"</label></th>
                    <td > 
                        <select name="pixintegra_widget_enabled" id="pixintegra_widget_enabled">
                            <option value="1" <?php selected($widget_enabled, '1'); ?>>Ativado</option>
                            <option value="0" <?php selected($widget_enabled, '0'); ?>>Desativado</option>
                        </select>
                    </td>
                </tr>
            </table>

             <input type="submit" class="pixintegra-submit-button" value="Salvar Configurações">
        </form>
        <?php
    }

    public function add_dashboard_widget() {
        $widget_enabled = get_option('pixintegra_widget_enabled', '1');
        if ($widget_enabled === '1') {
            wp_add_dashboard_widget(
                'pixintegra_saldos_widget',   // ID do widget
                'Exibir saldos',              // Título do widget
                [$this, 'display_saldos_widget'] // Função de callback que renderiza o widget
            );
        }
    }

    public function display_saldos_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pixintegra_auth';
        $api_token = $wpdb->get_var($wpdb->prepare("SELECT api_token FROM $table_name LIMIT 1"));
        $api_key = $wpdb->get_var($wpdb->prepare("SELECT api_key FROM $table_name LIMIT 1"));
    
        if ($api_token && $api_key) {
            $response = consultar_saldos_pixintegra($api_token, $api_key);
            if ($response) {
                $saldo_disponivel = esc_html($response->saldo_disponivel);
                $saldo_a_receber = esc_html($response->saldo_a_receber);
                $saldo_bloqueado = esc_html($response->saldo_bloqueado);
                ?>
                <div class="pixintegra-dashboard-widget" style="font-family: 'Roboto', sans-serif; background-color: #fafafa; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); max-width: 450px; margin: 0 auto;">
                    <div class="pixintegra-saldo-box" style="margin-bottom: 15px;">
                        <div style="background-color: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #eaeaea;">
                            <h3 style="margin: 0; font-size: 1.3em; font-weight: 500; color: #333;"><i class="fas fa-wallet" style="color: #28a745; margin-right: 8px;"></i> Saldo Disponível</h3>
                            <p style="margin: 5px 0 0; font-size: 1.1em; color: #666;"><?php echo $saldo_disponivel; ?></p>
                        </div>
                    </div>
                    <div class="pixintegra-saldo-box" style="margin-bottom: 15px;">
                        <div style="background-color: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #eaeaea;">
                            <h3 style="margin: 0; font-size: 1.3em; font-weight: 500; color: #333;"><i class="fas fa-hand-holding-usd" style="color: #ffc107; margin-right: 8px;"></i> Saldo a Receber</h3>
                            <p style="margin: 5px 0 0; font-size: 1.1em; color: #666;"><?php echo $saldo_a_receber; ?></p>
                        </div>
                    </div>
                    <div class="pixintegra-saldo-box">
                        <div style="background-color: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #eaeaea;">
                            <h3 style="margin: 0; font-size: 1.3em; font-weight: 500; color: #333;"><i class="fas fa-lock" style="color: #dc3545; margin-right: 8px;"></i> Saldo Bloqueado</h3>
                            <p style="margin: 5px 0 0; font-size: 1.1em; color: #666;"><?php echo $saldo_bloqueado; ?></p>
                        </div>
                    </div>
                </div>
                <?php
            } else {
                echo "<div class='notice notice-error'><p>Erro ao obter saldo.</p></div>";
            }
        } else {
            echo '<div class="notice notice-error"><p>As chaves de API não estão configuradas.</p></div>';
        }
    }
    
    
    
    
}

new PixintegraAdminMenu();

?>
