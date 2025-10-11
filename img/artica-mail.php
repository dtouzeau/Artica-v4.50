<?php
include_once(dirname(__FILE__).'/functions.sql.inc');
include_once(dirname(__FILE__).'/class.files.inc');

     
        
        $ressources_artica="
        <table>
        <tr><td colspan=2 style='padding-bottom:15px'><strong style='font-size:18px'>Artica For Postfix Appliance resources</strong></td></tr>
        <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://mail-appliance.org' style='font-size:16px;font-weight:bold'>The dedicated Website mail-appliance.org</a></td>
        </tr>
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://sourceforge.net/projects/artica-postfix/files/' style='font-size:16px;font-weight:bold'>Download the ISO file 32/64 bits</a></td>
        </tr> 
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.artica.fr/support' style='font-size:16px;font-weight:bold'>Help desk & Support</a></td>
        </tr>               
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.artica.fr/forum/viewforum.php?f=59' style='font-size:16px;font-weight:bold'>Community Forum</a></td>
        </tr>  
        </table>        
        ";
        
$ressources_artica=RoundedLightGrey($ressources_artica);

        $ressourceZarafa="
        <table>
        <tr><td colspan=2 style='padding-bottom:15px'><strong style='font-size:18px'>Artica For Zarafa Appliance resources</strong></td></tr>
        <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.zarafa.com' style='font-size:16px;font-weight:bold'>Zarafa official Website</a></td>
        </tr>
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://sourceforge.net/projects/artica-zarafa/files/' style='font-size:16px;font-weight:bold'>Download the ISO file 32/64 bits</a></td>
        </tr> 
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.artica.fr/support' style='font-size:16px;font-weight:bold'>Help desk & Support</a></td>
        </tr>               
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://forum.artica.fr/viewforum.php?f=108' style='font-size:16px;font-weight:bold'>Community Forum</a></td>
        </tr>  
        </table>        
        ";
        
$ressourceZarafa=RoundedLightGrey($ressourceZarafa);



$html="
<p style='font-size:16px'>The Artica Mail family of appliances delivers a scalable SMTP gateway/Mail server platform architecture 
to secure messaging communications.<br>
Artica is designed on <strong>2 products</strong>:
<strong>Artica For Postfix Appliance</strong> and <strong>Artica For Zarafa appliance</strong><br>
<i>Each appliance is based on Debian 6 with Postfix and associated Open source plugins in order to fight spam and viruses.</i>
</p>


<table style='width:100%'>
<tr>

	<td valign='top' width=1%><center><img src='/img/mail-350.png'></center></td>
	<td valign='top'>

".RoundedLightGrey("<div style='font-size:18px;font-weight:bold;'>Artica For Postfix Appliance features list:</div>")."
<p>&nbsp;</p>
  <table>
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>Anti-Spam/Antivirus</strong> with SpamAssassin, ClamAV, Amavis, Milter-Greylist...</td>
  	</tr> 
  	<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>Postfix Multiple-instances</strong> allows you to create multiple MTA on the same machine</td>
  	</tr>
  		<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Can <strong>Backup</strong> messages before delivery them to the MDA</td>
  	</tr> 
  		<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Can be an MDA with Cyrus-IMAP in order to provide mailbox POP3/IMAP system. </td>
  	</tr> 
  		<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Multiple WebMail with RoundCube email (if using mailbox) </td>
  	</tr>   	
  		<tr><td colspan=3>&nbsp;</td></tr>  	 	 
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Display rich <strong>statistics</strong> in realtime by users, SMTP servers, domains and all by day/week/month/year </td>
  	</tr>   		
 </table>
 <p>&nbsp;</p>
$ressources_artica
</td>
</tr>
</table>
<hr>
<table style='width:100%;margin-top:20px'>
<tr>

	<td valign='top' width=1%><center style='width:350px'><img src='/img/zarafa-box-256.png'></center></td>
	<td valign='top'>

".RoundedLightGrey("<div style='font-size:18px;font-weight:bold;'>Artica For Zarafa Appliance features list:</div>")."
<p>
	<strong style='font-size:16px'>Groupware/Mailbox system.</strong>
<br><span style='font-size:16px'>
Zarafa provides email storage on the server side and brings its own Ajax-based mail client called WebAccess.<br>
Artica For Zarafa provides easy-to-install system that allows you to create a full feature mail server in 5 minutes.<br>
It provides all features listed on the Artica For Postfix appliance plus following features:
</p>
 <table>
   	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>POP3/IMAP</strong> access</td>
  	</tr> 
  	<tr><td colspan=3>&nbsp;</td></tr> 
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>Addressbook, Calendar, Notes, Tasks</strong> and Personal Folders / Public Outlook Folders</strong></td>
  	</tr> 
  	
  	<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>Resources planning</strong></td>
  	</tr>
  	<tr><td colspan=3>&nbsp;</td></tr> 
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>Calendar system</strong> plus iCalendar and CalDAV</td>
  	</tr>
  	<tr><td colspan=3>&nbsp;</td></tr> 
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>PDA synchronization</strong> via Z-push and BlackBerry integration over BES</td>
  	</tr>
  </table>
 <p>&nbsp;</p>
$ressourceZarafa

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
