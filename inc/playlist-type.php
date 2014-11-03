<?php

if( !class_exists( 'CED_Playlist_Type' )) :

class CED_Playlist_Type extends CED_Post_Type {
	public $post_type = 'playlist';	
	
	function __construct(){
		parent::__construct();
		add_action( 'wp_loaded', array($this, 'register_connections'), 100);
		add_filter( 'the_posts', array($this, 'process_posts'), 10, 2 );
	}
	
	function setup_post_type() {
			$labels = array(
			'name'                => _x( 'Playlists', 'Post Type General Name', 'cedmc' ),
			'singular_name'       => _x( 'Playlist', 'Post Type Singular Name', 'cedmc' ),
			'menu_name'           => __( 'Playlists', 'cedmc' ),
			'parent_item_colon'   => __( 'Parent Playlist:', 'cedmc' ),
			'all_items'           => __( 'All Playlists', 'cedmc' ),
			'view_item'           => __( 'View Playlist', 'cedmc' ),
			'add_new_item'        => __( 'Add New Playlist', 'cedmc' ),
			'add_new'             => __( 'Add New', 'cedmc' ),
			'edit_item'           => __( 'Edit Playlist', 'cedmc' ),
			'update_item'         => __( 'Update Playlist', 'cedmc' ),
			'search_items'        => __( 'Search Playlist', 'cedmc' ),
			'not_found'           => __( 'Not found', 'cedmc' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'cedmc' ),
		);
		$rewrite = array(
			'slug'                => 'playlist',
			'with_front'          => true,
			'pages'               => true,
			'feeds'               => true,
		);
		$args = array(
			'label'               => __( 'playlist', 'cedmc' ),
			'description'         => __( 'A playlist post', 'cedmc' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'thumbnail', 'comments', 'trackbacks', ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'query_var'           => 'playlist',
			'rewrite'             => $rewrite,
			'capability_type'     => 'post',
			'menu_icon'           => 'dashicons-video-alt3'
		);
		register_post_type( $this->post_type, $args );	
	}
	
	function setup_taxonomies() {
		$labels = array(
			'name'                       => _x( 'Playlist Types', 'Taxonomy General Name', 'cedmc' ),
			'singular_name'              => _x( 'Playlist Type', 'Taxonomy Singular Name', 'cedmc' ),
			'menu_name'                  => __( 'Playlist Types', 'cedmc' ),
			'all_items'                  => __( 'All Playlist Types', 'cedmc' ),
			'parent_item'                => __( 'Parent Playlist Type', 'cedmc' ),
			'parent_item_colon'          => __( 'Parent Playlist Type:', 'cedmc' ),
			'new_item_name'              => __( 'New Playlist Type Name', 'cedmc' ),
			'add_new_item'               => __( 'Add New Playlist Type', 'cedmc' ),
			'edit_item'                  => __( 'Edit Playlist Type', 'cedmc' ),
			'update_item'                => __( 'Update Playlist Type', 'cedmc' ),
			'separate_items_with_commas' => __( 'Separate playlist types with commas', 'cedmc' ),
			'search_items'               => __( 'Search Playlist Types', 'cedmc' ),
			'add_or_remove_items'        => __( 'Add or remove playlist types', 'cedmc' ),
			'choose_from_most_used'      => __( 'Choose from the most used playlist types', 'cedmc' ),
			'not_found'                  => __( 'Not Found', 'cedmc' ),
		);
		$rewrite = array(
			'slug'                       => 'playlist-type',
			'with_front'                 => true,
			'hierarchical'               => false,
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => false,
			'show_admin_column'          => false,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
			'query_var'                  => 'playlist_type',
			'rewrite'                    => $rewrite,
		);
		register_taxonomy( 'playlist_type', array( $this->post_type ), $args );	
	}
	
	function populate_taxonomy() {
		wp_insert_term( 'Audio Playlists', 'playlist_type', array( 'slug' => 'audio') );	
		wp_insert_term( 'Video Playlists', 'playlist_type', array( 'slug' => 'video') );		
	}
	
	function process_posts( $posts, $query ) {
		remove_filter( 'the_posts', array( $this, 'process_posts' ) );
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'playlist_to_media' )  ) { 
			$items =& $posts;
			$playlists = array(
				'audio' =>array(),
				'video' => array()
			);				

			foreach( $items as $item ) {
				if( 'playlist' === $item->post_type ) {
					$item->media = array();
					$type = get_playlist_type( $item->ID );
					$playlists[$type][ $item->ID ] = $item; 
				}
			}
			
			foreach( array( 'audio', 'video' ) as $type ) {
				if( ! empty( $playlists[$type] ) ) {
					$media = new WP_Query( array(
					  'connected_type' => 'playlist_to_media',
					  'connected_items' => $playlists[$type],
					  'nopaging' => true,
					  'connected_orderby' => 'media_order',
					  'connected_order' => 'asc',
					  'connected_order_num' => true,
					  'post_mime_type' => $type
					) );
					
					$groups = scb_list_group_by( $media->posts, '_p2p_get_other_id' );
					foreach ( $groups as $outer_item_id => $connected_items ) {
						$playlists[$type][ $outer_item_id ]->media = $connected_items;
					}
				}
			}
		}
		add_filter( 'the_posts', array( $this, 'process_posts' ), 10, 2 );
		return $posts; 			
	}
	
	function register_connections(){
		if ( !function_exists( 'p2p_register_connection_type' ) )
			return;

		p2p_register_connection_type( array(
			'name' => 'playlist_to_media',
			'from' => 'playlist',
			'to' => 'attachment',
			'cardinality' => 'many-to-many',
			'prevent_duplicates' => true,
			'admin_box' => false,
			'to_query_vars' => array( 
				'nopaging' => true,
				'post_status' => 'inherit',
				'connected_orderby' => 'media_order',
				'connected_order' => 'asc',
				'connected_order_num' => true,
			)							
		) );			
	}
}
endif;

function playlist_content( $content ) {
	$playlist = get_post();
	if( 'playlist' !== $playlist->post_type )	
		return $content;
	
	$meta = get_playlist_meta( $playlist->ID );
	$meta['ids'] = get_playlist_media_ids( $playlist->ID );
	return wp_playlist_shortcode( $meta ) . $content;
}

add_filter( 'the_content', 'playlist_content' );