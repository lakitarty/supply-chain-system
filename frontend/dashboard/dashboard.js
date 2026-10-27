const dashboardCardGrid = field("dashboardCardGrid");
const dashboardMessage = field("dashboardMessage");

function createElement(tag, className, text) {
    const node = document.createElement(tag);

    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = String(text);

    return node;
}

function formatCardDate(value) {
    if (!value) return "";

    const normalised = String(value).replace(" ", "T");
    const date = new Date(normalised);

    if (Number.isNaN(date.getTime())) return "";

    return new Intl.DateTimeFormat("en-GB", {
        dateStyle: "medium",
        timeStyle: "short",
        timeZone: "Africa/Nairobi"
    }).format(date);
}

function makePageLink(path, label, className = "dashboard-row-action") {
    if (!path) return null;

    const link = createElement("a", className, label);
    link.href = pageUrl(path);

    return link;
}

function renderDashboardHeader() {
    const header = createElement("header", "dashboard-command-header");
    const title = createElement(
        "h1",
        "dashboard-command-title",
        "Supply Chain Command Center"
    );

    header.append(title);

    const organization = window.__nexoraAccount?.organization?.organization_name;
    if (organization) {
        header.append(
            createElement("div", "dashboard-organization-label", organization)
        );
    }

    return header;
}
function renderMetrics(summary) {
    const strip = createElement("section", "dashboard-metric-strip");
    strip.setAttribute("aria-label", "Supply network summary");

    const preferredOrder = [
        "Needs your attention",
        "Unread notifications",
        "Active connections",
        "Products available"
    ];

    const metricRoutes = {
        "Unread notifications": "modules/messages/notifications/notifications.html",
        "Active connections": "modules/network/relationships/relationships.html",
        "Products available": "modules/products/catalogue/catalogue.html"
    };

    const metrics = Array.isArray(summary) ? summary : [];
    const ordered = preferredOrder
        .map(label => metrics.find(metric => metric.label === label))
        .filter(Boolean);

    for (const metric of ordered) {
        const labelText = metric.label;
        const numericValue = Number(metric.value ?? 0);
        const valueText = metric.value ?? 0;
        const isAttention = labelText === "Needs your attention";

        let item;

        if (isAttention) {
            item = createElement(
                "button",
                "dashboard-metric dashboard-metric-link dashboard-metric-attention"
            );

            item.type = "button";
            item.setAttribute(
                "aria-label",
                `Needs your attention: ${valueText}. Focus the Action Queue.`
            );

            item.addEventListener("click", () => {
                const target = document.querySelector(".dashboard-attention-panel");

                if (!target) return;

                target.setAttribute("tabindex", "-1");
                target.scrollIntoView({
                    behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches
                        ? "auto"
                        : "smooth",
                    block: "nearest"
                });
                target.focus({ preventScroll: true });
            });
        } else if (metricRoutes[labelText]) {
            item = createElement("a", "dashboard-metric dashboard-metric-link");
            item.href = pageUrl(metricRoutes[labelText]);
            item.setAttribute(
                "aria-label",
                `${labelText}: ${valueText}. Open ${labelText}.`
            );
        } else {
            item = createElement("div", "dashboard-metric");
        }

        const label = createElement("span", "dashboard-metric-label", labelText);
        const value = createElement("strong", "dashboard-metric-value", valueText);

        if (isAttention && Number.isFinite(numericValue) && numericValue > 0) {
            item.classList.add("has-attention");
        }

        item.append(label, value);
        strip.append(item);
    }

    if (ordered.length === 0) {
        strip.append(
            createElement(
                "p",
                "dashboard-muted-message",
                "Summary information is not available."
            )
        );
    }

    return strip;
}

function renderActionRow(item) {
    const row = createElement("article", "dashboard-action-row");
    const copy = createElement("div", "dashboard-action-copy");
    const title = createElement("h3", "dashboard-action-title", item.title || "Update");
    const detail = createElement("p", "dashboard-action-detail", item.detail || "");

    copy.append(title);
    if (item.detail) copy.append(detail);

    const actionLabel = item.action
        || (String(item.title || "").toLowerCase().includes("publish") ? "Review change" : "Open");

    const link = makePageLink(item.path, actionLabel);

    if (link) {
        link.classList.add("dashboard-row-link");
        link.replaceChildren();
        link.setAttribute("aria-label", `${item.title || "Update"}. Open item`);
        row.append(copy, link);
    } else {
        row.append(copy);
    }

    return row;
}

function renderActionGroup(container, title, items, className) {
    if (!items.length) return;

    const group = createElement("section", `dashboard-action-group ${className}`);
    const heading = createElement("div", "dashboard-group-heading");
    const label = createElement("h3", "dashboard-group-title", title);
    const count = createElement("span", "dashboard-group-count", items.length);

    heading.append(label, count);
    group.append(heading);

    const list = createElement("div", "dashboard-action-list");

    for (const item of items) {
        list.append(renderActionRow(item));
    }

    group.append(list);
    container.append(group);
}

