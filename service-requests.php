<?php
/**
 * Plugin Name: Service Requests Manager
 * Description: Multi-step service request form with admin dashboard, status tracking, and confirmation system.
 * Version: 1.0
 * Author: Subha
 */

if (!defined('ABSPATH')) exit;

// Define paths
define('SR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Includes
require_once SR_PLUGIN_DIR . 'includes/class-sr-db.php';
require_once SR_PLUGIN_DIR . 'includes/class-sr-frontend.php';
require_once SR_PLUGIN_DIR . 'includes/class-sr-ajax.php';
require_once SR_PLUGIN_DIR . 'includes/class-sr-admin.php';
require_once SR_PLUGIN_DIR . 'includes/class-sr-email.php';

// Activation hook
register_activation_hook(__FILE__, ['SR_DB', 'create_table']);

// Init classes
add_action('plugins_loaded', function () {
    new SR_Frontend();
    new SR_Ajax();
    new SR_Admin();
    new SR_Email();
});
