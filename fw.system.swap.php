<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
//"{macro_remove_disk}","{macro_remove_disk_explain}"

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["stats"])){stats();exit;}
if(isset($_GET["table"])){swap_start();exit;}
if(isset($_GET["swap-table"])){swap_table();exit;}
if(isset($_POST["newswap"])){swap_save();exit;}
if(isset($_GET["swap-delete"])){swap_delete_ask();exit;}

if(isset($_GET["newswap-js"])){newswap_js();exit;}
if(isset($_GET["newswap-popup"])){newswap_popup();exit;}


if(isset($_POST["build"])){Build_save();exit;}
if(isset($_GET["build-after"])){Build_after();exit;}
if(isset($_POST["swap-delete"])){swap_delete_save();exit;}
if(isset($_GET["rescan-js"])){rescan_js();exit;}

page();

/**
 * Garde de privileges cote SERVEUR pour les points d'entree mutants.
 *
 * Masquer un bouton ne protege rien: le bloc de dispatch en tete de fichier
 * repond a toute session authentifiee, et ces handlers ecrivent (SET_INFO
 * DELETE_SWAP / CREATE_NEW_SWAP, SaveConfigFile MacroBuildPart,
 * hd.php?rescan-swap). Un simple POST swap-delete=<base64> supprimait un espace
 * d'echange depuis n'importe quel compte authentifie.
 *
 * $mode choisit la FORME de la reponse, qui doit correspondre a l'appelant:
 *  - "post" (defaut): reponse d'un form_outside / js_confirm_delete. Seul
 *    post_error() prefixe "jserror:", qui est ce qui declenche la popup; un echo
 *    brut atterrirait dans la banniere en tete de modale, hors ecran sur un
 *    formulaire long -- et un texte de moins de 4 caracteres serait meme compte
 *    comme un SUCCES par le JS genere.
 *  - "js": reponse d'un Loadjs, evaluee comme du JavaScript.
 *  - "bare": corps HTML d'une modale.
 */
function swap_require_admin($mode="post"):bool{
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	$tpl=new template_admin();
	if($mode=="js"){$tpl->js_no_privileges();return false;}
	if($mode=="bare"){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}
	echo $tpl->post_error("{ERROR_NO_PRIVS}");
	return false;
}

function newswap_js(){
	if(!swap_require_admin("js")){return;}
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog("{add_swap}", "$page?newswap-popup=yes");
}
function swap_delete_ask(){
	if(!swap_require_admin("js")){return;}
	$tpl=new template_admin();
	$page=CurrentPageName();
	$swapfile=$_GET["swap-delete"];
    $swapfileenc=base64_encode($swapfile);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.swap.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.swap.txt";
    $ARRAY["CMD"]="/system/swap/file/delete";
    $ARRAY["TITLE"]="{remove_swap}";
    $ARRAY["AFTER"]="LoadAjax('swap-part-table','$page?swap-table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $after="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-swap-restart')";
    $tpl->js_confirm_delete("{remove_swap} $swapfile","swap-delete",$swapfileenc,$after);
}

function swap_delete_save(){
    if(!swap_require_admin()){return;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    // On journalise le chemin DECODE, pas le base64 transporte: admin_tracks_post()
    // aurait ecrit la charge telle quelle, illisible dans le journal.
    admin_tracks("Remove swap area ".base64_decode($_POST["swap-delete"]));
    $sock=new sockets();
    $sock->SET_INFO("DELETE_SWAP",$_POST["swap-delete"]);
}

function rescan_js(){
    if(!swap_require_admin("js")){return;}
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    admin_tracks("Rescan swap areas");
    // Le tableau relit /proc/swaps a chaque affichage (/system/list-swap) : il
    // suffit de le recharger (hd.php?rescan-swap visait un --swap inexistant).
    echo "LoadAjax('swap-part-table','$page?swap-table=yes');";
}

function directory_monitor_graph_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$path=$_GET["directories-monitor-graph-js"];
	$pathenc=urlencode($path);
	$tpl->js_dialog1("{your_hard_disks} >> {directories_monitor} >> $path", "$page?directories-monitor-graph-popup=$pathenc");	
}
function directory_monitor_path_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$path=$_GET["directories-monitor-path-js"];
	$pathenc=urlencode($path);
	if($path==null){$title="{new_directory}";}else{$title=$path;}
	$tpl->js_dialog2("{your_hard_disks} >> {directories_monitor} >> $title", "$page?directories-monitor-path-popup=$pathenc");
}

