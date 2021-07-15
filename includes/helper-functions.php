<?php
// Prohibit direct script loading.
defined('ABSPATH') || die('No direct script access allowed!');

if ( ! function_exists( 'directorist_get_listings_directory_type' ) ) {
    function directorist_get_listings_directory_type( $listing_id = '' ) {
        $directory_type = wp_get_post_terms( $listing_id, ATBDP_DIRECTORY_TYPE );
        if ( empty( $directory_type ) || ! is_array( $directory_type ) ) return '';

        return $directory_type[0]->term_id;
    }
}

if ( ! function_exists( 'directorist_get_all_page_list' ) ) {
    function directorist_get_all_page_list( $listing_id = '' ) {
        $pages = get_pages();
        $pages_options = [
           ['value' => '', 'label' => 'Select...']
        ];

        if ( empty( $pages ) || ! is_array( $pages ) ) return $pages_options;

        foreach ($pages as $page) {
            $pages_options[] = array('value' => $page->ID, 'label' => $page->post_title);
        }

        return $pages_options;
    }
}

if ( ! function_exists( 'atbdp_is_truthy' ) ) {
    function atbdp_is_truthy( $data ) {

        if ( $data === true ||  $data === 'true'|| $data === 1 || $data === '1' ) {
            return true;
        }

        return false;
    }
}

if ( ! function_exists( 'e_var_dump' ) ) {
    function e_var_dump( $the_var = '' ) {
        echo '<pre>';
        print_r( $the_var );
        echo '</pre>';
    }
}

if ( ! function_exists( 'directorist_console_log' ) ) {
    function directorist_console_log( array $data = [] ) {
        $data = json_encode( $data ); ?>
        <script>
            var data = JSON.parse( '<?php echo $data ?>' );
            console.log( data );
        </script>
        <?php
    }
}

if ( ! function_exists( 'atbdp_redirect_after_login' ) ) {
    // atbdp_redirect_after_login
    function atbdp_redirect_after_login( array $args = [] ) {
        $default = [ 'url' => '' ];
        $args = array_merge( $default, $args );

        if ( empty( $args['url'] ) ) { return; }

        set_transient( 'atbdp_redirect_after_login', $args['url'] );
    }
}

if ( ! function_exists( 'atbdp_add_flush_message' ) ) {
    // atbdp_add_flush_message
    function atbdp_add_flush_message( array $args = [] ) {
        $default = [ 'key' => '', 'type' => 'info', 'message' => '' ];
        $args = array_merge( $default, $args );

        if ( empty( $args['key'] ) ) { return; }
        if ( empty( $args['message'] ) ) { return; }

        $get_previous_messages = get_transient( 'atbdp_flush_messages' );
        $flush_messages = $get_previous_messages;

        if ( empty( $flush_messages ) ) {
            $flush_messages = [];
        }

        $key = $args[ 'key' ];
        $flush_messages[ $key ] = $args;

        set_transient( 'atbdp_flush_messages', $flush_messages );
    }
}

if ( ! function_exists( 'atbdp_get_flush_messages' ) ) {
    // atbdp_get_flush_messages
    function atbdp_get_flush_messages( array $args = [] ) {
        $flush_messages = get_transient( 'atbdp_flush_messages' );
        if ( empty( $flush_messages  ) ) { return; }

        delete_transient( 'atbdp_flush_messages' );

        ob_start();

        echo '<div class="atbdp-flush-message-container">';
        foreach ( $flush_messages as $message_key => $messages ) { ?>
            <div class="atbdp-flush-message-item type-<?php echo $messages['type'] ?>">
                <?php echo $messages['message'] ?>
            </div>
        <?php }
        echo '</div>';

        $contents = apply_filters( 'atbdp_flush_message_content', ob_get_clean(), $flush_messages );
        echo $contents;
    }
}

if ( ! function_exists( 'atbdp_auth_guard' ) ) {
    function atbdp_auth_guard( array $args = [] ) {
        $flush_message = [
            'key'     => 'logged_in_user_only',
            'type'    => 'info',
            'message' => __( 'You need to be logged in to view the content of this page', 'directorist' ),
        ];

        $default = [ 'flush_message' => $flush_message ];
        $args = array_merge( $default, $args );

        global $wp;

        $current_page  = home_url( $wp->request );
        $login_page_id = get_directorist_option( 'user_login' );
        $login_page    = ( ! empty( $login_page_id ) ) ? get_page_link( $login_page_id ) : '';
        $home_page     = home_url();
        $redirect_link = ( ! empty( $login_page ) ) ? $login_page : $home_page;

        atbdp_add_flush_message( $args['flush_message'] );

        atbdp_redirect_after_login( [ 'url' => $current_page ] );
        wp_redirect( $redirect_link );

        die;
    }
}

function atbdp_add_flush_alert( array $args = [] ) {
    $default = [
        'id'          => '',
        'type'        => 'success',
        'page'        => '',
        'dismissible' => true,
        'message'     => '',
    ];

    $args = array_merge( $default, $args );

    if ( empty( $args['id'] ) ) { return; }

    $id = $args['id'];
    unset( $args['id'] );

    $fulsh_messages = get_transient( 'atbdp_flush_alerts' );

    if ( ! $fulsh_messages ) {
        $fulsh_messages = [];
    }

    $fulsh_messages[ $id ] = $args;

    set_transient( 'atbdp_flush_alerts', $fulsh_messages );
}

// atbdp_show_the_flush_alert
function atbdp_show_the_flush_alert( string $id = '' ) {
    if ( ! empty( $id ) ) { return; }

    $fulsh_alerts = get_transient( 'atbdp_flush_alerts' );
    if ( ! $fulsh_alerts && empty( $fulsh_alerts[ $id ] ) ) { return; }

    $fulsh_alert = $fulsh_alerts[ $id ];

    atbdp_render_the_flush_alert( $fulsh_alert );
    unset( $fulsh_alerts[ $id ] );

    set_transient( 'atbdp_flush_alerts', $fulsh_alerts );
}

// atbdp_show_flush_alerts
function atbdp_show_flush_alerts( array $args = [] ) {
    $default = [ 'page' => '' ];
    $args = array_merge( $default, $args );

    $fulsh_alerts = get_transient( 'atbdp_flush_alerts' );

    if ( $fulsh_alerts ) {
        foreach ( $fulsh_alerts as $id => $alert ) {
            $alert_page = ! empty( $alert['page'] ) ? $alert['page'] : '';

            if ( ! empty( $args['page'] ) && ( $alert_page !== $args['page'] && 'global' !== $alert_page ) ) {
                continue;
            }

            atbdp_render_the_flush_alert( $alert );
            unset( $fulsh_alerts[ $id ] );
        }
    }

    set_transient( 'atbdp_flush_alerts', $fulsh_alerts );
}

// atbdp_render_the_flush_alert
function atbdp_render_the_flush_alert( array $alert = [] ) {
    $classes = 'notice';
    $classes .= ( ! empty( $alert['type'] ) ) ? ' notice-' .  $alert['type'] : '';
    $classes .= ( empty( $alert['dismissible'] ) ) ? '' : ' is-dismissible';
    ?>
    <div class="<?php echo $classes; ?>">
        <p><strong><?php echo $alert['message'] ?></strong></p>
    </div>
    <?php
}


// atbdp_load_admin_template
function atbdp_load_admin_template( string $path = '', $data = [] ) {
    $file = trailingslashit( ATBDP_VIEWS_DIR ) . "admin-templates/$path.php";

    if ( file_exists( $file ) ) {
        include( $file );
    }
}

if ( !function_exists('get_help') ) {
    function get_help() {
        $path = ATBDP_CLASS_DIR . 'class-helper.php';
        if ( file_exists( $path ) ) {
            require_once( $path );
        }

        if ( class_exists( 'ATBDP_Helper' ) ) {
            $helper = new ATBDP_Helper;
            return $helper;
        }

        return null;
    }
}

if ( ! function_exists( 'atbdp_polylang_is_active' ) ) :
    function atbdp_required_polylang_url() {
        if ( class_exists('Polylang') ) {
            $pll_current_language = pll_current_language();
            $pll_default_language = pll_default_language();

            if ( $pll_current_language !== $pll_default_language ) {
                return true;
            }
        }

        return false;
    }
endif;


if ( ! function_exists( 'atbdp_get_listing_order' ) ) :
    // atbdp_get_listing_order
    function atbdp_get_listing_order( $listing_id ) {
        $order = new WP_Query([
            'post_type' => 'atbdp_orders',
            'meta_query' => array(
                array(
                    'key' => '_listing_id',
                    'value' => $listing_id,
                    'compare' => '=',
                )
            ),
            'per_page' => 1

        ]);

        return $order->post;
    }
endif;

if ( ! function_exists( 'atbdp_get_listing_status_after_submission' ) ) :
// atbdp_get_listing_status_after_submission
function atbdp_get_listing_status_after_submission( array $args = [] ) {
    $default = ['id' => '', 'edited' => true];
    $args = array_merge( $default, $args );

    $args['edited'] = ( true === $args['edited'] || '1' === $args['edited'] || 'yes' === $args['edited'] ) ? true : false;
    $listing_id = $args['id'];

    $new_l_status   = $args['new_l_status'];
    $edit_l_status  = $args['edit_l_status'];
    $edited         = $args['edited'];
    $listing_status = ( true === $edited || 'yes' === $edited || '1' === $edited ) ? $edit_l_status : $new_l_status;

    $monitization          = get_directorist_option('enable_monetization', 0);
    $featured_enabled      = get_directorist_option('enable_featured_listing');
    $pricing_plans_enabled = is_fee_manager_active();

    $post_status =  $listing_status;

    // If Pricing Plans are Enabled
    if ( $monitization && $pricing_plans_enabled ) {
        $plan_id   = get_post_meta($listing_id, '_fm_plans', true);
        $plan_meta = get_post_meta($plan_id);
        $plan_type = ( ! empty( $plan_meta['plan_type'] ) && ! empty( $plan_meta['plan_type'][0] ) ) ? $plan_meta['plan_type'][0] : '';
        // $plan_type = $plan_meta['plan_type'][0];

        $_listing_id    = ( 'pay_per_listng' === $plan_type ) ? $listing_id : false;
        $plan_purchased = subscribed_package_or_PPL_plans(get_current_user_id(), 'completed', $plan_id, $_listing_id);

        $post_status = ( ! $plan_purchased ) ? 'pending' : $listing_status;
    }

    // If Featured Listing is Enabled
    if ( $monitization && ! $pricing_plans_enabled && $featured_enabled ) {
        $has_order      = atbdp_get_listing_order( $listing_id );
        $payment_status = ( $has_order ) ? get_post_meta( $has_order->ID, '_payment_status', true) : null;

        $post_status = ( $has_order && 'completed' !== $payment_status ) ? 'pending' : $listing_status;
    }

    return $post_status;
}
endif;


if (!function_exists('load_dependencies')):
    /**
     * It loads files from a given directory using require_once.
     * @param string|array $files list of the names of file or a single file name to be loaded. Default: all
     * @param string $directory the location of the files
     * @param string $ext the ext of the files to be loaded
     * @return resource|bool it requires all the files in a given directory
     */
    function load_dependencies($files = 'all', $directory = ATBDP_CLASS_DIR, $ext = '.php')
    {
        if (!file_exists($directory)) return; // vail if the directory does not exist

        switch ($files) {
            case is_array($files) && 'all' !== strtolower($files[0]):
                // include one or more file looping through the $files array
                load_some_file($files, $directory);
                break;
            case !is_array($files) && 'all' !== $files:
                //load a single file here
                (file_exists($directory . $files . $ext)) ? require_once $directory . $files . $ext : null;
                break;
            case 'all' == $files || 'all' == strtolower($files[0]):
                // load all php file here
                load_all_files($directory);
                break;
        }

        return false;

    }
endif;


if (!function_exists('load_all_files')):
    /**
     * It loads all files that has the extension named $ext from the $dir
     * @param string $dir Name of the directory
     * @param string $ext Name of the extension of the files to be loaded
     */
    function load_all_files($dir = '', $ext = '.php')
    {
        if (!file_exists($dir)) return;
        foreach (scandir($dir) as $file) {
            // require once all the files with the given ext. eg. .php
            if (preg_match("/{$ext}$/i", $file)) {
                require_once($dir . $file);
            }
        }
    }
endif;


if (!function_exists('load_some_file')):

    /**
     * It loads one or more files but not all files that has the $ext from the $dir
     * @param string|array $files the array of files that should be loaded
     * @param string $dir Name of the directory
     * @param string $ext Name of the extension of the files to be loaded
     */
    function load_some_file($files = array(), $dir = '', $ext = '.php')
    {
        if (!file_exists($dir)) return; // vail if directory does not exist

        if (is_array($files)) {  // if the given files is an array then
            $files_to_loads = array_map(function ($i) use ($ext) {
                return $i . $ext;
            }, $files);// add '.php' to the end of all files
            $found_files = scandir($dir); // get the list of all the files in the given $dir
            foreach ($files_to_loads as $file_to_load) {
                in_array($file_to_load, $found_files) ? require_once $dir . $file_to_load : null;
            }
        }

    }
endif;


if (!function_exists('attc_letter_to_number')):

    /**
     * Calculate the column index (number) of a column header string (example: A is 1, AA is 27, ...).
     *
     * For the opposite, @param string $column Column string.
     * @return int $number Column number, 1-based.
     * @see number_to_letter().
     *
     * @since 1.0.0
     *
     */
    function attc_letter_to_number($column)
    {
        $column = strtoupper($column);
        $count = strlen($column);
        $number = 0;
        for ($i = 0; $i < $count; $i++) {
            $number += (ord($column[$count - 1 - $i]) - 64) * pow(26, $i);
        }
        return $number;
    }

endif;

if (!function_exists('attc_number_to_letter')):

    /**
     * "Calculate" the column header string of a column index (example: 2 is B, AB is 28, ...).
     *
     * For the opposite, @param int $number Column number, 1-based.
     * @return string $column Column string.
     * @see letter_to_number().
     *
     * @since 1.0.0
     *
     */
    function attc_number_to_letter($number)
    {
        $column = '';
        while ($number > 0) {
            $column = chr(65 + (($number - 1) % 26)) . $column;
            $number = floor(($number - 1) / 26);
        }
        return $column;
    }
endif;

if (!function_exists('atbdp_v_d')):

    /**
     * It dumps data to the screen in a div that has margin left 200px.
     * It is good for dumping data in WordPress dashboard
     */
    function atbdp_v_d($a = null)
    {
        echo "<pre>";
        var_dump($a);
        echo "</pre>";
    }
endif;

if (!function_exists('list_file_name')):
    /**
     * It returns a list of names of all files which are not hidden files
     * @param string $path
     * @return array
     */
    function list_file_name($path = __DIR__)
    {
        $file_names = array();
        foreach (new DirectoryIterator($path) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            $file_names[] = $fileInfo->getFilename();
        }
        return $file_names;
    }

endif;

if (!function_exists('list_file_path')):
    /**
     * It returns a list of path of all files which are not hidden files
     * @param string $path
     * @return array
     */
    function list_file_path($path = __DIR__)
    {
        $file_paths = array();
        foreach (new DirectoryIterator($path) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            $file_paths[] = $fileInfo->getRealPath();
        }
        return $file_paths;
    }

endif;

if (!function_exists('beautiful_datetime')):
    /**
     * It display a nice date and time
     * @param $datetime
     * @param string $type
     * @param string $separator
     * @return string
     */
    function beautiful_datetime($datetime, $type = 'mysql', $separator = ' ')
    {
        if ('mysql' === $type) {
            return mysql2date(get_option('date_format'), $datetime) . $separator . mysql2date(get_option('time_format'), $datetime);
        } else {
            return date_i18n(get_option('date_format'), $datetime) . $separator . date_i18n(get_option('time_format'), $datetime);
        }
    }

endif;

if (!function_exists('aazztech_enc_serialize')) {
    /**
     * It will serialize and then encode the string and return the encoded data
     * @param $data
     * @return string
     */
    function aazztech_enc_serialize($data)
    {
        return (!empty($data)) ? base64_encode(serialize($data)) : null;
    }
}

if (!function_exists('aazztech_enc_unserialize')) {
    /**
     * It will decode the data and then unserialize the data and return it
     * @param string $data Encoded strings that should be decoded and then unserialize
     * @return mixed
     */
    function aazztech_enc_unserialize($data)
    {
        return (!empty($data)) ? unserialize(base64_decode($data)) : null;
    }
}


if (!function_exists('atbd_get_related_posts')) {
    // get related post based on tags or categories
    function atbd_get_related_posts()
    {
        global $post;
        // get all tags assigned to current post
        $tags = wp_get_post_tags($post->ID);
        $args = array();
        // set args to get related posts based on tags
        if (!empty($tags)) {
            $tag_ids = array();
            foreach ($tags as $tag) $tag_ids[] = $tag->term_id;
            $args = array(
                'tag__in' => $tag_ids,
                'post__not_in' => array($post->ID),
                'ignore_sticky_posts' => true,
                'posts_per_page' => 5,
                'orderby' => 'rand',
            );
        } else {
            // get all cats assigned to current post
            $cats = get_the_category($post->ID);
            // set the args to get all related posts based on category.
            if ($cats) {
                $cat_ids = array();
                foreach ($cats as $cat) $cat_ids[] = $cat->term_id;
                $args = array(
                    'category__in' => $cat_ids,
                    'post__not_in' => array($post->ID),
                    'ignore_sticky_posts' => true,
                    'posts_per_page' => 5,
                    'orderby' => 'rand',
                );
            }
        }
        if (!empty($args)) {
            // build the markup and return
            return new WP_Query($args);

        }
        return null;
    }
}

if (!function_exists('atbdp_get_option')) {

    /**
     * It retrieves an option from the database if it exists and returns false if it is not exist.
     * It is a custom function to get the data of custom setting page
     * @param string $name The name of the option we would like to get. Eg. map_api_key
     * @param string $group The name of the group where the option is saved. eg. general_settings
     * @param mixed $default Default value for the option key if the option does not have value then default will be returned
     * @return mixed    It returns the value of the $name option if it exists in the option $group in the database, false otherwise.
     */
    function atbdp_get_option($name, $group, $default = false)
    {
        // at first get the group of options from the database.
        // then check if the data exists in the array and if it exists then return it
        // if not, then return false
        if (empty($name) || empty($group)) {
            if (!empty($default)) return $default;
            return false;
        } // vail if either $name or option $group is empty
        $options_array = (array)get_option($group);
        if (array_key_exists($name, $options_array)) {
            return $options_array[$name];
        } else {
            if (!empty($default)) return $default;
            return false;
        }
    }
}

function get_directorist_type_option( $type, $name, $default='' ) {
    $meta = get_term_meta( $type, $name, true );
    $result = $meta != '' ? $meta : $default;
    return $result;
}

if (!function_exists('get_directorist_option')) {

    /**
     * It retrieves an option from the database if it exists and returns false if it is not exist.
     * It is a custom function to get the data of custom setting page
     * @param string $name The name of the option we would like to get. Eg. map_api_key
     * @param mixed $default Default value for the option key if the option does not have value then default will be returned
     * @param bool $force_default Whether to use default value when database return anything other than NULL such as '', false etc
     * @return mixed    It returns the value of the $name option if it exists in the option $group in the database, false otherwise.
     */
    function get_directorist_option($name, $default = false, $force_default = false)
    {
        // at first get the group of options from the database.
        // then check if the data exists in the array and if it exists then return it
        // if not, then return false
        if (empty($name)) {
            return $default;
        }
        // get the option from the database and return it if it is not a null value. Otherwise, return the default value
        $options = (array)get_option('atbdp_option');
        $v = (array_key_exists($name, $options))
            ? $v = $options[sanitize_key($name)]
            : null;

        $newvalue = apply_filters( 'directorist_option', $v, $name );

        if ( $newvalue != $v ) {
           return $newvalue;
        }

        // use default only when the value of the $v is NULL
        if (is_null($v)) {
            return $default;
        }
        if ($force_default) {
            // use the default value even if the value of $v is falsy value returned from the database
            if (empty($v)) {
                return $default;
            }
        }
        return (isset($v)) ? $v : $default; // return the data if it is anything but NULL.
    }
}

if ( ! function_exists( 'update_directorist_option' ) ) {
    function update_directorist_option( $key = '', $value = '' ) {
        $options = ( array ) get_option( 'atbdp_option', [] );
        $options[ $key ] = $value;

        update_option( 'atbdp_option', $options );
    }
}


if (!function_exists('atbdp_yes_to_bool')) {
    function atbdp_yes_to_bool($v = false)
    {
        if (empty($v)) return false;
        return ('yes' == trim($v)) ? true : false;
    }
}


if (!function_exists('atbdp_pagination')) {
    /**
     * Prints pagination for custom post
     * @param object|WP_Query $custom_post_query
     * @param int $paged
     *
     * @return string
     */
    function atbdp_pagination($custom_post_query, $paged = 1)
    {
        $navigation = '';
        $largeNumber = 999999999; // we need a large number here

        $total = ( isset( $custom_post_query->total_pages ) ) ? $custom_post_query->total_pages : $custom_post_query->max_num_pages;
        $paged = ( isset( $custom_post_query->current_page ) ) ? $custom_post_query->current_page : $paged;

        $links = paginate_links(array(
            'base'      => str_replace($largeNumber, '%#%', esc_url(get_pagenum_link($largeNumber))),
            'format'    => '?paged=%#%',
            'current'   => max(1, $paged),
            'total'     => $total,
            'prev_text' => apply_filters('atbdp_pagination_prev_text', '<span class="fa fa-chevron-left"></span>'),
            'next_text' => apply_filters('atbdp_pagination_next_text', '<span class="fa fa-chevron-right atbdp_right_nav"></span>'),
        ));

        if ($links) {
            $navigation = _navigation_markup($links, 'pagination', ' ');
        }

        return apply_filters('atbdp_pagination', $navigation, $links, $custom_post_query, $paged);
    }
}

if(!function_exists('get_recent_reviews')) {
    function get_recent_reviews( $number = 5 ){
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}atbdp_review
            ORDER BY id DESC
            LIMIT %d",
            $number
        );

        $items = $wpdb->get_results( $sql );

        return $items;
    }
 }

if(!function_exists('get_review_by_ids')) {
    function get_review_by_ids( $review_ids = [], $number = 5 ){
        global $wpdb;
        $ids =  implode(",",$review_ids);
        $sql = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}atbdp_review
            WHERE id IN ({$ids})
            LIMIT %d",
            $number
        );

        $items = $wpdb->get_results( $sql );

        return $items;
    }
 }

