<?php
include_once(dirname(__FILE__).'/functions.sql.inc');
include_once(dirname(__FILE__).'/class.files.inc');
$ptextsize="text-align: justify;font-size:13px;padding-top:5px;min-height:125px;";


  $RoutersNumber=RoutersNumber();	
  $RoutersNumber=numberFormat($RoutersNumber,0,""," ");
  $latestTarBall=GetFilesVersions();
  $GetNightVersions=GetNightVersions();  
  $countmembers=CountMembers();
  $countposts=CountPosts();
 $countmembers=numberFormat($countmembers,0,""," ");
$countposts=numberFormat($countposts,0,""," ");
        $sql="SELECT SUM(rowsnum) as tsum FROM indexcats";
        $ligne=mysql_fetch_array(QUERY_SQL($sql,null,0,"articafr9"));
        
        $websites=$ligne["tsum"];
        $websites=numberFormat($websites,0,""," ");
		 if(preg_match("#^([0-9]+)\s+#", $websites,$re)){$websites=$re[1];}

$MailApplianceText="A mail gateway or a mail box server in 5mn with <b>Postfix</b> and/or <b>Cyrus-IMAP</b> and/or <b>Zarafa</b> 
as an image file for installation on a physical machine or <b>virtual machine</b>.";
$ProxyApplianceText="Save <strong>bandwith</strong>, <strong>protect your users</strong> 
with more than $websites+ Millions categorized websites and advanced rules with <b>Squid Cache 3.2x</b>";

$StorageApplianceText="Need to implement a <strong>NAS system</strong> in your network ?<br>  
including the Windows-compatible network file sharing";

$SupportText="Get <strong>free help</strong> by asking the Artica <strong>Community</strong>.<br>
Or use our help desk system in order to create support tickets directly to the Artica R&D Team.";

$toolbarr="<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/firefox-logo-20.png'></td>
  		<td nowrap><a href=\"index.php?option=com_content&view=article&catid=null&id=452:artica-web-filter-toolbar-for-firefox\" target=_new>Web Filter toolbar 1.0</strong></a></td>
  	</tr>   ";

  $infos="
  <table>
 	 <tr>
  		<td colspan=3 style='border-bottom:1px solid #CCCCCC'><strong style='font-size:14px;'>Community</td>
  	</tr>
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td nowrap><a href=\"http://www.artica.fr/routers-maps.php\" target=_new><strong>$RoutersNumber</strong> servers in activity.</a></td>
  	</tr>
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td nowrap><a href=\"http://forum.artica.fr\" target=_new><strong>$countmembers</strong> active members in forum.</a></td>
  	</tr> 
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td nowrap><a href=\"http://forum.artica.fr\" target=_new><strong>$countposts</strong> posts in forum.</a></td>
  	</tr>  
 	<tr>
  		<td colspan=3 style='border-bottom:1px solid #CCCCCC;padding-top:10px'><strong style='font-size:14px;'>Artica versions</td>
  	</tr> 
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td nowrap><a href=\"http://www.artica.fr/index.php/what-to-download-/102-download-artica-tgz\" target=_new>Stable/release:&nbsp;<strong>$latestTarBall</strong></a></td>
  	</tr> 
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td nowrap><a href=\"http://www.artica.fr/index.php/nightly-builds\" target=_new>Nightly build:&nbsp;<strong>$GetNightVersions</strong></a></td>
  	</tr> 
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td nowrap>Categorized Websites:&nbsp;<strong>$websites+ Millions</strong></td>
  	</tr> 
 	   	 	  	 	 	 	
  </table>	
  
 
  
  ";
  //<a href=\"index.php?option=com_content&view=article&catid=null&id=453:artica-for-proxy-appliance-available-categories\" target=_new>
  $infos=RoundedLightGrey($infos);

$html="
<div>
	<div class=\"title-box\" style='margin-top:-30px'>
	<table style='width:100%'>
	<tr>
		
		<td valign='top' style='padding-right:15px'>
		<div style='float:left;margin-right:5px;margin-top:40px'><a href=\"http://www.youtube.com/articaproject\"><img src='http://www.artica.fr/images/youtube_channel.png'></a></div>
		<div style='margin-top:10px;font-size:12px;text-align:right;font-weight:bold;font-size:16px;'>How to build a Mail/Proxy/File server easily?</div>
		<p style=\"text-align: justify;font-size:13px\">
		<div style=font-size:16px;'><strong>Artica, a web based management console:</strong></div>
