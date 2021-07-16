<?php
/**
 * @author  wpWax
 * @since   6.7
 * @version 6.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$address_data = $listing->get_address( $data );
$address = ( is_string( $address_data ) ) ? wp_kses_post( $address_data ) : '';
?>

<div class="directorist-single-info directorist-single-info-address">

	<div class="directorist-single-info__label">
		<span class="directorist-single-info__label-icon"><?php directorist_icon( $icon );?></span>
		<span class="directorist-single-info__label--text"><?php echo ( isset( $data['label'] ) ) ? esc_html( $data['label'] ) : ''; ?></span>
	</div>
	
	<div class="directorist-single-info__value"><?php echo $address; ?></div>

</div>