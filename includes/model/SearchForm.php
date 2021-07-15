<?php
/**
 * @author wpWax
 */

namespace Directorist;

if ( ! defined( 'ABSPATH' ) ) exit;

class Directorist_Listing_Search_Form {

	// Search Shortcode
	public $options = [];
	public $type;
	public $listing_type;
	public $form_data;

	public $atts;
	public $defaults;
	public $params;

	public $show_title_subtitle;
	public $has_search_button;
	public $has_more_filters_button;
	public $logged_in_user_only;
	public $redirect_page_url;
	public $search_bar_title;
	public $search_bar_sub_title;
	public $search_button_text;
	public $more_filters_text;
	public $more_filters_display;
	public $show_connector;
	public $connectors_title;
	public $popular_cat_title;
	public $popular_cat_num;
	public $show_popular_category;
	public $directory_type;
	public $default_directory_type;

	// Common - Search Shortcode and Listing Header
	public $has_reset_filters_button;
	public $has_apply_filters_button;
	public $reset_filters_text;
	public $apply_filters_text;

	public $c_symbol;
	public $categories_fields;
	public $locations_fields;
	public $category_id;
	public $category_class;
	public $location_id;
	public $location_class;
	public $select_listing_map;

	public function __construct( $type, $listing_type, $atts = array() ) {

		$this->type = $type;
		$this->atts = $atts;

		$this->set_default_options();

		// Search form shortcode
		if ( $type == 'search_form' ) {
			$this->update_options_for_search_form();
			$this->prepare_search_data($atts);
		}

		if ( $listing_type ) {
			$this->listing_type = (int) $listing_type;
		}
		else {
			$this->listing_type = $this->get_default_listing_type();
		}

		// Search result page
		if ( $type == 'search_result' ) {
			$this->update_options_for_search_result_page();
			$this->prepare_search_data($atts);
		}

		// Listing Archive page
		if ( $type == 'listing' ) {
			$this->prepare_listing_data();
		}

		$this->form_data          = $this->build_form_data();

		$this->c_symbol           = atbdp_currency_symbol( get_directorist_option( 'g_currency', 'USD' ) );
		$this->categories_fields  = search_category_location_filter( $this->search_category_location_args(), ATBDP_CATEGORY );
		$this->locations_fields   = search_category_location_filter( $this->search_category_location_args(), ATBDP_LOCATION );
		$this->select_listing_map = get_directorist_option( 'select_listing_map', 'google' );
	}

	// set_default_options
	public function set_default_options() {
		$this->options['more_filters_fields']     = get_directorist_option( 'listing_filters_fields', array( 'search_text', 'search_category', 'search_location', 'search_price', 'search_price_range', 'search_rating', 'search_tag', 'search_custom_fields', 'radius_search' ) );
		$this->options['search_fields']           = get_directorist_option('search_tsc_fields', array('search_text', 'search_category', 'search_location'));
		$this->options['search_filters']          = get_directorist_option('listings_filters_button', array('search_reset_filters', 'search_apply_filters'));
		$this->options['search_listing_text']     = get_directorist_option('search_listing_text', __('Search Listing', 'directorist'));
		$this->options['search_more_filter']      = !empty( get_directorist_option( 'search_more_filter', 1 ) ) ? 'yes' : '';
		$this->options['search_more_filters']     = get_directorist_option('search_more_filters', __('More Filters', 'directorist'));
		$this->options['search_button']           = !empty( get_directorist_option( 'search_button', 1 ) ) ? 'yes' : '';
		$this->options['search_placeholder']      = get_directorist_option('listings_search_text_placeholder', __('What are you looking for?', 'directorist'));
		$this->options['filters_buttons']         = get_directorist_option( 'listings_filters_button', array( 'reset_button', 'apply_button' ) );

		$this->options['more_filters_button']        = get_directorist_option( 'listing_filters_button', 1 );
		$this->options['display_more_filter_icon']   = get_directorist_option('listing_filters_icon', 1);
		$this->options['display_search_button_icon'] = get_directorist_option('listing_filters_icon', 1);
		$this->options['open_filter_fields']         = get_directorist_option('listings_display_filter', 'sliding');

		$this->options['reset_filters_text']      = get_directorist_option('listings_reset_text', __('Reset Filters', 'directorist'));
		$this->options['apply_filters_text']      = get_directorist_option( 'listings_apply_text', __( 'Apply Filters', 'directorist' ) );
	}

