<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["status-top"])){status_top();exit;}
if(isset($_POST["rbl-choose"])){rbl_save();exit;}
if(isset($_GET["rbl-js"])){rbl_js();exit;}
if(isset($_GET["rbl-delete"])){rbl_delete_js();exit;}
if(isset($_POST["rbl-delete"])){rbl_delete();exit;}
if(isset($_GET["rbl-popup"])){rbl_popup();exit;}
if(isset($_GET["rbl-enable"])){rbl_enable();exit;}
if(isset($_GET["table-div"])){table_div();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["rbl-status"])){rbl_status();exit;}
if(isset($_GET["EnableDNSWL-js"])){EnableDNSWL_js();exit;}
if(isset($_GET["rblpack-enable"])){rblpack_enable();exit;}

page();

function EnableDNSWL_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableDNSWL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSWL"));
    if($EnableDNSWL==1){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSWL",0);}else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSWL",1);
    }
    header("content-type: application/x-javascript");



    echo $tpl->framework_buildjs(
        "postfix:/postfix/smtpd/restrictions/0",
    "smtpd_client_restrictions_progress",
    "smtpd_client_restrictions_progress.log",
    "progress-postfix-rbl","LoadAjax('rbl-status','$page?rbl-status=yes')");

}
function rblpack_enable(){
    // feature 016 — one-click curated RBL Pack toggle. Flips the Valkey gate EnableRblPack (0/1) then
    // rebuilds smtpd_client_restrictions via the same daemon route EnableDNSWL uses. The Go builder
    // reads EnableRblPack and appends the four reject_rbl_client rules (in order) when it is 1.
    $instance_id=intval($_GET["instance-id"]);
    $EnableRblPack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRblPack"));
    if($EnableRblPack==1){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRblPack",0);}else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRblPack",1);
    }
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/smtpd/restrictions/$instance_id");
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $instance_id=intval($_GET["instance-id"]);

    $html=$tpl->page_header("{ip_reputation} v$POSTFIX_VERSION",
        "fas fa-badge-check",
        "{postfix_ip_reputation_explain}",
        "$page?tabs=yes&instance-id=$instance_id",
        "postfix-rbl",
        "progress-postfix-rbl",false,
        "table-loader-postfix-rbl"
    );



    if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
    if($instance_id==0) {
        $array["{status}"] = "$page?status=yes";
    }
    if($EnablePostfixMultiInstance==1){
        if($instance_id==0) {
            echo $tpl->tabs_default($array);
            return true;
        }
    }
	$array["{public_rbls}"]="$page?table-div=yes&instance-id=$instance_id";
	echo $tpl->tabs_default($array);
    return true;
}

function table_div(){
	$page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);
	echo "<div id='postfix-rbl-div' style='margin-top:20px'></div><script>LoadAjax('postfix-rbl-div','$page?table=yes&instance-id=$instance_id');</script>";
}

function rbl_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 : rbl-delete et id etaient recopies sans encodage dans la page (XSS).
	$ID=intval($_GET["rbl-delete"]);
	$divid=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["id"]);
	$tpl->js_confirm_delete($ID, "rbl-delete", $ID,"$('#$divid').remove()");
}
function rbl_delete(){
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	// Pentest 2026-11-04 : rbl-delete etait concatene dans la requete.
	$rbl=intval($_POST["rbl-delete"]);
	$q->QUERY_SQL("DELETE FROM ip_reputations WHERE ID='$rbl'");
	if(!$q->ok){$q->mysql_error;}
}

function rbl_js(){
	$sock=new sockets();
	$page=CurrentPageName();
	$title="{new_service}";
	$tpl=new template_admin();
	$ID=intval($_GET["rbl-js"]);
	if($ID>0){$title=$_GET["rbl-js"];}
	$instance_id=intval($_GET["instance-id"]);
	$tpl->js_dialog1("{ip_reputation}", "$page?rbl-popup=$ID&instance-id=$instance_id");
}
function rbl_enable(){
	$rbl=intval($_GET["rbl-enable"]);
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled from ip_reputations WHERE ID='$rbl'");
	$enabled=$ligne["enabled"];
	if($enabled==1){$q->QUERY_SQL("UPDATE ip_reputations SET enabled=0 WHERE ID='$rbl'");return;}
	$q->QUERY_SQL("UPDATE ip_reputations SET enabled=1 WHERE ID='$rbl'");
}

