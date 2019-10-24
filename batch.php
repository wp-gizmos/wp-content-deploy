<?php


/**
* Return the endpoint url for the endpoints
*/
function get_wpcd_endpoint($site, $type){
	if($site == 'remote'){
		$root_domain = wpcd_setting( 'wpcd_remote_url' );
	}else{
		$root_domain = get_site_url();
	}
	switch ($type) {
		case 'posts':
			$endpoint = $root_domain.'/wp-json/wp-content-deploy/v1/posts/';
			break;
		case 'users':
		$endpoint = $root_domain.'/wp-json/wp-content-deploy/v1/users/';

		default:
			$endpoint = $root_domain.'/wp-json/wp-content-deploy/v1/posts/';
			break;
	}
	return $endpoint;
}

/**
* function to return a list of posts from the endpoint
*/
function wpcd_request_posts($site){
	$endpoint = get_wpcd_endpoint($site, 'posts');
	$response = wp_remote_get( $endpoint );
	$posts = '';
	if(!empty($response['response']) && $response['response']['code'] == 200){
		$posts = json_decode($response['body'], true);
	}
	return $posts;
}


/**
* check for any net-new content, and return an array of new posts
*/
function wpcd_get_new_posts($local_posts, $remote_posts){
	return array_diff_key($local_posts, $remote_posts);
}
/**
* compare to post arrays and return posts that have been modified since
*/
function wpcd_get_modified_posts($local_posts, $remote_posts){

	return array_diff_assoc($local_posts, $remote_posts);


}

/**
* Post Details templates
*/
function wpcd_post_details($post_id){
	$markup =  '';
	$post_type_obj = get_post_type_object(get_post_type($post_id));
	if(isset($post_id)){
		$markup .= '<h4 >'.get_the_title($post_id).'</h4><p>Last modified '.get_the_modified_date('l, M jS, Y', $post_id).' at ' .get_the_modified_time('g:i A', $post_id).' by '.get_the_author().' - <span><a href="'.get_the_permalink($post_id).'" target="_blank">View '.$post_type_obj->labels->singular_name.'</a> | <a href="'.get_edit_post_link($post_id).'" target="_blank">Edit '.$post_type_obj->labels->singular_name.'</a></span></p>';
	}
	return $markup;
}

function wpcd_preview_details($post_id){
	$markup = '';
	$post_type_obj = get_post_type_object(get_post_type($post_id));
	if(isset($post_id)){
		$markup .= '<strong>'.get_the_title($post_id).'</strong> - <span><a href="'.get_the_permalink($post_id).'" target="_blank">View '.$post_type_obj->labels->singular_name.'</a> </span>';
	}
	return $markup;
}

/**
*	Admin Page for Creating/Processing Batch Jobs
*/

