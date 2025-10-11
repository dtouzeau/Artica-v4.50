<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsPostfixAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["statistics"])){statistics();exit;}
if(isset($_GET["pie"])){graph();exit;}
if(isset($_GET["postfix-refused-table"])){postfix_refused_table();exit;}
if(isset($_GET["line-week"])){line_week();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();





function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{refused}</h1></div>
	</div>
	<div class='row'>
		<div id='postfix-refused-messages' class='ibox-content'></div>
	</div>
	";


		
		
$html[]="<script>
	$.address.state('/');
	$.address.value('postfix-refused');
	$.address.title('Artica: SMTP refused messages');
	LoadAjax('postfix-refused-messages','$page?tabs=yes');
</script>";

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{statistics}"]="$page?statistics=yes";
    $array["{events}"]="fw.postfix.transactions.php?refused=yes";
    echo $tpl->tabs_default($array);
}

function statistics(){
    $page=CurrentPageName();
    $tpl=new template_admin();
//

    $html[]="<table style='width:90%'>";
    $html[]="<tr>";
    $html[]="<td valign=top style='width:50%'><div id='postfix-refused-pie' style='height:450px;width:650px'></div></td>";
    $html[]="<td valign=top><div id='postfix-refused-table' style='margin-left:10px;margin-rigth:10px;padding-top:10px'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td valign=top colspan='2'><div id='postfix-refused-line' style='height:450px;width:99%'></div>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?pie=yes')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}


function graph(){
    $PieData=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PIE_REFUSED_SMTP")));
    $tpl=new template_admin();
    $page=CurrentPageName();


    $highcharts=new highcharts();
    $highcharts->container="postfix-refused-pie";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="TOP {refused}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("TOP {refused}/{description}");
    echo $highcharts->BuildChart();
    echo "\n";
    echo "LoadAjax('postfix-refused-table','$page?postfix-refused-table=yes');\n";

}
function postfix_refused_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PieData=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PIE_REFUSED_SMTP")));
    $html[]="<table class='table table-striped'>";
    $html[]="<tr>";
    $html[]="<th>{reason}</th>";
    $html[]="<th>{hits}</th>";
    $html[]="</tr>";

    foreach ($PieData as $reason=>$hits){


        $js2="Loadjs('fw.postfix.transactions.php?second-query=".base64_encode("WHERE reason='$reason'")."&title=".urlencode("{see_messages_with}:$reason")."')";

        $hits=FormatNumber($hits);
        $html[]="<tr>";
        $html[]="<td>".$tpl->td_href($reason,"{see_messages_with}",$js2)."</td>";
        $html[]="<td style='width:1%' nowrap>{$hits}</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?line-week=yes')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function line_week(){
    $PieData=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GRAPH_REFUSED_SMTP_WEEK")));
    $title="{messages}";
    $timetext="{hours}";
    $highcharts=new highcharts();
    $highcharts->container="postfix-refused-line";
    $highcharts->xAxis=$PieData["xdata"];
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="22px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="messages";
    $highcharts->xAxis_labels=false;
    $highcharts->LegendPrefix=null;
    $highcharts->LegendSuffix="{messages}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{messages}"=>$PieData["ydata"]);
    echo $highcharts->BuildChart();
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}