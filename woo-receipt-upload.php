<?php
/**
 * Plugin Name: Woo Receipt Upload
 * Plugin URI: https://ainaarawaida.com
 * Description: Receipt Upload payment method for Woocommerce
 * Author: Luqman Nordin
 * Author URI: http://ainaarawaida.com
 * Version: 2.1.0
 * Text Domain: woo-receipt-upload
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2019 Luqman Nordin
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   woo-receipt-upload
 * @author    Luqman
 * @category  Admin
 * @copyright Copyright (c) 2019 Luqman Nordin, Inc. and WooCommerce
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * This receipt upload payment method for the WooCommerce create another offline payment method.
 */
 
defined( 'ABSPATH' ) or exit;


// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}


/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */
function wc_offline_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Luq_Receipt_Upload';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_offline_add_to_gateways' );


/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_offline_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=offline_gateway' ) . '">' . __( 'Configure', 'WC-ReceiptUpload-Offline' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_offline_gateway_plugin_links' );


/**
 * Offline Payment Gateway
 *
 * Provides an Offline Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_Luq_Receipt_Upload
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Luqman Nordin
 */
add_action( 'plugins_loaded', 'wc_offline_gateway_init', 11 );

function wc_offline_gateway_init() {

	class WC_Luq_Receipt_Upload extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'offline_gateway';
			$this->icon               = apply_filters('woocommerce_offline_icon', '');
			$this->has_fields         = false;
			$this->method_title       = __( 'Receipt Upload', 'WC-ReceiptUpload-Offline' );
			$this->method_description = __( 'Allows payments by Upload Receipt.Orders are marked as "on-hold" when received.', 'WC-ReceiptUpload-Offline' );
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
		  
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}
	
	
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'wc_offline_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'WC-ReceiptUpload-Offline' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Payment', 'WC-ReceiptUpload-Offline' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'WC-ReceiptUpload-Offline' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'WC-ReceiptUpload-Offline' ),
					'default'     => __( 'Receipt Upload Payment', 'WC-ReceiptUpload-Offline' ),
					'desc_tip'    => true,
				),
				
				'description' => array(
					'title'       => __( 'Description', 'WC-ReceiptUpload-Offline' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'WC-ReceiptUpload-Offline' ),
					'default'     => __( 'Please pay in Bank Account Name || Bank Account No || Name Bank Account and upload your receipt.', 'WC-ReceiptUpload-Offline' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'WC-ReceiptUpload-Offline' ),
					'type'        => 'textarea',
					'description' => __( 'Your payment on-hold until We check your receipt and our account.', 'WC-ReceiptUpload-Offline' ),
					'default'     => __( 'Your payment on-hold until We check your receipt and our account.', 'WC-ReceiptUpload-Offline' ),
					'desc_tip'    => true,
				),
			) );
		}
	
	/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
// ok, let's display some description before the payment form
	if ( $this->description ) {
		// you can instructions for test mode, I mean test card numbers etc.
		// display the description with <p> tags etc.
		echo wpautop( wp_kses_post( $this->description ) );
	}
 
	
 
	echo '<input id="myFiles" type="file" name="luqfileuploadname"/>';
	
	echo '<div id="luquploadfield" class=""><br><br></div>';
	
	?>
	
	<style>
	.loading{
		background: url("https://image.flaticon.com/icons/png/128/189/189792.png");
		
	}
	</style>
	
	
	<?php 

		}
 
	
	public function validate_fields() {
			if(isset($_POST['luqfileuploadname'])) {
				
			if(isset($_POST[ 'luqfileuploadname' ]) && empty( $_POST[ 'luqfileuploadname' ]) ) {
				wc_add_notice(  'Receipt is required!', 'error' );
				return false;
			}
			return true;
		}else{
			wc_add_notice(  'Receipt is required!', 'error' );
				return false;
		}
 
	}
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}
	
	
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
	
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	
			$order = wc_get_order( $order_id );
			
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting offline payment', 'WC-ReceiptUpload-Offline' ) );
			
			// Reduce stock levels
			$order->reduce_order_stock();
			
			// Remove cart
			WC()->cart->empty_cart();
			
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}
	
  } // end \WC_Luq_Receipt_Upload class
}

