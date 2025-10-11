#!/usr/bin/php
<?php
ini_set("bug_compat_42" , "off"); ini_set("session.bug_compat_warn" , "off"); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.domains.diclaimers.inc');
include_once(dirname(__FILE__).'/ressources/class.mail.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.smtp.sockets.inc');
// to see : http://php-dkim.cvs.sourceforge.net/viewvc/php-dkim/php-dkim/
define( 'EX_TEMPFAIL', 75 );
define( 'EX_UNAVAILABLE', 69 );
define( RM_STATE_READING_HEADER, 1 );
define( RM_STATE_READING_FROM,   2 );
define( RM_STATE_READING_SUBJECT,3 );
define( RM_STATE_READING_SENDER, 4 );
define( RM_STATE_READING_BODY,   5 );
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;echo "verbose=true;\n";}
$GLOBALS["VERBOSE"]=true;
if($GLOBALS["VERBOSE"]){events("receive: " . implode(" ",$argv),"main",__LINE__);}
$options = parse_args( array( 's', 'r', 'c', 'h', 'u','i','z' ), $_SERVER['argv']); //getopt("s:r:c:h:u:");
$Masterdirectory="/var/spool/artica-adv";
if(QueueDirectoryIsMounted()){$Masterdirectory="/var/spool/artica-advmem";}

if (!array_key_exists('r', $options) || !array_key_exists('s', $options)) {
    fwrite(STDOUT, "Usage is $argv[0] -s sender@domain -r recip@domain\n");
    exit(EX_TEMPFAIL);
}

$GLOBALS_ARRAY["sender"]= strtolower($options['s']);
$GLOBALS_ARRAY["recipients"] = $options['r'];
$GLOBALS_ARRAY["original_recipient"]=$options['r'];
$GLOBALS_ARRAY["POSTFIX_INSTANCE"]=$options['i'];
$client_address = $options['c'];
$smtp_final_sender = strtolower($options['h']);
$sasl_username = strtolower($options['u']);

events("starting up, [{$GLOBALS_ARRAY["POSTFIX_INSTANCE"]}] sender={$GLOBALS_ARRAY["sender"]}, recipient={$GLOBALS_ARRAY["recipients"]}, client_address=$client_address", "main",__LINE__);

$tb=explode("@", $GLOBALS_ARRAY["recipients"]);
$TargetDomain=$tb[1];
$GLOBALS_ARRAY["rcpt_domain"]=$TargetDomain;
$directory="$Masterdirectory/{$GLOBALS_ARRAY["POSTFIX_INSTANCE"]}/$TargetDomain";
@mkdir($directory,0755,true);

	$GLOBALS_ARRAY["recipients"]=trim(strtolower($GLOBALS_ARRAY["recipients"]));
	$tmpfname = my_tempnam('IN.' ,".msg", $directory);
	events("starting up, [{$GLOBALS_ARRAY["POSTFIX_INSTANCE"]}] $tmpfname -> sender={$GLOBALS_ARRAY["sender"]}, recipient={$GLOBALS_ARRAY["recipients"]}, client_address=$client_address", "main",__LINE__);
	$tmpf = @fopen($tmpfname, "w");
	if( !$tmpf ) {writelogs("Error: Could not open $tmpfname for writing: ".php_error(), "main",__FILE__,__LINE__);exit(EX_TEMPFAIL);}


while (!feof(STDIN)) {
  $buffer = fread( STDIN, 8192 );
  if( fwrite($tmpf, $buffer) === false ) {exit(EX_TEMPFAIL);}
}
$GLOBALS_ARRAY["mail_data"]=$tmpfname;
$GLOBALS_ARRAY["timestamp"]=time();
$tpfnameRout=my_tempnam('IN.' ,".routing", $directory);
@file_put_contents($tpfnameRout, serialize($GLOBALS_ARRAY));
@fclose($tmpf);

$Timer=file_time_min("$Masterdirectory/chockTime.time");
if($Timer>3){
	events("-> chockSender()","MAIN",__LINE__);
	chockSender();
	@unlink("$Masterdirectory/chockTime.time");
	@file_put_contents("$Masterdirectory/chockTime.time", time());
}

function events($text,$function,$line=0){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-adv/mail.log";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$text="[$pid] $date $function:: $text (L.$line)\n";
		if($GLOBALS["VERBOSE"]){echo $text;}
		@fwrite($f, $text);
		@fclose($f);	
		}

