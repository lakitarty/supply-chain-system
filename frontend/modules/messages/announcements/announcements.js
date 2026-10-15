"use strict";

const announcementForm = document.getElementById("announcementForm");
const publishSection = document.getElementById("publishSection");
const productSelect = document.getElementById("announcementProduct");
const titleInput = document.getElementById("announcementTitle");
const messageInput = document.getElementById("announcementMessage");
const formMessage = document.getElementById("announcementFormMessage");
const publishButton = document.getElementById("publishButton");
const productPreview = document.getElementById("announcementProductPreview");
const productImage = document.getElementById("announcementImage");
const productDetails = document.getElementById("announcementProductDetails");
const announcementList = document.getElementById("announcementList");
const announcementStatus = document.getElementById("announcementStatus");
const listTitle = document.getElementById("listTitle");
const announcementSearch = document.getElementById("announcementSearch");
const availabilityFilter = document.getElementById("announcementAvailabilityFilter");
const receiptFilter = document.getElementById("announcementReceiptFilter");
const receiptFilterGroup = document.getElementById("receiptFilterGroup");
const announcementPagination = document.getElementById("announcementPagination");

let account = null;
let products = [];
let announcements = [];
let announcementDirection = "none";
let announcementPage = 1;

const announcementsPerPage = 10;

function showFormMessage(message, type = "error") {
    formMessage.textContent = message;
    formMessage.className = `form-message ${type}`;
}

function clearFormMessage() {
    formMessage.textContent = "";
    formMessage.className = "form-message";
}

function organizationId() {
    return Number(account?.organization?.organization_id
        ?? account?.organization_id
        ?? 0);
}

function organizationType() {
    return account?.organization?.organization_type
        ?? account?.organization_type
        ?? "";
}

function canPublish() {
    const role = account?.user?.role ?? account?.role;
    return role === "ADMIN"
        && ["MANUFACTURER", "MANUFACTURER_SUPPLIER"].includes(organizationType());
}

function currentVersion(product) {
    return product.versions?.find(version => Number(version.is_current) === 1)
        ?? null;
}

function imageSource(path) {
    if (!path) return "";
    if (/^(https?:\/\/|data:|\/)/i.test(path)) return path;
    return new URL(path, pageUrl("")).href;
}

function showProductPreview() {
    const product = products.find(item =>
        String(item.product_id) === productSelect.value
    );

    if (!product) {
        productPreview.hidden = true;
        productImage.removeAttribute("src");
        productDetails.textContent = "";
        return;
    }

    const version = currentVersion(product);
    const details = [
        product.category,
        version?.pack_size,
        version?.barcode ? `Barcode: ${version.barcode}` : ""
    ].filter(Boolean);

    productDetails.textContent = details.join(" · ");

    const source = imageSource(product.image_path);
    if (source) {
        productImage.src = source;
        productImage.hidden = false;
    } else {
        productImage.removeAttribute("src");
        productImage.hidden = true;
    }

    productPreview.hidden = false;
}

async function loadProducts() {
    const result = await getJson("products/list.php");

    if (!result.ok || result.data.success !== true) {
        throw new Error(result.data.error || "Unable to load products.");
    }

    products = Array.isArray(result.data.products)
        ? result.data.products
        : [];

    const ownProducts = products.filter(product =>
        Number(product.manufacturer_id) === organizationId()
        && currentVersion(product)
    );

    productSelect.replaceChildren(new Option("Select a product", ""));

    for (const product of ownProducts) {
        productSelect.add(new Option(
            product.product_name,
            String(product.product_id)
        ));
    }

    if (ownProducts.length === 0) {
        productSelect.replaceChildren(
            new Option("No eligible products available", "")
        );
        publishButton.disabled = true;
    }
}

function createTextElement(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    element.textContent = text ?? "";
    return element;
}

function openImageViewer(source, altText, trigger) {
    const overlay = document.createElement("div");
    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");
    overlay.setAttribute("aria-label", "Product image viewer");

    Object.assign(overlay.style, {
        position: "fixed",
        inset: "0",
        zIndex: "99999",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "24px",
        boxSizing: "border-box",
        background: "rgba(0, 0, 0, 0.88)",
        cursor: "zoom-out"
    });

    const fullImage = document.createElement("img");
    fullImage.src = source;
    fullImage.alt = altText;

    Object.assign(fullImage.style, {
        display: "block",
        maxWidth: "94vw",
        maxHeight: "88vh",
        width: "auto",
        height: "auto",
        objectFit: "contain",
        borderRadius: "8px",
        boxShadow: "0 8px 40px rgba(0, 0, 0, 0.4)",
        cursor: "default"
    });

    const closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.textContent = "×";
    closeButton.setAttribute("aria-label", "Close image viewer");

    Object.assign(closeButton.style, {
        position: "absolute",
        top: "16px",
        right: "20px",
        width: "44px",
        height: "44px",
        border: "none",
        borderRadius: "50%",
        background: "#ffffff",
        color: "#222222",
        fontSize: "32px",
        lineHeight: "1",
        cursor: "pointer"
    });

    function closeViewer() {
        document.removeEventListener("keydown", handleKeyDown);
        overlay.remove();

        if (trigger && trigger.isConnected) {
            trigger.focus();
        }
    }

    function handleKeyDown(event) {
        if (event.key === "Escape") {
            closeViewer();
        }
    }

    overlay.addEventListener("click", closeViewer);
    fullImage.addEventListener("click", event => event.stopPropagation());

    closeButton.addEventListener("click", event => {
        event.stopPropagation();
        closeViewer();
    });

    overlay.append(fullImage, closeButton);
    document.body.append(overlay);
    document.addEventListener("keydown", handleKeyDown);
    closeButton.focus();
}

