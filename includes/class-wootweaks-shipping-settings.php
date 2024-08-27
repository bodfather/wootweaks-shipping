<?php

// Safety first
if (!defined('ABSPATH')) {
    header('Location: https://bodmerch.com/welcome.php');
    exit;
}

class Wootweaks_Shipping_Settings {
    public function __construct() {
        add_filter('woocommerce_get_sections_shipping', array($this, 'add_shipping_section'));
        add_filter('woocommerce_get_settings_shipping', array($this, 'add_shipping_settings'), 10, 2);
        add_filter('woocommerce_package_rates', array($this, 'modify_pudo_shipping_rate'), 10, 2); // Hook for modifying PUDO rate
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_packaging_surcharge')); // Hook for adding surcharge
        add_filter('woocommerce_cart_ready_to_calc_shipping', array($this, 'remove_shipping_from_cart_page'));
    }

    public function add_shipping_section($sections) {
        $sections['wootweaks_shipping'] = __('Wootweaks Shipping', 'wootweaks-shipping');
        return $sections;
    }

    public function add_shipping_settings($settings, $current_section) {
        if ($current_section == 'wootweaks_shipping') {
    
            // Initialize the shipping instances array
            $shipping_instances = array();
    
            // Get all existing shipping zones (including custom zones and "Rest of the World" zone)
            $zones = WC_Shipping_Zones::get_zones();
            $zones[] = new WC_Shipping_Zone(0); // Add the 'Rest of the World' zone
    
            // Loop through each zone
            foreach ($zones as $zone_data) {
                $zone_id = $zone_data instanceof WC_Shipping_Zone ? $zone_data->get_id() : $zone_data['zone_id'];
                $shipping_zone = new WC_Shipping_Zone($zone_id);
    
                // Get the shipping methods for the current zone
                $shipping_methods = $shipping_zone->get_shipping_methods(true);
    
                // Loop through each shipping method for this zone
                foreach ($shipping_methods as $instance_id => $shipping_method) {
                    // Check if the shipping method is enabled
                    if ('yes' === $shipping_method->enabled) {
                        // Populate the $shipping_instances array with instance IDs and titles
                        $shipping_instances[$instance_id] = esc_html($shipping_method->title);
                    }
                }
            }
            
    
            $settings = array(
                array(
                    'title' => __('Wootweaks Shipping Options', 'wootweaks-shipping'),
                    'type'  => 'title',
                    'id'    => 'wootweaks_shipping_options',
                ),
                array(
                    'title'    => __('Enable Free Shipping', 'wootweaks-shipping'),
                    'type'     => 'checkbox',
                    'desc'     => __('Enable free shipping for orders above a certain amount.', 'wootweaks-shipping'),
                    'id'       => 'wootweaks_free_shipping_enabled',
                    'default'  => 'no',
                ),
                array(
                    'title'    => __('Free Shipping Minimum Amount', 'wootweaks-shipping'),
                    'type'     => 'text',
                    'desc'     => __('Enter the minimum amount for free shipping.', 'wootweaks-shipping'),
                    'id'       => 'wootweaks_free_shipping_amount',
                    'default'  => '100',
                    'desc_tip' => true,
                ),
                array(
                    'title'    => __('Hide All Shipping Methods', 'wootweaks-shipping'),
                    'type'     => 'checkbox',
                    'desc'     => __('Hide all shipping methods from the cart, including local pickup.', 'wootweaks-shipping'),
                    'id'       => 'wootweaks_hide_shipping',
                    'default'  => 'no',
                ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'wootweaks_shipping_options',
                ),
                array(
                    'title' => __('Add Surcharge:', 'wootweaks-shipping'),
                    'type'  => 'title',
                    'id'    => 'wootweaks_shipping_surcharge_options',
                ),
                array(
                    'title'    => __('Name of Surcharge', 'wootweaks-shipping'),
                    'type'     => 'text',
                    'desc'     => __('Enter the surcharge item name to show on Checkout.', 'wootweaks-shipping'),
                    'id'       => 'wootweaks_packaging_surcharge_name',
                    'default'  => 'Packaging Surcharge',
                    'desc_tip' => true,
                ),
                array(
                    'title'    => __('Amount', 'wootweaks-shipping'),
                    'type'     => 'text',
                    'desc'     => __('Enter the fixed amount to add to PUDO shipping costs.', 'wootweaks-shipping'),
                    'id'       => 'wootweaks_packaging_fixed_amount',
                    'default'  => '0',
                    'desc_tip' => true,
                ),
                array(
                    'title'    => __('Apply Surcharge to:', 'wootweaks-shipping'),
                    'type'     => 'multiselect',
                    'desc'     => __('Select shipping methods to add a surcharge to.', 'wootweaks-shipping'),
                    'id'       => 'wootweaks_surcharge_shipping_ids',
                    'options'  => $shipping_instances,
                    'default'  => '',
                    'desc_tip' => true,
                    'class'    => 'wc-enhanced-select',
                ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'wootweaks_shipping_surcharge_options',
                ),
                
                
            );
        }
    
        return $settings;
    }