In several cases, Linux products are stable, powerful, have better performance, are free of charge... etc, etc...<br>
But installing, managing and maintaining these products requires some Linux knowledge...<br>
People who start Linux do not have necessarily time and knowledge to quickly provide a complete <strong>SMTP/IMAP/Proxy/File server</strong> for their company.<br>
Artica For Postfix is a <strong>software/distribution</strong> designed to reduce the cost in order to create and <strong>manage</strong> a full Linux server.<br>
The free existing Web consoles for Postfix in particular do not provide all the available features in a <strong>&laquo;sexy way&raquo;</strong>.</p>
<center style='margin-top:10px;font-size:18px;font-weight:bold'>&laquo;Contact us:  +33 9 77 06 43 54 (France)&raquo;</center>
	</td>
	<td valign='top' style='padding-left:5px'>$infos</td>
</tr>
</table>
	
	
	</div>
	<div class=\"content4Box\">
		<div class=\"box4top\">
			<h4>Mail appliances</h4>
			
			<img src=\"/img/software-install-164.png\">
		</div>
		<div class=\"box4bottom itservices\" style=\"margin-top:-15px\">
			<p style=\"$ptextsize\">$MailApplianceText</p>
			<table style='margin-left:-5px;margin-top:-5px'>
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td><a href='index.php?option=com_content&view=article&catid=null&id=450:mail-appliances-features-list' style='font-weight:bold'>Features list</a></td>
				</tr>			
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td><a href='http://sourceforge.net/projects/artica-postfix/files/Artica%20ISO/' style='font-weight:bold'>Artica Appliance</a></td>
				</tr>
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td nowrap><a href='http://sourceforge.net/projects/artica-zarafa/files/' style='font-weight:bold'>Zarafa Appliance</a></td>
				</tr>
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td><a href='http://mail-appliance.org/' style='font-weight:bold'>Dedicated Web site</a></td>
				</tr>					
			</table>
		</div>
	</div>
	
	
	
	<div class=\"content4Box\">
		<div class=\"box4top\">
			<h4>Proxy Appliance</h4>
			<img src=\"/img/software-install-164.png\">
		</div>
			<div class=\"box4bottom itservices\" style=\"margin-top:-15px\">
			<p style=\"$ptextsize\">$ProxyApplianceText</p>
			<table style='margin-left:-5px;margin-top:-40px'>
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td><a href='http://proxy-appliance.org/' style='font-weight:bold'>More informations</a></td>
				</tr>							
			</table>
		</div>
	</div>
	
	
	<div class=\"content4Box\">
		<div class=\"box4top\">
			<h4 style=\"margin-right:-10px\">Storage Appliance</h4>
			<img src=\"/img/software-install-164.png\">
		</div>
		
		<div class=\"box4bottom itservices\" style=\"margin-top:-15px\">
			<p style=\"$ptextsize\">$StorageApplianceText</p>

			<table style='margin-left:-5px;margin-top:-5px'>
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td><a href='index.php?option=com_content&view=article&catid=null&id=451:storage-appliance-features-list' style='font-weight:bold'>Features list</a></td>
				</tr>	
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td><a href='http://nas-appliance.org/' style='font-weight:bold'>Dedicated Web site</a></td>
				</tr>						
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td><a href='http://sourceforge.net/projects/artica-samba/files/' style='font-weight:bold'>Download ISO</a></td>
				</tr>
					
			</table>			
			

		</div>
	</div>
	
	
	
	<div class=\"content4Box\">
		<div class=\"box4top\">
			<h4>Support - Forum</h4>
			<img src=\"/img/technical-support-130.png\"></div>
		<div class=\"box4bottom itservices\" style=\"margin-top:-15px\">
			<p style=\"$ptextsize\">$SupportText</p>

			<table style='margin-left:-5px;margin-top:-5px'>
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td><a href='http://forum.artica.fr' style='font-weight:bold'>Artica Forum</a></td>
				</tr>			
				<tr>
					<td width=1%><img src='/img/arrow-right-16.png'></td>
					<td><a href='http://www.artica.fr/support/' style='font-weight:bold'>HelpDesk</a></td>
				</tr>				
			</table>			
			
			
		</div>
	</div>
	
		 
