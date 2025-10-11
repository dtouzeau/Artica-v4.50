<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.fstab.inc");
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die("DIE " .__FILE__." Line: ".__LINE__);}
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["add-dev"])){addfstab();exit;}
	if(isset($_GET["del-dev"])){delfstab();exit;}
	if(isset($_GET["fslist"])){echo listfstab($_GET["fslist"]);exit;}
	if(isset($_GET["umount"])){fstab_umount();exit;}
	if(isset($_GET["mount"])){fstab_mount();exit;}
	
	
	js();
	
	
function js(){

	$dev=$_GET["dev"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{CONNECT_HD}','system.internal.disks.php');
	$suffix=str_replace('.','_',$page);
	$macro_build_bigpart_warning=$tpl->_ENGINE_parse_body('{macro_build_bigpart_warning}','system.internal.disks.php');
	$macro_build_bigpart_warning=str_replace("\n",'\\n',$macro_build_bigpart_warning);
	
	$html="
		function {$suffix}LoadPage(){
			YahooWin6(700,'$page?popup=yes&dev=$dev','$title');
		}
		
	var x_fstabAdd= function (obj) {
			var response=obj.responseText;
			if(response.length>0){alert(response);}
		    fslist();
		}			
		
		function fstabAdd(){
				var XHR = new XHRConnection();
				XHR.appendData('add-dev','$dev');
				XHR.appendData('mount_point',document.getElementById('mount_point').value);
				document.getElementById('fslist').innerHTML='<div style=\"width:100%\"><center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center></div>';
				XHR.sendAndLoad('$page', 'GET',x_fstabAdd);
				}
				
		function fslist(){
			LoadAjax('fslist','$page?fslist=$dev');
		}
		
		function fstabDelete(num){
				var XHR = new XHRConnection();
				XHR.appendData('del-dev','$dev');
				XHR.appendData('index',num);
				document.getElementById('fslist').innerHTML='<div style=\"width:100%\"><center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center></div>';
				XHR.sendAndLoad('$page', 'GET',x_fstabAdd);
		}
		
		function fstabumount(mountp){
			var XHR = new XHRConnection();
			XHR.appendData('umount',mountp);
			document.getElementById('fslist').innerHTML='<div style=\"width:100%\"><center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center></div>';
			XHR.sendAndLoad('$page', 'GET',x_fstabAdd);			
		}
		
		function fstabmount(mountp){
			var XHR = new XHRConnection();
			XHR.appendData('mount',mountp);
			document.getElementById('fslist').innerHTML='<div style=\"width:100%\"><center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center></div>';
			XHR.sendAndLoad('$page', 'GET',x_fstabAdd);				
		
		}
			
	{$suffix}LoadPage();";
	
	echo $html;
	
}


function popup(){
$dev=$_GET["dev"];	
$sock=new sockets();
$uuid=base64_decode($sock->getFrameWork("hd.php?uuid-from-dev=".urlencode($dev)));
$html="<div style='font-size:22px;margin-bottom:30px'>$dev ( $uuid )</div>
	<table style='width:100%'>
		<tr>
		<td valign='top' width=1%><center><img src='img/database-connect-128.png'></center></td>
		<td valign='top' width=99%>
			<div id='fstab'><div class=explain style='font-size:16px'>{CONNECT_HD_TEXT}</div>
				<div style='width:98%' class=form>
				<table>
				<tr>
					<td valign='middle' class=legend nowrap style='font-size:16px'>{mount_point}:</td>
					<td nowrap>". Field_text('mount_point',null,'width:220px;font-size:16px')."". button_browse('mount_point')."</td>
					<td>". button("{add}","fstabAdd()",18)."</td>
				</tr>
				</table>
				</div>
				<div id='fslist' style='width:99%;height:220px;overflow:auto'>". listfstab($dev)."</div>
			</div>
		</td>
		</tr>
	</table>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'system.internal.disks.php');	
}

