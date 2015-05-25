<?php
if( ! defined( 'P2P_TEXTDOMAIN' ) ) {
	define( 'P2P_TEXTDOMAIN', 'posts-to-posts' );
	
	require '/scb/load.php';
	
	function ced_load_p2p_core() {
		if ( !function_exists( 'p2p_register_connection_type' ) ) {
			load_plugin_textdomain( P2P_TEXTDOMAIN, '', basename( dirname( __FILE__ ) ) . '/lang' );
			require_once CEDMC_INC_DIR . '/p2p-core/autoload.php';
	
			P2P_Storage::init();
		
			P2P_Query_Post::init();
			P2P_Query_User::init();
		
			P2P_URL_Query::init();	
		}
	}
	
	scb_init( 'ced_load_p2p_core' );

}