<?php
//Gallery load

if( ! defined( CEDMC_GALLERY ) ) {
	define( CEDMC_GALLERY, true );
	
	require_once 'gallery-type.php';
	require_once 'gallery-admin.php';
	require_once 'gallery-ajax.php';	
}