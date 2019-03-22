<?php

class ControllerExtensionPaymentDagpay extends Controller {
  private $error = array();

  public function index() {
    $this->load->language('extension/payment/dagpay');
    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');
    $this->load->model('localisation/order_status');

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$this->model_setting_setting->editSetting('payment_dagpay', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
    }

    $data['action']             = $this->url->link('extension/payment/dagpay', 'user_token=' . $this->session->data['user_token'], true);
    $data['cancel']             = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
    $data['order_statuses']     = $this->model_localisation_order_status->getOrderStatuses();

    if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

    $data['breadcrumbs'] = array();
    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_home'),
        'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    );
    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_extension'),
        'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
    );
    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('extension/payment/dagpay', 'user_token=' . $this->session->data['user_token'], true)
    );

    $fields = array('payment_dagpay_status', 'payment_dagpay_enviroment_id', 'payment_dagpay_user_id', 'payment_dagpay_secret',
      'payment_dagpay_pending_status_id', 'payment_dagpay_waiting_status_id', 'payment_dagpay_paid_status_id',
      'payment_dagpay_failed_status_id', 'payment_dagpay_expired_status_id', 'payment_dagpay_canceled_status_id',
      'payment_dagpay_total', 'payment_dagpay_test_mode');

    foreach ($fields as $field) {
      if (isset($this->request->post[$field])) {
  			$data[$field] = $this->request->post[$field];
  		} else {
  			$data[$field] = $this->config->get($field);
  		}
    }

    $data['payment_dagpay_sort_order'] = isset($this->request->post['payment_dagpay_sort_order']) ?
            $this->request->post['payment_dagpay_sort_order'] :  $this->config->get('payment_dagpay_sort_order');

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/payment/dagpay', $data));
  }

  public function install() {
    $this->load->model('localisation/currency');

    $data = [
              'title' => 'Dagcoin',
              'code' => 'DAG',
              'symbol_right' => 'DAG',
              'decimal_place' => 2,
              'value' => 1
            ];
    $this->model_localisation_currency->addCurrency($data);
	}
	public function uninstall() {
    $this->load->model('localisation/currency');

    $currency = $this->model_localisation_currency->getCurrencyByCode('DAG');
    $this->model_localisation_currency->deleteCurrency($currency['currency_id']);
	}
}
