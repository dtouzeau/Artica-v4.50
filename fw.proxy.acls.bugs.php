<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");

if(isset($_GET["refresh"])){refresh();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}
if(isset($_GET["delete-all"])){delete_all();exit;}
service_js();


function refresh(){
    $page       = CurrentPageName();
    $f[]="if(document.getElementById('main-popup-aclsbugs') ){";
    $f[]="\tLoadAjax('main-popup-aclsbugs','$page?popup-table=main-popup-aclsbugs');";
    $f[]="}";
    $f[]="if(document.getElementById('proxy-acls-bugs-function') ){";
    $f[]="\tvar func=document.getElementById('proxy-acls-bugs-function').value;";
    $f[]="\teval(func + '()');";
    $f[]="}";
    $f[]="if(document.getElementById('proxy-acls-items-function') ){";
    $f[]="\tvar func=document.getElementById('proxy-acls-items-function').value;";
    $f[]="\teval(func + '()');";
    $f[]="}";



    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    // Loadjs('fw.proxy.acls.bugs.php?refresh=yes');
}

function delete_all(){
    $page       = CurrentPageName();
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUID_ACLS_REMOVED_ITEMS",serialize(array()));
    echo "dialogInstance4.close();\n";
    echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');\n";
    $f[]="if(document.getElementById('main-popup-aclsbugs') ){";
    $f[]="\tLoadAjax('main-popup-aclsbugs','$page?popup-table=main-popup-aclsbugs');";
    $f[]="}";
    $f[]="if(document.getElementById('proxy-acls-bugs-function') ){";
    $f[]="\tvar func=document.getElementById('proxy-acls-bugs-function').value;";
    $f[]="\teval(func + '()');";
    $f[]="}";
    $f[]="if(document.getElementById('proxy-acls-items-function') ){";
    $f[]="\tvar func=document.getElementById('proxy-acls-items-function').value;";
    $f[]="\teval(func + '()');";
    $f[]="}";

    echo @implode("\n",$f);
}

function service_js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();
    $tpl->js_dialog4("{issues_in_acls}","$page?popup-main=$t");
}
function rule_js(){
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();

    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}

    $tpl->js_dialog5("{gzip_rules}: $title","$page?popup-rule=$rule&serviceid=$serviceid");
}
function compile_js_progress():string{
    $page       = CurrentPageName();
    $tpl=new template_admin();
    $serviceid  = intval($_GET["popup-table"]);


    $jsrestart=$tpl->framework_buildjs("/proxy/acls/php/compile",
        "squid.access.center.progress","squid.access.center.progress.log",
        "main-popup-aclsbugs","dialogInstance4.close();");


    return $jsrestart;

}
function popup_main(){
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-aclsbugs'></div>
    <script>LoadAjax('main-popup-aclsbugs','$page?popup-table=$serviceid');</script>";
}

function popup_table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tableid    = time();
    $TRCLASS    = null;

    $topbuttons[]=array("Loadjs('$page?delete-all=yes')","fas fa-repeat-1-alt","{delete_all}");

    $html[]="<div id='progress-compile-replace-$tableid' style='margin-top:20px'></div>";
    $html[]=$tpl->table_buttons($topbuttons);
    $html[]=$tpl->table_head(array("","{info}"),"table-$tableid");

    $SQUID_ACLS_REMOVED_ITEMS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_ACLS_REMOVED_ITEMS"));


    foreach ($SQUID_ACLS_REMOVED_ITEMS as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $bell="<i class='text-danger fas fa-bell'></i>&nbsp;";
        $html[]="<tr class='$TRCLASS' id='$index'>";
        $html[]="<td width=1% nowrap>$bell</td>";
        $html[]="<td width=99% nowrap><strong>$ligne</strong></td>";
        $html[]="</tr>";

    }

     $html[]=$tpl->table_footer("table-$tableid",2,false);
        echo $tpl->_ENGINE_parse_body($html);
        }