function rbl_popup(){
	$t=time();
    $instance_id=intval($_GET["instance-id"]);
	$ID=intval($_GET["rbl-popup"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$data=file_get_contents("ressources/dnsrbl.db");
	$tr=explode("\n",$data);
    foreach ($tr as $val){
		if(preg_match("#RBL:(.+)#",$val,$re)){
			$RBL[$val]=strtolower(trim($re[1]))." ({RBL})";
			
		}
		if(preg_match("#RHSBL:(.+)#",$val,$re)){
			$RBL[$val]=strtolower(trim($re[1]))." ({RHSBL})";
		}
	}
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT * from ip_reputations WHERE ID='$ID'");
    $rbl=$ligne["service"];
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_hidden("instance_id",$instance_id);
	$form[]=$tpl->field_array_hash($RBL, "rbl-choose", "{service2}", $rbl);
	$form[]=$tpl->field_text("rbl", "{create_a_new_one}", null);
	echo $tpl->form_outside("{ip_reputation} {RBL} / {RHSBL}", $form,null,"{add}","dialogInstance1.close();LoadAjax('postfix-rbl-div','$page?table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
	
}

function rbl_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

	$instance_id=intval($_POST["instance_id"]);
	if($_POST["rbl-choose"]<>null){$rbl=$_POST["rbl-choose"];}
	if($_POST["rbl"]<>null){$rbl="RBL:".strtolower(trim($_POST["rbl"]));}
	if($rbl==null){return;}
	// Pentest 2026-11-04 : rbl/rbl-choose etait concatene dans la requete.
	$q->QUERY_SQL_PARAMS("INSERT INTO ip_reputations (service,enabled,instanceid) VALUES (?,1,$instance_id)",array((string)$rbl));
	if(!$q->ok){echo $q->mysql_error;}
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	

    $reconfigure=$tpl->framework_buildjs(
        "postfix:/postfix/smtpd/restrictions/$instance_id",
        "smtpd_client_restrictions_progress",
        "smtpd_client_restrictions_progress.log",
        "progress-postfix-rbl",
        "LoadAjax('postfix-queue-rbl','$page?table=yes&instance-id=$instance_id');");

	

	$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rbl-js=0&instance-id=$instance_id')\">";
    $btn[]="<i class='fa fa-plus'></i> {new_service} </label>";



    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"javascript:$reconfigure;\">";
    $btn[]="<i class='fa fa-save'></i> {apply_configuration} </label>";

    $btn[]="</div>";
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{service2}</th>";
	$html[]="<th class='center'>{type}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='pfxrbl-rows'>";
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

	$results=$q->QUERY_SQL("SELECT * FROM ip_reputations WHERE instanceid=$instance_id");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}

    $EnableRblPack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRblPack")); // feature 016
    // Le bouquet de listes gere en interne: ce n'est pas une entree de la table
    // ip_reputations, d'ou l'absence de suppression.
    $html[]=pfxrbl_row("rbl-pack","{rbl_pack}","{rbl_pack_explain}","{RBL}",
        $tpl->icon_check($EnableRblPack,"Loadjs('$page?rblpack-enable=yes')",null,"AsPostfixAdministrator"),
        $EnableRblPack);




	foreach ($results as $num=>$ligne){
        $ID=$ligne["ID"];
		$service=trim($ligne["service"]);
		$id=md5(serialize($ligne));
		
		if(preg_match("#RBL:(.+)#",$service,$re)){
			$service=strtolower(trim($re[1]));
			$TYPE="{RBL}";
				
		}
		if(preg_match("#RHSBL:(.+)#",$service,$re)){
			$service=strtolower(trim($re[1]));
			$TYPE="{RHSBL}";
		}
		
		
		$actions=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?rbl-enable=$ID&instance-id=$instance_id')",null,"AsPostfixAdministrator")
			.$tpl->icon_delete("Loadjs('$page?rbl-delete=$ID&id=$id&instance-id=$instance_id')","AsPostfixAdministrator");
		$html[]=pfxrbl_row($id,htmlspecialchars($service),"",$TYPE,$actions,intval($ligne["enabled"]));
	}

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span class='awc-pg' id='pfxrbl-pager'></span>";
	$html[]="</div></div></div>";

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{ip_reputation}:{public_rbls} v$POSTFIX_VERSION";
    $TINY_ARRAY["ICO"]="fas fa-badge-check";
    $TINY_ARRAY["EXPL"]="{postfix_ip_reputation_explain}<div id='progress-postfix-rbl'></div>";
    $TINY_ARRAY["URL"]="postfix-rbl-$instance_id";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"pfxrbl-rows","pfxrbl-pager");

}

/**
 * Une liste de reputation: nom du service en identite, explication en sous-titre.
 * Le type (RBL / RHSBL) dit sur quoi porte l'interrogation -- une adresse ou un
 * nom de domaine -- et reste donc une colonne a part entiere.
 */