function directory_monitor_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$html="<div id='directories-monitor-popup'></div>
	<script>
		LoadAjax('directories-monitor-popup','$page?directories-monitor-table=yes');
	</script>";
	echo $html;	
	
}


function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
    $EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    if($MUNIN_CLIENT_INSTALLED==0){
        $EnableMunin=0;
    }

    if($EnableMunin==0){
        swap_start();
        return true;
    }

	$array["{swap_label}"]="$page?table=yes";
    $array["{statistics}"]="$page?stats=yes";
	echo $tpl->tabs_default($array);
    return true;
	
}
function stats(){
	
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	
	$h[]="swap";

	$f[]="-day.png";
	$f[]="-week.png";
	$f[]="-month.png";
	$f[]="-year.png";
	
	foreach ($h as $them){
		foreach ($f as $suffix){
			$image="$them$suffix";
			if(is_file("$path/$image")){
				echo "<div class='center' style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t' alt=''></div>";
			}
		}
	}
}





function newswap_popup(){
	if(!swap_require_admin("bare")){return;}
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.swap.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.swap.txt";
    $ARRAY["CMD"]="/system/swap/file/create";
    $ARRAY["TITLE"]="{creating_swap}";
    $ARRAY["AFTER"]="LoadAjax('swap-part-table','$page?swap-table=yes');BootstrapDialog1.close();";
    $prgress=base64_encode(serialize($ARRAY));
    $after="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=creating-new-swap')";

	$form[]=$tpl->field_hidden("newswap", "yes");
	$form[]=$tpl->field_browse_directory("path", "{path}", "/home/swaps");
	$form[]=$tpl->field_numeric("size","{size} (MB)",1024);
	$html="<div id='creating-new-swap'></div>".
        $tpl->form_outside("{add_swap}", @implode("\n", $form),null,"{add}",$after,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}

function swap_save(){
    if(!swap_require_admin()){return;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    // Apres CLEAN_POST: le journal montre les valeurs REELLEMENT enregistrees
    // (chemin, taille), pas celles recues avant assainissement.
    admin_tracks_post("Create a new swap file");
    $DATA=base64_encode(serialize($_POST));
    $sock=new sockets();
    $sock->SET_INFO("CREATE_NEW_SWAP",$DATA);

}

function Build_after(){
	if(!swap_require_admin("js")){return;}
	header("content-type: application/x-javascript");
	$MAIN=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacroBuildPart"));
	$page=CurrentPageName();
	
	if($MAIN["dev"]==null){echo "alert('No disk defined!');";return;}
	// Journalise apres la sortie anticipee: une macro sans disque ne lance rien,
	// et une trace pour une operation qui n'a pas eu lieu est pire que pas de trace.
	admin_tracks("Build partitions on {$MAIN["dev"]}");
	$nofstab=null;
	$dev=urlencode($MAIN["dev"]);
	$label=urlencode($MAIN["label"]);
	$fs_type=urlencode($MAIN["fs_type"]);
	if(isset($MAIN["nofstab"])){$nofstab="&nofstab=yes";}
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.partition.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.partition.txt";
	$ARRAY["CMD"]="cmd.php?fdisk-build-big-partitions=yes&dev=$dev&label=$label&fs_type=$fs_type$nofstab";
	$ARRAY["TITLE"]="{macro_build_bigpart}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-harddisks','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$rescan_hd="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-hd-restart')";
	
	echo "BootstrapDialog1.close()\n";
	echo $rescan_hd."\n";
	
	
}

function Build_save(){
	if(!swap_require_admin()){return;}
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks_post("Save the partition build macro (MacroBuildPart)");
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($_POST), "MacroBuildPart");
	
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{swap_label}",ico_hd,"{swap_label_explain}","$page?tabs=yes","system-swap","progress-swap-restart",false,"table-loader-swap");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
	
}
function swap_start(){

    $page=CurrentPageName();
    $html="<div id='swap-part-table'></div><script>LoadAjax('swap-part-table','$page?swap-table=yes');</script>";
    echo $html;
}


/**
 * Feuille de style de la carte centrale (maquette
 * stitch_swap_partition_manager_optimization, section "Swap Partitions Table").
 *
 * Emise UNE fois par requete, et TOUJOURS concatenee EN DEHORS de
 * _ENGINE_parse_body(): son regex #{(.+?)}# lit chaque corps de regle CSS comme
 * un {token} et declencherait un acces memcached par regle.
 */
function swap_css():string{
	if(isset($GLOBALS["SWAPCARD_CSS_DONE"])){return "";}
	$GLOBALS["SWAPCARD_CSS_DONE"]=true;
	return "<style>
.swpc{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.05);margin-top:10px}
.swpc *{box-sizing:border-box}
.swpc-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:16px 24px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.swpc-h1{margin:0;font-size:13px;font-weight:600;color:#1e293b}
.swpc-h2{margin:2px 0 0 0;font-size:11.5px;color:#64748b}
.swpc-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;color:#334155;font-size:11.5px;font-weight:500;text-decoration:none;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.swpc-btn:hover{background:#f1f5f9;color:#0f172a;text-decoration:none}
.swpc-scroll{overflow-x:auto}
.swpc-tbl{width:100%;border-collapse:collapse;text-align:left;font-size:12px}
.swpc-tbl thead tr{background:#f1f5f9;border-bottom:1px solid #e2e8f0}
.swpc-tbl thead th{padding:12px 16px;font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#475569;white-space:nowrap}
.swpc-tbl thead th.swpc-f{padding-left:24px}
.swpc-tbl thead th.swpc-l{padding-right:24px;text-align:right}
.swpc-tbl tbody tr{border-top:1px solid rgba(226,232,240,.8)}
.swpc-tbl tbody tr:hover{background:#f8fafc}
.swpc-tbl tbody td{padding:16px;vertical-align:middle}
.swpc-tbl tbody td.swpc-f{padding-left:24px}
.swpc-tbl tbody td.swpc-l{padding-right:24px;text-align:right}
.swpc-id{display:flex;align-items:center;gap:12px}
.swpc-ico{flex:0 0 auto;width:34px;height:34px;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0;display:inline-flex;align-items:center;justify-content:center;font-size:15px}
.swpc-name{display:block;font-family:monospace;font-weight:700;font-size:12px;color:#0f172a}
.swpc-meta{display:block;margin-top:2px;font-family:monospace;font-size:11px;color:#94a3b8}
.swpc-meta-warn{color:#b45309}
.swpc-tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500;white-space:nowrap}
.swpc-tag-part{background:#f1f5f9;color:#334155;border:1px solid #e2e8f0}
.swpc-tag-file{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}
.swpc-prio{font-family:monospace;font-weight:500;color:#475569}
.swpc-size{font-weight:600;color:#0f172a;white-space:nowrap}
.swpc-use{min-width:230px}
.swpc-use-l{display:flex;justify-content:space-between;gap:12px;font-size:11px;font-family:monospace;margin-bottom:4px}
.swpc-use-r{color:#64748b}
.swpc-bar{width:100%;height:12px;border-radius:6px;background:#e2e8f0;padding:2px;overflow:hidden}
.swpc-bar span{display:block;height:100%;border-radius:4px}
.swpc-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
.swpc-dot{width:6px;height:6px;border-radius:50%;background:#10b981}
.swpc-acts{display:inline-flex;align-items:center;gap:4px}
.swpc-act{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;color:#94a3b8;text-decoration:none}
.swpc-act:hover{background:#f1f5f9;color:#334155;text-decoration:none}
.swpc-act-del:hover{background:#fff1f2;color:#e11d48}
.swpc-none{color:#cbd5e1}
.swpc-empty{padding:28px 24px;text-align:center;color:#64748b;font-size:13px}
.swpc-foot{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:14px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:11.5px;color:#64748b}
.swpc-foot b{color:#334155;font-weight:600}
.swpc-mono{font-family:monospace;background:rgba(226,232,240,.6);color:#334155;padding:1px 5px;border-radius:4px}
@media(max-width:900px){.swpc-use{min-width:150px}}
</style>";
}

/**
 * Sources de swap declarees dans /etc/fstab, resolues en chemin reel.
 *
 * fstab designe une partition par UUID=/LABEL= aussi souvent que par /dev/sdX,
 * alors que /proc/swaps ne connait que le chemin resolu: sans cette resolution
 * une partition parfaitement persistante serait affichee comme volatile.
 * Retourne un tableau VIDE si le fichier n'est pas lisible — l'appelant doit
 * alors n'afficher AUCUNE mention de persistance plutot qu'une fausse.
 */
function swap_fstab_sources():array{
	$out=array();
	if(!@is_readable("/etc/fstab")){return $out;}
	$lines=@file("/etc/fstab",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	if(!is_array($lines)){return $out;}
	foreach($lines as $line){
		$line=trim($line);
		if($line==""){continue;}
		if(substr($line,0,1)=="#"){continue;}
		$f=preg_split('#\s+#',$line);
		if(!isset($f[2])){continue;}
		if(strtolower($f[2])<>"swap"){continue;}
		$src=trim($f[0],"\"");
		if(preg_match('#^UUID=(.+)$#i',$src,$re)){$src="/dev/disk/by-uuid/".trim($re[1],"\"");}
		if(preg_match('#^LABEL=(.+)$#i',$src,$re)){$src="/dev/disk/by-label/".trim($re[1],"\"");}
		$real=@realpath($src);
		if($real<>false && $real<>""){$src=$real;}
		$out[$src]=true;
	}
	return $out;
}

/** Teinte de la jauge d'utilisation. Un swap plein est un incident, pas une couleur de plus. */
function swap_usage_tone($prc):array{
	if($prc>=90){return array("#b91c1c","linear-gradient(90deg,#ef4444,#dc2626)");}
	if($prc>=70){return array("#b45309","linear-gradient(90deg,#f59e0b,#d97706)");}
	return array("#047857","linear-gradient(90deg,#10b981,#14b8a6)");
}

/** Cellule d'identite: pastille d'icone, chemin, et la ligne secondaire de persistance. */
function swap_row_identity($dev,$type,$persistent):string{
	$glyph="fas fa-hard-drive";
	$color="#059669";
	if($type=="file"){$glyph="fas fa-file-code";$color="#0d9488";}
	$meta="";
	// $persistent vaut null quand /etc/fstab n'a pas pu etre lu: on se tait
	// plutot que d'annoncer une volatilite qu'on n'a pas constatee.
	if($persistent===true){$meta="<span class='swpc-meta'>{persistent}</span>";}
	if($persistent===false){$meta="<span class='swpc-meta swpc-meta-warn'>{swap_not_in_fstab}</span>";}
	$h ="<div class='swpc-id'>";
	$h.="<span class='swpc-ico'><i class=\"$glyph\" style=\"color:$color\"></i></span>";
	$h.="<span><span class='swpc-name'>".htmlspecialchars($dev)."</span>$meta</span>";
	$h.="</div>";
	return $h;
}

/** Badge de type: partition noyau ou fichier d'echange. */
function swap_row_type($type):string{
	if($type=="file"){return "<span class='swpc-tag swpc-tag-file'>{swap_file}</span>";}
	return "<span class='swpc-tag swpc-tag-part'>{partition} Linux</span>";
}

/** Jauge d'utilisation: pourcentage a gauche, volume utilise a droite, barre dessous. */
function swap_row_usage($used,$size):string{
	// /proc/swaps peut annoncer une taille nulle sur un volume en cours de
	// desactivation: la division d'origine levait alors une Division by zero.
	$prc=0;
	if($size>0){$prc=($used/$size)*100;}
	if($prc<0){$prc=0;}
	if($prc>100){$prc=100;}
	list($fg,$bg)=swap_usage_tone($prc);
	$h ="<div class='swpc-use'>";
	$h.="<div class='swpc-use-l'>";
	$h.="<span style=\"color:$fg;font-weight:600\">".sprintf("%.2f",$prc)."%</span>";
	$h.="<span class='swpc-use-r'>".FormatBytes($used,true)." {used}</span>";
	$h.="</div>";
	$h.="<div class='swpc-bar'><span style=\"width:".sprintf("%.2f",$prc)."%;background:$bg\"></span></div>";
	$h.="</div>";
	return $h;
}

/** Pastille d'etat. /proc/swaps ne liste que les espaces actifs: tout ce qui est la est en ligne. */
function swap_row_status():string{
	return "<span class='swpc-pill'><span class='swpc-dot'></span>{online}</span>";
}

/**
 * Actions de la ligne. La maquette en montre trois (details, suspendre,
 * supprimer); seule la suppression d'un FICHIER de swap existe cote daemon
 * (hd.php?remove-swap), donc c'est la seule rendue: un bouton qui ne fait rien
 * est pire que pas de bouton.
 */
function swap_row_actions($dev,$type,$page,$canadmin):string{
	if($type<>"file" || !$canadmin){return "<span class='swpc-none'>&mdash;</span>";}
	$devenc=urlencode($dev);
	return "<span class='swpc-acts'>"
		."<a href=\"javascript:blur();\" class='swpc-act swpc-act-del' title='{remove_swap}' "
		."OnClick=\"Loadjs('$page?swap-delete=$devenc')\"><i class=\"fas fa-trash-can\"></i></a>"
		."</span>";
}

/** Une ligne du tableau. */
function swap_row($e,$fstab,$fstabread,$page,$canadmin):string{
	$dev=(string)$e->name;
	$type=(string)$e->type;
	$size=floatval($e->size);
	$used=floatval($e->used);
	$prio=property_exists($e,"prio")?intval($e->prio):0;

	$persistent=null;
	if($fstabread){
		$real=@realpath($dev);
		if($real==false || $real==""){$real=$dev;}
		$persistent=(isset($fstab[$dev]) || isset($fstab[$real]));
	}

	$h ="<tr>";
	$h.="<td class='swpc-f'>".swap_row_identity($dev,$type,$persistent)."</td>";
	$h.="<td>".swap_row_type($type)."</td>";
	$h.="<td class='swpc-prio'>$prio</td>";
	$h.="<td class='swpc-size'>".FormatBytes($size,true)."</td>";
	$h.="<td>".swap_row_usage($used,$size)."</td>";
	$h.="<td>".swap_row_status()."</td>";
	$h.="<td class='swpc-l'>".swap_row_actions($dev,$type,$page,$canadmin)."</td>";
	$h.="</tr>";
	return $h;
}

/** En-tete de la carte: titre, sous-titre et le bouton de rafraichissement de la maquette. */
function swap_card_head($page):string{
	$h ="<div class='swpc-head'>";
	$h.="<div><div class='swpc-h1'>{swap_volumes_and_partitions}</div>";
	$h.="<div class='swpc-h2'>{swap_volumes_explain}</div></div>";
	$h.="<div><a href=\"javascript:blur();\" class='swpc-btn' "
		."OnClick=\"LoadAjax('swap-part-table','$page?swap-table=yes')\">"
		."<i class=\"fas fa-rotate-right\"></i>{refresh}</a></div>";
	$h.="</div>";
	return $h;
}

/** Pied de carte: le compte a gauche, l'utilisation cumulee a droite. */
function swap_card_foot($count,$fstabcount,$fstabread,$used,$size):string{
	$prc=0;
	if($size>0){$prc=($used/$size)*100;}
	// Le token partage {total} est stocke en minuscules ("total"); le corriger
	// dans la base toucherait toutes les pages qui l'utilisent, donc on le
	// capitalise ici, sur ce seul mot (cf. CLAUDE.md, tokens partages).
	$left="<span style='text-transform:capitalize'>{total}</span>: <b>$count</b>";
	if($fstabread){
		$left.=" &middot; <b>$fstabcount</b> {swap_declared_in} <span class='swpc-mono'>/etc/fstab</span>";
	}
	$right="{combined_usage}: <b>".FormatBytes($used,true)." / ".FormatBytes($size,true)
		." (".sprintf("%.2f",$prc)."%)</b>";
	return "<div class='swpc-foot'><span>$left</span><span>$right</span></div>";
}

function swap_table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	$topbuttons=array();
	$feature_disabled="";

	$DisableSystemSwap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSystemSwap"));

	if($users->AsSystemAdministrator) {
		if($DisableSystemSwap==0) {
			$topbuttons[] = array("Loadjs('$page?newswap-js=yes')", ico_plus, "{add_swap}");
		}

		$EmptySwapJS=$tpl->framework_buildjs("/system/clean-swap",
			"swap-clean.progress",
			"swap-clean.progress.txt",
			"progress-swap-restart",
			"LoadAjax('swap-part-table','$page?swap-table=yes')"

		);
		$DisableSwapJS=$tpl->framework_buildjs("/system/disable-swap",
			"swap-clean.progress",
			"swap-clean.progress.txt",
			"progress-swap-restart",
			"LoadAjax('swap-part-table','$page?swap-table=yes')"

		);

		$EnableSwapJS=$tpl->framework_buildjs("/system/enable-swap",
			"swap-clean.progress",
			"swap-clean.progress.txt",
			"progress-swap-restart",
			"LoadAjax('swap-part-table','$page?swap-table=yes')"

		);

		if($DisableSystemSwap==0){
			$topbuttons[] = array($EmptySwapJS, ico_trash, "{empty_swap}");
			$topbuttons[] = array($DisableSwapJS, ico_stop, "{disable}: SWAP");
		}else{
			$feature_disabled="&nbsp;<span class='label label-danger'>{feature_disabled}</span>";
			$topbuttons[] = array($EnableSwapJS, ico_run, "{enable}: SWAP");
		}

	}

	$data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/list-swap"));
	if(!is_object($data) || !$data->Status){
		echo $tpl->div_error(is_object($data)?$data->Error:"{no_data}");
		return false;
	}
	if(!property_exists($data,"swaps")){
		echo $tpl->div_error("{no_data}");
		return false;
	}
	$SWAP_PARTITIONS=$data->swaps;
	if(!is_array($SWAP_PARTITIONS)){$SWAP_PARTITIONS=array();}

	$fstabread=@is_readable("/etc/fstab");
	$fstab=swap_fstab_sources();
	$canadmin=$users->AsSystemAdministrator;

	$rows=array();
	$TOTAL_SIZE=0;
	$TOTAL_USED=0;
	foreach ($SWAP_PARTITIONS as $e){
		$TOTAL_SIZE=$TOTAL_SIZE+floatval($e->size);
		$TOTAL_USED=$TOTAL_USED+floatval($e->used);
		$rows[]=swap_row($e,$fstab,$fstabread,$page,$canadmin);
	}

	$html=array();
	$html[]="<div class='swpc'>";
	$html[]=swap_card_head($page);
	$html[]="<div class='swpc-scroll'>";
	$html[]="<table class='swpc-tbl'>";
	$html[]="<thead><tr>";
	$html[]="<th class='swpc-f'>{path} / {device}</th>";
	$html[]="<th>{type}</th>";
	$html[]="<th>{priority}</th>";
	$html[]="<th>{size}</th>";
	$html[]="<th>{usage}</th>";
	$html[]="<th>{status}</th>";
	$html[]="<th class='swpc-l'>{actions}</th>";
	$html[]="</tr></thead>";
	$html[]="<tbody>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='7'><div class='swpc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table>";
	$html[]="</div>";
	$html[]=swap_card_foot(count($rows),count($fstab),$fstabread,$TOTAL_USED,$TOTAL_SIZE);
	$html[]="</div>";

	$EnableDockerService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerService"));
	if($EnableDockerService==1){
		$topbuttons=array();
	}
	$TINY_ARRAY=array();
	$TINY_ARRAY["TITLE"]="{swap_label}$feature_disabled";
	$TINY_ARRAY["ICO"]=ico_hd;
	$TINY_ARRAY["EXPL"]="{swap_label_explain}";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// Le <script> et la CSS sont concatenes AUTOUR du parseur, jamais dedans.
	$script="<script>$jstiny\nNoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
	echo swap_css().$tpl->_ENGINE_parse_body(@implode("\n", $html)).$script;
	return true;

}



function directory_monitor_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$page=CurrentPageName();
	$q=new mysql();
	$directory=$tpl->_ENGINE_parse_body("{directory}");
	$partition=$tpl->_ENGINE_parse_body("{partition}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$new_directory=$tpl->_ENGINE_parse_body("{new_directory}");
	$used=$tpl->javascript_parse_text("{used}");
	$hard_drive=$tpl->javascript_parse_text("{disk}");
	$free=$tpl->javascript_parse_text("{free}");
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.dirmon.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.dirmon.progress.txt";
	$ARRAY["CMD"]="system.php?folders-monitors-progress=yes";
	$ARRAY["TITLE"]="{directories_monitor}::{rescan}";
	$ARRAY["AFTER"]="LoadAjax('directories-monitor-popup','$page?directories-monitor-table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	
	
	$rescan_hd="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-directories-monitor-restart')";
	
	
	$html[]=$tpl->_ENGINE_parse_body("
			<div id='progress-directories-monitor-restart' style='margin-bottom:10px'></div>
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"javascript:$rescan_hd;\"><i class='fas fa-check'></i> {launch_scan} </label>
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?directories-monitor-path-js=');\"><i class='fas fa-check'></i> {new_directory} </label>
			</div>");
	
	$html[]="<table id='table-directories-monitor' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$directory}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$partition}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$hard_drive</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$used</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$free</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$results=$q->QUERY_SQL("SELECT * FROM philesight ORDER BY FREEMB","artica_backup");
	$TRCLASS=null;
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$color="black";
		$icon="&nbsp;";
		$distance=null;
		$directory=$ligne["directory"];
		$md5=md5($directory);
		$partition=$ligne["partition"];
		$hd=$ligne["hd"];
		$maxtime=$ligne["maxtime"];
		$lastscan=intval($ligne["lastscan"]);
		$USED=$ligne["USED"];
		$FREEMB=intval($ligne["FREEMB"]);
		if($lastscan>0){
			$lastscan=date("Y-m-d H:i:s",$lastscan);
		}
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		$directoryenc=urlencode($directory);
		$jslink="Loadjs('$page?directory-js=$directoryenc');";
	
		
		if(intval($ligne["lastscan"])>0){
			$distance=$tpl->_ENGINE_parse_body(distanceOfTimeInWords($ligne["lastscan"],time(),true));
			
			if(is_file("/usr/share/artica-postfix/img/philesight/$md5.png")){
				$icon=$tpl->icon_pie("Loadjs('$page?directories-monitor-graph-js=$directoryenc')");
			}
			
			$distance="<br><i>$distance</i>";
		}
		
		$md=md5($directory);
		$delete=$tpl->icon_delete("Loadjs('$page?directories-monitor-delete=$directoryenc&md=$md')","AsSystemAdministrator");
		$text_class=null;
		if($partition==null){$partition=$tpl->icon_nothing();}
		if($hd==null){$hd=$tpl->icon_nothing();}
		if(intval($USED)==0){$USED=$tpl->icon_nothing();}else{$USED="{$USED}%";}
		if(intval($FREEMB)==0){$FREEMB=$tpl->icon_nothing();}else{$FREEMB=FormatBytes($FREEMB*1024);}
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td class=\"$text_class\">".$tpl->td_href($lastscan,"{click_to_edit}",$jslink)."$distance</td>";
		$html[]="<td>".$tpl->td_href($directory,"{click_to_edit}",$jslink)."</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>{$partition}</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>{$hd}</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>{$USED}</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>{$FREEMB}</td>";
		$html[]="<td class=\"center\" style='width:1%' nowrap>$icon</td>";
		$html[]="<td class=\"center\" style='width:1%' nowrap>$delete</td>";
		

	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-directories-monitor').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function ParseHDline($dev,$array){
	$sock=new sockets();

	$WatchDog=unserialize($sock->GET_INFO("HardDisksWatchDog"));


	$ID_MODEL=null;
	$ID_VENDOR=null;
	$ID_FS_LABEL_TEXT=null;

	if(isset($array["ID_MODEL_ENC"])){
		$ID_MODEL=$array["ID_MODEL_ENC"];
			
	}

	if($ID_MODEL==null){
		if(isset($array["ID_MODEL"])){
			$ID_MODEL=$array["ID_MODEL"];
		}
	}

	if($ID_MODEL==null){
		if(isset($array["ID_MODEL_1"])){
			$ID_MODEL=$array["ID_MODEL_1"];
		}
	}

	if($ID_MODEL==null){
		if(isset($array["ID_MODEL_2"])){
			$ID_MODEL=$array["ID_MODEL_2"];
		}
	}

	if($ID_MODEL<>null){$MAIN["MODEL"]=$ID_MODEL;}



	$SIZE=$array["SIZE"];
	$ID_BUS=$array["ID_BUS"];
	if(isset($array["ID_VENDOR"])){$ID_VENDOR=$array["ID_VENDOR"];}
	
	
	$ID_USB_DRIVER=$array["ID_USB_DRIVER"];
	if(isset($array["ID_SERIAL_SHORT"])){
		$ID_SERIAL_SHORT=$array["ID_SERIAL_SHORT"];
	}
	$part_number=count($array["PARTITIONS"]);
	if(trim($ID_VENDOR)<>null){$MAIN["VENDOR"]=$ID_VENDOR;}
	$link="PartInfos('$dev')";
	$title[]="<div style='font-size:40px;margin-bottom:15px'><a href=\"javascript:blur();\" OnClick=\"$link\" style='text-decoration:underline'>$dev ($SIZE)</a></div>";
	$title[]="<div style='font-size:18px'>$ID_VENDOR$ID_MODEL</div>";
	if(isset($array["ID_REVISION"])){$MAIN["VERSION"]=$array["ID_REVISION"];}

	$title[]="<div style='font-size:18px'>{path}: $dev {partitions_number}: $part_number</div>";
	if($part_number>0){
		$watchdog_text="{disabled}";
		if(isset($WatchDog[$dev])){
			$watchdog_text="{enabled}";
		}
			
		$title[]="<div style='font-size:18px;font-weight:bold'><a href=\"javascript:blur();\"
			OnClick=\"Loadjs('system.internal.disks.watchdog.php?dev=". urlencode($dev)."')\"
			style='text-decoration:underline'>
			{watchdog}: $watchdog_text</a></div>";
	}

	
	return $MAIN;


}