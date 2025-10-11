/* ====================================================================
 * Copyright (c) 2000 The Apache Group.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the
 *    distribution.
 *
 * 3. All advertising materials mentioning features or use of this
 *    software must display the following acknowledgment:
 *    "This product includes software developed by the Apache Group
 *    for use in the Apache HTTP server project (http://www.apache.org/)."
 *
 * 4. The names "Apache Server" and "Apache Group" must not be used to
 *    endorse or promote products derived from this software without
 *    prior written permission. For written permission, please contact
 *    apache@apache.org.
 *
 * 5. Products derived from this software may not be called "Apache"
 *    nor may "Apache" appear in their names without prior written
 *    permission of the Apache Group.
 *
 * 6. Redistributions of any form whatsoever must retain the following
 *    acknowledgment:
 *    "This product includes software developed by the Apache Group
 *    for use in the Apache HTTP server project (http://www.apache.org/)."
 *
 * THIS SOFTWARE IS PROVIDED BY THE APACHE GROUP ``AS IS'' AND ANY
 * EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE APACHE GROUP OR
 * ITS CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 * ====================================================================
 *
 * This software consists of voluntary contributions made by many
 * individuals on behalf of the Apache Group and was originally based
 * on public domain software written at the National Center for
 * Supercomputing Applications, University of Illinois, Urbana-Champaign.
 * For more information on the Apache Group and the Apache HTTP server
 * project, please see <http://www.apache.org/>.
 *
 */


/** $Id: mod_extract_forwarded.c,v 1.8 2003/03/18 21:27:31 ahosey Exp $ **/

/** Release version: 1.4 (nevermind the CVS version up there) **/
/** 1.4 adds the code from Richard Barrett <R.Barrett@ftel.co.uk>
 * which resets the conn_rec after each request, and applies a
 * somewhat more rigorous (or lenient, depending on how you look at
 * it) interpretation of the X-Forwarded-For proxy chain, and does the
 * conditional checking of PROXY_ADDR. 1.4 also removes support for
 * Apache 1.2 - isn't it time you upgraded? 
 **/

#include <unistd.h>

#include "httpd.h"
#include "http_config.h"
#include "http_core.h"
#include "http_main.h"
#include "http_log.h"
#include "http_protocol.h"
#include "scoreboard.h"

module extract_forwarded_module;

typedef struct {
    int allow_cache;
    table *allowed_proxies;
    table *denied_proxies;
} fwd_dir_conf;


/* Given an IP as "arg", make sure it is in the allowed_proxies
 * table. It will only ever exist once in a given table. 
 */
static const char *add_forwarder(cmd_parms *cmd, fwd_dir_conf *conf, 
				 const char *arg) 
{
    struct hostent *hptr = NULL;
    char** haddr;

    /* "all" keyword replaces everything with just itself. */
    if (strcasecmp(arg, "all") == 0) {
	ap_clear_table(conf->allowed_proxies);
	ap_table_set(conf->allowed_proxies, arg, "t");
    } else {
	hptr = gethostbyname(arg);
	if (hptr) {
	    for (haddr=hptr->h_addr_list; *haddr; haddr++)
		ap_table_set(conf->allowed_proxies, 
			     inet_ntoa(*((struct in_addr*)(*haddr))), "t");
	}
    }
    return NULL;
}


/* Given an IP as "arg", make sure it is NOT in the allowed_proxies table. 
 */
static const char *rm_forwarder(cmd_parms *cmd, fwd_dir_conf *conf,
				const char *arg)
{
    struct hostent *hptr = NULL;
    char** haddr;

    if (strcasecmp(arg, "all") == 0) {
	ap_clear_table(conf->denied_proxies);
	ap_table_set(conf->denied_proxies, arg, "t");
    } else {
	hptr = gethostbyname(arg);
	if (hptr) {
	    for (haddr=hptr->h_addr_list; *haddr; haddr++)
		ap_table_set(conf->denied_proxies,
			     inet_ntoa(*((struct in_addr*)(*haddr))), "t");
	}
    }
    return NULL;
}


