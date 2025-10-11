<?php
include_once(dirname(__FILE__).'/functions.sql.inc');
include_once(dirname(__FILE__).'/class.files.inc');

        $sql="SELECT SUM(rowsnum) as tsum FROM indexcats";
        $ligne=mysql_fetch_array(QUERY_SQL($sql,null,0,"articafr9"));
        
        $websites=$ligne["tsum"];
        $websites=numberFormat($websites,0,""," ");
        if(preg_match("#^([0-9]+)\s+#", $websites,$re)){$websites=$re[1];}
        
        $ressources_artica="
        <table>
        <tr><td colspan=2 style='padding-bottom:15px'><strong style='font-size:18px'>Artica For Squid Appliance resources</strong></td></tr>
        <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://proxy-appliance.org' style='font-size:16px;font-weight:bold'>The dedicated Website proxy-appliance.org</a></td>
        </tr>
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://sourceforge.net/projects/artica-squid/files/' style='font-size:16px;font-weight:bold'>Download the ISO file 32/64 bits</a></td>
        </tr> 
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.artica.fr/support' style='font-size:16px;font-weight:bold'>Help desk & Support</a></td>
        </tr>               
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://forum.artica.fr/viewforum.php?f=38' style='font-size:16px;font-weight:bold'>Community Forum</a></td>
        </tr>  
        </table>        
        ";
        
$ufbContent="<hr>
<table style='width:100%;margin-top:20px'>
<tr>

	<td valign='top' width=1%><center><img src='/img/ufb-350.png'></center></td>
	<td valign='top'>

".RoundedLightGrey("<div style='font-size:18px;font-weight:bold;'>Url Filter Box features list:</div>")."
<p>
	<strong style='font-size:16px'>Internet Accelerator and URL/Content Filtering.</strong>
<br><span style='font-size:16px'>The URL Filter Box is the highly powerful solution available on the market, combining Cache Acceleration and URL Filtering Engine.<br>
Depending your style of Internet surfing, you will be able to <strong>reach 95% of Save Bandwidth</strong>.<br>
We have optimized our solution for Youtube, DailyMotion, Microsoft WindowsUpdate, Compressed Files, Images, Video files, etc... <br>
We also provide an unique solution to <strong>accelerate</strong>, at a second level, your Internet surf with our <strong>Turbo-Booster</strong> engine increasing your 
surf again and again.<br>
The URL Filter Box maximizes performance and flexibility of your Network Organization.<br>
Database ensures maximum, in-line performance and minimal latency.<br>
Flexible policies can be implemented to control employees and network activity
Comparing competitor solutions, we have pushed our URL Filter Box to the extreme limit giving you the possibility to use your hardware and your bandwidth how it should be.
<br> We have simplified to the maximum the Management Console to concentrate your time to the most important options, we have <strong>tuned by ourselves 99% of the configuration.</strong></p>
 <p>&nbsp;</p>
$ressourcesufb

</td>
</tr>
</table>";        
        
$ressources_artica=RoundedLightGrey($ressources_artica);

        $ressourcesufb="
        <table>
        <tr><td colspan=2 style='padding-bottom:15px'><strong style='font-size:18px'>Url Filter Box Appliance resources</strong></td></tr>
        <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.artica.fr/index.php/home/urlfilterbox' style='font-size:16px;font-weight:bold'>Technical informations</a></td>
        </tr>
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://sourceforge.net/projects/artica-squid/files/ISO/UrlFilterBox/' style='font-size:16px;font-weight:bold'>Download the ISO file 32/64 bits</a></td>
        </tr> 
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.artica.fr/support' style='font-size:16px;font-weight:bold'>Help desk & Support</a></td>
        </tr>               
         <tr>
        	<td width=1%><img src='/img/arrow-right-24.png'></td>
        	<td><a href='http://www.artica.fr/forum/viewforum.php?f=133' style='font-size:16px;font-weight:bold'>Community Forum</a></td>
        </tr>  
        </table>        
        ";
        
$ressourcesufb=RoundedLightGrey($ressourcesufb);



$html="
<p style='font-size:16px'>Artica Proxy appliance delivers a scalable proxy platform architecture 
to secure Web communications and accelerate delivery of business applications.
<p style='font-size:16px'>
Your Internet Bandwidth is not big enough ?<br>
You do not manage where are your users surfing ?<br>
You need to protect your network against Malwares, Phishing, Porn, Warez, etc... ?<br>  
Artica Proxy Appliance will provide you all these needs ! and more...<br>
Including $websites+ Millions Domains, compatibility with LDAP and Microsoft AD, Accelerate your surf, etc....
</p>
<p style='font-size:16px'>
<i>Artica appliance is based on Debian 6 with Squid cache 3.2x and UfdbgGuard.</i>
</p>


<table style='width:100%'>
<tr>

	<td valign='top' width=1%><center><img src='/img/squid-350.png'></center></td>
	<td valign='top'>

".RoundedLightGrey("<div style='font-size:18px;font-weight:bold;'>Artica For Squid Appliance features list:</div>")."
<p>&nbsp;</p>
  <table>
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>Authenticate</strong> your users with Microsoft <strong>Active Directory</strong> or <strong>LDAP</strong> database</td>
  	</tr> 
  	<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>Protect</strong> users and networks from web threats, phishing and other attacks</td>
  	</tr>
  		<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><strong>Accelerate</strong> application performances with files, email, web, ssl and rich media</td>
  	</tr> 
  		<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Defines rules in order to <strong>reduce bandwith</strong> when accessing specifics websites.</td>
  	</tr> 
  		<tr><td colspan=3>&nbsp;</td></tr>  
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Ban websites with a database of more  <a href=\"http://artica.fr/index.php/component/content/article/453-artica-for-proxy-appliance-available-categories\" style='font-weight:bold'>$websites+ million categorized websites</a> in <strong>150 categories</strong></td>
  	</tr>   
  		<tr><td colspan=3>&nbsp;</td></tr>  	 	 
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>Provide extended <strong>statistics</strong> in realtime, including users, websites and categories - by Day/Week/Month/Year</td>
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
