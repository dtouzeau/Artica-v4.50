<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["profile"])){profile();exit;}
if(isset($_POST["NewName"])){ChangeName();exit;}
if(isset($_GET["members"])){members();exit;}
if(isset($_GET["members-table"])){members_table();exit;}
if(isset($_GET["link-group-js"])){link_group_js();exit;}
if(isset($_GET["link-group-popup"])){link_group_popup();exit;}
if(isset($_GET["link-js"])){link_member();exit;}
if(isset($_GET["unlink-js"])){unlink_members();exit;}
if(isset($_GET["privileges"])){privileges();exit;}
if(isset($_GET["privileges-harmp-enable"])){privileges_harmp_enable();exit;}


if(isset($_GET["privileges-access-rule"])){privileges_access_rule();exit;}
if(isset($_GET["privileges-access-rule-switch"])){privileges_access_rules_switch();exit;}

if(isset($_GET["privileges-categories"])){privileges_categories();exit;}
if(isset($_GET["privileges-categories-enable"])){privileges_categories_enable();exit;}
if(isset($_GET["privileges-hamrp"])){privileges_harmp();exit;}
if(isset($_POST["privs"])){privileges_save();exit;}
js();

function js():bool{
    $ActiveDirectoryIndex="None";
    $urlExtension=null;
    $dn=$_GET["dn"];
    $dnenc=urlencode($dn);

    if(isset($_GET["connection-id"])) {
        $ActiveDirectoryIndex = intval($_GET["connection-id"]);
        if ($ActiveDirectoryIndex > 0) {
            if($ActiveDirectoryIndex==123456789){
                $ct=new external_ad_search(null);
                $params=$ct->FindParametersByDN($dn);
                $ActiveDirectoryIndex=intval($params["CONNECTION_ID"]);
            }
            $urlExtension = "&connection-id=$ActiveDirectoryIndex";
        }
    }
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ct=new external_ad_search(null,$ActiveDirectoryIndex);
    $hash=$ct->member_infos($dn);
    $GroupName=$hash[0]["samaccountname"][0];

    return $tpl->js_dialog5("{$GroupName}","$page?tabs=$dnenc$urlExtension");
}


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryIndex="None";$urlExtension=null;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $EnablePersonalCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePersonalCategories"));
    $EnableManagedReverseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableManagedReverseProxy"));

    if($EnablePersonalCategories==1){
        $q=new postgres_sql();
        $ligne=pg_fetch_array($q->QUERY_SQL("SELECT COUNT(*) AS tcount FROM personal_categories WHERE PublicMode='1'"));
        if(intval($ligne["tcount"])==0){$EnablePersonalCategories=0;}
    }

    $dn=$_GET["tabs"];
    $dnenc=urlencode($dn);
    $ct=new external_ad_search(null,$ActiveDirectoryIndex);
    $hash=$ct->member_infos($dn);
    $GroupName=$hash[0]["samaccountname"][0];
    $array[$GroupName]="$page?profile=$dnenc$urlExtension";
    $array["{members}"]="$page?members=$dnenc$urlExtension";
    $array["{privileges}"]="$page?privileges=$dnenc$urlExtension";
    if($EnablePersonalCategories==1){
        $array["{categories}"]="$page?privileges-categories=$dnenc$urlExtension";
    }
    if($EnableManagedReverseProxy==1){
        $array["{APP_HAMRP}"]="$page?privileges-hamrp=$dnenc$urlExtension";
    }
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==1){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) AS tcount FROM webfilters_simpleacls");
         if(!isset($ligne["tcount"])){$ligne["tcount"]=0;}
         VERBOSE("$ligne[tcount]",__LINE__);
         if(intval($ligne["tcount"])>0){
             $array["{access_rules}"]="$page?privileges-access-rule=$dnenc$urlExtension";
         }
    }


    echo $tpl->tabs_default($array);
}

function members(){
    $urlExtension=null;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $page=CurrentPageName();
    $dn=$_GET["members"];
    $dnenc=urlencode($dn);
    $html="<div id='MembersOfTheGroup'></div>
	<script>LoadAjaxTiny('MembersOfTheGroup','$page?members-table=$dnenc$urlExtension');</script>";
    echo $html;
}


