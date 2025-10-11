package internal

import (
	"github.com/bradfitz/gomemcache/memcache"
	"log"
)

var MC = memcache.New("/var/run/memcached.sock")

func Get(key string, isDebug bool) string {
	val, err := MC.Get(key)
	if err != nil {
		if isDebug {
			log.Printf("Memcached Get->Error: %s", err)
		}
		panic(err)
	}
	if string(val.Value) == "" {
		return ""
	}
	return string(val.Value)
}

func Set(key string, value []byte, ttl int32) {
	err := MC.Set(&memcache.Item{Key: key, Value: value, Expiration: ttl})
	if err != nil {
		panic(err)
	}
}
