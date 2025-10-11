<?php
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.accesslogs.inc");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");

$users=new usersMenus();
if(!$users->AsProxyMonitor){
	header("content-type: application/x-javascript");
	echo "alert('No privs!');";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["ping"])){ping();exit;}

js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{monitor}");
	$html="YahooWinBrowse('680','$page?popup=yes&t=$t','$title')";
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("squid.php?ps-aux-squid=yes")));
	$CPU_NUM=count($ARRAY);
	$t=time();
	$error=null;
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$error="<p class=text-error>{multiprocessor_performance_no_license}</p>";
		
	}
	
	for($i=0;$i<$CPU_NUM;$i++){
		$title="CPU #<strong>$i</strong>";
		$titleM="{memory}";
		if($i==0){$title="Master CPU";}
		if($i==0){$titleM="{memory}";}
		$CPU=$ARRAY[$i]["CPU"];
		$MEM=$ARRAY[$i]["MEM"];
		if(!is_numeric($CPU)){$CPU=0;}
		if(!is_numeric($MEM)){$MEM=0;}
		$HTML[]="
		<div style='font-size:18px;margin-top:20px;margin-bottom:5px'><img src='img/processor-24.png' style='float:left:margin:5px'>&nbsp;&nbsp;$title -&nbsp;<span id='TEXTC-$i-$t'>$CPU</span>%</div>	
		<div id='progressC-$i-$t' style='height:35px'></div>
		<div style='font-size:18px;margin-top:20px;margin-bottom:5px'>$titleM -&nbsp;<span id='TEXTM-$i-$t'>$MEM</span>%</div>
		<div id='progressM-$i-$t' style='height:35px'></div>
		</div>";
		$JS[]="$('#progressC-$i-$t').progressbar({ value: $CPU });";
		$JS[]="$('#progressM-$i-$t').progressbar({ value: $MEM });";
		
		
	}
	
	$html="$error<div style='width:93%;margin-left:5px;margin-right:5px' class=form>
	<input type='hidden' id='pointer-$t' value='11'>
	<div style='font-size:22px;margin-bottom:15px'>{multiprocessor_performance}<div>".CompileTr3($HTML,true)."
	<script>
	".@implode("\n", $JS).		
		
	"
	Loadjs('$page?ping=yes&t=$t');		
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ping(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("squid.php?ps-aux-squid=yes")));
	$CPU_NUM=count($ARRAY);
	for($i=0;$i<$CPU_NUM;$i++){
		$title="CPU% #$i";
		$titleM="{memory}";
		if($i==0){$title="Master CPU";}
		if($i==0){$titleM="{memory}";}
		$CPU=$ARRAY[$i]["CPU"];
		$MEM=$ARRAY[$i]["MEM"];
		
		if(!is_numeric($CPU)){continue;}
		
		$HTML[]="
		
		
		if(document.getElementById('progressC-$i-$t')){
			document.getElementById('TEXTC-$i-$t').innerHTML='$CPU';
			$('#progressC-$i-$t').progressbar({ value: $CPU });
		}
		if(document.getElementById('progressM-$i-$t')){
			document.getElementById('TEXTM-$i-$t').innerHTML='$MEM';
			$('#progressM-$i-$t').progressbar({ value: $MEM });
		}
		
				
		";
		
	
	
	}

	$tt=time();
	$html="function function$tt(){
		if(!document.getElementById('pointer-$t')){return;}
		if(!YahooWinBrowseOpen()){return;}	
		".@implode("\n", $HTML)."
	
		Loadjs('$page?ping=yes&t=$t');
	}
	
	
	setTimeout(\"function$tt()\",1000);";
	
	echo $html;
	
}


