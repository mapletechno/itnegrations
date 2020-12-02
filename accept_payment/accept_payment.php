<?php
/*
 * Plugin Name: Accept Paymob Payment Gateway
 * Plugin URI: https://mapletechno.com/
 * Description: Accept payment gateway/Paymob
 * Author: Ahmed Aly
 * Author URI: https://www.mapletechno.com
 * Version: 0.8.1
 *
 */



/*
WC_Payment_Gateway class.

Every class method is described below. You can begin with copying and pasting the below code into your main plugin file.

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'weacceptx_add_gateway_class' );
function weacceptx_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Weaccept_Gateway'; // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'weacceptx_payment_init_gateway_class' );
function weacceptx_payment_init_gateway_class() {

	class WC_Weaccept_Gateway extends WC_Payment_Gateway {

 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {

$this->id = 'weaccept'; // payment gateway plugin ID
	$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
	$this->has_fields = true; // in case you need a custom credit card form
	$this->method_title = 'Accept/paymob Gateway';
	$this->method_description = 'Description of WeAccept/paymob payment gateway'; // will be displayed on the options page

	// gateways can support subscriptions, refunds, saved payment methods,
	// but in this tutorial we begin with simple payments
	$this->supports = array(
		'products'
	);

	// Method with all the options fields
	$this->init_form_fields();

	// Load the settings.
	$this->init_settings();
	$this->title = $this->get_option( 'title' );
	$this->description = $this->get_option( 'description' );
	$this->enabled = $this->get_option( 'enabled' );
	$this->testmode = 'yes' === $this->get_option( 'testmode' );
	$this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
	$this->iframe_id = $this->get_option('iframe_id');
	$this->integration_id = $this->get_option('integration_id');



	// This action hook saves the settings
	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	// We need custom JavaScript to obtain a token
	add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

	// You can also register a webhook here
	// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );

 		}

		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){

$this->form_fields = array(
		'enabled' => array(
			'title'       => 'Enable/Disable',
			'label'       => 'Enable WeAccept/paymob Gateway',
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => 'Title',
			'type'        => 'text',
			'description' => 'This controls the title which the user sees during checkout.',
			'default'     => 'Paymob Credit Card',
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => 'Description',
			'type'        => 'textarea',
			'description' => 'This controls the description which the user sees during checkout.',
			'default'     => 'Pay with your credit card via our super-cool payment gateway.',
		),
		'testmode' => array(
			'title'       => 'Test mode',
			'label'       => 'Enable Test Mode',
			'type'        => 'checkbox',
			'description' => 'Place the payment gateway in test mode using test API keys.',
			'default'     => 'yes',
			'desc_tip'    => true,
		),
		'test_private_key' => array(
			'title'       => 'Test Private Key',
			'type'        => 'password',
		),
		'private_key' => array(
			'title'       => 'Live Private Key',
			'type'        => 'password'
		),
		'integration_id' => array(
			'title'       => 'integration ID',
			'type'        => 'text'
		),
		'iframe_id' => array(
			'title'       => 'iframe ID',
			'type'        => 'text'
		)
	);

	 	}

		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
	*/

		public function payment_fields() {


		}
		public function needs_setup()
		{
			return true;
		}
		 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
		  	public function payment_scripts() {

	 	}

		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {

if( empty( $_POST[ 'billing_first_name' ]) ) {
		wc_add_notice(  'First name is required!', 'error' );
		return false;
	}
	return true;

		}

		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
global $woocommerce;
 
	// we need it to get any order detailes
	$order = wc_get_order( $order_id );
 //order total amount, to pass to the API
 $orderamount = $order->get_total();
 
	/*
 	 * Array with parameters for API interaction
	 */
	$args = array();
 
	/*
	 * Your API interaction could be built with wp_remote_post()
 	 */
//	 $response = wp_remote_post( '{payment processor endpoint}', $args );


/*
 *generate auth token
 *so we can get the payment link
 */

$json = '{
  "api_key": "'.$this->private_key.'"
}';


//$payloadName = array("client_id"=>$client, "client_secret"=>$secret,"code"=>$code);
$additionalHeaders = array(
//	"Host: tip-of-the-day.myshopify.com",
  //  "X-Shopify-Access-Token: b0bb404533ebd7f54167c59f8c8ca666",
    "Content-Type: application/json"
  );

$host = "https://accept.paymobsolutions.com/api/auth/tokens";

$chm = curl_init($host);

curl_setopt($chm, CURLOPT_HTTPHEADER, $additionalHeaders);
//curl_setopt($chm, CURLOPT_HEADER, 1);
//curl_setopt($ch, CURLOPT_USERPWD, "ahmad:123456");
curl_setopt($chm, CURLOPT_TIMEOUT, 30);
curl_setopt($chm, CURLOPT_POST, 1);
curl_setopt($chm, CURLOPT_POSTFIELDS, $json);
curl_setopt($chm, CURLOPT_RETURNTRANSFER, TRUE);
$return1 = curl_exec($chm);
curl_close($chm);
//print_r($return1);
$return1 = json_decode($return1);

//this is the auth token we need to generate the payment link
$tokenx = $return1->token;

/*
 *generate the order in paymob
 *so we can get the payment link
 */

