<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cbbe_allowed_types = array(
	'text',
	'textarea',
	'int',
	'decimal1',
	'decimal2',
	'decimal3',
	'image',
	'taxonomy',
	'url',
	'email',
	'bool',
	'date',
	'datetime',
);

function cbbe_get_all_columns( $custom_fields = array() ) {
	$columns = array(
		'product_image'             => __( 'Product image', 'coding-bunny-bulk-edit' ),
		'product_gallery'           => __( 'Product gallery', 'coding-bunny-bulk-edit' ),
		'regular_price'             => __( 'Regular price', 'coding-bunny-bulk-edit' ),
		'sale_price'                => __( 'Sale price', 'coding-bunny-bulk-edit' ),
		'product_sku'               => __( 'SKU', 'coding-bunny-bulk-edit' ),
		'gtin'                      => __( 'GTIN, UPC, EAN or ISBN', 'coding-bunny-bulk-edit' ),
		'stock_quantity'            => __( 'Stock quantity', 'coding-bunny-bulk-edit' ),
		'stock_management'          => __( 'Stock management', 'coding-bunny-bulk-edit' ),
		'stock_status'              => __( 'Stock status', 'coding-bunny-bulk-edit' ),
		'sold_individually'         => __( 'Sold individually', 'coding-bunny-bulk-edit' ),
		'backorders'                => __( 'Allow back-orders', 'coding-bunny-bulk-edit' ),
		'low_stock_threshold'       => __( 'Low stock threshold', 'coding-bunny-bulk-edit' ),
		'product_weight'            => __( 'Weight', 'coding-bunny-bulk-edit' ),
		'product_length'            => __( 'Length', 'coding-bunny-bulk-edit' ),
		'product_width'             => __( 'Width', 'coding-bunny-bulk-edit' ),
		'product_height'            => __( 'Height', 'coding-bunny-bulk-edit' ),
		'post_status'               => __( 'Status', 'coding-bunny-bulk-edit' ),
		'shipping_class'            => __( 'Shipping class', 'coding-bunny-bulk-edit' ),
		'enable_review'             => __( 'Enable review', 'coding-bunny-bulk-edit' ),
		'menu_order'                => __( 'Menu order', 'coding-bunny-bulk-edit' ),
		'product_description'       => __( 'Product description', 'coding-bunny-bulk-edit' ),
		'product_short_description' => __( 'Product Short Description', 'coding-bunny-bulk-edit' ),
		'variation_description'     => __( 'Variation description', 'coding-bunny-bulk-edit' ),
		'product_categories'        => __( 'Categories', 'coding-bunny-bulk-edit' ),
		'product_tags'              => __( 'Tags', 'coding-bunny-bulk-edit' ),
		'visibility'                => __( 'Visibility', 'coding-bunny-bulk-edit' ),
		'featured'                  => __( 'Featured', 'coding-bunny-bulk-edit' ),
		'pos_visibility'            => __( 'Available for POS', 'coding-bunny-bulk-edit' ),
		'product_id'                => __( 'ID', 'coding-bunny-bulk-edit' ),
		'publication_date_time'     => __( 'Publication date', 'coding-bunny-bulk-edit' ),
		'tax_status'                => __( 'Tax status', 'coding-bunny-bulk-edit' ),
		'tax_class'                 => __( 'Tax class', 'coding-bunny-bulk-edit' ),
		'purchase_note'             => __( 'Purchase note', 'coding-bunny-bulk-edit' ),
		'sale_start_date'           => __( 'Sale start date', 'coding-bunny-bulk-edit' ),
		'sale_end_date'             => __( 'Sale end date', 'coding-bunny-bulk-edit' ),
		'product_url'               => __( 'Product URL', 'coding-bunny-bulk-edit' ),
		'button_text'               => __( 'Button Text', 'coding-bunny-bulk-edit' ),
		'cost_of_goods_sold'        => __( 'Cost of goods', 'coding-bunny-bulk-edit' ),
		'product_brands'            => __( 'Brand', 'coding-bunny-bulk-edit' ),
		'upsells'                   => __( 'Upsells', 'coding-bunny-bulk-edit' ),
		'cross_sells'               => __( 'Cross-sells', 'coding-bunny-bulk-edit' ),
	);

	if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( ! empty( $attribute_taxonomies ) ) {
			foreach ( $attribute_taxonomies as $attribute ) {
				$taxonomy  = wc_attribute_taxonomy_name( $attribute->attribute_name );
				$attr_slug = 'attribute_' . $taxonomy;
				/* translators: %s = attribute label */
				$label     = sprintf( __( 'Attribute: %s', 'coding-bunny-bulk-edit' ), $attribute->attribute_label );
				$columns[ $attr_slug ] = $label;
			}
		}
	}
	foreach ( $custom_fields as $slug => $field_info ) {
		$columns[ 'custom_field_' . $slug ] = is_array( $field_info ) ? $field_info['label'] : $field_info;
	}
	return $columns;
}

