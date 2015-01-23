<?php

if( !class_exists( 'CED_Gallery_Type' )) :

class CED_Gallery_Type extends CED_Post_Type {
	public $post_type = 'gallery';	
	
	function __construct(){
		parent::__construct();
		add_action( 'wp_loaded', array($this, 'register_connections'), 100);
		add_filter( 'the_posts', array($this, 'process_posts'), 10, 2 );
	}
	
	function setup_post_type() {
		$labels = array(
			'name'                => _x( 'Galleries', 'Post Type General Name', 'cedmc' ),
			'singular_name'       => _x( 'Gallery', 'Post Type Singular Name', 'cedmc' ),
			'menu_name'           => __( 'Galleries', 'cedmc' ),
			'parent_item_colon'   => __( 'Parent Gallery:', 'cedmc' ),
			'all_items'           => __( 'All Galleries', 'cedmc' ),
			'view_item'           => __( 'View Gallery', 'cedmc' ),
			'add_new_item'        => __( 'Add New Gallery', 'cedmc' ),
			'add_new'             => __( 'Add New', 'cedmc' ),
			'edit_item'           => __( 'Edit Gallery', 'cedmc' ),
			'update_item'         => __( 'Update Gallery', 'cedmc' ),
			'search_items'        => __( 'Search Gallery', 'cedmc' ),
			'not_found'           => __( 'Not found', 'cedmc' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'cedmc' ),
		);
		$rewrite = array(
			'slug'                => 'gallery',
			'with_front'          => true,
			'pages'               => true,
			'feeds'               => true,
		);
		$args = array(
			'label'               => __( 'gallery', 'cedmc' ),
			'description'         => __( 'A gallery post', 'cedmc' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'comments', 'trackbacks', ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-format-gallery',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'query_var'           => 'gallery',
			'rewrite'             => $rewrite,
			'capability_type'     => 'page',
		);
		register_post_type( $this->post_type, $args );	
	}
	
	function setup_rewrite_api() {
		//Query vars/tags	
		add_rewrite_tag( '%media_in_gallery%', '([^/]+)' );	
		
		$structs = array(
			'gallery/%year%/%monthnum%/%day%/' ,
			'gallery/%year%/%monthnum%/',
			'gallery/%year%/',
		);
		
		foreach ( $structs as $struct ) {
			$this->struct_to_rewrite( $struct );	
		}
	}
	
	function parse_query( $query ) {
		if( $gallery = $query->get( 'media_in_gallery' ) ) {
			$gallery = is_numeric( $gallery ) ? $gallery : $this->slug_to_id( $gallery, 'gallery' );
			$query->gallery = get_post( $gallery );
			$query->set( 'connected_type', 'gallery_to_media' );
			$query->set( 'connected_items', $query->gallery );
		}
	}
	
	function process_posts( $posts, $query ) {
		remove_filter( 'the_posts', array( $this, 'process_posts' ) );
		$this->add_connected( $posts, array(), 'gallery_to_media', 'media', true );
		add_filter( 'the_posts', array( $this, 'process_posts' ), 10, 2 );
		return $posts; 			
	}
	
	function register_connections(){
		if ( !function_exists( 'p2p_register_connection_type' ) )
			return;

		p2p_register_connection_type( array(
			'name' => 'gallery_to_media',
			'from' => 'gallery',
			'to' => 'attachment',
			'cardinality' => 'many-to-many',
			'prevent_duplicates' => true,
			'admin_box' => false,
			'to_query_vars' => array( 
				'nopaging' => true,
				'post_status' => 'inherit',
				'post_mime_type' => 'image',
				'connected_orderby' => 'media_order',
				'connected_order' => 'asc',
				'connected_order_num' => true,
			)							
		) );			
	}
}
endif;

function gallery_content( $content ) {
	$gallery = get_post();
	if( 'gallery' !== $gallery->post_type )	
		return $content;
	
	$meta = get_gallery_meta( $gallery->ID );
	$meta['ids'] = get_gallery_media_ids( $gallery->ID );
	$meta['size'] = 'gallery_preview';
	return gallery_shortcode( $meta ) . $content;
}

function gallery_sizes( $sizes ) {
	return array_merge( $sizes, array( 'gallery_preview'=> __( 'Gallery Preview' ) ) );	
}

add_filter( 'the_content', 'gallery_content' );
add_filter( 'image_size_names_choose', 'gallery_sizes' );
add_image_size( 'gallery_preview', 400, 400, true );
