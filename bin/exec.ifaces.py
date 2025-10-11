#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
import netifaces
import json
OUT={}
OUT["INTERFACES"]={}
OUT["GATEWAY"]={}
nics = netifaces.interfaces()

for Interface in nics:
    OUT["INTERFACES"][Interface]=netifaces.ifaddresses(Interface)


OUT["GATEWAY"] = netifaces.gateways()
dump=json.dumps(OUT)

SET_INFO("PythonIfaces",dump)


