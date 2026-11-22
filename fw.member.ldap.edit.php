<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux
// d'entites. Include INCONDITIONNEL: place dans le bootstrap if(!class_exists("sockets"))
// il ne s'executerait jamais et la page mourrait sur awc_table_style() indefinie.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["profile"])){profile();exit;}
if(isset($_POST["password"])){change_password_save();exit;}
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
if(isset($_GET["ch-pass"])){change_password_js();exit;}
if(isset($_GET["ch-pass-popup"])){change_password_popup();exit;}

js();

// ── OpenLDAP REST helpers ─────────────────────────────────────────────────────
// All member/group CRUD on this page goes through the daemon's /openldap/* API
// (backend: LdapUsers → OpenLDAP local or remote). No direct LDAP from PHP here.
function openldap_member($uid){
	$r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/members/".urlencode($uid)));
	if(isset($r->Status) && $r->Status && isset($r->Data)){return $r->Data;}
	return null;
}
function openldap_displayname($uid){
	$m=openldap_member($uid);
	if($m && isset($m->displayName) && $m->displayName!=""){return $m->displayName;}
	return $uid;
}
function openldap_member_groups($uid){
	// returns associative array gid => groupname
	$r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/members/".urlencode($uid)."/groups"));
	$out=array();
	if(isset($r->Status) && $r->Status && is_array($r->Data)){
		foreach($r->Data as $row){$out[$row->gid]=$row->name;}
	}
	return $out;
}

function js(){
	$page=CurrentPageName();
	$uid=$_GET["uid"];
	$uidenc=urlencode($_GET["uid"]);
	$tpl=new template_admin();
	if(!isset($_GET["function"])){$_GET["function"]=null;}
	// Pentest 2026-11-04 : uid etait recopie sans encodage dans la page (XSS).
	// Le titre est decode une fois (html_entity_decode) par javascript_parse_text puis rendu en HTML.
	$tpl->js_dialog4(req_html(req_html(openldap_displayname($uid))), "$page?tabs=$uidenc&function={$_GET["function"]}");
}

function change_password_js():bool{
    $page=CurrentPageName();
    $uid=$_GET["ch-pass"];
    $uidenc=urlencode($_GET["ch-pass"]);
    $tpl=new template_admin();
    // Pentest 2026-11-04 : ch-pass etait recopie sans encodage dans la page (XSS).
    return $tpl->js_dialog5(req_html(req_html(openldap_displayname($uid))), "$page?ch-pass-popup=$uidenc",550);
}

function change_password_popup():bool{
    $tpl=new template_admin();
    $uid=$_GET["ch-pass-popup"];
    // Pentest 2026-11-04 : valeur encodee au centre par field_hidden/field_info/js_confirm_delete (XSS).
    $tpl->field_hidden("ch-pass",$uid);
    $form[]=$tpl->field_password2("password", "{password}", null,true);
    echo $tpl->form_outside("", @implode("\n", $form),null,
        "{apply}","dialogInstance5.close();","AllowAddUsers");
    return true;

}
function change_password_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $uid=$_POST["ch-pass"];
    if(mbrldap_account_edit_denied($uid,"change the password")){return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/openldap/members/".urlencode($uid)."/password",
        array("password"=>$_POST["password"]));
    return admin_tracks("Change LDAP password of member $uid");
}

