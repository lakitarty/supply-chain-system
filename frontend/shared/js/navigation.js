const TYPE_LABELS = {
    RETAILER: "Retailer",
    SUPPLIER: "Supplier",
    MANUFACTURER: "Manufacturer",
    MANUFACTURER_SUPPLIER: "Manufacturer and supplier"
};

const BUILT_PATHS = [
    "dashboard/dashboard.html",
    "modules/admin/users/users.html",
    "modules/admin/branches/branches.html",
    "modules/admin/organisation-profile/organisation-profile.html",
    "modules/admin/activity-log/activity-log.html"
];

function page(module, name) {
    return `modules/${module}/${name}/${name}.html`;
}

const TABS = [
    {
        id: "dashboard",
        label: "Dashboard",
        path: "dashboard/dashboard.html"
    },
    {
        id: "products",
        label: "Products",
        sections: [
            { label: "Catalogue", path: page("products", "catalogue") },
            { label: "Updates", path: page("products", "updates") },
            { label: "History", path: page("products", "history") }
        ]
    },
    {
        id: "network",
        label: "Supply Network",
        sections: [
            { label: "Relationships", path: page("network", "relationships") },
            { label: "Changes", path: page("network", "changes") }
        ]
    },
    {
        id: "promotions",
        label: "Promotions",
        sections: [
            { label: "Campaigns", path: page("promotions", "campaigns") },
            { label: "Claims", path: page("promotions", "claims") }
        ]
    },
    {
        id: "stock",
        label: "Stock",
        types: ["RETAILER"],
        sections: [
            { label: "Stock", path: page("stock", "stock") },
            { label: "Alerts", path: page("stock", "alerts") }
        ]
    },
    {
        id: "messages",
        label: "Messages",
        sections: [
            { label: "Announcements", path: page("messages", "announcements") },
            { label: "Notifications", path: page("messages", "notifications") },
            { label: "Conversations", path: page("messages", "conversations") }
        ]
    },
    {
        id: "admin",
        label: "Admin",
        adminOnly: true,
        sections: [
            { label: "Users", path: page("admin", "users") },
            { label: "Branches", path: page("admin", "branches") },
            { label: "Organisation Profile", path: page("admin", "organisation-profile") },
            { label: "Activity Log", path: page("admin", "activity-log") }
        ]
    }
];

function isVisible(item, account) {
    const typeAllowed = !item.types || item.types.includes(account.organization.organization_type);
    const roleAllowed = !item.adminOnly || account.user.role === "ADMIN";

    return typeAllowed && roleAllowed;
}

function tabsFor(account) {
    return TABS
        .filter(tab => isVisible(tab, account))
        .map(tab => ({
            ...tab,
            sections: (tab.sections ?? []).filter(section => isVisible(section, account))
        }));
}

function isBuilt(path) {
    return BUILT_PATHS.includes(path);
}

function tabTarget(tab) {
    const candidates = tab.path ? [tab.path] : tab.sections.map(section => section.path);

    return candidates.find(isBuilt) ?? null;
}