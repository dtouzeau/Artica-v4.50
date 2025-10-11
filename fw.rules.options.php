<?php
define(td1prc ,  "widht=1% class='center' style='vertical-align:middle' nowrap");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.firehol.inc");
include_once(dirname(__FILE__)."/ressources/class.iptables.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["ruleid"])){save();exit;}

page();



function page(){
    $ruleid     = intval($_GET["rule-id"]);
    $eth        = $_GET["eth"];
    $function   = $_GET["function"];
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne      = $q->mysqli_fetch_array("SELECT accepttype FROM iptables_main WHERE ID='$ruleid'");
    $accepttype = $ligne["accepttype"];

    if($accepttype=="MARK"){
        page_MARK($ruleid);
        return;
    }
    if($accepttype=="TPROXY"){
        page_TPROXY($ruleid);
        return;
    }

    page_xtables();

}

function page_MARK($ruleid){
    $tpl    = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ruleid'");

    $tpl->field_hidden("ruleid",$ruleid);
    $form[] = $tpl->field_numeric("MARK", "{MARK_NUMBER}", $ligne["MARK"]);
    $form[] = $tpl->field_ipaddr("ForwardTo", "{and_redirect_to}", $ligne["ForwardTo"]);
    $form[] = $tpl->field_interfaces("ForwardNIC", "{using_the_interface}", $ligne["ForwardNIC"]);

    echo $tpl->form_outside("{mark_section}",@implode("\n", $form),
        "{mark_section_explain}","{apply}","Loadjs('fw.rules.php?fill=$ruleid');",
        "AsFirewallManager");
}

function page_TPROXY($ruleid){
    $tpl    = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");

    if(!$q->FIELD_EXISTS("iptables_main","ForwardToPort")){$q->QUERY_SQL("ALTER TABLE iptables_main ADD ForwardToPort INTEGER NOT NULL DEFAULT 0"); }



    $ligne      = $q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ruleid'");
    if(intval($ligne["ForwardToPort"])==0){$ligne["ForwardToPort"]=8080;}

    $tpl->field_hidden("ruleid",$ruleid);
    $form[] = $tpl->field_ipaddr("ForwardTo", "{remote_address}", $ligne["ForwardTo"]);
    $form[] = $tpl->field_numeric("ForwardToPort", "{remote_port}", $ligne["ForwardToPort"]);

    echo $tpl->form_outside("{tproxy_method}",@implode("\n", $form),
        "{tproxy_method_explain}","{apply}","Loadjs('fw.rules.php?fill=$ruleid');",
        "AsFirewallManager");


}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ruleid"]);
    unset($_POST["ruleid"]);

    foreach ($_POST as $key=>$val){

        $tt[]="`$key`='$val'";

    }

    $sql="UPDATE iptables_main SET ".@implode(", ",$tt)." WHERE ID=$ID";
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}

}

function page_xtables(){
    $tpl                    = new template_admin();
    $ruleid                 = intval($_GET["rule-id"]);
    $APP_XTABLES_INSTALLED  = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_XTABLES_INSTALLED");
    $q                      = new lib_sqlite("/home/artica/SQLITE/firewall.db");

    if(!$q->FIELD_EXISTS("iptables_main","xt_ratelimit")){
        $q->QUERY_SQL("ALTER TABLE iptables_main ADD xt_ratelimit INTEGER NOT NULL DEFAULT 0");
        $q->QUERY_SQL("ALTER TABLE iptables_main ADD xt_ratelimit_dir TEXT NOT NULL DEFAULT 'src'");
    }

    if(!$q->TABLE_EXISTS("xt_ratelimit")){
        $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS xt_ratelimit (pattern TEXT,limit integer,enabled INTEGER NOT NULL DEFAULT 1,ruleid INTEGER NOT NULL DEFAULT 0)");
    }


    $ligne                  = $q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ruleid'");


    if($APP_XTABLES_INSTALLED==0){
        $html=$tpl->div_error("{xtables_not_installed}");
        echo $tpl->_ENGINE_parse_body($html);
        return;
    }
// 1 kbps = 1000 bps
// 1 bps = 0.001 kbps
    $dir["src"]="{src}";
    $dir["dst"]="{dst}";
    $tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_checkbox("xt_ratelimit","{Limit_Bandwidth}",$ligne["xt_ratelimit"]);
    $form[]=$tpl->field_array_hash($dir,"xt_ratelimit_dir","{match_in}",$ligne["xt_ratelimit_dir"]);

    $html[]=$tpl->form_outside("{traffic_shapping}",$form," {traffic_shapping_explain}","{apply}","Loadjs('fw.rules.php?fill=$ruleid')","AsFirewallManager");

    echo $tpl->_ENGINE_parse_body($html);

}