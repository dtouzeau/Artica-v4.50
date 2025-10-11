package handlers

var isDebug bool

func init() {
	isDebug = GetSocketInfoBool("Go_Shield_Server_Debug")

}
