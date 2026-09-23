<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cbbe_render_filter_modal() {
	$categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	$attributes = wc_get_attribute_taxonomies();

	$selected_category = isset( $_GET['product_category'] ) ? sanitize_text_field( wp_unslash( $_GET['product_category'] ) ) : '';
	$availability_filter = isset( $_GET['availability'] ) ? sanitize_text_field( wp_unslash( $_GET['availability'] ) ) : '';
	$selected_attribute = isset( $_GET['product_attribute'] ) ? sanitize_text_field( wp_unslash( $_GET['product_attribute'] ) ) : '';
	$selected_term = isset( $_GET['product_term'] ) ? sanitize_text_field( wp_unslash( $_GET['product_term'] ) ) : '';
	$selected_status = isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '';
	$selected_shipping_class = isset( $_GET['shipping_class'] ) ? sanitize_text_field( wp_unslash( $_GET['shipping_class'] ) ) : '';
	$search_product_name = isset( $_GET['search_product_name'] ) ? sanitize_text_field( wp_unslash( $_GET['search_product_name'] ) ) : '';
	$search_product_id = isset( $_GET['search_product_id'] ) ? absint( $_GET['search_product_id'] ) : '';
	$selected_tag = isset( $_GET['product_tag'] ) ? sanitize_text_field( wp_unslash( $_GET['product_tag'] ) ) : '';
	$selected_featured = isset( $_GET['featured'] ) ? sanitize_text_field( wp_unslash( $_GET['featured'] ) ) : '';
	$selected_pos_visibility = isset( $_GET['pos_visibility'] ) ? sanitize_text_field( wp_unslash( $_GET['pos_visibility'] ) ) : '';
	$selected_brand = isset( $_GET['product_brand'] ) ? sanitize_text_field( wp_unslash( $_GET['product_brand'] ) ) : '';
	$selected_blocksy_brand = isset( $_GET['product_brands'] ) ? sanitize_text_field( wp_unslash( $_GET['product_brands'] ) ) : '';
	$price_compare = isset( $_GET['price_compare'] ) ? sanitize_text_field( wp_unslash( $_GET['price_compare'] ) ) : '';
	$price_value = isset( $_GET['price_value'] ) ? floatval( $_GET['price_value'] ) : '';
	$sale_price_compare = isset( $_GET['sale_price_compare'] ) ? sanitize_text_field( wp_unslash( $_GET['sale_price_compare'] ) ) : '';
	$sale_price_value = isset( $_GET['sale_price_value'] ) ? floatval( $_GET['sale_price_value'] ) : '';
	?>

	<div id="cbbe-filter-modal" class="cbbe-modal" style="display:none;">
		<div class="cbbe-modal-content">
			<span class="cbbe-modal-close" id="close-filter-modal">&times;</span>
			<form method="get" action="" id="cbbe-filter-form">
				<input type="hidden" name="page" value="coding-bunny-bulk-edit">
				<?php wp_nonce_field( 'cbbe_filter_products' ); ?>
				<input type="hidden" id="cbbe_filter_terms_nonce" value="<?php echo esc_attr( wp_create_nonce( 'cbbe_get_attribute_terms' ) ); ?>">
				<h3><?php esc_html_e( 'Search & Filter', 'coding-bunny-bulk-edit' ); ?></h3>
				<div style="display:block;">
					<?php
					// Filter Row 1
					echo '<div class="cbbe-filter-container">';
					echo '<div class="cbbe-field-container">';
					echo '<label for="search_product_name" class="cbbe-label">' . esc_html__( 'Search by name', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<input type="text" name="search_product_name" id="search_product_name" class="filter-input" value="' . esc_attr( $search_product_name ) . '" placeholder="' . esc_attr__( 'Product name...', 'coding-bunny-bulk-edit' ) . '">';
					echo '</div>';
					echo '<div class="cbbe-field-container">';
					echo '<label for="search_product_id" class="cbbe-label">' . esc_html__( 'Search by ID', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<input type="number" name="search_product_id" id="search_product_id" class="filter-input" value="' . esc_attr( $search_product_id ) . '" placeholder="' . esc_attr__( 'Product ID...', 'coding-bunny-bulk-edit' ) . '">';
					echo '</div>';
					echo '</div>';

					// Filter Row 2
					echo '<div class="cbbe-filter-container">';
					echo '<div class="cbbe-field-container">';
					echo '<label for="product_category" class="cbbe-label">' . esc_html__( 'Filter by category', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="product_category" id="product_category" class="filter-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					$sorted_categories = array();
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
						$category_name = $category->parent ? '- ' . esc_html( $category->name ) : esc_html( $category->name );
						echo '<option value="' . esc_attr( $category->term_id ) . '"' . selected( $selected_category, $category->term_id, false ) . '>' . esc_html( $category_name ) . '</option>';
					}
					echo '</select>';
					echo '</div>';
					echo '<div class="cbbe-field-container">';
					echo '<label for="product_tag" class="cbbe-label">' . esc_html__( 'Filter by tag', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="product_tag" id="product_tag" class="filter-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					$tags = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => false ) );
					foreach ( $tags as $tag ) {
						echo '<option value="' . esc_attr( $tag->slug ) . '"' . selected( $selected_tag, $tag->slug, false ) . '>' . esc_html( $tag->name ) . '</option>';
					}
					echo '</select>';
					echo '</div>';
					echo '<div class="cbbe-field-container">';
					echo '<label for="product_attribute" class="cbbe-label">' . esc_html__( 'Filter by attributes', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="product_attribute" id="product_attribute" class="filter-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					if ( ! empty( $attributes ) ) {
						foreach ( $attributes as $attribute ) {
							echo '<option value="' . esc_attr( $attribute->attribute_name ) . '"' . selected( $selected_attribute, $attribute->attribute_name, false ) . '>' . esc_html( $attribute->attribute_label ) . '</option>';
						}
					}
					echo '</select>';
					echo '</div>';

					$term_wrap_style = ! empty( $selected_attribute ) ? '' : 'display:none;';
					echo '<div class="cbbe-field-container" id="cbbe-product-term-wrap" style="' . esc_attr( $term_wrap_style ) . '">';
					echo '<label for="product_term" class="cbbe-label">' . esc_html__( 'Filter by term', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="product_term" id="product_term" class="filter-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					if ( ! empty( $selected_attribute ) ) {
						$terms = get_terms( array( 'taxonomy' => 'pa_' . $selected_attribute, 'hide_empty' => false ) );
						if ( ! is_wp_error( $terms ) ) {
							foreach ( $terms as $term ) {
								echo '<option value="' . esc_attr( $term->slug ) . '"' . selected( $selected_term, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
							}
						}
					}
					echo '</select>';
					echo '</div>';

					if ( taxonomy_exists( 'product_brand' ) ) {
						echo '<div class="cbbe-field-container">';
						echo '<label for="product_brand" class="cbbe-label">' . esc_html__( 'Filter by brand', 'coding-bunny-bulk-edit' ) . '</label>';
						echo '<select name="product_brand" id="product_brand" class="filter-select">';
						echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
						$brands = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) );
						foreach ( $brands as $brand ) {
							echo '<option value="' . esc_attr( $brand->slug ) . '"' . selected( $selected_brand, $brand->slug, false ) . '>' . esc_html( $brand->name ) . '</option>';
						}
						echo '</select>';
						echo '</div>';
					}
					echo '</div>';

					// Filter Row 3
					echo '<div class="cbbe-filter-container">';
					echo '<div class="cbbe-field-container">';
					echo '<label for="availability" class="cbbe-label">' . esc_html__( 'Filter by stock status', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="availability" id="availability" class="filter-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="instock"' . selected( $availability_filter, 'instock', false ) . '>' . esc_html__( 'In stock', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="outofstock"' . selected( $availability_filter, 'outofstock', false ) . '>' . esc_html__( 'Out of stock', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="onbackorder"' . selected( $availability_filter, 'onbackorder', false ) . '>' . esc_html__( 'On backorder', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '</select>';
					echo '</div>';
					echo '<div class="cbbe-field-container">';
					echo '<label for="post_status" class="cbbe-label">' . esc_html__( 'Filter by status', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="post_status" id="post_status" class="filter-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="publish"' . selected( $selected_status, 'publish', false ) . '>' . esc_html__( 'Published', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="draft"' . selected( $selected_status, 'draft', false ) . '>' . esc_html__( 'Draft', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="future"' . selected( $selected_status, 'future', false ) . '>' . esc_html__( 'Scheduled', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '</select>';
					echo '</div>';
					echo '<div class="cbbe-field-container">';
					echo '<label for="featured" class="cbbe-label">' . esc_html__( 'Filter by featured', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="featured" id="featured" class="filter-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="yes"' . selected( $selected_featured, 'yes', false ) . '>' . esc_html__( 'Featured', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="no"' . selected( $selected_featured, 'no', false ) . '>' . esc_html__( 'Not Featured', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '</select>';
					echo '</div>';
					if ( taxonomy_exists( 'pos_product_visibility' ) ) {
						echo '<div class="cbbe-field-container">';
						echo '<label for="pos_visibility" class="cbbe-label">' . esc_html__( 'Filter by Available for POS', 'coding-bunny-bulk-edit' ) . '</label>';
						echo '<select name="pos_visibility" id="pos_visibility" class="filter-select">';
						echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
						echo '<option value="yes"' . selected( $selected_pos_visibility, 'yes', false ) . '>' . esc_html__( 'Yes', 'coding-bunny-bulk-edit' ) . '</option>';
						echo '<option value="no"' . selected( $selected_pos_visibility, 'no', false ) . '>' . esc_html__( 'No', 'coding-bunny-bulk-edit' ) . '</option>';
						echo '</select>';
						echo '</div>';
					}
					echo '<div class="cbbe-field-container">';
					echo '<label for="shipping_class" class="cbbe-label">' . esc_html__( 'Filter by shipping class', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="shipping_class" id="shipping_class" class="filter-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="no_shipping_class">' . esc_html__( 'No Shipping Class', 'coding-bunny-bulk-edit' ) . '</option>';
					$shipping_classes = WC()->shipping->get_shipping_classes();
					foreach ( $shipping_classes as $shipping_class ) {
						echo '<option value="' . esc_attr( $shipping_class->slug ) . '"' . selected( $selected_shipping_class, $shipping_class->slug, false ) . '>' . esc_html( $shipping_class->name ) . '</option>';
					}
					echo '</select>';
					echo '</div>';
					echo '</div>';

					// Filter Row 4
					echo '<div class="cbbe-filter-container">';
					echo '<div class="cbbe-field-container">';
					echo '<label for="price_compare" class="cbbe-label">' . esc_html__( 'Filter by regular price', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="price_compare" id="price_compare" class="cbbe-filter-price-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="equal"' . selected( $price_compare, 'equal', false ) . '>' . esc_html__( 'Equal to', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="less_than"' . selected( $price_compare, 'less_than', false ) . '>' . esc_html__( 'Less than or equal to', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="greater_than"' . selected( $price_compare, 'greater_than', false ) . '>' . esc_html__( 'Greater than or equal to', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '</select>';
					echo '<input type="number" step="0.01" name="price_value" id="price_value" class="cbbe-filter-price-input" value="' . esc_attr( $price_value ) . '" placeholder="' . esc_attr__( 'Price...', 'coding-bunny-bulk-edit' ) . '">';
					echo '</div>';
					echo '<div class="cbbe-field-container">';
					echo '<label for="sale_price_compare" class="cbbe-label">' . esc_html__( 'Filter by sale price', 'coding-bunny-bulk-edit' ) . '</label>';
					echo '<select name="sale_price_compare" id="sale_price_compare" class="cbbe-filter-price-select">';
					echo '<option value="">' . esc_html__( 'All', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="equal"' . selected( $sale_price_compare, 'equal', false ) . '>' . esc_html__( 'Equal to', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="less_than"' . selected( $sale_price_compare, 'less_than', false ) . '>' . esc_html__( 'Less than or equal to', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '<option value="greater_than"' . selected( $sale_price_compare, 'greater_than', false ) . '>' . esc_html__( 'Greater than or equal to', 'coding-bunny-bulk-edit' ) . '</option>';
					echo '</select>';
					echo '<input type="number" step="0.01" name="sale_price_value" id="sale_price_value" class="cbbe-filter-price-input" value="' . esc_attr( $sale_price_value ) . '" placeholder="' . esc_attr__( 'Price...', 'coding-bunny-bulk-edit' ) . '">';
					echo '<input type="hidden" name="paged" value="1">';
					echo '</div>';
					echo '</div>';
					?>

					<div class="cbbe-modal-actions">
						<input type="submit" value="<?php esc_html_e( 'Apply', 'coding-bunny-bulk-edit' ); ?>" class="button button-primary" style="margin-right: 10px;">
						<button type="button" id="cbbe-filter-reset" class="button"><?php esc_html_e( 'Reset All', 'coding-bunny-bulk-edit' ); ?></button>
					</div>
				</div>
			</form>
		</div>
	</div>
	<?php
}