<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    if(!isset($_SESSION["MODSECUTIRY_UPDATE_SEARCH"])){$_SESSION["MODSECUTIRY_UPDATE_SEARCH"]="50 events";}


    $html[]="
        <div class=\"row\" style='margin-top: 10px'>
<div class='ibox-content'>
			<div class=\"input-group\" >
	      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["MODSECUTIRY_UPDATE_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
	      		<span class=\"input-group-btn\"><button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button></span>
	     	</div>
    	</div>
	</div>
	<div class='row'><div id='progress-modsec-updt'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>

		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss+'&function=ss$t');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";


    echo $tpl->_ENGINE_parse_body($html);

}

function search(){

    $tpl            = new template_admin();




    $function       = $_GET["function"];

    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    if($rp==0){$rp=250;}
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/reverse-proxy/modsec/updevents/$search/$rp"));







    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{date}</th>
        	<th nowrap>{PID}</th>
            <th nowrap>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    $c=0;
    $MONTHS=array("Jan"=>1,"Feb"=>2,"Mar"=>3,"Apr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Aug"=>8,"Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);

    foreach ($json->Events as $line){
        $t1=time();
        $line=trim($line);
        if(!preg_match("#(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#", $line,$re)){continue;}
        $month=$MONTHS[trim($re[1])];
        $day=$re[2];
        $time=$re[3];
        $year=date("Y");
        $stime=strtotime("$year-$month-$day $time");
        $zdate=$tpl->time_to_date($stime,true);
        $pid=$re[4];
        $events=$re[5];
        $class="";

        if(strpos($events,"WARNING")>0){
            $class="text-warning";
        }

        if(preg_match("#(Cannot|failed|unable|error)#i",$events)){
            $class="text-danger";
        }



        $events=str_replace("[INFO]:","<span class='label label-info'>INFO..</span>",$events);
        $events=str_replace("[WARNING]:","<span class='label label-warning'>WARN</span>",$events);
        $events=str_replace("[ERROR]:","<span class='label label-danger'>ERRO.</span>",$events);
        $events=str_replace("[SUCCESS]:","<span class='label label-primary'>SUCC.</span>",$events);

        $c++;
        $html[]="<tr>
				<td style='width:1%' class='$class' nowrap>$zdate</td>
				<td style='width:1%' class='$class' nowrap>$pid</td> 
				<td style='width:99%' class='$class'><strong>$events</strong></td>  
                </tr>";

        $t2=time();
        if($GLOBALS["VERBOSE"]){
            VERBOSE("$c  ".($t2-$t1)." seconds");
        }

    }

    $html[]="</tbody></table>";

    $serviceupdate=$tpl->framework_buildjs("nginx:/reverse-proxy/modsec/update",
        "modsecurity-download.progress","modsecurity-download.log","progress-modsec-updt",
        "$function()"
    );



    $bt[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bt[]="<label class=\"btn btn btn-primary\" OnClick=\"$serviceupdate\"><i class='fa fa-download'></i> {update_now} </label>";
    $bt[]="</div>";
    $bts=@implode("\n",$bt);

    $TINY_ARRAY["TITLE"]="{update_events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{MODSECUTIRY_UPDATE_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$bts;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="</script>";



    echo $tpl->_ENGINE_parse_body($html);



}
