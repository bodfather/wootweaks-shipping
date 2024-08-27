<?php
/**
 * Plugin Name: Wootweaks Shipping Plugin
 * Plugin URI: https://github.com/bodfather/wootweaks-shipping
 * Description: Adds additional shipping options to the WooCommerce shipping settings page.
 * Version: 0.0.1
 * Author: bodfather
 * Author URI: https://github.com/bodfather
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * License: GPLv2 or later
 * Plugin Repository: bodfather/wootweaks-shipping
 * Text Domain: wootweaks-shipping
 * Requires at Least: 5.0
 * Tested Up To: 6.6.1
 * Tags: woocommerce, shipping, customization, wootweaks
 * Requires PHP: 7.0
 * Update URI: https://api.github.com/repos/bodfather/wootweaks-shipping/releases/latest
 * Banner URI: /assets/banner.jpg
 * Icon URI: /assets/icon.png
 * Short Description: A plugin to add functionality to Woocommerce shipping.
 */

// Safety first

if (!defined('ABSPATH')) {
    header('Location: https://bodmerch.com/welcome.php');
    exit;
}

// Updater Code

function get_plugin_update_info() {
    // Define the path to the main plugin file
    $plugin_file = __FILE__;

    // Fetch only the text domain from the plugin header
    $plugin_data = get_file_data($plugin_file, array(
        'text_domain' => 'Text Domain'
    ));

    // Extract the text domain
    $text_domain = $plugin_data['text_domain'] ?? '';

    // Use the text domain in your functions
    function get_plugin_update_info() {
        global $text_domain;

        if (empty($text_domain)) {
            // Fallback if the text domain is not set
            return false;
        }

        $transient_key = 'update_plugins_' . $text_domain;
        $transient = get_site_transient($transient_key);

        if (isset($transient->response[$text_domain . '/' . $text_domain . '.php'])) {
            $update_info = $transient->response[$text_domain . '/' . $text_domain . '.php'];
            return $update_info;
        }

        return false;
    }

    // Example usage of get_plugin_update_info
    $update_info = get_plugin_update_info();

    if ($update_info) {
        echo 'Latest Version: ' . $update_info->new_version;
        echo 'Last Updated: ' . $update_info->last_updated;
    }
}


// Call the updater

require_once 'updater.php';

/*
add_action('plugins_loaded', 'emailsig_updater_init');

function emailsig_updater_init() {
    // Store the last update check time
    $last_check_time = get_transient('emailsig_last_update_check');

    if (empty($last_check_time) || (time() - $last_check_time > 86400)) { // 86400 seconds = 24 hours
        // Include the updater file only when needed
        require_once 'updater.php';

        // Manually call the update check function
        $transient = new stdClass();
        $transient = updater_pre_set_site_transient_update_plugins($transient);

        // Update the last check time
        set_transient('emailsig_last_update_check', time());
    }

    // Schedule the custom cron job
    wp_schedule_event(time(), 'daily', 'wp_cron_emailsig_updater');

    // Hook into the custom cron job
    add_action('wp_cron_emailsig_updater', 'emailsig_updater_hook');
}
*/
// Add menu items

function parse_readme($readme_file) {
    $content = file_get_contents($readme_file);
    $sections = [];

    // Regular expression to match headers and their content
    $pattern = '/##\s+(.+?)\s+(.*?)(?=\n##\s+|\Z)/s';
    
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $section_title = trim($match[1]);
        $section_content = trim($match[2]);

        // Normalize section titles to lower case for consistent array keys
        $section_key = strtolower(str_replace(' ', '_', $section_title));
        $sections[$section_key] = $section_content;
    }

    return $sections;
}


add_filter('plugins_api', 'wootweaks_shipping_plugin_info', 20, 3);

