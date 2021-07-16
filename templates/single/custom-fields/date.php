<?php
/**
 * @author  wpWax
 * @since   6.7
 * @version 6.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$date_format = get_option( 'date_format' );
?>

<div class="directorist-single-info directorist-single-info-date">
	
	<div class="directorist-single-info__label">
		<span class="directorist-single-info__label-icon"><?php directorist_icon( $icon );?></span>
		<span class="directorist-single-info__label--text"><?php echo esc_html( $data['label'] ); ?></span>
	</div>
	
	<div class="directorist-single-info__value"><?php echo date( $date_format, strtotime( esc_html( $value ) ) ); ?></div>
	
</div>