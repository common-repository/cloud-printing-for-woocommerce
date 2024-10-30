<?php
class PrintercoEmailEditor extends WC_Integration {

    use IconnectValidation;

    private $api_settings;
    const DESCRIPTION = 'PrinterCo Template Email Editor for Woocommerce store
    
    <b>Settings below will work only in case if default notification functionality is in use (Notify Url is empty or [OurShortCodename] is in use)</b>
    <br/>
    <b><strong>You can added automatically replace tags:</strong></b>
    <br/>
    <b>[order_id] - Order id</b>
    <br/>
    <b>[site_name] - Your site name</b>
    <br/>
    <b>[agreed_time] - Agreed time</b>
    <br/>
    <b>[processing_time] - Processing time</b>
    <br/>
    <b>[rejected_message] - Rejected reason</b>
    <br/>
    <b>[customer_name] - Customer name</b>
    ';


    /**
     * Init and hook in the integration.
     */
    public function __construct() {
        global $woocommerce;

        $this->id                 = 'iconnect-api-integration-email-editor';
        $this->method_title       = __( 'PrinterCo Email Editor', 'woocommerce-iconnect-api-integration' );
        $this->method_description = __( self::DESCRIPTION, 'woocommerce-iconnect-api-integration' );

        $this->order = null;

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
        add_filter( 'woocommerce_settings_api_form_fields_'. $this->id, array( $this, 'init_form_fields') );
    }
    /**
     * Initialize integration settings form fields.
     */
    public function init_form_fields() {
        return PrintercoSettings::get_email_settings();
    }
}