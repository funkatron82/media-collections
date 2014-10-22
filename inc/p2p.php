<?php
if( !defined( 'P2P_TEXTDOMAIN' ) )
	define( 'P2P_TEXTDOMAIN', 'posts-to-posts' );
	
require CEDMC_INC_DIR . '/scb/load.php';

scb_init( 'cedmc_p2p_core_init' );

function cedmc_p2p_core_init() {
	add_action( 'plugins_loaded', 'cedmc_load_p2p_core', 20 );
}

function cedmc_load_p2p_core() {
	if ( function_exists( 'p2p_register_connection_type' ) )
		return;

	require_once CEDMC_INC_DIR . '/p2p-core/init.php';

	add_action( 'admin_init', array( 'P2P_Storage', 'install' ) );
}