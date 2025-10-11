<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["operation"])){operation();exit;}
if(isset($_GET["remove-folder"])){remove_folder();exit;}
if(isset($_POST["remove-folder"])){remove_folder_perform();exit;}
if(isset($_GET["add-folder"])){add_folder_js();exit;}
if(isset($_POST["add-folder"])){add_folder_perform();exit;}


js();


function remove_folder():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $folder=$_GET["remove-folder"];
    $function=$_GET["function"];
    return $tpl->js_confirm_delete($folder, "remove-folder","$folder","$function()");
}
function add_folder_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $folder=$_GET["add-folder"];
    $function=$_GET["function"];
    return $tpl->js_prompt("{new_directory}", $folder,ico_plus,$page,"add-folder","$function()","",$folder);
}
function remove_folder_perform():bool{
    $targetFolder=$_POST["remove-folder"];
    $newpathEnc=urlencode($targetFolder);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/deletefolder/$newpathEnc");
    return admin_tracks("Delete a directory $targetFolder");

}
function add_folder_perform():bool{
    $user=new usersMenus();
    if(!$user->AsAnAdministratorGeneric){
        return false;
    }

    $tpl=new template_admin();
    $user=new usersMenus();
    if(!$user->AsAnAdministratorGeneric){
        return false;
    }

    $sourceFolder=$_POST["KeyID"];
    if($sourceFolder=="/"){
        echo $tpl->post_error("You must select a valid directory");
        return false;
    }
    $targetFolder=$_POST["add-folder"];
    $newpath="$sourceFolder/$targetFolder";
    $newpathEnc=urlencode($newpath);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/createfolder/$newpathEnc");
    return admin_tracks("Create a new directory $newpath");
}

function js(){
	$page=CurrentPageName();
    $tpl=new template_admin();
    $titlesrc=$_GET["title"];
    $title=$tpl->javascript_parse_text("$titlesrc: {select_a_directory}");

    $field_id=$_GET["field-id"];

    $tokens[]="field-id=$field_id";
    $tokens[]="title=".urlencode($titlesrc);

    if(isset($_GET["basepath"])) {
        $basePath = $_GET["basepath"];
        $tokens[] = "basepath=$basePath";
    }
    if(isset($_GET["no-select"])){
        $tokens[] = "no-select=yes";
    }

    $ztok=@implode("&",$tokens);
	$tpl->js_dialog4($title, "$page?popup=yes&$ztok");
	
	
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $field_id=$_GET["field-id"];
    $topbuttons=array();
	$t=time();
    $basePath="/";
    if(isset($_GET["basepath"])) {
        $basePath = urlencode($_GET["basepath"]);
    }
    $users=new usersMenus();
    if($users->AsAnAdministratorGeneric) {
        $topbuttons[] = array("CreateDir$t()", ico_plus, "{new_directory}");
        $topbuttons[] = array("Delete$t()", ico_trash, "{remove_directory}");
        $topbuttons[] = array("Selected$t()", ico_arrow_right, "{select}: <span id='bt-$t'></span>");
    }
    $btns=$tpl->table_buttons($topbuttons);



	$html[]="<input type='hidden' id='selected-$t' value=''>";
    $html[]=$btns;
	$html[]="<div id='jstree_$t' style='margin:30px'></div>";
    $html[]="<script>
function buildTree$t() {
  const selector = '#jstree_$t';
  const tree = $(selector);
  tree.jstree({
        core: {
            data: {
                url: 'fw.browse-directory.backend.php?basePath=$basePath',
                data: function (node) {
                    return { id: node.id };
                }
            },
            check_callback: true,
        },
    });
  
  tree.on('select_node.jstree', function (e, data) {
    $('#bt-$t').text(data.node.id);
  });
    
  tree.on('loaded.jstree', function () {
    const inst = tree.jstree(true);
    const root = inst.get_node('#');
    if (!root || !root.children) return;
    root.children.forEach(function (firstLevelId) {
      inst.open_node(firstLevelId, function () {
        const firstLevelNode = inst.get_node(firstLevelId);
        if (firstLevelNode && firstLevelNode.children) {
          // Open all second-level children
          firstLevelNode.children.forEach(function (secondLevelId) {
            inst.open_node(secondLevelId);
          });
        }
      });
    });
  });

 }
function Selected$t(){
      let ref = $('#jstree_$t').jstree(true), sel = ref.get_selected();
        if (sel.length) {
            const forbidden = ['/', '/var', '/home', '/usr','/root',  '/lib',  '/usr/share',  '/usr/share/artica-postfix',  '/usr/lib',  '/usr/bin',  '/usr/sbin',  '/usr/local',  '/usr/lib32',  '/etc',  '/sbin',  '/proc',  '/sys',  '/dev',  '/boot',  '/lost+found',  '/bin',  '/run',  '/mnt',  '/tmp','/opt','/srv','/media','/automounts'];
            var fieldid='$field_id';
            var value=sel[0];
            if (!forbidden.includes(value)) {
                document.getElementById('$field_id').value=value;
                dialogInstance4.close();
            }
       }
}
function CreateDir$t(){
    let ref = $('#jstree_$t').jstree(true), sel = ref.get_selected();
    if (sel.length) {
        var value=sel[0];
        const encoded = encodeURIComponent(value);
        Loadjs('$page?add-folder='+encoded+'&function=Refresh$t');
        return true;
    }
    let basePath='$basePath'
    if (basePath.length > 3) {
        Loadjs('$page?add-folder='+basePath+'&function=Refresh$t');
    }
}

function Refresh$t(){
    let ref = $('#jstree_$t').jstree(true), sel = ref.get_selected();
    ref.refresh();
} 

function Delete$t(){
     let ref = $('#jstree_$t').jstree(true), sel = ref.get_selected();
    if (sel.length) {
        var value=sel[0];
        const encoded = encodeURIComponent(value);
        Loadjs('$page?remove-folder='+encoded+'&function=Refresh$t');
    }   
}

$(document).ready(function () {
   buildTree$t();
});

		</script>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}
