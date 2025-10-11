package handlers

import (
	"bytes"
	"errors"
	"log"
	"os"
	"strconv"
)

func GetSocketInfoInt(token string) int {

	if _, err := os.Stat("/etc/artica-postfix/settings/Daemons/" + token); errors.Is(err, os.ErrNotExist) {
		if isDebug {
			log.Printf("Token %s dont exist %s", token, err)
		}
		return 0
	}
	tk, err := os.ReadFile("/etc/artica-postfix/settings/Daemons/" + token)
	if err != nil {
		if isDebug {
			log.Printf("Failed read file %s %s %s", token, tk, err)
		}
		return 0
	}
	tk = bytes.TrimSuffix(tk, []byte("\n"))
	tk = bytes.TrimSpace(tk)
	tkint, err := strconv.Atoi(string(tk))
	if err == nil {
		if isDebug {
			log.Printf("Init converted file %s val %s => %d", token, tk, tkint)
		}
		return tkint
	} else {
		if isDebug {
			log.Printf("Failed to convert to init token %s => %d", token, tkint)
		}
		return 0
	}

	return 0
}

func GetSocketInfoBool(token string) bool {
	if _, err := os.Stat("/etc/artica-postfix/settings/Daemons/" + token); errors.Is(err, os.ErrNotExist) {
		if isDebug {
			log.Printf("Token %s dont exist %s", token, err)
		}
		return false
	}
	tk, err := os.ReadFile("/etc/artica-postfix/settings/Daemons/" + token)
	if err != nil {
		if isDebug {
			log.Printf("Failed read file %s %s %s", token, tk, err)
		}
		return false
	}
	tk = bytes.TrimSuffix(tk, []byte("\n"))
	tk = bytes.TrimSpace(tk)
	boolValue, err := strconv.ParseBool(string(tk))
	if err == nil {
		if isDebug {
			log.Printf("Bool converted file %s val %s => %t", token, tk, boolValue)
		}
		return boolValue
	} else {
		if isDebug {
			log.Printf("Failed to convert to bool token %s => %s", token, err)
		}
		return false
	}

	return false
}

func GetSocketInfoString(token string) string {
	if _, err := os.Stat("/etc/artica-postfix/settings/Daemons/" + token); errors.Is(err, os.ErrNotExist) {
		if isDebug {
			log.Printf("Token %s dont exist %s", token, err)
		}
		return ""
	}
	tk, err := os.ReadFile("/etc/artica-postfix/settings/Daemons/" + token)
	if err != nil {
		if isDebug {
			log.Printf("Failed read file %s %s %s", token, tk, err)
		}
		return ""
	}
	if isDebug {
		log.Printf("Token converted to string correctly %s val %s", token, tk)
	}
	return string(tk)
}