function new_group_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $uid=null;
	$uidenc=null;
	$uid_title="";
	$ou=$_GET["ou"];
	$ouenc=urlencode($ou);
	if(isset($_GET["uid"])){	$uid=$_GET["uid"];}
	if($uid<>null){
		$uidenc=urlencode($uid);
		// Pentest 2026-11-04 : uid etait recopie sans encodage dans la page (XSS).
		$uid_title=" &raquo; {member} ".req_html(req_html($uid));
	}

	// Liste des organisations. Un groupe vit sous ou=groups,ou=<ORG>,... : il en
	// faut au moins une (sinon DN invalide "ou=,"). S'il y en a PLUSIEURS et
	// qu'aucune n'est imposee par le contexte, le choix se fait dans le popup via
	// un menu deroulant -- on n'en fige donc pas une ici.
	$ous=array();
	$r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/ous"));
	if(isset($r->Data) && is_array($r->Data)){
		foreach($r->Data as $o){$o=trim((string)$o);if($o!==""){$ous[]=$o;}}
	}
	if(count($ous)==0){
		return $tpl->js_error("{no_organization_defined_create_one_first}");
	}
	if(trim((string)$ou)=="" && count($ous)==1){$ou=$ous[0];}
	$ouenc=urlencode($ou);
	// Pentest 2026-11-04 : ou etait recopie sans encodage dans la page (XSS).
	$ou_title=req_html(req_html($ou));
	$tpl->js_dialog5("{new_group} >> {organization} $ou_title{$uid_title}","$page?new-group-popup=yes&ou=$ouenc&uid=$uidenc&function={$_GET["function"]}");
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
		// Pentest 2026-11-04 : uid et ou etait recopie sans encodage dans la page (XSS).
		$uid_title=" &raquo; {member} ".req_html(req_html($uid));
	}
	$ou_title=req_html(req_html($ou));
	$tpl->js_dialog5("{link_group} >> {organization} $ou_title{$uid_title}","$page?link-group-popup=yes&ou=$ouenc&uid=$uidenc");
	
}
function link_group_perform(){
    if(mbrldap_membership_denied("add a member to a group",false)){return false;}
	header("content-type: application/x-javascript");
	$gpid=$_GET["link-group-perform"];
	$md=$_GET["md"];
	$uid=$_GET["uid"];
	$page=CurrentPageName();
	$uidenc=urlencode($uid);
	$r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/openldap/groups/".urlencode($gpid)."/members/".urlencode($uid),array()));
	if(isset($r->Status) && $r->Status){
		$js[]="$('#$md').remove();";
		$js[]="if(document.getElementById('table-my-userid-groups')){ LoadAjaxTiny('user-groups-table','$page?groups-table=$uidenc');}";
		$js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
		echo @implode("\n", $js);
	}

}

