<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

//if(!isset($_SERVER["HTTP_UUID"])){die();}



GET_SECURITY_CATEGORIES($_GET["url"]);

function Whitelist($url){

    $f[]="\.w3\.org";
    $f[]="\.(googleapis|googleusercontent)\.com\/";
    $f[]="\.(office\.com|microsoft\.com)\/";
    foreach ($f as $pattern){
        if(preg_match("#$pattern#i",$url)){
            return true;
        }

    }
    return false;

}

function GET_SECURITY_CATEGORIES($url){
    if(Whitelist($url)){
        $array["status"] = true;
        $array["message"]="Line:".__LINE__;
        $array["category"] = 9999;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        return;
    }

    $fam = new squid_familysite();
    $CATINT[92] = "category_malware";
    $CATINT[105] = "category_phishing";
    $memcached = new lib_memcached();
    $parsed = parse_url($url);
    $sitename = $parsed["host"];

    if (strpos($sitename, ":")) {
        $xtr = explode(":", $sitename);
        $sitename = $xtr[0];
    }

    $familysite = $fam->GetFamilySites($sitename);
    $zmd5_sitename=md5(strtolower($sitename));

    $urlmd5 = md5($url);
    $urlmd2 = md5($url."/");
    $category = $memcached->getKey("SAFE:$urlmd5");
    if ($memcached->MemCachedFound) {
        if($category>0) {
            $array["status"] = true;
            $array["message"] = "Line:" . __LINE__;
            $array["category"] = $category;
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 200);
            return;
        }

    }

    $q = new postgres_sql();
    $ligne = $q->mysqli_fetch_array("SELECT category FROM safebrowsing WHERE zmd5='$urlmd5'");
    if (intval($ligne["category"]) > 0) {
        $memcached->saveKey("SAFE:$urlmd5", $ligne["category"], 1800);
        $array["status"] = true;
        $array["message"]="Line:".__LINE__;
        $array["category"] = $ligne["category"];
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        return;
    }
    $ligne = $q->mysqli_fetch_array("SELECT category FROM safebrowsing WHERE zmd5='$urlmd2'");
    if (intval($ligne["category"]) > 0) {
        $memcached->saveKey("SAFE:$urlmd2", $ligne["category"], 1800);
        $array["status"] = true;
        $array["message"]="Line:".__LINE__;
        $array["category"] = $ligne["category"];
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        return;
    }



    $ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM malwares_domains WHERE zmd5='$zmd5_sitename'");
    if(strpos($ligne["zmd5"]>5)){
        $memcached->saveKey("SAFE:$urlmd5", 90, 1800);
        $q->QUERY_SQL("INSERT INTO safebrowsing (zdate,zmd5,category) VALUES (NOW(),'$urlmd5','$category')");
        $array["status"] = true;
        $array["message"]="Line:".__LINE__;
        $array["category"] = $category;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        return;
    }


    $category = GoogleCheckUrl($url);
    if ($category > 0) {
        $memcached->saveKey("SAFE:$urlmd5", $category, 1800);
        $q->QUERY_SQL("INSERT INTO safebrowsing (zdate,zmd5,category) VALUES (NOW(),'$urlmd5','$category')");
        $array["status"] = true;
        $array["message"]="Line:".__LINE__;
        $array["category"] = $category;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);

        if ($familysite == $sitename) {
            $q->QUERY_SQL("INSERT INTO {$CATINT[$category]} (sitename) VALUES ('$sitename')");
        }
        return;
    }

    $category=phishtank($url);
    if ($category > 0) {
        $memcached->saveKey("SAFE:$urlmd5", $category, 1800);
        $q->QUERY_SQL("INSERT INTO safebrowsing (zdate,zmd5,category) VALUES (NOW(),'$urlmd5','$category')");
        $array["status"] = true;
        $array["message"]="Line:".__LINE__;
        $array["category"] = $category;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);

        if ($familysite == $sitename) {
            $q->QUERY_SQL("INSERT INTO {$CATINT[105]} (sitename) VALUES ('$sitename')");
        }
        return;
    }

    $catz=new mysql_catz();
    $catz->OnlyQuery();
    $catz->LogTosyslog();
    $catz->OnlyLocal();
    $catz->SaveNotCategorizedToDatabase();
    $categories=$catz->GET_CATEGORIES($sitename);

    $WRONGCATZ[90]=true;
    $WRONGCATZ[109]=true;
    $WRONGCATZ[105]=true;
    $WRONGCATZ[135]=true;
    $WRONGCATZ[167]=true;
    $WRONGCATZ[195]=true;
    $WRONGCATZ[181]=true;
    $WRONGCATZ[49]=true;
    $WRONGCATZ[140]=true;

    if(isset($WRONGCATZ[$categories])){
        $memcached->saveKey("SAFE:$urlmd5", $categories, 1800);
        $q->QUERY_SQL("INSERT INTO safebrowsing (zdate,zmd5,category) VALUES (NOW(),'$urlmd5','$category')");
        $array["status"] = true;
        $array["message"]="Line:[".__LINE__."]: Artica Databases".@implode("\n",$GLOBALS["LOGS"]);
        $array["category"] = $categories;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        return;
    }

    $memcached->saveKey("SAFE:$urlmd5", 9999, 1800);
    $q->QUERY_SQL("INSERT INTO safebrowsing (zdate,zmd5,category) VALUES (NOW(),'$urlmd5','9999')");
    $array["status"] = true;
    $array["message"]="Line:".__LINE__;
    $array["category"] = 9999;
    $RestAPi = new RestAPi();
    $RestAPi->response(json_encode($array), 200);
}




