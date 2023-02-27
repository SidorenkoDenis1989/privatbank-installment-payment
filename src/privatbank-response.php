<?php
add_action( 'wp_loaded', function() {
   if ( isset($_GET['privatbank_payment_response'] )) {
      do_action('privatbank_payment_response', new PrivatbankResponseListener($_GET));
   }
} );

class PrivatbankResponseListener {

   private $valid = false;
   private $response_params = [];
   private $order_info = [];

   public function __construct(array $order_info) {

		$response_json = file_get_contents('php://input');
		$response_values = json_decode($response_json, true);

   	 	$this->valid = $this->validatePostData($response_values);
       	$this->response_params = $response_values;
       	$this->order_info = $order_info;
       	$this->handleResponse();
   }

	private function handleResponse() {
      	if ($this->valid) {
      		$message = '<ul>';
      		$message .= '<li>Телефон: ' . $this->order_info["phone"] . '</li>';
      		$message .= "<li>Ім'я: " . $this->order_info["name"] . '</li>';
      		$message .= "<li>Курс: " . $this->order_info["course"] . '</li>';
      		$message .= "<li>Ціна: " . $this->order_info["price"] . '</li>';
      		$message .= "<li>Інвойс: " . $this->order_info["store_order_id"] . '</li>';
      		$message .= '</ul>';

      		$email = get_option( 'monobank_email', '' );

      		if($email) {
	      		wp_mail($email, "Нова оплата частинами на сайті", $message, 'Content-type: text/html');
      		}
   	}
   }

	private function validatePostData(array $postdata) {
		return $postdata['paymentState'] === "SUCCESS";
	}
}