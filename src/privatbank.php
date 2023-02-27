<?php
add_action( 'plugins_loaded', array( 'PrivatBank', 'init' ));

class PrivatBank {
    public function __construct() {
    	$this->store_id = get_option( 'privatbank_store_id');
    	$this->store_password = get_option( 'privatbank_password');
        add_filter('admin_init', array($this, 'register_privatbank_general_settings_fields'));
		add_action('wp_ajax_privatbank_payment', array($this, 'privatbank_payment_handler'));
		add_action('wp_ajax_nopriv_privatbank_payment', array($this, 'privatbank_payment_handler'));
		add_action( 'wp_enqueue_scripts', array($this, 'register_privatbank_assets'));
		add_action( 'zakra_action_after', array($this, 'add_popup_to_footer'));
    }

    function register_privatbank_general_settings_fields() {
		register_setting('general', 'privatbank_store_id', 'esc_attr');
		add_settings_field('privatbank_store_id', '<label for="privatbank_store_id">' . __('ПриватБанк. Идентификатор магазина') . '</label>' , array($this, 'general_settings_privatbank_store_id_html'), 'general');

		register_setting('general', 'privatbank_password', 'esc_attr');
		add_settings_field('privatbank_password', '<label for="privatbank_password">' . __('ПриватБанк. Пароль магазина') . '</label>' , array($this, 'general_settings_privatbank_password_html'), 'general');

		register_setting('general', 'privatbank_parts', 'esc_attr');
		add_settings_field('privatbank_parts', '<label for="privatbank_parts">' . __('Количество платежей для оплаты частями от ПриватБанк?') . '</label>' , array($this, 'general_settings_privatbank_parts_html'), 'general');

		register_setting('general', 'privatbank_is_active', 'esc_attr');
		add_settings_field('privatbank_is_active', '<label for="privatbank_is_active">' . __('Включить оплату частями от ПриватБанк?') . '</label>' , array($this, 'general_settings_privatbank_is_active_html'), 'general');
    }

    function register_privatbank_assets() {
	    wp_register_style( 'privatbank_styles', '/wp-content/themes/zakra-child/styles/privatbank.css', false, '20230220' );
	    wp_enqueue_style( 'privatbank_styles' );

        wp_enqueue_script( 'privatbank-scripts', '/wp-content/themes/zakra-child/scripts/privatbank.js', array(), "20230220", true);
    	wp_localize_script('privatbank-scripts', 'privatbank_scripts_vars', array(
            	'ajaxurl' => site_url() . '/wp-admin/admin-ajax.php',
        	)
    	);
    }

    function general_settings_privatbank_store_id_html() {
		echo '<input type="text" id="privatbank_store_id" name="privatbank_store_id" value="' . $this->store_id . '" />';
	}

    function general_settings_privatbank_password_html() {
		echo '<input type="text" id="privatbank_password" name="privatbank_password" value="' . $this->store_password . '" />';
	}

	function general_settings_privatbank_parts_html() {
		$privatbank_parts = get_option( 'privatbank_parts', '' );
		echo '<select type="checkbox" id="privatbank_parts" name="privatbank_parts"/>';
		for ($i = 2; $i <= 25; $i++) {
			echo '<option value="' . $i . '" ';
			if ($privatbank_parts == $i) {
				echo "selected";
			}
			echo '>' . $i . '</option>';
		}
		echo '</select>';
	}

	function general_settings_privatbank_is_active_html() {
		$privatbank_is_active = get_option( 'privatbank_is_active', '' );
		echo '<select type="checkbox" id="privatbank_is_active" name="privatbank_is_active"/>';
		echo '<option value="yes" ';
		if ($privatbank_is_active === "yes") {
			echo "selected";
		}
		echo '>Да</option>';
		echo '<option value="no" ';
		if ($privatbank_is_active === "no") {
			echo "selected";
		}
		echo '>Нет</option>';
		echo '</select>';
	}

	function privatbank_payment_handler() {
   		$url = 'https://payparts2.privatbank.ua/ipp/v2/payment/create';
   		$language = $_POST['language'];
   		$priceDecimals = 2;
   		$priceDecimalsSeparator = ".";
   		$priceThousandsSeparator = "";
		$price = number_format(($_POST['price'] ? $_POST['price'] : 0), $priceDecimals, $priceDecimalsSeparator, $priceThousandsSeparator);
		$withoutFloatPrice = (+$price) * 100;

		$course = trim($_POST['course'] ? $_POST['course'] : "");

		$name = trim($_POST['name'] ? $_POST['name'] : 0);

		$phone = $_POST['phone'] ? $_POST['phone'] : "";
		$phone = str_replace("(", "", $phone);
		$phone = str_replace(")", "", $phone);
		$phone = str_replace("-", "", $phone);
		$phone = str_replace(" ", "", $phone);

		$phoneWithoutPlus = str_replace("+", "", $phone);

		$order_id = date("Y-m-d") . '/' . date("H:i:s");
		$privatbank_parts = get_option( 'privatbank_parts', '' );
		$parts = $privatbank_parts ? $privatbank_parts : 3;
		$merchant_type = "PP";

		$response_url = site_url() . '?privatbank_payment_response=true&phone=' . $phoneWithoutPlus . '&store_order_id=' . $order_id . '&name=' . $name . '&price=' . $price . '&course=' . $course;

		$redirect_url = $language === 'ru' ? site_url() . "/ru/uspeshnaya-zayavka/" : site_url() . '/uspishna-zayavka/';

		$product_string = $course . 1 . $withoutFloatPrice;

		$signature_string = $this->store_password . $this->store_id . $order_id . $withoutFloatPrice . $parts .  $merchant_type . $response_url . $redirect_url . $product_string . $this->store_password;
   		$signature = base64_encode(SHA1($signature_string, true));


   		$postfields  = array(
           	'storeId'  => $this->store_id,
           	'orderId' => $order_id,
           	'amount' => $price,
           	"partsCount" => $parts,
          	"merchantType" => "PP",
		    "products" => [
		        [
		            "name" => $course,
		            "count" => 1,
		            "price" => $price
		        ],
		    ],
		    "responseUrl" => $response_url,
		    "redirectUrl" => $redirect_url,
		    "signature" => $signature,
        );

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
		    CURLOPT_POST => TRUE,
		    CURLOPT_RETURNTRANSFER => TRUE,
		    CURLOPT_HTTPHEADER => array(
				'Accept: application/json',
			    'Accept-Encoding: UTF-8',
			    'Content-Type: application/json; charset=UTF-8',
		    ),
		    CURLOPT_POSTFIELDS => json_encode($postfields)
		));
		$response = curl_exec($ch);
		$responseData = json_decode($response, TRUE);
		curl_close($ch);

		$payment_url = "https://payparts2.privatbank.ua/ipp/v2/payment?token=" . $responseData["token"];

   		wp_send_json(array(
   			'status' => $responseData["state"],
   			'message' => $responseData["message"],
			'redirect_url' => $payment_url,
		));
	}

	function add_popup_to_footer() {
		require_once(get_stylesheet_directory() . '/template-parts/privatbank-popup.php');
	}
}

new PrivatBank();