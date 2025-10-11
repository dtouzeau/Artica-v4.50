package internal

import (
	"context"
	"errors"
	"github.com/bradfitz/gomemcache/memcache"
	"github.com/go-redis/redis/v8"
	"log"
	"time"
)

var isDebugMemcached bool
var MC = memcache.New("/var/run/memcached.sock")
var ctxRedis = context.Background()
var rdb *redis.Client

func InitMemcached(debug bool) {
	isDebugMemcached = debug
}

func Get(key string) (response string, err error) {
	log.Printf("Memcached GET %s", key)
	val, err := MC.Get(key)
	if err != nil {
		if isDebugMemcached {
			log.Printf("Memcached Get->Error: %s", err)
		}
		return "", err
	}
	if string(val.Value) == "" {
		if isDebugMemcached {
			log.Printf("Memcached Get->Error: %s", errors.New("empty value"))
		}
		return "", errors.New("empty value")
	}
	value := string(val.Value)
	return value, nil
}

func Set(key string, value string, ttl int32) {
	log.Printf("Memcached SET %s", key)
	err := MC.Set(&memcache.Item{Key: key, Value: []byte(value), Expiration: ttl})
	if err != nil {
		if isDebugMemcached {
			log.Printf("Memcached Get->Error: %s", err)
		}
		//panic(err)
	}
}

func Inc(key string, value string) {
	if isDebugMemcached {
		log.Printf("Memcached Inc %s", key)
	}

	_, err := Get(key)
	if err != nil {
		Set(key, "1", 36000)
	} else {
		MC.Increment(key, 1)
	}
}

func redisConnect() bool {
	rdb = redis.NewClient(&redis.Options{
		Addr:     "127.0.0.1:6123",
		Password: "", // no password set
		DB:       0,  // use default DB
	})

	err := rdb.Set(ctxRedis, "key", "value", 0).Err()
	if err != nil {

		if isDebugMemcached {
			log.Printf("Failed to connect to redis server %s", err)
		}
		return false
	}
	return true

}

func RedisGet(key string) string {
	if !redisConnect() {
		if isDebugMemcached {
			log.Printf("Failed to connect to redis server")
		}
		return ""
	}
	val, err := rdb.Get(ctxRedis, key).Result()
	if err == redis.Nil {
		if isDebugMemcached {
			log.Printf("key %s does not exist", key)
		}
		return ""
	} else if err != nil {
		if isDebugMemcached {
			log.Printf("erro getting key %s => %s", key, err)
		}
		return ""
	} else {
		return val
	}
	return ""
}

func RedisSet(key string, val string, ttl time.Duration) bool {
	if !redisConnect() {
		if isDebugMemcached {
			log.Printf("Failed to connect to redis server")
		}
		return false
	}
	err := rdb.Set(ctxRedis, key, val, ttl).Err()
	if err != nil {
		if isDebugMemcached {
			log.Printf("erro setting key %s => %s", key, err)
		}
		return false
	}
	return true
}
