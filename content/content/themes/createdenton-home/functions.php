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

	// Add support for framework features
	add_theme_support( 'hybrid-core-menus', array( 'primary' ) );
	add_theme_support( 'hybrid-core-sidebars', array( 'profile' ) );
	add_theme_support( 'hybrid-core-widgets' );
	add_theme_support( 'hybrid-core-seo' );
	add_theme_support( 'hybrid-core-template-hierarchy' );
	add_theme_support( 'hybrid-core-deprecated' );

	// Load our JS and CSS files
	add_action( 'wp_enqueue_scripts', 'cd_load_scripts', 11 );

	// After user registration, login user
	add_action( 'gform_user_registered', 'pi_gravity_registration_autologin', 10, 4 );
	
	// Change Gravity Forms upload path
	add_filter("gform_upload_path", "change_upload_path", 10, 2);
	
	// Update avatar in user meta via Gravity Forms
	add_filter("gform_post_data", "cd_update_avatar", 10, 3);

	// Register profile sidebar
	register_sidebar(array(
		'name' => __( 'Profile' ),
		'id' => 'profile',
		'description' => __( 'Widgets in this area will be shown on the right-hand side.' )
	));
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

function cd_first_timer() {
	if (!isset( $_COOKIE['create_denton_first_timer'] ) ) {
	    setcookie( 'create_denton_first_timer', 'no' );
	    get_template_part( 'first-timer' );
	    exit();
	}
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
	$first_name		= isset( $user_meta['first_name'][0] );
	$last_name		= isset( $user_meta['last_name'][0] );
	$zip			= isset( $user_meta['user_zip'][0] );
	$primary_job	= isset( $user_meta['user_primary_job'][0] );
	$avatar			= isset( $user_meta['avatar_type'][0] );
		
	$errors = array();
	
	if ( $email == '' )
		$errors[] = ' email';

	if ( !$first_name )
		$errors[] = ' first name';

	if ( !$last_name )
		$errors[] = ' last name';

	if ( !$zip )
		$errors[] = ' zip code';
			
	if ( !$primary_job )
		$errors[] = ' primary job';

	if ( !$avatar )
		$errors[] = ' avatar';
			
	$output = implode( ',', $errors );
	
	return $output;
}

function cd_clean_username( $user_id ) {
	$user_info = get_userdata( $user_id );

	$username = strtolower( $user_info->user_login );
	
	$output = preg_replace("![^a-z0-9]+!i", "-", $username );
	
	return $output;
}

// this function is called by both filters and returns the requested user meta of the current user
function populate_usermeta($meta_key){
    global $current_user;
    return $current_user->__get($meta_key);
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
	
	if( isset( $data->response->result ) ){
	
		if( $attribute == '' ){
			$output = isset( $data->response->result->data->user->identities );
		}
		
		if( $attribute == 'thumbnail' && isset( $data->response->result->data->user->identities->identity[0]->thumbnailUrl ) ) {
			$output = $data->response->result->data->user->identities->identity[0]->thumbnailUrl;
		}
	
		if( $attribute == 'picture' && isset( $data->response->result->data->user->identities->identity[0]->pictureUrl ) ) {
			$output = $data->response->result->data->user->identities->identity[0]->pictureUrl;
		}	
		
	} else {
		$output = get_avatar_url( get_avatar( $user_id, 150 ) );
	}
		
	return $output;
	
}

function get_avatar_url($get_avatar){
    preg_match("/src='(.*?)'/i", $get_avatar, $matches);
    return $matches[1];
}

function cd_timthumbit( $image, $width, $height ) {
	$output = get_stylesheet_directory_uri() . "/timthumb.php?src=". $image ."&w=". $width ."&h=". $height ."&zc=1&a=c&f=2";
	return $output;
}

function cd_is_404( $url ) {
	$handle = curl_init($url);
	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
	
	/* Get the HTML or whatever is linked in $url. */
	$response = curl_exec($handle);
	
	/* Check for 404 (file not found). */
	$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	if($httpCode == 404) {
	    return false;
	} else {
		return true;
	}
	
	curl_close($handle);
	
	/* Handle $response here. */
}

function cd_choose_avatar( $user_id ) {

	// Make sure the user can edit this user
	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;
	
	$user = get_user_by( 'id', $user_id );
	$hash = md5( strtolower( trim( $user->user_email ) ) );
	
	$avatar_local		= get_user_meta( $user_id, 'avatar', true );
	//$avatar_social		= get_user_meta( $user_id, 'oa_social_login_user_thumbnail', true );
	$avatar_social		= cd_get_oneall_user( $user_id, 'thumbnail' );
	$avatar_gravatar	= 'http://www.gravatar.com/avatar/'. $hash .'?s=200&r=pg&d=404';
	if( isset( $avatar_gravatar ) ) {
		if( cd_is_404( $avatar_gravatar ) ){
			$check_gravatar		= file_get_contents($avatar_gravatar);
		} else {
			unset( $avatar_gravatar );
		}
	}

	if( !empty( $avatar_local ) ) {
		echo '<img id="avatar-local" src="'. cd_timthumbit( $avatar_local, 150, 150 ) .'" class="pull-right" width="100">';
	}
	if( !empty( $avatar_social ) ) {
		echo '<img id="avatar-social" src="'. cd_timthumbit( $avatar_social, 150, 150 ) .'" class="pull-right" width="100">';
	}
	if( !empty( $avatar_gravatar ) ) {
		echo '<img id="avatar-gravatar" src="'. cd_timthumbit( $avatar_gravatar, 150, 150 ) .'" class="pull-right" width="100">';
	}
}

function cd_get_avatar( $user_id ) {
	$avatar = get_user_meta( $user_id, 'avatar_type', true );
	$user = get_user_by( 'id', $user_id );
	$hash = md5( strtolower( trim( $user->user_email ) ) );
		
	if( $avatar == 'avatar_social'){
		$image = cd_get_oneall_user( $user_id, 'picture' );
	}
	if( $avatar == 'avatar_gravatar'){
		$image = 'http://www.gravatar.com/avatar/'. $hash .'?s=150';
	}
	if( $avatar == 'avatar_upload' ){
		$image = get_user_meta( $user_id, 'avatar', true );
	}
	
	$output = cd_timthumbit( $image, 150, 150 );
	
	return $output;
}

function change_upload_path($path_info, $form_id){
   $path_info["path"] = get_stylesheet_directory() .'/uploads/avatars/';
   $path_info["url"] = get_stylesheet_directory_uri() .'/uploads/avatars/';
   return $path_info;
}

function cd_update_avatar($post_data, $form, $entry){
	global $current_user;

	if($form["id"] != 2)
		return $post_data;
	
	$file = $entry["10"];
	
	update_user_meta( $current_user->ID, 'avatar', $file );
	
	return $post_data;
}