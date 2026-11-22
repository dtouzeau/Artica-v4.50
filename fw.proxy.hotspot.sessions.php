<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$users=new usersMenus();if(!$users->AsHotSpotManager){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["HotSpotRedirectUI"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["uncheck"])){uncheck();exit;}
if(isset($_GET["remove"])){remove();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["reload-squid-cache"])){reload_squid_cache();exit;}

page();


function remove(){
    $sessionkey=$_GET["remove"];
    $md=$_GET["md"];
    $tpl=new template_admin();
    $tpl->js_confirm_delete("{session} $sessionkey","remove",$sessionkey,"$('#$md').remove();");
}
function remove_perform(){
    $sessionkey=$_POST["remove"];
    // Pentest 2026-11-04 : remove (POST) etait concatene dans la requete.
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $memcached=new lib_memcached();
    $rs=$q->QUERY_SQL_PARAMS("SELECT * FROM sessions WHERE sessionkey=?",array((string)$sessionkey));$ligne=isset($rs[0])?$rs[0]:array();
    $macaddress=$ligne["macaddress"];
    $ipaddr=$ligne["ipaddr"];
    $memcached->Delkey("MICROHOTSPOT:$ipaddr");
    if($macaddress<>null){$memcached->Delkey("MICROHOTSPOT:$macaddress");}
    $q->QUERY_SQL_PARAMS("DELETE FROM sessions WHERE sessionkey=?",array((string)$sessionkey));
    if(!$q->ok){echo $q->mysql_error;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/nohup/reload");
}


function uncheck(){

    $sessionkey=$_GET["uncheck"];
    // Pentest 2026-11-04 : uncheck (GET) etait concatene dans la requete.
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $rs=$q->QUERY_SQL_PARAMS("SELECT * FROM sessions WHERE sessionkey=?",array((string)$sessionkey));$ligne=isset($rs[0])?$rs[0]:array();
    $macaddress=$ligne["macaddress"];
    $ipaddr=$ligne["ipaddr"];
    $enabled=$ligne["enabled"];
    if($enabled==1){$newenabled=0;}else{$newenabled=1;}
    $q->QUERY_SQL_PARAMS("UPDATE sessions SET enabled=$newenabled WHERE sessionkey=?",array((string)$sessionkey));
    if($q->ok){
        admin_tracks("Update HotSpot session key $sessionkey to enabled=$newenabled");
    }
    $memcached=new lib_memcached();


    if($newenabled==0){
        if($macaddress<>null){$memcached->Delkey("MICROHOTSPOT:$macaddress");}
        $memcached->Delkey("MICROHOTSPOT:$ipaddr");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/nohup/reload");
    }else{
        $rs=$q->QUERY_SQL_PARAMS("SELECT * FROM sessions WHERE sessionkey=?",array((string)$sessionkey));$ligne=isset($rs[0])?$rs[0]:array();
        if($macaddress<>null){$memcached->saveKey("MICROHOTSPOT:$macaddress",$ligne,5*60);}
        $memcached->saveKey("MICROHOTSPOT:$ipaddr",$ligne,5*60);
    }
}

function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{sessions_manager} v5",
        "fas fa-address-card",
        "{sessions_manager_explain}",
        "$page?table=yes",
        "hotspot-sessions",
        "progress-hotspot-sessions-restart",false,"table-hotspot-sessions");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{sessions_manager}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{general_settings}"]="$page?table=yes";
    echo $tpl->tabs_default($array);
}

