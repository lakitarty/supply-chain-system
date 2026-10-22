const SYSTEM_NAME = "Supply Chain Management System";

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
        ...rows.flatMap(([label, value]) => [element("dt", "", label), element("dd", "", value)])
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
        element("span", "shell-user-meta", `${readable(user.role)}  |  ${organization.organization_name}${branch}`)
    );

    let profileLink = null;

    if (user.role === "ADMIN") {
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
    area.append(text, ...(profileLink ? [profileLink] : []));

    return area;
}

function renderShell(account) {
    const tabs = tabsFor(account);
    const activeTab = tabs.find(tab => tabPaths(tab).some(isCurrent));

    const tabBar = element("nav", "shell-tabs");
    tabBar.append(
        ...tabs.map(tab => navItem("shell-tab", tab.label, tabTarget(tab), tab === activeTab))
    );

    const mainRow = element("div", "shell-main-row");
    mainRow.append(
        element("div", "shell-brand", SYSTEM_NAME),
        buildUserArea(account)
    );

    const primaryRow = element("div", "shell-primary-row");
    primaryRow.append(tabBar);

    const sectionBar = element("nav", "shell-sections");
    sectionBar.append(
        ...(activeTab?.sections ?? []).map(section =>
            navItem("shell-section", section.label, section.path, isCurrent(section.path))
        )
    );

    const shell = element("div", "shell");
    shell.append(mainRow, primaryRow, sectionBar);

    document.getElementById("shell").replaceChildren(shell);
    document.body.append(
        element("p", "desktop-notice", "This system is designed for desktop and laptop screens. Please open it on a larger device.")
    );
}

function showShellError(message) {
    document.getElementById("shell").replaceChildren(element("p", "form-message error", message));
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

        return data;
    } catch (error) {
        showShellError(`Unable to load the page: ${error.message}`);
        return null;
    }
}

const shellReady = initialiseShell();