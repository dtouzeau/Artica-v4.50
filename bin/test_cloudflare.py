#!/usr/bin/env python

from cloudflare_query import *

cloudflare=CloudflareQuery(True)
cloudflare.output=True
cloudflare.debug=True
if cloudflare.query("dotypemaintainfileclicks.icu") ==1 : print("OK!!!")
if cloudflare.query("www.articatechc4qd.com") ==1 : print("OK!!!")
