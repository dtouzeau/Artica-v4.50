<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}

if(isset($_GET["zoomid"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["zoom-delete"])){zoom_delete();exit;}
page();


function zoom_js(){
    $id=intval($_GET["zoomid"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("Error N.$id","$page?zoom-popup=$id",990);
}
function zoom_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["zoom-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sql_errors WHERE ID=$ID");
    $subject=$ligne["subject"];
    $html[]="<H1>$subject</H1>";
    $html[]="<H2>{$ligne["database"]}</h2>";
    $html[]=$tpl->div_explain(base64_decode($ligne["sql"]));
    $error=base64_decode($ligne["error"]);
    if(is_serialized($error)){
        $serror=unserialize($error);
        foreach ($serror as $index=>$line){
            $s[]="<div>[$index]: $line</div>";
        }
        $html[]=$tpl->div_error(@implode("",$s));
    }else{
        $html[]=$tpl->div_error($error);
    }

    $DebugTracback=unserialize(base64_decode($ligne["debug"]));

    foreach ($DebugTracback as $index=>$row){
        $class=null;
        $file=$row["file"];$file=str_replace("/usr/share/artica-postfix/","",$file);
        $line=$row["line"];
        $function=$row["function"];
        if(isset($row["class"])){$class="(".$row["class"].")&nbsp;&nbsp;";}
        $html[]="<div><strong>{line} $line {function} $class$function"."()"." {file}: $file</strong></div>";


    }
    $html[]="<div style='text-align:right;margin:20px'>";
    $html[]=$tpl->button_autnonome("{delete}","Loadjs('$page?zoom-delete=$ID')",
            "fa fa-trash","AsSystemAdministrator",0,"btn-danger");
    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body($html);

}

function zoom_delete(){

    $id=intval($_GET["zoom-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $q->QUERY_SQL("DELETE FROM sql_errors WHERE ID=$id");

    header("content-type: application/x-javascript");
    echo "dialogInstance1.close();\n";
    echo "databases_error_table_refresh();\n";
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();


    $html=$tpl->page_header("{sql_errors}","fad fa-bug","{sql_errors_explain}",null,
        "sql-errors","progress-firehol-restart",true);
    //

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{sql_errors}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function search(){
    $time=null;
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $search=trim($_GET["search"]);
    $search=$tpl->CLEAN_BAD_XSS($search);
    $page=CurrentPageName();

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{error}</th>
        	<th>{database}</th>
        </tr>
  	</thead>
	<tbody>
";

    if($search<>null){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $sql="SELECT * FROM sql_errors WHERE subject like '$search' ORDER BY zdate DESC LIMIT 250";

    }else{
        $sql="SELECT * FROM sql_errors ORDER BY zdate DESC LIMIT 250";
    }

    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        $tpl->div_error($q->mysql_error);
    }

    foreach ($results as $ligne){
        $ID=$ligne["ID"];
        $FTime=$tpl->time_to_date($ligne["zdate"],true);
        $subject=$tpl->td_href($ligne["subject"],null,"Loadjs('$page?zoomid=$ID')");
        $database=$ligne["database"];

        $html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=99% >$subject</td>
				<td width=1% nowrap>$database</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body($html);



}
function is_serialized($data){
    return (is_string($data) && preg_match("#^((N;)|((a|O|s):[0-9]+:.*[;}])|((b|i|d):[0-9.E-]+;))$#um", $data));
}