	// update_options_for_search_result_page
	public function update_options_for_search_result_page() {
		$this->options['more_filters_fields']     = get_directorist_option('search_result_filters_fields', array('search_price', 'search_price_range', 'search_rating', 'search_tag', 'search_custom_fields', 'radius_search'));
		$this->options['search_filters']          = get_directorist_option('search_result_filters_button', array( 'reset_button', 'apply_button' ), true);
		$this->options['more_filters_button']     = get_directorist_option( 'search_result_filters_button_display', 1 );
		$this->options['reset_filters_text']      = get_directorist_option('sresult_reset_text', __('Reset Filters', 'directorist'));
		$this->options['apply_filters_text']      = get_directorist_option( 'sresult_apply_text', __( 'Apply Filters', 'directorist' ) );
	}

	// update_options_for_search_form
	public function update_options_for_search_form() {
		$this->options['more_filters_fields'] = get_directorist_option('search_more_filters_fields', array( 'search_price', 'search_price_range', 'search_rating', 'search_tag', 'search_custom_fields', 'radius_search'));

		$this->options['search_filters']             = get_directorist_option('search_filters', array('search_reset_filters', 'search_apply_filters'), true );
		$this->options['more_filters_button']        = get_directorist_option( 'search_more_filter', 1 );
		$this->options['display_more_filter_icon']   = get_directorist_option('search_more_filter_icon', 1);
		$this->options['display_search_button_icon'] = get_directorist_option('search_button_icon', 1);
		$this->options['open_filter_fields']         = get_directorist_option('home_display_filter', 'sliding');

		$this->options['reset_filters_text']      = get_directorist_option( 'search_reset_text', __('Reset Filters', 'directorist'));
		$this->options['apply_filters_text']      = get_directorist_option( 'search_apply_filter', __( 'Apply Filters', 'directorist' ) );
	}

	// prepare_search_data
	public function prepare_search_data($atts) {
		$search_more_filters_fields = $this->options['more_filters_fields'];
		$search_filters             = $this->options['search_filters'];

		$search_fields        = $search_more_filters_fields;
		$reset_filters_button = in_array('reset_button', $search_filters) ? 'yes' : '';
		$apply_filters_button = in_array('apply_button', $search_filters) ? 'yes' : '';

		if ( 'search_form' === $this->type ) {
			$search_fields = $this->options['search_fields'];
			$reset_filters_button = in_array('search_reset_filters', $search_filters) ? 'yes' : '';
			$apply_filters_button = in_array('search_apply_filters', $search_filters) ? 'yes' : '';
		}

		$this->defaults = array(
			'show_title_subtitle'    		=> 'yes',
			'search_bar_title'       		=> get_directorist_option('search_title', __("Search here", 'directorist')),
			'search_bar_sub_title'  		=> get_directorist_option('search_subtitle', __("Find the best match of your interest", 'directorist')),
			'search_button'          		=> $this->options['search_button'],
			'search_button_text'     		=> $this->options['search_listing_text'],
			'more_filters_button'    		=> ( $this->options['more_filters_button'] ) ? 'yes' : '',
			'more_filters_text'      		=> $this->options['search_more_filters'],
			'reset_filters_button'   		=> $reset_filters_button,
			'apply_filters_button'   		=> $apply_filters_button,
			'reset_filters_text'     		=> $this->options['reset_filters_text'],
			'apply_filters_text'     		=> $this->options['apply_filters_text'],
			'logged_in_user_only'    		=> '',
			'redirect_page_url'      		=> '',
			'more_filters_display'   		=> $this->options['open_filter_fields'],
			'directory_type'         		=> '',
			'default_directory_type'        => '',
			'show_popular_category'			=> ! empty( get_directorist_option('show_popular_category', 1 ) ) ? 'yes' : ''
		);

		$this->params = shortcode_atts( $this->defaults, $this->atts );

		$this->show_title_subtitle      = $this->params['show_title_subtitle'] == 'yes' ? true : false;
		$this->has_search_button        = $this->params['search_button'] == 'yes' ? true : false;
		$this->has_more_filters_button  = $this->params['more_filters_button'] == 'yes' ? true : false;
		$this->has_reset_filters_button = $this->params['reset_filters_button'] == 'yes' ? true : false;
		$this->has_apply_filters_button = $this->params['apply_filters_button'] == 'yes' ? true : false;
		$this->logged_in_user_only      = $this->params['logged_in_user_only'] == 'yes' ? true : false;
		$this->show_connector           = !empty( get_directorist_option('show_connector', 1) ) ? true : false;
		$this->show_popular_category    = ( 'yes' == $this->params['show_popular_category'] ) ? true : false;

		$this->search_bar_title     	= $this->params['search_bar_title'];
		$this->search_bar_sub_title 	= $this->params['search_bar_sub_title'];
		$this->search_button_text   	= $this->params['search_button_text'];
		$this->more_filters_text    	= $this->params['more_filters_text'];
		$this->reset_filters_text   	= $this->params['reset_filters_text'];
		$this->apply_filters_text   	= $this->params['apply_filters_text'];
		$this->more_filters_display 	= $this->params['more_filters_display'];
		$this->redirect_page_url    	= $this->params['redirect_page_url'];
		$this->directory_type           = !empty( $this->params['directory_type'] ) ? explode( ',', $this->params['directory_type'] ) : '';
		$this->default_directory_type   = !empty( $this->params['default_directory_type'] ) ? $this->params['default_directory_type'] : '';

		$this->category_id             = '';
		$this->category_class          = 'search_fields directorist-category-select';
		$this->location_id             = '';
		$this->location_class          = 'search_fields directorist-location-select';
		$this->connectors_title        = get_directorist_option('connectors_title', __('Or', 'directorist'));
		$this->popular_cat_title       = get_directorist_option('popular_cat_title', __('Browse by popular categories', 'directorist'));
		$this->popular_cat_num         = get_directorist_option('popular_cat_num', 10);
	}

