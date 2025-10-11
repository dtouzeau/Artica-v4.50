<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.categories.inc');

xstart();

function xstart(){
    $tpl=new template_admin();
	$q=new postgres_sql();
	if(!$q->TABLE_EXISTS("personal_categories")){$s=new categories();}
	$sql="SELECT category_id,categoryname FROM personal_categories order by category_id";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		squid_admin_mysql(0, "MySQL Error", $q->mysql_error,__FILE__,__LINE__);
		echo "!!!!!!!!!!!!!! $q->mysql_error !!!!!!!!!!!!!!\n";
		return;
	}
	
	while ($ligne = pg_fetch_assoc($results)) {
		$category_id=$ligne["category_id"];
		$categoryname=$ligne["categoryname"];
		$CATZ[]=$tpl->utf8_encode($category_id."|".$categoryname);
		$MCATZ[$category_id]=$tpl->utf8_encode($categoryname);
	}
	
	@mkdir("/home/ufdb-templates",0755,true);
	@mkdir("/etc/squid3",0755,true);
	@file_put_contents("/etc/squid3/categories.db",serialize($MCATZ));
	@file_put_contents("/home/ufdb-templates/CATEGORIES_NAMES",@implode("\n", $CATZ));
	if(is_file("/etc/init.d/squid-logger")){@touch("/etc/squid3/reload-category.action");}
    cluster_table();

}