<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cbbe_add_product_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cbbe_add_product_action' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'coding-bunny-bulk-edit' ) ) );
		return;
	}

	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to create products.', 'coding-bunny-bulk-edit' ) ) );
		return;
	}

	$product_name  = isset( $_POST['product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['product_name'] ) ) : '';
	$product_type  = isset( $_POST['product_type'] ) ? sanitize_text_field( wp_unslash( $_POST['product_type'] ) ) : 'simple';
	$regular_price = isset( $_POST['regular_price'] ) && $_POST['regular_price'] !== '' ? floatval( wp_unslash( $_POST['regular_price'] ) ) : '';

	$attributes = array();
	if ( isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ) {
		$attributes_raw = map_deep(
		wp_unslash( $_POST['attributes'] ),
		'sanitize_text_field'
	);
	foreach ( $attributes_raw as $taxonomy => $term_ids ) {
		$taxonomy_clean = sanitize_text_field( $taxonomy );
		if ( is_array( $term_ids ) ) {
			$attributes[ $taxonomy_clean ] = array_map( 'intval', $term_ids );
		}
	}
}

if ( empty( $product_name ) ) {
	wp_send_json_error( array( 'message' => __( 'Product name is required.', 'coding-bunny-bulk-edit' ) ) );
	return;
}

try {
	$product = null;

	if ( 'variable' === $product_type ) {
		$product = new WC_Product_Variable();
	} elseif ( 'downloadable' === $product_type ) {
		$product = new WC_Product_Simple();
		$product->set_downloadable( true );
		$product->set_virtual( true );
	} else {
		$product = new WC_Product_Simple();
	}

	$product->set_name( $product_name );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );

	if ( 'variable' !== $product_type && $regular_price !== '' ) {
		$product->set_regular_price( $regular_price );
	}

	$product_id = $product->save();

	if ( ! $product_id ) {
		throw new Exception( __( 'Failed to create product.', 'coding-bunny-bulk-edit' ) );
	}

	if ( 'variable' === $product_type && ! empty( $attributes ) ) {
		$product_attributes = array();
		$variation_data    = array();

		foreach ( $attributes as $taxonomy => $term_ids ) {
			if ( empty( $term_ids ) ) {
				continue;
			}

			$result = wp_set_object_terms( $product_id, $term_ids, $taxonomy );
			if ( is_wp_error( $result ) ) {
				continue;
			}

			$attribute = new WC_Product_Attribute();
			$attribute->set_id( function_exists( 'wc_attribute_taxonomy_id_by_name' ) ? wc_attribute_taxonomy_id_by_name( $taxonomy ) : 0 );
			$attribute->set_name( $taxonomy );
			$attribute->set_visible( true );
			$attribute->set_variation( true );

			$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'include'    => $term_ids,
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$term_slugs = wp_list_pluck( $terms, 'slug' );
			$attribute->set_options( $term_slugs );
			$product_attributes[ $taxonomy ] = $attribute;
			$variation_data[ $taxonomy ]    = $term_slugs;
		}
	}

	if ( ! empty( $product_attributes ) ) {
		$product->set_attributes( $product_attributes );
		$product->save();

		$product = wc_get_product( $product_id );

		$variations         = cbbe_generate_variations( $variation_data );
		$created_variations = 0;
		$variation_ids      = array();

		foreach ( $variations as $variation_attributes ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_attributes( $variation_attributes );
			$variation->set_status( 'publish' );
			$variation->set_manage_stock( false );
			$variation->set_stock_status( 'instock' );

			if ( $regular_price !== '' ) {
				$variation->set_regular_price( $regular_price );
			}

			$variation_id = $variation->save();

			if ( $variation_id ) {
				$created_variations++;
				$variation_ids[] = $variation_id;

				update_post_meta( $variation_id, '_stock_status', 'instock' );
				update_post_meta( $variation_id, '_manage_stock', 'no' );
				update_post_meta( $variation_id, '_thumbnail_id', 0 );

				clean_post_cache( $variation_id );
			}
		}

		if ( ! empty( $variation_ids ) ) {
			WC_Product_Variable::sync( $product_id );

			wc_delete_product_transients( $product_id );
			clean_post_cache( $product_id );

			foreach ( $variation_ids as $var_id ) {
				wc_delete_product_transients( $var_id );
				clean_post_cache( $var_id );
			}

			wp_cache_flush();
		}

		wp_send_json_success(
		array(
			/* translators: %d: number of variations created */
			'message'          => sprintf( __( 'Variable product created successfully with %d variations!', 'coding-bunny-bulk-edit' ), $created_variations ),
			'product_id'       => $product_id,
			'variations_count' => $created_variations,
			'variation_ids'    => $variation_ids,
			'edit_url'         => get_edit_post_link( $product_id, 'raw' ),
			)
		);
	}
}

wp_send_json_success(
array(
	'message'    => __( 'Product created successfully!', 'coding-bunny-bulk-edit' ),
	'product_id' => $product_id,
	'edit_url'   => get_edit_post_link( $product_id, 'raw' ),
	)
);
} catch ( Exception $e ) {
	/* translators: %d: number of error */
	wp_send_json_error( array( 'message' => sprintf( __( 'Error: %s', 'coding-bunny-bulk-edit' ), $e->getMessage() ) ) );
}
}
if ( ! has_action( 'wp_ajax_cbbe_add_product', 'cbbe_add_product_ajax' ) ) {
add_action( 'wp_ajax_cbbe_add_product', 'cbbe_add_product_ajax' );
}

