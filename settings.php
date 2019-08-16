<?php
/**
*	Gets a setting from wp_options, or if already set from a global
*	Example: define ('WPCD_REMOTE_URL', 'https://someurl.com');
*
*	@param string $setting 
*	@return string
*/
function wpcd_setting( $setting ) {
	if( defined( strtoupper($setting) ) )  {
		return constant( strtoupper($setting) );
	}
	else{
		return get_option( $setting );
	}
}


/**
*	Checks whether a setting is being overridden by a global
*	Example: define ('WPCD_REMOTE_URL', 'https://someurl.com');
*
*	@param string $setting 
*	@return string constant|option
*/
function wpcd_setting_type( $setting ) {
	if( defined( strtoupper($setting) ) )  {
		return 'constant';
	}
	else{
		return 'option';
	}
}


/**
*	Admin Page for Plugin Settings
*/
function wpcd_page() {
	$wpcd_remote_url = wpcd_setting( 'wpcd_remote_url' );
	$wpcd_key = wpcd_setting( 'wpcd_key' );
	$wpcd_default_user = wpcd_setting( 'wpcd_default_user' );
	$wpcd_local_environment = wpcd_setting( 'wpcd_local_environment' );

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
							<input '.(wpcd_setting_type( 'wpcd_local_environment' ) == 'constant' ? 'disabled' : '').' type="radio" name="wpcd_local_environment" class="regular-text code" value="staging" '.($wpcd_local_environment == 'staging' ? 'checked' : '').'>
							staging
						</label>
						<br>
						<label>
							<input '.(wpcd_setting_type( 'wpcd_local_environment' ) == 'constant' ? 'disabled' : '').' type="radio" name="wpcd_local_environment" class="regular-text code" value="production" '.($wpcd_local_environment == 'production' ? 'checked' : '').'>
							production
						</label>
					</td>
				</tr>
				<tr>
					<th><label>Remote Server URL</label></th>
					<td><input type="text" name="wpcd_remote_url" id="wpcd_remote_url" class="regular-text code" '.(wpcd_setting_type( 'wpcd_remote_url' ) == 'constant' ? 'disabled' : '').' value="'.$wpcd_remote_url.'"></td>
				</tr>
				<tr>
					<th><label>Deployment Key</label></th>
					<td>
						<input type="password" name="wpcd_key" id="wpcd_key" class="regular-text code" '.(wpcd_setting_type( 'wpcd_key' ) == 'constant' ? 'disabled' : '').' value="'.$wpcd_key.'">
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
*	Returns a Dropdown to pick from administrators and editors to use as the default deploying user
*
* 	@param int $wpcd_default_user User ID of current default user
*	@return string <select> element
*/
function wpcd_select_default_user($wpcd_default_user) {
	if( wpcd_setting_type( 'wpcd_default_user' ) == 'constant' ) {
		return '<input disabled type="text" value="'.wpcd_setting('wpcd_default_user').'">';
	}


	$return = '<select name="wpcd_select_default_user">';
	$admin_users = get_users( array('role__in' => array('administrator', 'editor') ) );
	foreach( $admin_users as $user ) {
		if( $user->display_name === $wpcd_default_user ) {
			$return .= '<option selected value="'.$user->display_name.'">'.esc_html( $user->display_name ).'</option>';
		}
		else{
			$return .= '<option value="'.$user->display_name.'">'.esc_html( $user->display_name ).'</option>';
		}
	}
	$return .= '</select>';
	return $return;
}
?>
