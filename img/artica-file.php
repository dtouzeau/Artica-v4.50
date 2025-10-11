<?php
include_once(dirname(__FILE__).'/functions.sql.inc');
include_once(dirname(__FILE__).'/class.files.inc');

     
        
        $ressources_artica="
        <table>
        <tr><td colspan=2 style='padding-bottom:15px'><strong style='font-size:18px'>Artica For Samba Appliance resources</strong></td></tr>
        
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://sourceforge.net/projects/artica-samba/files/' style='font-size:16px;font-weight:bold'>Download the ISO file 32/64 bits</a></td>
        </tr> 
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.artica.fr/support' style='font-size:16px;font-weight:bold'>Help desk & Support</a></td>
        </tr>               
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.artica.fr/forum/viewforum.php?f=32' style='font-size:16px;font-weight:bold'>Community Forum</a></td>
        </tr>  
        </table>        
        ";
        
$ressources_artica=RoundedLightGrey($ressources_artica);



$html="
<p style='font-size:16px'>The Artica For Samba appliance delivers a scalable storage/NAS system.<br>
<i>This appliance is based on Debian 6 with Samba and associated Open source plugins in order to fight viruses and backup datas.</i>
</p>


<table style='width:100%'>
<tr>

	<td valign='top' width=1%><center><img src='/img/artica-nas-350.png'></center></td>
	<td valign='top'>

".RoundedLightGrey("<div style='font-size:18px;font-weight:bold;'>Artica For Samba Appliance features list:</div>")."
<p>&nbsp;</p>
  <table>
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Can act as a <strong>Primary Domain controler (PDC)</strong></td>
  	</tr>
 	 
  	<tr><td colspan=3>&nbsp;</td></tr>  
 	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Can act as a <strong>Secondary Domain controler (BDC)</strong> with Microsoft Active Directory</td>
  	</tr>
 	 
  	<tr><td colspan=3>&nbsp;</td></tr>   	
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>Easy-to-use</strong> to share folders trough network</td>
  	</tr>
  		<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Include <strong>OCS Inventory</strong> as an OpenSource computer inventory and package deployement system for Win32 and Unix</td>
  	</tr> 
  	 		
 </table>
 <p>&nbsp;</p>
$ressources_artica
</td>
</tr>
</table>


"; 
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
