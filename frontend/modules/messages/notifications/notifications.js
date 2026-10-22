"use strict";

const notificationList = document.getElementById("notificationList");
const notificationStatus = document.getElementById("notificationStatus");
const notificationSearch = document.getElementById("notificationSearch");
const notificationFilter = document.getElementById("notificationFilter");
const refreshButton = document.getElementById("refreshNotifications");

let notifications = [];

function element(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
}

function readableType(type) {
    return String(type || "GENERAL")
        .toLowerCase()
        .replaceAll("_", " ")
        .replace(/\b\w/g, letter => letter.toUpperCase());
}

function renderNotifications() {
    notificationList.replaceChildren();

    const term = notificationSearch.value.trim().toLowerCase();
    const filter = notificationFilter.value;

    const filtered = notifications.filter(item => {
        if (filter !== "ALL" && item.status !== filter) return false;

        const text = `${item.title || ""} ${item.message || ""} ${item.notification_type || ""}`.toLowerCase();
        return !term || text.includes(term);
    });

    const unread = notifications.filter(item => item.status === "UNREAD").length;
    notificationStatus.textContent =
        `${filtered.length} shown · ${unread} unread · ${notifications.length} total`;

    if (!filtered.length) {
        notificationList.append(element(
            "p",
            "notification-empty",
            notifications.length
                ? "No notifications match your search or filter."
                : "You have no notifications yet."
        ));
        return;
    }

    for (const item of filtered) {
        const card = element(
            "article",
            `notification-item${item.status === "UNREAD" ? " unread" : ""}`
        );
        const content = element("div");
        content.append(
            element("span", "notification-type", readableType(item.notification_type)),
            element("h2", "", item.title),
            element("p", "", item.message),
            element(
                "div",
                "notification-meta",
                `${item.created_at || "Date unavailable"} · ${item.status === "UNREAD" ? "Unread" : "Read"}`
            )
        );

        const actions = element("div");
        if (item.status === "UNREAD") {
            const button = element("button", "", "Mark as read");
            button.type = "button";
            button.addEventListener("click", () => markAsRead(item, button));
            actions.append(button);
        }

        card.append(content, actions);
        notificationList.append(card);
    }
}

async function loadNotifications() {
    refreshButton.disabled = true;
    notificationStatus.textContent = "Loading notifications...";

    try {
        const result = await getJson("messages/notifications-list.php");
        if (!result.ok || result.data.success !== true) {
            throw new Error(result.data.error || "Unable to load notifications.");
        }

        notifications = Array.isArray(result.data.notifications)
            ? result.data.notifications
            : [];
        renderNotifications();
    } catch (error) {
        notificationStatus.textContent =
            error.message || "Unable to load notifications.";
    } finally {
        refreshButton.disabled = false;
    }
}

async function markAsRead(item, button) {
    if (button.disabled) return;

    button.disabled = true;
    button.textContent = "Saving...";

    try {
        const result = await postJson("messages/notifications-read.php", {
            notificationId: Number(item.notification_id)
        });

        if (!result.ok || result.data.success !== true) {
            throw new Error(result.data.error || "Unable to update notification.");
        }

        item.status = "READ";
        item.read_at = item.read_at || new Date().toISOString().slice(0, 19).replace("T", " ");
        renderNotifications();
    } catch (error) {
        button.disabled = false;
        button.textContent = "Mark as read";
        notificationStatus.textContent =
            error.message || "Unable to update notification.";
    }
}

async function initialiseNotifications() {
    try {
        const account = await shellReady;
        if (!account) return;

        notificationSearch.addEventListener("input", renderNotifications);
        notificationFilter.addEventListener("change", renderNotifications);
        refreshButton.addEventListener("click", loadNotifications);

        await loadNotifications();
    } catch (error) {
        notificationStatus.textContent =
            error.message || "Unable to initialise notifications.";
    }
}

initialiseNotifications();
