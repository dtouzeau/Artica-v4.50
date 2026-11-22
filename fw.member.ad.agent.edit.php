<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.ad-agent.inc');
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["profile"])){profile();exit;}
if(isset($_GET["profile-dump"])){profile_dump_js();exit;}
if(isset($_GET["profile-dump-popup"])){profile_dump_popup();exit;}

if(isset($_POST["NewGroup"])){new_group_save();exit;}
if(isset($_POST["uid"])){profile_save();exit;}
if(isset($_GET["groups"])){groups();exit;}
if(isset($_GET["groups-table"])){groups_table();exit;}
if(isset($_GET["new-group-js"])){new_group_js();exit;}
if(isset($_GET["new-group-popup"])){new_group_popup();exit;}

if(isset($_GET["link-group-js"])){link_group_js();exit;}
if(isset($_GET["link-group-popup"])){link_group_popup();exit;}
if(isset($_GET["link-group-table"])){link_group_table();exit;}
if(isset($_GET["link-group-perform"])){link_group_perform();exit;}
if(isset($_GET["unlink-js"])){group_js_unlink();exit;}
js();

function profile_dump_js(){
    $page           = CurrentPageName();
    $dn             = $_GET["profile-dump"];

    $DisplayName=$_GET["name"];
    $tpl=new template_admin();
    $dn=urlencode($dn);
    // Pentest 2026-11-04 : name etait recopie sans encodage dans la page (XSS).
    $tpl->js_dialog5(req_html(req_html($DisplayName)), "$page?profile-dump-popup=$dn");

}

function urlExtension():string{
    $urlExtension               = "";
    if(isset($_GET["connection-id"])){
        $ActiveDirectoryIndex=intval($_GET["connection-id"]);
        $urlExtension="&connection-id=$ActiveDirectoryIndex";
    }
    if(isset($_GET["function"])){
        // $_GET["function"] part BRUT dans une chaine JavaScript (LoadAjaxTiny,
        // OnClick): liste blanche a la lecture. search_block n'engendre que des
        // noms en ss<time>.
        $fname=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);
        if($fname!==""){$urlExtension=$urlExtension."&function=$fname";}
    }

    return $urlExtension;

}

function ActiveDirectoryIndex(){
    $ActiveDirectoryIndex       = "None";
    if(isset($_GET["connection-id"])){
        $ActiveDirectoryIndex=intval($_GET["connection-id"]);
    }
    return $ActiveDirectoryIndex;
}

function js(){
    $page           = CurrentPageName();
    $dn             = $_GET["dn"];

    $DisplayName=$_GET["name"];
    $tpl=new template_admin();
    $dn=urlencode($dn);
    $DisplayNameEnc=urlencode($DisplayName);
    // Pentest 2026-11-04 : name etait recopie sans encodage dans la page (XSS).
    $tpl->js_dialog4(req_html(req_html($DisplayName)), "$page?tabs=$dn&name=$DisplayNameEnc");
}
function new_group_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $uidenc=null;
    $ou=$_GET["ou"];
    $ouenc=urlencode($ou);
    $uid=$_GET["uid"];
    if($uid<>null){
        $uidenc=urlencode($uid);
        $uid_title=" &raquo; {member} ".req_html(req_html($uid));
    }
    // Pentest 2026-11-04 : ou/uid etait recopie sans encodage dans la page (XSS).
    $tpl->js_dialog5("{new_group} &raquo; {organization} ".req_html(req_html($ou)).$uid_title,"$page?new-group-popup=yes&ou=$ouenc&uid=$uidenc");
}

function link_group_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $uidenc=null;
    $ou=$_GET["ou"];
    $ouenc=urlencode($ou);
    $uid=$_GET["uid"];
    if($uid<>null){
        $uidenc=urlencode($uid);
        $uid_title=" &raquo; {member} ".req_html(req_html($uid));
    }
    // Pentest 2026-11-04 : ou/uid etait recopie sans encodage dans la page (XSS).
    $tpl->js_dialog5("{link_group} &raquo; {organization} ".req_html(req_html($ou)).$uid_title,"$page?link-group-popup=yes&ou=$ouenc&uid=$uidenc");

}
function link_group_perform(){
    if(mbradagent_membership_denied("add a member to a group",false)){return false;}
    header("content-type: application/x-javascript");
    $gpid=$_GET["link-group-perform"];
    // $md est reinjecte dans $('#<md>').remove(): c'est toujours un md5.
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $uid=$_GET["uid"];
    $page=CurrentPageName();
    $uidenc=urlencode($uid);
    $ldap=new clladp();
    if($ldap->AddUserToGroup($gpid, $uid)){
        $js[]="$('#$md').remove();";
        $js[]="if(document.getElementById('table-my-userid-groups')){ LoadAjaxTiny('user-groups-table','$page?groups-table=$uidenc');}";
        $js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
        echo @implode("\n", $js);
    }

}

