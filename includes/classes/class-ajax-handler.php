<?php
defined('ABSPATH') || die('Direct access is not allowed.');

use \Directorist\Helper;

if (!class_exists('ATBDP_Ajax_Handler')) :

    /**
     * Class ATBDP_Ajax_Handler.
     * It handles all ajax requests from our plugin
     */
    /**
     * Class ATBDP_Ajax_Handler
     */
    class ATBDP_Ajax_Handler
    {

        /**
         * It registers our ajax functions to our ajax hooks
         */
        public function __construct()
        {
            add_action('wp_ajax_atbdp_social_info_handler', array($this, 'atbdp_social_info_handler'));
            add_action('wp_ajax_nopriv_atbdp_social_info_handler', array($this, 'atbdp_social_info_handler'));
            add_action('wp_ajax_remove_listing_review', array($this, 'remove_listing_review'));
            add_action('wp_ajax_save_listing_review', array($this, 'save_listing_review'));
            add_action('wp_ajax_nopriv_save_listing_review', array($this, 'save_listing_review')); // don not allow unregistered user to submit review
            // paginate review
            add_action('wp_ajax_atbdp_review_pagination', array($this, 'atbdp_review_pagination_output'));
            add_action('wp_ajax_nopriv_atbdp_review_pagination', array($this, 'atbdp_review_pagination_output'));

            add_action('wp_ajax_remove_listing', array($this, 'remove_listing')); //delete a listing
            add_action('wp_ajax_update_user_profile', array($this, 'update_user_profile'));

            /*CHECKOUT RELATED STUFF*/
            add_action('wp_ajax_atbdp_format_total_amount', array('ATBDP_Checkout', 'ajax_atbdp_format_total_amount'));
            add_action('wp_ajax_nopriv_atbdp_format_total_amount', array('ATBDP_Checkout', 'ajax_atbdp_format_total_amount'));

            /*REPORT ABUSE*/
            add_action('wp_ajax_atbdp_public_report_abuse', array($this, 'ajax_callback_report_abuse'));
            add_action('wp_ajax_nopriv_atbdp_public_report_abuse', array($this, 'ajax_callback_report_abuse'));

            /*CONTACT FORM*/
            add_action('wp_ajax_atbdp_public_send_contact_email', array($this, 'ajax_callback_send_contact_email'));
            add_action('wp_ajax_nopriv_atbdp_public_send_contact_email', array($this, 'ajax_callback_send_contact_email'));

            /*
             * stuff for handling add to favourites
             */
            add_action('wp_ajax_atbdp_public_add_remove_favorites', array($this, 'atbdp_public_add_remove_favorites'));
            add_action('wp_ajax_nopriv_atbdp_public_add_remove_favorites', array($this, 'atbdp_public_add_remove_favorites'));

            add_action('wp_ajax_atbdp_submit-uninstall-reason', array($this, 'atbdp_deactivate_reason'));
            add_action('wp_ajax_nopriv_atbdp_submit-uninstall-reason', array($this, 'atbdp_deactivate_reason'));

            // location & category child term
            add_action('wp_ajax_bdas_public_dropdown_terms', array($this, 'bdas_dropdown_terms'));
            add_action('wp_ajax_nopriv_bdas_public_dropdown_terms', array($this, 'bdas_dropdown_terms'));
            //custom field search
            add_action('wp_ajax_atbdp_custom_fields_search', array($this, 'custom_field_search'), 10, 1);
            add_action('wp_ajax_nopriv_atbdp_custom_fields_search', array($this, 'custom_field_search'), 10, 1);
            add_action('wp_ajax_atbdp-favourites-all-listing', array($this, 'atbdp_public_add_remove_favorites_all'));
            add_action('wp_ajax_nopriv_atbdp-favourites-all-listing', array($this, 'atbdp_public_add_remove_favorites_all'));
            add_action('wp_ajax_atbdp_post_attachment_upload', array($this, 'atbdp_post_attachment_upload'));
            add_action('wp_ajax_nopriv_atbdp_post_attachment_upload', array($this, 'atbdp_post_attachment_upload'));
            //login
            add_action('wp_ajax_ajaxlogin', array($this, 'atbdp_ajax_login'));
            add_action('wp_ajax_nopriv_ajaxlogin', array($this, 'atbdp_ajax_login'));

            add_action('wp_ajax_atbdp_ajax_quick_login', array($this, 'atbdp_quick_ajax_login'));
            add_action('wp_ajax_nopriv_atbdp_ajax_quick_login', array($this, 'atbdp_quick_ajax_login'));

            // regenerate pages
            add_action('wp_ajax_atbdp_upgrade_old_pages', array($this, 'upgrade_old_pages'));
            // default listing type
            add_action('wp_ajax_atbdp_listing_default_type', array($this, 'atbdp_listing_default_type'));
            // listing type slug edit
            add_action('wp_ajax_directorist_type_slug_change', array($this, 'directorist_type_slug_change'));

            // Guset Reception
            add_action('wp_ajax_atbdp_guest_reception', array($this, 'guest_reception'));
            add_action('wp_ajax_nopriv_atbdp_guest_reception', array($this, 'guest_reception'));

            // custom field
            add_action('wp_ajax_atbdp_custom_fields_listings',                 array($this, 'ajax_callback_custom_fields'), 10, 2);
            add_action('wp_ajax_nopriv_atbdp_custom_fields_listings',          array($this, 'ajax_callback_custom_fields'), 10, 2);
            // add_action('wp_ajax_atbdp_custom_fields_listings_front_selected',        array($this, 'ajax_callback_custom_fields'), 10, 2);
            // add_action('wp_ajax_nopriv_atbdp_custom_fields_listings_front_selected', array($this, 'ajax_callback_custom_fields'), 10, 2);
            // add_action('wp_ajax_atbdp_custom_fields_listings',                       array($this, 'ajax_callback_custom_fields'), 10, 2 );
            // add_action('wp_ajax_atbdp_custom_fields_listings_selected',              array($this, 'ajax_callback_custom_fields'), 10, 2 );

            add_action('wp_ajax_atbdp_listing_types_form', array( $this, 'atbdp_listing_types_form' ) );
            add_action('wp_ajax_nopriv_atbdp_listing_types_form', array( $this, 'atbdp_listing_types_form' ) );

            add_action('wp_ajax_directorist_category_custom_field_seach', array( $this, 'directorist_category_custom_field_seach' ) );
            add_action('wp_ajax_nopriv_directorist_category_custom_field_seach', array( $this, 'directorist_category_custom_field_seach' ) );

            //dashboard become author
            add_action( 'wp_ajax_atbdp_become_author', array( $this, 'atbdp_become_author' ) );
            add_action( 'wp_ajax_atbdp_user_type_approved', array( $this, 'atbdp_user_type_approved' ) );
            add_action( 'wp_ajax_atbdp_user_type_deny', array( $this, 'atbdp_user_type_deny' ) );

            add_action( 'wp_ajax_directorist_prepare_listings_export_file', [ $this, 'handle_prepare_listings_export_file_request' ] );

            add_action('wp_ajax_directorist_ajax_quick_login', array($this, 'directorist_quick_ajax_login'));
            add_action('wp_ajax_nopriv_directorist_ajax_quick_login', array($this, 'directorist_quick_ajax_login'));
        }

        // directorist_quick_ajax_login
        public function directorist_quick_ajax_login() {
            if ( ! check_ajax_referer( 'directorist-quick-login-nonce', 'directorist-quick-login-security', false ) ) {
                wp_send_json([
                    'loggedin' => false,
                    'message' => __('Invalid Nonce', 'directorist'),
                ]);
            }

            if ( is_user_logged_in() ) {
                wp_send_json([
                    'loggedin' => true,
                    'message' => __('Your are already loggedin', 'directorist'),
                ]);
            }

			$user = get_user_by( 'login', $_POST['username'] );
			$user = ( ! $user ) ? get_user_by( 'email', $_POST['username'] ) : $user;
			$has_valid_password = ( wp_check_password( $_POST['password'], $user->data->user_pass, $user->ID ) ) ? true : false;
			$is_valid_user = ( $user && $has_valid_password ) ? true : false;
			$remember = ( ! empty( $_POST['rememberme'] ) ) ? true : false;

			if ( ! $is_valid_user ) {
				wp_send_json([
                    'loggedin' => false,
                    'message'  => __('Wrong username or password.', 'directorist'),
                ]);
			} 

			wp_set_auth_cookie( $user->ID, $remember, is_ssl() );

			wp_send_json([
				'loggedin' => true,
				'message'  => __('Login successful, redirecting...', 'directorist'),
			]);
        }

        // handle_prepare_listings_export_file_request
        public function handle_prepare_listings_export_file_request() {
            $file = Directorist\Listings_Exporter::get_prepared_listings_export_file();

            wp_send_json( $file );
        }

        public function atbdp_user_type_deny() {
            if ( wp_verify_nonce( $_POST['_nonce'], 'atbdp_user_type_deny' ) ) {
                $user_id = ! empty( $_POST['userId'] ) ? $_POST['userId'] : '';
                update_user_meta( $user_id, '_user_type', 'general' );
                wp_send_json( array(
                    'user_type' => __( 'User', 'directorist' )
                )
                );
            }
        }

        public function atbdp_user_type_approved() {
            if ( wp_verify_nonce( $_POST['_nonce'], 'atbdp_user_type_approve' ) ) {
                $user_id = ! empty( $_POST['userId'] ) ? $_POST['userId'] : '';
                update_user_meta( $user_id, '_user_type', 'author' );
                wp_send_json( array(
                    'user_type' => __( 'Author', 'directorist' )
                )
                );
            }
        }

        public function atbdp_become_author() {
            if ( wp_verify_nonce( $_POST['nonce'], 'atbdp_become_author' ) ) {
                $user_id = ! empty( $_POST['userId'] ) ? $_POST['userId'] : '';
                do_action( 'atbdp_become_author', $user_id );
                update_user_meta( $user_id, '_user_type', 'become_author' );
                $success_message = __( 'Sent successfully', 'directorist' );
                wp_send_json($success_message);
            }
        }

        // atbdp_quick_ajax_login
        public function atbdp_quick_ajax_login()
        {
            if ( is_user_logged_in() ) {
                wp_send_json([
                    'loggedin' => true,
                    'message' => __('Your are already loggedin', 'directorist'),
                ]);
            }

            $keep_signed_in = ( ! empty( $_POST['rememberme'] ) ) ? true : false;

            $info = [];
            $info['user_login']    = $_POST['username'];
            $info['user_password'] = $_POST['password'];
            $info['remember']      = $keep_signed_in;

            $user_signon = wp_signon( $info, false );

            if ( is_wp_error($user_signon) ) {
                wp_send_json([
                    'loggedin' => false,
                    'message'  => __('Wrong username or password.', 'directorist')
                ]);

            } else {
                wp_set_current_user($user_signon->ID);

                wp_send_json([
                    'loggedin' => true,
                    'message'  => __('Login successful, redirecting...', 'directorist')
                ]);
            }
        }

        // atbdp_listing_types_form
        public function atbdp_listing_types_form() {
            $listing_type    = !empty( $_POST['listing_type'] ) ? esc_attr( $_POST['listing_type'] ) : '';
            $term            = get_term_by( 'slug', $listing_type, ATBDP_TYPE );
            $listing_type_id = ( $term ) ? $term->term_id : 0;
            $searchform      = new \Directorist\Directorist_Listing_Search_Form( 'search_form', $listing_type_id, [] );
            $class           = 'directorist-search-form-top directorist-flex directorist-align-center directorist-search-form-inline';
            // search form
            ob_start();
            ?>
				<div class="<?php echo esc_attr( $class ); ?>">
                    <?php
                    foreach ( $searchform->form_data[0]['fields'] as $field ){
                        $searchform->field_template( $field );
                    }
                    if ( $searchform->more_filters_display !== 'always_open' ){
                        $searchform->more_buttons_template();
                    }
                    ?>

                </div>

                <?php
                if ( $searchform->more_filters_display == 'always_open' ){
                    $searchform->advanced_search_form_fields_template();
                }
                else {
                    if ($searchform->has_more_filters_button) { ?>
                        <div class="<?php Helper::search_filter_class( $searchform->more_filters_display ); ?>">
                            <?php $searchform->advanced_search_form_fields_template();?>
                        </div>
                        <?php
                    }
                }
            $search_form =  ob_get_clean();

            ob_start();

			$searchform->top_categories_template();

            $popular_categories = ob_get_clean();

            wp_send_json( array(
                'search_form'          => $search_form,
                'atbdp_search_listing' => Directorist\Script_Helper::get_search_script_data( [ 'directory_type_id' => $listing_type_id  ] ),
                'popular_categories'   => $popular_categories
             ) );
        }

        // directorist_category_custom_field_seach
        public function directorist_category_custom_field_seach() {
            $listing_type    = !empty( $_POST['listing_type'] ) ? esc_attr( $_POST['listing_type'] ) : '';
            $term            = get_term_by( 'slug', $listing_type, ATBDP_TYPE );
            $listing_type_id = ( $term ) ? $term->term_id : 0;
            $searchform      = new \Directorist\Directorist_Listing_Search_Form( 'search_form', $listing_type_id, [] );
            $class           = 'directorist-search-form-top directorist-flex directorist-align-center directorist-search-form-inline';
            // search form
            ob_start();
            ?>
				<div class="<?php echo esc_attr( $class ); ?>">
                    <?php
                    foreach ( $searchform->form_data[0]['fields'] as $field ){
                        $searchform->field_template( $field );
                    }
                    if ( $searchform->more_filters_display !== 'always_open' ){
                        $searchform->more_buttons_template();
                    }
                    ?>

                </div>

                <?php
                if ( $searchform->more_filters_display == 'always_open' ){
                    $searchform->advanced_search_form_fields_template();
                }
                else {
                    if ($searchform->has_more_filters_button) { ?>
                        <div class="<?php Helper::search_filter_class( $searchform->more_filters_display ); ?>">
                            <?php $searchform->advanced_search_form_fields_template();?>
                        </div>
                        <?php
                    }
                }
            $search_form =  ob_get_clean();


            wp_send_json( array(
                'search_form'          => $search_form,
             ) );
        }

        public function atbdp_listing_default_type() {
            $type_id = sanitize_key( $_POST[ 'type_id' ] );
            $listing_types = get_terms([
                'taxonomy'   => 'atbdp_listing_types',
                'hide_empty' => false,
              ]);
              foreach ($listing_types as $listing_type) {
                if( $listing_type->term_id !== (int) $type_id ){
                    update_term_meta( $listing_type->term_id, '_default', false );
                }
              }
            update_term_meta( $type_id, '_default', true );
            wp_send_json( 'Updated Successfully!' );
        }

        public function directorist_type_slug_change() {
            $type_id        = isset( $_POST[ 'type_id' ] ) ? sanitize_key( $_POST[ 'type_id' ] ) : '';
            $update_slug    = isset( $_POST[ 'update_slug' ] ) ? sanitize_key( $_POST[ 'update_slug' ] ) : '';
           
            $directory_slugs = [];
            $listing_types = get_terms( [
                'taxonomy'   => ATBDP_TYPE,
                'hide_empty' => false,
                ] );
            if( $listing_types ) {
                foreach( $listing_types as $listing_type ){
                    $directory_slugs[] = $listing_type->slug;
                    if( $type_id == $listing_type->term_id ) {
                        $old_slug = $listing_type->slug; 
                    }
                }
            }

            if( in_array( $update_slug, $directory_slugs ) ) {
                wp_send_json( array(
                    'error' => __('This slug already in use.', 'directorist'),
                    'old_slug' => ! empty( $old_slug ) ? $old_slug : '',
                ) );
            } else {
                $update_type_slug = wp_update_term( $type_id, ATBDP_TYPE, array( 'slug' => $update_slug ) );
                if( $update_type_slug ) {
                    wp_send_json( array(
                        'success' => __('Slug Changes Successfully.', 'directorist')
                    ) );
                }
            }
        }

        public function ajax_callback_custom_fields() {
            $listing_type = !empty( $_POST['directory_type'] ) ? sanitize_text_field( $_POST['directory_type'] ) : '';
            $categories = !empty( $_POST['term_id'] ) ? atbdp_sanitize_array( $_POST['term_id'] ) : [];
            $post_id = !empty( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';
            // wp_send_json($post_id);
            $template = '';
            $submission_form_fields = [];

            if ( is_string( $listing_type ) && ! is_numeric( $listing_type ) ) {
                $listing_term = get_term_by( 'slug', $listing_type, ATBDP_DIRECTORY_TYPE );
                $listing_type = ( $listing_term ) ? $listing_term->term_id : $listing_type;
            }

            if ( $listing_type ) {
                $submission_form = get_term_meta( $listing_type, 'submission_form_fields', true );
                $submission_form_fields = $submission_form['fields'];
            }

             foreach( $submission_form_fields as $key => $value ){
                // $value['request_from_no_admin'] = true;
                $category = !empty( $value['category'] ) ? $value['category'] : '';
                if( $category ) {
                    if( in_array( $category, $categories ) ) {
                        ob_start();
                        \Directorist\Directorist_Listing_Form::instance()->add_listing_category_custom_field_template( $value, $post_id );
                        $template .= ob_get_clean();
                    }
                }
            }
           wp_send_json( $template );
        }

        // guest_reception
        public function guest_reception() {

            // Get the data
            $email = ( ! empty( $_GET['email'] ) ) ? $_GET['email'] : '';
            $email = ( ! empty( $_POST['email'] ) ) ? $_POST['email'] : $email;

            // Data Validation
            // ---------------------------
            $error_log = [];

            // Validate email
            if ( empty( $email ) ) {
                $error_log['email'] = [
                    'key' => 'invalid_email',
                    'message' => 'Invalid Email',
                ];
            }

            // Send error log if has any error
            if ( ! empty( $error_log  ) ) {
                $data = [
                    'status'      => false,
                    'status_code' => 'invalid_data',
                    'message'     => 'Invalid data found',
                    'data'        => [
                        'error_log' => $error_log
                    ],
                ];

                wp_send_json( $data, 200 );
            }

            // User Validation
            // ---------------------------
            // Check if user exist
            $email = esc_html( $email  );
            $email = sanitize_email( $email );
            $user  = get_user_by( 'email', $email );

            if ( $user ) {
                $data = [
                    'status'      => true,
                    'status_code' => 'user_exist',
                    'message'     => 'User already existed',
                    'data'        => [
                        'user_id' => $user->ID
                    ],
                ];

                wp_send_json( $data, 200 );
            }

            // User Registration
            // ---------------------------
            // Register the user
            $user_name = preg_replace( '/@.+$/', '', $email );
            $rand      = rand( 10000, 90000 );
            $username  = "{$user_name}_{$rand}";
            $new_user  = register_new_user( $username, $email );

            if ( ! $new_user ) {
                $data = [
                    'status'      => false,
                    'status_code' => 'unknown_error',
                    'message'     => 'Sorry, something went wrong, please try again',
                    'data'        => null,
                ];

                wp_send_json( $data, 200 );
            }

            $data = [
                'status'      => true,
                'status_code' => 'registration_successfull',
                'message'     => 'The user is registrated successfully',
                'data'        => [
                    'user_id' => $new_user,
                ],
            ];

            wp_send_json( $data, 200 );
        }

        /**
         * It upgrades old pages and make them compatible with new shortcodes
         */
        public function upgrade_old_pages()
        {
            update_option('atbdp_pages_version', 0);
            wp_send_json_success(__('Congratulations! All old pages have been updated successfully', 'directorist'));
        }

        public function atbdp_ajax_login()
        {
            // First check the nonce, if it fails the function will break
            $check_ajax_referer = check_ajax_referer('ajax-login-nonce', 'security', false);

            if (!$check_ajax_referer) {
                echo json_encode(array(
                    'loggedin' => false,
                    'message' => __('Something went wrong, please reload the page', 'directorist'),
                    'nonce_faild' => true,
                ));

                die();
            }

            if (is_user_logged_in()) {
                echo json_encode(array(
                    'loggedin' => true,
                    'message' => __('Login successful, redirecting...', 'directorist'),
                ));

                die();
            }

            // Nonce is checked, get the POST data and sign user on
            $keep_signed_in = ($_POST['rememberme'] === 1 || $_POST['rememberme'] === '1') ? true : false;

            $info = array();
            $info['user_login'] = $_POST['username'];
            $info['user_password'] = $_POST['password'];
            $info['remember'] = $keep_signed_in;

            $user_signon = wp_signon($info, $keep_signed_in);
            if (is_wp_error($user_signon)) {
                echo json_encode(array(
                    'loggedin' => false,
                    'message' => __('Wrong username or password.', 'directorist')
                ));
            } else {
                wp_set_current_user($user_signon->ID);

                echo json_encode(array(
                    'loggedin' => true,
                    'message' => __('Login successful, redirecting...', 'directorist')
                ));
            }

            die();
        }

        public function atbdp_post_attachment_upload()
        {
            // security
            check_ajax_referer('atbdp_attachment_upload', '_ajax_nonce');
            $field_id = isset($_POST["imgid"]) ? esc_attr($_POST["imgid"]) : '';
            $post_id = isset($_POST["post_id"]) ? absint($_POST["post_id"]) : '';
            // set directory temp upload dir
            add_filter('upload_dir', array(__CLASS__, 'temp_upload_dir'));

            $fixed_file = $_FILES[$field_id . 'async-upload'];

            // handle file upload
            $status = wp_handle_upload($fixed_file, array(
                'test_form' => true,
                'action' => 'atbdp_post_attachment_upload'
            ));
            // unset GD temp upload dir
            remove_filter('upload_dir', array(__CLASS__, 'temp_upload_dir'));

            if (!isset($status['url']) && isset($status['error'])) {
                print_r($status);
            }

            // send the uploaded file url in response
            if (isset($status['url']) && $post_id) {

                // insert to DB
                $file_info = update_post_meta($post_id, $field_id, $status['url']);

                if (is_wp_error($file_info)) {
                    //atbdp_error_log( $file_info->get_error_message(), 'post_attachment_upload', __FILE__, __LINE__ );
                } else {
                    $wp_upload_dir = wp_upload_dir();
                    echo $status['url'];
                }
            } elseif (isset($status['url'])) {
                echo $status['url'];
            } else {
                echo 'x';
            }

            // if file exists it should have been moved if uploaded correctly so now we can remove it
            /*if(!empty($status['file']) && $post_id){
                wp_delete_file( $status['file'] );
            }*/
            // atbdp_Media::post_attachment_upload();
            //ATBDP()->atbdp_Media->post_attachment_upload();
            wp_die();
        }

        public static function temp_upload_dir($upload)
        {
            $upload['subdir'] = "/atbdp_temp";
            $upload['path'] = $upload['basedir'] . $upload['subdir'];
            $upload['url'] = $upload['baseurl'] . $upload['subdir'];

            return $upload;
        }

        /**
         * Add or Remove favourites.
         *
         * @since    4.0
         * @access   public
         */
        public function atbdp_public_add_remove_favorites_all()
        {

            $user_id = get_current_user_id();
            $post_id = (int) $_POST['post_id'];

            if (!$user_id) {
                $data = "login_required";
                echo esc_attr($data);
                wp_die();
            }

            $favourites = (array) get_user_meta($user_id, 'atbdp_favourites', true);

            if (in_array($post_id, $favourites)) {
                if (($key = array_search($post_id, $favourites)) !== false) {
                    unset($favourites[$key]);
                }
            } else {
                $favourites[] = $post_id;
            }

            $favourites = array_filter($favourites);
            $favourites = array_values($favourites);

            delete_user_meta($user_id, 'atbdp_favourites');
            update_user_meta($user_id, 'atbdp_favourites', $favourites);

            $favourites = (array) get_user_meta(get_current_user_id(), 'atbdp_favourites', true);
            if (in_array($post_id, $favourites)) {
                $data = $post_id;
            } else {
                $data = false;
            }
            echo wp_json_encode($data);
            wp_die();
        }

        /**
         * Add or Remove favourites.
         *
         * @since    4.0
         * @access   public
         */
        public function atbdp_public_add_remove_favorites()
        {

            $post_id = (int) $_POST['post_id'];

            $favourites = (array) get_user_meta(get_current_user_id(), 'atbdp_favourites', true);

            if (in_array($post_id, $favourites)) {
                if (($key = array_search($post_id, $favourites)) !== false) {
                    unset($favourites[$key]);
                }
            } else {
                $favourites[] = $post_id;
            }

            $favourites = array_filter($favourites);
            $favourites = array_values($favourites);

            delete_user_meta(get_current_user_id(), 'atbdp_favourites');
            update_user_meta(get_current_user_id(), 'atbdp_favourites', $favourites);

            echo the_atbdp_favourites_link($post_id);

            wp_die();
        }

        /**
         * It update user from from the front end dashboard using ajax
         */
        public function update_user_profile()
        {
            // process the data and the return a success
            if ($_POST['user']) {
                // passed the security
                // update the user data and also its meta
                $user_id = !empty($_POST['user']['ID']) ? absint($_POST['user']['ID']) : get_current_user_id();

                $old_pro_pic_id = get_user_meta($user_id, 'pro_pic', true);
                if (!empty($_POST['profile_picture_meta']) && count($_POST['profile_picture_meta'])) {
                    $meta_data = $_POST['profile_picture_meta'][0];

                    if ( 'true' !== $meta_data['oldFile'] ) {
                        foreach ($_FILES as $file => $array) {
                            $id = $this->insert_attachment( $file, 0 );
                            update_user_meta( $user_id, 'pro_pic', $id );
                        }
                    }
                } else {
                    update_user_meta($user_id, 'pro_pic', '');
                }
                $success = ATBDP()->user->update_profile($_POST['user']); // update_profile() will handle sanitisation, so we can just the pass the data through it
                if ($success) {
                    wp_send_json_success(array('message' => __('Profile updated successfully', 'directorist')));
                } else {
                    wp_send_json_error(array('message' => __('Ops! something went wrong. Try again.', 'directorist')));
                };
            }
            wp_die();
        }

        private function insert_attachment($file_handler, $post_id, $setthumb = 'false')
        {
            // check to make sure its a successful upload
            if ($_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK) __return_false();

            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');

            $attach_id = media_handle_upload($file_handler, $post_id);

            if ($setthumb) update_post_meta($post_id, '_thumbnail_id', $attach_id);
            return $attach_id;
        }

        public function remove_listing()
        {
            // delete the listing from here. first check the nonce and then delete and then send success.
            // save the data if nonce is good and data is valid
            if (valid_js_nonce() && !empty($_POST['listing_id'])) {
                $pid = (int) $_POST['listing_id'];
                // Check if the current user is the owner of the post
                $listing = get_post($pid);
                // delete the post if the current user is the owner of the listing
                if (get_current_user_id() == $listing->post_author || current_user_can('delete_at_biz_dirs')) {
                    $success = ATBDP()->listing->db->delete_listing_by_id($pid);
                    if ($success) {
                        echo 'success';
                    } else {
                        echo 'error';
                    }
                }
            } else {
                echo 'error';
                // show error message
            }
            wp_die();
        }

        public function remove_listing_review()
        {
                if (!empty($_POST['review_id'])) {
                    $success = ATBDP()->review->db->delete(absint($_POST['review_id']));
                    if ($success) {
                        echo 'success';
                    } else {
                        echo 'error';
                    }
                }else {
                    echo 'error';
                }
            wp_die();
        }


        public function atbdp_review_pagination_output()
        {
            $msg = '';
            if (isset($_POST['page'])) {
                $enable_reviewer_img = get_directorist_option('enable_reviewer_img', 1);
                $enable_reviewer_content = get_directorist_option('enable_reviewer_content', 1);
                $review_num = get_directorist_option('review_num', 5);
                // Sanitize the received page
                $page = sanitize_text_field($_POST['page']);
                $listing_id = sanitize_text_field($_POST['listing_id']);
                $cur_page = $page;
                $page -= 1;
                // Set the number of results to display
                $per_page = $review_num;
                $previous_btn = true;
                $next_btn = true;
                $first_btn = true;
                $last_btn = true;
                $start = $page * $per_page;
                // Query the necessary reviews
                $reviews = ATBDP()->review->db->get_reviews_by('post_id', (int) $listing_id, $start, $per_page);
                // At the same time, count the number of queried review
                $count = ATBDP()->review->db->count(array('post_id' => $listing_id));
                // Loop into all the posts
                if (!empty($reviews)) {
                    foreach ($reviews as $key => $review) :
                        $author_id = $review->by_user_id;
                        $u_pro_pic = get_user_meta($author_id, 'pro_pic', true);
                        $u_pro_pic = !empty($u_pro_pic) ? wp_get_attachment_image_src($u_pro_pic, 'thumbnail') : '';
                        $avatar_img = get_avatar($author_id, apply_filters('atbdp_avatar_size', 32));

                        // Set the desired output into a variable
                        $msg .= '<div class="directorist-signle-review" id="directorist-single-review-' . $review->id . '">';
                        $msg .= '<div class="directorist-signle-review__top">';
                        $msg .= '<div class="directorist-signle-review-avatar-wrap">';
                        if (!empty($enable_reviewer_img)) {
                            $msg .= '<div class="directorist-signle-review-avatar">';
                            if (empty($u_pro_pic)) {
                                $msg .= $avatar_img;
                            }
                            if (!empty($u_pro_pic)) {
                                $msg .= '<img src="' . esc_url($u_pro_pic[0]) . '" alt="Avatar Image">';
                            }
                            $msg .= '</div>';
                        }
                        $msg .= '<div class="directorist-signle-review-avatar__info">';
                        $msg .= '<p>' . esc_html($review->name) . '</p>';
                        $msg .= '<span class="directorist-signle-review-time">' .
                            sprintf(__('%s ago', 'directorist'), human_time_diff(strtotime($review->date_created), current_time('timestamp'))) . '</span>';
                        $msg .= '</div>';
                        $msg .= '</div>';
                        $msg .= '<div class="directorist-rated-stars">';
                        $msg .= ATBDP()->review->print_static_rating($review->rating);
                        $msg .= '</div>';
                        $msg .= '</div>';
                        if( !empty( $enable_reviewer_content ) ) {
                        $msg .= '<div class="directorist-signle-review__content">';
                        $msg .= '<p>' . stripslashes(esc_html($review->content)) . '</p>';
                        $msg .= '</div>';
                        }
                        $msg .= '</div>';
                    endforeach;
                } else {
                    $msg .= ' <div class="directorist-alert directorist-alert-info" id="review_notice">
                                <div class="directorist-alert__content">
                                    <span class="' . atbdp_icon_type(false) . '-info-circle" aria-hidden="true"></span> ' .
                                    __('No reviews found. Be the first to post a review !', 'directorist') . '</div>
                                </div>';
                }
                // Optional, wrap the output into a container
                $msg = "<div class='atbdp-universal-content'>" . $msg . "</div><br class = 'clear' />";

                // This is where the magic happens
                $no_of_paginations = ceil($count / $per_page);
                if ($cur_page >= 5) {
                    $start_loop = $cur_page - 2;
                    if ($no_of_paginations > $cur_page + 2)
                        $end_loop = $cur_page + 2;
                    else if ($cur_page <= $no_of_paginations && $cur_page > $no_of_paginations - 4) {
                        $start_loop = $no_of_paginations - 4;
                        $end_loop = $no_of_paginations;
                    } else {
                        $end_loop = $no_of_paginations;
                    }
                } else {
                    $start_loop = 1;
                    if ($no_of_paginations > 5)
                        $end_loop = 5;
                    else
                        $end_loop = $no_of_paginations;
                }

                $pag_container = '';
                // Pagination Buttons logic
                $pag_container .= "
        <div class='atbdp-universal-pagination'>
            <ul>";

                if ($previous_btn && $cur_page > 1) {
                    $pre = $cur_page - 1;
                    $pag_container .= "<li data-page='$pre' class='atbd-active'><i class='la la-angle-left'></i></li>";
                } else if ($previous_btn) {
                    $pag_container .= "<li class='atbd-inactive'><i class='la la-angle-left'></i></li>";
                }
                if ($first_btn && $cur_page > 1) {
                    $first_class = 'atbd-active';
                } else if ($first_btn) {
                    $first_class = 'atbd-selected';
                }
                $pag_container .= "<li data-page='1' class='" . $first_class . "'>1</li>";
                for ($i = $start_loop; $i <= $end_loop; $i++) {
                    if ($i === 1 || $i === $no_of_paginations) continue;
                    if (($no_of_paginations <= 5) && ($no_of_paginations == $i)) continue;
                    $dot_ = (int) $cur_page + 2;
                    $backward = ($cur_page == $no_of_paginations) ? 4 : (($cur_page == $no_of_paginations - 1) ? 3 : 2);
                    $dot__ = (int) $cur_page - $backward;
                    // show dot if current page say 'i have some neighbours left form mine'
                    if ($cur_page > 4) {
                        if (($dot__ == $i)) {
                            $jump = $i - 5;
                            $jump = $jump < 1 ? 1 : $jump;
                            $pag_container .= "<li data-page='$jump' class='atbd-page-jump-back atbd-active' title='" . __('Previous 5 Pages', 'directorist') . "'><i class='la la-ellipsis-h la_d'></i> <i class='la la-angle-double-left la_h'></i></li>";
                        }
                    }
                    if ($cur_page == $i) {
                        $pag_container .= "<li data-page='$i' class = 'atbd-selected' >{$i}</li>";
                    } else {
                        $pag_container .= "<li data-page='$i' class='atbd-active'>{$i}</li>";
                    }
                    // show dot if current page say 'i have some neighbours right form mine'
                    if (($cur_page > 4)) {
                        if (($dot_ == $i)) {
                            $jump = $i + 5;
                            $jump = $jump > $no_of_paginations ? $no_of_paginations : $jump;
                            $pag_container .= "<li data-page='$jump' class='atbd-page-jump-up atbd-active' title='" . __('Next 5 Pages', 'directorist') . "'><i class='la la-ellipsis-h la_d'></i> <i class='la la-angle-double-right la_h'></i></li>";
                        }
                    }
                    // show dot after first 5
                    if ((($cur_page == 1) || ($cur_page == 2) || ($cur_page == 3) || ($cur_page == 4)) && ($no_of_paginations > 5)) {
                        $jump = $i + 5;
                        $jump = $jump > $no_of_paginations ? $no_of_paginations : $jump;
                        if ($i == 5) {
                            $pag_container .= "<li data-page='$jump' class='atbd-page-jump-up atbd-active' title='" . __('Next 5 Pages', 'directorist') . "'><i class='la la-ellipsis-h la_d'></i> <i class='la la-angle-double-right la_h'></i></li>";
                        }
                    }
                }


                if ($last_btn && $cur_page < $no_of_paginations) {
                    $last_class = 'atbd-active';
                } else if ($last_btn) {
                    $last_class = 'atbd-selected';
                }
                $pag_container .= "<li data-page='$no_of_paginations' class='" . $last_class . "'>{$no_of_paginations}</li>";

                if ($next_btn && $cur_page < $no_of_paginations) {
                    $nex = $cur_page + 1;
                    $pag_container .= "<li data-page='$nex' class='atbd-active'><i class='la la-angle-right'></i></li>";
                } else if ($next_btn) {
                    $pag_container .= "<li class='atbd-inactive'><i class='la la-angle-right'></i></li>";
                }

                $pag_container = $pag_container . "
            </ul>
        </div>";
                // We echo the final output
                echo '<div class = "atbdp-pagination-content">' . $msg . '</div>';
                if (!empty($count) && $count > $review_num) {
                    echo '<div class = "atbdp-pagination-nav">' . $pag_container . '</div>';
                }
            }
            // Always exit to avoid further execution
            exit();
        }
        public function save_listing_review()
        {
            $guest_review = get_directorist_option('guest_review', 0);
            $guest_email = isset($_POST['guest_user_email']) ? esc_attr($_POST['guest_user_email']) : '';
            if ($guest_review && $guest_email) {
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
                    $data = array(
                        'error' => __('Email already exists!', 'directorist')
                    );
                    echo wp_json_encode($data);
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
            // save the data if nonce is good and data is valid

            if ($this->validate_listing_review()) {
                $u_name = !empty($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
                $u_email = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
                $user = wp_get_current_user();
                $data = array(
                    'post_id' => absint($_POST['post_id']),
                    'name' => !empty($user->display_name) ? $user->display_name : $u_name,
                    'email' => !empty($user->user_email) ? $user->user_email : $u_email,
                    'content' => !empty( $_POST['content'] ) ? sanitize_textarea_field( $_POST['content'] ) : '',
                    'rating' => floatval($_POST['rating']),
                    'by_guest' => !empty($user->ID) ? 0 : 1,
                    'by_user_id' => !empty($user->ID) ? $user->ID : 0,
                );
                $approve_immediately = get_directorist_option('approve_immediately', 1);
                $review_duplicate = !empty($_POST['review_duplicate']) ? sanitize_text_field($_POST['review_duplicate']) : '';
                if (empty($approve_immediately)) {
                    if (empty($review_duplicate)) {
                        $this->atbdp_send_email_review_to_admin();
                        send_review_for_approval($data);
                    }
                } elseif ($id = ATBDP()->review->db->add($data)) {

                    $reviewer_id = ( ! empty( $data['by_guest'] ) ) ? $data['by_guest'] : $data['by_user_id'];

                    $required = [
                        'post_id' => $_POST['post_id'],
                        'reviewer_id' => $reviewer_id,
                        'rating' => floatval($_POST['rating']),
                        'status' => 'published',
                    ];

                    $review_meta = array_merge( $data, $required );

                    Helper::add_listings_review_meta( $review_meta );

                    $this->atbdp_send_email_review_to_user();
                    $this->atbdp_send_email_review_to_admin();

                    wp_send_json_success(array( 'id' => $id, 'date' => date(get_option('date_format'))));
                }
            } else {
                echo 'Errors: make sure you wrote something about your review.';
                // show error message
            }


            die();
        }

        /*
         * send email to listing's owner for review
         * */
        public function atbdp_send_email_review_to_user()
        {

            if (!in_array('listing_review', get_directorist_option('notify_user', array()))) return false;
            // sanitize form values
            $post_id = (int) $_POST["post_id"];
            $message = esc_textarea($_POST["content"]);

            // vars
            $user = wp_get_current_user();
            $site_name = get_bloginfo('name');
            $site_url = get_bloginfo('url');
            $listing_title = get_the_title($post_id);
            $listing_url = get_permalink($post_id);

            $placeholders = array(
                '{site_name}' => $site_name,
                '{site_link}' => sprintf('<a href="%s">%s</a>', $site_url, $site_name),
                '{site_url}' => sprintf('<a href="%s">%s</a>', $site_url, $site_url),
                '{listing_title}' => $listing_title,
                '{listing_link}' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_title),
                '{listing_url}' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_url),
                '{sender_name}' => $user->display_name,
                '{sender_email}' => $user->user_email,
                '{message}' => $message
            );
            $send_email = get_directorist_option('admin_email_lists');

            $to = $user->user_email;

            $subject = __('[{site_name}] New review at "{listing_title}"', 'directorist');
            $subject = strtr($subject, $placeholders);

            $message = __("Dear User,<br /><br />A new review at {listing_url}.<br /><br />", 'directorist');
            $message = strtr($message, $placeholders);

            $headers = "From: {$user->display_name} <{$user->user_email}>\r\n";
            $headers .= "Reply-To: {$user->user_email}\r\n";

            // return true or false, based on the result
            return ATBDP()->email->send_mail($to, $subject, $message, $headers) ? true : false;
        }

        /*
         * send email to admin for review
         * */
        public function atbdp_send_email_review_to_admin()
        {

            if (get_directorist_option('disable_email_notification')) return false; //vail if email notification is off

            if (!in_array('listing_review', get_directorist_option('notify_admin', array()))) return false; // vail if order created notification to admin off
            // sanitize form values
            $post_id = (int) $_POST["post_id"];
            $message = !empty( $_POST["content"] ) ? esc_textarea( $_POST["content"] ) : '';

            // vars
            $user = wp_get_current_user();
            $site_name = get_bloginfo('name');
            $site_url = get_bloginfo('url');
            $listing_title = get_the_title($post_id);
            $listing_url = get_permalink($post_id);

            $placeholders = array(
                '{site_name}' => $site_name,
                '{site_link}' => sprintf('<a href="%s">%s</a>', $site_url, $site_name),
                '{site_url}' => sprintf('<a href="%s">%s</a>', $site_url, $site_url),
                '{listing_title}' => $listing_title,
                '{listing_link}' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_title),
                '{listing_url}' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_url),
                '{sender_name}' => $user->display_name,
                '{sender_email}' => $user->user_email,
                '{message}' => $message
            );
            $send_email = get_directorist_option('admin_email_lists');

            $to = !empty($send_email) ? $send_email : get_bloginfo('admin_email');

            $subject = __('[{site_name}] New review at "{listing_title}"', 'directorist');
            $subject = strtr($subject, $placeholders);

            $message = __("Dear Administrator,<br /><br />A new review at {listing_url}.<br /><br />Name: {sender_name}<br />Email: {sender_email}", 'directorist');
            $message = strtr($message, $placeholders);

            $headers = "From: {$user->display_name} <{$user->user_email}>\r\n";
            $headers .= "Reply-To: {$user->user_email}\r\n";

            // return true or false, based on the result
            return ATBDP()->email->send_mail($to, $subject, $message, $headers) ? true : false;
        }


        /**
         * It checks if the user has filled up proper data for adding a review
         * @return bool It returns true if the review data is perfect and false otherwise
         */
        public function validate_listing_review()
        {
            $enable_reviewer_content = get_directorist_option( 'enable_reviewer_content', 1 );
            $required_reviewer_content = get_directorist_option( 'required_reviewer_content', 1 );
            if (!empty($_POST['rating']) && ( empty( $enable_reviewer_content ) || ( !empty( $_POST['content'] ) || empty( $required_reviewer_content ) ) ) && !empty($_POST['post_id'])) {
                return true;
            }
            return false;
        }

        /**
         *  Add new Social Item in the member page in response to Ajax request
         */
        public function atbdp_social_info_handler()
        {
            $id = ( ! empty( $_POST['id'] ) ) ? absint( $_POST['id'] ) : 0;

            $path = 'listing-form/social-item';

            Directorist\Helper::get_template( $path, array( 'id' => $id ) );

            die();
        }

        public function atbdp_email_admin_report_abuse()
        {

            // sanitize form values
            $post_id = (int) $_POST["post_id"];
            $message = esc_textarea($_POST["message"]);

            // vars
            $user = wp_get_current_user();
            $site_name = get_bloginfo('name');
            $site_url = get_bloginfo('url');
            $listing_title = get_the_title($post_id);
            $listing_url = get_permalink($post_id);

            $placeholders = array(
                '{site_name}' => $site_name,
                '{site_link}' => sprintf('<a href="%s">%s</a>', $site_url, $site_name),
                '{site_url}' => sprintf('<a href="%s">%s</a>', $site_url, $site_url),
                '{listing_title}' => $listing_title,
                '{listing_link}' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_title),
                '{listing_url}' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_url),
                '{sender_name}' => $user->display_name,
                '{sender_email}' => $user->user_email,
                '{message}' => $message
            );
            $send_email = get_directorist_option('admin_email_lists');

            $to = !empty($send_email) ? $send_email : get_bloginfo('admin_email');

            $subject = __('{site_name} Report Abuse via "{listing_title}"', 'directorist');
            $subject = strtr($subject, $placeholders);

            $message = __("Dear Administrator,<br /><br />This is an email abuse report for a listing at {listing_url}.<br /><br />Name: {sender_name}<br />Email: {sender_email}<br />Message: {message}", 'directorist');
            $message = strtr($message, $placeholders);
            $message = atbdp_email_html($subject, $message);
            $headers = "From: {$user->display_name} <{$user->user_email}>\r\n";
            $headers .= "Reply-To: {$user->user_email}\r\n";

            // return true or false, based on the result
            return ATBDP()->email->send_mail($to, $subject, $message, $headers) ? true : false;
        }

        public function ajax_callback_report_abuse()
        {


            $data = array('error' => 0);


            if ($this->atbdp_email_admin_report_abuse()) {

                $data['message'] = __('Your message sent successfully.', 'directorist');
            } else {

                $data['error'] = 1;
                $data['message'] = __('Sorry! Please try again.', 'directorist');
            }


            echo wp_json_encode($data);
            wp_die();
        }

        /**
         * Send contact message to the listing owner.
         *
         * @return   string    $result    Message based on the result.
         * @since    4.0.0
         *
         */
        function atbdp_email_listing_owner_listing_contact()
        {
            // sanitize form values
            $post_id = (int) $_POST["post_id"];
            $name = sanitize_text_field($_POST["name"]);
            $email = sanitize_email($_POST["email"]);
            $listing_email = get_post_meta($post_id, '_email', true);
            $message = stripslashes(esc_textarea($_POST["message"]));
            // vars
            $post_author_id = get_post_field('post_author', $post_id);
            $user = get_userdata($post_author_id);
            $site_name = get_bloginfo('name');
            $site_url = get_bloginfo('url');
            $site_email = get_bloginfo('admin_email');
            $listing_title = get_the_title($post_id);
            $listing_url = get_permalink($post_id);
            $listing_url = ATBDP_Permalink::get_listing_permalink($post_id, $listing_url);
            $date_format = get_option('date_format');
            $time_format = get_option('time_format');
            $current_time = current_time('timestamp');
            $contact_email_subject = get_directorist_option('email_sub_listing_contact_email');
            $contact_email_body = get_directorist_option('email_tmpl_listing_contact_email');
            $user_email = get_directorist_option('user_email', 'author');

            $placeholders = array(
                '==NAME==' => $user->display_name,
                '==USERNAME==' => $user->user_login,
                '==SITE_NAME==' => $site_name,
                '==SITE_LINK==' => sprintf('<a href="%s">%s</a>', $site_url, $site_name),
                '==SITE_URL==' => sprintf('<a href="%s">%s</a>', $site_url, $site_url),
                '==LISTING_TITLE==' => $listing_title,
                '==LISTING_LINK==' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_title),
                '==LISTING_URL==' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_url),
                '==SENDER_NAME==' => $name,
                '==SENDER_EMAIL==' => $email,
                '==MESSAGE==' => $message,
                '==TODAY==' => date_i18n($date_format, $current_time),
                '==NOW==' => date_i18n($date_format . ' ' . $time_format, $current_time)
            );
            if ('listing_email' == $user_email) {
                $to = $listing_email;
            } else {
                $to = $user->user_email;
            }
            $subject = strtr($contact_email_subject, $placeholders);
            $message = strtr($contact_email_body, $placeholders);
            $message = nl2br($message);
            $headers = "From: {$name} <{$site_email}>\r\n";
            $headers .= "Reply-To: {$email}\r\n";
            $message = atbdp_email_html($subject, $message);
            // return true or false, based on the result
            return ATBDP()->email->send_mail($to, $subject, $message, $headers) ? true : false;
        }

        /**
         * Send contact message to the admin.
         *
         * @since    4.0
         */
        function atbdp_email_admin_listing_contact()
        {
            // sanitize form values
            $post_id = (int) $_POST["post_id"];
            $name = sanitize_text_field($_POST["name"]);
            $email = sanitize_email($_POST["email"]);
            $message = esc_textarea($_POST["message"]);
            // vars
            $site_name = get_bloginfo('name');
            $site_url = get_bloginfo('url');
            $listing_title = get_the_title($post_id);
            $listing_url = get_permalink($post_id);
            $listing_url = ATBDP_Permalink::get_listing_permalink($post_id, $listing_url);
            $date_format = get_option('date_format');
            $time_format = get_option('time_format');
            $current_time = current_time('timestamp');
            $placeholders = array(
                '{site_name}' => $site_name,
                '{site_link}' => sprintf('<a href="%s">%s</a>', $site_url, $site_name),
                '{site_url}' => sprintf('<a href="%s">%s</a>', $site_url, $site_url),
                '{listing_title}' => $listing_title,
                '{listing_link}' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_title),
                '{listing_url}' => sprintf('<a href="%s">%s</a>', $listing_url, $listing_url),
                '{sender_name}' => $name,
                '{sender_email}' => $email,
                '{message}' => $message,
                '{today}' => date_i18n($date_format, $current_time),
                '{now}' => date_i18n($date_format . ' ' . $time_format, $current_time)
            );
            $send_emails = ATBDP()->email->get_admin_email_list();
            $to = !empty($send_emails) ? $send_emails : get_bloginfo('admin_email');
            $subject = __('{site_name} Contact via {listing_title}', 'directorist');
            $subject = strtr($subject, $placeholders);
            $message = __("Dear Administrator,<br /><br />A listing on your website {site_name} received a message.<br /><br />Listing URL: {listing_url}<br /><br />Name: {sender_name}<br />Email: {sender_email}<br />Message: {message}<br />Time: {now}<br /><br />This is just a copy of the original email and was already sent to the listing owner. You don't have to reply this unless necessary.", 'directorist');
            $message = strtr($message, $placeholders);
            $headers = "From: {$name} <{$email}>\r\n";
            $headers .= "Reply-To: {$email}\r\n";
            $message = atbdp_email_html($subject, $message);
            return ATBDP()->email->send_mail($to, $subject, $message, $headers) ? true : false;
        }

        /**
         * Send contact email.
         *
         * @since    4.0
         * @access   public
         */
        public function ajax_callback_send_contact_email()
        {
            /**
             * If fires sending processing the submitted contact information
             * @since 4.4.0
             */
            do_action('atbdp_before_processing_contact_to_owner');
            $data = array('error' => 0);
            $sendOwner = in_array('listing_contact_form', get_directorist_option('notify_user', array( 'listing_contact_form' )));
            $sendAdmin = in_array('listing_contact_form', get_directorist_option('notify_admin', array( 'listing_contact_form' )));
            $disable_all_email = get_directorist_option('disable_email_notification');
            $data['sendOwner'] = $sendOwner;
            $data['sendAdmin'] = $sendAdmin;
            $data['disable_all_email'] = $disable_all_email;
            // is admin disabled all the notification
            if ($disable_all_email) {
                $data['error'] = 1;
                $data['message'] = __('Sorry! Something wrong.', 'directorist');
                echo wp_json_encode($data);
                die();
            }
            // is admin disabled both notification
            if (!$sendOwner && !$sendAdmin) {
                $data['error'] = 1;
                $data['message'] = __('Sorry! Something wrong.', 'directorist');
                echo wp_json_encode($data);
                die();
            }
            // let's check is admin decides to send email to it's owner
            if ($sendOwner) {
                $send_to_owner = $this->atbdp_email_listing_owner_listing_contact();
                if (!$send_to_owner) {
                    $data['error'] = 1;
                    $data['message'] = __('Sorry! Please try again.', 'directorist');
                }
            }
            // let's check is admin decides to send email to him/her
            if ($sendAdmin) {
                $send_to_admin = $this->atbdp_email_admin_listing_contact();
                if (!$send_to_admin) {
                    $data['error'] = 1;
                    $data['message'] = __('Sorry! Please try again.', 'directorist');
                }
            }
            // no error found so let's show submitter the success message
            if ($data['error'] === 0) {
                $data['message'] = __('Your message sent successfully.', 'directorist');
            }
            /**
             * @package Directorist
             * @since 6.3.3
             * It fires when a contact is made by visitor with listing owner
             */
            do_action('atbdp_listing_contact_owner_submitted');
            echo wp_json_encode($data);
            die();
        }


        public function atbdp_deactivate_reason_mail()
        {
            global $wpdb;

            if (!isset($_POST['reason_id'])) {
                wp_send_json_error();
            }
            $site_name = get_bloginfo('name');
            $current_user = wp_get_current_user();

            $data = array(
                'reason_id' => sanitize_text_field($_POST['reason_id']),
                'url' => home_url(),
                'user_email' => $current_user->user_email,
                'user_name' => $current_user->display_name,
                'reason_info' => isset($_REQUEST['reason_info']) ? trim(stripslashes($_REQUEST['reason_info'])) : '',
                'software' => $_SERVER['SERVER_SOFTWARE'],
                'php_version' => phpversion(),
                'mysql_version' => $wpdb->db_version(),
                'wp_version' => get_bloginfo('version'),
                'locale' => get_locale(),
                'multisite' => is_multisite() ? 'Yes' : 'No'
            );
            $to = 'support@aazztech.com';
            $subject = 'Deactivate directorist plugin';
            $message = $data['reason_info'];
            $headers = 'From ' . $data['user_email'];
            return ATBDP()->email->send_mail($to, $subject, $message, $headers) ? true : false;
        }

        public function atbdp_deactivate_reason()
        {

            $data = array('error' => 0);

            if ($this->atbdp_deactivate_reason_mail()) {


                $data['message'] = __('Thanks for information', 'directorist');
            }


            echo wp_json_encode($data);
            wp_die();
        }

        public function bdas_dropdown_terms()
        {
            check_ajax_referer('bdas_ajax_nonce', 'security');

            if (isset($_POST['taxonomy']) && isset($_POST['parent'])) {

                $args = array(
                    'taxonomy' => sanitize_text_field($_POST['taxonomy']),
                    'base_term' => 0,
                    'parent' => (int) $_POST['parent']
                );

                if ('at_biz_dir-location' == $args['taxonomy']) {

                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                }

                if ('at_biz_dir-category' == $args['taxonomy']) {

                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                }

                if (isset($_POST['class']) && '' != trim($_POST['class'])) {
                    $args['class'] = sanitize_text_field($_POST['class']);
                }

                if ($args['parent'] != $args['base_term']) {
                    ob_start();
                    bdas_dropdown_terms($args);
                    $output = ob_get_clean();
                    print $output;
                }
            }

            wp_die();
        }

        public function custom_field_search($term_id = 0)
        {
            $ajax = false;
            if (isset($_POST['term_id'])) {

                check_ajax_referer('bdas_ajax_nonce', 'security');

                $ajax = true;
                $term_id = (int) $_POST['term_id'];
            }
            // Get custom fields
            $custom_field_ids = atbdp_get_custom_field_ids($term_id);

            $args = array(
                'post_type'      => ATBDP_CUSTOM_FIELD_POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'post__in'       => $custom_field_ids,
                'meta_query'     => array(
                    array(
                        'key'     => 'searchable',
                        'value'   => 1,
                        'type'    => 'NUMERIC',
                        'compare' => '='
                    ),
                ),
                'orderby' => 'meta_value_num',
                'order'   => 'ASC',
                'fields'  => 'ids',
            );

            $custom_fields = ATBDP_Cache_Helper::get_the_transient([
                'group'      => 'atbdp_custom_field_query',
                'name'       => 'atbdp_custom_fields',
                'query_args' => $args,
                'cache'      => apply_filters( 'atbdp_cache_custom_fields', true ),
                'value'      => function( $data ) {
                    return get_posts( $data['query_args'] );
                }
            ]);

            // Process output
            ob_start();
            require ATBDP_VIEWS_DIR . 'custom-fields.php';
            wp_reset_postdata(); // Restore global post data stomped by the_post()
            $output = ob_get_clean();

            echo $output;

            if ($ajax) {
                wp_die();
            }
        }
    }


endif;