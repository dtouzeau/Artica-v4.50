<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

//
$GLOBALS["POSTFIX_RULES"][1]="<strong>{ACCEPT}</strong><br>{POSTFIX_REGEX_ACCEPT}";
$GLOBALS["POSTFIX_RULES"][2]="<strong>{INFO}</strong><br>{POSTFIX_REGEX_INFO}";
$GLOBALS["POSTFIX_RULES"][3]="<strong>{DISCARD}</strong><br>{POSTFIX_REGEX_DISCARD}";
$GLOBALS["POSTFIX_RULES"][4]="<strong>{REJECT}</strong><br>{POSTFIX_REGEX_REJECT}";
$GLOBALS["POSTFIX_RULES"][5]="<strong>{BCC}</strong><br>{BCC_help}";
$GLOBALS["POSTFIX_RULES"][6]="<strong>{PREPEND}</strong><br>{POSTFIX_REGEX_PREPEND}";


$GLOBALS["POSTFIX_RULES_STATUS"][1]="label-primary";
$GLOBALS["POSTFIX_RULES_STATUS"][2]="label-info";
$GLOBALS["POSTFIX_RULES_STATUS"][3]="label-danger";
$GLOBALS["POSTFIX_RULES_STATUS"][5]="label-danger";
$GLOBALS["POSTFIX_RULES_STATUS"][5]="label-pink";
$GLOBALS["POSTFIX_RULES_STATUS"][6]="label-info";

$GLOBALS["POSTFIX_RULES_TITLE"][1]="{ACCEPT}";
$GLOBALS["POSTFIX_RULES_TITLE"][2]="{INFO}";
$GLOBALS["POSTFIX_RULES_TITLE"][3]="{DISCARD}";
$GLOBALS["POSTFIX_RULES_TITLE"][5]="{REJECT}";
$GLOBALS["POSTFIX_RULES_TITLE"][5]="{BCC}";
$GLOBALS["POSTFIX_RULES_TITLE"][6]="{PREPEND}";


if(isset($_GET["popjs"])){popup_js();exit;}
if(isset($_GET["rule-safesearch"])){rule_safesearch();exit;}
if(isset($_GET["replace-rule"])){replace_rule();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}

if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}


if(isset($_GET["ch-method-js"])){change_method_js();exit;}
if(isset($_GET["ch-method-popup"])){change_method_popup();exit;}
if(isset($_POST["ch-rule"])){change_method_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["default-js"])){default_js();exit;}
if(isset($_GET["default-popup"])){default_popup();exit;}
if(isset($_POST["ProxyDefaultUncryptSSL"])){ProxyDefaultUncryptSSL_save();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["rule-cache"])){rule_cache();exit;}
if(isset($_GET["view-rules"])){view_rules_js();exit;}
if(isset($_GET["view-rules-popup"])){view_rules_popup();exit;}
page();

