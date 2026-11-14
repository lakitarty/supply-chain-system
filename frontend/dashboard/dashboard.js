const dashboardCardGrid = field("dashboardCardGrid");
const dashboardMessage = field("dashboardMessage");


function createElement(tag, className, text) {
    const node = document.createElement(tag);

    if (className) node.className = className;
    if (text !== undefined && text !== null) {
        node.textContent = String(text);
    }

    return node;
}

function formatCardDate(value) {
    if (!value) return "";

    const date = new Date(String(value).replace(" ", "T"));

    if (Number.isNaN(date.getTime())) return "";

    return new Intl.DateTimeFormat("en-GB", {
        dateStyle: "medium",
        timeStyle: "short",
        timeZone: "Africa/Nairobi"
    }).format(date);
}

function dateValue(value) {
    if (!value) return 0;

    const date = new Date(String(value).replace(" ", "T"));
    return Number.isNaN(date.getTime()) ? 0 : date.getTime();
}

function moneyLabel(value) {
    const amount = Number(value || 0);

    if (!Number.isFinite(amount)) return "KSh 0";

    return `KSh ${new Intl.NumberFormat("en-KE", {
        maximumFractionDigits: 0
    }).format(amount)}`;
}

function quantityLabel(value) {
    return new Intl.NumberFormat("en-KE", {
        maximumFractionDigits: 0
    }).format(Number(value || 0));
}

function statusLabel(value) {
    return String(value || "UNKNOWN")
        .replaceAll("_", " ")
        .toLowerCase()
        .replace(/\b\w/g, character => character.toUpperCase());
}

function dashboardUrl(path, parameters = {}) {
    const url = new URL(pageUrl(path));

    for (const [key, value] of Object.entries(parameters)) {
        if (value !== undefined && value !== null && value !== "") {
            url.searchParams.set(key, String(value));
        }
    }

    return url.href;
}

function makePageLink(path, label, className = "dashboard-row-action") {
    if (!path) return null;

    const link = createElement("a", className, label);
    link.href = pageUrl(path);

    return link;
}

function imageSource(path) {
    if (!path) return "";

    const value = String(path).trim();

    if (/^(https?:\/\/|data:|\/)/i.test(value)) {
        return value;
    }

    return new URL(value, pageUrl("")).href;
}

async function optionalDashboardRequest(path) {
    try {
        const response = await getJson(path);

        if (!response.ok || response.data?.success !== true) {
            console.warn(`Dashboard data unavailable: ${path}`);
            return null;
        }

        return response.data;
    } catch (error) {
        console.warn(`Dashboard request failed: ${path}`, error);
        return null;
    }
}

async function loadDashboardFeeds(account) {
    const organizationType = String(
        account?.organization?.organization_type || ""
    ).toUpperCase();

    const role = String(account?.user?.role || "").toUpperCase();
    const branchId = account?.user?.branch_id || "";

    const calls = {
        conversations: optionalDashboardRequest(
            "messages/direct-conversations.php?action=list"
        ),
        notifications: optionalDashboardRequest(
            "messages/notifications-list.php"
        ),
        announcements: optionalDashboardRequest(
            "announcements/list.php"
        ),
        changes: optionalDashboardRequest(
            "changes/list.php"
        )
    };

    let branchQuery = "";

    if (branchId && role !== "ADMIN") {
        branchQuery = `?branchId=${encodeURIComponent(branchId)}`;
    }

    let orderQuery = "";

    if (organizationType === "RETAILER" && branchQuery) {
        orderQuery = branchQuery;
    }

    calls.orders = optionalDashboardRequest(
        `purchasing/orders-list.php${orderQuery}`
    );

    if (organizationType === "RETAILER") {
        const salesQuery = new URLSearchParams({
            period: "LAST_7_DAYS"
        });

        if (branchId && role !== "ADMIN") {
            salesQuery.set("branchId", String(branchId));
        }

        calls.sales = optionalDashboardRequest(
            `stock/sales-report.php?${salesQuery.toString()}`
        );

        calls.receivingLpos = optionalDashboardRequest(
            `receiving/lpo-list.php${branchQuery}`
        );

        calls.grns = optionalDashboardRequest(
            `receiving/list.php${branchQuery}`
        );
    }

    const results = await Promise.all(
        Object.entries(calls).map(async ([key, request]) => [
            key,
            await request
        ])
    );

    return Object.fromEntries(results);
}

