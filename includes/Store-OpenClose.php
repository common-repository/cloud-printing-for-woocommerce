<?php

if ( ! class_exists( 'StoreOpenClose' ) ) :
   
class StoreOpenClose extends WC_Integration
{
        const PRINTERCO_DOMAIN         = 'http://mypanel.printerco.net/';
        const PRINTERCO_SHOPSTATUS_URL =  self::PRINTERCO_DOMAIN . 'get_status.php';



    public function __construct()
    {         
        $this->id                 = 'iconnect-api-integration';
        $this->method_title       = __( 'PrinterCo API Integration', 'woocommerce-iconnect-api-integration' );
		$this->method_description = __( 'PrinterCo API Integration for Woocommerce store', 'woocommerce-iconnect-api-integration' );
        add_action( 'woocommerce_before_checkout_form', [$this,'cc_find_openclose_shop'] );
    }


    public function cc_find_openclose_shop()
    {   $params                        = array();
        $params['req_api_key']         = $this->get_option( 'iconnect_api_key' );
        $params['req_api_password']    = $this->get_option( 'iconnect_api_password' );
        $params['req_printer_id']      = $this->get_option( 'iconnect_printer_id' );
        if($params['req_api_key'] != "" && $params['req_api_password'] != "" 
        && $params['req_printer_id'] != "" )
        {
        $response      = $this->find_shop_status($params);
        $xml           = simplexml_load_string($response);
        if((string)$xml->status != "FAILED")
        {  
        $shopStatus    = (string)$xml->details->shop_status;
        $connectionStatus = (string)$xml->details->connection_status;
        if($shopStatus != 'open' && $this->get_option( 'iconnect_shopclose_action' ) != "yes")
        {
            echo  "<p class='woocommerce-error'>".$this->get_option( 'iconnect_shopclose_message' )."</p>";
            echo '<style>.woocommerce-checkout #place_order { display: none; }</style>';
           // remove_action( 'woocommerce_proceed_to_checkout','woocommerce_button_proceed_to_checkout', 20);

        }

        if($connectionStatus == 'not connected' && $this->get_option( 'iconnect_printer_disconnect' ) != "yes")
        {
            echo  "<p class='woocommerce-error'>".$this->get_option( 'iconnect_printerdisconnect_message' )."</p>";
            echo '<style>.woocommerce-checkout #place_order { display: none; }</style>';
        }
       } // end of status condition
      } // end of if condition
        
    } // end of function


    public static function find_shop_status($params)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => self::PRINTERCO_SHOPSTATUS_URL.'?printer_id='.$params['req_printer_id'].'&api_key='.$params['req_api_key'].'&api_password='.$params['req_api_password'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
          ));
        $response = curl_exec($curl);
        curl_close($curl);    
        return $response;
    }
   
} // end of class

endif; // end of condition : class_exists( 'StoreOpenClose' )