$additionalHeaders = array(
//	"Host: tip-of-the-day.myshopify.com",
  //  "X-Shopify-Access-Token: b0bb404533ebd7f54167c59f8c8ca666",
    "Content-Type: application/json",
    "Authorization: Bearer $tokenx"
  );

// because the payment gateway, needs the amount in cents!
$neworderamount = $orderamount * 100;
$host2 = "https://accept.paymobsolutions.com/api/ecommerce/orders?token=$tokenx";
$json2 = '{
  "access_token": "'.$tokenx.'",
  "delivery_needed": "true",
  "merchant_id": "3247",
  "amount_cents": "'.$neworderamount.'",
  "currency": "EGP",
  "merchant_order_id": '.$order_id.',
  "shipping_data": {
    "apartment": "", 
    "email": "'.$order->billing_email.'", 
    "floor": "", 
    "first_name": "'.$order->billing_first_name.'", 
    "street": "'.$order->billing_address_1.'",
    "building": "", 
    "phone_number": "'.$order->billing_phone.'", 
    "postal_code": "'.$order->billing_postcode.'", 
    "city": "'.$order->billing_city.'", 
    "country": "'.$order->billing_country.'", 
    "last_name": "'.$order->billing_last_name.'", 
    "state": "'.$order->billing_state.'"
  }
}';
//echo "hi";
$chm = curl_init($host2);

curl_setopt($chm, CURLOPT_HTTPHEADER, $additionalHeaders);
//curl_setopt($chm, CURLOPT_HEADER, 1);
//curl_setopt($ch, CURLOPT_USERPWD, "ahmad:123456");
curl_setopt($chm, CURLOPT_TIMEOUT, 30);
curl_setopt($chm, CURLOPT_POST, 1);
curl_setopt($chm, CURLOPT_POSTFIELDS, $json2);
curl_setopt($chm, CURLOPT_RETURNTRANSFER, TRUE);
$return1 = curl_exec($chm);
curl_close($chm);
//print_r($return1);
$return1 = json_decode($return1);

$testingretturn = $return1;
$orderid = $return1->shipping_data->order_id;


/*
*This code is to fix a bug
*in case the order was already inserted, and the buyer did not complete it before
*an error message is returend from the API : duplicate
* so we check if this is the case, we get the order ID from the API
*/


if($return1->message == "duplicate")
{

//$orderid = 1;
$host = "https://accept.paymobsolutions.com/api/ecommerce/orders";

$chm = curl_init($host);

curl_setopt($chm, CURLOPT_HTTPHEADER, $additionalHeaders);
curl_setopt($chm, CURLOPT_TIMEOUT, 30);
curl_setopt($chm, CURLOPT_RETURNTRANSFER, TRUE);
$return1 = curl_exec($chm);
curl_close($chm);
$return1 = json_decode($return1);
foreach ($return1->results as $key => $value) {
//print_r($value);
if($value->merchant_order_id == $order_id)
{
//echo "order found!";
//echo "order id: ". $value->id;
$orderid = $value->id;
break;
}
}
}






//request payment link - 3rd step
$json = '{
  "auth_token": "'.$tokenx.'",
  "amount_cents": "'.$neworderamount.'",
  "expiration": 3600,
  "order_id": "'.$orderid.'",
  "billing_data": {
    "apartment": "0",
    "email": "'.$order->billing_email.'", 
    "floor": "0",
    "first_name": "'.$order->billing_first_name.'", 
    "street": "'.$order->billing_address_1.'",
    "building": "0",
    "phone_number": "'.$order->billing_phone.'", 
    "shipping_method": "UNK",
    "postal_code": "'.$order->billing_postcode.'", 
    "city": "'.$order->billing_city.'", 
    "country": "'.$order->billing_country.'", 
    "last_name": "'.$order->billing_last_name.'", 
    "state": "'.$order->billing_state.'"
  }, 
  "currency": "EGP",
  "integration_id": '.$this->integration_id.',
  "lock_order_when_paid": "false"
}';
$host = "https://accept.paymobsolutions.com/api/acceptance/payment_keys?token=$tokenx";
//$host = "https://accept.paymobsolutions.com/api/acceptance/payment_keys";

//$json = json_decode($json);
//print_r($json);
//exit;
$chm = curl_init($host);

curl_setopt($chm, CURLOPT_HTTPHEADER, $additionalHeaders);
//curl_setopt($chm, CURLOPT_HEADER, 1);
//curl_setopt($ch, CURLOPT_USERPWD, "ahmad:123456");
curl_setopt($chm, CURLOPT_TIMEOUT, 30);
curl_setopt($chm, CURLOPT_POST, 1);
curl_setopt($chm, CURLOPT_POSTFIELDS, $json);
curl_setopt($chm, CURLOPT_RETURNTRANSFER, TRUE);
$return1 = curl_exec($chm);
curl_close($chm);
//print_r($return1);
$return1 = json_decode($return1);
//print_r($return1);
$paytoken =  $return1->token;



			return array(
				'result' => 'success',
				'redirect' => "https://accept.paymobsolutions.com/api/acceptance/iframes/".$this->iframe_id."?payment_token=$paytoken"
				//'redirect' => $this->integration_id
			);
 
	 	}




		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {

	 	}
 	}
}
?>
