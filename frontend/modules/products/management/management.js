const form = document.getElementById("productForm");
const productSelect = document.getElementById("existingProduct");
const formTitle = document.getElementById("formTitle");
const productMessage = document.getElementById("productMessage");
const saveButton = document.getElementById("saveProductButton");
const cancelButton = document.getElementById("cancelEditButton");
const imageInput = document.getElementById("productImage");
const previewContainer = document.getElementById("imagePreviewContainer");
const previewImage = document.getElementById("imagePreview");
const previewLabel = document.getElementById("imagePreviewLabel");

const inputs = {
    name: document.getElementById("productName"),
    category: document.getElementById("category"),
    availability: document.getElementById("availabilityStatus"),
    barcode: document.getElementById("barcode"),
    packSize: document.getElementById("packSize"),
    packaging: document.getElementById("packaging"),
    effectiveDate: document.getElementById("effectiveDate"),
    description: document.getElementById("description")
};

let account = null;
let products = [];
let selectedProduct = null;
let existingImagePath = null;

function showProductMessage(message, type = "error") {
    productMessage.textContent = message;
    productMessage.className = `form-message ${type}`;
}

function clearProductMessage() {
    productMessage.textContent = "";
    productMessage.className = "form-message";
}

function clearPreview() {
    previewImage.removeAttribute("src");
    previewContainer.hidden = true;
}

function showPreview(source, label) {
    previewImage.src = source;
    previewLabel.textContent = label;
    previewContainer.hidden = false;
}

function currentVersion(product) {
    return product.versions?.find(version => Number(version.is_current) === 1)
        ?? product.versions?.[0]
        ?? null;
}

function updateBarcodeRequirement() {
    const version = selectedProduct ? currentVersion(selectedProduct) : null;
    const canSetInitialBarcode = !selectedProduct || !version?.barcode;

    inputs.barcode.required =
        canSetInitialBarcode && inputs.availability.value === "AVAILABLE";
}

function setInitialFormState() {
    form.reset();
    selectedProduct = null;
    existingImagePath = null;

    inputs.barcode.disabled = false;
    inputs.packSize.disabled = false;
    inputs.packaging.disabled = false;
    inputs.effectiveDate.disabled = false;

    updateBarcodeRequirement();
    inputs.effectiveDate.required = true;

    formTitle.textContent = "Create a product";
    saveButton.textContent = "Create product";
    cancelButton.hidden = true;

    imageInput.value = "";
    clearPreview();
    clearProductMessage();
}

function populateProductSelect() {
    productSelect.replaceChildren(
        new Option("Create a new product", "")
    );

    const ownProducts = products.filter(product =>
        Number(product.manufacturer_id) === Number(account.organization.organization_id)
    );

    for (const product of ownProducts) {
        productSelect.add(
            new Option(product.product_name, String(product.product_id))
        );
    }
}

function selectProduct(productId) {
    if (!productId) {
        setInitialFormState();
        return;
    }

    const product = products.find(item =>
        String(item.product_id) === String(productId)
        && Number(item.manufacturer_id) === Number(account.organization.organization_id)
    );

    if (!product) {
        productSelect.value = "";
        setInitialFormState();
        showProductMessage("That product is not available for editing.");
        return;
    }

    const version = currentVersion(product);

    selectedProduct = product;
    existingImagePath = product.image_path || null;

    inputs.name.value = product.product_name || "";
    inputs.category.value = product.category || "";
    inputs.availability.value = product.availability_status || "AVAILABLE";
    inputs.description.value = product.description || "";
    inputs.barcode.value = version?.barcode || "";
    inputs.packSize.value = version?.pack_size || "";
    inputs.packaging.value = version?.packaging_description || "";
    inputs.effectiveDate.value = version?.effective_from || "";

    const canSetInitialBarcode = !version?.barcode;

    inputs.barcode.disabled = !canSetInitialBarcode;
    inputs.packSize.disabled = !canSetInitialBarcode;
    inputs.packaging.disabled = !canSetInitialBarcode;
    inputs.effectiveDate.disabled = !canSetInitialBarcode;

    updateBarcodeRequirement();
    inputs.effectiveDate.required = canSetInitialBarcode;

    formTitle.textContent = "Edit product information";
    saveButton.textContent = "Save changes";
    cancelButton.hidden = false;

    imageInput.value = "";
    clearProductMessage();

    if (existingImagePath) {
        showPreview(pageUrl(existingImagePath), "Current product image");
    } else {
        clearPreview();
    }
}

async function loadProducts() {
    const result = await getJson("products/list.php");

    if (!result.ok || result.data.success !== true) {
        throw new Error(result.data.error || "Unable to load products.");
    }

    products = result.data.products || [];
    populateProductSelect();
}

