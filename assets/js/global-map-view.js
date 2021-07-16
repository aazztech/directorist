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
/******/ 	return __webpack_require__(__webpack_require__.s = 17);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/global/map-scripts/map-view.js":
/*!******************************************************!*\
  !*** ./assets/src/js/global/map-scripts/map-view.js ***!
  \******************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _lib_helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./../../lib/helper */ "./assets/src/js/lib/helper.js");

var atbdp_map = Object(_lib_helper__WEBPACK_IMPORTED_MODULE_0__["get_dom_data"])('atbdp_map'); // Define Marker Shapes

var MAP_PIN = 'M0-48c-9.8 0-17.7 7.8-17.7 17.4 0 15.5 17.7 30.6 17.7 30.6s17.7-15.4 17.7-30.6c0-9.6-7.9-17.4-17.7-17.4z';

var inherits = function inherits(childCtor, parentCtor) {
  /** @constructor */
  function tempCtor() {}

  tempCtor.prototype = parentCtor.prototype;
  childCtor.superClass_ = parentCtor.prototype;
  childCtor.prototype = new tempCtor();
  childCtor.prototype.constructor = childCtor;
};

function Marker(options) {
  google.maps.Marker.apply(this, arguments);

  if (options.map_icon_label) {
    this.MarkerLabel = new MarkerLabel({
      map: this.map,
      marker: this,
      text: options.map_icon_label
    });
    this.MarkerLabel.bindTo('position', this, 'position');
  }
} // Apply the inheritance


inherits(Marker, google.maps.Marker); // Custom Marker SetMap

Marker.prototype.setMap = function () {
  google.maps.Marker.prototype.setMap.apply(this, arguments);
  this.MarkerLabel && this.MarkerLabel.setMap.apply(this.MarkerLabel, arguments);
}; // Marker Label Overlay


var MarkerLabel = function MarkerLabel(options) {
  var self = this;
  this.setValues(options); // Create the label container

  this.div = document.createElement('div');
  this.div.className = 'map-icon-label'; // Trigger the marker click handler if clicking on the label

  google.maps.event.addDomListener(this.div, 'click', function (e) {
    e.stopPropagation && e.stopPropagation();
    google.maps.event.trigger(self.marker, 'click');
  });
}; // Create MarkerLabel Object


MarkerLabel.prototype = new google.maps.OverlayView(); // Marker Label onAdd

MarkerLabel.prototype.onAdd = function () {
  var pane = this.getPanes().overlayImage.appendChild(this.div);
  var self = this;
  this.listeners = [google.maps.event.addListener(this, 'position_changed', function () {
    self.draw();
  }), google.maps.event.addListener(this, 'text_changed', function () {
    self.draw();
  }), google.maps.event.addListener(this, 'zindex_changed', function () {
    self.draw();
  })];
}; // Marker Label onRemove


MarkerLabel.prototype.onRemove = function () {
  this.div.parentNode.removeChild(this.div);

  for (var i = 0, I = this.listeners.length; i < I; ++i) {
    google.maps.event.removeListener(this.listeners[i]);
  }
}; // Implement draw


MarkerLabel.prototype.draw = function () {
  var projection = this.getProjection();
  var position = projection.fromLatLngToDivPixel(this.get('position'));
  var div = this.div;
  this.div.innerHTML = this.get('text').toString();
  div.style.zIndex = this.get('zIndex'); // Allow label to overlay marker

  div.style.position = 'absolute';
  div.style.display = 'block';
  div.style.left = "".concat(position.x - div.offsetWidth / 2, "px");
  div.style.top = "".concat(position.y - div.offsetHeight, "px");
};

