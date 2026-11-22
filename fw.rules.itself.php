<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
// Nom de constante NON quoté = erreur fatale en PHP 8 : la page ne s'ouvrait plus.
if(!defined("td1prc")){define("td1prc","class='center' style='vertical-align:middle;width:1%' nowrap");}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
// Contrôle de droits côté serveur : form_outside(…,"AsFirewallManager") ne le fait que dans le navigateur.
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["pattern"])){add_final();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["add-js"])){add_js();exit;}
if(isset($_GET["add"])){add();exit;}
js();

function js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog1("{outgoing_rule}","$page?table-start=yes",550);

}
function add_js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog1("{outgoing_rule}:{new_item}","$page?add=yes",550);
}
function add(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $form[]=$tpl->field_text("pattern","{address}",null,true);
    $form[]=$tpl->field_text("port","{listen_ports}",null,true);
    $form[]=$tpl->field_text("comment","{comment}",null,false);
    $tpl->form_add_button("{cancel}","Loadjs('$page');");
    echo $tpl->form_outside("{new_item}",$form,null,"{add}","Loadjs('$page');","AsFirewallManager");
}
function delete(){
    $tpl        = new template_admin();
    $md         = preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    $ID         = intval($_GET["delete"]);
    $e=fwapi_error(fwapi_send("DELETE","itself/$ID"));
    if($e!=""){$tpl->js_error($e);return;}
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
}

function add_final(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $IP=new IP();
    if(!$IP->isIPAddressOrRange($_POST["pattern"])){
        echo (new template_admin())->post_error("{$_POST["pattern"]} not valid, should be 1.1.1.1/11 or 1.1.1.1");
        return;
    }

    if(!preg_match("#^(tcp|sctp|udp|udplite|icmp|icmp6):([0-9\-]+)$#",$_POST["port"])){
        echo (new template_admin())->post_error("{$_POST["port"]} not valid should be tcp|sctp|udp|udplite|icmp|icmp6:Port Nuber");
        return;
    }

    if(!preg_match("#^\[[0-9\-]+#",$_POST["comment"])) {
        $date=date("Y-m-d H:i:s");
        $_POST["comment"]="[$date]: {$_POST["comment"]}";
    }
    $e=fwapi_error(fwapi_send("POST","itself",array(
        "pattern"=>trim((string)$_POST["pattern"]),
        "port"=>trim((string)$_POST["port"]),
        "comment"=>(string)$_POST["comment"],
    )));
    if($e!=""){echo $tpl->post_error($e);}
}

function table_start(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $add        = "Loadjs('$page?add-js=yes');";


    $html[]="
    <div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom:10px'>
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>
    	
</div>";

    $html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/firewall.db","firehol_itself","","&table=yes");
    echo $tpl->_ENGINE_parse_body($html);
}

function table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $th_center  = "data-sortable=true class='text-capitalize center' data-type='text' width='1%'";
    $t          = time();
    $TRCLASS    = null;
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $tdfree     = $tpl->table_tdfree(null);
    $td1prc     = $tpl->table_td1prc();



//md5,pattern,official,port

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{address}</th>";
    $html[]="<th>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='rulesitself-rows'>";

    $ico_nothing=$tpl->icon_nothing();
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    $sql="SELECT * FROM firehol_itself {$search["Q"]} ORDER BY ID DESC LIMIT {$search["MAX"]}";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error."<br>$sql");}

    $color      = null;
    foreach ($results as $index=>$ligne){
        $md         = $ligne["md5"];
        $comment    = $ligne["comment"];
        $ID         = $ligne["ID"];
        $TRCLASS=null;
        $delete=$tpl->icon_delete("Loadjs('$page?delete=$ID&id=$md')","AsFirewallManager");
        if(intval($ligne["official"])==1){$delete=$ico_nothing;}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $tdfree><strong>{$ligne["pattern"]}</strong>:{$ligne["port"]}<br><small>$comment</small></td>";
        $html[]="<td $td1prc>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='rulesitself-pager'></span></div></div></div>";
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"rulesitself-rows","rulesitself-pager");
}