<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}
page();

function ShowID_js(){
	
	$id=$_GET["ShowID-js"];
	if(!is_numeric($id)){
	
		return;
	
	}$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT subject FROM events WHERE ID=$id";
	$q=new lib_sqlite("/home/artica/SQLITE/clusters_events.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$subject=$tpl->javascript_parse_text($ligne["subject"]);
	$tpl->js_dialog($subject, "$page?ShowID=$id");
	
}



function page(){
	$tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div style='margin-top:10px'></div>";
	$html[]=$tpl->search_block($page,"","","table-loader","&table=yes");
	$html[]="<div id='table-loader'></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();


    $t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->javascript_parse_text("{events}");






	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$date</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]=".";}
    $ss=base64_encode($search["S"]);
    $jsAfter="LoadAjax('table-loader','$page?table=yes');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $TRCLASS=null;

    $sock=new sockets();
    $EndPoint="/cluster/events/$ss";
    $data=$sock->REST_API($EndPoint);

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }



	$prioCL[0]="label-danger";
	$prioCL[1]="label-warning";
	$prioCL[2]="label-primary";
	
	$prioTX[0]="text-danger";
	$prioTX[1]="text-warning";
	$prioTX[2]="text-primary";
	$curs="OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='auto'\"";
	
	$TRCLASS=null;
    foreach ($json->Logs as $line){


        $text_class="";
        $array=ParseLine($line);
        if(strlen($array["subject"])<3){
            continue;
        }
		$zdate=$array["date"];
        $Content=$array["content"];
        $prio_class="label-primary";
        if(isset($array["prio"])) {
            $prio_class = $array["prio"];
            if (isset($prioCL[$array["prio"]])) {
                $prio_class = $prioCL[$array["prio"]];
            }
        }
        if(strlen($Content)>2){
            $array["subject"]=$array["subject"]."<br><small>$Content
</small>";
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text=$tpl->_ENGINE_parse_body($array["subject"]);
        $text=str_replace("HaClientService.go","",$text);
        $text=str_replace("hacluster/","",$text);
        $text=str_replace("acluster/","",$text);
        $text=str_replace("SyncPostgreSQL.go","",$text);
        $text=str_replace("ClusterClientRestore.","",$text);
        $text=str_replace("[HaClientService.","[",$text);
        $text=str_replace("ClusterClient.go","",$text);
        $text=str_replace("ClusterClient.","",$text);
        $text=str_replace("ClusterClientHTTP.","",$text);
        $text=str_replace("ClusterClientHTTP.go","",$text);

        if(preg_match("#.*?ArticaWeb:(.+?)\[crit\](.+?)#",$text,$re)){
            $zdate=$re["date"];
            $prio_class="label-danger";
            $text=$re[2];
        }

        if(strpos($text,"[INFO] Success")>0){
            $text=str_replace("[INFO] Success","",$text);
            $prio_class="label-primary";
        }

        if(strpos($text,"[WARNING]")>0){
            $text=str_replace("[WARNING]","",$text);
            $prio_class="label-warning";
        }


        if(preg_match("#Prio:2#",$text)){
            $prio_class="label-info";
            $text=str_replace("Prio:2","",$text);
        }

        if(preg_match("#\[SyncPostgreSQL:([0-9]+)\]#",$text,$match)){
            $prio_class="label-info";
            $text=str_replace("SyncPostgreSQL:$match[1]","",$text);
            $text="<span class='label $prio_class'>PostgreSQL</span>&nbsp;$text";
        }

        if(strpos($text,"Error ")>0){
            $prio_class="label-danger";
        }
        if(strpos($text,"Failed to")>0){
            $prio_class="label-danger";
        }
        if(strpos($text,"failed to")>0){
            $prio_class="label-danger";
        }
        $text=str_replace("ClusterTools.go[ClusterTools.ClusterEvents:","[",$text);

        if(strpos($text,"PersonalCategories")>0){
            $text="<span class='label $prio_class'>{categories}</span>&nbsp;$text";
        }
        if(strpos($text,"ClusterServicePort.go[ClusterServicePort.restStorage")>0){
            $text=str_replace("ClusterServicePort.go[ClusterServicePort.restStorage","[",$text);
            $text="<span class='label $prio_class'>{client}</span>&nbsp;$text";
        }
        if(strpos("  $text","ClusterServer.go[acluster.CreatePackage:")>0){
            $text=str_replace("ClusterServer.go[acluster.CreatePackage:","[",$text);
            $text="<span class='label $prio_class'>Master Package</span>&nbsp;$text";
        }
        if(strpos("  $text","UfdbguardBackup.go[acluster.BackupPersonalCategories:")>0){
            $text=str_replace("UfdbguardBackup.go[acluster.BackupPersonalCategories:","[",$text);
            $text="<span class='label $prio_class'>Master Package</span>&nbsp;$text";
        }
        if(strpos("  $text","Unbound.go[SyncUnbound:")>0){
            $text=str_replace("Unbound.go[SyncUnbound:","[",$text);
            $text="<span class='label $prio_class'>{APP_UNBOUND}</span>&nbsp;$text";
        }
        if(strpos("  $text","DNS Cache parameters")>0){
            $text="<span class='label $prio_class'>{APP_UNBOUND}</span>&nbsp;$text";
        }

        if(strpos("  $text","[PingMaster")>0){
            $text=str_replace("[PingMaster","[",$text);
            $text="<span class='label $prio_class'>Ping</span>&nbsp;$text";
        }
        if(strpos("  $text","go[Restore:")>0){
            $text=str_replace("go[Restore:","[",$text);
            $text="<span class='label $prio_class'>{restore}</span>&nbsp;$text";
        }
        if(strpos("  $text","SyncSQLite.go[ImportSQLite:")>0){
            $text=str_replace("SyncSQLite.go[ImportSQLite:","[",$text);
            $text="<span class='label $prio_class'>{restore} SQLite</span>&nbsp;$text";
        }
        
        if(strpos($text,"personal_categories")>0){
            $text="<span class='label $prio_class'>{categories}</span>&nbsp;$text";
        }


		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" nowrap style='width:1%;padding-top:12px;padding-bottom:12px;'><div class='label $prio_class' style='font-size:13px;width:100%' $curs >$zdate</a></div></td>";
		$html[]="<td class=\"$text_class\">$text</td>";
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
	$html[]="</table>";



    $TINY_ARRAY["TITLE"]="{events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="&nbsp;";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
	<script>$jstiny
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });

