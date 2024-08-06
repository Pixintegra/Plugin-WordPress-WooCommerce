<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['order_id'])) {
    wp_die('Invalid request', 'Invalid Request', 403);
}

$order_id = intval($_GET['order_id']);

$order = wc_get_order($order_id);
if (!$order) {
    wp_die('Order not found.', 'Order Not Found', 404);
}


$amount = $order->get_total();
$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
$nome_produto = obter_nome_do_produto($order_id);
$qr_code_url = obter_qrcode_url($order_id);


if (!wp_script_is('jquery', 'enqueued')) {
    wp_enqueue_script('jquery');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f4f7;
        }

        .container {
            height: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            width: 100%;
            max-width: 700px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
  jQuery(document).ready(function($) {
    function updatePaymentStatus(message, iconClass, textClass, redirectUrl) {
        $('#payment-status').html(`<i class="fa ${iconClass} fa-lg me-2"></i><span class="${textClass} roboto-medium">${message}</span>`);
        setTimeout(function() {
            window.location.href = redirectUrl;
        }, 3000);
    }

    function checkPaymentStatus() {
        $.ajax({
            url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
            method: "POST",
            dataType: 'json',
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            data: {
                action: 'check_pix_payment_status',
                order_id: <?php echo $order_id; ?>,
                _wpnonce: '<?php echo wp_create_nonce('check_pix_payment_status'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.status == 'pago') {
                    updatePaymentStatus(
                        'Pagamento confirmado!',
                        'fa-check-circle text-success',
                        'text-success',
                        response.data.redirect_url
                    );
                } else if(response.success && response.data.status == 'cancelado'){ 
                    updatePaymentStatus(
                        'Tempo para pagamento expirado!',
                        'fa-check-circle text-danger',
                        'text-danger',
                        response.data.redirect_url
                    );
                } else {
                    setTimeout(checkPaymentStatus, 2000);
                }
            },
            error: function() {
                setTimeout(checkPaymentStatus, 5000);
            }
        });
    }

    checkPaymentStatus();
});
    </script>
</head>
<body>
<div class="container">
    <div class="card border-0 shadow">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/logo_completa.png'); ?>" alt="Logo" width="150" height="70" class="img-fluid">
            </div>
            <h5 class="card-title text-center mb-4">Finalize seu pagamento</h5>
            <div class="d-flex justify-content-between align-items-center my-3">
                <span class="text-muted">Produto:</span>
                <strong><?php echo esc_html($nome_produto); ?></strong>
            </div>
            <div class="d-flex justify-content-between align-items-center my-3 mb-3">
                <span class="text-muted">Preço:</span>
                <strong class="text-success">R$ <?php echo number_format($amount, 2, ',', '.'); ?></strong>
            </div>
            <div class="text-center my-4">
                <div id="payment-status" class="d-flex align-items-center justify-content-center">
                    <i class="fa fa-spinner fa-spin fa-lg me-2"></i>
                    <span class="text-muted roboto-medium">Processando...</span>
                </div>
                <h6 class="text-muted">Escaneie o QR Code para pagar</h6>
                <img src="<?php echo esc_url($qr_code_url); ?>" class="img-fluid" style="max-width:350px; max-height:350px;" alt="QR Code para pagamento via PIX">
            </div>
        </div>
        <div class="card-footer">
            <p class="mb-0 text-center">Pixintegra Solução e Intermediação de Negócios LTDA<br>CNPJ: 53.471.075/0001-17</p>
        </div>
    </div>
</div>
</body>
</html>