function makeInboxEntries(overview, feeds) {
    const entries = [];

    const requests = Array.isArray(overview?.items)
        ? overview.items
        : [];

    for (const item of requests) {
        const title = String(item.title || "");
        const searchable = `${title} ${item.group || ""}`.toLowerCase();

        if (!/supply request|relationship request|connection request|invitation/.test(searchable)) {
            continue;
        }

        entries.push({
            kind: "Request",
            title: title || "Organisation request",
            detail: item.detail || "",
            meta: formatCardDate(item.date || item.created_at),
            date: item.date || item.created_at,
            priority: 3,
            path: item.path || "modules/network/relationships/relationships.html"
        });
    }

    const notifications = Array.isArray(feeds.notifications?.notifications)
        ? feeds.notifications.notifications
        : [];

    for (const notification of notifications) {
        const title = String(notification.title || "Notification");
        const type = String(notification.notification_type || "").toUpperCase();

        if (
            type.includes("DIRECT_MESSAGE") ||
            title.toLowerCase().includes("new direct message")
        ) {
            continue;
        }

        const unread = String(notification.status || "").toUpperCase() !== "READ";

        entries.push({
            kind: unread ? "Unread" : "Notification",
            title,
            detail: notification.message || "",
            meta: formatCardDate(notification.created_at),
            date: notification.created_at,
            priority: unread ? 2 : 0,
            path: "modules/messages/notifications/notifications.html"
        });
    }

    const conversations = Array.isArray(feeds.conversations?.conversations)
        ? feeds.conversations.conversations
        : [];

    for (const conversation of conversations) {
        entries.push({
            kind: "Message",
            title: conversation.other_organization_name || "Organisation",
            detail: conversation.last_message || "Open conversation",
            meta: formatCardDate(conversation.last_message_at),
            date: conversation.last_message_at,
            priority: 1,
            path: "modules/messages/conversations/conversations.html"
        });
    }

    return entries
        .sort((a, b) =>
            (b.priority - a.priority) ||
            (dateValue(b.date) - dateValue(a.date))
        )
        .slice(0, 20);
}

function describeChange(change) {
    const details = [];

    if (change.old_barcode || change.new_barcode) {
        details.push(
            `Barcode: ${change.old_barcode || "New"} to ${change.new_barcode || "Not recorded"}`
        );
    }

    if (change.old_pack_size || change.new_pack_size) {
        details.push(
            `Pack size: ${change.old_pack_size || "Not recorded"} to ${change.new_pack_size || "Not recorded"}`
        );
    }

    if (change.old_packaging || change.new_packaging) {
        details.push(
            `Packaging: ${change.old_packaging || "Not recorded"} to ${change.new_packaging || "Not recorded"}`
        );
    }

    if (change.reason) details.push(change.reason);

    return details.join(" · ") || "Published product information update.";
}

function makeMarketEntries(feeds) {
    const entries = [];

    const announcements = Array.isArray(feeds.announcements?.announcements)
        ? feeds.announcements.announcements
        : [];

    for (const announcement of announcements) {
        if (
            announcement.status &&
            String(announcement.status).toUpperCase() !== "PUBLISHED"
        ) {
            continue;
        }

        const published = announcement.published_at || announcement.created_at;

        entries.push({
            kind: "Announcement",
            title: announcement.announcement_title ||
                announcement.product_name ||
                "Manufacturer announcement",
            detail: announcement.announcement_message ||
                (announcement.product_name
                    ? `Product update: ${announcement.product_name}`
                    : ""),
            meta: [
                announcement.manufacturer_name,
                formatCardDate(published)
            ].filter(Boolean).join(" · "),
            date: published,
            image: announcement.image_path || "",
            path: "modules/messages/announcements/announcements.html"
        });
    }

    const changes = Array.isArray(feeds.changes?.changes)
        ? feeds.changes.changes
        : [];

    for (const change of changes) {
        if (String(change.status || "").toUpperCase() !== "PUBLISHED") {
            continue;
        }

        const published = change.published_at || change.created_at;

        entries.push({
            kind: statusLabel(change.change_type),
            title: change.product_name || "Product change",
            detail: describeChange(change),
            meta: [
                change.manufacturer_name,
                formatCardDate(published)
            ].filter(Boolean).join(" · "),
            date: published,
            path: "modules/products/updates/updates.html"
        });
    }

    return entries
        .sort((a, b) => dateValue(b.date) - dateValue(a.date))
        .slice(0, 20);
}

