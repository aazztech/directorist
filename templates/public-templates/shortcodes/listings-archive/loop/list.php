<div class="atbd_single_listing atbd_listing_list">
  <article class="atbd_single_listing_wrapper <?php echo ($featured) ? 'directorist-featured-listings' : ''; ?>">
      <figure class="atbd_listing_thumbnail_area" style="<?php echo (empty(get_directorist_option('display_preview_image')) || 'no' == $display_image) ? 'display:none' : '' ?>">
          <?php
          $disable_single_listing = get_directorist_option('disable_single_listing');
          if (empty($disable_single_listing)) {
          ?>
              <a href="<?php echo esc_url(get_post_permalink(get_the_ID())); ?>" <?php echo $thumbnail_link_attr; ?>>
              <?php
          }
          the_thumbnail_card();
          if (empty($disable_single_listing)) {
              echo '</a>';
          }
          //Start lower badge
          $l_badge_html = '<span class="atbd_lower_badge">';
          if ($featured && !empty($display_feature_badge_cart)) {
              $l_badge_html .= '<span class="atbd_badge atbd_badge_featured">' . $feature_badge_text . '</span>';
          }
          $popular_listing_id = atbdp_popular_listings(get_the_ID());
          $badge = '<span class="atbd_badge atbd_badge_popular">' . $popular_badge_text . '</span>';
          if ($popular_listing_id === get_the_ID() && !empty($display_popular_badge_cart)) {
              $l_badge_html .= $badge;
          }
          //print the new badge
          $l_badge_html .= new_badge();
          $l_badge_html .= '</span>';

          /**
            * @since 5.0
            */
          echo apply_filters('atbdp_list_lower_badges', $l_badge_html); ?>
      </figure>
      <div class="atbd_listing_info">
          <div class="atbd_content_upper">
              <?php do_action('atbdp_list_view_before_title'); ?>
              <?php if (!empty($display_title)) { ?>
                  <h4 class="atbd_listing_title">
                      <?php
                      if (empty($disable_single_listing)) {
                          echo '<a href="' . esc_url(get_post_permalink(get_the_ID())) . '"' . $title_link_attr . '>' . esc_html(stripslashes(get_the_title())) . '</a>';
                      } else {
                          echo esc_html(stripslashes(get_the_title()));
                      } ?>
                  </h4>
              <?php
              }
              /**
                * @since 6.2.3
                */
              do_action('atbdp_list_view_after_title');
              if (!empty($tagline) && !empty($enable_tagline) && !empty($display_tagline_field)) { ?>
                  <p class="atbd_listing_tagline"><?php echo esc_html(stripslashes($tagline)); ?></p>
              <?php
              }
              /**
                * Fires after the title and sub title of the listing is rendered
                *
                *
                * @since 1.0.0
                */
              do_action('atbdp_after_listing_tagline');
              ?>
              <?php
              $meta_html = '';
              if (!empty($display_review) || (!empty($display_price) && (!empty($price) || !empty($price_range)))) {
                  $meta_html .= '<div class="atbd_listing_meta">';
                  if (!empty($display_review)) {
                      $average = ATBDP()->review->get_average(get_the_ID());
                      $meta_html .= '<span class="atbd_meta atbd_listing_rating">' . $average . '<i class="' . atbdp_icon_type() . '-star"></i></span>';
                  }
                  $listing_pricing = !empty($listing_pricing) ? $listing_pricing : '';
                  if (!empty($display_price) && !empty($display_pricing_field)) {
                      if (!empty($price_range) && ('range' === $listing_pricing)) {
                          $output = atbdp_display_price_range($price_range);
                          $meta_html .= $output;
                      } else {
                          $meta_html .= atbdp_display_price($price, $is_disable_price, $currency = null, $symbol = null, $c_position = null, $echo = false);
                      }
                  }
                  /**
                    * Fires after the price of the listing is rendered
                    *
                    *
                    * @since 3.1.0
                    */
                  do_action('atbdp_after_listing_price');
                  $plan_hours = true;
                  if (is_fee_manager_active()) {
                      $plan_hours = is_plan_allowed_business_hours(get_post_meta(get_the_ID(), '_fm_plans', true));
                  }
                  if (is_business_hour_active() && $plan_hours && empty($disable_bz_hour_listing)) {
                      //lets check is it 24/7
                      if (!empty($enable247hour)) {
                          $open = get_directorist_option('open_badge_text');
                          $meta_html .= '<span class="atbd_badge atbd_badge_open">' . $open . '</span>';
                      } else {
                          $bh_statement = BD_Business_Hour()->show_business_open_close($business_hours, false); // show the business hour in an unordered list
                          $meta_html .= $bh_statement;
                      }
                  }
                  $meta_html .= '</div>'; // End atbd listing meta
              }
              echo apply_filters('atbdp_listings_list_review_price', $meta_html);
              if (!empty($display_contact_info) || !empty($display_publish_date) || !empty($display_email) || !empty($display_web_link)) {
                atbdp_get_shortcode_template( 'global/data-list', compact( 'display_contact_info', 'address', 'address_location', 'display_address_field', 'locs', 'phone_number', 'display_phone_field', 'display_publish_date', 'email', 'display_email', 'web', 'display_web_link', 'web', 'use_nofollow' ) );
              }
              //show category and location info
              ?>
              <?php if (!empty($excerpt) && !empty($enable_excerpt) && !empty($display_excerpt_field)) {
                  $excerpt_limit = get_directorist_option('excerpt_limit', 20);
                  $excerpt_limit = get_directorist_option('excerpt_limit', 20);
                  $display_readmore = get_directorist_option('display_readmore', 0);
                  $readmore_text = get_directorist_option('readmore_text', __('Read More', 'directorist'));
              ?>
                  <p class="atbd_excerpt_content"><?php echo esc_html(stripslashes(wp_trim_words($excerpt, $excerpt_limit)));
                    /**
                    * @since 5.0.9
                    */
                    do_action('atbdp_listings_after_exerpt');
                    if (!empty($display_readmore)) {
                    ?><a href="<?php the_permalink(); ?>"><?php printf(__(' %s', 'directorist'), $readmore_text); ?></a></p>
                <?php }
                }
                if (!empty($display_mark_as_fav)) {
                    $mark_as_fav_for_list_view = apply_filters('atbdp_mark_as_fav_for_list_view', atbdp_listings_mark_as_favourite(get_the_ID()));
                    echo $mark_as_fav_for_list_view;
                }
          ?>
          </div><!-- end ./atbd_content_upper -->
          
        <?php ob_start();
        if ( ! empty( $display_category ) || ! empty( $display_view_count ) || ! empty( $display_author_image ) ) { ?>
        <div class="atbd_listing_bottom_content">
            <?php if ( ! empty( $display_category ) ) { if ( ! empty( $cats ) ) { $totalTerm = count( $cats ); ?>
            <div class="atbd_content_left">
                <div class="atbd_listing_category">
                    <a href="<?php echo ATBDP_Permalink::atbdp_get_category_page( $cats[0] ); ?>">
                        <span class="<?php echo atbdp_icon_type(); ?>-tags"></span>
                        <?php echo $cats[0]->name; ?>
                    </a>
                    <?php if ( $totalTerm > 1 ) { $totalTerm = $totalTerm - 1; ?>
                    <div class="atbd_cat_popup">
                        <span><?php echo $totalTerm; ?></span>
                        <div class="atbd_cat_popup_wrapper">
                            <span>
                            <?php foreach ( array_slice( $cats, 1 ) as $cat ) {
                            $link  = ATBDP_Permalink::atbdp_get_category_page( $cat );
                            $space = str_repeat( ' ', 1 ); echo $space; ?>
                                <span>
                                    <a href='<?php echo $link; ?>'><?php echo $cat->name; ?><span>,</span></a>
                                </span>
                            <?php } ?>
                            </span>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } else { ?>
            <div class="atbd_content_left">
                <div class="atbd_listing_category">
                    <a href="./">
                        <span class="<?php atbdp_icon_type() ?>-tags"></span>
                        <?php _e( 'Uncategorized', 'directorist' ); ?>
                    </a>
                </div>
            </div>
            <?php } }
            if ( ! empty( $display_view_count ) || ! empty( $display_author_image ) ) {
            $catViewCountAuthor .= '<ul class="atbd_content_right">'; ?>
            <ul class="atbd_content_right">
                <?php if ( ! empty( $display_view_count ) ) { ?>
                <li class="atbd_count">
                    <span class="<?php atbdp_icon_type(); ?>-eye"></span>
                    <?php echo ! empty( $post_view ) ? $post_view : 0; ?>
                </li>
                <?php }
                if ( ! empty( $display_author_image ) ) {
                $author                 = get_userdata( $author_id );
                $author_first_last_name = $author->first_name . ' ' . $author->last_name;
                $class                  = ! empty( $author->first_name && $author->last_name ) ? 'atbd_tooltip' : '';
                ?>
                <li class="atbd_author">
                    <a href="<?php echo ATBDP_Permalink::get_user_profile_page_link( $author_id ); ?>"
                        class="<?php echo $class; ?>"
                        aria-label="<?php $author_first_last_name; ?>">
                        <?php if ( empty( $u_pro_pic ) ) {echo $avatar_img;}
                        if ( ! empty( $u_pro_pic ) ) { ?>
                        <img src="<?php echo esc_url( $u_pro_pic[0] ); ?>" alt="Author Image">
                        <?php } ?>
                    </a>
                </li>
                <?php } ?>
            </ul>
            <?php } ?>
        </div>
        <?php } echo apply_filters( 'atbdp_listings_list_cat_view_count_author', ob_get_clean() ); ?>
      </div>
  </article>
</div>