if ( ! function_exists('get_fa_icons') ) {
    function get_fa_icons()
    {
        $font_type = get_directorist_option('font_type', 'line');
        $iconsLA = array (
            'las la-american-sign-language-interpreting',
            'las la-assistive-listening-systems',
            'las la-audio-description',
            'las la-blind',
            'las la-braille',
            'las la-closed-captioning',
            'las la-deaf',
            'las la-low-vision',
            'las la-phone-volume',
            'las la-question-circle',
            'las la-sign-language',
            'las la-tty',
            'las la-universal-access',
            'las la-wheelchair',
            'las la-bell',
            'las la-bell-slash',
            'las la-exclamation',
            'las la-exclamation-circle',
            'las la-exclamation-triangle',
            'las la-radiation',
            'las la-radiation-alt',
            'las la-skull-crossbones',
            'las la-cat',
            'las la-crow',
            'las la-dog',
            'las la-dove',
            'las la-dragon',
            'las la-feather',
            'las la-feather-alt',
            'las la-fish',
            'las la-frog',
            'las la-hippo',
            'las la-horse',
            'las la-horse-head',
            'las la-kiwi-bird',
            'las la-otter',
            'las la-paw',
            'las la-spider',
            'las la-angle-double-down',
            'las la-angle-double-left',
            'las la-angle-double-right',
            'las la-angle-double-up',
            'las la-angle-down',
            'las la-angle-left',
            'las la-angle-right',
            'las la-angle-up',
            'las la-arrow-alt-circle-down',
            'las la-arrow-alt-circle-left',
            'las la-arrow-alt-circle-right',
            'las la-arrow-alt-circle-up',
            'las la-arrow-circle-down',
            'las la-arrow-circle-left',
            'las la-arrow-circle-right',
            'las la-arrow-circle-up',
            'las la-arrow-down',
            'las la-arrow-left',
            'las la-arrow-right',
            'las la-arrow-up',
            'las la-arrows-alt',
            'las la-arrows-alt-h',
            'las la-arrows-alt-v',
            'las la-caret-down',
            'las la-caret-left',
            'las la-caret-right',
            'las la-caret-square-down',
            'las la-caret-square-left',
            'las la-caret-square-right',
            'las la-caret-square-up',
            'las la-caret-up',
            'las la-cart-arrow-down',
            'las la-chart-line',
            'las la-chevron-circle-down',
            'las la-chevron-circle-left',
            'las la-chevron-circle-right',
            'las la-chevron-circle-up',
            'las la-chevron-down',
            'las la-chevron-left',
            'las la-chevron-right',
            'las la-chevron-up',
            'las la-cloud-download-alt',
            'las la-cloud-upload-alt',
            'las la-compress-arrows-alt',
            'las la-download',
            'las la-exchange-alt',
            'las la-expand-arrows-alt',
            'las la-external-link-alt',
            'las la-external-link-square-alt',
            'las la-hand-point-down',
            'las la-hand-point-left',
            'las la-hand-point-right',
            'las la-hand-point-up',
            'las la-hand-pointer',
            'las la-history',
            'las la-level-down-alt',
            'las la-level-up-alt',
            'las la-location-arrow',
            'las la-long-arrow-alt-down',
            'las la-long-arrow-alt-left',
            'las la-long-arrow-alt-right',
            'las la-long-arrow-alt-up',
            'las la-mouse-pointer',
            'las la-play',
            'las la-random',
            'las la-recycle',
            'las la-redo',
            'las la-redo-alt',
            'las la-reply',
            'las la-reply-all',
            'las la-retweet',
            'las la-share',
            'las la-share-square',
            'las la-sign-in-alt',
            'las la-sign-out-alt',
            'las la-sort',
            'las la-sort-alpha-down',
            'las la-sort-alpha-down-alt',
            'las la-sort-alpha-up',
            'las la-sort-alpha-up-alt',
            'las la-sort-amount-down',
            'las la-sort-amount-down-alt',
            'las la-sort-amount-up',
            'las la-sort-amount-up-alt',
            'las la-sort-down',
            'las la-sort-numeric-down',
            'las la-sort-numeric-down-alt',
            'las la-sort-numeric-up',
            'las la-sort-numeric-up-alt',
            'las la-sort-up',
            'las la-sync',
            'las la-sync-alt',
            'las la-text-height',
            'las la-text-width',
            'las la-undo',
            'las la-undo-alt',
            'las la-upload',
            'las la-audio-description',
            'las la-backward',
            'las la-broadcast-tower',
            'las la-circle',
            'las la-closed-captioning',
            'las la-compress',
            'las la-compress-arrows-alt',
            'las la-eject',
            'las la-expand',
            'las la-expand-arrows-alt',
            'las la-fast-backward',
            'las la-fast-forward',
            'las la-file-audio',
            'las la-file-video',
            'las la-film',
            'las la-forward',
            'las la-headphones',
            'las la-microphone',
            'las la-microphone-alt',
            'las la-microphone-alt-slash',
            'las la-microphone-slash',
            'las la-music',
            'las la-pause',
            'las la-pause-circle',
            'las la-phone-volume',
            'las la-photo-video',
            'las la-play',
            'las la-play-circle',
            'las la-podcast',
            'las la-random',
            'las la-redo',
            'las la-redo-alt',
            'las la-rss',
            'las la-rss-square',
            'las la-step-backward',
            'las la-step-forward',
            'las la-stop',
            'las la-stop-circle',
            'las la-sync',
            'las la-sync-alt',
            'las la-tv',
            'las la-undo',
            'las la-undo-alt',
            'las la-video',
            'las la-volume-down',
            'las la-volume-mute',
            'las la-volume-off',
            'las la-volume-up',
            'las la-youtube',
            'las la-air-freshener',
            'las la-ambulance',
            'las la-bus',
            'las la-bus-alt',
            'las la-car',
            'las la-car-alt',
            'las la-car-battery',
            'las la-car-crash',
            'las la-car-side',
            'las la-charging-station',
            'las la-gas-pump',
            'las la-motorcycle',
            'las la-oil-can',
            'las la-shuttle-van',
            'las la-tachometer-alt',
            'las la-taxi',
            'las la-truck',
            'las la-truck-monster',
            'las la-truck-pickup',
            'las la-apple-alt',
            'las la-campground',
            'las la-cloud-sun',
            'las la-drumstick-bite',
            'las la-football-ball',
            'las la-hiking',
            'las la-mountain',
            'las la-tractor',
            'las la-tree',
            'las la-wind',
            'las la-wine-bottle',
            'las la-beer',
            'las la-blender',
            'las la-cocktail',
            'las la-coffee',
            'las la-flask',
            'las la-glass-cheers',
            'las la-glass-martini',
            'las la-glass-martini-alt',
            'las la-glass-whiskey',
            'las la-mug-hot',
            'las la-wine-bottle',
            'las la-wine-glass',
            'las la-wine-glass-alt',
            'las la-archway',
            'las la-building',
            'las la-campground',
            'las la-church',
            'las la-city',
            'las la-clinic-medical',
            'las la-dungeon',
            'las la-gopuram',
            'las la-home',
            'las la-hospital',
            'las la-hospital-alt',
            'las la-hotel',
            'las la-house-damage',
            'las la-igloo',
            'las la-industry',
            'las la-kaaba',
            'las la-landmark',
            'las la-monument',
            'las la-mosque',
            'las la-place-of-worship',
            'las la-school',
            'las la-store',
            'las la-store-alt',
            'las la-synagogue',
            'las la-torii-gate',
            'las la-university',
            'las la-vihara',
            'las la-warehouse',
            'las la-address-book',
            'las la-address-card',
            'las la-archive',
            'las la-balance-scale',
            'las la-balance-scale-left',
            'las la-balance-scale-right',
            'las la-birthday-cake',
            'las la-book',
            'las la-briefcase',
            'las la-building',
            'las la-bullhorn',
            'las la-bullseye',
            'las la-business-time',
            'las la-calculator',
            'las la-calendar',
            'las la-calendar-alt',
            'las la-certificate',
            'las la-chart-area',
            'las la-chart-bar',
            'las la-chart-line',
            'las la-chart-pie',
            'las la-city',
            'las la-clipboard',
            'las la-coffee',
            'las la-columns',
            'las la-compass',
            'las la-copy',
            'las la-copyright',
            'las la-cut',
            'las la-edit',
            'las la-envelope',
            'las la-envelope-open',
            'las la-envelope-square',
            'las la-eraser',
            'las la-fax',
            'las la-file',
            'las la-file-alt',
            'las la-folder',
            'las la-folder-minus',
            'las la-folder-open',
            'las la-folder-plus',
            'las la-glasses',
            'las la-globe',
            'las la-highlighter',
            'las la-industry',
            'las la-landmark',
            'las la-marker',
            'las la-paperclip',
            'las la-paste',
            'las la-pen',
            'las la-pen-alt',
            'las la-pen-fancy',
            'las la-pen-nib',
            'las la-pen-square',
            'las la-pencil-alt',
            'las la-percent',
            'las la-phone',
            'las la-phone-alt',
            'las la-phone-slash',
            'las la-phone-square',
            'las la-phone-square-alt',
            'las la-phone-volume',
            'las la-print',
            'las la-project-diagram',
            'las la-registered',
            'las la-save',
            'las la-sitemap',
            'las la-socks',
            'las la-sticky-note',
            'las la-stream',
            'las la-table',
            'las la-tag',
            'las la-tags',
            'las la-tasks',
            'las la-thumbtack',
            'las la-trademark',
            'las la-wallet',
            'las la-binoculars',
            'las la-campground',
            'las la-compass',
            'las la-fire',
            'las la-fire-alt',
            'las la-first-aid',
            'las la-frog',
            'las la-hiking',
            'las la-map',
            'las la-map-marked',
            'las la-map-marked-alt',
            'las la-map-signs',
            'las la-mountain',
            'las la-route',
            'las la-toilet-paper',
            'las la-tree',
            'las la-dollar-sign',
            'las la-donate',
            'las la-dove',
            'las la-gift',
            'las la-globe',
            'las la-hand-holding-heart',
            'las la-hand-holding-usd',
            'las la-hands-helping',
            'las la-handshake',
            'las la-heart',
            'las la-leaf',
            'las la-parachute-box',
            'las la-piggy-bank',
            'las la-ribbon',
            'las la-seedling',
            'las la-comment',
            'las la-comment-alt',
            'las la-comment-dots',
            'las la-comment-medical',
            'las la-comment-slash',
            'las la-comments',
            'las la-frown',
            'las la-icons',
            'las la-meh',
            'las la-phone',
            'las la-phone-alt',
            'las la-phone-slash',
            'las la-poo',
            'las la-quote-left',
            'las la-quote-right',
            'las la-smile',
            'las la-sms',
            'las la-video',
            'las la-video-slash',
            'las la-chess',
            'las la-chess-bishop',
            'las la-chess-board',
            'las la-chess-king',
            'las la-chess-knight',
            'las la-chess-pawn',
            'las la-chess-queen',
            'las la-chess-rook',
            'las la-square-full',
            'las la-apple-alt',
            'las la-baby',
            'las la-baby-carriage',
            'las la-bath',
            'las la-biking',
            'las la-birthday-cake',
            'las la-cookie',
            'las la-cookie-bite',
            'las la-gamepad',
            'las la-ice-cream',
            'las la-mitten',
            'las la-robot',
            'las la-school',
            'las la-shapes',
            'las la-snowman',
            'las la-graduation-cap',
            'las la-hat-cowboy',
            'las la-hat-cowboy-side',
            'las la-hat-wizard',
            'las la-mitten',
            'las la-shoe-prints',
            'las la-socks',
            'las la-tshirt',
            'las la-user-tie',
            'las la-archive',
            'las la-barcode',
            'las la-bath',
            'las la-bug',
            'las la-code',
            'las la-code-branch',
            'las la-coffee',
            'las la-file',
            'las la-file-alt',
            'las la-file-code',
            'las la-filter',
            'las la-fire-extinguisher',
            'las la-folder',
            'las la-folder-open',
            'las la-keyboard',
            'las la-laptop-code',
            'las la-microchip',
            'las la-project-diagram',
            'las la-qrcode',
            'las la-shield-alt',
            'las la-sitemap',
            'las la-stream',
            'las la-terminal',
            'las la-user-secret',
            'las la-window-close',
            'las la-window-maximize',
            'las la-window-minimize',
            'las la-window-restore',
            'las la-address-book',
            'las la-address-card',
            'las la-american-sign-language-interpreting',
            'las la-assistive-listening-systems',
            'las la-at',
            'las la-bell',
            'las la-bell-slash',
            'las la-bluetooth',
            'las la-bluetooth-b',
            'las la-broadcast-tower',
            'las la-bullhorn',
            'las la-chalkboard',
            'las la-comment',
            'las la-comment-alt',
            'las la-comments',
            'las la-envelope',
            'las la-envelope-open',
            'las la-envelope-square',
            'las la-fax',
            'las la-inbox',
            'las la-language',
            'las la-microphone',
            'las la-microphone-alt',
            'las la-microphone-alt-slash',
            'las la-microphone-slash',
            'las la-mobile',
            'las la-mobile-alt',
            'las la-paper-plane',
            'las la-phone',
            'las la-phone-alt',
            'las la-phone-slash',
            'las la-phone-square',
            'las la-phone-square-alt',
            'las la-phone-volume',
            'las la-rss',
            'las la-rss-square',
            'las la-tty',
            'las la-voicemail',
            'las la-wifi',
            'las la-database',
            'las la-desktop',
            'las la-download',
            'las la-ethernet',
            'las la-hdd',
            'las la-headphones',
            'las la-keyboard',
            'las la-laptop',
            'las la-memory',
            'las la-microchip',
            'las la-mobile',
            'las la-mobile-alt',
            'las la-mouse',
            'las la-plug',
            'las la-power-off',
            'las la-print',
            'las la-satellite',
            'las la-satellite-dish',
            'las la-save',
            'las la-sd-card',
            'las la-server',
            'las la-sim-card',
            'las la-stream',
            'las la-tablet',
            'las la-tablet-alt',
            'las la-tv',
            'las la-upload',
            'las la-brush',
            'las la-drafting-compass',
            'las la-dumpster',
            'las la-hammer',
            'las la-hard-hat',
            'las la-paint-roller',
            'las la-pencil-alt',
            'las la-pencil-ruler',
            'las la-ruler',
            'las la-ruler-combined',
            'las la-ruler-horizontal',
            'las la-ruler-vertical',
            'las la-screwdriver',
            'las la-toolbox',
            'las la-tools',
            'las la-truck-pickup',
            'las la-wrench',
            'las la-bitcoin',
            'las la-btc',
            'las la-dollar-sign',
            'las la-ethereum',
            'las la-euro-sign',
            'las la-gg',
            'las la-gg-circle',
            'las la-hryvnia',
            'las la-lira-sign',
            'las la-money-bill',
            'las la-money-bill-alt',
            'las la-money-bill-wave',
            'las la-money-bill-wave-alt',
            'las la-money-check',
            'las la-money-check-alt',
            'las la-pound-sign',
            'las la-ruble-sign',
            'las la-rupee-sign',
            'las la-shekel-sign',
            'las la-tenge',
            'las la-won-sign',
            'las la-yen-sign',
            'las la-bell',
            'las la-bell-slash',
            'las la-calendar',
            'las la-calendar-alt',
            'las la-calendar-check',
            'las la-calendar-minus',
            'las la-calendar-plus',
            'las la-calendar-times',
            'las la-clock',
            'las la-hourglass',
            'las la-hourglass-end',
            'las la-hourglass-half',
            'las la-hourglass-start',
            'las la-stopwatch',
            'las la-adjust',
            'las la-bezier-curve',
            'las la-brush',
            'las la-clone',
            'las la-copy',
            'las la-crop',
            'las la-crop-alt',
            'las la-crosshairs',
            'las la-cut',
            'las la-drafting-compass',
            'las la-draw-polygon',
            'las la-edit',
            'las la-eraser',
            'las la-eye',
            'las la-eye-dropper',
            'las la-eye-slash',
            'las la-fill',
            'las la-fill-drip',
            'las la-highlighter',
            'las la-icons',
            'las la-layer-group',
            'las la-magic',
            'las la-marker',
            'las la-object-group',
            'las la-object-ungroup',
            'las la-paint-brush',
            'las la-paint-roller',
            'las la-palette',
            'las la-paste',
            'las la-pen',
            'las la-pen-alt',
            'las la-pen-fancy',
            'las la-pen-nib',
            'las la-pencil-alt',
            'las la-pencil-ruler',
            'las la-ruler-combined',
            'las la-ruler-horizontal',
            'las la-ruler-vertical',
            'las la-save',
            'las la-splotch',
            'las la-spray-can',
            'las la-stamp',
            'las la-swatchbook',
            'las la-tint',
            'las la-tint-slash',
            'las la-vector-square',
            'las la-align-center',
            'las la-align-justify',
            'las la-align-left',
            'las la-align-right',
            'las la-bold',
            'las la-border-all',
            'las la-border-none',
            'las la-border-style',
            'las la-clipboard',
            'las la-clone',
            'las la-columns',
            'las la-copy',
            'las la-cut',
            'las la-edit',
            'las la-eraser',
            'las la-file',
            'las la-file-alt',
            'las la-font',
            'las la-glasses',
            'las la-heading',
            'las la-highlighter',
            'las la-i-cursor',
            'las la-icons',
            'las la-indent',
            'las la-italic',
            'las la-link',
            'las la-list',
            'las la-list-alt',
            'las la-list-ol',
            'las la-list-ul',
            'las la-marker',
            'las la-outdent',
            'las la-paper-plane',
            'las la-paperclip',
            'las la-paragraph',
            'las la-paste',
            'las la-pen',
            'las la-pen-alt',
            'las la-pen-fancy',
            'las la-pen-nib',
            'las la-pencil-alt',
            'las la-print',
            'las la-quote-left',
            'las la-quote-right',
            'las la-redo',
            'las la-redo-alt',
            'las la-remove-format',
            'las la-reply',
            'las la-reply-all',
            'las la-screwdriver',
            'las la-share',
            'las la-spell-check',
            'las la-strikethrough',
            'las la-subscript',
            'las la-superscript',
            'las la-sync',
            'las la-sync-alt',
            'las la-table',
            'las la-tasks',
            'las la-text-height',
            'las la-text-width',
            'las la-th',
            'las la-th-large',
            'las la-th-list',
            'las la-tools',
            'las la-trash',
            'las la-trash-alt',
            'las la-trash-restore',
            'las la-trash-restore-alt',
            'las la-underline',
            'las la-undo',
            'las la-undo-alt',
            'las la-unlink',
            'las la-wrench',
            'las la-apple-alt',
            'las la-atom',
            'las la-award',
            'las la-bell',
            'las la-bell-slash',
            'las la-book-open',
            'las la-book-reader',
            'las la-chalkboard',
            'las la-chalkboard-teacher',
            'las la-graduation-cap',
            'las la-laptop-code',
            'las la-microscope',
            'las la-music',
            'las la-school',
            'las la-shapes',
            'las la-theater-masks',
            'las la-user-graduate',
            'las la-angry',
            'las la-dizzy',
            'las la-flushed',
            'las la-frown',
            'las la-frown-open',
            'las la-grimace',
            'las la-grin',
            'las la-grin-alt',
            'las la-grin-beam',
            'las la-grin-beam-sweat',
            'las la-grin-hearts',
            'las la-grin-squint',
            'las la-grin-squint-tears',
            'las la-grin-stars',
            'las la-grin-tears',
            'las la-grin-tongue',
            'las la-grin-tongue-squint',
            'las la-grin-tongue-wink',
            'las la-grin-wink',
            'las la-kiss',
            'las la-kiss-beam',
            'las la-kiss-wink-heart',
            'las la-laugh',
            'las la-laugh-beam',
            'las la-laugh-squint',
            'las la-laugh-wink',
            'las la-meh',
            'las la-meh-blank',
            'las la-meh-rolling-eyes',
            'las la-sad-cry',
            'las la-sad-tear',
            'las la-smile',
            'las la-smile-beam',
            'las la-smile-wink',
            'las la-surprise',
            'las la-tired',
            'las la-atom',
            'las la-battery-empty',
            'las la-battery-full',
            'las la-battery-half',
            'las la-battery-quarter',
            'las la-battery-three-quarters',
            'las la-broadcast-tower',
            'las la-burn',
            'las la-charging-station',
            'las la-fire',
            'las la-fire-alt',
            'las la-gas-pump',
            'las la-industry',
            'las la-leaf',
            'las la-lightbulb',
            'las la-plug',
            'las la-poop',
            'las la-power-off',
            'las la-radiation',
            'las la-radiation-alt',
            'las la-seedling',
            'las la-solar-panel',
            'las la-sun',
            'las la-water',
            'las la-wind',
            'las la-archive',
            'las la-clone',
            'las la-copy',
            'las la-cut',
            'las la-file',
            'las la-file-alt',
            'las la-file-archive',
            'las la-file-audio',
            'las la-file-code',
            'las la-file-excel',
            'las la-file-image',
            'las la-file-pdf',
            'las la-file-powerpoint',
            'las la-file-video',
            'las la-file-word',
            'las la-folder',
            'las la-folder-open',
            'las la-paste',
            'las la-photo-video',
            'las la-save',
            'las la-sticky-note',
            'las la-balance-scale',
            'las la-balance-scale-left',
            'las la-balance-scale-right',
            'las la-book',
            'las la-cash-register',
            'las la-chart-line',
            'las la-chart-pie',
            'las la-coins',
            'las la-comment-dollar',
            'las la-comments-dollar',
            'las la-credit-card',
            'las la-donate',
            'las la-file-invoice',
            'las la-file-invoice-dollar',
            'las la-hand-holding-usd',
            'las la-landmark',
            'las la-money-bill',
            'las la-money-bill-alt',
            'las la-money-bill-wave',
            'las la-money-bill-wave-alt',
            'las la-money-check',
            'las la-money-check-alt',
            'las la-percentage',
            'las la-piggy-bank',
            'las la-receipt',
            'las la-stamp',
            'las la-wallet',
            'las la-bicycle',
            'las la-biking',
            'las la-burn',
            'las la-fire-alt',
            'las la-heart',
            'las la-heartbeat',
            'las la-hiking',
            'las la-running',
            'las la-shoe-prints',
            'las la-skating',
            'las la-skiing',
            'las la-skiing-nordic',
            'las la-snowboarding',
            'las la-spa',
            'las la-swimmer',
            'las la-walking',
            'las la-apple-alt',
            'las la-bacon',
            'las la-bone',
            'las la-bread-slice',
            'las la-candy-cane',
            'las la-carrot',
            'las la-cheese',
            'las la-cloud-meatball',
            'las la-cookie',
            'las la-drumstick-bite',
            'las la-egg',
            'las la-fish',
            'las la-hamburger',
            'las la-hotdog',
            'las la-ice-cream',
            'las la-lemon',
            'las la-pepper-hot',
            'las la-pizza-slice',
            'las la-seedling',
            'las la-stroopwafel',
            'las la-chess',
            'las la-chess-bishop',
            'las la-chess-board',
            'las la-chess-king',
            'las la-chess-knight',
            'las la-chess-pawn',
            'las la-chess-queen',
            'las la-chess-rook',
            'las la-dice',
            'las la-dice-d20',
            'las la-dice-d6',
            'las la-dice-five',
            'las la-dice-four',
            'las la-dice-one',
            'las la-dice-six',
            'las la-dice-three',
            'las la-dice-two',
            'las la-gamepad',
            'las la-ghost',
            'las la-headset',
            'las la-heart',
            'las la-playstation',
            'las la-puzzle-piece',
            'las la-steam',
            'las la-steam-square',
            'las la-steam-symbol',
            'las la-twitch',
            'las la-xbox',
            'las la-genderless',
            'las la-mars',
            'las la-mars-double',
            'las la-mars-stroke',
            'las la-mars-stroke-h',
            'las la-mars-stroke-v',
            'las la-mercury',
            'las la-neuter',
            'las la-transgender',
            'las la-transgender-alt',
            'las la-venus',
            'las la-venus-double',
            'las la-venus-mars',
            'las la-book-dead',
            'las la-broom',
            'las la-cat',
            'las la-cloud-moon',
            'las la-crow',
            'las la-ghost',
            'las la-hat-wizard',
            'las la-mask',
            'las la-skull-crossbones',
            'las la-spider',
            'las la-toilet-paper',
            'las la-allergies',
            'las la-fist-raised',
            'las la-hand-holding',
            'las la-hand-holding-heart',
            'las la-hand-holding-usd',
            'las la-hand-lizard',
            'las la-hand-middle-finger',
            'las la-hand-paper',
            'las la-hand-peace',
            'las la-hand-point-down',
            'las la-hand-point-left',
            'las la-hand-point-right',
            'las la-hand-point-up',
            'las la-hand-pointer',
            'las la-hand-rock',
            'las la-hand-scissors',
            'las la-hand-spock',
            'las la-hands',
            'las la-hands-helping',
            'las la-handshake',
            'las la-praying-hands',
            'las la-thumbs-down',
            'las la-thumbs-up',
            'las la-ambulance',
            'las la-h-square',
            'las la-heart',
            'las la-heartbeat',
            'las la-hospital',
            'las la-medkit',
            'las la-plus-square',
            'las la-prescription',
            'las la-stethoscope',
            'las la-user-md',
            'las la-wheelchair',
            'las la-candy-cane',
            'las la-carrot',
            'las la-cookie-bite',
            'las la-gift',
            'las la-gifts',
            'las la-glass-cheers',
            'las la-holly-berry',
            'las la-mug-hot',
            'las la-sleigh',
            'las la-snowman',
            'las la-baby-carriage',
            'las la-bath',
            'las la-bed',
            'las la-briefcase',
            'las la-car',
            'las la-cocktail',
            'las la-coffee',
            'las la-concierge-bell',
            'las la-dice',
            'las la-dice-five',
            'las la-door-closed',
            'las la-door-open',
            'las la-dumbbell',
            'las la-glass-martini',
            'las la-glass-martini-alt',
            'las la-hot-tub',
            'las la-hotel',
            'las la-infinity',
            'las la-key',
            'las la-luggage-cart',
            'las la-shower',
            'las la-shuttle-van',
            'las la-smoking',
            'las la-smoking-ban',
            'las la-snowflake',
            'las la-spa',
            'las la-suitcase',
            'las la-suitcase-rolling',
            'las la-swimmer',
            'las la-swimming-pool',
            'las la-tv',
            'las la-umbrella-beach',
            'las la-utensils',
            'las la-wheelchair',
            'las la-wifi',
            'las la-bath',
            'las la-bed',
            'las la-blender',
            'las la-chair',
            'las la-couch',
            'las la-door-closed',
            'las la-door-open',
            'las la-dungeon',
            'las la-fan',
            'las la-shower',
            'las la-toilet-paper',
            'las la-tv',
            'las la-adjust',
            'las la-bolt',
            'las la-camera',
            'las la-camera-retro',
            'las la-chalkboard',
            'las la-clone',
            'las la-compress',
            'las la-compress-arrows-alt',
            'las la-expand',
            'las la-eye',
            'las la-eye-dropper',
            'las la-eye-slash',
            'las la-file-image',
            'las la-film',
            'las la-id-badge',
            'las la-id-card',
            'las la-image',
            'las la-images',
            'las la-photo-video',
            'las la-portrait',
            'las la-sliders-h',
            'las la-tint',
            'las la-award',
            'las la-ban',
            'las la-barcode',
            'las la-bars',
            'las la-beer',
            'las la-bell',
            'las la-bell-slash',
            'las la-blog',
            'las la-bug',
            'las la-bullhorn',
            'las la-bullseye',
            'las la-calculator',
            'las la-calendar',
            'las la-calendar-alt',
            'las la-calendar-check',
            'las la-calendar-minus',
            'las la-calendar-plus',
            'las la-calendar-times',
            'las la-certificate',
            'las la-check',
            'las la-check-circle',
            'las la-check-double',
            'las la-check-square',
            'las la-circle',
            'las la-clipboard',
            'las la-clone',
            'las la-cloud',
            'las la-cloud-download-alt',
            'las la-cloud-upload-alt',
            'las la-coffee',
            'las la-cog',
            'las la-cogs',
            'las la-copy',
            'las la-cut',
            'las la-database',
            'las la-dot-circle',
            'las la-download',
            'las la-edit',
            'las la-ellipsis-h',
            'las la-ellipsis-v',
            'las la-envelope',
            'las la-envelope-open',
            'las la-eraser',
            'las la-exclamation',
            'las la-exclamation-circle',
            'las la-exclamation-triangle',
            'las la-external-link-alt',
            'las la-external-link-square-alt',
            'las la-eye',
            'las la-eye-slash',
            'las la-file',
            'las la-file-alt',
            'las la-file-download',
            'las la-file-export',
            'las la-file-import',
            'las la-file-upload',
            'las la-filter',
            'las la-fingerprint',
            'las la-flag',
            'las la-flag-checkered',
            'las la-folder',
            'las la-folder-open',
            'las la-frown',
            'las la-glasses',
            'las la-grip-horizontal',
            'las la-grip-lines',
            'las la-grip-lines-vertical',
            'las la-grip-vertical',
            'las la-hashtag',
            'las la-heart',
            'las la-history',
            'las la-home',
            'las la-i-cursor',
            'las la-info',
            'las la-info-circle',
            'las la-language',
            'las la-magic',
            'las la-marker',
            'las la-medal',
            'las la-meh',
            'las la-microphone',
            'las la-microphone-alt',
            'las la-microphone-slash',
            'las la-minus',
            'las la-minus-circle',
            'las la-minus-square',
            'las la-paste',
            'las la-pen',
            'las la-pen-alt',
            'las la-pen-fancy',
            'las la-pencil-alt',
            'las la-plus',
            'las la-plus-circle',
            'las la-plus-square',
            'las la-poo',
            'las la-qrcode',
            'las la-question',
            'las la-question-circle',
            'las la-quote-left',
            'las la-quote-right',
            'las la-redo',
            'las la-redo-alt',
            'las la-reply',
            'las la-reply-all',
            'las la-rss',
            'las la-rss-square',
            'las la-save',
            'las la-screwdriver',
            'las la-search',
            'las la-search-minus',
            'las la-search-plus',
            'las la-share',
            'las la-share-alt',
            'las la-share-alt-square',
            'las la-share-square',
            'las la-shield-alt',
            'las la-sign-in-alt',
            'las la-sign-out-alt',
            'las la-signal',
            'las la-sitemap',
            'las la-sliders-h',
            'las la-smile',
            'las la-sort',
            'las la-sort-alpha-down',
            'las la-sort-alpha-down-alt',
            'las la-sort-alpha-up',
            'las la-sort-alpha-up-alt',
            'las la-sort-amount-down',
            'las la-sort-amount-down-alt',
            'las la-sort-amount-up',
            'las la-sort-amount-up-alt',
            'las la-sort-down',
            'las la-sort-numeric-down',
            'las la-sort-numeric-down-alt',
            'las la-sort-numeric-up',
            'las la-sort-numeric-up-alt',
            'las la-sort-up',
            'las la-star',
            'las la-star-half',
            'las la-sync',
            'las la-sync-alt',
            'las la-thumbs-down',
            'las la-thumbs-up',
            'las la-times',
            'las la-times-circle',
            'las la-toggle-off',
            'las la-toggle-on',
            'las la-tools',
            'las la-trash',
            'las la-trash-alt',
            'las la-trash-restore',
            'las la-trash-restore-alt',
            'las la-trophy',
            'las la-undo',
            'las la-undo-alt',
            'las la-upload',
            'las la-user',
            'las la-user-alt',
            'las la-user-circle',
            'las la-volume-down',
            'las la-volume-mute',
            'las la-volume-off',
            'las la-volume-up',
            'las la-wifi',
            'las la-wrench',
            'las la-box',
            'las la-boxes',
            'las la-clipboard-check',
            'las la-clipboard-list',
            'las la-dolly',
            'las la-dolly-flatbed',
            'las la-hard-hat',
            'las la-pallet',
            'las la-shipping-fast',
            'las la-truck',
            'las la-warehouse',
            'las la-ambulance',
            'las la-anchor',
            'las la-balance-scale',
            'las la-balance-scale-left',
            'las la-balance-scale-right',
            'las la-bath',
            'las la-bed',
            'las la-beer',
            'las la-bell',
            'las la-bell-slash',
            'las la-bicycle',
            'las la-binoculars',
            'las la-birthday-cake',
            'las la-blind',
            'las la-bomb',
            'las la-book',
            'las la-bookmark',
            'las la-briefcase',
            'las la-building',
            'las la-car',
            'las la-coffee',
            'las la-crosshairs',
            'las la-directions',
            'las la-dollar-sign',
            'las la-draw-polygon',
            'las la-eye',
            'las la-eye-slash',
            'las la-fighter-jet',
            'las la-fire',
            'las la-fire-alt',
            'las la-fire-extinguisher',
            'las la-flag',
            'las la-flag-checkered',
            'las la-flask',
            'las la-gamepad',
            'las la-gavel',
            'las la-gift',
            'las la-glass-martini',
            'las la-globe',
            'las la-graduation-cap',
            'las la-h-square',
            'las la-heart',
            'las la-heartbeat',
            'las la-helicopter',
            'las la-home',
            'las la-hospital',
            'las la-image',
            'las la-images',
            'las la-industry',
            'las la-info',
            'las la-info-circle',
            'las la-key',
            'las la-landmark',
            'las la-layer-group',
            'las la-leaf',
            'las la-lemon',
            'las la-life-ring',
            'las la-lightbulb',
            'las la-location-arrow',
            'las la-low-vision',
            'las la-magnet',
            'las la-male',
            'las la-map',
            'las la-map-marker',
            'las la-map-marker-alt',
            'las la-map-pin',
            'las la-map-signs',
            'las la-medkit',
            'las la-money-bill',
            'las la-money-bill-alt',
            'las la-motorcycle',
            'las la-music',
            'las la-newspaper',
            'las la-parking',
            'las la-paw',
            'las la-phone',
            'las la-phone-alt',
            'las la-phone-square',
            'las la-phone-square-alt',
            'las la-phone-volume',
            'las la-plane',
            'las la-plug',
            'las la-plus',
            'las la-plus-square',
            'las la-print',
            'las la-recycle',
            'las la-restroom',
            'las la-road',
            'las la-rocket',
            'las la-route',
            'las la-search',
            'las la-search-minus',
            'las la-search-plus',
            'las la-ship',
            'las la-shoe-prints',
            'las la-shopping-bag',
            'las la-shopping-basket',
            'las la-shopping-cart',
            'las la-shower',
            'las la-snowplow',
            'las la-street-view',
            'las la-subway',
            'las la-suitcase',
            'las la-tag',
            'las la-tags',
            'las la-taxi',
            'las la-thumbtack',
            'las la-ticket-alt',
            'las la-tint',
            'las la-traffic-light',
            'las la-train',
            'las la-tram',
            'las la-tree',
            'las la-trophy',
            'las la-truck',
            'las la-tty',
            'las la-umbrella',
            'las la-university',
            'las la-utensil-spoon',
            'las la-utensils',
            'las la-wheelchair',
            'las la-wifi',
            'las la-wine-glass',
            'las la-wrench',
            'las la-anchor',
            'las la-binoculars',
            'las la-compass',
            'las la-dharmachakra',
            'las la-frog',
            'las la-ship',
            'las la-skull-crossbones',
            'las la-swimmer',
            'las la-water',
            'las la-wind',
            'las la-ad',
            'las la-bullhorn',
            'las la-bullseye',
            'las la-comment-dollar',
            'las la-comments-dollar',
            'las la-envelope-open-text',
            'las la-funnel-dollar',
            'las la-lightbulb',
            'las la-mail-bulk',
            'las la-poll',
            'las la-poll-h',
            'las la-search-dollar',
            'las la-search-location',
            'las la-calculator',
            'las la-divide',
            'las la-equals',
            'las la-greater-than',
            'las la-greater-than-equal',
            'las la-infinity',
            'las la-less-than',
            'las la-less-than-equal',
            'las la-minus',
            'las la-not-equal',
            'las la-percentage',
            'las la-plus',
            'las la-square-root-alt',
            'las la-subscript',
            'las la-superscript',
            'las la-times',
            'las la-wave-square',
            'las la-allergies',
            'las la-ambulance',
            'las la-band-aid',
            'las la-biohazard',
            'las la-bone',
            'las la-bong',
            'las la-book-medical',
            'las la-brain',
            'las la-briefcase-medical',
            'las la-burn',
            'las la-cannabis',
            'las la-capsules',
            'las la-clinic-medical',
            'las la-comment-medical',
            'las la-crutch',
            'las la-diagnoses',
            'las la-dna',
            'las la-file-medical',
            'las la-file-medical-alt',
            'las la-file-prescription',
            'las la-first-aid',
            'las la-heart',
            'las la-heartbeat',
            'las la-hospital',
            'las la-hospital-alt',
            'las la-hospital-symbol',
            'las la-id-card-alt',
            'las la-joint',
            'las la-laptop-medical',
            'las la-microscope',
            'las la-mortar-pestle',
            'las la-notes-medical',
            'las la-pager',
            'las la-pills',
            'las la-plus',
            'las la-poop',
            'las la-prescription',
            'las la-prescription-bottle',
            'las la-prescription-bottle-alt',
            'las la-procedures',
            'las la-radiation',
            'las la-radiation-alt',
            'las la-smoking',
            'las la-smoking-ban',
            'las la-star-of-life',
            'las la-stethoscope',
            'las la-syringe',
            'las la-tablets',
            'las la-teeth',
            'las la-teeth-open',
            'las la-thermometer',
            'las la-tooth',
            'las la-user-md',
            'las la-user-nurse',
            'las la-vial',
            'las la-vials',
            'las la-weight',
            'las la-x-ray',
            'las la-archive',
            'las la-box-open',
            'las la-couch',
            'las la-dolly',
            'las la-people-carry',
            'las la-route',
            'las la-sign',
            'las la-suitcase',
            'las la-tape',
            'las la-truck-loading',
            'las la-truck-moving',
            'las la-wine-glass',
            'las la-drum',
            'las la-drum-steelpan',
            'las la-file-audio',
            'las la-guitar',
            'las la-headphones',
            'las la-headphones-alt',
            'las la-microphone',
            'las la-microphone-alt',
            'las la-microphone-alt-slash',
            'las la-microphone-slash',
            'las la-music',
            'las la-napster',
            'las la-play',
            'las la-record-vinyl',
            'las la-sliders-h',
            'las la-soundcloud',
            'las la-spotify',
            'las la-volume-down',
            'las la-volume-mute',
            'las la-volume-off',
            'las la-volume-up',
            'las la-ambulance',
            'las la-anchor',
            'las la-archive',
            'las la-award',
            'las la-baby-carriage',
            'las la-balance-scale',
            'las la-balance-scale-left',
            'las la-balance-scale-right',
            'las la-bath',
            'las la-bed',
            'las la-beer',
            'las la-bell',
            'las la-bicycle',
            'las la-binoculars',
            'las la-birthday-cake',
            'las la-blender',
            'las la-bomb',
            'las la-book',
            'las la-book-dead',
            'las la-bookmark',
            'las la-briefcase',
            'las la-broadcast-tower',
            'las la-bug',
            'las la-building',
            'las la-bullhorn',
            'las la-bullseye',
            'las la-bus',
            'las la-calculator',
            'las la-calendar',
            'las la-calendar-alt',
            'las la-camera',
            'las la-camera-retro',
            'las la-candy-cane',
            'las la-car',
            'las la-carrot',
            'las la-church',
            'las la-clipboard',
            'las la-cloud',
            'las la-coffee',
            'las la-cog',
            'las la-cogs',
            'las la-compass',
            'las la-cookie',
            'las la-cookie-bite',
            'las la-copy',
            'las la-cube',
            'las la-cubes',
            'las la-cut',
            'las la-dice',
            'las la-dice-d20',
            'las la-dice-d6',
            'las la-dice-five',
            'las la-dice-four',
            'las la-dice-one',
            'las la-dice-six',
            'las la-dice-three',
            'las la-dice-two',
            'las la-digital-tachograph',
            'las la-door-closed',
            'las la-door-open',
            'las la-drum',
            'las la-drum-steelpan',
            'las la-envelope',
            'las la-envelope-open',
            'las la-eraser',
            'las la-eye',
            'las la-eye-dropper',
            'las la-fax',
            'las la-feather',
            'las la-feather-alt',
            'las la-fighter-jet',
            'las la-file',
            'las la-file-alt',
            'las la-file-prescription',
            'las la-film',
            'las la-fire',
            'las la-fire-alt',
            'las la-fire-extinguisher',
            'las la-flag',
            'las la-flag-checkered',
            'las la-flask',
            'las la-futbol',
            'las la-gamepad',
            'las la-gavel',
            'las la-gem',
            'las la-gift',
            'las la-gifts',
            'las la-glass-cheers',
            'las la-glass-martini',
            'las la-glass-whiskey',
            'las la-glasses',
            'las la-globe',
            'las la-graduation-cap',
            'las la-guitar',
            'las la-hat-wizard',
            'las la-hdd',
            'las la-headphones',
            'las la-headphones-alt',
            'las la-headset',
            'las la-heart',
            'las la-heart-broken',
            'las la-helicopter',
            'las la-highlighter',
            'las la-holly-berry',
            'las la-home',
            'las la-hospital',
            'las la-hourglass',
            'las la-igloo',
            'las la-image',
            'las la-images',
            'las la-industry',
            'las la-key',
            'las la-keyboard',
            'las la-laptop',
            'las la-leaf',
            'las la-lemon',
            'las la-life-ring',
            'las la-lightbulb',
            'las la-lock',
            'las la-lock-open',
            'las la-magic',
            'las la-magnet',
            'las la-map',
            'las la-map-marker',
            'las la-map-marker-alt',
            'las la-map-pin',
            'las la-map-signs',
            'las la-marker',
            'las la-medal',
            'las la-medkit',
            'las la-memory',
            'las la-microchip',
            'las la-microphone',
            'las la-microphone-alt',
            'las la-mitten',
            'las la-mobile',
            'las la-mobile-alt',
            'las la-money-bill',
            'las la-money-bill-alt',
            'las la-money-check',
            'las la-money-check-alt',
            'las la-moon',
            'las la-motorcycle',
            'las la-mug-hot',
            'las la-newspaper',
            'las la-paint-brush',
            'las la-paper-plane',
            'las la-paperclip',
            'las la-paste',
            'las la-paw',
            'las la-pen',
            'las la-pen-alt',
            'las la-pen-fancy',
            'las la-pen-nib',
            'las la-pencil-alt',
            'las la-phone',
            'las la-phone-alt',
            'las la-plane',
            'las la-plug',
            'las la-print',
            'las la-puzzle-piece',
            'las la-ring',
            'las la-road',
            'las la-rocket',
            'las la-ruler-combined',
            'las la-ruler-horizontal',
            'las la-ruler-vertical',
            'las la-satellite',
            'las la-satellite-dish',
            'las la-save',
            'las la-school',
            'las la-screwdriver',
            'las la-scroll',
            'las la-sd-card',
            'las la-search',
            'las la-shield-alt',
            'las la-shopping-bag',
            'las la-shopping-basket',
            'las la-shopping-cart',
            'las la-shower',
            'las la-sim-card',
            'las la-skull-crossbones',
            'las la-sleigh',
            'las la-snowflake',
            'las la-snowplow',
            'las la-space-shuttle',
            'las la-star',
            'las la-sticky-note',
            'las la-stopwatch',
            'las la-stroopwafel',
            'las la-subway',
            'las la-suitcase',
            'las la-sun',
            'las la-tablet',
            'las la-tablet-alt',
            'las la-tachometer-alt',
            'las la-tag',
            'las la-tags',
            'las la-taxi',
            'las la-thumbtack',
            'las la-ticket-alt',
            'las la-toilet',
            'las la-toolbox',
            'las la-tools',
            'las la-train',
            'las la-tram',
            'las la-trash',
            'las la-trash-alt',
            'las la-tree',
            'las la-trophy',
            'las la-truck',
            'las la-tv',
            'las la-umbrella',
            'las la-university',
            'las la-unlock',
            'las la-unlock-alt',
            'las la-utensil-spoon',
            'las la-utensils',
            'las la-wallet',
            'las la-weight',
            'las la-wheelchair',
            'las la-wine-glass',
            'las la-wrench',
            'las la-500px',
            'las la-accusoft',
            'las la-adn',
            'las la-adobe',
            'las la-adversal',
            'las la-affiliatetheme',
            'las la-airbnb',
            'las la-algolia',
            'las la-amazon',
            'las la-amilia',
            'las la-android',
            'las la-angellist',
            'las la-angrycreative',
            'las la-angular',
            'las la-app-store',
            'las la-app-store-ios',
            'las la-apper',
            'las la-apple',
            'las la-artstation',
            'las la-asymmetrik',
            'las la-atlassian',
            'las la-audible',
            'las la-autoprefixer',
            'las la-avianex',
            'las la-aviato',
            'las la-aws',
            'las la-backspace',
            'las la-bandcamp',
            'las la-battle-net',
            'las la-behance',
            'las la-behance-square',
            'las la-bimobject',
            'las la-bitbucket',
            'las la-bity',
            'las la-black-tie',
            'las la-blackberry',
            'las la-blender-phone',
            'las la-blogger',
            'las la-blogger-b',
            'las la-bootstrap',
            'las la-buffer',
            'las la-buromobelexperte',
            'las la-buy-n-large',
            'las la-buysellads',
            'las la-canadian-maple-leaf',
            'las la-centercode',
            'las la-centos',
            'las la-chrome',
            'las la-chromecast',
            'las la-cloudscale',
            'las la-cloudsmith',
            'las la-cloudversify',
            'las la-codepen',
            'las la-codiepie',
            'las la-confluence',
            'las la-connectdevelop',
            'las la-contao',
            'las la-cotton-bureau',
            'las la-cpanel',
            'las la-creative-commons',
            'las la-creative-commons-by',
            'las la-creative-commons-nc',
            'las la-creative-commons-nc-eu',
            'las la-creative-commons-nc-jp',
            'las la-creative-commons-nd',
            'las la-creative-commons-pd',
            'las la-creative-commons-pd-alt',
            'las la-creative-commons-remix',
            'las la-creative-commons-sa',
            'las la-creative-commons-sampling',
            'las la-creative-commons-sampling-plus',
            'las la-creative-commons-share',
            'las la-creative-commons-zero',
            'las la-crown',
            'las la-css3',
            'las la-css3-alt',
            'las la-cuttlefish',
            'las la-dashcube',
            'las la-delicious',
            'las la-deploydog',
            'las la-deskpro',
            'las la-dev',
            'las la-deviantart',
            'las la-dhl',
            'las la-diaspora',
            'las la-digg',
            'las la-digital-ocean',
            'las la-discord',
            'las la-discourse',
            'las la-dochub',
            'las la-docker',
            'las la-draft2digital',
            'las la-dribbble',
            'las la-dribbble-square',
            'las la-dropbox',
            'las la-drupal',
            'las la-dumpster-fire',
            'las la-dyalog',
            'las la-earlybirds',
            'las la-ebay',
            'las la-edge',
            'las la-elementor',
            'las la-ello',
            'las la-ember',
            'las la-empire',
            'las la-envira',
            'las la-erlang',
            'las la-etsy',
            'las la-evernote',
            'las la-expeditedssl',
            'las la-facebook',
            'las la-facebook-f',
            'las la-facebook-messenger',
            'las la-facebook-square',
            'las la-fedex',
            'las la-fedora',
            'las la-figma',
            'las la-file-csv',
            'las la-firefox',
            'las la-first-order',
            'las la-first-order-alt',
            'las la-firstdraft',
            'las la-flickr',
            'las la-flipboard',
            'las la-fly',
            'las la-font-awesome',
            'las la-font-awesome-alt',
            'las la-font-awesome-flag',
            'las la-fonticons',
            'las la-fonticons-fi',
            'las la-fort-awesome',
            'las la-fort-awesome-alt',
            'las la-forumbee',
            'las la-foursquare',
            'las la-free-code-camp',
            'las la-freebsd',
            'las la-fulcrum',
            'las la-get-pocket',
            'las la-git',
            'las la-git-alt',
            'las la-git-square',
            'las la-github',
            'las la-github-alt',
            'las la-github-square',
            'las la-gitkraken',
            'las la-gitlab',
            'las la-gitter',
            'las la-glide',
            'las la-glide-g',
            'las la-gofore',
            'las la-goodreads',
            'las la-goodreads-g',
            'las la-google',
            'las la-google-drive',
            'las la-google-play',
            'las la-google-plus',
            'las la-google-plus-g',
            'las la-google-plus-square',
            'las la-gratipay',
            'las la-grav',
            'las la-gripfire',
            'las la-grunt',
            'las la-gulp',
            'las la-hacker-news',
            'las la-hacker-news-square',
            'las la-hackerrank',
            'las la-hips',
            'las la-hire-a-helper',
            'las la-hooli',
            'las la-hornbill',
            'las la-hotjar',
            'las la-houzz',
            'las la-html5',
            'las la-hubspot',
            'las la-imdb',
            'las la-instagram',
            'las la-intercom',
            'las la-internet-explorer',
            'las la-invision',
            'las la-ioxhost',
            'las la-itch-io',
            'las la-itunes',
            'las la-itunes-note',
            'las la-java',
            'las la-jenkins',
            'las la-jira',
            'las la-joget',
            'las la-joomla',
            'las la-js',
            'las la-js-square',
            'las la-jsfiddle',
            'las la-kaggle',
            'las la-keybase',
            'las la-keycdn',
            'las la-kickstarter',
            'las la-kickstarter-k',
            'las la-korvue',
            'las la-laravel',
            'las la-lastfm',
            'las la-lastfm-square',
            'las la-leanpub',
            'las la-less',
            'las la-line',
            'las la-linkedin',
            'las la-linkedin-in',
            'las la-linode',
            'las la-linux',
            'las la-lyft',
            'las la-magento',
            'las la-mailchimp',
            'las la-mandalorian',
            'las la-markdown',
            'las la-mastodon',
            'las la-maxcdn',
            'las la-mdb',
            'las la-medapps',
            'las la-medium',
            'las la-medium-m',
            'las la-medrt',
            'las la-meetup',
            'las la-megaport',
            'las la-mendeley',
            'las la-microsoft',
            'las la-mix',
            'las la-mixcloud',
            'las la-mizuni',
            'las la-modx',
            'las la-monero',
            'las la-neos',
            'las la-network-wired',
            'las la-nimblr',
            'las la-node',
            'las la-node-js',
            'las la-npm',
            'las la-ns8',
            'las la-nutritionix',
            'las la-odnoklassniki',
            'las la-odnoklassniki-square',
            'las la-opencart',
            'las la-openid',
            'las la-opera',
            'las la-optin-monster',
            'las la-orcid',
            'las la-osi',
            'las la-page4',
            'las la-pagelines',
            'las la-palfed',
            'las la-patreon',
            'las la-periscope',
            'las la-phabricator',
            'las la-phoenix-framework',
            'las la-phoenix-squadron',
            'las la-php',
            'las la-pied-piper',
            'las la-pied-piper-alt',
            'las la-pied-piper-hat',
            'las la-pied-piper-pp',
            'las la-pinterest',
            'las la-pinterest-p',
            'las la-pinterest-square',
            'las la-product-hunt',
            'las la-pushed',
            'las la-python',
            'las la-qq',
            'las la-quinscape',
            'las la-quora',
            'las la-r-project',
            'las la-raspberry-pi',
            'las la-ravelry',
            'las la-react',
            'las la-reacteurope',
            'las la-readme',
            'las la-rebel',
            'las la-red-river',
            'las la-reddit',
            'las la-reddit-alien',
            'las la-reddit-square',
            'las la-redhat',
            'las la-renren',
            'las la-replyd',
            'las la-researchgate',
            'las la-resolving',
            'las la-rev',
            'las la-rocketchat',
            'las la-rockrms',
            'las la-safari',
            'las la-salesforce',
            'las la-sass',
            'las la-schlix',
            'las la-scribd',
            'las la-searchengin',
            'las la-sellcast',
            'las la-sellsy',
            'las la-servicestack',
            'las la-shirtsinbulk',
            'las la-shopware',
            'las la-signature',
            'las la-simplybuilt',
            'las la-sistrix',
            'las la-sith',
            'las la-sketch',
            'las la-skull',
            'las la-skyatlas',
            'las la-skype',
            'las la-slack',
            'las la-slack-hash',
            'las la-slideshare',
            'las la-snapchat',
            'las la-snapchat-ghost',
            'las la-snapchat-square',
            'las la-sourcetree',
            'las la-speakap',
            'las la-speaker-deck',
            'las la-squarespace',
            'las la-stack-exchange',
            'las la-stack-overflow',
            'las la-stackpath',
            'las la-staylinked',
            'las la-sticker-mule',
            'las la-strava',
            'las la-studiovinari',
            'las la-stumbleupon',
            'las la-stumbleupon-circle',
            'las la-superpowers',
            'las la-supple',
            'las la-suse',
            'las la-swift',
            'las la-symfony',
            'las la-teamspeak',
            'las la-telegram',
            'las la-telegram-plane',
            'las la-tencent-weibo',
            'las la-the-red-yeti',
            'las la-themeco',
            'las la-themeisle',
            'las la-think-peaks',
            'las la-trade-federation',
            'las la-trello',
            'las la-tripadvisor',
            'las la-tumblr',
            'las la-tumblr-square',
            'las la-twitter',
            'las la-twitter-square',
            'las la-typo3',
            'las la-uber',
            'las la-ubuntu',
            'las la-uikit',
            'las la-umbraco',
            'las la-uniregistry',
            'las la-untappd',
            'las la-ups',
            'las la-usb',
            'las la-usps',
            'las la-ussunnah',
            'las la-vaadin',
            'las la-viacoin',
            'las la-viadeo',
            'las la-viadeo-square',
            'las la-viber',
            'las la-vimeo',
            'las la-vimeo-square',
            'las la-vimeo-v',
            'las la-vine',
            'las la-vk',
            'las la-vnv',
            'las la-vr-cardboard',
            'las la-vuejs',
            'las la-waze',
            'las la-weebly',
            'las la-weibo',
            'las la-weight-hanging',
            'las la-weixin',
            'las la-whatsapp',
            'las la-whatsapp-square',
            'las la-whmcs',
            'las la-wikipedia-w',
            'las la-windows',
            'las la-wix',
            'las la-wolf-pack-battalion',
            'las la-wordpress',
            'las la-wordpress-simple',
            'las la-wpbeginner',
            'las la-wpexplorer',
            'las la-wpforms',
            'las la-wpressr',
            'las la-xing',
            'las la-xing-square',
            'las la-y-combinator',
            'las la-yahoo',
            'las la-yammer',
            'las la-yandex',
            'las la-yandex-international',
            'las la-yarn',
            'las la-yelp',
            'las la-yoast',
            'las la-youtube-square',
            'las la-zhihu',
            'las la-alipay',
            'las la-amazon-pay',
            'las la-apple-pay',
            'las la-bell',
            'las la-bitcoin',
            'las la-bookmark',
            'las la-btc',
            'las la-bullhorn',
            'las la-camera',
            'las la-camera-retro',
            'las la-cart-arrow-down',
            'las la-cart-plus',
            'las la-cc-amazon-pay',
            'las la-cc-amex',
            'las la-cc-apple-pay',
            'las la-cc-diners-club',
            'las la-cc-discover',
            'las la-cc-jcb',
            'las la-cc-mastercard',
            'las la-cc-paypal',
            'las la-cc-stripe',
            'las la-cc-visa',
            'las la-certificate',
            'las la-credit-card',
            'las la-ethereum',
            'las la-gem',
            'las la-gift',
            'las la-google-wallet',
            'las la-handshake',
            'las la-heart',
            'las la-key',
            'las la-money-check',
            'las la-money-check-alt',
            'las la-paypal',
            'las la-receipt',
            'las la-shopping-bag',
            'las la-shopping-basket',
            'las la-shopping-cart',
            'las la-star',
            'las la-stripe',
            'las la-stripe-s',
            'las la-tag',
            'las la-tags',
            'las la-thumbs-down',
            'las la-thumbs-up',
            'las la-trophy',
            'las la-award',
            'las la-balance-scale',
            'las la-balance-scale-left',
            'las la-balance-scale-right',
            'las la-bullhorn',
            'las la-check-double',
            'las la-democrat',
            'las la-donate',
            'las la-dove',
            'las la-fist-raised',
            'las la-flag-usa',
            'las la-handshake',
            'las la-person-booth',
            'las la-piggy-bank',
            'las la-republican',
            'las la-vote-yea',
            'las la-ankh',
            'las la-atom',
            'las la-bible',
            'las la-church',
            'las la-cross',
            'las la-dharmachakra',
            'las la-dove',
            'las la-gopuram',
            'las la-hamsa',
            'las la-hanukiah',
            'las la-haykal',
            'las la-jedi',
            'las la-journal-whills',
            'las la-kaaba',
            'las la-khanda',
            'las la-menorah',
            'las la-mosque',
            'las la-om',
            'las la-pastafarianism',
            'las la-peace',
            'las la-place-of-worship',
            'las la-pray',
            'las la-praying-hands',
            'las la-quran',
            'las la-star-and-crescent',
            'las la-star-of-david',
            'las la-synagogue',
            'las la-torah',
            'las la-torii-gate',
            'las la-vihara',
            'las la-yin-yang',
            'las la-atom',
            'las la-biohazard',
            'las la-brain',
            'las la-burn',
            'las la-capsules',
            'las la-clipboard-check',
            'las la-dna',
            'las la-eye-dropper',
            'las la-filter',
            'las la-fire',
            'las la-fire-alt',
            'las la-flask',
            'las la-frog',
            'las la-magnet',
            'las la-microscope',
            'las la-mortar-pestle',
            'las la-pills',
            'las la-prescription-bottle',
            'las la-radiation',
            'las la-radiation-alt',
            'las la-seedling',
            'las la-skull-crossbones',
            'las la-syringe',
            'las la-tablets',
            'las la-temperature-high',
            'las la-temperature-low',
            'las la-vial',
            'las la-vials',
            'las la-galactic-republic',
            'las la-galactic-senate',
            'las la-globe',
            'las la-jedi',
            'las la-jedi-order',
            'las la-journal-whills',
            'las la-meteor',
            'las la-moon',
            'las la-old-republic',
            'las la-robot',
            'las la-rocket',
            'las la-satellite',
            'las la-satellite-dish',
            'las la-space-shuttle',
            'las la-user-astronaut',
            'las la-ban',
            'las la-bug',
            'las la-door-closed',
            'las la-door-open',
            'las la-dungeon',
            'las la-eye',
            'las la-eye-slash',
            'las la-file-contract',
            'las la-file-signature',
            'las la-fingerprint',
            'las la-id-badge',
            'las la-id-card',
            'las la-id-card-alt',
            'las la-key',
            'las la-lock',
            'las la-lock-open',
            'las la-mask',
            'las la-passport',
            'las la-shield-alt',
            'las la-unlock',
            'las la-unlock-alt',
            'las la-user-lock',
            'las la-user-secret',
            'las la-user-shield',
            'las la-bookmark',
            'las la-calendar',
            'las la-certificate',
            'las la-circle',
            'las la-cloud',
            'las la-comment',
            'las la-file',
            'las la-folder',
            'las la-heart',
            'las la-heart-broken',
            'las la-map-marker',
            'las la-play',
            'las la-shapes',
            'las la-square',
            'las la-star',
            'las la-bell',
            'las la-birthday-cake',
            'las la-camera',
            'las la-comment',
            'las la-comment-alt',
            'las la-envelope',
            'las la-hashtag',
            'las la-heart',
            'las la-icons',
            'las la-image',
            'las la-images',
            'las la-map-marker',
            'las la-map-marker-alt',
            'las la-photo-video',
            'las la-poll',
            'las la-poll-h',
            'las la-retweet',
            'las la-share',
            'las la-share-alt',
            'las la-share-square',
            'las la-star',
            'las la-thumbs-down',
            'las la-thumbs-up',
            'las la-thumbtack',
            'las la-user',
            'las la-user-circle',
            'las la-user-friends',
            'las la-user-plus',
            'las la-users',
            'las la-video',
            'las la-asterisk',
            'las la-atom',
            'las la-certificate',
            'las la-circle-notch',
            'las la-cog',
            'las la-compact-disc',
            'las la-compass',
            'las la-crosshairs',
            'las la-dharmachakra',
            'las la-fan',
            'las la-haykal',
            'las la-life-ring',
            'las la-palette',
            'las la-ring',
            'las la-slash',
            'las la-snowflake',
            'las la-spinner',
            'las la-stroopwafel',
            'las la-sun',
            'las la-sync',
            'las la-sync-alt',
            'las la-yin-yang',
            'las la-baseball-ball',
            'las la-basketball-ball',
            'las la-biking',
            'las la-bowling-ball',
            'las la-dumbbell',
            'las la-football-ball',
            'las la-futbol',
            'las la-golf-ball',
            'las la-hockey-puck',
            'las la-quidditch',
            'las la-running',
            'las la-skating',
            'las la-skiing',
            'las la-skiing-nordic',
            'las la-snowboarding',
            'las la-swimmer',
            'las la-table-tennis',
            'las la-volleyball-ball',
            'las la-allergies',
            'las la-broom',
            'las la-cloud-sun',
            'las la-cloud-sun-rain',
            'las la-frog',
            'las la-rainbow',
            'las la-seedling',
            'las la-umbrella',
            'las la-ban',
            'las la-battery-empty',
            'las la-battery-full',
            'las la-battery-half',
            'las la-battery-quarter',
            'las la-battery-three-quarters',
            'las la-bell',
            'las la-bell-slash',
            'las la-calendar',
            'las la-calendar-alt',
            'las la-calendar-check',
            'las la-calendar-day',
            'las la-calendar-minus',
            'las la-calendar-plus',
            'las la-calendar-times',
            'las la-calendar-week',
            'las la-cart-arrow-down',
            'las la-cart-plus',
            'las la-comment',
            'las la-comment-alt',
            'las la-comment-slash',
            'las la-compass',
            'las la-door-closed',
            'las la-door-open',
            'las la-exclamation',
            'las la-exclamation-circle',
            'las la-exclamation-triangle',
            'las la-eye',
            'las la-eye-slash',
            'las la-file',
            'las la-file-alt',
            'las la-folder',
            'las la-folder-open',
            'las la-gas-pump',
            'las la-info',
            'las la-info-circle',
            'las la-lightbulb',
            'las la-lock',
            'las la-lock-open',
            'las la-map-marker',
            'las la-map-marker-alt',
            'las la-microphone',
            'las la-microphone-alt',
            'las la-microphone-alt-slash',
            'las la-microphone-slash',
            'las la-minus',
            'las la-minus-circle',
            'las la-minus-square',
            'las la-parking',
            'las la-phone',
            'las la-phone-alt',
            'las la-phone-slash',
            'las la-plus',
            'las la-plus-circle',
            'las la-plus-square',
            'las la-print',
            'las la-question',
            'las la-question-circle',
            'las la-shield-alt',
            'las la-shopping-cart',
            'las la-sign-in-alt',
            'las la-sign-out-alt',
            'las la-signal',
            'las la-smoking-ban',
            'las la-star',
            'las la-star-half',
            'las la-star-half-alt',
            'las la-stream',
            'las la-thermometer-empty',
            'las la-thermometer-full',
            'las la-thermometer-half',
            'las la-thermometer-quarter',
            'las la-thermometer-three-quarters',
            'las la-thumbs-down',
            'las la-thumbs-up',
            'las la-tint',
            'las la-tint-slash',
            'las la-toggle-off',
            'las la-toggle-on',
            'las la-unlock',
            'las la-unlock-alt',
            'las la-user',
            'las la-user-alt',
            'las la-user-alt-slash',
            'las la-user-slash',
            'las la-video',
            'las la-video-slash',
            'las la-volume-down',
            'las la-volume-mute',
            'las la-volume-off',
            'las la-volume-up',
            'las la-wifi',
            'las la-acquisitions-incorporated',
            'las la-book-dead',
            'las la-critical-role',
            'las la-d-and-d',
            'las la-d-and-d-beyond',
            'las la-dice-d20',
            'las la-dice-d6',
            'las la-dragon',
            'las la-dungeon',
            'las la-fantasy-flight-games',
            'las la-fist-raised',
            'las la-hat-wizard',
            'las la-penny-arcade',
            'las la-ring',
            'las la-scroll',
            'las la-skull-crossbones',
            'las la-wizards-of-the-coast',
            'las la-archway',
            'las la-atlas',
            'las la-bed',
            'las la-bus',
            'las la-bus-alt',
            'las la-cocktail',
            'las la-concierge-bell',
            'las la-dumbbell',
            'las la-glass-martini',
            'las la-glass-martini-alt',
            'las la-globe-africa',
            'las la-globe-americas',
            'las la-globe-asia',
            'las la-globe-europe',
            'las la-hot-tub',
            'las la-hotel',
            'las la-luggage-cart',
            'las la-map',
            'las la-map-marked',
            'las la-map-marked-alt',
            'las la-monument',
            'las la-passport',
            'las la-plane',
            'las la-plane-arrival',
            'las la-plane-departure',
            'las la-shuttle-van',
            'las la-spa',
            'las la-suitcase',
            'las la-suitcase-rolling',
            'las la-swimmer',
            'las la-swimming-pool',
            'las la-taxi',
            'las la-tram',
            'las la-tv',
            'las la-umbrella-beach',
            'las la-wine-glass',
            'las la-wine-glass-alt',
            'las la-address-book',
            'las la-address-card',
            'las la-baby',
            'las la-bed',
            'las la-biking',
            'las la-blind',
            'las la-chalkboard-teacher',
            'las la-child',
            'las la-female',
            'las la-frown',
            'las la-hiking',
            'las la-id-badge',
            'las la-id-card',
            'las la-id-card-alt',
            'las la-male',
            'las la-meh',
            'las la-people-carry',
            'las la-person-booth',
            'las la-poo',
            'las la-portrait',
            'las la-power-off',
            'las la-pray',
            'las la-restroom',
            'las la-running',
            'las la-skating',
            'las la-skiing',
            'las la-skiing-nordic',
            'las la-smile',
            'las la-snowboarding',
            'las la-street-view',
            'las la-swimmer',
            'las la-user',
            'las la-user-alt',
            'las la-user-alt-slash',
            'las la-user-astronaut',
            'las la-user-check',
            'las la-user-circle',
            'las la-user-clock',
            'las la-user-cog',
            'las la-user-edit',
            'las la-user-friends',
            'las la-user-graduate',
            'las la-user-injured',
            'las la-user-lock',
            'las la-user-md',
            'las la-user-minus',
            'las la-user-ninja',
            'las la-user-nurse',
            'las la-user-plus',
            'las la-user-secret',
            'las la-user-shield',
            'las la-user-slash',
            'las la-user-tag',
            'las la-user-tie',
            'las la-user-times',
            'las la-users',
            'las la-users-cog',
            'las la-walking',
            'las la-wheelchair',
            'las la-bolt',
            'las la-cloud',
            'las la-cloud-meatball',
            'las la-cloud-moon',
            'las la-cloud-moon-rain',
            'las la-cloud-rain',
            'las la-cloud-showers-heavy',
            'las la-cloud-sun',
            'las la-cloud-sun-rain',
            'las la-meteor',
            'las la-moon',
            'las la-poo-storm',
            'las la-rainbow',
            'las la-smog',
            'las la-snowflake',
            'las la-sun',
            'las la-temperature-high',
            'las la-temperature-low',
            'las la-umbrella',
            'las la-water',
            'las la-wind',
            'las la-glass-whiskey',
            'las la-icicles',
            'las la-igloo',
            'las la-mitten',
            'las la-skating',
            'las la-skiing',
            'las la-skiing-nordic',
            'las la-snowboarding',
            'las la-snowplow',
            'las la-tram',

            'lab la-accessible-icon',
            'lab la-500px',
            'lab la-accusoft',
            'lab la-adn',
            'lab la-adobe',
            'lab la-adversal',
            'lab la-affiliatetheme',
            'lab la-airbnb',
            'lab la-algolia',
            'lab la-amazon',
            'lab la-amilia',
            'lab la-android',
            'lab la-angellist',
            'lab la-angrycreative',
            'lab la-angular',
            'lab la-app-store',
            'lab la-app-store-ios',
            'lab la-apper',
            'lab la-apple',
            'lab la-artstation',
            'lab la-asymmetrik',
            'lab la-atlassian',
            'lab la-audible',
            'lab la-autoprefixer',
            'lab la-avianex',
            'lab la-aviato',
            'lab la-aws',
            'lab la-bandcamp',
            'lab la-battle-net',
            'lab la-behance',
            'lab la-behance-square',
            'lab la-bimobject',
            'lab la-bitbucket',
            'lab la-bity',
            'lab la-black-tie',
            'lab la-blackberry',
            'lab la-blogger',
            'lab la-blogger-b',
            'lab la-bootstrap',
            'lab la-buffer',
            'lab la-buromobelexperte',
            'lab la-buy-n-large',
            'lab la-buysellads',
            'lab la-canadian-maple-leaf',
            'lab la-centercode',
            'lab la-centos',
            'lab la-chrome',
            'lab la-chromecast',
            'lab la-cloudscale',
            'lab la-cloudsmith',
            'lab la-cloudversify',
            'lab la-codepen',
            'lab la-codiepie',
            'lab la-confluence',
            'lab la-connectdevelop',
            'lab la-contao',
            'lab la-cotton-bureau',
            'lab la-cpanel',
            'lab la-creative-commons',
            'lab la-creative-commons-by',
            'lab la-creative-commons-nc',
            'lab la-creative-commons-nc-eu',
            'lab la-creative-commons-nc-jp',
            'lab la-creative-commons-nd',
            'lab la-creative-commons-pd',
            'lab la-creative-commons-pd-alt',
            'lab la-creative-commons-remix',
            'lab la-creative-commons-sa',
            'lab la-creative-commons-sampling',
            'lab la-creative-commons-sampling-plus',
            'lab la-creative-commons-share',
            'lab la-creative-commons-zero',
            'lab la-css3',
            'lab la-css3-alt',
            'lab la-cuttlefish',
            'lab la-dashcube',
            'lab la-delicious',
            'lab la-deploydog',
            'lab la-deskpro',
            'lab la-dev',
            'lab la-deviantart',
            'lab la-dhl',
            'lab la-diaspora',
            'lab la-digg',
            'lab la-digital-ocean',
            'lab la-discord',
            'lab la-discourse',
            'lab la-dochub',
            'lab la-docker',
            'lab la-draft2digital',
            'lab la-dribbble',
            'lab la-dribbble-square',
            'lab la-dropbox',
            'lab la-drupal',
            'lab la-dyalog',
            'lab la-earlybirds',
            'lab la-ebay',
            'lab la-edge',
            'lab la-elementor',
            'lab la-ello',
            'lab la-ember',
            'lab la-empire',
            'lab la-envira',
            'lab la-erlang',
            'lab la-etsy',
            'lab la-evernote',
            'lab la-expeditedssl',
            'lab la-facebook',
            'lab la-facebook-f',
            'lab la-facebook-messenger',
            'lab la-facebook-square',
            'lab la-fedex',
            'lab la-fedora',
            'lab la-figma',
            'lab la-firefox',
            'lab la-first-order',
            'lab la-first-order-alt',
            'lab la-firstdraft',
            'lab la-flickr',
            'lab la-flipboard',
            'lab la-fly',
            'lab la-font-awesome',
            'lab la-font-awesome-alt',
            'lab la-font-awesome-flag',
            'lab la-fonticons',
            'lab la-fonticons-fi',
            'lab la-fort-awesome',
            'lab la-fort-awesome-alt',
            'lab la-forumbee',
            'lab la-foursquare',
            'lab la-free-code-camp',
            'lab la-freebsd',
            'lab la-fulcrum',
            'lab la-get-pocket',
            'lab la-git',
            'lab la-git-alt',
            'lab la-git-square',
            'lab la-github',
            'lab la-github-alt',
            'lab la-github-square',
            'lab la-gitkraken',
            'lab la-gitlab',
            'lab la-gitter',
            'lab la-glide',
            'lab la-glide-g',
            'lab la-gofore',
            'lab la-goodreads',
            'lab la-goodreads-g',
            'lab la-google',
            'lab la-google-drive',
            'lab la-google-play',
            'lab la-google-plus',
            'lab la-google-plus-g',
            'lab la-google-plus-square',
            'lab la-gratipay',
            'lab la-grav',
            'lab la-gripfire',
            'lab la-grunt',
            'lab la-gulp',
            'lab la-hacker-news',
            'lab la-hacker-news-square',
            'lab la-hackerrank',
            'lab la-hips',
            'lab la-hire-a-helper',
            'lab la-hooli',
            'lab la-hornbill',
            'lab la-hotjar',
            'lab la-houzz',
            'lab la-html5',
            'lab la-hubspot',
            'lab la-imdb',
            'lab la-instagram',
            'lab la-intercom',
            'lab la-internet-explorer',
            'lab la-invision',
            'lab la-ioxhost',
            'lab la-itch-io',
            'lab la-itunes',
            'lab la-itunes-note',
            'lab la-java',
            'lab la-jenkins',
            'lab la-jira',
            'lab la-joget',
            'lab la-joomla',
            'lab la-js',
            'lab la-js-square',
            'lab la-jsfiddle',
            'lab la-kaggle',
            'lab la-keybase',
            'lab la-keycdn',
            'lab la-kickstarter',
            'lab la-kickstarter-k',
            'lab la-korvue',
            'lab la-laravel',
            'lab la-lastfm',
            'lab la-lastfm-square',
            'lab la-leanpub',
            'lab la-less',
            'lab la-line',
            'lab la-linkedin',
            'lab la-linkedin-in',
            'lab la-linode',
            'lab la-linux',
            'lab la-lyft',
            'lab la-magento',
            'lab la-mailchimp',
            'lab la-mandalorian',
            'lab la-markdown',
            'lab la-mastodon',
            'lab la-maxcdn',
            'lab la-mdb',
            'lab la-medapps',
            'lab la-medium',
            'lab la-medium-m',
            'lab la-medrt',
            'lab la-meetup',
            'lab la-megaport',
            'lab la-mendeley',
            'lab la-microsoft',
            'lab la-mix',
            'lab la-mixcloud',
            'lab la-mizuni',
            'lab la-modx',
            'lab la-monero',
            'lab la-neos',
            'lab la-nimblr',
            'lab la-node',
            'lab la-node-js',
            'lab la-npm',
            'lab la-ns8',
            'lab la-nutritionix',
            'lab la-odnoklassniki',
            'lab la-odnoklassniki-square',
            'lab la-opencart',
            'lab la-openid',
            'lab la-opera',
            'lab la-optin-monster',
            'lab la-orcid',
            'lab la-osi',
            'lab la-page4',
            'lab la-pagelines',
            'lab la-palfed',
            'lab la-patreon',
            'lab la-periscope',
            'lab la-phabricator',
            'lab la-phoenix-framework',
            'lab la-phoenix-squadron',
            'lab la-php',
            'lab la-pied-piper',
            'lab la-pied-piper-alt',
            'lab la-pied-piper-hat',
            'lab la-pied-piper-pp',
            'lab la-pinterest',
            'lab la-pinterest-p',
            'lab la-pinterest-square',
            'lab la-product-hunt',
            'lab la-pushed',
            'lab la-python',
            'lab la-qq',
            'lab la-quinscape',
            'lab la-quora',
            'lab la-r-project',
            'lab la-raspberry-pi',
            'lab la-ravelry',
            'lab la-react',
            'lab la-reacteurope',
            'lab la-readme',
            'lab la-rebel',
            'lab la-red-river',
            'lab la-reddit',
            'lab la-reddit-alien',
            'lab la-reddit-square',
            'lab la-redhat',
            'lab la-renren',
            'lab la-replyd',
            'lab la-researchgate',
            'lab la-resolving',
            'lab la-rev',
            'lab la-rocketchat',
            'lab la-rockrms',
            'lab la-safari',
            'lab la-salesforce',
            'lab la-sass',
            'lab la-schlix',
            'lab la-scribd',
            'lab la-searchengin',
            'lab la-sellcast',
            'lab la-sellsy',
            'lab la-servicestack',
            'lab la-shirtsinbulk',
            'lab la-shopware',
            'lab la-simplybuilt',
            'lab la-sistrix',
            'lab la-sith',
            'lab la-sketch',
            'lab la-skyatlas',
            'lab la-skype',
            'lab la-slack',
            'lab la-slack-hash',
            'lab la-slideshare',
            'lab la-snapchat',
            'lab la-snapchat-ghost',
            'lab la-snapchat-square',
            'lab la-sourcetree',
            'lab la-speakap',
            'lab la-speaker-deck',
            'lab la-squarespace',
            'lab la-stack-exchange',
            'lab la-stack-overflow',
            'lab la-stackpath',
            'lab la-staylinked',
            'lab la-sticker-mule',
            'lab la-strava',
            'lab la-studiovinari',
            'lab la-stumbleupon',
            'lab la-stumbleupon-circle',
            'lab la-superpowers',
            'lab la-supple',
            'lab la-suse',
            'lab la-swift',
            'lab la-symfony',
            'lab la-teamspeak',
            'lab la-telegram',
            'lab la-telegram-plane',
            'lab la-tencent-weibo',
            'lab la-the-red-yeti',
            'lab la-themeco',
            'lab la-themeisle',
            'lab la-think-peaks',
            'lab la-trade-federation',
            'lab la-trello',
            'lab la-tripadvisor',
            'lab la-tumblr',
            'lab la-tumblr-square',
            'lab la-twitter',
            'lab la-twitter-square',
            'lab la-typo3',
            'lab la-uber',
            'lab la-ubuntu',
            'lab la-uikit',
            'lab la-umbraco',
            'lab la-uniregistry',
            'lab la-untappd',
            'lab la-ups',
            'lab la-usb',
            'lab la-usps',
            'lab la-ussunnah',
            'lab la-vaadin',
            'lab la-viacoin',
            'lab la-viadeo',
            'lab la-viadeo-square',
            'lab la-viber',
            'lab la-vimeo',
            'lab la-vimeo-square',
            'lab la-vimeo-v',
            'lab la-vine',
            'lab la-vk',
            'lab la-vnv',
            'lab la-vuejs',
            'lab la-waze',
            'lab la-weebly',
            'lab la-weibo',
            'lab la-weixin',
            'lab la-whatsapp',
            'lab la-whatsapp-square',
            'lab la-whmcs',
            'lab la-wikipedia-w',
            'lab la-windows',
            'lab la-wix',
            'lab la-wolf-pack-battalion',
            'lab la-wordpress',
            'lab la-wordpress-simple',
            'lab la-wpbeginner',
            'lab la-wpexplorer',
            'lab la-wpforms',
            'lab la-wpressr',
            'lab la-xing',
            'lab la-xing-square',
            'lab la-y-combinator',
            'lab la-yahoo',
            'lab la-yammer',
            'lab la-yandex',
            'lab la-yandex-international',
            'lab la-yarn',
            'lab la-yelp',
            'lab la-yoast',
            'lab la-youtube-square',
            'lab la-zhihu',
            );

        $iconsFA = array (
            'fas fa-ad',
            'fas fa-address-book',
            'fas fa-address-card',
            'fas fa-adjust',
            'fas fa-air-freshener',
            'fas fa-align-center',
            'fas fa-align-justify',
            'fas fa-align-left',
            'fas fa-align-right',
            'fas fa-allergies',
            'fas fa-ambulance',
            'fas fa-american-sign-language-interpreting',
            'fas fa-anchor',
            'fas fa-angle-double-down',
            'fas fa-angle-double-left',
            'fas fa-angle-double-right',
            'fas fa-angle-double-up',
            'fas fa-angle-down',
            'fas fa-angle-left',
            'fas fa-angle-right',
            'fas fa-angle-up',
            'fas fa-angry',
            'fas fa-ankh',
            'fas fa-apple-alt',
            'fas fa-archive',
            'fas fa-archway',
            'fas fa-arrow-alt-circle-down',
            'fas fa-arrow-alt-circle-left',
            'fas fa-arrow-alt-circle-right',
            'fas fa-arrow-alt-circle-up',
            'fas fa-arrow-circle-down',
            'fas fa-arrow-circle-left',
            'fas fa-arrow-circle-right',
            'fas fa-arrow-circle-up',
            'fas fa-arrow-down',
            'fas fa-arrow-left',
            'fas fa-arrow-right',
            'fas fa-arrow-up',
            'fas fa-arrows-alt',
            'fas fa-arrows-alt-h',
            'fas fa-arrows-alt-v',
            'fas fa-assistive-listening-systems',
            'fas fa-asterisk',
            'fas fa-at',
            'fas fa-atlas',
            'fas fa-atom',
            'fas fa-audio-description',
            'fas fa-award',
            'fas fa-baby',
            'fas fa-baby-carriage',
            'fas fa-backspace',
            'fas fa-backward',
            'fas fa-bacon',
            'fas fa-bacteria',
            'fas fa-bacterium',
            'fas fa-bahai',
            'fas fa-balance-scale',
            'fas fa-balance-scale-left',
            'fas fa-balance-scale-right',
            'fas fa-ban',
            'fas fa-band-aid',
            'fas fa-barcode',
            'fas fa-bars',
            'fas fa-baseball-ball',
            'fas fa-basketball-ball',
            'fas fa-bath',
            'fas fa-battery-empty',
            'fas fa-battery-full',
            'fas fa-battery-half',
            'fas fa-battery-quarter',
            'fas fa-battery-three-quarters',
            'fas fa-bed',
            'fas fa-beer',
            'fas fa-bell',
            'fas fa-bell-slash',
            'fas fa-bezier-curve',
            'fas fa-bible',
            'fas fa-bicycle',
            'fas fa-biking',
            'fas fa-binoculars',
            'fas fa-biohazard',
            'fas fa-birthday-cake',
            'fas fa-blender',
            'fas fa-blender-phone',
            'fas fa-blind',
            'fas fa-blog',
            'fas fa-bold',
            'fas fa-bolt',
            'fas fa-bomb',
            'fas fa-bone',
            'fas fa-bong',
            'fas fa-book',
            'fas fa-book-dead',
            'fas fa-book-medical',
            'fas fa-book-open',
            'fas fa-book-reader',
            'fas fa-bookmark',
            'fas fa-border-all',
            'fas fa-border-none',
            'fas fa-border-style',
            'fas fa-bowling-ball',
            'fas fa-box',
            'fas fa-box-open',
            'fas fa-box-tissue',
            'fas fa-boxes',
            'fas fa-braille',
            'fas fa-brain',
            'fas fa-bread-slice',
            'fas fa-briefcase',
            'fas fa-briefcase-medical',
            'fas fa-broadcast-tower',
            'fas fa-broom',
            'fas fa-brush',
            'fas fa-bug',
            'fas fa-building',
            'fas fa-bullhorn',
            'fas fa-bullseye',
            'fas fa-burn',
            'fas fa-bus',
            'fas fa-bus-alt',
            'fas fa-business-time',
            'fas fa-calculator',
            'fas fa-calendar',
            'fas fa-calendar-alt',
            'fas fa-calendar-check',
            'fas fa-calendar-day',
            'fas fa-calendar-minus',
            'fas fa-calendar-plus',
            'fas fa-calendar-times',
            'fas fa-calendar-week',
            'fas fa-camera',
            'fas fa-camera-retro',
            'fas fa-campground',
            'fas fa-candy-cane',
            'fas fa-cannabis',
            'fas fa-capsules',
            'fas fa-car',
            'fas fa-car-alt',
            'fas fa-car-battery',
            'fas fa-car-crash',
            'fas fa-car-side',
            'fas fa-caravan',
            'fas fa-caret-down',
            'fas fa-caret-left',
            'fas fa-caret-right',
            'fas fa-caret-square-down',
            'fas fa-caret-square-left',
            'fas fa-caret-square-right',
            'fas fa-caret-square-up',
            'fas fa-caret-up',
            'fas fa-carrot',
            'fas fa-cart-arrow-down',
            'fas fa-cart-plus',
            'fas fa-cash-register',
            'fas fa-cat',
            'fas fa-certificate',
            'fas fa-chair',
            'fas fa-chalkboard',
            'fas fa-chalkboard-teacher',
            'fas fa-charging-station',
            'fas fa-chart-area',
            'fas fa-chart-bar',
            'fas fa-chart-line',
            'fas fa-chart-pie',
            'fas fa-check',
            'fas fa-check-circle',
            'fas fa-check-double',
            'fas fa-check-square',
            'fas fa-cheese',
            'fas fa-chess',
            'fas fa-chess-bishop',
            'fas fa-chess-board',
            'fas fa-chess-king',
            'fas fa-chess-knight',
            'fas fa-chess-pawn',
            'fas fa-chess-queen',
            'fas fa-chess-rook',
            'fas fa-chevron-circle-down',
            'fas fa-chevron-circle-left',
            'fas fa-chevron-circle-right',
            'fas fa-chevron-circle-up',
            'fas fa-chevron-down',
            'fas fa-chevron-left',
            'fas fa-chevron-right',
            'fas fa-chevron-up',
            'fas fa-child',
            'fas fa-church',
            'fas fa-circle',
            'fas fa-circle-notch',
            'fas fa-city',
            'fas fa-clinic-medical',
            'fas fa-clipboard',
            'fas fa-clipboard-check',
            'fas fa-clipboard-list',
            'fas fa-clock',
            'fas fa-clone',
            'fas fa-closed-captioning',
            'fas fa-cloud',
            'fas fa-cloud-download-alt',
            'fas fa-cloud-meatball',
            'fas fa-cloud-moon',
            'fas fa-cloud-moon-rain',
            'fas fa-cloud-rain',
            'fas fa-cloud-showers-heavy',
            'fas fa-cloud-sun',
            'fas fa-cloud-sun-rain',
            'fas fa-cloud-upload-alt',
            'fas fa-cocktail',
            'fas fa-code',
            'fas fa-code-branch',
            'fas fa-coffee',
            'fas fa-cog',
            'fas fa-cogs',
            'fas fa-coins',
            'fas fa-columns',
            'fas fa-comment',
            'fas fa-comment-alt',
            'fas fa-comment-dollar',
            'fas fa-comment-dots',
            'fas fa-comment-medical',
            'fas fa-comment-slash',
            'fas fa-comments',
            'fas fa-comments-dollar',
            'fas fa-compact-disc',
            'fas fa-compass',
            'fas fa-compress',
            'fas fa-compress-alt',
            'fas fa-compress-arrows-alt',
            'fas fa-concierge-bell',
            'fas fa-cookie',
            'fas fa-cookie-bite',
            'fas fa-copy',
            'fas fa-copyright',
            'fas fa-couch',
            'fas fa-credit-card',
            'fas fa-crop',
            'fas fa-crop-alt',
            'fas fa-cross',
            'fas fa-crosshairs',
            'fas fa-crow',
            'fas fa-crown',
            'fas fa-crutch',
            'fas fa-cube',
            'fas fa-cubes',
            'fas fa-cut',
            'fas fa-database',
            'fas fa-deaf',
            'fas fa-democrat',
            'fas fa-desktop',
            'fas fa-dharmachakra',
            'fas fa-diagnoses',
            'fas fa-dice',
            'fas fa-dice-d20',
            'fas fa-dice-d6',
            'fas fa-dice-five',
            'fas fa-dice-four',
            'fas fa-dice-one',
            'fas fa-dice-six',
            'fas fa-dice-three',
            'fas fa-dice-two',
            'fas fa-digital-tachograph',
            'fas fa-directions',
            'fas fa-disease',
            'fas fa-divide',
            'fas fa-dizzy',
            'fas fa-dna',
            'fas fa-dog',
            'fas fa-dollar-sign',
            'fas fa-dolly',
            'fas fa-dolly-flatbed',
            'fas fa-donate',
            'fas fa-door-closed',
            'fas fa-door-open',
            'fas fa-dot-circle',
            'fas fa-dove',
            'fas fa-download',
            'fas fa-drafting-compass',
            'fas fa-dragon',
            'fas fa-draw-polygon',
            'fas fa-drum',
            'fas fa-drum-steelpan',
            'fas fa-drumstick-bite',
            'fas fa-dumbbell',
            'fas fa-dumpster',
            'fas fa-dumpster-fire',
            'fas fa-dungeon',
            'fas fa-edit',
            'fas fa-egg',
            'fas fa-eject',
            'fas fa-ellipsis-h',
            'fas fa-ellipsis-v',
            'fas fa-envelope',
            'fas fa-envelope-open',
            'fas fa-envelope-open-text',
            'fas fa-envelope-square',
            'fas fa-equals',
            'fas fa-eraser',
            'fas fa-ethernet',
            'fas fa-euro-sign',
            'fas fa-exchange-alt',
            'fas fa-exclamation',
            'fas fa-exclamation-circle',
            'fas fa-exclamation-triangle',
            'fas fa-expand',
            'fas fa-expand-alt',
            'fas fa-expand-arrows-alt',
            'fas fa-external-link-alt',
            'fas fa-external-link-square-alt',
            'fas fa-eye',
            'fas fa-eye-dropper',
            'fas fa-eye-slash',
            'fas fa-fan',
            'fas -fast-backward',
            'fas -fast-forward',
            'fas fa-faucet',
            'fas fa-fax',
            'fas fa-feather',
            'fas fa-feather-alt',
            'fas fa-female',
            'fas fa-fighter-jet',
            'fas fa-file',
            'fas fa-file-alt',
            'fas fa-file-archive',
            'fas fa-file-audio',
            'fas fa-file-code',
            'fas fa-file-contract',
            'fas fa-file-csv',
            'fas fa-file-download',
            'fas fa-file-excel',
            'fas fa-file-export',
            'fas fa-file-image',
            'fas fa-file-import',
            'fas fa-file-invoice',
            'fas fa-file-invoice-dollar',
            'fas fa-file-medical',
            'fas fa-file-medical-alt',
            'fas fa-file-pdf',
            'fas fa-file-powerpoint',
            'fas fa-file-prescription',
            'fas fa-file-signature',
            'fas fa-file-upload',
            'fas fa-file-video',
            'fas fa-file-word',
            'fas fa-fill',
            'fas fa-fill-drip',
            'fas fa-film',
            'fas fa-filter',
            'fas fa-fingerprint',
            'fas fa-fire',
            'fas fa-fire-alt',
            'fas fa-fire-extinguisher',
            'fas fa-first-aid',
            'fas fa-fish',
            'fas fa-fist-raised',
            'fas fa-flag',
            'fas fa-flag-checkered',
            'fas fa-flag-usa',
            'fas fa-flask',
            'fas fa-flushed',
            'fas fa-folder',
            'fas fa-folder-minus',
            'fas fa-folder-open',
            'fas fa-folder-plus',
            'fas fa-font',
            'fas fa-football-ball',
            'fas fa-forward',
            'fas fa-frog',
            'fas fa-frown',
            'fas fa-frown-open',
            'fas fa-funnel-dollar',
            'fas fa-futbol',
            'fas fa-gamepad',
            'fas fa-gas-pump',
            'fas fa-gavel',
            'fas fa-gem',
            'fas fa-genderless',
            'fas fa-ghost',
            'fas fa-gift',
            'fas fa-gifts',
            'fas fa-glass-cheers',
            'fas fa-glass-martini',
            'fas fa-glass-martini-alt',
            'fas fa-glass-whiskey',
            'fas fa-glasses',
            'fas fa-globe',
            'fas fa-globe-africa',
            'fas fa-globe-americas',
            'fas fa-globe-asia',
            'fas fa-globe-europe',
            'fas fa-golf-ball',
            'fas fa-gopuram',
            'fas fa-graduation-cap',
            'fas fa-greater-than',
            'fas fa-greater-than-equal',
            'fas fa-grimace',
            'fas fa-grin',
            'fas fa-grin-alt',
            'fas fa-grin-beam',
            'fas fa-grin-beam-sweat',
            'fas fa-grin-hearts',
            'fas fa-grin-squint',
            'fas fa-grin-squint-tears',
            'fas fa-grin-stars',
            'fas fa-grin-tears',
            'fas fa-grin-tongue',
            'fas fa-grin-tongue-squint',
            'fas fa-grin-tongue-wink',
            'fas fa-grin-wink',
            'fas fa-grip-horizontal',
            'fas fa-grip-lines',
            'fas fa-grip-lines-vertical',
            'fas fa-grip-vertical',
            'fas fa-guitar',
            'fas fa-h-square',
            'fas fa-hamburger',
            'fas fa-hammer',
            'fas fa-hamsa',
            'fas fa-hand-holding',
            'fas fa-hand-holding-heart',
            'fas fa-hand-holding-medical',
            'fas fa-hand-holding-usd',
            'fas fa-hand-holding-water',
            'fas fa-hand-lizard',
            'fas fa-hand-middle-finger',
            'fas fa-hand-paper',
            'fas fa-hand-peace',
            'fas fa-hand-point-down',
            'fas fa-hand-point-left',
            'fas fa-hand-point-right',
            'fas fa-hand-point-up',
            'fas fa-hand-pointer',
            'fas fa-hand-rock',
            'fas fa-hand-scissors',
            'fas fa-hand-sparkles',
            'fas fa-hand-spock',
            'fas fa-hands',
            'fas fa-hands-helping',
            'fas fa-hands-wash',
            'fas fa-handshake',
            'fas fa-handshake-alt-slash',
            'fas fa-handshake-slash',
            'fas fa-hanukiah',
            'fas fa-hard-hat',
            'fas fa-hashtag',
            'fas fa-hat-cowboy',
            'fas fa-hat-cowboy-side',
            'fas fa-hat-wizard',
            'fas fa-hdd',
            'fas fa-head-side-cough',
            'fas fa-head-side-cough-slash',
            'fas fa-head-side-mask',
            'fas fa-head-side-virus',
            'fas fa-heading',
            'fas fa-headphones',
            'fas fa-headphones-alt',
            'fas fa-headset',
            'fas fa-heart',
            'fas fa-heart-broken',
            'fas fa-heartbeat',
            'fas fa-helicopter',
            'fas fa-highlighter',
            'fas fa-hiking',
            'fas fa-hippo',
            'fas fa-history',
            'fas fa-hockey-puck',
            'fas fa-holly-berry',
            'fas fa-home',
            'fas fa-horse',
            'fas fa-horse-head',
            'fas fa-hospital',
            'fas fa-hospital-alt',
            'fas fa-hospital-symbol',
            'fas fa-hospital-user',
            'fas fa-hot-tub',
            'fas fa-hotdog',
            'fas fa-hotel',
            'fas fa-hourglass',
            'fas fa-hourglass-end',
            'fas fa-hourglass-half',
            'fas fa-hourglass-start',
            'fas fa-house-damage',
            'fas fa-house-user',
            'fas fa-hryvnia',
            'fas fa-i-cursor',
            'fas fa-ice-cream',
            'fas fa-icicles',
            'fas fa-icons',
            'fas fa-id-badge',
            'fas fa-id-card',
            'fas fa-id-card-alt',
            'fas fa-igloo',
            'fas fa-image',
            'fas fa-images',
            'fas fa-inbox',
            'fas fa-indent',
            'fas fa-industry',
            'fas fa-infinity',
            'fas fa-info',
            'fas fa-info-circle',
            'fas fa-italic',
            'fas fa-jedi',
            'fas fa-joint',
            'fas fa-journal-whills',
            'fas fa-kaaba',
            'fas fa-key',
            'fas fa-keyboard',
            'fas fa-khanda',
            'fas fa-kiss',
            'fas fa-kiss-beam',
            'fas fa-kiss-wink-heart',
            'fas fa-kiwi-bird',
            'fas fa-landmark',
            'fas fa-language',
            'fas fa-laptop',
            'fas fa-laptop-code',
            'fas fa-laptop-house',
            'fas fa-laptop-medical',
            'fas fa-laugh',
            'fas fa-laugh-beam',
            'fas fa-laugh-squint',
            'fas fa-laugh-wink',
            'fas fa-layer-group',
            'fas fa-leaf',
            'fas fa-lemon',
            'fas fa-less-than',
            'fas fa-less-than-equal',
            'fas fa-level-down-alt',
            'fas fa-level-up-alt',
            'fas fa-life-ring',
            'fas fa-lightbulb',
            'fas fa-link',
            'fas fa-lira-sign',
            'fas fa-list',
            'fas fa-list-alt',
            'fas fa-list-ol',
            'fas fa-list-ul',
            'fas fa-location-arrow',
            'fas fa-lock',
            'fas fa-lock-open',
            'fas fa-long-arrow-alt-down',
            'fas fa-long-arrow-alt-left',
            'fas fa-long-arrow-alt-right',
            'fas fa-long-arrow-alt-up',
            'fas fa-low-vision',
            'fas fa-luggage-cart',
            'fas fa-lungs',
            'fas fa-lungs-virus',
            'fas fa-magic',
            'fas fa-magnet',
            'fas fa-mail-bulk',
            'fas fa-male',
            'fas fa-map',
            'fas fa-map-marked',
            'fas fa-map-marked-alt',
            'fas fa-map-marker',
            'fas fa-map-marker-alt',
            'fas fa-map-pin',
            'fas fa-map-signs',
            'fas fa-marker',
            'fas fa-mars',
            'fas fa-mars-double',
            'fas fa-mars-stroke',
            'fas fa-mars-stroke-h',
            'fas fa-mars-stroke-v',
            'fas fa-mask',
            'fas fa-medal',
            'fas fa-medkit',
            'fas fa-meh',
            'fas fa-meh-blank',
            'fas fa-meh-rolling-eyes',
            'fas fa-memory',
            'fas fa-menorah',
            'fas fa-mercury',
            'fas fa-meteor',
            'fas fa-microchip',
            'fas fa-microphone',
            'fas fa-microphone-alt',
            'fas fa-microphone-alt-slash',
            'fas fa-microphone-slash',
            'fas fa-microscope',
            'fas fa-minus',
            'fas fa-minus-circle',
            'fas fa-minus-square',
            'fas fa-mitten',
            'fas fa-mobile',
            'fas fa-mobile-alt',
            'fas fa-money-bill',
            'fas fa-money-bill-alt',
            'fas fa-money-bill-wave',
            'fas fa-money-bill-wave-alt',
            'fas fa-money-check',
            'fas fa-money-check-alt',
            'fas fa-monument',
            'fas fa-moon',
            'fas fa-mortar-pestle',
            'fas fa-mosque',
            'fas fa-motorcycle',
            'fas fa-mountain',
            'fas fa-mouse',
            'fas fa-mouse-pointer',
            'fas fa-mug-hot',
            'fas fa-music',
            'fas fa-network-wired',
            'fas fa-neuter',
            'fas fa-newspaper',
            'fas fa-not-equal',
            'fas fa-notes-medical',
            'fas fa-object-group',
            'fas fa-object-ungroup',
            'fas fa-oil-can',
            'fas fa-om',
            'fas fa-otter',
            'fas fa-outdent',
            'fas fa-pager',
            'fas fa-paint-brush',
            'fas fa-paint-roller',
            'fas fa-palette',
            'fas fa-pallet',
            'fas fa-paper-plane',
            'fas fa-paperclip',
            'fas fa-parachute-box',
            'fas fa-paragraph',
            'fas fa-parking',
            'fas fa-passport',
            'fas fa-pastafarianism',
            'fas fa-paste',
            'fas fa-pause',
            'fas fa-pause-circle',
            'fas fa-paw',
            'fas fa-peace',
            'fas fa-pen',
            'fas fa-pen-alt',
            'fas fa-pen-fancy',
            'fas fa-pen-nib',
            'fas fa-pen-square',
            'fas fa-pencil-alt',
            'fas fa-pencil-ruler',
            'fas fa-people-arrows',
            'fas fa-people-carry',
            'fas fa-pepper-hot',
            'fas fa-percent',
            'fas fa-percentage',
            'fas fa-person-booth',
            'fas fa-phone',
            'fas fa-phone-alt',
            'fas fa-phone-slash',
            'fas fa-phone-square',
            'fas fa-phone-square-alt',
            'fas fa-phone-volume',
            'fas fa-photo-video',
            'fas fa-piggy-bank',
            'fas fa-pills',
            'fas fa-pizza-slice',
            'fas fa-place-of-worship',
            'fas fa-plane',
            'fas fa-plane-arrival',
            'fas fa-plane-departure',
            'fas fa-plane-slash',
            'fas fa-play',
            'fas fa-play-circle',
            'fas fa-plug',
            'fas fa-plus',
            'fas fa-plus-circle',
            'fas fa-plus-square',
            'fas fa-podcast',
            'fas fa-poll',
            'fas fa-poll-h',
            'fas fa-poo',
            'fas fa-poo-storm',
            'fas fa-poop',
            'fas fa-portrait',
            'fas fa-pound-sign',
            'fas fa-power-off',
            'fas fa-pray',
            'fas fa-praying-hands',
            'fas fa-prescription',
            'fas fa-prescription-bottle',
            'fas fa-prescription-bottle-alt',
            'fas fa-print',
            'fas fa-procedures',
            'fas fa-project-diagram',
            'fas fa-pump-medical',
            'fas fa-pump-soap',
            'fas fa-puzzle-piece',
            'fas fa-qrcode',
            'fas fa-question',
            'fas fa-question-circle',
            'fas fa-quidditch',
            'fas fa-quote-left',
            'fas fa-quote-right',
            'fas fa-quran',
            'fas fa-radiation',
            'fas fa-radiation-alt',
            'fas fa-rainbow',
            'fas fa-random',
            'fas fa-receipt',
            'fas fa-record-vinyl',
            'fas fa-recycle',
            'fas fa-redo',
            'fas fa-redo-alt',
            'fas fa-registered',
            'fas fa-remove-format',
            'fas fa-reply',
            'fas fa-reply-all',
            'fas fa-republican',
            'fas fa-restroom',
            'fas fa-retweet',
            'fas fa-ribbon',
            'fas fa-ring',
            'fas fa-road',
            'fas fa-robot',
            'fas fa-rocket',
            'fas fa-route',
            'fas fa-rss',
            'fas fa-rss-square',
            'fas fa-ruble-sign',
            'fas fa-ruler',
            'fas fa-ruler-combined',
            'fas fa-ruler-horizontal',
            'fas fa-ruler-vertical',
            'fas fa-running',
            'fas fa-rupee-sign',
            'fas fa-sad-cry',
            'fas fa-sad-tear',
            'fas fa-satellite',
            'fas fa-satellite-dish',
            'fas fa-save',
            'fas fa-school',
            'fas fa-screwdriver',
            'fas fa-scroll',
            'fas fa-sd-card',
            'fas fa-search',
            'fas fa-search-dollar',
            'fas fa-search-location',
            'fas fa-search-minus',
            'fas fa-search-plus',
            'fas fa-seedling',
            'fas fa-server',
            'fas fa-shapes',
            'fas fa-share',
            'fas fa-share-alt',
            'fas fa-share-alt-square',
            'fas fa-share-square',
            'fas fa-shekel-sign',
            'fas fa-shield-alt',
            'fas fa-shield-virus',
            'fas fa-ship',
            'fas fa-shippi-fast',
            'fas fa-shoe-prints',
            'fas fa-shopping-bag',
            'fas fa-shopping-basket',
            'fas fa-shopping-cart',
            'fas fa-shower',
            'fas fa-shuttle-van',
            'fas fa-sign',
            'fas fa-sign-in-alt',
            'fas fa-sign-language',
            'fas fa-sign-out-alt',
            'fas fa-signal',
            'fas fa-signature',
            'fas fa-sim-card',
            'fas fa-sink',
            'fas fa-sitemap',
            'fas fa-skating',
            'fas fa-skiing',
            'fas fa-skiing-nordic',
            'fas fa-skull',
            'fas fa-skull-crossbones',
            'fas fa-slash',
            'fas fa-sleigh',
            'fas fa-sliders-h',
            'fas fa-smile',
            'fas fa-smile-beam',
            'fas fa-smile-wink',
            'fas fa-smog',
            'fas fa-smoking',
            'fas fa-smoking-ban',
            'fas fa-sms',
            'fas fa-snowboarding',
            'fas fa-snowflake',
            'fas fa-snowman',
            'fas fa-snowplow',
            'fas fa-soap',
            'fas fa-socks',
            'fas fa-solar-panel',
            'fas fa-sort',
            'fas fa-sort-alpha-down',
            'fas fa-sort-alpha-down-alt',
            'fas fa-sort-alpha-up',
            'fas fa-sort-alpha-up-alt',
            'fas fa-sort-amount-down',
            'fas fa-sort-amount-down-alt',
            'fas fa-sort-amount-up',
            'fas fa-sort-amount-up-alt',
            'fas fa-sort-down',
            'fas fa-sort-numeric-down',
            'fas fa-sort-numeric-down-alt',
            'fas fa-sort-numeric-up',
            'fas fa-sort-numeric-up-alt',
            'fas fa-sort-up',
            'fas fa-spa',
            'fas fa-space-shuttle',
            'fas fa-spell-check',
            'fas fa-spider',
            'fas fa-spinner',
            'fas fa-splotch',
            'fas fa-spray-can',
            'fas fa-square',
            'fas fa-square-full',
            'fas fa-square-root-alt',
            'fas fa-stamp',
            'fas fa-star',
            'fas fa-star-and-crescent',
            'fas fa-star-half',
            'fas fa-star-half-alt',
            'fas fa-star-of-david',
            'fas fa-star-of-life',
            'fas fa-step-backward',
            'fas fa-step-forward',
            'fas fa-stethoscope',
            'fas fa-sticky-note',
            'fas fa-stop',
            'fas fa-stop-circle',
            'fas fa-stopwatch',
            'fas fa-stopwatch-20',
            'fas fa-store',
            'fas fa-store-alt',
            'fas fa-store-alt-slash',
            'fas fa-store-slash',
            'fas fa-stream',
            'fas fa-street-view',
            'fas fa-strikethrough',
            'fas fa-stroopwafel',
            'fas fa-subscript',
            'fas fa-subway',
            'fas fa-suitcase',
            'fas fa-suitcase-rolling',
            'fas fa-sun',
            'fas fa-superscript',
            'fas fa-surprise',
            'fas fa-swatchbook',
            'fas fa-swimmer',
            'fas fa-swimming-pool',
            'fas fa-synagogue',
            'fas fa-sync',
            'fas fa-sync-alt',
            'fas fa-syringe',
            'fas fa-table',
            'fas fa-table-tennis',
            'fas fa-tablet',
            'fas fa-tablet-alt',
            'fas fa-tablets',
            'fas fa-tachometer-alt',
            'fas fa-tag',
            'fas fa-tags',
            'fas fa-tape',
            'fas fa-tasks',
            'fas fa-taxi',
            'fas fa-teeth',
            'fas fa-teeth-open',
            'fas fa-temperature-high',
            'fas fa-temperature-low',
            'fas fa-tenge',
            'fas fa-terminal',
            'fas fa-text-height',
            'fas fa-text-width',
            'fas fa-th',
            'fas fa-th-large',
            'fas fa-th-list',
            'fas fa-theater-masks',
            'fas fa-thermometer',
            'fas fa-thermometer-empty',
            'fas fa-thermometer-full',
            'fas fa-thermometer-half',
            'fas fa-thermometer-quarter',
            'fas fa-thermometer-three-quarters',
            'fas fa-thumbs-down',
            'fas fa-thumbs-up',
            'fas fa-thumbtack',
            'fas fa-ticket-alt',
            'fas fa-times',
            'fas fa-times-circle',
            'fas fa-tint',
            'fas fa-tint-slash',
            'fas fa-tired',
            'fas fa-toggle-off',
            'fas fa-toggle-on',
            'fas fa-toilet',
            'fas fa-toilet-paper',
            'fas fa-toilet-paper-slash',
            'fas fa-toolbox',
            'fas fa-tools',
            'fas fa-tooth',
            'fas fa-torah',
            'fas fa-torii-gate',
            'fas fa-tractor',
            'fas fa-trademark',
            'fas fa-traffic-light',
            'fas fa-trailer',
            'fas fa-train',
            'fas fa-tram',
            'fas fa-transgender',
            'fas fa-transgender-alt',
            'fas fa-trash',
            'fas fa-trash-alt',
            'fas fa-trash-restore',
            'fas fa-trash-restore-alt',
            'fas fa-tree',
            'fas fa-trophy',
            'fas fa-truck',
            'fas fa-truck-loading',
            'fas fa-truck-monster',
            'fas fa-truck-moving',
            'fas fa-truck-pickup',
            'fas fa-tshirt',
            'fas fa-tty',
            'fas fa-tv',
            'fas fa-umbrella',
            'fas fa-umbrella-beach',
            'fas fa-underline',
            'fas fa-undo',
            'fas fa-undo-alt',
            'fas fa-universal-access',
            'fas fa-university',
            'fas fa-unlink',
            'fas fa-unlock',
            'fas fa-unlock-alt',
            'fas fa-upload',
            'fas fa-user',
            'fas fa-user-alt',
            'fas fa-user-alt-slash',
            'fas fa-user-astronaut',
            'fas fa-user-check',
            'fas fa-user-circle',
            'fas fa-user-clock',
            'fas fa-user-cog',
            'fas fa-user-edit',
            'fas fa-user-friends',
            'fas fa-user-graduate',
            'fas fa-user-injured',
            'fas fa-user-lock',
            'fas fa-user-md',
            'fas fa-user-minus',
            'fas fa-user-ninja',
            'fas fa-user-nurse',
            'fas fa-user-plus',
            'fas fa-user-secret',
            'fas fa-user-shield',
            'fas fa-user-slash',
            'fas fa-user-tag',
            'fas fa-user-tie',
            'fas fa-user-times',
            'fas fa-users',
            'fas fa-users-cog',
            'fas fa-users-slash',
            'fas fa-utensil-spoon',
            'fas fa-utensils',
            'fas fa-vector-square',
            'fas fa-venus',
            'fas fa-venus-double',
            'fas fa-venus-mars',
            'fas fa-vest',
            'fas fa-vest-patches',
            'fas fa-vial',
            'fas fa-vials',
            'fas fa-video',
            'fas fa-video-slash',
            'fas fa-vihara',
            'fas fa-virus',
            'fas fa-virus-slash',
            'fas fa-viruses',
            'fas fa-voicemail',
            'fas fa-volleyball-ball',
            'fas fa-volume-down',
            'fas fa-volume-mute',
            'fas fa-volume-off',
            'fas fa-volume-up',
            'fas fa-vote-yea',
            'fas fa-vr-cardboard',
            'fas fa-walking',
            'fas fa-wallet',
            'fas fa-warehouse',
            'fas fa-water',
            'fas fa-wave-square',
            'fas fa-weight',
            'fas fa-weight-hanging',
            'fas fa-wheelchair',
            'fas fa-wifi',
            'fas fa-wind',
            'fas fa-window-close',
            'fas fa-window-maximize',
            'fas fa-window-minimize',
            'fas fa-window-restore',
            'fas fa-wine-bottle',
            'fas fa-wine-glass',
            'fas fa-wine-glass-alt',
            'fas fa-won-sign',
            'fas fa-wrench',
            'fas fa-x-ray',
            'fas fa-yen-sign',
            'fas fa-yin-yang',

            'far fa-address-book',
            'far fa-address-card',
            'far fa-angry',
            'far fa-arrow-alt-circle-down',
            'far fa-arrow-alt-circle-left',
            'far fa-arrow-alt-circle-right',
            'far fa-arrow-alt-circle-up',
            'far fa-bell',
            'far fa-bell-slash',
            'far fa-bookmark',
            'far fa-building',
            'far fa-calendar',
            'far fa-calendar-alt',
            'far fa-calendar-check',
            'far fa-calendar-minus',
            'far fa-calendar-plus',
            'far fa-calendar-times',
            'far fa-caret-square-down',
            'far fa-caret-square-left',
            'far fa-caret-square-right',
            'far fa-caret-square-up',
            'far fa-chart-bar',
            'far fa-check-circle',
            'far fa-check-square',
            'far fa-circle',
            'far fa-clipboard',
            'far fa-clock',
            'far fa-clone',
            'far fa-closed-captioning',
            'far fa-comment',
            'far fa-comment-alt',
            'far fa-comment-dots',
            'far fa-comments',
            'far fa-compass',
            'far fa-copy',
            'far fa-copyright',
            'far fa-credit-card',
            'far fa-dizzy',
            'far fa-dot-circle',
            'far fa-edit',
            'far fa-envelope',
            'far fa-envelope-open',
            'far fa-eye',
            'far fa-eye-slash',
            'far fa-file',
            'far fa-file-alt',
            'far fa-file-archive',
            'far fa-file-audio',
            'far fa-file-code',
            'far fa-file-excel',
            'far fa-file-image',
            'far fa-file-pdf',
            'far fa-file-powerpoint',
            'far fa-file-video',
            'far fa-file-word',
            'far fa-flag',
            'far fa-flushed',
            'far fa-folder',
            'far fa-folder-open',
            'far fa-frown',
            'far fa-frown-open',
            'far fa-futbol',
            'far fa-gem',
            'far fa-grimace',
            'far fa-grin',
            'far fa-grin-alt',
            'far fa-grin-beam',
            'far fa-grin-beam-sweat',
            'far fa-grin-hearts',
            'far fa-grin-squint',
            'far fa-grin-squint-tears',
            'far fa-grin-stars',
            'far fa-grin-tears',
            'far fa-grin-tongue',
            'far fa-grin-tongue-squint',
            'far fa-grin-tongue-wink',
            'far fa-grin-wink',
            'far fa-hand-lizard',
            'far fa-hand-paper',
            'far fa-hand-peace',
            'far fa-hand-point-down',
            'far fa-hand-point-left',
            'far fa-hand-point-right',
            'far fa-hand-point-up',
            'far fa-hand-pointer',
            'far fa-hand-rock',
            'far fa-hand-scissors',
            'far fa-hand-spock',
            'far fa-handshake',
            'far fa-hdd',
            'far fa-heart',
            'far fa-hospital',
            'far fa-hourglass',
            'far fa-id-badge',
            'far fa-id-card',
            'far fa-image',
            'far fa-images',
            'far fa-keyboard',
            'far fa-kiss',
            'far fa-kiss-beam',
            'far fa-kiss-wink-heart',
            'far fa-laugh',
            'far fa-laugh-beam',
            'far fa-laugh-squint',
            'far fa-laugh-wink',
            'far fa-lemon',
            'far fa-life-ring',
            'far fa-lightbulb',
            'far fa-list-alt',
            'far fa-map',
            'far fa-meh',
            'far fa-meh-blank',
            'far fa-meh-rolling-eyes',
            'far fa-minus-square',
            'far fa-money-bill-alt',
            'far fa-moon',
            'far fa-newspaper',
            'far fa-object-group',
            'far fa-object-ungroup',
            'far fa-paper-plane',
            'far fa-pause-circle',
            'far fa-play-circle',
            'far fa-plus-square',
            'far fa-question-circle',
            'far fa-registered',
            'far fa-sad-cry',
            'far fa-sad-tear',
            'far fa-save',
            'far fa-share-square',
            'far fa-smile',
            'far fa-smile-beam',
            'far fa-smile-wink',
            'far fa-snowflake',
            'far fa-square',
            'far fa-star',
            'far fa-star-half',
            'far fa-sticky-note',
            'far fa-stop-circle',
            'far fa-sun',
            'far fa-surprise',
            'far fa-thumbs-down',
            'far fa-thumbs-up',
            'far fa-times-circle',
            'far fa-tired',
            'far fa-trash-alt',
            'far fa-user',
            'far fa-user-circle',
            'far fa-window-close',
            'far fa-window-maximize',
            'far fa-window-minimize',
            'far fa-window-restore',

            'fab fa-address-book',
            'fab fa-address-card',
            'fab fa-angry',
            'fab fa-arrow-alt-circle-down',
            'fab fa-arrow-alt-circle-left',
            'fab fa-arrow-alt-circle-right',
            'fab fa-arrow-alt-circle-up',
            'fab fa-bell',
            'fab fa-bell-slash',
            'fab fa-bookmark',
            'fab fa-building',
            'fab fa-calendar',
            'fab fa-calendar-alt',
            'fab fa-calendar-check',
            'fab fa-calendar-minus',
            'fab fa-calendar-plus',
            'fab fa-calendar-times',
            'fab fa-caret-square-down',
            'fab fa-caret-square-left',
            'fab fa-caret-square-right',
            'fab fa-caret-square-up',
            'fab fa-chart-bar',
            'fab fa-check-circle',
            'fab fa-check-square',
            'fab fa-circle',
            'fab fa-clipboard',
            'fab fa-clock',
            'fab fa-clone',
            'fab fa-closed-captioning',
            'fab fa-comment',
            'fab fa-comment-alt',
            'fab fa-comment-dots',
            'fab fa-comments',
            'fab fa-compass',
            'fab fa-copy',
            'fab fa-copyright',
            'fab fa-credit-card',
            'fab fa-dizzy',
            'fab fa-dot-circle',
            'fab fa-edit',
            'fab fa-envelope',
            'fab fa-envelope-open',
            'fab fa-eye',
            'fab fa-eye-slash',
            'fab fa-file',
            'fab fa-file-alt',
            'fab fa-file-archive',
            'fab fa-file-audio',
            'fab fa-file-code',
            'fab fa-file-excel',
            'fab fa-file-image',
            'fab fa-file-pdf',
            'fab fa-file-powerpoint',
            'fab fa-file-video',
            'fab fa-file-word',
            'fab fa-flag',
            'fab fa-flushed',
            'fab fa-folder',
            'fab fa-folder-open',
            'fab fa-frown',
            'fab fa-frown-open',
            'fab fa-futbol',
            'fab fa-gem',
            'fab fa-grimace',
            'fab fa-grin',
            'fab fa-grin-alt',
            'fab fa-grin-beam',
            'fab fa-grin-beam-sweat',
            'fab fa-grin-hearts',
            'fab fa-grin-squint',
            'fab fa-grin-squint-tears',
            'fab fa-grin-stars',
            'fab fa-grin-tears',
            'fab fa-grin-tongue',
            'fab fa-grin-tongue-squint',
            'fab fa-grin-tongue-wink',
            'fab fa-grin-wink',
            'fab fa-hand-lizard',
            'fab fa-hand-paper',
            'fab fa-hand-peace',
            'fab fa-hand-point-down',
            'fab fa-hand-point-left',
            'fab fa-hand-point-right',
            'fab fa-hand-point-up',
            'fab fa-hand-pointer',
            'fab fa-hand-rock',
            'fab fa-hand-scissors',
            'fab fa-hand-spock',
            'fab fa-handshake',
            'fab fa-hdd',
            'fab fa-heart',
            'fab fa-hospital',
            'fab fa-hourglass',
            'fab fa-id-badge',
            'fab fa-id-card',
            'fab fa-image',
            'fab fa-images',
            'fab fa-keyboard',
            'fab fa-kiss',
            'fab fa-kiss-beam',
            'fab fa-kiss-wink-heart',
            'fab fa-laugh',
            'fab fa-laugh-beam',
            'fab fa-laugh-squint',
            'fab fa-laugh-wink',
            'fab fa-lemon',
            'fab fa-life-ring',
            'fab fa-lightbulb',
            'fab fa-list-alt',
            'fab fa-map',
            'fab fa-meh',
            'fab fa-meh-blank',
            'fab fa-meh-rolling-eyes',
            'fab fa-minus-square',
            'fab fa-money-bill-alt',
            'fab fa-moon',
            'fab fa-newspaper',
            'fab fa-object-group',
            'fab fa-object-ungroup',
            'fab fa-paper-plane',
            'fab fa-pause-circle',
            'fab fa-play-circle',
            'fab fa-plus-square',
            'fab fa-question-circle',
            'fab fa-registered',
            'fab fa-sad-cry',
            'fab fa-sad-tear',
            'fab fa-save',
            'fab fa-share-square',
            'fab fa-smile',
            'fab fa-smile-beam',
            'fab fa-smile-wink',
            'fab fa-snowflake',
            'fab fa-square',
            'fab fa-star',
            'fab fa-star-half',
            'fab fa-sticky-note',
            'fab fa-stop-circle',
            'fab fa-sun',
            'fab fa-surprise',
            'fab fa-thumbs-down',
            'fab fa-thumbs-up',
            'fab fa-times-circle',
            'fab fa-tired',
            'fab fa-trash-alt',
            'fab fa-user',
            'fab fa-user-circle',
            'fab fa-window-close',
            'fab fa-window-maximize',
            'fab fa-window-minimize',
            'fab fa-window-restore',
           );
        if ( 'line' == $font_type ) {
            return $iconsLA;
        } else {
            return $iconsFA;
        }
    }
}

