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
	$modified_posts = array();
	//error_log(print_r($remote_posts, true));
	return array_diff_assoc($local_posts, $remote_posts);

	//get the list of net new posts - content we need to create.

	foreach ($local_posts as $post => $timestamp) {

			if(property_exists($remote_posts, $post)){
				error_log('found matching post');
				//found this post, compare wpcd_compare_timestamps
				if(wpcd_compare_timestamps($post, $remote_posts->$post)){

				}
			}else{
				//this post doesn't exist on the remote, add it to the modified array
				$modified_posts[] = $post;
			}


		}
}

function wpcd_compare_timestamps($a, $b){
	// returns true if $a is more recent than $b
	error_log('date diff a b '.date_diff($a, $b));

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



					$title = 'Preview Batch';
					$output .= '<form method="post">';
					$output .= wp_nonce_field( 'wpcd_send', 'wpcd_nonce' );
					$output .= '<a href="" class="button-secondary">Cancel</a>';
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
				}
			}else{
				//create a new batch
				// Create Batch - this form displays updated and new content and allows a user to select content to be deployed
				 //for each post type, including media
					 //generate lists of remote site and local site content
						// staging site query database and get a list of all timestamps and guid for published items
						// rest api endpoint on production returns timestamps and GUID for everything in the database
						// key value pairs with guid->timestamp
					 //compare time stamps - array diff on the two arrays
						 //add local items not found on the remote site to the list of modified content
						 //add local items with more recent time stamps to the list of modified content
					//Query staging site for updated content - titles, slug, guid, modified date, modified author
					 //create a form with inputs for each changed item to select if it gets deployed
					 //output the list items as checkbox fields organized by post type
							//include post title, slug, modifed by, local modification date, remote modification date (or new)
								//add links to the post edit screen for convenience.
							//input value = guid

				//get the posts from the two sites
				$remote_posts = wpcd_request_posts('remote');
				$local_posts = wpcd_request_posts('local');
				//compare timestamps
				$new_posts = wpcd_get_new_posts($local_posts, $remote_posts);
				$modified_posts = wpcd_get_modified_posts($local_posts, $remote_posts);
			//	error_log('new '.print_r($new_posts, true));
			//	error_log('modified '.print_r($modified_posts, true));

				$guids = "'".implode("','", array_keys($modified_posts))."'";
			//	error_log('guids: '.print_r(array_keys($modified_posts), true));
				global $wpdb;
				$results = array();
				$query = "SELECT GROUP_CONCAT(ID SEPARATOR ',') FROM {$wpdb->prefix}posts WHERE guid IN ($guids)";
				$results = $wpdb->get_row($query, ARRAY_N);
				$mod_posts_types = array();
				$form_field_output = '';

				//error_log('post id array '.print_r($results, true));
				if(!empty($results)){
					$modified_post_ids = $results[0];
					$args = array(
						'post__in' => explode(',', $modified_post_ids)
					);
					$mod_posts_query = new WP_Query($args);
					global $post;
					//error_log(print_r($mod_posts_query, true));
					if($mod_posts_query->have_posts()) :
						$show_form = true;
						while($mod_posts_query->have_posts()) :
							//build an array or posts and the post meta that we need to build the form.
							// - post type
							//
							$mod_posts_query->the_post();


							$mod_posts_types[] = $post->post_type;

							$form_field_output .= '<tr class="field-wrapper">
								<td class="checkbox-wrapper"><input type="checkbox" value="'.get_the_guid($post->ID).'"></input></td>
								<td>
								<h4 >'.get_the_title($post->ID).'</h4>
								<p>Last modified '.get_the_modified_date('l, M jS, Y', $post->ID).' at ' .get_the_modified_time('g:i A', $post->ID).' by '.get_the_author().' - <span><a href="'.get_the_permalink($post->ID).'" target="_blank">View Post</a> | <a href="'.get_edit_post_link($post->ID).'" target="_blank">Edit post</a></span></p>
								</td>
							</tr>';

						endwhile;
						error_log('post types: '.print_r($mod_posts_types, true));
					endif;



				}


				$title = 'Create Batch';
				$instructions = 'Choose the items you want to sync and then preview the batch.';
				$output .= '<form method="post">';
				$output.= '<h2>Modified Posts</h2>';
				$output.= '<table class="widefat striped">';
				$output .= $form_field_output;
				$output .= '</table>';
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
