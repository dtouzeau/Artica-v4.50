#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import socket
import logging
from unix import *
import os
import gzip
from datetime import datetime, timedelta
import hashlib
import re
import tldextract
from urlparse import urlparse
from postgressql import *
import pycurl
from StringIO import StringIO
import pprint
import datetime
from mysqlclass import *
from itchartclass import *
import base64
import psutil
import socket
from phpserialize import serialize, unserialize
from ldapclass import *
import dns.resolver
import inspect
import shutil
from ping import *
from activedirectoryclass import *
from squidlogsparseclass import *
with open("/var/log/squid/access.log", 'rb') as f_in, gzip.open("/var/log/squid/access.log.gz", 'wb') as f_out:
        shutil.copyfileobj(f_in, f_out)
    

log=SquidLog("/var/log/squid/access.log.gz")
for l in log:
    print l.rfc931




 

