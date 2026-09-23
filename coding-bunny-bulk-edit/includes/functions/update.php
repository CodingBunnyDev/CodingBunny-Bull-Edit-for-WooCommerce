<?php

if (!defined('ABSPATH')) {
	exit;
}

function cbbe_wc_supports_native_variation_gallery() {
	return defined('WC_VERSION') && version_compare(WC_VERSION, '11.1', '>=');
}

function cbbe_get_variation_gallery_ids($variation_id) {
	$meta = get_post_meta($variation_id, '_product_image_gallery', true);
	if (empty($meta)) {
		return array();
	}
	return array_values(array_filter(array_map('intval', explode(',', $meta))));
}

function cbbe_set_variation_gallery_ids($variation_id, $gallery_ids) {
	$gallery_ids = array_values(array_filter(array_map('intval', (array) $gallery_ids)));
	update_post_meta($variation_id, '_product_image_gallery', implode(',', $gallery_ids));

	$variation = wc_get_product($variation_id);
	if ($variation && method_exists($variation, 'set_gallery_image_ids')) {
		$variation->set_gallery_image_ids($gallery_ids);
	}

	wc_delete_product_transients($variation_id);
}

class CBBE_Product_Updater {

	private static $field_mapping = [
		'product_name'           => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_name'],
		'regular_price'          => ['sanitizer' => 'wc_format_decimal',        'method' => 'set_regular_price'],
		'sale_price'             => ['sanitizer' => 'wc_format_decimal',        'method' => 'set_sale_price', 'special' => 'sale_price'],
		'cost_of_goods_sold'     => ['sanitizer' => 'wc_format_decimal',        'method' => 'set_cogs_value'],
		'stock_quantity'         => ['sanitizer' => 'wc_stock_amount',          'method' => 'set_stock_quantity', 'special' => 'stock'],
		'sku'                    => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_sku'],
		'gtin'                   => ['sanitizer' => 'sanitize_text_field',      'special' => 'meta', 'meta_key' => '_global_unique_id'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'weight'                 => ['sanitizer' => 'wc_format_decimal',        'method' => 'set_weight'],
		'length'                 => ['sanitizer' => 'wc_format_decimal',        'method' => 'set_length'],
		'width'                  => ['sanitizer' => 'wc_format_decimal',        'method' => 'set_width'],
		'height'                 => ['sanitizer' => 'wc_format_decimal',        'method' => 'set_height'],
		'manage_stock'           => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_manage_stock', 'special' => 'boolean'],
		'stock_status'           => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_stock_status'],
		'sold_individually'      => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_sold_individually', 'special' => 'yes_no_boolean'],
		'backorders'             => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_backorders'],
		'low_stock_threshold'    => ['sanitizer' => 'intval',                   'method' => 'set_low_stock_amount'],
		'post_status'            => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_status'],
		'shipping_class'         => ['sanitizer' => 'intval',                   'method' => 'set_shipping_class_id'],
		'enable_review'          => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_reviews_allowed', 'special' => 'yes_no_boolean'],
		'menu_order'             => ['sanitizer' => 'intval',                   'method' => 'set_menu_order'],
		'description'            => ['sanitizer' => 'wp_kses_post',             'method' => 'set_description'],
		'short_description'      => ['sanitizer' => 'wp_kses_post',             'method' => 'set_short_description'],
		'variation_description'  => ['sanitizer' => 'wp_kses_post',             'method' => 'set_description'],
		'visibility'             => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_catalog_visibility'],
		'featured'               => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_featured', 'special' => 'yes_no_boolean'],
		'pos_visibility'         => ['sanitizer' => 'sanitize_text_field',      'special' => 'pos_visibility'],
		'publication_date_time'  => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_date_created', 'special' => 'datetime'],
		'image'                  => ['sanitizer' => 'intval',                   'method' => 'set_image_id'],
		'gallery'                => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_gallery_image_ids', 'special' => 'gallery'],
		'tax_status'             => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_tax_status'],
		'tax_class'              => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_tax_class'],
		'purchase_note'          => ['sanitizer' => 'wp_kses_post',             'method' => 'set_purchase_note'],
		'sale_start_date'        => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_date_on_sale_from', 'special' => 'datetime'],
		'sale_end_date'          => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_date_on_sale_to', 'special' => 'datetime'],
		'product_url'            => ['sanitizer' => 'esc_url_raw',              'method' => 'set_product_url'],
		'button_text'            => ['sanitizer' => 'sanitize_text_field',      'method' => 'set_button_text'],
		'upsells'                => ['sanitizer' => 'sanitize_array_intval',    'method' => 'set_upsell_ids', 'special' => 'product_ids'],
		'cross_sells'            => ['sanitizer' => 'sanitize_array_intval',    'method' => 'set_cross_sell_ids', 'special' => 'product_ids'],
	];

	private static $taxonomy_mapping = [
		'product_categories' => 'product_cat',
		'product_tags'       => 'product_tag',
		'product_brands'     => 'product_brand'
	];

	private static function sanitize_array_intval($value) {
		if (is_string($value)) {
			$value = explode(',', $value);
		}
		if (!is_array($value)) {
			return array();
		}
		return array_values(array_filter(array_map('intval', $value)));
	}

	private static function set_product_pos_visibility($product_id, $visible_in_pos) {
		if (!taxonomy_exists('pos_product_visibility')) {
			return;
		}

		$is_currently_visible = !has_term('pos-hidden', 'pos_product_visibility', $product_id);
		if ($is_currently_visible === $visible_in_pos) {
			return;
		}

		if ($visible_in_pos) {
			wp_remove_object_terms($product_id, 'pos-hidden', 'pos_product_visibility');
		} else {
			wp_set_object_terms($product_id, 'pos-hidden', 'pos_product_visibility');
		}

		$product = wc_get_product($product_id);
		if ($product && $product->is_type('variable')) {
			foreach ($product->get_children() as $variation_id) {
				if ($visible_in_pos) {
					wp_remove_object_terms($variation_id, 'pos-hidden', 'pos_product_visibility');
				} else {
					wp_set_object_terms($variation_id, 'pos-hidden', 'pos_product_visibility');
				}
			}
		}
	}

	private static function get_extended_taxonomy_mapping() {
		$mapping = self::$taxonomy_mapping;

		if (function_exists('wc_get_attribute_taxonomies')) {
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			if (!empty($attribute_taxonomies)) {
				foreach ($attribute_taxonomies as $attribute) {
					$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
					$field = 'attribute_' . $taxonomy;
					$mapping[$field] = $taxonomy;
				}
			}
		}

		return $mapping;
	}

	public static function verify_nonce() {
		if (!current_user_can('edit_products')) {
			wp_die(
			esc_html__('You do not have permission to edit products.', 'coding-bunny-bulk-edit'),
			esc_html__('Permission Denied', 'coding-bunny-bulk-edit'),
			array('response' => 403)
		);
	}

	$nonce = isset($_POST['cbbe_nonce']) ? sanitize_text_field(wp_unslash($_POST['cbbe_nonce'])) : '';
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if (empty($nonce) || !wp_verify_nonce($nonce, 'cbbe_update_products_action')) {
		wp_die(
		esc_html__('Security check failed. Please try again.', 'coding-bunny-bulk-edit'),
		esc_html__('Security Error', 'coding-bunny-bulk-edit'),
		array('response' => 403)
	);
}
}

public static function sanitize_post_data($data_arrays) {
$sanitized = [];

if (isset($data_arrays['modified_products'])) {
	$sanitized['modified_products'] = array_map('intval', wp_unslash($data_arrays['modified_products']));
}

foreach (self::$field_mapping as $field => $config) {
	if (isset($data_arrays[$field])) {
		$sanitizer = $config['sanitizer'];
		$raw_data = wp_unslash($data_arrays[$field]);

		$sanitized_field = [];
		foreach ($raw_data as $product_id => $value) {
			if ($value === '' || $value === null) {
				continue;
			}

			if ($sanitizer === 'wc_format_decimal') {
				$sanitized_field[$product_id] = wc_format_decimal(sanitize_text_field($value));
			} elseif ($sanitizer === 'wc_stock_amount') {
				$sanitized_field[$product_id] = wc_stock_amount(sanitize_text_field($value));
			} elseif ($sanitizer === 'intval') {
				$sanitized_field[$product_id] = intval($value);
			} elseif ($sanitizer === 'sanitize_array_intval') {
				$sanitized_value = self::sanitize_array_intval($value);
				if (!empty($sanitized_value)) {
					$sanitized_field[$product_id] = $sanitized_value;
				}
			} elseif ($sanitizer === 'wp_kses_post') {
				$sanitized_field[$product_id] = wp_kses_post($value);
			} elseif ($sanitizer === 'esc_url_raw') {
				$sanitized_field[$product_id] = esc_url_raw($value);
			} else {
				$sanitized_field[$product_id] = sanitize_text_field($value);
			}
		}

		if (!empty($sanitized_field)) {
			$sanitized[$field] = $sanitized_field;
		}
	}
}

$taxonomy_mapping = self::get_extended_taxonomy_mapping();
foreach ($taxonomy_mapping as $field => $taxonomy) {
	if (isset($data_arrays[$field])) {
		$raw_data = wp_unslash($data_arrays[$field]);
		$sanitized_taxonomy = [];

		foreach ($raw_data as $product_id => $terms) {
			$cleaned_terms = array_filter(array_map('intval', (array)$terms));
			if (!empty($cleaned_terms)) {
				$sanitized_taxonomy[$product_id] = $cleaned_terms;
			}
		}

		if (!empty($sanitized_taxonomy)) {
			$sanitized[$field] = $sanitized_taxonomy;
		}
	}
}

$sanitized['custom_field'] = [];
if (isset($data_arrays['custom_field']) && is_array($data_arrays['custom_field'])) {
	foreach ($data_arrays['custom_field'] as $field_slug => $products_data) {
		foreach ($products_data as $product_id => $value) {
			if ($value !== '' && $value !== null) {
				$sanitized['custom_field'][$field_slug][$product_id] = $value;
			}
		}
	}
}

return $sanitized;
}

public static function update_product_field($product, $product_id, $field_name, $value, $config) {
if (!array_key_exists($product_id, $value)) {
	return false;
}

$field_value = $value[$product_id];

if (in_array($field_name, ['upsells', 'cross_sells']) && isset($config['special']) && $config['special'] === 'product_ids') {
	$product->{$config['method']}(is_array($field_value) ? $field_value : []);
	return true;
}

if ($field_value === '' || $field_value === null) {
	return false;
}

if (isset($config['special'])) {
	switch ($config['special']) {
		case 'sale_price':
		if ($field_value === '' || $field_value < 0) {
			$product->set_sale_price('');
		} else {
			$product->set_sale_price($field_value);
		}
		return true;
		case 'stock':
		$product->set_stock_quantity($field_value);
		$stock_status = $field_value > 0 ? 'instock' : 'outofstock';
		$product->set_stock_status($stock_status);
		return true;
		case 'boolean':
		$product->{$config['method']}($field_value === '1');
		return true;
		case 'yes_no_boolean':
		$product->{$config['method']}($field_value === 'yes');
		return true;
		case 'pos_visibility':
		self::set_product_pos_visibility($product_id, $field_value === 'yes');
		return true;
		case 'datetime':
		if ($field_value) {
			try {
				$date = new WC_DateTime($field_value);
				$product->{$config['method']}($date);
				return true;
			} catch (Exception $e) {
				self::log(sprintf('Invalid date format for product %d: %s', $product_id, $field_value), 'warning');
				return false;
			}
		} else {
			$product->{$config['method']}('');
			return true;
		}
		case 'gallery':
		$gallery_ids = array_map('intval', explode(',', $field_value));
		if ($product->is_type('variation')) {
			cbbe_set_variation_gallery_ids($product_id, $gallery_ids);
		} else {
			$product->set_gallery_image_ids($gallery_ids);
		}
		return true;
		case 'meta':
		update_post_meta($product_id, $config['meta_key'], $field_value);
		return true;
		case 'product_ids':
		if (is_array($field_value) && !empty($field_value)) {
			$product->{$config['method']}($field_value);
		} else {
			$product->{$config['method']}([]);
		}
		return true;
	}
}

$product->{$config['method']}($field_value);
return true;
}

public static function update_product_taxonomies($product_id, $data) {
$taxonomy_mapping = self::get_extended_taxonomy_mapping();
foreach ($taxonomy_mapping as $field => $taxonomy) {
	if (array_key_exists($product_id, $data[$field] ?? [])) {
		if ($taxonomy === 'product_brand' && !taxonomy_exists('product_brand')) {
			continue;
		}
		wp_set_object_terms($product_id, $data[$field][$product_id], $taxonomy);
	}
}
}

public static function update_single_product($product_id, $data) {
try {
	$product = wc_get_product($product_id);
	if (!$product) {
		self::log(sprintf('Product %d not found', $product_id), 'error');
		return false;
	}

	$has_changes = false;

	do_action('cbbe_before_update_product', $product, $product_id, $data);

	foreach ($data as $field_name => $field_values) {
		if (isset(self::$field_mapping[$field_name])) {
			$config = self::$field_mapping[$field_name];
			if (self::update_product_field($product, $product_id, $field_name, $field_values, $config)) {
				$has_changes = true;
			}
		}
	}

	if (isset($data['manage_stock'][$product_id]) &&
		$data['manage_stock'][$product_id] !== '1' &&
	isset($data['stock_status'][$product_id])) {
		$product->set_stock_status($data['stock_status'][$product_id]);
		$has_changes = true;
	}

	$taxonomy_mapping = self::get_extended_taxonomy_mapping();
	foreach ($taxonomy_mapping as $field => $taxonomy) {
		if (isset($data[$field]) && array_key_exists($product_id, $data[$field])) {
			if ($taxonomy === 'product_brand' && !taxonomy_exists('product_brand')) {
				continue;
			}
			wp_set_object_terms($product_id, $data[$field][$product_id], $taxonomy);
			$has_changes = true;
		}
	}

	$product_attributes = $product->get_attributes();
	if (!is_array($product_attributes)) {
		$product_attributes = [];
	}

	foreach ($taxonomy_mapping as $field => $taxonomy) {
		if (strpos($field, 'attribute_pa_') === 0 && isset($data[$field]) && array_key_exists($product_id, $data[$field])) {
			$term_ids = $data[$field][$product_id];

			$taxonomy_name = strpos($taxonomy, 'pa_') === 0 ? $taxonomy : 'pa_' . $taxonomy;

			$attribute_id = wc_attribute_taxonomy_id_by_name($taxonomy_name);
			if (!$attribute_id) {
				$attribute_id = wc_attribute_taxonomy_id_by_name(str_replace('pa_', '', $taxonomy_name));
			}

			if (!empty($term_ids) && $attribute_id) {
				$terms = get_terms([
					'taxonomy'   => $taxonomy_name,
					'include'    => $term_ids,
					'hide_empty' => false,
					]);

					if (!is_wp_error($terms) && !empty($terms)) {
						$term_slugs = wp_list_pluck($terms, 'slug');

						if (isset($product_attributes[$taxonomy_name])) {
							$attribute = $product_attributes[$taxonomy_name];
						} else {
							$attribute = new WC_Product_Attribute();
						}

						$attribute->set_id($attribute_id);
						$attribute->set_name($taxonomy_name);
						$attribute->set_options($term_slugs);
						$attribute->set_position(
						isset($product_attributes[$taxonomy_name])
							? $product_attributes[$taxonomy_name]->get_position()
								: 0
					);
					$attribute->set_visible(true);
					$attribute->set_variation($product->is_type('variable') || $product->is_type('variation'));

					$product_attributes[$taxonomy_name] = $attribute;
					$has_changes = true;
				}
			} else {
				if (isset($product_attributes[$taxonomy_name])) {
					unset($product_attributes[$taxonomy_name]);
					$has_changes = true;
				}
			}
		}
	}

	if (!empty($product_attributes)) {
		$product->set_attributes($product_attributes);
	}

	if (!empty($data['custom_field'])) {
		foreach ($data['custom_field'] as $field_slug => $products_data) {
			if (isset($products_data[$product_id])) {
				$has_changes = true;
				break;
			}
		}
		if ($has_changes) {
			CBBE_Custom_Fields::save_fields($product_id, $data['custom_field']);
		}
	}

	if (!$has_changes) {
		return false;
	}

	$product->save();
	wc_delete_product_transients($product_id);

	do_action('cbbe_after_update_product', $product, $product_id, $data);

	return $product;

} catch (Exception $e) {
	self::log(sprintf('Error updating product %d: %s', $product_id, $e->getMessage()), 'error');
	return false;
}
}

public static function get_variable_product_ids($products) {
$variable_product_ids = [];
foreach ($products as $product) {
	if ($product->is_type('variation')) {
		$variable_product_ids[] = $product->get_parent_id();
	} elseif ($product->is_type('variable')) {
		$variable_product_ids[] = $product->get_id();
	}
}
return array_unique($variable_product_ids);
}

public static function update_bulk_field($product, $field_name, $value, $current_value = null) {
if ($value === 'no_change') {
	return;
}

switch ($field_name) {
	case 'bulk_regular_price':
	if ($value !== '') $product->set_regular_price($value);
	break;
	case 'bulk_sale_price':
	if ($value !== '') $product->set_sale_price($value);
	break;
	case 'bulk_cost_of_goods_sold':
	if ($value !== '') $product->set_cogs_value($value);
	break;
	case 'bulk_stock':
	if ($value !== '' && $value > 0) $product->set_stock_quantity($value);
	break;
	case 'bulk_weight':
	if ($value !== '') $product->set_weight($value);
	break;
	case 'bulk_length':
	if ($value !== '') $product->set_length($value);
	break;
	case 'bulk_width':
	if ($value !== '') $product->set_width($value);
	break;
	case 'bulk_height':
	if ($value !== '') $product->set_height($value);
	break;
	case 'bulk_shipping_class':
	if ($value !== '') $product->set_shipping_class_id($value);
	break;
	case 'bulk_post_status':
	if ($value !== '') $product->set_status($value);
	break;
	case 'bulk_enable_review':
	if ($value !== '') $product->set_reviews_allowed($value === 'yes');
	break;
	case 'bulk_sold_individually':
	if ($value !== '') $product->set_sold_individually($value === 'yes');
	break;
	case 'bulk_backorders':
	if ($value !== '') $product->set_backorders($value);
	break;
	case 'bulk_featured':
	if ($value !== '') $product->set_featured($value === 'yes');
	break;
	case 'bulk_pos_visibility':
	if ($value !== '') self::set_product_pos_visibility($product->get_id(), $value === 'yes');
	break;
	case 'bulk_manage_stock':
	if ($value !== '') $product->set_manage_stock($value === 'yes');
	break;
	case 'bulk_stock_status':
	if ($value !== '') $product->set_stock_status($value);
	break;
	case 'bulk_low_stock_threshold':
	if ($value !== '') $product->set_low_stock_amount($value);
	break;
	case 'bulk_upsells':
	$product->set_upsell_ids(is_array($value) ? array_map('intval', $value) : []);
	break;
	case 'bulk_cross_sells':
	$product->set_cross_sell_ids(is_array($value) ? array_map('intval', $value) : []);
	break;
}
}

public static function apply_price_changes($product, $field_name, $value, $current_price) {
if ($value === '' || !$current_price) {
	return;
}
$is_percentage = strpos($value, '%') !== false;
$numeric_value = $is_percentage ? floatval(str_replace('%', '', $value)) : wc_format_decimal($value);

switch ($field_name) {
	case 'bulk_increase_regular_price':
	$new_price = $is_percentage ? $current_price * (1 + $numeric_value / 100) : $current_price + $numeric_value;
	$product->set_regular_price($new_price);
	break;
	case 'bulk_decrease_regular_price':
	$new_price = $is_percentage ? $current_price * (1 - $numeric_value / 100) : max(0, $current_price - $numeric_value);
	$product->set_regular_price($new_price);
	break;
	case 'bulk_increase_sale_price':
	$new_price = $is_percentage ? $current_price * (1 + $numeric_value / 100) : $current_price + $numeric_value;
	$product->set_sale_price($new_price);
	break;
	case 'bulk_decrease_sale_price':
	$new_price = $is_percentage ? $current_price * (1 - $numeric_value / 100) : max(0, $current_price - $numeric_value);
	$product->set_sale_price($new_price);
	break;
}
}

public static function handle_bulk_taxonomies($product_id, $bulk_data) {
$taxonomy_operations = [
	'bulk_add_categories'    => ['product_cat',   'add'],
	'bulk_remove_categories' => ['product_cat',   'remove'],
	'bulk_add_tags'          => ['product_tag',   'add'],
	'bulk_remove_tags'       => ['product_tag',   'remove'],
	'bulk_add_brands'        => ['product_brand', 'add'],
	'bulk_remove_brands'     => ['product_brand', 'remove']
];

foreach ($taxonomy_operations as $field => $config) {
	if (empty($bulk_data[$field])) continue;
	[$taxonomy, $operation] = $config;
	if ($taxonomy === 'product_brand' && !taxonomy_exists('product_brand')) continue;
	$current_terms = wp_get_post_terms($product_id, $taxonomy, ['fields' => 'ids']);

	if ($operation === 'add') {
		$new_terms = array_unique(array_merge($current_terms, $bulk_data[$field]));
	} else {
		$new_terms = array_diff($current_terms, $bulk_data[$field]);
	}
	wp_set_post_terms($product_id, $new_terms, $taxonomy);
}

if (!empty($bulk_data) && is_array($bulk_data)) {
	foreach ($bulk_data as $key => $values) {
		if (!is_string($key)) continue;
		if (preg_match('#^bulk_(add|remove)_taxonomy_(.+)$#', $key, $matches)) {
			$op = $matches[1]; // add or remove
			$taxonomy = sanitize_key($matches[2]);
			if (empty($taxonomy) || !taxonomy_exists($taxonomy)) {
				continue;
			}
			$term_ids = is_array($values) ? array_map('intval', $values) : array_filter(array_map('intval', (array)explode(',', (string)$values)));
			if (empty($term_ids)) {
				continue;
			}
			$current_terms = wp_get_post_terms($product_id, $taxonomy, ['fields' => 'ids']);
			if ($op === 'add') {
				$new_terms = array_unique(array_merge($current_terms, $term_ids));
			} else {
				$new_terms = array_diff($current_terms, $term_ids);
			}
			wp_set_post_terms($product_id, $new_terms, $taxonomy);
		}
	}
}
}

public static function handle_bulk_upsells_crosssells($product_id, $bulk_data) {
$product = wc_get_product($product_id);
if (!$product) return;

$operations = [
	'bulk_add_upsells'        => ['upsells',      'add'],
	'bulk_remove_upsells'     => ['upsells',      'remove'],
	'bulk_add_cross_sells'    => ['cross_sells',  'add'],
	'bulk_remove_cross_sells' => ['cross_sells',  'remove']
];

foreach ($operations as $field => $config) {
	if (empty($bulk_data[$field])) continue;
	[$type, $operation] = $config;

	$current_ids = $type === 'upsells' ? $product->get_upsell_ids() : $product->get_cross_sell_ids();

	if ($operation === 'add') {
		$new_ids = array_unique(array_merge($current_ids, $bulk_data[$field]));
	} else {
		$new_ids = array_diff($current_ids, $bulk_data[$field]);
	}

	if ($type === 'upsells') {
		$product->set_upsell_ids($new_ids);
	} else {
		$product->set_cross_sell_ids($new_ids);
	}
}

$product->save();
}
}

class CBBE_Custom_Fields {

public static function save_fields($product_id, $custom_fields_data) {
$custom_fields = get_option('cbbe_custom_fields', []);
if (empty($custom_fields_data)) return;

foreach ($custom_fields_data as $field_slug => $products_data) {
	if (!isset($products_data[$product_id])) continue;
	$field_slug_clean = sanitize_text_field($field_slug);
	if (!isset($custom_fields[$field_slug_clean])) continue;
	$field_config = $custom_fields[$field_slug_clean];
	$field_value = $products_data[$product_id];

	if ($field_config['type'] === 'taxonomy' && !empty($field_config['taxonomy'])) {
		self::save_taxonomy_field($product_id, $field_config['taxonomy'], $field_value);
	} else {
		self::save_meta_field($product_id, $field_slug_clean, $field_value, $field_config);
	}
}
}

private static function save_taxonomy_field($product_id, $taxonomy, $term_ids) {
if (!taxonomy_exists($taxonomy)) return;
if (!is_array($term_ids)) {
	$term_ids = array_filter(array_map('intval', explode(',', $term_ids)));
} else {
	$term_ids = array_filter(array_map('intval', $term_ids));
}
wp_set_object_terms($product_id, $term_ids, $taxonomy);
}

private static function save_meta_field($product_id, $field_slug, $field_value, $field_config) {
if (is_array($field_value)) {
	$field_value = implode(',', array_map('sanitize_text_field', $field_value));
} else {
	$type = isset($field_config['type']) ? $field_config['type'] : 'text';
	switch ($type) {
		case 'int':
		$field_value = intval($field_value);
		break;
		case 'decimal1':
		case 'decimal2':
		case 'decimal3':
		$field_value = wc_format_decimal($field_value);
		break;
		case 'url':
		$field_value = esc_url_raw($field_value);
		break;
		case 'email':
		$field_value = sanitize_email($field_value);
		break;
		case 'textarea':
		$field_value = sanitize_textarea_field($field_value);
		break;
		case 'bool':
		$field_value = (bool) $field_value;
		break;
		default:
		$field_value = sanitize_text_field($field_value);
		break;
	}
}
update_post_meta($product_id, $field_slug, $field_value);
}

public static function save_bulk_fields($product_id, $bulk_custom_fields_data) {
$custom_fields = get_option('cbbe_custom_fields', []);
if (empty($bulk_custom_fields_data)) return;

foreach ($bulk_custom_fields_data as $field_slug => $field_value) {
	if ($field_value === 'no_change') continue;
	$field_slug_clean = sanitize_text_field($field_slug);
	if (!isset($custom_fields[$field_slug_clean])) continue;
	$field_config = $custom_fields[$field_slug_clean];
	if ($field_config['type'] === 'taxonomy' && !empty($field_config['taxonomy'])) {
		self::save_taxonomy_field($product_id, $field_config['taxonomy'], $field_value);
	} else {
		self::save_meta_field($product_id, $field_slug_clean, $field_value, $field_config);
	}
}
}
}

function cbbe_update_individual_products() {
CBBE_Product_Updater::verify_nonce();

// phpcs:ignore WordPress.Security.NonceVerification.Missing
$sanitized_data = CBBE_Product_Updater::sanitize_post_data($_POST);
$updated_products = [];

if (!empty($sanitized_data['modified_products'])) {
$product_ids = $sanitized_data['modified_products'];
} else {
	echo '<div class="notice notice-warning"><p>' . esc_html__('No products were modified.  Please make changes before saving.', 'coding-bunny-bulk-edit') . '</p></div>';
	return;
}

if (empty($product_ids)) {
	echo '<div class="notice notice-warning"><p>' . esc_html__('No products were modified. Please make changes before saving.', 'coding-bunny-bulk-edit') . '</p></div>';
	return;
}

foreach ($product_ids as $product_id) {
	if (!isset($sanitized_data['upsells'][$product_id])) {
		$sanitized_data['upsells'][$product_id] = [];
	}
	if (!isset($sanitized_data['cross_sells'][$product_id])) {
		$sanitized_data['cross_sells'][$product_id] = [];
	}
}

foreach ($product_ids as $product_id) {
	$product = CBBE_Product_Updater::update_single_product($product_id, $sanitized_data);
	if ($product) {
		$updated_products[] = $product;
	}
}

$variable_product_ids = CBBE_Product_Updater::get_variable_product_ids($updated_products);
foreach ($variable_product_ids as $parent_id) {
	if (!in_array($parent_id, $product_ids)) {
		CBBE_Product_Updater::update_single_product($parent_id, $sanitized_data);
	}
}

$count = count($updated_products);
if ($count > 0) {
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(
	/* translators: %d: number of products updated */
	_n('%d product successfully updated!', '%d products successfully updated!', $count, 'coding-bunny-bulk-edit'),
	$count
		)) . '</p></div>';
} else {
	echo '<div class="notice notice-warning"><p>' . esc_html__('No products were updated.', 'coding-bunny-bulk-edit') . '</p></div>';
}
}

