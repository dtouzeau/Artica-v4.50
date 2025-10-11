#!/usr/bin/env python
import sys
import os
import traceback as tb
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
from unix import *
from postgressql import *





def writepid(category_id):
    pidpath="/var/run/compile-category.".category_id.".pid"
    outputFile = open(pidpath, "w")
    pid = str(os.getpid())
    outputFile.write(pid)
    outputFile.close()




if __name__ == "__main__":
    try:

        zargv=[]
        if len(sys.argv)==0:
            print("Please set the category_id to compile")
            sys.exit(0)

        category_id = intval(sys.argv[1:])
        writepid(category_id)
        main(zargv)
    except:
        xsyslog(tb.format_exc())
        squid_admin_mysql(0, "Fatal system Exception while compiling category [%s]" % category_id,
                          tb.format_exc(), "main", "compile-category", 99)

