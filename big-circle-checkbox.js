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

    // Build a Big Rectangle Integer Input card
    function createIntegerCard({
                                   title,
                                   titleHTML,
                                   description,
                                   descriptionHTML,
                                   defaultValue = 0,
                                   min = null,
                                   max = null,
                                   disabled = false,
                                   onChange,
                                   onSave
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
                userSelect: "none",
                transition: "box-shadow .2s ease, border-color .2s ease"
            },
            rectangle: {
                flex: "0 0 auto",
                width: "164px",
                height: "64px",
                borderRadius: "12px",
                display: "grid",
                placeItems: "center",
                border: `2px solid ${COLORS.border}`,
                background: COLORS.grey,
                boxShadow: "none",
                transition: "background-color .2s ease, border-color .2s ease, box-shadow .2s ease"
            },
            inputField: {
                width: "100%",
                height: "100%",
                border: "none",
                background: "transparent",
                color: "#ffffff",
                fontSize: "20px",
                fontWeight: "700",
                textAlign: "center",
                outline: "none",
                padding: "0 12px"
            },
            content: { display: "grid", gap: "6px", minWidth: "0", flex: "1" },
            title: { fontWeight: "800", letterSpacing: ".2px", fontSize: "18px", color: COLORS.text },
            desc: { fontSize: "16px", color: COLORS.muted }
        };

        const wrap = el("div", styles.card);
        const rectangle = el("div", styles.rectangle);

        const input = el("input", styles.inputField, {
            type: "number",
            value: String(defaultValue || 0),
            "aria-label": "Integer value",
            "aria-disabled": String(!!disabled)
        });

        if (min !== null) input.setAttribute("min", String(min));
        if (max !== null) input.setAttribute("max", String(max));
        input.disabled = !!disabled;

        const content = el("span", styles.content);

        const titleEl = (titleHTML != null)
            ? el("span", styles.title, { html: titleHTML })
            : el("span", styles.title, { text: title || "Title" });

        const descEl = (descriptionHTML != null)
            ? el("span", styles.desc, { html: descriptionHTML })
            : el("span", styles.desc, { text: description || "Enter an integer value." });

        content.append(titleEl, descEl);
        rectangle.appendChild(input);
        wrap.append(rectangle, content);

        let lastSavedValue = defaultValue || 0;

        function validateInput() {
            let val = parseInt(input.value, 10);
            if (isNaN(val)) {
                val = defaultValue || 0;
                input.value = val;
            }
            if (min !== null && val < min) {
                val = min;
                input.value = val;
            }
            if (max !== null && val > max) {
                val = max;
                input.value = val;
            }
            return val;
        }

        function applyState() {
            if (input.disabled) {
                rectangle.style.background = COLORS.greyLight;
                rectangle.style.borderColor = COLORS.greyLight;
                rectangle.style.boxShadow = "none";
                titleEl.style.color = COLORS.muted;
                descEl.style.color = COLORS.muted;
                input.style.color = COLORS.muted;
            } else {
                const val = parseInt(input.value, 10);
                const hasValue = !isNaN(val) && val !== 0;
                rectangle.style.background = hasValue ? COLORS.ok : COLORS.grey;
                rectangle.style.borderColor = hasValue ? COLORS.ok : COLORS.border;
                rectangle.style.boxShadow = hasValue ? "0 10px 22px -10px rgba(26,179,148,0.5)" : "none";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            }
        }

        function saveValue() {
            const val = validateInput();
            applyState();

            // Only save if value changed
            if (val !== lastSavedValue) {
                lastSavedValue = val;
                if (typeof onSave === "function") onSave(val);
            }
        }

        // Focus ring
        input.addEventListener("focus", () => {
            if (!input.disabled) wrap.style.boxShadow = `0 0 0 4px ${COLORS.ring}`;
        });

        // Blur - save if changed
        input.addEventListener("blur", () => {
            wrap.style.boxShadow = "none";
            saveValue();
        });

        // Enter key - save if changed
        input.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.keyCode === 13) {
                e.preventDefault();
                input.blur(); // This will trigger blur event which saves
            }
        });

        // Input change - update visual state only
        input.addEventListener("input", () => {
            applyState();
            const val = parseInt(input.value, 10);
            if (!isNaN(val) && typeof onChange === "function") {
                onChange(val);
            }
        });

        // Prevent mouse wheel scrolling from changing value
        input.addEventListener("wheel", (e) => {
            e.preventDefault();
        });

        applyState();

        return {
            root: wrap,
            input,
            getValue() {
                return parseInt(input.value, 10) || 0;
            },
            setValue(val) {
                input.value = String(val || 0);
                lastSavedValue = val || 0;
                validateInput();
                applyState();
            },
            setDisabled(flag) {
                input.disabled = !!flag;
                input.setAttribute("aria-disabled", String(!!flag));
                applyState();
            }
        };
    }

    // Public API for Integer Input
    const IntegerAPI = {
        renderList(containerId, items = []) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return []; }
            const grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
            host.appendChild(grid);
            const ctrls = [];
            for (const cfg of items) {
                const card = createIntegerCard(cfg || {});
                grid.appendChild(card.root);
                ctrls.push(card);
            }
            return ctrls;
        },

        add(containerId, item = {}) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return null; }
            let grid = host.lastElementChild;
            const isGrid = grid && grid.style && grid.style.display === "grid";
            if (!isGrid) {
                grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
                host.appendChild(grid);
            }
            const card = createIntegerCard(item);
            grid.appendChild(card.root);
            return card;
        },

        clear(containerId) {
            const host = document.getElementById(containerId);
            if (host) host.innerHTML = "";
        }
    };

    window.BigCircleInteger = IntegerAPI;
    window.BigCircleInteger.__version__ = REQUIRED_VERSION;

    // Build a Network Address Input card (no left indicator, field below description)
    function createNetworkCard({
                                   title,
                                   titleHTML,
                                   description,
                                   descriptionHTML,
                                   defaultValue = '',
                                   placeholder = '192.168.1.0/24',
                                   disabled = false,
                                   onChange,
                                   onSave
                               } = {}) {
        const styles = {
            card: {
                position: "relative",
                display: "grid",
                gap: "12px",
                background: COLORS.card,
                border: `1px solid ${COLORS.border}`,
                borderRadius: "16px",
                padding: "18px",
                userSelect: "none",
                transition: "box-shadow .2s ease, border-color .2s ease"
            },
            header: { display: "grid", gap: "6px" },
            title: { fontWeight: "800", letterSpacing: ".2px", fontSize: "18px", color: COLORS.text },
            desc: { fontSize: "16px", color: COLORS.muted },
            inputWrapper: {
                width: "100%",
                borderRadius: "12px",
                border: `2px solid ${COLORS.border}`,
                background: COLORS.grey,
                boxShadow: "none",
                transition: "background-color .2s ease, border-color .2s ease, box-shadow .2s ease",
                overflow: "hidden"
            },
            inputField: {
                width: "100%",
                border: "none",
                background: "transparent",
                color: "#ffffff",
                fontSize: "18px",
                fontWeight: "600",
                outline: "none",
                padding: "14px 16px",
                fontFamily: "monospace"
            }
        };

        const wrap = el("div", styles.card);
        const header = el("div", styles.header);

        const titleEl = (titleHTML != null)
            ? el("div", styles.title, { html: titleHTML })
            : el("div", styles.title, { text: title || "Network Address" });

        const descEl = (descriptionHTML != null)
            ? el("div", styles.desc, { html: descriptionHTML })
            : el("div", styles.desc, { text: description || "Enter IP address or CIDR notation" });

        header.append(titleEl, descEl);

        const inputWrapper = el("div", styles.inputWrapper);
        const input = el("input", styles.inputField, {
            type: "text",
            value: String(defaultValue || ''),
            placeholder: placeholder,
            "aria-label": "Network address",
            "aria-disabled": String(!!disabled)
        });
        input.disabled = !!disabled;

        inputWrapper.appendChild(input);
        wrap.append(header, inputWrapper);

        let lastSavedValue = defaultValue || '';

        // Validate network address format
        function validateNetwork(value) {
            const trimmed = value.trim();
            if (!trimmed) return { valid: false, error: 'Empty value' };

            // Pattern 1: IP address (192.168.1.1)
            const ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;

            // Pattern 2: CIDR notation (192.168.1.0/24)
            const cidrPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/;

            // Pattern 3: IP with subnet mask (192.168.1.0/255.255.255.0)
            const subnetPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;

            let match;

            // Check CIDR first
            if ((match = trimmed.match(cidrPattern))) {
                const [, oct1, oct2, oct3, oct4, cidr] = match;
                if (!isValidOctet(oct1) || !isValidOctet(oct2) || !isValidOctet(oct3) || !isValidOctet(oct4)) {
                    return { valid: false, error: 'Invalid IP octets (must be 0-255)' };
                }
                const cidrNum = parseInt(cidr, 10);
                if (cidrNum < 0 || cidrNum > 32) {
                    return { valid: false, error: 'Invalid CIDR (must be 0-32)' };
                }
                return { valid: true, value: trimmed };
            }

            // Check subnet mask notation
            if ((match = trimmed.match(subnetPattern))) {
                const [, oct1, oct2, oct3, oct4, mask1, mask2, mask3, mask4] = match;
                if (!isValidOctet(oct1) || !isValidOctet(oct2) || !isValidOctet(oct3) || !isValidOctet(oct4)) {
                    return { valid: false, error: 'Invalid IP octets' };
                }
                if (!isValidOctet(mask1) || !isValidOctet(mask2) || !isValidOctet(mask3) || !isValidOctet(mask4)) {
                    return { valid: false, error: 'Invalid subnet mask octets' };
                }
                if (!isValidSubnetMask(mask1, mask2, mask3, mask4)) {
                    return { valid: false, error: 'Invalid subnet mask format' };
                }
                return { valid: true, value: trimmed };
            }

            // Check plain IP
            if ((match = trimmed.match(ipPattern))) {
                const [, oct1, oct2, oct3, oct4] = match;
                if (!isValidOctet(oct1) || !isValidOctet(oct2) || !isValidOctet(oct3) || !isValidOctet(oct4)) {
                    return { valid: false, error: 'Invalid IP octets (must be 0-255)' };
                }
                return { valid: true, value: trimmed };
            }

            return { valid: false, error: 'Invalid format (use IP, CIDR, or subnet mask)' };
        }

        function isValidOctet(octet) {
            const num = parseInt(octet, 10);
            return num >= 0 && num <= 255;
        }

        function isValidSubnetMask(oct1, oct2, oct3, oct4) {
            // Convert to binary and check it's contiguous 1s followed by 0s
            const mask = (parseInt(oct1) << 24) | (parseInt(oct2) << 16) | (parseInt(oct3) << 8) | parseInt(oct4);
            // Check if it's a valid subnet mask (contiguous bits)
            const binary = mask.toString(2).padStart(32, '0');
            return /^1*0*$/.test(binary);
        }

        function applyState(isValid = null) {
            if (input.disabled) {
                inputWrapper.style.background = COLORS.greyLight;
                inputWrapper.style.borderColor = COLORS.greyLight;
                inputWrapper.style.boxShadow = "none";
                titleEl.style.color = COLORS.muted;
                descEl.style.color = COLORS.muted;
                input.style.color = COLORS.muted;
            } else if (isValid === true) {
                // Valid network
                inputWrapper.style.background = COLORS.ok;
                inputWrapper.style.borderColor = COLORS.ok;
                inputWrapper.style.boxShadow = "0 10px 22px -10px rgba(26,179,148,0.5)";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            } else if (isValid === false) {
                // Invalid network
                inputWrapper.style.background = "#8B2E2E";
                inputWrapper.style.borderColor = "#C74444";
                inputWrapper.style.boxShadow = "0 10px 22px -10px rgba(199,68,68,0.5)";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            } else {
                // Neutral (empty or typing)
                inputWrapper.style.background = COLORS.grey;
                inputWrapper.style.borderColor = COLORS.border;
                inputWrapper.style.boxShadow = "none";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            }
        }

        function saveValue() {
            const result = validateNetwork(input.value);

            if (!result.valid) {
                applyState(false);
                return;
            }

            applyState(true);
            const cleanValue = result.value;

            // Only save if value changed
            if (cleanValue !== lastSavedValue) {
                lastSavedValue = cleanValue;
                if (typeof onSave === "function") onSave(cleanValue);
            }
        }

        // Focus ring
        input.addEventListener("focus", () => {
            if (!input.disabled) wrap.style.boxShadow = `0 0 0 4px ${COLORS.ring}`;
        });

        // Blur - validate and save if changed
        input.addEventListener("blur", () => {
            wrap.style.boxShadow = "none";
            saveValue();
        });

        // Enter key - save if valid
        input.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.keyCode === 13) {
                e.preventDefault();
                input.blur(); // This will trigger blur event which saves
            }
        });

        // Input change - update visual state in real-time
        input.addEventListener("input", () => {
            const result = validateNetwork(input.value);
            applyState(input.value.trim() ? result.valid : null);

            if (typeof onChange === "function") {
                onChange(input.value, result.valid);
            }
        });

        applyState(defaultValue ? validateNetwork(defaultValue).valid : null);

        return {
            root: wrap,
            input,
            getValue() {
                return input.value.trim();
            },
            setValue(val) {
                input.value = String(val || '');
                lastSavedValue = val || '';
                const result = validateNetwork(input.value);
                applyState(result.valid);
            },
            isValid() {
                return validateNetwork(input.value).valid;
            },
            setDisabled(flag) {
                input.disabled = !!flag;
                input.setAttribute("aria-disabled", String(!!flag));
                applyState(input.disabled ? null : validateNetwork(input.value).valid);
            }
        };
    }

    // Public API for Network Input
    const NetworkAPI = {
        renderList(containerId, items = []) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return []; }
            const grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
            host.appendChild(grid);
            const ctrls = [];
            for (const cfg of items) {
                const card = createNetworkCard(cfg || {});
                grid.appendChild(card.root);
                ctrls.push(card);
            }
            return ctrls;
        },

        add(containerId, item = {}) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return null; }
            let grid = host.lastElementChild;
            const isGrid = grid && grid.style && grid.style.display === "grid";
            if (!isGrid) {
                grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
                host.appendChild(grid);
            }
            const card = createNetworkCard(item);
            grid.appendChild(card.root);
            return card;
        },

        clear(containerId) {
            const host = document.getElementById(containerId);
            if (host) host.innerHTML = "";
        }
    };

    window.BigNetworkField = NetworkAPI;
    window.BigNetworkField.__version__ = REQUIRED_VERSION;

    // Build a Domain/Hostname Input card (no left indicator, field below description)
    function createDomainCard({
                                  title,
                                  titleHTML,
                                  description,
                                  descriptionHTML,
                                  defaultValue = '',
                                  placeholder = 'example.com',
                                  disabled = false,
                                  onChange,
                                  onSave
                              } = {}) {
        const styles = {
            card: {
                position: "relative",
                display: "grid",
                gap: "12px",
                background: COLORS.card,
                border: `1px solid ${COLORS.border}`,
                borderRadius: "16px",
                padding: "18px",
                marginTop: "10px",
                userSelect: "none",
                transition: "box-shadow .2s ease, border-color .2s ease"
            },
            header: { display: "grid", gap: "6px" },
            title: { fontWeight: "800", letterSpacing: ".2px", fontSize: "18px", color: COLORS.text },
            desc: { fontSize: "16px", color: COLORS.muted, position: "relative" },
            descContent: { display: "inline" },
            expandBtn: {
                display: "inline",
                marginLeft: "6px",
                color: COLORS.ok,
                cursor: "pointer",
                fontWeight: "600",
                textDecoration: "underline",
                transition: "color .2s ease"
            },
            inputWrapper: {
                width: "100%",
                borderRadius: "12px",
                border: `2px solid ${COLORS.border}`,
                background: COLORS.grey,
                boxShadow: "none",
                transition: "background-color .2s ease, border-color .2s ease, box-shadow .2s ease",
                overflow: "hidden"
            },
            inputField: {
                width: "100%",
                border: "none",
                background: "transparent",
                color: "#ffffff",
                fontSize: "18px",
                fontWeight: "600",
                outline: "none",
                padding: "14px 16px",
                fontFamily: "monospace"
            }
        };

        const wrap = el("div", styles.card);
        const header = el("div", styles.header);

        const titleEl = (titleHTML != null)
            ? el("div", styles.title, { html: titleHTML })
            : el("div", styles.title, { text: title || "Domain Name" });

        // Handle description with expand/collapse for long text
        const descText = descriptionHTML != null ? descriptionHTML : (description || "Enter a valid hostname or domain");
        const descEl = el("div", styles.desc);
        const descContentSpan = el("span", styles.descContent);

        let isExpanded = false;
        let expandBtn = null;
        const shouldTruncate = descText.length > 90;

        if (shouldTruncate) {
            const truncatedText = descText.substring(0, 90) + "...";

            if (descriptionHTML != null) {
                descContentSpan.innerHTML = truncatedText;
            } else {
                descContentSpan.textContent = truncatedText;
            }

            expandBtn = el("span", styles.expandBtn, { text: "more" });
            expandBtn.addEventListener("mouseenter", () => {
                expandBtn.style.color = "#ffffff";
            });
            expandBtn.addEventListener("mouseleave", () => {
                expandBtn.style.color = COLORS.ok;
            });

            expandBtn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (isExpanded) {
                    // Collapse
                    if (descriptionHTML != null) {
                        descContentSpan.innerHTML = truncatedText;
                    } else {
                        descContentSpan.textContent = truncatedText;
                    }
                    expandBtn.textContent = "more";
                    isExpanded = false;
                } else {
                    // Expand
                    if (descriptionHTML != null) {
                        descContentSpan.innerHTML = descText;
                    } else {
                        descContentSpan.textContent = descText;
                    }
                    expandBtn.textContent = "less";
                    isExpanded = true;
                }
            });

            descEl.appendChild(descContentSpan);
            descEl.appendChild(expandBtn);
        } else {
            // No truncation needed
            if (descriptionHTML != null) {
                descContentSpan.innerHTML = descText;
            } else {
                descContentSpan.textContent = descText;
            }
            descEl.appendChild(descContentSpan);
        }

        header.append(titleEl, descEl);

        const inputWrapper = el("div", styles.inputWrapper);
        const input = el("input", styles.inputField, {
            type: "text",
            value: String(defaultValue || ''),
            placeholder: placeholder,
            "aria-label": "Domain name",
            "aria-disabled": String(!!disabled)
        });
        input.disabled = !!disabled;

        inputWrapper.appendChild(input);
        wrap.append(header, inputWrapper);

        let lastSavedValue = defaultValue || '';

        // Validate hostname/domain format
        function validateDomain(value) {
            const trimmed = value.trim();
            if (!trimmed) return { valid: true, value: '' };

            // Pattern 1: Regex pattern (starts with ~)
            // Example: ~^(?.+)\.example\.net$
            if (trimmed.startsWith('~')) {
                try {
                    // Remove the leading ~
                    const pattern = trimmed.substring(1);
                    // Try to create a regex to validate syntax
                    new RegExp(pattern);
                    return { valid: true, value: trimmed };
                } catch (e) {
                    return { valid: false, error: 'Invalid regex pattern: ' + e.message };
                }
            }

            // Pattern 2: IP address (192.168.1.1)
            const ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
            const ipMatch = trimmed.match(ipPattern);
            if (ipMatch) {
                const [, oct1, oct2, oct3, oct4] = ipMatch;
                const octets = [oct1, oct2, oct3, oct4];
                for (const octet of octets) {
                    const num = parseInt(octet, 10);
                    if (num < 0 || num > 255) {
                        return { valid: false, error: 'Invalid IP address (octets must be 0-255)' };
                    }
                }
                return { valid: true, value: trimmed };
            }

            // Pattern 3: Wildcard patterns (* can appear in labels)
            // Examples: *.example.org, mail.*, www.*.example.com
            const hasWildcard = trimmed.includes('*');

            // Check total length (excluding wildcards for length check)
            if (trimmed.length > 253) {
                return { valid: false, error: 'Hostname too long (max 253 characters)' };
            }

            // Check for invalid start/end (but allow * at start/end of labels)
            if (trimmed.startsWith('.') || trimmed.endsWith('.')) {
                return { valid: false, error: 'Hostname cannot start or end with a dot' };
            }

            // Split into labels and validate each
            const labels = trimmed.split('.');

            // Must have at least one label
            if (labels.length === 0) {
                return { valid: false, error: 'Invalid hostname format' };
            }

            // Validate each label
            for (let i = 0; i < labels.length; i++) {
                const label = labels[i];

                // Label cannot be empty (unless it's part of a wildcard pattern edge case)
                if (label.length === 0) {
                    return { valid: false, error: 'Empty label in hostname' };
                }

                // Label length check (1-63 characters)
                if (label.length > 63) {
                    return { valid: false, error: 'Label too long (max 63 characters)' };
                }

                // Special handling for wildcard labels
                if (label === '*') {
                    // Pure wildcard is valid
                    continue;
                }

                if (label.includes('*')) {
                    // Partial wildcard (e.g., www*, *mail, w*w)
                    // Allow alphanumeric, hyphen, and asterisk
                    if (!/^[a-zA-Z0-9*-]+$/.test(label)) {
                        return { valid: false, error: 'Invalid characters in wildcard label' };
                    }
                    // Don't allow hyphen at start/end (but * is ok)
                    if (label.startsWith('-') || label.endsWith('-')) {
                        return { valid: false, error: 'Label cannot start or end with hyphen' };
                    }
                    continue;
                }

                // Regular label validation (no wildcards)
                // Label cannot start or end with hyphen
                if (label.startsWith('-') || label.endsWith('-')) {
                    return { valid: false, error: 'Label cannot start or end with hyphen' };
                }

                // Label can only contain alphanumeric and hyphen
                if (!/^[a-zA-Z0-9-]+$/.test(label)) {
                    return { valid: false, error: 'Invalid characters in hostname (only a-z, A-Z, 0-9, hyphen, * allowed)' };
                }
            }

            // If no wildcards, check TLD (last label) should not be all numeric
            if (!hasWildcard) {
                const tld = labels[labels.length - 1];
                if (/^\d+$/.test(tld)) {
                    return { valid: false, error: 'Top-level domain cannot be all numeric' };
                }
            }

            return { valid: true, value: trimmed.toLowerCase() };
        }

        function applyState(isValid = null) {
            if (input.disabled) {
                inputWrapper.style.background = COLORS.greyLight;
                inputWrapper.style.borderColor = COLORS.greyLight;
                inputWrapper.style.boxShadow = "none";
                titleEl.style.color = COLORS.muted;
                descEl.style.color = COLORS.muted;
                input.style.color = COLORS.muted;
            } else if (isValid === true) {
                // Valid domain
                inputWrapper.style.background = COLORS.ok;
                inputWrapper.style.borderColor = COLORS.ok;
                inputWrapper.style.boxShadow = "0 10px 22px -10px rgba(26,179,148,0.5)";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            } else if (isValid === false) {
                // Invalid domain
                inputWrapper.style.background = "#8B2E2E";
                inputWrapper.style.borderColor = "#C74444";
                inputWrapper.style.boxShadow = "0 10px 22px -10px rgba(199,68,68,0.5)";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            } else {
                // Neutral (empty or typing)
                inputWrapper.style.background = COLORS.grey;
                inputWrapper.style.borderColor = COLORS.border;
                inputWrapper.style.boxShadow = "none";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            }
        }

        function saveValue() {
            const result = validateDomain(input.value);

            if (!result.valid) {
                applyState(false);
                return;
            }

            applyState(true);
            const cleanValue = result.value;

            // Only save if value changed
            if (cleanValue !== lastSavedValue) {
                lastSavedValue = cleanValue;
                if (typeof onSave === "function") onSave(cleanValue);
            }
        }

        // Focus ring
        input.addEventListener("focus", () => {
            if (!input.disabled) wrap.style.boxShadow = `0 0 0 4px ${COLORS.ring}`;
        });

        // Blur - validate and save if changed
        input.addEventListener("blur", () => {
            wrap.style.boxShadow = "none";
            saveValue();
        });

        // Enter key - save if valid
        input.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.keyCode === 13) {
                e.preventDefault();
                input.blur(); // This will trigger blur event which saves
            }
        });

        // Input change - update visual state in real-time
        input.addEventListener("input", () => {
            const result = validateDomain(input.value);
            applyState(input.value.trim() ? result.valid : null);

            if (typeof onChange === "function") {
                onChange(input.value, result.valid);
            }
        });

        applyState(defaultValue ? validateDomain(defaultValue).valid : null);

        return {
            root: wrap,
            input,
            getValue() {
                return input.value.trim();
            },
            setValue(val) {
                input.value = String(val || '');
                lastSavedValue = val || '';
                const result = validateDomain(input.value);
                applyState(result.valid);
            },
            isValid() {
                return validateDomain(input.value).valid;
            },
            setDisabled(flag) {
                input.disabled = !!flag;
                input.setAttribute("aria-disabled", String(!!flag));
                applyState(input.disabled ? null : validateDomain(input.value).valid);
            }
        };
    }

    // Public API for Domain Input
    const DomainAPI = {
        renderList(containerId, items = []) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return []; }
            const grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
            host.appendChild(grid);
            const ctrls = [];
            for (const cfg of items) {
                const card = createDomainCard(cfg || {});
                grid.appendChild(card.root);
                ctrls.push(card);
            }
            return ctrls;
        },

        add(containerId, item = {}) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return null; }
            let grid = host.lastElementChild;
            const isGrid = grid && grid.style && grid.style.display === "grid";
            if (!isGrid) {
                grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
                host.appendChild(grid);
            }
            const card = createDomainCard(item);
            grid.appendChild(card.root);
            return card;
        },

        clear(containerId) {
            const host = document.getElementById(containerId);
            if (host) host.innerHTML = "";
        }
    };

    window.BigDomainField = DomainAPI;
    window.BigDomainField.__version__ = REQUIRED_VERSION;

    // Build a URI input card (accepts URLs, hostnames, or null)
    function createUriCard({
                               title,
                               titleHTML,
                               description,
                               descriptionHTML,
                               defaultValue = '',
                               placeholder = 'http://example.com or domain.tld',
                               disabled = false,
                               onChange,
                               onSave
                           } = {}) {
        const styles = {
            card: {
                position: "relative",
                display: "grid",
                gap: "12px",
                background: COLORS.card,
                border: `1px solid ${COLORS.border}`,
                borderRadius: "16px",
                padding: "18px",
                marginTop: "10px",
                userSelect: "none",
                transition: "box-shadow .2s ease, border-color .2s ease"
            },
            header: { display: "grid", gap: "6px" },
            title: { fontWeight: "800", letterSpacing: ".2px", fontSize: "18px", color: COLORS.text },
            desc: { fontSize: "16px", color: COLORS.muted, position: "relative" },
            descContent: { display: "inline" },
            expandBtn: {
                display: "inline",
                marginLeft: "6px",
                color: COLORS.ok,
                cursor: "pointer",
                fontWeight: "600",
                textDecoration: "underline",
                transition: "color .2s ease"
            },
            inputWrapper: {
                width: "100%",
                borderRadius: "12px",
                border: `2px solid ${COLORS.border}`,
                background: COLORS.grey,
                boxShadow: "none",
                transition: "background-color .2s ease, border-color .2s ease, box-shadow .2s ease",
                overflow: "hidden"
            },
            inputField: {
                width: "100%",
                border: "none",
                background: "transparent",
                color: "#ffffff",
                fontSize: "18px",
                fontWeight: "600",
                outline: "none",
                padding: "14px 16px",
                fontFamily: "monospace"
            }
        };

        const wrap = el("div", styles.card);
        const header = el("div", styles.header);

        const titleEl = (titleHTML != null)
            ? el("div", styles.title, { html: titleHTML })
            : el("div", styles.title, { text: title || "URI / Hostname" });

        // Handle description with expand/collapse for long text
        const descText = descriptionHTML != null ? descriptionHTML : (description || "Enter a URL, hostname, or leave empty");
        const descEl = el("div", styles.desc);
        const descContentSpan = el("span", styles.descContent);

        let isExpanded = false;
        let expandBtn = null;
        const shouldTruncate = descText.length > 90;

        if (shouldTruncate) {
            const truncatedText = descText.substring(0, 90) + "...";

            if (descriptionHTML != null) {
                descContentSpan.innerHTML = truncatedText;
            } else {
                descContentSpan.textContent = truncatedText;
            }

            expandBtn = el("span", styles.expandBtn, { text: "more" });
            expandBtn.addEventListener("mouseenter", () => {
                expandBtn.style.color = "#ffffff";
            });
            expandBtn.addEventListener("mouseleave", () => {
                expandBtn.style.color = COLORS.ok;
            });

            expandBtn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (isExpanded) {
                    // Collapse
                    if (descriptionHTML != null) {
                        descContentSpan.innerHTML = truncatedText;
                    } else {
                        descContentSpan.textContent = truncatedText;
                    }
                    expandBtn.textContent = "more";
                    isExpanded = false;
                } else {
                    // Expand
                    if (descriptionHTML != null) {
                        descContentSpan.innerHTML = descText;
                    } else {
                        descContentSpan.textContent = descText;
                    }
                    expandBtn.textContent = "less";
                    isExpanded = true;
                }
            });

            descEl.appendChild(descContentSpan);
            descEl.appendChild(expandBtn);
        } else {
            // No truncation needed
            if (descriptionHTML != null) {
                descContentSpan.innerHTML = descText;
            } else {
                descContentSpan.textContent = descText;
            }
            descEl.appendChild(descContentSpan);
        }

        header.append(titleEl, descEl);

        const inputWrapper = el("div", styles.inputWrapper);
        const input = el("input", styles.inputField, {
            type: "text",
            value: String(defaultValue || ''),
            placeholder: placeholder,
            "aria-label": "URI or hostname",
            "aria-disabled": String(!!disabled)
        });
        input.disabled = !!disabled;

        inputWrapper.appendChild(input);
        wrap.append(header, inputWrapper);

        let lastSavedValue = defaultValue || '';

        // Validate URI format (URL or hostname)
        function validateUri(value) {
            const trimmed = value.trim();

            // Empty/null is valid
            if (!trimmed) return { valid: true, value: '' };

            // Try to parse as URL first
            try {
                const url = new URL(trimmed);
                // Valid URL with protocol
                if (url.protocol === 'http:' || url.protocol === 'https:' ||
                    url.protocol === 'ftp:' || url.protocol === 'ftps:') {
                    return { valid: true, value: trimmed };
                }
            } catch (e) {
                // Not a valid URL, try as hostname
            }

            // Validate as hostname/domain (same logic as BigDomainField)
            // Pattern 1: Regex pattern (starts with ~)
            if (trimmed.startsWith('~')) {
                try {
                    const pattern = trimmed.substring(1);
                    new RegExp(pattern);
                    return { valid: true, value: trimmed };
                } catch (e) {
                    return { valid: false, error: 'Invalid regex pattern: ' + e.message };
                }
            }

            // Pattern 2: IP address (192.168.1.1)
            const ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
            const ipMatch = trimmed.match(ipPattern);
            if (ipMatch) {
                const [, oct1, oct2, oct3, oct4] = ipMatch;
                const octets = [oct1, oct2, oct3, oct4];
                for (const octet of octets) {
                    const num = parseInt(octet, 10);
                    if (num < 0 || num > 255) {
                        return { valid: false, error: 'Invalid IP address (octets must be 0-255)' };
                    }
                }
                return { valid: true, value: trimmed };
            }

            // Pattern 3: Hostname/domain validation
            // Check for invalid start/end
            if (trimmed.startsWith('.') || trimmed.endsWith('.')) {
                return { valid: false, error: 'Hostname cannot start or end with a dot' };
            }

            // Check total length
            if (trimmed.length > 253) {
                return { valid: false, error: 'Hostname too long (max 253 characters)' };
            }

            // Split into labels and validate each
            const labels = trimmed.split('.');

            // Must have at least one label
            if (labels.length === 0) {
                return { valid: false, error: 'Invalid hostname format' };
            }

            // Validate each label
            for (let i = 0; i < labels.length; i++) {
                const label = labels[i];

                if (label.length === 0) {
                    return { valid: false, error: 'Empty label in hostname' };
                }

                if (label.length > 63) {
                    return { valid: false, error: 'Label too long (max 63 characters)' };
                }

                // Special handling for wildcard labels
                if (label === '*') {
                    continue;
                }

                if (label.includes('*')) {
                    if (!/^[a-zA-Z0-9*-]+$/.test(label)) {
                        return { valid: false, error: 'Invalid characters in wildcard label' };
                    }
                    if (label.startsWith('-') || label.endsWith('-')) {
                        return { valid: false, error: 'Label cannot start or end with hyphen' };
                    }
                    continue;
                }

                // Regular label validation
                if (label.startsWith('-') || label.endsWith('-')) {
                    return { valid: false, error: 'Label cannot start or end with hyphen' };
                }

                if (!/^[a-zA-Z0-9-]+$/.test(label)) {
                    return { valid: false, error: 'Invalid characters in hostname (only a-z, A-Z, 0-9, hyphen, * allowed)' };
                }
            }

            // Check TLD not all numeric (unless it's an IP which we already handled)
            const hasWildcard = trimmed.includes('*');
            if (!hasWildcard) {
                const tld = labels[labels.length - 1];
                if (/^\d+$/.test(tld)) {
                    return { valid: false, error: 'Top-level domain cannot be all numeric' };
                }
            }

            return { valid: true, value: trimmed.toLowerCase() };
        }

        function applyState(isValid = null) {
            if (input.disabled) {
                inputWrapper.style.background = COLORS.greyLight;
                inputWrapper.style.borderColor = COLORS.greyLight;
                inputWrapper.style.boxShadow = "none";
                titleEl.style.color = COLORS.muted;
                descEl.style.color = COLORS.muted;
                input.style.color = COLORS.muted;
            } else if (isValid === true) {
                // Valid URI
                inputWrapper.style.background = COLORS.ok;
                inputWrapper.style.borderColor = COLORS.ok;
                inputWrapper.style.boxShadow = "0 10px 22px -10px rgba(26,179,148,0.5)";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            } else if (isValid === false) {
                // Invalid URI
                inputWrapper.style.background = "#8B2E2E";
                inputWrapper.style.borderColor = "#C74444";
                inputWrapper.style.boxShadow = "0 10px 22px -10px rgba(199,68,68,0.5)";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            } else {
                // Neutral (empty or typing)
                inputWrapper.style.background = COLORS.grey;
                inputWrapper.style.borderColor = COLORS.border;
                inputWrapper.style.boxShadow = "none";
                titleEl.style.color = COLORS.text;
                descEl.style.color = COLORS.muted;
                input.style.color = "#ffffff";
            }
        }

        function saveValue() {
            const result = validateUri(input.value);

            if (!result.valid) {
                applyState(false);
                return;
            }

            applyState(true);
            const cleanValue = result.value;

            // Only save if value changed
            if (cleanValue !== lastSavedValue) {
                lastSavedValue = cleanValue;
                if (typeof onSave === "function") onSave(cleanValue);
            }
        }

        // Focus ring
        input.addEventListener("focus", () => {
            if (!input.disabled) wrap.style.boxShadow = `0 0 0 4px ${COLORS.ring}`;
        });

        // Blur - validate and save if changed
        input.addEventListener("blur", () => {
            wrap.style.boxShadow = "none";
            saveValue();
        });

        // Enter key - save if valid
        input.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.keyCode === 13) {
                e.preventDefault();
                input.blur(); // This will trigger blur event which saves
            }
        });

        // Input change - update visual state in real-time
        input.addEventListener("input", () => {
            const result = validateUri(input.value);
            applyState(input.value.trim() ? result.valid : null);

            if (typeof onChange === "function") {
                onChange(input.value, result.valid);
            }
        });

        applyState(defaultValue ? validateUri(defaultValue).valid : null);

        return {
            root: wrap,
            input,
            getValue() {
                return input.value.trim();
            },
            setValue(val) {
                input.value = String(val || '');
                lastSavedValue = val || '';
                const result = validateUri(input.value);
                applyState(result.valid);
            },
            isValid() {
                return validateUri(input.value).valid;
            },
            setDisabled(flag) {
                input.disabled = !!flag;
                input.setAttribute("aria-disabled", String(!!flag));
                applyState(input.disabled ? null : validateUri(input.value).valid);
            }
        };
    }

    // Public API for URI Input
    const UriAPI = {
        renderList(containerId, items = []) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return []; }
            const grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
            host.appendChild(grid);
            const ctrls = [];
            for (const cfg of items) {
                const card = createUriCard(cfg || {});
                grid.appendChild(card.root);
                ctrls.push(card);
            }
            return ctrls;
        },

        add(containerId, item = {}) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return null; }
            let grid = host.lastElementChild;
            const isGrid = grid && grid.style && grid.style.display === "grid";
            if (!isGrid) {
                grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
                host.appendChild(grid);
            }
            const card = createUriCard(item);
            grid.appendChild(card.root);
            return card;
        },

        clear(containerId) {
            const host = document.getElementById(containerId);
            if (host) host.innerHTML = "";
        }
    };

    window.BigUriField = UriAPI;
    window.BigUriField.__version__ = REQUIRED_VERSION;

    // Build an Explain Checkbox card (icon indicator, editable description)
    function createExplainCheckboxCard({
                                           title,
                                           titleHTML,
                                           description,
                                           descriptionHTML,
                                           disabled = false,
                                           onDescriptionSave
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
                marginBottom: "6px",
                cursor: "pointer",
                userSelect: "none",
                transition: "box-shadow .2s ease, transform .02s ease, border-color .2s ease"
            },
            hiddenInput: {
                position: "absolute",
                opacity: "0",
                width: "1px",
                height: "1px",
                pointerEvents: "none"
            },
            iconContainer: {
                flex: "0 0 auto",
                width: "64px",
                height: "64px",
                display: "grid",
                placeItems: "center",
                transition: "transform .02s ease"
            },
            icon: {
                fontSize: "42px",
                color: COLORS.grey,
                transition: "color .2s ease, text-shadow .2s ease",
                textShadow: "none"
            },
            content: { display: "grid", gap: "6px", minWidth: "0", flex: "1" },
            title: { fontWeight: "800", letterSpacing: ".2px", fontSize: "18px", color: COLORS.text },
            descWrapper: {
                position: "relative"
            },
            descDisplay: {
                fontSize: "16px",
                color: COLORS.muted,
                cursor: "text",
                padding: "4px 8px",
                borderRadius: "4px",
                transition: "background-color .2s ease"
            },
            descEdit: {
                fontSize: "16px",
                color: COLORS.text,
                background: COLORS.card,
                border: `2px solid ${COLORS.ok}`,
                borderRadius: "4px",
                padding: "6px 10px",
                outline: "none",
                width: "100%",
                minHeight: "40px",
                fontFamily: "inherit",
                resize: "vertical",
                boxShadow: `0 0 0 4px ${COLORS.ring}`,
                display: "none"
            }
        };

        const wrap = el("div", styles.card);

        const iconContainer = el("span", styles.iconContainer, { "aria-hidden": "true" });
        const icon = el("i", styles.icon);
        icon.className = "fas fa-comment-alt";

        const content = el("span", styles.content);

        const titleEl = (titleHTML != null)
            ? el("span", styles.title, { html: titleHTML })
            : el("span", styles.title, { text: title || "Title" });

        // Description wrapper with display and edit modes
        const descWrapper = el("div", styles.descWrapper);

        const descDisplay = el("div", styles.descDisplay);
        const initialDesc = descriptionHTML != null ? descriptionHTML : (description || "Click to edit explanation...");
        if (descriptionHTML != null) {
            descDisplay.innerHTML = initialDesc;
        } else {
            descDisplay.textContent = initialDesc;
        }

        const descEdit = el("textarea", styles.descEdit);
        descEdit.value = descDisplay.textContent || descDisplay.innerText || '';

        descWrapper.append(descDisplay, descEdit);
        content.append(titleEl, descWrapper);

        iconContainer.appendChild(icon);
        wrap.append(iconContainer, content);

        let isEditing = false;
        let hasBeenEdited = false;  // Track if user has ever entered edit mode
        let lastSavedDescription = descEdit.value;

        function applyState() {
            if (disabled) {
                icon.style.color = COLORS.greyLight;
                icon.style.textShadow = "none";
                titleEl.style.color = COLORS.muted;
                descDisplay.style.color = COLORS.muted;
            } else if (isEditing) {
                // Green when editing
                icon.style.color = COLORS.ok;
                icon.style.textShadow = "0 10px 22px rgba(26,179,148,0.5)";
                titleEl.style.color = COLORS.text;
                descDisplay.style.color = COLORS.muted;
            } else if (hasBeenEdited) {
                // Green after editing
                icon.style.color = COLORS.ok;
                icon.style.textShadow = "0 10px 22px rgba(26,179,148,0.5)";
                titleEl.style.color = COLORS.text;
                descDisplay.style.color = COLORS.muted;
            } else {
                // Grey initially (before any editing)
                icon.style.color = COLORS.grey;
                icon.style.textShadow = "none";
                titleEl.style.color = COLORS.text;
                descDisplay.style.color = COLORS.muted;
            }
            wrap.style.cursor = disabled ? "not-allowed" : "pointer";
        }

        function enterEditMode() {
            if (disabled || isEditing) return;

            isEditing = true;
            wrap.style.cursor = "default";
            descDisplay.style.display = "none";
            descEdit.style.display = "block";
            descEdit.focus();
            descEdit.setSelectionRange(descEdit.value.length, descEdit.value.length);
            applyState();  // Update icon color to green
        }

        function exitEditMode(save = true) {
            if (!isEditing) return;

            isEditing = false;
            hasBeenEdited = true;  // Mark that user has edited at least once
            wrap.style.cursor = disabled ? "not-allowed" : "pointer";
            descEdit.style.display = "none";
            descDisplay.style.display = "block";

            const newValue = descEdit.value.trim();

            if (save && newValue !== lastSavedDescription) {
                // Update display
                descDisplay.textContent = newValue || "Click to edit explanation...";
                lastSavedDescription = newValue;

                // Fire callback
                if (typeof onDescriptionSave === "function") {
                    onDescriptionSave(newValue);
                }
            } else {
                // Restore previous value if not saving
                descEdit.value = lastSavedDescription;
            }

            applyState();  // Update icon color back to grey/green based on hasBeenEdited
        }

        // Click on description to edit
        descDisplay.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            enterEditMode();
        });

        // Click on title to edit
        titleEl.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            enterEditMode();
        });

        // Click on icon to edit
        icon.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            enterEditMode();
        });

        descDisplay.addEventListener("mouseenter", () => {
            if (!disabled && !isEditing) {
                descDisplay.style.background = "rgba(255,255,255,0.05)";
            }
        });

        descDisplay.addEventListener("mouseleave", () => {
            descDisplay.style.background = "transparent";
        });

        // Blur on textarea to exit edit mode
        descEdit.addEventListener("blur", () => {
            exitEditMode(true);
        });

        // Escape to cancel, Enter (without Shift) to save
        descEdit.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                e.preventDefault();
                exitEditMode(false); // Cancel
            }
            // Allow Shift+Enter for newlines in textarea
        });

        // Prevent clicks on textarea from bubbling
        descEdit.addEventListener("click", (e) => {
            e.stopPropagation();
        });

        applyState();

        return {
            root: wrap,
            descriptionTextarea: descEdit,
            getDescription() {
                return descEdit.value;
            },
            setDescription(text) {
                descEdit.value = text || '';
                descDisplay.textContent = text || "Click to edit explanation...";
                lastSavedDescription = text || '';
            }
        };
    }

    // Public API for Explain Checkbox
    const ExplainCheckboxAPI = {
        renderList(containerId, items = []) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return []; }
            const grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
            host.appendChild(grid);
            const ctrls = [];
            for (const cfg of items) {
                const card = createExplainCheckboxCard(cfg || {});
                grid.appendChild(card.root);
                ctrls.push(card);
            }
            return ctrls;
        },

        add(containerId, item = {}) {
            const host = document.getElementById(containerId);
            if (!host) { console.warn("Container not found:", containerId); return null; }
            let grid = host.lastElementChild;
            const isGrid = grid && grid.style && grid.style.display === "grid";
            if (!isGrid) {
                grid = el("div", { maxWidth: "780px", margin: "0 auto", display: "grid", gap: "16px" });
                host.appendChild(grid);
            }
            const card = createExplainCheckboxCard(item);
            grid.appendChild(card.root);
            return card;
        },

        clear(containerId) {
            const host = document.getElementById(containerId);
            if (host) host.innerHTML = "";
        }
    };

    window.BigExplainCheckbox = ExplainCheckboxAPI;
    window.BigExplainCheckbox.__version__ = REQUIRED_VERSION;
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

