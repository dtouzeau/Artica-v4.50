<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=='--build'){build_phpmyadmin();exit;}

build_phpmyadmin();


function build_phpmyadmin(){
	if(!is_dir("/usr/share/phpmyadmin/themes")){
		echo "[INFO] phpmyadmin not detected\n";
	}
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$blowfish_secret=md5($hostname);
	echo "[INFO] Starting building phpmyadmin\n";

$f[]="<?php";
$f[]="/* vim: set expandtab sw=4 ts=4 sts=4: */";
$f[]="/**";
$f[]=" * phpMyAdmin sample configuration, you can use it as base for";
$f[]=" * manual configuration. For easier setup you can use setup/";
$f[]=" *";
$f[]=" * All directives are explained in documentation in the doc/ folder";
$f[]=" * or at <http://docs.phpmyadmin.net/>.";
$f[]=" *";
$f[]=" * @package PhpMyAdmin";
$f[]=" */";
$f[]="";
$f[]="/*";
$f[]=" * This is needed for cookie based authentication to encrypt password in";
$f[]=" * cookie";
$f[]=" */";
$f[]="\$cfg['blowfish_secret'] = '$blowfish_secret'; /* YOU MUST FILL IN THIS FOR COOKIE AUTH! */";
$f[]="";
$f[]="/*";
$f[]=" * Servers configuration";
$f[]=" */";
$f[]="\$i = 0;";
$f[]="";
$f[]="/*";
$f[]=" * First server";
$f[]=" */";

$f[]="/* Authentication type */";

$f[]="/* Server parameters */";
$f[]="\$i++;";
$f[]="\$cfg['Servers'][\$i]['auth_type'] = 'cookie';";
$f[]="\$cfg['Servers'][\$i]['verbose'] = 'Local MySQL';";
$f[]="\$cfg['Servers'][\$i]['socket'] = '/var/run/mysqld/mysqld.sock';";
$f[]="\$cfg['Servers'][\$i]['host'] = 'localhost';";
$f[]="\$cfg['Servers'][\$i]['connect_type'] = 'socket';";
$f[]="\$cfg['Servers'][\$i]['compress'] = false;";
$f[]="\$cfg['Servers'][\$i]['AllowNoPassword'] = true;";
$f[]="\$cfg['Servers'][\$i]['nopassword'] = true;";
$f[]="\$cfg['Servers'][\$i]['AllowRoot'] = true;";
$f[]="\$cfg['Servers'][\$i]['AllowNoPasswordRoot'] = true;";



if($unix->is_socket("/var/run/mysqld/squid-db.sock")){
	$f[]="\$i++;";
	$f[]="\$cfg['Servers'][\$i]['auth_type'] = 'cookie';";
	$f[]="\$cfg['Servers'][\$i]['verbose'] = 'Proxy MySQL Statistics';";
	$f[]="\$cfg['Servers'][\$i]['socket'] = '/var/run/mysqld/squid-db.sock';";
	$f[]="\$cfg['Servers'][\$i]['host'] = 'localhost';";
	$f[]="\$cfg['Servers'][\$i]['connect_type'] = 'socket';";
	$f[]="\$cfg['Servers'][\$i]['compress'] = false;";
	$f[]="\$cfg['Servers'][\$i]['AllowNoPassword'] = true;";	
	$f[]="\$cfg['Servers'][\$i]['nopassword'] = true;";
	$f[]="\$cfg['Servers'][\$i]['AllowRoot'] = true;";
	$f[]="\$cfg['Servers'][\$i]['AllowNoPasswordRoot'] = true;";
	
}

$f[]="";
$f[]="/*";
$f[]=" * phpMyAdmin configuration storage settings.";
$f[]=" */";
$f[]="";
$f[]="\$i++;";
$f[]="/* User used to manipulate with storage */";
$f[]="// \$cfg['Servers'][\$i]['controlhost'] = '';";
$f[]="// \$cfg['Servers'][\$i]['controlport'] = '';";
$f[]="// \$cfg['Servers'][\$i]['controluser'] = 'pma';";
$f[]="// \$cfg['Servers'][\$i]['controlpass'] = 'pmapass';";
$f[]="";
$f[]="/* Storage database and tables */";
$f[]="\$cfg['Servers'][\$i]['pmadb'] = 'phpmyadmin';";
$f[]="\$cfg['Servers'][\$i]['bookmarktable'] = 'pma__bookmark';";
$f[]="\$cfg['Servers'][\$i]['relation'] = 'pma__relation';";
$f[]="\$cfg['Servers'][\$i]['table_info'] = 'pma__table_info';";
$f[]="\$cfg['Servers'][\$i]['table_coords'] = 'pma__table_coords';";
$f[]="\$cfg['Servers'][\$i]['pdf_pages'] = 'pma__pdf_pages';";
$f[]="\$cfg['Servers'][\$i]['column_info'] = 'pma__column_info';";
$f[]="\$cfg['Servers'][\$i]['history'] = 'pma__history';";
$f[]="\$cfg['Servers'][\$i]['table_uiprefs'] = 'pma__table_uiprefs';";
$f[]="\$cfg['Servers'][\$i]['tracking'] = 'pma__tracking';";
$f[]="\$cfg['Servers'][\$i]['designer_coords'] = 'pma__designer_coords';";
$f[]="\$cfg['Servers'][\$i]['userconfig'] = 'pma__userconfig';";
$f[]="\$cfg['Servers'][\$i]['recent'] = 'pma__recent';";
$f[]="\$cfg['Servers'][\$i]['favorite'] = 'pma__favorite';";
$f[]="\$cfg['Servers'][\$i]['users'] = 'pma__users';";
$f[]="\$cfg['Servers'][\$i]['usergroups'] = 'pma__usergroups';";
$f[]="\$cfg['Servers'][\$i]['navigationhiding'] = 'pma__navigationhiding';";
$f[]="\$cfg['Servers'][\$i]['savedsearches'] = 'pma__savedsearches';";
$f[]="/* Contrib / Swekey authentication */";
$f[]="// \$cfg['Servers'][\$i]['auth_swekey_config'] = '/etc/swekey-pma.conf';";
$f[]="";
$f[]="/*";
$f[]=" * End of servers configuration";
$f[]=" */";
$f[]="";
$f[]="/*";
$f[]=" * Directories for saving/loading files from server";
$f[]=" */";
$f[]="\$cfg['UploadDir'] = '';";
$f[]="\$cfg['SaveDir'] = '';";
$f[]="";
$f[]="/**";
$f[]=" * Whether to display icons or text or both icons and text in table row";
$f[]=" * action segment. Value can be either of 'icons', 'text' or 'both'.";
$f[]=" */";
$f[]="//\$cfg['RowActionType'] = 'both';";
$f[]="";
$f[]="/**";
$f[]=" * Defines whether a user should be displayed a \"show all (records)\"";
$f[]=" * button in browse mode or not.";
$f[]=" * default = false";
$f[]=" */";
$f[]="//\$cfg['ShowAll'] = true;";
$f[]="";
$f[]="/**";
$f[]=" * Number of rows displayed when browsing a result set. If the result";
$f[]=" * set contains more rows, \"Previous\" and \"Next\".";
$f[]=" * default = 30";
$f[]=" */";
$f[]="//\$cfg['MaxRows'] = 50;";
$f[]="";
$f[]="/**";
$f[]=" * disallow editing of binary fields";
$f[]=" * valid values are:";
$f[]=" *   false    allow editing";
$f[]=" *   'blob'   allow editing except for BLOB fields";
$f[]=" *   'noblob' disallow editing except for BLOB fields";
$f[]=" *   'all'    disallow editing";
$f[]=" * default = blob";
$f[]=" */";
$f[]="//\$cfg['ProtectBinary'] = 'false';";
$f[]="";
$f[]="/**";
$f[]=" * Default language to use, if not browser-defined or user-defined";
$f[]=" * (you find all languages in the locale folder)";
$f[]=" * uncomment the desired line:";
$f[]=" * default = 'en'";
$f[]=" */";
$f[]="//\$cfg['DefaultLang'] = 'en';";
$f[]="//\$cfg['DefaultLang'] = 'de';";
$f[]="//\$cfg['DefaultLang'] = 'fr';";
$f[]="";
$f[]="/**";
$f[]=" * default display direction (horizontal|vertical|horizontalflipped)";
$f[]=" */";
$f[]="//\$cfg['DefaultDisplay'] = 'vertical';";
$f[]="";
$f[]="";
$f[]="/**";
$f[]=" * How many columns should be used for table display of a database?";
$f[]=" * (a value larger than 1 results in some information being hidden)";
$f[]=" * default = 1";
$f[]=" */";
$f[]="//\$cfg['PropertiesNumColumns'] = 2;";
$f[]="";
$f[]="/**";
$f[]=" * Set to true if you want DB-based query history.If false, this utilizes";
$f[]=" * JS-routines to display query history (lost by window close)";
$f[]=" *";
$f[]=" * This requires configuration storage enabled, see above.";
$f[]=" * default = false";
$f[]=" */";
$f[]="//\$cfg['QueryHistoryDB'] = true;";
$f[]="";
$f[]="/**";
$f[]=" * When using DB-based query history, how many entries should be kept?";
$f[]=" *";
$f[]=" * default = 25";
$f[]=" */";
$f[]="//\$cfg['QueryHistoryMax'] = 100;";
$f[]="";
$f[]="/**";
$f[]=" * Should error reporting be enabled for JavaScript errors";
$f[]=" *";
$f[]=" * default = 'ask'";
$f[]=" */";
$f[]="//\$cfg['SendErrorReports'] = 'ask';";
$f[]="";
$f[]="/*";
$f[]=" * You can find more configuration options in the documentation";
$f[]=" * in the doc/ folder or at <http://docs.phpmyadmin.net/>.";
$f[]=" */";
$f[]="?>";

echo "slapd: [INFO] phpmyadmin success\n";
@file_put_contents("/usr/share/phpmyadmin/config.inc.php",@implode("\n",$f));	
@chmod("/usr/share/phpmyadmin/config.inc.php",0705);

$q=new mysql();
if(!$q->DATABASE_EXISTS("phpmyadmin")){
	$q->CREATE_DATABASE("phpmyadmin");
}

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__bookmark` (
`id` int(11) NOT NULL auto_increment,
`dbase` varchar(255) NOT NULL default '',
`user` varchar(255) NOT NULL default '',
`label` varchar(255) COLLATE utf8_general_ci NOT NULL default '',
`query` text NOT NULL,
PRIMARY KEY  (`id`)
)
COMMENT='Bookmarks'
DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__column_info` (
		`id` int(5) unsigned NOT NULL auto_increment,
		`db_name` varchar(64) NOT NULL default '',
		`table_name` varchar(64) NOT NULL default '',
		`column_name` varchar(64) NOT NULL default '',
		`comment` varchar(255) COLLATE utf8_general_ci NOT NULL default '',
		`mimetype` varchar(255) COLLATE utf8_general_ci NOT NULL default '',
		`transformation` varchar(255) NOT NULL default '',
		`transformation_options` varchar(255) NOT NULL default '',
		PRIMARY KEY  (`id`),
		UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`)
)
COMMENT='Column information for phpMyAdmin'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__history` (
		`id` bigint(20) unsigned NOT NULL auto_increment,
		`username` varchar(64) NOT NULL default '',
		`db` varchar(64) NOT NULL default '',
		`table` varchar(64) NOT NULL default '',
		`timevalue` timestamp NOT NULL,
		`sqlquery` text NOT NULL,
		PRIMARY KEY  (`id`),
		KEY `username` (`username`,`db`,`table`,`timevalue`)
)
COMMENT='SQL history for phpMyAdmin'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__pdf_pages` (
		`db_name` varchar(64) NOT NULL default '',
		`page_nr` int(10) unsigned NOT NULL auto_increment,
		`page_descr` varchar(50) COLLATE utf8_general_ci NOT NULL default '',
		PRIMARY KEY  (`page_nr`),
		KEY `db_name` (`db_name`)
)
COMMENT='PDF relation pages for phpMyAdmin'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");


$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__recent` (
		`username` varchar(64) NOT NULL,
		`tables` text NOT NULL,
		PRIMARY KEY (`username`)
)
COMMENT='Recently accessed tables'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");


$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__favorite` (
		`username` varchar(64) NOT NULL,
		`tables` text NOT NULL,
		PRIMARY KEY (`username`)
)
COMMENT='Favorite tables'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__table_uiprefs` (
		`username` varchar(64) NOT NULL,
		`db_name` varchar(64) NOT NULL,
		`table_name` varchar(64) NOT NULL,
		`prefs` text NOT NULL,
		`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`username`,`db_name`,`table_name`)
)
COMMENT='Tables'' UI preferences'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__relation` (
		`master_db` varchar(64) NOT NULL default '',
		`master_table` varchar(64) NOT NULL default '',
		`master_field` varchar(64) NOT NULL default '',
		`foreign_db` varchar(64) NOT NULL default '',
		`foreign_table` varchar(64) NOT NULL default '',
		`foreign_field` varchar(64) NOT NULL default '',
		PRIMARY KEY  (`master_db`,`master_table`,`master_field`),
		KEY `foreign_field` (`foreign_db`,`foreign_table`)
)
COMMENT='Relation table'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__table_coords` (
		`db_name` varchar(64) NOT NULL default '',
		`table_name` varchar(64) NOT NULL default '',
		`pdf_page_number` int(11) NOT NULL default '0',
		`x` float unsigned NOT NULL default '0',
		`y` float unsigned NOT NULL default '0',
		PRIMARY KEY  (`db_name`,`table_name`,`pdf_page_number`)
)
COMMENT='Table coordinates for phpMyAdmin PDF output'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__table_info` (
		`db_name` varchar(64) NOT NULL default '',
		`table_name` varchar(64) NOT NULL default '',
		`display_field` varchar(64) NOT NULL default '',
		PRIMARY KEY  (`db_name`,`table_name`)
)
COMMENT='Table information for phpMyAdmin'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__designer_coords` (
		`db_name` varchar(64) NOT NULL default '',
		`table_name` varchar(64) NOT NULL default '',
		`x` INT,
		`y` INT,
		`v` TINYINT,
		`h` TINYINT,
		PRIMARY KEY (`db_name`,`table_name`)
)
COMMENT='Table coordinates for Designer'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");


$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__tracking` (
		`db_name` varchar(64) NOT NULL,
		`table_name` varchar(64) NOT NULL,
		`version` int(10) unsigned NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		`schema_snapshot` text NOT NULL,
		`schema_sql` text,
		`data_sql` longtext,
		`tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') default NULL,
		`tracking_active` int(1) unsigned NOT NULL default '1',
		PRIMARY KEY  (`db_name`,`table_name`,`version`)
)
COMMENT='Database changes tracking for phpMyAdmin'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__userconfig` (
		`username` varchar(64) NOT NULL,
		`timevalue` timestamp NOT NULL,
		`config_data` text NOT NULL,
		PRIMARY KEY  (`username`)
)
COMMENT='User preferences storage for phpMyAdmin'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__users` (
		`username` varchar(64) NOT NULL,
		`usergroup` varchar(64) NOT NULL,
		PRIMARY KEY (`username`,`usergroup`)
)
COMMENT='Users and their assignments to user groups'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__usergroups` (
		`usergroup` varchar(64) NOT NULL,
		`tab` varchar(64) NOT NULL,
		`allowed` enum('Y','N') NOT NULL DEFAULT 'N',
		PRIMARY KEY (`usergroup`,`tab`,`allowed`)
)
COMMENT='User groups with configured menu items'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__navigationhiding` (
		`username` varchar(64) NOT NULL,
		`item_name` varchar(64) NOT NULL,
		`item_type` varchar(64) NOT NULL,
		`db_name` varchar(64) NOT NULL,
		`table_name` varchar(64) NOT NULL,
		PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`)
)
COMMENT='Hidden items of navigation tree'
		DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;","phpmyadmin");

$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `pma__savedsearches` (
		`id` int(5) unsigned NOT NULL auto_increment,
		`username` varchar(64) NOT NULL default '',
		`db_name` varchar(64) NOT NULL default '',
		`search_name` varchar(64) NOT NULL default '',
		`search_data` text NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`)
)","phpmyadmin");



}
?>