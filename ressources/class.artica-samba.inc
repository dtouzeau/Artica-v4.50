<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026
/**
 * ArticaSamba - PHP client for artica-samba orchestrator.
 *
 * Communicates with the artica-samba daemon over its Unix socket API.
 * Uses libcurl with CURLOPT_UNIX_SOCKET_PATH for local socket access.
 */
class ArticaSamba
{
    private $socketPath;
    private $timeout;
    /**
     * @param string $socketPath Path to Unix socket (default: /run/samba/artica-samba.sock)
     * @param int    $timeout    Request timeout in seconds (default: 30)
     */
    public function __construct(string $socketPath = '/run/samba/artica-samba.sock', int $timeout = 30)
    {
        $this->socketPath = $socketPath;
        $this->timeout = $timeout;
    }

    // ── Instances ──────────────────────────────────────────────

    public function listInstances(): array{
        return $this->get('/v1/instances');
    }

    public function createInstance(array $params): array
    {
        return $this->post('/v1/instances', $params);
    }

    public function getInstance(string $id): array
    {
        return $this->get("/v1/instances/{$id}");
    }

    public function startInstance(string $id): array
    {
        return $this->post("/v1/instances/{$id}/start");
    }

    public function stopInstance(string $id, bool $force = false): array
    {
        $query = $force ? '?force=true' : '';
        return $this->post("/v1/instances/{$id}/stop{$query}");
    }

    public function restartInstance(string $id): array
    {
        return $this->post("/v1/instances/{$id}/restart");
    }

    public function deleteInstance(string $id, bool $force = false): array
    {
        $query = $force ? '?force=true' : '';
        return $this->delete("/v1/instances/{$id}{$query}");
    }

    public function updateInstance(string $id, array $params): array
    {
        return $this->put("/v1/instances/{$id}", $params);
    }

    public function getStatus(string $id): array
    {
        return $this->get("/v1/instances/{$id}/status");
    }

    public function getIndexerConfig(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/config");
    }

    public function updateIndexerConfig(string $instanceId, array $params): array
    {
        return $this->put("/v1/instances/{$instanceId}/indexer/config", $params);
    }

    public function getIndexerStatus(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/status");
    }

    /**
     * indexerDependencies returns the host-tool dependency map:
     * {<tool>:{available,version?,languages?,optional?,note?}}, keyed by
     * pdftotext, pdftoppm, tesseract, file, mount_cifs, umount, nsenter,
     * onnx_runtime, qdrant.
     */
    public function indexerDependencies(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/dependencies");
    }

    /**
     * indexerModels returns the known embedding/model catalog:
     * []{id,type,version,dimension,installed,valid,problem,active}.
     */
    public function indexerModels(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/models");
    }

    /**
     * indexerSemantic returns the semantic engine's status:
     * {semantic_enabled,model?,embedder_configured,embedder_available,
     *  embedder_healthy,ready,vector_backend,vectors_total,vectors_loaded,
     *  docs_pending,truncated?,counts_stale?,reranker_enabled,reranker_model?,
     *  reranker_available,reranker_healthy,reranker_ready}.
     *
     * counts_stale true means vectors_total/docs_pending could NOT be read —
     * a database error, not "zero". A caller must not display either count.
     */
    public function indexerSemantic(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/semantic");
    }

    public function listIndexerSources(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/sources");
    }

