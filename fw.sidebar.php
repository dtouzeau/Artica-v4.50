<?php
if(isset($_GET["call"])){call();exit;}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
$sock=new sockets();
$tpl=new template_admin();
$users=new usersMenus();
$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
$SquidDisableHyperCacheDedup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableHyperCacheDedup"));
$HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
$SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));
$FRONTAIL_LINUX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FRONTAIL_LINUX_INSTALLED"));
$EnableFrontail=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFrontail"));
$PostfixEnable=intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("EnablePostfix"));
if($FRONTAIL_LINUX_INSTALLED==0){$EnableFrontail=0;}
$UFDB=false;

if($PostfixEnable==1){
    $users->APP_UFDBGUARD_INSTALLED=false;
    $users->SQUID_INSTALLED=false;
}

if($users->APP_UFDBGUARD_INSTALLED){
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if($EnableUfdbGuard==1){$UFDB=true;}
}

$f[]="<div class='sidebar-title' id='my-side-barr-title'><H2 style='margin-bottom:0px;margin-top:0px'>{actions}</H2></div>";
$f[]="<ul class='sidebar-list'>";

$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
$SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
if($SQUIDEnable==0){$users->SQUID_INSTALLED=false;}

// https://www.googleapis.com/youtube/v3/search?part=id%2Csnippet&channelId=UC2NZbd1F9HcnSHSc7F40W1g&q=Tutorials+about+managing&key=AIzaSyBs04_Fectn-vvp5dvF93m7A1wU1CgdZ_U

if($users->AsAnAdministratorGeneric){
    $jshelp="s_PopUpFull('https://wiki.articatech.com',1024,768,'Artica Wiki')";
	$f[]="<li>";
	$f[]="<div><h4 style='font-weight:bold;font-size:19px;margin-bottom:0px;margin-top:0px'>{help_support}</H4></div>";
	$f[]="</li>";
	$f[]=line_icon("Loadjs('fw.youtube.php');","fab fa-youtube","{videos_help}","btn-primary");
    $f[]=line_icon("$jshelp;","fas fa-file-pdf","{online_help}","btn-primary");
	$f[]=line_icon("LoadAjaxSilent('MainContent','fw.support.php');","fas fa-bug","{create_a_ticket}","btn-primary");
	if($users->AsSystemAdministrator){
        $f[]=line_icon("Loadjs('fw.support-tool.php');","fas fa-file-archive","{support_package}","btn-primary");
    }

    $OPENVPN_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENVPN_INSTALLED"));
    if($OPENVPN_INSTALLED==1){
        $f[]=line_icon("LoadAjaxSilent('MainContent','fw.vpn.client.php');","fa fa-compress","{vpn_client}","btn-primary");
    }


}
if(!$users->SQUID_INSTALLED){
	$Query=false;
	
	$EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
	$RemoteUfdbCat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteUfdbCat"));
    $Query=true;

	if($Query){
		$f[]=line_icon("Loadjs('fw.ufdb.categorize.php?js-simple=yes')",
				"fas fa-map-marker-question",
				"{test_categories}",
				"btn-primary"
		);
			
			
	}
	
}


if($users->SQUID_INSTALLED){
	if($users->AsProxyMonitor){
		$f[]="<li>";
		$f[]="<div><h4 style='font-weight:bold;font-size:19px;margin-bottom:0px;margin-top:0px'>{your_proxy}</H4></div>";
		$f[]="</li>";
		
		
		$f[]=line_icon("Loadjs('fw.proxy.actions.php')",
				"fa fa-star",
				"{services_operations}",
				"btn-primary"
		);
		

        $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
		$RemoteUfdbCat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteUfdbCat"));
		$Query=true;

		
		if($Query){
			$f[]=line_icon("Loadjs('fw.ufdb.categorize.php?js-simple=yes')",
					"fas fa-map-marker-question",
					"{test_categories}",
					"btn-primary"
			);
			
			
		}

		
		if($UFDB){
			
			$f[]="<li>";
			$f[]="<div><h4 style='font-weight:bold;font-size:16px;margin-bottom:0px;margin-top:0px'><i class='fas fa-filter'></i>&nbsp;&nbsp;{webfiltering}</H4></div>";

            $UfdbGuardDisabledTemp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardDisabledTemp"));
			if($UfdbGuardDisabledTemp==0){
				
				$f[]=line_icon("Loadjs('fw.ufdb.disable.temp.php')",
						ico_play,
						"{disable_webfiltering}",
						"btn-primary"
				);
	
				
			}else{
				
				$f[]=line_icon("Loadjs('fw.ufdb.disable.temp.php')",
						"fa fa-pause",
						"{enable_webfiltering}",
						"btn-danger"
				);
				
	
				
			}

            $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
            if($Go_Shield_Server_Enable==0) {
                $f[]=line_icon("Loadjs('fw.ufdb.verify.rules.php')",
                    "fas fa-check",
                    "{verify_rules} $Go_Shield_Server_Enable",
                    "btn-primary"
                );
            }else{
                $f[]=line_icon("Loadjs('fw.goshield.verify.rules.php')",
                    "fas fa-check",
                    "{verify_rules}",
                    "btn-primary"
                );
            }
			

		

		}
		
		
		
		$f[]="</li>";
			
	}
	
}

$POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
if($POSTFIX_INSTALLED==1){
	$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
	if($EnablePostfix==1){
		
		$f[]="<li>";
		$f[]="<div><h4 style='font-weight:bold;font-size:19px;margin-bottom:0px;margin-top:0px'>{messaging}</H4></div>";
		$f[]="</li>";
		if($users->AsPostfixAdministrator){
			if($FRONTAIL_LINUX_INSTALLED==1){if($EnableFrontail==1){$f[]=line_icon("s_PopUp('/maillog/',800,600,'Mail.log')",ico_eye,"mail.log","btn-primary");} }
		}
		
		if($users->AsAnAdministratorGeneric){
			$f[]=line_icon("Loadjs('fw.postfix.spamassassin.analyze.php');","fas fa-envelope-square","{message_analyze}","btn-primary");
			
		}
		
	}
	
	
}
		
		


if($users->AsAnAdministratorGeneric){


	$f[]="<li>";
	$f[]="<div><h4 style='font-weight:bold;font-size:19px;margin-bottom:0px;margin-top:0px'>{system}</H4></div>";
	
	if($users->AsAnAdministratorGeneric){
		
		
		$f[]=line_icon("Loadjs('fw.services.status.php')",
				"fas fa-cogs",
				"{services_status}",
				"btn-primary"
		);


        if(!$users->AsDockerWeb) {
            $f[] = line_icon("Loadjs('fw.openports.php?js=yes')",
                "fas fa-ethernet",
                "{open_ports}",
                "btn-primary"
            );
        }
        if($users->AsDebianSystem) {
           $f[] = line_icon("s_PopUp('/ssh/',800,600,'SSH')",
                        ico_terminal,
                        "{system_console}",
                        "btn-primary"
                    );
                }


		if($FRONTAIL_LINUX_INSTALLED==1){
			if($EnableFrontail==1){
				$f[]=line_icon("s_PopUp('/syslog/',800,600,'SYSLOG')",
						ico_eye,
						"Syslog",
						"btn-primary"
				);
				
			}
			
		}
		
		if($users->AsSystemAdministrator){
			if($sock->GET_INFO("EnableMemcached")==1){
				$f[]=line_icon("LoadAjaxSilent('MainContent','fw.system.memcached.php');",
						"fad fa-memory",
						"MemCache",
						"btn-primary"
				);
			}
		}

	
	}	
	
	if($users->AsSystemAdministrator){
		
		$f[]=line_icon("Loadjs('fw.system.rootpwd.php');",
				"fa-user-circle",
				"{root_password2}",
				"btn-primary"
		);
		
		$f[]=line_icon("LoadAjaxSilent('MainContent','fw.system.localadmins.php');",
				"fas fa-users",
				"{local_admins}",
				"btn-primary"
		);		
		
		
		$f[]=line_icon("Loadjs('fw.system.restart.php')",
				"fal fa-sync-alt",
				"{reboot_system}",
				"btn-warning"
		);
		
		$f[]=line_icon("Loadjs('fw.system.restart.php?reset=yes');",
				"far fa-redo-alt",
				"{reset_system}",
				"btn-danger"
		);
	}
	$f[]="</li>";	
	
	
	
	$f[]="<li>";
	$f[]="<div><h4 style='font-weight:bold;font-size:19px;margin-bottom:0px;margin-top:0px'>{tools}</H4></div>";
    if($users->AsProxyMonitor) {
        $f[] = line_icon("document.location.href='/siege'",
            "fas fa-tachometer",
            "{mysql_benchmark}",
            "btn-primary"
        );
    }

    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    if($EnableGeoipUpdate==1){
        $f[] = line_icon("Loadjs('fw.geoip.php?client-js=yes');",
            ico_earth,
            "{geoip}",
            "btn-primary"
        );

    }

    $f[] = line_icon("Loadjs('fw.smtpclient.php');",
                "fa-question",
                "{smtp_simulation}",
                "btn-primary"
    );


    if($users->ASDCHPAdmin) {
        $f[] = line_icon("Loadjs('fw.dhclient.php');",
            "fa-question",
            "{dhcp_simulation}",
            "btn-primary"
        );
    }

    if($users->AsDnsAdministrator) {
        $f[] = line_icon("Loadjs('fw.dig.php');",
            "fa-question",
            "{dns_simulation}",
            "btn-primary"
        );
    }

    if($users->AsProxyMonitor) {
        $f[] = line_icon("Loadjs('fw.curl.php');",
            "fa-question",
            "{request_simulation}",
            "btn-primary"
        );
    }



    if($users->AsDatabaseAdministrator) {
        $f[] = line_icon("Loadjs('fw.mysql.client.php');",
            "fal fa-table",
            "{mysql_client}",
            "btn-primary"
        );
    }



    if($users->AsDnsAdministrator) {
        $EnableDNSFilterd = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterd"));
        if ($EnableDNSFilterd == 1) {
            $f[] = line_icon("Loadjs('fw.dns.filterd.verify.rules.php')",
                "fas fa-check",
                "{verify_rules}",
                "btn-primary"
            );

        }
    }

    $f[]=line_icon("Loadjs('fw.crypt.client.php');",
        "fal fa-table",
        "{encrypt_tool}",
        "btn-primary"
    );
	
	
	$f[]="</li>";

	$f[]="<div style='text-align:right;margin-top: 10px;padding:5px;margin-right:15px'>
			<a class='btn btn-outline btn-default' href=\"javascript:blur();\" 
			OnClick=\"javascript:$('#right-sidebar').toggleClass('sidebar-open');$('#artica-right-sidebarr').empty();\">{close}</a></div>";
	$f[]="</ul>";

    $f[]="<script>";
    $f[]="function CheckSideBarr(){";
    /*
    $f[]="alert(className);";
    $f[]="\tif ( document.getElementById('my-side-barr-title') ){";
    $f[]="\t\t$('#right-sidebar').removeClass( 'sidebar-open' );";
    $f[]="\t\tdocument.getElementById('artica-right-sidebarr').innerHTML='';";
    $f[]="\t}";
    */

    $f[]="}";
    $f[]="CheckSideBarr();";
    $f[]="</script>";
	
}