/* Set allow_cache in this fwd_dir_conf to be whatever FLAG we are given. 
 */
static const char *toggle_caching(cmd_parms *cmd, void *mconfig, int flag)
{
    fwd_dir_conf *conf = (fwd_dir_conf*)mconfig;
    conf->allow_cache = flag;
    return NULL;
}

/* Hook up the cmd functions we have lovingly defined above. */
command_rec extract_cmds[] = {
    { "AddAcceptForwarder", add_forwarder, NULL, OR_OPTIONS, 
      ITERATE, "One or more proxy IPs to add to the accept list" },
    { "RemoveAcceptForwarder", rm_forwarder, NULL, OR_OPTIONS,
      ITERATE, "One or more proxy IPs to remove from the accept list" },
    { "AllowForwarderCaching", toggle_caching, NULL, OR_OPTIONS,
      FLAG, "Allow caching of this page if fetched by proxy - On or Off" },
    { NULL }
};


/* This is a callback function used by merge_fwd_dir_conf(). See that
 * function for more details. */
static int take_out_proxies(void *allows, const char *key, const char* val)
{
    /* If key exists in the allows list take it out. */
    ap_table_unset((table*)allows, key);
    return 1;
}


/* The next two functions are the fwd_dir_conf creator and merger. */
static void *create_fwd_dir_conf(pool *p, char *dir)
{
    fwd_dir_conf *conf = (fwd_dir_conf*)ap_pcalloc(p, sizeof(fwd_dir_conf));
    /* This defaults to a 2, meaning "unspecified" */
    conf->allow_cache = 2;
    /* We start with an empty table, which means ignore all
     * Forwarded-For headers. */
    conf->allowed_proxies = ap_make_table(p, 0);
    conf->denied_proxies = ap_make_table(p, 0);
    return (void*)conf;
}


static void *merge_fwd_dir_conf(pool *p, void *base_conf, void* new_conf)
{
    fwd_dir_conf *parent = (fwd_dir_conf*)base_conf;
    fwd_dir_conf *child = (fwd_dir_conf*)new_conf;
    fwd_dir_conf *merged = (fwd_dir_conf*)ap_pcalloc(p, sizeof(fwd_dir_conf));
    int altered_child_denied = 0;
    int altered_child_allowed = 0;

    /* If child->allow_cache was explicitly set in this dir, use
     * that. Else use the parent value. If the parent value is unset,
     * set to "On" */
    if (child->allow_cache != 2)
      merged->allow_cache = child->allow_cache;
    else if (parent->allow_cache == 2)
      merged->allow_cache = 1;
    else
      merged->allow_cache = parent->allow_cache;

    /* The new allowed list starts as the parent list. */
    merged->allowed_proxies = ap_copy_table(p, parent->allowed_proxies);
    merged->denied_proxies = ap_copy_table(p, parent->denied_proxies);

    /* Process "all"s before anything else, and process Removes before
     * Adds. */
    if (ap_table_get(child->denied_proxies, "all")) {
	ap_clear_table(merged->allowed_proxies);
	/* Flag to remind us we need to undo this change. */
	altered_child_denied = 1;
	ap_table_unset(child->denied_proxies, "all");
    }
    if (ap_table_get(child->allowed_proxies, "all")) {
	ap_clear_table(merged->allowed_proxies);
	ap_table_set(merged->allowed_proxies, "all", "t");
	/* Flag to remind us we need to undo this change. */
	altered_child_allowed = 1;
	ap_table_unset(child->allowed_proxies, "all");
    }

    /* If we have an allow "all" then the denied list becomes a
     * "mask" meaning "allow everything except these". Otherwise we just
     * remove the IPs from the allowed list. */
    if (ap_table_get(merged->allowed_proxies, "all")) {
	merged->denied_proxies = 
	    ap_overlay_tables(p, child->denied_proxies, 
			      merged->denied_proxies);
    } else {
	ap_table_do(take_out_proxies, (void*)merged->allowed_proxies, 
		    child->denied_proxies, NULL);
    }
    /* Now handle the allows, which is easy for a change. */
    merged->allowed_proxies = 
	ap_overlay_tables(p, child->allowed_proxies, merged->allowed_proxies);

    /* If we altered the child tables then set them back. */
    if (altered_child_denied)
	ap_table_set(child->denied_proxies, "all", "t");
    if (altered_child_allowed)
	ap_table_set(child->allowed_proxies, "all", "t");

    return (void*)merged;
}


