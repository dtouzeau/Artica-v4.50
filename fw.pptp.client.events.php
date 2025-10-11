<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}

page();



function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
    if($_SESSION["PPTP_SEARCH"]==null){$_SESSION["PPTP_SEARCH"]="limit 200";}

    $html[]="
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["PPTP_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>	
<div class='row'><div id='progress-firehol-restart'></div>";
$html[]="<div class='ibox-content'>
	<div id='table-loader'></div>
	</div>
	</div>";

    $html[]="<script>";
    $html[]="function Search$t(e){";
    $html[]="if(!checkEnter(e) ){return;}";
    $html[]="ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?table=yes&t=$t&search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth_sql=null;
    $token=null;
    $class=null;
    $t=$_GET["t"];
    if(!is_numeric($t)){$t=time();}
    $zdate=$tpl->_ENGINE_parse_body("{date}");
    $events=$tpl->javascript_parse_text("{events}");
    $target_file=PROGRESS_DIR."/pptp.log";


    $html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";


    $_SESSION["PPTP_SEARCH"]=trim(strtolower($_GET["search"]));
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));


    $ss=base64_encode($search["S"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pptp.client.php?searchlogs=$ss&rp={$search["MAX"]}");
    $datas=explode("\n",@file_get_contents($target_file));


    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zdate</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";


    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    krsort($datas);

    $td1prc=$tpl->table_td1prc();

    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $results=$q->QUERY_SQL("SELECT ID,connexion_name FROM `connections` ORDER BY connexion_name");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $TRCLASS=null;

    foreach ($results as $index=>$ligne){
        $connexions_name[$ligne["ID"]]=$ligne["connexion_name"];

    }

    foreach ($datas as $key=>$line){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text_class=null;
        if(trim($line)==null){continue;}


        if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?pptp.*?\[([0-9]+)\]:\s+(.+)#",$line,$re)){continue;}
        $year=date("Y");
        $datestr="{$re[1]} {$re[2]} {$re[3]} $year";
        $date=strtotime($datestr);
        VERBOSE("DATE: $datestr ($date)",__LINE__);

        $pid=$re[4];
        $line=$re[5];
        $datetext=$tpl->time_to_date($date,true);
        if(preg_match("#(does not exist|Error while|Can not|terminated|Terminating|Closing connection|is not connected|Could not|exited with error|timed out)#i", $line)){
            $text_class="text-danger";
        }

        if(preg_match("#(connection established|call established|succeed)#i",$line)){
            $text_class="text-success";
        }

        if(preg_match("#succeed with local interface#i",$line)){
            $text_class="text-info font-bold";
        }

        if(preg_match("#watchdog:#i",$line)){
            $text_class="text-warning font-bold";
        }

        if(preg_match("#CNX([0-9]+)#",$line,$re)){
            $line=str_replace("CNX{$re[1]}","<strong>".$connexions_name[$re[1]]."</strong>",$line);
        }


        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td $td1prc class=\"$text_class\" >$datetext</td>";
        $html[]="<td $td1prc class=\"$text_class\">$pid</td>";
        $html[]="<td class=\"$text_class\">$line</a></td>";
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
    $html[]="</table><div><i></i></div>";
    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable(
{
\"filtering\": {
\"enabled\": false
},
\"sorting\": {
				\"enabled\": true
			}

			}
		

	); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);

}
