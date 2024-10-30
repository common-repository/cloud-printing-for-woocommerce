<?php
require_once 'printerco-order-info.php';

class PrintercoOrderCallback
{
    public
        $printer_id,
        $order_id,
        $order_status,
        $message,
        $processing_time,
        $delivery_time,
        $manual_update,
        $order_type;

    public function __construct()
    {
    }
}