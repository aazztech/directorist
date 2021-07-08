<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Setup wizard class
 *
 * Walkthrough to the basic setup upon installation
 */
class SetupWizard
{
    /** @var string Currenct Step */
    public $step   = '';

    /** @var array Steps for the setup wizard */
    public $steps  = array();

    /**
     * Actions to be executed after the HTTP response has completed
     *
     * @var array
     */
    private $deferred_actions = array();

    /**
     * Hook in tabs.
     */
    public function __construct() {

            add_action( 'admin_menu', array( $this, 'admin_menus' ) );
            add_action( 'admin_init', array( $this, 'setup_wizard' ), 99 );
            add_action( 'admin_notices', array( $this, 'render_run_admin_setup_wizard_notice' ) );
            add_action( 'wp_ajax_atbdp_dummy_data_import', array( $this, 'atbdp_dummy_data_import' ) );
            add_action( 'wp_loaded', array( $this, 'hide_notices' ) );
    }

    public function render_run_admin_setup_wizard_notice() {

        $setup_wizard = get_option( 'directorist_setup_wizard_completed' );
        $atpdp_setup_wizard = apply_filters( 'atbdp_setup_wizard', true );
        if( $setup_wizard || ! $atpdp_setup_wizard ) {
            return;
        }

        ?>
        <div id="message" class="updated atbdp-message">
            <p><?php echo wp_kses_post( __( '<strong>Welcome to Directorist</strong> &#8211; You&lsquo;re almost ready to start your directory!', 'directorist' ) ); ?></p>
            <p class="submit">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=directorist-setup' ) ); ?>" class="button-primary"><?php esc_html_e( 'Run the Setup Wizard', 'directorist' ); ?></a>
                <a class="button-secondary skip" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist-hide-notice', 'install' ), 'directorist_hide_notices_nonce', '_atbdp_notice_nonce' ) ); ?>"><?php _e( 'Skip setup', 'directorist' ); ?></a>
            </p>
        </div>
    <?php
    }

