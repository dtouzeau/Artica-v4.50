<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ifname-js"])){ifname_js();exit;}
if(isset($_GET["ifname-tabs"])){ifname_tabs();exit;}
if(isset($_GET["ifname-enable"])){ifname_enable();exit;}
if(isset($_POST["src-net"])){src_nets_save();exit;}
if(isset($_GET["src-nets"])){src_nets();exit;}
if(isset($_GET["dst-nets"])){dst_nets();exit;}
if(isset($_POST["dst-net"])){dst_nets_save();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["rules-text"])){echo rule_text($_GET["rules-text"]);exit;}
page();

function ifname_js(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
	$ID=intval($_GET["id"]);
    $ifname=$_GET["ifname-js"];
	if($ID==0){
	    $q->QUERY_SQL("INSERT INTO firehol_masquerade (nic,enabled) VALUES ('$ifname',0)");
	    $ligne=$q->mysqli_fetch_array("SELECT ID FROM firehol_masquerade WHERE nic='$ifname'");
	    $ID=intval($ligne["ID"]);
    }

	$tpl->js_dialog("Masquerade: $ifname","$page?ifname-tabs=$ID");
}

function delete_js(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("DELETE FROM pnic_nat WHERE ID={$_GET["delete-rule-js"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	echo "LoadAjax('table-loader','$page?table=yes');";
}
function ifname_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ifname-tabs"]);
    $array["{source_network}"]="$page?src-nets=$ID";
    $array["{whitelisted_destination}"]="$page?dst-nets=$ID";
    echo $tpl->tabs_default($array);
}
function ifname_enable(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

    $ID=intval($_GET["id"]);
    $ifname=$_GET["ifname-enable"];
    if($ID==0){
        $q->QUERY_SQL("INSERT INTO firehol_masquerade (nic,enabled) VALUES ('$ifname',0)");
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM firehol_masquerade WHERE nic='$ifname'");
        $ID=intval($ligne["ID"]);
    }
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM firehol_masquerade WHERE ID='$ID'");
    if(intval($ligne["enabled"])==0){$enabled=1;}else{$enabled=0;}
    $q->QUERY_SQL("UPDATE firehol_masquerade SET enabled=$enabled WHERE ID=$ID");

    echo "LoadAjaxSilent('rules-text-$ID','$page?rules-text=$ID');\n";
}
function src_nets(){
    $ID=intval($_GET["src-nets"]);
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $tpl=new template_admin();
    $ligne=$q->mysqli_fetch_array("SELECT include_src FROM firehol_masquerade WHERE ID='$ID'");

    $array=unserialize(base64_decode($ligne["include_src"]));
    if(!is_array($array)){$array=array();}

    $tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_textareacode("src-net",null,@implode("\n",$array));

    echo $tpl->_ENGINE_parse_body($tpl->form_outside(null,$form,"{excludetransparentin_explain}","{apply}","LoadAjax('table-masquerade','$page?table=yes');","AsFirewallManager"));
}
function dst_nets(){
    $ID=intval($_GET["dst-nets"]);
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $tpl=new template_admin();
    $ligne=$q->mysqli_fetch_array("SELECT exclude_dst FROM firehol_masquerade WHERE ID='$ID'");

    $array=unserialize(base64_decode($ligne["exclude_dst"]));
    if(!is_array($array)){$array=array();}

    $tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_textareacode("dst-net",null,@implode("\n",$array));

    echo $tpl->_ENGINE_parse_body($tpl->form_outside(null,$form,"{excludetransparentout_explain}","{apply}","LoadAjax('table-masquerade','$page?table=yes');","AsFirewallManager"));

}
function dst_nets_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $IP=new IP();
    $array=explode("\n",$_POST["dst-net"]);
    foreach ($array as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!$IP->isIPAddressOrRange($line)){
            echo "$line {false}<b>";
            continue;}
        $T[]=$line;

    }
    $include_src=base64_encode(serialize($T));
    $ID=intval($_POST["ID"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $q->QUERY_SQL("UPDATE firehol_masquerade SET exclude_dst='$include_src' WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error."\n";}
}
function src_nets_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $IP=new IP();
    $array=explode("\n",$_POST["src-net"]);
    $T=array();
    foreach ($array as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!$IP->isIPAddressOrRange($line)){
            echo "$line {false}<b>";
            continue;}
        $T[]=$line;

    }
    $include_src=base64_encode(serialize($T));
    $ID=intval($_POST["ID"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $q->QUERY_SQL("UPDATE firehol_masquerade SET include_src='$include_src' WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error."\n";}

}



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>Masquerade</h1>
	<p>{masquerade_whatis}</p>
	</div>
	
	</div>
		

		
	<div class='row'><div id='progress-masquerade-restart'></div>
	<div class='ibox-content'>

	<div id='table-masquerade'></div>

	</div>
	</div>
		
		
		
	<script>
	$.address.state('/');
	$.address.value('/masquerade');	
	LoadAjax('table-masquerade','$page?table=yes');
		
	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$eth_sql=null;
	$token=null;
	$class=null;
	$t=time();
	$nic=new networking();
	$nicZ=$nic->Local_interfaces();



    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-masquerade-restart",
        "");


	$html[]=$tpl->_ENGINE_parse_body("

			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_firewall_rules} </label>
			</div>");
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{interface}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{rules}</th>";

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";




    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $pnic_bridges=array();
    $results=$q->QUERY_SQL("SELECT nic_to FROM pnic_bridges WHERE masquerading=1 AND enabled=1");

    foreach ($results as $index=>$ligne){
        $pnic_bridges[$ligne["nic_to"]]=true;
    }






	$TRCLASS=null;

	foreach ($nicZ as $ifname){
	    $znic=new system_nic($ifname);
        $md=md5(serialize($znic).time().$ifname);
        $Norule     = false;
        $class      =null;
        $rules_text =null;
        if($znic->Bridged==1){continue;}
        if($znic->enabled==0){continue;}
        $ligne=$q->mysqli_fetch_array("SELECT * FROM firehol_masquerade WHERE nic='$ifname'");
        $ifname_id=intval($ligne["ID"]);
        $enabled=intval($ligne["enabled"]);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?ifname-enable=$ifname&id=$ifname_id')","AsFirewallManager");


       if($znic->firewall_masquerade==1){
           if($enabled==0) {
               $enable = "<i class='fas fa-check'></i>";
           }
           $Norule=true;

       }

       if(isset($pnic_bridges[$ifname])){
           $enable="<i class='fas fa-check'></i>";
       }

        if($znic->isFW==0){
            if($znic->firewall_masquerade==0) {
                $enable = $tpl->icon_nothing();
                $Norule = true;
            }
        }

        $rules_text=rule_text($ifname_id,$ifname);

		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"center\" width='1%' nowrap>$enable</td>";
		$html[]="<td class=\"left\" width='1%' nowrap><i class='fas fa-ethernet'></i>&nbsp;$ifname $znic->NICNAME</td>";
        $html[]="<td class=\"left\"><span id='rules-text-$ifname_id'>$rules_text</span></td>";

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
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}

function rule_text($ID,$interface=null){
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $tpl=new template_admin();



    $pnic_bridges=array();
    $results=$q->QUERY_SQL("SELECT nic_to FROM pnic_bridges WHERE masquerading=1 AND enabled=1");
    foreach ($results as $index=>$ligne){
        $pnic_bridges[$ligne["nic_to"]]=true;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT nic,enabled FROM firehol_masquerade WHERE ID='$ID'");
    $ifname=$ligne["nic"];

    if($ifname==null){$ifname=$interface;}
    $znic=new system_nic($ifname);
    $enabled=intval($ligne["enabled"]);

    if($znic->firewall_masquerade==1){
        $link="Loadjs('fw.settings.php?nic-form-settings-build=$ifname');";
        return $tpl->td_href("{every_outgoing_packets}, {defined_in_the_interface_option}",null,$link);
    }

    if(isset($pnic_bridges[$ifname])){
        $pnic_bridge_id=pnic_bridge_id($ifname);
        $link="Loadjs('fw.bridges.php?ruleid-js=$pnic_bridge_id');";
        return $tpl->td_href("{every_outgoing_packets}, {used_by_a_connector}",null,$link);
    }
    if($znic->isFW==0){
        if($znic->firewall_masquerade==0) {
            return $tpl->_ENGINE_parse_body("{firewall_is_disabled_nic} ($ifname)");
        }
    }

    if($enabled==0){
        return $tpl->_ENGINE_parse_body("{disabled}");
    }
    $masquerade_nb_rules=masquerade_nb_rules($ID);
    if($masquerade_nb_rules==0){
        return $tpl->_ENGINE_parse_body("{every_outgoing_packets}");
    }
    return masquerade_rules($ID);
}

function pnic_bridge_id($eth){
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM pnic_bridges WHERE nic_to='$eth' AND masquerading=1");
    return intval($ligne["ID"]);
}

function rule_save(){
	$tpl=new templates();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

	$ID=$_POST["ID"];
	unset($_POST["ID"]);

	reset($_POST);foreach ($_POST as $key=>$val){
		$EDIT[]="`$key`='$val'";
		$ADDFIELD[]="`$key`";
		$ADDVALS[]="'$val'";

	}

	if($ID==0){
		$zMD5=md5(serialize($_POST));
		$ADDFIELD[]="`zMD5`";
		$ADDVALS[]="'$zMD5'";
		$sql="INSERT INTO pnic_nat (".@implode(",", $ADDFIELD).") VALUES (".@implode(",", $ADDVALS).")";

	}else{
		$sql="UPDATE pnic_nat SET ".@implode(",", $EDIT)." WHERE ID=$ID";

	}

	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true,$sql);}
}
function masquerade_nb_rules($masqid){
    $eth="MASQ:$masqid";
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM 
                iptables_main WHERE eth='$eth' AND enabled=1");
    return intval($ligne["tcount"]);

}

function masquerade_rules($masqid){
    $eth="MASQ:$masqid";
    $f=array();
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results=$q->QUERY_SQL("SELECT rulename FROM 
                iptables_main WHERE eth='$eth' AND enabled=1");
    if(!$q->ok){echo $q->mysql_error;}
    foreach ($results as $index=>$ligne){
        $f[]="{rule}: <strong>".$ligne["rulename"]."</strong>";
    }

    return @implode("<br>",$f);
}