!function(e){function t(r){if(n[r])return n[r].exports;var o=n[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,t),o.l=!0,o.exports}var n={};t.m=e,t.c=n,t.d=function(e,n,r){t.o(e,n)||Object.defineProperty(e,n,{configurable:!1,enumerable:!0,get:r})},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=0)}([function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0});n(1)},function(e,t,n){"use strict";function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!==typeof t&&"function"!==typeof t?e:t}function i(e,t){if("function"!==typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}var l=n(2),s=(n.n(l),n(3)),a=(n.n(s),n(4)),u=n.n(a),p=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),c=wp.i18n.__,f=wp.element.Component,m=wp.blocks.registerBlockType,h=wp.editor.InspectorControls,b=wp.date.__experimentalGetSettings,v=wp.hooks.addFilter,y=wp.components,d=y.PanelBody,g=y.SelectControl,w=y.TextControl,S=y.DateTimePicker,j=y.Button,O=wp.compose.createHigherOrderComponent,E=wp.element.Fragment;v("blocks.registerBlockType","simpleshop/attributes/custom",function(e,t){return e.attributes=u()(e.attributes,{simpleShopGroup:{type:"string"},simpleShopIsMember:{type:"string"},simpleShopIsLoggedIn:{type:"string"},simpleShopDaysToView:{type:"string"},simpleShopSpecificDateFrom:{type:"string",default:""},simpleShopSpecificDateTo:{type:"string"}}),e});var _=[{label:"Choose",value:""},{label:"Yes",value:"yes"},{label:"No",value:"no"}],I=O(function(e){return function(t){var n=t.attributes,r=n.simpleShopGroup,o=n.simpleShopIsMember,i=n.simpleShopIsLoggedIn,l=n.simpleShopDaysToView,s=n.simpleShopSpecificDateFrom,a=n.simpleShopSpecificDateTo,u=b(),p=/a(?!\\)/i.test(u.formats.time.toLowerCase().replace(/\\\\/g,"").split("").reverse().join("")),f=ssGutenbergVariables.groups,m=[{label:"None",value:""}];for(var v in f)m.push({label:f[v],value:v});return wp.element.createElement(E,null,wp.element.createElement(e,t),wp.element.createElement(h,null,wp.element.createElement(d,{title:c("Simpleshop Settings"),initialOpen:!0},wp.element.createElement(g,{label:c("Group","ss"),value:r,options:m,onChange:function(e){t.setAttributes({simpleShopGroup:e})}}),wp.element.createElement(g,{label:c("Is member","ss"),value:o,options:_,onChange:function(e){t.setAttributes({simpleShopIsMember:e})}}),wp.element.createElement(g,{label:c("Is logged in","ss"),value:i,options:_,onChange:function(e){t.setAttributes({simpleShopIsLoggedIn:e})}}),wp.element.createElement(w,{label:c("Days to view","ss"),value:l,onChange:function(e){t.setAttributes({simpleShopDaysToView:e})}}),wp.element.createElement(S,{label:c("From date","ss"),currentDate:s,value:s,onChange:function(e){t.setAttributes({simpleShopSpecificDateFrom:e})},is12Hour:p}),wp.element.createElement(S,{label:c("To date","ss"),currentDate:a,value:a,onChange:function(e){t.setAttributes({simpleShopSpecificDateTo:e})},is12Hour:p}))))}},"withInspectorControls");wp.hooks.addFilter("editor.BlockEdit","display-heading/with-inspector-controls",I);var F=function(e){function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return i(t,e),p(t,[{key:"render",value:function(){var e=this,t=this.props,n=(t.setAttributes,t.attributes.ssFormId),r=[];return jQuery.each(ssGutenbergVariables.products,function(e,t){r.push({label:t,value:e})}),wp.element.createElement(h,{key:"inspector"},wp.element.createElement(d,null,wp.element.createElement(g,{className:"simpleshop-form-select",label:c("Form"),description:c("Select the SimpleShop Form"),options:r,value:n,onChange:function(t){return e.props.setAttributes({ssFormId:t})}}),wp.element.createElement(j,{className:"simpleshop-reload"},c("Reload forms"))))}}]),t}(f),C=function(e){function t(){return r(this,t),o(this,(t.__proto__||Object.getPrototypeOf(t)).apply(this,arguments))}return i(t,e),p(t,[{key:"render",value:function(){var e=this.props,t=e.attributes.ssFormId,n=e.setAttributes;return[wp.element.createElement(F,Object.assign({setAttributes:n},this.props)),wp.element.createElement("div",{id:"cgb-testimonial",className:"cgb-testimonial"},"SimpleShop Form ",t)]}}]),t}(f);m("simpleshop/simpleshop-form",{title:c("SimpleShop Form"),icon:"shield",category:"common",keywords:[c("SimpleShop"),c("form")],attributes:{ssFormId:{type:"string",default:"Choose form"}},edit:C,save:function(e){console.log(e);var t=e.attributes.ssFormId,n="https://form.simpleshop.cz/iframe/js/?id="+t;return wp.element.createElement("div",{class:"simpleshop-form"},wp.element.createElement("script",{type:"text/javascript",src:n}))}}),jQuery("body").on("click",".simpleshop-reload",function(){jQuery.post(ajaxurl,{action:"load_simple_shop_products"},function(e){var t=jQuery(".simpleshop-form-select select");t.find("option").remove(),jQuery.each(e,function(e,n){t.append('<option value="'+e+'">'+n+"</option>")})},"json")})},function(e,t){},function(e,t){},function(e,t){function n(e,t,n){switch(n.length){case 0:return e.call(t);case 1:return e.call(t,n[0]);case 2:return e.call(t,n[0],n[1]);case 3:return e.call(t,n[0],n[1],n[2])}return e.apply(t,n)}function r(e,t){for(var n=-1,r=Array(e);++n<e;)r[n]=t(n);return r}function o(e,t){var n=x(e)||m(e)?r(e.length,String):[],o=n.length,i=!!o;for(var l in e)!t&&!F.call(e,l)||i&&("length"==l||u(l,o))||n.push(l);return n}function i(e,t,n){var r=e[t];F.call(e,t)&&f(r,n)&&(void 0!==n||t in e)||(e[t]=n)}function l(e){if(!c(e))return k(e);var t=[];for(var n in Object(e))F.call(e,n)&&"constructor"!=n&&t.push(n);return t}function s(e,t){return t=D(void 0===t?e.length-1:t,0),function(){for(var r=arguments,o=-1,i=D(r.length-t,0),l=Array(i);++o<i;)l[o]=r[t+o];o=-1;for(var s=Array(t+1);++o<t;)s[o]=r[o];return s[t]=l,n(e,this,s)}}function a(e,t,n,r){n||(n={});for(var o=-1,l=t.length;++o<l;){var s=t[o],a=r?r(n[s],e[s],s,n,e):void 0;i(n,s,void 0===a?e[s]:a)}return n}function u(e,t){return!!(t=null==t?S:t)&&("number"==typeof e||_.test(e))&&e>-1&&e%1==0&&e<t}function p(e,t,n){if(!d(n))return!1;var r=typeof t;return!!("number"==r?h(n)&&u(t,n.length):"string"==r&&t in n)&&f(n[t],e)}function c(e){var t=e&&e.constructor;return e===("function"==typeof t&&t.prototype||I)}function f(e,t){return e===t||e!==e&&t!==t}function m(e){return b(e)&&F.call(e,"callee")&&(!A.call(e,"callee")||C.call(e)==j)}function h(e){return null!=e&&y(e.length)&&!v(e)}function b(e){return g(e)&&h(e)}function v(e){var t=d(e)?C.call(e):"";return t==O||t==E}function y(e){return"number"==typeof e&&e>-1&&e%1==0&&e<=S}function d(e){var t=typeof e;return!!e&&("object"==t||"function"==t)}function g(e){return!!e&&"object"==typeof e}function w(e){return h(e)?o(e):l(e)}var S=9007199254740991,j="[object Arguments]",O="[object Function]",E="[object GeneratorFunction]",_=/^(?:0|[1-9]\d*)$/,I=Object.prototype,F=I.hasOwnProperty,C=I.toString,A=I.propertyIsEnumerable,k=function(e,t){return function(n){return e(t(n))}}(Object.keys,Object),D=Math.max,T=!A.call({valueOf:1},"valueOf"),x=Array.isArray,P=function(e){return s(function(t,n){var r=-1,o=n.length,i=o>1?n[o-1]:void 0,l=o>2?n[2]:void 0;for(i=e.length>3&&"function"==typeof i?(o--,i):void 0,l&&p(n[0],n[1],l)&&(i=o<3?void 0:i,o=1),t=Object(t);++r<o;){var s=n[r];s&&e(t,s,r,i)}return t})}(function(e,t){if(T||c(t)||h(t))return void a(t,w(t),e);for(var n in t)F.call(t,n)&&i(e,n,t[n])});e.exports=P}]);