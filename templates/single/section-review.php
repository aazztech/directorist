<?php
/**
 * @author  wpWax
 * @since   6.7
 * @version 6.7
 */

use \Directorist\Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

if ( !Helper::is_review_enabled() ) {
	return;
}

$review_placeholder = $listing->current_review() ? esc_html__( 'Update your review.....', 'directorist' ) : esc_html__( 'Write your review.....', 'directorist' );
$review_content = $listing->current_review() ? $listing->current_review()->content : '';
?>

<div class="directorist-single-listing-review <?php echo esc_attr( $class );?>" <?php $listing->section_id( $id ); ?>>

	<div class="directorist-card directorist-card-review-block" id="directorist-review-block">

		<div class="directorist-card__header directorist-flex directorist-align-center directorist-justify-content-between">

			<h4 class="directorist-card__header--title">
				<span class="<?php atbdp_icon_type( true ); ?>-star"></span>
				<span id="directorist-review-counter"><?php echo esc_html( $listing->review_count() ); ?></span>
				<?php echo esc_html( $listing->review_count_text() );?>
			</h4>

			<?php if ( atbdp_logged_in_user() || $listing->guest_review_enabled() ): ?>
				<label for="review_content" class="directorist-btn directorist-btn-primary directorist-btn-xs directorist-btn-add-review"><?php esc_html_e( 'Add a review', 'directorist' ); ?></label>
			<?php endif; ?>

		</div>

		<div class="directorist-card__body">
			<input type="hidden" id="review_post_id" data-post-id="<?php echo esc_attr( $listing->id ); ?>">
			<div id="directorist-client-review-list"></div>
			<div id="clint_review"></div>
		</div>

	</div>

	<?php
	if ( atbdp_logged_in_user() || $listing->guest_review_enabled() ): ?>

		<?php if (get_current_user_id() != $listing->author_id || $listing->owner_review_enabled() ): ?>

			<div class="directorist-card directorist-card-rating-block">

				<div class="directorist-card__header">
					<h4 class="directorist-card__header--title">
						<span class="<?php atbdp_icon_type( true ); ?>-star" aria-hidden="true"></span>
						<?php echo $listing->current_review() ? esc_html__( 'Update Review', 'directorist' ) : esc_html__( 'Leave a Review', 'directorist' ); ?>
					</h4>
				</div>

				<div class="directorist-card__body directorist-review-area">
					<form action="#" id="directorist-review-form" method="post">

						<div class="directorist-rating-review-block">

							<?php if ($listing->current_review()): ?>

								<div class="directorist-review-current-rating">

									<p class="directorist-review-current-rating__label"><?php esc_html_e('Current Rating:', 'directorist'); ?></p>

									<div class="directorist-review-current-rating__stars">
										<ul>
											<?php
											$rating = $listing->current_review()->rating;
											for ($i=1; $i<=5; $i++){
												printf( '<li><span class="%s"></span></li>', $i <= $rating ? 'directorist-rate-active' : 'rate_disable' );
											}
											?>
										</ul>
									</div>

								</div>

							<?php endif; ?>

							<div class="directorist-rating-given-block">

								<p class="directorist-rating-given-block__label"><?php echo $listing->current_review() ? esc_html__('Update Rating:', 'directorist') : esc_html__('Your Rating:', 'directorist'); ?></p>

								<div class="directorist-rating-given-block__stars">
									<select class="directorist-stars" name="rating" id="directorist-review-rating">
										<option value="1"><?php esc_html_e( '1', 'directorist' ); ?></option>
										<option value="2"><?php esc_html_e( '2', 'directorist' ); ?></option>
										<option value="3"><?php esc_html_e( '3', 'directorist' ); ?></option>
										<option value="4"><?php esc_html_e( '4', 'directorist' ); ?></option>
										<option value="5" selected><?php esc_html_e( '5', 'directorist' ); ?></option>
									</select>
								</div>

							</div>

						</div>

						<div class="directorist-form-group directorist-form-group-review-text">
							<textarea name="content" id="review_content" class="directorist-form-element" cols="20" rows="5" placeholder="<?php echo esc_attr( $review_placeholder ); ?>"><?php echo esc_html( $review_content ); ?></textarea>
						</div>

						<?php if ( $listing->guest_review_enabled() && !atbdp_logged_in_user() ): ?>

							<div class="directorist-form-group directorist-form-group-guest-user">

								<label for="guest_user"><?php echo esc_html( $listing->guest_email_label() ); ?>:<span class="directorist-star-red">*</span></label>

								<input type="text" id="guest_user_email" name="guest_user_email" required class="directorist-form-element" placeholder="<?php echo esc_attr($listing->guest_email_placeholder()); ?>"/>

							</div>

						<?php endif; ?>
						
						<div class="directorist-review-form-action">
							
							<?php if ($listing->current_review()): ?>

								<button class="directorist-btn directorist-btn-primary directorist-btn-sm" type="submit" id="directorist-review-form-submit"><?php esc_html_e( 'Update', 'directorist' ); ?></button>

								<button class="directorist-btn directorist-btn-danger directorist-btn-sm" type="button" id="directorist-review-remove" data-review_id="<?php echo $listing->current_review()->id; ?>"><?php esc_html_e( 'Remove', 'directorist' ); ?></button>

							<?php else: ?>

								<button class="directorist-btn directorist-btn-primary directorist-btn-sm" type="submit" id="directorist-review-form-submit"><?php esc_html_e( 'Submit Review', 'directorist' ); ?></button>

							<?php endif; ?>

							<?php wp_nonce_field('atbdp_review_action_form', 'atbdp_review_nonce_form'); ?>

							<input type="hidden" name="post_id" value="<?php echo esc_attr( $listing->id ); ?>">

							<input type="hidden" name="name" value="<?php echo esc_attr( $listing->reviewer_name() ); ?>" id="reviewer_name">

							<input type="hidden" name="name" id="reviewer_img" value='<?php echo esc_attr( $listing->get_reviewer_img() ); ?>'>

							<input type="hidden" name="approve_immediately" id="approve_immediately" value="<?php echo $listing->review_approve_immediately() ? 'yes' : 'no';?>">

							<input type="hidden" name="review_duplicate" id="review_duplicate" value="<?php echo $listing->review_is_duplicate() ? 'yes' : '';?>">
							
						</div>

					</form>
				</div>

			</div>

		<?php endif; ?>

	<?php else: ?>

		<div class="directorist-card directorist-card-review-alert">

			<div class="directorist-card__body">

				<div class="directorist-alert directorist-alert-info">

					<div class="directorist-alert__content">

						<span class="<?php atbdp_icon_type( true ); ?>-info-circle" aria-hidden="true"></span>
						
						<p><?php printf(__('You need to <a href="%s">%s</a> or <a href="%s">%s</a> to submit a review', 'directorist'), ATBDP_Permalink::get_login_page_link(), esc_html__( 'Login', 'directorist' ), ATBDP_Permalink::get_registration_page_link(), esc_html__(' Sign Up', 'directorist' ) );?></p>

					</div>

				</div>

			</div>

		</div>

	<?php endif; ?>

</div>