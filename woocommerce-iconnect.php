<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Plugin Name: POS Printer for WooCommerce
 * Description: Description: This plugin extends on top of the features provided by the official WooCommerce one. It enables your WooCommerce web store with cloud printing capabilities via a handheld portable thermal printer. Perfect for businesses that need orders printed out on demand! Get your printer from www.printerco.net
 * Author: PrinterCo
 * Plugin Site: http://printerco.net/woocommerce
 * Version: 2.8.1
 * Tested up to: 6.5.3
 *
 */

if ( ! class_exists( 'WC_Iconnect_Api' ) ) :


class WC_Iconnect_Api {

	/**
	* Construct the plugin.
	*/
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	* Initialize the plugin.
	*/
	public function init() {
		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Integration' ) ) {
            //Include WordPress package
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
			// Include our integration class.
			include_once 'includes/class-wc-iconnect.php';
            include_once 'includes/printerco-email-editor.php';
            include_once 'includes/printerco-dokan.php';
            require_once 'includes/PrintercoEmailKey.php';
            require_once 'includes/PrintercoOptions.php';
            require_once 'includes/PrintercoRefund.php';
            require_once 'includes/PrintercoOrderCallback.php';
			require_once 'includes/Store-OpenClose.php';
			// Register the integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		} else {
			// throw an admin error if you like
		}
	}

	/**
	 * Add a new integration to WooCommerce.
	 */
	public function add_integration( $integrations ) {
		$integrations[] = 'WC_Iconnect';
        $integrations[] = 'PrintercoEmailEditor';
		$integrations[] = 'StoreOpenClose';										   
		return $integrations;
	}
}

$WC_Iconnect_Api = new WC_Iconnect_Api();

