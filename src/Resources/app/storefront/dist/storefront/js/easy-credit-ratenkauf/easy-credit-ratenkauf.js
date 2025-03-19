(()=>{"use strict";var e={857:e=>{var t=function(e){var t;return!!e&&"object"==typeof e&&"[object RegExp]"!==(t=Object.prototype.toString.call(e))&&"[object Date]"!==t&&e.$$typeof!==r},r="function"==typeof Symbol&&Symbol.for?Symbol.for("react.element"):60103;function i(e,t){return!1!==t.clone&&t.isMergeableObject(e)?o(Array.isArray(e)?[]:{},e,t):e}function n(e,t,r){return e.concat(t).map(function(e){return i(e,r)})}function s(e){return Object.keys(e).concat(Object.getOwnPropertySymbols?Object.getOwnPropertySymbols(e).filter(function(t){return Object.propertyIsEnumerable.call(e,t)}):[])}function a(e,t){try{return t in e}catch(e){return!1}}function o(e,r,c){(c=c||{}).arrayMerge=c.arrayMerge||n,c.isMergeableObject=c.isMergeableObject||t,c.cloneUnlessOtherwiseSpecified=i;var l,u,d=Array.isArray(r);return d!==Array.isArray(e)?i(r,c):d?c.arrayMerge(e,r,c):(u={},(l=c).isMergeableObject(e)&&s(e).forEach(function(t){u[t]=i(e[t],l)}),s(r).forEach(function(t){(!a(e,t)||Object.hasOwnProperty.call(e,t)&&Object.propertyIsEnumerable.call(e,t))&&(a(e,t)&&l.isMergeableObject(r[t])?u[t]=(function(e,t){if(!t.customMerge)return o;var r=t.customMerge(e);return"function"==typeof r?r:o})(t,l)(e[t],r[t],l):u[t]=i(r[t],l))}),u)}o.all=function(e,t){if(!Array.isArray(e))throw Error("first argument should be an array");return e.reduce(function(e,r){return o(e,r,t)},{})},e.exports=o}},t={};function r(i){var n=t[i];if(void 0!==n)return n.exports;var s=t[i]={exports:{}};return e[i](s,s.exports,r),s.exports}(()=>{r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t}})(),(()=>{r.d=(e,t)=>{for(var i in t)r.o(t,i)&&!r.o(e,i)&&Object.defineProperty(e,i,{enumerable:!0,get:t[i]})}})(),(()=>{r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t)})(),(()=>{var e=r(857),t=r.n(e);class i{static ucFirst(e){return e.charAt(0).toUpperCase()+e.slice(1)}static lcFirst(e){return e.charAt(0).toLowerCase()+e.slice(1)}static toDashCase(e){return e.replace(/([A-Z])/g,"-$1").replace(/^-/,"").toLowerCase()}static toLowerCamelCase(e,t){let r=i.toUpperCamelCase(e,t);return i.lcFirst(r)}static toUpperCamelCase(e,t){return t?e.split(t).map(e=>i.ucFirst(e.toLowerCase())).join(""):i.ucFirst(e.toLowerCase())}static parsePrimitive(e){try{return/^\d+(.|,)\d+$/.test(e)&&(e=e.replace(",",".")),JSON.parse(e)}catch(t){return e.toString()}}}class n{static isNode(e){return"object"==typeof e&&null!==e&&(e===document||e===window||e instanceof Node)}static hasAttribute(e,t){if(!n.isNode(e))throw Error("The element must be a valid HTML Node!");return"function"==typeof e.hasAttribute&&e.hasAttribute(t)}static getAttribute(e,t){let r=!(arguments.length>2)||void 0===arguments[2]||arguments[2];if(r&&!1===n.hasAttribute(e,t))throw Error('The required property "'.concat(t,'" does not exist!'));if("function"!=typeof e.getAttribute){if(r)throw Error("This node doesn't support the getAttribute function!");return}return e.getAttribute(t)}static getDataAttribute(e,t){let r=!(arguments.length>2)||void 0===arguments[2]||arguments[2],s=t.replace(/^data(|-)/,""),a=i.toLowerCamelCase(s,"-");if(!n.isNode(e)){if(r)throw Error("The passed node is not a valid HTML Node!");return}if(void 0===e.dataset){if(r)throw Error("This node doesn't support the dataset attribute!");return}let o=e.dataset[a];if(void 0===o){if(r)throw Error('The required data attribute "'.concat(t,'" does not exist on ').concat(e,"!"));return o}return i.parsePrimitive(o)}static querySelector(e,t){let r=!(arguments.length>2)||void 0===arguments[2]||arguments[2];if(r&&!n.isNode(e))throw Error("The parent node is not a valid HTML Node!");let i=e.querySelector(t)||!1;if(r&&!1===i)throw Error('The required element "'.concat(t,'" does not exist in parent node!'));return i}static querySelectorAll(e,t){let r=!(arguments.length>2)||void 0===arguments[2]||arguments[2];if(r&&!n.isNode(e))throw Error("The parent node is not a valid HTML Node!");let i=e.querySelectorAll(t);if(0===i.length&&(i=!1),r&&!1===i)throw Error('At least one item of "'.concat(t,'" must exist in parent node!'));return i}}class s{publish(e){let t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},r=arguments.length>2&&void 0!==arguments[2]&&arguments[2],i=new CustomEvent(e,{detail:t,cancelable:r});return this.el.dispatchEvent(i),i}subscribe(e,t){let r=arguments.length>2&&void 0!==arguments[2]?arguments[2]:{},i=this,n=e.split("."),s=r.scope?t.bind(r.scope):t;if(r.once&&!0===r.once){let t=s;s=function(r){i.unsubscribe(e),t(r)}}return this.el.addEventListener(n[0],s),this.listeners.push({splitEventName:n,opts:r,cb:s}),!0}unsubscribe(e){let t=e.split(".");return this.listeners=this.listeners.reduce((e,r)=>([...r.splitEventName].sort().toString()===t.sort().toString()?this.el.removeEventListener(r.splitEventName[0],r.cb):e.push(r),e),[]),!0}reset(){return this.listeners.forEach(e=>{this.el.removeEventListener(e.splitEventName[0],e.cb)}),this.listeners=[],!0}get el(){return this._el}set el(e){this._el=e}get listeners(){return this._listeners}set listeners(e){this._listeners=e}constructor(e=document){this._el=e,e.$emitter=this,this._listeners=[]}}class a{init(){throw Error('The "init" method for the plugin "'.concat(this._pluginName,'" is not defined.'))}update(){}_init(){this._initialized||(this.init(),this._initialized=!0)}_update(){this._initialized&&this.update()}_mergeOptions(e){let r=i.toDashCase(this._pluginName),s=n.getDataAttribute(this.el,"data-".concat(r,"-config"),!1),a=n.getAttribute(this.el,"data-".concat(r,"-options"),!1),o=[this.constructor.options,this.options,e];s&&o.push(window.PluginConfigManager.get(this._pluginName,s));try{a&&o.push(JSON.parse(a))}catch(e){throw console.error(this.el),Error('The data attribute "data-'.concat(r,'-options" could not be parsed to json: ').concat(e.message))}return t().all(o.filter(e=>e instanceof Object&&!(e instanceof Array)).map(e=>e||{}))}_registerInstance(){window.PluginManager.getPluginInstancesFromElement(this.el).set(this._pluginName,this),window.PluginManager.getPlugin(this._pluginName,!1).get("instances").push(this)}_getPluginName(e){return e||(e=this.constructor.name),e}constructor(e,t={},r=!1){if(!n.isNode(e))throw Error("There is no valid element given.");this.el=e,this.$emitter=new s(this.el),this._pluginName=this._getPluginName(r),this.options=this._mergeOptions(t),this._initialized=!1,this._registerInstance(),this._init()}}let o=async()=>{let e;if(void 0===window.csrf)return;let t=window.csrf;return"1"===t.enabled&&"ajax"===t.mode&&(e=await fetch(window.router["frontend.csrf.generateToken"],{method:"POST",headers:new Headers({"content-type":"application/json"})}).then(e=>e.json()).then(e=>e.token)),e},c=(e,t)=>{var r=document.createElement("input");return r.setAttribute("type","hidden"),r.setAttribute("name",e),r.setAttribute("value",t),r};PluginManager.register("EasyCreditRatenkaufCheckout",class extends a{init(){document.querySelector("easycredit-checkout")&&document.querySelector("easycredit-checkout").addEventListener("submit",async e=>{var t=document.getElementById("changePaymentForm");let r=await o();return r&&t.append(c("_csrf_token",r)),t.append(c("easycredit[submit]","1")),t.append(c("easycredit[number-of-installments]",e.detail.numberOfInstallments)),t.submit(),!1})}},".is-ctl-checkout.is-act-confirmpage"),PluginManager.register("EasyCreditRatenkaufCheckoutExpress",class extends a{init(){this.el.addEventListener("submit",async e=>{let t=this.buildAdditionalParams(e.detail),r=document.getElementById("productDetailPageBuyProductForm");if(r){var i;let e={},n=await o();if(n&&(e._csrf_token=n),e.redirectTo="frontend.easycredit.express",e.redirectParameters=JSON.stringify(t),i=await this.replicateForm(r,e)){i.submit();return}}if(document.querySelector(".is-ctl-checkout.is-act-cartpage")||this.el.closest(".cart-offcanvas")){let e=new URLSearchParams(t).toString();window.location.href="/easycredit/express?"+e;return}window.alert("Die Express-Zahlung mit easyCredit konnte nicht gestartet werden."),console.error("easyCredit payment could not be started. Please check the integration.")})}replicateForm(e,t){if(!(e instanceof HTMLFormElement))return!1;let r=e.getAttribute("action"),i=e.getAttribute("method");if(!r||!i)return!1;let n=document.createElement("form");n.setAttribute("action",r),n.setAttribute("method",i),n.style.display="none";let s=new FormData(e);for(let[e,r]of Object.entries(t))s.set(e,r);for(let e of s.keys()){let t=document.createElement("input");t.setAttribute("type","hidden"),t.setAttribute("name",e),t.setAttribute("value",s.get(e)),n.appendChild(t)}return document.body.appendChild(n),n}constructor(...e){super(...e),this.buildAdditionalParams=e=>{let t={};for(let[r,i]of(e.express="1",Object.entries(e)))t["easycredit["+r+"]"]=i;return t}}},"easycredit-express-button"),PluginManager.register("EasyCreditRatenkaufWidget",class extends a{init(){this.initWidget(document.querySelector(".product-detail-buy")),this.initWidget(document.querySelector(".cms-element-product-listing")),this.registerOffCanvas()}registerOffCanvas(){let e=document.querySelector("[data-off-canvas-cart]");e&&window.PluginManager.getPluginInstanceFromElement(e,"OffCanvasCart").$emitter.subscribe("offCanvasOpened",this.onOffCanvasOpened.bind(this))}onOffCanvasOpened(){this.initWidget(document.querySelector("div.cart-offcanvas"))}initWidget(e){if(!e)return;let t=this.getMeta("widget-selector",e);if(null===t||null===this.getMeta("api-key"))return;let r=this.processSelector(t);e.querySelectorAll(r.selector).forEach(e=>{this.applyWidget(e,r.attributes)})}applyWidget(e,t){let r=this.getMeta("amount",e);if(null===r||isNaN(r)){let t=e.parentNode;r=t&&t.querySelector("[itemprop=price]")?t.querySelector("[itemprop=price]").content:null}if(null===r||isNaN(r))return;let i=document.createElement("easycredit-widget");if(i.setAttribute("webshop-id",this.getMeta("api-key")),i.setAttribute("amount",r),i.setAttribute("payment-types",this.getMeta("payment-types",e)),this.getMeta("disable-flexprice")?i.setAttribute("disable-flexprice","true"):i.removeAttribute("disable-flexprice"),t)for(let[e,r]of Object.entries(t))i.setAttribute(e,r);e.appendChild(i)}getMeta(e){let t,r=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null,i="meta[name=easycredit-"+e+"]";return(t=r?this.searchUpTheTree(r,i):document.querySelector(i))?t.content:null}processSelector(e){let t;if(t=e.match(/(.+) easycredit-widget(\[.+?\])$/)){let e=t[2].split("]").map(e=>e.slice(1).split("=")).filter(e=>{let[t,r]=e;return t}).reduce((e,t)=>{let[r,i]=t;return{...e,[r]:i}},{});return{selector:t[1],attributes:e}}return{selector:e}}searchUpTheTree(e,t){for(;e&&e.parentElement;){let r=e.parentElement,i=Array.from(r.children).find(r=>r!==e&&r.matches(t));if(i)return i;e=r}return e===document.documentElement&&Array.from(document.head.children).find(e=>e.matches(t))||null}},"body"),PluginManager.register("EasyCreditRatenkaufMarketing",class extends a{init(){this.initMarketing()}initMarketing(){if(this.body=document.querySelector("body"),this.bar=document.querySelector("easycredit-box-top"),this.bar&&this.body.classList.add("easycredit-box-top"),this.card=document.querySelector(".easycredit-box-listing"),this.card){var e,t=[...(e=this.card).parentElement.children].filter(t=>t!=e),r=this.card.querySelector("easycredit-box-listing").getAttribute("position"),i=Number(r-1),n=Number(r-2);!r||i<=0||(n in t?t[n].after(this.card):this.card.parentElement.append(this.card))}}},"body")})()})();