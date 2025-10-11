<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/ressources/class.modsectools.inc');

if(isset($_GET["form"])){search_form();exit;}
if(isset($_GET["filter-bysite"])){filter_bysite();exit;}
if(isset($_GET["search"])){search();exit;}

page();
function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html=$tpl->page_header("{WAF_LONG}: {threats}",
        ico_eye,"{APP_WAF_VIRUS_EXPLAIN}","$page?form=yes","waf-viruses",null,false,"div-waf-viruses"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{WAF}: {threats}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

function search_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $urltoadd="";
    if(isset($_GET["uuid"])){
        $urltoadd="&uuid={$_GET["uuid"]}";

    }
    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT ID,servicename FROM nginx_services WHERE enabled=1 ORDER BY servicename";
    $results=$q->QUERY_SQL($sql);


    foreach ($results as $index=>$ligne){
        $servicename=$ligne["servicename"];
        $ID=$ligne["ID"];
        $zids[$ID]=$servicename;
        $Key="$servicename-$ID";
        $servicenameencode=urlencode($servicename);

        $options["DROPDOWN"]["CONTENT"]["$servicename"]="Loadjs('$page?filter-bysite=$Key&srvname=$servicenameencode&function=%s')";
    }



    $title=$tpl->_ENGINE_parse_body("{websites}");
    if(isset($_SESSION["NGINXSEARCH"]["proxy_upstream_name"])){
        if(preg_match("#-([0-9]+)$#",$_SESSION["NGINXSEARCH"]["proxy_upstream_name"],$re)){
            $title=$zids[$re[1]];
        }
    }
    $options["DROPDOWN"]["TITLE"]=$title;
    echo "<div style='margin-top:5px'>&nbsp;</div>";
    echo $tpl->search_block($page,null,null,null,$urltoadd,$options);
    return true;
}
function filter_bysite(){
    header("content-type: application/x-javascript");
    $key=$_GET["filter-bysite"];
    $function=$_GET["function"];
    $servname=base64_encode($_GET["srvname"]);
    $_SESSION["NGINXSEARCH"]["proxy_upstream_name"]=$key;

    $f[]="if(document.getElementById('SearchBlockDropDownTitle')){";
    $f[]="\tdocument.getElementById('SearchBlockDropDownTitle').innerHTML=base64_decode('$servname');";
    $f[]="}";
    $f[]="$function();";
    echo @implode("\n",$f);
}

