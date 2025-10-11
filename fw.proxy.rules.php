<?php
$GLOBALS["OBJTYPES"][0]="{src}";
$GLOBALS["OBJTYPES"][1]="{mac_address}";
$GLOBALS["OBJTYPES"][2]="{srcdomain}";
$GLOBALS["OBJTYPES"][3]="{member}";
$GLOBALS["OBJTYPES"][4]="{dstdomain}";
$GLOBALS["OBJTYPES"][5]="{categories}";

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["rule-fill"])){rule_fill();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["tiny-js"])){tiny_page();exit;}
if(isset($_GET["start"])){start();exit;}

if(isset($_GET["notify-message"])){notify_message();exit;}
if(isset($_POST["notify-message"])){notify_message_save();exit;}

if(isset($_GET["rules-search"])){search();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_GET["rule-settings"])){rule_settings_js();exit;}
if(isset($_POST["rule-settings"])){rule_settings_save();exit;}
if(isset($_GET["rule-settings-tab"])){rule_settings_tab();exit;}
if(isset($_GET["rule-settings-popup"])){rule_settings_popup();exit;}
if(isset($_GET["rule-settings-move"])){rule_settings_move();exit;}
if(isset($_GET["rule-settings-enable"])){rule_settings_enable();exit;}
if(isset($_GET["rule-settings-delete"])){rule_settings_delete();exit;}
if(isset($_POST["rule-settings-delete"])){rule_settings_delete_confirm();exit;}
if(isset($_GET["reconfigure-js"])){reconfigure_js();exit;}

if(isset($_GET["rule-object"])){rule_object_search();exit;}
if(isset($_GET["rule-object-search"])){rule_object_table();exit;}
if(isset($_GET["rule-object-enable"])){rule_object_enable();exit;}
if(isset($_GET["rule-object-move"])){rule_object_move();exit;}
if(isset($_GET["rule-object-delete"])){rule_object_delete();exit;}



if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["object-button"])){object_button();exit;}

if(isset($_GET["add-categories-js"])){categories_js();exit;}
if(isset($_GET["add-categories-popup"])){categories_popup();exit;}
if(isset($_GET["category-post-js"])){category_post_js();exit;}

if(isset($_GET["add-domain-js"])){domain_js();exit;}
if(isset($_GET["add-domain-popup"])){domain_popup();exit;}
if(isset($_POST["add-domain"])){domain_save();exit;}

if(isset($_GET["add-src-js"])){src_js();exit;}
if(isset($_GET["add-src-popup"])){src_popup();exit;}
if(isset($_POST["add-src"])){src_save();exit;}

if(isset($_GET["add-mac-js"])){mac_js();exit;}
if(isset($_GET["add-mac-popup"])){mac_popup();exit;}
if(isset($_POST["add-mac"])){mac_save();exit;}

if(isset($_GET["add-srcdomain-js"])){srcdomain_js();exit;}
if(isset($_GET["add-srcdomain-popup"])){srcdomain_popup();exit;}
if(isset($_POST["add-srcdomain"])){srcdomain_save();exit;}


if(isset($_GET["fill"])){fill();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_GET["delete-all"])){delete_all();exit;}
if(isset($_GET["delete-all-confirm"])){delete_all_confirm();exit;}
if(isset($_GET["delete-all-confirm-ok"])){delete_all_confirm_ok();exit;}
page();

function reconfigure_js():bool{
    $page       = CurrentPageName();
    $function   = $_GET["function"];
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    header("content-type: application/x-javascript");
    echo "$function()\n";
    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{access_rules}","fad fa-shield-alt","{PROXY_ACLS_EXPLAIN}<div id='proxy-acls-simple'></div>","$page?start=yes","proxy-rules","progress-standard-rules-restart",false,"table-standard-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{access_rules}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}

