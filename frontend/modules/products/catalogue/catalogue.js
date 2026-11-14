(function () {
    "use strict";

    const state = { products: [], selectedProductId: null, currentVersionId: null, pendingCascade: null, organizationId: null, organizationType: "", recommendationData: null, activePromotions: [], cataloguePromotionsByVersion: {}, cataloguePromotionCache: {} };

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
        basis: document.getElementById("inlineBasis"),
        basisGroup: document.getElementById("inlineBasisGroup"),
        margin: document.getElementById("inlineMargin"),
        profit: document.getElementById("inlineProfit"),
        saveBtn: document.getElementById("saveInlinePriceBtn"),
        simulateBtn: document.getElementById("simulateHikeBtn"),
        cascadeActions: document.querySelector(".detail-actions"),
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
        passwordToggleBtn: document.getElementById("priceAdminPasswordToggle"),
        recommendationWorkspace: document.getElementById("priceRecommendationWorkspace"),
        rrpRecommendationRow: document.getElementById("rrpRecommendationRow"),
        rrpRecommendationAmount: document.getElementById("rrpRecommendationAmount"),
        rrpRecommendationMeta: document.getElementById("rrpRecommendationMeta"),
        rspRecommendationRow: document.getElementById("rspRecommendationRow"),
        rspRecommendationAmount: document.getElementById("rspRecommendationAmount"),
        rspRecommendationMeta: document.getElementById("rspRecommendationMeta"),
        recommendationComparison: document.getElementById("priceRecommendationComparison"),
        recommendationComparisonLabel: document.getElementById("priceRecommendationComparisonLabel"),
        recommendationComparisonValue: document.getElementById("priceRecommendationComparisonValue"),
        recommendationForm: document.getElementById("priceRecommendationForm"),
        recommendationType: document.getElementById("priceRecommendationType"),
        recommendationAmount: document.getElementById("priceRecommendationAmount"),
        recommendationEffectiveFrom: document.getElementById("priceRecommendationEffectiveFrom"),
        saveRecommendationBtn: document.getElementById("savePriceRecommendationBtn"),
        recommendationStatus: document.getElementById("priceRecommendationStatus"),
        recommendationHistoryWrap: document.getElementById("priceRecommendationHistoryWrap"),
        recommendationHistory: document.getElementById("priceRecommendationHistory"),
        activePromotionPrices: document.getElementById("activePromotionPrices"),
        activePromotionList: document.getElementById("activePromotionList")
    };


    let originalCostPrice = "";
    let originalCostIncludesVat = false;
    let originalSellingPrice = "";
    let originalBasis = "";
    let pricingLoaded = false;
    let priceSaveInProgress = false;
    let livePriceRefreshInProgress = false;
    let cataloguePageActive = true;
    const activePromotionRequests = new Set();

    function basisChanged() {
        return el.basis.value !== originalBasis;
    }

    function hasUnsavedPriceEdits() {
        return pricingLoaded && (
            normalizePriceForComparison(el.cost.value) !== originalCostPrice ||
            normalizePriceForComparison(el.selling.value) !== originalSellingPrice ||
            basisChanged()
        );
    }

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
            normalizePriceForComparison(el.selling.value) !== originalSellingPrice ||
            basisChanged()
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
            updateRecommendationComparison();
        });
        el.basis.addEventListener("change", () => {
            el.selling.value = el.basis.value === originalBasis ? originalSellingPrice : "";
            calcMargin();
            updateSaveButtonState();
            updateRecommendationComparison();
        });
        if (el.saveBtn) el.saveBtn.addEventListener("click", savePrices);
        if (el.simulateBtn) el.simulateBtn.addEventListener("click", simulatePriceHike);
        if (el.applyCascadeBtn) el.applyCascadeBtn.addEventListener("click", applyPriceCascade);
        if (el.cancelCascadeBtn) el.cancelCascadeBtn.addEventListener("click", closePriceCascadePreview);
        if (el.search) el.search.addEventListener("input", renderList);
        if (el.recommendationForm) el.recommendationForm.addEventListener("submit", saveRecommendation);
        if (el.recommendationType) el.recommendationType.addEventListener("change", updateRecommendationEditorAmount);
        
        await fetchProducts();
        if (!cataloguePageActive) return;
        startLivePriceRefresh();
        startActivePromotionRefresh();
    }

    async function fetchProducts() {
        try {
            const response = await getJson("products/list.php");
            const data = response.data || response;
            state.organizationId = Number(data.organization_id) || null;
            state.organizationType = String(data.organization_type || "");

            const canInitiateManufacturerCascade = [
                "MANUFACTURER",
                "MANUFACTURER_SUPPLIER"
            ].includes(state.organizationType);

            if (el.cascadeActions) {
                el.cascadeActions.style.display =
                    canInitiateManufacturerCascade ? "" : "none";
            }

            if (!canInitiateManufacturerCascade && el.preview) {
                el.preview.hidden = true;
            }
            if (data && Array.isArray(data.products)) {
                state.products = data.products;
                el.listCount.textContent = state.products.length;
                renderList();
                loadCataloguePromotionSummaries().catch(error =>
                    console.error("Unable to load catalogue promotion summaries:", error)
                );

                if (state.products.length > 0) {
                    selectProduct(state.products[0].product_id);
                }
            }
        } catch (e) { console.error(e); }
    }

    function getCurrentProductVersion(product) {
        const versions = Array.isArray(product?.versions) ? product.versions : [];
        return versions.find(version => Number(version.is_current) === 1)
            || versions[0]
            || null;
    }
    function renderList() {
        if (!el.list) return;

        el.list.replaceChildren();
        const query = String(el.search?.value || "").trim().toLowerCase();
        const filtered = state.products.filter(product => {
            const version = product.versions && product.versions.length > 0
                ? product.versions[0]
                : null;
            const searchable = `${product.product_name || ""} ${product.stock ?? 0}`.toLowerCase();
            return !query || searchable.includes(query);
        });

        if (filtered.length === 0) {
            const empty = document.createElement("p");
            empty.className = "catalogue-empty-state";
            empty.textContent = state.products.length
                ? "No products match your search."
                : "No products are available in this catalogue yet.";
            el.list.appendChild(empty);
            return;
        }

        const fragment = document.createDocumentFragment();

        filtered.forEach(product => {
            const isActive = String(product.product_id) === String(state.selectedProductId);
            const item = document.createElement("button");
            item.type = "button";
            item.className = `product-item${isActive ? " active" : ""}`;
            item.setAttribute("aria-pressed", String(isActive));
            item.setAttribute(
                "aria-label",
                `${product.product_name || "Product"}, stock ${product.stock ?? 0}`
            );

            if (product.image_path) {
                const image = document.createElement("img");
                image.className = "item-img";
                image.alt = "";
                image.loading = "lazy";
                image.src = "../../../" + String(product.image_path).replace(/^\/+/, "");
                image.addEventListener("error", () => {
                    const placeholder = document.createElement("span");
                    placeholder.className = "item-img-placeholder";
                    placeholder.setAttribute("aria-hidden", "true");
                    placeholder.textContent = "\u2014";
                    image.replaceWith(placeholder);
                }, { once: true });
                item.appendChild(image);
            } else {
                const placeholder = document.createElement("span");
                placeholder.className = "item-img-placeholder";
                placeholder.setAttribute("aria-hidden", "true");
                placeholder.textContent = "\u2014";
                item.appendChild(placeholder);
            }

            const copy = document.createElement("span");
            copy.className = "item-copy";

            const title = document.createElement("span");
            title.className = "item-title";
            title.textContent = product.product_name || "Unnamed product";

            const stock = document.createElement("span");
            stock.className = "item-sub";
            stock.textContent = `${product.stock ?? 0} in stock`;

            copy.append(title, stock);
            item.appendChild(copy);
            item.addEventListener("click", () => selectProduct(product.product_id));
            fragment.appendChild(item);
        });

        el.list.appendChild(fragment);
    }

    function renderCataloguePromotionSummary(copy, version) {
        if (state.organizationType !== "RETAILER" || !version) return;

        const versionId = String(version.product_version_id);
        const promotions = state.cataloguePromotionsByVersion[versionId];

        if (!Array.isArray(promotions) || promotions.length === 0) return;

        promotions.forEach(promotion => {
            const price = Number(promotion.promotion_price);
            const endDate = String(promotion.end_date || "");

            if (!Number.isFinite(price) || price <= 0 || endDate < todayNairobiDate()) {
                return;
            }

            const summary = document.createElement("span");
            summary.className = "item-promotion";

            const priceLabel = document.createElement("span");
            priceLabel.className = "item-promotion-price";
            priceLabel.textContent = `Promo KSh ${price.toLocaleString("en-KE", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;

            const meta = document.createElement("span");
            meta.className = "item-promotion-meta";

            const dateParts = endDate.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            const formattedEndDate = dateParts
                ? new Date(Number(dateParts[1]), Number(dateParts[2]) - 1, Number(dateParts[3]))
                    .toLocaleDateString("en-KE", {
                        day: "numeric",
                        month: "short",
                        year: "numeric"
                    })
                : endDate;

            const branchName = String(promotion.branch_name || "").trim();
            const showBranch = promotions.length > 1 && branchName;
            meta.textContent = `Ends ${formattedEndDate}${showBranch ? ` \u00B7 ${branchName}` : ""}`;

            summary.append(priceLabel, meta);
            copy.appendChild(summary);
        });
    }

    async function loadCataloguePromotionSummaries(forceRefresh = false) {
        if (state.organizationType !== "RETAILER") return;

        const versions = [...new Set(
            state.products
                .map(product => product.versions && product.versions.length > 0
                    ? String(getCurrentProductVersion(product).product_version_id)
                    : "")
                .filter(Boolean)
        )];

        let nextIndex = 0;
        const worker = async () => {
            while (nextIndex < versions.length) {
                const versionId = versions[nextIndex++];
                const cached = state.cataloguePromotionCache[versionId];

                if (!forceRefresh && cached && cached.expiresAt > Date.now()) {
                    state.cataloguePromotionsByVersion[versionId] = cached.promotions;
                    continue;
                }

                try {
                    const response = await getJson(
                        `products/active-promotions.php?product_version_id=${encodeURIComponent(versionId)}&_=${Date.now()}`
                    );
                    const data = response.data || response;
                    if (response.ok === false || data.success !== true) {
                        throw new Error(data.error || data.message || "Unable to load promotions.");
                    }

                    const today = String(data.today || todayNairobiDate());
                    const promotions = (Array.isArray(data.promotions) ? data.promotions : [])
                        .filter(item =>
                            String(item.start_date || "") <= today &&
                            String(item.end_date || "") >= today &&
                            Number(item.promotion_price) > 0
                        );

                    state.cataloguePromotionsByVersion[versionId] = promotions;
                    state.cataloguePromotionCache[versionId] = {
                        promotions,
                        expiresAt: Date.now() + 60000
                    };
                } catch (error) {
                    console.error(`Unable to load list promotions for version ${versionId}:`, error);
                }
            }
        };

        await Promise.all(Array.from({ length: Math.min(3, versions.length) }, () => worker()));
        renderList();
    }

    function selectProduct(id) {
        closePriceCascadePreview();
        state.selectedProductId = id;
        renderList();
        const p = state.products.find(x => x.product_id === id);
        if (!p) return;
        updatePriceLabels(p);

        const v = getCurrentProductVersion(p);
        state.currentVersionId = v ? v.product_version_id : null;

        el.name.textContent = p.product_name || "Unnamed product";
        el.image.alt = `${p.product_name || "Product"} image`;
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
            el.image.onerror = () => {
                el.image.style.display = "none";
                if (el.imageFallback) el.imageFallback.style.display = "flex";
            };
            el.image.onload = () => {
                el.image.style.display = "block";
                if (el.imageFallback) el.imageFallback.style.display = "none";
            };
            el.image.src = "../../../" + String(p.image_path).replace(/^\/+/, "");
            el.image.style.display = "block";
            if (el.imageFallback) el.imageFallback.style.display = "none";
        } else {
            el.image.onerror = null;
            el.image.onload = null;
            el.image.style.display = "none";
            if (el.imageFallback) el.imageFallback.style.display = "flex";
        }

        if (state.currentVersionId) {
            loadPricing(state.currentVersionId);
            loadPriceRecommendations(state.currentVersionId);
            loadActivePromotions(state.currentVersionId);
        } else {
            state.recommendationData = null;
            if (el.recommendationWorkspace) el.recommendationWorkspace.hidden = true;
            clearActivePromotions();
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
        el.basisGroup.hidden = !(canManufacture && isOwnProduct);
    }
    
    function calcMargin() {
        const cost = parseFloat(el.cost.value) || 0;
        const selling = parseFloat(el.selling.value) || 0;

        if (typeof state.vatPercent !== "number") {
            el.profit.textContent = "-";
            el.margin.textContent = "-";
            el.profit.style.color = "";
            el.margin.style.color = "";
            return;
        }

        const factor = 1 + state.vatPercent / 100;
        const ownManufacturer = !el.basisGroup.hidden;
        const costChanged =
            normalizePriceForComparison(el.cost.value) !== originalCostPrice;

        const costIncludesVat = ownManufacturer
            ? (costChanged ? state.vatPercent > 0 : originalCostIncludesVat)
            : true;

        const netCost = costIncludesVat ? cost / factor : cost;
        const netSelling = ownManufacturer && el.basis.value === "EXCLUSIVE"
            ? selling
            : selling / factor;

        const profit = netSelling - netCost;
        const margin = netSelling > 0 ? (profit / netSelling) * 100 : 0;
        const isLoss = profit < 0;
        el.profit.classList.toggle("text-green", !isLoss);

        el.profit.textContent = profit.toFixed(2);
        el.margin.textContent = margin.toFixed(1) + "%";
        el.profit.style.color = isLoss ? "#dc2626" : "";
        el.margin.style.color = isLoss ? "#dc2626" : "";
    }
    async function refreshCurrentPricing() {
        if (
            livePriceRefreshInProgress ||
            priceSaveInProgress ||
            !pricingLoaded ||
            !state.currentVersionId ||
            !document.body.contains(el.cost) ||
            !document.body.contains(el.selling)
        ) {
            return;
        }

        const requestedVersionId = state.currentVersionId;
        livePriceRefreshInProgress = true;

        try {
            const response = await getJson(
                `products/price-list.php?product_version_id=${encodeURIComponent(requestedVersionId)}&live_refresh=${Date.now()}`
            );
            const data = response.data || response;

            if (response.ok === false || data.success === false) {
                throw new Error(data.error || data.message || "Unable to refresh current prices.");
            }
            if (!data || !Array.isArray(data.prices)) return;
            if (!cataloguePageActive || requestedVersionId !== state.currentVersionId) return;

            let latestCostValue = "";
            let latestSellingValue = "";
            let latestBasis = "";
            data.prices.forEach(price => {
                if (price.price_type === "COST") latestCostValue = String(price.price_amount ?? "");
                if (price.price_type === "SELLING") {
                    latestSellingValue = String(price.price_amount ?? "");
                    latestBasis = Number(price.price_includes_vat) === 1 ? "INCLUSIVE" : "EXCLUSIVE";
                }
            });

            const latestCost = normalizePriceForComparison(latestCostValue);
            const latestSelling = normalizePriceForComparison(latestSellingValue);
            const serverPriceChanged =
                latestCost !== originalCostPrice ||
                latestSelling !== originalSellingPrice ||
                latestBasis !== originalBasis;

            if (!serverPriceChanged) return;

            const priceInputFocused = document.activeElement === el.cost || document.activeElement === el.selling;
            if (hasUnsavedPriceEdits() || priceSaveInProgress || priceInputFocused) return;

            el.cost.value = latestCostValue;
            el.selling.value = latestSellingValue;
            originalCostPrice = latestCost;
            originalSellingPrice = latestSelling;
            el.basis.value = latestBasis;
            originalBasis = latestBasis;
            calcMargin();
            updateSaveButtonState();
            updateRecommendationComparison();
        } catch (error) {
            console.error("Automatic price refresh failed:", error);
        } finally {
            livePriceRefreshInProgress = false;
        }
    }

    function startLivePriceRefresh() {
        if (window.__nexoraCataloguePriceRefreshTimer) {
            window.clearInterval(window.__nexoraCataloguePriceRefreshTimer);
        }

        window.__nexoraCataloguePriceRefreshTimer = window.setInterval(() => {
            if (!document.body.contains(el.cost) || !document.body.contains(el.selling)) {
                window.clearInterval(window.__nexoraCataloguePriceRefreshTimer);
                window.__nexoraCataloguePriceRefreshTimer = null;
                return;
            }
            if (document.visibilityState === "hidden") return;
            refreshCurrentPricing();
        }, 10000);
    }
    function clearActivePromotions() {
        state.activePromotions = [];
        if (el.activePromotionList) el.activePromotionList.replaceChildren();
        if (el.activePromotionPrices) el.activePromotionPrices.hidden = true;
        if (state.organizationType === "RETAILER" && el.recordOfferStatus) {
            el.recordOfferStatus.textContent = "No active promotion";
        }
    }

    function formatPromotionEndDate(value) {
        const match = String(value || "").match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return "date unavailable";
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        return `${Number(match[3])} ${months[Number(match[2]) - 1]} ${match[1]}`;
    }

    function formatPromotionMoney(value) {
        const amount = Number(value);
        if (!Number.isFinite(amount) || amount <= 0) return "Price unavailable";
        return `KSh ${amount.toLocaleString("en-KE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function createElement(tagName, className = "", text = "") {
        const element = document.createElement(tagName);

        if (className) {
            element.className = className;
        }

        if (text !== undefined && text !== null && text !== "") {
            element.textContent = String(text);
        }

        return element;
    }
    function renderActivePromotions(promotions) {
        if (!el.activePromotionPrices || !el.activePromotionList) return;

        el.activePromotionList.replaceChildren();

        const rows = Array.isArray(promotions) ? promotions : [];
        state.activePromotions = rows;
        el.activePromotionPrices.hidden = rows.length === 0;

        if (el.recordOfferStatus && state.organizationType === "RETAILER") {
            const branchCount = new Set(rows.map(item => String(item.branch_id))).size;
            el.recordOfferStatus.textContent = rows.length
                ? `Active at ${branchCount} branch${branchCount === 1 ? "" : "es"}`
                : "No active promotion";
        }

        for (const promotion of rows) {
            const row = createElement("div", "catalogue-active-promotion-row");
            const amount = createElement("div", "catalogue-active-promotion-amount");
            const promotionPrice = Number(promotion.promotion_price);

            amount.appendChild(
                createElement(
                    "strong",
                    "catalogue-active-promotion-price",
                    formatPromotionMoney(promotionPrice)
                )
            );

            row.appendChild(amount);
            if (promotion.end_date) {
                const endDate = createElement(
                    "div",
                    "catalogue-active-promotion-meta",
                    `Ends ${String(promotion.end_date).slice(0, 10)}`
                );
                row.appendChild(endDate);
            }

            el.activePromotionList.appendChild(row);
        }
    }
    async function loadActivePromotions(versionId) {
        if (
            !versionId ||
            state.organizationType !== "RETAILER" ||
            !el.activePromotionPrices ||
            !el.activePromotionList
        ) {
            clearActivePromotions();
            return;
        }

        const requestKey = String(versionId);
        if (!cataloguePageActive || activePromotionRequests.has(requestKey)) return;
        activePromotionRequests.add(requestKey);

        el.activePromotionPrices.hidden = true;
        el.activePromotionList.replaceChildren();
        if (el.recordOfferStatus) el.recordOfferStatus.textContent = "No active promotion";

        try {
            const response = await getJson(
                `products/active-promotions.php?product_version_id=${encodeURIComponent(versionId)}&_=${Date.now()}`
            );
            const data = response.data || response;
            if (response.ok === false || data.success !== true) {
                throw new Error(data.error || data.message || "Unable to load active promotions.");
            }
            if (!cataloguePageActive || Number(versionId) !== Number(state.currentVersionId)) return;

            const today = String(data.today || todayNairobiDate());
            const promotions = (Array.isArray(data.promotions) ? data.promotions : [])
                .filter(item =>
                    String(item.start_date || "") <= today &&
                    String(item.end_date || "") >= today &&
                    Number(item.promotion_price) > 0
                );

            renderActivePromotions(promotions);
        } catch (error) {
            if (Number(versionId) === Number(state.currentVersionId)) clearActivePromotions();
            console.error("Unable to load active catalogue promotions:", error);
        } finally {
            activePromotionRequests.delete(requestKey);
        }
    }

    function startActivePromotionRefresh() {
        if (window.__chainBridgeActivePromotionRefreshTimer) {
            window.clearInterval(window.__chainBridgeActivePromotionRefreshTimer);
        }
        window.__chainBridgeActivePromotionRefreshTimer = window.setInterval(() => {
            if (!document.body.contains(el.activePromotionPrices)) {
                window.clearInterval(window.__chainBridgeActivePromotionRefreshTimer);
                window.__chainBridgeActivePromotionRefreshTimer = null;
                return;
            }
            if (document.visibilityState === "hidden" || !state.currentVersionId) return;
            if (state.organizationType !== "RETAILER") return;
            loadActivePromotions(state.currentVersionId);
        }, 60000);
    }

    async function loadPricing(versionId) {
        state.vatPercent = null;
        pricingLoaded = false;
        originalCostPrice = "";
        originalCostIncludesVat = false;
        originalSellingPrice = "";
        originalBasis = "";
        el.basis.value = "";
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

            state.vatPercent = data.vat_percent ?? null;

            if (data && data.prices) {
                data.prices.forEach(p => {
                    if (p.price_type === "COST") {
                        el.cost.value = p.price_amount;
                        originalCostIncludesVat = Number(p.price_includes_vat) === 1;
                    }
                    if (p.price_type === "SELLING") {
                        el.selling.value = p.price_amount;
                        el.basis.value = Number(p.price_includes_vat) === 1 ? "INCLUSIVE" : "EXCLUSIVE";
                    }
                });
            }

            originalCostPrice = normalizePriceForComparison(el.cost.value);
            originalSellingPrice = normalizePriceForComparison(el.selling.value);
            originalBasis = el.basis.value;
            pricingLoaded = true;

            calcMargin();
            updateSaveButtonState();
            updateRecommendationComparison();
            return true;
        } catch (e) {
            console.error(e);
            pricingLoaded = false;
            updateSaveButtonState();
            return false;
        }
    }

    function recommendationMoney(value) {
        const amount = Number(value);
        if (!Number.isFinite(amount)) return "Unavailable";
        return `KSh ${amount.toLocaleString("en-KE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function formatRecommendationDate(value) {
        const match = String(value || "").match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[3]}/${match[2]}/${match[1]}` : "date unavailable";
    }

    function todayNairobiDate() {
        const parts = new Intl.DateTimeFormat("en-CA", {
            timeZone: "Africa/Nairobi", year: "numeric", month: "2-digit", day: "2-digit"
        }).formatToParts(new Date()).reduce((out, part) => {
            if (["year", "month", "day"].includes(part.type)) out[part.type] = part.value;
            return out;
        }, {});
        return `${parts.year}-${parts.month}-${parts.day}`;
    }

    function setRecommendationStatus(message, isError = false) {
        if (!el.recommendationStatus) return;
        el.recommendationStatus.textContent = message || "";
        el.recommendationStatus.classList.toggle("is-error", Boolean(isError));
        el.recommendationStatus.hidden = !message;
    }

    async function loadPriceRecommendations(versionId) {
        state.recommendationData = null;
        if (el.recommendationWorkspace) el.recommendationWorkspace.hidden = true;
        if (el.recommendationComparison) el.recommendationComparison.hidden = true;
        if (!versionId || !el.recommendationWorkspace) return;

        try {
            const response = await getJson(
                `products/price-recommendations-list.php?product_version_id=${encodeURIComponent(versionId)}`
            );
            const data = response.data || response;
            if (response.ok === false || data.success !== true) {
                throw new Error(data.error || data.message || "Unable to load pricing guidance.");
            }
            if (Number(versionId) !== Number(state.currentVersionId)) return;

            state.recommendationData = data;
            renderPriceRecommendations(data);
        } catch (error) {
            console.error("Unable to load pricing recommendations:", error);
        }
    }

    function renderPriceRecommendations(data) {
        const visibleTypes = Array.isArray(data.visible_types) ? data.visible_types : [];
        const recommendations = data.recommendations || {};
        const rrp = recommendations.RRP || null;
        const rsp = recommendations.RSP || null;

        const isRetailer = String(state.organizationType || "").toUpperCase() === "RETAILER";
        const showRrp = visibleTypes.includes("RRP");
        const showRsp = !isRetailer && visibleTypes.includes("RSP");

        el.recommendationWorkspace.hidden = !showRrp && !showRsp;
        el.rrpRecommendationRow.hidden = !showRrp;
        el.rspRecommendationRow.hidden = !showRsp;

        if (visibleTypes.includes("RRP")) {
            el.rrpRecommendationAmount.textContent = rrp ? recommendationMoney(rrp.amount) : "Unavailable";
            el.rrpRecommendationMeta.textContent = "";
        }

        if (showRsp) {
            el.rspRecommendationAmount.textContent = rsp ? recommendationMoney(rsp.amount) : "Unavailable";
            el.rspRecommendationMeta.textContent = rsp
                ? ""
                : (data.can_manage ? "" : "");
        }

        el.recommendationForm.hidden = !data.can_manage;
        el.recommendationEffectiveFrom.min = data.today || todayNairobiDate();
        if (!el.recommendationEffectiveFrom.value) {
            el.recommendationEffectiveFrom.value = data.today || todayNairobiDate();
        }
        updateRecommendationEditorAmount();
        updateRecommendationComparison();
    }


    function updateRecommendationEditorAmount() {
        if (!el.recommendationType || !el.recommendationAmount) return;
        const type = el.recommendationType.value;
        const recommendation = state.recommendationData?.recommendations?.[type];
        if (recommendation) {
            el.recommendationAmount.value = Number(recommendation.amount).toFixed(2);
        } else if (state.recommendationData?.can_manage) {
            el.recommendationAmount.value = "";
        }
    }

    function updateRecommendationComparison() {
        el.recommendationComparison.hidden = true;
        return;
        const data = state.recommendationData;
        if (!data || !el.recommendationComparison) return;
        const type = data.comparison_type;
        if (!type) {
            el.recommendationComparison.hidden = true;
            return;
        }

        const recommendation = data.recommendations?.[type];
        if (!recommendation || !(Number(recommendation.amount) > 0)) {
            el.recommendationComparison.hidden = true;
            return;
        }

        const sellingText = pricingLoaded ? String(el.selling?.value ?? '').trim() : '';
        const selling = sellingText === '' ? Number.NaN : Number(sellingText);
        if (!Number.isFinite(selling) || selling <= 0) {
            el.recommendationComparisonLabel.textContent = "Selling price";
            el.recommendationComparisonValue.textContent = "";
            el.recommendationComparison.className = "recommendation-comparison";
            el.recommendationComparison.hidden = false;
            return;
        }

        const reference = Number(recommendation.amount);
        const delta = ((selling - reference) / reference) * 100;
        const label = type === "RRP" ? "RRP" : "RSP";
        el.recommendationComparisonLabel.textContent = `Selling price \u00B7 ${recommendationMoney(selling)}`;

        if (delta < -0.05) {
            el.recommendationComparisonValue.textContent = `${Math.abs(delta).toFixed(1)}% below ${label}`;
            el.recommendationComparison.className = "recommendation-comparison is-below";
        } else if (delta > 30) {
            el.recommendationComparisonValue.textContent = `${delta.toFixed(1)}% above ${label}`;
            el.recommendationComparison.className = "recommendation-comparison is-high";
        } else {
            el.recommendationComparisonValue.textContent = Math.abs(delta) < 0.05
                ? `At ${label}`
                : `${delta.toFixed(1)}% above ${label}`;
            el.recommendationComparison.className = "recommendation-comparison is-equal";
        }
        el.recommendationComparison.hidden = false;
    }

    async function saveRecommendation(event) {
        event.preventDefault();
        if (!state.currentVersionId || !state.recommendationData?.can_manage) return;

        const amountText = String(el.recommendationAmount.value || "").trim();
        const effectiveFrom = String(el.recommendationEffectiveFrom.value || "").trim();
        if (!amountText || !Number.isFinite(Number(amountText)) || Number(amountText) <= 0) {
            setRecommendationStatus("Enter a recommendation greater than zero.", true);
            el.recommendationAmount.focus();
            return;
        }
        if (!effectiveFrom || effectiveFrom < todayNairobiDate()) {
            setRecommendationStatus("Choose today or a future effective date.", true);
            el.recommendationEffectiveFrom.focus();
            return;
        }

        const button = el.saveRecommendationBtn;
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = "Publishing...";
        setRecommendationStatus("");

        try {
            const response = await postJson("products/price-recommendation-save.php", {
                product_version_id: state.currentVersionId,
                recommendation_type: el.recommendationType.value,
                amount: amountText,
                effective_from: effectiveFrom
            });
            const result = response.data || response;
            if (response.ok === false || result.success !== true) {
                throw new Error(result.error || result.message || "Unable to publish recommendation.");
            }

            await loadPriceRecommendations(state.currentVersionId);
        } catch (error) {
            setRecommendationStatus(error.message || "Unable to publish recommendation.", true);
        } finally {
            button.disabled = false;
            button.textContent = originalText;
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
            normalizePriceForComparison(selling) !== originalSellingPrice || basisChanged();

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

        if (
            isOwnManufacturedProduct && sellingChanged && !basisChanged()
            && Number(selling) > Number(originalSellingPrice)
        ) {
            document.getElementById("manufacturerNewPrice").value = selling;
            el.selling.value = originalSellingPrice;
            calcMargin();
            updateSaveButtonState();
            document.getElementById("simulateHikeBtn").click();
            return;
        }

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

        priceSaveInProgress = true;
        el.saveBtn.disabled = true;
        el.saveBtn.textContent = "Saving...";

        try {
            if (costChanged) {
                await saveOnePrice({
                    product_version_id: versionId,
                    price_type: "COST",
                    price_amount: parseFloat(cost),
                    price_includes_vat: isOwnManufacturedProduct ? Number(state.vatPercent) > 0 : true,
                    effective_from: today
                });
                costSaved = true;
            }

            if (sellingChanged) {
                const sellingResult = await saveOnePrice({
                    product_version_id: versionId,
                    price_type: "SELLING",
                    price_amount: parseFloat(selling),
                    price_includes_vat: isOwnManufacturedProduct ? el.basis.value === "INCLUSIVE" : true,
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
            priceSaveInProgress = false;
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


    const registerCataloguePageCleanup = window.__nexoraPageRouter?.registerCleanup;
    if (typeof registerCataloguePageCleanup === "function") {
        registerCataloguePageCleanup(() => {
            cataloguePageActive = false;
            if (window.__nexoraCataloguePriceRefreshTimer) {
                window.clearInterval(window.__nexoraCataloguePriceRefreshTimer);
                window.__nexoraCataloguePriceRefreshTimer = null;
            }
            if (window.__chainBridgeActivePromotionRefreshTimer) {
                window.clearInterval(window.__chainBridgeActivePromotionRefreshTimer);
                window.__chainBridgeActivePromotionRefreshTimer = null;
            }
        });
    }

    if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", init); } else { init(); }
})();











