"use strict";

const announcementList = document.getElementById("announcementList");
const announcementStatus = document.getElementById("announcementStatus");
const announcementSearch = document.getElementById("announcementSearch");
const announcementFilter = document.getElementById("announcementFilter");
const refreshButton = document.getElementById("refreshAnnouncementsButton");
const composeButton = document.getElementById("composeAnnouncementButton");

const composeModal = document.getElementById("composeModal");
const composeForm = document.getElementById("composeForm");
const composeTitle = document.getElementById("composeAnnouncementTitle");
const composeMessage = document.getElementById("composeAnnouncementMessage");
const allBranchesToggle = document.getElementById("allBranchesToggle");
const branchTargetsGroup = document.getElementById("branchTargetsGroup");
const branchTargetsList = document.getElementById("branchTargetsList");
const composeFormMessage = document.getElementById("composeFormMessage");
const sendAnnouncementButton = document.getElementById("sendAnnouncementButton");

const previewModal = document.getElementById("previewModal");
const previewTitle = document.getElementById("previewTitle");
const previewContent = document.getElementById("previewContent");
const previewActions = document.getElementById("previewActions");
const recipientSection = document.getElementById("recipientSection");
const recipientStatusList = document.getElementById("recipientStatusList");
const replyArea = document.getElementById("replyArea");
const replyHeading = document.getElementById("replyHeading");
const replyList = document.getElementById("replyList");
const replyForm = document.getElementById("replyForm");
const replyText = document.getElementById("replyText");
const replyFormMessage = document.getElementById("replyFormMessage");
const sendReplyButton = document.getElementById("sendReplyButton");

let announcements = [];
let branches = [];
let canManage = false;
let canCompose = false;
let myBranchId = null;
let selectedAnnouncement = null;
let selectedThreadBranchId = null;
let previewTrigger = null;

function element(tag, className = "", text = "") {
    const node = document.createElement(tag);

    if (className) {
        node.className = className;
    }

    if (text !== undefined && text !== null) {
        node.textContent = String(text);
    }

    return node;
}

function setMessage(node, message, type = "") {
    node.textContent = message || "";
    node.className = "form-message";

    if (type) {
        node.classList.add(type);
    }
}

function dateText(value) {
    if (!value) {
        return "Date unavailable";
    }

    const parsed = new Date(String(value).replace(" ", "T"));

    return Number.isNaN(parsed.getTime())
        ? String(value)
        : parsed.toLocaleString();
}

function readableStatus(status) {
    const labels = {
        UNREAD: "Unread",
        READ: "Read",
        ACKNOWLEDGED: "Acknowledged"
    };

    return labels[status] || status || "Unknown";
}

function apiError(result, fallback) {
    return result?.data?.error || fallback;
}

function configureFilter() {
    let options;

    if (canManage) {
        options = [
            ["ALL", "All announcements"],
            ["WAITING", "Awaiting acknowledgement"],
            ["COMPLETE", "Fully acknowledged"]
        ];
    } else {
        options = [["ALL", "All announcements"]];

        if (canCompose) {
            options.push(["SENT", "Sent by me"]);
        }

        options.push(
            ["UNREAD", "Unread"],
            ["READ", "Read"],
            ["ACKNOWLEDGED", "Acknowledged"]
        );
    }

    const current = announcementFilter.value;

    announcementFilter.replaceChildren(
        ...options.map(([value, label]) => {
            const node = element("option", "", label);
            node.value = value;
            return node;
        })
    );

    announcementFilter.value = options.some(([value]) => value === current)
        ? current
        : "ALL";
}

function announcementMatchesFilter(item) {
    const filter = announcementFilter.value;

    if (filter === "ALL") {
        return true;
    }

    if (canManage) {
        const total = Number(item.recipient_count || 0);
        const acknowledged = Number(item.acknowledged_count || 0);

        if (filter === "WAITING") {
            return total > acknowledged;
        }

        if (filter === "COMPLETE") {
            return total > 0 && acknowledged >= total;
        }

        return true;
    }

    if (filter === "SENT") {
        return item.branch_status === "SENT";
    }

    const status = item.branch_status === "SENT"
        ? item.recipient_status
        : item.branch_status;

    return status === filter;
}