if (!function_exists('get_fa_icons_full')) {
    function get_fa_icons_full()
    {
        return array(
            'none' => '',
            'glass' => 'f000',
            'remove' => 'f00d',
            'rmb' => 'f157',
            'rotate-right' => 'f01e',
            'send' => 'f1d8',
            'shekel' => 'f20b',
            'shower' => 'f2cc',
            'thermometer-0' => 'f2cb',
            'thermometer-4' => 'f2c7',
            'thermometer-quarter' => 'f2ca',
            'times-rectangle' => 'f2d3',
            'toggle-left' => 'f191',
            'toggle-right' => 'f152',
            'user-circle-o' => 'f2be',
            'vcard-o' => 'f2bc',
            'warning' => 'f071',
            'window-minimize' => 'f2d1',
            'y-combinator-square' => 'f1d4',
            'dedent' => 'f03b',
            'eercast' => 'f2da',
            'envelope-open-o' => 'f2b7',
            'etsy' => 'f2d7',
            'facebook-f' => 'f09a',
            'free-code-camp' => 'f2c5',
            'gears' => 'f085',
            'gittip' => 'f184',
            'grav' => 'f2d6',
            'hand-grab-o' => 'f255',
            'hand-stop-o' => 'f256',
            'hourglass-2' => 'f252',
            'intersex' => 'f224',
            'life-buoy' => 'f1cd',
            'linode' => 'f2b8',
            'microchip' => 'f2db',
            'ravelry' => 'f2d9',
            'rotate-left' => 'f0e2',
            's15' => 'f2cd',
            'superpowers' => 'f2dd',
            'thermometer' => 'f2c7',
            'thermometer-3' => 'f2c8',
            'thermometer-half' => 'f2c9',
            'toggle-down' => 'f150',
            'unlink' => 'f127',
            'user-circle' => 'f2bd',
            'vcard' => 'f2bb',
            'weixin' => 'f1d7',
            'window-maximize' => 'f2d0',
            'won' => 'f159',
            'yc-square' => 'f1d4',
            'automobile' => 'f1b9',
            'bandcamp' => 'f2d5',
            'battery' => 'f240',
            'battery-3' => 'f241',
            'bitcoin' => 'f15a',
            'book' => 'f02d',
            'close' => 'f00d',
            'cny' => 'f157',
            'bar-chart-o' => 'f080',
            'bathtub' => 'f2cd',
            'battery-2' => 'f242',
            'dashboard' => 'f0e4',
            'deafness' => 'f2a4',
            'drivers-license-o' => 'f2c3',
            'edit' => 'f044',
            'envelope-open' => 'f2b6',
            'file-movie-o' => 'f1c8',
            'file-picture-o' => 'f1c5',
            'gear' => 'f013',
            'google-plus-circle' => 'f2b3',
            'hourglass-1' => 'f251',
            'id-card-o' => 'f2c3',
            'legal' => 'f0e3',
            'life-bouy' => 'f1cd',
            'mail-forward' => 'f064',
            'paste' => 'f0ea',
            'photo' => 'f03e',
            'quora' => 'f2c4',
            'euro' => 'f153',
            'fa' => 'f2b4',
            'feed' => 'f09e',
            'file-photo-o' => 'f1c5',
            'file-zip-o' => 'f1c6',
            'hard-of-hearing' => 'f2a4',
            'id-card' => 'f2c2',
            'imdb' => 'f2d8',
            'institution' => 'f19c',
            'life-saver' => 'f1cd',
            'ra' => 'f1d0',
            'reorder' => 'f0c9',
            'resistance' => 'f1d0',
            'rupee' => 'f156',
            'soccer-ball-o' => 'f1e3',
            'sort-down' => 'f0dd',
            'star-half-full' => 'f123',
            'thermometer-2' => 'f2c9',
            'thermometer-full' => 'f2c7',
            'tv' => 'f26c',
            'unsorted' => 'f0dc',
            'user-o' => 'f2c0',
            'window-close-o' => 'f2d4',
            'wpexplorer' => 'f2de',
            'yc' => 'f23b',
            'address-book-o' => 'f2ba',
            'asl-interpreting' => 'f2a3',
            'address-card-o' => 'f2bc',
            'bank' => 'f19c',
            'bars' => 'f0c9',
            'battery-0' => 'f244',
            'battery-4' => 'f240',
            'cab' => 'f1ba',
            'file-sound-o' => 'f1c7',
            'flash' => 'f0e7',
            'group' => 'f0c0',
            'handshake-o' => 'f2b5',
            'hotel' => 'f236',
            'hourglass-3' => 'f253',
            'id-badge' => 'f2c1',
            'image' => 'f03e',
            'mail-reply-all' => 'f122',
            'meetup' => 'f2e0',
            'mobile-phone' => 'f10b',
            'mortar-board' => 'f19d',
            'navicon' => 'f0c9',
            'podcast' => 'f2ce',
            'rouble' => 'f158',
            'ruble' => 'f158',
            'save' => 'f0c7',
            'send-o' => 'f1d9',
            'sheqel' => 'f20b',
            'signing' => 'f2a7',
            'snowflake-o' => 'f2dc',
            'sort-up' => 'f0de',
            'star-half-empty' => 'f123',
            'support' => 'f1cd',
            'telegram' => 'f2c6',
            'thermometer-1' => 'f2ca',
            'thermometer-empty' => 'f2cb',
            'thermometer-three-quarters' => 'f2c8',
            'times-rectangle-o' => 'f2d4',
            'toggle-up' => 'f151',
            'turkish-lira' => 'f195',
            'wechat' => 'f1d7',
            'window-close' => 'f2d3',
            'window-restore' => 'f2d2',
            'yen' => 'f157',
            'address-book' => 'f2b9',
            'bath' => 'f2cd',
            'battery-1' => 'f243',
            'dollar' => 'f155',
            'drivers-license' => 'f2c2',
            'music' => 'f001',
            'search' => 'f002',
            'envelope-o' => 'f003',
            'heart' => 'f004',
            'star' => 'f005',
            'star-o' => 'f006',
            'user' => 'f007',
            'film' => 'f008',
            'th-large' => 'f009',
            'th' => 'f00a',
            'th-list' => 'f00b',
            'check' => 'f00c',
            'times' => 'f00d',
            'search-plus' => 'f00e',
            'search-minus' => 'f010',
            'power-off' => 'f011',
            'signal' => 'f012',
            'cog' => 'f013',
            'trash-o' => 'f014',
            'home' => 'f015',
            'file-o' => 'f016',
            'clock-o' => 'f017',
            'road' => 'f018',
            'download' => 'f019',
            'arrow-circle-o-down' => 'f01a',
            'arrow-circle-o-up' => 'f01b',
            'inbox' => 'f01c',
            'play-circle-o' => 'f01d',
            'repeat' => 'f01e',
            'refresh' => 'f021',
            'list-alt' => 'f022',
            'lock' => 'f023',
            'flag' => 'f024',
            'headphones' => 'f025',
            'volume-off' => 'f026',
            'volume-down' => 'f027',
            'volume-up' => 'f028',
            'qrcode' => 'f029',
            'barcode' => 'f02a',
            'tag' => 'f02b',
            'tags' => 'f02c',
            'television' => 'f02d',
            'bookmark' => 'f02e',
            'print' => 'f02f',
            'camera' => 'f030',
            'font' => 'f031',
            'bold' => 'f032',
            'italic' => 'f033',
            'text-height' => 'f034',
            'text-width' => 'f035',
            'align-left' => 'f036',
            'align-center' => 'f037',
            'align-right' => 'f038',
            'align-justify' => 'f039',
            'list' => 'f03a',
            'outdent' => 'f03b',
            'indent' => 'f03c',
            'video-camera' => 'f03d',
            'picture-o' => 'f03e',
            'pencil' => 'f040',
            'map-marker' => 'f041',
            'adjust' => 'f042',
            'tint' => 'f043',
            'pencil-square-o' => 'f044',
            'share-square-o' => 'f045',
            'check-square-o' => 'f046',
            'arrows' => 'f047',
            'step-backward' => 'f048',
            'fast-backward' => 'f049',
            'backward' => 'f04a',
            'play' => 'f04b',
            'pause' => 'f04c',
            'stop' => 'f04d',
            'forward' => 'f04e',
            'fast-forward' => 'f050',
            'step-forward' => 'f051',
            'eject' => 'f052',
            'chevron-left' => 'f053',
            'chevron-right' => 'f054',
            'plus-circle' => 'f055',
            'minus-circle' => 'f056',
            'times-circle' => 'f057',
            'check-circle' => 'f058',
            'question-circle' => 'f059',
            'info-circle' => 'f05a',
            'crosshairs' => 'f05b',
            'times-circle-o' => 'f05c',
            'check-circle-o' => 'f05d',
            'ban' => 'f05e',
            'arrow-left' => 'f060',
            'arrow-right' => 'f061',
            'arrow-up' => 'f062',
            'arrow-down' => 'f063',
            'share' => 'f064',
            'expand' => 'f065',
            'compress' => 'f066',
            'plus' => 'f067',
            'minus' => 'f068',
            'asterisk' => 'f069',
            'exclamation-circle' => 'f06a',
            'gift' => 'f06b',
            'leaf' => 'f06c',
            'fire' => 'f06d',
            'eye' => 'f06e',
            'eye-slash' => 'f070',
            'exclamation-triangle' => 'f071',
            'plane' => 'f072',
            'calendar' => 'f073',
            'random' => 'f074',
            'comment' => 'f075',
            'magnet' => 'f076',
            'chevron-up' => 'f077',
            'chevron-down' => 'f078',
            'retweet' => 'f079',
            'shopping-cart' => 'f07a',
            'folder' => 'f07b',
            'folder-open' => 'f07c',
            'arrows-v' => 'f07d',
            'arrows-h' => 'f07e',
            'bar-chart' => 'f080',
            'twitter-square' => 'f081',
            'facebook-square' => 'f082',
            'camera-retro' => 'f083',
            'key' => 'f084',
            'cogs' => 'f085',
            'comments' => 'f086',
            'thumbs-o-up' => 'f087',
            'thumbs-o-down' => 'f088',
            'star-half' => 'f089',
            'heart-o' => 'f08a',
            'sign-out' => 'f08b',
            'linkedin-square' => 'f08c',
            'thumb-tack' => 'f08d',
            'external-link' => 'f08e',
            'sign-in' => 'f090',
            'trophy' => 'f091',
            'github-square' => 'f092',
            'upload' => 'f093',
            'lemon-o' => 'f094',
            'phone' => 'f095',
            'square-o' => 'f096',
            'bookmark-o' => 'f097',
            'phone-square' => 'f098',
            'twitter' => 'f099',
            'facebook' => 'f09a',
            'github' => 'f09b',
            'unlock' => 'f09c',
            'credit-card' => 'f09d',
            'rss' => 'f09e',
            'hdd-o' => 'f0a0',
            'bullhorn' => 'f0a1',
            'bell' => 'f0f3',
            'certificate' => 'f0a3',
            'hand-o-right' => 'f0a4',
            'hand-o-left' => 'f0a5',
            'hand-o-up' => 'f0a6',
            'hand-o-down' => 'f0a7',
            'arrow-circle-left' => 'f0a8',
            'arrow-circle-right' => 'f0a9',
            'arrow-circle-up' => 'f0aa',
            'arrow-circle-down' => 'f0ab',
            'globe' => 'f0ac',
            'wrench' => 'f0ad',
            'tasks' => 'f0ae',
            'filter' => 'f0b0',
            'briefcase' => 'f0b1',
            'arrows-alt' => 'f0b2',
            'users' => 'f0c0',
            'link' => 'f0c1',
            'cloud' => 'f0c2',
            'flask' => 'f0c3',
            'scissors' => 'f0c4',
            'files-o' => 'f0c5',
            'paperclip' => 'f0c6',
            'floppy-o' => 'f0c7',
            'square' => 'f0c8',
            'bars' => 'f0c9',
            'list-ul' => 'f0ca',
            'list-ol' => 'f0cb',
            'strikethrough' => 'f0cc',
            'underline' => 'f0cd',
            'table' => 'f0ce',
            'magic' => 'f0d0',
            'truck' => 'f0d1',
            'pinterest' => 'f0d2',
            'pinterest-square' => 'f0d3',
            'google-plus-square' => 'f0d4',
            'google-plus' => 'f0d5',
            'money' => 'f0d6',
            'caret-down' => 'f0d7',
            'caret-up' => 'f0d8',
            'caret-left' => 'f0d9',
            'caret-right' => 'f0da',
            'columns' => 'f0db',
            'sort' => 'f0dc',
            'sort-desc' => 'f0dd',
            'sort-asc' => 'f0de',
            'envelope' => 'f0e0',
            'linkedin' => 'f0e1',
            'undo' => 'f0e2',
            'gavel' => 'f0e3',
            'tachometer' => 'f0e4',
            'comment-o' => 'f0e5',
            'comments-o' => 'f0e6',
            'bolt' => 'f0e7',
            'sitemap' => 'f0e8',
            'umbrella' => 'f0e9',
            'clipboard' => 'f0ea',
            'lightbulb-o' => 'f0eb',
            'exchange' => 'f0ec',
            'cloud-download' => 'f0ed',
            'cloud-upload' => 'f0ee',
            'user-md' => 'f0f0',
            'stethoscope' => 'f0f1',
            'suitcase' => 'f0f2',
            'bell-o' => 'f0a2',
            'coffee' => 'f0f4',
            'cutlery' => 'f0f5',
            'file-text-o' => 'f0f6',
            'building-o' => 'f0f7',
            'hospital-o' => 'f0f8',
            'ambulance' => 'f0f9',
            'medkit' => 'f0fa',
            'fighter-jet' => 'f0fb',
            'beer' => 'f0fc',
            'h-square' => 'f0fd',
            'plus-square' => 'f0fe',
            'angle-double-left' => 'f100',
            'angle-double-right' => 'f101',
            'angle-double-up' => 'f102',
            'angle-double-down' => 'f103',
            'angle-left' => 'f104',
            'angle-right' => 'f105',
            'angle-up' => 'f106',
            'angle-down' => 'f107',
            'desktop' => 'f108',
            'laptop' => 'f109',
            'tablet' => 'f10a',
            'mobile' => 'f10b',
            'circle-o' => 'f10c',
            'quote-left' => 'f10d',
            'quote-right' => 'f10e',
            'spinner' => 'f110',
            'circle' => 'f111',
            'reply' => 'f112',
            'github-alt' => 'f113',
            'folder-o' => 'f114',
            'folder-open-o' => 'f115',
            'smile-o' => 'f118',
            'frown-o' => 'f119',
            'meh-o' => 'f11a',
            'gamepad' => 'f11b',
            'keyboard-o' => 'f11c',
            'flag-o' => 'f11d',
            'flag-checkered' => 'f11e',
            'terminal' => 'f120',
            'code' => 'f121',
            'reply-all' => 'f122',
            'star-half-o' => 'f123',
            'location-arrow' => 'f124',
            'crop' => 'f125',
            'code-fork' => 'f126',
            'chain-broken' => 'f127',
            'question' => 'f128',
            'info' => 'f129',
            'exclamation' => 'f12a',
            'superscript' => 'f12b',
            'subscript' => 'f12c',
            'eraser' => 'f12d',
            'puzzle-piece' => 'f12e',
            'microphone' => 'f130',
            'microphone-slash' => 'f131',
            'shield' => 'f132',
            'calendar-o' => 'f133',
            'fire-extinguisher' => 'f134',
            'rocket' => 'f135',
            'maxcdn' => 'f136',
            'chevron-circle-left' => 'f137',
            'chevron-circle-right' => 'f138',
            'chevron-circle-up' => 'f139',
            'chevron-circle-down' => 'f13a',
            'html5' => 'f13b',
            'css3' => 'f13c',
            'anchor' => 'f13d',
            'unlock-alt' => 'f13e',
            'bullseye' => 'f140',
            'ellipsis-h' => 'f141',
            'ellipsis-v' => 'f142',
            'rss-square' => 'f143',
            'play-circle' => 'f144',
            'ticket' => 'f145',
            'minus-square' => 'f146',
            'minus-square-o' => 'f147',
            'level-up' => 'f148',
            'level-down' => 'f149',
            'check-square' => 'f14a',
            'pencil-square' => 'f14b',
            'external-link-square' => 'f14c',
            'share-square' => 'f14d',
            'compass' => 'f14e',
            'caret-square-o-down' => 'f150',
            'caret-square-o-up' => 'f151',
            'caret-square-o-right' => 'f152',
            'eur' => 'f153',
            'gbp' => 'f154',
            'usd' => 'f155',
            'inr' => 'f156',
            'jpy' => 'f157',
            'rub' => 'f158',
            'krw' => 'f159',
            'btc' => 'f15a',
            'file' => 'f15b',
            'file-text' => 'f15c',
            'sort-alpha-asc' => 'f15d',
            'sort-alpha-desc' => 'f15e',
            'sort-amount-asc' => 'f160',
            'sort-amount-desc' => 'f161',
            'sort-numeric-asc' => 'f162',
            'sort-numeric-desc' => 'f163',
            'thumbs-up' => 'f164',
            'thumbs-down' => 'f165',
            'youtube-square' => 'f166',
            'youtube' => 'f167',
            'xing' => 'f168',
            'xing-square' => 'f169',
            'youtube-play' => 'f16a',
            'dropbox' => 'f16b',
            'stack-overflow' => 'f16c',
            'instagram' => 'f16d',
            'flickr' => 'f16e',
            'adn' => 'f170',
            'bitbucket' => 'f171',
            'bitbucket-square' => 'f172',
            'tumblr' => 'f173',
            'tumblr-square' => 'f174',
            'long-arrow-down' => 'f175',
            'long-arrow-up' => 'f176',
            'long-arrow-left' => 'f177',
            'long-arrow-right' => 'f178',
            'apple' => 'f179',
            'windows' => 'f17a',
            'android' => 'f17b',
            'linux' => 'f17c',
            'dribbble' => 'f17d',
            'skype' => 'f17e',
            'foursquare' => 'f180',
            'trello' => 'f181',
            'female' => 'f182',
            'male' => 'f183',
            'gratipay' => 'f184',
            'sun-o' => 'f185',
            'moon-o' => 'f186',
            'archive' => 'f187',
            'bug' => 'f188',
            'vk' => 'f189',
            'weibo' => 'f18a',
            'renren' => 'f18b',
            'pagelines' => 'f18c',
            'stack-exchange' => 'f18d',
            'arrow-circle-o-right' => 'f18e',
            'arrow-circle-o-left' => 'f190',
            'caret-square-o-left' => 'f191',
            'dot-circle-o' => 'f192',
            'wheelchair' => 'f193',
            'vimeo-square' => 'f194',
            'try' => 'f195',
            'plus-square-o' => 'f196',
            'space-shuttle' => 'f197',
            'slack' => 'f198',
            'envelope-square' => 'f199',
            'wordpress' => 'f19a',
            'openid' => 'f19b',
            'university' => 'f19c',
            'graduation-cap' => 'f19d',
            'yahoo' => 'f19e',
            'google' => 'f1a0',
            'reddit' => 'f1a1',
            'reddit-square' => 'f1a2',
            'stumbleupon-circle' => 'f1a3',
            'stumbleupon' => 'f1a4',
            'delicious' => 'f1a5',
            'digg' => 'f1a6',
            'pied-piper-pp' => 'f1a7',
            'pied-piper-alt' => 'f1a8',
            'drupal' => 'f1a9',
            'joomla' => 'f1aa',
            'language' => 'f1ab',
            'fax' => 'f1ac',
            'building' => 'f1ad',
            'child' => 'f1ae',
            'paw' => 'f1b0',
            'spoon' => 'f1b1',
            'cube' => 'f1b2',
            'cubes' => 'f1b3',
            'behance' => 'f1b4',
            'behance-square' => 'f1b5',
            'steam' => 'f1b6',
            'steam-square' => 'f1b7',
            'recycle' => 'f1b8',
            'car' => 'f1b9',
            'taxi' => 'f1ba',
            'tree' => 'f1bb',
            'spotify' => 'f1bc',
            'deviantart' => 'f1bd',
            'soundcloud' => 'f1be',
            'database' => 'f1c0',
            'file-pdf-o' => 'f1c1',
            'file-word-o' => 'f1c2',
            'file-excel-o' => 'f1c3',
            'file-powerpoint-o' => 'f1c4',
            'file-image-o' => 'f1c5',
            'file-archive-o' => 'f1c6',
            'file-audio-o' => 'f1c7',
            'file-video-o' => 'f1c8',
            'file-code-o' => 'f1c9',
            'vine' => 'f1ca',
            'codepen' => 'f1cb',
            'jsfiddle' => 'f1cc',
            'life-ring' => 'f1cd',
            'circle-o-notch' => 'f1ce',
            'rebel' => 'f1d0',
            'empire' => 'f1d1',
            'git-square' => 'f1d2',
            'git' => 'f1d3',
            'hacker-news' => 'f1d4',
            'tencent-weibo' => 'f1d5',
            'qq' => 'f1d6',
            'question-circle-o' => 'f1d7',
            'paper-plane' => 'f1d8',
            'paper-plane-o' => 'f1d9',
            'history' => 'f1da',
            'circle-thin' => 'f1db',
            'header' => 'f1dc',
            'paragraph' => 'f1dd',
            'sliders' => 'f1de',
            'share-alt' => 'f1e0',
            'share-alt-square' => 'f1e1',
            'bomb' => 'f1e2',
            'futbol-o' => 'f1e3',
            'tty' => 'f1e4',
            'binoculars' => 'f1e5',
            'plug' => 'f1e6',
            'slideshare' => 'f1e7',
            'twitch' => 'f1e8',
            'yelp' => 'f1e9',
            'newspaper-o' => 'f1ea',
            'wifi' => 'f1eb',
            'calculator' => 'f1ec',
            'paypal' => 'f1ed',
            'google-wallet' => 'f1ee',
            'cc-visa' => 'f1f0',
            'cc-mastercard' => 'f1f1',
            'cc-discover' => 'f1f2',
            'cc-amex' => 'f1f3',
            'cc-paypal' => 'f1f4',
            'cc-stripe' => 'f1f5',
            'bell-slash' => 'f1f6',
            'bell-slash-o' => 'f1f7',
            'trash' => 'f1f8',
            'copyright' => 'f1f9',
            'at' => 'f1fa',
            'eyedropper' => 'f1fb',
            'paint-brush' => 'f1fc',
            'birthday-cake' => 'f1fd',
            'area-chart' => 'f1fe',
            'pie-chart' => 'f200',
            'line-chart' => 'f201',
            'lastfm' => 'f202',
            'lastfm-square' => 'f203',
            'toggle-off' => 'f204',
            'toggle-on' => 'f205',
            'bicycle' => 'f206',
            'bus' => 'f207',
            'ioxhost' => 'f208',
            'angellist' => 'f209',
            'cc' => 'f20a',
            'ils' => 'f20b',
            'meanpath' => 'f20c',
            'buysellads' => 'f20d',
            'connectdevelop' => 'f20e',
            'dashcube' => 'f210',
            'forumbee' => 'f211',
            'leanpub' => 'f212',
            'sellsy' => 'f213',
            'shirtsinbulk' => 'f214',
            'simplybuilt' => 'f215',
            'skyatlas' => 'f216',
            'cart-plus' => 'f217',
            'cart-arrow-down' => 'f218',
            'diamond' => 'f219',
            'ship' => 'f21a',
            'user-secret' => 'f21b',
            'motorcycle' => 'f21c',
            'street-view' => 'f21d',
            'heartbeat' => 'f21e',
            'venus' => 'f221',
            'mars' => 'f222',
            'mercury' => 'f223',
            'transgender' => 'f224',
            'transgender-alt' => 'f225',
            'venus-double' => 'f226',
            'mars-double' => 'f227',
            'venus-mars' => 'f228',
            'mars-stroke' => 'f229',
            'mars-stroke-v' => 'f22a',
            'mars-stroke-h' => 'f22b',
            'neuter' => 'f22c',
            'genderless' => 'f22d',
            'facebook-official' => 'f230',
            'pinterest-p' => 'f231',
            'whatsapp' => 'f232',
            'server' => 'f233',
            'user-plus' => 'f234',
            'user-times' => 'f235',
            'bed' => 'f236',
            'viacoin' => 'f237',
            'train' => 'f238',
            'subway' => 'f239',
            'medium' => 'f23a',
            'y-combinator' => 'f23b',
            'optin-monster' => 'f23c',
            'opencart' => 'f23d',
            'expeditedssl' => 'f23e',
            'battery-full' => 'f240',
            'battery-three-quarters' => 'f241',
            'battery-half' => 'f242',
            'battery-quarter' => 'f243',
            'battery-empty' => 'f244',
            'mouse-pointer' => 'f245',
            'i-cursor' => 'f246',
            'object-group' => 'f247',
            'object-ungroup' => 'f248',
            'sticky-note' => 'f249',
            'sticky-note-o' => 'f24a',
            'cc-jcb' => 'f24b',
            'cc-diners-club' => 'f24c',
            'clone' => 'f24d',
            'balance-scale' => 'f24e',
            'hourglass-o' => 'f250',
            'hourglass-start' => 'f251',
            'hourglass-half' => 'f252',
            'hourglass-end' => 'f253',
            'hourglass' => 'f254',
            'hand-rock-o' => 'f255',
            'hand-paper-o' => 'f256',
            'hand-scissors-o' => 'f257',
            'hand-lizard-o' => 'f258',
            'hand-spock-o' => 'f259',
            'hand-pointer-o' => 'f25a',
            'hand-peace-o' => 'f25b',
            'trademark' => 'f25c',
            'registered' => 'f25d',
            'creative-commons' => 'f25e',
            'gg' => 'f260',
            'gg-circle' => 'f261',
            'tripadvisor' => 'f262',
            'odnoklassniki' => 'f263',
            'odnoklassniki-square' => 'f264',
            'get-pocket' => 'f265',
            'wikipedia-w' => 'f266',
            'safari' => 'f267',
            'chrome' => 'f268',
            'firefox' => 'f269',
            'opera' => 'f26a',
            'internet-explorer' => 'f26b',
            'television' => 'f26c',
            'contao' => 'f26d',
            '500px' => 'f26e',
            'amazon' => 'f270',
            'calendar-plus-o' => 'f271',
            'calendar-minus-o' => 'f272',
            'calendar-times-o' => 'f273',
            'calendar-check-o' => 'f274',
            'industry' => 'f275',
            'map-pin' => 'f276',
            'map-signs' => 'f277',
            'map-o' => 'f278',
            'map' => 'f279',
            'commenting' => 'f27a',
            'commenting-o' => 'f27b',
            'houzz' => 'f27c',
            'vimeo' => 'f27d',
            'black-tie' => 'f27e',
            'fonticons' => 'f280',
            'reddit-alien' => 'f281',
            'edge' => 'f282',
            'credit-card-alt' => 'f283',
            'codiepie' => 'f284',
            'modx' => 'f285',
            'fort-awesome' => 'f286',
            'usb' => 'f287',
            'product-hunt' => 'f288',
            'mixcloud' => 'f289',
            'scribd' => 'f28a',
            'pause-circle' => 'f28b',
            'pause-circle-o' => 'f28c',
            'stop-circle' => 'f28d',
            'stop-circle-o' => 'f28e',
            'shopping-bag' => 'f290',
            'shopping-basket' => 'f291',
            'hashtag' => 'f292',
            'bluetooth' => 'f293',
            'bluetooth-b' => 'f294',
            'percent' => 'f295',
            'gitlab' => 'f296',
            'wpbeginner' => 'f297',
            'wpforms' => 'f298',
            'envira' => 'f299',
            'universal-access' => 'f29a',
            'wheelchair-alt' => 'f29b',
            'question-circle-o' => 'f29c',
            'blind' => 'f29d',
            'audio-description' => 'f29e',
            'volume-control-phone' => 'f2a0',
            'braille' => 'f2a1',
            'assistive-listening-systems' => 'f2a2',
            'american-sign-language-interpreting' => 'f2a3',
            'deaf' => 'f2a4',
            'glide' => 'f2a5',
            'glide-g' => 'f2a6',
            'sign-language' => 'f2a7',
            'low-vision' => 'f2a8',
            'viadeo' => 'f2a9',
            'viadeo-square' => 'f2aa',
            'snapchat' => 'f2ab',
            'snapchat-ghost' => 'f2ac',
            'snapchat-square' => 'f2ad',
            'pied-piper' => 'f2ae',
            'first-order' => 'f2b0',
            'yoast' => 'f2b1',
            'themeisle' => 'f2b2',
            'google-plus-official' => 'f2b3',
            'font-awesome' => 'f2b4',


        );
    }
}

