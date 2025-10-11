<?php
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.mysql-multi.inc');
class dotclear{
	var $groupware;
	var $ou;
	var $www_dir;
	var $ServerPort;	
	var $servername;
	var $database;
	var $uid;
	var $rebuildb=false;
	var $mysql_password=null;	
	var $mysql_username=null;
	var $mysql_instance_id=0;
	
	function __construct($hostname=null){
			if($hostname<>null){
				$this->servername=$hostname;
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
				if($this->www_dir==null){$this->www_dir="/var/www/$this->servername";}
				writelogs("[$this->servername] DOTCLEAR mysql user=$this->mysql_username",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				if(strlen($ligne["mysql_database"])>16){$ligne["mysql_database"]=null;}
				$this->database="dt_".date("YmdHi");
				if($ligne["mysql_database"]<>null){$this->database=$ligne["mysql_database"];}else{
					$sql="UPDATE freeweb SET mysql_database='{$this->database}' WHERE servername='$this->servername'";
					$q->QUERY_SQL($sql,"artica_backup");
				}
				$this->uid=$ligne["uid"];	
			}
	}
	
	function CheckInstall(){
		$unix=new unix();
		@mkdir($this->www_dir,755,true);
		if(!$this->VerifyFiles()){
			writelogs("[$this->servername] DOTCLEAR Installing core application",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$cp=$unix->find_program("cp");
			shell_exec("$cp -rf /usr/share/dotclear/* $this->www_dir/");
		}
		$this->CreateConfig();
		
	}
	
	private function VerifyFiles(){

				$f[]="themes/default";
				$f[]="themes/default/smilies";
				$f[]="themes/default/smilies/confused.png";
				$f[]="themes/default/smilies/evil.png";
				$f[]="themes/default/smilies/normal.png";
				$f[]="themes/default/smilies/dizzy.png";
				$f[]="themes/default/smilies/idea.png";
				$f[]="themes/default/smilies/razz.png";
				$f[]="themes/default/smilies/wink.png";
				$f[]="themes/default/smilies/redface.png";
				$f[]="themes/default/smilies/alien.png";
				$f[]="themes/default/smilies/lol.png";
				$f[]="themes/default/smilies/question.png";
				$f[]="themes/default/smilies/laugh.png";
				$f[]="themes/default/smilies/exclam.png";
				$f[]="themes/default/smilies/mrgreen.png";
				$f[]="themes/default/smilies/eek.png";
				$f[]="themes/default/smilies/cry.png";
				$f[]="themes/default/smilies/surprised.png";
				$f[]="themes/default/smilies/cool.png";
				$f[]="themes/default/smilies/arrow.png";
				$f[]="themes/default/smilies/smilies.txt";
				$f[]="themes/default/smilies/rolleyes.png";
				$f[]="themes/default/smilies/sad.png";
				$f[]="themes/default/smilies/smile.png";
				$f[]="themes/default/smilies/angry.png";
				$f[]="themes/default/js/post.js";
				$f[]="themes/default/js/jquery.js";
				$f[]="themes/default/js/jquery.cookie.js";
				$f[]="themes/default/_define.php";
				$f[]="themes/default/style.css";
				$f[]="themes/default/img";
				$f[]="themes/default/img/comment-t.png";
				$f[]="themes/default/img/page-b.png";
				$f[]="themes/default/img/tag.png";
				$f[]="themes/default/img/commentmy-t.png";
				$f[]="themes/default/img/comment.png";
				$f[]="themes/default/img/trackback.png";
				$f[]="themes/default/img/page-t.png";
				$f[]="themes/default/img/comment-b.png";
				$f[]="themes/default/img/feed.png";
				$f[]="themes/default/img/commentmy-b.png";
				$f[]="themes/default/img/page-bg.png";
				$f[]="themes/default/img/attach.png";
				$f[]="themes/default/img/body-bg.png";
				$f[]="themes/default/screenshot.jpg";
				$f[]="themes/default/print.css";
				$f[]="inc/swf";
				$f[]="inc/digests";
				$f[]="inc/core_error.php";
				$f[]="inc/load_plugin_file.php";
				$f[]="inc/core/class.dc.log.php";
				$f[]="inc/core/class.dc.error.php";
				$f[]="inc/prepend.php";
				$f[]="inc/public/class.dc.template.php";
				$f[]="inc/public/lib.urlhandlers.php";
				$f[]="inc/public/rs.extension.php";
				$f[]="inc/public/default-templates";
				$f[]="inc/public/default-templates/atom-comments.xml";
				$f[]="inc/public/default-templates/_top.html";
				$f[]="inc/public/prepend.php";
				$f[]="inc/public/lib.tpl.context.php";
				$f[]="inc/admin/lib.dc.page.php";
				$f[]="inc/admin/prepend.php";
				$f[]="inc/admin/lib.pager.php";
				$f[]="inc/admin/class.dc.menu.php";
				$f[]="inc/dbschema/upgrade-cli.php";
				$f[]="inc/dbschema/db-schema.php";
				$f[]="inc/dbschema/upgrade.php";
				$f[]="inc/libs/clearbricks/template";
				$f[]="inc/libs/clearbricks/template/class.template.php";
				$f[]="inc/libs/clearbricks/mail.convert";
				$f[]="inc/libs/clearbricks/mail.convert/class.mail.convert.php";
				$f[]="inc/libs/clearbricks/zip/class.zip.php";
				$f[]="inc/libs/clearbricks/zip/class.unzip.php";
				$f[]="inc/libs/clearbricks/_common.php";
				$f[]="inc/libs/clearbricks/net.nntp";
				$f[]="inc/libs/clearbricks/filemanager/class.filemanager.php";
				$f[]="inc/libs/clearbricks/xmlsql/class.xmlsql.php";
				$f[]="inc/libs/clearbricks/debian/dirs";
				$f[]="inc/libs/clearbricks/debian/mkdcl.php";
				$f[]="inc/libs/clearbricks/session.db";
				$f[]="inc/libs/clearbricks/session.db/class.session.db.php";
				$f[]="inc/libs/clearbricks/net.xmlrpc";
				$f[]="inc/libs/clearbricks/net.xmlrpc/class.net.xmlrpc.php";
				$f[]="inc/libs/clearbricks/mail/class.socket.mail.php";
				$f[]="inc/libs/clearbricks/ext/incutio.ixr_library.php";
				$f[]="inc/libs/clearbricks/html.filter";
				$f[]="inc/libs/clearbricks/html.filter/class.html.filter.php";
				$f[]="inc/libs/clearbricks/mail.mime";
				$f[]="inc/libs/clearbricks/mail.mime/class.mime.message.php";
				$f[]="index.php";
				$f[]="locales/en/plugins.po";
				$f[]="locales/en/public.lang.php";
				$f[]="locales/en/help/media.html";
				$f[]="admin/comments_actions.php";
				$f[]="admin/blogs.php";
				$f[]="admin/posts_actions.php";
				$f[]="admin/popup_link.php";
				$f[]="admin/auth.php";
				$f[]="admin/category.php";
				$f[]="admin/update.php";
				$f[]="admin/comments.php";
				$f[]="admin/blog.php";
				$f[]="admin/trackbacks.php";
				$f[]="admin/langs.php";
				$f[]="admin/js/_blog_pref.js";
				$f[]="admin/js/jsToolBar/jsToolBar.dotclear.js";
				$f[]="admin/js/prelude.js";
				$f[]="admin/js/_comments.js";
				$f[]="admin/js/dragsort-tablerows.js";
				$f[]="admin/js/tool-man";
				$f[]="admin/js/tool-man/events.js";
				$f[]="admin/js/common.js";
				$f[]="admin/js/_preferences.js";
				$f[]="admin/js/_users.js";
				$f[]="admin/js/confirm-close.js";
				$f[]="admin/js/_category.js";
				$f[]="admin/js/_index.js";
				$f[]="admin/js/_permissions.js";
				$f[]="admin/permissions.php";
				$f[]="admin/style";
				$f[]="admin/style/farbtastic/mask.png";
				$f[]="admin/style/candyUpload/cancel.png";
				$f[]="admin/images/help.png";
				$f[]="admin/images/menu/entries-b.png";
				$f[]="admin/images/menu/media.png";
				$f[]="admin/images/menu/search.png";
				$f[]="admin/images/menu/plugins-b.png";
				$f[]="admin/images/plus.png";
				$f[]="admin/images/check-on.png";
				$f[]="admin/images/logout.png";
				$f[]="admin/images/outgoing-blue.png";
				$f[]="admin/images/noscreenshot.png";
				$f[]="admin/images/trash.png";
				$f[]="admin/images/superadmin.png";
				$f[]="admin/images/junk.png";
				$f[]="admin/images/scheduled.png";
				$f[]="admin/images/admin.png";
				$f[]="admin/images/attach.png";
				$f[]="admin/images/favicon.png";
				$f[]="admin/images/edit-mini.png";
				$f[]="admin/images/check-wrn.png";
				$f[]="admin/images/add.png";
				$f[]="admin/popup_posts.php";
				$f[]="admin/media.php";
				$f[]="admin/post_media.php";
				$f[]="admin/plugins.php";
				$f[]="admin/media_item.php";
				$f[]="admin/xmlrpc.php";

		while (list ($none, $xpath) = each ($f) ){
			$testfile="$this->www_dir/$xpath";
			if(!is_file($testfile)){
				if(!is_dir($testfile)){
					writelogs("[$this->servername] DOTCLEAR $testfile no such file or directory",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					return false;
				}
			}
			
		}
		
		return true;				
		
	}
	
	
	
	private function CreateConfig(){
			$q=new mysql();
			if($this->mysql_instance_id>0){$q=new mysql_multi($this->mysql_instance_id);}
			if($this->mysql_username==null){$this->mysql_username=$q->mysql_admin;}
			if($this->mysql_password==null){$this->mysql_password=$q->mysql_password;}		
			writelogs("[$this->servername] DOTCLEAR mysql user=$this->mysql_username",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);

			$DB_HOST=$q->mysql_server;
			if($this->mysql_instance_id>0){$DB_HOST=":$q->SocketPath";}		
			$serverkey=md5($this->servername);
			if(!$q->DATABASE_EXISTS($this->database)){$q->CREATE_DATABASE($this->database);}
		
			$f[]="<?php";
			$f[]="# -- BEGIN LICENSE BLOCK ---------------------------------------";
			$f[]="#";
			$f[]="# This file is part of Dotclear 2.";
			$f[]="#";
			$f[]="# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear";
			$f[]="# Licensed under the GPL version 2.0 license.";
			$f[]="# See LICENSE file or";
			$f[]="# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html";
			$f[]="#";
			$f[]="# -- END LICENSE BLOCK -----------------------------------------";
			$f[]="if (!defined('DC_RC_PATH')) { return; }";
			$f[]="";
			$f[]="// Database driver (mysql, pgsql, sqlite)";
			$f[]="define('DC_DBDRIVER','mysql');";
			$f[]="";
			$f[]="// Database hostname (usually \"localhost\")";
			$f[]="define('DC_DEBUG',true);";
			$f[]="define('DC_DBHOST','$DB_HOST');";
			$f[]="";
			$f[]="// Database user";
			$f[]="define('DC_DBUSER','$this->mysql_username');";
			$f[]="";
			$f[]="// Database password";
			$f[]="define('DC_DBPASSWORD','$this->mysql_password');";
			$f[]="";
			$f[]="// Database name";
			$f[]="define('DC_DBNAME','$this->database');";
			$f[]="";
			$f[]="// Tables' prefix";
			$f[]="define('DC_DBPREFIX','dc_');";
			$f[]="";
			$f[]="// Persistent database connection";
			$f[]="define('DC_DBPERSIST',false);";
			$f[]="";
			$f[]="// Crypt key (password storage)";
			$f[]="define('DC_MASTER_KEY','$serverkey');";
			$f[]="";
			$f[]="";
			$f[]="// Admin URL. You need to set it for some features.";
			$f[]="define('DC_ADMIN_URL','http://$this->servername/admin/');";
			$f[]="";
			$f[]="// Admin mail from address. For password recovery and such.";
			$f[]="define('DC_ADMIN_MAILFROM','postmaster@$this->servername');";
			$f[]="";
			$f[]="// Cookie's name";
			$f[]="define('DC_SESSION_NAME','$serverkey');";
			$f[]="";
			$f[]="// Plugins root";
			$f[]="define('DC_PLUGINS_ROOT',dirname(__FILE__).'/../plugins');";
			$f[]="";
			$f[]="// Template cache directory";
			$f[]="define('DC_TPL_CACHE',dirname(__FILE__).'/../cache');";		
			@file_put_contents("$this->www_dir/inc/config.php",@implode("\n", $f));
			if(is_dir("$this->www_dir/cache")){chmod("$this->www_dir/cache", 755);}
			$unix=new unix();
			$APACHE_RUN_USER=$unix->APACHE_SRC_ACCOUNT();
			$APACHE_RUN_GROUP=$unix->APACHE_SRC_GROUP();
			$chown=$unix->find_program("chown");
			$chmod=$unix->find_program("chmod");
			shell_exec("$chown -R $APACHE_RUN_USER:$APACHE_RUN_GROUP $this->www_dir");
			shell_exec("$chmod -R 777 $this->www_dir/cache");
	}
	
	
	function ChangeDotClearUris(){
		
		
		$sql="SELECT * FROM `dotclear_blog`";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		while ($ligne =@mysqli_fetch_array($results)) { 
			$uri=$ligne["blog_url"];
			$uri=substr($uri,strpos($uri,'/'),strlen($uri));
			$uri=str_replace('//','',$uri);
			$uri=substr($uri,strpos($uri,'/')+1,strlen($uri));
			
			$new_uri="$this->DotClearExternalWebUri/$uri";
			$sql="UPDATE dotclear_blog SET blog_url='$new_uri' WHERE blog_uid='{$ligne["blog_uid"]}'";
			$q->QUERY_SQL($sql,"artica_backup");
			$count=$count+1;
			
			
			
		}	
		
		echo "$count blog(s) updated\n";
		
		
		
		
		
	}
	

	
	function TestsDatabase(){
		$result=true;
		$s=new mysql();
		$tables=array("dotclear_blog","dotclear_category","dotclear_comment","dotclear_link","dotclear_log",
		"dotclear_media","dotclear_meta","dotclear_permissions","dotclear_ping","dotclear_post",
		"dotclear_post_media","dotclear_session","dotclear_setting","dotclear_spamrule","dotclear_user","dotclear_version");
		while (list ($num, $ligne) = each ($tables) ){
			if(!$s->TABLE_EXISTS($ligne,$this->database)){
				writelogs("FAILED ON $ligne",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$result=false;
				break;
			}
			
		}
		
		return $result;
		
	}
	
	
	function AddUserDotClear($uid,$password,$mail,$home){
		$blog_uid=md5($uid);
		$q=new mysql();
		$sql="SELECT blog_id FROM dotclear_blog WHERE blog_uid='$blog_uid'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,$this->database));
		if($ligne["blog_id"]==null){
			$sql="INSERT INTO `dotclear_blog` (`blog_id`, `blog_uid`, `blog_creadt`, `blog_upddt`, `blog_url`, `blog_name`, `blog_desc`, `blog_status`) 
			VALUES
			('$uid', '$blog_uid', '".date('Y-m-d H:i:s')."', '".date('Y-m-d H:i:s')."', '$this->DotClearExternalWebUri/$uid/index.php?', '$uid', '', 1)";
			$q->QUERY_SQL($sql,$this->database);
			$this->AddBlogSettings($uid,'lang','system','string','en','Default blog language');
			$this->AddBlogSettings($uid,'blog_timezone','system','string','Europe/Berlin','Blog timezone');
			$this->AddBlogSettings($uid,'antispam_moderation_ttl','antispam','string','0','');
			$this->AddBlogSettings($uid,'antispam_date_last_purge','antispam','integer','1232927114','Antispam Date Last Purge (unix timestamp)');
			$this->AddBlogSettings($uid,'public_path','system','string',"$home/blog/public",'Path to public directory, begins with a / for a full system path');
			$this->AddBlogSettings($uid,'public_url','system','string',"/$uid/public",'URL to public directory');
			}else{
				$this->SetBlogSettings($uid,'public_path','system',"$home/blog/public");
				$this->SetBlogSettings($uid,'public_url','system',"/$uid/public");
			}
			
		$this->AddUser($uid,$password,$mail);
		$this->RestartLDAP();
		}
	
	function AddBlogSettings($uid,$key,$setting_ns,$setting_type,$value,$setting_label){
		$q=new mysql();
		$sql="SELECT setting_value FROM dotclear_setting WHERE setting_id='$key' AND blog_id='$uid' AND setting_ns='$setting_ns'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,$this->database));
		if($ligne["setting_value"]==null){
			$sql="INSERT INTO `dotclear_setting` (`setting_id`, `blog_id`, `setting_ns`, `setting_value`, `setting_type`, `setting_label`) VALUES
			('$key','$uid','$setting_ns','$value','$setting_type','$setting_label');";
			$q->QUERY_SQL($sql,$this->database);
			
		}
	}
	
	function SetBlogSettings($uid,$key,$setting_ns,$value){
		$q=new mysql();
		writelogs("updateing $key=$value for $uid");
		$sql="
		UPDATE `artica_backup`.`dotclear_setting` SET `setting_value` = '$value' WHERE 
		`dotclear_setting`.`setting_id` = '$key' 
		AND `dotclear_setting`.`blog_id` = '$uid' 
		AND `dotclear_setting`.`setting_ns` = '$setting_ns'  LIMIT 1 ;";
		
		writelogs("$sql");
		$q->QUERY_SQL($sql,$this->database);
	}
	
	
	function AddUser($uid,$password,$email){
		$password=crypt::hmac('artica',$password);
		$date=date('Y-m-d H:i:s');
		$q=new mysql();
		$sql="SELECT user_pwd FROM dotclear_user WHERE user_id='$uid'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,$this->database));
		if($ligne["user_pwd"]==null){
			$sql="INSERT INTO `dotclear_user` (`user_id`, `user_super`, `user_status`, `user_pwd`, `user_recover_key`,
										   `user_name`, `user_firstname`, `user_displayname`, `user_email`, 
										   `user_url`, `user_desc`, `user_default_blog`, `user_options`, `user_lang`, `user_tz`, `user_post_status`, `user_creadt`, `user_upddt`) VALUES
											('$uid', 0, 1, '$password', NULL, '$uid', '$uid', NULL, '$email', NULL, NULL, '$uid', 
											'a:3:{s:9:\"edit_size\";i:24;s:14:\"enable_wysiwyg\";b:1;s:11:\"post_format\";s:4:\"wiki\";}', 
											'en','Europe/Berlin', -2, '$date', '$date');
											";
			$q->QUERY_SQL($sql,$this->database);
		}else{
			$sql="UPDATE `artica_backup`.`dotclear_user` SET `user_pwd` = '$password' WHERE `dotclear_user`.`user_id` = '$uid' LIMIT 1 ;";
			$q->QUERY_SQL($sql,$this->database);
		}
		

		$sql="SELECT permissions FROM dotclear_permissions WHERE user_id='$uid' AND blog_id='$uid'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,$this->database));
		if($ligne["permissions"]==null){
			$sql="INSERT INTO `dotclear_permissions` (`user_id`, `blog_id`, `permissions`) VALUES
				('$uid', '$uid', '|admin|pages|blogroll|');";
			$q->QUERY_SQL($sql,$this->database);
		}
		
		

		
		
	}
	
	

	
	
}

# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2006 Olivier Meunier and contributors. All rights
# reserved.
#
# Clearbricks is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# Clearbricks is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# ***** END LICENSE BLOCK *****

/**
* Functions to handle passwords (hash, random password generator...)
*
* @package Clearbricks
*/
class crypt
{
	/**
	* Returns an HMAC encoded value of <var>$data</var>, using the said <var>$key</var>
	* and <var>$hashfunc</var> as hash method (sha1 or md5 are accepted.)
	*
	* @param	string	$key			Hash key
	* @param	string	$data		Data
	* @param	string	$hashfunc		Hash function (md5 or sha1)
	* @return string
	*/
	public static function hmac($key,$data,$hashfunc='sha1')
	{
		$blocksize=64;
		if ($hashfunc != 'sha1') {
			$hashfunc = 'md5';
		}
		
		if (strlen($key)>$blocksize) {
			$key=pack('H*', $hashfunc($key));
		}
		
		$key=str_pad($key,$blocksize,chr(0x00));
		$ipad=str_repeat(chr(0x36),$blocksize);
		$opad=str_repeat(chr(0x5c),$blocksize);
		$hmac = pack('H*',$hashfunc(($key^$opad).pack('H*',$hashfunc(($key^$ipad).$data))));
		return bin2hex($hmac);
	}
	
	/**
	* Returns an 8 characters random password.
	*
	* @return	string
	*/
	public static function createPassword()
	{
		$pwd = array();
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$chars2 = '$!@';
		
		foreach (range(0,8) as $i) {
			$pwd[] = $chars[rand(0,strlen($chars)-1)];
		}
		
		$pos1 = array_rand(array(0,1,2,3));
		$pos2 = array_rand(array(4,5,6,7));
		$pwd[$pos1] = $chars2[rand(0,strlen($chars2)-1)];
		$pwd[$pos2] = $chars2[rand(0,strlen($chars2)-1)];
		
		return implode('',$pwd);
	}
}