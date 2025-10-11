package ad

import (
	"fmt"
	"github.com/go-ldap/ldap/v3"
	"github.com/techoner/gophp"
	"handlers"
	"log"
	"strconv"
	"strings"
)

var (
	SelfADLdapServer   string
	SelfADLdapPort     int
	SelfADLdapIsSSL    bool
	SelfADLdapSSL      string
	SelfADLdapFQDN     string
	SelfADLdapUsername string
	SelfADLdapSuffix   string
	SelfADLdapPassword string
	SelfADConnections  []interface{}
	DebugAD            bool
)

func init() {
	activeDirectoryConnectionsData := handlers.GetSocketInfoString("ActiveDirectoryConnections")
	if len(activeDirectoryConnectionsData) == 0 {
		if DebugAD {
			log.Printf("ActiveDirectoryConnections Len=0")
		}
	}
	out, err := gophp.Unserialize([]byte(activeDirectoryConnectionsData))
	if err != nil {
		if DebugAD {
			log.Printf("error Unserialize itcharts.ids: ", err)
		}
	}
	SelfADConnections = out.([]interface{})
}

func LoadConnection(connID int) bool {
	for d, data := range SelfADConnections {
		if d == connID {
			for k, v := range data.(map[string]interface{}) {
				if k == "LDAP_SUFFIX" {
					SelfADLdapSuffix = v.(string)
				}
				if k == "LDAP_PASSWORD" {
					SelfADLdapPassword = v.(string)
				}
				if k == "LDAP_SERVER" {
					SelfADLdapServer = v.(string)
				}
				if k == "LDAP_PORT" {
					SelfADLdapPort, _ = strconv.Atoi(v.(string))
				}
				if k == "LDAP_DN" {
					SelfADLdapUsername = v.(string)
				}
				if k == "LDAP_SSL" {
					SelfADLdapSSL = v.(string)
				}
			}
		}

	ldapSuffix = strings.ToLower(activeDirectoryConnections["LDAP_SUFFIX"].(string))
	ldapPassword = activeDirectoryConnections["LDAP_PASSWORD"].(string)
	ldapServer = activeDirectoryConnections["LDAP_SERVER"].(string)
	ldapPort = activeDirectoryConnections["LDAP_PORT"].(int)
	ldapUsername = activeDirectoryConnections["LDAP_DN"].(string)
	if _, ok := activeDirectoryConnections["LDAP_SSL"]; ok {
		ldapSSL = activeDirectoryConnections["LDAP_SSL"].(string)
	}
	if ldapPort == 636 {
		ldapIsSSL = true
	}
}

func Connect() (*ldap.Conn, error) {
	proto := "ldap"
	if ldapIsSSL {
		proto = "ldaps"
	}
	// You can also use IP instead of FQDN
	l, err := ldap.DialURL(fmt.Sprintf("%s://%s:%d", proto, ldapServer, ldapPort))
	if err != nil {
		if isDebug {
			log.Printf("Failed to connect to ldap %s://%s:%d with error %s", proto, ldapServer, ldapPort, err)
		}
		return nil, err
	}

	return l, nil
}
