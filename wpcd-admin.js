/**
*	Randomly generate a string and add it as the deployment key
*/
document.addEventListener('click', function (event) {
	if (!event.target.matches('#wpcd-cd-keygen')) return;
	event.preventDefault();

	const wpcdKey = document.querySelector('#wpcd_key');
	wpcdKey.value = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);

}, false);

/**
*	Toggle Visibility of the Key Field
*/
document.addEventListener('click', function (event) {
	if (!event.target.matches('#wpcd-key-toggle')) return;
	event.preventDefault();

	const wpcdKey = document.querySelector('#wpcd_key');
	wpcdKey.type = (wpcdKey.type == 'password' ? 'text' : 'password');

}, false);


/**
* Loop through posts and send their guids over to the remote site
**/
jQuery('document').ready(function(){
	if( jQuery('#wpcd-post-send-list').length > 0 ){
		posts = jQuery('#wpcd-post-send-list li');
		jQuery.each(posts, function(i){
			var post_guid = jQuery(posts[i]).find('input').attr('value');
			//change the li class to 'sending'
			//make rest ajax request here to the remote site for each post guid
				//callback for the response should change the li class to 'success' or 'error'
				//callback should also update the status bar

		});
	}
});
