<?php

if( !class_exists( 'CED_Gallery_Type_Admin' )) :

class CED_Gallery_Type_Admin extends CED_Post_Type_Admin {
	public $post_type = 'gallery';
	
	function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_cedmc_read_gallery', array( $this, 'read_gallery' ) );	
		add_action( 'wp_ajax_cedmc_update_gallery', array( $this, 'update_gallery' ) );	
		add_action( 'edit_form_after_title', array( $this, 'show_gallery')  );
		add_action( 'print_media_templates', array( $this, 'print_templates' ) );
	}
	
	function add_columns( $columns ) {		
		return array_slice( $columns, 0, 2, true ) + array( 'media' => 'Media' ) + array_slice( $columns, 2, NULL, true );	
	}
	
	function manage_columns( $column, $id ) {
		global $post;
		if( 'media' === $column ) {
			$media = count( $post->media );
			if( ( $media > 0 ) ) {
				echo $media . _n( ' image', ' images', $media );
			} else {
				echo "—";	
			}
		}
		
	}
	
	function enqueue_scripts( $hook ) {
		if( $this->bail() || ( 'post.php' !== $hook && 'post-new.php' !== $hook ) ) {
			return;
		}
			
		global $post;
		$id = $post->ID;
		
		wp_register_script( 'cedmc-gallery', CEDMC_URL . 'js/gallery.js', array( 'backbone', 'cedmc-models', 'cedmc-views' ) );
		wp_localize_script( 'cedmc-gallery', 'galleryData', $this->get_data( $id ) );		
		wp_enqueue_script( 'cedmc-gallery' );

		wp_register_style( 'cedmc-gallery', CEDMC_URL . 'css/gallery.css', array( 'cedmc' ) );		
		wp_enqueue_style( 'cedmc-gallery' );
	}
	
	function get_data( $gallery = 0 ) {
		$gallery = get_post( $gallery );
		$meta = array(
			'id' => $gallery->ID,
			'ids' => (array) get_gallery_media_ids( $gallery ),
			'nonces' => array(
				'update'	=> wp_create_nonce( 'cedmc-update_' . $gallery->ID ),
			)
		);
		$meta = array_merge( $meta, (array) get_gallery_meta( $gallery ) );		
		return $meta;
	}
	
	function show_gallery( $post ) {
		if( 'gallery' !== $post->post_type )	
			return;

		?>
        <div id="cedmc-main">
            <div id="cedmc-toolbar">
            </div>
            <div id="cedmc-preview">
            </div>	
        </div>
        <label for="content"><strong>Description</strong></label>
		<?php
		wp_editor( $post->post_content, 'content', array( 'tinymce' => false, 'media_buttons' => false ) );
	}

	
	function remove_media_ids( $gallery ) {
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'gallery_to_media' )  ) { 
			$media_ids = get_gallery_media_ids( $gallery );
			foreach( $media_ids as $media_id ) {
				p2p_type( 'gallery_to_media' )->disconnect( $gallery, $media_id );
			}
		}
	}
	
	function set_media_ids( $gallery, $media_ids ) {
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'gallery_to_media' )  ) {
			foreach( $media_ids as $index => $media_id ) {
				p2p_type( 'gallery_to_media' )->connect( $gallery, $media_id, array( 'media_order' => $index ) );
			}	
		}
	}

	function update_gallery() {
		$gallery =  isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;	
		$changes =  isset( $_REQUEST['changes'] ) ? (array) $_REQUEST['changes'] : array();
		check_ajax_referer( 'cedmc-update_' . $gallery );
		
		foreach( $changes as $key => $value ) {
			if( 'ids' === $key ) {
				$this->remove_media_ids( $gallery );	
				$this->set_media_ids( $gallery, $value );
			} else {
				update_post_meta( $gallery, $key, $value );	
			}
		}
		
		wp_send_json_success( $this->get_data( $gallery ) );
	}
	
	function read_gallery() {
		$gallery =  isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
		wp_send_json_success( $this->get_data( $gallery ) );		
	}

	
	function print_templates() {
		?>
        <script type="text/html" id="tmpl-cedmc-gallery-toolbar"> 
		<div class="primary-bar">
		<a href="#" class="update button">
			<# if( data.ids.length > 0 ) { #>
				<span class="dashicons dashicons-edit cedmc-icon"></span> <?php _e( 'Edit Gallery', 'cedmc' ); ?>
			<# } else { #>
				<span class="dashicons dashicons-plus cedmc-icon"></span> <?php _e( 'Add to Gallery', 'cedmc' ); ?>
			<# } #>
			</a>
		</div>
		<div class="status">
			{{{ data.ids.length }}} <?php _e( ' items ', 'cedmc' ); ?>
		</div>
		
		</script>
		<?php 
	}
	
	
}
endif;