    public function add_custom_js_to_settings_page() {
        // Add custom JavaScript for the settings page
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Add custom class to specific settings fields
                $('input#wootweaks_packaging_fixed_amount').closest('tr').addClass('wootweaks-custom-class');
                $('select#wootweaks_surcharge_shipping_ids').closest('tr').addClass('wootweaks-custom-class');
                // Add more selectors and classes as needed
            });
        </script>
        <?php
    }
    
    
    public function remove_shipping_from_cart_page($show_shipping) {
        $hide_shipping = get_option('wootweaks_hide_shipping', 'no');

        if (is_cart() && 'yes' === $hide_shipping) {
            return false;
        }
        return $show_shipping;
    }

    public function add_packaging_surcharge() {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
    
        // Check if we are on the checkout page
        if (!is_checkout()) {
            return;
        }
    
        $surcharge = floatval(get_option('wootweaks_packaging_fixed_amount', 0)); // Get the surcharge amount
        $shipping_ids_option = get_option('wootweaks_surcharge_shipping_ids', ''); // Get the shipping method IDs
        $surcharge_name = get_option('wootweaks_packaging_surcharge_name', 'Packaging'); // Get the surcharge name
    
        // Ensure $shipping_ids_option is a string
        if (is_array($shipping_ids_option)) {
            $shipping_ids_option = implode(',', $shipping_ids_option);
        }
    
        $shipping_ids = explode(',', $shipping_ids_option); // Convert to an array
    
        if (WC()->cart && !WC()->cart->is_empty()) {
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            $has_surcharge_method = false;
    
            foreach ($chosen_shipping_methods as $chosen_method) {
                foreach ($shipping_ids as $shipping_id) {
                    if (strpos($chosen_method, trim($shipping_id)) !== false) {
                        $has_surcharge_method = true;
                        break 2; // Exit both loops if we find a match
                    }
                }
            }
    
            if ($has_surcharge_method) {
                WC()->cart->add_fee(__($surcharge_name, 'wootweaks-shipping'), $surcharge, true);
            }
        }
    }
    
    
    public function modify_pudo_shipping_rate($rates, $package) {
        $fixed_amount = floatval(get_option('wootweaks_pudo_fixed_amount', 0)); // Get the fixed amount from the settings
    
        foreach ($rates as $rate_key => $rate) {
            // Debugging: Log the method_id and rate_key
            error_log('Checking shipping method: ' . $rate->method_id . ' with rate key: ' . $rate_key);
    
            if ('9' === $rate->method_id) { // PUDO method ID
                // Debugging: Log the original cost
                error_log('Original cost for method ' . $rate->method_id . ': ' . $rate->cost);
                
                // Add the fixed amount to the existing cost
                $rates[$rate_key]->cost += $fixed_amount;
    
                // Adjust taxes if necessary
                foreach ($rates[$rate_key]->taxes as &$tax) {
                    $tax += $fixed_amount * ($tax / $rate->cost);
                }
    
                // Debugging: Log the updated cost
                error_log('Updated cost for method ' . $rate->method_id . ': ' . $rates[$rate_key]->cost);
            }
        }
    
        return $rates;
    }
}

// Initialize the plugin class
new Wootweaks_Shipping_Settings();
