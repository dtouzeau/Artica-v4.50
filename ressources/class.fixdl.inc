<?php

// Bouchon pour les SAPI ou dl() a ete retiree. La declaration etait
// inconditionnelle: partout ou dl() existe deja (c'est le cas en CLI comme sous
// PHP-FPM), PHP refusait la redeclaration et tuait la page appelante
// ("Cannot redeclare dl()"). Ce fichier est inclus par class.xapian.inc.
if(!function_exists("dl")){
	function dl($lib){
		return false;

	}
}