function cbbe_generate_variations( $attributes ) {
if ( empty( $attributes ) ) {
	return array();
}

$combinations = array( array() );

foreach ( $attributes as $attribute_name => $values ) {
	$temp = array();
	foreach ( $combinations as $combination ) {
		foreach ( $values as $value ) {
			$temp[] = array_merge( $combination, array( $attribute_name => $value ) );
		}
	}
	$combinations = $temp;
}

return $combinations;
}

function cbbe_perform_delete_ids( $product_ids ) {
$deleted         = 0;
$deleted_ids     = array();
$parents_to_sync = array();

foreach ( $product_ids as $pid ) {
	$pid = intval( $pid );
	if ( $pid <= 0 ) {
		continue;
	}

	$post = get_post( $pid );
	if ( ! $post ) {
		continue;
	}

	$is_variation = ( 'product_variation' === $post->post_type ) || ( function_exists( 'wc_get_product' ) && wc_get_product( $pid ) && wc_get_product( $pid )->is_type( 'variation' ) );

	try {
		$res = wp_trash_post( $pid );
		if ( $res ) {
			$deleted++;
			$deleted_ids[] = $pid;
			wc_delete_product_transients( $pid );
			clean_post_cache( $pid );

			if ( $is_variation ) {
				$parent_id = get_post_meta( $pid, '_parent_id', true );
				if ( ! $parent_id ) {
					$prod = wc_get_product( $pid );
					if ( $prod && method_exists( $prod, 'get_parent_id' ) ) {
						$parent_id = $prod->get_parent_id();
					}
				}
				if ( $parent_id ) {
					$parents_to_sync[] = intval( $parent_id );
				}
			}
		}
	} catch ( Exception $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'CBBE: failed to trash product %d - %s', $pid, $e->getMessage() ) );
		}
	}
}

$parents_to_sync = array_unique( array_filter( array_map( 'intval', $parents_to_sync ) ) );
foreach ( $parents_to_sync as $parent_id ) {
	$parent_product = wc_get_product( $parent_id );
	if ( $parent_product && $parent_product->is_type( 'variable' ) ) {
		WC_Product_Variable::sync( $parent_id );
		wc_delete_product_transients( $parent_id );
	}
}

return array(
	'deleted'         => $deleted,
	'deleted_ids'     => $deleted_ids,
	'parents_to_sync' => $parents_to_sync,
);
}

function cbbe_delete_product_ajax() {
if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cbbe_delete_product_action' ) ) {
	wp_send_json_error( array( 'message' => __( 'Security check failed.', 'coding-bunny-bulk-edit' ) ) );
	return;
}

if ( ! current_user_can( 'delete_products' ) ) {
	wp_send_json_error( array( 'message' => __( 'You do not have permission to delete products.', 'coding-bunny-bulk-edit' ) ) );
	return;
}

$product_id = isset( $_POST['product_id'] ) ? intval( wp_unslash( $_POST['product_id'] ) ) : 0;
if ( $product_id <= 0 ) {
	wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'coding-bunny-bulk-edit' ) ) );
	return;
}

