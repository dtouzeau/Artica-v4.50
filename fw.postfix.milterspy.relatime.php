<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(isset($_GET["search"])){search();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$RUN=FALSE;
	if($users->AsPostfixAdministrator OR $users->AsMessagingOrg){$RUN=true;}
	if(!$RUN){die("DIE: ".__FILE__." L.".__LINE__);}
	$t=time();
	if(!isset($_SESSION["MILTERSPY_SEARCH"])){$_SESSION["MILTERSPY_SEARCH"]="50 events";}
	if($_SESSION["MILTERSPY_SEARCH"]==null){$_SESSION["MILTERSPY_SEARCH"]="50 events";}
	$addPLUS=null;
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{forwarded_messages}</h1></div>
	</div>
	<div class=\"row\">
	<div class='ibox-content'>
	<div class=\"input-group\">
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["MILTERSPY_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	<span class=\"input-group-btn\">
	<button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
	</span>
	</div>
	</div>
	</div>
	<div class='row' id='spinner'>
	<div id='progress-firehol-restart'></div>
	<div  class='ibox-content'>
		<div id='table-loader'></div>
	</div>
	</div>
	</div>
	<script>
		$.address.state('/');
		$.address.value('/milterspy.log');

function Search$t(e){
	if(!checkEnter(e) ){return;}
	ss$t();
}

function ss$t(){
		var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
		LoadAjax('table-loader','$page?search='+ss+'$addPLUS');
}

		function Start$t(){
		var ss=document.getElementById('search-this-$t').value;
		ss$t();
}
		Start$t();
		</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

		echo $tpl->_ENGINE_parse_body($html);

}


function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$GLOBALS["TPLZ"]=$tpl;
	$max=0;$date=null;$c=0;
	if($_GET["search"]==null){$_GET["search"]="50 events";}
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$sock=new sockets();
	writelogs("mailspy.php?access-real=yes&rp=....",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork("mailspy.php?access-real=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"])."&SearchString={$_GET["SearchString"]}&FinderList={$_GET["FinderList"]}$addPLUS");
	$source_file="/usr/share/artica-postfix/ressources/logs/milterspy.log.tmp";
	
	
	$html[]="
	
	<table class=\"table table-hover\" id='milterspy-eye'>
	<thead>
	<tr>
	<th>{date}</th>
	<th>{from}</th>
	<th>{to}</th>
	<th>{size}</th>
	<th>{subject}</th>
	<th>{attachments}</th>
	</tr>
	</thead>
	<tbody>
	";
	
	if(!is_file($source_file)){
		echo $tpl->FATAL_ERROR_SHOW_128("$source_file  No such file");
		return;
	}
	
	$handle=fopen($source_file,'r');
	if(!$handle){
		echo $tpl->FATAL_ERROR_SHOW_128("$source_file  error");
		return false;
	}
	
		$c=0;
		while (!feof($handle)) {
			
			$value=trim(fgets($handle));
			$md5=md5($value);
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."] LINE $c <code>$value</code><br>\n";}
			if($value==null){continue;}
			$f=explode("\t",$value);
			$zdate=0;
			$from="&nbsp;";
			$to=null;
			$subject=null;
			$size=0;
			$attach=array();
			$attach_text=0;
			foreach ($f as $field){
				if(preg_match("#time=([0-9]+)#", $field,$re)){$zdate=$re[1];}
				if(preg_match("#from=<(.*?)(>|$)#", $field,$re)){$from=$re[1];}
				if(preg_match("#to=<(.*?)(>|$)#", $field,$re)){$to=$re[1];}
				if(preg_match("#subject=(.*)#",$field,$re)){$subject=$re[1];}
				if(preg_match("#size=([0-9]+)#",$field,$re)){$size=$re[1];}
				if(preg_match("#file=(.*)#", $field,$re)){$attach[]=imapUtf8($re[1]);}
			}
			
			$date=$tpl->time_to_date($zdate,true);
			if(strlen($from)>43){$from=$tpl->td_href("...".substr($from,strlen($from)-40,strlen($from)),$from);}
			if(strlen($to)>43){$to=$tpl->td_href("...".substr($to,strlen($to)-40,strlen($to)),$to);}
			$subject=imapUtf8($subject);
			$size=FormatBytes($size/1024);
			$html[]="<tr>";
			$html[]="<td width=1% nowrap>$date</td>";
			$html[]="<td width=1% nowrap>$from</td>";
			$html[]="<td width=1% nowrap>$to</td>";
			$html[]="<td width=1% nowrap>$size</td>";
			$html[]="<td>$subject</td>";
			if(count($attach)>0){$attach_text=$tpl->td_href(count($attach),@implode("<br>", $attach)."<!-- $md5 -->");}
			$html[]="<td>$attach_text</td>";
			$html[]="</tr>";
			$c++;
		}	
	
		fclose($handle);
		$html[]="<tfoot>";
		
		$html[]="<tr>";
		$html[]="<td colspan='9'>";
		$html[]="<ul class='pagination pull-right'></ul>";
		$html[]="</td>";
		$html[]="</tr>";
		$html[]="</tfoot>";
		$html[]="</tbody></table>";
		$html[]="<div style='font-size:10px'>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/milterspy.log.cmd")."</div>";
		$html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('#milterspy-eye').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
		
	
}

