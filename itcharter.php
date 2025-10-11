<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);


if( isset($_GET["Token"]) OR ( isset($_POST["AcceptChart"]) ) ) {
    include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
    if (!isset($GLOBALS["CLASS_SOCKETS"])) {
        if (!class_exists("sockets")) {
            include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
        }
        $GLOBALS["CLASS_SOCKETS"] = new sockets();
    }
    include_once(dirname(__FILE__) . "/ressources/class.squid.templates-simple.inc");

    if(isset($_GET["Token"])){build_page();exit;}
    if(isset($_POST["AcceptChart"])){build_save();exit;}

}

    die("Access Denied");

//50:6b:8d:29:27:05|2|GET|artica-proxy.com

build_page();

function build_page_redirect($redirect,$ChartID){
    $t=time();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT ChartHeaders FROM itcharters WHERE ID='$ChartID'");
    $ChartHeaders=$ligne["ChartHeaders"];
    $html[]="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
    $html[]="<html xmlns=\"http://www.w3.org/1999/xhtml\" dir=\"ltr\" lang=\"en\">";
    $html[]="<head>";
    $html[]="<title>{$ligne["title"]}</title>";
    $html[]="<meta http-equiv=\"refresh\" content=\"5; URL=$redirect\" />";
    $html[]=base64_decode($ChartHeaders);
    $html[]="</head>";
    $html[]="<body class=MsoBody>";
    $html[]=" <div class=\"middle-box\">";
    $html[]="<h1>{$ligne["title"]}</h1>";
    $html[]="<H2>Redirecting... </h2>
        <p class=MsoNormal><a href='$redirect/?t=$t'>$redirect</a></p>
   </div>";
    $html[]="</body>";
    $html[]="</html>";
    header('Content-type: text/html');
    echo @implode("\n",$html);

}

function build_page(){
    $data=base64_decode($_GET["Token"]);
    VERBOSE($_GET["Token"],__LINE__);
    VERBOSE($data,__LINE__);
    $t=time();
    $exploded=explode("|",base64_decode($_GET["Token"]));
    $UserKey=$exploded[0];
    $ChartID=intval($exploded[1]);
    $PROTO=$exploded[2];
    $DOMAIN=$exploded[3];
    if($UserKey=="PDF"){ouputpdf($ChartID);exit;}
    if($ChartID==0){die("No Chart ID defined");}
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT ChartHeaders,enablepdf,TextIntro,PdfFileName,TextButton,`title`,ChartContent FROM itcharters WHERE ID='$ChartID'");
    if($ligne["TextIntro"]==null){$ligne["TextIntro"]="Please read the IT chart before accessing trough Internet";}
    if($ligne["TextButton"]==null){$ligne["TextButton"]="I accept the terms and conditions of this agreement";}
    $ChartHeaders=$ligne["ChartHeaders"];
    $TextIntro=$ligne["TextIntro"];
    $TextButton=$ligne["TextButton"];
    $enablepdf=intval($ligne["enablepdf"]);

    $html[]="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
    $html[]="<html xmlns=\"http://www.w3.org/1999/xhtml\" dir=\"ltr\" lang=\"en\">";
    $html[]="<head>";
    $html[]="<title>{$ligne["title"]}</title>";
    $html[]=base64_decode($ligne["ChartHeaders"]);
    $html[]="</head>";
    $html[]="<body class=MsoBody>";
    $html[]=" <div class=\"middle-box\">";
    $html[]="<h1>{$ligne["title"]}</h1>";
    $html[]="<div class='Textintro'><p class=MsoNormal>$TextIntro</p></div>";





    if($ligne["enablepdf"]==1){
        $URL=urlencode(base64_encode("PDF|$ChartID|NONE|NONE"));
        $ligne["ChartContent"]="
        <p>&nbsp;</p>
		<object data=\"/ITCharter/$URL\" type=\"application/pdf\" width=\"800\" height=\"600\">
 		<p class=Textintro>It appears you don't have a PDF plugin for this browser.
 		 <br>You can <a href=\"/ITCharter/$URL\">click here to download the {$ligne["PdfFileName"]} file.</a></p>
  		</object>
  		<p>&nbsp;</p>		
		";
    }else{
        $ligne["ChartContent"]="<div class='ChartContent'>".base64_decode($ligne["ChartContent"])."</div>";
    }

	$html[]="<!-- chart id: $ChartID -->
		
	
	<p style='margin-left:50px' id='$t'>{$ligne["ChartContent"]}</p>
	
	<form method='post' action='/itcharter.php' id='post-itchart'>
		<input type='hidden' name='AcceptChart' value='yes'>
		<input type='hidden' name='AcceptChartContent' value='{$_GET["Token"]}'>
	</form>
	
	
	<div class='MsoButtonDiv'>
          <a class=\"effect1\" href=\"#\" OnClick='Accept$t()'>
                {$ligne["TextButton"]}<span class=\"bg\"></span>
           </a>
    </div>

	</div>";

    $html[]="<script>
function Accept$t(){
	document.forms['post-itchart'].submit();
}
</script>";
    $html[]="</body>";
    $html[]="</html>";



    header('Content-type: text/html');
    echo @implode("\n",$html);



}
function ouputpdf($ID){


    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT PdfFileName,PdfFileSize,PdfContent FROM itcharters WHERE ID='$ID'");
    $PdfFileName=$ligne["PdfFileName"];
    $PdfFileSize=$ligne["PdfFileSize"];
    header('Content-type: application/pdf');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: Inline; filename=\"$PdfFileName\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$PdfFileSize);
    ob_clean();
    flush();
    echo base64_decode($ligne["PdfContent"]);
}

function build_save(){
    $Data=base64_decode($_POST["AcceptChartContent"]);
    $exploded=explode("|",$Data);
    $KEYACCOUNT=$exploded[0];
    $itchartid=$exploded[1];
    $PROTO=$exploded[2];
    $DOMAIN=$exploded[3];

    WLOG("[SPLASH]:$KEYACCOUNT: As accepted the IT Chart ID $itchartid (redirect to $PROTO $DOMAIN)",__LINE__);
    $Key="$KEYACCOUNT|$itchartid";
    $ClusterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterEnabled"));
    $ClusterMaster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterMaster"));
    $redis=new Redis();

    $redis_server='127.0.0.1';
    $redis_port=6123;
    if($ClusterEnabled==1){
        if(strpos($ClusterMaster,":")>0){
            $ff=explode(":",$ClusterMaster);
            $redis_server=$ff[0];
            $redis_port=$ff[1];
        }else{$redis_server=$ClusterMaster;}
    }
    try {
        $redis->connect($redis_server,$redis_port);
    } catch (Exception $e) {
        WLOG("[ERROR]: ".$e->getMessage(),__LINE__);
        build_page();
        return;
    }

    $redis->set($Key,time());
    $pref="http";
    if($PROTO=="CONNECT"){$pref="https";}
    build_page_redirect("{$pref}://$DOMAIN",$itchartid);
    die();


}
function WLOG($text,$line=0){
    $text="$text ($line)";
    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("ItCharter", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}

}

?>