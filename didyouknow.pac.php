<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["HIDE"])){HIDE();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$compile_squid_ask=$tpl->javascript_parse_text("{compile_squid_ask}");
	
	$title=$tpl->javascript_parse_text("{didyouknow}");
	
	echo "
	function Start$t(){	
		RTMMail('800','$page?popup=yes','$title');
	}
	Start$t();";
	
	
}
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$html="<div style='font-size:22px;margin-bottom:20px'>{didyouknow}</div>
	<p style='font-size:18px;margin-bottom:20px' class=text-info>{didyouknow_wpad}</p>
		
	<center style='margin:20px'>
		<a href=\"http://artica-proxy.com/the-artica-proxy-pacwpad-service/\" style='font-size:22px;text-decoration:underline;color:black' target=_new>{web_documentation}</a>
	</center>
	<div style='text-align:right;margin-top:20px'>
			". button("{hide_info_def}","Hide$t()",18)."</div>
				
</center>
<script>
	var xHide$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	RTMMailHide();
}

function Hide$t(){
var XHR = new XHRConnection();
XHR.appendData('HIDE', 1);
XHR.sendAndLoad('$page', 'POST',xHide$t);
}
</script>
";



echo $tpl->_ENGINE_parse_body($html);
}

function HIDE(){
	$sock=new sockets();
	$sock->SET_INFO("DidYouKnowWPAD", 1);
	
}