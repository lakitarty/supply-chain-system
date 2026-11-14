(function () {
    if (window.__nexoraPageRouter) {
        return;
    }

    const routerScriptUrl = document.currentScript
        ? new URL(document.currentScript.src)
        : null;
    const routerPath = routerScriptUrl?.pathname ?? "";

    const nativeAdd = EventTarget.prototype.addEventListener;
    const nativeRemove = EventTarget.prototype.removeEventListener;

    let trackPageListeners = false;
    let pageGlobalListeners = [];
    let pageCleanups = [];
    let navigationSequence = 0;
    let moduleSequence = 0;

    const loadedSharedScripts = new Set(
        [...document.querySelectorAll("script[src]")]
            .map(script => normaliseUrl(script.src))
            .filter(url => url.pathname.includes("/shared/js/"))
            .map(url => url.href)
    );

    let activePageStylesheet = [...document.querySelectorAll('link[rel="stylesheet"]')]
        .find(link => !isSharedStylesheet(new URL(link.href)));

    if (activePageStylesheet) {
        activePageStylesheet.dataset.nexoraPageStylesheet = "active";
    }

    for (const link of document.querySelectorAll('link[rel="stylesheet"][href]')) {
        link.href = new URL(link.getAttribute("href"), window.location.href).href;
    }

    function normaliseUrl(value, base = window.location.href) {
        const url = new URL(value, base);
        url.search = "";
        url.hash = "";
        return url;
    }

    function isSharedStylesheet(url) {
        return url.pathname.endsWith("/shared/css/base.css")
            || url.pathname.endsWith("/shared/css/shell.css");
    }

    function isSharedScript(url) {
        return url.pathname.includes("/shared/js/")
            && url.pathname !== routerPath;
    }

    function trackableTarget(target) {
        return target === document || target === window;
    }

    EventTarget.prototype.addEventListener = function (type, listener, options) {
        if (trackPageListeners && trackableTarget(this) && listener) {
            pageGlobalListeners.push({
                target: this,
                type,
                listener,
                options
            });
        }

        return nativeAdd.call(this, type, listener, options);
    };

    function removePageGlobalListeners() {
        trackPageListeners = false;

        for (const item of pageGlobalListeners) {
            nativeRemove.call(
                item.target,
                item.type,
                item.listener,
                item.options
            );
        }

        pageGlobalListeners = [];
        trackPageListeners = true;
    }

    function registerPageCleanup(cleanup) {
        if (typeof cleanup !== "function") {
            throw new TypeError("Page cleanup must be a function.");
        }

        pageCleanups.push(cleanup);

        let registered = true;

        return () => {
            if (!registered) return;

            registered = false;

            const index = pageCleanups.indexOf(cleanup);
            if (index !== -1) {
                pageCleanups.splice(index, 1);
            }
        };
    }

    function disposePageResources() {
        const cleanups = pageCleanups;
        pageCleanups = [];

        for (const cleanup of cleanups) {
            try {
                cleanup();
            } catch (error) {
                console.error("Page resource cleanup failed.", error);
            }
        }
    }
    function builtPathFor(value) {
        const url = new URL(value, window.location.href);
        const root = new URL(frontendRoot);

        if (url.origin !== root.origin) {
            return null;
        }

        const rootPath = root.pathname.endsWith("/")
            ? root.pathname
            : `${root.pathname}/`;

        if (!url.pathname.startsWith(rootPath)) {
            return null;
        }

        let relativePath;

        try {
            relativePath = decodeURIComponent(url.pathname.slice(rootPath.length));
        } catch {
            return null;
        }

        return BUILT_PATHS.includes(relativePath) ? relativePath : null;
    }

    function resourcesFromPage(parsed, pageUrl) {
        const stylesheets = [...parsed.querySelectorAll('link[rel="stylesheet"]')]
            .map(link => new URL(link.getAttribute("href"), pageUrl))
            .filter(url => !isSharedStylesheet(url));

        const scripts = [...parsed.querySelectorAll("script[src]")]
            .map(script => new URL(script.getAttribute("src"), pageUrl))
            .filter(url => url.protocol === "http:" || url.protocol === "https:");

        return {
            stylesheet: stylesheets[0] ?? null,
            sharedScripts: scripts.filter(isSharedScript),
            pageScripts: scripts.filter(url =>
                !isSharedScript(url) && url.pathname !== routerPath
            )
        };
    }

    async function loadSharedScript(url) {
        const key = normaliseUrl(url).href;

        if (loadedSharedScripts.has(key)) {
            return;
        }

        await new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.src = url.href;
            script.async = false;

            script.onload = resolve;
            script.onerror = () => {
                script.remove();
                reject(new Error(`Unable to load shared script: ${url.pathname}`));
            };

            document.head.appendChild(script);
        });

        loadedSharedScripts.add(key);
    }

    async function prepareStylesheet(url) {
        if (!url) {
            return null;
        }

        if (
            activePageStylesheet &&
            normaliseUrl(activePageStylesheet.href).href === normaliseUrl(url).href
        ) {
            return activePageStylesheet;
        }

        const link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = url.href;
        link.dataset.nexoraPageStylesheet = "pending";

        await new Promise((resolve, reject) => {
            link.onload = resolve;
            link.onerror = () => {
                link.remove();
                reject(new Error(`Unable to load page styles: ${url.pathname}`));
            };

            document.head.appendChild(link);
        });

        return link;
    }

    async function runPageScripts(scripts, routeId) {
        for (const source of scripts) {
            const moduleUrl = new URL(source.href);
            const moduleId = `${routeId}-${++moduleSequence}`;

            moduleUrl.searchParams.set("__nexora_route", moduleId);
            moduleUrl.searchParams.set(
                "__nexora_cache",
                `${Date.now()}-${moduleId}`
            );

            await import(moduleUrl.href);
        }
    }

    function clearModalState() {
        for (const className of [...document.body.classList]) {
            if (className.endsWith("modal-open")) {
                document.body.classList.remove(className);
            }
        }
    }

    function syncNavigation(routeUrl) {
        const account = window.__nexoraAccount;

        if (!account || typeof tabsFor !== "function") {
            return;
        }

        const visibleTabs = tabsFor(account);
        const activeTabIndex = visibleTabs.findIndex(tab =>
            tabPaths(tab).some(path =>
                new URL(pageUrl(path)).pathname === routeUrl.pathname
            )
        );

        const tabLinks = [
            ...document.querySelectorAll(".shell-tabs .shell-tab")
        ];

        tabLinks.forEach((link, index) => {
            const active = index === activeTabIndex;

            link.classList.toggle("active", active);

            if (active) {
                link.setAttribute("aria-current", "page");
            } else {
                link.removeAttribute("aria-current");
            }
        });

        const activeTab = activeTabIndex >= 0
            ? visibleTabs[activeTabIndex]
            : null;

        const sectionBar = document.querySelector(".shell-sections");

        if (!sectionBar) {
            return;
        }

        if (routeUrl.pathname.includes("/modules/purchasing/orders/")) {
            return;
        }

        const sections = activeTab?.sections ?? [];

        sectionBar.replaceChildren(
            ...sections.map(section => {
                const active =
                    new URL(pageUrl(section.path)).pathname === routeUrl.pathname;

                return navItem(
                    "shell-section",
                    section.label,
                    section.path,
                    active
                );
            })
        );
    }
    function isPersistentNode(node) {
        return node.id === "shell"
            || node.tagName === "SCRIPT"
            || node.classList?.contains("desktop-notice")
            || node.classList?.contains("shell-status-footer");
    }

    async function renderRoute(value, addHistory) {
        const previousUrl = window.location.href;
        const targetUrl = new URL(value, previousUrl);

        if (!builtPathFor(targetUrl.href)) {
            return false;
        }

        if (
            targetUrl.pathname === window.location.pathname &&
            targetUrl.search === window.location.search &&
            targetUrl.hash === window.location.hash
        ) {
            return true;
        }

        const routeId = ++navigationSequence;
        let incomingStylesheet = null;
        let oldStylesheet = activePageStylesheet;
        const oldStylesheetDisabled = oldStylesheet?.disabled ?? false;
        let oldPageNodes = [];
        let newPageNodes = [];
        let contentChanged = false;

        try {
            const response = await fetch(targetUrl.href, {
                method: "GET",
                credentials: "include",
                cache: "no-store",
                headers: { Accept: "text/html" }
            });

            if (!response.ok) {
                throw new Error(`The page request failed (${response.status}).`);
            }

            const html = await response.text();
            const parsed = new DOMParser().parseFromString(html, "text/html");

            newPageNodes = [...parsed.body.children].filter(
                node => !isPersistentNode(node)
            );

            if (!newPageNodes.some(node => node.matches("main.page"))) {
                throw new Error("The requested page has no main content area.");
            }

            const resources = resourcesFromPage(parsed, targetUrl);

            for (const scriptUrl of resources.sharedScripts) {
                await loadSharedScript(scriptUrl);
            }

            incomingStylesheet = await prepareStylesheet(resources.stylesheet);

            if (routeId !== navigationSequence) {
                if (incomingStylesheet && incomingStylesheet !== oldStylesheet) {
                    incomingStylesheet.remove();
                }
                return false;
            }

            oldPageNodes = [...document.body.children].filter(
                node => !isPersistentNode(node)
            );

            if (!oldPageNodes.some(node => node.matches("main.page"))) {
                throw new Error("The current page content area could not be found.");
            }

            if (oldStylesheet && oldStylesheet !== incomingStylesheet) {
                oldStylesheet.disabled = true;
            }

            disposePageResources();
            removePageGlobalListeners();
            clearModalState();

            oldPageNodes.forEach(node => node.remove());

            const scriptAnchor = [...document.body.children].find(
                node => node.tagName === "SCRIPT"
            ) ?? null;

            for (const node of newPageNodes) {
                document.body.insertBefore(node, scriptAnchor);
            }

            contentChanged = true;
            document.title = parsed.title || document.title;

            if (typeof setupPasswordToggles === "function") {
                setupPasswordToggles();
            }

            if (addHistory) {
                history.pushState({ nexoraPage: true }, "", targetUrl.href);
            }

            await runPageScripts(resources.pageScripts, routeId);

            if (routeId !== navigationSequence) {
                if (addHistory && window.location.href === targetUrl.href) {
                    history.replaceState(history.state, "", previousUrl);
                }
                return false;
            }

            if (oldStylesheet && oldStylesheet !== incomingStylesheet) {
                oldStylesheet.remove();
            }

            activePageStylesheet = incomingStylesheet;

            if (activePageStylesheet) {
                activePageStylesheet.dataset.nexoraPageStylesheet = "active";
            }

            syncNavigation(targetUrl);
            window.scrollTo(0, 0);

            return true;
        } catch (error) {
            if (routeId !== navigationSequence) {
                if (addHistory && window.location.href === targetUrl.href) {
                    history.replaceState(history.state, "", previousUrl);
                }
                if (incomingStylesheet && incomingStylesheet !== oldStylesheet) {
                    incomingStylesheet.remove();
                }
                return false;
            }

            if (addHistory && window.location.href === targetUrl.href) {
                history.replaceState(history.state, "", previousUrl);
            }

            if (contentChanged) {
                newPageNodes.forEach(node => node.remove());

                const scriptAnchor = [...document.body.children].find(
                    node => node.tagName === "SCRIPT"
                ) ?? null;

                for (const node of oldPageNodes) {
                    document.body.insertBefore(node, scriptAnchor);
                }
            }

            if (incomingStylesheet && incomingStylesheet !== oldStylesheet) {
                incomingStylesheet.remove();
            }

            if (oldStylesheet) {
                oldStylesheet.disabled = oldStylesheetDisabled;
                activePageStylesheet = oldStylesheet;
            }

            console.error("Nexora page navigation failed:", error);
            return false;
        }
    }
    function handleDocumentClick(event) {
        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        const target = event.target instanceof Element
            ? event.target
            : event.target?.parentElement;

        const anchor = target?.closest("a[href]");

        if (!anchor || anchor.target || anchor.hasAttribute("download")) {
            return;
        }

        const url = new URL(anchor.href, window.location.href);

        if (url.origin !== window.location.origin || !builtPathFor(url.href)) {
            return;
        }

        event.preventDefault();

        if (
            url.pathname === window.location.pathname &&
            url.search === window.location.search
        ) {
            if (url.hash && url.hash !== window.location.hash) {
                history.pushState({ nexoraPage: true }, "", url.href);
                window.location.hash = url.hash;
            }
            return;
        }

        renderRoute(url.href, true);
    }

    window.__nexoraPageRouter = {
        navigate: value => renderRoute(value, true),
        registerCleanup: registerPageCleanup
    };

    nativeAdd.call(document, "click", handleDocumentClick, true);

    nativeAdd.call(window, "popstate", () => {
        renderRoute(window.location.href, false);
    });

    trackPageListeners = true;
})();