add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted ){ 
				
if(isset($_POST['luqfileuploadname'])){
		$custom_fields = $_POST ; 
		foreach ( $custom_fields as $field_name => $val) {
			if ( $field_name == 'luqfileuploadname' ) {
				$meta_key = $field_name;
				$field_value = $val; // WC will handle sanitation
				update_post_meta( $order_id, $meta_key , $field_value ); 
			}
		}
		
		
				$file = unserialize(urldecode($_POST['luqfileuploadname']));
				
				if ( ! function_exists( 'wp_handle_upload' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}
				$get_dir = dirname($file['file']) ; 
				$get_filename = basename($file['file']) ; 
				$strname = explode('xxx_', $file['file']);
				//print_r($strname);
				rename($file['file'], $get_dir.'/'.$order_id.'xxx_'.$strname['3']);
				


	}

} , 10, 2);






//end


function so_enqueue_scripts() {

	wp_enqueue_script( 
        'ajaxHandlex', 
        plugins_url('luqman.js', __FILE__), 
        array('jquery'), 
        '1.1', 
        true 
);
     wp_localize_script( 'ajaxHandlex', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));


}
add_action('wp_enqueue_scripts', 'so_enqueue_scripts');

add_action( "wp_ajax_myaction", "so_wp_ajax_function" );
add_action( "wp_ajax_nopriv_myaction", "so_wp_ajax_function" );
function so_wp_ajax_function(){
  //DO whatever you want with data posted
  //To send back a response you have to echo the result!
 $_SESSION['token'] = uniqid();
  if(isset($_FILES['file'])){
	   $name = $_FILES['file']['name'] ;
	   $type = $_FILES['file']['type'] ;
	   $url = ($_FILES['file']['tmp_name']) ;
	   $error = $_FILES['file']['error'] ;
	   $size = ($_FILES['file']['size']) ;
	   $_FILES['file'] = array( 'name' => 'xxx_tempxxx_'.$_SESSION["token"].'xxx_'.$name, 'type' => $type, 'tmp_name' => $url, 'error' => $error, 'size' => $size);
	  
	   $filesdetail= $_FILES['file'] ;
	   if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		
		$file_return = wp_handle_upload($filesdetail,array('test_form' => false,
    'mimes' => get_allowed_mime_types()));
		$file_return['token'] = $_SESSION['token'] ; 
	
	  if(isset($file_return['error'])){
		echo ($file_return['error']); 
echo('<input type="hidden" name="luqfileuploadname" value="">');		
	  }else{
	  echo ('<a target="_blank" href="'.$file_return['url'].'">'.$name.'</a><input type="hidden" name="luqfileuploadname" value="'.urlencode(serialize($file_return)).'">');
	  }
	  
	  wp_die(); // ajax call must die to avoid trailing 0 in your response
  }
	  
   wp_die();
  
  
}



//admin order menu start

// Add your custom action buttons
add_action( 'woocommerce_admin_order_actions_end', 'admin_order_actions_custom_button' );
function admin_order_actions_custom_button( $order ) {

    // create some tooltip text to show on hover
    $tooltip = __('Receipt', 'receipt');

    // create a button label
    $label = __('Receipt', 'receipt');

    // get order line items
    $order_items = $order->get_items();

    // get the first item
    $first_item  = reset( $order_items );
$metaitem = get_post_meta($order->get_id());
    // get 'street-name' order item custom meta data
 
	if(isset($metaitem['luqfileuploadname'])){
		
		$luqitem = unserialize(urldecode($metaitem['luqfileuploadname'][0]));
		$strname2 = explode('xxx_', $luqitem['url'] );
		echo '<a class="button tips custom-class wc-action-button-receipt" href="'.$strname2['0'].$order->get_id()."xxx_".$strname2['3'].'" data-tip="'.$tooltip.'" target="_blank">'.$label.'</a>';
	}
}

