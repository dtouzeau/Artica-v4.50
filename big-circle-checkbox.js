/* Big Circle Checkbox – On-Demand (pure JS, inline style objects) */
(function () {
    var REQUIRED_VERSION = '1.0.4';
    const COLORS = {
        card: "#111317",
        text: "#e6eaf2",
        muted: "#9aa4b2",
        border: "#20242b",
        ring: "rgba(91,156,255,0.25)",
        ok: "#1AB394",      // checked
        grey: "#9aa4b2",    // unchecked
        greyLight: "#e6e8ee"// disabled
    };

    // Utility: create element with inline styles + attributes (supports { text, html })
    function el(tag, styleObj = {}, attrs = {}) {
        const node = document.createElement(tag);
        Object.assign(node.style, styleObj);
        for (const [k, v] of Object.entries(attrs)) {
            if (k === "text") node.textContent = v;
            else if (k === "html") node.innerHTML = v;   // allow trusted HTML
            else node.setAttribute(k, v);
        }
        return node;
    }

    // Build a single Big Circle Checkbox card
    function createCard({
                            title,
                            titleHTML,
                            description,
                            descriptionHTML,
                            defaultChecked = false,
                            disabled = false,
                            onChange
                        } = {}) {
        const styles = {
            card: {
                position: "relative",
                display: "flex",
                alignItems: "flex-start",
                gap: "16px",
                background: COLORS.card,
                border: `1px solid ${COLORS.border}`,
                borderRadius: "16px",
                padding: "18px",
                cursor: disabled ? "not-allowed" : "pointer",
                userSelect: "none",
                transition: "box-shadow .2s ease, transform .02s ease, border-color .2s ease"
            },
            hiddenInput: {
                position: "absolute",
                top: "0", left: "0", right: "0", bottom: "0", // overlay entire card
                opacity: "0",
                width: "100%",
                height: "100%",
                cursor: disabled ? "not-allowed" : "pointer",
                zIndex: "1"
            },
            circle: {
                flex: "0 0 auto",
                width: "64px",
                height: "64px",
                borderRadius: "50%",
                display: "grid",
                placeItems: "center",
                border: `2px solid ${COLORS.border}`,
                background: COLORS.grey, // unchecked
                boxShadow: "none",
                transition: "background-color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .02s ease"
            },
            tick: {
                width: "34px",
                height: "34px",
                opacity: "0",
                transform: "scale(0.9)",
                transition: "opacity .18s ease, transform .18s ease",
                color: "#ffffff"
            },
            content: { display: "grid", gap: "6px", minWidth: "0" },
            title:   { fontWeight: "800", letterSpacing: ".2px", fontSize: "18px", color: COLORS.text },
            desc:    { fontSize: "16px", color: COLORS.muted }
        };

        const wrap  = el("label", styles.card);
        const input = el("input", styles.hiddenInput, {
            type: "checkbox",
            "aria-checked": String(!!defaultChecked),
            "aria-disabled": String(!!disabled)
        });
        input.checked  = !!defaultChecked;
        input.disabled = !!disabled;

        const circle = el("span", styles.circle, { "aria-hidden": "true" });

        const svg  = el("svg", styles.tick, { viewBox: "0 0 24 24", fill: "none" });
        svg.setAttribute("stroke", "currentColor");
        svg.setAttribute("stroke-width", "3");
        svg.setAttribute("stroke-linecap", "round");
        svg.setAttribute("stroke-linejoin", "round");
        const poly = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
        poly.setAttribute("points", "20 6 9 17 4 12");
        svg.appendChild(poly);

        const content = el("span", styles.content);

        const titleEl = (titleHTML != null)
            ? el("span", styles.title, { html: titleHTML }) // trusted HTML
            : el("span", styles.title, { text: title || "Title" });

        const descEl = (descriptionHTML != null)
            ? el("span", styles.desc, { html: descriptionHTML }) // trusted HTML
            : el("span", styles.desc, { text: description || "Explanation goes here." });

        content.append(titleEl, descEl);

        circle.appendChild(svg);
        wrap.append(input, circle, content);

        function applyState() {
            if (input.disabled) {
                circle.style.background = COLORS.greyLight;
                circle.style.borderColor = COLORS.greyLight;
                circle.style.boxShadow = "none";
                titleEl.style.color = COLORS.muted;
                descEl.style.color  = COLORS.muted;
            } else if (input.checked) {
                circle.style.background = COLORS.ok;        // #1AB394
                circle.style.borderColor = COLORS.ok;
                circle.style.boxShadow = "0 10px 22px -10px rgba(26,179,148,0.5)";
                titleEl.style.color = COLORS.text;
                descEl.style.color  = COLORS.muted;
            } else {
                circle.style.background = COLORS.grey;
                circle.style.borderColor = COLORS.border;
                circle.style.boxShadow = "none";
                titleEl.style.color = COLORS.text;
                descEl.style.color  = COLORS.muted;
            }
            svg.style.opacity   = input.checked && !input.disabled ? "1" : "0";
            svg.style.transform = input.checked && !input.disabled ? "scale(1)" : "scale(0.9)";
            wrap.style.cursor   = input.disabled ? "not-allowed" : "pointer";
        }

        // Focus ring
        input.addEventListener("focus", () => { if (!input.disabled) wrap.style.boxShadow = `0 0 0 4px ${COLORS.ring}`; });
        input.addEventListener("blur",  () => { wrap.style.boxShadow = "none"; });

        // Press micro-animation
        wrap.addEventListener("mousedown", () => { if (!input.disabled) wrap.style.transform = "translateY(1px)"; });
        ["mouseup","mouseleave"].forEach(evt => wrap.addEventListener(evt, () => { wrap.style.transform = "translateY(0)"; }));

        // Toggle (native)
        input.addEventListener("change", () => {
            input.setAttribute("aria-checked", String(input.checked));
            applyState();
            if (typeof onChange === "function") onChange(input.checked);
        });

        // Forward label clicks to input (single native toggle)
        wrap.addEventListener("click", (e) => {
            if (input.disabled) return;
            if (e.target !== input) {
                e.preventDefault();
                input.click(); // fires 'change'
            }
        });

        applyState();

        return {
            root: wrap,
            input,
            setChecked(flag) {
                input.checked = !!flag;
                input.setAttribute("aria-checked", String(!!flag));
                input.dispatchEvent(new Event("change", { bubbles: true })); // keep onChange in sync
            },
            setDisabled(flag) {
                input.disabled = !!flag;
                input.setAttribute("aria-disabled", String(!!flag));
                applyState();
            }
        };
    }

    // Public API (global)
    const API = {
        /**
         * Render multiple cards into a container by ID.
         * @param {string} containerId
         * @param {Array<{title?:string,titleHTML?:string,description?:string,descriptionHTML?:string,defaultChecked?:boolean,disabled?:boolean,onChange?:(v:boolean)=>void}>} items
         * @returns {Array} controllers
         */
        renderList(containerId, items = []) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return []; }
            const grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
            host.appendChild(grid);
            const ctrls = [];
            for (const cfg of items) {
                const card = createCard(cfg || {});
                grid.appendChild(card.root);
                ctrls.push(card);
            }
            return ctrls;
        },

        /**
         * Append a single card to a container by ID.
         * @param {string} containerId
         * @param {object} item
         * @returns {object|null} controller
         */
        add(containerId, item = {}) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return null; }
            // Reuse last grid if present, else create a new one
            let grid = host.lastElementChild;
            const isGrid = grid && grid.style && grid.style.display === "grid";
            if (!isGrid) {
                grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
                host.appendChild(grid);
            }
            const card = createCard(item);
            grid.appendChild(card.root);
            return card;
        },

        /** Clear all content of a container by ID. */
        clear(containerId) {
            const host = document.getElementById(containerId);
            if (host) host.innerHTML = "";
        }
    };

    window.BigCircleCheckbox = API;
    window.BigCircleCheckbox.__version__ = REQUIRED_VERSION;
})();

