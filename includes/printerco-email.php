<?php
class DefaultEmail{

    const DEFAULT_ACCEPT_MESSAGE = '
Hey [customer_name],

Good news! Your order [order_id] has been accepted!

Please give us [processing_time] to prepare your order. You should have this ready by [agreed_time].

Thank you for ordering with us.
[site_name] Team';

    const DEFAULT_REJECT_MESSAGE = '
Hi [customer_name],

We regret to inform you that we were unable to process your order [order_id] at this time.

Reason for rejection: [rejected_message]

Please try again later or call us if you have any queries. 

Thank you for ordering with us.
[site_name] Team';

    const DEFAULT_ACCEPT_RESERVATION_MESSAGE = 'Your reservation has now been booked for [agreed_time].';

    const DEFAULT_REJECT_RESERVATION_MESSAGE = 'Sorry! The reservation you have requested for [agreed_time] is not available.';
}