function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&rules-search=yes");
    echo "</div>";
    return true;
}
function rule_fill():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-fill"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT * FROM webfilters_simpleacls WHERE ID='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    $aclname=base64_encode($tpl->td_href($ligne["aclname"],"","Loadjs('$page?rule-settings=$ID')"));

    $zexplain=base64_encode($ligne["zExplain"]);
    $array_accesses=array_accesses();
    $ruleaction=$ligne["ruleaction"];

    $fl=ico_arrow_right;
    $Cin=CountOfIN($ID);
    $Cout=CountOfOut($ID);
    if($Cin==0 && $Cout==0){
        $ruleaction=999;
    }

    $recs=base64_encode($tpl->_ENGINE_parse_body("$Cin&nbsp;{records}&nbsp;<i class='$fl'></i>&nbsp;$Cout&nbsp;{records}"));


    $access=base64_encode($tpl->_ENGINE_parse_body($array_accesses[$ruleaction]));

    $PortDirectionS=method_list();
    $aclport=$ligne["aclport"];
    if(!isset($PortDirectionS[$aclport])){
        $PortDirectionS[$aclport]="{all}";
    }
    $port=base64_encode($tpl->_ENGINE_parse_body($PortDirectionS[$aclport]));
    $did="document.getElementById";
    $bs64="innerHTML=base64_decode";
    $f[]="if($did('acls-$ID-aclname')){ $did('acls-$ID-aclname').$bs64('$aclname');}";
    $f[]="if($did('acls-$ID-zexplain')){ $did('acls-$ID-zexplain').$bs64('$zexplain');}";
    $f[]="if($did('acls-$ID-access')){ $did('acls-$ID-access').$bs64('$access');}";
    $f[]="if($did('acls-$ID-port')){ $did('acls-$ID-port').$bs64('$port');}";
    $f[]="if($did('acls-$ID-recs')){ $did('acls-$ID-recs').$bs64('$recs');}";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;
}
function search():bool{
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $t=time();
    $function=$_GET["function"];
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));

    if($Go_Shield_Server_Enable==0){

        $jsinstall=$tpl->framework_buildjs("/goshield/install",
            "go.shield.server.progress",
            "go.shield.server.log",
            "progress-standard-rules-restart","window.location.href='/proxy-rules'");

        $btn=$tpl->button_autnonome("{install} {APP_GO_SHIELD_SERVER}", $jsinstall, ico_cd, "AsSystemAdministrator",350);
        echo $tpl->div_error("{ERROR_GO_SHIELD_SERVER_DISABLED}<div style='margin:15px;text-align:right'>$btn</div>");
        return false;
    }


    $limit=50;
    $TRCLASS="";
    $data1="class='text-capitalize' style='width:1%;text-align:center'";
    $TableClass="footable ";


    $search=trim($_GET["search"]);
    if(strlen($search)>1) {
        if(preg_match("#(rows|max|limit)=([0-9]+)#i",$search,$re)){
            $limit=intval($re[2]);
            $search=str_replace("$re[1]=$re[2]","",$search);
        }
        $search = "*$search*";
        $search = str_replace("**", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*", "%", $search);
    }
    if($limit==0){$limit=30;}
    $sql = "SELECT * FROM webfilters_simpleacls ORDER BY xORDER LIMIT $limit";
    if(strlen($search)>0) {
        $sql = "SELECT * FROM webfilters_simpleacls WHERE aclname LIKE '$search' ORDER BY xORDER LIMIT $limit";
    }
    writelogs("sql=$sql",__FUNCTION__,__FILE__,__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return true;
    }

    $html[]="<table id='table-simlefilters-rules-$t' class=\"{$TableClass}table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th $data1>{order}</th>";
    $html[]="<th $data1>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{method}</th>";
    $html[]="<th data-sortable=false>IN/OUT</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $PortDirectionS=method_list();
    $array_accesses=array_accesses();


    foreach ($results as $index=>$ligne){

        $ID=$ligne["ID"];
        $aclname=$ligne["aclname"];
        $enabled=$ligne["enabled"];
        $xORDER=$ligne["xORDER"];
        $aclport=$ligne["aclport"];
        $zExplain=$ligne["zExplain"];
        $ruleaction=$ligne["ruleaction"];
        $aclname=$tpl->td_href($aclname,"","Loadjs('$page?rule-settings=$ID')");
        $md=md5(serialize($ligne));
        if(!isset($PortDirectionS[$aclport])){
            $PortDirectionS[$aclport]="{all}";
        }
        if(!$users->AsDansGuardianAdministrator){
            writelogs("AsDansGuardianAdministrator == False",__FUNCTION__,__FILE__,__LINE__);
            if(count($users->SIMPLE_ACLS)>0){
                if(!isset($users->SIMPLE_ACLS[$ID])){
                    VERBOSE("($index) $aclname in_array=False");
                    continue;
                }
            }
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $priv="AsDansGuardianAdministrator";
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?rule-settings-enable=$ID')","",$priv);
        $up=$tpl->icon_up("Loadjs('$page?rule-settings-move=$ID&dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?rule-settings-move=$ID&dir=0');");
        $delete=$tpl->icon_delete("Loadjs('$page?rule-settings-delete=$ID&md=$md');",$priv);

        if(!$users->AsDansGuardianAdministrator){
            $up=$tpl->icon_up("");
            $down=$tpl->icon_down("");
        }

        $fl=ico_arrow_right;
        $Cin=CountOfIN($ID);
        $Cout=CountOfOut($ID);
        if($Cin==0 && $Cout==0){
            $ruleaction=999;
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap >$xORDER</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap><span id='acls-$ID-access'>$array_accesses[$ruleaction]</span></td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap><span id='acls-$ID-aclname'>$aclname</span></td>";
        $html[]="<td class='left' style='width:50%'><span id='acls-$ID-zexplain'>$zExplain</span></td>";
        $html[]="<td style='vertical-align:middle;width:25%'><span id='acls-$ID-port'>$PortDirectionS[$aclport]</span></td>";

        $html[]="<td style='vertical-align:middle;width:1%' nowrap><span id='acls-$ID-recs'>$Cin&nbsp;{records}&nbsp;<i class='$fl'></i>&nbsp;$Cout&nbsp;{records}</span></td>";

        $html[]="<td class='center' style='width:1%' nowrap>$enable</td>";
        $html[]="<td class='center' style='width:1%' nowrap>$up&nbsp;&nbsp;$down</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$delete</center></td>";
        $html[]="</tr>";
    }


    $toTiny="Loadjs('$page?tiny-js=yes&function=$function')";

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $html[]="<script>";

        $html[]="$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";


    $html[]="$toTiny
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    LoadAjax('proxy-acls-bugs','$page?proxy-acls-bugs=yes');
</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;

}
function CountOfIN($ID):int{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM webfilters_simpleaobjects WHERE aclid=$ID AND direction=0");
    return intval($ligne["tcount"]);

}
function CountOfOut($ID):int{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM webfilters_simpleaobjects WHERE aclid=$ID AND direction=1");
    return intval($ligne["tcount"]);

}

function array_accesses():array{

    $array_access[0]="<span class='label label-danger'>{deny_access}</span>";
    $array_access[1]="<span class='label label-primary'>{allow_access}</span>";
    $array_access[2]="<span class='label label-info'>{acl_object}</span>";
    $array_access[999]="<span class='label label-default'>{inactive2}</span>";
    return $array_access;
}

function tiny_page():string{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $function       = $_GET["function"];

    $add="Loadjs('$page?newrule-js=yes&function=$function');";
    $reconfigure="Loadjs('$page?reconfigure-js=yes&function=$function');";
    $users=new usersMenus();
    $topbuttons=array();
    if($users->AsDansGuardianAdministrator) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
    }
    $topbuttons[] = array($reconfigure, ico_retweet, "{reconfigure}");

    $js="s_PopUp('https://wiki.articatech.com/proxy-service/simple-acls','1024','800')";
    $topbuttons[] = array($js, ico_support, "Wiki");

    $TINY_ARRAY["TITLE"]="{access_rules}";
    $TINY_ARRAY["ICO"]="fad fa-shield-alt";
    $TINY_ARRAY["EXPL"]="{PROXY_ACLS_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->th_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    header("content-type: application/x-javascript");
    echo $jstiny;
    return "";
}
function rule_settings_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["rule-settings"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT aclname FROM webfilters_simpleacls WHERE ID='$ID'");
    $function   = $_GET["function"];
    $title      = $ligne["aclname"];
    return $tpl->js_dialog($title,"$page?rule-settings-tab=$ID&function=$function");
}
function rule_settings_tab():bool{
    $users=new usersMenus();
    $page       = CurrentPageName();
    $ID         = intval($_GET["rule-settings-tab"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT ruleaction,aclname FROM webfilters_simpleacls WHERE ID='$ID'");
    $title      = $ligne["aclname"];
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleaction=intval($ligne["ruleaction"]);
    if($users->AsDansGuardianAdministrator) {
        $array[$title] = "$page?rule-settings-popup=$ID&function=$function";
    }
    $array["{incoming_criteria}"] = "$page?rule-object=$ID&function=$function&dir=0";
    $array["{outgoing_criteria}"] = "$page?rule-object=$ID&function=$function&dir=1";
    if($users->AsDansGuardianAdministrator) {
        if($ruleaction==0){
            $array["{NotifyMessage}"] = "$page?notify-message=$ID";
        }
    }


    echo $tpl->tabs_default($array);
    return true;
}
function rule_settings_move():bool{
    $tpl=new template_admin();
    $ID=$_GET["rule-settings-move"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT xORDER FROM webfilters_simpleacls WHERE `ID`='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
    $xORDER_ORG=intval($ligne["xORDER"]);
    $xORDER=$xORDER_ORG;


    if($_GET["dir"]==1){$xORDER=$xORDER_ORG-1;}
    if($_GET["dir"]==0){$xORDER=$xORDER_ORG+1;}
    if($xORDER<0){$xORDER=0;}
    $sql="UPDATE webfilters_simpleacls SET xORDER=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}
    if($GLOBALS["VERBOSE"]){echo "$sql\n";}

    if($_GET["dir"]==1){
        $xORDER2=$xORDER+1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE webfilters_simpleacls SET xORDER=$xORDER2 WHERE ID<>$ID AND xORDER=$xORDER";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}
    }
    if($_GET["dir"]==0){
        $xORDER2=$xORDER-1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE webfilters_simpleacls SET xORDER=$xORDER2 WHERE ID<>{$_GET["rule-settings-move"]} AND xORDER=$xORDER";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
    }

    $c=0;
    $sql="SELECT ID FROM webfilters_simpleacls ORDER BY xORDER";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}

    foreach($results as $index=>$ligne) {
        $c++;
        $q->QUERY_SQL("UPDATE webfilters_simpleacls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}

    }
    return true;
}
function rule_object_table():bool{
    $page       = CurrentPageName();
    $tpl=new template_admin();
    $aclid         = intval($_GET["rule-object-search"]);
    $dir        = intval($_GET["dir"]);
    $function   = $_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $search=trim($_GET["search"]);
    if(strlen($search)>1) {
        if(preg_match("#(rows|max|limit)=([0-9]+)#i",$search,$re)){
            $limit=intval($re[2]);
            $search=str_replace("$re[1]=$re[2]","",$search);
        }
        $search = "*$search*";
        $search = str_replace("**", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*", "%", $search);
    }
    if($limit==0){$limit=30;}
    $sql = "SELECT * FROM webfilters_simpleaobjects WHERE aclid=$aclid AND direction=$dir ORDER BY xORDER LIMIT $limit";
    if(strlen($search)>0) {
        $sql = "SELECT * FROM webfilters_simpleacls WHERE (aclid=$aclid AND direction=$dir) AND pattern LIKE '$search' ORDER BY xORDER LIMIT $limit";
    }
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return true;
    }


    $data1="class='text-capitalize' style='width:1%;text-align:center'";
    $html[]="<table id='table-object-$aclid-$dir' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th $data1>{order}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{pattern}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>&nbsp;</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS="";
    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$aclid])){
            $priv="";
        }
    }

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $aclname=$ligne["aclname"];
        $enabled=$ligne["enabled"];
        $xORDER=$ligne["xORDER"];
        $pattern=$ligne["pattern"];
        $description=$ligne["description"];
        $objecttype=$ligne["objecttype"];
        if($objecttype==5){
            $catz=new mysql_catz();
            $pattern=$catz->CategoryIntToStr(intval($pattern));

        }
        $md=md5("acl-object-$aclid-$dir-$index");
        if($pattern=="*"){
            $pattern="{all}";
        }
        $pattern=$tpl->td_href($pattern,"","Loadjs('$page?object-settings=$ID&aclid=$aclid')");




        $enable=$tpl->icon_check($enabled,"Loadjs('$page?rule-object-enable=$ID&aclid=$aclid')","",$priv);
        $up=$tpl->icon_up("Loadjs('$page?rule-object-move=$ID&dir=1&aclid=$aclid');");
        $down=$tpl->icon_down("Loadjs('$page?rule-object-move=$ID&dir=0&aclid=$aclid');");
        $delete=$tpl->icon_delete("Loadjs('$page?rule-object-delete=$ID&aclid=$aclid&md=$md');",$priv);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap >$xORDER</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap>{$GLOBALS["OBJTYPES"][$objecttype]}</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap>$pattern</td>";
        $html[]="<td style='vertical-align:middle;'>$description</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$enable</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$delete</center></td>";
        $html[]="</tr>";
    }


    $buttons="LoadAjaxTiny('proxy-acls-simple-object-$aclid-$dir','$page?object-button=$aclid&function=$function&dir=$dir')";

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $html[]="<script>";

        $html[]="$(document).ready(function() { $('#table-object-$aclid-$dir').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";


    $html[]="$buttons
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    
</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));

    return true;
}
function rule_object_delete():bool{
    $tpl=new template_admin();
    $ID         = intval($_GET["rule-object-delete"]);
    $md         = $_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM webfilters_simpleaobjects WHERE ID='$ID'");
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}
    echo "$('#$md').remove();\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return true;
}
function rule_object_move():bool{
    $tpl=new template_admin();
    $ID=$_GET["rule-object-move"];
    $aclid=intval($_GET["aclid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT xORDER FROM webfilters_simpleaobjects WHERE `ID`='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
    $xORDER_ORG=intval($ligne["xORDER"]);
    $xORDER=$xORDER_ORG;


    if($_GET["dir"]==1){$xORDER=$xORDER_ORG-1;}
    if($_GET["dir"]==0){$xORDER=$xORDER_ORG+1;}
    if($xORDER<0){$xORDER=0;}
    $sql="UPDATE webfilters_simpleaobjects SET xORDER=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}
    if($GLOBALS["VERBOSE"]){echo "$sql\n";}

    if($_GET["dir"]==1){
        $xORDER2=$xORDER+1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE webfilters_simpleaobjects SET xORDER=$xORDER2 WHERE ID<>$ID AND aclid=$aclid AND xORDER=$xORDER";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}
    }
    if($_GET["dir"]==0){
        $xORDER2=$xORDER-1;
        if($xORDER2<0){$xORDER2=0;}
        $sql="UPDATE webfilters_simpleaobjects SET xORDER=$xORDER2 WHERE ID<>{$_GET["rule-object-move"]} AND aclid=$aclid AND xORDER=$xORDER";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
    }

    $c=0;
    $sql="SELECT ID FROM webfilters_simpleaobjects WHERE aclid=$aclid ORDER BY xORDER";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}

    foreach($results as $index=>$ligne) {
        $c++;
        $q->QUERY_SQL("UPDATE webfilters_simpleaobjects SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}

    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return true;
}
function rule_settings_enable():bool{
    $tpl=new template_admin();
    $ID         = intval($_GET["rule-settings-enable"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT enabled FROM webfilters_simpleacls WHERE ID='$ID'");
    $enabled    = intval($ligne["enabled"]);
    if($enabled==1){
        $enabled=0;
    }else{
        $enabled=1;
    }
    $q->QUERY_SQL("UPDATE webfilters_simpleacls SET enabled=$enabled WHERE ID='$ID'");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Enable/Disable=$enabled for Proxy simple ACL rule $ID");
}
function rule_settings_delete():bool{
    $tpl=new template_admin();
    $md=$_GET["md"];
    $ID         = intval($_GET["rule-settings-delete"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT aclname FROM webfilters_simpleacls WHERE ID='$ID'");
    $aclname=$ligne["aclname"];
    return $tpl->js_confirm_delete("$aclname","rule-settings-delete",$ID,"$('#$md').remove();");;
}
function rule_settings_delete_confirm():bool{
    $tpl=new template_admin();
    $ID         = intval($_POST["rule-settings-delete"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT aclname FROM webfilters_simpleacls WHERE ID='$ID'");
    $aclname=$ligne["aclname"];
    $q->QUERY_SQL("DELETE FROM webfilters_simpleaobjects WHERE aclid='$ID'");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    $q->QUERY_SQL("DELETE FROM webfilters_simpleacls WHERE ID='$ID'");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    $q->QUERY_SQL("DELETE FROM webfilters_simpleacls_privs WHERE aclid=$ID");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Delete Proxy simple ACL rule $aclname");
}
function rule_object_enable():bool{
    $tpl=new template_admin();
    $ID         = intval($_GET["rule-object-enable"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT enabled FROM webfilters_simpleaobjects WHERE ID='$ID'");
    $enabled    = intval($ligne["enabled"]);
    if($enabled==1){
        $enabled=0;
    }else{
        $enabled=1;
    }
    $q->QUERY_SQL("UPDATE webfilters_simpleaobjects SET enabled=$enabled WHERE ID='$ID'");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Enable/Disable=$enabled for Proxy simple ACL object $ID");
}
function rule_object_search():bool{
    $page       = CurrentPageName();
    $tpl=new template_admin();
    $ID         = intval($_GET["rule-object"]);
    $dir        = intval($_GET["dir"]);
    $function   = $_GET["function"];
    $html[]="<div id='proxy-acls-simple-object-$ID-$dir' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page,"","","","&rule-object-search=$ID&dir=$dir&function2=$function");
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}
function object_button(){
    $page       = CurrentPageName();
    $ID         = intval($_GET["object-button"]);
    $dir        = intval($_GET["dir"]);
    $function   = $_GET["function"];
    $tpl=new template_admin();
    $topbuttons=array();
    if($dir==1){
        $topbuttons[] = array("Loadjs('$page?add-categories-js=$ID&function=$function')", ico_books, "{add_category}");
        $topbuttons[] = array("Loadjs('$page?add-domain-js=$ID&function=$function')", ico_earth, "{add_domain}");

    }else{
        $topbuttons[] = array("Loadjs('$page?add-src-js=$ID&function=$function')", ico_networks, "{src}");
        $topbuttons[] = array("Loadjs('$page?add-mac-js=$ID&function=$function')", ico_nic, "{ComputerMacAddress}");
        $topbuttons[] = array("Loadjs('$page?add-srcdomain-js=$ID&function=$function')", ico_computer, "{srcdomain}");
    }
    echo $tpl->th_buttons($topbuttons);
}

function rule_settings_popup():bool{
    $tpl=new template_admin();
    $page       = CurrentPageName();
    $ID         = intval($_GET["rule-settings-popup"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM webfilters_simpleacls WHERE ID='$ID'");
    $function=$_GET["function"];

    $jsafter[]="BootstrapDialog1.close();";
    $jsafter[]="Loadjs('$page?rule-fill=$ID&function=$function');";

    if($function<>null){
        $jsafter[]="$function()";
    }
    $array_access[1]="{allow_access}";
    $array_access[0]="{deny_access}";
    $array_access[2]="{acl_object}";

    $form[]=$tpl->field_hidden("rule-settings", $ID);
    $form[]=$tpl->field_text("aclname", "{rule_name}", $ligne["aclname"],true);
    $form[]=$tpl->field_array_hash($array_access, "access", "{type}", $ligne["ruleaction"]);
    $form[]=$tpl->field_text("description", "{description}", $ligne["zExplain"]);
    $form[]=$tpl->field_array_hash(method_list(), "PortDirection", "{method}", intval($ligne["aclport"]));

    $html=$tpl->form_outside("", @implode("\n", $form),"","{apply}",
        @implode(";",$jsafter),"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_settings_save():bool{
    $q          =new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl        = new template_admin();
    $tpl->CLEAN_POST_XSS();
    $ID=$_POST["rule-settings"];
    $aclname    =$q->sqlite_escape_string2($_POST["aclname"]);
    $description=sqlite_escape_string2($_POST["description"]);
    $access=intval($_POST["access"]);
    $PortDirection=intval($_POST["PortDirection"]);


    $sql="UPDATE webfilters_simpleacls SET aclname='$aclname',zExplain='$description',ruleaction=$access,aclport=$PortDirection WHERE ID='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Update Proxy simple ACL rule $aclname");
}
function method_list():array{
    $localport=array();
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $HaClusterProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyPort"));
    $HaClusterNoAuthPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterNoAuthPort"));
    if($HaClusterNoAuthPort==0){
        $HaClusterNoAuthPort=8091;
    }

    if($HaClusterClient==1){
        $localport[$HaClusterNoAuthPort]="HaCluster NoAuth";
        $localport[$HaClusterProxyPort]="HaCluster LB";
    }

    $ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT port,PortName,xnote FROM proxy_ports WHERE enabled=1";
    $resultsPorts = $ql->QUERY_SQL($sql);
    foreach ($resultsPorts as $index=>$lignePorts) {
        $port = intval($lignePorts["port"]);
        if($port==0){continue;}
        $PortName=$lignePorts["PortName"];
        $xnote=$lignePorts["xnote"];
        $PortName="{listen_port} $port $PortName $xnote";
        $localport[$port]=trim($PortName);
    }
    return $localport;
}

function new_rule_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $function   = $_GET["function"];
    $title="{new_rule}";
    return $tpl->js_dialog($title,"$page?newrule-popup=yes&function=$function");
}

function new_rule_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $jsafter    = array();
    $array_access[1]="{allow_access}";
    $array_access[0]="{deny_access}";
    $explain=$tpl->_ENGINE_parse_body("{new_acls_rule_explain}");
    $function=$_GET["function"];
    $jsafter[]="BootstrapDialog1.close();";

    if($function<>null){
        $jsafter[]="$function()";
    }
    $form[]=$tpl->field_hidden("newrule", "yes");
    $form[]=$tpl->field_text("aclname", "{rule_name}", null,true);
    $form[]=$tpl->field_array_hash($array_access, "access", "{type}", 0);
    $form[]=$tpl->field_text("description", "{description}", null);
    $form[]=$tpl->field_array_hash(method_list(), "PortDirection", "{method}","");
    $html=$tpl->form_outside("", @implode("\n", $form),$explain,"{add}",
        @implode(";",$jsafter),"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
return true;
}
function new_rule_save():bool{
    $q          =new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl        = new template_admin();
    $tpl->CLEAN_POST_XSS();
    $aclport    =intval($_POST["PortDirection"]);
    $aclname    =sqlite_escape_string2($_POST["aclname"]);
    $TempName   =md5(time());
    $description=sqlite_escape_string2($_POST["description"]);
    $access=intval($_POST["access"]);

    $sql="INSERT INTO webfilters_simpleacls (aclname,enabled,acltpl,xORDER,aclport,aclgroup,zExplain,ruleaction)
	VALUES ('$TempName',1,'','0','$aclport','0','$description',$access)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}


    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_simpleacls WHERE aclname='$TempName'");
    $LASTID=$ligne["ID"];
    writelogs("Create a new Proxy simple ACL $aclname #$LASTID",__FUNCTION__,__FILE__,__LINE__);;

    $q->QUERY_SQL("UPDATE webfilters_simpleacls SET aclname='$aclname' WHERE ID='$LASTID'");

    $c=0;
    $sql="SELECT ID FROM webfilters_simpleacls WHERE aclport=$aclport ORDER BY xORDER";
    $results = $q->QUERY_SQL($sql);
    foreach($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE webfilters_simpleacls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
        if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
        $c++;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Create a new Proxy simple ACL $aclname");
}
function categories_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["add-categories-js"]);
    $function   = $_GET["function"];
    $tpl->js_dialog8("{add_category}","$page?add-categories-popup=$ruleid&function=$function");
}
function domain_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["add-domain-js"]);
    $function   = $_GET["function"];
    $tpl->js_dialog8("{add_domain}","$page?add-domain-popup=$ruleid&function=$function");
}
function src_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["add-src-js"]);
    $function   = $_GET["function"];
    return $tpl->js_dialog8("{src}","$page?add-src-popup=$ruleid&function=$function");
}
function mac_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["add-mac-js"]);
    $function   = $_GET["function"];
    return $tpl->js_dialog8("{ComputerMacAddress}","$page?add-mac-popup=$ruleid&function=$function");
}
function srcdomain_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function   = $_GET["function"];
    $ruleid=intval($_GET["add-srcdomain-js"]);
    return $tpl->js_dialog8("{srcdomain}","$page?add-srcdomain-popup=$ruleid&function=$function");
}
function isaregex($pattern):bool{
    $pattern=" $pattern";
    if(strpos($pattern, "*")>0){return true;}
    if(strpos($pattern, "[")>0){return true;}
    if(strpos($pattern, "]")>0){return true;}
    if(strpos($pattern, "(")>0){return true;}
    if(strpos($pattern, ")")>0){return true;}
    if(strpos($pattern, "?")>0){return true;}
    return false;
}
function cleanDomain($www):string{
    if(trim($www)==null){return "";}
    if( isaregex($www) ){
        return $www;
    }
    if(preg_match("#^(http|ftp):#", $www)){
        $array=parse_url($www);
        $www=$array["host"];
        if(strpos($www, ":")>0){$t=explode(":", $www);$www=$t[0];}
    }

    $www=str_replace("^","",$www);
    if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
    if(preg_match("#^(.+?)\?#", $www,$re)){$www=$re[1];}
    if(preg_match("#^(.+?)\/#", $www,$re)){$www=$re[1];}
    if(strpos(" $www", "/")>0){return "";}
    if(strpos(" $www", ";")>0){return "";}
    if(strpos(" $www", ",")>0){return "";}
    if(strpos(" $www", "%")>0){return "";}
    if(strpos(" $www", "!")>0){return "";}
    if(strpos(" $www", "<")>0){return "";}
    if(strpos(" $www", ">")>0){return "";}
    if(strpos(" $www", "[")>0){return "";}
    if(strpos(" $www", "]")>0){return "";}
    if(strpos(" $www", "(")>0){return "";}
    if(strpos(" $www", ")")>0){return "";}
    if(strpos(" $www", "+")>0){return "";}
    if(strpos(" $www", "?")>0){return "";}
    $www=trim(strtolower($www));
    if(function_exists("idn_to_ascii")){$www = @idn_to_ascii($www, "UTF-8");}
    return $www;
}
function domain_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $page=CurrentPageName();
    $ruleid=intval($_POST["add-domain"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {domain} {added} by $uid");

    $order=1;
    $tb=explode("\n",$_POST["domain"]);
    foreach ($tb as $index=>$domain) {
        if($domain=="*"){
            $q->QUERY_SQL("INSERT INTO webfilters_simpleaobjects (aclid,objecttype,pattern,enabled,description,xORDER,direction) 
        values ($ruleid,4,'*',1,'$description',$order,1)");
            continue;
        }

        $www=cleanDomain($domain);
        $order++;
        if(strlen($www)<3){
            echo $tpl->post_error("{domain} $index.[$domain] - {domain_too_short}");
            return false;
        }
        $q->QUERY_SQL("INSERT INTO webfilters_simpleaobjects (aclid,objecttype,pattern,enabled,description,xORDER,direction) 
        values ($ruleid,4,'$www',1,'$description',$order,1)");
        if(!$q->ok){echo $tpl->post_error("$index.[$www] - $q->mysql_error");return false;}
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks_post("Add domain(s) to Proxy simple ACL $ruleid");
}

function srcdomain_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["srcdomain-mac"]);
    if($ruleid==0){
        echo $tpl->post_error("{no_rule_selected}");
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {srcdomain} {added} by $uid");

    $order=1;
    $tb=explode("\n",$_POST["srcdomain"]);
    foreach ($tb as $index=>$domain) {
        $order++;
        if(strlen($domain)<3){continue;}
        $q->QUERY_SQL("INSERT INTO webfilters_simpleaobjects (aclid,objecttype,pattern,enabled,description,xORDER,direction) 
        values ($ruleid,2,'$domain',1,'$description',$order,0)");
        if(!$q->ok){echo $tpl->post_error("$index.[$domain] - $q->mysql_error");return false;}
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks_post("Add Client Hostname to Proxy simple ACL $ruleid");
}
function mac_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["add-mac"]);
    if($ruleid==0){
        echo $tpl->post_error("{no_rule_selected}");
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {mac} {added} by $uid");

    $order=1;
    $tb=explode("\n",$_POST["mac"]);
    foreach ($tb as $index=>$domain) {
        $order++;
        if(strlen($domain)<3){continue;}
        $q->QUERY_SQL("INSERT INTO webfilters_simpleaobjects (aclid,objecttype,pattern,enabled,description,xORDER,direction) 
        values ($ruleid,1,'$domain',1,'$description',$order,0)");
        if(!$q->ok){echo $tpl->post_error("$index.[$domain] - $q->mysql_error");return false;}
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks_post("Add MAC(s) to Proxy simple ACL $ruleid");
}
function src_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["add-src"]);
    if($ruleid==0){
        echo $tpl->post_error("{no_rule_selected}");
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {src} {added} by $uid");

    $order=1;
    $tb=explode("\n",$_POST["src"]);
    foreach ($tb as $index=>$domain) {
        $order++;
        if(strlen($domain)<3){continue;}
        $q->QUERY_SQL("INSERT INTO webfilters_simpleaobjects (aclid,objecttype,pattern,enabled,description,xORDER,direction) 
        values ($ruleid,0,'$domain',1,'$description',$order,0)");
        if(!$q->ok){echo $tpl->post_error("$index.[$domain] - $q->mysql_error");return false;}
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks_post("Add domain(s) to Proxy simple ACL $ruleid");
}
function domain_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["add-domain-popup"]);
    $form[]=$tpl->field_hidden("add-domain", $ruleid);
    $form[]=$tpl->field_textareacode("domain", "", null,true);

    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv="";
        }
    }

    $jsafter[]="dialogInstance8.close();";
    if(strlen($function)>0){
        $jsafter[]= "$function();";
    }

    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),$priv);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function src_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["add-src-popup"]);
    $form[]=$tpl->field_hidden("add-src", $ruleid);
    $form[]=$tpl->field_textareacode("src", "", null,true);

    $jsafter[]="dialogInstance8.close();";
    if(strlen($function)>0){
        $jsafter[]= "$function();";
    }
    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv="";
        }
    }

    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),$priv);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function mac_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["add-mac-popup"]);
    $form[]=$tpl->field_hidden("add-mac", $ruleid);
    $form[]=$tpl->field_textareacode("mac", "", null,true);

    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv="";
        }
    }

    $jsafter[]="dialogInstance8.close();";
    if(strlen($function)>0){
        $jsafter[]= "$function();";
    }

    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),$priv);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function srcdomain_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["add-srcdomain-popup"]);
    $form[]=$tpl->field_hidden("add-srcdomain", $ruleid);
    $form[]=$tpl->field_textareacode("srcdomain", "", null,true);

    $jsafter[]="dialogInstance8.close();";
    if(strlen($function)>0){
        $jsafter[]= "$function();";
    }
    $users=new usersMenus();
    $priv="AsDansGuardianAdministrator";
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv="";
        }
    }

    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),$priv);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function categories_popup(){
    include_once(dirname(__FILE__)."/ressources/class.categories.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=$_GET["add-categories-popup"];
    $function   = $_GET["function"];
    $Ccategories=new categories();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $EnableNRDS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNRDS"));
    if($ruleid==0){
        echo $tpl->div_error("{no_rule_selected}");
        return true;
    }
    $users=new usersMenus();
    $priv=false;
    if(!$users->AsDansGuardianAdministrator){
        if(isset($users->SIMPLE_ACLS[$ruleid])){
            $priv=true;
        }
    }else{
        $priv=true;
    }


    $sql="SELECT pattern FROM webfilters_simpleaobjects WHERE aclid=$ruleid AND objecttype=5 ORDER BY pattern";
    $results = $q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne) {
        $ALREADY[intval($ligne["pattern"])]=true;
    }

    $qPos=new postgres_sql();
    $Ccategories->patches_categories();
    $dans=new dansguardian_rules();
    if($qPos->COUNT_ROWS("personal_categories")==0){$dans->CategoriesTableCache();}

    if(!$qPos->TABLE_EXISTS("personal_categories")){
        $Ccategories->initialize();
    }

    $sql="SELECT *  FROM personal_categories ORDER BY categoryname ASC";
    $results = $qPos->QUERY_SQL($sql);
    if(!$qPos->ok){echo $qPos->mysql_error_html(true);}
    $TRCLASS=null;
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $html[]="<table id='table-category-add' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    while ($ligne = pg_fetch_assoc($results)) {
        if(preg_match("#^reserved#",$ligne['categoryname'])){continue;}
        if($ligne["category_icon"]==null){$ligne["category_icon"]="img/20-categories-personnal.png";}
        if(isset($ALREADY[$ligne["category_id"]])){continue;}
        $meta=intval($ligne["meta"]);
        $license=null;$remote_explain=null;
        $img="{$ligne["category_icon"]}";
        $button="";
        $md=md5(serialize($ligne));
        $ligne['category_description']=$tpl->utf8_encode($ligne['category_description']);
        $category_id=$ligne["category_id"];
        $remotecatz=intval($ligne["remotecatz"]);
        if($remotecatz>0){
            $remote_explain="&nbsp;<small>({use_remote_categories_services})</small>&nbsp;";

        }
        if($meta==1){
            $remote_explain="&nbsp;<small>({use_remote_categories_services})</small>&nbsp;";

        }

        $styleText=null;
        $js="Loadjs('$page?category-post-js=$category_id&ruleid=$ruleid&md=$md&function=$function')";


        if($EnableNRDS==0){
            if($category_id==238){
                $button="<button class='btn btn-default btn-xs' OnClick=\"Blur()\">{disabled}</button>";
                $styleText="style='color:#CCCCCC'";
            }
        }

        if($priv) {
            $button = "<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><img src='$img' alt='none'></td>";
        $html[]="<td $styleText nowrap style='width:1%'>".$tpl->_ENGINE_parse_body("{$ligne['categoryname']}")."</td>";
        $html[]="<td $styleText style='width:99%;'>".$tpl->_ENGINE_parse_body("{$ligne['category_description']}$remote_explain$license")."</td>";
        $html[]=$tpl->_ENGINE_parse_body("<td>$button</td>");
        $html[]="</tr>";

    }
    $TheShieldsCguard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsCguard"));
    if($TheShieldsCguard==1) {
        $cguardico = "<i class='fa-solid fa-shield-quartered'></i>";
        $CGuard_categories = $Ccategories->CGuard_categories();
        foreach ($CGuard_categories as $category_id => $categoryname) {
            if (isset($ALREADY[$ligne["category_id"]])) {
                continue;
            }
            if ($TRCLASS == "footable-odd") {
                $TRCLASS = null;
            } else {
                $TRCLASS = "footable-odd";
            }

            $md = md5("$category_id$categoryname");
            $js = "Loadjs('$page?category-post-js=$category_id&ruleid=$ruleid&md=$md&function=$function')";
            $button = "<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";
            $html[] = "<tr class='$TRCLASS' id='$md'>";
            $html[] = "<td style='width:1%'>$cguardico</td>";
            $html[] = "<td>$categoryname</td>";
            $html[] = "<td>$categoryname</td>";
            $html[] = $tpl->_ENGINE_parse_body("<td>$button</td>");
            $html[] = "</tr>";

        }
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-category-add').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}
function category_post_js():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $category=$_GET["category-post-js"];
    $ruleid=intval($_GET["ruleid"]);
    $md=$_GET["md"];
    $function   = $_GET["function"];
    $uid=$_SESSION["uid"];
    if($_SESSION["uid"]==-100){
        $uid="Manager";
    }
    $description=date("Y-m-d H:i:s").$tpl->javascript_parse_text(" {category} {added} by $uid");
    $q->QUERY_SQL("DELETE FROM webfilters_simpleaobjects WHERE aclid=$ruleid AND pattern='$category' AND objecttype=5");;
    $q->QUERY_SQL("INSERT INTO webfilters_simpleaobjects (aclid,pattern,direction,objecttype,enabled,description) VALUES ('$ruleid','$category',1,5,1,'$description')");
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";return false;}

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    if(strlen($function)>0){
        echo "$function();\n";
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return true;
}
function notify_message():bool{
    $page=CurrentPageName();
    $ID         = $_GET["notify-message"];
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");

    $ligne=$q->mysqli_fetch_array("SELECT zTemplate FROM webfilters_simpleacls WHERE ID=$ID");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $zTemplate=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["zTemplate"]);


    if(!isset($zTemplate["ENABLE"])){$zTemplate["ENABLE"]=0;}
    if(!isset($zTemplate["BODY"])){$zTemplate["BODY"]=null;}
    if(!isset($zTemplate["TITLE"])){$zTemplate["TITLE"]=null;}
    if(!isset($zTemplate["TEMPLATE_ID"])){$zTemplate["TEMPLATE_ID"]=0;}
    if($zTemplate["BODY"]==null) {
        $zTemplate["BODY"] = "<div id=\"titles\">\n<h1>ERROR</h1>\n<h2>Cache Access Denied.</h2>\n</div>\n<hr>\n\n<div id=\"content\">\n<p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>\n\n<blockquote id=\"error\">\n<p><b>Cache Access Denied.</b></p>\n</blockquote>\n\n<p>Sorry, you are not currently allowed to request %U from this cache until you have authenticated yourself.</p>\n\n<p>Please contact the <a href=\"mailto:%w%W\">cache administrator</a> if you have difficulties authenticating yourself.</p>\n\n<br>\n</div>\n\n<hr> \n<div id=\"footer\">\n<p>Generated %T by %h (%s)</p>\n<!-- %c -->\n</div>";
    }
    if($zTemplate["TITLE"]==null) {$zTemplate["TITLE"]="ERROR: Internet Access Denied";}
    if(intval($zTemplate["TEMPLATE_ID"])==0){$zTemplate["TEMPLATE_ID"]=1;}

    $form[]=$tpl->field_hidden("notify-message",$ID);
    $form[]=$tpl->field_checkbox("ENABLE","{enable}",$zTemplate["ENABLE"],true);
    $form[]=$tpl->field_templates("TEMPLATE_ID","{template}",$zTemplate["TEMPLATE_ID"]);
    $form[]=$tpl->field_text("TITLE", "{subject}", $tpl->utf8_decode($zTemplate["TITLE"]));
    $form[]=$tpl->field_textareacode("BODY","{content}",$tpl->utf8_decode($zTemplate["BODY"]));
    $tpl->form_add_button("{help}", "Loadjs('fw.proxy.templates.error.squid.php?help-js')");
    $jsafter="LoadAjax('table-acls-rules','$page?table=yes');Loadjs('$page?fill=$ID');";
    echo $tpl->form_outside("($ID): {NotifyMessage}", $form,null,"{apply}",$jsafter,"AsDansGuardianAdministrator",false);
    return true;
}
function notify_message_save():bool{
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl->CLEAN_POST();

    if($_POST["ENABLE"]==1){
        $_POST["ENABLE_ERROR_PAGE"]=0;
    }

    $ID=$_POST["notify-message"];
    if($ID==0){
        echo $tpl->post_error("ID === 0 !!");
        return false;
    }
    $ligne=$q->QUERY_SQL("SELECT zTemplate,aclname FROM webfilters_simpleacls WHERE ID=$ID");
    $aclname=$ligne["aclname"];
    $zTemplate=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["zTemplate"]);
    foreach ($_POST as $key=>$value){
        $zTemplate[$key]=$tpl->utf8_encode($value);
    }

    $zTemplateSer=serialize($zTemplate);
    $zTemplateNew=base64_encode($zTemplateSer);
    $sql="UPDATE webfilters_simpleacls SET zTemplate='$zTemplateNew' WHERE ID='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/config/simplerules");
    return admin_tracks("Modify notification page of $aclname rule");
}