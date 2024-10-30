<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/PrintercoOptions.php';

class PrintercoRefund {

    public function __construct()
    {
    }

    function dokan_refund($settings, $order, $message){

        $fees = $order->get_fees();
        $items = $order->get_items();
        $taxes = $order->get_taxes();
        $refund_items_tax = array();
        $refund_items = array();
        $refund_item_qtys = array();
        foreach ($items as $item){
            $refund_items[$item->get_id()] = $item->get_total();
            $refund_item_qtys [$item->get_id()] = $item->get_quantity();
        }
        foreach ($fees as $fee){
            $refund_items[$fee->get_id()] = $fee->get_total();
        }
        foreach ($taxes as $tax){
            $refund_items_tax[$tax->get_id()] = $tax->get_total();
        }

        $data = [
            'order_id'               => $order->id,
            'refund_amount'          => $order->get_total(),
            'refund_reason'          => $message,
            'line_item_qtys'         => json_encode( $refund_item_qtys ),
            'line_item_totals'       => json_encode( $refund_items ),
            'line_item_tax_totals'   => json_encode( $refund_items_tax ),
            'api_refund'             => false,
            'restock_refunded_items' => null,
            'status'                 => 0
        ];

        try {
            $refund = \WeDevs\DokanPro\Refund\Ajax::create_refund_request($data);
        }
        catch (Exception $e){
           $this->save_debug_info($settings, $order, $e);
        }
    }

    function Refund($settings, $order, $message){
        if ($this->refund_enabled($settings)){
            return $this->make_refund_order($settings, $order, $message);
        }else{
            return false;
        }
    }

    private function save_debug_info($settings, $order, $e){
        update_post_meta($order->get_id(), '_printerco_refund_error', addslashes(serialize($e)));
        if($settings[PrintercoOptions::DEBUG_MODE]){
            $body = date('d/m/Y H:i') ." Order ID:". $order->get_id() . " failed refund : " . $e->getMessage();
            $file = fopen("printercolog.txt","w");
            fwrite($file, $body);
            fclose($file);
        }
    }
    private function refund_enabled($settings){
        if ($settings[PrintercoOptions::AUTO_REFUND] == "yes"){
            return true;
        }
        else {
            return false;
        }
    }
    private function make_refund_order($settings, $order, $message){
        $automated_refund = $settings[PrintercoOptions::AUTOMATED_REFUND_SERVICE];
        $manual_refund = $settings[PrintercoOptions::MANUAL_REFUND_SERVICE];

        $payment_method = $order->get_payment_method();
        $auto = in_array($payment_method, $automated_refund);
        $manual = in_array($payment_method, $manual_refund);

        if ($auto && $manual) {
            $auto = false;
        }

        if ($auto && $order->is_paid()){
            if ($payment_method == PrintercoOptions::DOKAN_STRIPE_PAYMENT_METHOD){
                $this->dokan_refund($settings, $order, $message);
            }else{
                $this->automated_refund_order($settings, $order, $message);
            }

            return true;
        }
        if ($manual){
            $this->manual_refund($settings, $order, $message);
            return true;
        }
        return false;
    }
    private function manual_refund($settings, $order, $message){
        $order->update_status($settings[PrintercoOptions::MANUAL_ORDER_STATUS], $message);
    }
    private function automated_refund_order($settings, $order, $message){
        try {
            $refund = wc_create_refund(array(
                'amount' => $order->get_total(),
                'reason' => $message,
                'order_id' => $order->get_id(),
                'refund_payment' => true
            ));
        }
        catch (Exception $e){
            $this->save_debug_info($settings, $order, $e);
        }
    }
}