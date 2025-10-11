<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["sslcrtd_program_in_memory"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}


page();


function page(){
	$page=CurrentPageName();
    $html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding id='ssl-title'>Cloud&nbsp;Categories &raquo;&nbsp;{status}</h1><p id='par-ssl'>{UseCloudArticaCategories_text}</div>
	
</div>
			
                            
			
<div class='row'><div id='progress-ssl-restart'></div>
			<div class='ibox-content'>
       	
			 	<div id='table-artica-cloud'></div>
                                    
			</div>
</div>
					
			
			
<script>
	$.address.state('/');
	$.address.value('/artica-cloud');
	LoadAjax('table-artica-cloud','$page?tabs=yes');
</script>";
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
   	$array["{status}"]="$page?table=yes";
	echo $tpl->tabs_default($array);

}

function table(){
	$addon=null;
	$page=CurrentPageName();
	$tpl=new template_admin();

	$mem=new lib_memcached();
	$q=new mysql_catz();
	$categories=$q->ufdbcat_dns("mastercard.com");
	$categories_text=$q->CategoryIntToStr($categories);

    $UFDBCAT_DNS_COUNT=intval($mem->getKey("UFDBCAT_DNS_COUNT"));
    $UFDBCAT_DNS_COUNT=$tpl->FormatNumber($UFDBCAT_DNS_COUNT);

	$html[]="<table style='width:100%'><tr>";

	
	$html[]="<td style='vertical-align:top;width:33%;padding:10px'>";
	$html[]=$tpl->widget_h("green","fa-heart","$UFDBCAT_DNS_COUNT","{requests}");
	$html[]="</td>";
	
	$html[]="<td style='vertical-align:top;width:33%;padding:10px'>";
	if($categories==0){
        $html[]=$tpl->widget_h("red","fa-cloud","{error}",$mem->getKey("UFDBCAT_DNS_ERROR"));
    }else{
        $html[]=$tpl->widget_h("green","fa-cloud","{$categories_text}","* * mastercard.com * *");
    }

	$html[]="</td>";
    if($categories>0){
	$html[]="<td style='vertical-align:top;width:33%;padding:10px'>";
	    $q->ufdbcat_dns_infos();
	    $upd=distanceOfTimeInWords($q->CategoryTime,time());
	    $Items=$tpl->FormatNumber($q->CategoryNumbers);
	    if($Items==0){$Items="-";}
        $html[]=$tpl->widget_h("green","fa fa-clock",$Items,"{updated} $upd");
	}

	
	$html[]="</td>";
	$html[]="</tr></table>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
}

