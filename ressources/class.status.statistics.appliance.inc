<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}
function incr_stats_apps_func($array){
	$array[]="SchedulesStatsApps";
	return $array;
}



function SchedulesStatsApps(){
	$EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
	$CategoriesDatabasesByCron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesDatabasesByCron"));
	$DisableCategoriesDatabasesUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableCategoriesDatabasesUpdates"));
	if($DisableCategoriesDatabasesUpdates==1){return;}
	if($CategoriesDatabasesByCron==1){
		events("CategoriesDatabasesByCron == 1 ",__FUNCTION__,__LINE__);
		return;}
	$EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard());
	if($EnableUfdbGuard==1){$RUN=true;}
	if($EnableLocalUfdbCatService==1){$RUN=true;}
	if(!$RUN){
		events("NOT NECESSARY TO UPDATE WEB FILTER DBs",__FUNCTION__,__LINE__);
		return;
	}
	
	

}