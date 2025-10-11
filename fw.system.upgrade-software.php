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
    echo @implode("\n",$f)."\n";

}

function js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $fkey=null;
    $uuid="";
    if(isset($_GET["uuid"])){
        $uuid="&uuid=".$_GET["uuid"];
    }

    if(isset($_GET["filter-key"])){
        $fkey="&filter-key=".intval($_GET["filter-key"]);
    }
	$title=$tpl->_ENGINE_parse_body("{{$_GET["product"]}}");
    if($_GET["product"]=="APP_SYSLOGD"){
        $title=$tpl->_ENGINE_parse_body("{APP_RSYSLOG}");
    }
    if($_GET["product"]=="APP_QAT"){
        $title= "Intel QuickAssist";
    }

	$tpl->js_dialog11($title, "$page?popup={$_GET["product"]}$fkey$uuid",670);
    return true;
}


function popup():bool{
	$tpl            = new template_admin();
	$page           = CurrentPageName();
    $kernbin        = 0;
    $PKEY           = $_GET["popup"];
    $product        = "{{$PKEY}}";
    $product        = $tpl->_ENGINE_parse_body("$product");
    $product_text   = $product;
    $uuid="";
    if(isset($_GET["uuid"])) {
        $uuid = $_GET["uuid"];
    }

    $fkey=0;
    if(isset($_GET["filter-key"])){
        $fkey=intval($_GET["filter-key"]);

    }
    $product_text=  $tpl->_ENGINE_parse_body("{{$_GET["popup"]}}");
    if($PKEY=="APP_SYSLOGD"){
        $product_text= $tpl->_ENGINE_parse_body("{APP_RSYSLOG}");
    }
    if($PKEY=="APP_QAT"){
        $product_text= "Intel QuickAssist";
    }


    if(strlen($product_text)>122){$product_text=substr($product_text,0,119)."...";}
    $UPDATES_ARRAY  = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("v4softsRepo"));

	$html[]=$tpl->div_explain("$product_text||{install_this_prog}");
	$html[]="<table style='width:100%'>";
	$html[]="<tbody>";
	$html[]="<tr>";
	$html[]="<td style='vertical-align:top'>";
	$html[]="<div class='table-responsive'>";
	$html[]="<table class='table table-striped'>";
	$html[]="<tbody>";
    $PRODUCT=$_GET["popup"];

	if(!is_array($UPDATES_ARRAY)){
	    $t=time();

        $prgress=$tpl->framework_buildjs("/system/softwares/refresh",
            "UpdateReposIndex.progress","UpdateReposIndex.log",
            "$t","Loadjs('$page?product={$_GET["popup"]}');Loadjs('fw.system.upgrade-software.php?jsafter=yes');");


        $jsrestart2="Loadjs('fw.progress.php?content=$prgress&mainid=$t')";
        echo "<div id='$t'></div><script>$jsrestart2</script>";
        return true;
    }

    if(!isset($UPDATES_ARRAY[$PKEY])){$UPDATES_ARRAY[$PKEY]=array();}
	$ISARRAY    = $UPDATES_ARRAY[$PKEY];
    if($PKEY=="APP_XKERNEL" OR $PKEY=="APP_XTABLES") {
        $kernbin = $tpl->kernel_binary_ver();
    }

	krsort($ISARRAY);
    $NEWARRAY=array();
    foreach ($ISARRAY as $integ=>$array){
        $VERSION=$array["VERSION"];
        $VERSION=str_replace("-",".",$VERSION);
        $VERSIONTB=explode(".",$VERSION);
        VERBOSE("VERSION = $VERSION",__LINE__);
        array_unshift($VERSIONTB,"000");

        foreach ($VERSIONTB as $index=>$num){
            if(strlen($num)<2){
                $num="$num"."0";
                $VERSIONTB[$index]=$num;
            }
            if(strlen($num)<3){
                $num="$num"."00";
                $VERSIONTB[$index]=$num;
            }
        }

        if(count($VERSIONTB)<3){
            $VERSIONTB[]="000";
        }
        if(count($VERSIONTB)<4){
            $VERSIONTB[]="000";
        }
        if(count($VERSIONTB)<5){
            $VERSIONTB[]="000";
        }
        $array["INTEGER"]=$integ;

        $VERSIONBIN=intval(@implode("",$VERSIONTB));
        VERBOSE("VERSION = $VERSION = $VERSIONBIN",__LINE__);
        $NEWARRAY[$VERSIONBIN]=$array;
    }

    krsort($NEWARRAY);

    foreach ($NEWARRAY as $none=>$array){
        $integ=$array["INTEGER"];
		$VERSION=$array["VERSION"];
		$DISTRI=$array["DISTRI"];
		$SIZE=$array["SIZE"];
		$size=FormatBytes($SIZE/1024);
        $URI=$array["URI"];
        $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
        if ($ArticaRepoSSL==1){
            $URI=str_replace("http://mirror.articatech.com", "https://www.articatech.com",$URI);
        }
        VERBOSE("Verison:$VERSION Distri:$DISTRI size:$SIZE uri:$URI:fkey:$fkey",__LINE__);
        $md5=md5(serialize($array));
        if($fkey>0){
            if($integ<>$fkey){
                VERBOSE("Check $integ<>$fkey CONTINUE",__LINE__);
                continue;}
        }

		$html[]="<tr id='$md5'>";
		$html[]="<td width=99% nowrap style='font-weight:bold'><a href=\"$URI\">$product_text</a> $VERSION</td>";
		$html[]="<td width=1% nowrap>$size</td>";

        $jsrestart=$tpl->framework_buildjs("/system/softwares/install/{$_GET["popup"]}/$integ",
        "system.installsoft.progress","system.installsoft.progress.txt",
        "{$_GET["popup"]}-$integ-progress-install","dialogInstance2.close();Loadjs('$page?jsafter=yes');");

        if(strlen($uuid)>5){
            $jsrestart=$tpl->framework_buildjs("hamrp.php?installv2=yes&product={$_GET["popup"]}&key=$integ&uuid=$uuid",
                "system.installsoft.$uuid.progress","system.installsoft.$uuid.progress.txt",
                "{$_GET["popup"]}-$integ-progress-install","dialogInstance2.close();Loadjs('$page?jsafter=yes');");

        }

		
		$bton=$tpl->button_autnonome("{install_upgrade}",
				"$jsrestart",
				"fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $upload=$tpl->button_upload("{upload}",$page,"btn-primary btn-xs","&product={$_GET["popup"]}&key=$integ");


        if($PKEY=="APP_XKERNEL" OR $PKEY=="APP_XTABLES") {
            VERBOSE("Check $integ<>$kernbin",__LINE__);
		    if($integ<>$kernbin){
                $bton=$tpl->button_autnonome("{install_upgrade}",
                    "blur()",
                    "fa-download","AsSystemAdministrator",0,"btn btn-xs");

            $upload=$tpl->button_autnonome("{upload}",
                    "blur()",
                    "fa fa-upload","AsSystemAdministrator",0,"btn btn-xs");
            }
        }
        if(strlen($uuid)>5){
            $upload="&nbsp;";
        }

		
		$html[]="<td width=1% nowrap>$bton</td>";
        $html[]="<td width=1% nowrap>$upload</td>";
		$html[]="</tr>";
        $html[]="<tr>";
        $html[]="<td colspan='4'><div style='width:100%;margin:5px' id='{$_GET["popup"]}-$integ-progress-install'></div></td>";
        $html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="</table>";

    if($PRODUCT=="APP_TAILSCALE"){
        $html[]="<div id='APP_TAILSCALE_PROGRESS'>";
        $js=$tpl->framework_buildjs("tailscale.php?pkgupgrade=yes",
            "tailscale.apt.progress","tailscale.apt.log","APP_TAILSCALE_PROGRESS");
        $html[]="<div style='text-align:right'>";
        $html[]=$tpl->button_autnonome("{upgrade_via_system}",$js,"fas fa-sync-alt");
        $html[]="</div></div>";
    }

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