function wpcd_batch_page() {
	$wpcd_key = wpcd_setting('wpcd_key');

		if ( empty( $wpcd_key ) ) { // TODO: Validate Remote instead of just checking for presense of key
			$output = '<div class="notice notice-error"><p>You must set a deploy key on staging and production before you can create a batch.</p></div>';
		}
		else{

			//are we looking at a POST? If so, which one (preview or send)
			//if we are not in a post, then this is the intial GET to display modified content (create batch)
			$output = '';
			if( isset( $_POST['wpcd_nonce'] ) ){ //this is a post, it should either be wpcd_preview or wpcd_send

				if( wp_verify_nonce( $_POST['wpcd_nonce'], 'wpcd_preview' ) ) {
					//Preview/verify batch - this form displays content to be deployed and allows a user to cancel and go back or submit the form to deploy content
					//Display only selected content to be deployed in the batch with warning message.


					$posts_to_sync = array();
					foreach ($_POST as $name => $value) {
						if(strpos($name, 'postid_') !== false){
							$posts_to_sync[] = filter_input( INPUT_POST, $name, FILTER_SANITIZE_SPECIAL_CHARS );
						}
					}
					$title = 'Preview Batch';
					$output .= '<p>The following content will be deployed</p>';
					$current_post_type = '';
					$output .= '<form method="post"><table class="form-table">';

					$output .= wp_nonce_field( 'wpcd_send', 'wpcd_nonce' );
					foreach($posts_to_sync as $post_id){
						if($current_post_type !== ''){
							$output .= '</ul>';//close the previous table
						}
						if($current_post_type !== get_post_type($post_id)){
							$output.= '<h4>'.get_post_type_object(get_post_type($post_id))->labels->menu_name.'</h4>';
							$output .= '<ul class="">';
							$current_post_type = get_post_type($post_id);
						}
						$output.= '<li><input type="hidden" name="postid_'.$post_id.'" value ="'.get_the_guid($post_id).'"></input>';
						$output .= wpcd_preview_details($post_id);
						$output .= '</li>';
					}
					$output .= '</table>';
					$output .= '<a href="" class=""> << Cancel </a>';
					$output .= get_submit_button('Send Batch', 'primary');
					$output .= '</form>';
				}
				else if( wp_verify_nonce( $_POST['wpcd_nonce'], 'wpcd_send' ) ){
					//Send batch - this page loops through the content to deploy and makes a series of post requests to the remote site.
						//after selecting items to deploy and previewing deployment
						//series of posts to remote site rest endpoint

						//Remote site responds with success or error for each post
						//status bar shows 1 of 10, 2 of 10, etc
					$title = 'Send Batch';
					$output .= '<progress id="wpcd-batch-progress" class="widefat" max="100" value="0">0%</progress>';
					$output .= '<div>sending batch...</div>';
					//for each guid in the incoming post request do this:
					//make a hidden form for each guid
				}
			}else{
				//Create a new batch

				//get the posts from the two sites
				$remote_posts = wpcd_request_posts('remote');
				$local_posts = wpcd_request_posts('local');

				//compare timestamps
				$new_posts = wpcd_get_new_posts($local_posts, $remote_posts);
				$modified_posts = wpcd_get_modified_posts($local_posts, $remote_posts);
				//error_log('new '.print_r($new_posts, true));
				//error_log('modified '.print_r($modified_posts, true));

				$guids = "'".implode("','", array_keys($modified_posts))."'";

				//query the database for the updated posts and post meta
				global $wpdb;
				$results = array();
				//old way $query = "SELECT GROUP_CONCAT(ID SEPARATOR ',') FROM {$wpdb->prefix}posts WHERE guid IN ($guids)";
				$query = "SELECT ID, post_type, post_modified_gmt FROM wp_posts WHERE post_status='publish' and guid IN($guids) ORDER BY post_type, post_modified_gmt";
				//matt says
				$results = $wpdb->get_results($query, ARRAY_A);
				$mod_posts_types = array();
				$form_field_output = '';
				$current_post_type = '';

				if(!empty($results)){
					foreach ($results as $modified_post) {
						$post_id = $modified_post['ID'];
						$post_type_obj = get_post_type_object($modified_post['post_type']);
						if($current_post_type !== $modified_post['post_type']){

							if($current_post_type !== ''){
								$form_field_output .= '</table>';  //close the previous post type table
							}

							$form_field_output .= '<h3>'.$post_type_obj->labels->menu_name.'</h3>';
							$form_field_output .= '<table class="form-table widefat striped">';//open a new table
							$current_post_type = $modified_post['post_type'];
						}
						$form_field_output .= '<tr class="field-wrapper">
							<td class="checkbox-wrapper"><input name="postid_'.$post_id.'"type="checkbox" value="'.$post_id.'"></input></td>
							<td>
							'.wpcd_post_details($post_id).'
							</td>
						</tr>';
					}
					$form_field_output .= '</table>';
				}


				$title = 'Create Batch';
				$instructions = 'Choose the items you want to sync and then preview the batch.';
				$output .= '<form method="post">';
				$output .= $form_field_output;
				$output .= wp_nonce_field( 'wpcd_preview', 'wpcd_nonce' );
				$output .= get_submit_button('Preview Batch', 'primary');
				$output .= '</form>';
			}

	echo '<div class="wrap">
		<h1>'.$title.'</h1><p>'.$instructions.'</p>';
	echo $output;
	echo '</div>';
	}

}
?>