// Set Here the WooCommerce icon for your action button
add_action( 'admin_head', 'add_custom_print_actions_buttons_css' );
function add_custom_print_actions_buttons_css() {
    $slug_icons = array(
        'receipt'       => '\f497'
    );
    // Added Woocommerce compatibility version
    $class  = version_compare( WC_VERSION, '3.3', '<' ) ? '.view.' : '.wc-action-button-';

    echo '<style>';

    foreach ( $slug_icons as $slug => $icon_code )
        echo $class.$slug.'::after { font-family: dashicons !important; content: "'.$icon_code.'" !important; font-size:1.4em !important; margin-top: -4px !important; }';

    echo '</style>';
}


// create a scheduled event (if it does not exist already)
function cronstarter_activationluq() {
	if( !wp_next_scheduled( 'mycronjob' ) ) {  
	   wp_schedule_event( time(), 'daily', 'mycronjob' );  
	}
}
// and make sure it's called whenever WordPress loads
add_action('wp', 'cronstarter_activationluq');

// unschedule event upon plugin deactivation
function cronstarter_deactivateluq() {	
	// find out when the last event was scheduled
	$timestamp = wp_next_scheduled ('mycronjob');
	// unschedule previous event if any
	wp_unschedule_event ($timestamp, 'mycronjob');
} 
register_deactivation_hook (__FILE__, 'cronstarter_deactivateluq');

// here's the function we'd like to call with our cron job
function my_repeat_functionluq() {
	
	// do here what needs to be done automatically as per your schedule
	// in this example we're sending an email
	$orders = wc_get_orders(array('limit' => -1,'return' => 'ids'));
	$allorderidarray = implode(',',$orders) ;
	$path    = wp_get_upload_dir()['path'];
	$allwantdeletefiles = scandir($path);
	foreach($allwantdeletefiles AS $val){
		if(substr($val, 0, 12) == 'xxx_tempxxx_'){ //jika temp file yg ditinggalkan
			$filelastmodified = filemtime($path ."/". $val);
			 if((time() - $filelastmodified) > 300)
			{ 
			   unlink($path ."/". $val);
			}
		}elseif(substr($val, 3, 4) == 'xxx_'){ //cari order file
			$getfileid = explode('xxx_', $val);
			if(!in_array($getfileid[0], $orders)){ //jika order dah delete
			
				 unlink($path ."/". $val);
				
			}
			
		}
		
	}

	
	
	
}
// hook that function onto our scheduled event:
add_action ('mycronjob', 'my_repeat_functionluq'); 







// Adding Meta container admin shop_order pages
add_action( 'add_meta_boxes', 'luq_receipt_upload_add_meta_boxes' );
if ( ! function_exists( 'luq_receipt_upload_add_meta_boxes' ) )
{
    function luq_receipt_upload_add_meta_boxes()
    {
        add_meta_box( 'luq_receipt_upload_add_meta_boxes', __('Receipt Upload','woocommerce'), 'mv_add_other_fields_luq_receipt_upload', 'shop_order', 'side', 'core' );
    }
}

// Adding Meta field in the meta container admin shop_order pages
if ( ! function_exists( 'mv_add_other_fields_luq_receipt_upload' ) )
{
    function mv_add_other_fields_luq_receipt_upload()
    {
        global $post;

		$tooltip = __('Receipt', 'receipt');

		// create a button label
		$label = __('Receipt', 'receipt');

	
		$meta_field_data = get_post_meta( $post->ID, 'luqfileuploadname', true) ? get_post_meta( $post->ID, 'luqfileuploadname', true ) : '';
		$luqitem = unserialize(urldecode($meta_field_data));
		$strname2 = explode('xxx_', $luqitem['url'] );
echo '<a class="button tips custom-class wc-action-button-receipt" href="'.$strname2['0'].$post->ID."xxx_".$strname2['3'].'" data-tip="'.$tooltip.'" target="_blank">'.$label.'</a>';
	
    }
}