function cbbe_update_bulk_products() {
CBBE_Product_Updater::verify_nonce();

// phpcs:ignore WordPress.Security.NonceVerification.Missing
if (!isset($_POST['bulk_update_products'])) return;

$bulk_fields = [
	'bulk_regular_price', 'bulk_sale_price', 'bulk_discount_percentage', 'bulk_stock',
	'bulk_weight', 'bulk_length', 'bulk_width', 'bulk_height', 'bulk_shipping_class',
	'bulk_post_status', 'bulk_enable_review', 'bulk_sold_individually', 'bulk_backorders',
	'bulk_featured', 'bulk_pos_visibility', 'bulk_manage_stock', 'bulk_stock_status', 'bulk_increase_regular_price',
	'bulk_decrease_regular_price', 'bulk_increase_sale_price', 'bulk_decrease_sale_price',
	'bulk_low_stock_threshold', 'bulk_cost_of_goods_sold', 'bulk_upsells', 'bulk_cross_sells'
];

$bulk_data = [];
foreach ($bulk_fields as $field) {
	if (in_array($field, ['bulk_upsells', 'bulk_cross_sells'], true)) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$bulk_data[$field] = isset($_POST[$field]) ? array_map('intval', wp_unslash($_POST[$field])) : [];
	} else {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$bulk_data[$field] = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : '';
	}
}