function renderAnnouncements() {
    announcementList.replaceChildren();

    const term = announcementSearch.value.trim().toLowerCase();

    const filtered = announcements.filter(item => {
        const searchable = [
            item.title,
            item.message,
            item.sender_name
        ].filter(Boolean).join(" ").toLowerCase();

        return searchable.includes(term) && announcementMatchesFilter(item);
    });

    const unreadCount = announcements.filter(item =>
        !canManage && item.branch_status === "UNREAD"
    ).length;

    const sentCount = announcements.filter(item =>
        item.branch_status === "SENT"
    ).length;

    if (canManage) {
        announcementStatus.textContent =
            `${filtered.length} shown · ${announcements.length} sent`;
    } else if (canCompose) {
        announcementStatus.textContent =
            `${filtered.length} shown · ${unreadCount} unread · ${sentCount} sent by you`;
    } else {
        announcementStatus.textContent =
            `${filtered.length} shown · ${unreadCount} unread`;
    }

    if (filtered.length === 0) {
        announcementList.append(element(
            "p",
            "branch-announcement-empty",
            announcements.length
                ? "No announcements match your search or filter."
                : "No branch announcements yet."
        ));
        return;
    }

    const fragment = document.createDocumentFragment();

    for (const item of filtered) {
        const sentByMe = !canManage && item.branch_status === "SENT";

        const row = element(
            "button",
            `branch-announcement-row${item.branch_status === "UNREAD" ? " unread" : ""}`
        );

        row.type = "button";
        row.dataset.announcementId = String(item.announcement_id);
        row.setAttribute(
            "aria-label",
            `Open announcement: ${item.title || "Branch announcement"}`
        );

        const copy = element("span", "branch-announcement-copy");

        copy.append(
            element(
                "span",
                "branch-announcement-title",
                item.title || "Untitled announcement"
            ),
            element(
                "span",
                "branch-announcement-message",
                item.message || "No message provided."
            )
        );

        let source;
        let status;

        if (canManage) {
            source = "Sent";
            status = `${Number(item.acknowledged_count || 0)}/${Number(item.recipient_count || 0)} branches acknowledged`;
        } else if (sentByMe) {
            source = "Sent by you";
            status = `Branch receipt: ${readableStatus(item.recipient_status)}`;
        } else {
            source = item.sender_role === "BRANCH_ADMIN"
                ? "From your branch admin"
                : "From head office";

            status = readableStatus(item.branch_status);
        }

        copy.append(element(
            "span",
            "branch-announcement-meta",
            `${source} · ${dateText(item.created_at)} · ${status}`
        ));

        row.append(copy, element("span", "branch-announcement-open", "Preview"));
        row.addEventListener("click", () => openAnnouncementPreview(item, row));

        fragment.append(row);
    }

    announcementList.append(fragment);
}

async function loadAnnouncements() {
    refreshButton.disabled = true;
    announcementStatus.textContent = "Loading announcements...";

    try {
        const result = await getJson("branch-communications/list.php");

        if (!result.ok || result.data.success !== true) {
            throw new Error(apiError(result, "Unable to load branch announcements."));
        }

        announcements = Array.isArray(result.data.announcements)
            ? result.data.announcements
            : [];

        canManage = result.data.can_manage === true;
        canCompose = result.data.can_compose === true;
        myBranchId = result.data.branch_id
            ? Number(result.data.branch_id)
            : null;

        const toggleLabel = allBranchesToggle.closest(".branch-all-toggle");
        if (toggleLabel) {
            toggleLabel.hidden = !canManage;
        }

        composeButton.hidden = !canCompose;

        configureFilter();
        renderAnnouncements();
    } catch (error) {
        announcementStatus.textContent =
            error.message || "Unable to load branch announcements.";
    } finally {
        refreshButton.disabled = false;
    }
}

