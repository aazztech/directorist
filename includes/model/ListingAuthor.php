<?php
/**
 * @author wpWax
 */

namespace Directorist;

if ( ! defined( 'ABSPATH' ) ) exit;

class Directorist_Listing_Author {

	protected static $instance = null;

	public $id;
	public $all_listings;
	public $rating;
	public $total_review;

	public $listing_types;
	public $current_listing_type;

	private function __construct() {
		$this->prepare_data();
	}

	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function get_id() {
		return $this->id;
	}

	private function get_all_posts() {
		$args = array(
			'post_type'      => ATBDP_POST_TYPE,
			'post_status'    => 'publish',
			'author'         => $this->id,
			'orderby'        => 'post_date',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		);

		$posts = \ATBDP_Listings_Data_Store::get_archive_listings_query( $args, [ 'cache' => false ]);
		return $posts;
	}

	// extract_user_id
	public function extract_user_id( $user_id = '' ) {
		$extracted_user_id = ( is_numeric( $user_id ) ) ? $user_id : get_current_user_id();
		
		if ( is_string( $user_id ) && ! empty( $user_id ) ) {
			$user = get_user_by( 'login', $user_id );
			
			if ( $user ) {
				$extracted_user_id = $user->ID;
			}
		}
		
		$extracted_user_id = intval( $extracted_user_id );

		return $extracted_user_id;
	}

	function prepare_data() {
		$this->listing_types        = $this->get_listing_types();
		$this->current_listing_type = $this->get_current_listing_type();

		$this->id = $this->extract_user_id( get_query_var( 'author_id' ) );

		if ( ! $this->id ) {
			return \ATBDP_Helper::guard( [ 'type' => '404' ] );
		}
		
		$this->all_listings = $this->get_all_posts();
		$this->get_rating();

	}

