<?php
require_once(DIR_SYSTEM . 'library/dagpay/dagpay.php');

class ControllerExtensionPaymentDagpay extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/dagpay');
        $this->load->model('checkout/order');

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['action'] = $this->url->link('extension/payment/dagpay/checkout', '', true);

        return $this->load->view('extension/payment/dagpay', $data);
    }

    public function checkout()
    {
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $description = '';
        foreach ($this->cart->getProducts() as $product) {
            $description .= $product['quantity'] . ' × ' . $product['name'] . ';';
        }

        $response = $this->redirectToPayment(
            $order_info['order_id'],
            (float) number_format($order_info['total'], 2, '.', ''),
            $description,
            $order_info['currency_code']
        );
        if ($response) {
            $this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_dagpay_pending_status_id'));
            $this->response->redirect($response['paymentUrl']);
        } else {
            $this->log->write('Order #' . $order_info['order_id'] . ' is not valid.');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }
    }

    public function cancel()
    {
        $this->response->redirect($this->url->link('checkout/cart', ''));
    }

    public function success()
    {
        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    public function callback()
    {
        $this->load->model('checkout/order');
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        $dagpay_invoice_status = $input['state'];
        $order_id = $input['paymentId'];

        $order_info = $this->model_checkout_order->getOrder($order_id);

        $signature_check = $this->checkInvoiceSignature($input);

        if (!empty($order_info) && $signature_check) {
            switch ($dagpay_invoice_status) {
                case 'PAID':
                case 'PAID_EXPIRED':
                    $order_status = 'payment_dagpay_paid_status_id';

                    break;
                case 'PENDING':
                    $order_status = 'payment_dagpay_pending_status_id';

                    break;
                case 'WAITING_FOR_CONFIRMATION':
                    $order_status = 'payment_dagpay_waiting_status_id';

                    break;
                case 'EXPIRED':
                    $order_status = 'payment_dagpay_expired_status_id';

                    break;
                case 'FAILED':
                    $order_status = 'payment_dagpay_failed_status_id';

                    break;
                case 'CANCELLED':
                    $order_status = 'payment_dagpay_canceled_status_id';

                    break;
                default:
                    $order_status = null;
            }
            if ($order_status !== null) {
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($order_status));
            }
        } elseif (!$signature_check) {
            $error_message = 'Invalid signature provided in #Order ' . $order_id;
            $this->log->write($error_message);
        }
        $this->response->addHeader('HTTP/1.1 200 OK');
    }

    private function initDagpayClient()
    {
        return new DagpayClient(
            $this->config->get('payment_dagpay_enviroment_id'),
            $this->config->get('payment_dagpay_user_id'),
            $this->config->get('payment_dagpay_secret'),
            $this->config->get('payment_dagpay_test_mode'),
            'standalone'
        );
    }

    private function checkInvoiceSignature($info)
    {
        $client_instance = $this->initDagpayClient();
        $expected_signature = $client_instance->getInvoiceInfoSignature($info);
        $received_signature = $info['signature'];

        return $expected_signature == $received_signature;
    }

    private function redirectToPayment($orderId, $total, $desc, $currency = 'DAG')
    {
        $client = $this->initDagpayClient();
        return $client->createInvoice($orderId, $currency, $total, $desc);
    }
}
