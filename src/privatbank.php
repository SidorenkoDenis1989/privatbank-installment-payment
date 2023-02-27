<?php
add_action( 'plugins_loaded', array( 'PrivatBank', 'init' ));

class PrivatBank {
    public function __construct() {

    }
}

new PrivatBank();

add_action( 'wp_loaded', function() {
   if ( isset($_GET['privatbank_payment_response'] )) {
      do_action('privatbank_payment_response', new PrivatbankResponseListener($_GET));
   }
} );

class PrivatbankResponseListener {
   public function __construct(array $order_info) {

   }
}