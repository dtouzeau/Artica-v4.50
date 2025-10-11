<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["NORESOLVCOMP"]=true;

if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--shared"){folders();exit();}



function build(){
	
$f[]="# Netatalk configuration";
$f[]="# Change this to increase the maximum number of clients that can connect:";
$f[]="AFPD_MAX_CLIENTS=20";
$f[]="";
$f[]="# Change this to set the machine's atalk name and zone.";
$f[]="# NOTE: if your zone has spaces in it, you're better off specifying";
$f[]="#       it in afpd.conf";
$f[]="#ATALK_ZONE=@zone";
$f[]="ATALK_NAME=`/bin/hostname --short`";
$f[]="";
$f[]="# specify the Mac and unix charsets to be used";
$f[]="ATALK_MAC_CHARSET='MAC_ROMAN'";
$f[]="ATALK_UNIX_CHARSET='LOCALE'";
$f[]="";
$f[]="# specify the UAMs to enable";
$f[]="# available options: uams_guest.so, uams_clrtxt.so, uams_randnum.so, ";
$f[]="# 		             uams_dhx.so, uams_dhx2.so";
$f[]="# AFPD_UAMLIST=\"-U uams_dhx.so,uams_dhx2.so\"";
$f[]="";
$f[]="# Change this to set the id of the guest user";
$f[]="AFPD_GUEST=nobody";
$f[]="";
$f[]="# Set which daemons to run.";
$f[]="# If you need legacy AppleTalk, run atalkd.";
$f[]="# papd, timelord and a2boot are dependent upon atalkd.";
$f[]="# If you use \"AFP over TCP\" server only, run only cnid_metad and afpd.";
$f[]="ATALKD_RUN=no";
$f[]="PAPD_RUN=no";
$f[]="TIMELORD_RUN=no";
$f[]="A2BOOT_RUN=no";
$f[]="CNID_METAD_RUN=yes";
$f[]="AFPD_RUN=yes";
$f[]="";
$f[]="# Control whether the daemons are started in the background.";
$f[]="# If it is dissatisfied that atalkd starts slowly, set \"yes\".";
$f[]="ATALK_BGROUND=no";
$f[]="";
$f[]="# export the charsets, read form ENV by apps";
$f[]="export ATALK_MAC_CHARSET";
$f[]="export ATALK_UNIX_CHARSET";
$f[]="";
$f[]="# config for cnid_metad. Default log config:";
$f[]="# CNID_CONFIG=\"-l log_note\"";	
@file_put_contents("/etc/default/netatalk", @implode("\n", $f));
echo "Starting......: ".date("H:i:s")." Netatalk /etc/default/netatalk done\n";
$f=array();

$f[]="- -transall -uamlist uams_dhx.so,uams_dhx2.so -nosavepassword -advertise_ssh";
@file_put_contents("/etc/netatalk/afpd.conf", @implode("\n", $f));
echo "Starting......: ".date("H:i:s")." Netatalk /etc/netatalk/afpd.conf done\n";
AppleVolumes_system();
avahi_services();
folders();
	
}



function avahi_services(){
	$users=new usersMenus();
	$servername=$users->hostname;
	if(!is_dir("/etc/avahi/services")){echo "Starting......: ".date("H:i:s")." Netatalk /etc/avahi/services no such directory\n";return;}
	$f[]="<?xml version=\"1.0\" standalone='no'?><!--*-nxml-*-->";
	$f[]="<!DOCTYPE service-group SYSTEM \"avahi-service.dtd\"> ";
	$f[]="<service-group>";
	$f[]="	<name replace-wildcards=\"yes\">%h $servername</name>";
	$f[]="	<service>";
	$f[]="		<type>_afpovertcp._tcp</type>";
	$f[]="		<port>548</port>";
	$f[]="	</service>";
	$f[]="	<service>";
	$f[]="		<type>_device-info._tcp</type>";
	$f[]="		<port>0</port>";
	$f[]="		<txt-record>model=Xserve</txt-record>";
	$f[]="	</service>";
	$f[]="</service-group>";
	$f[]="";
	@file_put_contents("/etc/avahi/services/afpd.service", @implode("\n", $f));	
	echo "Starting......: ".date("H:i:s")." Netatalk /etc/avahi/services/afpd.service done\n";
	if(is_file("/etc/init.d/avahi-daemon")){
		echo "Starting......: ".date("H:i:s")." Netatalk restarting avahi-daemon\n";
		system("/usr/sbin/artica-phpfpm-service -nsswitch");
		shell_exec("/etc/init.d/avahi-daemon restart");
	}
	
}


