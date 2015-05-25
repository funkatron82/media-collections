<?php
if( !class_exists( 'CED_Post_Type_Admin' ) ) {
	class CED_Post_Type_Admin {
		public $post_type;
		public $no_month_filter = false;
	
		function __construct() {
			add_filter( "manage_{$this->post_type}_posts_columns", array( $this,'add_columns' ) );
			add_filter( "manage_edit-{$this->post_type}_sortable_columns", array( $this,'add_sortable_columns' ) );
			add_action( "manage_{$this->post_type}_posts_custom_column", array( $this,'manage_columns'), 10, 2 );
						
			//Meta Boxes
			add_action( 'add_meta_boxes_' . $this->post_type, array( $this, 'add_meta_boxes' ) );
			add_action( 'admin_menu', array( $this, 'remove_meta_boxes' ) );
			add_action( 'edit_form_after_title', array( $this, 'print_post_nonce' )  );		
			
			//Filtering
			add_action( 'restrict_manage_posts', array( $this, 'restrict_posts' ) );
			add_filter( 'months_dropdown_results', array( $this, 'remove_month_filter'), 10, 2 );
			
			//Scripts and Styles
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) ); 
			
			//Save
			add_action( "save_post_{$this->post_type}", array( $this, 'save' ), 10, 3 );
			add_action( "publish_{$this->post_type}", array( $this, 'publish' ), 10, 2 );
			 
		}
	
		function bail() {
			if ( !isset( get_current_screen()->post_type ) || ( $this->post_type != get_current_screen()->post_type ) )
				return true;
	
			return false;
		}
		
		function add_meta_boxes( $post ) {}

		
		function remove_meta_boxes() 
		{
			if( !property_exists(  $this, 'post_type' ) || !property_exists(  $this, 'taxonomies' ) ) 
				return;
				
			foreach( (array) $this->taxonomies as $tax )
			{
				remove_meta_box( 'tagsdiv-' . $tax, $this->post_type, 'core' );	
				remove_meta_box( $tax . 'div', $this->post_type, 'core' );	
			}
		}
		
		function print_post_nonce( $post ){
			if( $this->post_type !== $post->post_type )
				return;
				
			wp_nonce_field( "ced-post-nonce{$post->ID}", "ced_nonce_{$this->post_type}" );
		}
		
		function verify_save( $post, $autosave = false ) {
			$post = get_post( $post );
			$nonce = isset( $_POST["ced_nonce_{$this->post_type}"] ) ? sanitize_key( $_POST["ced_nonce_{$this->post_type}"] ) : '';	
			
			if ( empty( $_POST["ced_nonce_{$this->post_type}"] ) || ! wp_verify_nonce( $nonce, "ced-post-nonce{$post->ID}" ) )
				return false;
				
			// Autosave
			if ( defined( 'DOING_AUTOSAVE' ) && ! $autosave )
				return false;
			
			return true;
		}
		
		function enqueue_scripts( $hook ) {
		}
		
		function remove_month_filter( $months, $post_type ) {
			if( $this->post_type == $post_type && $this->no_month_filter) {
				return array();	
			}
			
			return $months;
		}
	
		function generate_taxonomy_filter( $taxonomy ) {
				$tax_obj = get_taxonomy( $taxonomy );
				$tax_name = strtolower( $tax_obj->labels->name );
				$terms = get_terms( $taxonomy );
				
				if( empty( $terms ) ){
					return;	
				}
	
				$args = array( 'selected' => get_query_var( $taxonomy ) );
		
				// output html for taxonomy dropdown filter
				echo "<select name='$taxonomy' class='postform'>";
				echo "<option value=''>All $tax_name</option>";
				$walker = new CED_Taxonomy_Filter_Walker();
				echo $walker->walk( $terms, 0, $args );
				echo "</select>";
		}
		
		function generate_connected_post_filter( $post_type, $name ) {
			$post_type_object = get_post_type_object( $post_type );
			$label = strtolower( $post_type_object->label );
			$posts = get_posts( array( 
				'post_type'=> $post_type, 
				'post_status'=> 'publish', 
				'suppress_filters' => false, 
				'posts_per_page'=>-1,
				'orderby' => 'title',
				'order' => 'ASC'
			) );
	
			$selected = get_query_var( $name );
			echo "<select name=\"{$name}\" >";
			echo "<option value = \"\" >All {$label} </option>";
			$walker = new CED_Post_Connection_Filter_Walker();
			echo $walker->walk( $posts, 0, array( 'selected' => $selected) );
			echo "</select>";
		}
	
		function add_columns($columns) {
			return $columns;
		}
		
		function add_sortable_columns($columns) {
			return $columns;
		}
		
		function manage_columns($columns, $id) {}
		
		function restrict_posts() {}
		
		function setup_metaboxes() {}
		
		function save( $pid, $post, $update ){}
		
		function publish( $pid, $post ) {}
		
		function meta_months_dropdown( $meta_key, $name, $default = 'Show all dates' ) {
			global $wpdb, $wp_locale;
	
			$dates = $wpdb->get_results( $wpdb->prepare( "
				SELECT YEAR( meta_value ) AS year, MONTH( meta_value ) AS month,  count(*) as posts
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				GROUP BY year, month
				ORDER BY meta_value DESC
			", $meta_key ) );
			
			if ( count( $dates ) < 2 )
				return;
		
			$m = get_query_var( $name );
			$html = sprintf( 
				'<select name="%s">', 
				$name 
			);
			$html .= sprintf( 
				'<option %s value="">%s</option>',
				selected( $m, NULL, false ),
				$default
			);
			
			foreach ( $dates as $date ) {
				
				$month = zeroise( $date->month, 2 );
				$year = $date->year;
				$html .= sprintf(
					'<option %s value="%s">%s</option>',
					selected( $m, $year . '-' . $month, false),
					esc_attr( $year . '-' . $month ),
					sprintf( __( '%1$s %2$d (%3$d)' ), $wp_locale->get_month( $month ), $year, $date->posts )
				);	
			}
			
			$html .= '</select>';
			
			echo $html;
		}
	
	 }
}