/* Make sure the given proxy IP is allowed with the conf we are given. */
static int proxy_is_kosher(fwd_dir_conf *conf, char *proxy_ip) {

    /* If the allowed list is set to "all", then we "mask out" any
     * proxies that might be in the denied list. */
    if (ap_table_get(conf->allowed_proxies, "all")) {
	if (ap_table_get(conf->denied_proxies, proxy_ip))
	    return 0;
    }
    /* Otherwise we just need to make sure this IP is in the allowed list. */
    else if (!ap_table_get(conf->allowed_proxies, proxy_ip)) {
	/* Oops. It's not. */
	return 0;
    }
    return 1;
}

/* Request cleanup handler and associated data structure.
 * 
 * This restores the remote_ip of the connection over which requests
 * are being made after a request transaction has completed, if the
 * conn_rec was changed for that request. This is needed if our
 * incoming connection is from a proxy server which is making multiple
 * requests, potentially for different clients, down a persistent
 * connection.
 * 
 * If we do not restore the proxy server's IP in the conn_rec then all
 * subsequent requests down the connection will be misattributed to
 * the same IP as the first request.
 */
typedef struct proxy_save_rec proxy_save_rec;

struct proxy_save_rec {
    conn_rec *saved_connection;         /* connection record being used */
    char *saved_remote_ip;              /* original remote_ip */
    char *saved_remote_host;            /* original remote_host */
};

static void restore_proxy_remote_addr(void *data)
{
    proxy_save_rec *proxy_saved = (proxy_save_rec *)data;

    conn_rec *conn = proxy_saved->saved_connection;

    conn->remote_ip = proxy_saved->saved_remote_ip;
    conn->remote_addr.sin_addr.s_addr = inet_addr(conn->remote_ip);
    conn->remote_host = proxy_saved->saved_remote_host;
}


/* If a proxy has provided us with an X-Forwarded-For: header we want
 * to set the remote IP of the request to the one provided. 
 */
