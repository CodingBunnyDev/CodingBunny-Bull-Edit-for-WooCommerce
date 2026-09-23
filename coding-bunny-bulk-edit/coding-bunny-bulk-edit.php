<?php

/**
 * Plugin Name: CodingBunny Bulk Edit for WooCommerce
 * Description: Quickly edit your e-commerce products.
 * Version:     2.2.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      CodingBunny
 * Text Domain: coding-bunny-bulk-edit
 * Domain Path: /languages
 * License: GNU General Public License v3.0 or later
 * WC tested up to: 11.1
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CBBE_VERSION', '2.2.0' );
define( 'CBBE_PLUGIN_FILE', __FILE__ );

class CodingBunnyBulkEdit {

	private $includes_dir;

	public function __construct() {
		$this->includes_dir = plugin_dir_path( __FILE__ ) . 'includes/';
		$this->register_hooks();
		$this->include_files();
	}

	private function register_hooks() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'admin_menu', [ $this, 'cbbe_menu' ] );
		add_action( 'wp_ajax_cbbe_save_options_auto', [ $this, 'ajax_save_options_auto' ] );
		add_action( 'admin_init', [ $this, 'cleanup_legacy_licensing' ] );
	}

	/**
	 * One-time cleanup of data left by the old licensing system.
	 */
	public function cleanup_legacy_licensing() {
		if ( get_option( 'cbbe_licensing_removed' ) ) {
			return;
		}

		wp_clear_scheduled_hook( 'cbbe_check_license' );

		$data = get_option( 'cbbe_licence_data' );
		if ( is_array( $data ) && ! empty( $data['key'] ) ) {
			$email  = isset( $data['email'] ) ? $data['email'] : '';
			$domain = wp_parse_url( get_site_url(), PHP_URL_HOST );
			delete_transient( 'cbbe_licence_validation_' . md5( $data['key'] . $email ) );
			delete_transient( 'cbbe_domain_status_' . md5( $data['key'] . $domain ) );
		}
		delete_option( 'cbbe_licence_data' );
		delete_transient( 'cbbe_deactivated_addons_checked' );

		update_option( 'cbbe_licensing_removed', 1, false );
	}

	public function cbbe_menu() {
		add_menu_page(
			esc_html__( 'CodingBunny Bulk Edit for WooCommerce', 'coding-bunny-bulk-edit' ),
			esc_html__( 'Bulk Edit', 'coding-bunny-bulk-edit' ),
			'manage_woocommerce',
			'coding-bunny-bulk-edit',
			'cbbe_settings_page',
			$this->get_icon_data_uri(),
			56
		);
	}

	private function get_icon_data_uri() {
		$icon_path = plugin_dir_path( __FILE__ ) . 'assets/images/cbbe-icon.svg';
		if ( file_exists( $icon_path ) ) {
			return 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $icon_path ) );
		}
		return '';
	}

	public function load_textdomain() {
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
		load_plugin_textdomain( 'coding-bunny-bulk-edit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	private function include_files() {
		$files_to_include = [
			'product-table.php',
			'admin-options.php',			
			'enqueue-scripts.php',
			'functions/update.php',
			'functions/product-actions.php',
		];

		foreach ( $files_to_include as $file ) {
			$file_path = $this->includes_dir . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	public function ajax_save_options_auto() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized', 'coding-bunny-bulk-edit' ) ] );
		}

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'cbbe_save_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce', 'coding-bunny-bulk-edit' ) ] );
		}

		if ( ! function_exists( 'cbbe_sanitize_custom_fields' ) || ! function_exists( 'cbbe_get_all_columns' ) || ! function_exists( 'cbbe_get_bulk_fields' ) ) {
			wp_send_json_error( [ 'message' => __( 'Required functions not loaded', 'coding-bunny-bulk-edit' ) ] );
		}

		$custom_fields_opt = cbbe_sanitize_custom_fields( get_option( 'cbbe_custom_fields', [] ) );
		$all_columns = cbbe_get_all_columns( $custom_fields_opt );
		$bulk_fields = cbbe_get_bulk_fields( $custom_fields_opt );

		$columns = isset( $_POST['columns'] ) ?  array_map( 'sanitize_text_field', wp_unslash( $_POST['columns'] ) ) : array_keys( $all_columns );
		$order = isset( $_POST['order'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['order'] ) ) : array_keys( $all_columns );
		update_option( 'cbbe_columns', $columns );
		update_option( 'cbbe_order', $order );

		$enabled = isset( $_POST['bulk_enabled_fields'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bulk_enabled_fields'] ) ) : [];
		$bulk_order = isset( $_POST['bulk_order'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bulk_order'] ) ) : array_keys( $bulk_fields );
		update_option( 'cbbe_bulk_enabled_fields', $enabled );
		update_option( 'cbbe_bulk_fields_order', $bulk_order );

		$pagination_products = isset( $_POST['pagination_products'] ) ? intval( wp_unslash( $_POST['pagination_products'] ) ) : 50;
		$table_height = isset( $_POST['table_height'] ) ? intval( wp_unslash( $_POST['table_height'] ) ) : 440;
		$hide_bulk_edit_section = isset( $_POST['hide_bulk_edit_section'] ) ?  1 : 0;
		update_option( 'cbbe_pagination_products', $pagination_products );
		update_option( 'cbbe_table_height', $table_height );
		update_option( 'cbbe_hide_bulk_edit_section', $hide_bulk_edit_section );

		wp_send_json_success( [ 'message' => __( 'Settings saved', 'coding-bunny-bulk-edit' ) ] );
	}
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

global $cbbe_plugin_instance;
$cbbe_plugin_instance = new CodingBunnyBulkEdit();