function reload_squid_cache(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/nohup/reload");
    $tpl=new template_admin();
    $tpl->js_display_results("{empty_cache}","{empty_cache} {success}");
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/squid/hotspot/database.db");
    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";

    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?reload-squid-cache=yes')\"><i class='fa fa-sync-alt'></i> {empty_cache} </label>";
    $btns[]="</label>";

    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"LoadAjax('table-hotspot-sessions','$page?table=yes')\"><i class='fa fa-sync-alt'></i> {refresh} </label>";
    $btns[]="</label>";

    $btns[]="</div>";
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th class='awc-nowrap'>{created}</th>";
	$html[]="<th class='awc-nowrap'>{member}</th>";
	$html[]="<th class='awc-nowrap'>{MAC}</th>";
	$html[]="<th class='awc-nowrap'>{ipaddr}</th>";
	$html[]="<th class='awc-nowrap'>{will_be_removed_on}</th>";
	$html[]="<th>{enabled}</th>";
	$html[]="<th>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='hotspotsessi-rows'>";
	
	// Le nombre de sessions n'est pas borne et la pagination est faite par le NAVIGATEUR
	// (awc_pager_js plus bas): sans limite, toutes les sessions partent dans le fragment,
	// qui finit par bloquer le rendu -- meme classe de panne que le RESULT_CODE_HUNG de
	// fw.proxy.whitelist.php. On ne sert que les premieres, l'operateur filtre pour le reste.
	$SESS_MAX=500;
	$sesstotal=0;
	$cnt=$q->QUERY_SQL("SELECT COUNT(*) as tcount FROM sessions");
	if(is_array($cnt) && isset($cnt[0]["tcount"])){$sesstotal=intval($cnt[0]["tcount"]);}
	$results=$q->QUERY_SQL("SELECT * FROM sessions ORDER BY removeaccount LIMIT $SESS_MAX");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	if(!is_array($results)){$results=array();}
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$Time=time();
		$md=md5(serialize($ligne));
        $TRCLASS=null;
		$enabled=$ligne["enabled"];
		// Pentest 2026-11-04 : sessionkey (donnee externe) place sans encodage dans les OnClick (XSS stockee).
		$sessionkey=req_url($ligne["sessionkey"]);
		$removeaccount=$ligne["removeaccount"];
		$macaddress=$ligne["macaddress"];
		$ipaddr=$ligne["ipaddr"];
		$username=$ligne["username"];
		$autocreate=$ligne["autocreate"];
        $tooltip=null;
		if($autocreate==1){
		    $tooltip=$tpl->_ENGINE_parse_body(" <span class='label label-warning'>{waiting_confirmation}</span>");
        }
        if($autocreate==2){
            $tooltip=$tpl->_ENGINE_parse_body(" <span class='label label-primary'>{confirmed}</span>");
        }
        if($autocreate==10){
            $tooltip=$tpl->_ENGINE_parse_body(" <span class='label label-primary'>{voucher_room}</span>");
        }

		
		if($removeaccount>0){
			
			$removed_on=distanceOfTimeInWords($Time,$removeaccount)." (".$tpl->time_to_date($removeaccount,true).")";
		}else{
			$removed_on="{never}";
		}
		if($macaddress==null){$macaddress="00:00:00:00:00:00";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>".$tpl->time_to_date($ligne["created"],true)."</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->td_href($username,null,"{Loadjs('$page?edit=$sessionkey')")." $tooltip</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->td_href($macaddress,null,"{Loadjs('$page?edit=$sessionkey')")."</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->td_href($ipaddr,null,"{Loadjs('$page?edit=$sessionkey')")."</td>";
		$html[]="<td nowrap>$removed_on</td>";
		$html[]="<td style='width:1%' nowrap class='center'>".$tpl->icon_check($enabled,
                "Loadjs('$page?uncheck=$sessionkey')",null,"AsHotSpotManager")."</td>";
		$html[]="<td style='width:1%' nowrap class='center'>".$tpl->icon_delete("Loadjs('$page?remove=$sessionkey&md=$md')",
                "AsHotSpotManager")."</center></td>";
		$html[]="</tr>";
		
	}
	
	$html[]="</tbody>";
	$html[]="";
	// "affiches / total": dire que la liste est tronquee, sinon l'operateur croit voir tout.
	$sesscompte="{showing} ".count($results).($sesstotal>count($results)?" / $sesstotal":"");
	$html[]="</table></div><div class='awc-foot'><span>$sesscompte</span><span class='awc-pg' id='hotspotsessi-pager'></span></div></div></div>";
	$html[]="";
	$html[]="<script>";

    $TINY_ARRAY["TITLE"]="{sessions_manager} v5";
    $TINY_ARRAY["ICO"]="fas fa-address-card";
    $TINY_ARRAY["EXPL"]="{sessions_manager_explain}";
    $TINY_ARRAY["URL"]="hotspot-sessions";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="\t$jstiny";
	$html[]="\tNoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"hotspotsessi-rows","hotspotsessi-pager");
}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if($_POST["HotSpotRedirectUI2"]<>null){
		$_POST["HotSpotRedirectUI"]=$_POST["HotSpotRedirectUI2"];
		unset($_POST["HotSpotRedirectUI2"]);
	}
	$tpl->SAVE_POSTs();
	
	
	
}