if (!function_exists('get_cat_icon')) {
    function get_cat_icon($term_id)
    {
        $icon = get_term_meta($term_id, 'category_icon', true);
        return !empty($icon) ? $icon : '';
    }
}

/**
 * @since 5.3.0
 */

if (!function_exists('atbdp_icon_type')) {
    function atbdp_icon_type($echo = false)
    {
        $font_type = get_directorist_option('font_type', 'line');
        $font_type = ('line' === $font_type) ? "la la" : "fa fa";
        if ($echo) {
            echo $font_type;
        } else {
            return $font_type;
        }
    }
}

function directorist_icon( $icon, $echo = true ) {
    if ( !$icon ) {
        return;
    }

    $html = sprintf('<i class="directorist-icon %s"></i>', $icon );

    if ($echo) {
        echo $html;
    }
    else {
        return $html;
    }
}

if ( ! function_exists( 'atbdp_get_term_icon' ) ) {
    function atbdp_get_term_icon( array $args = [] )
    {
        $default = [ 'icon' => '', 'default' => 'la la-folder-open', 'echo' => false ];
        $args = array_merge( $default, $args );

        $icon = ( ! empty($args['icon'] ) ) ?  'la ' . $args['icon'] : $args['default'];
        $icon = ( ! empty( $icon ) ) ?  'la ' . $icon : '';
        $icon = ( ! empty( $icon ) ) ? '<span class="'. $icon .'"></span>' : $icon;

        if ( ! $args['echo'] ) { return $icon; }

        echo $icon;
    }
}


