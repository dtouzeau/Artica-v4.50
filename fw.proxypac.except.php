<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Politique Meta proxypac : exceptions en lecture seule quand "rules" est tenu.
include_once(dirname(__FILE__)."/ressources/class.proxypac.meta.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["delete-js"])){item_delete();exit;}
if(isset($_POST["ID"])){item_save();exit;}

js();
function enabled_js(){
    if(proxypac_meta_refuse_rules_js()){return;}
    $tpl=new template_admin();
    $aclid=intval($_GET["enabled-js"]);
    $ruleid=intval($_GET["ruleid"]);
    $items=acls_api_get("/proxypac/excepts?ruleID=$ruleid");
    $item=null;
    if(is_array($items)){
        foreach($items as $row){if(intval($row["id"])==$aclid){$item=$row;break;}}
    }
    $enabled=(is_array($item) && intval($item["enabled"])==1)?0:1;
    admin_tracks("Enable = $enabled for proxy.pac ".($item["pattern"] ?? $aclid)." exception item $aclid");
    list($ok,$err)=acls_api_send("PUT","/proxypac/excepts/$aclid/enable",["enabled"=>$enabled]);
    if(!$ok){echo $err;}
}
function item_delete(){
    if(proxypac_meta_refuse_rules_js()){return;}
    $ID=intval($_GET["delete-js"]);
    $md=$_GET["md"];
    $ruleid=intval($_GET["ruleid"]);
    $items=acls_api_get("/proxypac/excepts?ruleID=$ruleid");
    $pattern=$ID;
    if(is_array($items)){
        foreach($items as $row){if(intval($row["id"])==$ID){$pattern=$row["pattern"] ?? $ID;break;}}
    }
    list($ok,$err)=acls_api_send("DELETE","/proxypac/excepts/$ID");
    if(!$ok){header("content-type: application/x-javascript");echo "alert('".addslashes($err)."');";return;}
    admin_tracks("Removed $pattern for proxy.pac exception item $ID");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
}

function js(){
    $ruelid=$_GET["ruleid"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{exceptfor}","$page?table-start=$ruelid",650);
}
function item_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["item-js"];
    $ruleid=$_GET["ruleid"];

    if($ID==0) {
        $tpl->js_dialog2("{exceptfor}: {new_item}", "$page?item-popup=0&ruleid=$ruleid");
        return;
    }
    $tpl->js_dialog2("{exceptfor}: {item} $ID", "$page?item-popup=$ID&ruleid=$ruleid");
}

function table_start(){
    $page=CurrentPageName();
    $ruelid=intval($_GET["table-start"]);
    $html[]="<div id='exceptfor-$ruelid'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('exceptfor-$ruelid','$page?table=$ruelid');";
    $html[]="</script>";
    echo @implode("\n",$html);
}

function item_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["item-popup"]);
    $ruleid=intval($_GET["ruleid"]);

    $title="{item} $ID";
    if($ID==0){
        $btname="{add}";
        $title="{new_item}";
        $ligne["enabled"]=1;
        $ligne["type"]="src";
        $addjs="dialogInstance2.close();";
    }else{
        $addjs=null;
        $btname="{apply}";
        $items=acls_api_get("/proxypac/excepts?ruleID=$ruleid");
        $ligne=["enabled"=>1,"type"=>"src","pattern"=>""];
        if(is_array($items)){
            foreach($items as $row){if(intval($row["id"])==$ID){$ligne=["enabled"=>intval($row["enabled"]),"type"=>$row["type"],"pattern"=>$row["pattern"]];break;}}
        }
    }

    $tpl->field_hidden("ID",$ID);
    $tpl->field_hidden("ruleid",$ruleid);
    if(proxypac_meta_rules_held()){proxypac_meta_lock_form($tpl);}

    $type["src"]="{src}";
    $type["browser"]="{browser}";

    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    $form[]=$tpl->field_array_hash($type,"ztype","{type}",$ligne["type"]);
    $form[]=$tpl->field_text("pattern","{pattern}",$ligne["pattern"]);


    echo $tpl->form_outside($title,$form,null,$btname,
        "LoadAjax('exceptfor-$ruleid','$page?table=$ruleid');LoadAjax('table-loader-proxy-pac','fw.proxypac.rules.php?table=yes');$addjs","AsSquidAdministrator",true);

}
function item_save(){
    $tpl=new template_admin();
    $meta=proxypac_meta_refuse_rules_post();
    if($meta!==""){echo $meta;return false;}
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $ruleID=intval($_POST["ruleid"]);
    $pattern=$_POST["pattern"];
    $ztype=$_POST["ztype"];

    if($ID==0){
        list($ok,$err)=acls_api_send("POST","/proxypac/excepts",["ruleID"=>$ruleID,"type"=>$ztype,"pattern"=>$pattern]);
        if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return false;}
        admin_tracks("Created $pattern for proxy.pac rule $ruleID");
        return false;
    }

    // No PUT for type/pattern — delete+re-insert to update
    list($ok,$err)=acls_api_send("DELETE","/proxypac/excepts/$ID");
    if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return false;}
    list($ok,$err)=acls_api_send("POST","/proxypac/excepts",["ruleID"=>$ruleID,"type"=>$ztype,"pattern"=>$pattern]);
    if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($err));return false;}

    admin_tracks("Updated $pattern for proxy.pac rule $ruleID");

}

function table(){
    $t=time();
    $page=CurrentPageName();
    $ruleid=intval($_GET["table"]);
    $tpl=new template_admin();
    $TRCLASS=null;

    $add="Loadjs('$page?item-js=0&ruleid=$ruleid');";
    $xsec=proxypac_meta_rules_sec("AsSquidAdministrator");

    if(proxypac_meta_rules_held()){
        $html[]=proxypac_meta_banner($tpl,proxypac_meta_rules());
    }else{
        $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
        $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>";
        $html[]="</div>";
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{pattern}</th>";
    $html[]="<th class='center'>{type}</th>";
    $html[]="<th class='center'>{enable}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='pacx-rows'>";

    $results=acls_api_get("/proxypac/excepts?ruleID=$ruleid");
    if(!is_array($results)){echo $tpl->_ENGINE_parse_body("<tr><td colspan='4'>{error}</td></tr></tbody></table>");return;}

    $type["src"]="{src}";
    $type["browser"]="{browser}";

    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["id"]);
        $editjs="Loadjs('$page?item-js=$ID&ruleid=$ruleid');";
        $md=md5(serialize($ligne));
        $TRCLASS=null;
        $check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$ID&ruleid=$ruleid')",null,$xsec);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&ruleid=$ruleid&md=$md')",$xsec);

        // DEUX </center> orphelins fermaient ces cellules, et la derniere
        // portait un width=1% NON QUOTE contenant un %.
        $html[]="<tr id='$md'>";
        $html[]="<td><strong>". $tpl->td_href(htmlspecialchars((string)$ligne["pattern"]),null,$editjs)."</strong></td>";
        $html[]="<td class='center awc-nowrap'>".($type[$ligne["type"]] ?? htmlspecialchars((string)$ligne["type"]))."</td>";
        $html[]="<td class='center awc-nowrap'>$check</td>";
        $html[]="<td><div class='awc-acts'>$delete</div></td>";
        $html[]="</tr>";


    }


	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='pacx-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

	echo awc_table_style().$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js(100,"pacx-rows","pacx-pager");
}
