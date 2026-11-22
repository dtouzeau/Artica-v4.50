<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : connection-id etait recopie sans encodage dans la page (XSS).
if(isset($_GET["connection-id"])){$_GET["connection-id"]=intval($_GET["connection-id"]);}
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
    $urlExtension   = urlExtension();
    $ct             = new external_ad_search(null,ActiveDirectoryIndex());
    $hash           = $ct->member_infos($dn);
    // $hash[0] etait lu sans garde: annuaire injoignable ou DN inconnu
    // declenchait trois avertissements et un titre de modale vide.
    $DisplayName=madm_attr($hash,"samaccountname");
    if($DisplayName===""){$DisplayName=(string)$dn;}
    $tpl=new template_admin();
    $dn=urlencode($dn);
    // Pentest 2026-11-04 : profile-dump etait recopie sans encodage dans la page (XSS).
    // Le titre est decode une fois (html_entity_decode) par javascript_parse_text puis rendu en HTML.
    $tpl->js_dialog5(req_html(req_html($DisplayName)), "$page?profile-dump-popup=$dn$urlExtension");

}

function urlExtension():string{
    $urlExtension               = "";
    if(isset($_GET["connection-id"])){
        $ActiveDirectoryIndex=intval($_GET["connection-id"]);
        $urlExtension="&connection-id=$ActiveDirectoryIndex";
    }
    if(isset($_GET["function"])){
        // $_GET["function"] part BRUT dans une chaine JavaScript (LoadAjaxTiny, OnClick):
    // liste blanche a la lecture. search_block n'engendre que des noms en ss<time>.
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
    $urlExtension   = urlExtension();
    $ct             = new external_ad_search(null,ActiveDirectoryIndex());
	$hash           = $ct->member_infos($dn);
	// Dernier des quatre acces non gardes a $hash[0]: sans annuaire joignable,
	// la modale s'ouvrait avec un titre vide apres trois avertissements.
	$DisplayName=madm_attr($hash,"samaccountname");
	if($DisplayName===""){$DisplayName=(string)$dn;}
	$tpl=new template_admin();
	$dn=urlencode($dn);
	// Pentest 2026-11-04 : dn etait recopie sans encodage dans la page (XSS).
	// Le titre est decode une fois (html_entity_decode) par javascript_parse_text puis rendu en HTML.
	$tpl->js_dialog4(req_html(req_html($DisplayName)), "$page?tabs=$dn$urlExtension");
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
		// Pentest 2026-11-04 : uid et ou etait recopie sans encodage dans la page (XSS).
		$uid_title=" &raquo; {member} ".req_html(req_html($uid));
	}
	$ou_title=req_html(req_html($ou));
	$tpl->js_dialog5("{new_group} &raquo; {organization} $ou_title{$uid_title}","$page?new-group-popup=yes&ou=$ouenc&uid=$uidenc");
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
	$tpl->js_dialog5("{link_group} &raquo; {organization} $ou_title{$uid_title}","$page?link-group-popup=yes&ou=$ouenc&uid=$uidenc");
	
}
function link_group_perform(){
    if(mbrad_membership_denied("add a member to a group",false)){return false;}
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
    if(mbrad_membership_denied("remove a member from a group",false)){return false;}
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


/**
 * Lit un attribut LDAP renvoye par external_ad_search::member_infos().
 *
 * member_infos() renvoie un tableau VIDE quand le DN est introuvable ou que
 * l'annuaire est injoignable, et l'annuaire omet les attributs non renseignes.
 * Quatre fonctions de ce fichier lisaient $hash[0]["attr"][0] sans garde.
 */
function madm_attr($hash,$key,$default=""){
    if(!is_array($hash) || !isset($hash[0]) || !is_array($hash[0])){return $default;}
    if(!isset($hash[0][$key])){return $default;}
    $v=$hash[0][$key];
    if(is_array($v)){return isset($v[0])?(string)$v[0]:$default;}
    return (string)$v;
}

function link_group_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ou=$_GET["ou"];
	$uid=$_GET["uid"];
	$uidenc=urlencode($uid);
	$group = new groups ( );
	$hash_group = $group->list_of_groups ( $ou, 1 );
	if(!is_array($hash_group)){$hash_group=array();}
	$users = new user($uid);
	$usergroups = $users->Groups_list();
	if(!is_array($usergroups)){$usergroups=array();}

	$rows=array();
	foreach ($hash_group as $gpid=>$GroupName){
		if(isset($usergroups[$gpid])){continue;}
		$rows[]=madm_linkgroup_row($gpid,$GroupName,$page,$uidenc,$tpl);
	}
	$shown=count($rows);

	$html=array();
	$html[]="<div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{groupname}</th>";
	$html[]="<th class='center'>{add}</th>";
	$html[]="</tr></thead><tbody id='madm-link-rows'>";
	if($shown==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown</span>";
	$html[]="<span class='awc-pg' id='madm-link-pager'></span>";
	$html[]="</div></div>";

	// Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"madm-link-rows","madm-link-pager");

}

/** Un groupe auquel le membre peut etre rattache. */
function madm_linkgroup_row($gpid,$GroupName,$page,$uidenc,$tpl):string{
	$md5=md5($gpid);
	// Le nom du groupe vient de l'annuaire et partait BRUT dans le HTML.
	$h ="<tr id='$md5'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='far fa-users'></i></span>";
	$h.="<span class='awc-name'>".htmlspecialchars((string)$GroupName)."</span>";
	$h.="</div></td>";
	$h.="<td class='center'><div class='awc-acts'>"
		.$tpl->icon_select("Loadjs('$page?link-group-perform=".urlencode($gpid)."&uid=$uidenc&md=$md5')","AllowAddGroup")
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
		$uid_title=" {member} &raquo; ".req_html($_GET["uid"]);
		$uidenc=urlencode($_GET["uid"]);}
	// Pentest 2026-11-04 : valeur encodee au centre par field_hidden/field_info/js_confirm_delete (XSS).
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
    if(mbrad_membership_denied("create a group for a member",true)){return false;}
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
	$ActiveDirectoryIndex="None";$urlExtension=null;
	if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
	if(isset($_GET["function"])){
		// Meme liste blanche que dans urlExtension(): ce nom finit dans du JS.
		$fname=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);
		if($fname!==""){$urlExtension=$urlExtension."&function=$fname";}
	}
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ct=new external_ad_search(null,$ActiveDirectoryIndex);
	$hash=$ct->member_infos($dn);

	// Un libelle d'onglet vide rend l'onglet invisible: on retombe sur le nom
	// commun puis sur le DN.
	$DisplayName=madm_attr($hash,"samaccountname");
	if($DisplayName===""){$DisplayName=madm_attr($hash,"cn");}
	if($DisplayName===""){$DisplayName=(string)$dn;}
	$array=array();
	// Pentest 2026-11-04 : tabs etait recopie sans encodage dans la page (XSS).
	$array[req_html($DisplayName)]="$page?profile=$dnenc$urlExtension";
	$array["{groups2}"]="$page?groups=$dnenc$urlExtension";
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
    $ct=new external_ad_search();
    $hash=$ct->member_infos($dn);

    // $hash[0] etait lu sans garde, puis parcouru: foreach() sur autre chose
    // qu'un tableau est FATAL sous PHP 8.
    if(!isset($hash[0]) || !is_array($hash[0])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{user_not_found}: ".htmlspecialchars((string)$dn)));
        return;
    }
    $array=$hash[0];

    // Attributs de service, sans interet pour l'administrateur.
    $skip=array("count"=>true,"dn"=>true,"objectsid"=>true,"objectguid"=>true);

    $rows=array();
    foreach ($array as $index=>$minz){
        if(isset($skip[$index])){continue;}
        if(is_numeric($index)){continue;}
        // Ce test venait APRES l'emission de "<tr><td>...</td><td>": un
        // attribut sans compteur laissait une ligne ouverte et une cellule
        // pendante dans le tableau.
        if(!is_array($minz) || !isset($minz["count"])){continue;}
        $rows[]=madm_dump_row($index,$minz);
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

    // Le <script> est concatene APRES l'analyse, comme partout ailleurs: la
    // regex #{(.+?)}# de _ENGINE_parse_body lit toute accolade JavaScript comme
    // un {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();</script>";
}