if (!function_exists('atbdp_sanitize_array')) {
    /**
     * It sanitize a multi-dimensional array
     * @param array &$array The array of the data to sanitize
     * @return mixed
     */
    function atbdp_sanitize_array(&$array)
    {

        foreach ($array as &$value) {
            if (!is_array($value)) {
                // sanitize if value is not an array
                $value = sanitize_text_field($value);
            } else {
                // go inside this function again
                atbdp_sanitize_array($value);
            }
        }
        return $array;
    }
}

if (!function_exists('is_directoria_active')) {
    /**
     * It checks if the Directorist theme is installed currently.
     * @return bool It returns true if the directorist theme is active currently. False otherwise.
     */
    function is_directoria_active()
    {
        return wp_get_theme()->get_stylesheet() === 'directoria';
    }
}

if (!function_exists('is_multiple_images_active')) {
    /**
     * It checks if the Directorist Multiple images Extension is active and enabled
     * @return bool It returns true if the Directorist Multiple images Extension is active and enabled
     */
    function is_multiple_images_active()
    {

        return true; // plugin is active and enabled
    }
}


if (!function_exists('is_business_hour_active')) {
    /**
     * It checks if the Directorist Business Hour Extension is active and enabled
     * @return bool It returns true if the Directorist Business Hour Extension is active and enabled
     */
    function is_business_hour_active()
    {
        $enable = get_directorist_option('enable_business_hour');
        if ($enable && class_exists('BD_Business_Hour')) {
            return true;
        }
    }
}

