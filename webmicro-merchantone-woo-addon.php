<?php
/**
 * Plugin Name: Merchant One WooCommerce Addon
 * Plugin URI: Plugin URI: https://wordpress.org/plugins/webmicro-merchantone-woo-addon/
 * Description: This plugin adds a payment option in WooCommerce for customers to pay with their Credit Cards Via Merchant One.
 * Version: 1.0.0
 * Author: Syed Nazrul Hassan
 * Author URI: https://nazrulhassan.wordpress.com/
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function merchantone_init()
{
	
	function add_merchantone_gateway_class( $methods ) 
	{
		$methods[] = 'WC_MerchantOne_Gateway'; 
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_merchantone_gateway_class' );
	
	if(class_exists('WC_Payment_Gateway'))
	{
		class WC_MerchantOne_Gateway extends WC_Payment_Gateway 
		{
		public function __construct()
		{

		$this->id               = 'merchantonegateway';
		$this->icon             = apply_filters( 'woocommerce_merchantone_icon', plugins_url( 'images/merchantone.png' , __FILE__ ) );
		$this->has_fields       = true;
		$this->method_title     = 'Merchant One Cards Settings';		
		$this->init_form_fields();
		$this->init_settings();
		$this->title			          = $this->get_option( 'merchantone_title' );
		$this->merchantone_apilogin        = $this->get_option( 'merchantone_apilogin' );
		$this->merchantone_transactionkey  = $this->get_option( 'merchantone_transactionkey' );
		$this->merchantone_sandbox         = $this->get_option( 'merchantone_sandbox' ); 
		
		$this->merchantone_cardtypes       = $this->get_option( 'merchantone_cardtypes'); 
		
		$this->merchantone_liveurl         = 'https://secure.merchantonegateway.com/api/transact.php';
          
         
		if(!defined("MERCHANT_ONE_SANDBOX"))
		{define("MERCHANT_ONE_SANDBOX", ($this->merchantone_sandbox =='yes'? true : false));}
		
		
		
		 if (is_admin()) 
		 {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); 		 }

		}
			
		public function admin_options()
		{
		?>
		<h3><?php _e( 'Merchant One addon for WooCommerce', 'woocommerce' ); ?></h3>
		<p><?php  _e( 'Merchant One is a payment gateway service provider allowing merchants to accept credit card.', 'woocommerce' ); ?></p>
		<table class="form-table">
		  <?php $this->generate_settings_html(); ?>
		</table>
		<?php
		}
		
		
		public function init_form_fields()
		{
		$this->form_fields = array
		(
			'enabled' => array(
			  'title' => __( 'Enable/Disable', 'woocommerce' ),
			  'type' => 'checkbox',
			  'label' => __( 'Enable Merchant One', 'woocommerce' ),
			  'default' => 'yes'
			  ),
			'merchantone_title' => array(
			  'title' => __( 'Title', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This controls the title which the buyer sees during checkout.', 'woocommerce' ),
			  'default' => __( 'Merchant One merchantone', 'woocommerce' ),
			  'desc_tip'      => true,
			  ),
			'merchantone_apilogin' => array(
			  'title' => __( 'API Login ID', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This is the API Login ID Merchant One.', 'woocommerce' ),
			  'default' => '',
			  'desc_tip'      => true,
			  'placeholder' => 'Merchant One API Login ID'
			  ),
			'merchantone_transactionkey' => array(
			  'title' => __( 'Transaction Key', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This is the Transaction Key of Merchant One.', 'woocommerce' ),
			  'default' => '',
			  'desc_tip'      => true,
			  'placeholder' => 'Merchant One Transaction Key'
			  ),
			'merchantone_sandbox' => array(
			  'title'       => __( 'Transaction Mode', 'woocommerce' ),
			  'type'        => 'checkbox',
			  'label'       => __( 'Enable Merchant One sandbox (Live Mode if Unchecked)', 'woocommerce' ),
			  'description' => __( 'If checked its in sanbox mode and if unchecked its in live mode', 'woocommerce' ),
			  'desc_tip'      => true,
			  'default'     => 'no',
			),
			'merchantone_cardtypes' => array(
			 'title'    => __( 'Accepted Cards', 'woocommerce' ),
			 'type'     => 'multiselect',
			 'class'    => 'chosen_select',
			 'css'      => 'width: 350px;',
			 'desc_tip' => __( 'Select the card types to accept.', 'woocommerce' ),
			 'options'  => array(
				'mastercard'       => 'MasterCard',
				'visa'             => 'Visa',
				'discover'         => 'Discover',
				'amex' 		    => 'American Express',
				'jcb'		    => 'JCB',
				'dinersclub'       => 'Dinners Club',
			 ),
			 'default' => array( 'mastercard', 'visa', 'discover', 'amex' ),
			),
	  	);
  		}
				
		/*Get Card Types*/
		function get_card_type($number)
		{
		    $number=preg_replace('/[^\d]/','',$number);
		    if (preg_match('/^3[47][0-9]{13}$/',$number))
		    {
		        return 'amex';
		    }
		    elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
		    {
		        return 'dinersclub';
		    }
		    elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number))
		    {
		        return 'discover';
		    }
		    elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number))
		    {
		        return 'jcb';
		    }
		    elseif (preg_match('/^5[1-5][0-9]{14}$/',$number))
		    {
		        return 'mastercard';
		    }
		    elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number))
		    {
		        return 'visa';
		    }
		    else
		    {
		        return 'unknown';
		    }
		}// End of getcard type function
		
		
		//Function to check IP
		function get_client_ip() 
		{
			$ipaddress = '';
			if (getenv('HTTP_CLIENT_IP'))
				$ipaddress = getenv('HTTP_CLIENT_IP');
			else if(getenv('HTTP_X_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
			else if(getenv('HTTP_X_FORWARDED'))
				$ipaddress = getenv('HTTP_X_FORWARDED');
			else if(getenv('HTTP_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_FORWARDED_FOR');
			else if(getenv('HTTP_FORWARDED'))
				$ipaddress = getenv('HTTP_FORWARDED');
			else if(getenv('REMOTE_ADDR'))
				$ipaddress = getenv('REMOTE_ADDR');
			else
				$ipaddress = '0.0.0.0';
			return $ipaddress;
		}
		
		//End of function to check IP

		public function merchantone_params($wc_order) {

			$merchantone_args = array(
			// Login Information
			'username'  		=> $this->merchantone_apilogin,
			'password'  		=> $this->merchantone_transactionkey,
			// Sales Information
			'ccnumber'  		=> sanitize_text_field($_POST['merchantone_cardno']),
			'ccexp'     		=> sanitize_text_field($_POST['merchantone_expmonth' ]).sanitize_text_field($_POST['merchantone_expyear' ]) ,
			'amount'    		=> number_format($wc_order->order_total,2,".",""),
			'cvv'       		=> sanitize_text_field($_POST['merchantone_cardcvv']) ,
			// Order Information
			'ipaddress' 		=> $this->get_client_ip(),
			'orderid'   		=> $wc_order->get_order_number() ,
			'orderdescription' 	=> get_bloginfo('blogname').' Order #'.$wc_order->get_order_number() ,
			'tax'       		=> number_format($wc_order->get_total_tax(),2,".","") ,
			'shipping'  		=> number_format($wc_order->get_total_shipping(),2,".","") ,
			'ponumber'  		=> $wc_order->get_order_number() , 
			// Billing Information
			'firstname'         => $wc_order->billing_first_name , 
			'lastname'          => $wc_order->billing_last_name ,
			'company'           => $wc_order->billing_company ,
			'address1'          => $wc_order->billing_address_1 ,
			'address2'          => $wc_order->billing_address_2 ,
			'city'              => $wc_order->billing_city ,
			'state'             => $wc_order->billing_state ,
			'zip'               => $wc_order->billing_postcode ,
			'country'           => $wc_order->billing_country ,
			'phone'             => $wc_order->billing_phone ,
			'fax'               => $wc_order->billing_phone ,
			'email'             => $wc_order->billing_email,
			'website'           => get_bloginfo('url'),
			// Shipping Information
			'shipping_firstname'=> $wc_order->shipping_first_name ,
			'shipping_lastname' => $wc_order->shipping_last_name,
			'shipping_company'  => $wc_order->shipping_company,
			'shipping_address1' => $wc_order->shipping_address_1,
			'shipping_address2' => $wc_order->shipping_address_2,
			'shipping_city'     => $wc_order->shipping_city,
			'shipping_state'    => $wc_order->shipping_state,
			'shipping_zip'      => $wc_order->shipping_postcode ,
			'shipping_country'  => $wc_order->shipping_country ,
			'shipping_email'    => $wc_order->shipping_email ,
			'type'              => 'sale' );
			return $merchantone_args ; 
		}

		
		
		
		/*Start of payment functions field*/
		public function payment_fields()
		{	
		?>
		<table>
		    <tr>
		    	<td><label for="merchantone_cardno"><?php echo __( 'Card No.', 'woocommerce') ?></label></td>
			<td><input type="text" name="merchantone_cardno" class="input-text" placeholder="Credit Card No"  /></td>
		    </tr>
		    <tr>
		    	<td><label for="merchantone_expiration_date"><?php echo __( 'Expiration Date', 'woocommerce') ?>.</label></td>
			<td>
			   <select name="merchantone_expmonth">
			      <option value=""><?php _e( 'Month', 'woocommerce' ) ?></option>
			      <option value='01'>01</option>
			      <option value='02'>02</option>
			      <option value='03'>03</option>
			      <option value='04'>04</option>
			      <option value='05'>05</option>
			      <option value='06'>06</option>
			      <option value='07'>07</option>
			      <option value='08'>08</option>
			      <option value='09'>09</option>
			      <option value='10'>10</option>
			      <option value='11'>11</option>
			      <option value='12'>12</option>  
			    </select>
			    <select name="merchantone_expyear">
			      <option value=""><?php _e( 'Year', 'woocommerce' ) ?></option>
			      <?php
			      $years = array();
			      for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) 
			      {
					printf( '<option value="20%u">20%u</option>', $i, $i );
			      } 
			      ?>
			    </select>
			</td>
		    </tr>
		    <tr>
		    	<td><label for="merchantone_cardcvv"><?php echo __( 'Card CVC', 'woocommerce') ?></label></td>
			<td><input type="text" name="merchantone_cardcvv" class="input-text" placeholder="CVC" /></td>
		    </tr>
		</table>
	        <?php  
		} // end of public function payment_fields()
		
		/*Payment Processing Fields*/
		public function process_payment($order_id)
		{
		
			global $woocommerce;
         		$wc_order = new WC_Order($order_id);
         		
			$cardtype = $this->get_card_type(sanitize_text_field($_POST['merchantone_cardno']));
			
         		if(!in_array($cardtype ,$this->merchantone_cardtypes ))
         		{
         			wc_add_notice('Merchant do not support accepting in '.$cardtype,  $notice_type = 'error' );
         			return array (
								'result'   => 'success',
								'redirect' => WC()->cart->get_checkout_url(),
							   );
				die;
         		}
         
		
			$gatewayurl = $this->merchantone_liveurl;
			
			
			$params = $this->merchantone_params($wc_order);
         

			$post_string = '';
			foreach( $params as $key => $value )
			{ 
			  $post_string .= urlencode( $key )."=".urlencode($value )."&"; 
			}
			$post_string = rtrim($post_string,"&");
			
		

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $gatewayurl);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 45);
			curl_setopt($ch, CURLOPT_TIMEOUT, 70);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
			curl_setopt($ch, CURLOPT_POST, 1);

			if (!($data = curl_exec($ch))) {
				return ERROR;
			}
			curl_close($ch);
			unset($ch);
			
			
 			$data = explode("&",$data);
 			
			for($i=0;$i<count($data);$i++) 
			{
				$rsponsedata = explode("=",$data[$i]);
				$response_array[$rsponsedata[0]] = $rsponsedata[1];
				
			}
 			


		if ( count($response_array) > 1 )
		{
			 if( 100 == $response_array['response_code'] )
			{
			$wc_order->add_order_note( __( $response_array['responsetext']. 'on '.date("d-m-Y h:i:s e").' with Transaction ID = '.$response_array['transactionid'].' using '.$response_array['type'].', Authorization Code ='.$response_array['authcode'].',Response Code ='.$response_array['response_code'].', Response ='.$response_array['response'],  'woocommerce' ) );
			
			$wc_order->payment_complete($response_array['transactionid']);
			WC()->cart->empty_cart();
			return array (
						'result'   => 'success',
						'redirect' => $this->get_return_url( $wc_order ),
					   );
			}
			else 
			{
				$wc_order->add_order_note( __( 'Merchant One payment failed.'.$response_array['responsetext'].', Response Code'.$response_array['response_code'].', Response ='.$response_array['response'],  'woocommerce' ) );
				wc_add_notice('Error Processing Merchant One Payments', $notice_type = 'error' );
			}
		}
		else 
		{
			$wc_order->add_order_note( __( 'Merchant One payment failed.'.$response_array['responsetext'].', Response Code'.$response_array['response_code'].', Response ='.$response_array['response'], 'woocommerce' ) );
			wc_add_notice('Error Processing Merchant One Payments', $notice_type = 'error' );
		}
        
		}// End of process_payment
		
		
		}// End of class WC_Authorizenet_merchantone_Gateway
	} // End if WC_Payment_Gateway
}// End of function authorizenet_merchantone_init

add_action( 'plugins_loaded', 'merchantone_init' );

function merchantone_addon_activate() {

	if(!function_exists('curl_exec'))
	{
		 wp_die( '<pre>This plugin requires PHP CURL library installled in order to be activated </pre>' );
	}
}
register_activation_hook( __FILE__, 'merchantone_addon_activate' );
