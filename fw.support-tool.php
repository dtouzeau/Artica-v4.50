<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
// Service Pack 9
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["checkdownloads"])){checkdownloads();exit;}
if(isset($_POST["SupportToolAnonymize"])){save_anonymize();exit;}
if(isset($_GET["anonmap-js"])){anonmap_js();exit;}
if(isset($_GET["anonmap-popup"])){anonmap_popup();exit;}


js();

function js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $subtitle="{OS}";
    $reverse="";
    if(isset($_GET["reverse"])){
        $subtitle="{APP_NGINX}";
        $reverse="&reverse=yes";
    }
	return $tpl->js_dialog6("{build_support_package} $subtitle", "$page?popup=yes$reverse",580);
}

function popup(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
    $subtitle="{OS}";
    $checkdownloads="";
    $jsbton= $tpl->framework_buildjs(
        "/system/support/tool/generate",
        "squid.debug.support-tool.progress","squid.debug.support-tool.progress.txt","support-progress-tool",
    "LoadAjax('support-progress-download','$page?checkdownloads=yes');");

    if(isset($_GET["reverse"])){
        $subtitle="{APP_NGINX}";
        $checkdownloads="&reverse=yes";
        $jsbton= $tpl->framework_buildjs(
            "nginx:/supporttool",
            "reverse-proxy-support.progress",
            "reverse-proxy-support.progress.log","support-progress-tool",
            "LoadAjax('support-progress-download','$page?checkdownloads=yes$checkdownloads');");
    }
    if(isset($_GET["smtp"])){
        $checkdownloads="&smtp=yes";
        $subtitle="{APP_POSTFIX}";
        $jsbton= $tpl->framework_buildjs(
            "/system/support/postfix/build",
            "postfix.support.progress",
            "postfix.support.progress.log",
            "support-progress-tool",
            "LoadAjax('support-progress-download','$page?checkdownloads=yes$checkdownloads');");
    }





    $anonblock="";
    if(!isset($_GET["reverse"]) && !isset($_GET["smtp"])){
        $anon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SupportToolAnonymize"));
        $btmap=$tpl->button_autnonome("{anonymization_map}","Loadjs('$page?anonmap-js=yes')","fas fa-exchange-alt");
        $anonblock=$tpl->BigCircleCheckbox("SupportToolAnonymize","{support_tool_anonymize}","{support_tool_anonymize_explain}",$anon)
            ."<div style='margin:10px 0' class='center'>$btmap</div>";
    }

	$bton=$tpl->button_autnonome("{build_now}", $jsbton, "fas fa-file-archive");

	$html[]="<div id='support-progress-tool'></div>";
	$html[]="<div id='support-tool-explain' style='margin-top:10px'>
	<div class='alert alert-info'><strong>$subtitle</strong>:<br>{build_support_package_explain}</div>
	$anonblock
	<center>$bton</center>";
	$html[]="</div>";
	$html[]="<div id='support-progress-download'></div>";
	$html[]="<script>LoadAjax('support-progress-download','$page?checkdownloads=yes$checkdownloads');</script>";

	
	echo $tpl->_ENGINE_parse_body($html);
}