	public function prepare_listing_data() {
		$filters_buttons                = get_directorist_option( 'listings_filters_button', array( 'reset_button', 'apply_button' ), true );
		$this->has_reset_filters_button = in_array( 'reset_button', $filters_buttons ) ? true : false;
		$this->has_apply_filters_button = in_array( 'apply_button', $filters_buttons ) ? true : false;
		$this->reset_filters_text       = get_directorist_option('listings_reset_text', __('Reset Filters', 'directorist'));
		$this->apply_filters_text       = get_directorist_option( 'listings_apply_text', __( 'Apply Filters', 'directorist' ) );

		$this->category_id             = 'cat-type';
		$this->category_class          = 'directory_field bdas-category-search';
		$this->location_id             = 'loc-type';
		$this->location_class          = 'directory_field bdas-category-location';
	}

	public function get_default_listing_type() {
		$listing_types = get_terms(
			array(
				'taxonomy'   => ATBDP_TYPE,
				'hide_empty' => false,
			)
		);

		foreach ( $listing_types as $type ) {
			$is_default = get_term_meta( $type->term_id, '_default', true );
			if ( $is_default ) {
				$current = $type->term_id;
				break;
			}
		}

		if( $this->default_directory_type ) {
			$default_type = get_term_by( 'slug', $this->default_directory_type, ATBDP_TYPE );
			$current 	  = $default_type ? $default_type->term_taxonomy_id : $current;
		}

		if( $this->directory_type ) {
			$current_id = true;
			foreach( $this->directory_type as $value ) {
				$default_type = get_term_by( 'slug', $value, ATBDP_TYPE );
				$term_id      = $default_type->term_taxonomy_id;
				if( $current == $term_id ) {
					$current_id = null;
					break;
				}
			}
			if( $current_id != null ) {
				$directory_types =  get_term_by( 'slug', $this->directory_type[0], ATBDP_TYPE );
				$current 		 = $directory_types->term_taxonomy_id;
			}
		}

		return (int) $current;
	}

	public function build_form_data() {
		$form_data          = array();
		$search_form_fields     = get_term_meta( $this->listing_type, 'search_form_fields', true );
		$submission_form_fields = get_term_meta( $this->listing_type, 'submission_form_fields', true );

		if ( !empty( $search_form_fields['fields'] ) ) {

			foreach ( $search_form_fields['fields'] as $key => $value ) {

				if ( ! is_array( $value) ) {
					continue;
				}

				$search_form_fields['fields'][$key]['field_key'] = '';
				$search_form_fields['fields'][$key]['options'] = [];

				$form_key = isset( $value['original_widget_key'] ) ? $value['original_widget_key'] : '';

				unset( $search_form_fields['fields'][$key]['widget_key'] );
				unset( $search_form_fields['fields'][$key]['original_widget_key'] );

				if ( $form_key ) {
					if ( !empty( $submission_form_fields['fields'][$form_key]['field_key'] ) ) {
						$search_form_fields['fields'][$key]['field_key'] = $submission_form_fields['fields'][$form_key]['field_key'];
					}

					if ( !empty( $submission_form_fields['fields'][$form_key]['options'] ) ) {
						$search_form_fields['fields'][$key]['options'] = $submission_form_fields['fields'][$form_key]['options'];
					}
				}

			}
		}

		if ( !empty( $search_form_fields['groups'] ) ) {
			foreach ( $search_form_fields['groups'] as $group ) {
				$section           = $group;
				$section['fields'] = array();
				foreach ( $group['fields'] as $field ) {
					$section['fields'][ $field ] = $search_form_fields['fields'][ $field ];
				}
				$form_data[] = $section;
			}
		}

		return $form_data;
	}

