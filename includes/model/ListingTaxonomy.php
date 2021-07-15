<?php
/**
 * @author wpWax
 */

namespace Directorist;

use \ATBDP_Permalink;

if ( ! defined( 'ABSPATH' ) ) exit;

class Directorist_Listing_Taxonomy {

	public $atts;
	public $type;
	public $tax;

	public $listing_types;
	public $current_listing_type;

	public $view;
	public $orderby;
	public $order;
	public $per_page;
	public $columns;
	public $slug;
	public $logged_in_user_only;
	public $redirect_page_url;
	public $directory_type;
	public $directory_type_count;
	public $default_directory_type;

	public $show_count;
	public $hide_empty;
	public $depth;
	public $terms;

	public function __construct( $atts = array(), $type = 'category' ) {
		
		$categories_view = get_directorist_option('display_categories_as', 'grid');
		$categories_orderby = get_directorist_option('order_category_by', 'id');
		$categories_order = get_directorist_option('sort_category_by', 'asc');
		$categories_columns = get_directorist_option('categories_column_number', 3);
		$categories_show_count = !empty( get_directorist_option('display_listing_count', 1 ) ) ? true : false;
		$categories_hide_empty = !empty( get_directorist_option('hide_empty_categories') ) ? true : false;

		$locations_view = get_directorist_option('display_locations_as', 'grid');
		$locations_orderby = get_directorist_option('order_location_by', 'id');
		$locations_order = get_directorist_option('sort_location_by', 'asc');
		$locations_columns = get_directorist_option('locations_column_number', 3);
		$locations_show_count = !empty( get_directorist_option('display_location_listing_count', 1 ) ) ? true : false;
		$locations_hide_empty = !empty( get_directorist_option('hide_empty_locations') ) ? true : false;
		$atts = shortcode_atts( apply_filters( 'directorist_taxonomy_shortcode_attributes', array(
			'view'                		  => ( 'category' == $type ) ? $categories_view : $locations_view ,
			'orderby'            		  => ( 'category' == $type ) ? $categories_orderby : $locations_orderby,
			'order'              		  => ( 'category' == $type ) ? $categories_order : $locations_order,
			'cat_per_page'       		  => 100,
			'loc_per_page'        		  => 100,
			'columns'             		  => ( 'category' == $type ) ? $categories_columns : $locations_columns,
			'slug'                		  => '',
			'logged_in_user_only' 		  => '',
			'redirect_page_url'   		  => '',
			'directory_type'	  		  => '',
			'default_directory_type'	  => '',
		) ), $atts);
		
		$this->atts                = $atts;
		$this->type                = $type;
		$this->tax                 = ($type == 'category') ? ATBDP_CATEGORY : ATBDP_LOCATION;

		$this->view                	 	 = $atts['view'];
		$this->orderby             	 	 = $atts['orderby'];
		$this->order               	 	 = $atts['order'];
		$this->per_page            	     = ($type == 'category') ? $atts['cat_per_page'] : $atts['loc_per_page'];
		$this->columns              	 = ! empty( $atts['columns'] ) ? $atts['columns'] : 3;
		$this->slug                 	 = $atts['slug'];
		$this->logged_in_user_only  	 = $atts['logged_in_user_only'] == 'yes' ? true : false;
		$this->redirect_page_url    	 = $atts['redirect_page_url'];
		$this->directory_type       	 = ! empty( $atts['directory_type'] ) ? explode( ',', $atts['directory_type'] ) : array();
		$this->directory_type_count 	 = ! empty( $this->directory_type ) ? count( $this->directory_type ) : 0;
		$this->default_directory_type    = $atts['default_directory_type'];
		
		$this->show_count = ( 'category' == $type ) ? $categories_show_count : $locations_show_count;
		$this->hide_empty = ( 'category' == $type ) ? $categories_hide_empty : $locations_hide_empty;
		$this->depth      = ($type == 'category') ? get_directorist_option('categories_depth_number', 1) : get_directorist_option('locations_depth_number', 1);
		$this->listing_types              = $this->get_listing_types();
		$this->current_listing_type       = $this->get_current_listing_type();
		//$this->taxonomy_from_directory_type();
		$this->set_terms();
		
		
	}

	public function set_terms(){
		$args = array(
			'orderby'      => $this->orderby,
			'order'        => $this->order,
			'hide_empty'   => $this->hide_empty,
			'parent'       => 0,
			'hierarchical' => $this->hide_empty,
			'slug'         => !empty($this->slug) ? explode(',', $this->slug) : '',
		);

		if ( $this->type == 'category' ) {
			$args = apply_filters('atbdp_all_categories_argument', $args);
		}
		else {
			$args = apply_filters('atbdp_all_locations_argument', $args);
		}
		$terms = get_terms($this->tax, $args);
		$terms = array_slice($terms, 0, $this->per_page);

		$this->terms = $terms;
	}

