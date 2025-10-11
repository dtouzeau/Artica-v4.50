#!/usr/bin/env python
import sys
import os
import traceback as tb
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
from unix import *
from dnsfirewallclass import *
import re
from datetime import datetime



def main(argv):
    global debug
    start_time = datetime.now()
    debug=False
    SourceIPAddr = argv[0]
    domainname = argv[1]
    QueryType  = argv[2]
    qstate=""
    INTERNAL_LOGS=[]

    FIREWALLCLASS = dnsfw(True)
    FIREWALLCLASS.DebugTool=True
    firewallid = FIREWALLCLASS.operate(SourceIPAddr, QueryType, domainname, "IN")
    for text in FIREWALLCLASS.INTERNAL_LOGS:
        INTERNAL_LOGS.append(text)

    print("<H2>{results} {from} %s {domain} %s {query} %s</H2>" % (SourceIPAddr,domainname,QueryType))


    if firewallid>0:
        if FIREWALLCLASS.generate_response(qstate, domainname, QueryType, QueryType, firewallid):
            for text in FIREWALLCLASS.INTERNAL_LOGS:
                INTERNAL_LOGS.append(text)
                if FIREWALLCLASS.MAIN_ACTION=="REFUSED":
                    FIREWALLCLASS.MAIN_ACTION="<span style='color:red'>REFUSED</span>"
            print("<H3>Rule ID: %s action: <strong>%s</strong></H3>" % (firewallid,FIREWALLCLASS.MAIN_ACTION))
        else:
            print("<H3>Rule ID: %s Generate response failed</H3>" % (firewallid))
    else:
        print("<H3>Rule ID: {none} action <strong>PASS</strong></h3>")

    print("<table style='width:100%'>")
    print("<tr><th>{events}</th></tr>")
    for text in INTERNAL_LOGS:
        print("<tr><td><small>%s</small></td></tr>" % text)

    print("</tr></table>")



























if __name__ == "__main__":
   main(sys.argv[1:])

