<?php
/**
*	Add wp-json endpoints
*/
add_action( 'rest_api_init', function () {
	register_rest_route( 'wp-content-deploy/v1', '/posts/',
		array(
		'methods' => 'GET',
		'callback' => 'wpcd_post_list',
	) );
	register_rest_route( 'wp-content-deploy/v1', '/posts/(?<base64_guid>.*)',
		array(
		'methods' => 'GET, POST',
		'callback' => 'wpcd_post_data',
		'args' => array(
	      'base64_guid' => array('type' => 'string'),
	    ),
	) );
	register_rest_route( 'wp-content-deploy/v1', '/users/',
		array(
		'methods' => 'GET',
		'callback' => 'wpcd_user_list',
	) );
	register_rest_route( 'wp-content-deploy/v1', '/files/',
		array(
		'methods' => 'GET',
		'callback' => 'wpcd_file_list',
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
*	Returns the data required to recreate a post
*
*	@param $base64_guid string The guid of the post to retrieve, base64 encoded
*	@return $post_data array
*/
function wpcd_post_data( WP_REST_Request $request ) {
	global $wpdb;
	$guid = trim(base64_decode($request['base64_guid']));
	$query = $wpdb->prepare("
		SELECT ID FROM {$wpdb->prefix}posts
		WHERE `guid` = %s
		AND `post_status`='publish'
		LIMIT 1",
		$guid
	);
	$post_id = $wpdb->get_var($query);
	$post_object = get_post($post_id);
	$post_meta = get_post_meta($post_id);
	$post_author = get_userdata($post_object->post_author);
	$object_taxonomies = get_object_taxonomies($post_object);
	foreach($object_taxonomies as $taxonomy){
		$terms = wp_get_post_terms($post_id, $taxonomy);
		foreach($terms as $term){
			$parent = get_term($term->parent);
			$term_response[] = array(
				'name' => $term->name,
				'slug' => $term->slug,
				'parent' => $parent->slug,
				'link' => get_term_link($term),
			);
		}
		$post_taxonomies[$taxonomy] = $term_response;
		unset($term_response);
	}

	$response = array(
		'post_object' => $post_object,
		'post_meta' => $post_meta,
		'post_author' => array(
			'user_login' => $post_author->data->user_login,
			'user_email' => $post_author->data->user_email,
			'display_name' => $post_author->data->display_name,
		),
		'post_taxonomies' => $post_taxonomies,
	);

	return rest_ensure_response($response);
}


/**
*	Returns list of users and usermeta
*
*	@return $return array
*/
function wpcd_user_list() {
	global $wpdb;
	$return = array();

	$query = "SELECT u.*,
			(
			SELECT GROUP_CONCAT( CONCAT_WS(':', m.meta_key, m.meta_value) SEPARATOR ',' )
			FROM {$wpdb->prefix}usermeta m WHERE m.user_id = u.ID ORDER BY m.meta_key
			)
			AS usermeta
		FROM {$wpdb->prefix}users u;";

	$results = $wpdb->get_results($query, OBJECT);

	foreach ( $results as $result ) {
		$return[base64_encode($result->user_email)] = base64_encode($result->usermeta);
	}

	return $return;
}


/**
*	Returns list of files in media library folders
*
*	@return $return array
*/
function wpcd_file_list() {
	$return = array();
	$upload_info = wp_get_upload_dir();

	if( is_dir($upload_info['basedir']) ) {

		$directoryIterator = new RecursiveDirectoryIterator( $upload_info['basedir'] );
		$files = new RecursiveIteratorIterator( $directoryIterator );
		foreach( $files as $file ){
			$path = str_replace($upload_info['basedir'], '', $file->getPathname());
			$url = $upload_info['baseurl'].$path;

			if( !preg_match('/^\./', $file->getFilename()) ){
				$return[$path] = $url;
			}
		}
	}

	return $return;
}