	public function grid_count_html($term,$total) {
		$html = '';

		if ( $this->type == 'category' ) {
			if ($this->show_count) {
				$html = "<span>(" . $total . ")</span>";
			}
            /**
             * @since 5.0.0
             */
            return apply_filters('atbdp_all_categories_after_category_name', $html, $term);
        }

        else {
        	if ($this->show_count) {
        		$html = "<p>(" . $total . ")</p>";
        	}
            /**
             * @since 5.0.0
             */
            return apply_filters('atbdp_all_locations_after_location_name', $html, $term);
        }
    }

    public function list_count_html($term,$total) {
    	$html = '';
    	if ($this->show_count) {
    		$html = ' (' .  $total . ')';
    	}
    	return $html;
    }

    public function subterms_html($term){

    	if ($this->depth <= 0) {
    		return;
    	}

    	$args = array(
    		'orderby'      => $this->orderby,
    		'order'        => $this->order,
    		'hide_empty'   => $this->hide_empty,
    		'parent'       => $term->term_id,
    		'hierarchical' => false
    	);

    	$terms = get_terms($this->tax, $args);
    	$html = '';

    	if (count($terms) > 0) {

    		--$this->depth;

    		$html .= '<ul class="list-unstyled atbdp_child_category">';

    		foreach ($terms as $term) {
				
    			$child_category = get_term_children($term->term_id, $this->tax);
    			$plus_icon = !empty($child_category) ? '<span class="expander">+</span>' : '';
    			$count = 0;
    			if ($this->hide_empty || $this->show_count) {
    				$count = ( $this->type == 'category' ) ? atbdp_listings_count_by_category($term->term_id) : atbdp_listings_count_by_location($term->term_id);

    				if ($this->hide_empty && 0 == $count) continue;
				}
				if( ! empty( $_GET['directory_type'] ) ) {
					$directory_type = $_GET['directory_type'];
				} else {
					$directory_type = ( 1 == $this->directory_type_count ) ? $this->directory_type[0] : '';
				}

    			$permalink = ( $this->type == 'category' ) ? ATBDP_Permalink::atbdp_get_category_page( $term, $directory_type ) : ATBDP_Permalink::atbdp_get_location_page( $term, $directory_type );

    			$html .= '<li>';
    			$html .= '<a href=" ' . $permalink . ' ">';
    			$html .= $term->name;
    			if ($this->show_count) {
    				$html .= ' (' . $count . ')';
    			}
    			$html .= "</a>$plus_icon";
    			$html .= $this->subterms_html($term);
    			$html .= '</li>';
    		}

    		$html .= '</ul>';
    	}

    	return $html;
    }

    public function tax_data() {
    	$result = array();

    	foreach ($this->terms as $term) {
			$directory_type_meta  = get_term_meta( $term->term_id, '_directory_type', true );
			$directory_type_meta  = ! empty( $directory_type_meta ) ? $directory_type_meta : array();
			$directory_type_meta  = is_array( $directory_type_meta ) ? $directory_type_meta : array( $directory_type_meta );

			$listing_type_meta = array();
			foreach( $directory_type_meta as $type ) {

				if( is_numeric( $type ) ) {
					$get_type = get_term_by( 'term_id', $type, ATBDP_TYPE );
					$listing_type_meta[] = ! empty( $get_type ) ? $get_type->slug : '';
				} else {

					$listing_type_meta[] = $type;

				}

			}
			$current_type		  = $this->current_listing_type;
			$current_slug = '';
			if( $current_type ) {
				$type    	   = get_term_by( 'id', $current_type, ATBDP_TYPE );
				$current_slug  = $type ? $type->slug : '';
			}
			$get_current_url_type = isset( $_GET['directory_type'] ) ? $_GET['directory_type'] : $current_type;
			if( in_array( $current_slug, $listing_type_meta ) || 'all' == $get_current_url_type ) {
				$current_listing_type   = $this->current_listing_type;
				$count 					= 0;
				if ($this->hide_empty || $this->show_count) {
					$count = ( $this->type == 'category' ) ? atbdp_listings_count_by_category( $term->term_id, $current_listing_type ) : atbdp_listings_count_by_location( $term->term_id, $current_listing_type );

					if ($this->hide_empty && 0 == $count) {
						continue;
					}
				}

				$expired_listings = atbdp_get_expired_listings($this->tax, $term->term_id);
				$number_of_expired = $expired_listings->post_count;
				$number_of_expired = !empty($number_of_expired) ? $number_of_expired : '0';
				$total = ($count) ? ($count - $number_of_expired) : $count;

				$image = get_term_meta($term->term_id, 'image', true);
				if ( $image ) {
					$image = atbdp_get_image_source($image, apply_filters("atbdp_{$this->type}_image_size", array('350', '280')));
					$image = !empty($image) ? $image : '';
				}

				$child_terms = get_term_children($term->term_id, $this->tax);
				
				if( ! empty( $_GET['directory_type'] ) ) {
					$directory_type = $_GET['directory_type'];
				} else {
					$directory_type = ( 1 == $this->directory_type_count ) ? $this->directory_type[0] : '';
				}

				$permalink = ( $this->type == 'category' ) ? ATBDP_Permalink::atbdp_get_category_page( $term, $directory_type ) : ATBDP_Permalink::atbdp_get_location_page( $term, $directory_type );

				$data = array(
					'term'      => $term,
					'has_child' => !empty($child_terms) ? true : false,
					'name'      => $term->name,
					'permalink' => $permalink,
					'count'     => $total,
					'grid_count_html' => $this->grid_count_html($term,$total),
					'list_count_html' => $this->list_count_html($term,$total),
					'img'        => $image,
					'subterm_html' => ($this->view == 'list') ? $this->subterms_html($term) : '',
				);

				if ($this->type == 'category') {
					$icon = get_term_meta($term->term_id, 'category_icon', true);
					$icon_type = substr($icon, 0,2);
					$data['has_icon']  = ( !empty( $icon ) && ( 'none' != $icon ) ) ? true : false;
					$data['icon_class'] = ('la' === $icon_type)? $icon_type.' '. $icon : 'fa '. $icon;
				}

				$result[] = $data;
			}
    	}

    	return $result;
    }

