#!/usr/bin/env -S python3 -O
# -*- coding: utf-8 -*-
# SP 131
import sys
from multiprocessing import Process
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import re
import random
import os
import ldap
# from articacatz import mysqlcatz
import hashlib
from adclass import *
from unix import *
import os.path
import datetime
import urllib
import ldap
import traceback as tb
import socket
import syslog
from pprint import pprint
from phpserialize import serialize, unserialize


WebErrorPagesCompiled=GET_INFO_STR("WebErrorPagesCompiled")
web_pages = unserialize(WebErrorPagesCompiled)
for index in web_pages:
    if web_pages[index].has_key("category"): print("YES as key")
    print(web_pages[index]["category"])


