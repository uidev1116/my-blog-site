(self.webpackChunkmun_ticket=self.webpackChunkmun_ticket||[]).push([[906],{8091:e=>{"use strict";function l(e,l){if(null==e)throw new TypeError("Cannot convert first argument to object");for(var t=Object(e),s=1;s<arguments.length;s++){var n=arguments[s];if(null!=n)for(var o=Object.keys(Object(n)),r=0,i=o.length;r<i;r++){var a=o[r],c=Object.getOwnPropertyDescriptor(n,a);void 0!==c&&c.enumerable&&(t[a]=n[a])}}return t}e.exports={assign:l,polyfill:function(){Object.assign||Object.defineProperty(Object,"assign",{enumerable:!1,configurable:!0,writable:!0,value:l})}}},7638:(e,l,t)=>{"use strict";Object.defineProperty(l,"__esModule",{value:!0});var s=function(){function e(e,l){for(var t=0;t<l.length;t++){var s=l[t];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(e,s.key,s)}}return function(l,t,s){return t&&e(l.prototype,t),s&&e(l,s),l}}(),n=t(8091),o=t(7249),r={suggestClass:"is-active",scrollableClass:"is-scrollable",scrollableRightClass:"is-right-scrollable",scrollableLeftClass:"is-left-scrollable",scrollHintClass:"scroll-hint",scrollHintIconClass:"scroll-hint-icon",scrollHintIconAppendClass:"",scrollHintIconWrapClass:"scroll-hint-icon-wrap",scrollHintText:"scroll-hint-text",scrollHintBorderWidth:10,remainingTime:-1,enableOverflowScrolling:!0,applyToParents:!1,suggestiveShadow:!1,offset:0,i18n:{scrollable:"scrollable"}},i=function(){function e(l,t){var s=this;!function(e,l){if(!(e instanceof l))throw new TypeError("Cannot call a class as a function")}(this,e),this.opt=(0,n.assign)({},r,t),this.items=[];var i="string"==typeof l?document.querySelectorAll(l):l,a=this.opt.applyToParents;[].forEach.call(i,(function(e){a&&(e=e.parentElement),e.style.position="relative",e.style.overflow="auto",s.opt.enableOverflowScrolling&&("overflowScrolling"in e.style?e.style.overflowScrolling="touch":"webkitOverflowScrolling"in e.style&&(e.style.webkitOverflowScrolling="touch"));var l={element:e,scrolledIn:!1,interacted:!1};document.addEventListener("scroll",(function(t){t.target===e&&(l.interacted=!0,s.updateItem(l))}),!0),(0,o.addClass)(e,s.opt.scrollHintClass),(0,o.append)(e,'<div class="'+s.opt.scrollHintIconWrapClass+'" data-target="scrollable-icon">\n        <span class="'+s.opt.scrollHintIconClass+(s.opt.scrollHintIconAppendClass?" "+s.opt.scrollHintIconAppendClass:"")+'">\n          <div class="'+s.opt.scrollHintText+'">'+s.opt.i18n.scrollable+"</div>\n        </span>\n      </div>"),s.items.push(l)})),window.addEventListener("scroll",(function(){s.updateItems()})),window.addEventListener("resize",(function(){s.updateItems()})),this.updateItems()}return s(e,[{key:"isScrollable",value:function(e){var l=this.opt.offset,t=e.element;return t.offsetWidth+l<t.scrollWidth}},{key:"checkScrollableDir",value:function(e){var l=this.opt,t=l.scrollHintBorderWidth,s=l.scrollableRightClass,n=l.scrollableLeftClass,r=e.element,i=r.children[0].scrollWidth,a=r.offsetWidth,c=r.scrollLeft;a+c<i-t?(0,o.addClass)(r,s):(0,o.removeClass)(r,s),a<i&&c>t?(0,o.addClass)(r,n):(0,o.removeClass)(r,n)}},{key:"needSuggest",value:function(e){var l=e.scrolledIn;return!e.interacted&&l&&this.isScrollable(e)}},{key:"updateItems",value:function(){var e=this;[].forEach.call(this.items,(function(l){e.updateItem(l)}))}},{key:"updateStatus",value:function(e){var l=this,t=e.element;e.scrolledIn||(0,o.getOffset)(t).top+t.offsetHeight/2<(0,o.getScrollTop)()+window.innerHeight&&(e.scrolledIn=!0,-1!==this.opt.remainingTime&&setTimeout((function(){e.interacted=!0,l.updateItem(e)}),this.opt.remainingTime))}},{key:"updateItem",value:function(e){var l=this.opt,t=e.element,s=t.querySelector('[data-target="scrollable-icon"]');this.updateStatus(e),this.isScrollable(e)?(0,o.addClass)(t,l.scrollableClass):(0,o.removeClass)(t,l.scrollableClass),this.needSuggest(e)?(0,o.addClass)(s,l.suggestClass):(0,o.removeClass)(s,l.suggestClass),l.suggestiveShadow&&this.checkScrollableDir(e)}}]),e}();l.default=i,e.exports=l.default},7249:(e,l)=>{"use strict";Object.defineProperty(l,"__esModule",{value:!0}),l.append=function(e,l){var t=document.createElement("div");for(t.innerHTML=l;t.children.length>0;)e.appendChild(t.children[0])},l.addClass=function(e,l){e.classList?e.classList.add(l):e.className+=" "+l},l.removeClass=function(e,l){e.classList?e.classList.remove(l):e.className=e.className.replace(new RegExp("(^|\\b)"+l.split(" ").join("|")+"(\\b|$)","gi")," ")};var t=l.getScrollTop=function(){return window.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop||0},s=l.getScrollLeft=function(){return window.pageXOffset||document.documentElement.scrollLeft||document.body.scrollLeft||0};l.getOffset=function(e){var l=e.getBoundingClientRect();return{top:l.top+t(),left:l.left+s()}}},559:(e,l,t)=>{"use strict";t.r(l),t.d(l,{default:()=>o});var s=t(7638),n=t.n(s);const o=(e,l={})=>{new(n())(e,Object.assign({suggestClass:"is-active",scrollableClass:"is-scrollable",scrollableRightClass:"is-right-scrollable",scrollableLeftClass:"is-left-scrollable",scrollHintClass:"scroll-hint",scrollHintIconClass:"scroll-hint-icon",scrollHintIconAppendClass:"scroll-hint-icon-white",scrollHintIconWrapClass:"scroll-hint-icon-wrap",scrollHintText:"scroll-hint-text",remainingTime:-1,scrollHintBorderWidth:10,enableOverflowScrolling:!0,suggestiveShadow:!1,applyToParents:!1,i18n:{scrollable:"スクロールできます"}},l))}}}]);