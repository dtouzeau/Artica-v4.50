<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["start-js"])){start_js();exit;}
if(isset($_GET["stop-js"])){stop_js();exit;}
if(isset($_POST["ID"])){backends_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["backend-js"])){backend_js();exit;}
if(isset($_GET["backend-zoom-js"])){backend_zoom_js();exit;}

if(isset($_GET["backend-popup"])){backend_popup();exit;}
if(isset($_GET["backend-enable-js"])){backends_enable();exit;}
if(isset($_GET["backend-delete-js"])){backend_delete_js();exit;}
if(isset($_POST["backend-delete"])){backend_delete();exit;}
if(isset($_GET["backend-zoom"])){backend_zoom();exit;}
if(isset($_GET["page"])){page();exit;}
tabs();


function tabs(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $array["{backends}"]="$page?page=yes";
    echo $tpl->tabs_default($array);

}

function stop_js(){
    $page=CurrentPageName();
    $sock=new sockets();
    $pname=urlencode($_GET["stop-js"]);
    $sock->getFrameWork("haexchange.php?stop-socket=$pname");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('backend-list','$page?table=yes');";
}



function start_js(){
    $page=CurrentPageName();
    $sock=new sockets();
    $pname=urlencode($_GET["start-js"]);
    header("content-type: application/x-javascript");
    $sock->getFrameWork("haexchange.php?start-socket=$pname");
    echo "LoadAjaxSilent('backend-list','$page?table=yes');";
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();



    $html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{backends}</h1>
	<p></p>
	
	</div>
</div>                    
<div class='row'><div id='haexchange-backend-restart' class='white-bg'></div>
	<div class='ibox-content'>
		<div id='backend-list'></div>
     </div>
</div>
<script>
	$.address.state('/');
	$.address.value('/haexchange-backends');
	LoadAjax('backend-list','$page?table=yes');
</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{backends}",$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);

}

function backend_zoom_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["backend-zoom-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM haexchange WHERE ID=$ID");
    $title="{$ligne["backendname"]}";
    $tpl->js_dialog2($title, "$page?backend-zoom=$ID");
}