async function loadBranches() {
    const result = await getJson("branch-communications/targets.php");

    if (!result.ok || result.data.success !== true) {
        throw new Error(apiError(result, "Unable to load branches."));
    }

    branches = Array.isArray(result.data.branches)
        ? result.data.branches
        : [];

    branchTargetsList.replaceChildren();

    const legend = branchTargetsGroup.querySelector("legend");
    if (legend) {
        legend.textContent = canManage ? "Select branches" : "Recipient branch";
    }

    if (branches.length === 0) {
        branchTargetsList.append(element(
            "p",
            "branch-communications-empty",
            "There are no active branches to select."
        ));
        return;
    }

    for (const branch of branches) {
        const label = element("label", "branch-target-option");
        const checkbox = document.createElement("input");

        checkbox.type = "checkbox";
        checkbox.name = "branchIds";
        checkbox.value = String(branch.branch_id);
        checkbox.defaultChecked = !canManage;
        checkbox.checked = !canManage;
        checkbox.disabled = !canManage;

        const name = element("span", "", branch.branch_name);

        label.append(checkbox, name);

        if (Number(branch.is_main_branch) === 1) {
            label.append(element("span", "branch-target-location", "Main branch"));
        } else if (branch.location) {
            label.append(element("span", "branch-target-location", branch.location));
        }

        branchTargetsList.append(label);
    }
}

function openComposeModal() {
    composeForm.reset();

    allBranchesToggle.checked = canManage;
    branchTargetsGroup.hidden = canManage;
    setMessage(composeFormMessage, "");

    const heading = composeModal.querySelector("h2");
    if (heading) {
        heading.textContent = canManage
            ? "New branch announcement"
            : "New notice for your branch";
    }

    const legend = branchTargetsGroup.querySelector("legend");
    if (legend) {
        legend.textContent = canManage ? "Select branches" : "Recipient branch";
    }

    branchTargetsList.querySelectorAll('input[name="branchIds"]').forEach(input => {
        input.checked = !canManage;
    });

    composeModal.hidden = false;
    document.body.classList.add("branch-communications-modal-open");
    composeModal.querySelector(".branch-communications-modal").focus();
}

function closeComposeModal() {
    composeModal.hidden = true;
    document.body.classList.remove("branch-communications-modal-open");
    composeButton.focus();
}

async function submitAnnouncement(event) {
    event.preventDefault();

    const title = composeTitle.value.trim();
    const message = composeMessage.value.trim();

    if (!title || !message) {
        setMessage(composeFormMessage, "Enter a subject and message.", "error");
        return;
    }

    const allBranches = canManage && allBranchesToggle.checked;

    const branchIds = canManage
        ? allBranches
            ? []
            : [...branchTargetsList.querySelectorAll(
                'input[name="branchIds"]:checked'
            )].map(input => Number(input.value))
        : myBranchId
            ? [myBranchId]
            : [];

    if (!allBranches && branchIds.length === 0) {
        setMessage(composeFormMessage, "No valid recipient branch was found.", "error");
        return;
    }

    sendAnnouncementButton.disabled = true;
    sendAnnouncementButton.textContent = "Sending...";

    try {
        const result = await postJson("branch-communications/create.php", {
            title,
            message,
            allBranches,
            branchIds
        });

        if (!result.ok || result.data.success !== true) {
            throw new Error(apiError(result, "Unable to send the announcement."));
        }

        closeComposeModal();
        announcementSearch.value = "";
        announcementFilter.value = "ALL";

        await loadAnnouncements();

        announcementStatus.textContent =
            `Announcement sent to ${result.data.recipient_count} branch(es).`;
    } catch (error) {
        setMessage(
            composeFormMessage,
            error.message || "Unable to send the announcement.",
            "error"
        );
    } finally {
        sendAnnouncementButton.disabled = false;
        sendAnnouncementButton.textContent = "Send announcement";
    }
}