function makeOrderEntry(order, organizationType) {
    const direction = String(order.order_direction || "").toUpperCase();

    let partner = "";
    let relationship = "";

    if (organizationType === "RETAILER") {
        partner = order.supplier_name || "Supplier";
        relationship = "To";
    } else if (
        ["SUPPLIER", "MANUFACTURER_SUPPLIER"].includes(organizationType) &&
        direction === "SUPPLIER_TO_MANUFACTURER"
    ) {
        partner = order.supplier_name || "Manufacturer";
        relationship = "To";
    } else {
        partner = order.retailer_name || "Supply partner";
        relationship = "From";
    }

    const orderId = Number(order.supply_order_id);

    return {
        kind: statusLabel(order.status),
        title: `LPO ${order.lpo_number || orderId}`,
        detail: [
            `${relationship} ${partner}`,
            `${quantityLabel(order.item_count)} product lines`,
            `${quantityLabel(order.quantity_ordered)} units`
        ].join(" · "),
        meta: formatCardDate(order.order_date),
        date: order.order_date,
        path: "modules/purchasing/orders/orders.html",
        parameters: orderId > 0 ? { lpo: orderId } : {}
    };
}

function makeReceivingEntries(feeds, mode = "all") {
    const entries = [];

    if (mode !== "received") {
        const outstandingOrders = Array.isArray(feeds.receivingLpos?.orders)
            ? feeds.receivingLpos.orders
            : [];

        for (const order of outstandingOrders) {
            const items = Array.isArray(order.items) ? order.items : [];
            const outstanding = items.reduce(
                (total, item) => total + Number(item.outstanding_quantity || 0),
                0
            );

            entries.push({
                kind: "Awaiting receipt",
                title: `LPO ${order.lpo_number || order.supply_order_id}`,
                detail: [
                    order.supplier_name || "Supplier",
                    `${quantityLabel(outstanding)} units outstanding`,
                    `${quantityLabel(items.length)} products`
                ].join(" · "),
                meta: order.expected_delivery_date
                    ? `Expected ${formatCardDate(order.expected_delivery_date)}`
                    : `Ordered ${formatCardDate(order.order_date)}`,
                date: order.expected_delivery_date || order.order_date,
                path: "modules/purchasing/orders/orders.html",
                parameters: { view: "receiving" }
            });
        }
    }

    if (mode !== "outstanding") {
        const grns = Array.isArray(feeds.grns?.grns)
            ? feeds.grns.grns
            : [];

        for (const grn of grns) {
            entries.push({
                kind: statusLabel(grn.reconciliation_status || grn.status || "Received"),
                title: `GRN ${grn.grn_number || grn.grn_id}`,
                detail: [
                    grn.supplier_name || "Supplier",
                    `${quantityLabel(grn.accepted_quantity)} accepted`,
                    `${quantityLabel(grn.rejected_quantity)} rejected`
                ].join(" · "),
                meta: formatCardDate(grn.received_date || grn.created_at),
                date: grn.received_date || grn.created_at,
                path: "modules/purchasing/orders/orders.html",
                parameters: {
                    view: "receiving",
                    receiving: "register"
                }
            });
        }
    }

    return entries.sort(
        (a, b) => dateValue(b.date) - dateValue(a.date)
    );
}

function createRegion(title, className) {
    const region = createElement("section", `dashboard-region ${className}`);
    const heading = createElement("div", "dashboard-region-heading");

    heading.append(
        createElement("span", "dashboard-region-label", title)
    );

    const body = createElement("div", "dashboard-region-body");
    region.append(heading, body);

    return { region, body };
}

