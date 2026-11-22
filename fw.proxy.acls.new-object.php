<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}

js();


function build_params():string{
    if(!isset($_GET["DnsDist"])){$DnsDist=0;}else{$DnsDist=intval($_GET["DnsDist"]);}
    if(!isset($_GET["ProxyPac"])){$ProxyPac=0;}else{$ProxyPac=$_GET["ProxyPac"];}
    if(!isset($_GET["firewall"])){$firewall=0;}else{$firewall=intval($_GET["firewall"]);}
    if(!isset($_GET["TableLink"])){$TableLink=null;}else{$TableLink=trim($_GET["TableLink"]);}
    if(!isset($_GET["function"])){$function=null;}else{$function=trim($_GET["function"]);}
    $fastacls=intval($_GET["fastacls"] ?? 0);
    return "&ProxyPac=$ProxyPac&DnsDist=$DnsDist&firewall=$firewall&TableLink=$TableLink&fastacls=$fastacls&function=$function";
}

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $build_params=build_params();
    $id=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    $tpl->js_dialog4("{new_object}", "$page?popup=yes&id=$id$build_params");
}


function popup(){
    $dnsfw=false;
    $Smtp=0;
    $SSLERROR=0;
    $fastacls=intval($_GET["fastacls"] ?? 0);
    $IDS=0;
    if(!isset($_GET["DnsDist"])){$DnsDist=0;}else{$DnsDist=intval($_GET["DnsDist"]);}
    if(!isset($_GET["ProxyPac"])){$ProxyPac=0;}else{$ProxyPac=$_GET["ProxyPac"];}
    if(!isset($_GET["firewall"])){$firewall=0;}else{$firewall=intval($_GET["firewall"]);}
    $EnableKerbAuth = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $EnableArticaAuthenticationAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAuthenticationAgent"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    if($WindowsActiveDirectoryKerberos==1){$EnableKerbAuth=1;}
    if($LockActiveDirectoryToKerberos==1){$EnableKerbAuth=1;}
    if($EnableArticaAuthenticationAgent==1){$EnableKerbAuth=1;}

    // Sept comparaisons sur une cle qui peut ne pas exister: autant
    // d'avertissements "Undefined array key" par ouverture de la modale.
    $TableLink=(string)($_GET["TableLink"] ?? "");
    $acl_allowdeny=false;
    if($TableLink=="webfilters_sqacllinks"){$acl_allowdeny=true;}
    if($TableLink=="dnsfw_acls_link"){$dnsfw=true;}
    if($TableLink=="dnsdist_sqacllinks"){$DnsDist=1;}
    if($TableLink=="wpad_sources_link"){$ProxyPac=1;}
    if($TableLink=="postfix_sqacllinks"){$Smtp=1;}
    if($TableLink=="sslproxy_cert_error_sqacllinks"){$SSLERROR=1;}
    if($TableLink=="suricata_sqacllinks"){$IDS=1;}


    $js=array();
    $tpl=new template_admin();
    $qProxy=new mysql_squid_builder(true);
    $proxy_object=$tpl->_ENGINE_parse_body("{proxy_objects}");
    $description=$tpl->_ENGINE_parse_body("{description}");
    // Quatre colonnes -- icone, libelle, description, bouton -- pour une liste de
    // choix: l'icone et le libelle designent la MEME chose et la description est
    // le sous-titre du libelle. Deux colonnes suffisent: l'objet, et le choix.
    $html=array();

    // Recherche cote navigateur: la liste des types d'objets depasse la
    // trentaine d'entrees et se lit mal dans une modale. awc_filter_js()
    // compare le texte de la ligne entiere -- donc le libelle, la methode
    // squid ET son explication, tous rendus en sous-titre par
    // newobject_row(). Emis en fin de fonction, APRES le pager.
    $html[]=awc_search_bar("aclsnewobjec-search","{search}");

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>$proxy_object</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='aclsnewobjec-rows'>";
    // $id est le nom du champ a remplir cote appelant; il partait BRUT dans
    // getElementById('...'), donc dans du JavaScript execute. Un ?id= forge
    // suffisait a injecter du code dans la session de l'administrateur.
    $id=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    reset($qProxy->acl_GroupType);

    if($SSLERROR==0) {
        unset($qProxy->acl_GroupType["ssl_error"]);
    }

    $TRCLASS=null;
    $method=$tpl->_ENGINE_parse_body("{method}").":";
    $select=$tpl->_ENGINE_parse_body("{select}");

    if($dnsfw){
        foreach ($qProxy->acl_GroupType_DNSFW as $num=>$value){
            $qProxy->acl_GroupType[$num]=$value;
        }
    }

    if($Smtp==1){
        foreach ($qProxy->acl_GroupType_SMTP as $num=>$value){
            $qProxy->acl_GroupType[$num]=$value;
        }
    }
    if($IDS==1){
        // unset() sur une propriete DECLAREE la supprime de l'objet: la
        // reaffectation qui suit en recree une dynamique, depreciee en PHP 8.2.
        $qProxy->acl_GroupType=array();
        foreach ($qProxy->acl_GroupType_IDS as $num=>$value){
            $qProxy->acl_GroupType[$num]=$value;
        }
    }




    if($ProxyPac==0){
        unset($qProxy->acl_GroupType["dstproxy"]);
        unset($qProxy->acl_GroupType["srcproxy"]);
        unset($qProxy->acl_GroupType["rgexsrc"]);
        unset($qProxy->acl_GroupType["rgexdst"]);
    }

    if($acl_allowdeny){
        unset($qProxy->acl_GroupType["rep_mime_type"]);
        unset($qProxy->acl_GroupType["rep_header_filename"]);
        if($EnableKerbAuth==0){
            unset($qProxy->acl_GroupType["proxy_auth_ads"]);
            unset($qProxy->acl_GroupType["proxy_auth_adou"]);
            unset($qProxy->acl_GroupType["proxy_auth_statad"]);
            unset($qProxy->acl_GroupType["proxy_auth_tagad"]);
            unset($qProxy->acl_GroupType["proxy_auth_multiad"]);
            unset($qProxy->acl_GroupType["proxy_auth_authenticated"]);

        }
    }
    $dnsdist_acls_errors=array();
    if($DnsDist==1){
        $qProxy->acl_GroupType["netbiosname"]=$tpl->_ENGINE_parse_body("{acl_netbiosname}");
        $qProxy->acl_GroupType["ptr"]=$tpl->_ENGINE_parse_body("{ptr_dst}");
        $qProxy->acl_GroupType["doh"]=$tpl->_ENGINE_parse_body("{APP_DOH_BACKEND}");
        $qProxy->acl_GroupType["webfilter"]=$tpl->_ENGINE_parse_body("{webfiltering}");
        $qProxy->acl_GroupType["dnsquerytype"]=$tpl->_ENGINE_parse_body("{dnsquerytype}");
        $qProxy->acl_GroupType["the_shields"]=$tpl->_ENGINE_parse_body("{SRN}");
        $qProxy->acl_GroupType["geoipsrc"]=$tpl->_ENGINE_parse_body("{geoipsrc}");
        $qProxy->acl_GroupType["dst"]=$tpl->_ENGINE_parse_body("{dns addresses}");
        $qProxy->acl_GroupType["opendns"]="OpenDNS";
        $qProxy->acl_GroupType["opendnsf"]="OpenDNS Family";
        $qProxy->acl_GroupType["reputation"]=$tpl->_ENGINE_parse_body("{use_reput_service}");
        $dnsdist_acls_errors=$qProxy->dnsdist_acls_errors();
    }
    if($firewall==1){
        $qProxy->acl_GroupType["dnsdynamic"]="{dnsdynamic}";
    }

    if($EnableActiveDirectoryFeature==0){
        unset($qProxy->acl_GroupType_SMTP["adfrom"]);
        unset($qProxy->acl_GroupType_SMTP["adto"]);
    }


    foreach ($qProxy->acl_GroupType as $num=>$line){
        $choose=null;
        if($fastacls==1){if(!isset($qProxy->acl_GroupType_fast[$num])){continue;}}
        if($ProxyPac==1){if(!isset($qProxy->acl_GroupType_WPAD[$num])){continue;}}
        if($DnsDist==1){
            if(!isset($qProxy->acl_GroupType_DNSDIST[$num])){
                continue;
            }
        }
        if($Smtp==1){
            if(!isset($qProxy->acl_GroupType_SMTP[$num])){
                continue;
            }
        }

        if($firewall==1){
            if(!isset($qProxy->acl_GroupType_iptables[$num])){continue;}}
        if($dnsfw){if(!isset($qProxy->acl_GroupType_DNSFW[$num])){continue;}}
        $funct="Select".md5($num);
        $explain=null;

        if(isset($qProxy->acl_GroupType_explain[$num])) {
            $explain = $tpl->javascript_parse_text($qProxy->acl_GroupType_explain[$num]);
            $explain = str_replace("\\n", " ", $explain);
            if (strlen($explain) > 83) {
                $explain = substr($explain, 0, 80) . "...";
            }
        }

        $TRCLASS=null;
        $choose="<button class='awc-btn' type='button' OnClick=\"$funct();\">$select</button>";

        $js[]="
		function $funct(){
			document.getElementById('$id').value='$num';
			dialogInstance4.close();
			}";

        if(isset($dnsdist_acls_errors[$num])){
            $choose="<button class='awc-btn' style='background:#263238' type='button' OnClick=\"blur();\">{unavailable}</button>";
            $explain="$explain<br><strong class='text-danger'>$dnsdist_acls_errors[$num]</strong>";
        }

        // Les trois cellules d'origine fermaient un </span> jamais ouvert, et la
        // derniere fermait en plus un </center> absent.
        $ico=$qProxy->acl_GroupTypeIcon[$num] ?? "fa-cube";
        $html[]=newobject_row($num,$line,$ico,$method,$explain,$choose);


    }


    if(count($js)==0){
        $html[]="<tr><td colspan='2' class='awc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div>";
	// count($js) EST le nombre de lignes: newobject_row() et $js[] sont
	// alimentes ensemble a chaque tour, sans exception -- c'est deja
	// l'hypothese du test "count($js)==0" ci-dessus.
	$html[]="<div class='awc-foot'><span>".$tpl->FormatNumber(count($js))." {objects}</span>"
	       ."<span class='awc-pg' id='aclsnewobjec-pager'></span></div>";
	$html[]="</div></div>";

    // Le bloc <script> est concatene APRES l'analyse. Il declare une fonction
    // par ligne du tableau: la regex #{(.+?)}# de _ENGINE_parse_body prenait
    // chaque corps de fonction pour un jeton et lancait autant de recherches
    // memcached qu'il y a de types d'objets.
    $script="<script>NoSpinner();\n".@implode("\n",$js)."\n</script>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    // Et le filtre APRES le pager, qui lui passe la main par tb.__awcRedraw.
    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .$script
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"aclsnewobjec-rows","aclsnewobjec-pager")
	    .awc_filter_js("aclsnewobjec-search","aclsnewobjec-rows","aclsnewobjec-pager");

}

/**
 * Un type d'objet ACL propose au choix.
 * La methode squid ($num) et son explication descendent en sous-titre du
 * libelle: elles le decrivent, elles ne se comparent pas d'une ligne a l'autre.
 */
function newobject_row($num,$line,$ico,$method,$explain,$choose):string{
    $h ="<tr id='".htmlspecialchars((string)$num)."'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fa $ico'></i></span>";
    $h.="<div><div class='awc-name'>$line</div>";
    $h.="<div class='awc-meta'><strong>$method&nbsp;".htmlspecialchars((string)$num)."</strong>";
    if(trim((string)$explain)!==""){$h.="<br>$explain";}
    $h.="</div></div></div></td>";
    $h.="<td><div class='awc-acts' style='opacity:1'>$choose</div></td>";
    $h.="</tr>";
    return $h;
}