	public function get_listing_types() {
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

	public function get_current_listing_type() {
		$listing_types      = $this->get_listing_types();
		$listing_type_count = count( $listing_types );

		$current = !empty($listing_types) ? array_key_first( $listing_types ) : '';

		if ( ! empty( get_query_var( 'directory_type' ) ) ) {
			$current = get_query_var( 'directory_type' );
		}
		else {

			foreach ( $listing_types as $id => $type ) {
				$is_default = get_term_meta( $id, '_default', true );
				if ( $is_default ) {
					$current = $id;
					break;
				}
			}
		}

		if( ! is_numeric( $current ) ) {
			$term = get_term_by( 'slug', $current, ATBDP_TYPE );
			$current = ( ! empty( $term ) ) ? $term->term_id : 0;
		}

		return (int) $current;
	}

	// Hooks ------------
	public function archive_type( $listings ) {
		$count = count( $listings->listing_types );
		$enable_multi_directory = get_directorist_option( 'enable_multi_directory', false );
		if ( $count > 1 && ! empty( $enable_multi_directory ) ) {
			Helper::get_template( 'archive/directory-type-nav', array('listings' => $listings) );
		}
	}

	public function get_rating() {
		$user_listings = $this->all_listings;

		$review_in_post = 0;
		$all_reviews    = 0;

		if ( ! empty( $user_listings->ids ) ) :
			// Prime caches to reduce future queries.
			if ( ! empty( $user_listings->ids ) && is_callable( '_prime_post_caches' ) ) {
				_prime_post_caches( $user_listings->ids );
			}
			
			$original_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : get_post();

			foreach ( $user_listings->ids as $listings_id ) :
				$GLOBALS['post'] = get_post( $listings_id );
				setup_postdata( $GLOBALS['post'] );

				$average = ATBDP()->review->get_average( $listings_id );
				if ( ! empty( $average ) ) {
					$averagee = array( $average );
					foreach ( $averagee as $key ) {
						$all_reviews += $key;
					}
					$review_in_post++;
				}
			endforeach;

			$GLOBALS['post'] = $original_post;
            wp_reset_postdata();
		endif;

		$author_rating = ( ! empty( $all_reviews ) && ! empty( $review_in_post ) ) ? ( $all_reviews / $review_in_post ) : 0;
		$author_rating = substr( $author_rating, '0', '3' );

		$this->rating       = $author_rating;
		$this->total_review = $review_in_post;

		return $author_rating;
	}

	public function get_review_count() {
		$user_listings = $this->all_listings;

		$review_in_post = 0;

		if ( ! empty( $user_listings->ids ) ) :
			// Prime caches to reduce future queries.
			if ( ! empty( $user_listings->ids ) && is_callable( '_prime_post_caches' ) ) {
				_prime_post_caches( $user_listings->ids );
			}

			$original_post = $GLOBALS['post'];

			foreach ( $user_listings->ids as $listings_id ) :
				$GLOBALS['post'] = get_post( $listings_id );
				setup_postdata( $GLOBALS['post'] );

				$average = ATBDP()->review->get_average( $listings_id );
				if ( ! empty( $average ) ) {
					$review_in_post++;
				}
			endforeach;

			$GLOBALS['post'] = $original_post;
            wp_reset_postdata();
		endif;

		return $review_in_post;
	}

	private function enqueue_scripts() {
		wp_enqueue_script( 'directorist-search-form-listing' );
        wp_enqueue_script( 'directorist-search-listing' );

		$data = Script_Helper::get_search_script_data();
		wp_localize_script( 'directorist-search-form-listing', 'atbdp_search_listing', $data );
		wp_localize_script( 'directorist-search-listing', 'atbdp_search', [
			'ajaxnonce' => wp_create_nonce('bdas_ajax_nonce'),
			'ajax_url' => admin_url('admin-ajax.php'),
			'added_favourite' => __('Added to favorite', 'directorist'),
			'please_login' => __('Please login first', 'directorist')
		]);
		wp_localize_script( 'directorist-search-listing', 'atbdp_search_listing', $data );
	}

	public function author_listings_query() {
		$category = ! empty( $_GET['category'] ) ? $_GET['category'] : '';
		$paged    = atbdp_get_paged_num();
		$paginate = get_directorist_option( 'paginate_author_listings', 1 );

		$args = array(
			'post_type'      => ATBDP_POST_TYPE,
			'post_status'    => 'publish',
			'author'         => $this->get_id(),
			'posts_per_page' => (int)get_directorist_option( 'all_listing_page_items', 6 ),
		);

		if ( ! empty( $paginate ) ) {
			$args['paged'] = $paged;
		} else {
			$args['no_found_rows'] = true;
		}

		if ( ! empty( $this->current_listing_type ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy'         => ATBDP_TYPE,
					'field'            => 'term_id',
					'terms'            => $this->current_listing_type ,
					'include_children' => true, /*@todo; Add option to include children or exclude it*/
				),
			);
		}

		if ( ! empty( $category ) ) {
			$category = array(
				array(
					'taxonomy'         => ATBDP_CATEGORY,
					'field'            => 'slug',
					'terms'            => ! empty( $category ) ? $category : '',
					'include_children' => true, /*@todo; Add option to include children or exclude it*/
				),
			);
		}
		
		if ( ! empty( $category ) ) {
			$args['tax_query'] = $category;
		}
		$meta_queries   = array();
		$meta_queries['expired'] = array(
			array(
				'key'     => '_listing_status',
				'value'   => 'expired',
				'compare' => '!=',
			),
		);

		$meta_queries       = apply_filters( 'atbdp_author_listings_meta_queries', $meta_queries );
		$count_meta_queries = count( $meta_queries );
		if ( $count_meta_queries ) {
			$args['meta_query'] = ( $count_meta_queries > 1 ) ? array_merge( array( 'relation' => 'AND' ), $meta_queries ) : $meta_queries;
		}

		return $args;
	}

	public function avatar_html() {
		$html = '';
		$author_id = $this->id;
		$u_pro_pic = get_user_meta( $author_id, 'pro_pic', true );

		if ( !empty( $u_pro_pic ) ) {
			$html = wp_get_attachment_image( $u_pro_pic );
		}

		if ( !$html ) {
			$html = get_avatar( $author_id );
		}

		return $html;
	}

	public function member_since_text() {
		$author_id = $this->id;
		$user_registered = get_the_author_meta('user_registered', $author_id);
		$member_since_text = sprintf(__('Member since %s ago', 'directorist'), human_time_diff(strtotime($user_registered), current_time('timestamp')));
		return $member_since_text;
	}

	public function review_count_html() {
		$review_count = $this->total_review;
		$review_count_html = sprintf( _nx( '<span>%s</span>Review', '<span>%s</span>Reviews', $review_count, 'author review count', 'directorist' ), $review_count );
		return $review_count_html;
	}

	public function listing_count_html() {
		$listing_count = ! empty( $this->all_listings ) ? $this->all_listings->total : '';
		$listing_count_html = sprintf( _nx( '<span>%s</span>Listing', '<span>%s</span>Listings', $listing_count, 'author review count', 'directorist' ), $listing_count );
		return $listing_count_html;
	}

	public function review_enabled() {
		return get_directorist_option('enable_review', 1);
	}

	public function rating_count() {
		return $this->rating;
	}

	public function display_name() {
		$author_id = $this->id;
		return get_the_author_meta('display_name', $author_id);
	}

	public function header_template() {
		$author_id = $this->id;
		Helper::get_template( 'author/header', [ 'author' => $this ] );
	}

