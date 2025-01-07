<?php
/**
 * TinyMCE Modal Field view
 *
 * @package ConvertKit
 * @author ConvertKit
 */

switch ( $field['type'] ) {

	/**
	 * Text
	 */
	case 'text':
		?>
		<input type="text" 
				id="tinymce_modal_<?php echo esc_attr( $field_name ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
				value="<?php echo esc_attr( isset( $shortcode['attributes'][ $field_name ]['default'] ) ? $shortcode['attributes'][ $field_name ]['default'] : '' ); ?>" 
				data-shortcode="<?php echo esc_attr( $field_name ); ?>"
				placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"
				class="widefat" />
		<?php
		break;

	/**
	 * Number
	 */
	case 'number':
		?>
		<input type="number" 
				id="tinymce_modal_<?php echo esc_attr( $field_name ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>" 
				value="<?php echo esc_attr( isset( $shortcode['attributes'][ $field_name ]['default'] ) ? $shortcode['attributes'][ $field_name ]['default'] : '' ); ?>" 
				data-shortcode="<?php echo esc_attr( $field_name ); ?>"
				min="<?php echo esc_attr( $field['min'] ); ?>" 
				max="<?php echo esc_attr( $field['max'] ); ?>" 
				step="<?php echo esc_attr( $field['step'] ); ?>"
				class="widefat" />
		<?php
		break;

	/**
	 * Select
	 */
	case 'resource':
	case 'select':
		?>
		<select name="<?php echo esc_attr( $field_name ); ?>"
				id="tinymce_modal_<?php echo esc_attr( $field_name ); ?>"
				data-shortcode="<?php echo esc_attr( $field_name ); ?>"
				size="1"
				class="widefat">
			<?php
			$field['default_value'] = ( isset( $shortcode['attributes'][ $field_name ]['default'] ) ? $shortcode['attributes'][ $field_name ]['default'] : '' );
			foreach ( $field['values'] as $value => $label ) {
				?>
				<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $field['default_value'], $value ); ?>>
					<?php echo esc_attr( $label ); ?>
				</option>
				<?php
			}
			?>
		</select>
		<?php
		// Display a refresh resource button if the field type is resource.
		if ( $field['type'] === 'resource' ) {
			$button_title = sprintf(
				/* translators: Resource Type (Forms, Products, Tags etc). */
				__( 'Refresh %s from Kit account', 'convertkit' ),
				$field['resource']
			);
			?>
			<button class="wp-convertkit-refresh-resources button button-secondary hide-if-no-js"
					title="<?php echo esc_attr( $button_title ); ?>"
					data-resource="<?php echo esc_attr( $field['resource'] ); ?>"
					data-field="#tinymce_modal_<?php echo esc_attr( $field_name ); ?>">
				<span class="dashicons dashicons-update"></span>
			</button>
			<?php
		}
		break;

	/**
	 * Toggle
	 */
	case 'toggle':
		?>
		<select name="<?php echo esc_attr( $field_name ); ?>"
				id="tinymce_modal_<?php echo esc_attr( $field_name ); ?>"
				data-shortcode="<?php echo esc_attr( $field_name ); ?>"
				size="1"
				class="widefat">
			<?php
			$field['default_value'] = ( isset( $shortcode['attributes'][ $field_name ]['default'] ) ? $shortcode['attributes'][ $field_name ]['default'] : '' );
			?>
			<option value="0"<?php selected( $field['default_value'], 0 ); ?>><?php esc_html_e( 'No', 'convertkit' ); ?></option>
			<option value="1"<?php selected( $field['default_value'], 1 ); ?>><?php esc_html_e( 'Yes', 'convertkit' ); ?></option>
		</select>
		<?php
		break;

	/**
	 * Color Picker
	 */
	case 'color':
		?>
		<input type="color" 
				id="tinymce_modal_<?php echo esc_attr( $field_name ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
				value="<?php echo esc_attr( isset( $shortcode['attributes'][ $field_name ]['default'] ) ? $shortcode['attributes'][ $field_name ]['default'] : '' ); ?>" 
				data-value="<?php echo esc_attr( isset( $shortcode['attributes'][ $field_name ]['default'] ) ? $shortcode['attributes'][ $field_name ]['default'] : '' ); ?>" 
				data-shortcode="<?php echo esc_attr( $field_name ); ?>"
				placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
		<?php
		break;
}

if ( isset( $field['description'] ) ) {
	?>
	<p class="description">
		<?php echo esc_attr( $field['description'] ); ?>
	</p>
	<?php
}