(function ($) {
  // map view

  /**
   *  Render a Google Map onto the selected jQuery element.
   *
   *  @since    5.0.0
   */
  var at_icon = [];

  function atbdp_rander_map($el) {
    $el.addClass('atbdp-map-loaded'); // var

    var $markers = $el.find('.marker'); // vars

    var args = {
      zoom: parseInt(atbdp_map.zoom),
      center: new google.maps.LatLng(0, 0),
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      zoomControl: true,
      scrollwheel: false,
      gestureHandling: 'cooperative',
      averageCenter: true,
      scrollWheelZoom: 'center'
    }; // create map

    var map = new google.maps.Map($el[0], args); // add a markers reference

    map.markers = []; // set map type

    map.type = $el.data('type');
    var infowindow = new google.maps.InfoWindow({
      content: ''
    }); // add markers

    $markers.each(function () {
      atbdp_add_marker($(this), map, infowindow);
    }); // center map

    atbdp_center_map(map); // update map when contact details fields are updated in the custom post type 'acadp_listings'

    /* const mcOptions = {
        imagePath: atbdp_map.plugin_url+'assets/images/m',
        //cssClass: 'marker-cluster-shape',
        styles:[{
            url: atbdp_map.plugin_url+'assets/images/m1.png',
            width: 53,
            height:53,
            textColor:"#ffffff",
        }]
    }; */

    var mcOptions = new MarkerClusterer(map, [], {
      imagePath: atbdp_map.plugin_url + 'assets/images/m'
    });
    mcOptions.setStyles(mcOptions.getStyles().map(function (style) {
      style.textColor = '#fff';
      return style;
    }));

    if (map.type === 'markerclusterer') {
      //const markerCluster = new MarkerClusterer(map, map.markers, mcOptions);
      mcOptions.addMarkers(map.markers);
    }
  }
  /**
   *  Add a marker to the selected Google Map.
   *
   *  @since    1.0.0
   */


  function atbdp_add_marker($marker, map, infowindow) {
    // var
    var latlng = new google.maps.LatLng($marker.data('latitude'), $marker.data('longitude')); // check to see if any of the existing markers match the latlng of the new marker

    if (map.markers.length) {
      for (var i = 0; i < map.markers.length; i++) {
        var existing_marker = map.markers[i];
        var pos = existing_marker.getPosition(); // if a marker already exists in the same position as this marker

        if (latlng.equals(pos)) {
          // update the position of the coincident marker by applying a small multipler to its coordinates
          var latitude = latlng.lat() + (Math.random() - 0.5) / 1500; // * (Math.random() * (max - min) + min);

          var longitude = latlng.lng() + (Math.random() - 0.5) / 1500; // * (Math.random() * (max - min) + min);

          latlng = new google.maps.LatLng(latitude, longitude);
        }
      }
    }

    var icon = $marker.data('icon');
    var marker = new Marker({
      position: latlng,
      map: map,
      icon: {
        path: MAP_PIN,
        fillColor: 'transparent',
        fillOpacity: 1,
        strokeColor: '',
        strokeWeight: 0
      },
      map_icon_label: icon !== undefined && "<div class=\"atbd_map_shape\"><i class=\"".concat(icon, "\"></i></div>")
    }); // add to array

    map.markers.push(marker); // if marker contains HTML, add it to an infoWindow

    if ($marker.html()) {
      // map info window close button
      google.maps.event.addListener(infowindow, 'domready', function () {
        var closeBtn = $('.iw-close-btn').get();
        google.maps.event.addDomListener(closeBtn[0], 'click', function () {
          infowindow.close();
        });
      }); // show info window when marker is clicked

      google.maps.event.addListener(marker, 'click', function () {
        if (atbdp_map.disable_info_window === 'no') {
          infowindow.setContent($marker.html());
          infowindow.open(map, marker);
        }
      });
    }
  }
  /**
   *  Center the map, showing all markers attached to this map.
   *
   *  @since    1.0.0
   */


  function atbdp_center_map(map) {
    // vars
    var bounds = new google.maps.LatLngBounds(); // loop through all markers and create bounds

    $.each(map.markers, function (i, marker) {
      var latlng = new google.maps.LatLng(marker.position.lat(), marker.position.lng());
      bounds.extend(latlng);
    }); // only 1 marker?

    /* if (map.markers.length !== 1) {
        // set center of map
        map.setCenter(bounds.getCenter());
        map.setZoom(parseInt(atbdp_map.zoom));
    } else {
        // fit to bounds
        map.fitBounds(bounds);
    } */

    if (map.markers.length > 0) {
      // set center of map
      map.setCenter(bounds.getCenter());
      map.setZoom(parseInt(atbdp_map.zoom));
    }
  }

  function setup_info_window() {
    var abc = document.querySelectorAll('div');
    abc.forEach(function (el, index) {
      if (el.innerText === 'atgm_marker') {
        // console.log(at_icon)
        el.innerText = ' ';
        el.innerHTML = "<i class=\"la ".concat(at_icon, " atbd_map_marker_icon\"></i>");
      } // ${$marker.data('icon')}

    });
    document.querySelectorAll('div').forEach(function (el1, index) {
      if (el1.style.backgroundImage.split('/').pop() === 'm1.png")') {
        el1.addEventListener('click', function () {
          setInterval(function () {
            var abc = document.querySelectorAll('div');
            abc.forEach(function (el, index) {
              if (el.innerText === 'atgm_marker') {
                el.innerText = ' ';
                el.innerHTML = "<i class=\"la ".concat(at_icon, " atbd_map_marker_icon\"></i>");
              }
            });
          }, 100);
        });
      }
    });
  }

  function setup_map() {
    // render map in the custom post
    $('.atbdp-map').each(function () {
      atbdp_rander_map($(this));
    });
  }

  window.addEventListener('load', setup_map);
  window.addEventListener('load', setup_info_window);
  window.addEventListener('directorist-reload-listings-map-archive', setup_map);
  window.addEventListener('directorist-reload-listings-map-archive', setup_info_window);
  $(document).ready(function () {
    $('body').find('.map-info-wrapper').addClass('map-info-wrapper--show');
  });
})(jQuery);

