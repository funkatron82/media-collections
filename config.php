<?php
//vVrsion
if (!defined('CEDMC_VERSION'))
	define("CEDMC_VERSION", "1.0" ); 
	
//Plugin dir
if (!defined('CEDMC_DIR'))
	define('CEDMC_DIR', plugin_dir_path( __FILE__ ) );
define('CEDMC_INC_DIR', trailingslashit( CEDMC_DIR . 'inc' ) );


//Plugin url
if (!defined('CEDMC_URL'))
	define('CEDMC_URL',  plugin_dir_url( __FILE__ ));
define('CEDMC_INC_URL', trailingslashit( CEDMC_URL . 'inc' ) );
