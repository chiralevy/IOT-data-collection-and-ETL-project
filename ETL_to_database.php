<?php
$ftp_server = "###";
$ftp_user_name = "chira";
$ftp_user_pass = "###";


#PDO: glue between database and code
$db = new PDO('pgsql:host=192.168.1.10;dbname=iot;', 'postgres', $ftp_user_pass);

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

//Returns a list of files in the given directory
$gpslist = ftp_nlist($conn_id, "/gps");
$camlist = ftp_nlist($conn_id, "/camera");
$audiolist = ftp_nlist($conn_id, "/audio");


foreach ($gpslist as $gpsfile) {

    //Don't want to use an intermediate file?  
    //Use 'php://output' as the filename and then capture the output using output buffering.

    ob_start();

    ftp_get ($conn_id, "php://output", $gpsfile, FTP_ASCII);

    //Return the contents of the output buffer
    $data = ob_get_contents();

    /*
    ob_end_flush sends the contents of the topmost output buffer (if any) and turn the output buffer off. 
    ob_end_clean (here) will erase the output buffer and turn off output buffering (doesn't send).
    */

    ob_end_clean();

    $gps_array= json_decode($data, true);  //Takes a JSON encoded string and converts it into a PHP variable. 

    if (array_key_exists('signal', $gps_array)) {

        continue;
    } 
    else {

        $latitude = $gps_array ["latitude"];
        $longitude = $gps_array ["longitude"];
        $elevation = $gps_array ["elevation"];
        $age = $gps_array ["age"];
        $speed = $gps_array ["speed"];
        $filename = substr($gpsfile, 5);

        echo $filename;
        echo "\n";

        if ($gps_array ["course"] == "") {
            $course = "null";
        }
        else {
            $course = $gps_array ["course"];
        }

        $trandate = substr($gpsfile, 14, -4);

        $gpssql  = "INSERT INTO tbgps (latitude, longitude, filename, trandate, elevation, course, age, speed) VALUES (" . $latitude . "," . $longitude . ",'" .  $filename  . "', '" .  $trandate  . "', " . $elevation  . "," . $course . "," . $age . "," . $speed . ")";

        $gpsrecords = $db->prepare($gpssql);

        $gpsrecords->execute();

        file_put_contents ("/home/chira/Downloads/$gpsfile", $data);
    }
}


function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}


foreach ($camlist as $camfile) {

    //download camfile form server to local
    //use base64 encode command to convert camfile string into base64 string

    //ftp_get returns TRUE on success hence "1" when "file" is echoed

    $localdir = "/home/chira/Downloads/$camfile";
    ftp_get ($conn_id, $localdir, $camfile, FTP_BINARY);

    //chunk split can be used to split a string into smaller chunks which is useful for e.g. converting base64_encode() output to match RFC 2045 semantics. It inserts end every chunklen characters.
    $b64 = chunk_split(base64_encode(file_get_contents("/home/chira/Downloads/$camfile")));

    //Returns an array of strings, each of which is a substring of string formed by splitting it on boundaries formed by the string delimiter.
    $namesplit = explode ("/", $camfile);

    //you cant use echo on an array, hence the error returned when echo $f
    $camname = $namesplit[2];
    //$fname = end($f);
            
    $b64dest = str_replace (".jpg", ".txt", "/home/chira/Downloads/camera/base64/$camname");

    //rename($b64, $b64dest);
    file_put_contents ($b64dest, $b64);

    $filename = substr($camfile, 8);

    $trandate = substr($camfile, 16, -5);

    $uuid = gen_uuid();

    $picsql = "INSERT INTO tbpictures (recid, filename, trandate) VALUES ('" . $uuid . "','" .  $filename  . "', '" .  $trandate  . "')";
    echo $picsql;
    echo ("\n");

    $camrecords = $db->prepare($picsql);

    $camrecords->execute();

    $filesql = "INSERT INTO tbfiles (recid, filename, base64) VALUES ('" . $uuid . "','" .  $filename  . "','" .  $b64  . "')";

    $filerecords = $db->prepare($filesql);

    $filerecords->execute();

}



foreach ($audiolist as $audiofile) {

    //download audiofile form server to local
    //use base64 encode command to convert audiofile string into base64 string

    //ftp_get returns TRUE on success hence "1" when "file" is echoed
    $localdir = "/home/chira/Downloads/$audiofile";
    ftp_get ($conn_id, $localdir, $audiofile, FTP_BINARY);

    //chunk split can be used to split a string into smaller chunks which is useful for e.g. converting base64_encode() output to match RFC 2045 semantics. It inserts end every chunklen characters.
    $b64 = chunk_split(base64_encode(file_get_contents("/home/chira/Downloads/$audiofile")));

    //Returns an array of strings, each of which is a substring of string formed by splitting it on boundaries formed by the string delimiter.
    $namesplit = explode ("/", $audiofile);

    //you can't use echo on an array, hence the error returned when echo $f
    $audioname = $namesplit[2];
    //$fname = end($f);
            
    $b64dest = str_replace (".wav", ".txt", "/home/chira/Downloads/audio/base64/$audioname");

    //rename($b64, $b64dest);
    file_put_contents ($b64dest, $b64);

    $filename = substr($audiofile, 7);

    $trandate = substr($audiofile, 16, -4);

    $uuid = gen_uuid();

    $audiosql = "INSERT INTO tbaudio (recid, filename, trandate) VALUES ('" . $uuid . "','" .  $filename  . "', '" .  $trandate  . "')";
    echo $audiosql;
    echo ("\n");

    $audiorecords = $db->prepare($audiosql);

    $audiorecords->execute();

    $filesql = "INSERT INTO tbfiles (recid, filename, base64) VALUES ('" . $uuid . "','" .  $filename  . "','" .  $b64  . "')";

    $filerecords = $db->prepare($filesql);

    $filerecords->execute();

}
// close the FTP stream 
ftp_close($conn_id); 

?>