if (!function_exists('is_empty_v')) {
    /**
     * It checks if the value of the given data ( array or string etc ) is empty
     * @param array $value The value to check if it is empty
     * @return bool It returns true if the value of the given data is empty, and false otherwise.
     */
    function is_empty_v($value)
    {
        if (!is_array($value)) return empty($value);
        foreach ($value as $key => $val) {
            if (!empty($val))
                return false;
        }
        return true;
    }
}

if (!function_exists('atbdp_get_paged_num')) {
    /**
     * Get current page number for the pagination.
     *
     * @return    int    $paged    The current page number for the pagination.
     * @since    1.0.0
     *
     */
    function atbdp_get_paged_num()
    {

        global $paged;

        if (get_query_var('paged')) {
            $paged = get_query_var('paged');
        } else if (get_query_var('page')) {
            $paged = get_query_var('page');
        } else {
            $paged = 1;
        }

        return absint($paged);

    }


}

if (!function_exists('valid_js_nonce')) {
    /**
     * It checks if the nonce is set and valid
     * @return bool it returns true if the nonce is valid and false otherwise
     */
    function valid_js_nonce()
    {
        if (!empty($_POST['atbdp_nonce_js']) && (wp_verify_nonce($_POST['atbdp_nonce_js'], 'atbdp_nonce_action_js')))
            return true;
        return false;
    }
}

if (!function_exists('atbdp_get_featured_settings_array')) {
    /**
     * It fetch all the settings related to featured listing.
     * @return array it returns an array of settings related to featured listings.
     */
    function atbdp_get_featured_settings_array()
    {
        return array(
            'active' => get_directorist_option('enable_featured_listing'),
            'label' => get_directorist_option('featured_listing_title'),
            'desc' => get_directorist_option('featured_listing_desc'),
            'price' => get_directorist_option('featured_listing_price'),
        );
    }
}

if (!function_exists('atbdp_only_logged_in_user')) {

    /**
     * It informs a user to logged in and returns false if the user is not logged in.
     * if a user is not logged in.
     * @param string $message
     * @return bool It returns true if a user is logged in and false otherwise. Besides, it display a message to non-logged in users
     */
    function atbdp_is_user_logged_in($message = '')
    {
        if (!atbdp_logged_in_user()) {
            // user not logged in;
            $error_message = (empty($message))
                ? sprintf(__('You need to be logged in to view the content of this page. You can login %s. Don\'t have an account? %s', 'directorist'), apply_filters("atbdp_login_page_link", "<a href='" . ATBDP_Permalink::get_login_page_link() . "'> " . __('Here', 'directorist') . "</a>"), apply_filters("atbdp_signup_page_link", "<a href='" . ATBDP_Permalink::get_registration_page_link() . "'> " . __('Sign up', 'directorist') . "</a>"))
                : $message;
            $container_fluid = is_directoria_active() ? 'container' : 'container-fluid';
            ?>
            <section class="directory_wrapper single_area">
                <div class="<?php echo apply_filters('atbdp_login_message_container_fluid', $container_fluid) ?>">
                    <div class="row">
                        <div class="col-md-12">
                            <?php ATBDP()->helper->show_login_message($error_message); ?>
                        </div>
                    </div>
                </div> <!--ends container-fluid-->
            </section>
            <?php
            return false;
        }
        return true;
    }
}

if (!function_exists('atbdp_get_months')) {
    /**
     * Get an array of translatable month names
     * @return array
     * @since    3.1.0
     */
    function atbdp_get_months()
    {
        return array(
            __("Jan", 'directorist'),
            __("Feb", 'directorist'),
            __("Mar", 'directorist'),
            __("Apr", 'directorist'),
            __("May", 'directorist'),
            __("Jun", 'directorist'),
            __("Jul", 'directorist'),
            __("Aug", 'directorist'),
            __("Sep", 'directorist'),
            __("Oct", 'directorist'),
            __("Nov", 'directorist'),
            __("Dec", 'directorist')
        );
    }
}

if (!function_exists('calc_listing_expiry_date')) {
    /**
     * Calculate listing expiry date from the given date
     *
     * @param string $start_date Date from which the expiry date should be calculated.
     * @return   string   $date          It returns expiry date in the mysql date format
     * @since    3.1.0
     *
     */
    function calc_listing_expiry_date($start_date = NULL, $expire = NULL)
    {
        $exp_days = get_term_meta( default_directory_type(), 'default_expiration', true );
        $expired_date = !empty($expire) ? $expire : $exp_days;
        // Current time
        $start_date = !empty($start_date) ? $start_date : current_time('mysql');
        // Calculate new date
        $date = new DateTime($start_date);
        $date->add(new DateInterval("P{$expired_date}D")); // set the interval in days
        return $date->format('Y-m-d H:i:s');

    }
}

if (!function_exists('get_date_in_mysql_format')) {
    /**
     * It converts a date array to MySQL date format (Y-m-d H:i:s).
     *
     * @param array $date Array of date values.
     * eg. array(
     * 'year'  => 0,
     * 'month' => 0,
     * 'day'   => 0,
     * 'hour'  => 0,
     * 'min'   => 0,
     * 'sec'   => 0
     * );
     * @return   string   $date    Formatted MySQL date string.
     * @since    3.1.0
     *
     */
    function get_date_in_mysql_format($date)
    {

        $defaults = array(
            'year' => 0,
            'month' => 0,
            'day' => 0,
            'hour' => 0,
            'min' => 0,
            'sec' => 0
        );
        $date = wp_parse_args($date, $defaults);

        $year = (int)$date['year'];
        $year = str_pad($year, 4, '0', STR_PAD_RIGHT);

        $month = (int)$date['month'];
        $month = max(1, min(12, $month));

        $day = (int)$date['day'];
        $day = max(1, min(31, $day));

        $hour = (int)$date['hour'];
        $hour = max(1, min(24, $hour));

        $min = (int)$date['min'];
        $min = max(0, min(59, $min));

        $sec = (int)$date['sec'];
        $sec = max(0, min(59, $sec));

        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $min, $sec);

    }
}

if (!function_exists('atbdp_parse_mysql_date')) {
    /**
     * Parse MySQL date format.
     *
     * @param string $date MySQL date string.
     * @return   array     $date    Array of date values.
     * @since    3.1.0
     *
     */
    function atbdp_parse_mysql_date($date)
    {

        $date = preg_split('([^0-9])', $date);

        return array(
            'year' => $date[0],
            'month' => $date[1],
            'day' => $date[2],
            'hour' => $date[3],
            'min' => $date[4],
            'sec' => $date[5]
        );

    }
}

if (!function_exists('currency_has_decimal')) {
    /**
     * Check if currency has decimals.
     * @param string $currency
     * @return bool
     */
    function currency_has_decimals($currency)
    {
        if (in_array($currency, array('RIAL', 'SAR', 'HUF', 'JPY', 'TWD'))) {
            return false;
        }

        return true;
    }
}

/**
 * Print formatted Price inside a p tag
 *
 * @param int|string $price The price amount to display
 * @param bool $disable_price whether displaying price is enabled or disabled
 * @param string $currency The name of the currency
 * @param string $symbol currency symbol
 * @param string $c_position currency position
 * @param bool $echo Whether to Print value or to Return value. Default is printing value.
 * @return mixed
 */
function atbdp_display_price($price = '', $disable_price = false, $currency = '', $symbol = '', $c_position = '', $echo = true)
{
    if (empty($price) || $disable_price) return null; // vail if the price is empty or price display is disabled.

    $allow_decimal = get_directorist_option('allow_decimal', 1);
    $before = '';
    $after = '';
    if (empty($c_position)) {
        $c_position = get_directorist_option('g_currency_position');
    }
    if (empty($currency)) {
        $currency = get_directorist_option('g_currency', 'USD');
    }
    if (empty($symbol)) {
        $symbol = atbdp_currency_symbol($currency);
    }

    ('after' == $c_position) ? $after = $symbol : $before = $symbol;
    $price = $before . atbdp_format_amount($price, $allow_decimal) . $after;
    $p = sprintf("<span class='directorist-listing-price'>%s</span>", $price);
    if ($echo) {
        echo $p;
    } else {
        return $p;
    }

}

/**
 * Print formatted Price inside a p tag
 *
 *
 * @return mixed
 */
function atbdp_display_price_range($price_range)
{
    $currency = get_directorist_option('g_currency', 'USD');
    $c_symbol = atbdp_currency_symbol($currency);
    if (empty($price_range)) return null;
    $output = '';
    if ('skimming' == $price_range) {
        $output =
            '<span class="atbd_meta atbd_listing_average_pricing atbd_tooltip" aria-label="Skimming"><span class="atbd_active">' . $c_symbol . '</span><span class="atbd_active">' . $c_symbol . '</span><span class="atbd_active">' . $c_symbol . '</span><span class="atbd_active">' . $c_symbol . '</span>
        </span>';
    } elseif ('moderate' == $price_range) {
        $output =
            '<span class="atbd_meta atbd_listing_average_pricing atbd_tooltip" aria-label="Moderate"><span class="atbd_active">' . $c_symbol . '</span><span class="atbd_active">' . $c_symbol . '</span><span class="atbd_active">' . $c_symbol . '</span><span>' . $c_symbol . '</span>
            </span>';
    } elseif ('economy' == $price_range) {
        $output =
            '<span class="atbd_meta atbd_listing_average_pricing atbd_tooltip" aria-label="Economy"><span class="atbd_active">' . $c_symbol . '</span><span class="atbd_active">' . $c_symbol . '</span><span>' . $c_symbol . '</span><span>' . $c_symbol . '</span>
        </span>';
    } elseif ('bellow_economy' == $price_range) {

        $output =
            '<span class="atbd_meta atbd_listing_average_pricing atbd_tooltip" aria-label="Cheap"><span class="atbd_active">' . $c_symbol . '</span><span>' . $c_symbol . '</span><span>' . $c_symbol . '</span><span>' . $c_symbol . '</span>
        </span>';

    }
    return $output;

}


/**
 * Get total listings count.
 *
 * @param int $term_id Custom Taxonomy term ID.
 * @return   int                    Listings count.
 * @since    4.0.0
 *
 */
function atbdp_listings_count_by_category( $term_id, $lisitng_type = '' )
{
    $args = array(
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'post_type'      => ATBDP_POST_TYPE,
        'post_status'    => 'publish',
    );

    if( ! empty( $lisitng_type ) ) {
        $args['tax_query'] = array(
            'relation' => 'AND',
            array(
                'taxonomy' => ATBDP_CATEGORY,
                'field' => 'term_id',
                'terms' => $term_id,
                'include_children' => true
            ),
            array(
                'taxonomy' => ATBDP_TYPE,
                'field' => 'term_id',
                'terms' => (int) $lisitng_type,
            )
        );
    } else {
        $args['tax_query'] = array(
            array(
                'taxonomy' => ATBDP_CATEGORY,
                'field' => 'term_id',
                'terms' => $term_id,
                'include_children' => true
            )
        );
    }

    $total_categories = ATBDP_Listings_Data_Store::get_listings( $args );

    return count( $total_categories );
}

/**
 * List ACADP categories.
 *
 * @param array $settings Settings args.
 * @return   string                 HTML code that contain categories list.
 * @since    1.0.0
 *
 */
function atbdp_list_categories($settings)
{

    if ($settings['depth'] <= 0) {
        return;
    }

    $args = array(
        'orderby' => $settings['orderby'],
        'order' => $settings['order'],
        'hide_empty' => !empty($settings['hide_empty']) ? 1 : 0,
        'parent' => $settings['term_id'],
        'hierarchical' => false
    );

    $terms = get_terms(ATBDP_CATEGORY, $args);
    $html = '';

    if (count($terms) > 0) {

        --$settings['depth'];

        $html .= '<ul class="list-unstyled atbdp_child_category">';

        foreach ($terms as $term) {
            $settings['term_id'] = $term->term_id;
            $child_category = get_term_children($term->term_id, ATBDP_CATEGORY);
            $plus_icon = !empty($child_category) ? '<span class="expander">+</span>' : '';
            $count = 0;
            if (!empty($settings['hide_empty']) || !empty($settings['show_count'])) {
                $count = atbdp_listings_count_by_category($term->term_id);

                if (!empty($settings['hide_empty']) && 0 == $count) continue;
            }

            $html .= '<li>';
            $html .= '<a href=" ' . ATBDP_Permalink::atbdp_get_category_page($term) . ' ">';
            $html .= $term->name;
            if (!empty($settings['show_count'])) {
                $html .= ' (' . $count . ')';
            }
            $html .= "</a>$plus_icon";
            $html .= atbdp_list_categories($settings);
            $html .= '</li>';
        }

        $html .= '</ul>';

    }

    return $html;
}

/**
 * Get total listings count.
 *
 * @param int $term_id Custom Taxonomy term ID.
 * @return   int                    Listings count.
 * @since    4.0.0
 *
 */
function atbdp_listings_count_by_location( $term_id, $lisitng_type = '' )
{
    $args = array(
        'fields' => 'ids',
        'posts_per_page' => -1,
        'post_type' => ATBDP_POST_TYPE,
        'post_status' => 'publish',
    );

    if( ! empty( $lisitng_type ) ) {
        $args['tax_query'] = array(
            'relation' => 'AND',
            array(
                'taxonomy' => ATBDP_LOCATION,
                'field' => 'term_id',
                'terms' => $term_id,
                'include_children' => true
            ),
            array(
                'taxonomy' => ATBDP_TYPE,
                'field' => 'term_id',
                'terms' => (int) $lisitng_type,
            )
        );
    } else {
        $args['tax_query'] = array(
            array(
                'taxonomy' => ATBDP_LOCATION,
                'field' => 'term_id',
                'terms' => $term_id,
                'include_children' => true
            )
        );
    }

    $total_location = ATBDP_Listings_Data_Store::get_listings( $args );
    return count( $total_location );
}

/**
 * List ACADP categories.
 *
 * @param array $settings Settings args.
 * @return   string                 HTML code that contain categories list.
 * @since    1.0.0
 *
 */
function atbdp_list_locations($settings)
{

    if ($settings['depth'] <= 0) {
        return;
    }

    $args = array(
        'orderby' => $settings['orderby'],
        'order' => $settings['order'],
        'hide_empty' => !empty($settings['hide_empty']) ? 1 : 0,
        'parent' => $settings['term_id'],
        'hierarchical' => false
    );

    $terms = get_terms(ATBDP_LOCATION, $args);

    $html = '';

    if (count($terms) > 0) {

        --$settings['depth'];

        $html .= '<ul class="list-unstyled atbdp_child_category">';

        foreach ($terms as $term) {
            $settings['term_id'] = $term->term_id;
            $child_category = get_term_children($term->term_id, ATBDP_LOCATION);
            $plus_icon = !empty($child_category) ? '<span class="expander">+</span>' : '';
            $count = 0;
            if (!empty($settings['hide_empty']) || !empty($settings['show_count'])) {
                $count = atbdp_listings_count_by_location($term->term_id);

                if (!empty($settings['hide_empty']) && 0 == $count) continue;
            }

            $html .= '<li>';
            $html .= '<a href=" ' . ATBDP_Permalink::atbdp_get_location_page($term) . ' ">';
            $html .= $term->name;
            if (!empty($settings['show_count'])) {
                $html .= ' (' . $count . ')';
            }
            $html .= "</a>$plus_icon";
            $html .= atbdp_list_locations($settings);
            $html .= '</li>';
        }

        $html .= '</ul>';

    }

    return $html;
}

