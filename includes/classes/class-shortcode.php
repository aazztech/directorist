<?php
/**
 * @author wpWax
 */

namespace Directorist;

if ( ! defined( 'ABSPATH' ) ) exit;

class ATBDP_Shortcode {

	public static $instance = null;
	public static $shortcodes = [];

	public function __construct() {

		if ( is_null( self::$instance ) ) {

			self::$instance = $this;
			
			self::$shortcodes = apply_filters( 'atbdp_shortcodes', [
				// Archive
				'directorist_all_listing' => [ $this, 'listing_archive' ],
				'directorist_category'    => [ $this, 'category_archive' ],
				'directorist_tag'         => [ $this, 'tag_archive' ],
				'directorist_location'    => [ $this, 'location_archive' ],
	
				// Taxonomy
				'directorist_all_categories' => [ $this, 'all_categories' ],
				'directorist_all_locations'  => [ $this, 'all_locations' ],
	
				// Search
				'directorist_search_listing' => [ $this, 'search_listing' ],
				'directorist_search_result'  => [ $this, 'search_result' ],
	
				// Author
				'directorist_author_profile' => [ $this, 'author_profile' ],
				'directorist_all_authors' 	 => [ $this, 'author_archive' ],
				'directorist_user_dashboard' => [ $this, 'user_dashboard' ],
	
				// Forms
				'directorist_add_listing'         => [ $this, 'add_listing' ],
				'directorist_custom_registration' => [ $this, 'user_registration' ],
				'directorist_user_login'          => [ $this, 'user_login' ],
	
				// Checkout
				'directorist_checkout'            => [ new \ATBDP_Checkout, 'display_checkout_content' ],
				'directorist_payment_receipt'     => [ new \ATBDP_Checkout, 'payment_receipt' ],
				'directorist_transaction_failure' => [ new \ATBDP_Checkout, 'transaction_failure' ],

				// Single
				'directorist_single_listings_header'  => [ $this, 'single_listings_header' ],
				'directorist_single_listings_section' => [ $this, 'single_listings_section' ],
	
				// Single -- legacy shortcode
				'directorist_listing_top_area'            => [ $this, 'empty_string' ],
				'directorist_listing_tags'                => [ $this, 'empty_string' ],
				'directorist_listing_custom_fields'       => [ $this, 'empty_string' ],
				'directorist_listing_video'               => [ $this, 'empty_string' ],
				'directorist_listing_map'                 => [ $this, 'empty_string' ],
				'directorist_listing_contact_information' => [ $this, 'empty_string' ],
				'directorist_listing_author_info'         => [ $this, 'empty_string' ],
				'directorist_listing_contact_owner'       => [ $this, 'empty_string' ],
				'directorist_listing_review'              => [ $this, 'empty_string' ],
				'directorist_related_listings'            => [ $this, 'empty_string' ],
	
			]);
	
			// Register Shorcodes
			foreach ( self::$shortcodes as $shortcode => $callback ) {
				add_shortcode( $shortcode, $callback);
			}
		}

		return self::$instance;
	}

	public function empty_string() {
		return '';
	}

	// single_listings_header
	public function single_listings_header( $atts ) {

		if ( !is_singular( ATBDP_POST_TYPE ) ) {
			return '';
		}

		$listing = Directorist_Single_Listing::instance();

		ob_start();
		echo '<div class="directorist-single-wrapper">';
		$listing->header_template();
		echo '<br>';
		echo '</div>';

		return ob_get_clean();
	}

	public function single_listings_section( $atts ) {

		if ( !is_singular( ATBDP_POST_TYPE ) ) {
			return '';
		}

		ob_start();
		$listing = Directorist_Single_Listing::instance();

		foreach ( $listing->content_data as $section ) {
			$section_label = preg_replace( '/\s/', '-' , strtolower( $section['label'] ) );

			$section_key = ( isset( $atts['key'] ) ) ? $atts['key'] : '';
			$section_keys = preg_split( '/\s*[,]\s/', $section_key );

			if ( ! empty( $section_keys ) && ! in_array( $section_label, $section_keys ) ) {
				continue;
			}

			$listing->section_template( $section );
		}

		return ob_get_clean();
	}

