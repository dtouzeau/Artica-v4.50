<?php
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.user.inc");


class piwigo{

	private $servername;
	private $database;
	private $uid;
	private $rebuildb=false;
	private $mysql_password=null;	
	private $mysql_username=null;
	private $mysql_instance_id=0;
	private $useSSL=0;
	private $www_dir=null;
	
	private function Load(){
			$sql="SELECT * from freeweb WHERE servername='$this->servername'";
			$q=new mysql();
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$this->groupware=$ligne["groupware"];
			$this->servername=$ligne["servername"];
			$this->ou=$ligne["ou"];
			$this->www_dir=$ligne["www_dir"];
			$this->ServerPort=$ligne["ServerPort"];
			$this->mysql_password=$ligne["mysql_password"];
			$this->mysql_username=$ligne["mysql_username"];	
			$this->mysql_instance_id=$ligne["mysql_instance_id"];
				
			$this->useSSL=$ligne["useSSL"];	
			if($this->www_dir==null){$this->www_dir="/var/www/$this->servername";}
			writelogs("[$this->servername] Piwigo storage:$this->www_dir",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$this->database="piwigo".time();
			if($ligne["mysql_database"]<>null){$this->database=$ligne["mysql_database"];}else{
				$sql="UPDATE freeweb SET mysql_database='{$this->database}' WHERE servername='$this->servername'";
				$q->QUERY_SQL($sql,"artica_backup");
			}
			
			if($this->mysql_username==null){$this->mysql_username=$q->mysql_admin;}
			if($this->mysql_password==null){$this->mysql_password=$q->mysql_password;}
			$this->uid=$ligne["uid"];
			
	}
	function piwigo($servername){
		$this->servername=$servername;
		$this->load();
		
		
	}
	
	
	public function verifinstall(){
			$servername=$this->servername;
			$sources="/usr/local/share/artica/piwigo_src";
			$sourcesql="$sources/install/piwigo_structure-mysql.sql";
			$appli_password=null;
			$appli_user=null;
			if(!is_dir($sources)){writelogs("[$servername] $sources no such directory",__FUNCTION__,__FILE__,__LINE__);return;}
			$q=new mysql();
			$unix=new unix();
			$ldap=new clladp();
			if(!$q->DATABASE_EXISTS($this->database)){
				$q->CREATE_DATABASE($this->database);
			}
			
			if(!$this->checkfiles()){
				$cp=$unix->find_program("cp");
				$cmd="$cp -rf $sources/* $this->www_dir/";
				writelogs("[$servername] $cmd",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				exec($cmd,$results);
				if($GLOBALS["VERBOSE"]){while (list ($none, $line) = each ($results) ){writelogs("[$servername] $line",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
			}
			
			$results=array();
			if(!$this->checkMysql()){
				if(!is_file($sourcesql)){
					writelogs("[$servername] Fatal $sourcesql no such file",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					return;
				}
				
				$mysql=$unix->find_program("mysql");
				$password=$q->mysql_password;
				if(strlen($password)>0){$password=" -p$password";}
				writelogs("[$servername] Installing table & database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				
				
				$cmd="$mysql -u $q->mysql_admin$password --batch -h $q->mysql_server -P $q->mysql_port -D $this->database < $sourcesql 2>&1";
				
				if($GLOBALS["VERBOSE"]){writelogs("[$servername] $cmd",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				
				exec($cmd,$results);

				if($GLOBALS["VERBOSE"]){while (list ($none, $line) = each ($results) ){writelogs("[$servername] $line",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
				
				if(!$this->checkMysql()){
					writelogs("[$servername] Installing table & database failed.",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					return;
				}
						
				$sql="INSERT INTO `piwigo_config` (`param`, `value`, `comment`) VALUES('local_data_dir_checked', 'true', NULL),('nb_comment_page', '10', 'number of comments to display on each page'),('log', 'false', 'keep an history of visits on your website'),('comments_validation', 'false', 'administrators validate users comments before becoming visible'),('comments_forall', 'false', 'even guest not registered can post comments'),('user_can_delete_comment', 'false', 'administrators can allow user delete their own comments'),('user_can_edit_comment', 'false', 'administrators can allow user edit their own comments'),('email_admin_on_comment_edition', 'false', 'Send an email to the administrators when a comment is modified'),('email_admin_on_comment_deletion', 'false', 'Send an email to the administrators when a comment is deleted'),('gallery_locked', 'false', 'Lock your gallery temporary for non admin users'),('gallery_title', 'Piwigo demonstration site', 'Title at top of each page and for RSS feed'),('gallery_url', '', 'Optional alternate homepage for the gallery'),('rate', 'true', 'Rating pictures feature is enabled'),('rate_anonymous', 'true', 'Rating pictures feature is also enabled for visitors'),('page_banner', '<h1>Piwigo demonstration site</h1><p>My photos web site</p>', 'html displayed on the top each page of your gallery'),('history_admin', 'false', 'keep a history of administrator visits on your website'),('history_guest', 'true', 'keep a history of guest visits on your website'),('allow_user_registration', 'true', 'allow visitors to register?'),('allow_user_customization', 'true', 'allow users to customize their gallery?'),('nbm_send_html_mail', 'true', 'Send mail on HTML format for notification by mail'),('nbm_send_mail_as', '', 'Send mail as param value for notification by mail'),('nbm_send_detailed_content', 'true', 'Send detailed content for notification by mail'),('nbm_complementary_mail_content', '', 'Complementary mail content for notification by mail'),('nbm_send_recent_post_dates', 'true', 'Send recent post by dates for notification by mail'),('email_admin_on_new_user', 'false', 'Send an email to theadministrators when a user registers'),('email_admin_on_comment', 'false', 'Send an email to the administrators when a valid comment is entered'),('email_admin_on_comment_validation', 'false', 'Send an email to the administrators when a comment requires validation'),('email_admin_on_picture_uploaded', 'false', 'Send an email to the administrators when a picture is uploaded'),('obligatory_user_mail_address', 'false', 'Mail address is obligatory for users'),('c13y_ignore', NULL, 'List of ignored anomalies'),('upload_link_everytime', 'false', 'Show upload link every time'),('upload_user_access', '2', 'User access level to upload'),('extents_for_templates', 'a:0:{}', 'Actived template-extension(s)'),('blk_menubar', '', 'Menubar options'),('menubar_filter_icon', 'true', 'Display filter icon'),('index_sort_order_input', 'true', 'Display image order selection list'),('index_flat_icon', 'true', 'Display flat icon'),('index_posted_date_icon', 'true', 'Display calendar by posted date'),('index_created_date_icon', 'true', 'Display calendar by creation date icon'),('index_slideshow_icon', 'true', 'Display slideshow icon'),('picture_metadata_icon', 'true', 'Display metadata icon on picture page'),('picture_slideshow_icon', 'true', 'Display slideshow icon on picture page'),('picture_favorite_icon', 'true', 'Display favorite icon on picture page'),('picture_download_icon', 'true', 'Display download icon on picture page'),('picture_navigation_icons', 'true', 'Display navigation icons on picture page'),('picture_navigation_thumb', 'true', 'Display navigation thumbnails on picture page'),('picture_informations', 'a:11:{s:6:\"author\";b:1;s:10:\"created_on\";b:1;s:9:\"posted_on\";b:1;s:10:\"dimensions\";b:1;s:4:\"file\";b:1;s:8:\"filesize\";b:1;s:4:\"tags\";b:1;s:10:\"categories\";b:1;s:6:\"visits\";b:1;s:12:\"average_rate\";b:1;s:13:\"privacy_level\";b:1;}', 'Information displayed on picture page'),('secret_key', '1fed8190c215435d360c82cb045e37ba', 'a secret key specific to the gallery for internal use');";
				$q->QUERY_SQL($sql,$this->database);	
				
				$sql="INSERT INTO `piwigo_languages` (`id`, `version`, `name`) VALUES('pt_BR', '0', 'Brasil [BR]'),('ca_ES', '0', 'Catalan [CA]'),('da_DK', '0', 'Dansk [DK]'),('de_DE', '0', 'Deutsch [DE]'),('en_UK', '0', 'English [UK]'),('es_AR', '0', 'Español [AR]'),('es_ES', '0', 'Español [ES]'),('fr_FR', '0', 'Français [FR]'),('fr_CA', '0', 'Français [QC]'),('hr_HR', '0', 'Hrvatski [HR]'),('it_IT', '0', 'Italiano [IT]'),('lv_LV', '0', 'Latviešu [LV]'),('hu_HU', '0', 'Magyar [HU]'),('nl_NL', '0', 'Nederlands [NL]'),('pl_PL', '0', 'Polski [PL]'),('pt_PT', '0', 'Português [PT]'),('ro_RO', '0', 'Român? [RO]'),('sk_SK', '0', 'Slovensky [SK]'),('sl_SL', '0', 'Slovenšcina [SL]'),('sh_RS', '0', 'Srpski [SR]'),('sv_SE', '0', 'Svenska [SE]\n'),('vi_VN', '0', 'Ti?ng Vi?t [VN]'),('tr_TR', '0', 'Türkçe [TR]'),('cs_CZ', '0', '?esky [CZ]'),('mk_MK', '0', '?????????? [MK]'),('ru_RU', '0', '??????? [RU]'),('sr_RS', '0', '?????? [SR]'),('he_IL', '0', '????? [IL]'),('ar_SA', '0', '??????? [AR]'),('fa_IR', '0', '????? [IR]'),('ka_GE', '0', '??????? [GE]'),('ja_JP', '0', '??? [JP]'),('zh_CN', '0', '???? [CN]');";	
				$q->QUERY_SQL($sql,$this->database);
				
				$sql="INSERT INTO `piwigo_user_infos` (`user_id`, `nb_image_line`, `nb_line_page`, `status`, `adviser`, `language`, `maxwidth`, `maxheight`, `expand`, `show_nb_comments`, `show_nb_hits`, `recent_period`, `theme`, `registration_date`, `enabled_high`, `level`) VALUES(1, 5, 3, 'webmaster', 'false', 'en_UK', NULL, NULL, 'false', 'false', 'false', 7, 'Sylvia', NOW(), 'true', 8),(2, 5, 3, 'guest', 'false', 'en_UK', NULL, NULL, 'false', 'false', 'false', 7, 'Sylvia', '2011-01-11 12:51:10', 'true', 0);";
				$q->QUERY_SQL($sql,$this->database);
			}
			
		$ldap=new clladp();
		$appli_user=$ldap->ldap_admin;
		$appli_password=$ldap->ldap_password;
				
		writelogs("[$servername] owner: $this->uid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($this->uid<>null){
			$appli_user=$this->uid;
			$ct=new user($this->uid);
			$appli_password=$ct->password;
		}	
		$md_password=md5($appli_password);
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT username FROM piwigo_users WHERE username='$appli_user'",$this->database));
		if(trim($ligne["username"])==null){
			writelogs("[$servername] owner: $appli_user not found in table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$sql="INSERT INTO `piwigo_users` (`username`, `password`, `mail_address`) 
			VALUES ('$appli_user', '$md_password', '$appli_user@$servername')";		
			
		}else{
			$sql="UPDATE `piwigo_users` SET `username`='$appli_user', `password`='$md_password' WHERE `username`='$appli_user'";	
		}
		$q->QUERY_SQL($sql,$this->database);
		
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT username FROM piwigo_users WHERE username='guest'",$this->database));
		if(trim($ligne["username"])==null){
			writelogs("[$servername] owner: $appli_user not found in table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$sql="INSERT INTO `piwigo_users` (`username`, `password`, `mail_address`) 
			VALUES ('guest', NULL, NULL)";
			$q->QUERY_SQL($sql,$this->database);
		}
		
		
		
		$f=array();		
		$f[]="<?php";	
		$f[]="\$conf['dblayer'] = 'mysql';";
		$f[]="\$conf['db_base'] = '$this->database';";
		$f[]="\$conf['db_user'] = '$this->mysql_username';";
		$f[]="\$conf['db_password'] = '$this->mysql_password';";
		$f[]="\$conf['db_host'] = '$q->mysql_server';";
		$f[]="";
		$f[]="\$prefixeTable = 'piwigo_';";
		$f[]="";
		$f[]="define('PHPWG_INSTALLED', true);";
		$f[]="define('PWG_CHARSET', 'utf-8');";
		$f[]="define('DB_CHARSET', 'utf8');";
		$f[]="define('DB_COLLATE', '');";
		$f[]="?>";	
		@mkdir("$this->www_dir/local/config",0755,true);	
		@file_put_contents("$this->www_dir/local/config/database.inc.php",@implode("\n",$f));
		writelogs("[$servername] $this->www_dir/local/config/database.inc.php done",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
	}
	
	
	
	private function checkfiles(){
		$f[]="about.php";
		$f[]="admin.php";
		$f[]="comments.php";
		$f[]="identification.php";
		$f[]="install.php";
		$f[]="notification.php";
		$f[]="picture.php";
		$f[]="profile.php";
		$f[]="random.php";
		$f[]="search.php";
		$f[]="tags.php ";
		$f[]="upgrade.php";
		$f[]="ws.php";
		$f[]="action.php";
		$f[]="category.php";
		$f[]="feed.php";
		$f[]="index.php";
		$f[]="nbm.php";
		$f[]="password.php";
		$f[]="popuphelp.php";
		$f[]="qsearch.php";
		$f[]="register.php";
		$f[]="search_rules.php";
		$f[]="upgrade_feed.php";
		$f[]="upload.php";

		$q=new mysql();
		while (list ($none, $file) = each ($f) ){
			if(!file_exists("$this->www_dir/$file")){
				writelogs("Fatal $this->www_dir/$file no such file",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			
		}
		return true;		
		
	}
	
	
	private function checkMysql(){
		$f[]="piwigo_caddie";
		$f[]="piwigo_categories";
		$f[]="piwigo_comments";
		$f[]="piwigo_config";
		$f[]="piwigo_favorites";
		$f[]="piwigo_group_access";
		$f[]="piwigo_groups";
		$f[]="piwigo_history";
		$f[]="piwigo_history_summary";
		$f[]="piwigo_image_category";
		$f[]="piwigo_image_tag";
		$f[]="piwigo_images";
		$f[]="piwigo_languages";
		$f[]="piwigo_old_permalinks";
		$f[]="piwigo_plugins";
		$f[]="piwigo_rate";
		$f[]="piwigo_search";
		$f[]="piwigo_sessions";
		$f[]="piwigo_sites";
		$f[]="piwigo_tags";
		$f[]="piwigo_themes";
		$f[]="piwigo_upgrade";
		$f[]="piwigo_user_access";
		$f[]="piwigo_user_cache";
		$f[]="piwigo_user_cache_categories";
		$f[]="piwigo_user_feed";
		$f[]="piwigo_user_group";
		$f[]="piwigo_user_infos";
		$f[]="piwigo_user_mail_notification";
		$f[]="piwigo_users";
		$f[]="piwigo_waiting";
		
		$q=new mysql();
		while (list ($none, $table) = each ($f) ){
			if(!$q->TABLE_EXISTS($table, $this->database)){
				return false;
			}
			
		}
		return true;
		
	}
	
	
	
	
}