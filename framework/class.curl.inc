<?php

class CurlHTTP{
	var $function_exists=false;
	
	function __construct(){
		if(!function_exists("curl_init")){
			error_log("[{$_SESSION["uid"]}]::unable use curl_init() in ".__FILE__." line ".__LINE__);
			return null;
		}
		
		$this->function_exists=true;
		    
	}
	
	function GetFile($filename,$uri){
		$out = fopen($filename, 'wb');
    	if ($out == FALSE){
      		error_log("[{$_SESSION["uid"]}]::unable to open file $filename in ".__FILE__." line ".__LINE__);
      		exit;
    	 }
   
    	$ch = curl_init();
        curl_setopt($ch, CURLOPT_FILE, $out);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_URL, $file);
               
    	curl_exec($ch);
    	error_log(curl_error( $ch)." in ".__FILE__." line ".__LINE__);
		curl_close($ch); 
		
		
	}
	
	
}

?>