    public function createIndexerSource(string $instanceId, array $params): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/sources", $params);
    }

    public function updateIndexerSource(string $instanceId, string $sourceId, array $params): array
    {
        return $this->put("/v1/instances/{$instanceId}/indexer/sources/{$sourceId}", $params);
    }

    public function deleteIndexerSource(string $instanceId, string $sourceId): array
    {
        return $this->delete("/v1/instances/{$instanceId}/indexer/sources/{$sourceId}");
    }

    public function scanIndexerSource(string $instanceId, string $sourceId): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/sources/{$sourceId}/scan");
    }

    // ── Indexer Synonyms ───────────────────────────────────────

    public function listIndexerSynonyms(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/synonyms");
    }

    public function createIndexerSynonym(string $instanceId, array $rule): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/synonyms", $rule);
    }

    public function updateIndexerSynonym(string $instanceId, string $ruleId, array $rule): array
    {
        return $this->put("/v1/instances/{$instanceId}/indexer/synonyms/" . rawurlencode($ruleId), $rule);
    }

    public function deleteIndexerSynonym(string $instanceId, string $ruleId): array
    {
        return $this->delete("/v1/instances/{$instanceId}/indexer/synonyms/" . rawurlencode($ruleId));
    }

    public function importIndexerSynonyms(string $instanceId, array $rules): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/synonyms/import", $rules);
    }

    public function exportIndexerSynonyms(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/synonyms/export");
    }

    // ── Indexer Dictionary ─────────────────────────────────────

    public function listIndexerDictionary(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/dictionary");
    }

    public function createIndexerDictionary(string $instanceId, array $entry): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/dictionary", $entry);
    }

    public function updateIndexerDictionary(string $instanceId, string $entryId, array $entry): array
    {
        return $this->put("/v1/instances/{$instanceId}/indexer/dictionary/" . rawurlencode($entryId), $entry);
    }

    public function deleteIndexerDictionary(string $instanceId, string $entryId): array
    {
        return $this->delete("/v1/instances/{$instanceId}/indexer/dictionary/" . rawurlencode($entryId));
    }

    public function importIndexerDictionary(string $instanceId, array $entries): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/dictionary/import", $entries);
    }

    public function exportIndexerDictionary(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/dictionary/export");
    }

    // ── Mail (IMAP) accounts ───────────────────────────────────
    // The IMAP password is write-only: it is sent (as "password") only on
    // create or an explicit change, and NEVER returned by the daemon (the
    // account carries only "secret_configured": true|false).

    public function listIndexerMailAccounts(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/mail/accounts");
    }

    public function createIndexerMailAccount(string $instanceId, array $account): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/mail/accounts", $account);
    }

    public function updateIndexerMailAccount(string $instanceId, string $accountId, array $account): array
    {
        return $this->put("/v1/instances/{$instanceId}/indexer/mail/accounts/" . rawurlencode($accountId), $account);
    }

    public function deleteIndexerMailAccount(string $instanceId, string $accountId): array
    {
        return $this->delete("/v1/instances/{$instanceId}/indexer/mail/accounts/" . rawurlencode($accountId));
    }

    public function testIndexerMailAccount(string $instanceId, string $accountId): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/mail/accounts/" . rawurlencode($accountId) . "/test");
    }

    public function syncIndexerMailAccount(string $instanceId, string $accountId): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/mail/accounts/" . rawurlencode($accountId) . "/sync");
    }

    // ── Remote SMB connections ─────────────────────────────────
    // The CIFS password is write-only: it is sent (as "password") only on
    // create or an explicit change, and NEVER returned by the daemon (the
    // connection carries only "secret_configured": true|false). Mirrors the
    // mail-account methods above.

    public function listRemoteSMB(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/remote-smb");
    }

    /**
     * Creates or updates a remote-SMB connection. $conn["id"] present and
     * non-empty selects an update (PUT); otherwise a new connection is
     * created (POST). $password is sent only when non-empty, so a blank
     * password on edit leaves the stored secret untouched.
     */
    public function saveRemoteSMB(string $instanceId, array $conn, string $password = ""): array
    {
        $id = (string)($conn["id"] ?? "");
        unset($conn["id"]);
        if ($password !== "") {
            $conn["password"] = $password;
        }
        if ($id !== "") {
            return $this->put("/v1/instances/{$instanceId}/indexer/remote-smb/" . rawurlencode($id), $conn);
        }
        return $this->post("/v1/instances/{$instanceId}/indexer/remote-smb", $conn);
    }

    public function deleteRemoteSMB(string $instanceId, string $connectionId): array
    {
        return $this->delete("/v1/instances/{$instanceId}/indexer/remote-smb/" . rawurlencode($connectionId));
    }

    public function testRemoteSMB(string $instanceId, string $connectionId): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/remote-smb/" . rawurlencode($connectionId) . "/test");
    }

    public function connectRemoteSMB(string $instanceId, string $connectionId): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/remote-smb/" . rawurlencode($connectionId) . "/connect");
    }

    public function disconnectRemoteSMB(string $instanceId, string $connectionId): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/remote-smb/" . rawurlencode($connectionId) . "/disconnect");
    }

    public function scanRemoteSMB(string $instanceId, string $connectionId): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/remote-smb/" . rawurlencode($connectionId) . "/scan");
    }

    public function statusRemoteSMB(string $instanceId, string $connectionId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/remote-smb/" . rawurlencode($connectionId) . "/status");
    }

    // ── Indexer Backup / Restore ────────────────────────────────
    // Whole-instance backup of the ArticaIndexer state (config db, scoped
    // secrets ciphertext, scheduler rows, optionally mail archives) to/from a
    // server-side .tar.gz. ListBackups only returns each manifest's "name"
    // (never a filesystem path) — only the create response carries the path,
    // which the caller (fw.samba.indexer.backup.php) caches for the browser
    // session so Restore has something to send. See that page's header
    // comment / the task report for the full v1-scope rationale.

    public function indexerBackupCreate(string $instanceId, bool $includeMailArchives = false): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/backup", ["include_mail_archives" => $includeMailArchives]);
    }

    public function indexerBackupList(string $instanceId): array
    {
        $r = $this->get("/v1/instances/{$instanceId}/indexer/backups");
        return is_array($r) ? $r : [];
    }

    /**
     * Builds the WebConsole page URL that proxies a backup archive download.
     * The daemon's own .../backups/{name}/download route lives behind the
     * artica-samba Unix socket — unreachable directly from the browser — so
     * this returns a URL on the CURRENTLY EXECUTING fw.*.php page (via
     * CurrentPageName()), which must dispatch its own "download" GET action
     * to downloadIndexerBackup() (mirrors openIndexerDocument's proxy pattern).
     */
    public function indexerBackupDownloadUrl(string $instanceId, string $name): string
    {
        return CurrentPageName() . "?download=yes&instance-id=" . rawurlencode($instanceId) . "&name=" . rawurlencode($name);
    }

    /** Streams a backup archive's bytes from the daemon (status/mime/filename/body). */
    public function downloadIndexerBackup(string $instanceId, string $name): array
    {
        return $this->rawGetWithHeaders("/v1/instances/{$instanceId}/indexer/backups/" . rawurlencode($name) . "/download");
    }

    /** $path is the server-side archive path (from indexerBackupCreate's "path"). */
    public function indexerRestore(string $instanceId, string $path): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/restore", ["path" => $path]);
    }

    // ── Gmail interactive OAuth consent ────────────────────────
    // The daemon mints a CSRF state and returns Google's consent URL; the
    // browser is then sent there. Google redirects back to the console callback
    // page, which relays code+state to completeIndexerMailOAuth for the
    // server-side code exchange. The client secret + refresh token never touch
    // this client — only the OAuth code (single-use, useless alone) and state.

    public function startIndexerMailOAuth(string $instanceId, string $accountId, string $redirectUri): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/mail/accounts/" . rawurlencode($accountId) . "/oauth/start", ["redirect_uri" => $redirectUri]);
    }

    public function completeIndexerMailOAuth(string $code, string $state): array
    {
        // Instance-agnostic: the daemon resolves instance+account from the state.
        return $this->post("/v1/instances/mailoauth/callback", ["code" => $code, "state" => $state]);
    }

    // Revokes the OAuth grant at Google (best-effort) and clears the stored
    // refresh token; the account reverts to not-connected but keeps its client
    // id/secret. Returns {"disconnected":true,"revoked":bool} — revoked=false
    // means Google-side revocation failed (revoke manually there).
    public function disconnectIndexerMailOAuth(string $instanceId, string $accountId): array
    {
        return $this->post("/v1/instances/{$instanceId}/indexer/mail/accounts/" . rawurlencode($accountId) . "/oauth/disconnect");
    }

    /** Durable skip ledger: permanently-skipped messages (too large / no longer present) for one account. */
    public function listIndexerMailAccountSkipped(string $instanceId, string $accountId): array
    {
        return $this->get("/v1/instances/{$instanceId}/indexer/mail/accounts/" . rawurlencode($accountId) . "/skipped");
    }

    /**
     * ACL-filtered search on behalf of $user. $admin grants the daemon-side
     * "see everything regardless of ACL" override (SearchRequest.Admin) —
     * only ever pass true from a caller already gated on
     * AsSambaAdministrator (fw.samba.indexer.search.php dies() otherwise).
     */
    public function searchIndexer(string $instanceId, string $query, string $user, int $page = 1, int $pageSize = 20, array $filters = [], ?bool $dedup = null, ?bool $groupVersions = null, bool $admin = false, string $mode = '', bool $explain = false): array
    {
        $body = [
            'query' => $query,
            'user' => $user,
            'page' => $page,
            'page_size' => $pageSize,
        ];
        if ($admin) {
            $body['admin'] = true;
        }
        // mode: "" / "lexical" => lexical (daemon default); "semantic" => vector
        // search (T-Sem/2); "hybrid" => RRF fusion (T-Sem/3). Only sent for a
        // non-lexical mode so the lexical wire body stays byte-identical.
        if ($mode === 'semantic' || $mode === 'hybrid') {
            $body['mode'] = $mode;
        }
        // explain: per-signal ScoreExplanation on each result (T-Sem/5). Only
        // sent when requested, keeping the non-explain body byte-identical.
        if ($explain) {
            $body['explain'] = true;
        }

        // Wire contract: filters.mime_types (string[]), filters.source_ids
        // (string[]), filters.years (int[]). Only sent when at least one
        // dimension is non-empty; years is always cast to int here so the
        // daemon never receives stray string years from a caller.
        $mimeTypes = array_values(array_filter(array_map('strval', $filters['mime_types'] ?? [])));
        $sourceIds = array_values(array_filter(array_map('strval', $filters['source_ids'] ?? [])));
        $years = array_values(array_map('intval', $filters['years'] ?? []));

        if (!empty($mimeTypes) || !empty($sourceIds) || !empty($years)) {
            $body['filters'] = [
                'mime_types' => $mimeTypes,
                'source_ids' => $sourceIds,
                'years' => $years,
            ];
        }

        // Wire contract: dedup (bool). Omitted entirely when null so the
        // daemon's own default (dedup on) applies; only forced true/false
        // when the caller explicitly passes one.
        if ($dedup !== null) {
            $body['dedup'] = (bool)$dedup;
        }

        // Wire contract: group_versions (bool). Omitted entirely when null so
        // the daemon's own default (grouping on) applies; only forced
        // true/false when the caller explicitly passes one.
        if ($groupVersions !== null) {
            $body['group_versions'] = (bool)$groupVersions;
        }

        $data = $this->post("/v1/instances/{$instanceId}/indexer/search", $body);
        $data += ['total' => 0, 'results' => []];
        return $data;
    }

    /** Filename suggestions (ACL-filtered). */
    public function suggestIndexer(string $instanceId, string $prefix, string $user, int $limit = 10): array
    {
        $r = $this->get("/v1/instances/{$instanceId}/indexer/suggest?q=" . rawurlencode($prefix)
            . "&user=" . rawurlencode($user) . "&limit=" . intval($limit));
        return is_array($r["suggestions"] ?? null) ? $r["suggestions"] : [];
    }

    /** Escaped text preview (never active HTML). Returns the plain response body. */
    public function previewIndexerDocument(string $instanceId, string $docId, string $user): string
    {
        return $this->rawGet("/v1/instances/{$instanceId}/indexer/document/" . rawurlencode($docId)
            . "/preview?user=" . rawurlencode($user));
    }

    /** Streams a document's bytes from the daemon. Returns status + mime + filename + body. */
    public function openIndexerDocument(string $instanceId, string $docId, string $user): array
    {
        return $this->rawGetWithHeaders("/v1/instances/{$instanceId}/indexer/document/" . rawurlencode($docId)
            . "/open?user=" . rawurlencode($user));
    }

    // ── Resources ─────────────────────────────────────────────

    public function updateResources(string $id, array $limits): array
    {
        return $this->put("/v1/instances/{$id}/resources", $limits);
    }

    public function getResources(string $id): array
    {
        return $this->get("/v1/instances/{$id}/resources");
    }

    // ── Shares ────────────────────────────────────────────────

    public function listShares(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/shares");
    }

    // Demande au démon de remesurer la taille sur disque des partages de
    // l'instance. Asynchrone : 202 {"queued": n} tout de suite ; les tailles
    // arrivent ensuite dans listShares() (champ disk_usage) au fil du parcours.
    public function shareUsageScan(string $instanceId): array
    {
        return $this->post("/v1/instances/{$instanceId}/shares/usage/scan");
    }

    public function createShare(string $instanceId, array $params): array
    {
        return $this->post("/v1/instances/{$instanceId}/shares", $params);
    }

    public function updateShare(string $instanceId, string $name, array $params): array
    {
        return $this->put("/v1/instances/{$instanceId}/shares/{$name}", $params);
    }

    public function deleteShare(string $instanceId, string $name): array
    {
        return $this->delete("/v1/instances/{$instanceId}/shares/{$name}");
    }

    // ── Users ─────────────────────────────────────────────────

    public function listUsers(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/users");
    }

    public function createUser(string $instanceId, string $name, string $password): array
    {
        return $this->post("/v1/instances/{$instanceId}/users", [
            'name' => $name,
            'password' => $password,
        ]);
    }

    public function updateUser(string $instanceId, string $name, array $params): array
    {
        return $this->put("/v1/instances/{$instanceId}/users/{$name}", $params);
    }

    public function deleteUser(string $instanceId, string $name): array
    {
        return $this->delete("/v1/instances/{$instanceId}/users/{$name}");
    }

    // ── Groups (net groupmap) ─────────────────────────────────

    public function listGroups(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/groups");
    }

    public function createGroup(string $instanceId, string $name, string $unixGroup = ""): array
    {
        return $this->post("/v1/instances/{$instanceId}/groups", ["name" => $name, "unix_group" => $unixGroup]);
    }

    public function deleteGroup(string $instanceId, string $name): array
    {
        return $this->delete("/v1/instances/{$instanceId}/groups/" . rawurlencode($name));
    }

    /**
     * Host Unix groups (sorted by name), used to populate group pickers.
     * `members` (explicit members + primary-gid users) exists since daemon 2026-09-11; absent before.
     * @return array<int,array{name:string,gid:int,members?:array<int,string>}>
     */
    public function listUnixGroups(): array
    {
        $r = $this->get("/v1/system/unix-groups");
        return is_array($r["groups"] ?? null) ? $r["groups"] : [];
    }

    /**
     * Host Unix accounts with their primary group resolved to a name.
     * `system` marks uid < 1000 (daemon accounts) so a picker can skip them.
     * Daemon route added 2026-09-11; older daemons answer "Not Found".
     * @return array<int,array{name:string,uid:int,gid:int,group?:string,system?:bool}>
     */
    public function listUnixUsers(): array
    {
        $r = $this->get("/v1/system/unix-users");
        return is_array($r["users"] ?? null) ? $r["users"] : [];
    }

    /**
     * Identities to suggest for an administrator's "search as user": the members
     * (explicit and by primary group) of the Unix groups that authorize the
     * instance's shares, sorted and deduplicated. Suggestions only — the field
     * must stay free text, since an AD/winbind account is a valid identity that
     * belongs to no local group. The WebConsole session id ("-100" for the
     * Manager) is no Unix account, and searching as it finds nothing readable.
     * Throws RuntimeException like the two calls it makes.
     * @return string[]
     */
    public function searchIdentities(string $instanceId): array
    {
        $groups = [];
        foreach ($this->listShares($instanceId) as $s) {
            $authz = is_array($s["authz"] ?? null) ? $s["authz"] : [];
            foreach (["read_groups", "write_groups"] as $k) {
                foreach ((array)($authz[$k] ?? []) as $g) { $groups[(string)$g] = true; }
            }
        }
        $users = [];
        foreach ($this->listUnixGroups() as $g) {
            if (!isset($groups[(string)($g["name"] ?? "")])) { continue; }
            foreach (["members", "primary_members"] as $k) {
                foreach ((array)($g[$k] ?? []) as $u) {
                    if ((string)$u !== "") { $users[] = (string)$u; }
                }
            }
        }
        $users = array_values(array_unique($users));
        sort($users, SORT_STRING);
        return $users;
    }

    /**
     * Creates a host Unix group. An existing group is not an error —
     * `created` says whether it had to be created.
     * Daemon route added 2026-09-12; older daemons answer "Not Found".
     * @return array{name:string,gid:int,created:bool}
     */
    public function createUnixGroup(string $name): array
    {
        return $this->post("/v1/system/unix-groups", ["name" => $name]);
    }

    /**
     * Adds an existing Unix user to an existing Unix group. Shares authorize
     * groups, never users, so this IS the "give access to that folder" gesture.
     * ⚠️ Host-global: the user gains the same access on EVERY share naming that group.
     * Daemon route added 2026-09-11; older daemons answer "Not Found".
     */
    public function addUnixGroupMember(string $group, string $user): array
    {
        return $this->post("/v1/system/unix-groups/" . rawurlencode($group) . "/members", ["user" => $user]);
    }

    /**
     * Removes an explicit member from a Unix group. Refused (400) when the user
     * belongs through their primary group — gpasswd -d cannot remove that.
     */
    public function removeUnixGroupMember(string $group, string $user): array
    {
        return $this->delete("/v1/system/unix-groups/" . rawurlencode($group) . "/members/" . rawurlencode($user));
    }

    // ── Metrics ───────────────────────────────────────────────

    public function getSessions(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/metrics/sessions");
    }

    public function getIO(string $instanceId, string $period = '1h', string $user = ''): array
    {
        $query = "?period={$period}";
        if ($user !== '') {
            $query .= "&user=" . urlencode($user);
        }
        return $this->get("/v1/instances/{$instanceId}/metrics/io{$query}");
    }

    public function getTop(string $instanceId, int $limit = 10, string $sort = 'total_bytes'): array
    {
        return $this->get("/v1/instances/{$instanceId}/metrics/top?limit={$limit}&sort={$sort}");
    }

    public function getPrometheus(string $instanceId): string
    {
        return $this->rawGet("/v1/instances/{$instanceId}/metrics/prometheus");
    }

    /**
     * Usage of one instance over period ∈ {1h,24h,7d,30d} (default 24h):
     * points = every slice (minute/hour/day, appliance local time, RFC3339 bucket),
     * polls=0 → not recorded, avg_sessions=0 with polls>0 → nobody connected;
     * summary (peak_sessions, peak_at, active_slots, avg_when_active…);
     * users / shares with seconds seen. Traffic (read_bytes, write_bytes,
     * op_count = SMB requests) is measured for the whole instance by Samba
     * profiling; io_polls = 0 means not measured (never per user or share).
     */
    public function getTimeSeries(string $instanceId, string $period = '24h'): array
    {
        return $this->get("/v1/instances/{$instanceId}/metrics/timeseries?period=" . urlencode($period));
    }

    /**
     * Cross-instance aggregate: totals, per-instance rollups, global top users.
     * period ∈ {1h,24h,7d,30d} (daemon default 24h).
     */
    public function globalMetrics(string $period = '24h'): array
    {
        return $this->get("/v1/metrics/global?period=" . urlencode($period));
    }

    // ── Activity (live smbstatus + disconnect controls) ───────

    public function getActivity(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/activity");
    }

    public function killClient(string $instanceId, string $clientIP): array
    {
        return $this->post("/v1/instances/{$instanceId}/activity/kill-client", ["client_ip" => $clientIP]);
    }

    public function closeShare(string $instanceId, string $share): array
    {
        return $this->post("/v1/instances/{$instanceId}/activity/close-share", ["share" => $share]);
    }

    // ── Core Samba daemons (host smbd/nmbd/winbindd or samba) ──

    public function coreStatus(): array
    {
        return $this->get("/v1/samba/core");
    }

    public function coreAction(string $action): array
    {
        return $this->post("/v1/samba/core/{$action}");
    }

    public function coreDaemon(string $name, string $action): array
    {
        return $this->post("/v1/samba/core/" . rawurlencode($name) . "/{$action}");
    }

    // ── Health ────────────────────────────────────────────────

    public function health(): array
    {
        return $this->get('/health');
    }

    // ── Log Helpers ──────────────────────────────────────────
    // Served by the daemon (it reads the 0750 root-owned instance tree that the
    // www-data WebConsole cannot); a local scandir would just return nothing.

    public function listLogFiles(string $instanceId): array
    {
        $r = $this->get("/v1/instances/{$instanceId}/logs");
        return is_array($r["files"] ?? null) ? $r["files"] : [];
    }

    public function readLogFile(string $instanceId, string $filename, int $lines = 500): string
    {
        $r = $this->get("/v1/instances/{$instanceId}/logs/" . rawurlencode(basename($filename)) . "?lines=" . intval($lines));
        return is_string($r["content"] ?? null) ? $r["content"] : "";
    }

    // ── Plakar backup module (host-global, /v1/plakar/*) ──────

    public function plakarStatus(): array { return $this->get('/v1/plakar/status'); }
    public function plakarVersion(): array { return $this->get('/v1/plakar/version'); }
    public function plakarHealth(): array { return $this->get('/v1/plakar/health'); }
    public function plakarCapabilities(): array { return $this->get('/v1/plakar/capabilities'); }
    public function plakarService(string $action): array { return $this->post("/v1/plakar/service/{$action}"); }
    public function plakarServiceLogs(int $lines = 200): array { return $this->get("/v1/plakar/service/logs?lines=" . intval($lines)); }

    public function plakarGetSettings(): array { return $this->get('/v1/plakar/settings'); }
    public function plakarSaveSettings(array $params): array { return $this->post('/v1/plakar/settings', $params); }

    // Repositories
    public function plakarRepositories(): array { return $this->get('/v1/plakar/repositories'); }
    public function plakarRepository(int $id): array { return $this->get("/v1/plakar/repositories/{$id}"); }
    public function plakarRepositoryCreate(array $params): array { return $this->post('/v1/plakar/repositories', $params); }
    public function plakarRepositoryUpdate(int $id, array $params): array { return $this->put("/v1/plakar/repositories/{$id}", $params); }
    public function plakarRepositoryDelete(int $id): array { return $this->delete("/v1/plakar/repositories/{$id}"); }
    public function plakarRepositoryCheck(int $id): array { return $this->post("/v1/plakar/repositories/{$id}/check"); }
    public function plakarRepositoryTestCredentials(int $id): array { return $this->post("/v1/plakar/repositories/{$id}/test-credentials"); }
    // Divulgation de la phrase secrète scellée. Le daemon EXIGE confirm dans le
    // corps (plakar.ErrRevealNotConfirmed) : sans lui il répond 400, ce qui est
    // exactement le but — une requête mal aiguillée ne doit pas sortir un secret.
    // 1 et non true : ArticaSamba::request() sérialise en JSON et le daemon
    // décode ce champ en flexBool, qui accepte les deux.
    public function plakarRepositoryRevealPassphrase(int $id): array { return $this->post("/v1/plakar/repositories/{$id}/passphrase/reveal", array("confirm" => 1)); }
    // Mesure a la demande de la consommation disque. Le balayage periodique du
    // demon (4 h) reste l'autorite ; cette route sert quand l'operateur ne veut
    // pas l'attendre. Reponse : {"measurable":bool,"disk_usage":{...}|null}.
    public function plakarRepositoryUsageScan(int $id): array { return $this->post("/v1/plakar/repositories/{$id}/usage/scan"); }
    public function plakarRepositorySetDefault(int $id): array { return $this->post("/v1/plakar/repositories/{$id}/set-default"); }
    public function plakarRepositoryUsage(int $id): array { return $this->get("/v1/plakar/repositories/{$id}/usage"); }
    public function plakarRetentionPreview(int $id, array $policy): array { return $this->post("/v1/plakar/repositories/{$id}/retention/preview", ["policy" => $policy]); }
    public function plakarRetentionApply(int $id, array $policy, bool $confirm): array { return $this->post("/v1/plakar/repositories/{$id}/retention/apply", ["policy" => $policy, "confirm" => $confirm]); }

    // Pools (network server mode)
    public function plakarPools(): array { return $this->get('/v1/plakar/pools'); }
    public function plakarPoolGet(string $uuid): array { return $this->get("/v1/plakar/pools/" . rawurlencode($uuid)); }
    public function plakarPoolSave(string $uuid, array $params): array {
        if ($uuid === "") { return $this->post('/v1/plakar/pools', $params); }
        return $this->put("/v1/plakar/pools/" . rawurlencode($uuid), $params);
    }
    public function plakarPoolDelete(string $uuid): array { return $this->delete("/v1/plakar/pools/" . rawurlencode($uuid)); }

    // Sources
    public function plakarSources(): array { return $this->get('/v1/plakar/sources'); }
    public function plakarSource(int $id): array { return $this->get("/v1/plakar/sources/{$id}"); }
    public function plakarSourceCreate(array $params): array { return $this->post('/v1/plakar/sources', $params); }
    public function plakarSourceUpdate(int $id, array $params): array { return $this->put("/v1/plakar/sources/{$id}", $params); }
    public function plakarSourceDelete(int $id): array { return $this->delete("/v1/plakar/sources/{$id}"); }
    public function plakarSourceTest(int $id): array { return $this->post("/v1/plakar/sources/{$id}/test"); }
    public function plakarSourceTestCredentials(int $id): array { return $this->post("/v1/plakar/sources/{$id}/test-credentials"); }

    // Destinations
    public function plakarDestinations(): array { return $this->get('/v1/plakar/destinations'); }
    public function plakarDestination(int $id): array { return $this->get("/v1/plakar/destinations/{$id}"); }
    public function plakarDestinationCreate(array $params): array { return $this->post('/v1/plakar/destinations', $params); }
    public function plakarDestinationUpdate(int $id, array $params): array { return $this->put("/v1/plakar/destinations/{$id}", $params); }
    public function plakarDestinationDelete(int $id): array { return $this->delete("/v1/plakar/destinations/{$id}"); }
    public function plakarDestinationTest(int $id): array { return $this->post("/v1/plakar/destinations/{$id}/test"); }
    public function plakarDestinationTestCredentials(int $id): array { return $this->post("/v1/plakar/destinations/{$id}/test-credentials"); }

    // Jobs
    public function plakarJobs(): array { return $this->get('/v1/plakar/jobs'); }
    public function plakarJob(int $id): array { return $this->get("/v1/plakar/jobs/{$id}"); }
    public function plakarJobCreate(array $params): array { return $this->post('/v1/plakar/jobs', $params); }
    public function plakarJobUpdate(int $id, array $params): array { return $this->put("/v1/plakar/jobs/{$id}", $params); }
    public function plakarJobDelete(int $id): array { return $this->delete("/v1/plakar/jobs/{$id}"); }
    public function plakarJobRun(int $id): array { return $this->post("/v1/plakar/jobs/{$id}/run"); }
    public function plakarJobStop(int $id): array { return $this->post("/v1/plakar/jobs/{$id}/stop"); }
    public function plakarJobRuns(int $id): array { return $this->get("/v1/plakar/jobs/{$id}/runs"); }
    public function plakarJobLastRun(int $id): array { return $this->get("/v1/plakar/jobs/{$id}/last-run"); }

    // Snapshots
    public function plakarSnapshots(int $repositoryId = 0, array $filters = []): array {
        $q = [];
        if ($repositoryId > 0) { $q[] = "repository_id={$repositoryId}"; }
        foreach ($filters as $k => $v) { if ($v !== "" && $v !== null) { $q[] = urlencode($k) . "=" . urlencode((string)$v); } }
        $qs = count($q) ? "?" . implode("&", $q) : "";
        return $this->get("/v1/plakar/snapshots{$qs}");
    }
    public function plakarSnapshotSyncCache(int $repositoryId = 0): array { return $this->post('/v1/plakar/snapshots/sync-cache', ["repository_id" => $repositoryId]); }
    public function plakarSnapshot(string $snapshotId, int $repositoryId): array { return $this->get("/v1/plakar/snapshots/" . rawurlencode($snapshotId) . "?repository_id={$repositoryId}"); }
    public function plakarSnapshotBrowse(string $snapshotId, int $repositoryId, string $path = ""): array {
        return $this->get("/v1/plakar/snapshots/" . rawurlencode($snapshotId) . "/browse?repository_id={$repositoryId}&path=" . urlencode($path));
    }
    public function plakarSnapshotVerify(string $snapshotId, int $repositoryId): array { return $this->post("/v1/plakar/snapshots/" . rawurlencode($snapshotId) . "/verify", ["repository_id" => $repositoryId]); }
    public function plakarSnapshotDelete(string $snapshotId, int $repositoryId): array { return $this->post("/v1/plakar/snapshots/" . rawurlencode($snapshotId) . "/delete", ["repository_id" => $repositoryId, "confirm" => true]); }

    // Restore
    public function plakarRestore(array $params): array { return $this->post('/v1/plakar/restore', $params); }
    public function plakarRestoreJobs(): array { return $this->get('/v1/plakar/restore/jobs'); }
    public function plakarRestoreJob(int $id): array { return $this->get("/v1/plakar/restore/jobs/{$id}"); }
    public function plakarRestoreStop(int $id): array { return $this->post("/v1/plakar/restore/jobs/{$id}/stop"); }

    // Metrics
    public function plakarMetricsOverview(): array { return $this->get('/v1/plakar/metrics/overview'); }
    public function plakarMetricsJobs(): array { return $this->get('/v1/plakar/metrics/jobs'); }
    public function plakarMetricsRepositories(): array { return $this->get('/v1/plakar/metrics/repositories'); }
    public function plakarMetricsSnapshots(): array { return $this->get('/v1/plakar/metrics/snapshots'); }
    public function plakarMetricsErrors(): array { return $this->get('/v1/plakar/metrics/errors'); }

    // ── File sync module (T-Sync, /v1/syncs/* + /v1/instances/{id}/syncs/*) ──
    //
    // rclone-backed multi-protocol synchronisation (smb/ftp/sftp/webdav/drive).
    // Three daemon-side rules govern EVERY method below, and none of them is
    // negotiable from this side:
    //
    //  1. The daemon decodes with DisallowUnknownFields — an unrecognised key
    //     in a body is a 400, not a field politely ignored. So nothing here
    //     ever adds a field "just in case", and the two save methods STRIP the
    //     server-owned fields a page would otherwise echo back from a GET.
    //  2. Booleans must be REAL JSON booleans. The console's habit of passing
    //     form values through intval() would send 0/1, which the strict routes
    //     (PUT /v1/syncs/settings above all) refuse outright with
    //     "cannot unmarshal number into ... of type bool". Every boolean below
    //     is cast with (bool) before json_encode sees it.
    //  3. No secret ever travels in a URL: the SSH/FTP/SMB password and the
    //     Google Drive client_secret go in the BODY of a POST/PUT only, and
    //     the daemon never returns either one (the connection carries only
    //     secret_configured / oauth_connected).
    //
    // The query strings are just as strict: these routes refuse an unknown
    // key, a repeated key and a present-but-empty value, so every builder here
    // omits an empty filter entirely rather than sending "key=".

    /** Always-200 diagnostic: rclone installed/version/path, rule counts, next_run, unscheduled[]. */
    public function syncsStatus(): array
    {
        return $this->get('/v1/syncs/status');
    }

    /** {supported:[],available:[],bisync:bool} — the vocabulary vs. what the installed rclone has. */
    public function syncsProtocols(): array
    {
        return $this->get('/v1/syncs/protocols');
    }

    public function getSyncSettings(): array
    {
        return $this->get('/v1/syncs/settings');
    }

    /**
     * PUT /v1/syncs/settings — MERGES onto the settings in force: an omitted
     * key keeps its current value (the daemon seeds the decode from them), so
     * a partial array is a legitimate update, not a reset.
     *
     * Only the eight known keys are forwarded, each coerced to the type the
     * daemon's struct declares. This route has NO tolerant boolean decoder
     * (unlike the rule/connection routes), so "enabled" => 1 would be a 400;
     * the (bool) casts below are what makes a checkbox array usable as-is.
     * An unknown key is refused HERE, loudly, rather than dropped in silence:
     * a silently dropped setting reads as "saved" to whoever sent it.
     *
     * ⚠️ THROWS \InvalidArgumentException on any key outside those eight —
     * where every other method of this class only ever throws from the
     * transport. Uncaught, that is a blank page, so BUILD THE ARRAY FIELD BY
     * FIELD: handing it $_POST whole is guaranteed to throw on the CSRF
     * token, on "submit", on any hidden field the form carries.
     */
    public function saveSyncSettings(array $settings): array
    {
        $bools = ["enabled", "syslog_enabled"];
        $ints = ["max_parallel_syncs", "global_bwlimit_kb", "retention_days"];
        $strings = ["binary_path", "log_level", "syslog_facility"];

        $body = [];
        foreach ($settings as $key => $value) {
            if (in_array($key, $bools, true)) {
                $body[$key] = (bool)$value;
            } elseif (in_array($key, $ints, true)) {
                $body[$key] = intval($value);
            } elseif (in_array($key, $strings, true)) {
                $body[$key] = (string)$value;
            } else {
                throw new \InvalidArgumentException("unknown sync setting: {$key}");
            }
        }
        return $this->put('/v1/syncs/settings', $body);
    }

    /**
     * Host-global run history, deliberately cross-instance. Filters:
     * instance_id, rule_id, status, since (RFC 3339), limit, offset.
     * "since" filters on the run's creation, so the window includes runs that
     * were created inside it and never started (a run refused by a precheck).
     * Throws \InvalidArgumentException on any other key — see syncQuery().
     */
    public function listSyncRuns(array $filters = []): array
    {
        $allowed = ["instance_id", "rule_id", "status", "since", "limit", "offset"];
        return $this->get('/v1/syncs/runs' . $this->syncQuery($filters, $allowed));
    }

    // ── Sync connections (remotes) ─────────────────────────────

    public function listSyncRemotes(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/remotes");
    }

    public function getSyncRemote(string $instanceId, int $remoteId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/remotes/" . intval($remoteId));
    }

    /**
     * Creates or updates a connection. $remote["id"] present and non-zero
     * selects an update (PUT); otherwise a new one is created (POST).
     *
     * The secret is write-only and travels in the body: $password for
     * smb/ftp/sftp/webdav, $clientSecret for a Google Drive OAuth client.
     * They are the SAME slot under two spellings, so sending both is refused
     * here exactly as the daemon refuses it — arbitrating between them would
     * discard one secret while the caller believed it was stored.
     *
     * THREE INTENTIONS, TWO METHODS. The daemon takes the secret as a POINTER
     * precisely to keep them apart, and so does this client:
     *
     *   leave alone → saveSyncRemote(…)              (secret key ABSENT)
     *   replace     → saveSyncRemote(…, "s3cret")    (secret key = the value)
     *   ERASE       → clearSyncRemoteSecret(…)       (secret key = "")
     *
     * The empty string is NOT folded into this method's parameters: here ""
     * means "leave alone", the safe reading for a blank field an operator
     * simply did not retype. Erasing is a deliberate act and has its own
     * method, below.
     *
     * ⚠️ On a drive connection, changing drive_client_id or drive_scope DROPS
     * the sealed refresh token (the scope is frozen at consent): oauth_connected
     * falls back to false and the consent must be replayed. And
     * secret_configured true does NOT mean usable — only oauth_connected does.
     */
    public function saveSyncRemote(string $instanceId, array $remote, string $password = "", string $clientSecret = ""): array
    {
        if ($password !== "" && $clientSecret !== "") {
            throw new \InvalidArgumentException('send either $password or $clientSecret, not both — they are the same field');
        }

        $id = intval($remote["id"] ?? 0);
        $body = $this->stripSyncReadOnly($remote, [
            "id", "instance_id", "secret_configured", "oauth_connected",
            "last_test_at", "last_test_status", "last_test_error",
            "created_at", "updated_at",
        ]);
        if (array_key_exists("enabled", $body)) {
            $body["enabled"] = (bool)$body["enabled"];
        }
        if ($password !== "") {
            $body["password"] = $password;
        }
        if ($clientSecret !== "") {
            $body["client_secret"] = $clientSecret;
        }

        if ($id > 0) {
            return $this->put("/v1/instances/{$instanceId}/syncs/remotes/{$id}", $body);
        }
        return $this->post("/v1/instances/{$instanceId}/syncs/remotes", $body);
    }

    /**
     * ERASES the stored secret of a connection, keeping the connection itself.
     * The third intention saveSyncRemote deliberately cannot express: the
     * daemon reads the secret as a pointer, so an ABSENT key means "leave it"
     * and an EMPTY STRING means "wipe it" — and only this method ever sends
     * the empty string.
     *
     * Its named use is a legacy `drive` connection whose sealed credential can
     * no longer be decrypted: the daemon's own refusal tells the administrator
     * to send an empty "client_secret", and without this method that message
     * would point at a door the console cannot open. Erasing lets a fresh
     * client_secret be laid down (saveSyncRemote) and the consent replayed.
     *
     * The spelling follows the protocol — "client_secret" for drive,
     * "password" everywhere else — since the two are one slot and sending
     * both is a 400.
     *
     * $remote is the connection as it must remain: PUT replaces the whole
     * body, so the current fields have to travel with the erasure. Left empty
     * it is re-read here (getSyncRemote), which is a read-modify-write — pass
     * the connection you already hold when you have one.
     *
     * NOT to be confused with disconnectSyncRemoteOAuth(), which revokes the
     * Google grant and clears the refresh TOKEN while keeping the
     * client_secret in place. This one does the opposite half.
     */
    public function clearSyncRemoteSecret(string $instanceId, int $remoteId, array $remote = []): array
    {
        if (empty($remote)) {
            $remote = $this->getSyncRemote($instanceId, $remoteId);
        }
        $protocol = (string)($remote["protocol"] ?? "");
        $field = $protocol === "drive" ? "client_secret" : "password";

        $body = $this->stripSyncReadOnly($remote, [
            "id", "instance_id", "secret_configured", "oauth_connected",
            "last_test_at", "last_test_status", "last_test_error",
            "created_at", "updated_at",
        ]);
        // Les deux orthographes sont le MÊME emplacement : en envoyer deux est
        // un 400. On retire donc l'autre avant de poser la chaîne vide, au cas
        // où l'appelant aurait recopié un tableau qui la portait.
        unset($body["password"], $body["client_secret"]);
        if (array_key_exists("enabled", $body)) {
            $body["enabled"] = (bool)$body["enabled"];
        }
        $body[$field] = "";

        return $this->put("/v1/instances/{$instanceId}/syncs/remotes/" . intval($remoteId), $body);
    }

    public function deleteSyncRemote(string $instanceId, int $remoteId): array
    {
        return $this->delete("/v1/instances/{$instanceId}/syncs/remotes/" . intval($remoteId));
    }

    /**
     * Probes the connection. Always 200 when the probe could RUN: a negative
     * probe is DATA, never an HTTP error — so the absence of an exception says
     * nothing, the payload has to be read.
     *
     * ⚠️ THE VERDICT IS "ok", AND ITS POLARITY IS THE OPPOSITE OF browse's.
     * This route answers filesync.TestResult — {"ok":bool,"latency":…,
     * "error":…} — and carries NO "failed" key whatsoever. Its neighbour
     * browseSyncRemote answers filesync.BrowseResult, whose verdict is
     * "failed" (omitempty, so ABSENT when the listing worked). Two adjacent
     * routes, two field names, two polarities:
     *
     *   test   → success is  !empty($r["ok"])          — read "ok"
     *   browse → success is  empty($r["failed"])       — read "failed"
     *
     * Crossing them is silent in BOTH directions and neither is caught by a
     * test that only ever tries a working connection: `empty($r["failed"])`
     * on THIS route reads an always-absent key, so every dead connection is
     * announced as reachable — a "test" button that certifies instead of
     * testing; `!empty($r["ok"])` on browse reads an always-absent key, so
     * every listing looks failed. Read the verdict named for the route.
     *
     * Note also that this route's "ok" is the connection's verdict, INSIDE
     * the daemon's data payload — not the transport envelope's own "ok",
     * which request() has already consumed by the time this returns.
     */
    public function testSyncRemote(string $instanceId, int $remoteId): array
    {
        return $this->post("/v1/instances/{$instanceId}/syncs/remotes/" . intval($remoteId) . "/test");
    }

    /**
     * Lists ONE level of the remote tree. An empty $path means the
     * connection's own base path — never an error. Always 200 when the
     * listing could RUN, like testSyncRemote, but the verdict is "failed"
     * here, not "ok" — see the asymmetry spelled out above testSyncRemote.
     * "failed" is omitempty: a successful listing has no such key, and an
     * empty directory (entries == []) with no "failed" is a real empty
     * directory, not a failure. A ".." segment is refused (400) by the daemon.
     */
    public function browseSyncRemote(string $instanceId, int $remoteId, string $path = ""): array
    {
        $query = $path === "" ? "" : "?path=" . rawurlencode($path);
        return $this->get("/v1/instances/{$instanceId}/syncs/remotes/" . intval($remoteId) . "/browse" . $query);
    }

    // ── Google Drive OAuth consent (drive connections only) ────
    // The daemon mints a single-use, 10-minute anti-CSRF state bound to this
    // connection and returns Google's consent URL; the browser goes there and
    // Google redirects back to the console callback page, which relays
    // code+state to completeSyncRemoteOAuth for the server-side exchange.
    // No response of the three ever carries a secret.

    /** $redirectUri must be an absolute https URL (the console callback page). */
    public function syncRemoteOAuthUrl(string $instanceId, int $remoteId, string $redirectUri): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/remotes/" . intval($remoteId)
            . "/oauth/url?redirect_uri=" . rawurlencode($redirectUri));
    }

    /**
     * Relays Google's return. code and state go in the BODY: an authorization
     * code in a query string ends up in every access log and browser history
     * between Google and this console.
     */
    public function completeSyncRemoteOAuth(string $instanceId, int $remoteId, string $code, string $state): array
    {
        return $this->post("/v1/instances/{$instanceId}/syncs/remotes/" . intval($remoteId) . "/oauth/callback",
            ["code" => $code, "state" => $state]);
    }

    /**
     * Relays Google's return WITHOUT knowing which connection it belongs to.
     *
     * The consent redirect carries nothing but code and state — Google matches
     * the registered redirect URI exactly, so the console callback page cannot
     * carry an instance or a connection id in it. This host-global route
     * resolves both FROM THE STATE and answers with what it resolved:
     * {"connected":true,"instance_id":"…","remote_id":7}. Same body as
     * completeSyncRemoteOAuth (code and state, nothing else — unknown fields
     * are a 400), same single-use state, same consent logic underneath.
     *
     * 400 covers an unknown, expired OR replayed state under ONE message that
     * deliberately does not say which. The state store does not persist, so a
     * daemon restart during the consent lands here too: the screen must say
     * "start the consent again", never "failure".
     */
    public function completeSyncOAuth(string $code, string $state): array
    {
        return $this->post("/v1/syncs/oauth/callback", ["code" => $code, "state" => $state]);
    }

    /**
     * Revokes at Google FIRST, erases locally second. Returns
     * {"disconnected":true,"revoked":bool}; revoked=false is not an error — it
     * means that one token has to be withdrawn by hand at Google.
     */
    public function disconnectSyncRemoteOAuth(string $instanceId, int $remoteId): array
    {
        return $this->post("/v1/instances/{$instanceId}/syncs/remotes/" . intval($remoteId) . "/oauth/disconnect");
    }

    // ── Google Photos import (T-Sync/8) ────────────────────────
    //
    // A Photos account is NOT a remote connection and no sync rule can name
    // one: since 31 March 2025 the Photos Library API only returns media the
    // calling application itself created, so no recurring retrieval of an
    // ordinary library is possible — by rclone or anything else. What is left
    // is the Picker API, where a human designates the media in a browser. Hence
    // a separate entity, a separate table on the daemon side, and no foreign
    // key between the two worlds.
    //
    // There is no scope parameter anywhere below: the Picker API has exactly
    // one (photospicker.mediaitems.readonly), it is read-only, and the daemon
    // holds it as a constant. Nothing here can widen it.

    public function listPhotoAccounts(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/photos");
    }

    public function getPhotoAccount(string $instanceId, int $accountId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/photos/" . intval($accountId));
    }

    /**
     * Creates or replaces a Photos account.
     *
     * $clientSecret follows the three-intent convention of savePhotoAccount's
     * siblings: null leaves the stored secret alone, "" clears it, anything
     * else replaces it. It is write-only — no read ever returns it.
     *
     * CAUTION on update: moving client_id, or clearing the secret, DROPS the
     * consent (the daemon revokes the token at Google and erases it). The
     * account then comes back with oauth_connected=false and imports nothing
     * until the consent is replayed. That is deliberate: a refresh token is
     * issued to one client id, so a moved id makes the stored token worthless.
     */
    public function savePhotoAccount(string $instanceId, array $account, ?string $clientSecret = null): array
    {
        $body = $account;
        if ($clientSecret !== null) {
            $body["client_secret"] = $clientSecret;
        }
        $id = isset($account["id"]) ? intval($account["id"]) : 0;
        if ($id > 0) {
            return $this->put("/v1/instances/{$instanceId}/syncs/photos/{$id}", $body);
        }
        return $this->post("/v1/instances/{$instanceId}/syncs/photos", $body);
    }

    public function deletePhotoAccount(string $instanceId, int $accountId): array
    {
        return $this->delete("/v1/instances/{$instanceId}/syncs/photos/" . intval($accountId));
    }

    /** $redirectUri must be an absolute https URL (the console callback page). */
    public function photoAccountOAuthUrl(string $instanceId, int $accountId, string $redirectUri): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/photos/" . intval($accountId)
            . "/oauth/url?redirect_uri=" . rawurlencode($redirectUri));
    }

    /**
     * Revokes at Google FIRST, erases locally second. revoked=false is not an
     * error — it means that token must be withdrawn by hand from the Google
     * account's third-party access page. The client_secret survives; only the
     * token goes.
     */
    public function disconnectPhotoAccountOAuth(string $instanceId, int $accountId): array
    {
        return $this->post("/v1/instances/{$instanceId}/syncs/photos/" . intval($accountId) . "/oauth/disconnect");
    }

    /**
     * Opens a picking session and returns the import row, whose picker_uri is
     * the URL a HUMAN must open to designate the media.
     *
     * The daemon answers 202, not 201: nothing is imported until someone has
     * actually picked. The screen's job is therefore to show picker_uri and
     * then poll getPhotoImport until the status is terminal.
     *
     * Throws on 409 when the account is not consented — the one refusal an
     * operator is most likely to hit, and the message names it.
     */
    public function startPhotoImport(string $instanceId, int $accountId): array
    {
        return $this->post("/v1/instances/{$instanceId}/syncs/photos/" . intval($accountId) . "/imports");
    }

    public function listPhotoImports(string $instanceId, int $accountId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/photos/" . intval($accountId) . "/imports");
    }

    public function getPhotoImport(string $instanceId, int $importId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/photos/imports/" . intval($importId));
    }

    /** The per-media failures of one import (the 100 most recent). */
    public function photoImportErrors(string $instanceId, int $importId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/photos/imports/" . intval($importId) . "/errors");
    }

    /**
     * Stops an import in flight. Answers {"stopped":bool}; false on an
     * already-finished import is NOT an error — cancelling what is done is not
     * a fault, and reporting it as one would send the operator looking for a
     * failure that does not exist.
     */
    public function cancelPhotoImport(string $instanceId, int $importId): array
    {
        return $this->post("/v1/instances/{$instanceId}/syncs/photos/imports/" . intval($importId) . "/cancel");
    }

    // ── Sync rules ─────────────────────────────────────────────

    public function listSyncRules(string $instanceId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/rules");
    }

    public function getSyncRule(string $instanceId, int $ruleId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/rules/" . intval($ruleId));
    }

    /**
     * Creates or updates a rule. $rule["id"] present and non-zero selects an
     * update (PUT); otherwise a new rule is created (POST).
     *
     * bisync_initialized is stripped along with the other server-owned fields:
     * it is the daemon's own baseline marker, written only by a successful
     * non-dry-run resync, and it is `omitempty` — it simply DISAPPEARS from
     * the JSON when false, so never read a bidirectional rule's readiness from
     * it. Read it from syncsStatus()'s unscheduled[] instead.
     */
    public function saveSyncRule(string $instanceId, array $rule): array
    {
        $id = intval($rule["id"] ?? 0);
        $body = $this->stripSyncReadOnly($rule, [
            "id", "instance_id", "bisync_initialized", "created_at", "updated_at",
        ]);
        foreach (["enabled", "propagate_deletes"] as $flag) {
            if (array_key_exists($flag, $body)) {
                $body[$flag] = (bool)$body[$flag];
            }
        }

        if ($id > 0) {
            return $this->put("/v1/instances/{$instanceId}/syncs/rules/{$id}", $body);
        }
        return $this->post("/v1/instances/{$instanceId}/syncs/rules", $body);
    }

    public function deleteSyncRule(string $instanceId, int $ruleId): array
    {
        return $this->delete("/v1/instances/{$instanceId}/syncs/rules/" . intval($ruleId));
    }

    /**
     * Runs a rule now. dry_run is the ONLY query parameter this route accepts,
     * it must appear EXACTLY once, and its value must be a readable boolean —
     * an empty value, a repeat, or another spelling of the key ("dryRun",
     * "dry-run") is a 400, never a silent fall back to a REAL transfer. Hence
     * the literal "true"/"false" built here rather than any cast of $dryRun.
     *
     * Answers a run report (8 possible statuses, aborted_guard/cancelled among
     * them); a rule that merely failed is data in that report, not an error.
     */
    public function runSyncRule(string $instanceId, int $ruleId, bool $dryRun = false): array
    {
        $query = "?dry_run=" . ($dryRun ? "true" : "false");
        return $this->post("/v1/instances/{$instanceId}/syncs/rules/" . intval($ruleId) . "/run{$query}");
    }

    /**
     * Asks the running pass to stop. Takes NO body and NO query parameter at
     * all — any parameter is a 400 — so nothing is appended here.
     *
     * Returns {"stopped":bool}. false is NOT an error: the stop is idempotent,
     * and false only says no cancellation was registered (the pass was in a
     * precheck, or in the ownership walk, or already over — in every one of
     * which no further byte will move). ⚠️ true means "a cancellation was
     * registered", not "a transfer was interrupted". The real outcome of a
     * pass is read from its history entry (status: cancelled), never here.
     */
    public function stopSyncRule(string $instanceId, int $ruleId): array
    {
        return $this->post("/v1/instances/{$instanceId}/syncs/rules/" . intval($ruleId) . "/stop");
    }

    /**
     * Establishes a bidirectional rule's baseline (rclone bisync --resync).
     * Bidir only. confirm and dry_run go in the BODY — this route accepts no
     * query parameter whatsoever — and both are sent as real booleans.
     *
     * ⚠️ A --resync --dry-run initialises NOTHING: only a confirmed,
     * non-dry-run resync writes the baseline the rule then needs to run.
     */
    public function resyncSyncRule(string $instanceId, int $ruleId, bool $confirm, bool $dryRun = false): array
    {
        return $this->post("/v1/instances/{$instanceId}/syncs/rules/" . intval($ruleId) . "/resync",
            ["confirm" => (bool)$confirm, "dry_run" => (bool)$dryRun]);
    }

    /**
     * One rule's run history (cloistered: an unknown rule, or one belonging to
     * another instance, is a 404 rather than an empty page). Filters: status,
     * since (RFC 3339), limit, offset — instance_id/rule_id are NOT accepted
     * here, they come from the path. Throws \InvalidArgumentException on any
     * other key — see syncQuery().
     */
    public function listSyncRuleRuns(string $instanceId, int $ruleId, array $filters = []): array
    {
        $allowed = ["status", "since", "limit", "offset"];
        return $this->get("/v1/instances/{$instanceId}/syncs/rules/" . intval($ruleId) . "/runs"
            . $this->syncQuery($filters, $allowed));
    }

    /** One run in full, errors[] included. Accepts no query parameter. */
    public function getSyncRun(string $instanceId, int $ruleId, int $runId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/rules/" . intval($ruleId)
            . "/runs/" . intval($runId));
    }

    /**
     * Live progress of the current pass. Always 200; when nothing is in
     * flight it answers running:false and progress:null — so test "running",
     * never the mere presence of the progress key.
     */
    public function syncRuleProgress(string $instanceId, int $ruleId): array
    {
        return $this->get("/v1/instances/{$instanceId}/syncs/rules/" . intval($ruleId) . "/progress");
    }

    // ── Sync helpers ───────────────────────────────────────────

    /**
     * Builds a query string for the sync read routes, which refuse an unknown
     * key, a key sent twice AND a key present with an empty value.
     *
     * An empty (or null) filter is therefore OMITTED entirely rather than sent
     * as "key=", and a key outside $allowed throws here instead of travelling
     * to a 400 — or worse, being dropped silently and handing the caller a
     * wider page than the one they asked for.
     *
     * ⚠️ THROWS \InvalidArgumentException on an unknown key, and that reaches
     * its two public callers — listSyncRuns() and listSyncRuleRuns(). Uncaught
     * that is a blank page, so BUILD THE FILTER ARRAY FIELD BY FIELD: a
     * $_GET passed whole will throw on the page's own parameters (the page
     * name, a tab id, a cache-buster), none of which is a filter.
     */
    private function syncQuery(array $filters, array $allowed): string
    {
        $parts = [];
        foreach ($filters as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                throw new \InvalidArgumentException("unknown sync filter: {$key}");
            }
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                throw new \InvalidArgumentException("sync filter {$key} must be scalar, array given");
            }
            $value = (string)$value;
            if ($value === "") {
                continue;
            }
            $parts[] = rawurlencode((string)$key) . "=" . rawurlencode($value);
        }
        return count($parts) ? "?" . implode("&", $parts) : "";
    }

    /**
     * Drops the fields the daemon owns and a page would otherwise echo back
     * from a GET (ids, timestamps, secret_configured, last-test outcome, the
     * bisync baseline marker). They are declared fields, so sending them is
     * not a 400 — but they are not the caller's to state, and stripping them
     * keeps a save from claiming to set something it cannot.
     */
    private function stripSyncReadOnly(array $entity, array $readOnly): array
    {
        foreach ($readOnly as $key) {
            unset($entity[$key]);
        }
        return $entity;
    }

    // ── Agent module (Windows agent mTLS) ─────────────────────

    /**
     * Les six champs que GET /v1/agents/settings ajoute par-dessus les
     * reglages. C'est un STATUT, pas quelque chose qu'on peut reecrire : le
     * demon decode avec DisallowUnknownFields, donc renvoyer le GET verbatim
     * repond 400.
     */
    private const AGENT_STATUS_FIELDS = array(
        "listening",
        "listen_addresses",
        "ca_fingerprint",
        "ca_expires_at",
        "server_cert_expires_at",
        "last_error",
    );

    /** Les six reglages qu'un appelant a le droit de changer. */
    private const AGENT_SETTING_FIELDS = array(
        "enabled",
        "interface",
        "port",
        "advertise_host",
        "client_cert_ttl_days",
        "identity_map_retention_days",
    );

    /** Etat et reglages du module agent Windows (hote-global). */
    public function agentSettings(): array
    {
        return $this->get('/v1/agents/settings');
    }

    /**
     * agentSettingsMergeBody construit le corps du PUT a partir des reglages
     * en vigueur et des seuls champs que l'appelant veut changer.
     *
     * Elle est PURE — ni socket, ni etat — pour que la propriete qui compte
     * soit testable sans demon : changer le port ne doit pas deranger la
     * conservation, Y COMPRIS quand celle-ci vaut 0 (« garder indefiniment »),
     * qui est le cas ou un ecrasement couterait le plus cher.
     *
     * Les champs de statut sont retires par NOM (liste noire) plutot qu'en
     * selectionnant les reglages connus (liste blanche). Les deux echouent
     * dans des directions opposees : une liste blanche remet silencieusement a
     * zero tout reglage qu'une version future ajouterait, tandis qu'une liste
     * noire fait echouer bruyamment en « 400 unknown field » si un champ de
     * statut apparait. Sur une page qui gouverne la duree de conservation de
     * donnees personnelles, une panne visible vaut mieux qu'une modification
     * silencieuse.
     *
     * Pour la conservation, null est transmis tel quel : cote demon le champ
     * est un pointeur, et JSON null s'y decode en nil, c'est-a-dire le defaut
     * de 90 jours (mesure). Il n'y a donc aucun besoin de « retirer une clef ».
     */
    public static function agentSettingsMergeBody(array $current, array $delta): array
    {
        foreach (array_keys($delta) as $key) {
            if (!in_array($key, self::AGENT_SETTING_FIELDS, true)) {
                throw new \InvalidArgumentException("unknown agent setting: {$key}");
            }
        }
        $body = $current;
        foreach (self::AGENT_STATUS_FIELDS as $field) {
            unset($body[$field]);
        }
        foreach ($delta as $key => $value) {
            $body[$key] = $value;
        }
        return $body;
    }

    /**
     * saveAgentSettings relit les reglages en vigueur, applique le delta et
     * ecrit UNE fois. Tous les chemins d'ecriture de la page passent par ici :
     * il n'existe qu'un seul endroit qui construise l'objet complet.
     *
     * Leve \RuntimeException comme tout le reste de cette classe. Le code HTTP
     * est dans le message ; l'appelant le distingue avec
     * agentSaveFailureKind().
     */
    public function saveAgentSettings(array $delta): array
    {
        $current = $this->agentSettings();
        return $this->put('/v1/agents/settings', self::agentSettingsMergeBody($current, $delta));
    }

    /**
     * Le certificat PUBLIC de l'autorite, en PEM. C'est ce qu'un poste Windows
     * epingle pour reconnaitre le listener.
     *
     * rawGet et non get : la reponse est un certificat, pas du JSON. La passer
     * au decodeur rendrait null sans dire pourquoi.
     */
    public function agentCACertificate(): string
    {
        return $this->rawGet('/v1/agents/ca.crt');
    }

    /** Reapplique les reglages en vigueur : relance le listener sans rien changer. */
    public function agentListenerReload(): array
    {
        return $this->post('/v1/agents/listener/reload');
    }

    /**
     * Remplace l'autorite. DESAVOUE TOUS LES POSTES ENROLES : leurs
     * certificats ont ete signes par l'ancienne CA et ne seront plus acceptes.
     *
     * La confirmation est exigee DANS LE CORPS par le daemon, exactement pour
     * qu'un POST parti par accident ne puisse pas faire cela. On la pose ici
     * en toutes lettres plutot que de la rendre configurable : il n'y a aucun
     * autre usage legitime de cette route.
     */
    public function agentRegenerateCA(): array
    {
        return $this->post('/v1/agents/ca/regenerate', array("confirm" => "regenerate"));
    }

    /**
     * agentSaveFailureKind classe l'echec d'un saveAgentSettings a partir du
     * message de l'exception, que request() prefixe par « API error (CODE) ».
     * Les trois echecs ne veulent pas dire la meme chose et la page ne doit
     * pas les presenter pareil :
     *   "invalid"   400 — les valeurs sont refusees, la modale reste ouverte
     *   "listen"    409 — le module n'a pas pu ecouter, il reste eteint
     *   "rollback"  500 — applique puis annule : la demande n'a RIEN laisse
     *   "unknown"   tout le reste (socket injoignable, JSON illisible)
     */
    public static function agentSaveFailureKind(string $message): string
    {
        if (strpos($message, "API error (400)") !== false) { return "invalid"; }
        if (strpos($message, "API error (409)") !== false) { return "listen"; }
        if (strpos($message, "API error (500)") !== false) { return "rollback"; }
        return "unknown";
    }

    /**
     * agentHttpCode extrait le code HTTP du message d'une \RuntimeException
     * levee par request(), qui le prefixe par « API error (CODE): ».
     *
     * Elle existe parce que les 409 de D2b-1 ne veulent PAS tous dire la meme
     * chose : le meme code signifie « module eteint » sur la frappe d'un
     * jeton, « poste revoque » sur une liaison de compte et « poste pas encore
     * revoque » sur une suppression. Un classifieur par NOM d'echec comme
     * agentSaveFailureKind() serait donc faux ici ; c'est l'appelant, qui sait
     * quelle route il a appelee, qui traduit.
     *
     * Le motif est ancre en DEBUT de message : un motif de revocation saisi
     * par l'administrateur peut contenir « (409) » et ne doit pas etre lu
     * comme un code de reponse.
     */
    public static function agentHttpCode(string $message): int
    {
        if (preg_match('#^API error \((\d{3})\)#', $message, $m) === 1) {
            return (int)$m[1];
        }
        return 0;
    }

    // ── Postes Windows d'une instance (D2b-1) ─────────────────
    //
    // Sept routes sous /v1/instances/{id}/agents. Elles ne font que
    // transporter : toute la logique testable sans demon vit dans les
    // fonctions pures agentRowCells() et agentHttpCode().

    /** Les postes inscrits. Les revoques RESTENT listes (le demon ne les cache pas). */
    public function listAgents(string $instanceId): array
    {
        return $this->get('/v1/instances/' . rawurlencode($instanceId) . '/agents');
    }

    /**
     * Frappe un code d'enrolement. La reponse porte le code EN CLAIR et c'est
     * la SEULE fois : le demon n'en stocke que l'empreinte, et
     * listEnrollmentTokens() ne le rend jamais.
     *
     * $account vide = le jeton ne lie aucun compte.
     * Echecs : 409 le module est eteint ; 400 ttl ou compte invalide.
     */
    public function createEnrollmentToken(string $instanceId, int $ttlMinutes, string $account): array
    {
        return $this->post('/v1/instances/' . rawurlencode($instanceId) . '/agents/enrollment-tokens',
            array("ttl_minutes" => $ttlMinutes, "account" => $account));
    }

    /** Les jetons encore utilisables : token_id, instance_id, account, created_at, expires_at. Jamais le code. */
    public function listEnrollmentTokens(string $instanceId): array
    {
        return $this->get('/v1/instances/' . rawurlencode($instanceId) . '/agents/enrollment-tokens');
    }

    public function deleteEnrollmentToken(string $instanceId, string $tokenId): array
    {
        return $this->delete('/v1/instances/' . rawurlencode($instanceId)
            . '/agents/enrollment-tokens/' . rawurlencode($tokenId));
    }

    /** Revoque un poste. $reason est borne a 200 caracteres par le demon (400 au-dela). */
    public function revokeAgent(string $instanceId, string $clientId, string $reason): array
    {
        return $this->post('/v1/instances/' . rawurlencode($instanceId)
            . '/agents/' . rawurlencode($clientId) . '/revoke', array("reason" => $reason));
    }

    /**
     * Lie le compte serveur au nom duquel le poste cherche.
     * Echecs : 400 compte invalide ; 404 inconnu ; 409 le poste est REVOQUE.
     */
    public function setAgentAccount(string $instanceId, string $clientId, string $account): array
    {
        return $this->put('/v1/instances/' . rawurlencode($instanceId)
            . '/agents/' . rawurlencode($clientId), array("account" => $account));
    }

    /** Politique poste de travail : ["stored" => ce qu'a ecrit l'administrateur, "effective" => ce que recoivent les postes]. */
    public function getWorkstationPolicy(string $instanceId): array
    {
        return $this->get('/v1/instances/' . rawurlencode($instanceId) . '/workstation-policy');
    }

    /**
     * Remplace la politique ENTIERE ; array() revient aux defauts. 400 (message
     * nommant le champ) si une valeur est refusee. Le corps vide part en « {} » :
     * un « [] » serait refuse par le demon.
     */
    public function setWorkstationPolicy(string $instanceId, array $policy): array
    {
        return $this->request('PUT', '/v1/instances/' . rawurlencode($instanceId)
            . '/workstation-policy', $policy, true);
    }

    /** Supprime un poste. 409 tant qu'il n'est pas DEJA revoque : la sequence est en deux temps. */
    public function deleteAgent(string $instanceId, string $clientId): array
    {
        return $this->delete('/v1/instances/' . rawurlencode($instanceId)
            . '/agents/' . rawurlencode($clientId));
    }

    /**
     * agentRowCells transforme une ligne rendue par listAgents() en cellules
     * PRETES A POSER DANS LE HTML — deja echappees.
     *
     * Elle est PURE et STATIQUE, et ce n'est pas un gout de style : il n'y a
     * ni demon ni socket sur la VM de developpement, donc c'est la seule
     * facon de PROUVER l'echappement avant deploiement. Son garde-fou est
     * class.artica-samba.agents-selftest.php.
     *
     * ⚠️ device_name, agent_version et os_version sont du TEXTE LIBRE
     * CONTROLE PAR LE POSTE. Cote demon agentproto.ValidField ne verifie que
     * « UTF-8 valide, <= 64 runes imprimables », et unicode.IsPrint accepte
     * < > " ' & — un poste qui s'annonce <img src=x onerror=alert(1)> est
     * stocke tel quel. C'est le PREMIER stockage persistant de chaines
     * fournies par un tiers qu'une page de cette console affiche, dans une
     * console dont l'historique porte un balayage XSS reflechi sur 214
     * fichiers.
     *
     * ENT_QUOTES et non le defaut : client_id part dans un attribut data-,
     * ou un guillemet non echappe sort de l'attribut aussi surement qu'un
     * chevron sort du texte.
     *
     * L'etat revoque se lit sur revoked_at : le demon rend "" pour un
     * horodatage nul (rfc3339 sur un time.Time zero), donc une chaine non
     * vide EST la revocation. Ne pas chercher un booleen, il n'y en a pas.
     */
    public static function agentRowCells(array $agent): array
    {
        $esc = static function ($v): string {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };
        $or = static function ($v, string $fallback) use ($esc): string {
            $s = (string)$v;
            return $s === "" ? $fallback : $esc($s);
        };
        $revokedAt = (string)($agent["revoked_at"] ?? "");
        return array(
            "device_name"         => $or($agent["device_name"] ?? "", "—"),
            "account"             => $or($agent["account"] ?? "", "—"),
            "last_seen"           => $esc($agent["last_seen_at"] ?? ""),
            "last_seen_ip"        => $esc($agent["last_seen_ip"] ?? ""),
            "version"             => $or($agent["agent_version"] ?? "", "—"),
            "os_version"          => $esc($agent["os_version"] ?? ""),
            "certificate_expires" => $esc($agent["certificate_expires_at"] ?? ""),
            "client_id"           => $esc($agent["client_id"] ?? ""),
            "revocation_reason"   => $esc($agent["revocation_reason"] ?? ""),
            "revoked_at"          => $esc($revokedAt),
            "is_revoked"          => $revokedAt !== "",
        );
    }

    /**
     * identityRowCells transforme une ligne rendue par listIdentityMap() en
     * cellules PRETES A POSER — chacune echappee POUR SON CONTEXTE.
     *
     * ⚠️ ELLE NE REDUIT RIEN, et c'est la difference essentielle avec
     * agents2_cid() de la page des postes. Reduire a l'alphabet attendu est
     * sans perte sur un IDENTIFIANT OPAQUE (randomHex), mais DESTRUCTEUR sur
     * un NOM : « jean-luc » deviendrait « jeanluc », et l'effacement porterait
     * sur la ligne de quelqu'un d'autre. Mesure a l'appui : RecordIdentity
     * (internal/agentmtls/module.go:296) n'appelle AUCUNE validation, et
     * security.ValidateUsername n'est appele nulle part sur ce chemin — le
     * compte stocke est ce que la dorsale a resolu, unix, LDAP ou AD.
     *
     * D'ou les trois sorties pour le compte, dont le SUFFIXE dit ou chacune
     * est utilisable. Employer la mauvaise se voit alors a la LECTURE, pas a
     * l'execution.
     *
     * ⚠️ account_js porte DEJA ses guillemets (json_encode rend un litteral
     * complet) : ecrire identityErase('<?= $c["account_js"] ?>') les
     * doublerait et casserait le JavaScript. Ecrire identityErase(<?=
     * $c["account_js"] ?>), sans guillemets autour.
     *
     * ⚠️ windows_sid, windows_upn et windows_account_name sont du TEXTE LIBRE
     * CONTROLE PAR LE POSTE : le demon n'exige que « imprimable, <= 256
     * runes », donc < > " ' & passent tous.
     *
     * device_html vide n'est pas une donnee manquante : last_device_name vient
     * d'un LEFT JOIN sur la table des postes (identitymap.go:107) et vaut ''
     * quand le poste a ete SUPPRIME. La ligne survit au poste qu'elle nomme —
     * d'ou device_deleted, que l'appelant rend en clair.
     */
    public static function identityRowCells(array $row): array
    {
        $h = static function ($v): string {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };
        $dash = static function ($v) use ($h): string {
            $s = (string)$v;
            return $s === "" ? "—" : $h($s);
        };
        $account = (string)($row["unix_username"] ?? "");
        $device  = (string)($row["last_device_name"] ?? "");
        return array(
            "account_html"      => $h($account),
            "account_url"       => rawurlencode($account),
            "account_js"        => json_encode($account,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
            // ⚠️ $dash, pas $h : la 4e colonne du groupe « annonce » doit rendre
            // un TIRET quand le poste a ete supprime, comme ses trois voisines
            // (spec §6 : « Une cellule vide rend un tiret, jamais du blanc »).
            // device_deleted reste calcule sur $device, la valeur BRUTE, donc
            // le rendu ne peut pas le fausser — c'est lui que la page emploie
            // pour afficher « poste supprime » dans l'AUTRE colonne.
            "device_html"       => $dash($device),
            "device_deleted"    => $device === "",
            "client_id_html"    => $h($row["last_client_id"] ?? ""),
            "last_seen_html"    => $h($row["last_seen_at"] ?? ""),
            "created_at_html"   => $h($row["created_at"] ?? ""),
            "sid_html"          => $dash($row["windows_sid"] ?? ""),
            "upn_html"          => $dash($row["windows_upn"] ?? ""),
            "account_name_html" => $dash($row["windows_account_name"] ?? ""),
        );
    }

    /**
     * b64urlEncode / b64urlDecode : le QUATRIEME echappement du compte, a cote
     * des trois d'identityRowCells() (_html, _url, _js).
     *
     * Il ne sert qu'a UN endroit : la valeur du champ cache que la modale
     * d'effacement poste. Ce chemin-la traverse quatre transformations que rien
     * d'autre ne subit — contexte d'attribut HTML de field_hidden, puis
     * encodeURIComponent, puis escape() applique PAR-DESSUS par
     * XHRConnection.appendData, puis les 219 str_replace de
     * url_decode_special_tool() appelee par CLEAN_POST(). Ces 219 motifs
     * contiennent une table %XX et un depliage generique « %25 -> % » :
     * MESURE, un compte valant « a%C3%C2¡b » ou « n%25C3%25A9o » ressortait
     * DIFFERENT d'un aller-retour par rawurlencode(), le demon repondait 404,
     * et le 404 etait affiche comme un succes — on n'effacait rien en disant
     * que c'etait fait. Et « % » est autorise dans un sAMAccountName AD.
     *
     * base64url ne produit que du [A-Za-z0-9-_] : ni « % », ni apostrophe, ni
     * chevron, ni esperluette. Aucun des 219 motifs ne peut y matcher (ils
     * contiennent tous un « % »). On ne demontre pas qu'un mecanisme fragile
     * est sur en pratique : on le remplace par un qui ne peut pas echouer.
     *
     * ⚠️ NE REDUIT RIEN, comme identityRowCells() : l'aller-retour est exact
     * sur n'importe quel octet. Un compte reduit designerait quelqu'un d'autre.
     */
    public static function b64urlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /**
     * Inverse de b64urlEncode(). Rend "" — jamais une valeur approchante — si
     * l'entree n'est pas du base64url : l'appelant doit REFUSER, pas partir au
     * demon avec un compte vide qui rendrait un 404 pris pour un succes.
     */
    public static function b64urlDecode(string $enc): string
    {
        if ($enc !== '' && preg_match('#^[A-Za-z0-9_-]+$#', $enc) !== 1) { return ''; }
        $b64 = strtr($enc, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) { $b64 .= str_repeat('=', 4 - $pad); }
        $out = base64_decode($b64, true);
        return $out === false ? '' : $out;
    }

    // ── Table de correspondance d'identites (D2b-2) ───────────
    //
    // Deux routes sous /v1/instances/{id}/identity-map — SŒURS de l'arbre
    // /agents, jamais imbriquees dedans : le chemin est « /identity-map »,
    // pas « /agents/identity-map ».

    /**
     * Les correspondances, triees par le demon sur last_seen_at DESC.
     *
     * $limit vaut 500 par defaut : c'est le PLAFOND de la route, et la page
     * filtre ensuite en PHP. Le defaut du demon est 50 — s'en contenter ferait
     * qu'une recherche ne trouverait pas une personne situee en 60e position,
     * et une recherche qui ment est pire que pas de recherche.
     */
    public function listIdentityMap(string $instanceId, int $limit = 500, int $offset = 0): array
    {
        return $this->get('/v1/instances/' . rawurlencode($instanceId)
            . '/identity-map?limit=' . (int)$limit . '&offset=' . (int)$offset);
    }

    /**
     * Efface la correspondance d'UN compte.
     *
     * $account part tel quel dans le chemin, seulement rawurlencode() : le
     * reduire designerait quelqu'un d'autre. 404 ErrIdentityUnknown quand la
     * ligne n'existe plus — ce qui n'est PAS un echec, voir l'appelant.
     */
    public function deleteIdentity(string $instanceId, string $account): array
    {
        return $this->delete('/v1/instances/' . rawurlencode($instanceId)
            . '/identity-map/' . rawurlencode($account));
    }

    // ── HTTP Methods ──────────────────────────────────────────

    private function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    private function post(string $path, ?array $body = null): array
    {
        return $this->request('POST', $path, $body);
    }

    private function put(string $path, ?array $body = null): array
    {
        return $this->request('PUT', $path, $body);
    }

    private function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    private function rawGet(string $path): string
    {
        $ch = $this->initCurl($path);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("curl error: {$error}");
        }

        return $response;
    }

    /**
     * GET that captures response headers (used to stream a document with its
     * Content-Type/Content-Disposition instead of decoding a JSON envelope).
     * @return array{status:int,mime:string,filename:string,body:string}
     */
    private function rawGetWithHeaders(string $path): array
    {
        $ch = $this->initCurl($path);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("curl error: {$error}");
        }

        $headerStr = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $mime = '';
        $filename = '';
        foreach (preg_split('/\r\n|\n/', $headerStr) as $line) {
            if (stripos($line, 'Content-Type:') === 0) {
                $mime = trim(substr($line, strlen('Content-Type:')));
            } elseif (stripos($line, 'Content-Disposition:') === 0) {
                if (preg_match('/filename\s*=\s*"?([^";]+)"?/i', $line, $m)) {
                    $filename = trim($m[1]);
                }
            }
        }

        return [
            'status' => (int)$httpCode,
            'mime' => $mime,
            'filename' => $filename,
            'body' => (string)$body,
        ];
    }

    private function request(string $method, string $path, ?array $body = null, bool $emptyAsObject = false): array
    {
        $ch = $this->initCurl($path);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($body !== null) {
            // array() s'encode « [] » ; une route qui attend un objet veut « {} ».
            $json = ($emptyAsObject && $body === array()) ? '{}' : json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("curl error: {$error}");
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new \RuntimeException("invalid JSON response: {$response}");
        }

        if (!($decoded['ok'] ?? false)) {
            throw new \RuntimeException(
                "API error ({$httpCode}): " . ($decoded['error'] ?? 'unknown')
            );
        }

        return $decoded['data'] ?? [];
    }

    private function initCurl(string $path)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->socketPath,
            CURLOPT_URL => "http://localhost{$path}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        return $ch;
    }
}
