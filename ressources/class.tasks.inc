<?php

class system_tasks{
    var $tasks_array=array();
    var $tasks_explain_array=array();
    var $tasks_processes=array();
    var $task_disabled=array();

    // Cache statique par requete: la classe est instanciee plusieurs fois par
    // page (4 fois rien que dans fw.system.tasks.php) - sans memorisation,
    // chaque `new system_tasks()` ajoute un aller-retour vers le daemon. La
    // duree de vie d'un processus PHP est celle d'une requete, donc une
    // statique de classe suffit (pas besoin de TTL/expiration).
    private static $CATALOG_CACHE=null;

    public function __construct(){
        $this->loadFromCatalog();
    }

    // Charge le catalogue depuis /system/tasks/types et alimente les 4
    // proprietes publiques historiques. En cas de daemon injoignable (socket
    // fermee, JSON invalide, success=false), REST_API() renvoie une chaine
    // vide/non-JSON: json_decode() rend alors null, et les 4 proprietes
    // restent des tableaux vides plutot que de lever une erreur. C'est un
    // choix delibere: un tableau vide fait echouer chaque isset() cote
    // appelant (exec.schedules.php, fw.*.php) de la meme facon qu'un
    // identifiant de tache inconnu le faisait deja avant ce refactor - la
    // page continue de s'afficher (vide/neutre), jamais d'erreur PHP fatale
    // ni de page blanche.
    private function loadFromCatalog(){
        $json=self::fetchCatalog();
        // Un echec de chargement laisse les 4 proprietes vides et la page
        // s'affiche neutre -- mais SANS TRACE, l'ancien code plantait
        // bruyamment. C'est le seul diagnostic disponible pour le scenario de
        // deploiement decale (binaire Go pas encore livre, route absente,
        // daemon arrete) : sans cette ligne, une console qui n'affiche plus
        // aucun type de tache ne laisse rien derriere elle.
        if(!isset($json->success) || !$json->success){
            writelogs("/system/tasks/types: reponse inexploitable (daemon arrete, route absente du binaire deploye, ou success=false) - catalogue vide",
                __FUNCTION__,__FILE__,__LINE__);
            return;
        }
        if(!isset($json->data->types)){
            writelogs("/system/tasks/types: success=true mais data.types absent - catalogue vide",
                __FUNCTION__,__FILE__,__LINE__);
            return;
        }

        foreach ($json->data->types as $id=>$t){
            $id=intval($id);
            if(isset($t->label)){$this->tasks_array[$id]=$t->label;}
            if(isset($t->explain)){$this->tasks_explain_array[$id]=$t->explain;}
            // N'ajouter une entree que si `process` n'est pas vide: le code
            // appelant (exec.schedules.php) teste sa presence avec isset()
            // pour decider si une tache est executable.
            if(isset($t->process) && $t->process<>""){$this->tasks_processes[$id]=$t->process;}
        }

        if(isset($json->data->disabled)){
            foreach ($json->data->disabled as $id=>$reason){
                $this->task_disabled[intval($id)]=true;
            }
        }
    }

    private static function fetchCatalog(){
        if(self::$CATALOG_CACHE !== null){return self::$CATALOG_CACHE;}
        $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tasks/types");
        $json=json_decode($raw);
        self::$CATALOG_CACHE=$json;
        return $json;
    }


    // STATIQUE, et delibere: cette fonction est PURE (elle ne lit aucune
    // propriete d'instance, seulement son argument), alors que le constructeur
    // de la classe fait un aller-retour HTTP vers /system/tasks/types. Une page
    // de la famille PROXY qui instanciait system_tasks juste pour l'atteindre
    // payait donc, a chaque rendu de tableau, le catalogue de l'AUTRE famille --
    // dont elle n'utilise pas une seule entree.
    //
    // Les appels existants en $obj->PatternToHuman(...) continuent de
    // fonctionner: PHP autorise l'appel d'une methode statique via -> (c'est
    // l'inverse -- appeler une methode d'instance statiquement -- qui est fatal
    // depuis PHP 8). Ne pas y ajouter de $this.
    public static function PatternToHuman($pattern,$nobr=false):string{
        if(!preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $pattern,$re)){return "{corrupted}...";}
        $min=$re[1];
        $hour=$re[2];
        $DayOfMonth=$re[3];
        $Year=$re[4];
        $DayOfWeek=$re[5];
        $DD=array();
        $MM=array();
        $HH=array();
        if($min=="*"){$mins="{all_minutes}";$MM[]="{all_minutes}";}
        if($hour=="*"){$hours="{all_hours}";$HH[]="{all_hours}";}
        if($DayOfWeek=="*"){$days="{all_days}";$DD[]="{all_days}";}else{$days=$DayOfWeek;}

        VERBOSE("DayOfWeek:$DayOfWeek",__LINE__);

        $Months=array(1=>"January",2=>"February",3=>"March",4=>"April",5=>"May",6=>"June",7=>"July",8=>"August",9=>"September",10=>"October",11=>"November",12=>"December");

        $array_day_of_week=array(
            "0"=>"{Sunday}",
            "1"=>"{Monday}",
            "2"=>"{Tuesday}",
            "3"=>"{Wednesday}",
            "4"=>"{Thursday}",
            "5"=>"{Friday}",
            "6"=>"{Saturday}");

        if(count($HH)==0){
            if(strpos($hour, ",")){$HH=explode(",",$hour);}else{$HH[]=$hour;}
        }

        if(count($MM)==0){
            if(strpos($min, ",")){$MM=explode(",",$min);}else{$MM[]=$min;}
        }
        if(count($DD)==0){
            if(strpos($DayOfWeek, ",")){$DD=explode(",",$DayOfWeek);}else{$DD[]=$DayOfWeek;}
        }


        $br="";
        $t=array();
        $f=array();

        foreach ($DD as $DAY_X){
            //echo "Day: $DAY_X\n";
            if(isset($array_day_of_week[$DAY_X])){
                $f[]=$array_day_of_week[$DAY_X];
            }else{
                $f[]="$DAY_X, ";
            }

            foreach ($HH as $hour_x){
                if(is_numeric($hour_x)){if(strlen($hour_x)==1){$hour_x="0$hour_x";}}
                foreach ($MM as $min_x){
                    if(is_numeric($min_x)){if(strlen($min_x)==1){$min_x="0$min_x";}}
                    $t[]="$hour_x:$min_x";
                }
            }

        }
        if(!$nobr){$br="<br>";}

        if($Year<>"*"){
            return "{each} {year} {{$Months[$Year]}} {day} $DayOfMonth {at} ".@implode(", ", $t);

        }

        if($DayOfMonth<>"*"){
            return "{each} {month} {day} $DayOfMonth {at} ".@implode(", ", $t);

        }




        return @implode(", ",$f)."$br {at} ".@implode(", ", $t);

    }


    public function CheckDefaultSchedules():bool{
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tasks/defaults"));
        return isset($json->success) && $json->success;
    }
}
