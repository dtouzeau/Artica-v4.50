<?php

FUNCTION NGINX_DESTINATION_EXPLAIN($cache_peer_id,$color=null){
	$q=new mysql_squid_builder();
	$tpl=new templates();

	if($color==null){$color="black";}
	$ligne=@mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$cache_peer_id'"));
	$servername=$ligne["servername"];
	$ipaddr=$ligne["ipaddr"];
	$port=$ligne["port"];
	$ssl=$ligne["ssl"];
	$cacheid=$ligne["cacheid"];
	$ssl_text=null;
	$forceddomain=$ligne["forceddomain"];
	$remote_path=$ligne["remote_path"];
	if($ssl==1){$ssl_text=" (SSL)";}
	$f[]="<a href=\"javascript:blur();\" 
	OnClick=\"Loadjs('nginx.peer.php?js=yes&ID={$cache_peer_id}');\" 
	style='font-size:26px;text-decoration:underline;color:$color'>$servername</span></a>";
	$f[]="<i style='font-size:18px;font-weight:normal;color:$color'>$ipaddr:$port$ssl_text</i>";
	if($forceddomain<>null){$f[]="<i style='font-size:18px;font-weight:normal;color:$color'>{virtualhost}:$forceddomain</i>";}
	if($remote_path<>null){$f[]="<i style='font-size:18px;font-weight:normal;color:$color'>{path}:$remote_path</i>";}
	if($cacheid>0){
		$ligne=@mysqli_fetch_array($q->QUERY_SQL("SELECT `keys_zone` FROM nginx_caches WHERE ID='$cacheid'"));
		$f[]="<i style='font-size:12px;font-weight:normal;color:$color'>{use_cache}:<a href=\"javascript:blur();\" 
		OnClick=\"Loadjs('nginx.caches.php?js-cache=yes&ID=$cacheid')\"
		style='font-size:12px;font-weight:normal;text-decoration:underline;color:$color'>{$ligne["keys_zone"]}</a></i>";
	}

	$text=@implode("<br>", $f);
	return $tpl->_ENGINE_parse_body($text);


}

