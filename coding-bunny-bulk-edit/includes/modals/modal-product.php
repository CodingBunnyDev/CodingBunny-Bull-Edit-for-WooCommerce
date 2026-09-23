<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cbbe_render_add_product_modal() {
	?>
	<div id="cbbe-add-product-modal" class="cbbe-modal" style="display:none;">
		<div class="cbbe-modal-content">
			<span class="cbbe-modal-close" id="close-add-product-modal">&times;</span>
			<h3><?php esc_html_e('Add New Product', 'coding-bunny-bulk-edit'); ?></h3>
			<form id="cbbe-add-product-form">
				<?php wp_nonce_field('cbbe_add_product_action', 'nonce'); ?>

				<div class="cbbe-field-container">
					<label for="cbbe-product-name" class="cbbe-label">
						<?php esc_html_e('Product Name', 'coding-bunny-bulk-edit'); ?>
					</label>
					<input type="text" id="cbbe-product-name" name="product_name" class="cbbe-input" required style="width: 100%;">
				</div>

				<div class="cbbe-field-container">
					<label for="cbbe-product-type" class="cbbe-label">
						<?php esc_html_e('Product Type', 'coding-bunny-bulk-edit'); ?>
					</label>
					<select id="cbbe-product-type" name="product_type" class="cbbe-select" required style="width: 100%;">
						<option value="simple"><?php esc_html_e('Simple Product', 'coding-bunny-bulk-edit'); ?></option>
						<option value="variable"><?php esc_html_e('Variable Product', 'coding-bunny-bulk-edit'); ?></option>
						<option value="downloadable"><?php esc_html_e('Downloadable Product', 'coding-bunny-bulk-edit'); ?></option>
					</select>
				</div>

				<div class="cbbe-field-container" id="cbbe-regular-price-container">
					<label for="cbbe-regular-price" class="cbbe-label">
						<?php esc_html_e('Regular Price', 'coding-bunny-bulk-edit'); ?> (<?php echo esc_html(get_woocommerce_currency_symbol()); ?>)
					</label>
					<input type="number" step="0.01" id="cbbe-regular-price" name="regular_price" class="cbbe-input" placeholder="0.00" required style="width: 100%;">
					<small>
						<?php esc_html_e('For variable products, this price will be applied to all variations.', 'coding-bunny-bulk-edit'); ?>
					</small>
				</div>

				<div id="cbbe-variable-options" style="display: none;">
					<div class="cbbe-field-container">
						<label class="cbbe-label">
							<?php esc_html_e('Select Attributes', 'coding-bunny-bulk-edit'); ?>
						</label>
						<small>
							<?php esc_html_e('Select attributes and their values.  All possible variations will be generated automatically. ', 'coding-bunny-bulk-edit'); ?>
						</small>

						<div id="cbbe-attributes-container">
							<?php
							if (function_exists('wc_get_attribute_taxonomies')) {
								$attributes = wc_get_attribute_taxonomies();
								if (!empty($attributes)) {
									foreach ($attributes as $attribute) {
										$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
										$terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
										if (!empty($terms) && ! is_wp_error($terms)) {
											echo '<div class="cbbe-attribute-group">';
											echo '<label style="font-weight: 600; display: block;">';
											echo '<input type="checkbox" class="cbbe-attribute-toggle" data-attribute="' . esc_attr($taxonomy) . '"> ';
											echo esc_html($attribute->attribute_label);
											echo '</label>';
											echo '<div class="cbbe-attribute-terms" data-attribute="' . esc_attr($taxonomy) . '" style="display: none; padding-left: 20px;">';
											foreach ($terms as $term) {
												echo '<label style="display: block; margin: 5px 0;">';
												echo '<input type="checkbox" name="attributes[' . esc_attr($taxonomy) . '][]" value="' . esc_attr($term->term_id) . '"> ';
												echo esc_html($term->name);
												echo '</label>';
											}
											echo '</div>';
											echo '</div>';
										}
									}
								} else {
									echo '<p>' . esc_html__('No attributes available.', 'coding-bunny-bulk-edit') . '</p>';
								}
							}
							?>
						</div>
					</div>
				</div>

				<div class="cbbe-modal-actions">
					<button type="button" id="cbbe-cancel-add-product" class="button" style="margin-right: 10px;">
						<?php esc_html_e('Cancel', 'coding-bunny-bulk-edit'); ?>
					</button>
					<button type="submit" class="button button-primary" id="cbbe-submit-add-product">
						<?php esc_html_e('Create Product', 'coding-bunny-bulk-edit'); ?>
					</button>
				</div>

				<div id="cbbe-add-product-message"></div>
			</form>
		</div>
	</div>
	<?php
}