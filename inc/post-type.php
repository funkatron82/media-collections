<?php
if( !class_exists( 'CED_Post_Type' ) ) {
	class CED_Post_Type {
		public $post_type; 
		
		function __construct() {
			add_action( $this->post_type . '_activate', array( $this, 'activate' ) );
		
			add_action( 'init', array( $this, 'setup_post_type' ), 20 );
			add_action( 'init', array( $this, 'setup_taxonomies' ), 30 );
			add_action( 'init', array( $this, 'setup_post_statuses' ), 50 );
			add_action( 'init', array( $this, 'setup_rewrite_api' ), 40 );
			add_filter( 'post_type_link', array( $this, 'create_permalink' ), 10, 4 );
			add_filter( 'posts_clauses', array( $this, 'parse_clause' ), 10, 2 );
			add_filter( 'request', array( $this, 'parse_request' ) );	
			add_filter( 'the_posts', array( $this, 'process_posts' ), 10, 2 );
			add_filter( 'get_previous_post_join', array( $this, 'get_adjacent_post_join' ), 10, 4 );
			add_filter( 'get_next_post_join', array( $this, 'get_adjacent_post_join' ), 10,4 );	
			add_filter( 'get_previous_post_where', array( $this, 'get_previous_post_where' ), 10, 4 );
			add_filter( 'get_next_post_where', array( $this, 'get_next_post_where' ), 10, 4 );
			add_filter( 'get_previous_post_sort', array( $this, 'get_previous_post_sort' ), 10, 2 );
			add_filter( 'get_next_post_sort', array( $this, 'get_next_post_sort' ), 10, 2 );	
			add_action( 'parse_query', array( $this, 'parse_query' ) );
		}
		
		function setup() {
			
		}
		
		function bail() {
			if ( !isset( get_current_screen()->post_type ) || ( $this->post_type != get_current_screen()->post_type ) )
				return true;
		
			return false;
		}
		
		
		function activate() {
			$this->setup_post_type();
			$this->setup_taxonomies();
			$this->populate_taxonomy();
			$this->setup_rewrite_api();
		}
		
		function setup_post_type() {
		
		}
		
		function setup_post_statuses() {
			
		}
		
		function setup_taxonomies() {
		
		}
		
		function populate_taxonomy() {
		
		}
		
		function setup_rewrite_api() {
			
		}
		
		
		function create_permalink( $permalink, $post, $leavename, $sample ) {		
			$rewritecode = array(
				'%year%',
				'%monthnum%',
				'%day%',
				'%hour%',
				'%minute%',
				'%second%',
				$leavename? '' : '%postname%',
				'%post_id%',
				'%category%',
				'%author%',
				$leavename? '' : '%pagename%'
				//Add custom permalink tags here
			);
			if ( '' != $permalink && !in_array($post->post_status, array('draft', 'pending', 'auto-draft')) ) {
				$unixtime = strtotime($post->post_date);
		 
				$category = '';
				if ( strpos($permalink, '%category%') !== false ) {
					$cats = get_the_category($post->ID);
					if ( $cats ) {
						usort($cats, '_usort_terms_by_ID'); // order by ID
						$category = $cats[0]->slug;
						if ( $parent = $cats[0]->parent )
							$category = get_category_parents($parent, false, '/', true) . $category;
					}
					// show default category in permalinks, without
					// having to assign it explicitly
					if ( empty($category) ) {
						$default_category = get_category( get_option( 'default_category' ) );
						$category = is_wp_error( $default_category ) ? '' : $default_category->slug;
					}
				}
		 
				$author = '';
				if ( strpos($permalink, '%author%') !== false ) {
					$authordata = get_userdata($post->post_author);
					$author = $authordata->user_nicename;
				}
		 
				$date = explode(" ",date('Y m d H i s', $unixtime));
				//Enter permalink manipulations here			
				$rewritereplace = array(
					$date[0],
					$date[1],
					$date[2],
					$date[3],
					$date[4],
					$date[5],
					$post->post_name,
					$post->ID,
					$category,
					$author,
					$post->post_name
					//Add custom tag replacements here
				);
				$permalink = str_replace($rewritecode, $rewritereplace, $permalink);
			}
			return $permalink;		
		}
		
		function pre_posts( $query ) {
		}
		
		function parse_request( $query_vars ) {
			//Enter qv manipulations here
			return $query_vars;
		}
		
		function parse_query( $query ) {
			
		}
		
		function parse_clause($clauses, $wp_query) {
			//Enter clause manipulations
			if($wp_query->query_vars['post_type'] != $this->post_type) 
				return $clauses;
			global $wpdb;
			$mygroupby = "{$wpdb->posts}.ID";			
			if( !preg_match( "/$mygroupby/", $clauses['groupby'] ) && !strlen(trim($clauses['groupby']))) {
				$clauses['groupby'] = $mygroupby;
			} else {
				$clauses['groupby'] .= ", " . $mygroupby;
			}
			return $clauses;		
		}
		
		function is_post_type ($posts, $query) {
			
			if(!in_array($this->post_type, (array) $query->get( 'post_type' ) )|| count($posts) <=0) 
				return false;
			
			return true;
		}
		
		function process_posts($posts, $query) {
			return $posts;
		}
		
		function add_connected( &$posts, $extra_qv = array(), $connection_type, $prop_name = 'connected', $multiple = true ){
			if( function_exists( 'p2p_distribute_connected' ) && p2p_type( $connection_type )  ){
				$indexed_list = array();
				foreach( $posts as $post ) {
					if( $post->post_type == $this->post_type ){
						$indexed_list[$post->ID] = $post;	
					}
				}
				$extra_qv = wp_parse_args( $extra_qv,
					array(
						'connected_type' => $connection_type,
						'connected_items' => $indexed_list,
						'nopaging' => true
					)
				 );
				$connected = new WP_Query( $extra_qv );
				
				$groups = scb_list_group_by( $connected->posts, '_p2p_get_other_id' );
				foreach ( $groups as $outer_item_id => $connected_items ) {
					$indexed_list[ $outer_item_id ]->$prop_name = $multiple ? $connected_items : array_shift( $connected_items );
				}
			}
		}
		
		function label_taxonomies($name, $plural_name) {
			$labels = array(
				'name' => __( '' . $plural_name . '', 'taxonomy general name' ),
				'singular_name' => __( '' . $name . '', 'taxonomy singular name' ),
				'search_items' =>  __( 'Search ' . $plural_name . '' ),
				'popular_items' => __( 'Popular ' . $plural_name . '' ),
				'all_items' => __( 'All ' . $plural_name . '' ),
				'parent_item' => null,
				'parent_item_colon' => null,
				'edit_item' => __( 'Edit ' . $name . '' ), 
				'update_item' => __( 'Update ' . $name . '' ),
				'add_new_item' => __( 'Add New ' . $name . '' ),
				'new_item_name' => __( 'New ' . $name . ' Name' ),
				'separate_items_with_commas' => __( 'Separate ' . strtolower($plural_name) . ' with commas' ),
				'add_or_remove_items' => __( 'Add or remove ' . strtolower($plural_name) . '' ),
				'choose_from_most_used' => __( 'Choose from the most used ' . strtolower($plural_name) . '' ),
				'menu_name' => __( '' . $plural_name . '' ),
			);
			
			return $labels;			
		}
		
		function slug_from_id( $id, $post_type ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM $wpdb->posts WHERE ID = %d AND post_type = %s", $id, $post_type ) );
			
		}
		
		function slug_to_id( $slug, $post_type ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s", $slug, $post_type ) );
			
		}
			
		function get_adjacent_post_join($join, $in_same_cat, $excluded_categories) {
				return $join;
		}
			
		function get_next_post_where($where, $in_same_cat, $excluded_categories ) {
			global $post;
			if($post->post_type == $this->post_type)
				return $this->get_adjacent_post_where($where, $in_same_cat, $excluded_categories, false);
			else
				return $where;
		}
		
		function get_previous_post_where($where, $in_same_cat, $excluded_categories) {
			global $post;
			if($post->post_type == $this->post_type)
				return $this->get_adjacent_post_where($where, $in_same_cat, $excluded_categories, true);
			else
				return $where;
		}
		
		function get_adjacent_post_where($where, $in_same_cat, $excluded_categories, $previous = true) {
			return $where;
		}
		
		function get_next_post_sort($sort ) {
			global $post;
			if($post->post_type == $this->post_type)
				return $this->get_adjacent_post_sort($sort, false);
			else
				return $sort;
		}
		
		function get_previous_post_sort($sort) {
			global $post;
			if($post->post_type == $this->post_type)
				return $this->get_adjacent_post_sort($sort, true);
			else
				return $sort;
		}
		
		function get_adjacent_post_sort($sort, $previous = true) {
			return $sort;
		}
		
		function struct_to_query( $struct ) {
			global $wp_rewrite	;
			$querycode = array_merge( $wp_rewrite->queryreplace, array( 'paged=', 'feed=' ) );
			$tagcode = array_merge( $wp_rewrite->rewritecode, array( '%paged%', '%feed%' ) );
			$num_toks = preg_match_all('/%.+?%/', $struct, $toks);
			$tokens = $toks[0];
			$query_result = str_replace( $tagcode, $querycode, $tokens );
			$query_string = '';
			
			foreach( $query_result as $index => $value ) {
				$query_string .= '&' . $value . '$matches[' . (string)( (int) $index + 1 ). ']';
			}
			return 'index.php?post_type=' . $this->post_type . $query_string;
		}
		
		function struct_to_regex( $struct ) {
			global $wp_rewrite	;
			$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
			$regexcode = array_merge( $wp_rewrite->rewritereplace, array( '([0-9]{1,})', $feeds ) );
			$tagcode = array_merge( $wp_rewrite->rewritecode, array( '%paged%', '%feed%' ) );
			return rtrim( str_replace( $tagcode, $regexcode, $struct ), '/' ) . '/?$';
		
		}
		
		function struct_to_rewrite( $struct = '', $pages = true, $feeds = true ) {
			$endpoints = array();
			if( $pages ) {
				$endpoints = array_merge( $endpoints, array( 'page/%paged%/' ) );	
			}
			
			if( $feeds ) {
				$endpoints = array_merge( $endpoints, array( 'feed/%feed%/', '%feed%/' ) );	
			}
			foreach( $endpoints as $endpoint ) {
				add_rewrite_rule(
					$this->struct_to_regex( $struct . $endpoint ),
					$this->struct_to_query( $struct . $endpoint ),
					'top'
				);
			}
			
			add_rewrite_rule(
				$this->struct_to_regex( $struct ),
				$this->struct_to_query( $struct ),
				'top'
			);
				
		}
	}

}
