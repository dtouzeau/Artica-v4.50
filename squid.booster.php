<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	if(posix_getuid()==0){die("DIE " .__FILE__." Line: ".__LINE__);}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	
	if(isset($_GET["tab"])){tabs();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["SquidBoosterMem"])){Save();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["cache-mem"])){cache_mem();exit;}
	if(isset($_GET["cache-rock"])){cache_rock();exit;}
	
	js();
	
	
function js() {
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{squid_booster}");
	$page=CurrentPageName();
	echo "
	AnimateDiv('BodyContent');
	LoadAjax('BodyContent','$page?tabs=yes');";
	
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]="{squid_booster}";
	$array["status"]="{status}";
	
	$fontsize="22px";
	$t=time();
	foreach ($array as $num=>$ligne){
	
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	
	echo build_artica_tabs($html, "squid_booster_tab",1490)."
	<script>LeftDesign('speed-256.png');</script>";
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	$SquidBoosterMemK=$sock->GET_INFO("SquidBoosterMemK");
	$SquidBoosterOnly=$sock->GET_INFO("SquidBoosterOnly");
	$SquidBoosterEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBoosterEnable"));
	
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	if(!is_numeric($SquidBoosterMemK)){$SquidBoosterMemK=50;}
	if(!is_numeric($SquidBoosterOnly)){$SquidBoosterOnly=0;}
	$disabled=$tpl->javascript_parse_text("{disabled}");
	if($SquidBoosterMem==0){$SquidBoosterMemText="&nbsp;$disabled";}
	$warn_squid_restart=$tpl->javascript_parse_text("{warn_squid_restart}");

	
	
	$t=time();
	$maxMem=500;
	$CPUS=0;
	$currentMem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TOTAL_MEMORY_MB"));
	$soustraire=500;
	if($currentMem>3500){$soustraire=1000;}
	if($currentMem>4000){$soustraire=1200;}
	if($currentMem>6000){$soustraire=2000;}
	if($currentMem>7999){$soustraire=2500;}
	if($currentMem>9999){$soustraire=3000;}
	
	if($currentMem>0){
		$maxMem=$currentMem-$soustraire;
	}
	
	$users=new usersMenus();
	$CPUS=$users->CPU_NUMBER;
		
	
	
	$html=Paragraphe_switch_img("{activate_the_booster_cache}", 
			"{squid_booster_text}","SquidBoosterEnable",$SquidBoosterEnable,null,1400)."
	
	
	
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:22px' widht=1%>{memory}:</td>
		<td width=99%><strong style='font-size:35px' id='$t-value'>{$SquidBoosterMem}M/{$maxMem}M</strong><input type='hidden' id='$t-mem' value='$SquidBoosterMem'></td>
	</tr>
	<tr>
		<td colspan=2><div id='slider$t' style='height:70px'></div></td>
	</tr>
	</table>
	
	<div style='margin-top:20px;text-align:right'><HR>". button("{apply}","SaveBooster$t()",36)."</div>	
	
	
	
	<script>
		$(document).ready(function(){
			$('#slider$t').slider({ max: $maxMem,step:5,
			value:$SquidBoosterMem,
			 slide: function(e, ui) {
			 
			// $( '#control-direct' ).val( ui.values[ 0 ] + '%' );
			var slidercolor =ui.value+'%';

			$('.ui-slider-horizontal').css({background: '#16D33F'})
				.css({background: 'linear-gradient(to right,  #D32516 '+slidercolor+',#16D33F '+slidercolor+')'})
				.css({background: '-moz-linear-gradient(left,  #16D33F '+slidercolor+', #D32516 '+slidercolor+')'})
				.css({background: '-webkit-linear-gradient(left,  #16D33F '+slidercolor+',#D32516 '+slidercolor+')'});
			
			ChangeSlideField$t(ui.value)
        	},
        	change: function(e, ui) {
        		
          		ChangeSlideField$t(ui.value);
        	}
		});
		
	
		$('.ui-slider-horizontal').css({background: '#16D33F'});
		$('.ui-slider-handle').height(75);
		
		
		});
		
		function ChangeSlideField$t(val){
			var disabled='';
			if(val==0){disabled='&nbsp;$disabled';}
			document.getElementById('$t-value').innerHTML=val+'M/{$maxMem}M'+disabled;
			document.getElementById('$t-mem').value=val;
					
		}
		

	var x_SaveBooster$t=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
      	Loadjs('squid.caches.progress.php');
     	}	

	function SaveBooster$t(){
		if(confirm('$warn_squid_restart')){
			var XHR = new XHRConnection();
			
			XHR.appendData('SquidBoosterEnable',document.getElementById('SquidBoosterEnable').value);
			XHR.appendData('SquidBoosterMem',document.getElementById('$t-mem').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveBooster$t);		
		}
	
	}		
	ChangeSlideField$t($SquidBoosterMem);
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	if($_POST["SquidBoosterMem"]==0){$_POST["SquidBoosterEnable"]=0;}
	$sock->SET_INFO("SquidBoosterMem",$_POST["SquidBoosterMem"]);
	$sock->SET_INFO("SquidBoosterEnable",$_POST["SquidBoosterEnable"]);
	
}

