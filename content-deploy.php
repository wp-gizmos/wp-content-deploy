<?php
/*
  Plugin Name: WP Content Deploy
  Description: Selectively batched content deployment from staging to production
  Version: 1.0
  Author: Matt Beck, Gabe Shaughnessey
  License: GPL2
*/

require_once('settings.php'); //settings page
require_once('batch.php'); //page for creating and sending batches

/**
*	WordPress css/js enqueue for wp-admin pages
*/
function wpcd_enqueue() {
	$admin_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS);
	if( preg_match('/^wp-content-deploy/', $admin_page) ) {
		wp_enqueue_style( 'wpcd_css', plugins_url('wpcd-admin.css', __FILE__) );
		wp_enqueue_script( 'wpcd_js', plugins_url('wpcd-admin.js', __FILE__) );
	}
}
add_action( 'admin_enqueue_scripts', 'wpcd_enqueue' );


/**
*	Add pages in admin under Dashboard
*/
function wpcd_menu_page() {
	add_menu_page( '', 'Content Deploy', 'manage_options', 'wp-content-deploy', 'wpcd_page', 'dashicons-upload', 4 );
	add_submenu_page( 'wp-content-deploy', 'Settings', 'Settings', 'manage_options', 'wp-content-deploy', 'wpcd_page' );
	add_submenu_page( 'wp-content-deploy', 'Add Batch', 'Add Batch', 'manage_options', 'wp-content-deploy-batch', 'wpcd_batch_page' );
}
add_action( 'admin_menu', 'wpcd_menu_page' );



/**
*	Returns a Dropdown to pick from administrators and editors to use as the default deploying user
*
* 	@param int $wpcd_default_user User ID of current default user
*	@return string <select> element
*/
function wpcd_select_default_user($wpcd_default_user) {
	$return = '<select name="wpcd_select_default_user">';
	$admin_users = get_users( array('role__in' => array('administrator', 'editor') ) );
	foreach( $admin_users as $user ) {
		if( $user->ID === $wpcd_default_user ) {
			$return .= '<option selected value="'.$user->ID.'">'.esc_html( $user->display_name ).'</option>';
		}
		else{
			$return .= '<option value="'.$user->ID.'">'.esc_html( $user->display_name ).'</option>';
		}
	}
	$return .= '</select>';
	return $return;
}
