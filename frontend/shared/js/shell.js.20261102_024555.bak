const SYSTEM_NAME = "Bridge";

function readable(code) {
    const text = code.toLowerCase().replaceAll("_", " ");
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function element(tag, className, text) {
    const node = document.createElement(tag);
    node.className = className;

    if (text !== undefined) {
        node.textContent = text;
    }

    return node;
}

function renderDetailList(list, rows) {
    list.replaceChildren(
        ...rows.flatMap(([label, value]) => [
            element("dt", "", label),
            element("dd", "", value)
        ])
    );
}

function groupedOptions(items, groupName, optionFor) {
    const groups = new Map();

    items.forEach(item => {
        const name = groupName(item);
        groups.set(name, [...(groups.get(name) ?? []), optionFor(item)]);
    });

    return [...groups].map(([name, options]) => {
        const group = element("optgroup", "");
        group.label = name;
        group.append(...options);
        return group;
    });
}

function isCurrent(path) {
    return new URL(pageUrl(path)).pathname === window.location.pathname;
}

function tabPaths(tab) {
    return tab.path ? [tab.path] : tab.sections.map(section => section.path);
}

function navItem(baseClass, label, path, active) {
    const built = path !== null && isBuilt(path);
    const node = element(built ? "a" : "span", baseClass, label);

    if (built) {
        node.href = pageUrl(path);
    } else {
        node.classList.add("disabled");
        node.title = "Coming soon";
        node.setAttribute("aria-disabled", "true");
    }

    if (active) {
        node.classList.add("active");
        node.setAttribute("aria-current", "page");
    }

    return node;
}

async function signOut() {
    try {
        await postJson("auth/logout.php");
    } finally {
        goTo(LOGIN_PATH);
    }
}

function buildUserArea(account) {
    const { user, organization } = account;
    const branch = user.branch_name ? `  |  ${user.branch_name}` : "";

    const text = element("div", "shell-user-text");
    text.append(
        element("span", "shell-user-name", `${user.first_name} ${user.last_name}`),
        element(
            "span",
            "shell-user-meta",
            (["BRANCH_ADMIN", "STAFF"].includes(user.role) && user.branch_name) ? user.branch_name : `${readable(user.role)}  |  ${organization.organization_name}${branch}`
        )
    );

    let profileLink = null;

    if (["ADMIN", "BRANCH_ADMIN", "STAFF"].includes(user.role)) {
        const profilePath = "modules/admin/organisation-profile/organisation-profile.html";

        profileLink = element("a", "shell-organization-profile");
        profileLink.href = pageUrl(profilePath);
        profileLink.title = "Organisation profile";
        profileLink.setAttribute("aria-label", "Open organisation profile");

        const initials = String(organization.organization_name || "?")
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(word => word.charAt(0).toUpperCase())
            .join("") || "?";

        const fallback = element("span", "shell-organization-avatar-fallback", initials);
        const logoPath = organization.logo_path || "";
        const validLogo = /^uploads\/organization-logos\/org-\d+-[a-f0-9]{32}\.(jpg|png|webp)$/i.test(logoPath);

        if (validLogo) {
            const image = element("img", "shell-organization-avatar-image");
            image.alt = "";
            image.src = new URL(
                "../../../../" + logoPath,
                pageUrl(profilePath)
            ).href + "?v=" + encodeURIComponent(logoPath);

            image.addEventListener("error", () => {
                profileLink.replaceChildren(fallback);
            }, { once: true });

            profileLink.append(image);
        } else {
            profileLink.append(fallback);
        }
    }

    const area = element("div", "shell-user");
    // Organisation profile icon removed from top navigation.

    return area;
}

function buildSessionFooter(account) {
    const { user, organization } = account;
    const name = `${user.first_name || ""} ${user.last_name || ""}`.trim() || "User";
    const organisation = organization.organization_name || "Organisation";

    const footer = element("footer", "shell-status-footer");
    footer.setAttribute("aria-label", "Current signed-in session");

    const identity = element("div", "shell-status-identity");
    identity.append(
        element("span", "shell-status-details", `Signed in as ${name} at ${organisation}`)
    );

    const clock = element("div", "shell-status-time");
    clock.style.cssText = "display:flex;flex-direction:column;align-items:flex-end;gap:2px;line-height:1.25;";

    const date = element("span", "shell-status-date");
    date.style.fontSize = "11px";

    const time = element("time", "shell-status-clock");
    time.style.fontSize = "13px";

    function updateClock() {
        const now = new Date();

        date.textContent = new Intl.DateTimeFormat("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            timeZone: "Africa/Nairobi"
        }).format(now);

        time.dateTime = now.toISOString();
        time.textContent = new Intl.DateTimeFormat("en-GB", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: false,
            timeZone: "Africa/Nairobi"
        }).format(now);
    }

    updateClock();
    window.setInterval(updateClock, 1000);

    clock.append(date, time);
    footer.append(identity, clock);

    return footer;
}
function renderShell(account) {
    window.__nexoraAccount = account;

    const tabs = tabsFor(account);
    const activeTab = tabs.find(tab =>
        tabPaths(tab).some(isCurrent)
    );

    const tabBar = element("nav", "shell-tabs");

    tabBar.append(
        ...tabs.map(tab => {
            const item = navItem(
                "shell-tab",
                tab.label,
                tabTarget(tab),
                tab === activeTab
            );

            if (tab.id === "profile") {
                item.classList.add("shell-tab-profile");
            }

            return item;
        })
    );

    const mainRow = element("div", "shell-main-row");
    mainRow.append(tabBar, buildUserArea(account));

    const sectionBar = element("nav", "shell-sections");

    sectionBar.append(
        ...(activeTab?.sections ?? []).map(section =>
            navItem(
                "shell-section",
                section.label,
                section.path,
                isCurrent(section.path)
            )
        )
    );

    const shell = element("div", "shell");
    shell.append(mainRow, sectionBar);
    document.getElementById("shell").replaceChildren(shell);
    document.querySelectorAll(".shell-status-footer").forEach(footer => footer.remove());
    document.body.append(buildSessionFooter(account));

    document.body.append(
        element(
            "p",
            "desktop-notice",
            "This system is designed for desktop and laptop screens. Please open it on a larger device."
        )
    );
}

function showShellError(message) {
    document.getElementById("shell").replaceChildren(
        element("p", "form-message error", message)
    );
}

async function refreshNotificationIndicator() {
    const button = document.querySelector(".chainbridge-utility-notifications");
    const dot = button?.querySelector(".chainbridge-notification-dot");

    if (!dot || document.visibilityState !== "visible") return;

    try {
        const result = await getJson("messages/notifications-list.php");

        if (!result.ok || result.data.success !== true) return;

        const unread = Number(result.data.unread_count) > 0;
        dot.hidden = !unread;

        button.setAttribute(
            "aria-label",
            unread ? "Notifications, unread items" : "Notifications"
        );

        button.title = unread ? "Notifications, unread items" : "Notifications";
    } catch (error) {
    }
}

async function initialiseShell() {
    try {
        const { data } = await getJson("auth/session.php");

        if (data.authenticated !== true) {
            goTo(LOGIN_PATH);
            return null;
        }

        renderShell(data);
        document.body.classList.add("shell-ready");

        void refreshNotificationIndicator();
        window.setInterval(() => {
            void refreshNotificationIndicator();
        }, 5000);

        return data;
    } catch (error) {
        showShellError(`Unable to load the page: ${error.message}`);
        return null;
    }
}

const shellReady = initialiseShell();