function GoogleCheckUrl($url)
{

    $sock=new sockets();
    $API_KEY = $sock->GET_INFO("GoogleSafeAPIKey");
    $URL = "https://safebrowsing.googleapis.com/v4/threatMatches:find?key=$API_KEY";
    $ch = curl_init();

    $data="{
\"client\": {
  \"clientId\":      \"yourcompanyname\",
  \"clientVersion\": \"1.5.2\"
},
\"threatInfo\": {
  \"threatTypes\":      [\"MALWARE\", \"SOCIAL_ENGINEERING\"],
  \"platformTypes\":    [\"WINDOWS\"],
  \"threatEntryTypes\": [\"URL\"],
  \"threatEntries\": [
    {\"url\": \"$url\"}    
  ]
}
}";

    curl_setopt_array($ch, array(
        CURLOPT_URL => "$URL",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "$data",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
            "postman-token: b05b8d34-85f2-49cf-0f8e-03686a71e4e9"
        )
    ));

    $reply = curl_exec($ch);
    curl_close($ch);
    $main = json_decode($reply);
    $results=null;

    try {

        $results=$main->matches[0]->threatType;

    } catch (Exception $e) {

    }

    if($results=="SOCIAL_ENGINEERING"){return 105;}
    if($results=="MALWARE"){return 92;}
    return 0;
}

function phishtank($MAIN_URI){
    $sock=new sockets();
    $MimedefangPhishTankAPIKey=trim($sock->GET_INFO("MimedefangPhishingInitiativeAPIKey"));
    if($MimedefangPhishTankAPIKey==null){
        $GLOBALS["LOGS"][]="Phishtank: No API Key";
        return null;}

    $data = array('url'=>$MAIN_URI,
        'format'=>'json',
        'app_key'=>$MimedefangPhishTankAPIKey);

    $ch = curl_init();
    $URL="http://checkurl.phishtank.com/checkurl/";
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_URL, "$URL");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $reply=curl_exec($ch);
    $Infos= curl_getinfo($ch);

    $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $headers=substr($reply, 0, $header_size);
    $content=substr( $reply, $header_size );
    curl_close($ch);
    $headerZ=explode("\n",$headers);

    foreach ($headerZ as $line){
        if(!preg_match("#(.+?):(.+)#",$line,$re)){continue;}
        $HEADZ[trim($re[1])]=trim($re[2]);

    }

    if(intval($Infos["http_code"])<>200){
        return 0;
    }


    $main=json_decode($content);
    if($main->results->in_database){

        return 105;
    }
    return 0;
}



