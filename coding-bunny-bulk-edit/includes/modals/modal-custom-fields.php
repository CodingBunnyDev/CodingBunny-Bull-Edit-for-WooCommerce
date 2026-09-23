<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cbbe_render_manage_custom_fields_modal() {
	global $cbbe_allowed_types;

	$custom_fields          = cbbe_sanitize_custom_fields( get_option( 'cbbe_custom_fields', array() ) );
	$detected_custom_fields = cbbe_detect_product_custom_fields();

	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
	$excluded_taxonomies = array( 'category', 'post_tag', 'nav_menu', 'link_category', 'post_format' );
	$available_taxonomies = array();

	foreach ( $taxonomies as $tax ) {
		if ( ! in_array( $tax->name, $excluded_taxonomies, true ) ) {
			$available_taxonomies[ $tax->name ] = $tax->label . ' (' . $tax->name . ')';
		}
	}
	?>
	<div id="cbbe-custom-fields-modal" class="cbbe-modal" style="display: none;">
		<div class="cbbe-modal-content">
			<span class="cbbe-modal-close" id="cancel-custom-fields">&times;</span>
			<h3><?php esc_html_e( 'Custom Fields', 'coding-bunny-bulk-edit' ); ?></h3>
			<p class="cbbe-modal-description">
				<?php esc_html_e( 'Add, edit, or remove custom fields in your products.', 'coding-bunny-bulk-edit' ); ?>
			</p>
			<div class="cbbe-modal-body">
				<div id="cbbe-custom-fields-message"></div>				
				<div id="cbbe-existing-custom-fields-container">
					<?php cbbe_render_existing_custom_fields_table( $custom_fields, $cbbe_allowed_types, $available_taxonomies ); ?>
				</div>
				<form id="custom-fields-form">
					<?php wp_nonce_field( 'cbbe_save_custom_fields', 'cbbe_custom_fields_nonce' ); ?>
					<?php wp_nonce_field( 'cbbe_remove_custom_field', 'cbbe_remove_custom_field_nonce' ); ?>
					<?php wp_nonce_field( 'cbbe_reload_custom_fields', 'cbbe_reload_custom_fields_nonce' ); ?>

					<div class="add-custom-fields">
						<h4><?php esc_html_e( 'Add New Custom Fields', 'coding-bunny-bulk-edit' ); ?></h4>
						<div id="custom-fields-container">
							<?php cbbe_render_custom_field_row( $detected_custom_fields, $custom_fields, $cbbe_allowed_types, $available_taxonomies ); ?>
						</div>
						<div>
							<button type="button" id="cbbe-add-custom-field" class="button button-secondary" aria-label="<?php echo esc_attr__( 'Add new custom field', 'coding-bunny-bulk-edit' ); ?>">
								<span class="dashicons dashicons-insert" aria-hidden="true"></span>
								<?php esc_html_e( 'Add New Custom Field', 'coding-bunny-bulk-edit' ); ?>
							</button>
						</div>
					</div>
					<div class="cbbe-modal-actions">
						<button type="submit" name="cbbe_save_custom_fields" class="button button-primary" aria-label="<?php echo esc_attr__( 'Save custom fields', 'coding-bunny-bulk-edit' ); ?>">
							<span class="dashicons dashicons-database-view" aria-hidden="true"></span>
							<?php esc_html_e( 'Save Changes', 'coding-bunny-bulk-edit' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script>
		var cbbeDetectedFields = <?php echo wp_json_encode( $detected_custom_fields ); ?>;
		var cbbeCustomFieldTypes = <?php echo wp_json_encode( array_map( function( $type ) {
			return array(
				'value' => $type,
				'label' => $type === 'bool' ? 'True/False' : ucfirst( $type ),
			);
		}, $cbbe_allowed_types ) ); ?>;
		var cbbeAvailableTaxonomies = <?php echo wp_json_encode( array_map( function( $slug, $label ) {
			return array( 'value' => $slug, 'label' => $label );
		}, array_keys( $available_taxonomies ), array_values( $available_taxonomies ) ) ); ?>;
		</script>
		<?php
	}

	function cbbe_render_custom_field_row( $detected_custom_fields, $custom_fields, $cbbe_allowed_types, $available_taxonomies ) {
		?>
		<div class="custom-field-row">
			<select class="custom-field-selector regular-text" style="max-width: 250px;">
				<option value=""><?php esc_html_e( '-- Select existing field or type manually --', 'coding-bunny-bulk-edit' ); ?></option>

				<?php foreach ( $detected_custom_fields as $cf_key => $cf_label ) : ?>
					<?php if ( ! isset( $custom_fields[ $cf_key ] ) ) : ?>
						<option value="<?php echo esc_attr( $cf_key ); ?>">
							<?php echo esc_html( $cf_label . ' (' . $cf_key . ')' ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>

			<input type="text" name="custom_field_slugs[]" placeholder="<?php esc_attr_e( 'Meta key (e.g., custom_field)', 'coding-bunny-bulk-edit' ); ?>" class="regular-text custom-field-metakey">

			<input type="text" name="custom_field_labels[]" placeholder="<?php esc_attr_e( 'Display label', 'coding-bunny-bulk-edit' ); ?>" class="regular-text custom-field-label">

			<select name="custom_field_types[]" class="custom-field-type">
				<?php foreach ( $cbbe_allowed_types as $type ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>">
						<?php echo esc_html( $type === 'bool' ? 'True/False' : ucfirst( $type ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="custom_field_taxonomies[]" class="custom-field-taxonomy" style="display:none;">
				<?php foreach ( $available_taxonomies as $tax_slug => $tax_label ) : ?>
					<option value="<?php echo esc_attr( $tax_slug ); ?>">
						<?php echo esc_html( $tax_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	function cbbe_render_existing_custom_fields_table( $custom_fields, $cbbe_allowed_types, $available_taxonomies ) {
		if ( empty( $custom_fields ) ) {
			return;
		}
		?>
		<div class="existing-custom-fields">
			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Slug', 'coding-bunny-bulk-edit' ); ?></th>
						<th><?php esc_html_e( 'Label', 'coding-bunny-bulk-edit' ); ?></th>
						<th><?php esc_html_e( 'Type', 'coding-bunny-bulk-edit' ); ?></th>
						<th><?php esc_html_e( 'Taxonomy', 'coding-bunny-bulk-edit' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'coding-bunny-bulk-edit' ); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php foreach ( $custom_fields as $slug => $field_info ) : ?>
						<tr data-cf-slug="<?php echo esc_attr( $slug ); ?>">
							<td><code><?php echo esc_html( $slug ); ?></code></td>
							<td><span class="cf-label-view"><?php echo esc_html( $field_info['label'] ); ?></span></td>
							<td><span class="cf-type-view"><?php echo esc_html( $field_info['type'] === 'bool' ? 'True/False' : ucfirst( $field_info['type'] ) ); ?></span></td>
							<td><span class="cf-taxonomy-view"><?php echo ! empty( $field_info['taxonomy'] ) ? esc_html( $field_info['taxonomy'] ) : ''; ?></span></td>
							<td>
								<span class="cf-action-view">
									<button type="button" class="button button-secondary cf-edit-btn">
										<span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
										<?php esc_html_e( 'Edit', 'coding-bunny-bulk-edit' ); ?>
									</button>
									<button type="button" class="button button-secondary cf-remove-btn" data-slug="<?php echo esc_attr( $slug ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
										<?php esc_html_e( 'Delete', 'coding-bunny-bulk-edit' ); ?>
									</button>
								</span>

								<form class="cf-edit-form" style="display:none; margin:0;">
									<?php wp_nonce_field( 'cbbe_edit_custom_field', 'cbbe_edit_custom_field_nonce' ); ?>

									<input type="hidden" name="edit_slug" value="<?php echo esc_attr( $slug ); ?>">

									<input type="text" name="edit_label" value="<?php echo esc_attr( $field_info['label'] ); ?>" style="width:120px;">

									<select name="edit_type" class="cf-edit-type">
										<?php foreach ( $cbbe_allowed_types as $type ) : ?>
											<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $field_info['type'], $type ); ?>>
												<?php echo esc_html( $type === 'bool' ? 'True/False' : ucfirst( $type ) ); ?>
											</option>
										<?php endforeach; ?>
									</select>

									<select name="edit_taxonomy" class="cf-edit-taxonomy" style="display:<?php echo ( 'taxonomy' === $field_info['type'] ) ? 'inline-block' : 'none'; ?>;">
										<?php foreach ( $available_taxonomies as $tax_slug => $tax_label ) : ?>
											<option value="<?php echo esc_attr( $tax_slug ); ?>" <?php selected( isset( $field_info['taxonomy'] ) ? $field_info['taxonomy'] : '', $tax_slug ); ?>>
												<?php echo esc_html( $tax_label ); ?>
											</option>
										<?php endforeach; ?>
									</select>

									<button type="button" class="button button-primary cf-apply-edit-btn" style="margin-right: 10px;">
										<?php esc_html_e( 'Apply', 'coding-bunny-bulk-edit' ); ?>
									</button>
									<button type="button" class="button cf-cancel-edit">
										<?php esc_html_e( 'Cancel', 'coding-bunny-bulk-edit' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}