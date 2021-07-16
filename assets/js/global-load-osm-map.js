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
/******/ 	return __webpack_require__(__webpack_require__.s = 16);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/global/map-scripts/load-osm-map.js":
/*!**********************************************************!*\
  !*** ./assets/src/js/global/map-scripts/load-osm-map.js ***!
  \**********************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _lib_helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./../../lib/helper */ "./assets/src/js/lib/helper.js");

var loc_data = Object(_lib_helper__WEBPACK_IMPORTED_MODULE_0__["get_dom_data"])('loc_data');
window.addEventListener('load', setup_map);
window.addEventListener('directorist-reload-listings-map-archive', setup_map);

function setup_map() {
  bundle1.fillPlaceholders();
  var localVersion = bundle1.getLibVersion('leaflet.featuregroup.subgroup', 'local');

  if (localVersion) {
    localVersion.checkAssetsAvailability(true).then(function () {
      load();
    }).catch(function () {
      var version102 = bundle1.getLibVersion('leaflet.featuregroup.subgroup', '1.0.2');

      if (version102) {
        version102.defaultVersion = true;
      }

      load();
    });
  } else {
    load();
  }
}

function load() {
  var url = window.location.href;
  var urlParts = URI.parse(url);
  var queryStringParts = URI.parseQuery(urlParts.query);
  var list = bundle1.getAndSelectVersionsAssetsList(queryStringParts);
  list.push({
    type: 'script',
    path: loc_data.script_path
  });
  loadJsCss.list(list, {
    delayScripts: 500 // Load scripts after stylesheets, delayed by this duration (in ms).

  });
}

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

/***/ 16:
/*!****************************************************************!*\
  !*** multi ./assets/src/js/global/map-scripts/load-osm-map.js ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! ./assets/src/js/global/map-scripts/load-osm-map.js */"./assets/src/js/global/map-scripts/load-osm-map.js");


/***/ })

/******/ });
//# sourceMappingURL=global-load-osm-map.js.map