function group_js_unlink(){
    if(mbrldap_membership_denied("remove a member from a group",false)){return false;}
	$gpid=$_GET["unlink-js"];
	// Pentest 2026-11-04 : $md est reinjecte dans $('#<md>').remove() : toujours un md5.
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	$uid=$_GET["uid"];
	$r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/openldap/groups/".urlencode($gpid)."/members/".urlencode($uid)));
	if(isset($r->Status) && $r->Status){
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
	$hash_group = array();
	$r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/groups?ou=".urlencode($ou)));
	if(isset($r->Status) && $r->Status && is_array($r->Data)){
		foreach($r->Data as $row){$hash_group[$row->gid]=$row->name;}
	}
	$usergroups = openldap_member_groups($uid);

	$rows=array();
	foreach ($hash_group as $gpid=>$GroupName){
		if(isset($usergroups[$gpid])){continue;}
		$rows[]=mldap_linkable_group_row($gpid,$GroupName,$uidenc,$tpl,$page);
	}
	$c=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	// L'id table-link-userid-groups est CONSERVE lors du passage au design awc:
	// il ne sert plus a footable, mais un id de tableau est une sentinelle dans
	// cette famille de pages et le perdre ne produit aucune erreur, seulement du
	// silence.
	$html[]="<table id='table-link-userid-groups' class='awc-tbl'><thead><tr>";
	$html[]="<th>{groupname}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='mldap-link-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_group}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='mldap-link-pager'></span>";
	$html[]="</div></div></div>";

	// Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque accolade JavaScript pour un {token}.
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(25,"mldap-link-rows","mldap-link-pager");
}

// Un groupe que le membre n'a PAS encore. L'id md5 de la <tr> est conserve:
// link_group_perform() fait $('#<md5>').remove() pour retirer la ligne choisie
// sans recharger la liste.
function mldap_linkable_group_row($gpid,$GroupName,$uidenc,$tpl,$page):string{
	$md5=md5($gpid);
	$gpenc=rawurlencode((string)$gpid);
	$sel=$tpl->icon_select("Loadjs('$page?link-group-perform=$gpenc&uid=$uidenc&md=$md5')","AllowAddGroup");

	$h ="<tr id='$md5'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-users'></i></span>";
	// Le nom du groupe vient de l'annuaire et partait BRUT dans le HTML.
	$h.="<span><span class='awc-name'>".htmlspecialchars((string)$GroupName)."</span>";
	$h.="<div class='awc-meta awc-mono'>GID: ".htmlspecialchars((string)$gpid)."</div>";
	$h.="</span></div></td>";
	$h.="<td><div class='awc-acts'>$sel</div></td>";
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
		$uid_title=" {member} &raquo; ".req_html($_GET["uid"]);
		$uidenc=urlencode($_GET["uid"]);}
	$ou=trim((string)($_GET["ou"] ?? ""));
	// Organisations disponibles.
	$ous=array();
	$r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/ous"));
	if(isset($r->Data) && is_array($r->Data)){
		foreach($r->Data as $o){$o=trim((string)$o);if($o!==""){$ous[]=$o;}}
	}
	// Contexte non fige + plusieurs organisations -> menu deroulant pour choisir.
	// Sinon organisation fixe en lecture seule (field_info soumet aussi sa valeur).
	if($ou==="" && count($ous)>1){
		$hash=array();
		foreach($ous as $o){$hash[$o]=$o;}
		$form[]=$tpl->field_array_hash($hash,"ou","nonull:{organization}",$ous[0]);
	}else{
		if($ou==="" && count($ous)>=1){$ou=$ous[0];}
		// Pentest 2026-11-04 : valeur encodee au centre par field_hidden/field_info/js_confirm_delete (XSS).
		$form[]=$tpl->field_info("ou", "{organization}", $ou);
	}
	// Pentest 2026-11-04 : valeur encodee au centre par field_hidden/field_info/js_confirm_delete (XSS).
	if($_GET["uid"]<>null){$form[]=$tpl->field_info("uid", "{member}", $_GET["uid"]);}
	$form[]=$tpl->field_text("NewGroup", "{groupname}", null,true);


    if($_GET["function"]<>null){
        $js[]="{$_GET["function"]}();";
    }
	$js[]="if(document.getElementById('table-my-userid-groups')){ LoadAjaxTiny('user-groups-table','$page?groups-table=$uidenc');}";
	$js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
	$js[]="dialogInstance5.close();";
	$jsafter=@implode("\n", $js);
	$html[]=$tpl->form_outside("{new_group}", @implode("\n", $form),null,"{add}",$jsafter,"AllowAddGroup");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function new_group_save(){
    if(mbrldap_membership_denied("create a group for a member",true)){return false;}
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$group=$_POST["NewGroup"];
	$ou=$_POST["ou"];
	$uid=$_POST["uid"];

	if($ou==null){if($_SESSION["ou"]<>null){$ou=$_SESSION["ou"];}}

	// Sans organisation, le demon construit un DN "ou=groups,ou=,dc=organizations,..."
	// dont le RDN vide est refuse par l'annuaire ("Invalid DN Syntax"). On arrete
	// ici avec un message clair plutot que de laisser filtrer l'erreur cryptique.
	if(trim((string)$ou)==""){echo $tpl->_ENGINE_parse_body("{no_organization_defined_create_one_first}");return;}
	if(trim((string)$group)==""){echo $tpl->_ENGINE_parse_body("{groupname}: {cannot_be_a_null_value}");return;}

	$payload=array("name"=>$group,"ou"=>$ou);
	if($uid<>null){$payload["uid"]=$uid;}
	$r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/openldap/groups",$payload));
	if(isset($r->Status) && !$r->Status){echo @$r->Error;}
}

function tabs(){
    clean_xss_deep();
	$uid=$_GET["tabs"];
	$uidenc=urlencode($_GET["tabs"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$DisplayName=openldap_displayname($uid);
	// Pentest 2026-11-04 : tabs etait recopie sans encodage dans la page (XSS).
	$array[req_html($DisplayName)]="$page?profile=$uidenc&function={$_GET["function"]}";
	$array["{groups2}"]="$page?groups=$uidenc&function={$_GET["function"]}";
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));
    if($LighttpdArticaClientAuth==1){
        $sarray["uid"]=$uid;
        $sarray["type"]="LLDAP";
        $sarray["displayname"]=$DisplayName;
        $sdata=urlencode(base64_encode(serialize($sarray)));
        $array["{client_certificate}"]="fw.member.client.cert.php?sdata=$sdata";
    }
	echo $tpl->tabs_default($array);
}

function profile(){
	$uid=$_GET["profile"];
    $uidEncoded=urlencode($uid);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ct=openldap_member($uid);
	if($ct==null){$ct=new stdClass();}

    $CLUSTER_CLIENT=false;
    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient");
    if($EnableLDAPSyncProv==1){
        if($EnableLDAPSyncProvClient==1){
            $CLUSTER_CLIENT=true;
        }
    }
    $btn="{apply}";
    if($CLUSTER_CLIENT){$btn=null;}

	// Pentest 2026-11-04 : valeur encodee au centre par field_hidden/field_info/js_confirm_delete (XSS).
	$form[]=$tpl->field_hidden("uid", $uid);
	$form[]=$tpl->field_info("ou", "{organization}",$ct->ou);
    $form[]=$tpl->field_info("ident", "{login_credentials}",$uid);
	$form[]=$tpl->field_text("givenName", "{firstname}", $ct->givenName,true);
	$form[]=$tpl->field_text("sn", "{lastname}", $ct->sn,true);
	$form[]=$tpl->field_text("mail", "{email}", $ct->mail,true);

	$form[]=$tpl->field_section("{work}");
	$form[]=$tpl->field_text("title", "{working_title}", $ct->title,false);
	$form[]=$tpl->field_text("telephoneNumber", "{phone}", $ct->telephoneNumber,false);
	$form[]=$tpl->field_text("mobile", "{mobile}", $ct->mobile,false);
	$jsrestart="blur();";
	if(!$CLUSTER_CLIENT){$tpl->form_add_button("{change_password}","Loadjs('$page?ch-pass=$uidEncoded')");}
	echo $tpl->form_outside(req_html($uid), @implode("\n", $form),null,$btn,"$jsrestart","AllowAddUsers");
}





function profile_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();

	$uid=$_POST["uid"];
	if(mbrldap_account_edit_denied($uid,"edit the profile")){return false;}
	$payload=array();
	foreach(array("givenName","sn","mail","title","telephoneNumber","mobile") as $k){
		if(isset($_POST[$k])){$payload[$k]=$_POST[$k];}
	}
	$GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/openldap/members/".urlencode($uid),$payload);
    admin_tracks("Saving LDAP member $uid");
    return true;
}

