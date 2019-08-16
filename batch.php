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

      //Preview/verify batch - this form displays content to be deployed and allows a user to cancel and go back or submit the form to deploy content
				//Display only selected content to be deployed in the batch with warning message.

			//Send batch - this page loops through the content to deploy and makes a series of post requests to the remote site.
				//after selecting items to deploy and previewing deployment
				//series of posts to remote site rest endpoint

				//Remote site responds with success or error for each post
        //status bar shows 1 of 10, 2 of 10, etc

				//what about uploads? Do sites get set up with a shared uploads directory?
		}

	echo '</div>';
}
?>
