<?php
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/printerco-order-info.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/printerco-order-item.php';
class PrintercoBaseOrder
{
    public
        $order_id,
        $isVarified,
        $order_time,
        $cust_name,
        $cust_instruction,
        $cust_phone,
        $cust_address,
        $order_type,
        $currency,
        $deliverycost,
        $payment_method,
        $payment_status,
        $delivery_time,
        $total_amount,
        $apply_settings,
        $auto_print,
        $auto_accept,
        $enter_delivery_time,
        $time_input_method,
        $time_list,
        $extra_line_feed,
        $card_fee,
        $item,
        $extra_fee,
        $total_discount;

    function __construct(){

    }
}
class PrintercoEatInOrder extends PrintercoBaseOrder {

    public $table_number;

    function __construct($object){
        $this->order_id = $object->order_id;
        $this->isVarified = $object->isVarified;
        $this->order_time = $object->order_time;
        $this->cust_name = $object->cust_name;
        $this->cust_instruction = $object->cust_instruction;
        $this->cust_phone = $object->cust_phone;
        $this->cust_address = $object->cust_address;
        $this->order_type = OrderType::EatIn;
        $this->currency = $object->currency;
        $this->deliverycost = $object->deliverycost;
        $this->payment_method = $object->payment_method;
        $this->payment_status = $object->payment_status;
        $this->delivery_time = $object->delivery_time;
        $this->total_amount = $object->total_amount;
        $this->apply_settings = $object->apply_settings;
        $this->auto_print = $object->auto_print;
        $this->auto_accept = $object->auto_accept;
        $this->time_input_method = $object->time_input_method;
        $this->enter_delivery_time = $object->enter_delivery_time;
        $this->time_list = $object->time_list;
        $this->extra_line_feed = $object->extra_line_feed;
        $this->card_fee = $object->card_fee;
        $this->item = $object->item;
        $this->extra_fee = $object->extra_fee;
        $this->total_discount = $object->total_discount;
    }

}
class PrintercoReservationOrder extends PrintercoBaseOrder {

    public $reservation_time;
    public $number_of_people;
    public $reservation_email;

    function __construct(){
    }
    function CreateFromBaseOrder($object){
        $this->cust_name = $object->cust_name;
        $this->cust_instruction = $object->cust_instruction;
        $this->cust_phone = $object->cust_phone;
        $this->cust_address = $object->cust_address;
        $this->order_id = $object->order_id;
        $this->isVarified = $object->isVarified;
        $this->order_time = $object->order_time;
        $this->order_type = OrderType::Reservation;
    }
}
