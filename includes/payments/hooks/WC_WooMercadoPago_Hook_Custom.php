<?php

class WC_WooMercadoPago_Hook_Custom extends WC_WooMercadoPago_Hook_Abstract
{
    /**
     * WC_WooMercadoPago_Hook_Custom constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        parent::__construct($payment);
    }

    /**
     * Load Hooks
     */
    public function loadHooks()
    {
        parent::loadHooks();
        add_action('wp_enqueue_scripts', array($this, 'add_checkout_scripts'));

        if (!empty($this->payment->settings['enabled']) && $this->payment->settings['enabled'] == 'yes') {
            add_action('woocommerce_after_checkout_form', array($this, 'add_mp_settings_script_custom'));
            add_action('woocommerce_thankyou', array($this, 'update_mp_settings_script_custom'));
        }
    }

    /**
     *  Add Discount
     */
    public function add_discount()
    {
        if (!isset($_POST['mercadopago_custom'])) {
            return;
        }
        if (is_admin() && !defined('DOING_AJAX') || is_cart()) {
            return;
        }
        $custom_checkout = $_POST['mercadopago_custom'];
        parent::add_discount_abst($custom_checkout);
    }

    /**
     * @return mixed
     */
    public function custom_process_admin_options()
    {
        $this->payment->init_settings();
        $post_data = $this->payment->get_post_data();

        $array = array('_mp_public_key_test', '_mp_access_token_test', '_mp_public_key_prod', '_mp_access_token_prod', 'checkout_credential_production');
        foreach ($this->payment->get_form_fields() as $key => $field) {
            if ('title' !== $this->payment->get_field_type($field)) {

                $value = $this->payment->get_field_value($key, $field, $post_data);
//
//
//                    $payments = WC_WooMercadoPago_Module::$payments_name;
//                    foreach ($payments as $payment){
//                        $id = forward_static_call(array($payment, 'getId'));
//                        $name = "woocommerce_".$id.'_settings';
//                        $config = get_option($name);
//                        $config[$key] = $value;
//
//                        update_option($name, apply_filters('woocommerce_settings_api_sanitized_fields_' . $id, $config));
//                    }
//                    update_option($key, $value, true);
//                    continue;
//                }
                $array = $this->payment->getCommonConfigs();
                if (in_array($key, $array)) {
                    update_option($key, $value, true);
                }
                $value = $this->payment->get_field_value($key, $field, $post_data);
                $this->payment->settings[$key] = $value;

            }
        }
        $_site_id_v1 = get_option('_site_id_v1', '');
        $is_test_user = get_option('_test_user_v1', false);
        if (!empty($_site_id_v1) && !$is_test_user) {
            // Analytics.
            $infra_data = WC_WooMercadoPago_Module::get_common_settings();
            $infra_data['checkout_custom_credit_card'] = ($this->payment->settings['enabled'] == 'yes' ? 'true' : 'false');
            $infra_data['checkout_custom_credit_card_coupon'] = ($this->payment->settings['coupon_mode'] == 'yes' ? 'true' : 'false');
            $this->mpInstance->analytics_save_settings($infra_data);
        }
        // Apply updates.
        return update_option($this->payment->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->payment->id, $this->payment->settings));
    }

    /**
     *
     */
    public function add_mp_settings_script_custom()
    {
        parent::add_mp_settings_script();
    }

    /**
     * @param $order_id
     */
    public function update_mp_settings_script_custom($order_id)
    {
        echo parent::update_mp_settings_script($order_id);
    }
}