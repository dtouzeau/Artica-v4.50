<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Les autres pages du pare-feu (fw.nat.php, fw.rules.php) vérifient ce droit
// côté serveur ; celle-ci ne le faisait qu'à travers form_outside, c'est-à-dire
// dans le navigateur.
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}

// Verrou de la politique Artica Meta "firewall" : regles tenues = options en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");
if(isset($_POST["ruleid"])){if(firewall_meta_holds("rules")){echo firewall_meta_refuse_post();exit;}save();exit;}

page();



function page(){
    $ruleid     = intval($_GET["rule-id"]);
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
    $tpl    = new template_admin();if(firewall_meta_holds("rules")){firewall_meta_lock_form($tpl);echo firewall_meta_banner("rules",$tpl);}
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
    $tpl    = new template_admin();if(firewall_meta_holds("rules")){firewall_meta_lock_form($tpl);echo firewall_meta_banner("rules",$tpl);}
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");




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
    if($ID==0){return;}
    $data=array();
    if(isset($_POST["MARK"])){$data["mark"]=intval($_POST["MARK"]);}
    if(isset($_POST["ForwardTo"])){$data["forwardto"]=trim((string)$_POST["ForwardTo"]);}
    if(isset($_POST["ForwardNIC"])){$data["forwardnic"]=(string)$_POST["ForwardNIC"];}
    if(isset($_POST["ForwardToPort"])){$data["forwardtoport"]=intval($_POST["ForwardToPort"]);}
    if(isset($_POST["xt_ratelimit"])){$data["xt_ratelimit"]=intval($_POST["xt_ratelimit"]);}
    if(isset($_POST["xt_ratelimit_dir"])){$data["xt_ratelimit_dir"]=(string)$_POST["xt_ratelimit_dir"];}
    $e=fwapi_error(fwapi_send("PUT","rules/$ID/options",$data));
    if($e!=""){echo $tpl->post_error($e);}
}

function page_xtables(){
    $tpl                    = new template_admin();if(firewall_meta_holds("rules")){firewall_meta_lock_form($tpl);echo firewall_meta_banner("rules",$tpl);}
    $ruleid                 = intval($_GET["rule-id"]);
    $APP_XTABLES_INSTALLED  = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_XTABLES_INSTALLED");
    $q                      = new lib_sqlite("/home/artica/SQLITE/firewall.db");



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