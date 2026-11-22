<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");
// awc_table_style()/awc_pager_js()/awc_search_bar()/awc_filter_js(): kit des tableaux
// d'entites. Inclusion INCONDITIONNELLE au premier niveau -- placee dans un bloc de
// bootstrap elle ne s'executerait jamais et la page mourrait sur
// « Call to undefined function awc_table_style() ».
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbrules.meta.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["link-group-js"])){link_group_js();exit;}
if(isset($_GET["link-group-popup"])){link_group_popup();exit;}
if(isset($_GET["link-group-choose"])){link_group_choose();exit;}
if(isset($_GET["link-group-step2"])){link_group_choose_step2();exit;}
if(isset($_POST["link1"])){link_group_choose_save();exit;}
if(isset($_POST["link2"])){link_group_choose_final();exit;}
if(isset($_GET["unlink-js"])){group_rmunlink();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["none"])){exit();}
if(isset($_GET["link-dump-js"])){link_dump_js();exit;}
if(isset($_GET["link-dump-popup"])){link_dump_popup();exit;}

if(isset($_GET["link-dump-debug-js"])){link_dump_debug_js();exit;}
if(isset($_GET["link-dump-debug-popup"])){link_dump_debug_popup();exit;}
if(isset($_GET["link-dump-debug-perform"])){link_dump_debug_perform();exit;}
if(isset($_GET["source-edit-ad-js"])){source_edit_ad_js();exit;}
if(isset($_GET["source-edit-ad-start"])){source_edit_ad_start();exit;}
if(isset($_GET["source-edit-ad-popup"])){source_edit_ad_popup();exit;}
if(isset($_GET["source-edit-ad-ssl"])){source_edit_ad_ssl();exit;}
if(isset($_GET["source-edit-ad-account-js"])){source_edit_ad_account_js();exit;}
if(isset($_GET["source-edit-ad-account-popup"])){source_edit_ad_account_popup();exit;}
if(isset($_POST["editadaccount"])){source_edit_ad_account_save();exit;}
js();

function source_edit_ad_account_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["source-edit-ad-account-js"]);
    return $tpl->js_dialog9("{sources} #$ID {account}", "$page?source-edit-ad-account-popup=$ID",550);
}
function source_edit_ad_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["source-edit-ad-js"]);
    return $tpl->js_dialog8("{sources} #$ID", "$page?source-edit-ad-start=$ID",750);
}
function source_edit_ad_start():bool{
    $ID=intval($_GET["source-edit-ad-start"]);
    $page=CurrentPageName();
    echo "<div id='source-edit-ad-$ID'></div><script>LoadAjaxSilent('source-edit-ad-$ID','$page?source-edit-ad-popup=$ID');</script>";
    return true;
}