function backend_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$new_backend=$tpl->_ENGINE_parse_body("{new_backend}");
	$ID=intval($_GET["backend-js"]);


	if($ID==0){$title="$new_backend";}else{
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM haexchange WHERE ID=$ID");
	    $title="{$ligne["backendname"]}";
	}

	$tpl->js_dialog2($title, "$page?backend-popup=$ID");
}
function backends_enable(){
    $page=CurrentPageName();
	$ID=intval($_GET["backend-enable-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM haexchange WHERE ID=$ID");
	if(intval($ligne["enabled"])==1){$enabled=0;}else{$enabled=1;}
    $q->QUERY_SQL("UPDATE haexchange SET enabled=$enabled WHERE ID=$ID");
    header("content-type: application/x-javascript");
	echo reload_js()."\n\n";
}
function backend_delete_js(){
	$tpl=new template_admin();
	$ID=intval($_GET["backend-delete-js"]);

    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM haexchange WHERE ID=$ID");
    $title="{$ligne["backendname"]}";
    header("content-type: application/x-javascript");
	$js[]="$('#{$_GET["md"]}').remove()";
	$js[]=reload_js();
	$tpl->js_confirm_delete($title , "backend-delete", $ID,@implode(";", $js));
	
}

function backend_zoom(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["backend-zoom"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM haexchange WHERE ID=$ID");
    $title="{$ligne["backendname"]}";

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/hacluster.connect.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/hacluster.connect.txt";
    $ARRAY["CMD"]="haexchange.php?connect-node=$ID";
    $ARRAY["TITLE"]="{reconfigure} $title";
    $ARRAY["AFTER"]="LoadAjax('backend-list','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $reconfigure_node="Loadjs('fw.progress.php?content=$prgress&mainid=reconfigure-progress-$ID')";

    $html[]="<H2>$title</H2><hr>";
    $html[]="<div id='reconfigure-progress-$ID'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td width='33%'>".$tpl->button_autnonome("{reconfigure}",$reconfigure_node,"fas fa-sync-alt")."</td>";
    $html[]="</tr>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);

}

function reload_js(){
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress.txt";
    $ARRAY["CMD"]="haexchange.php?reload=yes";
    $ARRAY["TITLE"]="{reloading}";
    $ARRAY["AFTER"]="LoadAjax('backend-list','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=haexchange-backend-restart');\n";
}

function backend_delete(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["backend-delete"];
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $q->QUERY_SQL("DELETE  FROM haexchange WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;}
	
}

function backend_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["backend-popup"];
    $ligne=array();

    $f[]="mapi";
    $f[]="rpc";
    $f[]="owa";
    $f[]="eas";
    $f[]="ecp";
    $f[]="ews";
    $f[]="oab";
    $f[]="autodiscover";
    $f[]="smtp";
    $f[]="imaps";

    if($ID>0) {
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM haexchange WHERE ID=$ID");

    }
	$jsadd=null;

    if($ID>0) {
        $title = $ligne["backendname"];
        $buttonname="{apply}";
    }else{
        $ligne["backendname"]="proxy-".time();
        $title="{new_backend}";
		$jsadd="dialogInstance2.close();";
		$ligne["listen_port"]="8090";
		$ligne["bweight"]=1;
        $buttonname="{add}";
        $ligne["enabled"]=1;

        foreach ($f as $field){
            $ligne[$field]=1;
        }
	}



    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress.txt";
    $ARRAY["CMD"]="haexchange.php?reload=yes";
    $ARRAY["TITLE"]="{reloading}";
    $ARRAY["AFTER"]="LoadAjax('backend-list','$page?table=yes');$jsadd";
    $prgress=base64_encode(serialize($ARRAY));
    $jsreload="Loadjs('fw.progress.php?content=$prgress&mainid=haexchange-backend-restart')";
	
	$jsafter[]="";
	$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}", $ligne["enabled"],true);
    $form[]=$tpl->field_text("backendname", "{backendname}", $ligne["backendname"],true);
	$form[]=$tpl->field_ipaddr("ipaddr", "{destination_address}", $ligne["ipaddr"]);

	$EXC[0]="Exchange 2010";
	$EXC[1]="Exchange 2013";
    $EXC[2]="Exchange 2016";
    $EXC[2]="Exchange 2019";

    $form[]=$tpl->field_array_hash($EXC,"exchtype","nonull:MS Exchange",$ligne["exchtype"]);

	$form[]=$tpl->field_numeric("bweight","{weight}", $ligne["bweight"]);



    foreach ($f as $field){
        $form[]=$tpl->field_checkbox($field,$field, $ligne[$field],false);

    }


    $html=$tpl->form_outside($title, @implode("\n", $form),null,
            $buttonname,"$jsadd;$jsreload","AsSquidAdministrator",true);
	echo $tpl->_ENGINE_parse_body($html);
	
}

function backends_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $ID=$_POST["ID"];
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");

    if(!$q->FIELD_EXISTS("haexchange","exchtype")){
        $q->QUERY_SQL("ALTER TABLE haexchange ADD exchtype INTEGER NOT NULL DEFAULT 1");
    }

    if(!isset($_POST["listen_port"])){$_POST["listen_port"]=0;}
    if(!isset($_POST["status"])){$_POST["status"]=0;}
    if(!isset($_POST["imap"])){$_POST["imap"]=0;}
    $f[]="backendname";
    $f[]="ipaddr";
    $f[]="listen_port";
    $f[]="status";
    $f[]="bweight";
    $f[]="mapi";
    $f[]="rpc";
    $f[]="owa";
    $f[]="eas";
    $f[]="ecp";
    $f[]="ews";
    $f[]="oab";
    $f[]="autodiscover";
    $f[]="smtp";
    $f[]="imap";
    $f[]="imaps";
    $f[]="exchtype";

    foreach ($f as $field){
        $Add1[]=$field;
        $Add2[]="'{$_POST[$field]}'";
        $edit[]="$field='{$_POST[$field]}'";
    }


    if($ID==0){
        $sql="INSERT INTO haexchange (".@implode(",",$Add1).")
        VALUES(".@implode(",",$Add2).")";

        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $tpl=new template_admin();
            $q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
            echo "jserror:$q->mysql_error";
            return;
        }

        return;
    }


    $sql="UPDATE haexchange SET ".@implode(",",$edit)." WHERE ID=$ID";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl=new template_admin();
        $q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
        echo "jserror:$q->mysql_error";
        return;
    }
}

