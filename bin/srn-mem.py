#!/usr/bin/python -O
# -*- coding: utf-8 -*-
# SP 131
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import re
import traceback as tb
import redis




def main(argv):

    print("Open Connection...")
    r=redis.Redis(unix_socket_path="/var/run/redis/redis-server.sock")
    value = r.get("TEST_KEY")
    print("Get Key TEST_KEY: %s" % value)


    print("Create Key TEST_KEY with value OK for 5 seconds")
    r.set("TEST_KEY", "OK",5)

    value=r.get("TEST_KEY")
    print("Get Key TEST_KEY: %s" % value)

if __name__ == "__main__":
   main(sys.argv[1:])