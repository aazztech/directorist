<?php
/**
 * @author  wpWax
 * @since   6.7
 * @version 6.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="directorist-single-info directorist-single-info-time">

	<div class="directorist-single-info__label"><span class="directorist-single-info__label-icon"><?php directorist_icon( $icon );?></span class="directorist-single-info__label--text"><span><?php echo esc_html( $data['label'] ); ?></span></div>
	
	<div class="directorist-single-info__value"><?php echo date('h:i A', strtotime( esc_html( $value ) )); ?></div>
	
</div>