/**
 * Un attribut LDAP brut et ses valeurs. La boucle allait jusqu'a "count+1" --
 * un cran de trop, rattrape par un isset() a chaque tour.
 */
function madm_dump_row($index,$minz):string{
    $values=array();
    $count=intval($minz["count"]);
    for($i=0;$i<$count;$i++){
        if(!isset($minz[$i])){continue;}
        $values[]=htmlspecialchars((string)$minz[$i]);
    }

    $h ="<tr>";
    $h.="<td class='awc-right awc-nowrap'><strong>".htmlspecialchars((string)$index)."</strong></td>";
    $h.="<td class='awc-mono'>".(count($values)>0
        ?@implode("<br>",$values)
        :"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="</tr>";
    return $h;
}

function profile(){
	$dn=$_GET["profile"];
    $dnenc=urlencode($dn);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ct=new external_ad_search();
	$urlExtension=urlExtension();
	$hash=$ct->member_infos($dn);
	// $hash[0] etait lu sans garde, puis chacun de ses attributs.
	if(!isset($hash[0]) || !is_array($hash[0])){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("{user_not_found}: ".htmlspecialchars((string)$dn)));
		return;
	}
	$array=$hash[0];


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

    $useraccountcontrol_value=intval($array["useraccountcontrol"][0]);
    $lastlogoff=$tpl->icon_nothing();
    $lastlogon=$tpl->icon_nothing();
    $pwdlastset=$tpl->icon_nothing();
    $lastlogontimestamp=$tpl->icon_nothing();
    if(!isset($array["lastlogoff"])){$array["lastlogoff"][0]=0;}
    if(!isset($array["lastlogon"])){$array["lastlogon"][0]=0;}
    if(!isset($array["pwdlastset"])){$array["pwdlastset"][0]=0;}
    if(!isset($array["accountexpires"])){$array["accountexpires"][0]=0;}
    if(!isset($array["telephonenumber"])){$array["telephonenumber"][0]=0;}
    if(!isset($array["mobile"])){$array["mobile"][0]=0;}
    if(!isset($array["description"])){$array["description"][0]=0;}


    if(intval($array["lastlogoff"][0])>0){
        $lastlogoff=convertWindowsTimestamp($array["lastlogoff"][0]);
        $lastlogoff=$lastlogoff.")".$tpl->time_to_date($lastlogoff)." <small>(".distanceOfTimeInWords($lastlogoff,time()).")</small>";
    }
    if(intval($array["lastlogon"][0])>0){
        $lastlogon=convertWindowsTimestamp($array["lastlogon"][0]);
        $lastlogon=$tpl->time_to_date($lastlogon)." <small>(".distanceOfTimeInWords($lastlogon,time())."</small>)";

        if(intval($array["lastlogontimestamp"][0])>0) {
            $lastlogontimestamp = convertWindowsTimestamp($array["lastlogontimestamp"][0]);
            $lastlogontimestamp = $tpl->time_to_date($lastlogontimestamp) . " <small>(" . distanceOfTimeInWords($lastlogontimestamp, time()) . "</small>)";
        }

    }
    if(intval($array["pwdlastset"][0])>0){
        $pwdlastset=convertWindowsTimestamp($array["pwdlastset"][0]);
        // Une ligne recalculant $lastlogon trainait ici: copier-coller du bloc
        // lastlogon ci-dessus. Elle etait fausse dans les deux cas de figure.
        //  - compte deja connecte: $lastlogon contenait deja la date FORMATEE,
        //    qu'elle reformatait une seconde fois;
        //  - compte jamais connecte (lastlogon=0, donc tout compte fraichement
        //    cree): $lastlogon valait encore icon_nothing(), soit
        //    "<i class='fa fa-minus'></i>". time_to_date() prenait le tiret de
        //    "fa-minus" pour une date, appelait new DateTime() dessus et
        //    l'exception non attrapee renvoyait un 500 a corps vide.
        // pwdlastset est deja calcule juste au-dessus: rien d'autre a faire ici.
        $pwdlastset=$tpl->time_to_date($pwdlastset)." <small>(".distanceOfTimeInWords($pwdlastset,time())."</small>)";
    }

    if(!isset($array["name"][0])){
        $array["name"][0]="";
    }
    if(!isset($array["givenname"][0])){
        $array["givenname"][0]="";
    }
    if(!isset($array["samaccountname"][0])){
        $array["samaccountname"][0]="";
    }
    if(!isset($array["samaccounttype"][0])){
        $array["samaccounttype"][0]="";
    }
    if(!isset($array["accountexpires"][0])){
        $array["accountexpires"][0]=0;
    }
    $smtype="";
    $samaccounttype=intval($array["samaccounttype"][0]);

    // "N'expire jamais" a DEUX representations dans Active Directory: accountExpires
    // vaut 0, ou la valeur maximale d'un entier signe 64 bits (0x7FFFFFFFFFFFFFFF =
    // 9223372036854775807). Converties comme une date reelle, elles s'affichaient en
    // "30828 Thursday September 14 (over 28821 years)". La comparaison se fait en
    // flottant plutot qu'en entier: la sentinelle est a la limite de PHP_INT_MAX et
    // certains annuaires renvoient une valeur voisine.
    $rawexpires=trim((string)$array["accountexpires"][0]);
    if($rawexpires==="" || $rawexpires==="0" || (float)$rawexpires>=9.2233720368547758e18){
        $accountexpires="{never}";
    }else{
        $accountexpires=convertWindowsTimestamp($rawexpires);
        $accountexpires=$tpl->time_to_date($accountexpires)." <small>(".distanceOfTimeInWords($accountexpires,time())."</small>)";
    }
    if(isset($UserAccountControl[$samaccounttype])) {
        $smtype="{".$UserAccountControl[$samaccounttype]."}";
    }

    $desc="";
    if(strlen($array["description"][0])>1){
        $desc=$array["description"][0];
    }
    $tpl->table_form_section($array["samaccountname"][0],"$desc $smtype");
    $tpl->table_form_field_text("{CommonName}",$array["cn"][0],ico_user);
    $tpl->table_form_field_text("{givenname}",$array["givenname"][0],ico_user);
    $tpl->table_form_field_text("{name}",$array["name"][0],ico_user);
    $tpl->table_form_field_text("{accountname}",$array["samaccountname"][0],ico_user);
    $tpl->table_form_field_text("{telephoneNumber}",$array["telephonenumber"][0],"fa-solid fa-phone-office");
    $tpl->table_form_field_text("{mobile}",$array["mobile"][0],"fa-solid fa-mobile");


    $tpl->table_form_section("{options}");
    // Pentest 2026-11-04 : profile etait recopie sans encodage dans la page (XSS).
    $tpl->table_form_field_text("{DN}","<small>".req_html($dn)."</small>",ico_microsoft);
    $tpl->table_form_field_text("{uid}",$array["samaccountname"][0],ico_member);
    $tpl->table_form_field_text("{lastlogon}"," <small>$lastlogon {or} $lastlogontimestamp</small>",ico_timeout);
    $tpl->table_form_field_text("{lastlogoff}",$lastlogoff,"fa-solid fa-arrow-up-left-from-circle");
    $tpl->table_form_field_text("{password}","<small>{pwdlastset} $pwdlastset</small>",ico_lock);
    $tpl->table_form_field_text("{expire}",$accountexpires,ico_timeout);


    if(isset($UserAccountControl[$useraccountcontrol_value])) {
        $tpl->table_form_field_text("{useraccountcontrol}","{".$UserAccountControl[$useraccountcontrol_value]."}",ico_administrator);
    }



	
	$tpl->table_form_button("{more}","Loadjs('$page?profile-dump=$dnenc{$urlExtension}')","AllowAddUsers");
	echo $tpl->table_form_compile(2);
}
function convertWindowsTimestamp($wintime) {
    return $wintime / 10000000 - 11644477200;
}

