(function () {
    "use strict";

    const state = { products: [], selectedProductId: null, currentVersionId: null, pendingCascade: null, organizationId: null, organizationType: "" };

    const el = {
        list: document.getElementById("productListContainer"),
        search: document.getElementById("catalogueSearch"),
        listCount: document.getElementById("listCount"),
        
        name: document.getElementById("detailProductName"),
        barcode: document.getElementById("detailBarcode"),
        manufacturer: document.getElementById("detailManufacturer"),
        category: document.getElementById("detailCategory"),
        version: document.getElementById("detailVersion"),
        packSize: document.getElementById("detailPackSize"),
        image: document.getElementById("detailImage"),
        imageFallback: document.getElementById("imageFallbackText"),
        stock: document.getElementById("detailStock"),
        recordVersion: document.getElementById("recordVersion"),
        recordOfferStatus: document.getElementById("recordOfferStatus"),
        
        cost: document.getElementById("inlineCost"),
        selling: document.getElementById("inlineSelling"),
        margin: document.getElementById("inlineMargin"),
        profit: document.getElementById("inlineProfit"),
        saveBtn: document.getElementById("saveInlinePriceBtn"),
        simulateBtn: document.getElementById("simulateHikeBtn"),
        preview: document.getElementById("priceCascadePreview"),
        previewTitle: document.getElementById("priceCascadeTitle"),
        previewDescription: document.getElementById("priceCascadeDescription"),
        previewWarnings: document.getElementById("priceCascadeWarnings"),
        previewWarningList: document.getElementById("priceCascadeWarningList"),
        previewRows: document.getElementById("priceCascadeRows"),
        branchList: document.getElementById("priceCascadeBranchList"),
        noBranches: document.getElementById("priceCascadeNoBranches"),
        applyCascadeBtn: document.getElementById("applyPriceCascadeBtn"),
        cancelCascadeBtn: document.getElementById("cancelPriceCascadeBtn"),
        confirmDialog: document.getElementById("priceChangeConfirmDialog"),
        confirmMessage: document.getElementById("priceChangeConfirmMessage"),
        confirmCancelBtn: document.getElementById("priceChangeCancelBtn"),
        confirmProceedBtn: document.getElementById("priceChangeProceedBtn"),
        passwordDialog: document.getElementById("priceAdminPasswordDialog"),
        passwordInput: document.getElementById("priceAdminPasswordInput"),
        passwordMessage: document.getElementById("priceAdminPasswordMessage"),
        passwordCancelBtn: document.getElementById("priceAdminCancelBtn"),
        passwordVerifyBtn: document.getElementById("priceAdminVerifyBtn"),
        passwordToggleBtn: document.getElementById("priceAdminPasswordToggle")
    };

    const escapeHtml = (str) => (str || "").toString().replace(/</g, "&lt;").replace(/>/g, "&gt;");

    let originalCostPrice = "";
    let originalSellingPrice = "";
    let pricingLoaded = false;

    function normalizePriceForComparison(value) {
        const raw = String(value ?? "").trim();
        if (raw === "") return "";
        const amount = Number(raw);
        return Number.isFinite(amount) ? amount.toFixed(2) : `invalid:${raw}`;
    }

    function updateSaveButtonState() {
        if (!el.saveBtn || !el.cost || !el.selling) return;

        const hasChanges = pricingLoaded && (
            normalizePriceForComparison(el.cost.value) !== originalCostPrice ||
            normalizePriceForComparison(el.selling.value) !== originalSellingPrice
        );

        el.saveBtn.disabled = !hasChanges;
        el.saveBtn.setAttribute("aria-disabled", String(!hasChanges));
    }
    async function init() {
        // Fail-safe: Only attach listeners if the elements exist
        if (el.cost) el.cost.addEventListener("input", () => {
            calcMargin();
            updateSaveButtonState();
        });
        if (el.selling) el.selling.addEventListener("input", () => {
            calcMargin();
            updateSaveButtonState();
        });
        if (el.saveBtn) el.saveBtn.addEventListener("click", savePrices);
        if (el.simulateBtn) el.simulateBtn.addEventListener("click", simulatePriceHike);
        if (el.applyCascadeBtn) el.applyCascadeBtn.addEventListener("click", applyPriceCascade);
        if (el.cancelCascadeBtn) el.cancelCascadeBtn.addEventListener("click", closePriceCascadePreview);
        if (el.search) el.search.addEventListener("input", renderList);
        
        await fetchProducts();
    }

    async function fetchProducts() {
        try {
            const response = await getJson("products/list.php");
            const data = response.data || response;
            state.organizationId = Number(data.organization_id) || null;
            state.organizationType = String(data.organization_type || "");
            if (data && data.products && data.products.length > 0) {
                state.products = data.products;
                el.listCount.textContent = state.products.length;
                renderList();
                selectProduct(state.products[0].product_id);
            }
        } catch (e) { console.error(e); }
    }

    function renderList() {
        el.list.innerHTML = "";
        const query = el.search.value.toLowerCase();
        state.products.forEach(p => {
            const v = p.versions && p.versions.length > 0 ? p.versions[0] : null;
            const text = `${p.product_name} ${p.manufacturer_name} ${v ? v.barcode : ''}`.toLowerCase();
            if (query && !text.includes(query)) return;

            const isActive = p.product_id === state.selectedProductId;
            const barcode = v ? v.barcode : 'No Barcode';
            
            const div = document.createElement("div");
            div.className = `product-item ${isActive ? 'active' : ''}`;
            
            div.innerHTML = `
                <img class="item-img" src="${p.image_path ? '../../../' + p.image_path : ''}" onerror="this.style.display='none'">
                <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                    <div class="item-title">${escapeHtml(p.product_name)}</div>
                    <div class="item-sub">Stock: ${p.stock || 0}</div>
                </div>
            `;
            div.addEventListener("click", () => selectProduct(p.product_id));
            el.list.appendChild(div);
        });
    }

    function selectProduct(id) {
        closePriceCascadePreview();
        state.selectedProductId = id;
        renderList();
        const p = state.products.find(x => x.product_id === id);
        if (!p) return;
        updatePriceLabels(p);

        const v = p.versions && p.versions.length > 0 ? p.versions[0] : null;
        state.currentVersionId = v ? v.product_version_id : null;

        el.name.textContent = p.product_name;
        el.barcode.textContent = v ? v.barcode : 'N/A';
        el.manufacturer.textContent = p.manufacturer_name;
        el.category.textContent = p.category || 'N/A';
        el.version.textContent = v ? `Version ${v.version_number}` : 'N/A';

        if (el.recordVersion) {
            el.recordVersion.textContent = v ? `Version ${v.version_number}` : 'N/A';
        }

        if (el.recordOfferStatus) {
            el.recordOfferStatus.textContent = 'Not available';
        }
        el.packSize.textContent = v ? (v.pack_size || v.packaging_description || 'N/A') : 'N/A';
        
        el.stock.textContent = p.stock || 0;
        el.stock.style.color = p.stock <= 0 ? '#dc2626' : '#202b38';

        if (p.image_path) {
            el.image.src = "../../../" + p.image_path;
            el.image.style.display = "block";
            if (el.imageFallback) el.imageFallback.style.display = "none";
        } else {
            el.image.style.display = "none";
            if (el.imageFallback) el.imageFallback.style.display = "block";
        }

        if (state.currentVersionId) {
            loadPricing(state.currentVersionId);
        }
    }

    function updatePriceLabels(product) {
        const label = document.getElementById("inlineCostLabel");
        if (!label) return;
    
        const canManufacture =
            state.organizationType === "MANUFACTURER" ||
            state.organizationType === "MANUFACTURER_SUPPLIER";
        const isOwnProduct =
            Number(product.manufacturer_id) === Number(state.organizationId);
    
        label.textContent =
            canManufacture && isOwnProduct ? "Production Cost" : "Purchase Cost";
    }
    
    function calcMargin() {
        const cost = parseFloat(el.cost.value) || 0;
        const selling = parseFloat(el.selling.value) || 0;
        const netCost = cost / 1.16;
        const netSelling = selling / 1.16;
        const profit = netSelling - netCost;
        const margin = netSelling > 0 ? (profit / netSelling) * 100 : 0;
        
        el.profit.textContent = profit.toFixed(2);
        el.margin.textContent = margin.toFixed(1) + "%";
        el.profit.style.color = profit < 0 ? '#dc2626' : '#16a34a';
    }

    async function loadPricing(versionId) {
        pricingLoaded = false;
        originalCostPrice = "";
        originalSellingPrice = "";
        updateSaveButtonState();

        el.cost.value = "";
        el.selling.value = "";
        calcMargin();

        try {
            const res = await getJson(`products/price-list.php?product_version_id=${versionId}`);
            const data = res.data || res;

            if (res.ok === false || data.success === false) {
                throw new Error(data.error || data.message || "Unable to load current prices.");
            }

            if (data && data.prices) {
                data.prices.forEach(p => {
                    if (p.price_type === "COST") el.cost.value = p.price_amount;
                    if (p.price_type === "SELLING") el.selling.value = p.price_amount;
                });
            }

            originalCostPrice = normalizePriceForComparison(el.cost.value);
            originalSellingPrice = normalizePriceForComparison(el.selling.value);
            pricingLoaded = true;

            calcMargin();
            updateSaveButtonState();
            return true;
        } catch (e) {
            console.error(e);
            pricingLoaded = false;
            updateSaveButtonState();
            return false;
        }
    }

    function requestManufacturerPriceConfirmation(productName, oldValue, newValue) {
        const dialog = el.confirmDialog;

        if (
            !dialog ||
            !el.confirmMessage ||
            !el.confirmCancelBtn ||
            !el.confirmProceedBtn ||
            typeof dialog.showModal !== "function"
        ) {
            alert("Price confirmation is unavailable. No prices were saved.");
            return Promise.resolve(false);
        }

        const money = value => Number(value).toLocaleString("en-KE", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        el.confirmMessage.textContent =
            `Change ${productName} from KSh ${money(oldValue)} to KSh ${money(newValue)}? This may affect other supply-chain partners.`;

        return new Promise(resolve => {
            const finish = confirmed => {
                el.confirmCancelBtn.removeEventListener("click", onCancel);
                el.confirmProceedBtn.removeEventListener("click", onConfirm);
                dialog.removeEventListener("cancel", onDialogCancel);

                if (dialog.open) dialog.close();
                resolve(confirmed);
            };

            const onCancel = () => finish(false);
            const onConfirm = () => finish(true);
            const onDialogCancel = event => {
                event.preventDefault();
                finish(false);
            };

            el.confirmCancelBtn.addEventListener("click", onCancel);
            el.confirmProceedBtn.addEventListener("click", onConfirm);
            dialog.addEventListener("cancel", onDialogCancel);

            dialog.showModal();
        });
    }
    function requestAdministratorPassword() {
        const dialog = el.passwordDialog;
        const input = el.passwordInput;
        const message = el.passwordMessage;
        const cancelButton = el.passwordCancelBtn;
        const verifyButton = el.passwordVerifyBtn;
        const toggleButton = el.passwordToggleBtn;

        if (
            !dialog || !input || !message || !cancelButton ||
            !verifyButton || !toggleButton ||
            typeof dialog.showModal !== "function"
        ) {
            alert("Administrator password verification is unavailable. No prices were saved.");
            return Promise.resolve(null);
        }

        input.value = "";
        input.type = "password";
        toggleButton.textContent = "Show";
        toggleButton.setAttribute("aria-pressed", "false");
        message.textContent = "Enter your administrator password to approve this price change.";

        return new Promise(resolve => {
            let finished = false;

            const finish = password => {
                if (finished) return;
                finished = true;

                cancelButton.removeEventListener("click", onCancel);
                verifyButton.removeEventListener("click", onVerify);
                toggleButton.removeEventListener("click", onToggleVisibility);
                input.removeEventListener("keydown", onKeyDown);
                dialog.removeEventListener("cancel", onDialogCancel);

                if (dialog.open) dialog.close();

                input.value = "";
                input.type = "password";
                toggleButton.textContent = "Show";
                toggleButton.setAttribute("aria-pressed", "false");

                resolve(password);
            };

            const onCancel = () => finish(null);

            const onToggleVisibility = () => {
                const showPassword = input.type === "password";
                input.type = showPassword ? "text" : "password";
                toggleButton.textContent = showPassword ? "Hide" : "Show";
                toggleButton.setAttribute("aria-pressed", String(showPassword));
                input.focus();
            };

            const onVerify = () => {
                const password = input.value;

                if (!password) {
                    message.textContent = "Enter your administrator password.";
                    input.focus();
                    return;
                }

                finish(password);
            };

            const onKeyDown = event => {
                if (event.key === "Enter") {
                    event.preventDefault();
                    onVerify();
                }
            };

            const onDialogCancel = event => {
                event.preventDefault();
                finish(null);
            };

            cancelButton.addEventListener("click", onCancel);
            verifyButton.addEventListener("click", onVerify);
            toggleButton.addEventListener("click", onToggleVisibility);
            input.addEventListener("keydown", onKeyDown);
            dialog.addEventListener("cancel", onDialogCancel);

            dialog.showModal();
            input.focus();
        });
    }
    async function savePrices() {
        if (!pricingLoaded || !state.currentVersionId) {
            alert("Current prices are still loading. Please try again.");
            return;
        }

        const cost = el.cost.value.trim();
        const selling = el.selling.value.trim();

        const costChanged =
            normalizePriceForComparison(cost) !== originalCostPrice;
        const sellingChanged =
            normalizePriceForComparison(selling) !== originalSellingPrice;

        if (!costChanged && !sellingChanged) {
            updateSaveButtonState();
            return;
        }

        if (costChanged && !cost) {
            alert("A cost price cannot be cleared. Enter a valid amount.");
            updateSaveButtonState();
            return;
        }

        if (sellingChanged && !selling) {
            alert("A selling price cannot be cleared. Enter a valid amount.");
            updateSaveButtonState();
            return;
        }

        const selectedProduct = state.products.find(
            product => Number(product.product_id) === Number(state.selectedProductId)
        );

        const isOwnManufacturedProduct = Boolean(
            selectedProduct &&
            ["MANUFACTURER", "MANUFACTURER_SUPPLIER"].includes(state.organizationType) &&
            Number(selectedProduct.manufacturer_id) === Number(state.organizationId)
        );

        let confirmationPassword = "";

        if (isOwnManufacturedProduct && sellingChanged) {
            const confirmed = await requestManufacturerPriceConfirmation(
                selectedProduct.product_name || "this product",
                originalSellingPrice,
                selling
            );

            if (!confirmed) {
                el.cost.value = originalCostPrice;
                el.selling.value = originalSellingPrice;
                calcMargin();
                updateSaveButtonState();
                return;
            }
        }

        if (isOwnManufacturedProduct && (costChanged || sellingChanged)) {
            confirmationPassword = await requestAdministratorPassword();

            if (!confirmationPassword) {
                updateSaveButtonState();
                return;
            }
        }
        const dateParts = new Intl.DateTimeFormat("en-CA", {
            timeZone: "Africa/Nairobi",
            year: "numeric",
            month: "2-digit",
            day: "2-digit"
        }).formatToParts(new Date()).reduce((parts, part) => {
            if (["year", "month", "day"].includes(part.type)) {
                parts[part.type] = part.value;
            }
            return parts;
        }, {});

        const today = `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
        const versionId = state.currentVersionId;
        let costSaved = false;
        let sellingSaved = false;
        let successMessage = "Price save completed successfully.";

        async function saveOnePrice(payload) {
            if (isOwnManufacturedProduct) {
                payload.price_confirmation_password = confirmationPassword;
            }

            const response = await postJson("products/price-save.php", payload);
            const data = response.data || response;

            if (response.ok === false || data.success !== true) {
                throw new Error(data.error || data.message || "The price could not be saved.");
            }

            return data;
        }

        el.saveBtn.disabled = true;
        el.saveBtn.textContent = "Saving...";

        try {
            if (costChanged) {
                await saveOnePrice({
                    product_version_id: versionId,
                    price_type: "COST",
                    price_amount: parseFloat(cost),
                    price_includes_vat: true,
                    effective_from: today
                });
                costSaved = true;
            }

            if (sellingChanged) {
                const sellingResult = await saveOnePrice({
                    product_version_id: versionId,
                    price_type: "SELLING",
                    price_amount: parseFloat(selling),
                    price_includes_vat: true,
                    effective_from: today
                });
                sellingSaved = true;

                if (sellingResult.message) {
                    successMessage = sellingResult.message;
                }
            }

            const refreshed = await loadPricing(versionId);

            if (refreshed) {
                alert(`${successMessage} The displayed prices have been refreshed.`);
                el.saveBtn.textContent = "Saved!";
            } else {
                alert("The price save completed, but the displayed prices could not be refreshed. Reopen the product to verify its saved values.");
                el.saveBtn.textContent = "Saved";
            }

            setTimeout(() => {
                el.saveBtn.textContent = "Save prices";
                updateSaveButtonState();
            }, 2000);
        } catch (error) {
            const detail = error.message || "Unknown error.";

            if (costChanged && costSaved && sellingChanged && !sellingSaved) {
                alert("The cost price was saved, but the selling-price save failed. Check the saved prices before retrying. " + detail);
            } else {
                alert("Unable to save the changed price. " + detail);
            }

            el.saveBtn.textContent = "Save prices";
            updateSaveButtonState();
        } finally {
            confirmationPassword = "";
        }
    }

function formatCascadeMoney(value) {
    const amount = Number(value);
    return Number.isFinite(amount) ? `KSh ${amount.toLocaleString("en-KE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}` : "Not available";
}

function formatCascadeMargin(value) {
    const amount = Number(value);
    return Number.isFinite(amount) ? `${amount.toFixed(2)}%` : "Not available";
}

function cascadeRoleLabel(type) {
    const labels = {
        MANUFACTURER: "Manufacturer",
        MANUFACTURER_SUPPLIER: "Manufacturer and supplier",
        SUPPLIER: "Supplier",
        RETAILER: "Retailer"
    };
    return labels[type] || String(type || "Organisation");
}

function closePriceCascadePreview() {
    state.pendingCascade = null;
    if (el.preview) el.preview.hidden = true;
    if (el.previewRows) el.previewRows.replaceChildren();
    if (el.previewWarningList) el.previewWarningList.replaceChildren();
    if (el.branchList) el.branchList.replaceChildren();
    if (el.previewWarnings) el.previewWarnings.hidden = true;
    if (el.noBranches) el.noBranches.hidden = true;
}

function renderPriceCascadePreview(plan) {
    if (!el.preview || !el.previewRows || !el.previewTitle || !el.previewDescription) {
        throw new Error("The price preview panel is missing from Catalogue.");
    }

    el.previewTitle.textContent = `Price impact: ${plan.product.product_name}`;
    el.previewDescription.textContent =
        `Version ${plan.product.version_number} \u00B7 Manufacturer price ${formatCascadeMoney(plan.manufacturer_new_price)} \u00B7 Effective ${plan.effective_date}. Review every affected organisation before applying.`;

    el.previewRows.replaceChildren();

    for (const organization of (plan.organizations || [])) {
        const row = document.createElement("tr");
        const cells = [
            organization.organization_name,
            cascadeRoleLabel(organization.organization_type),
            `${formatCascadeMoney(organization.old_cost_price)} \u2192 ${formatCascadeMoney(organization.new_cost_price)}`,
            `${formatCascadeMoney(organization.old_selling_price)} \u2192 ${formatCascadeMoney(organization.new_selling_price)}`,
            `${formatCascadeMargin(organization.gross_margin_before_percent)} \u2192 ${formatCascadeMargin(organization.gross_margin_after_percent)}`,
            organization.rounding_rule === "MULTIPLE_OF_5"
                ? "Multiple of KSh 5"
                : organization.rounding_rule === "ENDS_IN_99"
                    ? "Ends in .99"
                    : "Not applicable",
            organization.organization_type === "RETAILER"
                ? `${Number(organization.branch_prices_found || 0)} active branch price(s)`
                : "Not applicable"
        ];

        for (const value of cells) {
            const cell = document.createElement("td");
            cell.textContent = String(value ?? "Not available");
            row.appendChild(cell);
        }

        el.previewRows.appendChild(row);
    }

    const warnings = Array.isArray(plan.warnings) ? plan.warnings : [];
    el.previewWarningList.replaceChildren();
    el.previewWarnings.hidden = warnings.length === 0;

    for (const warning of warnings) {
        const item = document.createElement("li");
        item.textContent = warning;
        el.previewWarningList.appendChild(item);
    }

    const branches = Array.isArray(plan.branch_changes) ? plan.branch_changes : [];
    el.branchList.replaceChildren();
    el.noBranches.hidden = branches.length > 0;

    for (const branch of branches) {
        const item = document.createElement("li");
        const label = document.createElement("span");
        const values = document.createElement("strong");

        label.textContent = `${branch.organization_name} \u00B7 ${branch.branch_name} \u00B7 ${branch.price_basis}`;
        values.textContent = `${formatCascadeMoney(branch.old_price)} \u2192 ${formatCascadeMoney(branch.new_price)}`;
        item.append(label, values);
        el.branchList.appendChild(item);
    }

    el.preview.hidden = false;
    el.preview.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

async function simulatePriceHike() {
    if (!state.currentVersionId) {
        alert("Select a product first.");
        return;
    }

    const priceInput = document.getElementById("manufacturerNewPrice");
    const newPrice = Number(priceInput?.value);

    if (
        !priceInput ||
        priceInput.value.trim() === "" ||
        !Number.isFinite(newPrice) ||
        newPrice <= 0
    ) {
        alert("Enter a valid manufacturer price greater than zero.");
        priceInput?.focus();
        return;
    }

    const originalText = el.simulateBtn.textContent;
    el.simulateBtn.textContent = "Building preview...";
    el.simulateBtn.disabled = true;

    try {
        const response = await postJson("products/price-cascade-preview.php", {
            product_version_id: state.currentVersionId,
            mfg_new_price: newPrice
        });

        const data = response.data || response;
        if (data.success !== true || !data.plan) {
            throw new Error(data.error || data.message || "Unable to build the price preview.");
        }

        state.pendingCascade = {
            product_version_id: state.currentVersionId,
            mfg_new_price: newPrice,
            plan_signature: data.plan.signature,
            plan: data.plan
        };

        renderPriceCascadePreview(data.plan);
    } catch (error) {
        alert(error.message || "Unable to build the price preview.");
        console.error("Price cascade preview failed:", error);
    } finally {
        el.simulateBtn.textContent = originalText;
        el.simulateBtn.disabled = false;
    }
}

async function applyPriceCascade() {
    const pending = state.pendingCascade;
    if (!pending) {
        alert("Run Simulate change and review the preview first.");
        return;
    }

    if (pending.product_version_id !== state.currentVersionId) {
        closePriceCascadePreview();
        alert("The selected product changed. Run the simulation again.");
        return;
    }

    const originalText = el.applyCascadeBtn.textContent;
    el.applyCascadeBtn.textContent = "Applying...";
    el.applyCascadeBtn.disabled = true;
    if (el.cancelCascadeBtn) el.cancelCascadeBtn.disabled = true;

    try {
        let cascadePassword = await requestAdministratorPassword();

        if (!cascadePassword) {
            return;
        }

        const response = await postJson("products/price-cascade.php", {
            product_version_id: pending.product_version_id,
            mfg_new_price: pending.mfg_new_price,
            plan_signature: pending.plan_signature,
            price_confirmation_password: cascadePassword
        });

        cascadePassword = "";

        const data = response.data || response;
        if (data.success !== true) {
            throw new Error(data.error || data.message || "Unable to apply the price change.");
        }

        const versionId = pending.product_version_id;
        const message = data.message || "The price change was applied.";
        closePriceCascadePreview();
        const priceInput = document.getElementById("manufacturerNewPrice");
        if (priceInput) priceInput.value = "";
        await loadPricing(versionId);
        alert(message);
    } catch (error) {
        alert(error.message || "Unable to apply the price change.");
        console.error("Price cascade apply failed:", error);
    } finally {
        if (el.applyCascadeBtn) {
            el.applyCascadeBtn.textContent = originalText;
            el.applyCascadeBtn.disabled = false;
        }
        if (el.cancelCascadeBtn) el.cancelCascadeBtn.disabled = false;
    }
}


    if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", init); } else { init(); }
})();