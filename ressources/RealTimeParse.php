<?php

function ParseRealTimeStats($line):array{
    $argvs=explode(":::",$line);
    $icap_error="";
    $ipclass=new IP();
    if($ipclass->isValid($argvs[3])){

        $ARRAY["ProxyName"]=$argvs[0];
        $ARRAY["UA"]=$argvs[18];

        $ARRAY["proxy_server"]="";
        $ArrayENC["PROXY_CODE"] = $argvs[8];
        $ARRAY["xADDONS"]=$argvs[15];
        $ARRAY["XMAC"]=$argvs[2];
        $ARRAY["SOURCE_URL"]=$argvs[10];
        $ARRAY["XUSER"]=$argvs[4];
        $ARRAY["URL"]=$argvs[10];
        $ARRAY["DESTINATION"]=$argvs[20];
        $ARRAY["PROTO"]=$argvs[5];
        $ARRAY["size"]=$argvs[7];
        $ARRAY["ArrayENC"]=$ArrayENC;
        $ARRAY["zCode"]=$zCode = explode(":", $argvs[8]);
        $ARRAY["ip"]=$argvs[3];
        $ARRAY["durationunit"]="ms";
        $ARRAY["duration"]=$argvs[12];
        if(is_numeric($argvs[1])) {
            $ARRAY["date"] = date("Y-m-d H:i:s", $argvs[1]);
        }else{
            $ARRAY["date"]="";
        }
        $ARRAY["zCode0"]=intval($argvs[6]);
        $ARRAY["zCode1"]=$zCode[1];
        if($ipclass->isValid($argvs[9])){
            $ARRAY["ip"]=$argvs[9];
        }
        $TBERR=explode("|",$argvs[19]);
        $ERROR_EXT = trim($TBERR[0]);
        if (preg_match("#^30[0-9]+:http#", $ERROR_EXT)) {
            $ERROR_EXT =  $GLOBALS["redirect_text"];
        }
        $ERROR_EXT_STR = trim($TBERR[1]);
        if ($ERROR_EXT == "-") {
            $ERROR_EXT = "";
        }
        if ($ERROR_EXT_STR == "-") {
            $ERROR_EXT_STR = "";
        }
        if ($ERROR_EXT == "ERR_ICAP_FAILURE") {
            $icap_error = "<i class='fa-solid fa-plug-circle-exclamation' style='color:#D0080A'></i>";
        }
        $ARRAY["icap_error"]=$icap_error;
        $ARRAY["ERROR_EXT"]=$ERROR_EXT;
        $ARRAY["ERROR_EXT_STR"]=$ERROR_EXT_STR;

/*


Rec.Hierarchy = argvs[8]
		Rec.ForwardedFor = argvs[9]
		Rec.Sitename = strings.TrimSpace(strings.ToLower(argvs[10]))
		Rec.ClientFQDN = strings.TrimSpace(strings.ToLower(argvs[11]))

		Rec.MimeContent = argvs[13]
		Rec.SNI = argvs[14]
		Rec.Uniqueid = argvs[16]
		Rec.HOST_REQUESTED = argvs[17]
		Rec.UserAgent = strings.TrimSpace(argvs[18])
*/
    return $ARRAY;
    }
    return array();
}

function ParseRealHaLB($line):array{

    if(!preg_match("#^([A-Za-z]+)\s+(.+)\s+(.+?)\s+.*?squid-[0-9]+:(.+)#",$line,$re)){
        return array(false,array());
    }
    $ProxyName = $re[1] . "/";


}

