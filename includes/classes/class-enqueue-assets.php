<?php

namespace Directorist;

class Enqueue_Assets {

	public static $js_scripts     = [];
	public static $css_scripts    = [];
	public static $script_version = false;
	public static $all_shortcodes = [];
	public static $instance       = null;

	/**
	 * Constuctor
	 */
	function __construct() {

		if ( is_null( self::$instance ) ) {
			self::$instance = $this;

			add_filter( 'directorist_load_min_files', [ $this, 'manage_min_unmin_assets_switching' ] );

			// Enqueue Public Scripts
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_scripts' ] );

			// Enqueue Admin Scripts
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

			// Enqueue Block Editor Scripts
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		}


		return self::$instance;
	}

	/**
	 * Load Assets
	 *
	 * @return void
	 */
	public static function load_assets() {

		// Store All Shortcode Keys
		$shortcodes = ATBDP_Shortcode::$shortcodes;
		if ( is_array( $shortcodes ) ) {
			self::$all_shortcodes = array_keys( $shortcodes );
		}

		// Set Script Version
		$script_version = ( self::load_min_files() ) ? DIRECTORIST_SCRIPT_VERSION : md5( time() );
		self::$script_version = apply_filters( 'directorist_script_version', $script_version );

		// Load Vendor Assets
		self::add_vendor_css_scripts();
		self::add_vendor_js_scripts();

		// Load Global Assets
		self::add_global_css_scripts();
		self::add_global_js_scripts();

		// Load Public Assets
		self::add_public_css_scripts();
		self::add_public_js_scripts();

		// Load Admin Assets
		self::add_admin_css_scripts();
		self::add_admin_js_scripts();

		// Inject Scripts Meta
		self::inject_scripts_meta();

		// Apply Hook to Scripts
		self::apply_hook_to_scripts();
	}

	/**
	 * Manage min unmin assets switching
	 *
	 * @return void
	 */
	public function manage_min_unmin_assets_switching( $default ) {
		$script_debug = get_directorist_option( 'script_debugging', false, true );
		$load_min = ( $script_debug ) ? false : $default;

		return $load_min;
	}

	/**
	 * Load min files
	 *
	 * @return void
	 */
	public static function load_min_files() {
		return apply_filters( 'directorist_load_min_files',  DIRECTORIST_LOAD_MIN_FILES );
	}

	/**
	 * Apply Hook to Scripts
	 *
	 * @return void
	 */
	public static function apply_hook_to_scripts() {
		self::$css_scripts = apply_filters( 'directorist_css_scripts', self::$css_scripts );
		self::$js_scripts = apply_filters( 'directorist_js_scripts', self::$js_scripts );
	}