function parse_args( $opts, $args ){
  $ret = array();
  for( $i = 0; $i < count($args); ++$i ) {
    $arg = $args[$i];
    if( $arg[0] == '-' ) {
      if( in_array( $arg[1], $opts ) ) {
	$val = array();
	$i++;
	while( $i < count($args) && $args[$i][0] != '-' ) {
	  $val[] = $args[$i];
	  $i++;
	}
	$i--;
	if( array_key_exists($arg[1],$ret) && is_array( $ret[$arg[1]] ) ) $ret[$arg[1]] = array_merge((array)$ret[$arg[1]] ,(array)$val);
	else if( count($val) == 1 ) $ret[$arg[1]] = $val[0];
	else $ret[$arg[1]] = $val;
      }
    }
  }
  return $ret;
}

function my_tempnam($prefix = null, $suffix = null, $dir = null){
	$prefix = trim($prefix);
    $suffix = trim($suffix);
    $dir = trim($dir);

   
    $fn_chars = array_flip(array_diff(array_merge(range(50,57), range(65,90), range(97,122), array(95,45)), array(73,79,108)));
    for($fn = rtrim($dir, '/') . '/' . $prefix, $loop = 0, $x = 0; $x++ < 20; $fn .= chr(array_rand($fn_chars)));
    while (file_exists($fn.$suffix)){
        $fn .= chr(array_rand($fn_chars));
        $loop++ > 10 and exit(EX_TEMPFAIL);
        
    }

    $fn = $fn.$suffix;
    return $fn;
}

function file_time_min($path){
		if(!is_dir($path)){if(!is_file($path)){return 100000;}}
	 		$last_modified = filemtime($path);
	 		$data1 = $last_modified;
			$data2 = time();
			$difference = ($data2 - $data1); 	 
			return round($difference/60);	 
		}

function chockSender(){
	$fp = stream_framework("/postfix.php?smtp-adv-start=yes");
	
	
}
function QueueDirectoryIsMounted(){
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	foreach ( $f as $index=>$line ){if(preg_match("#^tmpfs.+?artica-advmem tmpfs#", $line)){return true;}
	}return false;
	
}
function stream_framework($uri){

	$fp = stream_socket_client("unix:///usr/share/artica-postfix/ressources/web/framework.sock:80", $errno, $errstr, 10);
	if (!$fp){
		
		if(function_exists("writelogs")){writelogs("ERROR: unable to open remote file http://127.0.0.1:47980/$uri",__CLASS__ . "=>" . __FUNCTION__,__FILE__);}
		return false;
	}
	
	$header[]="GET /$uri HTTP/1.1";
	$header[]="User-Agent: Artica Framework";
	$header[]="Host: 127.0.0.1";
	$header[]="Accept: */*";

	fwrite($fp, @implode("\r\n",$header)."\r\n\r\n");
	$response =stream_get_contents($fp);

/*
 * 
 * $info = stream_get_meta_data($fp);
 * print_r($info);
 (
 		[stream_type] => unix_socket
 		[mode] => r+
 		[unread_bytes] => 0
 		[seekable] =>
 		[timed_out] =>
 		[blocked] => 1
 		[eof] => 1
 )
*/
	@fclose($fp);
	list($response,$datas) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
	$response = preg_split("/\r\n|\n|\r/", $response);
	list($protocol, $code, $status_message) = explode(' ', trim(array_shift($response)), 3);
	$headers = array();

	// Parse the response headers.
	while ($line = trim(array_shift($response))) {
		list($name, $value) = explode(':', $line, 2);
		$name = strtolower($name);
		if (isset($headers[$name]) && $name == 'set-cookie') {
			// RFC 2109: the Set-Cookie response header comprises the token Set-
			// Cookie:, followed by a comma-separated list of one or more cookies.
			$headers[$name] .= ',' . trim($value);
		}
		else {
			$headers[$name] = trim($value);
		}
	}

$responses = array(100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found',303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 400 => 'Bad Request',401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed',413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported', );
if (!isset($responses[$code])) {
	$code = floor($code / 100) * 100;
}

switch ($code) {
	case 200: // OK
	case 304: // Not modified
		break;
	case 301: // Moved permanently
	case 302: // Moved temporarily
	case 307: // Moved temporarily
		break;
	default:
		if(function_exists("writelogs")){writelogs("Fatal ERROR $code $uri $status_message",__CLASS__ . "=>" . __FUNCTION__,__FILE__);}
		return false;
}

return true;

}