(function () {
    "use strict";

    const byId = id => document.getElementById(id);
    const ui = {
        refresh: byId("retailerOffersRefresh"),
        status: byId("retailerOffersStatus"),
        list: byId("retailerOffersList"),
        detail: byId("retailerOfferDetail")
    };
    const state = { account: null, today: "", role: "", branches: [], offers: [], selectedId: 0, busy: false };

    function esc(value) {
        return String(value ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
    }
    function money(value) {
        if (value === null || value === undefined || value === "") return "Not recorded";
        const n = Number(value);
        return `KSh ${(Number.isFinite(n) ? n : 0).toLocaleString("en-KE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
    function dateText(value) {
        const m = String(value ?? "").match(/^(\d{4})-(\d{2})-(\d{2})/);
        return m ? `${m[3]}/${m[2]}/${m[1]}` : "—";
    }
    function apiData(response, fallback) {
        const data = response?.data ?? response;
        if (response?.ok === false || data?.success !== true) throw new Error(data?.error || data?.message || fallback);
        return data;
    }
    function notify(text, type = "success") {
        ui.status.textContent = text;
        ui.status.className = `retailer-offers-message is-${type}`;
        ui.status.hidden = false;
    }
    function clearNotice() { ui.status.textContent = ""; ui.status.hidden = true; }
    function statusClass(value) { return String(value || "").toLowerCase().replace(/[^a-z]+/g, "-"); }
    function statusMarkup(value) { return `<span class="retailer-offer-status is-${esc(statusClass(value))}">${esc(value)}</span>`; }
    function selectedOffer() { return state.offers.find(o => Number(o.promotion_id) === Number(state.selectedId)) || null; }
    function setBusy(value) { state.busy = value; ui.refresh.disabled = value; }

    async function load(preserveId = state.selectedId) {
        const data = apiData(await getJson("promotions/retailer-campaigns-list.php"), "Unable to load manufacturer campaigns.");
        state.today = data.today || state.today;
        state.role = data.role || state.account?.user?.role || "";
        state.branches = Array.isArray(data.branches) ? data.branches : [];
        state.offers = Array.isArray(data.offers) ? data.offers : [];
        const selected = state.offers.find(o => Number(o.promotion_id) === Number(preserveId)) || state.offers[0] || null;
        state.selectedId = selected ? Number(selected.promotion_id) : 0;
        renderList();
        renderDetail();
    }

    function renderList() {
        if (!state.offers.length) {
            ui.list.innerHTML = '<p class="retailer-offer-empty">No manufacturer campaigns have been offered to your organisation.</p>';
            return;
        }
        ui.list.innerHTML = state.offers.map(o => `
            <button type="button" class="retailer-offer-list-item" data-select-offer="${Number(o.promotion_id)}" aria-current="${Number(o.promotion_id) === state.selectedId ? "true" : "false"}">
                <span class="retailer-offer-list-name">${esc(o.promotion_name)}</span>
                <span class="retailer-offer-list-meta"><span>${esc(o.manufacturer_name)}</span>${statusMarkup(o.participant_status)}</span>
                <span class="retailer-offer-list-meta"><span>${esc(dateText(o.start_date))} – ${esc(dateText(o.end_date))}</span>${statusMarkup(o.display_status)}</span>
            </button>`).join("");
    }

    function productRows(offer) {
        const products = offer.products || [];
        if (!products.length) return '<tr><td colspan="4" class="retailer-offer-empty">No product terms are available.</td></tr>';
        return products.map(p => `
            <tr>
                <td>${esc(p.product_name)}${p.pack_size ? ` · ${esc(p.pack_size)}` : ""}</td>
                <td class="money">${esc(money(p.rrp_snapshot))}</td>
                <td class="money">${esc(money(p.promotion_price))}</td>
                <td class="money">${esc(money(p.compensation_per_unit))}</td>
            </tr>`).join("");
    }

    function branchMarkup(offer) {
        const branches = offer.branches || [];
        if (!branches.length) return '<p class="retailer-offer-empty">No branches selected for this campaign yet.</p>';
        return branches.map(b => {
            const action = b.status === "ACTIVE" ? `<span>Runs ${esc(dateText(offer.start_date))} to ${esc(dateText(offer.end_date))}</span>` : "";
            return `<div class="retailer-offer-branch-line"><div><strong>${esc(b.branch_name)}</strong> &nbsp; ${statusMarkup(b.status)}</div>${action}</div>`;
        }).join("");
    }

    function renderDetail() {
        const offer = selectedOffer();
        if (!offer) {
            ui.detail.innerHTML = '<p class="retailer-offer-empty">Select a manufacturer campaign to review its offer and choose whether to participate.</p>';
            return;
        }

        let responseActions = "";
        if (offer.can_respond && state.role === "ADMIN") {
            const branchOptions = state.branches.map(b => `
                <label><input type="checkbox" name="offerBranch" value="${Number(b.branch_id)}"> ${esc(b.branch_name)}</label>`).join("");
            responseActions = `
                <div class="retailer-offer-branch-chooser">
                    <div class="retailer-offer-section-title">Choose participating branches</div>
                    <div class="retailer-offer-branch-options">${branchOptions || '<span>No active branches available.</span>'}</div>
                    <div class="retailer-offer-actions">
                        <button type="button" class="primary-button" data-action="accept">Accept and choose branches</button>
                        <button type="button" class="secondary-button danger-button" data-action="decline">Decline offer</button>
                    </div>
                </div>`;
        } else if (offer.participant_status === "PENDING") {
            responseActions = '<p class="retailer-offer-empty">Your organisation administrator must accept or decline this offer and choose participating branches.</p>';
        }

        const branches = offer.branches || [];
        const hasBranches = branches.length > 0;
        const branchSection = hasBranches || ["ACCEPTED", "ACTIVE", "COMPLETED"].includes(offer.participant_status)
            ? `<div class="retailer-offer-section-title">Participating branches</div>${branchMarkup(offer)}` : "";

        ui.detail.innerHTML = `
            <div class="retailer-offer-detail-heading">
                <div><h2>${esc(offer.promotion_name)}</h2><p>${esc(offer.manufacturer_name)}</p>${offer.description ? `<p>${esc(offer.description)}</p>` : ""}</div>
                ${statusMarkup(offer.display_status)}
            </div>
            <div class="retailer-offer-meta">
                <div><span>Campaign status</span><strong>${esc(offer.display_status)}</strong></div>
                <div><span>Your response</span><strong>${esc(offer.participant_status)}</strong></div>
                <div><span>Campaign period</span><strong>${esc(dateText(offer.start_date))} – ${esc(dateText(offer.end_date))}</strong></div>
            </div>
            <div class="retailer-offer-section-title">Campaign prices and support</div>
            <div class="retailer-offer-table-wrap"><table class="retailer-offer-table"><thead><tr><th>Product</th><th class="money">RRP snapshot</th><th class="money">Promotion price</th><th class="money">Compensation per unit</th></tr></thead><tbody>${productRows(offer)}</tbody></table></div>
            ${responseActions}
            ${branchSection}`;
    }

    async function runAction(action) {
        const body = { promotionId: Number(state.selectedId), action };
        if (action === "accept") {
            body.branchIds = Array.from(ui.detail.querySelectorAll('input[name="offerBranch"]:checked')).map(input => Number(input.value));
            if (!body.branchIds.length) throw new Error("Select at least one branch before accepting the campaign.");
        }
        const response = await postJson("promotions/retailer-campaign-action.php", body);
        const data = apiData(response, "Unable to update campaign participation.");
        await load(Number(state.selectedId));
        notify(data.message || "Campaign response updated.", "success");
    }

    async function onClick(event) {
        const selectButton = event.target.closest("[data-select-offer]");
        if (selectButton) {
            state.selectedId = Number(selectButton.dataset.selectOffer);
            clearNotice(); renderList(); renderDetail(); return;
        }
        const actionButton = event.target.closest("[data-action]");
        if (!actionButton) return;
        try {
            setBusy(true); clearNotice();
            await runAction(actionButton.dataset.action);
        } catch (error) { notify(error.message, "error"); }
        finally { setBusy(false); }
    }

    ui.list.addEventListener("click", onClick);
    ui.detail.addEventListener("click", onClick);
    ui.refresh.addEventListener("click", async () => {
        try { setBusy(true); clearNotice(); await load(); notify("Manufacturer campaigns refreshed.", "success"); }
        catch (error) { notify(error.message, "error"); }
        finally { setBusy(false); }
    });

    shellReady.then(async account => {
        state.account = account;
        if (!account || account.organization?.organization_type !== "RETAILER") {
            ui.detail.innerHTML = '<p class="retailer-offer-empty">This page is available to retailer organisations only.</p>';
            return;
        }
        try { setBusy(true); await load(); }
        catch (error) { ui.list.innerHTML = `<p class="retailer-offer-empty">${esc(error.message)}</p>`; }
        finally { setBusy(false); }
    }).catch(error => notify(error.message, "error"));
})();
