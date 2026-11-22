<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__).'/ressources/class.ad-agent.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["profile"])){profile();exit;}
if(isset($_POST["NewName"])){ChangeName();exit;}
if(isset($_GET["members"])){members();exit;}
if(isset($_GET["members-table"])){members_table();exit;}
if(isset($_GET["link-group-popup"])){link_group_popup();exit;}
if(isset($_GET["privileges"])){privileges();exit;}
if(isset($_GET["privileges-categories"])){privileges_categories();exit;}
if(isset($_GET["privileges-categories-enable"])){privileges_categories_enable();exit;}
if(isset($_POST["privs"])){privileges_save();exit;}
js();

function js(){
    $dn=$_GET["dn"];
    $GroupName=urlencode($_GET["group_name"]);
    $dnenc=urlencode($dn);

    $page=CurrentPageName();
    $tpl=new template_admin();

    // Pentest 2026-11-04 : group_name etait recopie sans encodage dans la page (XSS).
    // Le titre est decode (html_entity_decode) puis rendu en HTML : double encodage.
    $tpl->js_dialog5(req_html(req_html($_GET["group_name"] ?? "")),"$page?tabs=$dnenc&group_name=$GroupName");
}


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $EnablePersonalCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePersonalCategories"));

    if($EnablePersonalCategories==1){
        $q=new postgres_sql();
        $ligne=pg_fetch_array($q->QUERY_SQL("SELECT COUNT(*) AS tcount FROM personal_categories WHERE PublicMode='1'"));
        if(intval($ligne["tcount"])==0){$EnablePersonalCategories=0;}
    }

    $dn=$_GET["tabs"];
    $dnenc=urlencode($dn);
    $GroupName=urlencode($_GET["group_name"]);

    // Pentest 2026-11-04 : group_name etait recopie sans encodage dans la page (XSS).
    $array[req_html(urldecode($GroupName))]="$page?profile=$dnenc";
    $array["{members}"]="$page?members=$dnenc";
    $array["{privileges}"]="$page?privileges=$dnenc";
    if($EnablePersonalCategories==1){
        $array["{categories}"]="$page?privileges-categories=$dnenc";
    }
    echo $tpl->tabs_default($array);
}

function members(){
    $page=CurrentPageName();
    $dn=$_GET["members"];
    $dnenc=urlencode($dn);
    $html="<div id='MembersOfTheGroup'></div>
	<script>LoadAjaxTiny('MembersOfTheGroup','$page?members-table=$dnenc');</script>";
    echo $html;
}


