const productSelect = field("productSelect");
const versionSelect = field("versionSelect");
const productDetail = field("productDetail");
const catalogueMessage = field("catalogueMessage");
const catalogueImageContainer = field("catalogueImageContainer");
const catalogueProductImage = field("catalogueProductImage");

let products = [];

const isCurrentVersion = version => Number(version.is_current) === 1;

function versionLabel(version) {
    return `Version ${version.version_number}${isCurrentVersion(version) ? " (current)" : ""}`;
}

function selectedProduct() {
    return products.find(product => String(product.product_id) === productSelect.value);
}

function selectedVersion() {
    return selectedProduct()?.versions.find(version => String(version.version_number) === versionSelect.value);
}

function renderDetail() {
    const product = selectedProduct();
    const version = selectedVersion();

    if (product?.image_path) {
        catalogueProductImage.src = pageUrl(product.image_path);
        catalogueProductImage.alt = `${product.product_name} product image`;
        catalogueImageContainer.hidden = false;
    } else {
        catalogueProductImage.removeAttribute("src");
        catalogueImageContainer.hidden = true;
    }

    renderDetailList(productDetail, product && version
        ? [
            ["Product", product.product_name],
            ["Manufacturer", product.manufacturer_name],
            ["Category", product.category || "None"],
            ["Version", versionLabel(version)],
            ["Barcode", version.barcode],
            ["Pack size", version.pack_size || "None"],
            ["Packaging", version.packaging_description || "None"],
            ["Effective from", version.effective_from],
            ["Effective to", version.effective_to || "Open-ended"]
        ]
        : []);
}

function renderVersions() {
    const versions = selectedProduct()?.versions ?? [];
    const current = versions.find(isCurrentVersion) ?? versions[0];

    versionSelect.replaceChildren(
        ...versions.map(version => option(String(version.version_number), `${versionLabel(version)}  |  ${version.barcode}`))
    );
    versionSelect.disabled = versions.length === 0;

    if (current) {
        versionSelect.value = String(current.version_number);
    }
}

function renderProducts() {
    productSelect.replaceChildren(
        ...(products.length === 0
            ? [option("", "No products reach your organisation yet")]
            : products.map(product => option(String(product.product_id), `${product.product_name}  |  ${product.manufacturer_name}`)))
    );
    productSelect.disabled = products.length === 0;

    renderVersions();
    renderDetail();
}

async function loadCatalogue() {
    const { ok, data } = await getJson("products/list.php");

    if (!ok) {
        showMessage(catalogueMessage, data.error || "Unable to load the catalogue.", "error");
        return;
    }

    products = data.products;
    renderProducts();
}

productSelect.addEventListener("change", () => {
    renderVersions();
    renderDetail();
});

versionSelect.addEventListener("change", renderDetail);

shellReady.then(account => {
    if (account) {
        loadCatalogue();
    }
});