$result = cbbe_perform_delete_ids( array( $product_id ) );

if ( $result['deleted'] > 0 ) {
	wp_send_json_success(
	array(
		'message'    => __( 'Product moved to trash.', 'coding-bunny-bulk-edit' ),
		'product_id' => $product_id,
		'parent_id'  => isset( $result['parents_to_sync'][0] ) ? $result['parents_to_sync'][0] : 0,
		)
	);
}

wp_send_json_error( array( 'message' => __( 'Failed to move product to trash.', 'coding-bunny-bulk-edit' ) ) );
}
if ( ! has_action( 'wp_ajax_cbbe_delete_product', 'cbbe_delete_product_ajax' ) ) {
add_action( 'wp_ajax_cbbe_delete_product', 'cbbe_delete_product_ajax' );
}

function cbbe_bulk_delete_ajax() {
$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'cbbe_bulk_delete_action' ) ) {
	wp_send_json_error( array( 'message' => __( 'Security check failed.', 'coding-bunny-bulk-edit' ) ) );
	return;
}

if ( ! current_user_can( 'delete_products' ) ) {
	wp_send_json_error( array( 'message' => __( 'You do not have permission to delete products.', 'coding-bunny-bulk-edit' ) ) );
	return;
}

if ( empty( $_POST['product_ids'] ) || ! is_array( $_POST['product_ids'] ) ) {
	wp_send_json_error( array( 'message' => __( 'No products selected.', 'coding-bunny-bulk-edit' ) ) );
	return;
}

$product_ids = array_map( 'intval', wp_unslash( $_POST['product_ids'] ) );
$result      = cbbe_perform_delete_ids( $product_ids );

/* translators: %d: number of products moved to trash */
$message = sprintf( _n( '%d product moved to trash.', '%d products moved to trash.', $result['deleted'], 'coding-bunny-bulk-edit' ), $result['deleted'] );

wp_send_json_success( array( 'message' => $message, 'deleted' => $result['deleted'], 'deleted_ids' => $result['deleted_ids'] ) );
}
if ( ! has_action( 'wp_ajax_cbbe_bulk_delete', 'cbbe_bulk_delete_ajax' ) ) {
add_action( 'wp_ajax_cbbe_bulk_delete', 'cbbe_bulk_delete_ajax' );
}

function cbbe_duplicate_simple_product( $orig_id ) {
$orig_post = get_post( $orig_id );
if ( ! $orig_post || 'product' !== $orig_post->post_type ) {
	return new WP_Error( 'invalid_product', __( 'Invalid product', 'coding-bunny-bulk-edit' ) );
}

$new_post = array(
	'post_author'  => get_current_user_id(),
	'post_content' => $orig_post->post_content,
	'post_excerpt' => $orig_post->post_excerpt,
	'post_status'  => 'draft',
	'post_title'   => $orig_post->post_title . ' (' . __( 'Copy', 'coding-bunny-bulk-edit' ) . ')',
	'post_type'    => 'product',
);

$new_id = wp_insert_post( $new_post );
if ( ! $new_id || is_wp_error( $new_id ) ) {
	return new WP_Error( 'insert_failed', __( 'Failed to create product copy', 'coding-bunny-bulk-edit' ) );
}

$taxonomies = get_object_taxonomies( 'product' );
foreach ( $taxonomies as $tax ) {
	$terms = wp_get_post_terms( $orig_id, $tax, array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		wp_set_object_terms( $new_id, $terms, $tax );
	}
}

$meta = get_post_meta( $orig_id );
if ( is_array( $meta ) ) {
	$blacklist = array( '_edit_lock', '_edit_last' );
	foreach ( $meta as $meta_key => $meta_values ) {
		if ( in_array( $meta_key, $blacklist, true ) ) {
			continue;
		}
		foreach ( $meta_values as $value ) {
			add_post_meta( $new_id, $meta_key, maybe_unserialize( $value ) );
		}
	}
}

clean_post_cache( $new_id );
wc_delete_product_transients( $new_id );

return $new_id;
}

