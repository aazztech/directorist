/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 3);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/public/search-form-listing.js":
/*!*****************************************************!*\
  !*** ./assets/src/js/public/search-form-listing.js ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function ($) {
  $('body').on('click', '.search_listing_types', function (event) {
    event.preventDefault();
    var listing_type = $(this).attr('data-listing_type');
    var type_current = $('.directorist-listing-type-selection__link--current');

    if (type_current.length) {
      type_current.removeClass('directorist-listing-type-selection__link--current');
    }

    $('#listing_type').val(listing_type);
    $(this).addClass('directorist-listing-type-selection__link--current');
    var form_data = new FormData();
    form_data.append('action', 'atbdp_listing_types_form');
    form_data.append('listing_type', listing_type);
    $('.directorist-search-form-box').addClass('atbdp-form-fade');
    $.ajax({
      method: 'POST',
      processData: false,
      contentType: false,
      url: atbdp_search.ajax_url,
      data: form_data,
      success: function success(response) {
        if (response) {
          var _atbdp_search_listing = response['atbdp_search_listing'] ? response['atbdp_search_listing'] : _atbdp_search_listing;

          $('.directorist-search-form-box').empty().html(response['search_form']);
          $('.directorist-listing-category-top').empty().html(response['popular_categories']);
          var events = [new CustomEvent('directorist-search-form-nav-tab-reloaded'), new CustomEvent('directorist-reload-select2-fields'), new CustomEvent('directorist-reload-map-api-field')];
          events.forEach(function (event) {
            document.body.dispatchEvent(event);
          });
        }

        $('.directorist-search-form-box').removeClass('atbdp-form-fade');
        atbd_callingSlider();
      },
      error: function error(_error) {
        console.log(_error);
      }
    });
  }); // Advance search

  // category custom filed search form
  $('body').on('change', '.directorist-category-select', function(event) {
    var listing_type = $('#listing_type').val();
    var cat_id       = $(this).val();
    var form_data = new FormData();
    var custom_field = $('.directorist-category-select option[value='+cat_id+']').attr("data-custom-field");
    if( ! custom_field ){
      return;
    }
    $('.directorist-search-form-box').addClass('atbdp-form-fade');
    form_data.append('action', 'directorist_category_custom_field_seach');
    form_data.append('listing_type', listing_type);
    form_data.append('cat_id', cat_id);
    $.ajax({
      method: 'POST',
      processData: false,
      contentType: false,
      url: atbdp_search.ajax_url,
      data: form_data,
      success: function success(response) {
        if (response) {
          $('.directorist-search-form-box').empty().html(response['search_form']);
        }
         $('.directorist-category-select option[value='+cat_id+']').attr("selected",true);
         $('.directorist-category-select option').attr("data-custom-field", 1);
        var events = [new CustomEvent('directorist-search-form-nav-tab-reloaded'), new CustomEvent('directorist-reload-select2-fields'), new CustomEvent('directorist-reload-map-api-field')];
          events.forEach(function (event) {
            document.body.dispatchEvent(event);
          });
          $('.directorist-search-form-box').removeClass('atbdp-form-fade');
      },
      error: function error(_error) {
        console.log(_error);
      }
    });
  })
  // Populate atbdp child terms dropdown

  $('.bdas-terms').on('change', 'select', function (e) {
    e.preventDefault();
    var $this = $(this);
    var taxonomy = $this.data('taxonomy');
    var parent = $this.data('parent');
    var value = $this.val();
    var classes = $this.attr('class');
    $this.closest('.bdas-terms').find('input.bdas-term-hidden').val(value);
    $this.parent().find('div:first').remove();

    if (parent != value) {
      $this.parent().append('<div class="bdas-spinner"></div>');
      var data = {
        action: 'bdas_public_dropdown_terms',
        taxonomy: taxonomy,
        parent: value,
        class: classes,
        security: atbdp_search.ajaxnonce
      };
      $.post(atbdp_search.ajax_url, data, function (response) {
        $this.parent().find('div:first').remove();
        $this.parent().append(response);
      });
    }
  }); // load custom fields of the selected category in the search form

  $('body').on('change', '.bdas-category-search, .directorist-category-select', function () {
    var $search_elem = $(this).closest('form').find('.atbdp-custom-fields-search');

    if ($search_elem.length) {
      $search_elem.html('<div class="atbdp-spinner"></div>');
      var data = {
        action: 'atbdp_custom_fields_search',
        term_id: $(this).val(),
        security: atbdp_search.ajaxnonce
      };
      $.post(atbdp_search.ajax_url, data, function (response) {
        $search_elem.html(response);
        var item = $('.custom-control').closest('.bads-custom-checks');
        item.each(function (index, el) {
          var count = 0;
          var abc = $(el)[0];
          var abc2 = $(abc).children('.custom-control');

          if (abc2.length <= 4) {
            $(abc2).closest('.bads-custom-checks').next('a.more-or-less').hide();
          }

          $(abc2).slice(4, abc2.length).hide();
        });
      });
    }
  });
  $('.address_result').hide();
  window.addEventListener('load', init_map_api_field);
  document.body.addEventListener('directorist-reload-map-api-field', init_map_api_field);

  function init_map_api_field() {
    if (atbdp_search_listing.i18n_text.select_listing_map === 'google') {
      function initialize() {
        var opt = {
          types: ['geocode'],
          componentRestrictions: {
            country: atbdp_search_listing.restricted_countries
          }
        };
        var options = atbdp_search_listing.countryRestriction ? opt : '';
        var input_fields = [{
          input_id: 'address',
          lat_id: 'cityLat',
          lng_id: 'cityLng',
          options: options
        }, {
          input_id: 'address_widget',
          lat_id: 'cityLat',
          lng_id: 'cityLng',
          options: options
        }];

        var setupAutocomplete = function setupAutocomplete(field) {
          var input = document.getElementById(field.input_id);
          var autocomplete = new google.maps.places.Autocomplete(input, field.options);
          google.maps.event.addListener(autocomplete, 'place_changed', function () {
            var place = autocomplete.getPlace();
            console.log({
              place: place
            });
            document.getElementById(field.lat_id).value = place.geometry.location.lat();
            document.getElementById(field.lng_id).value = place.geometry.location.lng();
          });
        };

        input_fields.forEach(function (field) {
          setupAutocomplete(field);
        });
      }

      initialize();
    } else if (atbdp_search_listing.i18n_text.select_listing_map === 'openstreet') {
      var getResultContainer = function getResultContainer(context, field) {
        return $(context).next(field.search_result_elm);
      };

      var getWidgetResultContainer = function getWidgetResultContainer(context, field) {
        return $(context).parent().next(field.search_result_elm);
      };

      var input_fields = [{
        input_elm: '#address',
        search_result_elm: '.address_result',
        getResultContainer: getResultContainer
      }, {
        input_elm: '#q_addressss',
        search_result_elm: '.address_result',
        getResultContainer: getResultContainer
      }, {
        input_elm: '.atbdp-search-address',
        search_result_elm: '.address_result',
        getResultContainer: getResultContainer
      }, {
        input_elm: '#address_widget',
        search_result_elm: '#address_widget_result',
        getResultContainer: getWidgetResultContainer
      }];
      input_fields.forEach(function (field) {
        if (!$(field.input_elm).length) {
          return;
        }

        $(field.input_elm).on('keyup', function (event) {
          event.preventDefault();
          var search = $(this).val();
          var result_container = field.getResultContainer(this, field);
          result_container.css({
            display: 'block'
          });

          if (search === '') {
            result_container.css({
              display: 'none'
            });
          }

          var res = '';
          $.ajax({
            url: "https://nominatim.openstreetmap.org/?q=%27+".concat(search, "+%27&format=json"),
            type: 'POST',
            data: {},
            success: function success(data) {
              for (var i = 0; i < data.length; i++) {
                res += "<li><a href=\"#\" data-lat=".concat(data[i].lat, " data-lon=").concat(data[i].lon, ">").concat(data[i].display_name, "</a></li>");
              }

              result_container.html("<ul>".concat(res, "</ul>"));

              if (res.length) {
                result_container.show();
              } else {
                result_container.hide();
              }
            },
            error: function error(_error2) {
              console.log({
                error: _error2
              });
            }
          });
        });
      }); // hide address result when click outside the input field

      $(document).on('click', function (e) {
        if (!$(e.target).closest('#address, #q_addressss, .atbdp-search-address').length) {
          $('.address_result').hide();
        }
      });

      var syncLatLngData = function syncLatLngData(context, event, args) {
        event.preventDefault();
        var text = $(context).text();
        var lat = $(context).data('lat');
        var lon = $(context).data('lon');
        $('#cityLat').val(lat);
        $('#cityLng').val(lon);
        var inp = $(context).closest(args.result_list_container).parent().find('#address, #address_widget, #q_addressss, .atbdp-search-address');
        inp.val(text);
        $(args.result_list_container).hide();
      };

      $('body').on('click', '.address_result ul li a', function (event) {
        syncLatLngData(this, event, {
          result_list_container: '.address_result'
        });
      });
      $('body').on('click', '#address_widget_result ul li a', function (event) {
        syncLatLngData(this, event, {
          result_list_container: '#address_widget_result'
        });
      });
    }

    if ($('#address, #q_addressss,.atbdp-search-address').val() === '') {
      $(this).parent().next('.address_result').css({
        display: 'none'
      });
    }
  }
})(jQuery);

/***/ }),

/***/ 3:
/*!***********************************************************!*\
  !*** multi ./assets/src/js/public/search-form-listing.js ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! ./assets/src/js/public/search-form-listing.js */"./assets/src/js/public/search-form-listing.js");


/***/ })

/******/ });
//# sourceMappingURL=public-search-form-listing.js.map