</div>";


echo $html;


function numberFormat  ($number  , $decimals = 2 , $dec_point = '.' , $sep = ',', $group=3   ){
    $num = sprintf("%0.{$decimals}f",$number);   
    $num = explode('.',$num);
    while (strlen($num[0]) % $group) $num[0]= ' '.$num[0];
    $num[0] = str_split($num[0],$group);
    $num[0] = join($sep[0],$num[0]);
    $num[0] = trim($num[0]);
    $num = join($dec_point[0],$num);
   
    return $num;
}

function RoutersNumber(){
	$now=date('Y-m-d');
	$sql="SELECT COUNT(uuid) as tcount FROM hosts";
	$ligne=mysql_fetch_array(QUERY_SQL($sql,null,0,"articafr"));
	$number=$ligne["tcount"];
	return $number;
}


function GetFilesVersions($returnarray=0){
          	 $f=new Fichiers();
          	 $h=$f->DirListTable('/home/www/445063d0c9bf877fb052052271f883f2/web/download',true);
          	if(is_array($h)){
          		while (list ($num, $val) = each ($h) ){
          		if (preg_match('#artica-([0-9\.]+)\.tgz#i',$val,$r)){
          			if(strpos($r[1],'.')>0){
          				$key=$r[1];
          				$key=str_replace('.','',$key);
          				$arr[$key]=$r[1];}
          		}
          		}}
          		ksort($arr);
          		while (list ($num, $val) = each ($arr) ){
          			$v[]=$val;
          		}
          		if($returnarray==1){return $v;}
          		return $v[count($v)-1];
          }	
          
function GetNightVersions($returnarray=0){
	$f=new Fichiers();
    $h=$f->DirListTable('/home/www/445063d0c9bf877fb052052271f883f2/web/nightbuilds',true);
          	if(is_array($h)){
          		while (list ($num, $val) = each ($h) ){
          		if (preg_match('#artica-([0-9\.]+)\.tgz#i',$val,$r)){
          			if(strpos($r[1],'.')>0){
          				$key=$r[1];
          				$key=str_replace('.','',$key);
          				$arr[$key]=$r[1];}
          		}
          		}}
          		ksort($arr);
          		while (list ($num, $val) = each ($arr) ){
          			$v[]=$val;
          		}
          		if($returnarray==1){return $v;}
          		return $v[count($v)-1];
}	          
function CountMembers(){
          	$sql="SELECT COUNT(user_id) as tcount FROM phpbb_users";
          	$ligne=mysql_fetch_array(QUERY_SQL($sql,null,0,"articafr1"));
			return $ligne["tcount"];
          	
          }

          
          function CountPosts(){
          	$sql="SELECT COUNT(post_id) as tcount FROM phpbb_posts";
            $ligne=mysql_fetch_array(QUERY_SQL($sql,null,0,"articafr1"));
			return $ligne["tcount"];	
          	
          }
          
          
function RoundedLightGrey($text){
	
	$id=md5($text);
	
	
	
	
return "
<div $ls>
  <b class=\"RLightGrey\" id='$id'>
  <b class=\"RLightGrey1\" id ='$id" . "_1'><b></b></b>
  <b class=\"RLightGrey2\" id ='$id" . "_2'><b></b></b>
  <b class=\"RLightGrey3\" id ='$id" . "_3'></b>
  <b class=\"RLightGrey4\" id ='$id" . "_4'></b>
  <b class=\"RLightGrey5\" id ='$id" . "_5'></b></b>

  <div class=\"RLightGreyfg\" style='padding:8px;'  id ='$id" . "_11'>
   			$left$text
  </div>

  <b class=\"RLightGrey\" id ='$id" . "_0'>
  <b class=\"RLightGrey5\" id ='$id" . "_6'></b>
  <b class=\"RLightGrey4\" id ='$id" . "_7'></b>
  <b class=\"RLightGrey3\" id ='$id" . "_8'></b>
  <b class=\"RLightGrey2\" id ='$id" . "_9'><b></b></b>
  <b class=\"RLightGrey1\" id ='$id" . "_10'><b></b></b></b>
</div>
";	
	
}          
    
?>