/***/ }),

/***/ "./assets/src/js/lib/helper.js":
/*!*************************************!*\
  !*** ./assets/src/js/lib/helper.js ***!
  \*************************************/
/*! exports provided: get_dom_data, convertToSelect2 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "get_dom_data", function() { return get_dom_data; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "convertToSelect2", function() { return convertToSelect2; });
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/typeof */ "./node_modules/@babel/runtime/helpers/typeof.js");
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__);

var $ = jQuery;

function get_dom_data(key) {
  var dom_content = document.body.innerHTML;

  if (!dom_content.length) {
    return '';
  }

  var pattern = new RegExp("(<!-- directorist-dom-data::" + key + "\\s)(.+)(\\s-->)");
  var terget_content = pattern.exec(dom_content);

  if (!terget_content) {
    return '';
  }

  if (typeof terget_content[2] === 'undefined') {
    return '';
  }

  var dom_data = JSON.parse(terget_content[2]);

  if (!dom_data) {
    return '';
  }

  return dom_data;
}

function convertToSelect2(field) {
  if (!field) {
    return;
  }

  if (!field.elm) {
    return;
  }

  if (!field.elm.length) {
    return;
  }

  var default_args = {
    allowClear: true,
    width: '100%',
    templateResult: function templateResult(data) {
      // We only really care if there is an element to pull classes from
      if (!data.element) {
        return data.text;
      }

      var $element = $(data.element);
      var $wrapper = $('<span></span>');
      $wrapper.addClass($element[0].className);
      $wrapper.text(data.text);
      return $wrapper;
    }
  };
  var args = field.args && _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0___default()(field.args) === 'object' ? Object.assign(default_args, field.args) : default_args;
  var options = field.elm.find('option');
  var placeholder = options.length ? options[0].innerHTML : '';

  if (placeholder.length) {
    args.placeholder = placeholder;
  }

  field.elm.select2(args);
}



/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/typeof.js":
/*!*******************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/typeof.js ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    module.exports = _typeof = function _typeof(obj) {
      return typeof obj;
    };

    module.exports["default"] = module.exports, module.exports.__esModule = true;
  } else {
    module.exports = _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };

    module.exports["default"] = module.exports, module.exports.__esModule = true;
  }

  return _typeof(obj);
}

module.exports = _typeof;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ 17:
/*!************************************************************!*\
  !*** multi ./assets/src/js/global/map-scripts/map-view.js ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! ./assets/src/js/global/map-scripts/map-view.js */"./assets/src/js/global/map-scripts/map-view.js");


/***/ })

/******/ });
//# sourceMappingURL=global-map-view.js.map