function groups(){
	$uid=urlencode($_GET["groups"]);
	$page=CurrentPageName();
	echo "<div id='user-groups-table'></div>
	<script>LoadAjaxTiny('user-groups-table','$page?groups-table=$uid');</script>		
	";
	
}

function groups_table(){
	$uid=$_GET["groups-table"];
	$member=openldap_member($uid);
	$ou=($member && isset($member->ou))?$member->ou:"";
	$ouenc=urlencode($ou);
	$uidenc=urlencode($uid);
	$page=CurrentPageName();
	$tpl=new template_admin();

	$CLUSTER_CLIENT=false;
	$EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
	$EnableLDAPSyncProvClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient");
	if($EnableLDAPSyncProv==1){
		if($EnableLDAPSyncProvClient==1){
			$CLUSTER_CLIENT=true;
		}
	}

	$html=array();
	if(!$CLUSTER_CLIENT) {
		$html[] = "
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>
			" . $tpl->button_label_table("{link_group}", "Loadjs('$page?link-group-js=yes&ou=$ouenc&uid=$uidenc')", "fa-plus", "AllowAddGroup")
			. $tpl->button_label_table("{new_group}", "Loadjs('$page?new-group-js=yes&ou=$ouenc&uid=$uidenc');", "fa-plus", "AllowAddGroup") . "
			</div>";
	}

	$groups = openldap_member_groups($uid);
	$rows=array();
	foreach ($groups as $gpid=>$GroupName){
		$rows[]=mldap_member_group_row($gpid,$GroupName,$ouenc,$uidenc,$CLUSTER_CLIENT,$tpl,$page);
	}
	$c=count($rows);

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	// L'id table-my-userid-groups est une SENTINELLE, pas une simple ancre
	// footable: link_group_perform() et new_group_save() testent
	// document.getElementById('table-my-userid-groups') pour decider de
	// rafraichir la liste. Le perdre en passant au design awc n'aurait produit
	// aucune erreur -- seulement une liste qui cesse de se mettre a jour apres
	// un rattachement ou une creation de groupe.
	$html[]="<table id='table-my-userid-groups' class='awc-tbl'><thead><tr>";
	$html[]="<th>{groupname}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='mldap-grp-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_group}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='mldap-grp-pager'></span>";
	$html[]="</div></div></div>";

	// Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque accolade JavaScript pour un {token}.
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(25,"mldap-grp-rows","mldap-grp-pager");
}