    public function render_shortcode( $atts = [] ) {
    	if ( $this->logged_in_user_only && ! atbdp_logged_in_user() ) {
    		return ATBDP()->helper->guard( array('type' => 'auth') );
    	}

    	if ($this->redirect_page_url) {
    		$redirect = '<script>window.location="' . esc_url($this->redirect_page_url) . '"</script>';
    		return $redirect;
    	}

    	wp_enqueue_script('loc_cat_assets');

    	if ( $this->type == 'category' ) {
			$column = $this->columns ? $this->columns : 3;
    		$args = array(
    			'taxonomy'   => $this,
    			'categories' => $this->tax_data(),
    			'grid_container' => apply_filters('atbdp_cat_container_fluid', 'container-fluid'),
    			'grid_col_class' => $this->columns == 5 ? 'atbdp_col-5' : 'col-md-' . floor(12 / $column ). ' col-sm-6',
    			'list_col_class' => 'col-md-' . floor(12 / $column ),
    		);
    		$template_file = 'taxonomies/categories-'. $this->view;
    	}

    	else {
    		$args = array(
    			'taxonomy'   => $this,
    			'locations' => $this->tax_data(),
    			'grid_col_class' => $this->columns == 5 ? 'atbdp_col-5' : 'col-md-' . floor(12 / $this->columns). ' col-sm-6',
    			'list_col_class' => 'col-md-' . floor(12 / $this->columns),
    		);
    		$template_file = 'taxonomies/locations-'. $this->view;
    	}

    	if ( !empty( $this->terms ) && !is_wp_error( $this->terms ) ) {
			ob_start();
			if ( ! empty( $atts['shortcode'] ) ) {
				Helper::add_shortcode_comment( $atts['shortcode'] );
			}

    		echo Helper::get_template_contents( $template_file, $args );
			return ob_get_clean();
    	}
    	else {
    		return __('<p>No Results found!</p>', 'directorist');
    	}
	}
	
	public function taxonomy_from_directory_type() {
		if ( empty( $this->directory_type ) ) {
			return;
		}
		$listings = new \WP_Query( array(
			'post_type'     => ATBDP_POST_TYPE,
			'posts_per_page'=> -1,
			'post_status'    => 'public',
			'tax_query'     => array(
				array(
					'taxonomy'         => ATBDP_DIRECTORY_TYPE,
					'field'            => 'slug',
					'terms'            => ! empty( $this->directory_type ) ? $this->directory_type : array(),
				),
			)
			
		) );
		
		$slug = [];		
		if( $listings->have_posts() ) {
			while( $listings->have_posts() ) : $listings->the_post();
			global $post;
			$terms  = get_the_terms( $post->id, $this->tax );
			if( $terms ) {
				foreach( $terms as $term ) {
					$slug[] = $term->slug;
				}
			}
			endwhile;
			wp_reset_query();
		}
		$this->slug = ( $slug ) ? implode( ',', $slug ) : ' ';

		return $slug;

	}

	public function get_listing_types() {
		$listing_types = array();
		$args          = array(
			'taxonomy'   => ATBDP_TYPE,
			'hide_empty' => false
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

	public function get_current_listing_type() {
		$listing_types      = $this->get_listing_types();
		$listing_type_count = count( $listing_types );

		$current = !empty($listing_types) ? array_key_first( $listing_types ) : '';

		if ( isset( $_GET['directory_type'] ) && 'all' != $_GET['directory_type'] ) {
			$current = $_GET['directory_type'] ;
		}
		else if( $this->default_directory_type ) {
			$current = $this->default_directory_type;
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

		if( ! is_numeric( $current ) && 'all' != $current ) {
			$term = get_term_by( 'slug', $current, ATBDP_TYPE );
			$current = $term ? $term->term_id : '';
		}
		return $current;
	}

	// Hooks ------------
	public static function archive_type($listings) {
		
		$count = count( $listings->listing_types );
		$enable_multi_directory = get_directorist_option( 'enable_multi_directory', false );
		if ( $count > 1 && ! empty( $enable_multi_directory ) ) {
			
			Helper::get_template( 'archive/directory-type-nav', array('listings' => $listings, 'all_types' => true ) );
		}
	}
}