function pfxrbl_row($id,$service,$explain,$type,$actions,$enabled):string{
	$h ="<tr id='$id'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='".($enabled?"awc-ico":"awc-ico awc-ico-off")."'><i class='fas fa-badge-check'></i></span>";
	$h.="<span><span class='".($enabled?"awc-name":"awc-name awc-name-off")."'>$service</span>";
	if($explain!==""){$h.="<div class='awc-meta'>$explain</div>";}
	$h.="</span></div></td>";
	$h.="<td class='center'><span class='awc-tag'>$type</span></td>";
	$h.="<td><div class='awc-acts'>$actions</div></td>";
	$h.="</tr>";
	return $h;
}

function status(){
	$page=CurrentPageName();
	echo "<div id='rbl-status'></div>
	<script>LoadAjax('rbl-status','$page?rbl-status=yes');</script>";
}
function rbl_status(){	
	$page=CurrentPageName();
	$tpl=new template_admin();



	// Mise en page en flex. Le <table> exterieur n'etait jamais ferme (ni </td>
	// ni </tr> ni </table> apres la derniere cellule).
	$html[]="<div style='width:99%;margin-top:20px'>";
	$html[]="<div style='padding:6px 0'><div id='progress-postfix-rbl'></div></div>";
	$html[]="<div><div id='status-top'></div></div>";

    $widget_metrics_rbl=widget_metrics_rbl();
    $widget_wbl=widget_wbl();
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start'>";
    $html[]="<div style='flex:1 1 280px;min-width:0'>$widget_metrics_rbl</div>";
    $html[]="<div style='flex:1 1 280px;min-width:0'>$widget_wbl</div>";
    $html[]="</div>";
    $html[]="</div>";

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{ip_reputation} {status} v$POSTFIX_VERSION";
    $TINY_ARRAY["ICO"]="fas fa-badge-check";
    $TINY_ARRAY["EXPL"]="{postfix_ip_reputation_explain}";
    $TINY_ARRAY["URL"]="postfix-rbl";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $jsrefresh=$tpl->RefreshInterval_js("status-top",$page,"status-top=yes");

    $html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
    $html[]="<script>";
    $html[]=$jsrefresh;
    $html[]="$jstiny</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function status_top():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $widget_public_rbls=widget_public_rbls();
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start'>";
    $html[]="<div style='flex:1 1 340px;min-width:0'>$widget_public_rbls</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}


function widget_public_rbls():string{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $EnableRblPack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRblPack")); // feature 016
    // feature 016 — one-click curated RBL Pack toggle (reflects the stored EnableRblPack state on load)

    if($EnableRblPack==1){

        $btn["ico"]=ico_check;
        $btn["name"]="{disable}";
        $btn["js"]="Loadjs('$page?rblpack-enable=yes')";

        return $tpl->widget_h("minheight:169px:green", "fas fas fa-database",
            "{active2}",
            "{rbl_pack}",
            $btn);
    }



    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $CountRBL=$q->COUNT_ROWS("ip_reputations");
    $btn=array();
    if($CountRBL==0){

        $btn["ico"]=ico_cd;
        $btn["name"]="{enable}";
        $btn["js"]="Loadjs('$page?rblpack-enable=yes')";

        return $tpl->widget_h("minheight:169px:grey", "fas fas fa-database",
            "{inactive}",
            "{public_rbls}",
            $btn);
    }
    return $tpl->widget_h("minheight:169px:green", "fas fas fa-database",
            $CountRBL,
            "{nbs_of_rbls}",
            $btn);


}
function widget_metrics_rbl():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $CountRBL=$q->COUNT_ROWS("ip_reputations");

    if($CountRBL==0){
        return $tpl->widget_h("minheight:169px:grey","fas fa-ban",0,
            "{public_rbls} {rejected_messages}");
    }


    $CountOFRBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CountOfRBLThreats"));

    if($CountOFRBL==0){
        return $tpl->widget_h("minheight:169px:grey","fas fa-ban",0,"{public_rbls} {rejected_messages}");
    }


    $CountOFRBL=FormatNumber($CountOFRBL);
    return $tpl->widget_h("minheight:169px:red","fas fa-ban",$CountOFRBL,"{public_rbls} {rejected_messages}");


}

function widget_wbl(){
    $EnableDNSWL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSWL"));
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btn=array();

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        return $tpl->widget_h("minheight:169px:grey",ico_check,"{license_error}","{public_whitelist_database}");
    }

    if($EnableDNSWL==0){
        $btn["ico"]="far fa-shield-check";;
        $btn["name"]="{activate}";
        $btn["js"]="Loadjs('$page?EnableDNSWL-js=yes');";
        return $tpl->widget_h("minheight:169px:grey",ico_check,
            "{inactive2}","{public_whitelist_database}",$btn);
    }

    $btn["ico"]="fas fa-badge";;
    $btn["name"]="{disable}";
    $btn["js"]="Loadjs('$page?EnableDNSWL-js=yes');";
    return $tpl->widget_h("minheight:169px:green",ico_check,
        "{active2}","{public_whitelist_database}",$btn);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