function groups(){
	$uid=urlencode($_GET["groups"]);
	$page=CurrentPageName();
	$ActiveDirectoryIndex="None";$urlExtension=null;
	if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
	if(isset($_GET["function"])){
		// Meme liste blanche que dans urlExtension(): ce nom finit dans du JS.
		$fname=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);
		if($fname!==""){$urlExtension=$urlExtension."&function=$fname";}
	}
	echo "<div id='user-groups-table' style='margin-top:10px'></div>
	<script>LoadAjaxTiny('user-groups-table','$page?groups-table=$uid$urlExtension');</script>		
	";
	
}

function groups_table(){
	$dn=$_GET["groups-table"];
	$ActiveDirectoryIndex="None";$urlExtension="";
	if(isset($_GET["connection-id"])){
		$ActiveDirectoryIndex=$_GET["connection-id"];
		$urlExtension="&connection-id=".intval($ActiveDirectoryIndex);
	}
	if(isset($_GET["function"])){
		// Ce nom est reinjecte dans des attributs OnClick.
		$urlExtension.="&function=".preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);
	}
	$page=CurrentPageName();
	$tpl=new template_admin();

	$ct=new external_ad_search(null,$ActiveDirectoryIndex);
	$groups=$ct->GroupsOfMember($dn);
	if(!is_array($groups)){$groups=array();}

	$rows=array();
	// La boucle nommait sa cle $dn -- la MEME variable que le parametre de la
	// fonction, qui se retrouvait ecrasee par le dernier groupe parcouru.
	foreach ($groups as $groupDN=>$GroupName){
		$rows[]=madm_membergroup_row($groupDN,$GroupName,$urlExtension,$tpl);
	}
	$shown=count($rows);

	$html=array();
	$html[]="<div class='awc-card'><div class='awc-scroll'>";
	// L'id table-my-userid-groups est une SENTINELLE, pas une ancre de style: il
	// avait disparu lors du passage au design awc, et les DEUX lecteurs restants
	// -- link_group_perform() et le $jsafter de new_group_popup() -- testent
	// document.getElementById('table-my-userid-groups') pour decider de
	// rafraichir la liste. Le test etant devenu toujours faux, rattacher un
	// membre a un groupe ou en creer un ne mettait plus la liste a jour, sans la
	// moindre erreur nulle part.
	$html[]="<table id='table-my-userid-groups' class='awc-tbl'><thead><tr>";
	$html[]="<th>{groups2}</th>";
	$html[]="</tr></thead><tbody id='madm-grp-rows'>";
	if($shown==0){
		$html[]="<tr><td><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown</span>";
	$html[]="<span class='awc-pg' id='madm-grp-pager'></span>";
	$html[]="</div></div>";

	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"madm-grp-rows","madm-grp-pager");
}