function members_table(){
    $ActiveDirectoryIndex="None";$urlExtension=null;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $dn=$_GET["members-table"];
    $ct=new external_ad_search(null,$ActiveDirectoryIndex);
    $hash=$ct->member_infos($dn);
    if(!isset($hash[0])){
        $hash[0]=array("member" =>"","count"=>0);
    }

    $memberof=$hash[0]["member"];
    $md5=md5($dn);
    $tpl=new template_admin();

    $html[]=$tpl->_ENGINE_parse_body("
<table id='table-membersof-$md5' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members} ({$memberof["count"]})</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    for ($i=0;$i<$memberof["count"];$i++) {
        $gpid=$memberof[$i];
        $hash=$ct->member_infos($gpid);
        if(!isset($hash[0])){
            continue;
        }
        $dnuser=$hash[0]["dn"];
        $DisplayName=$hash[0]["samaccountname"][0];
        if(is_null($dnuser)){
            $dnuser="";
        }

        $text_class=null;
        $md=md5($gpid.$dnuser);
        $dnuserenc=urlencode($dnuser);



        $js="Loadjs('fw.member.ad.edit.php?dn=$dnuserenc$urlExtension')";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td>". $tpl->td_href($DisplayName,"{click_to_edit}",$js)."</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-membersof-$md5').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}





function profile(){
    $ActiveDirectoryIndex="None";$urlExtension=null;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $page=CurrentPageName();
    $dn=$_GET["profile"];
    $tpl=new template_admin();
    $ct=new external_ad_search(null,$ActiveDirectoryIndex);
    $hash=$ct->member_infos($dn);
    $array=$hash[0];
    $jsrestart=null;
    if(!isset($array["description"])){$array["description"][0]=null;}
    if(!isset($array["name"])){$array["name"][0]=null;}
    $form[]=$tpl->field_info("{CommonName}","{CommonName}", $array["cn"][0]);
    $form[]=$tpl->field_info("name","{name}", $array["name"][0]);
    $form[]=$tpl->field_info("{samaccountname}","{member}", $array["samaccountname"][0]);
    $form[]=$tpl->field_info("{description}","{description}", $array["description"][0]);
    unset($array["cn"]);
    unset($array["samaccountname"]);
    unset($array["description"]);
    unset($array["objectguid"]);
    unset($array["objectsid"]);
    unset($array["member"]);
    unset($array["name"]);
    unset($array["objectclass"]);


    foreach ($array as $key=>$val){
        if(!isset($val["count"])){continue;}

        for($i=0;$i<$val["count"];$i++){
            $form[]=$tpl->field_info($key,$key, $val[$i]);
        }

    }
    $samaccountname="";
    if(isset($array["samaccountname"][0])){
        $samaccountname=$array["samaccountname"][0];
    }



    echo $tpl->form_outside($samaccountname, @implode("\n", $form),null,null,"$jsrestart","AllowAddUsers");

}

function privileges():bool{
    $ActiveDirectoryIndex=0;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];}
    $dn=$_GET["privileges"];
    $hash["ArticaGroupPrivileges"]="";
    $tpl=new template_admin();
    $AD=true;

    $EnablePostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix");
    if(preg_match("#sqlite:([0-9]+)#", $dn,$re)){
        $ID=$re[1];
        $AD=false;
        $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
        $ligne=$q->mysqli_fetch_array("SELECT privileges FROM groups WHERE ID='$ID'");
        if(!is_null($ligne["privileges"])) {
            $hash["ArticaGroupPrivileges"] = base64_decode($ligne["privileges"]);
        }

    }

    if($AD){
        $ct=new external_ad_search(null,$ActiveDirectoryIndex);
        $hash=$ct->LoadGroupDataByDN($dn);
    }
    $data=$hash["ArticaGroupPrivileges"];
    $ldap=new clladp();
    $strlen=0;
    if(!is_null($data)){
        $strlen=strlen($data);
    }
    VERBOSE("$dn $strlen bytes",__LINE__);
    $HashPrivieleges=$ldap->_ParsePrivieleges($data);
    $form[]=$tpl->field_section("{groups_allow}");
    $form[]=$tpl->field_hidden("privs", $dn);


    $proxy_allow=array("AllowAddUsers","AllowAddGroup","AsOrgAdmin","AsSquidPersonalCategories");

    foreach ($proxy_allow as $priv){
        if(!isset($HashPrivieleges[$priv])){$HashPrivieleges[$priv]=0;}
        $form[]=$tpl->field_checkbox($priv,"{{$priv}}",$HashPrivieleges[$priv],false,null,false,$priv);
    }
    $trings="AsWebStatisticsAdministrator,AsDansGuardianAdministrator,AsSquidAdministrator,AsHotSpotManager,AsProxyMonitor";
    $form[]=$tpl->field_section("{proxy_allow}");
    $f=explode(",",$trings);
    foreach ($f as $priv){
        if(!isset($HashPrivieleges[$priv])){$HashPrivieleges[$priv]=0;}
        $form[]=$tpl->field_checkbox($priv,"{{$priv}}",$HashPrivieleges[$priv],false,null,false,$priv);
    }
    if($EnablePostfix==1){
        $trings="AsPostfixAdministrator,AsMailBoxAdministrator";
        $form[]=$tpl->field_section("{messaging_allow}");
        $f=explode(",",$trings);
        foreach ($f as $priv){
            if(!isset($HashPrivieleges[$priv])){$HashPrivieleges[$priv]=0;}
            $form[]=$tpl->field_checkbox($priv,"{{$priv}}",$HashPrivieleges[$priv],false,null,false,$priv);
        }
    }
    $form[]=$tpl->field_section("{administrators_allow}");
    $trings="AsArticaAdministrator,AsFirewallManager,AsVPNManager,AsDnsAdministrator,ASDCHPAdmin,AsCertifsManager,AsDatabaseAdministrator,AsWebMaster,AsWebSecurity,AsSambaAdministrator,AsDockerAdmin";
    $f=explode(",",$trings);
    foreach ($f as $priv){
        if(!isset($HashPrivieleges[$priv])){$HashPrivieleges[$priv]=0;}
        $form[]=$tpl->field_checkbox($priv,"{{$priv}}",$HashPrivieleges[$priv],false,null,false,$priv);
        //$name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null

    }

    $html[]=$tpl->form_outside("{privileges}", @implode("\n", $form),null,"{apply}","blur()","AsSystemAdministrator");

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function privileges_access_rule():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS webfilters_simpleacls_privs ( ID INTEGER PRIMARY KEY AUTOINCREMENT,aclid INTEGER NOT NULL DEFAULT 0,dngroup TEXT NOT NULL DEFAULT '')");

    $dn=$_GET["privileges-access-rule"];
    if(strlen($dn)<2){
        echo $tpl->div_error("NO DN ??");
        return false;
    }
    $dnenc=urlencode($dn);
    $ENABLEDS=array();
    $results=$q->QUERY_SQL("SELECT aclid FROM webfilters_simpleacls_privs WHERE dngroup='$dn'");
    foreach ($results as $index=>$ligne){
        VERBOSE("$index=>$ligne",__LINE__);
        $ENABLEDS[$ligne["aclid"]]=true;
    }
    $results=$q->QUERY_SQL("SELECT ID,aclname,zExplain FROM webfilters_simpleacls ORDER BY aclname");

    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error</div>";
        return false;
    }
    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{rule}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $aclid = $ligne["ID"];
        $aclname=$ligne["aclname"];
        $zExplain=$ligne["zExplain"];
        $enable = 0;

        if(strlen($zExplain)>1){
            $zExplain="<br><i>$zExplain</i>";
        }
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td style='width:1%' nowrap><strong>$aclname</strong>$zExplain</td>";


        if (isset($ENABLEDS[$aclid])) {
            $enable = 1;
        }

        $enabled = $tpl->icon_check($enable,
            "Loadjs('$page?privileges-access-rule-switch=$dnenc&aclid=$aclid')");
        $html[] = "<td style='width:1%' class='center' nowrap>$enabled</td>";
        $html[] = "</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function privileges_harmp():bool{
    $tpl=new template_admin();
    $dn=$_GET["privileges-hamrp"];
    $t=time();
    $dnenc=urlencode($dn);
    $page=CurrentPageName();
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS privs (`ID` INTEGER PRIMARY KEY AUTOINCREMENT, groupid INT,dngroup TEXT )");
    $ENABLEDS=array();
    $results=$q->QUERY_SQL("SELECT groupid FROM privs WHERE dngroup='$dn'");
    foreach ($results as $index=>$ligne){
        VERBOSE("$index=>$ligne",__LINE__);
        $ENABLEDS[$ligne["groupid"]]=true;
    }
    $results=$q->QUERY_SQL("SELECT * FROM groups");

    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error</div>";
        return false;
    }


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{group}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach ($results as $index=>$ligne){
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $groupid = $ligne["ID"];
        $groupname=$ligne["groupname"];
        $comment=$ligne["comment"];
        $enable = 0;

        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td style='width:1%' nowrap><strong>$groupname</strong></td>";
        $html[] = "<td nowrap>$comment</td>";

        if (isset($ENABLEDS[$groupid])) {
            $enable = 1;
        }

        $enabled = $tpl->icon_check($enable,
            "Loadjs('$page?privileges-harmp-enable=$dnenc&groupid=$groupid')");
        $html[] = "<td style='width:1%' class='center' nowrap>$enabled</td>";
        $html[] = "</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function privileges_categories(){
    $q=new postgres_sql();
    $tpl=new template_admin();
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS personal_categories_privs (
				 id SERIAL PRIMARY KEY,
				 category_id BIGINT,
				 dngroup VARCHAR( 512 ) )");

    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

    $page=CurrentPageName();
    $pp=new categories();
    $t=time();
    $dn=$_GET["privileges-categories"];
    $dnenc=urlencode($dn);

    $results=$q->QUERY_SQL("SELECT id,category_id FROM personal_categories_privs WHERE dngroup='$dn'");
    while ($ligne = pg_fetch_assoc($results)) {
        $HASH[$ligne["category_id"]]=$ligne["id"];
    }

    $sql="SELECT * FROM personal_categories WHERE PublicMode=1 order by categoryname ";
    $results=$q->QUERY_SQL($sql);


    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error<br>$sql</div>";
        return;
    }


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{categories}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $category_id = $ligne["category_id"];
        $categoryname = $ligne["categoryname"];
        $enable = 0;
        $text_category = $tpl->_ENGINE_parse_body($ligne["category_description"]);
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td style='width:1%' nowrap><strong>$categoryname</strong></td>";
        $html[] = "<td nowrap>$text_category</td>";

        if (isset($HASH[$category_id])) {
            $enable = 1;
        }

        $enabled = $tpl->icon_check($enable,
            "Loadjs('$page?privileges-categories-enable=$dnenc&category_id=$category_id')");
        $html[] = "<td style='width:1%' class='center' nowrap>$enabled</td>";
        $html[] = "</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function privileges_categories_enable():bool{
    $tpl=new template_admin();
    $dn=$_GET["privileges-categories-enable"];
    $category_id=intval($_GET["category_id"]);
    $q=new postgres_sql();
    $q->create_personal_categories();
    $ligne=$q->QUERY_SQL("SELECT id FROM personal_categories_privs WHERE dngroup='$dn' and category_id=$category_id");

    $id=intval($ligne["id"]);
    if($id==0){
        $dn=str_replace("'","\'",$dn);
        $q->QUERY_SQL("INSERT INTO personal_categories_privs (category_id,dngroup)
        VALUES ('$category_id','$dn')");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
        return true;
    }
    $q->QUERY_SQL("DELETE FROM personal_categories_privs WHERE id=$id");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    return true;
}
function privileges_access_rules_switch():bool{
    $tpl=new template_admin();
    // privileges-access-rule-switch=$dnenc&aclid=$dnenc
    $dn=$_GET["privileges-access-rule-switch"];
    if(strlen($dn)<3){
        return $tpl->js_mysql_alert("Invalid DN");

    }
    $aclid=intval($_GET["aclid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->QUERY_SQL("SELECT aclid FROM webfilters_simpleacls_privs WHERE dngroup='$dn'");
    $id=intval($ligne["aclid"]);
    if($id==0){
        $dn=str_replace("'","\'",$dn);
        $q->QUERY_SQL("INSERT INTO webfilters_simpleacls_privs (aclid,dngroup)
        VALUES ('$aclid','$dn')");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    }
    return admin_tracks("Set privileges on Web Filter ACL #$aclid for group $dn");
}

function privileges_harmp_enable():bool{
    $tpl=new template_admin();
    $dn=$_GET["privileges-harmp-enable"];
    $groupid=intval($_GET["groupid"]);
    if($groupid==0){return false;}
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $ligne=$q->QUERY_SQL("SELECT ID FROM privs WHERE dngroup='$dn' and groupid=$groupid");
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return false;
    }
    if(!isset($ligne["ID"])){$ligne["ID"]=0;}

    $id=intval($ligne["ID"]);
    if($id==0){
        $dn=str_replace("'","\'",$dn);
        VERBOSE("INSERT INTO privs (groupid,dngroup) VALUES ('$groupid','$dn')");
        $q->QUERY_SQL("INSERT INTO privs (groupid,dngroup) VALUES ('$groupid','$dn')");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
        return admin_tracks("Set privileges on Reverse Proxy group #$groupid for group $dn");
    }
    $q->QUERY_SQL("DELETE FROM privs WHERE ID=$id");
    return admin_tracks("Delete privileges on Reverse Proxy group #$groupid for group $dn");
}


function privileges_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ldap=new clladp();
    $dn=$_POST["privs"];
    $Hash=array();
    $SQLITE=false;
    $AD=true;
    if(preg_match("#sqlite:([0-9]+)#", $dn,$re)){
        $ID=$re[1];
        $AD=false;
        $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
        $ligne=$q->mysqli_fetch_array("SELECT `privileges` FROM groups WHERE ID='$ID'");
        $SQLITE=true;
        if(isset($ligne["privileges"])) {
            $Hash["ArticaGroupPrivileges"] = base64_decode($ligne["privileges"]);
        }
    }
    if($AD){
        $ct=new external_ad_search();
        $Hash=$ct->LoadGroupDataByDN($dn);
    }

    if(!is_array($Hash["ArticaGroupPrivileges"])){
        writelogs("ldap->_ParsePrivieleges(...)",__FUNCTION__,__FILE__,__LINE__);
        $ArticaGroupPrivileges=$ldap->_ParsePrivieleges($Hash["ArticaGroupPrivileges"]);
    }else{
        $ArticaGroupPrivileges=$Hash["ArticaGroupPrivileges"];
    }
    $admLogs=array();
    if(is_array($ArticaGroupPrivileges)){
        foreach ($ArticaGroupPrivileges as $num=>$val){$GroupPrivilege[$num]=$val;}
    }
    $GroupPrivilegeNew=array();
    foreach ($_POST as $num=>$val){
        if(!is_numeric($val)){continue;}
        if($val==1){$val="yes";}else{$val="no";}
        $GroupPrivilege[$num]=$val;
    }
    foreach ($GroupPrivilege as $num=>$val){
        if($val=="no"){writelogs("[$num]=SKIP",__FUNCTION__,__FILE__,__LINE__);continue;}
        writelogs("[$num]=\"$val\"",__FUNCTION__,__FILE__,__LINE__);
        $admLogs[]="$num=$val";
        $GroupPrivilegeNew[]="[$num]=\"$val\"";
    }

    $values=@implode("\n",$GroupPrivilegeNew);

    if($SQLITE){
        $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
        $values=base64_encode($values);
        $q->QUERY_SQL("UPDATE `groups` SET `privileges`='$values' WHERE ID=$ID");
        if(!$q->ok){echo $q->mysql_error;}
        return admin_tracks("Defines Web-console privileges ".@implode(",",$admLogs)." on group $dn");

    }

    $gp=new external_ad_search();
    $gp->SaveGroupPrivileges($values,$dn);
    return admin_tracks("Defines Web-console privileges ".@implode(",",$admLogs)." on group $dn");
}



function ChangeName(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $newname=$_POST["NewName"];
    $group=new groups($_POST["gpid"]);
    if(trim($newname)<>null){
        if(trim(strtolower($newname)) <> trim(strtolower($group->groupName)) ){
            $gp=new groups($_POST["gpid"]);
            $actualdn=$gp->dn;
            if(preg_match('#cn=(.+?),(.+)#',$actualdn,$re)){$branch=$re[2];}
            $newdn="cn=$newname";
            $newdn2="$newdn,$branch";
            $ldap=new clladp();
            if($ldap->ExistsDN($newdn2)){return null;}
            writelogs("Rename $actualdn to $newdn",__CLASS__.'/'.__FUNCTION__,__FILE__);
            if(!$ldap->Ldap_rename_dn($newdn,$actualdn,$branch)){echo $tpl->_ENGINE_parse_body("{GROUP_RENAME} $group->groupName {to} $newname {failed}\n $ldap->ldap_last_error");}

        }
    }
    if(!$group->SaveDescription($_POST["description"])){echo $group->ldap_error;}

}