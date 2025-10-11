package internal

import (
	"bufio"
	"crypto/md5"
	"encoding/hex"
	"errors"
	"fmt"
	"github.com/allegro/bigcache/v3"
	"io"
	"log"
	"os"
	"strconv"
	"strings"
	"time"
)

var InMemoryCache *bigcache.BigCache

var (
	SelfLifeWindow       time.Duration = 86400
	SelfHardMaxCacheSize int           = 2048
	SelfShards                         = 512
	isDebugBigCache      bool
)

func InitBigcache(debug bool) {

	SelfLifeWindow = time.Duration(GetSocketInfoInt("Go_Shield_Server_Cache_Time"))
	SelfHardMaxCacheSize = GetSocketInfoInt("Go_Shield_Server_DB_Size")
	isDebugBigCache = debug
	SelfShards = GetSocketInfoInt("Go_Shield_Server_DB_Shards")
	if SelfLifeWindow == 0 {
		SelfLifeWindow = time.Duration(86400)
	}

	if SelfHardMaxCacheSize == 0 {
		SelfHardMaxCacheSize = 2048
	}

	if SelfShards == 0 {
		SelfShards = 1024
	}

	config := bigcache.Config{
		// number of shards (must be a power of 2)
		Shards: SelfShards,

		// time after which entry can be evicted
		LifeWindow: SelfLifeWindow * time.Second,

		// Interval between removing expired entries (clean up).
		// If set to <= 0 then no action is performed.
		// Setting to < 1 second is counterproductive — bigcache has a one second resolution.
		CleanWindow: 5 * time.Minute,

		// rps * lifeWindow, used only in initial memory allocation
		MaxEntriesInWindow: 1000 * 10 * 60,

		// max entry size in bytes, used only in initial memory allocation
		MaxEntrySize: 512,

		// prints information about additional memory allocation
		Verbose: isDebugBigCache,

		// cache will not allocate more memory than this limit, value in MB
		// if value is reached then the oldest entries can be overridden for the new ones
		// 0 value means no size limit
		HardMaxCacheSize: SelfHardMaxCacheSize,

		// callback fired when the oldest entry is removed because of its expiration time or no space left
		// for the new entry, or because delete was called. A bitmask representing the reason will be returned.
		// Default value is nil which means no callback and it prevents from unwrapping the oldest entry.
		OnRemove: nil,

		// OnRemoveWithReason is a callback fired when the oldest entry is removed because of its expiration time or no space left
		// for the new entry, or because delete was called. A constant representing the reason will be passed through.
		// Default value is nil which means no callback and it prevents from unwrapping the oldest entry.
		// Ignored if OnRemove is specified.
		OnRemoveWithReason: nil,
	}
	InMemoryCache, _ = bigcache.NewBigCache(config)
}
func Md5string(str string) string {
	h := md5.New()
	io.WriteString(h, str)
	hashed := hex.EncodeToString(h.Sum(nil))
	return hashed
}

func Append(key string, value string) {
	InMemoryCache.Append(key, []byte(value))
}

func Fetch(key string) (response *string, error error) {
	if value, err := InMemoryCache.Get(key); err == nil {
		val := string(value)
		if val == "" {
			err = errors.New("empty val")
			if isDebugBigCache {
				log.Printf("Unable to Fetch %s due %s", key, err)
			}
			return nil, err
		}
		return &val, nil
	} else {
		if isDebugBigCache {
			log.Printf("Unable to Fetch %s due %s", key, err)
		}
		return nil, err
	}

	//return nil
}

func Increment(key string) {
	counter, _ := Fetch(key)
	if counter == nil {
		val := 0
		val = val + 1
		Append(key, strconv.Itoa(val))
	} else {
		val, err := strconv.Atoi(*counter)
		if err == nil {
			val = val + 1
			Append(key, strconv.Itoa(val))
		}
	}

}

func Decrement(key string) {
	counter, _ := Fetch(key)
	val, err := strconv.Atoi(*counter)
	if err == nil {
		val = val - 1
		if val < 0 {
			val = 0
		}
		Append(key, strconv.Itoa(val))
	}
}

