<?
    function RADIUS_AUTHENTICATION($username,$password,$radiushost,$radiusport=1812,$sharedsecret,$suffix=null) {
	global $debug;

	if ($radiusport==0){
	    $radiusport=getservbyname("radius","udp");
	}

	$nasIP=explode(".","127.0.0.1");

	// 17 is UDP, formerly known as PROTO_UDP
	$sock=@socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	if ($sock==FALSE) {
		if(function_exists("WLOG")){
			if($GLOBALS["VERBOSE"]){echo "[RAD]: $radiushost:$radiusport ".socket_strerror(socket_last_error())." (".__LINE__.")\n";}
			WLOG("[RAD]: $radiushost:$radiusport ".socket_strerror(socket_last_error())."\n");
		}
	    
	    return 0;
	}
	$retval=socket_connect($sock,$radiushost,$radiusport);
	if ($retval==FALSE) {
		if(function_exists("WLOG")){
			WLOG("[RAD]: $radiushost:$radiusport ".socket_strerror(socket_last_error())."\n");
		}
	    return 0;
	}

	if (!preg_match("/@/",$username))
	    $username.=$suffix;

	if ($debug)
	    echo "<br>radius-port: $radiusport<br>radius-host: $radiushost<br>username: $username<br>suffix: $suffix<hr>\n";

	if($GLOBALS["VERBOSE"]){echo "[RAD]: calculate RA (".__LINE__.")\n";}
	$RA=pack("CCCCCCCCCCCCCCCC",				// auth code
	    1+mt_rand()%255, 1+mt_rand()%255, 1+mt_rand()%255, 1+mt_rand()%255,
	    1+mt_rand()%255, 1+mt_rand()%255, 1+mt_rand()%255, 1+mt_rand()%255,
	    1+mt_rand()%255, 1+mt_rand()%255, 1+mt_rand()%255, 1+mt_rand()%255,
	    1+mt_rand()%255, 1+mt_rand()%255, 1+mt_rand()%255, 1+mt_rand()%255);

	
	if($GLOBALS["VERBOSE"]){echo "[RAD]: Encrypt($password,$sharedsecret,$RA) (".__LINE__.")\n";}
	$encryptedpassword=Encrypt($password,$sharedsecret,$RA);

	$length=4+				// header
		16+				// auth code
		6+				// service type
		2+strlen($username)+		// username
		2+strlen($encryptedpassword)+	// userpassword
		6+				// nasIP
		6;				// nasPort

	$thisidentifier=mt_rand()%256;
	//          v   v v     v   v   v     v     v
	$data=pack("CCCCa*CCCCCCCCa*CCa*CCCCCCCCN",
	    1,$thisidentifier,$length/256,$length%256,		// header
	    $RA,						// authcode
	    6,6,0,0,0,1,					// service type
	    1,2+strlen($username),$username,			// username
	    2,2+strlen($encryptedpassword),$encryptedpassword,	// userpassword
	    4,6,$nasIP[0],$nasIP[1],$nasIP[2],$nasIP[3],	// nasIP
	    5,6,$_SERVER['SERVER_PORT']				// nasPort
	    );

	if($GLOBALS["VERBOSE"]){echo "[RAD]: Sending AUTH.... $length bytes (".__LINE__.")\n";}
	socket_write($sock,$data,$length);

	//
	// Wait at most five seconds for the answer. Thanks to
	// Michael Long <mlong@infoave.net> for his remark about this.
	//
	$read = array($sock);
	if($GLOBALS["VERBOSE"]){echo "[RAD]: Waiting answer... (".__LINE__.")\n";}
	$num_sockets = socket_select($read, $write = NULL, $except = NULL, 60);
	if ($num_sockets === FALSE) {
	    echo "socket_select() failed: " .
	    	socket_strerror(socket_last_error()) . "\n";
	    socket_close($sock);
	    return 0;
	} elseif ($num_sockets == 0) {
	    echo "No answer from radius server, aborting\n";
	    socket_close($sock);
	    return 0;
	}
	unset($read);

	$readdata=socket_read($sock,2);
	socket_close($sock);
	if ($readdata===FALSE) {
	    echo "socket_read() failed: " .
	    	socket_strerror(socket_last_error()) . "\n";
	    return 0;
	}
	
	if($GLOBALS["VERBOSE"]){echo "[RAD]: received:\n $readdata (".__LINE__.")\n";}
	
	if (ord(substr($readdata, 1, 1)) != $thisidentifier) {
		$r=ord(substr($readdata, 1, 1));
		if($GLOBALS["VERBOSE"]){echo "[RAD]: Wrong id received from radius server, aborting \"$r\" (".__LINE__.")\n";
		}
	    //echo "Wrong id received from radius server, aborting\n";
	    //return 0;
	    return 3; // FIXME this is awfull
	}

	if($GLOBALS["VERBOSE"]){echo "[RAD]: SEND -> \"".ord($readdata)."\" (".__LINE__.")\n";}
	
	return ord($readdata);
	// 2 -> Access-Accept
	// 3 -> Access-Reject
	// See RFC2138 for this.
    }

    function Encrypt($password,$key,$RA) {
	global $debug;

	$keyRA=$key.$RA;

	if ($debug)
	    echo "<br>key: $key<br>password: $password<hr>\n";

	$md5checksum=md5($keyRA);
	$output="";

	for ($i=0;$i<=15;$i++) {
	    if (2*$i>strlen($md5checksum)) $m=0; else $m=hexdec(substr($md5checksum,2*$i,2));
	    if ($i>strlen($keyRA)) $k=0; else $k=ord(substr($keyRA,$i,1));
	    if ($i>strlen($password)) $p=0; else $p=ord(substr($password,$i,1));
	    $c=$m^$p;
	    $output.=chr($c);
	}
	return $output;
    }
?>