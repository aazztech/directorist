<?php

if ( ! class_exists('ATBDP_Settings_Panel') ) {
	class ATBDP_Settings_Panel
	{
		private $extension_url    = '';
		public $fields            = [];
		public $layouts           = [];
		public $config            = [];
		public $default_form      = [];
		public $old_custom_fields = [];
		public $cetagory_options  = [];

		// run
		public function run()
		{
            add_action( 'admin_menu', [$this, 'add_menu_pages'] );
			add_action( 'wp_ajax_save_settings_data', [ $this, 'handle_save_settings_data_request' ] );

            add_filter( 'atbdp_listing_type_settings_field_list', [ $this, 'register_setting_fields' ] );
			
            $this->extension_url = sprintf("<a target='_blank' href='%s'>%s</a>", esc_url(admin_url('edit.php?post_type=at_biz_dir&page=atbdp-extension')), __('Checkout Awesome Extensions', 'directorist'));
		}

        public static function in_settings_page() {
            if ( ! is_admin() ) {
                return false;
            }

            if ( ! isset( $_REQUEST['post_type'] ) && ! isset( $_REQUEST['page'] ) ) {
                return false;
            }

            if ( 'at_biz_dir' !== $_REQUEST['post_type'] && 'atbdp-settings' !== $_REQUEST['page'] ) {
                return false;
            }

            return true;
        }

        // register_setting_fields
        public function register_setting_fields( $fields = [] ) {
            $fields['script_debugging'] = [
                'type'  => 'toggle',
                'label' => 'Script debugging',
                'description' => __( 'Loads unminified .css, .js files', 'directorist' ),
            ];

            $fields['import_settings'] = [
                'type'         => 'import',
                'label'        => 'Import Settings',
                'button-label' => 'Import',
            ];

            $fields['export_settings'] = [
                'type'             => 'export',
                'label'            => 'Export Settings',
                'button-label'     => 'Export',
                'export-file-name' => 'directory-settings',
            ];

            $fields['restore_default_settings'] = [
                'type'         => 'restore',
                'label'        => 'Restore Default Settings',
                'button-label' => 'Restore',
                'restor-data'  => $this->get_simple_data_content( [ 'path' => 'directory/directory-settings.json' ] ),
            ];

            $fields['enable_multi_directory'] = [
                'type'  => 'toggle',
                'label' => 'Enable Multi Directory',
                'value' => false,
                'confirm-before-change' => true,
                'confirmation-modal' => [
                    'show-model-header' => false
                ],
                'data-on-change' => [
                    'action' => 'updateData',
                    'args'   => [ 'reload_after_save' => true ]
                ],
                'componets' => [
                    'link' => [
                        'label' => __( 'Start Building Directory', 'directorist' ),
                        'type'  => 'success',
                        'url'   => admin_url( 'edit.php?post_type=at_biz_dir&page=atbdp-directory-types' ),
                        'show'  => get_directorist_option( 'enable_multi_directory', false ),
                    ]
                ]
            ];

            $fields['single_listing_slug_with_directory_type'] = [
                'type'  => 'toggle',
                'label' => __('Listing Slug with Directory Type', 'directorist'),
                'value' => get_directorist_option( 'enable_multi_directory' ),
            ];

            $fields['regenerate_pages'] = [
                'type'                       => 'ajax-action',
                'action'                     => 'atbdp_upgrade_old_pages',
                'label'                      => 'Upgrade/Regenerate Pages',
                'button-label'               => 'Regenerate Pages',
                'button-label-on-processing' => '<i class="fas fa-circle-notch fa-spin"></i> Processing',
                'data'                       => [],
            ];

            $fields['sanitize_builder_data'] = [
                'type'                       => 'ajax-action',
                'action'                     => 'directorist_sanitize_builder_data_structure',
                'label'                      => 'Sanitize Builder Data',
                'button-label'               => 'Sanitize Builder Data',
                'button-label-on-processing' => '<i class="fas fa-circle-notch fa-spin"></i> Processing',
                'data'                       => [],
            ];

            $users = get_users([ 'role__not_in' => 'Administrator' ]); // Administrator | Subscriber
            $recipient = [];

            if ( ! empty( $users ) ) {
                foreach ( $users as $user ) {
                    $recipient[] = [
                        'value' => $user->user_email,
                        'label' => ( ! empty( $user->display_name ) ) ? $user->display_name : $user->user_nicename,
                    ];
                }
            }

            $fields['announcement'] = [
                'type'                       => 'ajax-action',
                'action'                     => 'atbdp_send_announcement',
                'label'                      => '',
                'button-label'               => 'Send',
                'button-label-on-processing' => '<i class="fas fa-circle-notch fa-spin"></i> Sending',
                'option-fields' => [
                    'to' => [
                        'type' => 'select',
                        'label' => 'To',
                        'options' => [
                            [ 'value' => 'all_user', 'label' => 'All User' ],
                            [ 'value' => 'selected_user', 'label' => 'Selected User' ],
                        ],
                        'value' => 'all_user',
                    ],
                    'recipient' => [
                        'type'    => 'checkbox',
                        'label'   => 'Recipients',
                        'options' => $recipient,
                        'value'   => '',
                        'show-if' => [
                            'where' => "self.to",
                            'conditions' => [
                                ['key' => 'value', 'compare' => '=', 'value' => 'selected_user'],
                            ],
                        ],
                    ],
                    'subject' => [
                        'type'  => 'text',
                        'label' => 'Subject',
                        'value' => '',
                    ],
                    'message' => [
                        'type'        => 'textarea',
                        'label'       => 'Message',
                        'description' => 'Maximum 400 characters are allowed',
                        'value'       => '',
                    ],
                    'expiration' => [
                        'type'  => 'range',
                        'min'   => '0',
                        'max'   => '365',
                        'label' => 'Expires in Days',
                        'value' => 0,
                    ],
                    'send_to_email' => [
                        'type'  => 'toggle',
                        'label' => 'Send a copy to email',
                        'value' => true,
                    ],
                ],
                'value' => '',
                'save-option-data' => false,
            ];

            $fields['listing_import_button'] = [
                'type'            => 'button',
                'url'             => admin_url( 'edit.php?post_type=at_biz_dir&page=tools' ),
                'open-in-new-tab' => true,
                'label'           => __( 'Import Listings', 'directorist' ),
                'button-label'    => __( 'Run Importer', 'directorist' ),
            ];

            $fields['listing_export_button'] = [
                'type'             => 'export-data',
                'label'            => __( 'Export Listings', 'directorist' ),
                'button-label'     => __( 'Export', 'directorist' ),
                'export-file-name' => __( 'listings-export-data', 'directorist' ),
                'prepare-export-file-from' => 'directorist_prepare_listings_export_file',
            ];

            $c = '<b><span style="color:#c71585;">'; //color start
            $e = '</span></b>'; // end color
            $description = <<<SWBD
                You can use the following keywords/placeholder in any of your email bodies/templates or subjects to output dynamic value. **Usage: place the placeholder name between $c == $e and $c == $e . For Example: use {$c}==SITE_NAME=={$e} to output The Your Website Name etc. <br/><br/>
                {$c}==NAME=={$e} : It outputs The listing owner's display name on the site<br/>
                {$c}==USERNAME=={$e} : It outputs The listing owner's user name on the site<br/>
                {$c}==SITE_NAME=={$e} : It outputs your site name<br/>
                {$c}==SITE_LINK=={$e} : It outputs your site name with link<br/>
                {$c}==SITE_URL=={$e} : It outputs your site url with link<br/>
                {$c}==EXPIRATION_DATE=={$e} : It outputs Expiration date<br/>
                {$c}==CATEGORY_NAME=={$e} : It outputs the category name that is going to expire<br/>
                {$c}==LISTING_ID=={$e} : It outputs the listing's ID<br/>
                {$c}==RENEWAL_LINK=={$e} : It outputs a link to renewal page<br/>
                {$c}==LISTING_TITLE=={$e} : It outputs the listing's title<br/>
                {$c}==LISTING_LINK=={$e} : It outputs the listing's title with link<br/>
                {$c}==LISTING_URL=={$e} : It outputs the listing's url with link<br/>
                {$c}==ORDER_ID=={$e} : It outputs the order id. It should be used for order related email only<br/>
                {$c}==ORDER_RECEIPT_URL=={$e} : It outputs a link to the order receipt page. It should be used for order related email only<br/>
                {$c}==ORDER_DETAILS=={$e} : It outputs order detailsc. It should be used for order related email only<br/>
                {$c}==TODAY=={$e} : It outputs the current date<br/>
                {$c}==NOW=={$e} : It outputs the current time<br/>
                {$c}==DASHBOARD_LINK=={$e} : It outputs the user dashboard page link<br/>
                {$c}==USER_PASSWORD=={$e} : It outputs new user's temporary passoword<br/><br/>
                Additionally, you can also use HTML tags in your template.
SWBD;

            $fields['email_note'] = [
                'type'        => 'note',
                'title'       => 'You can use Placeholders to output dynamic value',
                'description' => $description,
            ];


            // Map Country Restriction Field
            $fields['country_restriction'] = [
                'type'  => 'toggle',
                'label' => __('Country Restriction', 'directorist'),
                'value' => false,
                'show-if' => [
                    'where' => "select_listing_map",
                    'conditions' => [
                        ['key' => 'value', 'compare' => '=', 'value' => 'google'],
                    ],
                ],
            ];

            $countries = atbdp_country_code_to_name();
            $items = array();

            foreach ($countries as $country => $code) {
                $items[] = array(
                    'value' => $country,
                    'label' => $code,
                );
            }

            $fields['restricted_countries'] = [
                'type'    => 'checkbox',
                'label'   => __('Select Countries', 'directorist'),
                'options' => $items,
                'value'   => '',
                'show-if' => [
                    'where' => "country_restriction",
                    'conditions' => [
                        ['key' => 'value', 'compare' => '=', 'value' => true],
                    ],
                ],
            ];


            // Single Listings
            $fields['submission_confirmation'] = [
                'type'  => 'toggle',
                'label' => __('Show Submission Confirmation', 'directorist'),
                'value' => true,
            ];

            $fields['pending_confirmation_msg'] = [
                'type'  => 'textarea',
                'label' => __('Pending Confirmation Message', 'directorist'),
                'value' => __('Thank you for your submission. Your listing is being reviewed and it may take up to 24 hours to complete the review.', 'directorist'),
                'show-if' => [
                    'where' => "submission_confirmation",
                    'conditions' => [
                        ['key' => 'value', 'compare' => '=', 'value' => true],
                    ],
                ],
            ];

            $fields['publish_confirmation_msg'] = [
                'type'  => 'textarea',
                'label' => __('Publish Confirmation Message', 'directorist'),
                'value' => __('Congratulations! Your listing has been approved/published. Now it is publicly available.', 'directorist'),
                'show-if' => [
                    'where' => "submission_confirmation",
                    'conditions' => [
                        ['key' => 'value', 'compare' => '=', 'value' => true],
                    ],
                ],
            ];

            return $fields;
        }

		// get_simple_data_content
		public function get_simple_data_content( array $args = [] ) {
			$default = [ 'path' => '', 'json_decode' => true ];
			$args = array_merge( $default,  $args );

			$path = ( ! empty( $args['path'] ) ) ? $args['path'] : '';

			// $path = 'directory/directory.json'
			$file = trailingslashit( dirname( ATBDP_FILE ) ) . "admin/assets/simple-data/{$path}";
			if ( ! file_exists( $file ) ) { return ''; }

			$data = file_get_contents( $file );

			if ( $args['json_decode'] ) {
				$data = json_decode( $data, true );
			}

			return $data;
		}

		// handle_save_settings_data_request
		public function handle_save_settings_data_request()
		{
			$status = [ 'success' => false, 'status_log' => [] ];
			$field_list = ( ! empty( $_POST['field_list'] ) ) ? $this->maybe_json( $_POST['field_list'] ) : [];

			// If field list is empty
			if ( empty( $field_list ) || ! is_array( $field_list ) ) {
				$status['status_log'] = [
					'type' => 'success',
					'message' => __( 'No changes made', 'directorist' ),
				];

				wp_send_json( [ 'status' => $status, '$field_list' => $field_list ] );
			}

			$options = [];
			foreach ( $field_list as $field_key ) {
				if ( ! isset( $_POST[ $field_key ] ) ) { continue; }

				$options[ $field_key ] = $_POST[ $field_key ];
			}

			$update_settings_options = $this->update_settings_options( $options );

			wp_send_json( $update_settings_options );
		}

		// update_settings_options
		public function update_settings_options( array $options = [] ) {
			$status = [ 'success' => false, 'status_log' => [] ];

			// If field list is empty
			if ( empty( $options ) || ! is_array( $options ) ) {
				$status['status_log'] = [
					'type' => 'success',
					'message' => __( 'Nothing to save', 'directorist' ),
				];

				return [ 'status' => $status ];
			}

			// Update the options
			$atbdp_options = get_option('atbdp_option');
			foreach ( $options as $option_key => $option_value ) {
				$atbdp_options[ $option_key ] = $this->maybe_json( $option_value );
			}

			update_option( 'atbdp_option', $atbdp_options );

			// Send Status
			$status['options'] = $options;
			$status['success'] = true;
			$status['status_log'] = [
				'type' => 'success',
				'message' => __( 'Saving Successful', 'directorist' ),
			];

			return [ 'status' => $status ];
		}

        // maybe_json
		public function maybe_json( $input_data ) {
            if ( 'string' !== gettype( $input_data )  ) { return $input_data; }
        
            $output_data = $input_data;

            // JSON Docode
            $decode_json = json_decode( $input_data, true );

            if ( ! is_null( $decode_json ) ) return $decode_json;
            
            // JSON Decode from Base64
            $decode_base64 = base64_decode( $input_data );
            $decode_base64_json = json_decode( $decode_base64, true );

            if ( ! is_null( $decode_base64_json ) ) return $decode_base64_json;

            return $output_data;
        }

		// maybe_serialize
		public function maybe_serialize($value = '')
		{
			return maybe_serialize($this->maybe_json($value));
		}

		// prepare_settings
		public function prepare_settings()
		{
			$business_hours_label = sprintf(__('Open Now %s', 'directorist'), !class_exists('BD_Business_Hour') ? '(Requires Business Hours extension)' : '');

			$bank_transfer_instruction = "
Please make your payment directly to our bank account and use your ORDER ID (#==ORDER_ID==) as a Reference. Our bank account information is given below.

Account details :

Account Name : [Enter your Account Name]
Account Number : [Enter your Account Number]
Bank Name : [Enter your Bank Name]

Please remember that your order may be canceled if you do not make your payment within next 72 hours.";

        $bank_payment_desc = __('You can make your payment directly to our bank account using this gateway. Please use your ORDER ID as a reference when making the payment. We will complete your order as soon as your deposit is cleared in our bank.', 'directorist');
        $pricing_plan = '<a style="color: red" href="https://directorist.com/product/directorist-pricing-plans" target="_blank">Pricing Plans</a>';

            $this->fields = apply_filters('atbdp_listing_type_settings_field_list', [

                'enable_monetization' => [
                    'label' => __('Enable Monetization Feature', 'directorist'),
                    'type'  => 'toggle',
                    'value' => false,
                    'description' => __('Choose whether you want to monetize your site or not. Monetization features will let you accept payment from your users if they submit listing based on different criteria. Default is NO.', 'directorist'),

                ],

                'enable_featured_listing' => [
                    'label'         => __('Monetize by Featured Listing', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                    'description'   => __('You can enabled this option to collect payment from your user for making their listing featured.', 'directorist'),
                    'show-if' => [
                        'where' => "enable_monetization",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],

                'featured_listing_title' => [
                    'type' => 'text',
                    'label' => __('Title', 'directorist'),
                    'description' => __('You can set the title for featured listing to show on the ORDER PAGE', 'directorist'),
                    'value' => __('Featured', 'directorist'),
                    'show-if' => [
                        'where' => "enable_featured_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],

                'featured_listing_price' => [
                    'label'         => __('Price in ', 'directorist') . atbdp_get_payment_currency(),
                    'type'          => 'number',
                    'value'         => 19.99,
                    'description'   => __('Set the price you want to charge a user if he/she wants to upgrade his/her listing to featured listing. Note: you can change the currency settings under the gateway settings', 'directorist'),
                    'show-if' => [
                        'where' => "enable_featured_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'featured_listing_desc' => [
                    'type' => 'textarea',
                    'label' => __('Description', 'directorist'),
                    'show-if' => [
                        'where' => "enable_featured_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => __('(Top of the search result and listings pages for a number days and it requires an additional payment.)', 'directorist'),
                ],

                'featured_listing_price' => [
                    'label'         => __('Price in ', 'directorist') . atbdp_get_payment_currency(),
                    'type'          => 'number',
                    'max'           => 0,
                    'value'         => 15,
                    'description'   => __('Set the price you want to charge a user if he/she wants to upgrade his/her listing to featured listing. Note: you can change the currency settings under the gateway settings', 'directorist'),
                    'show-if' => [
                        'where' => "enable_featured_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],

                'featured_listing_time' => [
                    'label'         => __('Featured Duration in Days', 'directorist'),
                    'type'          => 'number',
                    'value'         => 30,
                    'show-if' => [
                        'where' => "enable_featured_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],

                'monetization_promotion'    => [
                    'type'          => 'note',
                    'description' => sprintf(__('Monetize your website by selling listing plans using %s extension.', 'directorist'), $pricing_plan),
                ],

                'paypal_gateway_promotion'    => [
                    'type'          => 'note',
                    'title'         => __('Need more gateways?', 'directorist'),
                    'description' => sprintf(__('You can use different payment gateways to process payment including PayPal. %s', 'directorist'), $this->extension_url),
                ],

                'gateway_test_mode' =>[
                    'label'         => __('Enable Test Mode', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => 1,
                    'description'   => __('If you enable Test Mode, then no real transaction will occur. If you want to test the payment system of your website then you can set this option enabled. NOTE: Your payment gateway must support test mode eg. they should provide you a sandbox account to test. Otherwise, use only offline gateway to test.', 'directorist'),
                ],

                'active_gateways' => [
                    'label'     => __('Active Gateways', 'directorist'),
                    'type'      => 'checkbox',
                    'value'     => ['bank_transfer'],
                    'options'   => apply_filters( 'directorist_active_gateways', [
                        [
                            'value' => 'bank_transfer',
                            'label' => __('Bank Transfer (Offline Gateway)', 'directorist'),
                        ],
                    ] ),
                    'description' => __('Check the gateway(s) you would like to use to collect payment from your users. A user will be use any of the active gateways during the checkout process ', 'directorist'),
                ],

                'default_gateway' => [
                    'label'     => __('Default Gateway', 'directorist'),
                    'type'      => 'select',
                    'value'     => 'bank_transfer',
                    'options'   => apply_filters('atbdp_default_gateways', [
                        [
                            'value' => 'bank_transfer',
                            'label' => __('Bank Transfer (Offline Gateway)', 'directorist'),
                        ]
                    ] ),
                    'description' => __('Select the default gateway you would like to show as a selected gateway on the checkout page', 'directorist'),
                ],

                'payment_currency_note'  => [
                    'type'          => 'note',
                    'title'         => __('Note About This Currency Settings:', 'directorist'),
                    'description' => __('This currency settings lets you customize how you would like to accept payment from your user/customer and how to display pricing on the order form/history.', 'directorist'),
                ],

                'payment_currency' => [
                    'type'          => 'text',
                    'label'         => __('Currency Name', 'directorist'),
                    'value'         => __('USD', 'directorist'),
                    'description'   => __( 'Enter the Name of the currency eg. USD or GBP etc.', 'directorist' ),
                ],

                'payment_thousand_separator' => [
                    'type'          => 'text',
                    'label'         => __('Thousand Separator', 'directorist'),
                    'value'         => __(',', 'directorist'),
                    'description'   => __( 'Enter the currency thousand separator. Eg. , or . etc.', 'directorist' ),
                ],

                'payment_decimal_separator' => [
                    'type'          => 'text',
                    'label'         => __('Decimal Separator', 'directorist'),
                    'value'         => __('.', 'directorist'),
                    'description' => __('Enter the currency decimal separator. Eg. "." or ",". Default is "."', 'directorist'),
                ],

                'payment_currency_position' => [
                    'label'     => __('Currency Position', 'directorist'),
                    'type'      => 'select',
                    'value'     => 'before',
                    'options'   => [
                        [
                            'value' => 'before',
                            'label' => __('$5 - Before', 'directorist'),
                        ],
                        [
                            'value' => 'after',
                            'label' => __('After - 5$', 'directorist'),
                        ]
                    ],
                    'description' => __('Select where you would like to show the currency symbol. Default is before. Eg. $5', 'directorist'),
                ],

                // gateway settings
                'offline_payment_note'    => [
                    'type'                => 'note',
                    'title'               => __('Note About Bank Transfer Gateway:', 'directorist'),
                    'description'         => __('You should remember that this payment gateway needs some manual action to complete an order. After getting notification of order using this offline payment gateway, you should check your bank if the money is deposited to your account. Then you should change the order status manually from the "Order History" submenu.', 'directorist'),
                ],

                'bank_transfer_title' => [
                    'type'            => 'text',
                    'label'           => __('Gateway Title', 'directorist'),
                    'value'           => __('Bank Transfer', 'directorist'),
                    'description'     => __('Enter the title of this gateway that should be displayed to the user on the front end.', 'directorist'),
                ],

                'bank_transfer_description' => [
                    'type'          => 'textarea',
                    'label'         => __('Gateway Description', 'directorist'),
                    'value'         => $bank_payment_desc,
                    'description'   => __('Enter some description for your user to transfer funds to your account.', 'directorist'),
                ],

                'bank_transfer_instruction' => [
                    'type'          => 'textarea',
                    'label'         => __('Bank Information', 'directorist'),
                    'value'         => $bank_transfer_instruction,
                    'description'   => __('Enter your bank information below so that use can make payment directly to your bank account.', 'directorist'),
                ],

                //extension setting
                'extension_promotion'    => [
                    'type'          => 'note',
                    'title'         => __('Need more Features?', 'directorist'),
                    'description'   => sprintf(__('You can add new features and expand the functionality of the plugin even more by using extensions. %s', 'directorist'), $this->extension_url),
                ],

                'announcement_to' => [
                    'label'     => __('To', 'directorist'),
                    'type'      => 'select',
                    'value'     => 'all_user',
                    'options'   => [
                        [
                            'value' => 'all_user',
                            'label' => __( 'All User', 'directorist' )
                        ],
                        [
                            'value' => 'selected_user',
                            'label' => __( 'Selected User', 'directorist' )
                        ]
                    ]
                ],

                'announcement_subject' => [
                    'label' => __('Subject', 'directorist'),
                    'type'  => 'text',
                    'value' => false
                ],

                'announcement_send_to_email' => [
                    'label'   => __('Send a copy to email', 'directorist'),
                    'type'    => 'toggle',
                    'default' => true,
                ],

                'button_type' => [
                    'label' => __('Button Type', 'directorist'),
                    'type'  => 'select',
                    'value' => 'select_option',
                    'show-default-option' => true,
                    'options'   => [
                        [
                            'value' => 'solid_primary',
                            'label' => __('Solid Primary', 'directorist'),
                        ],
                        [
                            'value' => 'solid_secondary',
                            'label' => __('Solid Secondary', 'directorist'),
                        ],
                        [
                            'value' => 'solid_danger',
                            'label' => __('Solid Danger', 'directorist'),
                        ],
                        [
                            'value' => 'solid_success',
                            'label' => __('Solid Success', 'directorist'),
                        ],
                        [
                            'value' => 'solid_lighter',
                            'label' => __('Solid Lighter', 'directorist'),
                        ],
                        [
                            'value' => 'primary_outline',
                            'label' => __('Primary Outline', 'directorist'),
                        ],
                        [
                            'value' => 'primary_outline_light',
                            'label' => __('Primary Outline Light', 'directorist'),
                        ],
                        [
                            'value' => 'danger_outline',
                            'label' => __('Danger Outline', 'directorist'),
                        ],
                    ]
                ],

                // solid primary color
                'primary_example' => [
                    'label'       => __('Button Example', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => 'https://directorist.com/wp-content/uploads/2020/02/solid-primary.png',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_primary'],
                        ],
                    ],
                ],
                'primary_color' => [
                    'label' => __('Text Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#ffffff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_primary'],
                        ],
                    ],
                ],
                'primary_hover_color' => [
                    'label' => __('Text Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#ffffff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_primary'],
                        ],
                    ],
                ],
                'back_primary_color' => [
                    'label' => __('Background Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#444752',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_primary'],
                        ],
                    ],
                ],
                'back_primary_hover_color' => [
                    'label' => __('Background Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#222222',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_primary'],
                        ],
                    ],
                ],
                'border_primary_color' => [
                    'label' => __('Border Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#444752',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_primary'],
                        ],
                    ],
                ],
                'border_primary_hover_color' => [
                    'label' => __('Border Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#222222',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_primary'],
                        ],
                    ],
                ],
                //solid secondary color
                'secondary_example' => [
                    'label'       => __('Button Example', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => 'https://directorist.com/wp-content/uploads/2020/02/solid-secondary.png',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_secondary'],
                        ],
                    ],
                ],
                'secondary_color' => [
                    'label' => __('Text Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_secondary'],
                        ],
                    ],
                ],
                'secondary_hover_color' => [
                    'label' => __('Text Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_secondary'],
                        ],
                    ],
                ],
                'back_secondary_color' => [
                    'label' => __('Background Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#122069',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_secondary'],
                        ],
                    ],
                ],
                'back_secondary_hover_color' => [
                    'label' => __('Background Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#131469',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_secondary'],
                        ],
                    ],
                ],
                'secondary_border_color' => [
                    'label' => __('Border Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#131469',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_secondary'],
                        ],
                    ],
                ],
                'secondary_border_hover_color' => [
                    'label' => __('Border Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#131469',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_secondary'],
                        ],
                    ],
                ],
                // solid danger color
                'danger_example' => [
                    'label'       => __('Button Example', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => 'https://directorist.com/wp-content/uploads/2020/02/solid-danger.png',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_danger'],
                        ],
                    ],
                ],
                'danger_color' => [
                    'label' => __('Text Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_danger'],
                        ],
                    ],
                ],
                'danger_hover_color' => [
                    'label' => __('Text Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_danger'],
                        ],
                    ],
                ],
                'back_danger_color' => [
                    'label' => __('Background Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#e23636',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_danger'],
                        ],
                    ],
                ],
                'back_danger_hover_color' => [
                    'label' => __('Background Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#c5001e',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_danger'],
                        ],
                    ],
                ],
                'danger_border_color' => [
                    'label' => __('Border Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#e23636',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_danger'],
                        ],
                    ],
                ],
                'danger_border_hover_color' => [
                    'label' => __('Border Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#c5001e',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_danger'],
                        ],
                    ],
                ],
                // solid success
                'success_example' => [
                    'label'       => __('Button Example', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => 'https://directorist.com/wp-content/uploads/2020/02/solid-success.png',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_success'],
                        ],
                    ],
                ],
                'success_color' => [
                    'label' => __('Text Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_success'],
                        ],
                    ],
                ],
                'success_hover_color' => [
                    'label' => __('Text Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_success'],
                        ],
                    ],
                ],
                'back_success_color' => [
                    'label' => __('Background Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#32cc6f',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_success'],
                        ],
                    ],
                ],
                'back_success_hover_color' => [
                    'label' => __('Background Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#2ba251',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_success'],
                        ],
                    ],
                ],
                'border_success_color' => [
                    'label' => __('Border Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#32cc6f',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_success'],
                        ],
                    ],
                ],
                'border_success_hover_color' => [
                    'label' => __('Border Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#2ba251',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_success'],
                        ],
                    ],
                ],
                // solid lighter
                'lighter_example' => [
                    'label'       => __('Button Example', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => 'https://directorist.com/wp-content/uploads/2021/05/roomrent.png',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_lighter'],
                        ],
                    ],
                ],
                'lighter_color' => [
                    'label' => __('Text Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#1A1B29',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_lighter'],
                        ],
                    ],
                ],
                'lighter_hover_color' => [
                    'label' => __('Text Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#1A1B29',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_lighter'],
                        ],
                    ],
                ],
                'back_lighter_color' => [
                    'label' => __('Background Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#F6F7F9',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_lighter'],
                        ],
                    ],
                ],
                'back_lighter_hover_color' => [
                    'label' => __('Background Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#F6F7F9',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_lighter'],
                        ],
                    ],
                ],
                'border_lighter_color' => [
                    'label' => __('Border Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#F6F7F9',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_lighter'],
                        ],
                    ],
                ],
                'border_lighter_hover_color' => [
                    'label' => __('Border Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#F6F7F9',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'solid_lighter'],
                        ],
                    ],
                ],
                // primary outline
                'priout_example' => [
                    'label'       => __('Button Example Outline', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => 'https://directorist.com/wp-content/uploads/2020/02/outline-primary.png',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline'],
                        ],
                    ],
                ],
                'priout_color' => [
                    'label' => __('Text Color Outline', 'directorist'),
                    'type' => 'color',
                    'value' => '#444752',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline'],
                        ],
                    ],
                ],
                'priout_hover_color' => [
                    'label' => __('Text Hover Color Outline', 'directorist'),
                    'type' => 'color',
                    'value' => '#444752',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline'],
                        ],
                    ],
                ],
                'back_priout_color' => [
                    'label' => __('Background Color Outline', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline'],
                        ],
                    ],
                ],
                'back_priout_hover_color' => [
                    'label' => __('Background Hover Color Outline', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline'],
                        ],
                    ],
                ],
                'border_priout_color' => [
                    'label' => __('Border Color Outline', 'directorist'),
                    'type' => 'color',
                    'value' => '#444752',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline'],
                        ],
                    ],
                ],
                'border_priout_hover_color' => [
                    'label' => __('Border Hover Color Outline', 'directorist'),
                    'type' => 'color',
                    'value' => '#9299b8',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline'],
                        ],
                    ],
                ],
                 // primary outline light
                 'prioutlight_example' => [
                    'label'       => __('Button Example Outline Light', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => 'https://directorist.com/wp-content/uploads/2020/02/outline-primary-light.png',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline_light'],
                        ],
                    ],
                ],
                'prioutlight_color' => [
                    'label' => __('Text Color Outline Light', 'directorist'),
                    'type' => 'color',
                    'value' => '#444752',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline_light'],
                        ],
                    ],
                ],
                'prioutlight_hover_color' => [
                    'label' => __('Text Hover Color Outline Light', 'directorist'),
                    'type' => 'color',
                    'value' => '#ffffff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline_light'],
                        ],
                    ],
                ],
                'back_prioutlight_color' => [
                    'label' => __('Background Color Outline Light', 'directorist'),
                    'type' => 'color',
                    'value' => '#ffffff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline_light'],
                        ],
                    ],
                ],
                'back_prioutlight_hover_color' => [
                    'label' => __('Background Hover Color Outline Light', 'directorist'),
                    'type' => 'color',
                    'value' => '#444752',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline_light'],
                        ],
                    ],
                ],
                'border_prioutlight_color' => [
                    'label' => __('Border Color Outline Light', 'directorist'),
                    'type' => 'color',
                    'value' => '#e3e6ef',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline_light'],
                        ],
                    ],
                ],
                'border_prioutlight_hover_color' => [
                    'label' => __('Border Hover Color Outline Light', 'directorist'),
                    'type' => 'color',
                    'value' => '#444752',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'primary_outline_light'],
                        ],
                    ],
                ],
                // Danger outline
                'danout_example' => [
                    'label'       => __('Button Example', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => 'https://directorist.com/wp-content/uploads/2020/02/outline-danger.png',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'danger_outline'],
                        ],
                    ],
                ],
                'danout_color' => [
                    'label' => __('Text Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#e23636',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'danger_outline'],
                        ],
                    ],
                ],
                'danout_hover_color' => [
                    'label' => __('Text Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'danger_outline'],
                        ],
                    ],
                ],
                'back_danout_color' => [
                    'label' => __('Background Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#fff',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'danger_outline'],
                        ],
                    ],
                ],
                'back_danout_hover_color' => [
                    'label' => __('Background Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#e23636',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'danger_outline'],
                        ],
                    ],
                ],
                'border_danout_color' => [
                    'label' => __('Border Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#e23636',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'danger_outline'],
                        ],
                    ],
                ],
                'border_danout_hover_color' => [
                    'label' => __('Border Hover Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#e23636',
                    'show-if' => [
                        'where' => "button_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'danger_outline'],
                        ],
                    ],
                ],




                'open_back_color' => [
                    'label' => __('Open Background Color', 'directorist'),
                    'type' => 'color',
                    'value' => '#32cc6f',
                ],

                'closed_back_color' => [
                    'type' => 'color',
                    'label' => __('Closed Background Color', 'directorist'),
                    'value' => '#e23636',
                ],

                'featured_back_color' => [
                    'type' => 'color',
                    'label' => __('Featured Background Color', 'directorist'),
                    'value' => '#fa8b0c',
                ],

                'popular_back_color' => [
                    'type' => 'color',
                    'label' => __('Popular Background Color', 'directorist'),
                    'value' => '#f51957',
                ],

                'new_back_color' => [
                    'type' => 'color',
                    'label' => __('New Background Color', 'directorist'),
                    'value' => '#122069',
                ],

                'marker_shape_color' => [
                    'type' => 'color',
                    'label' => __('Marker Shape Color', 'directorist'),
                    'value' => '#444752',
                ],

                'marker_icon_color' => [
                    'type' => 'color',
                    'label' => __('Marker Icon Color', 'directorist'),
                    'value' => '#444752',
                ],

                'primary_dark_back_color' => [
                    'type' => 'color',
                    'label' => __('Background Color', 'directorist'),
                    'default' => '#444752',
                ],

                'primary_dark_border_color' => [
                    'type' => 'color',
                    'label' => __('Border Color', 'directorist'),
                    'default' => '#444752',
                ],

                'font_type' => [
                    'label' => __('Icon Library', 'directorist'),
                    'type'  => 'select',
                    'value' => 'line',
                    'options' => [
                        [
                            'label' => __('Font Awesome', 'directorist'),
                            'value' => 'font',
                        ],
                        [
                            'label' => __('Line Awesome', 'directorist'),
                            'value' => 'line',
                        ],
                    ],
                ],
                'can_renew_listing' => [
                    'label' => __('Can User Renew Listing?', 'directorist'),
                    'type'  => 'toggle',
                    'value' => true,
                    'description' => __('Here YES means users can renew their listings.', 'directorist'),
                ],
                'email_to_expire_day' => [
                    'label' => __('When to send expire notice', 'directorist'),
                    'type'  => 'number',
                    'description' => __('Select the days before a listing expires to send an expiration reminder email', 'directorist'),
                    'value' => 7,
                    'placeholder' => '10',
                    'rules' => [
                        'required' => true,
                    ],
                ],
                'email_renewal_day' => [
                    'label' => __('When to send renewal reminder', 'directorist'),
                    'type'  => 'number',
                    'description' => __('Select the days after a listing expires to send a renewal reminder email', 'directorist'),
                    'value' => 7,
                    'placeholder' => '10',
                    'rules' => [
                        'required' => true,
                    ],
                ],
                'delete_expired_listing' => [
                    'label' => __('Delete/Trash Expired Listings', 'directorist'),
                    'type'  => 'toggle',
                    'value' => true,
                ],
                'delete_expired_listings_after' => [
                    'label' => __('Delete/Trash Expired Listings After (days) of Expiration', 'directorist'),
                    'type'  => 'number',
                    'description' => __('Select the days before a listing expires to send an expiration reminder email', 'directorist'),
                    'value' => 15,
                    'placeholder' => '15',
                    'rules' => [
                        'required' => true,
                    ],
                    'show-if' => [
                        'where' => "delete_expired_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'deletion_mode' => [
                    'label' => __('Delete or Trash Expired Listings', 'directorist'),
                    'type'  => 'select',
                    'description' => __('Choose the Default actions after a listing reaches its deletion threshold.', 'directorist'),
                    'value' => 'trash',
                    'options' => [
                        [
                            'value' => 'force_delete',
                            'label' => __('Delete Permanently', 'directorist'),
                        ],
                        [
                            'value' => 'trash',
                            'label' => __('Move to Trash', 'directorist'),
                        ],
                    ],
                    'show-if' => [
                        'where' => "delete_expired_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'paginate_author_listings' => [
                    'label' => __('Paginate Author Listings', 'directorist'),
                    'type'  => 'toggle',
                    'value' => true,
                ],
                'display_author_email' => [
                    'label' => __('Author Email', 'directorist'),
                    'type'  => 'select',
                    'value' => 'public',
                    'options' => [
                        [
                            'value' => 'public',
                            'label' => __('Display', 'directorist'),
                        ],
                        [
                            'value' => 'logged_in',
                            'label' => __('Display only for Logged in Users', 'directorist'),
                        ],

                        [
                            'value' => 'none_to_display',
                            'label' => __('Hide', 'directorist'),
                        ],
                    ],
                ],
                'author_cat_filter' => [
                    'label' => __('Show Category Filter on Author Page', 'directorist'),
                    'type'  => 'toggle',
                    'value' => true,
                ],
                'atbdp_enable_cache' => [
                    'label' => __('Enable Cache', 'directorist'),
                    'type'  => 'toggle',
                    'value' => true,
                ],
                'atbdp_reset_cache' => [
                    'label' => __('Reset Cache', 'directorist'),
                    'type'  => 'toggle',
                    'show-if' => [
                        'where' => "atbdp_enable_cache",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                    'value' => false,
                ],
                'guest_listings' => [
                    'label' => __('Guest Listing Submission', 'directorist'),
                    'type'  => 'toggle',
                    'value' => false,
                ],

                // listings page
                'display_listings_header' => [
                    'label' => __('Display Header', 'directorist'),
                    'type'  => 'toggle',
                    'value' => true,
                ],
                'all_listing_title' => [
                    'type' => 'text',
                    'label'   => __('Header Title', 'directorist'),
                    'value'   => __('Items Found', 'directorist'),
                    'show-if' => [
                        'where' => "display_listings_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'listing_filters_button' => [
                    'type' => 'toggle',
                    'label' => __('Display Filters Button', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "display_listings_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'listing_filters_icon' => [
                    'type' => 'toggle',
                    'label' => __('Display Filters Icon', 'directorist'),
                    'value' => true,
                ],
                'listings_filter_button_text' => [
                    'type' => 'text',
                    'label' => __('Filters Button Text', 'directorist'),
                    'value' => __('Filters', 'directorist'),
                ],
                'listing_tags_field' => [
                    'label' => __('Tags Filter Source', 'directorist'),
                    'type'  => 'select',
                    'value' => 'all_tags',
                    'options' => [
                        [
                            'value' => 'category_based_tags',
                            'label' => __('Category Based Tags', 'directorist'),
                        ],
                        [
                            'value' => 'all_tags',
                            'label' => __('All Tags', 'directorist'),
                        ],
                    ],
                ],
                'listing_default_radius_distance' => [
                    'label' => __('Default Radius Distance', 'directorist'),
                    'type'  => 'number',
                    'value' => 0,
                    'placeholder' => '10',
                ],
                'listings_filters_button' => [
                    'label' => __('Filter Buttons', 'directorist'),
                    'type'  => 'checkbox',
                    'value' => [
                            'reset_button',
                            'apply_button',
                        ],
                    'options' => [
                        [
                            'value' => 'reset_button',
                            'label' => __('Reset', 'directorist'),
                        ],
                        [
                            'value' => 'apply_button',
                            'label' => __('Apply', 'directorist'),
                        ],
                    ],
                ],
                'listings_reset_text' => [
                    'type' => 'text',
                    'label' => __('Reset Filters Button text', 'directorist'),
                    'value' => __('Reset Filters', 'directorist'),
                ],
                'listings_apply_text' => [
                    'type' => 'text',
                    'label' => __('Apply Filters Button text', 'directorist'),
                    'value' => __('Apply Filters', 'directorist'),
                ],


                'display_sort_by' => [
                    'type' => 'toggle',
                    'label' => __('Display "Sort By" Dropdown', 'directorist'),
                    'value' => true,
                ],
                'sort_by_text'    => [
                    'type'          => 'text',
                    'label'         => __('"Sort By" Text', 'directorist'),
                    'value'         => __('Sort By', 'directorist'),
                    'show-if' => [
                        'where' => "display_sort_by",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'listings_sort_by_items' => [
                    'label' => __('"Sort By" Dropdown', 'directorist'),
                    'type'  => 'checkbox',
                    'value' => [
                        'a_z',
                        'z_a',
                        'latest',
                        'oldest',
                        'popular',
                        'price_low_high',
                        'price_high_low',
                        'random'
                        ],
                    'options' => [
                        [
                            'value' => 'a_z',
                            'label' => __('A to Z (title)', 'directorist'),
                        ],
                        [
                            'value' => 'z_a',
                            'label' => __('Z to A (title)', 'directorist'),
                        ],
                        [
                            'value' => 'latest',
                            'label' => __('Latest listings', 'directorist'),
                        ],
                        [
                            'value' => 'oldest',
                            'label' => __('Oldest listings', 'directorist'),
                        ],
                        [
                            'value' => 'popular',
                            'label' => __('Popular listings', 'directorist'),
                        ],
                        [
                            'value' => 'price_low_high',
                            'label' => __('Price (low to high)', 'directorist'),
                        ],
                        [
                            'value' => 'price_high_low',
                            'label' => __('Price (high to low)', 'directorist'),
                        ],
                        [
                            'value' => 'random',
                            'label' => __('Random listings', 'directorist'),
                        ],
                    ],
                ],
                'display_view_as' => [
                    'type' => 'toggle',
                    'label' => __('Display "View As" Dropdown', 'directorist'),
                    'value' => true,
                ],
                'view_as_text' => [
                    'type' => 'text',
                    'label' => __('"View As" Text', 'directorist'),
                    'show-if' => [
                        'where' => "display_view_as",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => __('View As', 'directorist'),
                ],
                'listings_view_as_items' => [
                    'label' => __('"View As" Dropdown', 'directorist'),
                    'type'  => 'checkbox',
                    'show-if' => [
                        'where' => "display_view_as",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => [
                        'listings_grid',
                        'listings_list',
                        'listings_map'
                        ],
                    'options' => [
                        [
                            'value' => 'listings_grid',
                            'label' => __('Grid', 'directorist'),
                        ],
                        [
                            'value' => 'listings_list',
                            'label' => __('List', 'directorist'),
                        ],
                        [
                            'value' => 'listings_map',
                            'label' => __('Map', 'directorist'),
                        ],
                    ],
                ],
                'default_listing_view' => [
                    'label' => __('Default View', 'directorist'),
                    'type'  => 'select',
                    'value' => 'grid',
                    'options' => [
                        [
                            'value' => 'grid',
                            'label' => __('Grid', 'directorist'),
                        ],
                        [
                            'value' => 'list',
                            'label' => __('List', 'directorist'),
                        ],
                        [
                            'value' => 'map',
                            'label' => __('Map', 'directorist'),
                        ],
                    ],
                ],
                'grid_view_as' => [
                    'label' => __('Grid View', 'directorist'),
                    'type'  => 'select',
                    'value' => 'normal_grid',
                    'options' => [
                        [
                            'value' => 'masonry_grid',
                            'label' => __('Masonry', 'directorist'),
                        ],
                        [
                            'value' => 'normal_grid',
                            'label' => __('Normal', 'directorist'),
                        ],
                    ],
                ],
                'all_listing_columns' => [
                    'label' => __('Number of Columns', 'directorist'),
                    'type'  => 'number',
                    'value' => 3,
                    'placeholder' => '3',
                ],
                'order_listing_by' => [
                    'label' => __('Listings Order By', 'directorist'),
                    'type'  => 'select',
                    'value' => 'date',
                    'options' => [
                       [
                            'value' => 'title',
                            'label' => __('Title', 'directorist'),
                       ],
                       [
                            'value' => 'date',
                            'label' => __('Date', 'directorist'),
                       ],
                       [
                            'value' => 'price',
                            'label' => __('Price', 'directorist'),
                       ],
                       [
                            'value' => 'rand',
                            'label' => __('Random', 'directorist'),
                       ],
                    ],
                ],
                'sort_listing_by' => [
                    'label' => __('Listings Sort By', 'directorist'),
                    'type'  => 'select',
                    'value' => 'desc',
                    'options' => [
                        [
                            'value' => 'asc',
                            'label' => __('Ascending', 'directorist'),
                        ],
                        [
                            'value' => 'desc',
                            'label' => __('Descending', 'directorist'),
                        ],
                    ],
                ],
                'preview_image_quality' => [
                    'label' => __('Preview Image Quality', 'directorist'),
                    'type'  => 'select',
                    'value' => 'large',
                    'options' => [
                        [
                            'value' => 'medium',
                            'label' => __('Medium', 'directorist'),
                        ],
                        [
                            'value' => 'large',
                            'label' => __('Large', 'directorist'),
                        ],
                        [
                            'value' => 'full',
                            'label' => __('Full', 'directorist'),
                        ],
                    ],
                ],
                'way_to_show_preview' => [
                    'label' => __('Image Size', 'directorist'),
                    'type'  => 'select',
                    'value' => 'cover',

                    'options' => [
                        [
                            'value' => 'full',
                            'label' => __('Original', 'directorist'),
                        ],
                        [
                            'value' => 'cover',
                            'label' => __('Fill with Container', 'directorist'),
                        ],
                        [
                            'value' => 'contain',
                            'label' => __('Fit with Container', 'directorist'),
                        ],
                    ],
                ],
                'crop_width' => [
                    'label' => __('Container Width', 'directorist'),
                    'type'  => 'number',
                    'value' => '350',
                    'min' => '1',
                    'max' => '1200',
                    'step' => '1',

                ],
                'crop_height' => [
                    'label' => __('Container Height', 'directorist'),
                    'type'  => 'number',
                    'value' => '260',
                    'min' => '1',
                    'max' => '1200',
                    'step' => '1',

                ],
                'prv_container_size_by' => [
                    'label' => __('Container Size By', 'directorist'),
                    'type'  => 'select',
                    'value' => 'px',

                    'options' => [
                        [
                            'value' => 'px',
                            'label' => __('Pixel', 'directorist'),
                        ],
                        [
                            'value' => 'ratio',
                            'label' => __('Ratio', 'directorist'),
                        ],
                    ],
                ],
                'prv_background_type' => [
                    'label' => __('Background', 'directorist'),
                    'type'  => 'select',
                    'value' => 'blur',

                    'options' => [
                        [
                            'value' => 'blur',
                            'label' => __('Blur', 'directorist'),
                        ],
                        [
                            'value' => 'color',
                            'label' => __('Custom Color', 'directorist'),
                        ],
                    ],
                ],
                'prv_background_color' => [
                    'type' => 'text',
                    'label' => __('Select Color', 'directorist'),
                    'show-if' => [
                        'where' => "prv_background_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'color'],
                        ],
                    ],
                    'value' => 'gainsboro',
                ],
                'default_preview_image' => [
                    'label'       => __('Select Listing Preview Image', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => DIRECTORIST_ASSETS . 'images/grid.jpg',
                    'value'       => '',
                ],
                'info_display_in_single_line' => [
                    'type' => 'toggle',
                    'label' => __('Display Each Grid Info on Single Line', 'directorist'),
                    'description' => __('Here Yes means display all the informations (i.e. title, tagline, excerpt etc.) of grid view on single line', 'directorist'),
                    'value' => false,
                ],

                'address_location' => [
                    'label' => __('Address', 'directorist'),
                    'type'  => 'select',
                    'value' => 'contact',
                    'description' => __('Choose which address you want to show on listings page', 'directorist'),
                    'options' => [
                        [
                            'value' => 'location',
                            'label' => __('Display From Location', 'directorist'),
                        ],
                        [
                            'value' => 'contact',
                            'label' => __('Display From Contact Information', 'directorist'),
                        ],
                    ],
                ],

                'publish_date_format' => [
                    'label' => __('Publish Date Format', 'directorist'),
                    'type'  => 'select',
                    'value' => 'time_ago',
                    'options' => [
                        [
                            'value' => 'time_ago',
                            'label' => __('Number of Days Ago', 'directorist'),
                        ],
                        [
                            'value' => 'publish_date',
                            'label' => __('Standard Date Format', 'directorist'),
                        ],
                    ],
                ],

                'paginate_all_listings' => [
                    'type' => 'toggle',
                    'label' => __('Paginate Listings'),
                    'value' => true,
                ],
                'all_listing_page_items' => [
                    'label' => __('Listings Per Page', 'directorist'),
                    'type'  => 'number',
                    'value' => '6',
                    'min' => '1',
                    'max' => '100',
                    'step' => '1',
                ],
                // single listing settings
                'disable_single_listing' => [
                    'type' => 'toggle',
                    'label' => __('Disable Single Listing View'),
                    'value' => false,
                ],
                'restrict_single_listing_for_logged_in_user' => [
                    'type' => 'toggle',
                    'label' => __('Restrict Single Listings for Logged in User Only', 'directorist'),
                    'value' => false,
                ],
                'single_listing_template' => [
                    'label' => __('Template', 'directorist'),
                    'type'  => 'select',
                    'value' => 'directorist_template',
                    'show-if' => [
                        'where' => "disable_single_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                    'options' => [
                        [
                            'value' => 'theme_template_page',
                            'label' => __('Theme Template (Page)', 'directorist'),
                        ],
                        [
                            'value' => 'current_theme_template',
                            'label' => __('Theme Template (Post)', 'directorist'),
                        ],
                        [
                            'value' => 'directorist_template',
                            'label' => __('Directorist Template', 'directorist'),
                        ],
                    ],
                ],
                'atbdp_listing_slug' => [
                    'type' => 'text',
                    'label' => __('Listing Slug', 'directorist'),
                    'show-if' => [
                        'where' => "disable_single_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                    'value' => 'directory',
                ],
                'edit_listing_redirect' => [
                    'label' => __('Redirect after Editing a Listing', 'directorist'),
                    'type'  => 'select',
                    'value' => 'view_listing',
                    'description' => __('Select where user will be redirected after editing a listing on the frontend.', 'directorist'),
                    'show-if' => [
                        'where' => "disable_single_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                    'options' => [
                        [
                            'value' => 'view_listing',
                            'label' => __('Frontend of the Listing', 'directorist'),
                        ],
                        [
                            'value' => 'dashboard',
                            'label' => __('User Dashboard', 'directorist'),
                        ],
                    ],
                ],

                'dsiplay_slider_single_page' => [
                    'type' => 'toggle',
                    'label' => __('Show Slider Image', 'directorist'),
                    'value' => true,
                    'description' => __('Hide/show slider image from single listing page.', 'directorist'),
                    'show-if' => [
                        'where' => "disable_single_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                ],
                'single_slider_image_size' => [
                    'label' => __('Slider Image Size', 'directorist'),
                    'type'  => 'select',
                    'value' => 'cover',
                    'show-if' => [
                        'where' => "dsiplay_slider_single_page",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'options' => [
                        [
                            'value' => 'cover',
                            'label' => __('Fill with Container', 'directorist'),
                        ],
                        [
                            'value' => 'contain',
                            'label' => __('Fit with Container', 'directorist'),
                        ],
                    ],
                ],
                'single_slider_background_type' => [
                    'label' => __('Slider Background Type', 'directorist'),
                    'type'  => 'select',
                    'value' => 'custom-color',
                    'show-if' => [
                        'where' => "single_slider_image_size",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'contain'],
                        ],
                    ],
                    'options' => [
                        [
                            'value' => 'blur',
                            'label' => __('Blur', 'directorist'),
                        ],
                        [
                            'value' => 'custom-color',
                            'label' => __('Custom Color', 'directorist'),
                        ],
                    ],
                ],
                'single_slider_background_color' => [
                    'type' => 'color',
                    'label' => __('Slider Background Color', 'directorist'),
                    'show-if' => [
                        'where' => "single_slider_background_type",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'custom-color'],
                        ],
                    ],
                    'value' => '#ffffff',
                ],

                'gallery_crop_width' => [
                    'label' => __('Image Width', 'directorist'),
                    'type'  => 'number',
                    'value' => '740',
                    'min' => '1',
                    'max' => '1200',
                    'step' => '1',
                    'show-if' => [
                        'where' => "dsiplay_slider_single_page",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'gallery_crop_height' => [
                    'label' => __('Image Height', 'directorist'),
                    'type'  => 'number',
                    'value' => '580',
                    'min' => '1',
                    'max' => '1200',
                    'step' => '1',
                    'show-if' => [
                        'where' => "dsiplay_slider_single_page",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],

                'address_map_link' => [
                    'type' => 'toggle',
                    'label' => __('Address Linked with Map', 'directorist'),
                    'value' => false,
                    'show-if' => [
                        'where' => "disable_single_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                ],
                'user_email' => [
                    'label' => __('Email Send to', 'directorist'),
                    'type'  => 'select',
                    'value' => 'author',
                    'description' => __('Email recipient for receiving email from Contact Listing Owner Form.', 'directorist'),
                    'options' => [
                        [
                            'value' => 'author',
                            'label' => __('Author Email', 'directorist'),
                        ],
                        [
                            'value' => 'listing_email',
                            'label' => __('Listing\'s Email', 'directorist'),
                        ],
                    ],
                ],
                'rel_listings_logic' => [
                    'label' => __('Related Listings Based on', 'directorist'),
                    'type'  => 'select',
                    'value' => 'OR',
                    'description' => __('Display related listings based on category and/or tag.', 'directorist'),
                    'options' => [
                        [
                            'value' => 'OR',
                            'label' => __('Category or Tag', 'directorist'),
                        ],
                        [
                            'value' => 'AND',
                            'label' => __('Category and Tag', 'directorist'),
                        ],
                    ],
                ],

                'fix_listing_double_thumb' => [
                    'type' => 'toggle',
                    'label' => __('Fix Repeated Thumbnail of Single Listing', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "disable_single_listing",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                ],
                // badge settings
                'new_badge_text' => [
                    'type' => 'text',
                    'label' => __('New Badge Text', 'directorist'),
                    'value' => __('New', 'directorist'),
                ],
                'new_listing_day' => [
                    'label' => __('New Badge Duration in Days', 'directorist'),
                    'type'  => 'number',
                    'value' => '3',
                    'min' => '1',
                    'max' => '100',
                    'step' => '1',
                ],
                'feature_badge_text' => [
                    'type' => 'text',
                    'label' => __('Featured Badge Text', 'directorist'),
                    'value' => __('Featured', 'directorist'),
                ],
                'popular_badge_text' => [
                    'type' => 'text',
                    'label' => __('Popular Badge Text', 'directorist'),
                    'value' => __('Popular', 'directorist'),
                ],
                'listing_popular_by' => [
                    'label' => __('Popular Based on', 'directorist'),
                    'type'  => 'select',
                    'value' => 'view_count',
                    'options' => [
                        [
                            'value' => 'view_count',
                            'label' => __('View Count', 'directorist'),
                        ],
                        [
                            'value' => 'average_rating',
                            'label' => __('Average Rating', 'directorist'),
                        ],
                        [
                            'value' => 'both_view_rating',
                            'label' => __('Both', 'directorist'),
                        ]
                    ],
                ],
                'views_for_popular' => [
                    'type' => 'text',
                    'label' => __('Threshold in Views Count', 'directorist'),
                    'value' => 5,
                ],
                'count_loggedin_user' => [
                    'type' => 'toggle',
                    'label' => __('Count Logged-in User View', 'directorist'),
                    'value' => false,
                ],
                'average_review_for_popular' => [
                    'label' => __('Threshold in Average Ratings (equal or grater than)', 'directorist'),
                    'type'  => 'number',
                    'value' => '4',
                    'min' => '.5',
                    'max' => '4.5',
                    'step' => '.5',
                ],

                // review settings
                'enable_review' => [
                    'type' => 'toggle',
                    'label' => __('Enable Reviews & Rating', 'directorist'),
                    'value' => true,
                ],
                'enable_owner_review' => [
                    'type' => 'toggle',
                    'label' => __('Enable Owner Review', 'directorist'),
                    'description' => __('Allow a listing owner to post a review on his/her own listing.', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "enable_review",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'approve_immediately' => [
                    'type' => 'toggle',
                    'label' => __('Approve Immediately?', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "enable_review",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'review_approval_text' => [
                    'type' => 'textarea',
                    'label' => __('Approval Notification Text', 'directorist'),
                    'description' => __('You can set the title for featured listing to show on the ORDER PAGE', 'directorist'),
                    'value' => __('We have received your review. It requires approval.', 'directorist'),
                    'show-if' => [
                        'where' => "enable_review",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'enable_reviewer_img' => [
                    'type' => 'toggle',
                    'label' => __('Enable Reviewer Image', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "enable_review",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'enable_reviewer_content' => [
                    'type' => 'toggle',
                    'label' => __('Enable Reviewer content', 'directorist'),
                    'value' => true,
                    'description' => __('Allow to display content of reviewer on single listing page.', 'directorist'),
                    'show-if' => [
                        'where' => "enable_review",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'required_reviewer_content' => [
                    'type' => 'toggle',
                    'label' => __('Required Reviewer content', 'directorist'),
                    'value' => true,
                    'description' => __('Allow to Require the content of reviewer on single listing page.', 'directorist'),
                    'show-if' => [
                        'where' => "enable_review",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'review_num' => [
                    'label' => __('Number of Reviews', 'directorist'),
                    'description' => __('Enter how many reviews to show on Single listing page.', 'directorist'),
                    'type'  => 'number',
                    'value' => '5',
                    'min' => '1',
                    'max' => '20',
                    'step' => '1',
                    'show-if' => [
                        'where' => "enable_review",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'guest_review' => [
                    'type' => 'toggle',
                    'label' => __('Guest Review Submission', 'directorist'),
                    'value' => false,
                    'show-if' => [
                        'where' => "enable_review",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                // select map settings
                'select_listing_map' => [
                    'label' => __('Select Map', 'directorist'),
                    'type'  => 'select',
                    'value' => 'openstreet',
                    'options' => [
                        [
                            'value' => 'google',
                            'label' => __('Google Map', 'directorist'),
                        ],
                        [
                            'value' => 'openstreet',
                            'label' => __('OpenStreetMap', 'directorist'),
                        ],
                    ],
                ],
                'map_api_key' => [
                    'type' => 'text',
                    'label' => __('Google Map API key', 'directorist'),
                    'description' => sprintf(__('Please replace it by your own API. It\'s required to use Google Map. You can find detailed information %s.', 'directorist'), '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank"> <div class="atbdp_shortcodes" style="color: red;">here</div> </a>'),
                    'value' => '',
                    'show-if' => [
                        'where' => "select_listing_map",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'google'],
                        ],
                    ],
                ],
                'default_latitude'     => [
                    'type'           => 'text',
                    'label'          => __('Default Latitude', 'directorist'),
                    'description'    => sprintf(__('You can find it %s.', 'directorist'), '<a href="https://www.maps.ie/coordinates.html" target="_blank"> <div class="atbdp_shortcodes" style="color: red;">here</div> </a>'),
                    'value'          => '40.7127753',
                ],
                'default_longitude'    => [
                    'type'          => 'text',
                    'label'         => __('Default Longitude', 'directorist'),
                    'description'   => sprintf(__('You can find it %s.', 'directorist'), '<a href="https://www.maps.ie/coordinates.html" target="_blank"> <div class="atbdp_shortcodes" style="color: red;">here</div> </a>'),
                    'value'         => '-74.0059728',
                ],
                'map_zoom_level'       => [
                    'label'         => __('Zoom Level for Single Listing', 'directorist'),
                    'description'   => __('Here 0 means 100% zoom-out. 22 means 100% zoom-in. Minimum Zoom Allowed = 1. Max Zoom Allowed = 22.', 'directorist'),
                    'type'          => 'number',
                    'value'         => '16',
                    'min'           => '1',
                    'max'           => '22',
                    'step'          => '1',
                ],
                'map_view_zoom_level' => [
                    'label'         => __('Zoom Level for Map View', 'directorist'),
                    'description'   => __('Here 0 means 100% zoom-out. 18 means 100% zoom-in. Minimum Zoom Allowed = 1. Max Zoom Allowed = 22.', 'directorist'),
                    'type'          => 'number',
                    'value'         => '1',
                    'min'           => '1',
                    'max'           => '18',
                    'step'          => '1',
                ],
                'listings_map_height' => [
                    'label'         => __('Map Height', 'directorist'),
                    'description'   => __('In pixel.', 'directorist'),
                    'type'          => 'number',
                    'value'         => '350',
                    'min'           => '5',
                    'max'           => '1200',
                    'step'          => '5',
                ],
                'display_map_info' => [
                    'type' => 'toggle',
                    'label' => __('Display Map Info Window', 'directorist'),
                    'value' => true,
                ],
                'display_image_map' => [
                    'type' => 'toggle',
                    'label' => __('Display Preview Image', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "display_map_info",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'display_title_map' => [
                    'type' => 'toggle',
                    'label' => __('Display Title', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "display_map_info",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'display_address_map' => [
                    'type' => 'toggle',
                    'label' => __('Display Address', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "display_map_info",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'display_direction_map' => [
                    'type' => 'toggle',
                    'label' => __('Display Get Direction', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "display_map_info",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                // user dashboard settings
                'my_listing_tab' => [
                    'type' => 'toggle',
                    'label' => __('Display My Listing Tab', 'directorist'),
                    'value' => true,
                ],
                'my_listing_tab_text'    => [
                    'type'          => 'text',
                    'label'         => __('"My Listing" Tab Label', 'directorist'),
                    'value'         => __('My Listing', 'directorist'),
                    'show-if' => [
                        'where' => "my_listing_tab",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'user_listings_pagination' => [
                    'type'  => 'toggle',
                    'label' => __('Listings Pagination', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "my_listing_tab",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'user_listings_per_page' => [
                    'label'         => __('Listings Per Page', 'directorist'),
                    'type'          => 'number',
                    'value'         => '9',
                    'min'           => '1',
                    'max'           => '30',
                    'step'          => '1',
                    'show-if' => [
                        'where' => "my_listing_tab",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'my_profile_tab' => [
                    'type'  => 'toggle',
                    'label' => __('Display My Profile Tab', 'directorist'),
                    'value' => true,
                ],
                'my_profile_tab_text'    => [
                    'type'          => 'text',
                    'label'         => __('"My Profile" Tab Label', 'directorist'),
                    'value'         => __('My Profile', 'directorist'),
                    'show-if' => [
                        'where' => "my_profile_tab",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'fav_listings_tab' => [
                    'type'  => 'toggle',
                    'label' => __('Display Favourite Listings Tab', 'directorist'),
                    'value' => true,
                ],
                'fav_listings_tab_text'    => [
                    'type'          => 'text',
                    'label'         => __('"Favourite Listings" Tab Label', 'directorist'),
                    'value'         => __('Favorite Listings', 'directorist'),
                    'show-if' => [
                        'where' => "fav_listings_tab",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'announcement_tab' => [
                    'type'  => 'toggle',
                    'label' => __('Display Announcements Tab', 'directorist'),
                    'value' => true,
                ],
                'announcement_tab_text'    => [
                    'type'          => 'text',
                    'label'         => __('"Announcement" Tab Label', 'directorist'),
                    'value'         => __('Announcements', 'directorist'),
                    'show-if' => [
                        'where' => "announcement_tab",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'submit_listing_button' => [
                    'type'  => 'toggle',
                    'label' => __('Display Submit Listing Button', 'directorist'),
                    'value' => true,
                ],
                'become_author_button' => [
                    'type'  => 'toggle',
                    'label' => __('Display "Become An Author" button', 'directorist'),
                    'value' => true,
                ],
                'become_author_button_text'    => [
                    'type'          => 'text',
                    'label'         => __('"Become An Author" button Label', 'directorist'),
                    'value'         => __('Become An Author', 'directorist'),
                    'show-if' => [
                        'where' => "become_author_button",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                // search form settings
                'search_title'    => [
                    'type'          => 'text',
                    'label'         => __('Search Bar Title', 'directorist'),
                    'value' => __('Search here', 'directorist'),
                ],
                'search_subtitle'    => [
                    'type'          => 'text',
                    'label'         => __('Search Bar Sub-title', 'directorist'),
                    'value'         => __( 'Find the best match of your interest', 'directorist' ),
                ],
                'search_border' => [
                    'type'  => 'toggle',
                    'label' => __('Search Bar Border', 'directorist'),
                    'value' => true,
                ],

                'search_more_filter' => [
                    'type'  => 'toggle',
                    'label' => __('Display More Filters', 'directorist'),
                    'value' => true,
                ],
                'search_more_filter_icon' => [
                    'type'  => 'toggle',
                    'label' => __('Display More Filters Icon', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "search_more_filter",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_button' => [
                    'type'  => 'toggle',
                    'label' => __('Display Search Button', 'directorist'),
                    'value' => true,
                ],
                'search_button_icon' => [
                    'type'  => 'toggle',
                    'label' => __('Display Search Button Icon', 'directorist'),
                    'value' => true,
                    'show-if' => [
                        'where' => "search_button",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'home_display_filter' => [
                    'label' => __('Open Filter Fields', 'directorist'),
                    'type'  => 'select',
                    'value' => 'sliding',
                    'show-if' => [
                        'where' => "search_more_filter",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'options' => [
                        [
                            'value' => 'overlapping',
                            'label' => __('Overlapping', 'directorist'),
                        ],
                        [
                            'value' => 'sliding',
                            'label' => __('Sliding', 'directorist'),
                        ],
                        [
                            'value' => 'always_open',
                            'label' => __('Always Open', 'directorist'),
                        ],
                    ],
                ],
                'search_filters' => [
                    'type' => 'checkbox',
                    'label' => __('Filters Button', 'directorist'),
                    'show-if' => [
                        'where' => "search_more_filter",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => [
                        'search_reset_filters',
                        'search_apply_filters',
                    ],
                    'options' => [
                        [
                            'value' => 'search_reset_filters',
                            'label' => __('Reset', 'directorist'),
                        ],
                        [
                            'value' => 'search_apply_filters',
                            'label' => __('Apply', 'directorist'),
                        ],
                    ],
                ],
                'search_default_radius_distance' => [
                    'label'         => __('Default Radius Distance', 'directorist'),
                    'type'          => 'number',
                    'value'         => '0',
                    'min'           => '0',
                    'max'           => '750',
                    'step'          => '1',
                    'show-if' => [
                        'where' => "search_more_filter",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_listing_text'    => [
                    'type'          => 'text',
                    'label'         => __('Search Button Text', 'directorist'),
                    'value'         => __('Search Listing', 'directorist'),
                    'show-if' => [
                        'where' => "search_button",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_more_filters'    => [
                    'type'          => 'text',
                    'label'         => __('More Filters Button Text', 'directorist'),
                    'value'         => __('More Filters', 'directorist'),
                    'show-if' => [
                        'where' => "search_more_filter",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_reset_text'    => [
                    'type'          => 'text',
                    'label'         => __('Reset Filters Button Text', 'directorist'),
                    'value'         => __('Reset Filters', 'directorist'),
                    'show-if' => [
                        'where' => "search_more_filter",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_apply_filter'    => [
                    'type'          => 'text',
                    'label'         => __('Apply Filters Button Text', 'directorist'),
                    'value'         => __('Apply Filters', 'directorist'),
                    'show-if' => [
                        'where' => "search_more_filter",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'show_popular_category' => [
                    'type'  => 'toggle',
                    'label' => __('Display Popular Categories', 'directorist'),
                    'value' => false,
                ],

                'popular_cat_title'    => [
                    'type'          => 'text',
                    'label'         => __('Popular Categories Title', 'directorist'),
                    'value'         => __('Browse by popular categories', 'directorist'),
                    'show-if' => [
                        'where' => "show_popular_category",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'popular_cat_num' => [
                    'label'         => __('Number of Popular Categories', 'directorist'),
                    'type'          => 'number',
                    'value'         => '10',
                    'min'           => '1',
                    'max'           => '30',
                    'step'          => '1',
                    'show-if' => [
                        'where' => "show_popular_category",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_home_bg' => [
                    'label'       => __('Search Page Background', 'directorist'),
                    'type'        => 'wp-media-picker',
                    'default-img' => '',
                    'value'       => '',
                ],
                // search result settings
                'search_header' => [
                    'type'  => 'toggle',
                    'label' => __('Display Header', 'directorist'),
                    'value' => true,
                ],
                'search_result_filters_button_display' => [
                    'type'  => 'toggle',
                    'label' => __('Display Filters Button', 'directorist'),
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => true,
                ],
                'search_result_filter_button_text'    => [
                    'type'          => 'text',
                    'label'         => __('Filters Button Text', 'directorist'),
                    'value'         => __('Filters', 'directorist'),
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_result_display_filter' => [
                    'label' => __('Open Filter Fields', 'directorist'),
                    'type'  => 'select',
                    'value' => 'sliding',
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'options' => [
                        [
                            'value' => 'overlapping',
                            'label' => __('Overlapping', 'directorist'),
                        ],
                        [
                            'value' => 'sliding',
                            'label' => __('Sliding', 'directorist'),
                        ],
                    ],
                ],
                'sresult_default_radius_distance' => [
                    'label'         => __('Default Radius Distance', 'directorist'),
                    'type'          => 'number',
                    'value'         => '0',
                    'min'           => '0',
                    'max'           => '1000',
                    'step'          => '1',
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_result_filters_button' => [
                    'type' => 'checkbox',
                    'label' => __('Filters Button', 'directorist'),
                    'description' => '',
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => [
                        'reset_button',
                        'apply_button',
                    ],
                    'options' => [
                        [
                            'value' => 'reset_button',
                            'label' => __('Reset', 'directorist'),
                        ],
                        [
                            'value' => 'apply_button',
                            'label' => __('Apply', 'directorist'),
                        ],
                    ],
                ],
                'sresult_reset_text'    => [
                    'type'          => 'text',
                    'label'         => __('Reset Filters Button text', 'directorist'),
                    'value'         => __('Reset Filters', 'directorist'),
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'sresult_apply_text'    => [
                    'type'          => 'text',
                    'label'         => __('Apply Filters Button text', 'directorist'),
                    'value'         => __('Apply Filters', 'directorist'),
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],

                'search_view_as' => [
                    'type'  => 'toggle',
                    'label' => __('Display "View As" Dropdown', 'directorist'),
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => true,
                ],
                'search_viewas_text'    => [
                    'type'          => 'text',
                    'label'         => __('"View As" Text', 'directorist'),
                    'value'         => __('View As', 'directorist'),
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_view_as_items' => [
                    'type' => 'checkbox',
                    'label' => __('View As" Dropdown', 'directorist'),
                    'description' => '',
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => [
                        'listings_grid',
                        'listings_list',
                        'listings_map'
                    ],
                    'options' => [
                        [
                            'value' => 'listings_grid',
                            'label' => __('Grid', 'directorist'),
                        ],
                        [
                            'value' => 'listings_list',
                            'label' => __('List', 'directorist'),
                        ],
                        [
                            'value' => 'listings_map',
                            'label' => __('Map', 'directorist'),
                        ],
                    ],
                ],
                'search_sort_by' => [
                    'type'  => 'toggle',
                    'label' => __('Display "Sort By" Dropdown', 'directorist'),
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => true,
                ],
                'search_sortby_text'    => [
                    'type'          => 'text',
                    'label'         => __('"Sort By" Text', 'directorist'),
                    'value'         => __('Sort By', 'directorist'),
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'search_sort_by_items' => [
                    'type' => 'checkbox',
                    'label' => __('"Sort By" Dropdown', 'directorist'),
                    'description' => '',
                    'show-if' => [
                        'where' => "search_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                    'value' => [
                        'a_z',
                        'z_a',
                        'latest',
                        'oldest',
                        'popular',
                        'price_low_high',
                        'price_high_low',
                        'random'
                    ],
                    'options' => [
                        [
                            'value' => 'a_z',
                            'label' => __('A to Z (title)', 'directorist'),
                        ],
                        [
                            'value' => 'z_a',
                            'label' => __('Z to A (title)', 'directorist'),
                        ],
                        [
                            'value' => 'latest',
                            'label' => __('Latest listings', 'directorist'),
                        ],
                        [
                            'value' => 'oldest',
                            'label' => __('Oldest listings', 'directorist'),
                        ],
                        [
                            'value' => 'popular',
                            'label' => __('Popular listings', 'directorist'),
                        ],
                        [
                            'value' => 'price_low_high',
                            'label' => __('Price (low to high)', 'directorist'),
                        ],
                        [
                            'value' => 'price_high_low',
                            'label' => __('Price (high to low)', 'directorist'),
                        ],
                        [
                            'value' => 'random',
                            'label' => __('Random listings', 'directorist'),
                        ],
                    ],
                ],
                'search_order_listing_by' => [
                    'label' => __('Order By', 'directorist'),
                    'type'  => 'select',
                    'value' => 'date',
                    'options' => [
                        [
                            'value' => 'title',
                            'label' => __('Title', 'directorist'),
                        ],
                        [
                            'value' => 'date',
                            'label' => __('Date', 'directorist'),
                        ],
                        [
                            'value' => 'price',
                            'label' => __('Price', 'directorist'),
                        ],
                        [
                            'value' => 'rand',
                            'label' => __('Random', 'directorist'),
                        ],
                    ],
                ],
                'search_sort_listing_by' => [
                    'label' => __('Sort By', 'directorist'),
                    'type'  => 'select',
                    'value' => 'desc',
                    'options' => [
                        [
                            'value' => 'asc',
                            'label' => __('Ascending', 'directorist'),
                        ],
                        [
                            'value' => 'desc',
                            'label' => __('Descending', 'directorist'),
                        ],
                    ],
                ],
                'search_listing_columns' => [
                    'label'         => __('Number of Columns', 'directorist'),
                    'type'          => 'number',
                    'value'         => '3',
                    'min'           => '1',
                    'max'           => '5',
                    'step'          => '1',
                ],
                'paginate_search_results' => [
                    'type'  => 'toggle',
                    'label' => __('Paginate Search Result', 'directorist'),
                    'value' => true,
                ],
                'search_posts_num' => [
                    'label'         => __('Search Results Per Page', 'directorist'),
                    'type'          => 'number',
                    'value'         => '6',
                    'min'           => '1',
                    'max'           => '100',
                    'step'          => '1',
                ],
                'radius_search_unit' => [
                    'label' => __('Radius Search Unit', 'directorist'),
                    'type'  => 'select',
                    'value' => 'miles',
                    'options' => [
                        [
                            'value' => 'miles',
                            'label' => __('Miles', 'directorist'),
                        ],
                        [
                            'value' => 'kilometers',
                            'label' => __('Kilometers', 'directorist'),
                        ],
                    ],
                ],
                // upgrade/ regenerate pages
                'shortcode-updated' => [
                    'type'  => 'toggle',
                    'label' => __('Upgrade/Regenerate Pages', 'directorist'),
                    'value' => true,
                ],
                // pages, links, views settings
                'add_listing_page' => [
                    'label' => __('Add Listing Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_add_listing]</div>'),
                    'value' => atbdp_get_option('add_listing_page', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'all_listing_page' => [
                    'label' => __('All Listings Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_all_listing]</div>'),
                    'value' => atbdp_get_option('all_listing_page', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'user_dashboard' => [
                    'label' => __('Dashboard Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_user_dashboard]</div>'),
                    'value' => atbdp_get_option('user_dashboard', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'author_profile_page' => [
                    'label' => __('User Profile Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_author_profile]</div>'),
                    'value' => atbdp_get_option('author_profile', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'all_categories_page' => [
                    'label' => __('All Categories Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_all_categories]</div>'),
                    'value' => atbdp_get_option('all_categories', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'single_category_page' => [
                    'label' => __('Single Category Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_category]</div>'),
                    'value' => atbdp_get_option('single_category_page', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'all_locations_page' => [
                    'label' => __('All Locations Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_all_locations]</div>'),
                    'value' => atbdp_get_option('all_locations', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'single_location_page' => [
                    'label' => __('Single Location Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_location]</div>'),
                    'value' => atbdp_get_option('single_location_page', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'single_tag_page' => [
                    'label' => __('Single Tag Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_tag]</div>'),
                    'value' => atbdp_get_option('single_tag_page', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'custom_registration' => [
                    'label' => __('Registration Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_custom_registration]</div>'),
                    'value' => atbdp_get_option('custom_registration', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'user_login' => [
                    'label' => __('Login Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_user_login]</div>'),
                    'value' => atbdp_get_option('user_login', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'search_listing' => [
                    'label' => __('Listing Search Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_search_listing]</div>'),
                    'value' => atbdp_get_option('search_listing', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'search_result_page' => [
                    'label' => __('Listing Search Result Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_search_result]</div>'),
                    'value' => atbdp_get_option('search_result_page', 'atbdp_general'),
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'checkout_page' => [
                    'label' => __('Checkout Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_checkout]</div>'),
                    'value' => '',
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'payment_receipt_page' => [
                    'label' => __('Payment/Order Receipt Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_payment_receipt]</div>'),
                    'value' => '',
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'transaction_failure_page' => [
                    'label' => __('Transaction Failure Page', 'directorist'),
                    'type'  => 'select',
                    'description' => sprintf(__('Following shortcode must be in the selected page %s', 'directorist'), '<div class="atbdp_shortcodes" style="color: #ff4500;">[directorist_transaction_failure]</div>'),
                    'value' => '',
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'privacy_policy' => [
                    'label' => __('Privacy Policy Page', 'directorist'),
                    'type'  => 'select',
                    'value' => '',
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                'terms_conditions' => [
                    'label' => __('Terms & Conditions Page', 'directorist'),
                    'type'  => 'select',
                    'value' => '',
                    'showDefaultOption' => true,
                    'options' => $this->get_pages_vl_arrays(),
                ],
                // seo settings
                'atbdp_enable_seo' => [
                    'type'  => 'toggle',
                    'label' => __('Enable SEO', 'directorist'),
                    'value' => true,
                ],
                'add_listing_page_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('Add Listing Page Meta Title', 'directorist'),
                    'description'   => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                ],
                'add_listing_page_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Add Listing Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                'all_listing_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('All Listing Page Meta Title', 'directorist'),
                    'description'   => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                ],
                'all_listing_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('All Listing Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                'dashboard_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('User Dashboard Page Meta Title', 'directorist'),
                    'description' => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                ],
                'dashboard_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Dashboard Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                'author_profile_meta_title' => [
                    'type'          => 'text',
                    'label'         => __('Author Page Meta Title', 'directorist'),
                    'description'   => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                ],
                'author_page_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Author Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                'category_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('Category Page Meta Title', 'directorist'),
                    'description' => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                ],
                'category_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Category Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                'single_category_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('Single Category Page Meta Title', 'directorist'),
                    'description'   => __('Default the title of the category.', 'directorist'),
                    'value'         => '',
                ],
                'single_category_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Single Category Page Meta Description', 'directorist'),
                    'description'   => __('Leave it blank to set category\'s description as meta description of this page', 'directorist'),
                    'value'         => '',
                ],
                'all_locations_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('All Locations Page Meta Title', 'directorist'),
                    'description'   => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                ],
                'all_locations_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('All Locations Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                'single_locations_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('Single Location Page Meta Title', 'directorist'),
                    'description'   => __('Default the title of the location.', 'directorist'),
                    'value'         => '',
                ],
                'single_locations_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Single Locations Page Meta Description', 'directorist'),
                    'description'   => __('Leave it blank to set location\'s description as meta description of this page', 'directorist'),
                    'value'         => '',
                ],
                'registration_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('Registration Page Meta Title', 'directorist'),
                    'description'   => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                ],
                'registration_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Registration Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                'login_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('Login Page Meta Title', 'directorist'),
                    'description'   => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                ],
                'login_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Login Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                'homepage_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('Search Home Page Meta Title', 'directorist'),
                    'description' => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                ],
                'homepage_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Search Home Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                'meta_title_for_search_result' => [
                    'label' => __('Search Result Page Meta Title', 'directorist'),
                    'type'  => 'select',
                    'value' => 'searched_value',
                    'options' => [
                        [
                            'value' => 'searched_value',
                            'label' => __('From User Search', 'directorist'),
                        ],
                        [
                            'value' => 'custom',
                            'label' => __('Custom', 'directorist'),
                        ],
                    ],
                ],
                'search_result_meta_title'    => [
                    'type'          => 'text',
                    'label'         => __('Custom Meta Title', 'directorist'),
                    'description'   => __('Default the title of the page set as frontpage.', 'directorist'),
                    'value'         => '',
                    'show-if' => [
                        'where' => "meta_title_for_search_result",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'custom'],
                        ],
                    ],
                ],
                'search_result_meta_desc'    => [
                    'type'          => 'text',
                    'label'         => __('Search Result Page Meta Description', 'directorist'),
                    'value'         => '',
                ],
                //currency settings
                'g_currency_note'    => [
                    'type'          => 'note',
                    'title'         => __('Note About This Currency Settings:', 'directorist'),
                    'description' => __('This currency settings lets you customize how you would like to display price amount in your website. However, you can accept currency in a different currency. Therefore, for accepting currency in a different currency, Go to Gateway Settings Tab.', 'directorist'),
                ],
                'g_currency'    => [
                    'type'          => 'text',
                    'label'         => __('Currency Name', 'directorist'),
                    'description'   => __('Enter the Name of the currency eg. USD or GBP etc.', 'directorist'),
                    'value'         => 'USD',
                ],
                'g_thousand_separator'    => [
                    'type'          => 'text',
                    'label'         => __('Thousand Separator', 'directorist'),
                    'description'   => __('Enter the currency thousand separator. Eg. , or . etc.', 'directorist'),
                    'value'         => ',',
                ],
                'allow_decimal' => [
                    'label'         => __('Allow Decimal', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'g_decimal_separator'    => [
                    'type'          => 'text',
                    'label'         => __('Decimal Separator', 'directorist'),
                    'description'   => __('Enter the currency decimal separator. Eg. "." or ",". Default is "."', 'directorist'),
                    'value'         => '.',
                ],
                'g_currency_position' => [
                    'label'        => __('Currency Position', 'directorist'),
                    'type'        => 'select',
                    'value'       => 'before',
                    'description' => __('Select where you would like to show the currency symbol. Default is before. Eg. $5', 'directorist'),
                    'options' => [
                        [
                            'value' => 'before',
                            'label' => __('$5 - Before', 'directorist'),
                        ],
                        [
                            'value' => 'after',
                            'label' => __('After - 5$', 'directorist'),
                        ],
                    ],
                ],
                // categories settings
                'display_categories_as' => [
                    'label'        => __('Default View', 'directorist'),
                    'type'        => 'select',
                    'value'       => 'grid',
                    'options' => [
                        [
                            'value' => 'grid',
                            'label' => __('Grid', 'directorist'),
                        ],
                        [
                            'value' => 'list',
                            'label' => __('List', 'directorist'),
                        ],
                    ],
                ],
                'categories_column_number' => [
                    'label' => __('Number of  Columns', 'directorist'),
                    'description' => __('Set how many columns to display on categories page.', 'directorist'),
                    'type'  => 'number',
                    'value' => '4',
                    'min' => '1',
                    'max' => '5',
                    'step' => '1',
                ],
                'categories_depth_number' => [
                    'label' => __('Sub-category Depth', 'directorist'),
                    'description' => __('Set how many sub-categories to display.', 'directorist'),
                    'type'  => 'number',
                    'value' => '4',
                    'min' => '1',
                    'max' => '15',
                    'step' => '2',
                    'show-if' => [
                        'where' => "display_categories_as",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'list'],
                        ],
                    ],
                ],
                'order_category_by' => [
                    'label'        => __('Categories Order By', 'directorist'),
                    'type'        => 'select',
                    'value'       => 'id',
                    'options' => [
                        [
                            'value' => 'id',
                            'label' => __('ID', 'directorist'),
                        ],
                        [
                            'value' => 'count',
                            'label' => __('Count', 'directorist'),
                        ],
                        [
                            'value' => 'name',
                            'label' => __('Name', 'directorist'),
                        ],
                        [
                            'value' => 'slug',
                            'label' => __('Slug', 'directorist'),
                        ],
                    ],
                ],
                'sort_category_by' => [
                    'label'       => __('Categories Sort By', 'directorist'),
                    'type'        => 'select',
                    'value'       => 'asc',
                    'options' => [
                        [
                            'value' => 'asc',
                            'label' => __('Ascending', 'directorist'),
                        ],
                        [
                            'value' => 'desc',
                            'label' => __('Descending', 'directorist'),
                        ],
                    ],
                ],
                'display_listing_count' => [
                    'label'         => __('Display Listing Count', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'hide_empty_categories' => [
                    'label'         => __('Hide Empty Categories', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                ],
                // locations settings
                'display_locations_as' => [
                    'label'        => __('Default View', 'directorist'),
                    'type'        => 'select',
                    'value'       => 'grid',
                    'options' => [
                        [
                            'value' => 'grid',
                            'label' => __('Grid', 'directorist'),
                        ],
                        [
                            'value' => 'list',
                            'label' => __('List', 'directorist'),
                        ],
                    ],
                ],
                'locations_column_number' => [
                    'label'       => __('Number of  Columns', 'directorist'),
                    'description' => __('Set how many columns to display on locations page.', 'directorist'),
                    'type'        => 'number',
                    'value'       => '4',
                    'min'         => '1',
                    'max'         => '5',
                    'step'        => '1',
                ],
                'locations_depth_number' => [
                    'label' => __('Sub-location Depth', 'directorist'),
                    'description' => __('Set how many sub-locations to display.', 'directorist'),
                    'type'  => 'number',
                    'value' => '4',
                    'min' => '1',
                    'max' => '15',
                    'step' => '2',
                    'show-if' => [
                        'where' => "display_locations_as",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => 'list'],
                        ],
                    ],
                ],
                'order_location_by' => [
                    'label'        => __('Locations Order By', 'directorist'),
                    'type'        => 'select',
                    'value'       => 'id',
                    'options' => [
                        [
                            'value' => 'id',
                            'label' => __('ID', 'directorist'),
                        ],
                        [
                            'value' => 'count',
                            'label' => __('Count', 'directorist'),
                        ],
                        [
                            'value' => 'name',
                            'label' => __('Name', 'directorist'),
                        ],
                        [
                            'value' => 'slug',
                            'label' => __('Slug', 'directorist'),
                        ],
                    ],
                ],
                'sort_location_by' => [
                    'label'       => __('Locations Sort By', 'directorist'),
                    'type'        => 'select',
                    'value'       => 'asc',
                    'options' => [
                        [
                            'value' => 'asc',
                            'label' => __('Ascending', 'directorist'),
                        ],
                        [
                            'value' => 'desc',
                            'label' => __('Descending', 'directorist'),
                        ],
                    ],
                ],
                'display_location_listing_count' => [
                    'label'         => __('Display Listing Count', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'hide_empty_locations' => [
                    'label'         => __('Hide Empty Locations', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                ],
                // registration settings
                'reg_username'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('Username', 'directorist'),
                ],
                'display_password_reg' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'reg_password'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('Password', 'directorist'),
                    'show-if' => [
                        'where' => "display_password_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'require_password_reg' => [
                    'label'         => __('Required', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                    'show-if' => [
                        'where' => "display_password_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'reg_email'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('Email', 'directorist'),
                ],
                'display_website_reg' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                ],
                'reg_website'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('Website', 'directorist'),
                    'show-if' => [
                        'where' => "display_website_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'require_website_reg' => [
                    'label'         => __('Required', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                    'show-if' => [
                        'where' => "display_website_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'display_fname_reg' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                ],
                'reg_fname'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('First Name', 'directorist'),
                    'show-if' => [
                        'where' => "display_fname_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'require_fname_reg' => [
                    'label'         => __('Required', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                    'show-if' => [
                        'where' => "display_fname_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'display_lname_reg' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                ],
                'reg_lname'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('Last Name', 'directorist'),
                    'show-if' => [
                        'where' => "display_lname_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'require_lname_reg' => [
                    'label'         => __('Required', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                    'show-if' => [
                        'where' => "display_lname_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'display_bio_reg' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                ],
                'reg_bio'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('About/bio', 'directorist'),
                    'show-if' => [
                        'where' => "display_bio_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'require_bio_reg' => [
                    'label'         => __('Required', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                    'show-if' => [
                        'where' => "display_bio_reg",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'registration_privacy' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'registration_privacy_label'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('I agree to the', 'directorist'),
                    'show-if' => [
                        'where' => "registration_privacy",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'registration_privacy_label_link'    => [
                    'type'          => 'text',
                    'label'         => __('Linking Text', 'directorist'),
                    'value'         => __('Privacy & Policy', 'directorist'),
                    'show-if' => [
                        'where' => "registration_privacy",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'regi_terms_condition' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'regi_terms_label'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('I agree with all', 'directorist'),
                    'show-if' => [
                        'where' => "regi_terms_condition",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'regi_terms_label_link'    => [
                    'type'          => 'text',
                    'label'         => __('Linking Text', 'directorist'),
                    'value'         => __('terms & conditions', 'directorist'),
                    'show-if' => [
                        'where' => "regi_terms_condition",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'display_user_type' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                ],
                'reg_signup'    => [
                    'type'          => 'text',
                    'label'         => __('Text', 'directorist'),
                    'value'         => __('Sign Up', 'directorist'),
                ],
                'display_login' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'login_text'    => [
                    'type'          => 'text',
                    'label'         => __('Text', 'directorist'),
                    'value'         => __('Already have an account? Please login', 'directorist'),
                    'show-if' => [
                        'where' => "display_login",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'log_linkingmsg'    => [
                    'type'          => 'text',
                    'label'         => __('Linking Text', 'directorist'),
                    'value'         => __('Here', 'directorist'),
                    'show-if' => [
                        'where' => "display_login",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'auto_login' => [
                    'label'         => __('Auto Login after Registration', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                ],
                'redirection_after_reg' => [
                    'label' => __('Redirection after Registration', 'directorist'),
                    'type'  => 'select',
                    'value' => atbdp_get_option('user_dashboard', 'atbdp_general'),
                    'options' => $this->get_pages_with_prev_page(),
                ],
                // login settings
                'log_username'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('Username or Email Address', 'directorist'),
                ],
                'log_password'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('Password', 'directorist'),
                ],
                'display_rememberme' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'log_rememberme'    => [
                    'type'          => 'text',
                    'label'         => __('Label', 'directorist'),
                    'value'         => __('Remember Me', 'directorist'),
                    'show-if' => [
                        'where' => "display_rememberme",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'log_button'    => [
                    'type'          => 'text',
                    'label'         => __('Text', 'directorist'),
                    'value'         => __('Log In', 'directorist'),
                ],
                'display_signup' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'reg_text'    => [
                    'type'          => 'textarea',
                    'label'         => __('Text', 'directorist'),
                    'value'         => __("Don't have an account?", 'directorist'),
                    'show-if' => [
                        'where' => "display_signup",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'reg_linktxt'    => [
                    'type'          => 'text',
                    'label'         => __('Linking Text', 'directorist'),
                    'value'         => __('Sign Up', 'directorist'),
                    'show-if' => [
                        'where' => "display_signup",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'display_recpass' => [
                    'label'         => __('Enable', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'recpass_text'    => [
                    'type'          => 'text',
                    'label'         => __('Name', 'directorist'),
                    'value'         => __('Recover Password', 'directorist'),
                    'show-if' => [
                        'where' => "display_recpass",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'recpass_desc'    => [
                    'type'          => 'textarea',
                    'label'         => __('Description', 'directorist'),
                    'value'         => __('Lost your password? Please enter your email address. You will receive a link to create a new password via email.', 'directorist'),
                    'show-if' => [
                        'where' => "display_recpass",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'recpass_username'    => [
                    'type'          => 'text',
                    'label'         => __('Email Label', 'directorist'),
                    'value'         => __('E-mail', 'directorist'),
                    'show-if' => [
                        'where' => "display_recpass",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'recpass_placeholder'    => [
                    'type'          => 'text',
                    'label'         => __('Username or Email Placeholder', 'directorist'),
                    'value'         => __('eg. mail@example.com', 'directorist'),
                    'show-if' => [
                        'where' => "display_recpass",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'recpass_button'    => [
                    'type'          => 'text',
                    'label'         => __('Button Text', 'directorist'),
                    'value'         => __('Get New Password', 'directorist'),
                    'show-if' => [
                        'where' => "display_recpass",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'redirection_after_login' => [
                    'label' => __('Redirection after Login', 'directorist'),
                    'type'  => 'select',
                    'value' => atbdp_get_option('user_dashboard', 'atbdp_general'),
                    'options' => $this->get_pages_with_prev_page(),
                ],
                // email general settings
                'disable_email_notification' => [
                    'label'         => __('Disable all Email Notifications', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => false,
                ],
                'email_from_name'    => [
                    'type'           => 'text',
                    'label'          => __('Email\'s "From Name"', 'directorist'),
                    'description'    => __('The name should be used as From Name in the email generated by the plugin.', 'directorist'),
                    'value'         => get_option('blogname'),
                    'show-if' => [
                        'where' => "disable_email_notification",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                ],
                'email_from_email'    => [
                    'type'           => 'text',
                    'label'          => __('Email\'s "From Email"', 'directorist'),
                    'description'    => __('The email should be used as From Email in the email generated by the plugin.', 'directorist'),
                    'value'          => get_option('admin_email'),
                    'show-if' => [
                        'where' => "disable_email_notification",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                ],
                'admin_email_lists'    => [
                    'type'           => 'textarea',
                    'label'          => __('Admin Email Address(es)', 'directorist'),
                    'description' => __('Enter the one or more admin email addresses (comma separated) to send notification. Eg. admin1@example.com, admin2@example.com etc', 'directorist'),
                    'value'          => get_option('admin_email'),
                    'show-if' => [
                        'where' => "disable_email_notification",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                ],
                'notify_admin' => [
                    'label'       => __('Notify the Admin when Any of the Selected Event Happens', 'directorist'),
                    'type'        => 'checkbox',
                    'value'       =>  $this->default_events_to_notify_admin(),
                    'options'     => $this->events_to_notify_admin(),
                    'description' => __('Select the situation when you would like to send an email to the Admin', 'directorist'),
                    'show-if' => [
                        'where' => "disable_email_notification",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                ],
                'notify_user' => [
                    'label'       => __('Notify the Listing Owner when Any of the Selected Event Happens', 'directorist'),
                    'type'        => 'checkbox',
                    'value'       => $this->default_events_to_notify_user(),
                    'options'     => $this->events_to_notify_user(),
                    'description' => __('Select the situation when you would like to send an email to the Listing', 'directorist'),
                    'show-if' => [
                        'where' => "disable_email_notification",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => false],
                        ],
                    ],
                ],
                // email templates settings
                'allow_email_header' => [
                    'label'         => __('Email Header', 'directorist'),
                    'type'          => 'toggle',
                    'value'         => true,
                ],
                'email_header_color'    => [
                    'type'           => 'color',
                    'label'          => __('Email Header Color', 'directorist'),
                    'value'          => '#8569fb',
                    'show-if' => [
                        'where' => "allow_email_header",
                        'conditions' => [
                            ['key' => 'value', 'compare' => '=', 'value' => true],
                        ],
                    ],
                ],
                'email_sub_new_listing'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when a listing is submitted/received.', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : Listing "==LISTING_TITLE==" Received', 'directorist'),
                ],
                'email_tmpl_new_listing'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description' => __('Edit the email template for sending to the user when a listing is submitted/received. HTML content is allowed too.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,

                    This email is to notify you that your listing '==LISTING_TITLE==' has been received and it is under review now.
                    It may take up to 24 hours to complete the review.

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_pub_listing'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when a listing is approved/published.', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : Listing "==LISTING_TITLE==" published', 'directorist'),
                ],
                'email_tmpl_pub_listing'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user when a listing is approved/published. HTML content is allowed too.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,
                    Congratulations! Your listing '==LISTING_TITLE==' has been approved/published. Now it is publicly available at ==LISTING_URL==

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_edit_listing'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when a listing is edited.', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : Listing "==LISTING_TITLE==" Edited', 'directorist'),
                ],
                'email_tmpl_edit_listing'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user when a listing is edited. HTML content is allowed too.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,
                    Congratulations! Your listing '==LISTING_TITLE==' has been edited. It is publicly available at ==LISTING_URL==

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_to_expire_listing'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when a listing is ABOUT TO EXPIRE.', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : Your Listing "==LISTING_TITLE==" is about to expire.', 'directorist'),
                ],
                'email_tmpl_to_expire_listing'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user when a listing is ABOUT TO EXPIRE. HTML content is allowed too.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,
                    Your listing '==LISTING_TITLE==' is about to expire. It will expire on ==EXPIRATION_DATE==. You can renew it at ==RENEWAL_LINK==

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_expired_listing'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when a Listing HAS EXPIRED.', 'directorist'),
                    'value'          => __("[==SITE_NAME==] : Your Listing '==LISTING_TITLE==' has expired.", 'directorist'),
                ],
                'email_tmpl_expired_listing'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user when a Listing HAS EXPIRED. HTML content is allowed too.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,
                    Your listing '==LISTING_TITLE==' has expired on ==EXPIRATION_DATE==. You can renew it at ==RENEWAL_LINK==

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_to_renewal_listing'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user to renew his/her listings.', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : A Reminder to Renew your listing "==LISTING_TITLE=="', 'directorist'),
                ],
                'email_tmpl_to_renewal_listing'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user to renew his/her listings. HTML content is allowed too.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,

                    We have noticed that you might have forgot to renew your listing '==LISTING_TITLE==' at ==SITE_LINK==. We would like to remind you that it expired on ==EXPIRATION_DATE==. But please don't worry.  You can still renew it by clicking this link: ==RENEWAL_LINK==.

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_renewed_listing'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user his/her listings has renewed successfully.', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : Your Listing "==LISTING_TITLE==" Has Renewed', 'directorist'),
                ],
                'email_tmpl_renewed_listing'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user his/her listings has renewed successfully. HTML content is allowed too.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,

                    Congratulations!
                    Your listing '==LISTING_LINK==' with the ID #==LISTING_ID== has been renewed successfully at ==SITE_LINK==.
                    Your listing is now publicly viewable at ==LISTING_URL==

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_deleted_listing'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when his/her listings has deleted successfully.', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : Your Listing "==LISTING_TITLE==" Has Been Deleted', 'directorist'),
                ],
                'email_tmpl_deleted_listing'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user when his/her listings has deleted successfully. HTML content is allowed too.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,

                    Your listing '==LISTING_LINK==' with the ID #==LISTING_ID== has been deleted successfully at ==SITE_LINK==.

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_new_order'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when an order is created.', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : Your Order (#==ORDER_ID==) Received.', 'directorist'),
                ],
                'email_tmpl_new_order'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user when an order is created.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,

                    Thank you very much for your order.
                    This email is to notify you that your order (#==ORDER_ID==) has been received. You can check your order details and progress by clicking the link below.

                    Order Details Page: ==ORDER_RECEIPT_URL==

                    Your order summery:
                    ==ORDER_DETAILS==


                    NB. You need to be logged in your account to access the order details page.

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_offline_new_order'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when an order is created using offline payment like bank transfer.', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : Your Order (#==ORDER_ID==) Received.', 'directorist'),
                ],
                'email_tmpl_offline_new_order'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user when an order is created using offline payment like bank transfer.', 'directorist'),
                    'value'          => sprintf(__("
                    Dear ==NAME==,

                    Thank you very much for your order.
                    This email is to notify you that your order (#==ORDER_ID==) has been received.

                    %s

                    You can check your order details and progress by clicking the link below.
                    Order Details Page: ==ORDER_RECEIPT_URL==

                    Your order summery:
                    ==ORDER_DETAILS==


                    NB. You need to be logged in your account to access the order details page.

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'), get_directorist_option('bank_transfer_instruction')),
                ],
                'email_sub_completed_order'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when an order is completed', 'directorist'),
                    'value'          => __('[==SITE_NAME==] : Congratulation! Your Order #==ORDER_ID== Completed.', 'directorist'),
                ],
                'email_tmpl_completed_order'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user when an order is completed.', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,

                    Congratulation! This email is to notify you that your order #==ORDER_ID== has been completed.

                    You can check your order details by clicking the link below.
                    Order Details Page: ==ORDER_RECEIPT_URL==

                    Your order summery:
                    ==ORDER_DETAILS==


                    NB. You need to be logged in your account to access the order details page.

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_listing_contact_email'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when listing contact message send.', 'directorist'),
                    'value'          => __('==SITE_NAME== Contact via ==LISTING_TITLE==', 'directorist'),
                ],
                'email_tmpl_listing_contact_email'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Edit the email template for sending to the user when when listing contact message send', 'directorist'),
                    'value'          => __("
                    Dear ==NAME==,

                    You have received a message from your listing at ==LISTING_URL==.

                    Name: ==SENDER_NAME==
                    Email: ==SENDER_EMAIL==
                    Message: ==MESSAGE==
                    Time: ==NOW==

                    Thanks,
                    The Administrator of ==SITE_NAME==
                    ", 'directorist'),
                ],
                'email_sub_registration_confirmation'    => [
                    'type'           => 'text',
                    'label'          => __('Email Subject', 'directorist'),
                    'description'    => __('Edit the subject for sending to the user when listing contact message send.', 'directorist'),
                    'value'          => __('Registration Confirmation!', 'directorist'),
                ],
                'email_tmpl_registration_confirmation'    => [
                    'type'           => 'textarea',
                    'label'          => __('Email Body', 'directorist'),
                    'description'    => __('Note: Use ==USER_PASSWORD== to show a temporary password when password field is disable from registration page', 'directorist'),
                    'value'          => __('
                    Hi ==USERNAME==,

                    Thanks for creating an account on <b>==SITE_NAME==</b>. Your username is <b>==USERNAME==</b>. You can access your account area to view listings, change your password, and more at: ==DASHBOARD_LINK==

                    We look forward to seeing you soon', 'directorist'),
                ],
                // single template settings
                'single_temp_max_width'    => [
                    'type'           => 'text',
                    'label'          => __('Maximum Width (px)', 'directorist'),
                    'value'          => '1080',
                ],
                'single_temp_padding_top'    => [
                    'type'           => 'text',
                    'label'          => __('Top', 'directorist'),
                    'value'          => '30',
                ],
                'single_temp_padding_bottom'    => [
                    'type'           => 'text',
                    'label'          => __('Bottom', 'directorist'),
                    'value'          => '50',
                ],
                'single_temp_padding_left'  => [
                    'type'           => 'text',
                    'label'          => __('Left', 'directorist'),
                    'value'          => '4',
                ],
                'single_temp_padding_right'    => [
                    'type'           => 'text',
                    'label'          => __('right', 'directorist'),
                    'value'          => '4',
                ],
                'single_temp_margin_top' => [
                    'type'           => 'text',
                    'label'          => __('Top', 'directorist'),
                    'value'          => '4',
                ],
                'single_temp_margin_bottom' => [
                    'type'           => 'text',
                    'label'          => __('Bottom', 'directorist'),
                    'value'          => '50',
                ],
                'single_temp_margin_left'  => [
                    'type'           => 'text',
                    'label'          => __('Left', 'directorist'),
                    'value'          => '4',
                ],
                'single_temp_margin_right'    => [
                    'type'           => 'text',
                    'label'          => __('right', 'directorist'),
                    'value'          => '4',
                ],
                'enable_uninstall'    => [
                    'type'           => 'toggle',
                    'label'          => __('Remove Data on Uninstall?', 'directorist'),
                    'description'=> __('Checked it if you would like Directorist to completely remove all of its data when the plugin is deleted.','directorist'),
                    'value'          => false,
                ],
            ]);

            $this->layouts = apply_filters('atbdp_listing_type_settings_layout', [
                'listing_settings' => [
                    'label' => __( 'Listings', 'directorist' ),
                    'icon' => '<i class="fa fa-list directorist_Blue"></i>',
                    'submenu' => apply_filters('atbdp_listing_settings_submenu', [
                        'general' => [
                            'label' => __('General', 'directorist'),
                            'icon' => '<i class="fa fa-sliders-h"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_general_sections', [
                                'general_settings' => [
                                    'fields'      => [
                                        'enable_multi_directory',
                                        'font_type', 'can_renew_listing', 'email_to_expire_day', 'email_renewal_day', 'delete_expired_listing', 'delete_expired_listings_after', 'deletion_mode', 'paginate_author_listings', 'display_author_email', 'author_cat_filter', 'guest_listings',
                                    ],
                                ],

                            ] ),
                        ],
                        'listings_page' => [
                            'label' => __('All Listings', 'directorist'),
                            'icon' => '<i class="fa fa-archive"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_listings_page_sections', [
                                'labels' => [
                                    'fields'      => [
                                        'display_listings_header', 'all_listing_title', 'listing_filters_button', 'listing_filters_icon', 'listings_filter_button_text', 'listing_tags_field', 'listing_default_radius_distance', 'listings_filters_button', 'listings_reset_text', 'listings_apply_text', 'display_sort_by', 'sort_by_text', 'listings_sort_by_items', 'display_view_as', 'view_as_text', 'listings_view_as_items', 'default_listing_view', 'grid_view_as', 'all_listing_columns', 'order_listing_by', 'sort_listing_by', 'preview_image_quality', 'way_to_show_preview', 'crop_width', 'crop_height', 'prv_container_size_by', 'prv_background_type', 'prv_background_color', 'default_preview_image', 'info_display_in_single_line', 'address_location', 'publish_date_format', 'paginate_all_listings', 'all_listing_page_items'
                                    ],
                                ],
                            ] ),
                        ],
                        'single_listing' => [
                            'label' => __('Single Listings', 'directorist'),
                            'icon' => '<i class="fa fa-info"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_listing_page_sections', [
                                'labels' => [
                                    'fields'      => [
                                        'disable_single_listing', 'restrict_single_listing_for_logged_in_user', 'atbdp_listing_slug', 'single_listing_slug_with_directory_type', 'edit_listing_redirect', 'submission_confirmation', 'pending_confirmation_msg', 'publish_confirmation_msg', 'dsiplay_slider_single_page', 'single_slider_image_size', 'single_slider_background_type', 'single_slider_background_color', 'gallery_crop_width', 'gallery_crop_height', 'address_map_link', 'rel_listings_logic', 'fix_listing_double_thumb'
                                    ],
                                ],
                            ] ),
                        ],
                        'categories_locations' => [
                            'label' => __( 'Location & Category', 'directorist' ),
                            'icon' => '<i class="fa fa-list-alt"></i>',
                            'sections' => apply_filters( 'atbdp_categories_settings_sections', [
                                'categories_settings' => [
                                    'title'       => __('Categories Page Settings', 'directorist'),
                                    'fields'      => [
                                        'display_categories_as', 'categories_column_number', 'categories_depth_number', 'order_category_by', 'sort_category_by', 'display_listing_count', 'hide_empty_categories'
                                     ],
                                ],
                                'locations_settings' => [
                                    'title'       => __('Locations Page Settings', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_locations_as', 'locations_column_number', 'locations_depth_number', 'order_location_by', 'sort_location_by', 'display_location_listing_count', 'hide_empty_locations'
                                     ],
                                ],
                            ] ),
                        ],


                        'review' => [
                            'label' => __('Review', 'directorist'),
                            'icon' => '<i class="fa fa-star"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_review_sections', [
                                'labels' => [
                                    'fields'      => [
                                        'enable_review', 'enable_owner_review', 'approve_immediately', 'review_approval_text', 'enable_reviewer_img', 'enable_reviewer_content', 'required_reviewer_content', 'review_num', 'guest_review'
                                    ],
                                ],
                            ] ),
                        ],

                        'currency_settings' => [
                            'label' => __( 'Listing Currency', 'directorist' ),
                            'icon' => '<i class="fa fa-money-bill"></i>',
                            'sections' => apply_filters( 'atbdp_currency_settings_sections', [
                                'title_metas' => [
                                    'fields'      => [
                                        'g_currency_note', 'g_currency', 'g_thousand_separator', 'allow_decimal', 'g_decimal_separator', 'g_currency_position'
                                     ],
                                ],
                            ] ),
                        ],

                        'map' => [
                            'label' => __('Map', 'directorist'),
                            'icon' => '<i class="fa fa-map"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_map_sections', [
                                'map_settings' => [
                                    'title'       => __('Map', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'select_listing_map', 'map_api_key', 'country_restriction', 'restricted_countries', 'default_latitude', 'default_longitude', 'map_zoom_level', 'map_view_zoom_level', 'listings_map_height'
                                    ],
                                ],
                                'map_info_window' => [
                                    'title'       => __('Map Info Window Settings', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_map_info', 'display_image_map', 'display_title_map', 'display_address_map', 'display_direction_map'
                                    ],
                                ],
                            ] ),
                        ],

                    ]),
                ],

                'page_settings' => [
                    'label' => __( 'Page Setup', 'directorist' ),
                    'icon' => '<i class="fa fa-desktop directorist_wordpress"></i>',
                    'sections' => apply_filters( 'atbdp_listing_settings_page_settings_sections', [
                        'upgrade_pages' => [
                            'title'       => __('Upgrade/Regenerate Pages', 'directorist'),
                            'description' => '',
                            'fields'      => [
                                'regenerate_pages'
                             ],
                        ],
                        'pages_links_views' => [
                            'title'       => __('Page, Links & View Settings', 'directorist'),
                            'description' => '',
                            'fields'      => apply_filters( 'atbdp_pages_settings_fields', [
                                'add_listing_page', 'all_listing_page', 'user_dashboard', 'author_profile_page', 'all_categories_page', 'single_category_page', 'all_locations_page', 'single_location_page', 'single_tag_page', 'custom_registration', 'user_login', 'search_listing', 'search_result_page', 'checkout_page', 'payment_receipt_page', 'transaction_failure_page', 'privacy_policy', 'terms_conditions'
                             ] ),
                        ],
                    ]),
                ],

                'search_settings' => [
                    'label' => __( 'Search', 'directorist' ),
                    'icon' => '<i class="fa fa-search directorist_warning"></i>',
                    'submenu' => apply_filters('atbdp_email_settings_submenu', [
                        'search_form' => [
                            'label' => __('Search Listing', 'directorist'),
                            'icon' => '<i class="fa fa-search"></i>',
                            'sections' => apply_filters( 'directorist_search_setting_sections', [
                                'search_form' => [
                                    'fields'      => [
                                        'search_title', 'search_subtitle', 'search_border', 'search_more_filter', 'search_more_filter_icon', 'search_button', 'search_button_icon', 'home_display_filter', 'search_filters','search_default_radius_distance', 'search_listing_text', 'search_more_filters', 'search_reset_text', 'search_apply_filter', 'show_popular_category', 'popular_cat_title', 'popular_cat_num', 'search_home_bg'
                                     ],
                                ],
                            ] ),
                        ],

                        'search_result' => [
                            'label' => __('Search Result', 'directorist'),
                            'icon' => '<i class="fa fa-check"></i>',
                            'sections' => apply_filters( 'atbdp_reg_settings_sections', [
                                'search_result' => [
                                    'fields'      => [
                                        'search_header', 'search_result_filters_button_display', 'search_result_filter_button_text', 'search_result_display_filter', 'sresult_default_radius_distance', 'search_result_filters_button', 'sresult_reset_text', 'sresult_apply_text', 'search_view_as', 'search_viewas_text', 'search_view_as_items', 'search_sort_by', 'search_sortby_text', 'search_sort_by_items', 'search_order_listing_by', 'search_sort_listing_by', 'search_listing_columns', 'paginate_search_results', 'search_posts_num', 'radius_search_unit'
                                     ],
                                ],
                            ] ),
                        ],

                    ]),
                ],

                'user_settings' => [
                    'label' => __( 'User', 'directorist' ),
                    'icon' => '<i class="fa fa-users-cog directorist_green"></i>',
                    'submenu' => apply_filters('atbdp_user_settings_submenu', [
                        'registration_form' => [
                            'label' => __('Registration Form', 'directorist'),
                            'icon' => '<i class="fa fa-envelope-open"></i>',
                            'sections' => apply_filters( 'atbdp_reg_settings_sections', [
                                'username' => [
                                    'title'       => __('Username', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'reg_username'
                                     ],
                                ],
                                'password' => [
                                    'title'       => __('Password', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_password_reg', 'reg_password', 'require_password_reg'
                                     ],
                                ],
                                'email' => [
                                    'title'       => __('Email', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'reg_email'
                                     ],
                                ],
                                'website' => [
                                    'title'       => __('Website', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_website_reg', 'reg_website', 'require_website_reg'
                                     ],
                                ],
                                'first_name' => [
                                    'title'       => __('First Name', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_fname_reg', 'reg_fname', 'require_fname_reg'
                                     ],
                                ],
                                'last_name' => [
                                    'title'       => __('Last Name', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_lname_reg', 'reg_lname', 'require_lname_reg'
                                     ],
                                ],
                                'about' => [
                                    'title'       => __('About/Bio', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_bio_reg', 'reg_bio', 'require_bio_reg'
                                     ],
                                ],
                                'user_type' => [
                                    'title'       => __('User Type Registration', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_user_type'
                                     ],
                                ],
                                'privacy_policy' => [
                                    'title'       => __('Privacy Policy', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'registration_privacy', 'registration_privacy_label', 'registration_privacy_label_link'
                                     ],
                                ],
                                'terms_condition' => [
                                    'title'       => __('Terms Conditions', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'regi_terms_condition', 'regi_terms_label', 'regi_terms_label_link'
                                     ],
                                ],

                                'signup_button' => [
                                    'title'       => __('Sign Up Button', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'reg_signup'
                                     ],
                                ],
                                'login_message' => [
                                    'title'       => __('Login Message', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_login', 'login_text', 'log_linkingmsg'
                                     ],
                                ],
                                'redirection' => [
                                    'title'       => __('', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'auto_login', 'redirection_after_reg'
                                     ],
                                ],
                            ] ),
                        ],
                        'login_form' => [
                            'label' => __('Login Form', 'directorist'),
                            'icon' => '<i class="fa fa-mail-bulk"></i>',
                            'sections' => apply_filters( 'atbdp_email_templates_settings_sections', [
                                'username' => [
                                    'title'       => __('Username', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'log_username'
                                     ],
                                ],
                                'password' => [
                                    'title'       => __('Password', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'log_password'
                                     ],
                                ],
                                'remember_login_info' => [
                                    'title'       => __('Remember Login Information', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_rememberme', 'log_rememberme'
                                     ],
                                ],
                                'login_button' => [
                                    'title'       => __('Login Button', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'log_button'
                                     ],
                                ],
                                'signup_message' => [
                                    'title'       => __('Sign Up Message', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_signup', 'reg_text', 'reg_linktxt'
                                     ],
                                ],
                                'recover_password' => [
                                    'title'       => __('Recover Password', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'display_recpass', 'recpass_text', 'recpass_desc', 'recpass_username', 'recpass_placeholder', 'recpass_button'
                                     ],
                                ],
                                'login_redirect' => [
                                    'title'       => '',
                                    'description' => '',
                                    'fields'      => [
                                        'redirection_after_login'
                                     ],
                                ],

                            ] ),
                        ],
                        'user_dashboard' => [
                            'label' => __('Dashboard', 'directorist'),
                            'icon' => '<i class="fa fa-chart-bar"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_user_dashboard_sections', [
                                'general_dashboard' => [
                                    'fields'      => [
                                         'my_profile_tab', 'my_profile_tab_text', 'fav_listings_tab', 'fav_listings_tab_text', 'announcement_tab', 'announcement_tab_text'
                                    ],
                                ],
                                'author_dashboard' => [
                                    'title'       => __('Author Dashboard', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'my_listing_tab', 'my_listing_tab_text', 'user_listings_pagination', 'user_listings_per_page', 'submit_listing_button'
                                        ],
                                ],
                                'user_dashboard' => [
                                    'title'       => __('User Dashboard', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'become_author_button', 'become_author_button_text'
                                        ],
                                ],
                            ] ),
                        ],
                    ]),
                ],

                'email_settings' => [
                    'label' => __( 'Email', 'directorist' ),
                    'icon' => '<i class="fa fa-envelope directorist_Blue"></i>',
                    'submenu' => apply_filters('atbdp_email_settings_submenu', [
                        'email_general' => [
                            'label' => __('Email General', 'directorist'),
                            'icon' => '<i class="fa fa-envelope-open directorist_info"></i>',
                            'sections' => apply_filters( 'atbdp_reg_settings_sections', [
                                'username' => [
                                    'fields'      => [
                                        'disable_email_notification', 'email_from_name', 'email_from_email', 'admin_email_lists', 'notify_admin', 'notify_user'
                                     ],
                                ],
                            ] ),
                        ],
                        'email_templates' => [
                            'label' => __('Email Templates', 'directorist'),
                            'icon' => '<i class="fa fa-mail-bulk directorist_info"></i>',
                            'sections' => apply_filters( 'atbdp_email_templates_settings_sections', [
                                'general' => [
                                    'title'       => __('General', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'allow_email_header', 'email_header_color'
                                     ],
                                ],
                                'new_listing' => [
                                    'title'       => __('For New Listing', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_note', 'email_sub_new_listing', 'email_tmpl_new_listing'
                                     ],
                                ],
                                'approved_listings' => [
                                    'title'       => __('For Approved/Published Listings', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_pub_listing', 'email_tmpl_pub_listing'
                                     ],
                                ],
                                'edited_listings' => [
                                    'title'       => __('For Edited Listings', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_edit_listing', 'email_tmpl_edit_listing'
                                     ],
                                ],
                                'about_expire_listings' => [
                                    'title'       => __('For About To Expire Listings', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                       'email_sub_to_expire_listing', 'email_tmpl_to_expire_listing'
                                     ],
                                ],
                                'expired_listings' => [
                                    'title'       => __('For Expired Listings', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_expired_listing', 'email_tmpl_expired_listing'
                                     ],
                                ],
                                'remind_renewal_listings' => [
                                    'title'       => __('For Renewal Listings (Remind To Renew)', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_to_renewal_listing', 'email_tmpl_to_renewal_listing'
                                     ],
                                ],
                                'after_renewed_listings' => [
                                    'title'       => __('For Renewed Listings (After Renewed)', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_renewed_listing', 'email_tmpl_renewed_listing'
                                     ],
                                ],
                                'deleted_listings' => [
                                    'title'       => __('For Deleted/Trashed Listings', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_deleted_listing', 'email_tmpl_deleted_listing'
                                     ],
                                ],
                                'new_order_created' => [
                                    'title'       => __('For New Order (Created)', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_new_order', 'email_tmpl_new_order'
                                     ],
                                ],
                                'new_order_offline_bank' => [
                                    'title'       => __('For New Order (Created Using Offline Bank Transfer)', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_offline_new_order', 'email_tmpl_offline_new_order'
                                     ],
                                ],
                                'completed_order' => [
                                    'title'       => __('For Completed Order', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_completed_order', 'email_tmpl_completed_order'
                                     ],
                                ],
                                'listing_contact_email' => [
                                    'title'       => __('For Listing Contact Email', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_listing_contact_email', 'email_tmpl_listing_contact_email'
                                     ],
                                ],
                                'registration_confirmation' => [
                                    'title'       => __('Registration Confirmation', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'email_sub_registration_confirmation', 'email_tmpl_registration_confirmation'
                                     ],
                                ],
                            ] ),
                        ],
                    ]),
                ],

                'monetization_settings' => [
                    'label' => __( 'Monetization', 'directorist' ),
                    'icon' => '<i class="fa fa-credit-card directorist_info"></i>',
                    'submenu' => apply_filters('atbdp_monetization_settings_submenu', [
                        'monetization_general' => [
                            'label' => __('General Settings', 'directorist'),
                            'icon' => '<i class="fa fa-home"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_monetization_general_sections', [
                                'general' => [
                                    'title'       => __('Monetization Settings', 'directorist'),
                                    'description' => '',
                                    'fields'      => [ 'enable_monetization' ],
                                ],
                                'plan_promo' => [
                                    'title'       => __('Monetize by Listing Plans', 'directorist'),
                                    'description' => '',
                                    'fields'      => [ 'monetization_promotion' ],
                                ],
                            ] ),
                        ],
                        'featured_listings' => [
                            'label' => __('Featured Listing', 'directorist'),
                            'icon' => '<i class="fa fa-arrow-up"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_featured_sections', [
                                'featured' => [
                                    'fields'      => [
                                        'enable_featured_listing',
                                        'featured_listing_title',
                                        'featured_listing_desc',
                                        'featured_listing_price',
                                        'featured_listing_time',
                                    ],
                                ],
                            ] ),
                        ],
                        'gateway' => [
                            'label' => __('Gateways Settings', 'directorist'),
                            'icon' => '<i class="fa fa-bezier-curve"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_gateway_sections', [
                                'gateway_general' => [
                                    'fields'      => [
                                        'paypal_gateway_promotion',
                                        'gateway_test_mode',
                                        'active_gateways',
                                        'default_gateway',
                                        'payment_currency_note',
                                        'payment_currency',
                                        'payment_thousand_separator',
                                        'payment_decimal_separator',
                                        'payment_currency_position'
                                    ],
                                ],
                            ] ),
                        ],
                        'offline_gateway' => [
                            'label' => __('Offline Gateways Settings', 'directorist'),
                            'icon' => '<i class="fa fa-university"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_offline_gateway_sections', [
                                'offline_gateway_general' => [
                                    'fields'      => [
                                        'offline_payment_note',
                                        'bank_transfer_title',
                                        'bank_transfer_description',
                                        'bank_transfer_instruction'
                                    ],
                                ],
                            ] ),
                        ],
                    ]),
                ],

                'style_settings' => [
                    'label' => __( 'Personalization', 'directorist' ),
                    'icon' => '<i class="fa fa-paint-brush directorist_success"></i>',
                    'submenu' => apply_filters('atbdp_style_settings_submenu', [
                        'single_template' => [
                            'label' => __('Single Listing Template', 'directorist'),
                            'icon' => '<i class="fa fa-swatchbook directorist_info"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_single_template_sections', [
                                'general' => [
                                    'title'       => '',
                                    'description' => '',
                                    'fields'      => [
                                        'single_listing_template',
                                        'single_temp_max_width'
                                    ],
                                ],
                                'padding' => [
                                    'title'       => __('Padding (PX)'),
                                    'description' => '',
                                    'fields'      => [
                                        'single_temp_padding_top', 'single_temp_padding_bottom', 'single_temp_padding_left', 'single_temp_padding_right'
                                    ],
                                ],
                                'margin' => [
                                    'title'       => __('Margin (PX)'),
                                    'description' => '',
                                    'fields'      => [
                                        'single_temp_margin_top', 'single_temp_margin_bottom', 'single_temp_margin_left', 'single_temp_margin_right'
                                    ],
                                ],
                            ] ),
                        ],
                        'color_settings' => [
                            'label' => __('Color', 'directorist'),
                            'icon' => '<i class="fa fa-palette directorist_info"></i>',
                            'sections'=> apply_filters('atbdp_style_settings_controls', [
                                'button_type' => [
                                    'title' => __('Button Color', 'directorist'),
                                    'fields' => [
                                        'button_type', 'primary_example', 'primary_color', 'primary_hover_color', 'back_primary_color', 'back_primary_hover_color', 'border_primary_color', 'border_primary_hover_color', 'secondary_example', 'secondary_color', 'secondary_hover_color', 'back_secondary_color', 'back_secondary_hover_color', 'secondary_border_color', 'secondary_border_hover_color', 'danger_example', 'danger_color', 'danger_hover_color', 'back_danger_color', 'back_danger_hover_color', 'danger_border_color', 'danger_border_hover_color', 'success_example', 'success_color', 'success_hover_color', 'back_success_color', 'back_success_hover_color', 'border_success_color', 'border_success_hover_color', 'lighter_example', 'lighter_color', 'lighter_hover_color', 'back_lighter_color', 'back_lighter_hover_color', 'border_lighter_color', 'border_lighter_hover_color', 'priout_example', 'priout_color', 'priout_hover_color', 'back_priout_color', 'back_priout_hover_color', 'border_priout_color', 'border_priout_hover_color', 'prioutlight_example', 'prioutlight_color', 'prioutlight_hover_color', 'back_prioutlight_color', 'back_prioutlight_hover_color', 'border_prioutlight_color', 'border_prioutlight_hover_color', 'danout_example', 'danout_color', 'danout_hover_color', 'back_danout_color', 'back_danout_hover_color', 'border_danout_color', 'border_danout_hover_color'
                                    ]
                                ],

                                'badge_color' => [
                                    'title' => __('Badge Color', 'directorist'),
                                    'fields' => apply_filters('atbdp_badge_color', [
                                        'open_back_color',
                                        'closed_back_color',
                                        'featured_back_color',
                                        'popular_back_color',
                                        'new_back_color',
                                    ])
                                ],

                                'map_marker' => [
                                    'title' => __('All Listings Map Marker', 'directorist'),
                                    'fields' => apply_filters('atbdp_map_marker_color', [
                                        'marker_shape_color',
                                        'marker_icon_color'
                                    ])
                                ],

                                'primary_color' => array(
                                    'title' => __('Primary Color', 'directorist'),
                                    'fields' => apply_filters('atbdp_primary_dark_color', [
                                        'primary_dark_back_color',
                                        'primary_dark_border_color'
                                    ])
                                ),
                            ])
                        ],
                        'badge' => [
                            'label' => __('Badge', 'directorist'),
                            'icon' => '<i class="fa fa-certificate"></i>',
                            'sections' => apply_filters( 'atbdp_listing_settings_badge_sections', [
                                'badge_management' => [
                                    'title'       => __('Badge Management', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'new_badge_text', 'new_listing_day'
                                    ],
                                ],
                                'popular_badge' => [
                                    'title'       => __('Popular Badge', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'popular_badge_text', 'listing_popular_by', 'views_for_popular', 'average_review_for_popular', 'count_loggedin_user'
                                    ],
                                ],
                                'featured_badge' => [
                                    'title'       => __('Featured Badge', 'directorist'),
                                    'description' => '',
                                    'fields'      => [
                                        'feature_badge_text'
                                    ],
                                ],
                            ] ),
                        ],

                    ]),
                ],

                'extension_settings' => [
                    'label' => __( 'Extensions', 'directorist' ),
                    'icon' => '<i class="fa fa-magic directorist_danger"></i>',
                    'submenu' => apply_filters('atbdp_extension_settings_submenu', [
                        'extensions_general' => [
                            'label' => __('Extensions General', 'directorist'),
                            'icon' => '<i class="fa fa-home"></i>',
                            'sections' => apply_filters( 'atbdp_extension_settings_controls', [
                                'general_settings' => [
                                    'fields' =>  apply_filters( 'atbdp_extension_fields', [
                                         'extension_promotion'
                                    ]) ,
                                ],
                            ] ),
                        ],
                    ]),
                ],

                'tools' => [
                    'label' => __( 'Tools', 'directorist' ),
                    'icon' => '<i class="fa fa-tools directorist_info"></i>',
                    'submenu' => apply_filters('atbdp_tools_submenu', [
                        'announcement_settings' => [
                            'label'     => __('Announcement', 'directorist'),
                            'icon' => '<i class="fa fa-bullhorn"></i>',
                            'sections'  => apply_filters('atbdp_announcement_settings_controls', [
                                'send-announcement'     => [
                                    'fields'        => [
                                        'announcement',
                                    ]
                                ],
                            ]),
                        ],

                        'listings_import' => [
                            'label' => __('Listings Import/Export', 'directorist'),
                            'icon' => '<i class="fa fa-upload"></i>',
                            'sections'  => apply_filters('atbdp_listings_import_controls', [
                                'import_methods' => array(
                                    'fields' => apply_filters('atbdp_csv_import_settings_fields', [
                                        'listing_import_button', 'listing_export_button',
                                    ]),
                                ),
                            ]),
                        ],

                        'settings_import_export' => [
                            'label' => __( 'Settings Import/Export', 'directorist' ),
                            'icon' => '<i class="fa fa-tools"></i>',
                            'sections'  => apply_filters('atbdp_settings_import_export_controls', [
                                'import_export' => [
                                    'title' => __( 'Import/Export', 'directorist' ),
                                    'fields' => [ 'import_settings', 'export_settings' ]
                                ],
                                'restore_default' => [
                                    'title' => __( 'Restore Default', 'directorist' ),
                                    'fields' => [ 'restore_default_settings' ]
                                ],
                            ]),
                        ],

                        'other' => [
                            'label' => __( 'Other', 'directorist' ),
                            'icon' => '<i class="fa fa-list"></i>',
                            'sections'  => apply_filters('atbdp_settings_tools_other_controls', [
                                'builder-options' => [
                                    'title' => __( 'Builder Options', 'directorist' ),
                                    'fields' => [ 'sanitize_builder_data' ]
                                ],
                            ]),
                        ],

                    ]),
                ],

                'advanced' => [
                    'label' => __( 'Advanced', 'directorist' ),
                    'icon' => '<i class="fa fa-filter directorist_wordpress"></i>',
                    'submenu' => apply_filters('atbdp_advanced_submenu', [
                        'seo_settings' => [
                            'label' => __( 'Title & Meta (SEO)', 'directorist' ),
                            'icon' => '<i class="fa fa-bolt"></i>',
                            'sections' => apply_filters( 'atbdp_seo_settings_sections', [
                                'title_metas' => [
                                    'fields'      => [
                                        'atbdp_enable_seo', 'add_listing_page_meta_title', 'add_listing_page_meta_desc', 'all_listing_meta_title', 'all_listing_meta_desc', 'dashboard_meta_title', 'dashboard_meta_desc', 'author_profile_meta_title', 'author_page_meta_desc', 'category_meta_title', 'category_meta_desc', 'single_category_meta_title', 'single_category_meta_desc', 'all_locations_meta_title', 'all_locations_meta_desc', 'single_locations_meta_title', 'single_locations_meta_desc', 'registration_meta_title', 'registration_meta_desc', 'login_meta_title', 'login_meta_desc', 'homepage_meta_title', 'homepage_meta_desc', 'meta_title_for_search_result', 'search_result_meta_title', 'search_result_meta_desc'
                                     ],
                                ],
                            ] ),
                        ],

                        'miscellaneous' => [
                            'label'     => __('Miscellaneous', 'directorist'),
                            'icon' => '<i class="fas fa-thumbtack"></i>',
                            'sections'  => apply_filters('atbdp_caching_controls', [
                                'caching' => [
                                    'title' => __( 'Caching', 'directorist' ),
                                    'fields'      => [
                                        'atbdp_enable_cache', 'atbdp_reset_cache',
                                     ],
                                ],
                                'debugging' => [
                                    'title' => __( 'Debugging', 'directorist' ),
                                    'fields'      => [
                                        'script_debugging',
                                     ],
                                ],
                                'uninstall' => [
                                    'title' => __( 'Uninstall', 'directorist' ),
                                    'fields' => [ 'enable_uninstall' ]
                                ],
                            ] ),
                        ],
                    ]),
                ],

            ]);

            $this->config = [
                'fields_theme' => 'butterfly',
                'submission' => [
                    'url' => admin_url('admin-ajax.php'),
                    'with' => [ 'action' => 'save_settings_data' ],
                ],
            ];


        }


        // add_menu_pages
        public function add_menu_pages()
        {
            add_submenu_page(
                'edit.php?post_type=at_biz_dir',
                'Settings',
                'Settings',
                'manage_options',
                'atbdp-settings',
                [ $this, 'menu_page_callback__settings_manager' ],
                12
            );
        }

        // menu_page_callback__settings_manager
        public function menu_page_callback__settings_manager()
        {
            // Prepare Settings
            $this->prepare_settings();

            // Get Saved Data
            $atbdp_options = get_option('atbdp_option');

            foreach( $this->fields as $field_key => $field_opt ) {
                if ( ! isset(  $atbdp_options[ $field_key ] ) ) {
                    $this->fields[ $field_key ]['forceUpdate'] = true;
                    continue;
                }

                $this->fields[ $field_key ]['value'] = $atbdp_options[ $field_key ];
            }

            $atbdp_settings_manager_data = [
                'fields'  => $this->fields,
                'layouts' => $this->layouts,
                'config'  => $this->config,
            ];

            wp_localize_script('directorist-settings-manager', 'atbdp_settings_manager_data', $atbdp_settings_manager_data);

            /* $status = $this->update_settings_options([
                'new_listing_status' => 'publish',
                'edit_listing_status' => 'publish',
            ]);

            $check_new = get_directorist_option( 'new_listing_status' );
            $check_edit = get_directorist_option( 'edit_listing_status' );

            var_dump( [ '$check_new' => $check_new,  '$check_edit' => $check_edit] ); */

            atbdp_load_admin_template('settings-manager/settings');
        }

        /**
         * Get all the pages in an array where each page is an array of key:value:id and key:label:name
         *
         * Example : array(
         *                  array('value'=> 1, 'label'=> 'page_name'),
         *                  array('value'=> 50, 'label'=> 'page_name'),
         *          )
         * @return array page names with key value pairs in a multi-dimensional array
         * @since 3.0.0
         */
        function get_pages_vl_arrays()
        {
            $pages = get_pages();
            $pages_options = array();
            if ($pages) {
                foreach ($pages as $page) {
                    $pages_options[] = array('value' => $page->ID, 'label' => $page->post_title);
                }
            }

            return $pages_options;
        }

        /**
         * Get all the pages with previous page in an array where each page is an array of key:value:id and key:label:name
         *
         * Example : array(
         *                  array('value'=> 1, 'label'=> 'page_name'),
         *                  array('value'=> 50, 'label'=> 'page_name'),
         *          )
         * @return array page names with key value pairs in a multi-dimensional array
         * @since 3.0.0
         */
        function get_pages_with_prev_page()
        {
            $pages = get_pages();
            $pages_options = array();
            $pages_options[] = array( 'value' => 'previous_page', 'label' => 'Previous Page' );
            if ($pages) {
                foreach ($pages as $page) {
                    $pages_options[] = array('value' => $page->ID, 'label' => $page->post_title);
                }
            }

            return $pages_options;
        }

        /**
         * Get an array of events to notify both the admin and the users
         * @return array it returns an array of events
         * @since 3.1.0
         */
        private function default_notifiable_events()
        {
            return apply_filters('atbdp_default_notifiable_events', array(
                array(
                    'value' => 'order_created',
                    'label' => __('Order Created', 'directorist'),
                ),
                array(
                    'value' => 'order_completed',
                    'label' => __('Order Completed', 'directorist'),
                ),
                array(
                    'value' => 'listing_submitted',
                    'label' => __('New Listing Submitted', 'directorist'),
                ),
                array(
                    'value' => 'listing_published',
                    'label' => __('Listing Approved/Published', 'directorist'),
                ),
                array(
                    'value' => 'listing_edited',
                    'label' => __('Listing Edited', 'directorist'),
                ),
                array(
                    'value' => 'payment_received',
                    'label' => __('Payment Received', 'directorist'),
                ),
                array(
                    'value' => 'listing_deleted',
                    'label' => __('Listing Deleted', 'directorist'),
                ),
                array(
                    'value' => 'listing_contact_form',
                    'label' => __('Listing Contact Form', 'directorist'),
                ),
                array(
                    'value' => 'listing_review',
                    'label' => __('Listing Review', 'directorist'),
                ),
            ));
        }

        /**
         * Get the list of an array of notification events array to notify admin
         * @return array It returns an array of events when an admin should be notified
         * @since 3.1.0
         */
        public function events_to_notify_admin()
        {
            $events = $this->default_notifiable_events();
            return apply_filters('atbdp_events_to_notify_admin', $events);
        }

        /**
         * Get the list of an array of notification events array to notify user
         * @return array It returns an array of events when an user should be notified
         * @since 3.1.0
         */
        public function events_to_notify_user()
        {
            $events = array_merge($this->default_notifiable_events(), $this->only_user_notifiable_events());
            return apply_filters('atbdp_events_to_notify_user', $events);
        }

        /**
         * Get the default events to notify the user.
         * @return array It returns an array of default events when an user should be notified.
         * @since 3.1.0
         */
        public function default_events_to_notify_user()
        {
            return apply_filters('atbdp_default_events_to_notify_user', array(
                'order_created',
                'listing_submitted',
                'payment_received',
                'listing_published',
                'listing_to_expire',
                'listing_expired',
                'remind_to_renew',
                'listing_renewed',
                'order_completed',
                'listing_edited',
                'listing_deleted',
                'listing_contact_form',
            ));
        }

        /**
         * Get an array of events to notify only users
         * @return array it returns an array of events
         * @since 3.1.0
         */
        private function only_user_notifiable_events()
        {
            return apply_filters('atbdp_only_user_notifiable_events', array(
                array(
                    'value' => 'listing_to_expire',
                    'label' => __('Listing nearly Expired', 'directorist'),
                ),
                array(
                    'value' => 'listing_expired',
                    'label' => __('Listing Expired', 'directorist'),
                ),
                array(
                    'value' => 'remind_to_renew',
                    'label' => __('Remind to renew', 'directorist'),
                ),
            ));
        }

        /**
         * Get the default events to notify the admin.
         * @return array It returns an array of default events when an admin should be notified.
         * @since 3.1.0
         */
        public function default_events_to_notify_admin()
        {
            return apply_filters('atbdp_default_events_to_notify_admin', array(
                'order_created',
                'order_completed',
                'listing_submitted',
                'payment_received',
                'listing_published',
                'listing_deleted',
                'listing_contact_form',
                'listing_review'
            ));
        }
    }
}