function ParseRealTime($line):array{

    list($isFix,$Array)=ParseRealHaLB($line);
    if($isFix){
        return $Array;
    }

    $UA="";
    $icap_error="";
    $ProxyName = "";
    $proxy_server="";
    $ERROR_EXT="";
    $ERROR_EXT_STR="";
    $IFACE_TEXT="";
    $PROTO="";
    $xADDONS="";
    $XMAC="";
    $XUSER="";
    $DESTINATION="";
    $durationunit = "s";
        if ( $GLOBALS["Enablehacluster"] == 0) {
            if (preg_match("#^[A-Za-z]+\s+[0-9]+\s+[0-9:]+\s+(.+?)\s+squid\[[0-9]+\]:(.+)#", $line, $re)) {
                $ProxyName = $re[1] . "/";
                $line = trim($re[2]);
            }
            if(preg_match("#^[A-Za-z]+\s+[0-9:]+\s+[0-9:]+\s+(.+?)\s+\(squid.*?:\s+(.+)#",$line,$re)){
                $ProxyName = $re[1] . "/";
                $line = trim($re[2]);
            }
        }

    if (strpos($line,":::")>0){
        return ParseRealTimeStats($line);
    }

    if (preg_match("#iface=\"(.+?)\"#", $line, $re)) {
        $IFACE_TEXT=$re[1];
    }
    if (preg_match("#ua=\"(.+?)\"#", $line, $re)) {
        $UA = $re[1];
        $line = str_replace(" ua=\"$UA\"", "", $line);
        if ($UA == "-") {
            $UA = null;
        }
    }
    if (preg_match("#exterr=\"(.*?)\|(.*?)\"#", $line, $re)) {
        $ERROR_EXT = trim($re[1]);
        if (preg_match("#^30[0-9]+:http#", $ERROR_EXT)) {
            $ERROR_EXT =  $GLOBALS["redirect_text"];
        }
        $ERROR_EXT_STR = trim($re[2]);
        if ($ERROR_EXT == "-") {
            $ERROR_EXT = "";
        }
        if ($ERROR_EXT_STR == "-") {
            $ERROR_EXT_STR = "";
        }
        if ($ERROR_EXT == "ERR_ICAP_FAILURE") {
            $icap_error = "<i class='fa-solid fa-plug-circle-exclamation' style='color:#D0080A'></i>";
        }
    }

    $TR = preg_split("/[\s]+/", $line);
    if (count($TR) < 5) {
        return array();
    }
    if (strpos("  $TR[2]", "squid[") > 0) {
        $OLDTR = $TR;
        $TR = array();
        $proxy_server = "&nbsp;($OLDTR[1])";
        unset($OLDTR[0]);
        unset($OLDTR[1]);
        unset($OLDTR[2]);
        foreach ($OLDTR as $zwline) {
            $TR[] = $zwline;
        }

    }
    if ($GLOBALS["Enablehacluster"] == 0) {

        VERBOSE("DATE: <strong style='color:green'>$TR[0]</strong>", __LINE__);
        if (!is_numeric($TR[0])) {
            VERBOSE("Not a numeric [$TR[0]]", __LINE__);
            return array();
        }
        $date = date("Y-m-d H:i:s", (int) $TR[0]);

        $duration = $TR[1] / 1000;
        if ($duration < 60) {
            $duration = round($duration, 2);
        }
        if ($duration > 60) {
            $duration = round($duration / 60, 2);
            $durationunit = "mn";
        }
        $ip = $TR[2];
        $zCode = explode("/", $TR[3]);
        $size = $TR[4];
        $PROTO = $TR[5];
        $URL = $TR[6];
        $SOURCE_URL = $URL;
        VERBOSE("SOURCE_URL=[$SOURCE_URL]", __LINE__);
        $ArrayENC["CATEGORY"] = "";
        $ArrayENC["PROXY_CODE"] = $TR[3];
        $XUSER = $TR[7];
        $DESTINATION = $TR[8];


        $xADDONS = null;
        $XMAC = null;
        if (isset($TR[10])) {
            $XMAC = $TR[10];
        }
        if (isset($TR[11])) {
            $xADDONS = $TR[11];
        }

    } else {
        $URL="";
        $date="";
        if(is_numeric($TR[5])) {
            if(strpos($TR[5],".")>0) {
                $TTR=explode(".", $TR[5]);
                $TR[5]=$TTR[0];
            }
            $date = date("Y-m-d H:i:s", $TR[5]);
        }
        $proxy_server = "&nbsp;($TR[3])";

        $ddr=intval($TR[6]);
        $duration=0;


        if($ddr>0) {
            $duration = $ddr / 1000;
        }
        if($duration>0){
            if ($duration < 60) {
                $duration = round($duration, 2);
            }
            if ($duration > 60) {
                $duration = round($duration / 60, 2);
                $durationunit = "mn";
            }
        }

        $ip = $TR[7];
        $zCode = explode("/", $TR[8]);
        $ArrayENC["PROXY_CODE"] = $TR[8];
        $size = $TR[9];
        if(isset($TR[10])) {
            $PROTO = $TR[10];
        }
        if(isset($TR[13])) {
            $DESTINATION = $TR[13];
        }
        if(isset($TR[11])) {
            $URL = $TR[11];
        }
        if(isset($TR[12])) {
            $XUSER = $TR[12];
        }
        if(isset($TR[15])) {
            $XMAC = $TR[15];
        }
        if(isset($TR[16])) {
            $xADDONS = $TR[16];
        }
        $SOURCE_URL = $URL;
    }

    $ARRAY["ProxyName"]=$ProxyName;
    $ARRAY["UA"]=$UA;
    $ARRAY["icap_error"]=$icap_error;
    $ARRAY["ERROR_EXT"]=$ERROR_EXT;
    $ARRAY["ERROR_EXT_STR"]=$ERROR_EXT_STR;
    $ARRAY["proxy_server"]=$proxy_server;
    $ARRAY["xADDONS"]=$xADDONS;
    $ARRAY["XMAC"]=$XMAC;
    $ARRAY["SOURCE_URL"]=$SOURCE_URL;
    $ARRAY["XUSER"]=$XUSER;
    $ARRAY["URL"]=$URL;
    $ARRAY["DESTINATION"]=$DESTINATION;
    $ARRAY["PROTO"]=$PROTO;
    $ARRAY["size"]=$size;
    $ARRAY["ArrayENC"]=$ArrayENC;
    $ARRAY["zCode"]=$zCode;
    $ARRAY["ip"]=$ip;
    $ARRAY["durationunit"]=$durationunit;
    $ARRAY["duration"]=$duration;
    $ARRAY["date"]=$date;
    $ARRAY["zCode0"]=$zCode[0];
    $ARRAY["zCode1"]=$zCode[1];
    $ARRAY["IFACE"]=$IFACE_TEXT;
    return $ARRAY;
}
