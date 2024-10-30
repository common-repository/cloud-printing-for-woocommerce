<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/printerco-settings.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/IconnectSettings.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/IconnectValidation.php';

class PrintercoDokan
{
    use IconnectValidation;

    const DOKAN_LITE_PLUGIN_NAME = "dokan-lite/dokan.php";
    const DOKAN_PRO_PLUGIN_NAME = "dokan-pro/dokan.php";

    private $options_title = "Printerco Printer Settings";


    public function __construct()
    {
        if ($this->is_dokan_activated()) {
            add_action( 'user_profile_update_errors', array($this, 'validate_vendor_settings'), 1, 3 );
            add_action( 'show_user_profile', array($this, 'add_customer_meta_fields'), 40);
            add_action( 'edit_user_profile', array($this, 'add_customer_meta_fields'), 40);
            add_action( 'edit_user_profile_update', array( $this, 'save_vendor_printer_fields' ) );
            add_action( 'personal_options_update', array( $this, 'save_vendor_printer_fields' ) );
        }
    }


    public function is_dokan_activated()
    {
        return is_plugin_active(self::DOKAN_LITE_PLUGIN_NAME) || is_plugin_active(self::DOKAN_PRO_PLUGIN_NAME);
    }

    private function is_vendor($user)
    {
        return isset($user->caps['seller']) || isset($user->caps['administrator']);
    }

    public function get_vendor_printer_settings_fields()
    {
        return PrintercoSettings::get_all_settings();
    }


    public function get_vendor_printer_options($vendor_id, $prefix = true) {
        $settings = [];
        $form_field_settings = get_user_meta($vendor_id, IconnectSettings::VENDOR_SETTINGS, true);

        if (!empty($form_field_settings)){
            foreach ($form_field_settings as $key => $option) {
                if (!$prefix) {
                    $key = PrintercoSettings::get_settings_key_without_prefix($key);
                }
                $settings[$key] = $option;
            }
        }
        return $settings;
    }

    public function save_vendor_printer_fields($user_id)
    {
        if (current_user_can( 'edit_user', $user_id ) ) {
            if ($this->is_validation_success()) {
                $options = array();
                $saved_fields = $this->get_vendor_printer_settings_fields();

                foreach ($saved_fields as $key => $settings) {
                    $posted_setting_value = $_POST[$key];
                    if (!empty($posted_setting_value)) {
                        if(is_array($posted_setting_value)) {
                            $options[$key] = $posted_setting_value;
                        } else {
                            $options[$key] = wp_kses_post(trim(stripslashes($posted_setting_value)));
                        }
                    }
                }

                update_user_meta($user_id, IconnectSettings::VENDOR_SETTINGS, $options);
            }
        }
    }


    public function add_customer_meta_fields($user)
    {
        if ($this->is_vendor($user)) {
            $user_settings = $this->get_vendor_printer_options($user->ID);
            $show_fields = $this->get_vendor_printer_settings_fields();

            ?>
            <div class="woocommerce">
                <h2><?php echo $this->options_title; ?></h2>
                <table class="form-table " id="printerco-printer-settings">
                    <?php foreach ($show_fields as $key => $field) : ?>
                        <?php
                        $style = (!empty($field['css'])) ? $field['css'] : "";
                        $value = esc_html(isset($user_settings[$key]) ? $user_settings[$key] : $field['default']);
                        ?>
                        <tr>
                            <th>
                                <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($field['title']); ?></label>
                            </th>
                            <td>
                                <?php if (!empty($field['type']) && 'text' === $field['type']) : ?>
                                    <input type="text" name="<?php echo esc_attr($key); ?>"
                                           id="<?php echo esc_attr($key); ?>"
                                           value="<?php echo $value ?>"/>
                                    <?php if (!empty($field['desc_tip']) && $field['desc_tip']) : ?>
                                        <p class="description"><?php echo wp_kses_post($field['description']); ?></p>
                                    <?php endif; ?>
                                <?php elseif (!empty($field['type']) && ('select' === $field['type'] || 'multiselect' === $field['type'])) : ?>
                                    <select name="<?php echo esc_attr($key); ?><?php echo ('multiselect' === $field['type']) ? '[]' : ''; ?>"
                                        <?php echo ('multiselect' === $field['type']) ? "multiple" : "" ?>
                                            id="<?php echo esc_attr($key); ?>"
                                            style="<?php echo $style; ?>">
                                        <?php

                                        if ($field['optgroup']) {
                                            foreach ($field['options'] as $optgroup_key => $optgroup) : ?>
                                                <optgroup label="<?php echo $optgroup_key; ?>">
                                                    <?php foreach ($optgroup as $option_key => $option_value) : ?>
                                                        <option value="<?php echo esc_attr($option_key); ?>"
                                                            <?php

                                                            $selected = $user_settings[$key];
                                                            if (is_array($selected)) {
                                                                selected(in_array((string)$option_key, $selected, true), true);
                                                            } else {
                                                                selected($selected, (string)$option_key);
                                                            }

                                                            ?>
                                                        ><?php echo esc_html($option_value); ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        <?php } else {

                                            foreach ($field['options'] as $option_key => $option_value) : ?>
                                                <option value="<?php echo esc_attr($option_key); ?>"
                                                    <?php

                                                    $selected = $user_settings[$key];
                                                    if (is_array($selected)) {
                                                        selected(in_array((string)$option_key, $selected, true), true);
                                                    } else {
                                                        selected($selected, (string)$option_key);
                                                    }

                                                    ?>><?php echo esc_html($option_value); ?></option>
                                            <?php endforeach; ?>
                                        <?php } ?>
                                    </select>
                                <?php elseif (!empty($field['type']) && 'textarea' === $field['type']) : ?>
                                <textarea cols="20" rows="3" name="<?php echo esc_attr($key); ?>"
                                          id="<?php echo esc_attr($key); ?>"
                                          style="<?php echo $style; ?>"><?php echo $value; ?></textarea>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php
        }
    }
}
