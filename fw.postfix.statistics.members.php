<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["stat-js"])){stat_js();exit;}
if(isset($_GET["build-stats"])){stats_gen_popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["new-js"])){new_js();exit;}
if(isset($_GET["new-popup"])){new_popup();exit;}
if(isset($_GET["new-organization"])){new_organization();exit;}
if(isset($_GET["new-group"])){new_group();exit;}
if(isset($_GET["events-head"])){events_head();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["status-table"])){status_table();exit;}
if(isset($_GET["status-graph"])){status_graph();exit;}
if(isset($_GET["delete-js"])){delete_report_js();exit;}
if(isset($_POST["delete"])){delete_report_perform();exit;}
page();




function delete_report_js(){
    $email=$_GET["delete-js"];
    $tablename=$_GET["tablename"];
    $tpl=new template_admin();
    $tpl->js_confirm_delete("{delete_report} $email","delete",$tablename,"dialogInstance1.close();");
}

function delete_report_perform(){
    $tablename=$_POST["delete"];
    $q=new postgres_sql();
    $q->QUERY_SQL("DROP TABLE $tablename");
    if(!$q->ok){echo $q->mysql_error;}
}


function tabs(){
    $email=$_GET["tabs"];
    $emailenc=urlencode($email);
    $table_name=str_replace("@","",strtolower($email));
    $table_name=str_replace(".","",$table_name);
    $table_name=str_replace("-","",$table_name);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?status=$emailenc&tablename=$table_name";
    $array["{events}"]="$page?events-head=$emailenc&tablename=$table_name";
    echo $tpl->tabs_default($array);
}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $tablename=$_GET["tablename"];
    $email=$_GET["status"];
    $emailen=urlencode($email);
    $md5=md5($email);
    $ligne=$q->mysqli_fetch_array("SELECT zdate FROM $tablename ORDER by zdate LIMIT 1");

    $date_start=$tpl->time_to_date(strtotime($ligne["zdate"]));
    if(!$q->ok){echo $q->mysql_error_html(true);}
    $ligne=$q->mysqli_fetch_array("SELECT zdate FROM $tablename ORDER by zdate DESC LIMIT 1");
    $date_to=$tpl->time_to_date(strtotime($ligne["zdate"]));


    $ligne=$q->mysqli_fetch_array("SELECT SUM(hits) as refused FROM $tablename WHERE refused=1");
    $refused=intval($ligne["refused"]);
    $refused=FormatNumber($refused);

    $ligne=$q->mysqli_fetch_array("SELECT SUM(hits) as sent FROM $tablename WHERE sent=1");
    $sent=intval($ligne["sent"]);
    $sent=FormatNumber($sent);


    $html[]="";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valing='top' style='padding:0px' width='95%' valign='top'>";
    $html[]="<H1>{period} $date_start {to} $date_to</H1><div style='margin-top:-16px;text-align:right'></div><small>$email</small></div>";
    $html[]="</td>";
    $html[]="<td valing='top' style='padding:0px' width='5%' valign='middle'>";
    $html[]=$tpl->button_autnonome("{delete_report}","Loadjs('$page?delete-js=$emailen&tablename=$tablename')","fa fa-trash",null,0,"btn-danger");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valing='top' style='padding:3px' width='50%' valign='top'>";
    $html[]=$tpl->widget_h("red","fa-thumbs-down",$refused,"{refused}");
    $html[]="</td>";
    $html[]="<td valing='top' style='padding:3px' width='50%' valign='top'>";
    $html[]=$tpl->widget_h("green","fa-thumbs-up",$sent,"{sent}");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td valing='top' style='padding:3px' width='50%' valign='top'><div id='$md5-2'></div></td>";
    $html[]="<td valing='top' style='padding:3px' width='50%' valign='top'><div id='$md5-1'></div></td>";
    $html[]="</tr>";
    $html[]="</table>
<script>
    Loadjs('$page?status-graph=yes&tablename=$tablename&container=$md5-1&container2=$md5-2');
    
</script>

";

    echo $tpl->_ENGINE_parse_body($html);
}


function events_head(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tablename=$_GET["tablename"];
   echo $tpl->search_block($page,"postgres",$tablename,null,"&tablename=$tablename");


}