	public function about_template() {
		$author_id = $this->id;

		$bio = get_user_meta($author_id, 'description', true);
		$bio = trim( $bio );

		$display_email = get_directorist_option('display_author_email', 'public');

		if ( $display_email == 'public' ) {
			$email_endabled = true;
		}
		elseif ( $display_email == 'logged_in' && atbdp_logged_in_user() ) {
			$email_endabled = true;
		}
		else {
			$email_endabled = false;
		}

		$args = array(
			'author'         => $this,
			'bio'            => nl2br( $bio ),
			'address'        => get_user_meta($author_id, 'address', true),
			'phone'          => get_user_meta($author_id, 'atbdp_phone', true),
			'email_endabled' => $email_endabled,
			'email'          => get_the_author_meta('user_email', $author_id),
			'website'        => get_the_author_meta('user_url', $author_id),
			'facebook'       => get_user_meta($author_id, 'atbdp_facebook', true),
			'twitter'        => get_user_meta($author_id, 'atbdp_twitter', true),
			'linkedin'       => get_user_meta($author_id, 'atbdp_linkedin', true),
			'youtube'        => get_user_meta($author_id, 'atbdp_youtube', true),
		);

		Helper::get_template( 'author/about', $args );
	}

	public function author_listings_template() {
		$args = array(
			'author'   => $this,
			'listings' => $this->get_listings(),
		);

		Helper::get_template( 'author/listings', $args );
	}

	public function get_listings() {
		$query    = $this->author_listings_query();
		$listings = new Directorist_Listings( NULL, NULL, $query, ['cache' => false] );
		return $listings;
	}

	public function cat_filter_enabled() {
		return get_directorist_option( 'author_cat_filter', 1 );
	}

	public function get_listing_categories() {
		return get_terms( ATBDP_CATEGORY , array( 'hide_empty' => 0 ) );
	}

	public function listing_pagination_enabled() {
		return get_directorist_option( 'paginate_author_listings', 1 );
	}

	public function render_shortcode_author_profile( $atts ) {

		$atts = shortcode_atts( array(
			'logged_in_user_only' => '',
			'redirect_page_url'   => '',
		), $atts );

		$logged_in_user_only = $atts['logged_in_user_only'];
		$redirect_page_url   = $atts['redirect_page_url'];

		if ( $redirect_page_url ) {
			$redirect = '<script>window.location="' . esc_url( $redirect_page_url ) . '"</script>';

			return $redirect;
		}

		$this->enqueue_scripts();

		if ( 'yes' === $logged_in_user_only && ! atbdp_logged_in_user() ) {
			return ATBDP()->helper->guard( array('type' => 'auth') );
		}

		ob_start();
		if ( ! empty( $atts['shortcode'] ) ) { Helper::add_shortcode_comment( $atts['shortcode'] ); }
		echo Helper::get_template_contents( 'author-contents', array( 'author' => $this ) );

		return ob_get_clean();
	}

	public function render_shortcode_author_archive( $atts = [] ) {
		
		ob_start();
		$all_authors_role	        =	get_directorist_option( 'all_authors_role', true );
		$all_authors_select_role	=	get_directorist_option( 'all_authors_select_role', 'all' );
		$args = array();
		if( ! empty( $all_authors_role ) && 'all' != $all_authors_select_role ) {
			$args = array( 'role__in' => array( $all_authors_select_role ) );
		}
		$args = array(
			'all_authors' 						=> get_users( $args ),
			'alphabets'	  						=> range( 'A', 'Z' ),
			'all_authors_columns'				=> get_directorist_option( 'all_authors_columns', 3 ),
			'all_authors_sorting'				=> get_directorist_option( 'all_authors_sorting', true ),
			'all_authors_image'					=> get_directorist_option( 'all_authors_image', true ),
			'all_authors_name'					=> get_directorist_option( 'all_authors_name', true ),
			'all_authors_role'					=> $all_authors_role,
			'all_authors_info'					=> get_directorist_option( 'all_authors_info', true ),
			'all_authors_description'			=> get_directorist_option( 'all_authors_description', true ),
			'all_authors_description_limit'		=> get_directorist_option( 'all_authors_description_limit', 13 ),
			'all_authors_social_info'			=> get_directorist_option( 'all_authors_social_info', true ),
			'all_authors_button'				=> get_directorist_option( 'all_authors_button', true ),
			'all_authors_button_text'			=> get_directorist_option( 'all_authors_button_text', 'View All Listings' ),
		);
		if ( ! empty( $atts['shortcode'] ) ) { Helper::add_shortcode_comment( $atts['shortcode'] ); }
		echo Helper::get_template_contents( 'author/archive', $args );

		return ob_get_clean();
	}
}