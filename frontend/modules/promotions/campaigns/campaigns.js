(function () {
    "use strict";

    const STATUS_LABELS = {
        DRAFT: "Draft",
        SUBMITTED: "Awaiting approval",
        SCHEDULED: "Scheduled",
        ACTIVE: "Active",
        EXPIRED: "Expired",
        CANCELLED: "Cancelled"
    };
    const STATUS_TONES = { ACTIVE: "success", SUBMITTED: "warning", SCHEDULED: "warning", CANCELLED: "danger" };
    const RETAILER_LABELS = {
        PENDING: "Awaiting response",
        ACCEPTED: "Accepted",
        DECLINED: "Declined",
        ACTIVE: "Running",
        COMPLETED: "Completed"
    };
    const RETAILER_TONES = { ACCEPTED: "success", ACTIVE: "success", DECLINED: "danger" };
    const MONEY_PATTERN = /^\d{1,10}(?:\.\d{1,2})?$/;

    const BUTTON_CLASSES = { primary: "primary-button", secondary: "secondary-button", danger: "secondary-button is-danger" };

    const state = {
        loadError: "",
        today: "",
        campaigns: [],
        options: { products: [], retailers: [] },
        selected: null,
        mode: "view",
        draft: null,
        busy: false,
        pendingAction: ""
    };

    const el = {
        select: document.getElementById("campaignSelect"),
        newButton: document.getElementById("campaignNew"),
        refresh: document.getElementById("campaignRefresh"),
        status: document.getElementById("campaignStatus"),
        content: document.getElementById("campaignContent"),
        dialog: document.getElementById("campaignDialog"),
        dialogContent: document.getElementById("campaignDialogContent")
    };

    function esc(value) {
        return String(value ?? "").replace(/[&<>"']/g, character => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
        })[character]);
    }

    function money(value) {
        const amount = Number(value ?? 0);
        return `KSh ${(Number.isFinite(amount) ? amount : 0).toLocaleString("en-KE", {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        })}`;
    }

    function dateText(value) {
        const match = String(value ?? "").match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[3]}/${match[2]}/${match[1]}` : "—";
    }

    function toCents(text) {
        if (!MONEY_PATTERN.test(text)) return null;
        const [whole, fraction = ""] = text.split(".");
        return Number(whole) * 100 + Number(fraction.padEnd(2, "0"));
    }

    function fromCents(cents) {
        return (cents / 100).toFixed(2);
    }

    function apiData(response, defaultMessage) {
        const data = response?.data ?? response;
        if (response?.ok === false || data?.success !== true) {
            throw new Error(data?.error || data?.message || defaultMessage);
        }
        return data;
    }

    function notify(message, type = "success") {
        el.status.textContent = message;
        el.status.className = `pricing-status is-${type}`;
        el.status.hidden = false;
    }

    function clearNotice() {
        el.status.textContent = "";
        el.status.hidden = true;
    }

    function setBusy(value) {
        state.busy = value;
        el.refresh.disabled = value;
        el.refresh.textContent = value ? "Refreshing…" : "Refresh";
    }

    function pill(label, tone) {
        return `<span class="pricing-status-text${tone ? ` is-${tone}` : ""}"><span class="pricing-status-mark" aria-hidden="true"></span>${esc(label)}</span>`;
    }

    function statusPill(status) {
        return pill(STATUS_LABELS[status] || status, STATUS_TONES[status]);
    }

    function button(action, label, kind, extra = "") {
        return `<button type="button" class="${BUTTON_CLASSES[kind]}" data-action="${action}"${extra}>${esc(label)}</button>`;
    }

    function removeButton(action, id) {
        return `<button type="button" class="secondary-button is-small" data-action="${action}" data-id="${id}">Remove</button>`;
    }

    async function load(selectedId) {
        setBusy(true);
        try {
            const query = selectedId ? `?id=${encodeURIComponent(selectedId)}` : "";
            const listResponse = await getJson(`promotions/campaigns-list.php${query}`);
            const list = apiData(listResponse, "Unable to load campaigns.");
            const effectiveDate = list.selected?.start_date || list.today;
            const optionsResponse = await getJson(
                `promotions/campaign-options.php?effective_date=${encodeURIComponent(effectiveDate)}`
            );
            const options = apiData(optionsResponse, "Unable to load products and retailers.");
            state.loadError = "";
            state.today = list.today;
            state.campaigns = list.campaigns;
            state.selected = list.selected;
            state.options = { products: options.products, retailers: options.retailers };
        } catch (error) {
            state.loadError = error.message || "Unable to load campaigns.";
        } finally {
            setBusy(false);
            render();
        }
    }

    async function loadOptionsForDate(effectiveDate, rerender = true) {
        if (!effectiveDate) return;
        const response = await getJson(
            `promotions/campaign-options.php?effective_date=${encodeURIComponent(effectiveDate)}`
        );
        const data = apiData(response, "Unable to load product RRPs for that campaign date.");
        state.options = {
            products: Array.isArray(data.products) ? data.products : [],
            retailers: Array.isArray(data.retailers) ? data.retailers : state.options.retailers
        };

        if (state.draft) {
            state.draft.products = state.draft.products.map(existing => {
                const current = state.options.products.find(
                    product => Number(product.product_id) === Number(existing.productId)
                );
                return {
                    ...existing,
                    rrpAmount: current?.rrp_amount ?? null,
                    rrpRecommendationId: current?.rrp_recommendation_id ?? null,
                    productVersionId: current?.product_version_id ?? null
                };
            });
        }
        if (rerender && state.mode === "edit") render();
    }

    function selectedProductOption() {
        const select = document.getElementById("campaignProduct");
        return select?.selectedOptions?.[0] || null;
    }

    function updateCampaignRrpField() {
        const field = document.getElementById("campaignRrp");
        const option = selectedProductOption();
        if (!field) return;
        field.value = option?.dataset.rrp ? Number(option.dataset.rrp).toFixed(2) : "";
    }

    function render() {
        renderToolbar();
        if (state.loadError) {
            el.content.innerHTML = `<p class="pricing-empty-text">${esc(state.loadError)}</p>`;
        } else if (state.mode === "edit") {
            el.content.innerHTML = formMarkup();
        } else if (state.selected) {
            el.content.innerHTML = viewMarkup(state.selected);
        } else {
            el.content.innerHTML = '<p class="pricing-empty-text">No campaigns yet.</p>';
        }
    }

    function renderToolbar() {
        const editing = state.mode === "edit";
        const selectedId = Number(state.selected?.promotion_id || 0);
        el.select.innerHTML = state.campaigns.length
            ? state.campaigns.map(campaign => `<option value="${Number(campaign.promotion_id)}"${Number(campaign.promotion_id) === selectedId ? " selected" : ""}>${esc(campaign.promotion_name)} · ${esc(STATUS_LABELS[campaign.display_status] || campaign.display_status)} · ${esc(dateText(campaign.start_date))} to ${esc(dateText(campaign.end_date))}</option>`).join("")
            : '<option value="">No campaigns yet</option>';
        el.select.disabled = editing || !state.campaigns.length;
        el.newButton.disabled = editing || Boolean(state.loadError);
    }

    function retailerResponse(campaign, retailer) {
        if (["DRAFT", "SUBMITTED"].includes(campaign.display_status)) return pill("Not sent yet", "");
        return pill(RETAILER_LABELS[retailer.status] || retailer.status, RETAILER_TONES[retailer.status]);
    }

    function viewMarkup(campaign) {
        const productRows = campaign.products.map(product => {
            const hasRrpSnapshot = product.rrp_snapshot !== null && product.rrp_snapshot !== undefined;
            const rrpCents = hasRrpSnapshot ? toCents(Number(product.rrp_snapshot).toFixed(2)) : null;
            const promoCents = toCents(Number(product.promotion_price).toFixed(2));
            const cut = rrpCents !== null && promoCents !== null ? fromCents(rrpCents - promoCents) : null;
            return `<tr>
                <td>${esc(product.product_name)}</td>
                <td class="pricing-amount">${hasRrpSnapshot ? esc(money(product.rrp_snapshot)) : "RRP not recorded"}</td>
                <td class="pricing-amount">${esc(money(product.promotion_price))}</td>
                <td class="pricing-amount">${cut !== null ? esc(money(cut)) : "—"}</td>
                <td class="pricing-amount">${esc(money(product.compensation_per_unit))}</td>
            </tr>`;
        }).join("");
        const retailerRows = campaign.retailers.map(retailer => `<tr>
                <td>${esc(retailer.organization_name)}</td>
                <td>${retailerResponse(campaign, retailer)}</td>
            </tr>`).join("");
        const approval = campaign.approved_by_name
            ? `<div><dt>Approved by</dt><dd>${esc(campaign.approved_by_name)}</dd></div>`
            : "";

        return `<section class="pricing-section">
                <p class="campaign-name">${esc(campaign.promotion_name)}</p>
                ${campaign.description ? `<p class="campaign-description">${esc(campaign.description)}</p>` : ""}
                <dl class="campaign-facts">
                    <div><dt>Status</dt><dd>${statusPill(campaign.display_status)}</dd></div>
                    <div><dt>Runs</dt><dd>${esc(dateText(campaign.start_date))} to ${esc(dateText(campaign.end_date))}</dd></div>
                    <div><dt>Created by</dt><dd>${esc(campaign.created_by_name)}</dd></div>
                    ${approval}
                </dl>
                ${actionsMarkup(campaign)}
            </section>
            <section class="pricing-section">
                <div class="pricing-section-title"><span>Products</span></div>
                <div class="pricing-table-wrap"><table class="pricing-table">
                    <thead><tr><th>Product</th><th class="pricing-amount">RRP</th><th class="pricing-amount">Promotion price</th><th class="pricing-amount">RRP reduction</th><th class="pricing-amount">Compensation per unit</th></tr></thead>
                    <tbody>${productRows}</tbody>
                </table></div>
            </section>
            <section class="pricing-section">
                <div class="pricing-section-title"><span>Retailers</span></div>
                <div class="pricing-table-wrap"><table class="pricing-table">
                    <thead><tr><th>Retailer</th><th>Response</th></tr></thead>
                    <tbody>${retailerRows}</tbody>
                </table></div>
            </section>`;
    }

    function actionsMarkup(campaign) {
        const buttons = [];
        let hint = "";
        if (campaign.status === "DRAFT") {
            buttons.push(button("edit", "Edit draft", "secondary"), button("submit", "Submit for approval", "primary"));
        }
        if (campaign.status === "SUBMITTED") {
            if (campaign.can_approve) {
                buttons.push(button("approve", "Approve", "primary"));
            } else {
                hint = "A different administrator in your organisation must approve this campaign.";
            }
            buttons.push(button("return", "Return to draft", "secondary"));
        }
        if (["DRAFT", "SUBMITTED", "APPROVED"].includes(campaign.status) && campaign.display_status !== "EXPIRED") {
            buttons.push(button("cancel", "Cancel campaign", "danger"));
        }
        if (!buttons.length) return "";

        return `<div class="pricing-actions">${buttons.join("")}</div>${hint ? `<p class="pricing-hint">${esc(hint)}</p>` : ""}`;
    }

    function newDraft() {
        return { promotionId: 0, name: "", description: "", startDate: state.today, endDate: "", products: [], retailers: [] };
    }

    function draftFromCampaign(campaign) {
        return {
            promotionId: Number(campaign.promotion_id),
            name: campaign.promotion_name,
            description: campaign.description || "",
            startDate: campaign.start_date,
            endDate: campaign.end_date,
            products: campaign.products.map(product => {
                const current = state.options.products.find(
                    option => Number(option.product_id) === Number(product.product_id)
                );
                return {
                    productId: Number(product.product_id),
                    productName: product.product_name,
                    rrpAmount: current?.rrp_amount ?? product.rrp_snapshot ?? null,
                    rrpRecommendationId: current?.rrp_recommendation_id ?? product.rrp_recommendation_id ?? null,
                    productVersionId: current?.product_version_id ?? product.product_version_id ?? null,
                    promotionPrice: product.promotion_price,
                    compensation: product.compensation_per_unit
                };
            }),
            retailers: campaign.retailers.map(retailer => ({
                id: Number(retailer.organization_id),
                name: retailer.organization_name
            }))
        };
    }

    function readFormFields() {
        const form = document.getElementById("campaignForm");
        if (!form) return;
        state.draft.name = document.getElementById("campaignName").value;
        state.draft.description = document.getElementById("campaignDescription").value;
        state.draft.startDate = document.getElementById("campaignStart").value;
        state.draft.endDate = document.getElementById("campaignEnd").value;
    }

    function formMarkup() {
        const draft = state.draft;
        const usedProducts = new Set(draft.products.map(product => product.productId));
        const usedRetailers = new Set(draft.retailers.map(retailer => retailer.id));
        const productOptions = state.options.products
            .filter(product => !usedProducts.has(Number(product.product_id)))
            .map(product => {
                const rrp = product.rrp_amount === null || product.rrp_amount === undefined
                    ? ""
                    : Number(product.rrp_amount).toFixed(2);
                const unavailable = !rrp || !product.rrp_recommendation_id;
                const label = `${product.product_name} · ${rrp ? `RRP ${money(rrp)}` : "RRP not set"}`;
                return `<option value="${Number(product.product_id)}" data-rrp="${esc(rrp)}" data-version-id="${Number(product.product_version_id || 0)}" data-recommendation-id="${Number(product.rrp_recommendation_id || 0)}"${unavailable ? " disabled" : ""}>${esc(label)}</option>`;
            }).join("");
        const retailerOptions = state.options.retailers
            .filter(retailer => !usedRetailers.has(Number(retailer.organization_id)))
            .map(retailer => `<option value="${Number(retailer.organization_id)}">${esc(retailer.organization_name)}</option>`).join("");
        const productRows = draft.products.length
            ? draft.products.map(product => {
                const hasRrp = product.rrpAmount !== null && product.rrpAmount !== undefined && product.rrpAmount !== "";
                const rrpCents = hasRrp ? toCents(Number(product.rrpAmount).toFixed(2)) : null;
                const promoCents = toCents(Number(product.promotionPrice).toFixed(2));
                const cut = rrpCents !== null && promoCents !== null ? fromCents(rrpCents - promoCents) : null;
                return `<tr>
                    <td>${esc(product.productName)}</td>
                    <td class="pricing-amount">${hasRrp ? esc(money(product.rrpAmount)) : "RRP unavailable"}</td>
                    <td class="pricing-amount">${esc(money(product.promotionPrice))}</td>
                    <td class="pricing-amount">${cut !== null ? esc(money(cut)) : "—"}</td>
                    <td class="pricing-amount">${esc(money(product.compensation))}</td>
                    <td class="pricing-amount">${removeButton("remove-product", product.productId)}</td>
                </tr>`;
            }).join("")
            : '<tr><td colspan="6" class="pricing-muted">No products added yet.</td></tr>';
        const retailerRows = draft.retailers.length
            ? draft.retailers.map(retailer => `<tr>
                    <td>${esc(retailer.name)}</td>
                    <td class="pricing-amount">${removeButton("remove-retailer", retailer.id)}</td>
                </tr>`).join("")
            : '<tr><td colspan="2" class="pricing-muted">No retailers invited yet.</td></tr>';

        return `<form id="campaignForm" novalidate>
                <section class="pricing-section">
                    <div class="pricing-grid">
                        <div class="pricing-field pricing-note-field">
                            <label for="campaignName">Name</label>
                            <input id="campaignName" type="text" maxlength="200" value="${esc(draft.name)}" required>
                        </div>
                        <div class="pricing-field pricing-note-field">
                            <label for="campaignDescription">Description</label>
                            <textarea id="campaignDescription" maxlength="2000">${esc(draft.description)}</textarea>
                        </div>
                        <div class="pricing-field">
                            <label for="campaignStart">Start date</label>
                            <input id="campaignStart" type="date" value="${esc(draft.startDate)}" min="${esc(state.today)}" required>
                        </div>
                        <div class="pricing-field">
                            <label for="campaignEnd">End date</label>
                            <input id="campaignEnd" type="date" value="${esc(draft.endDate)}" min="${esc(draft.startDate)}" required>
                        </div>
                    </div>
                </section>
                <section class="pricing-section">
                    <div class="pricing-add-row">
                        <div class="pricing-field">
                            <label for="campaignProduct">Product</label>
                            <select id="campaignProduct"><option value="">Choose product</option>${productOptions}</select>
                        </div>
                        <div class="pricing-field">
                            <label for="campaignRrp">RRP (KSh)</label>
                            <input id="campaignRrp" type="number" readonly aria-readonly="true" placeholder="Published RRP" tabindex="-1">
                        </div>
                        <div class="pricing-field">
                            <label for="campaignPromotion">Promotion price (KSh)</label>
                            <input id="campaignPromotion" type="number" min="0.01" step="0.01" inputmode="decimal">
                        </div>
                        <div class="pricing-field">
                            <label for="campaignCompensation">Compensation per unit (KSh)</label>
                            <input id="campaignCompensation" type="number" min="0" step="0.01" inputmode="decimal">
                        </div>
                        ${button("add-product", "Add product", "secondary", productOptions ? "" : " disabled")}
                    </div>
                    <div class="pricing-table-wrap"><table class="pricing-table">
                        <thead><tr><th>Product</th><th class="pricing-amount">RRP</th><th class="pricing-amount">Promotion price</th><th class="pricing-amount">RRP reduction</th><th class="pricing-amount">Compensation per unit</th><th></th></tr></thead>
                        <tbody>${productRows}</tbody>
                    </table></div>
                </section>
                <section class="pricing-section">
                    <div class="pricing-add-row pricing-add-row-single">
                        <div class="pricing-field">
                            <label for="campaignRetailer">Retailer</label>
                            <select id="campaignRetailer">${retailerOptions || '<option value="">No more retailers to invite</option>'}</select>
                        </div>
                        ${button("add-retailer", "Invite retailer", "secondary", retailerOptions ? "" : " disabled")}
                    </div>
                    <div class="pricing-table-wrap"><table class="pricing-table">
                        <thead><tr><th>Retailer</th><th></th></tr></thead>
                        <tbody>${retailerRows}</tbody>
                    </table></div>
                </section>
                <div class="pricing-actions">
                    <button type="submit" class="primary-button">Save draft</button>
                    ${button("discard", "Discard changes", "secondary")}
                </div>
            </form>`;
    }

    function addProduct() {
        readFormFields();
        const select = document.getElementById("campaignProduct");
        const option = select?.selectedOptions?.[0];
        const productId = Number(select?.value);
        const rrp = option?.dataset.rrp ? toCents(option.dataset.rrp) : null;
        const recommendationId = Number(option?.dataset.recommendationId || 0);
        const versionId = Number(option?.dataset.versionId || 0);
        const promotion = toCents(document.getElementById("campaignPromotion").value.trim());
        const compensation = toCents(document.getElementById("campaignCompensation").value.trim());
        let problem = "";
        if (!productId) problem = "Choose a product to add.";
        else if (option?.disabled || rrp === null || recommendationId < 1 || versionId < 1) problem = "This product has no RRP effective on the campaign start date. Publish its RRP in Product Catalogue first.";
        else if (promotion === null || compensation === null) problem = "Enter a promotion price and compensation in KSh with no more than two decimal places.";
        else if (promotion < 1 || promotion >= rrp) problem = `The promotion price must be above zero and below the RRP of ${money(fromCents(rrp))}.`;
        else if (compensation > rrp - promotion) problem = `Compensation cannot exceed the RRP reduction of ${money(fromCents(rrp - promotion))}.`;
        if (problem) {
            notify(problem, "error");
            return;
        }

        clearNotice();
        state.draft.products.push({
            productId,
            productName: option.textContent.split(" · RRP")[0],
            productVersionId: versionId,
            rrpRecommendationId: recommendationId,
            rrpAmount: fromCents(rrp),
            promotionPrice: fromCents(promotion),
            compensation: fromCents(compensation)
        });
        render();
    }

    function addRetailer() {
        readFormFields();
        const select = document.getElementById("campaignRetailer");
        const id = Number(select.value);
        if (!id) return;

        clearNotice();
        state.draft.retailers.push({ id, name: select.options[select.selectedIndex].text });
        render();
    }

    async function saveDraft() {
        readFormFields();
        const draft = state.draft;
        try {
            setBusy(true);
            const response = await postJson("promotions/campaign-save.php", {
                promotionId: draft.promotionId,
                name: draft.name,
                description: draft.description,
                startDate: draft.startDate,
                endDate: draft.endDate,
                products: draft.products.map(product => ({
                    productId: product.productId,
                    promotionPrice: product.promotionPrice,
                    compensation: product.compensation
                })),
                retailers: draft.retailers.map(retailer => retailer.id)
            });
            const data = apiData(response, "Unable to save the campaign.");
            state.mode = "view";
            state.draft = null;
            await load(data.promotion_id);
            notify(data.message, "success");
        } catch (error) {
            setBusy(false);
            notify(error.message, "error");
        }
    }

    async function runTransition(action, reason) {
        const response = await postJson("promotions/campaign-transition.php", {
            promotionId: Number(state.selected.promotion_id),
            action,
            reason
        });
        return apiData(response, "Unable to update the campaign.");
    }

    async function transitionNow(action) {
        try {
            setBusy(true);
            const data = await runTransition(action, "");
            await load(state.selected.promotion_id);
            notify(data.message, "success");
        } catch (error) {
            setBusy(false);
            notify(error.message, "error");
        }
    }

    function openReasonDialog(action) {
        const copy = action === "cancel"
            ? { title: "Cancel this campaign", text: "Retailers will no longer be offered it. Sales already counted stay claimable.", confirm: "Cancel campaign", kind: "danger" }
            : { title: "Return to draft", text: "The campaign goes back to draft so it can be edited and submitted again.", confirm: "Return to draft", kind: "primary" };
        state.pendingAction = action;
        el.dialogContent.innerHTML = `<p class="campaign-dialog-title">${esc(copy.title)}</p>
            <p class="campaign-dialog-text">${esc(copy.text)}</p>
            <form id="campaignReasonForm" novalidate>
                <div class="pricing-field">
                    <label for="campaignReason">Reason</label>
                    <textarea id="campaignReason" maxlength="300" required></textarea>
                </div>
                <p id="campaignReasonError" class="pricing-status is-error" hidden></p>
                <div class="campaign-dialog-actions">
                    ${button("dialog-close", "Keep as it is", "secondary")}
                    <button type="submit" class="${BUTTON_CLASSES[copy.kind]}">${esc(copy.confirm)}</button>
                </div>
            </form>`;
        el.dialog.showModal();
        document.getElementById("campaignReason").focus();
    }

    async function submitReason(event) {
        event.preventDefault();
        const reason = document.getElementById("campaignReason").value.trim();
        const errorBox = document.getElementById("campaignReasonError");
        if (reason.length < 3) {
            errorBox.textContent = "Enter a reason of at least 3 characters.";
            errorBox.hidden = false;
            return;
        }

        try {
            setBusy(true);
            const data = await runTransition(state.pendingAction, reason);
            el.dialog.close();
            await load(state.selected.promotion_id);
            notify(data.message, "success");
        } catch (error) {
            setBusy(false);
            errorBox.textContent = error.message;
            errorBox.hidden = false;
        }
    }

    function onContentClick(event) {
        const trigger = event.target.closest("[data-action]");
        if (!trigger || state.busy) return;
        const action = trigger.dataset.action;
        const id = Number(trigger.dataset.id);

        if (action === "edit") {
            state.draft = draftFromCampaign(state.selected);
            state.mode = "edit";
            clearNotice();
            render();
        } else if (action === "submit" || action === "approve") {
            transitionNow(action);
        } else if (action === "return" || action === "cancel") {
            openReasonDialog(action);
        } else if (action === "add-product") {
            addProduct();
        } else if (action === "add-retailer") {
            addRetailer();
        } else if (action === "remove-product") {
            readFormFields();
            state.draft.products = state.draft.products.filter(product => product.productId !== id);
            render();
        } else if (action === "remove-retailer") {
            readFormFields();
            state.draft.retailers = state.draft.retailers.filter(retailer => retailer.id !== id);
            render();
        } else if (action === "discard") {
            state.mode = "view";
            state.draft = null;
            clearNotice();
            render();
        }
    }

    el.select.addEventListener("change", () => {
        clearNotice();
        load(Number(el.select.value));
    });
    el.newButton.addEventListener("click", async () => {
        state.draft = newDraft();
        state.mode = "edit";
        clearNotice();
        try {
            await loadOptionsForDate(state.draft.startDate, false);
            render();
            updateCampaignRrpField();
        } catch (error) {
            notify(error.message || "Unable to load product RRPs.", "error");
            render();
        }
    });
    el.refresh.addEventListener("click", () => {
        clearNotice();
        load(Number(state.selected?.promotion_id || 0));
    });
    el.content.addEventListener("click", onContentClick);
    el.content.addEventListener("change", async event => {
        if (event.target?.id === "campaignProduct") {
            updateCampaignRrpField();
        } else if (event.target?.id === "campaignStart" && state.mode === "edit") {
            readFormFields();
            const endField = document.getElementById("campaignEnd");
            if (endField) endField.min = event.target.value;
            try {
                await loadOptionsForDate(event.target.value, true);
                updateCampaignRrpField();
            } catch (error) {
                notify(error.message || "Unable to refresh RRPs for that date.", "error");
            }
        }
    });
    el.content.addEventListener("submit", event => {
        event.preventDefault();
        saveDraft();
    });
    el.dialogContent.addEventListener("click", event => {
        if (event.target.closest('[data-action="dialog-close"]')) el.dialog.close();
    });
    el.dialogContent.addEventListener("submit", submitReason);

    load(0);
})();