function status_zoom($ID,$class,$text){
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->td_href("<span class='label $class'>$text</span>","{action}","Loadjs('$page?backend-zoom-js=$ID');");
}

function table(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");



    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress.txt";
    $ARRAY["CMD"]="haexchange.php?reload=yes";
    $ARRAY["TITLE"]="{reloading}";
    $ARRAY["AFTER"]="LoadAjax('backend-list','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsreload="Loadjs('fw.progress.php?content=$prgress&mainid=haexchange-backend-restart')";


    $LoadStatus=LoadStatus();

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?backend-js=0');\">";
	$html[]="<i class='fa fa-plus'></i> {new_backend} </label>";

    $html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsreload\">";
    $html[]="<i class='fal fa-sync-alt'></i> {reload_service} </label>";

    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"LoadAjax('backend-list','$page?table=yes');\">";
    $html[]="<i class='fal fa-sync-alt'></i> {refresh} {table} </label>";

	$html[]="</div>";
	$html[]="<table id='table-haproxy-backends' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{backends}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{active2}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";




	$sql="SELECT *  FROM `haexchange` ORDER BY bweight";
	$results = $q->QUERY_SQL($sql);
	
	$TRCLASS=null;$ligne=null;


	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$error=null;
		$ID=$ligne["ID"];
        $MAIN=$LoadStatus[$ID];
		$md=md5(serialize($ligne));
		$listen_ip=$ligne["ipaddr"];
		$enabled=intval($ligne["enabled"]);


        $disable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?backend-enable-js=$ID')",null,"AsSquidAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?backend-delete-js=$ID&md=$md')","AsSquidAdministrator");
        $backendname=$tpl->td_href($ligne["backendname"],"$listen_ip","Loadjs('$page?backend-js=$ID')");


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td valign='top'><strong style='font-size:22px'>$backendname ($listen_ip)</strong></td>";
        $html[]="<td style='width:1%' nowrap>$disable</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";

        if($enabled==1) {
            $html[] = "<tr class='$TRCLASS' id='$md-1'>";
            $html[] = "<td valign='top' colspan='3'>";
            $html[] = "<table>";
            $html[] = "<thead>";
            $html[] = "<tr>";
            $html[] = "<th class='text-capitalize center' data-type='text'>{status}</th>";
            $html[] = "<th class='text-capitalize center' data-type='text'>{proto}</th>";
            $html[] = "<th class='text-capitalize' data-type='text'>IN</th>";
            $html[] = "<th class='text-capitalize' data-type='text'>OUT</th>";
            $html[] = "<th class='text-capitalize' data-type='text'>RQS</th>";
            $html[] = "<th class='text-capitalize center' data-type='text'>ACT</th>";
            $html[] = "</tr>";
            $html[] = "</thead>";
            foreach ($MAIN as $protocol => $array) {
                if ($protocol == null) {
                    continue;
                }
                $button = $tpl->icon_nothing();
                $IN = $tpl->icon_nothing();
                $OUT = $tpl->icon_nothing();
                $RQS = $tpl->icon_nothing();


                //  echo $protocol."<hr>";
                // print_r($array);

                if (isset($array["IMG"])) {
                    $img = $array["IMG"];
                    $button = $array["BUTTON"];
                    $color = $array["COLOR"];
                    $IN = $array["BIN"];
                    $OUT = $array["BOUT"];
                    $RQS = $array["REQS"];
                    $error_text=trim($array["ERR"]);
                }

                if ($IN > 1024) {
                    $IN = FormatBytes($IN / 1024);
                } else {
                    $IN = "$IN Bytes";
                }
                if ($OUT > 1024) {
                    $OUT = FormatBytes($OUT / 1024);
                } else {
                    $OUT = "$OUT Bytes";
                }

                if($error_text<>null){
                    $error_text="<br><small>".htmlspecialchars($error_text)."</small>";
                }

                $RQS = $tpl->FormatNumber($RQS);
                $html[] = "<tr>";
                $styleCol1 = "text-align: left;padding-left:10px;padding-top:5px;width:15%;color:$color";
                $styleCol0 = "text-align: left;padding-left:10px;padding-top:5px;width:10%;";
                $styleCol2 = "text-align: left;padding-left:10px;padding-top:5px;width:20%;color:$color;";
                $spancol2="text-transform:uppercase;font-weight:bold;font-size:16px";
                $html[] = "<td valign='top' style='$styleCol0'>$img</td>";
                $html[] = "<td valign='top' style='$styleCol2'><span style='$spancol2'>$protocol</span>$error_text</td>";
                $html[] = "<td valign='top' style='$styleCol1'>$IN</td>";
                $html[] = "<td valign='top' style='$styleCol1'>$OUT</td>";
                $html[] = "<td valign='top' style='$styleCol1'>$RQS</td>";
                $html[] = "<td valign='top' style='$styleCol1'>$button</td>";
                $html[] = "</tr>";

            }
            $html[] = "</table>";
            $html[] = "</td>";
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
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}

