=== Cloud Printing for WooCommerce ===
Contributors:
Tags: Sunmi V2, Woocommerce automatic order printing,WooCommerce POS, WooCommerce order printer, WooCommerce printer, PrinterCo,add print button to woocommerce checkout page
Author: PrinterCo.net
Requires at least: 4.1.1
Requires PHP: 7.0
Tested up to: 6.5.3
Stable tag: 4.1.1
License: GPLv2 or later License
URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 2.2.10
WC tested up to: 8.9.1


This plugin adds on-demand order printing features to the official WooCommerce plugin. Useful for restaurants and takeaways or online stores that need print on demand. It prints orders automatically from woocommerce. If you cancel an order from the printer app the order will be cancelled in woocommerce. An automatic refund can be set or cancelled orders. Compatible with popular restaurant ordering plugin.

== Description ==


Automatic order printing plugin for woocommerce. It prints orders automatically to PrinterCo POS or any POS printer.


Accept or Reject an Order
Cancel an order from the printer app and it will be automatically cancelled on your woo-commerce website and will issue a refund.
Set a delivery or collection time while accepting an order
Select and assign a driver to collect the item and deliver it.
Set automated printing at a busy hour
Preview an order before accepting
Order Countdown timer, see which orders are getting late.
Notify Customers For More Time To Prepare Orders
Send confirmation to your customer by SMS and email
Print End of the day taking and many more.

Get your POS printer from [www.PrinterCo.net](https://www.printerco.net)


[youtube https://www.youtube.com/watch?v=utmyjLz6ZCQ&t=4s]

**POS Printer Demonstration**
[youtube https://www.youtube.com/watch?v=-Tt6T8ugQe4]

Please support us by following us on social media. Just search **@PrinterCoMedia** on Facebook, Twitter or Instagram
.
You can also message on socials for support or go on our website for live chat.



== Installation ==

= Prerequisite =
To use this plugin, you need to: 
**(a)** Register with PrinterCo and access your MyPanel dashboard. 
**(b)** Add your POS printer to your MyPanel dashboard.
**(c)** Have your unique Printer ID ready to use.
**(d)** If you already have a Thermal printer, ask our experts if the version of your device is compatible with PrinterCo. This way you can use it again without buying a new one.

Tutorial for registering with PrinterCo: [pco.link/mypanel-setup-guide](pco.link/mypanel-setup-guide)

Tutorial for adding a printer to MyPanel: [pco.link/add-printer-guide](pco.link/add-printer-guide)

1. From your WordPress dashboard, in left navigation panel, click on **Plugins** > **Add New** > **Upload Plugin**.
2.
 Click the **Choose file** button and browse this plugin after downloading it. Make sure to upload it as a zip file. 
3. Click on the **Install Now** button.
4. **Active** the plugin after it has been successfully installed.
5. From the left navigation panel click **WooCommerce** > **Settings** > **Integration**.
6. Complete the form using the information found in your **MyPanel dashboard** > **My Account** > **Edit Information**.

= Integration form guideline =
**a) API Key:** Uniquely generated for every account registered with PrinterCo.

**b) API Password:** Uniquely generated for every account registered with PrinterCo.

**c) Licence Key:** To get your licence key from your MyPanel dashboard, click the Printer List button.
Next, click the edit button (pen icon) for the printer you want to assign the licence to.
In the Edit Printer page, select *Yes* for the *Register Licence?* dropdown box and click the *Submit* button to save changes.
You will be taken back to the printer list page. Click the View button (eye icon) to open the Printer Details page. Scroll down to find your licence key.
Tutorial: [pco.link/licence-key](pco.link/licence-key)

**d) Printer ID:** A uniquely generated for every printer added to PrinterCo. Find this under **Printers** > **Printer List**.

**e) Notify URL:** This is a page created by yourself with the purpose to retrieve order updates. With a logical statement, you can then take the appropriate action (i.e. notifying your customer when the order is accepted by the kitchen).
Create a blank page with the WordPress shortcode [PrinterCoShortCode].

**f) Receipt Header/Footer:** These fields allow you to create the contents for the header and footer of your receipt. Giving you the flexibility to extend your marketing efforts to your receipt papers.
To use breaklines use the code %% (i.e. 1st line of text%%2nd line of text).

**g) Text Size:** This allows you to change the font size for the contents on the receipts. For more control, leave the default setting and change the font size from your MyPanel dashboard instead.

**h) Prepaid Payment Option:** This setting allows you to define all prepaid payment options. By selecting a payment option populated in the list box, it will raise a flag to mark your customers orders as **PAID**. If a customer chooses a payment option that you have not selected here, then their order will be marked as **NOT PAID**.

**i) Delivery Options:** You may have already set this up with WooCommerce, but this serves a slightly different purpose. Since websites can have different names for a delivery order, it gets a little tricky for the plugin to identify this and label your orders correctly. So, if your website has a delivery option labelled as *Deliver It*, then you will need to type that in here.

**j) All Included?:** A somewhat redundant setting but nonetheless. This is used to force all order details to be included on your receipt (i.e. Order ID, Order Time, Order Requested Time etc). However, enabling this will essentially disable the options you have on the printers to single out and disable certain order details. We recommend leaving this setting set to the default *No*.

