<?php
/*
  Plugin Name: WP Content Deploy
  Description: Selectively batched content deployment from staging to production
  Version: 1.0
  Author: Matt Beck, Gabe Shaughnessey
  License: GPL2
*/


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
*	Admin Page for Plugin Settings
*/
function wpcd_page() {
	$wpcd_remote_url = get_option( 'wpcd_remote_url' );
	$wpcd_key = get_option( 'wpcd_key' );
	$wpcd_default_user = get_option( 'wpcd_default_user' );
	$wpcd_local_environment = get_option( 'wpcd_local_environment' );

	if(!isset($_POST['wpcd_nonce']) || ! wp_verify_nonce( $_POST['wpcd_nonce'], 'wpcd_save' )) {
		// Set up the Form defaults
		$wpcd_default_user = ( !empty( $wpcd_default_user ) ) ? $wpcd_default_user : get_current_user_id();
		$wpcd_local_environment = ( !empty( $wpcd_local_environment ) ) ? $wpcd_local_environment : 'staging';
	}
	else{
		// Nonce is valid, Process the Form
		$wpcd_remote_url = filter_input( INPUT_POST, 'wpcd_remote_url', FILTER_SANITIZE_SPECIAL_CHARS );
		$wpcd_key = filter_input( INPUT_POST, 'wpcd_key', FILTER_SANITIZE_SPECIAL_CHARS );
		$wpcd_default_user = filter_input( INPUT_POST, 'wpcd_default_user', FILTER_SANITIZE_SPECIAL_CHARS );
		$wpcd_local_environment = filter_input( INPUT_POST, 'wpcd_local_environment', FILTER_SANITIZE_SPECIAL_CHARS);
		update_option( 'wpcd_remote_url', $wpcd_remote_url, false );
		update_option( 'wpcd_key', $wpcd_key, false );
		update_option( 'wpcd_default_user', $wpcd_key, false );
		update_option( 'wpcd_local_environment', $wpcd_local_environment, false );
	}

	echo '
	<div class="wrap">
		<h1>Content Deployment Settings</h1>
		<div class="notice notice-info is-dismissible"><p>All fields are required on both staging and production.</p></div>
		<form method="post">
			'.wp_nonce_field( 'wpcd_save', 'wpcd_nonce' ).'
			<table class="form-table">
				<tr>
					<th><label>Local Server Environment</label></th>
					<td>
						<label>
							<input type="radio" name="wpcd_local_environment" class="regular-text code" value="staging" '.($wpcd_local_environment == 'staging' ? 'checked' : '').'>
							staging
						</label>
						<br>
						<label>
							<input type="radio" name="wpcd_local_environment" class="regular-text code" value="production" '.($wpcd_local_environment == 'production' ? 'checked' : '').'>
							production
						</label>
					</td>
				</tr>
				<tr>
					<th><label>Remote Server URL</label></th>
					<td><input type="text" name="wpcd_remote_url" id="wpcd_remote_url" class="regular-text code" value="'.$wpcd_remote_url.'"></td>
				</tr>
				<tr>
					<th><label>Deployment Key</label></th>
					<td>
						<input type="password" name="wpcd_key" id="wpcd_key" class="regular-text code" value="'.$wpcd_key.'">
						<button class="button-secondary" id="wpcd-key-toggle">show</button>
						<p><button class="button-secondary" id="wpcd-cd-keygen">Generate Key</button></p>
						<p>This key must match on both staging and production sites</p>
					</td>
				</tr>
				<tr>
					<th><label>Deployment User</label></th>
					<td>
						'.wpcd_select_default_user( $wpcd_default_user ).'
					</td>
				<tr>
					<th></th>
					<td><input type="submit" class="button-primary" value="Save Settings"></td>
				</tr>
			</table>
		</form>
	</div>';
}


/**
*	Admin Page for Creating/Processing Batch Jobs
*/
function wpcd_batch_page() {
	$wpcd_key = get_option('wpcd_key');
	echo '<div class="wrap">
		<h1>Content Deploy Batch</h1>';

		if (empty( $wpcd_key ) ) { // TODO: Validate Remote instead of just checking for presense of key
			echo '<div class="notice notice-error"><p>You must set a deploy key on staging and production before you can create a batch.</p></div>';
		}
		else{
			// Create Batch
		}

	echo '</div>';
}


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