function iconnect_shortcode_handler( $atts, $content = null ) {
    $orderCallbackData = new PrintercoOrderCallback();
    if(isset( $_REQUEST['printer_id'])){        $printer_id     = sanitize_text_field( $_REQUEST['printer_id']); }else{$printer_id = ''; }
    if(isset( $_REQUEST['order_id'])){          $order_id       = sanitize_text_field( $_REQUEST['order_id']); }else{$order_id = ''; }
    if(isset( $_REQUEST['status'])){            $status         = sanitize_text_field( $_REQUEST['status']); }else{$status = ''; }
    if(isset( $_REQUEST['msg'])){               $msg            = sanitize_text_field( $_REQUEST['msg']); }else{$msg = ''; }
    if(isset( $_REQUEST['processing_time'])){   $processing_time = sanitize_text_field( $_REQUEST['processing_time']); }else{$processing_time = ''; }
    if(isset( $_REQUEST['delivery_time'])){     $delivery_time  = sanitize_text_field( $_REQUEST['delivery_time']); }else{$delivery_time = ''; }
    if(isset( $_REQUEST['manual_update'])){     $manual_update  = sanitize_text_field( $_REQUEST['manual_update']); }else{$manual_update = ''; }
    if(isset( $_REQUEST['order_type'])){        $order_type     = sanitize_text_field( $_REQUEST['order_type']); }else{$order_type = ''; }

    $orderCallbackData->printer_id      = $printer_id;
    $orderCallbackData->order_id        = $order_id;
    $orderCallbackData->order_status    = $status;
    $orderCallbackData->message         = $msg;
    $orderCallbackData->processing_time = $processing_time;
    $orderCallbackData->delivery_time   = $delivery_time;
    $orderCallbackData->manual_update   = $manual_update;
    $orderCallbackData->order_type      = $order_type;
	if($orderCallbackData->order_id){
	    if($orderCallbackData->order_type == OrderType::Reservation){
            $settings = get_printerco_email_settings();
            $emailData = reservation_order_handler($orderCallbackData, $settings);
            if ($settings[PrintercoOptions::RESERVATION_EMAIL] == "yes")
            {
                send_notification($emailData);
            }
        }else{
           $emailData = order_handler($orderCallbackData);
           // if ($settings[PrintercoOptions::ORDER_SEND_EMAIL] == "yes")
           //{
                send_notification($emailData);
           // }
           
        }
	}

	return "OK";
}
function send_notification($emailData){
    $body = $emailData['body'];
    $subject = $emailData['subject'];
    $recipient = $emailData['recipient'];
    $site_name = get_site_name();
    $admin_email = get_option( 'admin_email');
     if ($admin_email)
     {
        $body = str_replace("\r\n", "<br/>", $body);
        $headers = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "From: " . $site_name . " <" . $admin_email . ">";
        $headers[] = "Reply-To: " . $site_name . " <" . $admin_email . ">";
        $headers[] = "X-Mailer: PHP/" . phpversion();
         if ($recipient) 
        {
            wp_mail($recipient, $subject, $body, implode("\r\n", $headers));
            // wp_mail("dev.support@printerco.net", $subject, $body, implode("\r\n", $headers));
            // wp_mail("dev.support@printerco.net", 'test email shoot', 'It is test msg', implode("\r\n", $headers));
        }
    }
}
function get_site_name(){
    return $site_name = get_option('blogname', "Woocommerce store");
}
function get_replace_data($orderInfo){
    return array(
        PrintercoEmailKey::ORDER_ID_KEY => $orderInfo->order_id,
        PrintercoEmailKey::SITE_NAME_KEY => get_site_name(),
        PrintercoEmailKey::PROCESSING_TIME_KEY => $orderInfo->processing_time,
        PrintercoEmailKey::REJECTED_MESSAGE_KEY => $orderInfo->message);
}
function order_handler($orderInfo){
    $order = new WC_Order($orderInfo->order_id);
    $settings = get_printerco_settings($order);
    $order_data = $order->get_data();
    $customer_name = $order_data['billing']['first_name'] . " " . $order_data['billing']['last_name'];
    $replace = get_replace_data($orderInfo);
    $replace[PrintercoEmailKey::CUSTOMER_NAME_KEY] = $customer_name;
    $site_name = $replace[PrintercoEmailKey::SITE_NAME_KEY];
    $subject = "Order notification from ".$site_name;
    $body = '';
    if($orderInfo->order_status == 1){
        delete_post_meta($orderInfo->order_id, '_iconnect_notification_status');
        update_post_meta($orderInfo->order_id, '_iconnect_notification_status', '1' );
        if(!$orderInfo->manual_update){
            $requested_delivery_time = get_post_meta($orderInfo->order_id, '_iconnect_notification_agreed_time', true );
            if($requested_delivery_time) {
                $rdt = date('Y-m-d H:i:s',strtotime($requested_delivery_time));
            } else {
                $rdt = date('Y-m-d H:i:s',strtotime($order->order_date));
            }
            $agreed_time = get_agreed_time($orderInfo->delivery_time, $rdt);
            delete_post_meta($orderInfo->order_id, '_iconnect_notification_agreed_time');
            update_post_meta($orderInfo->order_id, '_iconnect_notification_agreed_time', strtotime($agreed_time) );
            delete_post_meta($orderInfo->order_id, '_iconnect_notification_msg');
            update_post_meta($orderInfo->order_id, '_iconnect_notification_msg', $orderInfo->message);
        }
        $order->update_status('completed', $orderInfo->message);
        $subject ="";
        if ($agreed_time != null){
            $agreed_time = date('d/m/Y H:i',strtotime($agreed_time));
        }
        $replace[PrintercoEmailKey::AGREED_TIME_KEY] = $agreed_time;
        $acceptSubOption =  $settings[PrintercoOptions::ACCEPT_SUBJECT];
        if (trim($acceptSubOption) == ""){
            $subject = "Your order (ID: ".$orderInfo->order_id.") has been accepted on ".$site_name;
        }else{
            $subject = replace_key($replace, $acceptSubOption);
        }
        $body ="";
        $acceptBodyOption =  $settings[PrintercoOptions::ACCEPT_BODY];
        if (trim($acceptBodyOption) != ""){
            $body = replace_key($replace, $acceptBodyOption);
        }
    } elseif($orderInfo->order_status == 2) {
        $subject = "";
        $body = "";
        delete_post_meta($orderInfo->order_id, '_iconnect_notification_status');
        update_post_meta( $orderInfo->order_id, '_iconnect_notification_status', '2' );
        $printercoRefund = new PrintercoRefund();
        $refund = $printercoRefund->Refund($settings, $order, $orderInfo->message);
        if (!$refund){
            $order->update_status('cancelled', $orderInfo->message);
        }
        $rejectSubOption = $settings[PrintercoOptions::REJECT_SUBJECT];
        if (trim($rejectSubOption) == ""){
            $subject = "Your order (ID: ".$orderInfo->order_id.") has been rejected on ".$site_name;
        }else{
            $subject = replace_key($replace, $rejectSubOption);
        }
        $rejectBodyOption = $settings[PrintercoOptions::REJECT_BODY];
        if (trim($rejectBodyOption) != ""){
            $body = replace_key($replace, $rejectBodyOption);
        }
    }
    $recipient = get_post_meta($orderInfo->order_id, '_billing_email', true );
    return array("subject" => $subject, "body" => $body, "recipient" => $recipient);
}
function reservation_order_handler($orderInfo, $settings){
    require_once(RTB_PLUGIN_DIR . '/includes/Booking.class.php');
    $order = new rtbBooking();
    $order->load_post($orderInfo->order_id);
    $replace = get_replace_data($orderInfo);
    $replace[PrintercoEmailKey::CUSTOMER_NAME_KEY] = $order->name;
    $site_name = $replace[PrintercoEmailKey::SITE_NAME_KEY];
    $body = '';
    if (!$orderInfo->manual_update) {
        $agreed_time = get_agreed_time($orderInfo->delivery_time, $order->date);
        $order->date = $agreed_time;
    }
    if ($agreed_time != null) {
        $agreed_time = date('d/m/Y H:i', strtotime($agreed_time));
    }
    if ($orderInfo->order_status == 1) {
        $order->post_status = 'confirmed';
        $order->insert_post_data();
        $replace[PrintercoEmailKey::AGREED_TIME_KEY] = $agreed_time;
        $acceptSubOption = $settings[PrintercoOptions::ACCEPT_RESERVATION_SUBJECT];
        if (trim($acceptSubOption) == "") {
            $subject = "Reservation ".$orderInfo->order_id." approved";
        } else {
            $subject = replace_key($replace, $acceptSubOption);
        }
        $body = "";
        $acceptBodyOption = $settings[PrintercoOptions::ACCEPT_RESERVATION_BODY];
        if (trim($acceptBodyOption) != "") {
            $body = replace_key($replace, $acceptBodyOption);
        }
    } else {
        $body = "";
        $order->post_status = 'closed';
        $order->insert_post_data();
        $rejectSubOption = $settings[PrintercoOptions::REJECT_RESERVATION_SUBJECT];
        if (trim($rejectSubOption) == "") {
            $subject ="Sorry! The reservation you have requested for ".$agreed_time." is not available.";
        } else {
            $subject = replace_key($replace, $rejectSubOption);
        }
        $rejectBodyOption = $settings[PrintercoOptions::REJECT_RESERVATION_BODY];
        if (trim($rejectBodyOption) != "") {
            $body = replace_key($replace, $rejectBodyOption);
        }
    }
    return array("subject" => $subject, "body" => $body, "recipient" => $order->email);
}
function get_agreed_time($delivery_time, $requested_delivery_time){
    $year = 0;
    $month = 0;
    $day = 0;
    $hour = 0;
    $min = 0;
    $full_date = 0;
    if ($delivery_time) {
        if (strlen($delivery_time) > 5) {
            $full_date = 1;
            $arr1 = explode(' ', $delivery_time);
            $arr2 = explode(':', $arr1[0]);
            $arr3 = explode('-', $arr1[1]);
            $hour = $arr2[0];
            $min = $arr2[1];
            $year = '20' . $arr3[2];
            $month = $arr3[1];
            $day = $arr3[0];
        } else {
            $arr1 = explode(':', $delivery_time);
            $hour = $arr1[0];
            $min = $arr1[1];
        }
    }
    $rdt_arr = explode(' ', $requested_delivery_time);
    if ($full_date) {
        $agreed_time = $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $min . ':00';
    } else {
        $agreed_time = $rdt_arr[0] . ' ' . $hour . ':' . $min . ':00';
    }
    return $agreed_time;
}
function get_dokan_settings($order){
    $printerco_dokan = new PrintercoDokan();
    $settings = null;
    if($printerco_dokan->is_dokan_activated()) {
        $items = $order->get_items();
        foreach($items as $item) {
            $vendor_id = get_post($item['product_id'])->post_author;
            $settings = $printerco_dokan->get_vendor_printer_options($vendor_id);
            break;
        }
    }
    return $settings;
}
function get_printerco_settings($order){
    $email_settings = new PrintercoEmailEditor();
    $settings = get_dokan_settings($order);
    if ($settings == null || count($settings) <= 0){
        $settings = array();
        $wc_iconnect = new WC_Iconnect();
        $wc_iconnect->init_seller_settings();
        $options_const = PrintercoOptions::getConstants();
        foreach ($options_const as $value){
            if ($wc_iconnect->get_option($value) != ""){
                $settings[$value] =  $wc_iconnect->get_option($value);
            }
            else{
                $settings[$value] =  $email_settings->get_option($value);
            }
        }
    }
    return $settings;
}
function get_printerco_email_settings(){
    $email_settings = new PrintercoEmailEditor();
    $options_const = PrintercoOptions::getConstants();
    $settings = array();
    foreach ($options_const as $value){
        if ($email_settings->get_option($value) != ""){
            $settings[$value] = $email_settings->get_option($value);
        }
    }
    return $settings;
}
function replace_key($keys, $body){
    $search = array();
    $replace = array();
    foreach ($keys as $key => $val){
        array_push($search, $key);
        array_push($replace, $val);
    }
    $result = str_replace($search, $replace, $body);
    return $result;
}
add_shortcode( 'PrinterCoShortCode', 'iconnect_shortcode_handler' );
endif;