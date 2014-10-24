<?php

if( !class_exists( 'CED_Media_Collection_Type_Admin' )) :

class CED_Media_Collection_Type_Admin extends CED_Post_Type_Admin {
	public $post_type = 'media_collection';
	
	function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_read_media_collection', array( $this, 'get_read_response' ) );	
		add_action( 'wp_ajax_update_media_collection', array( $this, 'get_update_response' ) );	
		add_action( 'wp_ajax_get_media_collection_html', array( $this, 'get_html_response' ) );	
		add_action( 'edit_form_after_title', array( $this, 'show_collection')  );
		add_action( 'print_media_templates', array( $this, 'print_templates' ) );
	}
	
	function add_meta_boxes( $post ) {
		$type = $this->get_collection_type( $post->ID );
		add_meta_box( 'ced_collection_type', __( 'Collection Type', 'cedmc' ), array( $this, 'show_connection_type' ), 'media_collection', 'side', 'high' );
	}
	
	function show_connection_type( $post ) {
		echo '<div id="cedmc-type"></div>';
	}
	
	function enqueue_scripts( $hook ) {
		if( $this->bail() )
			return;
			
		global $post;
		$id = $post->ID;
		wp_enqueue_media();
		wp_register_script( 'cedmc-models', CEDMC_URL . 'js/models.js', array( 'backbone', 'media-editor', 'media-models', 'media-audiovideo' ) );
		wp_register_script( 'cedmc-views', CEDMC_URL . 'js/views.js', array( 'backbone', 'media-editor', 'cedmc-models', 'wp-playlist', 'wp-mediaelement' ) );
		wp_register_script( 'cedmc', CEDMC_URL . 'js/media-collections.js', array( 'backbone', 'cedmc-models', 'cedmc-views' ) );
		wp_localize_script( 'cedmc', 'cedmc_data', $this->get_collection_data( $id ) );
		
		wp_enqueue_script( 'cedmc' );
		
		wp_register_style( 'cedmc', CEDMC_URL . 'css/media-collections.css', array() );
		
		wp_enqueue_style( 'cedmc' );
	}
	
	function show_collection( $post ) {
		if( 'media_collection' !== $post->post_type )	
			return;
			
		/*
		$styles = wp_media_mce_styles(); 
		foreach ( $styles as $style ) { 
			printf( '<link rel="stylesheet" href="%s"/>', $style ); 
		} 
		*/
		
		?>
			<div id="cedmc-main-section">
				<div id="cedmc-toolbar">
				</div>
				<div id="cedmc-content">
				</div>	
			</div>
         <?php
			
		wp_underscore_playlist_templates(); 
	}
	
	
	function get_media_ids( $collection_id ) {
		$media = new WP_Query( array(
		  'connected_type' => 'collection_to_media',
		  'connected_items' => $collection_id,
		  'nopaging' => true,
		  'post_status' => 'inherit',
		  'connected_orderby' => 'media_order',
		  'connected_order' => 'asc',
		  'connected_order_num' => true
		) );
	
		return wp_list_pluck( $media->posts, 'ID' );
	}
	
	function get_collection_data( $collection_id ) {
		
		$meta = (array) get_post_meta( $collection_id, 'cedmc_meta', true );
		return array(
			'id'	 => $collection_id,
			'nonces' => array(
				'update'	=> wp_create_nonce( 'cedmc-update_' . $collection_id ),
			),
			'ids' 	=> $this->get_media_ids( $collection_id ),
			'meta'	=> $meta,
			'type'	=> $this->get_collection_type( $collection_id )
		);
	}
	
	function remove_media_ids( $collection_id ) {
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'collection_to_media' )  ) { 
			$media_ids = $this->get_media_ids( $collection_id );
			foreach( $media_ids as $media_id ) {
				p2p_type( 'collection_to_media' )->disconnect( $collection_id, $media_id );
			}
		}
	}
	
	function set_media_ids( $collection_id, $media_ids ) {
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'collection_to_media' )  ) {
			foreach( $media_ids as $index => $media_id ) {
				p2p_type( 'collection_to_media' )->connect( $collection_id, $media_id, array( 'media_order' => $index ) );
			}	
		}
	}
	
	function get_collection_type( $collection_id ) {
		
		 if ( ! $collection = get_post( $collection_id ) )
	                return false;
		
		$_type = get_the_terms( $collection->ID, 'media_collection_type' );

		if ( empty( $_type ) )	               
				return 'image';
		
		$_type = array_shift( $_type );	
		
		return $_type->slug;
	}
	
	function get_update_response() {
		
		$_put = file_get_contents('php://input');
		$id =  isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
		check_ajax_referer( 'cedmc-update_' . $id );
		
		if( $changes = $_REQUEST['changes'] ) {
			if( $type = $changes['type'] ) {
				wp_set_object_terms( $id, $type, 'media_collection_type' );	
				$this->remove_media_ids( $id );
				update_post_meta( $id, 'cedmc_meta', array( 'orderby' => 'post__in', 'order' => 'ASC' ) );
			} else {			
				if( $ids = $changes['ids'] ) {
					$this->remove_media_ids( $id );
					$this->set_media_ids( $id, $ids );					
				}
				
				if( $meta = $changes['meta'] ) {
					$meta = wp_parse_args( $meta, array( 'orderby' => 'post__in', 'order' => 'ASC' ) );
					update_post_meta( $id, 'cedmc_meta', $meta );
				}
			}
			
		}
		wp_send_json_success( $this->get_collection_data( $id ) );
	}
	
	function get_read_response() {
		$id =  isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
		wp_send_json_success( $this->get_collection_data( $id ) );		
	}
	
	function get_html_response() {
		$data = $_REQUEST['meta'];
		if( ! $data['type'] ) {
			 wp_send_json_error( $data ); 	
		} 
		
		if( 'image' === $data['type'] ) {
			$response = gallery_shortcode( $data );
		} else {
			$response = wp_playlist_shortcode( $data );
		}
		
		 wp_send_json_success( $response ); 
	}
	
	function print_templates() {
		?>
    		<script type="text/html" id="tmpl-cedmc-toolbar"> 
				<a href="#" class="update button">
					<# if( data.ids.length > 0 ) { #>
						<span class="dashicons dashicons-edit cedmc-icon"></span> <?php _e( 'Edit', 'ced' ); ?> 
					<# } else { #>
						<span class="dashicons dashicons-plus cedmc-icon"></span> <?php _e( 'Add to ', 'ced' ); ?> 
					<# } #>
					<# if( "image" === data.type ) { #>
						<?php _e( 'Gallery ', 'ced' ); ?>
					<# } else  { #>
						<?php _e( 'Playlist ', 'ced' ); ?>
					<# } #>
				</a>
				<div class="status">
					{{{ data.ids.length }}} <span class="dashicons dashicons-format-{{{ data.type }}} cedmc-icon"></span>					
				</div>
			</script>
		<?php 
		
		?>
		<script type="text/html" id="tmpl-cedmc-selector">
			<select name="media_collection_type" class="widefat selector">
				<option value="image" <# if( "image" === data.type ) { #> selected="selected" <# } #>><?php _e( 'Image Gallery', 'cedmc') ?></option>
				<option value="audio" <# if( "audio" === data.type ) { #> selected="selected" <# } #>><?php _e( 'Audio Playlist', 'cedmc') ?></option>
				<option value="video" <# if( "video" === data.type ) { #> selected="selected" <# } #>><?php _e( 'Video Playlist', 'cedmc') ?></option>
			</select>
		</script>
        <?php
	}
	
	
}
endif;
