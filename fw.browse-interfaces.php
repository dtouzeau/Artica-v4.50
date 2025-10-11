<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["interfaces"])){content();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$label=$_GET["title"];
    $addon="";
    if(preg_match("#vs:(.+)#",$label,$re)){
        $label=$re[1];
        $addon="&vs=1";
    }
    $title=$tpl->javascript_parse_text("{interfaces}");
    if(strlen($label)>0) {
        $title = $tpl->javascript_parse_text("$label");
    }
	$tpl->js_dialog6($title, "$page?popup=yes&field-id={$_GET["field-id"]}$addon",500);
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();

    $addon="";
	if(isset($_GET["vs"])){
        $addon="&vs=1";
    }


	$html="<div id='choose-interfaces'></div>
	<script>
		var Currentinterfaces=encodeURIComponent(document.getElementById('{$_GET["field-id"]}').value);
		LoadAjax('choose-interfaces','$page?interfaces='+Currentinterfaces+'$addon&field-id={$_GET["field-id"]}');
	</script>
	";
	echo $html;
	
}


function content(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $OnLyVS=false;
	$currentInterfaces=explode(",",$_GET["interfaces"]);
    if(isset($_GET["vs"])){
        $OnLyVS=true;
    }
	
	foreach ($currentInterfaces as $eth){
		if(trim($eth)==null){continue;}
		$CURS[$eth]=$eth;
		
	}
	
	include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	$t=time();
    if(!$OnLyVS) {
        $NICS["lo"] = "Loopback (127.0.0.1)";
    }
	foreach ($nicZ as $yinter=>$line){
		$znic=new system_nic($yinter);
		if($znic->Bridged==1){continue;}
		if($znic->enabled==0){continue;}
        if(preg_match("/gre[0-9]+$/",$yinter)){continue;}
        if(preg_match("/gretap[0-9]+$/",$yinter)){continue;}
        if(preg_match("/erspan[0-9]+$/",$yinter)){continue;}
        if(preg_match("/wccp[0-9]+$/",$yinter)){continue;}
        if($OnLyVS){
            if(!preg_match("#^(.+?)h([0-9]+)$#",$yinter,$ri)) {
                continue;
            }
            $Int=intval($ri[2]);
            if($Int==0){
                continue;
            }
            if(preg_match("#^eth#",$ri[1])){
                continue;
            }
            $NICS[$yinter]="$znic->NICNAME ($yinter/$znic->IPADDR)";
            continue;
        }


		$NICS[$yinter]="$znic->NICNAME ($yinter/$znic->IPADDR)";
	}
	$html[]="<table>";
    foreach ($NICS as $yinter => $label){
		$checked=null;
		if(isset($CURS[$yinter])){$checked="checked";}
		$id="CHOOSE_INTERFACE_$yinter";
		$html[]="<tr>";
		$html[]="<td style='width:30%;text-align:right;vertical-align:middle;padding-bottom:10px;' nowrap><span class=labelform id=''>{$label}</span>:</td>";
		$html[]="<td style='width:70%;padding-bottom:10px;padding-left:19px'>
			<!-- name=$yinter -->
			<div class=\"switch\">
					<div class=\"onoffswitch\">
						<input type=\"checkbox\" $checked class=\"onoffswitch-checkbox\" id=\"$id\">
						<label class=\"onoffswitch-label\" for=\"$id\">
							<span class=\"onoffswitch-inner\"></span>
							<span class=\"onoffswitch-switch\"></span>
						</label>
					</div>
					
				</div>";
		$html[]="</td>";
		$html[]="</tr>";
	}
	$html[]="<tr>";
	$html[]="<td colspan=2 style='text-align: right'>".$tpl->button_autnonome("{apply}", "Choose$t()", "fa-edit")."</td>";
	$html[]="</tr>";
	
	$html[]="</table>";
	$html[]="<script>";
	$html[]="
	function Choose$t(){
		var final=[];
		var elements = $('.onoffswitch-checkbox');
	   	elements.each(function() { 
	   		var arr=[];
	   		xid=$(this).attr('id');
	   		// alert('".__LINE__."'+xid);
	   		arr = /CHOOSE_INTERFACE_(.+)/.exec(xid);
	   		// alert('".__LINE__."');
	   		if(arr){
	   			if(arr.length>0){
	   				// alert('".__LINE__."');
	   				if( document.getElementById(xid).checked ){
	   					// alert('".__LINE__."');
	   					final[final.length]=arr[1];
	   				}
	   			}
	   		}
	   	} );
	
	   if(final.length==0){
	   		document.getElementById('{$_GET["field-id"]}').value='';
	   		dialogInstance6.close();
	   		return;
	   }
	   document.getElementById('{$_GET["field-id"]}').value=final.join(',');
	   dialogInstance6.close();
	}
	";
	$html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."";
	$html[]="</script>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}
