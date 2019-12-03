<?php
$p_id = get_query_var('atbdp_listing_id', 0);
if (!empty($p_id)) {
    $p_id = absint($p_id);
    $listing = get_post($p_id);
    // kick the user out if he tries to edit the listing of other user
    if ($listing->post_author != get_current_user_id() && !current_user_can('edit_others_at_biz_dirs')) {
        echo '<p class="error">' . __('You do not have permission to edit this listing', ATBDP_TEXTDOMAIN) . '</p>';
        return;
    }
    $listing_info['never_expire'] = get_post_meta($p_id, '_never_expire', true);
    $listing_info['featured'] = get_post_meta($p_id, '_featured', true);
    $listing_info['listing_type'] = get_post_meta($p_id, '_listing_type', true);
    $listing_info['price'] = get_post_meta($p_id, '_price', true);
    $listing_info['videourl'] = get_post_meta($p_id, '_videourl', true);
    $listing_info['price_range'] = get_post_meta($p_id, '_price_range', true);
    $listing_info['atbd_listing_pricing'] = get_post_meta($p_id, '_atbd_listing_pricing', true);
    $listing_info['listing_status'] = get_post_meta($p_id, '_listing_status', true);
    $listing_info['tagline'] = get_post_meta($p_id, '_tagline', true);
    $listing_info['excerpt'] = get_post_meta($p_id, '_excerpt', true);
    $listing_info['address'] = get_post_meta($p_id, '_address', true);
    $listing_info['phone'] = get_post_meta($p_id, '_phone', true);
    $listing_info['email'] = get_post_meta($p_id, '_email', true);
    $listing_info['website'] = get_post_meta($p_id, '_website', true);
    $listing_info['zip'] = get_post_meta($p_id, '_zip', true);
    $listing_info['social'] = get_post_meta($p_id, '_social', true);
    $listing_info['faqs'] = get_post_meta($p_id, '_faqs', true);
    $listing_info['manual_lat'] = get_post_meta($p_id, '_manual_lat', true);
    $listing_info['manual_lng'] = get_post_meta($p_id, '_manual_lng', true);
    $listing_info['hide_map'] = get_post_meta($p_id, '_hide_map', true);
    $listing_info['bdbh'] = get_post_meta($p_id, '_bdbh', true);
    $listing_info['enable247hour'] = get_post_meta($p_id, '_enable247hour', true);
    $listing_info['disable_bz_hour_listing'] = get_post_meta($p_id, '_disable_bz_hour_listing', true);
    $listing_info['listing_img'] = get_post_meta($p_id, '_listing_img', true);
    $listing_info['listing_prv_img'] = get_post_meta($p_id, '_listing_prv_img', true);
    $listing_info['hide_contact_info'] = get_post_meta($p_id, '_hide_contact_info', true);
    $listing_info['hide_contact_owner'] = get_post_meta($p_id, '_hide_contact_owner', true);
    $listing_info['expiry_date'] = get_post_meta($p_id, '_expiry_date', true);
    $listing_info['t_c_check'] = get_post_meta($p_id, '_t_c_check', true);

    extract($listing_info);
    //for editing page
    $p_tags = wp_get_post_terms($p_id, ATBDP_TAGS);
    $p_locations = wp_get_post_terms($p_id, ATBDP_LOCATION);
    $p_cats = wp_get_post_terms($p_id, ATBDP_CATEGORY);
}
// prevent the error if it is not edit listing page when listing info var is not defined.
if (empty($listing_info)) {
    $listing_info = array();
}

$t = get_the_title();
$t = !empty($t) ? esc_html($t) : __('No Title ', ATBDP_TEXTDOMAIN);
$tg = !empty($tagline) ? esc_html($tagline) : '';
$ad = !empty($address) ? esc_html($address) : '';
$image = (!empty($listing_img[0])) ? "<img src='" . esc_url(wp_get_attachment_image_url($listing_img[0], 'thumbnail')) . "'>" : '';
/*build the markup for google map info window*/
$info_content = "<div class='map_info_window'> <h3> {$t} </h3>";
$info_content .= "<p> {$tg} </p>";
$info_content .= $image; // add the image if available
$info_content .= "<p> {$ad}</p></div>";
// grab social information
$social_info = !empty($social) ? (array)$social : array();
$listing_img = !empty($listing_img) ? (array)$listing_img : array();

// get the category and location lists/array
$categories = get_terms(ATBDP_CATEGORY, array('hide_empty' => 0));
$locations = get_terms(ATBDP_LOCATION, array('hide_empty' => 0));
$listing_tags = get_terms(ATBDP_TAGS, array('hide_empty' => 0));

