#!/bin/bash
INPUT=/tmp/menu.update.sh.$$
OUTPUT=/tmp/menu.update.output.sh.$$
trap "rm $OUTPUT; rm $INPUT; exit" SIGHUP SIGINT SIGTERM
DIALOG=${DIALOG=dialog}
function Updatep(){
	/usr/bin/python /usr/share/artica-postfix/menu.update.py --generate
	/tmp/menu.update.sh
}
function unstable(){
	/usr/bin/python /usr/share/artica-postfix/bin/menu-instable.py --build
	/tmp/menu.update.sh

}

while true
do


/usr/bin/dialog --clear  --nocancel --backtitle "Artica Update Menu" --title "[ U P D A T E -  M E N U ]" --menu "You can use the UP/DOWN arrow keys
  Choose the TASK" 20 100 10 roolback "Roolback official version" unstable "Update instable Service Pack" Quit "Return to main menu" 2>"${INPUT}"


  menuitem=$(<"${INPUT}")
  case $menuitem in
    roolback) Updatep;;
    unstable) unstable;;

    Quit) break;;
  esac
done
