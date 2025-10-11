#!/usr/bin/env python
import sys
import os,re
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
from postgressql import *
from K9Query import *


def main(argv):
    global debug
    if len(argv)==0:
        print("Help: ")
        print("\t--domain [xxx]: Get domain category")
        sys.exit(0)

    for i in argv:
        print("%s" % i)

    k9 = K9Query()
    matches=re.search("--verbose"," ".join(argv))
    if matches:
        if len(argv)>1:
            print(" * * Verbosed... argv[1]=%s" % argv[1])
        else:
            print(" * * Verbosed...")
        debug=True
        k9.debug=True


    if argv[0]=="--domain":
        if k9.debug: print("Query %s" % argv[1])
        k9.query(argv[1])
        if k9.error:
            print("Error %s" % k9.errorstr)
            sys.exit(0)

        print("%s\nicat\t%s\nzcat\t%s\n" %(argv[1],k9.icat,k9.zcat))
        sys.exit(0)


    POSTGRES = Postgres(1,"articastats")
    sql = "SELECT sitename,familysite,category,siteid FROM statscom_websites WHERE k9catz=0"
    if debug: print(sql)
    rows = POSTGRES.QUERY_SQL(sql)
    if not POSTGRES.ok: print(POSTGRES.sql_error)
    for row in rows:
        sitename=row[0]
        familysite=row[1]
        category=row[2]
        siteid=row[3]
        print("%s %s %s" % (sitename,familysite,category))
        category=k9.query(sitename)
        k9catz=k9.icat
        POSTGRES.QUERY_SQL("UPDATE statscom_websites SET k9catz=%s, category=%s WHERE siteid=%s" % (k9catz,category,siteid))
        if not POSTGRES.ok: print(POSTGRES.sql_error)






if __name__ == "__main__":
   main(sys.argv[1:])