function js():bool{
	$ID=intval($_GET["ID"]);
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	return $tpl->js_dialog("{sources} $rulename", "$page?popup=$ID");
}
function link_group_js():bool{
	$ID=intval($_GET["link-group-js"]);
	if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_refuse_js(); }
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	return $tpl->js_dialog("{link_group} $rulename", "$page?link-group-popup=$ID");
}
function link_dump_js():bool{
	$ID=intval($_GET["link-dump-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	return $tpl->js_dialog2("$rulename {members}", "$page?link-dump-popup=$ID");
	
}
function link_dump_debug_js():bool{
    $ID=intval($_GET["link-dump-debug-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
    $ligne=$q->mysqli_fetch_array($sql);
    $rulename=$ligne["groupname"];
    return $tpl->js_dialog2("$rulename {members}", "$page?link-dump-debug-popup=$ID",990);
}
function link_dump_debug_popup():bool{
    $ID=intval($_GET["link-dump-debug-popup"]);
    $page=CurrentPageName();
    echo "<div id='link-dump-debug-$ID'></div>";
    echo "<script>LoadAjax('link-dump-debug-$ID','$page?link-dump-debug-perform=$ID');</script>";
    return true;
}
function link_dump_debug_perform():bool{
    $ID=intval($_GET["link-dump-debug-perform"]);
    $sock=new sockets();
    $sock->REST_API_TIMEOUT=30;
    $tpl=new template_admin();
    $json=json_decode($sock->REST_API("/ufdb/debug/userdump/$ID"),true);

    if(!isset($json["Result"])){
        echo $tpl->div_error("{error}||{protocol_error}");
        return true;
    }

    if(!isset($json["Debug"])){
        echo $tpl->div_error("{error}||You should never hit that!");
        return true;
    }

    echo "<textarea style='
width:100%;
height:650px;
background:#0f172a;
color:#e5e7eb;

font-size:13px;
line-height:1.5;
padding:12px;
border:1px solid #1e293b;
border-radius:8px;
resize:vertical;
outline:none;
overflow:auto;
white-space:pre;
tab-size:2;
box-shadow:inset 0 0 10px rgba(0,0,0,0.4);
caret-color:transparent;white-space: pre;
'>{$json["Debug"]}</textarea>";

    return true;
}



function link_dump_popup(){
    $ID=intval($_GET["link-dump-popup"]);
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userdump/$ID"),true);

    if(!isset($json["Users"]) || !is_array($json["Users"])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{protocol_error}"));
        return false;
    }

    $rows=array();
    foreach($json["Users"] as $member){
        // Comparaison STRICTE a la chaine vide: « $member==null » etait vrai pour "0"
        // en PHP 7, si bien qu'un identifiant valant zero disparaissait du dump.
        $member=trim((string)$member);
        if($member===""){ continue; }
        $member=urldecode($member);
        // Le nom vient du daemon, qui le tient de l'annuaire: c'est du texte qu'un tiers
        // controle et qui arrivait tel quel dans le HTML.
        $rows[]="<tr><td><div class='awc-ident' style='gap:10px'>"
            ."<span class='awc-ico' style='width:30px;height:30px;font-size:13px'>"
            ."<i class='fas fa-user-shield'></i></span>"
            ."<span class='awc-name' style='font-size:13px'>".htmlspecialchars($member)."</span>"
            ."</div></td></tr>";
    }
    $n=count($rows);

    $html=array();
    $html[]=awc_search_bar("ufdbd-$ID-search","{search}");
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr><th>{members}</th></tr></thead>";
    $html[]="<tbody id='ufdbd-$ID-rows'>";
    if($n==0){
        // La ligne « vide » vit DANS le tableau: rendue a sa place, l'en-tete de colonne
        // disparaitrait et la modale changerait de forme selon qu'il y a ou non un membre.
        $html[]="<tr><td><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n {members}</span>"
           ."<span class='awc-pg' id='ufdbd-$ID-pager'></span></div>";
    $html[]="</div></div>";
    $html[]="<script>if(typeof NoSpinner==='function'){NoSpinner();}</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(100,"ufdbd-$ID-rows","ufdbd-$ID-pager")
        .awc_filter_js("ufdbd-$ID-search","ufdbd-$ID-rows","ufdbd-$ID-pager");
    return true;
}


function group_rmunlink():bool{
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!ufdbs_require_admin()){
		echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return false;
	}
	$ID=intval($_GET["unlink-js"]);
	$ruleid=isset($_GET["ruleid"])?intval($_GET["ruleid"]):0;
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	// Verrou Artica Meta : la regle ET le groupe du lien sont relus en base (pas l'URL).
	$assoc=$q->QUERY_SQL_PARAMS("SELECT webfilter_id,group_id FROM webfilter_assoc_groups WHERE ID=?",array($ID));
	if(!$q->ok){ return ufdbrules_meta_refuse_js(); }
	if(isset($assoc[0])){
		if(ufdbrules_meta_locked_rule(intval($assoc[0]["webfilter_id"])) || ufdbrules_meta_locked_group(intval($assoc[0]["group_id"]))){
			return ufdbrules_meta_refuse_js();
		}
	}
	$q->QUERY_SQL_PARAMS("DELETE FROM webfilter_assoc_groups WHERE ID=?",array($ID));
	if(!$q->ok){
		writelogs("group_rmunlink($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return false;
	}
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userclear");
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
	echo ufdbs_reload($ruleid);
	return admin_tracks("Web-Filtering: Unlink #$ID Group");
}

function delete_js(){
	$tpl=new template_admin();
	if(!ufdbs_require_admin()){ $tpl->js_no_privileges(); return; }
	$ruleid=intval($_GET["ruleid"]);
	$gpid=intval($_GET["gpid"]);
	$assoc=intval($_GET["delete-js"]);
	if(ufdbrules_meta_locked_rule($ruleid) || ufdbrules_meta_locked_group($gpid)){ ufdbrules_meta_refuse_js(); return; }
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$page=CurrentPageName();

	$rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_rules WHERE ID=?",array($ruleid));
	$rulename=isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$ruleid";
	$rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_group WHERE ID=?",array($gpid));
	$GroupName=isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$gpid";

	// js_confirm_delete() insere ce texte en HTML (puis l'encode en base64, donc les
	// guillemets ne cassent pas le JavaScript) -- mais un nom porteur de balises y serait
	// rendu comme du balisage. Les deux viennent de la base, pas du moteur de traduction:
	// les echapper est ici correct.
	$GroupName=htmlspecialchars($GroupName);
	$rulename=htmlspecialchars($rulename);
	$text="{delete_group} $GroupName {in_database} <br>{and} $GroupName {in_rule} $rulename<br>{and} {in_all_rules} ?";
	$jsafter="Loadjs('$page?delete-confirm=$assoc&ruleid=$ruleid&gpid=$gpid')";
	$tpl->js_confirm_delete($text,"none","none",$jsafter,true);
}

function delete_confirm():bool{
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	// Cette action retire le groupe de TOUTES les regles et le supprime de la base:
	// elle repondait a n'importe quelle session authentifiee.
	if(!ufdbs_require_admin()){
		echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return false;
	}
	$ruleid=intval($_GET["ruleid"]);
	$gpid=intval($_GET["gpid"]);
	if($gpid<=0){ echo "alert(".json_encode($tpl->javascript_parse_text("{error}")).");"; return false; }
	// Verrou Artica Meta : groupe Meta, ou groupe lie a une regle Meta, jamais supprime ici.
	if(ufdbrules_meta_locked_group($gpid) || ufdbrules_meta_locked_rule($ruleid) || ufdbrules_meta_group_in_meta_rule($gpid)){
		echo "alert(".json_encode(ufdbrules_meta_text()).");"; return false;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	foreach(array(
		"DELETE FROM webfilter_assoc_groups WHERE group_id=?",
		"DELETE FROM webfilter_group WHERE ID=?",
	) as $sql){
		$q->QUERY_SQL_PARAMS($sql,array($gpid));
		if(!$q->ok){
			writelogs("delete_confirm($gpid): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
			echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return false;
		}
	}
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userclear");
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
	echo ufdbs_reload($ruleid);
	return admin_tracks("Web-Filtering : Unlink #$gpid Group from #$ruleid web-filtering rule");
}



function popup():bool{
	$page=CurrentPageName();
	$ID=intval($_GET["popup"]);
    // RefreshTable NE VOYAGE PLUS. C'etait un base64 de JavaScript passe dans l'URL,
    // que fw.ufdb.group.php decodait et inserait dans le $jsafter de form_outside --
    // donc EXECUTAIT. Chaque page decide desormais elle-meme de ce qu'elle recharge.
    echo "<div id='ufdb-rules-source'></div><script>LoadAjaxSilent('ufdb-rules-source','$page?table=$ID');</script>";
    return true;
}
function link_group_popup():bool{
	$page=CurrentPageName();
	$ID=intval($_GET["link-group-popup"]);
	echo "<div id='ufdb-rules-source-choose'></div><script>LoadAjaxSilent('ufdb-rules-source-choose','$page?link-group-choose=$ID');</script>";
    return true;
}
function link_group_choose():bool{
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["link-group-choose"]);
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($Enablehacluster==1){$EnableKerbAuth=1;}
    if($EnableActiveDirectoryFeature==1){
        $EnableKerbAuth=1;
    }
	$_SESSION["UFDB_CHOOSE_GROUP"]=array();

	if($EnableOpenLDAP==1){$zlocalldap[0]="{ldap_group}";}
	$zlocalldap[1]="{virtual_group}";
	if($EnableKerbAuth==1){
		$zlocalldap[2]="{active_directory_group}";
        $zlocalldap[5]="{active_directory_ou}";
	}
	$zlocalldap[3]="{remote_ladp_group}";
	$localldap[0]="{ldap_group}";
	$localldap[1]="{virtual_group}";
	$localldap[2]="{active_directory_group}";
	$localldap[3]="{remote_ladp_group}";
	$localldap[4]="{active_directory_group} ({other})";
    $localldap[5]="{active_directory_ou}";
	
	$sql="SELECT *  FROM `webfilter_group` order by groupname";
	$results = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
	$GROUPS=array();
    foreach ($results as $index=>$ligne){
        VERBOSE("$index=>{$ligne["ID"]}",__LINE__);
		$groupid=$ligne['ID'];
		if(preg_match("#ExtLDAP:(.+?):#", $ligne["groupname"],$re)){$ligne["groupname"]=$re[1];}
		$typeexplain=$tpl->_ENGINE_parse_body($localldap[$ligne["localldap"]]);
		$GROUPS[$groupid]="{$ligne["groupname"]} ($typeexplain)";
	}
	$Form[]=$tpl->field_hidden("link1", $ID);
	if(count($GROUPS)>0){
		$Form[]=$tpl->field_array_hash($GROUPS, "choosen","{choose_a_source}");
	}
	$Form[]=$tpl->field_array_hash($zlocalldap, "localldap", "{new_source}", null);
	
	echo $tpl->form_outside("$rulename {link_group}", @implode("\n", $Form),null,"{next}","LoadAjaxSilent('ufdb-rules-source-choose','$page?link-group-step2=$ID');");
	return true;

}

function link_group_choose_step2():bool{
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
    $form_explain="";
	$ID=intval($_GET["link-group-step2"]);
	// Verrou Artica Meta : pas de lien sur une regle Meta, ni de groupe Meta sur une regle locale.
	if(ufdbrules_meta_locked_rule($ID)){ echo $tpl->div_error(ufdbrules_meta_text()); return false; }
	if(isset($_SESSION["UFDB_CHOOSE_GROUP"]["choosen"]) && ufdbrules_meta_locked_group(intval($_SESSION["UFDB_CHOOSE_GROUP"]["choosen"]))){
		echo $tpl->div_error(ufdbrules_meta_text()); return false;
	}

	$js[]="BootstrapDialog1.close()";
	$js[]="LoadAjaxSilent('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";
	$js[]="LoadAjaxSilent('ufdb-rules-source','$page?table=$ID');";
	
	
	if(isset($_SESSION["UFDB_CHOOSE_GROUP"]["choosen"])){
		$groupid=intval($_SESSION["UFDB_CHOOSE_GROUP"]["choosen"]);
        if($groupid>0) {
            $md5 = md5("$ID$groupid");
            $sql = "INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$ID','$groupid')";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                // mysql_error_html() renvoyait la REQUETE elle-meme au navigateur.
                writelogs("webfilter_assoc_groups: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
                echo "{database_error}";
                return false;
            }
            echo "<script>" . @implode(";\n", $js) . "</script>";
            return false;
        }
	}
	
	
	
	
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$localldap_f=$_SESSION["UFDB_CHOOSE_GROUP"]["localldap"];
	$localldap[0]="{ldap_group}";
	$localldap[1]="{virtual_group}";
	$localldap[2]="{active_directory_group}";
	$localldap[3]="{remote_ladp_group}";
	$localldap[4]="{active_directory_group} ({other})";
    $localldap[5]="{active_directory_ou}";
	$typeexplain=$tpl->_ENGINE_parse_body($localldap[$localldap_f]);
	
	$form[]=$tpl->field_hidden("link2", $ID);
	$form[]=$tpl->field_hidden("localldap", $localldap_f);



	if($localldap_f==0){
        $form_explain="{ufdb_localldap_0}";
        $form[]=$tpl->field_ldap_group("groupid", "{ldap_group}",
            null,true,null,false,true);
    }
	
	if($localldap_f==2){
		$form_explain="{ufdb_localldap_2}";
		$form[]=$tpl->field_activedirectorygrp("groupid", "{activedirectory_group}",
            null,true,null,false,true);
	}

    if($localldap_f==3){
        $form_explain="{ufdb_localldap_3}";
        // Sous la form de ExtLDAP:GroupName:DN
        $form[]=$tpl->field_remote_ldap_groups("groupid", "{remote_ladp_group}",
            null,true,null,false,true);
    }
	
	if($localldap_f==1){
		$form_explain="{group_explain_proxy_acls_type_1}";
		$form[]=$tpl->field_text("groupid", "{groupname}", null,true,null,false,true);
	}

    if($localldap_f==5){
        $form_explain="{group_explain_proxy_acls_type_5}";
        $form[]=$tpl->field_text("groupid", "{organization}", null,true,null,false,true);
    }


    echo $tpl->form_outside("$rulename &raquo; {source} &raquo; $typeexplain", @implode("\n", $form),$form_explain,"{link}",@implode(";", $js),"AsDansGuardianAdministrator");
	return true;
	
}

function link_group_choose_final():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$webfilter_ruleid=intval($_POST["link2"]);
	if(ufdbrules_meta_locked_rule($webfilter_ruleid)){ return ufdbrules_meta_refuse_post(); }
	$localldap=intval($_POST["localldap"]);
	$groupid=$_POST["groupid"];



    if($localldap==5){
        $ouName=$groupid;
        $ad=new external_ad_search();
        $Ous=$ad->active_directory_ListOus($ouName);
        if(!$Ous){
            echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("{unable_to_find_ou}")." $ouName<br>$ad->error");
            return false;
        }

        // Pentest 2026-11-04 : le nom du groupe (POST/AD) etait concatene dans la requete.
        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) VALUES (?,?,1,?,?,'')";
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $q->QUERY_SQL_PARAMS($sql_add,array($ouName,$ouName,$localldap,"Organization Unit $ouName"));
        if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo (new template_admin())->post_error("$q->mysql_error");return false;}

        $groupid=$q->last_id;
        if($groupid==0){echo (new template_admin())->post_error("Unable to find the Next ID!!");return false;}

        $md5=md5("$webfilter_ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return false;}
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userclear");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
        return admin_tracks("Link $ouName to group #$groupid and rule #$groupid");

    }
	
	if($localldap==2){
		$dn=$groupid;
		$ad=new external_ad_search();

		$ARRAY=$ad->FindParametersByDN($dn);
		if($ARRAY["samaccountname"]==null){
			echo (new template_admin())->post_error("samaccountname not found");
			return false;
		}
		$settings=base64_encode(serialize($ARRAY));
		$groupname=$ARRAY["samaccountname"];
		$description=$ARRAY["description"];
		//$ldap_server=$ARRAY["LDAP_SERVER"].":".$ARRAY["LDAP_PORT"];
		//recherche la source via le DN...
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		// Pentest 2026-11-04 : samaccountname/description/dn (AD) etaient concatenes dans la requete.
		$sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) VALUES (?,?,1,?,?,?)";

		$q->QUERY_SQL_PARAMS($sql_add,array($settings,$groupname,$localldap,$description,$dn));

		if(!$q->ok){echo $q->mysql_error;return false;}
		$groupid=$q->last_id;
		if($groupid==0){echo "Unable to find the Next ID!!\n";return false;}
		
		$md5=md5("$webfilter_ruleid$groupid");
		$sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return false;}
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userclear");
        return admin_tracks("Link AD $groupname to group #$groupid and rule #$groupid");
	}	
	
	if($localldap==1){
		$groupname=$groupid;
		// Pentest 2026-11-04 : le nom du groupe (POST) etait concatene dans la requete.
		$sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) 
		VALUES ('',?,1,?,'{virtual_group}','')";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$q->QUERY_SQL_PARAMS($sql_add,array($groupname,$localldap));
		if(!$q->ok){echo $q->mysql_error;return false;}
		$groupid=$q->last_id;
		if($groupid==0){echo "Unable to find the Next ID!!\n";return false;}
		$md5=md5("$webfilter_ruleid$groupid");
		$sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return false;}
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userclear");
        return admin_tracks("Link Virtual group $groupname to group #$groupid and rule #$groupid");
		
	}
    if($localldap==3){

        if(!preg_match("#ExtLDAP:(.+?):(.+)#", $groupid,$re)){
            if(function_exists("WLOG")){WLOG("Wrong ACL pattern $groupid");}
            echo (new template_admin())->post_error("Wrong ACL pattern $groupid");
            return false;
        }

        $groupname=$groupid;
        // Pentest 2026-11-04 : le nom du groupe (POST/AD) etait concatene dans la requete.
        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) 
		VALUES ('',?,1,?,'{remote_ladp_group}','')";
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $q->QUERY_SQL_PARAMS($sql_add,array($groupname,$localldap));
        if(!$q->ok){echo $q->mysql_error;return false;}
        $groupid=$q->last_id;
        if($groupid==0){echo "Unable to find the Next ID!!\n";return false;}
        $md5=md5("$webfilter_ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return false;}
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userclear");
        return admin_tracks("Link ExtLDAP group $groupname to group #$groupid and rule #$groupid");

    }

    if($localldap==0){
        $groupname=$groupid;
        // Pentest 2026-11-04 : le nom du groupe (POST/AD) etait concatene dans la requete.
        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`,gpid) 
		VALUES ('',?,1,?,'{ldap_group}','',?)";
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $q->QUERY_SQL_PARAMS($sql_add,array($groupname,$localldap,intval($groupid)));
        if(!$q->ok){echo $q->mysql_error;return false;}
        $groupid=$q->last_id;
        if($groupid==0){echo "Unable to find the Next ID!!\n";return false;}
        $md5=md5("$webfilter_ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return false;}
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userclear");
        return admin_tracks("Link Local LDAP group $groupname to group #$groupid and rule #$groupid");

    }
	return false;
}

function link_group_choose_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(ufdbrules_meta_locked_rule(intval($_POST["link1"]))){ return ufdbrules_meta_refuse_post(); }
	if(isset($_POST["choosen"]) && ufdbrules_meta_locked_group(intval($_POST["choosen"]))){ return ufdbrules_meta_refuse_post(); }
	$_SESSION["UFDB_CHOOSE_GROUP"]["localldap"]=$_POST["localldap"];
	$_SESSION["UFDB_CHOOSE_GROUP"]["ruleid"]=$_POST["link1"];
	if(isset($_POST["choosen"])){$_SESSION["UFDB_CHOOSE_GROUP"]["choosen"]=$_POST["choosen"];}
	return true;
}

function source_edit_ad_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ID=intval($_GET["source-edit-ad-popup"]);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilter_group WHERE ID=$ID");
    $settingsEnc=unserialize(base64_decode($ligne["settings"]));
    $ADNETIPADDR="";
    $UPDATE=false;
    if(isset($settingsEnc["ADNETIPADDR"])){
        $vl=$settingsEnc["ADNETIPADDR"];
        if(preg_match("#^0\.0\.0#",$vl)){
            $settingsEnc["ADNETIPADDR"]="";
            $vl="";
            $UPDATE=true;
        }
        if(strlen($vl)>2){
            $ADNETIPADDR="<br><i><small>{$settingsEnc["ADNETIPADDR"]}</small></i>";
        }
    }
    if(!isset($settingsEnc["LDAP_SSL"])){
        $settingsEnc["LDAP_SSL"]=0;
    }
    $LDAP_SSL=intval($settingsEnc["LDAP_SSL"]);
    if($LDAP_SSL==1){
        if($settingsEnc["LDAP_PORT"]==389) {
            $settingsEnc["LDAP_PORT"] = 636;
            $UPDATE=true;
        }
    }
    $tpl->table_form_field_text("ID",$ligne["ID"],ico_params);
    $tpl->table_form_field_bool("{enabled}",$ligne["enabled"],ico_check);
    $tpl->table_form_field_text("{groupname}",$ligne["groupname"]."<br><small style='text-transform:none;font-size:12px'><i>{$ligne["description"]}</i></small>",ico_users);
    $tpl->table_form_field_text("DN","<small style='text-transform:none;font-size:12px'>{$settingsEnc["dn"]}</small><br><i>{$settingsEnc["samaccountname"]}</i>",ico_microsoft);
    $tpl->table_form_field_text("{activedirectory_server}",$settingsEnc["LDAP_SERVER"].":".$settingsEnc["LDAP_PORT"].$ADNETIPADDR,ico_server);

    $tpl->table_form_field_js("Loadjs('$page?source-edit-ad-account-js=$ID','AsDansGuardianAdministrator');");
    $tpl->table_form_field_text("{username}",$settingsEnc["LDAP_DN"],ico_admin);

    $tpl->table_form_field_js("Loadjs('$page?source-edit-ad-ssl=$ID','AsDansGuardianAdministrator');");
    $tpl->table_form_field_bool("{useSSL}",$LDAP_SSL,ico_ssl);
    echo $tpl->table_form_compile();
    
    // Groupe Meta : jamais reecrit ici (l'import du noeud le tient).
    if($UPDATE && !ufdbrules_meta_locked_group($ID)){
        $newserialize=base64_encode(serialize($settingsEnc));
        $q->QUERY_SQL("UPDATE webfilter_group SET settings='$newserialize' WHERE ID=$ID");
        if(!$q->ok){
            echo $tpl->div_error($q->mysql_error);
        }
    }
   
    return true;
}
function source_edit_ad_account_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["source-edit-ad-account-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT settings FROM webfilter_group WHERE ID=$ID");
    $settingsEnc=unserialize(base64_decode($ligne["settings"]));
    $form[]=$tpl->field_hidden("editadaccount",$ID);
    $form[]=$tpl->field_text("username","{username}",$settingsEnc["LDAP_DN"]);
    $form[]=$tpl->field_password2("password","{password}","",true);
    echo $tpl->form_outside("",$form,"","{apply}",
        "dialogInstance9.close();LoadAjaxSilent('source-edit-ad-$ID','$page?source-edit-ad-popup=$ID');","AsDansGuardianAdministrator");
    return true;
}
function source_edit_ad_account_save():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["editadaccount"]);
    if(ufdbrules_meta_locked_group($ID)){ return ufdbrules_meta_refuse_post(); }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT settings FROM webfilter_group WHERE ID=$ID");
    if(!isset($ligne["settings"])){echo (new template_admin())->post_error("source #$ID not found");return false;}
    $settingsEnc=unserialize(base64_decode($ligne["settings"]));
    if(!is_array($settingsEnc)){echo (new template_admin())->post_error("source #$ID: unable to read the stored connection");return false;}

    $username=trim($_POST["username"] ?? "");
    $password=trim($_POST["password"] ?? "");
    if($username==null){echo (new template_admin())->post_error("{username}: {error}");return false;}

    $settingsEnc["LDAP_DN"]=$username;
    $settingsEnc["WINDOWS_SERVER_ADMIN"]=$username;
    // Mot de passe vide = "je ne change que le compte": l'ecraser viderait la
    // seule copie du secret utilisee pour le bind (webfilter_group.settings
    // est un instantane, il n'est jamais relu depuis ActiveDirectoryConnections).
    if($password<>null){
        $settingsEnc["LDAP_PASSWORD"]=$password;
        $settingsEnc["WINDOWS_SERVER_PASS"]=$password;
    }

    // SANS CETTE REECRITURE la fonction ne faisait QUE vider les caches et
    // recompiler -- avec les ANCIENNES credentials -- puis repondait succes:
    // le compte affiche ne changeait jamais, ufdbguardd continuait a binder
    // avec l'ancien compte de service ("AD bind failed: LDAP Result Code 49"
    // dans /var/log/squid/ufdbguardd.log, verrouillage du compte cote AD), et
    // le seul contournement etait de supprimer/recreer la source.
    // Cf. source_edit_ad_ssl() juste en dessous, qui a toujours ecrit, lui.
    $newserialize=base64_encode(serialize($settingsEnc));
    $q->QUERY_SQL("UPDATE webfilter_group SET settings='$newserialize' WHERE ID=$ID");
    if(!$q->ok){echo (new template_admin())->post_error("{$q->mysql_error}");return false;}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userclear");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    return admin_tracks("Change Active Directory Credentials to $username for Web-filtering rule ID $ID");

}
function source_edit_ad_ssl(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["source-edit-ad-ssl"]);
    if(ufdbrules_meta_locked_group($ID)){ return ufdbrules_meta_refuse_js(); }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT settings FROM webfilter_group WHERE ID=$ID");
    $settingsEnc=unserialize(base64_decode($ligne["settings"]));
    $SSL=intval($settingsEnc["LDAP_SSL"] ?? 0);
    if($SSL==0){
        $newssl=1;
        $settingsEnc["LDAP_SSL"]=1;
        $settingsEnc["LDAP_PORT"]=636;


    }else{
        $newssl=0;
        $settingsEnc["LDAP_SSL"]=0;
        $settingsEnc["LDAP_PORT"]=389;
    }
    $newserialize=base64_encode(serialize($settingsEnc));
    $q->QUERY_SQL("UPDATE webfilter_group SET settings='$newserialize' WHERE ID=$ID");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userclear");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    echo "LoadAjaxSilent('source-edit-ad-$ID','$page?source-edit-ad-popup=$ID');";
    return admin_tracks("Switch Active Directory SSL to $newssl for Web-filtering rule ID $ID");
}

/** ufdbs_require_admin — le garde SERVEUR des trois ecritures de cette page. */
function ufdbs_require_admin(){
    $users=new usersMenus();
    return $users->AsDansGuardianAdministrator?true:false;
}

/** ufdbs_reload — le rechargement renvoye a Loadjs apres une mutation. */
function ufdbs_reload($ruleid){
    $page=CurrentPageName();
    $ruleid=intval($ruleid);
    // Rechargement du fragment plutot que « $('#<zMD5 venu de l URL>').remove() »: cet
    // identifiant arrivait brut du navigateur dans du JavaScript emis, et retirer la
    // ligne a la main laissait le compteur du pied sur l'ancien total.
    return "if(document.getElementById('ufdb-rules-source')){"
        ."LoadAjaxSilent('ufdb-rules-source','$page?table=$ruleid');}\n"
        ."if(document.getElementById('table-loader-ufdbrules-service')){"
        ."LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');}\n";
}

/**
 * ufdbs_source_row — une source (groupe d'utilisateurs) rattachee a la regle.
 *
 * $name et $description arrivent DEJA prets: selon la branche, le nom est un lien
 * fabrique par td_href() et la description sort parfois de htmlentities(). C'est
 * l'appelant qui sait laquelle des six natures de groupe il tient.
 */
function ufdbs_source_row($assocID,$groupID,$ruleid,$name,$description,$type,$icon,$members,$enabled,$extra,$canEdit,$canWrite){
    $page=CurrentPageName();
    $assocID=intval($assocID); $groupID=intval($groupID); $ruleid=intval($ruleid);
    $ico=$enabled?"awc-ico":"awc-ico awc-ico-off";
    $nameCls=$enabled?"awc-name":"awc-name awc-name-off";

    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='$ico'><i class='fas $icon'></i></span>";
    $h.="<span><span class='$nameCls'>$name</span>";
    // L'etat « desactive » etait CALCULE (une couleur grise) puis jamais utilise: une
    // source coupee s'affichait a l'identique d'une source active.
    if(!$enabled){ $h.=" <span class='awc-badge awc-badge-off'>{disabled}</span>"; }
    if($description!==""){ $h.="<div class='awc-meta'>$description</div>"; }
    if($extra!==""){ $h.="<div class='awc-meta'>$extra</div>"; }
    $h.="</span></div></td>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-tag'>$type</span></td>";
    $h.="<td class='center' style='width:1%' nowrap>"
       .($members===""?"<span class='awc-muted'>&mdash;</span>"
                      :"<span class='awc-seen'>$members</span><div class='awc-meta'>{members}</div>")
       ."</td>";

    $a=array();
    if($canEdit){
        $a[]="<a class='awc-act' title=\"{edit}\" href=\"javascript:blur();\" "
            ."OnClick=\"Loadjs('$page?source-edit-ad-js=$groupID')\"><i class='fas fa-pen'></i></a>";
    }
    if($canWrite){
        $a[]="<a class='awc-act' title=\"{unlink}\" href=\"javascript:blur();\" "
            ."OnClick=\"Loadjs('$page?unlink-js=$assocID&ruleid=$ruleid')\"><i class='fas fa-link-slash'></i></a>";
        $a[]="<a class='awc-act awc-act-danger' title=\"{delete}\" href=\"javascript:blur();\" "
            ."OnClick=\"Loadjs('$page?delete-js=$assocID&gpid=$groupID&ruleid=$ruleid')\"><i class='fas fa-trash'></i></a>";
    }
    if(count($a)==0){ $a[]="&nbsp;"; }
    $h.="<td style='width:1%' nowrap><div class='awc-acts'>".implode("",$a)."</div></td>";
    $h.="</tr>";
    return $h;
}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ID=intval($_GET["table"]);
    // QuotaID partait CONCATENE dans la requete alternative.
    $QuotaID=isset($_GET["QuotaID"])?intval($_GET["QuotaID"]):0;
    $canWrite=ufdbs_require_admin();
    // Regle Artica Meta : sources affichees, ni liees ni retirees ni modifiees ici.
    $ruleLocked=ufdbrules_meta_locked_rule($ID);
    if($ruleLocked){ $canWrite=false; }

    if($QuotaID>0){
        $sql="SELECT webfilter_assoc_quota_groups.ID,webfilter_assoc_quota_groups.webfilter_id,
            webfilter_group.groupname,webfilter_group.description,webfilter_group.gpid,
            webfilter_group.localldap,webfilter_group.ID as webfilter_group_ID,
            webfilter_group.dn as webfilter_group_dn,webfilter_group.settings as settings,
            webfilter_group.enabled
            FROM webfilter_group,webfilter_assoc_quota_groups
            WHERE webfilter_assoc_quota_groups.webfilter_id=? AND webfilter_assoc_quota_groups.group_id=webfilter_group.ID
            ORDER BY webfilter_group.groupname";
        $bind=$QuotaID;
    }else{
        $sql="SELECT webfilter_assoc_groups.ID,webfilter_assoc_groups.zMD5,webfilter_assoc_groups.webfilter_id,
            webfilter_group.groupname,webfilter_group.description,webfilter_group.gpid,
            webfilter_group.localldap,webfilter_group.ID as webfilter_group_ID,
            webfilter_group.dn as webfilter_group_dn, webfilter_group.settings as settings,
            webfilter_group.enabled
            FROM webfilter_group,webfilter_assoc_groups
            WHERE webfilter_assoc_groups.webfilter_id=? AND webfilter_assoc_groups.group_id=webfilter_group.ID
            ORDER BY webfilter_group.groupname";
        $bind=$ID;
    }

    $localldap=array(
        0=>"{ldap_group}",1=>"{virtual_group}",2=>"{active_directory_group}",
        3=>"{remote_ladp_group}",4=>"{active_directory_group} ({other})",5=>"{active_directory_ou}");

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/userdump/$ID"),true);
    $addonDump="";
    if(isset($json["Count"]) && intval($json["Count"])>1){
        $addonDump=" (".intval($json["Count"])." {records})";
    }

    $buttons=array();
    if($canWrite){ $buttons[]=array("Loadjs('$page?link-group-js=$ID')","fas fa-plus","{link_group}"); }
    $buttons[]=array("Loadjs('$page?link-dump-js=$ID')","fas fa-magnifying-glass","{display_members}$addonDump");
    $buttons[]=array("Loadjs('$page?link-dump-debug-js=$ID')","fas fa-bug","{display_members} ({debug})");

    $html=array();
    $html[]="<div class='awc-search' style='margin-top:0'><div class='awc-search-actions'>";
    foreach($buttons as $b){
        $html[]="<a class='awc-btn' href=\"javascript:blur();\" OnClick=\"".$b[0]."\">"
            ."<i class='".$b[1]."'></i> ".$b[2]."</a>";
    }
    $html[]="</div></div>";
    if($ruleLocked){ $html[]=ufdbrules_meta_banner($tpl,$ID); }
    $html[]=awc_search_bar("ufdbs-$ID-search","{search}");
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{sources}</th>";
    $html[]="<th class='center' style='width:1%'>{type}</th>";
    $html[]="<th class='center' style='width:1%'>{items}</th>";
    $html[]="<th style='width:1%;text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='ufdbs-$ID-rows'>";

    $results=$q->QUERY_SQL_PARAMS($sql,array($bind));
    if(!$q->ok){
        // Le message SQLite nomme tables et colonnes: journal, pas ecran.
        writelogs("webfilter_assoc_groups: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        $results=array();
    }
    if(!is_array($results)){ $results=array(); }

    $n=0;
    foreach($results as $ligne){
        $KEY_ID_GROUP=intval($ligne["webfilter_group_ID"]);
        $assocID=intval($ligne["ID"]);
        $LocalLDAP=intval($ligne["localldap"]);
        $enabled=(intval($ligne["enabled"])!=0);
        $name=htmlspecialchars((string)$ligne["groupname"]);
        $description="";
        $extra="";
        $members="";
        $canEdit=false;
        $icon=($LocalLDAP<2)?"fa-users":"fa-sitemap";

        if($LocalLDAP==0){
            $gp=new groups($ligne["gpid"]);
            $name=$tpl->td_href(htmlspecialchars($gp->groupName),"","Loadjs('fw.groups.ldap.php?gpid=".intval($ligne["gpid"])."')");
            $description=htmlspecialchars((string)$gp->description);
            $members=count($gp->members);
            // Un groupe LDAP externe n'expose pas son effectif: on le dit, au lieu de
            // laisser « ?? » comme le faisait la valeur initiale du compteur.
            if(preg_match("#^ExtLdap:(.+)#",(string)$ligne["webfilter_group_dn"],$re)){
                $members=""; $extra=htmlspecialchars($re[1]);
            }
        }

        if($LocalLDAP==1){
            $rows=$q->QUERY_SQL_PARAMS("SELECT COUNT(ID) as tcount FROM webfilter_members WHERE groupid=?",array($KEY_ID_GROUP));
            $members=isset($rows[0]["tcount"])?intval($rows[0]["tcount"]):0;
            $name=$tpl->td_href(htmlspecialchars((string)$ligne["groupname"]),null,
                "Loadjs('fw.ufdb.group.php?ID=$KEY_ID_GROUP')");
            $description=htmlspecialchars((string)$ligne["description"]);
        }

        if($LocalLDAP==2){
            $canEdit=true;
            if(preg_match("#AD:(.*?):(.+)#",(string)$ligne["webfilter_group_dn"],$re)){
                $LDAPID=$re[1];
                $dn=base64_decode($re[2]);
                $ad=new ActiveDirectory($LDAPID);
                $tty=array();
                if(preg_match("#^CN=(.+?),.*#i",$dn,$re2)){
                    $name=htmlspecialchars($re2[1]);
                    $members="";
                    $extra="<a href=\"javascript:blur();\" OnClick=\"Loadjs('dansguardian2.explodeadgroup.php?rule-id=$ID&groupid=$KEY_ID_GROUP')\">"
                        .$tpl->_ENGINE_parse_body("{dump_group}")."</a>";
                }else{
                    $tty=$ad->ObjectProperty($dn);
                    if(!is_array($tty)){ $tty=array(); }
                    $members=isset($tty["MEMBERS"])?intval($tty["MEMBERS"]):0;
                }
                // htmlentities(null) est deprecie en PHP 8.1 et $tty["description"]
                // n'existe pas sur la branche CN=.
                $description=isset($tty["description"])?htmlentities((string)$tty["description"]):"";
                if($description===""){ $description=htmlspecialchars((string)$ligne["description"]); }
            }else{
                $settings=@unserialize(base64_decode((string)$ligne["settings"]));
                if(!is_array($settings)){ $settings=array(); }
                $description=htmlspecialchars((string)$ligne["description"]);
                $ad=new ActiveDirectory(0,$settings);
                $tty=$ad->ObjectProperty($ligne["webfilter_group_dn"]);
                if(!is_array($tty)){ $tty=array(); }
                $members=isset($tty["MEMBERS"])?intval($tty["MEMBERS"]):0;
            }
        }

        if($LocalLDAP==3){
            // $DN etait lu HORS du if: quand la regexp echouait, $re gardait le contenu
            // laisse par une AUTRE branche ou une AUTRE ligne, et la recherche portait
            // sur le DN d'un groupe qui n'a rien a voir.
            $DN="";
            if(preg_match("#ExtLDAP:(.+?):(.+)#",(string)$ligne["groupname"],$re)){
                $name=htmlspecialchars($re[1]);
                $DN=base64_decode($re[2]);
            }
            if($DN!==""){
                include_once(dirname(__FILE__)."/ressources/class.ldap-extern.inc");
                $ldap_ext=new ldap_extern();
                $members=intval($ldap_ext->CountDeUsersByGroupDN($DN));
            }
        }

        if($LocalLDAP==4){
            $settings=@unserialize(base64_decode((string)$ligne["settings"]));
            if(!is_array($settings)){ $settings=array(); }
            $ad=new ActiveDirectory(0,$settings);
            $Members=$ad->search_users_from_groupName($ligne["groupname"]);
            $members=is_array($Members)?count($Members):0;
        }

        $type=$tpl->_ENGINE_parse_body(isset($localldap[$LocalLDAP])?$localldap[$LocalLDAP]:"{unknown}");
        // Groupe Artica Meta : badge, ni modifie ni delie.
        $gpLocked=ufdbrules_meta_locked_group($KEY_ID_GROUP);
        if($gpLocked){ $name.="&nbsp;".$tpl->_ENGINE_parse_body(ufdbrules_meta_badge()); }
        $html[]=ufdbs_source_row($assocID,$KEY_ID_GROUP,$ID,$name,$description,$type,$icon,
            ($members===""?"":strval($members)),$enabled,$extra,($canEdit && !$gpLocked),($canWrite && !$gpLocked));
        $n++;
    }

    if($n==0){ $html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>"; }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n {sources}</span>"
           ."<span class='awc-pg' id='ufdbs-$ID-pager'></span></div>";
    $html[]="</div></div>";
    // ICON_SCRIPTS_COMPILE() draine $GLOBALS["TPL_ICON_SCRIPTS"] ET la pile d'instance:
    // les lignes sont desormais baties par leur propre fonction, avec leur propre $tpl.
    $html[]="<script>if(typeof NoSpinner==='function'){NoSpinner();}\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    // Pagination et filtre remplacent le <tfoot><ul class='pagination'> de FooTable.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ufdbs-$ID-rows","ufdbs-$ID-pager")
        .awc_filter_js("ufdbs-$ID-search","ufdbs-$ID-rows","ufdbs-$ID-pager");
    return true;
}