function addfstab(){
	$dev=$_GET["add-dev"];
	$mount_point=$_GET["mount_point"];
	if(trim($mount_point)==null){return ;}
	if(trim($dev)==null){return ;}
	$sock=new sockets();
	$dev_encode=urlencode($dev);
	$mount_point_encode=urlencode($mount_point);
	writelogs("Add in fstab $dev -> $mount_point",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork("hd.php?fstab-add=yes&dev=$dev_encode&mount=$mount_point_encode");
	
}

function listfstab($dev){
	$fstab=new fstab();
	$sock=new sockets();
	
	
	
	$uuid=base64_decode($sock->getFrameWork("hd.php?uuid-from-dev=".urlencode($dev)));
	if($uuid<>null){
		$uuidKEY="UUID=$uuid";
	}
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>";
	if(is_array($fstab->fstab_array[$dev])){
			while (list ($num, $array) = each ($fstab->fstab_array[$dev])){
				if(!is_array($array)){continue;}
				
				$mount_point=$array["mount"];
				if(trim($sock->getFrameWork("cmd.php?disk-ismounted=yes&dev=$mount_point"))=="TRUE"){
					$mounted=imgtootltip('status_ok.png','{umount}',"fstabumount('$mount_point')");
				}else{
					$mounted=imgtootltip('status_critical.png','{mount}',"fstabmount('$mount_point')");	
				}
				
				
				$html=$html."<tr ". CellRollOver().">
				<td><code style='font-size:16px'>$mount_point</td>
				<td width=1%><code style='font-size:16px'>$mounted</code></td>
				<td width=1%>". imgtootltip('delete-32.png','{delete}',"fstabDelete('$num')")."</td>
				</tr>
				";
			}
	}
	
	
	
	if(is_array($fstab->fstab_array[$uuidKEY])){
		while (list ($num, $array) = each ($fstab->fstab_array[$uuidKEY])){
			if(!is_array($array)){continue;}
	
			$mount_point=$array["mount"];
			if(trim($sock->getFrameWork("cmd.php?disk-ismounted=yes&dev=$mount_point"))=="TRUE"){
				$mounted=imgtootltip('check-32.png','{umount}',"fstabumount('$mount_point')");
			}else{
				$mounted=imgtootltip('check-32-grey.png','{mount}',"fstabmount('$mount_point')");
			}
	
	
			$html=$html."<tr ". CellRollOver().">
			<td><code style='font-size:16px'>$mount_point</td>
			<td width=10%><code style='font-size:16px'>$mounted</code></td>
			<td width=10%>". imgtootltip('delete-32.png','{delete}',"fstabDelete('$num')")."</td>
			</tr>
				";
			}
		}	
	
	$sock=new sockets();
	$mapper=$sock->getFrameWork("cmd.php?lvs-mapper=$dev");
if(is_array($fstab->fstab_array[$mapper])){
			while (list ($num, $array) = each ($fstab->fstab_array[$mapper])){
				if(!is_array($array)){continue;}
				$mount_point=$array["mount"];
				
				$mount_point=$array["mount"];
				if(trim($sock->getFrameWork("cmd.php?disk-ismounted=yes&dev=$mount_point"))=="TRUE"){
					$mounted=imgtootltip('check-32.png','{umount}',"fstabumount('$mount_point')");
				}else{
					$mounted=imgtootltip('check-32-grey.png','{mount}',"fstabmount('$mount_point')");	
				}
				
				$html=$html."<tr ". CellRollOver().">
				<td width=1%><img src='img/fw_bold.gif'></td>
				<td><code style='font-size:13px'>$mount_point</td>
				<td width=10%>$mounted</td>
				<td width=10%>". imgtootltip('delete-32.png','{delete}',"fstabDelete('$num')")."</td>
				</tr>
				";
			}
	}	
	
	$html=$html . "</table></div>";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html,'system.internal.disks.php');	
	
	
}

function fstab_umount(){
	$mount=$_GET["umount"];
	$sock=new sockets();
	echo $sock->getFrameWork("fstab-cmd.php?fstabumount=$mount");	
}

function delfstab(){
	$fstab=new fstab();
	$dev=$_GET["del-dev"];
	$index=$_GET["index"];
	$fstab=new fstab();
	unset($fstab->fstab_array[$dev][$index]);
	$fstab->save();
}

function  fstab_mount(){
	$mount=$_GET["mount"];
	$sock=new sockets();
	echo $sock->getFrameWork("fstab-cmd.php?fstabmount=$mount");	
}

?>