function search(){
    $tpl=new template_admin();
    $table=$_GET["tablename"];
    $page=CurrentPageName();
    $search=$_GET["search"];
    $querys=$tpl->query_pattern($search);
    $MAX=$querys["MAX"];
    if($MAX==0){$MAX=150;}
    $q=new postgres_sql();
    $t=time();
    $sql="SELECT * FROM $table {$querys["Q"]} ORDER BY zdate desc LIMIT $MAX";

    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}

    if(pg_num_rows($results)==0){

        echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
        return;
    }

    $TRCLASS=null;
    $html[]="<table id='table$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{messages}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sender}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{reason}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $md=md5(serialize($ligne));
        $id=$ligne["id"];
        $color="#000000";
        $zdate=$tpl->time_to_date(strtotime($ligne["zdate"]),true);
//hits,frommail,refused,sent,reason
        $hits=FormatNumber($ligne["hits"]);
        $mailfrom=$ligne["frommail"];
        $refused=$ligne["refused"];
        $sent=$ligne["sent"];
        $reason=$ligne["reason"];
        $class=null;
        if($sent==1){
            $class="text-success";
        }

        if($refused==1){
            $class="text-danger";
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap class='$class'>{$zdate}</td>";
        $html[]="<td style='width:1%' nowrap class='$class'>{$hits}</td>";
        $html[]="<td style='width:1%' nowrap class='$class'>{$mailfrom}</td>";
        $html[]="<td style='width:99%' nowrap class='$class'>$reason</td>";

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
    $html[]="<small>$sql</small>
			<script>
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$(document).ready(function() { $('#table$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
			</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function status_graph(){


    $tpl=new template_admin();
    $page=CurrentPageName();

    $q=new postgres_sql();
    $tablename=$_GET["tablename"];
    $results=$q->QUERY_SQL("SELECT SUM(hits) as t,reason FROM $tablename  WHERE refused=1 group by reason ORDER BY t desc LIMIT 20");
    while ($ligne = pg_fetch_assoc($results)) {
        if($GLOBALS["VERBOSE"]){echo $ligne["reason"]."\t".$ligne["t"]."\n";}

        if(preg_match("#SecuriteInfo\.com\.(.*?)\.UNOFFICIAL#",$ligne["reason"],$re)){
            $ligne["reason"]=$re[1];
        }

        $PieData[$ligne["reason"]]=$ligne["t"];
    }


    $highcharts=new highcharts();
    $highcharts->container=$_GET["container"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="TOP {refused}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("TOP {refused}/{description}");
    echo $highcharts->BuildChart();
    echo "\nLoadAjax('{$_GET["container2"]}','$page?status-table=yes&tablename=$tablename')";



}

function status_table(){
    $tpl=new template_admin();

    $html[]="<table class='table table-striped'>";
    $html[]="<tr>";
    $html[]="<th>{reason}</th>";
    $html[]="<th>{hits}</th>";
    $html[]="</tr>";
    $q=new postgres_sql();
    $tablename=$_GET["tablename"];
    $results=$q->QUERY_SQL("SELECT SUM(hits) as t,reason FROM $tablename  WHERE refused=1 group by reason ORDER BY t desc LIMIT 20");
    while ($ligne = pg_fetch_assoc($results)) {
        if($GLOBALS["VERBOSE"]){echo $ligne["reason"]."\t".$ligne["t"]."\n";}
        $hits=$ligne["t"];
        $reason=$ligne["reason"];
        $reason_text=$reason;
        if(preg_match("#SecuriteInfo\.com\.(.*?)\.UNOFFICIAL#",$reason_text,$re)){
            $reason_text=$re[1];
        }

        $js2="Loadjs('fw.postfix.transactions.php?second-query=".base64_encode("WHERE reason='$reason'")."&title=".urlencode("{see_messages_with}:$reason")."')";

        $hits=FormatNumber($hits);
        $html[]="<tr>";
        $html[]="<td>".$tpl->td_href($reason_text,"{see_messages_with}","blur()")."</td>";
        $html[]="<td style='width:1%' nowrap>{$hits}</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="<script>";

    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}



function stat_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $email=$_GET["stat-js"];
    $emailenc=urlencode($email);
    $table_name=$_GET["tablename"];
    $q=new postgres_sql();


    $ligne=$q->mysqli_fetch_array("SELECT count(*) as approximate_row_count from $table_name;");
    if($GLOBALS["VERBOSE"]){echo "*********************\n";print_r($ligne);}
    $Rows=intval($ligne["approximate_row_count"]);



    if($Rows==0){

        if(isset($_GET["stats-builded"])){
            $tpl->js_error("$table_name $q->mysql_error");
            return;
        }


        $tpl->js_dialog1("{ARTICA_STATISTICS}","$page?build-stats=$emailenc&tablename=$table_name");
        return;
    }
    $tpl->js_dialog1("{ARTICA_STATISTICS} $email","$page?tabs=$emailenc&tablename=$table_name",1600);



}

function stats_gen_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $email=$_GET["build-stats"];
    $emailenc=urlencode($email);
    $uuid=md5($email);
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/smtp.stats.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/smtp.stats.progress.log";
    $ARRAY["CMD"]="postfix2.php?smtp-member-stats=$emailenc";
    $ARRAY["TITLE"]="{building}";
    $ARRAY["AFTER"]="Loadjs('$page?stat-js=$emailenc&stats-builded=yes&tablename={$_GET["tablename"]}')";
    $prgress=base64_encode(serialize($ARRAY));
    $task="Loadjs('fw.progress.php?content=$prgress&mainid=$uuid');";

    $html[]="
<H1>$email</H1><H2>{building_statistics}....</H2>
<div id='$uuid'></div><script>$task</script>";

    echo $tpl->_ENGINE_parse_body($html);


}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["MEMBERS_SEARCH"]==null){$_SESSION["MEMBERS_SEARCH"]="";}
	
	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{my_members}</h1></div>
	</div>
		

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["MEMBERS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:TableLoaderMyMemberearch();\">Go!</button>
      	</span>
     </div>
    </div>
</div>	
	
	
	
		
	<div class='row'><div id='progress-firehol-restart'></div>";

	$html[]="<div class='ibox-content'>

	<div id='table-loader-my-members'></div>

	</div>
	</div>
		
		
		
<script>
	$.address.state('/');
	$.address.value('smtp-members-statistics');
	$.address.title('SMTP: {members_stats}');
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			TableLoaderMyMemberearch();
		}
		
		function TableLoaderMyMemberearch(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader-my-members','$page?table=yes&t=$t&search='+ss+'&function=TableLoaderMyMemberearch');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			TableLoaderMyMemberearch();
		}
		Start$t();
	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("SMTP: {members_stats}",$html);
		echo $tpl->build_firewall();
		return;
	}	

	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ldap=new clladp();
	$stringtofind=url_decode_special_tool($_GET["search"]);
	$stringtofind="*$stringtofind*";
	$stringtofind=str_replace("**", "*", $stringtofind);
	$stringtofind=str_replace("**", "*", $stringtofind);
    $stringtofind=str_replace("*", "%", $stringtofind);


	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-smtp-members' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text' width=1%>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{email}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";

	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
    $TRCLASS=null;
    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT * FROM smtp_users WHERE tomail LIKE '$stringtofind' ORDER BY tomail LIMIT 250");
    if($results) {
        while ($ligne = pg_fetch_assoc($results)) {

            $tomail = $ligne["tomail"];
            $table_name = str_replace("@", "", strtolower($tomail));
            $table_name = str_replace(".", "", $table_name);
            $table_name = str_replace("-", "", $table_name);
            $tomailenc = urlencode($tomail);
            if ($TRCLASS == "footable-odd") {
                $TRCLASS = null;
            } else {
                $TRCLASS = "footable-odd";
            }
            $js = "Loadjs('$page?stat-js=$tomailenc&tablename=$table_name')";
            $delete = $tpl->icon_nothing();
            if ($q->TABLE_EXISTS($table_name)) {
                $delete = $tpl->icon_delete("Loadjs('$page?delete-js=$tomailenc&tablename=$table_name')");
            }


            $html[] = "<tr class='$TRCLASS'>";
            $html[] = "<td class=\"center\" width='1%'><i class='far fa-users'></i></td>";
            $html[] = "<td class=\"\">" . $tpl->td_href($tomail, "{click_to_edit}", $js) . "</td>";
            $html[] = "<td  width='1%' class=\"center\">$delete</td>";
            $html[] = "</tr>";

        }
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
	$(document).ready(function() { $('#table-smtp-members').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";
echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}