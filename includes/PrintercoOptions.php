<?php


class PrintercoOptions
{
    const ACCEPT_SUBJECT = "iconnect_accepted_subject";
    const ACCEPT_RESERVATION_SUBJECT= "iconnect_accepted_reservation_subject";
    const REJECT_RESERVATION_SUBJECT = "iconnect_rejected_reservation_subject";
    const ACCEPT_BODY = "iconnect_accepted_message";
    const REJECT_SUBJECT = "iconnect_rejected_subject";
    const REJECT_BODY = "iconnect_rejected_message";
    const DEBUG_MODE = "iconnect_debug_mode";
    const MANUAL_REFUND_SERVICE = "iconnect_manual_refund_services";
    const MANUAL_ORDER_STATUS = "iconnect_manual_refund_order_status";
    const AUTOMATED_REFUND_SERVICE = "iconnect_automated_refund_services";
    const AUTO_REFUND = "iconnect_auto_refund";
    const DOKAN_STRIPE_PAYMENT_METHOD = "dokan-stripe-connect";
    const REJECT_RESERVATION_BODY = "iconnect_rejected_reservation_message";
    const ACCEPT_RESERVATION_BODY = "iconnect_accepted_reservation_message";
    const RESERVATION_EMAIL = "iconnect_reservation_email";

    static function getConstants(){
        $oClass = new ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}