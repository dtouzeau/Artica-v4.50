<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_POST["none"])){$sock=new sockets();$sock->getFrameWork("artica.php?restart-webconsole-wait=yes");exit;}
if(isset($_GET["ask"])){ask();exit;}

page();


function ask(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$s=page();
	$tpl->js_confirm_execute("{reboot_webconsole}", "none", 1,"$s");
	
}

function page(){

	$tpl=new template_admin();
$please_wait=$tpl->_ENGINE_parse_body("{please_wait}");
$script="var uiBlocked = false;

window.setInterval(function() {
    $.ajax({
      cache: false,
      type: 'GET',
      url: '/404.php',
      timeout: 1000,
      success: function(data, textStatus, XMLHttpRequest) {
			if (uiBlocked == true) {
            	uiBlocked = false;
            	$.unblockUI();
            	
          	}
          	document.location.href='/index';
        }
      ,error: function(XMLHttpRequest, textStatus, errorThrown) {
			if (uiBlocked == false) {
				uiBlocked = true;
	            $.blockUI({
	              message: '$please_wait',
	              css: {
	                border: 'none',
	                padding: '115px',
	                backgroundColor: '#000',
	                '-webkit-border-radius': '10px',
	                '-moz-border-radius': '10px',
	                opacity: .5,
	                color: '#fff'
	              } });
		
			}
		}
    })

  }, 4000);
";
return $script;

}