function operation(){
	$default="/";
    $rslt=array();
    if(isset($_GET["basepath"])) {
        if(strpos($_GET["basepath"],"==")>0) {
            $GLOBALS["BASEPATH"] = base64_decode($_GET["basepath"]);
            $default=base64_decode($_GET["basepath"]);
        }else{
            $default=$_GET["basepath"];
        }
    }
    if(!isset($GLOBALS["BASEPATH"])){$GLOBALS["BASEPATH"]=null;}
	
	switch($_GET['operation']) {
		case 'get_node':

			$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : $default;
            VERBOSE("get_node: lst($node,..)",__LINE__);
			$rslt = lst($node, (isset($_GET['id']) && $_GET['id'] === '#'));
			break;
			
			
		case "get_content":
			$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : $default;
			$rslt=array();
			break;
			
			
		case "create_node":
			break;
			
		case "rename_node":
			$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : $default;
			$text=$_GET["text"];
			$sock=new sockets();
			$text=replace_accents($text);
			$Directory=urlencode(base64_encode(Getpath($node)."/".$text));
			$sock->getFrameWork("system.php?create-directory=$Directory");
			$rslt=array();
			break;
	}
	
	
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($rslt);
}


function protected_dir($dir){
	$dir=str_replace("//", "/", $dir);
	$tbr=explode("/",$dir);
	if($tbr[0]==null){$tbr[0]=$tbr[1];}
	writelogs($dir." --> '" .$tbr[0]."'",__FUNCTION__,__FILE__,__LINE__);
	if($tbr[0]=="proc"){return true;}
    if($tbr[0]=="root"){return true;}
	if($tbr[0]=="boot"){return true;}
	if($tbr[0]=="dev"){return true;}
	if($tbr[0]=="etc"){return true;}
	if($tbr[0]=="lost+found"){return true;}
	if($tbr[0]=="opt"){return true;}
    if($tbr[0]=="tmp"){return true;}
	if($tbr[0]=="sbin"){return true;}
    if($tbr[0]=="emul"){return true;}
	if($tbr[0]=="bin"){return true;}
	if($tbr[0]=="lib"){return true;}
	if($tbr[0]=="lib32"){return true;}
	if($tbr[0]=="lib64"){return true;}
	if($tbr[0]=="var"){return true;}
	if($tbr[0]=="selinux"){return true;}
	if($tbr[0]=="sys"){return true;}
	if($tbr[0]=="run"){return true;}
	if($tbr[0]=="srv"){return true;}
    if($tbr[0]=="libx32"){return true;}

    if(preg_match("#/home/ufdb#", $dir)){return true;}
    if(preg_match("#/home/logrotate#", $dir)){return true;}
    if(preg_match("#/home/squid#", $dir)){return true;}
    if(preg_match("#/home/syslog#", $dir)){return true;}
    if(preg_match("#/home/unbound#", $dir)){return true;}
    if(preg_match("#/home/netdata#", $dir)){return true;}
    if(preg_match("#/home/logs-backup#", $dir)){return true;}
    if(preg_match("#/home/logrotate_backup#", $dir)){return true;}
    if(preg_match("#/home/ArticaStatsDB#", $dir)){return true;}
    if(preg_match("#/home/ArticaStats#", $dir)){return true;}
    if(preg_match("#/home/nginx#", $dir)){return true;}
    if(preg_match("#/home/artica#", $dir)){return true;}
    if(preg_match("#/home/[0-9]+$#",$dir)){return true;}
    if(preg_match("#/home/squid_admin#",$dir)){return true;}
    if(preg_match("#/home/letsencrypt#",$dir)){return true;}
    if(preg_match("#/media/cdrom0#", $dir)){return true;}
	if(preg_match("#/ArticaStats#", $dir)){return true;}
	if(preg_match("#/usr/share/artica-#", $dir)){return true;}
	if(preg_match("#/usr/share/mysql#i", $dir)){return true;}
	if(preg_match("#/usr/share/ssh#i", $dir)){return true;}
	if(preg_match("#/usr/share/squid#i", $dir)){return true;}
	if(preg_match("#/usr/share/python#i", $dir)){return true;}
    if(preg_match("#/usr/games#", $dir)){return true;}
    if(preg_match("#/usr/src#", $dir)){return true;}
    if(preg_match("#/usr/lib#", $dir)){return true;}
	if(preg_match("#/usr/bin#", $dir)){return true;}
	if(preg_match("#/usr/sbin#", $dir)){return true;}
	if(preg_match("#/usr/etc#", $dir)){return true;}
	if(preg_match("#/usr/include#", $dir)){return true;}
	if(preg_match("#/usr/local#", $dir)){return true;}
    if(preg_match("#/usr/share/debhelper#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-imap#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-readline#", $dir)){return true;}
    if(preg_match("#/usr/share/javascript#", $dir)){return true;}
    if(preg_match("#/usr/share/metainfo#", $dir)){return true;}
    if(preg_match("#/usr/share/adduser#", $dir)){return true;}
    if(preg_match("#/usr/share/perl5#", $dir)){return true;}
    if(preg_match("#/usr/share/console-setup#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-imap#", $dir)){return true;}
    if(preg_match("#/usr/share/aspell#", $dir)){return true;}
    if(preg_match("#/usr/share/pam-configs#", $dir)){return true;}
    if(preg_match("#/usr/share/libdbi-perl#", $dir)){return true;}
    if(preg_match("#/usr/share/intltool-debian#", $dir)){return true;}
    if(preg_match("#/usr/share/gtk-doc#", $dir)){return true;}
    if(preg_match("#/usr/share/autoconf#", $dir)){return true;}
    if(preg_match("#/usr/share/pam#", $dir)){return true;}
    if(preg_match("#/usr/share/mhonarc#", $dir)){return true;}
    if(preg_match("#/usr/share/proftpd#", $dir)){return true;}
    if(preg_match("#/usr/share/smartmontools#", $dir)){return true;}
    if(preg_match("#/usr/share/systemtap#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-xml#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-pgsql#", $dir)){return true;}
    if(preg_match("#/usr/share/consolefonts#", $dir)){return true;}
    if(preg_match("#/usr/share/debianutils#", $dir)){return true;}
    if(preg_match("#/usr/share/ntp#", $dir)){return true;}
    if(preg_match("#/usr/share/distro-info#", $dir)){return true;}
    if(preg_match("#/usr/share/binfmts#", $dir)){return true;}
    if(preg_match("#/usr/share/terminfo#", $dir)){return true;}
    if(preg_match("#/usr/share/xapian-core#", $dir)){return true;}
    if(preg_match("#/usr/share/quota#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-bcmath#", $dir)){return true;}
    if(preg_match("#/usr/share/update-ipsets#", $dir)){return true;}
    if(preg_match("#/usr/share/sensible-utils#", $dir)){return true;}
    if(preg_match("#/usr/share/antiword#", $dir)){return true;}
    if(preg_match("#/usr/share/xml-core#", $dir)){return true;}
    if(preg_match("#/usr/share/memcached#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-xmlrpc#", $dir)){return true;}
    if(preg_match("#/usr/share/perl#", $dir)){return true;}
    if(preg_match("#/usr/share/po-debconf#", $dir)){return true;}
    if(preg_match("#/usr/share/libpam-ldap#", $dir)){return true;}
    if(preg_match("#/usr/share/greensql-console#", $dir)){return true;}
    if(preg_match("#/usr/share/zsh#", $dir)){return true;}
    if(preg_match("#/usr/share/smokeping#", $dir)){return true;}
    if(preg_match("#/usr/share/et#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-zip#", $dir)){return true;}
    if(preg_match("#/usr/share/luajit-2.1.0-beta3#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-mbstring#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-json#", $dir)){return true;}
    if(preg_match("#/usr/share/apport#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-snmp#", $dir)){return true;}
    if(preg_match("#/usr/share/libdrm#", $dir)){return true;}
    if(preg_match("#/usr/share/themes#", $dir)){return true;}
    if(preg_match("#/usr/share/lintian#", $dir)){return true;}
    if(preg_match("#/usr/share/freeradius#", $dir)){return true;}
    if(preg_match("#/usr/share/ssl-cert#", $dir)){return true;}
    if(preg_match("#/usr/share/apt-file#", $dir)){return true;}
    if(preg_match("#/usr/share/ziproxy#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-pgsql#", $dir)){return true;}
    if(preg_match("#/usr/share/info#", $dir)){return true;}
    if(preg_match("#/usr/share/nano#", $dir)){return true;}
    if(preg_match("#/usr/share/libtool#", $dir)){return true;}
    if(preg_match("#/usr/share/dict#", $dir)){return true;}
    if(preg_match("#/usr/share/dstat#", $dir)){return true;}
    if(preg_match("#/usr/share/bandwidthd#", $dir)){return true;}
    if(preg_match("#/usr/share/RichFilemanager#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-common#", $dir)){return true;}
    if(preg_match("#/usr/share/awk#", $dir)){return true;}
    if(preg_match("#/usr/share/vim#", $dir)){return true;}
    if(preg_match("#/usr/share/grub#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-xml#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-mbstring#", $dir)){return true;}
    if(preg_match("#/usr/share/python-pycparser#", $dir)){return true;}
    if(preg_match("#/usr/share/menu#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-gd#", $dir)){return true;}
    if(preg_match("#/usr/share/discover#", $dir)){return true;}
    if(preg_match("#/usr/share/owfs#", $dir)){return true;}
    if(preg_match("#/usr/share/readline#", $dir)){return true;}
    if(preg_match("#/usr/share/pixmaps#", $dir)){return true;}
    if(preg_match("#/usr/share/initramfs-tools#", $dir)){return true;}
    if(preg_match("#/usr/share/locale#", $dir)){return true;}
    if(preg_match("#/usr/share/dpkg#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-ldap#", $dir)){return true;}
    if(preg_match("#/usr/share/phpipam#", $dir)){return true;}
    if(preg_match("#/usr/share/sysv-rc#", $dir)){return true;}
    if(preg_match("#/usr/share/polkit-1#", $dir)){return true;}
    if(preg_match("#/usr/share/aclocal-1.16#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-odbc#", $dir)){return true;}
    if(preg_match("#/usr/share/elasticsearch#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-json#", $dir)){return true;}
    if(preg_match("#/usr/share/ca-certificates#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-soap#", $dir)){return true;}
    if(preg_match("#/usr/share/libvirt#", $dir)){return true;}
    if(preg_match("#/usr/share/alsa#", $dir)){return true;}
    if(preg_match("#/usr/share/icu#", $dir)){return true;}
    if(preg_match("#/usr/share/catdoc#", $dir)){return true;}
    if(preg_match("#/usr/share/dns#", $dir)){return true;}
    if(preg_match("#/usr/share/python-apt#", $dir)){return true;}
    if(preg_match("#/usr/share/samba#", $dir)){return true;}
    if(preg_match("#/usr/share/emacs#", $dir)){return true;}
    if(preg_match("#/usr/share/kopano#", $dir)){return true;}
    if(preg_match("#/usr/share/X11#", $dir)){return true;}
    if(preg_match("#/usr/share/kibana#", $dir)){return true;}
    if(preg_match("#/usr/share/groff#", $dir)){return true;}
    if(preg_match("#/usr/share/sgml#", $dir)){return true;}
    if(preg_match("#/usr/share/filebeat#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-opcache#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-tidy#", $dir)){return true;}
    if(preg_match("#/usr/share/wsusoffline#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-zip#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-mysql#", $dir)){return true;}
    if(preg_match("#/usr/share/misc#", $dir)){return true;}
    if(preg_match("#/usr/share/apparmor-features#", $dir)){return true;}
    if(preg_match("#/usr/share/php#", $dir)){return true;}
    if(preg_match("#/usr/share/insserv#", $dir)){return true;}
    if(preg_match("#/usr/share/lua#", $dir)){return true;}
    if(preg_match("#/usr/share/krb5-kdc#", $dir)){return true;}
    if(preg_match("#/usr/share/squid3#", $dir)){return true;}
    if(preg_match("#/usr/share/iptables#", $dir)){return true;}
    if(preg_match("#/usr/share/sgml-base#", $dir)){return true;}
    if(preg_match("#/usr/share/artica-postfix#", $dir)){return true;}
    if(preg_match("#/usr/share/icons#", $dir)){return true;}
    if(preg_match("#/usr/share/netdata#", $dir)){return true;}
    if(preg_match("#/usr/share/shtool#", $dir)){return true;}
    if(preg_match("#/usr/share/mysql#", $dir)){return true;}
    if(preg_match("#/usr/share/debianbts#", $dir)){return true;}
    if(preg_match("#/usr/share/impacket#", $dir)){return true;}
    if(preg_match("#/usr/share/base-files#", $dir)){return true;}
    if(preg_match("#/usr/share/fontconfig#", $dir)){return true;}
    if(preg_match("#/usr/share/pyshared#", $dir)){return true;}
    if(preg_match("#/usr/share/conntrackd#", $dir)){return true;}
    if(preg_match("#/usr/share/suricata#", $dir)){return true;}
    if(preg_match("#/usr/share/file#", $dir)){return true;}
    if(preg_match("#/usr/share/slapd#", $dir)){return true;}
    if(preg_match("#/usr/share/dh-autoreconf#", $dir)){return true;}
    if(preg_match("#/usr/share/build-essential#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-pspell#", $dir)){return true;}
    if(preg_match("#/usr/share/dhcpd-pools#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-sqlite3#", $dir)){return true;}
    if(preg_match("#/usr/share/mime#", $dir)){return true;}
    if(preg_match("#/usr/share/python3#", $dir)){return true;}
    if(preg_match("#/usr/share/glances#", $dir)){return true;}
    if(preg_match("#/usr/share/i18n#", $dir)){return true;}
    if(preg_match("#/usr/share/dictionaries-common#", $dir)){return true;}
    if(preg_match("#/usr/share/bash-completion#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-mysql#", $dir)){return true;}
    if(preg_match("#/usr/share/debconf#", $dir)){return true;}
    if(preg_match("#/usr/share/bug#", $dir)){return true;}
    if(preg_match("#/usr/share/java#", $dir)){return true;}
    if(preg_match("#/usr/share/python-webdav#", $dir)){return true;}
    if(preg_match("#/usr/share/keyrings#", $dir)){return true;}
    if(preg_match("#/usr/share/nginx#", $dir)){return true;}
    if(preg_match("#/usr/share/davfs2#", $dir)){return true;}
    if(preg_match("#/usr/share/arp-scan#", $dir)){return true;}
    if(preg_match("#/usr/share/perl-openssl-defaults#", $dir)){return true;}
    if(preg_match("#/usr/share/kerberos-configs#", $dir)){return true;}
    if(preg_match("#/usr/share/munin#", $dir)){return true;}
    if(preg_match("#/usr/share/tabset#", $dir)){return true;}
    if(preg_match("#/usr/share/nmap#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-common#", $dir)){return true;}
    if(preg_match("#/usr/share/GConf#", $dir)){return true;}
    if(preg_match("#/usr/share/lua5.3#", $dir)){return true;}
    if(preg_match("#/usr/share/gdb#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-curl#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-sqlite3#", $dir)){return true;}
    if(preg_match("#/usr/share/freetds#", $dir)){return true;}
    if(preg_match("#/usr/share/python#", $dir)){return true;}
    if(preg_match("#/usr/share/fonts#", $dir)){return true;}
    if(preg_match("#/usr/share/common-licenses#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-gd#", $dir)){return true;}
    if(preg_match("#/usr/share/zoneinfo#", $dir)){return true;}
    if(preg_match("#/usr/share/aclocal#", $dir)){return true;}
    if(preg_match("#/usr/share/autofs#", $dir)){return true;}
    if(preg_match("#/usr/share/snmp#", $dir)){return true;}
    if(preg_match("#/usr/share/tools#", $dir)){return true;}
    if(preg_match("#/usr/share/openssh#", $dir)){return true;}
    if(preg_match("#/usr/share/glib-2.0#", $dir)){return true;}
    if(preg_match("#/usr/share/pkgconfig#", $dir)){return true;}
    if(preg_match("#/usr/share/collectd#", $dir)){return true;}
    if(preg_match("#/usr/share/offlineimap#", $dir)){return true;}
    if(preg_match("#/usr/share/calendar#", $dir)){return true;}
    if(preg_match("#/usr/share/lzo#", $dir)){return true;}
    if(preg_match("#/usr/share/spamassassin#", $dir)){return true;}
    if(preg_match("#/usr/share/dwagent#", $dir)){return true;}
    if(preg_match("#/usr/share/consoletrans#", $dir)){return true;}
    if(preg_match("#/usr/share/python-wheels#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-xmlrpc#", $dir)){return true;}
    if(preg_match("#/usr/share/mailgraph#", $dir)){return true;}
    if(preg_match("#/usr/share/automake-1.16#", $dir)){return true;}
    if(preg_match("#/usr/share/apps#", $dir)){return true;}
    if(preg_match("#/usr/share/needrestart#", $dir)){return true;}
    if(preg_match("#/usr/share/libnss-ldap#", $dir)){return true;}
    if(preg_match("#/usr/share/ucarp#", $dir)){return true;}
    if(preg_match("#/usr/share/postfix#", $dir)){return true;}
    if(preg_match("#/usr/share/lighttpd#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-ldap#", $dir)){return true;}
    if(preg_match("#/usr/share/ca-certificates-java#", $dir)){return true;}
    if(preg_match("#/usr/share/ppp#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-pspell#", $dir)){return true;}
    if(preg_match("#/usr/share/ieee-data#", $dir)){return true;}
    if(preg_match("#/usr/share/base-passwd#", $dir)){return true;}
    if(preg_match("#/usr/share/applnk#", $dir)){return true;}
    if(preg_match("#/usr/share/mysql-common#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-intl#", $dir)){return true;}
    if(preg_match("#/usr/share/tcltk#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.3-dba#", $dir)){return true;}
    if(preg_match("#/usr/share/tasksel#", $dir)){return true;}
    if(preg_match("#/usr/share/man#", $dir)){return true;}
    if(preg_match("#/usr/share/applications#", $dir)){return true;}
    if(preg_match("#/usr/share/enchant#", $dir)){return true;}
    if(preg_match("#/usr/share/defaults#", $dir)){return true;}
    if(preg_match("#/usr/share/gnupg#", $dir)){return true;}
    if(preg_match("#/usr/share/rubygems-integration#", $dir)){return true;}
    if(preg_match("#/usr/share/gcc-8#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-dba#", $dir)){return true;}
    if(preg_match("#/usr/share/kde4#", $dir)){return true;}
    if(preg_match("#/usr/share/emacsen-common#", $dir)){return true;}
    if(preg_match("#/usr/share/nDPI#", $dir)){return true;}
    if(preg_match("#/usr/share/libc-bin#", $dir)){return true;}
    if(preg_match("#/usr/share/docutils#", $dir)){return true;}
    if(preg_match("#/usr/share/namebench#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-opcache#", $dir)){return true;}
    if(preg_match("#/usr/share/dbus-1#", $dir)){return true;}
    if(preg_match("#/usr/share/httrack#", $dir)){return true;}
    if(preg_match("#/usr/share/man-db#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-bz2#", $dir)){return true;}
    if(preg_match("#/usr/share/GeoIP#", $dir)){return true;}
    if(preg_match("#/usr/share/gettext#", $dir)){return true;}
    if(preg_match("#/usr/share/php-composer#", $dir)){return true;}
    if(preg_match("#/usr/share/xml#", $dir)){return true;}
    if(preg_match("#/usr/share/cdbs#", $dir)){return true;}
    if(preg_match("#/usr/share/doc-base#", $dir)){return true;}
    if(preg_match("#/usr/share/libthai#", $dir)){return true;}
    if(preg_match("#/usr/share/systemd#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-snmp#", $dir)){return true;}
    if(preg_match("#/usr/share/php7.4-curl#", $dir)){return true;}
    if(preg_match("#/usr/share/thumbnailers#", $dir)){return true;}
    if(preg_match("#/usr/share/gettext-0.19.8#", $dir)){return true;}
    if(preg_match("#/usr/share/privoxy#", $dir)){return true;}
    if(preg_match("#usr/share/apt-listchanges#", $dir)){return true;}
    if(preg_match("#usr/share/avahi#", $dir)){return true;}
    if(preg_match("#usr/share/clamav#", $dir)){return true;}
    if(preg_match("#usr/share/bsd-mailx#", $dir)){return true;}
    if(preg_match("#var/lib/clamav#", $dir)){return true;}
    if(preg_match("#usr/share/cmake#", $dir)){return true;}
    if(preg_match("#usr/share/installation-report#", $dir)){return true;}
    if(preg_match("#usr/share/iso-codes#", $dir)){return true;}
    if(preg_match("#usr/share/keyutils#", $dir)){return true;}
    if(preg_match("#usr/share/nfs-common#", $dir)){return true;}
    if(preg_match("#usr/share/os-prober#", $dir)){return true;}
    if(preg_match("#usr/share/reportbug#", $dir)){return true;}
    if(preg_match("#usr/share/sysvinit#", $dir)){return true;}
    if(preg_match("#usr/share/transmission#", $dir)){return true;}
    if(preg_match("#usr/share/w3m#", $dir)){return true;}
    return false;
}

