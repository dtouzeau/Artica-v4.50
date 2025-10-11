import sys,os
global GEO_MODULE
GEO_MODULE=True
try:
    import geoip2.database
    import geoip2.errors
except ImportError:
    GEO_MODULE = False
import traceback as tb
import re

sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *



class geoip2free:
    def __init__(self, DEBUG):
        self.DEBUG=DEBUG
        self.iso_code=""
        self.country=""
        self.city=""
        self.error=""
        self.autonomous_system_number=0
        self.autonomous_system_organization=""
        self.city_path="/usr/local/share/GeoIP/GeoLite2-City.mmdb"
        self.anonymous_path="/usr/local/share/GeoIP/GeoIP2-Anonymous-IP.mmdb"
        self.asn_database="/usr/local/share/GeoIP/GeoLite2-ASN.mmdb"
        self.country_path="/usr/local/share/GeoIP/GeoLite2-Country.mmdb"
        self.EnableGeoipUpdate=GET_INFO_INT("EnableGeoipUpdate")


    def operate(self,ipaddr):
        global GEO_MODULE
        self.iso_code=""
        self.country=""
        self.city=""
        self.error=""
        self.autonomous_system_number=0
        self.autonomous_system_organization=""
        if self.EnableGeoipUpdate == 0:
            self.error="GeoIP lookup is disabled ( see EnableGeoipUpdate )"
            return False
        if not GEO_MODULE:
            self.error = "Python libraries are not installed"
            return False
        if not os.path.exists(self.city_path):
            self.error = "GeoLite2-City.mmdb is not installed"
            return False
        ipaddr=ipaddr.strip()
        matches = re.search('^[0-9\.]+$', ipaddr)
        if not matches:
            self.error="%s is not an Ipv4 address"
            return False

        try:
            with geoip2.database.Reader(self.city_path) as reader:
                response = reader.city(ipaddr)
                self.iso_code=response.country.iso_code
                self.country = response.country.name
                self.city=response.city.name

            if os.path.exists(self.asn_database):
                with geoip2.database.Reader(self.asn_database) as reader:
                    response = reader.asn(ipaddr)
                    self.autonomous_system_number=response.autonomous_system_number
                    self.autonomous_system_organization = response.autonomous_system_organization

        except geoip2.errors.AddressNotFoundError:
            self.error="{} was not found in the GeoIp database".format(ipaddr)
            return False

        except:
            self.error=tb.format_exc()
            return False

        return True

