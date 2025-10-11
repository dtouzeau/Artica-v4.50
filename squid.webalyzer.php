<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}
	
	if($GLOBALS["VERBOSE"]){echo "CLASESS<br>\n";}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.rtmm.tools.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ccurl.inc');
	
	
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	if(isset($_GET["analyze"])){analyze();exit;}
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$html="<div class=explain>{webalyzer_howto}</div>
	<table class=form>
	<tbody>
	<tr>
		<td class=legend>{url}:</td>
		<td>". Field_text("webalizer-uri",null,"font-size:16px;width:100%")."</td>
		<td width=1%>". button("{analyze}", "WebalyzerPerform()")."</td>
	</tr>
	</table>
	<div id='webalyzer-results' style='width:100%;height:350px;overlow:auto'></div>
	
	<script>	
		function WebalyzerPerform(){
				var uri=escape(document.getElementById('webalizer-uri').value);
				LoadAjax('webalyzer-results','$page?analyze=yes&uri='+uri);
		}	
	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}



function analyze(){
	if($GLOBALS["VERBOSE"]){echo "analyze<br>\n";}
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$uri=$_GET["uri"];
	$curl=new ccurl($uri);
	if($GLOBALS["VERBOSE"]){echo "$uri<br>\n";}
	$filename=md5($uri);
	if(!$curl->GetFile("ressources/logs/web/$filename")){
		echo "<H2>".$curl->error."</H2>";
		return;
	}
	$ARRY=array();
	if($GLOBALS["VERBOSE"]){echo "Open ressources/logs/web/$filename<br>\n";}
	$datas=@file_get_contents("ressources/logs/web/$filename");
	$tb=explode("\n", $datas);
	@unlink("ressources/logs/web/$filename");
	
	if(preg_match("#google\..+?#", $uri)){
		if(preg_match_all('#<h3 class="r"><a href="(.+?)"#', $datas, $re)){
			
			while (list ($num, $uri) = each ($re[1]) ){
				if(preg_match("#^(?:[^/]+://)?([^/:]+)#",$uri,$ri)){
					$sitename=$ri[1];
					if(substr($sitename, 0,1)=="#"){continue;}
					if(preg_match("#^www\.(.+)#",$sitename,$ri)){$sitename=$ri[1];}
					if(preg_match("#\.php\?#", $sitename)){continue;}
					if(preg_match("#\.php$#", $sitename)){continue;}						
					$ARRY[$sitename]=$sitename;
				}
			}
			
		}
		
	}

	while (list ($num, $line) = each ($tb) ){
		if(preg_match("#<a\s+href=(.*)\.([a-z]+)#i",$line, $re)){
			$uri=trim($re[1].".".$re[2]);
			$uri=str_replace("\"", "", $uri);
			$uri=str_replace("'", "", $uri);
			if(strpos($uri, ">")>0){$uri=substr($uri, 0,strpos($uri, ">"));}
			if(preg_match("#^(?:[^/]+://)?([^/:]+)#",$uri,$re)){
				$sitename=$re[1];
				if(substr($sitename, 0,1)=="#"){continue;}
				if(preg_match("#^www\.(.+)#",$sitename,$ri)){$sitename=$ri[1];}
				if(preg_match("#\.php\?#", $sitename)){continue;}
				if(preg_match("#\.php\s+[a-z]#", $sitename)){continue;}
				if(preg_match("#\.php$#", $sitename)){continue;}
				if(strpos($sitename, ".")==0){continue;}
				if(strpos($sitename, "{")>0){continue;}
				if(strpos($sitename, "}")>0){continue;}
				if(strpos($sitename, "$")>0){continue;}								
				$ARRY[trim(strtolower($sitename))]=trim(strtolower($sitename));
			}	
		}	
	}
	
	$html="
	";
	$f=0;
	$s=0;
	$t=0;
	while (list ($num, $line) = each ($ARRY) ){
		if(strlen($num)<3){continue;}
		if($num=="javascript"){continue;}
		if(strpos($num, ".")==0){continue;}
		if(strpos($num, "{")>0){continue;}
		if(strpos($num, " ")>0){continue;}
		if(strpos($num, "}")>0){continue;}
		if(strpos($num, "$")>0){continue;}
		$tz=explode(".", $num);
		if($tz[count($tz)-1]=="php"){continue;}
		if($tz[count($tz)-1]=="html"){continue;}
		if($tz[count($tz)-1]=="htm"){continue;}
		if(preg_match("#\.php\?#", $num)){continue;}
		if(preg_match("#\.php$#", $num)){continue;}
		
		$t++;
		if($t>200){break;}
		$cats=$q->GET_CATEGORIES($num);
		$mustcat="&nbsp;";
		$color="black";
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($cats==null){
			$tt=explode(".",$num);
			$familysite=$tt[count($tt)-2].".".$tt[count($tt)-1];
			$q->QUERY_SQL("INSERT IGNORE INTO visited_sites (sitename,familysite) VALUES ('$num','$familysite')");
			$f++;
			$cats="&nbsp;";
			$mustcat=imgtootltip("add-database-32.png","{categorize}","Loadjs('squid.categorize.php?www=$num')");
			$color="#CC0A0A";
		
		}else{$s++;}
		$html=$html."
		<tr class=$classtr>
		<td style='font-size:14px;color:$color'>$num</td>
		<td style='font-size:14px'>$cats</td>
		<td width=1%>$mustcat</td>
		</tr>
		";
		
		
	}
	//javascript:Loadjs('squid.categorize.php?www=api161.thefilter.com&day=&week=');
	$purc=round(($s/$t)*100,2);
	
	$html="<center><table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
	<thead class='thead'>
	<tr>
	<th>$t {websites} $f {failed} $s {success} $purc%</th>
	<th>{category}</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody>$html</tbody></table><span id='webalyzer-lock'></span>";
	echo $tpl->_ENGINE_parse_body($html);
	
}
	