function LoadStatus(){
    $page=CurrentPageName();
    $users=new usersMenus();

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("haexchange.php?global-stats=yes");
    $xtable=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/haexchange.stattus.dmp"));

    $typof=array(0=>"frontend", 1=>"backend", 2=>"server", 3=>"socket");
    $status["UNK"]="unknown";
    $status["INI"]="initializing";
    $status["SOCKERR"]="socket error";
    $status["L4OK"]="check passed on layer 4, no upper layers testing enabled";
    $status["L4TMOUT"]="layer 1-4 timeout";
    $status["L4CON"]="layer 1-4 connection problem";
    $status["L6OK"]="check passed on layer 6";
    $status["L6TOUT"]="layer 6 (SSL) timeout";
    $status["L6RSP"]="layer 6 invalid response - protocol error";
    $status["L7OK"]="check passed on layer 7";
    $status["L7OKC"]="check conditionally passed on layer 7, for example 404 with disable-on-404";
    $status["L7TOUT"]="layer 7 (HTTP/SMTP) timeout";
    $status["L7RSP"]="layer 7 invalid response - protocol error";
    $status["L7STS"]="layer 7 response error, for example HTTP 5xx";

    $ERR["SOCKERR"]=true;
    $ERR["L4TMOUT"]=true;
    $ERR["L4CON"]=true;
    $ERR["L6TOUT"]=true;
    $ERR["L6RSP"]=true;
    $ERR["L7TOUT"]=true;
    $ERR["L7RSP"]=true;
    $ERR["L7STS"]=true;


    foreach ($xtable as $num=>$ligne) {
        $check_status_text=null;
        $protocol=null;
        $ligne = trim($ligne);
        if ($ligne == null) {continue;}


        $f = explode(",", $ligne);
        if (preg_match("#\##", $ligne)) {continue;}

        $pxname = $f[0];
        $svname = trim($f[1]);

        $CommandName="$pxname/$svname";
        $CommandNameEncoded=urlencode(base64_encode($CommandName));

        if($GLOBALS["VERBOSE"]){VERBOSE("Found server $pxname/$svname",__LINE__);}

        if(preg_match("#be_exchange_(.+)#",$pxname,$re)){
            $protocol=$re[1];
        }

        if(!preg_match("#^exchsrv([0-9]+)#",$svname,$re)){continue;}
        $ID=$re[1];

//        foreach ($f as $i=>$xline){
  //          echo "<li>($i): $xline</li>";
    //    }


        $qcur = $f[2];
        $qmax = $f[3];
        $scur = $f[4];
        $smax = $f[5];
        $slim = $f[6];
        $stot = $f[7];
        $bin = $f[8];
        $bout = $f[9];
        $dreq = $f[10];
        $dresp = $f[11];
        $ereq = $f[12];
        $econ = $f[13];
        $eresp = $f[14];
        $wretr = $f[15];
        $wredis = $f[16];
        $status = $f[17];
        $weight = $f[18];
        $act = $f[19];
        $bck = $f[20];
        $chkfail = $f[21];
        $chkdown = $f[22];
        $lastchg = $f[23];
        $downtime = $f[24];
        $qlimit = $f[25];
        $pid = $f[26];
        $iid = $f[27];
        $sid = $f[28];
        $throttle = $f[29];
        $lbtot = $f[30];
        $tracked = $f[31];
        $type = $typof[$f[32]];
        $rate = $f[33];
        $rate_lim = $f[34];
        $rate_max = $f[35];
        $check_status = $f[36];
        $check_code = $f[37];
        $check_duration = $f[38];
        $hrsp_1xx = $f[39];
        $hrsp_2xx = $f[40];
        $hrsp_3xx = $f[41];
        $hrsp_4xx = $f[42];
        $hrsp_5xx = $f[43];
        $hrsp_other = $f[44];
        $hanafail = $f[45];
        $req_rate = $f[46];
        $req_rate_max = $f[47];
        $req_tot = intval($f[48]);
        $cli_abrt = $f[49];
        $srv_abrt = $f[50];
        $error_text=$f[56];

        if($GLOBALS["VERBOSE"]){echo "<li>Status:$status</li>\n<li>check_status:$check_status</li>\n";}




        $img=null;
        $color=null;
        //$check_status_text=$status[$check_status];
        if(isset($ERR[$check_status])){$img="<div class='label label-danger' style='display:block;padding:5px'>$check_status</div>";$color="#D20C0C";}


        if($status=="MAINT"){
            $color="#F8AC59";
            $img="<div class='label label-warning' style='display:block;padding:5px'>{maintenance}</div>";
            $button="<button class='btn btn-w-m btn-warning' type='button' OnClick=\"Loadjs('$page?start-js=$CommandNameEncoded')\">{start}</button>";
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}

        }

        if(preg_match("#DOWN#", $status)){
            $button=null;
            $img="<div class='label label-danger' style='display:block;padding:5px'>{stopped}</div>";
            $color="#D20C0C";
            $button="<button class='btn btn-w-m btn-danger' type='button' OnClick=\"Loadjs('$page?start-js=$CommandNameEncoded')\">{start}</button>";
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}

        }

        if(preg_match("#UP#", $status)){
            $button=null;
            $img="<div class='label label-primary' style='display:block;padding:5px'>{running}</div>";
            $button="<button class='btn btn-w-m btn-primary' type='button' OnClick=\"Loadjs('$page?stop-js=$CommandNameEncoded')\">{stop}</button>";
            if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";}

        }


        $req_tot=$stot;

        $MAIN[$ID][$protocol]["IMG"]=$img;
        $MAIN[$ID][$protocol]["BUTTON"]=$button;
        $MAIN[$ID][$protocol]["COLOR"]=$color;
        $MAIN[$ID][$protocol]["BIN"]=intval($bin);
        $MAIN[$ID][$protocol]["BOUT"]=intval($bout);
        $MAIN[$ID][$protocol]["REQS"]=intval($req_tot);
        $MAIN[$ID][$protocol]["ERR"]=$error_text;





    }

    return $MAIN;
}





function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}