function AppleVolumes_system(){
$f[]=".1st      \"TEXT\"  \"ttxt\"      Text Readme                    SimpleText                application/text";
$f[]=".669      \"6669\"  \"SNPL\"      669 MOD Music                  PlayerPro";
$f[]=".8med     \"STrk\"  \"SCPL\"      Amiga OctaMed music            SoundApp";
$f[]=".8svx     \"8SVX\"  \"SCPL\"      Amiga 8-bit sound              SoundApp";
$f[]=".a        \"TEXT\"  \"ttxt\"      Assembly Source                SimpleText";
$f[]=".aif      \"AIFF\"  \"SCPL\"      AIFF Sound                     SoundApp                  audio/x-aiff";
$f[]=".aifc     \"AIFC\"  \"SCPL\"      AIFF Sound Compressed          SoundApp                  audio/x-aiff";
$f[]=".aiff     \"AIFF\"  \"SCPL\"      AIFF Sound                     SoundApp                  audio/x-aiff";
$f[]=".al       \"ALAW\"  \"SCPL\"      ALAW Sound                     SoundApp";
$f[]=".ani      \"ANIi\"  \"GKON\"      Animated NeoChrome             GraphicConverter";
$f[]=".apd      \"TEXT\"  \"ALD3\"      Aldus Printer Description      Aldus PageMaker";
$f[]=".arc      \"mArc\"  \"SITx\"      PC ARChive                     StuffIt Expander";
$f[]=".arj      \"BINA\"  \"DArj\"      ARJ Archive                    DeArj";
$f[]=".arr      \"ARR \"  \"GKON\"      Amber ARR image                GraphicConverter";
$f[]=".art      \"ART \"  \"GKON\"      First Publisher                GraphicConverter";
$f[]=".asc      \"TEXT\"  \"ttxt\"      ASCII Text                     SimpleText                text/plain";
$f[]=".ascii    \"TEXT\"  \"ttxt\"      ASCII Text                     SimpleText                text/plain";
$f[]=".asf      \"ASF_\"  \"Ms01\"      Netshow Player                 Netshow Server            video/x-ms-asf";
$f[]=".asm      \"TEXT\"  \"ttxt\"      Assembly Source                SimpleText";
$f[]=".asx      \"ASX_\"  \"Ms01\"      Netshow Player                 Netshow Server            video/x-ms-asf";
$f[]=".au       \"ULAW\"  \"TVOD\"      Sun Sound                      QuickTime Player          audio/basic";
$f[]=".avi      \"VfW \"  \"TVOD\"      AVI Movie                      QuickTime Player          video/avi";
$f[]=".bar      \"BARF\"  \"S691\"      Unix BAR Archive               SunTar";
$f[]=".bas      \"TEXT\"  \"ttxt\"      BASIC Source                   SimpleText";
$f[]=".bat      \"TEXT\"  \"ttxt\"      MS-DOS Batch File              SimpleText";
$f[]=".bga      \"BMPp\"  \"ogle\"      OS/2 Bitmap                    PictureViewer";
$f[]=".bib      \"TEXT\"  \"ttxt\"      BibTex Bibliography            SimpleText";
$f[]=".bin      \"SIT!\"  \"SITx\"      MacBinary                      StuffIt Expander          application/macbinary";
$f[]=".binary   \"BINA\"  \"hDmp\"      Untyped Binary Data            HexEdit                   application/octet-stream";
$f[]=".bmp      \"BMPp\"  \"ogle\"      Windows Bitmap                 PictureViewer";
$f[]=".boo      \"TEXT\"  \"ttxt\"      BOO encoded                    SimpleText";
$f[]=".bst      \"TEXT\"  \"ttxt\"      BibTex Style                   SimpleText";
$f[]=".bw       \"SGI \"  \"GKON\"      SGI Image                      GraphicConverter";
$f[]=".c        \"TEXT\"  \"CWIE\"      C Source                       CodeWarrior";
$f[]=".cgm      \"CGMm\"  \"GKON\"      Computer Graphics Meta         GraphicConverter";
$f[]=".class    \"Clss\"  \"CWIE\"      Java Class File                CodeWarrior";
$f[]=".clp      \"CLPp\"  \"GKON\"      Windows Clipboard              GraphicConverter";
$f[]=".cmd      \"TEXT\"  \"ttxt\"      OS/2 Batch File                SimpleText";
$f[]=".com      \"PCFA\"  \"SWIN\"      MS-DOS Executable              SoftWindows";
$f[]=".cp       \"TEXT\"  \"CWIE\"      C++ Source                     CodeWarrior";
$f[]=".cpp      \"TEXT\"  \"CWIE\"      C++ Source                     CodeWarrior";
$f[]=".cpt      \"PACT\"  \"SITx\"      Compact Pro Archive            StuffIt Expander";
$f[]=".csv      \"TEXT\"  \"XCEL\"      Comma Separated Vars           Excel";
$f[]=".ct       \"..CT\"  \"GKON\"      Scitex-CT                      GraphicConverter";
$f[]=".cut      \"Halo\"  \"GKON\"      Dr Halo Image                  GraphicConverter";
$f[]=".cvs      \"drw2\"  \"DAD2\"      Canvas Drawing                 Canvas";
$f[]=".dbf      \"COMP\"  \"FOX+\"      DBase Document                 FoxBase+";
$f[]=".dcx      \"DCXx\"  \"GKON\"      Some PCX Images                GraphicConverter";
$f[]=".dif      \"TEXT\"  \"XCEL\"      Data Interchange Format        Excel";
$f[]=".diz      \"TEXT\"  \"R*Ch\"      BBS Descriptive Text           BBEdit";
$f[]=".dl       \"DL  \"  \"AnVw\"      DL Animation                   MacAnim Viewer";
$f[]=".dll      \"PCFL\"  \"SWIN\"      Windows DLL                    SoftWindows";
$f[]=".doc      \"WDBN\"  \"MSWD\"      Word Document                  Microsoft Word            application/msword";
$f[]=".dot      \"sDBN\"  \"MSWD\"      Word for Windows Template      Microsoft Word";
$f[]=".dvi      \"ODVI\"  \"xdvi\"      TeX DVI Document               xdvi                      application/x-dvi";
$f[]=".dwt      \"TEXT\"  \"DmWr\"      Dreamweaver Template           Dreamweaver";
$f[]=".dxf      \"TEXT\"  \"SWVL\"      AutoCAD 3D Data                Swivel Pro";
$f[]=".eps      \"EPSF\"  \"vgrd\"      Postscript                     LaserWriter 8             application/postscript";
$f[]=".epsf     \"EPSF\"  \"vgrd\"      Postscript                     LaserWriter 8             application/postscript";
$f[]=".etx      \"TEXT\"  \"ezVu\"      SEText                         Easy View                 text/x-setext";
$f[]=".evy      \"EVYD\"  \"ENVY\"      Envoy Document                 Envoy";
$f[]=".exe      \"PCFA\"  \"SWIN\"      MS-DOS Executable              SoftWindows";
$f[]=".faq      \"TEXT\"  \"ttxt\"      ASCII Text                     SimpleText                text/x-usenet-faq";
$f[]=".fit      \"FITS\"  \"GKON\"      Flexible Image Transport       GraphicConverter          image/x-fits";
$f[]=".flc      \"FLI \"  \"TVOD\"      FLIC Animation                 QuickTime Player";
$f[]=".fli      \"FLI \"  \"TVOD\"      FLI Animation                  QuickTime Player";
$f[]=".fm       \"FMPR\"  \"FMPR\"      FileMaker Pro Database         FileMaker Pro";
$f[]=".for      \"TEXT\"  \"MPS \"      Fortran Source                 MPW Shell";
$f[]=".fts      \"FITS\"  \"GKON\"      Flexible Image Transport       GraphicConverter";
$f[]=".gem      \"GEM-\"  \"GKON\"      GEM Metafile                   GraphicConverter";
$f[]=".gif      \"GIFf\"  \"ogle\"      GIF Picture                    PictureViewer             image/gif";
$f[]=".gl       \"GL  \"  \"AnVw\"      GL Animation                   MacAnim Viewer";
$f[]=".grp      \"GRPp\"  \"GKON\"      GRP Image                      GraphicConverter";
$f[]=".gz       \"SIT!\"  \"SITx\"      Gnu ZIP Archive                StuffIt Expander          application/x-gzip";
$f[]=".h        \"TEXT\"  \"CWIE\"      C Include File                 CodeWarrior";
$f[]=".hcom     \"FSSD\"  \"SCPL\"      SoundEdit Sound ex SOX         SoundApp";
$f[]=".hp       \"TEXT\"  \"CWIE\"      C Include File                 CodeWarrior";
$f[]=".hpgl     \"HPGL\"  \"GKON\"      HP GL/2                        GraphicConverter";
$f[]=".hpp      \"TEXT\"  \"CWIE\"      C Include File                 CodeWarrior";
$f[]=".hqx      \"TEXT\"  \"SITx\"      BinHex                         StuffIt Expander          application/mac-binhex40";
$f[]=".htm      \"TEXT\"  \"MOSS\"      HyperText                      Netscape Communicator     text/html";
$f[]=".html     \"TEXT\"  \"MOSS\"      HyperText                      Netscape Communicator     text/html";
$f[]=".i3       \"TEXT\"  \"R*ch\"      Modula 3 Interface             BBEdit";
$f[]=".ic1      \"IMAG\"  \"GKON\"      Atari Image                    GraphicConverter";
$f[]=".ic2      \"IMAG\"  \"GKON\"      Atari Image                    GraphicConverter";
$f[]=".ic3      \"IMAG\"  \"GKON\"      Atari Image                    GraphicConverter";
$f[]=".icn      \"ICO \"  \"GKON\"      Windows Icon                   GraphicConverter";
$f[]=".ico      \"ICO \"  \"GKON\"      Windows Icon                   GraphicConverter";
$f[]=".ief      \"IEF \"  \"GKON\"      IEF image                      GraphicConverter          image/ief";
$f[]=".iff      \"ILBM\"  \"GKON\"      Amiga IFF Image                GraphicConverter";
$f[]=".ilbm     \"ILBM\"  \"GKON\"      Amiga ILBM Image               GraphicConverter";
$f[]=".image    \"dImg\"  \"ddsk\"      Apple DiskCopy Image           Disk Copy";
$f[]=".img      \"IMGg\"  \"GKON\"      GEM bit image/XIMG             GraphicConverter";
$f[]=".ini      \"TEXT\"  \"ttxt\"      Windows INI File               SimpleText";
$f[]=".java     \"TEXT\"  \"CWIE\"      Java Source File               CodeWarrior";
$f[]=".jfif     \"JPEG\"  \"ogle\"      JFIF Image                     PictureViewer";
$f[]=".jpe      \"JPEG\"  \"ogle\"      JPEG Picture                   PictureViewer             image/jpeg";
$f[]=".jpeg     \"JPEG\"  \"ogle\"      JPEG Picture                   PictureViewer             image/jpeg";
$f[]=".jpg      \"JPEG\"  \"ogle\"      JPEG Picture                   PictureViewer             image/jpeg";
$f[]=".latex    \"TEXT\"  \"OTEX\"      Latex                          OzTex                     application/x-latex";
$f[]=".lbm      \"ILBM\"  \"GKON\"      Amiga IFF Image                GraphicConverter";
$f[]=".lha      \"LHA \"  \"SITx\"      LHArc Archive                  StuffIt Expander";
$f[]=".lzh      \"LHA \"  \"SITx\"      LHArc Archive                  StuffIt Expander";
$f[]=".m1a      \"MPEG\"  \"TVOD\"      MPEG-1 audiostream             MoviePlayer               audio/x-mpeg";
$f[]=".m1s      \"MPEG\"  \"TVOD\"      MPEG-1 systemstream            MoviePlayer";
$f[]=".m1v      \"M1V \"  \"TVOD\"      MPEG-1 IPB videostream         MoviePlayer               video/mpeg";
$f[]=".m2       \"TEXT\"  \"R*ch\"      Modula 2 Source                BBEdit";
$f[]=".m2v      \"MPG2\"  \"MPG2\"      MPEG-2 IPB videostream         MPEG2decoder";
$f[]=".m3       \"TEXT\"  \"R*ch\"      Modula 3 Source                BBEdit";
$f[]=".mac      \"PICT\"  \"ogle\"      PICT Picture                   PictureViewer             image/x-pict";
$f[]=".mak      \"TEXT\"  \"R*ch\"      Makefile                       BBEdit";
$f[]=".mcw      \"WDBN\"  \"MSWD\"      Mac Word Document              Microsoft Word";
$f[]=".me       \"TEXT\"  \"ttxt\"      Text Readme                    SimpleText";
$f[]=".med      \"STrk\"  \"SCPL\"      Amiga MED Sound                SoundApp";
$f[]=".mf       \"TEXT\"  \"*MF*\"      Metafont                       Metafont";
$f[]=".mid      \"Midi\"  \"TVOD\"      MIDI Music                     MoviePlayer";
$f[]=".midi     \"Midi\"  \"TVOD\"      MIDI Music                     MoviePlayer";
$f[]=".mif      \"TEXT\"  \"Fram\"      FrameMaker MIF                 FrameMaker                application/x-framemaker";
$f[]=".mime     \"TEXT\"  \"SITx\"      MIME Message                   StuffIt Expander          message/rfc822";
$f[]=".ml       \"TEXT\"  \"R*ch\"      ML Source                      BBEdit";
$f[]=".mod      \"STrk\"  \"SCPL\"      MOD Music                      SoundApp";
$f[]=".mol      \"TEXT\"  \"RSML\"      MDL Molfile                    RasMac";
$f[]=".moov     \"MooV\"  \"TVOD\"      QuickTime Movie                MoviePlayer               video/quicktime";
$f[]=".mov      \"MooV\"  \"TVOD\"      QuickTime Movie                MoviePlayer               video/quicktime";
$f[]=".mp2      \"MPEG\"  \"TVOD\"      MPEG-1 audiostream             MoviePlayer               audio/x-mpeg";
$f[]=".mp3      \"MPG3\"  \"TVOD\"      MPEG-3 audiostream             MoviePlayer               audio/x-mpeg";
$f[]=".mpa      \"MPEG\"  \"TVOD\"      MPEG-1 audiostream             MoviePlayer               audio/x-mpeg";
$f[]=".mpe      \"MPEG\"  \"TVOD\"      MPEG Movie of some sort        MoviePlayer               video/mpeg";
$f[]=".mpeg     \"MPEG\"  \"TVOD\"      MPEG Movie of some sort        MoviePlayer               video/mpeg";
$f[]=".mpg      \"MPEG\"  \"TVOD\"      MPEG Movie of some sort        MoviePlayer               video/mpeg";
$f[]=".msp      \"MSPp\"  \"GKON\"      Microsoft Paint                GraphicConverter";
$f[]=".mtm      \"MTM \"  \"SNPL\"      MultiMOD Music                 PlayerPro";
$f[]=".mw       \"MW2D\"  \"MWII\"      MacWrite Document              MacWrite II               application/macwriteii";
$f[]=".mwii     \"MW2D\"  \"MWII\"      MacWrite Document              MacWrite II               application/macwriteii";
$f[]=".neo      \"NeoC\"  \"GKON\"      Atari NeoChrome                GraphicConverter";
$f[]=".nfo      \"TEXT\"  \"ttxt\"      Info Text                      SimpleText                application/text";
$f[]=".nst      \"STrk\"  \"SCPL\"      MOD Music                      SoundApp";
$f[]=".obj      \"PCFL\"  \"SWIN\"      Object (DOS/Windows)           SoftWindows";
$f[]=".oda      \"ODIF\"  \"ODA \"      ODA Document                   MacODA XTND Translator    application/oda";
$f[]=".okt      \"OKTA\"  \"SCPL\"      Oktalyser MOD Music            SoundApp";
$f[]=".out      \"BINA\"  \"hDmp\"      Output File                    HexEdit";
$f[]=".ovl      \"PCFL\"  \"SWIN\"      Overlay (DOS/Windows)          SoftWindows";
$f[]=".p        \"TEXT\"  \"CWIE\"      Pascal Source                  CodeWarrior";
$f[]=".pac      \"STAD\"  \"GKON\"      Atari STAD Image               GraphicConverter";
$f[]=".pas      \"TEXT\"  \"CWIE\"      Pascal Source                  CodeWarrior";
$f[]=".pbm      \"PPGM\"  \"GKON\"      Portable Bitmap                GraphicConverter          image/x-portable-bitmap";
$f[]=".pc1      \"Dega\"  \"GKON\"      Atari Degas Image              GraphicConverter";
$f[]=".pc2      \"Dega\"  \"GKON\"      Atari Degas Image              GraphicConverter";
$f[]=".pc3      \"Dega\"  \"GKON\"      Atari Degas Image              GraphicConverter";
$f[]=".pcs      \"PICS\"  \"GKON\"      Animated PICTs                 GraphicConverter";
$f[]=".pct      \"PICT\"  \"ogle\"      PICT Picture                   PictureViewer             image/x-pict";
$f[]=".pcx      \"PCXx\"  \"GKON\"      PC PaintBrush                  GraphicConverter";
$f[]=".pdb      \"TEXT\"  \"RSML\"      Brookhaven PDB file            RasMac";
$f[]=".pdf      \"PDF \"  \"CARO\"      Portable Document Format       Acrobat Reader            application/pdf";
$f[]=".pdx      \"TEXT\"  \"ALD5\"      Printer Description            PageMaker";
$f[]=".pf       \"CSIT\"  \"SITx\"      Private File                   StuffIt Expander ";
$f[]=".pgm      \"PPGM\"  \"GKON\"      Portable Graymap               GraphicConverter          image/x-portable-graymap";
$f[]=".pi1      \"Dega\"  \"GKON\"      Atari Degas Image              GraphicConverter";
$f[]=".pi2      \"Dega\"  \"GKON\"      Atari Degas Image              GraphicConverter";
$f[]=".pi3      \"Dega\"  \"GKON\"      Atari Degas Image              GraphicConverter";
$f[]=".pic      \"PICT\"  \"ogle\"      PICT Picture                   PictureViewer             image/x-pict";
$f[]=".pict     \"PICT\"  \"ogle\"      PICT Picture                   PictureViewer             image/x-macpict";
$f[]=".pit      \"PIT \"  \"SITx\"      PackIt Archive                 StuffIt Expander";
$f[]=".pkg      \"HBSF\"  \"SITx\"      AppleLink Package              StuffIt Expander";
$f[]=".pl       \"TEXT\"  \"McPL\"      Perl Source                    MacPerl";
$f[]=".plt      \"HPGL\"  \"GKON\"      HP GL/2                        GraphicConverter";
$f[]=".pm       \"PMpm\"  \"GKON\"      Bitmap from xv                 GraphicConverter";
$f[]=".pm3      \"ALB3\"  \"ALD3\"      PageMaker 3 Document           PageMaker";
$f[]=".pm4      \"ALB4\"  \"ALD4\"      PageMaker 4 Document           PageMaker";
$f[]=".pm5      \"ALB5\"  \"ALD5\"      PageMaker 5 Document           PageMaker";
$f[]=".png      \"PNG \"  \"ogle\"      Portable Network Graphic       PictureViewer";
$f[]=".pntg     \"PNTG\"  \"ogle\"      Macintosh Painting             PictureViewer";
$f[]=".ppd      \"TEXT\"  \"ALD5\"      Printer Description            PageMaker";
$f[]=".ppm      \"PPGM\"  \"GKON\"      Portable Pixmap                GraphicConverter          image/x-portable-pixmap";
$f[]=".prn      \"TEXT\"  \"R*ch\"      Printer Output File            BBEdit";
$f[]=".ps       \"TEXT\"  \"vgrd\"      PostScript                     LaserWriter 8             application/postscript";
$f[]=".psd      \"8BPS\"  \"8BIM\"      PhotoShop Document             Photoshop";
$f[]=".pt4      \"ALT4\"  \"ALD4\"      PageMaker 4 Template           PageMaker";
$f[]=".pt5      \"ALT5\"  \"ALD5\"      PageMaker 5 Template           PageMaker";
$f[]=".pxr      \"PXR \"  \"8BIM\"      Pixar Image                    Photoshop";
$f[]=".qdv      \"QDVf\"  \"GKON\"      QDV image                      GraphicConverter";
$f[]=".qt       \"MooV\"  \"TVOD\"      QuickTime Movie                MoviePlayer               video/quicktime";
$f[]=".qxd      \"XDOC\"  \"XPR3\"      QuarkXpress Document           QuarkXpress";
$f[]=".qxt      \"XTMP\"  \"XPR3\"      QuarkXpress Template           QuarkXpress";
$f[]=".raw      \"BINA\"  \"GKON\"      Raw Image                      GraphicConverter";
$f[]=".readme   \"TEXT\"  \"ttxt\"      Text Readme                    SimpleText                application/text";
$f[]=".rgb      \"SGI \"  \"GKON\"      SGI Image                      GraphicConverter          image/x-rgb";
$f[]=".rgba     \"SGI \"  \"GKON\"      SGI Image                      GraphicConverter          image/x-rgb";
$f[]=".rib      \"TEXT\"  \"RINI\"      Renderman 3D Data              Renderman";
$f[]=".rif      \"RIFF\"  \"GKON\"      RIFF Graphic                   GraphicConverter";
$f[]=".rle      \"RLE \"  \"GKON\"      RLE image                      GraphicConverter";
$f[]=".rme      \"TEXT\"  \"ttxt\"      Text Readme                    SimpleText";
$f[]=".rpl      \"FRL!\"  \"REP!\"      Replica Document               Replica";
$f[]=".rsc      \"rsrc\"  \"RSED\"      Resource File                  ResEdit";
$f[]=".rsrc     \"rsrc\"  \"RSED\"      Resource File                  ResEdit";
$f[]=".rtf      \"TEXT\"  \"MSWD\"      Rich Text Format               Microsoft Word            application/rtf";
$f[]=".rtx      \"TEXT\"  \"R*ch\"      Rich Text                      BBEdit                    text/richtext";
$f[]=".s3m      \"S3M \"  \"SNPL\"      ScreamTracker 3 MOD            PlayerPro";
$f[]=".scc      \"MSX \"  \"GKON\"      MSX pitcure                    GraphicConverter";
$f[]=".scg      \"RIX3\"  \"GKON\"      ColoRIX                        GraphicConverter";
$f[]=".sci      \"RIX3\"  \"GKON\"      ColoRIX                        GraphicConverter";
$f[]=".scp      \"RIX3\"  \"GKON\"      ColoRIX                        GraphicConverter";
$f[]=".scr      \"RIX3\"  \"GKON\"      ColoRIX                        GraphicConverter";
$f[]=".scu      \"RIX3\"  \"GKON\"      ColoRIX                        GraphicConverter";
$f[]=".sea      \"APPL\"  \"????\"      Self-Extracting Archive        Self Extracting Archive";
$f[]=".sf       \"IRCM\"  \"SDHK\"      IRCAM Sound                    SoundHack";
$f[]=".sgi      \".SGI\"  \"ogle\"      SGI Image                      PictureViewer";
$f[]=".sha      \"TEXT\"  \"UnSh\"      Unix Shell Archive             UnShar                    application/x-shar";
$f[]=".shar     \"TEXT\"  \"UnSh\"      Unix Shell Archive             UnShar                    application/x-shar";
$f[]=".shp      \"SHPp\"  \"GKON\"      Printmaster Icon Library       GraphicConverter";
$f[]=".sit      \"SIT!\"  \"SITx\"      StuffIt 1.5.1 Archive          StuffIt Expander          application/x-stuffit";
$f[]=".sithqx   \"TEXT\"  \"SITx\"      BinHexed StuffIt Archive       StuffIt Expander          application/mac-binhex40";
$f[]=".six      \"SIXE\"  \"GKON\"      SIXEL image                    GraphicConverter";
$f[]=".slk      \"TEXT\"  \"XCEL\"      SYLK Spreadsheet               Excel";
$f[]=".snd      \"BINA\"  \"SCPL\"      Sound of various types         SoundApp";
$f[]=".spc      \"Spec\"  \"GKON\"      Atari Spectrum 512             GraphicConverter";
$f[]=".sr       \"SUNn\"  \"GKON\"      Sun Raster Image               GraphicConverter";
$f[]=".sty      \"TEXT\"  \"*TEX\"      TeX Style                      Textures";
$f[]=".sun      \"SUNn\"  \"GKON\"      Sun Raster Image               GraphicConverter";
$f[]=".sup      \"SCRN\"  \"GKON\"      StartupScreen                  GraphicConverter";
$f[]=".svx      \"8SVX\"  \"SCPL\"      Amiga IFF Sound                SoundApp";
$f[]=".syk      \"TEXT\"  \"XCEL\"      SYLK Spreadsheet               Excel";
$f[]=".sylk     \"TEXT\"  \"XCEL\"      SYLK Spreadsheet               Excel";
$f[]=".tar      \"TARF\"  \"SITx\"      Unix Tape ARchive              StuffIt Expander          application/x-tar";
$f[]=".targa    \"TPIC\"  \"GKON\"      Truevision Image               GraphicConverter";
$f[]=".taz      \"ZIVU\"  \"SITx\"      Compressed Tape ARchive        StuffIt Expander          application/x-compress";
$f[]=".tex      \"TEXT\"  \"OTEX\"      TeX Document                   OzTeX                     application/x-tex";
$f[]=".texi     \"TEXT\"  \"OTEX\"      TeX Document                   OzTeX";
$f[]=".texinfo  \"TEXT\"  \"OTEX\"      TeX Document                   OzTeX                     application/x-texinfo";
$f[]=".text     \"TEXT\"  \"ttxt\"      ASCII Text                     SimpleText                text/plain";
$f[]=".tga      \"TPIC\"  \"GKON\"      Truevision Image               GraphicConverter";
$f[]=".tgz      \"Gzip\"  \"SITx\"      Gnu ZIPed Tape ARchive         StuffIt Expander          application/x-gzip";
$f[]=".tif      \"TIFF\"  \"ogle\"      TIFF Picture                   PictureViewer             image/tiff";
$f[]=".tiff     \"TIFF\"  \"ogle\"      TIFF Picture                   PictureViewer             image/tiff";
$f[]=".tny      \"TINY\"  \"GKON\"      Atari TINY Bitmap              GraphicConverter";
$f[]=".tsv      \"TEXT\"  \"XCEL\"      Tab Separated Values           Excel                     text/tab-separated-values";
$f[]=".tx8      \"TEXT\"  \"ttxt\"      8-bit ASCII Text               SimpleText";
$f[]=".txt      \"TEXT\"  \"ttxt\"      ASCII Text                     SimpleText                text/plain";
$f[]=".ul       \"ULAW\"  \"TVOD\"      Mu-Law Sound                   MoviePlayer               audio/basic";
$f[]=".url      \"AURL\"  \"Arch\"      URL Bookmark                   Anarchie                  message/external-body";
$f[]=".uu       \"TEXT\"  \"SITx\"      UUEncode                       StuffIt Expander";
$f[]=".uue      \"TEXT\"  \"SITx\"      UUEncode                       StuffIt Expander";
$f[]=".vff      \"VFFf\"  \"GKON\"      DESR VFF Greyscale Image       GraphicConverter";
$f[]=".vga      \"BMPp\"  \"ogle\"      OS/2 Bitmap                    PictureViewer";
$f[]=".voc      \"VOC \"  \"SCPL\"      VOC Sound                      SoundApp";
$f[]=".w51      \".WP5\"  \"WPC2\"      WordPerfect PC 5.1 Doc         WordPerfect               application/wordperfect5.1";
$f[]=".wav      \"WAVE\"  \"TVOD\"      Windows WAV Sound              MoviePlayer               audio/x-wav";
$f[]=".wk1      \"XLBN\"  \"XCEL\"      Lotus Spreadsheet r2.1         Excel";
$f[]=".wks      \"XLBN\"  \"XCEL\"      Lotus Spreadsheet r1.x         Excel";
$f[]=".wmf      \"WMF \"  \"GKON\"      Windows Metafile               GraphicConverter";
$f[]=".wp       \".WP5\"  \"WPC2\"      WordPerfect PC 5.1 Doc         WordPerfect               application/wordperfect5.1";
$f[]=".wp4      \".WP4\"  \"WPC2\"      WordPerfect PC 4.2 Doc         WordPerfect";
$f[]=".wp5      \".WP5\"  \"WPC2\"      WordPerfect PC 5.x Doc         WordPerfect               application/wordperfect5.1";
$f[]=".wp6      \".WP6\"  \"WPC2\"      WordPerfect PC 6.x Doc         WordPerfect";
$f[]=".wpg      \"WPGf\"  \"GKON\"      WordPerfect Graphic            GraphicConverter";
$f[]=".wpm      \"WPD1\"  \"WPC2\"      WordPerfect Mac                WordPerfect";
$f[]=".wri      \"WDBN\"  \"MSWD\"      MS Write/Windows               Microsoft Word";
$f[]=".wve      \"BINA\"  \"SCPL\"      PSION sound                    SoundApp";
$f[]=".x10      \"XWDd\"  \"GKON\"      X-Windows Dump                 GraphicConverter          image/x-xwd";
$f[]=".x11      \"XWDd\"  \"GKON\"      X-Windows Dump                 GraphicConverter          image/x-xwd";
$f[]=".xbm      \"XBM \"  \"GKON\"      X-Windows Bitmap               GraphicConverter          image/x-xbm";
$f[]=".xl       \"XLS \"  \"XCEL\"      Excel Spreadsheet              Excel";
$f[]=".xlc      \"XLC \"  \"XCEL\"      Excel Chart                    Excel";
$f[]=".xlm      \"XLM \"  \"XCEL\"      Excel Macro                    Excel";
$f[]=".xls      \"XLS \"  \"XCEL\"      Excel Spreadsheet              Excel";
$f[]=".xlw      \"XLW \"  \"XCEL\"      Excel Workspace                Excel";
$f[]=".xm       \"XM  \"  \"SNPL\"      FastTracker MOD Music          PlayerPro";
$f[]=".xpm      \"XPM \"  \"GKON\"      X-Windows Pixmap               GraphicConverter          image/x-xpm";
$f[]=".xwd      \"XWDd\"  \"GKON\"      X-Windows Dump                 GraphicConverter          image/x-xwd";
$f[]=".Z        \"ZIVU\"  \"SITx\"      Unix Compress Archive          StuffIt Expander          application/x-compress";
$f[]=".zip      \"ZIP \"  \"SITx\"      PC ZIP Archive                 StuffIt Expander          application/zip";
$f[]=".zoo      \"Zoo \"  \"Booz\"      Zoo Archive                    MacBooz";
$f[]=".bld  \"BLD \"  \"GKON\"  BLD                            GraphicConverter";
$f[]=".bum  \".bMp\"  \"GKON\"  QuickTime Importer(QuickDraw)  GraphicConverter";
$f[]=".cel  \"CEL \"  \"GKON\"  KISS CEL                       GraphicConverter";
$f[]=".cur  \"CUR \"  \"GKON\"  Windows Cursor                 GraphicConverter";
$f[]=".cwj  \"CWSS\"  \"cwkj\"  ClarisWorks Document           ClarisWorks 4.0";
$f[]=".dat  \"TCLl\"  \"GKON\"  TCL image                      GraphicConverter";
$f[]=".hr   \"TR80\"  \"GKON\"  TSR-80 HR                      GraphicConverter";
$f[]=".iss  \"ISS \"  \"GKON\"  ISS                            GraphicConverter";
$f[]=".jif  \"JIFf\"  \"GKON\"  JIF99a                         GraphicConverter";
$f[]=".lwf  \"lwfF\"  \"GKON\"  LuraWave(LWF)                  GraphicConverter";
$f[]=".mbm  \"MBM \"  \"GKON\"  PSION 5(MBM)                   GraphicConverter";
$f[]=".ngg  \"NGGC\"  \"GKON\"  Mobile Phone(Nokia)Format      GraphicConverter";
$f[]=".nol  \"NOL \"  \"GKON\"  Mobile Phone(Nokia)Format      GraphicConverter";
$f[]=".pal  \"8BCT\"  \"8BIM\"  Color Table                    GraphicConverter";
$f[]=".pgc  \"PGCF\"  \"GKON\"  PGC/PGF  Atari Portfolio PCG   GraphicConverter";
$f[]=".pics \"PICS\"  \"GKON\"  PICS-PICT Sequence             GraphicConverter";
$f[]=".swf  \"SWFL\"  \"SWF2\"  Flash                          Macromedia Flash";
$f[]=".vpb  \"VPB \"  \"GKON\"  VPB QUANTEL                    GraphicConverter";
$f[]=".wbmp \"WBMP\"  \"GKON\"  WBMP                           GraphicConverter";
$f[]=".x-face  \"TEXT\"  \"GKON\"  X-Face                      GraphicConverter";
$f[]=".fla  \"SPA \"  \"MFL2\"  Flash source                   Macromedia Flash";
@file_put_contents("/etc/netatalk/AppleVolumes.system", @implode("\n", $f));
echo "Starting......: ".date("H:i:s")." Netatalk /etc/netatalk/AppleVolumes.system done\n";	

}


