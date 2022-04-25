/**
 * @license Hyphenopoly 4.11.0 - client side hyphenation for webbrowsers
 * ©2021  Mathias Nater, Güttingen (mathiasnater at gmail dot com)
 * https://github.com/mnater/Hyphenopoly
 *
 * Released under the MIT license
 * http://mnater.github.io/Hyphenopoly/LICENSE
 */
((e,t)=>{"use strict";const n=(n=>{const r=new Map([["afterElementHyphenation",[]],["beforeElementHyphenation",[]],["engineReady",[]],["error",[t=>{t.runDefault&&e.console.warn(t)}]],["hyphenopolyEnd",[]],["hyphenopolyStart",[]]]);if(n.handleEvent){const e=new Map(t.entries(n.handleEvent));r.forEach(((t,n)=>{e.has(n)&&t.unshift(e.get(n))}))}return{fire:(e,t)=>{t.runDefault=!0,t.preventDefault=()=>{t.runDefault=!1},r.get(e).forEach((e=>{e(t)}))}}})(Hyphenopoly);(e=>{function n(e){const t=new Map;function n(n){return t.has(n)?t.get(n):e.get(n)}function r(e,n){t.set(e,n)}return new Proxy(e,{get:(e,t)=>"set"===t?r:"get"===t?n:n(t),ownKeys:()=>[...new Set([...e.keys(),...t.keys()])]})}const r=n(new Map([["defaultLanguage","en-us"],["dontHyphenate",n(new Map("abbr,acronym,audio,br,button,code,img,input,kbd,label,math,option,pre,samp,script,style,sub,sup,svg,textarea,var,video".split(",").map((e=>[e,!0]))))],["dontHyphenateClass","donthyphenate"],["exceptions",new Map],["keepAlive",!0],["normalize",!1],["processShadows",!1],["safeCopy",!0],["substitute",new Map],["timeout",1e3]]));t.entries(e.s).forEach((([e,o])=>{switch(e){case"selectors":r.set("selectors",t.keys(o)),t.entries(o).forEach((([e,o])=>{const a=n(new Map([["compound","hyphen"],["hyphen","­"],["leftmin",0],["leftminPerLang",0],["minWordLength",6],["mixedCase",!0],["orphanControl",1],["rightmin",0],["rightminPerLang",0]]));t.entries(o).forEach((([e,n])=>{"object"==typeof n?a.set(e,new Map(t.entries(n))):a.set(e,n)})),r.set(e,a)}));break;case"dontHyphenate":case"exceptions":t.entries(o).forEach((([t,n])=>{r.get(e).set(t,n)}));break;case"substitute":t.entries(o).forEach((([e,n])=>{r.substitute.set(e,new Map(t.entries(n)))}));break;default:r.set(e,o)}})),e.c=r})(Hyphenopoly),(r=>{const o=r.c;let a=null;function s(e,t="",n=!0){return(e=e.closest("[lang]:not([lang=''])"))&&e.lang?e.lang.toLowerCase():t||(n?a:null)}function l(a=null,l=null){const i=function(){const e=new Map,t=[0];return{add:function(n,r,o){const a={element:n,selector:o};return e.has(r)||e.set(r,[]),e.get(r).push(a),t[0]+=1,a},counter:t,list:e,rem:function(r){let a=0;e.has(r)&&(a=e.get(r).length,e.delete(r),t[0]-=a,0===t[0]&&(n.fire("hyphenopolyEnd",{msg:"hyphenopolyEnd"}),o.keepAlive||(window.Hyphenopoly=null)))}}}(),c=(()=>{let e="."+o.dontHyphenateClass;return t.getOwnPropertyNames(o.dontHyphenate).forEach((t=>{o.dontHyphenate.get(t)&&(e+=","+t)})),e})(),h=o.selectors.join(",")+","+c;function u(t,a,l,c=!1){const p=s(t,a),g=r.cf.langs.get(p);"H9Y"===g?(i.add(t,p,l),!c&&o.safeCopy&&function(t){t.addEventListener("copy",(t=>{t.preventDefault();const n=e.getSelection(),r=document.createElement("div");r.appendChild(n.getRangeAt(0).cloneContents()),t.clipboardData.setData("text/plain",n.toString().replace(/­/g,"")),t.clipboardData.setData("text/html",r.innerHTML.replace(/­/g,""))}),!0)}(t)):g||"zxx"===p||n.fire("error",Error(`Element with '${p}' found, but '${p}.wasm' not loaded. Check language tags!`)),t.childNodes.forEach((e=>{1!==e.nodeType||e.matches(h)||u(e,p,l,!0)}))}function p(e){o.selectors.forEach((t=>{e.querySelectorAll(t).forEach((e=>{u(e,s(e),t,!1)}))}))}return null===a?(o.processShadows&&e.document.querySelectorAll("*").forEach((e=>{e.shadowRoot&&p(e.shadowRoot)})),p(e.document)):u(a,s(a),l),i}n.fire("hyphenopolyStart",{msg:"hyphenopolyStart"});const i=new Map;function c(e,t,r){const a=t+"-"+r;if(i.has(a))return i.get(a);const s=o.get(r);function l(o){let a=e.cache.get(r).get(o);var l;return a||(a=e.exc.has(o)?e.exc.get(o).replace(/-/g,s.hyphen):!s.mixedCase&&(l=o,[...l].map((e=>e===e.toLowerCase())).some(((e,t,n)=>e!==n[0])))?o:-1===o.indexOf("-")?function(r){if(r.length>61)n.fire("error",Error("Found word longer than 61 characters"));else if(!e.reNotAlphabet.test(r))return e.hyphenate(r,s.hyphen.charCodeAt(0),s.leftminPerLang.get(t),s.rightminPerLang.get(t));return r}(o):function(n){let o=null,a=null;return"auto"===s.compound||"all"===s.compound?(a=c(e,t,r),o=n.split("-").map((e=>e.length>=s.minWordLength?a(e):e)),n="auto"===s.compound?o.join("-"):o.join("-​")):n=n.replace("-","-​"),n}(o),e.cache.get(r).set(o,a)),a}return e.cache.set(r,new Map),i.set(a,l),l}const h=new Map;function u(e,t,a){const s=r.languages.get(e),l=o.get(t),i=l.minWordLength,u=RegExp(`[${s.alphabet}a-z̀-ͯ҃-҇ß-öø-þāăąćĉčďđēėęěĝğģĥīįıĵķļľłńņňōőœŕřśŝşšťūŭůűųźżžſǎǐǒǔǖǘǚǜșțʼΐά-ώϐϣϥϧϩϫϭϯϲа-яё-ќўџґүөա-օևअ-ऌएऐओ-नप-रलळव-हऽॠॡঅ-ঌএঐও-নপ-রলশ-হঽৎড়ঢ়য়-ৡਅ-ਊਏਐਓ-ਨਪ-ਰਲਲ਼ਵਸ਼ਸਹઅ-ઋએઐઓ-નપ-રલળવ-હઽૠଅ-ଌଏଐଓ-ନପ-ରଲଳଵ-ହୠୡஃஅ-ஊஎ-ஐஒ-கஙசஜஞடணதந-பம-வஷ-ஹఅ-ఌఎ-ఐఒ-నప-ళవ-హౠౡಅ-ಌಎ-ಐಒ-ನಪ-ಳವ-ಹಽೞೠೡഅ-ഌഎ-ഐഒ-നപ-ഹൠൡൺ-ൿก-ฮะาำเ-ๅა-ჰሀ-ቈቊ-ቍቐ-ቖቘቚ-ቝበ-ኈኊ-ኍነ-ኰኲ-ኵኸ-ኾዀዂ-ዅወ-ዖዘ-ጐጒ-ጕጘ-ፚᎀ-ᎏḍḷṁṃṅṇṭἀ-ἇἐ-ἕἠ-ἧἰ-ἷὀ-ὅὐ-ὗὠ-ὧὰ-ώᾀ-ᾇᾐ-ᾗᾠ-ᾧᾲ-ᾴᾶᾷῂ-ῄῆῇῒΐῖῗῢ-ῧῲ-ῴῶῷⲁⲃⲅⲇⲉⲍⲏⲑⲓⲕⲗⲙⲛⲝⲟⲡⲣⲥⲧⲩⲫⲭⲯⲱⳉⶀ-ⶖⶠ-ⶦⶨ-ⶮⶰ-ⶶⶸ-ⶾⷀ-ⷆⷈ-ⷎⷐ-ⷖⷘ-ⷞꬁ-ꬆꬉ-ꬎꬑ-ꬖꬠ-ꬦꬨ-ꬮ­​-‍-]{${i},}`,"gui");function p(n){o.normalize&&(n=n.normalize("NFC"));let r=n.replace(u,c(s,e,t));return 1!==l.orphanControl&&(r=r.replace(/(\u0020*)(\S+)(\s*)$/,function(e){if(h.has(e))return h.get(e);const t=o.get(e);function n(e,n,r,o){return 3===t.orphanControl&&" "===n&&(n=" "),n+r.replace(RegExp(t.hyphen,"g"),"")+o}return h.set(e,n),n}(t))),r}let g=null;var f;return"string"==typeof a?g=p(a):a instanceof HTMLElement&&(f=a,n.fire("beforeElementHyphenation",{el:f,lang:e}),f.childNodes.forEach((e=>{3===e.nodeType&&/\S/.test(e.data)&&e.data.length>=i&&(e.data=p(e.data))})),r.res.els.counter[0]-=1,n.fire("afterElementHyphenation",{el:f,lang:e})),g}function p(t,a){const s=a.list.get(t);s?s.forEach((e=>{u(t,e.selector,e.element)})):n.fire("error",Error(`Engine for language '${t}' loaded, but no elements found.`)),0===a.counter[0]&&(e.clearTimeout(r.timeOutHandler),-1!==o.hide&&r.hide(0,null),n.fire("hyphenopolyEnd",{msg:"hyphenopolyEnd"}),o.keepAlive||(window.Hyphenopoly=null))}function g(e){let t="";return o.exceptions.has(e)&&(t=o.exceptions.get(e)),o.exceptions.has("global")&&(""===t?t=o.exceptions.get("global"):t+=", "+o.exceptions.get("global")),""===t?new Map:new Map(t.split(", ").map((e=>[e.replace(/-/g,""),e])))}r.unhyphenate=()=>(r.res.els.list.forEach((e=>{e.forEach((e=>{const t=e.element.firstChild;t.data=t.data.replace(RegExp(o[e.selector].hyphen,"g"),"")}))})),Promise.resolve(r.res.els));const f=(()=>{if(e.TextDecoder){const e=new TextDecoder("utf-16le");return t=>e.decode(t)}return e=>String.fromCharCode.apply(null,e)})();r.res.DOM.then((()=>{a=s(e.document.documentElement,"",!1),a||""===o.defaultLanguage||(a=o.defaultLanguage);const t=l();r.res.els=t,t.list.forEach(((e,n)=>{r.languages&&r.languages.has(n)&&r.languages.get(n).ready&&p(n,t)}))})),r.res.he.forEach(((e,t)=>{!function(e,t){const a=window.WebAssembly;e.w.then((n=>{if(n.ok){let t=n;return e.c&&(t=n.clone()),a.instantiateStreaming&&"application/wasm"===n.headers.get("Content-Type")?a.instantiateStreaming(t):t.arrayBuffer().then((e=>a.instantiate(e)))}return Promise.reject(Error(`File ${t}.wasm can't be loaded from ${r.paths.patterndir}`))})).then((function(e){const s=e.instance.exports;let l=s.conv();l=function(e,n){return o.substitute.has(t)&&o.substitute.get(t).forEach(((t,r)=>{const o=r.toUpperCase(),a=o===r?0:o.charCodeAt(0);e=n.subst(r.charCodeAt(0),a,t.charCodeAt(0))})),e}(l,s);const i={buf:s.mem.buffer,hw:a.Global?s.hwo.value:s.hwo,lm:a.Global?s.lmi.value:s.lmi,rm:a.Global?s.rmi.value:s.rmi,wo:a.Global?s.uwo.value:s.uwo};!function(e,t,a,s,l){o.selectors.forEach((t=>{const n=o.get(t);0===n.leftminPerLang&&n.set("leftminPerLang",new Map),0===n.rightminPerLang&&n.set("rightminPerLang",new Map),n.leftminPerLang.set(e,Math.max(s,n.leftmin,Number(n.leftminPerLang.get(e))||0)),n.rightminPerLang.set(e,Math.max(l,n.rightmin,Number(n.rightminPerLang.get(e))||0))})),r.languages||(r.languages=new Map),a=a.replace(/\\*-/g,"\\-"),r.languages.set(e,{alphabet:a,cache:new Map,exc:g(e),hyphenate:t,ready:!0,reNotAlphabet:RegExp(`[^${a}]`,"i")}),r.hy6ors.get(e).resolve(function(e){return(t,r=".hyphenate")=>("string"!=typeof t&&n.fire("error",Error("This use of hyphenators is deprecated. See https://mnater.github.io/Hyphenopoly/Hyphenators.html")),u(e,r,t))}(e)),n.fire("engineReady",{lang:e}),r.res.els&&p(e,r.res.els)}(t,function(e,t){const n=new Uint16Array(e.buf,e.wo,64);return(r,o,a,s)=>{n.set([95,...[...r].map((e=>e.charCodeAt(0))),95,0]);const l=t(a,s,o);return l>0&&(r=f(new Uint16Array(e.buf,e.hw,l))),r}}(i,s.hyphenate),f(new Uint16Array(s.mem.buffer,1026,l-1)),i.lm,i.rm)}),(e=>{n.fire("error",e),r.res.els.rem(t)}))}(e,t)})),Promise.all([...r.hy6ors.entries()].reduce(((e,t)=>"HTML"!==t[0]?e.concat(t[1]):e),[]).concat(r.res.DOM)).then((()=>{r.hy6ors.get("HTML").resolve(((e,t=".hyphenate")=>(l(e,t).list.forEach(((e,t)=>{e.forEach((e=>{u(t,e.selector,e.element)}))})),null)))}),(e=>{n.fire("error",e)}))})(Hyphenopoly)})(window,Object);