function cbbe_duplicate_product_with_variations( $orig_id ) {
$orig_post    = get_post( $orig_id );
if ( ! $orig_post || 'product' !== $orig_post->post_type ) {
	return new WP_Error( 'invalid_product', __( 'Invalid product', 'coding-bunny-bulk-edit' ) );
}

$orig_product = wc_get_product( $orig_id );
if ( ! $orig_product ) {
	return new WP_Error( 'no_product', __( 'Product not found', 'coding-bunny-bulk-edit' ) );
}

$new_parent = new WC_Product_Variable();
$new_parent->set_name( $orig_product->get_name() );
$new_parent->set_status( 'draft' );
$new_parent->set_catalog_visibility( $orig_product->get_catalog_visibility() );
$new_parent->set_description( $orig_post->post_content );
$new_parent->set_short_description( $orig_post->post_excerpt );

$new_parent_id = $new_parent->save();
if ( ! $new_parent_id ) {
	return new WP_Error( 'insert_failed', __( 'Failed to create product copy', 'coding-bunny-bulk-edit' ) );
}

$taxonomies = get_object_taxonomies( 'product' );
foreach ( $taxonomies as $tax ) {
	$terms = wp_get_post_terms( $orig_id, $tax, array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		wp_set_object_terms( $new_parent_id, $terms, $tax );
	}
}

$product_attributes_meta = get_post_meta( $orig_id, '_product_attributes', true );
$attributes_objects      = array();
if ( ! empty( $product_attributes_meta ) && is_array( $product_attributes_meta ) ) {
	update_post_meta( $new_parent_id, '_product_attributes', $product_attributes_meta );

	foreach ( $product_attributes_meta as $attr_key => $attr_data ) {
		$attribute = new WC_Product_Attribute();

		$tax_name = $attr_key;
		if ( isset( $attr_data['id'] ) ) {
			$attribute->set_id( intval( $attr_data['id'] ) );
		} else {
			if ( function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
				$attribute->set_id( wc_attribute_taxonomy_id_by_name( $tax_name ) );
			}
		}

		$attribute->set_name( $tax_name );

		$options = array();
		if ( isset( $attr_data['options'] ) && is_array( $attr_data['options'] ) ) {
			$options = $attr_data['options'];
		} elseif ( isset( $attr_data['value'] ) && $attr_data['value'] !== '' ) {
			$options = is_array( $attr_data['value'] ) ? $attr_data['value'] : array_map( 'trim', explode( ',', $attr_data['value'] ) );
		}

		$attribute->set_options( $options );
		$attribute->set_visible( isset( $attr_data['is_visible'] ) ? (bool) $attr_data['is_visible'] : true );
		$attribute->set_variation( isset( $attr_data['is_variation'] ) ? (bool) $attr_data['is_variation'] : false );

		$attributes_objects[ $tax_name ] = $attribute;
	}

	$_new_parent_obj = wc_get_product( $new_parent_id );
	if ( $_new_parent_obj && ! empty( $attributes_objects ) ) {
		$_new_parent_obj->set_attributes( $attributes_objects );
		$_new_parent_obj->save();
	}
}

wp_set_object_terms( $new_parent_id, 'variable', 'product_type' );

$parent_thumb_id = get_post_meta( $orig_id, '_thumbnail_id', true );
if ( $parent_thumb_id ) {
	update_post_meta( $new_parent_id, '_thumbnail_id', intval( $parent_thumb_id ) );
	$_prod = wc_get_product( $new_parent_id );
	if ( $_prod ) {
		$_prod->set_image_id( intval( $parent_thumb_id ) );
		$_prod->save();
	}
}
$parent_gallery = get_post_meta( $orig_id, '_product_image_gallery', true );
if ( $parent_gallery ) {
	update_post_meta( $new_parent_id, '_product_image_gallery', sanitize_text_field( $parent_gallery ) );
}

$meta = get_post_meta( $orig_id );
if ( is_array( $meta ) ) {
	$blacklist = array( '_edit_lock', '_edit_last', '_thumbnail_id', '_product_image_gallery' );
	foreach ( $meta as $meta_key => $meta_values ) {
		if ( in_array( $meta_key, $blacklist, true ) ) {
			continue;
		}
		foreach ( $meta_values as $value ) {
			add_post_meta( $new_parent_id, $meta_key, maybe_unserialize( $value ) );
		}
	}
}

$variation_ids          = $orig_product->get_children();
$created_variation_ids  = array();

if ( ! empty( $variation_ids ) ) {
	foreach ( $variation_ids as $vid ) {
		$variation = wc_get_product( $vid );
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			continue;
		}

		$variation_attributes = $variation->get_attributes();

		$new_variation = new WC_Product_Variation();
		$new_variation->set_parent_id( $new_parent_id );
		$new_variation->set_status( $variation->get_status() ?: 'publish' );
		$new_variation->set_sku( $variation->get_sku() );
		$new_variation->set_regular_price( $variation->get_regular_price() );
		$new_variation->set_sale_price( $variation->get_sale_price() );
		$new_variation->set_manage_stock( $variation->get_manage_stock() );
		if ( $variation->get_manage_stock() ) {
			$new_variation->set_stock_quantity( $variation->get_stock_quantity() );
		}
		$new_variation->set_stock_status( $variation->get_stock_status() );
		$new_variation->set_tax_class( $variation->get_tax_class() );

		$new_variation->set_attributes( $variation_attributes );

		$new_vid = $new_variation->save();
		if ( ! $new_vid ) {
			continue;
		}

		$thumb_id = get_post_meta( $vid, '_thumbnail_id', true );
		if ( $thumb_id ) {
			update_post_meta( $new_vid, '_thumbnail_id', intval( $thumb_id ) );
			$_v = wc_get_product( $new_vid );
			if ( $_v ) {
				$_v->set_image_id( intval( $thumb_id ) );
				$_v->save();
			}
		}

		$gallery = get_post_meta( $vid, '_product_image_gallery', true );
		if ( $gallery ) {
			update_post_meta( $new_vid, '_product_image_gallery', sanitize_text_field( $gallery ) );
		}

		$meta_v = get_post_meta( $vid );
		if ( is_array( $meta_v ) ) {
			$blacklist_v = array( '_edit_lock', '_edit_last', '_variation_description', '_thumbnail_id', '_product_image_gallery' );
			foreach ( $meta_v as $meta_key => $meta_values ) {
				if ( in_array( $meta_key, $blacklist_v, true ) ) {
					continue;
				}
				foreach ( $meta_values as $mval ) {
					add_post_meta( $new_vid, $meta_key, maybe_unserialize( $mval ) );
				}
			}
		}

		$created_variation_ids[] = $new_vid;
	}

	if ( ! empty( $created_variation_ids ) ) {
		WC_Product_Variable::sync( $new_parent_id );
	}
}