echo $tpl->_ENGINE_parse_body(@implode("\n", $f));


function line_icon($js,$icon_class,$text,$button_class){
	$fa="fa";
	if($js<>null){
		$OnMouse[]="OnClick=\"javascript:$js;\"";
		$OnMouse[]="OnMouseOver=\";this.style.cursor='pointer';style.fontWeight='bold'\"";
		$OnMouse[]="OnMouseOut=\";this.style.cursor='default';style.fontWeight='normal'\"";
		$OnMouseLink=@implode(" ", $OnMouse);
	}
	if(preg_match("#(.+?)\s+(.+)#", $icon_class,$re)){$fa=$re[1];$icon_class=$re[2];}
	$f[]="<div class='setings-item'><i class='$fa $icon_class'></i>&nbsp;&nbsp;<span $OnMouseLink>$text</span>";
	$f[]="<div style='float:right;margin-top:-1px'><button class='btn $button_class btn-xs' type='button'";
	$f[]="OnClick=\"javascript:$js\">Go</button></div>";
	$f[]="</div>";
	return @implode("\n", $f);
}

function call(){
    $f[]="function SideBarOpen(){";
    $f[]="\tif ( document.getElementById('my-side-barr-title') ){";
    $f[]="\t\t$('#right-sidebar').removeClass( 'sidebar-open' );";
    $f[]="\t\tdocument.getElementById('artica-right-sidebarr').innerHTML='';";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tclassName = $('#right-sidebar').attr('class');";
    $f[]="\tif (!className){";
    $f[]="\t\t$('#right-sidebar').addClass('sidebar-open');";
    $f[]="\t\tLoadAjaxSilent('artica-right-sidebarr','fw.sidebar.php');";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tif ( className.length  == 0 ){";
    $f[]="\t\t$('#right-sidebar').addClass('sidebar-open');";
    $f[]="\t\tLoadAjaxSilent('artica-right-sidebarr','fw.sidebar.php');";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tLoadAjaxSilent('artica-right-sidebarr','fw.sidebar.php');";


    $f[]="}";
    $f[]="SideBarOpen();\n";
    echo @implode("\n",$f);
}


function ufdbgInConf(){return true;}