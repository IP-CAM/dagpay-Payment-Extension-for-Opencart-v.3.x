<?php

class ModelExtensionPaymentDagpay extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/dagpay');
        $status = true;
        $payment_dagpay_total = $this->config->get('payment_dagpay_total');
        if ($payment_dagpay_total > 0 && $payment_dagpay_total > $total) {
            $status = false;
        }
        $method_data = array();
        if ($status) {
            $method_data = array(
                'code' => 'dagpay',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('payment_dagpay_sort_order')
            );
        }
        return $method_data;
    }
}
