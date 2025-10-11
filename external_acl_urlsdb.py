#!/usr/bin/env python
import sys
import os
import traceback as tb
import logging
import re
import anydbm
import hashlib
import time


def main(arg):

    levelLOG = logging.INFO
    if os.path.exists("/etc/squid3/urlsdb.debug"): levelLOG=logging.DEBUG


    logging.basicConfig(format='%(asctime)s [%(process)d] [%(levelname)s] %(message)s',
                        filename='/var/log/squid/urlsdb.log', filemode='a', level=levelLOG)
    logging.raiseExceptions = False

    logging.debug("Starting new helper...")
    CountSleep = 0
    while True:
        line = sys.stdin.readline().strip()
        logging.debug("Receive '" + line + "'")
        if len(line) < 2:
            logging.debug("Sleeping 1s " + str(CountSleep) + "/3")
            time.sleep(1)
            CountSleep = CountSleep + 1
            if CountSleep > 3:
                logging.debug("Die() maxcount >3 -> raise SystemExit(0)")
                raise SystemExit(0)
            continue

        MainArray = line.split(" ")



        try:
            Concurrency = int(MainArray[0])
            URL = str(MainArray[1])
            groupid = int(MainArray[2])
            Concurrency_text = str(Concurrency) + " "

            matches=re.match('^http(s|):\/\/',URL)
            if not matches:
                Port=0
                Proto="http"
                Host=URL
                matches = re.match('^(.+?):([0-9]+)', URL)
                if matches:
                    Port=int(matches.group(2))
                    if Port==443:
                        Port=0
                        Proto="https"
                        Host=matches.group(1)
                if Port==0:
                    URL=Proto+"://"+Host+"/"
                else:
                    URL = Proto + "://" + Host+":"+str(Port)+"/"

            logging.debug("URL:" + URL+" For Group "+ str(groupid))

            dbpath = "/etc/squid3/acls/urlsdb/" + str(groupid)+"/urls.db"
            if not os.path.exists(dbpath):
                LineToSend = Concurrency_text + "ERR\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue

            try:
                db = anydbm.open(dbpath, 'r')
            except:
                logging.debug("FATAL while open database "+dbpath)
                logging.debug(tb.format_exc())
                LineToSend = Concurrency_text + "ERR\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue

            try:
                md5 = hashlib.md5(URL.encode('utf-8')).hexdigest()
                if db.has_key(md5):
                    db.close()
                    logging.debug(md5 +" MATCHES!")
                    sys.stdout.write(Concurrency_text + "OK\n")
                    sys.stdout.flush()
                    continue
            except:
                logging.debug("FATAL while query database " + dbpath)
                logging.debug(tb.format_exc())
                LineToSend = Concurrency_text + "ERR\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue

            logging.debug(md5 + " UNKNOWN")
            LineToSend = Concurrency_text + "ERR\n"
            sys.stdout.write(LineToSend)
            sys.stdout.flush()
            continue
        except:
            logging.debug(tb.format_exc())
            sys.stdout.write("ERR\n")
            sys.stdout.flush()
            continue


if __name__ == '__main__':
    sys.exit(main(arg=sys.argv[1:]))