</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
function ParseLine($line):array{
    $tpl=new template_admin();
    $months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");
    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]: Prio:([0-9])\s+\[(.*?)\] \[(.*?)\]#",$line,$re)){
        $Month=$months[$re[1]];
        $Day=$re[2];
        $Hour=$re[3];
        $date=$tpl->time_to_date(strtotime(date("Y")."-$Month-$Day $Hour"),true);
        $Pid=$re[4];
        $prio=$re[5];
        $Subject=$re[6];
        $Content=$re[7];
        return array(
            "date"=>$date,
            "prio"=>$prio,
            "pid"=>$Pid,
            "subject"=>$Subject,
            "content"=>$Content
        );
    }
    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]: (.+)#",$line,$re)){
        $Month=$months[$re[1]];
        $Day=$re[2];
        $Hour=$re[3];
        $date=$tpl->time_to_date(strtotime(date("Y")."-$Month-$Day $Hour"),true);
        $Pid=$re[4];
        $Subject=$re[5];


        return array(
            "date"=>$date,
            "prio"=>4,
            "pid"=>$Pid,
            "subject"=>$Subject,
            "content"=>""
        );
    }
    return array(
        "date"=>"00-00-00",
        "prio"=>4,
        "pid"=>0,
        "subject"=>$line,
        "content"=>""
    );

}