function wootweaks_shipping_plugin_info($res, $action, $args) {
    if ($action !== 'plugin_information') {
        return $res;
    }

    // Fetch plugin header data
    $plugin_file = __FILE__;
    $plugin_data = get_file_data($plugin_file, array(
        'name' => 'Plugin Name',
        'version' => 'Version',
        'author' => 'Author',
        'homepage' => 'Plugin URI',
        'requires' => 'Requires at least',
        'tested' => 'Tested up to',
        'requires_php' => 'Requires PHP',
        'compatible_up_to' => 'Compatible up to',
        'text_domain' => 'Text Domain',
    ));

    // Use the text domain from the plugin header
    $text_domain = $plugin_data['text_domain'];

    if (isset($args->slug) && $args->slug === $text_domain) {
        $readme_path = plugin_dir_path($plugin_file) . 'README.md';
        $sections = file_exists($readme_path) ? parse_readme($readme_path) : array();

        // Get last updated
        $last_updated = get_option($text_domain . '_last_updated');

        // Format the last updated date if available
        $last_updated_date = $last_updated ? date('F j, Y', strtotime($last_updated)) : 'Unknown';

        // Convert Markdown to HTML
        require_once plugin_dir_path($plugin_file) . 'includes/parsedown/Parsedown.php'; // Include Parsedown library
        $parsedown = new Parsedown();

        foreach ($sections as $key => $section) {
            $sections[$key] = $parsedown->text($section);
        }

        // Construct the response object
        $res = new stdClass();
        $res->name = $plugin_data['name'];
        $res->slug = $text_domain;
        $res->version = $plugin_data['version'] ?: '0.0.1'; // Default version if not specified
        $res->author = $plugin_data['author'] ?: 'bodfather'; // Default author if not specified
        $res->homepage = $plugin_data['homepage'] ?: 'https://github.com/bodfather/' . $plugin_data['text_domain'] ; // Default homepage if not specified
        $res->requires = $plugin_data['requires'] ?: '4.7.0'; // Default requires version if not specified
        $res->tested = $plugin_data['tested'] ?: '6.5.5'; // Default tested up to version if not specified
        $res->requires_php = $plugin_data['requires_php'] ?: '5.6'; // Default requires PHP version if not specified
        $res->last_updated = $last_updated_date ?: '4 months ago'; // Default last updated info if not specified
        $res->compatible_up_to = $plugin_data['compatible_up_to'] ?: '6.5.5'; // Default compatible up to version if not specified
        $res->sections = array(
            'description' => $sections['description'] ?? '',
            'features' => $sections['features'] ?? '',
            'installation' => $sections['installation'] ?? '',
            'changelog' => $sections['changelog'] ?? '',
            'usage' => $sections['usage'] ?? '',
            'FAQ' => $sections['faq'] ?? '',
            // Add more sections if needed
        );

        // Optionally add more fields like 'banners', 'icons', etc.
        $res->banners = array(
            'high' => plugins_url('assets/banner.jpg', $plugin_file),
        );
        $res->icons = array(
            '1x' => plugins_url('assets/icon.png', $plugin_file),
        );
    }

    return $res;
}

add_filter( 'plugin_row_meta', 'wootweaks_shipping_plugin_row_meta', 10, 2 );

function wootweaks_shipping_plugin_row_meta( $links, $file ) {
    // Ensure this filter applies to your plugin file
    if ( plugin_basename( __FILE__ ) == $file ) {
        // Add custom links to the plugin row meta
        $row_meta = array(
            // 'view_details' => '<a href="' . esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=emailsignature&TB_iframe=true&width=772&height=245')) . '" class="thickbox open-plugin-details-modal" aria-label="' . esc_attr__('More information about EmailSignature', 'emailsignature') . '" data-title="' . esc_attr__('EmailSignature', 'emailsignature') . '">' . esc_html__('View details', 'emailsignature') . '</a>',
            // 'docs'         => '<a href="' . esc_url( 'https://docs.yourwebsite.com/emailsignature' ) . '" target="_blank" aria-label="' . esc_attr__( 'Docs', 'emailsignature' ) . '">' . esc_html__( 'Docs', 'emailsignature' ) . '</a>',
            // 'support'      => '<a href="' . esc_url( 'https://support.yourwebsite.com' ) . '" target="_blank" aria-label="' . esc_attr__( 'Support Forum', 'emailsignature' ) . '">' . esc_html__( 'Support Forum', 'emailsignature' ) . '</a>',
        );
        return array_merge( $links, $row_meta );
    }
    return (array) $links;
}



 // Check for Wootweaks plugin
    if (!is_plugin_active('woo-tweaks-menu/woo-tweaks-menu.php')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . __('Wootweaks Shipping requires the Wootweaks plugin to be installed and active.', 'wootweaks-shipping') . '</p></div>';
        });
        return;
    }

    // Check for WooCommerce plugin
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . __('Wootweaks Shipping requires the WooCommerce plugin to be installed and active.', 'wootweaks-shipping') . '</p></div>';
        });
        return;
    }

// Define plugin directory.
define('WOOTWEAKS_SHIPPING_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include the settings class.
require_once WOOTWEAKS_SHIPPING_PLUGIN_DIR . 'includes/class-wootweaks-shipping-settings.php';

// Initialize the plugin.
function wootweaks_shipping_init() {
    new Wootweaks_Shipping_Settings();
}
add_action('plugins_loaded', 'wootweaks_shipping_init');

