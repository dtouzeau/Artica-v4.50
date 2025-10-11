<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__).'/ressources/charts.php');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}

page();


function page(){


    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    if(!isset($_SESSION["MEMBERS_SEARCH"])){$_SESSION["MEMBERS_SEARCH"]="*";}
    $TINY_ARRAY["TITLE"]="{members} / {tcp_address}";
    $TINY_ARRAY["ICO"]="fa-regular fa-people-group";
    $TINY_ARRAY["EXPL"]="{proxy_service_about}";
    $TINY_ARRAY["URL"]="proxy-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html="
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["MEMBERS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row' id='spinner'>
		<div id='progress-firehol-restart'></div>
		<div  class='ibox-content'>
			<div id='table-loader'></div>
		</div>
	</div>
	</div>
	<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
        $jstiny
	</script>";


    echo $tpl->_ENGINE_parse_body($html);
}
function search(){
    $tpl=new template_admin();
    $t=time();
    $html[]="

<table class=\"table table-hover\" id='table-$t'>
	<thead>
    	<tr>
        	<th>{ipaddr}</th>
        	<th>{requests}</th>
            <th>TUNNEL</th>
            <th>NONE</th>
            <th>HITS</th>
            <th>MISS</th>
            <th>REDIRECT</th>
            
        </tr>
  	</thead>
	<tbody>
";
    $RQS=null;
    $q=new lib_sqlite("/home/artica/SQLITE/mgr_client_list.db");
    $search=$_GET["search"];
    if($search<>null){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $RQS="WHERE ( (ipaddr LIKE '$search') OR (uid LIKE '$search') )";
    }

    $sql="SELECT * FROM mgr_client_list $RQS ORDER BY RQS DESC LIMIT 500";
    $results=$q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne){

        $ipaddr=$ligne["ipaddr"];
        $uid=$ligne["uid"];
        $CUR_CNX=intval($ligne["CURN_CNX"]);
        $RQS=$tpl->FormatNumber(intval($ligne["RQS"]));
        $TAG_NONE=$tpl->FormatNumber(intval($ligne["TAG_NONE"]));
        $TCP_HIT=$tpl->FormatNumber(intval($ligne["TCP_HIT"]));
        $TCP_REDIRECT=$tpl->FormatNumber(intval($ligne["TCP_REDIRECT"]));
        $TCP_TUNNEL=$tpl->FormatNumber(intval($ligne["TCP_TUNNEL"]));
        $TCP_MISS=$tpl->FormatNumber(intval($ligne["TCP_MISS"]));
        if($uid<>null){$uid="<small>($uid)</small>";}

        $html[]="<tr>
				<td ><strong>$ipaddr</strong> $uid</td>
				<td width='1%' nowrap>$RQS</td>
				<td width='1%' nowrap>$TCP_TUNNEL</td>
                <td width='1%' nowrap>$TAG_NONE</td>  
                <td width='1%' nowrap>$TCP_HIT</td> 
                <td width='1%' nowrap>$TCP_MISS</span></td>   
                <td width='1%' nowrap>$TCP_REDIRECT</td>
                </tr>";

        
    }


    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</tbody></table>";
    $html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);


}