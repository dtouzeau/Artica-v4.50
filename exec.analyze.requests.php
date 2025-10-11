<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');


xstart();


function buildcmdline($url,$array,$tmpfile,$redirect=false){
	
	$unix=new unix();
	$curl=$unix->find_program("curl");
	if(!is_numeric($array["website-analysis-timeout"])){$array["website-analysis-timeout"]=2;}
	if($array["website-analysis-timeout"]==0){$array["website-analysis-timeout"]=2;}
	if($array["website-analysis-timeout"]>30){$array["website-analysis-timeout"]=15;}
	

	$CMDS[]=$curl;
	$CMDS[]="--ipv4";
	$CMDS[]="--output $tmpfile";
	$CMDS[]="--compressed";
	$CMDS[]="--max-filesize 524288";
	$CMDS[]="--show-error --trace-time --trace-ascii /usr/share/artica-postfix/ressources/logs/web/curl.trace";
	$CMDS[]="--connect-timeout {$array["website-analysis-timeout"]}";
	if($redirect){$CMDS[]="--location";}
	$urls=$url;
	$parse_url=parse_url($url);
	$scheme=$parse_url["scheme"];
	$host=$parse_url["host"];
	

	if($scheme=="https"){$CMDS[]="--insecure";}
	
	if($array["website-analysis-address"]<>null){
		$CMDS[]="--interface {$array["website-analysis-address"]}";
	}
	
	if($array["website-analysis-proxy"]<>null){
		$CMDS[]="--proxy {$array["website-analysis-proxy"]}";
	
		if($array["website-analysis-username"]<>null){
			$CMDS[]="--proxy-anyauth";
			$CMDS[]="--proxy-user {$array["website-analysis-username"]}:".$unix->shellEscapeChars($array["website-analysis-password"]);
		}
	}
	
	$CMDS[]="\"$url\"";
	
	return @implode(" ", $CMDS);
}

function parse_errors(){
	$result=true;
	$error=null;
	$f=explode("\n",@file_get_contents(PROGRESS_DIR."/curl.trace"));
	foreach ($f as $line){
		if(!preg_match("#^0000:\s+(.+)#", $line,$re)){continue;}
		$FF[]=$re[1];
		//echo "$re[1]\n";
		
		if(preg_match("#Location:\s+(.+?)ufdbgu#", $line,$re)){
			$result=false;
			echo "*** Redirect: {$re[1]} ****\n";
			$error="{redirected}: {$re[1]}";
			break;
		}

		if(preg_match("#HTTP\/.*?404 Not Found#",  $line,$re)){
			$error="404 Not Found";
			$result=false;
			break;
		}
		
		if(preg_match("#Connection: close#", $line,$re)){
			
			//break;
		}
		
	}

	
	$FINAL["ERROR"]=$error;
	$FINAL["RESULT"]=$result;
	$FINAL["DATA"]=@implode("\n", $FF);
	return $FINAL;
	//print_r($FINAL);
}

function xstart(){
	$unix=new unix();
	
	
	$tmpfile=$unix->FILE_TEMP();
	
	
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebSiteAnalysis")));
	if(!isset($array["website-analysis"])){$array["website-analysis"]="http://www.articatech.com";}
	build_progress("{website_analysis}:", 20);
	echo "URL: {$array["website-analysis"]}\n";
	build_progress(10, $array["website-analysis"]);
	
	$tempfile=$unix->FILE_TEMP();
	$cmdline=buildcmdline($array["website-analysis"],$array,$tmpfile,true);
	echo "$cmdline\n";
	system("$cmdline");
	$FINAL=parse_errors();

	if(!$FINAL["RESULT"]){
		echo "RESULT --> FAILED\n";
		$FINAL_ARRAY[$array["website-analysis"]]=$FINAL;
		@unlink(PROGRESS_DIR."/curl.trace");
		@unlink($tempfile);
		@file_put_contents(PROGRESS_DIR."/curl.results", serialize($FINAL_ARRAY));
		build_progress("{website_analysis} {done}", 100);
		exit();
	}
	$parse_url=parse_url($array["website-analysis"]);
	$scheme=$parse_url["scheme"];
	$host=$parse_url["host"];
	$prefix="{$scheme}://$host";
	$FINAL_ARRAY[$array["website-analysis"]]=$FINAL;
	$ARRAY=parseLinks($tmpfile,$prefix);
	$ARRAY_COUNT=count($ARRAY);
	$c=21;
	build_progress("{website_analysis}:", 21);
	while (list ($url, $ligne) = each ($ARRAY) ){
		if(isset($FINAL_ARRAY[$url])){continue;}
		if(preg_match("#\/squid-internal-static\/#", $url)){continue;}
		$prc=$c/$ARRAY_COUNT;
		$prc=$prc*100;
		$prc=round($prc);
		$c++;
		if($prc>20){
			if($prc<99){build_progress("$url", $prc);}
		}

		@unlink(PROGRESS_DIR."/curl.trace");
		@unlink($tempfile);
		$cmdline=buildcmdline($url,$array,$tmpfile);
		system("$cmdline");
		$FINAL_ARRAY[$url]=parse_errors();
	}
	@file_put_contents(PROGRESS_DIR."/curl.results", serialize($FINAL_ARRAY));
	build_progress("{website_analysis} {done}", 100);

}

function parseLinks($tempfile,$prefix){
	$html = file_get_contents($tempfile);
	$ZLINKS=array();
	$doc = new DOMDocument();
	$doc->loadHTML($html); //helps if html is well formed and has proper use of html entities!
	
	
	$styles = $doc->getElementsByTagName('link');
	$links = $doc->getElementsByTagName('a');
	$scripts = $doc->getElementsByTagName('script');
	
	foreach($scripts as $node) {
	
		$link=trim($node->getAttribute('src'));
		if(!preg_match("#^http#", "$link")){
			if(substr($link, 0,1)<>"/"){$link="/$link";}
			$link="{$prefix}$link";}
			if($prefix==$link){continue;}
			$ZLINKS[$link]=$link;
	
	}
	foreach($links as $node) {
		$link=trim($node->getAttribute('href'));
		if(preg_match("#^mailto:#", $link)){continue;}
		if(!preg_match("#^http#", "$link")){
			if(substr($link, 0,1)<>"/"){$link="/$link";}
			$link="{$prefix}$link";}
			if($prefix==$link){continue;}
			$ZLINKS[$link]=$link;
	}
	foreach($styles as $node) {
		$link=trim($node->getAttribute('href'));
		if(!preg_match("#^http#", "$link")){
			if(substr($link, 0,1)<>"/"){$link="/$link";}
			$link="{$prefix}$link";}
			if($prefix==$link){continue;}
			$ZLINKS[$link]=$link;
	}
	
return $ZLINKS;
	
	
	
}


function build_progress($text,$pourc){
	echo $text."\n";
	$cachefile=PROGRESS_DIR."/squid.debug.website.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
}