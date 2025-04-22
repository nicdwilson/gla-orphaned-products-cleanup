<?php
/**
 * Plugin Name: GLA Orphaned Products Cleanup
 * Plugin URI: https://woocommerce.com/
 * Description: A tool to clean up orphaned products from Google Merchant Center that no longer exist in WooCommerce.
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: gla-orphaned-products-cleanup
 * Requires at least: 5.6
 * Requires PHP: 7.4
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'GLA_ORPHANED_PRODUCTS_CLEANUP_FILE' ) ) {
    define( 'GLA_ORPHANED_PRODUCTS_CLEANUP_FILE', __FILE__ );
}

if ( ! defined( 'GLA_ORPHANED_PRODUCTS_CLEANUP_PATH' ) ) {
    define( 'GLA_ORPHANED_PRODUCTS_CLEANUP_PATH', plugin_dir_path( __FILE__ ) );
}

// Check if GLA is active
if ( ! class_exists( 'Automattic\WooCommerce\GoogleListingsAndAds\Plugin' ) ) {
    add_action(
        'admin_notices',
        function() {
            ?>
            <div class="notice notice-error">
                <p><?php esc_html_e( 'GLA Orphaned Products Cleanup requires WooCommerce Google Listings & Ads to be installed and active.', 'gla-orphaned-products-cleanup' ); ?></p>
            </div>
            <?php
        }
    );
    return;
}

// Load the cleanup class
require_once GLA_ORPHANED_PRODUCTS_CLEANUP_PATH . 'includes/class-gla-orphaned-products-cleanup.php';

// Initialize the plugin
add_action(
    'plugins_loaded',
    function() {
        new GLA_Orphaned_Products_Cleanup();
    }
); 