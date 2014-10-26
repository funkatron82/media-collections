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
require_once( CEDMC_INC_DIR . 'post-type.php' );
require_once( CEDMC_INC_DIR . 'post-type-admin.php' );
require_once( CEDMC_INC_DIR . 'functions.php' );
require_once( CEDMC_INC_DIR . 'playlist-type.php' );
require_once( CEDMC_INC_DIR . 'gallery-type.php' );
require_once( CEDMC_INC_DIR . 'gallery-admin.php' );
require_once( CEDMC_INC_DIR . 'playlist-admin.php' );


function cedmc_plugin_load() {
	//Core
	
	//Admin
	if( is_admin() ){
		new CED_Gallery_Type_Admin();
		new CED_Playlist_Type_Admin();
	}
	
	//Front end
	else{
	}
}
add_action( 'plugins_loaded', 'cedmc_plugin_load' );

//Post Type
new CED_Gallery_Type();
new CED_Playlist_Type();

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
add_action( 'admin_enqueue_scripts', 'cedmc_enqueue_scripts' );