clean_post_cache( $new_parent_id );
wc_delete_product_transients( $new_parent_id );
if ( ! empty( $created_variation_ids ) ) {
	foreach ( $created_variation_ids as $cvid ) {
		wc_delete_product_transients( $cvid );
		clean_post_cache( $cvid );
	}
}

return $new_parent_id;
}

function cbbe_bulk_duplicate_ajax() {
$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'cbbe_bulk_duplicate_action' ) ) {
	wp_send_json_error( array( 'message' => __( 'Security check failed.', 'coding-bunny-bulk-edit' ) ) );
}

if ( ! current_user_can( 'edit_products' ) ) {
	wp_send_json_error( array( 'message' => __( 'You do not have permission to duplicate products.', 'coding-bunny-bulk-edit' ) ) );
}

if ( empty( $_POST['product_ids'] ) || ! is_array( $_POST['product_ids'] ) ) {
	wp_send_json_error( array( 'message' => __( 'No products selected.', 'coding-bunny-bulk-edit' ) ) );
}

$product_ids = array_map( 'intval', wp_unslash( $_POST['product_ids'] ) );
$created     = 0;
$skipped     = 0;
$mapping     = array();

foreach ( $product_ids as $pid ) {
	$orig_post = get_post( $pid );
	if ( ! $orig_post || 'product' !== $orig_post->post_type ) {
		continue;
	}

	$existing = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => array( 'draft', 'publish', 'pending', 'private' ),
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'meta_query'     => array(
			array(
				'key'   => '_cbbe_duplicated_from',
				'value' => $pid,
			),
		),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);

if ( ! empty( $existing ) ) {
	$skipped++;
	continue;
}

$prod = wc_get_product( $pid );
if ( $prod && $prod->is_type( 'variable' ) ) {
	$new_id = cbbe_duplicate_product_with_variations( $pid );
} else {
	$new_id = cbbe_duplicate_simple_product( $pid );
}

