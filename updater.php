<?php

// Safety first

if (!defined('ABSPATH')) {
    header('Location: https://bodmerch.com/welcome.php');
    exit;
}

function updater_pre_set_site_transient_update_plugins($transient) {
        // Get the directory of the current file
        $current_dir = plugin_dir_path(__FILE__);
    
        // Assuming the main plugin file has the same name as the directory
        $plugin_file = $current_dir . basename($current_dir) . '.php';

         // Alternatively, if you know the exact name of the main plugin file but want it dynamic
    // $plugin_file = $current_dir . 'mysolidplugin.php';

    // Or if you want to dynamically determine the file name based on other criteria
    // For example, if the main plugin file has a known suffix or prefix
    // $plugin_file = $current_dir . 'main-file-name.php';
    
        // Fetch the plugin data from the main plugin file
        $plugin_data = get_file_data($plugin_file, array(
            'Version' => 'Version',
            'PluginRepository' => 'Plugin Repository',
            'UpdateURI' => 'Update URI',
            'TextDomain' => 'Text Domain',
        ), false);
    
        // Ensure the data has been correctly fetched
        $plugin_current_version = $plugin_data['Version'] ?? '';
        $plugin_repository = $plugin_data['PluginRepository'] ?? '';
        $update_uri = $plugin_data['UpdateURI'] ?? '';
        $text_domain = $plugin_data['TextDomain'] ?? '';
    
        // Define the plugin file path and transient key as a variable
        $plugin_file_path = $text_domain . '/' . $text_domain . '.php';
        $transient_key = 'update_plugins_' . $text_domain;
    
        // Check for updates from the GitHub repository
        $update = updater_check_for_updates($plugin_current_version, $plugin_repository, $plugin_file_path, $text_domain);
    
        if ($update) {
            $transient->response[$plugin_file_path] = (object) $update;
        } else {
            $item = (object) array(
                'id'            => $plugin_file_path,
                'slug'          => $text_domain,
                'plugin'        => $plugin_file_path,
                'new_version'   => $plugin_current_version,
                'url'           => '',
                'package'       => '',
                'icons'         => array(),
                'banners'       => array(),
                'banners_rtl'   => array(),
                'tested'        => '',
                'requires_php'  => '',
                'compatibility' => new stdClass(),
            );
            $transient->no_update[$plugin_file_path] = $item;
        }
    
        return $transient;
    }
    

add_filter('pre_set_site_transient_update_plugins', 'updater_pre_set_site_transient_update_plugins');

// Updated function definition to include $plugin_file_path and $text_domain
function updater_check_for_updates($plugin_current_version, $plugin_repository, $plugin_file_path, $text_domain) {
    // Make the API request to GitHub
    $response = wp_remote_get("https://api.github.com/repos/$plugin_repository/releases/latest", array(
        'headers' => array(
            'Accept'        => 'application/vnd.github.v3+json',
        )
    ));
  
    if (is_wp_error($response)) {
        return false;
    }
  
    $release = json_decode(wp_remote_retrieve_body($response));

    // Trim the 'v' from the tag name if it exists
    $release_version = isset($release->tag_name) ? ltrim($release->tag_name, 'v') : '';
    $last_updated = $release->published_at ?? 'Unknown';
    update_option($text_domain . '_last_updated', $last_updated);

    // Display the current version and the update version for testing
    //   echo '<div class="error" style="position: absolute; top:30px;left:40px;">Current Version: ' . $plugin_current_version . '<br>';
    
    
       if (isset($release->tag_name)) {
        echo 'Latest Release: ' . $release_version . '<br></div>';
    } else {
        echo 'No update version found<br></div>';
    };
    
    if (version_compare($plugin_current_version, $release_version, '<')) {
        // Use the first asset found
        if (!empty($release->assets)) {
            $asset = $release->assets[0]; // Get the first asset
            return array(
                'id'            => $plugin_file_path,
                'slug'          => $text_domain,
                'plugin'        => $plugin_file_path,
                'new_version'   => $release_version,
                'url'           => $release->html_url,
                'package'       => $asset->browser_download_url,
                'last_updated'  => $release->published_at ?? 'Unknown',
                'icons'         => array(), // Add icon URLs if you have them
                'banners'       => array(), // Add banner URLs if you have them
                'banners_rtl'   => array(), // Add RTL banner URLs if applicable
                'tested'        => '6.6.1', // Optionally add the latest WP version tested with
                'requires_php'  => '7.0', // Optionally add the minimum PHP version required
                'compatibility' => new stdClass(),
                'sections'      => array(
                 //   'description' => 'A plugin to manage email signatures. This plugin allows you to create and manage email signatures within your WordPress site.',
                 //   'changelog'   => '### [0.0.3] - 2024-08-15\n- Added new features.\n- Fixed bugs.',
                    'installation' => '1. Upload the plugin to your site.\n2. Activate the plugin through the "Plugins" menu in WordPress.',
                    'FAQ' => 'Yo, FAQ!', // Add other sections if needed (e.g., FAQ, support)
                ),
            );
        }
    }
    return false;
    
}

// delete_site_transient('update_plugins');