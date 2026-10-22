const NEW_RECORD = "new";

function createRecordEditor(config) {
    const select = field("recordSelect");
    const form = field("recordForm");
    const message = field("formMessage");
    const button = field("saveButton");

    let records = [];

    const isCreating = () => select.value === NEW_RECORD;
    const selectedRecord = () => records.find(record => String(record[config.idKey]) === select.value);

    function renderSelect(selectedValue) {
        select.replaceChildren(
            option(NEW_RECORD, config.newLabel),
            ...records.map(record => option(String(record[config.idKey]), config.describe(record)))
        );
        select.value = selectedValue;
    }

    function fillForm() {
        const values = config.toForm(isCreating() ? null : selectedRecord());

        config.fieldIds.forEach(id => {
            field(id).value = values[id] ?? "";
        });
    }

    function applyMode() {
        const creating = isCreating();

        button.textContent = creating ? config.addLabel : "Save changes";
        config.onMode(creating);
    }

    function collectPayload() {
        const payload = Object.fromEntries(config.fieldIds.map(id => [id, fieldValue(id)]));

        if (!isCreating()) {
            payload[config.idField] = Number(select.value);
        }

        return payload;
    }

  

    async function load(selectedValue) {
        const { ok, data } = await getJson(config.listPath);

        if (!ok) {
            throw new Error(data.error || "Unable to load records.");
        }

        records = data[config.listKey];

        const first = records[0] ? String(records[0][config.idKey]) : NEW_RECORD;

        renderSelect(selectedValue ?? first);
        fillForm();
        applyMode();
    }

       async function submit(event) {
        event.preventDefault();

        const creating = isCreating();

        const data = await sendForm({
            form,
            button,
            message,
            path: creating ? config.createPath : config.updatePath,
            payload: collectPayload()
        });

        if (data !== null) {
            await load(creating ? String(data[config.idKey]) : select.value);
            showMessage(message, data.message, "success");
            if (typeof config.onSaved === "function") {
                await config.onSaved(data);
            }
        }

        applyMode();
    }

    function start() {
        select.addEventListener("change", () => {
            showMessage(message, "", "");
            clearFieldErrors(form);
            fillForm();
            applyMode();
        });

        form.addEventListener("submit", submit);
        clearErrorOnInput(form);

        return load();
    }

    return { start };
}