function group_js_unlink(){
    if(mbradagent_membership_denied("remove a member from a group",false)){return false;}
    $gpid=$_GET["unlink-js"];
    // $md est reinjecte dans $('#<md>').remove(): c'est toujours un md5.
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $uid=$_GET["uid"];
    $ldap=new clladp();
    if($ldap->GroupDeleteUser($gpid, $uid)){
        $js[]="$('#$md').remove();";
        $js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
        echo @implode("\n", $js);
    }

}

function link_group_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ou=$_GET["ou"];
    $uid=$_GET["uid"];
    $uidenc=urlencode($uid);
    $group = new groups ( );
    $hash_group = $group->list_of_groups ( $ou, 1 );
    // Ces deux listes partaient directement dans un foreach(): sous PHP 8, une
    // recherche infructueuse rend autre chose qu'un tableau et la page fatale.
    if(!is_array($hash_group)){$hash_group=array();}
    $users = new user($uid);
    $usergroups = $users->Groups_list();
    if(!is_array($usergroups)){$usergroups=array();}

    $rows=array();
    foreach ($hash_group as $gpid=>$GroupName){
        if(isset($usergroups[$gpid])){continue;}
        $rows[]=maae_linkgroup_row($gpid,$GroupName,$page,$uidenc,$tpl);
    }
    $shown=count($rows);

    $html=array();
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    // L'id table-link-userid-groups ne sert plus a footable, mais un id de
    // tableau est une sentinelle dans cette famille de pages et le perdre ne
    // produit aucune erreur, seulement du silence.
    $html[]="<table id='table-link-userid-groups' class='awc-tbl'><thead><tr>";
    $html[]="<th>{groupname}</th>";
    $html[]="<th class='center'>{add}</th>";
    $html[]="</tr></thead><tbody id='maae-link-rows'>";
    if($shown==0){
        $html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown</span>";
    $html[]="<span class='awc-pg' id='maae-link-pager'></span>";
    $html[]="</div></div>";

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"maae-link-rows","maae-link-pager");

}

/**
 * Un groupe auquel le membre peut etre rattache.
 *
 * L'id md5 de la <tr> est CONSERVE: link_group_perform() fait
 * $('#<md5>').remove() pour retirer la ligne choisie sans recharger la liste.
 */
function maae_linkgroup_row($gpid,$GroupName,$page,$uidenc,$tpl):string{
    $md5=md5($gpid);
    $gpenc=rawurlencode((string)$gpid);
    $h ="<tr id='$md5'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='far fa-users'></i></span>";
    // Le nom du groupe vient de l'annuaire et partait BRUT dans le HTML.
    $h.="<span class='awc-name'>".htmlspecialchars((string)$GroupName)."</span>";
    $h.="</div></td>";
    $h.="<td class='center'><div class='awc-acts'>"
       .$tpl->icon_select("Loadjs('$page?link-group-perform=$gpenc&uid=$uidenc&md=$md5')","AllowAddGroup")
       ."</div></td>";
    $h.="</tr>";
    return $h;
}


function link_group_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $uidenc=null;
    $ou=$_GET["ou"];
    $ouenc=urlencode($ou);
    $uid=$_GET["uid"];
    $uidenc=urlencode($uid);

    echo "<div id='link-group-to-user'></div>
	<script>LoadAjaxTiny('link-group-to-user','$page?link-group-table=yes&ou=$ouenc&uid=$uidenc');</script>
			
	";


}

