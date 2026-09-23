<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cbbe_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'coding-bunny-bulk-edit' ) ) {
		return;
	}

	$css_rel_path  = '../assets/css/cbbe-styles.css';
	$css_file_path = plugin_dir_path( __FILE__ ) . $css_rel_path;
	if ( file_exists( $css_file_path ) ) {
		$css_version = filemtime( $css_file_path );
		wp_enqueue_style(
			'cbbe-admin-styles',
			plugin_dir_url( __FILE__ ) . $css_rel_path,
			array(),
			$css_version
		);
	}

	$sortable_js_rel_path = '../assets/js/cbbe-sortable.js';
	$sortable_js_file_path = plugin_dir_path( __FILE__ ) .  $sortable_js_rel_path;
	if ( file_exists( $sortable_js_file_path ) ) {
		$sortable_js_version = filemtime( $sortable_js_file_path );
		wp_enqueue_script(
			'cbbe-sortable',
			plugin_dir_url( __FILE__ ) . $sortable_js_rel_path,
			array(),
			$sortable_js_version,
			true
		);
	}

	$js_rel_path   = '../assets/js/cbbe-scripts.js';
	$js_file_path  = plugin_dir_path( __FILE__ ) . $js_rel_path;
	if ( file_exists( $js_file_path ) ) {
		$js_version = filemtime( $js_file_path );
		if ( strpos( $hook, 'coding-bunny-bulk-edit' ) !== false ) {
			wp_enqueue_media();
		}
		wp_enqueue_script(
			'cbbe-admin-script',
			plugin_dir_url( __FILE__ ) . $js_rel_path,
			array( 'cbbe-sortable', 'jquery', 'media-editor' ),
			$js_version,
			true
		);

		wp_localize_script(
			'cbbe-admin-script',
			'cbbeL10n',
			array(
				'imageTitle'    => __( 'Select or upload image', 'coding-bunny-bulk-edit' ),
				'imageButton'   => __( 'Use this image', 'coding-bunny-bulk-edit' ),
				'galleryTitle'  => __( 'Select or upload images', 'coding-bunny-bulk-edit' ),
				'galleryButton' => __( 'Use these images', 'coding-bunny-bulk-edit' ),
			)
		);

		wp_localize_script(
			'cbbe-admin-script',
			'cbbeAjax',
			array(
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'bulkDeleteNonce'     => wp_create_nonce( 'cbbe_bulk_delete_action' ),
				'bulkDuplicateNonce'  => wp_create_nonce( 'cbbe_bulk_duplicate_action' ),
				'deleteProductNonce'  => wp_create_nonce( 'cbbe_delete_product_action' ),
				'addProductNonce'     => wp_create_nonce( 'cbbe_add_product_action' ),
				'saveOptionsNonce'    => wp_create_nonce( 'cbbe_save_options' ),
				'splitVariationsNonce' => wp_create_nonce( 'cbbe_split_variations_action' ),
			)
		);
	}
	
	if ( strpos( $hook, 'coding-bunny-bulk-edit' ) !== false ) {
		$tracker_rel_path  = '../assets/js/product-tracker.js';
		$tracker_file_path = plugin_dir_path( __FILE__ ) . $tracker_rel_path;

		if ( file_exists( $tracker_file_path ) ) {
			$tracker_version = filemtime( $tracker_file_path );
			wp_enqueue_script(
				'cbbe-product-tracker',
				plugin_dir_url( __FILE__ ) . $tracker_rel_path,
				array( 'jquery' ),
				$tracker_version,
				true
			);

			wp_localize_script(
				'cbbe-product-tracker',
				'cbbeTrackerConfig',
				array(
					'debug'          => defined( 'WP_DEBUG' ) && WP_DEBUG,
					'enableWarning'  => true,
					'i18n'           => array(
						'unsavedChanges'   => __( 'You have unsaved changes. Are you sure you want to leave?', 'coding-bunny-bulk-edit' ),
						'modifiedProducts' => __( 'Modified products:', 'coding-bunny-bulk-edit' ),
						'noChanges'        => __( 'No products have been modified.', 'coding-bunny-bulk-edit' ),
					),
				)
			);
		}
	}
}
add_action( 'admin_enqueue_scripts', 'cbbe_admin_assets' );