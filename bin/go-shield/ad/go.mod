module ad

go 1.18

replace handlers => ../handlers

require (
	github.com/go-ldap/ldap/v3 v3.4.3
	github.com/techoner/gophp v0.2.0
	handlers v0.0.0-00010101000000-000000000000
)

require (
	github.com/Azure/go-ntlmssp v0.0.0-20211209120228-48547f28849e // indirect
	github.com/go-asn1-ber/asn1-ber v1.5.4 // indirect
	github.com/leekchan/timeutil v0.0.0-20150802142658-28917288c48d // indirect
	github.com/op/go-logging v0.0.0-20160315200505-970db520ece7 // indirect
	golang.org/x/crypto v0.0.0-20220331220935-ae2d96664a29 // indirect
)