function checkdownloads():bool{

    if(isset($_GET["reverse"])){

        if(!is_file("ressources/conf/upload/reverse-proxy-support.tar.gz")){return true;}
        $size=filesize("ressources/conf/upload/reverse-proxy-support.tar.gz");
        $size=FormatBytes($size/1024);
        $date=date("Y-m-d H:i:s",filemtime("ressources/conf/upload/reverse-proxy-support.tar.gz"));

        $filedown="
		<div style='margin:15px' class='center'>
		<a href='ressources/conf/upload/reverse-proxy-support.tar.gz'>
		<img src='img/file-compressed-128.png' class='img-rounded'>
		</a><br>
		<a href='ressources/support/support.tar.gz'><small>reverse-proxy-support.tar.gz ($size)</small></a><br>
		<a href='ressources/support/support.tar.gz'><small>$date</small></a>
		</div>
			
		";
        echo $filedown;
        return true;
    }

    if(isset($_GET["smtp"])){

        if(!is_file("ressources/conf/upload/postfix.support.tar.gz")){return true;}
        $size=filesize("ressources/conf/upload/postfix.support.tar.gz");
        $size=FormatBytes($size/1024);
        $date=date("Y-m-d H:i:s",filemtime("ressources/conf/upload/postfix.support.tar.gz"));

        $filedown="
		<div style='margin:15px' class='center'>
		<a href='ressources/conf/upload/postfix.support.tar.gz'>
		<img src='img/file-compressed-128.png' class='img-rounded'>
		</a><br>
		<a href='ressources/support/support.tar.gz'><small>postfix.support.tar.gz ($size)</small></a><br>
		<a href='ressources/support/support.tar.gz'><small>$date</small></a>
		</div>
			
		";
        echo $filedown;
        return true;
    }

	if(!is_file("ressources/support/support.tar.gz")){return true;}
	
		$size=filesize("ressources/support/support.tar.gz");
		$size=FormatBytes($size/1024);
		$date=date("Y-m-d H:i:s",filemtime("ressources/support/support.tar.gz"));
	
		$filedown="
		<div style='margin:15px' class='center'>
		<a href='ressources/support/support.tar.gz'>
		<img src='img/file-compressed-128.png' class='img-rounded'>
		</a><br>
		<a href='ressources/support/support.tar.gz'><small>support.tar.gz ($size)</small></a><br>
		<a href='ressources/support/support.tar.gz'><small>$date</small></a>
		</div>
			
		";
	
	echo $filedown;
    return true;
}

function save_anonymize():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return true;
}

function anonmap_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog7("{anonymization_map}","$page?anonmap-popup=yes",750);
}

function anonmap_popup():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/support/tool/anonmap"));
    if(!$json || !property_exists($json,"Status") || !$json->Status){
        echo "<div class='alert alert-danger'>".$tpl->_ENGINE_parse_body("{error}")."</div>";
        return false;
    }
    $maps=$json->data;
    if(!is_array($maps) || count($maps)==0){
        echo $tpl->div_explain("{no_anonymization_map}");
        return true;
    }
    $id=md5(microtime());
    $html=array();
    $html[]="<input type='text' class='form-control' id='anon-filter-$id' placeholder='1.2.3.17 / 00:50:56:00:00:01' style='margin-bottom:10px'>";
    foreach($maps as $m){
        $html[]="<h3>Bundle ".intval($m->id)." &mdash; ".htmlspecialchars($m->created)."</h3>";
        $html[]="<table class='table table-striped anon-map-$id'><thead><tr><th>{fictive_value}</th><th>{real_value}</th></tr></thead><tbody>";
        // Tri numérique : l'ordre du JSON (1.2.10, 1.2.100, 1.2.101… avant 1.2.11)
        // rendait illisibles les ~4 000 réseaux d'une box de production.
        $nets=(array)$m->ipv4_24;
        uksort($nets,'version_compare');
        $macs=(array)$m->mac;
        ksort($macs,SORT_STRING);
        foreach($nets as $fict=>$real){
            $html[]="<tr><td>".htmlspecialchars("$fict.x")."</td><td>".htmlspecialchars("$real.x")."</td></tr>";
        }
        foreach($macs as $fict=>$real){
            $html[]="<tr><td>".htmlspecialchars($fict)."</td><td>".htmlspecialchars($real)."</td></tr>";
        }
        $html[]="</tbody></table>";
    }
    // Filtre côté navigateur : on colle l'adresse citée par le support (1.2.3.17),
    // la ligne de son /24 (1.2.3.x) reste affichée. « input » et pas seulement
    // « keyup » : un collage à la souris ne produit aucune touche. Comparaison
    // CELLULE par cellule : en sous-chaîne, 1.2.3.17 trouvait aussi 21.2.3.x.
    $html[]="<script>
    $('#anon-filter-$id').on('input keyup',function(){
        var v=this.value.trim().toLowerCase();
        var parts=v.split('.');
        var net=(parts.length===4)?parts.slice(0,3).join('.')+'.x':'';
        $('.anon-map-$id tbody tr').each(function(){
            var hit=(v==='');
            $(this).find('td').each(function(){
                var c=$(this).text().trim().toLowerCase();
                if(c===v || (net!=='' && c===net) || (net==='' && v.length>2 && c.indexOf(v)>=0)){hit=true;}
            });
            $(this).toggle(hit);
        });
    });
    </script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
