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

		wp_enqueue_script( 'cedmc-playlist', CEDMC_URL . 'js/playlist.js', array( 'backbone', 'cedmc-models', 'cedmc-views' ) );		
		wp_enqueue_style( 'cedmc' );
	}
	
	function add_columns( $columns ) {		
		return array_slice( $columns, 0, 2, true ) + array( 'taxonomy-playlist_type' => 'Type', 'media' => 'Media' ) + array_slice( $columns, 2, NULL, true );	
	}
	
	function manage_columns( $column, $id ) {
		global $post;
		if( 'media' === $column ) {
			$media = count( $post->media );
			if( ( $media > 0 ) ) {
				printf('<a href="%s" target="_new">%s %s</a>', admin_url( 'upload.php?media_in_playlist=' . $id ), $media,  _n( 'item', 'items', $media ) );
			} else {
				echo "—";	
			}
		} 		
	}
	
	function restrict_posts() {			
		if(	$this->bail() ) {
			return;
		}
			
		$this->generate_taxonomy_filter( 'playlist_type' );
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

	
	function print_templates() {
		?>
        <script type="text/html" id="tmpl-cedmc-playlist-type">
        	<option value="audio"><?php _e( 'Audio Playlist', 'cedmc' ); ?></option>
			<option value="video"><?php _e( 'Video Playlist', 'cedmc' ); ?></option>
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
						<div class="dashicons dashicons-video-alt3"></div><p><?php _e( 'No items found.', cedmc ); ?></p>
				</div>
		<# } #>
		</script>

		<?php 
		wp_underscore_playlist_templates();
	}
	
	
}
endif;