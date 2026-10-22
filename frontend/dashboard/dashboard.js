const GROUP_ORDER = ["Needs your attention", "Waiting on others"];

const attentionSelect = field("attentionSelect");
const attentionDetail = field("attentionDetail");
const attentionActions = field("attentionActions");
const openButton = field("openButton");
const dashboardMessage = field("dashboardMessage");

let items = [];

const selectedItem = () => items[Number(attentionSelect.value)];

function renderSelectedItem() {
    const item = selectedItem();

    renderDetailList(attentionDetail, item
        ? [
            ["Item", item.title],
            ["Details", item.detail],
            ["Group", item.group]
        ]
        : []);

    attentionActions.hidden = !item;
}

function renderItems() {
    const indexed = items.map((item, index) => ({ ...item, index }));

    attentionSelect.replaceChildren(
        ...(items.length === 0
            ? [option("", "Nothing needs your attention")]
            : groupedOptions(indexed, item => item.group, item => option(String(item.index), `${item.title}  |  ${item.detail}`)))
    );
    attentionSelect.disabled = items.length === 0;

    renderSelectedItem();
}

async function loadDashboard() {
    const { ok, data } = await getJson("dashboard/overview.php");

    if (!ok) {
        showMessage(dashboardMessage, data.error || "Unable to load the dashboard.", "error");
        return;
    }

    renderDetailList(field("summaryList"), data.summary.map(entry => [entry.label, String(entry.value)]));

    items = [...data.items].sort((a, b) => GROUP_ORDER.indexOf(a.group) - GROUP_ORDER.indexOf(b.group));
    renderItems();
}

openButton.addEventListener("click", () => window.location.assign(pageUrl(selectedItem().path)));
attentionSelect.addEventListener("change", renderSelectedItem);

shellReady.then(account => {
    if (account) {
        loadDashboard();
    } 
});