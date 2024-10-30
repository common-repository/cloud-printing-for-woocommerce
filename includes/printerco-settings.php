<?php
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/printerco-email.php';
class PrintercoSettings {

    const FIELD_PREFIX = "iconnect_";

    public static function get_settings() {
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $orders_status = wc_get_order_statuses();
        $statuses = array();
        foreach ($orders_status as $key => $value){
            $statuses[$key] = $value;
        }

        $all_gateway_list = array();
        $automated_refund_gateways = array();
        foreach($available_gateways as $key=>$val){
            $all_gateway_list[$key] = $val->get_method_title();

            $refund_enable = $val->supports('refunds') || $val->id == PrintercoOptions::DOKAN_STRIPE_PAYMENT_METHOD;
            if ($refund_enable){
                $automated_refund_gateways[$key] = $val->get_method_title();
            }
        }

        $gateway_list = array();
        foreach($available_gateways as $key=>$val){
            $gateway_list[$key] = $val->title;
        }

        $shippings = self::get_shipping_zones();

        return array(
            'iconnect_api_key' => array(
                'title'             => __( 'API Key', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'Enter your API Key. You can find this in your PrinterCo panel.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'iconnect_api_password' => array(
                'title'             => __( 'API Password', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'Enter your API Password. You provided this when you registered with PrinterCo.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'iconnect_license_key' => array(
                'title'             => __( 'License Key', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'Enter your License Key. If you buy license for plugin then you can find it in your PrinterCo panel.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'iconnect_printer_id' => array(
                'title'             => __( 'Priner ID', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'Enter your Printer ID. You can find this in your PrinterCo panel.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'iconnect_notify_url' => array(
               'title'             => __( 'Notify Url', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'leave empty in order to use default notification handling code or create a page for Notify Url and put shortcode [PrinterCoShortCode] as the page content and use the Url of that page as Notify Url here.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'iconnect_receipt_header' => array(
                'title'             => __( 'Receipt Header', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'Enter your Receipt Header.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'iconnect_receipt_footer' => array(
                'title'             => __( 'Receipt Footer', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'Enter your Receipt Footer.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'iconnect_receipt_text_size' => array(
                'title'             => __( 'Text Size', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'select',
                'description'       => __( 'Select Receipt Text Size.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'options'			=> array(
                    'default'=>'Default (small, large mix)',
                    'small'=>'Whole receipt small',
                    'large'=>'Whole receipt large',
                )
            ),
            'iconnect_prepaid_payment_option' => array(
                'title'             => __( 'Prepaid Payment Options', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'multiselect',
                'description'       => __( 'Select all the prepaid payment options. If customer chooses any of these option order will be marked as paid. Order with other options will be marked as not paid.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'css'				=> 'height:auto',
                'options'			=> $gateway_list,
            ),
            'iconnect_delivery_option' => array(
                'title'             => __( 'Delivery Options', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'multiselect',
                'description'       => __( 'Orders with shipping methods selected here will be considered as Delivery orders.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'optgroup'          => true,
                'default'           => '',
                'css'				=> 'height:200px',
                'options'           => $shippings
            ),
            'iconnect_all_include' => array(
                'title'             => __( 'All Included?', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'select',
                'description'       => __( 'This will include Order ID, Order Type, Order Time, Delivery Time in main receipt body so that you will be able to control text size but this will cause you to turn off the printing of these sections from printer setttings.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'options'			=> array(
                    'no'=>'No',
                    'yes'=>'Yes',

                )
            ),
            'iconnect_auto_refund' => array(
                'title'             => __( 'Enable Refunds ?', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'select',
                'description'       => __( 'Enable or disable refund when order is rejected.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'options'			=> array(
                    'no'=>'No',
                    'yes'=>'Yes',
                )
            ),
            'iconnect_automated_refund_services' => array(
                'title'             => __( 'Auto-refund Payment Options', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'multiselect',
                'description'       => __( 'Orders with one of selected Payment Option will be refunded automatically using standard functionality of WooCommerce.
                                            NOTE: please ensure that your payment plugin supports automated refunds. 
                                            In case when plugin does not support such functionality - order will be marked as refunded, but money will not return to user!',
                                            'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'css'				=> 'height:auto; disabled = "disabled"',
                'options'			=> $automated_refund_gateways,
            ),
            'iconnect_manual_refund_order_status' => array(
                'title'             => __(  'Order Status For Manual Refund', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'select',
                'description'       => __( 'Choose status that will be set on orders that you want to refund manually.
                 Please check WooCommerce documentation to see how to create new statuses.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'options'			=> $statuses
            ),
            'iconnect_manual_refund_services' => array(
                'title'             => __( 'Manual Refund Payment Options', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'multiselect',
                'description'       => __( 'Orders with one of selected Payment Option will get a status of your choice 
                                            indicating which order is waiting for your attention to be refunded.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'css'				=> 'height:auto;',
                'options'			=> $all_gateway_list,
            ),

            'iconnect_shopclose_action' => array(
                'title'             => __( 'Allow checkout, if shop is closed?', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'select',
                'description'       => __( 'Allow customers to checkout/place orders when the shop is closed. ', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'options'			=> array(
                    'yes'=>'Yes',
                    'no' =>'No'
                    

                )
            ),
            'iconnect_shopclose_message' => array(
                'title'             => __( 'Show message if shop is closed', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'If shop is closed then this message will show.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'iconnect_printer_disconnect' => array(
                'title'             => __( 'Allow checkout, if printer is disconnected?', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'select',
                'description'       => __( 'Allow customers to checkout/place orders when the printer is disconnected. ', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'options'			=> array(
                    'yes'=>'Yes',
                    'no'=>'No'
                    

                )
            ),
            'iconnect_printerdisconnect_message' => array(
                'title'             => __( 'Show message if printer is disconnect', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'If printer is disconnect then this message will show.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),



            
            'iconnect_debug_mode' => array(
                'title'             => __( 'Debug Mode?', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'select',
                'description'       => __( 'Elabling debug mode will email debug data to specified email and/or write into fite.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'options'			=> array(
                    'no'=>'No',
                    'yes'=>'Yes',

                )
            ),
            'iconnect_debug_email' => array(
                'title'             => __( 'Email Debug Data To', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'text',
                'description'       => __( 'Debug data will be emailed to this email address.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
        );

    }
    public static function get_email_settings(){
        return array(
            'iconnect_accepted_subject' => array(
                'title'             => __( 'Accept Order Subject', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'textarea',
                'description'       => __( 'Set your subject for accepted order message.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => 'Your order (ID:  [order_id]) has been accepted on  [site_name]',
                'css'				=> 'width:50%'
            ),
            'iconnect_accepted_message' => array(
                'title'             => __( 'Accept Order Message', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'textarea',
                'description'       => __( 'Set your message body for accepted order message.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => DefaultEmail::DEFAULT_ACCEPT_MESSAGE,
                'css'				=> 'width:50%; min-height:200px'

            ),
            'iconnect_rejected_subject' => array(
                'title'             => __( 'Reject Order Subject', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'textarea',
                'description'       => __( 'Set your subject for rejected order message.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => 'Your order (ID:  [order_id]) has been rejected on  [site_name]',
                'css'				=> 'width:50%'
            ),
            'iconnect_rejected_message' => array(
                'title'             => __( 'Reject Order Message', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'textarea',
                'description'       => __( 'Set your message body for rejected order message.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => DefaultEmail::DEFAULT_REJECT_MESSAGE,
                'css'				=> 'width:50%; min-height:200px'
            ),
            'iconnect_reservation_email' => array(
                'title'             => __( 'Reservation Email Notification', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'select',
                'description'       => __( 'Enable or disable email notification for reservation.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => '',
                'options'			=> array(
                    'no'=>'No',
                    'yes'=>'Yes',
                )
            ),
            'iconnect_accepted_reservation_subject' => array(
                'title'             => __( 'Accept Reservation Subject', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'textarea',
                'description'       => __( 'Set your subject for accepted order message.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => 'Reservation [order_id] approved',
                'css'				=> 'width:50%'
            ),
            'iconnect_accepted_reservation_message' => array(
                'title'             => __( 'Accept Reservation Order Message', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'textarea',
                'description'       => __( 'Set your message body for accepted reservation order message.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => DefaultEmail::DEFAULT_ACCEPT_RESERVATION_MESSAGE,
                'css'				=> 'width:50%; min-height:200px'

            ),
            'iconnect_rejected_reservation_subject' => array(
                'title'             => __( 'Reject Reservation Subject', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'textarea',
                'description'       => __( 'Set your subject for rejected order message.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => 'Reservation [order_id] declined',
                'css'				=> 'width:50%'
            ),
            'iconnect_rejected_reservation_message' => array(
                'title'             => __( 'Reject Reservation Order Message', 'woocommerce-iconnect-api-integration' ),
                'type'              => 'textarea',
                'description'       => __( 'Set your message body for rejected reservation order message.', 'woocommerce-iconnect-api-integration' ),
                'desc_tip'          => true,
                'default'           => DefaultEmail::DEFAULT_REJECT_RESERVATION_MESSAGE,
                'css'				=> 'width:50%; min-height:200px'
            )
        );
    }

    public static function get_shipping_zones() {
        $shipping_methods = [];
        $shipping_zones = WC_Shipping_Zones::get_zones();

        foreach($shipping_zones as $zone) {
            $zone_name = $zone['zone_name'];
            foreach ($zone['shipping_methods'] as $shipping) {
                $rate_id = $shipping->get_rate_id();
                $shipping_methods[$zone_name][$rate_id] = $shipping->get_title();
            }
        }

        return $shipping_methods;
    }

    public static function get_all_settings(){
        $settings = PrintercoSettings::get_settings();
        $email_settings = PrintercoSettings::get_email_settings();
        return array_merge($settings, $email_settings);
    }
    public static function get_settings_key_without_prefix($option) {
        return str_replace(self::FIELD_PREFIX, "", $option);
    }
}
