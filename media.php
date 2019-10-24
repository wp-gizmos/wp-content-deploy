<?php
/**
* function to return a list of files from the endpoint
*/
function wpcd_request_files($site){
	$endpoint = get_wpcd_endpoint($site, 'files');
	$response = wp_remote_get( $endpoint );
	$files = '';
	if(!empty($response['response']) && $response['response']['code'] == 200){
		$files = json_decode($response['body'], true);
	}
	return $files;
}


function wpcd_media_page(){
	echo '<div class="wrap">
		<h2>Media Sync</h2>';
		$local_files = wpcd_request_files('local');
		$remote_files = wpcd_request_files('remote');
		$file_diff = array_diff($local_files, $remote_files);

		// temp
		echo 'File Diff: <pre>'.print_r($file_diff, true).'</pre>';

	echo '
	</div>';
}