/** Un groupe auquel le membre appartient. */
function madm_membergroup_row($groupDN,$GroupName,$urlExtension,$tpl):string{
	$md=md5($groupDN.$GroupName);
	// Le DN partait NON ENCODE dans l'URL: une virgule ou une esperluette dans
	// le nom distingue -- "CN=Doe, John,OU=..." est courant en Active Directory
	// -- tronquait le parametre et le groupe ressortait introuvable.
	$js="Loadjs('fw.groups.ad.php?dn=".urlencode($groupDN)."$urlExtension')";

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fad fa-users'></i></span>";
	$h.="<span><span class='awc-name'>";
	$h.=$tpl->td_href(htmlspecialchars((string)$GroupName),"{click_to_edit}",$js)."</span>";
	$h.="<div class='awc-meta awc-mono'>".htmlspecialchars((string)$groupDN)."</div>";
	$h.="</span></div></td>";
	$h.="</tr>";
	return $h;
}

// Appartenance aux groupes (ajout, retrait, nouveau groupe du membre) : administrateur systeme
// seulement, controle COTE SERVEUR. Sans lui, tout compte connecte pouvait s'ajouter a un groupe
// administrateur et heriter de ses droits. Rend true = REFUSE.
function mbrad_membership_denied($what,$post=false):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    $tpl=new template_admin();
    admin_tracks("Refused: $what (not a system administrator)");
    if($post){echo $tpl->post_error("{ERROR_NO_PRIVS}");}else{$tpl->js_error("{ERROR_NO_PRIVS}");}
    return true;
}
