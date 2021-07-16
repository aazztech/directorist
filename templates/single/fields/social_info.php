<?php
/**
 * @author  wpWax
 * @since   6.7
 * @version 6.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$socials = $listing->get_socials();

if ( empty( $socials ) ) {
	return;
}
?>

<div class="directorist-single-info directorist-single-info-socials">

	<?php if ( $data['label'] ): ?>
		<div class="directorist-single-info__label">
			<span class="directorist-single-info__label-icon"><?php directorist_icon( $icon );?></span>
			<span class="directorist-single-info__label--text"><?php echo esc_html( $data['label'] ); ?></span>
		</div>
	<?php endif; ?>

	<div class="directorist-social-links">
		<?php  foreach ( $socials as $social ): ?>
			<a target='_blank' href="<?php echo esc_url( $social['url'] ); ?>" class="<?php echo esc_attr( $social['id'] ); ?>">
				<span class="<?php atbdp_icon_type( true );?>-<?php echo esc_attr( $social['id'] ); ?>"></span>
			</a>
		<?php endforeach; ?>
	</div>

</div>