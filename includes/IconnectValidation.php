<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/printerco-settings.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/IconnectValidationOptions.php';

trait IconnectValidation
{
    private $iconnect_errors;
    private $post_value;
    private $wc_integration_validation;

    private function is_empty_field($value) {
        return isset( $value ) && 0 == strlen( $value );
    }

    private function get_post_value($key) {
        $index = $key;
        if(isset($this->plugin_id, $this->id)) {
            $index = $this->plugin_id . $this->id . '_' . $key;
            $this->wc_integration_validation = true;
        }
        // get the posted value
        $value = isset($_POST[ $index ]) ? $_POST[ $index ] : null;

        return $value;
    }


    private function general_validation_field($key, $field_title = "") {
        $value = $this->get_post_value($key);
        // check mandatory. Throw an error which will prevent the user from saving.
        if ( $this->is_empty_field($value) ) {
            $this->iconnect_errors[$key] = $key;
            if($this->wc_integration_validation) {
                $text_error = $this->get_default_error_notice($field_title);
                WC_Admin_Settings::add_error($text_error);
            }
        }

        return $value;
    }


    public function validate_vendor_settings(&$errors, $update, &$user) {
        $save_fields = PrintercoSettings::get_all_settings();
        foreach ($save_fields as $key => $settings) {
            $value = $this->handling_validation_field($key);
            if(isset($this->iconnect_errors[$key])) {
                $this->iconnect_errors[$key]['invalid_value'] = $value;
                $errors->add($key, $this->get_default_error_notice($settings['title']));
            }
        }
        return $errors;
    }

    public function handling_validation_field($key) {
        // Look for a validate_FIELDID_field method for special handling.
        if ( is_callable( array( $this, 'validate_' . $key . '_field' ) ) ) {
            return $this->{'validate_' . $key . '_field'}( $key );
        }
    }


    /**
     * Validate the API key
     * @see validate_settings_fields()
     */
    public function validate_iconnect_api_key_field( $key ) {

        $field_title = '';

        if (isset($this->form_fields[$key]['title']))
        {
            $field_title = $this->form_fields[$key]['title'];
        }
        
        return $this->general_validation_field($key, $field_title);
    }

    /**
     * Validate the API Password
     * @see validate_settings_fields()
     */
    public function validate_iconnect_api_password_field( $key ) {

        $field_title = '';

        if (isset($this->form_fields[$key]['title']))
        {
            $field_title = $this->form_fields[$key]['title'];
        }
        
        return $this->general_validation_field($key, $field_title);
    }

    /**
     * Validate the License Key
     * @see validate_settings_fields()
     */
    public function validate_iconnect_license_key_field( $key ) {

        $field_title = '';

        if (isset($this->form_fields[$key]['title']))
        {
            $field_title = $this->form_fields[$key]['title'];
        }
        
        return $this->general_validation_field($key, $field_title);
    }

    /**
     * Validate the Printer ID
     * @see validate_settings_fields()
     */
    public function validate_iconnect_printer_id_field( $key ) {
        
        $field_title = '';

        if (isset($this->form_fields[$key]['title']))
        {
            $field_title = $this->form_fields[$key]['title'];
        }
        
        return $this->general_validation_field($key, $field_title);
    }

    /**
     * Validate the Receipt Header
     * @see validate_settings_fields()
     */
    public function validate_iconnect_receipt_header_field( $key ) {
        
        $field_title = '';

        if (isset($this->form_fields[$key]['title']))
        {
            $field_title = $this->form_fields[$key]['title'];
        }
        
        return $this->general_validation_field($key, $field_title);
    }

    /**
     * Validate the Receipt Footer
     * @see validate_settings_fields()
     */
    public function validate_iconnect_receipt_footer_field( $key ) {
        
        $field_title = '';

        if (isset($this->form_fields[$key]['title']))
        {
            $field_title = $this->form_fields[$key]['title'];
        }
        
        return $this->general_validation_field($key, $field_title);
    }

    public function is_validation_success() {
        return is_null($this->iconnect_errors);
    }

    public function get_default_error_notice($title) {
        return "Looks like you made a mistake with the " . $title . " field.
                     Make sure you put correct data into the field";
    }


}