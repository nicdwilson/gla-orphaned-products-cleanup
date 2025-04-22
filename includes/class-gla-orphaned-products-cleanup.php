<?php
/**
 * Class GLA_Orphaned_Products_Cleanup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */

class GLA_Orphaned_Products_Cleanup {
    /**
     * @var \Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService
     */
    protected $google_product_service;

    /**
     * @var \Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository
     */
    protected $product_repository;

    /**
     * @var \Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper
     */
    protected $product_helper;

    /**
     * @var \Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer
     */
    protected $product_syncer;

    /**
     * Constructor.
     */
    public function __construct() {
        // Get GLA container
        $gla_container = \Automattic\WooCommerce\GoogleListingsAndAds\Plugin::instance()->get_container();

        // Get required services
        $this->google_product_service = $gla_container->get( \Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService::class );
        $this->product_repository = $gla_container->get( \Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository::class );
        $this->product_helper = $gla_container->get( \Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper::class );
        $this->product_syncer = $gla_container->get( \Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer::class );

        // Add admin menu
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

        // Register AJAX handler
        add_action( 'wp_ajax_gla_cleanup_orphaned_products', [ $this, 'handle_cleanup_request' ] );
    }

    /**
     * Add admin menu item.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Cleanup Orphaned Products', 'gla-orphaned-products-cleanup' ),
            __( 'Cleanup Orphaned Products', 'gla-orphaned-products-cleanup' ),
            'manage_woocommerce',
            'gla-orphaned-products-cleanup',
            [ $this, 'render_admin_page' ]
        );
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Cleanup Orphaned Products', 'gla-orphaned-products-cleanup' ); ?></h1>
            
            <div class="card">
                <h2><?php esc_html_e( 'About', 'gla-orphaned-products-cleanup' ); ?></h2>
                <p><?php esc_html_e( 'This tool will help you clean up products that exist in Google Merchant Center but no longer exist in your WooCommerce store.', 'gla-orphaned-products-cleanup' ); ?></p>
                
                <h2><?php esc_html_e( 'How it works', 'gla-orphaned-products-cleanup' ); ?></h2>
                <ol>
                    <li><?php esc_html_e( 'Finds all products in Google Merchant Center', 'gla-orphaned-products-cleanup' ); ?></li>
                    <li><?php esc_html_e( 'Compares them with your WooCommerce products', 'gla-orphaned-products-cleanup' ); ?></li>
                    <li><?php esc_html_e( 'Identifies products that exist in Google but not in WooCommerce', 'gla-orphaned-products-cleanup' ); ?></li>
                    <li><?php esc_html_e( 'Deletes the orphaned products from Google Merchant Center', 'gla-orphaned-products-cleanup' ); ?></li>
                </ol>

                <div id="cleanup-status"></div>
                <button type="button" class="button button-primary" id="start-cleanup">
                    <?php esc_html_e( 'Start Cleanup', 'gla-orphaned-products-cleanup' ); ?>
                </button>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#start-cleanup').on('click', function() {
                    var $button = $(this);
                    var $status = $('#cleanup-status');
                    
                    $button.prop('disabled', true);
                    $status.html('<p><?php esc_html_e( 'Starting cleanup...', 'gla-orphaned-products-cleanup' ); ?></p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'gla_cleanup_orphaned_products',
                            nonce: '<?php echo wp_create_nonce( 'gla_cleanup_orphaned_products' ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $status.html('<p class="notice notice-success">' + response.data.message + '</p>');
                            } else {
                                $status.html('<p class="notice notice-error">' + response.data.message + '</p>');
                            }
                        },
                        error: function() {
                            $status.html('<p class="notice notice-error"><?php esc_html_e( 'An error occurred while processing the request.', 'gla-orphaned-products-cleanup' ); ?></p>');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Handle cleanup request.
     */
    public function handle_cleanup_request() {
        check_ajax_referer( 'gla_cleanup_orphaned_products', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'gla-orphaned-products-cleanup' ) ] );
        }

        try {
            // Get all products from Google Merchant Center
            $google_products = $this->google_product_service->get_products();
            
            // Get all WooCommerce product IDs
            $wc_product_ids = $this->product_repository->find_all_product_ids();
            
            // Find orphaned products
            $orphaned_products = [];
            foreach ( $google_products as $google_product ) {
                $wc_id = $this->product_helper->get_wc_product_id_from_google_id( $google_product->getId() );
                if ( ! $wc_id || ! in_array( $wc_id, $wc_product_ids ) ) {
                    $orphaned_products[] = $google_product->getId();
                }
            }

            if ( empty( $orphaned_products ) ) {
                wp_send_json_success( [ 'message' => __( 'No orphaned products found.', 'gla-orphaned-products-cleanup' ) ] );
                return;
            }

            // Delete orphaned products in batches
            $batch_size = 50;
            $batches = array_chunk( $orphaned_products, $batch_size );
            $deleted_count = 0;

            foreach ( $batches as $batch ) {
                $product_entries = [];
                foreach ( $batch as $google_id ) {
                    $product_entries[] = new \Automattic\WooCommerce\GoogleListingsAndAds\BatchProductIDRequestEntry( $google_id );
                }
                $this->product_syncer->delete_by_batch_requests( $product_entries );
                $deleted_count += count( $batch );
            }

            wp_send_json_success( [
                'message' => sprintf(
                    __( 'Successfully deleted %d orphaned products from Google Merchant Center.', 'gla-orphaned-products-cleanup' ),
                    $deleted_count
                )
            ] );

        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }
} 