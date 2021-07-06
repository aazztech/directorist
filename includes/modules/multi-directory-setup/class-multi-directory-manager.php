<?php

namespace Directorist;

include_files();
class Multi_Directory_Manager
{
    use Multi_Directory_Helper;

    public static $fields  = [];
    public static $layouts = [];
    public static $config  = [];

    public static $builder_data_sanitizer = null;
    public static $migration = null;

    public $default_form      = [];
    public $old_custom_fields = [];
    public $cetagory_options  = [];

    public function __construct() {
        self::$migration = new Multi_Directory_Migration([ 'multi_directory_manager' => $this ]);
        self::$builder_data_sanitizer = new Builder_Data_Sanitizer();
    }

    // run
    public function run() {
        add_filter( 'cptm_fields_before_update', [$this, 'cptm_fields_before_update'], 20, 1 );

        // add_action( 'admin_enqueue_scripts', [$this, 'register_scripts'] );
        add_action( 'init', [$this, 'register_terms'] );
        add_action( 'init', [$this, 'setup_migration'] );
        add_action( 'init', [$this, 'init_sanitize_builder_data_structure'] );
        add_action( 'init', [$this, 'update_default_directory_type_option'] );
        add_action( 'admin_menu', [$this, 'add_menu_pages'] );
        add_action( 'admin_post_delete_listing_type', [$this, 'handle_delete_listing_type_request'] );

        // Ajax
        add_action( 'wp_ajax_save_post_type_data', [ $this, 'save_post_type_data' ] );
        add_action( 'wp_ajax_save_imported_post_type_data', [ $this, 'save_imported_post_type_data' ] );
        add_action( 'wp_ajax_directorist_force_migrate', [ $this, 'handle_force_migration' ] );
        add_action( 'wp_ajax_directorist_sanitize_builder_data_structure', [ $this, 'handle_sanitize_builder_data_structure_request' ] );
        
        add_filter( 'atbdp_listing_type_settings_layout', [ $this, 'conditional_layouts' ] );
    }

    // init_sanitize_builder_data_structure
    public function init_sanitize_builder_data_structure() {
        $is_sanitized = get_directorist_option( 'sanitized_builder_data_structure', false, true );

        if ( ! empty( $is_sanitized ) ) { return; }
        self::$builder_data_sanitizer->sanitize_builder_data_structure();

        update_directorist_option( 'sanitized_builder_data_structure', true );
    }

    // handle_sanitize_builder_data_structure_request
    public function handle_sanitize_builder_data_structure_request() {
        self::$builder_data_sanitizer->sanitize_builder_data_structure();

        wp_send_json_success(__('The data has been updated successfully', 'directorist'));
    }

    // update_default_directory_type_option
    public function update_default_directory_type_option() {
        $args = array(
            'hide_empty' => false, // also retrieve terms which are not used yet
            'meta_query' => array(
                array(
                    'key'   => '_default',
                    'value' => true,
                )
            ),
            'taxonomy' => ATBDP_DIRECTORY_TYPE,
        );

        $default_directory = get_directorist_option( 'atbdp_default_derectory', '' );
        $terms = get_terms( $args );

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $default_directory = $terms[0]->term_id;
        }

