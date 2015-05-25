<?php
//Gallery load

if( ! defined( CEDMC_PLAYLIST ) ) {
	define( CEDMC_PLAYLIST, true );
	
	require_once 'playlist-type.php';
	require_once 'playlist-admin.php';
	require_once 'playlist-ajax.php';	
}