	public function buttons_template() {
		if ($this->has_reset_filters_button || $this->has_apply_filters_button) {
			Helper::get_template( 'search-form/buttons', array('searchform' => $this) );
		}
	}

	public function load_map_scripts() {
		wp_localize_script( 'directorist-geolocation', 'adbdp_geolocation', array( 'select_listing_map' => $this->select_listing_map ) );
		wp_enqueue_script( 'directorist-geolocation' );
	}

	public function load_radius_search_scripts( $data ) {
		$sliderjs = is_rtl() ? 'atbdp-range-slider-rtl' : 'atbdp-range-slider';
		wp_enqueue_script( $sliderjs );
		$radius_search_unit = !empty( $data['radius_search_unit'] ) ? $data['radius_search_unit'] : '';
		if ( 'kilometers' == $radius_search_unit ) {
			$miles = __( ' Kilometers', 'directorist' );
		}
		else {
			$miles = __( ' Miles', 'directorist' );
		}

		$value = !empty( $_REQUEST['miles'] ) ? $_REQUEST['miles'] : $data['default_radius_distance'];

		wp_localize_script( 'directorist-range-slider', 'atbdp_range_slider', apply_filters( 'directorist_range_slider_args', [
			'miles' => $miles,
			'slider_config' => [
				'minValue' => $value,
				'maxValue' => 1000,
			]
		]));
	}

	public function get_pricing_type() {
		$submission_form_fields = get_term_meta( $this->listing_type, 'submission_form_fields', true );
		$ptype = !empty( $submission_form_fields['fields']['pricing']['pricing_type'] ) ? $submission_form_fields['fields']['pricing']['pricing_type'] : 'both';
		return $ptype;
	}

	// custom field assign to category
	public function assign_to_category(){
		$submission_form_fields = get_term_meta( $this->listing_type , 'submission_form_fields', true );
		$category_id = isset( $_REQUEST['cat_id'] ) ? $_REQUEST['cat_id'] : '';
		$custom_field_key = array();
		$assign_to_cat = array();

		if( $submission_form_fields['fields'] ) {
			foreach( $submission_form_fields['fields'] as $field ) {
				if( ! empty( $field['assign_to'] ) && $field['assign_to'] == 'category' && $category_id != $field['category'] ) {
					$custom_field_key[] = $field['field_key'];
					$assign_to_cat[]	= $field['category'];
				}
			}
		}

		$category_custom_field = array( 
			'custom_field_key'	=> $custom_field_key,
			'assign_to_cat'		=> $assign_to_cat,
		);
		return $category_custom_field;
	}

	public function field_template( $field_data ) {
		$key = $field_data['field_key'];
		if ( $this->is_custom_field( $field_data ) ) {
			$value = !empty( $_REQUEST['custom_field'][$key] ) ? $_REQUEST['custom_field'][$key] : '';
		}
		else {
			$value = $key && isset( $_REQUEST[$key] ) ? $_REQUEST[$key] : '';
		}

		$args = array(
			'searchform' 		=> $this,
			'data'       		=> $field_data,
			'value'      		=> $value,
		);
		if ( $this->is_custom_field( $field_data ) && ( ! in_array( $field_data['field_key'], $this->assign_to_category()['custom_field_key'] ) ) ) {
			$template = 'search-form/custom-fields/' . $field_data['widget_name'];
		}
		else {
			$template = 'search-form/fields/' . $field_data['widget_name'];
		}

		$template = apply_filters( 'directorist_search_field_template', $template, $field_data );
		Helper::get_template( $template, $args );
	}

	public function is_custom_field( $data ) {
		$fields = [ 'checkbox', 'color_picker', 'date', 'file', 'number', 'radio', 'select', 'text', 'textarea', 'time', 'url' ];
		return in_array( $data['widget_name'], $fields ) ? true : false;
	}