function BadDirs():array{
    $f[]="/root";
    $f[]="/home";
    $f[]="/lib";
    $f[]="/usr/share";
    $f[]="/usr/share/artica-postfix";
    $f[]="/usr/lib";
    $f[]="/usr/bin";
    $f[]="/usr/sbin";
    $f[]="/usr/local";
    $f[]="/etc";
    $f[]="/sbin";
    $f[]="/bin";
    $f[]="/media";
    $f[]="/mnt";
    $f[]="/automounts";
    $f[]="/tmp";
    return $f;

}


function lst($id, $with_root = true) {
	$dir = Getpath($id);
    VERBOSE("lst: dir=[$dir]",__LINE__);
	if(protected_dir($dir)){return;}
	$lst = @scandir($dir);
	if(!$lst) { return array();}
	$res = array();
	foreach($lst as $item) {
		if($item == '.' || $item == '..' || $item === null) { continue; }
        if(preg_match("#^\.#",$item)){continue;}
		$tmp = preg_match('([^ a-zа-я-_0-9.]+)ui', $item);
		if($tmp === false || $tmp === 1) { continue; }
		$directoryFinale=$dir . DIRECTORY_SEPARATOR . $item;
		if(protected_dir($directoryFinale)){continue;}
		if(!is_dir($dir . DIRECTORY_SEPARATOR . $item)) {continue;}
		if(trim($item)==null){continue;}
		$res[] = array('text' => $item, 'children' => true,  
				'id' => Getid($dir . DIRECTORY_SEPARATOR . $item), 'icon' => 'jstree-folder');
		
	}
	if($with_root && Getid($dir) === '/') {
		$res = array(array('text' => basename($GLOBALS["BASEPATH"]), 'children' => $res, 'id' => '/', 'icon'=>'jstree-folder', 
				'state' => array('opened' => true, 'disabled' => true)));
	}
	return $res;
}
function Getid($path) {
	$path = Getreal($path);
	$path = substr($path, strlen($GLOBALS["BASEPATH"]));
	$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
	$path = trim($path, '/');
	return strlen($path) ? $path : '/';
}
function Getpath($id) {

	$id = str_replace('/', DIRECTORY_SEPARATOR, $id);
	$id = trim($id, DIRECTORY_SEPARATOR);
    VERBOSE("Getpath: id:[$id] -- [{$GLOBALS["BASEPATH"]}]",__LINE__);
    $strtmp=str_replace($id,"",$GLOBALS["BASEPATH"]);
    if($strtmp=="/"){
        return Getreal($GLOBALS["BASEPATH"]);
    }
	$id = Getreal($GLOBALS["BASEPATH"] . DIRECTORY_SEPARATOR . $id);
	return $id;
}
function Getreal($path) {
    VERBOSE("Getreal($path)",__LINE__);
	$temp = realpath($path);
	if(!$temp) { return "/no-such-directory";}
	if($GLOBALS["BASEPATH"] && strlen($GLOBALS["BASEPATH"])) {
		if(strpos($temp, $GLOBALS["BASEPATH"]) !== 0) { throw new Exception('Path is not inside base ('.$GLOBALS["BASEPATH"].'): ' . $temp); }
	}
	return $temp;
}