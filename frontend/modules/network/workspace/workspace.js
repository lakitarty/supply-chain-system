(() => {
    const field = id => document.getElementById(id);

    const message = field("workspaceMessage");
    const partnerList = field("workspacePartnerList");
    const partnerSearch = field("workspacePartnerSearch");
    const partnerCount = field("workspacePartnerCount");
    const selectionPrompt = field("workspaceSelectionPrompt");
    const workspaceContent = field("workspaceContent");

    const partnerName = field("workspacePartnerName");
    const partnerType = field("workspacePartnerType");
    const supplyDirection = field("workspaceSupplyDirection");
    const status = field("workspaceStatus");
    const meta = field("workspaceMeta");

    const supplierCoverageList = field("workspaceSupplierCoverage");
    const supplierCoverageCount = field("workspaceSupplierCoverageCount");
    const productsList = field("workspaceProducts");
    const ordersList = field("workspaceOrders");
    const promotionsList = field("workspacePromotions");
    const productsCount = field("productsCount");
    const ordersCount = field("ordersCount");
    const promotionsCount = field("promotionsCount");
    const branchSelect = field("workspaceBranchSelect");
    const branchCounty = field("workspaceBranchCounty");
    const distributionAreaSelect = field("workspaceDistributionAreaSelect");
    const distributionAreaNote = field("workspaceDistributionNote");

    let currentAccount = null;
    let activeBranches = [];
    let activeDistributionAreas = [];
    let selectedDistributionAreaId = 0;
    let selectedBranchId = 0;
    let selectedBranch = null;
    let activeRelationships = [];
    let selectedRelationshipId = 0;
    let workspaceRequestSequence = 0;

    function el(tag, className, text) {
        const node = document.createElement(tag);

        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = String(text);

        return node;
    }

    function typeLabel(type) {
        const labels = {
            MANUFACTURER: "Manufacturer",
            SUPPLIER: "Supplier",
            MANUFACTURER_SUPPLIER: "Manufacturer and Supplier",
            RETAILER: "Retailer"
        };

        return labels[type] || "Organisation";
    }

    function humanize(value) {
        return String(value || "")
            .toLowerCase()
            .replaceAll("_", " ")
            .replace(/\b\w/g, letter => letter.toUpperCase());
    }

    function dateText(value) {
        if (!value) return "Not recorded";

        const date = new Date(String(value).replace(" ", "T"));
        if (Number.isNaN(date.getTime())) return String(value);

        return new Intl.DateTimeFormat("en-GB", {
            dateStyle: "medium",
            timeZone: "Africa/Nairobi"
        }).format(date);
    }

    function localDateString() {
        const date = new Date();
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 10);
    }

    function moneyText(value) {
        if (value === null || value === undefined || value === "") {
            return "Order value not recorded";
        }

        const amount = Number(value);

        if (!Number.isFinite(amount)) {
            return "Order value not recorded";
        }

        return new Intl.NumberFormat("en-KE", {
            style: "currency",
            currency: "KES",
            maximumFractionDigits: 2
        }).format(amount);
    }

    function record(title, detail, badgeText) {
        const node = el("article", "workspace-record");
        node.append(el("div", "workspace-record-title", title));

        if (detail) {
            node.append(el("div", "workspace-record-detail", detail));
        }

        if (badgeText) {
            node.append(el("span", "workspace-record-status", humanize(badgeText)));
        }

        return node;
    }

    function renderRecords(container, countNode, records, emptyText, renderItem) {
        container.replaceChildren();
        countNode.textContent = String(records.length);

        if (!records.length) {
            container.append(el("p", "workspace-empty", emptyText));
            return;
        }

        records.forEach(item => container.append(renderItem(item)));
    }

    function renderRelationship(relationship) {
        partnerName.textContent = relationship.partner_name || "Organisation";
        partnerType.textContent = typeLabel(relationship.partner_type);
        status.textContent = humanize(relationship.status);

        supplyDirection.textContent = Number(relationship.i_supply) === 1
            ? `Your organisation supplies ${relationship.partner_name}.`
            : `${relationship.partner_name} supplies your organisation.`;

        meta.replaceChildren(
            el("span", "", `Relationship started ${dateText(relationship.start_date)}`),
            el("span", "", `Supply arrangement: ${humanize(relationship.relationship_type)}`)
        );

        if (relationship.partner_email) {
            meta.append(el("span", "", `Email: ${relationship.partner_email}`));
        }

        if (relationship.partner_phone) {
            meta.append(el("span", "", `Phone: ${relationship.partner_phone}`));
        }

        if (relationship.partner_address) {
            meta.append(el("span", "", `Address: ${relationship.partner_address}`));
        }

        if (relationship.partner_kra_pin) {
            meta.append(el("span", "", `KRA PIN: ${relationship.partner_kra_pin}`));
        }
    }

    function productImageUrl(path) {
        if (
            typeof path !== "string" ||
            !/^uploads\/products\/[a-f0-9]{32}\.(jpg|png|webp)$/i.test(path)
        ) {
            return null;
        }

        return pageUrl(path);
    }

    function renderProduct(item) {
        const lines = [
            `Manufacturer: ${item.manufacturer_name || "Not recorded"}`,
            item.category ? `Category: ${item.category}` : "",
            item.pack_size ? `Pack size: ${item.pack_size}` : "",
            item.packaging_description ? `Packaging: ${item.packaging_description}` : "",
            item.barcode ? `Barcode: ${item.barcode}` : ""
        ].filter(Boolean);

        const node = el("article", "workspace-record workspace-product-record");
        const heading = el("div", "workspace-product-heading");
        const imageUrl = productImageUrl(item.image_path);

        if (imageUrl) {
            const imageFrame = el("div", "workspace-product-image");
            const image = el("img");

            image.src = imageUrl;
            image.alt = item.product_name
                ? `${item.product_name} product image`
                : "Product image";
            image.loading = "lazy";

            image.addEventListener("error", () => {
                imageFrame.remove();
            }, { once: true });

            imageFrame.append(image);
            heading.append(imageFrame);
        }

        heading.append(
            el("div", "workspace-record-title", item.product_name || "Unnamed product")
        );

        node.append(heading);

        if (lines.length) {
            node.append(
                el("div", "workspace-record-detail", lines.join("\n"))
            );
        }

        if (item.supply_availability) {
            node.append(
                el("span", "workspace-record-status", humanize(item.supply_availability))
            );
        }

        return node;
    }

    function supplierCoverageStatus(item) {
        if (item.status !== "ACTIVE") return "Inactive";

        const today = localDateString();

        if (String(item.start_date) > today) return "Scheduled";
        if (item.end_date && String(item.end_date) < today) return "Expired";

        return "Active";
    }

    function renderSupplierCoverage(item) {
        const period = [
            `Effective from: ${dateText(item.start_date)}`,
            `Effective until: ${item.end_date ? dateText(item.end_date) : "No planned end date"}`
        ].join(" | ");

        return record(
            item.area_name || "Unknown county",
            period,
            supplierCoverageStatus(item)
        );
    }

    function renderOrder(item) {
        const lines = [
            `Order date: ${dateText(item.order_date)}`,
            `Expected delivery: ${dateText(item.expected_delivery_date)}`,
            `Items: ${item.item_count} | Units ordered: ${item.quantity_ordered}`,
            `Units received: ${item.quantity_received}`,
            `Order value: ${moneyText(item.order_value)}`,
            item.branch_name ? `Branch: ${item.branch_name}` : ""
        ].filter(Boolean);

        return record(
            `Supply order #${item.supply_order_id}`,
            lines.join("\n"),
            item.status
        );
    }

    function renderPromotion(item) {
        const lines = [
            item.description || "",
            `From: ${item.owner_name}`,
            `Dates: ${dateText(item.start_date)} to ${dateText(item.end_date)}`,
            `Products: ${item.product_count}`,
            `Participation: ${humanize(item.participation_status)}`
        ].filter(Boolean);

        return record(item.promotion_name, lines.join("\n"), item.status);
    }

    function initials(name) {
        return String(name || "")
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(word => word.charAt(0))
            .join("")
            .toUpperCase() || "?";
    }

    function logoImageUrl(path) {
        if (
            typeof path !== "string" ||
            !/^uploads\/organization-logos\/org-\d+-[a-f0-9]{32}\.(jpg|png|webp)$/.test(path)
        ) {
            return null;
        }

        return new URL("../../../../" + path, window.location.href).href;
    }

    function makePartnerAvatar(relationship) {
        const avatar = el(
            "span",
            "workspace-partner-avatar",
            initials(relationship.partner_name)
        );

        const source = logoImageUrl(relationship.partner_logo_path);

        if (!source) return avatar;

        avatar.classList.add("has-logo");

        const image = el("img");
        image.src = source;
        image.alt = "";
        image.loading = "lazy";

        image.addEventListener("error", () => {
            avatar.classList.remove("has-logo");
            avatar.replaceChildren();
            avatar.textContent = initials(relationship.partner_name);
        }, { once: true });

        avatar.replaceChildren(image);
        return avatar;
    }

    function directionLabel(relationship) {
        return Number(relationship.i_supply) === 1
            ? "You supply them"
            : "They supply you";
    }

    function renderPartnerList() {
        const term = partnerSearch.value.trim().toLowerCase();

        const matches = activeRelationships.filter(relationship => {
            const searchable = [
                relationship.partner_name,
                typeLabel(relationship.partner_type),
                directionLabel(relationship)
            ].join(" ").toLowerCase();

            return searchable.includes(term);
        });

        partnerCount.textContent = String(matches.length);
        partnerList.replaceChildren();

        if (matches.length === 0) {
            partnerList.append(
                el(
                    "p",
                    "workspace-partner-empty",
                    activeRelationships.length
                        ? "No relationships match this search."
                        : "No active trading relationships are available."
                )
            );
            return;
        }

        matches.forEach(relationship => {
            const id = Number(relationship.relationship_id);
            const selected = id === selectedRelationshipId;

            const button = el(
                "button",
                `workspace-partner-item${selected ? " is-selected" : ""}`
            );

            button.type = "button";
            button.setAttribute("aria-pressed", String(selected));
            button.setAttribute(
                "aria-label",
                `${relationship.partner_name}, ${typeLabel(relationship.partner_type)}, ${directionLabel(relationship)}, Active`
            );

            const copy = el("span", "workspace-partner-copy");

            copy.append(
                el(
                    "span",
                    "workspace-partner-name",
                    relationship.partner_name || "Organisation"
                ),
                el(
                    "span",
                    "workspace-partner-meta",
                    `${typeLabel(relationship.partner_type)} | ${directionLabel(relationship)}`
                )
            );

            button.append(
                makePartnerAvatar(relationship),
                copy,
                el("span", "workspace-partner-status", "Active")
            );

            button.addEventListener("click", () => {
                if (id === selectedRelationshipId) return;

                selectedRelationshipId = id;
                renderPartnerList();
                loadWorkspace(id);
            });

            partnerList.append(button);
        });
    }

    function populateDistributionAreaSelect(areas) {
        activeDistributionAreas = Array.isArray(areas) ? areas : [];

        const requestedId = Number(selectedDistributionAreaId || 0);
        const hasSelection = activeDistributionAreas.some(
            area => Number(area.area_id) === requestedId
        );

        distributionAreaSelect.replaceChildren(
            new Option("Choose a distribution county", "")
        );

        activeDistributionAreas.forEach(area => {
            distributionAreaSelect.add(
                new Option(area.area_name, String(area.area_id))
            );
        });

        distributionAreaSelect.value = hasSelection ? String(requestedId) : "";

        if (!hasSelection) {
            selectedDistributionAreaId = 0;
        }

        const isBranchAdmin = currentAccount?.user?.role === "BRANCH_ADMIN";

        distributionAreaSelect.disabled =
            isBranchAdmin || activeDistributionAreas.length === 0;

        if (isBranchAdmin) {
            distributionAreaNote.textContent = selectedBranch?.area_name
                ? "The distribution county is fixed to this branch for branch administrators."
                : "This branch has no county assigned. Ask your organisation administrator to map the branch before viewing regional products.";
        } else {
            distributionAreaNote.textContent =
                "Choose the county whose distribution appointments you want to view. This is independent of the branch location.";
        }
    }

    async function loadWorkspace(relationshipId) {
        const requestId = ++workspaceRequestSequence;

        selectionPrompt.hidden = false;
        selectionPrompt.textContent = "Loading relationship information...";
        workspaceContent.hidden = true;
        message.textContent = "";
        message.className = "form-message";

        try {
            const result = await getJson(
                `network/workspace.php?relationshipId=${encodeURIComponent(relationshipId)}&branchId=${encodeURIComponent(selectedBranchId)}&areaId=${encodeURIComponent(selectedDistributionAreaId || "")}`
            );

            if (requestId !== workspaceRequestSequence) return;

            if (!result.ok || result.data?.success !== true) {
                showMessage(
                    message,
                    result.data?.error || "Unable to load this relationship.",
                    "error"
                );

                selectionPrompt.textContent =
                    "The selected relationship workspace could not be loaded.";
                return;
            }

            const data = result.data;

            selectedDistributionAreaId =
                data.distribution_area_id === null ||
                data.distribution_area_id === undefined
                    ? 0
                    : Number(data.distribution_area_id);

            populateDistributionAreaSelect(data.areas || []);

            renderRelationship(data.relationship);

            renderRecords(
                supplierCoverageList,
                supplierCoverageCount,
                Array.isArray(data.supplier_coverage) ? data.supplier_coverage : [],
                data.supplier_coverage_visible
                    ? "This supplier has not declared any county coverage."
                    : "Supplier-declared coverage is not available for this relationship.",
                renderSupplierCoverage
            );

            renderRecords(
                productsList,
                productsCount,
                Array.isArray(data.products) ? data.products : [],
                selectedDistributionAreaId
                    ? "No active product appointments are recorded for this distribution county and supplier."
                    : currentAccount?.user?.role === "BRANCH_ADMIN"
                        ? "This branch has no county assigned. Contact your organisation administrator before viewing regional products."
                        : "Choose a distribution county to view active regional product appointments.",
                renderProduct
            );

            renderRecords(
                ordersList,
                ordersCount,
                Array.isArray(data.orders) ? data.orders : [],
                "No supply orders are recorded between these two organisations yet.",
                renderOrder
            );

            renderRecords(
                promotionsList,
                promotionsCount,
                Array.isArray(data.promotions) ? data.promotions : [],
                "No relevant approved or active promotions were found for this relationship.",
                renderPromotion
            );

            workspaceContent.hidden = false;
            selectionPrompt.hidden = true;
            message.textContent = "";
            message.className = "form-message";

            const url = new URL(window.location.href);
            url.searchParams.set("relationshipId", String(relationshipId));
            url.searchParams.set("branchId", String(selectedBranchId));
            url.searchParams.set("areaId", String(selectedDistributionAreaId || ""));
            window.history.replaceState(window.history.state, "", url.href);
        } catch (error) {
            if (requestId !== workspaceRequestSequence) return;

            showMessage(
                message,
                "Unable to load relationship information. Check your connection and try again.",
                "error"
            );

            selectionPrompt.textContent =
                "The selected relationship workspace could not be loaded.";
        }
    }

    function updateBranchContext() {
        selectedBranch = activeBranches.find(
            branch => Number(branch.branch_id) === Number(selectedBranchId)
        ) || null;

        branchCounty.textContent = selectedBranch && selectedBranch.area_name
            ? `Physical branch location: ${selectedBranch.area_name}. Distribution county is selected separately.`
            : "Branch county is not recorded. Organisation administrators can still select a distribution county.";
    }

    function populateBranchSelect() {
        branchSelect.replaceChildren();

        activeBranches.forEach(branch => {
            const label = `${branch.branch_name} | ${branch.area_name || "County not assigned"}`;
            branchSelect.add(new Option(label, String(branch.branch_id)));
        });

        branchSelect.value = String(selectedBranchId || "");
        updateBranchContext();
    }

    async function initialiseWorkspace(account) {
        currentAccount = account;
        try {
            const branchResult = await getJson("branches/list.php");

            if (!branchResult.ok || branchResult.data?.success !== true) {
                throw new Error(branchResult.data?.error || "Unable to load branches.");
            }

            activeBranches = (branchResult.data.branches || [])
                .filter(branch => branch.status === "ACTIVE")
                .sort((a, b) => {
                    const mainDifference = Number(b.is_main_branch) - Number(a.is_main_branch);
                    return mainDifference || String(a.branch_name).localeCompare(String(b.branch_name));
                });

            if (activeBranches.length === 0) {
                branchSelect.disabled = true;
                branchCounty.textContent = "No active branch is available for this organisation.";
                selectionPrompt.textContent = "Create or activate a branch before viewing regional products.";
                showMessage(message, "No active branch is available for this organisation.", "error");
                return;
            }

            const params = new URLSearchParams(window.location.search);
            const requestedBranchId = Number(params.get("branchId"));
            const accountBranchId = Number(account?.user?.branch_id || 0);
            const preferredBranchId = account?.user?.role === "BRANCH_ADMIN"
                ? accountBranchId
                : requestedBranchId;

            selectedBranchId = activeBranches.some(
                branch => Number(branch.branch_id) === preferredBranchId
            )
                ? preferredBranchId
                : Number(activeBranches[0].branch_id);

            const branchForContext = activeBranches.find(
                branch => Number(branch.branch_id) === Number(selectedBranchId)
            ) || null;

            selectedDistributionAreaId = params.has("areaId")
                ? (Number(params.get("areaId")) || 0)
                : Number(branchForContext?.area_id || 0);

            if (account?.user?.role === "BRANCH_ADMIN") {
                selectedDistributionAreaId = Number(branchForContext?.area_id || 0);
            }

            populateBranchSelect();

            const result = await getJson("network/list.php");

            if (!result.ok || result.data?.success !== true) {
                showMessage(
                    message,
                    result.data?.error || "Unable to load active relationships.",
                    "error"
                );

                selectionPrompt.textContent = "Active relationships could not be loaded.";
                return;
            }

            activeRelationships = (result.data.relationships || [])
                .filter(relationship => relationship.status === "ACTIVE")
                .sort((a, b) =>
                    String(a.partner_name).localeCompare(String(b.partner_name))
                );

            const requestedRelationshipId = Number(params.get("relationshipId"));

            if (activeRelationships.length === 0) {
                renderPartnerList();
                selectionPrompt.textContent =
                    "No active relationships are available. Establish a relationship through Supply Network.";
                return;
            }

            selectedRelationshipId = activeRelationships.some(
                relationship => Number(relationship.relationship_id) === requestedRelationshipId
            )
                ? requestedRelationshipId
                : Number(activeRelationships[0].relationship_id);

            renderPartnerList();
            await loadWorkspace(selectedRelationshipId);
        } catch (error) {
            showMessage(
                message,
                error.message || "Unable to initialise the relationship workspace.",
                "error"
            );
            selectionPrompt.textContent = "The workspace could not be initialised.";
        }
    }

    partnerSearch.addEventListener("input", renderPartnerList);

    branchSelect.addEventListener("change", () => {
        selectedBranchId = Number(branchSelect.value);
        updateBranchContext();

        const url = new URL(window.location.href);
        url.searchParams.set("branchId", String(selectedBranchId));
        url.searchParams.set("areaId", String(selectedDistributionAreaId || ""));
        window.history.replaceState(window.history.state, "", url.href);

        if (selectedRelationshipId) {
            loadWorkspace(selectedRelationshipId);
        }
    });

    distributionAreaSelect.addEventListener("change", () => {
        if (currentAccount?.user?.role === "BRANCH_ADMIN") return;

        selectedDistributionAreaId =
            Number(distributionAreaSelect.value) || 0;

        const url = new URL(window.location.href);
        url.searchParams.set("areaId", String(selectedDistributionAreaId || ""));
        window.history.replaceState(window.history.state, "", url.href);

        if (selectedRelationshipId) {
            loadWorkspace(selectedRelationshipId);
        }
    });

    shellReady.then(account => {
        if (account) initialiseWorkspace(account);
    });
})();