$array_fields = [
	'bulk_add_categories', 'bulk_remove_categories', 'bulk_add_tags',
	'bulk_remove_tags', 'bulk_add_brands', 'bulk_remove_brands',
	'bulk_add_upsells', 'bulk_remove_upsells', 'bulk_add_cross_sells', 'bulk_remove_cross_sells'
];
foreach ($array_fields as $field) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$bulk_data[$field] = isset($_POST[$field]) ? array_map('intval', wp_unslash($_POST[$field])) : [];
}

// phpcs:ignore WordPress.Security.NonceVerification.Missing
if (!empty($_POST) && is_array($_POST)) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	foreach ($_POST as $pkey => $pval) {
		if (!is_string($pkey)) {
			continue;
		}
		if (preg_match('#^bulk_(add|remove)_taxonomy_(.+)$#', $pkey, $m)) {
			$field_key = sanitize_text_field($pkey);
			$taxonomy_raw = $m[2];
			$taxonomy = sanitize_key($taxonomy_raw);
			if (empty($taxonomy) || !taxonomy_exists($taxonomy)) {
				continue;
			}
			if (is_array($pval)) {
				$vals = array_map('intval', wp_unslash($pval));
			} else {
				$raw = sanitize_text_field(wp_unslash($pval));
				$vals = array_filter(array_map('intval', explode(',', $raw)));
			}
			$bulk_data[$field_key] = $vals;
		}
	}
}

