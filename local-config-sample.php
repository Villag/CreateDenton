<?php
/*
This is a sample local-config.php file
In it, you *must* include the four main database defines

You may include other settings here that you only want enabled on your local development checkouts
*/

// Connect to the remote database
define( 'DB_NAME',			'' );
define( 'DB_USER',			'' );
define( 'DB_PASSWORD',		'' );
define( 'DB_HOST',			'' );

// Override database URLs
define( 'WP_HOME',			'http://createdenton.local' );
define( 'WP_SITEURL',		'http://createdenton.local/wp' );

// Enable WP_DEBUG mode
define( 'WP_DEBUG',			true );

// Enable Debug logging to the /wp-content/debug.log file
define( 'WP_DEBUG_LOG',		true );

// Disable display of errors and warnings 
define( 'WP_DEBUG_DISPLAY',	true );
@ini_set( 'display_errors',	1 );

// Use dev versions of core JS and CSS files
define( 'SCRIPT_DEBUG',		true );