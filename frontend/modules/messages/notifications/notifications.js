"use strict";

const notificationList = document.getElementById("notificationList");
const notificationStatus = document.getElementById("notificationStatus");
const notificationSearch = document.getElementById("notificationSearch");
const notificationFilter = document.getElementById("notificationFilter");
const refreshButton = document.getElementById("refreshNotifications");
const previewModal = document.getElementById("notificationPreview");
const previewTitle = document.getElementById("notificationPreviewTitle");
const previewContent = document.getElementById("notificationPreviewContent");
const previewActions = document.getElementById("notificationPreviewActions");
const closePreviewButton = document.getElementById("closeNotificationPreview");

let notifications = [];
let previewTrigger = null;
let selectedNotificationId = null;

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

function getNotificationDate(item) {
    return item.created_at || "Date unavailable";
}

function renderNotifications() {
    notificationList.replaceChildren();

    const term = notificationSearch.value.trim().toLowerCase();
    const filter = notificationFilter.value;

    const filtered = notifications.filter(item => {
        if (filter !== "ALL" && item.status !== filter) return false;

        const searchableText = [
            item.title,
            item.message,
            item.notification_type
        ].filter(Boolean).join(" ").toLowerCase();

        return !term || searchableText.includes(term);
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

    const fragment = document.createDocumentFragment();

    for (const item of filtered) {
        const row = element(
            "button",
            `notification-row${item.status === "UNREAD" ? " unread" : ""}`
        );

        row.type = "button";
        row.dataset.notificationId = String(item.notification_id);
        row.setAttribute("aria-label", `Preview notification: ${item.title || "Notification"}`);

        const copy = element("span", "notification-row-copy");
        const heading = element("span", "notification-row-heading");

        heading.append(
            element("span", "notification-row-title", item.title || "Notification")
        );

        if (item.status === "UNREAD") {
            heading.append(element("span", "notification-row-unread", "Unread"));
        }

        copy.append(
            heading,
            element("span", "notification-row-message", item.message || "No message provided."),
            element("span", "notification-row-meta",
                `${readableType(item.notification_type)} · ${getNotificationDate(item)}`)
        );

        row.append(
            copy,
            element("span", "notification-row-open", "Preview")
        );

        row.addEventListener("click", () => openNotificationPreview(item, row));
        fragment.append(row);
    }

    notificationList.append(fragment);
}

function openNotificationPreview(item, trigger) {
    previewTrigger = trigger;
    selectedNotificationId = String(item.notification_id);

    previewTitle.textContent = item.title || "Notification";
    previewContent.replaceChildren();
    previewActions.replaceChildren();

    previewContent.append(
        element("p", "notification-preview-type", readableType(item.notification_type)),
        element("p", "notification-preview-message", item.message || "No message provided.")
    );

    const details = document.createElement("dl");
    details.className = "notification-preview-details";

    const detailRows = [
        ["Received", getNotificationDate(item)],
        ["Status", item.status === "UNREAD" ? "Unread" : "Read"],
        ["Read at", item.read_at || (item.status === "READ" ? "Read" : "Not yet read")]
    ];

    for (const [label, value] of detailRows) {
        details.append(
            element("dt", "", label),
            element("dd", "", value || "Not available")
        );
    }

    previewContent.append(details);

    if (item.status === "UNREAD") {
        const markReadButton = element("button", "primary-button", "Mark as read");
        markReadButton.type = "button";
        markReadButton.addEventListener("click", () => markAsRead(item, markReadButton));
        previewActions.append(markReadButton);
    } else {
        previewActions.append(element("span", "notification-preview-receipt", "Read"));
    }

    previewModal.hidden = false;
    document.body.classList.add("notification-preview-open");
    closePreviewButton.focus();
}

function closeNotificationPreview() {
    previewModal.hidden = true;
    document.body.classList.remove("notification-preview-open");

    if (previewTrigger && previewTrigger.isConnected) {
        previewTrigger.focus();
    } else if (selectedNotificationId) {
        const row = notificationList.querySelector(
            `[data-notification-id="${CSS.escape(selectedNotificationId)}"]`
        );

        if (row) row.focus();
        else refreshButton.focus();
    }

    previewTrigger = null;
    selectedNotificationId = null;
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

        if (selectedNotificationId) {
            const selected = notifications.find(
                item => String(item.notification_id) === selectedNotificationId
            );

            if (selected) {
                const row = notificationList.querySelector(
                    `[data-notification-id="${CSS.escape(selectedNotificationId)}"]`
                );

                if (row) previewTrigger = row;
                openNotificationPreview(selected, row || previewTrigger);
            }
        }
    } catch (error) {
        notificationStatus.textContent =
            error.message || "Unable to load notifications.";
    } finally {
        refreshButton.disabled = false;
    }
}

async function markAsRead(item, button) {
    if (button.disabled || item.status !== "UNREAD") return;

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
        item.read_at = item.read_at ||
            new Date().toISOString().slice(0, 19).replace("T", " ");

        renderNotifications();

        const row = notificationList.querySelector(
            `[data-notification-id="${CSS.escape(String(item.notification_id))}"]`
        );

        previewTrigger = row || null;
        openNotificationPreview(item, row || null);
    } catch (error) {
        button.disabled = false;
        button.textContent = "Mark as read";
        notificationStatus.textContent =
            error.message || "Unable to update notification.";
    }
}

notificationSearch.addEventListener("input", renderNotifications);
notificationFilter.addEventListener("change", renderNotifications);
refreshButton.addEventListener("click", loadNotifications);
closePreviewButton.addEventListener("click", closeNotificationPreview);

previewModal.addEventListener("click", event => {
    if (event.target === previewModal) {
        closeNotificationPreview();
    }
});

document.addEventListener("keydown", event => {
    if (event.key === "Escape" && !previewModal.hidden) {
        closeNotificationPreview();
    }
});

async function initialiseNotifications() {
    try {
        const account = await shellReady;
        if (!account) return;

        await loadNotifications();
    } catch (error) {
        notificationStatus.textContent =
            error.message || "Unable to initialise notifications.";
    }
}

initialiseNotifications();