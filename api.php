<?php
/**
*	Add wp-json endpoints
*/
add_action( 'rest_api_init', function () {
	register_rest_route( 'wp-content-deploy/v1', '/posts/', array(
		'methods' => 'GET',
		'callback' => 'wpcd_post_list',
	) );
	register_rest_route( 'wp-content-deploy/v1', '/users/', array(
		'methods' => 'GET',
		'callback' => 'wpcd_user_list',
	) );

} );


/**
*	Returns list of posts by type and modified timestamp
*
*	@return $return array 
*/
function wpcd_post_list() {
	global $wpdb;
	$return = array();
	$results = $wpdb->get_results("SELECT guid, post_modified_gmt FROM {$wpdb->prefix}posts WHERE post_status = 'publish';", OBJECT);
	foreach ($results as $result) {
		$return[$result->guid] = $result->post_modified_gmt;
	}
	return $return;
}



/**
*	Returns list of users and postmeta
*
*	@return $return array 
*/
function wpcd_user_list() {
	global $wpdb;
	$return = array();

	// TODO
	// Get list of users by email, hash of user_meta

	return $return;
}