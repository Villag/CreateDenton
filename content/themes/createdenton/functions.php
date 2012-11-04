<?php

/* Load the core theme framework. */
require_once( trailingslashit( TEMPLATEPATH ) . 'library/hybrid.php' );
$theme = new Hybrid();

/* Do theme setup on the 'after_setup_theme' hook. */
add_action( 'after_setup_theme', 'cd_theme_setup' );

/**
 * Theme setup function. This function adds support for theme features and defines the default theme
 * actions and filters.
 *
 * @since 1.0
 */
function cd_theme_setup() {
	
register_sidebar(array(
'name' => __( 'Profile' ),
'id' => 'profile',
'description' => __( 'Widgets in this area will be shown on the right-hand side.' )
));

	/* Add support for framework features. */
	add_theme_support( 'hybrid-core-menus', array( 'primary' ) );
	add_theme_support( 'hybrid-core-sidebars', array( 'profile' ) );
	add_theme_support( 'hybrid-core-widgets' );
	add_theme_support( 'hybrid-core-seo' );
	add_theme_support( 'hybrid-core-template-hierarchy' );
	add_theme_support( 'hybrid-core-deprecated' );
	
	// Check user for IP and display launch screen if not listed
	add_action( 'template_redirect', 'cd_launch_check', 11 );

	// Load our JS and CSS files
	add_action( 'wp_enqueue_scripts', 'cd_load_scripts', 11 );

	// After user registration, login user
	add_action( 'gform_user_registered', 'pi_gravity_registration_autologin', 10, 4 );

}

/**
 * Queue up all of our JS and CSS for loading in the correct
 * order and location within templates.
 */
function cd_load_scripts() {

	// Dequeue scripts/styles loaded by the parent theme
	wp_dequeue_script( 'twentytwelve-navigation' );
	wp_dequeue_style( 'twentytwelve-fonts' );
	
	// Queue CSS
	wp_enqueue_style( 'style',				get_stylesheet_directory_uri() .'/style.css' );
	
	// Queue JS
	// Load Modernizr into the HEAD, before any other scripts
	wp_enqueue_script( 'modernizr',			get_stylesheet_directory_uri() .'/js/modernizr.2.5.3.min.js',		'', '1.0', false );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'isotope',			get_stylesheet_directory_uri() .'/js/jquery.isotope.min.js',		array( 'jquery' ), '1.0', true );
	wp_enqueue_script( 'foundation',		get_stylesheet_directory_uri() .'/js/foundation.min.js',			array( 'jquery' ), '1.0', true );
	//wp_enqueue_script( 'infinite-scroll',	get_stylesheet_directory_uri() .'/js/jquery.infinite-scroll.min.js',array( 'jquery' ), '1.0', true );
	//wp_enqueue_script( 'blur',			get_stylesheet_directory_uri() .'/js/blur.min.js',					array( 'jquery' ), '1.0', true );
	wp_enqueue_script( 'app',				get_stylesheet_directory_uri() .'/js/app.js',						array( 'jquery' ), '1.0', true );
}

/**
 * Auto login after registration.
 */
function pi_gravity_registration_autologin( $user_id, $user_config, $entry, $password ) {
	$user = get_userdata( $user_id );
	$user_login = $user->user_login;
	$user_password = $password;

    wp_signon( array(
		'user_login'	=> $user_login,
		'user_password'	=> $user_password,
		'remember'		=> false
    ) );
}

/**
 * Checks if the user is valid (has all the right info) and returns boolean.
 * 
 */
function cd_is_valid_user( $user_id ) {
	
	if( cd_user_errors( $user_id ) == null )
		return true;
	else
		return false;
}

/**
 * Displays the errors a user has
 * (i.e. missing data required to be a valid user)
 */
function cd_user_errors( $user_id ) {
	
	$user_data		= get_userdata( $user_id );
	$email			= $user_data->user_email;
	
	$user_meta		= get_user_meta( $user_id );
	$first_name		= $user_meta['first_name'][0];
	$last_name		= $user_meta['last_name'][0];
	$primary_job	= $user_meta['Primary Job'][0];
	
	$errors = array();
	
	if ( $email == '' )
		$errors[] = 'email';

	if ( !$first_name )
		$errors[] = 'first name';

	if ( !$last_name )
		$errors[] = 'last name';
		
	if ( !$primary_job )
		$errors[] = 'primary job';
	
	$output = implode( ', ', $errors );
	
	return $output;
}

function cd_clean_username( $user_id ) {
	$user_info = get_userdata( $user_id );

	$username = strtolower( $user_info->user_login );
	
	$output = preg_replace("![^a-z0-9]+!i", "-", $username );
	
	return $output;
}

/**
 * Use the launch template if the user is not local (on MAMP)
 * or if the user is not on given IP addresses.
 */
function cd_launch_check() {
	
	// If this is not local dev, show the launch page
	if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV == false ) {
		include ( STYLESHEETPATH . '/page-template-launch.php' );
		exit;
	}

}

