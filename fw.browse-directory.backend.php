<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

$tpl=new template_admin();
header('Content-Type: application/json');
$id=urlencode($_GET["id"]);
if($_GET["id"]=="#" or $_GET["id"]=="/" or $_GET["id"]==""){
    if(isset($_GET["basePath"])){
        if($tpl->IsBase64($_GET["basePath"])){
            $basePath=base64_decode($_GET["basePath"]);
        }else{
            // Pentest 2026-11-04 : basePath etait recopie sans encodage dans la page (XSS).
            VERBOSE(req_html($_GET["basePath"])." is not a valid base64 path");
            $basePath=$_GET["basePath"];
        }
        if($basePath<>"/"){
            $id=urlencode($basePath);
        }
    }
}
if($id=="/"){
    $id=urlencode("/");
}
if(strlen($id)<=0){
    $id=urlencode("/");
}
VERBOSE("/system/tree/$id",__LINE__);
$json=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tree/$id");
// Chemin de base invalide (ex. basePath=1 venant d'un appelant qui a decale les
// arguments de field_browse_directory) : le daemon repond {"error":"open /1: no such
// file or directory"} et jstree affiche un arbre vide, sans aucun message. On
// retombe sur la racine pour que le selecteur reste utilisable.
// Le repli ne vaut que pour le CHARGEMENT INITIAL (id="#") : pour un noeud
// enfant en erreur (automount injoignable, repertoire disparu) renvoyer la liste
// racine ferait apparaitre /home, /media... comme enfants de ce noeud, avec des
// ids deja presents dans l'arbre -- jstree corrompt alors son modele et l'arbre
// entier se vide (constate le 2026-09-11 sur un automount Synology).
$initial=(!isset($_GET["id"]) || $_GET["id"]=="#" || $_GET["id"]=="/" || $_GET["id"]=="");
$decoded=json_decode((string)$json,true);
if($initial && $id!=urlencode("/") && (!is_array($decoded) || isset($decoded["error"]))){
    VERBOSE("/system/tree/$id -> ".(is_array($decoded)&&isset($decoded["error"])?$decoded["error"]:"invalid answer").", fallback to /",__LINE__);
    $json=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tree/".urlencode("/"));
}
// Noeud enfant en erreur : jstree rendrait l'objet {"error":...} comme un noeud
// sans nom. On lui donne une feuille desactivee qui porte le message, pour que
// l'exploitant voie POURQUOI ce repertoire est vide (automount injoignable...).
if(!$initial && is_array($decoded) && isset($decoded["error"])){
    // Pentest 2026-11-04 : id (chemin repris dans le message d'erreur) etait recopie sans encodage dans la page (XSS) : jstree rend text en HTML.
    $json=json_encode(array(array("text"=>req_html((string)$decoded["error"]),"icon"=>"fa fa-triangle-exclamation",
        "children"=>false,"state"=>array("disabled"=>true))));
}
echo $json;