function folders(){
	$q=new mysql();
	
	$f[]="~/\t\"Home Directory\"";
	$results=$q->QUERY_SQL("SELECT * FROM netatalk ORDER BY sharedname","artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";return;}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$dir=$ligne["directory"];
		$sharename=$ligne["sharedname"];
		$datas=unserialize(base64_decode($ligne["allow"]));
		$allowed_hosts=array();
		$allow=array();
		$allow_text=null;
		$allowed_hosts_text=null;
		$Already=array();
		$ro=null;
		if($ligne["readonly"]==1){$ro=",ro";}
		
		if(is_array($datas)){
		while (list ($uid, $ligne) = each ($datas) ){
				if($uid==null){continue;}
				$member=null;
				
				if($GLOBALS["VERBOSE"]){echo "Check $uid\n";}
				if(isset($Already[$uid])){continue;}
				
				if(strpos($uid,"$")>0){
					$cp=new computers($uid);
					if($GLOBALS["VERBOSE"]){echo "Computer $uid =>$cp->ComputerIP\n";}
					$allowed_hosts[]=$cp->ComputerIP;
					$Already[$uid]=true;	
					continue;
				}
				if(substr($uid,0,1)=="@"){$allow[]=$uid;$Already[$uid]=true;continue;}
				if($member==null){$allow[]=$uid;}
				$Already[$uid]=true;		
			}
		}
		if(count($allow)>0){$allow_text="allow:".@implode(",", $allow);}
		if(count($allowed_hosts)>0){$allowed_hosts_text=" allowed_hosts:".@implode(",", $allowed_hosts);}
		
		$f[]="$dir\t\"$sharename\" $allow_text options:usedots$ro,upriv,tm$allowed_hosts_text";
		
	}
	 $f[]="";
	 @file_put_contents("/etc/netatalk/AppleVolumes.default", @implode("\n", $f));
	 echo "Starting......: ".date("H:i:s")." Netatalk /etc/netatalk/AppleVolumes.default done\n";	
	
}