function members_table(){
    $dn=$_GET["members-table"];
    $encodedval=urlencode($dn);

    $adagent=new ADAgent();

    $adgroupattr=$adagent->getAttrOf("$encodedval");
    $data = json_decode($adgroupattr, TRUE);
    $md5=md5($dn);

    $page=CurrentPageName();
    $tpl=new template_admin();
    $membercount=count($data[0]["data"]["member"]);


    $html[]=$tpl->_ENGINE_parse_body("
<table id='table-membersof-$md5' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members} ({$membercount})</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;

    foreach ($data[0]["data"]["member"] as $k=>$v) {

        $DisplayName=explode(",", $v);
        $DisplayName=str_replace("CN=", "", $DisplayName[0]);

        $text_class=null;
        $md=md5($v);
        $dnuserenc=urlencode($v);

        $js="Loadjs('fw.member.ad.agent.edit.php?dn=$dnuserenc&name=$DisplayName')";
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
    $page=CurrentPageName();
    $dn=$_GET["profile"];
    $tpl=new template_admin();
    $encodedval=urlencode($dn);
    $adagent=new ADAgent();

    $adgroupattr=$adagent->getAttrOf("$encodedval");

    $data = json_decode($adgroupattr, TRUE);

    $jsrestart=null;
    $form[]=$tpl->field_info("{CommonName}","{CommonName}", $data[0]["data"]["cn"]);
    $form[]=$tpl->field_info("name","{name}", $data[0]["data"]["name"]);
    $form[]=$tpl->field_info("{samaccountname}","{member}", $data[0]["data"]["sAMAccountName"]);
    $form[]=$tpl->field_info("{description}","{description}", $data[0]["data"]["description"]);
    unset($data[0]["data"]["cn"]);
    unset($data[0]["data"]["sAMAccountName"]);
    unset($data[0]["data"]["description"]);
    unset($data[0]["data"]["objectguid"]);
    unset($data[0]["data"]["objectsid"]);
    unset($data[0]["data"]["members"]);
    unset($data[0]["data"]["name"]);
    unset($data[0]["data"]["objectClass"]);


    foreach ($data[0]["data"] as $key=>$val){
        if(is_array($val)) {
            foreach ($val as $k=>$v){
                $form[]=$tpl->field_info($key,$key, $v);
            }

        } else {
            $form[]=$tpl->field_info($key,$key, $val);
        }


    }

    echo $tpl->form_outside($data[0]["sAMAccountName"], @implode("\n", $form),null,null,"$jsrestart","AllowAddUsers");

}

function privileges(){
    $ActiveDirectoryIndex="None";$urlExtension=null;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $dn=$_GET["privileges"];
    $tpl=new template_admin();
    $AD=true;
    $SQLITE=false;
    $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    $EnablePostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix");
    if(preg_match("#sqlite:([0-9]+)#", $dn,$re)){
        $ID=intval($re[1]);
        $AD=false;
        $SQLITE=true;
        $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/localadmins/groups/$ID/privileges")),true);
        $privs="";
        if(is_array($resp) && !empty($resp["success"]) && is_array($resp["data"] ?? null)){
            $privs=strval($resp["data"]["privileges"] ?? "");
        }
        $hash["ArticaGroupPrivileges"]=base64_decode($privs);

    }

    if($AD){
        $ct=new external_ad_search(null,$ActiveDirectoryIndex);
        $hash=$ct->LoadGroupDataByDN($dn);

    }





    $data=$hash["ArticaGroupPrivileges"];
    $ldap=new clladp();

    VERBOSE("$dn ".strlen($data)." bytes",__LINE__);

    $HashPrivieleges=$ldap->_ParsePrivieleges($data);
    if($_SESSION["uid"]==-100){
        $priv=new privileges();
        $priv->AsArticaAdministrator=true;
        $priv->AsFirewallManager=true;
        $priv->AsVPNManager=true;
    }

    $form[]=$tpl->field_section("{groups_allow}");
    $form[]=$tpl->field_hidden("privs", $dn);


    $trings="AllowAddUsers,AllowAddGroup,AsOrgAdmin";
    $f=explode(",",$trings);
    foreach ($f as $priv){
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
    $trings="AsArticaAdministrator,AsFirewallManager,AsVPNManager,AsDnsAdministrator,AsDnsMonitor,ASDCHPAdmin,AsCertifsManager,AsDatabaseAdministrator,AsSambaAdministrator";
    // Artica Meta multi-tenant : administrateur des tenants (clients) dont le groupe est proprietaire.
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMeta"))==1){$trings.=",AsMetaAdmin";}
    $f=explode(",",$trings);
    foreach ($f as $priv){
        if(!isset($HashPrivieleges[$priv])){$HashPrivieleges[$priv]=0;}
        $form[]=$tpl->field_checkbox($priv,"{{$priv}}",$HashPrivieleges[$priv],false,null,false,($priv=="AsMetaAdmin")?"AsSystemAdministrator":$priv);
        //$name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null

    }

    $html[]=$tpl->form_outside("{privileges}", @implode("\n", $form),null,"{apply}","blur()","AsSystemAdministrator");

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

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

    // Pentest 2026-11-04 : privileges-categories (DN) etait concatene dans la requete.
    $results=$q->QUERY_SQL_PARAMS("SELECT id,category_id FROM personal_categories_privs WHERE dngroup=$1",array((string)$dn));
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
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function privileges_categories_enable(){
    if(grpadagent_privs_write_denied("grant personal categories",false)){return false;}
    $tpl=new template_admin();
    $dn=$_GET["privileges-categories-enable"];
    $category_id=intval($_GET["category_id"]);
    $q=new postgres_sql();
    $q->create_personal_categories();
    // Pentest 2026-11-04 : privileges-categories-enable (DN) etait concatene dans la requete
    // (et echappe a la mode MySQL dans l'INSERT, ce qui ne protege rien sous PostgreSQL).
    $res=$q->QUERY_SQL_PARAMS("SELECT id FROM personal_categories_privs WHERE dngroup=$1 and category_id=$2",array((string)$dn,$category_id));
    $ligne=$res?pg_fetch_assoc($res):array();
    if(!is_array($ligne)){$ligne=array();}

    $id=intval($ligne["id"] ?? 0);
    if($id==0){
        $q->QUERY_SQL_PARAMS("INSERT INTO personal_categories_privs (category_id,dngroup)
        VALUES ($1,$2)",array($category_id,(string)$dn));
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
        return;
    }
    $q->QUERY_SQL("DELETE FROM personal_categories_privs WHERE id=$id");



}
function privileges_save(){
    if(grpadagent_privs_write_denied("define Web-console privileges",true)){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ldap=new clladp();
    $dn=$_POST["privs"];
    $SQLITE=false;
    $AD=true;
    if(preg_match("#sqlite:([0-9]+)#", $dn,$re)){
        $ID=intval($re[1]);
        $AD=false;
        $SQLITE=true;
        $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/localadmins/groups/$ID/privileges")),true);
        $privs="";
        if(is_array($resp) && !empty($resp["success"]) && is_array($resp["data"] ?? null)){
            $privs=strval($resp["data"]["privileges"] ?? "");
        }
        $hash["ArticaGroupPrivileges"]=base64_decode($privs);
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

    if(is_array($ArticaGroupPrivileges)){
        foreach ($ArticaGroupPrivileges as $num=>$val){$GroupPrivilege[$num]=$val;}
    }

    foreach ($_POST as $num=>$val){
        if(!is_numeric($val)){continue;}
        if($val==1){$val="yes";}else{$val="no";}
        $GroupPrivilege[$num]=$val;
    }
    foreach ($GroupPrivilege as $num=>$val){
        if($val=="no"){writelogs("[$num]=SKIP",__FUNCTION__,__FILE__,__LINE__);continue;}
        writelogs("[$num]=\"$val\"",__FUNCTION__,__FILE__,__LINE__);
        $GroupPrivilegeNew[]="[$num]=\"$val\"";
    }

    $values=@implode($GroupPrivilegeNew, "\n");

    if($SQLITE){
        $values=base64_encode($values);
        $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/localadmins/groups/$ID/privileges",
            array("privileges"=>$values))),true);
        if(!is_array($resp) || empty($resp["success"])){
            echo is_array($resp)?strval($resp["error"] ?? "unknown error"):"daemon unreachable";
        }
        admin_tracks("Modified privileges of local administrators group ID=$ID");
        return;

    }

    $gp=new external_ad_search();
    $gp->SaveGroupPrivileges($values,$dn);
    return;



}



function ChangeName(){
    if(grpadagent_privs_write_denied("rename",true)){return false;}
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

// Ecriture de privileges de groupe (blob ArticaGroupPrivileges, tenants SIEM, categories, ACL,
// reverse-proxy manage) : administrateur systeme seulement, controle COTE SERVEUR -- le verrou
// des tuiles/cases n'est qu'un affichage. Sans lui, tout compte connecte pouvait s'accorder
// AsArticaMetaAdmin ou AsSystemAdministrator sur son propre groupe. Rend true = REFUSE.
function grpadagent_privs_write_denied($what,$post=false):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    $tpl=new template_admin();
    $target=isset($_POST["privs"])?(string)$_POST["privs"]:"";
    admin_tracks("Refused: $what on group $target (not a system administrator)");
    if($post){echo $tpl->post_error("{ERROR_NO_PRIVS}");}else{$tpl->js_error("{ERROR_NO_PRIVS}");}
    return true;
}