$bulk_data['bulk_custom_field'] = [];
// phpcs:ignore WordPress.Security.NonceVerification.Missing
if (isset($_POST['bulk_custom_field']) && is_array($_POST['bulk_custom_field'])) {
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
	$bulk_custom_fields = wp_unslash($_POST['bulk_custom_field']);

	foreach ($bulk_custom_fields as $slug => $value) {
		$sanitized_slug = sanitize_text_field($slug);

		if (
		$value === '' ||
			$value === null ||
				$value === 'no_change' ||
					(is_array($value) && count(array_filter($value, function($v) { return $v !== '' && $v !== 'no_change'; })) === 0)
						) continue;

		if (is_array($value)) {
			$sanitized_value = array_map('sanitize_text_field', $value);
		} else {
			$sanitized_value = sanitize_text_field($value);
		}

		$bulk_data['bulk_custom_field'][$sanitized_slug] = $sanitized_value;
	}
}

// phpcs:ignore WordPress.Security.NonceVerification.Missing
if (empty($_POST['selected_products']) || !is_array($_POST['selected_products'])) {
	echo '<div class="error"><p>' . esc_html__('Please select at least one product to update.', 'coding-bunny-bulk-edit') . '</p></div>';
	return;
}
// phpcs:ignore WordPress.Security.NonceVerification.Missing
$selected_products = array_map('intval', wp_unslash($_POST['selected_products']));

