<?php
include_once(dirname(__FILE__)."/class.request.filter.inc"); // md/function* : identifiants seulement (pentest 2026-11-04)
include_once(dirname(__FILE__)."/settings.exists.inc"); // artica_setting_exists(): settings are root-only, see the file
// Service Pack 9 - SECURITY (reflected XSS, central mitigation): $_GET["function"] is the search_block refresh-function
// name and is interpolated RAW into executable JS across ~200 fw.*.php pages — table_buttons emits it
// into OnClick="…", and form_outside/js_confirm_delete/js_prompt emit it into $jsafter, all unescaped.
// search_block only ever generates ss<time>, so the legitimate value is always [A-Za-z0-9_]. This file
// is included by every page that renders those sinks, so whitelisting the param here (before any page
// reads it) neutralizes ?function=0;alert()// for the whole console without touching each page.
if(isset($_GET["function"])){$_GET["function"]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);}
if(!defined("PROGRESS_DIR")){define("PROGRESS_DIR","/usr/share/artica-postfix/ressources/logs/web");}
ini_set('log_errors','On');ini_set('display_errors','Off');ini_set('error_reporting', E_ALL );
if(!isset($GLOBALS["FOOTABLE_PSIZE"])){$GLOBALS["FOOTABLE_PSIZE"]=100;}
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('html_errors',0);$GLOBALS["VERBOSE"]=true;}
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
if(!isset($GLOBALS["DEBUG_INCLUDES"])){$GLOBALS["DEBUG_INCLUDES"]=false;}
// Surcouche de marque white-label. Fichier NEUF : garde is_file(), sinon une
// appliance ou il n'est pas encore deploye rend un fatal. Inclus ICI parce que
// toute fw.*.php charge ce fichier : c'est ce qui rend brand_favicon_tag()
// disponible a l'ensemble de la console, et pas seulement a fw.articaweb.php.
$BRANDINC=dirname(__FILE__)."/class.brand.inc";
if(is_file($BRANDINC)){include_once($BRANDINC);}
$includes=array();
$includes[]="class.icons.inc";
$includes[]="class.templates.inc";
$includes[]="class.tcpip.inc";
$includes[]="class.mysql.squid.builder.php";
$includes[]="class.dansguardian.inc";
$includes[]="class.squid.reverse.inc";
$includes[]="class.system.network.inc";
$includes[]="class.postgres.inc";
$includes[]="class.system.nics.inc";
$includes[]="class.ndpi.services.inc";
$includes[]="class.openvpn.inc";
$includes[]="class.strongswan.inc";
$includes[]="class.squid.dynamic-acls.inc";
$includes[]="class.html.tools.inc";
$includes[]="class.sqlite.inc";
$includes[]="class.memcached.inc";
$includes[]="class.mysql.catz.inc";
$includes[]="class.chartjs.inc";

foreach ($includes as $classfile){
    if(!is_file(dirname(__FILE__)."/$classfile")) {continue;}
    if($GLOBALS["DEBUG_INCLUDES"]){echo "$classfile<br>";}
    include_once(dirname(__FILE__)."/$classfile");
}

class template_admin{
    private $TRCLASS=null;
    private $switchjs=null;
    private $TABLE_FORM_BTS=array();
    private $TABLE_FORM_PRIV="";
    private $TABLE_FORM=array();
    private $TABLE_FORM_JS=array();
    private $TABLE_FORM_BUTTONS=array();
    private $TABLE_FORM_COLUMNS=1;
    private $TABLE_FORM_JS_FRESH=false;
    private $FieldTable=array();
    private $FieldTableJs=array();
    private $FieldTableBack=array();
    public $CLUSTER_CLI=false;
    public $KEEPALIVED_CLI = false;
    public  $FORM_IN_ARRAY=false;
    public  $DynaAcls=false;
    private $buttons=array();
    private $button_title=array();
    public $title=null;
    public $this_form_locked_explain=null;
    public $FORM_LOCKED=false;
    private $content;
    private $XHR=array();
    private $XHRHIDDEN;
    private $JS=array();
    public $cssScript=array();
    private $JS_FUNCs=array();
    private $DISABLE_ALL=array();
    private $CHOSENS=array();
    private $LABELS=array();
    private $FormStartupScripts=array();
    private $form_id;
    public $id_temp="";
    public $init_error=null;
    public $form_privileges=null;
    public $suffixid=null;
    public $formwizard=false;
    public $search_box_function=null;
    public $language=null;
    public $label_class=null;
    private $abt_more_rot="abt-s";
    private $abt_lbl_rot=0;
    private $button_id;
    public $ICON_SCRIPTS=array();

    public function __construct($title=null,$content=null){
        $this->title=$title;
        if(is_array($content)){
            $this->content=@implode("\n",$content);
        }else {
            $this->content = $content;
        }
        $this->XHRHIDDEN=array();
        $this->suffixid=md5(microtime(true).rand(0, time()));
        $tpl=new templates();
        $this->language=$tpl->language;

    }
    public function BoolToInteger($value):int{
        if(is_null($value)){
            return 0;
        }

        if(is_bool($value)){
            if($value){return 1;}
            return 0;
        }
        if(is_numeric($value)){return $value;}
        if(strtolower($value)=="yes"){return 1;}
        if(strtolower($value)=="true"){return 1;}
        return 0;
    }
    public function IsBase64($value): bool {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        $value = trim($value);

        // Length must be multiple of 4
        if (strlen($value) % 4 !== 0) {
            return false;
        }

        // Only valid base64 characters
        if (!preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $value)) {
            return false;
        }

        // Decode and re-encode to confirm
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $value;
    }

    public function TarListFiles($fullpath):array{
        include_once(dirname(__FILE__).'/class.tar.inc');
        $tar_object = new Archive_Tar($fullpath);
        $results=array();
        if (($v_list  =  $tar_object->listContent()) != 0) {
            foreach ($v_list as $key => $value) {
                if($GLOBALS["VERBOSE"]){echo "$key:\n";}
                if (!is_array($value)) {
                    continue;
                }
                if (!isset($value["filename"])) {
                    continue;
                }
                $results[] = $value["filename"];
            }
        }
        return $results;
    }


    /**
     * Feuille de style des listes de parametres (design "central form view").
     * Emise une seule fois par requete, comme svctile_css().
     * NE JAMAIS faire passer ce CSS par _ENGINE_parse_body(): son regex #{(.+?)}#
     * prend chaque corps de regle pour un {token} et declenche un acces memcached
     * par regle. table_form_compile() concatene donc le CSS APRES le parse.
     */
    private function table_form_css():string{
        if(isset($GLOBALS["TBLFORM_CSS_DONE"])){return "";}
        $GLOBALS["TBLFORM_CSS_DONE"]=true;
        // Les accents suivent la marque quand elle est posee, et retombent sur les
        // verts de la maquette sinon. class.brand.inc peut manquer sur une appliance
        // ou il n'est pas encore deploye : garde function_exists, repli identique.
        $ac=array("accent"=>"#005447","accent_dark"=>"#003d33",
                  "accent_soft"=>"rgba(0,84,71,.12)","accent_line"=>"rgba(0,84,71,.35)",
                  "on_fg"=>"#ffffff","label"=>"#1e293b");
        if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
            $ac=brand_ui_accents(brand_palette());
        }
        $c=array();
        $c[]="<style type='text/css'>";
        // --- la carte (maquette stitch_network_device_details_dialog, 2026-11-11) :
        // une carte blanche par section, en-tete (icone + titre + bouton) separe des
        // lignes par un filet ; les lignes sont une liste libelle a gauche / valeur a
        // droite, separees par un filet clair, sans tuile ni ombre propre.
        $c[]=".tfrm{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;";
        $c[]="box-shadow:0 1px 2px rgba(15,23,42,.06);margin:10px 0 16px 5px;color:#0f172a}";
        // Sans border-box, le padding des lignes s'ajoute a la largeur de piste de la
        // grille et fait deborder la carte en mode 2 colonnes.
        $c[]=".tfrm,.tfrm *{-webkit-box-sizing:border-box;box-sizing:border-box}";
        // Grille meme sur une colonne. Aucun row-gap : c'est le filet de chaque ligne
        // qui separe. minmax(0,1fr) et non 1fr : "1fr" vaut minmax(auto,1fr), dont le
        // minimum est la taille min-content de la ligne, qui deborde alors de sa piste.
        $c[]=".tfrm-grid{display:-ms-grid;display:grid;grid-template-columns:minmax(0,1fr)}";
        // 2 colonnes: opt-in via table_form_columns(2). Repli en 1 colonne sur petit ecran.
        $c[]=".tfrm-c2{grid-template-columns:minmax(0,1fr) minmax(0,1fr);";
        $c[]="grid-column-gap:24px;-webkit-column-gap:24px;column-gap:24px}";
        $c[]="@media (max-width:900px){.tfrm-c2{display:block}}";
        // --- la ligne : 40px mini, filet #f1f5f9 au-dessus, sauf la premiere de la
        // carte (et la deuxieme en 2 colonnes, qui partage la premiere rangee).
        // Marge negative + padding : le fond de survol deborde un peu du texte sans
        // decaler l'alignement des libelles sur le titre de la carte.
        $c[]=".tfrm-row{display:-webkit-box;display:-ms-flexbox;display:flex;";
        $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
        $c[]="min-height:40px;padding:8px 10px;margin:0 -10px;border-top:1px solid #f1f5f9;border-radius:8px;";
        // min-width:0 : un element de grille refuse par defaut de retrecir sous la
        // largeur de son contenu (min-width:auto) et deborde alors de sa piste.
        $c[]="min-width:0;-webkit-transition:background-color .15s ease;transition:background-color .15s ease}";
        $c[]=".tfrm-grid>.tfrm-row:first-child,";
        $c[]=".tfrm-c2>.tfrm-row:first-child:not(.tfrm-full)+.tfrm-row:not(.tfrm-full){border-top-color:transparent}";
        $c[]=".tfrm-full{grid-column:1/-1}";
        $c[]=".tfrm-row-js{cursor:pointer}";
        $c[]=".tfrm-row-js:hover{background:#f8fafc}";
        // --- l'icone : 24px, carre blanc borde, gris ; prend l'accent quand le
        // booleen de la ligne est actif.
        // Font Awesome tourne en mode SVG+JS: la taille se pose sur le CONTENEUR
        // (un selecteur descendant "i" ne matche plus rien apres remplacement).
        $c[]=".tfrm-chip{-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto;width:24px;height:24px;";
        $c[]="border-radius:6px;background:#fff;border:1px solid #e2e8f0;color:#64748b;font-size:11px;";
        $c[]="box-shadow:0 1px 1px rgba(15,23,42,.04);";
        $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;";
        $c[]="align-items:center;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center}";
        $c[]=".tfrm-chip-on{background:{$ac["accent_soft"]};border-color:{$ac["accent_line"]};color:{$ac["accent_dark"]}}";
        $c[]=".tfrm-chip-warn{background:#fff7ed;border-color:#fed7aa;color:#b45309}";
        $c[]=".tfrm-chip-err{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
        // min-width:0 est indispensable: sans lui un flex item refuse de retrecir et la
        // valeur de droite sort de la carte.
        $c[]=".tfrm-lbl{-webkit-box-flex:1;-ms-flex:1 1 auto;flex:1 1 auto;min-width:0;padding:0 12px 0 10px}";
        // Libelle simple : gris, comme le <dt> de la maquette. Avec un sous-titre, il
        // devient un vrai titre (fonce, 500) au-dessus de son explication.
        // Pas de text-transform:capitalize : les titres portent des phrases entieres.
        $c[]=".tfrm-t{font-size:13px;font-weight:400;color:#64748b;line-height:1.45}";
        $c[]=".tfrm-lbl-x .tfrm-t{font-weight:500;color:{$ac["label"]}}";
        $c[]=".tfrm-s{font-size:13px;color:#64748b;line-height:1.45;margin-top:2px}";
        $c[]=".tfrm-val{-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto;max-width:55%;";
        $c[]="font-size:13px;font-weight:500;color:#0f172a;text-align:right;";
        $c[]="overflow:hidden;text-overflow:ellipsis}";
        // Le libelle porte deja min-width:0 : c'est lui qui retrecit pour laisser la
        // place au bouton, au lieu de le faire couper.
        $c[]=".tfrm-val-btn{max-width:none;overflow:visible;text-overflow:clip}";
        $c[]=".tfrm-val a,.tfrm-val a:hover{color:{$ac["accent_dark"]};text-decoration:none}";
        $c[]=".tfrm-val a:hover{text-decoration:underline}";
        // Valeur texte sans balise : le <dd> de la maquette, du texte nu et fonce.
        // Posee par table_form_field_text() sur les seules valeurs sans balise.
        $c[]=".tfrm-pill{display:inline-block;max-width:100%;vertical-align:middle;";
        $c[]="white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
        $c[]=".tfrm-mono{font-family:'Courier New',Courier,monospace;color:#64748b}";
        // --- l'etat : puce carree bordee, icone + texte (jamais la couleur seule)
        $c[]=".tfrm-state{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:6px;";
        $c[]="border:1px solid transparent;font-size:13px;font-weight:600;line-height:1.5;white-space:nowrap}";
        $c[]=".tfrm-state-on{background:{$ac["accent_soft"]};color:{$ac["accent_dark"]};border-color:{$ac["accent_line"]}}";
        $c[]=".tfrm-state-off{background:#f1f5f9;color:#64748b;border-color:#e2e8f0;font-weight:500}";
        // La classe est recopiee par FA sur le <svg> genere : la taille tient.
        $c[]=".tfrm-state-i{font-size:11px}";
        $c[]=".tfrm-act{-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto;padding-left:8px}";
        // Le crayon : une icone nue, grise, qui prend l'accent au survol.
        // icon_edit_field() pose un style inline sur l'icone, et FA recopie cet attribut
        // sur le <svg> genere: il faut !important pour reprendre la main sur la couleur.
        $c[]=".tfrm-act .btn:not(.abt){background:transparent !important;border:0 !important;";
        $c[]="border-radius:8px !important;box-shadow:none !important;text-shadow:none !important;";
        $c[]="padding:6px 8px;color:#94a3b8 !important}";
        $c[]=".tfrm-act .btn:not(.abt) i,.tfrm-act .btn:not(.abt) svg{color:#94a3b8 !important}";
        $c[]=".tfrm-act .btn:not(.abt):hover,.tfrm-act .btn:not(.abt):focus{background:#f1f5f9 !important;color:{$ac["accent_dark"]} !important}";
        $c[]=".tfrm-act .btn:not(.abt):hover i,.tfrm-act .btn:not(.abt):hover svg,.tfrm-act .btn:not(.abt):focus i,.tfrm-act .btn:not(.abt):focus svg{color:{$ac["accent_dark"]} !important}";
        // --- l'en-tete de carte : icone 28px teintee, titre 15/600, bouton a droite,
        // filet dessous. Le sous-titre passe sous le titre.
        $c[]=".tfrm-sec{display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;";
        $c[]="align-items:center;gap:10px;padding:0 0 14px 0;margin:0 0 6px 0;border-bottom:1px solid #f1f5f9}";
        $c[]=".tfrm-sec-ico{-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto;width:28px;height:28px;border-radius:8px;";
        $c[]="background:{$ac["accent_soft"]};color:{$ac["accent_dark"]};font-size:12px;";
        $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;align-items:center;justify-content:center}";
        $c[]=".tfrm-sec-txt{-webkit-box-flex:1;-ms-flex:1 1 auto;flex:1 1 auto;min-width:0}";
        $c[]=".tfrm-sec-t{font-size:15px;font-weight:600;color:#0f172a;line-height:1.4;margin:0;padding:0}";
        $c[]=".tfrm-sec-s{font-size:13px;color:#64748b;margin:2px 0 0 0;padding:0;line-height:1.45}";
        $c[]=".tfrm-sec-b{-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto}";
        // Une carte qui ne contient que son en-tete (texte d'information) n'a pas de filet.
        $c[]=".tfrm-sec:last-child{border-bottom:0;padding-bottom:0;margin-bottom:0}";
        // --- avertissement : un encart ambre, pas une ligne de liste
        // Selecteur a la meme specificite que ".tfrm-grid>.tfrm-row:first-child" et place
        // apres : un avertissement en tete de carte garde sa bordure du haut.
        $c[]=".tfrm-grid>.tfrm-row.tfrm-warn{background:#fff7ed;border:1px solid #fed7aa;margin:8px 0;padding:10px 12px}";
        $c[]=".tfrm-row.tfrm-warn+.tfrm-row{border-top-color:transparent}";
        $c[]=".tfrm-warn .tfrm-t{color:#92400e;font-weight:500}";
        $c[]=".tfrm-foot{padding:14px 0 0 0;margin:8px 0 0 0;background:none;";
        $c[]="border:0;border-top:1px solid #f1f5f9;border-radius:0;text-align:right;";
        $c[]="display:flex;flex-wrap:wrap;justify-content:flex-end;align-items:center;gap:8px}";
        // --- boutons d'action : la pastille de stitch_button_dashboard
        //
        // Porte uniquement par le CSS, et limitee a .tfrm : button_autnonome() et
        // th_buttons() servent toute la console, les restyler toucherait des
        // centaines de pages hors sujet. Cibler .tfrm .btn couvre d'un coup
        // table_form_field_info, _button, _buttons et table_form_button, sans
        // modifier une ligne de PHP ni aucun des 94 appels.
        //
        // !important est necessaire : le helper pose un style INLINE
        // (text-transform + une largeur de 250 ou 300px) que la pastille doit
        // reprendre pour epouser son contenu.
        // PIEGE : "$btn:hover" n'accroche la pseudo-classe qu'au DERNIER selecteur
        // d'une liste separee par des virgules. Les deux premiers se retrouvaient
        // alors dans la regle de survol SANS :hover, et son fond blanc s'appliquait
        // en permanence. Chaque suffixe est donc distribue sur chaque selecteur.
        // Le pied de carte garde le bouton abt tel quel (table_form_button = meme bouton
        // que le pied de form_outside) : la pastille ne s'applique qu'aux autres .btn.
        $sels=array(".tfrm-val .btn:not(.abt)",".tfrm-foot .btn:not(.abt)",".tfrm-row .tfrm-lbl .btn:not(.abt)");
        $btn=@implode(",",$sels);
        $bh=array(); $bi=array();
        foreach($sels as $x){ $bh[]="$x:hover"; $bh[]="$x:focus"; $bi[]="$x i"; $bi[]="$x svg"; }
        $btnHover=@implode(",",$bh);
        $btnIco=@implode(",",$bi);
        $c[]="$btn{display:inline-block;width:auto !important;padding:4px 12px !important;";
        $c[]="border-radius:999px !important;font-size:12px !important;font-weight:600 !important;";
        $c[]="text-transform:none !important;line-height:1.5;white-space:nowrap;";
        $c[]="box-shadow:0 1px 2px rgba(15,23,42,.05) !important;text-shadow:none !important;";
        $c[]="background:{$ac["accent_soft"]} !important;color:{$ac["accent_dark"]} !important;";
        $c[]="border:1px solid {$ac["accent_line"]} !important}";
        $c[]="$btnHover{background:#fff !important;";
        $c[]="border-color:{$ac["accent_dark"]} !important;color:{$ac["accent_dark"]} !important}";
        $c[]="$btnIco{color:{$ac["accent_dark"]} !important}";
        // Les variantes semantiques survivent : la maquette ne montre qu'un bouton
        // neutre, mais table_form_field_button rend btn-danger des que l'icone est
        // une corbeille. Peindre une suppression en vert la rendrait indiscernable
        // d'une action benigne.
        // Un bouton abt (button_autnonome, button_inline, th_buttons, field_array_buttons,
        // table_form_button, table_form_field_button) garde son propre style partout dans
        // la carte : la pastille et ses variantes ne visent que les autres .btn.
        $c[]=".tfrm-val .btn-danger:not(.abt),.tfrm-foot .btn-danger:not(.abt){background:rgba(217,43,50,.10) !important;";
        $c[]="color:#b3261e !important;border-color:rgba(217,43,50,.35) !important}";
        $c[]=".tfrm-val .btn-danger:not(.abt):hover,.tfrm-foot .btn-danger:not(.abt):hover{background:#fff !important;";
        $c[]="border-color:#d92b32 !important;color:#b3261e !important}";
        $c[]=".tfrm-val .btn-danger:not(.abt) i,.tfrm-val .btn-danger:not(.abt) svg,";
        $c[]=".tfrm-foot .btn-danger:not(.abt) i,.tfrm-foot .btn-danger:not(.abt) svg{color:#b3261e !important}";
        $c[]=".tfrm-val .btn-warning:not(.abt),.tfrm-foot .btn-warning:not(.abt){background:rgba(217,131,36,.12) !important;";
        $c[]="color:#8a5a00 !important;border-color:rgba(217,131,36,.38) !important}";
        $c[]=".tfrm-val .btn-warning:not(.abt) i,.tfrm-val .btn-warning:not(.abt) svg,";
        $c[]=".tfrm-foot .btn-warning:not(.abt) i,.tfrm-foot .btn-warning:not(.abt) svg{color:#8a5a00 !important}";
        $c[]=".tfrm-val .abt,.tfrm-row .tfrm-lbl .abt{vertical-align:middle}";
        $c[]="</style>";
        return @implode("\n",$c);
    }

    /**
     * Nombre de colonnes de la prochaine liste compilee.
     * Opt-in: sans appel, le rendu reste sur une colonne et aucune page existante ne
     * change. table_form_compile() remet la valeur a 1, comme les autres buffers.
     */
    public function table_form_columns($columns=1):bool{
        $columns=intval($columns);
        if($columns<1){$columns=1;}
        if($columns>2){$columns=2;}
        $this->TABLE_FORM_COLUMNS=$columns;
        return true;
    }

    /**
     * Separe "titre||sous-titre" et absorbe le prefixe "noright:".
     * Retourne array($titre,$sous_titre,$noright).
     */
    private function table_form_split_label($field):array{
        $noright=false;
        if(preg_match("#^noright:(.+)#",$field,$re)){
            $noright=true;
            $field=$re[1];
        }
        $explain="";
        if(strpos($field,"||")!==false){
            $tb=explode("||",$field,2);
            $field=trim($tb[0]);
            $explain=trim($tb[1]);
        }
        return array($field,$explain,$noright);
    }

    /**
     * JS de clic pour la ligne courante, consomme une seule fois.
     * Retourne null si aucun table_form_field_js() n'a ete pose juste avant.
     */
    private function table_form_rowjs(){
        if(!$this->TABLE_FORM_JS_FRESH){return null;}
        $this->TABLE_FORM_JS_FRESH=false;
        return $this->switchjs;
    }

    /** Pastille d'icone de gauche. $variant: null | "on" | "warn" | "err". */
    private function table_form_chip($ico,$variant=null):string{
        if($ico==null){$ico="fa-regular fa-ellipsis";}
        $class="tfrm-chip";
        if($variant=="on"){$class.=" tfrm-chip-on";}
        if($variant=="warn"){$class.=" tfrm-chip-warn";}
        if($variant=="err"){$class.=" tfrm-chip-err";}
        return "<div class='$class'><i class='$ico'></i></div>";
    }

    /** Libelle: titre, plus un sous-titre gris quand la page en a fourni un. */
    private function table_form_label($field,$explain=""):string{
        $h=array();
        $hasExplain=strlen(trim((string)$explain))>0;
        // Avec un sous-titre, le libelle devient un titre fonce (classe tfrm-lbl-x) ;
        // seul, il reste le libelle gris d'une liste definition/valeur.
        $h[]=$hasExplain?"<div class='tfrm-lbl tfrm-lbl-x'>":"<div class='tfrm-lbl'>";
        $h[]="<div class='tfrm-t'>$field</div>";
        if($hasExplain){$h[]="<div class='tfrm-s'>$explain</div>";}
        $h[]="</div>";
        return @implode("",$h);
    }

    /**
     * Etat booleen : une pastille arrondie precedee d'un point, comme la maquette.
     *
     * Le point est un <span> ordinaire et non une icone Font Awesome : FA tourne en
     * mode SVG+JS et remplace le <i>, ce qui rend sa couleur et sa taille beaucoup
     * plus difficiles a piloter. currentColor suffit ici.
     */
    private function table_form_state_label($val):string{
        $val=$this->BoolToInteger($val);
        if($val==1){
            return "<span class='tfrm-state tfrm-state-on'><i class='fa-solid fa-circle-check tfrm-state-i' aria-hidden='true'></i>{active2}</span>";
        }
        return "<span class='tfrm-state tfrm-state-off'><i class='fa-regular fa-circle tfrm-state-i' aria-hidden='true'></i>{inactive2}</span>";
    }

    /**
     * Assemblage d'une ligne. $opts accepte:
     *   full  => la ligne occupe les 2 colonnes (en-tete de section, avertissement, boutons)
     *   on    => ligne mise en avant (booleen actif)
     *   class => classes supplementaires
     *   js    => rend la ligne cliquable (switchjs arme par table_form_field_js)
     */
    private function table_form_row($chip,$label,$value,$action,$opts=array()):string{
        $class="tfrm-row";
        if(isset($opts["on"]) && $opts["on"]){$class.=" tfrm-row-on";}
        if(isset($opts["full"]) && $opts["full"]){$class.=" tfrm-full";}
        if(isset($opts["class"])){$class.=" ".$opts["class"];}
        $attr="";
        if(isset($opts["js"])){
            if(strlen(trim((string)$opts["js"]))>0){
                $class.=" tfrm-row-js";
                // Le crayon de droite (icon_edit_field) porte le MEME js que la
                // ligne : son clic remontait jusqu'ici et ouvrait la fenetre deux
                // fois. Un controle de la ligne a deja sa propre action, la ligne
                // ne reagit donc qu'aux clics ailleurs. Pas de stopPropagation()
                // sur le bouton : BS3 ecoute ses menus sur document.
                $attr=" OnClick=\"if(event.target.closest('button,a,input,select,textarea')){return;}".$opts["js"]."\"";
            }
        }
        $h=array();
        $h[]="<div class='$class'$attr>";
        $h[]=$chip;
        $h[]=$label;
        // Les trois strlen() recevaient la valeur telle quelle: un appelant qui
        // passe la propriete absente d'un json_decode -- le cas courant d'un
        // rapport partiel -- envoyait NULL, et PHP 8.1 emet un "Deprecated:
        // Passing null to parameter #1 of type string" a chaque ligne rendue.
        if(strlen((string)$value)>0){
            // Un texte trop long s'ellipse, c'est voulu. Un BOUTON tronque, non :
            // "Manage 0 Items" ressortait "Manage 0 Iter", coupe par le plafond de
            // 45% et l'overflow:hidden de la zone de valeur. La detection porte ici
            // plutot que dans chaque appelant : table_form_field_button,
            // table_form_field_install et tout appelant futur en beneficient.
            $valclass="tfrm-val";
            if(stripos((string)$value,"<button")!==false){$valclass.=" tfrm-val-btn";}
            $h[]="<div class='$valclass'>$value</div>";
        }
        if(strlen((string)$action)>0){$h[]="<div class='tfrm-act'>$action</div>";}
        $h[]="</div>";
        return @implode("",$h);
    }

    /**
     * En-tete de carte. $ico (optionnel) : icone Font Awesome dans une pastille de
     * 28px. $button (optionnel) : array("label"=>,"js"=>,"ico"=>,"priv"=>) -- un
     * bouton blanc borde (abt-o ; abt-s est plein dans ce systeme) a droite du titre (ex. "Edit DHCP"), cadenasse si le droit
     * "priv" manque. Sans ces deux arguments, l'en-tete n'a que titre et sous-titre.
     */
    public function table_form_section($title=null,$explain=null,$ico=null,$button=null):bool{
        $INT=array();
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $title=html_safe_fragment($title);
        $explain=html_safe_fragment($explain);
        $ico=preg_replace('#[^A-Za-z0-9 _-]#','',(string)$ico);
        // Plus un element de grille : table_form_compile() la sort du flux des lignes
        // et en fait l'en-tete de SA carte. Le prefixe "<div class='tfrm-sec" est ce
        // que le compilateur reconnait pour couper -- ne pas le renommer sans lui.
        $INT[] = "<div class='tfrm-sec'>";
        if($ico<>""){ $INT[] = "<div class='tfrm-sec-ico'><i class='$ico' aria-hidden='true'></i></div>"; }
        $INT[] = "<div class='tfrm-sec-txt'>";
        $INT[] = "<div class='tfrm-sec-t'>$title</div>";
        if($explain<>""){ $INT[] = "<div class='tfrm-sec-s'>$explain</div>"; }
        $INT[] = "</div>";
        if(is_array($button) && isset($button["label"])){
            $bjs=isset($button["js"])?(string)$button["js"]:"";
            $bico=isset($button["ico"])?(string)$button["ico"]:"fa-solid fa-pen";
            $bpriv=isset($button["priv"])?$button["priv"]:null;
            $INT[] = "<div class='tfrm-sec-b'>".$this->button_inline($button["label"],$bjs,$bico,$bpriv,0,"abt-o")."</div>";
        }
        $INT[] = "</div>";

        $this->TABLE_FORM[]=@implode("\n",$INT);
        return true;
    }




    public function table_form_field_certificate($CertificateName):bool{
        if(preg_match("#SUB:([0-9]+)#",$CertificateName,$re)){
            $SUBID=intval($re[1]);
            include_once(dirname(__FILE__)."/class.certificates-db.inc");
            $cdb=new CertificatesDB();
            $sub=$cdb->subGetById($SUBID);
            $CertID=is_object($sub)?intval($sub->certid ?? 0):0;
            $main=$CertID>0?$cdb->getById($CertID):null;
            $mainName=is_object($main)?(string)($main->CommonName ?? ""):"";
            $CertificateName="Server:&nbsp;".$mainName."&nbsp;($SUBID)";
        }
        $this->table_form_field_info("{ssl_certificate}",$CertificateName,ico_certificate);
        return true;

    }

    public function SecondsToTime($inputSeconds):string {
    $secondsInAMinute = 60;
    $secondsInAnHour = 60 * $secondsInAMinute;
    $secondsInADay = 24 * $secondsInAnHour;

    // Extract days
    $days = floor($inputSeconds / $secondsInADay);

    // Extract hours
    $hourSeconds = $inputSeconds % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);

    // Extract minutes
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);

    // Extract the remaining seconds
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);

    // Format and return
    $timeParts = [];
    $sections = array(
        '{day' => (int)$days,
        '{hour' => (int)$hours,
        '{minute' => (int)$minutes,
        '{second' => (int)$seconds
    );

    foreach ($sections as $name => $value){
        if ($value > 0){
            $timeParts[] = $value. ' '.$name.($value == 1 ? '}' : 's}');
        }
}

return implode(', ', $timeParts);
}


    private function table_form_field_text_button($value,$ico=""){
        $color="#333";
        if(!is_array($value)){
            return array("",$value,$color);
        }

        if(!isset($value["BUTTON"])){
            return array("",$value,$color);
        }

        $Grey["{not_installed}"]=true;
        $Grey["{disabled}"]=true;
        $Grey["{inactive2}"]=true;
        $Grey["{none}"]=true;
        $NewVal="";


        if(isset($value["BUTTON"]["VALUE"])){
            $NewVal=$value["BUTTON"]["VALUE"];

        }
        if(isset($Grey[$NewVal])){
            $color="rgb(185, 182, 182)";
        }
        $labelbtn="";
        $class="btn-primary";
        $btnjs="blur()";
        if(isset($value["BUTTON"]["BT_CLASS"])){
            $class=$value["BUTTON"]["BT_CLASS"];
        }
        if(isset($value["BUTTON"]["LABEL"])){
            $labelbtn=$value["BUTTON"]["LABEL"];
        }
        if(isset($value["BUTTON"]["JS"])){
            $btnjs=$value["BUTTON"]["JS"];
        }
        // Bouton d'une ligne de carte : le meme que table_form_field_button() (button_inline,
        // 28 px, neutre ; corbeille = destructif ; sans JS = desactive).
        if($ico==ico_trash){$class="btn-danger";}
        $Button=$this->button_inline($labelbtn,$btnjs,$ico,null,0,$class,"small");

        return array($Button,$NewVal,$color);
    }
    public function table_form_field_warn($text):bool{
        $ico="fas fa-exclamation-triangle";
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $text=html_safe_fragment($text);
        // Le message porte l'information: il occupe le titre. La pastille ambre et la
        // teinte de la ligne suffisent a signaler la nature de l'avertissement.
        $this->TABLE_FORM[]=$this->table_form_row(
            $this->table_form_chip($ico,"warn"),
            $this->table_form_label($text),
            "","",
            array("full"=>true,"class"=>"tfrm-warn"));
        return true;
    }

    public function table_form_field_text($field,$value,$ico=null,$error=false):bool{
        if($ico==null){$ico="fa-regular fa-ellipsis";}
        list($Button,$value,$color)=$this->table_form_field_text_button($value,$ico);
        list($field,$explain,$NoRight)=$this->table_form_split_label($field);

        $edit=null;
        if($this->switchjs<>null){
            $edit=$this->icon_edit_field($this->switchjs,$this->TABLE_FORM_PRIV);

        }
        $ico_check=ico_check_double;
        if(!is_null($value)) {
            $value = str_replace("[OK]", "<i class='$ico_check' style='color:#1ab394'></i>", $value);
        }
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        // Valeur souvent externe (daemon, LDAP, journal) : filtre HTML ; les entites
        // des appelants qui pre-encodent sont gardees.
        $value=html_safe_fragment($value);

        $icon_help=null;
        if(preg_match("#^http#",$error)){
            $icon_help=$this->table_form_help_button($error);
            $error=false;

        }


        // $error="warn": a configuration defect (amber), not a failure (red).
        $warn=false;
        if($error==="warn"){$warn=true;$error=false;$color="#b45309";}
        if($error){
            $color="#ed5565";
        }

        $btns_flat="";
        $idStrong=md5(time()."strong".$value.$field);
        $rowjs=$this->table_form_rowjs();
        VERBOSE("TABLE_FORM_BTS:".count($this->TABLE_FORM_BTS),__LINE__);
        if(count($this->TABLE_FORM_BTS)>0) {
            // Des boutons occupent deja la ligne: le clic global la rendrait ambigue,
            // on le rebranche sur la seule valeur (comportement d'origine).
            $rowjs=null;
            if (strlen($this->switchjs) > 3) {
                $this->TABLE_FORM_JS[] = "$('#$idStrong').click(function () { $this->switchjs } );";
            }
            $btns_flat="&nbsp;".$this->th_buttons($this->TABLE_FORM_BTS);
            $this->TABLE_FORM_BTS=array();
        }

        $variant=null;
        if($error){$variant="err";}
        if($warn){$variant="warn";}
        // Une valeur texte sans balise prend l'etiquette grise de la maquette ; une valeur
        // deja mise en forme par la page (lien, label, icone) garde son propre rendu.
        $pill="";
        if(strpos((string)$value,"<")===false && strlen(trim((string)$value))>0){$pill=" class='tfrm-pill'";}
        $valuehtml="<span id='$idStrong'$pill style='color:$color'>$value</span>$icon_help$btns_flat";
        if(strlen($Button)>0){$valuehtml.="&nbsp;$Button";}

        $this->TABLE_FORM[]=$this->table_form_row(
            $this->table_form_chip($ico,$variant),
            $this->table_form_label($field,$explain),
            $valuehtml,
            $NoRight?"":$edit,
            array("js"=>$rowjs));
        return true;

    }

    /**
     * Rend le tampon et le VIDE.
     *
     * Chaque table_form_section() clot la carte courante et en ouvre une nouvelle,
     * comme la maquette stitch_table_form : une carte par groupe, son titre en
     * tete, ses lignes dessous. Sans aucune section, le rendu est celui d'avant --
     * une carte unique -- et les ~127 pages qui n'en posent pas ne changent pas.
     */
    public function table_form_compile($Columns=1):string{
        // Le CSS est monte a part: le faire traverser _ENGINE_parse_body() ferait
        // interpreter chaque corps de regle comme un {token}.
        if(intval($Columns)>1){
            $this->TABLE_FORM_COLUMNS=$Columns;
        }
        $css=$this->table_form_css();
        $grid="tfrm-grid";
        if($this->TABLE_FORM_COLUMNS>1){$grid.=" tfrm-c2";}

        // Decoupage en cartes. Un groupe = un en-tete de section eventuel, plus les
        // lignes qui le suivent jusqu'a la section suivante.
        $cards=array();
        $cur=array("head"=>null,"rows"=>array());
        foreach($this->TABLE_FORM as $entry){
            if(strpos((string)$entry,"<div class='tfrm-sec")===0){
                // On ne pousse le groupe courant que s'il porte quelque chose : un
                // tampon qui COMMENCE par une section produirait sinon une carte vide.
                if($cur["head"]!==null || count($cur["rows"])>0){$cards[]=$cur;}
                $cur=array("head"=>$entry,"rows"=>array());
                continue;
            }
            $cur["rows"][]=$entry;
        }
        if($cur["head"]!==null || count($cur["rows"])>0){$cards[]=$cur;}
        if(count($cards)==0){$cards[]=array("head"=>null,"rows"=>array());}

        $html=array();
        $last=count($cards)-1;
        foreach($cards as $n=>$card){
            $html[]="<div class='tfrm'>";
            if($card["head"]!==null){$html[]=$card["head"];}
            if(count($card["rows"])>0){
                $html[]="<div class='$grid'>";
                $html[]=@implode("\n",$card["rows"]);
                $html[]="</div>";
            }
            // Les boutons de pied appartiennent a la DERNIERE carte : ils closent le
            // formulaire entier, pas le groupe courant.
            if($n==$last && count($this->TABLE_FORM_BUTTONS)>0){
                $html[]="<div class='tfrm-foot'>";
                $html[]=@implode("\n",$this->TABLE_FORM_BUTTONS);
                $html[]="</div>";
            }
            $html[]="</div>";
        }

        // Meme raison que le CSS ci-dessus: le corps d'un "function(){ ... }" est
        // lu par #{(.+?)}#is comme un {token} et declenche une recherche memcached
        // par bloc. Le JS se concatene APRES l'analyse, jamais dedans.
        $js="";
        if(count($this->TABLE_FORM_JS)>0){
            $js="<script>".@implode("\n",$this->TABLE_FORM_JS)."</script>";
        }
        $this->TABLE_FORM=array();
        $this->TABLE_FORM_BUTTONS=array();
        $this->TABLE_FORM_JS=array();
        $this->TABLE_FORM_COLUMNS=1;
        $this->TABLE_FORM_JS_FRESH=false;
        return $css.$this->_ENGINE_parse_body($html).$js;
    }

    // $disabled : carte grisee et non cliquable (setDisabled du composant) -- pour un
    // reglage qui n'a pas de sens tant qu'un autre est eteint.
    public function BigCircleCheckbox($field,$title,$explain,$value,$after=null,$Privs=null,$disabled=false):string{
        $id=md5($field.time().rand(100,999).$title.$field);
        $f=array();
        $page=CurrentPageName();
        $title=base64_encode(html_entity_decode($this->_ENGINE_parse_body($title)));
        $explain=base64_encode($this->_ENGINE_parse_body($explain));
        $SetCheck="true";
        if($value==0){
            $SetCheck="false";
        }
        $SetDisabled=$disabled?"true":"false";

        if(strpos($field,"|")>0){
            $tb=explode("|",$field);
            foreach ($tb as $newf){
                if(strpos($newf,":")>0){
                $ss=explode(":",$newf);
                $f[]="['$ss[0]']: '$ss[1]'";
                }else{
                    $f[]="['$newf']: value";
                }
            }
        }else{
            $f[]="['$field']: value";
        }

        $fftxt=@implode(",",$f);
        // Pastille Actif / Inactif de la carte (design system v2 de big-circle-checkbox.js).
        $activeLabel=base64_encode($this->_ENGINE_parse_body("{active2}"));
        $inactiveLabel=base64_encode($this->_ENGINE_parse_body("{inactive2}"));

        return "<div id=\"chkbox-$id\"></div>
        <script>
            renderBigCheckboxListSafe('chkbox-$id', [
            { title: base64_decode('$title'), 
            descriptionHTML: base64_decode('$explain'),
            activeLabel: base64_decode('$activeLabel'),
            inactiveLabel: base64_decode('$inactiveLabel')
            }
            ]).then(function (ctrls) {
                    var ctrl = ctrls[0];
                    if (!ctrl) return;
                    ctrl.setChecked($SetCheck);     // check it
                    ctrl.setDisabled($SetDisabled);
                    ctrl.input.addEventListener('change', function () {
                        var value = ctrl.input.checked ? 1 : 0;
                        //console.log('User clicked, new value =', ctrl.input.checked);
                        $.post('$page', { $fftxt })
                          .done(function (resp, textStatus, jqXHR) {
                            // success
                            console.log('OK:', resp);
                            $after
                          })
                          .fail(function (jqXHR, textStatus, errorThrown) {
                            alert('POST failed:'+errorThrown)
                          })
                          .always(function () {
                            // runs on success OR failure
                          });
                    });
            });
        </script>";
    }

    public function BigFieldIntegerCheckbox($field,$title,$explain,$value,$min=null,$max=null,$after=null,$Privs=null):string{
        $id=time().rand(100,999);
        $f=array();
        $page=CurrentPageName();
        $title=base64_encode(html_entity_decode($this->_ENGINE_parse_body($title)));
        $explain=base64_encode($this->_ENGINE_parse_body($explain));

        $minAttr = ($min !== null) ? "min: $min," : "";
        $maxAttr = ($max !== null) ? "max: $max," : "";

        if(strpos($field,"|")>0){
            $tb=explode("|",$field);
            foreach ($tb as $newf){
                if(strpos($newf,":")>0){
                    $ss=explode(":",$newf);
                    $f[]="['$ss[0]']: '$ss[1]'";
                }else{
                    $f[]="['$newf']: value";
                }
            }
        }else{
            $f[]="['$field']: value";
        }

        $fftxt=@implode(",",$f);

        return "<div id=\"intbox-$id\"></div>
        <script>
            renderBigIntegerListSafe('intbox-$id', [
            {
                title: base64_decode('$title'),
                descriptionHTML: base64_decode('$explain'),
                defaultValue: $value,
                $minAttr
                $maxAttr
                onSave: function(value) {
                    // POST on save (Enter or Tab key)
                    $.post('$page', { $fftxt })
                      .done(function (resp, textStatus, jqXHR) {
                        console.log('Integer saved:', value, 'Response:', resp);
                        $after
                      })
                      .fail(function (jqXHR, textStatus, errorThrown) {
                        alert('POST failed: ' + errorThrown);
                      });
                }
            }
            ]).then(function (ctrls) {
                var ctrl = ctrls[0];
                if (!ctrl) return;
                ctrl.setDisabled(false);
            });
        </script>";
    }
    public function BigUriField($field,$title,$explain,$value,$after=null,$Privs=null):string{
        $id=md5(time().rand(100,999).$field);
        $placeholder='http://example.com or domain.tld';
        $f=array();
        $page=CurrentPageName();
        $title=base64_encode(html_entity_decode($this->_ENGINE_parse_body($title)));
        $explain=base64_encode($this->_ENGINE_parse_body($explain));

        if(strpos($field,"|")>0){
            $tb=explode("|",$field);
            foreach ($tb as $newf){
                if(strpos($newf,":")>0){
                    $ss=explode(":",$newf);
                    $f[]="['$ss[0]']: '$ss[1]'";
                }else{
                    $f[]="['$newf']: value";
                }
            }
        }else{
            $f[]="['$field']: value";
        }

        $fftxt=@implode(",",$f);

        return "<div id=\"uribox-$id\"></div>
        <script>
            renderBigUriListSafe('uribox-$id', [
            {
                title: base64_decode('$title'),
                descriptionHTML: base64_decode('$explain'),
                defaultValue: '$value',
                placeholder: '$placeholder',
                onSave: function(value) {
                    // POST on save (Enter key or blur if value changed and valid)
                    $.post('$page', { $fftxt })
                      .done(function (resp, textStatus, jqXHR) {
                        console.log('URI saved:', value, 'Response:', resp);
                        $after
                      })
                      .fail(function (jqXHR, textStatus, errorThrown) {
                        alert('POST failed: ' + errorThrown);
                      });
                }
            }
            ]).then(function (ctrls) {
                var ctrl = ctrls[0];
                if (!ctrl) return;
                ctrl.setDisabled(false);
            });
        </script>";
    }
    public function BigDomainField($field,$title,$explain,$value,$after=null,$Privs=null):string{
        $id=md5(time().rand(100,999).$field);
        $this->id_temp=$id;
        $placeholder='example.com';
        $f=array();
        $page=CurrentPageName();
        $title=base64_encode(html_entity_decode($this->_ENGINE_parse_body($title)));
        $explain=base64_encode($this->_ENGINE_parse_body($explain));

        if(strpos($field,"|")>0){
            $tb=explode("|",$field);
            foreach ($tb as $newf){
                if(strpos($newf,":")>0){
                    $ss=explode(":",$newf);
                    $f[]="['$ss[0]']: '$ss[1]'";
                }else{
                    $f[]="['$newf']: value";
                }
            }
        }else{
            $f[]="['$field']: value";
        }

        $fftxt=@implode(",",$f);

        return "<div id=\"domainbox-results-$id\"></div><div id=\"domainbox-$id\"></div>
        <script>
            renderBigDomainListSafe('domainbox-$id', [
            {
                title: base64_decode('$title'),
                descriptionHTML: base64_decode('$explain'),
                defaultValue: '$value',
                placeholder: '$placeholder',
                onSave: function(value) {
                    // POST on save (Enter key or blur if value changed and valid)
                    $.post('$page', { $fftxt })
                      .done(function (resp, textStatus, jqXHR) {
                        console.log('Domain saved:', value, 'Response:', resp);
                        $('#domainbox-results-$id').html(resp);
                        $after
                      })
                      .fail(function (jqXHR, textStatus, errorThrown) {
                        alert('POST failed: ' + errorThrown);
                      });
                }
            }
            ]).then(function (ctrls) {
                var ctrl = ctrls[0];
                if (!ctrl) return;
                ctrl.setDisabled(false);
            });
        </script>";
    }
    public function BigIntegerField($field,$title,$explain,$value,$after=null,$min=null,$max=null,$Privs=null):string{
        $placeholder='0';
        $id=time().rand(100,999);
        $f=array();
        $page=CurrentPageName();
        $title=base64_encode(html_entity_decode($this->_ENGINE_parse_body($title)));
        $explain=base64_encode($this->_ENGINE_parse_body($explain));

        $minAttr = ($min !== null) ? "min: $min," : "";
        $maxAttr = ($max !== null) ? "max: $max," : "";

        if(strpos($field,"|")>0){
            $tb=explode("|",$field);
            foreach ($tb as $newf){
                if(strpos($newf,":")>0){
                    $ss=explode(":",$newf);
                    $f[]="['$ss[0]']: '$ss[1]'";
                }else{
                    $f[]="['$newf']: value";
                }
            }
        }else{
            $f[]="['$field']: value";
        }

        $fftxt=@implode(",",$f);

        return "<div id=\"intfield-$id\"></div>
        <script>
            renderBigIntegerFieldSafe('intfield-$id', [
            {
                title: base64_decode('$title'),
                descriptionHTML: base64_decode('$explain'),
                defaultValue: $value,
                placeholder: '$placeholder',
                $minAttr
                $maxAttr
                onSave: function(value) {
                    // POST on save (Enter key or blur if value changed and valid)
                    $.post('$page', { $fftxt })
                      .done(function (resp, textStatus, jqXHR) {
                        console.log('Integer saved:', value, 'Response:', resp);
                        $after
                      })
                      .fail(function (jqXHR, textStatus, errorThrown) {
                        alert('POST failed: ' + errorThrown);
                      });
                }
            }
            ]).then(function (ctrls) {
                var ctrl = ctrls[0];
                if (!ctrl) return;
                ctrl.setDisabled(false);
            });
        </script>";
    }
    public function BigTextField($field,$title,$explain,$value,$after=null,$minLength=null,$maxLength=null,$Privs=null):string{
        $placeholder='Enter text';
        $id=md5(time().rand(1000,9999).$field);
        $f=array();
        $page=CurrentPageName();
        $title=base64_encode(html_entity_decode($this->_ENGINE_parse_body($title)));
        $explain=base64_encode($this->_ENGINE_parse_body($explain));

        $minAttr = ($minLength !== null) ? "minLength: $minLength," : "";
        $maxAttr = ($maxLength !== null) ? "maxLength: $maxLength," : "";

        if(strpos($field,"|")>0){
            $tb=explode("|",$field);
            foreach ($tb as $newf){
                if(strpos($newf,":")>0){
                    $ss=explode(":",$newf);
                    $f[]="['$ss[0]']: '$ss[1]'";
                }else{
                    $f[]="['$newf']: value";
                }
            }
        }else{
            $f[]="['$field']: value";
        }

        $fftxt=@implode(",",$f);

        return "<div id=\"textfield-$id\" style='margin-top:6px'></div>
        <script>
            renderBigTextFieldSafe('textfield-$id', [
            {
                title: base64_decode('$title'),
                descriptionHTML: base64_decode('$explain'),
                defaultValue: '$value',
                placeholder: '$placeholder',
                $minAttr
                $maxAttr
                onSave: function(value) {
                    // POST on save (Enter or Tab key)
                    $.post('$page', { $fftxt })
                      .done(function (resp, textStatus, jqXHR) {
                        console.log('Text saved:', value, 'Response:', resp);
                        $after
                      })
                      .fail(function (jqXHR, textStatus, errorThrown) {
                        alert('POST failed: ' + errorThrown);
                      });
                }
            }
            ]).then(function (ctrls) {
                var ctrl = ctrls[0];
                if (!ctrl) return;
                ctrl.setDisabled(false);
            });
        </script>";
    }
    public function BigNetworkField($field,$title,$explain,$value,$placeholder='192.168.1.0/24',$after=null,$Privs=null,$allowIPv6=false):string{
        $id=md5(time().rand(1000,9999).$field);
        $f=array();
        $page=CurrentPageName();
        $title=base64_encode(html_entity_decode($this->_ENGINE_parse_body($title)));
        $explain=base64_encode($this->_ENGINE_parse_body($explain));

        if(strpos($field,"|")>0){
            $tb=explode("|",$field);
            foreach ($tb as $newf){
                if(strpos($newf,":")>0){
                    $ss=explode(":",$newf);
                    $f[]="['$ss[0]']: '$ss[1]'";
                }else{
                    $f[]="['$newf']: value";
                }
            }
        }else{
            $f[]="['$field']: value";
        }

        $fftxt=@implode(",",$f);

        return "<div id=\"netbox-$id\"></div>
        <script>
            renderBigNetworkListSafe('netbox-$id', [
            {
                title: base64_decode('$title'),
                descriptionHTML: base64_decode('$explain'),
                defaultValue: '$value',
                placeholder: '$placeholder',
                allowIPv6: ".($allowIPv6?"true":"false").",
                onSave: function(value) {
                    // POST on save (Enter key or blur if value changed and valid)
                    $.post('$page', { $fftxt })
                      .done(function (resp, textStatus, jqXHR) {
                        console.log('Network saved:', value, 'Response:', resp);
                        $after
                      })
                      .fail(function (jqXHR, textStatus, errorThrown) {
                        alert('POST failed: ' + errorThrown);
                      });
                }
            }
            ]).then(function (ctrls) {
                var ctrl = ctrls[0];
                if (!ctrl) return;
                ctrl.setDisabled(false);
            });
        </script>";
    }
    public function BigExplainCheckbox($field,$title,$explain,$after=null,$Privs=null):string{
        $id=md5(time().rand(1000,9999).$field);
        $page=CurrentPageName();
        $title=base64_encode($this->_ENGINE_parse_body($title));
        $explain=base64_encode($this->_ENGINE_parse_body($explain));

        if(strpos($field,"|")>0){
            $tb=explode("|",$field);
            foreach ($tb as $newf){
                if(strpos($newf,":")>0){
                    $ss=explode(":",$newf);
                    $f[]="['$ss[0]']: '$ss[1]'";
                }else{
                    $f[]="['$newf']: description";
                }
            }
        }else{
            $f[]="['$field']: description";
        }
        $fftxt=@implode(",",$f);
        return "<div id=\"explbox-$id\"></div>
        <script>
            renderBigExplainCheckboxListSafe('explbox-$id', [
            {
                title: base64_decode('$title'),
                descriptionHTML: base64_decode('$explain'),
                onDescriptionSave: function (description) {
                    // Post description change
                    $.post('$page', { $fftxt })
                      .done(function (resp, textStatus, jqXHR) {
                        console.log('Description saved:', description);
                        $after
                      })
                      .fail(function (jqXHR, textStatus, errorThrown) {
                        alert('POST failed: ' + errorThrown);
                      });
                }
            }
            ]);
        </script>";
    }


    public function table_form_field_js($js,$privilege=null,$buttons=array()):bool{
        $this->TABLE_FORM_PRIV=$privilege;
        if($privilege<>null){
            if(!$this->IsSecurity($privilege)){
                $this->switchjs="";
                return false;
            }
        }
        if(!is_array($buttons)){$buttons=array();}
        VERBOSE("TABLE_FORM_BTS = ".count($buttons),__LINE__);
        $this->TABLE_FORM_BTS=$buttons;
        $this->switchjs=$js;
        // switchjs n'est historiquement jamais rearme: les champs suivants continuent
        // d'afficher le crayon d'edition du dernier appel. On preserve ce comportement
        // (178 pages en dependent peut-etre) mais on ne rend cliquable QUE la ligne qui
        // suit immediatement, sinon un clic n'importe ou ouvrirait la mauvaise modale.
        $this->TABLE_FORM_JS_FRESH=true;
        return true;
    }

    public function table_form_field_install($field,$value,$json,$jsoff):bool{
        list($field,$explain,$none)=$this->table_form_split_label($field);

        if($value==0){
            $ico=ico_cd;
            $text="{install}";
            $text_rows="{not_installed}";
            $variant=null;
            $button="<button class='btn btn-primary btn-sm'
            OnClick=\"$json\"
            type='button'><i class='$ico'></i>&nbsp;$text</button>";

        }else{
            $ico=ico_trash;
            $text_rows="{installed}";
            $text="{uninstall}";
            $variant="on";
            $button="<button class='btn btn-danger btn-sm'
            OnClick=\"$jsoff\"
            type='button'><i class='$ico'></i>&nbsp;$text</button>";
        }

        // La colonne d'action porte ici un bouton d'installation, pas l'icone d'edition:
        // on sort du style scope .tfrm-act pour ne pas le repeindre en emeraude.
        $this->TABLE_FORM[]=$this->table_form_row(
            $this->table_form_chip($ico,$variant),
            $this->table_form_label($field,$explain),
            "<span class='tfrm-state tfrm-state-".($value==0?"off":"on")."'>$text_rows</span>&nbsp;&nbsp;$button",
            "",
            array("on"=>($value<>0)));
        return true;

    }

    public function table_form_button($button_text,$js=null,$security=null,$ico=null,$class="btn-primary"):bool{
        // Pied de carte table_form() (15 appels) : meme bouton que le pied de
        // form_outside() (systeme abt, 36 px, largeur du contenu ; avant, 300 px fixes).
        // Une icone passee a la place du droit ("...",$js,ico_x) est reconnue comme icone.
        if($ico==null && is_string($security) && preg_match('#^(fa[a-z]?|fa-[a-z]+)\s+fa-[a-z0-9-]+#',$security)){
            $ico=$security;
            $security=null;
        }
        if($ico==null){$ico="fa-regular fa-ellipsis";}
        if($ico==ico_trash){$class="btn-danger";}
        $role=$this->abt_page_role((string)$class,$ico,(string)$button_text);
        if($role=="p" && ($class=="btn-warning" || $class=="btn-info")){$role="s";}
        $map=array("p"=>"abt-p","s"=>"abt-s","x"=>"abt-x");
        $this->TABLE_FORM_BUTTONS[]= $this->button_inline($button_text,$js,$ico,$security,0,$map[$role]);
        return true;
    }

    public function table_form_field_buttons($buttons=array(),$security=null):bool{
        if(!is_array($buttons)){
            VERBOSE("NO ARRAY = FALSE",__LINE__);
            return false;
        }
        if(count($buttons)==0){
            VERBOSE("NO BUTTONS = FALSE",__LINE__);
            return false;
        }
        // Droit manquant : la barre reste visible, chaque bouton porte le cadenas et la
        // raison au survol (th_buttons_label), comme partout ailleurs. Avant, la barre
        // disparaissait sans explication. Un bouton pilote par ids:... garde son marqueur.
        if(!is_null($security) && !$this->IsSecurity($security)){
            VERBOSE("Locked ! $security",__LINE__);
            foreach($buttons as $k=>$b){
                if(!is_array($b)){continue;}
                if(isset($b[3]) && preg_match("#^ids:#",strval($b[3]))){continue;}
                $buttons[$k][3]=$security;
            }
        }
        $btns=$this->th_buttons($buttons);
        if(strlen($btns)==0){
            VERBOSE("BTN length()==0 FALSE",__LINE__);
            return false;
        }

        VERBOSE("class.template-admin.inc: OK....",__LINE__);
        // Barre d'actions: pleine largeur, alignee a droite comme le pied de liste.
        $this->TABLE_FORM[]="<div class='tfrm-row tfrm-full' style='display:block;text-align:right'>$btns</div>";
        return true;
    }

    public function table_form_field_info($field,$value,$ico=null,$buttons=array()):bool{
        if($ico==null){$ico="fa-regular fa-ellipsis";}
        list($field,$explain,$NoRight)=$this->table_form_split_label($field);
        $edit=null;
        if($this->switchjs<>null){
            $edit=$this->icon_edit_field($this->switchjs,$this->TABLE_FORM_PRIV);

        }
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $value=html_safe_fragment($value);
        $btns=$this->th_buttons($buttons);
        if(strlen($btns)>2){
            $value="$value&nbsp;&nbsp;$btns";
        }

        $this->TABLE_FORM[]=$this->table_form_row(
            $this->table_form_chip($ico),
            $this->table_form_label($field,$explain),
            $value,
            $NoRight?"":$edit,
            array("js"=>$this->table_form_rowjs()));
        return true;
    }

    public function table_form_field_warning($text):bool{
        $text=$this->_ENGINE_parse_body($text);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $text=html_safe_fragment($text);

        $this->TABLE_FORM[]=$this->table_form_row(
            $this->table_form_chip("fa-solid fa-triangle-exclamation","warn"),
            $this->table_form_label($text),
            "","",
            array("full"=>true,"class"=>"tfrm-warn"));
        return true;

    }

    public function table_form_field_button($field,$value,$ico=null):bool{
        if($ico==null){$ico="fa-regular fa-ellipsis";}

        $class="btn-primary";
        if($ico==ico_trash){
            $class="btn-danger";
        }
        if(preg_match("#^class:(.+?):(.+)#",$field,$re)){
            $class=$re[1];
            $field=$re[2];
        }
        list($field,$explain,$none)=$this->table_form_split_label($field);

        // Action d'une ligne de carte (30 appels) : meme bouton que les lignes de tableau
        // (button_inline, 28 px, largeur du libelle ; avant, 250 px fixes en pastille).
        // Corbeille = destructif, sinon neutre ; sans JS = desactive.
        // Le bouton CONSOMME le table_form_field_js() qui le precede : sans cela le JS
        // restait "frais" et la ligne suivante devenait cliquable avec l'action du bouton.
        $js=$this->table_form_rowjs();
        if($js===null){$js=$this->switchjs;}
        $button=$this->button_inline($value,$js,$ico,null,0,$class,"small");

        $this->TABLE_FORM[]=$this->table_form_row(
            $this->table_form_chip($ico),
            $this->table_form_label($field,$explain),
            $button,"");
        return true;
    }

    /**
     * Aide wiki d'une ligne de carte (table_form_field_text / _bool) : bouton icone
     * discret du systeme abt, avec libelle. Avant, une icone ambre dans un lien sans
     * libelle. L'URL est echappee pour le OnClick.
     */
    private function table_form_help_button($uri):string{
        $helpjs="s_PopUpFull('".req_js($uri)."','1024','900');";
        return "&nbsp;<button type=\"button\" class=\"btn abt abt-g abt-ico abt-sm\" aria-label=\"Help on Wiki\" title=\"Help on Wiki\" OnClick=\"$helpjs\"><i class=\"".ico_support."\" aria-hidden=\"true\"></i></button>";
    }

    public function table_form_field_bool($field,$value,$ico=null,$helpuri=null):bool{
        $Button=null;
        list($field,$explain,$NoRight)=$this->table_form_split_label($field);

        if(is_array($value)){
            if(isset($value["BUTTON"])){
                $array=$value;
                $value=$array["BUTTON"]["VALUE"];
                if($ico==null){$ico="fa-regular fa-ellipsis";}
                // Meme bouton de ligne que table_form_field_text() / _button() (28 px, neutre,
                // corbeille = destructif).
                $class="btn-primary";
                if($ico==ico_trash){$class="btn-danger";}
                $Button=$this->button_inline($array["BUTTON"]["LABEL"],$array["BUTTON"]["JS"],$ico,null,0,$class,"small");
            }
        }

        // Etat retenu AVANT mise en forme: c'est lui qui allume la ligne et la pastille.
        $IsOn=($this->BoolToInteger($value)==1);
        $value=$this->table_form_state_label($value);
        if($ico==null){$ico="fa-regular fa-ellipsis";}

        $edit=null;
        if($this->switchjs<>null){
            $edit=$this->icon_edit_field($this->switchjs,$this->TABLE_FORM_PRIV);

        }
        $icon_help=null;
        if($helpuri<>null){
            $icon_help=$this->table_form_help_button($helpuri);

        }

        if($Button<>null){$value.="&nbsp;&nbsp;$Button";}

        $this->TABLE_FORM[]=$this->table_form_row(
            $this->table_form_chip($ico,$IsOn?"on":null),
            $this->table_form_label($field,$explain),
            $value.$icon_help,
            $NoRight?"":$edit,
            array("on"=>$IsOn,"js"=>$this->table_form_rowjs()));
        return true;

    }


    function xPrivs(){
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=new $GLOBALS["CLASS_USERS"];
        // Compte dont le seul droit est AsMetaAdmin (client d'un infogerant, spec 2026-11-13
        // 5.10.6) : la porte generique ne s'ouvre QUE sur la liste des pages Meta, avant tout
        // autre critere. Masquer le menu n'est pas un controle d'acces.
        if(method_exists($users,"isMetaAdminOnly") && $users->isMetaAdminOnly()){
            return usersMenus::MetaOnlyPageAllowed(xprivs_script_page());
        }
        if(isset($_SESSION["MANAGE_CATEGORIES"])) {
            if (is_array($_SESSION["MANAGE_CATEGORIES"])) {
                if (count($_SESSION["MANAGE_CATEGORIES"]) > 0) {
                    return true;
                }
            }
        }
        if(count($users->NGINX_SERVICES)>0){
            $EnableNginx=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx");
            if($EnableNginx==1){
                return true;
            }
        }
        if(property_exists($users,"SIMPLE_ACLS")) {
            if (is_array($users->SIMPLE_ACLS)) {
                if (count($users->SIMPLE_ACLS) > 0) {
                    return true;
                }
            }
        }
        // Un perimetre de tenants SIEM donne acces a la console, exactement comme un perimetre de
        // services nginx juste au-dessus. Sans cette clause, l'operateur d'un tenant franchit la
        // page de login (fw.login.php::ifRights()) puis se fait refuser par CETTE fonction sur la
        // page d'accueil: authentifie, session construite, et un ecran d'erreur 500.
        //
        // property_exists() pour la meme raison que SIMPLE_ACLS: un objet usersMenus mis en cache
        // par une version anterieure ne porte pas encore l'attribut.
        if(property_exists($users,"SIEM_TENANTS")) {
            if (is_array($users->SIEM_TENANTS)) {
                if (count($users->SIEM_TENANTS) > 0) {
                    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaSiem"))==1){
                        return true;
                    }
                }
            }
        }


        if($users->AsDatabaseAdministrator){return true;}
        if($users->AsCertifsManager){return true;}
        if($users->AsFirewallManager){return true;}
        if($users->AsVPNManager){return true;}
        if($users->AsProxyMonitor){return true;}
        if($users->AsAnAdministratorGeneric){return true;}
        if($users->AsPostfixAdministrator){return true;}
        if($users->AsMessagingOrg){return true;}
        if($users->AsSambaAdministrator){return true;}
        if($users->AsDnsAdministrator){return true;}
        if($users->AsDnsMonitor){return true;}
        if($users->AsWebStatisticsAdministrator){return true;}
        if($users->AllowAddUsers){return true;}
        if($users->AllowAddGroup){return true;}
        if($users->AsDockerAny){return true;}
        if($users->AsDockerReverse){return true;}
        if($users->AsSquidPersonalCategories){return true;}
        if($users->AsHamrpAdmin){return true;}
        if($users->AsDansGuardianAdministrator){return true;}
        // Administrateur Artica Meta de tenants qui a AUSSI d'autres droits (non admis plus haut) :
        // seulement les pages de la liste Meta (meme predicat que le compte AsMetaAdmin seul).
        if(!empty($users->AsMetaAdmin) && usersMenus::MetaOnlyPageAllowed(xprivs_script_page())){return true;}
        if(isset($_SESSION["SQUID_DYNAMIC_ACLS"])){
            if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){return true;}
        }

        VERBOSE("NO PRIVILEGES !!!",__LINE__);
        return false;
    }
    public function DnsReader():bool{
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        if($GLOBALS["CLASS_USERS"]->AsAnAdministratorGeneric){
            return true;
        }
        if ($GLOBALS["CLASS_USERS"]->AsDnsAdministrator) {
            return true;
        }
        if ($GLOBALS["CLASS_USERS"]->AsDnsMonitor) {
            return true;
        }
        return false;
    }


    public function build_heads():string{
        include_once(dirname(__FILE__)."/class.heads.builder.inc");
        $this->title=$this->javascript_parse_text($this->title);
        $HTTP_X_ARTICA_SUBFOLDER="/";
        if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
            $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
        }


        $f[]="<!DOCTYPE html>";
        $f[]="<html lang=\"en\">";
        $f[]= "<head>";
        $f[]="    <title>$this->title</title>";
        $f[]="    <meta charset=\"utf-8\">";
        $f[]="    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
        if(function_exists("brand_favicon_tag")){
            $f[]="    ".brand_favicon_tag($HTTP_X_ARTICA_SUBFOLDER);
        }else{
            $f[]="    <link rel=\"icon\" href=\"{$HTTP_X_ARTICA_SUBFOLDER}ressources/templates/default/favicon.ico\" type=\"image/x-icon\" />";
        }
        $f[]="    <link href=\"{$HTTP_X_ARTICA_SUBFOLDER}css/pace-theme-big-counter.css\" rel=\"stylesheet\">";
        $HeadBuilder=new HeadsBuilder();
        $HeadBuilder->BuildLoader();
        $f[]=$HeadBuilder->f;
        $this->cssScript[]=$HeadBuilder->cssScript;

        $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
        $Ordonnancer=$this->RefreshInterval_Loadjs("sysmemory","fw.system.status.php","top-widget=yes",30);

        // Libelles traduits du message jaune "HTTP 0" de OutError() (default.js), repli anglais dans default.js.
        $AjaxI18n=array();
        foreach(array("http0_title"=>"{ajax_http0_title}","http0_text"=>"{ajax_http0_text}","http0_button"=>"{ajax_http0_button}") as $k=>$tok){
            $v=html_entity_decode($this->javascript_parse_text($tok),ENT_QUOTES,"UTF-8");
            // jeton non traduit (renvoye tel quel entre accolades) : repli anglais de default.js
            if($v==="" || strpos($v,"{")!==false){continue;}
            if(function_exists("mb_check_encoding") && !mb_check_encoding($v,"UTF-8")){$v=utf8_encode($v);}
            $AjaxI18n[$k]=$v;
        }
        $AjaxI18nJson=json_encode((object)$AjaxI18n,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        if(!is_string($AjaxI18nJson)){$AjaxI18nJson="{}";}
        $f[]="<script>";
        $f[]="\twindow.ArticaAjaxI18n=$AjaxI18nJson;";
        $f[]="\t$.address.strict(false);";
        $f[]="\t$Ordonnancer";
        $f[]="\tfunction ArtPing() {";
            $f[]="";
            $f[]="\t\t$.ajax({";
            $f[]="\t\t\ttype: \"GET\",";
            $f[]="\t\t\turl: 'fw.ping.php?SessionCookieLifetime=$SessionCookieLifetime',";
            $f[]="\t\t\tcache: false,";
            $f[]="\t\t\ttimeout: 60000,";
            $f[]="\t\t\tsuccess: function(html, status, req) {";
            $f[]="\t\t\t\tif (req.status == 200 ) {";
            $f[]="\t\t\t\t\t$(\"#faclock\").html(html);";
            $f[]="\t\t\t\t} else {";
            $f[]="\t\t\t\talert(status);";
            $f[]="\t\t\t\t}";
            $f[]="\t\t\t},";
            $f[]="\t\t\terror: function(req, status, error) {";
            $f[]="\t\t\t\t";
            $f[]="\t\t\t}";
            $f[]="\t\t});";
            $f[]="\t}";
            $f[]="\tvar timer = $.timer(function() { ArtPing(); });";
            $f[]="\ttimer.set({ time : 30000, autostart : true });";

        $f[]="</script>";
        $f[]="</head>";

        return @implode("\n", $f);
    }

    public function MonthToInteger($month):string{
        $month=strtoupper($month);
        $zText=$month;
        $zText=str_replace('JAN', '01',$zText);
        $zText=str_replace('FEB', '02',$zText);
        $zText=str_replace('MAR', '03',$zText);
        $zText=str_replace('APR', '04',$zText);
        $zText=str_replace('MAY', '05',$zText);
        $zText=str_replace('JUN', '06',$zText);
        $zText=str_replace('JUL', '07',$zText);
        $zText=str_replace('AUG', '08',$zText);
        $zText=str_replace('SEP', '09',$zText);
        $zText=str_replace('OCT', '10',$zText);
        $zText=str_replace('NOV', '11',$zText);
        $zText=str_replace('DEC', '12',$zText);
        return $zText;
    }

    public function logs_color($line=""):string{
        if($line==null){return "";}
        // Pentest 2026-11-04 : ligne de journal (donnee externe) inseree telle quelle (XSS stockee).
        $line=html_safe_fragment((string)$line);
        if(preg_match("#(mounted filesystem|\s+registered|\s+registering)#i", $line)){
            return "<span class='text-info'>$line</span>";
        }

        if(preg_match("#(Failed|Invalid|error|Traceback)#i", $line)){
            return "<span class='text-danger'>$line</span>";
        }
        if(preg_match("#Accepted password#i", $line)){
            return "<span class='text-success'>$line</span>";
        }
        if(preg_match("#(Server listening|Accepted keyboard-interactive)#i", $line)){
            return "<span class='text-success'>$line</span>";
        }

        if(preg_match("#(warning|restarting|disabled|removed|authentication failures|authentication failure|Did not receive identification string)#i", $line)){
            return "<span class='text-warning'>$line</span>";
        }
        return $line;

    }

    public function ArticaEdition(){
        $VERSION    = trim(@file_get_contents("VERSION"));
        $CURPATCH   = intval(@file_get_contents("SP/$VERSION"));
        $FINAL_TIME = 0;
        $GOLD       = False;
        $Eval       = null;
        if($CURPATCH>0){$VERSION="$VERSION&nbsp;Service Pack $CURPATCH";}

        if($GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()){
            return "v$VERSION Gold Edition";
        }


        if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){

            $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
            $LicenseINGP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseINGP"));
            if(isset($LicenseInfos["FINAL_TIME"])){$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}

            if(isset($LicenseInfos["license_number"])){
                if($GLOBALS["CLASS_SOCKETS"]->IsGoldKey($LicenseInfos["license_number"])){
                    $FINAL_TIME=0;
                    $Eval="&nbsp;({gold_license})";
                }
            }
            if($FINAL_TIME>0){
                $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
                if($ExpiresSoon<31){

                    $Eval="&nbsp;<span class='label label-warning'>{trial_mode}</span>";
                    $Eval=$this->td_href($Eval,"","Loadjs('fw.index.php?trial-explain=yes')");
                }
                if($ExpiresSoon<1){
                    $Eval="&nbsp;<span class='label label-danger'>{expired}</span>";
                }
            }

            if($LicenseINGP>0){
                if($LicenseINGP>time()) {
                    $Eval = "&nbsp;<span class='label label-warning'>{grace_period}</span>";
                }
            }
            /*
             * E o mesmo aviso numa maquina v2, cujo estado nao esta nos valores
             * acima: o LicenseInfos e o LicenseINGP sao da V1 e vem vazios, por
             * isso este rodape ficava sem etiqueta nenhuma -- "Enterprise
             * Edition" calado, mesmo com a licenca expirada e a correr nos sete
             * dias de graca.
             *
             * Este rodape aparece em TODAS as paginas: e o unico aviso que um
             * administrador ve sem ir a pagina da licenca, e e por isso que vale
             * a pena ter as etiquetas la.
             */
            $licxTag=$this->LICX_V2_TAG();
            if($licxTag!=""){$Eval=$licxTag;}
           return "v$VERSION Enterprise Edition$Eval";
        }

        return "v$VERSION Community Edition$Eval";
    }

    /**
     * LICX_V2_TAG: a etiqueta do rodape numa maquina de licenciamento v2.
     *
     * Devolve "" quando nao ha nada a dizer -- e e a resposta mais comum: uma
     * licenca boa e longe do fim nao precisa de etiqueta.
     *
     * As datas vem do estado publicado (a chave LicenseV2), o mesmo que o
     * CORP_LICENSE le. Nao se recalcula nada aqui: duas contas sobre as mesmas
     * datas saem de acordo no dia em que alguem mexer numa.
     */
    public function LICX_V2_TAG():string{
        if(!$GLOBALS["CLASS_SOCKETS"]->USE_LICENSE_V2()){return "";}
        $a=$GLOBALS["CLASS_SOCKETS"]->LICENSE_V2();
        if(!is_array($a)){return "";}
        $agora=time();
        $expira=isset($a["expire"]) ? intval($a["expire"]) : 0;
        $graca=isset($a["grace_until"]) ? intval($a["grace_until"]) : 0;
        $activar=isset($a["activate_until"]) ? intval($a["activate_until"]) : 0;
        if($activar>$agora){
            // Por activar: e este rodape aparece em todas as paginas, que e o
            // unico sitio onde um administrador ve o aviso sem ir a pagina da
            // licenca. Calar-se aqui era esconder o prazo mais curto que existe.
            return "&nbsp;<span class='label label-warning'>{lv2_pending_activation}</span>";
        }
        if($expira>0 AND $expira<=$agora AND $graca>$agora){
            return "&nbsp;<span class='label label-warning'>{grace_period}</span>";
        }
        if(isset($a["is_trial"]) AND boolval($a["is_trial"])){
            return "&nbsp;<span class='label label-warning'>{trial_mode}</span>";
        }
        return "";
    }

    public function GoToTimestamp($s):int{

        if(preg_match("#^(.+?)T([0-9:]+)\.#",$s,$re)){
            VERBOSE("$s = $re[1] $re[2]",__LINE__);
            return strtotime("$re[1] $re[2]");
        }

        if(preg_match("#^[0-9]+:[0-9]+:[0-9]+$#",$s,$re)){
            VERBOSE("$s = ".date("Y-m-d")." $s",__LINE__);
            return strtotime(date("Y-m-d")." $s");
        }

        VERBOSE("$s NO MATCH1",__LINE__);

        $d=$this->iso8601_to_timestamp($s);
        if($d>0){
            return $d;
        }
        $s = preg_replace('/\.(\d{6})\d*(?=[+-]\d{2}:\d{2}$)/', '.$1', $s);

        $dt = DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $s);
        if (!$dt) {
            return 0;
        }
        return $dt->getTimestamp();
    }
    private function iso8601_to_timestamp(string $date): int {
        // Detect ISO 8601 with fractional seconds and timezone
        if (preg_match(
            '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(\.\d+)?([+-]\d{2}:\d{2}|Z)$/',
            $date,
            $m
        )) {
            // Keep only microseconds (max 6 digits)
            $fraction = '';
            if (!empty($m[2])) {
                $fraction = substr($m[2], 0, 7); // ".123456"
            }

            $clean = $m[1] . $fraction . $m[3];

            try {
                return (new DateTime($clean))->getTimestamp();
            } catch (Exception $e) {
                return 0;
            }
        }

        return 0;
    }

    public function build_firewall($token="nothing=none"){
        VERBOSE("BuildFireWall OK", __LINE__);
        $HTTP_X_ARTICA_SUBFOLDER="/";
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
            $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
        }

        if(isset($_SERVER["HTTP_X_META_PREFIX"])){
            $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_META_PREFIX"]."/";
        }

        $f[]=$this->build_heads();
        $f[]="<body id=\"page-top\" class='pace-done mini-navbar'>";
        $f[]="<input type='hidden' id='cronos-id' value='0'>";
        $f[]="<input type='hidden' id='cronos-key' value='0'>";
        $f[]="<div class='pace pace-active' id='pace-progress' style='font-size: 88px'></div>";
        $f[]="</div><div id=\"wrapper\">";
        $f[]="\t<div id='left-barr'></div>";
        $f[]="\t\t<div id=\"page-wrapper\" class=\"gray-bg\">";
        $f[]="\t\t\t<div id='top-barr'></div>";
        $f[]="\t\t\t<div class=\"wrapper wrapper-content animated fadeInRight\" id='MainContent' style='min-height:610px;padding-top:3px;margin-top:-9px;margin-left:-9px'>";
        $f[]=$this->content;

        $f[]="\t\t\t</div>";
        $f[]="\t\t\t<div class=\"footer\" style='margin-left:-3px;margin-right:-3px'>";
        $f[]="\t\t\t\t<div class=\"pull-right\">";
        $f[]=$this->ArticaEdition();
        $UPTIME_TEXT=null;
        $UPTIME=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/UPTIME"));
        $EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
        if($UPTIME>0){
            $UPTIME_TEXT="&nbsp;&nbsp;|&nbsp;&nbsp;{uptime}:&nbsp;".distanceOfTimeInWords($UPTIME,time());
            if($EnableMunin==1){$UPTIME_TEXT="&nbsp;&nbsp;|&nbsp;&nbsp;{uptime}:&nbsp;".$this->td_href(distanceOfTimeInWords($UPTIME,time()),"{statistics}","Loadjs('fw.system.status.uptime.php')");}
        }
        $f[]=$UPTIME_TEXT;
        $f[]="\t\t\t\t</div>";
        $f[]="\t\t\t\t<div>";
        $f[]="\t\t\t\t\t<strong>Copyright</strong> Artica Tech &copy; 2004-" .date("Y");
        $f[]="\t\t\t\t</div>";
        $f[]="\t\t\t</div>";
        $f[]="\t\t</div>";
        $f[]="</div>";
        $f[]="<div id='right-sidebar'>
				<div class='sidebar-container' id='artica-right-sidebarr'>
			</div>
		</div>";
        $f[]="<div id='artica-modal-dialog'></div><div id='modal-windows' class='modal inmodal fade'  data-keyboard=\"false\" data-backdrop=\"static\">";
        $LEFT=false;
        $f[]="<script src=\"{$HTTP_X_ARTICA_SUBFOLDER}angular/js/plugins/pace/pace.min.js\"></script>";
        $f[]=@implode("\n",$this->cssScript);
        $f[]="<script src=\"{$HTTP_X_ARTICA_SUBFOLDER}angular/js/plugins/chartJs/Chart.min.4.5.1.js\"></script>";
        $f[]="<!-- Custom and plugin javascript -->";

        $f[]="<script>";
        $f[]="";

        $f[]="function RefreshSecondInterfaceBarrs(){";
        if($this->DynaAcls){
            $LEFT=true;
            $f[]="LoadAjaxSilent('left-barr','{$HTTP_X_ARTICA_SUBFOLDER}fw-left-menus.php?dynacls=yes');";
        }
        if(!$LEFT){
            $f[]="LoadAjaxSilent('left-barr','{$HTTP_X_ARTICA_SUBFOLDER}fw-left-menus.php?nothing=yes&$token');";
        }

        $me=CurrentPageName();
        $f[]="LoadAjaxSilent('top-barr','{$HTTP_X_ARTICA_SUBFOLDER}fw-top-bar.php?by=$me&$token');";
        $f[]="Loadjs('{$HTTP_X_ARTICA_SUBFOLDER}fw.icon.top.php');";
        $f[]="}";

        $f[]="RefreshSecondInterfaceBarrs();";
        $f[]="</script>";
        VERBOSE("BuildFireWall OK", __LINE__);
        $tpl=new templates();
        return $tpl->_ENGINE_parse_body(@implode("\n",$f));

    }

    public function widget_style2($background="lazur-bg",$icon=ico_database,$text=null){
        //navy-bg/lazur-bg/blue-bg/red-bg/yellow-bg/gray-bg/black-bg
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $text=html_safe_fragment($text);

        //fa-thumbs-up
        return "
		<div class=\"widget style1 $background\">
			<div class=\"row\">
				<div class=\"col-xs-4\">
					<i class=\"$icon fa-5x\"></i>
				</div>
				<div class=\"col-xs-8 text-right\">
					$text
				</div>
			</div>
		</div>";

    }

    public function widget_micro($background="lazur-bg",$icon=ico_database,$text="",$subtext=""):string{

        if($background=="green-bg"){
            $background="navy-bg";
        }
        $color="white";
        if($background=="grey-bg"){
            $color="black";
        }
        if($background=="gray-bg"){
            $color="black";
        }
        $btnclass["navy-bg"]="btn-primary";
        $btnclass["lazur-bg"]="btn-info";
        $btnclass["red-bg"]="btn-danger";
        $btnclass["yellow-bg"]="btn-warning";
        $btnclass["gray-bg"]="btn-default";
        $btnclass["black-bg"]="btn-default";
        $btnclass["grey-bg"]="btn-default";

        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $text=html_safe_fragment($text);
        $subtext=html_safe_fragment($subtext);
        if(strlen($subtext)>2){
            $subtext="<small style='color:$color;font-size:11px;line-height: 2px
'>$subtext</small>";
        }

        $html="<div class=\"widget style1 $background\">
                        <div class=\"row vertical-align\">
                            <div class=\"col-xs-3\">
                                <i class=\"$icon fa-3x\"></i>
                            </div>
                            <div class=\"col-xs-9 text-right\">
                                <h2 class=\"font-bold\">$text</h2>$subtext
                            </div>
                        </div>
                    </div>";

        return $this->_ENGINE_parse_body($html);
    }

    // $extra : HTML deja bati pose sous la valeur (explication, bouton portant son propre
    // privilege via button_autnonome()). $button, lui, reconstruit le bouton SANS privilege.
    public function widget_style1($background="lazur-bg",$icon=ico_database,$title=null,$text=null,$button=array(),$extra=""):string{
        //navy-bg/lazur-bg/blue-bg/red-bg/yellow-bg/gray-bg/black-bg
        //fa-thumbs-up parse_button
        $btn="";

        if($background=="green-bg"){
            $background="navy-bg";
        }
        if($background=="bg-red"){
            $background="red-bg";
        }


        $btnclass["blue-bg"]="blue";
        $btnclass["navy-bg"]="btn-primary";
        $btnclass["lazur-bg"]="btn-info";
        $btnclass["red-bg"]="btn-danger";
        $btnclass["bg-red"]="btn-danger";
        $btnclass["yellow-bg"]="btn-warning";
        $btnclass["gray-bg"]="btn-default";
        $btnclass["black-bg"]="btn-default";
        $btnclass["grey-bg"]="btn-default";
        // Bouton fantome: reserve la hauteur quand l'appelant n'en fournit pas.
        // (Il trainait ici un </div> surnumeraire qui fermait la colonne trop tot et
        //  laissait fuir une balise fermante dans le reste de la page.)
        $btn="<div style='margin-top:30px;visibility:hidden' id='0'><button type='button' class='btn btn-blue'>&nbsp;Modify</button></div>";
        if(is_array($button)){
            if(count($button)>0){
                $sbtncalss="";
                if(!isset($button["color"])){
                    $sbtncalss=$btnclass[$background];
                }
                $btn=$this->parse_button($button,$sbtncalss);
            }
        }
        if(strlen((string)$extra)>0){
            // Le contenu fourni tient la hauteur : le bouton fantome n'a plus lieu d'etre.
            if(strpos($btn,"visibility:hidden")!==false){$btn="";}
            $btn=$extra.$btn;
        }
        $svcbar_class=$this->svcbar_class($background);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $title=html_safe_fragment($title);
        $text=html_safe_fragment($text);

        if($svcbar_class==null){
            // Couleur inconnue -> rendu historique, aucun effet vitre applique.
            return "<div class=\"widget style1 $background\" style='min-height: 172px'>
			<div class=\"row\">
				<div class=\"col-xs-4\">
					<i class=\"$icon fa-5x\"></i>
				</div>
				<div class=\"col-xs-8 text-right\">
					<span> $title </span>
					<h2 class=\"font-bold\">$text</h2>
					$btn
				</div>
			</div>
		</div>";
        }

        // min-height conservee: les appelants alignent ces cartes en grille.
        return $this->svctile_css()."<div class=\"svcbar $svcbar_class\" style='min-height: 172px'>
			<div class=\"row\">
				<div class=\"col-xs-4\">
					<div class=\"svcbar-ico\"><i class=\"$icon\"></i></div>
				</div>
				<div class=\"col-xs-8 text-right\">
					<span class=\"svcbar-explain\">$title</span>
					<h2 class=\"svcbar-number\">$text</h2>
					$btn
				</div>
			</div>
		</div>";

    }

    public function decode_mime_string($subject){


        if(function_exists("imap_mime_header_decode")){
            $elements = imap_mime_header_decode($subject);
            if(isset($elements[0])){
                if($elements[0]->text<>null){
                    $subject=htmlentities($elements[0]->text);
                    $subject=replace_accents($subject);
                    $subject=str_replace("'", "`", $subject);
                    return $subject;
                }

            }
        }
        if(function_exists("mb_convert_encoding")) {
            $subject= mb_convert_encoding($subject, 'UTF-8');
            $subject=str_replace("'", "`", $subject);
            return $subject;
        }

        if(function_exists("imap_utf8")) {
            $subject=imap_utf8($subject);
            $subject=str_replace("'", "`", $subject);
            return $subject;
        }
        return $subject;
    }

    function Round($size=64,$color="#1ab394"):string{

        return "<span aria-hidden=\"true\" 
        style=\"flex: 0 0 auto; width: {$size}px; height: {$size}px; border-radius: 50%; display: grid;
         place-items: center; border: 2px solid $color; 
         background: $color; 
         box-shadow: rgba(26, 179, 148, 0.5) 0px 10px 22px -10px;
          transition: background-color 0.2s, border-color 0.2s, box-shadow 0.2s, transform 0.02s;\">
              <svg viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" 
              stroke-width=\"3\" stroke-linecap=\"round\" 
              stroke-linejoin=\"round\" 
              style=\"width: 34px; height: 34px; opacity: 1; transform: scale(1); 
              transition: opacity 0.18s, transform 0.18s; color: rgb(255, 255, 255);\">
              </svg>
          </span>";
    }
    private static $ArticaRefreshI18nDone=false;
    /**
     * Libelles traduits du bandeau "donnee figee" affiche par ArticaRefresh
     * quand un rafraichissement echoue de maniere repetee. Emis une seule
     * fois par requete ; les libelles anglais de default.js servent de repli.
     * Le decodage d'entites est necessaire : le badge est rendu via
     * content:attr() en CSS, qui n'interprete pas les entites HTML.
     */
    private function RefreshIntervalI18n():string{
        if(self::$ArticaRefreshI18nDone){return "";}
        self::$ArticaRefreshI18nDone=true;
        $stale=addslashes(html_entity_decode($this->javascript_parse_text("{stale_data}"),ENT_QUOTES,"UTF-8"));
        $stopped=addslashes(html_entity_decode($this->javascript_parse_text("{refresh_stopped}"),ENT_QUOTES,"UTF-8"));
        return "if(window.ArticaRefresh){ArticaRefresh.i18n.stale='$stale';ArticaRefresh.i18n.stopped='$stopped';}";
    }

    /**
     * Construit l'URL rafraichie en conservant la normalisation historique
     * de fw.status.interval.php ($StripPage n'est vrai que pour le mode JS,
     * ou Loadjs() coupait aussi sur ".php?").
     */
    private function RefreshIntervalUrl($page,$Query,$StripPage=false):string{
        if(preg_match("#^\?(.+)#",$Query,$re)){$Query=$re[1];}
        if($StripPage){
            if(preg_match("#\.php\?(.+)#",$Query,$re)){$Query=$re[1];}
        }
        return "$page?$Query";
    }

    /**
     * Rafraichit periodiquement $div avec le HTML renvoye par $page?$Query.
     * Moteur ArticaRefresh (default.js) : un seul timer par couple (div,url),
     * pas de recouvrement, pas de reecriture du DOM si le HTML est identique,
     * pause si l'onglet est cache ou si le div est hors ecran.
     */
    public function RefreshInterval_js($div,$page,$Query,$Interval=3):string{
        $Interval=intval($Interval);
        if($Interval<3){$Interval=3;}
        $url=$this->RefreshIntervalUrl($page,$Query,false);
        return $this->RefreshIntervalI18n()."ArticaRefresh('".addslashes($div)."','".addslashes($url)."',$Interval);";
    }

    /**
     * Idem, mais $page?$Query renvoie du JavaScript (content-type
     * application/x-javascript) evalue a chaque tick. $div sert de sentinelle :
     * sa disparition arrete le timer.
     */
    public function RefreshInterval_Loadjs($div,$page,$Query,$Interval=3):string{
        $Interval=intval($Interval);
        if($Interval<3){$Interval=3;}
        $url=$this->RefreshIntervalUrl($page,$Query,true);
        if(strpos($url,"jQueryLjs=")===false){
            $url=$url."&jQueryLjs=yes";
        }
        return $this->RefreshIntervalI18n()."ArticaRefresh.js('".addslashes($div)."','".addslashes($url)."',$Interval);";
    }


    public function CLEAN_POST($exclude=null,$encode=false):array{
        $_SESSION["SESSARTTIMEOUT"] = time();
        $array=array();
        foreach ($_POST as $key=>$val){
            if($key==$exclude){continue;}
            $valsqz=url_decode_special_tool($val);
            if($encode){
                $valsqz=$this->utf8_encode($valsqz);
            }
            $valsql=sqlite_escape_string2($valsqz);
            // Pentest 2026-11-04 (injection SQL) : le NOM du champ POST devient un nom
            // de colonne ; seul un identifiant simple entre dans les fragments SQL.
            if(!preg_match('#^[A-Za-z_][A-Za-z0-9_]*$#',(string)$key)){$_POST[$key]=trim(url_decode_special_tool($val));continue;}
            $array["FIELDS_ADD"][]="`$key`";
            $array["VALUES_ADD"][]="'$valsql'";
            $array["EDIT"][]="`$key`='$valsql'";
            $_POST[$key]=trim(url_decode_special_tool($val));
        }

        return $array;
    }

    #[Pure]
    public function utf8_decode($string):string{
        if(is_null($string)){
            return "";
        }
        if(function_exists("mb_convert_encoding") ){
            return strval(mb_convert_encoding($string, 'ISO-8859-1'));
        }

        if(function_exists("utf8_decode")){
            $ret=utf8_decode($string);
            return strval($ret);
        }
        return strval(mb_convert_encoding($string, 'ISO-8859-1'));
    }

    public function utf8_encode($string):string{


        if(version_compare(phpversion(),"8.2",">=")){
            return strval(mb_convert_encoding($string, 'UTF-8'));
        }
        if(function_exists("utf8_encode")){
            return utf8_encode($string);
        }
        if(!function_exists("mb_convert_encoding")) {
            return $string;
        }

        return strval(mb_convert_encoding($string, 'UTF-8'));
    }
    public function CLEAN_GET_XSS():bool{
        foreach ($_GET as $key=>$val){
            $_GET[$key]=$this->CLEAN_BAD_XSS(url_decode_special_tool($val));
            }


        return true;
    }

    public function CLEAN_POST_XSS($exclude=null):array{
        if(!isset($_POST)){return array();}
        if(count($_POST)==0){return array();}
        $array=array();
        foreach ($_POST as $key=>$val){
            if($key<>$exclude){
                $valsql=sqlite_escape_string2($this->CLEAN_BAD_XSS(url_decode_special_tool($val)));
                // Pentest 2026-11-04 (injection SQL) : nom de champ = nom de colonne.
                if(preg_match('#^[A-Za-z_][A-Za-z0-9_]*$#',(string)$key)){
                $array["FIELDS_ADD"][]="`$key`";
                $array["VALUES_ADD"][]="'$valsql'";
                $array["EDIT"][]="`$key`='$valsql'";
                }
                if(strtolower($key)=="password"){
                    $_POST[$key]=url_decode_special_tool($val);
                    continue;
                }
                $_POST[$key]=$this->CLEAN_BAD_XSS(url_decode_special_tool($val));
            }
        }

        return $array;
    }

    public function MYSQL_ENCOSE($array){
        $newarray=array();
        foreach ($array as $values){
            $newarray[]="'{$values}'";
        }
        return $newarray;
    }
    public function SAVE_Admin_track($admintrcktext){
        $this->CLEAN_POST();
        $admintrack=array();

        foreach ($_POST as $key=>$val){
                $admintrack[]="$key: ".admin_tracks_mask_value($key,$val);
        }

        if($admintrcktext<>null){
            admin_tracks($admintrcktext ."(". @implode(", ",$admintrack).")");
        }

    }

    public function SAVE_POSTs($exclude=null,$admintrcktext=null):array{
        $this->CLEAN_POST($exclude);
        $admintrack=array();
        $keys=array();
        foreach ($_POST as $key=>$val){
            if($key<>$exclude){
                $admintrack[]="$key: ".admin_tracks_mask_value($key,$val);
                $keys["Keys"][]= array("key"=>$key,"value"=>$val);

            }
        }
        if($admintrcktext<>null){
            admin_tracks($admintrcktext ."(". @implode(", ",$admintrack).")");
        }
        reset($_POST);
        $payload = $keys;
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/setinfo",$payload));
        if(!$json){return $_POST;}
        return $_POST;
    }

    // Title shown on the progress card when the caller gives none: the action read from the
    // last segment of the route ("/unbound/restart" -> "Restarting service"). Before, all
    // ~900 callers showed the same "Running", which says nothing about what is running.
    public function framework_route_title($cmd):string{
        $verbs=array(
            "restart"=>"{restarting_service}","restarthup"=>"{restarting_service}",
            "reconfigure-restart"=>"{restarting_service}",
            "start"=>"{starting_service}","stop"=>"{stopping_service}","off"=>"{stopping_service}",
            "reload"=>"{reloading_service}",
            "reconfigure"=>"{reconfiguring_service}",
            "install"=>"{installing_service}","uninstall"=>"{uninstalling_service}",
            "enable"=>"{enabling}","disable"=>"{disabling}",
            "update"=>"{updating}","compile"=>"{building_configuration}","build"=>"{building_configuration}",
            "scan"=>"{scanning}","sync"=>"{synchronizing}",
        );
        $path=explode("?",(string)$cmd,2);
        $path=trim($path[0],"/");
        $last=strtolower(substr(strrchr("/$path","/"),1));
        if(isset($verbs[$last])){return $verbs[$last];}
        return "{ComputerRunning}";
    }

    public function framework_buildjs($cmd,$progress,$logfile,$divid,$after=null,$onfailed=null,$confirm=null,$privileges=null,$title=null):string{
        if(!is_null($onfailed)) {
            if (preg_match("#style:high#", $onfailed)) {
                $ARRAY["BIG"] = true;
            }
        }

        if($privileges<>null){
            if(!$this->IsSecurity($privileges)){
                return "Loadjs('fw.progress.php?badprivs=yes')";
            }
        }
        $progress=trim($progress);
        $logfile=trim($logfile);
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/$progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/$logfile";
        $ARRAY["CMD"]=$cmd;
        // Checked again by fw.progress.php right before the route runs (the signature
        // proves the page built the task, not that the person running it may).
        if($privileges<>null){$ARRAY["PRIV"]=$privileges;}
        if($title===null || trim($title)===""){$title=$this->framework_route_title($cmd);}
        $ARRAY["TITLE"]=$this->_ENGINE_parse_body($title);
        if($after<>null) {
            $ARRAY["AFTER"] = $after;
        }
        if($onfailed<>null) {
            $ARRAY["AFTER_FAILED"] =$onfailed;
        }
        $prgress=base64_encode(serialize($ARRAY));
        $js="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=$divid')";
        if($confirm<>null){
            $confirm=$this->_ENGINE_parse_body($confirm);
            $js="Loadjs('fw.progress.php?".fw_progress_confirm_args($confirm,$js)."')";
        }

        return $js;
    }


    function SESSION_POST($exclude=null){
        foreach ($_POST as $key=>$val){
            if($key<>$exclude){
                $_SESSION[$key]=$val;
            }
        }
        $_POST=array();
    }

    function CLEAN_BAD_CHARSNET($string){
        if(is_null($string)){return "";}
        $string=str_replace(['|','+','²','^','°', '\\', '*',' ','(',')','{','}','[',']','%','$',';',
            ',','?','=','#','&','`','"','>','<',"@",'�?',"'"], '', $string);
        return trim(strtolower($string));


    }
    public function CLEAN_BAD_XSS_SQL($string):string{
        if(is_null($string)){return "";}
        $string=str_replace(['|','+','²','^','°', '\\', '(',')','{','}','%','$',';',
            '#','&','`','"','�?',"'"], '', $string);
        return trim($string);
    }


    public function CLEAN_BAD_XSS($string):string{
        if(is_null($string)){return "";}
        $string=str_replace(['|','+','²','^','°', '\\', '(',')','{','}','%','$',';',
            '#','&','`','"','>','<','�?',"'"], '', $string);
        return trim($string);
    }

    function CLEAN_BAD_CHARMAIL($string){
        if(is_null($string)){return "";}
        $string=str_replace(['|','+','²','^','°', '\\', '/', '*',' ','(',')','{','}','[',']','%','$',';',
            ',','?','=','#','&','`','"','>','<',"'",'�?'], '', $string);
        return trim(strtolower($string));
    }

    public function MYSQL_POST(){
        foreach ($_POST as $eth=>$none){
            $_POST[$eth]=mysql_escape_string2($none);
        }
        reset($_POST);
    }

    public function time_to_date($xtime,$time=false){
        // Le tiret comme indice de "c'est une date" est beaucoup trop laxiste:
        // toute chaine en contenant un entrait ici, y compris du HTML --
        // icon_nothing() vaut "<i class='fa fa-minus'></i>" et le "fa-minus"
        // suffisait. new DateTime() n'etant pas garde, l'exception remontait
        // NON ATTRAPEE et la page entiere repondait 500 avec un corps vide
        // (fw.member.ad.edit.php, compte AD jamais connecte -- 08/2026).
        // Cette methode est partagee par ~1200 pages: un seul appelant fautif
        // ne doit pas pouvoir tuer la page. On restreint donc au cas utile
        // (chaine non numerique) et on echoue proprement sur "-", qui est deja
        // ce que templates::time_to_date() renvoie pour une entree invalide.
        if(!is_numeric($xtime) && strpos((string)$xtime,"-")>0){
            try{
                $date = new DateTime((string)$xtime);
                $xtime = $date->getTimestamp();
            }catch(\Exception $e){
                return "-";
            }
        }

        if(!isset($GLOBALS["TPL"])){$GLOBALS["TPL"]=new templates();}
        return $this->_ENGINE_parse_body($GLOBALS["TPL"]->time_to_date($xtime,$time));
    }

    public function time_to_week($xtime){
        $monday = strtotime("last monday",$xtime);
        $monday = date('w', $monday)==date('w',$xtime) ? $monday+7*86400 : $monday;
        $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
        $this_week_sd = date("Y-m-d",$monday);
        $this_week_ed = date("Y-m-d",$sunday);
        return array($this_week_sd,$this_week_ed);
    }

    public function time_diff_min($xtime){
        return $this->time_diff_min_interval(time(),$xtime);
    }

    public function time_diff_min_interval_hours($fromtime,$totime){
        $mins=$this->time_diff_min_interval($fromtime,$totime);
        $results=$mins/60;
        VERBOSE("$fromtime - $totime = $mins / 60 = $results Hours");
        return round($results);
    }

    public function time_diff_min_interval($fromtime,$totime):int{
        if($fromtime>$totime){$difference = ($fromtime - $totime);}else {$difference = ($totime - $fromtime);}
        $results=intval(round($difference/60));
        if($results<0){$results=1;}
        VERBOSE("$fromtime - $totime = $difference = $results minutes");
        return $results;
    }

    public function ACTIVE_DIRECTORY_LDAP_CONNECTIONS():array{
        if(!class_exists("ActiveDirectory")){
            include_once("/usr/share/artica-postfix/ressources/class.ActiveDirectory.inc");
        }
        $MAIN                       = array();
        $active                     = new ActiveDirectory();
        $EnableKerbAuth             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $ActiveDirectoryConnections = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADLicenseRemoved"))==1){$ActiveDirectoryConnections=array();}

        if($EnableKerbAuth==1){
            $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
            if($array["LDAP_SERVER"]==null){
                if($array["fullhosname"]<>null){$array["LDAP_SERVER"]=$array["fullhosname"];}
            }
            if(!isset($array["LDAP_DN"])){
                if(isset($array["WINDOWS_SERVER_ADMIN"])){$array["LDAP_DN"]=$array["WINDOWS_SERVER_ADMIN"];}
            }
            if(!isset($array["LDAP_PORT"])){$array["LDAP_PORT"]=null;}

            if(!isset($array["LDAP_PASSWORD"])){
                if(isset($array["WINDOWS_SERVER_PASS"])){$array["LDAP_PASSWORD"]=$array["WINDOWS_SERVER_PASS"];}
            }
            if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
            if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
            if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
            if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
            if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
            if($array["LDAP_SSL"]==null){$array["LDAP_SSL"]=$active->ldap_ssl;}
            if(preg_match("#^(.+?)@(.+?)@$#",trim($array["LDAP_DN"]),$re)){$array["LDAP_DN"]="$re[1]@$re[2]";}
            if(intval($array["LDAP_SSL"])==1){$array["LDAP_SERVER"]="ldaps://{$array["LDAP_SERVER"]}:636";}
            $md5=md5(serialize($array));
            $MAIN[$md5]=$array;
        }

        foreach ($ActiveDirectoryConnections as $index=>$ligne) {
            if (!is_numeric($index)) {continue;}
            if ($EnableKerbAuth == 1) {if ($index == 0) {continue;}}
            if (!isset($ligne["LDAP_PORT"])) {$ligne["LDAP_PORT"] = 389;}
            if (!isset($ligne["LDAP_SSL"])) {$ligne["LDAP_SSL"] = 0;}
            if (!isset($ligne["WINDOWS_SERVER_ADMIN"])) {
                if (isset($ligne["LDAP_DN"])) {
                    $ligne["WINDOWS_SERVER_ADMIN"] = $ligne["LDAP_DN"];
                }
            }

            $md5=md5(serialize($ligne));
            $MAIN[$md5]=$ligne;

        }

        return $MAIN;


    }
    public function kernel_binary_ver():int{
        $kernver = php_uname("r");
        if(preg_match("#^([0-9\.]+)\+deb.*?#",$kernver,$m)){
            $kernver = $m[1];
        }
        $kernver = str_replace( "+deb13", "",$kernver);
        $kernver = str_replace("+deb14", "",$kernver);
        $kernver = str_replace("+deb15", "",$kernver);
        $kernver = str_replace( "+deb16", "",$kernver);
        $kernver = str_replace( "+deb17", "",$kernver);
        $kernver = str_replace( "+deb18", "",$kernver);
        $kernver = str_replace( "+deb19", "",$kernver);
        $kernver = str_replace( "+deb20", "",$kernver);
        VERBOSE("([0-9\.-]+)-amd64 -> $kernver",__LINE__);

        if (preg_match("#^([0-9\.]+)$#", $kernver, $re)) {
            $kernbin = str_replace(".", "", $kernver);
            $kernbin = str_replace("-", "", $kernbin);
            VERBOSE("Current Kernel: $kernver -- $kernbin", __LINE__);
            return intval($kernbin);

        }

        if (!preg_match("#([0-9\.-]+)-amd64#", $kernver, $re)) {
            VERBOSE("ALERT !!!!! Current Kernel: NO MATCHES", __LINE__);
            return 0;
        }
        $kernver2 = $re[1];
        $kernbin = str_replace(".", "", $kernver2);
        $kernbin = str_replace("-", "", $kernbin);
        VERBOSE("Current Kernel: $kernver -- $kernbin", __LINE__);
        return intval($kernbin);
    }

    private function _translateVersionString($version):string{
        if(preg_match("#(.+?)-#",$version,$re)){$version=$re[1];}
        if(preg_match("#(.+?)\.$#",$version,$re)){$version=$re[1];}
        $curver_array=explode(".",$version);
        if(!isset($curver_array[1])){$curver_array[1]=0;}
        if(!isset($curver_array[2])){$curver_array[2]=0;}
        if(!isset($curver_array[3])){$curver_array[3]=0;}

        foreach ($curver_array as $index=>$vv){
            $vv=intval($vv);
            if(strlen($vv)==1){
                $vv="00$vv";
                $curver_array[$index]=$vv;
                continue;
            }
            if(strlen($vv)==2){
                $vv="0$vv";
                $curver_array[$index]=$vv;
                continue;
            }
            $curver_array[$index]=$vv;
        }
        return @implode(".",$curver_array);

    }

    function LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$key,$curver):int{
        $AVAILABLE_KEY=0;
        if(!isset($UPDATES_ARRAY[$key])){return 0;}
        if(trim($curver)==null){return 0;}
        VERBOSE("$key: $curver",__LINE__);


        $curver=$this->_translateVersionString($curver);
        $curverbin=str_replace(".","",$curver);
        $AVAILABLE=0;
        VERBOSE("$key: Current version $curver -- $curverbin",__LINE__);
        krsort($UPDATES_ARRAY[$key]);
        foreach ($UPDATES_ARRAY[$key] as $vbin=>$array) {
            $VERSION=$array["VERSION"];
            $VERSION_recovered=$this->_translateVersionString($VERSION);
            VERBOSE("$key: avai: $VERSION - recovered:$VERSION_recovered check $curver",__LINE__);
            $vc = intval(str_replace(".","",$VERSION_recovered));
            if ($vc <= $curverbin) {
                continue;
            }
            if ($vc > $AVAILABLE) {
                $AVAILABLE_KEY = $vbin;
                $AVAILABLE = $vc;
            }
        }
        VERBOSE("LAST $key VERSION = $AVAILABLE_KEY");
        return $AVAILABLE_KEY;
    }

    function NGINX_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_NGINX"])){return 0;}
        $APP_NGINX_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_NGINX",$APP_NGINX_VERSION);
    }
    function PDNS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_PDNS"])){return 0;}
        $PDNS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_PDNS",$PDNS_VERSION);
    }
    /**
     * Return array if a new version is available
     * @param array $function <p>
     * The function name, as a string.
     * </p>
     * @return array current version and available version <i>Product</i> new version exists and is a
     * , empty otherwise.
     * Array must include
     * NOTIF_ARRAY(Array("UPDATES_ARRAY"=>$UPDATES_ARRAY,"TOKEN_UPDATE_ARRAY"=>"APP_GO_SHIELD_SERVER",
     *  "TOKEN_VER"=>"APP_GO_SHIELD_VERSION","TOKEN_ENABLED"=>"Go_Shield_Server_Enable"));
     * </p>
     * <p>
     */
    function NOTIF_ARRAY($MAINSETTINGS=array(),$HideNewVerSwitch=true):array{
        $ERR                = array();
        if(!isset($MAINSETTINGS["TOKEN_ENABLED"])){$MAINSETTINGS["TOKEN_ENABLED"]="";}
        $UPDATES_ARRAY      = $MAINSETTINGS["UPDATES_ARRAY"];
        $TokenCurVer        = $MAINSETTINGS["TOKEN_VER"];
        $TokenCurEnabled    = $MAINSETTINGS["TOKEN_ENABLED"];
        $UPDATES_ARRAY_KEY  = $MAINSETTINGS["TOKEN_UPDATE_ARRAY"];
        if($TokenCurEnabled<>"ALWAYS") {
            if ($TokenCurEnabled <> null) {
                $EnableFeature = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($TokenCurEnabled));
            } else {
                $EnableFeature = 1;
            }
        }else{
            $EnableFeature=1;
        }
        if($EnableFeature==0) {return $ERR;}
        $CUR_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($TokenCurVer);
        $TokenHide             = "Hide{$UPDATES_ARRAY_KEY}VerIco$CUR_VERSION";
        if($HideNewVerSwitch) {
            $HideNewer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($TokenHide));
            if ($HideNewer == 1) {
                return $ERR;
            }
        }
        $AVAILABLE_VER = $this->ICON_TOP_AVAILABLE_VERSION($UPDATES_ARRAY,$UPDATES_ARRAY_KEY,$CUR_VERSION);
        if ($AVAILABLE_VER >0 ) {
            $NewVer = $UPDATES_ARRAY[$UPDATES_ARRAY_KEY][$AVAILABLE_VER]["VERSION"];
            return array("CUR_VER"=>$CUR_VERSION,"NEW_VER"=>$NewVer,"HIDE_TOKEN"=>$TokenHide);
        }
        return $ERR;
    }

    function ICON_TOP_AVAILABLE_VERSION($UPDATES_ARRAY,$ARRAY_KEY,$CURVER):int{
        if(!isset($UPDATES_ARRAY[$ARRAY_KEY])){return 0;}
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$ARRAY_KEY,$CURVER);
    }
    function DNSDIST_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_DNSDIST"])){return 0;}
        $MAINVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_DNSDIST",$MAINVERSION);
    }

    function APP_NGINX_CONSOLE_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_NGINX_CONSOLE"])){return 0;}
        $MAINVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_VERSION");
        VERBOSE("APP_NGINX_CONSOLE: Current version = $MAINVERSION",__LINE__);
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_NGINX_CONSOLE",$MAINVERSION);
    }
    function REDIS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_REDIS_SERVER"])){return 0;}
        $MAINVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_REDIS_SERVER",$MAINVERSION);
    }
    function WP_CLI_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if (!isset($UPDATES_ARRAY["WP_CLIENT"])) {return 0;}
        $MAINVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WP_CLIENT_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"WP_CLIENT",$MAINVERSION);
    }
    function CICAP_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_C_ICAP"])){return 0;}
        $CicapVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_C_ICAP",$CicapVersion);
    }
    function SYSLOG_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_SYSLOGD"])){return 0;}
        $APP_RSYSLOGD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_SYSLOGD",$APP_RSYSLOGD_VERSION);
    }


    function PRDPROXY_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_RDPPROXY"])){return 0;}
        $APP_RDPPROXY_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_RDPPROXY",$APP_RDPPROXY_VERSION);
    }

    //
    function MEMCACHED_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_MEMCACHED"])){return 0;}
        $CrVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MEMCACHED_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_MEMCACHED",$CrVersion);
    }
    function CLAMAV_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_CLAMAV"])){return 0;}
        $clamd_version = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_CLAMAV",$clamd_version);
    }
    function HAPROXY_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_HAPROXY";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }

    function MONIT_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_MONIT";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MONIT_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }
    function NAGIOS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_NAGIOS_CLIENT";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NAGIOS_CLIENT_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }
    function AUTOFS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_AUTOFS";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_AUTOFS_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }
    function SAMBA_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_SAMBA";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }
    function NETDATA_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        $MAIN_KEY="APP_NETDATA";
        if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return 0;}
        $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NETDATA_VERSION");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,$MAIN_KEY,$master_version);
    }

    function SQUID_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_SQUID"])){return 0;}
        $realsquidversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_SQUID",$realsquidversion);
    }
    function SQUID6_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY):int{
        if(!isset($UPDATES_ARRAY["APP_SQUID6"])){return 0;}
        $realsquidversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
        return $this->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY,"APP_SQUID6",$realsquidversion);
    }

    public function time_key_10mn(){

        $minutesTo10["00"]="00";
        $minutesTo10["01"]="00";
        $minutesTo10["02"]="00";
        $minutesTo10["03"]="00";
        $minutesTo10["04"]="00";
        $minutesTo10["05"]="00";
        $minutesTo10["06"]="00";
        $minutesTo10["07"]="00";
        $minutesTo10["08"]="00";
        $minutesTo10["09"]="00";
        $minutesTo10["10"]="10";
        $minutesTo10["11"]="10";
        $minutesTo10["12"]="10";
        $minutesTo10["13"]="10";
        $minutesTo10["14"]="10";
        $minutesTo10["15"]="10";
        $minutesTo10["16"]="10";
        $minutesTo10["17"]="10";
        $minutesTo10["18"]="10";
        $minutesTo10["19"]="10";
        $minutesTo10["20"]="20";
        $minutesTo10["21"]="20";
        $minutesTo10["22"]="20";
        $minutesTo10["23"]="20";
        $minutesTo10["24"]="20";
        $minutesTo10["25"]="20";
        $minutesTo10["26"]="20";
        $minutesTo10["27"]="20";
        $minutesTo10["28"]="20";
        $minutesTo10["29"]="20";
        $minutesTo10["30"]="30";
        $minutesTo10["31"]="30";
        $minutesTo10["32"]="30";
        $minutesTo10["33"]="30";
        $minutesTo10["34"]="30";
        $minutesTo10["35"]="30";
        $minutesTo10["36"]="30";
        $minutesTo10["37"]="30";
        $minutesTo10["38"]="30";
        $minutesTo10["39"]="30";
        $minutesTo10["40"]="40";
        $minutesTo10["41"]="40";
        $minutesTo10["42"]="40";
        $minutesTo10["43"]="40";
        $minutesTo10["44"]="40";
        $minutesTo10["45"]="40";
        $minutesTo10["46"]="40";
        $minutesTo10["47"]="40";
        $minutesTo10["48"]="40";
        $minutesTo10["49"]="40";
        $minutesTo10["50"]="50";
        $minutesTo10["51"]="50";
        $minutesTo10["52"]="50";
        $minutesTo10["53"]="50";
        $minutesTo10["54"]="50";
        $minutesTo10["55"]="50";
        $minutesTo10["56"]="50";
        $minutesTo10["57"]="50";
        $minutesTo10["58"]="50";
        $minutesTo10["59"]="50";
        $min=$minutesTo10[date("i")];
        return date("Y-m-d-").intval(date("H"))."-".$min;
    }

    public function cluster_text(){

        $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
        if($PowerDNSEnableClusterMaster==1){
            return $this->_ENGINE_parse_body("{this_server_is_a_master_cluster}");
        }
        $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
        if($PowerDNSEnableClusterSlave==1){
            $PowerDNSClusterMasterAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
            $PowerDNSClusterMasterPort=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterPort"));
            $addr="$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort";
            $this_server_is_a_client_cluster=$this->_ENGINE_parse_body("{this_server_is_a_client_cluster}");
            $this_server_is_a_client_cluster=str_replace("%s",$addr,$this_server_is_a_client_cluster);
            return $this_server_is_a_client_cluster;
        }

        return null;

    }

    public function TimeToDays($timestamp):int{
        $t=time();
        if($timestamp<$t){
            return 0;
        }
        $reste_seconds  = intval($timestamp) -intval($t);
        $days           = intval(intval($reste_seconds) / (3600*24));
        if($days<0){$days=0;}
        return intval($days);
    }

    public function _ENGINE_parse_body($html):string{
        if(!isset($GLOBALS["TPL"])){$GLOBALS["TPL"]=new templates();}
        if(is_array($html)){$html=@implode("\n", $html);}
        return strval($GLOBALS["TPL"]->_ENGINE_parse_body($html));
    }
    public function javascript_parse_text($html){
        if(!isset($GLOBALS["TPL"])){$GLOBALS["TPL"]=new templates();}
        return $GLOBALS["TPL"]->javascript_parse_text($html);

    }
    public function add_decimal($version){
        $versions=explode(".",$version);
        if(!isset($versions[2])){return $version;}
        if(intval($versions[2])<10){$versions[2]="{$versions[2]}0";}
        return @implode(".",$versions);
    }

    public function uptime(){
        $str   = @file_get_contents('/proc/uptime');
        $num   = floatval($str);
        $num   = round($num);
        $num = intdiv($num, 60);
        $mins  = $num % 60;      $num = intdiv($num, 60);
        $hours = $num % 24;      $num = intdiv($num, 24);
        $days  = $num;
        $t=array();

        if($days>2555) {
            $t[]="7 {years}";
            $days=$days-2555;
        }

        if($days>2190) {
            $t[]="6 {years}";
            $days=$days-2190;
        }
        if($days>1825) {
            $t[]="5 {years}";
            $days=$days-1825;
        }
        if($days>1460) {
            $t[]="4 {years}";
            $days=$days-1460;
        }
        if($days>1095) {
            $t[]="3 {years}";
            $days=$days-1095;
        }
        if($days>720) {
            $t[]="2 {years}";
            $days=$days-720;
        }
        if($days>360) {
            $t[]="1 {year}";
            $days=$days-360;
        }
        if($days>330) {
            $t[]="11 {months}";
            $days=$days-210;
        }
        if($days>300) {
            $t[]="10 {months}";
            $days=$days-210;
        }
        if($days>270) {
            $t[]="9 {months}";
            $days=$days-210;
        }
        if($days>240) {
            $t[]="8 {months}";
            $days=$days-210;
        }
        if($days>210) {
            $t[]="7 {months}";
            $days=$days-210;
        }
        if($days>180) {
            $t[]="6 {months}";
            $days=$days-180;
        }
        if($days>90) {
            $t[]="3 {months}";
            $days=$days-90;
        }
        if($days>60) {
            $t[]="2 {months}";
            $days=$days-60;
        }
        if($days>30) {
            $t[]="1 {month}";
            $days=$days-60;
        }
        if($days>15) {
            $t[]="2 {weeks}";
            $days=$days-15;
        }
        if($days>7) {
            $t[]="1 {week}";
            $days=$days-7;
        }
        if($days==1) {
            $t[]="$days {day}";
        }
        if($days>1){
            $t[]="$days {days}";
        }
        if($hours==1){
            $t[]="$hours {hour}";
        }
        if($hours>1){
            $t[]="$hours {hours}";
        }
        if($mins==1){
            $t[]="$mins {minute}";
        }
        if($mins>1){
            $t[]="$mins {minutes}";
        }
        return @implode(", ",$t);

    }

    public function masquerading_alreadyset($interface){
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM pnic_bridges 
            WHERE masquerading=1 AND enabled=1 AND nic_to='$interface'");
        if(intval($ligne["ID"])>0){return true;}


        $ligne=$q->mysqli_fetch_array("SELECT ID FROM firehol_masquerade 
                                WHERE nic='$interface' AND enabled=1");
        if(intval($ligne["ID"])>0){return true;}

        if(!class_exists("system_nic")){
            include_once(dirname(__FILE__)."/class.system.nics.inc");
        }
        $znic=new system_nic($interface);
        if($znic->firewall_masquerade==1){
            return true;
        }

        return false;
    }

    public function MacToVendor($MacAddr):string{

        if(!preg_match("#^([a-z0-9]+):([a-z0-9]+):([a-z0-9]+)#",$MacAddr,$re)){return "";}
        $mac_prefix     = strtoupper("{$re[1]}{$re[2]}{$re[3]}");
        if(isset($GLOBALS["MacToVendor"][$mac_prefix])){return strval($GLOBALS["MacToVendor"][$mac_prefix]);}

        $memcached      =new lib_memcached();
        $Vendor         = $memcached->getKey("HARDWAREVENDOR:$mac_prefix");
        if($memcached->MemCachedFound){
            if(strlen(trim($Vendor))>2) {
                $GLOBALS["MacToVendor"][$mac_prefix] = $Vendor;
                return $Vendor;
            }
        }

        $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $ligne2         = $q->mysqli_fetch_array("SELECT description FROM dhcpd_MacsList WHERE MacID='$mac_prefix' LIMIT 1");

        if(!is_array($ligne2) OR !isset($ligne2["description"])){
            $ligne2["description"]="";
        }
        if(!is_null($ligne2["description"])) {
            $description = trim($ligne2["description"]);
            if (strlen($description) > 108) {
                $description = substr($description, 0, 105) . "...";
            }
        }
        $GLOBALS["MacToVendor"][$mac_prefix] = $description;
        $memcached->saveKey("HARDWAREVENDOR:$mac_prefix",$description,1800);
        return strval($GLOBALS["MacToVendor"][$mac_prefix] );
    }
public function FormatSeconds($seconds):string {
    $hours = floor($seconds / 3600);
    $minutes = floor(((int)$seconds % 3600) / 60);
    $remainingSeconds = (int)$seconds % 60;

    $result = '';

    if ($hours > 0) {
        $result .= $hours . ' {hour' . ($hours > 1 ? 's}' : '}') . ' ';
    }

    if ($minutes > 0) {
        $result .= $minutes . ' {minute' . ($minutes > 1 ? 's}' : '}') . ' ';
    }

    if ($remainingSeconds > 0) {
        $result .= $remainingSeconds . ' {second' . ($remainingSeconds > 1 ? 's}' : '}');
    }

    return trim($result);
}

    public function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

    public function icon_up($js=null,$security=null){

        if(!$this->IsSecurity($security)){$js=null;}

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $js=null;
            }
        }


        if($js==null){return
            "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" ><i class='fa fa-arrow-up' style='color:#c2ccd1'></i></button>";}

        $md=md5($js);
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"trup$md(this);$js\" >
        <i class='fa fa-arrow-up' style='color:#18a689'></i></button>


		<script>
		function trup$md(e){
			var row = $(e).parents(\"tr:first\");
         	row.insertBefore(row.prev());
        }
         </script>
		";

    }
    public function icon_password($js=null,$security=null):string{
        $ico="fa-solid fa-user-lock";
        if(!$this->IsSecurity($security)){$js=null;}
        if($js==null){return "<button type='button' class='btn btn-white btn-bitbucket'>
        <i class='$ico' style='color:#c2ccd1'></i></button>";}
        $md=md5($js);
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"trdnw$md(this);$js\" >
        <i class='$ico' style='color:#18a689'></i></button>
        ";

    }

    public function icon_down($js=null,$security=null):string{
        $ico="fa fa-arrow-down";
        if(!$this->IsSecurity($security)){$js=null;}
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $js=null;
            }
        }


        if($js==null){return "<button type='button' class='btn btn-white btn-bitbucket'>
        <i class='$ico' style='color:#c2ccd1'></i></button>";}

        $md=md5($js);
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"trdnw$md(this);$js\" >
        <i class='$ico' style='color:#18a689'></i></button>
            <script>
                function trdnw$md(e){
		            var row = $(e).parents(\"tr:first\");
		            row.insertAfter(row.next());
                }
            </script>
        ";


        //return "<a href=\"#\" OnClick=\"$js\">$icon</a>";
    }
    public function icon_arrow_right($js=null){
        $icon="<i class='fa fa-arrow-right'></i>";
        if($js==null){return $icon;}
        return "<a href=\"#\" OnClick=\"$js\">$icon</a>";

    }


    public function table_menu($actions=array(),$CurrentPage=null,$addons=null,$forceFunc=null){
        $id=md5(time());
        $t=time();
        $search_text="{search_items}";

        $f[]="\t<div style='margin-bottom:15px;margin-top:5px'><table style='width:100%'>";
        $f[]="\t<tr>";
        $f[]="\t<td style='vertical-align:middle;width:99%'>";
        $f[]="\t<div class=\"input-group\">
				<input type=\"text\" class=\"form-control\" id='$id' value=\"\" placeholder=\"$search_text\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
				<span class=\"input-group-btn\">
					<button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
				</span>
			</div>";
        $f[]="\t</td>";
        $f[]="\t<td style='vertical-align:middle;' nowrap>";
        $f[]="\t<table style='width:100%'><tr>";
        $f[]="\t<td style='vertical-align:middle;padding-left:8px;width:1%'>";
        $f[]="\t\t\t<a href=\"javascript:blur();\" OnClick=\"ss$t()\">";
        $f[]="\t\t\t\t<i class=\"fas fa-sync\"></i>";
        $f[]="\t\t\t</a>";
        $f[]="\t</td>";



        foreach ($actions as $array){

            $icon=$array["ICON"];
            $js=$array["JS"];
            $js=str_replace("%func",base64_encode("ss$t()"),$js);
            $f[]="\t<td style='padding-left:8px;vertical-align: middle;width:1%'>";
            $f[]="\t\t\t<a href=\"javascript:blur();\" OnClick=\"$js\">";
            $f[]="\t\t\t\t<i class=\"$icon\"></i>";
            $f[]="\t\t\t</a>";
            $f[]="</td>";
        }


        $f[]="</tr></table>";
        $f[]="</td></tr></table></div>";
        $f[]="<div id='div-$t' style='background-color: #ffffff;margin:0;padding:0'></div>";
        $f[]="<script>";
        $f[]="\tfunction Search$t(e){";
        $f[]="\t\tif(!checkEnter(e) ){return false;}";
        $f[]="\tss$t();";
        $f[]="\t}
	
function ss$t(){
	var ss=encodeURIComponent(document.getElementById('$id').value);
	LoadAjax('div-$t','$CurrentPage?search='+ss+'&function=ss$t{$addons}');
}
	
function Start$t(){
	ss$t();
}
Start$t();";

if($forceFunc<>null){
    $f[]="function $forceFunc(){  ss$t();}";
}
        
        $f[]="</script>";
        return @implode("\n",$f);

    }


    public function table_ibox($js,$title){
        $id=md5(microtime(true).$js);
        $html[]="<div class='ibox' style='margin-top:10px'>";
        $html[]="	<div class='ibox-title'>
						<div class=\"ibox-tools\">
							<a href=\"#\" class=\"collapse-link\" OnClick=\"Collapse$id()\" >
								<i class=\"fa fa-chevron-up\" id='ico-$id'></i>
							</a>
							<a href=\"#\" class=\"collapse-link\" OnClick=\"Load$id();\" jsFunctionTable='Load$id'>
								<i class=\"fal fa-sync-alt\" id='ico-$id'></i>
							</a>
						</div>";
        $html[]="	<H3 style='font-weight:bolder'>$title</H3>";
        $html[]="	</div>";
        $html[]="<div class='ibox-content' id='table-$id'></div>";
        $html[]="</div>";
        $html[]="<script>";
        $html[]="function Collapse$id(){";
        $html[]="\t$('#table-$id').slideToggle('fast');";
        $html[]="\t$('#ico-$id').toggleClass('fa-chevron-up fa-chevron-down');";
        $html[]="}";
        $html[]="function Load$id(){";
        $html[]="\tLoadAjaxTiny('table-$id','$js');";
        $html[]="}";
        $html[]="Load$id();";

        $html[]="";

        $html[]="</script>";

        return @implode("\n", $html);
    }

    public function UnBoundVersionArray(){
        $ARRAY=array();
        $UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");
        $version=explode(".",$UnboundVersion);
        $ARRAY["MAJOR"]=0;
        $ARRAY["MINOR"]=0;
        $ARRAY["REVISION"]=0;
        if(isset($version[0])) {
            $ARRAY["MAJOR"] = intval($version[0]);
        }
        if(isset($version[1])) {
            $ARRAY["MINOR"] = intval($version[1]);
        }
        if(isset($version[2])) {
            $ARRAY["REVISION"] = $version[2];
        }
       return $ARRAY;
    }

    public function ClickMouse($js,$bold=false){
        $initial="this.style.fontWeight='initial'";
        if(preg_match("#pop:(.+)#",$js,$re)){
            $js="s_PopUpFull('{$re[1]}','1024','900');";
        }
        if($bold){
            $initial="this.style.fontWeight='bold'";
        }

        $ClickMouse="onMouseOver=\"this.style.cursor='pointer';this.style.fontWeight='bold';this.style.textDecoration='underline'\" 
        OnMouseOut=\"this.style.cursor='default';$initial;this.style.textDecoration='none'\"
        OnClick=\"$js\"
        ";
        return $ClickMouse;
    }

    public function DropDownMultiple($array,$CurrentValue,$placeholder,$Loadjs,$width=0){
        $stylewidth="";
        if($width>0){
            $stylewidth="width: '{$width}px'";
        }
        $placeholder=$this->_ENGINE_parse_body($placeholder);
        $t=md5(microtime(true).$placeholder.$Loadjs."Multiple");
        $f[]="<div class=\"btn-group\">";
        $f[]="<select data-placeholder=\"{$placeholder}\" id='DropDown$t' class=\"form-control\" multiple=\"multiple\">";
        if (is_null($CurrentValue)){
            $CurrentValue="";
        }
        $SEL=array();
        if( strpos($CurrentValue,",")>0){
            $tb=explode(",",$CurrentValue);
            foreach ($tb as $zz){
                $SEL[]="'".req_js($zz)."'";
            }
        }else{
            $SEL[]="'".req_js($CurrentValue)."'";
        }
        $defaults=@implode(",",$SEL);

        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        foreach ($array as $value=>$text){

            $f[]="<option value=\"".req_html($value)."\">".req_option($text)."</option>";
        }
        $f[]="</select>";
        $f[]="</div>";
        $f[]="<script>";
        $f[]="$(\"#DropDown$t\").select2({".$stylewidth."});";
        $f[]="$(\"#DropDown$t\").val([$defaults]).trigger(\"change\");";
        $f[]="$(\"#DropDown$t\").on('change', function() {";
        $f[]="var selectedValue = encodeURIComponent($(this).val());";
        $f[]="let js='".js_url_safe($Loadjs)."';";
        $f[]="let uri = js.replace(\"%s\", selectedValue);";
        $f[]="Loadjs(uri)";
        $f[]="});";
        $f[]="</script>";

        return @implode("\n",$f);
    }
    public function DropDown($array,$CurrentValue,$placeholder,$Loadjs){
        $placeholder=$this->_ENGINE_parse_body($placeholder);
        $t=md5(microtime(true).$placeholder.$Loadjs);
        $f[]="<div class=\"btn-group\">";
        $f[]="<select data-placeholder=\"{$placeholder}\" id='DropDown$t' class=\"form-control\">";
        if (is_null($CurrentValue)){
            $CurrentValue="";
        }

        if($CurrentValue==""){
            $f[]="<option value=\"\" selected>{select}</option>";
        }


        foreach ($array as $value=>$text){
            $selected="";
            if($value==$CurrentValue){
                $selected="selected";
            }
            // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
            $f[]="<option value=\"".req_html($value)."\" $selected>".req_option($text)."</option>";
        }
        $f[]="</select>";
        $f[]="</div>";
        $f[]="<script>";
        $f[]="$(\"#DropDown$t\").select2({ allowClear: true });";
        $f[]="$(\"#DropDown$t\").on('change', function() {";
        $f[]="var selectedValue = encodeURIComponent($(this).val());";
        $f[]="let js='".js_url_safe($Loadjs)."';";
        $f[]="let uri = js.replace(\"%s\", selectedValue);";
        $f[]="Loadjs(uri)";
        $f[]="});";
        $f[]="</script>";

        return @implode("\n",$f);
    }

    public function DropDownButton($text,$array,$class="btn-primary"){
        $text=$this->_ENGINE_parse_body($text);
        $f[]="<div class=\"btn-group\">";
        $f[]="<button data-toggle=\"dropdown\" class=\"btn $class dropdown-toggle\">$text <span class=\"caret\"></span></button>";
        $f[]="<ul class=\"dropdown-menu\">";

        foreach ($array as $text=>$js){
            if($text=="<hr>"){
                $f[]="<li class=\"divider\"></li>";
                continue;
            }
            $f[]="\t<li><a href=\"javascript:blur()\" OnClick=\"$js\">$text</a></li>";

        }

        $f[]="</ul>";
        $f[]="</div>";
        return @implode("\n", $f);

    }

    public function GetCloudLastversion($APP_KEY,$Curver){
        VERBOSE("[$APP_KEY]",__LINE__);
        $ZCurver        = explode(".",$Curver);
        $CountOfCurver  = count($ZCurver);
        VERBOSE("Current version: $Curver",__LINE__);



        $IntCurVer      = intval(@implode("",$ZCurver));
        if($IntCurVer==0){
            VERBOSE("$APP_KEY=[$Curver] -> CountOf($CountOfCurver) ==0 , Aborting",__LINE__);
            return array(0,0);
        }
        $NEWMAIN        = array();
        $FINAL          = array();

        $UPDATES_ARRAY               = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("v4softsRepo")));
        if(!isset($UPDATES_ARRAY[$APP_KEY])){
            VERBOSE("UPDATES_ARRAY[$APP_KEY] ->!isset",__LINE__);
            return array(0,0);
        }

        $ISARRAY=$UPDATES_ARRAY[$APP_KEY];
        krsort($ISARRAY);
        foreach ($ISARRAY as $integ=>$array){
            $zVERSION=explode(".",$array["VERSION"]);
            $CountOfVersion=count($zVERSION);
            //VERBOSE("$integ: {$array["VERSION"]} $CountOfVersion!=$CountOfCurver",__LINE__);

            if($CountOfVersion!=$CountOfCurver){
                $xVer=array();
                for($i=0;$i<$IntCurVer;$i++){
                    $xVer[]=$zVERSION[$i];
                }
            }else{
                $xVer=explode(".",$array["VERSION"]);
            }

            $BinVer=intval(@implode("",$xVer));
            //VERBOSE("$BinVer: {$array["VERSION"]}",__LINE__);
            $NEWMAIN[$BinVer]=$array["VERSION"];
        }

        krsort($NEWMAIN);

        foreach ($NEWMAIN as $bin=>$ver){
            $FINAL[]=$bin;

        }
        $Latest=0;

        if(isset($FINAL[0])) {
            $Latest = $FINAL[0];
        }
        $Latesta=$Latest/100;
        $Latestb=$IntCurVer/100;
        if($Latesta>10){
            VERBOSE("Latest: remove leading 0 in $Latest",__LINE__);
            if(preg_match("#^([0-9]+)0$#",$Latest,$re)){
                $Latest=intval($re[1]);
            }
        }


        VERBOSE("Latest: $Latest question is : $Latest>$IntCurVer $Latesta/$Latestb ?",__LINE__);
        if($Latest>$IntCurVer){
            if(!isset($NEWMAIN[$Latest])){
                return array(0,0);
            }
            return array($Latest,$NEWMAIN[$Latest]);
        }
        return array(0,0);

    }

    public function icon_nothing($js=null){
        $icon="<i class='fa fa-minus'></i>";
        if($js==null){return $icon;}
        return html_trusted("<a href=\"#\" OnClick=\"$js\">$icon</a>");

    }

    public function icon_list($js=null,$security=null,$tooltip=null){
        $pic="fad fa-list-ul";
        return $this->__icon_defaults($pic,$js,$security,$tooltip);

    }

    private function __icon_defaults($pic,$js,$security,$tooltip=null):string{
        $color="#18a689";
        if(preg_match("#color:(.+?):(.+)#",$pic,$re)){
            $color=$re[1];
            $pic=$re[2];
        }
        $btclass="btn btn-default btn-bitbucket";
        if(!$this->IsSecurity($security)){return "
        <button type='button' 
            class='$btclass' OnClick=\"Loadjs('fw.error.noprivs.php');\" >
            <i class='$pic' style='color:#c2ccd1'></i>
        </button>";}

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                return "<button type='button' 
                class='$btclass' OnClick=\"blur()\" >
                <i class='$pic' style='color:#c2ccd1'></i></button>";
            }
        }
        $id=md5("$pic,$js,$security,$tooltip".microtime(false));
        list($ztool,$none)=$this->Tooltips($id,$tooltip);

        if($js==null){
            $color="#c2ccd1";
            $js="Blur();";
            $ztool="";
        }


        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
            return "<button type='button' class='$btclass' $jsav >
            <i class='$pic' style='color:$color' id='$id' $ztool></i>
        </button>";
        }
        $md=md5("$jsav$pic".microtime(true));
        $jsav="OnClick=\"function$md();\"";
        $html[]="<button type='button' class='$btclass' $jsav id='$id' $ztool>";
        $html[]="<i class='$pic' style='color:$color' id='btn-$md'></i>";
        $html[]="</button>";
        $html[]="<script>";
        $html[]="function functionStop$md(){";
        $html[]="\tif ( document.getElementById('btn-$md') ){";
        $html[]="\tdocument.getElementById('btn-$md').classList.remove('fa-spin');";
        $html[]="\t}";
        $html[]="}";
        $html[]="function functionRun$md(){";
        $html[]="\t$js;";
        $html[]="\tsetTimeout(\"functionStop$md()\",1000);";
        $html[]="}";
        $html[]="function function$md(){";
        $html[]="\tdocument.getElementById('btn-$md').classList.add('fa-spin');";
        $html[]="\tsetTimeout(\"functionRun$md()\",200);";
        $html[]="}";
        $html[]="</script>";
        return @implode("\n",$html);
    }

    public function icon_copy($js=null,$security=null){
        $pic="fa fa-copy";
        return $this->__icon_defaults($pic,$js,$security);
    }

    public function icon_select($js=null,$security=null){
        $pic="fas fa-arrow-right";
        return $this->__icon_defaults($pic,$js,$security);
    }

    public function FATAL_ERROR_SHOW_128($text,$js=null){
        $text=$this->_ENGINE_parse_body($text);
        $button=null;
        if($js<>null){
            $button="<p><div style='text-align:right;margin:30px'>".$this->button_autnonome("{click_to_fix}",$js,"fas fa-tools")."</div></p>";
        }

        $f=$this->div_warning("$text$button");
        $data=$this->_ENGINE_parse_body($f);
        if(strpos("$data", "%email%")){
            $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
            $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
            if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
            if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
            $data=str_replace("%email%", $LicenseInfos["EMAIL"], $data);
        }

        return $data;

    }

    private function td_ipaddr_DecisionIP($ip,$js):string{

        if(!isset($GLOBALS["td_ipaddr_DecisionIP"])){
            $GLOBALS["td_ipaddr_DecisionIP"]=time();
        }
        $GLOBALS["td_ipaddr_DecisionIP"]++;
        $id=md5($js.microtime(true). $GLOBALS["td_ipaddr_DecisionIP"]);
        $icon="<i class='fa fa-arrow-right'></i>";
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $ipurl=req_url(trim(strip_tags((string)$ip)));
        $ip=html_safe_fragment($ip);
        return html_trusted($this->_ENGINE_parse_body("<span id=\"ip_$id\"
      style=\"border-bottom:1px solid #ccc;font-weight:bold;cursor:pointer;\"
      onmouseover=\"this.style.color='#337AB7'\"
      onmouseout=\"this.style.color=''\">$ip</span>

<div class='ip-context-menu' id=\"menu_ip_$id\"
     style=\"display:none;background:#fff;border:1px solid #ccc;
            box-shadow:0 4px 8px rgba(0,0,0,0.1);min-width:160px;z-index:2147483647;\">
    <div style=\"padding:8px;cursor:pointer;\"
         onclick=\"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$ipurl')\">$icon {details}</div>
    <div style=\"padding:8px;cursor:pointer;\"
         onclick=\"s_PopUp('https://dashboard.decisionip.io/ip?ip=$ipurl','1024','800')\">
         $icon DecisionIP Intelligence
    </div>
</div>

<script>

(function(){
    var span = document.getElementById(\"ip_$id\");
    var menu = document.getElementById(\"menu_ip_$id\");

    // move to body once (table-safe)
    if (menu.parentNode !== document.body) {
        document.body.appendChild(menu);
    }

    function closeMenu(){
        menu.classList.remove(\"show\");
    }

    function showMenu(){
        var rect = span.getBoundingClientRect();

        menu.style.position = \"fixed\";
        menu.classList.add(\"show\");

        var left = rect.left;
        var top  = rect.bottom + 6;

        // measure after visible (so offsetWidth/Height are correct)
        var mw = menu.offsetWidth || 220;
        var mh = menu.offsetHeight || 80;

        if (left + mw > window.innerWidth - 6) left = window.innerWidth - mw - 6;
        if (top + mh > window.innerHeight - 6) top = rect.top - mh - 6;

        if (left < 6) left = 6;
        if (top < 6) top = 6;

        menu.style.left = left + \"px\";
        menu.style.top  = top + \"px\";
    }

    // toggle on IP click
    span.addEventListener(\"click\", function(e){
        e.stopPropagation();
        if (menu.classList.contains(\"show\")) {
            closeMenu();
        } else {
            showMenu();
        }
    });

    // prevent \"outside click\" close when clicking inside the menu
    menu.addEventListener(\"click\", function(e){
        e.stopPropagation();
        // close after choosing an item
        closeMenu();
    });

    // close on outside click
    document.addEventListener(\"click\", function(){
        closeMenu();
    }, true);

    // close on scroll/resize
    window.addEventListener(\"scroll\", closeMenu, true);
    window.addEventListener(\"resize\", closeMenu, true);
})();
</script>"));
    }

    public function td_href($text,$tooltip="{click_to_edit}",$js=null,$security=null):string{
        if(!is_null($text)) {
            $text = trim($text);
        }
        if(is_null($text)){
            return "";
        }

        if(!is_null($js)) {
            if (strpos($js, "zoom-ip-js=") > 0) {
                if (!isset($GLOBALS["CLASS_SOCKETS_EnableDecisionIP"])) {
                    $GLOBALS["CLASS_SOCKETS_EnableDecisionIP"] = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDecisionIP"));
                }
                if ($GLOBALS["CLASS_SOCKETS_EnableDecisionIP"] == 1) {
                    return $this->td_ipaddr_DecisionIP($text, $js);
                }
            }
        }

        if(is_null($tooltip)){$tooltip="";}
        if($text==null){$text="?????";}
        $fromtooltip=$tooltip;
        $memmd=md5("$text$tooltip$js");
        // Pentest 2026-11-04 : sortie du template, gardee telle quelle par html_safe_fragment().
        if(isset($GLOBALS[__CLASS__][__FUNCTION__][$memmd])){return html_trusted($GLOBALS[__CLASS__][__FUNCTION__][$memmd]);}
        $id=md5("$text$tooltip$js".microtime(true).rand(0,time()));
        $text=$this->_ENGINE_parse_body($text);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $text=html_safe_fragment($text);
        $border="border-bottom:1px solid #CCCCCC;";
        if(preg_match("#fa-exclamation-square#",$text)){$border=null;}


        if(!$this->IsSecurity($security)){
            $js="";
            $border="";
            $tooltip="<strong>{ERROR_NO_PRIVS}</strong><hr>$tooltip";
        }

        if(strlen($tooltip)<3){
            $f[]="<span OnMouseOver=\"this.style.cursor='pointer';this.style.color='#337AB7'\"";
            $f[]="OnMouseOut=\"this.style.cursor='default';this.style.color=''\"";
            $f[]="style=\"{$border}font-weight:bold\"";
            $f[]="OnClick=\"$js\" id=\"$id\">$text</span>";
            $GLOBALS[__CLASS__][__FUNCTION__][$memmd]=@implode(" ", $f);
            return html_trusted(@implode(" ", $f));

        }
        $tooltip=$this->_ENGINE_parse_body($tooltip);
        // data-content est relu en HTML par le popover : filtre puis encodage d'attribut.
        $tooltip=req_html(html_safe_fragment($tooltip));

        $f[]="<span OnMouseOver=\"this.style.cursor='pointer';this.style.color='#337AB7'\"";
        $f[]="OnMouseOut=\"this.style.cursor='default';this.style.color=''\"";
        $f[]="style=\"{$border}font-weight:bold\"";
        $f[]="data-toggle=\"popover\"";
        $f[]="data-placement=\"right\"";
        $f[]="data-content=\"$tooltip\"";
        $f[]="OnClick=\"$js\" id=\"$id\">$text</span>
		<script type=\"text/javascript\">$(\"#$id\").popover({ trigger: 'hover',html: true,});</script>";
        if($GLOBALS["VERBOSE"]){echo "->td_href($text,$fromtooltip,...)<br>\n";}
        $GLOBALS[__CLASS__][__FUNCTION__][$memmd]=@implode(" ", $f);
        return html_trusted(@implode(" ", $f));

    }

    public function Tooltips($id,$text):array{
        $tooltip=$this->_ENGINE_parse_body($text);
        if (strlen($tooltip)<3){
            return array("","");
        }
        $tooltip=str_replace('"','\"',$tooltip);
        $f[]="data-toggle=\"popover\"";
        $f[]="data-html=\"true\"";
        $f[]="data-placement=\"right\"";
        $f[]="data-content=\"$tooltip\"";
        $f[]="data-container=\"body\"";
        $f[]="data-trigger=\"hover\"";

        return array(@implode(" ",$f),"$(\"#$id\").popover({ trigger: 'hover',html: true,});");
    }

    public function js_confirm_empty($text,$field,$value,$jsafter=null):bool{
        $tt=time();
        $page=CurrentPageName();
        $jsafter=$this->jsToTry($jsafter);
        $confirm=$this->javascript_parse_text("{empty_all_data_from} $text ?");
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        // $confirm part dans une chaine JS "..." ; $value dans des chaines JS '...'.
        $confirm=str_replace('"',"\\x22",js_title_safe($confirm));
        $value=req_js($value);
        $title=$this->javascript_parse_text("{are_you_sure}");
        $yes_delete_it=$this->javascript_parse_text("{yes_empty}");
        $success=$this->javascript_parse_text("{success}");
        $success_text=$this->javascript_parse_text("{all_entries_was_removed}");
        $Cancel=$this->javascript_parse_text("{cancel}");
        header("content-type: application/x-javascript");
        echo "
		var xDelete$tt= function (obj) {
		    var results=obj.responseText;
		    if(results.length>3){
		        swal({title: \"Oops...\", text: results, html: true, type: \"warning\" });
	        }else{
	            swal(\"$success!\", '$success_text: $value', \"success\");
	            $jsafter
	            LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
	        }
		}
	
		function Delete$tt(){
		    var XHR = new XHRConnection();
		    XHR.appendData('$field','$value');
		    XHR.sendAndLoad('$page', 'POST',xDelete$tt);
		}
	
	swal({
	title: \"$title\",
	text: \"$confirm\",
	type: \"warning\",
	showCancelButton: true,
	confirmButtonColor: \"#DD6B55\",
	confirmButtonText: \"$yes_delete_it\",
	cancelButtonText:\"$Cancel\",
	closeOnConfirm: false
	},
		function(){
		Delete$tt();
	});";
        return true;
    }

    public function js_display_results($html,$false=false,$title=null):bool{
        $type="success";
        if($false){$type="error";}
        header("content-type: application/x-javascript");
        if($title==null){$title=$this->javascript_parse_text("{results}");}
        $html=$this->_ENGINE_parse_body($html);
        $html=str_replace('"', "'", $html);
        $html=str_replace("\n", "<br>", $html);
        $html=str_replace("<br><br>", "<br>", $html);
        // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
        $html=str_replace('"',"'",html_safe_fragment($html));
        $html=str_replace("\\","\\\\",$html);
        $title=str_replace(array("\\",'"',"<",">"),array("\\\\","'","&lt;","&gt;"),(string)$title);
        echo "swal({
		title: \"$title\",
		text: \"$html\",
		html: true,
		type: \"$type\"
	});";
        return true;
    }

    public function js_confirm_execute($text,$field,$value,$jsafter=null):bool{
        $tt=time();
        $page=CurrentPageName();
        $confirm=$this->_ENGINE_parse_body("{execute_this_task}: $text ?");
        $confirm=str_replace("\n","<br>",$confirm);
        // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
        // html_safe_fragment() reecrit les attributs entre guillemets doubles : ils
        // fermaient la chaine JS text:"..." (bouton Restaurer des instantanes muet).
        $confirm=str_replace('"',"\\x22",js_title_safe($confirm));
        $value=req_js($value);
        $title=$this->_ENGINE_parse_body("{are_you_sure}");
        $title=str_replace("\n"," ",$title);
        $title=str_replace("'","`",$title);

        $yes_delete_it=$this->javascript_parse_text("{yes_execute_it}");
        $success=$this->javascript_parse_text("{success}");
        $success_text=$this->javascript_parse_text("{item_executed}: {apply_upgrade_help}");
        $Cancel=$this->javascript_parse_text("{cancel}");
        header("content-type: application/x-javascript");
        echo "
	var xDelete$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){
		swal({
		title: \"Oops...\",
		text: results,
		html: true,
		type: \"warning\"
	});
	
	}else{
	
		swal({
            title: '$success!',
              text: '$success_text',
              type: \"success\",
              timer: 2000
           });

	$jsafter
	LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
	}
	
	}
	
	function Delete$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('$field','$value');
	XHR.sendAndLoad('$page', 'POST',xDelete$tt);
	
	}
	
	swal({
	title: \"$title\",
	text: \"$confirm\",
	html: true,
	type: \"warning\",
	showCancelButton: true,
	confirmButtonColor: \"#DD6B55\",
	confirmButtonText: \"$yes_delete_it\",
	cancelButtonText:\"$Cancel\",
	closeOnConfirm: false
	},
	function(){
	Delete$tt();
	});";
        return "";
    }

    public function popup_no_privs(){
        $this->popup_error("{ERROR_NO_PRIVS2}");
    }

    public function popup_error($text):bool{
        $text=$this->_ENGINE_parse_body($text);
        $text=str_replace("\n", "<br>", $text);
        // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
        $text=html_safe_fragment($text);
        $text=base64_encode($text);

        header("content-type: application/x-javascript");
        $error=$this->_ENGINE_parse_body("{error}");


        $jsa[]="$.alert({";
        $jsa[]="theme: 'red-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"$error...\",";
        $jsa[]="content: base64_decode('$text'),";
        $jsa[]="icon: 'fas fa-exclamation',";
        $jsa[]="defaultButtons: {";
        $jsa[]="    ok: {";
        $jsa[]="        action: function (){ }";
        $jsa[]="    }";
        $jsa[]="        }";
        $jsa[]="});";
        echo @implode("\n",$jsa);
        return true;
    }
    public function popup_info($text,$js=null){
        $text=$this->_ENGINE_parse_body($text);
        $text=str_replace("'", "`", $text);
        $text=str_replace("\n", "<br>", $text);
        // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
        $text=js_title_safe($text);
        header("content-type: application/x-javascript");
        echo "swal( {title:'OK', text:'$text', html: true,type:'info'});\n$js";
    }

    function ParseFileRegex($path,$regex):array{

        if(!is_file($path)){
            writelogs("$path no such file",__FUNCTION__,__FILE__,__LINE__);
            return array(false,array());
        }
        $t=explode("\n",@file_get_contents($path));
        foreach ($t as $line){
            $line=trim($line);
            if($line==null){continue;}
            VERBOSE("[$line] == /$regex/",__LINE__);
            if(preg_match("#$regex#i",$line,$re)){return array(true,$re);}
        }
        return array(false,array());
    }


    public function js_mysql_alert($text):bool{
        header("content-type: application/x-javascript");
        $f[]=$text;
        $trace=debug_backtrace();

        foreach ($trace as $debug){
            $f[]="Called by ". basename($debug["file"])." function {$debug["function"]}() Line {$debug["line"]}()";
        }

        $text=@implode("<br>",$f);
        $text=$this->_ENGINE_parse_body($text);
        // Le moteur rend l'apostrophe en entite (templates::replace_strings): une
        // entite n'a aucun sens dans une boite de dialogue JavaScript, qui affiche
        // la chaine telle quelle. On la retrouve avant d'echapper pour de bon.
        $text=str_replace("&#39;", "'", $text);
        $text=str_replace("'", "\'", $text);
        $text=str_replace("\n", "<br>", $text);
        $error=$this->_ENGINE_parse_body("{error}");


        $jsa[]="$.alert({";
        $jsa[]="theme: 'red-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"$error: SQL...\",";
        $jsa[]="content: '$text',";
        $jsa[]="icon: 'fas fa-exclamation',";
        $jsa[]="defaultButtons: {";
        $jsa[]="    ok: {";
        $jsa[]="        action: function (){ }";
        $jsa[]="    }";
        $jsa[]="        }";
        $jsa[]="});";
        echo @implode("\n",$jsa);
        return true;


    }
    public function js_executed_background($text=null):bool{
        header("content-type: application/x-javascript");
        $operation_in_background=$this->_ENGINE_parse_body("{operation_in_background}");
        $text=$this->_ENGINE_parse_body($text);
        // Le moteur rend l'apostrophe en entite (templates::replace_strings): une
        // entite n'a aucun sens dans une boite de dialogue JavaScript, qui affiche
        // la chaine telle quelle. On la retrouve avant d'echapper pour de bon.
        $text=str_replace("&#39;", "'", $text);
        $text=str_replace("'", "\'", $text);
        $text=str_replace("\n", "<br>", $text);


        $jsa[]="$.alert({";
        $jsa[]="theme: 'white-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"<p style='margin-top:20px'>$operation_in_background</p>\",";
        $jsa[]="content: '<H1 class=center>&laquo;&nbsp;$text&nbsp;&raquo;</H1>',";
        $jsa[]="icon: 'fas fa-info-circle fa-4x',";
        $jsa[]="defaultButtons: {";
        $jsa[]="    ok: {";
        $jsa[]="        action: function (){ }";
        $jsa[]="    }";
        $jsa[]="        }";
        $jsa[]="});";
        echo @implode("\n",$jsa);
        return true;


    }
    public function js_ok($text=null,$after=null):bool{
        header("content-type: application/x-javascript");
        $success=$this->_ENGINE_parse_body("{success}");
        $text=$this->_ENGINE_parse_body($text);
        // Le moteur rend l'apostrophe en entite (templates::replace_strings): une
        // entite n'a aucun sens dans une boite de dialogue JavaScript, qui affiche
        // la chaine telle quelle. On la retrouve avant d'echapper pour de bon.
        $text=str_replace("&#39;", "'", $text);
        $text=str_replace("'", "\'", $text);
        $text=str_replace("\n", "<br>", $text);
        $jsa[]="$.alert({";
        $jsa[]="\ttheme: 'white-theme',";
        $jsa[]="\tcontainerFluid: true,";
        $jsa[]="\ttitle: \"<p style='margin-top:20px;margin-left:5px'>$success</p>\",";
        $jsa[]="\tcontent: '<H3 class=center>&laquo;&nbsp;$text&nbsp;&raquo;</H3>',";
        $jsa[]="\ticon: 'fas fa-thumbs-up fa-4x',";
        $jsa[]="\tdefaultButtons: {";
        $jsa[]="\t\tok: {";
        $jsa[]="\t\t\taction: function (){";
        $jsa[]="\t\t\t}";
        $jsa[]="\t\t}";
        $jsa[]="\t}";
        $jsa[]="});";
        $jsa[]="";
        $jsa[]=$after;
        $jsa[]="";
        echo @implode("\n",$jsa);
        return true;
    }
    public function js_config_applied($text=null):bool{
        header("content-type: application/x-javascript");
        $success=$this->_ENGINE_parse_body("{success}");
        $text=$this->_ENGINE_parse_body("{settings_applied}");
        // Le moteur rend l'apostrophe en entite (templates::replace_strings): une
        // entite n'a aucun sens dans une boite de dialogue JavaScript, qui affiche
        // la chaine telle quelle. On la retrouve avant d'echapper pour de bon.
        $text=str_replace("&#39;", "'", $text);
        $text=str_replace("'", "\'", $text);
        $text=str_replace("\n", "<br>", $text);
        $jsa[]="$.alert({";
        $jsa[]="theme: 'white-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"<p style='margin-top:20px;margin-left:5px'>$success</p>\",";
        $jsa[]="content: '<H3 class=center>&laquo;&nbsp;$text&nbsp;&raquo;</H3>',";
        $jsa[]="icon: 'fas fa-thumbs-up fa-4x',";
        $jsa[]="defaultButtons: {";
        $jsa[]="    ok: {";
        $jsa[]="        action: function (){ }";
        $jsa[]="    }";
        $jsa[]="        }";
        $jsa[]="});";
        echo @implode("\n",$jsa);
        return true;
    }

    public function validate_url($url) {
        $path = parse_url($url, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        $url = str_replace($path, implode('/', $encoded_path), $url);

        return (bool)filter_var($url, FILTER_VALIDATE_URL);
    }

    // post_error's text is shown as HTML (swal html:true) AFTER
    // javascript_parse_text() decoded its entities: a value the page had
    // escaped (a name typed by the operator, an error sent by a daemon) became
    // live HTML again — <img src onerror=…> ran in the console. Everything is
    // escaped, then only the formatting tags the platform itself writes come
    // back, bare (no attribute, so nothing can carry script): br, hr, strong,
    // b, i, em, code, u. ENT_NOQUOTES: no numeric entity, since form_outside's
    // JS turns every "#" into a line break.
    public static function post_error_html(string $text):string{
        $text=htmlspecialchars($text,ENT_NOQUOTES|ENT_SUBSTITUTE,"UTF-8");
        return preg_replace('#&lt;(/?)(br|hr|strong|b|i|em|code|u)\s*/?&gt;#i','<$1$2>',$text);
    }

    public function post_error($text):string{
        // Keeps accents: the "jserror:" body is read from responseText (UTF-8)
        // and shown by swal, never embedded in an ISO-8859-1 JavaScript literal.
        if(!isset($GLOBALS["TPL"])){$GLOBALS["TPL"]=new templates();}
        $text=$GLOBALS["TPL"]->javascript_parse_text($text,0,true);
        $text=self::post_error_html($text);
        $text=str_replace("\n","<br>",$text);
        $text=str_replace("<br><br>","<br>",$text);
        $text=str_replace("'","`",$text);
        return "jserror:$text";
    }



    public function js_error($html,$FUNCTION=null,$FILE=null,$LINE=null):bool{
        $added=array();
        $html=$this->_ENGINE_parse_body($html);
        if($FUNCTION<>null){
            $added[]="In function :$FUNCTION";
        }
        if($LINE<>null){
            $added[]="In Line :$LINE";
        }
        if($FILE<>null){
            $added[]="In File :".basename($FILE);
        }

        return $this->popup_error($html."<hr>".@implode("<br>",$added));


    }

    public function js_error_stop($text){
        header("content-type: application/x-javascript");

        $text=str_replace("<br>", "\\n", $text);
        $f[]=$text;

        $trace=debug_backtrace();
        foreach ($trace as $debug){
            $f[]="Called by ". basename($debug["file"])." function {$debug["function"]}() Line {$debug["line"]}()";
        }


        $this->popup_error(@implode("<br>",$f));
        exit;


    }

    public function js_confirm_delete_from_id($id,$field,$main_id=0,$jsafter=null){
        $tt=time();
        $page=CurrentPageName();
        $confirm=$this->_ENGINE_parse_body("{delete_from_query}: ");
        $title=$this->javascript_parse_text("{are_you_sure}");
        $title=str_replace("%C3%8A","Ê",$title);
        $title=str_replace("%C3%BB","û",$title);
        $yes_delete_it=$this->javascript_parse_text("{yes_delete_it}");
        $success=$this->javascript_parse_text("{success}");
        $success_text=$this->javascript_parse_text("{item_deleted}");
        $Cancel=$this->javascript_parse_text("{cancel}");
        header("content-type: application/x-javascript");
        echo "

		
var xDelete$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){
			swal({
			title: \"Oops...\",
			text: results,
			html: true,
			type: \"warning\"
		});
		
	}else{
		var zvalue=document.getElementById('$id').value;	
		swal({
		title:\"$success!\",
		text:\"$success_text: <b>\"+zvalue+\"</b>\",
		html: true,
		type: \"success\"});
		LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		$jsafter
	}
	
}
	
function Delete$tt(){
	var zvalue=document.getElementById('$id').value;	
	var XHR = new XHRConnection();
	XHR.appendData('$field',zvalue);
	XHR.appendData('main_id','$main_id');
	XHR.sendAndLoad('$page', 'POST',xDelete$tt);
	
}
function swallStart(){	
	var zvalue=document.getElementById('$id').value;	
	if(zvalue.length==0){zvalue='*';}
	
	swal({
		title: \"$title\",
		text: \"$confirm\" +zvalue,
		type: \"warning\",
		html: true,
		showCancelButton: true,
		confirmButtonColor: \"#DD6B55\",
		confirmButtonText: \"$yes_delete_it\",
		cancelButtonText:\"$Cancel\",
		closeOnConfirm: false
	},
	function(){
		Delete$tt();
	});
			
		
}
		swallStart();";
    }
    public function js_dialog_confirm_action($text,$field,$value,$jsafter=null,$freetext=false):bool{
        $tt=time();
        $valueSrc=$value;
        if($value=="none"){$value=null;}else{$value=": <b>$value</b>";}
        $page=CurrentPageName();
        $confirm=$this->_ENGINE_parse_body($text);
        $confirm=str_replace("\n","",$confirm);
        $confirm=str_replace(".<br>",".",$confirm);
        $confirm=str_replace(".",".<br>",$confirm);
        $title=$this->javascript_parse_text("{are_you_sure}");
        $title=str_replace("%C3%8A","Ê",$title);
        $yes_delete_it=$this->javascript_parse_text("{yes_execute_task}");
        $success=$this->javascript_parse_text("{success}");
        $success_text=$this->javascript_parse_text("{task_executed}");
        $Cancel=$this->javascript_parse_text("{cancel}");
        header("content-type: application/x-javascript");
        echo "
var xDelete$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){
		swal({
 			title: \"Oops...\",
  			text: results,
  			html: true,
  			type: \"warning\"
		});

	}else{
		swal({
			title:\"$success!\", 
			text:\"$success_text{$value}\",
			html: true,
			type: \"success\"});
		LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		$jsafter
		}
		
	}
	
	function Delete$tt(){
		var XHR = new XHRConnection();
		XHR.appendData('$field','$valueSrc');
		XHR.sendAndLoad('$page', 'POST',xDelete$tt);
	
	}
				
		swal({
			title: \"$title\",
			text: \"$confirm\",
			type: \"info\",
			html: true,
			showCancelButton: true,
			confirmButtonColor: \"#DD6B55\",
			confirmButtonText: \"$yes_delete_it\",
			cancelButtonText:\"$Cancel\",
			closeOnConfirm: false
		},
		function(){
			Delete$tt();
		});";

        return true;
    }

    public function js_confirm_delete($text,$field,$value,$jsafter=null,$freetext=false):bool{
        $tt                 = time();
        $page               = CurrentPageName();
        $delete_object      = $this->_ENGINE_parse_body("{delete_object}");
        $Cancel             = $this->javascript_parse_text("{cancel}");
        $yes_delete_it      = $this->javascript_parse_text("{yes_delete_it}");
        $title              = $this->javascript_parse_text("{are_you_sure}");
        $title              = str_replace("%C3%8A","Ê",$title);
        $title              = str_replace("%C2%BF","?",$title);
        $title              = str_replace("%C3%C2¡","á",$title);
        $title              = str_replace("'","`",$title);

        if(!$freetext){
            $confirm=$this->_ENGINE_parse_body("{delete_this_item}:<br> <strong style=\"font-size:16px\">$text ?</strong>");
        }else{
            $confirm=$this->_ENGINE_parse_body($text);
        }
        $confirm=str_replace("\n","<br>",$confirm);
        // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
        $confirm=html_safe_fragment($confirm);
        $value=req_js($value);
        $confirm=base64_encode("<div id=\"error_$tt\"></div>$confirm<br>$title?");
        header("content-type: application/x-javascript");

        $jsa[]="$.confirm({";
        $jsa[]="theme: 'red-theme',";
        $jsa[]="containerFluid: true,";
        $jsa[]="title: \"$delete_object...\",";
        $jsa[]="content: base64_decode('$confirm'),";
        $jsa[]="icon: 'fas fa-trash-alt',";
        $jsa[]="    buttons: {
        confirm: {
            text: '$yes_delete_it',
            action: function () { Delete$tt(); }
        },
        cancel: {
            text: '$Cancel',
            
        },
    }";
        $jsa[]="});";
        echo @implode("\n",$jsa);

        $error=base64_encode($this->_ENGINE_parse_body("{error}"));
    $jsb[]="var xDelete$tt = function (obj) {";
    $jsb[]="\tvar results=obj.responseText;";
    $jsb[]="\tif(results.length>3){";
        $jsb[]="\t\t$.alert({";
        $jsb[]="\t\ttheme: 'red-theme',";
        $jsb[]="\t\tcontainerFluid: true,";
        $jsb[]="\t\ttitle: base64_decode('$error'),";
        $jsb[]="\t\tcontent: results,";
        $jsb[]="\t\ticon: 'fas fa-exclamation',";
        $jsb[]="\t\tdefaultButtons: {";
        $jsb[]="\t\t    ok: {";
        $jsb[]="\t\t        action: function (){ }";
        $jsb[]="\t\t    }";
        $jsb[]="\t\t        }";
        $jsb[]="\t\t});";
    $jsb[]="\treturn;";
    $jsb[]="\t}";
	$jsb[]="\t$jsafter;";
    $jsb[]="\t}";

        $jsb[]="function Delete$tt(){";
    $jsb[]="var XHR = new XHRConnection();";
	$jsb[]="XHR.appendData('$field','$value');";
	$jsb[]="XHR.sendAndLoad('$page', 'POST',xDelete$tt);";
$jsb[]="}";
        echo @implode("\n",$jsb);
        return true;
    }




    public function button_ids_reconfigure():string{
        $jsrestart=$this->framework_buildjs(
            "suricata:/suricata/restart",
            "suricata.progress",
            "suricata.progress.txt","reconfigure-service-div"
        );
        return $this->_ENGINE_parse_body($this->button_inline("{apply_changes}",$jsrestart,"fa fa-save",null,0,"abt-p")."<div id='reconfigure-service-div'></div>");
    }

    public function StringToFonts($text,$add=null):string{
        if(is_null($text)) {
            if (is_null($add)) {
                return "";
            }
        }
        if($add<>null){$add=" $add";}
        $text=str_replace("&nbsp;"," ",$text);
        $period="<i class='fa-solid fa-period$add' style='margin-right:-15px'></i>";
        $str_split=str_split($text);
        $CURVERz=array();
        foreach ($str_split as $char) {
            if ($char == null) {
                continue;
            }
            if ($char == " ") {
                $char=".";
            }

            if ($char == ".") {
                $CURVERz[] = $period;
                continue;
            }
            if(is_numeric($char)) {
                $CURVERz[] = "<i class='fa-solid fa-$char$add' style='margin-right:-15px'></i>";
                continue;
            }
            $CURVERz[] = "<i class='fa-solid fa-$char$add' style='margin-right:-15px'></i>";
        }
        if(count($CURVERz)==0){return "";}
        return @implode("",$CURVERz);
    }



    public function button_upload($btname="{upload_a_file}",$currentpage=null,$classname=null,$addedparams=null,$field_name=null):string{
        $style=null;

        //height:46px;padding-top:10px;font-size:18px;

        // Meme bouton que button_autnonome() : envoyer un fichier est l'action de la
        // page (principal) ; btn-default/btn-white = neutre ; btn-xs/btn-sm = petit.
        if($classname<>null){
            if(preg_match("#style=(.+?);class=(.+)#",$classname,$re)){
                $style="style='$re[1]'";
                $classname=$re[2];
            }
            if(preg_match("#BYFORM:(.+)#", $classname,$re)){$classname=$re[1];}
        }
        $classname=(string)$classname;
        $small=preg_match('#btn-(xs|sm)#',$classname)==1;
        $neutral=$small || preg_match('#btn-(default|white)#',$classname)==1;
        $classtext="abt ".($neutral?"abt-s":"abt-p").($small?" abt-sm":"");
        $ffname="file-uploaded";
        if($field_name<>null){$ffname=$field_name;}
        $btid=md5(time().$btname.$currentpage);
        $btname=$this->javascript_parse_text($btname);
        $Uploading=$this->javascript_parse_text("{uploading}");
        $UploadFailed=$this->javascript_parse_text("{failed}");
        return "
		<span class=\"btn $classtext fileinput-button\" $style>
			<i class=\"fa fa-upload\" aria-hidden=\"true\"></i>
			<span id='$btid'>$btname</span>
			<!-- The file input field used as target for the file upload widget -->
			<input id=\"fileupload$btid\" type=\"file\" name=\"files\">
		</span>
		<script>
    $('#fileupload$btid').fileupload({
    	url: '/fw.upload.php',
        dataType: 'json',
        add: function (e, data) {
        	document.getElementById('$btid').innerHTML='$Uploading....';
            data.submit()
            
        },
    progressall: function (e, data) {
        var progress = parseInt(data.loaded / data.total * 100, 10);
        document.getElementById('$btid').innerHTML='$Uploading '+progress+'%';
 
    },        
        done: function (e, data) {
        	document.getElementById('$btid').innerHTML='$btname';
        	// Le handler renvoie TOUJOURS files[0].name, meme quand l'enregistrement a
        	// echoue (disque plein -> error:\"Failed to write file to disk\"). Sans ce
        	// test on enchaine sur un fichier qui n'existe pas et le daemon repond un
        	// \"no such file\" qui accuse le mauvais coupable.
        	var uploaded=(data.result && data.result.files) ? data.result.files[0] : null;
        	if(uploaded==null || uploaded.error){
        		swal({title:'$UploadFailed',text:(uploaded && uploaded.error)?uploaded.error:'$UploadFailed',type:'error',confirmButtonColor:'#ed5565'});
        		return;
        	}
        	// encodeURIComponent: um espaço, '+', '&', '#' ou '%' no nome chegava
        	// alterado à página (o fw.upload.php já o faz no retorno dele, pela
        	// mesma razão). Um ficheiro descarregado duas vezes chama-se
        	// nome (1).pem, e esse parêntese e espaço vêm de fábrica.
        	// &_t: sem ele, dois uploads do mesmo nome dao o mesmo URL e o
        	// navegador responde da cache -- a pagina repete a mensagem anterior
        	// e o servidor nunca e chamado.
        	Loadjs('$currentpage?$ffname='+encodeURIComponent(uploaded.name)+'&_t='+Date.now()+'$addedparams');
    	}
    });
    </script>
	";

    }



    public function button_openvpn_reconfigure($Onlyjs=false){
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/openvpn.enable.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/openvpn.enable.log";
        $ARRAY["CMD"]="openvpn.php?enable=yes";
        $ARRAY["TITLE"]="{reconfigure_service} {APP_OPENVPN}";
        $prgress=base64_encode(serialize($ARRAY));
        $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";
        if($Onlyjs){return $jsrestart;}
        return $this->button_inline("{reconfigure_service}",$jsrestart,"fa fa-save",null,0,"abt-p")."<div id='reconfigure-service-div'></div>";
    }

    public function button_strongswan_reconfigure($Onlyjs=false){
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.build.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/strongswan.build.log";
        $ARRAY["CMD"]="strongswan.php?reconfigure=yes";
        $ARRAY["TITLE"]="{reconfigure_service} {APP_STRONGSWAN}";
        $prgress=base64_encode(serialize($ARRAY));
        $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";
        if($Onlyjs){return $jsrestart;}
        return $this->button_inline("{reconfigure_service}",$jsrestart,"fa fa-save",null,0,"abt-p")."<div id='reconfigure-service-div'></div>";
    }

    public function progress_barr_static($currentvalue,$textbarr,$nocolor=false):string{
        $currentvalue=round($currentvalue,2);
        $class="progress-bar-default";
        if(!$nocolor) {
            if ($currentvalue > 80) {
                $class = "progress-bar-warning";
            }
            if ($currentvalue > 95) {
                $class = "progress-bar-danger";
            }
        }
        $html[]="<div class=\"progress\" style='margin-bottom:0;background-color: #00000024 !important'>";
        $html[]="\t<div style=\"width: $currentvalue%;padding-left:5px\" aria-valuemax=\"100\" aria-valuemin=\"0\" aria-valuenow=\"$currentvalue\" role=\"progressbar\" class=\"progress-bar $class\">";
        $html[]="\t\t<span class=\"\">$currentvalue% $textbarr</span>";
        $html[]="\t</div>";
        $html[]="</div>";

        return $this->_ENGINE_parse_body(@implode("\n", $html));

    }

    public function icon_edit_field($js=null,$security=null,$tooltip=null):string{
        $faloup="";
        if(preg_match("#^([0-9]+)x:(.+)#",$js,$re)){
            $faloup=" fa-$re[1]x";
            $js=$re[2];
        }

        // Crayon d'une ligne (table_form_field_info/text..., tableaux awc) : bouton icone du
        // systeme abt, discret (abt-g), libelle pour les lecteurs d'ecran.
        $aria=str_replace('"',"&quot;",strip_tags($this->_ENGINE_parse_body(strlen((string)$tooltip)>0?$tooltip:"{modify}")));
        if(!$this->IsSecurity($security)){
            $tip=str_replace('"',"&quot;",$this->_ENGINE_parse_body("{this_form_locked_privileges}"));
            return "<button type=\"button\" class=\"btn abt abt-g abt-ico abt-l\" aria-disabled=\"true\" aria-label=\"$aria\" data-tip=\"$tip\"><i class=\"fa-solid fa-lock\"></i></button>";}
        if($js==null){
            return "<button type=\"button\" class=\"btn abt abt-g abt-ico\" disabled aria-label=\"$aria\"><i class=\"far fa-tools\"></i></button>";
        }

        $id=md5("$js$tooltip". microtime(true));
        list($ztooltip,$none)=$this->Tooltips($id,$tooltip);
        return "<button type=\"button\" class=\"btn abt abt-g abt-ico\" OnClick=\"$js\" id='$id' aria-label=\"$aria\" $ztooltip><i class=\"fa-solid fa-pen-field$faloup\"></i></button>";
    }

    public function icon_services($js=null,$security=null,$tooltip=null):string{

        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' 
OnClick=\"blur()\" ><i class='fa-brands fa-whmcs'></i></button>";}
        if($js==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >
<i class='fa-brands fa-whmcs'></i></button>";
        }
        $id=md5($js.$security.$tooltip.microtime(false));
        list($ztool,$none)=$this->Tooltips($id,$tooltip);
        return "<button type='button' class='btn btn-white btn-bitbucket' 
        OnClick=\"$js\" id='$id' $ztool><i class='fa-brands fa-whmcs' style='color:#21b9bb'></i></button>";
    }
    public function icon_parameters($js=null,$security=null,$tooltip=null):string{

        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='far fa-tools'></i></button>";}
        if($js==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='far fa-tools'></i></button>";
        }

        $id=md5("$js$security$tooltip".microtime(false));
        list($ztooltip,$none)=$this->Tooltips($id,$tooltip);
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" id='$id' $ztooltip><i class='far fa-tools' style='color:#21b9bb'></i></button>";
    }

    public function icon_vitrification($js=null,$on=false,$err=false){
        $ico2="fas far fa-wine-glass";
        $ico = "<i class='$ico2' style='color:#CCCCCC'></i>";
        $btn="btn-white";
        if($err){
            $ico2="fas far fa-wine-glass";
            $ico = "<i class='text-danger $ico2'></i>";
            if($js==null) {
                $js = "blur();";
            }
            return "<button type='button' class='btn btn-danger btn-bitbucket' OnClick=\"$js\" >$ico</button>";
        }

        if($on) {
            $ico2="fas far fa-wine-glass";
            $ico = "<i class='$ico2'></i>";
            $btn="btn-warning";
        }

        if($js==null){
            $js="blur();";
            $ico="<i class='$ico2' style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn $btn btn-bitbucket' OnClick=\"$js\" >$ico</button>";
    }

    public function icon_refresh_animate($js=null):string{
        $ico2=ico_refresh_animate;

        $ico="<i class='$ico2' style='color:#21b9bb'></i>";

        if($js==null){
            $js="blur();";
            $ico="<i class='$ico2' style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";
    }

    public function icon_refresh($js=null,$tooltip=null):string{
        $ico="<i class='fa fa-repeat' style='color:#21b9bb'></i>";

        if($js==null){
            $js="blur();";
            $ico="<i class='fa fa-repeat' style='color:#CCCCCC'></i>";
        }
        $id=md5($js.$tooltip.microtime(false));
        list($ztool,$none)=$this->Tooltips($id,$tooltip);
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" id='$id' $ztool>$ico</button>";
    }


    public function button_dropdown_table($title,$array=array(),$security=null,$btclass=null):string{
        // Menu d'actions (7 appels) : meme bouton et meme menu que le "More" de
        // table_buttons() (abt-more / abt-menu). Droit manquant : cadenas sur le bouton,
        // entrees desactivees. fw.caret.php est garde (fermeture au depart de la souris,
        // masquage de la recherche pendant que le menu est ouvert).
        $id=md5(serialize($array));
        $idbtn="Btn$id";
        $small=false;
        $CaretAddon="";
        $dot="";
        $ddv="abt-s";
        if(isset($array["BUTTON"])){
            $PARAMS=$array["BUTTON"];
            unset($array["BUTTON"]);
            if(isset($PARAMS["type"]) && $PARAMS["type"]=="xs"){$small=true;}
            if(isset($PARAMS["NoCleanJs"])){$CaretAddon="&clean=yes";}
            if(isset($PARAMS["ID"])){$idbtn=$PARAMS["ID"];}
            // GLOBAL_CLASS colorait le bouton selon l'etat de la ligne (site ou backend
            // actif, inactif, en alerte) : il devient une pastille devant le libelle.
            if(isset($PARAMS["GLOBAL_CLASS"])){
                $tones=array("btn-primary"=>"abt-s","btn-warning"=>"abt-c4","btn-danger"=>"abt-x","btn-default"=>"abt-o");
                if(isset($tones[$PARAMS["GLOBAL_CLASS"]])){$ddv=$tones[$PARAMS["GLOBAL_CLASS"]];}
            }
        }
        $locked=!$this->IsSecurity($security);
        $cls="btn ".$this->abt_bs($ddv)." abt $ddv".($small?" abt-sm":"");
        $f=array();
        $f[]="<div class=\"abt-more\" id='$id'>";
        if($locked){
            $tip=str_replace('"',"&quot;",$this->_ENGINE_parse_body("{this_form_locked_privileges}"));
            $f[]="<button type=\"button\" class=\"$cls abt-l\" id='$idbtn' aria-disabled=\"true\" data-tip=\"$tip\"><i class=\"fa-solid fa-lock\"></i><span>$title</span></button>";
            $f[]="</div>";
            return @implode("",$f);
        }
        $f[]="<button type=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"$cls dropdown-toggle\" id='$idbtn' OnClick=\"Loadjs('fw.caret.php?id=$id$CaretAddon');\"><span>$title</span><i class=\"fa-solid fa-chevron-down\" aria-hidden=\"true\"></i></button>";
        $f[]="<ul class=\"dropdown-menu abt-menu\" onmouseleave=\"Loadjs('fw.caret.php?id=$id&leave=yes$CaretAddon');\">";
        foreach ($array as $label=>$js) {
            if($label=="SPACER"){$f[]="<li class=\"divider\"></li>";continue;}
            $ico="";
            $color="";
            $allowed=true;
            if(strpos($js,"||")>0){
                $js2=explode("||",$js);
                $js=$js2[1].";Loadjs('fw.caret.php?id=$id$CaretAddon');";
                list($textClor,$ico)=$this->button_dropdown_icon($js2[0]);
                if($textClor<>"currentColor"){$color=" style=\"color:$textClor\"";}
                if(isset($js2[2]) && $js2[2]<>null && !$this->IsSecurity($js2[2])){
                    VERBOSE("Perms FALSE = {$js2[2]}",__LINE__);
                    $allowed=false;
                }
            }
            if(!$allowed){
                $f[]="<li class=\"disabled\"><a href=\"#\" title=\"{this_form_locked_privileges}\" OnClick=\"return false\"><i class=\"fa-solid fa-lock\"></i><span>$label</span></a></li>";
                continue;
            }
            $js=rtrim($js,"; ");
            $f[]="<li><a href=\"#\" OnClick=\"$js;return false\"$color>$ico<span>$label</span></a></li>";
        }
        $f[]="</ul>";
        $f[]="</div>";
        return @implode("",$f);
    }
    private function button_dropdown_icon($Class):array{
        // Couleur d'une entree de menu (texte + icone), relevee au contraste AA sur blanc.
        $IconType=null;
        $IconValue="";
        $text="currentColor";
        if(preg_match("#^(.+?):(.+?):(.+)#",$Class,$re)){
            $Class=$re[1];
            $IconType=$re[2];
            $IconValue=$re[3];
        }
        if($IconType=="color"){
            $colors=array("red"=>"#b91c1c","yellow"=>"#b45309","green"=>"#047857","grey"=>"#475569","black"=>"#0f172a","blue"=>"#1d4ed8");
            if(isset($colors[$IconValue])){$text=$colors[$IconValue];}
        }
        return array($text,"<i class='$Class'></i>");
    }
    public function label_click($color=null,$label=null,$js=null){
        $array_bf["green"]="label-primary";
        $array_bf["yellow"]="label-warning";
        $array_bf["lazur"]="label-info";
        $array_bf["red"]="label-danger";
        $array_bf["blue"]="label-info";
        $array_bf["gray"]="label-default";
        $array_bf["black"]="black-bg";
        $array_bf["grey"]="label-default";
        if($js==null){$js="Blur();";}
        $over="onMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='default';\"";
        $click="OnClick=\"$js\"";
        $labelclass="label ".$array_bf[$color];
        $label=$this->_ENGINE_parse_body($label);
        return "<span class='$labelclass' $over $click>$label</span>";
    }

    public function button_label_table($title,$js,$class=null,$security=null,$disable=false){
        // Barre d'actions au-dessus d'un tableau (43 appels, souvent dans un btn-group) :
        // meme bouton que table_buttons(), role deduit du libelle (new_* principal,
        // remove_* destructif, le reste neutre). Avant, la couleur alternait d'un
        // bouton a l'autre (info/primary) sans rien signifier.
        // Couleur de rang d'un appel a l'autre (vert, bleu clair, bleu, orange, emeraude), comme
        // l'alternance d'origine ; destructif = rouge.
        $icon=trim((string)$class);
        if($this->abt_role($icon,(string)$title)=="x"){
            $variant="abt-x";
        }else{
            $variant=$this->abt_rot($this->abt_lbl_rot);
            $this->abt_lbl_rot++;
        }
        if($disable){$js="";}
        return $this->button_inline($title,$js,$icon,$security,0,$variant);
    }


    private function IsAsSecurityLabel($value=null){
        if($value==null){return false;}

        $data["AsDnsAdministrator"]=true;
        $data["AsSquidAdministrator"]=true;
        $data["ASDCHPAdmin"]=true;
        $data["AsSystemAdministrator"]=true;
        $data["AsDansGuardianAdministrator"]=true;
        $data["computer"]=true;
        $data["AsProxyMonitor"]=true;
        $data["AsVPNManager"]=true;
        $data["AsFirewallManager"]=true;
        $data["AllowAddUsers"]=true;
        $data["AsOrgAdmin"]=true;
        $data["AllowAddGroup"]=true;
        $data["AsPostfixAdministrator"]=true;
        $data["AsMailBoxAdministrator"]=true;
        $data["AsMessagingOrg"]=true;
        $data["AsHotSpotManager"]=true;
        if(!isset($data[$value])){
            VERBOSE("$value not found",__LINE__);
            return false;
        }
        return $data[$value];
    }

    public function IsSecurity($security=null){
        if($security==null){return true;}
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];

        if(isset($_SESSION["HARMPID"])){
            if($_SESSION["HARMPID"]>0){
                $users->AsCertifsManager=true;
            }
        }
        if($security=="FalseLicense"){return false;}
        if($security=="AsDatabaseAdministrator"){if(!$users->AsDatabaseAdministrator){return false;}}
        if($security=="AsCertifsManager"){if(!$users->AsCertifsManager){return false;}}
        if($security=="AsDnsAdministrator"){if(!$users->AsDnsAdministrator){return false;}}
        if($security=="AsSquidAdministrator"){if(!$users->AsSquidAdministrator){return false;}}
        if($security=="ASDCHPAdmin"){if(!$users->ASDCHPAdmin){return false;}}
        if($security=="AsSystemAdministrator"){if(!$users->AsSystemAdministrator){return false;}}
        if($security=="AsDansGuardianAdministrator"){if(!$users->AsDansGuardianAdministrator){return false;}}
        if($security=="AsDatabaseAdministrator"){if(!$users->AsDatabaseAdministrator){return false;}}
        if($security=="computer"){if(!$this->computers_rights()){return false;}}
        if($security=="AsProxyMonitor"){if($users->AsSquidAdministrator){return true;}}
        if($security=="AsProxyMonitor"){if($users->AsSystemAdministrator){return true;}}
        if($security=="AsProxyMonitor"){if($users->AsSquidAdministrator){return true;}}
        if($security=="AsProxyMonitor"){if(!$users->AsProxyMonitor){return false;}}
        if($security=="AllowAddUsers"){if(!$users->AllowAddUsers){return false;}}
        if($security=="AsOrgAdmin"){if(!$users->AsOrgAdmin){return false;}}
        if($security=="AsVPNManager"){if(!$users->AsVPNManager){return false;}}
        if($security=="AsFirewallManager"){if(!$users->AsFirewallManager){return false;}}
        if($security=="AllowAddGroup"){if(!$users->AllowAddGroup){return false;}}
        if($security=="AsPostfixAdministrator"){if(!$users->AsPostfixAdministrator){return false;}}
        if($security=="AsMailBoxAdministrator"){if(!$users->AsMailBoxAdministrator){return false;}}
        if($security=="AsMessagingOrg"){if(!$users->AsMessagingOrg){return false;}}
        if($security=="AsHotSpotManager"){if(!$users->AsHotSpotManager){return false;}}
        if($security=="AsDockerAdmin"){if(!$users->AsDockerAdmin){return false;}}
        if($security=="AsDockerReverse"){if(!$users->AsDockerReverse){return false;}}
        // Artica Meta (spec 2026-11-13, tache 10) : IsSecurity() ignorait ces deux noms, donc
        // form_outside(...,"AsArticaMetaAdmin") ne verrouillait rien. "AsArticaMetaAdmin" =
        // infogerant (ou administrateur systeme hors perimetre tenant) ; "AsMetaAdmin" = toute
        // page Meta utilisable par un tenant (AsArticaMetaAdmin ou AsMetaAdmin). Echec ferme si
        // les predicats (class.users.menus.inc) manquent.
        if($security=="AsArticaMetaAdmin"){if(!function_exists("meta_provider_or_sysadmin") || !meta_provider_or_sysadmin($users)){return false;}}
        if($security=="AsMetaAdmin"){if(!function_exists("meta_node_admin") || !meta_node_admin($users)){return false;}}

        return true;
    }

    function is_regex($pattern):bool{
        $f[]="{";
        $f[]="[";
        $f[]="+";
        $f[]="\\";
        $f[]="?";
        $f[]="$";
        $f[]=".*";

        foreach ($f as $val){
            if(strpos(" $pattern", $val)>0){return true;}
        }
        return false;
    }

    public function icon_reply($js=null,$security=null){
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-reply'></i></button>";}
        if($js==null){$js="blur();";}
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='fas fa-reply'></i></button>";
    }

    public function icon_repeat($js=null,$security=null){
        $ico="fad fa-repeat-alt";
        $iconoff="fal fa-repeat-alt";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_add($js=null,$security=null,$tooltip=null){
        $icon="<i class=\"fas fa-plus-circle\" style='color:#CCCCCC'></i>";
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$icon</button>";
            }
        }


        $icon="<i class=\"fas fa-plus-circle\" style='color:#18a689'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$icon</button>";}
        if($js==null){
            $js="blur();";
            $icon="<i class=\"fas fa-plus-circle\" style='color:#CCCCCC'></i>";
        }
        $id=md5("$js$tooltip". microtime(true));
        list($ztooltip,$none)=$this->Tooltips($id,$tooltip);

        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" id='$id' $ztooltip>$icon</button>";
    }
    public function icon_cd($js=null,$security=null,$tooltip=null):string{

        $ico="fad fa-compact-disc";
        $iconoff="fal fa-compact-disc";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }
         return $this->icon_gen($ico,$iconoff,$js,true,"",$tooltip);
    }
    public function icon_bash($js=null,$security=null):string{

        $ico="fad fa-rectangle-terminal";
        $iconoff="fal fa-rectangle-terminal";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_pause_error($js=null,$security=null):string{
        $ico="fas fa-pause-circle";
        $iconoff="fal fa-pause-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#ed5565");
    }
    public function icon_pause($js=null,$security=null):string{
        $ico="fas fa-pause-circle";
        $iconoff="fal fa-pause-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }
    public function icon_pause_warning($js=null,$security=null):string{
        $ico="fas fa-pause-circle";
        $iconoff="fal fa-pause-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#f0ad4e");
    }
    public function icon_firewall($js=null,$security=null):string{
        $ico="fa-solid fa-shield";
        $iconoff="fa-thin fa-shield";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(!is_null($security)) {
            if (strlen($security) > 2) {
                if (!$this->IsSecurity($security)) {
                    return $this->icon_gen($ico, $iconoff, $js, false);
                }
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }
    public function icon_run($js=null,$security=null):string{
        $ico="fas fa-arrow-to-right";
        $iconoff="fal fa-arrow-to-right";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(!is_null($security)) {
            if (strlen($security) > 2) {
                if (!$this->IsSecurity($security)) {
                    return $this->icon_gen($ico, $iconoff, $js, false);
                }
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_start($js=null,$security=null):string{
        $ico="fas fa-play-circle";
        $iconoff="fal fa-play-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#1ab394");
    }
    public function icon_start_error($js=null,$security=null):string{
        $ico="fas fa-play-circle";
        $iconoff="fal fa-play-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#ed5565");
    }

    public function icon_run_warn($js=null,$security=null):string{
        $ico="fas fa-play-circle";
        $iconoff="fal fa-play-circle";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js,true,"#f0ad4e");
    }
    public function icon_history($js=null,$security=null):string{
        $ico="fad fa-clock-rotate-left";
        $iconoff="fal fa-clock-rotate-left";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}

        if(strlen($security)>2){
            if(!$this->IsSecurity($security)){
                return $this->icon_gen($ico,$iconoff,$js,false);
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_stop($js=null,$security=null){

        $ico="fas fa-stop";
        $iconoff="fal fa-stop";

        if($js==null){return $this->icon_gen($ico,$iconoff,$js,false);}
        if(!is_null($security)) {
            if (strlen($security) > 2) {
                if (!$this->IsSecurity($security)) {
                    return $this->icon_gen($ico, $iconoff, $js, false);
                }
            }
        }

        return $this->icon_gen($ico,$iconoff,$js);
    }

    public function icon_restore($js=null,$security=null){
        $pic    = "fad fa-file-import";
        $color  = "18a689";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}

        $ico="<i class='$pic' style='color:#{$color}'></i>";
        if($js==null){
            $js="blur();";
            $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";


    }
    public function icon_erase($js=null,$security=null){
        $ico = "<i class='fa-solid fa-eraser'></i>";

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";
            }
        }
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}

        $ico="<i class='fa-solid fa-eraser' style='color:#ec4758'></i>";
        if($js==null){
            $js="blur();";
            $ico="<i class='fa-solid fa-eraser' style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";

    }
    public function icon_badge_bell($Nums=0,$js=null,$security=null,$class="text-warning",$tooltip=null):string{
        if((int)$Nums <= 0){
            return "<span style='position:relative;display:inline-block;'>
                    <i class='$class fas fa-bell-on'></i>
                </span>";
        }
       $colors["text-danger"]="#ed5565";
       $colors["text-warning"]="#f8ac59";
        $colors["text-default"]="#d2d2d2";
        $bg=$colors[$class];
        $id=md5("$Nums$js$class$tooltip".microtime(false));
        $badgecount="position:absolute;top:-12px;right:-12px;background:$bg;color:#fff;font-size:10px;font-weight:bold;line-height:1;padding:3px 3px;border-radius:2px;min-width:14px;text-align:center;";

        $bb= "<span id=\"pp-$id\" style='position:relative;display:inline-block;'>
                <i class='$class fas fa-bell-on'></i>
                <span style='$badgecount'>".htmlspecialchars((string)$Nums, ENT_QUOTES, 'UTF-8')."</span>
            </span>";
        if(strlen($js)<2){
            return $bb;
        }
        return $this->td_href($bb,$tooltip,$js);
    }

    public function icon_delete($js=null,$security=null){
        $icon_class=ico_trash;
        $ico="<i class='$icon_class' style='color:#CCCCCC'></i>";
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";
            }
        }



        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}

        $ico="<i class='$icon_class' style='color:#ec4758'></i>";
        if($js==null){
            $js="blur();";
            $ico="<i class='$icon_class' style='color:#CCCCCC'></i>";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";
    }
    public function icon_browser($js=null,$tooltip=null):string{
        $ico="fab fa-internet-explorer";

        if($js==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";
        }
        $id=md5($js.microtime(false));
        list($tooltip,$none)=$this->Tooltips($id,$tooltip);
        return "<button type='button' id='$id' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" $tooltip><i class='$ico'></i></button>";
    }
    public function icon_html($js=null,$tooltip=null):string{
        $ico=ico_html;

        if($js==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";
        }
        $id=md5($js.microtime(false));
        list($tooltip,$none)=$this->Tooltips($id,$tooltip);
        return "<button type='button' id='$id' class='btn btn-white btn-bitbucket' OnClick=\"$js\" $tooltip><i class='$ico' color='#1c84c6'></i></button>";
    }
    public function icon_support($url=null):string{
        $ico=ico_support;
        if($url==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";
        }

        $js="s_PopUpFull('$url',1024,768,'Support');";
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='$ico'></i></button>";
    }
    public function icon_wiki($url=null):string{
        $ico="fa-solid fa-book-open";
        if($url==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";
        }

        $js="s_PopUpFull('$url',1024,768,'Support');";
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='$ico'></i></button>";
    }
    public function icon_interface($js=null,$security=null):string{
        $ico=ico_interface;
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";}
        if($js==null){$js="blur();";}
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='$ico'></i></button>";
    }

    public function icon_loupe($enabled=false,$js=null,$id=null,$security=null):string{

        if(!$enabled){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" id='$id'><i class='fas fa-search'></i></button>";}
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-search'></i></button>";}
        if($js==null){$js="blur();";}
        return "<button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\" ><i class='fas fa-search'></i></button>";
    }


    public function icon_restart($js=null,$security=null){
        $ico="fa-solid fa-arrow-rotate-right";
        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$ico,$js,false);}
        if($js==null){return $this->icon_gen($ico,$ico,$js,false);}
        return $this->icon_gen($ico,$ico,$js,true);
    }
    public function icon_recycle($js=null,$security=null){
        $ico="fa fa-recycle";
        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$ico,$js,false);}
        if($js==null){return $this->icon_gen($ico,$ico,$js,false);}
        return $this->icon_gen($ico,$ico,$js,true);
    }
    public function icon_recycle_green($js=null,$security=null){
        $ico="fa fa-recycle";
        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$ico,$js,false);}
        if($js==null){return $this->icon_gen($ico,$ico,$js,false);}
        return $this->icon_gen($ico,$ico,$js,true);
    }


    public function icon_shield_yellow($js=null,$security=null,$ForceDisabled=false):string{
        $ico="fad fa-shield-alt";
        $iconff="fal fa-shield";


        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js,true,"f7a54a");
    }
    public function icon_logs($js=null,$security=null,$ForceDisabled=false,$tooltip=null):string{
        $ico=ico_list;
        $iconff=ico_list;


        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js,true,null,$tooltip);
    }
    public function icon_logs2($js=null,$security=null,$ForceDisabled=false,$tooltip=null):string{
        $ico="fa-solid fa-list-ol";
        $iconff="fa-solid fa-list-ol";


        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js,true,null,$tooltip);
    }


    public function icon_shell($js=null,$security=null,$ForceDisabled=false,$tooltip=null):string{
        $ico="fa-solid fa-square-terminal";
        $iconff="fa-regular fa-square-terminal";


        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js,true,null,$tooltip);
    }
    public function icon_shield($js=null,$security=null,$ForceDisabled=false):string{
        $ico="fad fa-shield-alt";
        $iconff="fal fa-shield";


        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js);
    }

    public function icon_optimize($js=null,$security=null,$ForceDisabled=false):string{
        $ico="fad fa-gauge-circle-bolt";
        $iconff="fal fa-gauge-circle-bolt";

        if($js==null){
            $ForceDisabled=true;
        }
        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js);
    }
    public function icon_optimize_grey($js=null,$security=null,$ForceDisabled=false):string{
        $ico="fad fa-gauge-circle-bolt";
        $iconff="fal fa-gauge-circle-bolt";

        if($js==null){
            $ForceDisabled=true;
        }
        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js,true,"d1dade");
    }

    public function icon_shield_grey($js=null,$security=null,$ForceDisabled=false){
        $ico="fad fa-shield-alt";
        $iconff="fal fa-shield";


        if(!$this->IsSecurity($security)){
            return $this->icon_gen($ico,$iconff,$js,false);
        }

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js,false);
    }

    public function icon_bug($js=null,$security=null,$ForceDisabled=false){
        $ico="fad  fa-bug";
        $iconff="fal fa-bug";


        if(!$this->IsSecurity($security)){return $this->icon_gen($ico,$iconff,$js,false);}

        if($ForceDisabled){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$iconff'></i>
                    </button>";
        }

        if($js==null){return $this->icon_gen($ico,$iconff,$js,false);}
        return $this->icon_gen($ico,$iconff,$js);
    }


    private function icon_gen($ico=null,$icoff=null,$js=null,$enabled=true,$color=null,$tooltip=null):string{
        if($color==null){
            $color="18a689";
        }
        $color=str_replace("#","",$color);
        if($icoff==null){$icoff=$ico;}
        if(!$enabled){
            $js="blur();";
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" >
                            <i class='$icoff'></i>
                    </button>";
        }
        $id=md5("$ico$icoff$js$enabled$color$tooltip".microtime(false));
        list($ztool,$none)=$this->Tooltips($id,$tooltip);
        return "<button type='button' class='btn btn-white btn-bitbucket' 
            OnClick=\"$js\" $ztool>
                <i class='$ico' style='color:#$color'></i>
            </button>";

    }


    public function icon_unlink($js=null,$security=null){
        $ico="<i class='fa fa-unlink' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}

        $ico="<i class='fa fa-unlink' style='color:#ec4758'></i>";

        if($js==null){
            $js="blur();";
            $ico="<i class='fa fa-unlink' style='color:#CCCCCC'></i>";


        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >$ico</button>";

    }


    public function icon_attach($js=null,$security=null){
        $pic="fas fa-paperclip";
        return $this->__icon_defaults($pic,$js,$security);
    }
    public function icon_archive($js=null,$security=null){
        $pic="fas fa-file-archive";
        return $this->__icon_defaults($pic,$js,$security);
    }
    public function icon_construction($value,$js,$security){
        $pic="fas fa-construction";
        $color="#c2ccd1";
        if($value==1){
            $color="#f7a54a";
        }
        return $this->__icon_defaults("color:$color:$pic",$js,$security);
    }

    public function icon_nic($js=null,$security=null){
        $pic="fad fa-ethernet";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $class="btn btn-white btn-bitbucket";
        $clasw="btn btn-white btn-bitbucket";

        if(!$this->IsSecurity($security)){
            return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";
        }
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='$clasw' $jsav >$ico</button>";

    }




    public function icon_user_lock($js=null,$security=null,$tooltip=null):string{
        $pic="fad fa-user-lock";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $class="btn btn-white btn-bitbucket";
        $clasw="btn btn-white btn-bitbucket";

        if(!$this->IsSecurity($security)){return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){ return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>"; }
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='$re[1]'\"";
        }
        $id=md5("$js$security$tooltip".microtime(false));
        list($ztool,$none)=$this->Tooltips($id,$tooltip);
        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='$clasw' $jsav id='$id' $ztool>$ico</button>";

    }
    public function icon_user($js=null,$security=null){
        $pic="fad fa-user";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $class="btn btn-white btn-bitbucket";
        $clasw="btn btn-white btn-bitbucket";

        if(!$this->IsSecurity($security)){
            return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";
        }
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='$clasw' $jsav >$ico</button>";

    }
    public function icon_group($js=null,$security=null){
        $pic="fad fa-users";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $class="btn btn-white btn-bitbucket";
        $clasw="btn btn-white btn-bitbucket";

        if(!$this->IsSecurity($security)){
            return "<button type='button' class='$class' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";
        }
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='$clasw' $jsav >$ico</button>";

    }

    public function icon_download_artica($local_path){
        $pic="fad fa-download";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        $local_path=str_replace("/usr/share/artica-postfix/","",$local_path);
        if(!is_file("/usr/share/artica-postfix/$local_path")){
            VERBOSE("[/usr/share/artica-postfix/$local_path] no such file",__LINE__);
            return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" >$ico</button>";
        }

        $jsav="OnClick=\"window.location='fw.download.php?fname=".base64_encode($local_path)."'\"";
        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='btn btn-white btn-bitbucket' $jsav >$ico</button>";
    }

    public function icon_download($js=null,$security=null){

        $pic="fad fa-download";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";}
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='btn btn-white btn-bitbucket' $jsav >$ico</button>";

    }
    public function icon_upload($js=null,$security=null){

        $pic="fa-solid fa-upload";
        $ico="<i class='$pic' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";}
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }

        $ico="<i class='$pic' style='color:#18a689'></i>";
        return "<button type='button' class='btn btn-white btn-bitbucket' $jsav >$ico</button>";

    }



//<i class="fas fa-file-export"></i>
    public function icon_export($js=null,$security=null){
        $ico="<i class='fas fa-file-export' style='color:#CCCCCC'></i>";
        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"blur()\" >$ico</button>";}
        if($js==null){$js="blur();";}
        $jsav="OnClick=\"$js\"";
        if(preg_match("#direct:(.+)#", $js,$re)){
            $jsav="OnClick=\"document.location.href='{$re[1]}'\"";
        }
        $ico="<i class='fas fa-file-export' style='color:#18a689'></i>";
        return "<button type='button' class='btn btn-white btn-bitbucket' $jsav >$ico</button>";

    }

    public function icon_pie($js=null,$security=null){



        if(!$this->IsSecurity($security)){return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-chart-pie'></i></button>";}
        if($js==null){
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-chart-pie'></i></button>";
        }
        return "<button type='button' class='btn btn-info btn-bitbucket' OnClick=\"$js\" ><i class='fas fa-chart-pie'></i></button>";


    }

    public function icon_stats($js = null, $security = null, $disable = false):string{
        if (!$this->IsSecurity($security)) {
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='fas fa-chart-bar'></i></button>";
        }
        if ($js == null) {
            $js = "blur();";
        }
        if ($disable == true) {
            $js = "blur();";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" ><i class='fas fa-chart-bar' style='color:#1ab394'></i></button>";


    }
    public function icon_pdf($js = null, $security = null, $disable = false):string{
        $ico="fas fa-file-pdf";
        $color="#CCCCCC";
        if (!$this->IsSecurity($security)) {
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" >
            <i class='$ico' style='color:$color'></i></button>";
        }
        $color="#1ab394";
        if ($js == null){
            $color="#CCCCCC";
            $js = "blur();";
        }
        if ($disable == true) {
            $color="#CCCCCC";
            $js = "blur();";
        }

        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" >
        <i class='$ico' style='color:$color'></i></button>";


    }
    public function icon_certificate($js = null, $security = null, $tooltips=null):string{
        $ico=ico_certificate;
        $tooltip="";
        $id=md5($js.$tooltips.microtime(true));
        if(!is_null($tooltips) && strlen($tooltips)>2){
            list($tooltip,$jsadd)=$this->Tooltips("span-$id",$tooltips);
        }

        if (!$this->IsSecurity($security)) {
            return "<button type='button' id='$id' class='btn btn-default btn-bitbucket' $tooltip OnClick=\"Blur()\" ><i class='$ico'></i></button>";
        }
        if ($js == null) {
            return "<button type='button' id='$id' class='btn btn-default btn-bitbucket' $tooltip OnClick=\"Blur()\" ><i class='$ico'></i></button>";
        }


        return "<button type='button' id='$id' class='btn btn-white btn-bitbucket' $tooltip OnClick=\"$js\" ><i class='$ico' style='color:#1ab394'></i></button>";


    }
    public function icon_chart($js = null, $security = null, $disable = false):string{
        $ico="fas fa-chart-pie";

        if (!$this->IsSecurity($security)) {
            return "<button type='button' class='btn btn-default btn-bitbucket' OnClick=\"blur()\" ><i class='$ico'></i></button>";
        }
        if ($js == null) {
            $js = "blur();";
        }
        if ($disable) {
            $js = "blur();";
        }
        return "<button type='button' class='btn btn-white btn-bitbucket' OnClick=\"$js\" ><i class='$ico' style='color:#1ab394'></i></button>";


    }
    //end keepalived

    public function js_prompt($title,$subtitle,$icon,$js,$fieldname=null,$jsafter=null,$defaultvalue=null,$KeyName=null):bool{
        header("content-type: application/x-javascript");

        $field_type = "text";
        $js         = $this->_ENGINE_parse_body($js);
        $title      = $this->_ENGINE_parse_body($title);
        $subtitle   = $this->_ENGINE_parse_body($subtitle);
        $id         = md5(time());
        $cancel     = $this->_ENGINE_parse_body("{cancel}");
        $fill_form  = $this->_ENGINE_parse_body("{fill_form}");
        $save       = $this->_ENGINE_parse_body("{save}");
        $onKeyPress = "onkeypress=\"SaveCheck{$id}(event);\"";
        if(!is_null($fieldname)) {
            if (preg_match("#password#i", $fieldname)) {
                $field_type = "password";
            }
        }

        $title      = str_replace("'","`",$title);
        $subtitle   = str_replace("'","`",$subtitle);
        $cancel     = str_replace("'","`",$cancel);
        $subtitle   = str_replace("'","\'",$subtitle);
        $subtitle   = str_replace("\n","<br>",$subtitle);
        // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
        $title        = html_safe_fragment($title);
        $subtitle     = html_safe_fragment($subtitle);
        $icon         = req_html($icon);
        $defaultvalue = req_html($defaultvalue);
        $titlejs      = str_replace(array("\\",'"',"\r","\n"),array("\\\\",'\\"',"",""),$title);

        $messages[] = "<div style=\"padding: 15px 15px;text-align: center;border-bottom: 1px solid #e5e5e5;\">";
        $messages[] = "<i class=\"$icon\" style=\"font-size: 84px;color:#e2e3e3;\"></i>";
        $messages[] = "<h3 style=\"text-align: center;\">$title</h3>";
        $messages[] = "<div id=\"modal-inside-alert\"></div>";
        $messages[] = "<div style=\"margin-top:-10px;margin-bottom:10px\"><small class=\"font-bold\">$subtitle</small></div>";
        $messages[] = "</div>";

        if($fieldname==null){
            $messages_final=base64_encode(@implode("",$messages));
            $jsa[]="$.confirm({";
            $jsa[]="theme: 'supervan',";
            $jsa[]="containerFluid: true,";
            $jsa[]="title: \"$titlejs\",";
            $jsa[]="content: base64_decode('$messages_final'),";
            $jsa[]="icon: '$icon',";
            $jsa[]="buttons: {";
            $jsa[]="confirm: {
                text: 'OK',
            action: function () { $jsafter; }
        },
            cancel: {
                text: '$cancel',
            
        },},";

            $jsa[]="});";
            echo @implode("\n",$jsa);
            return true;

        }

        if($fieldname<>null) {
            $messages[] ="<div class=\"form-group\" style=\"margin-top:8px\">";
            $messages[] = "    <input type=\"$field_type\" id=\"form$id\" $onKeyPress class=\"form-control\" style=\"z-index:999999;\" value=\"$defaultvalue\">";
            $messages[] = " </div>";
        }


        $messages_final=base64_encode(@implode("",$messages));

        $jsa[]="var x_Save$id= function (obj) {";
        $jsa[]="  var results=obj.responseText;";
        $jsa[]="  if(results.length>3){";
        $jsa[]="        if(document.getElementById('modal-inside-alert')){";
        $jsa[]="        document.getElementById('modal-inside-alert').innerHTML='<div class=\"alert alert-danger\"><strong>'+results+'</strong></div>';";
        $jsa[]="     }else{";
        $jsa[]="            alert(results);";
        $jsa[]="     }";
        $jsa[]="     return;";
        $jsa[]="   }";
        $jsa[]="    Boot$id.close();";
        $jsa[]="    $jsafter";
        $jsa[]="}";
        $jsa[]="";
        $jsa[]="";
        $jsa[]="function SaveCheck{$id}(e){";
        $jsa[]="    if(checkEnter(e)){ Save{$id}();}";
        $jsa[]="}";
        $jsa[]="function Save{$id}(){";
        $jsa[]="\tvar XHR = new XHRConnection();";
        $jsa[]="\tvar one='';";
        $jsa[]="\tif(document.getElementById('modal-inside-alert')){document.getElementById('modal-inside-alert').innerHTML='';}";
        $jsa[]="\tif(!document.getElementById('form$id')){alert('form$id !!');return;}";
        $jsa[]="\tone=document.getElementById('form$id').value;";
        $jsa[]="\tif(one.length==0){";
        $jsa[]="\tdocument.getElementById('modal-inside-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$fill_form</strong></div>';";
        $jsa[]="\treturn;}";

        if($KeyName<>null){
            $jsa[]="\tXHR.appendData('KeyID','".req_js($KeyName)."');";

        }

        $jsa[]="\tXHR.appendData('$fieldname',one);";
        $jsa[]="\tXHR.sendAndLoad('$js', 'POST',x_Save$id);";
        $jsa[]="}";
        $jsa[]="";
        $jsa[]="";
        $jsa[]="var Boot$id=BootstrapDialog.show({";
        $jsa[]="id: '$id',";
        $jsa[]="size: BootstrapDialog.SIZE_SMALL,";
        $jsa[]="title:'$titlejs',";
        $jsa[]="message: base64_decode('$messages_final'),";
        $jsa[]="closable: false,";
        $jsa[]="closeByBackdrop: false,";
        $jsa[]="closeByKeyboard: false,";
        $jsa[]="buttons: [";
        $jsa[]="{
                label: '$cancel',
                icon: 'far fa-window-close',
                action: function(dialogRef){ dialogRef.close(); }
            },";

        if($fieldname<>null) {
            $jsa[] = "{
                    label: '$save',
                    icon: 'fas fa-save',
                    cssClass: 'btn-primary',
                    action: function(dialogRef){ Save{$id}(); }
                },";
        }

        $jsa[]="],";
        $jsa[]="onshown: function(dialogRef){ ";
        $jsa[]="    $('#{$id}_title').css({ 'font-size' : '13px' });";
        $jsa[]="    $('#{$id}_title').css({ 'padding' : '5px' });";
        $jsa[]="},";
        $jsa[]="});";

        echo @implode("\n",$jsa);
        return true;
    }
    public function js_show_pic($title,$subtitle,$image_path){
        header("content-type: application/x-javascript");
        $image_path=urlencode(base64_encode($this->_ENGINE_parse_body($image_path)));
        $title=urlencode(base64_encode($this->_ENGINE_parse_body($title)));
        $subtitle=urlencode(base64_encode($this->_ENGINE_parse_body($subtitle)));
        echo "LoadAjax('artica-modal-dialog','fw.modal-pic.php?title=$title&subtitle=$subtitle&image=$image_path')";
    }
    public function js_show_video($title,$subtitle,$videoid){
        header("content-type: application/x-javascript");
        $title=urlencode(base64_encode($this->_ENGINE_parse_body($title)));
        $subtitle=urlencode(base64_encode($this->_ENGINE_parse_body($subtitle)));
        return "LoadAjax('artica-modal-dialog','fw.modal-video.php?title=$title&subtitle=$subtitle&video=$videoid')";
    }



    public function icon_excel($js=null){
        if($js==null){$js="blur();";}
        return "<div class='center'><button type='button' class='btn btn-primary btn-bitbucket' OnClick=\"$js\"><i class='fas fa-file-spreadsheet'></i></button></div>";

    }

    public function format_search_protocol($search,$standard_date=false,$mysql_date=false,$green_date=false,$nodate=false):array{
        $c              = 0;
        $date           = null;
        $PROTO          = null;
        $max            = 0;

        if(!is_null($search)) {
            $search=trim(strtolower($search));
            VERBOSE("SEARCH [$search]",__LINE__);
            $search = str_replace(['+', '²', '°', '\\', '%', '$', ';', '#', '&', '`', '"', '>', '<', '�?', "'"], '', $search);

            if(preg_match("#this hour#", $search)){
                $search=str_replace("this hour", "", $search);
                $time="\s+".date("H");
            }
            if(preg_match("#yesterday#", $search)){
                $search=str_replace("yesterday", "", $search);
                strtotime('-1 day', "node()");
                $time="\s+".date("H");
            }
            if(preg_match("#with categories#", $search)){
                $search=str_replace("with categories", "", $search);
                $MAIN["CATEGORIES"]=true;
            }
            if(preg_match("#with destinations#", $search)){
                $search=str_replace("with destinations", "", $search);
                $MAIN["DESTINATIONS"]=true;
            }
            if(preg_match("#last hour#", $search)){
                $search=str_replace("last hour", "", $search);
                $time="\s+".date("H",strtotime( '-1 hours' ));
            }
            if(preg_match("#today#", $search)){
                $date=date("M")."\s+".intval(date("d")).$time;
                if($standard_date){$date=date("m/d");}
                if($mysql_date){$date=date("m-d");}
                if($green_date){$date=date("d/M");}
                $search=str_replace("today", "", $search);
            }
            if(preg_match("#everything#", $search)){
                $search=str_replace("everything", "*", $search);
            }
            if(preg_match("#last\s+([0-9]+)#", $search,$re)){
                $search=str_replace("last {$re[1]}", "", $search);
                $max=$re[1];
            }
            if(preg_match("#yesterday#", $search)){
                $date=date("M", strtotime( '-1 days' ) )."\s+".intval(date("d",strtotime( '-1 days' ))).$time;
                if($standard_date){$date=date("m/d",strtotime( '-1 days' ));}
                if($mysql_date){$date=date("m-d",strtotime( '-1 days' ));}
                $search=str_replace("yesterday", "", $search);
            }
            if(preg_match("#protocol\s+([a-z\*]+)#",$search,$re)){
                $PROTO=strtoupper($re[1]);
                $c++;
            }
            if(preg_match("#(^|\s)(max|limit)\s+([0-9]+)#",$search,$re)){
                $search=str_replace("$re[2] $re[3]", "", $search);
                $max=strtoupper($re[3]);
            }
            if(preg_match("#([0-9]+)\s+events#",$search,$re)){
                VERBOSE("Found filtered events [$re[1]]",__LINE__);
                $search=str_replace("$re[1] events", "", $search);
                $max=intval($re[1]);
            }
            if(preg_match("#^max=([0-9]+)#i",$search,$re)){
                VERBOSE("Found max events [$re[1]]",__LINE__);
                $search=str_replace("max=$re[1]", "", $search);
                $max=$re[1];
            }
            if(preg_match("#(max)(\s+|=)([0-9]+)#i",$search,$re)){
                VERBOSE("Found max events [$re[1]]",__LINE__);
                $search=str_replace("$re[1]$re[2]$re[3]", "", $search);
                $max=$re[2];
            }
            if(preg_match("#src\s+([0-9\.\*]+)#",$search,$re)){
                $MAIN["SRC"]=$re[1];
                $c++;
                $search=str_replace("src {$re[1]}", "", $search);
            }
            if(preg_match("#dst\s+([0-9\.\*]+)#",$search,$re)){
                $MAIN["DST"]=$re[1];
                $c++;
                $search=str_replace("dst {$re[1]}", "", $search);
            }
            if(preg_match("#srcport\s+([0-9\*]+)#",$search,$re)){
                $MAIN["SRCPORT"]=$re[1];
                $search=str_replace("src {$re[1]}", "", $search);
            }
            if(preg_match("#dstport\s+([0-9\*]+)#",$search,$re)){
                $MAIN["DSTPORT"]=$re[1];
                $c++;
                $search=str_replace("dstport {$re[1]}", "", $search);
            }
            if(preg_match("#mac\s+([0-9\:\-\*]+)#",$search,$re)){
                $MAIN["MAC"]=$re[1];
                $MAIN["MAC"]=str_replace("-", ":", $MAIN["MAC"]);
                $c++;
                $search=str_replace("mac {$re[1]}", "", $search);
            }
            if(preg_match("#pid(=|\s+)([0-9]+)#",$search,$re)){
                $MAIN["PID"]=$re[2];
                $c++;
                $search=str_replace("pid{$re[1]}{$re[2]}", "", $search);
            }
        }
        if($max==0){$max=50;}
        if(!$nodate) {
            if ($date == null) {
                $date = date("M") . "\s+";
                if ($standard_date) {
                    $date = date("m/d") . "\s+";
                }
                if ($mysql_date) {
                    $date = date("m-d") . "\s+";
                }
            }
        }
        if(!is_null($search)) {
            if (preg_match("#all events#", $search)) {
                $MAIN["MAX"] = $max;
                $MAIN["DATE"] = null;
                $MAIN["TERM"] = null;
                $MAIN["C"] = $c;
                return $MAIN;
            }
            $search=trim($search);
        }

        $MAIN["MAX"]=$max;
        $MAIN["DATE"]=$date;
        if($PROTO<>null){
            $MAIN["PROTO"]=$PROTO;
        }


        $MAIN["TERM"]=$search;
        $MAIN["C"]=$c;
        return $MAIN;
    }

    // Lines of a service log, newest first, through articarest
    // /system/logfile/search/{log}/{max}/{search}. $log is an id of the daemon's closed
    // list (rest_logfile_search.go: "rsyncd", "snmpd", ...), never a path. $MAIN is the
    // array of format_search_protocol(); the pattern is built as the framework/*.php
    // readers did: DATE, then TERM with "." literal and "*" as a wildcard. On failure
    // $error receives the reason and the result is an empty array.
    public function logfile_search($log,$MAIN,$with_date=true,$default_max=250,&$error=null):array{
        $error="";
        if(!is_array($MAIN)){$MAIN=array();}
        $max=intval(isset($MAIN["MAX"])?$MAIN["MAX"]:0);
        if($max<=0){$max=intval($default_max);}
        if($max>1500){$max=1500;}
        $term=trim((string)(isset($MAIN["TERM"])?$MAIN["TERM"]:""));
        $term=str_replace(".","\\.",$term);
        $term=str_replace("*",".*?",$term);
        $date=$with_date?trim((string)(isset($MAIN["DATE"])?$MAIN["DATE"]:"")):"";
        $pattern=$term;
        if($date<>""){$pattern=($term=="")?$date:"$date.*?$term";}
        $pattern=str_replace(".*?.*?",".*?",$pattern);
        if(strlen($pattern)<3){$pattern="NONE";}

        $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/logfile/search/".rawurlencode($log)."/$max/".rawurlencode($pattern));
        $json=json_decode((string)$data);
        if(json_last_error()>JSON_ERROR_NONE || !is_object($json)){
            $error="articarest: ".json_last_error_msg();
            return array();
        }
        if(!isset($json->Status) || !$json->Status){
            $error=isset($json->Error)?(string)$json->Error:"articarest: unknown error";
            return array();
        }
        $lines=array();
        if(isset($json->Results) && is_array($json->Results)){
            foreach($json->Results as $line){
                $line=(string)$line;
                if(trim($line)==""){continue;}
                $lines[]=$line;
            }
        }
        return $lines;
    }


    public function icon_link($enabled=false,$js=null,$id=null){

        $square_class="text-navy";
        $square="fa-external-link-square";
        $text_class=null;
        if(!$enabled){
            $text_class=" text-muted";
            $square_class=null;

        }

        if($id==null){$id=md5(microtime());}
        if($js==null){return "<div class='center'><span class='$text_class'><i class='fa $square ' id='$id'></i></span></div>";}
        return "<span class='$text_class'><a href=\"#\" OnClick=\"$js\"><i class='fa $square' id='$id'></i></a></span>";


    }



    /**
     * $bare=true rend UNIQUEMENT la coche, sans le bouton blanc qui l'encadre.
     * L'etat inactif prend alors un gris visible (le #EEEEEE d'origine est
     * invisible des qu'on retire le cadre, et la case ne se verrait plus).
     * Comportement, identifiants et script de bascule sont inchanges.
     */
    public function icon_check($enabled=false,$js=null,$id=null,$security=null,$tooltips=null,$bare=false):string{
        $disabled=0;
        $tooltip="";
        $offcolor=$bare?"#b6c0c5":"#EEEEEE";
        if(is_bool($enabled)){if($enabled){$enabled=1;}else{$enabled=0;} }

        if($this->IsAsSecurityLabel($id)){$id=null;}
        if($id==null){$id=md5(microtime(true).date("Ymdhsisu")."$js");}
        $id=str_replace("-", "_", $id);

        if($security<>null){
            if(!$this->IsSecurity($security)){
                $js=null;
                $disabled=1;
                $color="#CCCCCC";
            }
        }

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $js=null;
                $color="#CCCCCC";
                $disabled=1;
            }
        }

        if($js==null){$color="#CCCCCC";$Over=null;$disabled=1;}
        if(!is_null($tooltips) && strlen($tooltips)>2){
            list($tooltip,$jsadd)=$this->Tooltips("span-$id",$tooltips);
        }

        if($disabled==1){

            if($enabled==1){
                $ico="<i class=\"fas fa-check\" style=\"color:$color\" id=\"ico-$id\"></i>";
                if($bare){return "<span id=\"btn-$id\" $tooltip>$ico</span>";}
                return  "<button type=\"button\" class=\"btn btn-white btn-bitbucket\"
                id=\"btn-$id\" $tooltip>$ico</button>";
            }

            if($enabled==0){
                if($bare){
                    // Sans cadre, une coche blanche ne se verrait pas du tout:
                    // l'etat inactif reste lisible en gris tres clair.
                    $ico="<i class=\"fas fa-check\" style=\"color:#dfe4e7\" id=\"ico-$id\"></i>";
                    return "<span id=\"btn-$id\" $tooltip>$ico</span>";
                }
                $ico="<i class=\"fas fa-check\" style=\"color:white\" id=\"ico-$id\"></i>";
                return  "<button type=\"button\" class=\"btn btn-white btn-bitbucket\"
                id=\"btn-$id\" $tooltip>$ico</button>";
            }
        }


        if($disabled==0) {
            $color = $offcolor;
            if ($enabled == 1) {
                $color = "#18a689";
            }
        }


        $Over="OnMouseOver=\"BtSwitchHover$id(this)\" OnMouseOut=\"BtSwitchOut$id(this)\"";
        $ico="<i class=\"fas fa-check\" style=\"color:$color\" id=\"ico-$id\"></i>";

        if($bare){
            $f[]="<input type=\"hidden\" id=\"hide-$id\" value=\"$enabled\">
        <span id=\"btn-$id\" style=\"cursor:pointer\" OnClick=\"BtSwitch$id(this)\" $Over
            onclick=\"BtSwitch$id(this)\" $tooltip>$ico</span>";
        }else{
            $f[]="<input type=\"hidden\" id=\"hide-$id\" value=\"$enabled\">
        <button type=\"button\" class=\"btn btn-white btn-bitbucket\"
            id=\"btn-$id\" OnClick=\"BtSwitch$id(this)\" $Over
            onclick=\"BtSwitch$id(this)\" $tooltip>$ico</button>";
        }




        $script="
    function BtSwitch$id(e){
        var disabled=$disabled;
        if(disabled==1){return;}
        var value=document.getElementById(\"hide-$id\").value;
       

        if(value==1){
            document.getElementById(\"btn-$id\").innerHTML=\"<i class='fas fa-check' style='color:$offcolor' id='ico-$id'></i>\";
            document.getElementById(\"hide-$id\").value=0;
            $js;
            return;
        }
        
        document.getElementById(\"btn-$id\").innerHTML=\"<i class='fas fa-check' style='color:#18a689' id='ico-$id'></i>\";
        document.getElementById(\"hide-$id\").value=1;
        
        $js;
    }
            
    function BtSwitchHover$id(e){
        e.style.cursor='pointer';
        $(e).addClass('hover');
    }
    function BtSwitchOut$id(e){
        e.style.cursor='default';
        $(e).removeClass('hover');
    }
     
    ";
        $this->ICON_SCRIPTS[]=$script;
        $GLOBALS["TPL_ICON_SCRIPTS"][]=$script;
        return @implode("\n",$f);
    }

    /**
     * Scripts des icones interactives (icon_check) accumules pendant la construction du HTML,
     * a emettre dans le <script> de fin de tableau. TOUJOURS preferer cette methode a
     * @implode("\n",$tpl->ICON_SCRIPTS).
     *
     * Pourquoi: icon_check() rend un <button OnClick="BtSwitch<id>(this)"> dont la fonction
     * BtSwitch<id>() est produite a part et empilee dans $this->ICON_SCRIPTS -- une propriete
     * d'INSTANCE. Or le nouveau dessin de tableau construit chaque ligne dans sa propre
     * fonction, laquelle fait naturellement son propre `new template_admin()`. Le script part
     * alors avec l'instance jetable de la ligne, tandis que la fonction du tableau implose
     * l'ICON_SCRIPTS d'une AUTRE instance, vide. Le bouton s'affiche parfaitement et ne
     * reagit a aucun clic: aucune erreur PHP, juste un ReferenceError dans la console.
     *
     * icon_check() alimente donc aussi $GLOBALS["TPL_ICON_SCRIPTS"], que cette methode vide.
     * Elle est independante de l'instance: n'importe quel $tpl rend l'ensemble des scripts
     * accumules dans la requete. array_unique() est indispensable, les deux accumulateurs
     * portant les memes entrees.
     */
    public function ICON_SCRIPTS_COMPILE():string{
        if(!isset($GLOBALS["TPL_ICON_SCRIPTS"]) || !is_array($GLOBALS["TPL_ICON_SCRIPTS"])){
            $GLOBALS["TPL_ICON_SCRIPTS"]=array();
        }
        $all=array_unique(array_merge($GLOBALS["TPL_ICON_SCRIPTS"],$this->ICON_SCRIPTS));
        // Vide les deux piles: une page qui rend deux tableaux dans la meme reponse ne
        // redefinirait sinon les fonctions du premier a la fin du second.
        $GLOBALS["TPL_ICON_SCRIPTS"]=array();
        $this->ICON_SCRIPTS=array();
        return @implode("\n",$all);
    }






    public function query_field($search,$LOWER=true){
        $search=trim($search);
        $xs=array();


        $COM=null;
        if(preg_match("#^IPADDRLIKE(.+)#", $search,$re)){
            $COM='>>=';
            if(isset($_GET["QueryIsSqlite"])){$COM="LIKE";}
            $search="{$re[1]}";
        }

        if(preg_match("#^~(.+)#", $search,$re)){
            $COM='~';
            if(isset($_GET["QueryIsSqlite"])){$COM="LIKE";}
            $search=$re[1];
        }
        if(preg_match("#^>=(.+)#", $search,$re)){
            $COM='>=';
            $search=$re[1];
        }
        if(preg_match("#^>(.+)#", $search,$re)){
            $COM='>';
            $search=$re[1];
        }

        if(preg_match("#^=<(.+)#", $search,$re)){
            $COM='=<';
            $search=$re[1];
        }

        if(preg_match("#^<(.+)#", $search,$re)){
            $COM='<';
            $search=$re[1];
        }
        if(preg_match("#^LIKE(.+)#i", $search,$re)){

            $COM='~';
            if( (isset($_GET["QueryIsSqlite"])) OR (isset($_GET["QueryIsMySQL"]))  ){$COM="LIKE";}
            $search=$re[1];

        }
        if(preg_match("#^=(.+)#", $search,$re)){
            $COM='=';
            $search=$re[1];
        }
        if($COM=="="){
            if(strpos(" $search", '*')){$COM='LIKE';}
        }

        if(strpos(" $search", "*")>0){
            if( (isset($_GET["QueryIsSqlite"])) OR (isset($_GET["QueryIsMySQL"]))  ){
                $COM="LIKE";
                $search=str_replace("*", "%", $search);
            }else{
                $COM='~';$LOWER=false;
                $search=str_replace(".", "\.", $search);
                $search=str_replace("*", ".*?", $search);
            }
        }
        if(preg_match('#"(.+?)"#', $search,$re)){$search=$re[1];}





        if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>LINE.".__LINE__." COM:$COM For =[$search]</span><br>\n";}

        if($COM==">>="){return " $COM '$search'";}
        if($COM==null){$COM="=";}
        if(is_numeric(trim($search))){
            return " $COM $search";
        }

        if($LOWER){
            return " $COM LOWER('$search')";
        }
        return " $COM '$search'";
    }


    public function query_postgres_ipaddrmac($search=null,$ipaddr_field="ipaddr",$mac_field="mac",$hostname_field="hostname"){

        if(preg_match("#^[0-9:]+$#",$search)){
            return "WHERE $mac_field = '$search'";
        }
        if(preg_match("#^[0-9\.]+$#",$search)){
            return "WHERE $ipaddr_field = '$search'";
        }

        if(preg_match("#^[0-9\.]+\/[0-9]+#",$search)){
            return "WHERE $ipaddr_field << inet '$search'";
        }

        if(preg_match("#^[a-z0-9\-\_\.\*]+#",$search)){
            $search=strtolower($search);
            if(strpos("   $search","*")>0){
                $search=str_replace("*","%",$search);

                return "WHERE LOWER($hostname_field) LIKE '$search'";
            }
            return "WHERE LOWER($hostname_field) = '$search'";
        }
        return "";
    }

    // Pentest 2026-11-04 (injection SQL) : query_pattern() recopiait dans la requete le
    // nom de colonne tape avant = < > LIKE, et les cles du tableau "types" venu de
    // l'URL. Les valeurs etaient deja privees de quotes, pas les noms : "a=1 union
    // select ... x=1" devenait une colonne. Un nom n'est accepte que s'il est un
    // identifiant SQL simple ; sinon la clause est ignoree.
    private function qp_ident($name):string{
        $name=trim((string)$name);
        if(preg_match('#^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$#',$name)){return $name;}
        if(function_exists("html_safe_fragment_log")){html_safe_fragment_log("query_pattern column refused: $name");}
        return "";
    }
    public function query_pattern($search=null,$aliases=array(),$forceThis=null){
        $search=$this->CLEAN_BAD_XSS_SQL($search);
        $xdate=null;
        if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>ANALYZE [$search]</span><br>\n";}
        $MaxORG=200;
        if(preg_match("#([0-9]+)(\s+|=)events#i",$search,$re)){
            $search=str_ireplace("{$re[1]}{$re[2]}events", "", $search);
            $MaxORG=intval($re[1]);
        }

        if(preg_match("#limit(\s+|=)([0-9]+)#i",$search,$re)){
            $search=str_ireplace("limit{$re[1]}{$re[2]}", "", $search);
            $MaxORG=intval($re[2]);
        }
        if(strpos(" $search","today")>0){
            VERBOSE("FOUND TODAY!!",__LINE__);
            if(isset($aliases["date"])) {
                $field_date = $aliases["date"];
                $xdate = "date_trunc('day', NOW() ) = date_trunc('day', $field_date)";
            }
            $search=str_replace("today ","",$search);
            VERBOSE("SEARCH: $search",__LINE__);
        }
        if(strpos(" $search","yesterday")>0){
            VERBOSE("FOUND YESTERDAY!!",__LINE__);
            if(isset($aliases["date"])) {
                $field_date = $aliases["date"];
                $xdate = "date_trunc('day', NOW() - INTERVAL '1 day') = date_trunc('day', $field_date)";
            }
            $search=str_replace("yesterday ","",$search);
            VERBOSE("SEARCH: $search",__LINE__);
        }
        if(strpos(" $search","yesteray")>0){
            VERBOSE("FOUND YESTERDAY!!",__LINE__);
            if(isset($aliases["date"])) {
                $field_date = $aliases["date"];
                $xdate = "date_trunc('day', NOW() - INTERVAL '1 day') = date_trunc('day', $field_date)";
            }
            $search=str_replace("yesteray ","",$search);
            VERBOSE("SEARCH: $search",__LINE__);
        }



        if(preg_match("#max(\s+|=)([0-9]+)#i",$search,$re)){
            VERBOSE("OK FOUND #max(\s+|=)([0-9]+)#i in $search",__LINE__);
            VERBOSE("{$re[0]} =='max{$re[1]}{$re[2]}",__LINE__);
            $search=str_ireplace("max{$re[1]}{$re[2]}", "", $search);
            $MaxORG=intval($re[2]);
            VERBOSE("MAX:$MaxORG search=[$search]",__LINE__);
        }

        if(stripos($search, " OR ")>0){
            $Zsearch=preg_split("/\s+OR\s+/i",$search);
            foreach ($Zsearch as $line){

                $query_pattern=$this->query_pattern($line,$aliases,$forceThis);
                if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>Q [{$query_pattern["Q"]}]</span><br>\n";}
                $query_pattern["Q"]=str_ireplace("WHERE ", "", $query_pattern["Q"]);

                $pp[]=$query_pattern["Q"];
                if(isset($query_pattern["MAX"])){
                    $MAX=$query_pattern["MAX"];
                }


            }
            VERBOSE("MaxORG=$MaxORG",__LINE__);
            if($MaxORG>0){$MAX=$MaxORG;}
            if($MAX==0){$MAX=150;}
            return array("MAX"=>$MAX,"Q"=>"WHERE $xdate ".@implode(" OR ", $pp),"S"=>$search);

        }

        if(is_array($aliases)) {
            if (count($aliases) == 0) {
                $aliases["src_ip"] = "src_ip";
                $aliases["ipaddr"] = "src_ip";
                $aliases["mac"] = "mac";
                $aliases["website"] = "website";
                $aliases["city"] = "city";
                $aliases["ville"] = "city";
                $aliases["country"] = "country";
                $aliases["pays"] = "country";
                $aliases["hostname"] = "hostname";
            }
        }

        if(preg_match("#(^|\s)src\s+(.+?)([\s+|$])#",$search,$re)){
            VERBOSE("FOUND SRC!!",__LINE__);
            if(isset($aliases["src"])){
                $value=$re[2];
                VERBOSE("FOUND SRC!! VALUE:$value",__LINE__);
                if($value=="*"){
                    $search=str_replace("{$re[1]}src $re[2]","",$search);
                }else {
                    $search = str_replace("{$re[1]}src $re[2]", "{$re[1]}{$aliases["src"]}=$value", $search);
                }
                VERBOSE("$search",__LINE__);
            }
        }
        if(preg_match("#(^|\s)dst\s+(.+?)([\s+|$])#",$search,$re)){
            VERBOSE("FOUND DST!!",__LINE__);
            if(isset($aliases["dst"])){
                $value=$re[2];
                VERBOSE("FOUND DST!! VALUE:$value",__LINE__);
                if($value=="*"){
                    $search=str_replace("{$re[1]}dst $re[2]","",$search);
                }else {
                    $search = str_replace("{$re[1]}dst $re[2]", "{$re[1]}{$aliases["dst"]}=$value", $search);
                }
                VERBOSE("$search",__LINE__);
            }
        }
        if(preg_match("#(^|\s)dstport\s+#",$search,$re)){
            if(isset($aliases["dstport"])){
                $search=str_replace("{$re[1]}dstport","{$re[1]}{$aliases["dstport"]}",$search);
            }
        }
        if(preg_match("#src_ip\s+\*#",$search,$re)){
            $search=str_replace("src_ip *","",$search);
        }
        if(preg_match("#all events#i", $search)){
            return array("MAX"=>200,"Q"=>"*","L"=>__LINE__,"S"=>"*");
        }


        $IPClass=new IP();
        $max=0;
        $query=array();

        VERBOSE("COUNT of query=[".count($query)."] MaxORG=$MaxORG",__LINE__);
        if($IPClass->IsvalidMAC($search)){
            VERBOSE("$search is a valid MAC address",__LINE__);
            if(isset($_GET["types"])){
                $ztypes=unserialize(base64_decode($_GET["types"]),array("allowed_classes"=>false));
                if(isset($ztypes["mac"])){
                    $search="mac=$search";
                }
            }


        }

        if($IPClass->isValid($search)){
            VERBOSE("$search is a valid IP address",__LINE__);
            if(isset($_GET["types"])){
                $ztypes = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["types"]);
                if(isset($ztypes["ipaddr"])){
                    $search="ipaddr=$search";
                }
            }

        }

        if($forceThis<>null){
            $query[]="$forceThis";
        }

        if($search==null){
            if($MaxORG>0){$MAX=$MaxORG;}
            return array("MAX"=>$MAX,"Q"=>null,"L"=>__LINE__,"S"=>$search);
        }
        $ztypes=array();
        if(isset($_GET["types"])) {
            $ztypes = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["types"]);
        }
        if(isset($ztypes["zdate"])) {
            VERBOSE("DATE ==> {$ztypes["zdate"]}",__LINE__);
            if (preg_match("#today#", $search)) {
                $query[] = "zdate > '" . date("Y-m-d 00:00:00") . "'";
                $search = str_replace("today", "", $search);

            }
            if (preg_match("#yesterday#", $search)) {
                $query[] = "zdate > '" . date("Y-m-d 00:00:00", strtotime('-1 days')) . "' AND zdate < '" . date("Y-m-d 00:00:00") . "'";
                $search = str_replace("yesterday", "", $search);
            }
            if (preg_match("#this week#", $search)) {
                $query[] = "zdate > '" . date("Y-m-d 00:00:00", strtotime('-7 days')) . "'";
                $search = str_replace("this week", "", $search);
            }

        }else{
            if($xdate<>null){
                $query[]=$xdate;
            }
            VERBOSE("ZTYPE DATE NOT SET !",__LINE__);
        }


        VERBOSE("COUNT of query=[".count($query)."] Max=$MaxORG",__LINE__);
        if(stripos($search, " AND ")>0){
            VERBOSE("EXPLODE FOR {AND} [$search]",__LINE__);
            $ttr=preg_split("/\s+AND\s+/i",$search);
            foreach ($ttr as $line){
                $line=trim($line);
                VERBOSE("SPLIT (AND): $line",__LINE__);
                if(preg_match("#^field:(.+?)(=|<|>|LIKE|IPADDRLIKE)(\s+|)(.+?)(\s|$)#i", $line,$re)){
                    VERBOSE("MATCH ($search)",__LINE__);
                    $search=str_ireplace("field:{$re[1]}{$re[2]}{$re[3]}{$re[4]}", "", $search);
                    $qpcol=$this->qp_ident($re[1]); if($qpcol!==""){$query[]=$qpcol.$this->query_field("{$re[2]}{$re[4]}",false);}
                    continue;
                }
                if(preg_match("#^(.+?)(=|<|>|LIKE|IPADDRLIKE)(\s+|)(.+?)(\s|$)#i", $line,$re)){
                    if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>LINE.".__LINE__." <strong>MATCH</strong> $search</span><br>\n";}
                    $search=str_ireplace("{$re[1]}{$re[2]}{$re[3]}{$re[4]}", "", $search);
                    $qpcol=$this->qp_ident($re[1]); if($qpcol!==""){$query[]=$qpcol.$this->query_field("{$re[2]}{$re[4]}",false);}
                    continue;
                }

            }

        }

        if(preg_match("#^field:(.+?)(=|<|>|LIKE|IPADDRLIKE)(\s+|)(.+?)(\s|$)#i", $search,$re)){
            VERBOSE("COUNT of query=[".count($query)."] Max=$MaxORG MATCH $search",__LINE__);
            $search=str_ireplace("field:{$re[1]}{$re[2]}{$re[3]}{$re[4]}", "", $search);
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>LINE.".__LINE__." query:{$re[2]}{$re[4]} newline=[$search]</span><br>\n";}
            $qpcol=$this->qp_ident($re[1]); if($qpcol!==""){$query[]=$qpcol.$this->query_field("{$re[2]}{$re[4]}",false);}

        }
        if(preg_match("#^(.+?)(=|<|>|LIKE|IPADDRLIKE)(\s+|)(.+?)(\s|$)#i", $search,$re)){
            VERBOSE("MATCH: $search",__LINE__);
            VERBOSE("query:{$re[2]}{$re[4]} newline=[$search]",__LINE__);
            $search=str_ireplace("{$re[1]}{$re[2]}{$re[3]}{$re[4]}", "", $search);
            $qpcol=$this->qp_ident($re[1]); if($qpcol!==""){$query[]=$qpcol.$this->query_field("{$re[2]}{$re[4]}",false);}

        }

        if(preg_match("#proto\s+([a-z]+)#",$search,$re)){
            VERBOSE("MATCH $search",__LINE__);
            $search=str_replace("proto {$re[1]}", "", $search);
            $query[]="proto".$this->query_field($re[1],false);
        }
        if(preg_match("#uid(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("uid{$re[1]}{$re[2]}", "", $search);
            $query[]="LOWER(uid)".$this->query_field($re[2]);
        }
        if(preg_match("#ipaddr(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            VERBOSE("#ipaddr(\s+|=)(.+?)(\s+|$)# match!! $search",__LINE__);
            $search=str_replace("ipaddr{$re[1]}{$re[2]}", "", $search);
            $query[]=$aliases["ipaddr"].$this->query_field($re[2],false);
        }else{
            VERBOSE("#ipaddr(\s+|=)(.+?)(\s+|$)# no match",__LINE__);
        }
        if(preg_match("#mac(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("mac{$re[1]}{$re[2]}", "", $search);
            $query[]=$aliases["mac"].$this->query_field($re[2]);
        }
        if(preg_match("#src_ip(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("src_ip{$re[1]}{$re[2]}", "", $search);
            $query[]=$aliases["src_ip"].$this->query_field($re[2]);
        }

        if(preg_match("#(website|site|sitename)(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $search);
            $query[]="LOWER(".$aliases["website"].")".$this->query_field($re[3]);
        }
        if(preg_match("#(ville|city|town)(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $search);
            $query[]="LOWER(".$aliases["city"].")".$this->query_field($re[3]);
        }
        if(preg_match("#(country|pays)(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $search);
            $query[]="LOWER(".$aliases["country"].")".$this->query_field($re[3]);
        }
        if(preg_match("#(computer|host|hostname|fqdn)(\s+|=)(.+?)(\s+|$)#",$search,$re)){
            $search=str_replace("{$re[1]}{$re[2]}{$re[3]}", "", $search);
            $query[]="LOWER(".$aliases["hostname"].")".$this->query_field($re[3]);
        }

        if(preg_match("#signature\s+([0-9\<\>\-]+)#",$search,$re)){
            $search=str_replace("signature {$re[1]}", "", $search);
            $query[]="signature".$this->query_field($re[1],false);
        }
        if(strpos($search, "everything")){
            $search=str_replace("everything", "", $search);
        }

        if(preg_match("#src(\s+|=)([0-9\.\*\<\>]+)#",$search,$re)){
            if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>LINE.".__LINE__." MATCH $search</span><br>\n";}
            if(trim($re[2])<>"*"){
                $query[]=$aliases["src_ip"].$this->query_field($re[2],false);
                $search=str_replace("src{$re[1]}{$re[2]}", "", $search);
            }
        }

        if(preg_match("#src ip(\s+|=)([0-9\.\*\<\>]+)#",$search,$re)){
            $query[]="src_ip".$this->query_field($re[2],false);
            $search=str_replace("src ip{$re[1]}{$re[2]}", "", $search);
        }

        if(preg_match("#srcport(\s+|=)([0-9\.\*\<\>\-]+)#",$search,$re)){
            $query[]="src_port".$this->query_field($re[2],false);
            $search=str_replace("srcport{$re[1]}{$re[2]}", "", $search);
        }

        if(preg_match("#dstport(\s+|=)([0-9\*\<\>\-]+)#",$search,$re)){
            $query[]="dst_port".$this->query_field($re[2],false);
            $search=str_replace("dstport{$re[1]}{$re[2]}", "", $search);
        }

        if(preg_match("#date\s+([0-9\/\-:\*\<\>]+)#",$search,$re)){
            $query[]="zdate".$this->query_field($re[1],false);
            $search=str_replace("date {$re[1]}", "", $search);
        }

        $search=trim($search);
        if($max==0){$max=300;}
        if(trim($search)<>null){
            VERBOSE("FINAL [$search]",__LINE__);
            if( ( !isset($aliases["text"]) ) && ( isset($_GET["types"]) ) ){
                $types=unserialize(base64_decode($_GET["types"]),array("allowed_classes"=>false));
                if(!is_array($types)){$types=array();}
                $taliases=array();
                foreach ($types as $field_name=>$field_type){
                    $zfield_name=trim(strtolower($field_name));
                    if($zfield_name=="md5"){continue;}
                    if($zfield_name=="zmd5"){continue;}
                    if($field_type=="TEXT"){$qpcol=$this->qp_ident($field_name); if($qpcol!==""){$taliases[]=$qpcol;}}
                }
                if(count($taliases)>0){
                    $search=str_replace("*","%",$search);
                    $search=str_replace("%%","%",$search);
                    $aliases["text"]=@implode(",",$taliases);}
            }



            $xs=array();
            if(isset($aliases["text"])){
                $textes=explode(",",$aliases["text"]);
                foreach ($textes as $field){
                    if(strpos(" $search","%")==0){$search="%$search%";}
                    $xs[]="($field LIKE '$search')";

                }
            }
            if(count($xs)>0){
                $query[]="(".@implode(" OR ", $xs).")";
            }
        }
        $query_finale=array();
        if(count($query)>0){
            VERBOSE("CLEANING....",__LINE__);
            foreach ($query as $line){
                $line=trim($line);
                $line5=md5($line);
                VERBOSE("$line",__LINE__);
                $FFF[$line5]=$line;}
            $query_finale=array();
            foreach ($FFF as $index=>$line2){
                VERBOSE("ADD....[$line2]",__LINE__);
                $query_finale[]=$line2;
            }
        }
        VERBOSE("COUNT of query=[".count($query_finale)."]",__LINE__);


        if(count($query)>0){
            return array("MAX"=>$MaxORG,"Q"=>" WHERE (".@implode(" AND ", $query_finale).") ","S"=>$search);
        }
        $search="%$search%";
        $search=str_replace("*","%",$search);
        $search=str_replace("%%","%",$search);
        $search=str_ireplace("and and","and",$search);
        return array("MAX"=>$MaxORG,"L"=>__LINE__,"S"=>$search,"Q"=>$search);
    }



    private function table_object_dnsdynamic():array{
        if(isset($GLOBALS["ACLS_OBJECT:dnsdynamic"])){
            return $GLOBALS["ACLS_OBJECT:dnsdynamic"];
        }
        $stats=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/cognixsys/stats"),true);
        if(!isset($stats["Stats"])) {
            $GLOBALS["ACLS_OBJECT:dnsdynamic"] = array();
            return $GLOBALS["ACLS_OBJECT:dnsdynamic"];
        }
        if(!isset($stats["Stats"]["firewall_zones"])) {
            $GLOBALS["ACLS_OBJECT:dnsdynamic"] = array();
            return $GLOBALS["ACLS_OBJECT:dnsdynamic"];
        }
        foreach ($stats["Stats"]["firewall_zones"] as $json2){
            $entries=$json2["entries"];
            $gpid=$json2["gpid"];
            $GLOBALS["ACLS_OBJECT:dnsdynamic"][$gpid]=$entries;
        }
        if(!isset($GLOBALS["ACLS_OBJECT:dnsdynamic"])){
            $GLOBALS["ACLS_OBJECT:dnsdynamic"]=array();

        }
       return $GLOBALS["ACLS_OBJECT:dnsdynamic"];
    }

    public function table_object($gpid,$link_table=null){
        $tpl=new template_admin();
        if(!isset($_GET["DnsDist"])){$DnsDist=0;}else{$DnsDist=intval($_GET["DnsDist"]);}
        if(isset($_GET["dnsfw"])){$DnsDist=$_GET["dnsfw"];}
        $SMTP=0;
        VERBOSE("gpid=$gpid, link_table=$link_table",__LINE__);
        if(isset($_GET["TableLink"])) {
            if ($_GET["TableLink"] == "webfilters_sqacllinks") {
                $acl_allowdeny = true;
            }
            if ($_GET["TableLink"] == "dnsfw_acls_link") {
                $DnsDist = 1;
            }
            if ($_GET["TableLink"] == "dnsdist_sqacllinks") {
                $DnsDist = 1;
            }
            if ($_GET["TableLink"] == "wpad_sources_link") {
                $ProxyPac = 1;
            }
            if ($_GET["TableLink"] == "postfix_sqacllinks") {
                $SMTP = 1;
            }
        }
        VERBOSE("$gpid,$link_table",__LINE__);

        $qProxy=new mysql_squid_builder(true);
        $qProxy->acl_GroupType["doh"]="{APP_DOH_BACKEND}";
        include_once(dirname(__FILE__)."/class.acls.backend.inc");
        $g=acls_api_get("/proxy/acls/sqlite/group/".intval($gpid));
        $ligne=array("GroupName"=>"","GroupType"=>"");
        if(is_array($g)){
            $ligne["GroupName"]=$g["groupName"] ?? "";
            $ligne["GroupType"]=$g["groupType"] ?? "";
        }
        $GroupName=null;
        $GroupType=$ligne["GroupType"];



        $acl_GroupType=array();
        foreach ($qProxy->acl_GroupType as $index=>$val){
            $acl_GroupType[$index]=$val;
        }
        foreach ($qProxy->acl_GroupType_DNSFW as $index=>$val){
            $acl_GroupType[$index]=$val;
        }


        foreach ($qProxy->acl_GroupType_DNSDIST as $index=>$val){
            $acl_GroupType[$index]=$val;
        }
        foreach ($qProxy->acl_GroupType_SMTP as $index=>$val){
            if(!isset($acl_GroupType[$index])) {
                $acl_GroupType[$index] = $val;
            }
        }

        if($DnsDist==0){
            $acl_GroupType["dst"]="{dst}";
        }

        $acl_GroupType["geoip"]="{geoip_location}";

        if(isset($acl_GroupType[$GroupType])){
            $GroupTypeName=$acl_GroupType[$GroupType];
        }else{
            $GroupTypeName=$qProxy->GroupTypeToString($GroupType);
        }


        $GroupTypeName=$this->_ENGINE_parse_body($GroupTypeName);
        $items=0;
        VERBOSE("$gpid - $GroupType - $GroupTypeName - {$ligne["GroupName"]}",__LINE__);

        $icon=$qProxy->acl_GroupTypeIcon[$GroupType];



        if($GroupType=="dnsdynamic"){
            $arry=$this->table_object_dnsdynamic();
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>$arry[$gpid],"TYPE"=>$GroupTypeName,
                "NOITEMS"=>False,"ICO"=>$icon);

        }

        if($GroupType=="AclsGroup"){
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $cnt=acls_api_get("/proxy/acls/sqlite/gpslink-count?groupid=".intval($gpid));
            $ligneNet=array("tcount"=> is_array($cnt) ? intval($cnt["count"]) : 0);
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>$ligneNet["tcount"],"TYPE"=>$GroupTypeName,
                "NOITEMS"=>False,"ICO"=>$icon);
        }


        if($GroupType=="localnet"){
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $GroupTypeName=$this->_ENGINE_parse_body($qProxy->acl_GroupType[$GroupType]);
            $cnt=acls_api_get("/proxy/acls/sqlite/networks/enabled/count");
            $ligneNet=array("tcount"=> is_array($cnt) ? intval($cnt["count"]) : 0);
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>$ligneNet["tcount"],"TYPE"=>$GroupTypeName,
                "NOITEMS"=>False,"ICO"=>$icon);
        }

        if($GroupType=="url_db"){
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $GroupTypeName=$this->_ENGINE_parse_body($qProxy->acl_GroupType[$GroupType]);
            $WorkDir="/etc/squid3/acls/urlsdb/$gpid";
            $Count=intval(@file_get_contents("$WorkDir/COUNT"));
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>$Count,"TYPE"=>$GroupTypeName,"NOITEMS"=>true,"ICO"=>$icon);

        }
        if($GroupType=="the_shields"){
            $ERROR=null;
            $license=true;
            $kInfos            =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()){
                if (!isset($kInfos["enable"])) {$kInfos["enable"] = 0;}
                if (!isset($kInfos["status"])) {$kInfos["status"] = null;}
                VERBOSE("enable = {$kInfos["enable"]}, status= {$kInfos["status"]}",__LINE__);
                if ($kInfos["enable"] == 0) {$license = false;}
                if ($kInfos["enable"] == 0 && $kInfos["status"] == null) {$license = false;}

                if ($kInfos["enable"] == 1) {
                    $license=true;
                    if (intval($kInfos["expire"]) > 0) {
                        $reste_days = $this->TimeToDays($kInfos["expire"]);
                        if ($reste_days == 0) {$license = false;}
                    }
                }
            }
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $GroupTypeName=$this->_ENGINE_parse_body($qProxy->acl_GroupType[$GroupType]);
            $KSRNAsACls      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNAsACls"));
            $KSRNEnable      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
            $KSRNEmergency  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEmergency"));
            if($link_table=="dnsfw_acls_link"){$KSRNAsACls=1;}
            if(!$license){$ERROR="{license_error}";}
            if($KSRNEmergency==1){$ERROR="{emergency_mode}";}
            if(!$GLOBALS["CLASS_SOCKETS"]->TheShieldEnabled()){
                $ERROR="{external_module_disabled}";
            }
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>0,
                "TYPE"=>$GroupTypeName,"NOITEMS"=>true,"ERROR"=>$ERROR,"ICO"=>$icon);
        }

        if($GroupType=="dnsquerytype"){
            $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
            $GroupTypeName=$this->_ENGINE_parse_body("{dnsquerytype}");
            $cnt=acls_api_get("/proxy/acls/sqlite/items-count?gpid=".intval($gpid));
            $items=is_array($cnt) ? intval($cnt["count"]) : 0;
            return array("GROUPNAME"=>"$GroupName","ITEMS"=>$items,
                "TYPE"=>$GroupTypeName,"NOITEMS"=>true,"ERROR"=>null,"ICO"=>$icon);
        }

        if($GroupType=="proxy_auth_ldap"){
            include_once(dirname(__FILE__).'/class.ldap-extern.inc');
            $p=new ldap_extern();
            preg_match("#^ExtLDAP:(.+?):(.+)#", $ligne['GroupName'],$re);
            $GroupName=$re[1];
            $DN=base64_decode($re[2]);
            $items=$p->CountDeUsersByGroupDN($DN);
        }
        if(preg_match("#time_quota:([0-9]+)#", $GroupType,$re)){
            $qo=acls_api_get("/proxy/acls/sqlite/quota-object/".intval($re[1]));
            $qoName=is_array($qo) ? ($qo["objectName"] ?? "") : "";
            $GroupTypeName=$this->_ENGINE_parse_body("{time_quota} $qoName");
        }
        if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $GroupType,$re)){
            $qProxy->acl_ARRAY_NO_ITEM[$GroupType]=true;
            $so=acls_api_get("/proxy/acls/sqlite/session-object/".intval($re[2]));
            $soName=is_array($so) ? ($so["objectName"] ?? "") : "";
            $GroupTypeName=$this->_ENGINE_parse_body("{session_tracking} - {$re[1]} - $soName");
        }


        if($GroupName==null){$GroupName=$this->utf8_encode($ligne["GroupName"]);}
        if($GroupType=="all"){$items="*";}

    if (isset($qProxy->acl_ARRAY_NO_ITEM[$GroupType])) {
        return array("GROUPNAME" => "$GroupName", "ITEMS" => $items, "TYPE" => $GroupTypeName, "NOITEMS" => True, "ICO" => $icon);
    }


        if($items==0){
            $cnt=acls_api_get("/proxy/acls/sqlite/items-count?gpid=".intval($gpid));
            $items=is_array($cnt) ? intval($cnt["count"]) : 0;
        }


        return array("GROUPNAME"=>"$GroupName","ITEMS"=>$items,"TYPE"=>$GroupTypeName,"NOITEMS"=>False,"ICO"=>$icon);


    }

    public function GetProgressBarr($title,$prc):string{
        $myid=time();
        $html[]="<div>";
        $html[]="<h5 id=\"title-$myid\">$title</h5>";
        $html[]="<div class=\"progress progress-bar-default\"";
        $html[]="id=\"main-$myid\">";
        $html[]="<div id=\"barr-$myid\" style=\"width: $prc%\" aria-valuemax=\"100\"";
        $html[]="aria-valuemin=\"0\" aria-valuenow=\"5\" role=\"progressbar\"";
        $html[]="class=\"progress-bar\">$prc% $title</div></div></div>";
        return @implode(" ",$html);
    }
    

    public function CleanServiceName($source){
        $source=utf8_decode($source);
        $source=str_replace("_", "", $source);
        $source=str_replace(" ", "", $source);
        $source=str_replace("-", "", $source);
        $source=str_replace("%24", "", $source);
        $source=str_replace(" ", "", $source);
        $source=str_replace("\r ", "", $source);
        $source=str_replace("\n ", "", $source);
        $source=str_replace('\\', "", $source);
        $source=str_replace('/', "", $source);
        $source=str_replace('#', "", $source);
        $source=str_replace('"', "", $source);
        $source=str_replace('"', "", $source);
        $source=str_replace('&', "", $source);
        $source=str_replace('(', "", $source);
        $source=str_replace(')', "", $source);
        $source=str_replace('@', "", $source);
        $source=str_replace('[', "", $source);
        $source=str_replace(']', "", $source);
        $source=str_replace('{', "", $source);
        $source=str_replace('}', "", $source);
        $source=str_replace('à', "", $source);
        $source=str_replace('é', "", $source);
        $source=str_replace('^', "", $source);
        $source=str_replace('²', "", $source);
        $source=str_replace('=', "", $source);
        $source=str_replace(";", "", $source);
        $source=str_replace(",", "", $source);
        $source=str_replace("!", "", $source);
        $source=str_replace("§", "", $source);
        $source=str_replace("€", "", $source);
        $source=str_replace("£", "", $source);
        $source=str_replace("*", "", $source);
        $source=str_replace("%", "", $source);
        $source=str_replace("ù", "", $source);
        $source=str_replace("°", "", $source);
        $source=str_replace("|", "", $source);
        $source=str_replace("+", "", $source);
        $source=str_replace(")", "", $source);
        $source=str_replace("(", "", $source);
        $source=replace_accents($source);
        $source=trim(strtolower($source));
        return $source;
    }

    public function js_microdaliog_danger($title,$ajax):bool{
        header("content-type: application/x-javascript");
        $title=$this->_ENGINE_parse_body($title);
        $title=replace_accents($title);
        $title=$this->javascript_parse_text($title);

        //$draggable="draggable: false,";
        //$closable="closable: false,";
        $close_button="\n\tbuttons: [{ label: 'Close', action: function(dialogRef) { dialogRef.close(); } }],";


        echo "
if(typeof dialogInstance == 'object'){ dialogInstance.close();}	
		dialogInstance = new BootstrapDialog({
		draggable: false,
		closable: false,
		size: BootstrapDialog.SIZE_NORMAL,
		title: '$title',
		type: BootstrapDialog.TYPE_DANGER,
		message: function(dialog) {
		var \$message = $('<div><img src=\"img/wait-200.gif\" alt=\"\" class=centerimg></div>');
		var pageToLoad = dialog.getData('pageToLoad');
		\$message.load(pageToLoad);
		return \$message;
	},
	$close_button
	data: {
	'pageToLoad': '$ajax'
	}
	});
	
if(typeof dialogInstance == 'object'){ 
	dialogInstance.open();
	LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
}";
        return true;
    }
    public function js_no_license():bool{
        return $this->popup_error("{license_invalid}");

    }
    public function js_no_privileges():bool{
        return $this->popup_error("{ERROR_NO_PRIVS2}");
    }

    public function js_dialog1($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
        return $this->buildADialog($title,$ajax,$size,1);
    }
    public function js_restart_api($endpoint,$after=null):string{
        $MAIN["ENDPOINT"]=$endpoint;
        $MAIN["AFTER"]=$after;
        $bond=base64_encode(serialize($MAIN));
        return "Loadjs('fw.restartapi.php?restart-via-api=$bond');";
    }

    public function js_dialog2($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
        return $this->buildADialog($title,$ajax,$size,2);
    }
    public function js_dialog3($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
        return $this->buildADialog($title,$ajax,$size,3);
    }
    public function js_dialog4($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
       return  $this->buildADialog($title,$ajax,$size,4);
    }
    public function js_dialog5($title,$ajax,$size=null):bool{
        if($size==null){$size=900;}
       return  $this->buildADialog($title,$ajax,$size,5);
    }


    private function js_dialogsize($size=null){
        $SIZE_WIDE="SIZE_WIDE";
        if(is_null($size)){$size=650;}
        if($size=="small"){$SIZE_WIDE="SIZE_SMALL";}
        if($size<=500){$SIZE_WIDE="SIZE_SMALL";}
        if($size>=500){$SIZE_WIDE="SIZE_NORMAL";}

        $cssClass="size: BootstrapDialog.$SIZE_WIDE,";
        if($size>649){$cssClass="cssClass: 'dialog650',";}
        if($size>699){$cssClass="cssClass: 'dialog700',";}
        if($size>749){$cssClass="cssClass: 'dialog750',";}
        if($size>809){$cssClass="cssClass: 'dialog810',";}
        if($size>849){$cssClass="cssClass: 'dialog850',";}
        if($size>879){$cssClass="cssClass: 'dialog880',";}
        if($size>900){$cssClass="cssClass: 'dialog900',";}
        if($size>945){$cssClass="cssClass: 'dialog950',";}
        if($size>970){$cssClass="cssClass: 'dialog980',";}
        if($size>1023){$cssClass="cssClass: 'dialog1024',";}
        if($size>1029){$cssClass="cssClass: 'dialog1030',";}
        if($size>1099){$cssClass="cssClass: 'dialog1100',";}
        if($size>1140){$cssClass="cssClass: 'dialog1500',";}
        if($size>1199){$cssClass="cssClass: 'dialog1200',";}
        if($size>1369){$cssClass="cssClass: 'dialog1370',";}
        return $cssClass;
    }
    public function js_dialog6($title,$ajax,$size=null,$onclose=null):bool{return $this->buildADialog($title,$ajax,$size,6,$onclose);}
    public function js_dialog7($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,7);}
    public function js_dialog8($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,8);}
    public function js_dialog9($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,9);}
    public function js_dialog10($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,10);}
    public function js_dialog11($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,11);}
    public function js_dialog12($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,12);}
    public function js_dialog13($title,$ajax,$size=650):bool{return $this->buildADialog($title,$ajax,$size,13);}

    // Titre d'une fenetre BootstrapDialog, place dans une chaine JavaScript '...'
    // puis insere en HTML par la fenetre. Les accents restent en entites HTML et
    // l'apostrophe devient \u0027 : avant, replace_accents() retirait les accents
    // et javascript_parse_text() changeait l'apostrophe en accent grave
    // (« Comptes d`enrolement »). Le HTML passe par le filtre du pentest
    // 2026-11-04 (html_safe_fragment), comme avec js_title_safe().
    public function js_dialog_title($title):string{
        if(is_array($title) || is_null($title)){return "";}
        $title=$this->_ENGINE_parse_body((string)$title);
        if($title===null){return "";}
        $title=str_replace(array("\r","\n","\t"),array(""," "," "),(string)$title);
        if(function_exists("mb_check_encoding") && !mb_check_encoding($title,"UTF-8")){
            $title=mb_convert_encoding($title,"UTF-8","ISO-8859-1");
        }
        $title=html_safe_fragment($title);
        return str_replace(array("\\","'","</"),array("\\\\","\\u0027","<\\/"),$title);
    }

    private function buildADialog($title,$ajax,$size,$number,$onhidejs=null):bool{
        $cssClass=$this->js_dialogsize($size);
        $mODAL=false;
        if(!is_null($title)) {
            if (preg_match("#modal:(.+)#", $title, $re)) {
                $title = $re[1];
                $mODAL = true;
            }
        }
        if(preg_match("#'(.+?)'#",$ajax,$re)){$ajax=$re[1];}

        $array[0]="TYPE_WARNING";
        $array[1]="TYPE_SUCCESS";
        $array[2]="TYPE_INFO";
        $array[3]="TYPE_PRIMARY";

        $type=rand(0, 3);
        $sied=md5(rand(0,65000).time());
        header("content-type: application/x-javascript");
        // Titre en HTML, accents et apostrophes conserves (js_dialog_title).
        $title=$this->js_dialog_title($title);
        $ajax=js_url_safe($ajax);
        $draggable="draggable: true,";
        $closable="closable: true,";
        $close_button=null;
        if($mODAL){
            $draggable="draggable: true,";
            $closable="closable: true,";

        }
        echo "
	//size=$size dialog number $number;
	if(typeof dialogInstance$number == 'object'){ dialogInstance$number.close();}
	dialogInstance$number = new BootstrapDialog({
		$draggable
		$closable
		$cssClass
		title: '$title',
		type: BootstrapDialog.$array[$type],
		autodestroy:true,
		closeByBackdrop: false,
		message: function(dialog) {
		    // NE PAS charger le contenu ici. A cet instant le noeud retourne est
		    // DETACHE du document, or jQuery n'execute PAS les <script> d'un fragment
		    // insere dans un noeud detache (garde jQuery.contains(doc,node) dans
		    // domManip). Selon que la reponse HTTP arrivait avant ou apres l'insertion
		    // du modal par BootstrapDialog, le <script> du fragment tournait ou etait
		    // silencieusement ignore: modale vide par intermittence, typiquement a la
		    // 2e ouverture (reponse servie plus vite). Le chargement se fait donc dans
		    // onshown, ou le corps du modal est bien dans le document.
		    return $('<div><img src=\"img/wait-200.gif\" class=centerimg alt=\"\"></div>');
	    },
		data: { 'pageToLoad': '$ajax','id':'$sied' },
		onshow: function(dialogRef){ $('body').Wload(); },
		onshown: function(dialogRef){
		    $('body').Wload('hide',{time:800});
		    // Neutralise les caracteres qui cassent jQuery .load(url): apres un
		    // espace, tout ce qui suit est pris pour un selecteur CSS (erreur
		    // Sizzle). On N'UTILISE PAS encodeURI(): contrairement a ce que
		    // l'ancien commentaire affirmait, elle re-encode le pourcent des
		    // sequences deja echappees (%3D devient %253D). Une URL construite
		    // avec urlencode() cote PHP revenait donc DOUBLEMENT encodee, et la
		    // page destinataire lisait une valeur encore echappee -- un DN LDAP
		    // CN=x,DC=y arrivait sous la forme CN%3Dx%2CDC%3Dy. Le pourcent est
		    // volontairement absent de la classe ci-dessous: la transformation
		    // reste ainsi idempotente.
		    dialogRef.getModalBody().load(String(dialogRef.getData('pageToLoad')).replace(/[ \"'<>`]/g, function(c){
		        return '%' + c.charCodeAt(0).toString(16).toUpperCase();
		    }));
		},
		onhide: function(dialogRef){
		        var id=$(this).attr('id');
		        $('#'+id).remove();
                $onhidejs
            },$close_button
	});
	
		if(typeof dialogInstance{$number} == 'object'){
			dialogInstance{$number}.open();
			LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		}";
        return true;
    }



    public function item_explain($GroupType):string{
        $explain=null;
        if($GroupType=="all"){$explain="{acl_all_text}";}
        if($GroupType=="src"){$explain="{acl_src_text}";}
        if($GroupType=="dst"){$explain="{acl_dst_text}";}
        if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
        if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
        if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
        if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
        if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
        if($GroupType=="req_mime_type"){$explain="{req_mime_type_explain}";}
        if($GroupType=="rep_mime_type"){$explain="{rep_mime_type_explain}";}
        if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
        if($GroupType=="srcdomain"){$explain="{acl_squid_srcdomain_explain}";}
        if($GroupType=="url_regex_extensions"){$explain="{url_regex_extensions_explain}";}
        if($GroupType=="max_user_ip"){$explain="<b>{acl_max_user_ip_title}</b><br>{acl_max_user_ip_text}";}
        //if($GroupType=="quota_time"){$explain="{acl_quota_time_text}";}
        if($GroupType=="quota_size"){$explain="{acl_quota_size_text}";}
        if($GroupType=="ssl_sni"){$explain="{acl_ssl_sni_text}";}
        if($GroupType=="myportname"){$explain="{acl_myportname_text}";}
        if($GroupType=="rep_header_filename"){$explain="{rep_header_filename_explain}";}
        if($GroupType=="ldap_group"){$explain="{ldap_group}";}
        if($GroupType=="server_cert_fingerprint"){$explain="{server_cert_fingerprint_explain}";}
        if($GroupType=="weekrange"){$explain="{weekranges}";}
        if($GroupType=="AclsGroup"){$explain="{new_group_of_objects_explain}";}
        if($GroupType=="envfrom"){$explain="{acl_envfrom_explain}";}
        if($GroupType=="envto"){$explain="{acl_envto_explain}";}


        if($explain==null){
            $Sql=new mysql_squid_builder();
            if(isset($Sql->acl_GroupType_explain[$GroupType])){
                $explain=$Sql->acl_GroupType_explain[$GroupType];
            }

        }


        return strval($explain);
    }

    public function js_dialog_modal($title,$ajax,$size=650,$OnClose=null):bool{
        $cssClass=$this->js_dialogsize($size);
        $array[0]="TYPE_WARNING";
        $array[1]="TYPE_SUCCESS";
        $array[2]="TYPE_INFO";
        $array[3]="TYPE_PRIMARY";
        $Close=$this->javascript_parse_text("{cancel}");
        $type=rand(0, 3);
        header("content-type: application/x-javascript");
        $title=$this->_ENGINE_parse_body($title);
        $title=trim($title);
        $title=replace_accents($title);

        echo "DialogModal = new BootstrapDialog({
		draggable: false,
		title: '$title',
		$cssClass
		type: BootstrapDialog.$array[$type],
		autodestroy: true,
		closable: false,
		buttons: [{ label: '$Close', action: function(dialogRef) { dialogRef.close(); } }],
		message: function(dialog) {
		var \$message = $('<div><img src=\"img/wait-200.gif\" class=centerimg alt=\"\"></div>');
		var pageToLoad = dialog.getData('pageToLoad');
		\$message.load(pageToLoad);
		
		return \$message;
		},
		data: { 'pageToLoad': '$ajax' },
        onshow: function(dialogRef){ $('body').Wload(); },
        onshown: function(dialogRef){ $('body').Wload('hide',{time:800});}
		});
        DialogModal.realize();
        DialogModal.getModalHeader().hide();
        DialogModal.getModalFooter().hide();
        DialogModal.getModalBody().css('background-color', '#FFFFFF');
        DialogModal.getModalBody().css('border', '0px');
		DialogModal.open();";
        return true;
    }

    public function js_dialog_confirm($title,$ajax){
        header("content-type: application/x-javascript");
        $Close=$this->javascript_parse_text("{cancel}");
        // Titre en HTML, accents et apostrophes conserves (js_dialog_title).
        $title=$this->js_dialog_title(trim((string)$title));
        $ajax=js_url_safe($ajax);
        echo "DialogConfirm = new BootstrapDialog({
		draggable: false,
		size: BootstrapDialog.SIZE_NORMAL,
		title: '$title',
		type: BootstrapDialog.TYPE_DANGER,
		buttons: [{ label: '$Close', action: function(dialogRef) { dialogRef.close(); } }],
		message: function(dialog) {
		var \$message = $('<div><img src=\"img/wait-200.gif\" class=centerimg></div>');
		var pageToLoad = dialog.getData('pageToLoad');
		\$message.load(pageToLoad);
		
		return \$message;
		},
		data: { 'pageToLoad': '$ajax' },
        onshow: function(dialogRef){ $('body').Wload(); },
        onshown: function(dialogRef){ $('body').Wload('hide',{time:800});}
		});
		
		
		DialogConfirm.open();";


    }

    public function CleanWebSite($site){
        $IP=new IP();
        $site=trim(strtolower($site));
        if($IP->isIPAddressOrRange($site)){return $site;}
        if(preg_match("#^http#", $site)){
            $arrayURI=parse_url($site);
            $site=$arrayURI["host"];
        }

        if(strpos($site, "/")>0){
            $site="http://$site";
            $arrayURI=parse_url($site);
            $site=$arrayURI["host"];
        }

        if(preg_match("#(.+.):([0-9]+)", $site,$re)){
            $site=$re[1];
        }
        return $site;
    }


    public function js_dialog($title,$ajax,$onhidejs=null):bool{
        header("content-type: application/x-javascript");
        // Titre en HTML, accents et apostrophes conserves (js_dialog_title).
        $title=$this->js_dialog_title($title);
        $ajax=js_url_safe($ajax);
        echo "var types = [BootstrapDialog.TYPE_DEFAULT,
		BootstrapDialog.TYPE_INFO,
		BootstrapDialog.TYPE_PRIMARY,
		BootstrapDialog.TYPE_SUCCESS,
		BootstrapDialog.TYPE_WARNING,
		BootstrapDialog.TYPE_DANGER];
		

		var BootstrapDialog1 = BootstrapDialog.show({
			draggable: true,
			size: BootstrapDialog.SIZE_WIDE,
			title: '$title',
			type: BootstrapDialog.TYPE_SUCCESS,
            message: function(dialog) {
                var \$message = $('<div><img src=\"img/wait-200.gif\" class=centerimg alt=\"\"></div>');
                var pageToLoad = dialog.getData('pageToLoad');
                \$message.load(pageToLoad);
        
                return \$message;
            },
		data: { 'pageToLoad': '$ajax' },
        onshow: function(dialogRef){ $('body').Wload(); },
        onshown: function(dialogRef){ $('body').Wload('hide',{time:800});},
        onhide: function(dialogRef){
                $onhidejs
            },
        });";

        return true;


    }
    public function StripSpecialsChars($pattern){

        $html=new htmltools_inc();
        return $html->StripSpecialsChars($pattern);
    }

    public function form_add_button_upload($btname,$page=null,$security=null):string{
        if($security<>null){if(!$this->IsSecurity()){return false;}}
        $this->buttons["UPLOAD::$btname"]=$page;
        return "";
    }


    public function form_add_button($btname,$js):string{
        $btname=$this->_ENGINE_parse_body($btname);
        $this->buttons[$btname]=$js;
        return "";
    }

    public function form_add_button_title($btname,$js):string{
        $this->button_title[$btname]=$js;
        return "";
    }

    private function form_button($btname,$js,$classname=null,$security=null):string{
        // Bouton de pied de form_outside() (1 800 formulaires) : systeme abt, 36 px.
        // Sans classe = le bouton d'enregistrement, principal. Bouton ajoute
        // (form_add_button) = role deduit du libelle : les trois couleurs tournantes
        // (warning/info/success) ne disaient rien. Les guillemets � � sont retires.
        if($security<>null){if(!$this->IsSecurity($security)){return $this->form_button_disabled($btname);}}
        if(preg_match("#^UPLOAD::(.+)#", $btname,$re)){
            return $this->button_upload($re[1],$js,"BYFORM:$classname");
        }
        if($classname==null){
            $variant="abt-p";
        }elseif(preg_match('#^abt-[a-z0-9]+$#',(string)$classname)){
            $variant=$this->abt_role("",strip_tags((string)$btname))=="x"?"abt-x":$classname;
        }else{
            $map=array("p"=>"abt-p","s"=>"abt-s","x"=>"abt-x");
            $variant=$map[$this->abt_role("",strip_tags((string)$btname))];
        }
        $this->button_id=md5("$btname$js");
        $bt="<button class=\"btn ".$this->abt_bs_opt($variant)." abt $variant\" type=\"button\" id='$this->button_id' OnClick=\"$js\"><span>$btname</span></button>";
        html_trusted($bt);
        return "\n".$bt;
    }

    private function form_button_disabled($btname):string{
        // Formulaire verrouille (droits, licence, esclave de cluster) : cadenas, la raison
        // est donnee par l'alerte affichee au-dessus du formulaire.
        if(preg_match("#^UPLOAD::(.+)#", $btname,$re)){$btname=$re[1];}
        $bt="<button class=\"btn abt abt-s abt-l\" type=\"button\" aria-disabled=\"true\"><i class=\"fa-solid fa-lock\"></i><span>$btname</span></button>";
        html_trusted($bt);
        return "\n".$bt;
    }

    //"btn-primary","small"
    public function form_button_upload($btname,$page,$explain=null,$security=null):string{
        $bt=$this->button_upload($btname,$page);
        if($security<>null){if(!$this->IsSecurity($security)){$bt=$this->form_button_disabled($btname);}}
        return $this->_field_intr("&nbsp;",$bt,$explain);
    }

    public function button_medium($btname,$class=null,$js=null):string{
        // Petit bouton (16 appels). Une couleur Bootstrap (btn-*) donne un role ; un mot
        // de couleur (ok, warning, error...) porte un etat : il devient une pastille a
        // cote du libelle, qui dit deja l'etat en toutes lettres. Sans JS : badge d'etat.
        $btgb=$this->btn_array();
        $key=is_null($class)?"":$class;
        $sclass=isset($btgb[$key])?$btgb[$key]:"btn-primary";
        $tones=array("btn-primary"=>"ok","btn-warning"=>"w","btn-danger"=>"x","btn-default"=>"n");
        $isState=!is_null($class) && strpos((string)$class,"btn-")!==0 && isset($tones[$sclass]);
        $isAction=preg_match('#^\{((start|stop|restart|install|uninstall|apply|execute|run|enable|disable|reload)(_[a-z0-9_]*)?|rspamd\.learning\.[a-z_]+)\}#i',trim((string)$btname))==1;
        $label=$this->_ENGINE_parse_body($btname);
        $jsx=trim((string)$js);
        if(strlen($jsx)<2 || preg_match('#^blur\(\);?$#i',$jsx)){
            $ok=$isState && $tones[$sclass]=="ok";
            $bt="<span class=\"abt-state".($ok?" abt-state-ok":"")."\">$label</span>";
            html_trusted($bt);
            return $bt;
        }
        if($isState && !$isAction){
            // L'etat colore le bouton comme avant (vert, orange, rouge ; blanc = inactif),
            // le libelle le dit deja en toutes lettres.
            $toneCls=array("ok"=>"abt-s","w"=>"abt-c4","x"=>"abt-x","n"=>"abt-o");
            $sclass=$toneCls[$tones[$sclass]];
        }
        if($isAction){
            // Une action garde le role de son libelle (apply = principal, uninstall =
            // destructif) : "Install" n'est pas rouge parce que le service manque.
            $map=array("p"=>"abt-p","s"=>"abt-s","x"=>"abt-x");
            $sclass=$map[$this->abt_role("",(string)$btname)];
        }
        return $this->button_inline($label,$jsx,"",null,0,$sclass,"small");
    }
    
    /**
     * Petit bouton dans une cellule ou un en-tete de carte (8 appels). Meme systeme que
     * button_inline() en taille small : role deduit, cadenas si le droit manque.
     * Sans JS, ce n'est pas une action mais un etat ("Trusted") : badge abt-state,
     * vert pour label-primary/label-success, neutre sinon.
     */
    public function button_tooltip($btname,$js=null,$icon_class=null,$security=null,$class="label-primary"):string{
        $jsx=trim((string)$js);
        if(strlen($jsx)<2 || preg_match('#^blur\(\);?$#i',$jsx)){
            $icon_class=trim((string)$icon_class);
            if($icon_class<>"" && strpos($icon_class," ")===false){$icon_class="fa $icon_class";}
            $ok=preg_match('#label-(primary|success)#',(string)$class)==1;
            $ico=$icon_class<>""?"<i class=\"$icon_class\" aria-hidden=\"true\"></i>":"";
            $bt="<span class=\"abt-state".($ok?" abt-state-ok":"")."\">$ico$btname</span>";
            html_trusted($bt);
            return $bt;
        }
        // label-danger signalait l'IP bloquee, pas une action destructive : on ne garde
        // pas la couleur, le role vient du libelle et de l'icone (refresh -> neutre).
        return $this->button_inline($btname,$jsx,$icon_class,$security,0,"btn-default","small");
    }
    
    /**
     * Bouton en ligne : dans un texte, sous un titre, dans une cellule ou un pied de
     * carte (35 appels). Meme systeme que button_autnonome() (styles table_buttons_css()) :
     *  - role deduit (abt_page_role) ; l'aide ("Online help", casque) devient un lien
     *    discret (abt-g) qui ne concurrence pas le titre ;
     *  - une ligne, jamais de retour ; $width (ignore jusqu'ici) sert de largeur minimale ;
     *  - droit manquant : cadenas + raison au survol ; JS vide ou blur() : desactive.
     * Une classe "abt-…" passee en $class est prise telle quelle (variante explicite).
     */
    public function button_inline($btname,$js,$icon_class,$security=null,$width=0,$class="btn-primary",$btsize=null):string{
        $icon_class=trim((string)$icon_class);
        if($icon_class<>"" && strpos($icon_class," ")===false){$icon_class="fa $icon_class";}
        $class=trim((string)$class);
        if(preg_match('#^abt-[a-z0-9]+$#',$class)){
            $variant=$class;
        }else{
            $role=$this->abt_page_role($class,$icon_class,$btname);
            // En ligne, le principal est rare : une action repetee sur chaque ligne
            // (small) ou une maintenance (btn-warning, btn-info) reste neutre.
            if($role=="p" && ($btsize=="small" || $class=="btn-warning" || $class=="btn-info")){$role="s";}
            $map=array("p"=>"abt-p","s"=>"abt-s","x"=>"abt-x");
            $variant=$map[$role];
            if($role=="s" && $this->abt_is_help($icon_class,$btname)){$variant="abt-g";}
        }
        $cls=array("btn",$this->abt_bs_opt($variant),"abt","abt-i",$variant);
        if($btsize=="small"){$cls[]="abt-sm";}
        $jsx=trim((string)$js);
        $nojs=strlen($jsx)<2 || preg_match('#^blur\(\);?$#i',$jsx);
        $attr="";
        if($security<>null && !$this->IsSecurity($security)){
            $cls[]="abt-l";
            $icon_class="fa-solid fa-lock";
            $tip=str_replace('"',"&quot;",$this->_ENGINE_parse_body("{this_form_locked_privileges}"));
            $attr=" aria-disabled=\"true\" data-tip=\"$tip\"";
        }elseif($nojs){
            $attr=" disabled";
        }else{
            $attr=" OnClick=\"$jsx\"";
        }
        $style="";
        if(is_numeric($width) && intval($width)>0){$style=" style='min-width:".intval($width)."px'";}
        $ico=$icon_class<>""?"<i class=\"$icon_class\"></i>":"";
        $bt="<button type=\"button\" class=\"".@implode(" ",$cls)."\"$attr$style>$ico<span>$btname</span></button>";
        html_trusted($bt);
        return $bt;
    }

    /** Aide en ligne : libelle d'aide ou icone casque / point d'interrogation. */
    private function abt_is_help($icon,$label):bool{
        if(preg_match('#^\{(online_help|see_doc|howto|help|wiki)\}#i',trim((string)$label))){return true;}
        return preg_match('#fa-(headset|user-headset|circle-question|question-circle|life-ring)(\s|$)#',(string)$icon)==1;
    }

    /**
     * Interrupteur d'etat (role=switch) qui remplace un faux bouton "Enabled/Disabled".
     * Le libelle reste visible a cote : l'etat n'est jamais porte par la seule couleur.
     * $js vide = lecture seule (l'etat est montre, pas modifiable ici).
     */
    public function inline_switch($label,$checked,$js=null,$security=null):string{
        $on=$checked?"true":"false";
        $cls="abt-sw".($checked?" abt-sw-on":"");
        $jsx=trim((string)$js);
        $attr=" OnClick=\"$jsx\"";
        if($security<>null && !$this->IsSecurity($security)){
            $tip=str_replace('"',"&quot;",$this->_ENGINE_parse_body("{this_form_locked_privileges}"));
            $attr=" aria-disabled=\"true\" data-tip=\"$tip\"";
            $cls.=" abt-sw-ro abt-l";
        }elseif(strlen($jsx)<2){
            $attr=" aria-disabled=\"true\"";
            $cls.=" abt-sw-ro";
        }
        $bt="<button type=\"button\" role=\"switch\" aria-checked=\"$on\" class=\"$cls\"$attr><span class=\"abt-sw-track\"><span class=\"abt-sw-knob\"></span></span><span class=\"abt-sw-lbl\">$label</span></button>";
        html_trusted($bt);
        return $bt;
    }

    /**
     * Valeur editable "a / b" (poids d'un backend…) : pastille en chiffres tabulaires,
     * suivie d'un badge d'etat ecrit en toutes lettres. $warn=true : style d'alerte.
     * $label non vide : remplace "$value / $max" par ce texte deja compose.
     */
    public function inline_metric($value,$max,$js,$icon,$security,$state,$warn=false,$aria="",$label=""):string{
        $cls=$warn?"abt-drain":"abt-s";
        $shown=($label<>"")?$label:"$value&nbsp;/&nbsp;$max";
        $txt="<span class=\"abt-num\">$shown</span>";
        $bt=$this->button_inline($txt,$js,$icon,$security,0,$cls,"small");
        if($aria<>""){
            $aria=str_replace('"',"&quot;",$aria);
            $bt=preg_replace('#^<button #',"<button aria-label=\"$aria\" ",$bt);
        }
        $badge="<span class=\"abt-state".($warn?" abt-state-w":"")."\">$state</span>";
        $out="<span class=\"abt-metric\">$bt$badge</span>";
        html_trusted($out);
        return $out;
    }
    public function button_wiki($url){
        $url="s_PopUpFull('$url','1024','900');";
        return $this->button_autnonome("{online_help}",$url,ico_support,null,null,"btn-info");
    }

    /**
     * Bouton d'action du corps de page (471 appels). Rendu par role, styles dans
     * table_buttons_css() (servie par angular.css.php) :
     *  - role deduit de la classe demandee, corrige par le sens : une suppression est
     *    toujours rouge, "Refresh"/"Close"/aide restent neutres meme demandes en rouge ;
     *  - largeur >= 400 px : grand bouton d'assistant (tuile d'icone + libelle + chevron) ;
     *  - largeur fixe : le libelle passe a la ligne au lieu de deborder ;
     *  - droit manquant : bouton verrouille (cadenas + raison au survol), plus « label » ;
     *  - JS vide : bouton desactive.
     * La classe "btn" est conservee : les regles de page (.tfrm .btn, .svcstat .btn,
     * kits de page) plus precises que .abt continuent de s'appliquer.
     */
    public function button_autnonome($btname,$js,$icon_class,$security=null,$width=0,$class="btn-primary",$btsize=null):string{
        $xlog="<!-- class=$class -->";
        if($icon_class==null){$icon_class=ico_params;}
        $icon_class=trim($icon_class);
        if(strpos($icon_class," ")===false){$icon_class="fa $icon_class";}
        if(preg_match("#^UPLOAD::(.+)#",$btname,$re)){$btname=$re[1];}

        $role=$this->abt_page_role($class,$icon_class,$btname);
        $locked=false;
        if($security<>null){if(!$this->IsSecurity($security)){$locked=true;}}
        $nojs=strlen(trim((string)$js))<2;

        $widthcss="";
        $wpx=0;
        if(is_numeric($width)){
            $wpx=intval($width);
            if($wpx>0){$widthcss="width:{$wpx}px;max-width:100%";}
        }elseif(is_string($width) && strpos($width,"%")>0){
            $widthcss="width:".preg_replace('#[^0-9.%]#',"",$width);
        }
        $wizard=$wpx>=400;

        $cls=array("btn","abt");
        if($wizard){
            $cls[]="abt-wiz";
            if($role=="x"){$cls[]="abt-wiz-x";}
            if($role=="s"){$cls[]="abt-wiz-s";}
        }else{
            $map=array("p"=>"abt-p","s"=>"abt-s","x"=>"abt-x");
            $cls[]=$this->abt_bs_opt($map[$role]);
            $cls[]=$map[$role];
            if($widthcss<>""){$cls[]="abt-w";}
        }
        if($btsize=="small" && !$wizard){$cls[]="abt-sm";}

        $attr="";
        if($locked){
            $cls[]="abt-l";
            $icon_class="fa-solid fa-lock";
            $tip=str_replace('"',"&quot;",$this->_ENGINE_parse_body("{this_form_locked_privileges}"));
            $attr=" aria-disabled=\"true\" data-tip=\"$tip\"";
        }elseif($nojs){
            $attr=" disabled";
        }else{
            $attr=" OnClick=\"$js\"";
        }
        $style=$widthcss<>""?" style='$widthcss'":"";

        $label=$this->_ENGINE_parse_body($btname);
        if($wizard){
            $inside="<span class=\"abt-wiz-ico\"><i class=\"$icon_class\"></i></span>".
                "<span class=\"abt-wiz-lbl\">$label</span>".
                "<span class=\"abt-wiz-go\"><i class=\"fa-solid fa-chevron-right\"></i></span>";
        }else{
            $inside="<i class=\"$icon_class\"></i><span>$label</span>";
        }
        $bt="<button type=\"button\" class=\"".@implode(" ",$cls)."\"$attr$style>$inside</button>";
        // Pentest 2026-11-04 : bouton du template, conserve par html_safe_fragment().
        html_trusted($bt);
        html_trusted($this->_ENGINE_parse_body($bt));
        html_trusted("$xlog$bt");
        return "$xlog$bt";
    }

    /**
     * Role d'un bouton de page : p (principal), s (neutre), x (destructif).
     * La classe Bootstrap demandee n'est pas fiable (btn-danger sur "Refresh" ou
     * "View", btn-warning sur "WIKI") : le sens du libelle et de l'icone passe avant.
     */
    private function abt_page_role($class,$icon,$label):string{
        if($this->abt_role((string)$icon,(string)$label)=="x"){return "x";}
        if(preg_match('#^\{(refresh|close|cancel|view|logfile|events|see_doc|online_help|howto|help|wiki)\}#i',trim((string)$label))){return "s";}
        if(preg_match('#^(wiki|help)$#i',trim((string)$label))){return "s";}
        if(preg_match('#fa-(headset|user-headset|circle-question|question-circle|life-ring|book)(\s|$)#',(string)$icon)){return "s";}
        $class=trim((string)$class);
        if($class=="btn-danger"){return "x";}
        if(preg_match('#^(btn|btn-default|btn-white|btn-info|btn-link|btn-outline)$#',$class)){return "s";}
        return "p";
    }

    public function javascript_senderror($text,$div):bool{
        $text=$this->_ENGINE_parse_body($this->div_error($text));
        $text=base64_encode($text);
        $f[]="if (document.getElementById('$div')){";
        $f[]="document.getElementById('$div').innerHTML=base64_decode('$text');";
        $f[]="}";
        echo @implode("",$f);
    return true;
    }


    public function ArrayNics($notun=false){

        $net=new networking();
        // Local_interfaces() returns [interface name => IP address]. Iterating
        // it with `as $eth` walks the VALUES, so every option carried an IP
        // where the consumers store an interface NAME: the current value
        // matched no option, the tun* filter never matched, and the operator
        // could not pick their NIC at all (assist.artica.center, 2026-09-08).
        $interfaces=$net->Local_interfaces();
        unset($interfaces["lo"]);

        $array=array();
        foreach ($interfaces as $eth => $ipaddr){
            if($notun){if(preg_match("#^tun[0-9]+#", $eth)){continue;}}
            $nic=new system_nic($eth);
            if($nic->enabled==0){continue;}
            $array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        }
        $array[null]="{none}/{default}";
        return $array;
    }


    public function is_firewall(){
        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
        $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
        if($users->SURICATA_INSTALLED){
            $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
        }

        if($EnableSuricata==1){ return true;}
        if($FireHolEnable==1){return true;}



        return false;


    }

    public function jsToTry($pattern):string{
        if($pattern==null) {return "";}
        $html=array();
        $jsss=array();
        if( strpos(" $pattern","if(") ) {
            $jsss[]=$pattern;

        }else{
            $jsss = explode(";", $pattern);
        }


            foreach ($jsss as $func) {
                $func=trim($func);
                if($func==null){continue;}
                if($func=="()"){continue;}
                $html[] = "//\t\tjsToTry";
                $html[] = "\t\ttry {";
                $html[] = "\t\t\t$func;";
                $html[] = "\t\t} catch (err) {";
                $html[] = "//\t\t\talert(err);";
                $html[] = "\t\t}";
                $html[] = "";
            }
        return @implode("\n",$html);
    }


    private function computers_rights():bool{

        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
        if($users->AsSystemAdministrator){return true;}
        if($users->ASDCHPAdmin){return true;}
        if($users->AsSambaAdministrator){return true;}
        if($users->AsDnsAdministrator){return true;}
        return false;
    }


    public function XHR_BUILD($page,$afterPost_function=null):string{
        $html[]="\tvar XHR = new XHRConnection();";
        $html[]="//\t".count($this->XHRHIDDEN)." hidden fields";

        foreach ($this->XHRHIDDEN as $XHRHIDDEN){
            $html[]="\t$XHRHIDDEN";
        }
        $html[]="//\t".count($this->XHR)." XHR fields";
        foreach ($this->XHR as $XHR){
            $html[]="\t$XHR";

        }
        if($afterPost_function<>null) {
            $html[] = "\tXHR.sendAndLoad('$page', 'POST',$afterPost_function);	";
            return @implode("\n",$html);
        }
        $html[] = "\tXHR.sendAndLoad('$page', 'POST');";
        return @implode("\n",$html);
    }


    /**
     * Coupe le contenu d'un form_outside() en cartes : chaque field_section() commence
     * par le marqueur <!--afrm-sec--> et ouvre une nouvelle carte (.afrm-card), qui a son
     * propre <table class='afrm-g'>. Les lignes d'avant la premiere section forment une
     * carte sans en-tete. Un morceau sans aucune ligne <tr> (champs caches seuls) est
     * rendu tel quel : il ne doit pas produire une carte vide.
     */
    private function afrm_cards($content):string{
        $content=(string)$content;
        $parts=explode("<!--afrm-sec-->",$content);
        $out=array();
        foreach($parts as $chunk){
            if(trim($chunk)==""){continue;}
            if(stripos($chunk,"<tr")===false){$out[]=$chunk;continue;}
            $out[]="<div class='afrm-card'><table class='afrm-g' style='width:100%'>$chunk</table></div>";
        }
        return @implode("\n",$out);
    }

    public function form_outside($title,$content,$explain=null,$btname=null,$jsafter=null,$security=null,$CheckLicense=false,$disabled_form=false,$iconclass=null){
        if($btname==null){$btname="{apply}";}
        if($btname=="remove-button"){$btname="";}
        // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
        if(!is_null($title)){$title=html_safe_fragment($title);}
        $help=null;
        $margin_top=null;
        $notitle="notitle:";$explainico="";$title_explain="";
        if(!is_null($explain)){
            $explain="";
        }

        if(!is_null($title) && !is_null($explain)) {
            if (strlen($explain) > 2 && strlen($title) > 2) {
                $notitle = $title."||";
                $title = "";
            }
        }

        if(is_array($explain)){
            $explain2=implode(" ",$explain);
            unset($explain);
            $explain=$explain2;
        }
        if(!is_null($explain)) {
            if (strpos($explain, "|") > 0) {
                $iconclass = "fa-solid fa-headset";
                $tt = explode("|", $explain);
                $explain = $tt[0];
                $help = $tt[1];
                $margin_top = "25";
                if(isset($tt[2])){
                    $explainico="ico:$tt[2];";
                    $explain =$tt[1];
                    $notitle=$tt[0]."||";
                    $margin_top = null;
                    $help="";
                    $iconclass="";
                }

            }

            if (preg_match("#^http.*wiki\.articatech\.com#", trim($explain))) {
                $help = $explain;
                $explain = null;
                $iconclass = "fa-solid fa-headset";
                $margin_top = "25";
            }
        }

        $page=CurrentPageName();
        if(is_array($content)){$content=@implode("\n", $content);}
        $LIMIT=false;
        $bts=array();
        $ERROR_PRIVS=null;
        $button=null;
        if($btname<>null){$btname=$this->_ENGINE_parse_body($btname);}
        $SaveIntro=null;
        $LOCKBTS=false;
        if($explain<>null){$explain=$this->div_explain("$notitle$explainico$explain");}
        $id=md5($content.serialize($this->XHRHIDDEN).serialize($this->XHR).time().rand(0,65535));
        if($GLOBALS["VERBOSE"]){VERBOSE("BTNAME DEFINED AS [$btname]", __LINE__);}
        if($GLOBALS["VERBOSE"]){VERBOSE("JSAFTER DEFINED AS [$jsafter]", __LINE__);}
        if($GLOBALS["VERBOSE"]){VERBOSE("SECURITY DEFINED AS [$security]", __LINE__);}
        if($GLOBALS["VERBOSE"]){VERBOSE("LICENSE DEFINED AS [$CheckLicense]", __LINE__);}
        if(!is_null($jsafter)) {
            $jsafter = str_replace(";!!;", ";", $jsafter);
        }

        if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
        $users=$GLOBALS["CLASS_USERS"];
        if($security=="license"){

            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
                if($GLOBALS["VERBOSE"]){VERBOSE("license FALSE in security", __LINE__);}
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_license}</div>");
                $SaveIntro="return;";
            }
        }
        if($security=="nolicense"){
            $LOCKBTS=true;
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_speciallicense}</div>");
            $SaveIntro="return;";
        }
        if($CheckLicense){

            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
                if($GLOBALS["VERBOSE"]){VERBOSE("license FALSE in CheckLicense", __LINE__);}
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_license}</div>");
                $SaveIntro="return;";
            }
        }
        if($disabled_form){
            $LOCKBTS=true;
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked}</div>");
            $SaveIntro="return;";
        }
        if($SaveIntro==null){
            if($security=="disabled"){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){VERBOSE("FALSE in security=\"disabled\"", __LINE__);}
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_disabled}</div>");
                $SaveIntro="return;";
            }}
        if($SaveIntro==null){if($security=="notinstalled"){
            $LOCKBTS=true;
            if($GLOBALS["VERBOSE"]){VERBOSE("license FALSE in security=notinstalled", __LINE__);}
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_not_installed}</div>");
            $SaveIntro="return;";
        }}
        if($SaveIntro==null){if($security=="dhcpfixed"){
            $LOCKBTS=true;
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_not_dhcpfixed}</div>");
            $SaveIntro="return;";
        }}


        if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: $security</span><br>\n";}

        if($SaveIntro==null){
            if($security=="computer"){
                if(!$this->computers_rights()){
                    $LOCKBTS=true;
                    if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: computer -> False</span><br>\n";}
                    $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                    $SaveIntro="return;";
                }
            }}
        if($SaveIntro==null){if($security=="AsSquidAdministrator"){

            if(!$users->AsSquidAdministrator){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AsSquidAdministrator -> False</span><br>\n";}
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsDnsAdministrator"){

            if(!$users->AsDnsAdministrator){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AsDnsAdministrator -> False</span><br>\n";}
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsDatabaseAdministrator"){

            if(!$users->AsDatabaseAdministrator){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AsDatabaseAdministrator -> False</span><br>\n";}
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsFirewallManager"){

            if(!$users->AsFirewallManager){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AsFirewallManager -> False</span><br>\n";}
                $ERROR_PRIVS="<div class='alert alert-danger'>{this_form_locked_privileges}</div>";
                $SaveIntro="return;";
            }

        }	}
        if($SaveIntro==null){if($security=="AllowAddUsers"){
            if(!$users->AllowAddUsers){
                $LOCKBTS=true;
                if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AllowAddUsers -> False</span><br>\n";}
                $ERROR_PRIVS="<div class='alert alert-danger'>{this_form_locked_privileges}</div>";
                $SaveIntro="return;";
            }
        }}
        if($SaveIntro==null){
            if($security=="AllowAddGroup"){
                if(!$users->AllowAddGroup){
                    $LOCKBTS=true;
                    if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Security: AllowAddGroup -> False</span><br>\n";}
                    $ERROR_PRIVS="<div class='alert alert-danger'>{this_form_locked_privileges}</div>";
                    $SaveIntro="return;";
                }

            }
        }
        if($SaveIntro==null){if($security=="AsProxyMonitor"){
            if(!$users->AsProxyMonitor){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsDnsAdministrator"){
            if(!$users->AsDnsAdministrator){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsPostfixAdministrator"){
            if(!$users->AsPostfixAdministrator){
                if($GLOBALS["VERBOSE"]){VERBOSE("FALSE in AsPostfixAdministrator=NONE", __LINE__);}
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsSystemAdministrator"){
            if(!$users->AsSystemAdministrator){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsWebStatisticsAdministrator"){
            if(!$users->AsWebStatisticsAdministrator){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($SaveIntro==null){if($security=="AsDansGuardianAdministrator"){
            if(!$users->AsDansGuardianAdministrator){
                $LOCKBTS=true;
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-danger'>{this_form_locked_privileges}</div>");
                $SaveIntro="return;";
            }

        }}
        if($this->FORM_LOCKED){
            $LOCKBTS=true;
            $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-warning'>{$this->this_form_locked_explain}</div>");
            $SaveIntro="return;";
        }
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $ERROR_PRIVS=$this->_ENGINE_parse_body("<div class='alert alert-warning'>{formlock_clutser_slave}</div>");
                $LOCKBTS=true;
                $SaveIntro="return;";
            }
        }
        if ($this->KEEPALIVED_CLI) {
            $ERROR_PRIVS = $this->_ENGINE_parse_body("<div class='alert alert-warning'>{formlock_clutser_slave}</div>");
            $LOCKBTS = true;
            $SaveIntro = "return;";
        }
        if(!$LOCKBTS){
            if($btname<>null){$button=$this->form_button($btname,"Save$id()");}
        }else{
            if($btname<>null){$button=$this->form_button_disabled($btname);}
        }
        if(preg_match("#Remove:Button#",$btname)){
            $button=null;
        }
        if($btname=="NO"){$button=null;}

        $ccl[0]="btn-warning";
        $ccl[1]="btn-info";
        $ccl[2]="btn-success";
        $title_buttons_final    = null;
        $title_buttons          = array();

        if(count($this->buttons)>0){
            $c=0;
            foreach ($this->buttons as $btname2=>$js){

                if(!$LOCKBTS){
                    if($c>2){$c=0;}
                    VERBOSE("GENERATE BUTTON -$btname2- -> $js",__LINE__);
                    $bts[]=$this->form_button($btname2,$js,$this->abt_rot($c+1));
                    $c++;
                }else{
                    $bts[]=$this->form_button_disabled($btname2);
                }

            }


            if(!$LOCKBTS){
                VERBOSE("GENERATE BUTTON -$btname- -> Save$id()",__LINE__);
                if($btname<>null){$bts[]=$this->form_button($btname,"Save$id()");}
            }elseif($btname<>null){
                $bts[]=$this->form_button_disabled($btname);
            }
            $button=@implode("\n", $bts);
        }
        if(is_null($this->form_id)){
            $this->form_id="FRM".time();
            if(!is_null($content)){
                $this->form_id="FRM_".md5($content);
            }
        }
        if($iconclass<>null){
            if($margin_top==null){$margin_top="-21";}
            if(preg_match("#^margin:(.+?):(.+)#",$iconclass,$re)){
                $iconclass=$re[2];
                $margin_top=$re[1];

            }
            $html[]="<table style='width:100%'>";
            $html[]="<tbody><tr>";
            $html[]="<td style='padding-right: 10px;vertical-align:top;width:1%'  nowrap>";
            $html[]="<div style='margin-top:{$margin_top}px'>";
            $html[]="<i class='fa-8x $iconclass'></i>";

            $html[]="</div>";

            if($help<>null){
                $html[]="<div style='margin-top: 15px'>";
                $jshelp="s_PopUpFull('".req_js($help)."',1024,768,'".req_js(strip_tags((string)$title))."')"; // Pentest 2026-11-04 : titre dans un OnClick (XSS).
                $html[]=$this->button_inline("{online_help}",$jshelp,ico_support,null,0,"btn-default");
                $html[]="</div>";


            }
            $html[]="</td>";
            $html[] = "<td style='vertical-align:top;border-left: 3px solid;border-left-color: #F4F4F4; padding-left:10px;'><div style='margin-top:0'>";

        }

        $html[]="<div id='inside-form-alert'>$this->init_error</div>";
        $html[]="<div class=\"row afrm-wrap\" style='width:100%;margin-left:10px'>";

        if(count($this->button_title)>0){
            $c=0;
            foreach ($this->button_title as $name=>$js){
                $title_buttons[]=$this->button_inline($name,$js,"",null,0,$this->abt_rot($c),"small");
                $c++;

            }

            $title_buttons_final=@implode("",$title_buttons);
            $title_buttons_final="<div class='abt-foot' style='float:right;width:50%'>$title_buttons_final</div>";
        }

        if($title<>null && is_null($explain)){
            $title= "<div class=ibox-title style='border-top:0;text-align:left'><H3>$title_buttons_final$title</H3></div>";
        }else{
            if($title_buttons_final<>null) {
                $title = "<div class=ibox-title style='border-top:0;text-align:left'>$title_buttons_final</div>";
            }
        }



        $background="none";
        $border="#EAEAEA";
        $shadow="#EAEAEA";
        $inset="inset -1px -1px 0px";
        $class_form="margin-top:10px !important;border-radius: 4px;-webkit-border-radius: 4px;-moz-border-radius: 10px;margin: 0px 0px 10px 0px;border: 1px solid $border;background:$background;box-shadow: $inset $shadow;-moz-box-shadow: $inset $shadow; -webkit-box-shadow: $inset $shadow;padding-bottom:0;border-top:0px;border-left:0px";
        // Maquette stitch_artica_dhcp_server_params (2026-11-11) : plus de cadre "inset",
        // une carte par field_section() (afrm_cards) et le pied hors du tableau. Le CSS
        // est global (afrm_css() dans angular.css.php), jamais en ligne ici.
        $class_form="padding-bottom:0";
        $cards=$this->afrm_cards($content);
        $html[]="<div class=\"col-lg-12 afrm\" style='$class_form'>";
        $html[]="<div class='ibox float-e-margins'>$title";
        $html[]="$ERROR_PRIVS	
						<div id='form-disabled-$this->form_id'></div>				
						$explain
						<form role='form' id='$this->form_id' name='$this->form_id'>
							$cards
							<div class='abt-foot afrm-foot' style='width:100%'>$button</div>
						</form>
					</div>
				</div>
			</div>";

       // if(!$this->formwizard){$html[]="</div>";}
        if($iconclass<>null){$html[]="</td></tr></table>";}

        if($this->FORM_IN_ARRAY){
            $html=array();
            $html[]="<td><span id='inside-form-alert'>$this->init_error</span>".$this->form_button($btname,"Save$id()");
        }
        $before_save=null;
        $reconfigure_service_forget=$this->javascript_parse_text("{reconfigure_service_forget}");

        if($this->form_privileges<>null){
            if($this->form_privileges=="AsSquidAdministrator"){
                if(!$users->AsSquidAdministrator){$LIMIT=true;}
            }

        }
        if($LIMIT){
            $ERROR_NO_PRIVS2=$this->javascript_parse_text("{ERROR_NO_PRIVS2}");
            $before_save="alert('$ERROR_NO_PRIVS2');return;";
        }

        $success_form_submited=$this->javascript_parse_text("{success_form_submited}");
        $html[]="
<script>
// JS HEAD == ". count($this->JS)." lines";
        $html[]="// JS Funcs == ". count($this->JS_FUNCs)." lines";
        $html[]="function FieldHTMLError(id){";
        $html[]="\tNoSpinner();";
        $html[]="\t$('#'+id).focus();";
        $html[]="\t$('#'+id).css('border-color', '#ED5565');";
        $html[]="\t$('#'+id).css('color', '#ED5565');";
        $html[]="\t$('#'+id).css('font-weight', 'bold');";
        $html[]="}\n\n";
        $html[]="var emailIsValid =  function(value){";
        $html[]="\tvar regex = new RegExp (\"^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,3}$\");";
        $html[]="\treturn regex.test(value);";
        $html[]="\t}\n\n";
        $html[]="// JS();";
        $html[]="// JS_FUNCs();";
        $html[]=@implode("\n", $this->JS);
        $html[]=@implode("\n",$this->JS_FUNCs);
        $html[]="var xSave$id= function (obj) {";
        $html[]="\t\$('body').Wload('hide',{time:800});";
        $html[]="\t$SaveIntro";
        $html[]="\t$before_save\n";


        reset($this->DISABLE_ALL);
        foreach ($this->DISABLE_ALL as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            $html[]="\t\t$('#$ids').css('border-color', '#E5E6E7');";
            $html[]="\t\t$('#$ids').css('color', 'inherit');";
            $html[]="\t\t$('#$ids').css('font-weight', 'normal');";
            $html[]="\t\t}";
        }


        $html[]="\tvar res=obj.responseText;";
        $html[]="\tif (res.length>3){";
        $html[]="\tvar regex = new RegExp('jserror:(.+)');";
        $html[]="\t		if( regex.exec(res) ){";
        $html[]="\tvar sText=RegExp.$1;";
        $html[]="\tcr = String.raw`\\n`;";
        $html[]="\tsText=sText.replace(/[\\r\\n|\\r|\\n|#]/g, '<br>');";
        // The literal two characters backslash-n (javascript_parse_text's line
        // breaks). A /cr/g regex here replaced the LETTERS "cr" ("secret" -> "se<br>et").
        $html[]="\tsText=sText.split(cr).join('<br>');";
        $html[]="\t//		    sText=sText.replace('#', '<br>');";
        $html[]="\tswal( {title:'Oops...', text:'<strong>'+sText+'</strong>', html: true,type:'error'});";
        $html[]="\t//LoadAjax('artica-modal-dialog','fw.popup.php?title='+encodeURIComponent('{post_error}')+'&subtitle='+encodeURIComponent(RegExp.$1))";
        $html[]="\treturn;";
        $html[]="\t}";
        $html[]="\tNoSpinner();";
        $html[]="\tdocument.getElementById('inside-form-alert').style.marginTop='6px';";
        $html[]="\tdocument.getElementById('inside-form-alert').className='alert alert-warning alert-dismissable';";
        $html[]="\tdocument.getElementById('inside-form-alert').innerHTML='<strong style=font-size:18px>'+res+'</strong>';";
        $html[]="\treturn;";
        $html[]="\t}";

        $html[]="\tif(document.getElementById('reconfigure-service-div') ){";
        $html[]="\tdocument.getElementById('reconfigure-service-div').style.marginTop='6px';";
        $html[]="\tdocument.getElementById('reconfigure-service-div').className='alert alert-warning alert-dismissable';";
    $html[]="\tdocument.getElementById('reconfigure-service-div').innerHTML='$reconfigure_service_forget';";
    $html[]="\t}";
    $html[]="\t\ttoastr.options.timeOut = 2000;";
    $html[]="\t\ttoastr.options.progressBar = true;";
    $html[]="\t\ttoastr.success('$success_form_submited');";
	$html[]="\t\tLoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
	$html[]="\t\tNoSpinner();";
    $html[]=$this->jsToTry($jsafter);
    $html[] = "\t}";
    $html[]="\tfunction Save$id(){";
	$html[]="\t$('body').Wload();";
	$html[]="\tdocument.getElementById('inside-form-alert').className='';";
	$html[]="\tdocument.getElementById('inside-form-alert').innerHTML='';";
	$html[]="\tvar XHR = new XHRConnection();";
    $html[]="//\t".count($this->XHRHIDDEN)." hidden fields";
    foreach ($this->XHRHIDDEN as $XHRHIDDEN){
            $html[]="\t$XHRHIDDEN";
    }
        $html[]="//\t".count($this->XHR)." XHR fields";
        foreach ($this->XHR as $XHR){
            $html[]="\t$XHR";

        }

    $html[]="\tXHR.sendAndLoad('$page', 'POST',xSave$id);	";
    $html[]="}";
    $html[]="function CheckFormCheckBox(vid){";
    // Seul acces DOM non garde de tout ce bloc genere -- tous les autres testent
    // l'existence avant d'ecrire. Or le formulaire est initialise par le <script>
    // du fragment, et un LoadAjax* concurrent peut avoir remplace le conteneur
    // entre-temps: la case n'existe alors plus, ".checked" leve une TypeError, et
    // l'exception avorte TOUT le reste du bloc -- le formulaire reste a moitie
    // initialise (vu sur fw.dhcp.configuration.php, bascule serveur/relai DHCP).
    $html[]="\tvar cbx=document.getElementById(vid);";
    $html[]="\tif(!cbx){return;}";
    $html[]="\tif(cbx.checked){";
    $html[]="\t\tSwitchFormInsteadOf(vid,0);";
    $html[]="\t\treturn;";
    $html[]="\t}";
    $html[]="\tSwitchFormInsteadOf(vid,1);";
    $html[]="}";
    $html[]="function SwitchFormInsteadOf(vid,direction){";
    $html[]="\tif(direction===1){";
        foreach ($this->DISABLE_ALL as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            $html[]="\t\t\tdocument.getElementById('$ids').disabled=true;";
            $html[]="\t\t}";

        }
        foreach ($this->LABELS as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            // classList : ecraser className effacait aussi afrm-lbl (style du libelle).
            $html[]="\t\t\tdocument.getElementById('$ids').classList.remove('labelform');document.getElementById('$ids').classList.add('labelform_disabled');";
            $html[]="\t\t}";
        }
        foreach ($this->CHOSENS as $ids) {
            $html[] = "$('#$ids').prop('disabled',true).trigger('chosen:updated').chosen('destroy').chosen()";
        }
        $form_disabled=$this->javascript_parse_text("{feature_disabled}");
        if(!$LOCKBTS){
            $html[]="\tdocument.getElementById('form-disabled-$this->form_id').className='alert alert-warning alert-dismissable';";
            $html[]="\tdocument.getElementById('form-disabled-$this->form_id').innerHTML='$form_disabled';";
            $html[]="\t\tif(vid.length>0){";
            $html[]="\t\t\tif(document.getElementById(vid)){";
            $html[]="\t\t\t\tdocument.getElementById(vid).disabled=false;";
            $html[]="\t\t\t\t}";
            $html[]="\t\t\t}";
        }
        $html[]="\t}";
        $html[]="\tif(direction===0){";
        reset($this->DISABLE_ALL);
        foreach ($this->DISABLE_ALL as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            $html[]="\t\t\tdocument.getElementById('$ids').disabled=false;";
            $html[]="\t\t}";
        }
        foreach ($this->LABELS as $ids){
            $html[]="\t\tif( document.getElementById('$ids') ){";
            $html[]="\t\t\tdocument.getElementById('$ids').classList.remove('labelform_disabled');document.getElementById('$ids').classList.add('labelform');";
            $html[]="\t\t}";
        }
        foreach ($this->CHOSENS as $ids) {
            $html[] = "$('#$ids').prop('disabled',false).trigger('chosen:updated').chosen('destroy').chosen()";
        }
        $html[]="\tif( document.getElementById('form-disabled-$this->form_id') ){";
        $html[]="\t\tdocument.getElementById('form-disabled-$this->form_id').style.marginTop='0px';";
        $html[]="\t\tdocument.getElementById('form-disabled-$this->form_id').className='';";
        $html[]="\t\tdocument.getElementById('form-disabled-$this->form_id').innerHTML='';";
        $html[]="\t\t}";
        $html[]="\t}";
        $html[]="}";
        $html[]="$(document).ready(function(){";
        $html[]="\t$('[data-toggle=\"popover\"]').popover({ trigger: 'hover', html: true, });";
        $html[]="});";
        $html[]="// -->  FormStartupScripts";
        $html[]=@implode("\n", $this->FormStartupScripts);
        $html[]="function FormCheckError(){";
        $html[]="\tNoSpinner();";
        $html[]="\tvar zalert=document.getElementById('inside-form-alert').innerHTML;";
        $html[]="\tif (zalert.length>3){";
        $html[]="\t\tdocument.getElementById('inside-form-alert').style.marginTop='6px';";
        $html[]="\t\tdocument.getElementById('inside-form-alert').className='alert alert-warning alert-dismissable';";
        $html[]="\t\tdocument.getElementById('inside-form-alert').innerHTML='<strong style=font-size:18px>'+zalert+'</strong>';";
        $html[]="\treturn;";
        $html[]="\t}";
        $html[]="}";
        $html[]="function Save$this->form_id(){";
        $html[]="\tSave$id();";
        $html[]="}";
        $html[]="FormCheckError();";
        if($LOCKBTS){
            $t=time();
            $html[]="function LockForm{$t}(){";
            $html[]="$"."('#{$this->form_id}').find('input').filter(";
            $html[]="\tfunction(){";
            $html[]="\t\tdocument.getElementById( $(this).attr('id')).disabled=true;";
            $html[]="\t});";
            $html[]="SwitchFormInsteadOf('',1);";
            $html[]="}";
            $html[]="LockForm{$t}();";

        }
        $html[]="\tvar postFormFields$id = document.querySelectorAll('.PostForm');";
        $html[]="\tpostFormFields$id.forEach(function(field) {";
        $html[]="\t\tfield.addEventListener('keydown', function(event) {";
        $html[]="\t\t\tif (event.keyCode === 13) {";
        $html[]="\t\t\t\tSave$id();";
        $html[]="\t\t\t\tif (event.target.tagName === 'INPUT') {  event.preventDefault(); }";
        $html[]="\t\t\t}";
        $html[]="\t\t});";
        $html[]="\t});";
        $html[]="</script>";

        if($this->FORM_IN_ARRAY){
            $html[]="</td>";
            $content=$this->_ENGINE_parse_body(@implode("\n", $html));
            return $content;
        }
        $html[]="";
        $content=$this->_ENGINE_parse_body(@implode("\n", $html));
        return $content;
    }



    public function field_hidden($name,$value){
        // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
        // Le remplacement de ' par \' ne protegeait que la chaine JavaScript :
        // dans l'attribut value='' l'antislash n'echappe rien.
        $valuejs   = req_js($value);
        $valuehtml = req_html($value);
        $this->XHRHIDDEN[]="XHR.appendData('$name',  encodeURIComponent('$valuejs'));";
        return "<input type='hidden' name='$name' id='$name' value='$valuehtml'>";
    }

    public function field_div_section($name,$ajax){
        $id=md5("$name$this->suffixid".time());
        $ReloadFunction="LoadAjax('$id','$ajax')";
        $ReloadFunctionEnc=base64_encode($ReloadFunction);
        $this->JS[]="LoadAjax('$id','$ajax&reload-function=$ReloadFunctionEnc');";
        return $this->_field_intr("TABLE!","\n<!-- DIV SECTION START -->\n<div id='$id' style='width:100%'></div>\n<!-- DIV SECTIOn END -->\n",null);

    }



    public function field_textareacode($name,$label,$value,$explain=null):string{
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $this->XHR[]="editor{$id}.save();";
        $this->DISABLE_ALL[]=$id;
        $width=null;
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(editor{$id}.getValue()));";
        $this->JS[]="var editor{$id};";
        $this->JS[]="function CodeMirror$id(){";
        $this->JS[]="editor{$id} = CodeMirror.fromTextArea(document.getElementById('$id'), { lineNumbers: true, matchBrackets: true, lineWrapping: true,autofocus:true });";
        $this->JS[]="}";
        $this->JS[]="setTimeout ('CodeMirror$id()',800);";

        if($label==null){$width=";width:100%";}
        return $this->_field_intr($label,
            "<textarea id='$id' name='$id' style='min-height:50px;$width' rows='5'>".req_html($value)."</textarea>",$explain);
    }
    public function field_textarea_normal($name,$label,$value=null,$width="100%",$height="450px",$explain=""):string{
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;

        if(strlen($explain)>1){
            $explain="<div style='font-size: 11px;margin-top: 5px;color: #a0a0a0;'>$explain</div>";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr2($label,"<textarea id='$id' name='$id' style='width:$width;min-height: $height;font-size: 12px;' class='form-control'>".req_html($value)."</textarea>$explain");
    }
    public function field_textarea($name,$label,$value=null,$width="100%",$height="450px"):string{
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        $ThisFieldHidden=$this->ThisFieldHidden($id,$value,$height,$width);
        if(preg_match("#^TextareaCert:(.+)#",$label,$re)){
            $height=406;

            if(strlen($value)>1500){
                $height=550;
            }

            if(strlen($value)>1600){
                $height=600;
            }

            if(strlen($value)>2000){
                $height=780;
            }
            return $this->_field_intr2("",
                "<textarea id='$id' name='$id' style='width: 98%;min-height: {$height}px;font-size: 15px;'>".req_html($value)."</textarea>");
        }



        return $this->_field_intr2($label,
            "<textarea id='$id' name='$id' 
        style='width:$width;height:$height;overflow:auto;visibility:collapse;max-width:640px' 
        class='InForms'>".req_html($value)."</textarea>$ThisFieldHidden");
    }

    // textareaPreview: the first 75 characters of a long value, escaped once,
    // one line per row. It used to escape each line, join them with <br>, cut
    // the result (possibly inside "<br>" or an entity) and escape it all again:
    // the preview showed "&lt;br&gt;" literally. Short values are unchanged.
    private static function textareaPreview(string $value):string{
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        if(strlen($value)<=75){return req_html($value);}
        $cut=function_exists("mb_substr")?mb_substr($value,0,75,"UTF-8"):substr($value,0,75);
        $rows=array();
        foreach(explode("\n",str_replace("\r","",$cut)) as $line){$rows[]=req_html($line);}
        return implode("<br>",$rows);
    }

    private function ThisFieldHidden($id,$value,$height=null,$width=null):string{
        $ClickMouse="onMouseOver=\"this.style.cursor='pointer';this.style.fontWeight='bold';this.style.textDecoration='underline';\" 
        OnMouseOut=\"this.style.cursor='default';this.style.fontWeight='normal';this.style.textDecoration='none';\"";
        $value=self::textareaPreview((string)$value);
        if($value==null){$value="{no_data}: {click_here}";}
        $htm[]="<div id='associates-$id' class='OutForms' $ClickMouse>$value</div>";
        $htm[]="<script>";
        $htm[]="$('#$id').css('visibility', 'collapse');";
        $htm[] = "$('#$id').css('height', '0');";
        $htm[]="$('#$id').css('width', '0');";
        $htm[]="$('#associates-$id').css('margin-top', '-6px');";
        $htm[]="$('#associates-$id').click(function() {";

         $htm[]="$( '.InForms' ).each(function() {";
         $htm[]="id='associates-'+this.id;";
         $htm[]="value=$('#'+this.id).val();";
         $htm[]="value=value.substring(0,75);";
         $htm[]="$('#'+id).text(value).html();";
         $htm[]="});";
        
        
        $htm[]="$('.OutForms').css('visibility', 'visible');";
        $htm[]="$('.InForms').css('height', '0');";
        $htm[]="$('.InForms').css('width', '0');";
        $htm[]="$('.InForms').css('visibility', 'collapse');";
        $htm[]="$('#associates-$id').css('visibility', 'collapse');";
        $htm[]="$('#$id').css('visibility', 'visible');";
        if($height<>null) {
            $htm[] = "$('#$id').css('height', '$height');";
        }
        if($width<>null) {
            $htm[]="$('#$id').css('width', '$width');";
        }
        $htm[]="} );";
        $htm[]="</script>";
        return @implode(" ",$htm);
    }


    public function field_textareaP($name,$label,$value){
        return $this->field_textarea($name,$label,$value,"100%","125px");

    }

    public function field_info_ERROR($name,$label,$value,$explain=null,$id=null){
        if($id==null){$id=md5("$name$this->suffixid");}
        $button=null;
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        // Les appelants passent frequemment une valeur ABSENTE (attribut LDAP non
        // renseigne, colonne SQL NULL): sous PHP 8 str_replace() sur null est
        // deprecie et les cles BUTTON* manquantes declenchent un avertissement.
        // La valeur est donc normalisee en chaine avant tout traitement.
        $bt_js=null;
        if(is_array($value)){
            $mvalue=isset($value["VALUE"])?$value["VALUE"]:null;
            if(isset($value["BUTTON"]) && $value["BUTTON"]==true){
                if(isset($value["BUTTON_JS"]) && $value["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$value["BUTTON_JS"]}\"";}
                $caption=isset($value["BUTTON_CAPTION"])?$value["BUTTON_CAPTION"]:null;
                $button="&nbsp;&nbsp;&nbsp;<span class=\"label label-primary\" $pointers $bt_js>$caption...</span></div>";

            }

        }else{
            $mvalue=$value;
        }

        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $mvaluejs=req_js((string)$mvalue);
        $mvalue=html_safe_fragment((string)$mvalue);
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent('$mvaluejs'));";
        return $this->_field_intr($label,"<label id='$id' class='text-danger'>$mvalue</label>$button",$explain);
    }


    public function field_info($name,$label,$value,$explain=null,$id=null){
        if($id==null){$id=md5("$name$this->suffixid");}
        $button=null;
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
        // = array("VALUE"=>$value,"BUTTON_JS"=>javascript,"BUTTON_CAPTION"=>button name,"BUTTON"=>true)
        //
        // Les appelants passent frequemment une valeur ABSENTE (attribut LDAP non
        // renseigne, colonne SQL NULL): sous PHP 8 str_replace() sur null est
        // deprecie et les cles BUTTON* manquantes declenchent un avertissement.
        // La valeur est donc normalisee en chaine avant tout traitement.
        $bt_js=null;
        if(is_array($value)){
            $mvalue=isset($value["VALUE"])?$value["VALUE"]:null;
            if(isset($value["BUTTON"]) && $value["BUTTON"]==true){
                if(isset($value["BUTTON_JS"]) && $value["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$value["BUTTON_JS"]}\"";}
                $caption=isset($value["BUTTON_CAPTION"])?$value["BUTTON_CAPTION"]:null;
                $button="&nbsp;&nbsp;&nbsp;<span class=\"label label-primary\" $pointers $bt_js>$caption...</span></div>";

            }

        }else{
            $mvalue=$value;
        }

        // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
        // La valeur part a la fois dans une chaine JavaScript et dans le HTML.
        $mvaluejs=req_js((string)$mvalue);
        $mvalue=html_safe_fragment((string)$mvalue);
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent('$mvaluejs'));";
        return $this->_field_intr($label,"<label id='$id'>$mvalue</label>$button",$explain);
    }


    public function field_interfaces_choose($name,$label,$value,$explain=null,$notnull=false){
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-interfaces.php?field-id=$id&title=$labeljs')";

        if(preg_match("#^vs:(.+)#",$label,$re)){
            $label=$re[1];
        }

        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{select}...</button> </span>
		</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){

            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);
    }

    public function TheShieldsLicenseStatus():string
    {
        $Val = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos");
        $Val = base64_decode($Val);
        $kInfos = unserialize($Val);
        if (!isset($kInfos["enable"])) {
            $kInfos["enable"] = 0;
        }
        if (!isset($kInfos["status"])) {
            $kInfos["status"] = null;
        }


        if ($kInfos["enable"] == 0 && $kInfos["status"] == null) {
            $btn[0]["name"] = "{activate}";
            $btn[0]["icon"] = "far fa-shield-check";
            $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
            return $this->widget_grey("{license_status}", "{license_invalid}", $btn);
        }
        if ($kInfos["enable"] == 0 && $kInfos["NoLic"] == 1) {
            $btn[0]["name"] = "{activate}";
            $btn[0]["icon"] = "far fa-shield-check";
            $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
            return $this->widget_grey("{license_status}", "{no_license}", $btn);
        }


        if ($kInfos["enable"] == 0) {
            if (intval($kInfos["expire"]) > 0) {
                $reste_days = $this->TimeToDays($kInfos["expire"]);
                if ($reste_days < 1) {
                    $btn[0]["name"] = "{license}";
                    $btn[0]["icon"] = "far fa-shield-check";
                    $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
                    return $this->widget_rouge("{license_status}", "{expired}", $btn);
                }
            }

            $btn[0]["name"] = "{activate}";
            $btn[0]["icon"] = "far fa-shield-check";
            $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
            return $this->widget_grey("{license_status}", "{license_invalid}", $btn);
        }

        if (intval($kInfos["expire"]) > 0) {
            $reste_days = $this->TimeToDays($kInfos["expire"]);

            if ($reste_days < 1) {
                $btn[0]["name"] = "{license}";
                $btn[0]["icon"] = "far fa-shield-check";
                $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
                return $this->widget_rouge("{license_status}", "{expired}", $btn);
            }

            if ($reste_days < 15) {
                $btn[0]["name"] = "{license}";
                $btn[0]["icon"] = "far fa-shield-check";
                $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
                return $this->widget_jaune("{license_status}", "{ExpiresSoon}: $reste_days {days}", $btn);
            }

            if($kInfos["ispaid"]==0) {
                $btn[0]["name"] = "{license}";
                $btn[0]["icon"] = "far fa-shield-check";
                $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
                return $this->widget_jaune("{license_status}", "{active2}/{not_paid_license}", $btn);
            }
        }


        $btn[0]["name"] = "{license}";
        $btn[0]["icon"] = "far fa-shield-check";
        $btn[0]["js"] = "Loadjs('fw.ksrn.license.php?js=yes');";
        return $this->widget_vert("{license_status}", "{active2}", $btn);
    }

    public function NGINX_ID_TO_HOSTNAME($ID=0):string{
        if($ID==0){return "{all_websites}";}
        if(isset($GLOBALS["NGINX_ID_TO_HOSTNAME"][$ID])){
            return $GLOBALS["NGINX_ID_TO_HOSTNAME"][$ID];
        }
        $sock=new socksngix($ID);
        $GLOBALS["NGINX_ID_TO_HOSTNAME"][$ID]=$sock->GetServiceName();
        return $GLOBALS["NGINX_ID_TO_HOSTNAME"][$ID];
    }

    public function NGINX_HOSTNAME_TO_ID($strsearch=null):int{
        $strsearch=trim(strtolower($strsearch));
        if(isset($GLOBALS["NGINX_HOSTNAME_TO_ID"][$strsearch])){return $GLOBALS["NGINX_HOSTNAME_TO_ID"][$strsearch];}
        if($strsearch==null){return 0;}
        $this->_loadNginxHostMap();
        if(isset($GLOBALS["NGINX_HOSTNAME_TO_ID"][$strsearch])){return $GLOBALS["NGINX_HOSTNAME_TO_ID"][$strsearch];}
        return 0;
    }

    private function _loadNginxHostMap(){
        if(isset($GLOBALS["_nginxHostMapLoaded"])){return;}
        $GLOBALS["_nginxHostMapLoaded"]=true;
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/list"),true);
        if(!is_array($json) || !isset($json["sites"])){return;}
        foreach($json["sites"] as $ligne){
            $hosts=isset($ligne["Hosts"]) ? $ligne["Hosts"] : "";
            $ID=intval($ligne["ID"]);
            if(strlen($hosts)==0){continue;}
            if(strpos($hosts,"||")>0){
                $Zhosts=explode("||",$hosts);
                foreach($Zhosts as $www){
                    $www=trim(strtolower($www));
                    if(preg_match("#^(.+?)>(.+)#",$www,$re)){$www=$re[1];}
                    if(strlen($www)==0){continue;}
                    $GLOBALS["NGINX_HOSTNAME_TO_ID"][$www]=$ID;
                    $GLOBALS["nginxHostToInt"][$www]=$ID;
                }
                continue;
            }
            $www=trim(strtolower($hosts));
            $GLOBALS["NGINX_HOSTNAME_TO_ID"][$www]=$ID;
            $GLOBALS["nginxHostToInt"][$www]=$ID;
        }
    }

    public function field_choose_websites($name,$label,$value=null,$explain=null,$notnull=false){
        $id=md5("$name$this->suffixid");
        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-websites.php?field-id=$id&title=$labeljs')";
        if(!is_numeric($value)) { $value=$this->NGINX_HOSTNAME_TO_ID($value);}
        $string_value=$this->NGINX_ID_TO_HOSTNAME($value);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $F="
        <input type='hidden' id='$id' value='".req_html($value)."'>
        <div class='input-group'><input type='text' class='form-control' id='$id-string' value='".req_html($string_value)."' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{select}...</button> </span>
		</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id-string'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);
    }

    public function field_choose_category($name,$label,$value=null,$explain=null,$notnull=false){
        $id=md5("$name$this->suffixid");
        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-categories.php?field-id=$id&title=$labeljs')";
        if(!is_numeric($value)) { $value=0;$string_value="{none}";}
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).

        $F="
        <input type='hidden' id='$id' value='".req_html($value)."'>
        <div class='input-group'><input type='text' class='form-control' id='$id-string' value='".req_html($string_value??"")."' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{select}...</button> </span>
		</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id-string'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);
    }
    public function nginxHostToInt($sitename){
        $sitename=trim(strtolower($sitename));
        $this->_loadNginxHostMap();
        if(isset($GLOBALS["nginxHostToInt"][$sitename])){return intval($GLOBALS["nginxHostToInt"][$sitename]);}
        return 0;
    }



    public function field_browse_UserAgent($name,$label,$value,$ModeRegex=0,$notnull=false){
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");

        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-usersagents.php?field-id=$id&title=$labeljs&ModeRegex=$ModeRegex')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."'>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }
    public function field_browse_waf_scope($name,$label,$value,$explain=null,$notnull=false){
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-waf-scope.php?field-id=$id&title=$labeljs')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";



        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);
    }

    public function field_browse_smtp_header($name,$label,$value,$explain=null,$notnull=false){
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $labeljs=urlencode($this->javascript_parse_text($label));
        $js="Loadjs('fw.browse-smtpheaders.php?field-id=$id&title=$labeljs')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."'>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";



        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }

    /**
     * $rootpath est le repertoire de depart du selecteur (null = "/"), $notnull rend
     * le champ obligatoire, $explain est le texte d'aide sous le champ. Trois appelants
     * passaient un booleen ou un {token} en 4e position : le selecteur ouvrait alors
     * /system/tree/1 (ou /{token}) et affichait un arbre VIDE sans aucune erreur.
     */
    public function field_browse_directory($name,$label,$value,$rootpath=null,$notnull=false,$explain=null):string{
        if($rootpath===true || $rootpath===false || $rootpath===1 || $rootpath===0 || $rootpath==="1" || $rootpath==="0"){$rootpath=null;}
        if(is_string($rootpath) && preg_match("/^\{[a-zA-Z0-9_.-]+\}$/",trim($rootpath))){$explain=$rootpath;$rootpath=null;}
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");


        $labeljs=urlencode($this->javascript_parse_text($label));
        if(!is_null($rootpath)) {
            $rootpath = urlencode($rootpath);
        }
        $js="Loadjs('fw.browse-directory2.php?field-id=$id&title=$labeljs&basepath=$rootpath')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";



        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        if($notnull){

            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F,$explain);

    }

    public function field_browse_suffix($name,$label,$value,$explain=null,$idOfServer=null,$idOfPort=null){
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $js="Loadjs('fw.browse-ldap-suffix.php?field-id=$id&idOfServer=$idOfServer&idOfPort=$idOfPort')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."'>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }

    public function field_array_select($HashList,$name,$label,$value,$explain=null,$jsafter=null):string{
        $id=md5("$name$this->suffixid");
        $id2=md5("{$name}2$this->suffixid");
        $value2=$HashList[$value];

        $Encoded=base64_encode(serialize($HashList));
        $DefaultValue=urlencode($value);
        if(!is_null($jsafter)) {
            $jsafter = urlencode(base64_encode($jsafter));
        }
        $labelenc=urlencode($label);
        $page=CurrentPageName();
        $js="Loadjs('fw.browse-select.php?&page=$page&name=$name&field-id=$id&Hash=$Encoded&field-id2=$id2&value=$DefaultValue&js=$jsafter&label=$labelenc')";

        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $F="<div class='input-group'>
            <input type='hidden' id='$id' value='".req_html($value)."'> 
            <input type='text' class='form-control' id='$id2' value='".req_html($value2)."' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{select}...</button> </span>
			</div>";

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);
    }
    public function field_browse_ldapgroups($name,$label,$value,$explain=null){
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $js="Loadjs('fw.browse-localldap.php?field-id=$id')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
			</div>";
        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }
    public function field_browse_ldap_user($name,$label,$value,$explain=null){
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $js="Loadjs('fw.browse-ldapuser.php?field-id=$id')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
			</div>";
        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }

    public function field_browse_adgroups($name,$label,$value=null,$explain=null){
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");$opts=null;$addon=null;
        if(preg_match("#(.+?):(.+)#",$label,$re)){
            $opts=$re[2];
            $label=$re[1];
        }
        if($opts=="by-dn"){
            $addon="&bydn=yes";
        }
        $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
        if ($EnableExternalACLADAgent==1){
            $js="Loadjs('fw.browse-activedirectory-adagent.php?field-id=$id{$addon}')";
            $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
			</div>";
        }
        else {
            $js="Loadjs('fw.browse-activedirectory.php?field-id=$id{$addon}')";
            $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled> 
			<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
			</div>";
        }

        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F);

    }

    public function field_charset($name,$label,$value,$explain=null){
        $CHARSETS["Arabic (ASMO 708)"]="ASMO-708";
        $CHARSETS["Arabic (DOS)"]="DOS-720";
        $CHARSETS["Arabic (ISO)"]="iso-8859-6";
        $CHARSETS["Arabic (Mac)"]="x-mac-arabic";
        $CHARSETS["Arabic (Windows)"]="windows-1256";
        $CHARSETS["Baltic (DOS)"]="ibm775";
        $CHARSETS["Baltic (ISO)"]="iso-8859-4";
        $CHARSETS["Baltic (Windows)"]="windows-1257";
        $CHARSETS["Central European (DOS)"]="ibm852";
        $CHARSETS["Central European (ISO)"]="iso-8859-2";
        $CHARSETS["Central European (Mac)"]="x-mac-ce";
        $CHARSETS["Central European (Windows)"]="windows-1250";
        $CHARSETS["Chinese Simplified (EUC)"]="EUC-CN";
        $CHARSETS["Chinese Simplified (GB2312)"]="gb2312";
        $CHARSETS["Chinese Simplified (HZ)"]="hz-gb-2312";
        $CHARSETS["Chinese Simplified (Mac)"]="x-mac-chinesesimp";
        $CHARSETS["Chinese Traditional (Big5)"]="big5";
        $CHARSETS["Chinese Traditional (CNS)"]="x-Chinese-CNS";
        $CHARSETS["Chinese Traditional (Eten)"]="x-Chinese-Eten";
        $CHARSETS["Chinese Traditional (Mac)"]="x-mac-chinesetrad";
        $CHARSETS["Cyrillic (DOS)"]="cp866";
        $CHARSETS["Cyrillic (ISO)"]="iso-8859-5";
        $CHARSETS["Cyrillic (KOI8-R)"]="koi8-r";
        $CHARSETS["Cyrillic (KOI8-U)"]="koi8-u";
        $CHARSETS["Cyrillic (Mac)"]="x-mac-cyrillic";
        $CHARSETS["Cyrillic (Windows)"]="windows-1251";
        $CHARSETS["Europa"]="x-Europa";
        $CHARSETS["German (IA5)"]="x-IA5-German";
        $CHARSETS["Greek (DOS)"]="ibm737";
        $CHARSETS["Greek (ISO)"]="iso-8859-7";
        $CHARSETS["Greek (Mac)"]="x-mac-greek";
        $CHARSETS["Greek (Windows)"]="windows-1253";
        $CHARSETS["Greek, Modern (DOS)"]="ibm869";
        $CHARSETS["Hebrew (DOS)"]="DOS-862";
        $CHARSETS["Hebrew (ISO-Logical)"]="iso-8859-8-i";
        $CHARSETS["Hebrew (ISO-Visual)"]="iso-8859-8";
        $CHARSETS["Hebrew (Mac)"]="x-mac-hebrew";
        $CHARSETS["Hebrew (Windows)"]="windows-1255";
        $CHARSETS["IBM EBCDIC (Arabic)"]="x-EBCDIC-Arabic";
        $CHARSETS["IBM EBCDIC (Cyrillic Russian)"]="x-EBCDIC-CyrillicRussian";
        $CHARSETS["IBM EBCDIC (Cyrillic Serbian-Bulgarian)"]="x-EBCDIC-CyrillicSerbianBulgarian";
        $CHARSETS["IBM EBCDIC (Denmark-Norway)"]="x-EBCDIC-DenmarkNorway";
        $CHARSETS["IBM EBCDIC (Denmark-Norway-Euro)"]="x-ebcdic-denmarknorway-euro";
        $CHARSETS["IBM EBCDIC (Finland-Sweden)"]="x-EBCDIC-FinlandSweden";
        $CHARSETS["IBM EBCDIC (Finland-Sweden-Euro)"]="x-ebcdic-finlandsweden-euro";
        $CHARSETS["IBM EBCDIC (France-Euro)"]="x-ebcdic-france-euro";
        $CHARSETS["IBM EBCDIC (Germany)"]="x-EBCDIC-Germany";
        $CHARSETS["IBM EBCDIC (Germany-Euro)"]="x-ebcdic-germany-euro";
        $CHARSETS["IBM EBCDIC (Greek Modern)"]="x-EBCDIC-GreekModern";
        $CHARSETS["IBM EBCDIC (Greek)"]="x-EBCDIC-Greek";
        $CHARSETS["IBM EBCDIC (Hebrew)"]="x-EBCDIC-Hebrew";
        $CHARSETS["IBM EBCDIC (Icelandic)"]="x-EBCDIC-Icelandic";
        $CHARSETS["IBM EBCDIC (Icelandic-Euro)"]="x-ebcdic-icelandic-euro";
        $CHARSETS["IBM EBCDIC (International-Euro)"]="x-ebcdic-international-euro";
        $CHARSETS["IBM EBCDIC (Italy)"]="x-EBCDIC-Italy";
        $CHARSETS["IBM EBCDIC (Italy-Euro)"]="x-ebcdic-italy-euro";
        $CHARSETS["IBM EBCDIC (Japanese and Japanese Katakana)"]="x-EBCDIC-JapaneseAndKana";
        $CHARSETS["IBM EBCDIC (Japanese and Japanese-Latin)"]="x-EBCDIC-JapaneseAndJapaneseLatin";
        $CHARSETS["IBM EBCDIC (Japanese and US-Canada)"]="x-EBCDIC-JapaneseAndUSCanada";
        $CHARSETS["IBM EBCDIC (Japanese katakana)"]="x-EBCDIC-JapaneseKatakana";
        $CHARSETS["IBM EBCDIC (Korean and Korean Extended)"]="x-EBCDIC-KoreanAndKoreanExtended";
        $CHARSETS["IBM EBCDIC (Korean Extended)"]="x-EBCDIC-KoreanExtended";
        $CHARSETS["IBM EBCDIC (Multilingual Latin-2)"]="CP870";
        $CHARSETS["IBM EBCDIC (Simplified Chinese)"]="x-EBCDIC-SimplifiedChinese";
        $CHARSETS["IBM EBCDIC (Spain)"]="X-EBCDIC-Spain";
        $CHARSETS["IBM EBCDIC (Spain-Euro)"]="x-ebcdic-spain-euro";
        $CHARSETS["IBM EBCDIC (Thai)"]="x-EBCDIC-Thai";
        $CHARSETS["IBM EBCDIC (Traditional Chinese)"]="x-EBCDIC-TraditionalChinese";
        $CHARSETS["IBM EBCDIC (Turkish Latin-5)"]="CP1026";
        $CHARSETS["IBM EBCDIC (Turkish)"]="x-EBCDIC-Turkish";
        $CHARSETS["IBM EBCDIC (UK)"]="x-EBCDIC-UK";
        $CHARSETS["IBM EBCDIC (UK-Euro)"]="x-ebcdic-uk-euro";
        $CHARSETS["IBM EBCDIC (US-Canada)"]="ebcdic-cp-us";
        $CHARSETS["IBM EBCDIC (US-Canada-Euro)"]="x-ebcdic-cp-us-euro";
        $CHARSETS["Icelandic (DOS)"]="ibm861";
        $CHARSETS["Icelandic (Mac)"]="x-mac-icelandic";
        $CHARSETS["ISCII Assamese"]="x-iscii-as";
        $CHARSETS["ISCII Bengali"]="x-iscii-be";
        $CHARSETS["ISCII Devanagari"]="x-iscii-de";
        $CHARSETS["ISCII Gujarathi"]="x-iscii-gu";
        $CHARSETS["ISCII Kannada"]="x-iscii-ka";
        $CHARSETS["ISCII Malayalam"]="x-iscii-ma";
        $CHARSETS["ISCII Oriya"]="x-iscii-or";
        $CHARSETS["ISCII Panjabi"]="x-iscii-pa";
        $CHARSETS["ISCII Tamil"]="x-iscii-ta";
        $CHARSETS["ISCII Telugu"]="x-iscii-te";
        $CHARSETS["Japanese (EUC)"]="euc-jp";
        $CHARSETS["Japanese (JIS)"]="iso-2022-jp";
        $CHARSETS["Japanese (JIS-Allow 1 byte Kana - SO/SI)"]="iso-2022-jp";
        $CHARSETS["Japanese (JIS-Allow 1 byte Kana)"]="csISO2022JP";
        $CHARSETS["Japanese (Mac)"]="x-mac-japanese";
        $CHARSETS["Japanese (Shift-JIS)"]="shift_jis";
        $CHARSETS["Korean"]="ks_c_5601-1987";
        $CHARSETS["Korean (EUC)"]="euc-kr";
        $CHARSETS["Korean (ISO)"]="iso-2022-kr";
        $CHARSETS["Korean (Johab)"]="Johab";
        $CHARSETS["Korean (Mac)"]="x-mac-korean";
        $CHARSETS["Latin 3 (ISO)"]="iso-8859-3";
        $CHARSETS["Latin 9 (ISO)"]="iso-8859-15";
        $CHARSETS["Norwegian (IA5)"]="x-IA5-Norwegian";
        $CHARSETS["OEM United States"]="IBM437";
        $CHARSETS["Swedish (IA5)"]="x-IA5-Swedish";
        $CHARSETS["Thai (Windows)"]="windows-874";
        $CHARSETS["Turkish (DOS)"]="ibm857";
        $CHARSETS["Turkish (ISO)"]="iso-8859-9";
        $CHARSETS["Turkish (Mac)"]="x-mac-turkish";
        $CHARSETS["Turkish (Windows)"]="windows-1254";
        $CHARSETS["Unicode"]="unicode";
        $CHARSETS["Unicode (Big-Endian)"]="unicodeFFFE";
        $CHARSETS["Unicode (UTF-7)"]="utf-7";
        $CHARSETS["Unicode (UTF-8)"]="utf-8";
        $CHARSETS["US-ASCII"]="us-ascii";
        $CHARSETS["Vietnamese (Windows)"]="windows-1258";
        $CHARSETS["Western European (DOS)"]="ibm850";
        $CHARSETS["Western European (IA5)"]="x-IA5";
        $CHARSETS["Western European (ISO)"]="iso-8859-1";
        $CHARSETS["Western European (Mac)"]="macintosh";
        $CHARSETS["Western European (Windows)"]="Windows-1252";
        $CF=array();
        foreach ($CHARSETS as $zexplain=>$key){
            $CF[strtoupper($key)]=$zexplain;
        }
        if($explain==null or is_numeric($explain)){
            $explain="$value (".$CF[strtoupper($value)].")";
        }
        return $this->field_array_hash($CF,$name,$label,$value,false,$explain);

    }


    public function field_schedule($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $each=$this->javascript_parse_text("{each}");
        $day=$this->javascript_parse_text("{day}");
        $Hours=$this->javascript_parse_text("{hours}");
        $saturday=$this->javascript_parse_text("{saturday}");
        $sunday=$this->javascript_parse_text("{sunday}");
        $minutes=$this->javascript_parse_text("{minutes}");
        $F[]="<div id='Sched$id'></div><input type='hidden' id='$id' value='".req_html($value)."'>
		<script>
		     $('#Sched$id').cron({";


        $F[]="customValues: {";
        $F[]="\"2 $minutes\" : \"0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * *\",";
        $F[]="\"2 $minutes (0-20)\" : \"0,2,4,6,8,10,12,14,16,18,20,22 * * *\",";
        $F[]="\"3 $minutes\" : \"3,6,9,11,13,15,17,19,21,23,25,27,29,31,33,35,37,39,41,43,45,47,49,51,55,57,59 * * * *\",";
        $F[]="\"5 $minutes\" : \"*/5 * * * *\",";
        $F[]="\"5 $minutes start at 2mn\" : \"2,7,12,17,22,27,32,37,42,47,52,57 * * * *\",";
        $F[]="\"5 $minutes start at 5mn\" : \"5,10,15,20,25,30,35,40,45,50,55,59 * * * *\",";
        $F[]="\"5 $minutes start at 7mn\" : \"7,22,37 * * * *\",";

        $F[]="\"$each 15 $minutes\" : \"0,15,30,45 * * * *\",";
        $F[]="\"$each 05,20,35 $minutes\" : \"5,20,35 * * * *\",";
        $F[]="\"$each 00,15,30 $minutes\" : \"0,15,30 * * * *\",";
        $F[]="\"$each 10 $minutes\" : \"10,20,30,40,50 * * * *\",";
        $F[]="\"$each 30 $minutes\" : \"0,30 * * * *\",";
        $F[]="\"$each $day 4h30 \" : \"30 4,8,12,16,20 * * *\",";
        $F[]="\"$each $day 23h15 \" : \"15 23 2,4,6,8,10,12,14,16,18,20,22,24,26,28,30 * *\",";
        $F[]="\"non-productive hours\" : \"30 3,6,12,18,22 * * *\",";
        $F[]="\"$each 2 $Hours $each $saturday/$sunday\" : \"0 */2 * * 5,6\",";
        $F[]="\"$each 2 $Hours $each $day\" : \"0 */2 * * *\",";
        $F[]="\"$each 3 $Hours $each $day\" : \"0 0,3,5,7,9,11,13,15,17,19,23 * * *\",";
        $F[]="\"3 $Hours 14 $minutes\" : \"14 0,3,6,9,12,15,18,21 * * *\",";
        $F[]="\"4 $Hours 00 $minutes\" : \"0 4,8,12,16,20 * * *\",";
        $F[]="\"5 $Hours 30 $minutes\" : \"30 0,5,10,15,20 * * *\"";
        $F[]="},";

        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        if($value<>null){    $F[]="initial: \"".req_js($value)."\",";}

        $F[]=" onChange: function() {
            	document.getElementById('$id').value=$(this).cron('value')
            },
            useGentleSelect: true
        });
		</script>
		";
        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,@implode("\n", $F),$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,@implode("\n", $F),$explain);
    }

    public function GlancesInfos():array{
        $EnableGlances  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
        if($EnableGlances==0) {return array();}
        $glances_dump   = $this->glances_dump();
        if(!isset($glances_dump)){return array();}
        if (!property_exists($glances_dump, "cpu")) {return array();}
        $cpu            = $glances_dump->cpu->total;
        $mem            = $glances_dump->mem->percent;
        $mem_total      = FormatBytes($glances_dump->mem->total / 1024);

        return array(
            "cpu"=>$cpu,"mem"=>$mem,"mem_total"=>$mem_total

        );
    }

    public function GlancesTop(){
        $EnableGlances  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
        if($EnableGlances==0) {return null;}
        $glances_dump   = $this->glances_dump();
        if(!is_object($glances_dump)){return null;}
        if (!property_exists($glances_dump, "cpu")) {return null;}
        $cpu            = $glances_dump->cpu->total;
        $mem            = $glances_dump->mem->percent;
        $mem_total      = FormatBytes($glances_dump->mem->total / 1024);
        $mem_color      = null;
        $cpu_color      = null;

        if ($mem > 70) {
            $mem_color = "text-info";
        }
        if ($mem > 80) {
            $mem_color = "text-warning";
        }
        if ($mem > 90) {
            $mem_color = "text-danger";
        }

        if ($cpu > 70) {
            $cpu_color = "text-info";
        }
        if ($cpu > 80) {
            $cpu_color = "text-warning";
        }
        if ($cpu > 90) {
            $cpu_color = "text-danger";
        }

        $f[]='$("#top-cpu").html("<span class='.$cpu_color.'>Cpu:'.$cpu.'%</span>");';
        $f[]='$("#top-memory").html("<span class='.$mem_color.'>Mem:'."{$mem}%/{$mem_total}".'</span>");';
        return @implode("\n",$f);
    }


    public function glances_dump(){
        if(!function_exists("curl_init")){return array();}
        if(!function_exists("json_decode")){return array();}
        $GLANCES_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GLANCES_VERSION");
        preg_match("#^([0-9]+)\.#",$GLANCES_VERSION,$re);
        $v=2;
        if(intval($re[1])>2){$v=3;}

        $uri="http://127.0.0.1:61208/api/$v/all";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_URL, "$uri");
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        $dataIndex=curl_exec($ch);
        $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if($GLOBALS["VERBOSE"]){echo "<strong>$uri: $CURLINFO_HTTP_CODE</strong><br>\n";}

        return json_decode($dataIndex);
    }

    public function page_header($title,$ico,$explain=null,$js=null,$url=null,$force_progress=null,$searchbox=false,$forceloadingdiv=null):string{
        $t=time();$additional_progress=null;$jstiny=null;
        $iddiv="table-$t";
        if($forceloadingdiv<>null){$iddiv=$forceloadingdiv;}
        $jsH="LoadAjax('$iddiv','$js');";
        $title_text=$this->javascript_parse_text($title);
        if(!isset($_GET["tiny-page"])){

            if($title<>null) {
                $html[] = "<div class=\"row border-bottom white-bg dashboard-header\">";
                $html[] = "<table style='width:100%'>";
                $html[] = "<tr>";
                if(preg_match("#\.png$#",$ico)){
                    $html[] = "<td style='vertical-align:top;padding-right: 10px;width:1%' nowrap>
                    <div id='tiny-ico'>
                    <img src='img/$ico' alt=\"\">
                    </div>
                    </td>";
                }else {
                    $html[] = "<td style='vertical-align:top;padding-right: 10px;width:1%' nowrap>
                        <div id='tiny-ico'>
                            <i class='fa-8x $ico'></i>
                       </div> 
                       </td>";
                }
                $html[] = "<td style='vertical-align:top;'><div class=\"col-sm-12\" style='margin-top:-26px'>";
                $html[]="<h1 class=ng-binding>";
                $html[]="<div id='tiny-title'>$title</div></h1>";
                $html[] = "<p>";

                if ($explain <> null) {

                    $html[]="<span id='tiny-explain'>$explain</span>
                        <div id='tiny-button'></div>
                        ";
                }else{
                    $explain="<span id='none'></span>
                    <span id='tiny-explain'></span>
                    <div id='tiny-button'></div>";
                }
                $html[] = "</p>";
                $html[] = "</div></td>";
                $html[] = "</tr>";
                $html[] = "</table>";
                $html[] = "</div>";

            }
        }
        $HTTP_X_ARTICA_SUBFOLDER="";
        if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
            $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"];
        }
        if(isset($_SERVER["HTTP_X_META_PREFIX"])){
            $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_META_PREFIX"]."/";
        }
        if(!is_null($url)) {
            if (substr($url, 0, 1) <> "/") {
                $url = "/" . $url;
            }
        }
        $rpgressPage="$HTTP_X_ARTICA_SUBFOLDER/fw.progress.php";
        $rpgressPage=str_replace("//","/",$rpgressPage);

        if(isset($_GET["tiny-page"])){
            $TINY_ARRAY["TITLE"]=$title;
            $TINY_ARRAY["ICO"]=$ico;
            $TINY_ARRAY["EXPL"]=$explain;
            $TINY_ARRAY["URL"]=$url;
            $jstiny="Loadjs('$rpgressPage?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

        }

        $html[]="<div class='row'>";
        $html[]="<div id='progress-$t'></div>";

        if(!is_null($force_progress)) {
            if (strpos($force_progress, ",") > 0) {
                $tb = explode(",", $force_progress);
                $force_progress = $tb[0];
                $additional_progress = $tb[1];
            }
        }

        if($force_progress<>null){
            $html[]="<div id='$force_progress' style='margin-left:15px'></div>";
        }
        if($additional_progress<>null){
            $html[] = "<div id='$additional_progress' style='margin-left:15px'></div>";
        }
        $animatedDiv="ibox-content";
        $html[]="<div class='ibox-content' style='min-height:600px'>";
        if($searchbox){
            $jsH=null;
            $urlToadd=null;
            $page=CurrentPageName();
            if(!is_bool($searchbox)){
                $urlToadd=$searchbox;
                if(strpos($urlToadd,0,1)<>"&"){$urlToadd="&$urlToadd";}
            }
            $html[]=$this->search_block($page,"file",null,null,$urlToadd,$js);
        }else {
            $html[] = "<div id='$iddiv'></div>";
        }

        $html[]="</div>";
        $html[]="</div>";
        $html[]="<script>";
        $BrowserItem["title"]=$title_text;

        if($url<>null) {
            $BrowserItem["url"]=$url;
        }
       
        if($js<>null) {
            $html[] = "$jsH";
        }
        if($jstiny<>null){
            $html[]=$jstiny;
        }

        $html[]="Loadjs('$HTTP_X_ARTICA_SUBFOLDER/fw.progress.php?browser-items=".base64_encode(serialize($BrowserItem))."');";
        $divs[]="tiny-ico:flip";

        $t=time();

        $html[] = "function Animated$t(){";
        foreach ($divs as $animatedDiv) {
            $tt=explode(":",$animatedDiv);
            $xdiv=$tt[0];
            $animation=$tt[1];
            $html[] = "if(document.getElementById('$xdiv')){";
            $html[] = "$('#$xdiv').removeAttr('class').attr('class', '');";
            $html[] = "$('#$xdiv').addClass('animated');";
            $html[] = "$('#$xdiv').addClass('$animation');";
            $html[] = "}\n";
        }
        $html[] = "}\n";


        $html[]="Animated$t();";
        $html[]="</script>";
        $html_final=@implode("\n",$html);

        if($searchbox){
            $html_final=str_replace("%sfunction",$this->search_box_function,$html_final);

        }
        return $html_final;

    }

    public function field_acls_groups($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $TableLink=null;
        if(isset($_GET["TableLink"])) {
            $TableLink = $_GET["TableLink"];
        }
        if($TableLink==null){$TableLink="webfilters_sqacllinks";}
        $function="";
        if(isset($_GET["function"])){$function=$_GET["function"];}
        if(!isset($_GET["ProxyPac"])){$_GET["ProxyPac"]=0;}
        if(!isset($_GET["DnsDist"])){$_GET["DnsDist"]=0;}
        if(!isset($_GET["firewall"])){$_GET["firewall"]=0;}
        $ProxyPac=intval($_GET["ProxyPac"]);
        $DnsDist=intval($_GET["DnsDist"]);
        $firewall=intval($_GET["firewall"]);
        $fastacls=intval($_GET["fastacls"]);
        $F="<div class='input-group'>
		<input type='text' id='$id' class='form-control' disabled> <span class='input-group-btn'> <button 
			class='btn btn-primary' 
			type='button' 
			OnClick=\"Loadjs('fw.proxy.acls.new-object.php?id=$id&ProxyPac=$ProxyPac&DnsDist=$DnsDist&firewall=$firewall&TableLink=$TableLink&fastacls=$fastacls&function=$function');\">{select}</button> </span>
		</div>";

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);

    }

    public function field_choose_adgroupDN($name,$label=null,$value=null,$explain=null){

        return $this->field_activedirectorygrp($name,$label,$value,false,$explain,false,true);
    }


    public function field_ldap_group($name,$label,$value,$notnull=false,$explain=null){
        $ldap = new clladp();
        $ou=null;
        $EnableMultipleOrganizations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));

        if( $EnableMultipleOrganizations==0){
            if($EnableMultipleOrganizations==0) {
                $hash = $ldap->hash_get_ou(false);
                $ou=$hash[0];
            }
        }

        $groups=$ldap->hash_groups($ou,1);
        return $this->field_array_hash($groups,$name,$label,$value,$notnull,$explain);


    }

    public function field_remote_ldap_groups($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        include_once(dirname(__FILE__)."/class.ldap-extern.inc");
        $ldap=new ldap_extern();
        $SearchGroups=$ldap->SearchGroups("*");
        foreach ($SearchGroups as $DN=>$GroupName){
            $DN=base64_encode($DN);
            $Key="ExtLDAP:$GroupName:$DN";
            $HASH[$Key]=$GroupName;

        }
        return $this->field_array_hash($HASH,$name,"nonull:$label",$value,$notnull,$explain);

    }

    public function field_activedirectory_cnxs($name,$label,$value,$explain=null):string{
        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADLicenseRemoved"))==1){$ActiveDirectoryConnections=array();}
        if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}

        foreach ($ActiveDirectoryConnections as $index=>$ligne) {
            if (!is_numeric($index)) {continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}
            if (!isset($ligne["LDAP_PORT"])) {$ligne["LDAP_PORT"] = 389;}
            if (!isset($ligne["LDAP_SSL"])) {$ligne["LDAP_SSL"] = 0;}
            if($ligne["LDAP_SSL"]==1){$ligne["LDAP_PORT"]=636;}
            $hash[$index]="{$ligne["LDAP_SERVER"]}:{$ligne["LDAP_PORT"]}";
        }

        return $this->field_array_hash($hash,$name,"nonull:$label",$value,true,$explain);
    }


    public function field_activedirectorygrp($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$UseDN=false){
        $ext=null;if($UseDN){$ext="&UseDN=1";}
        $id=md5("$name$this->suffixid");
        $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$EnableActiveDirectoryFeature=0;}

        if($EnableActiveDirectoryFeature==0){
            $F="<div class='text-danger'>{disabled}</div>";
            // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
            $this->XHR[]="XHR.appendData('$name',encodeURIComponent('".req_js($value)."'));";
            return $this->_field_intr($label,$F,$explain);
        }

        $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
        if ($EnableExternalACLADAgent==1){
            $F = "<div class='input-group'>
		<input type='text' id='$id' class='form-control' value='".req_html($value)."' disabled> <span class='input-group-btn'> <button
		class='btn btn-primary'
		type='button'
		OnClick=\"Loadjs('fw.BrowseActiveDirectoryGroups.ad.agent.php?id=$id$ext');\">{browse}...</button> </span>
		</div>";
        }
        else {
            $F = "<div class='input-group'>
		<input type='text' id='$id' class='form-control' value='".req_html($value)."' disabled> <span class='input-group-btn'> <button
		class='btn btn-primary'
		type='button'
		OnClick=\"Loadjs('fw.BrowseActiveDirectoryGroups.php?id=$id$ext');\">{browse}...</button> </span>
		</div>";
        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);


    }


    public function td_button($label,$bt_label, $bt_js=null,$value=null,$explain=null,$labelstyle="label-primary"){
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        if($bt_js<>null){$bt_js="OnClick=\"$bt_js\"";}


        $button="&nbsp;&nbsp;&nbsp;<span class=\"label $labelstyle\" $pointers $bt_js>{$bt_label}...</span>";

        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $F="<label>".html_safe_fragment($value)."</label>$button
		
		";


        return $this->_ENGINE_parse_body($this->_field_intr($label,$F,$explain));

    }

    public function table_td1prc_Left($text_class=null):string{
        return "width=1% class='$text_class' style='vertical-align:middle;text-align:left' nowrap";
    }

    public function table_td1prc($text_class=null):string{
        return "width=1% class='$text_class center' style='vertical-align:middle' nowrap";
    }
    public function table_td1prcLeft($text_class=null):string{
        return "width=1% class='$text_class' style='vertical-align:middle;text-algign:left' nowrap";
    }

    public function table_tdfree($text_class=null):string{
        return "class='$text_class' style='vertical-align:middle'";
    }

    public function table_head($heads=array(),$id=null):string{
    $f[]="<table class=\"table table-hover\" id='$id' style='margin-top:10px'>";
        $f[]="<thead>";
        $f[]="<tr>";
        foreach ($heads as $head){
            $f[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$head</th>";
        }
        $f[]="</tr>";
        $f[]="</thead>";
        return $this->_ENGINE_parse_body(@implode("\n",$f));

    }

    public function th_buttons_label($caption,$js,$icon_class,$security=null,$label_class=null,$ids=""):string{
        // Petit bouton de barre d'en-tete de tableau (th_buttons, 100 appels) : meme
        // systeme que table_buttons(), en 28 px. Variante "abt-..." explicite, sinon
        // *-danger = destructif, sinon role deduit de l'icone et du libelle.
        // $ids = "bouton,icone,texte" : les pages pilotent ces elements en JS.
        $icon_class=trim((string)$icon_class);
        if($icon_class<>"" && strpos($icon_class," ")===false){$icon_class="fa $icon_class";}
        $label_class=trim((string)$label_class);
        if(preg_match('#^abt-[a-z0-9]+$#',$label_class)){
            $variant=$label_class;
        }elseif(preg_match('#-danger$#',$label_class)){
            $variant="abt-x";
        }else{
            $map=array("p"=>"abt-p","s"=>"abt-s","x"=>"abt-x");
            $variant=$map[$this->abt_role($icon_class,(string)$caption)];
        }
        // Classe Bootstrap de repli : sans la feuille abt (angular.css.php ancien), le bouton
        // garde sa couleur au lieu de retomber sur un .btn gris.
        $cls=array("btn",$this->abt_bs_opt($variant),"abt","abt-i",$variant,"abt-sm");
        $jsx=trim((string)$js);
        $nojs=strlen($jsx)<2 || preg_match('#^blur\(\);?$#i',$jsx);
        if($security<>null && !$this->IsSecurity($security)){
            $cls[]="abt-l";
            $icon_class="fa-solid fa-lock";
            $tip=str_replace('"',"&quot;",$this->_ENGINE_parse_body("{this_form_locked_privileges}"));
            $attr=" aria-disabled=\"true\" data-tip=\"$tip\"";
        }elseif($nojs){
            $attr=" disabled";
        }else{
            $attr=" OnClick=\"$jsx\"";
        }
        $idbutt="";$idico="";$idText="";
        if(!is_null($ids) && strlen($ids)>2){
            $tb=explode(",",$ids);
            $idbutt=" id='$tb[0]'";
            if(isset($tb[1])){$idico=" id='$tb[1]'";}
            if(isset($tb[2])){$idText=" id='$tb[2]'";}
        }
        $ico=$icon_class<>""?"<i class=\"$icon_class\"$idico></i>":"";
        return "<button type=\"button\" class=\"".@implode(" ",$cls)."\"$idbutt$attr>$ico<span$idText>$caption</span></button>";
    }

    public function th_buttons($topbuttons=array()):string{
        // Barre de petits boutons au-dessus d'un tableau. Les quatre couleurs tournantes
        // (primary/warning/info/success) ne disaient rien : le role vient de l'icone et du
        // libelle (th_buttons_label). Forces conserves : corbeille / interdit = destructif,
        // "green" = principal, OFF / {disabled} / sans JS = neutre.
        $html=array();
        $rot=0;
        $html[]="<div class=\"btn-group\">";
        if(is_null($topbuttons)){
            $topbuttons=array();
        }
        foreach ($topbuttons as $ARRAY){
            if(!isset($ARRAY[2])){continue;}
            $Perms      = null;
            $class      = null;
            $jsrestart  = $ARRAY[0];
            $icon       = $ARRAY[1];
            $label      = $ARRAY[2];
            $ids="";

            if(isset($ARRAY[3])){
                VERBOSE("ARRAY[3]=$ARRAY[3]",__LINE__);
                if(preg_match("#ids:(.+)#",$ARRAY[3],$re)){
                    $ids=$re[1];
                }
            }

            if(isset($ARRAY[3])){
                if($ARRAY[3]=="OFF"){
                    $class="abt-o";
                }elseif($ids==""){
                    $Perms = strval($ARRAY[3]);
                }
            }

            if($icon=="fa fa-trash" || $icon==ico_ban){$class="abt-x";}
            if(preg_match("#(OFF|\{disabled\}|\{inactive2\})#",$label)){$class="abt-o";}
            if(strlen($jsrestart)==0){$class="abt-o";}
            if($Perms=="green"){$class="abt-s";}
            if(in_array($Perms,array("green","red","grey","OFF"))){$Perms=null;}
            // Bouton actif : couleur de rang (vert, bleu clair, bleu, orange, emeraude) ; destructif = rouge.
            if($class===null){
                $class=$this->abt_role(trim((string)$icon),(string)$label)=="x"?"abt-x":$this->abt_rot($rot++);
            }

            $html[]=$this->th_buttons_label($label,$jsrestart,$icon,$Perms,$class,$ids);
        }
        $html[]="</div>";
        return $this->_ENGINE_parse_body($html);
    }
    function footable($id,$filter=false):string{
        $filtertex="false";
        if ($filter){
            $filtertex="true";
        }

        $f[]="";
        $f[]="$(document).ready(function(){";
        $f[]="  $('#$id').footable({";
        $f[]="    \"filtering\":{\"enabled\":$filtertex},";
        $f[]="    \"sorting\":{\"enabled\":true},";
        $f[]="    \"paging\":{\"size\":50}";
        $f[]="  });";
        $f[]="});";
        $f[]="";

        return @implode("\n",$f);

    }
    /**
     * Barre d'actions d'en-tete (TINY) et de tableau -- maquette
     * stitch_console_button_system_redesign.
     * Chaque bouton est un triplet [js, icone, libelle], plus [3] le droit requis
     * et, facultatif, [4] le role force ("primary", "secondary", "danger").
     * La couleur suit le ROLE, plus la position : au plus un bouton principal
     * (plein, couleur de marque), les autres neutres, le destructif en rouge
     * a la fin apres un separateur, le refuse verrouille avec son explication.
     * Au-dela de 6 boutons, les 5 premiers restent visibles et le reste passe
     * dans un menu "More". Styles : table_buttons_css(), servie par angular.css.php.
     * Le balisage reste court : la barre voyage en base64 dans l'URL du TINY
     * (limite nginx 8 Ko).
     */
    public function table_buttons($topbuttons=array(),$security=null):string{
        $small=false;
        $sub=null;
        $items=array();
        if(!is_array($topbuttons)){$topbuttons=array();}
        if(isset($topbuttons["SMALL"])){$small=true;unset($topbuttons["SMALL"]);}
        if(isset($topbuttons["SUB"])){$sub=$topbuttons["SUB"];unset($topbuttons["SUB"]);}
        // SUB_TITLE : libelle du bouton a liste (defaut {options}).
        $subTitle="{options}";
        if(isset($topbuttons["SUB_TITLE"])){$subTitle=strval($topbuttons["SUB_TITLE"]);unset($topbuttons["SUB_TITLE"]);}
        $primary=false;
        foreach($topbuttons as $ARRAY){
            if(!is_array($ARRAY) || !isset($ARRAY[2])){continue;}
            $b=$this->abt_item($ARRAY);
            if($b["role"]=="p"){
                if($primary){$b["role"]="s";}
                $primary=true;
            }
            $items[]=$b;
        }
        // Principal en tete, destructifs en queue, ordre de l'auteur entre les deux.
        $lead=array();$rest=array();$tail=array();
        foreach($items as $b){
            if($b["role"]=="p"){$lead[]=$b;continue;}
            if($b["role"]=="x"){$tail[]=$b;continue;}
            $rest[]=$b;
        }
        $main=array_merge($lead,$rest);
        $visible=$main;
        $menu=array();
        if(count($items)>6){
            $visible=array_slice($main,0,5);
            $menu=array_merge(array_slice($main,5),$tail);
            $tail=array();
        }
        $n=0;
        foreach($visible as $k=>$b){
            if($b["role"]=="p" || $b["role"]=="s"){$visible[$k]["rot"]=$this->abt_rot($n);$n++;}
        }
        $this->abt_more_rot=$this->abt_rot($n);
        $h=array();
        $h[]="<div class=\"abt-bar\">";
        foreach($visible as $b){$h[]=$this->abt_button($b,$small);}
        if(count($tail)>0){
            if(count($visible)>0){$h[]="<span class=\"abt-sep\"></span>";}
            foreach($tail as $b){$h[]=$this->abt_button($b,$small);}
        }
        if(count($menu)>0){$h[]=$this->abt_more($menu,$small);}
        if(is_array($sub)){$h[]=$this->button_dropdown_table($subTitle,$sub,$security,"btn-default");}
        $h[]="</div>";
        // Separateur " " : ignore par le flex de .abt-bar, il espace les boutons quand la
        // feuille abt manque.
        $out=$this->_ENGINE_parse_body(@implode(" ",$h));
        html_trusted($out);
        return $out;
    }

    /** Un triplet de $topbuttons -> js, icone, libelle et role (p,s,x,l,d). */
    private function abt_item(array $ARRAY):array{
        $js=trim(strval($ARRAY[0]));
        $icon=trim(strval($ARRAY[1]));
        $label=strval($ARRAY[2]);
        $forced=array("primary"=>"p","secondary"=>"s","danger"=>"x");
        $role="";
        if(isset($ARRAY[4]) && isset($forced[strval($ARRAY[4])])){$role=$forced[strval($ARRAY[4])];}
        if(preg_match("#red:(.+)#",$label,$re)){$label=$re[1];$role="x";}
        if($role==""){$role=$this->abt_role($icon,$label);}
        // "ids:bouton,icone,texte" (comme th_buttons) : des ids que la page pilote
        // en JS, pas un droit -- sans ce cas, IsSecurity("ids:...") verrouillait
        // le bouton.
        $ids="";
        if(isset($ARRAY[3]) && preg_match('#^ids:(.+)$#',strval($ARRAY[3]),$re)){
            $ids=$re[1];
            $ARRAY[3]="";
        }
        if(isset($ARRAY[3]) && strval($ARRAY[3])<>""){
            if(!$this->IsSecurity(strval($ARRAY[3]))){
                VERBOSE("Perms FALSE = {$ARRAY[3]}",__LINE__);
                $role="l";
            }
        }
        if(strlen($js)<2){$role="d";}
        return array("js"=>$js,"icon"=>$icon,"label"=>$label,"role"=>$role,"ids"=>$ids);
    }

    /**
     * Role deduit de ce que les 518 pages passent deja : la corbeille ou un
     * token delete/remove/uninstall/purge = destructif ; fa-plus/save ou un
     * token new/add/create/apply/save/reconfigure/compile = principal ; le reste
     * neutre. "Refresh", premier bouton d'une barre sur trois, reste neutre.
     */
    private function abt_role($icon,$label):string{
        if(strpos($icon,"fa-trash")!==false){return "x";}
        if(preg_match('#(^\{|_)(delete|remove|uninstall|purge)(_[a-z0-9_]*)?\}$#i',$label)){return "x";}
        if(preg_match('#(^|\s)fa-(plus|save|floppy-disk)(\s|$)#',$icon)){return "p";}
        if(preg_match('#(^\{|_)(new|add|create|apply|save|reconfigure|compile)(_[a-z0-9_]*)?\}$#i',$label)){return "p";}
        return "s";
    }

    /** Un bouton de la barre. */
    /** Couleur d'un bouton actif de rang $i dans une barre : vert, bleu clair, bleu, orange, emeraude. */
    private function abt_rot($i):string{
        $r=array("abt-s","abt-c2","abt-c3","abt-c4","abt-c5");
        return $r[intval($i)%5];
    }

    /** Classes Bootstrap de repli (sans la feuille abt) pour une variante abt. */
    // Repli seulement pour une variante pleine : un bouton fantome (abt-g) ou verrouille
    // ne doit pas devenir un btn-default borde.
    private function abt_bs_opt($variant):string{
        if(in_array($variant,array("abt-p","abt-s","abt-c2","abt-c3","abt-c4","abt-c5","abt-x","abt-o"))){return $this->abt_bs($variant);}
        return "";
    }
    private function abt_bs($variant):string{
        $m=array("abt-p"=>"btn-primary","abt-s"=>"btn-primary","abt-c2"=>"btn-info","abt-c3"=>"btn-blue",
            "abt-c4"=>"btn-warning","abt-c5"=>"btn-primary","abt-x"=>"btn-danger");
        return isset($m[$variant])?$m[$variant]:"btn-default";
    }

    private function abt_button(array $b,$small):string{
        $cls=array("p"=>"abt-p","s"=>"abt-s","x"=>"abt-x","l"=>"abt-l","d"=>"abt-s");
        // "btn btn-<couleur>" a cote de "abt" : sans la feuille abt (angular.css.php ancien
        // ou pas encore recharge), le bouton retombe sur un bouton Bootstrap colore, pas
        // sur un <button> natif gris ; avec elle, .abt l'emporte (chargee apres bootstrap).
        $variant=$cls[$b["role"]];
        if($b["role"]=="d"){$variant="abt-o";}
        if(isset($b["rot"]) && ($b["role"]=="p" || $b["role"]=="s")){$variant=$b["rot"];}
        $class="btn ".$this->abt_bs($variant)." abt $variant".($small?" abt-sm":"");
        $icon=$b["icon"];
        if($b["role"]=="l"){
            $icon="fa-solid fa-lock";
            $attr=" aria-disabled=\"true\" data-tip=\"{this_form_locked_privileges}\"";
        }elseif($b["role"]=="d"){
            $attr=" disabled";
        }else{
            $attr=" OnClick=\"{$b["js"]}\"";
        }
        $idb="";$idi="";$idt="";
        if(!empty($b["ids"])){
            $p=array_pad(explode(",",preg_replace('/[^A-Za-z0-9_,-]/','',(string)$b["ids"])),3,"");
            if($p[0]<>""){$idb=" id=\"{$p[0]}\"";}
            if($p[1]<>""){$idi=" id=\"{$p[1]}\"";}
            if($p[2]<>""){$idt=" id=\"{$p[2]}\"";}
        }
        $i=$icon<>""?"<i$idi class=\"$icon\"></i>":"";
        return "<button type=\"button\"$idb class=\"$class\"$attr>$i<span$idt>{$b["label"]}</span></button>";
    }

    /** Menu "More" (dropdown Bootstrap 3 : clavier, Echap et aria-expanded fournis). */
    private function abt_more(array $menu,$small):string{
        $h=array();
        $h[]="<div class=\"abt-more\">";
        $mv=$this->abt_more_rot;
        $h[]="<button type=\"button\" class=\"btn ".$this->abt_bs($mv)." abt $mv".($small?" abt-sm":"")." dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\"><span>{abt_more}</span><i class=\"fa-solid fa-ellipsis\"></i></button>";
        $h[]="<ul class=\"dropdown-menu abt-menu\">";
        $n=0;$sep=false;
        foreach($menu as $b){
            if($b["role"]=="x" && !$sep){
                if($n>0){$h[]="<li class=\"divider\"></li>";}
                $sep=true;
            }
            $i=$b["icon"]<>""?"<i class=\"{$b["icon"]}\"></i>":"";
            if($b["role"]=="l"){
                $h[]="<li class=\"disabled\"><a href=\"#\" title=\"{this_form_locked_privileges}\" OnClick=\"return false\"><i class=\"fa-solid fa-lock\"></i>{$b["label"]}</a></li>";
            }elseif($b["role"]=="d"){
                $h[]="<li class=\"disabled\"><a href=\"#\" OnClick=\"return false\">$i{$b["label"]}</a></li>";
            }else{
                $x=$b["role"]=="x"?" class=\"abt-mx\"":"";
                $js=rtrim($b["js"],"; ");
                $h[]="<li$x><a href=\"#\" OnClick=\"$js;return false\">$i{$b["label"]}</a></li>";
            }
            $n++;
        }
        $h[]="</ul></div>";
        return @implode("",$h);
    }

    public function table_footer($id,$rows,$search=true):string{
        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='$rows'>";
        $html[]="<ul class='pagination pull-right'></ul>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</tfoot>";
        $html[]="</table>";
        $html[]=$this->table_footable_footer($id,$search);
        return @implode("\n",$html);
    }

    public function table_footable_footer($id,$search=true):string{
        $html[]="<script>";
        $html[]="NoSpinner();";
        $html[]=@implode("\n",$this->ICON_SCRIPTS);
        $html[]="$(document).ready(function() { ";
        $html[]="\t$('#$id').footable( {";
        if($search) {
            $html[] = "\"filtering\": { \"enabled\": true },";
        }else{
            $html[] = "\"filtering\": { \"enabled\": false },";
        }

        $html[]="\"sorting\": { \"enabled\": true";
        $html[]="},\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
        $html[]="</script>";
        return @implode(" ",$html);
    }


    /**
     * Feuille de style de la barre de recherche (.sb-stitch).
     * Extraite de search_block() pour que les pages qui filtrent leur tableau
     * cote navigateur -- awc_search_bar() -- portent EXACTEMENT le meme dessin
     * sans avoir a dupliquer les regles. La garde static vaut pour toute la
     * requete, quel que soit l'appelant: une seule feuille est emise.
     */
    public function search_block_css():string{
        static $__sb_css=false;
        if($__sb_css){return "";}
        $__sb_css=true;
        return "<style>
.sb-stitch{margin-right:0;margin-left:0}
.sb-stitch .ibox-content{border:0;padding-top:0;margin-top:5px;}
.sb-stitch .input-group{display:flex !important;align-items:center;height:46px;border:1px solid #bcc9c6;border-radius:14px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.05);padding:0 6px 0 0;width:750px;max-width:calc(100% - 15px);margin-left:15px}
.sb-stitch .input-group:focus-within{border-color:#00685e;box-shadow:0 0 0 3px rgba(0,104,94,.12)}
.sb-stitch .sb-ico{display:flex;align-items:center;padding:0 8px 0 15px;color:#6d7a77;font-size:15px}
.sb-stitch .form-control{border:0 !important;box-shadow:none !important;outline:0 !important;background:transparent !important;height:44px;line-height:44px;font-size:14px;flex:1 1 auto;min-width:0;padding:0}
.sb-stitch .input-group-btn{display:flex !important;align-items:center;white-space:nowrap;width:auto;margin:0 !important;left:auto !important;right:auto !important}
.sb-stitch .input-group-btn>.btn{margin:0 !important;left:auto !important}
.sb-stitch .sb-go{border:0 !important;background:#eceef0;color:#3d4947;font-weight:600;font-size:12px;letter-spacing:.03em;text-transform:uppercase;padding:0 16px;height:34px;line-height:34px;border-radius:10px;cursor:pointer;transition:background .12s;margin:0 !important}
.sb-stitch .sb-go:hover{background:#dfe2e4}
.sb-stitch .input-group-btn>.btn:not(.sb-go){margin-left:6px !important}
</style>";
    }

    public function search_block($pageToPost,$type="postgres",$table=null,$idToFill=null,$urlToadd=null,$options=null):string{
        $second_button=null;
        $t=time();
        $value=null;
        $width=null;
        $DropDownFinal="";
        if(!is_null($urlToadd)) {
            $urlToadd = str_replace("%s", "ss$t", $urlToadd);
        }

        if(is_array($options)){
            if(isset($options["WIDTH"])){
                $width="width:{$options["WIDTH"]}px;";
            }

            if(isset($options["WRENCH"])){
                $optionsZ=str_replace("%s","ss$t",$options["WRENCH"]);
                $second_button="<button style=\"text-transform: capitalize;\" class=\"btn btn -default\" type=\"button\" OnClick=\"$optionsZ;\"><i class='fa fa-wrench'></i></button>";
            }
            if(isset($options["UPLOAD"])){
                $ico=ico_upload;
                $optionsZ=str_replace("%s","ss$t",$options["UPLOAD"]);
                $second_button="<button style=\"text-transform: capitalize;\" class=\"btn btn -default\" type=\"button\" OnClick=\"$optionsZ;\"><i class='$ico'></i></button>";
            }

            if(isset($options["DROPDOWN"])){
                $DropDown[]="<div class=\"input-group-btn\">";
                $DropDownTitle=$options["DROPDOWN"]["TITLE"];
                $DropDown[]="<button data-toggle=\"dropdown\" class=\"btn btn-white dropdown-toggle\" type=\"button\" aria-expanded=\"true\"><span id='SearchBlockDropDownTitle'>$DropDownTitle</span> <span class=\"caret\"></span></button>";
                $DropDown[]="<ul class=\"dropdown-menu pull-right\">";
                foreach ($options["DROPDOWN"]["CONTENT"] as $title=>$js){
                    $js=str_replace("%s","ss$t",$js);
                    $DropDown[]="<li><a href=\"#\" onclick=\"$js\"\">$title</a></li>";
                }
                $DropDown[]="</ul>";
                $DropDown[]="</div>";
                $DropDownFinal=@implode("\n",$DropDown);
            }
            $options="";
        }


        if($options<>null){
            $options=str_replace("%s","ss$t",$options);
            $second_button="<button style=\"text-transform: capitalize;\" class=\"btn btn -default\" type=\"button\" OnClick=\"$options;\"><i class='fa fa-wrench'></i></button>";
        }

        if(!is_null($idToFill)) {
            if (preg_match("#value=(.+)#", $idToFill, $re)) {
                $value = $re[1];
            }
        }

        $types=null;
        $id=md5("$pageToPost,$t");
        $addPattrn = null;
        $this->search_box_function="Search$t";
        VERBOSE("search_block(): Type: $type...", __LINE__);
        $search_text=$this->_ENGINE_parse_body("{search}");

        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $sbcss=$this->search_block_css();
        $html[]=$sbcss."<div class=\"row sb-stitch\">
			<div class='ibox-content'>
				<div class=\"input-group\" style='$width'>
					<span class='sb-ico'><i class='fas fa-search'></i></span>
					<input type=\"text\" class=\"form-control\" id='$id' value=\"".req_html($value)."\" placeholder=\"$search_text\" OnKeyPress=\"Search$t(event);\">
					$DropDownFinal
					<span class=\"input-group-btn\">
						<button type=\"button\" class=\"btn sb-go\" OnClick=\"ss$t();\">Go!</button>
						$second_button
					</span>
				</div>
				<div id='results-$id' style='margin-top:12px'></div>
			</div></div>";


        $availableTags=array();
        if($table<>null) {
            $tags[] = "LIKE";
            $tags[] = "MAX";
            $tags[] = "AND";
            $tags[] = "=";
            $tags[] = ">";
            $tags[] = "< ";
            $tags[] = "IPADDRLIKE ";

            if ($type == "postgres") {
                $q = new postgres_sql();
                $tags = $q->FIELDS_LIST($table, $tags);

            }

            if (preg_match("#sqlite:(.+)$#", $type, $re)) {
                $addPattrn = "&QueryIsSqlite=yes";
                VERBOSE("sqlite database {$re[1]}...", __LINE__);
                $q = new lib_sqlite($re[1]);
                $tags = $q->FIELDS_LIST($table, $tags);
                $types=base64_encode(serialize($q->FIELDS_TYPES($table)));

            }

            if ($type == "mysql_green") {
                VERBOSE("mysql_green database...", __LINE__);
                $q = new green_sql();
                $tags = $q->FIELDS_LIST($table, $tags);
                $addPattrn = "&QueryIsMySQL=yes";

            }


            foreach ($tags as $line) {
                $availableTags[] = "\"$line\"";
            }
        }
        $VaravailableTags=@implode(",", $availableTags);
        $html[]="<script>";

        $html[]="$( function() {";
        $html[]="var availableTags = [$VaravailableTags];";
        $html[]="function split( val ) {";
        $html[]="return val.split( / / );";
        $html[]="}";
        $html[]="function extractLast( term ) {";
        $html[]="return split( term ).pop();";
        $html[]="}";
        $html[]="$( '#$id' )";
        $html[]=".on( 'keydown', function( event ) {";
        $html[]="if ( event.keyCode === $.ui.keyCode.TAB &&";
        $html[]="$( this ).autocomplete( 'instance' ).menu.active ) {";
        $html[]="event.preventDefault();";
        $html[]="}";
        $html[]="})";
        $html[]=".autocomplete({";
        $html[]="minLength: 0,";
        $html[]="source: function( request, response ) {";
        $html[]="// delegate back to autocomplete, but extract the last term";
        $html[]="response( $.ui.autocomplete.filter(";
        $html[]="availableTags, extractLast( request.term ) ) );";
        $html[]="},";
        $html[]="focus: function() {";
        $html[]="// prevent value inserted on focus";
        $html[]="return false;";
        $html[]="},";
        $html[]="select: function( event, ui ) {";
        $html[]="var terms = split( this.value );";
        $html[]="// remove the current input";
        $html[]="terms.pop();";
        $html[]="// add the selected item";
        $html[]="terms.push( ui.item.value );";
        $html[]="// add placeholder to get the comma-and-space at the end";
        $html[]="terms.push( ' ' );";
        $html[]="this.value = terms.join( ' ' );";
        $html[]="return false;";
        $html[]="}";
        $html[]="});";
        $html[]="} );";
        $html[]="function Search$t(e){";
        $html[]="\tif(!checkEnter(e) ){return false;}";
        $html[]="\tss$t();";
        $html[]="}";
        $html[]="";
        $html[]="function ss$t(){";
        // Le bloc peut avoir quitte le DOM avant l'appel : onglet vide par tabs_default(), page
        // reconstruite par un filtre d'en-tete, ou le Start$t() arme a 800 ms qui part apres un
        // changement rapide d'onglet. La fonction, elle, survit dans window : sans cette garde
        // chaque rappel tardif jette « Cannot read properties of null (reading 'value') ».
        $html[]="\tvar sbx$t=document.getElementById('$id');";
        $html[]="\tif(!sbx$t || !document.getElementById('results-$id')){ return; }";
        $html[]="\tvar ss=encodeURIComponent(sbx$t.value);";
        $html[]="\tLoadAjaxTiny('results-$id','$pageToPost?search='+ss+'$urlToadd&function=ss$t&$addPattrn&types=$types&div-id=results-$id');";
        $html[]="}";

        $html[]="function Start$t(){";
        $html[]="\tss$t();";
        $html[]="}";

        $html[]="setTimeout (\"Start$t()\", 800);";
        $html[]="</script>";
        return strval(@implode("\n", $html));

    }

    public function percentage_block($title=null,$percent=0,$subtext=null){
        $f[]="<div class=\"ibox-content\">";
        $f[]="\t<h5>{$title}</h5>";
        $f[]="\t<h2>{$percent}%</h2>";
        $f[]="\t<div class=\"progress progress-mini\">";
        $f[]="\t\t<div class=\"progress-bar\" style=\"width: {$percent}%;\"></div>";
        $f[]="\t</div>";
        $f[]="";
        $f[]="\t<div class=\"m-t-sm small\">$subtext</div>";
        $f[]="</div>";

        return @implode("\n",$f);
    }

    /** Pastille d'un lien de field_link() : "ico:fa-solid fa-x;Libelle" pose une icone. */
    private function afrm_link_button($text,$js):string{
        $ico="";
        if(preg_match('#^ico:([A-Za-z0-9 _-]+);(.*)$#s',(string)$text,$re)){
            $ico="<i class='".trim($re[1])."' aria-hidden='true'></i>";
            $text=$re[2];
        }
        return "<button type=\"button\" OnClick=\"$js\" class=\"afrm-lk\">$ico<span>$text</span><i class='fa-solid fa-chevron-right afrm-lk-chev' aria-hidden='true'></i></button>";
    }

    public function field_link($label=null,$explain=null,$js=null):string{

        $bnts=array();
        if(is_array($label)){

            foreach ($label as $index=>$text){
                $bnts[]=$this->afrm_link_button($text,$js[$index]);
            }

            if($this->FORM_IN_ARRAY){return "<td>".@implode("&nbsp;&nbsp;",$bnts)."</td>";}

            return "
		<tr class='afrm-row afrm-full'>
		<td colspan='2' style='padding-bottom:10px;text-align:right'><div class='afrm-lk-bar'>".@implode("",$bnts)."</div>
		</td>
		</tr>";

        }

        if($this->FORM_IN_ARRAY){return "<td>".$this->afrm_link_button($label,$js)."</td>";}

        $id=md5("$label,$explain,$js".time());
        return "
		<tr class='afrm-row afrm-full'>
		<td colspan='2' style='padding-bottom:10px;text-align:right'><div class='afrm-lk-bar' id='$id'>".$this->afrm_link_button($label,$js)."</div>
		</td>
		</tr>
		";
    }
    public function field_text_info_button($label,$value,$buttonName,$js){
        $id=md5("$label$value$buttonName$this->suffixid");
        $label=trim($label);
        $labelstr=strlen($label);
        $padding="padding-left:19px";
        $width_right="70%";
        if($label==null){
            $width_right="100%";
            $padding="padding-left:0";
            return " <!-- _field_intr 2 --><tr><td style='width:100%;padding-bottom:10px;$padding;'>$label</td></tr>";
        }

        $trace=@debug_backtrace();
        $last=$trace[1]["function"];

        $right[]="<table style='width:100%;padding:0;margin:0'>";
        $right[]="<tr>";
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $right[]="<td style='width:99%'><strong>".html_safe_fragment($value)."</strong></td>";

        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
        $bt_js="OnClick=\"$js\"";
        $button="<span class=\"label label-primary\" $pointers $bt_js>$buttonName</span>";
        $right[]="<td style='width:1%' nowrap>$button</td>";
        $right[]="</table>";
        $rightText=implode("",$right);
        return "
            <!-- _field_intr 3 -->
            <!-- Label: $labelstr $last -->
			<tr>
				<td style='width:30%;text-align:right;vertical-align:middle;padding-bottom:10px;' nowrap><span class=labelform id='$id' style='text-transform:capitalize !important'>$label</span></td>
				<td style='width:$width_right;padding-bottom:10px;$padding'>$rightText</td>
			</tr>
			 <!-- END Label: $labelstr -->";

    }
    public function field_email($name,$label,$value,$notnull=false,$explain=null,$disabled=false):string{
        $id=md5("$name$this->suffixid");

        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $F="<input type='email' name=\"$name\" placeholder='user@domain.tld' id='$id' class='form-control' value=\"".req_html($value)."\">";

        if($disabled){$notnull=false;}



        $this->XHR[]="var email=document.getElementById('$id').value;";


        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="if (!emailIsValid(email)) {  FieldHTMLError('$id');document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> Invalid email address</div>';return;}";
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);

    }
    public function field_button_upload($label=null,$field="file-uploaded",$bt_name=null,$explain=null):string{
        $page=CurrentPageName();
        $F=$this->button_upload($bt_name,$page,null,null,$field);
        return $this->_field_intr($label,$F,$explain);

    }



    public function field_button($label,$bt_name,$js,$explain=null):string{

        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
        $bt_js="OnClick=\"$js\"";
        $button="<span class=\"label label-primary\" $pointers $bt_js>$bt_name</span>";
        $F="
        <div class='input-group'>
       $button
        </div>";

        return $this->_field_intr($label,$F,$explain);

    }

    public function field_none_bt($name,$label,$value,$BtName,$js=null,$explain=null){
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        if($js<>null){$js="OnClick=\"$js\"";}

        $F="
        <div class='input-group'>
        <input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled>
        <span class='input-group-btn'>
        <button type='button' class='btn btn-primary' $js>$BtName</button>
        </span>
        </div>";

        return $this->_field_intr($label,$F,$explain);

    }
    public function field_ad_suffix_ad_conns($name,$cnxid,$label,$value,$notnull=false,$explain=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $labeljs=$this->javascript_parse_text($label_text);

        $js="Loadjs('fw.browse-suffix.php?js=yes&field-id=$id&title=$labeljs&suffixid=$this->suffixid&cnxid=$cnxid')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);


    }
    public function field_ad_suffix($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $labeljs=$this->javascript_parse_text($label_text);

        $js="Loadjs('fw.browse-suffix.php?js=yes&field-id=$id&title=$labeljs&suffixid=$this->suffixid')";
        $F="<div class='input-group'><input type='text' class='form-control' id='$id' value='".req_html($value)."' disabled>
		<span class='input-group-btn'> <button class='btn btn-primary' type='button' OnClick=\"$js\">{browse}...</button> </span>
		</div>";

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);


    }

    public function sshurl_toarray($string):array{
        $port       = 22;
        $username   = null;
        $password   = null;

        if($string==null){
            return array("HOST"=>null,"PORT"=>22,"USER"=>null,"PASS"=>null);
        }

        if(preg_match("#ssh:\/\/(.+?)@(.+?)$#",$string,$re)){
            $hostname=$re[2];
            $username=$re[1];

            if(strpos($username,":")>0){
                $tbhost=explode(":",$username);
                $username = $tbhost[0];
                $password = $tbhost[1];
            }


            if(strpos($hostname,":")>0){
                $tbhost=explode(":",$hostname);
                $hostname   = $tbhost[0];
                $port       = $tbhost[1];
            }

            return array("HOST"=>$hostname,"PORT"=>$port,"USER"=>$username,"PASS"=>$password);

        }

        if(preg_match("#ssh:\/\/(.+)$#",$string,$re)){
            $hostname=$re[1];
            if(strpos($hostname,":")>0){
                $tbhost=explode(":",$hostname);
                $hostname=$tbhost[0];
                $port=intval($tbhost[1]);
            }
            return array("HOST"=>$hostname,"PORT"=>$port,"USER"=>$username,"PASS"=>$password);
        }
        return array();
    }
    public function field_color($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false){
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        if($value<>null){
            $color_option="color: \"$value\",";
        }

        $F="<input type='text' name='$name' placeholder='{$label_text}' id='$id' class='form-control' value=\"".req_html($value)."\">";

        $this->JS[]="$(\"#$id\").colorpicker();";

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }
        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readOnly=true;";
            return $this->_field_intr($label,$F,$explain);
        }


        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }

    public function field_upload_image($name,$label,$value,$notnull=false,$explain=null){
        $id=md5("$name$label$explain"."$this->suffixid");
        $id2=md5("$name$label$explain$value"."$this->suffixid");
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $basename=basename($value);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $encoded_string=urlencode($value);
        $F="
       <div class='input-group'>
            <input type='hidden' id='$id' value='".req_html($value)."'>
            <input type='text' placeholder='' id='$id2' class='form-control' value=\"".req_html($basename)."\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('fw.image.validator.php?js=yes&id=$id&id2=$id2&path=$encoded_string');\">
                    <i class='fad fa-image'></i></button> 
            </span>
    </div>";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr("$label",$F,$explain);

    }

    /**
     * Champ texte accompagne d'un bouton d'upload.
     *
     * Upload: meme principe que button_upload() -> plugin jQuery fileupload qui poste
     * sur /fw.upload.php et depose le fichier dans ressources/conf/upload/.
     * A la difference de button_upload(), on ne rappelle PAS la page courante par
     * Loadjs(): le fichier renseigne directement le champ.
     *
     * Champ: meme comportement que field_upload_image() -> un input cache porte la
     * valeur reelle (chemin complet, c'est elle qui part dans le POST via XHR), un
     * input visible et desactive n'affiche que le basename.
     *
     * @param string $name    cle POST (recuperee cote handler dans $_POST[$name])
     * @param string $label   libelle, token {..} accepte
     * @param string $value   valeur initiale (chemin complet)
     * @param bool   $notnull bloque la soumission si aucun fichier n'est renseigne
     * @param string $explain texte d'aide (popover)
     */
    public function field_upload_file($name,$label,$value,$notnull=false,$explain=null){
        // Prefixes distincts, et surtout PAS $value dans la graine: sur un champ vide
        // "$name$label$explain" et "$name$label$explain$value" donnent le meme md5,
        // les deux <input> partagent alors le meme id et getElementById() ne rend que
        // le premier (le champ cache) -> l'affichage ne se met jamais a jour.
        $id=md5("hidden-$name$label$explain"."$this->suffixid");
        $id2=md5("visible-$name$label$explain"."$this->suffixid");
        $btid=md5("upload-$name$label$explain"."$this->suffixid");
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n","<br>",$label_text);
        $basename=basename($value);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $btname=$this->javascript_parse_text("{upload_a_file}");
        $Uploading=$this->javascript_parse_text("{uploading}");
        $UploadFailed=$this->javascript_parse_text("{failed}");
        // Repertoire de depot de class.upload.handler.inc ('upload_dir' => ressources/conf/upload/).
        $uploaddir="/usr/share/artica-postfix/ressources/conf/upload/";

        $F="
       <div class='input-group'>
            <input type='hidden' id='$id' value=\"".req_html($value)."\">
            <input type='text' placeholder='{$label_text}' id='$id2' class='form-control' value=\"".req_html($basename)."\" disabled>
            <span class=\"input-group-btn\">
                <span class=\"btn btn-primary fileinput-button\">
                    <i class='fa fa-upload'></i>&nbsp;<span id='$btid'>$btname</span>
                    <input id=\"fileupload$btid\" type=\"file\" name=\"files\">
                </span>
            </span>
    </div>
    <script>
    $('#fileupload$btid').fileupload({
        url: '/fw.upload.php',
        dataType: 'json',
        add: function (e, data) {
            document.getElementById('$btid').innerHTML='$Uploading....';
            data.submit();
        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            document.getElementById('$btid').innerHTML='$Uploading '+progress+'%';
        },
        done: function (e, data) {
            document.getElementById('$btid').innerHTML='$btname';
            var uploaded=(data.result && data.result.files) ? data.result.files[0] : null;
            if(uploaded==null || uploaded.error){
                swal({title:'$UploadFailed',text:(uploaded && uploaded.error)?uploaded.error:'$UploadFailed',type:'error',confirmButtonColor:'#ed5565'});
                return;
            }
            var uploadedfile=uploaded.name;
            document.getElementById('$id').value='$uploaddir'+uploadedfile;
            document.getElementById('$id2').value=uploadedfile;
        }
    });
    </script>";

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            // FieldHTMLError vise l'input VISIBLE: le champ cache ne peut pas etre surligne.
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id2'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);

    }

    public function field_ftp_params($array){
        $id=md5(serialize($array)."$this->suffixid");
        $label_text=$this->javascript_parse_text("{ftp_server}");
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $encoded_string=urlencode(base64_encode(serialize($array)));
        $F="
       <div class='input-group'>
            <input type='text' placeholder='{$label_text}' id='$id' class='form-control' value=\"".req_html($array["FTP_SERVER"]["VALUE"]??"")."\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('fw.ftp.validator.php?js=yes&id=$id&data=$encoded_string');\">
                    <i class='fas fa-cogs'></i></button> 
            </span>
    </div>";

        return $this->_field_intr("{ftp_server}",$F,null);

    }

    private function field_text_button_internal($name,$label,$value,$notnull=false,$explain=null,$text_button=null,$Loadjs=null,$prfixicon=null){
        $id=md5("$name$this->suffixid");
        $button_text="{change}";
        $rpfx="";
        if($text_button<>null){
            $button_text=$text_button;
        }
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $valueEnc=urlencode($value);

        if ($prfixicon<>null){
            $rpfx="<i style='color:white' class='$prfixicon'></i>&nbsp;";
        }
        $F="
       <div class='input-group'>
            <input type='text' name='$name' placeholder='{$label_text}' id='$id' class='form-control' value=\"".req_html($value)."\" OnClick=\"this.disabled=true;\" OnMouseOver=\"this.disabled=true;\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('$Loadjs&id=$id&value=$valueEnc&suffix-form=$this->suffixid');\">
                   $rpfx$button_text</button> 
            </span>
    </div>";



        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }
        $this->FormStartupScripts[] = "document.getElementById('$id').disabled=true;";
        $this->FormStartupScripts[] = "document.getElementById('$id').readOnly=true;";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }
    private function field_text_button_certificateid($name,$label,$value,$notnull=false,$explain=null,$text_button=null,$Loadjs=null,$prfixicon=null){
        $id=md5("$name$this->suffixid");
        $button_text="{change}";

        if($text_button<>null){
            $button_text=$text_button;
        }
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $valueEnc="";
        if(!is_null($value)) {
            $valueEnc = urlencode($value);
        }
        $rpfx="";
        if ($prfixicon<>null){
            $rpfx="<i style='color:white' class='$prfixicon'></i>&nbsp;";
        }
        $text_nic="{none}";
        if(!is_null($value)) {
            if (strlen($value) > 1) {
                include_once(dirname(__FILE__) . "/class.squid.reverse.inc");
                $squid_reverse = new squid_reverse();
                $sslcertificates = $squid_reverse->ssl_certificates_list();
                $text_nic = $sslcertificates[$value];
            }
        }
        $style="style='width:100%;text-align:left'";
        $Click="OnClick=\"Loadjs('$Loadjs&id=$id&value=$valueEnc&suffix-form=$this->suffixid');\"";
        $F="
       <div class='input-group'>
            <span class='input-group-btn'><button type=\"button\" $style $Click class=\"btn btn-default font-bold\" id='btnlabel-$id'>".req_html($text_nic)."</button></span>
            <input type='hidden' name='$name' placeholder='$label_text' id='$id' value=\"".req_html($value)."\">
            <span class=\"input-group-btn\"> 
                <button type=\"button\" class=\"btn btn-primary\" $Click\">$rpfx$button_text</button> 
            </span>
    </div>";



        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }
        $this->FormStartupScripts[] = "document.getElementById('$id').disabled=true;";
        $this->FormStartupScripts[] = "document.getElementById('$id').readOnly=true;";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }
    private function field_text_button_Interface($name,$label,$value,$notnull=false,$explain=null,$text_button=null,$Loadjs=null,$prfixicon=null){
        $id=md5("$name$this->suffixid");
        $button_text="{change}";

        if($text_button<>null){
            $button_text=$text_button;
        }
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $valueEnc=urlencode($value);
        $rpfx="";
        if ($prfixicon<>null){
            $rpfx="<i style='color:white' class='$prfixicon'></i>&nbsp;";
        }
        $text_nic="{default}";
        if(strlen($value)>1){
            $nic=new system_nic($value);
            $text_nic=$nic->NICNAME." (".$nic->IPADDR.")";
        }
        $style="style='min-width:175px;text-align:left'";
        $Click="OnClick=\"this.style.background='#FFFFFF'\"";
        $F="
       <div class='input-group'>
            <span class='input-group-btn'><button type=\"button\" $style $Click class=\"btn btn-default\" id='btnlabel-$id'>".req_html($text_nic)."</button></span>
            <input type='text' name='$name' placeholder='$label_text' id='$id' class='form-control' value=\"".req_html($value)."\" OnClick=\"this.disabled=true;\" OnMouseOver=\"this.disabled=true;\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('$Loadjs&id=$id&value=$valueEnc&suffix-form=$this->suffixid');\">
                   $rpfx$button_text</button> 
            </span>
    </div>";



        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }
         $this->FormStartupScripts[] = "document.getElementById('$id').disabled=true;";
         $this->FormStartupScripts[] = "document.getElementById('$id').readOnly=true;";
         $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }

    public function field_text_button($name,$label,$value,$notnull=false,$explain=null,$text_button=null){
        $id=md5("$name$this->suffixid");
        $page=CurrentPageName();
        $button_text="{change}";
        $LOCK=true;
        if($text_button<>null){
            $button_text=$text_button;
            $LOCK=false;
        }
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $valueEnc=urlencode($value);
        $F="
       <div class='input-group'>
            <input type='text' name='$name' placeholder='$label_text' id='$id' class='form-control' value=\"".req_html($value)."\" disabled>
            <span class=\"input-group-btn\"> 
                <button type=\"button\" 
                    class=\"btn btn-primary\"
                    OnClick=\"Loadjs('$page?$name-js=yes&id=$id&value=$valueEnc&suffix-form=$this->suffixid');\">
                   $button_text</button> 
            </span>
    </div>";



        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($LOCK) {
            $this->FormStartupScripts[] = "document.getElementById('$id').disabled=true;";
            $this->FormStartupScripts[] = "document.getElementById('$id').readOnly=true;";
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }


    public function field_tags($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $typeField="input type='text'";
        $option_value=null;

        if(preg_match("#^force:(.+)#",$explain,$re)){
            $tb=explode(",",$re[1]);
            $option_values=array();
            $explain="";
            $typeField="select multiple";
            foreach ($tb as $zval){
                if(trim($zval)==null){continue;}
                $option_values[]="<option value=\"".req_html($zval)."\">".req_html($zval)."</option>";
            }
            $option_value=@implode("\n",$option_values)."\n</select>";
        }


        $F="<$typeField name='$name' placeholder='$label_text' id='$id' class='form-control' value=\"".req_html($value)."\" data-role='tagsinput' >$option_value
        <script>
        $('#$id').tagsinput({ tagClass: 'label label-primary',trimValue: true });
        </script>";

        if(preg_match("#url:(.+?);(.+)#",$explain,$re)){
            $explain=$re[2];
            $jsbut="s_PopUpFull('{$re[1]}','1024','900')";
            $F="<div class='input-group'>$F<span class='input-group-btn'><button class='btn btn-default' type='button' OnClick=\"$jsbut\"><i class='fas fa-info-circle'></i></button></span></div><script>
        $('#$id').tagsinput({ tagClass: 'label label-primary',trimValue: true });
        </script>";
        }

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=$('#$id').val();";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }
        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readOnly=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent($('#$id').val()));";
        return $this->_field_intr($label,$F,$explain);
    }


    public function field_daterange($name,$label,$value,$MinDate,$MaxDate):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if(!preg_match("#^([0-9]+)-([0-9]+)-([0-9]+) ([0-9]+):([0-9]+)#",$value)){
        $value=date("Y-m-d H:i:00",strtotime('yesterday')) ." - ".date("Y-m-d H:i:00");
        }

        if(preg_match("#^(.+?)\s+([0-9:]+)\s+-\s+(.+?)\s+([0-9:]+)#",$value,$re)){
            $StartDate="$re[1] $re[2]";
            $EndDate="$re[3] $re[4]";
        }

        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $F="<input type='text' name='$name' id='$id' class='form-control' value=\"".req_html($value)."\">";
        $this->JS_FUNCs[] = " // Date Range picker for $id default: ".req_js($value)."\n";
        $this->JS_FUNCs[] = "$('#$id').daterangepicker({";
        // Top-level "format" is required by daterangepicker 1.3.x (it ignores locale.format);
        // locale.format is required by 3.1.x. Emit both so the dates parse whichever build is
        // loaded -- otherwise the picker falls back to its MM/DD/YYYY default and every
        // YYYY-MM-DD value parses to "Invalid date" (NaN calendar). Use HH (24h) to match the
        // PHP-supplied date("Y-m-d H:i:00") values.
        $this->JS_FUNCs[] = "\tformat: 'YYYY-MM-DD HH:mm',";
        $this->JS_FUNCs[] = "\ttimePicker: true,";
        $this->JS_FUNCs[] = "\ttimePicker24Hour: true,";
        $this->JS_FUNCs[] = "\ttimePicker12Hour: false,";
        $this->JS_FUNCs[] = "\tautoUpdateInput: true,";
        $this->JS_FUNCs[] = "\tstartDate: '".req_js($StartDate)."',";
        $this->JS_FUNCs[] = "\tendDate: '".req_js($EndDate)."',";
        $this->JS_FUNCs[] = "\tminDate: '".req_js($MinDate)."',";
        $this->JS_FUNCs[] = "\tmaxDate: '".req_js($MaxDate)."',";
        $this->JS_FUNCs[] = "\tlocale: {";
        $this->JS_FUNCs[] = "\t\t\tformat: 'YYYY-MM-DD HH:mm'";
        $this->JS_FUNCs[] = "\t}";
        $this->JS_FUNCs[] = "});";


        //$this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
        $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
        return $this->_field_intr($label,$F,null);
    }
    /**
     * Selection multiple, rendue par chosen -- le meme habillage que
     * field_array_hash juste a cote dans le meme formulaire. Un <select multiple>
     * nu obligeait a du Ctrl+clic et ne ressemblait a aucun autre champ.
     *
     * La valeur part en liste separee par des virgules. NE JAMAIS y remettre
     * d'encodeURIComponent(): XHRConnection.appendData() applique deja escape(),
     * qui encode la virgule en %2C. Le double encodage faisait arriver la chaine
     * "1%2C2%2C3" cote PHP -- une chaine SANS virgule, donc explode(",") ne rendait
     * qu'un seul element et toute selection multiple etait silencieusement reduite
     * a son premier element, sans la moindre erreur nulle part.
     */
    public function field_multiselect($Hash,$name,$label,$selectedIDs=array(),$explain=null):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if(!is_array($Hash)){$Hash=array();}
        if(!is_array($selectedIDs)){$selectedIDs=array();}
        $label_text=$this->javascript_parse_text($label);
        $h=array();
        $opts=array();
        foreach($Hash as $ID=>$text){
            if(strpos($text,"}")>0){$text=$this->_ENGINE_parse_body($text);}
            $sel = isset($selectedIDs[$ID]) ? " selected" : "";
            // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
            $opts[]="\t<option value=\"".req_html($ID)."\"$sel>".req_option($text)."</option>";
        }

        $h[]="<select data-placeholder=\"$label_text...\" class=\"chosen-select\" id='$id' multiple=\"multiple\" tabindex=\"2\">";
        $h[]=@implode("\n", $opts);
        $h[]="</select>";
        if(!is_null($explain)){
            $h[]="<small class='help-block' style='color:#999'>$explain</small>";
        }
        // Meme amorce que field_array_hash: chosen mesure la largeur de son
        // conteneur, il ne peut donc pas s'initialiser avant que l'animation de la
        // fenetre modale soit terminee.
        $h[]="<script>";
        $h[]="function Choosen$id(){ $('#$id').chosen({width: \"99%\"}); }";
        $h[]="setTimeout (\"Choosen$id()\", 1000);";
        $h[]="</script>";
        $this->CHOSENS[]=$id;
        $F=@implode("\n",$h);
        $this->XHR[]="var F$id=document.getElementById('$id');";
        $this->XHR[]="if(F$id){";
        $this->XHR[]="\tvar ids=[];";
        $this->XHR[]="\tfor(var i=0;i<F$id.options.length;i++){ if(F$id.options[i].selected){ ids.push(F$id.options[i].value); } }";
        $this->XHR[]="\tXHR.appendData('$name',  ids.join(','));";
        $this->XHR[]="\t}";
        return $this->_field_intr($label,$F,$explain);
    }
    
    public function field_text($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if(!is_null($value)) {
            // Pentest 2026-11-04 : req_html() ne vide pas une valeur Latin-1.
            $value = req_html($value);
        }
        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $F="<input type='text' name='$name' placeholder='{$label_text}' id='$id' class='form-control' value=\"$value\">";

        if(!is_null($explain)) {
            if (preg_match("#url:(.+?);(.*)#", $explain, $re)) {
                $explain = $re[2];
                $jsbut = "s_PopUpFull('{$re[1]}','1024','900')";
                $F = "<div class='input-group'>$F<span class='input-group-btn'><button class='btn btn-default' type='button' OnClick=\"$jsbut\"><i class='fas fa-info-circle'></i></button></span></div>";
            }
        }



        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }
        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readOnly=true;";
            return $this->_field_intr($label,$F,$explain);
        }


        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }

    public function field_text_big($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false):string{
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $this->FormStartupScripts[]="$(document).ready(function(){";
        $this->FormStartupScripts[]="\tvar field$id = document.getElementById('$id');";
        $this->FormStartupScripts[]="\tif (field$id) {";
        $this->FormStartupScripts[]="\t\tsetTimeout(function() {";
        $this->FormStartupScripts[]="\t\t\tfield$id.focus();";
        $this->FormStartupScripts[]="\t\t},800);";
        $this->FormStartupScripts[]="\t}";
        $this->FormStartupScripts[]="});";

        $label_text=$this->javascript_parse_text($label);
        $label_text=str_ireplace("<small>","",$label_text);
        $label_text=str_ireplace("</small>","",$label_text);
        $label_text=str_replace("\n", "<br>", $label_text);
        $F="<input type='text' placeholder='$label_text...' class='PostForm' id='$id' 
        style='width: 99%; padding: 20px; font-size: 2.5em; border: 2px solid #007445; 
        border-radius: 8px; color: #333; box-sizing: border-box;' value=\"".req_html($value)."\">";

        if(!is_null($explain)) {

            if (preg_match("#url:(.+?);(.*)#", $explain, $re)) {
                $explain = $re[2];
                $jsbut = "s_PopUpFull('$re[1]','1024','900')";
                $F = "<div class='input-group'>$F<span class='input-group-btn'><button class='btn btn-default' type='button' OnClick=\"$jsbut\"><i class='fas fa-info-circle'></i></button></span></div>";
            }else{
                $F="$F<div style='margin-top:10px;margin-bottom:10px;font-size:11px;color:#333'>$explain</div>";
            }
        }
        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_text</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr("",$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr("",$F,$explain);
        }
        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readOnly=true;";
            return $this->_field_intr("",$F,$explain);
        }


        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr("",$F,$explain);
    }

    public function field_ipv4($name,$label,$value,$notnull=false,$explain=null,$disabled=false):string{
        return $this->field_ipaddr($name,$label,$value,$notnull,$explain,$disabled);
    }


    public function field_MacAddress($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$readonly=false){
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $F="
		<input type=\"text\" id='$id'  placeholder=\"00:0c:29:7f:c3:bb\" class=\"form-control afrm-mono\" value=\"".req_html($value)."\">
		<script>$('#$id').setMask('**:**:**:**:**:**').val('".req_js($value)."');</script>";

        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        if($readonly){
            $this->FormStartupScripts[]="document.getElementById('$id').readonly=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }

    public function field_cdir($name,$label,$value,$notnull=false,$explain=null,$disabled=false):string{

        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $F="
		<input type=\"text\" id='$id'  placeholder=\"0.0.0.0/24\" class=\"form-control afrm-mono\" value=\"".req_html($value)."\">
		<script>$('#$id').mask('099.099.099.099/09');</script>";
        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }
    public function field_password2($name,$label,$value=null,$notnull=false,$explain=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $id2=md5("2$name$this->suffixid");

        if(!isset($GLOBALS["EnableShowPasswords"])){
            $GLOBALS["EnableShowPasswords"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableShowPasswords"));
        }

        $users=new usersMenus();
        if(!$users->AsAnAdministratorGeneric){
            $GLOBALS["EnableShowPasswords"]=0;
        }



        $view_pass1="<span class=\"input-group-btn\"> 
        <button type=\"button\" class=\"btn btn\" OnClick=\"my{$id}Passwitch('$id')\">
            <i class=\"fas fa-eye\" id='passhide{$id}'></i>
        </button> 
        </span>";

        $view_pass2="<span class=\"input-group-btn\"> 
        <button type=\"button\" class=\"btn btn\" OnClick=\"my{$id}Passwitch('$id2')\">
            <i class=\"fas fa-eye\" id='passhide{$id2}'></i>
        </button> 
        </span>";

        if($GLOBALS["EnableShowPasswords"]==0){
            $view_pass1=null;
            $view_pass2=null;
        }

        $maskedValue=($value!==null && strlen($value)>0) ? '%%MASKED%%' : '';
        $onblur=($maskedValue==='%%MASKED%%') ? " onblur=\"if(this.value===''){this.value='%%MASKED%%';}\"" : '';
        $F="<div class=\"input-group\"><input type='password' id='$id' class='form-control' value='$maskedValue' style='font-size:14px' autocomplete='new-password' onfocus=\"if(this.value==='%%MASKED%%'){this.value='';}\"$onblur>$view_pass1</div>";
        $F2="<div class=\"input-group\"><input type='password' id='$id2' class='form-control' value='$maskedValue' style='font-size:14px' autocomplete='new-password' onfocus=\"if(this.value==='%%MASKED%%'){this.value='';}\"$onblur>$view_pass2</div>";

        $NoSpinner="NoSpinner()";

        if($GLOBALS["EnableShowPasswords"]==1) {
            $this->JS_FUNCs[] = " // Password displays\n";
            $this->JS_FUNCs[] = "function my{$id}Passwitch(id) {";
            $this->JS_FUNCs[] = "\tvar x = document.getElementById(id);";
            $this->JS_FUNCs[] = "\tif (x.type === 'password') {";
            $this->JS_FUNCs[] = "\t\tx.type = 'text';";
            $this->JS_FUNCs[] = "\t\t$('#passhide'+id).toggleClass(\"fas fa-eye-slash\");";
            $this->JS_FUNCs[] = "\t} else {";
            $this->JS_FUNCs[] = "\t\tx.type = 'password';";
            $this->JS_FUNCs[] = "\t\t$('#passhide'+id).toggleClass(\"fas fa-eye\");";
            $this->JS_FUNCs[] = "\t}";
            $this->JS_FUNCs[] = "}";
        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id==='%%MASKED%%'){F$id='';}";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";

            $this->XHR[]="var aF$id=F$id;";
            $this->XHR[]="var bF$id=document.getElementById('$id2').value;";
            $this->XHR[]="if(bF$id==='%%MASKED%%'){bF$id='';}";
            $this->XHR[]="if ( aF$id.length > 0 ){";
            $this->XHR[]="\tif( aF$id.length < 5 ){ $NoSpinner;alert('Password is too short (minimum five characters)!');return;}";
            $this->XHR[]="\tif( aF$id == bF$id ){ }else{ $NoSpinner;alert('Passwords Do Not Match!');return;}";
            $this->XHR[]="}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            $tt[]=$this->_field_intr($label,$F,$explain);
            $tt[]=$this->_field_intr("{confirm_password}",$F2); // libelle propre : "&nbsp;" en faisait une ligne pleine largeur, muette
            return @implode("\n", $tt);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="var aF$id=document.getElementById('$id').value;";
        $this->XHR[]="var bF$id=document.getElementById('$id2').value;";
        $this->XHR[]="if(aF$id==='%%MASKED%%'){aF$id='';}";
        $this->XHR[]="if(bF$id==='%%MASKED%%'){bF$id='';}";
        $this->XHR[]="if( aF$id.length > 0 ){";
        $this->XHR[]="\tif( aF$id.length < 5 ){ $NoSpinner;alert('Password is too short (minimum five characters)!');return;}";
        $this->XHR[]="\tif( aF$id == bF$id ){ }else{ $NoSpinner;alert('Passwords Do Not Match!');return;}";
        $this->XHR[]="}";
        $this->XHR[]="if(aF$id!==''){XHR.appendData('$name',  encodeURIComponent(aF$id));}";
        $tt[]=$this->_field_intr($label,$F,$explain);
        $tt[]=$this->_field_intr("{confirm_password}",$F2); // libelle propre : "&nbsp;" en faisait une ligne pleine largeur, muette
        return @implode("\n", $tt);

    }
    public function field_password($name,$label,$value=null,$notnull=false,$explain=null,$disabled=false):string{

        if(!isset($GLOBALS["EnableShowPasswords"])){
            $GLOBALS["EnableShowPasswords"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableShowPasswords"));
        }

        $users=new usersMenus();
        if(!$users->AsAnAdministratorGeneric){
            $GLOBALS["EnableShowPasswords"]=0;
        }


        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;

        $view_pass1="<span class=\"input-group-btn\"> 
        <button type=\"button\" class=\"btn btn\" OnClick=\"my{$id}Passwitch('$id')\">
            <i class=\"fas fa-eye\" id='passhide{$id}'></i>
        </button> 
        </span>";
        if($GLOBALS["EnableShowPasswords"]==1) {
            $this->JS[] = "
			function my{$id}Passwitch(id) {
                var x = document.getElementById(id);
                if (x.type === 'password') {
                    x.type = 'text';
                    $('#passhide'+id).toggleClass(\"fas fa-eye-slash\");
                } else {
                    x.type = 'password';
                     $('#passhide'+id).toggleClass(\"fas fa-eye\");
                }
            }";
        }


        if($GLOBALS["EnableShowPasswords"]==0){$view_pass1=null;}


        $maskedValue=($value!==null && strlen($value)>0) ? '%%MASKED%%' : '';
        $onblur=($maskedValue==='%%MASKED%%') ? " onblur=\"if(this.value===''){this.value='%%MASKED%%';}\"" : '';
        $F="<div class=\"input-group\">
            <input type='password' name='{$name}' placeholder='{$label}' id='$id' class='form-control' value='$maskedValue' style='font-size:14px' autocomplete='new-password' onfocus=\"if(this.value==='%%MASKED%%'){this.value='';}\"$onblur>
            $view_pass1
            </div>";

        if($disabled){$notnull=false;$view_pass1=null;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id==='%%MASKED%%'){F$id='';}";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F,$explain);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="var F$id=document.getElementById('$id').value;";
        $this->XHR[]="if(F$id!=='%%MASKED%%'){XHR.appendData('$name',  encodeURIComponent(F$id));}";

        return $this->_field_intr($label,$F,$explain);


    }
    public function field_maskcdir($name,$label,$value,$notnull=false,$explain=null,$disabled=false){
        $MASK["32"]="255.255.255.255 (/32)";
        $MASK["31"]="255.255.255.254 (/31)";
        $MASK["30"]="255.255.255.252 (/30)";
        $MASK["29"]="255.255.255.248 (/29)";
        $MASK["28"]="255.255.255.240 (/28)";
        $MASK["27"]="255.255.255.224 (/27)";
        $MASK["26"]="255.255.255.192 (/26)";
        $MASK["25"]="255.255.255.128 (/25)";
        $MASK["24"]="255.255.255.0 (/24)";
        $MASK["23"]="255.255.254.0 (/23)";
        $MASK["22"]="255.255.252.0 (/22)";
        $MASK["21"]="255.255.248.0 (/21)";
        $MASK["20"]="255.255.240.0 (/20)";
        $MASK["19"]="255.255.224.0 (/19)";
        $MASK["18"]="255.255.192.0 (/18)";
        $MASK["17"]="255.255.128.0 (/17)";
        $MASK["16"]="255.255.0.0 (/16)";
        $MASK["15"]="255.254.0.0 (/15)";
        $MASK["14"]="255.252.0.0 (/14)";
        $MASK["13"]="255.248.0.0 (/13)";
        $MASK["12"]="255.240.0.0 (/12)";
        $MASK["11"]="255.224.0.0 (/11)";
        $MASK["10"]="255.192.0.0 (/10)";
        $MASK["9"]="255.128.0.0 (/9)";
        $MASK["8"]="255.0.0.0 (/8)";
        $MASK["7"]="254.0.0.0 (/7)";
        $MASK["6"]="252.0.0.0 (/6)";
        $MASK["5"]="248.0.0.0 (/5)";
        $MASK["4"]="240.0.0.0 (/4)";
        $MASK["3"]="224.0.0.0 (/3)";
        $MASK["2"]="192.0.0.0 (/2)";
        $MASK["1"]="128.0.0.0 (/1)";

        return $this->field_array_hash($MASK, $name, $label, $value,$explain);
    }
    public function field_text_online($name,$placeholder="0.0.0.0",$value=null,$width=null,$Loadjs=null){
        if(!is_null($placeholder)){
            $placeholder=$this->_ENGINE_parse_body($placeholder);
        }
        $width_text="";
        if(!is_null($width)){
            $width_text="style='width:{$width}px;'";
        }


        $t=md5(microtime(true).$name);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $f[]="<div class=\"btn-group\">";
        $f[]="<input type=\"text\" id=\"$name\" placeholder='$placeholder' name=\"$name\" class=\"form-control\" value=\"".req_html($value)."\" $width_text>";
        $f[]="</div>";
        
        $f[]="<script>";
        $f[]="function handleEnterKeyPress$t(event){";
        $f[]="alert(event.key);";
        $f[]="if (event.key === \"Enter\") { ";
        $f[]="alert('enterKey');";
        $f[]="}";
        $f[]="}";
        $f[]=" $( '#$name' ).on( 'keydown', function( event ) {
        if ( event.keyCode === 13 ) {
            value=encodeURIComponent(document.getElementById('$name').value);";
        if(!is_null($Loadjs)){
            $f[]="let js='".js_url_safe($Loadjs)."';";
            $f[]="let uri = js.replace(\"%s\", value);";
            $f[]="Loadjs(uri)";
        }
        $f[]="}";
        $f[]="})";
        $f[]="</script>";
        
        return @implode("",$f);
    }
    public function field_ipaddr($name,$label,$value,$notnull=false,$explain=null,$disabled=false,$addjs=null):string{
        $id=md5("$name$this->suffixid");
        $button2=null;$style=null;
        $big=false;
        if($addjs<>null){
            $button2 ="<button class='btn' type='button' OnClick=\"$addjs\" >
            <span class='fas fa-plus-square'></span>&nbsp;</button>";

        }

        if(preg_match("#^big:(.*)#",$label,$re)){
            $big=true;
            $label=$re[1];

        }

        if($big){
            $style=" style='font-size:40px;padding:40px'";
        }

        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $F="
		      <div class='input-group'>
                <input type=\"text\" id=\"$id\" placeholder='0.0.0.0' name=\"$id\" class=\"form-control afrm-mono\" value=\"".req_html($value)."\"$style>
                <span class='input-group-btn'>
                   $button2
                </span>
            </div>	
            <script>  
            $(document).ready(function () { 
                var ipv4_address = $('#ipv4');
          	    ipv4_address.inputmask({alias: \"ip\",\"placeholder\": \"\" });
            }); 
            
            </script>
		";
        if($disabled){$notnull=false;}

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="var F$id=document.getElementById('$id').value;";
            $this->XHR[]="if(F$id.length==0){ FieldHTMLError('$id'); document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
            $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(F$id));";
            return $this->_field_intr($label,$F);
        }

        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$F,$explain);
        }

        $this->XHR[]="XHR.appendData('$name',  encodeURIComponent(document.getElementById('$id').value));";
        return $this->_field_intr($label,$F,$explain);
    }
    public function field_div($html){
        return "<tr>
		<td colspan=2>$html</td>
		</tr>
		";

    }
    /**
     * Palette d'un bandeau, declinee par gravite sur un seul systeme tonal
     * (maquette "yellow warning widget revision"): lisere plein a gauche, lavis
     * tres clair degrade vers le blanc, pastille d'icone, texte sombre de la
     * meme famille chromatique.
     *
     * Le rouge #d92b32 et le vert #1ab394 sont ceux deja unifies ailleurs dans la
     * console (tuiles de service) plutot que les teintes brutes d'INSPINIA.
     */
    private function div_banner_palette($kind):array{
        if($kind=="error"){
            return array(
                "bar"=>"#d92b32","wash"=>"rgba(254,242,242,.95)","wash2"=>"rgba(254,242,242,.60)",
                "border"=>"rgba(252,165,165,.80)","chipbg"=>"rgba(254,226,226,.90)","chipbd"=>"#fecaca",
                "ico"=>"#b91c1c","title"=>"#450a0a","text"=>"#7f1d1d",
                "pillbg"=>"rgba(254,202,202,.55)","pillbd"=>"#fca5a5");
        }
        if($kind=="success"){
            return array(
                "bar"=>"#1ab394","wash"=>"rgba(236,253,245,.95)","wash2"=>"rgba(236,253,245,.60)",
                "border"=>"rgba(110,231,183,.80)","chipbg"=>"rgba(209,250,229,.90)","chipbd"=>"#a7f3d0",
                "ico"=>"#047857","title"=>"#022c22","text"=>"#065f46",
                "pillbg"=>"rgba(167,243,208,.55)","pillbd"=>"#6ee7b7");
        }
        if($kind=="info"){
            return array(
                "bar"=>"#2bc9c9","wash"=>"rgba(236,254,255,.95)","wash2"=>"rgba(236,254,255,.60)",
                "border"=>"rgba(103,232,249,.80)","chipbg"=>"rgba(207,250,254,.90)","chipbd"=>"#a5f3fc",
                "ico"=>"#0e7490","title"=>"#083344","text"=>"#155e75",
                "pillbg"=>"rgba(165,243,252,.55)","pillbd"=>"#67e8f9");
        }
        // "explain" est le seul neutre: c'est un bloc pedagogique en tete de page,
        // pas une alerte. Une couleur de gravite y crierait sans raison.
        if($kind=="explain"){
            return array(
                "bar"=>"#6b7280","wash"=>"rgba(243,244,246,.95)","wash2"=>"rgba(243,244,246,.60)",
                "border"=>"rgba(209,213,219,.90)","chipbg"=>"rgba(229,231,235,.90)","chipbd"=>"#d1d5db",
                "ico"=>"#4b5563","title"=>"#111827","text"=>"#374151",
                "pillbg"=>"rgba(229,231,235,.70)","pillbd"=>"#d1d5db");
        }
        return array(
            "bar"=>"#f59e0b","wash"=>"rgba(255,251,235,.95)","wash2"=>"rgba(255,251,235,.60)",
            "border"=>"rgba(252,211,77,.80)","chipbg"=>"rgba(254,243,199,.90)","chipbd"=>"#fde68a",
            "ico"=>"#b45309","title"=>"#451a03","text"=>"#78350f",
            "pillbg"=>"rgba(253,230,138,.55)","pillbd"=>"#fcd34d");
    }

    /**
     * Styles du bandeau. Volontairement INLINE et jamais dans un <style>: les trois
     * appelants publics totalisent ~1850 sites, dont une bonne part repassent le
     * retour dans _ENGINE_parse_body(), et le regex #{(.+?)}# du moteur prendrait
     * alors chaque corps de regle CSS pour un {token} a resoudre. Un style inline
     * traverse le moteur intact, quel que soit le nombre de passages.
     */
    private function div_banner_styles($kind,$nomargin=false):array{
        $c=$this->div_banner_palette($kind);
        $margin="margin:10px;";
        if($nomargin){$margin="margin:0;";}
        $s=array();
        $s["box"]="position:relative;overflow:hidden;{$margin}border-radius:12px;"
            ."border:1px solid {$c["border"]};"
            ."background:linear-gradient(90deg,{$c["wash"]} 0%,{$c["wash2"]} 55%,#ffffff 100%);"
            ."box-shadow:0 1px 2px rgba(0,0,0,.05)";
        // Le lisere porte la couleur pleine: le lavis est trop clair pour signaler
        // la gravite a lui seul, surtout sur les bandeaux d'une seule ligne.
        $s["bar"]="position:absolute;left:0;top:0;bottom:0;width:6px;background:{$c["bar"]}";
        $s["row"]="display:flex;flex-wrap:wrap;align-items:center;"
            ."justify-content:space-between;gap:16px;padding:15px 20px 15px 26px";
        // min-width:0 sinon un mot insecable (URL, chemin, message du daemon)
        // empeche l'element flex de retrecir et fait deborder le bandeau.
        $s["left"]="display:flex;align-items:flex-start;gap:14px;flex:1 1 340px;min-width:0";
        $s["chip"]="flex:0 0 auto;padding:8px;border-radius:8px;line-height:0;"
            ."background:{$c["chipbg"]};border:1px solid {$c["chipbd"]}";
        // Font Awesome tourne en mode SVG+JS mais recopie l'attribut style sur le
        // <svg> genere: la taille posee ici survit au remplacement, alors qu'un
        // selecteur descendant (.chip i{font-size:20px}) ne matcherait plus rien.
        $s["ico"]="font-size:20px;line-height:1;color:{$c["ico"]}";
        $s["head"]="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:3px";
        // <div> plutot que <h2>: le theme INSPINIA habille lourdement les titres
        // dans un .panel/.well, et ces bandeaux vivent un peu partout.
        $s["title"]="font-size:13px;font-weight:700;line-height:1.35;color:{$c["title"]}";
        $s["pill"]="display:inline-block;padding:1px 9px;border-radius:999px;font-size:11px;"
            ."font-weight:600;line-height:1.6;background:{$c["pillbg"]};color:{$c["text"]};"
            ."border:1px solid {$c["pillbd"]};white-space:nowrap";
        $s["body"]="flex:1 1 auto;min-width:0";
        $s["text"]="font-size:13px;line-height:1.5;color:{$c["text"]};"
            ."word-wrap:break-word;overflow-wrap:break-word";
        $s["act"]="flex:0 0 auto;text-align:right";
        return $s;
    }

    /**
     * Bouton d'action du bandeau (3e segment separe par ||). Trois formes recues:
     * "wiki:<url>" -> popup d'aide, du JS brut -> onclick, ou un fragment HTML deja
     * construit. Ce dernier cas est reel (fw.dns.unbound.php, fw.dns.servers.php
     * passent leur $button): le donner comme onclick a button_autnonome fabriquait
     * un attribut onclick rempli de HTML, donc un bouton mort.
     */
    private function div_banner_button($raw,$btclass):string{
        $raw=trim($raw);
        if($raw==""){return "";}
        if(strpos($raw,"<")!==false){return $raw;}
        if(preg_match("#wiki:(.+)#",$raw,$re)){
            $raw="s_PopUpFull('{$re[1]}','1024','900');";
        }
        return $this->button_autnonome("{howto}: {online_help}",$raw,"fas fa-user-headset",null,0,$btclass);
    }

    /**
     * Assemble le bandeau. Ne traduit rien: les trois appelants publics passent le
     * resultat a _ENGINE_parse_body(), et div_error() a deja traduit son texte en
     * entree. Une traduction ici ferait un troisieme passage pour rien.
     */
    private function div_banner($kind,$title,$text,$pill,$button,$glyph,$nomargin=false):string{
        $s=$this->div_banner_styles($kind,$nomargin);
        // Pentest 2026-11-04 : les bandeaux (div_error, div_warning, div_explain...)
        // affichent souvent un message d'erreur d'un daemon ou d'une base qui recopie
        // une valeur recue : titre et texte passent par le filtre HTML.
        $title=html_safe_fragment($title);
        $text=html_safe_fragment($text);

        // Une quinzaine d'appelants prefixent leur message d'un <i class='fas
        // fa-check-circle'></i> hérité du design precedent, qui faisait alors office
        // d'icone. La pastille le dit maintenant, et l'icone en double fait bruit.
        // On ne retire qu'une balise VIDE en tete de chaine: jamais une icone du
        // corps du texte, jamais une qui porterait du contenu.
        $text=preg_replace("#^\s*<i[^>]*>\s*</i>\s*#","",$text);

        // La pastille ne sort qu'avec un titre personnalise: c'est lui qui a chasse
        // le mot par defaut de l'entete, elle l'y remet. Sans titre, l'entete le dit
        // deja et la pastille l'ecrirait deux fois sur la meme ligne.
        $p="";
        if($pill<>""){$p="<span style=\"{$s["pill"]}\">$pill</span>";}
        $act="";
        if($button<>""){$act="<div style=\"{$s["act"]}\">$button</div>";}

        $html=array();
        $html[]="<div style=\"{$s["box"]}\">";
        $html[]="<div style=\"{$s["bar"]}\"></div>";
        $html[]="<div style=\"{$s["row"]}\">";
        $html[]="<div style=\"{$s["left"]}\">";
        $html[]="<div style=\"{$s["chip"]}\"><i class=\"$glyph\" style=\"{$s["ico"]}\"></i></div>";
        $html[]="<div style=\"{$s["body"]}\">";
        // Un titre vide supprime l'entete entiere: c'est ce que demande le prefixe
        // "notitle:" de div_explain(), dont le texte fournit deja ses propres <H1>.
        if($title<>""){
            $html[]="<div style=\"{$s["head"]}\"><div style=\"{$s["title"]}\">$title</div>$p</div>";
        }
        // <div> et non <p>: plusieurs appelants passent deja un <div> dans $text
        // (fw.nginx.DecisionIP.php), qu'un <p> refermerait prematurement.
        $html[]="<div style=\"{$s["text"]}\">$text</div>";
        $html[]="</div>";
        $html[]="</div>";
        $html[]=$act;
        $html[]="</div>";
        $html[]="</div>";

        return @implode("\n",$html);
    }

    function div_error($text):string{
        // Traduction en entree, comme a l'origine: le "||" est cherche dans le texte
        // deja resolu, donc un titre peut venir d'un {token}.
        $text=$this->_ENGINE_parse_body($text);
        $title="{SERVFAIL}";
        $pill="";
        $button="";
        // Seuil >3 (et non >0) conserve de l'origine: sur 1481 appels, un "||" colle
        // au debut vient d'un message, pas d'un titre.
        if(strpos($text,"||")>3){
            $tb=explode("||",$text);
            $title=$tb[0];
            $text=$tb[1];
            $pill="{error}";
            if(isset($tb[2])){$button=$this->div_banner_button($tb[2],"btn-danger");}
        }
        return $this->_ENGINE_parse_body($this->div_banner("error",$title,$text,$pill,$button,"fas fa-circle-xmark"));
    }

    function div_warning($text):string{
        // Le cas licence remplace le glyphe de la pastille. L'ancienne icone "fa-6x"
        // flottante n'a plus lieu d'etre: la maquette place justement l'icone dans
        // la pastille, et un 6x poussait le texte hors du bandeau.
        $glyph="fas fa-triangle-exclamation";
        if(strpos(" $text","{license_error}")>0){$glyph=ico_certificate;}
        $title="{Warning}";
        $pill="";
        $button="";
        if(strpos($text,"||")>0){
            $tb=explode("||",$text);
            $title=$tb[0];
            $text=$tb[1];
            $pill="{Warning}";
            if(isset($tb[2])){$button=$this->div_banner_button($tb[2],"btn-warning");}
        }
        return $this->_ENGINE_parse_body($this->div_banner("warning",$title,$text,$pill,$button,$glyph));
    }

    function div_success($text):string{
        // Vert et non plus le "panel-primary" bleu d'origine: sur 72 appels, la
        // quasi-totalite sont de vraies confirmations ({saved}, {deleted},
        // {connection_test_successful}), et le bleu empietait sur div_info().
        $glyph="fas fa-circle-check";
        if(strpos(" $text","{license_error}")>0){$glyph=ico_certificate;}
        $title="{success}";
        $pill="";
        $button="";
        if(strpos($text,"||")>0){
            $tb=explode("||",$text);
            $title=$tb[0];
            $text=$tb[1];
            $pill="{success}";
            if(isset($tb[2])){$button=$this->div_banner_button($tb[2],"btn-success");}
        }
        return $this->_ENGINE_parse_body($this->div_banner("success",$title,$text,$pill,$button,$glyph));
    }

    function div_info($text):string{
        // Titre par defaut {information} ("Information") plutot que {info} ("info"):
        // "INFO" etait trop sec en entete.
        $title="{information}";
        $button="";
        if(strpos($text,"||")>0){
            $tb=explode("||",$text);
            $title=$tb[0];
            $text=$tb[1];
            if(isset($tb[2])){$button=$this->div_banner_button($tb[2],"btn-info");}
        }
        // Aucune pastille sur les deux bandeaux neutres (info, explain): elle sert a
        // remettre la gravite qu'un titre personnalise a chassee de l'entete, et
        // "Information" n'est pas une gravite. Ce serait du bruit sur 571 appels.
        return $this->_ENGINE_parse_body($this->div_banner("info",$title,$text,"",$button,"fas fa-circle-info"));
    }
    function div_wizard($text):string{
        $title="{wizard}";
        if(strpos($text,"||")>3) {
            $tb = explode("||", $text);
            $title = $tb[0];
            $text = $tb[1];
        }

        $html[]="<div class='well'>";
        $html[]="    <div style='float:left;min-height:89px;margin-right:15px'>";
        $html[]="        <i class='fa-solid fa-hat-wizard fa-5x'></i>";
        $html[]="    </div>";
        $html[]="    <H2 style='margin-top:0'>$title</H2>";
        $html[]="    <div>$text</div>";
        $html[]="</div>";



        return @implode("\n",$html);
        
    }
    public function div_explain($text,$nomargin=false,$ico=""):string{
        $glyph="fa-solid fa-square-info";
        if(strlen($ico)>1){$glyph=$ico;}

        $title="{about2}";
        // "notitle:" -> aucune entete: ces appelants fournissent deja leurs propres
        // <H1>/<h3> dans le texte (fw.reputations.php, fw.ids.settings.php).
        if(preg_match("#^notitle:(.+)#",$text,$re)){
            $title="";
            $text=$re[1];
        }
        // "ico:<classe>;" -> glyphe choisi depuis le texte lui-meme (fw.networks.php),
        // en plus du 3e argument $ico (fw.nginx.well-known.php).
        if(preg_match("#ico:(.+?);#",$text,$re)){
            $glyph=$re[1];
            $text=str_replace("ico:{$re[1]};","",$text);
        }

        // Traduction en entree, comme a l'origine: le "||" est cherche dans le texte
        // deja resolu, donc le titre peut venir d'un {token}.
        $text=$this->_ENGINE_parse_body($text);

        $button="";
        if(strpos($text,"||")>2){
            $tb=explode("||",$text);
            $title=$tb[0];
            $text=$tb[1];
            // Un 3e segment NUMERIQUE etait l'ancienne taille d'icone ("fa-5x"): la
            // pastille fixe desormais cette taille, on l'ignore au lieu d'en faire un
            // bouton dont l'onclick vaudrait "4".
            if(isset($tb[2]) && !is_numeric($tb[2])){
                $button=$this->div_banner_button($tb[2],"btn-info");
            }
        }

        // $nomargin etait un parametre MORT: la version precedente codait sa marge en
        // dur. Deux appelants le passent a true (fw.ipfeeds.php) et n'obtenaient rien.
        return $this->_ENGINE_parse_body($this->div_banner("explain",$title,$text,"",$button,$glyph,$nomargin));
    }
    public function field_onoff_newlayer($label,$value,$js,$explain){
        $a="<a href=\"javascript:blur();\" OnClick=\"$js\">";
        if($value==1){
            $span="$a<span class='label label-primary'>{active2}</span></a>";
        }else{
            $span="$a<span class='label label'>{inactive}</span></a>";
        }
        return $this->_field_intr($label,$span,$explain);
    }
    /**
     * Ouvre une nouvelle carte de form_outside() (marqueur <!--afrm-sec-->, cf. afrm_cards()).
     * $title : titre (ou "titre||sous-titre") ; $explain : sous-titre gris ; $warn : le
     * texte devient un encadre d'avertissement sous le titre ; $ico : icone Font Awesome
     * dans une pastille de 28px.
     */
    public function field_section($title=null,$explain=null,$warn=false,$ico=""):string{
        $subtext="";
        if(is_null($explain) && strpos((string)$title,"||")>0){
            $tb=explode("||",(string)$title,2);
            $title=$tb[0];
            $subtext=$tb[1];
        }
        if(!is_null($explain) && !$warn){$subtext=(string)$explain;}
        $ico=preg_replace('#[^A-Za-z0-9 _-]#','',(string)$ico);
        $f=array();
        $f[]="<!--afrm-sec-->";
        $f[]="<tr class='afrm-row afrm-sec'>";
        $f[]="<td colspan='2'>";
        $f[]="<div class='afrm-sec-h'>";
        if($ico<>""){$f[]="<span class='afrm-sec-ico'><i class='$ico' aria-hidden='true'></i></span>";}
        $f[]="<div class='afrm-sec-txt'><div class='afrm-sec-t'>$title</div>";
        if(trim($subtext)<>""){$f[]="<div class='afrm-sec-s'>$subtext</div>";}
        $f[]="</div></div>";
        if($warn && !is_null($explain)){$f[]=$this->div_warning("$title||$explain");}
        $f[]="</td>";
        $f[]="</tr>";
        return @implode("\n",$f);
    }
    public function field_date($name,$label=null,$value=null,$explain=null){
        if($value==null){$value=date("Y-m-d");}
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $id=md5("$name$this->suffixid");
        $t=microtime();

        $F= "<div class='form-group' id='data_$id'>
			<div class='input-group date'>
				<span class='input-group-addon'><i class='fa fa-calendar'></i></span>
					<input type='text' class='form-control' value='".req_html($value)."' id='$id'>
			</div>
		</div>
		<script>
$('#data_$id .input-group.date').datepicker({
		todayBtn: \"linked\",
		keyboardNavigation: false,
		forceParse: false,
		calendarWeeks: true,
		autoclose: true,
		format: \"yyyy-mm-dd\"
	});
</script>		
		";



        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,$F,$explain);



    }
    public function field_squid_ports($name,$label,$value,$explain=null){
        $sql="SELECT * FROM proxy_ports";
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){echo "<div class='alert alert-danger' style='margin-top:10px'>$q->mysql_error<br><strong>$sql</strong></div>";}
        $ALL_TEXT=$this->_ENGINE_parse_body("{all_interfaces}");
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            if( ($ligne["transparent"]==1) OR ($ligne["TProxy"]==1)){continue;}
            $PortName=$this->javascript_parse_text($ligne["PortName"]);
            $ipaddr=trim($ligne["ipaddr"]);
            $port=$ligne["port"];
            $PORTS[$ID]="$port: $PortName - $ipaddr:$port";
        }
        return $this->field_array_hash($PORTS, $name, $label, $value,false,$explain);
    }

    public function field_localips($name,$label,$value,$explain=null){
        if(preg_match("#no default#", $value)){$value=null;$ASDEF=false;}
        include_once(dirname(__FILE__).'/class.system.nics.inc');
        $nic=new networking();
        $nicZ=$nic->Local_interfaces();
        if($ASDEF){$NICS["0.0.0.0"]="{all_interfaces}";}
        $NICS["127.0.0.1"]="Loopback";
        $EnableIwConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));
        foreach ($nicZ as $yinter=>$line){
            if(preg_match("#^dummy#", $yinter)){continue;}
            if(preg_match("#-ifb$#", $yinter)){continue;}
            if($EnableIwConfig==0){ if(preg_match("#^wlan[0-9]+#", $yinter)){continue;} }
            $znic=new system_nic($yinter);
            if($znic->Bridged==1){continue;}
            if($znic->enabled==0){continue;}
            $NICS[$znic->IPADDR]="$znic->NICNAME ($yinter)";
        }
        return $this->field_array_hash($NICS, $name, $label, $value,false,$explain);

    }

    public function field_interfaces_vlan($eth,$name,$label,$value,$explain=null){
        $ASDEF=true;
        $loopback=true;
        $NICS=array();
        if(preg_match("#nooloopNoDef:(.+)#",$label,$re)){$label=$re[1];$loopback=false;$ASDEF=false;}
        if(preg_match("#nooloop:(.+)#",$label,$re)){$label=$re[1];$loopback=false;}
        if(preg_match("#nodef:(.+)#",$label,$re)){$label=$re[1];$ASDEF=false;}
        if(preg_match("#no default#", $value)){$value=null;$ASDEF=false;}

        if($ASDEF){$NICS[null]="{none}";}
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $sql="SELECT * FROM nics_vlan WHERE nic='{$eth}' ORDER BY ID";
        $results=$q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne) {
            $ID=$ligne["ID"];
            $vlanid = $ligne["vlanid"];
            $eth_text = "vlan{$ligne["ID"]}";
            $ipaddr=$ligne["ipaddr"];
            $NICS[$ID]="$eth_text $ipaddr (TAG $vlanid)";
            $InterfacesCount++;
        }

        if($InterfacesCount==0){
            return $this->field_hidden($name,null);
        }

        return $this->field_array_hash($NICS, $name, $label, $value,false,$explain,null,$ASDEF);


    }

    public function InterfaceTOIP($ifname):string{
        include_once(dirname(__FILE__).'/class.system.nics.inc');
        $nic=new networking();
        $nic->ifconfig($ifname);
        return $nic->tcp_addr;

    }

    public function list_interfaces($ONLYPHYS=false,$NICS=array()):array{
        $EnableIwConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));
        include_once(dirname(__FILE__).'/class.system.nics.inc');
        $nic=new networking();
        $nicZ=$nic->Local_interfaces();
        foreach ($nicZ as $yinter=>$line){
            if($yinter=="lo"){continue;}
            if(preg_match("#^dummy#", $yinter)){continue;}
            if(preg_match("#-ifb$#", $yinter)){continue;}
            if(preg_match("#^(dummy|teql|ip6tnl|tunl|gre|ifb|sit|gretap|erspan)[0-9]+#",$yinter)){continue;}
            if($ONLYPHYS){
                if(!preg_match("#^(eth|wlan|ens)#",$yinter)){continue;}
            }
            if($EnableIwConfig==0){ if(preg_match("#^wlan[0-9]+#", $yinter)){continue;} }
            $znic=new system_nic($yinter);
            if($znic->Bridged==1){continue;}
            if($znic->enabled==0){continue;}
            $NICS[$yinter]="$znic->NICNAME $znic->IPADDR ($yinter)";

        }
        return $NICS;
    }

    public function field_certificate($name,$label,$value=null,$explain=null,$ServerCert=0):string{
        $NoRoots="";

        if($ServerCert==1){
            $NoRoots="&NoRoots=yes";
        }
        return $this->field_text_button_certificateid($name,$label,$value,false,$explain,"{select}","fw.field.certificates.php?name=".urlencode($name)."&current=".urlencode((string)$value).$NoRoots,ico_certificate);
    }

    public function field_interfaces($name,$label,$value=null,$explain=null):string{
        $ASDEF=true;
        $loopback=true;
        $ONLYPHYS=false;$ASDEFNone=false;
        $NICS=array();
        if(preg_match("#nooloopNoDefphy:(.+)#",$label,$re)){$label=$re[1];$loopback=false;$ASDEF=false;$ONLYPHYS=true;}
        if(preg_match("#nooloopNoDef:(.+)#",$label,$re)){$label=$re[1];$loopback=false;$ASDEF=false;}
        if(preg_match("#nooloop:(.+)#",$label,$re)){$label=$re[1];$loopback=false;}
        if(preg_match("#nodef:(.+)#",$label,$re)){$label=$re[1];$ASDEF=false;}
        if(preg_match("#phy:(.+)#",$label,$re)){$label=$re[1];$ONLYPHYS=false;}
        if(!is_null($value)) {
            if (preg_match("#no default#", $value)) {
                $value = null;
                $ASDEF = false;
            }
        }
        if(preg_match("#nooloopNone:(.+)#",$label,$re)){$label=$re[1];$loopback=false;$ASDEF=true;$ASDEFNone=true;}
        if($ASDEF){
            $NICS[null]="{all_interfaces}";
            if($ASDEFNone){
                $NICS[null]="{none}";
            }

        }
        if($loopback) {
            $NICS["lo"] = "Loopback (127.0.0.1)";
        }
        $NICS=$this->list_interfaces($ONLYPHYS,$NICS);

        if(count($NICS)==0){
            return $this->field_hidden($name,null);
        }
        $Base64Nics=base64_encode(serialize($NICS));
        return $this->field_text_button_Interface($name,$label,$value,false,$explain,"{select}","fw.field.interfaces.php?name=$name&nics=$Base64Nics",ico_nic);

    }


    public function field_templates($name,$label,$value,$explain=null){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $results = $q->QUERY_SQL("SELECT TemplateName,ID FROM templates_manager");
        foreach ($results as $index=>$ligne){
            $TemplateName=$this->utf8_encode($ligne["TemplateName"]);
            $ID=$ligne["ID"];
            $XTPLS[$ID]=$TemplateName;
        }
        return $this->field_array_hash($XTPLS, $name, $label, $value,false,$explain);
    }

    public function field_webfiltering_rules($name,$label,$value,$explain=null,$RULES_ADD=array()){
        $memcached=new lib_memcached();
        $UFDB_RULES_LISTS=$memcached->getKey("UFDB_RULES_LISTS");
        if($memcached->MemCachedFound){
            $RULES=unserialize($UFDB_RULES_LISTS);
        }else{
            $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
            $sql="SELECT ID,groupname FROM webfilter_rules WHERE enabled=1";
            $results = $q->QUERY_SQL($sql);
            foreach ($results as $index=>$ligne){$RULES[$ligne["ID"]]="{$ligne["groupname"]}";}
            $memcached->SetSetting("UFDB_RULES_LISTS", serialize($RULES),300);

        }
        if(count($RULES_ADD)>0){
            foreach ($RULES_ADD as $ID=>$ulename){
                $RULES[$ID]=$ulename;
            }
        }

        return $this->field_array_hash($RULES, $name, $label, $value,false,$explain);

    }
    public function field_dnsfiltering_rules($name,$label,$value,$explain=null):string{
        $memcached=new lib_memcached();
        $RULES=array();
        $UFDB_RULES_LISTS=$memcached->getKey("DNSUFDB_RULES_LISTS");
        if($memcached->MemCachedFound){
            $RULES=unserialize($UFDB_RULES_LISTS);
        }else{
            $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
            $sql="SELECT ID,groupname FROM webfilter_rules WHERE enabled=1";
            $results = $q->QUERY_SQL($sql);
            if($results) {
                foreach ($results as $index => $ligne) {
                    $RULES[$ligne["ID"]] = "{$ligne["groupname"]}";
                }
            }
            $memcached->SetSetting("DNSUFDB_RULES_LISTS", serialize($RULES),300);

        }
        return $this->field_array_hash($RULES, $name, $label, $value,false,$explain);

    }


    public function CategoryidToName($category_id):string{
        $q=new postgres_sql();
        if(preg_match("#^P([0-9]+)#i", $category_id,$re)){$category_id=intval($re[1]);}
        $sql="SELECT categoryname FROM personal_categories WHERE category_id=$category_id";
        $ligne=$q->mysqli_fetch_array($sql);
        if($ligne["categoryname"]==null){return $this->_ENGINE_parse_body("{unknown}");}
        return $this->utf8_decode($ligne["categoryname"]);
    }

    public function field_personal_categories_list($name,$label,$value,$explain=null):string{
        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        if($DisablePostGres==1){return "";}

        $q=new postgres_sql();
        if(preg_match("#(.+?):ALL#", $name,$re)){
            $name=$re[1];
            $newcat2[0]="{all_categories}";
        }
        if($q->TABLE_EXISTS("personal_categories")) {
            $sql = "SELECT * FROM personal_categories WHERE enabled=1 AND official_category=0 and free_category=0 order by categoryname";
            $results = $q->QUERY_SQL($sql);

            while ($ligne = pg_fetch_assoc($results)) {
                $categoryname = $ligne["categoryname"];
                $category_id = $ligne["category_id"];
                $newcat2[$category_id] = $categoryname;
            }
        }

        return $this->field_array_hash($newcat2, $name, $label, $value,false,$explain);
    }


    public function array_categories_list():array{
        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        if($DisablePostGres==1){return array();}
        $newcat2=array();
        $q=new postgres_sql();

        if(!$q->TABLE_EXISTS("personal_categories")){
            include_once(dirname(__FILE__)."/class.categories.inc");
            $cate=new categories();
            $cate->initialize();
        }

        $sql="SELECT * FROM personal_categories order by categoryname";
        $results=$q->QUERY_SQL($sql);
        if(!$results){
            return $newcat2;
        }


        while ($ligne = pg_fetch_assoc($results)) {
            $categoryname=$ligne["categoryname"];
            $category_id=$ligne["category_id"];
            $newcat2[$category_id]=$categoryname;
        }

        return $newcat2;
    }


    public function field_categories_list($name,$label,$value,$explain=null){

        $q=new postgres_sql();
        if(preg_match("#(.+?):ALL#", $name,$re)){
            $name=$re[1];
            $newcat2[0]="{all_categories}";
        }

        $ManageOfficialsCategories = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
        $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by categoryname";
        if($ManageOfficialsCategories==1){
            $sql="SELECT * FROM personal_categories WHERE official_category=1 order by category_id";
        }



        $results=$q->QUERY_SQL($sql);
        while ($ligne = pg_fetch_assoc($results)) {
            $categoryname=$ligne["categoryname"];
            if(preg_match("#^reserved#",$categoryname)){continue;}
            $category_id=$ligne["category_id"];
            $newcat2[$category_id]=$categoryname;
        }
        $newcat2["999999999"]="The Shields";
        return $this->field_array_hash($newcat2, $name, $label, $value,false,$explain);
    }



    public function field_choose_periods($name,$label,$value,$explain=null){
        $Timez[0]="{unlimited}";
        $Timez[5]="5 {minutes}";
        $Timez[10]="10 {minutes}";
        $Timez[15]="15 {minutes}";
        $Timez[30]="30 {minutes}";
        $Timez[60]="1 {hour}";
        $Timez[120]="2 {hours}";
        $Timez[240]="4 {hours}";
        $Timez[720]="12 {hours}";
        $Timez[1440]="1 {day}";
        $Timez[2880]="2 {days}";
        $Timez[10080]="1 {week}";
        $Timez[20160]="2 {weeks}";
        $Timez[43200]="1 {month}";
        $Timez[129600]="3 {months}";
        $Timez[518400]="1 {year}";

        return $this->field_array_hash($Timez, $name, $label, $value,false,$explain);
    }


    public function field_url($label,$uri,$explain=null):string{

        $F="<a href=\"javascript:blur();\" OnClick=\"s_PopUpFull('$uri','1024','1024');\" class='btn btn-sm btn-outline btn-primary' style='font-size:14px'>$uri</a>";

        return $this->_field_intr($label,$F,$explain);

    }

    public function field_certificate_old($name,$label,$value=null,$explain=null):string{

        include_once(dirname(__FILE__)."/class.squid.reverse.inc");
        $squid_reverse=new squid_reverse();
        $sslcertificates=$squid_reverse->ssl_certificates_list();
        return $this->field_array_hash($sslcertificates,$name,$label,$value,false,$explain);
    }

    public function field_multiple_64($name,$label,$srcvalue=0,$explain=null):string{
        $start=200000/196;

        if($srcvalue>0){
            $main[$srcvalue]=$this->FormatNumber($srcvalue);

        }

        for($i=$start;$i<10000;$i+=64){
            $value=64*$i;
            $main[$value]=$this->FormatNumber($value);

        }
        return $this->field_array_hash($main,$name,$label,$srcvalue,$explain);
    }

    public function field_multiple_2($name,$label,$srcvalue=0,$explain=null):string{
        $start=512;

        if($srcvalue>0){
            $main[$srcvalue]=$this->FormatNumber($srcvalue);

        }

        for($i=$start;$i<10000;$i+=512){
            $value=2*$i;
            $main[$value]=$this->FormatNumber($value);

        }
        return $this->field_array_hash($main,$name,$label,$srcvalue,$explain);
    }

    public function field_picklist($hash,$name,$label,$value):string{
        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $def=null;
        $selected=false;
        $java=null;
        $javaend=null;
        if(empty($value)){$def="selected";$selected=true;}
        //if($OnChange<>null){$javaend="$('#$id').chosen().change( $OnChange );";}
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $h[]="<!-- Value sent:  [".req_html($value)."] Advanced DropDown -->";
        $x= $name."[]";
        $h[]="<select data-values=\"\" data-placeholder=\"{$label}...\" class=\"chosen-select\" id='$id' multiple=\"multiple\" tabindex=\"2\" $java>";

        foreach ($hash as $val=>$key){
            $def=null;
            if(strpos($key, "}")>0){$key=$this->_ENGINE_parse_body($key);}
            $lenght=strlen($key);
            if($lenght>70){$key=substr($key, 0,67)."...";}
            $h[]="<!-- ".req_html($key).",".req_html($val)."  -->";

            if(!$selected){


                    $h[]="<!-- ".req_html($val)."  -->";
                        if (array_key_exists($key, $value)) {
                            $h[]="<!-- OK  -->";
                            $def="selected";
                            //$selected=true;
                            $h[]="\t<option value=\"".req_html($val)."\" selected>".req_option($key)."</option>";

                            continue;
        }
//                    if(strtolower($val)==strtolower($selectedvals[$i])){
//                        $h[]="<!-- OK  -->";
//                        $def="selected";
//                        //$selected=true;
//                        $h[]="\t<option value=\"".req_html($val)."\" selected>".req_option($key)."</option>";
//                        $i++;
//                        continue;
//                    }


            }
            $h[]="\t<option value=\"".req_html($val)."\">".req_option($key)."</option>";
            $i++;
        }

        $h[]="</select>";


        $h[]="<script>";
        $h[]="\t$(function() {";
        $h[]="\t$('#$id').pickList({\"afterRefresh\": eventHandler,});";
        $h[]="\tfunction eventHandler() {val = $('#$id').val();document.getElementById('$id').setAttribute('data-values', val);}";
        $h[]="\t});";

        $h[]="</script>";


        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $h=array();
                $h[]= "<div class=\"input-group\"><input type=\"hidden\" id='$id' value=\"".req_html($value)."\">
                <strong>".req_html($value)."</strong>
                </div>";

            }
        }

        $explain="";


        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').getAttribute('data-values'));";
        return $this->_field_intr($label,@implode("\n",$h),$explain);

    }

    public function NameSpaceIntToStr($nsID=0):string{
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/list"));
        if(!is_object($json) || !$json->success){
            $err="";
            return "{unknown}";

        }

        if(!isset($json->data) || !is_array($json->data) || count($json->data)==0){
            return "{unknown}";
        }
        foreach($json->data as $ns) {
            $id = intval($ns->ID ?? $ns->id ?? 0);
            $name = htmlspecialchars($ns->name ?? "ns-$id");
            if($nsID==$id){
                return $name;
            }
        }
        return "{unknown}";
    }

    public function field_NameSpaces($name,$value):string{
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/network/namespaces/list"));
        if(!is_object($json) || !$json->success){
            $err="";
            return $this->field_array_hash(array(),$name,"{namespace}","",false);

        }

        if(!isset($json->data) || !is_array($json->data) || count($json->data)==0){
            return $this->field_array_hash(array(),$name,"{namespace}","",false);
        }
        foreach($json->data as $ns){
            $id=intval($ns->ID ?? $ns->id ?? 0);
            $name=htmlspecialchars($ns->name ?? "ns-$id");
            $enabled=isset($ns->enabled) ? intval($ns->enabled) : 0;
            $exists=isset($ns->exists) ? $ns->exists : false;
            $ns_addr="-";
            if(isset($ns->ns_addrs) && is_array($ns->ns_addrs) && count($ns->ns_addrs)>0){
                $ns_addr=htmlspecialchars($ns->ns_addrs[0]);
            }
            $host_addr="-";
            if(isset($ns->host_addrs) && is_array($ns->host_addrs) && count($ns->host_addrs)>0){
                $host_addr=htmlspecialchars($ns->host_addrs[0]);
            }
            $has_nat=(isset($ns->has_nat) && $ns->has_nat) ? "NAT" : "-";
            $enabled_text="{active2}";
            if(!$enabled){
                $enabled_text="{inactive2}";

            }elseif($exists){
                $lo_up=isset($ns->lo_up) ? $ns->lo_up : false;
                if(!$lo_up){
                    $enabled_text="{degraded}";
                }
            }
            $MAIN[$id]="$name ($enabled_text - $has_nat)";

        }
        return $this->field_array_hash($MAIN,$name,"{namespace}",$value,false);
    }

    public function field_numeric($name,$label=null,$value=0,$explain=null,$js=null,$disabled=false){
        $id=md5("$name$this->suffixid");
        $jsinside=null;
        $return_single=false;
        $style=" style='font-size:14px'";
        if(!is_null($explain)) {
            if (preg_match("#xsingle:(.+)#", $explain, $re)) {
                $return_single = true;
                $explain = $re[1];
                $style = " style='font-size:large !important'";
            }
        }
        if(!is_null($value)) {
            if (preg_match("#^sysctl:(.+)#", $value, $re)) {
                $value=$GLOBALS["CLASS_SOCKETS"]->KERNEL_GET($re[1]);
                VERBOSE("sysctl Value *$value*", __LINE__);
            }
        }
        if($js<>null){
            $jsinside=" OnChange=\"$js($(this).attr('id'),$(this).val());\"";
        }
        $disable="";
        if($disabled){
            $disable="disabled=\"\"";
        }
        $this->DISABLE_ALL[]=$id;
        if(!is_numeric($value)){$value=1;}
        $t=microtime();
        $F= "
			        <div class=\"input-group\">
			              <span class=\"input-group-btn\">
			                  <button type=\"button\" class=\"btn btn-default\" data-value=\"-2\" data-target=\"#$id\" 
			                  	data-toggle=\"spinner\" OnClick=\"SpinnerLess('$id')\">
			                      <span class=\"glyphicon glyphicon-minus\"></span>
			                  </button>
			              </span>
			              <input type=\"number\" name=\"$name\" data-ride=\"spinner\" id=\"$id\" class=\"form-control input-number\"$style value=\"$value\" data-min=\"1\" data-max=\"9999999999999999999999\" autocomplete=\"on\"$jsinside $disable>
			              <span class=\"input-group-btn\">
			                  <button type=\"button\" class=\"btn btn-default\" data-value=\"1\" data-target=\"#$id\" 
			                  OnClick=\"SpinnerHigh('$id')\"
			                  data-toggle=\"spinner\" data-on=\"mousehold\">
			                      <span class=\"glyphicon glyphicon-plus\"></span>
			                  </button>
			              </span>
			          </div>
			          <!-- DEFAULT = $value -->
			       ";
        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $F= "<div class=\"input-group\"><input type=\"hidden\" id='$id' value=\"$value\">
                <strong>$value</strong>
                </div>";

            }
        }

        if($return_single){
            return $F;
        }

        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,$F,$explain);
    }


    /**
     * Human-readable equivalent of a KB/s rate: "<MB/s> MB/s &middot; <link>".
     * Mirrors the live JS in field_kbps() so the first render matches typing.
     */
    public function kbps_readout($kbps){
        $kbps=floatval($kbps);
        $mbs=number_format($kbps/1024,2);
        $mbps=round($kbps/125);
        $link=($mbps>1000) ? number_format($kbps/125000,1)." Gbps" : "{$mbps} Mbps";
        return "$mbs MB/s &middot; $link";
    }

    /**
     * Bandwidth-rate input. The stored/posted value is always in KB/s
     * (kilobytes per second) — that is what Squid delay pools consume after
     * a x1024 conversion to bytes/s. To keep things familiar for operators we
     * show, live, the equivalent in MB/s (file-transfer speak) and the link
     * speed in Mbps/Gbps (ISP speak). 1024 KB/s = 1 MB/s ~= 8 Mbps.
     */
    public function field_kbps($name,$label=null,$value=0,$explain=null){
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if(!is_numeric($value)){$value=1;}
        $readout=$this->kbps_readout($value);

        $html[]="<span class=\"input-group-addon\">KB/s</span>";
        $html[]="<input type=\"text\" name=\"$name\" id=\"$id\" class=\"form-control\"  onkeyup=\"kbps$id()\" onchange='kbps$id()' style='font-size:14px' value=\"$value\">";
        $html[]="<span class='input-group-addon' id='mbps-$id'>$readout</span>";
        $html[]="</div>";
        $html[]="<script>";
        $html[]="function kbps$id(){";
        $html[]="\tvar value=document.getElementById('$id').value;";
        $html[]="\tif(value==''||isNaN(value)){";
        $html[]="\t\tdocument.getElementById('$id').value='$value';";
        $html[]="\t\treturn false;";
        $html[]="\t}";
        $html[]="\tvalue=parseFloat(value);";
        $html[]="\tvar mbs=(value/1024).toFixed(2);";
        $html[]="\tvar mbps=Math.round(value/125);";
        $html[]="\tvar link=(mbps>1000)?((value/125000).toFixed(1)+' Gbps'):(mbps+' Mbps');";
        $html[]="\tdocument.getElementById('mbps-$id').innerHTML=mbs+' MB/s &middot; '+link;";
        $html[]="\treturn true;";
        $html[]="\t}";
        $html[]="</script>";

        $F=@implode("\n",$html);

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $F= "<div class=\"input-group\"><input type=\"hidden\" id='$id' value=\"$value\">
                <strong>$value</strong>
                </div>";

            }
        }

        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,$F,$explain);
    }

    private function _field_intr2($label,$formatted_field,$explain=null,$Checkbox=false){
        $labelid=$label;
        if($labelid==null){$labelid=rand(0,time());}
        // "label:" keeps the label id apart from the field id md5($name): a field named like its label
        // would otherwise give two elements the same id.
        $id=md5("label:$labelid$this->suffixid");
        if($explain<>null){
            if($label<>null) {
                $explain = $this->_ENGINE_parse_body($explain);
                $explain = str_replace('"', "`", $explain);
                $zlabel[] = "<a href=\"javascript:blur();\"";
                $zlabel[] = "data-toggle=\"popover\"";
                $zlabel[] = "data-placement=\"left\"";
                $zlabel[] = "data-content=\"$explain\"";
                $zlabel[] = ">$label</a>";
                $label = @implode(" ", $zlabel);
            }
        }
        if($this->FORM_IN_ARRAY){return "<td>$formatted_field</td>";}
        if(is_bool($Checkbox)){
            if(!$Checkbox){$this->LABELS[]=$id;}
        }

        // Zones de texte (field_textarea...) : pleine largeur, libelle au-dessus (afrm_css()).
        if($label==null){
            return "
		<tr class='afrm-row afrm-full'>
		<td style='padding-bottom:10px;text-align:left;padding-left:19px' colspan='2'>$formatted_field</td>
		</tr>
		";

        }


        return "
		<tr class='afrm-row afrm-f afrm-full'>
		<td class='afrm-l' style='text-align:right !important;vertical-align:top;width:10%' nowrap><span class='labelform afrm-lbl' id='$id'>{$label}</span></td>
		<td class='afrm-c' style='padding-bottom:10px;text-align:left;padding-left:19px'>$formatted_field</td>
		</tr>
		";


    }

    private function js_help($label,$explain=null,$URL=null):array{
        if($explain==null){return array($label,"");}
        $array_help["TITLE"]=$label;
        $array_help["content"]=$explain;
        $array_help["ico"]="fa fa-question";
        $array_help["URL"]=$URL;
        $ico="{click_to} <strong>{view_message}</strong><br><p>";
        $scontent=base64_encode(serialize($array_help));
        $js="LoadAjax('artica-modal-dialog','fw.popup.php?array=$scontent')";

        $explain=$this->_ENGINE_parse_body($ico.$explain);
        $explain=str_replace('"', "`", $explain);
        $explain=str_replace("\n", "<br>", $explain);
        if(strlen($explain)>200){$explain=substr($explain,0,197)."...";}
        $zid=md5($explain.microtime());
        $zlabel[]="<a href=\"javascript:$js;\"";
        $zlabel[]="style='color:#676a6c;text-decoration:underline;text-decoration-style: dashed;'";
        $zlabel[]="data-toggle=\"popover\"";
        $zlabel[]="data-placement=\"right\"";
        $zlabel[]="data-content=\"$explain</p>\" id='$zid'>";
        $ahref=@implode(" ",$zlabel);
        return array($label,$ahref,"$(\"#$zid\").popover({ trigger: 'hover',html: true,});");
        //
    }

    private function _field_intr($label,$formatted_field,$explain=null,$Checkbox=false,$URL=null):string{
        // Rendu d'une ligne de form_outside() (maquette stitch_artica_dhcp_server_params,
        // 2026-11-11). Trois formes, classees pour afrm_css() :
        //   afrm-f    : libelle au-dessus du champ, une colonne de la grille ;
        //   afrm-sw   : interrupteur (field_checkbox) a droite, libelle et aide a gauche ;
        //   afrm-full : pleine largeur (sans libelle, zone de texte, contenu libre).
        if($label=="TABLE!"){
            return " <!-- _field_intr 1 --><tr class='afrm-row afrm-full'><td style='width:100%;padding-bottom:10px;' colspan='2'>$formatted_field</td></tr>";
        }

        if($label==null){$explain=null;}
        // "label:" keeps the label id apart from the field id md5($name), see _field_intr2.
        $id=md5("label:$label$this->suffixid");
        // Aide affichee sous le libelle : texte simple seulement (ni tableau, ni url:).
        $help="";
        $helptext="";
        if(is_string($explain) && trim($explain)<>"" && !preg_match("#^url:#",trim($explain))){
            $helptext=trim($explain);
            // Coupe a deux lignes en style EN LIGNE : une console qui a encore l'ancienne
            // feuille en cache ne doit jamais afficher un paragraphe entier.
            $help="<div class='afrm-h' style='font-size:13px;color:#64748b;line-height:1.45;max-height:2.9em;overflow:hidden;text-align:left;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical'>$helptext</div>";
        }

        if(!is_null($label)) {
            list($label, $href_label) = $this->js_help($label, $explain, $URL);
            if ($href_label <> null) {
                // Le libelle ouvre l'aide complete ; l'icone le signale.
                $label = "$href_label$label<i class='fa-solid fa-circle-info afrm-i' aria-hidden='true'></i></a>";
            }
        }

        if($this->FORM_IN_ARRAY){return "<td>$formatted_field</td>";}
        if(is_bool($Checkbox)){
            if(!$Checkbox){$this->LABELS[]=$id;}
        }
        $label=trim((string)$label);
        if($label=="&nbsp;"){$label="";}
        $labelstr=strlen($label);

        $trace=@debug_backtrace();
        $last=$trace[1]["function"];

        if($labelstr<2){
            return "
            <!-- _field_intr NO_LABEL length: $labelstr by function:$last -->
			<tr class='afrm-row afrm-full'>
				<td style='vertical-align:middle;padding-bottom:10px;' colspan='2'>$formatted_field</td>
			</tr>";
        }

        $kind="afrm-f";
        if(strpos((string)$formatted_field,"onoffswitch")!==false){$kind="afrm-sw";}
        elseif(stripos((string)$formatted_field,"<textarea")!==false){$kind="afrm-f afrm-full";}

        if($kind=="afrm-sw"){
            return "
            <!-- _field_intr switch by function:$last -->
			<tr class='afrm-row afrm-sw".($help<>""?" afrm-sw-x":"")."'>
				<td class='afrm-l' style='width:30%;text-align:right;vertical-align:middle;padding-bottom:10px;'><span class='labelform afrm-lbl' id='$id'>$label</span></td>
				<td class='afrm-c' style='width:70%;padding-bottom:10px;padding-left:19px'>$formatted_field</td>
			</tr>".($help<>""?"
			<tr class='afrm-row afrm-full afrm-hrow'><td colspan='2' style='padding:0 0 10px 0'>$help</td></tr>":"");
        }
        return "
            <!-- _field_intr field by function:$last -->
			<tr class='afrm-row $kind'>
				<td class='afrm-l' style='width:30%;text-align:right;vertical-align:middle;padding-bottom:10px;'><span class='labelform afrm-lbl' id='$id'>$label</span></td>
				<td class='afrm-c' style='width:70%;padding-bottom:10px;padding-left:19px'>$formatted_field$help</td>
			</tr>";
    }

    public function field_slider($name,$label,$value,$explain=null,$MAIN_ARRAY=array(),$OnUpdatejs=null,$direction="ltr"){
        $label_js=$this->javascript_parse_text($label);
        $label=$this->_ENGINE_parse_body($label);
        $label=str_replace(" ", "&nbsp;", $label);
        if($OnUpdatejs<>null){$OnUpdatejs="$OnUpdatejs(zvalues);";}
        $id=md5("$name$this->suffixid");
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $valuejs=is_numeric($value)?$value:0;
        $h=array();
        $h[]="<input type='hidden' value='".req_html($value)."' id='$id' name='$id'>";
        $h[]="<div id='slide_$id' class='noUi-target noUi-horizontal' style='margin-bottom:30px'>";
        $h[]="<script>";
        $h[]="var slide_$id = document.getElementById('slide_$id');";

        $h[]="noUiSlider.create(slide_$id, {
			start: $valuejs,
			behaviour: 'snap',
			connect: 'upper',
			direction: '$direction',
			snap:true,
			step: {$MAIN_ARRAY["STEP"]},
			tooltips: true,
			range: {";

        foreach ($MAIN_ARRAY["RANGE"] as $key=>$val){$r[]="'$key':  $val";}
        $h[]=@implode(",", $r);

        $h[]="},
				pips: {
		mode: 'values',
		values: [".@implode(",", $MAIN_ARRAY["VALUES"])."],
		density: 4
		}
		});
		slide_$id.noUiSlider.on('change', function ( zvalues, handle ) {
		    if(!zvalues){return;}
			document.getElementById('$id').value=zvalues;
			$OnUpdatejs
		});
		$OnUpdatejs
		";
        $h[]="</script>";

        if($this->FORM_IN_ARRAY){return "<td>".@implode("\n", $h)."</td>";}
        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,@implode("\n",$h),$explain);

    }

    public function field_array_hash_simple($hash,$name,$label,$value,$notnull=false,$js=null):string{
        $label_js=$this->javascript_parse_text($label);
        $value=trim($value);
        $label=$this->_ENGINE_parse_body($label);
        $label=str_replace(" ", "&nbsp;", $label);
        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $t=microtime();
        $OnChange=null;
        $def=null;
        $selected=false;
        if($value==null){$def="selected";$selected=true;}
        if($js<>null){
            if(strpos($js,"(")==0){
                $js="$js('$id')";
            }
            $OnChange="OnChange=\"javascript:$js\"";
        }
        $h[]="<!-- javascript : ". strlen($js)." value ".req_html($value)." -->";
        $h[]="<select name=\"$name\" class=\"form-control m-b\" id='$id' style='font-size:14px' $OnChange> ";
        $h[]="\t<option value=\"\" $def>".$this->javascript_parse_text("{select} - {none}")."</option>";

        foreach ($hash as $val=>$key){
            $def=null;
            $val=trim($val);
            if(strpos($key, "}")>0){$key=$this->javascript_parse_text($key);}
            $lenght=strlen($key);
            if($lenght>50){$key=substr($key, 0,47)."...";}

            if(!$selected){
                if(strtolower($val)==strtolower($value)){
                    $def="selected";
                    $selected=true;
                    $h[]="\t<option value=\"".req_html($val)."\" selected>".req_option($key)."</option>";
                    continue;
                }
            }
            // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
            $h[]="\t<option value=\"".req_html($val)."\">".req_option($key)."</option>";
        }

        $h[]="</select>";


        $h[]="";

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            // Use the md5 element id (always a valid JS identifier) as the variable name — the raw
            // field $name may contain '-'/'.'/spaces, which produced an invalid identifier like
            // `var cyrmb-na-domainvalue` → "Unexpected token '-'", breaking the whole Save() script.
            $this->XHR[]="var F{$id}value=document.getElementById('$id').value;";
            $this->XHR[]="if(F{$id}value.length==0){ document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_js</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,@implode("\n",$h));
    }

    public function field_array_checkboxes2Columns($hash,$name,$value,$notnull=false){
        if(!is_array($hash)){$hash=array();}
        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $t=microtime();
        $def=null;
        $selected=false;
        $java=null;
        $javaend=null;
        if(trim($value)==null){$def="selected";$selected=true;}
        $this->XHR[]="var _{$name}_value='';";
        $icheck_style="font-weight:normal;";

       $border="border-bottom: 5px solid transparent; ";
       $slice="padding-bottom:10px;margin-bottom:10px;border-bottom: 1px solid transparent;border-image: linear-gradient(0.25turn, rgba(2,0,36,1) 0%, rgba(255,255,255,1) 80%); border-image-slice: 1;";

        $table_style="margin-bottom:10px;";

        $FIELDSCHK=array();
        $switch=1;
        $i=0;
        foreach ($hash as $val=>$explain){
            if(is_array($explain)){continue;}
            if($val=="DISABLED_FORMS"){continue;}
            $checked=null;
            $i++;
            $id=md5($id.$val.$i);
            VERBOSE("field_array_checkboxes checked='$checked'", __LINE__);
            if("$val"=="$value"){$checked="checked";}
            if(is_numeric($val)){
                if(is_numeric($value)){
                    if($val==$value){$checked="checked";}
                }
            }else{
                if("$val"=="$value"){$checked="checked";}
            }

            if($switch==1){$switch=0;}else{$switch=1;}
            // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
            $explain=html_safe_fragment($explain);

            $swithsA=array();
            $swithsA[]="<!-- TABLE SWITCH $switch -->";
            $swithsA[]="<table style='$table_style'>";
            $swithsA[]="<tr>";
            $swithsA[]="<td style='width:1%;padding-right:15px;vertical-align:top'>";
            $swithsA[]="<div class='i-checks' style='$icheck_style'><input type='radio' id='$id' value='".req_html($val)."' name='$name' $checked></div>";
            $swithsA[]="</td>";
            $swithsA[]="<td style='width:99%'><div style='$slice'><label style='font-weight:normal'>$explain</label></div></td>";
            $swithsA[]="</tr>";
            $swithsA[]="</table>";
            $FIELDSCHK[$switch][]=@implode("\n",$swithsA);

           /* $FIELDSCHK[$switch][]="<div class='i-checks' style='margin-bottom:10px;font-weight:normal'><label> <input type='radio' id='$id' value='".req_html($val)."' name='$name' $checked> <i></i> <span style='font-weight:normal'>$explain </span></label></div>";
           */
            $this->XHR[]="if(document.getElementById('$id').checked){ _{$name}_value='".req_js($val)."';}";

        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="if( _{$name}_value.length ==0){ document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>{lists}</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }
        $this->XHR[]="XHR.appendData('$name',  _{$name}_value);";
        $html[]="
		<script>
			$('.i-checks input').iCheck( {
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green' 
			});
		</script>";
        if(!is_array($FIELDSCHK[0])){$FIELDSCHK[0]=array();}
        if(!is_array($FIELDSCHK[1])){$FIELDSCHK[1]=array();}
        $html1[]="<table style='margin-top:10px'>";
        $html1[]="<tr>";
        $html1[]="<td style='width:50%;padding-right:5px;border-right:2px solid #CCCCCC;vertical-align:top;'>".@implode("\n",$FIELDSCHK[0])."</td>";
        $html1[]="<td style='width:50%;padding-left:5px;vertical-align:top;'>".@implode("\n",$FIELDSCHK[1])."</td>";
        $html1[]="</tr>";
        $html1[]="</table>";
        $html1[]=@implode("\n",$html);
        return $this->_field_intr("TABLE!",@implode("\n",$html1));
    }

    /**
     * Decoupe un libelle traduit en titre + description.
     *
     * Les jetons Artica de choix de service portent deja la forme
     * "[b]Titre[/b][br]Description", que _ENGINE_parse_body() rend en
     * "<b>Titre</b><br>Description". Le decoupage se fait donc APRES
     * traduction et jamais sur la forme brute : class.templates.inc:544
     * reordonne "[br]." en ".[br]", si bien que le point de
     * "[b]Titre[/b].[br]Texte" se retrouve colle au </b>. Un libelle
     * depourvu de </b> part entierement en titre, sans description.
     *
     * La description peut contenir ses propres <b> (CREATE_MATTERMOST_SERVICE
     * en a un) : on ne coupe donc que sur le PREMIER </b>.
     */
    private function radiocard_split($parsed):array{
        if(!preg_match('#</(?:b|strong)>#i',$parsed)){
            return array(trim($parsed),"");
        }
        $parts=preg_split('#</(?:b|strong)>#i',$parsed,2);
        $title=preg_replace('#^\s*<(?:b|strong)[^>]*>#i','',$parts[0]);
        $desc=isset($parts[1])?$parts[1]:"";
        // Ponctuation restee hors du gras, puis le <br> de separation.
        $desc=preg_replace('#^\s*[.:;,]\s*#','',$desc);
        $desc=preg_replace('#^\s*(?:<br\s*/?>\s*)+#i','',$desc);
        $desc=preg_replace('#(?:<br\s*/?>\s*)+$#i','',$desc);
        return array(trim($title),trim($desc));
    }

    /**
     * Feuille de style des cartes-radio.
     *
     * A echo'er PAR LE FORMULAIRE APPELANT, AVANT form_outside() et jamais
     * dans le contenu qu'on lui passe : form_outside() termine par
     * _ENGINE_parse_body(), dont la regex #{(.+?)}# lit chaque corps de regle
     * CSS comme un {token} et declenche une recherche memcached par regle.
     * D'ou une methode separee plutot qu'un <style> embarque dans le retour de
     * field_array_radiocards().
     */
    public function field_array_radiocards_css():string{
        if(isset($GLOBALS["RADIOCARDS_CSS_DONE"])){return "";}
        $GLOBALS["RADIOCARDS_CSS_DONE"]=true;
        return "<style>
.nwrc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;width:100%;}
@media (max-width:760px){.nwrc-grid{grid-template-columns:1fr;}}
.nwrc-card{position:relative;display:block;margin:0;cursor:pointer;font-weight:normal;}
.nwrc-radio{position:absolute;opacity:0;width:1px;height:1px;margin:0;}
.nwrc-in{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;background:#ffffff;
 border:2px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.05);
 transition:border-color .15s ease,background-color .15s ease,transform .15s ease;height:100%;}
.nwrc-card:hover .nwrc-in{border-color:#6ee7b7;background:#ecfdf5;transform:translateY(-1px);}
.nwrc-radio:checked ~ .nwrc-in{border-color:#1ab394;background:#ffffff;}
.nwrc-radio:focus-visible ~ .nwrc-in{outline:2px solid #1ab394;outline-offset:2px;}
.nwrc-dot{flex:0 0 auto;width:17px;height:17px;margin-top:2px;border-radius:50%;
 border:2px solid #cbd5e1;background:#ffffff;position:relative;transition:border-color .15s ease;}
.nwrc-radio:checked ~ .nwrc-in .nwrc-dot{border-color:#1ab394;}
.nwrc-radio:checked ~ .nwrc-in .nwrc-dot:after{content:'';position:absolute;left:50%;top:50%;
 width:9px;height:9px;margin:-4.5px 0 0 -4.5px;border-radius:50%;background:#1ab394;}
.nwrc-txt{min-width:0;}
.nwrc-t{display:block;font-size:13px;font-weight:600;color:#1e293b;line-height:1.35;margin-bottom:3px;}
.nwrc-radio:checked ~ .nwrc-in .nwrc-t{color:#047857;}
.nwrc-d{display:block;font-size:12px;color:#64748b;line-height:1.55;}
.nwrc-d b,.nwrc-d strong{color:#475569;font-weight:600;}
</style>";
    }

    /**
     * Grille de cartes-radio a deux colonnes (maquette
     * stitch_website_rule_creation_dialog), en remplacement visuel de
     * field_array_checkboxes2Columns() -- que cette methode ne touche PAS :
     * neuf pages en dependent.
     *
     * ELLE DOIT VIVRE DANS CETTE CLASSE, pas dans la page appelante :
     * form_outside() ne serialise PAS le DOM. Chaque field_* pousse son propre
     * JavaScript dans $this->XHR -- une propriete PRIVEE -- qui devient le
     * corps de Save<id>(). Une grille de <input> ecrite a la main dans un
     * fw.*.php s'afficherait normalement et ne serait JAMAIS postee, sans la
     * moindre erreur : le handler verrait simplement un champ vide.
     *
     * Le contrat reproduit ici est donc celui de field_array_checkboxes2Columns() :
     *   - DISABLE_ALL[]      pour que le verrouillage de formulaire opere,
     *   - "var _<name>_value" puis un test par option,
     *   - XHR.appendData()   en dernier.
     *
     * @param array  $hash    valeur => libelle "[b]Titre[/b][br]Description"
     * @param string $name    nom du champ poste
     * @param string $value   valeur pre-selectionnee
     * @param bool   $notnull refuse l'envoi si rien n'est coche
     */
    public function field_array_radiocards($hash,$name,$value,$notnull=false):string{
        if(!is_array($hash)){$hash=array();}
        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $this->XHR[]="var _{$name}_value='';";

        $cards=array();
        $i=0;
        foreach($hash as $val=>$explain){
            if(is_array($explain)){continue;}
            if($val=="DISABLED_FORMS"){continue;}
            $i++;
            $cid=md5($id.$val.$i);
            $checked=null;
            if("$val"=="$value"){$checked="checked";}
            if(is_numeric($val) && is_numeric($value)){
                if($val==$value){$checked="checked";}
            }
            // Traduire AVANT de decouper : voir radiocard_split().
            list($title,$desc)=$this->radiocard_split($this->_ENGINE_parse_body($explain));
            // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
            // html_safe_fragment() garde les entites : pas de double encodage.
            $title=html_safe_fragment($title);
            $desc=html_safe_fragment($desc);
            // Ni $title ni $desc ne passent par htmlspecialchars() : ils
            // sortent de _ENGINE_parse_body(), donc la base de traduction y a
            // deja mis ses accents sous forme d'entites HTML. Les echapper une
            // seconde fois afficherait "Ao&ucirc;t" en clair.
            $c=array();
            $c[]="<label class='nwrc-card'>";
            $c[]="<input type='radio' class='nwrc-radio' id='$cid' value='".req_html($val)."' name='$name' $checked>";
            $c[]="<span class='nwrc-in'>";
            $c[]="<span class='nwrc-dot'></span>";
            $c[]="<span class='nwrc-txt'>";
            $c[]="<span class='nwrc-t'>$title</span>";
            if($desc<>null){$c[]="<span class='nwrc-d'>$desc</span>";}
            $c[]="</span></span></label>";
            $cards[]=@implode("\n",$c);

            $this->XHR[]="if(document.getElementById('$cid').checked){ _{$name}_value='".req_js($val)."';}";
        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="if( _{$name}_value.length ==0){ document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>{lists}</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }
        $this->XHR[]="XHR.appendData('$name',  _{$name}_value);";

        return $this->_field_intr("TABLE!","<div class='nwrc-grid'>".@implode("\n",$cards)."</div>");
    }

    public function field_array_checkboxes($hash,$name,$value,$notnull=false){
        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $t=microtime();
        $def=null;
        $selected=false;
        $java=null;
        $javaend=null;
        if(trim($value)==null){$def="selected";$selected=true;}
        $this->XHR[]="var _{$name}_value='';";

        $html[]="<div style='margin-top:20px'>";
        $i=0;
        foreach ($hash as $val=>$explain){
            if(is_array($explain)){continue;}
            if($val=="DISABLED_FORMS"){continue;}
            $checked=null;
            $i++;
            $id=md5("$id.$val.$i");
            VERBOSE("field_array_checkboxes checked='$checked'", __LINE__);
            if("$val"=="$value"){$checked="checked";}
            if(is_numeric($val)){
                if(is_numeric($value)){
                    if($val==$value){$checked="checked";}
                }
            }else{
                if("$val"=="$value"){$checked="checked";}
            }


            VERBOSE("field_array_checkboxes $val==$value ? $checked", __LINE__);
            // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
            $explain=html_safe_fragment($explain);
            $html[]="<div class='i-checks' style='margin-bottom:10px;font-weight:normal'><label> <input type='radio' id='$id' value='".req_html($val)."' name='$name' $checked> <i></i> <span style='font-weight:normal'>$explain </span></label></div>";
            $this->XHR[]="if(document.getElementById('$id').checked){ _{$name}_value='".req_js($val)."';}";

        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            $this->XHR[]="if( _{$name}_value.length ==0){ document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>{lists}</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }
        $this->XHR[]="XHR.appendData('$name',  _{$name}_value);";
        $html[]="</div>
		<script>
			$('.i-checks input').iCheck( {
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green' 
			});
		</script>";
        return $this->_field_intr("&nbsp;",@implode("\n",$html));

    }

    public function ProxyPortsDirections(){
        $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        $HaClusterProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyPort"));

        $PortDirectionS[0]="{all_methods}";
        if($HaClusterClient==0) {
            $PortDirectionS[1] = "{standard_method}";
            $PortDirectionS[2] = "{transparent_method}";
            $PortDirectionS[3] = "{smartphones_port}";
        }
        $ip=new networking();
        $interfaces=$ip->Local_interfaces();
        unset($interfaces["lo"]);

        $arrayNICZ[null]="0.0.0.0";


        foreach ($interfaces as $eth){
            if(preg_match("#^gre#", $eth)){continue;}
            $nic=new system_nic($eth);
            if($nic->enabled==0){continue;}
            $arrayNICZ[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        }

        $ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $sql="SELECT * FROM proxy_ports WHERE enabled=1";
        $resultsPorts = $ql->QUERY_SQL($sql);
        foreach ($resultsPorts as $index=>$lignePorts){
            $eth=$lignePorts["nic"];
            $ipaddr="";
            if(isset($arrayNICZ[$eth])) {
                $ipaddr = $arrayNICZ[$eth];
            }
            $port=$lignePorts["port"];
            $IDPort=$lignePorts["ID"];
            $PortDirectionS[$IDPort]="{port} $port [$ipaddr]";
            if($HaClusterClient==1){
                if($port==$HaClusterProxyPort){
                    $PortDirectionS[$IDPort]="HaCluster: {port} $port {standard_method}";
                }
            }
        }
        if($HaClusterClient==1) {
            $HaClusterTproxy = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTproxy");
            $HaClusterTproxyEnabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode");
            if($HaClusterTproxyEnabled==1){
                $PortDirectionS[500]="HaCluster: {port} $HaClusterTproxy {transparent_method}";

            }

        }

        return $PortDirectionS;
    }

    public function field_proxy_ports($name,$label,$value,$notnull=false,$explain=null,$OnChange=null,$defnull=true){
        $PortDirectionS=$this->ProxyPortsDirections();
        return $this->field_array_hash($PortDirectionS,$name,$label,$value,$notnull,$explain,$OnChange,$defnull);

    }

    public function field_geoip_countries($name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true){
        include_once(dirname(__FILE__)."/class.geoip-db.inc");
        $COUNTRIES=GEO_IP_COUNTRIES_LIST();
        return $this->field_array_hash($COUNTRIES,$name,$label,$value,$notnull,$explain,$OnChange,$defnull);
    }

    public function field_read_get_value($pattern,$hash=array()):string{
        $valueText="";
        $valueInside=null;
        VERBOSE("Pattern = [$pattern]",__LINE__);
        if(preg_match("#^sysctl:(.+)#",$pattern,$re)){
            $valueInside=$GLOBALS["CLASS_SOCKETS"]->KERNEL_GET($re[1]);
            VERBOSE("valueInside of $re[1] = [$valueInside]",__LINE__);
        }
        if(count($hash)>0){
            VERBOSE("valueInside of hash count = [".count($hash)."]",__LINE__);
            $valueText=$hash[$valueInside];
        }else{
            $valueText=$valueInside;
        }

        return $this->_ENGINE_parse_body($valueText);

    }

    public function field_read_numeric_setinfo($name,$label=null,$value=0,$explain=null,$security=null,$frmwcmd=null){
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $id=md5("field-read-$name");
        $URL=null;
        $label_text=$label;
        if(preg_match("#^nonull:(.+)#",$label_text,$re)){
            $label_text=$re[1];
        }
        $edit=$this->icon_edit_field("2x:Stx$id();",$security);
        list($label_text,$href_label,$over)=$this->js_help($label_text,$explain,$URL);
        if($href_label<>null){
            $this->FieldTableBack[]=$over;
            $label_text="$href_label$label_text</a>";
        }
        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px' id='$id'>";
        $INT[] = "\t<td style='width:1%;padding-left:10px'><i class='fa-duotone fa-solid fa-check fa-2x'></i></td>";
        $INT[] = "\t<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>";
        $INT[] = "<span style='color:#676a6c;font-size:large'>$label_text:</span>";
        $INT[] = "\t</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'>";
        $INT[]="<span id='f-$id'></span>";
        $INT[]="</td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
        $INT[] = "</tr>";
        $this->FieldTable[]=@implode($INT);

        $params[]=array();
        $params[]=$name;
        $params[]=$value;
        $params[]=false;
        $params[]="xsingle:$explain";
        $params[]="id:Stx{$id}Save";
        $params[]=$security;
        $params[]=$frmwcmd;
        $params_encode=base64_encode(serialize($params));

        $this->FieldTableJs[]="function Stx$id(){";
        if(!$this->IsSecurity($security)){$this->FieldTableJs[]="return;";}
        $this->FieldTableJs[]="UnloadCurrentForm%form();";
        $this->FieldTableJs[]="Loadjs('fw.fields.php?type=setinfo&field=field_numeric&params=$params_encode&id=f-$id');";
        $this->FieldTableJs[]="}";
        $this->FieldTableJs[]="";
        $this->FieldTableJs[]="function Stx{$id}Save(e,val){";
        $this->FieldTableJs[]="Loadjs('fw.fields.php?post-it=$params_encode&f-id=$id&id='+e+'&saved='+val+'&OnOpen=Stx$id')";
        $this->FieldTableJs[]="}";
        $this->FieldTableBack[]="LoadAjaxSilent('f-$id','fw.fields.php?value-decode=$params_encode&OnOpen=Stx$id');";

        return true;
    }

    public function field_read_array_hash_setinfo($hash,$name,$label,$value=null,$notnull=false,$explain=null,$security=null,$frmwcmd=null):bool{
        if($this->TRCLASS=="footable-odd"){$this->TRCLASS=null;}else{$this->TRCLASS="footable-odd";}
        $id=md5("field-read-$name");
        $URL=null;
        $label_text=$label;
        if(preg_match("#^nonull:(.+)#",$label_text,$re)){
            $label_text=$re[1];
        }
        $edit=$this->icon_edit_field("2x:Stx$id();",$security);

        list($label_text,$href_label,$over)=$this->js_help($label_text,$explain,$URL);

        if($href_label<>null){
            $this->FieldTableBack[]=$over;
            $label_text="$href_label$label_text</a>";
        }
        $INT[] = "<tr class='$this->TRCLASS' style='height: 60px' id='$id'>";
        $INT[] = "\t<td style='width:1%;padding-left:10px'><i class='fa-duotone fa-solid fa-check fa-2x'></i></td>";
        $INT[] = "\t<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>";
        $INT[] = "<span style='color:#676a6c;font-size:large'>$label_text:</span>";
        $INT[] = "\t</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'>";
        $INT[]="<span id='f-$id'></span>";
        $INT[]="</td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
        $INT[] = "</tr>";
        $this->FieldTable[]=@implode($INT);

        $params[]=$hash;
        $params[]=$name;
        $params[]=$value;
        $params[]=$notnull;
        $params[]="xsingle:$explain";
        $params[]="id:Stx{$id}Save";
        $params[]="$security";
        $params[]=$frmwcmd;
        $params_encode=base64_encode(serialize($params));

        $this->FieldTableJs[]="function Stx$id(){";
        if(!$this->IsSecurity($security)){$this->FieldTableJs[]="return;";}
        $this->FieldTableJs[]="UnloadCurrentForm%form();";
        $this->FieldTableJs[]="Loadjs('fw.fields.php?type=setinfo&field=field_array_hash&params=$params_encode&id=f-$id');";
        $this->FieldTableJs[]="}";
        $this->FieldTableJs[]="";
        $this->FieldTableJs[]="function Stx{$id}Save(e,val){";
        $this->FieldTableJs[]="Loadjs('fw.fields.php?post-it=$params_encode&f-id=$id&id='+e+'&saved='+val+'&OnOpen=Stx$id')";
        $this->FieldTableJs[]="}";
        $this->FieldTableBack[]="LoadAjaxSilent('f-$id','fw.fields.php?value-decode=$params_encode&OnOpen=Stx$id');";

        return true;
    }

    public function field_read_compile():string{

        $INT[]="<table class='table table-stripped' style='width:90%;margin-top:20px;margin-left:20px;'>";
        foreach ($this->FieldTable as $row){
            $INT[]=$row;
        }
        $INT[]="</table>";
        $INT[]="<script>";
        foreach ($this->FieldTableJs as $row){
            $INT[]=$row;
        }
        $INT[]="function UnloadCurrentForm%form(){";
        foreach ($this->FieldTableBack as $row){
            $INT[]=$row;
        }
        $INT[]="}";
        $INT[]="UnloadCurrentForm%form();";
        $INT[]="</script>";
        $data= @implode("\n",$INT);
        $data=str_replace("%form",time(),$data);
        return $data;
    }

    public function field_radio_hash($hash,$name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true):string{

        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $NameID=md5("$name$this->suffixid".serialize($hash));

        if($this->CLUSTER_CLI) {
            $PowerDNSEnableClusterSlave = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if ($PowerDNSEnableClusterSlave == 1) {
                $disabled = true;
            }
        }

        $h=array();
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        foreach ($hash as $zvalue=>$text){
            $checked="";
            $disabledt="";
            if($zvalue==$value){
                $checked="checked=\"\"";
            }
            if($disabled){
                $disabledt="disabled=\"\"";
            }
            $h[]="<div class=\"i-checks\"><label> <input type=\"radio\" value=\"".req_html($zvalue)."\" $checked $disabledt name=\"$NameID\"><i></i> ".html_safe_fragment($text)." </label></div>";
        }

        $this->XHR[]="if(!document.getElementsByName('$NameID')){alert('$name:$NameID !!');return;};";
        $this->XHR[]="var radios$NameID = document.getElementsByName('$NameID');";
        $this->XHR[]="for(var i = 0; i < radios$NameID.length; i++) {";
        $this->XHR[]="\tif(radios$NameID"."[i].checked) {";
        $this->XHR[]="\t\tXHR.appendData('$name',  radios$NameID"."[i].value);";
        $this->XHR[]="\t\tbreak;";
        $this->XHR[]="\t}";
        $this->XHR[]="}";
        return $this->_field_intr($label,@implode("\n",$h),$explain);

    }

    public function field_array_hash_categories($name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true):string{

        if(!isset($_SESSION["field_array_hash_categories"])) {
            $q = new postgres_sql();
            $sql = "SELECT * FROM personal_categories WHERE free_category=0 order by categoryname";

            $results = $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo "<div class='alert alert-danger'>$q->mysql_error</div>";
                return false;
            }


            while ($ligne = pg_fetch_assoc($results)) {
                $category_id = $ligne["category_id"];
                if ($category_id == "234") {
                    $ligne["categoryname"] = "CloudFlare";
                }
                $categoryname = $ligne["categoryname"];
                if (preg_match("#^reserved#", $categoryname)) {
                    continue;
                }
                $_SESSION["field_array_hash_categories"][$category_id] = $categoryname;
            }

        }
        return $this->field_array_hash( $_SESSION["field_array_hash_categories"], $name, $label, $value,$notnull,$explain,$OnChange,$defnull);
    }

    public function field_array_buttons($hash,$name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true):string{
        if(!is_array($hash)){$hash=array();}
        $icon1=ico_check;
        $icon2="far fa-square-full";
        $id=md5("$name$this->suffixid");

        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $topbuttons[]= "<input type=hidden name=\"$name\" value=\"".req_html($value)."\" id='$id'>";
        $scr=array();
        $scr[]="<script>";
        $scr[]="function Set$id(value,Currid){";
        if($this->CLUSTER_CLI){
            $scr[]="return;";
        }
        // Choix unique (3 appels, periode HaCluster) : groupe d'options du systeme abt.
        // role=radiogroup / role=radio + aria-checked ; l'option choisie est pleine
        // (abt-p) ET cochee, l'etat ne tient pas a la seule couleur ; fleches = option voisine.
        $theok=base64_encode("<i class='$icon1' aria-hidden='true'></i>");
        $offCls="btn abt abt-i abt-o abt-sm";
        $onCls="btn abt abt-i abt-p abt-sm";
        $topbuttons[]="<div class=\"btn-group abt-seg\" role=\"radiogroup\" aria-label=\"".str_replace('"',"&quot;",$label)."\" onkeydown=\"Key$id(event)\">";
        $anyOn=false;
        foreach ($hash as $Arrvalue=>$Arrlabel){if($Arrvalue==$value){$anyOn=true;}}
        $first=true;
        foreach ($hash as $Arrvalue=>$Arrlabel){
            $btnid=md5("$name$this->suffixid$Arrlabel$Arrvalue");
            $on=$Arrvalue==$value;
            $tab=($on || (!$anyOn && $first))?"0":"-1";
            $first=false;
            $icok=$on?"<i class='$icon1' aria-hidden='true'></i>":"";
            $topbuttons[]= "<button type=\"button\" role=\"radio\" aria-checked=\"".($on?"true":"false")."\" tabindex=\"$tab\" class=\"".($on?$onCls:$offCls)."\" id=\"$btnid\" data-v=\"".req_html($Arrvalue)."\" OnClick=\"Set$id('".req_js($Arrvalue)."','$btnid')\"><span id='ico-$btnid'>$icok</span><span>".html_safe_fragment($Arrlabel)."</span></button>";
            $scr[]="var _b=document.getElementById('$btnid');_b.className='$offCls';_b.setAttribute('aria-checked','false');_b.tabIndex=-1;";
            $scr[]="document.getElementById('ico-$btnid').innerHTML='';";
        }
        $topbuttons[]="</div>";
        $scr[]="document.getElementById('$id').value=value;";
        $scr[]="var _c=document.getElementById(Currid);_c.className='$onCls';_c.setAttribute('aria-checked','true');_c.tabIndex=0;";
        $scr[]="document.getElementById('ico-'+Currid).innerHTML = base64_decode('$theok');";
        $scr[]="}";
        $scr[]="function Key$id(e){";
        $scr[]="var k=e.key;if(k!='ArrowRight'&&k!='ArrowDown'&&k!='ArrowLeft'&&k!='ArrowUp'){return;}";
        $scr[]="var g=e.currentTarget.querySelectorAll('[role=radio]');var i=Array.prototype.indexOf.call(g,document.activeElement);if(i<0||g.length<2){return;}";
        $scr[]="e.preventDefault();i=(k=='ArrowRight'||k=='ArrowDown')?(i+1)%g.length:(i-1+g.length)%g.length;g[i].focus();g[i].click();";
        $scr[]="}";
        $scr[]="</script>";
        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        $full=@implode("\n",$topbuttons)."\n".@implode("\n",$scr);
        return $this->_field_intr($label,$full,$explain);
    }


    public function field_array_hash($hash,$name,$label,$value=null,$notnull=false,$explain=null,$OnChange=null,$defnull=true):string{
        if(!isset($GLOBALS["StandardDropDown"])){
            $GLOBALS["StandardDropDown"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StandardDropDown"));
        }
        $StandardDropDown=$GLOBALS["StandardDropDown"];
        if(isset($_COOKIE["StandardDropDown"])){$StandardDropDown=intval($_COOKIE["StandardDropDown"]);}

        $return_single=false;
        $style=null;
        if(!is_null($explain)) {
            if (preg_match("#xsingle:(.+)#", $explain, $re)) {
                $return_single = true;
                $explain = $re[1];
                $style = " style='font-size:large !important'";
            }
        }
        if(!is_null($value)) {
            if(is_array($value)){
                $value=serialize($value);
            }
            if (preg_match("#^sysctl:(.+)#", $value, $re)) {
                $value=$GLOBALS["CLASS_SOCKETS"]->KERNEL_GET($re[1]);
                VERBOSE("sysctl Value *$value*", __LINE__);
            }
        }


        if(!isset($_SERVER["HTTP_USER_AGENT"])){$_SERVER["HTTP_USER_AGENT"]="unknown";}
        $FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]).".StandardDropDown";
        if(artica_setting_exists("$FileCookyKey")){$StandardDropDown=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("$FileCookyKey"));}


        if(!is_array($hash)){$hash=array();}
        if(isset($hash[-1])){$defnull=false;}

        
        if($StandardDropDown==1){
            // field_array_hash_simple($hash,$name,$label,$value,$notnull,$js):
            // $OnChange partait dans $notnull et $js n'etait jamais transmis.
            // Deux consequences en mode « StandardDropDown » : le gestionnaire
            // de changement n'etait pas branche (sur le selecteur de chemin du
            // redirector mTLS, target_server/remote_port cessaient d'etre
            // pre-remplis), et une chaine non vide dans $notnull armait le
            // controle « cannot be a null value » -- l'entree a valeur vide
            // (« acces direct ») devenait non soumettable.
            return $this->field_array_hash_simple($hash, $name, $label, $value, $notnull, $OnChange);
        }

        if(preg_match("#nonull:(.+)#",$label,$re)){
            $label=$re[1];
            $defnull=false;

        }

        $label_js=$this->javascript_parse_text($label);
        $label=$this->_ENGINE_parse_body($label);
        $label=str_replace(" ", "&nbsp;", $label);

        $id=md5("$name$this->suffixid");
        $this->id_temp=$id;
        $this->DISABLE_ALL[]=$id;
        $def=null;
        $selected=false;
        $java=null;
        if(!is_null($value)){
            $value=trim($value);
        }
        if($value==null){$def="selected";$selected=true;}

        $h[]="<select data-placeholder=\"$label...\" class=\"chosen-select\"$style id='$id' tabindex=\"2\" $java>";

        if($defnull) {
            if (!isset($hash[null])) {
                $h[] = "\t<option value=\"\"$style $def>" . $this->_ENGINE_parse_body("{default_or_null}") . "</option>";
            }
        }

        foreach ($hash as $val=>$key){
            $def=null;
            if(strpos($key, "}")>0){$key=$this->_ENGINE_parse_body($key);}
            $lenght=strlen($key);
            if($lenght>70){$key=substr($key, 0,67)."...";}
            //$h[]="<!-- $key,$val:$value  -->";
            if(!$selected){
                if(strtolower($val)==strtolower($value)){
                    $h[]="<!-- OK  -->";
                    $def="selected";
                    $selected=true;
                    $h[]="\t<option value=\"".req_html($val)."\"$style selected>".req_option($key)."</option>";
                    continue;
                }
            }
            // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
            $h[]="\t<option value=\"".req_html($val)."\"$style>".req_option($key)."</option>";
        }

        $h[]="</select>";


        $h[]="<script>";
        $h[]="function Choosen$id(){";
        $h[]="\t$('#$id').chosen({width: \"99%\"});";
        if($return_single) {
            $h[] = "\t$(\"#$id\").css( \"font-size\",\"large\");";
        }
        if($OnChange<>null){
            if(preg_match("#id:(.+)#",$OnChange)){
                $h[]="\t$('#$id').chosen().change(function(){";
                $h[]="\t\tvar id = $(this).val();";
                $h[]="\t\t$OnChange('$id',id);";
                $h[]="\t } );";
            }else {
                $h[] = "\t$('#$id').chosen().change(function(){";
                $h[] = "\t\tvar id = $(this).val();";
                $h[] = "\t\t$OnChange(id);";
                $h[] = "\t } );";
            }
        }
        $this->CHOSENS[]=$id;
        //$h[]="$('#$id').chosen().trigger('chosen:updated');";
        $h[]="}";
        $h[]="setTimeout (\"Choosen$id()\", 1000);";
        $h[]="</script>";


        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $h=array();
                $h[]= "<div class=\"input-group\"><input type=\"hidden\" id='$id' value=\"".req_html($value)."\">
                <strong>".req_html($value)."</strong>
                </div>";

            }
        }

        if($notnull){
            $cannot_be_a_null_value=$this->javascript_parse_text("{cannot_be_a_null_value}");
            // Use the md5 element id (always a valid JS identifier) as the variable name — the raw
            // field $name may contain '-'/'.'/spaces, which produced an invalid identifier like
            // `var cyrmb-na-domainvalue` → "Unexpected token '-'", breaking the whole Save() script.
            $this->XHR[]="var F{$id}value=document.getElementById('$id').value;";
            $this->XHR[]="if(F{$id}value.length==0){ document.getElementById('inside-form-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$label_js</strong> $cannot_be_a_null_value</div>';NoSpinner();return;}";
        }

        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        if($return_single){return @implode("\n",$h);}
        return $this->_field_intr($label,@implode("\n",$h),$explain);
    }

    public function field_checkbox_disbaleON($name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null){
        $URL            = null;
        $checked        = null;
        $status_label   = null;
        $button         = null;
        $OnClick        = null;
        $orgval         = $value;
        $AUTOSAVE       = false;
        $button_opts    = null;
        $re             = array();
        $button_help=null;
        if(is_array($disabled)){
            $explain=$disabled;
            $disabled=false;

        }
        if(preg_match("#^url:(.+?);(.*)#",trim($label),$re)){
            $label=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }

        if(preg_match("#^span:(.+)#",$label,$re)){
            $label=$re[1];
            preg_match("#\{(.+?)\}#",$label,$re);
            $status_label="<span id='{$re[1]}'></span>";
        }

        if(preg_match("#(.+?):js:(.+)#",$label,$re)){
            $label=$re[1];
            $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
            $bt_js="OnClick=\"{$re[2]}\"";

            $button_opts="<span class=\"label label-primary\" $pointers $bt_js>{options}</span>";
        }



        if(!is_numeric($value)){
            if($value=="yes"){$value=1;}
            if($value=="no"){$value=0;}
        }
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        if(is_array($explain)){
            if(isset($explain["AUTOSAVE"])){$AUTOSAVE=$explain["AUTOSAVE"];}
            if($explain["BUTTON"]==true){
                if($explain["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$explain["BUTTON_JS"]}\"";}
                $button="<span class=\"label label-primary\" $pointers $bt_js>{$explain["BUTTON_CAPTION"]}...</span>";

            }
            if(isset($explain["EXPLAIN"])){$explain=$explain["EXPLAIN"];}
        }

        if(preg_match("#^url:(.+?);(.*)#",trim($explain),$re)){
            $explain=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";
        }
        if(preg_match("#^url:(.+)#",$explain,$re)){
            $explain=null;
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }


        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if($value==1){$checked="checked";}
        if(!is_bool($disable_form)){
            $this->JS[]="// $name: disable_form Not boolean";
            $this->JS[]="function CheckBox$id(){";
            // La case peut avoir disparu du DOM: FormStartupScripts appelle cette
            // fonction a l'initialisation du fragment, or un autre LoadAjax* peut
            // avoir remplace le conteneur entre-temps (bascule de mode dans
            // fw.dhcp.configuration.php). Sans cette garde, getElementById() renvoie
            // null, ".checked" leve une TypeError, et l'exception avorte TOUT le
            // reste du bloc <script> -- le fragment reste a moitie initialise.
            $this->JS[]="\tif(!document.getElementById('$id')){return;}";
            if(strpos($disable_form, ",")>0){
                $FieldsTocheckZ=explode("," ,$disable_form);
            }else{
                $FieldsTocheckZ[]=$disable_form;
            }

            foreach ($FieldsTocheckZ as $fieldz){
                $diz=md5($fieldz.$this->suffixid);
                // $this->JS[]="alert('$diz->'+$('#$diz').hasClass('chosen-select'));";
                $this->JS[]="\tif(!document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',false).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";

                $this->JS[]="\tif(document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',true).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";


                $this->JS[]="\tif(!document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=false;}}";
                $this->JS[]="\tif(document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=true;}}";
            }

            $this->JS[]="}";
            $disable_form=false;
            $OnClick=" OnClick=\"CheckBox$id();\"";
            $this->FormStartupScripts[]="CheckBox$id();";
        }else{
            $this->JS[]="// $name: disable_form ($disable_form) boolean";
        }

        if($disabled){$disable_form=false;}

        if($disable_form){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"CheckFormCheckBox('$id')\"";
            $this->JS[]="CheckFormCheckBox('$id');";

        }

        if($AUTOSAVE){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"Save{$this->form_id}();\"";

        }
        $xtable=array();
        if(is_null($status_label)){$status_label="";}
        if(is_null($button_opts)){$button_opts="";}
        if(is_null($button)){$button="";}

        $strlen=strlen($status_label)+strlen($button_opts)+strlen($button);

        if($strlen>3){
            $xtable[]="<div id='xtable' style='width:7%;float:right;margin-top:-36px'>";
            $xtable[]="<table style='width:100%'>";
            $xtable[]="<tr>";
            if(strlen($status_label)>3){
                $xtable[]="<td style='width:33%'>$status_label</td>";
            }
            if(strlen($button_opts)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button_opts</td>";
            }
            if(strlen($button)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button</td>";
            }
            $xtable[]="</tr>";
            $xtable[]="</table>";
            $xtable[]="</div>";
        }

        $css=null;
        //	if($this->FORM_IN_ARRAY){$css=" style='padding-top:5px'";}
        $html="
			<!-- name=$name value == '$orgval/$value' -->
			<div class=\"switch\"$css>
					<div class=\"onoffswitch\">
						<input type=\"checkbox\" name=\"$name\" $checked class=\"onoffswitch-checkbox\" id=\"$id\" $OnClick>
						<label class=\"onoffswitch-label\" for=\"$id\">
							<span class=\"onoffswitch-inner\"></span>
							<span class=\"onoffswitch-switch\"></span>
						</label>
						<span class=\"afrm-swt afrm-swt-on\" style=\"display:none\">{afrm_on}</span><span class=\"afrm-swt afrm-swt-off\" style=\"display:none\">{afrm_off}</span>
					</div>
				</div>
    <!-- options label -->
   ". @implode("\n",$xtable)."
	<!-- options buttons OFF -->
	";

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $disabled=true;

            }
        }


        if($security<>null){
            if(!$this->IsSecurity($security)){$disabled=true;}
        }
        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$html,$explain,false,$URL);

        }



        $this->XHR[]="\tif(document.getElementById('$id').checked){XHR.appendData('$name',  1);}else{XHR.appendData('$name',  0);}";
        return $this->_field_intr($label,$html,$explain,$disable_form,$URL);
    }
    public function field_checkbox($name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null): string
    {
        $URL            = null;
        $checked        = null;
        $status_label   = null;
        $button         = null;
        $OnClick        = null;
        $orgval         = $value;
        $AUTOSAVE       = false;
        $button_opts    = null;
        $re             = array();
        $button_help=null;
        if(is_array($disabled)){
            $explain=$disabled;
            $disabled=false;

        }
        if(preg_match("#^url:(.+?);(.*)#",trim($label),$re)){
            $label=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }

        if(preg_match("#^span:(.+)#",$label,$re)){
            $label=$re[1];
            preg_match("#\{(.+?)\}#",$label,$re);
            $status_label="<span id='{$re[1]}'></span>";
        }

        if(preg_match("#(.+?):js:(.+)#",$label,$re)){
            $label=$re[1];
            $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
            $bt_js="OnClick=\"{$re[2]}\"";

            $button_opts="<span class=\"label label-primary\" $pointers $bt_js>{options}</span>";
        }



        if(!is_numeric($value)){
            if($value=="yes"){$value=1;}
            if($value=="no"){$value=0;}
        }
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        if(is_array($explain)){
            if(isset($explain["AUTOSAVE"])){$AUTOSAVE=$explain["AUTOSAVE"];}
            if($explain["BUTTON"]==true){
                if($explain["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$explain["BUTTON_JS"]}\"";}
                $button="<span class=\"label label-primary\" $pointers $bt_js>{$explain["BUTTON_CAPTION"]}...</span>";

            }
            if(isset($explain["EXPLAIN"])){$explain=$explain["EXPLAIN"];}
        }

        if(!is_null($explain)){
            $explain=trim($explain);
        }else{
            $explain="";
        }

        if(preg_match("#^url:(.+?);(.*)#",$explain,$re)){
            $explain=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";
        }
        if(preg_match("#^url:(.+)#",$explain,$re)){
            $explain=null;
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }


        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if($value==1){$checked="checked";}
        if(!is_bool($disable_form)){
            $this->JS[]="// $name: disable_form Not boolean";
            $this->JS[]="function CheckBox$id(){";
            // La case peut avoir disparu du DOM: FormStartupScripts appelle cette
            // fonction a l'initialisation du fragment, or un autre LoadAjax* peut
            // avoir remplace le conteneur entre-temps (bascule de mode dans
            // fw.dhcp.configuration.php). Sans cette garde, getElementById() renvoie
            // null, ".checked" leve une TypeError, et l'exception avorte TOUT le
            // reste du bloc <script> -- le fragment reste a moitie initialise.
            $this->JS[]="\tif(!document.getElementById('$id')){return;}";
            if(strpos($disable_form, ",")>0){
                $FieldsTocheckZ=explode("," ,$disable_form);
            }else{
                $FieldsTocheckZ[]=$disable_form;
            }

            foreach ($FieldsTocheckZ as $fieldz){
                $diz=md5($fieldz.$this->suffixid);
               // $this->JS[]="alert('$diz->'+$('#$diz').hasClass('chosen-select'));";
                $this->JS[]="\tif(document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',false).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";

                $this->JS[]="\tif(!document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',true).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";


                $this->JS[]="\tif(document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=false;}}";
                $this->JS[]="\tif(!document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=true;}}";
            }

            $this->JS[]="}";
            $disable_form=false;
            $OnClick=" OnClick=\"CheckBox$id();\"";
            $this->FormStartupScripts[]="CheckBox$id();";
        }else{
            $this->JS[]="// $name: disable_form ($disable_form) boolean";
        }

        if($disabled){$disable_form=false;}

        if($disable_form){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"CheckFormCheckBox('$id')\"";
            $this->JS[]="CheckFormCheckBox('$id');";

        }

        if($AUTOSAVE){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"Save{$this->form_id}();\"";

        }
        $xtable=array();
        if(is_null($status_label)){$status_label=""; }
        if(is_null($button_opts)){$button_opts="";}
        if(is_null($button)){$button="";}



        $strlen=strlen($status_label)+strlen($button_opts)+strlen($button);

        if($strlen>3){
            $xtable[]="<div id='xtable' style='width:7%;float:right;margin-top:-36px'>";
            $xtable[]="<table style='width:100%'>";
            $xtable[]="<tr>";
            if(strlen($status_label)>3){
                $xtable[]="<td style='width:33%'>$status_label</td>";
            }
            if(strlen($button_opts)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button_opts</td>";
            }
            if(strlen($button)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button</td>";
            }
            $xtable[]="</tr>";
            $xtable[]="</table>";
            $xtable[]="</div>";
        }

        $css=null;
        //	if($this->FORM_IN_ARRAY){$css=" style='padding-top:5px'";}
        $html="
			<!-- name=$name value == '$orgval/$value' -->
			<div class=\"switch\"$css>
					<div class=\"onoffswitch\">
						<input type=\"checkbox\" name=\"$name\" $checked class=\"onoffswitch-checkbox\" id=\"$id\" $OnClick>
						<label class=\"onoffswitch-label\" for=\"$id\">
							<span class=\"onoffswitch-inner\"></span>
							<span class=\"onoffswitch-switch\"></span>
						</label>
						<span class=\"afrm-swt afrm-swt-on\" style=\"display:none\">{afrm_on}</span><span class=\"afrm-swt afrm-swt-off\" style=\"display:none\">{afrm_off}</span>
					</div>
				</div>
    <!-- options label -->
   ". @implode("\n",$xtable)."
	<!-- options buttons OFF -->
	";

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $disabled=true;

            }
        }


        if($security<>null){
            if(!$this->IsSecurity($security)){$disabled=true;}
        }
        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$html,$explain,false,$URL);

        }



        $this->XHR[]="\tif(document.getElementById('$id').checked){XHR.appendData('$name',  1);}else{XHR.appendData('$name',  0);}";
        return $this->_field_intr($label,$html,$explain,$disable_form,$URL);
    }

    /**
     * Same signature as field_checkbox(), but renders a prominent explanation
     * panel (div_explain style) on a second row spanning both columns.
     *
     * Use when the checkbox toggles a non-trivial behavior the user must
     * understand before flipping it (security-sensitive flags, irreversible
     * options, features with side effects on other modules).
     *
     * The $explain argument supports the same forms as field_checkbox:
     *   - plain string                — rendered as the explain body
     *   - "title||body"               — title shown as <H2>, body below
     *   - array("EXPLAIN" => "...")   — body extracted from the EXPLAIN key
     *   - "url:HREF;text"             — same URL-popup syntax as elsewhere
     *
     * Returns two <tr> rows joined; safe to drop into any form table that
     * already uses field_checkbox().
     */
    public function field_checkbox_explain($name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null): string
    {
        $explainText = $explain;
        if(is_array($explainText) && isset($explainText["EXPLAIN"])){
            $explainText = $explainText["EXPLAIN"];
        }
        if(!is_string($explainText)){
            $explainText = "";
        }
        $explainText = trim($explainText);

        // The checkbox row keeps no per-line explain (we render it ourselves
        // below as a full-width panel), but we preserve label-side handling
        // by passing through the original $explain — field_checkbox extracts
        // BUTTON / AUTOSAVE / url: directives from arrays/strings on its own.
        $row = $this->field_checkbox($name,$label,$value,$disable_form,$explain,$disabled,$security);

        if($explainText === ""){
            return $row;
        }

        $f   = array();
        $f[] = $row;
        $f[] = "<!-- -------------------------------------------------------------------------------------------------- -->";
        $f[] = "<tr>";
        $f[] = "<td colspan='2' style='padding-top:0;text-align:right'>";
        $f[] = "<div style='margin-top:-10px;margin-bottom:10px;font-size:11px;color:#333'>$explainText</div>";
        $f[] = "</td>";
        $f[] = "</tr>";
        $f[]="<!-- -------------------------------------------------------------------------------------------------- -->";
        return @implode("\n",$f);
    }

    // -----------------------------------------------------------------------
    // Cases a cocher en TUILES sur plusieurs colonnes.
    //
    // field_checkbox() rend une <tr> a deux cellules: libelle a droite,
    // interrupteur a gauche. Vingt-trois privileges empiles de cette facon
    // forment une colonne unique qu'il faut lire de bout en bout pour trouver
    // un droit, et qui laisse la moitie de la largeur vide sur un ecran normal.
    //
    // Les fonctions ci-dessous rendent la MEME case -- meme id, meme balisage
    // onoffswitch, meme ligne XHR -- dans une tuile posee sur une grille. Le
    // contrat de sauvegarde est donc inchange: form_outside lit $this->XHR, qui
    // interroge document.getElementById($id).checked, et se moque de ce qui
    // entoure l'input.
    // -----------------------------------------------------------------------

    /**
     * La feuille de style des tuiles, une seule fois par requete.
     *
     * A CONCATENER AUTOUR de _ENGINE_parse_body(), jamais dedans: la regex
     * #{(.+?)}# du moteur lit chaque corps de regle CSS comme un {token} et
     * declenche une recherche memcached par regle.
     */
    public function field_tiles_css():string{
        if(isset($GLOBALS["FIELD_TILES_CSS_DONE"])){return "";}
        $GLOBALS["FIELD_TILES_CSS_DONE"]=true;
        return "<style>
.ftl-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:6px 0 4px}
.ftl-grid-1{grid-template-columns:minmax(0,1fr)}
.ftl-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.ftl-sec{grid-column:1/-1;margin:16px 0 2px;padding-bottom:8px;border-bottom:1px solid #bcc9c6;font-size:12px;font-weight:600;line-height:16px;letter-spacing:.06em;text-transform:uppercase;color:#3d4947}
.ftl-sec:first-child{margin-top:2px}
.ftl{display:flex;align-items:center;gap:14px;padding:12px 16px;border:1px solid #bcc9c6;border-radius:12px;background:#fff;transition:background .15s,border-color .15s,box-shadow .15s}
.ftl:hover{background:#fbfeff;border-color:#006a62}
.ftl-on{background:rgba(38,166,154,.07);border-color:#26a69a;box-shadow:inset 3px 0 0 #006a62}
.ftl-ico{width:40px;height:40px;border-radius:8px;background:rgba(0,106,98,.1);color:#006a62;display:inline-flex;align-items:center;justify-content:center;font-size:17px;flex:0 0 auto}
.ftl-off .ftl-ico{background:rgba(38,50,56,.08);color:#6d7a77}
.ftl-txt{flex:1 1 auto;min-width:0}
.ftl-name{font-size:14px;font-weight:600;line-height:19px;color:#111d23;overflow-wrap:anywhere}
.ftl-off .ftl-name{color:#3d4947}
.ftl-meta{margin-top:2px;font-size:11px;line-height:15px;color:#6d7a77;overflow-wrap:anywhere}
.ftl-sw{flex:0 0 auto;display:flex;align-items:center}
.ftl-sw .switch{margin:0}
.ftl-lock{opacity:.55}
.ftl-lock:hover{background:#fff;border-color:#bcc9c6}
@media(max-width:900px){.ftl-grid,.ftl-grid-3{grid-template-columns:minmax(0,1fr)}}
</style>";
    }

    /**
     * Le liant JavaScript des tuiles, une seule fois par requete.
     *
     * Sans lui la tuile garde l'apparence rendue par le serveur: on decoche un
     * droit et le fond reste vert. Une tuile qui affiche ACTIF alors que son
     * interrupteur vient de passer a OFF ment a l'operateur, qui appliquera un
     * formulaire dont il croit connaitre l'etat.
     *
     * A CONCATENER AUTOUR de _ENGINE_parse_body(), pour la meme raison que la
     * feuille de style ci-dessus.
     */
    public function field_tiles_js():string{
        if(isset($GLOBALS["FIELD_TILES_JS_DONE"])){return "";}
        $GLOBALS["FIELD_TILES_JS_DONE"]=true;
        return "<script>
(function(){
 function sync(cb){
  var t=cb;
  while(t && (!t.className || (' '+t.className+' ').indexOf(' ftl ')<0)){t=t.parentNode;}
  if(!t){return;}
  t.className=t.className.replace(/\\s*ftl-(on|off)\\b/g,'')+(cb.checked?' ftl-on':' ftl-off');
 }
 var all=document.getElementsByClassName('ftl-chk');
 for(var i=0;i<all.length;i++){
  if(all[i].__ftlBound){continue;}
  all[i].__ftlBound=true;
  all[i].onchange=function(){sync(this);};
 }
})();
</script>";
    }

    /**
     * Un intertitre qui occupe toute la largeur de la grille.
     * $title est un {token} comme n'importe quel libelle.
     */
    public function field_tiles_section($title):string{
        return "<div class='ftl-sec'>$title</div>";
    }

    /**
     * Une tuile d'interrupteur qui declenche du JavaScript au lieu d'etre postee.
     *
     * Meme balisage que field_checkbox_tile() -- donc meme rendu dans la meme grille -- avec
     * DEUX differences deliberees:
     *
     *  - aucune ligne $this->XHR n'est enregistree. La valeur ne vit pas dans le blob de
     *    privileges du groupe mais dans sa propre table, et l'ecriture a deja eu lieu quand
     *    l'operateur relache la souris. L'inscrire au formulaire ferait poster un champ que le
     *    gestionnaire d'enregistrement ne connait pas, et qui finirait ecrit dans le blob.
     *  - le champ n'a pas d'attribut name, pour la meme raison: une tuile posee a l'interieur
     *    d'un <form> ne doit pas pouvoir etre serialisee par accident.
     *
     * $js est du JavaScript emis tel quel dans un onclick: il vient du code appelant, jamais
     * d'une saisie, et toute valeur qu'il transporte doit deja etre encodee par l'appelant.
     */
    public function field_switch_tile($label,$meta,$value,$js,$icon="fas fa-key",$security=null):string{
        $value=intval($value);
        $id="ftlsw".md5($label.$meta.$js.$this->suffixid);

        $disabled=false;
        if($security<>null){
            if(!$this->IsSecurity($security)){$disabled=true;}
        }

        $checked=($value==1)?"checked":"";
        $state=($value==1)?"ftl-on":"ftl-off";
        $lock=$disabled?" ftl-lock":"";
        $dis=$disabled?" disabled":"";
        $onclick=$disabled?"":" onclick=\"$js\"";

        $h ="<div class='ftl $state$lock'>";
        $h.="<span class='ftl-ico'><i class='$icon'></i></span>";
        $h.="<div class='ftl-txt'>";
        $h.="<div class='ftl-name'>$label</div>";
        if(!is_null($meta) && trim((string)$meta)!==""){
            $h.="<div class='ftl-meta'>".htmlspecialchars((string)$meta)."</div>";
        }
        $h.="</div>";
        $h.="<div class='ftl-sw'><div class=\"switch\"><div class=\"onoffswitch\">";
        $h.="<input type=\"checkbox\" $checked$dis$onclick class=\"onoffswitch-checkbox ftl-chk\" id=\"$id\">";
        $h.="<label class=\"onoffswitch-label\" for=\"$id\">";
        $h.="<span class=\"onoffswitch-inner\"></span><span class=\"onoffswitch-switch\"></span>";
        $h.="</label></div></div></div>";
        $h.="</div>";
        return $h;
    }

    /**
     * Une case a cocher rendue en tuile.
     *
     * Reprend a l'identique ce dont depend la sauvegarde de field_checkbox():
     *  - l'id md5("$name$this->suffixid"), pour que deux rendus du meme champ
     *    dans une page ne se marchent pas dessus;
     *  - la ligne $this->XHR, que form_outside serialise pour construire le POST;
     *  - $this->DISABLE_ALL, qui verrouille le formulaire pendant l'envoi.
     *
     * $label traverse le moteur de traduction (les libelles de privileges
     * portent du [b]...[/b]): il ne doit donc PAS etre echappe ici.
     * $meta est le nom technique du droit, affiche en sous-titre -- c'est la
     * chaine que l'on retrouve dans usersMenus et dans l'API.
     */
    public function field_checkbox_tile($name,$label,$value=0,$icon="fas fa-key",$meta=null,$security=null):string{
        if(!is_numeric($value)){
            if($value=="yes"){$value=1;}
            if($value=="no"){$value=0;}
        }
        $value=intval($value);
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;

        $disabled=false;
        if($security<>null){
            if(!$this->IsSecurity($security)){$disabled=true;}
        }

        $checked=($value==1)?"checked":"";
        $state=($value==1)?"ftl-on":"ftl-off";
        $lock=$disabled?" ftl-lock":"";
        $dis=$disabled?" disabled":"";

        // Sans droit sur $security, la case est verrouillee ET n'est PAS
        // serialisee: une case desactivee ne poste rien, et field_checkbox()
        // fait deja exactement ce choix. Enregistrer la ligne XHR ici
        // renverrait 0 pour un droit que l'operateur n'avait pas le droit de
        // modifier -- soit une revocation silencieuse a chaque enregistrement.
        if(!$disabled){
            $this->XHR[]="\tif(document.getElementById('$id').checked){XHR.appendData('$name',  1);}else{XHR.appendData('$name',  0);}";
        }

        $h ="<div class='ftl $state$lock'>";
        $h.="<span class='ftl-ico'><i class='$icon'></i></span>";
        $h.="<div class='ftl-txt'>";
        $h.="<div class='ftl-name'>$label</div>";
        if(!is_null($meta) && trim((string)$meta)!==""){
            $h.="<div class='ftl-meta'>".htmlspecialchars((string)$meta)."</div>";
        }
        $h.="</div>";
        $h.="<div class='ftl-sw'><div class=\"switch\"><div class=\"onoffswitch\">";
        $h.="<input type=\"checkbox\" name=\"$name\" $checked$dis class=\"onoffswitch-checkbox ftl-chk\" id=\"$id\">";
        $h.="<label class=\"onoffswitch-label\" for=\"$id\">";
        $h.="<span class=\"onoffswitch-inner\"></span><span class=\"onoffswitch-switch\"></span>";
        $h.="</label></div></div></div>";
        $h.="</div>";
        return $h;
    }

    /**
     * Enveloppe la grille dans une <tr><td colspan=2>.
     *
     * INDISPENSABLE: form_outside depose son $content directement dans un
     * <table>. Un <div> pose la sans cellule est SORTI du tableau par le
     * navigateur (regle de reparation HTML) -- les tuiles remontent au-dessus
     * du formulaire, sans la moindre erreur nulle part.
     */
    public function field_tiles_grid($tiles,$columns=2):string{
        if(is_array($tiles)){$tiles=@implode("\n",$tiles);}
        $cls="ftl-grid";
        if(intval($columns)==1){$cls.=" ftl-grid-1";}
        if(intval($columns)==3){$cls.=" ftl-grid-3";}
        return "<tr><td colspan='2' style='padding:0'><div class='$cls'>$tiles</div></td></tr>";
    }

    public function field_checkbox_negative($name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null):string{
        $URL            = null;
        $checked        = null;
        $status_label   = null;
        $button         = null;
        $OnClick        = null;
        $orgval         = $value;
        $AUTOSAVE       = false;
        $button_opts    = null;
        $re             = array();
        $button_help=null;
        if(is_array($disabled)){
            $explain=$disabled;
            $disabled=false;

        }
        if(preg_match("#^url:(.+?);(.*)#",trim($label),$re)){
            $label=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }

        if(preg_match("#^span:(.+)#",$label,$re)){
            $label=$re[1];
            preg_match("#\{(.+?)\}#",$label,$re);
            $status_label="<span id='{$re[1]}'></span>";
        }

        if(preg_match("#(.+?):js:(.+)#",$label,$re)){
            $label=$re[1];
            $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
            $bt_js="OnClick=\"{$re[2]}\"";

            $button_opts="<span class=\"label label-primary\" $pointers $bt_js>{options}</span>";
        }



        if(!is_numeric($value)){
            if($value=="yes"){$value=1;}
            if($value=="no"){$value=0;}
        }
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";

        if(is_array($explain)){
            if(isset($explain["AUTOSAVE"])){$AUTOSAVE=$explain["AUTOSAVE"];}
            if($explain["BUTTON"]==true){
                if($explain["BUTTON_JS"]<>null){$bt_js="OnClick=\"{$explain["BUTTON_JS"]}\"";}
                $button="<span class=\"label label-primary\" $pointers $bt_js>{$explain["BUTTON_CAPTION"]}...</span>";

            }
            if(isset($explain["EXPLAIN"])){$explain=$explain["EXPLAIN"];}
        }

        if(!is_null($explain)){
            $explain=trim($explain);
        }else{
            $explain="";
        }

        if(preg_match("#^url:(.+?);(.*)#",$explain,$re)){
            $explain=$re[2];
            $URL="s_PopUpFull('{$re[1]}','1024','900')";
        }
        if(preg_match("#^url:(.+)#",$explain,$re)){
            $explain=null;
            $URL="s_PopUpFull('{$re[1]}','1024','900')";

        }


        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if($value==1){$checked="checked";}
        if(!is_bool($disable_form)){
            $this->JS[]="// $name: disable_form Not boolean";
            $this->JS[]="function CheckBox$id(){";
            // La case peut avoir disparu du DOM: FormStartupScripts appelle cette
            // fonction a l'initialisation du fragment, or un autre LoadAjax* peut
            // avoir remplace le conteneur entre-temps (bascule de mode dans
            // fw.dhcp.configuration.php). Sans cette garde, getElementById() renvoie
            // null, ".checked" leve une TypeError, et l'exception avorte TOUT le
            // reste du bloc <script> -- le fragment reste a moitie initialise.
            $this->JS[]="\tif(!document.getElementById('$id')){return;}";
            if(strpos($disable_form, ",")>0){
                $FieldsTocheckZ=explode("," ,$disable_form);
            }else{
                $FieldsTocheckZ[]=$disable_form;
            }

            foreach ($FieldsTocheckZ as $fieldz){
                $diz=md5($fieldz.$this->suffixid);
                // $this->JS[]="alert('$diz->'+$('#$diz').hasClass('chosen-select'));";
                $this->JS[]="\tif(!document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',false).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";
                $this->JS[]="\t}";

                $this->JS[]="\tif(document.getElementById('$id').checked){";
                $this->JS[]="\t\tif ($('#$diz').hasClass('chosen-select')){";
                $this->JS[]="\t\t\t$('#$diz').prop('disabled',true).trigger('chosen:updated').chosen('destroy').chosen();";
                $this->JS[]="\t\t}";



                $this->JS[]="\t}";


                $this->JS[]="\tif(!document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=false;}}";
                $this->JS[]="\tif(document.getElementById('$id').checked){if(document.getElementById('$diz')){document.getElementById('$diz').disabled=true;}}";
            }

            $this->JS[]="}";
            $disable_form=false;
            $OnClick=" OnClick=\"CheckBox$id();\"";
            $this->FormStartupScripts[]="CheckBox$id();";
        }else{
            $this->JS[]="// $name: disable_form ($disable_form) boolean";
        }

        if($disabled){$disable_form=false;}

        if($disable_form){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"CheckFormCheckBox('$id')\"";
            $this->JS[]="CheckFormCheckBox('$id');";

        }

        if($AUTOSAVE){
            if($this->form_id==null){$this->form_id=md5(microtime());}
            $OnClick=" OnClick=\"Save{$this->form_id}();\"";

        }
        $xtable=array();
        if(is_null($status_label)){$status_label=""; }
        if(is_null($button_opts)){$button_opts="";}
        if(is_null($button)){$button="";}



        $strlen=strlen($status_label)+strlen($button_opts)+strlen($button);

        if($strlen>3){
            $xtable[]="<div id='xtable' style='width:7%;float:right;margin-top:-36px'>";
            $xtable[]="<table style='width:100%'>";
            $xtable[]="<tr>";
            if(strlen($status_label)>3){
                $xtable[]="<td style='width:33%'>$status_label</td>";
            }
            if(strlen($button_opts)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button_opts</td>";
            }
            if(strlen($button)>3){
                $xtable[]="<td style='width:33%' style='padding-left:10px'>$button</td>";
            }
            $xtable[]="</tr>";
            $xtable[]="</table>";
            $xtable[]="</div>";
        }

        $css=null;
        //	if($this->FORM_IN_ARRAY){$css=" style='padding-top:5px'";}
        $html="
			<!-- name=$name value == '$orgval/$value' -->
			<div class=\"switch\"$css>
					<div class=\"onoffswitch\">
						<input type=\"checkbox\" name=\"$name\" $checked class=\"onoffswitch-checkbox\" id=\"$id\" $OnClick>
						<label class=\"onoffswitch-label\" for=\"$id\">
							<span class=\"onoffswitch-inner\"></span>
							<span class=\"onoffswitch-switch\"></span>
						</label>
						<span class=\"afrm-swt afrm-swt-on\" style=\"display:none\">{afrm_on}</span><span class=\"afrm-swt afrm-swt-off\" style=\"display:none\">{afrm_off}</span>
					</div>
				</div>
    <!-- options label -->
   ". @implode("\n",$xtable)."
	<!-- options buttons OFF -->
	";

        if($this->CLUSTER_CLI){
            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
            if($PowerDNSEnableClusterSlave==1){
                $disabled=true;

            }
        }


        if($security<>null){
            if(!$this->IsSecurity($security)){$disabled=true;}
        }
        if($disabled){
            $this->FormStartupScripts[]="document.getElementById('$id').disabled=true;";
            return $this->_field_intr($label,$html,$explain,false,$URL);

        }



        $this->XHR[]="\tif(document.getElementById('$id').checked){XHR.appendData('$name',  1);}else{XHR.appendData('$name',  0);}";
        return $this->_field_intr($label,$html,$explain,$disable_form,$URL);
    }


    public function field_checkbox_toogle($name,$label,$value,$array){

        $checked=null;
        $id=md5("$name$this->suffixid");
        $this->DISABLE_ALL[]=$id;
        if($value==$array[1]["VALUE"]){

            $checked="checked";
            $js="$('#$id').bootstrapToggle('on')";
        }else{
            $js="$('#$id').bootstrapToggle('off')";
        }
        $html="
		<!-- Current data = '$value' ==> {$array[1]["VALUE"]} check:$checked -->
		<input checked data-toggle=\"toggle\" id='$id'
		data-on=\"{$array[1]["LABEL"]}\" data-off=\"{$array[0]["LABEL"]}\" 
		data-onstyle='success' 
		data-size='mini'
		data-offstyle='danger' type='checkbox'>
		<script> $(function() { 
			$('#$id').bootstrapToggle();
			$js
		 }
		); </script>";
        $this->XHR[]="if(document.getElementById('$id').checked){XHR.appendData('$name',  '{$array[1]["VALUE"]}');}else{XHR.appendData('$name',  '{$array[0]["VALUE"]}');}";
        return $this->_field_intr($label,$html);
    }


    public function field_clock($name,$label,$value,$id=null){
        if($id==null){$id=md5("$name$this->suffixid");}
        $this->DISABLE_ALL[]=$id;
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).

        $html="
<div class='input-group' id='clock-$id' data-autoclose='true'>
	<input type='time' class='form-control' min=\"00:00:00\" max=\"23:59:59\" value='".req_html($value)."' id='$id' style='line-height: 20px !important;'>
	<span class='input-group-addon'><span class=\"fa fa-clock\"></span></span>
</div>";
        $this->XHR[]="if(!document.getElementById('$id')){alert('$name:$id !!');return;};XHR.appendData('$name',  document.getElementById('$id').value);";
        return $this->_field_intr($label,$html);

    }

    public function LeftMenu($array):string{
        $PAGE=trim($array["PAGE"]);
        $FILENAME=$PAGE;
        if(preg_match("#^(.+?)\?#",$PAGE,$re)){$FILENAME=$re[1];}

        if(preg_match("#^\/netagents\/webconsole\/proxy\/[0-9]+\/(.+)#",$FILENAME,$re)){
            $FILENAME=$re[1];
        }

        if(!is_file("/usr/share/artica-postfix/$FILENAME")){
            return "<!-- $FILENAME  no such file -->";
        }


        $ICON=$array["ICO"];
        $TEXT=$array["TEXT"];
        $fa="fa";
        if(preg_match("#^(.+?)\s+(.+)#", $ICON,$re)){$fa=$re[1];$ICON=$re[2];}
        $tt[]="<a href='#' OnClick=\"MenuRoot( $(this),'$PAGE'";

        if(isset($array["LEVEL3"])){
            $tt[]=",'{$array["LEVEL3"]["PAGE"]}'";
            $tt[]=",'{$array["LEVEL3"]["ID"]}'";

        }
        $tt[]=");\"><i class=\"$fa {$ICON}\"></i>&nbsp;";
        $tt[]="<span class=\"nav-label\">{$TEXT}</span></a>";
        $f[]="                <li>";
        $f[]="                  ".@implode("", $tt);
        if(isset($array["LEVEL3"])){
            $f[]="					<div id='{$array["LEVEL3"]["ID"]}'></div>";
        }
        $f[]="				 </li>";
        return @implode("\n",$f);
    }


    public function tabs_default($array):string{
        $js=null;
        $tabs=array();
        $content=array();
        $HIDE=array();
        if(!is_array($array)){
            $array=array();
        }
        $id=md5(serialize($array).microtime());
        $html[]="<div class=\"tabs-container\" id='$id'>";
        $html[]="<ul class=\"nav nav-tabs\" id='{$id}Tabs'>";


        $c=0;

        foreach ($array as $title => $url){
            $title=$this->_ENGINE_parse_body($title);
            // Pentest 2026-11-04 : XSS via les arguments du template, filtre au centre.
            // Titre = HTML filtre ; URL : & laisse tel quel (des appelants ecrivent
            // &amp;), seuls " < > sont encodes dans l'attribut data-url.
            $title=html_safe_fragment($title);
            $urljs=js_url_safe($url);
            if(!preg_match("#javascript:#",(string)$url)){
                $url=str_replace(array('"',"<",">"),array("&quot;","&lt;","&gt;"),(string)$url);
            }
            if($c==0){
                $tabs[]="<li class=\"active\"><a data-toggle=\"tab\" href=\"#tab-$id-$c\" data-url=\"$url\"> $title</a></li>";
                $content[]="<div id=\"tab-$id-$c\" class=\"tab-pane active\"></div>";
                $HIDE[]="\tdocument.getElementById('tab-$id-$c').innerHTML=''";
                $js="\tLoadAjaxTiny('tab-$id-$c','$urljs')";
                $c++;
                continue;
            }
            $c++;

            if(preg_match("#javascript:#",$url)){
                $tabs[]="<li class=\"\"><a data-url='0' OnClick=\"$url\"> $title</a></li>";
                continue;
            }


            $tabs[]="<li class=\"\"><a data-toggle=\"tab\" href=\"#tab-$id-$c\" data-url=\"$url\"> $title</a></li>";
            $content[]="<div id=\"tab-$id-$c\" class=\"tab-pane\"></div>";
            $HIDE[]="\tdocument.getElementById('tab-$id-$c').innerHTML='';";
        }

        $html[]=@implode("\n", $tabs)."</ul>";
        $html[]=@implode("\n", $content);
        $html[]="</div>";
        $html[]="<script>";
        $html[]="$('#$id').on('click','.tablink,#{$id}Tabs a',function (e) {";
        $html[]="\te.preventDefault();";
        $html[]="\tvar url = $(this).attr(\"data-url\");";
        $html[]="\tif (typeof url !== \"undefined\") {";
        $html[]="\tif(url=='0'){ return;}";
        $html[]=@implode("\n", $HIDE);
        $html[]="\tvar pane = $(this), href = this.hash;";
        $html[]="\t$(href).load(url,function(result){ pane.tab('show');  });";
        $html[]="\t} else {";
        $html[]="\t$(this).tab('show');";
        $html[]="\t}";
        $html[]="});";
        $html[]=$js;
        $html[]="</script>";
        return 	$this->_ENGINE_parse_body(@implode("\n", $html));

    }

    public function status_array($title,$text=null,$status=true,$disabled=false,$version="-"):string{
        $status_text="{running}";
        if($disabled){
            if(preg_match("#^v[0-9\.]+#", $text)){
                $version=$text;
                $text="-";
            }
        }

        $status_class="label-primary";
        if(!$status){
            $status_class="label-danger";
            $status_text="{stopped}";
        }

        if($disabled){
            $status_class="";
            $status_text="{disabled}";

        }

        return $this->_ENGINE_parse_body("<tr>
				<td><span class='label $status_class'>$status_text</span></td>
				<td style='padding-left:3px'>$title</td>
				<td style='padding-left:3px'>$version</td>
				<td style='padding-left:3px'>$text</td>
				</tR>");


    }


    public function DAEMON_STATUS_ROW($key,$bsini,$additionnal):string{
        $uptime=null;
        $memory=null;
        $version=null;
        if(!isset($bsini->_params[$key]["service_name"])){
            return $this->status_array("{{$key}}","{unknown}",false,true);
        }
        if(!isset($bsini->_params[$key]["master_pid"])){$bsini->_params[$key]["master_pid"]=null;}
        if(!isset($bsini->_params[$key]["uptime"])){$bsini->_params[$key]["uptime"]=null;}
        if(!isset($bsini->_params[$key]["processes_number"])){$bsini->_params[$key]["processes_number"]=0;}
        if(!isset($bsini->_params[$key]["running"])){$bsini->_params[$key]["running"]=0;}
        if(!isset($bsini->_params[$key]["installed"])){$bsini->_params[$key]["installed"]=1;}
        if(!isset($bsini->_params[$key]["pattern_version"])){$bsini->_params[$key]["pattern_version"]=null;}
        if(!isset($bsini->_params[$key]["pattern_date"])){$bsini->_params[$key]["pattern_date"]=null;}
        if(!isset($bsini->_params[$key]["license_error"])){$bsini->_params[$key]["license_error"]=null;}
        if(!isset($bsini->_params[$key]["why"])){$bsini->_params[$key]["why"]=null;}
        if(!isset($bsini->_params[$key]["other"])){$bsini->_params[$key]["other"]=null;}
        if(!isset($bsini->_params[$key]["threads_socket"])){$bsini->_params[$key]["threads_socket"]=null;}
        if(!isset($bsini->_params[$key]["master_memory"])){$bsini->_params[$key]["master_memory"]=null;}
        if(!isset($bsini->_params[$key]["master_cached_memory"])){$bsini->_params[$key]["master_cached_memory"]=null;}
        $master_title="{{$bsini->_params["$key"]["service_name"]}}";
        if(strlen($bsini->_params["$key"]["master_version"])>1){
            $version="v{$bsini->_params["$key"]["master_version"]}";
        }

        //$installed=$bsini->_params[$key]["installed"];

        if($bsini->_params[$key]["running"]==0){
            return $this->status_array("$master_title","",false,false,$version);

        }



        if(trim($bsini->_params["$key"]["master_memory"])<>null){
            $memory="{memory}: ".FormatBytes($bsini->_params["$key"]["master_memory"]);
        }

        if($bsini->_params["$key"]["uptime"]<>null){$uptime="<br><i>{since}: {$bsini->_params["$key"]["uptime"]}</i>";}
        $additionnal_text=null;
        if($additionnal<>null){$additionnal_text="<br><strong>$additionnal</strong>";}
        return $this->status_array("$master_title","$memory$uptime$additionnal_text",true,false,$version);

    }



    public function graph_date_line_sizeMB($data,$id){
        $f=array();
        foreach ($data as $time=>$size){
            if($GLOBALS["VERBOSE"]){echo "$time -> $size";}
            $timeZ=strtotime($time." UTC") * 1000;
            $size=$size/1024;
            $size=$size/1024;
            $size=round($size);
            if($GLOBALS["VERBOSE"]){echo "$timeZ -> $size\n";}
            $f[]="['$timeZ',$size]";

        }
        header("content-type: application/x-javascript");
        $fin[]="$(function() {";
        $fin[]="\tvar data=[\n".@implode(",", $f)."\n];";
        $fin[]="\t$.plot('#{$id}', [ data ], {";
        $fin[]="\t\tgrid: {	color: '#999999',";
        $fin[]="\t\ttickColor: '#D4D4D4',";
        $fin[]="\t\tborderWidth:0,";
        $fin[]="\t\tminBorderMargin: 20,";
        $fin[]="\t\tlabelMargin: 10,";
        $fin[]="\t\tbackgroundColor: { colors: ['#ffffff', '#ffffff'] },";
        $fin[]="\t\tmargin: { top: 8, bottom: 20, left: 20 }";
        $fin[]="\t\t},";
        $fin[]="\tcolors: ['#1ab394'],";
        $fin[]="\tseries: {";
        $fin[]="\t\tlines: { fill: true,show: true, barWidth: 0.6,align: 'center' }";
        $fin[]="\t},";
        $fin[]="\txaxis: {";
        $fin[]="\t\tmode: 'time',";
        $fin[]="\t\ttickLength: 0";
        $fin[]="\t}";
        $fin[]="});";



        $fin[]="});";
        echo @implode("\n",$fin);

    }

    public function graph_date_line_sizeKB($data,$id){

        foreach ($data as $time=>$size){
            if($GLOBALS["VERBOSE"]){echo "$time -> $size";}
            $timeZ=strtotime($time." UTC") * 1000;
            $size=$size/1024;
            $size=round($size);
            if($GLOBALS["VERBOSE"]){echo "$timeZ -> $size\n";}
            $f[]="['$timeZ',$size]";

        }
        header("content-type: application/x-javascript");
        $fin[]="$(function() {";
        $fin[]="\tvar data=[\n".@implode(",\n", $f)."\n];";
        $fin[]="\t$.plot('#{$id}', [ data ], {";
        $fin[]="\t\tgrid: {	color: '#999999',";
        $fin[]="\t\ttickColor: '#D4D4D4',";
        $fin[]="\t\tborderWidth:0,";
        $fin[]="\t\tminBorderMargin: 20,";
        $fin[]="\t\tlabelMargin: 10,";
        $fin[]="\t\tbackgroundColor: { colors: ['#ffffff', '#ffffff'] },";
        $fin[]="\t\tmargin: { top: 8, bottom: 20, left: 20 }";
        $fin[]="\t\t},";
        $fin[]="\tcolors: ['#1ab394'],";
        $fin[]="\tseries: {";
        $fin[]="\t\tlines: { fill: true,show: true, barWidth: 0.6,align: 'center' }";
        $fin[]="\t},";
        $fin[]="\txaxis: {";
        $fin[]="\t\tmode: 'time',";
        $fin[]="\t\ttickLength: 0";
        $fin[]="\t}";
        $fin[]="});";



        $fin[]="});";
        echo @implode("\n",$fin);

    }



    public function graph_date_line_int($data,$id){

        foreach ($data as $time=>$size){
            $timeZ=strtotime($time." UTC") * 1000;
            if($GLOBALS["VERBOSE"]){echo "$time - $timeZ Size:$size\n";}
            $f[]="['$timeZ',$size]";

        }
        header("content-type: application/x-javascript");
        $fin[]="$(function() {";
        $fin[]="\tvar data=[\n".@implode(",", $f)."\n];";
        $fin[]="\t$.plot('#{$id}', [ data ], {";
        $fin[]="\t\tgrid: {	color: '#999999',";
        $fin[]="\t\ttickColor: '#D4D4D4',";
        $fin[]="\t\tborderWidth:0,";
        $fin[]="\t\tminBorderMargin: 20,";
        $fin[]="\t\tlabelMargin: 10,";
        $fin[]="\t\tbackgroundColor: { colors: ['#ffffff', '#ffffff'] },";
        $fin[]="\t\tmargin: { top: 8, bottom: 20, left: 20 }";
        $fin[]="\t\t},";
        $fin[]="\tcolors: ['#1ab394'],";
        $fin[]="\tseries: {";
        $fin[]="\t\tlines: { fill: true,show: true, barWidth: 0.6,align: 'center' }";
        $fin[]="\t},";
        $fin[]="\txaxis: {";
        $fin[]="\t\tmode: 'time',";
        $fin[]="\t\ttickLength: 0";
        $fin[]="\t}";
        $fin[]="});";



        $fin[]="});";
        echo @implode("\n",$fin);

    }

    public function widget_install($explain=null,$LICENSE=false,$config=array()):string{
        $t=time();
        $BUTTON=true;
        $text_button="{launch_install}&nbsp;&raquo;&raquo;";
        $bg="navy-bg";
        $btbg="btn-primary";
        if($LICENSE){
            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
                $bg="gray-bg";
                $text_button="{license_invalid}";
                $btbg="btn-default";
            }
        }

        if(count($config)==0){
            $BUTTON=false;
        }
        $js=$this->framework_buildjs($config["CMD_ON"],
            $config["PROGRESS_FILE"],$config["LOG_FILE"],$t,$config["AFTER"]);

        if(isset($config["SECURITY"])){
            if(!$this->IsSecurity($config["SECURITY"])){$js=null;}
        }

        $html[]="<div style='margin:30px 30px 30px 30px;width:60%' class='center'>";
        $html[]="<div class=\"widget $bg p-lg text-center\">";
        $html[]="<table style='width:100%'>";
        $html[]="<tr>";
        $html[]="<td style='vertical-align:top;width:1%;' nowrap><i class=\"".ico_cd." fa-8x\"></i></td>";
        $html[]="<td style='vertical-align:top;padding-left:15px'><h1 class=\"m-xs\" style='text-align:left'>{install_the_feature}</h1>";
        $html[]="<p class=\"font-bold\" style='text-align:left !important;margin-left:30px;margin-right:30px'>$explain</p>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="<tr>";
        $html[]="<td colspan=2 style='text-align:right;margin-right:20px;border-top:1px solid #FFFFFF'>";
        $html[]="<div id='$t' style='margin-top:10px;margin-bottom:10px'></div>";
       if($BUTTON){ $html[]="<button type=\"button\" class=\"btn $btbg btn-lg\" style='text-transform: capitalize;' OnClick=\"$js\" >$text_button</button>";}
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</table>";
        $html[]="</div>";
        $html[]="</div>";

        return @implode("\n",$html);
    }

    /**
     * Design "Vibrant System" (stitch_modern_service_tiles) - feuille de style des tuiles de service.
     * Emise une seule fois par requete PHP. NE JAMAIS passer ce retour dans _ENGINE_parse_body()
     * (le moteur de traduction scanne #{(.+?)}# et attaquerait chaque regle CSS).
     */
    private function svctile_css():string{
        if(isset($GLOBALS["SVCTILE_CSS_DONE"])){return "";}
        $GLOBALS["SVCTILE_CSS_DONE"]=true;
        $c[]="<style type='text/css'>";
        $c[]=".svctile{position:relative;overflow:hidden;border-radius:5px;padding:24px;color:#fff;text-align:center;";
        $c[]="margin-top:2px;margin-bottom:10px;border:1px solid rgba(255,255,255,.10);box-shadow:0 4px 20px rgba(0,0,0,.10);";
        $c[]="-webkit-transition:-webkit-transform .3s ease,box-shadow .3s ease;transition:transform .3s ease,box-shadow .3s ease}";
        $c[]=".svctile:hover{-webkit-transform:scale(1.01);transform:scale(1.01);box-shadow:0 10px 30px rgba(0,0,0,.18)}";
        $c[]=".svctile:before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:rgba(255,255,255,.35);z-index:2}";
        // Degrades lineaires 135deg (design "central form view").
        // Rouge et jaune gardent volontairement leurs teintes: les hues du design
        // (#e74c3c, #f39c12) tombent a ~3,5:1 et ~2,1:1 face au texte blanc, sous le
        // seuil WCAG AA. Seule la geometrie du degrade s'aligne.
        $c[]=".svctile-green{background:linear-gradient(135deg,#0a8f66 0%,#067451 100%)}";
        $c[]=".svctile-red{background:linear-gradient(135deg,#d92b32 0%,#93000a 100%)}";
        $c[]=".svctile-yellow{background:linear-gradient(135deg,#d98324 0%,#7a3c05 100%)}";
        $c[]=".svctile-grey{background:linear-gradient(135deg,#7f8c8d 0%,#636e72 100%)}";
        $c[]=".svctile-lazur{background:linear-gradient(135deg,#1abc9c 0%,#16a085 100%)}";
        $c[]=".svctile-blue{background:linear-gradient(135deg,#2980b9 0%,#1a5980 100%)}";
        $c[]=".svctile-black{background:linear-gradient(135deg,#3a3d3f 0%,#0a0c0d 100%)}";
        $c[]=".svctile-wm{position:absolute;top:6px;right:10px;font-size:110px;line-height:1;opacity:.10;pointer-events:none;z-index:0}";
        $c[]=".svctile-in{position:relative;z-index:1}";
        // Font Awesome tourne en mode SVG+JS: les <i> sont remplaces par des <svg
        // class="svg-inline--fa"> hauts de 1em. La taille doit donc etre posee sur le
        // CONTENEUR (valable aussi en mode webfont, ou le <i> herite du font-size).
        $c[]=".svctile-badge{display:inline-block;padding:14px 18px;border-radius:5px;margin-bottom:14px;";
        $c[]="background:rgba(255,255,255,.20);border:1px solid rgba(255,255,255,.18);";
        $c[]="font-size:40px;line-height:1;color:#fff;";
        $c[]="-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px)}";
        $c[]=".svctile-badge i,.svctile-badge svg{font-size:inherit;line-height:1;color:#fff;vertical-align:middle}";
        $c[]=".svctile-title{font-size:18px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;";
        $c[]="opacity:.9;margin:0 0 6px 0;color:#fff;padding:0}";
        $c[]=".svctile-state{font-size:30px;font-weight:500;letter-spacing:-.01em;margin:0;padding:0;color:#fff;";
        $c[]="display:inline-block;vertical-align:middle;line-height:1.15}";
        $c[]=".svctile-state-sm{font-size:20px;font-weight:500;line-height:1.3}";
        $c[]=".svctile-dot{display:inline-block;width:10px;height:10px;border-radius:50%;background:#fff;";
        $c[]="margin-left:10px;vertical-align:middle;";
        $c[]="-webkit-animation:svctileglow 2s cubic-bezier(.4,0,.6,1) infinite;animation:svctileglow 2s cubic-bezier(.4,0,.6,1) infinite}";
        $c[]="@-webkit-keyframes svctileglow{0%,100%{opacity:.55}50%{opacity:1}}";
        $c[]="@keyframes svctileglow{0%,100%{opacity:.55;-webkit-transform:scale(1);transform:scale(1)}";
        $c[]="50%{opacity:1;-webkit-transform:scale(1.25);transform:scale(1.25)}}";
        $c[]=".svctile-glass{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.15);border-radius:5px;";
        $c[]="-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px)}";
        $c[]=".svctile-kpi{margin-top:18px;padding:14px 8px}";
        // table-layout:fixed = garde-fou: une valeur anormalement longue est tronquee
        // (ellipsis) au lieu de pousser la 2e colonne hors de la tuile.
        $c[]=".svctile-kpi table{width:100%;margin:0;border:0;color:#fff;table-layout:fixed}";
        $c[]=".svctile-kpi td{width:50%}";
        // color:#fff repete sur td/label/valeur: une table n'herite pas de la couleur du
        // parent en quirks mode, et le theme peut forcer une couleur sur les cellules.
        $c[]=".svctile-kpi td{text-align:left;padding:0 8px;vertical-align:top;border:0;color:#fff}";
        $c[]=".svctile-kpi td+td{border-left:1px solid rgba(255,255,255,.15)}";
        $c[]=".svctile-kpi-l{font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;opacity:.7;margin:0 0 2px 0;color:#fff}";
        $c[]=".svctile-kpi-v{font-size:19px;font-weight:700;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#fff}";
        $c[]=".svctile-meta{margin-top:16px;font-size:13px;font-weight:500;overflow:hidden;line-height:18px;color:#fff}";
        $c[]=".svctile-meta-l{float:left;opacity:.85;text-align:left}";
        $c[]=".svctile-meta-r{float:right;opacity:.8;font-style:italic;font-size:12px;padding-top:1px}";
        $c[]=".svctile-sparkl{font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;";
        $c[]="opacity:.7;margin:16px 0 4px 0;text-align:left}";
        $c[]=".svctile-spark{min-height:20px;text-align:center}";
        $c[]=".svctile-actions{margin-top:18px}";
        $c[]=".svctile-actions .btn-group{display:block;width:100%}";
        $c[]=".svctile-actions .btn-group>.btn,.svctile-actions .btn-group>label.btn{float:none;display:inline-block}";
        $c[]=".svctile-actions .btn,.svctile-actions label.btn{background:rgba(255,255,255,.12) !important;";
        $c[]="border:1px solid rgba(255,255,255,.40) !important;color:#fff !important;border-radius:5px !important;";
        $c[]="box-shadow:none !important;text-shadow:none !important;font-size:12px;font-weight:600;";
        $c[]="padding:8px 12px;margin:3px;text-transform:none;";
        $c[]="-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px)}";
        $c[]=".svctile-actions .btn:hover,.svctile-actions label.btn:hover,";
        $c[]=".svctile-actions .btn:focus,.svctile-actions label.btn:focus{background:rgba(255,255,255,.26) !important;color:#fff !important}";
        $c[]=".svctile-actions table{width:auto !important;margin:0 auto;border:0;color:#fff}";
        $c[]=".svctile-actions td{padding:0 3px;border:0;color:#fff}";
        $c[]=".svctile-sparkl,.svctile-title{color:#fff}";
        $c[]=".svctile-actions>div{margin-top:0 !important}";
        // --- Variante horizontale (widget_h) -------------------------------------
        // AUCUNE largeur ni min-height ici: ces widgets sont fluides et l'appelant
        // pilote lui-meme la taille via minheight:/minwidth: (styles inline).
        $c[]=".svcbar{position:relative;overflow:hidden;border-radius:5px;padding:18px 22px;color:#fff;";
        $c[]="margin-top:10px;margin-bottom:10px;border:1px solid rgba(255,255,255,.10);";
        $c[]="box-shadow:0 4px 20px rgba(0,0,0,.10);-webkit-transition:box-shadow .3s ease;transition:box-shadow .3s ease}";
        $c[]=".svcbar:hover{box-shadow:0 10px 30px rgba(0,0,0,.18)}";
        $c[]=".svcbar:before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:rgba(255,255,255,.35);z-index:2}";
        $c[]=".svcbar .row{position:relative;z-index:1}";
        // Icone nue et large, comme la version d'origine (fa-5x): pas de pastille vitree
        // ici, elle ecraserait la barre sur les formats courts. font-size sur le
        // conteneur (cf. remarque SVG+JS plus haut).
        // La colonne Bootstrap ajoute 15px de padding: on l'annule et on tire encore
        // l'icone vers l'exterieur pour la coller au bord gauche de la carte.
        $c[]=".svcbar .row>div:first-child{padding-left:0;padding-right:0}";
        $c[]=".svcbar-ico{display:block;line-height:1;font-size:76px;color:#fff;margin-left:-8px}";
        $c[]=".svcbar-ico i,.svcbar-ico svg{font-size:inherit;line-height:1;color:#fff;opacity:.95;";
        $c[]="filter:drop-shadow(0 2px 8px rgba(0,0,0,.15))}";
        $c[]=".svcbar-explain{display:block;font-size:13px;font-weight:500;text-transform:uppercase; line-height:18px;opacity:.85;color:#fff}";
        $c[]=".svcbar-number{font-size:28px;font-weight:500;letter-spacing:-.01em;margin:6px 0 0 0;padding:0;color:#fff;line-height:1.2}";
        $c[]=".svcbar .btn,.svcbar label.btn{background:rgba(255,255,255,.12) !important;";
        $c[]="border:1px solid rgba(255,255,255,.18) !important;color:#fff !important;border-radius:5px !important;";
        $c[]="box-shadow:none !important;text-shadow:none !important;font-weight:600;margin-top:10px;";
        $c[]="-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px)}";
        $c[]=".svcbar .btn:hover,.svcbar label.btn:hover,.svcbar .btn:focus,.svcbar label.btn:focus";
        $c[]="{background:rgba(255,255,255,.26) !important;color:#fff !important}";
        $c[]=".svcbar table{border:0;color:#fff}";
        $c[]=".svcbar td{border:0;color:#fff;padding:0 3px}";
        // --- Carte de statistiques (widget_h) ------------------------------------
        // Layout du design: libelle en capitales en haut a gauche, grand nombre en
        // dessous, boutons pilule, icone en filigrane bas-droite.
        // Comme .svcbar: aucune largeur ni hauteur imposee, l'appelant pilote via
        // minheight:/minwidth:.
        $c[]=".svcstat{position:relative;overflow:hidden;border-radius:5px;padding:24px;color:#fff;";
        $c[]="margin-top:10px;margin-bottom:10px;border:1px solid rgba(255,255,255,.10);";
        $c[]="box-shadow:0 4px 6px -1px rgba(0,0,0,.10),0 2px 4px -1px rgba(0,0,0,.06);";
        $c[]="-webkit-transition:box-shadow .3s ease;transition:box-shadow .3s ease}";
        $c[]=".svcstat:hover{box-shadow:0 10px 30px rgba(0,0,0,.18)}";
        $c[]=".svcstat:before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:rgba(255,255,255,.35);z-index:2}";
        $c[]=".svcstat-in{position:relative;z-index:1}";
        // Le filigrane porte sa taille sur SA PROPRE classe: Font Awesome recopie les
        // classes personnalisees sur le <svg> genere, donc la regle survit au mode SVG+JS.
        $c[]=".svcstat-wm{position:absolute;right:-14px;bottom:-18px;font-size:110px;line-height:1;";
        $c[]="opacity:.10;pointer-events:none;z-index:0;color:#fff}";
        $c[]=".svcstat-label{font-size:12px;font-weight:700;opacity:.80;margin:0 0 8px 0;padding:0;color:#fff;line-height:1.35}";
        // Applique uniquement aux libelles courts (cf. widget_h): une phrase entiere en
        // capitales espacees est illisible.
        $c[]=".svcstat-label-up{text-transform:uppercase;letter-spacing:.14em}";
        $c[]=".svcstat-num{font-size:34px;font-weight:500;letter-spacing:-.02em;margin:0;padding:0;color:#fff;line-height:1.15}";
        $c[]=".svcstat-num-sm{font-size:24px;line-height:1.25}";
        // Les branches de construction des boutons emballent deja le HTML dans un
        // <div style='text-align:right;float:right'>: on le neutralise ici plutot que de
        // reecrire ces branches.
        $c[]=".svcstat-act{margin-top:14px}";
        $c[]=".svcstat-act>div{margin-top:0 !important;text-align:left !important;float:none !important}";
        $c[]=".svcstat-act table{width:auto !important;margin:0;border:0;color:#fff}";
        $c[]=".svcstat-act td{padding:0 4px 0 0;border:0;color:#fff}";
        $c[]=".svcstat .btn,.svcstat label.btn{background:rgba(255,255,255,.10) !important;";
        $c[]="border:1px solid rgba(255,255,255,.40) !important;color:#fff !important;border-radius:5px !important;";
        $c[]="box-shadow:none !important;text-shadow:none !important;font-size:11px;font-weight:600;";
        $c[]="padding:6px 16px;text-transform:none;";
        $c[]="-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px)}";
        $c[]=".svcstat .btn:hover,.svcstat label.btn:hover,.svcstat .btn:focus,.svcstat label.btn:focus";
        $c[]="{background:rgba(255,255,255,.24) !important;color:#fff !important}";
        $c[]=$this->svctile_pale_css();
        $c[]="</style>";
        return @implode("\n",$c);
    }

    /** Correspondance couleur logique -> classe de degrade de la tuile. */
    private function svctile_class($color=null):string{
        if($color=="red"){return "svctile-red svctile-pale";}
        if($color=="yellow"){return "svctile-yellow svctile-pale";}
        if($color=="green"){return "svctile-green svctile-pale";}
        return "svctile-grey svctile-pale";
    }

    /**
     * Palettes pales a filigrane (maquette stitch_palette_tuiles_rouge_orange): fond
     * teinte-50, bordure teinte-200, filigrane teinte-500 a 12-14 %, libelle en capitales
     * colore, valeur en teinte-950, bouton plein teinte-600. Rouge = rose, jaune = ambre
     * (maquette), gris = ardoise et vert = emeraude dans les memes variantes.
     * Le vert suit la marque (brand_ui_accents): identique sur un boitier de serie,
     * recolore en marque blanche. blue = bleu, lazur = cyan; seul black garde son degrade sature.
     * Les regles portent sur .svctile-pale, ajoutee EN PLUS de la classe de teinte par
     * svctile_class()/svcbar_class(): elles surchargent la feuille "vitre" sans la toucher.
     */
    private function svctile_pale_css():string{
        $tones=array(
            "red"=>array("bg"=>"#fff1f2","bd"=>"#fecdd3","wm"=>"rgba(244,63,94,.12)","soft"=>"#ffe4e6",
                "acc"=>"#e11d48","cap"=>"#e11d48","num"=>"#4c0519","dot"=>"#f43f5e",
                "btn"=>"#e11d48","btnh"=>"#be123c","sep"=>"rgba(254,205,211,.6)"),
            "yellow"=>array("bg"=>"#fffbeb","bd"=>"#fde68a","wm"=>"rgba(245,158,11,.14)","soft"=>"#fef3c7",
                "acc"=>"#d97706","cap"=>"#b45309","num"=>"#451a03","dot"=>"#f59e0b",
                "btn"=>"#d97706","btnh"=>"#b45309","sep"=>"rgba(253,230,138,.6)"),
            "grey"=>array("bg"=>"#f8fafc","bd"=>"#e2e8f0","wm"=>"rgba(100,116,139,.12)","soft"=>"#f1f5f9",
                "acc"=>"#64748b","cap"=>"#64748b","num"=>"#1e293b","dot"=>"#94a3b8",
                "btn"=>"#475569","btnh"=>"#334155","sep"=>"rgba(226,232,240,.8)"),
            "blue"=>array("bg"=>"#eff6ff","bd"=>"#bfdbfe","wm"=>"rgba(59,130,246,.12)","soft"=>"#dbeafe",
                "acc"=>"#2563eb","cap"=>"#1d4ed8","num"=>"#172554","dot"=>"#3b82f6",
                "btn"=>"#2563eb","btnh"=>"#1d4ed8","sep"=>"rgba(191,219,254,.6)"),
            "lazur"=>array("bg"=>"#ecfeff","bd"=>"#a5f3fc","wm"=>"rgba(6,182,212,.12)","soft"=>"#cffafe",
                "acc"=>"#0891b2","cap"=>"#0e7490","num"=>"#083344","dot"=>"#06b6d4",
                "btn"=>"#0891b2","btnh"=>"#0e7490","sep"=>"rgba(165,243,252,.6)"),
            "green"=>array("bg"=>"#ecfdf5","bd"=>"#a7f3d0","wm"=>"rgba(16,185,129,.14)","soft"=>"#d1fae5",
                "acc"=>"#059669","cap"=>"#047857","num"=>"#022c22","dot"=>"#10b981",
                "btn"=>"#059669","btnh"=>"#047857","sep"=>"rgba(167,243,208,.6)"),
        );
        // Marque blanche: le vert se derive de l'accent de la marque. Garde function_exists:
        // class.brand.inc peut manquer la ou il n'est pas encore deploye.
        if(function_exists("brand_palette") && function_exists("brand_ui_accents") && function_exists("brand_rgba")){
            $bp=brand_palette();
            if(!empty($bp["colors_on"])){
                $ac=brand_ui_accents($bp);
                $bg=brand_rgba($ac["accent"],".08");
                $wm=brand_rgba($ac["accent"],".14");
                if($bg<>"" && $wm<>""){
                    $tones["green"]=array("bg"=>$bg,"bd"=>$ac["accent_line"],"wm"=>$wm,"soft"=>$ac["accent_soft"],
                        "acc"=>$ac["accent"],"cap"=>$ac["accent_dark"],"num"=>"#1e293b","dot"=>$ac["accent"],
                        "btn"=>$ac["accent"],"btnh"=>$ac["accent_dark"],"sep"=>$ac["accent_line"]);
                }
            }
        }
        $c=array();
        foreach($tones as $tone=>$t){
            $c[]=".svctile-$tone.svctile-pale{--p-bg:{$t["bg"]};--p-bd:{$t["bd"]};--p-wm:{$t["wm"]};--p-soft:{$t["soft"]};"
                ."--p-acc:{$t["acc"]};--p-cap:{$t["cap"]};--p-num:{$t["num"]};--p-dot:{$t["dot"]};"
                ."--p-btn:{$t["btn"]};--p-btnh:{$t["btnh"]};--p-sep:{$t["sep"]}}";
        }
        // Fond pose sur du blanc: une teinte rgba (marque) ne laisse pas transparaitre la page.
        $c[]="div.svctile-pale{background:linear-gradient(var(--p-bg),var(--p-bg)),#fff;border:1px solid var(--p-bd);";
        $c[]="color:var(--p-num);box-shadow:0 1px 2px rgba(15,23,42,.05)}";
        $c[]="div.svctile-pale:hover{box-shadow:0 2px 6px rgba(15,23,42,.08)}";
        $c[]="div.svctile-pale:before{display:none}";
        $c[]=".svctile-pale .svctile-wm,.svctile-pale .svcstat-wm{color:var(--p-wm);opacity:1}";
        $c[]=".svctile-pale .svctile-badge{background:var(--p-soft);border-color:var(--p-bd);color:var(--p-acc);";
        $c[]="-webkit-backdrop-filter:none;backdrop-filter:none}";
        $c[]=".svctile-pale .svctile-badge i,.svctile-pale .svctile-badge svg{color:var(--p-acc)}";
        $c[]=".svctile-pale .svcbar-ico,.svctile-pale .svcbar-ico i,.svctile-pale .svcbar-ico svg{color:var(--p-acc);filter:none}";
        $c[]=".svctile-pale .svctile-title,.svctile-pale .svcstat-label,.svctile-pale .svcbar-explain,.svctile-pale .svctile-sparkl{color:var(--p-cap);opacity:1;font-weight:700}";
        $c[]=".svctile-pale .svctile-state,.svctile-pale .svcstat-num,.svctile-pale .svcbar-number{color:var(--p-num);font-weight:700}";
        $c[]=".svctile-pale h1,.svctile-pale h2,.svctile-pale h3,.svctile-pale h4{color:var(--p-num)}";
        $c[]=".svctile-pale .svctile-dot{background:var(--p-dot)}";
        $c[]=".svctile-pale .svctile-glass{background:rgba(255,255,255,.65);border-color:var(--p-bd);-webkit-backdrop-filter:none;backdrop-filter:none}";
        $c[]=".svctile-pale table,.svctile-pale td,.svctile-pale .svctile-kpi-l,.svctile-pale .svctile-kpi-v,.svctile-pale .svctile-meta{color:var(--p-num)}";
        $c[]=".svctile-pale .svctile-kpi td+td{border-left-color:var(--p-bd)}";
        $c[]=".svctile-pale .svctile-actions,.svctile-pale .svcstat-act{border-top:1px solid var(--p-sep);padding-top:10px}";
        $c[]=".svctile-pale .btn,.svctile-pale label.btn{background:var(--p-btn) !important;border:1px solid var(--p-btn) !important;";
        $c[]="color:#fff !important;-webkit-backdrop-filter:none;backdrop-filter:none;box-shadow:0 1px 2px rgba(15,23,42,.06) !important}";
        $c[]=".svctile-pale .btn:hover,.svctile-pale label.btn:hover,.svctile-pale .btn:focus,.svctile-pale label.btn:focus";
        $c[]="{background:var(--p-btnh) !important;border-color:var(--p-btnh) !important;color:#fff !important}";
        $c[]=".svctile-pale .btn i,.svctile-pale .btn svg,.svctile-pale label.btn i,.svctile-pale label.btn svg{color:#fff}";
        // Les pages ecrivent encore leur sous-texte en blanc, du temps des tuiles a degrade sature
        // (<small style='color:white'>, ~41 pages) : blanc sur fond pale = illisible. Un style
        // inline ne se bat qu'avec !important. Boutons, labels et badges gardent leur blanc :
        // ils portent leur propre fond colore.
        $white=array("[style*='color:white' i]","[style*='color: white' i]","[style*='color:#fff' i]",
            "[style*='color: #fff' i]","[style*='color:rgb(255,255,255)' i]","[style*='color: rgb(255, 255, 255)' i]",".text-white");
        $sel=array();$keep=array();
        foreach($white as $w){
            $sel[]=".svctile-pale $w:where(:not(.btn):not(.label):not(.badge))"; // :where() = specificite nulle, la regle $keep (0,3,0) gagne dans un bouton
            foreach(array(".btn",".label",".badge") as $own){$keep[]=".svctile-pale $own$w,.svctile-pale $own $w";}
        }
        $c[]=@implode(",",$sel)."{color:var(--p-num) !important}";
        $c[]=@implode(",",$keep)."{color:#fff !important}";
        return @implode("\n",$c);
    }

    /**
     * Couleur -> classe de degrade partagee avec les tuiles.
     * Accepte les deux conventions du code existant: le nom logique de widget_h()
     * ("green", "red", ...) et la classe CSS de widget_style1() ("navy-bg", "red-bg", ...).
     * Retourne null quand aucune couleur n'est demandee ("none"/null) ou qu'elle est
     * inconnue: l'appelant veut alors un widget transparent, on ne lui impose pas de
     * fond vitre (sinon texte blanc sur fond blanc).
     */
    private function svcbar_class($background){
        $map=array(
            "green"=>"svctile-green svctile-pale", "navy"=>"svctile-green svctile-pale",
            "lazur"=>"svctile-lazur svctile-pale", "yellow"=>"svctile-yellow svctile-pale",
            "red"=>"svctile-red svctile-pale",     "blue"=>"svctile-blue svctile-pale",
            "gray"=>"svctile-grey svctile-pale",   "grey"=>"svctile-grey svctile-pale",
            "black"=>"svctile-black");
        if(is_null($background)){return null;}
        $background=strtolower(trim($background));
        // "navy-bg" -> "navy"
        $background=preg_replace("#-bg$#","",$background);
        if(!isset($map[$background])){return null;}
        return $map[$background];
    }

    /** Filigrane d'icone en haut a droite (design: opacite 16%, 110px). */
    private function svctile_watermark($ico):string{
        if(!is_string($ico) || $ico===""){$ico="fa far fa-times-circle";}
        return "<i class='$ico svctile-wm'></i>";
    }

    /** Pastille d'icone centree, verre depoli (design: 48px footprint). */
    private function svctile_badge($ico):string{
        if(!is_string($ico) || $ico===""){$ico="fa far fa-times-circle";}
        return "<div class='svctile-badge'><i class='$ico'></i></div>";
    }

    /** Titre du service + etat principal, avec la pastille pulsante "live". */
    private function svctile_head($title,$state,$livedot=false):string{
        $class="svctile-state";
        if(strlen(strip_tags($state))>22){$class.=" svctile-state-sm";}
        $dot="";
        if($livedot){$dot="<span class='svctile-dot'></span>";}
        $h[]="<div class='svctile-title'>$title</div>";
        $h[]="<div><span class='$class'>$state</span>$dot</div>";
        return @implode("\n",$h);
    }

    /**
     * Compacte une duree d'uptime pour tenir dans une colonne du panneau KPI.
     * "3 Days, 16 hours, 49 minutes" -> "3d,16h,49mn"
     * Les abreviations restent neutres (pas de token de traduction): la valeur doit
     * garder une largeur previsible quelle que soit la langue.
     */
    private function svctile_uptime($uptime):string{
        if(is_null($uptime)){return "";}
        if(strlen(trim($uptime))==0){return "";}
        // L'ordre compte: "month" doit etre teste avant "min" ("months" vs "minutes").
        $units=array("year"=>"y","month"=>"mo","week"=>"w","day"=>"d","hour"=>"h","min"=>"mn","sec"=>"s");
        $out=array();
        if(preg_match_all('#([0-9]+)\s*([a-zA-Z]+)#',$uptime,$re,PREG_SET_ORDER)){
            foreach($re as $m){
                $number=$m[1];
                $unit=strtolower($m[2]);
                foreach($units as $needle=>$abbr){
                    if(strpos($unit,$needle)===0){
                        $out[]=$number.$abbr;
                        break;
                    }
                }
                // 3 unites suffisent: au-dela la valeur deborde de la colonne KPI.
                if(count($out)>=3){break;}
            }
        }
        if(count($out)==0){return $uptime;}
        return @implode(",",$out);
    }

    /** Panneau "glass" 2 colonnes (uptime / memoire). */
    private function svctile_kpi($label1,$value1,$label2,$value2):string{
        $h[]="<div class='svctile-glass svctile-kpi'><table><tr>";
        $h[]="<td><div class='svctile-kpi-l'>$label1</div><div class='svctile-kpi-v'>$value1</div></td>";
        $h[]="<td><div class='svctile-kpi-l'>$label2</div><div class='svctile-kpi-v'>$value2</div></td>";
        $h[]="</tr></table></div>";
        return @implode("\n",$h);
    }

    /** Ligne de metadonnees: intitule a gauche, indicateur de sante a droite. */
    private function svctile_meta($left,$right=null):string{
        $r="";
        if($right<>null){$r="<span class='svctile-meta-r'>$right</span>";}
        return "<div class='svctile-meta'><span class='svctile-meta-l'>$left</span>$r</div>";
    }

    /** Zone sparkline (peity) avec son intitule. */
    private function svctile_spark($label,$content):string{
        if(is_null($content)){return "";}
        if(strlen($content)==0){return "";}
        $h[]="<div class='svctile-sparkl'>$label</div>";
        $h[]="<div class='svctile-spark'>$content</div>";
        return @implode("\n",$h);
    }

    /** Barre d'actions basse: les boutons heritent du style "glass" via le CSS de la tuile. */
    private function svctile_actions($buttons):string{
        if(is_array($buttons)){$buttons=@implode("\n",$buttons);}
        if(is_null($buttons)){return "";}
        if(strlen(trim($buttons))==0){return "";}
        return "<div class='svctile-actions'>$buttons</div>";
    }

    /** Assemblage final d'une tuile: filigrane + contenu, largeur et hauteur pilotees par l'appelant. */
    private function svctile_wrap($color,$ico,$inside,$width="335px",$minheight=240):string{
        $class=$this->svctile_class($color);
        $h[]="<div style='vertical-align:top;width:$width'>";
        $h[]="<div class='svctile $class' style='min-height:{$minheight}px'>";
        $h[]=$this->svctile_watermark($ico);
        $h[]="<div class='svctile-in'>$inside</div>";
        $h[]="</div>";
        $h[]="</div>";
        return @implode("\n",$h);
    }

    private function widget_engine($title,$text,$button=array(),$opt_ico=null,$size=0,$color=null){

        $html=array();
        $inside=array();
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        // Titre et etat recoivent des valeurs de base / REST (nom de regle, login).
        $title=html_safe_fragment($title);
        $text=html_safe_fragment($text);
        $csize=335;
        if($size>0){$csize=$size;}
        $bt_color=null;
        $ico="fa far fa-times-circle";

        if($color=="red"){
            $bt_color="red";
            $ico="fas fa-exclamation-triangle";
        }
        if($color=="yellow"){
            $bt_color="yellow";
            $ico="fas fa-exclamation-triangle";
        }
        if($color=="green"){
            $bt_color="green";
            $ico="fas fa-thumbs-up";
        }

        if(is_string($opt_ico) && $opt_ico!==""){$ico=$opt_ico;}
        $html[]="<!-- ICO: $ico -->";

        if(!is_array($button)) {
            if(!is_null($button)) {
                if (preg_match("#ico:(.+)#", $button, $re)) {
                    if (strpos($re[1], ",") > 0) {
                        $icoz = explode(",", $re[1]);
                        $rdom = rand(0, count($icoz) - 1);
                        $re[1] = $icoz[$rdom];
                    }
                    $ico = $re[1];
                    $button = array();
                }
            }
        }
        //$btn=array("name"=>"{download}","js"=>$js,"icon"=>ico,"color"=>optional);

        $inside[]=$this->svctile_badge($ico);
        $inside[]=$this->svctile_head($title,$text,($color=="green"));
        $inside[]=$this->svctile_actions($this->parse_button($button,$bt_color));

        $html[]=$this->svctile_wrap($color,$ico,@implode("\n",$inside),"{$csize}px",257);

        return $this->svctile_css().$this->_ENGINE_parse_body(@implode("\n", $html))."<script>NoSpinner()</script>";

    }

    public function widget_vert($title,$text,$button=array(),$opt_ico=null,$size=0):string{
        return $this->widget_engine($title,$text,$button,$opt_ico,$size,"green");
    }

    public function widget_rouge($title,$text,$button=array(),$opt_ico=null,$size=0):string{
        return $this->widget_engine($title,$text,$button,$opt_ico,$size,"red");
    }

    public function widget_jaune($title,$text,$button=array(),$opt_ico=null,$size=0):string{
        return $this->widget_engine($title,$text,$button,$opt_ico,$size,"yellow");
    }

    public function widget_grey($title,$text,$button=array(),$opt_ico=null,$size=0):string{
        return $this->widget_engine($title,$text,$button,$opt_ico,$size);
    }

    private function parse_button($array,$forceColor=null):string{
        $btbg=$this->btn_array();
        $button_array=array();
        if(!is_array($array)){$array=array();}
        if(count($array)==0){return "";}
        foreach ($array as $index=>$main){
            $margin=30;
            if(!isset($main["name"])){continue;}
            if(isset($main["margin"])){
                $margin=$main["margin"];
            }

            $bg=null;
            if($forceColor<>null){$bg=$btbg[$forceColor];}
            if(isset($main["color"])){$bg=$btbg[$main["color"]];}
            //$btn=array("name"=>"{download}","js"=>$js,"icon"=>ico,"color"=>optional);
            //$btname,$js,$icon_class,$security=null,$width=0,$class="btn-primary",$btsize=null
            $button1=$this->button_autnonome($main["name"], $main["js"], $main["icon"],null,0,$bg);
            $button1="<!-- color=[$bg/$forceColor] --><div style='margin-top:{$margin}px' id='$index'>$button1</div>";
            $button_array[]=$button1;
        }

        if(count($button_array)==0){return "";}

        if(count($button_array)==1){return @implode("\n",$button_array);}
        $s[]="<div class='center'><table style='width:5%'>";

        $i=0;
        foreach ($button_array as $btn){
            $i++;
            if($i==1){$s[]="<tr>";}
            $s[]="<td>$btn</td>";
            if($i==3){
                $i=0;
                $s[]="</tr>";
            }
        }
        if($i<4){
            $colspan=3-$i;
            if($colspan>1) {
                $s[] = "<td colspan='$colspan'>&nbsp;</td>";
                $s[] = "</tr>";
            }else{
                $s[] = "<td>&nbsp;</td>";
                $s[] = "</tr>";
            }
        }
        $s[]="</table></div>";
        return @implode("\n",$s);
    }



    public function spinner(){
        return "<div class='lds-roller'><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>";
    }
    public function widget_wizard($title,$subtitle,$explain,$cmdon=null,$checkliscense=false){

        $id=md5(time()."$cmdon$title");
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/wizard.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/wizard.log";
        $ARRAY["CMD"]=$cmdon;
        // Every wizard (fw.system.wizards.php) installs/uninstalls services.
        $ARRAY["PRIV"]="AsSystemAdministrator";
        $ARRAY["TITLE"]=$title;
        $ARRAY["AFTER"]="document.location.href='/features';";
        $prgress=base64_encode(serialize($ARRAY));
        $js="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-$id')";

        $button="<button style=\"text-transform: capitalize;\" class=\"btn btn-primary btn-sm\" OnClick=\"$js\">
            <i class='fas fa-magic'></i> {run_the_wizard}</button>";

        if($checkliscense){
            $users=new usersMenus();
            if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
                $button="<button style=\"text-transform: capitalize;\" class=\"btn btn-white btn-sm\" OnClick=\"blur()\">
            <i class='fas fa-magic'></i> {license_invalid}</button>";
            }

        }


        $explain=$this->_ENGINE_parse_body($explain);
        $explain=str_replace("%s",$_SERVER["SERVER_NAME"],$explain);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        // SERVER_NAME vient de l'en-tete Host de la requete.
        $explain=html_safe_fragment($explain);
        $title=html_safe_fragment($title);
        $subtitle=html_safe_fragment($subtitle);

        $html="<div class=\"row\">
                    <div class=\"col-md-7 b-r-xl bg-muted\" style='padding:20px'>

                                    <h2 class=\"font-bold m-b-xs\">
                                        $title
                                    </h2>
                                    <p>$subtitle</p>
                                    <div class=\"small text-muted\">$explain</div>
                                    <div id='progress-$id' style='margin:5px'></div>
                                    <hr>

                                    <div>
                                        <div class=\"btn-group\">
                                        $button
                                            
                                            
                                        </div>
                                    </div>



                                </div>
                            </div>

                    </div>
                    <div class=\"ibox-footer\">&nbsp;</div>
            </div>";

        return $html;


    }


    public function squid_admin_mysql($severity,$subject,$text=null,$file=null,$line=0){

        $ArticaNotifsMaxTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaNotifsMaxTime"));
        if($ArticaNotifsMaxTime==0){$ArticaNotifsMaxTime=7;}
        $removeafter=strtotime("+$ArticaNotifsMaxTime day");

        $function=null;
        $traces=array();
        $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
        if($severity<2){

            $error_get_last=error_get_last();
            if(isset($error_get_last["line"])) {
                if(intval($error_get_last["line"])>0) {
                    $traces[] = "Last error {$error_get_last["message"]} line {$error_get_last["line"]} in file " .
                        basename($error_get_last["file"]);
                }
            }

            if(function_exists("debug_backtrace")){
                $trace=debug_backtrace();
                foreach ($trace as $index=>$ligne){$file=basename($ligne["file"]);$line=$ligne["line"];
                    $function=$ligne["function"];$traces[]="Line $line in $function() in file $file";}
            }

        }

        $text=$text."\n".@implode("\n", $traces)."\n";
        $text=str_replace("'","`",$text);
        $text=$q->sqlite_escape_string2($text);
        $subject=$q->sqlite_escape_string2($subject);
        $zdate = time();
        $file=basename(__FILE__);
        $q->QUERY_SQL("INSERT OR IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`removeafter`) VALUES ('$zdate','$text','$subject','$function','$file','$line','$severity','$removeafter')");
        if(!$q->ok){writelogs("SQL ERROR $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
    }

    // -----------------------------------------------------------------------
    // Design "svcst" des etats de service (SERVICE_STATUS, _STOPPED, _DISABLED):
    // carte blanche de la maquette Stitch 031, reprise de pfx_st_service_card()
    // (fw.postfix.status.php?left-postfix-status=0). L'accent vient de la marque
    // (brand_ui_accents), jamais d'un vert en dur: rien ne change sur un boitier
    // de serie, tout se recolore en marque blanche.
    // -----------------------------------------------------------------------

    /** Feuille de style, une fois par requete, a concatener HORS _ENGINE_parse_body(). */
    private function svcst_css():string{
        if(isset($GLOBALS["SVCST_CSS_DONE"])){return "";}
        $GLOBALS["SVCST_CSS_DONE"]=true;
        $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
            "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
        if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
            $ac=brand_ui_accents(brand_palette());
        }
        $a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];$al=$ac["accent_line"];
        return "<style>
.svcst-wrap{vertical-align:top;margin-top:2px;margin-bottom:10px;max-width:100%}
.svcst-card{position:relative;overflow:hidden;background:#fff;border:1px solid #e2e8f0;border-radius:6px;box-shadow:0 1px 2px rgba(15,23,42,.06);padding:20px;text-align:center;color:#334155;box-sizing:border-box}
.svcst-card *{box-sizing:border-box}
.svcst-card.svcst-tone-err{border:3px solid #e11d48;box-shadow:0 1px 3px rgba(225,29,72,.18)}
.svcst-caps{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
.svcst-dot{display:inline-block;width:10px;height:10px;border-radius:50%;background:$a;margin-left:8px;vertical-align:middle}
.svcst-dot-pulse{animation:svcstPulse 2s ease-in-out infinite}
.svcst-dot-err{background:#e11d48}
.svcst-dot-off{background:#94a3b8}
@keyframes svcstPulse{0%,100%{opacity:1}50%{opacity:.35}}
.svcst-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#f1f5f9;border:1px solid #cbd5e1;color:#334155;border-radius:4px;padding:6px 4px;font-size:11px;font-weight:600;cursor:pointer;line-height:1.4;text-transform:capitalize;box-shadow:0 1px 1px rgba(15,23,42,.04)}
.svcst-btn:hover{background:#e2e8f0}
.svcst-btn-primary{background:$ad;border-color:$ad;color:#fff}
.svcst-btn-primary:hover{background:$ad;border-color:$ad;color:#fff;filter:brightness(.9)}
.svcst-btn-danger{background:#e11d48;border-color:#e11d48;color:#fff}
.svcst-btn-danger:hover{background:#be123c;border-color:#be123c;color:#fff}
.svcst-btn-block{width:100%;padding:5px 12px;font-size:12px}
.svcst-ico{display:inline-flex;align-items:center;justify-content:center;width:56px;height:48px;border-radius:4px;font-size:24px;background:$as;border:1px solid $al;color:$ad}
.svcst-tone-err .svcst-ico{background:#fff1f2;border-color:#fecdd3;color:#e11d48}
.svcst-tone-off .svcst-ico{background:#f1f5f9;border-color:#e2e8f0;color:#94a3b8}
.svcst-name{margin-top:12px;word-break:break-word}
.svcst-state{font-size:26px;font-weight:700;color:#1e293b;margin-top:2px}
.svcst-tone-err .svcst-state{color:#be123c}
.svcst-tone-off .svcst-state{color:#64748b}
.svcst-kpi{display:grid;grid-template-columns:1fr 1fr;margin-top:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:10px 4px;text-align:left}
.svcst-kpi>div{min-width:0;padding:0 8px}
.svcst-kpi>div+div{border-left:1px solid #e2e8f0}
.svcst-kpi-l{font-size:10px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.svcst-kpi-v{font-size:13px;font-weight:700;color:#1e293b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.svcst-meta{display:flex;justify-content:space-between;gap:8px;font-size:11px;color:#475569;margin-top:12px;padding-top:8px;border-top:1px solid #f1f5f9;text-align:left}
.svcst-meta strong{color:#1e293b}
.svcst-meta-r{font-style:italic;font-weight:600;color:$ad;white-space:nowrap}
.svcst-spark{margin-top:12px;text-align:left}
.svcst-spark-bars{display:flex;align-items:flex-end;gap:3px;height:38px;margin-top:6px;padding:4px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px}
.svcst-spark-bars span{flex:1 1 0;min-width:2px;background:$a;border-radius:1px 1px 0 0}
.svcst-spark-bars span:last-child{background:$a;opacity:.55}
.svcst-btns{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px}
.svcst-actions{margin-top:8px}
.svcst-actions .svcst-btn+.svcst-btn{margin-top:8px}
</style>";
    }

    /** Bouton de carte. $js est le JS existant (Loadjs, framework_buildjs), toujours entre apostrophes. */
    private function svcst_button($label,$js,$icon,$class=""):string{
        if(is_null($js) || $js==""){return "";}
        $c=$class==""?"":" $class";
        $ic=$icon==""?"":"<i class='$icon'></i>";
        return "<button type='button' class='svcst-btn$c' OnClick=\"$js\">$ic<span>$label</span></button>";
    }

    /** Courbe memoire: uniquement les vrais points de ressources/pmemory/<service>, jamais decoratifs. */
    private function svcst_spark($service_name):string{
        $service_name=(string)$service_name;
        if($service_name=="" || !preg_match('#^[A-Za-z0-9_.-]+$#',$service_name)){return "";}
        $path="/usr/share/artica-postfix/ressources/pmemory/$service_name";
        if(!is_file($path)){return "";}
        $vals=array();
        foreach(explode(";",(string)@file_get_contents($path)) as $v){
            $v=trim($v);
            if($v!=="" && is_numeric($v)){$vals[]=floatval($v);}
        }
        if(count($vals)<2){return "";}
        if(count($vals)>30){$vals=array_slice($vals,-30);}
        $max=max($vals);
        $min=min($vals);
        if($max<=0){return "";}
        // Memoire stable (ecart < 5 % du maximum): rangee a mi-hauteur; sinon min..max sur 20..95 %.
        $flat=(($max-$min)/$max)<0.05;
        $bars=array();
        foreach($vals as $v){
            $h=$flat?50:intval(round(20+(($v-$min)/($max-$min))*75));
            $bars[]="<span style='height:$h%'></span>";
        }
        return "<div class='svcst-spark'><div class='svcst-caps'>{graph}: {memory}</div>"
            ."<div class='svcst-spark-bars'>".implode("",$bars)."</div></div>";
    }

    /** Carte: icone, nom en petites capitales, etat avec pastille, puis le corps ($body). */
    private function svcst_card($tone,$ico,$title,$label,$body,$width="335px"):string{
        $dot="svcst-dot svcst-dot-pulse";
        if($tone=="err"){$dot="svcst-dot svcst-dot-err";}
        if($tone=="off"){$dot="svcst-dot svcst-dot-off";}
        $h=array();
        $h[]="<div class='svcst-wrap' style='width:$width'>";
        $h[]="<div class='svcst-card svcst-tone-$tone'>";
        $h[]="<div class='svcst-ico'><i class='$ico'></i></div>";
        $h[]="<div class='svcst-caps svcst-name'>$title</div>";
        $h[]="<div class='svcst-state'>$label<span class='$dot'></span></div>";
        $h[]=$body;
        $h[]="</div>";
        $h[]="</div>";
        return @implode("\n",$h);
    }

    public function SERVICE_STATUS_DISABLED($title):string{

        if(preg_match("#APP_SHELLINABOX_#",$title)){
            $title="{APP_SHELLINABOX}";
        }
        return $this->svcst_css().$this->svcst_card("off","far fa-times-circle",$title,"{disabled}","");

    }
    public function SERVICE_STATUS_STOPPED($title,$button=null):string{

        $body="";
        if(!is_null($button) && $button<>""){
            $body="<div class='svcst-actions'>$button</div>";
        }
        return $this->svcst_css().$this->svcst_card("err","fas fa-exclamation-triangle",$title,"{stopped}",$body);

    }



    public function SERVICE_STATUS($ini,$MASTER_KEY,$restartbutton_js=null,$forceversion=null,$refreshjs=null):string{
        $styleKey=null;
        $styleKeydata=null;
        $html=array();
        // Recupere les CSS AVANT de construire la carte: elles ne doivent jamais traverser
        // _ENGINE_parse_body(), et leurs drapeaux globaux les rendent idempotentes.
        // svctile_css() reste emise: widget_vert/jaune/rouge/grey en dependent (son drapeau
        // pose ici, la jeter les laisserait sans style).
        $svccss=$this->svctile_css().$this->svcst_css();
        $users=new usersMenus();
        if(!$users->AsSystemAdministrator){$restartbutton_js=null;}


        if(preg_match("#^(.+?):(.+)#",$MASTER_KEY,$re)){
            $MASTER_KEY=$re[1];
            $opts=$re[2];
            if(preg_match("#^(.+?)=(.+)#",$opts,$re)){
                $styleKey=strtolower($re[1]);
                $styleKeydata=$re[2];
            }
        }
        $widh="335px";
        if($styleKey=="width"){$widh=$styleKeydata;}

        $version=null;
        VERBOSE("SERVICE_STATUS:: Checking $MASTER_KEY",__LINE__);

        if(!isset($ini->_params[$MASTER_KEY])){
            VERBOSE("SERVICE_STATUS:: $MASTER_KEY did not found on ini->_params[", __LINE__);
            if($GLOBALS["VERBOSE"]){print_r($ini->_params);}
            // On rend $svccss et non "": les drapeaux globaux sont poses, donc les
            // feuilles ne seront plus jamais emises pour cette requete. Les jeter ici
            // laisse tous les widgets suivants (widget_vert/jaune/rouge/grey,
            // widget_h...) sans style. Vu sur /system-watchdog quand APP_MONIT est absent.
            return $svccss;
        }

        $ARRAY=$ini->_params[$MASTER_KEY];
        if($GLOBALS["VERBOSE"]) {
            foreach ($ARRAY as $key => $val) {
                VERBOSE("SERVICE_STATUS:: $MASTER_KEY $key=$val",__LINE__);
            }
        }

        if($MASTER_KEY=="C-ICAP"){$MASTER_KEY="APP_C_ICAP";}

        VERBOSE("SERVICE_STATUS:: $MASTER_KEY OK",__LINE__);
        if($ARRAY["service_disabled"]==0){
            $title="{".$MASTER_KEY."}";
            if(preg_match("#APP_SHELLINABOX_#",$title)){$title="{APP_SHELLINABOX}";}
            $html[]=$this->svcst_card("off","far fa-times-circle",$title,"{disabled}","",$widh);
            return $svccss.$this->_ENGINE_parse_body(@implode("\n", $html))."<script>NoSpinner()</script>";
        }

        if($forceversion<>null){
            $version="&nbsp;v{$forceversion}";
        }

        if($ARRAY["running"]==0){
            $btns=array();
            if($restartbutton_js<>null){
                $btns[]=$this->svcst_button("{restart}",$restartbutton_js,"fas fa-sync-alt","svcst-btn-block svcst-btn-danger");
                if($refreshjs<>null){
                    $btns[]=$this->svcst_button("{refresh}",$refreshjs,"fas fa-sync-alt","svcst-btn-block");
                }
            }
            $MASTER_TITLE=$MASTER_KEY;
            if(preg_match("#APP_SHELLINABOX_#",$MASTER_TITLE)){
                $MASTER_TITLE="APP_SHELLINABOX";
            }
            $title="{{$MASTER_TITLE}}$version";
            if(strlen((string)$ini->ChangetitleTo)>1){
                $title=$ini->ChangetitleTo.$version;
            }
            $body=count($btns)>0?"<div class='svcst-actions'>".@implode("\n",$btns)."</div>":"";
            $html[]=$this->svcst_card("err","fas fa-exclamation-triangle",$title,"{stopped}",$body,$widh);
            return $svccss.$this->_ENGINE_parse_body(@implode("\n", $html))."<script>NoSpinner();</script>";
        }

        $service_name=isset($ARRAY["service_name"])?$ARRAY["service_name"]:"";
        $MASTER_TITLE="{{$MASTER_KEY}}";
        if(preg_match("#^APP_#",$service_name)){
            $MASTER_TITLE="{{$service_name}}";
        }
        if(preg_match("#APP_SHELLINABOX_#",$MASTER_TITLE)){
            $MASTER_TITLE="{APP_SHELLINABOX}";
        }
        if(property_exists($ini,"ChangetitleTo")) {
            if (!is_null($ini->ChangetitleTo)) {
                if (strlen($ini->ChangetitleTo) > 1) {
                    $MASTER_TITLE = $ini->ChangetitleTo;
                }
            }
        }

        $inside=array();
        $uptime=$this->svctile_uptime($ARRAY["uptime"]);
        // FormatBytes() renvoie deja du HTML (chiffre + &nbsp; + unite): ne pas l'echapper.
        $mem=FormatBytes($ARRAY["master_memory"]);
        $inside[]="<div class='svcst-kpi'>";
        $inside[]="<div><div class='svcst-kpi-l'>{uptime}</div><div class='svcst-kpi-v' title=\"$uptime\">$uptime</div></div>";
        $inside[]="<div><div class='svcst-kpi-l'>{memory}</div><div class='svcst-kpi-v'>$mem</div></div>";
        $inside[]="</div>";

        // Ligne de metadonnees: descripteurs de fichiers consommes + sante deduite.
        if(isset($ARRAY["maxfd"])){
            $health="";
            if(intval($ARRAY["maxfd"])>0){
                $percent=100-((intval($ARRAY["curfd"])/intval($ARRAY["maxfd"]))*100);
                if($percent<0){$percent=0;}
                $health="<span class='svcst-meta-r'>".sprintf("%.1f",$percent)."% {health}</span>";
            }
            $inside[]="<div class='svcst-meta'><span>{files}: <strong>{$ARRAY["curfd"]}/{$ARRAY["maxfd"]}</strong></span>$health</div>";
        }

        $inside[]=$this->svcst_spark($service_name);

        if(!isset($ARRAY["Nograph"])) {
            $inside[]="<div class='svcst-btns'>";
            $inside[]=$this->svcst_button("{graph} {memory}","Loadjs('fw.rrd.micro.php?key=$MASTER_KEY');",ico_mem);
            $inside[]=$this->svcst_button("{graph} {cpu}","Loadjs('fw.rrd.micro.php?key=$MASTER_KEY');",ico_cpu);
            $inside[]="</div>";
        }
        $btns=array();
        if($restartbutton_js<>null){
            $btns[]=$this->svcst_button("{restart}",$restartbutton_js,"fas fa-sync-alt","svcst-btn-block svcst-btn-primary");
        }
        if($refreshjs<>null){
            $btns[]=$this->svcst_button("{refresh}",$refreshjs,"fas fa-sync-alt","svcst-btn-block");
        }
        if(count($btns)>0){
            $inside[]="<div class='svcst-actions'>".@implode("\n",$btns)."</div>";
        }

        $html[]=$this->svcst_card("ok","fas fa-thumbs-up","$MASTER_TITLE$version","{running}",@implode("\n",$inside),$widh);

        return $svccss.$this->_ENGINE_parse_body(@implode("\n", $html))."<script>NoSpinner();</script>";

    }


    function widget_h($background="green",$icon="fa-cloud",$number="0 {items}",$explain=null,$button=array(),$button2=array()){
        //fas fa-engine-warning
        //fas fa-database
        //fas fa-thumbs-down
        //fas fa-thumbs-up
        //fas fa-exclamation-circle
        //fas fa-database // <i class="fad fa-hdd"></i>
        //far fa-ethernet
        //fas fa-users
        //fas fa-percent
        //fas fa-signal
        // fad fa-shield-alt
        // fa fa-wrench
        // fas fa-plug
        $min_height=null;
        $min_width=null;

        if(preg_match("#minheight:(.+?):(.+)#",$background,$re)){
            $min_height="min-height: $re[1]";
            $background=$re[2];
        }
        if(preg_match("#minwidth:([0-9]+):(.+)#",$background,$re)){
            $min_width=";min-width: $re[1]px";
            $background=$re[2];
        }

        $button_html="";
        $button_help="";
        $explain=$this->_ENGINE_parse_body($explain);
        // Pentest 2026-11-04 : valeur inseree sans encodage (XSS).
        $explain=html_safe_fragment($explain);
        $number=html_safe_fragment($number);
        $array_bf["none"]=null;
        $array_bf["green"]="navy-bg";
        $array_bf["yellow"]="yellow-bg";
        $array_bf["lazur"]="lazur-bg";
        $array_bf["red"]="red-bg";
        $array_bf["blue"]="blue-bg";
        $array_bf["gray"]="gray-bg";
        $array_bf["black"]="black-bg";
        $array_bf["grey"]="gray-bg";
        $array_bf[null]=null;


        if(!is_array($button)){
            if(!is_null($button)){
                if(preg_match("#minheight:(.+)#",$button,$re)){
                    $min_height="min-height: $re[1]";
                    $button=array();
                }
            }
        }
        if(!is_array($button2)){
            if(!is_null($button2)){
                if(preg_match("#minheight:(.+)#",$button2,$re)) {
                $min_height = "min-height: $re[1]";
                }
            }
            $button2=array();
        }

        if(isset($button["help"]) AND count($button)>1 ){
            $btbg=$this->btn_array();
            $js="s_PopUpFull('{$button["help"]}','1024','900');";
            $button_help=$this->button_autnonome("{online_help}", $js, "fas fa-user-headset",null,0,$btbg[$background]);
            unset($button["help"]);

        }

        if(isset($button["help"])){
            $btbg=$this->btn_array();
            $js="s_PopUpFull('{$button["help"]}','1024','900');";
            $button_html=$this->button_autnonome("{online_help}", $js, "fas fa-user-headset",null,0,$btbg[$background]);
            $button_html="<div style='margin-top:30px'>$button_html</div>";
            unset($button);
        }
        if(!is_array($button2)){
            $button2=array();
        }
        
        if(!isset($button)){
            $button=array();
        }
        
        if(!is_array($button)){
            $button=array();
        }

        if(count($button)>0 AND count($button2)==0){
            $btbg=$this->btn_array();
            $button_htmlsrc=null;
            $icon1=null;
            if(isset($button["css"])){
                if(preg_match("#minheight:(.+)#",$button["css"],$re)){
                    $min_height="min-height: $re[1]";

                }
            }

            if($button_help==null && isset($button["text-left"])){
                $button_help=$button["text-left"];
            }

            if(isset($button["ico"])){$icon1=$button["ico"];}

            if($button["js"]=="UPLOAD"){
                VERBOSE("button_js: {$button["js"]}",__LINE__);
                $page=CurrentPageName();
                $button_htmlsrc=$this->button_upload($button["name"],$page,$btbg[$background]);
            }
            if($button_htmlsrc==null) {
                $button_htmlsrc = $this->button_autnonome($button["name"], $button["js"], $icon1, null, 0, $btbg[$background]);
            }
            $button_html="<div style='margin-top:30px'>$button_htmlsrc</div>";
            if($button_help<>null){
                $button_html="<div style='margin-top:30px;text-align:right'>
                <div style='text-align:right;float:right'>
                <table><tr><td>$button_htmlsrc</td><td>$button_help</td></tr></table></div>
                </div>";
            }

        }

        if(count($button)>0 AND count($button2)>0){
            $btbg=$this->btn_array();
            $ico2=$icon;
            $ico1=$icon;
            $button_html1=null;
            if(isset($button2["ico"])){$ico2=$button2["ico"];}
            if(isset($button["ico"])){$ico1=$button["ico"];}

            if(isset($button["css"])){
                if(preg_match("#minheight:(.+)#",$button["css"],$re)){
                    $min_height="min-height: $re[1]";

                }
            }


            if(isset($button2["css"])){
                if(preg_match("#minheight:(.+)#",$button2["css"],$re)){
                    $min_height="min-height: $re[1]";

                }
            }

            if($button["js"]=="UPLOAD"){
                $page=CurrentPageName();
                $button_html1=$this->button_upload($button["name"],$page,$btbg[$background]);
            }

            if($button_html1==null) {
                $button_html1 = $this->button_autnonome(
                    $button["name"], $button["js"], $ico1, null, 0, $btbg[$background]);
            }
            $button_html2=$this->button_autnonome($button2["name"], $button2["js"], $ico2,null,0,$btbg[$background]);
            $button_html="<div style='margin-top:30px;text-align:right'>
                <div style='text-align:right;float:right'>
                <table><tr><td>$button_html1</td><td>$button_html2</td></tr></table></div>
                </div>";

        }


        $html=array();
        $svcbar_class=$this->svcbar_class($background);

        if($svcbar_class==null){
            // Pas de couleur demandee -> rendu historique, aucun effet vitre applique.
            $legacy_bg=isset($array_bf[$background]) ? $array_bf[$background] : null;
            $html[]="<div class=\"widget style1 $legacy_bg\" style='$min_height$min_width'>";
            $html[]="\t<div class=\"row\">";
            $html[]="\t\t<div class=\"col-xs-4\">";
            $html[]="\t\t\t<i class=\"fa $icon fa-5x\"></i>";
            $html[]="\t\t</div>";
            $html[]="\t<div class=\"col-xs-8 text-right\">";
            $html[]="\t\t<span> $explain </span>";
            $html[]="\t\t<h2 class=\"font-bold\">$number</h2>";
            $html[]=$button_html;
            $html[]="\t</div>";
            $html[]="\t</div>";
            $html[]="</div>";
            return @implode("\n", $html);
        }

        // Carte de statistiques: libelle, nombre, boutons a gauche; icone en filigrane.
        // Le style inline ($min_height/$min_width) reste seul maitre de la taille.
        $html[]="<div class=\"svcstat $svcbar_class\" style='$min_height$min_width'>";
        $html[]="\t".$this->svcstat_watermark($icon);
        $html[]="\t<div class=\"svcstat-in\">";
        $html[]="\t\t".$this->svcstat_label($explain);
        $html[]="\t\t".$this->svcstat_number($number);
        if(strlen(trim($button_html))>0){
            $html[]="\t\t<div class=\"svcstat-act\">$button_html</div>";
        }
        $html[]="\t</div>";
        $html[]="</div>";
        return $this->svctile_css().@implode("\n", $html);
    }

    /** Filigrane d'icone en bas a droite de la carte de statistiques. */
    private function svcstat_watermark($icon):string{
        return "<i class=\"fa $icon svcstat-wm\"></i>";
    }

    /**
     * Libelle de la carte de statistiques.
     * Les capitales espacees ne sont appliquees qu'aux libelles courts: beaucoup
     * d'appelants passent une phrase entiere dans $explain, illisible ainsi traitee.
     */
    private function svcstat_label($explain):string{
        if(is_null($explain)){return "";}
        $class="svcstat-label";
        if(strlen(trim(strip_tags($explain)))<=28){$class.=" svcstat-label-up";}
        return "<div class=\"$class\">$explain</div>";
    }

    /** Valeur principale; retrecit quand la chaine est trop longue pour la taille pleine. */
    private function svcstat_number($number):string{
        $class="svcstat-num";
        if(strlen(trim(strip_tags($number)))>18){$class.=" svcstat-num-sm";}
        return "<div class=\"$class\">$number</div>";
    }

    private function btn_array():array{
        $btbg[null]="btn-primary";
        $btbg["green"]="btn-primary";
        $btbg["ok"]="btn-primary";
        $btbg[true]="btn-primary";
        $btbg["lazur"]="btn-info";
        $btbg["warning"]="btn-warning";
        $btbg["yellow"]="btn-warning";
        $btbg["red"]="btn-danger";
        $btbg["danger"]="btn-danger";
        $btbg[false]="btn-danger";
        $btbg["error"]="btn-danger";
        $btbg["blue"]="btn-blue";
        $btbg["disabled"]="btn-default";
        $btbg["default"]="btn-default";
        $btbg["none"]="btn-default";
        $btbg["white"]="btn-default";
        $btbg["gray"]="btn-default";
        $btbg["grey"]="btn-default";
        $btbg["black"]="btn-default";
        $btbg["btn-danger"]="btn-danger";
        $btbg["btn-default"]="btn-default";
        $btbg["btn-warning"]="btn-warning";
        $btbg["btn-info"]="btn-info";
        $btbg["btn-primary"]="btn-primary";
        return $btbg;
    }
}

function browser_realip():string{
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){return $_SERVER["HTTP_X_FORWARDED_FOR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){ return $_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["REMOTE_ADDR"])){return $_SERVER["REMOTE_ADDR"];}
    return "0.0.0.0";
}
function _xss_clean($data):string{
    // Fix &entity\n;
    $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
    $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
    $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
    $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

    // Remove any attribute starting with "on" or xmlns
    $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

    // Remove javascript: and vbscript: protocols
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

    // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

    // Remove namespaced elements (we do not need them)
    $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

    do
    {
        // Remove really unwanted tags
        $old_data = $data;
        $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
    }
    while ($old_data !== $data);

    // we are done...
    return strval($data);
}

function _cleanvars($dat){
    $dat=_xss_clean($dat);
    $dat=str_ireplace("<script","",$dat);
    return $dat;
}

function _debugtraceback($trace){
        if(count($trace)==0){return "";}
        $f=array();
        foreach ($trace as $line){
            $sourcefile=basename($line["file"]);
            $sourcefunction=$line["function"];
            $sourceline=$line["line"];
            $f[]="$sourcefile/$sourcefunction/L.$sourceline";
        }
        return @implode("| ",$f);
}

//XSS Protection
$NoXSSProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoXSSProtection"));
//List of forbidden words / tags
$xss_words = array("''","'","&apos;","\"><",'"',";",'";',"javascript:","OnKeyPress","OnClick","</script>", "alert(","'"," OR "," AND ","<script>","confirm(","onfocus","prompt(","[","]","<ScRiPt>","document.location=","</ScRiPt >","<img","onerror","document.title",'<a b=c>',"(a)","<a>");
if($NoXSSProtection==1) {
//Clean xss tags from $_COOKIE
    foreach ($_COOKIE as $key => $val) {
        if (strpos($key, "'") !== false) {
            unset($_COOKIE[$key]);
            continue;
        }
        $val = trim($val);
        //Remove bad tags xss
        $val = str_replace($xss_words, "", $val);
        //Clean byte array xss
        $val = preg_replace('/[\x00-\x1f]/', '', $val);
        //Clean <a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean (a-zA-Z0-9_.-) xss
        $val = preg_replace("/(\([a-zA-Z0-9_.-]*\))$/", '', $val);
        //Clean 111=222 xss
        $val = preg_replace("/(\d+=\d+)/", '', $val);
        $_COOKIE[$key] = $val;
    }
//Unset Cookie artica-language if > 2
    if(isset($_COOKIE["artica-language"])) {
        if (strlen($_COOKIE["artica-language"]) > 2) {
            unset($_COOKIE["artica-language"]);
        }
    }
//Unset POST that contain ' in key
    foreach ($_POST as $key => $val) {
        //echo md5($val)."-$key\n";

        //Unset GET that contain ' in key
        if (strpos($key, "'") !== false) {
            unset($_POST[$key]);
            continue;
        }
        $val = trim($val);

        //Remove bad tags xss
        //$val = str_replace($xss_words, "", $val);

        //Clean byte array xss
        $val = preg_replace('/[\x00-\x1f]/', '', $val);
        //Clean <a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean (a-zA-Z0-9_.-) xss
        $val = preg_replace("/(\([a-zA-Z0-9_.-]*\))$/", '', $val);
        //Clean 111=222 xss
        $val = preg_replace("/(\d+=\d+)/", '', $val);
        //$val = preg_replace('<[a-zA-Z]+(\s+[a-zA-Z]+\s*=\s*("([^"])"|\'([^\'])\'))\s/>', '', $val);
        //$val = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $val);
        //echo md5($val)."-$key\n";
        $_POST[$key] = $val;
    }
//Clean xss tags from $_GET
    foreach ($_GET as $key => $val) {
        //Unset GET that contain ' in key
        if (strpos($key, "'") !== false) {
            unset($_GET[$key]);
            continue;
        }
        $val = trim($val);
        //Remove bad tags xss
        $val = str_replace($xss_words, "", $val);
        //Clean byte array xss
        $val = preg_replace('/[\x00-\x1f]/', '', $val);
        //Clean <a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean <a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean <%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(<%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-> xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*>)$/", '', $val);
        //Clean >a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean >%a-zA-Z0-9_.-< xss
        $val = preg_replace("/(>%[a-zA-Z0-9_.-]*<)$/", '', $val);
        //Clean (a-zA-Z0-9_.-) xss
        $val = preg_replace("/(\([a-zA-Z0-9_.-]*\))$/", '', $val);
        //Clean 111=222 xss
        $val = preg_replace("/(\d+=\d+)/", '', $val);
        //$val = preg_replace('<[a-zA-Z]+(\s+[a-zA-Z]+\s*=\s*("([^"])"|\'([^\'])\'))\s/>', '', $val);
        //$val = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $val);
        $_GET[$key] = $val;
    }
}
function clean_xss_deep(){
    $trace=array();
    if(function_exists("debug_backtrace")){$trace=debug_backtrace();}
    foreach ($_POST as $key=>$val){
        // Un champ non scalaire (POST forgé `foo[]=x`) n'a aucune place dans un
        // formulaire de la console : trim() puis les preg_replace() ci-dessous
        // lèvent un TypeError (HTTP 500) sur un tableau. On le retire proprement
        // plutôt que de laisser planter tout traitement de POST.
        if(is_array($val) || is_object($val)){ unset($_POST[$key]); continue; }
        if(!is_null($val)) {
            $val = trim($val);
        }
        if($val==null){
            if($key=="search"){continue;}
            unset($_POST[$key]);
            continue;
        }
        $oldval=$val;
        $val = _cleanvars($val);

        if($oldval<>$val){
            admin_tracks("XSS Detected on value POST !" ._debugtraceback($trace));
        }

        $newKey=$key;
        $newKey = _cleanvars($newKey);
        if($newKey<>$key){
            admin_tracks("XSS Detected on key POST !" ._debugtraceback($trace));
            unset($_POST[$key]);
            $_POST[$newKey]=$val;
            continue;
        }
        $_POST[$key]=$val;
    }
    foreach ($_GET as $key=>$val){
        $val=trim($val);
        if($val==null){
            unset($_GET[$key]);
            continue;
        }
        $oldval=$val;
        $val = _cleanvars($val);

        if($oldval<>$val){
            admin_tracks("XSS Detected on value GET !" ._debugtraceback($trace));
        }

        $newKey=$key;
        $newKey = _cleanvars($newKey);

        if($newKey<>$key){
            admin_tracks("XSS Detected on key GET !" ._debugtraceback($trace));
            unset($_GET[$key]);
            $_GET[$newKey]=$val;
            continue;
        }
        $_GET[$key]=$val;
    }
}
function admin_tracks_post($text):bool{
    $tt=array();
    foreach ($_POST as $key=>$val){
        $val=admin_tracks_mask_value($key,$val);
        $tt[]=" $key=[$val]";
    }
    $text=$text." with values ".@implode(", ",$tt);
    return admin_tracks($text);
}

/**
 * Valeur d'un champ POST telle qu'elle peut entrer dans le journal
 * d'administration (admin_tracks, syslog ArticaTrackAdmins).
 *
 * Le journal recopiait TOUT $_POST: le secret TSIG d'un domaine local
 * (fw.dns.unbound.domains.php, fw.kea.ddns.php), les mots de passe et les cles
 * d'API s'y lisaient en clair. Seule la VALEUR est masquee ("***"), jamais le
 * nom du champ: le journal dit toujours quel reglage a change.
 *
 *  - secretkey, password, secret (nom exact): toujours masques ;
 *  - nom contenant "pass", "secret", "token", "pwd" ou "psk": masque, sauf la valeur 0/1 d'une case a
 *    cocher (EnableShowPasswords, PasswordAuthentication…) ; "bypass",
 *    "passive" et "passthrough"/"passtrough" ne designent pas un mot de passe ;
 *  - nom contenant "key": masque au-dela de 16 caracteres (cles d'API, cles
 *    privees, secrets base64) ; les noms de cle (keyname, tsigkeyname) et les
 *    listes de mots (keywords) restent lisibles.
 * Une valeur vide reste vide: "rien saisi" est une information utile.
 */
function admin_tracks_mask_value($key,$val){
    if(is_array($val)){return $val;}
    $val=(string)$val;
    if($val===""){return $val;}
    $name=strtolower((string)$key);
    if(in_array($name,array("secretkey","password","secret"),true)){return "***";}
    $stripped=str_replace(array("bypass","passive","passthrough","passtrough"),"",$name);
    if(strpos($stripped,"pass")!==false || strpos($stripped,"secret")!==false
        || strpos($stripped,"token")!==false || strpos($stripped,"pwd")!==false || strpos($stripped,"psk")!==false){
        if($val==="0" || $val==="1"){return $val;}
        return "***";
    }
    $stripped=str_replace(array("keyword","keyname"),"",$name);
    if(strpos($stripped,"key")!==false && strlen($val)>16){return "***";}
    return $val;
}

/**
 * Valide un rappel JavaScript transporte par le parametre d'URL "js-after".
 *
 * PROBLEME. Une dizaine de pages (fw.rules.items.php, fw.proxy.acls.objects.php,
 * fw.strongswan.*, …) acceptent un "js-after" encode en base64, le decodent et
 * l'inserent TEL QUEL dans un bloc <script>. C'est une execution de JavaScript
 * arbitraire depuis une URL, et l'encodage base64 la rend invisible a tout
 * filtre qui regarderait la chaine brute.
 *
 * CHOIX. Les charges legitimes se reduisent a trois formes, verifiees sur les
 * dix producteurs du depot:
 *     MaFonction()
 *     LoadAjax('un-div','une-url'); Loadjs('une-url');
 *     if(document.getElementById('un-div')){ …les formes ci-dessus… }
 * Plutot que de reecrire les trente points d'emission -- et de casser les
 * appels croises entre pages -- on VALIDE la chaine decodee contre cette
 * grammaire. Ce qui n'y entre pas est rejete et remplace par une chaine vide:
 * le rappel ne se fait pas, mais rien d'etranger ne s'execute.
 *
 * Les arguments admis sont exclusivement des litteraux simple-quote sans
 * apostrophe ni antislash, ou des nombres: aucune concatenation, aucun appel
 * imbrique, aucun acces a une propriete.
 *
 * La FONCTION APPELEE est elle aussi restreinte. Valider la seule forme ne
 * suffisait pas: "alert(1)" et "eval('alert(1)')" la respectent parfaitement.
 * Seules sont admises les fonctions de chargement de default.js et les
 * fonctions de rafraichissement engendrees par search_block (ss<horodatage>)
 * ou nommees Refresh<Quelquechose> par les pages.
 */
/**
 * Le parametre "js-after" sous sa forme de TRANSPORT (base64), pour les pages
 * qui ne font que le relayer vers une autre URL sans l'executer.
 *
 * Ces relais l'inserent dans une chaine JavaScript -- "Loadjs('…js-after=$v…')"
 * -- sans rien verifier. Une valeur comme "x');alert(1)//" n'est pas du base64
 * valide, mais elle refermait quand meme la chaine et executait la suite. On
 * restreint donc a l'alphabet base64: le relais ne peut plus rien casser, et
 * une valeur fabriquee sera de toute facon rejetee a l'arrivee par
 * jsafter_sanitize().
 */
function jsafter_token($v):string{
    return preg_replace('#[^A-Za-z0-9+/=]#','',(string)$v);
}

function jsafter_sanitize($js):string{
    $js=trim((string)$js);
    if($js===""){return "";}
    // Une charge legitime reste courte; au-dela, on refuse sans meme analyser.
    if(strlen($js)>2048){return "";}
    return jsafter_validate_sequence($js)?$js:"";
}

/** Une suite d'instructions separees par des point-virgules. */
function jsafter_validate_sequence($js):bool{
    $js=trim((string)$js);
    if($js===""){return true;}

    // Un bloc conditionnel: if(document.getElementById('id')){ … }
    $re='#^if\s*\(\s*document\.getElementById\(\s*'."'".'([A-Za-z0-9_-]+)'."'".'\s*\)\s*\)\s*\{(.*)\}\s*;?\s*$#s';
    if(preg_match($re,$js,$m)){
        return jsafter_validate_sequence($m[2]);
    }

    foreach(explode(";",$js) as $stmt){
        $stmt=trim($stmt);
        if($stmt===""){continue;}
        if(!jsafter_validate_call($stmt)){return false;}
    }
    return true;
}

/** Les seules fonctions qu'un rappel "js-after" a le droit d'appeler. */
function jsafter_allowed_callee($name):bool{
    static $allowed=array(
        "LoadAjax"=>true,"LoadAjaxSilent"=>true,"LoadAjaxTiny"=>true,"LoadAjaxWhite"=>true,
        "LoadAjaxRound"=>true,"LoadAjaxPreload"=>true,"LoadAjaxHidden"=>true,
        "LoadAjaxVerySilent"=>true,"LoadAjaxSequence"=>true,
        "Loadjs"=>true,"LoadjsSilent"=>true,"LoadjsFinal"=>true,
        "NoSpinner"=>true,"RefreshTable"=>true,
    );
    if(isset($allowed[$name])){return true;}
    // Fonction de rafraichissement engendree par search_block: "ss" suivi d'un
    // horodatage.
    if(preg_match('#^ss[0-9]+$#',$name)){return true;}
    // Fonction de rafraichissement nommee par une page: RefreshXxx().
    if(preg_match('#^Refresh[A-Za-z0-9_]*$#',$name)){return true;}
    return false;
}

/** Un appel de fonction a arguments litteraux. */
function jsafter_validate_call($stmt):bool{
    if(!preg_match('#^([A-Za-z_][A-Za-z0-9_]*)\s*\((.*)\)$#s',trim($stmt),$m)){return false;}
    if(!jsafter_allowed_callee($m[1])){return false;}
    $args=trim($m[2]);
    if($args===""){return true;}
    foreach(explode(",",$args) as $arg){
        $arg=trim($arg);
        if($arg===""){return false;}
        if(preg_match('#^-?[0-9]+(\.[0-9]+)?$#',$arg)){continue;}
        if($arg==="true"||$arg==="false"||$arg==="null"){continue;}
        // Litteral simple-quote: ni apostrophe ni antislash a l'interieur, ce
        // qui interdit de refermer la chaine ou d'echapper le delimiteur.
        if(preg_match('#^'."'".'[^'."'".'\\\\]*'."'".'$#',$arg)){continue;}
        return false;
    }
    return true;
}

function unserializeb64($data):array{
    if(is_null($data)){
        return array();
    }
    if(strlen($data)<3){
        return array();
    }
    $Decoded=base64_decode($data);
    if(!$Decoded){
        return array();
    }
    $Unser=unserialize($Decoded,array("allowed_classes"=>array("stdClass")));
    if(!$Unser){
        return array();
    }
    if(!is_array($Unser)){
        return array();
    }

    return $Unser;
}

function admin_tracks($text):bool{
    $uid="unknown";
    $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
    if($PowerDNSEnableClusterMaster==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/server/stamp");
    }
    $TrackAdmins=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TrackAdmins"));
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$TrackAdmins=0;}
    $time=time();
    if(isset($GLOBALS["ROOT_UID"])){
        $uid=$GLOBALS["ROOT_UID"];
    }else {
        if(isset($_SESSION["uid"])) {
            $uid = $_SESSION["uid"];
        }
    }
    if($uid==-100){$uid="Manager";}
    $ipaddr=browser_realip();
    if($TrackAdmins==1) {
        // Audit write delegated to articarest POST /localadmins/admintracks
        // (parameterized insert — the daemon nudges the one-second PRIMARY
        // KEY forward on collision instead of silently dropping the event).
        // The quote mangling below predates the API but is kept so stored
        // text stays in the exact legacy format.
        $text=str_replace("'","`",$text);
        $text=str_replace('"',"`",$text);
        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            $sourcefile=null;$sourcefunction=null;$sourceline=0;
            if(isset($trace[1])){
                if(isset($trace[1]["file"])){$sourcefile=basename($trace[1]["file"]);}
                if(isset($trace[1]["function"])){$sourcefunction=$trace[1]["function"];}
                if(isset($trace[1]["line"])){$sourceline=$trace[1]["line"];}
            }
            $text="$text [$sourcefile/$sourcefunction/L.$sourceline]";
        }

        $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/localadmins/admintracks",
            array("ipaddr"=>strval($ipaddr),"username"=>strval($uid),"operation"=>strval($text)))),true);
        if(!is_array($resp) || empty($resp["success"])){
            if(function_exists("writelogs")){
                writelogs("POST /localadmins/admintracks failed: ".strval(is_array($resp)?($resp["error"] ?? "unknown"):"no response"),__FUNCTION__,__FILE__,__LINE__);
            }
        }
        if (!function_exists("syslog")) {
            return false;
        }
        @openlog("ArticaTrackAdmins", LOG_PID, LOG_SYSLOG);
        @syslog(LOG_INFO, "[$uid] [$ipaddr] $text: ");
        @closelog();
    }
    return true;
}
function ExecTAtime($start,$text,$line){
    if(!isset($GLOBALS["VERBOSE"])){return;}
    $end = microtime(true);
    $executionTime = $end - $start;
    VERBOSE_TEMPLATE("($text) Execution time ".number_format($executionTime, 4) . " seconds",$line);
}
function VERBOSE_TEMPLATE($text,$line=0,$forceFunc=""):bool{
    $function=null;$file=null;
    if(!$GLOBALS["VERBOSE"]){return false;}
    if(!isset($GLOBALS["AS_ROOT"])){$GLOBALS["AS_ROOT"]=false;}
    if(!isset($GLOBALS["posix_getuid"])){
        $GLOBALS["posix_getuid"]=1000;
        if(function_exists("posix_getuid")){
            $GLOBALS["posix_getuid"]=posix_getuid();
        }
    }

    if($GLOBALS["posix_getuid"]==0){$GLOBALS["AS_ROOT"]=true;}


    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            $file=basename($trace[1]["file"]);
            if(isset($trace[1]["function"])){$function=$trace[1]["function"];}
            if(isset($trace[1]["class"])){$function="{$trace[1]["class"]}/$function";}
        }
    }

    if($GLOBALS["AS_ROOT"]){echo "[L.$line]: $text\n";return true;}
    $date=date("I:s:ss");


    $func="{$function}[$line]";
    if(strlen($forceFunc)>2){
        $func=$forceFunc;
    }
    echo "<div style='font-size:14px;font-weight:bold;background-color:white;color:black'><span style=color:red>$date&nbsp;$func:</span>&nbsp;$text&nbsp;<small>($file)</small></div>\n";
    return true;
}

/**
 * Zoom de la console (cookie ConsoleZoom, en pourcentage), regle depuis "Mon compte".
 * 0 = automatique (defaut sans cookie) : js/console-zoom.js le calcule selon la mise a
 * l'echelle de l'OS (100 % -> 100 % ; au-dela, reduction seulement si l'ecran fait au moins
 * 900 px CSS de haut, en gardant la moitie de l'agrandissement de l'OS, jamais sous 85 %).
 * Un niveau fixe est pose par angular.css.php sur <html>, donc sur toutes les pages
 * et sur l'ecran de connexion. Une valeur hors liste (cookie forge) retombe sur le
 * defaut : elle part dans du CSS.
 */
function console_zoom_levels():array{
    return array(0,50,60,70,80,90,100,110,125,150);
}
function console_zoom_current():int{
    $z=isset($_COOKIE["ConsoleZoom"])?intval($_COOKIE["ConsoleZoom"]):0;
    if(!in_array($z,console_zoom_levels(),true)){$z=0;}
    return $z;
}
/**
 * Balise du script js/console-zoom.js, a placer dans le <head> de toute page qui
 * charge angular.css.php : il annule la reduction de la console quand le navigateur
 * est deja en zoom arriere. Fichier statique et non script en ligne : le <head>
 * peut traverser _ENGINE_parse_body(), qui prend tout {...} pour un jeton.
 */
function console_zoom_script(string $prefix="/"):string{
    return "<script src=\"{$prefix}js/console-zoom.js?v=20261116\"></script>";
}

/*
 * HTML de boutons transmis par le client entre deux appels AJAX.
 * Plusieurs pages encodaient leurs boutons en base64 dans une URL, puis une
 * route les renvoyait tels quels : n'importe quel HTML/JS fourni dans l'URL
 * etait servi depuis l'origine de la console (pentest 2026-11-04, M-01).
 * Le jeton porte maintenant une signature HMAC liee a la session : seul un
 * contenu produit par la page elle-meme est renvoye.
 */
function signed_html_key():string{
    if(session_status()!==PHP_SESSION_ACTIVE){return "";}
    if(!isset($_SESSION["SIGNED_HTML_KEY"]) || strlen($_SESSION["SIGNED_HTML_KEY"])<32){
        $_SESSION["SIGNED_HTML_KEY"]=bin2hex(random_bytes(32));
    }
    return $_SESSION["SIGNED_HTML_KEY"];
}
function signed_html_token(string $html):string{
    $key=signed_html_key();
    if($key==""){return "";}
    $b64=base64_encode($html);
    return urlencode($b64.".".hash_hmac("sha256",$b64,$key));
}
// Rend le contenu d'un jeton signe, ou "" s'il est absent, forge ou modifie.
// Les pages relaient parfois le jeton sans urlencode : un "+" du base64 y
// devient une espace, qu'on retablit (le base64 n'en contient jamais).
function signed_html_decode($token):string{
    $token=str_replace(" ","+",strval($token));
    $pos=strrpos($token,".");
    if($pos===false){return "";}
    $b64=substr($token,0,$pos);
    $sig=substr($token,$pos+1);
    $key=signed_html_key();
    if($key=="" || !hash_equals(hash_hmac("sha256",$b64,$key),$sig)){return "";}
    return strval(base64_decode($b64));
}
function signed_html_echo($token):bool{
    $html=signed_html_decode($token);
    if($html==""){return false;}
    echo $html;
    return true;
}

// ---------------------------------------------------------------------------
// Barre d'actions table_buttons() : styles globaux, servis par angular.css.php
// (maquette stitch_console_button_system_redesign). Fonctions autonomes :
// class.brand.inc peut manquer sur une appliance ancienne.
// ---------------------------------------------------------------------------
function abt_hex6($hex):string{
    $hex=ltrim(trim(strval($hex)),"#");
    if(strlen($hex)==3){$hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];}
    if(!preg_match('#^[0-9a-fA-F]{6}$#',$hex)){return "";}
    return "#".strtolower($hex);
}
function abt_luminance($hex):float{
    $hex=abt_hex6($hex);
    if($hex==""){return 0.0;}
    $c=array();
    foreach(array(1,3,5) as $o){
        $v=hexdec(substr($hex,$o,2))/255;
        $c[]=$v<=0.03928?$v/12.92:pow(($v+0.055)/1.055,2.4);
    }
    return 0.2126*$c[0]+0.7152*$c[1]+0.0722*$c[2];
}
function abt_shade($hex,$factor):string{
    $hex=abt_hex6($hex);
    if($hex==""){return "#047857";}
    $out="#";
    foreach(array(1,3,5) as $o){
        $n=intval(round(hexdec(substr($hex,$o,2))*$factor));
        if($n<0){$n=0;}
        if($n>255){$n=255;}
        $out=$out.str_pad(dechex($n),2,"0",STR_PAD_LEFT);
    }
    return $out;
}
/** Fond du bouton principal : l'accent, assombri jusqu'a 4.5:1 sous un texte blanc. */
function abt_fill($accent):string{
    $hex=abt_hex6($accent);
    if($hex==""){$hex="#10b981";}
    $i=0;
    while((1.05/(abt_luminance($hex)+0.05))<4.5 && $i<30){
        $hex=abt_shade($hex,0.94);
        $i++;
    }
    return $hex;
}
function table_buttons_css($palette=array()):string{
    $accent="#10b981";
    if(is_array($palette) && count($palette)>0 && function_exists("brand_ui_accents")){
        $acc=brand_ui_accents($palette);
        if(isset($acc["accent"])){$accent=$acc["accent"];}
    }
    $fill=abt_fill($accent);
    $hover=abt_shade($fill,0.88);
    $press=abt_shade($fill,0.78);
    $f=array();
    $f[]="\n/* --- table_buttons() : barre d'actions par role --- */";
    $f[]=".abt-bar{display:flex;flex-wrap:wrap;align-items:center;gap:8px}";
    $f[]=".abt-foot{display:flex;flex-wrap:wrap;justify-content:flex-end;align-items:center;gap:8px}";
    // slideInLeft (fw.progress.php) laisse un transform sur #tiny-button (fill-mode both) :
    // contexte d'empilement au niveau 0, le contenu de la page passait par-dessus les menus.
    $f[]="#tiny-button{position:relative;z-index:30}";
    $f[]="#tiny-button .abt-bar{margin-top:12px}";
    $f[]="#tiny-button .siem-tinyf .abt-bar{margin-top:0}";
    $f[]=".abt{display:inline-flex;align-items:center;gap:8px;height:36px;padding:0 14px;margin:0;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#1e293b;font-size:14px;font-weight:600;line-height:1;white-space:nowrap;cursor:pointer;box-sizing:border-box;text-decoration:none;vertical-align:middle;transition:background-color .12s,border-color .12s,color .12s}";
    $f[]=".abt>span{display:inline-block}";
    $f[]=".abt>span::first-letter{text-transform:uppercase}";
    $f[]=".abt:hover{background:#f8fafc;border-color:#94a3b8;color:#0f172a;text-decoration:none}";
    $f[]=".abt:active{background:#f1f5f9}";
    // Les boutons portent aussi btn btn-<couleur> (repli sans cette feuille) : les etats
    // Bootstrap :active:hover / :active:focus (3 selecteurs) doivent etre repris ici a
    // poids egal pour que l'appui garde les couleurs abt, sans l'ombre interieure.
    $f[]=".abt:active,.abt:active:hover,.abt:active:focus,.abt.active{box-shadow:none}";
    $f[]=".abt:focus{outline:0}";
    $f[]=".abt:focus-visible{outline:0;box-shadow:0 0 0 2px #fff,0 0 0 4px $fill}";
    $f[]=".abt-p{background:$fill;border-color:$fill;color:#fff;box-shadow:0 1px 2px rgba(15,23,42,.08)}";
    $f[]=".abt-p>i,.abt-p>svg{color:#fff}";
    $f[]=".abt-p:hover,.abt-p:focus{background:$hover;border-color:$hover;color:#fff}";
    $f[]=".abt-p:active,.abt-p:active:hover,.abt-p:active:focus{background:$press;border-color:$press;color:#fff}";
    // Boutons actifs : couleurs pleines qui alternent dans une barre, comme avant le
    // systeme abt (vert, bleu clair, bleu, orange, emeraude #10b981 -- celui de
    // .aid-btn-primary, texte blanc). abt-s = vert (accent de marque),
    // premiere couleur et couleur d'un bouton seul. Le BLANC est reserve a ce qui est
    // desactive : abt-o (OFF, option non choisie), abt-l (droit manquant), [disabled].
    $rot=array("s"=>array($fill,$hover,$press),"c2"=>array("#0e7490","#155e75","#164e63"),
        "c3"=>array("#2563eb","#1d4ed8","#1e40af"),"c4"=>array("#c2410c","#9a3412","#7c2d12"),
        "c5"=>array("#10b981","#059669","#047857"),
        "x"=>array("#dc2626","#b91c1c","#991b1b"));
    foreach($rot as $k=>$v){
        $f[]=".abt-$k{background:{$v[0]};border-color:{$v[0]};color:#fff;box-shadow:0 1px 2px rgba(15,23,42,.08)}";
        $f[]=".abt-$k>i,.abt-$k>svg{color:#fff}";
        $f[]=".abt-$k:hover,.abt-$k:focus{background:{$v[1]};border-color:{$v[1]};color:#fff}";
        $f[]=".abt-$k:active,.abt-$k:active:hover,.abt-$k:active:focus{background:{$v[2]};border-color:{$v[2]};color:#fff}";
    }
    $f[]=".abt-o{background:#fff;border-color:#cbd5e1;color:#1e293b}";
    $f[]=".abt-o>i,.abt-o>svg{color:#475569}";
    $f[]=".abt-o:hover,.abt-o:focus{background:#f8fafc;border-color:#94a3b8;color:#0f172a}";
    $f[]=".abt-o:active,.abt-o:active:hover,.abt-o:active:focus{background:#f1f5f9;border-color:#94a3b8;color:#0f172a}";
    $f[]=".abt-l,.abt-l:hover,.abt-l:active{position:relative;background:#f1f5f9;border:1px dashed #cbd5e1;color:#475569;cursor:not-allowed}";
    $f[]=".abt-l>i,.abt-l>svg{color:#64748b}";
    $f[]=".abt-l[data-tip]:hover::after,.abt-l[data-tip]:focus-visible::after{content:attr(data-tip);position:absolute;left:0;top:calc(100% + 6px);z-index:1060;width:max-content;max-width:320px;white-space:normal;background:#0f172a;color:#fff;font-size:13px;font-weight:500;line-height:1.4;padding:6px 10px;border-radius:6px;text-align:left;pointer-events:none}";
    $f[]=".abt[disabled],.abt[disabled]:hover{opacity:.55;background:#fff;border-color:#cbd5e1;color:#1e293b;cursor:not-allowed}";
    $f[]=".abt-sm{height:28px;padding:0 10px;gap:6px;font-size:13px}";
    $f[]=".abt-sep{width:1px;height:24px;background:#cbd5e1;margin:0 4px}";
    $f[]=".abt-more{position:relative;display:inline-block}";
    $f[]=".abt-more.open>.abt{filter:brightness(.9)}";
    $f[]=".abt-menu{min-width:224px;margin-top:8px;padding:6px 0;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 10px 30px rgba(15,23,42,.16)}";
    $f[]=".abt-menu>li>a{display:flex;align-items:center;gap:10px;padding:8px 12px;font-size:13px;color:#1e293b;white-space:nowrap}";
    $f[]=".abt-menu>li>a>i,.abt-menu>li>a>svg{width:16px;color:#475569;text-align:center}";
    $f[]=".abt-menu>li>a:hover,.abt-menu>li>a:focus{background:#f1f5f9;color:#0f172a}";
    $f[]=".abt-menu>li>a:focus{outline:2px solid $fill;outline-offset:-2px}";
    $f[]=".abt-menu>li.abt-mx>a,.abt-menu>li.abt-mx>a>i,.abt-menu>li.abt-mx>a>svg{color:#b91c1c;font-weight:600}";
    $f[]=".abt-menu>li.abt-mx>a:hover,.abt-menu>li.abt-mx>a:focus{background:#fef2f2}";
    $f[]=".abt-menu>li.disabled>a,.abt-menu>li.disabled>a:hover{color:#64748b;background:transparent;cursor:not-allowed}";
    $f[]=".abt-menu>li.divider{margin:6px 0;background:#e2e8f0}";
    // --- button_autnonome() : boutons du corps de page
    $h=ltrim(abt_hex6($fill),"#");
    $tint="rgba(".hexdec(substr($h,0,2)).",".hexdec(substr($h,2,2)).",".hexdec(substr($h,4,2)).",.12)";
    $line="rgba(".hexdec(substr($h,0,2)).",".hexdec(substr($h,2,2)).",".hexdec(substr($h,4,2)).",.45)";
    // Largeur fixe : le libelle passe a la ligne au lieu de deborder.
    $f[]=".abt-w{white-space:normal;height:auto;min-height:36px;padding:7px 14px;line-height:1.3;justify-content:center;text-align:center}";
    $f[]=".abt-w.abt-sm{min-height:28px;padding:4px 10px}";
    // Grand bouton d'assistant (largeur >= 400 px).
    $f[]=".abt-wiz{display:flex;height:auto;min-height:72px;padding:12px 16px 12px 12px;gap:16px;border-color:#e2e8f0;border-radius:12px;background:#fff;color:#0f172a;white-space:normal;text-align:left;justify-content:flex-start;line-height:1.35;box-shadow:0 1px 2px rgba(15,23,42,.06);transition:background-color .12s,border-color .12s,box-shadow .12s}";
    $f[]=".abt-wiz:hover,.abt-wiz:focus{background:#f8fafc;border-color:$line;color:#0f172a;box-shadow:0 4px 12px rgba(15,23,42,.08)}";
    $f[]=".abt-wiz>.abt-wiz-ico{flex:0 0 48px;width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:$tint;color:$fill;font-size:22px;transition:background-color .12s,color .12s}";
    $f[]=".abt-wiz-ico>i,.abt-wiz-ico>svg{color:inherit}";
    $f[]=".abt-wiz:hover .abt-wiz-ico{background:$fill;color:#fff}";
    $f[]=".abt-wiz>.abt-wiz-lbl{display:block;flex:1 1 auto;min-width:0;font-size:16px;font-weight:600}";
    $f[]=".abt-wiz:hover .abt-wiz-lbl{color:$fill}";
    $f[]=".abt-wiz>.abt-wiz-go{flex:0 0 32px;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f1f5f9;color:#475569;font-size:13px;transition:transform .12s}";
    $f[]=".abt-wiz:hover .abt-wiz-go{transform:translateX(4px);color:#0f172a}";
    $f[]=".abt-wiz-s .abt-wiz-ico{background:#f1f5f9;color:#475569}";
    $f[]=".abt-wiz-s:hover .abt-wiz-ico{background:#e2e8f0;color:#0f172a}";
    $f[]=".abt-wiz-s:hover .abt-wiz-lbl{color:#0f172a}";
    $f[]=".abt-wiz-x:hover,.abt-wiz-x:focus{border-color:#fca5a5;background:#fff5f5}";
    $f[]=".abt-wiz-x .abt-wiz-ico{background:#fef2f2;color:#b91c1c}";
    $f[]=".abt-wiz-x:hover .abt-wiz-ico{background:#dc2626;color:#fff}";
    $f[]=".abt-wiz-x .abt-wiz-lbl,.abt-wiz-x:hover .abt-wiz-lbl{color:#b91c1c}";
    $f[]=".abt-wiz.abt-l .abt-wiz-ico,.abt-wiz.abt-l:hover .abt-wiz-ico{background:#e2e8f0;color:#64748b}";
    $f[]=".abt-wiz.abt-l .abt-wiz-lbl,.abt-wiz.abt-l:hover .abt-wiz-lbl{color:#475569}";
    $f[]=".abt-wiz.abt-l .abt-wiz-go{display:none}";
    $f[]=".abt-wiz[disabled] .abt-wiz-go{display:none}";
    // Tuile coloree (widget_h, rendu historique) : bouton blanc translucide.
    $on=array();
    foreach(array("navy-bg","red-bg","lazur-bg","blue-bg","yellow-bg","black-bg") as $bg){$on[]=".widget.$bg .abt";}
    $onsel=@implode(",",$on);
    $f[]="$onsel{background:rgba(255,255,255,.16);border-color:rgba(255,255,255,.55);color:#fff;box-shadow:none}";
    $hv=array(); $ic=array(); $fc=array();
    foreach($on as $s){$hv[]="$s:hover";$ic[]="$s>i";$ic[]="$s>svg";$fc[]="$s:focus-visible";}
    $f[]=@implode(",",$hv)."{background:rgba(255,255,255,.28);border-color:#fff;color:#fff}";
    $f[]=@implode(",",$ic)."{color:#fff}";
    $f[]=@implode(",",$fc)."{box-shadow:0 0 0 2px #fff}";
    // --- button_inline() : une ligne, aide discrete, valeur editable, interrupteur
    $f[]=".abt-i{white-space:nowrap}";
    $f[]=".abt-g{padding:0 8px;background:transparent;border-color:transparent;color:$fill;box-shadow:none}";
    $f[]=".abt-g>i,.abt-g>svg{color:$fill}";
    $f[]=".abt-g:hover,.abt-g:focus{background:#f1f5f9;border-color:transparent;color:$press}";
    $f[]=".abt-g:active{background:#e2e8f0}";
    $f[]=".abt-ico{width:36px;padding:0;justify-content:center}.abt-sm.abt-ico{width:28px}";
    $f[]=".abt-g.abt-l,.abt-g.abt-l:hover,.abt-g.abt-l:focus{background:transparent;border-color:transparent;color:#64748b}.abt-g.abt-l>i,.abt-g.abt-l>svg{color:#64748b}";
    $f[]=".abt-ico.abt-l[data-tip]:hover::after,.abt-ico.abt-l[data-tip]:focus-visible::after{left:auto;right:0}";
    $f[]=".abt-num{font-variant-numeric:tabular-nums;font-feature-settings:'tnum' 1}";
    $f[]=".abt-drain{background:#fffbeb;border-color:#fcd34d;color:#b45309}";
    $f[]=".abt-drain>i,.abt-drain>svg{color:#b45309}";
    $f[]=".abt-drain:hover,.abt-drain:focus{background:#fef3c7;border-color:#f59e0b;color:#92400e}";
    $f[]=".abt-metric{display:inline-flex;align-items:center;gap:8px;white-space:nowrap}";
    $f[]=".abt-state{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;height:22px;padding:0 8px;border-radius:6px;background:#f1f5f9;color:#334155;font-size:12px;font-weight:600}";
    $f[]=".abt-state-w{background:#fef2f2;color:#b91c1c}";
    $f[]=".abt-state-ok{background:#ecfdf5;color:#047857}";
    $f[]=".abt-dot{display:inline-block;vertical-align:middle;width:8px;height:8px;margin:-2px 6px 0 0;border-radius:50%;background:#94a3b8}";
    $f[]=".abt-dot-ok{background:#059669}.abt-dot-w{background:#d97706}.abt-dot-x{background:#dc2626}";
    $f[]=".btn-group>.abt{float:none;margin-left:0 !important;border-radius:8px !important}";
    $f[]=".btn-group:has(>.abt){display:inline-flex;flex-wrap:wrap;align-items:center;gap:8px}";
    $f[]=".abt-more>.abt>i:last-child,.abt-more>.abt>svg:last-child{font-size:11px;opacity:.85}";
    $f[]=".abt-menu>li>a{line-height:20px;margin:0 4px;border-radius:6px}";
    $f[]=".abt[disabled]>i,.abt[disabled]>svg{color:#475569}";
    $f[]=".abt-menu>li>a>span::first-letter{text-transform:uppercase}";
    $f[]=".abt-menu>li.disabled>a,.abt-menu>li.disabled>a:hover{color:#64748b;background:transparent;cursor:not-allowed}";
    $f[]=".abt.fileinput-button{position:relative;overflow:hidden}";
    $f[]=".abt-seg>.abt>span:empty{display:none}";
    $f[]=".abt.fileinput-button:focus-within{box-shadow:0 0 0 2px #fff,0 0 0 4px $fill}";
    $f[]=".abt-sw{display:inline-flex;align-items:center;gap:8px;margin:0;padding:2px;border:0;background:transparent;color:#334155;font-size:13px;font-weight:600;cursor:pointer;border-radius:12px;vertical-align:middle}";
    $f[]=".abt-sw-track{position:relative;display:inline-block;width:36px;height:20px;border-radius:10px;background:#cbd5e1;transition:background-color .12s}";
    $f[]=".abt-sw-knob{position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.25);transition:transform .12s}";
    $f[]=".abt-sw-on .abt-sw-track{background:$fill}";
    $f[]=".abt-sw-on .abt-sw-knob{transform:translateX(16px)}";
    $f[]=".abt-sw-on .abt-sw-lbl{color:$press}";
    $f[]=".abt-sw:focus{outline:0}";
    $f[]=".abt-sw:focus-visible{box-shadow:0 0 0 2px #fff,0 0 0 4px $fill}";
    $f[]=".abt-sw-ro{cursor:default}";
    $f[]=".abt-sw.abt-l{position:relative;background:transparent;border:0}";
    $f[]=".abt-sw.abt-l[data-tip]:hover::after,.abt-sw.abt-l[data-tip]:focus-visible::after{content:attr(data-tip);position:absolute;left:0;top:calc(100% + 6px);z-index:1060;width:max-content;max-width:320px;white-space:normal;background:#0f172a;color:#fff;font-size:13px;font-weight:500;line-height:1.4;padding:6px 10px;border-radius:6px;text-align:left;pointer-events:none}";
    // Tuile svcstat : sa regle .svcstat .btn peint le fond, l'icone suit le texte.
    $f[]=".svcstat .abt>i,.svcstat .abt>svg{color:#fff}";
    $f[]="@media (prefers-reduced-motion:reduce){.abt,.abt-wiz,.abt-wiz-ico,.abt-wiz-go,.abt-sw-track,.abt-sw-knob{transition:none}.abt-wiz:hover .abt-wiz-go{transform:none}}";
    return @implode("\n",$f)."\n";
}

/**
 * Formulaires form_outside() + field_* (maquette stitch_artica_dhcp_server_params, 2026-11-11).
 *
 * Feuille GLOBALE, ajoutee a angular.css.php comme table_buttons_css() : la sortie de
 * form_outside() est presque toujours repassee par _ENGINE_parse_body() chez l'appelant,
 * un <style> en ligne y serait lu regle par regle comme des {tokens}.
 *
 * Le balisage reste en <table> (des centaines de pages injectent leurs propres <tr>) :
 * - form_outside() coupe le contenu en une carte (.afrm-card) par field_section() ;
 * - _field_intr() classe chaque ligne : .afrm-f (libelle au-dessus du champ, une
 *   colonne de la grille), .afrm-sw (interrupteur a droite), .afrm-full (pleine largeur) ;
 * - toute ligne <tr> sans classe garde un rendu de table pleine largeur.
 * Grille 2 colonnes seulement quand le formulaire fait au moins 720px (requete de
 * conteneur) : une modale etroite reste sur une colonne.
 */
function afrm_css($palette=array()):string{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(is_array($palette) && count($palette)>0 && function_exists("brand_ui_accents")){
        $b=brand_ui_accents($palette);
        if(is_array($b)){$ac=array_merge($ac,$b);}
    }
    $fill=function_exists("abt_fill")?abt_fill($ac["accent"]):$ac["accent_dark"];
    $f=array();
    // Le conteneur : plus de bordure "inset" ni de fond, les cartes portent le cadre.
    // Marqueur : fw.progress.php recharge la feuille quand il manque (console ouverte avant une mise a jour).
    $f[]=":root{--afrm-v:2}";
    $f[]=".afrm-wrap{margin-left:0!important;margin-right:0!important;width:auto!important}";
    $f[]=".afrm{container-type:inline-size;container-name:afrm;margin:10px 0 10px 0;padding-left:0!important;padding-right:0!important;color:#0f172a}";
    $f[]=".afrm,.afrm *{box-sizing:border-box}";
    $f[]=".afrm .ibox{margin-bottom:0;background:transparent}";
    $f[]=".afrm .ibox-title{background:transparent;border:0;padding:0 0 12px 0;min-height:0}";
    $f[]=".afrm .ibox-title h3{font-size:18px;font-weight:600;color:#0f172a;margin:0}";
    // Carte = un groupe de champs, ouvert par field_section().
    $f[]=".afrm-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px 8px 20px;";
    $f[]="box-shadow:0 1px 2px rgba(15,23,42,.06);margin:0 0 16px 0}";
    // La table devient un bloc, son tbody une liste (ou une grille 2 colonnes).
    $f[]="table.afrm-g{display:block;width:100%;border:0;margin:0}";
    $f[]="table.afrm-g>tbody{display:block}";
    $f[]="@container afrm (min-width:720px){table.afrm-g>tbody{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);column-gap:16px}}";
    $f[]="table.afrm-g>tbody>tr{grid-column:1/-1}";
    // Lignes d'une page (sans classe) : rendu de table classique, pleine largeur.
    $f[]="table.afrm-g>tbody>tr:not(.afrm-row){display:table;width:100%;table-layout:auto}";
    $f[]="table.afrm-g>tbody>tr.afrm-row{display:block}";
    $f[]="table.afrm-g>tbody>tr.afrm-row>td{display:block;width:auto!important;padding:0!important;text-align:left!important;border:0}";
    // Champ : libelle au-dessus, 1 colonne de la grille.
    $f[]="table.afrm-g>tbody>tr.afrm-f{grid-column:auto;padding:8px 0 10px 0}";
    $f[]="tr.afrm-f>td.afrm-l{margin-bottom:6px}";
    $f[]="tr.afrm-full{padding:8px 0}";
    // Interrupteur : libelle et aide a gauche, l'interrupteur a droite, filet entre lignes.
    $f[]="table.afrm-g>tbody>tr.afrm-sw{display:flex;align-items:center;gap:16px;padding:12px 0;border-top:1px solid #f1f5f9}";
    // Pas de filet sous l'en-tete : un <input type=hidden> peut s'intercaler entre les
    // deux lignes, d'ou nth-of-type plutot que l'adjacence "+".
    $f[]="table.afrm-g>tbody>tr.afrm-sec~tr.afrm-sw:nth-of-type(2),table.afrm-g>tbody>tr.afrm-sw:first-of-type{border-top-color:transparent}";
    $f[]="tr.afrm-sw>td.afrm-l{flex:1 1 auto;min-width:0}";
    // Interrupteur suivi de son aide : l'aide (ligne colspan=2) se lit sous le libelle.
    $f[]="table.afrm-g>tbody>tr.afrm-sw-x{padding-bottom:2px}";
    $f[]="table.afrm-g>tbody>tr.afrm-hrow{padding:0 0 12px 0}";
    $f[]="tr.afrm-hrow .afrm-h{margin-top:0;padding-right:120px}";
    $f[]="tr.afrm-sw>td.afrm-c{flex:0 0 auto}";
    // Libelle : premiere lettre en capitale, jamais "Title Case" sur une phrase.
    $f[]=".afrm-lbl{display:inline-block;font-size:13px;font-weight:500;color:#334155;line-height:1.4;text-transform:none!important}";
    $f[]=".afrm-lbl::first-letter{text-transform:uppercase}";
    $f[]=".afrm .afrm-lbl,.afrm .afrm-lbl>a{display:inline-block;font-size:13px!important;font-weight:500!important;color:#334155!important;text-decoration:none!important;text-decoration-line:none!important}";
    $f[]=".afrm .afrm-lbl.labelform_disabled,.afrm .afrm-lbl.labelform_disabled>a{color:#94a3b8!important}";
    // ::first-letter ne traverse pas un enfant inline-block : le lien d'aide reste inline.
    $f[]=".afrm .afrm-lbl>a{display:inline!important}";
    $f[]=".afrm-lbl a:hover{color:{$ac["accent_dark"]}!important}";
    $f[]=".afrm-i{color:#94a3b8;font-size:12px;margin-left:5px}";
    // Aide : texte gris, deux lignes au plus ; le libelle ouvre le texte complet.
    $f[]=".afrm-h{font-size:13px;color:#64748b;line-height:1.45;margin-top:3px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}";
    $f[]="tr.afrm-f .afrm-h{margin-top:5px}";
    // Saisie : 38px, rayon 8, bordure ardoise, anneau de focus de la marque.
    $f[]=".afrm .form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 1px 1px rgba(15,23,42,.04);font-size:13px;color:#0f172a;padding:6px 12px;background:#fff}";
    $f[]=".afrm textarea.form-control{height:auto}";
    $f[]=".afrm .form-control:focus{border-color:{$ac["accent_dark"]};box-shadow:0 0 0 2px {$ac["accent_soft"]};outline:0}";
    $f[]=".afrm .form-control[disabled],.afrm .form-control[readonly]{background:#f8fafc;color:#64748b}";
    $f[]=".afrm .afrm-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,'Liberation Mono',monospace;font-weight:500}";
    $f[]=".afrm .input-group{width:100%}";
    $f[]=".afrm .input-group .form-control:not(:first-child):not(:last-child){border-radius:0}";
    $f[]=".afrm .input-group>.form-control:first-child{border-top-right-radius:0;border-bottom-right-radius:0}";
    $f[]=".afrm .input-group>.form-control:last-child{border-top-left-radius:0;border-bottom-left-radius:0}";
    $f[]=".afrm .input-group>.form-control:only-child{border-radius:8px}";
    $f[]=".afrm .input-group-btn>.btn{height:38px;border:1px solid #cbd5e1;background:#f8fafc;color:#475569;box-shadow:none}";
    $f[]=".afrm .input-group-btn:last-child>.btn{border-radius:0 8px 8px 0;margin-left:-1px}";
    $f[]=".afrm .input-group-btn:first-child>.btn{border-radius:8px 0 0 8px;margin-right:-1px}";
    $f[]=".afrm .input-group-btn:empty{display:none}";
    // Listes chosen (field_array_hash).
    $f[]=".afrm .chosen-container-single .chosen-single{height:38px;line-height:36px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;box-shadow:0 1px 1px rgba(15,23,42,.04);font-size:13px;color:#0f172a}";
    $f[]=".afrm .chosen-container-single .chosen-single div b{background-position:0 9px}";
    $f[]=".afrm .chosen-container-active .chosen-single{border-color:{$ac["accent_dark"]};box-shadow:0 0 0 2px {$ac["accent_soft"]}}";
    $f[]=".afrm .chosen-container-multi .chosen-choices{min-height:38px;border:1px solid #cbd5e1;border-radius:8px;background:#fff}";
    $f[]=".afrm select.form-control{padding-right:30px}";
    // Interrupteur (field_checkbox) : 36x20, texte On/Off a gauche, jamais la couleur seule.
    $f[]=".afrm .switch{margin:0}";
    $f[]=".afrm .onoffswitch{position:relative;display:inline-flex!important;align-items:center;gap:10px;width:auto!important;user-select:none}";
    $f[]=".afrm .onoffswitch-label{position:relative;display:block!important;width:36px!important;height:20px!important;margin:0!important;border:0!important;border-radius:10px!important;background:#cbd5e1;cursor:pointer;overflow:visible!important;transition:background-color .15s ease}";
    $f[]=".afrm .onoffswitch-inner{display:none!important}";
    $f[]=".afrm .onoffswitch-switch{position:absolute!important;top:2px!important;left:2px!important;right:auto!important;bottom:auto!important;width:16px!important;height:16px!important;margin:0!important;border:0!important;border-radius:50%!important;background:#fff!important;box-shadow:0 1px 2px rgba(15,23,42,.25);transition:transform .15s ease}";
    $f[]=".afrm .onoffswitch-checkbox:checked+.onoffswitch-label{background:$fill}";
    $f[]=".afrm .onoffswitch-checkbox:checked+.onoffswitch-label .onoffswitch-switch{transform:translateX(16px)}";
    $f[]=".afrm .onoffswitch-checkbox:focus-visible+.onoffswitch-label{box-shadow:0 0 0 2px #fff,0 0 0 4px {$ac["accent_dark"]}}";
    $f[]=".afrm .onoffswitch-checkbox:disabled+.onoffswitch-label{opacity:.5;cursor:not-allowed}";
    $f[]=".afrm .afrm-swt{order:-1;display:inline!important;font-size:13px;font-weight:500;min-width:24px;text-align:right}";
    $f[]=".afrm .afrm-swt-on{color:{$ac["accent_dark"]}}";
    $f[]=".afrm .afrm-swt-off{color:#64748b}";
    $f[]=".afrm .onoffswitch-checkbox:checked~.afrm-swt-off,.afrm .onoffswitch-checkbox:not(:checked)~.afrm-swt-on{display:none!important}";
    // En-tete de carte (field_section).
    $f[]="table.afrm-g>tbody>tr.afrm-sec{display:block;padding:0 0 14px 0;margin:0 0 4px 0;border-bottom:1px solid #f1f5f9}";
    $f[]="tr.afrm-sec>td{display:block;padding:0!important}";
    $f[]=".afrm-sec-h{display:flex;align-items:center;gap:10px}";
    $f[]=".afrm-sec-ico{flex:0 0 28px;width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;background:{$ac["accent_soft"]};color:{$ac["accent_dark"]};border:1px solid {$ac["accent_line"]}}";
    $f[]=".afrm-sec-txt{flex:1 1 auto;min-width:0}";
    $f[]=".afrm-sec-t{font-size:15px;font-weight:600;color:#0f172a;line-height:1.4;margin:0}";
    $f[]=".afrm-sec-s{font-size:13px;color:#64748b;line-height:1.45;margin-top:2px}";
    $f[]=".afrm-sec .alert{margin:12px 0 0 0}";
    // Liens vers des sous-fenetres (field_link) : pastilles ardoise avec chevron.
    $f[]=".afrm-lk-bar{display:flex;flex-wrap:wrap;gap:8px;padding-top:4px}";
    $f[]=".afrm-lk{display:inline-flex;align-items:center;gap:8px;height:32px;padding:0 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;color:#334155;font-size:13px;font-weight:500;cursor:pointer;transition:background-color .15s ease}";
    $f[]=".afrm-lk:hover{background:#f1f5f9;color:#0f172a}";
    $f[]=".afrm-lk:focus-visible{outline:2px solid {$ac["accent_dark"]};outline-offset:2px}";
    $f[]=".afrm-lk>i,.afrm-lk>svg{color:#64748b;font-size:12px}";
    $f[]=".afrm-lk .afrm-lk-chev{font-size:10px;color:#94a3b8}";
    // Pied : le bouton d'enregistrement, aligne a droite, sous un filet.
    $f[]=".afrm-foot{display:flex;justify-content:flex-end;align-items:center;gap:8px;padding:4px 0 0 0}";
    $f[]=".afrm-foot hr{display:none}";
    $f[]="@media (prefers-reduced-motion:reduce){.afrm *{transition:none!important}}";
    return @implode("\n",$f)."\n";
}

// Nom du script servi (basename de SCRIPT_FILENAME, fixe par le serveur, jamais par le client) :
// cle de la liste des pages d'un compte AsMetaAdmin (usersMenus::MetaOnlyPageAllowed()).
function xprivs_script_page():string{
    $s=isset($_SERVER["SCRIPT_FILENAME"])?strval($_SERVER["SCRIPT_FILENAME"]):"";
    return basename($s);
}
