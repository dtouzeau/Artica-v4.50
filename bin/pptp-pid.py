#!/usr/bin/env python

import sys
import tdb

cnx = tdb.open("/var/run/pppd2.tdb")

for key in cnx.iterkeys():
    print(key)


