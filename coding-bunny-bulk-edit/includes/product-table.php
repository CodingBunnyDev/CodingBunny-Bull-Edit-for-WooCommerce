<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once dirname(__FILE__) . '/modals/modal-product.php';
include_once dirname(__FILE__) . '/modals/modal-filter.php';
include_once dirname(__FILE__) . '/modals/modal-bulk-edit.php';
include_once dirname(__FILE__) . '/modals/modal-columns-set.php';
include_once dirname(__FILE__) . '/modals/modal-bulk-set.php';
include_once dirname(__FILE__) . '/modals/modal-custom-fields.php';

function cbbe_search($search, $wp_query) {
	global $wpdb;

	if (isset($wp_query->query_vars['s']) && !empty($wp_query->query_vars['s'])) {
		$search_term = $wp_query->query_vars['s'];
		$cache_key = 'search_by_name_or_variant_id_' . md5($search_term);
		$cached_result = wp_cache_get($cache_key);

		if ($cached_result !== false) {
			return $cached_result;
		}

		if (is_numeric($search_term)) {
			$post_id = intval($search_term);

			$post = get_post($post_id);
			if ($post && $post->post_type === 'product_variation') {
				$post_id = $post->post_parent;
			}

			$search = $wpdb->prepare(
			" AND ({$wpdb->posts}.ID = %d OR {$wpdb->posts}.post_title LIKE %s)",
			$post_id,
			'%' . $wpdb->esc_like($search_term) . '%'
		);
	} else {
		$search = $wpdb->prepare(
		" AND {$wpdb->posts}.post_title LIKE %s",
		'%' . $wpdb->esc_like($search_term) . '%'
	);
}

wp_cache_set($cache_key, $search);
}

return $search;
}

