<?php

if( !class_exists( 'CED_Playlist_Type_Admin' )) :

class CED_Playlist_Type_Admin extends CED_Post_Type_Admin {
	public $post_type = 'playlist';
	
	function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_cedmc_read_playlist', array( $this, 'read_playlist' ) );	
		add_action( 'wp_ajax_cedmc_update_playlist', array( $this, 'update_playlist' ) );	
		add_action( 'edit_form_after_title', array( $this, 'show_playlist')  );
		add_action( 'print_media_templates', array( $this, 'print_templates' ) );
	}

	function enqueue_scripts( $hook ) {
		if( $this->bail() || ( 'post.php' !== $hook && 'post-new.php' !== $hook ) ) {
			return;
		}

		wp_register_script( 'cedmc-playlist', CEDMC_URL . 'js/playlist.js', array( 'backbone', 'cedmc-models', 'cedmc-views' ) );
		wp_enqueue_script( 'cedmc-playlist' );
		
		wp_enqueue_style( 'cedmc' );
	}
	
	function add_columns( $columns ) {		
		return array_slice( $columns, 0, 2, true ) + array( 'type' => 'Type', 'media' => 'Media' ) + array_slice( $columns, 2, NULL, true );	
	}
	
	function manage_columns( $column, $id ) {
		global $post;
		if( 'media' === $column ) {
			$media = count( $post->media );
			if( ( $media > 0 ) ) {
				printf('<a href="%s">%s %s</a>', admin_url( 'upload.php?media_in_playlist=' . $id ), $media,  _n( 'item', 'items', $media ) );
			} else {
				echo "—";	
			}
		} elseif( 'type' === $column ) {
			$types = get_the_terms( $id, 'playlist_type' );
			if( !$types || is_wp_error( $types ) ) {
				echo "—";
			} else {
				$type = array_shift( $types );	
				echo $type->name;
			}
		}
		
	}
	
	function restrict_posts() {			
		if(	$this->bail() ) {
			return;
		}
			
		$this->generate_taxonomy_select( 'playlist_type', 'playlist_type' );
	}
	
	function get_data( $playlist = 0 ) {
		$playlist = get_post( $playlist );
		$meta = array(
			'id' => $playlist->ID,
			'ids' => (array) get_playlist_media_ids( $playlist ),
			'nonces' => array(
				'update'	=> wp_create_nonce( 'cedmc-update_' . $playlist->ID ),
			)
		);
		$meta = array_merge( $meta, (array) get_playlist_meta( $playlist ) );		
		return $meta;
	}
	
	function show_playlist( $post ) {
		if( 'playlist' !== $post->post_type )	
			return;
		?>
        <div id="cedmc-main">
            <div id="cedmc-toolbar">
            </div>
            <div id="cedmc-preview">
            </div>	
        </div>
        <label for="content"><strong>Description</strong>:</label>
		<?php
		wp_editor( $post->post_content, 'content', array( 'tinymce' => false, 'media_buttons' => false ) );
	}

	
	function remove_media_ids( $playlist ) {
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'playlist_to_media' )  ) { 
			$media_ids = get_playlist_media_ids( $playlist );
			foreach( $media_ids as $media_id ) {
				p2p_type( 'playlist_to_media' )->disconnect( $playlist, $media_id );
			}
		}
	}
	
	function set_media_ids( $playlist, $media_ids ) {
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'playlist_to_media' )  ) {
			foreach( $media_ids as $index => $media_id ) {
				p2p_type( 'playlist_to_media' )->connect( $playlist, $media_id, array( 'media_order' => $index ) );
			}	
		}
	}

	function update_playlist() {
		$playlist =  isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;	
		$changes =  isset( $_REQUEST['changes'] ) ? (array) $_REQUEST['changes'] : array();
		check_ajax_referer( 'cedmc-update_' . $playlist );
		
		if( $changes['ids'] ) {
			$this->remove_media_ids( $playlist );	
			$this->set_media_ids( $playlist, $changes['ids'] );
			unset( $changes['ids'] );
		}
		
		if( $changes['type'] ) {
			wp_set_object_terms( $playlist, $changes['type'], 'playlist_type' );
			unset( $changes['type'] );
		}
		
		if( $changes ) {
			$old = get_gallery_meta( $playlist );
			$new = wp_parse_args( $changes, $old );
			unset( $new['type'] );
			update_post_meta( $playlist, '_playlist_metadata', $new );
		}

		wp_send_json_success( $this->get_data( $playlist ) );
	}
	
	function read_playlist() {
		$playlist =  isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
		wp_send_json_success( $this->get_data( $playlist ) );		
	}

	
	function print_templates() {
		?>
        <script type="text/html" id="tmpl-cedmc-playlist-toolbar"> 
		<div class="primary-bar">
				<a href="#" class="update button ">
				<# if( data.ids.length > 0 ) { #>
					<span class="dashicons dashicons-edit cedmc-icon"></span> <?php _e( 'Edit Playlist', 'cedmc' ); ?>
				<# } else { #>
					<span class="dashicons dashicons-plus cedmc-icon"></span> <?php _e( 'Add to Playlist', 'cedmc' ); ?>
				<# } #>
			</a>
			<select class="type">
				<option value="audio" <# if( 'audio' === data.type ) { #> selected="selected" <# } #>><?php _e( 'Audio Playlist', 'cedmc' ); ?></option>
				<option value="video" <# if( 'video' === data.type ) { #> selected="selected" <# } #>><?php _e( 'Video Playlist', 'cedmc' ); ?></option>
			</select>
		</div>
		<div class="status">
			{{{ data.ids.length }}} <?php _e( ' items ', 'cedmc' ); ?>
		</div>
		</script>
        <script type="text/html" id="tmpl-cedmc-playlist">
		<# if ( data.tracks ) { #>
				<div class="wp-playlist wp-{{ data.type }}-playlist wp-playlist-{{ data.style }}">
						<# if ( 'audio' === data.type ){ #>
						<div class="wp-playlist-current-item"></div>
						<# } #>
						<{{ data.type }} controls="controls" preload="none" <#
								if ( data.width ) { #> width="{{ data.width }}"<# }
								#><# if ( data.height ) { #> height="{{ data.height }}"<# } #>></{{ data.type }}>
						<div class="wp-playlist-next"></div>
						<div class="wp-playlist-prev"></div>
				</div>
				<div class="wpview-overlay"></div>
		<# } else { #>
				<div class="wpview-error">
						<div class="dashicons dashicons-video-alt3"></div><p><?php _e( 'No items found.' ); ?></p>
				</div>
		<# } #>
		</script>
		<?php 
		wp_underscore_playlist_templates();
	}
	
	
}
endif;