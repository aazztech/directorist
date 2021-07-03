module.exports = {
    commonEntries: {
        // Public
        // -------------------------------------------
        // JS
        ['public-main']: ["./assets/src/js/public/main.js"],
        ['public-checkout']: ["./assets/src/js/public/checkout.js"],
        ['public-search-listing']: ["./assets/src/js/public/search-listing.js"],
        ['public-search-form-listing']: ["./assets/src/js/public/search-form-listing.js"],

        ['public-atmodal']: ["./assets/src/js/public/atmodal.js"],
        ['public-releated-listings-slider']: ["./assets/src/js/public/releated-listings-slider.js"],

        // Admin
        // -------------------------------------------
        ['admin-main']: "./assets/src/js/admin/admin.js",
        ['admin-custom-field']: "./assets/src/js/admin/custom-field.js",
        ['admin-extension-update']: "./assets/src/js/admin/extension-update.js",
        ['admin-import-export']: "./assets/src/js/admin/import-export.js",
        ['admin-plugins']: "./assets/src/js/admin/plugins.js",
        ['admin-setup-wizard']: "./assets/src/js/admin/setup-wizard.js",
        ['admin-multi-directory-archive']: "./assets/src/js/admin/multi-directory-archive.js",

        // Global
        // -------------------------------------------
        // JS
        ['global-main']: ["./assets/src/js/global/global.js"],
        ['global-add-listing']: ["./assets/src/js/global/add-listing.js"],

        ['global-add-listing-openstreet-map-custom-script']: ["./assets/src/js/global/map-scripts/add-listing/openstreet-map.js"],
        ['global-add-listing-gmap-custom-script']: ["./assets/src/js/global/map-scripts/add-listing/google-map.js"],
        ['global-geolocation']: ["./assets/src/js/global/map-scripts/geolocation.js"],
        ['public-single-listing-openstreet-map-custom-script']: ["./assets/src/js/global/map-scripts/single-listing/openstreet-map.js"],
        ['public-single-listing-openstreet-map-widget-custom-script']: ["./assets/src/js/global/map-scripts/single-listing/openstreet-map-widget.js"],
        ['public-single-listing-gmap-custom-script']: ["./assets/src/js/global/map-scripts/single-listing/google-map.js"],
        ['public-single-listing-gmap-widget-custom-script']: ["./assets/src/js/global/map-scripts/single-listing/google-map-widget.js"],
        ['public-geolocation-widget']: ["./assets/src/js/global/map-scripts/geolocation-widget.js"],
        ['global-load-osm-map']: ["./assets/src/js/global/map-scripts/load-osm-map.js"],
        ['global-map-view']: ["./assets/src/js/global/map-scripts/map-view.js"],
        ['global-markerclusterer']: ["./assets/src/js/global/map-scripts/markerclusterer.js"],

        ['global-directorist-plupload']: "./assets/src/js/global/directorist-plupload.js",
        ['global-pure-select']: ["./assets/src/js/global/pureScriptSearchSelect.js"],
    },

    vueEntries: {
        ['admin-multi-directory-builder']: "./assets/src/js/admin/multi-directory-builder.js",
        ['admin-settings-manager']: "./assets/src/js/admin/settings-manager.js",
    },
}