	public function listing_archive( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$listings = new Directorist_Listings( $atts );

		if ( empty( $atts[ 'shortcode' ] ) ) {
			$atts[ 'shortcode' ] = 'directorist_all_listing';
		}
	
		return $listings->render_shortcode( $atts );
	}

	public function category_archive( $atts ) {
		$atts             = !empty( $atts ) ? $atts : array();
		$category_slug    = get_query_var('atbdp_category');
		$atts['category'] = sanitize_text_field( $category_slug );
		
		$atts[ 'shortcode' ] = 'directorist_category';

		return $this->listing_archive( $atts );
	}

	public function tag_archive( $atts ) {
		$atts        = !empty( $atts ) ? $atts : array();
		$tag_slug    = get_query_var('atbdp_tag');
		$atts['tag'] = sanitize_text_field( $tag_slug );
		
		$atts[ 'shortcode' ] = 'directorist_tag';

		return $this->listing_archive( $atts );
	}

	public function location_archive( $atts ) {
		$atts        = !empty( $atts ) ? $atts : array();
		$tag_slug    = get_query_var('atbdp_location');
		$atts['location'] = sanitize_text_field( $tag_slug );

		$atts[ 'shortcode' ] = 'directorist_location';

		return $this->listing_archive( $atts );
	}

	public function all_categories( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$taxonomy = new Directorist_Listing_Taxonomy($atts, 'category');
		
		$atts[ 'shortcode' ] = 'directorist_all_categories';
		
		return $taxonomy->render_shortcode( $atts );
	}

	public function all_locations( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$taxonomy = new Directorist_Listing_Taxonomy($atts, 'location');
		
		$atts[ 'shortcode' ] = 'directorist_all_locations';
		
		return $taxonomy->render_shortcode( $atts );
	}

	public function search_listing( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$listing_type = '';
		if (!empty($atts['listing_type'])) {
			$listing_type = $atts['listing_type'];
		}
		$searchform = new Directorist_Listing_Search_Form( 'search_form', $listing_type, $atts );
		
		$atts[ 'shortcode' ] = 'directorist_search_listing';
		
		return $searchform->render_search_shortcode( $atts );
	}

	public function search_result( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$listings = new Directorist_Listings( $atts, 'search_result' );
		
		$atts[ 'shortcode' ] = 'directorist_search_result';
		
		return $listings->render_shortcode( $atts );
	}

	public function author_profile( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$author = Directorist_Listing_Author::instance();

		$atts[ 'shortcode' ] = 'directorist_author_profile';

		return $author->render_shortcode_author_profile($atts);
	}

	public function author_archive( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$author = Directorist_Listing_Author::instance();

		$atts[ 'shortcode' ] = 'directorist_members';

		return $author->render_shortcode_author_archive($atts);
	}

	public function user_dashboard( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$dashboard = Directorist_Listing_Dashboard::instance();
		
		$atts[ 'shortcode' ] = 'directorist_user_dashboard';
		
		return $dashboard->render_shortcode($atts);
	}

	public function add_listing( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$url = $_SERVER['REQUEST_URI'];
		$pattern = "/edit\/(\d+)/i";
		$id = preg_match($pattern, $url, $matches) ? (int) $matches[1] : '';
		$forms = Directorist_Listing_Form::instance($id);

		$atts[ 'shortcode' ] = 'directorist_add_listing';
		
		return $forms->render_shortcode($atts);
	}

	public function user_registration( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$account = Directorist_Account::instance();

		$atts[ 'shortcode' ] = 'directorist_custom_registration';

		return $account->render_shortcode_registration( $atts );
	}

	public function user_login( $atts ) {
		$atts = !empty( $atts ) ? $atts : array();
		$account = Directorist_Account::instance();

		$atts[ 'shortcode' ] = 'directorist_user_login';

		return $account->render_shortcode_login( $atts );
	}

}