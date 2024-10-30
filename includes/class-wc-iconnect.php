<?php
/**
 * PrinterCo API Integration.
 *
 * @package  WC_Iconnect
 * @category Integration
 * @author   PrinterCo
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/printerco-orders.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/printerco-settings.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/IconnectValidation.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/printerco-dokan.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/printerco-plugins-support.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Store-OpenClose.php';

if ( ! class_exists( 'WC_Iconnect' ) ) :



class WC_Iconnect extends WC_Integration {

    use IconnectValidation;

    private $api_settings;
    private $item_arr;
    private $vendor_id;
    private $items;
    private $line_items_fee = [];
    private $requested_delivery_time2;

    const ICONNECT_DOMAIN = 'http://mypanel.printerco.net/';
    
    const ICONNECT_RESERVATION_ORDER_URL = self::ICONNECT_DOMAIN . 'api/submitreservationext';
    const ICONNECT_SEND_ORDER_URL =  self::ICONNECT_DOMAIN . 'api/submitorderext';

    const SUBMITTED_TO_ICONNECT_STATUS = 1;

    const ICONNECT_NOTIFY_STATUS_ACCEPTED = 1;
    const ICONNECT_NOTIFY_STATUS_REJECTED = 2;

    const PRINTERCO_NOTIFY_PAGE_URl = "/printerco_notify";
    const PRINTERCO_NOTIFY_PAGE_TITLE = "Printerco Notification";
    const PRINTERCO_NOTIFY_PAGE_NAME = "printerco_notify";
    const PRINTERCO_SHORTCODE = "[PrinterCoShortCode]";

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		global $woocommerce;

		$this->id                 = 'iconnect-api-integration';
		$this->method_title       = __( 'PrinterCo API Integration', 'woocommerce-iconnect-api-integration' );
		$this->method_description = __( 'PrinterCo API Integration for Woocommerce store', 'woocommerce-iconnect-api-integration' );

		$this->order = null;

		// Load the settings.
		$this->init_settings();
		$this->init_notification_page();
		// Define user set variables.
		$this->api_key          = $this->get_option( 'api_key' );
		$this->api_password     = $this->get_option( 'api_password' );
		$this->printer_id     	= $this->get_option( 'printer_id' );

        $this->notify_url     	= $this->get_option( 'notify_url' );
		
		$this->receipt_header  	= $this->get_option( 'receipt_header' );
		$this->receipt_footer   = $this->get_option( 'receipt_footer' );

		// Actions.
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );

        add_action( 'woocommerce_order_status_processing', array( $this, 'woocommerce_send_order_to_iconnect' ) );

        add_action ( 'rtb_insert_booking' , array( $this, 'booking_send_reservation_to_iconnect' ) );
		
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'admin_show_iconnect_info_in_order_details' ) );

		add_action('redi-reservation-email-content',  array( $this, 'booking_send_reservation_to_iconnect' ) );

		add_filter( 'woocommerce_settings_api_form_fields_'. $this->id, array( $this, 'init_form_fields') );

		$this->printerco_dokan = new PrintercoDokan();
	}

    function get_notify_page(){
        $page = get_page_by_path(self::PRINTERCO_NOTIFY_PAGE_URl);
        return get_page_link($page);
    }

    function init_notification_page()
    {
        $page = get_page_by_path(self::PRINTERCO_NOTIFY_PAGE_URl);
        if ($page == null){
            $PageGuid = site_url() . self::PRINTERCO_NOTIFY_PAGE_URl;
            $my_post  = array( 'post_title'     => self::PRINTERCO_NOTIFY_PAGE_TITLE,
                'post_type'      => 'page',
                'post_name'      => self::PRINTERCO_NOTIFY_PAGE_NAME,
                'post_content'   => self::PRINTERCO_SHORTCODE,
                'post_status'    => 'publish',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
                'post_author'    => 1,
                'menu_order'     => 0,
                'guid'           => $PageGuid );
             wp_insert_post( $my_post, FALSE );
        }

    }

	public function booking_send_reservation_to_iconnect($reservation){
	    $reservationOrder = new  PrintercoReservationOrder();

        $post_data = array();
        if ($_POST['action'] == "redi_restaurant-submit"){

            $data = $this->get_post_data();
            $user_id = get_post_meta($reservation->id, '_customer_user', true);
            if ($user_id) {

                $reservationOrder->isVarified = 4;
            } else {
                $reservationOrder->isVarified = 5;
            }
            $reservationOrder->order_id = esc_html($reservation["id"]);
            $reservationOrder->reservation_time = esc_html($data['startTime'].":00");
            $reservationOrder->number_of_people = esc_html($data['persons']);
            $reservationOrder->reservation_email = esc_html($data['UserEmail']);
            $reservationOrder->cust_phone = esc_html($data['UserPhone']);
            $reservationOrder->customer_info = esc_html($data['UserName']);
            $reservationOrder->cust_comment = esc_html($data['UserComments']);
        }else {
            $user_id = get_post_meta($reservation->ID, '_customer_user', true);
            if ($user_id) {
                $reservationOrder->isVarified = 4;
            } else {
                $reservationOrder->isVarified = 5;
            }
            $reservationOrder->order_id = esc_html($reservation->ID);
            $reservationOrder->reservation_time = esc_html($reservation->date);
            $reservationOrder->number_of_people = esc_html($reservation->party);
            $reservationOrder->reservation_email = esc_html($reservation->email);
            $reservationOrder->cust_phone = esc_html($reservation->phone);
            $reservationOrder->reservation_status = esc_html($reservation->post_status);
            $reservationOrder->customer_info = esc_html($reservation->name);
            $reservationOrder->cust_comment = esc_html($reservation->message);
        }
        $api_settings = array(
            'api_key'=>$this->get_option( 'iconnect_api_key' ),
            'api_password'=>$this->get_option( 'iconnect_api_password' ),
            'license_key'=>trim($this->get_option( 'iconnect_license_key' )),
            'printer_id'=>$this->get_option( 'iconnect_printer_id' ),
            'notify_url'=> $this->get_notify_url($this->get_option( 'iconnect_notify_url' )),
            'receipt_header'=>$this->get_option( 'iconnect_receipt_header' ),
            'receipt_footer'=>$this->get_option( 'iconnect_receipt_footer' ),
            'text_size'=>$this->get_option( 'iconnect_receipt_text_size' ),
            'prepaid_option'=>$this->get_option( 'iconnect_prepaid_payment_option' ),
            'all_include'=>$this->get_option( 'iconnect_all_include' ),
            'debug_mode'=>$this->get_option( 'iconnect_debug_mode' ),
            'debug_email'=>$this->get_option( 'iconnect_debug_email' )
        );
        $post_data['api_key'] = $api_settings['api_key'];
        $post_data['api_password'] = $api_settings['api_password'];
        $post_data['license_key'] = $api_settings['license_key'];
        $post_data['notify_url'] = $api_settings['notify_url'];
        $post_data['printer_id'] = $api_settings['printer_id'];
        $reservationOrder->order_type = OrderType::Reservation;
        $reservationOrder->order_time = date('H:i d-m-y');
        $post_data['receipt_header'] = $api_settings['receipt_header'];
        $post_data['receipt_footer'] = $api_settings['receipt_footer'];

        $reservationOrder =  apply_filters("before_send_reservation_order_to_iconnect", $reservationOrder);
        $reservationOrderArray = (array) $reservationOrder;
        $post_data += $reservationOrderArray;
        $response = $this->post_to_api(self::ICONNECT_RESERVATION_ORDER_URL,$post_data);
    }

	/**
	 * Initialize integration settings form fields.
	 */
	public function init_form_fields() {
	    return PrintercoSettings::get_settings();
	}

	private function init_default_api_settings() {
        $this->api_settings = array(
            'api_key' => $this->get_option('iconnect_api_key'),
            'api_password' => $this->get_option('iconnect_api_password'),
            'license_key' => trim($this->get_option('iconnect_license_key')),
            'printer_id' => $this->get_option('iconnect_printer_id'),
            'notify_url' => $this->get_option( 'iconnect_notify_url' ),
            'receipt_header' => $this->get_option('iconnect_receipt_header'),
            'receipt_footer' => $this->get_option('iconnect_receipt_footer'),
            'text_size' => $this->get_option('iconnect_receipt_text_size'),
            'prepaid_option' => $this->get_option('iconnect_prepaid_payment_option'),
            'delivery_option' => $this->get_option( 'iconnect_delivery_option' ),
            'all_include' => $this->get_option('iconnect_all_include'),
            'debug_mode' => $this->get_option('iconnect_debug_mode'),
            'debug_email' => $this->get_option('iconnect_debug_email'),
            'auto_refund' => $this->get_option('iconnect_auto_refund'),
            'automated_refund_services' => $this->get_option('iconnect_automated_refund_services'),
            'manual_refund_services' => $this->get_option('iconnect_manual_refund_services'),
            'manual_refund_order_status' => $this->get_option('iconnect_manual_refund_order_status')
        );

    }


    public function CompareBillingAndShipping($order){
       $difference = false;
       $billing = array();
       $billing['first_name'] = $order->get_billing_first_name();
       $billing['last_name'] = $order->get_billing_last_name();
       $billing['address_1'] = $order->get_billing_address_1();
       $billing['address_2'] = $order->get_billing_address_2();
       $billing['city'] = $order->get_billing_city();
       $billing['postcode'] = $order->get_billing_postcode();

       $shipping['first_name'] = $order->get_shipping_first_name();
       $shipping['last_name'] = $order->get_shipping_last_name();
       $shipping['address_1'] = $order->get_shipping_address_1();
       $shipping['address_2'] = $order->get_shipping_address_2();
       $shipping['city'] = $order->get_shipping_city();
       $shipping['postcode'] = $order->get_shipping_postcode();


       foreach ($billing as $key => $value ){
           if ($value != $shipping[$key] && trim($shipping[$key]) != "" ){
               $difference = true;
           }
           if ($difference){
               break;
           }
       }
       return $difference;
    }

    // IF PRINTER IS INACTIVE THEN ORDER WILL NOT CREATE
    public function check_printer_connection()
    {
        $params                        = array();
        $params['req_api_key']         = $this->get_option( 'iconnect_api_key' );
        $params['req_api_password']    = $this->get_option( 'iconnect_api_password' );
        $params['req_printer_id']      = $this->get_option( 'iconnect_printer_id' );
        if($params['req_api_key'] != "" && $params['req_api_password'] != "" && $params['req_printer_id'] != "" )
        {
        $response      = StoreOpenClose::find_shop_status($params);
        $xml           = simplexml_load_string($response);
        $printerStatus = (string)$xml->details->printer_status;
        if($printerStatus != 'active')
        {   
            return $printerStatus;
        }
        return $printerStatus;
        }
    }
	
    public function woocommerce_send_order_to_iconnect($order_id)
    {
		$printerStatus   =   "active";
        $this->order = new WC_Order($order_id);
        $printerStatus = $this->check_printer_connection();

        ob_start();
        echo '<pre>';
        print_r($this->order);


        if($this->printerco_dokan->is_dokan_activated()) {
            $parent_order = dokan()->order->get( $this->order->id );

            if($parent_order->get_meta( 'has_sub_order' ) == true) {
                return;
            }
        }

        $this->items = $this->order->get_items();

        $this->line_items_fee = $this->order->get_items('fee');

        print_r($this->items);

		if($printerStatus == "active")
        {
            $this->set_items_info();
            $this->order_processing();
        }
    }


    function init_seller_settings() {

        if($this->printerco_dokan->is_dokan_activated()) {
            $this->api_settings = $this->printerco_dokan->get_vendor_printer_options($this->vendor_id, false);
        } else {
            $this->init_default_api_settings();
        }

        $this->api_settings = apply_filters( 'printerco_api_settings', $this->api_settings, $this->vendor_id );
    }

    function order_processing() {
        $this->init_seller_settings();

        if (!empty($this->api_settings))
        {
            $post_data = $this->printerco_post_data_preparation();
            $this->send_info_to_printerco($this->order->id, $post_data);
        }
    }

    function send_info_to_printerco($order_id, $post_data) {
        $response = $this->post_to_api(self::ICONNECT_SEND_ORDER_URL, $post_data);

        print_r($response);
        //do your necessary things here based on the response status

        if($response['status']=='OK'){
            //order submitted successfully
            //echo 'OK';

            update_post_meta( $order_id, '_submitted_to_iconnect', '1' );
            update_post_meta( $order_id, '_iconnect_notification_status', '0' );
            update_post_meta( $order_id, '_iconnect_notification_msg', '' );
            update_post_meta( $order_id, '_iconnect_notification_agreed_time', $this->requested_delivery_time2 );
        }
        else{
            //order submition failed because of following reason
            /*echo '<pre>';
            print_r($response['error']);
            echo '</pre>';*/
            update_post_meta( $order_id, '_submitted_to_iconnect', '0' );
            update_post_meta( $order_id, '_iconnect_notification_status', '0' );
            update_post_meta( $order_id, '_iconnect_notification_msg', '' );
            update_post_meta( $order_id, '_iconnect_notification_agreed_time', $this->requested_delivery_time2 );

            if (!empty($response['error'])){
                update_post_meta($order_id, '_printerco_post_error', $response['error']);
            }
            if(!empty($response['curl_errors'])) {
                update_post_meta($order_id, '_iconnect_curl_error', $response['curl_errors']);
            }
        }


        echo '</pre>';


        $body = ob_get_clean();

        $subject = "wppizza debug log";

        $admin_email = get_option( 'admin_email', "info@woocommercestore.com" );

        $site_name = get_option( 'blogname', "Woocommerce store" );

        $headers   = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "From: ".$site_name." <".$admin_email.">";
        $headers[] = "Reply-To: ".$site_name." <".$admin_email.">";
        $headers[] = "X-Mailer: PHP/".phpversion();
        $to = $this->api_settings['debug_email'];
        if($this->api_settings['debug_mode']){
            wp_mail($to, $subject, $body, implode("\r\n", $headers) );

            $file = fopen("printercolog.txt","w");
            fwrite($file,$body);
            fclose($file);
        }
    }

    function printerco_post_data_preparation() {
        print_r($this->api_settings);

        $post_data = array();
        $post_data['api_key'] = $this->api_settings['api_key'];
        $post_data['api_password'] = $this->api_settings['api_password'];
        $post_data['license_key'] = $this->api_settings['license_key'];
        $post_data['notify_url'] = $this->get_notify_url($this->api_settings['notify_url']);

        $baseOrder = new  PrintercoBaseOrder();

        $baseOrder->order_type = $this->get_order_type();

        //$ship_to_different_address = isset($_POST['ship_to_different_address']) ? $_POST['ship_to_different_address'] : false;

        if(!$this->CompareBillingAndShipping($this->order) || $baseOrder->order_type != 1) {
            if($this->order->get_billing_first_name() || $this->order->get_billing_last_name()) {
                $baseOrder->cust_name = $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name();
            }
            if($this->order->get_billing_company()){
                $baseOrder->cust_address .= $this->order->get_billing_company() . ', ';
            }
            if($this->order->get_billing_address_1()){
                $baseOrder->cust_address .= $this->order->get_billing_address_1();
                if(!$this->order->get_billing_address_2()){
                    $baseOrder->cust_address .= ', ';
                }else{
                    $baseOrder->cust_address .= ' ';
                }
            }
            if($this->order->get_billing_address_2()){
                $baseOrder->cust_address .= $this->order->get_billing_address_2() . ', ';
            }
            if($this->order->get_billing_city()) {
                $baseOrder->cust_address .= $this->order->get_billing_city() . ', ';
            }
            if($this->order->get_billing_postcode()) {
                $baseOrder->cust_address .= $this->order->get_billing_postcode() . ', ';
            }
            //Temporary removed state and country
            /*if($order->get_billing_state()) {
                $post_data['cust_address'] .= $order->get_billing_state() . ', ';
            }
            if($order->get_billing_country()) {
                $post_data['cust_address'] .= $order->get_billing_country();
            }*/
        }else {
            if($this->order->get_shipping_first_name() || $this->order->get_shipping_last_name()) {
                $baseOrder->cust_name = $this->order->get_shipping_first_name() . ' ' . $this->order->get_shipping_last_name();
            }
            if($this->order->get_shipping_company()){
                $baseOrder->cust_address = $this->order->get_shipping_company() . ', ';
            }
            if($this->order->get_shipping_address_1()){
                $baseOrder->cust_address .= $this->order->get_shipping_address_1();
                if(!$this->order->get_shipping_address_2()){
                    $baseOrder->cust_address .= ', ';
                }else{
                    $baseOrder->cust_address .= ' ';
                }
            }
            if($this->order->get_shipping_address_2()){
                $baseOrder->cust_address .= $this->order->get_shipping_address_2() . ', ';
            }
            if($this->order->get_shipping_city()) {
                $baseOrder->cust_address .= $this->order->get_shipping_city() . ', ';
            }
            if($this->order->get_shipping_postcode()) {
                $baseOrder->cust_address .= $this->order->get_shipping_postcode() . ', ';
            }
            //Temporary removed state and country
            /*if($order->get_shipping_state()) {
                $post_data['cust_address'] .= $order->get_shipping_state() . ', ';
            }
            if($order->get_shipping_country()) {
                $post_data['cust_address'] .= $order->get_shipping_country();
            }*/
        }
        $baseOrder->cust_address = trim($baseOrder->cust_address, ', ');

        $baseOrder->deliverycost = $this->order->data['shipping_total']; // FOR EXCLUSIVE TAX CASE

// WORK FOR SHIPPING IN INCLUSIVE TAX - START 
// if (metadata_exists('post', $this->order->id, '_prices_include_tax'))
        {
            // $prices_include_tax = get_post_meta($this->order->id, '_prices_include_tax', true);
            //if($prices_include_tax == "yes")
           if( wc_prices_include_tax() ) // this function is used for finding it is tax inclusive or not
            {
                $shippingTxAmt = get_post_meta($this->order->id, "_order_shipping_tax", true);
                $shippingTxAmt = number_format($shippingTxAmt,2,'.','');
                $shippingTotal = $this->order->data['shipping_total'];
                $shippingTotal = number_format($shippingTotal,2,'.','');
                $baseOrder->deliverycost = $shippingTotal + $shippingTxAmt;
            }
        }


// WORK FOR SHIPPING IN INCLUSIVE TAX - END

        if($phone = $this->order->get_billing_phone()){
            $baseOrder->cust_phone = $phone;
        }

        if(trim( $baseOrder->cust_name) == ''){
            $baseOrder->cust_name = 'NA';
        }

        if(trim($baseOrder->cust_address) == ''){
            $baseOrder->cust_address = 'NA';
        }

        $baseOrder->cust_instruction = $this->order->get_customer_note();
        $rheader='';
        if($this->api_settings['text_size']=='small' || $this->api_settings['text_size']=='large'){
            $data1 = $this->api_settings['receipt_header'];

            if($this->api_settings['text_size']=='small'){
                $data2 = str_replace('/r','@@',$data1);

                $rheader = $data2;
            }
            else{
                $data2 = str_replace('@@','/r',$data1);
                $data3 = str_replace('/-','/r/-',$data2);
                $rheader = $data3.'/r';
            }
        }
        else{
            $rheader = $this->api_settings['receipt_header'];
        }
        $rfooter='';
        if($this->api_settings['text_size']=='small' || $this->api_settings['text_size']=='large'){
            $data1 = $this->api_settings['receipt_footer'];

            if($this->api_settings['text_size']=='small'){
                $data2 = str_replace('/r','@@',$data1);

                $rfooter = $data2;
            }
            else{
                $data2 = str_replace('@@','/r',$data1);
                $data3 = str_replace('/-','/r/-',$data2);
                $rfooter .= $data3.'/r';
            }
        }
        else{
            $rfooter = $this->api_settings['receipt_footer'];
        }

        $post_data['receipt_header'] = $rheader;
        $post_data['receipt_footer'] = $rfooter;
        $post_data['printer_id'] = $this->api_settings['printer_id'];

//        $baseOrder->order_id = $order_id;
        $baseOrder->order_id = $this->order->id;

        $baseOrder->currency = get_post_meta( $this->order->id, '_order_currency', true );
        if($this->order->currency)
        {
            $baseOrder->currency = $this->order->currency;
        }

        

        $baseOrder->payment_method = $paymentmethod1 = get_post_meta( $this->order->id, '_payment_method_title', true );
        $paymentmethod2 = get_post_meta( $this->order->id, '_payment_method', true );
        
// updated code - start (19th Apr 2024)
        if($this->order->payment_method_title)
        {
        $baseOrder->payment_method = $paymentmethod1  = $this->order->payment_method_title;    
        }
        
        if($this->order->payment_method)
        {
        $paymentmethod2 = $this->order->payment_method;    
        }
// updated code - end 
        echo 'Payment method: '.esc_html($paymentmethod1).':'.esc_html($paymentmethod2);

        $ptype1 = strtolower($paymentmethod1);
        $ptype2 = strtolower($paymentmethod2);

        $wc_order_status = $this->order->get_status();


        if (strtolower($wc_order_status) == 'processing' || strtolower($wc_order_status) == 'completed') {
            $baseOrder->payment_status = OrderPaymentStatus::NotPaid;
            foreach ($this->api_settings['prepaid_option'] as $pval) {
                if ($pval == $ptype1 || $pval == $ptype2) {
                    $baseOrder->payment_status = OrderPaymentStatus::Paid;
                    break;
                }
            }
        } else {

            $baseOrder->payment_status = OrderPaymentStatus::NotPaid;
        }

        $baseOrder->order_time = date('H:i d-m-y', strtotime(str_replace("/", "-", $this->order->order_date)));

        
        
// WORKED FOR 'ONLINE FOOD PREMIUM' PLUGIN - START  (20th APR 2023)
        if (metadata_exists('post', $this->order->id, 'fdoe_picked_time'))
        {
            $requested_delivery_time = $this->requested_delivery_time2= get_post_meta($this->order->id, 'fdoe_picked_time', true);
            $delTimeStatusTxt = "yes";

            if ((stripos($requested_delivery_time, 'As soon as possible') !== false )
                || (stripos($requested_delivery_time, 'snart som') !== false ))
            {
                $requested_delivery_time = $baseOrder->fdoe_picked_time_timestamp;
                $delTimeStatusTxt        = "no";
            } 
            else {
                $requested_delivery_time = strtotime($requested_delivery_time);
            }
        }
// IF BILLING TABLE EXIST
        if (metadata_exists('post', $this->order->id, '_billing_table'))
        {
            $billingTable = get_post_meta($this->order->id, '_billing_table', true);
            if ($billingTable) 
            {
                $baseOrder->table_number = $billingTable;
            }
        }

// WORKED FOR 'ONLINE FOOD PREMIUM' PLUGIN - END 


// WORKED FOR 'ORDERABLE' PLUGIN - START  
if (metadata_exists('post', $this->order->id, '_orderable_order_timestamp'))
{
    $requested_delivery_time = $this->requested_delivery_time2 = get_post_meta($this->order->id, '_orderable_order_timestamp', true);
}
// WORKED FOR 'ORDERABLE' PLUGIN - END    

// WORKED FOR 'WPCAFE' PLUGIN - START 
if (metadata_exists('post', $this->order->id, 'wpc_pro_delivery_time'))
{
    $wpcafeTime = get_post_meta($this->order->id, 'wpc_pro_delivery_time', true);
    $wpcafeDate = get_post_meta($this->order->id, 'wpc_pro_delivery_date', true);
    $aTime      = date("H:i", strtotime($wpcafeTime));
    $bDate      = date("Y-m-d", strtotime($wpcafeDate));
    $cDatetime  = strtotime($bDate." ".$aTime);
    $requested_delivery_time = $this->requested_delivery_time2 = $cDatetime;
}
// WORKED FOR 'WPCAFE' PLUGIN - END


        // $requested_delivery_time = $this->requested_delivery_time2 = get_post_meta($this->order->id, '_orddd_timeslot_timestamp', true);


        if ($requested_delivery_time) {
            $baseOrder->delivery_time = date('H:i d-m-y', $requested_delivery_time);
        }
        elseif (is_plugin_active(PrintercoPluginsSuport::WOOCOMMERCE_DELIVERY)){
            $date = date("d-m-y");
            $time = date("H:i");

            if (metadata_exists('post', $this->order->id, 'delivery_date')){
                $dateTime = new DateTime();
                $dateTime->setTimestamp(get_post_meta($this->order->id, 'delivery_date', true));
                $date = $dateTime->format("d-m-y");
            }
            if (metadata_exists('post', $this->order->id, 'delivery_time')){
                $time = get_post_meta($this->order->id, 'delivery_time', true);

                $time = str_replace(" ","" , trim($time));
                $time = str_replace(array("-", "."),":" , $time);
                $time = date("H:i", strtotime($time));
            }
            $baseOrder->delivery_time = $time . " " . $date;
        }
        else
        {
            if (class_exists('Coderockz_Woo_Delivery_Helper')) {
                $helper = new Coderockz_Woo_Delivery_Helper();//class-coderockz-woo-delivery-helper
                $timezone = $helper->get_the_timezone();
                date_default_timezone_set($timezone);
            }
            if (metadata_exists('post', $this->order->id, 'delivery_date') || (metadata_exists('post', $this->order->id, 'delivery_time'))
                || metadata_exists('post', $this->order->id, 'pickup_date') || metadata_exists('post', $this->order->id, 'pickup_time')) {

                $delivery_date_settings = get_option('coderockz_woo_delivery_date_settings');
                $delivery_date_format = (isset($delivery_date_settings['date_format']) && !empty($delivery_date_settings['date_format'])) ? $delivery_date_settings['date_format'] : "F j, Y";

                if (metadata_exists('post', $this->order->id, 'delivery_date')) {
                    $delivery_date = date($delivery_date_format, strtotime(get_post_meta($this->order->id, 'delivery_date', true)));
                    $date = date("d-m-y", strtotime($delivery_date));
                }
                elseif (metadata_exists('post', $this->order->id, 'pickup_date')) {
                    $delivery_date = date($delivery_date_format, strtotime(get_post_meta($this->order->id, 'pickup_date', true)));
                    $date = date("d-m-y", strtotime($delivery_date));
                }else {
                    $date = date("d-m-y");
                }

                if (metadata_exists('post', $this->order->id, 'delivery_time')) {
                    $time = get_post_meta($this->order->id, "delivery_time", true);
                    $time = explode(' - ', $time);
                    $time = max($time);
                    $times = date("H:i", strtotime($time));
                }
                elseif(metadata_exists('post', $this->order->id, 'pickup_time')){
                    $time = get_post_meta($this->order->id, "pickup_time", true);
                    $time = explode(' - ', $time);
                    $time = max($time);
                    $times = date("H:i", strtotime($time));
                }else {
                    $times = date("H:i");
                }
                $baseOrder->delivery_time = $times . " " . $date;
            } else {

                $baseOrder->delivery_time = date('H:i d-m-y', strtotime(str_replace("/", "-", $this->order->order_date)));
                update_post_meta($this->order->id, '_delivery_time', strtotime(str_replace("/", "-", $this->order->order_date)));
                $this->requested_delivery_time2 = strtotime(str_replace("/", "-", $this->order->order_date));
            }
        }

        $baseOrder->total_amount = number_format($this->order->get_total(),2,'.','');

        $extraFeeArray = array();
        if (count($this->line_items_fee) > 0){
            foreach ($this->line_items_fee as $item_id => $item){

                $data = $item->get_data();
                $extraFee = new  PrintercoOrderExtraFee();
                $extraFee->Name = $data['name'];
                $extraFee->Total = $data['total'];

                array_push($extraFeeArray, $extraFee);
            }
            $baseOrder->extra_fee = $extraFeeArray;
        }

        $baseOrder->apply_settings = '';
        $baseOrder->auto_print = '';
        $baseOrder->auto_accept = '';
        $baseOrder->enter_delivery_time = '';
        $baseOrder->time_input_method = '';
        $baseOrder->time_list = '';
        $baseOrder->extra_line_feed = '';
        $baseOrder->card_fee = 0;

        $order_items = array();

//        print_r($item_arr);
        while (count($this->item_arr)) {
//            $cat_id = 0;
//            $tcnt = 0;
            foreach ($this->item_arr as $ikey => $ival) {

                $orderItem = new  PrintercoOrderItem();
                $orderItem->Category =  $ival['cat'];
                $orderItem->Item =  $ival['item'];
                $orderItem->Description =  $ival['desc'];
                $orderItem->Quantity =  $ival['qnt'];
                $orderItem->Price =  $ival['price'];
                $orderItem->Addon = $this->get_item_addons($ival);
                array_push($order_items, $orderItem);
                unset($this->item_arr[$ikey]);
            }
        }
        $baseOrder->item = $order_items;
        $user_id = get_post_meta( $this->order->id, '_customer_user', true );
        $number_of_previous_order = 0;
        if($user_id){
            $orders=array();

            $args = array(
                'numberposts'     => -1,
                'meta_key'        => '_customer_user',
                'meta_value'      => $user_id,
                'post_type'       => 'shop_order',
                'post_status'     => 'publish',
                'tax_query'=>array(
                    array(
                        'taxonomy'  =>'shop_order_status',
                        'field'     => 'slug',
                        'terms'     =>'completed'
                    )
                )
            );

            $posts=get_posts($args);
            $orders=wp_list_pluck( $posts, 'ID' );
            $number_of_previous_order = count($orders);
        }

        if($user_id && $number_of_previous_order>1){
            $baseOrder->isVarified = 4;
        }
        else{
            $baseOrder->isVarified = 5;
        }
        $baseOrder->total_discount = number_format(-$this->order->get_total_discount(),2,'.','');
// WORK FOR ITEM PRICE IN INCLUSIVE TAX - START             
$prices_include_tax = get_post_meta($this->order->id, '_prices_include_tax', true);
if($prices_include_tax == "yes")
{   
    $baseOrder->total_discount = number_format(-$this->order->get_total_discount(0),2,'.',''); // flag pass 0
}
// WORK FOR ITEM PRICE IN INCLUSIVE TAX - END   

// VAT - START 
    $shippingTxAmt    = 0;
    if(metadata_exists('post', $this->order->id, '_order_shipping_tax'))
    {
        $shippingTxAmt = get_post_meta($this->order->id, "_order_shipping_tax", true);
        $shippingTxAmt = number_format($shippingTxAmt,2,'.','');
    }        
    if(metadata_exists('post', $this->order->id, '_order_tax'))
    {
        $txAmt = get_post_meta($this->order->id, "_order_tax", true);
        $baseOrder->tax_amount = number_format($txAmt,2,'.','') + $shippingTxAmt;
    }else
    {
        $baseOrder->tax_amount = null;
    }

// VAT IS NOT ADDING THEREFORE ADD BELOW CODE(3rdMay2024):
    if($this->order->total_tax)
    {
        $baseOrder->tax_amount = number_format($this->order->total_tax,2,'.','');   
    }    
    
// VAT - END

        $changedOrder =  apply_filters("before_send_order_to_iconnect", $baseOrder);
        if (!empty($changedOrder)){
            $baseOrder = $changedOrder;
        }
        //exit;
        $changedOrder->item = $this->convert_order_item($changedOrder->item);
        $orderArray = (array) $baseOrder;


        //print_r($orderArray);
        if (count($orderArray['item']) > 0 ) {
            $items = $orderArray['item'];
            unset($orderArray['item']);

            foreach ($items as $item){
                $itemsAddonsArray = array();
                $itemArray = (array) $item;
                foreach ($itemArray['item_addon'] as $addon){
                    $itemsAddonsArray[] = (array) $addon;
                }
                $itemArray['item_addon'] = $itemsAddonsArray;
                $orderArray['line_items'][] = $itemArray;
            }
         /*   for ($i = 0; $i < count($items); $i++){

                $orderItems =  $orderArray['item'][$i];
                $itemArray = array();
                $itemArray['cat_' . ($i + 1)] = $orderItems->Category;
                $itemArray['item_' .  ($i + 1)] = $orderItems->Item;
                $itemArray['desc_' .  ($i + 1)] = $orderItems->Description;
                $itemArray['qnt_' .  ($i + 1)] = $orderItems->Quantity;
                $itemArray['price_' . ($i + 1)] =  $orderItems->Price;
                $orderArray += $itemArray;

            }
            unset($orderArray['item']);*/
        }

        if($orderArray['extra_fee'] != null)
        {
	        if (count($orderArray['extra_fee']) > 0 ) 
	        {
	            for ($i = 0; $i < count($orderArray['extra_fee']); $i++) 
	            {
	                $extraFeeArray = array();
	                $extraFee = $orderArray['extra_fee'][$i];
	                $extraFeeArray["extra_fee_name_" . ($i + 1)] = $extraFee->Name;
	                $extraFeeArray["extra_fee_amount_" . ($i + 1)] = $extraFee->Total;
	                $orderArray += $extraFeeArray;
	            }
	            unset($orderArray['extra_fee']);
	        }
        }

        $post_data += $orderArray;
        return $post_data;
    }

    function get_item_addons($item){
        $addons = array();

        if (!empty($item) && isset($item['addon']) && !empty($item['addon']))
        {
            foreach ($item['addon'] as $value){

                $addon = get_term_by('slug', $value['addon_item']['value'], 'product_addon');
                $addonParent = get_term_by('id', $addon->parent, 'product_addon');

                $itemAddon = new PrintercoOrderItemAddon();
                $itemAddon->name = $addon->name;
                $itemAddon->title = $addonParent->name;
                $itemAddon->price = $value['price'];
                $addons[] = $itemAddon;
            }
        }
        
        return $addons;
    }

    function set_items_info() {

        foreach ($this->items as $item) {
            $item_info_arr = array();
            $item_id = $item['product_id'];
            $product = new WC_Product($item_id);
            print_r($product);
            $product_id = $item['product_id'];
            $this->vendor_id = get_post($product_id)->post_author;

            $product_cats = wp_get_post_terms($product_id, 'product_cat');

            if ($product_cats && !is_wp_error($product_cats)) {

                $single_cat = array_shift($product_cats);
                $item_info_arr['catid'] = $single_cat->term_id;
                $item_info_arr['cat'] = $single_cat->name;

            } else {
                $item_info_arr['catid'] = '';
                $item_info_arr['cat'] = '';
            }
            $item_meta = new WC_Order_Item_Meta($item, $product);
            $item_option_arr = $item_meta->get_formatted();
            $item_info_arr['item'] = $item['name'];
            $item_info_arr['qnt'] = $item['quantity'];
            $item_info_arr['price'] = $item['subtotal'];//WORK FOR ITEM PRICE IN EXCLUSIVE TAX 
// WORK FOR ITEM PRICE IN INCLUSIVE TAX - START             
            if( wc_prices_include_tax() ) // find it is inclusive, then go into this condition
            {
                if(isset($item['subtotal'])){ $subtotal = $item['subtotal']; }else {$subtotal = 0; }
                if(isset($item['subtotal_tax'])){ $subtotal_tax = $item['subtotal_tax']; }else {$subtotal_tax = 0; }
                $itemPrices =  number_format($subtotal,2,'.','') + number_format($subtotal_tax,2,'.','');
                $item_info_arr['price'] = $itemPrices;
            }
// WORK FOR ITEM PRICE IN INCLUSIVE TAX - END      

            $item_info_arr['addon'] = $item_meta->meta['_addon_items'];
            print_r($item_option_arr);
            $item_desc_arr = array();
            foreach ($item_option_arr as $val) {
                if ($val['value']) {
                    $item_desc_arr[] = '-' . $val['value'];
                }
            }
            $item_desc_text = implode('@@', $item_desc_arr);
            if (strlen($item_desc_text)) $item_desc_text = '@@' . $item_desc_text;
            $item_info_arr['desc'] = $item_desc_text;

            $this->item_arr[] = $item_info_arr;

        }
    }
	
	function post_to_api($url, $post_data) {
		set_time_limit(60);
		$output = array();
				
		$fields = "";
		$i = 0;
		$fields = http_build_query($post_data);

		/*foreach ($post_data as $key => $value) {
			$fields .= ($i > 0) ? "&" . "$key=" . urlencode($value) : "$key=" . urlencode($value);
			$i++;
		};*/

		$curlSession = curl_init();
        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_HEADER, 0);
        curl_setopt($curlSession, CURLOPT_POST, 1);
        //curl_setopt($curlSession, CURLOPT_PORT, 8080);
        curl_setopt($curlSession, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);
				
		$rawresponse = curl_exec($curlSession);
		
		$response_xml = simplexml_load_string($rawresponse);

        if ($response_xml && $response_xml->status == 'OK') {
            $output['status'] = 'OK';
            $output['details'] = (string)$response_xml->details->msg;
        } else {
            $output['status'] = 'FAILED';
            $output['error'] = array();
            $curl_errors = array();

            foreach ($response_xml->details->error as $val) {
                $output['error'][] = (string)$val;
            }

            $last_error_code = curl_errno($curlSession);
            if($last_error_code) {
                $curl_errors['error'] = 'Curl error: ' . curl_error($curlSession);
                $curl_errors['code'] = $last_error_code;
                $curl_errors['description'] = curl_strerror(curl_errno($curlSession));
                $curl_errors['http_status'] = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);

                $output['curl_errors'] = $curl_errors;
            }
        }

		curl_close($curlSession);
		
		return $output;
	}


	public function admin_show_iconnect_info_in_order_details($order){
		$submitted_to_iconnect = get_post_meta( $order->get_id(), '_submitted_to_iconnect', true );
		$iconnect_notification_status = get_post_meta( $order->get_id(), '_iconnect_notification_status', true );
		$iconnect_notification_msg = get_post_meta( $order->get_id(), '_iconnect_notification_msg', true );
		$iconnect_notification_agreed_time = get_post_meta( $order->get_id(), '_iconnect_notification_agreed_time', true );

        $msg = '<b>MyPanel Status:</b><br/>';
        $txt = '';
        $apiStatus = 'No';

        if ($submitted_to_iconnect == self::SUBMITTED_TO_ICONNECT_STATUS) {
            $body = '';
            $apiStatus = 'Yes';
            $printerStatus = 'Pending';

            switch($iconnect_notification_status) {
                case self::ICONNECT_NOTIFY_STATUS_ACCEPTED:
                    $printerStatus = 'Accepted';

                    $agreedTime = 'N/A';
                    if ($iconnect_notification_agreed_time) {
                        $agreedTime = date('d/m/Y H:i', $iconnect_notification_agreed_time);
                    }
                    $body .= 'Agreed time: ' . $agreedTime . '<br>';

                    $notify_msg = 'N/A';
                    if ($iconnect_notification_msg) {
                        $notify_msg = esc_html($iconnect_notification_msg);
                    }
                    $body .= 'Message: ' . $notify_msg . '<br>';
                break;
                case self::ICONNECT_NOTIFY_STATUS_REJECTED:
                    $printerStatus = 'Rejected';

                    $rejectedReason = 'N/A';
                    if ($iconnect_notification_msg) {
                        $rejectedReason = esc_html($iconnect_notification_msg);
                    }
                    $body .= 'Rejected for: ' . $rejectedReason . '<br>';
                break;
            }

            $txt = 'Status from printer: ' . $printerStatus . '<br>' . $body;
        }

        $msg .= 'Sent to API: ' . $apiStatus . '<br>' . $txt;

        $msg .= $this->get_order_refund_error_details($order->get_id());
		$msg .= $this->get_order_error_details($order->get_id());
        $msg .= $this->get_post_error_details($order->get_id());

       echo $msg;
	}

	private function get_order_refund_error_details($order_id){
        $error_details = '';
        $errors['Refund'] =  unserialize(get_post_meta( $order_id, '_printerco_refund_error', true ));
        if ($errors['Refund'] != "" ) {
            foreach ($errors as $name => $error) {
                if (!empty($error)) {
                    $error_details .= '<br><b> Refund Error Details</b><br>';

                    $error_details .= '<b>Error code: </b>';
                    $error_code = $error->get_error_code();
                    foreach ($error_code->get_error_messages() as $error_message){
                        $error_details .= '<b>  '. $error_message. '<br>';
                    }
                    $error_details .= '<b>Error description: </b>' . $error->getMessage() . '<br>';
                    $error_details .= '<b>Http status: </b>' . $error->get_status_code() . '<br>';
                }
            }
        }
        return $error_details;
    }

	private function get_order_error_details($order_id) {
        $error_details = '';
        $errors['Curl'] = get_post_meta( $order_id, '_iconnect_curl_error', true );
        if ($errors['Curl'] != "") {
            foreach ($errors as $name => $error) {
                if (!empty($error)) {
                    $error_details .= '<br><b> Curl Error Details</b><br>';
                    $error_details .= '<b>Error: </b>' . $error['error'] . '<br>';
                    $error_details .= '<b>Error code: </b>' . $error['code'] . '<br>';
                    $error_details .= '<b>Error description: </b>' . $error['description'] . '<br>';
                    $error_details .= '<b>Http status: </b>' . $error['http_status'] . '<br>';
                }
            }
        }

        return $error_details;
    }

    private function get_post_error_details($order_id){
        $errors = get_post_meta( $order_id, '_printerco_post_error', true );
        $error_details = '';
        if ($errors != "") {
            foreach ($errors as $error) {
                $error_details .= '<b>MyPanel Error: </b> ' . $error . '<br>';
            }
        }
        return $error_details;
    }

    private function get_notify_url($notify_url)
    {
        $notify = trim($notify_url) != "" && $notify_url != null;
        if ($notify){
            return $notify_url;
        }
        else{
            return $this->get_notify_page();
        }
    }

    private function get_order_type() {
        $orderShippingMethod = get_post_meta( $this->order->id, 'delivery-method', true );
        $shipping_method_info = $this->get_order_shipping_method_info();
        $shippingMethodRateId = $shipping_method_info['rate_id'];
        $shippingMethodTitle = $shipping_method_info['title'];
        $settings_apply = $shipping_method_info['settings_apply'];

        echo 'Shipping method: '.esc_html($orderShippingMethod).' : '.esc_html($shippingMethodTitle);

        $orderType =  OrderType::Collection;




        if($settings_apply) {
            foreach ($this->api_settings['delivery_option'] as $delivery_option) {
                if ($delivery_option  == $orderShippingMethod || $delivery_option  == $shippingMethodRateId) {
                    $orderType = OrderType::Delivery;
                    break;
                }
            }
            if (metadata_exists('post', $this->order->id, 'delivery_type')) {
                if (get_post_meta($this->order->id, "delivery_type", true) == "pickup") {
                    $orderType = OrderType::Collection;
                } elseif (get_post_meta($this->order->id, "delivery_type", true) == "delivery") {
                    $orderType = OrderType::Delivery;
                }
            }
// WORKED FOR 'ONLINE FOOD PREMIUM' PLUGIN - START  (27th APR 2023)
            if (metadata_exists('post', $this->order->id, 'fdoe_delivery_mode')) {
                if (get_post_meta($this->order->id, "fdoe_delivery_mode", true) == "pickup") {
                    $orderType = OrderType::Collection;
                } elseif (get_post_meta($this->order->id, "fdoe_delivery_mode", true) == "delivery") {
                    $orderType = OrderType::Delivery;
                } elseif (get_post_meta($this->order->id, "fdoe_delivery_mode", true) == "eathere") {
                    $orderType = OrderType::EatIn;
                }

                
            }
// WORKED FOR 'ONLINE FOOD PREMIUM' PLUGIN - END  (27th APR 2023)


        }

        return $orderType;
    }

    private function get_order_shipping_method_info() {
        $names = [];
        $names['settings_apply'] = false;
        foreach ($this->order->get_shipping_methods() as $shipping_method) {
            $instance_id = $shipping_method->get_instance_id();
            $method_id = $shipping_method->get_method_id();
            $names['title'] = $shipping_method->get_method_title();

            if($instance_id) {
                $names['rate_id'] = $method_id . ":" . $instance_id;
                $names['settings_apply'] = true;
            } else {
                // Only for orders creating by admin panel
                $methods = WC()->shipping->get_shipping_methods();
                if($methods && $methods[$method_id]) {
                    $names['title'] = $methods[$method_id]->get_method_title();
                }
                $names['rate_id'] = $method_id;
            }
        }
        return $names;
    }

    private function convert_order_item($items)
    {
        $result = array();
        foreach ($items as $item){

            $result[] = new PrintercoOrderPostItem($item);
        }
        return $result;
    }
}

endif;