	/**
	 * Load Vendor CSS Scripts
	 *
	 * @return void
	 */
	public static function add_vendor_css_scripts() {
		$scripts = [];
		$common_asset_group = 'global';

		// Global
		// ================================
		$scripts['directorist-openstreet-map-leaflet'] = [
			'file_name'      => 'leaflet',
			'base_path'      => DIRECTORIST_VENDOR_CSS . 'openstreet-map/',
			'ver'            => self::$script_version,
			'group'          => $common_asset_group,
			'has_min'        => false,                     // public || admin  || global
			'has_rtl'        => false,                            // public || admin  || global
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-openstreet-map-openstreet'] = [
			'file_name'      => 'openstreet',
			'base_path'      => DIRECTORIST_VENDOR_CSS . 'openstreet-map/',
			'ver'            => self::$script_version,
			'group'          => $common_asset_group,
			'has_rtl'        => false,
			'has_min'        => false,                           // public || admin  || global
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-select2'] = [
			'file_name' => 'select2.min',
			'base_path' => DIRECTORIST_VENDOR_CSS,
			'has_min'   => false,
			'has_rtl'   => false,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'global', // public || admin  || global
		];

		// Admin
		// ================================
		$scripts['directorist-unicons'] = [
			'link'      => '//unicons.iconscout.com/release/v3.0.3/css/line.css',
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'global', // public || admin  || global
		];

		// Public
		// ================================
		$scripts['directorist-bootstrap'] = [
			'file_name' => 'bootstrap',
			'base_path' => DIRECTORIST_VENDOR_CSS,
			'has_rtl'   => false,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'enable'    => false
		];

		$scripts['directorist-font-awesome'] = [
			'file_name'      => 'font-awesome.min',
			'base_path'      => DIRECTORIST_VENDOR_CSS,
			'has_min'        => false,
			'has_rtl'        => false,
			'deps'           => [],
			'ver'            => self::$script_version,
			'group'          => $common_asset_group,              // public || admin  || global
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-line-awesome'] = [
			'file_name'      => 'line-awesome.min',
			'base_path'      => DIRECTORIST_VENDOR_CSS,
			'has_min'        => false,
			'has_rtl'        => false,
			'deps'           => [],
			'ver'            => self::$script_version,
			'group'          => $common_asset_group,              // public || admin  || global
			'enable'         => true,
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-ez-media-uploader'] = [
			'file_name' => 'ez-media-uploader',
			'base_path' => DIRECTORIST_VENDOR_CSS,
			'has_min'   => false,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'public',                                        // public || admin  || global
			'enable'    => Script_Helper::is_enable__ez_media_uploader(),
		];

		$scripts['directorist-slick'] = [
			'file_name'      => 'slick',
			'base_path'      => DIRECTORIST_VENDOR_CSS,
			'has_min'        => false,
			'has_rtl'        => false,
			'deps'           => [],
			'ver'            => self::$script_version,
			'group'          => 'public',                         // public || admin  || global
			'enable'         => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-sweetalert'] = [
			'file_name'      => 'sweetalert.min',
			'base_path'      => DIRECTORIST_VENDOR_CSS,
			'has_min'        => false,
			'has_rtl'        => false,
			'deps'           => [],
			'ver'            => self::$script_version,
			'group'          => $common_asset_group,              // public || admin  || global
			'group'          => 'public',              // public || admin  || global
			'enable'         => true,
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts = array_merge( self::$css_scripts, $scripts);
		self::$css_scripts = $scripts;
	}

	/**
	 * Load Vendor JS Scripts
	 *
	 * @return void
	 */
	public static function add_vendor_js_scripts() {
		$scripts = [];
		$common_asset_group = 'global';

		// Global
		// ================================
		$scripts['directorist-no-script'] = [
			'file_name' => 'no-script',
			'base_path' => DIRECTORIST_VENDOR_JS,
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => 'global',
			// 'section'   => '',
		];

		// Openstreet
		$scripts['directorist-openstreet-layers'] = [
			'file_name' => 'openstreetlayers',
			'base_path' => DIRECTORIST_VENDOR_JS . 'openstreet-map/',
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => $common_asset_group,
			// 'section'   => '',
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),

		];

		$scripts['directorist-openstreet-unpkg'] = [
			'file_name' => 'unpkg-min',
			'base_path' => DIRECTORIST_VENDOR_JS . 'openstreet-map/',
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => $common_asset_group,
			// 'section'   => '',
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-openstreet-unpkg-index'] = [
			'file_name' => 'unpkg-index',
			'base_path' => DIRECTORIST_VENDOR_JS . 'openstreet-map/',
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => $common_asset_group,
			// 'section'   => '',
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-openstreet-unpkg-libs'] = [
			'file_name' => 'unpkg-libs',
			'base_path' => DIRECTORIST_VENDOR_JS . 'openstreet-map/',
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => $common_asset_group,
			// 'section'   => '',
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-openstreet-leaflet-versions'] = [
			'file_name' => 'leaflet-versions',
			'base_path' => DIRECTORIST_VENDOR_JS . 'openstreet-map/',
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => $common_asset_group,
			// 'section'   => '',
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-openstreet-leaflet-markercluster-versions'] = [
			'file_name' => 'leaflet.markercluster-versions',
			'base_path' => DIRECTORIST_VENDOR_JS . 'openstreet-map/',
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => $common_asset_group,
			// 'section'   => '',
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-openstreet-libs-setup'] = [
			'file_name' => 'libs-setup',
			'base_path' => DIRECTORIST_VENDOR_JS . 'openstreet-map/',
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => $common_asset_group,
			// 'section'   => '',
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-openstreet-open-layers'] = [
			'file_name' => 'OpenLayers',
			'base_path' => DIRECTORIST_VENDOR_JS . 'openstreet-map/openlayers/',
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => $common_asset_group,
			// 'section'   => '',
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-openstreet-crosshairs'] = [
			'file_name' => 'Crosshairs',
			'base_path' => DIRECTORIST_VENDOR_JS . 'openstreet-map/openlayers4jgsi/',
			'deps'      => [],
			'has_min'   => false,
			'ver'       => '',
			'group'     => $common_asset_group,
			// 'section'   => '',
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-openstreet-load-scripts'] = [
			'file_name' => 'global-load-osm-map',
			'base_path' => DIRECTORIST_JS,
			'deps'      => [],
			'has_min'   => true,
			'ver'       => '',
			'group'     => $common_asset_group,
			'section'   => '__',
			'enable'    => Script_Helper::is_enable_map( 'openstreet' ),
		];

		// Google Map
		$map_api_key = get_directorist_option( 'map_api_key', 'AIzaSyCwxELCisw4mYqSv_cBfgOahfrPFjjQLLo' );
		$scripts['directorist-google-map'] = [
			'link'           => '//maps.googleapis.com/maps/api/js?key=' . $map_api_key . '&libraries=places',
			'deps'           => [],
			'ver'            => self::$script_version,
			'group'          => $common_asset_group,
			'section'        => '',
			'enable'         => Script_Helper::is_enable_map( 'google' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-markerclusterer'] = [
			'file_name'      => 'markerclusterer',
			'base_path'      => DIRECTORIST_VENDOR_JS,
			'deps'           => [],
			'has_min'        => false,
			'ver'            => self::$script_version,
			'group'          => $common_asset_group,
			'section'        => '',
			'enable'         => Script_Helper::is_enable_map( 'google' ),
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		// Other
		$scripts['directorist-select2'] = [
			'file_name' => 'select2.min',
			'base_path' => DIRECTORIST_VENDOR_JS,
			'has_min'   => false,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'global',
		];

		$scripts['directorist-sweetalert'] = [
			'file_name'      => 'sweetalert.min',
			'base_path'      => DIRECTORIST_VENDOR_JS,
			'has_min'        => false,
			'deps'           => [],
			'ver'            => self::$script_version,
			'group'          => $common_asset_group, // public || admin  || global
			'section'        => '',
			'enable'         => true,
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-tooltip'] = [
			'file_name'      => 'tooltip',
			'base_path'      => DIRECTORIST_VENDOR_JS,
			'has_min'        => false,
			'deps'           => [ 'directorist-popper' ],
			'ver'            => self::$script_version,
			'group'          => $common_asset_group,              // public || admin  || global
			'section'        => '',
			'enable'         => true,
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-popper'] = [
			'file_name' => 'popper',
			'base_path' => DIRECTORIST_VENDOR_JS,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => $common_asset_group, // public || admin  || global
			'section'   => '',
			'enable'    => true,
		];

		$scripts['directorist-range-slider'] = [
			'file_name' => 'range-slider',
			'base_path' => DIRECTORIST_VENDOR_JS,
			'deps'      => [],
			'has_min'   => false,
			'has_rtl'   => true,
			'ver'       => self::$script_version,
			'group'     => $common_asset_group, // public || admin  || global
			'section'   => '__',
		];

		$scripts['directorist-ez-media-uploader'] = [
			'file_name' => 'ez-media-uploader',
			'base_path' => DIRECTORIST_VENDOR_JS,
			'deps'      => [],
			'has_min'   => false,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			// 'section'   => '__',
		];


		// Admin
		// ================================

		// Public
		// ================================
		$scripts['directorist-bootstrap'] = [
			'file_name' => 'bootstrap.min',
			'base_path' => DIRECTORIST_VENDOR_JS,
			'has_min'   => false,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => '',
			'enable'    => false,
		];

		$scripts['directorist-grid'] = [
			'file_name' => 'grid.min',
			'base_path' => DIRECTORIST_VENDOR_JS,
			'has_min'   => false,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => '',
			'enable'    => false,
		];

		$scripts['directorist-jquery-barrating'] = [
			'file_name' => 'jquery.barrating.min',
			'base_path' => DIRECTORIST_VENDOR_JS,
			'has_min'   => false,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => '',
			'enable'    => true,
		];

		$scripts['directorist-slick'] = [
			'file_name'      => 'slick.min',
			'base_path'      => DIRECTORIST_VENDOR_JS,
			'has_min'        => false,
			'deps'           => [],
			'ver'            => self::$script_version,
			'group'          => 'public',                         // public || admin  || global
			'section'        => '',
			'enable'         => true,
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-plasma-slider'] = [
			'file_name'      => 'plasma-slider',
			'base_path'      => DIRECTORIST_VENDOR_JS,
			'has_min'        => false,
			'ver'            => self::$script_version,
			'group'          => 'public',
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-uikit'] = [
			'file_name'      => 'uikit.min',
			'base_path'      => DIRECTORIST_VENDOR_JS,
			'has_min'        => false,
			'deps'           => [],
			'ver'            => self::$script_version,
			'group'          => 'public',                         // public || admin  || global
			'section'        => '',
			'enable'         => true,
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-validator'] = [
			'file_name' => 'validator.min',
			'base_path' => DIRECTORIST_VENDOR_JS,
			'has_min'   => false,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => '',
			'enable'    => false,
		];

		$scripts = array_merge( self::$js_scripts, $scripts);
		self::$js_scripts = $scripts;
	}


	/**
	 * Load Public CSS Scripts
	 *
	 * @return void
	 */
	public static function add_public_css_scripts() {
		$scripts = [];

		$scripts['directorist-main-style'] = [
			'file_name'      => 'public-main',
			'base_path'      => DIRECTORIST_CSS,
			'deps'           => [],
			'ver'            => self::$script_version,
			'group'          => ['public', 'block-editor'],                       // public || admin  || global
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-inline-style'] = [
			'file_name' => 'inline-style',
			'base_path' => DIRECTORIST_ASSETS . 'other/',
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'has_min'   => false,
			'has_rtl'   => false,
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-settings-style'] = [
			'file_name' => 'settings-style',
			'base_path' => DIRECTORIST_ASSETS . 'other/',
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'has_min'   => false,
			'has_rtl'   => false,
			'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts = array_merge( self::$css_scripts, $scripts);
		self::$css_scripts = $scripts;
	}

	/**
	 * Load Public JS Scripts
	 *
	 * @return void
	 */
	public static function add_public_js_scripts() {
		$scripts = [];

		$scripts['directorist-main-script'] = [
			'file_name' => 'public-main',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => '',
			'enable'    => true,
			'localize_data' => [
				'object_name' => 'atbdp_public_data',
				'data' => Script_Helper::get_main_script_data()
			],
		];

		$scripts['directorist-releated-listings-slider'] = [
			'file_name' => 'public-releated-listings-slider',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => '__',
			'enable'    => false,
			// 'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-atmodal'] = [
			'file_name' => 'public-atmodal',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => '',
			'enable'    => true,
		];

		$scripts['directorist-geolocation'] = [
			'file_name' => 'global-geolocation',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => 'search-home',
		];

		$scripts['directorist-geolocation-widget'] = [
			'file_name' => 'public-geolocation-widget',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => 'search-home',
		];

		$scripts['directorist-search-listing'] = [
			'file_name' => 'public-search-listing',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => 'search-form',
		];

		$scripts['directorist-search-form-listing'] = [
			'file_name' => 'public-search-form-listing',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section'   => 'search-form',
		];

		$scripts['directorist-single-listing-openstreet-map-custom-script'] = [
			'file_name'      => 'public-single-listing-openstreet-map-custom-script',
			'base_path'      => DIRECTORIST_JS,
			'ver'            => self::$script_version,
			'group'          => 'public',                                        // public || admin  || global
			'section'        => 'single-listing-page',                           // public || admin  || global
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			// 'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-single-listing-openstreet-map-widget-custom-script'] = [
			'file_name'      => 'public-single-listing-openstreet-map-widget-custom-script',
			'base_path'      => DIRECTORIST_JS,
			'ver'            => self::$script_version,
			'group'          => 'public',                                        // public || admin  || global
			'section'        => 'single-listing-page',                           // public || admin  || global
			'enable'         => Script_Helper::is_enable_map( 'openstreet' ),
			// 'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-single-listing-gmap-custom-script'] = [
			'file_name'      => 'public-single-listing-gmap-custom-script',
			'base_path'      => DIRECTORIST_JS,
			'ver'            => self::$script_version,
			'group'          => 'public',                                   // public || admin  || global
			'section'        => 'single-listing-page',
			'enable'         => Script_Helper::is_enable_map( 'google' ),
			// 'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-single-listing-gmap-widget-custom-script'] = [
			'file_name'      => 'public-single-listing-gmap-widget-custom-script',
			'base_path'      => DIRECTORIST_JS,
			'ver'            => self::$script_version,
			'group'          => 'public',                                   // public || admin  || global
			'section'        => 'single-listing-page',
			'enable'         => Script_Helper::is_enable_map( 'google' ),
			// 'fource_enqueue' => is_singular( ATBDP_POST_TYPE ),
		];

		$scripts['directorist-add-listing-public'] = [
			'file_name'      => 'global-add-listing',
			'base_path'      => DIRECTORIST_JS,
			'ver'            => self::$script_version,
			'group'          => 'public',                                           // public || admin  || global
			'section'        => 'add_listing',
			'before_enqueue' => [ Script_Helper::class, 'add_listing_brfore_enqueue_task' ],
		];

		$scripts['directorist-add-listing-openstreet-map-custom-script-public'] = [
			'file_name' => 'global-add-listing-openstreet-map-custom-script',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section' => '__',
			'enable'  => Script_Helper::is_enable_map( 'openstreet' ),
		];

		$scripts['directorist-add-listing-gmap-custom-script-public'] = [
			'file_name' => 'global-add-listing-gmap-custom-script',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
			'section' => '__',
			'enable'  => Script_Helper::is_enable_map( 'google' ),
		];

		$scripts['directorist-pure-select-public'] = [
			'file_name' => 'global-pure-select',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public', // public || admin  || global
		];

		$scripts = array_merge( self::$js_scripts, $scripts);
		self::$js_scripts = $scripts;
	}

	/**
	 * Load Admin CSS Scripts
	 *
	 * @return void
	 */
	public static function add_admin_css_scripts() {
		$scripts = [];

		$scripts['directorist-admin-style'] = [
			'file_name' => 'admin-main',
			'base_path' => DIRECTORIST_CSS,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => 'admin',
			'section'   => '',
			// 'page'      => 'plugins.php',
		];

		$scripts = array_merge( self::$css_scripts, $scripts);
		self::$css_scripts = $scripts;
	}

	/**
	 * Load Admin JS Scripts
	 *
	 * @return void
	 */
	public static function add_admin_js_scripts() {
		$scripts = [];

		$scripts['directorist-admin-script'] = [
			'file_name'     => 'admin-main',
			'base_path'     => DIRECTORIST_JS,
			// 'deps'          => Script_Helper::get_admin_script_dependency(),
			'ver'           => self::$script_version,
			'group'         => 'admin',
			'section'       => '',
			'localize_data' => [
				[
					'object_name' => 'atbdp_admin_data',
					'data' => Script_Helper::get_admin_script_data()
				],
				[
					'object_name' => 'atbdp_public_data',
					'data' => Script_Helper::get_main_script_data()
				],
			],
		];

		$scripts['directorist-add-listing-admin'] = [
			'file_name' => 'global-add-listing',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'admin', // public || admin  || global
			// 'page'      => ['post-new.php', 'post.php'],
		];

		$scripts['directorist-multi-directory-archive'] = [
			'file_name' => 'admin-multi-directory-archive',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'admin', // public || admin  || global
			'page'      => ['at_biz_dir_page_atbdp-directory-types'],
		];

		$scripts['directorist-add-listing-openstreet-map-custom-script-admin'] = [
			'file_name' => 'global-add-listing-openstreet-map-custom-script',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'admin',                                        // public || admin  || global
			'enable'    => Script_Helper::is_enable_map( 'openstreet' ),
			'page'      => ['post-new.php', 'post.php'],
			// 'section'   => '__',
		];

		$scripts['directorist-add-listing-gmap-custom-script-admin'] = [
			'file_name' => 'global-add-listing-gmap-custom-script',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'admin',                                    // public || admin  || global
			'enable'    => Script_Helper::is_enable_map( 'google' ),
			'page'      => ['post-new.php', 'post.php'],
			// 'section'   => '__',
		];


		$scripts['directorist-multi-directory-builder'] = [
			'file_name'     => 'admin-multi-directory-builder',
			'base_path'     => DIRECTORIST_JS,
			'ver'           => self::$script_version,
			'group'         => 'admin',
			'page'          => [
				'at_biz_dir_page_atbdp-layout-builder',
				'at_biz_dir_page_atbdp-directory-types'
			],
			'localize_data' => [
				'object_name' => 'ajax_data',
				'data' => [ 'ajax_url' => admin_url('admin-ajax.php') ]
			],
		];

		$scripts['directorist-settings-manager'] = [
			'file_name'     => 'admin-settings-manager',
			'base_path'     => DIRECTORIST_JS,
			'ver'           => self::$script_version,
			'group'         => 'admin',
			'page'          => 'at_biz_dir_page_atbdp-settings',
			'localize_data' => [
				'object_name' => 'ajax_data',
				'data' => [ 'ajax_url' => admin_url('admin-ajax.php') ]
			],
		];

		$scripts['directorist-plugins'] = [
			'file_name' => 'admin-plugins',
			'base_path' => DIRECTORIST_JS,
			// 'deps'      => ['jquery'],
			'ver'       => self::$script_version,
			'group'     => 'admin',
			'section'   => '',
			'page'      => 'plugins.php',
			'enable'    => is_admin(),
		];

		$scripts['directorist-plupload-public'] = [
			'file_name' => 'global-directorist-plupload',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'public',
			'section'   => '__',
		];

		$scripts['directorist-plupload-admin'] = [
			'file_name' => 'global-directorist-plupload',
			'base_path' => DIRECTORIST_JS,
			'ver'       => self::$script_version,
			'group'     => 'admin',
			'section'   => '',
		];

		$scripts['directorist-import-export'] = [
			'file_name'     => 'admin-import-export',
			'base_path'     => DIRECTORIST_JS,
			// 'deps'          => ['jquery'],
			'ver'           => self::$script_version,
			'group'         => 'admin',
			'section'       => '',
			'page'          => 'at_biz_dir_page_tools',
			'enable'        => is_admin(),
			'localize_data' => [
				'object_name' => 'import_export_data',
				'data' => [ 'ajaxurl' => admin_url( 'admin-ajax.php' ) ]
			],
		];

		$scripts = array_merge( self::$js_scripts, $scripts);
		self::$js_scripts = $scripts;
	}

	/**
	 * Load Global CSS Scripts
	 *
	 * @return void
	 */
	public static function add_global_css_scripts() {
		// $scripts = [];

		// $scripts = array_merge( self::$css_scripts, $scripts);
		// self::$css_scripts = $scripts;
	}

	/**
	 * Load Global JS Scripts
	 *
	 * @return void
	 */
	public static function add_global_js_scripts() {
		$scripts = [];
		$common_asset_group = 'global';

		$scripts['directorist-global-script'] = [
			'file_name' => 'global-main',
			'base_path' => DIRECTORIST_JS,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => $common_asset_group,
		];

		$scripts['directorist-map-view'] = [
			'file_name' => 'global-map-view',
			'base_path' => DIRECTORIST_JS,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => $common_asset_group,
			'section'   => '_',
			'enable'    => Script_Helper::is_enable_map( 'google' )
		];

		$scripts['directorist-gmap-marker-clusterer'] = [
			'file_name' => 'global-markerclusterer',
			'base_path' => DIRECTORIST_JS,
			'deps'      => [],
			'ver'       => self::$script_version,
			'group'     => $common_asset_group,
			'section'   => '__',
			'enable'    => Script_Helper::is_enable_map( 'google' )
		];

		$scripts = array_merge( self::$js_scripts, $scripts);
		self::$js_scripts = $scripts;
	}

	// Ref: https://gist.github.com/Rodrigo54/93169db48194d470188f
	public static function minify_css( $input ) {
		if(trim($input) === "") return $input;
		return preg_replace(
			array(
				// Remove comment(s)
				'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
				// Remove unused white-space(s)
				'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
				// Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
				'#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
				// Replace `:0 0 0 0` with `:0`
				'#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
				// Replace `background-position:0` with `background-position:0 0`
				'#(background-position):0(?=[;\}])#si',
				// Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
				'#(?<=[\s:,\-])0+\.(\d+)#s',
				// Minify string value
				'#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
				'#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
				// Minify HEX color code
				'#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
				// Replace `(border|outline):none` with `(border|outline):0`
				'#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
				// Remove empty selector(s)
				'#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
			),
			array(
				'$1',
				'$1$2$3$4$5$6$7',
				'$1',
				':0',
				'$1:0 0',
				'.$1',
				'$1$3',
				'$1$2$4$5',
				'$1$2$3',
				'$1:0',
				'$1$2'
			),
			$input);
	}

	public static function dynamic_style() {
		$style_path = ATBDP_DIR . 'assets/other/style.php';

		ob_start();
		include $style_path;
		$style = ob_get_clean();
		$style = str_replace( ['<style>', '</style>'], '', $style );
		$style = self::minify_css( $style );
		return $style;
	}

	/**
	 * Enqueue Global Scripts
	 *
	 * @return void
	 */
	public static function enqueue_global_scripts( $page = '', $fource_enqueue = false ) {
		// Other
		self::enqueue_custom_color_picker_scripts();
		// wp_enqueue_script( 'jquery' );

		// CSS
		self::register_css_scripts();
		self::enqueue_css_scripts_by_group( [ 'group' => 'global', 'page' => $page, 'fource_enqueue' => $fource_enqueue ] );
		wp_add_inline_style( 'directorist-inline-style', self::dynamic_style() );

		// JS
		self::register_js_scripts();
		self::enqueue_js_scripts_by_group( [ 'group' => 'global', 'page' => $page, 'fource_enqueue' => $fource_enqueue ] );
	}

	/**
	 * Enqueue Public Scripts
	 *
	 * @return void
	 */
	public static function enqueue_public_scripts( $page = '', $fource_enqueue = false ) {
		// Load Assets
		self::load_assets();

		// Other
		self::enqueue_custom_color_picker_scripts();
		// wp_enqueue_script( 'jquery' );

		// CSS
		self::register_css_scripts();
		self::enqueue_css_scripts_by_group( [ 'group' => 'public', 'page' => $page, 'fource_enqueue' => $fource_enqueue ] );
		wp_add_inline_style( 'directorist-inline-style', self::dynamic_style() );

		// JS
		self::register_js_scripts();
		self::enqueue_js_scripts_by_group( [ 'group' => 'public', 'page' => $page, 'fource_enqueue' => $fource_enqueue ] );
	}


	/**
	 * Enqueue Admin Scripts
	 *
	 * @return void
	 */
	public static function enqueue_admin_scripts( $page = '' ) {
		// Load Assets
		self::load_assets();

		// Other
		self::enqueue_custom_color_picker_scripts();
		wp_enqueue_script( 'jquery' );
		wp_enqueue_media();

		// CSS
		self::register_css_scripts();
		self::enqueue_css_scripts_by_group( [ 'group' => 'admin', 'page' => $page ] );

		// JS
		self::register_js_scripts();
		self::enqueue_js_scripts_by_group( [ 'group' => 'admin', 'page' => $page ] );
	}

	/**
	 * Enqueue Block Editor Scripts
	 *
	 * @return void
	 */
	public static function enqueue_block_editor_assets( $page = '' ) {

		self::load_assets();

		// CSS
		self::register_css_scripts();
		self::enqueue_css_scripts_by_group( [ 'group' => 'block-editor', 'page' => $page ] );

		// JS
		self::register_js_scripts();
		self::enqueue_js_scripts_by_group( [ 'group' => 'block-editor', 'page' => $page ] );
	}


	/**
	 * Register CSS Scripts
	 *
	 * @return void
	 */
	public static function register_css_scripts( array $args = [] ) {
		$default = [ 'scripts' => self::$css_scripts ];
		$args    = array_merge( $default, $args );

		foreach( $args['scripts'] as $handle => $script_args ) {
			$default = [
				'file_name' => $handle,
				'base_path' => DIRECTORIST_CSS,
				'deps'      => [],
				'ver'       => self::$script_version,
				'media'     => 'all',
				'link'      => ''
			];

			$script_args = array_merge( $default, $script_args );
			$src = $script_args['base_path'] . self::get_script_file_name( $script_args ) . '.css';

			if ( ! empty( $script_args['link'] ) ) {
				$src = $script_args['link'];
			}

			wp_register_style( $handle, $src, $script_args['deps'], $script_args['ver'], $script_args['media']);
		}
	}

	/**
	 * Enqueue CSS Scripts
	 *
	 * @return void
	 */
	public static function enqueue_css_scripts_by_group( array $args = [] ) {
		$default = [ 'scripts' => self::$css_scripts, 'group' => 'public' ];
		$args    = array_merge( $default, $args );

		foreach( $args['scripts'] as $handle => $script_args ) {

			if ( ! self::can_enqueue_asset( $handle, $script_args, $args ) ) continue;

			wp_enqueue_style( $handle );
		}
	}


	/**
	 * Register JS Scripts by Group
	 *
	 * @param array $args
	 * @return void
	 */
	public static function register_js_scripts( array $args = [] ) {
		$default = [ 'scripts' => self::$js_scripts ];
		$args    = array_merge( $default, $args );

		foreach( $args['scripts'] as $handle => $script_args ) {
			$default = [
				'file_name' => $handle,
				'base_path' => DIRECTORIST_JS,
				'link'      => '',
				'deps'      => [],
				'ver'       => self::$script_version,
				'has_rtl'   => false,
				'in_footer' => true,
			];

			$script_args = array_merge( $default, $script_args );
			$src = $script_args['base_path'] . self::get_script_file_name( $script_args ) . '.js';

			if ( ! empty( $script_args['link'] ) ) {
				$src = $script_args['link'];
			}

			wp_register_script( $handle, $src, $script_args['deps'], $script_args['ver'], $script_args['in_footer']);
		}
	}

	/**
	 * Enqueue JS Scripts
	 *
	 * @return void
	 */
	public static function enqueue_js_scripts_by_group( array $args = [] ) {
		$default = [ 'scripts' => self::$js_scripts, 'group' => 'public' ];
		$args    = array_merge( $default, $args );

		foreach( $args['scripts'] as $handle => $script_args ) {

			if ( ! self::can_enqueue_asset( $handle, $script_args, $args ) ) continue;

			if ( ! empty( $script_args['before_enqueue'] ) ) {
				self::handle_script_before_enqueue_task( $script_args['before_enqueue'] );
			}

			wp_enqueue_script( $handle );
			self::add_localize_data_to_script( $handle, $script_args );
		}
	}

	// can_enqueue_asset
	public static function can_enqueue_asset( $script_id = '', $script_args = [], $group_args = [] ) {

		$in_group = true;
		if ( isset( $script_args['group'] ) ) {
			if ( is_string( $script_args['group'] ) && ( $group_args['group'] !== $script_args['group'] ) ) {
				$in_group = false;
			}

			if ( is_array( $script_args['group'] ) && ! in_array( $group_args['group'], $script_args['group'] ) ) {
				$in_group = false;
			}

			if (  is_string( $script_args['group'] ) && 'global' === $script_args['group']  ) {
				$in_group = true;
			}

			if (  is_array( $script_args['group'] ) && in_array( 'global', $script_args['group'] )  ) {
				$in_group = true;
			}
		}

		if ( ! $in_group ) return false;

		if ( ( ! empty( $script_args['fource_enqueue'] ) || ! empty( $group_args['fource_enqueue'] ) ) ) {
			return true;
		}

		if ( ( isset( $script_args['enable'] ) && false === $script_args['enable'] ) ) {
			return false;
		}

		if ( ! empty( $script_args['section'] ) ) return false;

		if ( ( isset( $group_args['page'] ) && isset( $script_args[ 'page' ] ) ) ) {
			if ( is_string( $script_args[ 'page' ] ) && $group_args['page'] !== $script_args[ 'page' ] ) return false;
			if ( is_array( $script_args[ 'page' ] ) && ! in_array( $group_args['page'], $script_args[ 'page' ] ) ) return false;
		}

		if (  ! self::script__verify_shortcode( $script_args, $script_id ) ) {
			return false;
		}

		return true;
	}

	// handle_script_before_enqueue_task
	public static function handle_script_before_enqueue_task( $task = [] ) {

		if ( is_array( $task ) && ! self::is_assoc_array( $task ) && ( count( $task ) > 1 )) {
			$class_name  = $task[0];
			$method_name = $task[1];
			$args        = ( isset( $task[2] ) ) ? $task[2] : '';

			if ( class_exists( $class_name ) && method_exists( $class_name, $method_name ) ) {
				$class = new $class_name;
				$class->$method_name( $args );
			}
		}
	}

	// script__verify_shortcode
	public static function script__verify_shortcode( $script_args = [], $handle = '' ) {
		if ( empty( $script_args['shortcode'] ) ) {
			return true;
		}

		if ( ! is_array( $script_args['shortcode'] ) ) {
			return true;
		}

		$disable = ( class_exists( 'Elementor\Plugin' ) ) ? true : DIRECTORIST_DISABLE_SHORTCODE_RESTRICTION_ON_SCRIPTS;
		$disable = apply_filters( 'directorist_disable_shortcode_restriction_on_scripts', $disable );

		if ( $disable ) { return true; }

		$match_found = 0;
		foreach ( $script_args['shortcode'] as $_shortcode ) {
			if ( self::has_shortcode( $_shortcode, $handle ) ) {
				$match_found++;
			}
		}

		if ( ! $match_found ) { return false; }

		return true;
	}

	// has_shortcode
	public static function has_shortcode( $shortcode = '', $handle = '' ) {
		global $post;
		$found = false;
		if ( is_a( $post, 'WP_Post' ) ) {
			$found = has_shortcode( $post->post_content, $shortcode );

			$debug = apply_filters( 'directorist_debug_shortcode_scripts', DIRECTORIST_DEBUG_SHORTCODE_SCRIPTS );
			$show_all = apply_filters( 'directorist_debug_shortcode_scripts_show_all', DIRECTORIST_DEBUG_SHORTCODE_SCRIPTS_SHOW_ALL );

			if ( $debug && ( $found || $show_all ) ) {
				directorist_console_log( [
					'handle' => $handle,
					'shortcode' => $shortcode,
					'found' => $found
				]);
			}
		}

		return $found;
	}

	/**
	 *  Add localize data to script
	 *
	 * @param string $handle
	 * @param array $script_args
	 * @return void
	 */
	public static function add_localize_data_to_script( $handle, $script_args ) {

		if ( ! is_array( $script_args ) ) { return; }
		if ( empty( $script_args['localize_data'] ) ) { return false; }

		if ( self::is_assoc_array( $script_args['localize_data'] ) ) {
			if ( ! self::has_valid_localize_data( $script_args['localize_data'] ) ) {
				return;
			}

			wp_localize_script( $handle, $script_args['localize_data']['object_name'], $script_args['localize_data']['data'] );
			return;
		}

		foreach ( $script_args['localize_data'] as $script_args_item ) {

			if ( ! self::has_valid_localize_data( $script_args_item ) ) {
				return;
			}

			wp_localize_script( $handle, $script_args_item['object_name'], $script_args_item['data'] );
		}
	}

	// has_valid_localize_data
	public static function has_valid_localize_data( array $localize_data = [] ) {

		if ( empty( $localize_data['object_name'] ) ) { return false; }
		if ( ! is_string( $localize_data['object_name'] ) ) { return false; }
		if ( empty( $localize_data['data'] ) ) { return false; }
		if ( ! is_array(  $localize_data['data'] ) ) { return false; }

		return true;
	}

	// is_assoc_array
	public static function is_assoc_array( array $arr = [] ) {
		if ( array() === $arr ) { return false; }

		return array_keys( $arr) !== range( 0, count($arr) - 1 );
	}


	/**
	 * Get Script File Name
	 *
	 * @param array $args
	 * @return $file_name
	 */
	public static function get_script_file_name( array $args = [] ) {
		$default = [ 'has_min' => true, 'has_rtl' => true ];
		$args    = array_merge( $default, $args );

		$file_name  = ( ! empty( $args['file_name'] ) ) ? $args['file_name'] : '';
		$has_min    = ( ! empty( $args['has_min'] ) ) ? true : false;
		$has_rtl    = ( ! empty( $args['has_rtl'] ) ) ? true : false;


		$load_min = self::load_min_files();
		$is_rtl   =  is_rtl();

		if ( $has_min && $load_min ) {
			$file_name = "{$file_name}.min";
		}

		if ( $has_rtl && $is_rtl ) {
			$file_name = "{$file_name}.rtl";
		}

		return $file_name;
	}

	/**
	 * Inject Scripts Meta
	 *
	 * @return void
	 */
	public static function inject_scripts_meta() {
		// Add js script meta
		foreach( self::$js_scripts as $handle => $script_args ) {
			// Inject WP dependency meta
			if (  empty( $script_args['file_name'] ) ||  empty( $script_args['group'] ) ) {
				continue;
			}

			$file_name = self::get_script_file_name( $script_args );
			$asset_path = ATBDP_DIR . "assets/dest/{$script_args['group']}/js/{$file_name}.asset.php";

			if ( ! file_exists( $asset_path ) ) { continue; }
			$asset_source = require( $asset_path );

			$deps = ( isset( $script_args['deps'] ) && is_array( $script_args['deps'] ) ) ? $script_args['deps'] : [];
			$ver  = ( isset( $script_args['ver'] ) && is_string( $script_args['ver'] ) ) ? $script_args['ver'] : '';

			if ( isset( $asset_source['dependencies'] ) ) {
				$deps = array_unique( array_merge( $deps, $asset_source['dependencies'] ) );
			}

			if ( isset( $asset_source['version'] ) ) {
				$ver =  $asset_source['version'];
			}

			self::$js_scripts[ $handle ][ 'deps' ] = $deps;
			self::$js_scripts[ $handle ][ 'ver' ]  = $ver;
		}
	}

	/**
	 * Enqueue Custom Color Picker Scripts
	 *
	 * @return void
	 */
	public static function enqueue_custom_color_picker_scripts() {
		wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), false, 1 );
		wp_enqueue_script( 'wp-color-picker', admin_url( 'js/color-picker.min.js' ), array( 'iris', 'wp-i18n' ), false, 1 );

		$colorpicker_l10n = array(
			'clear'         => __( 'Clear' ),
			'defaultString' => __( 'Default' ),
			'pick'          => __( 'Select Color' ),
			'current'       => __( 'Current Color' ),
		);

		wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );

	}
}