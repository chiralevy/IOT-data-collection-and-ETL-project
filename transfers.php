<?php
	$FILES1= "/tmp/mounts/SD-P1/gps/*.txt";
	$FILES2= "/tmp/mounts/SD-P1/camera/*.jpg";
	$FILES3= "/tmp/mounts/SD-P1/audio/*.wav";

    //ftp server and password not shown for confidentiality
	$ftp_server = "###";
	$ftp_user_name = "chira";
	$ftp_user_pass = "###";
	
	//for testing without loop
	$destination_file = "pkglist.txt";
	$source_file = "pkglist.txt";

	// set up basic connection
	$conn_id = ftp_connect($ftp_server, 21, 90); 

	// login with username and password
	$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
	ftp_pasv($conn_id, true) or die("Cannot switch to passive mode"); 

	// check connection
	if ((!$conn_id) || (!$login_result)) { 
    		echo "FTP connection has failed!\n";
    		echo "Attempted to connect to $ftp_server for user $ftp_user_name\n"; 
    		exit; 
	} else {
    		echo "Connected to $ftp_server, for user $ftp_user_name\n";
	}

	//just filename
	$gpsfiles = glob("$FILES1");
	$camfiles = glob("$FILES2");
	$audiofiles = glob("$FILES3");


	foreach($gpsfiles as $gpsfile) {
		echo "$gpsfile\n";

		// upload the file in ASCII mode
		//$upload = ftp_put($conn_id, "/gps", $file, FTP_ASCII); 

		// upload the file in BINARY mode & test file names instead of loop	
		//$upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY); 

		// check upload status
		$f = explode ("/", $gpsfile);
		$fname = end($f);
		
		$upload = ftp_put($conn_id, "/gps/$fname", $gpsfile, FTP_ASCII);
	
		if (!$upload) { 
    			echo "FTP upload has failed!\n";
		} else {
    			//echo "Uploaded $source_file to $ftp_server as $destination_file\n";
			echo "Uploaded $file to $ftp_server as $gpsfile\n";
			$replace = str_replace ("*", "sent/$fname", $FILES1);
		//moves file
			rename($gpsfile, $replace);
		}
		echo "\n";
	}


		foreach($camfiles as $camfile) {
		echo "$camfile\n";

		// check upload status
		$f = explode ("/", $camfile);
		$fname = end($f);
		
		$upload = ftp_put($conn_id, "/camera/$fname", $camfile, FTP_ASCII);
	
		if (!$upload) { 
    			echo "FTP upload has failed!\n";
		} else {
    			//echo "Uploaded $source_file to $ftp_server as $destination_file\n";
			echo "Uploaded $camfile to $ftp_server as $camfile\n";
			$replace = str_replace ("*", "sent/$fname", $FILES2);
			rename($camfile, $replace);
		}
		echo "\n";
	}

	
	foreach($audiofiles as $audiofile) {
		echo "$audiofile\n";

		// check upload status
		$f = explode ("/", $audiofile);
		$fname = end($f);
		
		$upload = ftp_put($conn_id, "/audio/$fname", $audiofile, FTP_ASCII);
	
		if (!$upload) { 
    			echo "FTP upload has failed!\n";
		} else {
    			//echo "Uploaded $source_file to $ftp_server as $destination_file\n";
			echo "Uploaded $audiofile to $ftp_server as $audiofile\n";
			$replace = str_replace ("*", "sent/$fname", $FILES3);
			rename($audiofile, $replace);
		}
		echo "\n";

	}

	// close the FTP stream 
	ftp_close($conn_id); 
?>