function cbbe_get_bulk_fields( $custom_fields = array() ) {
	$fields = array(
		'regular_price'          => __( 'Regular price', 'coding-bunny-bulk-edit' ),
		'increase_regular_price' => __( 'Increase regular price', 'coding-bunny-bulk-edit' ),
		'decrease_regular_price' => __( 'Decrease regular price', 'coding-bunny-bulk-edit' ),
		'sale_price'             => __( 'Sale price', 'coding-bunny-bulk-edit' ),
		'increase_sale_price'    => __( 'Increase sale price', 'coding-bunny-bulk-edit' ),
		'decrease_sale_price'    => __( 'Decrease sale price', 'coding-bunny-bulk-edit' ),
		'discount_percentage'    => __( 'Discount percentage', 'coding-bunny-bulk-edit' ),
		'stock_quantity'         => __( 'Stock quantity', 'coding-bunny-bulk-edit' ),
		'stock_status'           => __( 'Stock status', 'coding-bunny-bulk-edit' ),
		'manage_stock'           => __( 'Manage stock', 'coding-bunny-bulk-edit' ),
		'featured'               => __( 'Featured', 'coding-bunny-bulk-edit' ),
		'pos_visibility'         => __( 'Available for POS', 'coding-bunny-bulk-edit' ),
		'weight'                 => __( 'Weight', 'coding-bunny-bulk-edit' ),
		'product_length'         => __( 'Length', 'coding-bunny-bulk-edit' ),
		'product_width'          => __( 'Width', 'coding-bunny-bulk-edit' ),
		'product_height'         => __( 'Height', 'coding-bunny-bulk-edit' ),
		'enable_review'          => __( 'Enable review', 'coding-bunny-bulk-edit' ),
		'post_status'            => __( 'Status', 'coding-bunny-bulk-edit' ),
		'backorders'             => __( 'Allow back-orders', 'coding-bunny-bulk-edit' ),
		'low_stock_threshold'    => __( 'Low stock threshold', 'coding-bunny-bulk-edit' ),
		'sold_individually'      => __( 'Sold individually', 'coding-bunny-bulk-edit' ),
		'menu_order'             => __( 'Menu order', 'coding-bunny-bulk-edit' ),
		'shipping_class'         => __( 'Shipping class', 'coding-bunny-bulk-edit' ),
		'purchase_note'          => __( 'Purchase note', 'coding-bunny-bulk-edit' ),
		'tax_status'             => __( 'Tax status', 'coding-bunny-bulk-edit' ),
		'tax_class'              => __( 'Tax class', 'coding-bunny-bulk-edit' ),
		'product_url'            => __( 'Product URL', 'coding-bunny-bulk-edit' ),
		'button_text'            => __( 'Button text', 'coding-bunny-bulk-edit' ),
		'add_categories'         => __( 'Add categories', 'coding-bunny-bulk-edit' ),
		'remove_categories'      => __( 'Remove categories', 'coding-bunny-bulk-edit' ),
		'add_tags'               => __( 'Add tags', 'coding-bunny-bulk-edit' ),
		'remove_tags'            => __( 'Remove tags', 'coding-bunny-bulk-edit' ),
		'add_brands'             => __( 'Add brands', 'coding-bunny-bulk-edit' ),
		'remove_brands'          => __( 'Remove brands', 'coding-bunny-bulk-edit' ),
		'cost_of_goods_sold'     => __( 'Cost of goods', 'coding-bunny-bulk-edit' ),
		'add_upsells'            => __( 'Add upsells', 'coding-bunny-bulk-edit' ),
		'remove_upsells'         => __( 'Remove upsells', 'coding-bunny-bulk-edit' ),
		'add_cross_sells'        => __( 'Add cross-sells', 'coding-bunny-bulk-edit' ),
		'remove_cross_sells'     => __( 'Remove cross-sells', 'coding-bunny-bulk-edit' ),
	);

	foreach ( $custom_fields as $slug => $field_info ) {
		if ( strpos( $slug, 'taxonomy_' ) === 0 ) {
			continue;
		}
		$fields[ 'custom_field_' . $slug ] = is_array( $field_info ) ?  $field_info['label'] : $field_info;
	}

	if ( function_exists( 'get_taxonomies' ) ) {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$excluded_taxonomies = array(
			'category',
			'post_tag',
			'nav_menu',
			'link_category',
			'post_format',
			'product_cat',
			'product_tag',
			'product_brand',
			'product_shipping_class',
		);

		$attribute_labels = array();
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			if ( ! empty( $attribute_taxonomies ) ) {
				foreach ( $attribute_taxonomies as $attribute ) {
					$taxonomy_name = wc_attribute_taxonomy_name( $attribute->attribute_name );
					$attribute_labels[ $taxonomy_name ] = $attribute->attribute_label;
				}
			}
		}

		foreach ( $taxonomies as $tax ) {
			if ( in_array( $tax->name, $excluded_taxonomies, true ) ) {
				continue;
			}
			if ( ( isset( $tax->object_type ) && ( in_array( 'product', $tax->object_type, true ) || in_array( 'product_variation', $tax->object_type, true ) ) ) ) {
				$add_key    = 'add_taxonomy_' . $tax->name;
				$remove_key = 'remove_taxonomy_' . $tax->name;

				if ( 0 === strpos( $tax->name, 'pa_' ) ) {
					if ( isset( $attribute_labels[ $tax->name ] ) && $attribute_labels[ $tax->name ] ) {
						$label = $attribute_labels[ $tax->name ];
					} else {
						$label = isset( $tax->label ) ? $tax->label : $tax->name;
					}
					/* translators: %s: Attribute label */
					$fields[ $add_key ]    = sprintf( __( 'Add attribute: %s', 'coding-bunny-bulk-edit' ), $label );
					/* translators: %s: Attribute label */
					$fields[ $remove_key ] = sprintf( __( 'Remove attribute: %s', 'coding-bunny-bulk-edit' ), $label );
				} else {
					$label = isset( $tax->label ) ? $tax->label : $tax->name;
					/* translators: %s: Taxonomy label */
					$fields[ $add_key ]    = sprintf( __( 'Add %s', 'coding-bunny-bulk-edit' ), $label );
					/* translators: %s: Taxonomy label */
					$fields[ $remove_key ] = sprintf( __( 'Remove %s', 'coding-bunny-bulk-edit' ), $label );
				}
			}
		}
	}

	return $fields;
}

