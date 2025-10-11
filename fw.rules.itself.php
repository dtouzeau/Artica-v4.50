<?php
define(td1prc ,  "widht=1% class='center' style='vertical-align:middle' nowrap");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.firehol.inc");
include_once(dirname(__FILE__)."/ressources/class.iptables.inc");
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
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $md         = $_GET["id"];
    $ID         = $_GET["delete"];
    $q->QUERY_SQL("DELETE FROM firehol_itself WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
}

function add_final(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $IP=new IP();
    if(!$IP->isIPAddressOrRange($_POST["pattern"])){
        echo "jserror: {$_POST["pattern"]} not valid, should be 1.1.1.1/11 or 1.1.1.1";
        return;
    }

    if(!preg_match("#^(tcp|sctp|udp|udplite|icmp|icmp6):([0-9\-]+)#",$_POST["port"])){
        echo "jserror: {$_POST["port"]} not valid should be tcp|sctp|udp|udplite|icmp|icmp6:Port Nuber";
        return;
    }

    $md5=md5($_POST["pattern"].$_POST["port"]);
    if(!preg_match("#^\[[0-9\-]+#",$_POST["comment"])) {
        $date=date("Y-m-d H:i:s");
        $_POST["comment"]="[$date]: {$_POST["comment"]}";
    }
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $line="('$md5','{$_POST["pattern"]}',0,'{$_POST["port"]}','{$_POST["comment"]}')";
    $q->QUERY_SQL("INSERT OR IGNORE INTO firehol_itself (md5,pattern,official,port,comment) VALUES $line");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}

}


function table_start(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $add        = "Loadjs('$page?add-js=yes');";

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR . "/firehol.reconfigure.log";
    $ARRAY["CMD"]="firehol.php?ipset-itself=yes";
    $ARRAY["TITLE"]="{compiling}";
    $ARRAY["AFTER"]="blur();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=ipset-itself-progress')";

    $html[]="
    <div id='ipset-itself-progress'></div>
    <div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom:10px'>
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>        <label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {compile} </label>
    	
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

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{address}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

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
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $delete=$tpl->icon_delete("Loadjs('$page?delete=$ID&id=$md')","AsFirewallManager");
        if(intval($ligne["official"])==1){$delete=$ico_nothing;}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $tdfree><strong>{$ligne["pattern"]}</strong>:{$ligne["port"]}<br><small>$comment</small></td>";
        $html[]="<td $td1prc>$delete</td>";
        $html[]="</tr>";
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
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); }); 
</script>";

    echo $tpl->_ENGINE_parse_body($html);
}