func UserAliases(mac string, ipaddr string, ipstrongswan string, isDebugBigCache bool) string {
	mac = strings.ToLower(mac)
	val := ""
	key := ""
	if len(mac) > 5 {
		key = fmt.Sprintf("%s:alias", mac)
		macpath := fmt.Sprintf("/home/artica/UsersMac/Caches/%s", mac)
		//val = Get(key, isDebugBigCache)
		//if val != "" {
		//	if val == "NONE" {
		//		return ""
		//	}
		//	sfinl := strings.Split(val, "|")
		//	if len(sfinl) > 0 {
		//		return sfinl[0]
		//	}
		//	return val
		//}
		if value, err := InMemoryCache.Get(key); err == nil {
			val = string(value)
			if val != "" {
				if val == "NONE" {
					return ""
				}
				sfinl := strings.Split(val, "|")
				if len(sfinl) > 0 {
					return sfinl[0]
				}
				return val
			}
		}

		if _, err := os.Stat(macpath); !os.IsNotExist(err) {
			f, err := os.Open(macpath)

			if err != nil {
				if isDebugBigCache {
					log.Printf("error reading %s %s", macpath, err)
				}

			}
			defer f.Close()
			scanner := bufio.NewScanner(f)
			for scanner.Scan() {
				Set(key, scanner.Text(), 600)
				sfinl := strings.Split(scanner.Text(), "|")
				if len(sfinl) > 0 {
					return sfinl[0]
				}
				return scanner.Text()
			}
			if err := scanner.Err(); err != nil {
				if isDebugBigCache {
					log.Printf("error scanning %s %s", macpath, err)
				}
			}
		}
		Set(key, "NONE", 600)
		if len(ipstrongswan) > 0 {
			key = fmt.Sprintf("%s:vpnalias", ipaddr)
			//val = Get(key, isDebugBigCache)
			//
			//if val != "" {
			//	if val == "NONE" {
			//		return ""
			//	}
			//	return val
			//}
			if value, err := InMemoryCache.Get(key); err == nil {
				val = string(value)
				if val != "" {
					if val == "NONE" {
						return ""
					}
					return val
				}
			}

			val = StrongSwanAlias(ipaddr, isDebugBigCache)
			if val == "" {
				Set(key, "NONE", 150)
				return ""
			}
			Set(key, val, 150)
		}
		key = fmt.Sprintf("%s:alias", ipaddr)
		cachepath := fmt.Sprintf("/home/artica/UsersMac/Caches/%s", ipaddr)
		//val = Get(key, isDebugBigCache)
		//if val != "" {
		//	if val == "NONE" {
		//		return ""
		//	}
		//	sfinl := strings.Split(val, "|")
		//	if len(sfinl) > 0 {
		//		return sfinl[0]
		//	}
		//	return val
		//}

		if value, err := InMemoryCache.Get(key); err == nil {
			val = string(value)
			if val != "" {
				if val == "NONE" {
					return ""
				}
				sfinl := strings.Split(val, "|")
				if len(sfinl) > 0 {
					return sfinl[0]
				}
				return val
			}
		}

		if _, err := os.Stat(cachepath); !os.IsNotExist(err) {
			f, err := os.Open(cachepath)

			if err != nil {
				if isDebugBigCache {
					log.Printf("error reading %s %s", cachepath, err)
				}

			}
			defer f.Close()
			scanner := bufio.NewScanner(f)
			for scanner.Scan() {
				Set(key, scanner.Text(), 600)
				sfinl := strings.Split(scanner.Text(), "|")
				if len(sfinl) > 0 {
					return sfinl[0]
				}
				return scanner.Text()
			}
			if err := scanner.Err(); err != nil {
				if isDebugBigCache {
					log.Printf("error scanning %s %s", cachepath, err)
				}
			}
		}

	}
	Set(key, "NONE", 600)
	return ""
}

func StrongSwanAlias(ipaddr string, isDebugBigCache bool) string {
	//TODO implement this method
	//data := GetSocketInfoString("strongSwanClientsArray")
	//out, err := gophp.Unserialize([]byte(data))
	//
	//if err != nil {
	//	if isDebugBigCache {
	//		log.Printf("error Unserialize strongSwanClientsArray: ", err)
	//	}
	//	return ""
	//}
	//if mout, ok := out.(map[string]interface{}); ok {
	//	for _, main_index := range mout {
	//		subarray := main_index.(map[string]interface{})
	//		for _, main_index2 := range subarray {
	//			subarray2 := mout[main_index][main_index2]
	//		}
	//	}
	//}
	return ""
}

func ShieldsFullCache(username string, ipaddr string, mac string, sitename string, method string) string {
	sitename = strings.ToLower(sitename)
	username = strings.ToLower(username)
	method = strings.ToLower(mac)
	PrepareCache := []byte(fmt.Sprintf("%s|%s|%s|%s|%s", username, ipaddr, mac, sitename, method))
	PrepareCache = []byte(Md5string(string(PrepareCache)))
	smd5 := fmt.Sprintf("SHIELD.%s", PrepareCache)
	return smd5
}
