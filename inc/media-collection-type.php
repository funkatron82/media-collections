<?php

if( !class_exists( 'CED_Media_Collection_Type' )) :

class CED_Media_Collection_Type extends CED_Post_Type {
	public $post_type = 'media_collection';	
	
	function __construct(){
		parent::__construct();
		add_action( 'wp_loaded', array($this, 'register_connections'), 100);
		add_filter( 'the_posts', array($this, 'process_posts'), 10, 2 );
	}
	
	function setup_post_type() {
		$labels = array(
			'name'                => _x( 'Media Collections', 'Post Type General Name', 'cedmc' ),
			'singular_name'       => _x( 'Media Collection', 'Post Type Singular Name', 'cedmc' ),
			'menu_name'           => __( 'Media Collections', 'cedmc' ),
			'parent_item_colon'   => __( 'Parent Media Collection:', 'cedmc' ),
			'all_items'           => __( 'All Media Collections', 'cedmc' ),
			'view_item'           => __( 'View Media Collection', 'cedmc' ),
			'add_new_item'        => __( 'Add New Media Collection', 'cedmc' ),
			'add_new'             => __( 'Add New', 'cedmc' ),
			'edit_item'           => __( 'Edit Media Collection', 'cedmc' ),
			'update_item'         => __( 'Update Media Collection', 'cedmc' ),
			'search_items'        => __( 'Search Media Collection', 'cedmc' ),
			'not_found'           => __( 'Not found', 'cedmc' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'cedmc' ),
		);
		$rewrite = array(
			'slug'                => 'media-collections',
			'with_front'          => true,
			'pages'               => true,
			'feeds'               => true,
		);
		$args = array(
			'label'               => __( 'media_collection', 'cedmc' ),
			'description'         => __( 'A collection of media (galleries, playlists)', 'cedmc' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'comments', 'trackbacks', ),
			'taxonomies'          => array( 'media_collection_type', 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 10,
			'menu_icon'           => 'dashicons-admin-media',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => $rewrite,
			'capability_type'     => 'post',
		);
		register_post_type( $this->post_type, $args );	
	}
	
	function setup_taxonomies() {
		$labels = array(
			'name'                       => _x( 'Collection Types', 'Taxonomy General Name', 'cedmc' ),
			'singular_name'              => _x( 'Collection Type', 'Taxonomy Singular Name', 'cedmc' ),
			'menu_name'                  => __( 'Collection Types', 'cedmc' ),
			'all_items'                  => __( 'All Collection Types', 'cedmc' ),
			'parent_item'                => __( 'Parent Collection Type', 'cedmc' ),
			'parent_item_colon'          => __( 'Parent Collection Type:', 'cedmc' ),
			'new_item_name'              => __( 'New Collection Type Name', 'cedmc' ),
			'add_new_item'               => __( 'Add New Collection Type', 'cedmc' ),
			'edit_item'                  => __( 'Edit Collection Type', 'cedmc' ),
			'update_item'                => __( 'Update Collection Type', 'cedmc' ),
			'separate_items_with_commas' => __( 'Separate collection types with commas', 'cedmc' ),
			'search_items'               => __( 'Search Collection Types', 'cedmc' ),
			'add_or_remove_items'        => __( 'Add or remove collection types', 'cedmc' ),
			'choose_from_most_used'      => __( 'Choose from the most used collection type', 'cedmc' ),
			'not_found'                  => __( 'Not Found', 'cedmc' ),
		);
		$rewrite = array(
			'slug'                       => 'collection-type',
			'with_front'                 => true,
			'hierarchical'               => false,
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => false,
			'show_admin_column'          => false,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => false,
			'rewrite'                    => $rewrite,
		);
		register_taxonomy( 'media_collection_type', array( $this->post_type ), $args );	
	}
	
	function populate_taxonomy() {
		wp_insert_term( 'Image Gallery', 'media_collection_type', array( 'slug' => 'image') );	
		wp_insert_term( 'Audio Playlist', 'media_collection_type', array( 'slug' => 'audio') );	
		wp_insert_term( 'Video Playlist', 'media_collection_type', array( 'slug' => 'video') );		
	}
	
	function process_posts( $posts, $query ) {
		remove_filter( 'the_posts', array( $this, 'process_posts' ) );
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'collection_to_media' )  ) { 
			$items =& $posts;
			$collections = array();
			
			foreach( $items as $item ) {
				if( 'media_collection' === $item->post_type ) {
					$item->media = array();
					$collections[ $item->ID ] = $item;	
				}
			}
			
			if( !empty( $collections ) ) {
				$media = new WP_Query( array(
				  'connected_type' => 'collection_to_media',
				  'connected_items' => $query->posts,
				  'nopaging' => true
				) );
				
				$groups = scb_list_group_by( $media->posts, '_p2p_get_other_id' );
				foreach ( $groups as $outer_item_id => $connected_items ) {
					$collections[ $outer_item_id ]->media = $connected_items;
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
			'name' => 'collection_to_media',
			'from' => 'media_collection',
			'to' => 'attachment',
			'cardinality' => 'many-to-many',
			'prevent_duplicates' => true,
			'admin_box' => false							
		) );			
	}
}

function media_collection_content( $content ) {
	$post = get_post();
	if( ! $post && 'media_collection' !== $post->post_type )
		return $content;
		
	//Get collection type
	$type = get_the_terms( $post->ID, 'media_collection_type' );
	if ( !empty( $type ) )	{              
		$type = array_shift( $type );
		$type = $type->slug;	
	} else {
		$type = 'image';	
	}	
	
	//Get collection meta
	$meta = (array) get_post_meta( $post->ID, 'cedmc_meta', true );
	
	
	//Get ids
	$media = new WP_Query( array(
	  'connected_type' => 'collection_to_media',
	  'connected_items' => $post->ID,
	  'nopaging' => true,
	  'post_status' => 'inherit',
	  'connected_orderby' => 'media_order',
	  'connected_order' => 'asc',
	  'connected_order_num' => true
	) );
	$ids = wp_list_pluck( $media->posts, 'ID' );
	
	//put it all together
	$meta['ids'] = $ids;
	if( 'image' === $type ) {
		$content = gallery_shortcode( $meta ) . $content;	
	} else {
		$meta['type'] = $type;
		$content = wp_playlist_shortcode( $meta ) . $content;	
	}
	return $content;
}

add_filter( 'the_content', 'media_collection_content' );

endif;