function cbbe_render_column_cell( $column_key, $id, $product, $is_variation ) {

if ( strpos( $column_key, 'attribute_pa_' ) === 0 ) {
if ( $is_variation ) {
	$taxonomy = str_replace('attribute_', '', $column_key);
	echo '<td class="column-' . esc_attr($column_key) . '">';

	$variation_attributes = $product->get_attributes();
	$value = '';
	if (isset($variation_attributes[$taxonomy]) && $variation_attributes[$taxonomy] !== '') {
		$term = get_term_by('slug', $variation_attributes[$taxonomy], $taxonomy);
		$value = $term ? $term->name : $variation_attributes[$taxonomy];
	}
	echo '<div class="cb-adv">' . esc_html($value) . '</div>';

	echo '</td>';
} else {
	$taxonomy = str_replace('attribute_', '', $column_key);
	echo '<td style="min-width:250px" class="column-' . esc_attr($column_key) . '">';
	$terms = get_terms([
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		]);
		if (is_wp_error($terms)) {
			$terms = [];
		}
		$selected_terms = wp_get_post_terms($id, $taxonomy, ['fields' => 'ids']);
		echo '<button type="button" class="toggle-attribute button-primary" data-target=".attribute-list-' . esc_attr($taxonomy) . '-' . esc_attr($id) . '">';
		echo '<span class="dashicons dashicons-edit" style="color: white;" aria-hidden="true"></span> ' . esc_html__( 'Edit', 'coding-bunny-bulk-edit' );
		echo '</button>';
		echo '<div class="attribute-list attribute-list-' . esc_attr($taxonomy) . '-' . esc_attr($id) . '" style="display: none;">';
		foreach ($terms as $term) {
			echo '<label class="attribute-label">';
			echo '<input type="checkbox" ';
			echo 'name="attribute_' . esc_attr($taxonomy) . '[' . esc_attr($id) . '][]" ';
			echo 'data-product-id="' . esc_attr($id) . '" ';
			echo 'value="' . esc_attr($term->term_id) . '" ';
			echo (in_array($term->term_id, $selected_terms) ? 'checked ' : '');
			echo '>';
			echo esc_html($term->name);
			echo '</label>';
		}
		echo '</div>';
		echo '</td>';
	}
	return;
}

if ( strpos( $column_key, 'custom_field_' ) === 0 ) {
	if ( $is_variation ) {
		$field_slug = str_replace('custom_field_', '', $column_key);
		$custom_fields = get_option('cbbe_custom_fields', []);
		if (isset($custom_fields[$field_slug])) {
			echo '<td class="column-' . esc_attr($column_key) . '">';
			$field_value = get_post_meta($id, $field_slug, true);
			$field_type = 'text';
			if (is_array($custom_fields[$field_slug]) && isset($custom_fields[$field_slug]['type'])) {
				$field_type = $custom_fields[$field_slug]['type'];
			}

			switch ($field_type) {
				case 'text':
				echo '<input type="text" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '" style="width: 100%;">';
				break;

				case 'url':
				echo '<input type="url" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '" style="width: 100%;">';
				break;

				case 'email':
				echo '<input type="email" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '" style="width: 100%;">';
				break;

				case 'date':
				$date_val = '';
				if (!empty($field_value)) {
					$timestamp = strtotime($field_value);
					$date_val = $timestamp ? gmdate('Y-m-d', $timestamp) : '';
				}
				echo '<input type="date" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($date_val) . '" style="width: 100%;">';
				break;

				case 'datetime':
				case 'datetime-local':
				$dt_val = '';
				if (!empty($field_value)) {
					$timestamp = strtotime($field_value);
					$dt_val = $timestamp ? gmdate('Y-m-d\TH:i', $timestamp) : '';
				}
				echo '<input type="datetime-local" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($dt_val) . '" style="width: 100%;">';
				break;

				case 'bool':
				$is_checked = !empty($field_value) && ($field_value === '1' || $field_value === 1 || $field_value === true || $field_value === 'true');
				echo '<input type="hidden" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" value="0">';
				echo '<input type="checkbox" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="1"' . ($is_checked ? ' checked' : '') . '>';
				break;

				case 'textarea':
				echo '<textarea name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" rows="1" style="width: 100%;">' . esc_textarea($field_value) . '</textarea>';
				break;

				case 'int':
				echo '<input type="number" step="1" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '" style="width: 100%;">';
				break;

				case 'decimal1':
				echo '<input type="number" step="0.1" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '" style="width: 100%;">';
				break;

				case 'decimal2':
				echo '<input type="number" step="0.01" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '" style="width: 100%;">';
				break;

				case 'decimal3':
				echo '<input type="number" step="0.001" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '" style="width: 100%;">';
				break;

				case 'image':
				$image_id = intval($field_value);
				$image_url = $image_id ? wp_get_attachment_url($image_id) : '';
				echo '<div class="image-upload-container">';
				echo '<img src="' . esc_url($image_url) . '" class="product-image">';
				echo '<input type="hidden" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($image_id) . '">';
				echo '<span class="cb-icon dashicons dashicons-format-gallery upload_image_button" data-input-name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" title="' . esc_attr__('Change image', 'coding-bunny-bulk-edit') . '"></span>';
				echo '</div>';
				break;	

				case 'taxonomy':
				echo '<span class="cb-adv">Same as parent</span>';
				break;

				default:
				echo '<input type="text" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '" style="width: 100%;">';
			}
			echo '</td>';
		}
	} else {
		$field_slug = str_replace('custom_field_', '', $column_key);
		$custom_fields = get_option('cbbe_custom_fields', []);
		echo '<td class="column-' . esc_attr($column_key) . '">';
		$field_value = get_post_meta($id, $field_slug, true);
		$field_type = 'text';
		if (isset($custom_fields[$field_slug]) && is_array($custom_fields[$field_slug]) && isset($custom_fields[$field_slug]['type'])) {
			$field_type = $custom_fields[$field_slug]['type'];
		}
		switch ($field_type) {
			case 'text':
			echo '<input type="text" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '">';
			break;

			case 'url':
			echo '<input type="url" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '">';
			break;

			case 'email':
			echo '<input type="email" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '">';
			break;

			case 'date':
			$date_val = '';
			if (!empty($field_value)) {
				$timestamp = strtotime($field_value);
				$date_val = $timestamp ? gmdate('Y-m-d', $timestamp) : '';
			}
			echo '<input type="date" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($date_val) . '">';
			break;

			case 'datetime':
			case 'datetime-local':
			$dt_val = '';
			if (!empty($field_value)) {
				$timestamp = strtotime($field_value);
				$dt_val = $timestamp ? gmdate('Y-m-d\TH:i', $timestamp) : '';
			}
			echo '<input type="datetime-local" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($dt_val) . '">';
			break;

			case 'bool':
			$is_checked = !empty($field_value) && ($field_value === '1' || $field_value === 1 || $field_value === true || $field_value === 'true');
			echo '<input type="hidden" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" value="0">';
			echo '<input type="checkbox" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="1"' . ($is_checked ? ' checked' : '') . '>';
			break;

			case 'textarea':
			echo '<textarea name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" rows="1" >' . esc_textarea($field_value) . '</textarea>';
			break;

			case 'int':
			echo '<input type="number" step="1" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '">';
			break;

			case 'decimal1':
			echo '<input type="number" step="0.1" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '">';
			break;

			case 'decimal2':
			echo '<input type="number" step="0.01" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '">';
			break;

			case 'decimal3':
			echo '<input type="number" step="0.001" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '">';
			break;

			case 'image':
			$image_id = intval($field_value);
			$image_url = $image_id ? wp_get_attachment_url($image_id) : '';
			echo '<div class="image-upload-container">';
			echo '<img src="' . esc_url($image_url) . '" class="product-image">';
			echo '<input type="hidden" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($image_id) . '">';
			echo '<span class="cb-icon dashicons dashicons-format-gallery upload_image_button" data-input-name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" title="' . esc_attr__('Change image', 'coding-bunny-bulk-edit') . '"></span>';
			echo '</div>';
			break;

			case 'taxonomy':
			$taxonomy = !empty($custom_fields[$field_slug]['taxonomy']) ? $custom_fields[$field_slug]['taxonomy'] : '';

			if ($taxonomy && taxonomy_exists($taxonomy)) {
				$all_terms = get_terms([
					'taxonomy' => $taxonomy,
					'hide_empty' => false,
					]);
					if (is_wp_error($all_terms)) {
						$all_terms = [];
					}

					$selected_terms = wp_get_object_terms($id, $taxonomy, ['fields' => 'ids']);

					$sorted_terms = [];
					if (is_taxonomy_hierarchical($taxonomy)) {
						foreach ($all_terms as $term) {
							if (!$term->parent) {
								$sorted_terms[] = $term;
								foreach ($all_terms as $subterm) {
									if ($subterm->parent == $term->term_id) {
										$sorted_terms[] = $subterm;
									}
								}
							}
						}
					} else {
						$sorted_terms = $all_terms;
					}
					$tax_label = isset($custom_fields[$field_slug]['label']) ? $custom_fields[$field_slug]['label'] : $taxonomy;
					$btn_class = 'toggle-taxonomy-' . esc_attr($taxonomy) . '-' . esc_attr($id);
					$list_class = 'taxonomy-list-' . esc_attr($taxonomy) . '-' . esc_attr($id);

					echo '<button type="button" style="min-width:250px;" class="toggle-custom-taxonomy button-primary" data-target=".' . esc_attr($list_class) . '">';
					echo '<span class="dashicons dashicons-edit" style="color: white;" aria-hidden="true"></span> ' . esc_html__( 'Edit', 'coding-bunny-bulk-edit' );
					echo '</button>';
					echo '<div class="custom-taxonomy-list ' . esc_attr($list_class) . '" style="display: none;">';
					foreach ($sorted_terms as $term) {
						$is_child = $term->parent && is_taxonomy_hierarchical($taxonomy);
						$indent = $is_child ? '&nbsp;&nbsp;&nbsp;' : '';
						$class = $is_child ? 'category-label subcategory-label' : 'category-label';
						echo '<label class="' . esc_attr($class) . '">';
						echo esc_html($indent) . '<input type="checkbox" ';
						echo 'name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . '][]" ';
						echo 'data-product-id="' . esc_attr($id) . '" ';
						echo 'value="' . esc_attr($term->term_id) . '" ';
						echo (in_array($term->term_id, $selected_terms, true) ? 'checked' : '') . '> ';
						echo esc_html($term->name);
						echo '</label>';
					}
					echo '</div>';
				} else {
					echo '<em style="color:red;">' . esc_html__('Custom taxonomy not found: ', 'coding-bunny-bulk-edit') . esc_html($taxonomy) . '</em>';
				}
				break;

				default:
				echo '<input type="text" name="custom_field[' . esc_attr($field_slug) . '][' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($field_value) . '">';
			}
			echo '</td>';
		}
		return;
	}

	switch ( $column_key ) {
		case 'product_image':
		if ( $is_variation ) {
			echo '<td class="column-product_image">';
			$variation_image_id = $product->get_image_id();
			$variation_image_url = wp_get_attachment_image_url($variation_image_id, 'thumbnail');
			echo '<div class="image-upload-container">';
			echo '<img src="' . esc_url($variation_image_url) . '" class="product-image">';
			echo '<input type="hidden" name="image[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($variation_image_id) . '">';
			echo '<span class="cb-icon dashicons dashicons-format-gallery upload_image_button" data-product_id="' . esc_attr($id) . '" title="' . esc_attr__('Change image', 'coding-bunny-bulk-edit') . '"></span>';
			echo '</div>';
			echo '</td>';
		} else {
			echo '<td class="column-product_image">';
			$image_id = $product->get_image_id();
			$image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
			echo '<div class="image-upload-container">';
			echo '<img src="' . esc_url($image_url) . '" class="product-image">';
			echo '<input type="hidden" name="image[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($image_id) . '">';
			echo '<span class="cb-icon dashicons dashicons-format-gallery upload_image_button" data-product_id="' . esc_attr($id) . '" title="' . esc_attr__('Change image', 'coding-bunny-bulk-edit') . '"></span>';
			echo '</div>';
			echo '</td>';
		}
		break;

		case 'product_gallery':
		if ( $is_variation ) {
			echo '<td class="column-product_gallery">';
			$variation_gallery_ids = cbbe_get_variation_gallery_ids($id);
			echo '<div class="gallery-upload-container">';
			foreach ($variation_gallery_ids as $variation_gallery_id) {
				$variation_gallery_url = wp_get_attachment_image_url($variation_gallery_id, 'thumbnail');
				echo '<img src="' . esc_url($variation_gallery_url) . '" class="product-image">';
			}
			echo '<input type="hidden" name="gallery[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr(implode(',', $variation_gallery_ids)) . '">';
			echo '<span class="cb-icon dashicons dashicons-format-gallery upload_gallery_button" data-product_id="' . esc_attr($id) . '" title="' . esc_attr__('Change gallery', 'coding-bunny-bulk-edit') . '"></span>';
			echo '</div>';
			echo '</td>';
		} else {
			echo '<td class="column-product_gallery">';
			$gallery_ids = $product->get_gallery_image_ids();
			echo '<div class="gallery-upload-container">';
			foreach ($gallery_ids as $gallery_id) {
				$gallery_url = wp_get_attachment_image_url($gallery_id, 'thumbnail');
				echo '<img src="' . esc_url($gallery_url) . '" class="product-image">';
			}
			echo '<input type="hidden" name="gallery[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr(implode(',', $gallery_ids)) . '">';
			echo '<span class="cb-icon dashicons dashicons-format-gallery upload_gallery_button" data-product_id="' . esc_attr($id) . '" title="' . esc_attr__('Change gallery', 'coding-bunny-bulk-edit') . '"></span>';
			echo '</div>';
			echo '</td>';
		}
		break;

		case 'regular_price':
		if ( $is_variation ) {
			echo '<td class="column-regular_price">';
			echo '<input type="number" step="0.01" name="regular_price[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_regular_price()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-regular_price">';
			if ($product->is_type('variable')) {
				echo '<span class="cb-adv">Variable product</span>';
			} else {
				echo '<input type="number" step="0.01" name="regular_price[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_regular_price()) . '">';
			}
			echo '</td>';
		}
		break;

		case 'sale_price':
		if ( $is_variation ) {
			echo '<td class="column-sale_price">';
			echo '<input type="number" step="0.01" name="sale_price[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_sale_price()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-sale_price">';
			if ($product->is_type('variable')) {
				echo '<span class="cb-adv">Variable product</span>';
			} else {
				echo '<input type="number" step="0.01" name="sale_price[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_sale_price()) . '">';
			}
			echo '</td>';
		}
		break;

		case 'cost_of_goods_sold':
		if ( $is_variation ) {
			echo '<td class="column-cost_of_goods_sold">';
			echo '<input type="number" step="0.01" name="cost_of_goods_sold[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_cogs_value()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-cost_of_goods_sold">';
			if ($product->is_type('variable')) {
				echo '<span class="cb-adv">Variable product</span>';
			} else {
				echo '<input type="number" step="0.01" name="cost_of_goods_sold[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_cogs_value()) . '">';
			}
			echo '</td>';
		}
		break;

		case 'product_sku':
		if ( $is_variation ) {
			echo '<td class="column-product_sku">';
			echo '<input type="text" name="sku[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_sku()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-product_sku">';
			echo '<input type="text" name="sku[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_sku()) . '">';
			echo '</td>';
		}
		break;

		case 'gtin':
		if ( $is_variation ) {
			echo '<td class="column-gtin">';
			echo '<input type="text" name="gtin[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr(get_post_meta($id, '_global_unique_id', true)) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-gtin">';
			echo '<input type="text" name="gtin[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr(get_post_meta($id, '_global_unique_id', true)) . '">';
			echo '</td>';
		}
		break;

		case 'stock_management':
		if ( $is_variation ) {
			echo '<td class="column-stock_management">';
			echo '<select name="manage_stock[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" style="width: 100%;">';
			echo '<option value="0" ' . selected($product->get_manage_stock(), false, false) . '>' . esc_html__('No', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="1" ' . selected($product->get_manage_stock(), true, false) . '>' . esc_html__('Yes', 'coding-bunny-bulk-edit') . '</option>';
			echo '</select>';
			echo '</td>';
		} else {
			echo '<td class="column-stock_management">';
			if ($product->is_type('external')) {
				echo '<span class="cb-adv">External/Affiliate product</span>';
			} else {
				echo '<select name="manage_stock[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
				echo '<option value="0" ' . selected($product->get_manage_stock(), false, false) . '>' . esc_html__('No', 'coding-bunny-bulk-edit') . '</option>';
				echo '<option value="1" ' . selected($product->get_manage_stock(), true, false) . '>' . esc_html__('Yes', 'coding-bunny-bulk-edit') . '</option>';
				echo '</select>';
			}
			echo '</td>';
		}
		break;

		case 'stock_status':
		if ( $is_variation ) {
			echo '<td class="column-stock_status">';
			echo '<select name="stock_status[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" style="width: 100%;">';
			echo '<option value="instock"' . selected($product->get_stock_status(), 'instock', false) . '>' . esc_html__('In stock', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="outofstock"' . selected($product->get_stock_status(), 'outofstock', false) . '>' . esc_html__('Out of stock', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="onbackorder"' . selected($product->get_stock_status(), 'onbackorder', false) . '>' . esc_html__('On backorder', 'coding-bunny-bulk-edit') . '</option>';
			echo '</select>';
			echo '</td>';
		} else {
			echo '<td class="column-stock_status">';
			if ($product->is_type('external')) {
				echo '<span class="cb-adv">External/Affiliate product</span>';
			} else {
				echo '<select name="stock_status[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
				echo '<option value="instock" ' . selected($product->get_stock_status(), 'instock', false) . '>' . esc_html__('In stock', 'coding-bunny-bulk-edit') . '</option>';
				echo '<option value="outofstock" ' . selected($product->get_stock_status(), 'outofstock', false) . '>' . esc_html__('Out of stock', 'coding-bunny-bulk-edit') . '</option>';
				echo '<option value="onbackorder" ' . selected($product->get_stock_status(), 'onbackorder', false) . '>' . esc_html__('On backorder', 'coding-bunny-bulk-edit') . '</option>';
				echo '</select>';
			}
			echo '</td>';
		}
		break;

		case 'stock_quantity':
		if ( $is_variation ) {
			echo '<td class="column-stock_quantity">';
			echo '<input type="number" name="stock_quantity[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_stock_quantity()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-stock_quantity">';
			if ($product->is_type('external')) {
				echo '<span class="cb-adv">External/Affiliate product</span>';
			} else {
				echo '<input type="number" name="stock_quantity[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_stock_quantity()) . '">';
			}
			echo '</td>';
		}
		break;

		case 'sold_individually':
		if ( $is_variation ) {
			echo '<td class="column-sold_individually">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-sold_individually">';
			if ($product->is_type('external')) {
				echo '<span class="cb-adv">External/Affiliate product</span>';
			} else {
				echo '<select name="sold_individually[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
				echo '<option value="no"' . selected($product->get_sold_individually(), false, false) . '>' . esc_html__('No', 'coding-bunny-bulk-edit') . '</option>';
				echo '<option value="yes"' . selected($product->get_sold_individually(), true, false) . '>' . esc_html__('Yes', 'coding-bunny-bulk-edit') . '</option>';
				echo '</select>';
			}
			echo '</td>';
		}
		break;

		case 'enable_review':
		if ( $is_variation ) {
			echo '<td class="column-enable_review">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-enable_review">';
			echo '<select name="enable_review[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
			echo '<option value="yes" ' . selected($product->get_reviews_allowed(), true, false) . '>' . esc_html__('Enable', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="no" ' . selected($product->get_reviews_allowed(), false, false) . '>' . esc_html__('Disable', 'coding-bunny-bulk-edit') . '</option>';
			echo '</select>';
			echo '</td>';
		}
		break;

		case 'backorders':
		if ( $is_variation ) {
			echo '<td class="column-backorders">';
			echo '<select name="backorders[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" style="width: 100%;">';
			echo '<option value="no"' . selected($product->get_backorders(), 'no', false) . '>' . esc_html__('Do not allow', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="notify"' . selected($product->get_backorders(), 'notify', false) . '>' . esc_html__('Allow, but notify customer', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="yes"' . selected($product->get_backorders(), 'yes', false) . '>' . esc_html__('Allow', 'coding-bunny-bulk-edit') . '</option>';
			echo '</select>';
			echo '</td>';
		} else {
			echo '<td class="column-backorders">';
			if ($product->is_type('external')) {
				echo '<span class="cb-adv">External/Affiliate product</span>';
			} else {
				echo '<select name="backorders[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
				echo '<option value="no"' . selected($product->get_backorders(), 'no', false) . '>' . esc_html__('Do not allow', 'coding-bunny-bulk-edit') . '</option>';
				echo '<option value="notify"' . selected($product->get_backorders(), 'notify', false) . '>' . esc_html__('Allow, but notify customer', 'coding-bunny-bulk-edit') . '</option>';
				echo '<option value="yes"' . selected($product->get_backorders(), 'yes', false) . '>' . esc_html__('Allow', 'coding-bunny-bulk-edit') . '</option>';
				echo '</select>';
			}
			echo '</td>';
		}
		break;

		case 'low_stock_threshold':
		if ( $is_variation ) {
			$default_low_stock_amount = get_option('woocommerce_notify_low_stock_amount');
			$low_stock_amount = $product->get_low_stock_amount() ? $product->get_low_stock_amount() : $default_low_stock_amount;
			echo '<td class="column-low_stock_threshold">';
			echo '<input type="number" name="low_stock_threshold[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($low_stock_amount) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-low_stock_threshold">';
			if ($product->is_type('external')) {
				echo '<span class="cb-adv">External/Affiliate product</span>';
			} else {
				$default_low_stock_amount = get_option('woocommerce_notify_low_stock_amount');
				$low_stock_amount = $product->get_low_stock_amount() ? $product->get_low_stock_amount() : $default_low_stock_amount;
				echo '<input type="number" name="low_stock_threshold[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($low_stock_amount) . '">';
			}
			echo '</td>';
		}
		break;

		case 'product_weight':
		if ( $is_variation ) {
			echo '<td class="column-product_weight">';
			echo '<input type="number" step="0.01" name="weight[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_weight()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-product_weight">';
			echo '<input type="number" step="0.01" name="weight[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_weight()) . '">';
			echo '</td>';
		}
		break;

		case 'product_length':
		if ( $is_variation ) {
			echo '<td class="column-product_length">';
			echo '<input type="number" step="0.01" name="length[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_length()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-product_length">';
			echo '<input type="number" step="0.01" name="length[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_length()) . '">';
			echo '</td>';
		}
		break;

		case 'product_width':
		if ( $is_variation ) {
			echo '<td class="column-product_width">';
			echo '<input type="number" step="0.01" name="width[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_width()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-product_width">';
			echo '<input type="number" step="0.01" name="width[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_width()) . '">';
			echo '</td>';
		}
		break;

		case 'product_height':
		if ( $is_variation ) {
			echo '<td class="column-product_height">';
			echo '<input type="number" step="0.01" name="height[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_height()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-product_height">';
			echo '<input type="number" step="0.01" name="height[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_height()) . '">';
			echo '</td>';
		}
		break;

		case 'post_status':
		if ( $is_variation ) {
			echo '<td class="column-post_status">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-post_status">';
			echo '<select name="post_status[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
			echo '<option value="publish" ' . selected($product->get_status(), 'publish', false) . '>' . esc_html__('Published', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="draft" ' . selected($product->get_status(), 'draft', false) . '>' . esc_html__('Draft', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="future" ' . selected($product->get_status(), 'future', false) . '>' . esc_html__('Scheduled', 'coding-bunny-bulk-edit') . '</option>';
			echo '</select>';
			echo '</td>';
		}
		break;

		case 'shipping_class':
		if ( $is_variation ) {
			echo '<td class="column-shipping_class">';
			echo '<select name="shipping_class[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" style="width: 100%;">';
			echo '<option value="">' . esc_html__('No Shipping Class', 'coding-bunny-bulk-edit') . '</option>';
			foreach (WC()->shipping->get_shipping_classes() as $shipping_class_option) {
				echo '<option value="' . esc_attr($shipping_class_option->term_id) . '" ' . selected($product->get_shipping_class_id(), $shipping_class_option->term_id, false) . '>' . esc_html($shipping_class_option->name) . '</option>';
			}
			echo '</select>';
			echo '</td>';
		} else {
			echo '<td class="column-shipping_class">';
			echo '<select name="shipping_class[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
			echo '<option value="">' . esc_html__('No Shipping Class', 'coding-bunny-bulk-edit') . '</option>';
			foreach (WC()->shipping->get_shipping_classes() as $shipping_class_option) {
				echo '<option value="' . esc_attr($shipping_class_option->term_id) . '" ' . selected($product->get_shipping_class_id(), $shipping_class_option->term_id, false) . '>' . esc_html($shipping_class_option->name) . '</option>';
			}
			echo '</select>';
			echo '</td>';
		}
		break;

		case 'menu_order':
		if ( $is_variation ) {
			echo '<td class="column-menu_order">';
			echo '<input type="number" step="1" name="menu_order[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_menu_order()) . '" style="width: 100%;">';
			echo '</td>';
		} else {
			echo '<td class="column-menu_order">';
			echo '<input type="number" step="1" name="menu_order[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_menu_order()) . '">';
			echo '</td>';
		}
		break;

		case 'product_description':
		if ( $is_variation ) {
			echo '<td class="column-product_description">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-product_description">';

			$editor_id        = 'description_editor_' . intval( $id );
			$editor_text_name = 'description[' . intval( $id ) . ']';

			echo '<button type="button" class="toggle-description button-primary">';
			echo '<span class="dashicons dashicons-edit" style="color: white;" aria-hidden="true"></span> ' . esc_html__( 'Edit', 'coding-bunny-bulk-edit' );
			echo '</button>';

			echo '<div class="description-textarea" style="display: none;">';

			$editor_settings = array(
				'textarea_name'     => $editor_text_name,
				'editor_height'     => 300,
				'media_buttons'     => false,
				'teeny'             => false,
				'wpautop'           => true,
				'tinymce'           => array(
					'wpautop' => true,
				),
				'quicktags'         => true,
				'drag_drop_upload'  => false,
			);

			ob_start();
			wp_editor( $product->get_description(), $editor_id, $editor_settings );
			$editor_html = ob_get_clean();

			echo $editor_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo '</div>';

			echo '</td>';
		}
		break;

		case 'product_short_description':
		if ( $is_variation ) {
			echo '<td class="column-product_short_description">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-product_short_description">';

			$editor_id_short        = 'short_description_editor_' . intval( $id );
			$editor_text_name_short = 'short_description[' . intval( $id ) . ']';

			echo '<button type="button" class="toggle-short-description button-primary">';
			echo '<span class="dashicons dashicons-edit" style="color: white;" aria-hidden="true"></span> ' . esc_html__( 'Edit', 'coding-bunny-bulk-edit' );
			echo '</button>';

			echo '<div class="short-description-textarea" style="display: none;">';

			$editor_settings_short = array(
				'textarea_name'     => $editor_text_name_short,
				'editor_height'     => 300,
				'media_buttons'     => false,
				'teeny'             => true,
				'wpautop'           => true,
				'tinymce'           => array(
					'wpautop' => true,
				),
				'quicktags'         => true,
				'drag_drop_upload'  => false,
			);

			ob_start();
			wp_editor( $product->get_short_description(), $editor_id_short, $editor_settings_short );
			$editor_html = ob_get_clean();

			echo $editor_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo '</div>';

			echo '</td>';
		}
		break;

		case 'variation_description':
		if ( $is_variation ) {
			echo '<td class="column-variation_description">';
			echo '<textarea name="variation_description[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" rows="1" style="width: 100%;">' . esc_textarea($product->get_description()) . '</textarea>';
			echo '</td>';
		} else {
			echo '<td class="column-variation_description">';
			if ($product->is_type('variable')) {
				echo '<span class="cb-adv">Variable product</span>';
			} elseif ($product->is_type('simple')) {
				echo '<span class="cb-adv">Simple product</span>';
			} elseif ($product->is_type('external')) {
				echo '<span class="cb-adv">External/Affiliate product</span>';
			} else {
				$variation_description = $product->get_description();
				echo '<textarea name="variation_description[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" rows="3" >' . esc_textarea($variation_description) . '</textarea>';
			}
			echo '</td>';
		}
		break;

		case 'product_categories':
		if ( $is_variation ) {
			echo '<td class="column-product_categories">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-product_categories">';
			$product_categories = wp_get_post_terms($id, 'product_cat', ['fields' => 'ids']);
			$categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
			$sorted_categories = [];
			foreach ($categories as $category) {
				if (!$category->parent) {
					$sorted_categories[] = $category;
					foreach ($categories as $subcategory) {
						if ($subcategory->parent == $category->term_id) {
							$sorted_categories[] = $subcategory;
						}
					}
				}
			}
			echo '<button type="button" class="toggle-categories button-primary">';
			echo '<span class="dashicons dashicons-edit" style="color: white;" aria-hidden="true"></span> ' . esc_html__( 'Edit', 'coding-bunny-bulk-edit' );
			echo '</button>';
			echo '<div class="categories-list" style="display: none;">';
			foreach ($sorted_categories as $category) {
				$is_subcategory = $category->parent ? true : false;
				$indent = $is_subcategory ? '&nbsp;&nbsp;&nbsp;' : '';
				$class = $is_subcategory ? 'category-label subcategory-label' : 'category-label';
				echo '<label class="' . esc_attr($class) . '">';
				echo esc_html($indent) . '<input type="checkbox" ';
				echo 'name="product_categories[' . esc_attr($id) . '][]" ';
				echo 'data-product-id="' . esc_attr($id) . '" ';
				echo 'value="' . esc_attr($category->term_id) . '" ';
				echo (in_array($category->term_id, $product_categories) ? 'checked ' : '');
				echo '>';
				echo esc_html($category->name);
				echo '</label>';
			}
			echo '</div>';
			echo '</td>';
		}
		break;

		case 'product_tags':
		if ( $is_variation ) {
			echo '<td class="column-product_tags">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-product_tags">';
			$product_tags = wp_get_post_terms($id, 'product_tag', ['fields' => 'ids']);
			$tags = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]);
			echo '<button type="button" class="toggle-tags button-primary">';
			echo '<span class="dashicons dashicons-edit" style="color: white;" aria-hidden="true"></span> ' . esc_html__( 'Edit', 'coding-bunny-bulk-edit' );
			echo '</button>';
			echo '<div class="tags-list" style="display: none;">';
			foreach ($tags as $tag) {
				echo '<label class="tag-label">';
				echo '<input type="checkbox" ';
				echo 'name="product_tags[' . esc_attr($id) . '][]" ';
				echo 'data-product-id="' . esc_attr($id) . '" ';
				echo 'value="' . esc_attr($tag->term_id) . '" ';
				echo (in_array($tag->term_id, $product_tags) ? 'checked ' : '');
				echo '>';
				echo esc_html($tag->name);
				echo '</label>';
			}
			echo '</div>';
			echo '</td>';
		}
		break;

		case 'product_brands':
		if ( $is_variation ) {
			echo '<td class="column-product_brands">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-product_brands">';
			$product_brands = wp_get_post_terms($id, 'product_brand', ['fields' => 'ids']);
			$brands = get_terms(['taxonomy' => 'product_brand', 'hide_empty' => false]);
			echo '<button type="button" class="toggle-brands button-primary">';
			echo '<span class="dashicons dashicons-edit" style="color: white;" aria-hidden="true"></span> ' . esc_html__( 'Edit', 'coding-bunny-bulk-edit' );
			echo '</button>';
			echo '<div class="brands-list" style="display: none;">';
			foreach ($brands as $brand) {
				echo '<label class="brands-label">';
				echo '<input type="checkbox" ';
				echo 'name="product_brands[' . esc_attr($id) . '][]" ';
				echo 'data-product-id="' . esc_attr($id) . '" ';
				echo 'value="' . esc_attr($brand->term_id) . '" ';
				echo (in_array($brand->term_id, $product_brands) ? 'checked ' : '');
				echo '>' . esc_html($brand->name);
				echo '</label>';
			}
			echo '</div>';
			echo '</td>';
		}
		break;

		case 'visibility':
		if ( $is_variation ) {
			echo '<td class="column-visibility">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-visibility">';
			echo '<select name="visibility[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
			echo '<option value="visible" ' . selected($product->get_catalog_visibility(), 'visible', false) . '>' . esc_html__('Catalogue & Search', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="catalog" ' . selected($product->get_catalog_visibility(), 'catalog', false) . '>' . esc_html__('Catalogue', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="search" ' . selected($product->get_catalog_visibility(), 'search', false) . '>' . esc_html__('Search', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="hidden" ' . selected($product->get_catalog_visibility(), 'hidden', false) . '>' . esc_html__('Hidden', 'coding-bunny-bulk-edit') . '</option>';
			echo '</select>';
			echo '</td>';
		}
		break;

		case 'featured':
		if ( $is_variation ) {
			echo '<td class="column-featured">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-featured">';
			echo '<select name="featured[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
			echo '<option value="yes" ' . selected($product->get_featured(), true, false) . '>' . esc_html__('Yes', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="no" ' . selected($product->get_featured(), false, false) . '>' . esc_html__('No', 'coding-bunny-bulk-edit') . '</option>';
			echo '</select>';
			echo '</td>';
		}
		break;

		case 'pos_visibility':
		if ( $is_variation ) {
			echo '<td class="column-pos_visibility">';
			echo '<span class="cb-adv">' . esc_html__( 'Same as parent', 'coding-bunny-bulk-edit' ) . '</span>';
			echo '</td>';
		} else {
			echo '<td class="column-pos_visibility">';
			echo '<select name="pos_visibility[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
			echo '<option value="yes" ' . selected(( ! has_term( 'pos-hidden', 'pos_product_visibility', $id ) ), true, false) . '>' . esc_html__('Yes', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="no" ' . selected(( ! has_term( 'pos-hidden', 'pos_product_visibility', $id ) ), false, false) . '>' . esc_html__('No', 'coding-bunny-bulk-edit') . '</option>';
			echo '</select>';
			echo '</td>';
		}
		break;

		case 'tax_status':
		if ( $is_variation ) {
			echo '<td class="column-tax_status">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-tax_status">';
			echo '<select name="tax_status[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
			echo '<option value="taxable"' . selected($product->get_tax_status(), 'taxable', false) . '>' . esc_html__('Taxable', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="shipping"' . selected($product->get_tax_status(), 'shipping', false) . '>' . esc_html__('Shipping only', 'coding-bunny-bulk-edit') . '</option>';
			echo '<option value="none"' . selected($product->get_tax_status(), 'none', false) . '>' . esc_html__('None', 'coding-bunny-bulk-edit') . '</option>';
			echo '</select>';
			echo '</td>';
		}
		break;

		case 'tax_class':
		if ( $is_variation ) {
			echo '<td class="column-tax_class">';
			$tax_classes = wc_get_product_tax_class_options();
			echo '<select name="tax_class[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" style="width: 100%;">';
			foreach ($tax_classes as $class => $label) {
				echo '<option value="' . esc_attr($class) . '"' . selected(( $product->get_tax_class() ?: 'standard' ), $class, false) . '>' . esc_html($label) . '</option>';
			}
			echo '</select>';
			echo '</td>';
		} else {
			echo '<td class="column-tax_class">';
			$tax_classes = wc_get_product_tax_class_options();
			$current_tax_class = $product->get_tax_class() ?: 'standard';
			echo '<select name="tax_class[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '">';
			foreach ($tax_classes as $class => $label) {
				echo '<option value="' . esc_attr($class) . '"' . selected($current_tax_class, $class, false) . '>' . esc_html($label) . '</option>';
			}
			echo '</select>';
			echo '</td>';
		}
		break;

		case 'purchase_note':
		if ( $is_variation ) {
			echo '<td class="column-purchase_note">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-purchase_note">';
			echo '<textarea name="purchase_note[' . esc_attr( $id ) . ']" data-product-id="' . esc_attr( $id ) . '" rows="1" class="cbbe-purchase-note-textarea" style="width:100%;">' . esc_textarea( $product->get_purchase_note() ) . '</textarea>';
			echo '</td>';
		}
		break;

		case 'sale_start_date':
		if ( $is_variation ) {
			echo '<td class="column-sale_start_date">';
			echo '<input type="date" name="sale_start_date[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_date_on_sale_from() ? $product->get_date_on_sale_from('edit')->date('Y-m-d') : '') . '" style="width: 100%;">';
			echo '</td>';
		} else {
			if (!$product->is_type('variable')) {
				echo '<td class="column-sale_start_date">';
				echo '<input type="date" name="sale_start_date[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_date_on_sale_from() ? $product->get_date_on_sale_from()->date('Y-m-d') : '') . '">';
				echo '</td>';
			} else {
				echo '<td class="column-sale_start_date"><span class="cb-adv">Variable product</span></td>';
			}
		}
		break;

		case 'sale_end_date':
		if ( $is_variation ) {
			echo '<td class="column-sale_end_date">';
			echo '<input type="date" name="sale_end_date[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_date_on_sale_to() ? $product->get_date_on_sale_to('edit')->date('Y-m-d') : '') . '" style="width: 100%;">';
			echo '</td>';
		} else {
			if (!$product->is_type('variable')) {
				echo '<td class="column-sale_end_date">';
				echo '<input type="date" name="sale_end_date[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product->get_date_on_sale_to() ? $product->get_date_on_sale_to()->date('Y-m-d') : '') . '">';
				echo '</td>';
			} else {
				echo '<td class="column-sale_end_date"><span class="cb-adv">Variable product</span></td>';
			}
		}
		break;

		case 'product_id':
		if ( $is_variation ) {
			echo '<td class="column-product_id">';
			echo '<span>' . esc_html($id) . '</span>';
			echo '<input type="hidden" name="product_id[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($id) . '">';
			echo '</td>';
		} else {
			echo '<td class="column-product_id">';
			echo esc_html($id);
			echo '</td>';
		}
		break;

		case 'publication_date_time':
		if ( $is_variation ) {
			echo '<td class="column-publication_date_time">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			$date_created = $product->get_date_created();
			$date_value = $date_created ? $date_created->date('Y-m-d\TH:i') : '';
			echo '<td class="column-publication_date_time">';
			echo '<input type="datetime-local" name="publication_date_time[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($date_value) . '">';
			echo '</td>';
		}
		break;

		case 'product_url':
		if ( $is_variation ) {
			echo '<td class="column-product_url">';
			if ($product->is_type('external')) {
				$product_url = $product->get_product_url();
				echo '<input type="url" name="product_url[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product_url) . '" style="width: 100%;">';
			} else {
				echo '<span class="cb-adv">Same as parent</span>';
			}
			echo '</td>';
		} else {
			echo '<td class="column-product_url">';
			if ($product->is_type('external')) {
				$product_url = $product->get_product_url();
				echo '<input type="url" name="product_url[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($product_url) . '">';
			} else {
				echo '<span class="cb-adv">' . ($product->is_type('variable') ? 'Variable product' : 'Simple product') . '</span>';
			}
			echo '</td>';
		}
		break;

		case 'button_text':
		if ( $is_variation ) {
			echo '<td class="column-button_text">';
			if ($product->is_type('external')) {
				$button_text = $product->get_button_text();
				echo '<input type="text" name="button_text[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($button_text) . '" style="width: 100%;">';
			} else {
				echo '<span class="cb-adv">Same as parent</span>';
			}
			echo '</td>';
		} else {
			echo '<td class="column-button_text">';
			if ($product->is_type('external')) {
				$button_text = $product->get_button_text();
				echo '<input type="text" name="button_text[' . esc_attr($id) . ']" data-product-id="' . esc_attr($id) . '" value="' . esc_attr($button_text) . '">';
			} else {
				echo '<span class="cb-adv">' . ($product->is_type('variable') ? 'Variable product' : 'Simple product') . '</span>';
			}
			echo '</td>';
		}
		break;

		case 'upsells':
		if ( $is_variation ) {
			echo '<td class="column-upsells">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-upsells">';
			$upsell_ids  = $product->get_upsell_ids();
			$all_products = wc_get_products( array(
				'status'  => 'publish',
				'limit'   => -1,
				'exclude' => array( $id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'orderby' => 'title',
				'order'   => 'ASC',
			) );
			echo '<button type="button" class="toggle-upsells button-primary" data-target=".upsells-list-' . esc_attr( $id ) . '">';
			echo '<span class="dashicons dashicons-edit" style="color: white;" aria-hidden="true"></span> ' . esc_html__( 'Edit', 'coding-bunny-bulk-edit' );
			echo '</button>';
			echo '<div class="upsells-list upsells-list-' . esc_attr( $id ) . '" style="display: none; max-height: 200px; overflow-y: auto;">';
			foreach ( $all_products as $p ) {
				echo '<label class="product-label">';
				echo '<input type="checkbox" ';
				echo 'name="upsells[' . esc_attr( $id ) . '][]" ';
				echo 'data-product-id="' . esc_attr( $id ) . '" ';
				echo 'value="' . esc_attr( $p->get_id() ) . '" ';
				echo ( in_array( $p->get_id(), $upsell_ids, true ) ? 'checked ' : '' );
				echo '>';
				echo esc_html( $p->get_name() ) . ' (#' . esc_html( $p->get_id() ) . ')';
				echo '</label>';
			}
			echo '</div>';
			echo '</td>';
		}
		break;

		case 'cross_sells':
		if ( $is_variation ) {
			echo '<td class="column-cross_sells">';
			echo '<span class="cb-adv">Same as parent</span>';
			echo '</td>';
		} else {
			echo '<td class="column-cross_sells">';
			$cross_sell_ids = $product->get_cross_sell_ids();
			$all_products = wc_get_products([
				'status' => 'publish',
				'limit' => -1,
				'exclude' => [$id], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'orderby' => 'title',
				'order' => 'ASC',
				]);
				echo '<button type="button" class="toggle-cross-sells button-primary" data-target=".cross-sells-list-' . esc_attr( $id ) . '">';
				echo '<span class="dashicons dashicons-edit" style="color: white;" aria-hidden="true"></span> ' . esc_html__( 'Edit', 'coding-bunny-bulk-edit' );
				echo '</button>';
				echo '<div class="cross-sells-list cross-sells-list-' . esc_attr($id) . '" style="display: none; max-height: 200px; overflow-y: auto;">';
				foreach ($all_products as $p) {
					echo '<label class="product-label">';
					echo '<input type="checkbox" ';
					echo 'name="cross_sells[' . esc_attr($id) . '][]" ';
					echo 'data-product-id="' . esc_attr($id) . '" ';
					echo 'value="' . esc_attr($p->get_id()) . '" ';
					echo (in_array($p->get_id(), $cross_sell_ids) ? 'checked ' : '');
					echo '>';
					echo esc_html($p->get_name()) . ' (#' . esc_html($p->get_id()) . ')';
					echo '</label>';
				}
				echo '</div>';
				echo '</td>';
			}
			break;

		}
	}

	function cbbe_settings_page() {	
		$currency_symbol   = get_woocommerce_currency_symbol();
		$weight_unit       = get_option('woocommerce_weight_unit', 'kg');
		$dimension_unit    = get_option('woocommerce_dimension_unit', 'cm');
		$hide_bulk_edit_section = get_option('cbbe_hide_bulk_edit_section', 0);

		$selected_category = isset($_GET['product_category']) ? sanitize_text_field(wp_unslash($_GET['product_category'])) : '';
		$availability_filter = isset($_GET['availability']) ? sanitize_text_field(wp_unslash($_GET['availability'])) : '';
		$selected_attribute = isset($_GET['product_attribute']) ? sanitize_text_field(wp_unslash($_GET['product_attribute'])) : '';
		$selected_term     = isset($_GET['product_term']) ? sanitize_text_field(wp_unslash($_GET['product_term'])) : '';
		$selected_status   = isset($_GET['post_status']) ? sanitize_text_field(wp_unslash($_GET['post_status'])) : '';
		$selected_shipping_class = isset($_GET['shipping_class']) ? sanitize_text_field(wp_unslash($_GET['shipping_class'])) : '';
		$order_by          = isset($_GET['order_by']) ? sanitize_text_field(wp_unslash($_GET['order_by'])) : 'ASC';
		$search_product_name = isset($_GET['search_product_name']) ? sanitize_text_field(wp_unslash($_GET['search_product_name'])) : '';
		$search_product_id = isset($_GET['search_product_id']) ? absint($_GET['search_product_id']) : '';
		$categories        = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
		$attributes        = wc_get_attribute_taxonomies();
		$selected_tag      = isset($_GET['product_tag']) ? sanitize_text_field(wp_unslash($_GET['product_tag'])) : '';
		$selected_featured = isset($_GET['featured']) ? sanitize_text_field(wp_unslash($_GET['featured'])) : '';
		$selected_pos_visibility = isset($_GET['pos_visibility']) ? sanitize_text_field(wp_unslash($_GET['pos_visibility'])) : '';
		$selected_brand    = isset($_GET['product_brand']) ? sanitize_text_field(wp_unslash($_GET['product_brand'])) : '';
		$selected_blocksy_brand = isset($_GET['product_brands']) ? sanitize_text_field(wp_unslash($_GET['product_brands'])) : '';
		$price_compare     = isset($_GET['price_compare']) ? sanitize_text_field(wp_unslash($_GET['price_compare']))  : '';
		$price_value       = isset($_GET['price_value']) ? floatval($_GET['price_value']) : '';
		$sale_price_compare = isset($_GET['sale_price_compare']) ? sanitize_text_field(wp_unslash($_GET['sale_price_compare']))  : '';
		$sale_price_value  = isset($_GET['sale_price_value']) ? floatval($_GET['sale_price_value']) : '';

		if (isset($_POST['cbbe_save_columns_only'])) {
			if (
			isset($_POST['_wpnonce']) &&
				wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'cbbe_save_columns_options')
			) {
				$custom_fields_opt = cbbe_sanitize_custom_fields(get_option('cbbe_custom_fields', array()));
				$all_columns = cbbe_get_all_columns($custom_fields_opt);
				$columns = isset($_POST['columns']) ? array_map('sanitize_text_field', wp_unslash($_POST['columns'])) : array_keys($all_columns);
				$order = isset($_POST['order']) ? array_map('sanitize_text_field', wp_unslash($_POST['order'])) : array_keys($all_columns);
				update_option('cbbe_columns', $columns);
				update_option('cbbe_order', $order);
				wp_safe_redirect(add_query_arg('columns_updated', '1', remove_query_arg('paged')));
				exit;
			}
		}

		if (isset($_POST['cbbe_save_bulk_fields_only'])) {
			if (
			isset($_POST['_wpnonce']) &&
				wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'cbbe_save_bulk_fields_options')
			) {
				$custom_fields_opt = cbbe_sanitize_custom_fields(get_option('cbbe_custom_fields', array()));
				$bulk_fields = cbbe_get_bulk_fields($custom_fields_opt);
				$enabled = isset($_POST['bulk_enabled_fields']) ? array_map('sanitize_text_field', wp_unslash($_POST['bulk_enabled_fields'])) : array();
				$bulk_order = isset($_POST['bulk_order']) ? array_map('sanitize_text_field', wp_unslash($_POST['bulk_order'])) : array_keys($bulk_fields);
				update_option('cbbe_bulk_enabled_fields', $enabled);
				update_option('cbbe_bulk_fields_order', $bulk_order);
				wp_safe_redirect(add_query_arg('bulk_fields_updated', '1', remove_query_arg('paged')));
				exit;
			}
		}

		if (isset($_POST['cbbe_update_pagination'])) {
			if (
			isset($_POST['cbbe_pagination_nonce']) &&
				wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cbbe_pagination_nonce'])), 'cbbe_update_pagination_action')
			) {
				$pagination_value = isset($_POST['pagination_products']) ?  intval($_POST['pagination_products']) : 50;
				update_option('cbbe_pagination_products', $pagination_value);
				wp_safe_redirect(add_query_arg('pagination_updated', '1', remove_query_arg('paged')));
				exit;
			}
		}

		if (!empty($search_product_name) || !empty($search_product_id)) {
			add_filter('posts_search', 'cbbe_search', 10, 2);
		}

		if (isset($_POST['bulk_update_products'])) {
			if (
			isset($_POST['cbbe_nonce']) &&
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_verify_nonce(wp_unslash($_POST['cbbe_nonce']), 'cbbe_update_products_action')
			) {
				cbbe_update_bulk_products();
			} else {
				wp_die(esc_html__('Security check failed. Please try again.', 'coding-bunny-bulk-edit'));
			}
		}

		if (isset($_POST['update_products'])) {
			if (
			isset($_POST['cbbe_nonce']) &&
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_verify_nonce(wp_unslash($_POST['cbbe_nonce']), 'cbbe_update_products_action')
			) {
				cbbe_update_individual_products();
			} else {
				wp_die(esc_html__('Security check failed. Please try again.', 'coding-bunny-bulk-edit'));
			}
		}
		?>

		<div class="wrap cbbe-dashboard">
			<h1 class="screen-reader-text">CodingBunny Bulk Edit for WooCommerce</h1>
			<div class="cbbe-header">
				<?php $logo_url = plugins_url( 'assets/images/logo.svg', dirname( __DIR__ ) . '/coding-bunny-bulk-edit.php' ); ?>
				<div class="cbbe-header-left">
					<img src="<?php echo esc_url( $logo_url ); ?>"
					alt="<?php echo esc_attr__( 'CodingBunny logo', 'coding-bunny-bulk-edit' ); ?>"
					class="cbbe-logo" />
					<div class="cbbe-title">
						<p>
							<?php esc_html_e( 'CodingBunny Bulk Edit for WooCommerce', 'coding-bunny-bulk-edit' ); ?>
							<span class="cbbe-version">
								v<?php echo defined( 'CBBE_VERSION' ) ? esc_html( CBBE_VERSION ) : ''; ?>
							</span>
						</p>
					</div>
				</div>
			</div>

			<?php cbbe_render_add_product_modal(); ?>
			<?php cbbe_render_filter_modal(); ?>
			<?php cbbe_render_bulk_modal(); ?>
			<?php cbbe_render_manage_columns_modal(); ?>
			<?php cbbe_render_manage_bulk_fields_modal(); ?>
			<?php cbbe_render_manage_custom_fields_modal(); ?>

			<?php

			echo '<form method="post" action="">';
			wp_nonce_field('cbbe_update_products_action', 'cbbe_nonce');

			echo '<div class="cbbe-section">';
			echo '<div>';
			echo '<div class="cbbe-toolbar">';
			echo '<div class="cbbe-toolbar-left">';

			echo '<button type="button" id="cbbe-add-product-btn" class="button-secondary" title="' . esc_attr__('Add New Product', 'coding-bunny-bulk-edit') . '">';
			echo '<span class="dashicons dashicons-insert"></span>';
			echo esc_html__('New', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo '<button type="button" id="cbbe-bulk-duplicate-btn" class="button-secondary" title="' . esc_attr__('Duplicate selected products', 'coding-bunny-bulk-edit') . '">';
			echo '<span class="dashicons dashicons-columns"></span>';
			echo esc_html__('Duplicate', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo '<button type="button" id="cbbe-bulk-delete-btn" class="button" title="' . esc_attr__('Delete selected products', 'coding-bunny-bulk-edit') . '">';
			echo '<span class="dashicons dashicons-trash"></span>';
			echo esc_html__('Delete', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo esc_html__(' ❖ ', 'coding-bunny-bulk-edit');

			echo '<button type="button" id="open-filter-modal" class="button-secondary" title="' . esc_attr__('Search and filter products', 'coding-bunny-bulk-edit') . '">';
			echo '<span class="dashicons dashicons-search"></span>';
			echo esc_html__('Search & Filter', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo '<button type="button" id="open-bulk-modal" class="button-secondary" title="' .  esc_attr__('Bulk edit selected products', 'coding-bunny-bulk-edit') . '">';
			echo '<span class="dashicons dashicons-editor-table"></span>';
			echo esc_html__('Bulk Edit', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo '<button type="button" id="cbbe-split-variations-btn" class="button-secondary" title="' . esc_attr__('Split variable products into simple products', 'coding-bunny-bulk-edit') . '">';
			echo '<span class="dashicons dashicons-list-view"></span>';
			echo esc_html__('Split Variations', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo '<button type="button" id="toggle-variations" class="button-secondary" title="' . esc_attr__('Show product variations', 'coding-bunny-bulk-edit') . '">';
			echo '<span class="dashicons dashicons-visibility"></span>';
			echo esc_html__('Show Variations', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo esc_html__(' ❖ ', 'coding-bunny-bulk-edit');

			echo '<button type="submit" name="update_products" class="button button-primary">';
			echo '<span class="dashicons dashicons-database-view"></span> ';
			echo esc_html__('Save Changes', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo '</div>';
			echo '<div class="cbbe-toolbar-right">';
			echo '<label for="cbbe-pagination-select" class="cbbe-column-label">';
			echo esc_html__('Products per page', 'coding-bunny-bulk-edit') . ':';
			echo '</label>';
			echo '</form>';

			echo '<form method="post">';
			wp_nonce_field('cbbe_update_pagination_action', 'cbbe_pagination_nonce');
			echo '<select id="cbbe-pagination-select" name="pagination_products" onchange="this.form.submit()" class="cbbe-pagination-select">';
			$current_pagination = get_option('cbbe_pagination_products', 50);
			$pagination_options = [50, 100, 250, 500];
			foreach ($pagination_options as $option) {
				echo '<option value="' . esc_attr($option) . '"' . selected($current_pagination, $option, false) . '>' . esc_html($option) . '</option>';
			}
			echo '</select>';
			echo '<input type="hidden" name="cbbe_update_pagination" value="1">';
			echo '</form>';

			echo esc_html__(' ❖ ', 'coding-bunny-bulk-edit');

			echo '<button type="button" id="open-columns-modal" class="button-secondary" title="' . esc_html__('Manage columns', 'coding-bunny-bulk-edit') . '">';
			echo '<span class="dashicons dashicons-admin-generic"></span>';
			echo esc_html__('Columns', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo '<button type="button" id="open-bulk-fields-modal" class="button-secondary" title="' . esc_html__('Manage bulk fields', 'coding-bunny-bulk-edit') . '">';
			echo '<span class="dashicons dashicons-admin-generic"></span>';
			echo esc_html__('Bulk Edit Fields', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo '<button type="button" id="open-custom-fields-modal" class="button-secondary" title="' . esc_html__( 'Manage custom fields', 'coding-bunny-bulk-edit' ) . '">';
			echo '<span class="dashicons dashicons-admin-generic"></span>';
			echo esc_html__('Custom Fields', 'coding-bunny-bulk-edit');
			echo '</button>';

			echo '</div>';
			echo '</div>';
			echo '</div>';
			$posts_per_page = get_option('cbbe_pagination_products', 50);
			$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

			$meta_query = ['relation' => 'AND'];
			$tax_query  = [];
			$product_ids = [];

			$filtered_variable_product_ids = [];
			if (
			(!empty($price_compare) && $price_value !== '') ||
				(!empty($sale_price_compare) && $sale_price_value !== '')
			) {
				$variation_meta_query = ['relation' => 'AND'];
				if (!empty($price_compare) && $price_value !== '') {
					$meta_compare = $price_compare === 'equal' ? '=' : ($price_compare === 'less_than' ? '<=' : '>=');
					$variation_meta_query[] = [
						'key'     => '_regular_price',
						'value'   => floatval($price_value),
						'compare' => $meta_compare,
						'type'    => 'NUMERIC'
					];
				}
				if (!empty($sale_price_compare) && $sale_price_value !== '') {
					$meta_compare = $sale_price_compare === 'equal' ? '=' : ($sale_price_compare === 'less_than' ? '<=' : '>=');
					$variation_meta_query[] = [
						'key'     => '_sale_price',
						'value'   => floatval($sale_price_value),
						'compare' => $meta_compare,
						'type'    => 'NUMERIC'
					];
				}
				$variation_args = [
					'post_type'      => 'product_variation',
					'posts_per_page' => -1,
					'post_status'    => ['publish', 'private', 'future'],	
					'meta_query'     => $variation_meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'fields'         => 'ids',
				];
				$variation_query = new WP_Query($variation_args);
				$variation_ids = $variation_query->posts;
				if ($variation_ids) {
					global $wpdb;
					$placeholders = implode(',', array_fill(0, count($variation_ids), '%d'));
					$sql = "SELECT DISTINCT post_parent FROM $wpdb->posts WHERE ID IN ($placeholders)";
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
					$parent_ids = $wpdb->get_col($wpdb->prepare($sql, ...$variation_ids));
					$filtered_variable_product_ids = array_map('intval', $parent_ids);
				}
			}

			$simple_product_ids = [];
			if (!empty($price_compare) && $price_value !== '') {
				$meta_compare = $price_compare === 'equal' ? '=' : ($price_compare === 'less_than' ? '<=' : '>=');
				$meta_query_simple = [
					[
						'key'     => '_regular_price',
						'value'   => floatval($price_value),
						'compare' => $meta_compare,
						'type'    => 'NUMERIC'
						]
					];
					$simple_args = [
						'post_type'      => ['product', 'product_external'],
						'posts_per_page' => -1,
						'post_status'    => !empty($selected_status) ? $selected_status : ['publish', 'draft', 'future'],
						'meta_query'     => $meta_query_simple, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'fields'         => 'ids',

					];
					$simple_query = new WP_Query($simple_args);
					$simple_product_ids = $simple_query->posts;
				}
				if (!empty($sale_price_compare) && $sale_price_value !== '') {
					$meta_compare = $sale_price_compare === 'equal' ? '=' : ($sale_price_compare === 'less_than' ? '<=' : '>=');
					$meta_query_simple = [
						[
							'key'     => '_sale_price',
							'value'   => floatval($sale_price_value),
							'compare' => $meta_compare,
							'type'    => 'NUMERIC'
							]
						];
						$simple_args = [
							'post_type'      => ['product', 'product_external'],
							'posts_per_page' => -1,
							'post_status'    => !empty($selected_status) ? $selected_status : ['publish', 'draft', 'future'],
							'meta_query'     => $meta_query_simple, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							'fields'         => 'ids',
						];
						$simple_query = new WP_Query($simple_args);
						$simple_product_ids = array_merge($simple_product_ids, $simple_query->posts);
					}

					if (!empty($filtered_variable_product_ids) || !empty($simple_product_ids)) {
						$product_ids = array_unique(array_merge($filtered_variable_product_ids, $simple_product_ids));
					}

					if (!empty($search_product_name) || !empty($search_product_id)) {
						$args['s'] = !empty($search_product_name) ? $search_product_name : $search_product_id;
					}

					if (!empty($selected_category)) {
						$tax_query[] = [
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => (int) $selected_category,
						];
					}

					if (!empty($availability_filter)) {
						$meta_query[] = [
							'key'     => '_stock_status',
							'value'   => $availability_filter,
							'compare' => '=',
						];
					}

					if (!empty($selected_attribute)) {
						$terms = [];
						if (!empty($selected_term)) {
							$terms = $selected_term;
						} else {
							$terms = wp_list_pluck(
							get_terms([
								'taxonomy'   => 'pa_' . $selected_attribute,
								'hide_empty' => false
								]),
								'slug'
							);
						}
						$tax_query[] = [
							'taxonomy' => 'pa_' . $selected_attribute,
							'field'    => 'slug',
							'terms'    => $terms,
						];
					}

					if (!empty($selected_tag)) {
						$tax_query[] = [
							'taxonomy' => 'product_tag',
							'field'    => 'slug',
							'terms'    => $selected_tag,
						];
					}

					if (taxonomy_exists('product_brand') && !empty($selected_brand)) {
						$tax_query[] = [
							'taxonomy' => 'product_brand',
							'field'    => 'slug',
							'terms'    => $selected_brand,
						];
					}

					if (!empty($selected_featured)) {
						$tax_query[] = [
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => 'featured',
							'operator' => $selected_featured === 'yes' ? 'IN' : 'NOT IN',
						];
					}

					if (!empty($selected_pos_visibility) && taxonomy_exists('pos_product_visibility')) {
						$tax_query[] = [
							'taxonomy' => 'pos_product_visibility',
							'field'    => 'slug',
							'terms'    => 'pos-hidden',
							'operator' => $selected_pos_visibility === 'no' ? 'IN' : 'NOT IN',
						];
					}

					if (!empty($selected_shipping_class)) {
						if ($selected_shipping_class === 'no_shipping_class') {
							$shipping_classes = get_terms([
								'taxonomy' => 'product_shipping_class',
								'fields'   => 'ids',
								]);
								$tax_query[] = [
									'taxonomy' => 'product_shipping_class',
									'field'    => 'term_id',
									'terms'    => $shipping_classes,
									'operator' => 'NOT IN',
								];
							} else {
								$tax_query[] = [
									'taxonomy' => 'product_shipping_class',
									'field'    => 'slug',
									'terms'    => $selected_shipping_class,
								];
							}
						}

						$args = [
							'post_type'      => ['product', 'product_external'],
							'posts_per_page' => $posts_per_page,
							'paged'          => $paged,
							'post_status'    => !empty($selected_status) ? $selected_status : ['publish', 'draft', 'future'],
							'orderby'        => 'title',
							'order'          => $order_by,
							'no_found_rows'  => false,
						];
						if (!empty($product_ids)) {
							$args['post__in'] = $product_ids;
						}
						if (!empty($tax_query)) {
							$args['tax_query'] = count($tax_query) > 1 // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
								? array_merge(['relation' => 'AND'], $tax_query)
									: $tax_query;
						}
						if (count($meta_query) > 1) {
							$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						}

						if (!empty($search_product_name)) {
							$args['s'] = $search_product_name;
						} elseif (!empty($search_product_id)) {
							$args['s'] = $search_product_id;
						}

						if (!empty($search_product_name) || !empty($search_product_id)) {
							add_filter('posts_search', 'cbbe_search', 10, 2);
						}

						$products = new WP_Query($args);

						if (!empty($search_product_name) || !empty($search_product_id)) {
							remove_filter('posts_search', 'cbbe_search', 10, 2);
						}

						$total_products = $products->found_posts;
						$total_pages = $products->max_num_pages;

						$currency_symbol = get_woocommerce_currency_symbol();
						$weight_unit = get_option( 'woocommerce_weight_unit' );
						$dimension_unit = get_option( 'woocommerce_dimension_unit' );

						echo '<div class="product-table-container">';
						echo '<table class="wp-list-table widefat fixed striped" style="width: auto; table-layout: fixed;">';

						$selected_columns = get_option('cbbe_columns', []);
						$column_order = get_option('cbbe_order', []);

						// Product Table Header
						echo '<thead><tr>';
						echo '<th></th>';
						echo '<th></th>';
						echo '<th><input type="checkbox" id="select-all"></th>';
						$order_by_current = isset($_GET['order_by']) ? sanitize_text_field( wp_unslash( $_GET['order_by'] ) ) : 'ASC';
						$order_by_next = ($order_by_current === 'ASC') ? 'DESC' : 'ASC';

						$current_url = remove_query_arg(['order_by', 'paged']);
						$order_by_url = add_query_arg('order_by', $order_by_next, $current_url);

						echo '<th>';
						echo '<a href="' . esc_url($order_by_url) . '" style="padding-left: 10px; text-decoration:none; color:inherit;">' . esc_html__('Product name', 'coding-bunny-bulk-edit');
						if ($order_by_current === 'ASC') {
							echo ' <span style="font-size:13px;">&#9650;</span>';
						} else {
							echo ' <span style="font-size:13px;">&#9660;</span>';
						}
						echo '</a>';
						echo '</th>';

						foreach ($column_order as $column_key) {
							if (in_array($column_key, $selected_columns)) {

								if (strpos($column_key, 'attribute_pa_') === 0) {
									$taxonomy = str_replace('attribute_', '', $column_key);
									$label = wc_attribute_label($taxonomy);
									echo '<th class="column-' . esc_attr($column_key) . '">Attribute: ' . esc_html($label) . '</th>';
									continue;
								}

								if (strpos($column_key, 'custom_field_') === 0) {
									$field_slug = str_replace('custom_field_', '', $column_key);
									$custom_fields = get_option('cbbe_custom_fields', []);
									if (isset($custom_fields[$field_slug])) {
										echo '<th class="column-' . esc_attr($column_key) . '">' .
											esc_html(is_array($custom_fields[$field_slug]) ? $custom_fields[$field_slug]['label'] : $custom_fields[$field_slug]) . '</th>';
									} else {
										echo '<th class="column-' . esc_attr($column_key) . '">' . esc_html($field_slug) . '</th>';
									}
									continue;
								}

								switch ($column_key) {
									case 'product_image':
									echo '<th>' . esc_html__( 'Product image', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_gallery':
									echo '<th class="column-product_gallery">' . esc_html__( 'Product gallery', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'regular_price':
									echo '<th class="column-regular_price">' . esc_html__( 'Regular price', 'coding-bunny-bulk-edit' ) . ' (' . esc_html( $currency_symbol ) . ')</th>';
									break;
									case 'sale_price':
									echo '<th class="column-sale_price">' . esc_html__( 'Sale price', 'coding-bunny-bulk-edit' ) . ' (' . esc_html( $currency_symbol ) . ')</th>';
									break;
									case 'cost_of_goods_sold':
									echo '<th class="column-cost_of_goods_sold">' . esc_html__( 'Cost of goods', 'coding-bunny-bulk-edit' ) . ' (' . esc_html( $currency_symbol ) . ')</th>';
									break;
									case 'product_sku':
									echo '<th class="column-product_sku">' . esc_html__( 'SKU', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'gtin':
									echo '<th class="column-gtin">' . esc_html__( 'GTIN, UPC, EAN or ISBN', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'stock_quantity':
									echo '<th class="column-stock_quantity">' . esc_html__( 'Stock quantity', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'stock_management':
									echo '<th class="column-stock_management">' . esc_html__( 'Stock management', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'stock_status':
									echo '<th class="column-stock_status">' . esc_html__( 'Stock status', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'sold_individually':
									echo '<th class="column-sold_individually">' . esc_html__( 'Sold individually', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'backorders':
									echo '<th class="column-backorders">' . esc_html__( 'Allow back-orders', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'low_stock_threshold':
									echo '<th class="column-low_stock_threshold">' . esc_html__( 'Low stock threshold', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_weight':
									echo '<th class="column-product_weight">' . esc_html__( 'Weight', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_length':
									echo '<th class="column-product_length">' . esc_html__( 'Length', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_width':
									echo '<th class="column-product_width">' . esc_html__( 'Width', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_height':
									echo '<th class="column-product_height">' . esc_html__( 'Height', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'post_status':
									echo '<th class="column-post_status">' . esc_html__( 'Status', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'shipping_class':
									echo '<th class="column-shipping_class">' . esc_html__( 'Shipping class', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'enable_review':
									echo '<th class="column-enable_review">' . esc_html__( 'Enable reviews', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'menu_order':
									echo '<th class="column-menu_order">' . esc_html__( 'Menu order', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_description':
									echo '<th class="column-product_description">' . esc_html__( 'Product description', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_short_description':
									echo '<th class="column-product_short_description">' . esc_html__( 'Product short description', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'variation_description':
									echo '<th class="column-variation_description">' . esc_html__( 'Variation description', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_categories':
									echo '<th class="column-product_categories">' . esc_html__( 'Categories', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_tags':
									echo '<th class="column-product_tags">' . esc_html__( 'Tags', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'visibility':
									echo '<th class="column-visibility">' . esc_html__( 'Visibility', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'featured':
									echo '<th class="column-featured">' . esc_html__( 'Featured', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'pos_visibility':
									echo '<th class="column-pos_visibility">' . esc_html__( 'Available for POS', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'tax_status':
									echo '<th class="column-tax_status">' . esc_html__( 'Tax status', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'tax_class':
									echo '<th class="column-tax_class">' . esc_html__( 'Tax class', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'purchase_note':
									echo '<th class="column-purchase_note">' . esc_html__( 'Purchase note', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_id':
									echo '<th class="column-product_id">' . esc_html__( 'ID', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'publication_date_time':
									echo '<th class="column-publication_date_time">' . esc_html__( 'Publication date', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'sale_start_date':
									echo '<th class="column-sale_start_date">' . esc_html__( 'Sale start date', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'sale_end_date':
									echo '<th class="column-sale_end_date">' . esc_html__( 'Sale end date', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_brands':
									echo '<th class="column-product_brands">' . esc_html__( 'Brand', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'product_url':
									echo '<th class="column-product_url">' . esc_html__( 'Product URL', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'button_text':
									echo '<th class="column-button_text">' . esc_html__( 'Button text', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'upsells':
									echo '<th class="column-upsells">' . esc_html__( 'Upsells', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
									case 'cross_sells':
									echo '<th class="column-cross_sells">' . esc_html__( 'Cross-sells', 'coding-bunny-bulk-edit' ) . '</th>';
									break;
								}
							}
						}

						echo '</tr></thead>';

						echo '<tbody>';

						$main_product_count = 0;

						if ($products->have_posts()) {
							while ($products->have_posts()) : $products->the_post();
							global $product;
							if ($product->is_type('simple') || $product->is_type('variable') || $product->is_type('external')) {
								$main_product_count++;
								$product_id = $product->get_id();
								$product_name = $product->get_name();
								$regular_price = $product->get_regular_price();
								$sale_price = $product->get_sale_price();
								$cost_of_goods_sold = $product->get_cogs_value();
								$product_sku = $product->get_sku();
								$stock_quantity = $product->get_stock_quantity();
								$manage_stock = $product->get_manage_stock();
								$stock_status = $product->get_stock_status();
								$weight = $product->get_weight();
								$length = $product->get_length();
								$width = $product->get_width();
								$height = $product->get_height();
								$status = $product->get_status();
								$shipping_class = $product->get_shipping_class_id();
								$enable_reviews = $product->get_reviews_allowed();
								$menu_order = $product->get_menu_order();
								$icon = $product->is_type( 'variable' ) ? '<span class="toggle-icon" style="cursor:pointer;"> + </span>' : ''; 
								$icon_var = $product->is_type( 'variable' ) ? '# ' : '';
								$product_tags = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'ids']);
								$tags = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]);
								$brands = get_terms(['taxonomy' => 'product_brand', 'hide_empty' => false]);
								$tax_status = $product->get_tax_status();
								$tax_class = $product->get_tax_class() ?: 'standard';
								$purchase_note = $product->get_purchase_note();
								$pos_visible = ! has_term( 'pos-hidden', 'pos_product_visibility', $product_id );

								$draft_class = ( isset( $status ) && $status === 'draft' ) ? ' cbbe-draft' : '';
								$scheduled_class = ( isset( $status ) && $status === 'future' ) ? ' cbbe-scheduled' : '';
								$row_classes = 'main-product' . $scheduled_class . $draft_class;

								$product_name_min_size = 15;
								$product_name_max_size = 45;
								$product_name_length = function_exists('mb_strlen') ? mb_strlen($product_name) : strlen($product_name);
								$product_name_size = max($product_name_min_size, min($product_name_max_size, $product_name_length + 2));

								echo '<tr class="' . esc_attr( $row_classes ) . '" data-product-id="' . esc_attr( $product_id ) . '">';
								echo '<td><a href="' . esc_url(get_edit_post_link($product_id)) . '" target="_blank" class="dashicons dashicons-welcome-write-blog" title="' . esc_attr__('Edit product', 'coding-bunny-bulk-edit') . '"></a></td>';
								echo '<td><a href="' . esc_url(get_permalink($product_id)) . '" target="_blank" class="dashicons dashicons-migrate" title="' . esc_attr__('View product', 'coding-bunny-bulk-edit') . '"></a></td>';
								echo '<td><input type="checkbox" class="product-checkbox" name="selected_products[]" value="' . esc_attr($product_id) . '"></td>';
								echo '<td class="product-name">';
								echo '<div class="cbbe-name-container" style="display: flex; align-items: center;">';
								echo '<input type="text" name="product_name[' . esc_attr($product_id) . ']" data-product-id="' . esc_attr($product_id) . '" value="' . esc_attr($product_name) . '" size="' . esc_attr($product_name_size) . '" style="margin-right: 8px;" readonly>';
								echo '<span class="cbbe-lock dashicons dashicons-lock" title="Unlock to edit"></span>';
								echo wp_kses_post($icon);
								echo '</div>';
								echo '</td>';

								foreach ($column_order as $column_key) {
									if (in_array($column_key, $selected_columns)) {
										cbbe_render_column_cell( $column_key, $product_id, $product, false );
									}
								}
								echo '</tr>';

								if ($product->is_type('variable')) {
									$variations = $product->get_available_variations();

									foreach ($variations as $variation) {
										$variation_id = $variation['variation_id'];
										$variation_product = wc_get_product($variation_id);

										$variation_attributes = $variation_product->get_attributes();
										$attribute_parts = array();
										foreach ($variation_attributes as $attr_name => $attr_value) {
											if ($attr_value === '') {
												continue;
											}
											if (strpos($attr_name, 'pa_') === 0) {
												$term = get_term_by('slug', $attr_value, $attr_name);
												$attribute_parts[] = $term ? $term->name : $attr_value;
											} else {
												$attribute_parts[] = $attr_value;
											}
										}
										$variation_name = implode(' - ', $attribute_parts);

										$variation_regular_price = $variation_product->get_regular_price();
										$variation_sale_price = $variation_product->get_sale_price();
										$variation_cogs = $variation_product->get_cogs_value();
										$variation_stock_quantity = $variation_product->get_stock_quantity();
										$variation_manage_stock = $variation_product->get_manage_stock();
										$variation_stock_status = $variation_product->get_stock_status();
										$variation_weight = $variation_product->get_weight();
										$variation_length = $variation_product->get_length();
										$variation_width = $variation_product->get_width();
										$variation_height = $variation_product->get_height();
										$variation_status = $variation_product->get_status();
										$variation_shipping_class = $variation_product->get_shipping_class_id();
										$variation_enable_reviews = $variation_product->get_reviews_allowed();
										$variation_menu_order = $variation_product->get_menu_order();
										$variation_product_id = $variation_product->get_id();
										$variation_image_id = $variation_product->get_image_id();
										$variation_image_url = wp_get_attachment_image_url($variation_image_id, 'thumbnail');
										$variation_gallery_ids = cbbe_get_variation_gallery_ids($variation_id);
										$variation_tax_class = $variation_product->get_tax_class() ?: 'standard';

										echo '<tr class="cbbe-variation" data-parent-id="' . esc_attr($product_id) . '" style="display:none; background-color: #ebf9fd;">';
										echo '<td></td>';
										echo '<td></td>';
										echo '<td><input type="checkbox" class="product-checkbox" name="selected_products[]" value="' . esc_attr($variation_id) . '"></td>';
										echo '<td>' . esc_html($icon_var . $product_name) . '<span>' . ($variation_name !== '' ? ' - ' . esc_html($variation_name) : '') . '</span></td>';

										foreach ($column_order as $column_key) {
											if (in_array($column_key, $selected_columns)) {
												cbbe_render_column_cell( $column_key, $variation_id, $variation_product, true );
											}
										}

										echo '</tr>';
									}
								}
							}
						endwhile;
					}
					echo '</tbody></table>';
					echo '</div>';
					/* translators: 1: Number of products. */
					echo '<p>' . sprintf(esc_html__('%1d products found ', 'coding-bunny-bulk-edit'), esc_html($total_products)) . '</p>';
					if ($total_pages > 1) {
						$current_url = remove_query_arg('paged');
						echo '<div class="cbbe-pagination" style="margin:16px 0;">';
						if ($paged > 1) {
							echo '<a class="page-numbers prev" href="' . esc_url(add_query_arg('paged', $paged - 1, $current_url)) . '">&laquo;</a> ';
						}
						for ($i = 1; $i <= $total_pages; $i++) {
							if ($i === $paged) {
								echo '<span class="page-numbers current">' . esc_html($i) . '</span> ';
							} else {
								echo '<a class="page-numbers" href="' . esc_url(add_query_arg('paged', $i, $current_url)) . '">' . esc_html($i) . '</a> ';
							}
						}
						if ($paged < $total_pages) {
							echo '<a class="page-numbers next" href="' . esc_url(add_query_arg('paged', $paged + 1, $current_url)) . '">&raquo;</a>';
						}
						echo '</div>';
						if (isset($_GET['paged'])) {
							echo '<input type="hidden" name="paged" value="' . esc_attr($paged) . '">';
						}
					}
					echo '</form>';
					echo '</div>';
					echo '</div>';
					wp_reset_postdata();
				}