/**
 * Get total listings count.
 *
 * @param int $term_id Custom Taxonomy term ID.
 * @return   int                    Listings count.
 * @since    4.0.0
 *
 */
function atbdp_listings_count_by_tag($term_id)
{

    $args = array(
        'fields' => 'ids',
        'posts_per_page' => -1,
        'post_type' => ATBDP_POST_TYPE,
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => ATBDP_TAGS,
                'field' => 'term_id',
                'terms' => $term_id,
                'include_children' => true
            )
        ),
        'meta_query' => apply_filters('atbdp_listings_with_tag_meta_query', array(
            'relation' => 'OR',
            array(
                'key' => '_expiry_date',
                'value' => current_time('mysql'),
                'compare' => '>', // eg. expire date 6 <= current date 7 will return the post
                'type' => 'DATETIME'
            ),
            array(
                'key' => '_never_expire',
                'value' => 1,
            ),
        ))
    );

    return count(get_posts($args));

}

/**
 * List ACADP categories.
 *
 * @param array $settings Settings args.
 * @return   string                 HTML code that contain categories list.
 * @since    1.0.0
 *
 */
function atbdp_list_tags($settings)
{

    if ($settings['depth'] <= 0) {
        return;
    }

    $args = array(
        'orderby' => $settings['orderby'],
        'order' => $settings['order'],
        'hide_empty' => !empty($settings['hide_empty']) ? 1 : 0,
        'parent' => $settings['term_id'],
        'hierarchical' => false
    );

    $terms = get_terms(ATBDP_TAGS, $args);

    $html = '';

    if (count($terms) > 0) {

        --$settings['depth'];

        $html .= '<ul class="list-unstyled">';

        foreach ($terms as $term) {
            $settings['term_id'] = $term->term_id;

            $count = 0;
            if (!empty($settings['hide_empty']) || !empty($settings['show_count'])) {
                $count = atbdp_listings_count_by_tag($term->term_id);

                if (!empty($settings['hide_empty']) && 0 == $count) continue;
            }

            $html .= '<li>';
            $html .= '<a href=" ' . ATBDP_Permalink::get_tag_archive($settings['term']) . ' ">';
            $html .= $term->name;
            if (!empty($settings['show_count'])) {
                $html .= ' (' . $count . ')';
            }
            $html .= '</a>';
            $html .= atbdp_list_tags($settings);
            $html .= '</li>';
        }

        $html .= '</ul>';

    }

    return $html;
}

/**
 * Get the current listings order.
 *
 * @param string $default_order Default Order.
 * @return   string    $order            Listings Order.
 * @since    4.0
 *
 */
function atbdp_get_listings_current_order($default_order = '')
{

    $order = $default_order;

    if (isset($_GET['sort'])) {
        $order = sanitize_text_field($_GET['sort']);
    } else if (isset($_GET['order'])) {
        $order = sanitize_text_field($_GET['order']);
    }

    return apply_filters('atbdp_get_listings_current_order', $order);

}

/**
 * Get orderby list.
 *
 * @return   array    $options    A list of the orderby options.
 * @since    1.0.0
 *
 */
function atbdp_get_listings_orderby_options($sort_by_items)
{
    $options = array(
        'title-asc' => __("A to Z (title)", 'directorist'),
        'title-desc' => __("Z to A (title)", 'directorist'),
        'date-desc' => __("Latest listings", 'directorist'),
        'date-asc' => __("Oldest listings", 'directorist'),
        'views-desc' => __("Popular listings", 'directorist'),
        'price-asc' => __("Price (low to high)", 'directorist'),
        'price-desc' => __("Price (high to low)", 'directorist'),
        'rand' => __("Random listings", 'directorist'),
    );
    $sort_by_items 	= is_array( $sort_by_items ) ? $sort_by_items : [];
    if (!in_array('a_z', $sort_by_items)) {
        unset($options['title-asc']);
    }
    if (!in_array('z_a', $sort_by_items)) {
        unset($options['title-desc']);
    }
    if (!in_array('latest', $sort_by_items)) {
        unset($options['date-desc']);
    }
    if (!in_array('oldest', $sort_by_items)) {
        unset($options['date-asc']);
    }
    if (!in_array('popular', $sort_by_items)) {
        unset($options['views-desc']);
    }
    if (!in_array('price_low_high', $sort_by_items)) {
        unset($options['price-asc']);
    }
    if (!in_array('price_high_low', $sort_by_items)) {
        unset($options['price-desc']);
    }
    if (!in_array('random', $sort_by_items)) {
        unset($options['rand']);
    }
    $args = array(
        'post_type'   => ATBDP_POST_TYPE,
        'post_status' => 'publish',
        'meta_key'    => '_price'
    );

    $values = new WP_Query($args);
    $prices = array();
    if ($values->have_posts()) {
        while ($values->have_posts()) {
            $values->the_post();
            $prices[] = get_post_meta(get_the_ID(), '_price', true);
        }
        $has_price = join($prices);
    }
    $disabled_price_by_admin = get_directorist_option('disable_list_price', 0);
    if ($disabled_price_by_admin || empty($has_price)) {
        unset($options['price-asc'], $options['price-desc']);
    }

    return apply_filters('atbdp_get_listings_orderby_options', $options);

}

/**
 * Get the listing view.
 *
 * @param string $view Default View.
 * @return   string    $view    Grid or List.
 * @since    4.0.0
 *
 */
function atbdp_get_listings_current_view_name($view)
{


    if (isset($_GET['view'])) {
        $view = sanitize_text_field($_GET['view']);
    }

    $allowed_views = array('list', 'grid', 'map');
    if (class_exists('BD_Map_View')) {
        array_push($allowed_views, 'listings_with_map');
    }
    if (!in_array($view, $allowed_views)) {
        $listing_view = get_directorist_option('default_listing_view');
        $listings_settings = !empty($listing_view) ? $listing_view : 'grid';
        $view = $listings_settings;
    }


    return $view;

}

function atbdp_calculate_column( $number ) {
    switch( $number ) {
        case 1:
            $columns = 12;
            break;
        case 2:
            $columns = 6;
            break;
        case 3:
            $columns = 4;
            break;
        case 4:
            $columns = 3;
            break;
        case 5:
            $columns = 2;
            break;
        case 6:
            $columns = 2;
            break;
            default:
            $columns = 3;
    }

    return $columns;
}

/**
 * Get the list of listings view options.
 *
 * @return   array    $view_options    List of view Options.
 * @since    4.0.0
 *
 */
function atbdp_get_listings_view_options($view_as_items)
{
    $listing_view = get_directorist_option('default_listing_view');
    $listings_settings = !empty($listing_view) ? $listing_view : 'grid';

    $options = array('grid', 'list', 'map');
    $display_map = get_directorist_option('display_map_field', 1);
    $select_listing_map = get_directorist_option('select_listing_map', 'google');

    $view_as_items = is_array( $view_as_items ) ? $view_as_items : [];
    if (!in_array('listings_grid', $view_as_items)) {
        unset($options[0]);
    }
    if (!in_array('listings_list', $view_as_items)) {
        unset($options[1]);
    }
    if (empty($display_map) || !in_array('listings_map', $view_as_items)) {
        unset($options[2]);
    }
    $options[] = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : $listings_settings;
    $options = array_unique($options);

    $views = array();

    foreach ($options as $option) {

        switch ($option) {
            case 'list' :
                $views[$option] = __('List', 'directorist');
                break;
            case 'grid' :
                $views[$option] = __('Grid', 'directorist');
                break;
            case 'map' :
                $views[$option] = __('Map', 'directorist');
                break;
        }

    }

    return $views;

}

/**
 * @param $var
 * @return array|string
 */
function atbdp_get_view_as($view)
{
    $views = atbdp_get_listings_view_options($view);
    $ways = '';
    foreach ($views as $value => $label) {
        $active_class = ($view == $value) ? ' active' : '';
        $ways = sprintf('<a class="dropdown-item%s" href="%s">%s</a>', $active_class, add_query_arg('view', $value), $label);

    }
    return $ways;


}

/*
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function directorist_clean($var)
{
    if (is_array($var)) {
        return array_map('directorist_clean', $var);
    } else {
        return is_scalar($var) ? sanitize_text_field($var) : $var;
    }
}

/**
 * Display the favourites link.
 *
 * @param int $post_id Post ID.
 * @return   mixed        Included the favourites and unfavourites button
 * @since    4.0
 *
 */
function the_atbdp_favourites_link($post_id = 0)
{
    if (atbdp_logged_in_user()) {
        if ($post_id == 0) {
            global $post;
            $post_id = $post->ID;
        }
        $favourites = (array)get_user_meta(get_current_user_id(), 'atbdp_favourites', true);
        if (in_array($post_id, $favourites)) {
            return '<span class="' . atbdp_icon_type() . '-heart" style="color: red"></span><a href="javascript:void(0)" class="atbdp-favourites" data-post_id="' . $post_id . '"></a>';
        } else {
            return '<span class="' . atbdp_icon_type() . '-heart"></span><a href="javascript:void(0)" class="atbdp-favourites" data-post_id="' . $post_id . '"></a>';
        }
    } else {
        return '<a href="javascript:void(0)" class="atbdp-require-login"><span class="' . atbdp_icon_type() . '-heart"></span></a>';
    }
}


function atbdp_listings_mark_as_favourite($listing_id)
{
    $favourites = (array)get_user_meta(get_current_user_id(), 'atbdp_favourites', true);
    $fav_class = '';
    if (in_array($listing_id, $favourites)) {
        $fav_class = 'atbdp_fav_isActive';
    }
    $mark_as_fav_link = '<div class="atbdp_add_to_fav_listings"><a class="atbdp_mark_as_fav ' . $fav_class . '" id="atbdp-fav_' . $listing_id . '" data-listing_id="' . $listing_id . '" href=""><span class="atbd_fav_icon"></span><span class="atbd_fav_tooltip"></span></a></div>';
    return $mark_as_fav_link;
}

/**
 * Generate a permalink to remove from favourites.
 *
 * @param int $listing_id Listing ID.
 * @return   string                   URL to remove from favourites.
 * @since    1.0.0
 *
 */
function atbdp_get_remove_favourites_page_link($listing_id)
{

    $link = add_query_arg(array('atbdp_action' => 'remove-favourites', 'atbdp_listing' => $listing_id));

    return $link;

}


/**
 * Display the favourites link.
 *
 * @param int $post_id Post ID.
 * @since    4.0
 *
 */
/*function the_atbdp_favourites_all_listing($post_id = 0)
{

    if (atbdp_logged_in_user()) {

        if ($post_id == 0) {
            global $post;
            $post_id = $post->ID;
        }

        $favourites = (array)get_user_meta(get_current_user_id(), 'atbdp_favourites', true);
        if (in_array($post_id, $favourites)) {
            echo '<a href="javascript:void(0)" class="atbdp-favourites-all-listing" data-post_id="' . $post_id . '"><span style="color: red" class="fa fa-heart"></span></a>';
        } else {
            echo '<a href="javascript:void(0)" class="atbdp-favourites-all-listing" data-post_id="' . $post_id . '"><span class="fa fa-heart"></span></a>';

        }

    } else {

        echo '<a href="javascript:void(0)" class="atbdp-require-login"><span class="fa fa-heart"></span></a>';

    }

}*/

/*
 * to get the new badge
 * @return $
 */


if (!function_exists('new_badge')) {
    function new_badge()
    {
        global $post;
        $new_listing_time = get_directorist_option('new_listing_day');
        $new_badge_text = get_directorist_option('new_badge_text', 'New');
        $each_hours = 60 * 60 * 24; // seconds in a day
        $s_date1 = strtotime(current_time('mysql')); // seconds for date 1
        $s_date2 = strtotime($post->post_date); // seconds for date 2
        $s_date_diff = abs($s_date1 - $s_date2); // different of the two dates in seconds
        $days = round($s_date_diff / $each_hours); // divided the different with second in a day
        $new = '<span class="atbd_badge atbd_badge_new">' . $new_badge_text . '</span>';
        if ($days <= (int)$new_listing_time) {
             return $new;

        }
    }
}

/**
 * Generate image crop.
 *
 * @param string $attachmentId Image Url.
 * @param int $width Image Width.
 * @param int $height Image Height.
 * @param bool $crop cropping condition.
 * @param int $quality Quality.
 * @return   array  $resizer return resize.
 * @since    4.0.0
 *
 */
function atbdp_image_cropping($attachmentId, $width, $height, $crop = true, $quality = 100)
{
    $resizer = new Atbdp_Image_resizer($attachmentId);

    return $resizer->resize($width, $height, $crop, $quality);
}

if (!function_exists('is_fee_manager_active')) {
    /**
     * It checks is user purchased plan included in that feature.
     * @return bool It returns true if the above mentioned exists.
     */
    function is_fee_manager_active()
    {
        $FM_disabled_byAdmin = get_directorist_option('fee_manager_enable', 1);
        $WFM_disabled_byAdmin = get_directorist_option('woo_pricing_plans_enable', 1);
        if (class_exists('ATBDP_Pricing_Plans') && $FM_disabled_byAdmin) {
            return true;
        } elseif (class_exists('DWPP_Pricing_Plans') && $WFM_disabled_byAdmin) {
            return true;
        } else {
            return false;
        }

    }
}

if ( ! function_exists( 'atbdp_pricing_plan_is_enabled' ) ) :
    // atbdp_pricing_plan_is_enabled
    function atbdp_pricing_plan_is_enabled() {
        $pricing_plan_is_enabled = get_directorist_option('fee_manager_enable', 1);

        if ( class_exists('ATBDP_Pricing_Plans') && $pricing_plan_is_enabled) {
            return true;
        }

        return false;
    }
endif;

if ( ! function_exists( 'atbdp_wc_pricing_plan_is_enabled' ) ) :
    // atbdp_wc_pricing_plan_is_enabled
    function atbdp_wc_pricing_plan_is_enabled() {
        $wc_pricing_plan_is_enabled = get_directorist_option('woo_pricing_plans_enable', 1);

        if ( class_exists('DWPP_Pricing_Plans') && $wc_pricing_plan_is_enabled) {
            return true;
        }

        return false;
    }
endif;

if (!function_exists('atbdp_deactivate_reasons')) {
    /**
     * Reasons for deactivate plugin
     * @since 4.4.0
     */
    function atbdp_deactivate_reasons()
    {
        $reasons = array(
            array(
                'id' => 'could-not-understand',
                'text' => 'I couldn\'t understand how to make it work',
                'type' => 'textarea',
                'placeholder' => 'Would you like us to assist you?'
            ),
            array(
                'id' => 'found-better-plugin',
                'text' => 'I found a better plugin',
                'type' => 'text',
                'placeholder' => 'What\'s the plugin\'s name?'
            ),
            array(
                'id' => 'not-have-that-feature',
                'text' => 'The plugin is great, but I need specific feature that you don\'t support',
                'type' => 'textarea',
                'placeholder' => 'Could you tell us more about that feature?'
            ),
            array(
                'id' => 'is-not-working',
                'text' => 'I couldn\'t get the plugin not to work ',
                'type' => 'textarea',
                'placeholder' => 'Could you tell us a bit more whats not working?'
            ),
            array(
                'id' => 'looking-for-other',
                'text' => 'It\'s not what I was looking for',
                'type' => '',
                'placeholder' => ''
            ),
            array(
                'id' => 'did-not-work-as-expected',
                'text' => 'The plugin didn\'t work as expected',
                'type' => 'textarea',
                'placeholder' => 'What did you expect?'
            ),
            array(
                'id' => 'other',
                'text' => 'Other',
                'type' => 'textarea',
                'placeholder' => 'Could you tell us a bit more?'
            ),
        );

        return $reasons;
    }


}

/**
 * Check that page is.
 *
 * @param string $atbdppages The page type.
 *
 * @return bool If valid returns true. Otherwise false.
 * @since   1.5.7 Updated to validate buddypress dashboard listings page as a author page.
 * @package atbdpectory
 * @global object $wp_query WordPress Query object.
 * @global object $post The current post object.
 *
 * @since   1.0.0
 * @since   1.5.6 Added to check GD invoices and GD checkout pages.
 */
function atbdp_is_page($atbdppages = '')
{
    global $post;

    $atbdppages = preg_replace( '/[-]/', '_', $atbdppages );

    switch ($atbdppages):
        case 'home':
            if (is_page() && get_the_ID() == get_directorist_option('search_listing')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_search_listing')) {
                return true;
            }
            break;
        case 'search_result':
            if (is_page() && get_the_ID() == get_directorist_option('search_result_page')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_search_result')) {
                return true;
            }
            break;
        case 'add_listing':
            if (is_page() && get_the_ID() == get_directorist_option('add_listing_page')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_add_listing')) {
                return true;
            }
            break;
        case 'all_listing':
            if (is_page() && get_the_ID() == get_directorist_option('all_listing_page')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_all_listing')) {
                return true;
            }
            break;
        case 'dashboard':
            if (is_page() && get_the_ID() == get_directorist_option('user_dashboard')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_user_dashboard')) {
                return true;
            }
            break;
        case 'author':
            if (is_page() && get_the_ID() == get_directorist_option('author_profile_page')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_author_profile')) {
                return true;
            }
            break;
        case 'category':
            if (is_page() && get_the_ID() == get_directorist_option('all_categories_page')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_all_categories')) {
                return true;
            }
            break;
        case 'single_listing':
            return is_singular('at_biz_dir');
            break;
        case 'single_category':
            if (is_page() && get_the_ID() == get_directorist_option('single_category_page')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_category')) {
                return true;
            }
            break;
        case 'all_locations':
            if (is_page() && get_the_ID() == get_directorist_option('all_locations_page')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_all_locations')) {
                return true;
            }
            break;
        case 'single_location':
            if (is_page() && get_the_ID() == get_directorist_option('single_location_page')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_location')) {
                return true;
            }
            break;
        case 'single_tag':
            if (is_page() && get_the_ID() == get_directorist_option('single_tag_page')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_tag')) {
                return true;
            }
            break;
        case 'registration':
            if (is_page() && get_the_ID() == get_directorist_option('custom_registration')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_custom_registration')) {
                return true;
            }
            break;
        case 'login':
            if (is_page() && get_the_ID() == get_directorist_option('user_login')) {
                return true;
            } elseif (is_page() && isset($post->post_content) && has_shortcode($post->post_content, 'directorist_user_login')) {
                return true;
            }
            break;

    endswitch;

    //endif;

    return false;
}

/**
 * @param $listing_id
 * @return integer $pop_listing_id
 * @since 4.7.8
 */
if (!function_exists('atbdp_popular_listings')) {
    function atbdp_popular_listings($listing_id)
    {
        $listing_popular_by = get_directorist_option('listing_popular_by');
        $average = ATBDP()->review->get_average($listing_id);
        $average_review_for_popular = get_directorist_option('average_review_for_popular', 4);
        $view_count = get_post_meta($listing_id, '_atbdp_post_views_count', true);
        $view_to_popular = get_directorist_option('views_for_popular');
        if ('average_rating' === $listing_popular_by) {
            if ($average_review_for_popular <= $average) {
                return $pop_listing_id = $listing_id;
            }
        } elseif ('view_count' === $listing_popular_by) {
            if ((int)$view_count >= (int)$view_to_popular) {
                return $pop_listing_id = $listing_id;
            }
        } else {
            if (($average_review_for_popular <= $average) && ((int)$view_count >= (int)$view_to_popular)) {
                return $pop_listing_id = $listing_id;
            }
        }
    }
}

/**
 * Outputs the directorist categories/locations dropdown.
 *
 * @param array $args Array of options to control the field output.
 * @param bool $echo Whether to echo or just return the string.
 * @return   string             HTML attribute or empty string.
 * @since    1.5.5
 *
 */
function bdas_dropdown_terms($args = array(), $echo = true)
{

    // Vars
    $args = array_merge(array(
        'show_option_none' => '-- ' . __('Select a category', 'directorist') . ' --',
        'option_none_value' => '',
        'taxonomy' => 'at_biz_dir-category',
        'name' => 'bdas_category',
        'class' => 'form-control',
        'required' => false,
        'base_term' => 0,
        'parent' => 0,
        'orderby' => 'name',
        'order' => 'ASC',
        'selected' => 0,
    ), $args);

    if (!empty($args['selected'])) {
        $ancestors = get_ancestors($args['selected'], $args['taxonomy']);
        $ancestors = array_merge(array_reverse($ancestors), array($args['selected']));
    } else {
        $ancestors = array();
    }

    // Build data
    $html = '';

    if (isset($args['walker'])) {

        $selected = count($ancestors) >= 2 ? (int)$ancestors[1] : 0;

        $html .= '<div class="bdas-terms">';
        $html .= sprintf('<input type="hidden" name="%s" class="bdas-term-hidden" value="%d" />', $args['name'], $selected);

        $term_args = array(
            'show_option_none' => $args['show_option_none'],
            'option_none_value' => $args['option_none_value'],
            'taxonomy' => $args['taxonomy'],
            'child_of' => $args['parent'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'selected' => $selected,
            'hierarchical' => true,
            'depth' => 2,
            'show_count' => false,
            'hide_empty' => false,
            'walker' => $args['walker'],
            'echo' => 0
        );

        unset($args['walker']);

        $select = wp_dropdown_categories($term_args);
        $required = $args['required'] ? ' required' : '';
        $replace = sprintf('<select class="%s" data-taxonomy="%s" data-parent="%d"%s>', $args['class'], $args['taxonomy'], $args['parent'], $required);

        $html .= preg_replace('#<select([^>]*)>#', $replace, $select);

        if ($selected > 0) {
            $args['parent'] = $selected;
            $html .= bdas_dropdown_terms($args, false);
        }

        $html .= '</div>';

    } else {

        $has_children = 0;
        $child_of = 0;

        $term_args = array(
            'parent' => $args['parent'],
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
            'hierarchical' => false,
        );
        $terms = get_terms($args['taxonomy'], $term_args);

        if (!empty($terms) && !is_wp_error($terms)) {

            if ($args['parent'] == $args['base_term']) {
                $required = $args['required'] ? ' required' : '';

                $html .= '<div class="bdas-terms">';
                $html .= sprintf('<input type="hidden" name="%s" class="bdas-term-hidden" value="%d" />', $args['name'], $args['selected']);
                $html .= sprintf('<select class="%s" data-taxonomy="%s" data-parent="%d"%s>', $args['class'], $args['taxonomy'], $args['parent'], $required);
                $html .= sprintf('<option value="%s">%s</option>', $args['option_none_value'], $args['show_option_none']);
            } else {
                $category_placeholder = apply_filters('atbdp_search_sub_category_placeholder', __('Select a Sub Category','directorist') );
                $location_placeholder = apply_filters('atbdp_search_sub_location_placeholder', __('Select a Sub Location','directorist') );
                $placeholder = ( $args['taxonomy'] == 'at_biz_dir-location') ? $location_placeholder : $category_placeholder;
                $html .= sprintf('<div class="bdas-child-terms bdas-child-terms-%d">', $args['parent']);
                $html .= sprintf('<select class="%s" data-taxonomy="%s" data-parent="%d">', $args['class'], $args['taxonomy'], $args['parent']);
                $html .= sprintf('<option value="%d">%s</option>', $args['parent'], $placeholder);
            }

            foreach ($terms as $term) {
                $selected = '';
                if (in_array($term->term_id, $ancestors)) {
                    $has_children = 1;
                    $child_of = $term->term_id;
                    $selected = ' selected';
                } else if ($term->term_id == $args['selected']) {
                    $selected = ' selected';
                }

                $html .= sprintf('<option value="%s"%s>%s</option>', $term->term_id, $selected, $term->name);
            }

            $html .= '</select>';
            if ($has_children) {
                $args['parent'] = $child_of;
                $html .= bdas_dropdown_terms($args, false);
            }
            $html .= '</div>';

        } else {

            if ($args['parent'] == $args['base_term']) {
                $required = $args['required'] ? ' required' : '';

                $html .= '<div class="bdas-terms">';
                $html .= sprintf('<input type="hidden" name="%s" class="bdas-term-hidden" value="%d" />', $args['name'], $args['selected']);
                $html .= sprintf('<select class="%s" data-taxonomy="%s" data-parent="%d"%s>', $args['class'], $args['taxonomy'], $args['parent'], $required);
                $html .= sprintf('<option value="%s">%s</option>', $args['option_none_value'], $args['show_option_none']);
                $html .= '</select>';
                $html .= '</div>';
            }

        }

    }

    // Echo or Return
    if ($echo) {
        echo $html;
        return '';
    } else {
        return $html;
    }

}

function atbdp_get_custom_field_ids($category = 0, $all = false)
{
    // Get global fields
    $args = array(
        'post_type'      => ATBDP_CUSTOM_FIELD_POST_TYPE,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );

    if( !$all ){
        $args['meta_query'] = array(
            array(
                'key'   => 'associate',
                'value' => 'form'
            ),
        );
    }

    // Get category fields
    if ( $category > 0 ) {
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'   => 'associate',
                'value' => 'form'
            ),

            array(
                'relation' => 'AND',
                array(
                    'key'     => 'category_pass',
                    'value'   => $category,
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => 'associate',
                    'value'   => 'categories',
                    'compare' => 'LIKE',
                ),
            ),
        );
    }

    $field_ids = ATBDP_Cache_Helper::get_the_transient([
        'group'      => 'atbdp_custom_field_query',
        'name'       => 'atbdp_custom_field_ids',
        'query_args' => $args,
        'cache'      => apply_filters( 'atbdp_cache_custom_field_ids', true ),
        'value'      => function( $data ) {
            return get_posts( $data['query_args'] );
        }
    ]);

    // Return
    if (empty($field_ids)) {
        $field_ids = array(0);
    }

    return $field_ids;

}

function get_advance_search_result_page_link()
{

    $link = home_url();

    if (get_option('permalink_structure')) {

        $page_settings = get_directorist_option('advance_search_result');;

        if ($page_settings > 0) {
            $link = get_permalink($page_settings);
        }

    }

    return $link;
}

/**
 * @return Wp_Query
 * @since 1.0.0
 */
if (!function_exists('get_atbdp_listings_ids')) {
    function get_atbdp_listings_ids()
    {
        $arg = array(
            'post_type'      => 'at_biz_dir',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids'
        );

        $query = new WP_Query( $arg );
        return $query;
    }
}

/**
 * @return Wp_Query
 * @since 4.7.7
 */
if (!function_exists('atbdp_get_expired_listings')) {
    function atbdp_get_expired_listings($texonomy, $categories)
    {
        return new WP_Query(array());
    }
}

/**
 * Get current address bar URL.
 *
 * @return   string    Current Page URL.
 * @since    5.4.0
 *
 */
function atbdp_get_current_url()
{

    $current_url = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "https://" : "http://";
    $current_url .= $_SERVER["SERVER_NAME"];
    if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443") {
        $current_url .= ":" . $_SERVER["SERVER_PORT"];
    }
    $current_url .= $_SERVER["REQUEST_URI"];

    return $current_url;

}

/**
 * Check if Yoast SEO plugin is active and Directorist can use that.
 *
 * @return bool $can_use_yoast "true" if can use Yoast, "false" if not.
 * @since 5.4.4
 *
 */
function atbdp_can_use_yoast()
{

    $can_use_yoast  = false;
    $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

    $yoast_free_is_active    = ( in_array('wordpress-seo/wp-seo.php', $active_plugins) ) ? true : false;
    $yoast_premium_is_active = ( in_array('wordpress-seo-premium/wp-seo-premium.php', $active_plugins) ) ? true : false;

    if ( $yoast_free_is_active || $yoast_premium_is_active ) {
        $can_use_yoast = true;
    }

    return $can_use_yoast;

}

// atbdp_yoast_is_active
function atbdp_yoast_is_active() {
    return atbdp_can_use_yoast();
}

/**arg
 *
 * @return bool $can_use_yoast "true" if can use Yoast, "false" if not.
 * @since 5.5.2
 *
 */
function atbdp_can_overwrite_yoast()
{
    $overwrite = false;
    $overwrite_yoast = get_directorist_option('overwrite_by_yoast');
    if ( ! empty( $overwrite_yoast ) || ! atbdp_yoast_is_active() ) {
        $overwrite = true;
    }

    return $overwrite;

}

function atbdp_disable_overwrite_yoast() {
    atbdp_can_overwrite_yoast();
}

if ( ! function_exists( 'directorist_is_active_rankmath' ) ) {
    function directorist_is_active_rankmath() {
        
        return class_exists( 'RankMath' );
    }
}


if (!function_exists('atbdp_page')) {
    function atbdp_page()
    {
        $pages = array(
            get_directorist_option('search_listing'), get_directorist_option('search_result_page'), get_directorist_option('add_listing_page'), get_directorist_option('all_listing_page'), get_directorist_option('all_categories_page'), get_directorist_option('single_category_page'), get_directorist_option('all_locations_page'), get_directorist_option('single_location_page'), get_directorist_option('single_tag_page'), get_directorist_option('author_profile_page'), get_directorist_option('user_dashboard'), get_directorist_option('custom_registration'), get_directorist_option('user_login'), get_directorist_option('checkout_page'), get_directorist_option('payment_receipt_page'), get_directorist_option('transaction_failure_page'),
        );
        foreach ($pages as $page) {
            return $page;
        }
    }
}
/**
 * @param $id
 * @param $tax
 * @return integer  Return the level of the term
 * @since 5.5.4
 */
function atbdp_get_tax_level($id, $tax)
{
    $ancestors = get_ancestors($id, $tax);
    return count($ancestors) + 1;
}

/**
 * @param $data
 * @since 5.6.5
 */
function send_review_for_approval($data)
{
    $listing_id = $data['post_id'];
    $review_id = wp_insert_post(array(
        'post_content' => '',
        'post_title' => get_the_title($listing_id),
        'post_status' => 'publish',
        'post_type' => 'atbdp_listing_review',
        'comment_status' => false,
    ));
    update_post_meta($review_id, '_review_listing', $listing_id);
    $listing_reviewer = $data['name'];
    update_post_meta($review_id, '_listing_reviewer', $listing_reviewer);
    update_post_meta($review_id, '_review_status', 'pending');
    $reviewer_details = $data['content'];
    update_post_meta($review_id, '_reviewer_details', $reviewer_details);
    $reviewer_rating = $data['rating'];
    update_post_meta($review_id, '_reviewer_rating', $reviewer_rating);

    $post_id = $data['post_id'];
    update_post_meta($review_id, '_post_id', $post_id);

    $email = $data['email'];
    update_post_meta($review_id, '_email', $email);

    $by_guest = $data['by_guest'];
    update_post_meta($review_id, '_by_guest', $by_guest);

    $by_user_id = $data['by_user_id'];
    update_post_meta($review_id, '_by_user_id', $by_user_id);
    //wp_send_json_success(array('id'=>$data));
    /* $message = array('error' => 0);
     $message['approve'] = 'plan';
     wp_send_json_success(array('id'=>$message));*/
}

/**
 * @since 5.7.1
 * check is user already submitted review for this listing
 */
if (!function_exists('tract_duplicate_review')) {
    function tract_duplicate_review($reviewer, $listing)
    {
        $args = [
            'post_type' => 'atbdp_listing_review',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_listing_reviewer',
                    'value' => $reviewer,
                ),
                array(
                    'key' => '_review_listing',
                    'value' => $listing,
                ),
                array(
                    'key' => '_review_status',
                    'value' => 'pending',
                )
            )
        ];


        $reviews = new WP_Query( $args );

        $review_meta = array();
        foreach ($reviews->posts as $key => $val) {
            $review_meta[] = !empty($val) ? $val : array();
        }

        return ( $review_meta ) ? $review_meta : false;
    }
}