function renderPreviewItem(entry) {
    const item = createElement(
        entry.path ? "a" : "article",
        "dashboard-record"
    );

    if (entry.path) {
        item.href = dashboardUrl(entry.path, entry.parameters || {});
        item.setAttribute(
            "aria-label",
            `${entry.title || "Dashboard record"}. Open details.`
        );
    }

    if (entry.image) {
        const frame = createElement("div", "dashboard-record-image-frame");
        const image = document.createElement("img");

        image.className = "dashboard-record-image";
        image.alt = "";
        image.loading = "lazy";
        image.src = imageSource(entry.image);

        image.addEventListener("error", () => frame.remove(), { once: true });
        frame.append(image);
        item.append(frame);
    }

    const copy = createElement("div", "dashboard-record-copy");

    if (entry.kind) {
        copy.append(
            createElement("span", "dashboard-record-kind", entry.kind)
        );
    }

    copy.append(
        createElement(
            "div",
            "dashboard-record-title",
            entry.title || "Update"
        )
    );

    if (entry.detail) {
        copy.append(
            createElement("p", "dashboard-record-detail", entry.detail)
        );
    }

    if (entry.meta) {
        copy.append(
            createElement("p", "dashboard-record-meta", entry.meta)
        );
    }

    item.append(copy);
    return item;
}

function renderEntryStack(container, entries, emptyText, maxRows = 5) {
    const stack = createElement("div", "dashboard-record-stack");
    const records = Array.isArray(entries) ? entries : [];

    if (!records.length) {
        stack.append(createElement("p", "dashboard-empty", emptyText));
    } else {
        stack.append(
            ...records.slice(0, maxRows).map(renderPreviewItem)
        );
    }

    container.append(stack);
    return stack;
}

function renderRecordList(
    container,
    title,
    entries,
    emptyText,
    viewAllPath = "",
    maxRows = 5,
    viewAllParameters = {}
) {
    const section = createElement("section", "dashboard-record-list");
    const heading = createElement("div", "dashboard-list-heading");

    heading.append(
        createElement("span", "dashboard-list-title", title)
    );

    if (viewAllPath) {
        const link = createElement("a", "dashboard-view-all", "See all");
        link.href = dashboardUrl(viewAllPath, viewAllParameters);
        heading.append(link);
    }

    section.append(heading);
    renderEntryStack(section, entries, emptyText, maxRows);
    container.append(section);

    return section;
}

function renderActivityCue(container, label, value, detail, path, parameters = {}) {
    const cue = createElement("a", "dashboard-activity-cue");
    cue.href = dashboardUrl(path, parameters);

    cue.append(
        createElement("span", "dashboard-cue-label", label),
        createElement("strong", "dashboard-cue-value", value),
        createElement("span", "dashboard-cue-detail", detail || "Open list")
    );

    container.append(cue);
    return cue;
}

function renderQuickLinks(account) {
    const type = String(
        account?.organization?.organization_type || ""
    ).toUpperCase();

    const shortcuts = createElement("nav", "dashboard-shortcuts");
    shortcuts.setAttribute("aria-label", "Quick access");

    const links = [
        {
            label: "Products",
            path: "modules/products/catalogue/catalogue.html"
        },
        {
            label: "Orders",
            path: "modules/purchasing/orders/orders.html"
        },
        {
            label: "Organisations",
            path: "modules/network/relationships/relationships.html"
        },
        {
            label: "Messages",
            path: "modules/messages/conversations/conversations.html"
        }
    ];

    if (type === "RETAILER") {
        links.unshift({
            label: "Sales",
            path: "modules/stock/sales/sales.html"
        });

        links.push({
            label: "Goods receiving",
            path: "modules/purchasing/orders/orders.html",
            parameters: { view: "receiving" }
        });
    }

    for (const item of links) {
        const link = createElement("a", "dashboard-shortcut", item.label);
        link.href = dashboardUrl(item.path, item.parameters || {});
        shortcuts.append(link);
    }

    return shortcuts;
}

