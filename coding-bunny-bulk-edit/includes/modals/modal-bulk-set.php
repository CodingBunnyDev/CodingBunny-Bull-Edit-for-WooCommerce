<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cbbe_render_manage_bulk_fields_modal() {
	$custom_fields = cbbe_sanitize_custom_fields( get_option( 'cbbe_custom_fields', array() ) );
	$bulk_fields   = cbbe_get_bulk_fields( $custom_fields );
	$bulk_fields_order = get_option( 'cbbe_bulk_fields_order' );
	$bulk_fields_order = is_array( $bulk_fields_order ) ? $bulk_fields_order : array_keys( $bulk_fields );
	$bulk_fields_order = array_values( array_unique( array_merge( $bulk_fields_order, array_keys( $bulk_fields ) ) ) );
	$enabled_bulk_fields = get_option( 'cbbe_bulk_enabled_fields' );
	$enabled_bulk_fields = is_array( $enabled_bulk_fields ) ? $enabled_bulk_fields : array_keys( $bulk_fields );
	?>
	<div id="manage-bulk-fields-modal" class="cbbe-modal" style="display: none;">
		<div class="cbbe-modal-content">
			<span class="cbbe-modal-close" id="close-bulk-fields-modal">&times;</span>
			<h3><?php esc_html_e( 'Manage Bulk Edit Fields', 'coding-bunny-bulk-edit' ); ?></h3>
			<p class="cbbe-modal-description">
				<?php esc_html_e( 'Select the fields available for bulk editing and reorder them using drag & drop.', 'coding-bunny-bulk-edit' ); ?>
			</p>
			<form method="post" action="" id="bulk-fields-form">
				<?php wp_nonce_field( 'cbbe_save_bulk_fields_options' ); ?>
				<input type="hidden" name="cbbe_save_bulk_fields_only" value="1">
				<div class="cbbe-modal-body">
					<ul id="bulk-column-order" class="cbbe-column-grid">
						<?php
						foreach ( $bulk_fields_order as $field_key ) :
							if ( ! isset( $bulk_fields[ $field_key ] ) ) {
								continue;
							}
							$label = $bulk_fields[ $field_key ];
							?>
							<li data-bulkfield="<?php echo esc_attr( $field_key ); ?>" class="sortable-item ui-sortable-handle">
								<span>
									<span class="dashicons dashicons-move" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php esc_html_e( 'Drag to reorder', 'coding-bunny-bulk-edit' ); ?></span>
								</span>
								<label class="cbbe-toggle-checkbox">
									<span class="cbbe-column-label"><?php echo esc_html( $label ); ?></span>
									<input type="checkbox" name="bulk_enabled_fields[]" value="<?php echo esc_attr( $field_key ); ?>" <?php checked( in_array( $field_key, $enabled_bulk_fields, true ), true ); ?>>
									<span class="cbbe-slider"></span>
								</label>
								<input type="hidden" name="bulk_order[]" value="<?php echo esc_attr( $field_key ); ?>">
							</li>
							<?php
						endforeach;
						?>
					</ul>
				</div>
				<div class="cbbe-modal-actions">
					<button type="submit" name="update_products" class="button button-primary" aria-label="<?php echo esc_attr__( 'Save bulk fields', 'coding-bunny-bulk-edit' ); ?>">
						<span class="dashicons dashicons-database-view" aria-hidden="true"></span>
						<?php esc_html_e( 'Save Changes', 'coding-bunny-bulk-edit' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
	<?php
}