function buildPreview(item) {
    previewTitle.textContent = item.title || "Branch announcement";
    previewContent.replaceChildren();
    previewActions.replaceChildren();
    recipientStatusList.replaceChildren();
    replyList.replaceChildren();

    recipientSection.hidden = true;
    replyArea.hidden = true;

    previewContent.append(
        element("p", "branch-preview-message", item.message || "No message provided."),
        element(
            "p",
            "branch-preview-meta",
            `Sent by ${item.sender_name || "Head office"} · ${dateText(item.created_at)}`
        )
    );

    const sentByMe = !canManage && item.branch_status === "SENT";

    if (!canManage && !sentByMe) {
        const status = item.branch_status;

        if (status === "ACKNOWLEDGED") {
            previewActions.append(element(
                "span",
                "branch-preview-confirmation",
                "Receipt acknowledged"
            ));
        } else {
            const button = element("button", "primary-button", "Acknowledge receipt");
            button.type = "button";
            button.addEventListener("click", () => acknowledgeAnnouncement(item, button));
            previewActions.append(button);
        }

        replyArea.hidden = false;
        replyHeading.textContent = item.sender_role === "BRANCH_ADMIN"
            ? "Conversation with branch admin"
            : "Conversation with head office";

        selectedThreadBranchId = myBranchId;
        loadReplies(item.announcement_id, selectedThreadBranchId);
    } else if (sentByMe) {
        previewActions.append(element(
            "span",
            "branch-preview-confirmation",
            `Sent to your branch · Receipt status: ${readableStatus(item.recipient_status)}`
        ));

        replyArea.hidden = false;
        replyHeading.textContent = "Replies from branch staff";

        selectedThreadBranchId = myBranchId;
        loadReplies(item.announcement_id, selectedThreadBranchId);
    } else {
        const summary = element(
            "p",
            "branch-preview-meta",
            `${Number(item.acknowledged_count || 0)} of ${Number(item.recipient_count || 0)} branches acknowledged`
        );

        previewActions.append(summary);
        recipientSection.hidden = false;
        loadRecipients(item);
    }
}

async function openAnnouncementPreview(item, trigger) {
    selectedAnnouncement = item;
    previewTrigger = trigger;

    buildPreview(item);

    previewModal.hidden = false;
    document.body.classList.add("branch-communications-modal-open");
    previewModal.querySelector(".branch-communications-modal").focus();

    if (!canManage && item.branch_status === "UNREAD") {
        try {
            const result = await postJson("branch-communications/read.php", {
                announcementId: Number(item.announcement_id)
            });

            if (result.ok && result.data.success === true) {
                item.branch_status = "READ";
                renderAnnouncements();
                buildPreview(item);
            }
        } catch {
        }
    }
}

async function loadRecipients(item) {
    const result = await getJson(
        `branch-communications/recipients.php?announcementId=${encodeURIComponent(item.announcement_id)}`
    );

    if (!result.ok || result.data.success !== true) {
        recipientStatusList.append(element(
            "p",
            "branch-communications-empty",
            apiError(result, "Unable to load branch responses.")
        ));
        return;
    }

    recipientStatusList.replaceChildren();

    const recipients = result.data.recipients || [];

    if (!recipients.length) {
        recipientStatusList.append(element(
            "p",
            "branch-communications-empty",
            "No recipient branches were recorded."
        ));
        return;
    }

    for (const branch of recipients) {
        const row = element("div", "branch-recipient-row");
        const name = element("span", "branch-recipient-name", branch.branch_name);
        const status = element(
            `span`,
            `branch-recipient-status${branch.status === "ACKNOWLEDGED" ? " acknowledged" : ""}`,
            readableStatus(branch.status)
        );

        const button = element("button", "secondary-button", "Open thread");
        button.type = "button";
        button.addEventListener("click", async () => {
            selectedThreadBranchId = Number(branch.branch_id);
            replyArea.hidden = false;
            replyHeading.textContent = `Conversation · ${branch.branch_name}`;
            await loadReplies(item.announcement_id, selectedThreadBranchId);
            replyArea.scrollIntoView({ block: "nearest", behavior: "smooth" });
        });

        row.append(name, status, button);
        recipientStatusList.append(row);
    }
}

async function acknowledgeAnnouncement(item, button) {
    if (button.disabled) return;

    button.disabled = true;
    button.textContent = "Saving...";

    try {
        const result = await postJson("branch-communications/acknowledge.php", {
            announcementId: Number(item.announcement_id)
        });

        if (!result.ok || result.data.success !== true) {
            throw new Error(apiError(result, "Unable to acknowledge receipt."));
        }

        item.branch_status = "ACKNOWLEDGED";
        renderAnnouncements();
        buildPreview(item);
    } catch (error) {
        button.disabled = false;
        button.textContent = "Try again";
        announcementStatus.textContent =
            error.message || "Unable to acknowledge receipt.";
    }
}