static int real_set_proxy_remote_addr(request_rec *r) 
{
    const char *fwded_for;
    char *val, *client_ip;
    proxy_save_rec *proxy_saved;
    fwd_dir_conf *conf;
    array_header *ary;
    int ctr, start_ptr;

    conf = (fwd_dir_conf*)ap_get_module_config(r->per_dir_config, 
					       &extract_forwarded_module);

    if (!(conf->allow_cache)) {
	ap_table_set(r->headers_out, "Pragma", "no-cache");
	ap_table_set(r->headers_out, "Cache-Control", "no-cache");
    }

    /* First make sure the proxy actually making this request is kosher. */
    if (! proxy_is_kosher(conf, r->connection->remote_ip))
	return OK;
    /* Okay now let's look for the header. */
    if ((fwded_for = ap_table_get(r->headers_in, "X-Forwarded-For")) == NULL &&
	(fwded_for = ap_table_get(r->headers_in, "Forwarded-For")) == NULL)
    return OK;

    ary = ap_make_array(r->pool, 1, sizeof(char*));
    ctr = 0;
    while (*fwded_for && 
	   (val = ap_get_token(r->pool, &fwded_for, 0))) {
        *(char**)ap_push_array(ary) = ap_pstrdup(r->pool, val);
	if (*fwded_for == ',' || *fwded_for == ';') {
	    ++fwded_for;
	}
	/* This is a little "anti-suicide" clause if someone tries to
	 * feed us a monster string. */
	if (++ctr > 64) break;
    }

    /* Scan back from the end of the list of forwarded-fors until we
     * find one that isn't kosher, that is, it isn't one of our proxy
     * servers. This allows us to back out any sequence of our proxy
     * servers and find the first IP that isn't, which is the IP we're
     * interested in. What we want is the IP number of the machine
     * that made the connection to first of, potentially a sequence
     * of, our trusted proxies. We don't care about any external
     * proxies that may precede our trusted proxies because we cannot
     * trust what they say.
     *
     * Do not search back beyond the 2nd forwarded-for IP number. Even
     * if the first is from a trusted proxy's IP number it must have
     * been acting as a client not a proxy if it appears in that
     * position.
     */
    for (ctr = ary->nelts - 1; ctr >= 1; ctr--)
	if (! proxy_is_kosher(conf, ((char**)ary->elts)[ctr] ))
	    break;
    client_ip = ((char**)ary->elts)[ctr];

    /* Preserve the proxy's IP etc so we can reset the conn_rec in our
     * cleanup handler. We pass the saved data in the cleanup handler
     * registration. */
    proxy_saved = ap_pcalloc(r->pool, sizeof(proxy_save_rec));
    proxy_saved->saved_connection = r->connection;
    proxy_saved->saved_remote_ip = r->connection->remote_ip;
    proxy_saved->saved_remote_host = r->connection->remote_host;
    ap_register_cleanup(r->pool, (void *)proxy_saved, 
			restore_proxy_remote_addr, ap_null_cleanup);
    /* Put the proxy IP in an env var so that subsequent modules, or
     * CGIs, can know who really sent the request (if they care), as
     * well as on who's behalf. The presence of this var also serves
     * to tell other modules in other phases (including this module!)
     * that the conn_rec has already been tampered with so don't do it
     * again. */
    ap_table_set(r->subprocess_env, "PROXY_ADDR", r->connection->remote_ip);
    /* Here's the spoof. */
    r->connection->remote_ip = client_ip;
    /* To allow .htaccess files to work, we really need to alter this
     * value as well. - David Hayes <dave@jetcafe.org> */
    r->connection->remote_addr.sin_addr.s_addr = inet_addr(client_ip);
    r->connection->remote_host = 
	ap_pstrdup(r->pool, 
		   ap_get_remote_host(r->connection,  r->per_dir_config, 
				      REMOTE_HOST));

    return OK;
}


/* This is what we export as our handler. It checks for the presence
 * of PROXY_ADDR and calls real_set_proxy_remote_addr() to do the
 * work, only if necessary. */
static int set_proxy_remote_addr(request_rec *r) 
{
    if (ap_table_get(r->subprocess_env, "PROXY_ADDR") == NULL)
	real_set_proxy_remote_addr(r);
    return OK;
}


/* Yet another wrapper, this one is hooked to the URI translation
 * phase where the return code needs to be different from above. */
static int ft_set_proxy_remote_addr(request_rec *r)
{
    if (ap_table_get(r->subprocess_env, "PROXY_ADDR") == NULL)
	real_set_proxy_remote_addr(r);
    return DECLINED;
}


/* We used to just hook into post read-request, but post read-request
 * is not invoked for subrequests and this was tripping us up. (For
 * example when the proxy and true server are named virtual hosts in
 * the same Apache instance.) So we hook it up in multiple places and
 * count on PROXY_ADDR to tell us if we need to really run or not.
 */
module MODULE_VAR_EXPORT extract_forwarded_module = {
    STANDARD_MODULE_STUFF,
    NULL,                       /* initializer */
    create_fwd_dir_conf,        /* dir config creater */
    merge_fwd_dir_conf,         /* dir merger --- default is to override */
    NULL,                       /* server config */
    NULL,                       /* merge server configs */
    extract_cmds,               /* command table */
    NULL,                       /* handlers */
    ft_set_proxy_remote_addr,   /* filename translation */
    NULL,                       /* check_user_id */
    NULL,                       /* check auth */
    NULL,                       /* check access */
    NULL,                       /* type_checker */
    NULL,                       /* fixups */
    NULL,                       /* logger */
    set_proxy_remote_addr,      /* header parser */
    NULL,                       /* child_init */
    NULL,                       /* child_exit */
    set_proxy_remote_addr       /* post read-request */
};