if ( is_wp_error( $new_id ) || ! $new_id ) {
	continue;
}

add_post_meta( $new_id, '_cbbe_duplicated_from', $pid );
$created++;
$mapping[ $pid ] = $new_id;
}

$message = sprintf(
/* translators: %1d: number of products product duplicated; %2d: number of products product skipped; */
_n( '%1$d product duplicated, %2$d skipped.', '%1$d products duplicated, %2$d skipped.', $created, 'coding-bunny-bulk-edit' ),
$created,
$skipped
);

wp_send_json_success( array( 'message' => $message, 'created' => $created, 'skipped' => $skipped, 'mapping' => $mapping, 'reload' => true ) );
}
if ( ! has_action( 'wp_ajax_cbbe_bulk_duplicate', 'cbbe_bulk_duplicate_ajax' ) ) {
add_action( 'wp_ajax_cbbe_bulk_duplicate', 'cbbe_bulk_duplicate_ajax' );
}

function cbbe_split_variations_ajax() {
if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cbbe_split_variations_action' ) ) {
wp_send_json_error( array( 'message' => __( 'Security check failed.', 'coding-bunny-bulk-edit' ) ) );
return;
}

if ( ! current_user_can( 'edit_products' ) ) {
wp_send_json_error( array( 'message' => __( 'You do not have permission to split variations.', 'coding-bunny-bulk-edit' ) ) );
return;
}

$parent_id = isset( $_POST['parent_id'] ) ? intval( wp_unslash( $_POST['parent_id'] ) ) : 0;
if ( $parent_id <= 0 ) {
wp_send_json_error( array( 'message' => __( 'Invalid parent product ID.', 'coding-bunny-bulk-edit' ) ) );
return;
}

$parent = wc_get_product( $parent_id );
if ( ! $parent || ! $parent->is_type( 'variable' ) ) {
wp_send_json_error( array( 'message' => __( 'Parent is not a variable product.', 'coding-bunny-bulk-edit' ) ) );
return;
}

$variation_ids = $parent->get_children();
if ( empty( $variation_ids ) ) {
wp_send_json_error( array( 'message' => __( 'No variations found for this product.', 'coding-bunny-bulk-edit' ) ) );
return;
}

$created     = 0;
$created_ids = array();
$split_taxonomies = get_object_taxonomies( 'product' );

foreach ( $variation_ids as $vid ) {
$variation = wc_get_product( $vid );
if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
continue;
}

$new_product = new WC_Product_Simple();

$name = $parent->get_name();
$attr_parts = array();
$variation_attributes = $variation->get_attributes();
if ( ! empty( $variation_attributes ) ) {
foreach ( $variation_attributes as $attr_key => $attr_val ) {
	if ( '' === $attr_val || null === $attr_val ) {
		continue;
	}
	$attr_taxonomy = $attr_key;
	if ( 0 === strpos( $attr_taxonomy, 'attribute_' ) ) {
		$attr_taxonomy = substr( $attr_taxonomy, 10 );
	}
	$label = $attr_val;
	if ( taxonomy_exists( $attr_taxonomy ) ) {
		$attr_term = get_term_by( 'slug', $attr_val, $attr_taxonomy );
		if ( ! $attr_term ) {
			$attr_term = get_term_by( 'name', $attr_val, $attr_taxonomy );
		}
		if ( $attr_term ) {
			$label = $attr_term->name;
		}
	}
	$attr_parts[] = $label;
}
if ( ! empty( $attr_parts ) ) {
	$name .= ' - ' . implode( ' / ', $attr_parts );
}
}
$new_product->set_name( $name );

$sku = $variation->get_sku();
if ( $sku ) {
$new_product->set_sku( $sku );
}

$regular = $variation->get_regular_price();
$sale    = $variation->get_sale_price();
if ( '' !== $regular ) {
$new_product->set_regular_price( $regular );
}
if ( '' !== $sale ) {
$new_product->set_sale_price( $sale );
}

$new_product->set_manage_stock( $variation->get_manage_stock() );
if ( $variation->get_manage_stock() ) {
$new_product->set_stock_quantity( $variation->get_stock_quantity() );
}
$new_product->set_stock_status( $variation->get_stock_status() );

$new_product->set_tax_class( $variation->get_tax_class() );

