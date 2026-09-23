<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cbbe_render_bulk_modal() {
	$currency_symbol   = get_woocommerce_currency_symbol();
	$weight_unit       = get_option( 'woocommerce_weight_unit', 'kg' );
	$dimension_unit    = get_option( 'woocommerce_dimension_unit', 'cm' );
	$categories        = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );

	$custom_fields = get_option( 'cbbe_custom_fields', [] );
	$bulk_fields = [
		'regular_price' => __( 'Regular price', 'coding-bunny-bulk-edit' ),
		'increase_regular_price' => __( 'Increase regular price', 'coding-bunny-bulk-edit' ),
		'decrease_regular_price' => __( 'Decrease regular price', 'coding-bunny-bulk-edit' ),
		'sale_price' => __( 'Sale price', 'coding-bunny-bulk-edit' ),
		'cost_of_goods_sold' => __( 'Cost of goods', 'coding-bunny-bulk-edit' ),
		'increase_sale_price' => __( 'Increase sale price', 'coding-bunny-bulk-edit' ),
		'decrease_sale_price' => __( 'Decrease sale price', 'coding-bunny-bulk-edit' ),
		'discount_percentage' => __( 'Discount percentage', 'coding-bunny-bulk-edit' ),
		'stock_quantity' => __( 'Stock quantity', 'coding-bunny-bulk-edit' ),
		'stock_status' => __( 'Stock status', 'coding-bunny-bulk-edit' ),
		'manage_stock' => __( 'Manage stock', 'coding-bunny-bulk-edit' ),
		'featured' => __( 'Featured', 'coding-bunny-bulk-edit' ),
		'pos_visibility' => __( 'Available for POS', 'coding-bunny-bulk-edit' ),
		'weight' => __( 'Weight', 'coding-bunny-bulk-edit' ),
		'product_length' => __( 'Length', 'coding-bunny-bulk-edit' ),
		'product_width' => __( 'Width', 'coding-bunny-bulk-edit' ),
		'product_height' => __( 'Height', 'coding-bunny-bulk-edit' ),
		'enable_review' => __( 'Enable review', 'coding-bunny-bulk-edit' ),
		'post_status' => __( 'Status', 'coding-bunny-bulk-edit' ),
		'backorders' => __( 'Allow back-orders', 'coding-bunny-bulk-edit' ),
		'low_stock_threshold' => __( 'Low stock threshold', 'coding-bunny-bulk-edit' ),
		'sold_individually' => __( 'Sold individually', 'coding-bunny-bulk-edit' ),
		'menu_order' => __( 'Menu order', 'coding-bunny-bulk-edit' ),
		'shipping_class' => __( 'Shipping class', 'coding-bunny-bulk-edit' ),
		'purchase_note' => __( 'Purchase note', 'coding-bunny-bulk-edit' ),
		'tax_status' => __( 'Tax status', 'coding-bunny-bulk-edit' ),
		'tax_class' => __( 'Tax class', 'coding-bunny-bulk-edit' ),
		'product_url' => __( 'Product URL', 'coding-bunny-bulk-edit' ),
		'button_text' => __( 'Button Text', 'coding-bunny-bulk-edit' ),
		'add_categories' => __( 'Add categories', 'coding-bunny-bulk-edit' ),
		'remove_categories' => __( 'Remove categories', 'coding-bunny-bulk-edit' ),
		'add_tags' => __( 'Add tags', 'coding-bunny-bulk-edit' ),
		'remove_tags' => __( 'Remove tags', 'coding-bunny-bulk-edit' ),
		'add_brands' => __( 'Add brands', 'coding-bunny-bulk-edit' ),
		'remove_brands' => __( 'Remove brands', 'coding-bunny-bulk-edit' ),
		'add_upsells' => __( 'Add upsells', 'coding-bunny-bulk-edit' ),
		'remove_upsells' => __( 'Remove upsells', 'coding-bunny-bulk-edit' ),
		'add_cross_sells' => __( 'Add cross-sells', 'coding-bunny-bulk-edit' ),
		'remove_cross_sells' => __( 'Remove cross-sells', 'coding-bunny-bulk-edit' ),
	];

	if ( is_array( $custom_fields ) ) {
		foreach ( $custom_fields as $slug => $field_info ) {
			if ( strpos( $slug, 'taxonomy_' ) === 0 ) {
				continue;
			}
			$bulk_fields['custom_field_' . $slug] = is_array( $field_info ) ? $field_info['label'] : $field_info;
		}
	}

	$excluded_taxonomies = array(
		'category',
		'post_tag',
		'nav_menu',
		'link_category',
		'post_format',
		'product_cat',
		'product_tag',
		'product_shipping_class',
		'product_brand',
	);
	$public_taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

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

	if ( ! empty( $public_taxonomies ) ) {
		foreach ( $public_taxonomies as $tax ) {
			if ( in_array( $tax->name, $excluded_taxonomies, true ) ) {
				continue;
			}
			if ( isset( $tax->object_type ) && ( in_array( 'product', $tax->object_type, true ) || in_array( 'product_variation', $tax->object_type, true ) ) ) {
				$add_key    = 'add_taxonomy_' . $tax->name;
				$remove_key = 'remove_taxonomy_' . $tax->name;
				if ( 0 === strpos( $tax->name, 'pa_' ) ) {
					if ( isset( $attribute_labels[ $tax->name ] ) && $attribute_labels[ $tax->name ] ) {
						$label_name = $attribute_labels[ $tax->name ];
					} else {
						$label_name = isset( $tax->label ) ? $tax->label : $tax->name;
					}
					/* translators: %s: Attribute label */
					$bulk_fields[ $add_key ]    = sprintf( __( 'Add attribute: %s', 'coding-bunny-bulk-edit' ), $label_name );
					/* translators: %s: Attribute label */
					$bulk_fields[ $remove_key ] = sprintf( __( 'Remove attribute: %s', 'coding-bunny-bulk-edit' ), $label_name );
				} else {
					$label_name = isset( $tax->label ) ? $tax->label : $tax->name;
					/* translators: %s: Taxonomy label */
					$bulk_fields[ $add_key ]    = sprintf( __( 'Add %s', 'coding-bunny-bulk-edit' ), $label_name );
					/* translators: %s: Taxonomy label */
					$bulk_fields[ $remove_key ] = sprintf( __( 'Remove %s', 'coding-bunny-bulk-edit' ), $label_name );
				}
			}
		}
	}

	$bulk_fields_order = get_option( 'cbbe_bulk_fields_order' );
	$bulk_fields_order = is_array( $bulk_fields_order ) ? $bulk_fields_order : array_keys( $bulk_fields );
	$bulk_fields_order = array_values( array_unique( array_merge( $bulk_fields_order, array_keys( $bulk_fields ) ) ) );

	$enabled_bulk_fields = get_option( 'cbbe_bulk_enabled_fields' );
	$enabled_bulk_fields = is_array( $enabled_bulk_fields ) ? $enabled_bulk_fields : array_keys( $bulk_fields );
	?>

	<div id="cbbe-bulk-modal" class="cbbe-modal" style="display:none;">
		<div class="cbbe-modal-content">
			<span class="cbbe-modal-close" id="close-bulk-modal">&times;</span>
			<form method="post" action="" id="cbbe-bulk-form">
				<?php wp_nonce_field( 'cbbe_update_products_action', 'cbbe_nonce' ); ?>
				<input type="hidden" name="page" value="coding-bunny-bulk-edit">
				<input type="hidden" id="selected_products_input" name="selected_products" value="">
				<h3><?php esc_html_e( 'Bulk Edit', 'coding-bunny-bulk-edit' ); ?></h3>

				<div>
					<div class="cbbe-bulk-container">
						<?php
						foreach ( $bulk_fields_order as $field_key ) {
							if ( ! in_array( $field_key, $enabled_bulk_fields, true ) ) {
								continue;
							}
							$label = isset( $bulk_fields[ $field_key ] ) ? $bulk_fields[ $field_key ] : '';

							echo '<div class="cbbe-field-container">';

							if ( strpos( $field_key, 'custom_field_' ) === 0 ) {
								$custom_slug = substr( $field_key, strlen( 'custom_field_' ) );
								$field_info  = isset( $custom_fields[ $custom_slug ] ) ? $custom_fields[ $custom_slug ] : [];
								$type        = isset( $field_info['type'] ) ? $field_info['type'] : 'text';
								$taxonomy    = isset( $field_info['taxonomy'] ) ? $field_info['taxonomy'] : '';
								echo '<label for="bulk_custom_field_' . esc_attr( $custom_slug ) . '" class="cbbe-label">' . esc_html( $label ) . '</label>';

								switch ( $type ) {
									case 'text':
									case 'url':
									case 'email':
									echo '<input type="' . ( $type === 'email' ? 'email' : ( $type === 'url' ? 'url' : 'text' ) ) . '" id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input">';
									break;
									case 'textarea':
									echo '<textarea id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input" rows="2"></textarea>';
									break;
									case 'int':
									echo '<input type="number" step="1" id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input">';
									break;
									case 'decimal1':
									echo '<input type="number" step="0.1" id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input">';
									break;
									case 'decimal2':
									echo '<input type="number" step="0.01" id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input">';
									break;
									case 'decimal3':
									echo '<input type="number" step="0.001" id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input">';
									break;
									case 'date':
									echo '<input type="date" id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input">';
									break;
									case 'datetime':
									echo '<input type="datetime-local" id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input">';
									break;
									case 'bool':
									echo '<select id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input">';
									echo '<option value="">' . esc_html__( 'No Change', 'coding-bunny-bulk-edit' ) . '</option>';
									echo '<option value="1">' . esc_html__( 'True', 'coding-bunny-bulk-edit' ) . '</option>';
									echo '<option value="0">' . esc_html__( 'False', 'coding-bunny-bulk-edit' ) . '</option>';
									echo '</select>';
									break;
									case 'image':
									echo '<input type="text" id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input" placeholder="' . esc_attr__( 'Image ID', 'coding-bunny-bulk-edit' ) . '">';
									break;
									case 'taxonomy':
									if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
										$terms = get_terms( [
											'taxonomy'   => $taxonomy,
											'hide_empty' => false,
											] );
											echo '<select id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . '][]" class="bulk-input" multiple>';
											if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
												foreach ( $terms as $term ) {
													$indent = $term->parent ? '&nbsp;&nbsp;&nbsp;' : '';
													echo '<option value="' . esc_attr( $term->term_id ) . '">' . wp_kses_post( $indent ) . esc_html( $term->name ) . '</option>';
												}
											}
											echo '</select>';
										} else {
											echo '<em style="color:red;">' . esc_html__( 'No valid taxonomy set', 'coding-bunny-bulk-edit' ) . '</em>';
										}
										break;
										default:
										echo '<input type="text" id="bulk_custom_field_' . esc_attr( $custom_slug ) . '" name="bulk_custom_field[' . esc_attr( $custom_slug ) . ']" class="bulk-input">';
									}
								} else {
									switch ( $field_key ) {
										case 'regular_price':
										echo '<label for="bulk_' . esc_attr( $field_key ) . '" class="cbbe-label">' . esc_html( $label ) . ' (' . esc_html( $currency_symbol ) . ')</label>';
										echo '<input type="number" step="0.01" id="bulk_' . esc_attr( $field_key ) . '" name="bulk_' . esc_attr( $field_key ) . '" class="bulk-input">';
										break;

										case 'sale_price':
										echo '<label for="bulk_' . esc_attr( $field_key ) . '" class="cbbe-label">' . esc_html( $label ) . ' (' . esc_html( $currency_symbol ) . ')</label>';
										echo '<input type="number" step="0.01" id="bulk_' . esc_attr( $field_key ) . '" name="bulk_' . esc_attr( $field_key ) . '" class="bulk-input">';
										break;

										case 'cost_of_goods_sold':
										echo '<label for="bulk_' . esc_attr( $field_key ) . '" class="cbbe-label">' . esc_html( $label ) . ' (' . esc_html( $currency_symbol ) . ')</label>';
										echo '<input type="number" step="0.01" id="bulk_' . esc_attr( $field_key ) . '" name="bulk_' . esc_attr( $field_key ) . '" class="bulk-input">';
										break;

										case 'increase_regular_price':
										case 'decrease_regular_price':
										case 'increase_sale_price':
										case 'decrease_sale_price':
										$id = 'bulk_' . esc_attr( $field_key );
										$name = 'bulk_' . esc_attr( $field_key );
										$label_text = $bulk_fields[ $field_key ];
										echo '<label for="' . esc_attr( $id ) . '" class="cbbe-label">' . esc_html( $label_text ) . '</label>';
										echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="bulk-input" placeholder="' . esc_attr( 'e.g., 10 or 10%' ) . '">';
										break;

										case 'add_categories':
										case 'remove_categories':
										$is_add = $field_key === 'add_categories';
										$label_text = $is_add ? esc_html__( 'Add categories', 'coding-bunny-bulk-edit' ) : esc_html__( 'Remove categories', 'coding-bunny-bulk-edit' );
										$list_class = $is_add ? 'bulk-add-categories-list' : 'bulk-remove-categories-list';

										echo '<label class="cbbe-label">' . esc_html( $label_text ) . '</label>';
										echo '<button type="button" class="toggle-bulk-categories" data-target=".' . esc_attr( $list_class ) . '">' . esc_html__( 'Show categories', 'coding-bunny-bulk-edit' ) . '</button>';
										echo '<div class="' . esc_attr( $list_class ) . ' categories-list" style="display: none;">';
										$sorted_categories = [];
										foreach ( $categories as $category ) {
											if ( ! $category->parent ) {
												$sorted_categories[] = $category;
												foreach ( $categories as $subcategory ) {
													if ( $subcategory->parent == $category->term_id ) {
														$sorted_categories[] = $subcategory;
													}
												}
											}
										}
										foreach ( $sorted_categories as $category ) {
											$is_subcategory = $category->parent ? true : false;
											$indent = $is_subcategory ? '&nbsp;&nbsp;&nbsp;' : '';
											$class = $is_subcategory ? 'category-label subcategory-label' : 'category-label';
											$input_id = 'bulk_' . esc_attr( $field_key ) . '_' . (int) $category->term_id;
											echo '<label class="' . esc_attr( $class ) . '" for="' . esc_attr( $input_id ) . '">';
											echo wp_kses_post( $indent ) . '<input type="checkbox" id="' . esc_attr( $input_id ) . '" name="bulk_' . esc_attr( $field_key ) . '[]" value="' . esc_attr( $category->term_id ) . '"> ' . esc_html( $category->name );
											echo '</label>';
										}
										echo '</div>';
										break;

										case 'add_tags':
										case 'remove_tags':
										$is_add = $field_key === 'add_tags';
										$label_text = $is_add ? esc_html__( 'Add tags', 'coding-bunny-bulk-edit' ) : esc_html__( 'Remove tags', 'coding-bunny-bulk-edit' );
										$list_class = $is_add ? 'bulk-add-tags-list' : 'bulk-remove-tags-list';

										echo '<label class="cbbe-label">' . esc_html( $label_text ) . '</label>';
										echo '<button type="button" class="toggle-bulk-tags" data-target=".' . esc_attr( $list_class ) . '">' . esc_html__( 'Show tags', 'coding-bunny-bulk-edit' ) . '</button>';
										echo '<div class="' . esc_attr( $list_class ) . ' tags-list" style="display: none;">';
										$tags = get_terms( [ 'taxonomy' => 'product_tag', 'hide_empty' => false ] );
										if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
											foreach ( $tags as $tag ) {
												$input_id = 'bulk_' . esc_attr( $field_key ) . '_' . (int) $tag->term_id;
												echo '<label class="tag-label" for="' . esc_attr( $input_id ) . '">';
												echo '<input type="checkbox" id="' . esc_attr( $input_id ) . '" name="bulk_' . esc_attr( $field_key ) . '[]" value="' . esc_attr( $tag->term_id ) . '"> ' . esc_html( $tag->name );
												echo '</label>';
											}
										}
										echo '</div>';
										break;

										case 'add_brands':
										case 'remove_brands':
										$is_add = $field_key === 'add_brands';
										$label_text = $is_add ? esc_html__( 'Add brands', 'coding-bunny-bulk-edit' ) : esc_html__( 'Remove brands', 'coding-bunny-bulk-edit' );
										$list_class = $is_add ? 'bulk-add-brands-list' : 'bulk-remove-brands-list';

										echo '<label class="cbbe-label">' . esc_html( $label_text ) . '</label>';
										echo '<button type="button" class="toggle-bulk-brands" data-target=".' . esc_attr( $list_class ) . '">' . esc_html__( 'Show brands', 'coding-bunny-bulk-edit' ) . '</button>';
										echo '<div class="' . esc_attr( $list_class ) . ' brands-list" style="display: none;">';
										$brands = get_terms( [ 'taxonomy' => 'product_brand', 'hide_empty' => false ] );
										if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) {
											foreach ( $brands as $brand ) {
												$input_id = 'bulk_' . esc_attr( $field_key ) . '_' . (int) $brand->term_id;
												echo '<label class="brands-label" for="' . esc_attr( $input_id ) . '">';
												echo '<input type="checkbox" id="' . esc_attr( $input_id ) . '" name="bulk_' . esc_attr( $field_key ) . '[]" value="' . esc_attr( $brand->term_id ) . '"> ' . esc_html( $brand->name );
												echo '</label>';
											}
										}
										echo '</div>';
										break;

										default:
										if ( 0 === strpos( $field_key, 'add_taxonomy_' ) || 0 === strpos( $field_key, 'remove_taxonomy_' ) ) {
											$is_add = 0 === strpos( $field_key, 'add_taxonomy_' );
											$taxonomy = str_replace( array( 'add_taxonomy_', 'remove_taxonomy_' ), '', $field_key );
											echo '<label class="cbbe-label">' . esc_html( $bulk_fields[ $field_key ] ) . '</label>';

											if ( in_array( $taxonomy, $excluded_taxonomies, true ) ) {
												echo '<em>' . esc_html__( 'Taxonomy excluded', 'coding-bunny-bulk-edit' ) . '</em>';
											} elseif ( taxonomy_exists( $taxonomy ) ) {
												$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );
												$list_class = $is_add ? 'bulk-add-taxonomy-' . esc_attr( $taxonomy ) . '-list' : 'bulk-remove-taxonomy-' . esc_attr( $taxonomy ) . '-list';
												echo '<button type="button" class="toggle-bulk-custom-taxonomy" data-target=".' . esc_attr( $list_class ) . '">' . esc_html__( 'Show terms', 'coding-bunny-bulk-edit' ) . '</button>';
												echo '<div class="' . esc_attr( $list_class ) . ' custom-taxonomy-list" style="display:none;">';
												if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
													foreach ( $terms as $term ) {
														$input_id = 'bulk_' . esc_attr( $field_key ) . '_' . (int) $term->term_id;
														echo '<label class="custom-taxonomy-label" for="' . esc_attr( $input_id ) . '">';
														echo '<input type="checkbox" id="' . esc_attr( $input_id ) . '" name="bulk_' . esc_attr( $field_key ) . '[]" value="' . esc_attr( $term->term_id ) . '"> ' . esc_html( $term->name );
														echo '</label>';
													}
												} else {
													echo '<em>' . esc_html__( 'No terms found', 'coding-bunny-bulk-edit' ) . '</em>';
												}
												echo '</div>';
											} else {
												echo '<em>' . esc_html__( 'No terms found', 'coding-bunny-bulk-edit' ) . '</em>';
											}
										} else {
											switch ( $field_key ) {
												case 'stock_quantity':
												echo '<label for="bulk_stock" class="cbbe-label">' . esc_html( $label ) . '</label>';
												echo '<input type="number" step="1" id="bulk_stock" name="bulk_stock" class="bulk-input">';
												break;

												case 'stock_status':
												echo '<label for="bulk_stock_status" class="cbbe-label">' . esc_html( $label ) . '</label>';
												echo '<select name="bulk_stock_status" id="bulk_stock_status" class="bulk-select">';
												echo '<option value="no_change">' . esc_html__( 'No Change', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="instock">' . esc_html__( 'In stock', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="outofstock">' . esc_html__( 'Out of stock', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="onbackorder">' . esc_html__( 'On backorder', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '</select>';
												break;

												case 'discount_percentage':
												echo '<label for="bulk_discount_percentage" class="cbbe-label">' . esc_html__( 'Discount (%)', 'coding-bunny-bulk-edit' ) . '</label>';
												echo '<input type="number" step="0.01" id="bulk_discount_percentage" name="bulk_discount_percentage" class="bulk-input" placeholder="Type \'100\' to delete">';
												break;

												case 'manage_stock':
												echo '<label for="bulk_manage_stock" class="cbbe-label">' . esc_html__( 'Stock management', 'coding-bunny-bulk-edit' ) . '</label>';
												echo '<select name="bulk_manage_stock" id="bulk_manage_stock" class="bulk-select">';
												echo '<option value="no_change">' . esc_html__( 'No Change', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="yes">' . esc_html__( 'Yes', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="no">' . esc_html__( 'No', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '</select>';
												break;

												case 'featured':
												case 'pos_visibility':
												case 'enable_review':
												case 'sold_individually':
												$select_id = 'bulk_' . esc_attr( $field_key );
												echo '<label for="' . esc_attr( $select_id ) . '" class="cbbe-label">' . esc_html( $label ) . '</label>';
												echo '<select name="' . esc_attr( $select_id ) . '" id="' . esc_attr( $select_id ) . '" class="bulk-select">';
												echo '<option value="no_change">' . esc_html__( 'No Change', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="yes">' . ( $field_key === 'enable_review' ? esc_html__( 'Enable', 'coding-bunny-bulk-edit' ) : esc_html__( 'Yes', 'coding-bunny-bulk-edit' ) ) . '</option>';
												echo '<option value="no">' . ( $field_key === 'enable_review' ? esc_html__( 'Disable', 'coding-bunny-bulk-edit' ) : esc_html__( 'No', 'coding-bunny-bulk-edit' ) ) . '</option>';
												echo '</select>';
												break;

												case 'backorders':
												echo '<label for="bulk_backorders" class="cbbe-label">' . esc_html( $label ) . '</label>';
												echo '<select name="bulk_backorders" id="bulk_backorders" class="bulk-select">';
												echo '<option value="no_change">' . esc_html__( 'No Change', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="no">' . esc_html__( 'Do not allow', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="notify">' . esc_html__( 'Allow, but notify customer', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="yes">' . esc_html__( 'Allow', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '</select>';
												break;

												case 'low_stock_threshold':
												echo '<label for="bulk_low_stock_threshold" class="cbbe-label">' . esc_html( $label ) . '</label>';
												echo '<input type="number" step="1" id="bulk_low_stock_threshold" name="bulk_low_stock_threshold" class="bulk-input">';
												break;

												case 'weight':
												echo '<label for="bulk_weight" class="cbbe-label">' . esc_html( $label ) . ' (' . esc_html( $weight_unit ) . ')</label>';
												echo '<input type="number" step="1" id="bulk_weight" name="bulk_weight" class="bulk-input">';
												break;

												case 'product_length':
												echo '<label for="bulk_length" class="cbbe-label">' . esc_html( $label ) . ' (' . esc_html( $dimension_unit ) . ')</label>';
												echo '<input type="number" step="1" id="bulk_length" name="bulk_length" class="bulk-input">';
												break;

												case 'product_width':
												echo '<label for="bulk_width" class="cbbe-label">' . esc_html( $label ) . ' (' . esc_html( $dimension_unit ) . ')</label>';
												echo '<input type="number" step="1" id="bulk_width" name="bulk_width" class="bulk-input">';
												break;

												case 'product_height':
												echo '<label for="bulk_height" class="cbbe-label">' . esc_html( $label ) . ' (' . esc_html( $dimension_unit ) . ')</label>';
												echo '<input type="number" step="1" id="bulk_height" name="bulk_height" class="bulk-input">';
												break;

												case 'post_status':
												echo '<label for="bulk_post_status" class="cbbe-label">' . esc_html( $label ) . '</label>';
												echo '<select name="bulk_post_status" id="bulk_post_status" class="bulk-select">';
												echo '<option value="no_change">' . esc_html__( 'No Change', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="publish">' . esc_html__( 'Published', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '<option value="draft">' . esc_html__( 'Draft', 'coding-bunny-bulk-edit' ) . '</option>';
												echo '</select>';
												break;

												case 'add_upsells':
												case 'remove_upsells':
												$is_add = $field_key === 'add_upsells';
												$label_text = $is_add ? esc_html__( 'Add upsells', 'coding-bunny-bulk-edit' ) : esc_html__( 'Remove upsells', 'coding-bunny-bulk-edit' );
												$list_class = $is_add ? 'bulk-add-upsells-list' : 'bulk-remove-upsells-list';

												echo '<label class="cbbe-label">' . esc_html( $label_text ) . '</label>';
												echo '<button type="button" class="toggle-bulk-upsells" data-target=".' . esc_attr( $list_class ) . '">' . esc_html__( 'Show products', 'coding-bunny-bulk-edit' ) . '</button>';
												echo '<div class="' . esc_attr( $list_class ) . ' upsells-list" style="display: none;">';
												$products_for_upsells = wc_get_products( [
													'status' => 'publish',
													'limit'  => -1,
													'return' => 'objects',
													] );
													if ( ! empty( $products_for_upsells ) ) {
														usort( $products_for_upsells, function( $a, $b ) {
															return strcasecmp( $a->get_name(), $b->get_name() );
														} );
														foreach ( $products_for_upsells as $product_option ) {
															$input_id = 'bulk_' . esc_attr( $field_key ) . '_' . (int) $product_option->get_id();
															echo '<label class="upsells-label" for="' . esc_attr( $input_id ) . '">';
															echo '<input type="checkbox" id="' . esc_attr( $input_id ) . '" name="bulk_' . esc_attr( $field_key ) . '[]" value="' . esc_attr( $product_option->get_id() ) . '"> ' . esc_html( $product_option->get_name() );
															echo '</label>';
														}
													}
													echo '</div>';
													break;

													case 'add_cross_sells':
													case 'remove_cross_sells':
													$is_add = $field_key === 'add_cross_sells';
													$label_text = $is_add ? esc_html__( 'Add cross-sells', 'coding-bunny-bulk-edit' ) : esc_html__( 'Remove cross-sells', 'coding-bunny-bulk-edit' );
													$list_class = $is_add ? 'bulk-add-cross-sells-list' : 'bulk-remove-cross-sells-list';

													echo '<label class="cbbe-label">' . esc_html( $label_text ) . '</label>';
													echo '<button type="button" class="toggle-bulk-cross-sells" data-target=".' . esc_attr( $list_class ) . '">' . esc_html__( 'Show products', 'coding-bunny-bulk-edit' ) . '</button>';
													echo '<div class="' . esc_attr( $list_class ) . ' cross-sells-list" style="display: none;">';
													$products_for_cross_sells = wc_get_products( [
														'status' => 'publish',
														'limit'  => -1,
														'return' => 'objects',
														] );
														if ( ! empty( $products_for_cross_sells ) ) {
															usort( $products_for_cross_sells, function( $a, $b ) {
																return strcasecmp( $a->get_name(), $b->get_name() );
															} );
															foreach ( $products_for_cross_sells as $product_option ) {
																$input_id = 'bulk_' . esc_attr( $field_key ) . '_' . (int) $product_option->get_id();
																echo '<label class="cross-sells-label" for="' . esc_attr( $input_id ) . '">';
																echo '<input type="checkbox" id="' . esc_attr( $input_id ) . '" name="bulk_' . esc_attr( $field_key ) . '[]" value="' . esc_attr( $product_option->get_id() ) . '"> ' . esc_html( $product_option->get_name() );
																echo '</label>';
															}
														}
														echo '</div>';
														break;

														case 'shipping_class':
														echo '<label for="bulk_shipping_class" class="cbbe-label">' . esc_html( $label ) . '</label>';
														echo '<select name="bulk_shipping_class" id="bulk_shipping_class" class="bulk-select">';
														echo '<option value="no_change">' . esc_html__( 'No Change', 'coding-bunny-bulk-edit' ) . '</option>';
														echo '<option value="">' . esc_html__( 'No Shipping Class', 'coding-bunny-bulk-edit' ) . '</option>';
														$shipping_classes = WC()->shipping->get_shipping_classes();
														if ( ! empty( $shipping_classes ) ) {
															foreach ( $shipping_classes as $shipping_class ) {
																echo '<option value="' . esc_attr( $shipping_class->term_id ) . '">' . esc_html( $shipping_class->name ) . '</option>';
															}
														}
														echo '</select>';
														break;

														case 'menu_order':
														echo '<label for="bulk_menu_order" class="cbbe-label">' . esc_html( $label ) . '</label>';
														echo '<input type="number" step="1" id="bulk_menu_order" name="bulk_menu_order" class="bulk-input">';
														break;

														case 'purchase_note':
														echo '<label for="bulk_purchase_note" class="cbbe-label">' . esc_html( $label ) . '</label>';
														echo '<textarea id="bulk_purchase_note" name="bulk_purchase_note" class="bulk-input" rows="2"></textarea>';
														break;

														case 'tax_status':
														echo '<label for="bulk_tax_status" class="cbbe-label">' . esc_html( $label ) . '</label>';
														echo '<select name="bulk_tax_status" id="bulk_tax_status" class="bulk-select">';
														echo '<option value="no_change">' . esc_html__( 'No Change', 'coding-bunny-bulk-edit' ) . '</option>';
														echo '<option value="taxable">' . esc_html__( 'Taxable', 'coding-bunny-bulk-edit' ) . '</option>';
														echo '<option value="shipping">' . esc_html__( 'Shipping only', 'coding-bunny-bulk-edit' ) . '</option>';
														echo '<option value="none">' . esc_html__( 'None', 'coding-bunny-bulk-edit' ) . '</option>';
														echo '</select>';
														break;

														case 'tax_class':
														echo '<label for="bulk_tax_class" class="cbbe-label">' . esc_html( $label ) . '</label>';
														echo '<select name="bulk_tax_class" id="bulk_tax_class" class="bulk-select">';
														echo '<option value="no_change">' . esc_html__( 'No Change', 'coding-bunny-bulk-edit' ) . '</option>';
														echo '<option value="">' . esc_html__( 'Standard', 'coding-bunny-bulk-edit' ) . '</option>';
														$tax_classes = WC_Tax::get_tax_classes();
														if ( ! empty( $tax_classes ) ) {
															foreach ( $tax_classes as $class ) {
																$slug = sanitize_title( $class );
																echo '<option value="' . esc_attr( $slug ) . '">' . esc_html( $class ) . '</option>';
															}
														}
														echo '</select>';
														break;

														case 'product_url':
														echo '<label for="bulk_product_url" class="cbbe-label">' . esc_html( $label ) . '</label>';
														echo '<input type="url" id="bulk_product_url" name="bulk_product_url" class="bulk-input">';
														break;

														case 'button_text':
														echo '<label for="bulk_button_text" class="cbbe-label">' . esc_html( $label ) . '</label>';
														echo '<input type="text" id="bulk_button_text" name="bulk_button_text" class="bulk-input">';
														break;

														default:
														echo '<label for="bulk_' . esc_attr( $field_key ) . '" class="cbbe-label">' . esc_html( $label ) . '</label>';
														echo '<input type="text" id="bulk_' . esc_attr( $field_key ) . '" name="bulk_' . esc_attr( $field_key ) . '" class="bulk-input">';
													}
												}
												break;
											}
										}
										echo '</div>';
									}
									?>
								</div>
								<div class="cbbe-modal-actions">
									<button type="submit" name="bulk_update_products" class="button button-primary" aria-label="<?php echo esc_attr__( 'Save Changes', 'coding-bunny-bulk-edit' ); ?>">
										<span class="dashicons dashicons-database-view" aria-hidden="true"></span>
										<?php esc_html_e( 'Save Changes', 'coding-bunny-bulk-edit' ); ?>
									</button>
								</div>
							</div>
						</form>
					</div>
				</div>
				<?php
			}