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

	$query = "SELECT u.*,
		SHA1(
				(
				SELECT GROUP_CONCAT( CONCAT_WS(':', m.meta_key, m.meta_value) SEPARATOR ',' )
				FROM {$wpdb->prefix}usermeta m WHERE m.user_id = u.ID ORDER BY m.meta_key
				)
			) AS usermeta
		FROM {$wpdb->prefix}users u;";

	$results = $wpdb->get_results($query, OBJECT);

	// $return = $wpdb->get_results($query, ARRAY_A);

	foreach ( $results as $result ) {
		$return[$result->user_email] = $result->usermeta;
	}

	return $return;
}