function renderAnnouncements() {
    announcementList.replaceChildren();
    announcementPagination.replaceChildren();

    if (announcements.length === 0) {
        announcementStatus.textContent = "No product announcements yet.";
        return;
    }

    const searchTerm = announcementSearch.value.trim().toLowerCase();
    const availabilityValue = availabilityFilter.value;
    const receiptValue = announcementDirection === "incoming"
        ? receiptFilter.value
        : "ALL";

    const filteredAnnouncements = announcements.filter(item => {
        const searchableText = [
            item.announcement_title,
            item.product_name,
            item.manufacturer_name,
            item.category,
            item.announcement_message,
            item.product_description
        ].filter(Boolean).join(" ").toLowerCase();

        if (searchTerm && !searchableText.includes(searchTerm)) {
            return false;
        }

        if (
            availabilityValue !== "ALL"
            && item.availability_status !== availabilityValue
        ) {
            return false;
        }

        if (
            receiptValue === "PENDING"
            && item.acknowledgement_status === "ACKNOWLEDGED"
        ) {
            return false;
        }

        if (
            receiptValue === "ACKNOWLEDGED"
            && item.acknowledgement_status !== "ACKNOWLEDGED"
        ) {
            return false;
        }

        return true;
    });

    if (filteredAnnouncements.length === 0) {
        announcementStatus.textContent = announcements.length
            ? "No announcements match your search or filters."
            : "No product announcements yet.";

        if (announcements.length > 0) {
            announcementList.append(
                createTextElement(
                    "p",
                    "announcement-empty-state",
                    "Try changing your search or filters."
                )
            );
        }
        return;
    }

    const pageCount = Math.ceil(
        filteredAnnouncements.length / announcementsPerPage
    );

    announcementPage = Math.min(announcementPage, pageCount);

    const startIndex = (announcementPage - 1) * announcementsPerPage;
    const pageAnnouncements = filteredAnnouncements.slice(
        startIndex,
        startIndex + announcementsPerPage
    );

    announcementStatus.textContent =
        `Showing ${startIndex + 1}–${Math.min(
            startIndex + announcementsPerPage,
            filteredAnnouncements.length
        )} of ${filteredAnnouncements.length} announcement(s)`;

    const fragment = document.createDocumentFragment();

    for (const item of pageAnnouncements) {
        const card = document.createElement("article");
        card.className = "announcement-card";

        card.append(
            createTextElement("h3", "section-title", item.announcement_title),
            createTextElement(
                "p",
                "",
                `Product: ${item.product_name || "Product"}`
            )
        );

        if (item.availability_status) {
            const availabilityLabel = item.availability_status === "COMING_SOON"
                ? "Coming soon"
                : "Available now";

            card.append(createTextElement(
                "p",
                "product-availability",
                `Availability: ${availabilityLabel}`
            ));
        }

        if (item.manufacturer_name) {
            card.append(createTextElement(
                "p",
                "",
                `Manufacturer: ${item.manufacturer_name}`
            ));
        }

        if (item.category) {
            card.append(createTextElement("p", "", `Category: ${item.category}`));
        }

        const details = [
            item.pack_size,
            item.barcode ? `Barcode: ${item.barcode}` : "",
            item.packaging_description
        ].filter(Boolean);

        if (details.length) {
            card.append(createTextElement("p", "", details.join(" · ")));
        }

        if (item.product_description) {
            card.append(createTextElement("p", "", item.product_description));
        }

        if (item.announcement_message) {
            card.append(createTextElement("p", "", item.announcement_message));
        }

        if (item.image_path) {
            const image = document.createElement("img");
            image.className = "announcement-product-image";
            image.src = imageSource(item.image_path);
            image.alt = item.product_name || "Product image";
            image.width = 140;
            image.loading = "lazy";

            const viewImageButton = document.createElement("button");
            viewImageButton.className = "announcement-view-image";
            viewImageButton.type = "button";
            viewImageButton.textContent = "View image";
            viewImageButton.setAttribute(
                "aria-label",
                `View larger image of ${image.alt}`
            );



            viewImageButton.addEventListener("click", () => {
                openImageViewer(image.src, image.alt, viewImageButton);
            });

            card.append(image, viewImageButton);
        }

        card.append(createTextElement(
            "p",
            "",
            `Published: ${item.published_at || "Date unavailable"}`
        ));

        if (canPublish()) {
            card.append(createTextElement(
                "p",
                "",
                `Organizations reached: ${item.recipient_count ?? 0} · `
                + `Acknowledged: ${item.acknowledged_count ?? 0}`
            ));
        } else if (item.acknowledgement_status) {
            const acknowledged =
                item.acknowledgement_status === "ACKNOWLEDGED";

            card.append(createTextElement(
                "p",
                "",
                acknowledged ? "Receipt acknowledged" : "Awaiting acknowledgement"
            ));

            if (!acknowledged) {
                const button = createTextElement(
                    "button",
                    "primary-button",
                    "Acknowledge receipt"
                );
                button.type = "button";
                button.addEventListener("click", () =>
                    acknowledgeAnnouncement(item.announcement_id, button)
                );
                card.append(button);
            }
        }

        fragment.append(card);
    }

    announcementList.append(fragment);

    if (pageCount > 1) {
        const previousButton = createTextElement(
            "button",
            "announcement-page-button",
            "Previous"
        );
        previousButton.type = "button";
        previousButton.disabled = announcementPage === 1;
        previousButton.addEventListener("click", () => {
            if (announcementPage > 1) {
                announcementPage--;
                renderAnnouncements();
            }
        });

        const pageSummary = createTextElement(
            "span",
            "announcement-page-summary",
            `Page ${announcementPage} of ${pageCount}`
        );

        const nextButton = createTextElement(
            "button",
            "announcement-page-button",
            "Next"
        );
        nextButton.type = "button";
        nextButton.disabled = announcementPage === pageCount;
        nextButton.addEventListener("click", () => {
            if (announcementPage < pageCount) {
                announcementPage++;
                renderAnnouncements();
            }
        });

        announcementPagination.append(
            previousButton,
            pageSummary,
            nextButton
        );
    }
}

