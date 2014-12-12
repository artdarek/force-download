<?php
    
    // include library
    require '../src/Artdarek/ForceDownload.php';

	// custom config
	$config = array(
		'allowed_extensions' => array(
		  // images
		      'gif' => 'image/gif',
		      'png' => 'image/png',
		      'jpg' => 'image/jpeg',
		      'jpeg'=> 'image/jpeg',
		)
	);

	// initialize download
	$force = new Artdarek\ForceDownload($config);
	$force->download();

?>