function validateForm() {
    if (!form.reportValidity()) {
        return false;
    }

    const barcode = inputs.barcode.value.trim();

    if (
        (inputs.availability.value === "AVAILABLE" || barcode !== "")
        && !/^[0-9]{8,14}$/.test(barcode)
    ) {
        showProductMessage("Enter an initial barcode containing 8 to 14 digits.");
        inputs.barcode.focus();
        return false;
    }

    const image = imageInput.files[0];

    if (image) {
        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];

        if (!allowedTypes.includes(image.type)) {
            showProductMessage("Choose a JPG, PNG, or WebP image.");
            return false;
        }

        if (image.size > 5 * 1024 * 1024) {
            showProductMessage("The image must not exceed 5 MB.");
            return false;
        }
    }

    return true;
}

async function submitProduct(event) {
    event.preventDefault();
    clearProductMessage();

    if (!validateForm()) {
        return;
    }

    const editing = selectedProduct !== null;
    const body = new FormData();

    body.append("productName", inputs.name.value.trim());
    body.append("category", inputs.category.value.trim());
    body.append("description", inputs.description.value.trim());
    body.append("availabilityStatus", inputs.availability.value);

    if (editing) {
        body.append("productId", String(selectedProduct.product_id));

        if (!currentVersion(selectedProduct)?.barcode) {
            body.append("barcode", inputs.barcode.value.trim());
            body.append("packSize", inputs.packSize.value.trim());
            body.append("packaging", inputs.packaging.value.trim());
            body.append("effectiveDate", inputs.effectiveDate.value);
        }
    } else {
        body.append("barcode", inputs.barcode.value.trim());
        body.append("packSize", inputs.packSize.value.trim());
        body.append("packaging", inputs.packaging.value.trim());
        body.append("effectiveDate", inputs.effectiveDate.value);
    }

    const image = imageInput.files[0];

    if (image) {
        body.append("productImage", image);
    }

    saveButton.disabled = true;
    saveButton.textContent = editing ? "Saving changes..." : "Creating product...";

    try {
        const endpoint = editing ? "products/update.php" : "products/create.php";

        const response = await fetch(apiUrl(endpoint), {
            method: "POST",
            credentials: "include",
            cache: "no-store",
            body
        });

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error("The server returned an unexpected response.");
        }

        if (!response.ok || data.success !== true) {
            throw new Error(data.error || "Unable to save the product.");
        }

        const savedId = data.product_id;

        await loadProducts();

        if (savedId !== undefined) {
            productSelect.value = String(savedId);
            selectProduct(String(savedId));
        } else {
            setInitialFormState();
        }

        showProductMessage(
            data.message || (editing ? "Product updated successfully." : "Product created successfully."),
            "success"
        );
    } catch (error) {
        showProductMessage(error.message || "Unable to contact the server.");
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = selectedProduct ? "Save changes" : "Create product";
    }
}

imageInput.addEventListener("change", () => {
    const image = imageInput.files[0];

    if (!image) {
        if (existingImagePath && selectedProduct) {
            showPreview(pageUrl(existingImagePath), "Current product image");
        } else {
            clearPreview();
        }
        return;
    }

    if (!["image/jpeg", "image/png", "image/webp"].includes(image.type)) {
        showProductMessage("Choose a JPG, PNG, or WebP image.");
        imageInput.value = "";
        return;
    }

    if (image.size > 5 * 1024 * 1024) {
        showProductMessage("The image must not exceed 5 MB.");
        imageInput.value = "";
        return;
    }

    showProductMessage("");
    showPreview(URL.createObjectURL(image), "Selected image preview");
});

previewImage.addEventListener("error", () => {
    previewImage.removeAttribute("src");
    previewLabel.textContent = "Image preview unavailable";
});

inputs.availability.addEventListener("change", updateBarcodeRequirement);

productSelect.addEventListener("change", () => {
    selectProduct(productSelect.value);
});

cancelButton.addEventListener("click", () => {
    productSelect.value = "";
    setInitialFormState();
});

form.addEventListener("submit", submitProduct);

async function initialiseProductManagement() {
    try {
        account = await shellReady;

        if (!account) {
            return;
        }

        const organizationType = account.organization.organization_type;

        if (!["MANUFACTURER", "MANUFACTURER_SUPPLIER"].includes(organizationType)) {
            form.hidden = true;
            productSelect.disabled = true;
            showProductMessage("Only manufacturers can manage products.");
            return;
        }

        await loadProducts();
    } catch (error) {
        showProductMessage(error.message || "Unable to initialise product management.");
        saveButton.disabled = true;
    }
}

initialiseProductManagement();