function renderAttentionPanel(items) {
    const panel = createElement("section", "dashboard-panel dashboard-attention-panel");
    const header = createElement("header", "dashboard-panel-header");
    const headingCopy = createElement("div", "dashboard-panel-heading-copy");
    const eyebrow = createElement("p", "dashboard-panel-eyebrow", "PENDING ACTIONS");
    const title = createElement("h2", "dashboard-panel-title", "Action queue");

    const needsAttention = items.filter(item =>
        String(item.group || "").toLowerCase() !== "waiting on others"
    );

    const waiting = items.filter(item =>
        String(item.group || "").toLowerCase() === "waiting on others"
    );

    const total = createElement("span", "dashboard-panel-count", needsAttention.length);

    headingCopy.append(eyebrow, title);
    header.append(headingCopy, total);
    panel.append(header);

    const body = createElement("div", "dashboard-panel-body");

    if (needsAttention.length === 0 && waiting.length === 0) {
        const empty = createElement("div", "dashboard-zero-state");
        empty.append(
            createElement("span", "dashboard-zero-mark", "✓"),
            createElement("h3", "", "No outstanding actions"),
            createElement("p", "", "There are no items requiring attention in the current dashboard feed.")
        );
        body.append(empty);
    } else {
        renderActionGroup(body, "Needs your attention", needsAttention, "is-attention");
        renderActionGroup(body, "Waiting on others", waiting, "is-waiting");
    }

    panel.append(body);
    return panel;
}

function renderActivityRow(item) {
    const row = createElement("article", "dashboard-activity-row");
    const marker = createElement("span", "dashboard-activity-marker");
    const copy = createElement("div", "dashboard-activity-copy");
    const title = createElement("h3", "dashboard-activity-title", item.title || "Recent activity");
    const detail = createElement("p", "dashboard-activity-detail", item.detail || "");
    const dateText = formatCardDate(item.date);

    copy.append(title);
    if (item.detail) copy.append(detail);
    if (dateText) {
        copy.append(createElement("time", "dashboard-activity-date", dateText));
    }

    const link = makePageLink(item.path, "View");

    if (link) {
        link.classList.add("dashboard-row-link");
        link.replaceChildren();
        link.setAttribute("aria-label", `${item.title || "Recent activity"}. Open activity`);
        row.append(marker, copy, link);
    } else {
        row.append(marker, copy);
    }

    return row;
}

function renderActivityPanel(activity) {
    const panel = createElement("section", "dashboard-panel dashboard-activity-panel");
    const header = createElement("header", "dashboard-panel-header");
    const headingCopy = createElement("div", "dashboard-panel-heading-copy");

    headingCopy.append(
        createElement("p", "dashboard-panel-eyebrow", "RECENTS"),
        createElement("h2", "dashboard-panel-title", "Recent activity")
    );

    header.append(headingCopy);
    panel.append(header);

    const list = createElement("div", "dashboard-activity-list");

    if (!activity.length) {
        list.append(
            createElement("p", "dashboard-empty", "Recent supply-network activity will appear here.")
        );
    } else {
        for (const item of activity) {
            list.append(renderActivityRow(item));
        }
    }

    panel.append(list);
    return panel;
}

function renderDashboard(data) {
    dashboardCardGrid.replaceChildren();

    const summary = Array.isArray(data.summary) ? data.summary : [];
    const items = Array.isArray(data.items) ? data.items : [];
    const activity = Array.isArray(data.activity) ? data.activity : [];

    const root = createElement("div", "dashboard-command-center");
    const workspace = createElement("div", "dashboard-command-workspace");

    root.append(
        renderDashboardHeader(),
        renderMetrics(summary)
    );

    workspace.append(
        renderAttentionPanel(items),
        renderActivityPanel(activity)
    );

    root.append(workspace);
    dashboardCardGrid.append(root);

    dashboardCardGrid.setAttribute("aria-busy", "false");
}

async function loadDashboard() {
    dashboardCardGrid.setAttribute("aria-busy", "true");

    try {
        const { ok, data } = await getJson("dashboard/overview.php");

        if (!ok || !data || data.success !== true) {
            throw new Error(data?.error || "Unable to load the dashboard.");
        }

        dashboardMessage.replaceChildren();
        dashboardMessage.className = "form-message";
        renderDashboard(data);
    } catch (error) {
        console.error("Unable to load ChainBridge dashboard:", error);
        dashboardCardGrid.replaceChildren();
        dashboardCardGrid.append(
            createElement(
                "p",
                "dashboard-empty dashboard-load-error",
                "Dashboard information could not be loaded. Check your connection and try again."
            )
        );
        dashboardCardGrid.setAttribute("aria-busy", "false");

        showMessage(
            dashboardMessage,
            error.message || "Unable to load dashboard information.",
            "error"
        );
    }
}

async function checkNegativeStockWarning() {
    try {
        const { ok, data } = await getJson("stock/negative-stock-count.php");
        const count = Number(data?.negative_count || 0);

        const existing = dashboardCardGrid.parentNode?.querySelector(
            ".dashboard-alert-warning"
        );
        existing?.remove();

        if (!ok || !data?.success || !Number.isFinite(count) || count <= 0) {
            return;
        }

        const warning = createElement("div", "dashboard-alert-warning");
        const heading = createElement("strong", "", "Stock accuracy alert");
        const message = createElement(
            "span",
            "",
            `${count} product${count === 1 ? "" : "s"} have negative stock balances.`
        );
        const link = makePageLink(
            "modules/stock/stock/stock.html",
            "Review stock"
        );

        warning.append(heading, message);
        if (link) warning.append(link);

        dashboardCardGrid.parentNode?.insertBefore(warning, dashboardCardGrid);
    } catch (error) {
        console.error("Unable to check negative stock:", error);
    }
}

shellReady.then(account => {
    if (account) {
        loadDashboard();
        checkNegativeStockWarning();
    } else {
        dashboardCardGrid.setAttribute("aria-busy", "false");
    }
});