$updated_count = 0;

foreach ($selected_products as $product_id) {
	$product = wc_get_product($product_id);
	if (!$product) continue;
	$current_regular_price = $product->get_regular_price();
	$current_sale_price = $product->get_sale_price();

	foreach ($bulk_fields as $field) {
		CBBE_Product_Updater::update_bulk_field($product, $field, $bulk_data[$field]);
	}

	if (!empty($bulk_data['bulk_discount_percentage']) && is_numeric($bulk_data['bulk_discount_percentage'])) {
		$percentage = floatval($bulk_data['bulk_discount_percentage']);
		if ($percentage > 0 && $percentage < 100 && !empty($current_regular_price)) {
			$discount = ($current_regular_price * $percentage) / 100;
			$sale_price = $current_regular_price - $discount;
			$product->set_sale_price($sale_price);
		} elseif ($percentage == 100) {
			$product->set_sale_price('');
		}
	}

	$price_fields = [
		'bulk_increase_regular_price', 'bulk_decrease_regular_price',
		'bulk_increase_sale_price', 'bulk_decrease_sale_price'
	];
	foreach ($price_fields as $field) {
		$current_price = strpos($field, 'sale') !== false ? $current_sale_price : $current_regular_price;
		CBBE_Product_Updater::apply_price_changes($product, $field, $bulk_data[$field], $current_price);
	}

	CBBE_Product_Updater::handle_bulk_taxonomies($product_id, $bulk_data);

	CBBE_Product_Updater::handle_bulk_upsells_crosssells($product_id, $bulk_data);

	if (!empty($bulk_data['bulk_custom_field'])) {
		CBBE_Custom_Fields::save_bulk_fields($product_id, $bulk_data['bulk_custom_field']);
	}

	$product->save();
	wc_delete_product_transients($product_id);
	$updated_count++;
}

echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(
/* translators: %d: number of products updated */
sprintf(__('%d products successfully updated!', 'coding-bunny-bulk-edit'), $updated_count)
	) . '</p></div>';
}

function cbbe_save_custom_fields($product_id, $custom_fields_data) {
CBBE_Custom_Fields::save_fields($product_id, $custom_fields_data);
}

function cbbe_save_bulk_custom_fields($product_id, $bulk_custom_fields_data) {
CBBE_Custom_Fields::save_bulk_fields($product_id, $bulk_custom_fields_data);
}