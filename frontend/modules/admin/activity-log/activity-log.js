const FILTER_IDS = {
    userId: "filterUser",
    action: "filterAction",
    from: "filterFrom",
    to: "filterTo"
};

const entrySelect = field("entrySelect");
const entryDetail = field("entryDetail");
const logMessage = field("logMessage");
const logNote = field("logNote");

let entries = [];


function personName(entry) {
    return entry.first_name ? `${entry.first_name} ${entry.last_name}` : "Unknown user";
}

function describeEntry(entry) {
    return `${entry.created_at}  |  ${personName(entry)}  |  ${readable(entry.action_type)}`;
}

function renderDetail(entry) {
    const rows = entry
        ? [
            ["When", entry.created_at],
            ["Who", entry.email ? `${personName(entry)}  (${entry.email})` : personName(entry)],
            ["Action", readable(entry.action_type)],
            ["Record", `${entry.entity_type.toLowerCase()} ${entry.entity_id ?? ""}`.trim()],
            ["Details", entry.description || "None"]
        ]
        : [];

        renderDetailList(entryDetail, rows);

}

function renderEntries(limit) {
    const options = entries.length === 0
        ? [option("", "No activity matches these filters")]
        : entries.map((entry, index) => option(String(index), describeEntry(entry)));

    entrySelect.replaceChildren(...options);
    entrySelect.disabled = entries.length === 0;
    renderDetail(entries[0]);

    logNote.textContent = entries.length === limit
        ? `Showing the latest ${limit} entries. Narrow the filters to see older ones.`
        : `${entries.length} ${entries.length === 1 ? "entry" : "entries"}`;
}

function filterQuery() {
    const params = new URLSearchParams();

    Object.entries(FILTER_IDS).forEach(([name, id]) => {
        if (fieldValue(id)) {
            params.set(name, fieldValue(id));
        }
    });

    return params.toString();
}

async function loadEntries() {
    showMessage(logMessage, "", "");

    const { ok, data } = await getJson(`audit/list.php?${filterQuery()}`);

    if (!ok) {
        showMessage(logMessage, data.error || "Unable to load the activity log.", "error");
        return null;
    }

    entries = data.entries;
    renderEntries(data.limit);

    return data;
}

function fillFilters(users, actions) {
    field("filterUser").replaceChildren(
        option("", "All users"),
        ...users.map(user => option(String(user.user_id), `${user.first_name} ${user.last_name}`))
    );

    field("filterAction").replaceChildren(
        option("", "All actions"),
        ...actions.map(action => option(action, readable(action)))
    );
}

async function initialise(account) {

    try {
        const { data: userData } = await getJson("users/list.php");
        const first = await loadEntries();

        fillFilters(userData.users, first?.actions ?? []);
    } catch (error) {
        showMessage(logMessage, "Unable to load the activity log.", "error");
    }
}

entrySelect.addEventListener("change", () => renderDetail(entries[Number(entrySelect.value)]));

Object.values(FILTER_IDS).forEach(id => field(id).addEventListener("change", loadEntries));

shellReady.then(account => {
    if (account) {
        initialise(account);
    }
});