function search_category_location_filter($settings, $taxonomy_id, $prefix = '')
{
    if ($settings['immediate_category']) {

        if ($settings['term_id'] > $settings['parent'] && !in_array($settings['term_id'], $settings['ancestors'])) {
            return;
        }

    }
    if (ATBDP_CATEGORY == $taxonomy_id) {
        $category_slug = get_query_var('atbdp_category');
        $category = get_term_by('slug', $category_slug, ATBDP_CATEGORY);
        $category_id = !empty($category->term_id) ? $category->term_id : '';
        $term_id = isset($_GET['in_cat']) ? $_GET['in_cat'] : $category_id;
    } else {
        $location_slug = get_query_var('atbdp_location');
        $location = get_term_by('slug', $location_slug, ATBDP_LOCATION);
        $location_id = !empty($location->term_id) ? $location->term_id : '';
        $term_id = isset($_GET['in_loc']) ? $_GET['in_loc'] : $location_id;
    }

    $args =  array(
        'orderby'      => $settings['orderby'],
        'order'        => $settings['order'],
        'hide_empty'   => $settings['hide_empty'],
        'parent'       => $settings['term_id'],
        'hierarchical' => ! empty($settings['hide_empty']) ? true : false
    );

    if (ATBDP_CATEGORY == $taxonomy_id){
        $arg = apply_filters('atbdp_search_listing_category_argument', $args);
    } else {
        $arg = apply_filters('atbdp_search_listing_location_argument', $args);
    }

    $terms = get_terms( $taxonomy_id, $arg );

    $html = '';

    if (count($terms) > 0) {

        foreach ($terms as $term) {
            $directory_type = get_term_meta( $term->term_id, '_directory_type', true );
            $directory_type = ! empty( $directory_type ) ? $directory_type : array();
            if( in_array( $settings['listing_type'], $directory_type ) ) {
                $settings['term_id'] = $term->term_id;

                $count = 0;
                if (!empty($settings['hide_empty']) || !empty($settings['show_count'])) {
                    $count = atbdp_listings_count_by_category($term->term_id);

                    if (!empty($settings['hide_empty']) && 0 == $count) continue;
                }
                $selected = ($term_id == $term->term_id) ? "selected" : '';
                $custom_field    = in_array( $term->term_id, $settings['assign_to_category']['assign_to_cat'] ) ? true : '';
                $html .= '<option data-custom-field="' . $custom_field . '" value="' . $term->term_id . '" ' . $selected . '>';
                $html .= $prefix . $term->name;
                if (!empty($settings['show_count'])) {
                    $html .= ' (' . $count . ')';
                }
                $html .= search_category_location_filter($settings, $taxonomy_id, $prefix . '&nbsp;&nbsp;&nbsp;');
                $html .= '</option>';
            }
        }

    }

    return $html;

}

function add_listing_category_location_filter( $lisitng_type, $settings, $taxonomy_id, $term_id, $prefix = '', $plan_cat = array())
{
    if ($settings['immediate_category']) {

        if ($settings['term_id'] > $settings['parent'] && !in_array($settings['term_id'], $settings['ancestors'])) {
            return;
        }

    }

    $term_slug = get_query_var($taxonomy_id);

    $args = array(
        'orderby' => $settings['orderby'],
        'order' => $settings['order'],
        'hide_empty' => $settings['hide_empty'],
        'parent' => $settings['term_id'],
        'exclude' => $plan_cat,
        'hierarchical' => !empty($settings['hide_empty']) ? true : false
    );

    $terms             = get_terms($taxonomy_id, $args);

    $html = '';

    if (count($terms) > 0) {

        foreach ($terms as $term) {
            $directory_type    = get_term_meta( $term->term_id, '_directory_type', true );
            $directory_type    = ! empty( $directory_type ) ? $directory_type : array();
            $directory_type    = is_array( $directory_type ) ? $directory_type : array( $directory_type );
            if( in_array( $lisitng_type, $directory_type ) ) {
                $settings['term_id'] = $term->term_id;

                $count = 0;
                if (!empty($settings['hide_empty']) || !empty($settings['show_count'])) {
                    $count = atbdp_listings_count_by_category($term->term_id);

                    if (!empty($settings['hide_empty']) && 0 == $count) continue;
                }
                $selected = in_array($term->term_id, $term_id) ? "selected" : '';
                $html .= sprintf('<option value="%s" %s>', $term->term_id, $selected);
                $html .= $prefix . $term->name;
                if (!empty($settings['show_count'])) {
                    $html .= ' (' . $count . ')';
                }
                $html .= add_listing_category_location_filter($lisitng_type, $settings, $taxonomy_id, $term_id, $prefix . '&nbsp;&nbsp;&nbsp;');
                $html .= '</option>';
            }
        }

    }

    return $html;

}


/*
 * @since 6.3.0
 */
function atbdp_guest_submission($guest_email)
{
    $string = $guest_email;
    $explode = explode("@", $string);
    array_pop($explode);
    $userName = join('@', $explode);
    //check if username already exist
    if (username_exists($userName)) {
        $random = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, 5);
        $userName = $userName . $random;
    }
    // Check if user exist by email
    if (email_exists($guest_email)) {
        wp_send_json(array(
                'error'                => true,
                'quick_login_required' => true,
                'email'                => $guest_email,
                'error_msg'            => __('Email already registered. Please login first', 'directorist'),
        ));
        die();
    } else {
        // lets register the user
        $reg_errors = new WP_Error;
        if (empty($reg_errors->get_error_messages())) {
            $password = wp_generate_password(12, false);
            $userdata = array(
                'user_login' => $userName,
                'user_email' => $guest_email,
                'user_pass' => $password,
            );
            $user_id = wp_insert_user($userdata); // return inserted user id or a WP_Error
            wp_set_current_user($user_id, $guest_email);
            wp_set_auth_cookie($user_id);
            do_action('atbdp_user_registration_completed', $user_id);
            update_user_meta($user_id, '_atbdp_generated_password', $password);
            wp_new_user_notification($user_id, null, 'admin'); // send activation to the admin
            ATBDP()->email->custom_wp_new_user_notification_email($user_id);
        }
    }
}

function atbdp_get_listing_attachment_ids($post_id){

    $listing_img = get_post_meta($post_id, '_listing_img', true);
    $listing_img = !empty($listing_img) ? $listing_img : array();
    $listing_prv_img = get_post_meta($post_id, '_listing_prv_img', true);
    array_unshift($listing_img, $listing_prv_img);
    return $listing_img;
}


function get_uninstall_settings_submenus() {
    return apply_filters('atbdp_uninstall_settings_fields', array(
        array(
            'type' => 'toggle',
            'name' => 'enable_uninstall',
            'label' => __('Remove Data on Uninstall?', 'directorist'),
            'description'=> __('Checked it if you would like Directorist to completely remove all of its data when the plugin is deleted.','directorist'),
            'default' => 0,
        ),
    )
    );
}
function get_csv_import_settings_submenus() {
    return apply_filters('atbdp_csv_import_settings_fields', array(
        array(
            'type' => 'toggle',
            'name' => 'csv_import',
            'label' => __('CSV', 'directorist'),
        ),
    )
    );
}

function atbdp_email_html($subject, $message){
    $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    $header = '';
    $email_header_color = get_directorist_option('email_header_color', '#8569fb');
    $allow_email_header = get_directorist_option('allow_email_header', 1);
    if ($allow_email_header){
        $header = apply_filters('atbdp_email_header', '<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_header" style=\'background-color: '.$email_header_color.'; color: #ffffff; border-bottom: 0; font-weight: bold; line-height: 100%; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; border-radius: 3px 3px 0 0;\'>
                                        <tr>
                                            <td id="header_wrapper" style="padding: 36px 48px; display: block;">
                                                <h1 style=\'font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; font-size: 30px; font-weight: 300; line-height: 150%; margin: 0; text-align: left; text-shadow: 0 1px 0 #ab79a1; color: #ffffff;\'>'.$subject.'</h1>
                                            </td>
                                        </tr>
                                    </table>');
    }

    return '<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Directorist</title>
    </head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="padding: 0;">
        <div id="wrapper" dir="ltr" style="background-color: #f7f7f7; margin: 0; padding: 70px 0; width: 100%; -webkit-text-size-adjust: none;">
            <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
                <tr>
                    <td align="center" valign="top">
                        <div id="template_header_image">
                                                    </div>
                        <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container" style="background-color: #ffffff; border: 1px solid #dedede; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1); border-radius: 3px;">
                            <tr>
                                <td align="center" valign="top">
                                    <!-- Header -->
                                    '.$header.'
                                    <!-- End Header -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- Body -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body">
                                        <tr>
                                            <td valign="top" id="body_content" style="background-color: #ffffff;">
                                                <!-- Content -->
                                                <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td valign="top" style="padding: 48px 48px 32px;">
                                                            <div id="body_content_inner" style=\'color: #636363; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; font-size: 14px; line-height: 150%; text-align: left;\'>
'.$message.'
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- End Content -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Body -->
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="center" valign="top">
                        <!-- Footer -->
                        <table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer">
                            <tr>
                                <td valign="top" style="padding: 0; border-radius: 6px;">
                                    <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                        <tr>
                                            <td colspan="2" valign="middle" id="credit" style=\'border-radius: 6px; border: 0; color: #8a8a8a; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; font-size: 12px; line-height: 150%; text-align: center; padding: 24px 0;\'>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <!-- End Footer -->
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>';
}

function atbdp_create_required_pages(){
    $options = get_option('atbdp_option'); // we are retrieving all of our custom options because it contains all the page options too. and we can filter this array instead of calling get_directorist_option() over and over.
    /*
    Remember: We can not add new option to atbdp_option if there is no key matched. Because VafPress will override it.
    Use normal update_option() instead if you need to add custom option that is not available in the settings fields of VP Framework.
    */

    $directorist_pages = apply_filters('atbdp_create_custom_pages', array(
        'search_listing' => array(
            'title' => __('Search Home', 'directorist'),
            'content' => '[directorist_search_listing]'
        ),
        'search_result_page' => array(
            'title' => __('Search Result', 'directorist'),
            'content' => '[directorist_search_result]'
        ),
        'add_listing_page' => array(
            'title' => __('Add Listing', 'directorist'),
            'content' => '[directorist_add_listing]'
        ),
        'all_listing_page' => array(
            'title' => __('All Listings', 'directorist'),
            'content' => '[directorist_all_listing]'
        ),
        /* 'all_categories_page' => array(
            'title' => __('All Categories', 'directorist'),
            'content' => '[directorist_all_categories]'
        ), */
        'single_category_page' => array(
            'title' => __('Single Category', 'directorist'),
            'content' => '[directorist_category]'
        ),
        /* 'all_locations_page' => array(
            'title' => __('All Locations', 'directorist'),
            'content' => '[directorist_all_locations]'
        ), */
        'single_location_page' => array(
            'title' => __('Single Location', 'directorist'),
            'content' => '[directorist_location]'
        ),
        'single_tag_page' => array(
            'title' => __('Single Tag', 'directorist'),
            'content' => '[directorist_tag]'
        ),
        'author_profile_page' => array(
            'title' => __('Author Profile', 'directorist'),
            'content' => '[directorist_author_profile]'
        ),
        'user_dashboard' => array(
            'title' => __('Dashboard', 'directorist'),
            'content' => '[directorist_user_dashboard]'
        ),
        'custom_registration' => array(
            'title' => __('Registration', 'directorist'),
            'content' => '[directorist_custom_registration]'
        ),
        'user_login' => array(
            'title' => __('Login', 'directorist'),
            'content' => '[directorist_user_login]'
        ),
        /* 'checkout_page' => array(
            'title' => __('Checkout', 'directorist'),
            'content' => '[directorist_checkout]'
        ),
        'payment_receipt_page' => array(
            'title' => __('Payment Receipt', 'directorist'),
            'content' => '[directorist_payment_receipt]'
        ),
        'transaction_failure_page' => array(
            'title' => __('Transaction Failure', 'directorist'),
            'content' => '[directorist_transaction_failure]'
        ), */
    ));
    $new_settings = 0; // lets keep track of new settings so that we do not update option unnecessarily.
    // lets iterate over the array and insert a new page with with the appropriate shortcode if the page id is not available in the option array.
    foreach ($directorist_pages as $op_name => $page_settings) {
        // $op_name is the page option name in the database.
        // if we do not have the page id assigned in the settings with the given page option name, then create an page
        // and update the option.

        if (empty($options[$op_name]) || !get_post($options[$op_name])) {

            $id = wp_insert_post(
                array(
                    'post_title' => $page_settings['title'],
                    'post_content' => $page_settings['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed'
                )
            );
            // if we have added the page successfully, lets add the page id to the options array to save the page settings in the database after the loop.
            if ($id) {
                $options[$op_name] = (int)$id;

                /*TRYING TO SET THE DEFAULT PAGE TEMPLATE FOR THIS PAGE WHERE OUR SHORTCODE IS USED */
                // get the template list of the theme and if it has any full width template then assign it.
                $page_templates = wp_get_theme()->get_page_templates();
                $custom_template = ''; // place holder for full width template
                $temp_type = ('search_listing' == $op_name) ? 'home-page.php' : 'full'; // look for home template for search_listing page
                // lets see if we can find any full width template, then use it for the page where our shortcode is used.
                foreach ($page_templates as $slug => $name) {
                    // checkout page and payment receipt page looks better on non full-width template, so skip them.
                    if (in_array($op_name, array('checkout_page', 'payment_receipt_page'))) break;
                    if (strpos($slug, $temp_type)) {
                        $custom_template = $slug;
                        break;
                    }
                }
                if (!empty($custom_template)) update_post_meta($id, '_wp_page_template', sanitize_text_field($custom_template));


            }
            $new_settings++;
        } else {
            $replace_shortcode = wp_update_post(
                array(
                    'ID' => $options[$op_name],
                    'post_title' => $page_settings['title'],
                    'post_content' => $page_settings['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed'
                ), true
            );
        }
        // if we have new options then lets update the options with new option values.
        if ($new_settings) {
            update_option('atbdp_option', $options);
        };
        update_option('atbdp_pages_version', 1);
    }
}

function atbdp_logged_in_user(){
    return _wp_get_current_user()->exists();
}

function atbdp_thumbnail_card($img_src = '', $_args = array())
{
    $args = apply_filters('atbdp_preview_image_args', $_args);

    // Default
    $is_blur           = get_directorist_option('prv_background_type', 'blur');     // blur | color
    $is_blur           = ('blur' === $is_blur ? true : false);
    $container_size_by = get_directorist_option('prv_container_size_by', 'px');
    $by_ratio          = ( 'px' === $container_size_by ) ? false : true;
    $image_size        = get_directorist_option('way_to_show_preview', 'cover');    // contain | full | cover
    $ratio_width       = get_directorist_option('crop_width', 360);
    $ratio_height      = get_directorist_option('crop_height', 300);
    $blur_background   = $is_blur;
    $background_color  = get_directorist_option('prv_background_color', '#fff');
    $image_quality     = get_directorist_option('preview_image_quality', 'large');  // medium | large | full

    $thumbnail_img = '';

    $listing_prv_img   = get_post_meta(get_the_ID(), '_listing_prv_img', true);
    $listing_img       = get_post_meta(get_the_ID(), '_listing_img', true);
    $default_image_src = get_directorist_option('default_preview_image', DIRECTORIST_ASSETS . 'images/grid.jpg');

    if ( is_array( $listing_img ) && ! empty( $listing_img ) ) {
        $thumbnail_img = atbdp_get_image_source( $listing_img[0], $image_quality );
        $thumbnail_id = $listing_img[0];
    }

    if ( ! empty( $listing_prv_img ) ) {
        $thumbnail_img = atbdp_get_image_source( $listing_prv_img, $image_quality );
        $thumbnail_id = $listing_prv_img;
    }

    if ( ! empty( $img_src ) ) {
        $thumbnail_img = $img_src;
        $thumbnail_id = 0;
    }

    if ( empty( $thumbnail_img ) ) {
        $thumbnail_img = $default_image_src;
        $thumbnail_id = 0;
    }

    /* if ( 'cover' === $image_size && false === $by_ratio ) {
        $thumbnail_img = atbdp_image_cropping($thumbnail_id, $ratio_width, $ratio_height, true, 100)['url'];
    } */

    if ( empty( $thumbnail_img ) ) { return ''; }

    $image_src    = is_array($thumbnail_img) ? $thumbnail_img[0] : $thumbnail_img;
    $image_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
    $image_alt = ( ! empty( $image_alt ) ) ? esc_attr( $image_alt ) : esc_html( get_the_title( $thumbnail_id ) );
    $image_alt = ( ! empty( $image_alt ) ) ? $image_alt : esc_html( get_the_title() );

    // Extend Default
    if ( isset($args['image']) ) {
        $image_src = esc_html(stripslashes($args['image']));
    }
    if ( isset($args['image-size']) ) {
        $image_size = esc_html(stripslashes($args['image-size']));
    }
    if ( isset($args['width']) ) {
        $ratio_width = esc_html(stripslashes($args['width']));
    }
    if ( isset($args['height']) ) {
        $ratio_height = esc_html(stripslashes($args['height']));
    }
    if ( isset($args['alt']) ) {
        $image_alt = esc_html(stripslashes($args['alt']));
    }
    if ( isset($args['blur-background']) ) {
        $blur_background = esc_html(stripslashes($args['blur-background']));
    }
    if ( isset($args['background-color']) ) {
        $background_color = esc_html(stripslashes($args['background-color']));
    }

    // Style
    $style = '';

    if ( $by_ratio ) {
        $padding_top_value = (int) $ratio_height / (int) $ratio_width * 100;
        $padding_top_css = "padding-top: $padding_top_value%;";
        $style .= $padding_top_css;
    } else {
        $height_value = (int) $ratio_height;
        $height_css = "height: {$height_value}px;";
        $style .= $height_css;
    }

    $background_color_css = '';
    if ( $image_size !== 'full' && !$blur_background ) {
        $background_color_css = "background-color: $background_color";
        $style .= $background_color_css;
    }


    // Card Front Wrap
    $card_front_wrap = "<div class='atbd-thumbnail-card-front-wrap'>";
    $card_front__img = "<img src='$image_src' alt='$image_alt' class='atbd-thumbnail-card-front-img'/>";
    $front_wrap_html = $card_front_wrap . $card_front__img . "</div>";

    // Card Back Wrap
    $card_back_wrap = "<div class='atbd-thumbnail-card-back-wrap'>";
    $card_back__img = "<img src='$image_src' class='atbd-thumbnail-card-back-img'/>";
    $back_wrap_html = $card_back_wrap . $card_back__img . "</div>";

    $blur_bg = ( $blur_background ) ? $back_wrap_html : '';

    // Card Contain
    $card_contain_wrap = "<div class='atbd-thumbnail-card card-contain' style='$style'>";
    $image_contain_html = $card_contain_wrap . $blur_bg . $front_wrap_html . "</div>";

    // Card Cover
    $card_cover_wrap = "<div class='atbd-thumbnail-card card-cover' style='$style'>";
    $image_cover_html = $card_cover_wrap . $front_wrap_html . "</div>";

    // Card Full
    $card_full_wrap = "<div class='atbd-thumbnail-card card-full' style='$background_color_css'>";
    $image_full_html = $card_full_wrap . $front_wrap_html . "</div>";

    $the_html = $image_cover_html;
    switch ($image_size) {
        case 'cover':
            $the_html = $image_cover_html;
            break;
        case 'contain':
            $the_html = $image_contain_html;
            break;
        case 'full':
            $the_html = $image_full_html;
            break;
    }

    echo $the_html;
}

function the_thumbnail_card($img_src = '', $_args = array()) {
    _deprecated_function( __FUNCTION__, '7.0', 'atbdp_thumbnail_card()' );
    return atbdp_thumbnail_card($img_src,$_args);
}


// get_plasma_slider
function get_plasma_slider()
{
    $show_slider       = get_directorist_option( 'dsiplay_slider_single_page', 1 );
    $slider_is_enabled = ( $show_slider === 1 || $show_slider === '1' ) ? true : false;

    if ( ! $slider_is_enabled ) { return ''; }

    global $post;
    $listing_id    = $post->ID;
    $listing_title = get_the_title( $post->ID );
    $data          = array();

    // Check if gallery is allowed or not
    $fm_plan      = get_post_meta($listing_id, '_fm_plans', true);
    $show_gallery = true;

    if ( is_fee_manager_active() ) {
        $show_gallery = is_plan_allowed_slider($fm_plan);
    }

    // Get the default image
    $default_image = get_directorist_option(
        'default_preview_image', DIRECTORIST_ASSETS . 'images/grid.jpg'
    );

    // Get the preview images
    $preview_img_id   = get_post_meta( $listing_id, '_listing_prv_img', true);
    $preview_img_link = ! empty($preview_img_id) ? atbdp_get_image_source($preview_img_id, 'large') : '';
    $preview_img_alt  = get_post_meta($preview_img_id, '_wp_attachment_image_alt', true);
    $preview_img_alt  = ( ! empty( $preview_img_alt )  ) ? $preview_img_alt : get_the_title( $preview_img_id );

    // Get the gallery images
    $listing_img  = get_post_meta( $listing_id, '_listing_img', true );
    $listing_imgs = ( ! empty( $listing_img ) ) ? $listing_img : array();
    $image_links  = array(); // define a link placeholder variable

    foreach ( $listing_imgs as $img_id ) {
        $alt = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
        $alt = ( ! empty( $alt )  ) ? $alt : get_the_title( $img_id );

        $image_links[] = [
            'alt' => ( ! empty( $alt )  ) ? $alt : $listing_title,
            'src' => atbdp_get_image_source( $img_id, 'large' ),
        ];
    }

    // Get the options
    $background_type  = get_directorist_option('single_slider_background_type', 'custom-color');

    // Set the options
    $data['images']             = [];
    $data['alt']                = $listing_title;
    $data['background-size']    = get_directorist_option('single_slider_image_size', 'cover');
    $data['blur-background']    = ( 'blur' === $background_type ) ? true : false;
    $data['width']              = get_directorist_option('gallery_crop_width', 670);
    $data['height']             = get_directorist_option('gallery_crop_height', 750);
    $data['background-color']   = get_directorist_option('single_slider_background_color', 'gainsboro');
    $data['thumbnail-bg-color'] = '#fff';
    $data['show-thumbnails']    = get_directorist_option('dsiplay_thumbnail_img', true);
    $data['gallery']            = true;
    $data['rtl']                = is_rtl();

    if ( $show_gallery && ! empty( $image_links ) ) {
        $data['images'] = $image_links;
    }

    if ( ! empty( $preview_img_link ) ) {
        $preview_img = [
            'alt' => $preview_img_alt,
            'src' => $preview_img_link,
        ];

        array_unshift( $data['images'], $preview_img );
    }

    if ( count( $data['images'] ) < 1 ) {
        $data['images'][] = [
            'alt' => $listing_title,
            'src' => $default_image,
        ];
    }

    $padding_top         = $data['height'] / $data['width'] * 100;
    $data['padding-top'] = $padding_top;

    return get_view('plasma-slider', $data);
}


function atbdp_style_example_image ($src) {
    $img = sprintf("<img src='%s'>", $src );
    echo $img;
}

function view( $file_path, $data = null )
{
    $path = ATBDP_VIEW_DIR . $file_path . '.php';
    if ( file_exists($path) ) {
        include($path);
    }
}

function get_view( $file_path, $data = null )
{
    $view = '';
    ob_start();
    view( $file_path, $data );
    $view =  ob_get_contents();
    ob_end_clean();

    return $view;
}

if(!function_exists('csv_get_data')){
    function csv_get_data($default_file = null, $multiple = null, $delimiter = ',')
    {
        $data = $multiple ? array() : '';
        $errors = array();
        // Get array of CSV files
        $file = $default_file ? $default_file : '';
        if (!$file) return;

        // Attempt to change permissions if not readable
        if (!is_readable($file)) {
            chmod($file, 0744);
        }

        // Check if file is writable, then open it in 'read only' mode
        if (is_readable($file) && $_file = fopen($file, "r")) {

            // To sum this part up, all it really does is go row by
            //  row, column by column, saving all the data
            $post = array();

            // Get first row in CSV, which is of course the headers
            $header = fgetcsv($_file, 0, $delimiter);

            while ($row = fgetcsv($_file, 0, $delimiter)) {

                foreach ($header as $i => $key) {
                    $post[$key] = $row[$i];
                }

                if ($multiple) {
                    $data[] = $post;
                } else {
                    $data = $post;
                }
            }

            fclose($_file);
        } else {
            $errors[] = "File '$file' could not be opened. Check the file's permissions to make sure it's readable by your server.";
        }
        if (!empty($errors)) {
            // ... do stuff with the errors
        }
        return $data;
    }
}

// Polyfill - backword comapbility of php7 function
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}

function directorist_redirect_to_admin_setup_wizard() {
    // Delete the redirect transient
    delete_transient( '_directorist_setup_page_redirect' );

    wp_safe_redirect( add_query_arg( array( 'page' => 'directorist-setup' ), admin_url( 'index.php' ) ) );
    exit;
}

function directorist_default_directory(){
    $id = '';
    $all_types     = get_terms(array(
        'taxonomy'   => ATBDP_TYPE,
        'hide_empty' => false,
    ));
    foreach( $all_types as $type ) {
        $default = get_term_meta( $type->term_id, '_default', true );
        if( $default ){
            $id = $type->term_id;
        }
    }
    return $id;
}


// @kowsar - remove later @for dev use only
function dvar_dump($data){
    return '';
    echo "<pre>";
    var_dump($data);
    echo "</pre>";
}

if( ! function_exists( 'atbdp_field_assigned_plan' ) ) {
    function atbdp_field_assigned_plan( $field_data, $selected_plan = NULL ) {
        if( ! $field_data ) return false;

        $quired_plan = ! empty( $_GET['plan'] ) ? sanitize_key( $_GET['plan'] ) : '';
        $selected_plan = ! empty( $selected_plan ) ? $selected_plan : $quired_plan;
        $plans = !empty( $field_data['plans'] ) ? $field_data['plans'] : [];

        if( $plans ) {
            foreach ( $plans as $plan ) {
                if( $plan['plan_id'] == $selected_plan ) {
                    return $plan;
                }
            }
        }
    }
}
if( !function_exists('directory_types') ){
    function directory_types() {
        $listing_types = get_terms([
            'taxonomy'   => ATBDP_TYPE,
            'hide_empty' => false,
            'orderby'    => 'date',
            'order'      => 'DSCE',
          ]);
          return $listing_types;
    }
}
if( !function_exists('default_directory_type') ){
    function default_directory_type() {
        $default_directory = '';
        if( !empty( directory_types() ) ) {
            foreach( directory_types() as $term ) {
                $default = get_term_meta( $term->term_id, '_default', true );
                if( $default ) {
                    $default_directory = $term->term_id;
                    break;
                }
            }
        }
        return $default_directory;
    }
}
if( !function_exists('get_listing_types') ){
    function get_listing_types() {
        $listing_types = array();
        $args          = array(
            'taxonomy'   => ATBDP_TYPE,
            'hide_empty' => false
        );
        $all_types     = get_terms( $args );

        foreach ( $all_types as $type ) {
            $listing_types[ $type->term_id ] = [
                'term' => $type,
                'name' => $type->name,
                'data' => get_term_meta( $type->term_id, 'general_config', true ),
            ];
        }
        return $listing_types;
    }
}

if( !function_exists('directorist_get_form_fields_by_directory_type') ){
    function directorist_get_form_fields_by_directory_type( $field = 'id', $value = '' ) {
        $term                   = get_term_by( $field, $value, ATBDP_TYPE );
        $submission_form        = get_term_meta( $term->term_id, 'submission_form_fields', true );
        $submission_form_fields = $submission_form['fields'];
        return $submission_form_fields;
    }
}

if( !function_exists('directorist_legacy_mode') ){
    function directorist_legacy_mode() {
        return false;
    }
}

if( !function_exists('directorist_multi_directory') ){
    function directorist_multi_directory() {
        return get_directorist_option( 'enable_multi_directory', false );
    }
}

if( ! function_exists( 'directorist_warnings' ) ) {
    function directorist_warnings() {
        $add_listing 			 	= get_directorist_option( 'add_listing_page' );
        $user_dashboard			 	= get_directorist_option( 'user_dashboard' );
        $user_profile   		 	= get_directorist_option( 'author_profile_page' );
        $single_category_page 	 	= get_directorist_option( 'single_category_page' );
        $single_location_page	 	= get_directorist_option( 'single_location_page' );
        $single_tag_page		 	= get_directorist_option( 'single_tag_page' );
        $custom_registration     	= get_directorist_option( 'custom_registration' );
        $user_login				 	= get_directorist_option( 'user_login' );
        $search_result_page      	= get_directorist_option( 'search_result_page' );
        $checkout_page			 	= get_directorist_option( 'checkout_page' );
        $payment_receipt_page	 	= get_directorist_option( 'payment_receipt_page' );
        $transaction_failure_page	= get_directorist_option( 'transaction_failure_page' );
        $enable_monetization	 	= get_directorist_option( 'enable_monetization' );
        $enable_featured_listing	= get_directorist_option( 'enable_featured_listing' );
        $select_listing_map			= get_directorist_option( 'select_listing_map' );
        $map_api_key				= get_directorist_option( 'map_api_key' );
        $host                       = gethostname();
        $connection                 =  @fsockopen( $host, 25, $errno, $errstr, 5 );
        $warnings = [];
        if( empty( $add_listing ) ) {
            $warnings[] = array(
                'title' => __( 'Add listing page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $user_dashboard ) ) {
            $warnings[] = array(
                'title' => __( 'Dashboard page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $user_profile ) ) {
            $warnings[] = array(
                'title' => __( 'User Profile page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $single_category_page ) ) {
            $warnings[] = array(
                'title' => __( 'Single Category page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $single_location_page ) ) {
            $warnings[] = array(
                'title' => __( 'Single Location page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $single_tag_page ) ) {
            $warnings[] = array(
                'title' => __( 'Single Location page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $custom_registration ) ) {
            $warnings[] = array(
                'title' => __( 'Registration page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $user_login ) ) {
            $warnings[] = array(
                'title' => __( 'Login page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $search_result_page ) ) {
            $warnings[] = array(
                'title' => __( 'Search Result page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $checkout_page ) && ! empty( $enable_monetization ) && ! empty( $enable_featured_listing ) ) {
            $warnings[] = array(
                'title' => __( 'Checkout page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $payment_receipt_page ) && ! empty( $enable_monetization ) && ! empty( $enable_featured_listing ) ) {
            $warnings[] = array(
                'title' => __( 'Payment Receipt page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( empty( $transaction_failure_page ) && ! empty( $enable_monetization ) && ! empty( $enable_featured_listing ) ) {
            $warnings[] = array(
                'title' => __( 'Transaction Failure page not selected', 'directorist'),
                'desc'  => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'  => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_pages",
                'link_text' => __( 'Select Page', 'directorist' )
            );
        }
        if( 'google' == $select_listing_map && empty( $map_api_key ) ) {
            $warnings[] = array(
                'title'      => __( 'Map Api Key is missing', 'directorist'),
                'desc'       => __( "Contains a collection of relevant data that will help you debug your website accurately and more efficiently.", 'directorist'),
                'link'       => admin_url()."/edit.php?post_type=at_biz_dir&page=aazztech_settings#_map_setting",
                'link_text'  => __( 'Give the Api', 'directorist' )
            );
        }
        if( ! is_resource( $connection ) ) {
            $warnings[] = array(
                'title'      => __( 'SMTP not configured', 'directorist'),
                'desc'       => __( "SMTP is a TCP/IP protocol responsible for email deliveries. You must configure SMTP to send or receive emails.", 'directorist'),
            );
        }

        return $warnings;
    }
}