	public function get_listing_type_data() {
		$listing_types = array();
		$args          = array(
			'taxonomy'   => ATBDP_TYPE,
			'hide_empty' => false,
		);
		if( $this->directory_type ) {
			$args['slug']     = $this->directory_type;
		}

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


	public function directory_type_nav_template() {
		$enable_multi_directory = get_directorist_option( 'enable_multi_directory', false );

		if( count( $this->get_listing_type_data() ) < 2 || empty( $enable_multi_directory ) ) {
			return;
		}

		Helper::get_template( 'search-form/directory-type-nav', [ 'searchform' => $this ] );
	}

	public function has_more_filters_icon() {
		$more_filters_icon = $this->options['display_more_filter_icon'];
		return !empty( $more_filters_icon ) ? true : false;
	}

	public function has_search_button_icon() {
		$search_button_icon = $this->options['display_search_button_icon'];
		return !empty( $search_button_icon ) ? true : false;
	}

	public function more_buttons_template() {
		Helper::get_template( 'search-form/more-buttons', [ 'searchform' => $this ] );
	}

	public function advanced_search_form_fields_template() {
		Helper::get_template( 'search-form/adv-search', array('searchform' => $this) );
	}

	public function top_categories() {
		$top_categories = [];

		$args = array(
			'type'          => ATBDP_POST_TYPE,
			'parent'        => 0,
			'orderby'       => 'count',
			'order'         => 'desc',
			'hide_empty'    => 1,
			'number'        => (int)$this->popular_cat_num,
			'taxonomy'      => ATBDP_CATEGORY,
			'no_found_rows' => true,
		);

		$cats = get_categories( $args );

		foreach ( $cats as $cat ) {
			$directory_type 	 = get_term_meta( $cat->term_id, '_directory_type', true );
			$directory_type 	 = ! empty( $directory_type ) ? $directory_type : array();
			$listing_type_id     = $this->listing_type;

			if( in_array( $listing_type_id, $directory_type ) ) {
				$top_categories[] = $cat;
			}
		}

		return $top_categories;
	}

	public function top_categories_template() {
		if ( $this->show_popular_category ) {
			$top_categories = $this->top_categories();
			$title = get_directorist_option( 'popular_cat_title', __( 'Browse by popular categories', 'directorist' ) );

			if ( !empty($top_categories) ) {
				$args = array(
					'searchform'      => $this,
					'top_categories'  => $top_categories,
					'title'           => $title,
				);
				Helper::get_template( 'search-form/top-cats', $args );
			}
		}
	}

	public function search_category_location_args() {
		return array(
			'parent'             => 0,
			'term_id'            => 0,
			'hide_empty'         => 0,
			'orderby'            => 'name',
			'order'              => 'asc',
			'show_count'         => 0,
			'single_only'        => 0,
			'pad_counts'         => true,
			'immediate_category' => 0,
			'active_term_id'     => 0,
			'ancestors'          => array(),
			'listing_type'		 => $this->listing_type,
			'assign_to_category' => $this->assign_to_category()
		);
	}

	public function price_value($arg) {
		if ( $arg == 'min' ) {
			return isset( $_REQUEST['price'] ) ? $_REQUEST['price'][0] : '';
		}

		if ( $arg == 'max' ) {
			return isset( $_REQUEST['price'] ) ? $_REQUEST['price'][1] : '';
		}

		return '';
	}

	public function the_price_range_input($range) {
		$checked = ! empty( $_REQUEST['price_range'] ) && $_REQUEST['price_range'] == $range ? ' checked="checked"' : '';
		printf('<input type="radio" name="price_range" value="%s"%s>', $range, $checked);
	}

	public function render_search_shortcode( $atts = [] ) {

		if ( $this->logged_in_user_only && ! atbdp_logged_in_user() ) {
			return ATBDP()->helper->guard( array('type' => 'auth') );
		}

		if ($this->redirect_page_url) {
			$redirect = '<script>window.location="' . esc_url($this->redirect_page_url) . '"</script>';
			return $redirect;
		}

		$this->search_listing_scripts_styles();

		ob_start();
		if ( ! empty( $atts['shortcode'] ) ) { Helper::add_shortcode_comment( $atts['shortcode'] ); }
		echo Helper::get_template_contents( 'search-form-contents', [ 'searchform' => $this ] );

		return ob_get_clean();
	}

	public function search_listing_scripts_styles() {
		wp_enqueue_script( 'directorist-search-form-listing' );
		wp_enqueue_script( 'directorist-range-slider' );
		wp_enqueue_script( 'directorist-search-listing' );

		$data = Script_Helper::get_search_script_data( [ 'directory_type_id' => $this->listing_type ] );
		wp_localize_script( 'directorist-search-form-listing', 'atbdp_search_listing', $data );
		wp_localize_script( 'directorist-search-listing', 'atbdp_search', [
			'ajaxnonce' => wp_create_nonce('bdas_ajax_nonce'),
			'ajax_url' => admin_url('admin-ajax.php'),
		]);
		wp_localize_script( 'directorist-search-listing', 'atbdp_search_listing', $data );
		wp_localize_script( 'directorist-range-slider', 'atbdp_range_slider', $data );
	}

	public function listing_type_slug() {
		$term_data = get_term( $this->listing_type, ATBDP_TYPE );
		return $term_data->slug;
	}

	public function background_img_style() {
		$bgimg = get_directorist_option('search_home_bg');
		$style = !empty( $bgimg ) ? sprintf( "background-image: url(%s)", esc_url( $bgimg ) ) : '';
		return $style;
	}

	public function border_class() {
		$search_border = get_directorist_option( 'search_border', 1 );
		return empty( $search_border ) ? 'directorist-no-search-border' : 'directorist-with-search-border';
	}

	public function category_icon_class($cat) {
		$icon = get_cat_icon($cat->term_id);
		$icon_type = substr($icon, 0, 2);
		$icon_class = ('la' === $icon_type) ? $icon_type . ' ' . $icon : 'fa ' . $icon;
		return $icon_class;
	}

	public function rating_field_data() {
		$rating_options = array(
			array(
				'selected' => '',
				'value'    => '',
				'label'    => __( 'Select Ratings', 'directorist' ),
			),
			array(
				'selected' => ( ! empty( $_REQUEST['search_by_rating'] ) && '5' == $_REQUEST['search_by_rating'] ) ? ' selected' : '',
				'value'    => '5',
				'label'    => __( '5 Star', 'directorist' ),
			),
			array(
				'selected' => ( ! empty( $_REQUEST['search_by_rating'] ) && '4' == $_REQUEST['search_by_rating'] ) ? ' selected' : '',
				'value'    => '4',
				'label'    => __( '4 Star & Up', 'directorist' ),
			),
			array(
				'selected' => ( ! empty( $_REQUEST['search_by_rating'] ) && '3' == $_REQUEST['search_by_rating'] ) ? ' selected' : '',
				'value'    => '3',
				'label'    => __( '3 Star & Up', 'directorist' ),
			),
			array(
				'selected' => ( ! empty( $_REQUEST['search_by_rating'] ) && '2' == $_REQUEST['search_by_rating'] ) ? ' selected' : '',
				'value'    => '2',
				'label'    => __( '2 Star & Up', 'directorist' ),
			),
			array(
				'selected' => ( ! empty( $_REQUEST['search_by_rating'] ) && '1' == $_REQUEST['search_by_rating'] ) ? ' selected' : '',
				'value'    => '1',
				'label'    => __( '1 Star & Up', 'directorist' ),
			),
		);

		return $rating_options;
	}

	public function listing_tag_terms($tag_source='all_tags') {
		$category_slug      = get_query_var( 'atbdp_category' );
		$category           = get_term_by( 'slug', $category_slug, ATBDP_CATEGORY );
		$category_id        = ! empty( $category->term_id ) ? $category->term_id : '';
		$tag_args           = array(
			'post_type' => ATBDP_POST_TYPE,
			'tax_query' => array(
				array(
					'taxonomy' => ATBDP_CATEGORY,
					'terms'    => ! empty( $_REQUEST['in_cat'] ) ? $_REQUEST['in_cat'] : $category_id,
				),
			),
		);
		$category_select = ! empty( $_REQUEST['in_cat'] ) ? $_REQUEST['in_cat'] : $category_id;
		$tag_posts       = get_posts( $tag_args );
		if ( ! empty( $tag_posts ) ) {
			foreach ( $tag_posts as $tag_post ) {
				$tag_id[] = $tag_post->ID;
			}
		}
		$tag_id = ! empty( $tag_id ) ? $tag_id : '';
		$terms  = wp_get_object_terms( $tag_id, ATBDP_TAGS );

		if ( 'all_tags' == $tag_source || empty( $category_select ) ) {
			$terms = get_terms( ATBDP_TAGS );
		}

		if ( ! empty( $terms ) ) {
			return $terms;
		} else {
			return array();
		}
	}
}