async function loadReplies(announcementId, branchId) {
    replyList.replaceChildren();

    if (!branchId) {
        replyList.append(element(
            "p",
            "branch-communications-empty",
            "This user is not assigned to a branch."
        ));
        return;
    }

    const query = new URLSearchParams({
        announcementId: String(announcementId),
        branchId: String(branchId)
    });

    const result = await getJson(
        `branch-communications/replies.php?${query.toString()}`
    );

    if (!result.ok || result.data.success !== true) {
        replyList.append(element(
            "p",
            "branch-communications-empty",
            apiError(result, "Unable to load replies.")
        ));
        return;
    }

    const replies = result.data.replies || [];

    if (!replies.length) {
        replyList.append(element(
            "p",
            "branch-communications-empty",
            "No replies yet."
        ));
    }

    for (const reply of replies) {
        const item = element("article", "branch-reply-item");
        const header = element("div", "branch-reply-item-header");

        header.append(
            element(
                "span",
                "branch-reply-sender",
                `${reply.sender_name || "User"}${reply.sender_role === "ADMIN" ? " · Head office" : ""}`
            ),
            element("span", "branch-reply-date", dateText(reply.sent_at))
        );

        item.append(
            header,
            element("p", "branch-reply-text", reply.message_text)
        );

        replyList.append(item);
    }
}

async function submitReply(event) {
    event.preventDefault();

    if (!selectedAnnouncement || !selectedThreadBranchId) {
        setMessage(replyFormMessage, "Choose a branch conversation first.", "error");
        return;
    }

    const text = replyText.value.trim();

    if (!text) {
        setMessage(replyFormMessage, "Write a reply first.", "error");
        return;
    }

    sendReplyButton.disabled = true;
    sendReplyButton.textContent = "Sending...";

    try {
        const result = await postJson("branch-communications/replies.php", {
            announcementId: Number(selectedAnnouncement.announcement_id),
            branchId: Number(selectedThreadBranchId),
            text
        });

        if (!result.ok || result.data.success !== true) {
            throw new Error(apiError(result, "Unable to send the reply."));
        }

        replyText.value = "";
        setMessage(replyFormMessage, "");
        await loadReplies(
            selectedAnnouncement.announcement_id,
            selectedThreadBranchId
        );
    } catch (error) {
        setMessage(
            replyFormMessage,
            error.message || "Unable to send the reply.",
            "error"
        );
    } finally {
        sendReplyButton.disabled = false;
        sendReplyButton.textContent = "Send reply";
    }
}

function closePreview() {
    previewModal.hidden = true;
    document.body.classList.remove("branch-communications-modal-open");

    selectedAnnouncement = null;
    selectedThreadBranchId = null;

    if (previewTrigger && previewTrigger.isConnected) {
        previewTrigger.focus();
    }

    previewTrigger = null;
}

allBranchesToggle.addEventListener("change", async () => {
    branchTargetsGroup.hidden = allBranchesToggle.checked;

    if (!allBranchesToggle.checked && branches.length === 0) {
        try {
            await loadBranches();
        } catch (error) {
            setMessage(
                composeFormMessage,
                error.message || "Unable to load branches.",
                "error"
            );
        }
    }
});

composeButton.addEventListener("click", async () => {
    openComposeModal();

    if (branches.length === 0) {
        try {
            await loadBranches();
        } catch (error) {
            setMessage(
                composeFormMessage,
                error.message || "Unable to load branches.",
                "error"
            );
        }
    }
});

document.getElementById("closeComposeButton").addEventListener("click", closeComposeModal);
document.getElementById("cancelComposeButton").addEventListener("click", closeComposeModal);
composeForm.addEventListener("submit", submitAnnouncement);

document.getElementById("closePreviewButton").addEventListener("click", closePreview);
previewModal.addEventListener("click", event => {
    if (event.target === previewModal) closePreview();
});

composeModal.addEventListener("click", event => {
    if (event.target === composeModal) closeComposeModal();
});

document.addEventListener("keydown", event => {
    if (event.key === "Escape") {
        if (!previewModal.hidden) closePreview();
        else if (!composeModal.hidden) closeComposeModal();
    }
});

announcementSearch.addEventListener("input", renderAnnouncements);
announcementFilter.addEventListener("change", renderAnnouncements);
refreshButton.addEventListener("click", loadAnnouncements);
replyForm.addEventListener("submit", submitReply);

shellReady.then(account => {
    if (account) {
        loadAnnouncements();
    }
});