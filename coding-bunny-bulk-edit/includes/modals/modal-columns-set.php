<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cbbe_render_manage_columns_modal() {
	$custom_fields = cbbe_sanitize_custom_fields( get_option( 'cbbe_custom_fields', array() ) );
	$all_columns = cbbe_get_all_columns( $custom_fields );
	$cbbe_columns_opt = get_option( 'cbbe_columns' );
	$selected_columns = is_array( $cbbe_columns_opt ) ? $cbbe_columns_opt : array_keys( $all_columns );
	$cbbe_order_opt = get_option( 'cbbe_order' );
	$column_order = is_array( $cbbe_order_opt ) ? $cbbe_order_opt : array_keys( $all_columns );
	?>
	<div id="manage-columns-modal" class="cbbe-modal" style="display: none;">
		<div class="cbbe-modal-content">
			<span class="cbbe-modal-close" id="close-columns-modal">&times;</span>
			<h3><?php esc_html_e( 'Manage Columns', 'coding-bunny-bulk-edit' ); ?></h3>
			<p class="cbbe-modal-description">
				<?php esc_html_e( 'Select which columns to display in the product table and reorder them using drag & drop.', 'coding-bunny-bulk-edit' ); ?>
			</p>
			<form method="post" action="" id="columns-form">
				<?php wp_nonce_field( 'cbbe_save_columns_options' ); ?>
				<input type="hidden" name="cbbe_save_columns_only" value="1">
				<div class="cbbe-modal-body">
					<ul id="column-order" class="cbbe-column-grid">
						<?php
						$columns_to_display = array_unique( array_merge( $column_order, array_keys( $all_columns ) ) );
						foreach ( $columns_to_display as $column_key ) :
							if ( isset( $all_columns[ $column_key ] ) ) :
								?>
								<li data-column="<?php echo esc_attr( $column_key ); ?>" class="sortable-item ui-sortable-handle">
									<span>
										<span class="dashicons dashicons-move" aria-hidden="true"></span>
										<span class="screen-reader-text"><?php esc_html_e( 'Drag to reorder', 'coding-bunny-bulk-edit' ); ?></span>
									</span>
									<label class="cbbe-toggle-checkbox">
										<span class="cbbe-label"><?php echo esc_html( $all_columns[ $column_key ] ); ?></span>
										<input type="checkbox" name="columns[]" value="<?php echo esc_attr( $column_key ); ?>" <?php checked( in_array( $column_key, $selected_columns, true ), true ); ?>>
										<span class="cbbe-slider"></span>
									</label>
									<input type="hidden" name="order[]" value="<?php echo esc_attr( $column_key ); ?>">
								</li>
								<?php
							endif;
						endforeach;
						?>
					</ul>
				</div>
				<div class="cbbe-modal-actions">
					<button type="submit" name="update_products" class="button button-primary" aria-label="<?php echo esc_attr__( 'Save columns', 'coding-bunny-bulk-edit' ); ?>">
						<span class="dashicons dashicons-database-view" aria-hidden="true"></span>
						<?php esc_html_e( 'Save Changes', 'coding-bunny-bulk-edit' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
	<?php
}