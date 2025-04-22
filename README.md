# GLA Orphaned Products Cleanup

A WordPress plugin that helps clean up orphaned products from Google Merchant Center that no longer exist in WooCommerce.

## Description

This plugin provides a simple interface to identify and remove products that exist in Google Merchant Center but no longer exist in your WooCommerce store. It's particularly useful when:

- Products have been deleted from WooCommerce but remain in Google Merchant Center
- You've migrated or restored your store and some products are missing
- You want to ensure your Google Merchant Center inventory matches your WooCommerce store

## Requirements

- WordPress 5.6 or later
- PHP 7.4 or later
- WooCommerce 5.0 or later
- WooCommerce Google Listings & Ads plugin

## Installation

1. Download the plugin files
2. Upload the `gla-orphaned-products-cleanup` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Make sure WooCommerce Google Listings & Ads is installed and active

## Usage

1. Go to WooCommerce > Cleanup Orphaned Products in your WordPress admin
2. Click the "Start Cleanup" button
3. The plugin will:
   - Find all products in Google Merchant Center
   - Compare them with your WooCommerce products
   - Identify products that exist in Google but not in WooCommerce
   - Delete the orphaned products from Google Merchant Center
4. You'll see a success message with the number of products deleted

## Notes

- The cleanup process is done in batches to avoid overwhelming the system
- You need the `manage_woocommerce` capability to use this tool
- The plugin requires WooCommerce Google Listings & Ads to be active
- Make sure you have a backup before running the cleanup

## Support

For support, please open an issue in the GitHub repository or contact WooCommerce support.

## License

This plugin is licensed under the GPL v2 or later. 