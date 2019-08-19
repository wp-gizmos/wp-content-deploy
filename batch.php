<?php
/**
*	Admin Page for Creating/Processing Batch Jobs
*/
function wpcd_batch_page() {

  //are we looking at a POST? If so, which one (preview or send)
  //if we are not in a post, then this is the intial GET to display modified content (create batch)
  $form_markup = '';
  if(isset($_POST['wpcd_nonce'])){ //this is a post, it should either be wpcd_preview or wpcd_send

    if(wp_verify_nonce( $_POST['wpcd_nonce'], 'wpcd_preview' )) {
      //Preview/verify batch - this form displays content to be deployed and allows a user to cancel and go back or submit the form to deploy content
				//Display only selected content to be deployed in the batch with warning message.
      $form_title = 'Preview Batch';
      $form_markup .= '<form method="post">';
      $form_markup .= wp_nonce_field( 'wpcd_send', 'wpcd_nonce' );
      $form_markup .= '<a href="" class="button-secondary">Cancel</a>';
      $form_markup .= get_submit_button('Send Batch', 'primary');
      $form_markup .= '</form>';
    }
    else if(wp_verify_nonce( $_POST['wpcd_nonce'], 'wpcd_send' )){
      //Send batch - this page loops through the content to deploy and makes a series of post requests to the remote site.
        //after selecting items to deploy and previewing deployment
        //series of posts to remote site rest endpoint

        //Remote site responds with success or error for each post
        //status bar shows 1 of 10, 2 of 10, etc
      $form_title = 'Send Batch';
      $form_markup .= '<div>sending batch...</div>';
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
    $form_title = 'Create Batch';
    $form_markup .= '<form method="post">';
    $form_markup .= wp_nonce_field( 'wpcd_preview', 'wpcd_nonce' );
    $form_markup .= get_submit_button('Preview Batch', 'primary');
    $form_markup .= '</form>';
  }

  $wpcd_key = get_option('wpcd_key');
	echo '<div class="wrap">
  		<h1>'.$form_title.'</h1>';
    echo $form_markup;
	echo '</div>';
}
?>
