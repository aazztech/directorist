var atbdp_lat_lon = get_dom_data( 'atbdp_lat_lon' );
var listings_data = get_dom_data( 'listings_data' );
var atbdp_map     = get_dom_data( 'atbdp_map' );

function get_dom_data ( key ) {
    var dom_content = document.body.innerHTML;

    if ( ! dom_content.length ) { return ''; }

    var pattern = new RegExp("<!-- directorist-dom-data::" + key + "(.*?)-->");
    var terget_content = pattern.exec( dom_content );

    if ( ! terget_content ) { return ''; }
    if ( typeof terget_content[1] === 'undefined' ) { return ''; }

    var dom_data = JSON.parse( terget_content[1] );

    if ( ! dom_data ) { return ''; }

    return dom_data;
}


(function($) {
        jQuery(document).ready(function($) {
                const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 18,
                        attribution:
                                '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ',
                });
                const latlng = L.latLng(atbdp_lat_lon.lat, atbdp_lat_lon.lon);
                const fullCount = listings_data.length;
                const quarterCount = Math.round(fullCount / 4);

                const map = L.map('map', {
                        center: latlng,
                        zoom: atbdp_map.zoom,
                        scrollWheelZoom: false,
                        layers: [tiles],
                });
                // map.once('focus', function() { map.scrollWheelZoom.enable(); });

                const mcg = L.markerClusterGroup();
                const group1 = L.featureGroup.subGroup(mcg);
                const // use `L.featureGroup.subGroup(parentGroup)` instead of `L.featureGroup()` or `L.layerGroup()`!
                        group2 = L.featureGroup.subGroup(mcg);
                const group3 = L.featureGroup.subGroup(mcg);
                const group4 = L.featureGroup.subGroup(mcg);
                const control = L.control.layers(null, null, { collapsed: false });
                let i;
                let a;
                let title;
                let marker;
                mcg.addTo(map);
                const test = $('.openstreet_icon');

                for (i = 0; i < listings_data.length; i++) {
                        const listing = listings_data[i];
                        const fontAwesomeIcon = L.divIcon({
                                html: `<div class="atbd_map_shape"><span class="${listing.cat_icon}"></span></div>`,
                                iconSize: [20, 20],
                                className: 'myDivIcon',
                        });

                        title = listing.info_content;
                        marker = L.marker([listing.manual_lat, listing.manual_lng], { icon: fontAwesomeIcon });
                        marker.bindPopup(title);

                        marker.addTo(
                                i < quarterCount
                                        ? group1
                                        : i < quarterCount * 2
                                        ? group2
                                        : i < quarterCount * 3
                                        ? group3
                                        : group4
                        );
                }

                /* control.addOverlay(group1, 'First quarter');
        control.addOverlay(group2, 'Second quarter');
        control.addOverlay(group3, 'Third quarter');
        control.addOverlay(group4, 'Fourth quarter'); */
                control.addTo(map);

                group1.addTo(map); // Adding to map now adds all child layers into the parent group.
                group2.addTo(map);
                group3.addTo(map);
                group4.addTo(map);
        });
})(jQuery);
