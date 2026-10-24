const dashboardCardGrid = field("dashboardCardGrid");
const dashboardMessage = field("dashboardMessage");

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

function formatCardDate(value) {
    if (!value) {
        return "";
    }

    const normalised = String(value).replace(" ", "T");
    const date = new Date(normalised);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    return new Intl.DateTimeFormat("en-GB", {
        dateStyle: "medium",
        timeStyle: "short",
        timeZone: "Africa/Nairobi"
    }).format(date);
}

function renderDashboardCards(cards) {
    dashboardCardGrid.replaceChildren();

    if (cards.length === 0) {
        dashboardCardGrid.append(
            createElement(
                "p",
                "dashboard-empty",
                "You're all caught up. New requests and updates will appear here."
            )
        );
        dashboardCardGrid.setAttribute("aria-busy", "false");
        return;
    }

    const permittedKinds = new Set([
        "connection",
        "notification",
        "attention",
        "announcement",
        "product",
        "communication"
    ]);

    for (const item of cards) {
        const kind = permittedKinds.has(item.kind) ? item.kind : "product";
        const card = createElement("article", "dashboard-card");
        card.dataset.kind = kind;

        card.append(
            createElement(
                "div",
                "dashboard-card-label",
                item.category || "Update"
            ),
            createElement(
                "div",
                "dashboard-card-title",
                item.title || "Update"
            ),
            createElement(
                "p",
                "dashboard-card-detail",
                item.detail || ""
            )
        );

        const dateText = formatCardDate(item.date);

        if (dateText) {
            const date = createElement("time", "dashboard-card-date", dateText);
            date.dateTime = String(item.date);
            card.append(date);
        }

        if (item.path) {
            const action = createElement(
                "a",
                "dashboard-card-action",
                item.action || "Open details"
            );
            action.href = pageUrl(item.path);
            card.append(action);
        }

        dashboardCardGrid.append(card);
    }

    dashboardCardGrid.setAttribute("aria-busy", "false");
}

async function loadDashboard() {
    dashboardCardGrid.setAttribute("aria-busy", "true");

    try {
        const { ok, data } = await getJson("dashboard/overview.php");

        if (!ok) {
            dashboardCardGrid.replaceChildren();
            dashboardCardGrid.setAttribute("aria-busy", "false");

            showMessage(
                dashboardMessage,
                data?.error || "Unable to load the dashboard.",
                "error"
            );
            return;
        }

        const cards = Array.isArray(data.cards) ? data.cards : [];
        dashboardMessage.replaceChildren();
        renderDashboardCards(cards);
    } catch (error) {
        dashboardCardGrid.replaceChildren();
        dashboardCardGrid.setAttribute("aria-busy", "false");

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
    } else {
        dashboardCardGrid.setAttribute("aria-busy", "false");
    }
});