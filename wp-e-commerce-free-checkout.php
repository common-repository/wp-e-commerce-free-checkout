<?php
/**
  * Plugin Name: WP e-Commerce Free Checkout
  * Plugin URI: http://mywebsiteadvisor.com/tools/wordpress-plugins/wp-e-commerce-free-checkout/
  * Description: Allows purchase of products when the total price is $0.00 for WordPress e-Commerce
  * Version:  1.0.3
  * Author: MyWebsiteAdvisor
  * Author URI: http://MyWebsiteAdvisor.com/
  **/
  


class WP_E_Commerce_Free_Checkout {


	private $plugin_name = "";
  
  
	public function __construct(){
	
		$this->plugin_name = basename(dirname( __FILE__ ));
		
		// add links for plugin help, donations,...
		add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);


		//hook onto wpsc_submit_checkout
		add_action ('wpsc_submit_checkout', array(&$this, 'wp_ec_free_checkout')); 
		
		
		// hook onto wpsc_inside_shopping_cart
		add_action('wpsc_inside_shopping_cart', array(&$this, 'wp_ec_free_checkout_hide_payment_form') );
	}
	
	
	
	
	
	/**
	 * Add links on installed plugin list
	 */
	public function add_plugin_links($links, $file) {
		if($file == plugin_basename( __FILE__ )) {
			$upgrade_url = 'http://mywebsiteadvisor.com/tools/wordpress-plugins/' . $this->plugin_name . '/';
			$links[] = '<a href="'.$upgrade_url.'" target="_blank" title="Click Here to Upgrade this Plugin!">Upgrade Plugin</a>';
			
			$install_url = admin_url()."plugins.php?page=MyWebsiteAdvisor";
			$links[] = '<a href="'.$install_url.'" target="_blank" title="Click Here to Install More Free Plugins!">More Plugins</a>';
			
			$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . $this->plugin_name . '?rate=5#postform';
			$links[] = '<a href="'.$rate_url.'" target="_blank" title="Click Here to Rate and Review this Plugin on WordPress.org">Rate This Plugin</a>';
		}
		
		return $links;
	}
	
	
	
	// use css to hide the default payment form fields table row
	// create a new table row that says free checkout
	
	function wp_ec_free_checkout_hide_payment_form(){
		
		global $wpsc_cart;
		
		if ($wpsc_cart->calculate_total_price() == '0') {
			
			echo '<style>table.wpsc_checkout_table tr td.wpsc_gateway_container{ display: none; }</style>';
			echo "<tr><td colspan='2'><h3><strong>$free_checkout_message</strong></h3></td></tr>";		
							
		}
		
	}
	
	

	
	// test for $0.00 checkout
	// use test gateway to mark as payment accepnted
	
	function wp_ec_free_checkout($purchase_info) {   
	
		global $wpdb, $wpsc_cart, $wpsc_shipping_modules;   
		
		$purchase_log_id = $purchase_info['purchase_log_id'];   
		$customer_id = $purchase_info['our_user_id'];   
		
		$query = "SELECT * FROM ".WPSC_TABLE_PURCHASE_LOGS." WHERE id = ".$purchase_log_id." LIMIT 1";
		$purchase_log = $wpdb->get_row($query, ARRAY_A) ;    
		
		
		// use test gateway for $0.00 transactions   
		if( '0.00' == $purchase_log['totalprice'] )  {
			   
			$sessionid = $purchase_log['sessionid'];     
			
			$free_checkout = new wpsc_merchant_testmode( $purchase_log_id );     
			
			//fixes issue with paypal payments express session id
			//without this fix the transaction results page is blank for paypal express checkouts
			wpsc_update_customer_meta( 'selected_gateway' , 'Free Checkout', $customer_id);
			
			$free_checkout->construct_value_array();     
			$free_checkout->set_purchase_processed_by_purchid(3);     
			$free_checkout->go_to_transaction_results($sessionid);   
		
			die();
		} 
	}





}


$wp_e_commerce_free_checkout = new WP_E_Commerce_Free_Checkout;




?>