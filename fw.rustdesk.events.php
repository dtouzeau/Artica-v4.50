<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){exit();}
if(isset($_GET["search"])){search();exit;}
page();


function page():bool{
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_RUSTDESK} {events}",ico_eye,
        "{APP_RUSTDESK_EXPLAIN}",null,"rustdesk-events","progress-rustdesk-restart",
        true,"table-rustdesk");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function search(){
    $tpl=new template_admin();
	$MAIN=$tpl->format_search_protocol($_GET["search"],false,false,false,true);
	$line=base64_encode(serialize($MAIN));
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("rustdesk.php?events=$line");
	$filename=PROGRESS_DIR."/rustdesk.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>&nbsp;</th>
        	<th>$date_text</th>        	
        	<th>{service}</th>
        	<th>PID</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["RUSTDESK_SEARCH"]=$_GET["search"];}
	rsort($data);

    $INFOS["info"]="<span class='label label-info'>{info}</span>";
    $INFOS["warning"]="<span class='label label-warning'>{warning}</span>";
    $INFOS["error"]="<span class='label label-danger'>{error}</span>";

    $LABELz["info"]="label-info";
    $LABELz["warning"]="label-warning";
    $LABELz["error"]="label-danger";


    $SERVICEz["relay_server"]="{APP_RUSTDESKBBR}";
    $SERVICEz["rendezvous_server"]="{APP_RUSTDESKBBS}";
    $SERVICEz["common"]="{all}";
    $SERVICEz["peer"]="{peers}";
    $SERVICEz["Artica"]="Artica";

    $COLORS["info"]="text-default";
    $COLORS["warning"]="text-warning font-bold";
    $COLORS["error"]="text-danger font-bold";
    $year=date("Y");
	foreach ($data as $line){
		$line=trim($line);

        $line=str_replace('\"',"&quot;",$line);
        $FTime=0;
        $PID=0;
        $msg=null;
        $LEVEL="INFO";
        $SERVICE="Artica";

        if(preg_match("#rustdesk:.*?\[(.+?)\..*?\]\s+([A-Z]+)\s+\[src\/(.+?)\..*?\]\s+(.+)#",$line,$ri)) {
            $FTime = strtotime($ri[1]);
            $LEVEL=$ri[2];
            $SERVICE=$ri[3];
            $msg=$ri[4];
        }else{
            if(preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?rustdesk\[([0-9]+)\]:\s+(.+)#",$line,$ri)) {
                $Month=$tpl->MonthToInteger($ri[1]);
                $Day=$ri[2];
                $Time=$ri[3];
                $PID=$ri[4];
                $msg=$ri[5];
                $FTime = strtotime("$Month-$Day-$year $Time");
            }


        }
        if($FTime==0){continue;}
        $zDate=$tpl->time_to_date($FTime,true);
        $level=strtolower($LEVEL);
        $ServiceIco=$SERVICE;
        if(isset($SERVICEz[$SERVICE])) {
            $ServiceIco = $SERVICEz[$SERVICE];
        }

        $color=$COLORS[$level];
        $PIDText=null;
        if($PID>0){
            $PIDText=$PID;
        }
		
		$html[]="<tr>
				<td width=1% class='$color' nowrap>$INFOS[$level]</td>
				<td width=1% class='$color' nowrap>$zDate</td>
				<td width=1% class='$color' nowrap><span class='label {$LABELz[$level]}'>$ServiceIco</span></td>
				<td width=1% class='$color' nowrap>$PIDText</td>
				<td class='$color' nowrap >$msg</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/docker.syslog.pattern")."</i></div>";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
	
	
}