/** Render integer input list safely once the container exists. */
function renderBigIntegerListSafe(idOrSelector, items, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigCircleInteger.renderList(cid, items);
        })
        .catch(function (err) { console.warn(err.message); return []; });
}

/** Add a single integer input safely once the container exists. */
function addBigIntegerSafe(idOrSelector, item, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigCircleInteger.add(cid, item);
        })
        .catch(function (err) { console.warn(err.message); return null; });
}

/** Render network input list safely once the container exists. */
function renderBigNetworkListSafe(idOrSelector, items, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigNetworkField.renderList(cid, items);
        })
        .catch(function (err) { console.warn(err.message); return []; });
}

/** Add a single network input safely once the container exists. */
function addBigNetworkSafe(idOrSelector, item, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigNetworkField.add(cid, item);
        })
        .catch(function (err) { console.warn(err.message); return null; });
}

/** Render domain input list safely once the container exists. */
function renderBigDomainListSafe(idOrSelector, items, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigDomainField.renderList(cid, items);
        })
        .catch(function (err) { console.warn(err.message); return []; });
}

/** Add a single domain input safely once the container exists. */
function addBigDomainSafe(idOrSelector, item, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigDomainField.add(cid, item);
        })
        .catch(function (err) { console.warn(err.message); return null; });
}

/** Render URI input list safely once the container exists. */
function renderBigUriListSafe(idOrSelector, items, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigUriField.renderList(cid, items);
        })
        .catch(function (err) { console.warn(err.message); return []; });
}

/** Add a single URI input safely once the container exists. */
function addBigUriSafe(idOrSelector, item, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigUriField.add(cid, item);
        })
        .catch(function (err) { console.warn(err.message); return null; });
}

/** Render explain checkbox list safely once the container exists. */
function renderBigExplainCheckboxListSafe(idOrSelector, items, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigExplainCheckbox.renderList(cid, items);
        })
        .catch(function (err) { console.warn(err.message); return []; });
}

/** Add a single explain checkbox safely once the container exists. */
function addBigExplainCheckboxSafe(idOrSelector, item, root = document) {
    return waitForContainer(idOrSelector, 5000, root)
        .then(function () {
            var cid = (idOrSelector && idOrSelector.nodeType === 1)
                ? idOrSelector.id
                : String(idOrSelector).replace(/^#/, '');
            return BigExplainCheckbox.add(cid, item);
        })
        .catch(function (err) { console.warn(err.message); return null; });
}