function status(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td valign='top' width=50%>
		<center style='font-size:26px'>{squid_booster} {memory}</center>
		<div id='cache-mem' style='width:410px;height:410px'></div>
	</td>
	<td valign='top' width=50%>
		<center style='font-size:26px'>{squid_booster} {hard_disk}</center>
		<div id='cache-rock' style='width:410px;height:410px'></div>
	</td>
	</tr>
	</table>
	<div style='text-align:right;margin:10px;margin-top:20px;'><hr>".button("{refresh}", "Loadjs('squid.refresh-status.php')",18)."</div>
<script>
	function Fsix1(){
				AnimateDiv('cache-mem');
				Loadjs('$page?cache-mem&yes&container=cache-mem',true);
			}
			
	function Fsix2(){
				AnimateDiv('cache-rock');
				Loadjs('$page?cache-rock&yes&container=cache-rock',true);
			}			
			
			setTimeout(\"Fsix1()\",800);
			setTimeout(\"Fsix2()\",1600);	
</script>		
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function cache_mem(){	
	$sock=new sockets();
	$Main=unserialize(base64_decode($sock->getFrameWork("squid.php?cacheBoosterStatus=yes")));
	$TOT=$Main["TOT"];
	
	$PARTITION_TEXT=FormatBytes($TOT);
	$TOT=round($TOT/1024);
	$USED=$Main["USED"];
	$PARTITION_SIZE=FormatBytes($USED);
	$USED=round($USED/1024);
	
	
	$PieData["{total}"]=$TOT;
	$PieData["{used}"]=$USED;
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{squid_booster} {memory}";
	$highcharts->Title="$PARTITION_SIZE/$PARTITION_TEXT";
	$highcharts->LegendSuffix=" Bytes";
	echo $highcharts->BuildChart();
}

function cache_rock(){
	$sock=new sockets();
	$cachefile="/etc/artica-postfix/settings/Daemons/squid_get_cache_infos.db";
	$array=unserialize(@file_get_contents($cachefile));
	$Main=$array["/home/squid/cache_rock"];
	


	$TOT=$Main["MAX"];
	$PARTITION_TEXT=FormatBytes($TOT);
	$TOT=round($TOT/1024);
	
	$USED=$Main["CURRENT"];
	$PARTITION_SIZE=FormatBytes($USED);
	$USED=round($USED/1024);


	$PieData["{total}"]=$TOT;
	$PieData["{used}"]=$USED;

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{squid_booster} ROCK";
	$highcharts->Title="$PARTITION_SIZE/$PARTITION_TEXT";
	$highcharts->LegendSuffix=" MB";
	echo $highcharts->BuildChart();
}
?>