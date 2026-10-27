(function () {
    "use strict";

    function currentFinanceView() {
        const pageView = document.querySelector("main.finance-page")?.dataset.financeView;
        if (["overview", "accounts", "documents", "payments", "ledger"].includes(pageView)) return pageView;
        const file = window.location.pathname.toLowerCase().split("/").pop();
        const views = {
            "overview.html": "overview",
            "accounts.html": "accounts",
            "documents.html": "documents",
            "payments.html": "payments",
            "ledger.html": "ledger"
        };
        return views[file] || "overview";
    }

    const state = {
        view: currentFinanceView(),
        organizationId: 0,
        summary: {},
        recentPayments: [],
        accounts: [],
        selectedAccountId: null,
        accountSearch: "",
        documents: [],
        payments: [],
        ledger: [],
        counterparties: [],
        showDocumentForm: false,
        busy: false,
        activePayment: null,
        dialogPurpose: null
    };

    const el = {
        content: document.getElementById("financeContent"),
        status: document.getElementById("financeStatus"),
        refresh: document.getElementById("financeRefresh"),
        dialog: document.getElementById("financeDialog"),
        dialogContent: document.getElementById("financeDialogContent")
    };

    function updatePageHeading() {
        const headings = {
            overview: {
                title: "Financial overview",
                description: ""
            },
            accounts: {
                title: "Chart of accounts",
                description: ""
            },
            documents: {
                title: "Invoices & bills",
                description: ""
            },
            payments: { title: "Payments", description: "" },
            ledger: {
                title: "General ledger",
                description: ""
            }
        };

        const heading = headings[state.view] || headings.overview;
        const titleElement = document.querySelector(".finance-page-header h1");
        const descriptionElement = document.querySelector(".finance-page-header .page-description");

        if (titleElement) titleElement.textContent = heading.title;
        if (descriptionElement) descriptionElement.textContent = heading.description;

        document.title = `${heading.title} | Accounts & Payments | ChainBridge`;
    }
    function esc(value) {
        return String(value ?? "").replace(/[&<>"']/g, character => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
        })[character]);
    }

    function money(value, currency = "KES") {
        const amount = Number(value ?? 0);
        if (!Number.isFinite(amount)) return "KSh 0.00";
        try {
            return new Intl.NumberFormat("en-KE", {
                style: "currency", currency,
                minimumFractionDigits: 2, maximumFractionDigits: 2
            }).format(amount);
        } catch {
            return `KSh ${amount.toFixed(2)}`;
        }
    }

    function dateToday() {
        const parts = new Intl.DateTimeFormat("en-CA", {
            timeZone: "Africa/Nairobi", year: "numeric", month: "2-digit", day: "2-digit"
        }).formatToParts(new Date());
        const data = Object.fromEntries(parts.map(part => [part.type, part.value]));
        return `${data.year}-${data.month}-${data.day}`;
    }

    function dateText(value) {
        if (!value) return "—";
        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[3]}/${match[2]}/${match[1]}` : esc(value);
    }

    function apiData(response, defaultMessage) {
        const data = response?.data ?? response;
        if (response?.ok === false || data?.success !== true) {
            throw new Error(data?.error || data?.message || defaultMessage || "The request could not be completed.");
        }
        return data;
    }

    function notify(message, type = "success") {
        el.status.textContent = message;
        el.status.className = `finance-status${type ? ` ${type}` : ""}`;
        el.status.hidden = false;
    }

    function clearNotice() {
        el.status.textContent = "";
        el.status.hidden = true;
        el.status.className = "finance-status";
    }

    function setBusy(value) {
        state.busy = Boolean(value);
        el.refresh.disabled = state.busy;
        el.refresh.textContent = state.busy ? "Refreshing…" : "Refresh";
    }

    async function loadAll() {
        setBusy(true);
        try {
            const responses = await Promise.all([
                getJson("finance/dashboard.php"),
                getJson("finance/accounts-list.php"),
                getJson("finance/documents-list.php?limit=200"),
                getJson("finance/payments-list.php?limit=200"),
                getJson("finance/ledger-list.php?limit=200"),
                getJson("finance/counterparties-list.php")
            ]);
            const dashboard = apiData(responses[0], "Unable to load the finance dashboard.");
            const accounts = apiData(responses[1], "Unable to load accounts.");
            const documents = apiData(responses[2], "Unable to load invoices and bills.");
            const payments = apiData(responses[3], "Unable to load payments.");
            const ledger = apiData(responses[4], "Unable to load the ledger.");
            const counterparties = apiData(responses[5], "Unable to load connected organisations.");

            state.summary = dashboard.summary || {};
            state.recentPayments = Array.isArray(dashboard.recent_payments) ? dashboard.recent_payments : [];
            state.accounts = Array.isArray(accounts.accounts) ? accounts.accounts : [];
            state.documents = Array.isArray(documents.documents) ? documents.documents : [];
            state.payments = Array.isArray(payments.payments) ? payments.payments : [];
            state.ledger = Array.isArray(ledger.ledger_lines) ? ledger.ledger_lines : [];
            state.counterparties = Array.isArray(counterparties.counterparties) ? counterparties.counterparties : [];
            state.organizationId = Number(counterparties.organization_id || window.__nexoraAccount?.organization?.organization_id || window.__nexoraAccount?.organization_id || 0);
            render();
        } catch (error) {
            console.error("Finance workspace load failed:", error);
            notify(error.message || "Unable to load Accounts & Payments.", "error");
            if (!el.content.innerHTML.trim()) el.content.innerHTML = '<div class="finance-empty">Finance information could not be loaded. Use Refresh to try again.</div>';
        } finally {
            setBusy(false);
        }
    }

    function statusPill(value) {
        const status = String(value || "UNKNOWN").toUpperCase();
        const type = ["CONFIRMED", "PAID", "POSTED", "ISSUED"].includes(status)
            ? "success"
            : ["PENDING", "DRAFT", "PARTIALLY_PAID", "OVERDUE"].includes(status)
                ? "warning"
                : ["FAILED", "VOID", "CANCELLED", "REVERSED"].includes(status) ? "danger" : "";
        return `<span class="finance-status-pill ${type}">${esc(status.replaceAll("_", " "))}</span>`;
    }

    function stat(label, value, note = "") {
        return `<div class="finance-stat"><span class="finance-stat-label">${esc(label)}</span><strong class="finance-stat-value">${esc(value)}</strong>${note ? `<span class="finance-stat-note">${esc(note)}</span>` : ""}</div>`;
    }

    function emptyRow(message, columns) {
        return `<tr><td colspan="${columns}" class="finance-muted" style="text-align:center;padding:25px">${esc(message)}</td></tr>`;
    }

    function paymentRows(rows, dashboard = false) {
        if (!rows.length) return emptyRow("No payments recorded yet.", dashboard ? 5 : 7);
        return rows.map(payment => {
            const direction = payment.direction || (Number(payment.payer_organization_id) === Number(payment.organization_id) ? "OUTGOING" : "INCOMING");
            const action = dashboard ? "" : paymentAction(payment);
            return `<tr>
                <td class="finance-number">${esc(payment.payment_id ? `PAY-${payment.payment_id}` : "—")}</td>
                <td>${esc(payment.counterparty_name || (direction === "OUTGOING" ? payment.payee_name : payment.payer_name) || "—")}</td>
                <td>${esc(String(payment.payment_method || "OTHER").replaceAll("_", " "))}</td>
                <td class="finance-number">${esc(money(payment.amount, payment.currency_code || "KES"))}</td>
                <td>${statusPill(payment.status)}</td>
                ${dashboard ? "" : `<td>${esc(dateText(payment.payment_date))}</td><td><div class="finance-inline-actions">${action}</div></td>`}
                ${dashboard ? `<td>${esc(dateText(payment.payment_date))}</td>` : ""}
            </tr>`;
        }).join("");
    }

    function paymentAction(payment) {
        const currentOrg = currentOrganizationId();
        const isIncoming = Number(payment.payee_organization_id) === currentOrg;
        const isOutgoing = Number(payment.payer_organization_id) === currentOrg;
        if (payment.status === "PENDING" && isIncoming) {
            return `<button type="button" class="finance-button finance-button-primary finance-button-small" data-action="confirm-payment" data-id="${Number(payment.payment_id)}">Confirm receipt</button>`;
        }
        if (payment.status === "CONFIRMED" && (isIncoming || isOutgoing) && Number(payment.amount) > Number(payment.allocated_to_own_documents || 0)) {
            return `<button type="button" class="finance-button finance-button-secondary finance-button-small" data-action="allocate-payment" data-id="${Number(payment.payment_id)}">Allocate</button>`;
        }
        return '<span class="finance-muted">—</span>';
    }

    function render() {
        if (state.view === "overview") renderOverview();
        else if (state.view === "accounts") renderAccounts();
        else if (state.view === "documents") renderDocuments();
        else if (state.view === "payments") renderPayments();
        else renderLedger();
    }

    function renderOverview() {
        const s = state.summary;
        el.content.innerHTML = `
            <div class="finance-summary-grid">
                ${stat("Money owed to you", money(s.receivables), `${Number(s.receivable_documents || 0)} receivable documents`)}
                ${stat("Money you owe", money(s.payables), `${Number(s.payable_documents || 0)} payable documents`)}
                ${stat("Net position", money(s.net_position), "Receivables less payables")}
                ${stat("Overdue documents", String(s.overdue_documents || 0), "Documents past their due date")}
            </div>
            <div class="finance-summary-grid">
                ${stat("Incoming payments pending", money(s.pending_incoming_amount), `${Number(s.pending_incoming_count || 0)} awaiting confirmation`)}
                ${stat("Outgoing payments pending", money(s.pending_outgoing_amount), `${Number(s.pending_outgoing_count || 0)} awaiting receipt confirmation`)}
                ${stat("Active accounts", String(state.accounts.length), "Ledger accounts available")}
                ${stat("Recorded payments", String(state.payments.length), "Latest 200 payment records")}
            </div>
            <section class="finance-panel">
                <div class="finance-panel-heading"><h3>Recent payments</h3><a href="payments.html" class="finance-button finance-button-secondary finance-button-small">View all payments</a></div>
                <div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>Reference</th><th>Counterparty</th><th>Method</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>${paymentRows(state.recentPayments, true)}</tbody></table></div>
            </section>
            `;
    }

    function renderAccounts() {
        const selectedAccount = state.accounts.find(account => Number(account.account_id) === Number(state.selectedAccountId)) || state.accounts[0] || null;
        if (selectedAccount) state.selectedAccountId = Number(selectedAccount.account_id);

        const search = String(state.accountSearch || "").trim().toLowerCase();
        const visibleAccounts = state.accounts.filter(account => {
            const value = `${account.account_code} ${account.account_name} ${account.account_type} ${account.normal_balance}`.toLowerCase();
            return !search || value.includes(search);
        });

        const accountRows = visibleAccounts.map(account => {
            const selected = selectedAccount && Number(account.account_id) === Number(selectedAccount.account_id);
            return `<button type="button" class="finance-account-row${selected ? " active" : ""}" data-action="select-account" data-id="${Number(account.account_id)}" data-account-search="${esc(`${account.account_code} ${account.account_name} ${account.account_type} ${account.normal_balance}`.toLowerCase())}">
                <span class="finance-account-mark" aria-hidden="true">${esc(String(account.account_name || "A").trim().slice(0, 1).toUpperCase())}</span>
                <span class="finance-account-row-copy"><strong>${esc(account.account_name)}</strong><small>${esc(account.account_code)} · ${esc(account.account_type)}</small></span>
                <span class="finance-account-row-status">${statusPill(account.status)}</span>
                <span class="finance-account-row-balance">${esc(money(account.balance))}</span>
            </button>`;
        }).join("") || `<div class="finance-empty finance-account-list-empty">${state.accounts.length ? "No accounts match your search." : "No active financial accounts were found."}</div>`;

        let detail = `<div class="finance-account-empty-detail"><h2>Select an account</h2><p>Choose an account from the list to inspect its balance and posted transactions.</p></div>`;
        if (selectedAccount) {
            const transactions = state.ledger.filter(line => String(line.account_code) === String(selectedAccount.account_code)).slice(0, 8);
            const transactionRows = transactions.map(line => `<tr>
                <td>${esc(dateText(line.entry_date))}</td>
                <td class="finance-number">${esc(line.entry_number || `JE-${line.journal_entry_id}` || "—")}</td>
                <td>${esc(line.entry_description || line.line_description || "—")}</td>
                <td class="finance-number">${esc(money(line.debit_amount))}</td>
                <td class="finance-number">${esc(money(line.credit_amount))}</td>
            </tr>`).join("") || `<tr><td colspan="5" class="finance-account-no-transactions">No posted transactions have affected this account yet.</td></tr>`;

            detail = `<section class="finance-account-detail-panel">
                <header class="finance-account-detail-heading">
                    <span class="finance-account-mark finance-account-mark-large" aria-hidden="true">${esc(String(selectedAccount.account_name || "A").trim().slice(0, 1).toUpperCase())}</span>
                    <div class="finance-account-detail-title"><h2>${esc(selectedAccount.account_name)}</h2><p>Account code ${esc(selectedAccount.account_code)}</p></div>
                    ${statusPill(selectedAccount.status)}
                </header>
                <div class="finance-account-summary-strip">
                    <div><span>Account type</span><strong>${esc(selectedAccount.account_type)}</strong></div>
                    <div><span>Normal balance</span><strong>${esc(selectedAccount.normal_balance)}</strong></div>
                    <div><span>Current balance</span><strong class="finance-account-current-balance">${esc(money(selectedAccount.balance))}</strong></div>
                </div>
                <section class="finance-account-detail-section">
                    <h3>Account details</h3>
                    <dl class="finance-account-detail-grid">
                        <div><dt>Account code</dt><dd>${esc(selectedAccount.account_code)}</dd></div>
                        <div><dt>Account name</dt><dd>${esc(selectedAccount.account_name)}</dd></div>
                        <div><dt>Account type</dt><dd>${esc(selectedAccount.account_type)}</dd></div>
                        <div><dt>Normal balance</dt><dd>${esc(selectedAccount.normal_balance)}</dd></div>
                        <div><dt>Status</dt><dd>${statusPill(selectedAccount.status)}</dd></div>
                    </dl>
                    
                </section>
                <section class="finance-account-detail-section finance-account-transactions">
                    <div class="finance-panel-heading"><h3>Recent transactions</h3><a class="finance-text-link" href="ledger.html">View ledger</a></div>
                    <div class="finance-table-wrap"><table class="finance-table finance-account-transactions-table"><thead><tr><th>Date</th><th>Reference</th><th>Description</th><th>Debit</th><th>Credit</th></tr></thead><tbody>${transactionRows}</tbody></table></div>
                </section>
            </section>`;
        }

        el.content.innerHTML = `<div class="finance-account-layout">
            <aside class="finance-account-list-panel" aria-label="Financial accounts">
                <header class="finance-account-list-heading"><div><h2>Financial accounts</h2></div><span class="finance-account-count">${state.accounts.length}</span></header>
                <div class="finance-account-search"><label class="visually-hidden" for="financeAccountSearch">Search accounts</label><input id="financeAccountSearch" type="search" value="${esc(state.accountSearch)}" placeholder="Search accounts by name or code" autocomplete="off"></div>
                <div class="finance-account-list" id="financeAccountList">${accountRows}</div>
            </aside>
            <div class="finance-account-detail-column">${detail}</div>
        </div>`;
    }

    function documentRows(rows) {
        if (!rows.length) return emptyRow("No invoices or bills match this view.", 7);
        return rows.map(document => {
            const documentStatus = document.calculated_status || document.status;
            const issue = document.status === "DRAFT"
                ? `<button type="button" class="finance-button finance-button-primary finance-button-small" data-action="issue-document" data-id="${Number(document.document_id)}">Issue</button>`
                : "";
            return `<tr>
                <td><strong>${esc(document.document_number)}</strong><div class="finance-muted">${esc(document.document_type)}</div></td>
                <td>${esc(document.counterparty_name || "—")}</td>
                <td>${esc(dateText(document.issue_date))}</td>
                <td class="finance-number">${esc(money(document.total_amount, document.currency_code || "KES"))}</td>
                <td class="finance-number">${esc(money(document.balance_due, document.currency_code || "KES"))}</td>
                <td>${statusPill(documentStatus)}</td>
                <td><div class="finance-inline-actions">${issue}<button type="button" class="finance-button finance-button-secondary finance-button-small" data-action="view-document" data-id="${Number(document.document_id)}">Details</button></div></td>
            </tr>`;
        }).join("");
    }

    function renderDocuments() {
        const visibleDocuments = state.documents;
        const options = state.counterparties.map(org => `<option value="${Number(org.organization_id)}">${esc(org.organization_name)} (${esc(org.organization_type)})</option>`).join("");
        const documentForm = state.showDocumentForm ? `
            <section class="finance-panel finance-form-panel">
                <div class="finance-section-heading"><div><h2>New invoice or bill</h2></div></div>
                <form id="financeDocumentForm" data-form="document">
                    <div class="finance-form-grid">
                        <div class="finance-field"><label for="financeDocumentType">Document type</label><select id="financeDocumentType" name="document_type" required><option value="INVOICE">Invoice · money owed to you</option><option value="BILL">Bill · money you owe</option></select></div>
                        <div class="finance-field"><label for="financeCounterparty">Supplier / retailer</label><select id="financeCounterparty" name="counterparty_organization_id" required><option value="">Choose organisation…</option>${options}</select></div>
                        <div class="finance-field"><label for="financeDocumentNumber">Document number</label><input id="financeDocumentNumber" name="document_number" maxlength="60" required placeholder="e.g. INV-2026-001"></div>
                        <div class="finance-field"><label for="financeIssueDate">Issue date</label><input type="date" id="financeIssueDate" name="issue_date" value="${dateToday()}" required></div>
                        <div class="finance-field"><label for="financeDueDate">Due date</label><input type="date" id="financeDueDate" name="due_date"></div>
                        <div class="finance-field"><label for="financeNotes">Notes</label><input id="financeNotes" name="notes" maxlength="10000" placeholder="Optional note"></div>
                    </div>
                    <div class="finance-line-header"><h3>Document lines</h3><button type="button" class="finance-button finance-button-secondary finance-button-small" data-action="add-document-line">Add line</button></div>
                    <div id="financeDocumentLines">${lineRow(0)}</div>
                    <div class="finance-form-actions"><button type="button" class="finance-button finance-button-secondary" data-action="cancel-new-document">Cancel</button><button type="submit" class="finance-button finance-button-primary">Save draft</button></div>
                </form>
            </section>` : "";
        const rows = state.documents;
        el.content.innerHTML = `<div class="finance-section-heading finance-document-toolbar"><button type="button" class="finance-button finance-button-primary" data-action="new-document">New invoice or bill</button></div>
            ${state.counterparties.length ? "" : '<div class="finance-status">No active supply relationships were found. A financial document can only be created for a connected organisation.</div>'}
            ${documentForm}
            <section class="finance-panel"><div class="finance-panel-heading"><h3>Documents</h3><span class="finance-muted">${rows.length} shown</span></div><div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>Document</th><th>Counterparty</th><th>Issue date</th><th>Total</th><th>Balance due</th><th>Status</th><th>Actions</th></tr></thead><tbody>${documentRows(visibleDocuments)}</tbody></table></div></section>`;
        const form = document.getElementById("financeDocumentForm");
        if (form) form.addEventListener("submit", submitDocument);
    }

    function lineRow(index) {
        return `<div class="finance-line-row" data-line-row="${index}">
            <input name="description" maxlength="500" required aria-label="Line ${index + 1} description" placeholder="Description or product">
            <input name="quantity" type="number" min="0.001" step="0.001" required value="1" aria-label="Quantity">
            <input name="unit_price" type="number" min="0" step="0.01" required value="0.00" aria-label="Unit price" class="line-price">
            <input name="tax_rate" type="number" min="0" max="100" step="0.001" required value="0" aria-label="Tax percent" class="line-tax">
            <button type="button" data-action="remove-document-line" aria-label="Remove line">×</button>
        </div>`;
    }

    function renderPayments() {
        const options = state.counterparties.map(org => `<option value="${Number(org.organization_id)}">${esc(org.organization_name)} (${esc(org.organization_type)})</option>`).join("");
        const rows = paymentRows(state.payments, false);
        el.content.innerHTML = `<section class="finance-panel finance-form-panel"><form id="financePaymentForm" data-form="payment">
                <div class="finance-form-grid">
                    <div class="finance-field"><label for="financePayee">Payee organisation</label><select id="financePayee" name="payee_organization_id" required><option value="">Choose organisation…</option>${options}</select></div>
                    <div class="finance-field"><label for="financePaymentAmount">Amount (KES)</label><input id="financePaymentAmount" name="amount" type="number" min="0.01" step="0.01" required></div>
                    <div class="finance-field"><label for="financePaymentMethod">Payment method</label><select id="financePaymentMethod" name="payment_method" required title="This records payment details only. It does not initiate a transfer."><option value="BANK_TRANSFER">Bank transfer</option><option value="MPESA">M-Pesa</option><option value="CASH">Cash</option><option value="CHEQUE">Cheque</option><option value="OTHER">Other</option></select></div>
                    <div class="finance-field"><label for="financePaymentDate">Payment date</label><input id="financePaymentDate" name="payment_date" type="date" value="${dateToday()}" required></div>
                    <div class="finance-field"><label for="financePaymentReference">Payment reference</label><input id="financePaymentReference" name="reference_number" maxlength="120" placeholder="M-Pesa code, bank reference…"></div>
                    <div class="finance-field"><label for="financePaymentNotes">Notes</label><input id="financePaymentNotes" name="notes" maxlength="10000" placeholder="Optional note"></div>
                </div>
                <div class="finance-form-actions"><button type="submit" class="finance-button finance-button-primary">Record payment</button></div>
                
            </form></section>
            <section class="finance-panel"><div class="finance-panel-heading"><h3>Payment history</h3><span class="finance-muted">${state.payments.length} shown</span></div><div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>Payment</th><th>Counterparty</th><th>Method</th><th>Amount</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody>${rows}</tbody></table></div></section>`;
        const form = document.getElementById("financePaymentForm");
        if (form) form.addEventListener("submit", submitPayment);
    }

    function renderLedger() {
        const rows = state.ledger.map(line => `<tr>
            <td class="finance-number">${esc(line.entry_number || line.journal_entry_number || line.journal_entry_id || "—")}</td>
            <td>${esc(dateText(line.entry_date))}</td>
            <td>${esc(line.account_code || "")} ${esc(line.account_name || "")}</td>
            <td>${esc(line.entry_description || line.description || line.line_description || "—")}</td>
            <td class="finance-number">${esc(money(line.debit_amount))}</td>
            <td class="finance-number">${esc(money(line.credit_amount))}</td>
            <td>${statusPill(line.status || "POSTED")}</td>
        </tr>`).join("") || emptyRow("No posted ledger entries yet. Issued documents and confirmed payments will appear here.", 7);
        el.content.innerHTML = `<section class="finance-panel"><div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>Entry</th><th>Date</th><th>Account</th><th>Description</th><th>Debit</th><th>Credit</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table></div></section>
            `;
    }

    async function submitDocument(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);
        const lines = [...form.querySelectorAll("[data-line-row]")].map(row => ({
            description: row.querySelector('[name="description"]').value.trim(),
            quantity: Number(row.querySelector('[name="quantity"]').value),
            unit_price: Number(row.querySelector('[name="unit_price"]').value),
            tax_rate: Number(row.querySelector('[name="tax_rate"]').value)
        }));
        if (!lines.length) { notify("Add at least one document line.", "error"); return; }
        if (lines.some(line => !line.description || !Number.isFinite(line.quantity) || line.quantity <= 0 || !Number.isFinite(line.unit_price) || line.unit_price < 0 || !Number.isFinite(line.tax_rate) || line.tax_rate < 0 || line.tax_rate > 100)) {
            notify("Check each document line. Quantity must be positive and price and tax must be valid.", "error"); return;
        }
        const body = {
            document_type: formData.get("document_type"),
            counterparty_organization_id: Number(formData.get("counterparty_organization_id")),
            document_number: String(formData.get("document_number") || "").trim(),
            issue_date: formData.get("issue_date"),
            due_date: formData.get("due_date") || "",
            notes: String(formData.get("notes") || "").trim(),
            lines
        };
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            const response = await postJson("finance/document-save.php", body);
            const data = apiData(response, "Unable to save the draft.");
            state.showDocumentForm = false;
            await loadAll();
            notify(`${body.document_type === "INVOICE" ? "Invoice" : "Bill"} draft ${body.document_number} saved. Issue it when ready.`, "success");
        } catch (error) {
            notify(error.message || "Unable to save the document.", "error");
            submit.disabled = false;
        }
    }

    async function submitPayment(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);
        const body = {
            payee_organization_id: Number(formData.get("payee_organization_id")),
            amount: Number(formData.get("amount")),
            payment_method: formData.get("payment_method"),
            reference_number: String(formData.get("reference_number") || "").trim(),
            payment_date: formData.get("payment_date"),
            notes: String(formData.get("notes") || "").trim()
        };
        if (!Number.isFinite(body.amount) || body.amount <= 0) { notify("Enter a payment amount greater than zero.", "error"); return; }
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            await postFinance("finance/payment-record.php", body, "Payment recorded as pending. The receiving organisation must confirm receipt.");
            form.reset();
            const dateInput = document.getElementById("financePaymentDate");
            if (dateInput) dateInput.value = dateToday();
            await loadAll();
            notify("Payment recorded as pending. It will not affect balances until the receiving organisation confirms receipt.", "success");
        } catch (error) {
            notify(error.message || "Unable to record the payment.", "error");
            submit.disabled = false;
        }
    }

    async function postFinance(path, body, message) {
        const response = await postJson(path, body);
        const data = apiData(response, message);
        return data;
    }

    async function issueDocument(id) {
        if (state.busy) return;
        try {
            const response = await postJson("finance/document-issue.php", { document_id: Number(id) });
            const data = apiData(response, "Unable to issue the document.");
            await loadAll();
            notify(data.message || "Document issued and posted to the ledger.", "success");
        } catch (error) {
            notify(error.message || "Unable to issue this document.", "error");
        }
    }

    function openDocumentDetails(id) {
        const doc = state.documents.find(item => Number(item.document_id) === Number(id));
        if (!doc) return;
        const lines = Array.isArray(doc.lines) ? doc.lines : [];
        el.dialogContent.innerHTML = `<h2>${esc(doc.document_type)} ${esc(doc.document_number)}</h2>
            <p>${esc(doc.counterparty_name)} · ${esc(doc.calculated_status || doc.status)}</p>
            <div class="finance-summary-grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
                ${stat("Document total", money(doc.total_amount, doc.currency_code || "KES"))}
                ${stat("Balance due", money(doc.balance_due, doc.currency_code || "KES"))}
            </div>
            <section class="finance-panel"><div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>Line</th><th>Qty</th><th>Unit price</th><th>Total</th></tr></thead><tbody>${lines.map(line => `<tr><td>${esc(line.description)}</td><td>${esc(line.quantity)}</td><td>${esc(money(line.unit_price))}</td><td>${esc(money(line.line_total))}</td></tr>`).join("") || emptyRow("No lines", 4)}</tbody></table></div></section>
            <p class="finance-muted">Issued ${esc(dateText(doc.issue_date))} · Due ${esc(dateText(doc.due_date))}</p>
            <div class="finance-dialog-actions"><button type="button" class="finance-button finance-button-secondary" data-dialog-action="close">Close</button>${doc.status === "DRAFT" ? `<button type="button" class="finance-button finance-button-primary" data-dialog-action="issue" data-id="${Number(doc.document_id)}">Issue document</button>` : ""}</div>`;
        el.dialog.showModal();
    }

    function paymentCanUseDocument(payment, document) {
        const orgId = currentOrganizationId();
        const isIncoming = Number(payment.payee_organization_id) === orgId;
        const counterpartyId = isIncoming ? Number(payment.payer_organization_id) : Number(payment.payee_organization_id);
        const expectedDirection = isIncoming ? "RECEIVABLE" : "PAYABLE";
        return document.direction === expectedDirection
            && Number(document.counterparty_organization_id) === counterpartyId
            && !["DRAFT", "VOID", "PAID"].includes(String(document.calculated_status || document.status))
            && Number(document.balance_due) > 0.005;
    }

    function currentOrganizationId() {
        return Number(state.organizationId || window.__nexoraAccount?.organization?.organization_id || window.__nexoraAccount?.organization_id || 0);
    }

    function openPaymentAction(paymentId, purpose) {
        const payment = state.payments.find(item => Number(item.payment_id) === Number(paymentId));
        if (!payment) return;
        state.activePayment = payment;
        state.dialogPurpose = purpose;
        const docs = state.documents.filter(document => paymentCanUseDocument(payment, document));
        const list = docs.map(document => `<label class="finance-allocation-row">
            <input type="checkbox" data-allocation-check="${Number(document.document_id)}">
            <span><strong>${esc(document.document_number)}</strong><br><span class="finance-muted">${esc(document.counterparty_name)} · due ${esc(money(document.balance_due))}</span></span>
            <input type="number" data-allocation-amount="${Number(document.document_id)}" min="0.01" max="${Number(document.balance_due)}" step="0.01" placeholder="Amount" disabled>
        </label>`).join("");
        const title = purpose === "confirm" ? "Confirm receipt of payment" : "Allocate confirmed payment";
        const description = purpose === "confirm"
            ? `Confirm that ${money(payment.amount)} from ${payment.payer_name || "the payer"} has actually been received. Any amount not allocated will be recorded as an advance.`
            : `Allocate a confirmed payment of ${money(payment.amount)} to your own invoices or bills. Only the unused payment amount can be allocated.`;
        const noDocs = docs.length ? "" : '<p class="finance-status">No eligible open documents match this counterparty. You can continue without an allocation; the unmatched amount remains an advance.</p>';
        el.dialogContent.innerHTML = `<h2>${title}</h2><p>${esc(description)}</p>${noDocs}
            <form id="financeAllocationForm" data-purpose="${esc(purpose)}">
                <div class="finance-allocation-list">${list}</div>
                <p class="finance-footnote">Choose the document(s) to allocate. Do not select more than the payment amount or the outstanding balance.</p>
                <div class="finance-dialog-actions"><button type="button" class="finance-button finance-button-secondary" data-dialog-action="close">Cancel</button><button type="submit" class="finance-button finance-button-primary">${purpose === "confirm" ? "Confirm receipt" : "Save allocation"}</button></div>
            </form>`;
        el.dialog.showModal();
    }

    async function submitPaymentAction(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const payment = state.activePayment;
        if (!payment) return;
        const purpose = form.dataset.purpose;
        const allocations = [];
        let total = 0;
        for (const checkbox of form.querySelectorAll("[data-allocation-check]")) {
            if (!checkbox.checked) continue;
            const id = Number(checkbox.dataset.allocationCheck);
            const input = form.querySelector(`[data-allocation-amount="${id}"]`);
            const amount = Number(input?.value);
            const doc = state.documents.find(item => Number(item.document_id) === id);
            if (!doc || !Number.isFinite(amount) || amount <= 0 || amount - Number(doc.balance_due) > 0.005) {
                notify("Enter a valid allocation that does not exceed the document balance.", "error");
                return;
            }
            total += amount;
            allocations.push({ document_id: id, allocated_amount: Math.round(amount * 100) / 100 });
        }
        const paymentTotal = Number(payment.amount);
        if (total - paymentTotal > 0.005) {
            notify("The allocations exceed the payment amount.", "error");
            return;
        }
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            if (purpose === "confirm") {
                const response = await postJson("finance/payment-confirm.php", { payment_id: Number(payment.payment_id), allocations });
                const data = apiData(response, "Unable to confirm receipt of payment.");
                el.dialog.close();
                await loadAll();
                notify(data.message || "Payment receipt confirmed.", "success");
            } else {
                if (!allocations.length) {
                    notify("Select at least one document for allocation.", "error");
                    submit.disabled = false;
                    return;
                }
                const response = await postJson("finance/payment-allocation-save.php", { payment_id: Number(payment.payment_id), allocations });
                const data = apiData(response, "Unable to allocate the payment.");
                el.dialog.close();
                await loadAll();
                notify(data.message || "Payment allocation saved.", "success");
            }
        } catch (error) {
            notify(error.message || "The payment action failed.", "error");
            submit.disabled = false;
        }
    }

    function onContentClick(event) {
        const button = event.target.closest("button[data-action]");
        if (!button) return;
        const action = button.dataset.action;
        if (action === "select-account") {
            state.selectedAccountId = Number(button.dataset.id);
            renderAccounts();
        } else if (action === "open-view") {
            const viewFiles = { overview: "overview.html", accounts: "accounts.html", documents: "documents.html", payments: "payments.html", ledger: "ledger.html" };
            goTo(`modules/finance/accounts/${viewFiles[button.dataset.view] || "overview.html"}`);
        } else if (action === "new-document") {
            state.showDocumentForm = true;
            renderDocuments();
            document.getElementById("financeDocumentNumber")?.focus();
        } else if (action === "cancel-new-document") {
            state.showDocumentForm = false;
            renderDocuments();
        } else if (action === "add-document-line") {
            const list = document.getElementById("financeDocumentLines");
            if (list && list.querySelectorAll("[data-line-row]").length < 100) {
                const index = list.querySelectorAll("[data-line-row]").length;
                list.insertAdjacentHTML("beforeend", lineRow(index));
            } else notify("A document can contain up to 100 lines.", "error");
        } else if (action === "remove-document-line") {
            const rows = document.querySelectorAll("#financeDocumentLines [data-line-row]");
            if (rows.length <= 1) { notify("A document must contain at least one line.", "error"); return; }
            button.closest("[data-line-row]")?.remove();
        } else if (action === "issue-document") {
            issueDocument(button.dataset.id);
        } else if (action === "view-document") {
            openDocumentDetails(button.dataset.id);
        } else if (action === "confirm-payment") {
            openPaymentAction(button.dataset.id, "confirm");
        } else if (action === "allocate-payment") {
            openPaymentAction(button.dataset.id, "allocate");
        }
    }

    function onDialogClick(event) {
        const button = event.target.closest("button[data-dialog-action]");
        if (!button) return;
        const action = button.dataset.dialogAction;
        if (action === "close") {
            el.dialog.close();
        } else if (action === "issue") {
            const id = button.dataset.id;
            el.dialog.close();
            issueDocument(id);
        }
    }

    function onDialogChange(event) {
        const checkbox = event.target.closest("[data-allocation-check]");
        if (!checkbox) return;
        const id = Number(checkbox.dataset.allocationCheck);
        const amount = el.dialogContent.querySelector(`[data-allocation-amount="${id}"]`);
        if (amount) {
            amount.disabled = !checkbox.checked;
            if (checkbox.checked && !amount.value) {
                const document = state.documents.find(item => Number(item.document_id) === id);
                amount.value = Math.min(Number(state.activePayment?.amount || 0), Number(document?.balance_due || 0)).toFixed(2);
            }
        }
    }

    el.refresh.addEventListener("click", () => { clearNotice(); loadAll(); });
    el.content.addEventListener("click", onContentClick);
    el.content.addEventListener("input", event => {
        if (event.target.id !== "financeAccountSearch") return;
        state.accountSearch = event.target.value;
        const query = state.accountSearch.trim().toLowerCase();
        el.content.querySelectorAll("button[data-action=\"select-account\"]").forEach(row => {
            row.hidden = query !== "" && !(row.dataset.accountSearch || "").includes(query);
        });
        const list = document.getElementById("financeAccountList");
        if (list && ![...list.querySelectorAll("button[data-action=\"select-account\"]")].some(row => !row.hidden)) {
            let empty = list.querySelector(".finance-account-search-empty");
            if (!empty) {
                empty = document.createElement("div");
                empty.className = "finance-empty finance-account-list-empty finance-account-search-empty";
                empty.textContent = "No accounts match your search.";
                list.appendChild(empty);
            }
            empty.hidden = false;
        } else {
            const empty = list?.querySelector(".finance-account-search-empty");
            if (empty) empty.hidden = true;
        }
    });
    el.dialogContent.addEventListener("click", onDialogClick);
    el.dialogContent.addEventListener("change", onDialogChange);
    el.dialogContent.addEventListener("submit", event => {
        if (event.target.matches("#financeAllocationForm")) submitPaymentAction(event);
    });

    updatePageHeading();
    loadAll();
})();
