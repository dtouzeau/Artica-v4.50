<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
startx();
function build_progress($text,$pourc){
    $cachefile=PROGRESS_DIR."/curl.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function startx(){

    $unix=new unix();
    $CONF=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("request_simulation"));
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $url=$CONF["URL"];
    build_progress("{starting}",20);
    $FILE_TEMP=$unix->FILE_TEMP();
    if($SQUIDEnable==0){$CONF["LOCAL_PROXY"]=0;}

    $cmds[]=$unix->find_program("curl");
    $cmds[]="--include";
    $cmds[]="--ipv4";
    $cmds[]="--location";
    $cmds[]="--verbose --show-error";

    if($CONF["INTERFACE"]<>null){
        $ipaddr=$unix->InterfaceToIPv4($CONF["INTERFACE"]);
        if($ipaddr<>null){
            $cmds[]="--interface \"$ipaddr\"";
        }

    }

    if($CONF["REFERER"]<>null) {
        $cmds[]="--referer \"{$CONF["REFERER"]}\"";
    }
    if($CONF["USERAGENT"]<>null){
        $cmds[]="--header \"User-Agent: {$CONF["USERAGENT"]}\"";
    }
    if($CONF["ACCEPTLANG"]<>null){
        $cmds[]="--header \"Accept-Language: {$CONF["ACCEPTLANG"]}\"";
    }
    if($CONF["ACCEPTENCODING"]<>null){
        $cmds[]="--header \"Accept-Encoding: {$CONF["ACCEPTENCODING"]}\"";
    }
    if($CONF["CONNECTION"]<>null){
        $cmds[]="--header \"Connection: {$CONF["CONNECTION"]}\"";
    }
    if($CONF["XFORWARDEDFOR"]<>null){
        $cmds[]="--header \"X-Forwarded-For: {$CONF["XFORWARDEDFOR"]}\"";
    }

    if(preg_match("#https:#",$url)){
        $cmds[]="--insecure";
    }
    $USEPXY=false;




    if($CONF["LOCAL_PROXY"]==1){
        $USEPXY=true;
        $SquidMgrListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
        if($SquidMgrListenPort>0){
            echo "Using Proxy 127.0.0.1:$SquidMgrListenPort\n";
        }
        $cmds[]="--proxy http://127.0.0.1:$SquidMgrListenPort";

    }
    if(!$USEPXY) {
        if ($CONF["REMOTE_PROXY"] <> null) {
            if (intval($CONF["REMOTE_PROXY_PORT"]) > 0) {
                $USEPXY = true;
                echo "Using Proxy {$CONF["REMOTE_PROXY"]}:{$CONF["REMOTE_PROXY_PORT"]}\n";
                $cmds[] = "--proxy http://{$CONF["REMOTE_PROXY"]}:{$CONF["REMOTE_PROXY_PORT"]}";
            }

        }
    }



    if(!$USEPXY){
        $cmds[]="--noproxy \"*\"";
    }

    $cmds[]="--output ".$FILE_TEMP;
    $cmds[]="--url \"$url\"";

    $cmdline=@implode(" ",$cmds);
    echo $cmdline."\n\n\n";
    build_progress("{running}",50);
    exec($cmdline." 2>&1",$results);
    $RESULT_CODE=0;
    $VIA_PROXY=true;
    $CONCLUSION=array();
    $ll=array();
    foreach ($results as $line){

        if(preg_match("#\* Expire in [0-9]+ ms for [0-9]+#",$line)){continue;}

        if(preg_match("#\(([0-9]+)\)\s+Could not(.+?)#",$line,$re)){
            $RESULT_CODE=$re[1];
            if(trim($re[2])<>null) {
                $RESULT_TEXT = "Could not " . $re[2];
                $CONCLUSION[] = "Network Error code $RESULT_CODE $RESULT_TEXT\n";
            }
        }

        $ll[]="Output: $line";
        if(preg_match("#< HTTP/1.[0-9]+\s+([0-9]+)\s+(.+)#",$line,$re)){
            $RESULT_CODE=$re[1];
            $RESULT_TEXT=$re[2];
            $CONCLUSION[]="HTTP Error code $RESULT_CODE $RESULT_TEXT";
        }
        if(preg_match("#X-Squid-Error:\s+(.+?)\s+([0-9]+)#",$line,$re)){
            $VIA_PROXY=false;
            $VIA_PROXY_ERROR=$re[1]." ".$re[2];
            $CONCLUSION[]="Proxy error ".$re[1]." ".$re[2];
        }
        if(preg_match("Via:\s+[0-9\.]+\s+(.+?)#",$line,$re)){
            $CONCLUSION[]="Using Proxy {$re[1]}";
        }
        if(preg_match("X-Cache:\s+(.+?)\s+#",$line,$re)){
            $CONCLUSION[]="Cache status $re[1]";
        }

    }

    $size=@filesize($FILE_TEMP);
    if($size>1024){$size=FormatBytes($size/1024);}
    echo "Size of Content: $size\n";
    echo @implode("\n",$CONCLUSION);
    echo "----------------------------\n";
    echo @implode("\n",$ll);



    if($RESULT_CODE>0){
        if($RESULT_CODE<>200){
            build_progress("{failed} Code:$RESULT_CODE",110);
            return;
        }
    }

    if(!$VIA_PROXY){
        build_progress("{failed} $VIA_PROXY_ERROR",110);
        return;
    }


    build_progress("{success}",100);

}