/* ---------- Safe helpers to wait for container and render ---------- */
function toDomRoot(root) {
    if (!root) return document;
    // jQuery object?
    if (root.jquery || (window.jQuery && root instanceof jQuery)) {
        return root[0] || document;
    }
    // Element or Document?
    if (root.nodeType === 1 || root.nodeType === 9) return root;
    // Fallback
    return document;
}

function toSelectorOrElement(idOrSelector) {
    if (!idOrSelector) return null;
    // If it's already an Element, pass through
    if (idOrSelector.nodeType === 1) return idOrSelector;
    // Else treat as string
    var sel = String(idOrSelector);
    return sel.startsWith('#') ? sel : ('#' + sel);
}

/**
 * Wait until an element exists in the DOM (inside an optional root).
 * idOrSelector: Element | "#id" | "id"
 * root: Document | Element | jQuery
 */
function waitForContainer(idOrSelector, timeoutMs = 5000, root = document) {
    var normRoot = toDomRoot(root);
    var target = toSelectorOrElement(idOrSelector);
    var resolveIfFound = function (resolve) {
        var el = (target && target.nodeType === 1)
            ? target
            : normRoot.querySelector(target);
        if (el) { resolve(el); return true; }
        return false;
    };

    return new Promise(function (resolve, reject) {
        if (resolveIfFound(resolve)) return;

        var timer = setTimeout(function () {
            try { observer.disconnect(); } catch(_) {}
            reject(new Error('Container not found within ' + timeoutMs + 'ms: ' + (target && target.nodeType === 1 ? '[Element]' : target)));
        }, timeoutMs);

        var observer = new MutationObserver(function () {
            resolveIfFound(function (el) {
                clearTimeout(timer);
                observer.disconnect();
                resolve(el);
            });
        });

        var observeNode = (normRoot === document) ? document.documentElement : normRoot;
        observer.observe(observeNode, { childList: true, subtree: true });
    });
}

/** Render a list safely once the container exists. */
function renderBigCheckboxListSafe(idOrSelector, items, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigCircleCheckbox.renderList(cid, items);
        })
        .catch(function (err) { console.warn(err.message); return []; });
}

/** Add a single card safely once the container exists. */
function addBigCheckboxSafe(idOrSelector, item, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigCircleCheckbox.add(cid, item);
        })
        .catch(function (err) { console.warn(err.message); return null; });
}
