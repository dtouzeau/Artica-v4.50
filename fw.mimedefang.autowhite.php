<?php

$GLOBALS["ACTIONSAV"][0]="{block_attachments_and_pass}";
$GLOBALS["ACTIONSAV"][1]="{save_message_in_quarantine}";
$GLOBALS["ACTIONSAV"][2]="{remove_message}";
$GLOBALS["ACTIONSAV"][3]="{do_nothing}";


include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_POST["zmd5"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete"])){rule_delete_perform();exit;}


page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $MimeDefangClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangAutoWhiteList"));

    $error=null;
    $js="LoadAjax('table-loader-as-autowhite','$page?table=yes');";

    if($MimeDefangClamav==0){
        $error="<div class='alert alert-warning'>{spamassassin_not_enabled}</div>";

    }


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{smtp_AutoWhiteList}</h1>
	<p>{smtp_AutoWhiteList_text}</p>$error
	</div>

	</div>



	<div class='row'><div id='progress-mimedf-restart'></div>
	<div class='ibox-content'>
";
    $html=$html.$tpl->search_block($page,"postgres","autowhite","table-loader-as-autowhite")."
	<div id='table-loader-as-autowhite'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/postfix-policies-autowhite');
	

	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{smtp_AutoWhiteList}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function rule_delete_js(){
    $tpl=new template_admin();
    $md5=$_GET["delete-rule-js"];
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM autowhite WHERE zmd5='$md5'");
    $mailfrom=$ligne["mailfrom"];
    $mailto=$ligne["mailto"];

    $title="$mailfrom > $mailto";
    $tpl->js_confirm_delete($title, "delete", $md5,"$('#$md5').remove()");
}
function rule_delete_perform(){
    $md5=$_POST["delete"];
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM autowhite WHERE zmd5='$md5'");
    if(!$q->ok){echo $q->mysql_error;}

}

function rule_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md5=$_GET["rule-js"];
    $title="{new_rule}";
    $tpl->js_dialog1($title, "$page?popup=$md5&function={$_GET["function"]}");

}

function rule_save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new postgres_sql();
    $_POST["mailfrom"]=str_replace("*@","",$_POST["mailfrom"]);
    $_POST["mailto"]=str_replace("*@","",$_POST["mailto"]);
    $_POST["mailfrom"]=strtolower($_POST["mailfrom"]);
    $_POST["mailto"]=strtolower($_POST["mailto"]);
    if($_POST["mailfrom"]==null){$_POST["mailfrom"]="*";}
    if($_POST["mailto"]==null){$_POST["mailto"]="*";}
    if($_POST["mailfrom"]=="*"){
        if($_POST["mailto"]=="*"){
            echo "operation invalid!";
            return;
        }
    }

    $new_zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
    $date=date("Y-m-d H:i:s");
    $sqladd="INSERT INTO autowhite(zmd5,zdate,mailfrom,mailto) VALUES ('$new_zmd5','$date','{$_POST["mailfrom"]}','{$_POST["mailto"]}')";
    $q->QUERY_SQL($sqladd);
    if(!$q->ok){echo $q->mysql_error;}

}



function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md5=$_GET["popup"];
    $title="{new_rule}";
    $bt="{add}";



    $js="dialogInstance1.close();{$_GET["function"]}();";

    $tpl->field_hidden("zmd5", $md5);
    $form[]=$tpl->field_text("mailfrom", "{sender}", null);
    $form[]=$tpl->field_text("mailto", "{recipient}", null);
    echo $tpl->form_outside($title, $form,"{mimedefang_email_explain}",$bt,$js,"AsPostfixAdministrator",true);
}

function IntelligentSearch($search_local){

    $ip=new IP();


    $search_local=trim($search_local);

    if(preg_match("#^\*@(.+)#", $search_local,$re)){
        $querys="WHERE (mailfrom ~ '{$re[1]}') OR (mailto ~ '{$re[1]}')";
        if(strpos("    ".$re[1], "*")>0){
            $search_local=str_replace("*", ".*?", $re[1]);
            $querys="WHERE (mailfrom ~'$search_local') OR (mailto ~'$search_local')";
        }

        return $querys;
    }
    if(preg_match("#^@(.+)#", $search_local,$re)){
        $querys="WHERE (mailfrom ~'{$re[1]}') OR (mailto ~'{$re[1]}')";
        if(strpos("    ".$re[1], "*")>0){
            $search_local=str_replace("*", ".*?", $re[1]);
            $querys="WHERE (mailfrom ~'{$search_local}') OR (mailto ~'{$search_local}')";
        }

        return $querys;
    }

    if(preg_match("#^(.+?)@(.+)#", $search_local,$re)){
        $querys="WHERE (mailfrom='$search_local') OR (mailto='$search_local')";
        if(strpos("    ".$search_local, "*")>0){
            $search_local=str_replace("*", ".*?", $re[1]);
            $querys="WHERE (mailfrom ~'$search_local') OR (mailto ~'$search_local')";
        }

        return $querys;
    }



}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();



    $t=time();

    $add="Loadjs('$page?rule-js=&function={$_GET["function"]}',true);";


    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true data-type='text'>{sender}</th>";
    $html[]="<th data-sortable=true data-type='text'>{recipients}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $search=trim($_GET["search"]);
    $MAX=150;
    $IntelligentSearch=IntelligentSearch($search);

    if($IntelligentSearch<>null){
        $querys["Q"]=$IntelligentSearch;
    }

    if(!isset($querys["Q"])){
        $querys=$tpl->query_pattern($search);
        $MAX=$querys["MAX"];
        if($MAX==0){$MAX=150;}
    }


    $TRCLASS=null;
    $sql="SELECT * FROM autowhite {$querys["Q"]} ORDER BY zdate desc LIMIT $MAX";
    $results=$q->QUERY_SQL($sql);


    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $zmd5=$ligne["zmd5"];
        $xtime=$tpl->time_to_date(strtotime($ligne["zdate"],true));
        $mailfrom= $ligne["mailfrom"];
        $mailto=$ligne["mailto"];



        $html[]="<tr class='$TRCLASS' id='$zmd5'>";
        $html[]="<td width=1% nowrap>$xtime</td>";
        $html[]="<td><strong>$mailfrom</strong></td>";
        $html[]="<td><strong>$mailto</strong></td>";
        $html[]="<td width=1% class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-rule-js=$zmd5')","AsPostfixAdministrator") ."</center></td>";
        $html[]="</tr>";


    }



    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<div><small>$sql</small></div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}