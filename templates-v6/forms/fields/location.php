<?php
/**
 * @author  AazzTech
 * @since   6.7
 * @version  7.0.3.2
 */
?>

<div class="form-group directorist-location-field">
	<?php $form->add_listing_label_template( $data ); ?>

	<select name="<?php echo esc_attr( $data['field_key'] ); ?>" class="form-control" id="at_biz_dir-location" <?php echo $data['type'] == 'multiple' ? 'multiple="multiple"' : '';  echo !empty( $data['max'] ) ? 'max="'. $data['max'] .'"' : ''; echo !empty( $data['create_new_loc'] ) ? 'data-allow_new="'. $data['create_new_loc'] .'"' : ''; ?>>

		<?php
		if ($data['type'] != 'multiple') {
			printf('<option>%s</option>', __( 'Select Location', 'directorist' ) );
		}

		echo $form->add_listing_location_fields();
		?>
	</select>

	<?php $form->add_listing_description_template( $data ); ?>
</div>
