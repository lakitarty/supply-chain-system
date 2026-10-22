const attentionGroups = field("attentionGroups");
const dashboardMessage = field("dashboardMessage");
const notificationNotice = field("notificationNotice");
const notificationTitle = field("notificationTitle");
const notificationText = field("notificationText");
const activityToggle = field("activityToggle");
const activityList = field("activityList");

function createElement(tag, className, text) {
    const node = document.createElement(tag);

    if (className) {
        node.className = className;
    }

    if (text !== undefined && text !== null) {
        node.textContent = String(text);
    }

    return node;
}

function createWorkspaceLink(path, label, className = "dashboard-item-link") {
    const link = createElement("a", className, label);
    link.href = pageUrl(path);
    return link;
}

function renderNotificationNotice(summary) {
    const unreadMetric = summary.find(
        item => item.label === "Unread notifications"
    );
    const unreadCount = Number(unreadMetric?.value ?? 0);

    notificationNotice.hidden = unreadCount < 1;

    if (unreadCount < 1) {
        return;
    }

    notificationTitle.textContent =
        `${unreadCount} unread notification${unreadCount === 1 ? "" : "s"}`;

    notificationText.textContent = "You have notifications to review.";
}

function renderAttention(items) {
    attentionGroups.replaceChildren();

    if (items.length === 0) {
        attentionGroups.append(
            createElement("p", "dashboard-empty", "Nothing needs your attention right now.")
        );
        return;
    }

    const groups = [...new Set(items.map(item => item.group))];

    for (const groupName of groups) {
        const groupItems = items.filter(item => item.group === groupName);
        const group = createElement("section", "dashboard-attention-group");
        const heading = createElement("h3", "dashboard-group-label", groupName);
        const list = createElement("div", "dashboard-attention-list");

        for (const item of groupItems) {
            const entry = createElement("article", "dashboard-attention-item");
            entry.append(
                createElement("h3", "dashboard-item-title", item.title),
                createElement("p", "dashboard-item-detail", item.detail)
            );

            if (item.path) {
                entry.append(createWorkspaceLink(item.path, "Open"));
            }

            list.append(entry);
        }

        group.append(heading, list);
        attentionGroups.append(group);
    }
}

function formatActivityDate(value) {
    if (!value) {
        return "";
    }

    const date = new Date(String(value).replace(" ", "T"));

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        timeStyle: "short"
    }).format(date);
}

function renderActivity(activity) {
    activityList.replaceChildren();

    if (activity.length === 0) {
        activityList.append(
            createElement("p", "dashboard-empty", "No recent updates to show.")
        );
        return;
    }

    for (const item of activity) {
        const entry = createElement("article", "dashboard-activity-item");
        const heading = createElement("h3", "dashboard-item-title");

        if (item.path) {
            heading.append(createWorkspaceLink(item.path, item.title, "dashboard-activity-link"));
        } else {
            heading.textContent = item.title;
        }

        entry.append(heading);

        if (item.detail) {
            entry.append(createElement("p", "dashboard-item-detail", item.detail));
        }

        const dateText = formatActivityDate(item.date);

        if (dateText) {
            entry.append(createElement("p", "dashboard-item-date", dateText));
        }

        activityList.append(entry);
    }
}

activityToggle.addEventListener("click", () => {
    const willOpen = activityList.hidden;
    activityList.hidden = !willOpen;
    activityToggle.setAttribute("aria-expanded", String(willOpen));
    activityToggle.textContent = willOpen ? "Hide recent updates" : "Show recent updates";
});

async function loadDashboard() {
    try {
        const { ok, data } = await getJson("dashboard/overview.php");

        if (!ok) {
            showMessage(
                dashboardMessage,
                data?.error || "Unable to load the dashboard.",
                "error"
            );
            return;
        }

        const summary = Array.isArray(data.summary) ? data.summary : [];
        const items = Array.isArray(data.items) ? data.items : [];
        const activity = Array.isArray(data.activity) ? data.activity : [];

        renderNotificationNotice(summary);
        renderAttention(items);
        renderActivity(activity);
    } catch (error) {
        showMessage(
            dashboardMessage,
            "Unable to load dashboard information. Check your connection and try again.",
            "error"
        );
    }
}

shellReady.then(account => {
    if (account) {
        loadDashboard();
    }
});