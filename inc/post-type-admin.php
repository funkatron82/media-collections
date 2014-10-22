<?php
if( !class_exists( 'CED_Post_Type_Admin' ) ) {
 class CED_Post_Type_Admin {
	public $post_type;

	function __construct() {
		/*if( !is_admin() )
			return;
			*/
		
		add_filter( "manage_{$this->post_type}_posts_columns", array( $this,'add_columns' ) );
		add_filter( "manage_edit-{$this->post_type}_sortable_columns", array( $this,'add_sortable_columns' ) );
		add_action( "manage_{$this->post_type}_posts_custom_column", array( $this,'manage_columns'), 10, 2 );
		
		add_action( 'admin_head-edit.php', array( $this, 'hide_publishing_actions' ) );
		
		//Meta Boxes
		add_action( 'add_meta_boxes_' . $this->post_type, array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . $this->post_type, array( $this, 'save_meta_boxes' ), 10, 3 );
		add_action( 'admin_menu', array( $this, 'remove_meta_boxes' ) );		
		
		//Filtering
		add_action( 'restrict_manage_posts', array( $this, 'restrict_posts' ) );
		
		//Scripts and Styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) ); 
		 
	}

	function bail() {
		if ( !isset( get_current_screen()->post_type ) || ( $this->post_type != get_current_screen()->post_type ) )
			return true;

		return false;
	}
	
	function add_meta_boxes( $post ) {
		
	}
	
	function save_meta_boxes( $post_id, $post, $update ) {
		
	}
	
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
	
	function enqueue_scripts( $hook ) {
	}

	function generate_taxonomy_select( $taxonomy, $name = false ) {
			$tax_obj = get_taxonomy( $taxonomy );
			$terms = get_terms( $taxonomy );
			$term_groups = array();
			
			foreach( $terms as $term ) {
				if( empty( $term_groups[ $term->parent ] ) ) {
					$term_groups[ $term->parent ]= array();
				}
				$term_groups[ $term->parent ][] = $term;
				
			}
			
			$selected = get_query_var( $name );
			if( ! $tax_name = $tax_obj->labels->name ) {
				return;;
			}
			
			$name = ! $name ? $taxonomy : $name;
	
			// output html for taxonomy dropdown filter
			echo "<select name='$name' id='$name' class='postform'>";
			echo "<option value=''>Show All $tax_name</option>";
			$this->print_taxonomy_options( $term_groups, 0, $selected, 0 );
			echo "</select>";
	}
	
	function print_taxonomy_options( $groups, $parent = 0,  $selected = NULL, $level = 0 ) {
		if( !isset( $groups[ $parent ] ) ) {
			return;
		}
		$terms = $groups[ $parent ];		
		foreach ( $terms as $term ) {
			printf( 
				'<option value="%s" %s> %s </option>',
				$term->slug,
				selected( $selected, $term->slug, false ),
				sprintf( '%s%s (%s)', str_repeat( '&nbsp;&nbsp;&nbsp;', $level ), $term->name, $term->count )
			);
			
			$this->print_taxonomy_options( $terms, $term->term_id, $selected, ++ $level );
		}
		
	}
	
	function generate_post_select($select_id, $post_type, $selected = 0) {
		$post_type_object = get_post_type_object($post_type);
		$label = $post_type_object->label;
		$posts = get_posts(array('post_type'=> $post_type, 'post_status'=> 'publish', 'suppress_filters' => false, 'posts_per_page'=>-1));
		echo '<select name="'. $select_id .'" id="'.$select_id.'">';
		echo '<option value = "" >All '.$label.' </option>';
		foreach ($posts as $post) {
			echo '<option value="', $post->ID, '"', $selected == $post->ID ? ' selected="selected"' : '', '>', $post->post_title, '</option>';
		}
		echo '</select>';
	}
	
	function add_columns($columns) {
		return $columns;
	}
	
	function add_sortable_columns($columns) {
		return $columns;
	}
	
	function manage_columns($columns, $id) {
		
	}
	
	function restrict_posts() {
		
	}
	function setup_metaboxes() {
		
	}
	
	function hide_publishing_actions(){

	}
	
	function meta_months_dropdown( $meta_key, $name, $default = 'Show all dates' ) {
		global $wpdb, $wp_locale;

		$dates = $wpdb->get_results( $wpdb->prepare( "
			SELECT DISTINCT YEAR( meta_value ) AS year, MONTH( meta_value ) AS month,  count(post_id) as posts
			FROM $wpdb->postmeta
			WHERE meta_key = %s
			ORDER BY meta_value DESC
		", $meta_key ) );
		
		$date_count = count( $dates );
		
		if ( !$date_count || ( 1 == $date_count && 0 == $dates[0]->month ) )
			return;

		$m = isset( $_GET[$name] ) ?  $_GET[$name] : '0000-00';
		$html = sprintf( 
			'<select name="%s">', 
			$name 
		);
		$html .= sprintf( 
			'<option %s value="">%s</option>',
			selected( $m, '0000-00', false ),
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
?>