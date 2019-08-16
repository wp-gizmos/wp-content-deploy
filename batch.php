<?php
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
			 //for each post type, including media
				 //generate lists of remote site and local site content
				 //compare time stamps
				 //add local items not found on the remote site to the list of modified content
				 //add local items with more recent time stamps to the list of modified content
				 //output the list items as checkbox fields organized by post type
				 		//include post title, local modification date, remote modification date (or new)
			//Preview
				//Display only selected content to be deployed in the batch with warning message.

			//Send batch
				//after selecting items to deploy and previewing deployment
				//series of posts to remote site rest endpoint
				//Remote site responds with success or error for each post

				//what about uploads? Do sites get set up with a shared uploads directory?
		}

	echo '</div>';
}
?>