$new_id = $new_product->save();
if ( ! $new_id ) {
continue;
}

$thumb_id = get_post_meta( $vid, '_thumbnail_id', true );
if ( $thumb_id ) {
update_post_meta( $new_id, '_thumbnail_id', intval( $thumb_id ) );
$_new_prod_obj = wc_get_product( $new_id );
if ( $_new_prod_obj ) {
	$_new_prod_obj->set_image_id( intval( $thumb_id ) );
	$_new_prod_obj->save();
}
}

$gallery = get_post_meta( $vid, '_product_image_gallery', true );
if ( $gallery ) {
update_post_meta( $new_id, '_product_image_gallery', sanitize_text_field( $gallery ) );
}

foreach ( $split_taxonomies as $tax ) {
$terms = wp_get_post_terms( $vid, $tax, array( 'fields' => 'ids' ) );
if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
	wp_set_object_terms( $new_id, $terms, $tax );
}
}

$product_attributes_objects = array();
if ( ! empty( $variation_attributes ) ) {
foreach ( $variation_attributes as $attr_key => $attr_value ) {
	$taxonomy = $attr_key;
	if ( 0 === strpos( $taxonomy, 'attribute_' ) ) {
		$taxonomy = substr( $taxonomy, 10 );
	}
	if ( '' === $attr_value || null === $attr_value ) {
		continue;
	}
	if ( taxonomy_exists( $taxonomy ) ) {
		$values = is_array( $attr_value ) ? $attr_value : explode( ',', $attr_value );
		$term_ids = array();
		$term_slugs = array();
		foreach ( $values as $val ) {
			$val = trim( $val );
			if ( '' === $val ) {
				continue;
			}
			$term = get_term_by( 'slug', $val, $taxonomy );
			if ( ! $term ) {
				$term = get_term_by( 'name', $val, $taxonomy );
			}
			if ( $term ) {
				$term_ids[] = intval( $term->term_id );
				$term_slugs[] = $term->slug;
			}
		}
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $new_id, $term_ids, $taxonomy );
		}
		$attribute_obj = new WC_Product_Attribute();
		$taxonomy_id = 0;
		if ( function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
			$taxonomy_id = wc_attribute_taxonomy_id_by_name( $taxonomy );
		}
		$attribute_obj->set_id( $taxonomy_id );
		$attribute_obj->set_name( $taxonomy );
		$attribute_obj->set_options( $term_slugs );
		$attribute_obj->set_visible( true );
		$attribute_obj->set_variation( false );
		$product_attributes_objects[ $taxonomy ] = $attribute_obj;
	} else {
		$meta_key = sanitize_text_field( $attr_key );
		update_post_meta( $new_id, $meta_key, sanitize_text_field( $attr_value ) );
	}
}
if ( ! empty( $product_attributes_objects ) ) {
	$_new_prod_obj = wc_get_product( $new_id );
	if ( $_new_prod_obj ) {
		$_new_prod_obj->set_attributes( $product_attributes_objects );
		$_new_prod_obj->save();
	}
}
}

$meta = get_post_meta( $vid );
if ( is_array( $meta ) ) {
$blacklist = array( '_edit_lock', '_edit_last', '_variation_description' );
foreach ( $meta as $meta_key => $meta_values ) {
	if ( in_array( $meta_key, $blacklist, true ) ) {
		continue;
	}
	if ( in_array( $meta_key, array( '_thumbnail_id', '_product_image_gallery' ), true ) ) {
		continue;
	}
	foreach ( $meta_values as $value ) {
		add_post_meta( $new_id, $meta_key, maybe_unserialize( $value ) );
	}
}
}

clean_post_cache( $new_id );
wc_delete_product_transients( $new_id );

$created++;
$created_ids[] = $new_id;
}

/* translators: %d: number of products products created from variations */
$message = sprintf( _n( '%d product created from variations.', '%d products created from variations.', $created, 'coding-bunny-bulk-edit' ), $created );

wp_send_json_success(
array(
'message'     => $message,
'created'     => $created,
'created_ids' => $created_ids,
'reload'      => true,
)
);
}
if ( ! has_action( 'wp_ajax_cbbe_split_variations', 'cbbe_split_variations_ajax' ) ) {
add_action( 'wp_ajax_cbbe_split_variations', 'cbbe_split_variations_ajax' );
}