function new_group_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if($_GET["uid"]<>null){
        $uid_title=" {member} &raquo; {$_GET["uid"]}";
        $uidenc=urlencode($_GET["uid"]);}
    $form[]=$tpl->field_info("ou", "{organization}", $_GET["ou"]);
    if($_GET["uid"]<>null){$form[]=$tpl->field_info("uid", "{member}", $_GET["uid"]);}
    $form[]=$tpl->field_text("NewGroup", "{groupname}", null,true);

    $js[]="if(document.getElementById('table-my-userid-groups')){ LoadAjaxTiny('user-groups-table','$page?groups-table=$uidenc');}";
    $js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
    $js[]="dialogInstance5.close();";
    $jsafter=@implode("", $js);
    $html[]=$tpl->form_outside("{new_group}", @implode("\n", $form),null,"{add}",$jsafter,"AllowAddGroup");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function new_group_save(){
    if(mbradagent_membership_denied("create a group for a member",true)){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $group=$_POST["NewGroup"];
    $ou=$_POST["ou"];
    $uid=$_POST["uid"];

    if($ou==null){if($_SESSION["ou"]<>null){$ou=$_SESSION["ou"];}}
    $ldap=new clladp();
    $groupClass=new groups();
    $list=$groupClass->samba_group_list();

    if(is_array($list)){
        foreach ($list as $num=>$ligne){
            if(trim(strtolower($ligne))==trim(strtolower($group))){
                $tpl=new templates();
                echo $tpl->_ENGINE_parse_body('{no_samba_group_in_ou}');
                return;
            }
        }
    }

    if(!$ldap->AddGroup($group,$ou)){echo $ldap->ldap_last_error;}

    if($uid<>null){
        $generated_id=$ldap->GroupIDFromName($ou, $group);

        writelogs("Link gpid $generated_id to $uid",__FUNCTION__,__FILE__,__LINE__);
        $group=new groups($generated_id);
        if(!$group->AddUsertoThisGroup($uid)){
            echo "Failed to add user $uid into $ldap->generated_id....\n";
        }

    }


}

function tabs(){
    $dn=$_GET["tabs"];
    $dnenc=urlencode($_GET["tabs"]);
    $DisplayName=$_GET["name"];
    $page=CurrentPageName();
    $tpl=new template_admin();

    // Pentest 2026-11-04 : name etait recopie sans encodage dans la page (XSS).
    $array[req_html($DisplayName)]="$page?profile=$dnenc";
    $array["{groups2}"]="$page?groups=$dnenc";
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));
    if($LighttpdArticaClientAuth==1){
        $sarray["uid"]=$DisplayName;
        $sarray["type"]="AD";
        $sarray["dn"]=$dn;
        $sarray["displayname"]=$DisplayName;
        $sdata=urlencode(base64_encode(serialize($sarray)));
        $array["{client_certificate}"]="fw.member.client.cert.php?sdata=$sdata";
    }
    echo $tpl->tabs_default($array);
}

function profile_dump_popup(){
    $dn=$_GET["profile-dump-popup"];
    $tpl=new template_admin();
    $adagent=new ADAgent();

    $array = json_decode($adagent->getAttrOf(urlencode($dn)), TRUE);

    // $array[0]["data"] etait lu sans garde puis parcouru: foreach() sur autre
    // chose qu'un tableau est FATAL sous PHP 8.
    if(!isset($array[0]["data"]) || !is_array($array[0]["data"])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{user_not_found}: ".htmlspecialchars((string)$dn)));
        return;
    }

    // Attributs de service, sans interet pour l'administrateur.
    $skip=array("count"=>true,"dn"=>true,"objectsid"=>true,"objectguid"=>true);

    $rows=array();
    foreach ($array[0]["data"] as $index=>$minz){
        if(isset($skip[$index])){continue;}
        if(is_numeric($index)){continue;}
        $rows[]=maae_dump_row($index,$minz);
    }

    $html=array();
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{attribute}</th>";
    $html[]="<th>{value}</th>";
    $html[]="</tr></thead><tbody>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div></div>";

    // Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body lit toute accolade JavaScript comme un {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();</script>";
}

/**
 * Un attribut de l'annuaire et ses valeurs.
 *
 * L'original emettait les valeurs d'un attribut multivalue, PUIS
 * htmlspecialchars($minz) sur le tableau lui-meme: sous PHP 7 cela imprimait
 * « Array » derriere chaque liste, et sous PHP 8 c'est une TypeError qui tue la
 * fenetre entiere. Les deux cas -- valeur simple, valeurs multiples -- sont
 * desormais traites separement.
 */
