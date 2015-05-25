<?php
/*
Plugin Name: Media Collections
Plugin URI: 
Description: Creates perisitent and embeddible galleries and playlists
Version: 0.1
Author: Manny "Funkatron" Fleurmond
Author URI: http://www.crosseyedesign.com
License: GPL2
*/


require_once( 'config.php' );
require_once( CEDMC_INC_DIR . 'p2p.php' );
require_once( CEDMC_INC_DIR . 'post/load.php' );
require_once( 'gallery/load.php' );
require_once( 'playlist/load.php' );
require_once( CEDMC_INC_DIR . 'functions.php' );


//Core
new CED_Gallery_Type();
new CED_Playlist_Type();

//Admin
if( is_admin() ){
	new CED_Gallery_Type_Admin();
	new CED_Playlist_Type_Admin();
}

//Activate
function cedmc_activate() {
	do_action( 'cedmc_activate' );
	do_action( 'gallery_activate' );
	do_action( 'playlist_activate' );
	flush_rewrite_rules();	
}
register_activation_hook( __FILE__, 'cedmc_activate' );

//Deactivate
function cedmc_deactivate() {
	do_action( 'cedmc_deactivate' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cedmc_deactivate' );

//Common scripts and styles
function cedmc_enqueue_scripts( $hook ) {
	wp_enqueue_media();
	wp_register_script( 'cedmc-models', CEDMC_URL . 'js/models.js', array( 'backbone', 'media-editor', 'media-models', 'media-audiovideo' ) );
	wp_register_script( 'cedmc-views', CEDMC_URL . 'js/views.js', array( 'backbone', 'media-editor', 'cedmc-models', 'wp-playlist', 'wp-mediaelement' ) );
	
	wp_register_style( 'cedmc', CEDMC_URL . 'css/media-collections.css', array() );
}

add_action( 'admin_enqueue_scripts', 'cedmc_enqueue_scripts', 5 );

function cedmc_templates(){
		?>
        <script type="text/html" id="tmpl-cedmc-edit-button"> 
			<span class="dashicons dashicons-edit cedmc-icon"></span> 
			<# if( data.ids.length > 0 ) { #>
				<?php _e( 'Edit', 'cedmc' ); ?>
			<# } else { #>
				<?php _e( 'Add to', 'cedmc' ); ?>
			<# } #>
			
			<# if( 'gallery' === data.type ) { #>
				<?php _e( ' Gallery', 'cedmc' ); ?>
			<# } else { #>
				<?php _e( ' Playlist', 'cedmc' ); ?>
			<# } #>
		</script>
        
        <script type="text/html" id="tmpl-cedmc-status"> 
			<# if( data.ids.length ) { #>
       			{{{ data.ids.length }}} 
			<# } else { #>
				<?php _e( 'No', 'cedmc' ); ?>
			<# } #>
			<?php _e( ' items ', 'cedmc' ); ?>
		</script>
        
        <script type="text/html" id="tmpl-cedmc-collection"> 			
            <div id="cedmc-toolbar">
				<div class="cedmc-primary-bar">	</div>
				<div class="cedmc-secondary-bar"></div>	
            </div>
            <div id="cedmc-preview"></div>	
		</script>
        <?php	
}

add_action( 'print_media_templates', 'cedmc_templates' );