function mime_decode($s) {
	$elements = imap_mime_header_decode($s);
	for($i = 0;$i < count($elements);$i++) {
		$charset = $elements[$i]->charset;
		$text =$elements[$i]->text;
		
		if($GLOBALS["VERBOSE"]){echo "Charset: $charset<br>\n";}
		if($GLOBALS["VERBOSE"]){echo "Text: $text<br>\n";}
		
		if(!strcasecmp($charset, "utf-8") || !strcasecmp($charset, "utf-7")){
			if($GLOBALS["VERBOSE"]){echo "$text iconv UTF-8<br>\n";}
			$text = iconv($charset, "utf-8", $text);
		}
		$decoded = $decoded . $text;
	}
	return $decoded;
}



function flatMimeDecode($string) {
	
	
	
		if($GLOBALS["VERBOSE"]){echo "<!-- ------------------------------------- --><hr>\n<code>$string</code><br>\n";}
        $array = imap_mime_header_decode ($string);
        $str = "";
        foreach ($array as $key => $part) {
        	if($GLOBALS["VERBOSE"]){echo "charset: <strong>$part->charset</strong><br>\n";}
        	if($GLOBALS["VERBOSE"]){echo "Text: <strong><code>$part->text</code></strong><br>\n";}
        	if($part->charset=="default"){$str .= $part->text;continue;}
           	if(strtoupper($part->charset) == "UTF-8"){$str .= utf8_decode ($part->text);}
            if($part->charset == "iso-8859-1"){$str .= utf8_encode($part->text);continue;}
            if(strtoupper($part->charset) == 'WINDOWS-1256'){$str .= iconv("windows-1256", "UTF-8", $part->text);}
            
            
            
            $str .= $part->text;
        }
        return $str;
    }
    
function imapUtf8($str){

	$str=utf8_encode($str);
	if (preg_match("#\?utf-8\?#i", $str)) return  str_replace("_"," ",mb_decode_mimeheader($str));

	
    $convStr = '';
    $subLines = preg_split('/[\r\n]+/', $str);
    for ($i=0; $i < count($subLines); $i++) {
        $convLine = '';
        $linePartArr = imap_mime_header_decode($subLines[$i]);
        for ($j=0; $j < count($linePartArr); $j++) {
            if ($linePartArr[$j]->charset === 'default') {
                if ($linePartArr[$j]->text != " ") {
                    $convLine .= ($linePartArr[$j]->text);
                }
            } else {
                $convLine .= iconv($linePartArr[$j]->charset, 'UTF-8', $linePartArr[$j]->text);
            }
        }
        $convStr .= $convLine;
    }

    return $convStr;
}