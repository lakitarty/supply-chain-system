const TYPE_LABELS = {
    RETAILER: "Retailer",
    SUPPLIER: "Supplier",
    MANUFACTURER: "Manufacturer",
    MANUFACTURER_SUPPLIER: "Manufacturer and supplier"
};

const BUILT_PATHS = [
    "modules/settings/settings/settings.html",
    "dashboard/dashboard.html",
    "modules/products/catalogue/catalogue.html",
    "modules/products/updates/updates.html",
    "modules/products/history/history.html",
    "modules/products/management/management.html",
    "modules/products/tax/tax.html",
    "modules/messages/announcements/announcements.html",
    "modules/messages/notifications/notifications.html",
    "modules/messages/branch-communications/branch-communications.html",
    "modules/admin/users/users.html",
    "modules/admin/branches/branches.html",
    "modules/admin/organisation-profile/organisation-profile.html",
    "modules/admin/activity-log/activity-log.html",
    "modules/network/relationships/relationships.html",
    "modules/network/workspace/workspace.html",
    "modules/network/coverage/coverage.html",
    "modules/network/distribution/distribution.html",
    "modules/stock/stock/stock.html",
    "modules/stock/alerts/alerts.html",
    "modules/stock/sales/sales.html",
    "modules/network/offers/offers.html",
    "modules/stock/prices/prices.html",
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
            {
                label: "Product management",
                path: page("products", "management"),
                types: ["MANUFACTURER", "MANUFACTURER_SUPPLIER"]
            },
            { label: "Tax classification", path: page("products", "tax"), types: ["MANUFACTURER", "MANUFACTURER_SUPPLIER"], roles: ["ADMIN"] },
            { label: "History", path: page("products", "history") }
        ]
    },
    {
        id: "network",
        label: "Supply network",
        roles: ["ADMIN", "BRANCH_ADMIN"],
        sections: [
            { label: "Relationships", path: page("network", "relationships") },
            { label: "Workspace", path: page("network", "workspace") },
            { label: "Supplier coverage", path: page("network", "coverage"), types: ["SUPPLIER", "MANUFACTURER_SUPPLIER"], roles: ["ADMIN"] },
            { label: "Supplier offers", path: page("network", "offers"), types: ["SUPPLIER", "MANUFACTURER_SUPPLIER"], roles: ["ADMIN"] },
            { label: "Distribution", path: page("network", "distribution"), types: ["MANUFACTURER", "MANUFACTURER_SUPPLIER"], roles: ["ADMIN"] },
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
            { label: "Alerts", path: page("stock", "alerts") },
            { label: "Selling prices", path: page("stock", "prices"), roles: ["ADMIN", "BRANCH_ADMIN"] },
            { label: "Sales & Reports", path: page("stock", "sales") }
        ]
    },
    {
        id: "messages",
        label: "Messages",
        sections: [
            { label: "Announcements", path: page("messages", "announcements") },
            { label: "Notifications", path: page("messages", "notifications") },
            { label: "Branch communications", path: page("messages", "branch-communications") },
            { label: "Conversations", path: page("messages", "conversations") }
        ]
    },
    {
        id: "team",
        label: "Team",
        roles: ["BRANCH_ADMIN"],
        sections: [
            { label: "Staff", path: page("admin", "users") }
        ]
    },    {
        id: "settings",
        label: "Settings",
        path: "modules/settings/settings/settings.html"
    },
    {
        id: "admin",
        label: "Admin",
        adminOnly: true,
        sections: [
            { label: "Users", path: page("admin", "users") },
            { label: "Branches", path: page("admin", "branches") },
            { label: "Organisation profile", path: page("admin", "organisation-profile") },
            { label: "Activity log", path: page("admin", "activity-log") }
        ]
    },
    {
        id: "profile",
        label: "Profile",
        path: page("admin", "organisation-profile"),
        roles: ["BRANCH_ADMIN", "STAFF"]
    }
];

function isVisible(item, account) {
    const typeAllowed = !item.types || item.types.includes(account.organization.organization_type);
    const roleAllowed =
        (!item.adminOnly || account.user.role === "ADMIN") &&
        (!item.roles || item.roles.includes(account.user.role));

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