async function loadAnnouncements() {
    announcementStatus.textContent = "Loading announcements...";

    const result = await getJson("announcements/list.php");

    if (!result.ok || result.data.success !== true) {
        throw new Error(result.data.error || "Unable to load announcements.");
    }

    announcements = Array.isArray(result.data.announcements)
        ? result.data.announcements
        : [];

    announcementDirection = result.data.direction || "none";
    receiptFilterGroup.hidden = announcementDirection !== "incoming";
    announcementPage = 1;

    if (result.data.direction === "outgoing") {
        listTitle.textContent = "Published announcements";
    } else if (result.data.direction === "incoming") {
        listTitle.textContent = "Announcements from manufacturers";
    } else {
        listTitle.textContent = "Announcements";
    }

    renderAnnouncements();
}

async function publishAnnouncement(event) {
    event.preventDefault();
    clearFormMessage();

    if (!productSelect.value) {
        showFormMessage("Select a product first.");
        return;
    }

    if (!titleInput.value.trim()) {
        showFormMessage("Enter an announcement title.");
        titleInput.focus();
        return;
    }

    publishButton.disabled = true;
    publishButton.textContent = "Publishing...";

    try {
        const result = await postJson("announcements/publish.php", {
            productId: Number(productSelect.value),
            title: titleInput.value.trim(),
            message: messageInput.value.trim()
        });

        if (!result.ok || result.data.success !== true) {
            throw new Error(result.data.error || "Unable to publish announcement.");
        }

        announcementForm.reset();
        showProductPreview();
        showFormMessage(
            `Announcement published. It was sent to ${result.data.recipientCount} retailer organization(s).`,
            "success"
        );

        await loadAnnouncements();
    } catch (error) {
        showFormMessage(error.message || "Unable to publish announcement.");
    } finally {
        publishButton.disabled = false;
        publishButton.textContent = "Publish announcement";
    }
}

async function acknowledgeAnnouncement(announcementId, button) {
    if (button.disabled) return;

    button.disabled = true;
    button.textContent = "Saving...";

    try {
        const result = await postJson("announcements/acknowledge.php", {
            announcementId: Number(announcementId)
        });

        if (!result.ok || result.data.success !== true) {
            throw new Error(result.data.error || "Unable to acknowledge announcement.");
        }

        await loadAnnouncements();
    } catch (error) {
        button.disabled = false;
        button.textContent = "Try again";
        announcementStatus.textContent =
            error.message || "Unable to acknowledge announcement.";
    }
}

async function initialiseAnnouncements() {
    try {
        account = await shellReady;

        if (!account) return;

        if (canPublish()) {
            publishSection.hidden = false;
            await loadProducts();
        } else {
            publishSection.remove();
        }

        productSelect.addEventListener("change", showProductPreview);
        announcementForm.addEventListener("submit", publishAnnouncement);

        announcementSearch.addEventListener("input", () => {
            announcementPage = 1;
            renderAnnouncements();
        });

        availabilityFilter.addEventListener("change", () => {
            announcementPage = 1;
            renderAnnouncements();
        });

        receiptFilter.addEventListener("change", () => {
            announcementPage = 1;
            renderAnnouncements();
        });

        await loadAnnouncements();
    } catch (error) {
        announcementStatus.textContent =
            error.message || "Unable to load product announcements.";
    }
}

initialiseAnnouncements();
