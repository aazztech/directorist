<?php
/**
 * @author  wpWax
 * @since   7.0.6
 * @version 7.0.6
 */

use \Directorist\Helper;
$columns = floor( 12 / $args['all_authors_columns'] );
?>

<div class="<?php echo ! empty( $args['sorting'] ) ? 'directorist-all-authors' : 'directorist-w-100 directorist-authors-section'; ?>" id="<?php echo ! empty( $args['sorting'] ) ? 'directorist-authors-wrapper' : 'directorist-all-authors'; ?>">
    <div class="directorist-container">
        <div class="directorist-authors">
            <?php if( $args['alphabets' ] && ! empty( $args['all_authors_sorting'] ) ) { ?>
            <div class="directorist-authors__nav">
                <ul>
                    <?php foreach( $args['alphabets'] as $alphabet ) { ?>
                    <li class=""><a href="#" class="directorist-alphabet <?php echo $alphabet; ?>" data-nonce="<?php echo wp_create_nonce( 'directorist_author_sorting' ); ?>" data-alphabet="<?php echo $alphabet; ?>"><?php echo $alphabet; ?></a></li>
                    <?php } ?>
                </ul>
            </div><!-- ends: .directorist-authors__nav -->
            <?php } ?>
            <div class="directorist-authors__cards">
                <div class="directorist-row">
                    <?php
                    $no_author_founds      = true;
                    foreach( $args['all_authors'] as $author ) {
                        $avatar_url           = get_avatar_url( $author->data->ID );
                        $pro_pic              = get_user_meta( $author->data->ID, 'pro_pic', true );
                        $u_pro_pic            = ! empty( $pro_pic ) ? wp_get_attachment_image_src( $pro_pic, 'thumbnail' ) : '';
                        $author_image_src     = ! empty( $u_pro_pic ) ? $u_pro_pic[0] : $avatar_url;
                        $description          = get_user_meta( $author->data->ID, 'description', true );
                        $atbdp_phone          = get_user_meta( $author->data->ID, 'atbdp_phone', true );
                        $user_email           = get_user_meta( $author->data->ID, 'user_email', true );
                        $address              = get_user_meta( $author->data->ID, 'address', true );
                        $display_name         = ucfirst( $author->data->display_name );
                        $atbdp_facebook       = get_user_meta( $author->data->ID, 'atbdp_facebook', true);
                        $atbdp_twitter        = get_user_meta( $author->data->ID, 'atbdp_twitter', true );
                        $atbdp_linkedin       = get_user_meta( $author->data->ID, 'atbdp_linkedin', true );
                        $atbdp_youtube        = get_user_meta( $author->data->ID, 'atbdp_youtube', true );
                        $firstCharacter       = $display_name[0];
                        $socials = array();
                        if( ! empty( $atbdp_facebook ) ) {
                            $socials[0] = array( 
                                'url' => $atbdp_facebook,
                                'id'  => 'facebook'
                            );
                        }
                        if( ! empty( $atbdp_twitter ) ) {
                            $socials[1] = array(
                                'url' => $atbdp_twitter,
                                'id'  => 'twitter'
                            );
                        }
                        if( ! empty( $atbdp_linkedin ) ) {
                            $socials[2] = array(
                                'url' => $atbdp_linkedin,
                                'id'  => 'linkedin'
                            );
                        }
                        if( ! empty( $atbdp_youtube ) ) {
                            $socials[3] = array(
                                'url' => $atbdp_youtube,
                                'id'  => 'youtube'
                            );
                        }
                        if(  empty( $_REQUEST['alphabet'] ) || ( ! empty( $_REQUEST['alphabet'] ) && $firstCharacter == $_REQUEST['alphabet'] ) ) {
                        $no_author_founds      = false;
                        
                    ?>
                    <div class="<?php Helper::directorist_column( $columns ); ?>">
                        <div class="directorist-authors__card">
                            <?php if( $author_image_src && ! empty( $args['all_authors_image'] ) ) { ?>
                            <div class="directorist-authors__card__img">
                                <img src="<?php echo $author_image_src; ?>" alt="<?php echo $display_name; ?>">
                            </div>
                            <?php } ?>
                            <div class="directorist-authors__card__details">
                                <?php if( $display_name && ! empty( $args['all_authors_name'] ) ) { ?>
                                <h2><?php echo $display_name; ?></h2>
                                <?php } ?>
                                <?php if( $author->roles[0] && ! empty( $args['all_authors_role'] ) ) { ?>
                                <h3><?php echo $author->roles[0]; ?></h3>
                                <?php } ?>
                                <?php if( ! empty( $args['all_authors_info'] ) ) { ?>
                                <ul class="directorist-authors__card__info-list">
                                    <?php if( ! empty( $atbdp_phone ) ) { ?>
                                    <li><i class="la la-phone"></i> <?php echo $atbdp_phone; ?></li>
                                    <?php } ?>
                                    <?php if( ! empty( $author->data->user_email ) ) { ?>
                                    <li><i class="la la-envelope"></i> <a href="mailto:<?php echo $author->data->user_email; ?>"><?php echo $author->data->user_email; ?></a></li>
                                    <?php } ?>
                                    <?php if( ! empty( $address ) ) { ?>
                                    <li><i class="la la-map-marker"></i> <?php echo esc_attr( $address ); ?></li>
                                    <?php } ?>
                                    <?php if( $author->data->user_url ) { ?>
                                    <li><i class="la la-globe"></i> <a href="<?php echo esc_url( $author->data->user_url ); ?>"><?php echo esc_url( $author->data->user_url ); ?></a></li>
                                    <?php } ?>
                                </ul>
                                <?php } ?>
                                <?php if( $description && ! empty( $args['all_authors_description'] ) ) { ?>
                                <p><?php echo wp_trim_words( $description, $all_authors_description_limit ); ?></p>
                                <?php } ?>
                                <?php if( ! empty( $all_authors_social_info ) && ! empty( $socials ) ) { ?>
                                <ul class="directorist-author-social directorist-author-social--light">
                                    <?php foreach( $socials as $social ) { ?>
                                    <li class="directorist-author-social-item"><a target="_blank" href="<?php echo $social['url']; ?>"><span class="la la-<?php echo $social['id']; ?>"></span></a></li>
                                    <?php } ?>
						        </ul>
                                <?php } ?>
                                <?php if( ! empty( $args['all_authors_button'] ) ) { ?>
                                <a href="<?php echo ATBDP_Permalink::get_user_profile_page_link( $author->data->ID );?>" class="directorist-btn directorist-btn-light directorist-btn-block"><?php echo ! empty( $args['all_authors_button_text'] ) ? $args['all_authors_button_text'] : ''; ?></a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php } } ?>
                    <?php if( ! empty( $no_author_founds ) ) { ?>
                        <p><?php _e( 'No author founds', 'directorist' ); ?></p>
                    <?php } ?>
                </div>
            </div><!-- ends: .directorist-authors__cards -->

        </div><!-- ends: .directorist-authors -->
    </div>
</div>