function renderTopSalesProducts(container, salesData) {
    const rows = Array.isArray(salesData?.rows)
        ? [...salesData.rows]
        : [];

    const movers = rows
        .filter(row => Number(row.units_sold) > 0)
        .sort((a, b) => Number(b.units_sold) - Number(a.units_sold))
        .slice(0, 3);

    if (!movers.length) return;

    const section = createElement("section", "dashboard-top-products");
    const heading = createElement("div", "dashboard-list-heading");

    heading.append(
        createElement("span", "dashboard-list-title", "Top product movement")
    );

    const list = createElement("div", "dashboard-top-products-list");

    for (const mover of movers) {
        const item = createElement("div", "dashboard-top-product");
        const details = [
            mover.pack_size,
            mover.branch_name
        ].filter(Boolean).join(" / ");

        item.append(
            createElement(
                "span",
                "dashboard-top-product-name",
                mover.product_name || "Product"
            ),
            createElement(
                "strong",
                "dashboard-top-product-quantity",
                `${quantityLabel(mover.units_sold)} units`
            )
        );

        if (details) {
            item.append(
                createElement("span", "dashboard-top-product-detail", details)
            );
        }

        list.append(item);
    }

    section.append(heading, list);
    container.append(section);
}

function renderOperations(account, feeds) {
    const type = String(
        account?.organization?.organization_type || ""
    ).toUpperCase();

    const ordersAvailable = Array.isArray(feeds.orders?.orders);
    const orders = ordersAvailable ? feeds.orders.orders : [];

    const region = createRegion("Operations", "dashboard-operations");
    const activity = createElement("section", "dashboard-activity");
    const cues = createElement("div", "dashboard-cue-grid");

    activity.append(
        createElement("div", "dashboard-activity-heading", "Activities"),
        cues
    );

    region.body.append(activity);

    const grid = createElement("div", "dashboard-operations-grid");

    if (type === "RETAILER") {
        const outstanding = Array.isArray(feeds.receivingLpos?.orders)
            ? feeds.receivingLpos.orders
            : [];

        const grns = Array.isArray(feeds.grns?.grns)
            ? feeds.grns.grns
            : [];

        renderActivityCue(
            cues,
            "Sales / last 7 days",
            feeds.sales?.totals
                ? moneyLabel(feeds.sales.totals.amount_entered)
                : "N/A",
            "Open sales report",
            "modules/stock/sales/sales.html"
        );

        renderActivityCue(
            cues,
            "Purchase orders",
            ordersAvailable ? quantityLabel(orders.length) : "N/A",
            "Open order register",
            "modules/purchasing/orders/orders.html"
        );

        renderActivityCue(
            cues,
            "Awaiting receipt",
            feeds.receivingLpos
                ? quantityLabel(outstanding.length)
                : "N/A",
            "Open goods receiving",
            "modules/purchasing/orders/orders.html",
            { view: "receiving" }
        );

        renderActivityCue(
            cues,
            "Goods received",
            feeds.grns ? quantityLabel(grns.length) : "N/A",
            "Open GRN register",
            "modules/purchasing/orders/orders.html",
            { view: "receiving", receiving: "register" }
        );

        renderTopSalesProducts(region.body, feeds.sales);

        renderRecordList(
            grid,
            "Purchase orders",
            orders.map(order => makeOrderEntry(order, type)),
            ordersAvailable
                ? "No purchase orders recorded."
                : "Order information is unavailable.",
            "modules/purchasing/orders/orders.html",
            5
        );

        renderRecordList(
            grid,
            "Deliveries and receiving",
            makeReceivingEntries(feeds),
            feeds.receivingLpos && feeds.grns
                ? "No outstanding receipts or received goods."
                : "Receiving information is unavailable.",
            "modules/purchasing/orders/orders.html",
            5,
            { view: "receiving" }
        );

        region.body.append(grid);
        return region.region;
    }

    if (["SUPPLIER", "MANUFACTURER_SUPPLIER"].includes(type)) {
        const retailerOrders = orders
            .filter(order =>
                String(order.order_direction || "").toUpperCase() ===
                "RETAILER_TO_SUPPLIER"
            )
            .map(order => makeOrderEntry(order, type));

        const manufacturerOrders = orders
            .filter(order =>
                String(order.order_direction || "").toUpperCase() ===
                "SUPPLIER_TO_MANUFACTURER"
            )
            .map(order => makeOrderEntry(order, type));

        renderActivityCue(
            cues,
            "Retailer orders",
            ordersAvailable ? quantityLabel(retailerOrders.length) : "N/A",
            "Orders received from retailers",
            "modules/purchasing/orders/orders.html"
        );

        renderActivityCue(
            cues,
            "Manufacturer orders",
            ordersAvailable ? quantityLabel(manufacturerOrders.length) : "N/A",
            "Orders placed with manufacturers",
            "modules/purchasing/orders/orders.html"
        );

        renderRecordList(
            grid,
            "Orders from retailers",
            retailerOrders,
            ordersAvailable
                ? "No retailer orders recorded."
                : "Order information is unavailable.",
            "modules/purchasing/orders/orders.html",
            5
        );

        renderRecordList(
            grid,
            "Orders to manufacturers",
            manufacturerOrders,
            ordersAvailable
                ? "No manufacturer orders recorded."
                : "Order information is unavailable.",
            "modules/purchasing/orders/orders.html",
            5
        );

        region.body.append(grid);
        return region.region;
    }

    const incoming = orders.map(order => makeOrderEntry(order, type));

    const awaitingReview = orders
        .filter(order =>
            String(order.status || "").toUpperCase() === "SUBMITTED"
        )
        .map(order => makeOrderEntry(order, type));

    const processed = orders
        .filter(order =>
            String(order.status || "").toUpperCase() !== "SUBMITTED"
        )
        .map(order => makeOrderEntry(order, type));

    renderActivityCue(
        cues,
        "Incoming supplier orders",
        ordersAvailable ? quantityLabel(incoming.length) : "N/A",
        "Orders received from suppliers",
        "modules/purchasing/orders/orders.html"
    );

    renderActivityCue(
        cues,
        "Awaiting review",
        ordersAvailable ? quantityLabel(awaitingReview.length) : "N/A",
        "Orders requiring review",
        "modules/purchasing/orders/orders.html"
    );

    renderRecordList(
        grid,
        "Incoming supplier orders",
        incoming,
        ordersAvailable
            ? "No supplier orders recorded."
            : "Order information is unavailable.",
        "modules/purchasing/orders/orders.html",
        5
    );

    renderRecordList(
        grid,
        "Awaiting review",
        awaitingReview,
        ordersAvailable
            ? "No orders are currently awaiting review."
            : "Review information is unavailable.",
        "modules/purchasing/orders/orders.html",
        5
    );

    renderRecordList(
        grid,
        "Recent order activity",
        processed,
        ordersAvailable
            ? "No processed supplier orders recorded."
            : "Order information is unavailable.",
        "modules/purchasing/orders/orders.html",
        5
    );

    region.body.append(grid);
    return region.region;
}