**k) Debug Mode?:** If you require debugging, you will need to enable this option and then provide an email address to where you want the debug information to be sent to.

After you have configured these settings, your clouding printing should be ready to go. If you think your plugin extension is not working as normal, or you need help then feel free to contact our support team. You can email support@printerco.net or reach us via live chat (found on our website) or use the forum [pco.link/forum](pco.link/forum).

== Frequently Asked Questions ==



= Is the plugin free? =

The plugin is free but you require a licence to use it. As a promotional offer, we are giving a single licence for GBP 19.99 and unlimited licenses for GBP 169.99. The licenses are for a lifetime with free updates. For more details, please visit [pco.link/wc-licences](pco.link/wc-licences)

= Do I need a printer to use this plugin? =

Yes, the purpose of this plugin is to send your WooCommerce orders to PrinterCo Android app. Our PrinterCo app is currently compatible with the Sunmi V2, Sunmi V2s, Sunmi V2 pro, Printerco H8, Q2i, P58-03 and TPS900 POS printers. You can also install the app on an Android phone or tablet and print the orders from any Bluetooth thermal printer. 

= Can I use any printer with this plugin, like a thermal printer? =

Our PrinterCo app is currently compatible with the Sunmi V2, Sunmi V2s, Sunmi V2 pro, Printerco H8, Q2i, P58-03 and TPS900 POS printers. You can also install the app on an Android phone or tablet and print the orders from any Bluetooth termal printer. 

= I have my own POS printer already, can I use that instead of yours? =

Yes, ask our experts if the version of your device is compatible with PrinterCo. This way you can use it again. If having your device is important for your business then we can definitely help you to make it compatible with PrinterCo app.

= Will this plugin allow me to accept and reject orders? =

Yes, but this is done on the POS printer itself.

= Does your printer have Wi-Fi or do I need a data sim card? =

We have a range of printers varying from just GMS and Wi-Fi & GSM combined. Visit our store for more info [pco.link/printers](pco.link/printers)

== Screenshots ==


1. Upload plugin
2. Install plugin
3. Plugin configuration - part 1
4. Plugin configuration - part 2




== Changelog ==


= 2.4 =
*Added securing output
*Added input validation
18th February 2019


= 2.5 =

*Added clearing the license key of the spaces
*Changed order creation format
6th March 2019

= 2.5.1 =

*Removed bug with failed activation - prompting to use latest PHP version.
1st June 2019


= 2.5.2 =

*Fixed shipping address not being parsed to printer.
*Fixed postcode not being collected
20th June 2019


= 2.5.3 =

*Prepaid Payment Options - reset WP default styles to get it show multiple lines instead of a single.
8 July 2019


= 2.5.4 =

*Extra Fee plugin integration added
19 June 2020


= 2.5.5 =

*Integration with Delivery Date & Time for WooCommerce plugin: https://wordpress.org/plugins/woo-delivery/
21 July 2020


= 2.5.6 =

*Integration with Five Star Restaurant Reservations – WordPress Booking Plugin https://wordpress.org/plugins/restaurant-reservations/
22 July 2020


= 2.5.7 =

*Fixed a bug with different time zones
*Fixed a bug with additional delivery fields
07 August 2020


= 2.5.8 =

*Integration with ReDi Restaurant Reservation https://wordpress.org/plugins/redi-restaurant-reservation/
14 August 2020


= 2.5.9 =

*Added support for "eat in" order format
21 August 2020


= 2.6 =

*Fixed a bug with PayPal payment
27 August 2020


= 2.6.1 =

*Added customisation email message for accepted and rejected order
18 September 2020


= 2.6.2 =

*Updated plugin integration with Delivery Date & Time for WooCommerce plugin version 1.2.49: https://wordpress.org/plugins/woo-delivery/
12 October 2020

= 2.6.3 =

*Dokan Multi Vendor Plugin integration added
14 January 2021

= 2.6.4

*Better error reporting
20 January 2021

= 2.6.5

*Added WooCommerce refund support

= 2.6.6

*Added support for WooCommerce Delivery plugin version version 1.1.17 https://codecanyon.net/item/woocommerce-delivery/26548021)

= 2.6.8

*Added support for Dokan Pro Stripe Refund https://wedevs.com/dokan

= 2.6.9

*Fixed error with processing time

= 2.7

*Added support for Food Store – Online Food Delivery & Pickup vesrion 1.3.11 https://wordpress.org/plugins/food-store/

= 2.7.2

*Fixed error with added payment method from WCFM https://wordpress.org/plugins/wc-frontend-manager/

= 2.7.3

*Added support for notifications for reservation order

= 2.7.5

*Added support compatibility for advance date

= 2.7.6

*update plugin according to latest version

= 2.7.7

* Print VAT on the receipt.
* Made it compatible with some other restaurant plugin.

= 2.7.8
* Some known bugs and warnings were fixed

= 2.7.9
* Some known bugs and warnings were fixed

= 2.8.0
* Some known bugs were fixed.
* Now you can disble the checkout when the shop is closed from the app.
* Now you can prevent the checkout when the printer is disconnected.

= 2.8.1
* Some known bugs were fixed.

== Upgrade Notice ==

= 2.4 =
Cleaned code and updated readme.txt to prepare plugin for WordPress.org repository.