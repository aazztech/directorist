import { convertToSelect2 } from './../lib/helper';

import './components/utility';
import './components/modal';

const $ = jQuery;

window.addEventListener('load', initSelect2 );
document.body.addEventListener( 'directorist-search-form-nav-tab-reloaded', initSelect2 );
document.body.addEventListener( 'directorist-reload-select2-fields', initSelect2 );

// Select 2
function initSelect2() {
    const select_fields = [
        { elm: $('.directorist-select').find( 'select' ) },
        { elm: $('#directorist-select-js') },
        { elm: $('#directorist-search-category-js') },
        { elm: $('#directorist-search-select-js') },
        { elm: $('#directorist-select-st-s-js') },
        { elm: $('#directorist-select-sn-s-js') },
        { elm: $('#directorist-select-mn-e-js') },
        { elm: $('#directorist-select-tu-e-js') },
        { elm: $('#directorist-select-wd-s-js') },
        { elm: $('#directorist-select-wd-e-js') },
        { elm: $('#directorist-select-th-e-js') },
        { elm: $('#directorist-select-fr-s-js') },
        { elm: $('#directorist-select-fr-e-js') },
        { elm: $('#directorist-location-select') },
        { elm: $('#directorist-category-select') },
        { elm: $('.select-basic') },
        { elm: $('#loc-type') },
        { elm: $('.bdas-location-search') },
        { elm: $('.directorist-location-select') },
        { elm: $('#at_biz_dir-category') },
        { elm: $('#cat-type') },
        { elm: $('.bdas-category-search') },
        { elm: $('.directorist-category-select') },
    ];

    select_fields.forEach( field => {
        convertToSelect2( field );
    });
}