function search():bool
{
    $page = CurrentPageName();
    $function = $_GET["function"];
    $tpl = new template_admin();
    clean_xss_deep();
    $urltoadd = null;
    if (!isset($_GET["search"])) {
        $_GET["search"] = "";
    }
    if (isset($_GET["uuid"])) {
        $urltoadd = "&uuid={$_GET["uuid"]}";

    }
    $index = 0;
    $MAIN = $tpl->format_search_protocol($_GET["search"]);
    $line = base64_encode(serialize($MAIN));
    if (!isset($_SESSION["NGINXSEARCH"])) {
        $_SESSION["NGINXSEARCH"] = array();
    }
    $SearchArray = $_SESSION["NGINXSEARCH"];
    $opts=base64_encode(serialize($SearchArray));

    if(preg_match("#-([0-9]+)$#",$_SESSION["NGINXSEARCH"]["proxy_upstream_name"],$re)){
        $index=$re[1];
    }
    $RFile=PROGRESS_DIR."/nginx-searchav.$index.syslog";
    $PFile=PROGRESS_DIR."/nginx-searchav.pattern";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("nginx.php?nginx-searchav=$line&opts=$opts&uuid={$_GET["uuid"]}&index=$index");

    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT ID,servicename FROM nginx_services ORDER BY servicename";
    $resultsServs=$q->QUERY_SQL($sql);

    $zids=array();
    foreach ($resultsServs as $index=>$ligne){
        $servicename=$ligne["servicename"];
        $ID=intval($ligne["ID"]);
        $zids[$ID]=$servicename;

    }


    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
    	    <th>&nbsp;</th>
        	<th>{date}</th>
        	<th>&nbsp;</th>
        	<th>&nbsp;</th>
        	<th nowrap>{src}</th>
        	<th>{sitename}</th>
        	<th>{virus}</th>
        	<th>{path}</th>
        </tr>
  	</thead>
	<tbody>
	
";
    $modtools=new modesctools();
    $Data=@file_get_contents($RFile);
    $results=explode("\n",$Data);
    krsort($results);
    $color_style="";
    $tdhead1="style='width:1%;$color_style' nowrap";
    $tdhead2="style='width:99%;$color_style'";

    $types["info"]="<span class='label label-default'>{info}</span>";
    $types["warn"]="<span class='label label-warning'>{warning}</span>";
    $types["error"]="<span class='label label-danger'>{error}</span>";

    $status[0]="<span class='label label-default'>{clean}</span>";
    $status[1]="<span class='label label-danger'>{infected}</span>";

    foreach ($results as $index=>$json){
        $BigMd5=md5($json);
        $json=trim($json);
        if(strlen($json)<5){continue;}
        $ligne=json_decode($json);

        $level=$ligne->level;
        $time=$ligne->time;
        $date=$tpl->time_to_date($time,true);
        $rflag=$types[$level];
        $message=$ligne->message;

        $xStatus=0;
        $VirusName="&nbsp;";
        $Ipaddr="";
        $Uri="&nbsp;";
        $Servername="&nbsp;";
        if(preg_match("#^.*?Status:([0-9]+)\|Name:(.+?)\|IP:(.+?)\|Uri:(.+?)\|Serverid:([0-9]+)#i",$message,$re)){
            $xStatus=$status[$re[1]];
            $VirusName=$re[2];
            $Ipaddr=$re[3];
            $Uri=$re[4];
            $ServerID=intval($re[5]);
            $Servername=$zids[$ServerID];
            $message="";
        }
        if(preg_match("#.*.\[VIRUS_FOUND\] (.+?) From IP:(.*?) Uri:(.+?) Serverid:([0-9]+)#i",$message,$re)){
            $xStatus=$status[1];
            $VirusName=$re[1];
            $Ipaddr=$re[2];
            $Uri=$re[3];
            $ServerID=intval($re[4]);
            $Servername=$zids[$ServerID];
            $message="";
        }
        if(preg_match("#.*.\[VIRUS_FOUND\] (.+)#i",$message,$re)){
            $xStatus=$status[1];
            $VirusName=$re[1];
            $Ipaddr="";
            $Servername="{unknown}";
            $message="";
        }

        if(strlen($Ipaddr)>2) {
            $modtools->hostinfo($Ipaddr);
            $hostname = $modtools->hostname;
            $flag = $modtools->flag;
            $Ipaddr = $tpl->td_href("$Ipaddr - $hostname", $modtools->country_name, "Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$Ipaddr')");
        }
        if($message<>null){
            $html[]="<tr id='$BigMd5'>";
            $html[]="<td $tdhead1>$rflag</td>";
            $html[]="<td $tdhead1>$date</td>";
            $html[]="<td $tdhead1><span class='label label-default'>SCANNER</span></td>";
            $html[]="<td colspan='5'>$message</td>";
            $html[]="</tr>";
            continue;
        }

        $html[]="<tr id='$BigMd5'>";
        $html[]="<td $tdhead1>$rflag</td>";
        $html[]="<td $tdhead1>$date</td>";
        $html[]="<td $tdhead1>$xStatus</td>";
        $html[]="<td width='1%' nowrap><img src='img/$flag'></td>";
        $html[]="<td $tdhead1>$Ipaddr</td>";
        $html[]="<td $tdhead1>$Servername</td>";
        $html[]="<td $tdhead1><strong>$VirusName</strong></td>";
        $html[]="<td $tdhead2>$Uri</td>";
        $html[]="</tr>";


    }


    $TINY_ARRAY["TITLE"]="{WAF_LONG}: {threats}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_WAF_VIRUS_EXPLAIN}";
    $TINY_ARRAY["URL"]="waf-viruses";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents($PFile)."</i></div>";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}