<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["jsafter"])){jsafter();exit;}
js();

function jsafter(){

    header("content-type: application/x-javascript");
    $f[]="if(document.getElementById('table-loader-versions-service')){";
    $f[]="\tLoadAjax('table-loader-versions-service','fw.versions.php?table=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('main-dashboard-status')){";
    $f[]="\tLoadAjaxSilent('main-dashboard-status','fw.system.status.php');";
    $f[]="}";
    $f[]="if(document.getElementById('unbound-status')){";
    $f[]="LoadAjaxSilent('unbound-status','fw.dns.unbound.php?unbound-status=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('dnsdist-left')){";
    $f[]="LoadAjax('dnsdist-left','fw.dns.unbound.php?dnsdist-status-left=yes');";
    $f[]="}";
    $f[]="Loadjs('fw.icon.top.php')";
    $f[]="dialogInstance11.close();";
    echo @implode("\n",$f)."\n";

}

function js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	return $tpl->js_dialog11("{new_available_hotfix}", "$page?popup=yes",670);
}


function popup():bool{
	$tpl            = new template_admin();
	$page           = CurrentPageName();

    $sock=new sockets();
    $data=$sock->REST_API("/system/artica/hotfix/devs");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
        return "";
    }
    foreach ($json->Hotfixes as $index=>$class) {

        $version = $class->version;
        $versionBin = $class->versionBin;
        $size = $class->size;
        $url = $class->url;
        $time = $class->time;
        $MAIN[$versionBin]["version"]=$version;
        $MAIN[$versionBin]["size"]=$size;
        $MAIN[$versionBin]["url"]=$url;
        $MAIN[$versionBin]["time"]=$time;
    }
    krsort($MAIN);


    $html[]="<div class='table-responsive'>";
    $html[]="<table class='table table-striped'>";
    $html[]="<tbody>";


    foreach ($MAIN as $vbin=>$main){

    $version=$main["version"];
    $size=$main["size"];
    $URI=$main["url"];
    $time=$main["time"];



    $md5=md5(serialize($main));
    $html[]="<tr id='$md5'>";
    $html[]="<td width=1% nowrap>".$tpl->time_to_date($time,true)."s</td>";
    $html[]="<td width=99% nowrap style='font-weight:bold'><a href=\"$URI\">$version</a></td>";
	$html[]="<td width=1% nowrap>$size</td>";

        $jsrestart=$tpl->framework_buildjs("/system/artica/hotfix/update/devs/$vbin",
        "system.hotfix.progress","system.hotfix.progress.txt",
        "{$_GET["popup"]}-$vbin-progress-install","dialogInstance2.close();Loadjs('$page?jsafter=yes');");

        $bton=$tpl->button_autnonome("{install_upgrade}",
            "$jsrestart",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


    $html[]="<td width=1% nowrap>$bton</td>";

	$html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan='5'><div style='width:100%;margin:5px' id='{$_GET["popup"]}-$vbin-progress-install'></div></td>";
    $html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="</table></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function file_uploaded(){

    $product=$_GET["product"];
    $key=$_GET["key"];
    $file=urlencode($_GET["file-uploaded"]);


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.installsoft.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.installsoft.progress.txt";
    $ARRAY["CMD"]="system.php?installv2=yes&product=$product&key=$key&file-uploaded=$file";
    $ARRAY["TITLE"]="{installing} $product";
    $ARRAY["AFTER"]="LoadAjax('table-loader-versions-service','fw.versions.php?table=yes');";

    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=$product-$key-progress-install')";
    echo $jsrestart;

}