// Get the gravatar URL
// source: http://wordpress.stackexchange.com/questions/46904/how-to-get-gravatar-url-alone
function cd_get_gravatar_url( $email ) {
    $hash = md5( strtolower( trim ( $email ) ) );
	$default = urlencode( get_stylesheet_directory_uri() .'/images/default_avatar.png' );
    return 'http://gravatar.com/avatar/' . $hash .'?size=150&default='. $default;
}

function get_avatar_url($get_avatar){
    preg_match("/src='(.*?)'/i", $get_avatar, $matches);
    return $matches[1];
}

function cd_get_oneall_user( $user_id, $attribute = '' ) {

	//Read settings
	$settings = get_option ('oa_social_login_settings');

	//API Settings
	$api_connection_handler = ((!empty ($settings ['api_connection_handler']) AND $settings ['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
	$api_connection_use_https = ((!isset ($settings ['api_connection_use_https']) OR $settings ['api_connection_use_https'] == '1') ? true : false);

	$site_subdomain = (!empty ($settings ['api_subdomain']) ? $settings ['api_subdomain'] : '');
	$site_public_key = (!empty ($settings ['api_key']) ? $settings ['api_key'] : '');
	$site_private_key = (!empty ($settings ['api_secret']) ? $settings ['api_secret'] : '');

	//API Access Domain
	$site_domain = $site_subdomain . '.api.oneall.com';

	$user_token = get_user_meta($user_id, 'oa_social_login_user_token', true);

	//Connection Resource
	$resource_uri = 'https://' . $site_domain . '/users/' . $user_token . '.json';

	// Initializing curl
	$ch = curl_init($resource_uri);

	// Configuring curl options
	$options = array(CURLOPT_URL => $resource_uri, CURLOPT_HEADER => 0, CURLOPT_USERPWD => $site_public_key . ":" . $site_private_key, CURLOPT_TIMEOUT => 15, CURLOPT_VERBOSE => 0, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => 1, CURLOPT_FAILONERROR => 0);

	// Setting curl options
	curl_setopt_array($ch, $options);

	// Getting results
	$result = curl_exec($ch);
		
	$data = json_decode($result);
	
	$output = '';
	
	if( !empty( $data->response->result ) ){
	
		if( $attribute == '' ){
			$output = $data->response->result->data->user->identities;
		}
		
		if( $attribute == 'thumbnail' ) {
			$output = $data->response->result->data->user->identities->identity[0]->thumbnailUrl;
		}
	
		if( $attribute == 'picture' ) {
			$output = $data->response->result->data->user->identities->identity[0]->pictureUrl;
		}	
		
	} else {
		$output = get_avatar_url( get_avatar( $user_id, 150 ) );
	}
	
	$output = cd_timthumbit( $output, 150, 150 );
	
	return $output;
	
}

// Function to display the custom-sized gravatar
function cd_avatar_timthumb($user_id, $width, $height, $class) {
	global $current_user;
    $custom = get_stylesheet_directory_uri() . "/timthumb.php?src=". get_avatar_url(get_avatar( $user_id, 150 )) ."&w=". $width ."&h=". $height ."&zc=1&a=c&f=2";
    return $custom;
}

function cd_timthumbit( $image, $width, $height ) {
	$output = get_stylesheet_directory_uri() . "/timthumb.php?src=". $image ."&w=". $width ."&h=". $height ."&zc=1&a=c&f=2";
	return $output;
}

function cd_first_timer() {
	if (!isset( $_COOKIE['create_denton_first_timer'] ) ) {
	    setcookie( 'create_denton_first_timer', 'no' );
	    get_template_part( 'first-timer' );
	    exit();
	}
}

function cd_choose_avatar( $user_id ) {
	
	$user_meta		= get_user_meta( $user_id );
	$first_name		= $user_meta['first_name'][0];
	
	if ( $user_meta['avatar'][0] = 'facebook' ) {
		cd_get_facebook_avatar_url( $user_id );
	} elseif ( $user_meta['avatar'][0] = 'twitter' ) {
		cd_get_twitter_avatar_url( $user_id );
	} else {
		cd_get_gravatar_url( $user_id );
	}
	
}

add_filter('gform_field_value_user_firstname', create_function("", '$value = populate_usermeta(\'first_name\'); return $value;' ));
add_filter('gform_field_value_user_lastname', create_function("", '$value = populate_usermeta(\'last_name\'); return $value;' ));
add_filter('gform_field_value_user_email', create_function("", '$value = populate_usermeta(\'user_email\'); return $value;' ));

// this function is called by both filters and returns the requested user meta of the current user
function populate_usermeta($meta_key){
    global $current_user;
    return $current_user->__get($meta_key);
}

function cd_real_avatar( $user_id ) {
	$avatar = get_user_meta( $user_id, 'oa_social_login_user_thumbnail', true );
	if ( strpos( $avatar, 'facebook' ) !== false) {
		$output = $avatar .'&type=large';
	} elseif ( strpos( $avatar, 'twitter' ) !== false) {
		$output = $avatar .'&type=large';
	} elseif ( strpos( $avatar, 'google' ) !== false) {
		$output = $avatar .'&type=large';
	} elseif ( strpos( $avatar, 'linkedin' ) !== false) {
		$output = $avatar .'&type=large';
	} else {
		$output = $avatar;
	}
	
	return $output;
}