        update_directorist_option( 'atbdp_default_derectory', $default_directory );
    }

    public function conditional_layouts( $layouts ) {

        $updated_layouts = $layouts;

        if( ! directorist_multi_directory() ) {
            unset( $updated_layouts['general']['sections']['default_preview'] );
        }
        return $updated_layouts;
    }

    // get_cetagory_options
    public function get_cetagory_options() {
        $terms = get_terms( array(
            'taxonomy'   => ATBDP_CATEGORY,
            'hide_empty' => false,
        ));

        $options = [];

        if ( is_wp_error( $terms ) ) { return $options; }
        if ( ! count( $terms ) ) { return $options; }

        foreach( $terms as $term ) {
            $options[] = [ 
                'id'    => $term->term_id,
                'value' => $term->term_id,
                'label' => $term->name,
            ];
        }

        return $options; 
    }
    
    // get_assign_to_field
    public function get_assign_to_field( array $args = [] ) {
        $default = [
            'type' => 'radio',
            'label' => __('Assign to', 'directorist'),
            'value' => 'form',
            'options' => [
                [
                    'label' => __('Form', 'directorist'),
                    'value' => 'form',
                ],
                [
                    'label' => __('Category', 'directorist'),
                    'value' => 'category',
                ],
            ],
        ];

        return array_merge( $default, $args );
    }

    // get_category_select_field
    public function get_category_select_field( array $args = [] ) {
        $default = [
            'type'    => 'select',
            'label'   => __('Select Category', 'directorist'),
            'value'   => '',
            'options' => $this->cetagory_options,
        ];

        return array_merge( $default, $args );
    }

    // setup_migration
    public function setup_migration() {
        $directory_types = get_terms( array(
            'taxonomy'   => ATBDP_DIRECTORY_TYPE,
            'hide_empty' => false,
        ));

        $has_multidirectory = ( ! is_wp_error( $directory_types ) && ! empty( $directory_types ) ) ? true : false;

        $get_listings = new \WP_Query([
            'post_type' => ATBDP_POST_TYPE,
            'posts_per_page' => 1,
        ]);

        $get_custom_fields = new \WP_Query([
            'post_type' => ATBDP_CUSTOM_FIELD_POST_TYPE,
            'posts_per_page' => 1,
        ]);
        
        $migrated          = get_option( 'atbdp_migrated', false );
        $has_listings      = false;
        $has_custom_fields = false;

        $has_listings        = $get_listings->post_count;
        $has_custom_fields   = $get_custom_fields->post_count;
        $need_migration      = ( empty( $migrated ) && ! $has_multidirectory && ( $has_listings || $has_custom_fields ) ) ? true : false;
        $need_import_default = ( ! $has_multidirectory && ! ( $has_listings || $has_custom_fields ) ) ? true : false;

        if ( $need_migration ) {
            $this->prepare_settings();
            self::$migration->migrate();
            return;
        }

        if ( apply_filters( 'atbdp_import_default_directory', $need_import_default ) ) {
            $this->prepare_settings();
            $this->import_default_directory();
        }
    }

    // handle_force_migration
    public function handle_force_migration() {
        wp_send_json( $this->run_force_migration() );
    }

    // run_force_migration
    public function run_force_migration() {
        $general_directory = term_exists( 'General', 'atbdp_listing_types' );
        $args = [];

        if ( $general_directory ) {
            $args[ 'term_id' ] = $general_directory['term_id'];
        }
        
        $this->prepare_settings();
        $migration_status = self::$migration->migrate( $args );
        
        $status = [
            'success' => $migration_status['success'],
            'message' => ( $migration_status ) ? __( 'Migration Successful', 'directorist' ) : __( 'Migration Failed', 'directorist' ),
        ];

        return $status;
    }

    // import_default_directory
    public function import_default_directory( array $args = [] ) {
        $file = trailingslashit( dirname( ATBDP_FILE ) )  . 'admin/assets/sample-data/directory/directory.json';
        if ( ! file_exists( $file ) ) { return; }
        $file_contents = file_get_contents( $file );

        $add_directory = self::add_directory([
            'directory_name' => 'General',
            'fields_value'   => $file_contents,
            'is_json'        => true
        ]);

        if ( $add_directory['status']['success'] ) {
            update_option( 'atbdp_has_multidirectory', true );
            update_term_meta( $add_directory['term_id'], '_default', true );

            // Add directory type to all listings
            $listings = new \WP_Query([
                'post_type' => ATBDP_POST_TYPE,
                'status'    => 'publish',
                'per_page'  => -1,                
            ]);

            if ( $listings->have_posts() ) {
                while ( $listings->have_posts() ) {
                    $listings->the_post();

                    wp_set_object_terms( get_the_id(), $add_directory['term_id'], 'atbdp_listing_types' );
                }
            }
        }
    }

    public function save_imported_post_type_data() {
        $term_id        = ( ! empty( $_POST[ 'term_id' ] ) ) ? ( int ) $_POST[ 'term_id' ] : 0;
        $directory_name = ( ! empty( $_POST[ 'directory-name' ] ) ) ? $_POST[ 'directory-name' ] : '';
        $json_file      = ( ! empty( $_FILES[ 'directory-import-file' ] ) ) ? $_FILES[ 'directory-import-file' ] : '';

        // Validation
        $response = [
            'status' => [
                'success'     => true,
                'status_log'  => [],
                'error_count' => 0,
            ]
        ];

        // Validate file
        if ( empty( $json_file ) ) {
            $response['status']['status_log']['file_is_missing'] = [
                'type' => 'error',
                'message' => __( 'File is missing', 'directorist' ),
            ];

            $response['status']['error_count']++;
        }

        // Validate file data
        $file_contents = file_get_contents( $json_file['tmp_name'] );
        if ( empty( $file_contents ) ) {
            $response['status']['status_log']['invalid_data'] = [
                'type' => 'error',
                'message' => __( 'The data is invalid', 'directorist' ),
            ];

            $response['status']['error_count']++;
        }
    
        // Send respone if has error
        if ( $response['status']['error_count'] ) {
            $response['status']['success'] = false;
            wp_send_json( $response , 200 );
        }

        // If dierctory name is numeric update the term instead of creating
        if ( is_numeric( $directory_name ) ) {
            $term_id = (int) $directory_name;
            $directory_name = '';
        }

        $this->prepare_settings();
        
        $add_directory = self::add_directory([ 
            'term_id'        => $term_id,
            'directory_name' => $directory_name,
            'fields_value'   => $file_contents,
            'is_json'        => true
        ]);

        wp_send_json( $add_directory, 200 );
    }


    // cptm_fields_before_update
    public function cptm_fields_before_update( $fields ) {
        $new_fields     = $fields;
        $fields_group   = self::$config['fields_group'];

        foreach ( $fields_group as $group_name => $group_fields ) {
            $grouped_fields_value = [];

            foreach ( $group_fields as $field_index => $field_key ) {
                if ('string' === gettype( $field_key ) && array_key_exists($field_key, self::$fields)) {
                    $grouped_fields_value[ $field_key ] = ( isset( $new_fields[ $field_key ] ) ) ? $new_fields[ $field_key ] : '';
                    unset( $new_fields[ $field_key ] );
                }

                if ( 'array' === gettype( $field_key ) ) {
                    $grouped_fields_value[ $field_index ] = [];

                    foreach ( $field_key as $sub_field_key ) {
                        if ( array_key_exists( $sub_field_key, self::$fields ) ) {
                            $grouped_fields_value[ $field_index ][ $sub_field_key ] = ( isset( $new_fields[ $sub_field_key ] ) ) ? $new_fields[ $sub_field_key ] : '';
                            unset( $new_fields[ $sub_field_key ] );
                        }
                    }
                }
            }

            $new_fields[ $group_name ] = $grouped_fields_value;
        }

        return $new_fields;
    }   

    // save_post_type_data
    public function save_post_type_data()
    {
        if ( empty( $_POST['name'] ) ) {
            wp_send_json([
                'status' => [
                    'success' => false,
                    'status_log' => [
                        'name_is_missing' => [
                            'type' => 'error',
                            'message' => __( 'Name is missing', 'directorist' ),
                        ],
                    ],
                ],
            ], 200);
        }

        $term_id        = ( ! empty( $_POST['listing_type_id'] ) ) ? $_POST['listing_type_id'] : 0;
        $directory_name = $_POST['name'];

        $fields     = [];
        $field_list = self::maybe_json( $_POST['field_list'] );

        foreach ( $field_list as $field_key ) {
            if ( isset( $_POST[$field_key] ) && 'name' !==  $field_key ) {
                $fields[ $field_key ] = $_POST[$field_key];
            }
        }

        $this->prepare_settings();

        $add_directory = self::add_directory([
            'term_id'        => $term_id,
            'directory_name' => $directory_name,
            'fields_value'   => $fields,
        ]);

        if ( ! $add_directory['status']['success'] ) {
            wp_send_json( $add_directory );
        }

        $enable_multi_directory = get_directorist_option( 'enable_multi_directory', false );

        if (  $enable_multi_directory && empty( $term_id ) ) {
            $redirect_url = admin_url( 'edit.php?post_type=at_biz_dir&page=atbdp-directory-types&action=edit&listing_type_id=' . $add_directory['term_id'] );
            $add_directory['redirect_url'] = $redirect_url;
        }
        
        $add_directory['term_id'] = $add_directory['term_id'];

        wp_send_json( $add_directory );
    }

    // update_validated_term_meta
    public static function update_validated_term_meta( $term_id, $field_key, $value ) {
        if ( ! isset( self::$fields[$field_key] ) && ! array_key_exists( $field_key, self::$config['fields_group'] ) ) {
            return;
        }

        if ( ! empty( self::$fields[$field_key]['type'] ) && 'toggle' === self::$fields[$field_key]['type'] ) {
            $value = ('true' === $value || true === $value || '1' === $value || 1 === $value) ? true : 0;
        }

        $value = self::maybe_json($value);
        update_term_meta($term_id, $field_key, $value);
    }

    // prepare_settings
    public function prepare_settings() {
        $this->cetagory_options = $this->get_cetagory_options();

        $custom_field_meta_key_field = apply_filters( 'directorist_custom_field_meta_key_field_args', [
            'type'  => 'hidden',
            'label' => __( 'Key', 'directorist' ),
            'value' => 'custom-text',
            'rules' => [
                'unique' => true,
                'required' => true,
            ]
        ]);

        $form_field_widgets = [
            'preset' => [
                'title' => __( 'Preset Fields', 'directorist' ),
                'description' => __( 'Click on a field to use it', 'directorist' ),
                'allowMultiple' => false,
                'widgets' => apply_filters('atbdp_form_preset_widgets', [
                    'title' => [
                        'label' => __( 'Title', 'directorist' ),
                        'icon' => 'fa fa-text-height',
                        'canTrash' => false,
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'text',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'listing_title',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Title',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => true,
                            ],

                        ],
                    ],

                    'description' => [
                        'label' => __( 'Description', 'directorist' ),
                        'icon' => 'fa fa-align-left',
                        'show' => true,
                        'options' => [
                            'type' => [
                                'type'  => 'select',
                                'label' => 'Type',
                                'value' => 'wp_editor',
                                'options' => [
                                    [
                                        'label' => __('Textarea', 'directorist'),
                                        'value' => 'textarea',
                                    ],
                                    [
                                        'label' => __('WP Editor', 'directorist'),
                                        'value' => 'wp_editor',
                                    ],
                                ]
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value' => 'listing_content',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Description',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                                'show_if' => [
                                    'where' => "self.type",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'textarea'],
                                    ],
                                ],
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'name'  => 'required',
                                'label'  => 'Required',
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],

                        ]
                    ],

                    'tagline' => [
                        'label' => __( 'Tagline', 'directorist' ),
                        'icon' => 'uil uil-text-fields',
                        'show' => true,
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'text',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'tagline',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Tagline',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],

                        ],
                    ],

                    'pricing' => [
                        'label' => __( 'Pricing', 'directorist' ),
                        'icon' => 'uil uil-bill',
                        'options' => [
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'pricing',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value'  => 'Pricing',
                            ],
                            'pricing_type' => [
                                'type'  => 'select',
                                'label'  => __( 'Select Pricing Type', 'directorist' ),
                                'value' => 'both',
                                // 'show-default-option' => true,
                                'options' => [
                                    ['value' => 'both', 'label' => 'Both'],
                                    ['value' => 'price_unit', 'label' => 'Price Unit'],
                                    ['value' => 'price_range', 'label' => 'Price Range'],
                                ],
                            ],
                            'price_range_label' => [
                                'type'  => 'text',
                                'show_if' => [
                                    'where' => "self.pricing_type",
                                    'compare' => 'or',
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'both'],
                                        ['key' => 'value', 'compare' => '=', 'value' => 'price_range'],
                                    ],
                                ],
                                'label'  => __( 'Select Pricing Type', 'directorist' ),
                                'value' => 'Price Range',
                            ],
                            'price_range_placeholder' => [
                                'type'  => 'text',
                                'show_if' => [
                                    'where' => "self.pricing_type",
                                    'compare' => 'or',
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'both'],
                                        ['key' => 'value', 'compare' => '=', 'value' => 'price_range'],
                                    ],
                                ],
                                'label'  => __( 'Price Range Placeholder', 'directorist' ),
                                'value' => 'Select Price Range',
                            ],
                            'price_unit_field_type' => [
                                'type'  => 'select',
                                'label'  => __( 'Price Unit Field Type', 'directorist' ),
                                'show_if' => [
                                    'where' => "self.pricing_type",
                                    'compare' => 'or',
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'both'],
                                        ['key' => 'value', 'compare' => '=', 'value' => 'price_unit'],
                                    ],
                                ],
                                'value' => 'number',
                                'options' => [
                                    ['value' => 'number', 'label' => 'Number',],
                                    ['value' => 'text', 'label' => 'Text',],
                                ],
                            ],
                            'price_unit_field_label' => [
                                'type'  => 'text',
                                'label'  => __( 'Price Unit Field label', 'directorist' ),
                                'show_if' => [
                                    'where' => "self.pricing_type",
                                    'compare' => 'or',
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'both'],
                                        ['key' => 'value', 'compare' => '=', 'value' => 'price_unit'],
                                    ],
                                ],
                                'value' => 'Price [USD]',
                            ],
                            'price_unit_field_placeholder' => [
                                'type'  => 'text',
                                'label'  => __( 'Price Unit Field Placeholder', 'directorist' ),
                                'show_if' => [
                                    'where' => "self.pricing_type",
                                    'compare' => 'or',
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'both'],
                                        ['key' => 'value', 'compare' => '=', 'value' => 'price_unit'],
                                    ],
                                ],
                                'value' => 'Price of this listing. Eg. 100',
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'modules' => [
                                'type'  => 'hidden',
                                'value' => [
                                    'price_unit' => [
                                        'label'     => __( 'Price Unit', 'directorist-pricing-plans' ),
                                        'type'      => 'text',
                                        'field_key' => 'price_unit',
                                    ],
                                    'price_range' => [
                                        'label'     => __( 'Price Range', 'directorist-pricing-plans' ),
                                        'type'      => 'text',
                                        'field_key' => 'price_range',
                                    ],
                                ],
                            ],
                        ]
                    ],

                    'view_count' => [
                        'label' => __( 'View Count', 'directorist' ),
                        'icon' => 'uil uil-eye',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'number',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value' => 'atbdp_post_views_count',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'View Count',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => true,
                            ],


                        ],
                    ],

                    'excerpt' => [
                        'label' => __( 'Excerpt', 'directorist' ),
                        'icon' => 'uil uil-subject',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'textarea',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value' => 'excerpt',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Excerpt',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'location' => [
                        'label' => 'Location',
                        'icon' => 'uil uil-map-marker',
                        'options' => [
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'tax_input[at_biz_dir-location][]',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Location',
                            ],
                            'type' => [
                                'type'  => 'radio',
                                'value' => 'multiple',
                                'label' => __( 'Selection Type', 'directorist' ),
                                'options' => [
                                    [
                                        'label' => __('Single Selection', 'directorist'),
                                        'value' => 'single',
                                    ],
                                    [
                                        'label' => __('Multi Selection', 'directorist'),
                                        'value' => 'multiple',
                                    ]
                                ]
                            ],
                            'create_new_loc' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Allow New', 'directorist' ),
                                'value' => false,
                            ],
                            'max_location_creation' => [
                                'type'  => 'number',
                                'label'  => __( 'Maximum Number', 'directorist' ),
                                'placeholder' => 'Here 0 means unlimited',
                                'value' => '0',
                                'show_if' => [
                                    'where' => "self.type",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'multiple'],
                                    ],
                                ],
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'tag' => [
                        'label' => 'Tag',
                        'icon' => 'uil uil-tag-alt',
                        'options' => [
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'tax_input[at_biz_dir-tags][]',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'type' => [
                                'type'  => 'radio',
                                'value' => 'multiple',
                                'label' => __( 'Selection Type', 'directorist' ),
                                'options' => [
                                    [
                                        'label' => __('Single Selection', 'directorist'),
                                        'value' => 'single',
                                    ],
                                    [
                                        'label' => __('Multi Selection', 'directorist'),
                                        'value' => 'multiple',
                                    ]
                                ]
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'allow_new' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Allow New', 'directorist' ),
                                'value' => true,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'category' => [
                        'label' => __( 'Category', 'directorist' ),
                        'icon' => 'uil uil-folder-open',
                        'options' => [
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'admin_category_select[]',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Category',
                            ],
                            'type' => [
                                'type'  => 'radio',
                                'value' => 'multiple',
                                'label' => __( 'Selection Type', 'directorist' ),
                                'options' => [
                                    [
                                        'label' => __('Single Selection', 'directorist'),
                                        'value' => 'single',
                                    ],
                                    [
                                        'label' => __('Multi Selection', 'directorist'),
                                        'value' => 'multiple',
                                    ]
                                ]
                            ],
                            'create_new_cat' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Allow New', 'directorist' ),
                                'value' => false,
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],   

                    'map' => [
                        'label' => 'Map',
                        'icon' => 'uil uil-map',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'map',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'map',
                            ],
                            'label' => [
                                'type'  => 'hidden',
                                'value' => 'Map',
                            ],
                            'lat_long' => [
                                'type'  => 'text',
                                'label' => __( 'Enter Coordinates Label', 'directorist' ),
                                'value' => __( 'Or Enter Coordinates (latitude and longitude) Manually', 'directorist' ),
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                        ],
                    ],

                    'address' => [
                        'label' => 'Address',
                        'icon' => 'uil uil-map-pin',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'text',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'address',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Address',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => __( 'Listing address eg. New York, USA', 'directorist' ),
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                        ],
                    ],

                    'zip' => [
                        'label' => 'Zip/Post Code',
                        'icon' => 'uil uil-map-pin',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'text',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'zip',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Zip/Post Code',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use'),
                                'value' => false,
                            ],


                        ],
                    ],

                    'phone' => [
                        'label' => 'Phone',
                        'icon' => 'uil uil-phone',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'tel',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'phone',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Phone',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'phone2' => [
                        'label' => 'Phone 2',
                        'icon' => 'uil uil-phone',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'tel',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'phone2',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Phone 2',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'fax' => [
                        'label' => 'Fax',
                        'icon' => 'uil uil-print',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'number',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'fax',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Fax',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'email' => [
                        'label' => 'Email',
                        'icon' => 'uil uil-envelope',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'email',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'email',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Email',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist'),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'website' => [
                        'label' => 'Website',
                        'icon' => 'uil uil-globe',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'text',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'website',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Website',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'social_info' => [
                        'label' => 'Social Info',
                        'icon' => 'uil uil-user-arrows',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'add_new',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'social',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Social Info',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'image_upload' => [
                        'label' => 'Images',
                        'icon' => 'uil uil-image',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'media',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'listing_img',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'hidden',
                                'value' => 'Images',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'select_files_label' => [
                                'type'  => 'text',
                                'label' => __( 'Select Files Label', 'directorist' ),
                                'value' => 'Select Files',
                            ],
                            'max_image_limit' => [
                                'type'  => 'number',
                                'label' => __( 'Max Image Limit', 'directorist' ),
                                'value' => 5,
                            ],
                            'max_per_image_limit' => [
                                'type'  => 'number',
                                'label' => __( 'Max Upload Size Per Image in MB', 'directorist' ),
                                'description' => __( 'Here 0 means unlimited.', 'directorist' ) ,
                                'value' => 0,
                            ],
                            'max_total_image_limit' => [
                                'type'  => 'number',
                                'label' => __( 'Total Upload Size in MB', 'directorist' ),
                                'value' => 2,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'video' => [
                        'label' => 'Video',
                        'icon' => 'uil uil-video',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'text',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'videourl',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Video',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'Only YouTube & Vimeo URLs.',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],


                        ],
                    ],

                    'hide_contact_owner' => [
                        'label' => 'Hiding Contact Owner Form',
                        'icon' => 'uil uil-postcard',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'checkbox',
                            ],
                            'field_key' => [
                                'type'   => 'hidden',
                                'value'  => 'hide_contact_owner',
                                'rules' => [
                                    'unique' => true,
                                    'required' => true,
                                ]
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Hide contact owner form for single listing page',
                            ],
                        ],
                    ],
                ]),
            ],

            'custom' => [
                'title' => __( 'Custom Fields', 'directorist' ),
                'description' => __( 'Click on a field type you want to create. Need help?', 'directorist' ),
                'allowMultiple' => true,
                'widgets' => apply_filters('atbdp_form_custom_widgets', [
                    'text' => [
                        'label' => 'Text',
                        'icon' => 'uil uil-text',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'text',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Text',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-text',
                            ]),
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),


                        ]

                    ],

                    'textarea' => [
                        'label' => 'Textarea',
                        'icon' => 'uil uil-text-fields',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'textarea',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Textarea',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-textarea',
                            ]),
                            'rows' => [
                                'type'  => 'number',
                                'label' => __( 'Rows', 'directorist' ),
                                'value' => 8,
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),


                        ]

                    ],

                    'number' => [
                        'label' => 'Number',
                        'icon' => 'uil uil-0-plus',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'number',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Number',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-number',
                            ]),
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),


                        ]

                    ],

                    'url' => [
                        'label' => 'URL',
                        'icon' => 'uil uil-link-add',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'text',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'URL',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-url',
                            ]),
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'target' => [
                                'type'  => 'toggle',
                                'label' => __( 'Open in new tab', 'directorist' ),
                                'value' => '',
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),


                        ]

                    ],

                    'date' => [
                        'label' => 'Date',
                        'icon' => 'uil uil-calender',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'date',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Date',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-date',
                            ]),
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),


                        ]

                    ],

                    'time' => [
                        'label' => 'Time',
                        'icon' => 'uil uil-clock',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'time',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Time',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-time',
                            ]),
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => '',
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),


                        ]

                    ],

                    'color_picker' => [
                        'label' => 'Color',
                        'icon' => 'uil uil-palette',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'color',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Color',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-color-picker',
                            ]),
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),


                        ]

                    ],

                    'select' => [
                        'label' => 'Select',
                        'icon' => 'uil uil-file-check',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'select',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Select',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-select',
                            ]),
                            'options' => [
                                'type' => 'multi-fields',
                                'label' => __('Options', 'directorist'),
                                'add-new-button-label' => __( 'Add Option', 'directorist' ),
                                'options' => [
                                    'option_value' => [
                                        'type'  => 'text',
                                        'label' => __( 'Option Value', 'directorist' ),
                                        'value' => '',
                                    ],
                                    'option_label' => [
                                        'type'  => 'text',
                                        'label' => __( 'Option Label', 'directorist' ),
                                        'value' => '',
                                    ],
                                ]
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),


                        ]

                    ],

                    'checkbox' => [
                        'label' => 'Checkbox',
                        'icon' => 'uil uil-check-square',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'checkbox',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Checkbox',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-checkbox',
                            ]),
                            'options' => [
                                'type' => 'multi-fields',
                                'label' => __('Options', 'directorist'),
                                'add-new-button-label' => __( 'Add Option', 'directorist' ),
                                'options' => [
                                    'option_value' => [
                                        'type'  => 'text',
                                        'label' => __( 'Option Value', 'directorist' ),
                                        'value' => '',
                                    ],
                                    'option_label' => [
                                        'type'  => 'text',
                                        'label' => __( 'Option Label', 'directorist' ),
                                        'value' => '',
                                    ],
                                ]
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),

                        ]

                    ],

                    'radio' => [
                        'label' => 'Radio',
                        'icon' => 'uil uil-circle',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'radio',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Radio',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-radio',
                            ]),
                            'options' => [
                                'type' => 'multi-fields',
                                'label' => __('Options', 'directorist'),
                                'add-new-button-label' => __( 'Add Option', 'directorist' ),
                                'options' => [
                                    'option_value' => [
                                        'type'  => 'text',
                                        'label' => __( 'Option Value', 'directorist' ),
                                        'value' => '',
                                    ],
                                    'option_label' => [
                                        'type'  => 'text',
                                        'label' => __( 'Option Label', 'directorist' ),
                                        'value' => '',
                                    ],
                                ]
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                            'assign_to' => $this->get_assign_to_field(),
                            'category' => $this->get_category_select_field([
                                'show_if' => [
                                    'where' => "self.assign_to",
                                    'conditions' => [
                                        ['key' => 'value', 'compare' => '=', 'value' => 'category'],
                                    ],
                                ],
                            ]),
                        ]

                    ],

                    'file' => [
                        'label' => __( 'File Upload', 'directorist' ),
                        'icon' => 'uil uil-file-upload-alt',
                        'options' => [
                            'type' => [
                                'type'  => 'hidden',
                                'value' => 'file',
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'File Upload',
                            ],
                            'field_key' => array_merge( $custom_field_meta_key_field, [
                                'value' => 'custom-file',
                            ]),
                            'file_type' => [
                                'type'  => 'select',
                                'label' => __( 'Chose a file type', 'directorist' ),
                                'value' => '',
                                'options' => [
                                    [
                                        'label' => __('All Types', 'directorist'),
                                        'value' => 'all_types',
                                    ],
                                    // Image Format
                                    [
                                        'label' => __('jpg', 'directorist'),
                                        'value' => 'jpg',
                                    ],
                                    [
                                        'label' => __('jpeg', 'directorist'),
                                        'value' => 'jpeg',
                                    ],
                                    [
                                        'label' => __('gif', 'directorist'),
                                        'value' => 'gif',
                                    ],
                                    [
                                        'label' => __('png', 'directorist'),
                                        'value' => 'png',
                                    ],
                                    [
                                        'label' => __('bmp', 'directorist'),
                                        'value' => 'bmp',
                                    ],
                                    [
                                        'label' => __('ico', 'directorist'),
                                        'value' => 'ico',
                                    ],
                                    
                                    // Video Format
                                    [
                                        'label' => __('asf', 'directorist'),
                                        'value' => 'asf',
                                    ],
                                    [
                                        'label' => __('flv', 'directorist'),
                                        'value' => 'flv',
                                    ],
                                    [
                                        'label' => __('avi', 'directorist'),
                                        'value' => 'avi',
                                    ],
                                    [
                                        'label' => __('mkv', 'directorist'),
                                        'value' => 'mkv',
                                    ],
                                    [
                                        'label' => __('mp4', 'directorist'),
                                        'value' => 'mp4',
                                    ],
                                    [
                                        'label' => __('mpeg', 'directorist'),
                                        'value' => 'mpeg',
                                    ],
                                    [
                                        'label' => __('mpg', 'directorist'),
                                        'value' => 'mpg',
                                    ],
                                    [
                                        'label' => __('wmv', 'directorist'),
                                        'value' => 'wmv',
                                    ],
                                    [
                                        'label' => __('3gp', 'directorist'),
                                        'value' => '3gp',
                                    ],
                                    
                                    // Audio Format
                                    [
                                        'label' => __('ogg', 'directorist'),
                                        'value' => 'ogg',
                                    ],
                                    [
                                        'label' => __('mp3', 'directorist'),
                                        'value' => 'mp3',
                                    ],
                                    [
                                        'label' => __('wav', 'directorist'),
                                        'value' => 'wav',
                                    ],
                                    [
                                        'label' => __('wma', 'directorist'),
                                        'value' => 'wma',
                                    ],
                                    
                                    // Text Format
                                    [
                                        'label' => __('css', 'directorist'),
                                        'value' => 'css',
                                    ],
                                    [
                                        'label' => __('csv', 'directorist'),
                                        'value' => 'csv',
                                    ],
                                    [
                                        'label' => __('htm', 'directorist'),
                                        'value' => 'htm',
                                    ],
                                    [
                                        'label' => __('html', 'directorist'),
                                        'value' => 'html',
                                    ],
                                    [
                                        'label' => __('txt', 'directorist'),
                                        'value' => 'txt',
                                    ],
                                    [
                                        'label' => __('rtx', 'directorist'),
                                        'value' => 'rtx',
                                    ],
                                    [
                                        'label' => __('vtt', 'directorist'),
                                        'value' => 'vtt',
                                    ],

                                    // Application Format
                                    [
                                        'label' => __('doc', 'directorist'),
                                        'value' => 'doc',
                                    ],
                                    [
                                        'label' => __('docx', 'directorist'),
                                        'value' => 'docx',
                                    ],
                                    [
                                        'label' => __('odt', 'directorist'),
                                        'value' => 'odt',
                                    ],
                                    [
                                        'label' => __('pdf', 'directorist'),
                                        'value' => 'pdf',
                                    ],
                                    [
                                        'label' => __('pot', 'directorist'),
                                        'value' => 'pot',
                                    ],
                                    [
                                        'label' => __('ppt', 'directorist'),
                                        'value' => 'ppt',
                                    ],
                                    [
                                        'label' => __('pptx', 'directorist'),
                                        'value' => 'pptx',
                                    ],
                                    [
                                        'label' => __('rar', 'directorist'),
                                        'value' => 'rar',
                                    ],
                                    [
                                        'label' => __('rtf', 'directorist'),
                                        'value' => 'rtf',
                                    ],
                                    [
                                        'label' => __('swf', 'directorist'),
                                        'value' => 'swf',
                                    ],
                                    [
                                        'label' => __('xls', 'directorist'),
                                        'value' => 'xls',
                                    ],
                                    [
                                        'label' => __('xlsx', 'directorist'),
                                        'value' => 'xlsx',
                                    ],
                                    [
                                        'label' => __('gpx', 'directorist'),
                                        'value' => 'gpx',
                                    ],

                                ],
                            ],
                            'file_size' => [
                                'type'  => 'text',
                                'label' => __( 'File Size', 'directorist' ),
                                'description' => __('Set maximum file size to upload', 'directorist'),
                                'value' => '2mb',
                            ],
                            'description' => [
                                'type'  => 'text',
                                'label' => __( 'Description', 'directorist' ),
                                'value' => '',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'only_for_admin' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Only For Admin Use', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],
                ])

            ],
        ];

        $single_listings_contents_widgets = [
            'preset_widgets' => [
                'title' => __( 'Preset Fields', 'directorist' ),
                'description' => __( 'Click on a field to use it', 'directorist' ),
                'allowMultiple' => false,
                'template' => 'submission_form_fields',
                'widgets' => apply_filters( 'atbdp_single_listing_content_widgets', [
                    'tag' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-tag',
                            ],
                        ]
                    ],
                    'address'          => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-map',
                            ],
                            'address_link_with_map' => [
                                'type'  => 'toggle',
                                'label' => __( 'Address Linked with Map', 'directorist' ),
                                'value' => false,
                            ],
                        ]
                    ],
                    'map' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-map',
                            ],
                        ]
                    ],
                    'zip' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-street-view',
                            ],
                        ]
                    ],
                    'phone' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-phone',
                            ],
                        ]
                    ],
                    'phone2' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-phone',
                            ],
                        ]
                    ],
                    'fax' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-fax',
                            ],
                        ]
                    ],
                    'email' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-envelope',
                            ],
                        ]
                    ],
                    'website' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-globe',
                            ],
                            'use_nofollow' => [
                                'type'  => 'toggle',
                                'label' => __( 'Use rel="nofollow" in Website Link', 'directorist' ),
                                'value' => false,
                            ],
                        ]
                    ],
                    'social_info' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-share-alt',
                            ],
                        ]
                    ],
                    'video' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-video',
                            ],
                        ]
                    ],
                    'text' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-text-height',
                            ],
                        ]
                    ],
                    'textarea' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-align-center',
                            ],
                        ]
                    ],
                    'number' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-list-ol',
                            ],
                        ]
                    ],
                    'url' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-link',
                            ],
                        ]
                    ],
                    'date' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-calendar',
                            ],
                        ]
                    ],
                    'time' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-clock-o',
                            ],
                        ]
                    ],
                    'color_picker' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-palette',
                            ],
                        ]
                    ],
                    'select' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-clipboard-check',
                            ],
                        ]
                    ],
                    'checkbox' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-check-square',
                            ],
                        ]
                    ],
                    'radio' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-circle',
                            ],
                        ]
                    ],
                    'file' => [
                        'options' => [
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-file-alt',
                            ],
                        ]
                    ],
                ] ),
            ],
            'other_widgets' => [
                'title' => __( 'Other Fields', 'directorist' ),
                'description' => __( 'Click on a field to use it', 'directorist' ),
                'allowMultiple' => false,
                'widgets' => apply_filters( 'atbdp_single_listing_other_fields_widget', [
                    'custom_content' => [ 
                        'type' => 'widget',
                        'label' => __( 'Custom Content', 'directorist' ),
                        'icon' => 'la la-align-right',
                        'allowMultiple' => true,
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => '',
                            ],
                            'icon' => [
                                'type'  => 'icon',
                                'label'  => __( 'Icon', 'directorist' ),
                                'value' => '',
                            ],
                            'content' => [
                                'type'  => 'textarea',
                                'label'  => __( 'Content', 'directorist' ),
                                'value' => '',
                                'description' => __( 'You can use any text or shortcode', 'directorist' ),
                            ],
                        ],
                    ],
                    'review' => [ 
                        'type' => 'section',
                        'label' => __( 'Review', 'directorist' ),
                        'icon' => 'la la-star',
                        'options' => [
                            'custom_block_id' => [
                                'type'  => 'text',
                                'label'  => __( 'Custom block ID', 'directorist' ),
                                'value' => '',
                            ],
                            'custom_block_classes' => [
                                'type'  => 'text',
                                'label'  => __( 'Custom block Classes', 'directorist' ),
                                'value' => '',
                            ],
                        ],
                    ],
                    'author_info' => [
                        'type' => 'section',
                        'label' => __( 'Author Info', 'directorist' ),
                        'icon' => 'la la-user',
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Author Info',
                            ],
                            'custom_block_id' => [
                                'type'  => 'text',
                                'label'  => __( 'Custom block ID', 'directorist' ),
                                'value' => '',
                            ],
                            'custom_block_classes' => [
                                'type'  => 'text',
                                'label'  => __( 'Custom block Classes', 'directorist' ),
                                'value' => '',
                            ],
                        ]
                    ],
                    'contact_listings_owner' => [
                        'type' => 'section',
                        'label' => __( 'Contact Listings Owner Form', 'directorist' ),
                        'icon' => 'la la-phone',
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Contact Listings Owner Form',
                            ],
                            'icon' => [
                                'type'  => 'icon',
                                'label' => __( 'Icon', 'directorist' ),
                                'value' => 'la la-phone',
                            ],
                            'custom_block_id' => [
                                'type'  => 'text',
                                'label'  => __( 'Custom block ID', 'directorist' ),
                                'value' => '',
                            ],
                            'custom_block_classes' => [
                                'type'  => 'text',
                                'label'  => __( 'Custom block Classes', 'directorist' ),
                                'value' => '',
                            ],
                        ]
                    ],
                    'related_listings' => [
                        'type' => 'section',
                        'label' => __( 'Related Listings', 'directorist' ),
                        'icon' => 'la la-copy',
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Related Listings',
                            ],
                            'custom_block_id' => [
                                'type'  => 'text',
                                'label'  => __( 'Custom block ID', 'directorist' ),
                                'value' => '',
                            ],
                            'custom_block_classes' => [
                                'type'  => 'text',
                                'label'  => __( 'Custom block Classes', 'directorist' ),
                                'value' => '',
                            ],
                        ]
                    ],
                ] ),
            ],
        ];

        $search_form_widgets = apply_filters( 'directorist_search_form_widgets', [
            'available_widgets' => [
                'title' => __( 'Preset Fields', 'directorist' ),
                'description' => __( 'Click on a field to use it', 'directorist' ),
                'allowMultiple' => false,
                'template' => 'submission_form_fields',
                'widgets' => [
                    'title' => [
                        'label' => __( 'Search Bar', 'directorist' ),
                        'options' => [
                            'required' => [
                                'type'  => 'toggle',
                                'label' => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => '',
                                'sync' => false,
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'What are you looking for?',
                            ],
                        ],
                    ],

                    'category' => [
                        'options' => [
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => '',
                                'sync' => false,
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label'  => __( 'Placeholder', 'directorist' ),
                                'value' => 'Category',
                            ],
                        ]
                    ],

                    'location' => [
                        'options' => [
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => '',
                                'sync' => false,
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label'  => __( 'Placeholder', 'directorist' ),
                                'value' => 'Location',
                            ],
                            'location_source' => [
                                'type'  => 'select',
                                'label'  => __( 'Location Source', 'directorist' ),
                                'options' => [
                                    [
                                        'label' => __('Display from Listing Location', 'directorist'),
                                        'value' => 'from_listing_location',
                                    ],
                                    [
                                        'label' => __('Display from Map API', 'directorist'),
                                        'value' => 'from_map_api',
                                    ],
                                ],
                                'value' => 'from_map_api',
                            ],
                        ]
                    ],

                    'tag' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'tags_filter_source' => [
                                'type'  => 'select',
                                'label' => __( 'Tags Filter Source', 'directorist' ),
                                'options' => [
                                    [
                                        'label' => __('All Tags', 'directorist'),
                                        'value' => 'all_tags',
                                    ],
                                    [
                                        'label' => __('Category Based Tags', 'directorist'),
                                        'value' => 'category_based_tags',
                                    ],
                                ],
                                'value' => 'all_tags',
                            ],
                        ]
                    ],

                    'pricing' => [
                        'options' => [
                            'price_range_min_placeholder' => [
                                'type'  => 'text',
                                'label'  => __( 'Price Range Min Placeholder', 'directorist' ),
                                'value' => 'Min',
                            ],
                            'price_range_max_placeholder' => [
                                'type'  => 'text',
                                'label'  => __( 'Price Range Max Placeholder', 'directorist' ),
                                'value' => 'Max',
                            ],
                        ]
                    ],

                    /* 'address' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => 'Label',
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label'  => 'Placeholder',
                                'value' => 'Address',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => 'Required',
                                'value' => false,
                            ],
                        ]
                    ], */

                    'zip' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label'  => __( 'Placeholder', 'directorist' ),
                                'value' => 'Zip',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]
                    ],

                    'phone' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'Phone',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]
                    ],

                    'phone2' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'Phone 2',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label' => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]
                    ],

                    'email' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'Email',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]
                    ],

                    'fax' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Fax',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'Fax',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]
                    ],

                    'website' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label'  => __( 'Placeholder', 'directorist' ),
                                'value' => 'Website',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]
                    ],
                    
                    'text' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'Text',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],
                    'number' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'Number',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],

                    'url' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label'  => __( 'Placeholder', 'directorist' ),
                                'value' => 'URL',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],

                    'date' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label'  => __( 'Placeholder', 'directorist' ),
                                'value' => 'Date',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],

                    'time' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label' => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'placeholder' => [
                                'type'  => 'text',
                                'label' => __( 'Placeholder', 'directorist' ),
                                'value' => 'Time',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label' => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],

                    'color_picker' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],

                    'select' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],

                    'checkbox' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],

                    'radio' => [
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Tag',
                            ],
                            'required' => [
                                'type'  => 'toggle',
                                'label'  => __( 'Required', 'directorist' ),
                                'value' => false,
                            ],
                        ]

                    ],
                    
                ],
            ],
            'other_widgets' => [
                'title' => __( 'Other Fields', 'directorist' ),
                'description' => __( 'Click on a field to use it', 'directorist' ),
                'allowMultiple' => false,
                'widgets' => [
                    'review' => [
                        'label' => 'Review',
                        'icon' => 'fa fa-star',
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Review',
                            ],
                        ],
                    ],
                    'radius_search' => [
                        'label' => __( 'Radius Search', 'directorist' ),
                        'icon' => 'fa fa-map',
                        'options' => [
                            'label' => [
                                'type'  => 'text',
                                'label'  => __( 'Label', 'directorist' ),
                                'value' => 'Radius Search',
                            ],
                            'default_radius_distance' => [
                                'type'  => 'range',
                                'label' => __( 'Default Radius Distance', 'directorist' ),
                                'min'   => 0,
                                'max'   => 750,
                                'value' => 0,
                            ],
                            'radius_search_unit' => [
                                'type'  => 'select',
                                'label' => __( 'Radius Search Unit', 'directorist' ),
                                'value' => 'miles',
                                'options' => [
                                    [ 'value' => 'miles', 'label' => 'Miles' ],
                                    [ 'value' => 'kilometers', 'label' => 'Kilometers' ],
                                ]
                            ],
                        ],
                    ],
                ]
            ]
        ] );

        $listing_card_widget = apply_filters( 'directorist_listing_card_widgets', [
            'listing_title' => [
                'type' => "title",
                'label' => __( "Listing Title", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_listing_title",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'title'],
                    ],
                ],
                'options' => [
                    'title' => __( "Listing Title Settings", "directorist" ),
                    'fields' => [
                        'show_tagline' => [
                            'type' => "toggle",
                            'label' => __( "Show Tagline", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'excerpt' => [
                'type' => "excerpt",
                'label' => __( "Excerpt", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_listing_excerpt",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'excerpt'],
                    ],
                ],
                'options' => [
                    'title' => __( "Excerpt Settings", "directorist" ),
                    'fields' => [
                        'words_limit' => [
                            'type' => "range",
                            'label' => __( "Words Limit", "directorist" ),
                            'min' => 5,
                            'max' => 200,
                            'value' => 20,
                        ],
                        'show_readmore' => [
                            'type' => "toggle",
                            'label' => __( "Show Readmore", "directorist" ),
                            'value' => true,
                        ],
                        'show_readmore_text' => [
                            'type' => "text",
                            'label' => __( "Read More Text", "directorist" ),
                            'value' => 'Read More',
                        ],
                    ],
                ],
            ],

            'listings_location' => [
                'type' => "list-item",
                'label' => __( "Listings Location", "directorist" ),
                'icon' => 'uil uil-location-point',
                'hook' => "atbdp_listings_location",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'location'],
                    ],
                ],
                'options' => [
                    'title' => __( "Listings Location Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-map-marker",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],
            
            'posted_date' => [
                'type' => "list-item",
                'label' => __( "Posted Date", "directorist" ),
                'icon' => 'la la-clock-o',
                'hook' => "atbdp_listings_posted_date",
                'options' => [
                    'title' => __( "Posted Date", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-clock-o",
                        ],
                        'date_type' => [
                            'type' => "radio",
                            'label' => __( "Date Type", "directorist" ),
                            'options' => [
                                [ 'id' => 'atbdp_days_ago', 'label' => 'Days Ago', 'value' => 'days_ago' ],
                                [ 'id' => 'atbdp_posted_date', 'label' => 'Posted Date', 'value' => 'post_date' ],
                            ],
                            'value' => "post_date",
                        ],
                    ],
                ],
            ],

            'website' => [
                'type' => "list-item",
                'label' => __( "Listings Website", "directorist" ),
                'icon' => 'la la-globe',
                'hook' => "atbdp_listings_website",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'website'],
                    ],
                ],
                'options' => [
                    'title' => __( "Listings Website Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-globe",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'zip' => [
                'type' => "list-item",
                'label' => __( "Listings Zip", "directorist" ),
                'icon' => 'la la-at',
                'hook' => "atbdp_listings_zip",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'zip'],
                    ],
                ],
                'options' => [
                    'title' => __( "Listings Zip Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-at",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'email' => [
                'type' => "list-item",
                'label' => __( "Listings Email", "directorist" ),
                'icon' => 'la la-envelope',
                'hook' => "atbdp_listings_email",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'email'],
                    ],
                ],
                'options' => [
                    'title' => __( "Listings Email Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-envelope",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'fax' => [
                'type' => "list-item",
                'label' => __( "Listings Fax", "directorist" ),
                'icon' => 'la la-fax',
                'hook' => "atbdp_listings_fax",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'fax'],
                    ],
                ],
                'options' => [
                    'title' => __( "Listings Fax Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-fax",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'phone' => [
                'type' => "list-item",
                'label' => __( "Listings Phone", "directorist" ),
                'icon' => 'la la-phone',
                'hook' => "atbdp_listings_phone",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'phone'],
                    ],
                ],
                'options' => [
                    'title' => __( "Listings Phone Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-phone",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'phone2' => [
                'type' => "list-item",
                'label' => __( "Listings Phone 2", "directorist" ),
                'icon' => 'la la-phone',
                'hook' => "atbdp_listings_phone2",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'phone2'],
                    ],
                ],
                'options' => [
                    'title' => __( "Listings Phone 2 Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-phone",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'address' => [
                'type' => "list-item",
                'label' => __( "Listings Address", "directorist" ),
                'icon' => 'la la-map-marker',
                'hook' => "atbdp_listings_map_address",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'address'],
                    ],
                ],
                'options' => [
                    'title' => __( "Listings Address Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-map-marker",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'pricing' => [
                'type' => "price",
                'label' => __( "Listings Price", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_single_listings_price",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'pricing'],
                    ],
                ],
            ],

            'rating' => [
                'type' => "rating",
                'label' => __( "Rating", "directorist" ),
                'hook' => "atbdp_listings_rating",
                'icon' => 'uil uil-text-fields',
            ],

            'featured_badge' => [
                'type' => "badge",
                'label' => __( "Featured", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_featured_badge",
            ],

            'new_badge' => [
                'type' => "badge",
                'label' => __( "New", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_new_badge",
            ],

            'popular_badge' => [
                'type' => "badge",
                'label' => __( "Popular", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_popular_badge",
            ],

            'favorite_badge' => [
                'type' => "icon",
                'label' => __( "Favorite", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_favorite_badge",
            ],

            'view_count' => [
                'type' => "view-count",
                'label' => __( "View Count", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_view_count",
                'options' => [
                    'title' => __( "View Count Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "fa fa-heart",
                        ],
                    ],
                ],
            ],

            'category' => [
                'type' => "category",
                'label' => __( "Category", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_category",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'category'],
                    ],
                ],
                'options' => [
                    'title' => __( "Category Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "fa fa-folder",
                        ],
                    ],
                ],
            ],

            'user_avatar' => [
                'type' => "avatar",
                'label' => __( "User Avatar", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_user_avatar",
                'can_move' => false,
                'options' => [
                    'title' => __( "User Avatar Settings", "directorist" ),
                    'fields' => [
                        'align' => [
                            'type' => "radio",
                            'label' => __( "Align", "directorist" ),
                            'value' => "center",
                            'options' => [
                                [ 'id' => 'atbdp_user_avatar_align_right', 'label' => __( 'Right', 'directorist' ), 'value' => 'right' ],
                                [ 'id' => 'atbdp_user_avatar_align_center', 'label' => __( 'Center', 'directorist' ), 'value' => 'center' ],
                                [ 'id' => 'atbdp_user_avatar_align_left', 'label' => __( 'Left', 'directorist' ), 'value' => 'left' ],
                            ],
                        ],
                    ],
                ],
            ],
            
            // Custom Fields
            'text' => [
                'type' => "list-item",
                'label' => __( "Text", "directorist" ),
                'icon' => 'uil uil-text-fields',
                'hook' => "atbdp_custom_text",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'text'],
                    ],
                ],
                'options' => [
                    'title' => __( "Text Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "uil uil-text-fields",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],
            
            'number' => [
                'type' => "list-item",
                'label' => __( "Number", "directorist" ),
                'icon' => 'uil uil-0-plus',
                'hook' => "atbdp_custom_number",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'number'],
                    ],
                ],
                'options' => [
                    'title' => "Number Settings",
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "uil uil-0-plus",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'url' => [
                'type' => "list-item",
                'label' => __( "URL", "directorist" ),
                'icon' => 'uil uil-link-add',
                'hook' => "atbdp_custom_url",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'url'],
                    ],
                ],
                'options' => [
                    'title' => __( "URL Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "uil uil-link-add",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'date' => [
                'type' => "list-item",
                'label' => __( "Date", "directorist" ),
                'icon' => 'la la-calendar-check-o',
                'hook' => "atbdp_custom_date",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'date'],
                    ],
                ],
                'options' => [
                    'title' => __( "Date Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "la la-calendar-check-o",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'time' => [
                'type' => "list-item",
                'label' => __( "Time", "directorist" ),
                'icon' => 'uil uil-clock',
                'hook' => "atbdp_custom_time",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'time'],
                    ],
                ],
                'options' => [
                    'title' => __( "Time Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "uil uil-clock",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'color_picker' => [
                'type' => "list-item",
                'label' => __( "Color Picker", "directorist" ),
                'icon' => 'uil uil-palette',
                'hook' => "atbdp_custom_color",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'color'],
                    ],
                ],
                'options' => [
                    'title' => __( "Color Picker Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "uil uil-palette",
                        ],
                    ],
                ],
            ],

            'select' => [
                'type' => "list-item",
                'label' => __( "Select", "directorist" ),
                'icon' => 'uil uil-file-check',
                'hook' => "atbdp_custom_select",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'select'],
                    ],
                ],
                'options' => [
                    'title' => __( "Select Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "uil uil-file-check",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'checkbox' => [
                'type' => "list-item",
                'label' => __( "Checkbox", "directorist" ),
                'icon' => 'uil uil-check-square',
                'hook' => "atbdp_custom_checkbox",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'checkbox'],
                    ],
                ],
                'options' => [
                    'title' => "Checkbox Settings",
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "uil uil-check-square",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],

            'radio' => [
                'type' => "list-item",
                'label' => __( "Radio", "directorist" ),
                'icon' => 'uil uil-circle',
                'hook' => "atbdp_custom_radio",
                'show_if' => [
                    'where' => "submission_form_fields.value.fields",
                    'conditions' => [
                        ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'radio'],
                    ],
                ],
                'options' => [
                    'title' => __( "Radio Settings", "directorist" ),
                    'fields' => [
                        'icon' => [
                            'type' => "icon",
                            'label' => __( "Icon", "directorist" ),
                            'value' => "uil uil-circle",
                        ],
                        'show_label' => [
                            'type' => "toggle",
                            'label' => __( "Show Label", "directorist" ),
                            'value' => false,
                        ],
                    ],
                ],
            ],
        ] );


        $listing_card_list_view_widget = $listing_card_widget;
        if ( ! empty( $listing_card_list_view_widget[ 'user_avatar' ] ) ) {
            $listing_card_list_view_widget[ 'user_avatar' ][ 'can_move' ] = true;

            if ( ! empty( $listing_card_list_view_widget[ 'user_avatar' ]['options'] ) ) {
                unset( $listing_card_list_view_widget[ 'user_avatar' ]['options'] );
            }
        }


        // Card Layouts
        $listing_card_grid_view_with_thumbnail_layout = [
            'thumbnail' => [
                'top_right' => [
                    'label' => __( 'Top Right', 'directorist' ),
                    'maxWidget' => 3,
                    'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                    'acceptedWidgets' => ["favorite_badge", "popular_badge", "featured_badge", "new_badge"],
                ],
                'top_left' => [
                    'maxWidget' => 3,
                    'acceptedWidgets' => ["favorite_badge", "popular_badge", "featured_badge", "new_badge"],
                ],
                'bottom_right' => [
                    'maxWidget' => 2,
                    'acceptedWidgets' => ["favorite_badge", "popular_badge", "featured_badge", "new_badge"],
                ],
                'bottom_left' => [
                    'maxWidget' => 3,
                    'acceptedWidgets' => ["favorite_badge", "popular_badge", "featured_badge", "new_badge"],
                ],
                'avatar' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => ["user_avatar"],
                ],
            ],

            'body' => [
                'top' => [
                    'maxWidget' => 0,
                    'acceptedWidgets' => [
                        "listing_title", "favorite_badge", "popular_badge", "featured_badge", "new_badge", "rating", "pricing",
                    ],
                ],
                'bottom' => [
                    'maxWidget' => 0,
                    'acceptedWidgets' => [
                        "listings_location", "phone", "phone2", "website", "zip", "fax", "address", "email",
                        'text', 'textarea', 'number', 'url', 'date', 'time', 'color', 'select', 'checkbox', 'radio', 'file', 'posted_date',
                    ],
                ],
                'excerpt' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => [ "excerpt" ],
                    'show_if' => [
                        'where' => "submission_form_fields.value.fields",
                        'conditions' => [
                            ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'excerpt'],
                        ],
                    ],
                ],
            ],

            'footer' => [
                'right' => [
                    'maxWidget' => 2,
                    'acceptedWidgets' => ["category", "favorite_badge", "view_count"],
                ],

                'left' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => ["category", "favorite_badge", "view_count"],
                ],
            ],
        ];

        $listing_card_grid_view_without_thumbnail_layout = [
            'body' => [
                'avatar' => [
                    'label' => __( 'Avatar', 'directorist' ),
                    'maxWidget' => 1,
                    'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                    'acceptedWidgets' => ["user_avatar"],
                ],
                'title' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => ["listing_title"],
                ],
                'quick_actions' => [
                    'maxWidget' => 2,
                    'acceptedWidgets' => ["favorite_badge"],
                ],
                'quick_info' => [
                    'acceptedWidgets' => ["favorite_badge", "popular_badge", "featured_badge", "new_badge", "rating", "pricing"],
                ],
                'bottom' => [
                    'maxWidget' => 0,
                    'acceptedWidgets' => [
                        "listings_location", "phone", "phone2", "website", "zip", "fax", "address", "email",
                        'text', 'textarea', 'number', 'url', 'date', 'time', 'color', 'select', 'checkbox', 'radio', 'file', 'posted_date',
                    ],
                ],
                'excerpt' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => [ "excerpt" ],
                    'show_if' => [
                        'where' => "submission_form_fields.value.fields",
                        'conditions' => [
                            ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'excerpt'],
                        ],
                    ],
                ],
            ],

            'footer' => [
                'right' => [
                    'maxWidget' => 2,
                    'acceptedWidgets' => ["category", "favorite_badge", "view_count"],
                ],

                'left' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => ["category", "favorite_badge", "view_count"],
                ],
            ],
        ];

        $listing_card_list_view_with_thumbnail_layout = [
            'thumbnail' => [
                'top_right' => [
                    'label' => __( 'Top Right', 'directorist' ),
                    'maxWidget' => 3,
                    'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                    'acceptedWidgets' => ["favorite_badge", "popular_badge", "featured_badge", "new_badge"],
                ],
            ],

            'body' => [
                'top' => [
                    'label' => __( 'Body Top', 'directorist' ),
                    'maxWidget' => 0,
                    'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                    'acceptedWidgets' => ["listing_title", "favorite_badge", "popular_badge", "featured_badge", "new_badge",  "rating", "pricing",],
                ],
                'right' => [
                    'label' => __( 'Body Right', 'directorist' ),
                    'maxWidget' => 2,
                    'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                    'acceptedWidgets' => ["favorite_badge", "popular_badge", "featured_badge", "new_badge"],
                ],
                'bottom' => [
                    'label' => __( 'Body Bottom', 'directorist' ),
                    'maxWidget' => 0,
                    'acceptedWidgets' => [
                        "listings_location", "phone", "phone2", "website", "zip", "fax", "address", "email",
                        'text', 'textarea', 'number', 'url', 'date', 'time', 'color', 'select', 'checkbox', 'radio', 'file', 'posted_date'
                    ],
                ],
                'excerpt' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => [ "excerpt" ],
                    'show_if' => [
                        'where' => "submission_form_fields.value.fields",
                        'conditions' => [
                            ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'excerpt'],
                        ],
                    ],
                ],
            ],

            'footer' => [
                'right' => [
                    'maxWidget' => 2,
                    'acceptedWidgets' => ["user_avatar", "category", "favorite_badge", "view_count"],
                ],

                'left' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => ["category", "favorite_badge", "view_count"],
                ],
            ],
        ];

        $listing_card_list_view_without_thumbnail_layout = [
            'body' => [
                'top' => [
                    'label' => __( 'Body Top', 'directorist' ),
                    'maxWidget' => 0,
                    'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                    'acceptedWidgets' => ["listing_title", "favorite_badge", "popular_badge", "featured_badge", "new_badge",  "rating", "pricing",],
                ],
                'right' => [
                    'label' => __( 'Body Right', 'directorist' ),
                    'maxWidget' => 2,
                    'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                    'acceptedWidgets' => ["favorite_badge", "popular_badge", "featured_badge", "new_badge"],
                ],
                'bottom' => [
                    'label' => __( 'Body Bottom', 'directorist' ),
                    'maxWidget' => 0,
                    'acceptedWidgets' => [
                        "listings_location", "phone", "phone2", "website", "zip", "fax", "address", "email",
                        'text', 'textarea', 'number', 'url', 'date', 'time', 'color', 'select', 'checkbox', 'radio', 'file', 'posted_date'
                    ],
                ],
                'excerpt' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => [ "excerpt" ],
                    'show_if' => [
                        'where' => "submission_form_fields.value.fields",
                        'conditions' => [
                            ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'excerpt'],
                        ],
                    ],
                ],
            ],

            'footer' => [
                'right' => [
                    'maxWidget' => 2,
                    'acceptedWidgets' => ["user_avatar", "category", "favorite_badge", "view_count"],
                ],

                'left' => [
                    'maxWidget' => 1,
                    'acceptedWidgets' => ["category", "favorite_badge", "view_count"],
                ],
            ],
        ];

        self::$fields = apply_filters('atbdp_listing_type_settings_field_list', [
            'icon' => [
                'label' => '',
                'type'  => 'icon',
                'value' => '',
                'placeholder' => __('fa fa-home', 'directorist'),
                'rules' => [
                    'required' => false,
                ],
            ],
            'preview_image' => [
                'button-label' => __('Select', 'directorist'),
                'type'         => 'wp-media-picker',
                'default-img'  => '',
                'value'        => '',
            ],

            'import_export' => [
                'button-label'     => __( 'Export', 'directorist' ),
                'export-file-name' => 'directory',
                'type'             => 'export',
            ],

            'default_expiration' => [
                'label' => __('Default expiration in days', 'directorist'),
                'type'  => 'number',
                'value' => 30,
                'placeholder' => '365',
                'rules' => [
                    'required' => true,
                ],
            ],

            'new_listing_status' => [
                'label' => __('New Listing Default Status', 'directorist'),
                'type'  => 'select',
                'value' => 'pending',
                'options' => [
                    [
                        'label' => __('Pending', 'directorist'),
                        'value' => 'pending',
                    ],
                    [
                        'label' => __('Publish', 'directorist'),
                        'value' => 'publish',
                    ],
                ],
            ],

            'edit_listing_status' => [
                'label' => __('Edited Listing Default Status', 'directorist'),
                'type'  => 'select',
                'value' => 'pending',
                'options' => [
                    [
                        'label' => __('Pending', 'directorist'),
                        'value' => 'pending',
                    ],
                    [
                        'label' => __('Publish', 'directorist'),
                        'value' => 'publish',
                    ],
                ],
            ],

            'global_listing_type' => [
                'label' => __('Global Listing Type', 'directorist'),
                'type'  => 'toggle',
                'value' => '',
            ],

            'submission_form_fields' => apply_filters( 'atbdp_listing_type_form_fields', [
                'type'    => 'form-builder',
                'widgets' => $form_field_widgets,
                'generalSettings' => [
                    'minGroup' => 1,
                    'addNewGroupButtonLabel' => __( 'Add Section', 'directorist' ),
                    'restricted_fields_warning_text' => __( 'You can not add in this section', 'directorist' ),
                ],
                'groupSettings' => [
                    'defaultGroupLabel' => 'Section',
                    'disableTrashIfGroupHasWidgets' => [ 
                        [ 'widget_name' => 'title', 'widget_group' => 'preset' ]
                    ],
                ],
                'groupFields' => [
                    'label' => [
                        'type'  => 'text',
                        'label' => 'Group Name',
                        'value' => 'Section',
                    ],
                ],
                'value' => [
                    'fields' => [
                        'title' => [
                            'widget_group' => 'preset',
                            'widget_name' => 'title',
                            'type'        => 'text',
                            'field_key'   => 'listing_title',
                            'required'    => true,
                            'label'       => 'Title',
                            'placeholder' => '',
                        ],
                    ],
                    'groups' => [
                        [
                            'label' => 'General Section',
                            'lock' => true,
                            'fields' => ['title'],
                            'plans' => []
                        ],
                    ]
                ],

            ] ),

            // Submission Settings
            'preview_mode' => [
                'label' => __('Enable Listing Preview', 'directorist'),
                'type'  => 'toggle',
                'value' => true,
            ],
            'submit_button_label' => [
                'label' => __('Submit Button Label', 'directorist'),
                'type'  => 'text',
                'value' => __('Save & Preview', 'directorist'),
            ],

            // Submit Button
            'submit_button_label' => [
                'label' => __('Submit Button Label', 'directorist'),
                'type'  => 'text',
                'value' => __('Save & Preview', 'directorist'),
            ],

            // TERMS AND CONDITIONS
            'listing_terms_condition' => [
                'label' => __('Enable', 'directorist'),
                'type'  => 'toggle',
                'value' => true,
            ],
            'require_terms_conditions' => [
                'label' => __('Required', 'directorist'),
                'type'  => 'toggle',
                'value' => true,
            ],
            'terms_label' => [
                'label'       => __('Label', 'directorist'),
                'type'        => 'text',
                'description' => __( 'Place the linking text between two <code>%</code> mark. Ex: %link% ', 'directorist' ),
                'value'       => 'I agree with all %terms & conditions%',
            ],

            // PRIVACY AND POLICY
            'listing_privacy' => [
                'label' => __('Enable', 'directorist'),
                'type'  => 'toggle',
                'value' => true,
            ],
            'require_privacy' => [
                'label' => __('Required', 'directorist'),
                'type'  => 'toggle',
                'value' => true,
            ],
            'privacy_label' => [
                'label' => __('Label', 'directorist'),
                'type'  => 'text',
                'description' => __( 'Place the linking text between two <code>%</code> mark. Ex: %link% ', 'directorist' ),
                'value' => 'I agree to the %Privacy & Policy%',
            ],
            
            'single_listings_contents' => [
                'type'     => 'form-builder',
                'widgets'  => $single_listings_contents_widgets,
                'generalSettings' => [
                    'addNewGroupButtonLabel' => __( 'Add Section', 'directorist' ),
                ],
                'groupFields' => [
                    'icon' => [
                        'type'  => 'icon',
                        'label'  => __( 'Block/Section Icon', 'directorist' ),
                        'value' => '',
                    ],
                    'label' => [
                        'type'  => 'text',
                        'label' => __( 'Label', 'directorist' ),
                        'value' => 'Section',
                    ],
                    'custom_block_id' => [
                        'type'  => 'text',
                        'label'  => __( 'Custom block ID', 'directorist' ),
                        'value' => '',
                    ],
                    'custom_block_classes' => [
                        'type'  => 'text',
                        'label'  => __( 'Custom block Classes', 'directorist' ),
                        'value' => '',
                    ],
                    'shortcode' => [
                        'type'  => 'shortcode',
                        'label' => __( 'Shortcode', 'directorist' ),
                        'value' => '[directorist_single_listings_section key="@@%%shortcode_key%%@@"]',
                        'filters' => [
                            [
                                'type'         => 'replace',
                                'find'         => '%%shortcode_key%%',
                                'replace_from' => 'self.label',
                            ],
                            [
                                'type'       => 'lowercase',
                                'find_regex' => '@@.+@@',
                            ],
                            [
                                'type'       => 'replace',
                                'look_for'   => '@@.+@@',
                                'find_regex' => '\\s',
                                'replace'    => '-',
                            ],
                            [
                                'type'       => 'replace',
                                'find_regex' => '@@(.+)@@',
                                'replace'    => '$1',
                            ],
                        ],
                        'show_if' => [
                            'where' => "enable_single_listing_page",
                            'conditions' => [
                                ['key' => 'value', 'compare' => '=', 'value' => true],
                            ],
                        ],

                    ],
                ],
                'value' => [],
            ],
            'enable_single_listing_page' => [
                'type'          => 'toggle',
                'label'         => __( 'Show single listing in page', 'directorist' ),
                'value'         => false,
            ],
            'single_listing_page' => [
                'label'             => __('Single listing page', 'directorist'),
                'type'              => 'select',
                'value'             => '',
                'showDefaultOption' => true,
                'options'           => directorist_get_all_page_list(),
                'show_if' => [
                    'where' => "enable_single_listing_page",
                    'conditions' => [
                        ['key' => 'value', 'compare' => '=', 'value' => true],
                    ],
                ],
            ],

            'single_listing_shortcodes' => [
                'type'        => 'shortcode-list',
                'buttonLabel' => 'Generate',
                'label'       => __( 'Generate shortcodes', 'directorist' ),
                'description' => __( 'Generate single listing shortcodes', 'directorist' ),
                'value'       => [
                    '[directorist_single_listings_header]',
                    [
                        'shortcode' => '[directorist_single_listings_section key="@@shortcode_key@@"]',
                        'mapAtts' => [
                            [
                                'mapAll' => 'single_listings_contents.value.groups',
                                'where' => [
                                    'key' => 'label',
                                        'applyFilter' => [
                                            ['type' => 'lowercase'],
                                            [
                                                'type'       => 'replace',
                                                'find_regex' => '\\s',
                                                'replace'    => '-',
                                            ],
                                        ],
                                        'mapTo' => '@@shortcode_key@@'
                                ]
                            ],
                        ],
                    ],
                ],

                'show_if' => [
                    'where' => "enable_single_listing_page",
                    'conditions' => [
                        ['key' => 'value', 'compare' => '=', 'value' => true],
                    ],
                ],
            ],

            'similar_listings_logics' => [
                'type'    => 'radio',
                'name'    => 'similar_listings_logics',
                'label' => __( 'Similar listings logics', 'directorist' ),
                'options' => [
                    ['id' => 'match_category_nd_location', 'label' => __( 'Must match category and tag', 'directorist' ), 'value' => 'AND'],
                    ['id' => 'match_category_or_location', 'label' => __( 'Must match category or tag', 'directorist' ), 'value' => 'OR'],
                ],
                'value'   => 'OR',
            ],
            'listing_from_same_author' => [
                'type'  => 'toggle',
                'label' => __( 'Listing from same author', 'directorist' ),
                'value' => false,
            ],
            'similar_listings_number_of_listings_to_show' => [
                'type'  => 'range',
                'min'   => 0,
                'max'   => 20,
                'label' => __( 'Number of listings to show', 'directorist' ),
                'value' => 0,
            ],
            'similar_listings_number_of_columns' => [
                'type'  => 'range',
                'min'   => 1,
                'max'   => 10,
                'label' => __( 'Number of columns', 'directorist' ),
                'value' => 3,
            ],

            'search_form_fields' => [
                'type'     => 'form-builder',
                'generalSettings' => [
                    'allowAddNewGroup' => false,
                ],
                'groupSettings' => [
                    'defaultGroupLabel' => 'Section',
                    'canTrash'   => false,
                    'draggable'  => false
                ],
                'widgets'  => $search_form_widgets,
                'value' => [
                    'groups' => [
                        [
                            'label'     => __( 'Basic', 'directorist' ),
                            'lock'      => true,
                            'draggable' => false,
                            'fields'    => [],
                        ],
                        [
                            'label'     => __( 'Advanced', 'directorist' ),
                            'lock'      => true,
                            'draggable' => false,
                            'fields'    => [],
                        ],
                    ]
                ],
            ],

            'single_listing_header' => apply_filters( 'directorist_listing_header_layout', [
                'type' => 'card-builder',
                'template' => 'listing-header',
                'value' => '',
                'card-options' => [
                    'general' => [
                        'back' => [
                            'type' => "badge",
                            'label' => __( "Back", 'directorist' ),
                            'options' => [
                                'title' => __( "Back Button Settings", "directorist" ),
                                'fields' => [
                                    'label' => [
                                        'type' => "toggle",
                                        'label' => __( "Enable", "directorist" ),
                                        'value' => true,
                                    ],
                                ],
                            ],
                        ],
                        'section_title' => [
                            'type' => "title",
                            'label' => __( "Section Title", "directorist" ),
                            'options' => [
                                'title' => __( "Section Title Options", "directorist" ),
                                'fields' => [
                                    'label' => [
                                        'type' => "text",
                                        'label' => __( "Label", "directorist" ),
                                        'value' => "Section Title",
                                    ],
                                    'icon' => [
                                        'type' => "icon",
                                        'label' => __( "Icon", "directorist" ),
                                        'value' => "",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'content_settings' => [
                        'listing_title' => [
                            'type' => "title",
                            'label' => __( "Listing Title", "directorist" ),
                            'options' => [
                                'title' => __( "Listing Title Settings", "directorist" ),
                                'fields' => [
                                    'enable_title' => [
                                        'type' => "toggle",
                                        'label' => __( "Show Title", "directorist" ),
                                        'value' => true,
                                    ],
                                    'enable_tagline' => [
                                        'type' => "toggle",
                                        'label' => __( "Show Tagline", "directorist" ),
                                        'value' => true,
                                    ],
                                ],
                            ],
                        ],
                        'listing_description' => [
                            'type' => "title",
                            'label' => __( "Description", "directorist" ),
                            'options' => [
                                'title' => __( "Description Settings", "directorist" ),
                                'fields' => [
                                'enable' => [
                                    'type' => "toggle",
                                    'label' => __( "Show Description", "directorist" ),
                                    'value' => true,
                                ],
                                ],
                            ],
                        ],
                    ],
                ],
                'options_layout' => [
                    'header' => ['back', 'section_title'],
                    'contents_area' => ['title_and_tagline', 'description'],
                ],
                'widgets' => [
                    'bookmark' => [
                        'type' => "button",
                        'label' => __( "Bookmark", "directorist" ),
                        'icon' => 'la la-bookmark',
                    ],
                    'share' => [
                        'type' => "badge",
                        'label' => __( "Share", "directorist" ),
                        'icon' => 'la la-share',
                        'options' => [
                            'title' => __( "Share Settings", "directorist" ),
                            'fields' => [
                                'icon' => [
                                    'type' => "icon",
                                    'label' => __( "Icon", "directorist" ),
                                    'value' => 'la la-share',
                                ],
                            ],
                        ],
                    ],
                    'report' => [
                        'type' => "badge",
                        'label' => __( "Report", "directorist" ),
                        'icon' => 'la la-flag',
                        'options' => [
                            'title' => __( "Report Settings", "directorist" ),
                            'fields' => [
                                'icon' => [
                                    'type' => "icon",
                                    'label' => __( "Icon", "directorist" ),
                                    'value' => 'la la-flag',
                                ],
                            ],
                        ],
                    ],
                    
                    'listing_slider' => [
                        'type' => "thumbnail",
                        'label' => __( "Listings Slider", "directorist" ),
                        'icon' => 'uil uil-text-fields',
                        'can_move' => false,
                        'options' => [
                            'title' => __( "Listings Slider Settings", "directorist" ),
                            'fields' => [
                                'footer_thumbail' => [
                                    'type' => "toggle",
                                    'label' => __( "Enable Footer Thumbnail", "directorist" ),
                                    'value' => true,
                                ],
                            ],
                        ],
                    ],
                    'price' => [
                        'type' => "badge",
                        'label' => __( "Listings Price", "directorist" ),
                        'icon' => 'uil uil-text-fields',
                    ],
                    'badges' => [
                        'type' => "badge",
                        'label' => __( "Badges", "directorist" ),
                        'icon' => 'uil uil-text-fields',
                        'options' => [
                            'title' => __( "Badge Settings". "directorist" ),
                            'fields' => [
                                'new_badge' => [
                                    'type' => "toggle",
                                    'label' => __( "Display New Badge", "directorist" ),
                                    'value' => true,
                                ],
                                'popular_badge' => [
                                    'type' => "toggle",
                                    'label' => __( "Display Popular Badge", "directorist" ),
                                    'value' => true,
                                ],
                                'featured_badge' => [
                                    'type' => "toggle",
                                    'label' => __( "Display Featured Badge", "directorist" ),
                                    'value' => true,
                                ],
                            ],
                        ],
                    ],
                    
                    'reviews' => [
                        'type' => "reviews",
                        'label' => __( "Listings Reviews", "directorist" ),
                        'icon' => 'uil uil-text-fields',
                    ],
                    'ratings_count' => [
                        'type' => "ratings-count",
                        'label' => __( "Listings Ratings", "directorist" ),
                        'icon' => 'uil uil-text-fields',
                    ],
                    'category' => [
                        'type' => "badge",
                        'label' => __( "Listings Category", "directorist" ),
                        'icon' => 'uil uil-text-fields',
                        'show_if' => [
                            'where' => "submission_form_fields.value.fields",
                            'conditions' => [
                                ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'category'],
                            ],
                        ],
                    ],
                    'location' => [
                        'type' => "badge",
                        'label' => __( "Listings Location", "directorist" ),
                        'icon' => 'uil uil-text-fields',
                        'show_if' => [
                            'where' => "submission_form_fields.value.fields",
                            'conditions' => [
                                ['key' => '_any.widget_name', 'compare' => '=', 'value' => 'location'],
                            ],
                        ],
                    ],
                ],

                'layout' => [
                    'listings_header' => [
                        'quick_actions' => [
                            'label' => __( 'Top Right', 'directorist' ),
                            'maxWidget' => 0,
                            'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                            'acceptedWidgets' => [ 'bookmark', 'share', 'report' ],
                        ],
                        'thumbnail' => [
                            'label' => __( 'Thumbnail', 'directorist' ),
                            'maxWidget' => 1,
                            'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                            'acceptedWidgets' => [ 'listing_slider' ],
                        ],
                        'quick_info' => [
                            'label' => __( 'Quick info', 'directorist' ),
                            'maxWidget' => 0,
                            'maxWidgetInfoText' => "Up to __DATA__ item{s} can be added",
                            'acceptedWidgets' => [ 'badges', 'price', 'reviews', 'ratings_count', 'category', 'location' ],
                        ],
                    ],
                ],
            ] ),

            'listings_card_grid_view' => apply_filters( 'directorist_listing_card_layouts', [
                'type' => 'card-builder',
                'card_templates' => [
                    'grid_view_with_thumbnail' => [
                        'label'    => __( 'With Preview Image', 'directorist' ),
                        'template' => 'grid-view-with-thumbnail',
                        'widgets'  => $listing_card_widget,
                        'layout'   => $listing_card_grid_view_with_thumbnail_layout,
                    ],
                    'grid_view_without_thumbnail' => [
                        'label'    => __( 'Without Preview Image', 'directorist' ),
                        'template' => 'grid-view-without-thumbnail',
                        'widgets'  => $listing_card_widget,
                        'layout'   => $listing_card_grid_view_without_thumbnail_layout,
                    ],
                ],
            ] ),

            'listings_card_list_view' => apply_filters( 'directorist_listing_list_layouts', [
                'type' => 'card-builder',
                'card_templates' => [
                    'list_view_with_thumbnail' => [
                        'label'    => __( 'With Preview Image', 'directorist' ),
                        'template' => 'list-view-with-thumbnail',
                        'widgets'  => $listing_card_widget,
                        'layout'   => $listing_card_list_view_with_thumbnail_layout,
                    ],
                    'list_view_without_thumbnail' => [
                        'label'    => __( 'Without Preview Image', 'directorist' ),
                        'template' => 'list-view-without-thumbnail',
                        'widgets'  => $listing_card_widget,
                        'layout'   => $listing_card_list_view_without_thumbnail_layout,
                    ],
                ],
            ] ),

        ]);
        
        self::$layouts = apply_filters('atbdp_listing_type_settings_layout', [
            'general' => [
                'label' => 'General',
                'icon' => '<i class="uil uil-estate"></i>',
                'sections' => [
                    'labels' => [
                        'title'       => __('Directory icon', 'directorist'),
                        'fields'      => [ 'icon' ],
                    ],

                    'listing_status' => [
                        'title' => __('Default listing status', 'directorist'),
                        'fields'      => [
                            'new_listing_status',
                            'edit_listing_status',
                        ],
                    ],

                    'expiration' => [
                        'title'       => __('Expiration', 'directorist'),
                        'description' => __('Default time to expire a listing.', 'directorist'),
                        'fields'      => [
                            'default_expiration',
                        ],
                    ],

                    'default_preview' => [
                        'title'       => __('Default Preview', 'directorist'),
                        'description' => __('This image will be used when listing preview image is not present. Leave empty to hide the preview image completely.', 'directorist'),
                        'fields'      => [
                            'preview_image',
                        ],
                    ],
                    
                    'export_import' => [
                        'title'       => __('Export The Config File', 'directorist'),
                        'description' => __('Export all the form, layout and settings', 'directorist'),
                        'fields'      => [
                            'import_export',
                        ],
                    ],
                ],
            ],

            'submission_form' => [
                'label' => __( 'Add Listing Form', 'directorist' ),
                'icon' => '<span class="uil uil-file-edit-alt"></span>',
                'submenu' => [
                    'form_fields' => [
                        'label' => __( 'Form Fields', 'directorist' ),
                        'container' => 'wide',
                        'sections' => [
                            'form_fields' => [
                                'title' => __( 'Select or create fields for the add listing form', 'directorist' ),
                                'description' => '<a target="_blank" href="https://directorist.com/documentation/directorist/form-and-layout-builder/add-listing-form-layout/"> '. __( 'Need help?', 'directorist' ) .' </a>',
                                'fields' => [
                                    'submission_form_fields'
                                ],
                            ],
                        ],
                    ],
                    'settings' => [
                        'label' => __( 'Settings', 'directorist' ),
                        'sections' => apply_filters( 'atbdp_submission_form_settings', [
                            'terms_and_conditions' => [
                                'title' => __('Terms and Conditions', 'directorist'),
                                'container' => 'short-width',
                                'fields' => [
                                    'listing_terms_condition',
                                    'require_terms_conditions',
                                    'terms_label',
                                ],
                            ],
                            'privacy_and_policy' => [
                                'title' => __('Privacy and Policy', 'directorist'),
                                'container' => 'short-width',
                                'fields' => [
                                    'listing_privacy',
                                    'require_privacy',
                                    'privacy_label',
                                ],
                            ],
                            'submittion_settings' => [
                                'title' => __('Submission Settings', 'directorist'),
                                'container' => 'short-width',
                                'fields' => [
                                    'preview_mode',
                                    'submit_button_label',
                                ],
                            ],
                        ] ),
                    ],
                ],
            ],

            'single_page_layout' => [
                'label' => __( 'Single Page Layout', 'directorist' ),
                'icon' => '<span class="uil uil-credit-card"></span>',
                'submenu' => [
                    'listing_header' => [
                        'label' => __( 'Listing Header', 'directorist' ),
                        'container' => 'wide',
                        'sections' => [
                            'listing_header' => [
                                'title' => __( 'Listing Header', 'directorist' ),
                                'title_align' => 'center',
                                'fields' => [
                                    'single_listing_header'
                                ],
                            ]
                        ]
                    ],
                    'contents' => [
                        'label' => __( 'Contents', 'directorist' ),
                        'container' => 'wide',
                        'sections' => [
                            'contents' => [
                                'title' => __( 'Contents', 'directorist' ),
                                'description' => '<a target="_blank" href="https://directorist.com/documentation/directorist/form-and-layout-builder/single-listings-layout/"> '. __( 'Need help?', 'directorist' ) .' </a>',
                                'fields' => [
                                    'single_listings_contents'
                                ],
                            ]
                        ]
                    ],
                    'similar_listings' => [
                        'label' => __( 'Other Settings', 'directorist' ),
                        'sections' => [
                            'page_settings' => [
                                'fields' => [
                                    'enable_single_listing_page',
                                    'single_listing_page',
                                    'single_listing_shortcodes',
                                ],
                            ],
                            'other' => [
                                'title' => __( 'Similar Listings', 'directorist' ),
                                'fields' => [
                                    'similar_listings_logics',
                                    'listing_from_same_author',
                                    'similar_listings_number_of_listings_to_show',
                                    'similar_listings_number_of_columns',
                                ],
                            ],
                        ]
                    ],
                ]
            ],
            'listings_card_layout' => [
                'label' => __( 'All Listing Layout', 'directorist' ),
                'icon' => '<span class="uil uil-list-ul"></span>',
                'submenu' => [
                    'grid_view' => [
                        'label' => __( 'All Listing Grid Layout', 'directorist' ),
                        'container' => 'wide',
                        'sections' => [
                            'listings_card' => [
                                'title' => __('Create and customize the listing card for grid view', 'directorist'),
                                'title_align' => 'center',
                                'description' => '<a target="_blank" href="https://directorist.com/documentation/directorist/form-and-layout-builder/multiple-directories/"> '. __( 'Need help?', 'directorist' ) .' </a>' . __( 'Read the documentation or open a ticket in our helpdesk.', 'directorist' ),
                                'fields' => [
                                    'listings_card_grid_view'
                                ],
                            ],
                        ],
                    ],
                    'list_view' => [
                        'label' => __( 'All Listing List Layout', 'directorist' ),
                        'container' => 'full-width',
                        'sections' => [
                            'listings_card' => [
                                'title' => __('Create and customize the listing card for listing view', 'directorist'),
                                'title_align' => 'center',
                                'description' => '<a target="_blank" href="https://directorist.com/documentation/directorist/form-and-layout-builder/multiple-directories/"> '. __( 'Need help?', 'directorist' ) .' </a>' . __( 'Read the documentation or open a ticket in our helpdesk.', 'directorist' ),
                                'fields' => [
                                    'listings_card_list_view'
                                ],
                            ],
                        ],
                    ],
                ],

            ],
            'search_forms' => [
                'label' => __( 'Search Forms', 'directorist' ),
                'icon' => '<span class="uil uil-search"></span>',
                'container' => 'wide',
                'sections' => [
                    'form_fields' => [
                        'title' => __('Customize the search form for this listing type', 'directorist'),
                        'description' => '<a target="_blank" href="https://directorist.com/documentation/directorist/form-and-layout-builder/search-form-layout/"> '. __( 'Need help?', 'directorist' ) .' </a>',
                        'fields' => [
                            'search_form_fields'
                        ],
                    ],
                ],
            ],
        ]);


        // Conditional Fields
        // -----------------------------
        // Guest Submission
        if ( get_directorist_option( 'guest_listings', 1 ) == '1' ) {
            self::$fields['guest_email_label'] = [
                'label' => __('Guest Email Label', 'directorist'),
                'type'  => 'text',
                'value' => 'Your Email',
            ];

            self::$fields['guest_email_placeholder'] = [
                'label' => __('Guest Email Placeholder', 'directorist'),
                'type'  => 'text',
                'value' => 'example@email.com',
            ];

            self::$layouts['submission_form']['submenu']['settings']['sections']['guest_submission'] = [
                'title'         => __('Guest Listing Submission', 'directorist'),
                'description'   => __('Need Help?', 'directorist'),
                'container'     => 'short-width',
                'fields' => [
                    'guest_email_label',
                    'guest_email_placeholder',
                ],
            ];
        }

        $this->options = [
            'name' => [
                'type'  => 'text',
                'placeholder'  => 'Name *',
                'value' => '',
                'rules' => [
                    'required' => true,
                ],
                'input_style' => [
                    'class_names' => 'cptm-form-control-light'
                ],
                'is-hidden' => true
            ],
        ];

        self::$config = [
            'submission' => [
                'url' => admin_url('admin-ajax.php'),
                'with' => [ 'action' => 'save_post_type_data' ],
            ],
            'fields_group' => [
                'general_config' => [
                    'icon',
                    'singular_name',
                    'plural_name',
                    'permalink',
                    'preview_image',
                ]
            ]
        ];
    }

    // add_menu_pages
    public function add_menu_pages()
    {
        $enable_multi_directory = get_directorist_option( 'enable_multi_directory', false );
        $page_title = __( 'Directory Builder', 'directorist' );
        $page_slug  = 'atbdp-layout-builder';

        if ( atbdp_is_truthy( $enable_multi_directory ) ) {
            $page_title = __( 'Directory Builder', 'directorist' );
            $page_slug  = 'atbdp-directory-types';
        }

        add_submenu_page(
            'edit.php?post_type=at_biz_dir',
            $page_title,
            $page_title,
            'manage_options',
            $page_slug,
            [$this, 'menu_page_callback__directory_types'],
            5
        );
    }

    // get_default_directory_id
    public function get_default_directory_id() {
        $default_directory = get_directorist_option( 'atbdp_default_derectory', '' );

        if ( ! empty( $default_directory  ) ) {
            return $default_directory;
        }

        return 0;
    }

    // menu_page_callback__directory_types
    public function menu_page_callback__directory_types()
    {
        $enable_multi_directory = get_directorist_option( 'enable_multi_directory', false );
        $enable_multi_directory = atbdp_is_truthy( $enable_multi_directory );

        $action = isset( $_GET['action'] ) ? $_GET['action'] : '';
        $listing_type_id = 0;

        $data = [
            'add_new_link' => admin_url('edit.php?post_type=at_biz_dir&page=atbdp-directory-types&action=add_new'),
        ];

        if ( ! $enable_multi_directory || ( ! empty( $action ) && ('edit' === $action || 'add_new' === $action ) ) ) {
            $this->prepare_settings();

            if ( ! empty( $_REQUEST['listing_type_id'] ) ) {
                $listing_type_id = ( ! $enable_multi_directory ) ? default_directory_type() : $_REQUEST['listing_type_id'];
                $this->update_fields_with_old_data( $listing_type_id );
            }

            $cptm_data = [
                'fields'  => self::$fields,
                'layouts' => self::$layouts,
                'config'  => self::$config,
                'options' => $this->options,
                'id'      => $listing_type_id,
            ];
            
            wp_localize_script('directorist-multi-directory-builder', 'cptm_data', $cptm_data);
            atbdp_load_admin_template('post-types-manager/edit-listing-type', $data);
            return;
        }

        atbdp_load_admin_template('post-types-manager/all-listing-types', $data);
    }



    // update_fields_with_old_data
    public function update_fields_with_old_data( $listing_type_id = 0 )
    {
        // $listing_type_id = absint($_REQUEST['listing_type_id']);
        $term      = get_term($listing_type_id, 'atbdp_listing_types');
        $term_name = ( $term ) ? $term->name : '';
        $term_id   = ( $term ) ? $term->term_id : 0;

        $this->options['name']['value'] = $term_name;

        $all_term_meta = get_term_meta( $term_id );
        $test_migration = apply_filters( 'atbdp_test_migration', false );
        // $test_migration = apply_filters( 'atbdp_test_migration', true );

        if ( $test_migration ) {
            $all_term_meta = self::$migration->get_fields_data();
        }

        if ( 'array' !== getType( $all_term_meta ) ) { return; }

        foreach ( $all_term_meta as $meta_key => $meta_value ) {
            if ( isset( self::$fields[$meta_key] ) ) {
                $_meta_value = ( ! $test_migration ) ? $meta_value[0] : $meta_value;
                $value = maybe_unserialize( maybe_unserialize( $_meta_value ) );

                self::$fields[ $meta_key ]['value'] = $value;
            }
        }

        foreach (self::$config['fields_group'] as $group_key => $group_fields) {
            if (array_key_exists($group_key, $all_term_meta)) {
                $_group_meta_value = ( ! $test_migration ) ? $all_term_meta[$group_key][0] : $all_term_meta[$group_key];
                $group_value = maybe_unserialize( maybe_unserialize( $_group_meta_value ) );

                foreach ($group_fields as $field_index => $field_key) {

                    if ( ! key_exists( $field_key, $group_value ) ) { continue; }

                    if ( 'string' === gettype($field_key) && array_key_exists($field_key, self::$fields)) {
                        self::$fields[$field_key]['value'] = $group_value[$field_key];
                    }

                    if ('array' === gettype($field_key)) {
                        foreach ($field_key as $sub_field_key) {
                            if (array_key_exists($sub_field_key, self::$fields)) {
                                self::$fields[$sub_field_key]['value'] = $group_value[$field_index][$sub_field_key];
                            }
                        }
                    }
                }
            }
        }

        // $test = get_term_meta( $listing_type_id, 'submission_form_fields' )[0];
        // $submission_form_fields = maybe_unserialize( maybe_unserialize( $all_term_meta['submission_form_fields'] ) );
        // $submission_form_fields = maybe_unserialize( maybe_unserialize( $all_term_meta['submission_form_fields'][0] ) );
        // e_var_dump( $submission_form_fields['fields']['image_upload'] );
        // e_var_dump( $all_term_meta['fields']['image_upload'] );
        // $test = get_term_meta( $listing_type_id, 'listings_card_grid_view' );
        // e_var_dump( $test['fields']['video'] );
        // e_var_dump( $test );
        // e_var_dump( self::$fields[ 'single_listings_contents' ] );
        // e_var_dump( json_decode( $test ) );
    }

    // handle_delete_listing_type_request
    public function handle_delete_listing_type_request()
    {

        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'delete_listing_type')) {
            wp_die('Are you cheating? | _wpnonce');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Are you cheating? | manage_options');
        }

        $term_id = isset($_REQUEST['listing_type_id']) ? absint($_REQUEST['listing_type_id']) : 0;

        $this->delete_listing_type($term_id);

        wp_redirect(admin_url('edit.php?post_type=at_biz_dir&page=atbdp-directory-types'));
        exit;
    }

    // delete_listing_type
    public function delete_listing_type($term_id = 0)
    {
        if (wp_delete_term($term_id, 'atbdp_listing_types')) {
            atbdp_add_flush_alert([
                'id'      => 'deleting_listing_type_status',
                'page'    => 'all-listing-type',
                'message' => __( 'Successfully Deleted the listing type', 'directorist' ),
            ]);
        } else {
            atbdp_add_flush_alert([
                'id'      => 'deleting_listing_type_status',
                'page'    => 'all-listing-type',
                'type'    => 'error',
                'message' => __( 'Failed to delete the listing type', 'directorist' )
            ]);
        }
    }

    // register_terms
    public function register_terms()
    {
        register_taxonomy( ATBDP_DIRECTORY_TYPE, [ ATBDP_POST_TYPE ], [
            'hierarchical' => false,
            'labels' => [
                'name' => _x('Listing Type', 'taxonomy general name', 'directorist'),
                'singular_name' => _x('Listing Type', 'taxonomy singular name', 'directorist'),
                'search_items' => __('Search Listing Type', 'directorist'),
                'menu_name' => __('Listing Type', 'directorist'),
            ],
            'show_ui' => false,
        ]);
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

    // enqueue_scripts
    public function enqueue_scripts( $page = '' )
    {
        wp_enqueue_media();
        wp_enqueue_style('atbdp-unicons');
        wp_enqueue_style('atbdp-font-awesome');
        wp_enqueue_style('atbdp-line-awesome');
        // wp_enqueue_style('atbdp-select2-style');
        // wp_enqueue_style('atbdp-select2-bootstrap-style');
        wp_enqueue_style('atbdp_admin_css');

        wp_localize_script('atbdp_admin_app', 'ajax_data', ['ajax_url' => admin_url('admin-ajax.php')]);
        wp_enqueue_script('atbdp_admin_app');
    }
}


// include_files
function include_files() {
   
    if ( ! trait_exists( 'Directorist\Multi_Directory_Helper' ) ) {
        $file = trailingslashit( dirname( __FILE__ ) )  . 'trait-multi-directory-helper.php';
        if ( file_exists( $file ) ) {
            require_once( $file );
        }
    }

    // if ( ! class_exists( 'Directorist\Multi_Directory_Migration' ) ) {
    //     $file = trailingslashit( dirname( __FILE__ ) )  . 'class-multi-directory-migration.php';
    //     if ( file_exists( $file ) ) {
    //         require_once( $file );
    //     }
    // }
}