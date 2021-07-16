<?php
/**
 * @author  wpWax
 * @since   6.7
 * @version 6.7
 */

use \Directorist\Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

$id         = $listing->id;
$author_id  = $listing->author_id;
$u_pro_pic  = get_user_meta($author_id, 'pro_pic', true);
$u_pro_pic  = !empty($u_pro_pic) ? wp_get_attachment_image_src($u_pro_pic, 'thumbnail') : '';
$author_img = !empty($u_pro_pic) ? $u_pro_pic[0] : '';
$avatar_img = get_avatar($author_id, 32);
?>

<div class="directorist-card directorist-card-author-info <?php echo esc_attr( $class );?>" <?php $listing->section_id( $id ); ?>>

	<div class="directorist-card__header">

		<h4 class="directorist-card__header--title"><?php directorist_icon( $icon );?><?php echo esc_html( $label );?></h4>
	
	</div>

	<div class="directorist-card__body">

		<div class="directorist-single-author-info">

			<div class="directorist-single-author-avatar">

				<div class="directorist-single-author-avatar-inner">
					<?php if ( $author_img ): ?>
						<img src="<?php echo esc_url( $author_img ); ?>" alt="<?php esc_attr_e( 'Avatar', 'directorist' );?>">
					<?php else: ?>
						<?php echo $avatar_img; ?>
					<?php endif; ?>
				</div>

				<div class="directorist-single-author-name">
					<h4><?php echo esc_html( $listing->author_info( 'name' ) ); ?></h4>
					<span class="directorist-single-author-membership"><?php printf( esc_html__( 'Member since %s ago', 'directorist' ), $listing->author_info( 'member_since' ) ); ?></span>
				</div>

			</div>

			<div class="directorist-single-author-contact-info">

				<ul>
					<?php if ( $address = $listing->author_info( 'address' ) ): ?>
						<li>
							<span class="<?php atbdp_icon_type( true );?>-map-marker"></span>
							<span class="directorist-single-author-contact-info-text"><?php echo esc_html( $address ); ?></span>
						</li>
					<?php endif; ?>


					<?php if ( $phone = $listing->author_info( 'phone' ) ): ?>
						<li>
							<span class="<?php atbdp_icon_type(true);?>-phone"></span>
							<span class="directorist-single-author-contact-info-text">
								<a href="tel:<?php Helper::formatted_tel( $phone ); ?>"><?php echo esc_html( $phone ); ?></a>
							</span>
						</li>
					<?php endif; ?>

					<?php if ( $listing->author_display_email() ): ?>
						<li>
							<span class="<?php atbdp_icon_type(true);?>-envelope"></span>
							<span class="directorist-single-author-contact-info-text"><?php echo esc_html( $listing->author_info( 'email' ) ); ?></span>
						</li>
					<?php endif; ?>

					<?php if ( $website = $listing->author_info( 'website' ) ): ?>
						<li>
							<span class="<?php atbdp_icon_type(true);?>-globe"></span>
							<a href="<?php echo esc_url( $website ); ?>" class="directorist-single-author-contact-info-text"><?php echo esc_url( $website ); ?></a>
						</li>
					<?php endif; ?>
				</ul>

			</div>

			<?php if ( $listing->author_has_socials() ): ?>
				
				<ul class="directorist-author-social">
					<?php
					if ( $facebook = $listing->author_info( 'facebook' ) ) {
						printf('<li class="directorist-author-social-item"><a target="_blank" href="%s"><span class="'.atbdp_icon_type().'-facebook"></span></a></li>', $facebook);
					}
					if ( $twitter = $listing->author_info( 'twitter' ) ) {
						printf('<li class="directorist-author-social-item"><a target="_blank" href="%s"><span class="'.atbdp_icon_type().'-twitter"></span></a></li>', $twitter);
					}
					if ( $linkedin = $listing->author_info( 'linkedin' ) ) {
						printf('<li class="directorist-author-social-item"><a target="_blank" href="%s"><span class="'.atbdp_icon_type().'-linkedin"></span></a></li>', $linkedin);
					}
					if ( $youtube = $listing->author_info( 'youtube' ) ) {
						printf('<li class="directorist-author-social-item"><a target="_blank" href="%s"><span class="'.atbdp_icon_type().'-youtube"></span></a></li>', $youtube);
					}
					?>
				</ul>

			<?php endif; ?>

			<a class="directorist-btn directorist-btn-primary directorist-btn-sm diretorist-view-profile-btn" href="<?php echo ATBDP_Permalink::get_user_profile_page_link( $author_id ); ?>"><?php esc_html_e( 'View Profile', 'directorist' ); ?></a>
			
		</div>
	</div>

</div>