    public function hide_notices() {
        if ( isset( $_GET['directorist-hide-notice'] ) && isset( $_GET['_atbdp_notice_nonce'] ) ) { // WPCS: input var ok, CSRF ok.
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_atbdp_notice_nonce'] ) ), 'directorist_hide_notices_nonce' ) ) { // WPCS: input var ok, CSRF ok.
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'directorist' ) );
			}

			update_option( 'directorist_setup_wizard_completed', true );
        }

        $create_pages = [
            'checkout_page'        => [
                'post_title'         => 'Checkout',
                'post_content'       => '[directorist_checkout]',
            ],
            'payment_receipt_page' => [
                'post_title'         => 'Payment Receipt',
                'post_content'       => '[directorist_payment_receipt]',
            ],
            'transaction_failure_page' => [
                'post_title'         => 'Transaction Failure',
                'post_content'       => '[directorist_transaction_failure]',
            ],
        ];
        $atbdp_option = get_option( 'atbdp_option' );

        if (!empty($atbdp_option['enable_monetization'])) {
            foreach ($create_pages as $key => $name) {

                $args = [
                    'post_title' => $name['post_title'],
                    'post_content' => $name['post_content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed'
                ];
                if (empty($atbdp_option[$key])) {
                    $id = wp_insert_post($args);

                    if ($id) {
                        $atbdp_option[$key] = $id;
                    }
                }
            }
        }
        update_option('atbdp_option', $atbdp_option);
    }

    public function atbdp_dummy_data_import()
    {
        $data               = array();
        $imported           = 0;
        $failed             = 0;
        $count              = 0;
        $preview_image      = isset($_POST['image']) ? sanitize_text_field($_POST['image']) : '';
        $file               = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
        $total_length       = isset($_POST['limit']) ? sanitize_text_field($_POST['limit']) : 0;
        $position           = isset($_POST['position']) ? sanitize_text_field($_POST['position']) : 0;
        $all_posts          = $this->read_csv($file);
        $posts              = array_slice($all_posts, $position);
        $limit              = 1;
        if ( ! $total_length ) {
            $data['error'] = __('No data found', 'directorist');
            die();
        }
        $listing_types = get_terms([
            'taxonomy'   => 'atbdp_listing_types',
            'hide_empty' => false,
            'showposts' => 1,
        ]);
        $directory_id = !empty( $listing_types[0] ) ? $listing_types[0]->term_id : '';
        $directory_slug = !empty( $listing_types[0] ) ? $listing_types[0]->slug : '';
        foreach ($posts as $index => $post) {
                if ($count === $limit ) break;
                // start importing listings
                $args = array(
                    "post_title"   => isset($post['name']) ? $post['name'] : '',
                    "post_content" => isset($post['details']) ? $post['details'] : '',
                    "post_type"    => 'at_biz_dir',
                    "post_status"  => 'publish',
                );
                $post_id = wp_insert_post($args);
                if (!is_wp_error($post_id)) {
                    $imported++;
                } else {
                    $failed++;
                }
                foreach($post as $key => $value){
                    if ('category' == $key) {
                        $taxonomy = ATBDP_CATEGORY;
                        $term_exists = get_term_by( 'name', $value, $taxonomy );
                        if ( ! $term_exists ) { // @codingStandardsIgnoreLine.
                            $result = wp_insert_term( $value, $taxonomy );
                            if( !is_wp_error( $result ) ){
                                $term_id = $result['term_id'];
                                update_term_meta($term_id, '_directory_type', [ $directory_id ] );
                                wp_set_object_terms($post_id, $term_id, $taxonomy);
                            }
                        }else{
                            wp_set_object_terms($post_id, $term_exists->term_id, $taxonomy);
                        }
                    } elseif ('location' == $key) {
                        $taxonomy = ATBDP_LOCATION;
                        $term_exists = get_term_by( 'name', $value, $taxonomy );
                        if ( ! $term_exists ) { // @codingStandardsIgnoreLine.
                            $result = wp_insert_term( $value, $taxonomy );
                            if( !is_wp_error( $result ) ){
                                $term_id = $result['term_id'];
                                update_term_meta($term_id, '_directory_type',  [ $directory_id ] );
                                wp_set_object_terms($post_id, $term_id, $taxonomy);
                            }
                        }else{
                            wp_set_object_terms($post_id, $term_exists->term_id, $taxonomy);
                        }
                    } elseif ('tag' == $key){
                        $taxonomy = ATBDP_TAGS;
                        $term_exists = get_term_by( 'name', $value, $taxonomy );
                        if ( ! $term_exists ) { // @codingStandardsIgnoreLine.
                            $result = wp_insert_term( $value, $taxonomy );
                            if( !is_wp_error( $result ) ){
                                $term_id = $result['term_id'];
                                wp_set_object_terms($post_id, $term_id, $taxonomy);
                            }
                        }else{
                            wp_set_object_terms($post_id, $term_exists->term_id, $taxonomy);
                        }
                    }
                    $skipped = array('name', 'details', 'category', 'location', 'tag', 'listing_prv_img');

                    if(!in_array( $key, $skipped )){
                        update_post_meta( $post_id, '_'.$key, $value );
                    }
                }
                $exp_dt = calc_listing_expiry_date();
                update_post_meta($post_id, '_expiry_date', $exp_dt);
                update_post_meta($post_id, '_featured', 0);
                update_post_meta($post_id, '_listing_status', 'post_status');
                $preview_url = isset($post['listing_prv_img']) ? $post['listing_prv_img'] : '';

                if ( $preview_image && $preview_url ) {
                   $attachment_id = ATBDP_Tools::atbdp_insert_attachment_from_url($preview_url, $post_id);
                   update_post_meta($post_id, '_listing_prv_img', $attachment_id);
                }

                //directory type
                if( !empty( $directory_id ) ){
                    update_post_meta($post_id, '_directory_type', $directory_id);
                    wp_set_object_terms($post_id, (int)$directory_id, 'atbdp_listing_types');
                }

                $count++;
        }
        $data['next_position'] = (int) $position + (int) $count;
        $data['percentage']    = absint(min(round((($data['next_position']) / $total_length) * 100), 100));
        $data['url']           = admin_url('index.php?page=directorist-setup&step=step-four');
        $data['total']         = $total_length;
        $data['imported']      = $imported;
        $data['failed']        = $failed;

        wp_send_json($data);
    }

    public function read_csv($file){
        $fp = fopen($file, 'r');
        $header = fgetcsv($fp);

        // get the rest of the rows
        $data = array();
        while ($row = fgetcsv($fp)) {
        $arr = array();
        foreach ($header as $i => $col)
            $arr[$col] = $row[$i];
        $data[] = $arr;
        }
        return $data;
    }

    /**
     * Add admin menus/screens.
     */
    public function admin_menus()
    {
        add_submenu_page(null, '', '', 'manage_options', 'directorist-setup', '');
    }

    /**
     * Show the setup wizard.
     */
    public function setup_wizard()
    {

        if (empty($_GET['page']) || 'directorist-setup' !== $_GET['page']) {
            return;
        }

        $this->set_steps();

        $this->step = isset($_GET['step']) ? sanitize_key($_GET['step']) : current(array_keys($this->steps));

        $this->enqueue_scripts();

        if (!empty($_POST['save_step']) && isset($this->steps[$this->step]['handler'])) { // WPCS: CSRF ok.
            call_user_func_array($this->steps[$this->step]['handler'], array($this));
        }

        ob_start();
        $this->set_setup_wizard_template();
        exit;
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style('atbdp_setup_select2', DIRECTORIST_VENDOR_CSS . 'select2.min.css', ATBDP_VERSION, true);
        wp_register_script('directorist-select2', DIRECTORIST_VENDOR_JS . 'select2.min.js', array('jquery'), ATBDP_VERSION, true);
        wp_enqueue_script('directorist-setup');
        wp_enqueue_script('directorist-select2');

        wp_register_style('directorist-admin-style', DIRECTORIST_CSS . 'admin-main.css', ATBDP_VERSION, true);
        wp_register_script('directorist-admin-setup-wizard-script', DIRECTORIST_JS . 'admin-setup-wizard.js', array('jquery'), ATBDP_VERSION, true);

        wp_enqueue_style('directorist-admin-style');
        wp_enqueue_script('directorist-admin-setup-wizard-script');

        wp_localize_script('directorist-admin-setup-wizard-script', 'import_export_data', [ 'ajaxurl' => admin_url('admin-ajax.php') ] );
    }

    /**
     * Set wizard steps
     *
     * @since 2.9.27
     *
     * @return void
     */
    protected function set_steps()
    {
        $this->steps = apply_filters('directorist_admin_setup_wizard_steps', array(
            'introduction' => array(
                'name'    =>  __('Introduction', 'directorist'),
                'view'    => array( $this, 'directorist_setup_introduction' ),
            ),
            'step-one' => array(
                'name'    =>  __('Step One', 'directorist'),
                'view'    => array( $this, 'directorist_step_one' ),
                'handler' => array( $this, 'directorist_step_one_save' ),
            ),
            'step-two' => array(
                'name'    =>  __('Step Two', 'directorist'),
                'view'    => array( $this, 'directorist_step_two' ),
                'handler' => array( $this, 'directorist_step_two_save' ),
            ),
            'step-three' => array(
                'name'    =>  __('Step Three', 'directorist'),
                'view'    => array( $this, 'directorist_step_three' ),
                'handler' => array( $this, 'directorist_step_three_save' ),
            ),
            'step-four' => array(
                'name'    =>  __('Step Four', 'directorist'),
                'view'    => array( $this, 'directorist_step_four' ),
            ),
        ));
    }

    public function directorist_step_one() { ?>
        <div class="atbdp-c-header">
            <h1><?php esc_html_e('Recommended Pages', 'directorist'); ?></h1>
        </div>

        <form method="post">
            <div class="atbdp-c-body">
                <div class="w-form-group">
                    <label for="add_listing_page"><?php _e( 'Add Listing', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='add_listing' class="w-switch" id='add_listing' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="all_listings"><?php _e( 'All Listings', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='all_listings' class="w-switch" id='all_listings' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="single_category"><?php _e( 'Single Category', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='single_category' class="w-switch" id='single_category' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="single_location"><?php _e( 'Single Location', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='single_location' class="w-switch" id='single_location' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="single_tag"><?php _e( 'Single Tag', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='single_tag' class="w-switch" id='single_tag' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="author_profile"><?php _e( 'Author Profile', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='author_profile' class="w-switch" id='author_profile' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="dashboard"><?php _e( 'Dashboard', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='dashboard' class="w-switch" id='dashboard' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="login"><?php _e( 'Login', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='login' class="w-switch" id='login' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="registration"><?php _e( 'Registration', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='registration' class="w-switch" id='registration' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="search_listing"><?php _e( 'Search Listing', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='search_listing' class="w-switch" id='search_listing' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="search_result"><?php _e( 'Search Result', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='search_result' class="w-switch" id='search_result' value=1 checked disabled>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="all_categories"><?php _e( 'All Categories', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='all_categories' class="w-switch" id='all_categories' value=1>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="all_locations"><?php _e( 'All Locations', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='all_locations' class="w-switch" id='all_locations' value=1>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="all_authors"><?php _e( 'All Authors', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='all_authors' class="w-switch" id='all_authors' value=1>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="terms_conditions"><?php _e( 'Terms & Conditions', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='terms_conditions' class="w-switch" id='terms_conditions' value=1>
                        </div>
                    </div>
                </div>
                <div class="w-form-group">
                    <label for="privacy_policy"><?php _e( 'Privacy Policy', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='privacy_policy' class="w-switch" id='privacy_policy' value=1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="atbdp-c-footer">
                <p class="atbdp-setup-actions step">
                    <a href="<?php echo esc_url($this->get_next_step_link()); ?>" class="w-skip-link"><?php esc_html_e('Skip this step', 'directorist'); ?></a>
                    <?php wp_nonce_field('directorist-setup'); ?>
                    <input type="submit" class="wbtn wbtn-primary" value="<?php esc_attr_e('Continue', 'directorist'); ?>" name="save_step" />
                </p>
            </div>
        </form>
        <?php
    }

    public function directorist_step_one_save() {
        check_admin_referer('directorist-setup');

        $_post_data = wp_unslash( $_POST );
        $all_categories     = !empty( $_post_data['all_categories'] ) ? $_post_data['all_categories'] : '';
        $all_locations      = !empty( $_post_data['all_locations'] ) ? $_post_data['all_locations'] : '';
        $all_authors        = !empty( $_post_data['all_authors'] ) ? $_post_data['all_authors'] : '';
        $terms_conditions   = !empty( $_post_data['terms_conditions'] ) ? $_post_data['terms_conditions'] : '';
        $privacy_policy     = !empty( $_post_data['privacy_policy'] ) ? $_post_data['privacy_policy'] : '';

        $atbdp_option = get_option('atbdp_option');
        $create_pages = [];
        if ( ! empty( $all_categories ) ) {
            $create_pages['all_categories_page'] = array(
                'post_title' => __('All Categories', 'directorist'),
                'post_content' => '[directorist_all_categories]'
            );
        }
        if ( ! empty( $all_locations ) ) {
            $create_pages['all_locations_page'] = array(
                'post_title' => __('All Locations', 'directorist'),
                'post_content' => '[directorist_all_locations]'
            );
        }
        if ( ! empty( $all_authors ) ) {
            $create_pages['all_authors_page'] = array(
                'post_title' => __('All Authors', 'directorist'),
                'post_content' => '[directorist_all_authors]'
            );
        }
        if ( ! empty( $terms_conditions ) ) {
            $create_pages['terms_conditions'] = array(
                'post_title' => __('Terms and Conditions', 'directorist'),
                'post_content' => ''
            );
        }
        if ( ! empty( $privacy_policy ) ) {
            $create_pages['privacy_policy'] = array(
                'post_title' => __('Privacy Policy', 'directorist'),
                'post_content' => ''
            );
        }

        if ( ! empty( $create_pages ) ) {
            foreach ( $create_pages as $key => $name ) {

                $args = [
                    'post_title' => $name['post_title'],
                    'post_content' => $name['post_content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed'
                ];
                if ( empty( $atbdp_option[ $key ] ) ) {
                    $id = wp_insert_post($args);

                    if ($id) {
                        $atbdp_option[$key] = $id;
                    }
                }
            }
        }

        update_option('atbdp_option', $atbdp_option);
        wp_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    public function directorist_step_two()
    {

    ?>
        <div class="atbdp-c-header">
            <h1><?php esc_html_e('Fill out the form to get maps and monetization feature to work right away', 'directorist'); ?></h1>
        </div>

        <form method="post">
            <div class="atbdp-c-body">
                <div class="w-form-group">
                    <label for="select_map"><?php _e( 'Select Map', 'directorist' ); ?></label>
                    <div><select name="select_listing_map" id="select_map">
                            <option value="openstreet"><?php _e( 'Openstreet', 'directorist' ); ?></option>
                            <option value="google"><?php _e( 'Google', 'directorist' ); ?></option>
                        </select></div>
                </div>
                <div class="w-form-group atbdp-sw-gmap-key">
                    <label for="google_api"><?php _e( 'Google Map API key', 'directorist' ); ?></label>
                    <div><input type="text" name="map_api_key" id="google_api"><small><?php _e( '* API Key is required for Google Map to work properly', 'directorist' ); ?></small></div>
                </div>
                <div class="w-form-group">
                    <label for="enable_monetization"><?php _e( 'Enable Monetization Feature', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='enable_monetization' class="w-switch" id='enable_monetization' value=1>
                        </div>
                    </div>
                </div>
                <div class="w-form-group atbdp-sw-featured-listing">
                    <label for="enable_featured_listing"><?php _e( 'Monetize by Featured Listing', 'directorist' ); ?></label>
                    <div>
                        <div class="w-toggle-switch">
                            <input type="checkbox" name='enable_featured_listing' class="w-switch" id='enable_featured_listing' value=1>
                        </div>
                    </div>
                </div>
                <div class="w-form-group atbdp-sw-listing-price">
                    <label for="featured_listing_price"><?php _e( 'Price', 'directorist' ); ?></label>
                    <div>
                        <div class="w-input-group">
                            <input type="text" name='featured_listing_price' id='featured_listing_price' value=19.99>
                            <span>USD</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="atbdp-c-footer">
                <p class="atbdp-setup-actions step">
                    <a href="<?php echo esc_url($this->get_next_step_link()); ?>" class="w-skip-link"><?php esc_html_e('Skip this step', 'directorist'); ?></a>
                    <?php wp_nonce_field('directorist-setup'); ?>
                    <input type="submit" class="wbtn wbtn-primary" value="<?php esc_attr_e('Continue', 'directorist'); ?>" name="save_step" />
                </p>
            </div>
        </form>
    <?php
    }

    /**
     * Save store options.
     */
    public function directorist_step_two_save()
    {
        check_admin_referer('directorist-setup');

        $_post_data = wp_unslash( $_POST );

        $atbdp_option = get_option('atbdp_option');
        $pages = !empty( $_post_data['share_essentials'] ) ? $_post_data['share_essentials'] : '';
        $atbdp_option['select_listing_map'] = !empty($_post_data['select_listing_map']) ? $_post_data['select_listing_map'] : '';
        $atbdp_option['map_api_key'] = !empty($_post_data['map_api_key']) ? $_post_data['map_api_key'] : '';
        $atbdp_option['enable_monetization'] = !empty($_post_data['enable_monetization']) ? $_post_data['enable_monetization'] : '';
        $atbdp_option['enable_featured_listing'] = !empty($_post_data['enable_featured_listing']) ? $_post_data['enable_featured_listing'] : '';
        $atbdp_option['featured_listing_price'] = !empty($_post_data['featured_listing_price']) ? $_post_data['featured_listing_price'] : '';

        do_action('directorist_admin_setup_wizard_save_step_two');

        $create_pages = [
            'checkout_page'        => [
                'post_title'         => 'Checkout',
                'post_content'       => '[directorist_checkout]',
            ],
            'payment_receipt_page' => [
                'post_title'         => 'Payment Receipt',
                'post_content'       => '[directorist_payment_receipt]',
            ],
            'transaction_failure_page' => [
                'post_title'         => 'Transaction Failure',
                'post_content'       => '[directorist_transaction_failure]',
            ],
        ];

        if (!empty($atbdp_option['enable_monetization'])) {
            foreach ($create_pages as $key => $name) {

                $args = [
                    'post_title' => $name['post_title'],
                    'post_content' => $name['post_content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed'
                ];
                if (empty($atbdp_option[$key])) {
                    $id = wp_insert_post($args);

                    if ($id) {
                        $atbdp_option[$key] = $id;
                    }
                }
            }
        }
        update_option('atbdp_option', $atbdp_option);

        wp_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    public function directorist_step_three()
    {
        $dummy_csv = ATBDP_URL . 'views/admin-templates/import-export/data/dummy.csv';
    ?>
        <div class="atbdp-c-header">
            <h1><?php _e( 'Import Dummy Data', 'directorist' ); ?></h1>
        </div>
        <form method="post" id="atbdp_dummy_form">
            <div class="atbdp-c-body">
                <div class="atbdp_dummy_body">
                    <input type="hidden" id="dummy_csv_file" value="<?php echo $dummy_csv; ?>">
                    <div class="w-form-group">
                        <label for="atbdp-listings-to-import"><?php esc_html_e('Number of Listings to import', 'directorist'); ?></label>
                        <div>
                            <select name="total_listings_to_import" id="atbdp-listings-to-import">
                                <option value="6">6</option>
                                <option value="12" selected>12</option>
                                <option value="18">18</option>
                                <option value="24">24</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                    </div>
                    <div class="w-form-group">
                        <label for="atbdp-import-image"><?php esc_html_e('Import images', 'directorist'); ?></label>
                        <div class="w-toggle-switch">
                            <input type="checkbox" value="1" class="w-switch" id="atbdp-import-image">
                        </div>
                    </div>
                </div>
                <div class="directorist-importer__importing" style="display: none;">
                    <header>
                        <span class="spinner is-active"></span>
                        <h2><?php esc_html_e('Importing', 'directorist');
                            ?></h2>
                        <p><?php esc_html_e('Your listing are now being imported...', 'directorist');
                            ?></p>
                    </header>
                    <section>
                        <span class="importer-notice"><?php esc_html_e('Please don\'t reload the page', 'directorist')?></span>
                        <div class="directorist-importer-wrapper">
                            <progress class="directorist-importer-progress" max="100" value="0"></progress>
                            <span class="directorist-importer-length"></span>
                        </div>
                        <span class="importer-details"></span>
                    </section>
                </div>

                <!-- add dummy contents here -->
            </div>
            <div class="atbdp-c-footer">
                <p class="atbdp-setup-actions step">
                    <a href="<?php echo esc_url($this->get_next_step_link()); ?>" class="w-skip-link"><?php esc_html_e('Skip this step', 'directorist'); ?></a>
                    <?php wp_nonce_field('directorist-setup'); ?>
                    <input type="submit" class="wbtn wbtn-primary" value="<?php esc_attr_e('Continue', 'directorist'); ?>" name="save_step" />
                </p>
            </div>
        </form>
    <?php
    }

    public function directorist_step_three_save()
    {
        check_admin_referer('directorist-setup');

        $_post_data = wp_unslash($_POST);

        $pages = !empty($_post_data['map']) ? $_post_data['map'] : '';
        wp_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    public function directorist_step_four()
    {
        update_option( 'directorist_setup_wizard_completed', true );
        ?>
        <div class="atbdp-c-body">
            <div class="wsteps-done">
                <span class="wicon-done dashicons dashicons-yes"></span>
                <h2><?php esc_html_e('Great, your directory is ready!', 'directorist'); ?></h2>
                <div>
                    <a href="<?php echo esc_url(admin_url().'edit.php?post_type=at_biz_dir'); ?>" class="wbtn wbtn-primary"><?php esc_html_e('Visit Directorist Dashboard', 'directorist'); ?></a>
                    <a href="<?php echo esc_url(admin_url().'post-new.php?post_type=at_biz_dir'); ?>" class="wbtn wbtn-outline-primary"><?php esc_html_e('Create your First Listing', 'directorist'); ?></a>
                </div>
            </div>
        </div>
        <div class="atbdp-c-footer atbdp-c-footer-center">
            <a href="<?php echo esc_url(admin_url()); ?>" class="w-footer-link"><?php esc_html_e('Return to the WordPress Dashboard', 'directorist'); ?></a>
        </div>
    <?php
    }

    public function get_steps()
    {
        return $this->steps;
    }

    /**
     * Introduction step.
     */
    public function directorist_setup_introduction()
    {
    ?>
        <div class="atbdp-c-body">
            <div class="atbdp-c-logo">
                <img src="<?php echo esc_url(DIRECTORIST_ASSETS . 'images/directorist-logo.svg');?>" alt="Directorist">
            </div>
            <h1 class="atbdp-c-intro-title"><?php esc_html_e('Welcome to the world of Directorist!', 'directorist'); ?></h1>
            <p><?php echo wp_kses(__('Thank you for choosing Directorist to amp your business directory. This quick setup wizard will help you <strong>configure the basic settings and get you started in no longer than 3 minutes.</strong>', 'directorist'), ['strong' => []]); ?></p>
            <p><?php esc_html_e('If you don\'t want to run the setup wizard now, you can skip and return to the WordPress dashboard. You can always come back and run the wizard at your convenience.', 'directorist'); ?></p>
        </div>
        <div class="atbdp-c-footer">
            <p class="atbdp-setup-actions step">
                <a href="<?php echo esc_url(admin_url()); ?>" class="wbtn wbtn-white"><?php esc_html_e('Not right now', 'directorist'); ?></a>
                <a href="<?php echo esc_url($this->get_next_step_link()); ?>" class="wbtn wbtn-primary"><?php esc_html_e('Let\'s Go!', 'directorist'); ?></a>
            </p>
        </div>
    <?php
    }

    public function get_next_step_link()
    {
        $keys = array_keys($this->steps);

        return add_query_arg('step', $keys[array_search($this->step, array_keys($this->steps)) + 1]);
    }

    /**
     * Wizard templates
     *
     * @since 2.9.27
     *
     * @return void
     */
    protected function set_setup_wizard_template()
    {
        $this->setup_wizard_header();
        $this->setup_wizard_steps();
        $this->setup_wizard_content();
        $this->setup_wizard_footer();
    }

    /**
     * Setup Wizard Header.
     */
    public function setup_wizard_header()
    {
        set_current_screen();
        $hide = ! isset( $_GET['step'] ) ? 'directorist-setup-wizard-vh' : 'directorist-setup-wizard-vh-none';
    ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>

        <head>
            <meta name="viewport" content="width=device-width" />
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title><?php esc_html_e('Directorist &rsaquo; Setup Wizard', 'directorist'); ?></title>
            <?php wp_print_scripts('directorist-admin-setup-wizard-script'); ?>
            <?php wp_print_scripts('directorist-select2'); ?>
            <?php do_action('admin_print_styles'); ?>
            <?php do_action('admin_head'); ?>
            <?php do_action('directorist_setup_wizard_styles'); ?>
        </head>

        <body class="atbdp-setup wp-core-ui<?php echo get_transient('directorist_setup_wizard_no_wc') ? ' directorist-setup-wizard-activated-wc' : '';  ?> <?php echo $hide; ?>">
            <div class="directorist-setup-wizard-wrapper">
            <?php
            /* $logo_url = ( ! empty( $this->custom_logo ) ) ? $this->custom_logo : plugins_url( 'assets/images/directorist-logo.svg', directorist_FILE );*/
            ?>
            <!--<h1 id="atbdp-logo"><a href="https://wedevs.com/directorist/"><img src="<?php /*echo esc_url( $logo_url ); */ ?>" alt="directorist Logo" width="135" height="auto" /></a></h1>-->
        <?php
    }

    /**
     * Output the steps.
     */
    public function setup_wizard_steps()
    {
        $ouput_steps = $this->steps;
        array_shift($ouput_steps);
        $hide = ! isset( $_GET['step'] ) ? 'atbdp-none' : '';
        ?>

            <ul class="atbdp-setup-steps <?php echo $hide; ?>">
            <li class="atbdsw-logo"><img src="<?php echo esc_url(DIRECTORIST_ASSETS . 'images/directorist-logo.svg');?>" alt="Directorist"></li>
                <?php foreach ($ouput_steps as $step_key => $step) : ?>
                    <li class="<?php
                        if ($step_key === $this->step && 'step-four' != $step_key ) {
                            echo 'active';
                        } elseif ( array_search( $this->step, array_keys($this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
                            echo 'done';
                        } elseif ( isset( $_GET['step'] ) && 'step-four' == $_GET['step'] ) {
                            echo 'done';
                        }
                        $number = 1;
                        if ( 'step-one' == $step_key ) {
                            $number = 1;
                        } else if ( 'step-two' == $step_key ) {
                            $number = 2;
                        } else if ( 'step-three' == $step_key ) {
                            $number = 3;
                        } else if ( 'step-four' == $step_key ) {
                            $number = 4;
                        }
                        ?>"><span class="atbdp-sw-circle"><span><?php echo $number; ?></span> <span class="dashicons dashicons-yes"></span></span><?php echo esc_html( $step['name'] ); ?> </li>
                <?php endforeach; ?>
            </ul>
        <?php
    }

    /**
     * Output the content for the current step.
     */
    public function setup_wizard_content()
    {
        if ( empty( $this->steps[ $this->step ]['view'] ) ) {
            wp_redirect(esc_url_raw(add_query_arg('step', 'introduction')));
            exit;
        }
        $introduction_class = ! isset( $_GET['step'] ) ? 'atbdp_introduction' : '';
        echo '<div class="atbdp-setup-content '. $introduction_class .'">';
        call_user_func($this->steps[$this->step]['view']);
        echo '</div> </div>';
    }

    /**
     * Setup Wizard Footer.
     */
    public function setup_wizard_footer()
    {
        ?>
            <?php if ( 'next_steps' === $this->step ) : ?>
                <a class="atbdp-return-to-dashboard" href="<?php echo esc_url(admin_url()); ?>"><?php esc_html_e('Return to the WordPress Dashboard', 'directorist'); ?></a>
            <?php endif; ?>
        </body>

        </html>
<?php
    }
}
new SetupWizard();