function popup_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID"]);
    $title=$tpl->javascript_parse_text("{events}");
    $instanceid=intval($_GET["instance-id"]);
    $ruleid=intval($_GET["ruleid"]);
    $tpl->js_dialog2($title, "$page?table-start=yes&instance-id=$instanceid&ruleid=$ruleid",1024);
}
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=intval($_GET["instance-id"]);

    $html=$tpl->page_header("{APP_POSTFIX}&nbsp;&raquo;&nbsp;{transactions}",
        ico_eye,"&nbsp;","$page?table-start=yes&instance-id=$instanceid","postfix-transactions",
        "progress-transactions-restart",false,"table-transactions-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function fillRulesList_explain():bool{
    $GLOBALS["POSTFIX_RULES"][1]="<strong>{ACCEPT}</strong><br>{POSTFIX_REGEX_ACCEPT}";
    $GLOBALS["POSTFIX_RULES"][2]="<strong>{INFO}</strong><br>{POSTFIX_REGEX_INFO}";
    $GLOBALS["POSTFIX_RULES"][3]="<strong>{DISCARD}</strong><br>{POSTFIX_REGEX_DISCARD}";
    $GLOBALS["POSTFIX_RULES"][4]="<strong>{REJECT}</strong><br>{POSTFIX_REGEX_REJECT}";
    $GLOBALS["POSTFIX_RULES"][5]="<strong>{BCC}</strong><br>{BCC_help}";
    $GLOBALS["POSTFIX_RULES"][6]="<strong>{PREPEND}</strong><br>{POSTFIX_REGEX_PREPEND}";
    return true;
}
function fillRules_canchange():array{
    $RULESSWITH[2]=true;
    $RULESSWITH[3]=true;
    $RULESSWITH[4]=true;
    return $RULESSWITH;
}
function table_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleparam="";
    $instanceid=intval($_GET["instance-id"]);
    if(isset($_GET["ruleid"])){
        $ruleparam="&ruleid={$_GET["ruleid"]}";
    }
    echo $tpl->search_block($page,null,null,null,"&table=yes&instance-id=$instanceid&$ruleparam");
    return true;
}
function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceid=intval($_GET["instance-id"]);
    $function=$_GET["function"];
    $ruleid=0;
    $ruleField=false;
    if(isset($_GET["ruleid"])){
        $ruleid=intval($_GET["ruleid"]);
        if($ruleid>0){
            $ruleField=true;
        }
    }
    $html[]="<table id='table-ssl-proxy-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    if(!$ruleField) {
        $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{rulename}</th>";
    }
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{from}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{recipients}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{subject}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $RULEQUERY="";
    if($ruleid>0){
        $RULEQUERY=" AND ruleid=$ruleid";
    }
    if(!$ruleField) {
        $sql = "SELECT ID,rulename FROM postfix_rules WHERE instanceid=$instanceid$RULEQUERY ORDER BY zOrder";
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index => $ligne) {
            $MAINRULES[$ligne["ID"]] = $tpl->utf8_encode($ligne["rulename"]);
        }
    }
    $QUERY="";
    $class="";
    $q=new postgres_sql();

    $search=$_GET["search"];
    if(strlen($search)>2){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $QUERY=" AND ( (mailfrom LIKE  '$search') OR (recipients LIKE '$search') OR (subject LIKE '$search') OR (hostname LIKE '$search') OR (ipaddr::text LIKE '$search') )";

    }
    $sql="SELECT * FROM articamilter WHERE instanceid=$instanceid$RULEQUERY $QUERY ORDER BY zdate DESC LIMIT 250";
    $results=$q->QUERY_SQL($sql);
    $TRCLASS=null;

    $tdmid="style='vertical-align:middle;width:1%'";
    if($results) {
        while ($ligne = @pg_fetch_assoc($results)) {
            if ($TRCLASS == "footable-odd ") {
                $TRCLASS = null;
            } else {
                $TRCLASS = "footable-odd ";
            }
            $ruleid = intval($ligne["ruleid"]);
            $mailfrom = $ligne["mailfrom"];
            $rulename = "{default}";
            $recipients = $ligne["recipients"];
            if ($ruleid > 0) {
                $rulename = $MAINRULES[$ruleid];
            }
            $subject = $ligne["subject"];
            $hostname = $ligne["hostname"];
            $ipaddr = $ligne["ipaddr"];
            $zdate = $tpl->time_to_date(strtotime($ligne["zdate"]), true);
            $status = $ligne["ruleaction"];
            $recipients = str_replace(",", "<br>", $recipients);
            $mailsize = intval($ligne["mailsize"]);
            $hostname_text = "$hostname [$ipaddr]";
            if ($hostname == $ipaddr) {
                $hostname_text = $ipaddr;
            }
            if (strlen($status) == 0) {
                $status = "PASS";
            }

            if (preg_match("#(PREPEND|INFO|BCC)#", $status)) {
                $status = "<span class='label label-info'>$status</span>";
            }

            if (preg_match("#(DISCARD|REJECT)#", $status)) {
                $status = "<span class='label label-danger'>$status</span>";
            }
            if (preg_match("#(ACCEPT|PASS)#", $status)) {
                $status = "<span class='label label-primary'>$status</span>";
            }
            $mailsize = FormatBytes($mailsize / 1024);

            $html[] = "<tr style='vertical-align:middle' class='$TRCLASS'>";
            $html[] = "<td $tdmid class=\"$class\" nowrap>$zdate</td>";
            $html[] = "<td $tdmid class=\"$class\" nowrap>$status</td>";
            if (!$ruleField) {
                if (strlen($rulename) < 2) {
                    $rulename = "#$ruleid";
                }
                $html[] = "<td $tdmid class=\"$class\" nowrap>$rulename</td>";
            }
            $len = strlen($mailfrom);
            if ($len > 30) {
                $dm = explode("@", $mailfrom);
                $mailfrom = substr($dm[0], 0, 27) . "...@" . $dm[1];

            }

            $html[] = "<td $tdmid class=\"$class\" nowrap><span class='$class'>$mailfrom</span></td>";
            $html[] = "<td $tdmid class=\"$class\" nowrap><span class='$class'>$recipients</span></td>";
            $html[] = "<td  class=\"$class\" style='width:99%'>$subject</span></td>";
            $html[] = "<td $tdmid class=\"$class\" nowrap><span class='$class'>$hostname_text</span></td>";
            $html[] = "<td $tdmid class=\"$class\" nowrap>$mailsize</td>";
            $html[] = "</tr>";
        }
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $colspan=7;
    if(!$ruleField){
        $colspan=8;
    }

    $html[]="<tr>";
    $html[]="<td colspan='$colspan'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    if(!$ruleField) {
        $TINY_ARRAY["TITLE"] = "Artica Milter&nbsp;&raquo;&nbsp;{transactions}";
        $TINY_ARRAY["ICO"] = ico_eye;
        $TINY_ARRAY["EXPL"] = "{postfix_acls_rules_explain}";
        $TINY_ARRAY["BUTTONS"] = "";
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
    }

    $html[]="
<script> 
    function RefreshPostfixRules(){ {$_GET["function"]}(); }
    $jstiny
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