// get the map zoom level from the user settings
$default_latitude = get_directorist_option('default_latitude', '40.7127753');
$default_longitude = get_directorist_option('default_longitude', '-74.0059728');
$map_zoom_level = get_directorist_option('map_zoom_level', 4);
$disable_price = get_directorist_option('disable_list_price');
$enable_video_url = get_directorist_option('atbd_video_url', 1);
$disable_contact_info = get_directorist_option('disable_contact_info');
$disable_contact_owner = get_directorist_option('disable_contact_owner',1);
$display_title_for = get_directorist_option('display_title_for',0);
$display_desc_for = get_directorist_option('display_desc_for',0);
$display_cat_for = get_directorist_option('display_cat_for','users');
$display_loc_for = get_directorist_option('display_loc_for',0);
$multiple_for_user = get_directorist_option('multiple_for_user',1);
$display_tag_for = get_directorist_option('display_tag_for',0);
$display_tagline_field = get_directorist_option('display_tagline_field', 0);
$display_tagline_for = get_directorist_option('display_tagline_for', 0);
// get the custom terms and conditions
$listing_terms_condition_text = get_directorist_option('listing_terms_condition_text');
$display_pricing_field = get_directorist_option('display_pricing_field', 1);
$display_price_for = get_directorist_option('display_price_for', 'admin_users');
$display_excerpt_field = get_directorist_option('display_excerpt_field', 0);
$display_short_desc_for = get_directorist_option('display_short_desc_for', 0);
$display_address_field = get_directorist_option('display_address_field', 1);
$display_address_for = get_directorist_option('display_address_for', 0);
$display_phone_field = get_directorist_option('display_phone_field', 1);
$display_phone_for = get_directorist_option('display_phone_for', 0);
$display_email_field = get_directorist_option('display_email_field', 1);
$display_email_for = get_directorist_option('display_email_for', 0);
$display_website_field = get_directorist_option('display_website_field', 1);
$display_website_for = get_directorist_option('display_website_for', 0);
$display_zip_field = get_directorist_option('display_zip_field', 1);
$display_zip_for = get_directorist_option('display_zip_for', 0);
$display_social_info_field = get_directorist_option('display_social_info_field', 1);
$display_social_info_for = get_directorist_option('display_social_info_for', 0);
$display_map_field = get_directorist_option('display_map_field', 1);
$display_map_for = get_directorist_option('display_map_for', 0);
$display_prv_field = get_directorist_option('display_prv_field', 1);
$display_gellery_field = get_directorist_option('display_gellery_field', 1);
$display_video_field = get_directorist_option('display_video_field', 1);
$display_prv_img_for    = get_directorist_option('display_prv_img_for', 0);
$display_glr_img_for    = get_directorist_option('display_glr_img_for', 0);
$display_video_for = get_directorist_option('display_video_for', 0);
$select_listing_map = get_directorist_option('select_listing_map', 'google');
$container_fluid             = is_directoria_active() ? 'container' : 'container-fluid';
?>
<div id="directorist" class="directorist atbd_wrapper atbd_add_listing_wrapper">
    <div class="<?php echo apply_filters('atbdp_add_listing_container_fluid',$container_fluid) ?>">
        <form action="<?= esc_url($_SERVER['REQUEST_URI']); ?>" method="post" id="post">
            <fieldset>
            <?php
            do_action('atbdb_before_add_listing_from_frontend');//for dev purpose
            $fm_plan = !empty(get_post_meta($p_id, '_fm_plans', true))?get_post_meta($p_id, '_fm_plans', true):'';
            ?>
            <div class="atbdp-form-fields">
                <div class="atbd_add_listing_title">
                    <h3><?= !empty($p_id) ? __('Update Listing', ATBDP_TEXTDOMAIN) : __('Add Listing', ATBDP_TEXTDOMAIN); ?></h3>
                </div>
                <?php
                /*
                 * if fires after
                 * @since 4.0.4
                 */
                do_action('atbdp_listing_form_after_add_listing_title',$listing_info)
                ?>
                <!--add nonce field security -->
                <?php ATBDP()->listing->add_listing->show_nonce_field(); ?>
                <input type="hidden" name="add_listing_form" value="1">
                <input type="hidden" name="listing_id" value="<?= !empty($p_id) ? esc_attr($p_id) : ''; ?>">

                <?php
                //to show validation notification @todo;letter need to validate with ajax action and identify the required field with color
                $all_validation = ATBDP()->listing->add_listing->add_listing_to_db();
                echo $all_validation;
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <?php
                        /**
                         * It fires before the listing title
                         * @param string $type Page type.
                         * @since 1.1.1
                         **/
                        do_action('atbdp_edit_before_title_fields', 'add_listing_page_frontend');
                        ?>
                        <div class="atbd_content_module atbd_general_information_module">
                            <div class="atbd_content_module__tittle_area">
                                <div class="atbd_area_title">
                                    <h4><?php esc_html_e('General information', ATBDP_TEXTDOMAIN) ?></h4>
                                </div>
                            </div>
                            <div class="atbdb_content_module_contents">
                                <?php if(empty($display_title_for)) {?>
                                <div class="form-group" id="atbdp_listing_title">
                                    <label for="listing_title"><?php
                                        $title = get_directorist_option('title_label', __('Title', ATBDP_TEXTDOMAIN));
                                        esc_html_e($title.':', ATBDP_TEXTDOMAIN);
                                        if(get_directorist_option('require_title',1)){echo '<span class="atbdp_make_str_red"> *</span>';} ?></label>
                                    <input type="text" name="listing_title"
                                           value="<?= !empty($listing->post_title) ? esc_attr($listing->post_title) : ''; ?>"
                                           class="form-control directory_field"
                                           placeholder="<?= __('Enter a title', ATBDP_TEXTDOMAIN); ?>"/>
                                </div>
                                <?php } ?>
                                <?php if(empty($display_desc_for)) { ?>
                                <div class="form-group" id="atbdp_listing_content">
                                    <label for="listing_content"><?php
                                        $long_details = get_directorist_option('long_details_label', __('Long Description', ATBDP_TEXTDOMAIN));
                                        esc_html_e($long_details.':', ATBDP_TEXTDOMAIN);if(get_directorist_option('require_long_details')){echo '<span class="atbdp_make_str_red"> *</span>';}?></label>
                                    <?php wp_editor(
                                        !empty($listing->post_content) ? wp_kses($listing->post_content, wp_kses_allowed_html('post')) : '',
                                        'listing_content',
                                        array(
                                            'media_buttons' => false,
                                            'quicktags' => true,
                                            'editor_height' => 200
                                        )); ?>
                                </div>
                                <?php } ?>
                                <?php if (!empty($display_tagline_field) && empty($display_tagline_for)){ ?>
                                    <div class="form-group" id="atbdp_excerpt">
                                        <label for="atbdp_excerpt"><?php
                                            $tagline_label = get_directorist_option('tagline_label', __('Tagline', ATBDP_TEXTDOMAIN));
                                            esc_html_e($tagline_label.':', ATBDP_TEXTDOMAIN);
                                           ?></label>
                                        <input type="text" name="tagline"
                                               id="has_tagline"
                                               value="<?= !empty($tagline) ? esc_attr($tagline) : ''; ?>"
                                               class="form-control directory_field"
                                               placeholder="<?= __('Your Listing\'s motto or tag-line', ATBDP_TEXTDOMAIN); ?>"/>
                                    </div>
                                <?php }?>
                                <?php
                                //data for average price range
                                $plan_average_price = true;
                                if (is_fee_manager_active()){
                                    $plan_average_price = is_plan_allowed_average_price_range($fm_plan);
                                }
                                $plan_price = true;
                                if (is_fee_manager_active()){
                                    $plan_price = is_plan_allowed_price($fm_plan);
                                }
                                $price_range = !empty($price_range) ? $price_range : '';
                                $atbd_listing_pricing = !empty($atbd_listing_pricing) ? $atbd_listing_pricing : '';

                                if (empty($display_price_for) && !empty($display_pricing_field) && ($plan_average_price || $plan_price)) { ?>
                                    <div class="form-group" id="atbd_pricing">
                                        <input type="hidden" id="atbd_listing_pricing" value="<?php echo $atbd_listing_pricing?>">
                                        <label for="#"><?php
                                            $price_label = get_directorist_option('price_label', __('Pricing', ATBDP_TEXTDOMAIN));
                                            esc_html_e($price_label.':', ATBDP_TEXTDOMAIN);
                                            ?></label>
                                        <div class="atbd_pricing_options">
                                            <?php
                                            if($plan_price){
                                                ?>
                                                <label for="price_selected" data-option="price">
                                                    <input type="checkbox" id="price_selected" value="price" name="atbd_listing_pricing"
                                                           <?php echo ('price' === $atbd_listing_pricing)?'checked':(empty($p_id)?'checked':'');?>>
                                                    <?php
                                                    $currency = get_directorist_option('g_currency', 'USD');
                                                    /*Translator: % is the name of the currency such eg. USD etc.*/
                                                    printf(esc_html__('Price [%s]%s', ATBDP_TEXTDOMAIN), $currency,get_directorist_option('require_price')?'<span class="atbdp_make_str_red">*</span>':''); ?>
                                                </label>
                                            <?php
                                            }
                                            ?>
                                            <?php
                                            if ($plan_average_price) {
                                                if($plan_price){
                                                    printf('<span>%s</span>', __('Or', ATBDP_TEXTDOMAIN));
                                                }
                                                ?>
                                                <label for="price_range_selected" data-option="price_range">
                                                    <input type="checkbox" id="price_range_selected"
                                                           value="range"
                                                           name="atbd_listing_pricing" <?php echo ('range' === $atbd_listing_pricing)?'checked':'';?>>
                                                    <?php echo __('Price Range', ATBDP_TEXTDOMAIN);echo get_directorist_option('require_price_range')?'<span class="atbdp_make_str_red">*</span>':'';?>
                                                    <!--<p id='price_range_option'><?php /*echo __('Price Range', ATBDP_TEXTDOMAIN); */ ?></p></label>-->
                                                </label>
                                                <?php
                                            }
                                            ?>

                                            <small><?php _e('(Optional - Uncheck to hide pricing for this listing)',ATBDP_TEXTDOMAIN) ?></small>
                                        </div>

                                        <?php
                                        if($plan_price){
                                        ?>
                                        <input type="text" id="price" name="price"
                                               value="<?= !empty($price) ? esc_attr($price) : ''; ?>"
                                               class="form-control directory_field"
                                               placeholder="<?= __('Price of this listing. Eg. 100', ATBDP_TEXTDOMAIN); ?>"/>

                                        <?php }
                                        if ($plan_average_price) {
                                            ?>
                                            <select class="form-control directory_field" id="price_range"
                                                    name="price_range">
                                                <option value=""><?= __('Select Price Range', ATBDP_TEXTDOMAIN); ?></option>
                                                <option value="skimming" <?php selected($price_range, 'skimming'); ?>>
                                                    <?= __('Ultra High ($$$$)', ATBDP_TEXTDOMAIN); ?>
                                                </option>
                                                <option value="moderate" <?php selected($price_range, 'moderate'); ?>>
                                                    <?= __('Expensive ($$$)', ATBDP_TEXTDOMAIN); ?>
                                                </option>
                                                <option value="economy" <?php selected($price_range, 'economy'); ?>>
                                                    <?= __('Moderate ($$)', ATBDP_TEXTDOMAIN); ?>
                                                </option>
                                                <option value="bellow_economy" <?php selected($price_range, 'economy'); ?>>
                                                    <?= __('Cheap ($)', ATBDP_TEXTDOMAIN); ?>
                                                </option>
                                            </select>
                                        <?php }

                                     /**
                                 * @since 4.7.1
                                 * It fires after the price field
                                 */
                                do_action('atbdp_add_listing_after_price_field', $p_id);
                                ?>
                                    </div>


                                <?php }
                                /**
                                 * @since 4.7.1
                                 * It fires after the price field
                                 */
                                do_action('atbdp_add_listing_after_price', $p_id);
                                ?>
                                 <?php if (!empty($display_excerpt_field) && empty($display_short_desc_for)){ ?>
                                    <div class="form-group">
                                        <label for="atbdp_excerpt"><?php
                                            $excerpt_label = get_directorist_option('excerpt_label', __('Short Description/Excerpt', ATBDP_TEXTDOMAIN));
                                            esc_html_e($excerpt_label.':', ATBDP_TEXTDOMAIN);
                                            echo get_directorist_option('require_excerpt')?'<span class="atbdp_make_str_red">*</span>':''; ?></label>
                                        <!--@todo; later let user decide if he wants to show tinymce or normal textarea-->
                                        <input type="hidden" id="has_excerpt" value="<?= !empty($excerpt) ? esc_textarea(stripslashes($excerpt)) :''; ?>">
                                        <textarea name="excerpt" id="atbdp_excerpt"
                                                  class="form-control directory_field" cols="30" rows="5"
                                                  placeholder="<?= __('Short Description or Excerpt', ATBDP_TEXTDOMAIN); ?>"><?= !empty($excerpt) ? esc_textarea(stripslashes($excerpt)) :''; ?></textarea>
                                    </div>
                                <?php }?>
                                <!--***********************************************************************
                                     Run the custom field loop to show all published custom fields asign to form
                                     **************************************************************************-->
                                <?php
                                // custom fields information
                                //// get all the custom field that has posted by admin ane return the field
                                $custom_fields = new WP_Query(array(
                                    'post_type' => ATBDP_CUSTOM_FIELD_POST_TYPE,
                                    'posts_per_page' => -1,
                                    'post_status' => 'publish',
                                    'meta_key' => 'associate',
                                    'meta_value' => 'form'
                                ));
                                $plan_custom_field = true;
                                if (is_fee_manager_active()){
                                    $plan_custom_field = is_plan_allowed_custom_fields($fm_plan);
                                }
                                if ($plan_custom_field){
                                    $fields = $custom_fields->posts;
                                }else{
                                    $fields = array();
                                }
                                foreach ($fields as $post) {
                                    setup_postdata($post);
                                    $post_id = $post->ID;
                                    $cf_required = get_post_meta(get_the_ID(), 'required', true);
                                    $post_meta = get_post_meta($post_id);
                                    $instructions = get_post_meta(get_the_ID(), 'instructions', true);
                                    ?>
                                    <div class="form-group" id="atbdp_custom_field_area">
                                        <label for=""><?php the_title(); ?><?php if ($cf_required) {
                                                echo '<span style="color: red"> *</span>';
                                            }
                                            if (!empty($instructions)) {
                                                printf('<span class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="%s"></span>', $instructions);
                                            }
                                            ?>
                                            </label>
                                            <?php
                                            if (isset($post_meta[$post->ID])) {
                                                $value = $post_meta[0];
                                            }
                                            $value = get_post_meta($p_id, $post_id, true); ///store the value for the db
                                            $cf_meta_default_val = get_post_meta(get_the_ID(), 'default_value', true);

                                        if (isset($post_id)) {
                                            $cf_meta_default_val = $post_id[0];
                                        }
                                        $cf_meta_val = get_post_meta(get_the_ID(), 'type', true);
                                        $cf_rows = get_post_meta(get_the_ID(), 'rows', true);
                                        $cf_placeholder = '';

                                        switch ($cf_meta_val) {
                                            case 'text' :
                                                echo '<div>';
                                                printf('<input type="text" name="custom_field[%d]" class="form-control directory_field" placeholder="%s" value="%s"/>', $post_id, $cf_placeholder, $value);
                                                echo '</div>';
                                                break;
                                            case 'number' :
                                                echo '<div>';
                                                printf('<input type="number" name="custom_field[%d]" class="form-control directory_field" placeholder="%s" value="%s"/>', $post_id, $cf_placeholder, $value);
                                                echo '</div>';
                                                break;
                                            case 'textarea' :
                                                echo '<div>';
                                                printf('<textarea  class="form-control directory_field" name="custom_field[%d]" class="textarea" rows="%d" placeholder="%s">%s</textarea>', $post->ID, (int)$cf_rows, esc_attr($cf_placeholder), esc_textarea($value));
                                                echo '</div>';
                                                break;
                                            case 'radio':
                                                echo '<div>';
                                                $choices = get_post_meta(get_the_ID(), 'choices', true);
                                                $choices = explode("\n", $choices);
                                                echo '<ul class="atbdp-radio-list vertical">';
                                                foreach ($choices as $choice) {
                                                    if (strpos($choice, ':') !== false) {
                                                        $_choice = explode(':', $choice);
                                                        $_choice = array_map('trim', $_choice);

                                                        $_value = $_choice[0];
                                                        $_label = $_choice[1];
                                                    } else {
                                                        $_value = trim($choice);
                                                        $_label = $_value;
                                                    }
                                                     $_checked = '';
                                                                                    if( trim( $value ) == $_value ) $_checked = ' checked="checked"';

                                                                                    printf( '<li><label><input type="radio" name="custom_field[%d]" value="%s"%s>%s</label></li>', $post->ID, $_value, $_checked, $_label );
                                                }
                                                    echo '</ul>';
                                                echo '</div>';
                                                    break;

                                                case 'select' :
                                                    echo '<div>';
                                                    $choices = get_post_meta(get_the_ID(), 'choices', true);
                                                    $choices = explode("\n", $choices);
                                                    printf('<select name="custom_field[%d]" class="form-control directory_field">', $post->ID);
                                                    if (!empty($field_meta['allow_null'][0])) {
                                                        printf('<option value="">%s</option>', '- ' . __('Select an Option', 'directorist') . ' -');
                                                    }
                                                    foreach ($choices as $choice) {
                                                        if (strpos($choice, ':') !== false) {
                                                            $_choice = explode(':', $choice);
                                                            $_choice = array_map('trim', $_choice);

                                                            $_value = $_choice[0];
                                                            $_label = $_choice[1];
                                                        } else {
                                                            $_value = trim($choice);
                                                            $_label = $_value;
                                                        }

                                                        $_selected = '';
                                                        if (trim($value) == $_value) $_selected = ' selected="selected"';

                                                        printf('<option value="%s"%s>%s</option>', $_value, $_selected, $_label);
                                                    }
                                                    echo '</select>';
                                                    echo '</div>';
                                                    break;

                                                case 'checkbox' :
                                                    echo '<div>';
                                                    $choices = get_post_meta(get_the_ID(), 'choices', true);
                                                    $choices = explode("\n", $choices);

                                                    $values = explode("\n", $value);
                                                    $values = array_map('trim', $values);
                                                    echo '<ul class="atbdp-checkbox-list vertical">';

                                                    foreach ($choices as $choice) {
                                                        if (strpos($choice, ':') !== false) {
                                                            $_choice = explode(':', $choice);
                                                            $_choice = array_map('trim', $_choice);

                                                            $_value = $_choice[0];
                                                            $_label = $_choice[1];
                                                        } else {
                                                            $_value = trim($choice);
                                                            $_label = $_value;
                                                        }

                                                        $_checked = '';
                                                        if (in_array($_value, $values)) $_checked = ' checked="checked"';

                                                        printf('<li><label><input type="hidden" name="custom_field[%s][]" value="" /><input type="checkbox" name="custom_field[%d][]" value="%s"%s> %s</label></li>', $post->ID, $post->ID, $_value, $_checked, $_label);
                                                    }
                                                    echo '</ul>';
                                                    echo '</div>';
                                                    break;
                                                case 'url'  :
                                                    echo '<div>';
                                                    printf('<input type="text" name="custom_field[%d]" class="form-control directory_field" placeholder="%s" value="%s"/>', $post->ID, esc_attr($cf_placeholder), esc_url($value));
                                                    echo '</div>';
                                                    break;

                                                case 'date'  :
                                                    echo '<div>';
                                                    printf('<input type="date" name="custom_field[%d]" class="form-control directory_field" placeholder="%s" value="%s"/>', $post->ID, esc_attr($cf_placeholder), esc_attr($value));
                                                    echo '</div>';
                                                    break;

                                                case 'email'  :
                                                    echo '<div>';
                                                    printf('<input type="email" name="custom_field[%d]" class="form-control directory_field" placeholder="%s" value="%s"/>', $post->ID, esc_attr($cf_placeholder), esc_attr($value));
                                                    echo '</div>';
                                                    break;
                                                case 'color'  :
                                                    echo '<div>';
                                                    printf('<input type="text" name="custom_field[%d]" id="color_code2" class="my-color-field" value="%s"/>', $post->ID, $value);
                                                    echo '</div>';
                                                    break;

                                                case 'time'  :
                                                    echo '<div>';
                                                    printf('<input type="time" name="custom_field[%d]" class="form-control directory_field" placeholder="%s" value="%s"/>', $post->ID, esc_attr($cf_placeholder), esc_attr($value));
                                                    echo '</div>';
                                                    break;
                                                case 'file'  :
                                                    require ATBDP_TEMPLATES_DIR . 'file-uploader.php';
                                                    break;
                                            }
                                            ?>
                                    </div>
                                    <?php
                                }
                                wp_reset_postdata();
                                ?>
                                <?php if(empty($display_loc_for)) {?>
                                <div class="form-group" id="atbdp_locations">
                                    <label for="at_biz_dir-location"><?php
                                        $location_label = get_directorist_option('location_label', __('Location', ATBDP_TEXTDOMAIN));
                                        esc_html_e($location_label.':', ATBDP_TEXTDOMAIN);
                                        echo get_directorist_option('require_location')?'<span class="atbdp_make_str_red">*</span>':'';?></label>
                                 <?php
                                    $location = wp_get_object_terms($p_id, ATBDP_LOCATION, array('fields' => 'ids'));
                                    $selected_location = count($location) ? $location[0] : -1;
                                    $args = array(
                                        'show_option_none' => apply_filters('atbdp_listing_form_location_placeholder','-- ' .__('Select Location', ATBDP_TEXTDOMAIN) . ' --'),
                                        'taxonomy' => ATBDP_LOCATION,
                                        'id' => 'loc-type',
                                        'class' => 'form-control directory_field',
                                        'name' => 'tax_input[at_biz_dir-location][]',
                                        'orderby' => 'name',
                                        'selected' => $selected_location,
                                        'hierarchical' => true,
                                        'depth' => 10,
                                        'show_count' => false,
                                        'hide_empty' => false,
                                    );

                                    wp_dropdown_categories($args);
                                    ?>

                                </div>
                                <?php } ?>
                                <?php
                                $plan_tag = true;
                                if (is_fee_manager_active()){
                                    $plan_tag = is_plan_allowed_tag($fm_plan);
                                }
                                if ($plan_tag && empty($display_tag_for)) {
                                    ?>
                                    <div class="form-group tag_area" id="atbdp_tags">
                                        <label for="at_biz_dir-tags"><?php
                                            $tag_label = get_directorist_option('tag_label', __('Tags', ATBDP_TEXTDOMAIN));
                                            esc_html_e($tag_label.':', ATBDP_TEXTDOMAIN);
                                            echo get_directorist_option('require_tags') ? '<span class="atbdp_make_str_red">*</span>' : ''; ?></label>
                                        <?php if (!empty($p_tags)) {
                                            $output = array();
                                            foreach ($p_tags as $p_tag) {
                                                $output[] = $p_tag->name;
                                            }
                                            echo '<p class="c_cat_list">' . __('Current Tags: <strong>', ATBDP_TEXTDOMAIN) . join(', ', $output) . '</strong></p>';
                                        } ?>
                                        <select name="tax_input[at_biz_dir-tags][]" class="form-control"
                                                id="at_biz_dir-tags" multiple="multiple">

                                            <?php foreach ($listing_tags as $l_tag) { ?>
                                                <option id='atbdp_tag'
                                                        value='<?= $l_tag->name ?>'><?= esc_html($l_tag->name) ?></option>
                                            <?php } ?>
                                        </select>
                                        <?php
                                        /**
                                         * @since 4.7.2
                                         * It fires after the tag field
                                         */
                                        do_action('atbdp_add_listing_after_tag_field', $p_id);
                                        ?>
                                    </div>
                                    <?php
                                }
                                /**
                                 * @since 4.7.1
                                 * It fires after the tag field
                                 */
                                do_action('atbdp_add_listing_after_tag', $p_id);
                                ?>
                                <!--***********************************************************************
                                    Run the custom field loop to show all published custom fields asign to Category
                                 **************************************************************************-->
                                <!--@ Options for select the category.-->
                                <div class="form-group" id="atbdp_categories">
                                    <label for="atbdp_select_cat"><?php
                                        $category_label = get_directorist_option('category_label', __('Select Category', ATBDP_TEXTDOMAIN));
                                        esc_html_e($category_label.':', ATBDP_TEXTDOMAIN);
                                        echo get_directorist_option('require_category')?'<span class="atbdp_make_str_red">*</span>':'';?></label>
                                    <?php
                                    $category = wp_get_object_terms($p_id, ATBDP_CATEGORY, array('fields' => 'ids'));
                                    $selected_category = count($category) ? $category[0] : -1;
                                    $plan_cat = 0;
                                    if (is_fee_manager_active()){
                                        $plan_cat = is_plan_allowed_category($fm_plan);
                                    }
                                    $args = array(
                                        'show_option_none' => '-- ' . __('Select Category', ATBDP_TEXTDOMAIN) . ' --',
                                        'taxonomy' => ATBDP_CATEGORY,
                                        'id' => 'cat-type',
                                        'class' => 'form-control directory_field',
                                        'name' => 'admin_category_select',
                                        'orderby' => 'name',
                                        'selected' => $selected_category,
                                        'hierarchical' => true,
                                        'depth' => 10,
                                        'exclude' => $plan_cat,
                                        'show_count' => false,
                                        'hide_empty' => false,
                                    );

                                    wp_dropdown_categories($args);
                                    $current_val = esc_attr(get_post_meta($p_id, '_admin_category_select', true));
                                    $term_id_selected = !empty($current_val) ? $current_val : '';
                                    ?>
                                    <input type="hidden" id="value_selected" value="<?php echo $term_id_selected ?>">
                                </div>
                                <?php
                                $plan_custom_field = true;
                                if (is_fee_manager_active()){
                                    $plan_custom_field = is_plan_allowed_custom_fields($fm_plan);
                                }
                                if ($plan_custom_field){
                                   ?>
                                    <div id="atbdp-custom-fields-list" data-post_id="<?php echo $p_id; ?>">
                                        <?php
                                        $selected_category = !empty($selected_category) ? $selected_category : '';
                                        do_action('wp_ajax_atbdp_custom_fields_listings', $p_id, $selected_category); ?>
                                    </div>
                                <?php
                                }
                                ?>

                                <?php
                                if ($term_id_selected) {
                                    ?>
                                    <div id="atbdp-custom-fields-list-selected" data-post_id="<?php echo $p_id; ?>">
                                        <?php
                                        $selected_category = !empty($selected_category) ? $selected_category : '';
                                        do_action('wp_ajax_atbdp_custom_fields_listings_selected', $p_id, $selected_category); ?>
                                    </div>
                                    <?php
                                }
                                ?>

                            </div>

                        </div><!-- end .atbd_custom_fields_contents -->
                        <div class="atbdb_content_module">
                        <?php if (empty($display_phone_for || $display_address_for || $display_email_for || $display_website_for || $display_zip_for || $display_social_info_for) || !empty($display_address_field || $display_phone_field || $display_email_field || $display_website_field  || $display_zip_field || $display_social_info_field)) { ?>
                            <div class="atbd_content_module atbd_contact_information">
                                <div class="atbd_content_module__tittle_area">
                                    <div class="atbd_area_title">
                                        <h4><?php esc_html_e('Contact Information', ATBDP_TEXTDOMAIN) ?></h4>
                                    </div>
                                </div>

                                <div class="atbdb_content_module_contents">
                                    <div class="form-check">
                                        <input type="checkbox" name="hide_contact_info" class="form-check-input"
                                               id="hide_contact_info" value="1" <?php if (!empty($hide_contact_info)) {
                                            checked($hide_contact_info);
                                        } ?> >
                                        <label class="form-check-label"
                                               for="hide_contact_info"><?php esc_html_e('Check it to hide Contact Information for this listing', ATBDP_TEXTDOMAIN); ?></label>
                                    </div>

                                    <?php if(!$disable_contact_owner) {?>
                                        <div class="form-check">
                                            <input type="checkbox" name="hide_contact_owner" class="form-check-input"
                                                   id="hide_contact_owner" value="1" <?php if (!empty($hide_contact_owner)) {
                                                checked($hide_contact_owner);
                                            } ?> >
                                            <label class="form-check-label"
                                                   for="hide_contact_owner"><?php esc_html_e('Check it to hide Contact listing owner', ATBDP_TEXTDOMAIN); ?></label>
                                        </div>
                                    <?php } ?>

                                    <?php
                                    if (empty($display_map_for || $display_address_for) && !empty($display_map_field || $display_address_field)) { ?>
                                        <div class="form-group" id="atbdp_address">
                                            <label for="address"><?php
                                                $address_label = get_directorist_option('address_label', __('Google Address', ATBDP_TEXTDOMAIN));
                                                esc_html_e($address_label.':', ATBDP_TEXTDOMAIN);
                                                echo get_directorist_option('require_address')?'<span class="atbdp_make_str_red">*</span>':''; ?></label>
                                            <input autocomplete="off" type="text" name="address" id="address"
                                                   value="<?= !empty($address) ? esc_attr($address) : ''; ?>"
                                                   class="form-control directory_field"
                                                   placeholder="<?php esc_html_e('Listing address eg. New York, USA', ATBDP_TEXTDOMAIN); ?>"/>
                                            <div id="result">
                                                <ul></ul>
                                            </div>
                                        </div>

                                            <!--Show map only if it is not disabled in the settings-->
                                            <!--Google map will be generated here using js-->
                                        <?php if(!empty($display_map_field)) {?>
                                            <div class="form-group">
                                                <div class="map_wrapper">
                                                    <?php if ('google' == $select_listing_map) {?>
                                                    <div id="floating-panel">
                                                        <button class="btn btn-danger"
                                                                id="delete_marker"><?php _e('Delete Marker', ATBDP_TEXTDOMAIN); ?></button>
                                                    </div>
                                                    <?php } ?>
                                                    <div id="gmap"></div>
                                                    <?php if ('google' == $select_listing_map) {?>
                                                    <small  class="map_drag_info"><i class="fa fa-info-circle" aria-hidden="true"></i> <?php _e('You can drag pinpoint to place the correct address manually.', ATBDP_TEXTDOMAIN); ?></small>
                                                    <?php } ?>
                                                    <div class="cor-wrap">
                                                    <?php $map_guide = sprintf("<span class='color:#c71585;'>%s</span>", __('SET 0 to LAT & LONG Field to HIDE MAP FOR THIS LISTING', ATBDP_TEXTDOMAIN)); ?>
                                                    <label for="manual_coordinate"><input type="checkbox" name="manual_coordinate"
                                                                                        value="1"
                                                                                        id="manual_coordinate" <?= (!empty($manual_coordinate)) ? 'checked' : ''; ?> > <?php
                                                        printf(__('Or Enter Coordinates (latitude and longitude) Manually.', ATBDP_TEXTDOMAIN), $map_guide)
                                                        ?>
                                                    </label>
                                                </div>

                                                </div>

                                                <div class="row">
                                                    <div id="hide_if_no_manual_cor" class="clearfix">
                                                        <div class="col-md-6 col-sm-12">
                                                            <div class="form-group">
                                                                <label for="manual_lat"> <?php _e('Latitude', ATBDP_TEXTDOMAIN); ?>  </label>
                                                                <input type="text" name="manual_lat" id="manual_lat"
                                                                    value="<?= !empty($manual_lat) ? esc_attr($manual_lat) : $default_latitude; ?>"
                                                                    class="form-control directory_field"
                                                                    placeholder="<?php esc_attr_e('Enter Latitude eg. 24.89904', ATBDP_TEXTDOMAIN); ?>"/>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6 col-sm-12">
                                                            <div class="form-group">
                                                                <label for="manual_lng"> <?php _e('Longitude', ATBDP_TEXTDOMAIN); ?> </label>
                                                                <input type="text" name="manual_lng" id="manual_lng"
                                                                    value="<?= !empty($manual_lng) ? esc_attr($manual_lng) : $default_longitude; ?>"
                                                                    class="form-control directory_field"
                                                                    placeholder="<?php esc_attr_e('Enter Longitude eg. 91.87198', ATBDP_TEXTDOMAIN); ?>"/>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3 col-sm-12">
                                                            <div class="form-group lat_btn_wrap">
                                                                <button class="btn btn-primary"
                                                                        id="generate_admin_map"><?php _e('Generate on Map', ATBDP_TEXTDOMAIN); ?></button>
                                                            </div>
                                                        </div> <!-- ends #hide_if_no_manual_cor-->

                                                    </div> <!--ends .row -->
                                                    <div class="col-sm-12">
                                                        <div class="form-group hide-map-option">
                                                            <input type="checkbox" name="hide_map" value="1"
                                                                id="hide_map" <?= (!empty($hide_map)) ? 'checked' : ''; ?> >
                                                            <label for="hide_map"> <?php _e('Hide map for this listing.', ATBDP_TEXTDOMAIN); ?> </label>
                                                        </div>
                                                    </div>
                                                </div> <!--ends .row-->
                                            </div><!--ends .row-->
                                    <?php } }

                                /**
                                 * It fires after the google map preview area
                                 * @param string $type Page type.
                                 * @param array $listing_info Information of the current listing
                                 * @since 1.1.1
                                 **/
                                do_action('atbdp_edit_after_googlemap_preview', 'add_listing_page_frontend', $listing_info, $p_id);
                                    if (empty($display_zip_for) && !empty($display_zip_field)){
                                    ?>
                                    <div class="form-group" id="atbdp_zip">
                                        <label for="atbdp_zip"><?php
                                            $zip_label = get_directorist_option('zip_label', __('Zip/Post Code', ATBDP_TEXTDOMAIN));
                                            esc_html_e($zip_label.':', ATBDP_TEXTDOMAIN);
                                            echo get_directorist_option('require_zip')?'<span class="atbdp_make_str_red">*</span>':''; ?></label>

                                        <input type="text" id="atbdp_zip" name="zip"
                                               value="<?= !empty($zip) ? esc_attr($zip) : ''; ?>"
                                               class="form-control directory_field"
                                               placeholder="<?php esc_attr_e('Enter Zip/Post Code', ATBDP_TEXTDOMAIN); ?>"/>
                                    </div>
                                    <?php }?>

                                    <?php
                                    $plan_phone = true;
                                    if (is_fee_manager_active()){
                                        $plan_phone = is_plan_allowed_listing_phone($fm_plan);
                                    }
                                    if ($plan_phone && empty($display_phone_for) && !empty($display_phone_field)){
                                    ?>
                                    <div class="form-group" id="atbdp_phone">
                                        <label for="atbdp_phone_number"><?php
                                            $phone_label = get_directorist_option('phone_label', __('Phone Number', ATBDP_TEXTDOMAIN));
                                            esc_html_e($phone_label.':', ATBDP_TEXTDOMAIN);
                                            echo get_directorist_option('require_phone_number')?'<span class="atbdp_make_str_red">*</span>':'';?></label>
                                        <input type="tel" name="phone" id="atbdp_phone_number"
                                               value="<?= !empty($phone) ? esc_attr($phone) : ''; ?>"
                                               class="form-control directory_field"
                                               placeholder="<?php esc_attr_e('Phone Number', ATBDP_TEXTDOMAIN); ?>"/>
                                    </div>
                                    <?php }
                                    $plan_email = true;
                                    if (is_fee_manager_active()){
                                        $plan_email = is_plan_allowed_listing_email($fm_plan);
                                    }
                                    if ($plan_email && empty($display_email_for) && !empty($display_email_field)){
                                        ?>
                                        <div class="form-group" id="atbdp_emails">
                                            <label for="atbdp_email"><?php
                                                $email_label = get_directorist_option('email_label', __('Email', ATBDP_TEXTDOMAIN));
                                                esc_html_e($email_label.':', ATBDP_TEXTDOMAIN);
                                                echo get_directorist_option('require_email')?'<span class="atbdp_make_str_red">*</span>':''; ?></label>
                                            <input type="email" name="email" id="atbdp_email"
                                                   value="<?= !empty($email) ? esc_attr($email) : ''; ?>"
                                                   class="form-control directory_field"
                                                   placeholder="<?php esc_attr_e('Enter Email', ATBDP_TEXTDOMAIN); ?>"/>
                                        </div>
                                        <?php }
                                        $plan_webLink = true;
                                    if (is_fee_manager_active()){
                                        $plan_webLink = is_plan_allowed_listing_webLink($fm_plan);
                                    }
                                    if ($plan_webLink && empty($display_website_for) && !empty($display_website_field)){
                                    ?>
                                    <div class="form-group" id="atbdp_webs">
                                        <label for="atbdp_website"><?php
                                            $website_label = get_directorist_option('website_label', __('Website', ATBDP_TEXTDOMAIN));
                                            esc_html_e($website_label.':', ATBDP_TEXTDOMAIN);
                                            echo get_directorist_option('require_website')?'<span class="atbdp_make_str_red">*</span>':''; ?></label>

                                        <input type="text" id="atbdp_website" name="website"
                                               value="<?= !empty($website) ? esc_url($website) : ''; ?>"
                                               class="form-control directory_field"
                                               placeholder="<?php esc_attr_e('Listing website eg. http://example.com', ATBDP_TEXTDOMAIN); ?>"/>
                                    </div>
                                    <?php }
                                     ?>

                                    <div class="form-group" id="atbdp_socialInFo">
                                        <?php
                                        /**
                                         * It fires before social information fields
                                         * @param string $type Page type.
                                         * @param array $listing_info Information of the current listing
                                         * @since 1.1.1
                                         **/
                                        do_action('atbdp_edit_before_social_info_fields', 'add_listing_page_frontend', $listing_info);
                                        $plan_social_networks = true;
                                        if (is_fee_manager_active()){
                                            $plan_social_networks = is_plan_allowed_listing_social_networks($fm_plan);
                                        }
                                        if ($plan_social_networks && empty($display_social_info_for) && !empty($display_social_info_field)) {
                                            ATBDP()->load_template('meta-partials/social', array('social_info' => $social_info));
                                        }
                                        /**
                                         * It fires after social information fields
                                         * @param string $type Page type.
                                         * @param array $listing_info Information of the current listing
                                         * @since 1.1.1
                                         **/
                                        do_action('atbdp_edit_after_social_info_fields', 'add_listing_page_frontend', $listing_info);

                                        ?>
                                    </div>
                                </div>
                            </div><!-- end .atbd_general_information_module -->
                            <?php
                            $plan_hours = true;
                            if (is_fee_manager_active()){
                                $plan_hours = is_plan_allowed_business_hours($fm_plan);
                            }
                            if (is_business_hour_active() && $plan_hours) {
                                ?>
                                <div class="atbd_content_module atbd_business_hour_module">
                                    <div class="atbd_content_module__tittle_area">
                                        <div class="atbd_area_title">
                                            <h4><?php _e('Opening/Business Hour Information', ATBDP_TEXTDOMAIN); ?></h4>
                                        </div>
                                    </div>

                                    <div class="atbdb_content_module_contents">
                                        <?php
                                        /**
                                         * It fires before social information fields
                                         * @param string $type Page type.
                                         * @param array $listing_info Information of the current listing
                                         * @since 1.1.1
                                         **/
                                        apply_filters('atbdp_edit_after_contact_info_fields', 'add_listing_page_frontend', $listing_info);
                                        ?>
                                    </div>
                                </div>
                                <?php
                            }
                            /**
                             * It fires before map
                             * @param string $type Page type.
                             * @param array $listing_info Information of the current listing
                             * @since 4.0
                             **/
                            do_action('atbdp_edit_after_business_hour_fields', 'add_listing_page_frontend', $listing_info);


                         }
                            /**
                            * It fires before map
                            * @param string $type Page type.
                            * @param array $listing_info Information of the current listing
                            * @since 4.0
                            **/
                            apply_filters('atbdp_after_contact_info_section', 'add_listing_page_frontend', $listing_info, $p_id);
                            ?>
                        <div class="directorist-contact-fields atbdp_info_module">
                            <div class="atbdp_info_module">
                                <?php
                                /**
                                 * It fires after the google map preview area
                                 * @param string $type Page type.
                                 * @param array $listing_info Information of the current listing
                                 * @since 4.4.7
                                 **/
                                do_action('atbdp_edit_before_video_field', 'add_listing_page_frontend', $listing_info, $p_id); ?>
                            </div>

                            <?php
                            $plan_video = true;
                            if (is_fee_manager_active()){
                                $plan_video =is_plan_allowed_listing_video($fm_plan);
                            }
                            $plan_slider = true;
                            if (is_fee_manager_active()){
                                $plan_slider =is_plan_allowed_slider($fm_plan);
                            }
                            ?>

                            <div class="atbd_content_module">
                                <div class="atbd_content_module__tittle_area">
                                    <div class="atbd_area_title">
                                        <h4>
                                            <?php
                                           $video_text = $plan_video?'& Video':'';
                                            _e("Images {$video_text}", ATBDP_TEXTDOMAIN);
                                            ?></h4>
                                    </div>
                                </div>

                                <div class="atbdb_content_module_contents">
                                    <!--Image Uploader-->
                                    <?php if((!empty($display_prv_field) && empty($display_prv_img_for)) || (!empty($display_gellery_field) && empty($display_glr_img_for))){?>
                                    <div id="_listing_gallery">
                                        <?php ATBDP()->load_template('front-end/front-media-upload', compact('listing_img', 'listing_prv_img', 'plan_slider', 'p_id'));
                                        ?>
                                    </div>
                                    <?php }?>
                                    <?php
                                    /**
                                     * @since 4.7.1
                                     * It fires after the tag field
                                     */
                                    do_action('atbdp_add_listing_after_listing_slider', 'add_listing_page_frontend', $listing_info);
                                    ?>
                                    <?php
                                    if (empty($display_video_for) && !empty($display_video_field) && $plan_video) {
                                        ?>
                                        <div class="form-group">
                                            <label for="videourl"><?php
                                                $video_label = get_directorist_option('video_label', __('Video Url', ATBDP_TEXTDOMAIN));
                                                esc_html_e($video_label.':', ATBDP_TEXTDOMAIN);
                                                echo get_directorist_option('require_video')?'<span class="atbdp_make_str_red">*</span>':''; ?></label>
                                            <input type="text" id="videourl" name="videourl"
                                                   value="<?= !empty($videourl) ? esc_url($videourl) : ''; ?>"
                                                   class="form-control directory_field"
                                                   placeholder="<?= __('Only YouTube & Vimeo URLs.', ATBDP_TEXTDOMAIN); ?>"/>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>


                            <?php
                            /*
                             * @since 4.1.0
                             */
                            do_action('atbdp_before_terms_and_conditions_font');

                            if (!empty(get_directorist_option('listing_terms_condition'))) {
                                ?>

                                <div class="atbd_term_and_condition_area">
                                    <?php
                                    if (get_directorist_option('require_terms_conditions') == 1) {
                                        printf('<span class="atbdp_make_str_red"> *</span>');
                                    }
                                    ?>
                                    <input id="listing_t" type="checkbox"
                                           name="t_c_check" <?php if (!empty($t_c_check)) if ('on' == $t_c_check) {
                                        echo 'checked';
                                    } ?>>
                                    <label for="listing_t"><?php echo __('I Agree with all ', ATBDP_TEXTDOMAIN); ?><a
                                                style="color: red" href="" id="listing_t_c"
                                        "><?php echo __('terms & conditions', ATBDP_TEXTDOMAIN); ?></a></label>
                                    <div id="tc_container" class="">
                                        <p><?php _e($listing_terms_condition_text, ATBDP_TEXTDOMAIN); ?></p>
                                    </div>
                                </div>

                                <?php
                            }
                            /**
                             * It fires before rendering submit listing button on the front end.
                             */
                            do_action('atbdp_before_submit_listing_frontend', $p_id);
                            ?>
                            <div class="btn_wrap list_submit">
                                <button type="submit"
                                        class="btn btn-primary btn-lg listing_submit_btn"><?php
                                    $submit_label = get_directorist_option('submit_label', __('Submit listing', ATBDP_TEXTDOMAIN));
                                    echo  !empty($p_id) ? __('Update Listing', ATBDP_TEXTDOMAIN) :

                                        __($submit_label, ATBDP_TEXTDOMAIN); ?></button>
                            </div>

                            <div class="clearfix"></div>
                        </div> <!--ends col-md-12 -->
                    </div><!--ends .row-->
                </div>
            </div>
            </fieldset>
        </form>
    </div> <!--ends container-fluid-->
</div>

<?php
if('openstreet' == $select_listing_map) {
    wp_register_script( 'openstreet_layer', ATBDP_PUBLIC_ASSETS . 'js/openstreetlayers.js', array( 'jquery' ), ATBDP_VERSION, true );
    wp_enqueue_script( 'openstreet_layer' );
 }
?>
<script>

    jQuery(document).ready(function ($) {

        <?php if(is_fee_manager_active() ) { ?>
//        $('#fm_plans_container').on('click', function(){
//            $('.atbdp-form-fields').fadeIn(1000);
//            $('#fm_plans_container').fadeOut(300)
//        });
        <?php } ?>



        // Bias the auto complete object to the user's geographical location,
        // as supplied by the browser's 'navigator.geolocation' object.
        <?php if ( empty($display_map_for || $display_address_for) && !empty($display_map_field && $display_address_field) ) {
            if('google' == $select_listing_map) {
            ?>
        // initialize all vars here to avoid hoisting related misunderstanding.
        var placeSearch, map, autocomplete, address_input, markers, info_window, $manual_lat, $manual_lng,
            saved_lat_lng, info_content;
        $manual_lat = $('#manual_lat');
        $manual_lng = $('#manual_lng');
        saved_lat_lng = {
            lat:<?= (!empty($manual_lat)) ? floatval($manual_lat) : $default_latitude ?>,
            lng: <?= (!empty($manual_lng)) ? floatval($manual_lng) : $default_longitude ?> }; // default is London city
        info_content = "<?= $info_content ?>";
        markers = [];// initialize the array to keep track all the marker
        info_window = new google.maps.InfoWindow({
            content: info_content,
            maxWidth: 400
        });


        address_input = document.getElementById('address');
        address_input.addEventListener('focus', geolocate);
        // this function will work on sites that uses SSL, it applies to Chrome especially, other browsers may allow location sharing without securing.
        function geolocate() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    var geolocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    var circle = new google.maps.Circle({
                        center: geolocation,
                        radius: position.coords.accuracy
                    });
                    autocomplete.setBounds(circle.getBounds());
                });
            }
        }


        function initAutocomplete() {
            // Create the autocomplete object, restricting the search to geographical
            // location types.
            autocomplete = new google.maps.places.Autocomplete(
                (address_input),
                {types: []});

            // When the user selects an address from the dropdown, populate the necessary input fields and draw a marker
            autocomplete.addListener('place_changed', fillInAddress);
        }

        function fillInAddress() {
            // Get the place details from the autocomplete object.
            var place = autocomplete.getPlace();

            // set the value of input field to save them to the database
            $manual_lat.val(place.geometry.location.lat());
            $manual_lng.val(place.geometry.location.lng());
            map.setCenter(place.geometry.location);
            var marker = new google.maps.Marker({
                map: map,
                position: place.geometry.location
            });

            marker.addListener('click', function () {
                info_window.open(map, marker);
            });

            // add the marker to the markers array to keep track of it, so that we can show/hide/delete them all later.
            markers.push(marker);
        }

        initAutocomplete(); // start google map place auto complete API call


        function initMap() {
            /* Create new map instance*/
            map = new google.maps.Map(document.getElementById('gmap'), {
                zoom: <?php echo !empty($map_zoom_level) ? intval($map_zoom_level) : 4; ?>,
                center: saved_lat_lng
            });
            var marker = new google.maps.Marker({
                map: map,
                position: saved_lat_lng,
                draggable: true,
                title: '<?php _e('You can drag the marker to your desired place to place a marker', ATBDP_TEXTDOMAIN); ?>'
            });
            marker.addListener('click', function () {
                info_window.open(map, marker);
            });
            // add the marker to the markers array to keep track of it, so that we can show/hide/delete them all later.
            markers.push(marker);

            // create a Geocode instance
            var geocoder = new google.maps.Geocoder();

            document.getElementById('generate_admin_map').addEventListener('click', function (e) {
                e.preventDefault();
                geocodeAddress(geocoder, map);
            });


            // This event listener calls addMarker() when the map is clicked.
            google.maps.event.addListener(map, 'click', function (event) {
                deleteMarker(); // at first remove previous marker and then set new marker;
                // set the value of input field to save them to the database
                $manual_lat.val(event.latLng.lat());
                $manual_lng.val(event.latLng.lng());
                // add the marker to the given map.
                addMarker(event.latLng, map);
            });
            // This event listener update the lat long field of the form so that we can add the lat long to the database when the MARKER is drag.
            google.maps.event.addListener(marker, 'dragend', function (event) {
                // set the value of input field to save them to the database
                $manual_lat.val(event.latLng.lat());
                $manual_lng.val(event.latLng.lng());
            });
        }

        /*
         * Geocode and address using google map javascript api and then populate the input fields for storing lat and long
         * */

        function geocodeAddress(geocoder, resultsMap) {
            var address = address_input.value;
            geocoder.geocode({'address': address}, function (results, status) {
                if (status === 'OK') {
                    // set the value of input field to save them to the database
                    $manual_lat.val(results[0].geometry.location.lat());
                    $manual_lng.val(results[0].geometry.location.lng());
                    resultsMap.setCenter(results[0].geometry.location);
                    var marker = new google.maps.Marker({
                        map: resultsMap,
                        position: results[0].geometry.location
                    });

                    marker.addListener('click', function () {
                        info_window.open(map, marker);
                    });

                    // add the marker to the markers array to keep track of it, so that we can show/hide/delete them all later.
                    markers.push(marker);
                } else {
                    alert('<?php _e('Geocode was not successful for the following reason: ', ATBDP_TEXTDOMAIN); ?>' + status);
                }
            });
        }

        initMap();


        // adding features of creating marker manually on the map on add listing page.
        /*var labels = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
         var labelIndex = 0;*/


        // Adds a marker to the map.
        function addMarker(location, map) {
            // Add the marker at the clicked location, and add the next-available label
            // from the array of alphabetical characters.
            var marker = new google.maps.Marker({
                position: location,
                /*label: labels[labelIndex++ % labels.length],*/
                draggable: true,
                title: '<?php _e('You can drag the marker to your desired place to place a marker', ATBDP_TEXTDOMAIN); ?>',
                map: map
            });
            marker.addListener('click', function () {
                info_window.open(map, marker);
            });
            // add the marker to the markers array to keep track of it, so that we can show/hide/delete them all later.
            markers.push(marker);
        }

        // Delete Marker
        $('#delete_marker').on('click', function (e) {
            e.preventDefault();
            deleteMarker();

        });

        function deleteMarker() {
            for (var i = 0; i < markers.length; i++) {
                markers[i].setMap(null);
            }
            markers = [];
        }
        <?php } elseif('openstreet' == $select_listing_map) {?>

        $('#address').on('keyup', function(event) {
            event.preventDefault();
            if(event.keyCode !== 40 && event.keyCode !== 38){
            var address = $('#address').val();
            $('#result').css({'display':'block'});
            if(address === ""){
                $('#result').css({'display':'none'});
            }
            var res = "";
            $.ajax({
                url: `https://nominatim.openstreetmap.org/?q=%27+${address}+%27&format=json`,
                type: 'POST',
                data: {},
                success: function (data) {
                    //console.log(data);
                    for (var i = 0; i < data.length; i++) {
                        res += `<li><a href="#" data-lat=${data[i].lat} data-lon=${data[i].lon}>${data[i].display_name}</a></li>`
                    }
                    $('#result ul').html(res);
                }
            });
            }
        });





        let mymap = (lon, lat) => {
           
            map = new OpenLayers.Map("gmap");
			map.addLayer(new OpenLayers.Layer.OSM());
	            var pois = new OpenLayers.Layer.Text( "My Points",
	                            { location:"./textfile.txt",
	                              projection: map.displayProjection
	                            });
	            map.addLayer(pois);
	         // create layer switcher widget in top right corner of map.
	            var layer_switcher= new OpenLayers.Control.LayerSwitcher({});
	            map.addControl(layer_switcher);

	            var EPSG4326        = new OpenLayers.Projection( "EPSG:4326" );
    			var EPSG900913      = new OpenLayers.Projection("EPSG:900913");

	            //Set start centrepoint and zoom
	            var XY = new OpenLayers.LonLat( lon, lat )
	                  .transform(
	                    new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
	                    map.getProjectionObject() // to Spherical Mercator Projection
	                  );
                      
                let zoom= <?php echo !empty($map_zoom_level) ? intval($map_zoom_level) : 4; ?>;
	            


	            map.setCenter (XY, zoom);
	             	var	deftColor     = "#00FF00";
				    var	deftIcon      = "<?php echo ATBDP_PUBLIC_ASSETS . 'images/map-icon.png'; ?>";
				    var	featureHeight = 40;
				    var	featureWidth  = 30;
				    var	featureStyle  =	{
				        fillColor:      deftColor,
				        strokeColor:    deftColor,
				        pointRadius:    1,
				        externalGraphic:deftIcon,
				        graphicWidth:   featureWidth,
				        graphicHeight:  featureHeight,
				        graphicXOffset: -featureWidth/2,
				        graphicYOffset: -featureHeight,
				        label:          "",
				        fontColor:      "#000000",
				        fontSize:       "10px",
				        fontWeight:     "bold",
				        labelAlign:     "rm"
				    };

	            var	vectorL = new OpenLayers.Layer.Vector(  "Vector Layer", {
                     styleMap:   new OpenLayers.StyleMap(  featureStyle  )
			    });
			    map.addLayer( vectorL );

	            var	dragVectorC = new OpenLayers.Control.DragFeature(   vectorL, { 
	              onDrag: function(feature, pixel){

			        //Don´t user the position of the pixel or the feature, use the point position instead!
			        var point = feature.geometry.components[0];

			        var llpoint = point.clone()
			        llpoint.transform(  new OpenLayers.Projection(EPSG900913), 
			                            new OpenLayers.Projection(EPSG4326));
			       

			        $('#manual_lat').val(llpoint.y);
					$('#manual_lng').val(llpoint.x);


			    }});

		    map.addControl( dragVectorC );
		    dragVectorC.activate();

		    var	point       = new OpenLayers.Geometry.Point( XY.lon, XY.lat );
		    var	featureOb   = new OpenLayers.Feature.Vector( new OpenLayers.Geometry.Collection([point]) );
		    vectorL.addFeatures( [featureOb] );

        }

        let lat = $('#manual_lat').val(),
            lon = $('#manual_lng').val();

        mymap(lon, lat);

        $('body').on('click', '#result ul li a', function(event) {
            event.preventDefault();
            $('#mapinit').html('<div id="gmap"></div>');
            let text = $(this).text(),
                lat = $(this).data('lat'),
                lon = $(this).data('lon');

            $('#manual_lat').val(lat);
            $('#manual_lng').val(lon);

            $('#address').val(text);
            $('#result').css({'display':'none'});
            //mymap(lon, lat);
        });

        // Popup controller by keyboard
        var index = 0;
        $('body').on('keyup', '#address', function(event) {
            event.preventDefault();
            var length = $('#directorist.atbd_wrapper #result ul li a').length;
            if(event.keyCode === 40) {
                index++;
               if( index > length) {
                   index = 0;
                }               
            } else if(event.keyCode === 38) {
                index--;
                if(index < 0) {
                    index = length
                };
            }
            
            if($('#directorist.atbd_wrapper #result ul li a').length > 0){

                $('#directorist.atbd_wrapper #result ul li a').removeClass('active')
                $($('#directorist.atbd_wrapper #result ul li a')[index]).addClass('active');
                if(event.keyCode === 13){                      
                    $($('#directorist.atbd_wrapper #result ul li a')[index]).click();
                    event.preventDefault();
                    return false;
                }
            };
            
        });

        $('#post').on('submit', function(event) {
            event.preventDefault();
            return false;
        });
        // Popup controller by keyboard



        <?php if(!empty($address)) {?>
        $('#OL_Icon_33').append('<div class="mapHover"><?php echo !empty($address) ? esc_attr($address) : ''; ?></div>');
        <?php
        } // address
            } // select map
        }  //disable map
        ?>

    }); // ends jquery ready function.
</script>
<style>
    #OL_Icon_33{
        position: relative;
    }
    .mapHover {
        position: absolute;
        background: #fff;
        padding: 5px;
        width: 150px;
        border-radius: 3px;
        border: 1px solid #ddd;
        display: none;
    }
    #OL_Icon_33:hover .mapHover{
        display: block;
    }

    #directorist.atbd_wrapper a {                
        display: block;
        background: #fff;
        padding: 8px 10px;
    }

    #directorist.atbd_wrapper a:hover {
        background: #eeeeee50;        
    }

    #directorist.atbd_wrapper a.active {
        background: #eeeeee70;        
    }

    .g_address_wrap ul li {
        margin-bottom: 0px;
        border-bottom: 1px solid #eee;
        padding-bottom: 0px;
    }

</style>
