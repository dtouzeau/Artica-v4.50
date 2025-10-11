#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import asyncore, socket, sqlite3, time, syslog, os, signal
import requests,memcache
import datetime





def timeround10():
    now = datetime.datetime.now()
    a, b = divmod(round(now.minute, -1), 60)
    min='%i-%02i' % ((now.hour + a) % 24, b)
    return now.strftime("%Y-%m-%d-")+min




Minutes=timeround10()
print Minutes





