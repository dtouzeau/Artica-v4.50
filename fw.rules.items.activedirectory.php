<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["adddn"])){add();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}

popup();

function uriext():string{
    $ext=array();
    $ext[]="&UseDN=1";

    foreach ($_GET as $num=>$val){
        if(trim($val)==null){continue;}
        if($num=="popup"){continue;}
        if($num=="search"){continue;}
        if($num=="adddn"){continue;}
        $ext[]="$num=$val";
    }
    if(count($ext)==0){return "";}
    return @implode("&",$ext);
}
function add():bool{
    $tpl=new template_admin();
    $DN=base64_decode($_GET["adddn"]);
    $md=$_GET["md"];
    $jsafter=jsafter_token($_GET["js-after"]);
    $function=$_GET["function"];
    $samaccountname=$_GET["samaccountname"];
    $gpid=intval($_GET["gpid"]);
    $description="{group}: ".base64_decode($samaccountname);
    header("content-type: application/x-javascript");
    $enabled=1;
    [$ok,$err]=acls_api_send("POST","/proxy/acls/sqlite/items",[
        "gpid"  => $gpid,
        "items" => [["pattern"=>$DN,"enabled"=>$enabled,"description"=>$description]],
    ]);
    if(!$ok){
        echo $tpl->js_error(is_string($err)?$err:"insert failed");
        return false;
    }
    echo "$('#$md').remove();\n";
    echo "Loadjs('fw.proxy.acls.bugs.php?refresh=yes');";

    if(strlen($function)>3){
        echo "$function();\n";
    }
    if(strlen($jsafter)>5){
        echo base64_decode($jsafter)."\n";
    }
    return true;
}
function popup(){
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ext=uriext();
    $html="<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>	
     </div>
     <div class='ibox-content' id='ad-$t-table'></div>
     
    <script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('ad-$t-table','$page?$ext&search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

    echo $tpl->_ENGINE_parse_body($html);


}


function search(){
    $tpl=new template_admin();
    $q=new mysql_squid_builder();
    $groups=$tpl->_ENGINE_parse_body("{groups2}");
    $domain=$tpl->_ENGINE_parse_body("{domain}");
    $html=array();
    // Quatre colonnes dont une pour la seule icone: le groupe et son domaine
    // designent le meme objet, le domaine devient le sous-titre.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>$groups</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='rulesitemsad-rows'>";
    $id=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    reset($q->acl_GroupType);
    $TRCLASS=null;
    $method=$tpl->_ENGINE_parse_body("{method}").":";
    $select=$tpl->_ENGINE_parse_body("{select}");
    if(!isset($_GET["search"])){$search="*";}else{$search=$_GET["search"];}
    if($search==null){$search="*";}
    if(strpos(" {$search}", "*")==0){$search="*{$search}*";}
    $search=str_replace("**", "*", $search);
    $search=str_replace("**", "*", $search);

    $ad=new external_ad_search();
    $Array=$ad->flexRTGroups($search,200);
    if(!is_array($Array)){$Array=array();}

    if(count($Array)==0){


        if(count($ad->TESTED_SERVERS)==0){
            $servers=" <span class='text-danger'>{error_no_server_specified}</span> ";
        }else{
            $servers=" {in} <strong>&laquo;".@implode(" {or} ",$ad->TESTED_SERVERS)."&raquo;</strong> ";
        }

        $html[]="<tr>";
        $html[]="<td colspan='2' class='awc-empty'>{your_search_results} <strong>&laquo;".htmlspecialchars($search)."&raquo;</strong>{$servers}: <span class='text-danger'>{no_data}</span></td>";
        $html[]="</tr>";
        // Sortie anticipee: elle doit fermer AUSSI les div du conteneur awc.
        $html[]="</tbody></table></div></div></div>";

        echo awc_table_style().$tpl->_ENGINE_parse_body($html);
        return;
    }

    ksort($Array);
    $page=CurrentPageName();
    $ext=uriext();
    foreach ($Array as $dn => $samaccountname){
        $choose=null;
        $funct="Select".md5($dn);
        $choose="<button class='awc-btn' type='button' OnClick=\"$funct();\">$select</button>";
        $trid=md5($dn);
        $dn_dom=array();
        $dn_temp=strtolower($dn);
        $dnexpl=explode(",",$dn_temp);
        foreach ($dnexpl as $item){
            if(!preg_match("#^dc=(.+)#", $item,$re)){continue;}
            $dn_dom[]=$re[1];
        }



        $domain=@implode(".", $dn_dom);
        $ValueToSend=$samaccountname;
        $samaccountnameenc=base64_encode($samaccountname);
        if(isset($_GET["UseDN"])){$ValueToSend=$dn;}
        $ValueToSendEnc=base64_encode($ValueToSend);
        $js[]= "function $funct(){ Loadjs('$page?adddn=$ValueToSendEnc&md=$trid&samaccountname=$samaccountnameenc&$ext'); }";



        // Les trois cellules fermaient un </span> jamais ouvert, et la derniere
        // un </center> absent.
        $html[]="<tr id='$trid'>";
        $html[]="<td><div class='awc-ident'>"
               ."<span class='awc-ico'><i class='far fa-users'></i></span>"
               ."<div><div class='awc-name'>".htmlspecialchars((string)$samaccountname)."</div>"
               ."<div class='awc-meta'>".htmlspecialchars((string)$domain)."</div>"
               ."</div></div></td>";
        $html[]="<td><div class='awc-acts' style='opacity:1'>$choose</div></td>";
        $html[]="</tr>";


    }


    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($Array)."</span>"
           ."<span class='awc-pg' id='rulesitemsad-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc <script> est concatene APRES l'analyse: il declare une fonction
    // par groupe trouve, et la regex #{(.+?)}# de _ENGINE_parse_body prenait
    // chaque corps de fonction pour un jeton a resoudre.
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n"
           .@implode("\n", $js)."\n</script>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"rulesitemsad-rows","rulesitemsad-pager");
}