// Un groupe du membre. L'id md5 de la <tr> est conserve: group_js_unlink() fait
// $('#<md>').remove() pour retirer la ligne sans recharger la liste.
function mldap_member_group_row($gpid,$GroupName,$ouenc,$uidenc,$CLUSTER_CLIENT,$tpl,$page):string{
	$md=md5($gpid.$GroupName);
	$gpenc=rawurlencode((string)$gpid);
	$unlink=$tpl->icon_unlink("Loadjs('$page?unlink-js=$gpenc&ou=$ouenc&uid=$uidenc&gpid=$gpenc&md=$md')","AllowAddGroup");
	if($CLUSTER_CLIENT){$unlink=$tpl->icon_unlink();}
	$js="Loadjs('fw.groups.ldap.php?gpid=$gpenc')";

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-users'></i></span>";
	// Le nom du groupe vient de l'annuaire et partait BRUT dans le HTML.
	$h.="<span><span class='awc-name'>".$tpl->td_href(htmlspecialchars((string)$GroupName),"{click_to_edit}",$js)."</span>";
	$h.="<div class='awc-meta awc-mono'>GID: ".htmlspecialchars((string)$gpid)."</div>";
	$h.="</span></div></td>";
	$h.="<td><div class='awc-acts'>$unlink</div></td>";
	$h.="</tr>";
	return $h;
}

// Mot de passe et profil d'un membre : administrateur systeme, ou le compte lui-meme (meme uid
// que la session ; jamais le Manager -100). Controle COTE SERVEUR : le droit AllowAddUsers du
// formulaire ne protegeait que l'affichage, et tout compte delegue pouvait reinitialiser le mot de
// passe d'un membre d'un groupe administrateur (ruling R-F6). Rend true = REFUSE (post_error).
function mbrldap_account_edit_denied($uid,$what):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    $me=isset($_SESSION["uid"])?strval($_SESSION["uid"]):"";
    $uid=strval($uid);
    if($me!=="" && $me!=="-100" && $uid!=="" && $me===$uid){return false;}
    $tpl=new template_admin();
    admin_tracks("Refused: $what of LDAP member $uid (not a system administrator)");
    echo $tpl->post_error("{ERROR_NO_PRIVS}");
    return true;
}

// Appartenance aux groupes (ajout, retrait, nouveau groupe du membre) : administrateur systeme
// seulement, controle COTE SERVEUR. Sans lui, tout compte connecte pouvait s'ajouter a un groupe
// administrateur et heriter de ses droits. Rend true = REFUSE.
function mbrldap_membership_denied($what,$post=false):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    $tpl=new template_admin();
    admin_tracks("Refused: $what (not a system administrator)");
    if($post){echo $tpl->post_error("{ERROR_NO_PRIVS}");}else{$tpl->js_error("{ERROR_NO_PRIVS}");}
    return true;
}