// Save the data of the Meta field
add_action( 'save_post', 'mv_save_wc_order_other_fields', 10, 1 );
if ( ! function_exists( 'mv_save_wc_order_other_fields' ) )
{

    function mv_save_wc_order_other_fields( $post_id ) {

        // We need to verify this with the proper authorization (security stuff).

        // Check if our nonce is set.
        if ( ! isset( $_POST[ 'mv_other_meta_field_nonce' ] ) ) {
            return $post_id;
        }
        $nonce = $_REQUEST[ 'mv_other_meta_field_nonce' ];

        //Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce ) ) {
            return $post_id;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

		
// Check the user's permissions.
        if ( 'page' == $_POST[ 'post_type' ] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }
        // --- Its safe for us to save the data ! --- //

        // Sanitize user input  and update the meta field in the database.
        update_post_meta( $post_id, '_my_field_slug', $_POST[ 'my_field_name' ] );
    }
}




//frontend my account
/**
 * @snippet       WooCommerce Add New Tab @ My Account
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 3.5.7
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */
  
// ------------------
// 1. Register new endpoint to use for My Account page
// Note: Resave Permalinks or it will give 404 error
  
function bbloomer_add_premium_support_endpoint() {
    add_rewrite_endpoint( 'luq-receipt-upload', EP_ROOT | EP_PAGES );
}
  
add_action( 'init', 'bbloomer_add_premium_support_endpoint' );
  
  
// ------------------
// 3. Insert the new endpoint into the My Account menu
  
function bbloomer_add_premium_support_link_my_account( $items ) {
	unset($items['customer-logout']);
    $items['luq-receipt-upload'] = 'Receipt Upload';
	$items['customer-logout'] = 'Logout';
	
    return $items;
}
  
add_filter( 'woocommerce_account_menu_items', 'bbloomer_add_premium_support_link_my_account', 0,1 );
  
  
// ------------------
// 4. Add content to the new endpoint
  
function bbloomer_premium_support_content($data) {
	global $post;

		$tooltip = __('Receipt', 'receipt');

		// create a button label
		$label = __('Receipt', 'receipt');

	
## ==> Define HERE the statuses of that orders 
$order_statuses = array('wc-on-hold', 'wc-processing', 'wc-completed');

## ==> Define HERE the customer ID
$customer_user_id = get_current_user_id(); // current user ID here for example

// Getting current customer orders
$customer_orders = wc_get_orders( array(
    'meta_key' => '_customer_user',
    'meta_value' => $customer_user_id,
    'post_status' => $order_statuses,
    'numberposts' => -1
) );

echo '<h3>Your Receipt Upload</h3>';

// Loop through each customer WC_Order objects
	foreach($customer_orders as $order ){

		// Order ID (added WooCommerce 3+ compatibility)
		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		
		$meta_field_data = get_post_meta( $order_id, 'luqfileuploadname', true) ? get_post_meta( $order_id, 'luqfileuploadname', true ) : '';
		if($meta_field_data){
				$luqitem = unserialize(urldecode($meta_field_data));
				$strname2 = explode('xxx_', $luqitem['url'] );
			echo '<a class="button tips custom-class wc-action-button-receipt" href="'.$strname2['0'].$order_id."xxx_".$strname2['3'].'" data-tip="'.$tooltip.'" target="_blank">'.$label.'</a>';
		}else{
			echo "No receipt Upload" ;
 		}
	  
	} 
}
  
add_action( 'woocommerce_account_luq-receipt-upload_endpoint', 'bbloomer_premium_support_content',10, 1 ); 
// Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format