function cbbe_sanitize_custom_fields( $custom_fields ) {
	global $cbbe_allowed_types;
	if ( ! is_array( $cbbe_allowed_types ) ) {
		$cbbe_allowed_types = array(
			'text','textarea','int','decimal1','decimal2','decimal3','image','taxonomy','url','email','bool','date','datetime',
		);
	}
	foreach ( $custom_fields as $slug => &$field ) {
		if ( ! is_array( $field ) ) {
			$field = array(
				'label'         => $field,
				'type'          => 'text',
				'bulk_editable' => 0,
			);
		} else {
			if ( ! isset( $field['bulk_editable'] ) ) {
				$field['bulk_editable'] = 0;
			}
			if ( ! isset( $field['type'] ) || ! in_array( $field['type'], $cbbe_allowed_types, true ) ) {
				$field['type'] = 'text';
			}
		}
	}
	unset( $field );
	return $custom_fields;
}

function cbbe_detect_product_custom_fields() {
	global $wpdb;

	$cache_key = 'cbbe_product_custom_fields';
	$custom_fields = wp_cache_get( $cache_key );
	if ( false !== $custom_fields ) {
		return $custom_fields;
	}

	$attribute_taxonomy_names = array();
	if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( ! empty( $attribute_taxonomies ) ) {
			foreach ( $attribute_taxonomies as $attribute ) {
				$attribute_taxonomy_names[] = wc_attribute_taxonomy_name( $attribute->attribute_name );
			}
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$meta_keys = $wpdb->get_col(
	$wpdb->prepare(
	"SELECT DISTINCT pm.meta_key
	FROM {$wpdb->postmeta} pm
	INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
	WHERE p.post_type IN (%s, %s)
	AND pm.meta_key NOT LIKE %s
	ORDER BY pm.meta_key ASC",
	'product',
	'product_variation',
	$wpdb->esc_like( '_' ) . '%'
		)
);

$wc_default_fields = array( 'total_sales', 'downloadable_files', 'product_url', 'button_text' );
$custom_fields     = array();

foreach ( $meta_keys as $key ) {
	if ( empty( $key ) || in_array( $key, $wc_default_fields, true ) ) {
		continue;
	}
	$label = ucwords( str_replace( array( '_', '-' ), ' ', $key ) );
	$custom_fields[ $key ] = $label;
}

$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
$excluded_taxonomies = array(
	'category','post_tag','nav_menu','link_category','post_format','product_cat','product_tag','product_shipping_class'
);

foreach ( $taxonomies as $taxonomy ) {
	if ( in_array( $taxonomy->name, $excluded_taxonomies, true ) ) {
		continue;
	}

	if ( in_array( $taxonomy->name, $attribute_taxonomy_names, true ) ) {
		continue;
	}

	if ( in_array( 'product', $taxonomy->object_type, true ) || in_array( 'product_variation', $taxonomy->object_type, true ) ) {
		$custom_fields[ 'taxonomy_' . $taxonomy->name ] = $taxonomy->label . ' (Taxonomy)';
	}
}

wp_cache_set( $cache_key, $custom_fields, '', 12 * HOUR_IN_SECONDS );
return $custom_fields;
}

add_action( 'wp_ajax_cbbe_save_custom_fields_ajax', 'cbbe_save_custom_fields_ajax' );
function cbbe_save_custom_fields_ajax() {
check_ajax_referer( 'cbbe_save_custom_fields', 'nonce' );
if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_send_json_error( array( 'message' => __( 'Permission denied.', 'coding-bunny-bulk-edit' ) ) );
}
global $cbbe_allowed_types;
$existing_custom_fields = cbbe_sanitize_custom_fields( get_option( 'cbbe_custom_fields', array() ) );
$new_custom_fields = $existing_custom_fields;
if ( isset( $_POST['custom_field_slugs'] ) && is_array( $_POST['custom_field_slugs'] ) ) {
	$slugs = array_map( 'sanitize_text_field', wp_unslash( $_POST['custom_field_slugs'] ) );
	$labels = isset( $_POST['custom_field_labels'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['custom_field_labels'] ) ) : array();
	$types = isset( $_POST['custom_field_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['custom_field_types'] ) ) : array();
	$taxonomies = isset( $_POST['custom_field_taxonomies'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['custom_field_taxonomies'] ) ) : array();
	$reserved_slugs = array( 'ID', 'post_title', 'post_content', 'post_excerpt', 'post_status' );
	foreach ( $slugs as $index => $slug ) {
		$label = isset( $labels[ $index ] ) ? $labels[ $index ] : $slug;
		$type = isset( $types[ $index ] ) ? $types[ $index ] : 'text';
		$taxonomy = ( 'taxonomy' === $type && ! empty( $taxonomies[ $index ] ) ) ? $taxonomies[ $index ] : '';
		if ( ! empty( $slug ) && ! isset( $existing_custom_fields[ $slug ] ) ) {
			if ( ! in_array( $slug, $reserved_slugs, true ) ) {
				$new_custom_fields[ $slug ] = array(
					'label' => ! empty( $label ) ? $label : $slug,
					'type'  => in_array( $type, $cbbe_allowed_types, true ) ? $type : 'text',
				);
				if ( 'taxonomy' === $type && ! empty( $taxonomy ) ) {
					$new_custom_fields[ $slug ]['taxonomy'] = $taxonomy;
				}
			}
		}
	}
}
update_option( 'cbbe_custom_fields', $new_custom_fields );
$bulk_fields_order = get_option( 'cbbe_bulk_fields_order', array() );
foreach ( $new_custom_fields as $slug => $field_info ) {
	$bulk_key = 'custom_field_' . $slug;
	if ( ! in_array( $bulk_key, $bulk_fields_order, true ) ) {
		$bulk_fields_order[] = $bulk_key;
	}
}
update_option( 'cbbe_bulk_fields_order', $bulk_fields_order );
wp_send_json_success( array(
	'message' => __( 'Custom fields saved successfully!', 'coding-bunny-bulk-edit' ),
	'custom_fields' => $new_custom_fields
	) );
}

add_action( 'wp_ajax_cbbe_edit_custom_field_ajax', 'cbbe_edit_custom_field_ajax' );
function cbbe_edit_custom_field_ajax() {
	check_ajax_referer( 'cbbe_edit_custom_field', 'nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'coding-bunny-bulk-edit' ) ) );
	}
	global $cbbe_allowed_types;
	$custom_fields = cbbe_sanitize_custom_fields( get_option( 'cbbe_custom_fields', array() ) );
	$edit_slug = isset( $_POST['edit_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_slug'] ) ) : '';
	$new_label = isset( $_POST['edit_label'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_label'] ) ) : '';
	$new_type = ( isset( $_POST['edit_type'] ) && in_array( $_POST['edit_type'], $cbbe_allowed_types, true ) )
		? sanitize_text_field( wp_unslash( $_POST['edit_type'] ) )
			: 'text';
	$new_taxonomy = ( 'taxonomy' === $new_type && isset( $_POST['edit_taxonomy'] ) )
		? sanitize_text_field( wp_unslash( $_POST['edit_taxonomy'] ) )
			: '';

	if ( isset( $custom_fields[ $edit_slug ] ) ) {
		$custom_fields[ $edit_slug ]['label'] = $new_label;
		$custom_fields[ $edit_slug ]['type'] = $new_type;
		if ( 'taxonomy' === $new_type && ! empty( $new_taxonomy ) ) {
			$custom_fields[ $edit_slug ]['taxonomy'] = $new_taxonomy;
		} elseif ( isset( $custom_fields[ $edit_slug ]['taxonomy'] ) ) {
			unset( $custom_fields[ $edit_slug ]['taxonomy'] );
		}
		update_option( 'cbbe_custom_fields', $custom_fields );
		wp_send_json_success( array(
			'message' => __( 'Custom field updated successfully!', 'coding-bunny-bulk-edit' ),
			'custom_fields' => $custom_fields
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Custom field not found.', 'coding-bunny-bulk-edit' ) ) );
		}
	}

	add_action( 'wp_ajax_cbbe_remove_custom_field_ajax', 'cbbe_remove_custom_field_ajax' );
	function cbbe_remove_custom_field_ajax() {
		check_ajax_referer( 'cbbe_remove_custom_field', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'coding-bunny-bulk-edit' ) ) );
		}
		$field_to_remove = isset( $_POST['field_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['field_slug'] ) ) : '';
		$custom_fields = cbbe_sanitize_custom_fields( get_option( 'cbbe_custom_fields', array() ) );
		if ( isset( $custom_fields[ $field_to_remove ] ) ) {
			unset( $custom_fields[ $field_to_remove ] );
			update_option( 'cbbe_custom_fields', $custom_fields );
			$selected_columns = get_option( 'cbbe_columns', array() );
			$column_order = get_option( 'cbbe_order', array() );
			$selected_columns = array_diff( $selected_columns, array( 'custom_field_' . $field_to_remove ) );
			$column_order = array_diff( $column_order, array( 'custom_field_' . $field_to_remove ) );
			update_option( 'cbbe_columns', $selected_columns );
			update_option( 'cbbe_order', $column_order );
			$bulk_fields_order = get_option( 'cbbe_bulk_fields_order', array() );
			$enabled_bulk_fields = get_option( 'cbbe_bulk_enabled_fields', array() );
			$bulk_key = 'custom_field_' . $field_to_remove;
			$bulk_fields_order = array_values( array_diff( $bulk_fields_order, array( $bulk_key ) ) );
			$enabled_bulk_fields = array_values( array_diff( $enabled_bulk_fields, array( $bulk_key ) ) );
			update_option( 'cbbe_bulk_fields_order', $bulk_fields_order );
			update_option( 'cbbe_bulk_enabled_fields', $enabled_bulk_fields );
			wp_send_json_success( array(
				'message' => __( 'Custom field removed successfully!', 'coding-bunny-bulk-edit' ),
				'custom_fields' => $custom_fields
				) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Custom field not found.', 'coding-bunny-bulk-edit' ) ) );
			}
		}

		add_action( 'wp_ajax_cbbe_reload_custom_fields_table', 'cbbe_reload_custom_fields_table' );
		function cbbe_reload_custom_fields_table() {
			check_ajax_referer( 'cbbe_reload_custom_fields', 'nonce' );
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'coding-bunny-bulk-edit' ) ) );
			}
			global $cbbe_allowed_types;
			$custom_fields = cbbe_sanitize_custom_fields( get_option( 'cbbe_custom_fields', array() ) );
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
			$excluded_taxonomies = array( 'category', 'post_tag', 'nav_menu', 'link_category', 'post_format' );
			$available_taxonomies = array();
			foreach ( $taxonomies as $tax ) {
				if ( ! in_array( $tax->name, $excluded_taxonomies, true ) ) {
					$available_taxonomies[ $tax->name ] = $tax->label . ' (' . $tax->name . ')';
				}
			}
			ob_start();
			if ( function_exists( 'cbbe_render_existing_custom_fields_table' ) ) {
				cbbe_render_existing_custom_fields_table( $custom_fields, $cbbe_allowed_types, $available_taxonomies );
			}
			$html = ob_get_clean();
			wp_send_json_success( array( 'html' => $html ) );
		}

		function cbbe_get_attribute_terms_ajax() {
			check_ajax_referer( 'cbbe_get_attribute_terms', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'coding-bunny-bulk-edit' ) ) );
			}

			$attribute = isset( $_POST['attribute'] ) ? sanitize_text_field( wp_unslash( $_POST['attribute'] ) ) : '';
			if ( empty( $attribute ) ) {
				wp_send_json_error( array( 'message' => __( 'No attribute specified', 'coding-bunny-bulk-edit' ) ) );
			}

			$attribute = sanitize_title( $attribute );
			$taxonomy = ( 0 === strpos( $attribute, 'pa_' ) ) ? $attribute : 'pa_' . $attribute;

			if ( ! taxonomy_exists( $taxonomy ) ) {
				wp_send_json_error( array( 'message' => __( 'Attribute taxonomy does not exist', 'coding-bunny-bulk-edit' ) ) );
			}

			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			) );

			if ( is_wp_error( $terms ) ) {
				wp_send_json_error( array( 'message' => $terms->get_error_message() ) );
			}

			$result = array();
			foreach ( $terms as $t ) {
				$result[] = array(
					'slug' => $t->slug,
					'name' => $t->name,
				);
			}

			wp_send_json_success( array( 'terms' => $result ) );
		}
		add_action( 'wp_ajax_cbbe_get_attribute_terms', 'cbbe_get_attribute_terms_ajax' );