function maae_dump_row($index,$minz):string{
    $values=array();
    if(is_array($minz)){
        foreach ($minz as $k=>$v){
            if($k==="count"){continue;}
            $values[]=htmlspecialchars((string)$v);
        }
    }else{
        $values[]=htmlspecialchars((string)$minz);
    }

    $h ="<tr>";
    $h.="<td class='awc-mono' style='width:1%' nowrap><strong>".htmlspecialchars((string)$index)."</strong></td>";
    $h.="<td>".(count($values)>0
        ?"<div style='word-break:break-all'>".@implode("</div><div style='word-break:break-all'>",$values)."</div>"
        :"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="</tr>";
    return $h;
}


function profile(){
    $dn=$_GET["profile"];
    $dnenc=urlencode($dn);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $encodedval=urlencode($dn);
    $adagent=new ADAgent();

    $admemberattr=$adagent->getAttrOf("$encodedval");
    $array = json_decode($admemberattr, TRUE);


    $UserAccountControl[1]="useraccountcontrol_script";
    $UserAccountControl[2]="ACCOUNTDISABLE";
    $UserAccountControl[8]="HOMEDIR_REQUIRED";
    $UserAccountControl[32]="PASSWD_NOTREQD";
    $UserAccountControl[64]="PASSWD_CANT_CHANGE";
    $UserAccountControl[128]="ENCRYPTED_TEXT_PWD_ALLOWED";
    $UserAccountControl[256]="TEMP_DUPLICATE_ACCOUNT";
    $UserAccountControl[512]="NORMAL_ACCOUNT";
    $UserAccountControl[2048]="INTERDOMAIN_TRUST_ACCOUNT";
    $UserAccountControl[4096]="WORKSTATION_TRUST_ACCOUNT";
    $UserAccountControl[8192]="SERVER_TRUST_ACCOUNT";
    $UserAccountControl[65536]="DONT_EXPIRE_PASSWORD";
    $UserAccountControl[131072]="MNS_LOGON_ACCOUNT";
    $UserAccountControl[262144]="SMARTCARD_REQUIRED";
    $UserAccountControl[524288]="TRUSTED_FOR_DELEGATION";
    $UserAccountControl[1048576]="NOT_DELEGATED";
    $UserAccountControl[2097152]="USE_DES_KEY_ONLY";
    $UserAccountControl[4194304]="DONT_REQ_PREAUTH";
    $UserAccountControl[8388608]="PASSWORD_EXPIRED";
    $UserAccountControl[16777216]="TRUSTED_TO_AUTH_FOR_DELEGATION";
    $UserAccountControl[67108864]="PARTIAL_SECRETS_ACCOUNT";

    $UserAccountControl[805306368]="NORMAL_ACCOUNT";
    $UserAccountControl[805306369]="WORKSTATION_TRUST_ACCOUNT";
    $UserAccountControl[805306370]="INTERDOMAIN_TRUST_ACCOUNT";
    $UserAccountControl[268435456]="SECURITY_GLOBAL_GROUP";
    $UserAccountControl[268435457]="DISTRIBUTION_GROUP";
    $UserAccountControl[536870912]="SECURITY_LOCAL_GROUP";
    $UserAccountControl[536870913]="DISTRIBUTION_LOCAL_GROUP";

    $useraccountcontrol_value=intval($array[0]["data"]["userAccountControl"]);
    $lastlogoff=$tpl->icon_nothing();
    $lastlogon=$tpl->icon_nothing();
    $pwdlastset=$tpl->icon_nothing();
    $lastlogontimestamp=$tpl->icon_nothing();
    if(!isset($array[0]["data"]["lastLogoff"])){$array[0]["data"]["lastLogoff"]=0;}
    if(!isset($array[0]["data"]["lastLogon"])){$array[0]["data"]["lastLogon"]=0;}
    if(!isset($array[0]["data"]["pwdLastSet"])){$array[0]["data"]["pwdLastSet"]=0;}
    if(!isset($array[0]["data"]["accountExpires"])){$array[0]["data"]["accountExpires"]=0;}
    if(!isset($array[0]["data"]["telephoneNumber"])){$array[0]["data"]["telephoneNumber"]=0;}
    if(!isset($array[0]["data"]["mobile"])){$array[0]["data"]["mobile"]=0;}
    if(!isset($array[0]["data"]["description"])){$array[0]["data"]["description"]=0;}


    if(intval($array[0]["data"]["lastLogoff"])>0){
        $lastlogoff=convertWindowsTimestamp($array[0]["data"]["lastLogoff"]);
        $lastlogoff=$lastlogoff.")".$tpl->time_to_date($lastlogoff)." <small>(".distanceOfTimeInWords($lastlogoff,time()).")</small>";
    }
    if(intval($array[0]["data"]["lastLogon"])>0){
        $lastlogon=convertWindowsTimestamp($array[0]["data"]["lastLogon"]);
        $lastlogon=$tpl->time_to_date($lastlogon)." <small>(".distanceOfTimeInWords($lastlogon,time())."</small>)";

        if(intval($array[0]["data"]["lastLogonTimestamp"])>0) {
            $lastlogontimestamp = convertWindowsTimestamp($array[0]["data"]["lastLogonTimestamp"]);
            $lastlogontimestamp = $tpl->time_to_date($lastlogontimestamp) . " <small>(" . distanceOfTimeInWords($lastlogontimestamp, time()) . "</small>)";
        }

    }
    if(intval($array[0]["data"]["pwdLastSet"])>0){
        $pwdlastset=convertWindowsTimestamp($array[0]["data"]["pwdLastSet"]);
        $lastlogon=$tpl->time_to_date($lastlogon)." <small>(".distanceOfTimeInWords($lastlogon,time())."</small>)";
        $pwdlastset=$tpl->time_to_date($pwdlastset)." <small>(".distanceOfTimeInWords($pwdlastset,time())."</small>)";
    }




    $accountexpires=convertWindowsTimestamp($array[0]["data"]["accountExpires"]);
    $samaccounttype=intval($array[0]["data"]["sAMAccountType"]);
    $accountexpires=$tpl->time_to_date($accountexpires)." <small>(".distanceOfTimeInWords($accountexpires,time())."</small>)";




    $form[]=$tpl->field_section($array[0]["data"]["sAMAccountName"].":ico=fad fa-user");
    $form[]=$tpl->field_info("{CommonName}","{CommonName}", $array[0]["data"]["cn"]);
    $form[]=$tpl->field_info("{givenname}","{givenname}", $array[0]["data"]["givenName"]);
    $form[]=$tpl->field_info("{name}","{name}", $array[0]["data"]["name"]);
    $form[]=$tpl->field_info("{samaccountname}","{member}", $array[0]["data"]["sAMAccountName"]);
    $form[]=$tpl->field_info("{telephoneNumber}","{telephoneNumber}", $array[0]["data"]["telephoneNumber"]);
    $form[]=$tpl->field_info("{mobile}","{mobile}", $array[0]["data"]["mobile"]);
    $form[]=$tpl->field_info("{description}","{description}", $array[0]["data"]["description"]);
    $form[]=$tpl->field_section("{options}:ico=fad fa-user-cog");


    $form[]=$tpl->field_info("{DN}","{DN}", $dn);
    $form[]=$tpl->field_info("{uid}","{uid}", $array[0]["data"]["sAMAccountName"]);
    $form[]=$tpl->field_info("{lastlogon}","{lastlogon}", $lastlogon);
    $form[]=$tpl->field_info("{lastlogontimestamp}","{lastlogon} ({replicate})", $lastlogontimestamp);
    $form[]=$tpl->field_info("{lastlogoff}","{lastlogoff}", $lastlogoff);
    $form[]=$tpl->field_info("{pwdlastset}","{pwdlastset}", $pwdlastset);
    $form[]=$tpl->field_info("{accountexpires}","{expire}", $accountexpires);


    if(isset($UserAccountControl[$useraccountcontrol_value])) {
        $form[]=$tpl->field_info("{useraccountcontrol}","{useraccountcontrol}","{{$UserAccountControl[$useraccountcontrol_value]}}");
    }


    if(isset($UserAccountControl[$samaccounttype])) {
        $form[] = $tpl->field_info("{type}", "{type}", "{{$UserAccountControl[$samaccounttype]}}");
    }

    $tpl->form_add_button("{more}","Loadjs('$page?profile-dump=$dnenc&name={$array[0]["data"]["name"]}')");
    echo "<p>&nbsp;</p>".$tpl->form_outside(null, @implode("\n", $form),null,null,"blur()","AllowAddUsers");
}
function convertWindowsTimestamp($wintime) {
    return $wintime / 10000000 - 11644477200;
}

function groups(){
    $uid=urlencode($_GET["groups"]);
    $page=CurrentPageName();
    echo "<div id='user-groups-table'></div>
	<script>LoadAjaxTiny('user-groups-table','$page?groups-table=$uid');</script>		
	";

}

function groups_table(){
    $dn=$_GET["groups-table"];
    $page=CurrentPageName();
    $tpl=new template_admin();

    $adagent=new ADAgent();
    $array = json_decode($adagent->getAttrOf(urlencode($dn)), TRUE);

    // memberOf est ABSENT quand le compte n'appartient a aucun groupe -- le cas
    // le plus banal. Le foreach() partait dessus sans garde: fatal sous PHP 8,
    // c'est-a-dire un spinner qui ne s'arrete jamais pour un membre sans groupe.
    $memberOf=array();
    if(isset($array[0]["data"]["memberOf"]) && is_array($array[0]["data"]["memberOf"])){
        $memberOf=$array[0]["data"]["memberOf"];
    }

    $rows=array();
    // La boucle nommait sa cle $dn -- la MEME variable que le parametre lu plus
    // haut, ecrasee des le premier tour.
    foreach ($memberOf as $groupDN=>$GroupName){
        $rows[]=maae_membergroup_row($GroupName,$tpl);
    }
    $shown=count($rows);

    $html=array();
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    // L'id table-my-userid-groups est une SENTINELLE: link_group_perform()
    // teste document.getElementById('table-my-userid-groups') pour decider de
    // rafraichir cette liste apres un rattachement. Le tableau portait un id
    // par membre (table-membersGroupof-<md5>) que PERSONNE ne lisait, donc le
    // test etait toujours faux et la liste ne se mettait jamais a jour -- sans
    // la moindre erreur. Les deux pages soeurs portent le meme id fixe.
    $html[]="<table id='table-my-userid-groups' class='awc-tbl'><thead><tr>";
    $html[]="<th>{groups2}</th>";
    $html[]="</tr></thead><tbody id='maae-grp-rows'>";
    if($shown==0){
        $html[]="<tr><td><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown</span>";
    $html[]="<span class='awc-pg' id='maae-grp-pager'></span>";
    $html[]="</div></div>";

    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"maae-grp-rows","maae-grp-pager");
}

/**
 * Un groupe auquel le membre appartient.
 *
 * $GroupName est le DN complet du groupe; le nom affichable est son premier RDN.
 */
function maae_membergroup_row($GroupName,$tpl):string{
    $GroupName=(string)$GroupName;
    $md=md5($GroupName);
    $DisplayName=explode(",", $GroupName);
    $DisplayName=str_replace("CN=", "", $DisplayName[0]);
    // Le DN partait NON ENCODE dans l'URL: une virgule ou une esperluette dans
    // le nom distingue -- "CN=Doe, John,OU=..." est courant en Active Directory
    // -- tronquait le parametre et le groupe ressortait introuvable.
    $js="Loadjs('fw.groups.ad.agent.php?dn=".urlencode($GroupName)
       ."&group_name=".urlencode($DisplayName)."')";

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fad fa-users'></i></span>";
    $h.="<span><span class='awc-name'>";
    $h.=$tpl->td_href(htmlspecialchars($DisplayName),"{click_to_edit}",$js)."</span>";
    $h.="<div class='awc-meta awc-mono'>".htmlspecialchars($GroupName)."</div>";
    $h.="</span></div></td>";
    $h.="</tr>";
    return $h;
}

// Appartenance aux groupes (ajout, retrait, nouveau groupe du membre) : administrateur systeme
// seulement, controle COTE SERVEUR. Sans lui, tout compte connecte pouvait s'ajouter a un groupe
// administrateur et heriter de ses droits. Rend true = REFUSE.
function mbradagent_membership_denied($what,$post=false):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    $tpl=new template_admin();
    admin_tracks("Refused: $what (not a system administrator)");
    if($post){echo $tpl->post_error("{ERROR_NO_PRIVS}");}else{$tpl->js_error("{ERROR_NO_PRIVS}");}
    return true;
}