function renderInbox(overview, feeds) {
    const region = createRegion("Inbox", "dashboard-inbox");

    renderEntryStack(
        region.body,
        makeInboxEntries(overview, feeds),
        "No recent messages or organisation requests.",
        6
    );

    return region.region;
}

function renderMarketPulse(feeds) {
    const region = createRegion("Market Pulse", "dashboard-market-pulse");

    renderEntryStack(
        region.body,
        makeMarketEntries(feeds),
        "No published product updates or announcements.",
        6
    );

    return region.region;
}

function renderDashboard(data, feeds, account) {
    dashboardCardGrid.replaceChildren();

    const root = createElement("div", "dashboard-command-center");
    const context = createElement("div", "dashboard-context-bar");

    context.append(
        createElement(
            "span",
            "dashboard-company-name",
            account?.organization?.organization_name || "ChainBridge"
        )
    );

    const contextDetails = [
        account?.organization?.organization_type
            ? statusLabel(account.organization.organization_type)
            : "",
        account?.user?.branch_name || ""
    ].filter(Boolean).join(" / ");

    if (contextDetails) {
        context.append(
            createElement("span", "dashboard-company-meta", contextDetails)
        );
    }

    const mainColumn = createElement("div", "dashboard-main-column");
    const sideColumn = createElement("aside", "dashboard-side-column");

    mainColumn.append(renderOperations(account, feeds));
    sideColumn.append(
        renderInbox(data, feeds),
        renderMarketPulse(feeds)
    );

    const workspace = createElement("div", "dashboard-workspace");
    workspace.append(mainColumn, sideColumn);

    root.append(
        context,
        renderQuickLinks(account),
        workspace
    );

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

        const account = window.__nexoraAccount || {};
        const feeds = await loadDashboardFeeds(account);

        dashboardMessage.replaceChildren();
        dashboardMessage.className = "form-message";

        renderDashboard(data, feeds, account);
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