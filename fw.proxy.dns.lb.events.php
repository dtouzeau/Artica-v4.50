<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}

js();


function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog5("{load_balancer} {events}","$page?popup=yes",950);
}

function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,null);
    echo "</div>";
}

function search(){

    $tpl        = new template_admin();
    $MAIN       = $tpl->format_search_protocol($_GET["search"],false,false,false,true);
    $line       = base64_encode(serialize($MAIN));
    $tfile      = PROGRESS_DIR."/proxylb.events.syslog";
    $pat        = null;

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?proxy-lb-events=$line");
    $data=explode("\n",@file_get_contents($tfile));
    krsort($data);
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";


    foreach ($data as $line){
        $line=trim($line);
        $class=null;


        $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$line</td>
				</tr>";

    }



    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents($pat)."</i></div>";
    echo $tpl->_ENGINE_parse_body($html);


}