if( ! class_exists( 'CED_Taxonomy_Filter_Walker' ) ) {
	class CED_Taxonomy_Filter_Walker extends Walker {
		public $tree_type = 'taxonomy';
		
		public $db_fields = array ('parent' => 'parent', 'id' => 'term_id');
		
		public function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 ) {
			$pad = str_repeat('&nbsp;', $depth * 3);
			
			$name = $term->name;
			
			$output .= "\t<option class=\"level-$depth\" value=\"" . $term->slug . "\"";
			$output .= selected( $args['selected'], $term->slug, false); 

			$output .= '>';
			$output .= $pad.$name;
			if ( $args['show_count'] )
				$output .= '&nbsp;&nbsp;('. number_format_i18n( $term->count ) .')';
			$output .= "</option>\n";
		}
	}
}

if( ! class_exists( 'CED_Post_Connection_Filter_Walker' ) ) {
	class CED_Post_Connection_Filter_Walker extends Walker {
		public $tree_type = 'post';
		
		public $db_fields = array ('parent' => 'post_parent', 'id' => 'ID');
		
		public function start_el( &$output, $post, $depth = 0, $args = array(), $id = 0 ) {
			$pad = str_repeat('&nbsp;', $depth * 3);
			
			$name = $post->post_title;
			
			$output .= "\t<option class=\"level-$depth\" value=\"" . $post->ID . "\"";
			$output .= selected( $args['selected'], $post->ID, false); 

			$output .= '>';
			$output .= $pad.$name;
			if ( $args['show_count'] )
				//$output .= '&nbsp;&nbsp;('. number_format_i18n( $post->count ) .')';
			$output .= "</option>\n";
		}
	}
}

if( ! function_exists( 'ced_print_taxonomy_select_meta_box' ) ) {
	function ced_print_taxonomy_select_meta_box( $post, $box ) {
		$taxonomy = $box['args']['taxonomy'];
		$tax_obj = get_taxonomy( $taxonomy );
		$label = strtolower( $tax_obj->labels->singular_name );
		$term = (array) wp_get_object_terms( $post->ID, $taxonomy, array() );
		$term = array_shift( $term );
		
		wp_dropdown_categories( array(
			'orderby' 			=> 'id',
			'hide_empty'		=> false,
			'selected'			=> $term->term_id,
			'taxonomy'			=> $taxonomy,
			'name'				=> "tax_input[{$taxonomy}]",
			'id'				=> $taxonomy,
			'show_option_all'	=> "Select a(n) {$label}"
		) );
		
	}
}