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

    const button = element("button", "shell-signout", "Sign out");
    button.type = "button";
    button.addEventListener("click", signOut);

    const area = element("div", "shell-user");
    area.append(text, button);

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
    mainRow.append(element("div", "shell-brand", SYSTEM_NAME), tabBar, buildUserArea(account));

    const sectionBar = element("nav", "shell-sections");
    sectionBar.append(
        ...(activeTab?.sections ?? []).map(section =>
            navItem("shell-section", section.label, section.path, isCurrent(section.path))
        )
    );

    const shell = element("div", "shell");
    shell.append(mainRow, sectionBar);

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