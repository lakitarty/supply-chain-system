(function () {
    "use strict";

    const byId = id => document.getElementById(id);
    const ui = {
        refresh: byId("retailerPromotionRefresh"),
        newButton: byId("retailerPromotionNew"),
        status: byId("retailerPromotionStatus"),
        list: byId("retailerPromotionList"),
        detail: byId("retailerPromotionDetail")
    };

    const state = {
        account: null,
        today: "",
        branches: [],
        branchProducts: [],
        promotions: [],
        selectedId: 0,
        draft: null,
        busy: false
    };

    function esc(value) {
        return String(value ?? "").replace(/[&<>"']/g, c => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
        })[c]);
    }

    function money(value) {
        const number = Number(value);
        return `KSh ${(Number.isFinite(number) ? number : 0).toLocaleString("en-KE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function dateText(value) {
        const m = String(value ?? "").match(/^(\d{4})-(\d{2})-(\d{2})/);
        return m ? `${m[3]}/${m[2]}/${m[1]}` : "—";
    }

    function apiData(response, fallback) {
        const data = response?.data ?? response;
        if (response?.ok === false || data?.success !== true) {
            throw new Error(data?.error || data?.message || fallback);
        }
        return data;
    }

    function message(text, type = "success") {
        ui.status.textContent = text;
        ui.status.className = `retailer-promo-message is-${type}`;
        ui.status.hidden = false;
    }

    function clearMessage() {
        ui.status.textContent = "";
        ui.status.hidden = true;
    }

    function statusClass(status) {
        return String(status || "").toLowerCase().replace(/[^a-z]+/g, "-");
    }

    function statusMarkup(status) {
        return `<span class="retailer-promo-status-text is-${esc(statusClass(status))}">${esc(status)}</span>`;
    }

    function selectedPromotion() {
        return state.promotions.find(p => Number(p.retailer_promotion_id) === Number(state.selectedId)) || null;
    }

    function canManage() {
        return ["ADMIN", "BRANCH_ADMIN"].includes(state.account?.user?.role);
    }

    function setBusy(value) {
        state.busy = value;
        ui.refresh.disabled = value;
        ui.newButton.disabled = value || state.branches.length === 0 || !canManage();
    }

    async function loadOptions(date = state.draft?.startDate || state.today) {
        const query = `promotions/retailer-promotion-options.php?start_date=${encodeURIComponent(date)}`;
        const data = apiData(await getJson(query), "Unable to load branches and priced products.");
        state.branches = Array.isArray(data.branches) ? data.branches : [];
        state.branchProducts = Array.isArray(data.branch_products) ? data.branch_products : [];
    }

    async function loadList(preserveId = state.selectedId) {
        const data = apiData(await getJson("promotions/retailer-promotions-list.php"), "Unable to load branch promotions.");
        state.today = data.today || state.today;
        state.promotions = Array.isArray(data.promotions) ? data.promotions : [];
        const chosen = state.promotions.find(p => Number(p.retailer_promotion_id) === Number(preserveId))
            || state.promotions[0] || null;
        state.selectedId = chosen ? Number(chosen.retailer_promotion_id) : 0;
        state.draft = null;
        renderList();
        renderDetail();
    }

    function renderList() {
        if (!state.promotions.length) {
            ui.list.innerHTML = '<p class="retailer-promo-empty">No branch promotions yet.</p>';
            return;
        }
        ui.list.innerHTML = state.promotions.map(p => `
            <button type="button" class="retailer-promo-list-item" data-select-promotion="${Number(p.retailer_promotion_id)}" aria-current="${Number(p.retailer_promotion_id) === state.selectedId ? "true" : "false"}">
                <span class="retailer-promo-list-name">${esc(p.promotion_name)}</span>
                <span class="retailer-promo-list-meta"><span>${esc(dateText(p.start_date))} – ${esc(dateText(p.end_date))}</span>${statusMarkup(p.display_status)}</span>
            </button>`).join("");
    }

    function newDraft() {
        state.selectedId = 0;
        state.draft = {
            promotionId: 0,
            name: "",
            description: "",
            startDate: state.today,
            endDate: state.today,
            items: []
        };
        renderList();
        renderDetail();
    }

    function editDraft(promotion) {
        if (!promotion || !promotion.can_manage || promotion.status !== "DRAFT") return;
        state.draft = {
            promotionId: Number(promotion.retailer_promotion_id),
            name: promotion.promotion_name || "",
            description: promotion.description || "",
            startDate: promotion.start_date,
            endDate: promotion.end_date,
            items: (promotion.items || []).map(item => ({
                branchId: Number(item.branch_id),
                branchName: item.branch_name,
                productId: Number(item.product_id),
                productVersionId: Number(item.product_version_id),
                productName: item.product_name + (item.pack_size ? ` · ${item.pack_size}` : ""),
                regularPrice: String(item.regular_price_snapshot),
                promotionPrice: String(item.promotion_price),
                priceBasis: item.price_basis || "VAT_INCLUSIVE"
            }))
        };
        state.selectedId = Number(promotion.retailer_promotion_id);
        renderList();
        renderDetail();
    }

    function availableProducts(branchId) {
        return state.branchProducts.filter(p => Number(p.branch_id) === Number(branchId));
    }

    function itemRows(items) {
        if (!items.length) return '<tr><td colspan="5" class="retailer-promo-empty">No products added.</td></tr>';
        return items.map((item, index) => `
            <tr>
                <td>${esc(item.branchName)}</td>
                <td>${esc(item.productName)}</td>
                <td class="money">${esc(money(item.regularPrice))}</td>
                <td class="money">${esc(money(item.promotionPrice))}</td>
                <td class="money"><button type="button" class="retailer-promo-remove" data-remove-item="${index}" aria-label="Remove ${esc(item.productName)}">Remove</button></td>
            </tr>`).join("");
    }

    function formMarkup() {
        const d = state.draft;
        const defaultBranch = state.branches[0] ? Number(state.branches[0].branch_id) : 0;
        const branches = state.branches.map(b => `<option value="${Number(b.branch_id)}">${esc(b.branch_name)}</option>`).join("");
        const products = availableProducts(defaultBranch);
        const productOptions = products.map(p => `<option value="${Number(p.product_version_id)}">${esc(p.product_name)}${p.pack_size ? ` · ${esc(p.pack_size)}` : ""}</option>`).join("");
        const selectedProduct = products[0] || null;

        return `
            <form id="retailerPromotionForm" class="retailer-promo-editor" novalidate>
                <div class="retailer-promo-detail-heading"><h2>${d.promotionId ? "Edit draft promotion" : "New branch promotion"}</h2></div>
                <div class="retailer-promo-fields">
                    <div class="retailer-promo-field"><label for="localPromotionName">Promotion name</label><input id="localPromotionName" maxlength="200" required value="${esc(d.name)}"></div>
                    <div class="retailer-promo-field"><label for="localPromotionStart">Start date</label><input id="localPromotionStart" type="date" min="${esc(state.today)}" required value="${esc(d.startDate)}"></div>
                    <div class="retailer-promo-field"><label for="localPromotionEnd">End date</label><input id="localPromotionEnd" type="date" min="${esc(d.startDate)}" required value="${esc(d.endDate)}"></div>
                    <div class="retailer-promo-field" style="grid-column:1/-1"><label for="localPromotionDescription">Description (optional)</label><textarea id="localPromotionDescription" maxlength="2000">${esc(d.description)}</textarea></div>
                </div>
                <div class="retailer-promo-section-title">Products and branch prices</div>
                <div class="retailer-promo-item-entry">
                    <div class="retailer-promo-field"><label for="localPromotionBranch">Branch</label><select id="localPromotionBranch">${branches || '<option value="">No active branches</option>'}</select></div>
                    <div class="retailer-promo-field"><label for="localPromotionProduct">Product</label><select id="localPromotionProduct">${productOptions || '<option value="">No selling price available for this date</option>'}</select></div>
                    <div class="retailer-promo-field"><label for="localPromotionRegular">Current selling price</label><input id="localPromotionRegular" readonly value="${selectedProduct ? esc(Number(selectedProduct.selling_price).toFixed(2)) : ""}"></div>
                    <div class="retailer-promo-field"><label for="localPromotionPrice">Promotion price (KSh)</label><input id="localPromotionPrice" type="number" min="0.01" step="0.01" inputmode="decimal"></div>
                    <button type="button" class="secondary-button item-add" data-action="add-item" ${!branches || !productOptions ? "disabled" : ""}>Add product</button>
                </div>
                <div class="retailer-promo-table-wrap">
                    <table class="retailer-promo-table">
                        <thead><tr><th>Branch</th><th>Product</th><th class="money">Regular price</th><th class="money">Promotion price</th><th></th></tr></thead>
                        <tbody>${itemRows(d.items)}</tbody>
                    </table>
                </div>
                <div class="retailer-promo-form-actions">
                    <button type="submit" class="primary-button" data-submit-action="save">Save draft</button>
                    <button type="button" class="secondary-button" data-action="publish-draft">Save and publish</button>
                    <button type="button" class="secondary-button" data-action="discard-edit">Cancel editing</button>
                </div>
            </form>`;
    }

    function viewMarkup(p) {
        const items = p.items || [];
        const lines = items.length ? items.map(item => `
            <tr><td>${esc(item.branch_name)}</td><td>${esc(item.product_name)}${item.pack_size ? ` · ${esc(item.pack_size)}` : ""}</td><td class="money">${esc(money(item.regular_price_snapshot))}</td><td class="money">${esc(money(item.promotion_price))}</td></tr>`).join("")
            : '<tr><td colspan="4" class="retailer-promo-empty">No products saved for the visible branches.</td></tr>';
        let actions = "";
        if (p.can_manage && p.status === "DRAFT") {
            actions += '<button type="button" class="secondary-button" data-action="edit-draft">Edit draft</button><button type="button" class="primary-button" data-action="publish">Publish</button><button type="button" class="secondary-button danger-button" data-action="cancel">Cancel promotion</button>';
        } else if (p.can_manage && ["SCHEDULED", "ACTIVE"].includes(p.display_status)) {
            actions += '<button type="button" class="secondary-button danger-button" data-action="cancel">Cancel promotion</button>';
        }
        return `
            <div class="retailer-promo-detail-heading">
                <div><h2>${esc(p.promotion_name)}</h2>${p.description ? `<p>${esc(p.description)}</p>` : ""}</div>
                ${statusMarkup(p.display_status)}
            </div>
            <div class="retailer-promo-detail-meta">
                <div><span>Start date</span><strong>${esc(dateText(p.start_date))}</strong></div>
                <div><span>End date</span><strong>${esc(dateText(p.end_date))}</strong></div>
                <div><span>Branch-product entries</span><strong>${Number(p.item_count || items.length || 0)}</strong></div>
            </div>
            <div class="retailer-promo-section-title">Promotion prices</div>
            <div class="retailer-promo-table-wrap"><table class="retailer-promo-table"><thead><tr><th>Branch</th><th>Product</th><th class="money">Regular price</th><th class="money">Promotion price</th></tr></thead><tbody>${lines}</tbody></table></div>
            <div class="retailer-promo-actions">${actions}</div>`;
    }

    function renderDetail() {
        if (state.draft) {
            ui.detail.innerHTML = formMarkup();
            updateProductEntry();
            return;
        }
        const promotion = selectedPromotion();
        if (!promotion) {
            ui.detail.innerHTML = '<p class="retailer-promo-empty">Select a promotion or create a new one.</p>';
            return;
        }
        ui.detail.innerHTML = viewMarkup(promotion);
    }

    function updateProductEntry() {
        const branchSelect = byId("localPromotionBranch");
        const productSelect = byId("localPromotionProduct");
        const regularInput = byId("localPromotionRegular");
        if (!branchSelect || !productSelect || !regularInput) return;
        const branchId = Number(branchSelect.value);
        const rows = availableProducts(branchId);
        const currentVersion = Number(productSelect.value);
        productSelect.innerHTML = rows.length
            ? rows.map(p => `<option value="${Number(p.product_version_id)}"${Number(p.product_version_id) === currentVersion ? " selected" : ""}>${esc(p.product_name)}${p.pack_size ? ` · ${esc(p.pack_size)}` : ""}</option>`).join("")
            : '<option value="">No selling price for this branch and date</option>';
        const selected = rows.find(p => Number(p.product_version_id) === Number(productSelect.value));
        regularInput.value = selected ? Number(selected.selling_price).toFixed(2) : "";
        const addButton = ui.detail.querySelector('[data-action="add-item"]');
        if (addButton) addButton.disabled = !selected;
    }

    function readDraftFields() {
        if (!state.draft) return;
        state.draft.name = byId("localPromotionName")?.value.trim() || "";
        state.draft.description = byId("localPromotionDescription")?.value.trim() || "";
        state.draft.startDate = byId("localPromotionStart")?.value || state.today;
        state.draft.endDate = byId("localPromotionEnd")?.value || state.draft.startDate;
    }

    async function refreshOptionsAndRender() {
        readDraftFields();
        try {
            await loadOptions(state.draft.startDate);
            renderDetail();
        } catch (error) {
            message(error.message, "error");
        }
    }

    function addItem() {
        readDraftFields();
        const branchId = Number(byId("localPromotionBranch")?.value);
        const productVersionId = Number(byId("localPromotionProduct")?.value);
        const promotionPrice = String(byId("localPromotionPrice")?.value || "").trim();
        const found = state.branchProducts.find(p => Number(p.branch_id) === branchId && Number(p.product_version_id) === productVersionId);
        const price = Number(promotionPrice);
        if (!branchId || !productVersionId || !found || !Number.isFinite(price) || price <= 0) {
            message("Choose a branch and product, then enter a valid promotion price.", "error");
            return;
        }
        if (price >= Number(found.selling_price)) {
            message("The promotion price must be below that branch's current selling price.", "error");
            return;
        }
        if (state.draft.items.some(item => Number(item.branchId) === branchId && Number(item.productVersionId) === productVersionId)) {
            message("That product is already added for this branch.", "error");
            return;
        }
        state.draft.items.push({
            branchId,
            branchName: found.branch_name,
            productId: Number(found.product_id),
            productVersionId,
            productName: found.product_name + (found.pack_size ? ` · ${found.pack_size}` : ""),
            regularPrice: Number(found.selling_price).toFixed(2),
            promotionPrice: price.toFixed(2),
            priceBasis: found.price_basis || "VAT_INCLUSIVE"
        });
        clearMessage();
        renderDetail();
    }

    async function saveDraft(thenPublish = false) {
        if (!state.draft) return null;
        readDraftFields();
        if (!state.draft.name) {
            message("Enter a promotion name.", "error");
            return null;
        }
        if (!state.draft.startDate || !state.draft.endDate || state.draft.endDate < state.draft.startDate) {
            message("Choose valid promotion dates.", "error");
            return null;
        }
        const response = await postJson("promotions/retailer-promotion-save.php", {
            promotionId: state.draft.promotionId,
            name: state.draft.name,
            description: state.draft.description,
            startDate: state.draft.startDate,
            endDate: state.draft.endDate,
            items: state.draft.items.map(item => ({
                branchId: item.branchId,
                productVersionId: item.productVersionId,
                promotionPrice: item.promotionPrice
            }))
        });
        const data = apiData(response, "Unable to save the branch promotion.");
        state.selectedId = Number(data.promotion_id);
        state.draft = null;
        await loadList(state.selectedId);
        message(data.message || "Promotion draft saved.", "success");
        if (thenPublish) await transition("publish", state.selectedId);
        return Number(data.promotion_id);
    }

    async function transition(action, promotionId = state.selectedId) {
        const response = await postJson("promotions/retailer-promotion-transition.php", { promotionId, action });
        const data = apiData(response, "Unable to update the branch promotion.");
        await loadList(Number(promotionId));
        message(data.message || "Promotion updated.", "success");
    }

    async function onClick(event) {
        const selectButton = event.target.closest("[data-select-promotion]");
        if (selectButton) {
            state.selectedId = Number(selectButton.dataset.selectPromotion);
            state.draft = null;
            clearMessage();
            renderList();
            renderDetail();
            return;
        }
        const removeButton = event.target.closest("[data-remove-item]");
        if (removeButton && state.draft) {
            readDraftFields();
            state.draft.items.splice(Number(removeButton.dataset.removeItem), 1);
            renderDetail();
            return;
        }
        const actionButton = event.target.closest("[data-action]");
        if (!actionButton) return;
        const action = actionButton.dataset.action;
        try {
            setBusy(true);
            clearMessage();
            if (action === "add-item") addItem();
            else if (action === "edit-draft") editDraft(selectedPromotion());
            else if (action === "discard-edit") { state.draft = null; renderList(); renderDetail(); }
            else if (action === "publish-draft") await saveDraft(true);
            else if (action === "publish") await transition("publish");
            else if (action === "cancel") {
                if (window.confirm("Cancel this branch promotion? The promotion record will remain available for history.")) {
                    await transition("cancel");
                }
            }
        } catch (error) {
            message(error.message, "error");
        } finally {
            setBusy(false);
        }
    }

    async function init(account) {
        state.account = account;
        if (!account || account.organization?.organization_type !== "RETAILER") {
            ui.detail.innerHTML = '<p class="retailer-promo-empty">This page is available to retailer organisations only.</p>';
            ui.newButton.hidden = true;
            return;
        }
        const manage = canManage();
        ui.newButton.hidden = !manage;
        try {
            setBusy(true);
            await loadList();
            if (manage) {
                await loadOptions(state.today);
                if (state.branches.length === 0) ui.newButton.disabled = true;
            }
        } catch (error) {
            ui.list.innerHTML = `<p class="retailer-promo-empty">${esc(error.message)}</p>`;
            ui.detail.innerHTML = '<p class="retailer-promo-empty">Unable to load branch promotions.</p>';
        } finally {
            setBusy(false);
        }
    }

    ui.list.addEventListener("click", onClick);
    ui.detail.addEventListener("click", onClick);
    ui.detail.addEventListener("submit", async event => {
        if (event.target.id !== "retailerPromotionForm") return;
        event.preventDefault();
        const submitter = event.submitter;
        try {
            setBusy(true);
            clearMessage();
            await saveDraft(false);
        } catch (error) {
            message(error.message, "error");
        } finally {
            setBusy(false);
        }
    });
    ui.detail.addEventListener("change", async event => {
        if (event.target.id === "localPromotionBranch" || event.target.id === "localPromotionProduct") {
            updateProductEntry();
        } else if (event.target.id === "localPromotionStart") {
            await refreshOptionsAndRender();
        }
    });
    ui.refresh.addEventListener("click", async () => {
        try {
            setBusy(true);
            clearMessage();
            await loadList();
            if (canManage()) await loadOptions(state.today);
            message("Branch promotions refreshed.", "success");
        } catch (error) { message(error.message, "error"); }
        finally { setBusy(false); }
    });
    ui.newButton.addEventListener("click", newDraft);

    shellReady.then(init).catch(error => message(error.message, "error"));
})();
