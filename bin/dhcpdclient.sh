#!/bin/bash
# Artica Tech @2017
timestamp() {
  date +"%s"
}


action=$1;
client_ip=$2;
client_mac=$3
client_name=$4;
DIRECTORY="/home/artica/dhcpd/queue";



current_time=$(timestamp)
###################
if [ ! -d "$DIRECTORY" ]; then
  mkdir -p $DIRECTORY
fi

echo "$current_time|$action|$client_ip|$client_name|$client_mac" >"$DIRECTORY/$current_time-$client_ip";
exit 0;
