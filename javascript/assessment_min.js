var closetimer=0,ddmenuitem=0,homemenuloaded=0;function mopen(a,b){"homemenu"==a&&0==homemenuloaded&&(basicahah(imasroot+"/gethomemenu.php?cid="+b,"homemenu"),homemenuloaded=1);mcancelclosetime();ddmenuitem?(ddmenuitem.style.visibility="hidden",ddmenuitem=null):(ddmenuitem=document.getElementById(a),ddmenuitem.style.visibility="visible")}function mclose(){ddmenuitem&&(ddmenuitem.style.visibility="hidden",ddmenuitem=null)}function mclosetime(){closetimer=window.setTimeout(mclose,250)}
function mcancelclosetime(){closetimer&&(window.clearTimeout(closetimer),closetimer=null)}function basicahah(a,b,c){null==c&&(c=" Fetching data... ");document.getElementById(b).innerHTML=c;c=!1;window.XMLHttpRequest?(req=new XMLHttpRequest,c=!0):window.ActiveXObject&&(req=new ActiveXObject("Microsoft.XMLHTTP"),c=!0);c&&(req.onreadystatechange=function(){basicahahDone(a,b)},req.open("GET",a,!0),req.send(""))}
function basicahahDone(a,b){4==req.readyState&&(200==req.status?document.getElementById(b).innerHTML=req.responseText:document.getElementById(b).innerHTML=" AHAH Error:\n"+req.status+"\n"+req.statusText)}function arraysearch(a,b){for(var c=0;c<b.length;c++)if(b[c]==a)return c;return-1}var tipobj=0,curtipel=null;
function tipshow(a,b){"object"!=typeof tipobj&&(tipobj=document.createElement("div"),tipobj.className="tips",tipobj.setAttribute("role","tooltip"),tipobj.id="hovertipsholder",document.getElementsByTagName("body")[0].appendChild(tipobj));curtipel=a;a.hasAttribute("data-tip")?tipobj.innerHTML=a.getAttribute("data-tip"):tipobj.innerHTML=b;tipobj.style.left="5px";tipobj.style.display="block";tipobj.setAttribute("aria-hidden","false");a.setAttribute("aria-describedby","hovertipsholder");"undefined"!=typeof usingASCIIMath&&
"undefined"!=typeof noMathRender&&usingASCIIMath&&!noMathRender&&rendermathnode(tipobj);var c=findPos(a);self.innerHeight?x=self.innerWidth:document.documentElement&&document.documentElement.clientHeight?x=document.documentElement.clientWidth:document.body&&(x=document.body.clientWidth);var d=0;"number"==typeof window.pageYOffset?d=window.pageXOffset:document.body&&(document.body.scrollLeft||document.body.scrollTop)?d=document.body.scrollLeft:document.documentElement&&(document.documentElement.scrollLeft||
document.documentElement.scrollTop)&&(d=document.documentElement.scrollLeft);x+=d;c[0]+tipobj.offsetWidth>x-10&&(c[0]=x-tipobj.offsetWidth-30);tipobj.style.left=c[0]+20+"px";tipobj.style.top=30>c[1]?c[1]+20+"px":c[1]-tipobj.offsetHeight+"px"}var popupwins=[];
function popupwindow(a,b,c,d,e){"fit"==d&&(d=window.height-80);c="width="+c+",height="+d+",status=0,resizable=1,directories=0,menubar=0";null!=e&&1==e&&(c+=",scrollbars=1");"undefined"==typeof popupwins[a]||popupwins[a].closed||popupwins[a].focus();b.match(/^http/)?popupwins[a]=window.open(b,a,c):(e=window.open("",a,c),e.document.write("<html><head><title>Popup</title></head><body>"),e.document.write(b),e.document.write("</body></html>"),e.document.close(),popupwins[a]=e)}
function tipout(a){tipobj.style.display="none";tipobj.setAttribute("aria-hidden","true");curtipel&&curtipel.removeAttribute("aria-describedby");curtipel=null}function findPos(a){var b=curtop=0;if(a.offsetParent){do b+=a.offsetLeft,curtop+=a.offsetTop,a.offsetParent&&(a.parentNode&&a.offsetParent!=a.parentNode?(b-=a.parentNode.scrollLeft,curtop-=a.parentNode.scrollTop):(b-=a.offsetParent.scrollLeft,curtop-=a.offsetParent.scrollTop));while(a=a.offsetParent)}return[b,curtop]}
function togglepic(a){a.getAttribute("src").match("userimg_sm")?a.setAttribute("src",a.getAttribute("src").replace("_sm","_")):a.setAttribute("src",a.getAttribute("src").replace("_","_sm"))}function addLoadEvent(a){var b=window.onload;window.onload="function"!=typeof window.onload?a:function(){b&&b();a()}}
function submitlimiter(a){var b=a.target;"submitted"==b.className?(alert("You have already submitted this page.  Please be patient while your submission is processed."),b.className="submitted2",a.preventDefault?a.preventDefault():a.returnValue=!1):"submitted2"==b.className?a.preventDefault?a.preventDefault():a.returnValue=!1:b.className="submitted"}
function setupFormLimiters(){for(var a=document.getElementsByTagName("form"),b=0;b<a.length;b++)if("function"!=typeof a[b].onsubmit&&"nolimit"!=a[b].className)$(a[b]).on("submit",submitlimiter)}addLoadEvent(setupFormLimiters);var GB_loaded=!1;
function GB_show(a,b,c,d){if(0==GB_loaded){var e=document.createElement("div");e.id="GB_overlay";e.onclick=GB_hide;document.getElementsByTagName("body")[0].appendChild(e);e=document.createElement("div");e.setAttribute("aria-role","dialog");e.setAttribute("aria-labelledby","GB_caption");e.setAttribute("tabindex",-1);e.id="GB_window";e.innerHTML='<div id="GB_caption"></div><div id="GB_loading">Loading...</div><div id="GB_frameholder" ></div>';document.getElementsByTagName("body")[0].appendChild(e);
GB_loaded=!0}document.getElementById("GB_frameholder").innerHTML='<iframe onload="GB_doneload()" id="GB_frame" src="'+b+'"></iframe>';jQuery("#GB_frameholder").isolatedScroll();b.match(/libtree/)?(document.getElementById("GB_caption").innerHTML='<span class="floatright"><input type="button" value="Use Libraries" onClick="document.getElementById(\'GB_frame\').contentWindow.setlib()" /> <a href="#" class="pointer" onclick="GB_hide();return false;" aria-label="Close">[X]</a>&nbsp;</span>Select Libraries<div class="clear"></div>',
a=self.innerHeight||de&&de.clientHeight||document.body.clientHeight):(document.getElementById("GB_caption").innerHTML='<span class="floatright"><a href="#" class="pointer" onclick="GB_hide();return false;" aria-label="Close">[X]</a></span>'+a,document.getElementById("GB_caption").onclick=GB_hide,a="auto"==d?self.innerHeight||de&&de.clientHeight||document.body.clientHeight:d);document.getElementById("GB_window").style.display="block";document.getElementById("GB_overlay").style.display="block";document.getElementById("GB_loading").style.display=
"block";b=$(document).width();c>b-20&&(c=b-20);document.getElementById("GB_window").style.width=c+"px";document.getElementById("GB_window").style.height=a-30+"px";document.getElementById("GB_frame").style.height=a-30-34+"px";document.getElementById("GB_window").focus();$(document).on("keydown.GB",function(a){27==a.keyCode&&GB_hide()})}function GB_doneload(){document.getElementById("GB_loading").style.display="none"}
function GB_hide(){document.getElementById("GB_window").style.display="none";document.getElementById("GB_overlay").style.display="none";$(document).off("keydown.GB")}function chkAllNone(a,b,c,d){a=document.getElementById(a);for(i=0;i<=a.elements.length;i++)try{if("all"==b&&"checkbox"==a.elements[i].type||a.elements[i].name==b)a.elements[i].checked=d&&a.elements[i].className==d?!c:c}catch(e){}return!1}var tinyMCEPreInit={base:imasroot+"/tinymce4"};
function initeditor(a,b,c,d,e){c=c||0;d=d||0;var f="";"exact"==a?f="#"+b.split(/,/).join(",#"):"textareas"==a?f="textarea."+b:"divs"==a?f="div."+b:"selector"==a&&(f=b);a={selector:f,inline:d,plugins:["lists advlist autolink attach image charmap anchor","searchreplace code link textcolor","media table paste asciimath asciisvg rollups"],menubar:!1,toolbar1:"myEdit myInsert styleselect | bold italic underline subscript superscript | forecolor backcolor | code | saveclose",toolbar2:" alignleft aligncenter alignright | bullist numlist outdent indent  | attach link unlink image | table | asciimath asciimathcharmap asciisvg",
extended_valid_elements:"iframe[src|width|height|name|align|allowfullscreen|frameborder],param[name|value],@[sscr]",content_css:imasroot+(1==c?"/assessment/mathtest.css,":"/imascore.css,")+imasroot+"/themes/"+coursetheme,AScgiloc:imasroot+"/filter/graph/svgimg.php",convert_urls:!1,file_picker_callback:filePickerCallBackFunc,file_browser_types:"file image",images_upload_url:imasroot+"/tinymce4/upload_handler.php",paste_data_images:!0,default_link_target:"_blank",browser_spellcheck:!0,branding:!1,resize:"both",
width:"100%",content_style:"body {background-color: #ffffff !important;}",table_class_list:[{title:"None",value:""},{title:"Gridded",value:"gridded"},{title:"Gridded Centered",value:"gridded centered"}],style_formats_merge:!0,style_formats:[{title:"Font Family",items:[{title:"Arial",inline:"span",styles:{"font-family":"arial"}},{title:"Book Antiqua",inline:"span",styles:{"font-family":"book antiqua"}},{title:"Comic Sans MS",inline:"span",styles:{"font-family":"comic sans ms,sans-serif"}},{title:"Courier New",
inline:"span",styles:{"font-family":"courier new,courier"}},{title:"Georgia",inline:"span",styles:{"font-family":"georgia,palatino"}},{title:"Helvetica",inline:"span",styles:{"font-family":"helvetica"}},{title:"Impact",inline:"span",styles:{"font-family":"impact,chicago"}},{title:"Open Sans",inline:"span",styles:{"font-family":"Open Sans"}},{title:"Symbol",inline:"span",styles:{"font-family":"symbol"}},{title:"Tahoma",inline:"span",styles:{"font-family":"tahoma"}},{title:"Terminal",inline:"span",
styles:{"font-family":"terminal,monaco"}},{title:"Times New Roman",inline:"span",styles:{"font-family":"times new roman,times"}},{title:"Verdana",inline:"span",styles:{"font-family":"Verdana"}}]},{title:"Font Size",items:[{title:"x-small",inline:"span",styles:{fontSize:"x-small","font-size":"x-small"}},{title:"small",inline:"span",styles:{fontSize:"small","font-size":"small"}},{title:"medium",inline:"span",styles:{fontSize:"medium","font-size":"medium"}},{title:"large",inline:"span",styles:{fontSize:"large",
"font-size":"large"}},{title:"x-large",inline:"span",styles:{fontSize:"x-large","font-size":"x-large"}},{title:"xx-large",inline:"span",styles:{fontSize:"xx-large","font-size":"xx-large"}}]}]};385>document.documentElement.clientWidth?(a.toolbar1="myEdit myInsert styleselect | bold italic underline | saveclose",a.toolbar2="bullist numlist outdent indent  | link image | asciimath asciisvg"):465>document.documentElement.clientWidth?(a.toolbar1="myEdit myInsert styleselect | bold italic underline forecolor | saveclose",
a.toolbar2="bullist numlist outdent indent  | link unlink image | asciimath asciisvg"):575>document.documentElement.clientWidth&&(a.toolbar1="myEdit myInsert styleselect | bold italic underline subscript superscript | forecolor | saveclose",a.toolbar2=" alignleft aligncenter | bullist numlist outdent indent  | link unlink image | asciimath asciimathcharmap asciisvg");e&&(a.setup=e);for(var h in tinymce.editors)tinymce.editors[h].remove();tinymce.init(a)}
function filePickerCallBack(a,b,c){b=imasroot+"/tinymce4/file_manager.php";switch(c.filetype){case "image":b+="?type=img";break;case "file":b+="?type=files"}tinyMCE.activeEditor.windowManager.open({file:b,title:"File Manager",width:350,height:450,resizable:"yes",inline:"yes",close_previous:"no"},{oninsert:function(b,c){a(b)}})}
function imascleanup(a,b){"get_from_editor"==a&&(b=b.replace(/\x3c!--([\s\S]*?)--\x3e|&lt;!--([\s\S]*?)--&gt;|<style>[\s\S]*?<\/style>/g,""),b=b.replace(/class="?Mso\w+"?/g,""),b=b.replace(/<p\s*>\s*<\/p>/gi,""),b=b.replace(/<script.*?\/script>/gi,""),b=b.replace(/<input[^>]*button[^>]*>/gi,""));return b}
function readCookie(a){a+="=";for(var b=document.cookie.split(";"),c=0;c<b.length;c++){for(var d=b[c];" "==d.charAt(0);)d=d.substring(1,d.length);if(0==d.indexOf(a))return unescape(d.substring(a.length,d.length))}return null}
function selectByDivID(a){var b=a.value;a=a.value.split(":")[0];for(var c=document.getElementsByTagName("div"),d=0;d<c.length;d++)c[d].className.match(a)&&(c[d].style.display=c[d].id==b?"block":"none");c=new Date;c.setDate(c.getDate()+365);document.cookie=a+"store="+escape(b)+";expires="+c.toGMTString()}
function setselectbycookie(){for(var a=document.getElementsByTagName("select"),b=0;b<a.length;b++)if(a[b].className.match("alts")){var c=a[b].className.replace(/alts/,"").replace(/\s/g,"");null!=(co=readCookie(c+"store"))&&(co=co.replace("store",""),a[b].value=co,selectByDivID(a[b]))}}addLoadEvent(setselectbycookie);var recordedunload=!1;
function recclick(a,b,c,d){if(0<cid){var e="",f;null!==(f=window.location.href.match(/showlinkedtext.*?&id=(\d+)/))&&0==recordedunload&&(e="&unloadinglinked="+f[1],recordedunload=!0);jQuery.ajax({type:"POST",url:imasroot+"/course/rectrack.php?cid="+cid,data:"type="+encodeURIComponent(a)+"&typeid="+encodeURIComponent(b)+"&info="+encodeURIComponent(c+"::"+d)+e})}}
function setuptracklinks(a,b){jQuery(b).attr("data-base")&&jQuery(b).click(function(a){var c=jQuery(this).attr("data-base").split("-");recclick(c[0],c[1],jQuery(this).attr("href"),jQuery(this).text());if("undefined"==typeof jQuery(b).attr("target"))return a.preventDefault(),setTimeout('window.location.href = "'+jQuery(this).attr("href")+'"',100),!1}).mousedown(function(a){3==a.which&&(a=jQuery(this).attr("data-base").split("-"),recclick(a[0],a[1],jQuery(this).attr("href"),jQuery(this).text()))})}
var videoembedcounter=0;
function togglevideoembed(){var a=this.id.substr(13),b=jQuery("#videoiframe"+a);if(0<b.length)"none"==b.css("display")?(b.show(),b.parent(".fluid-width-video-wrapper").show(),jQuery(this).text(" [-]"),jQuery(this).attr("title",_("Hide video"))):(b.hide(),b.get(0).contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}',"*"),b.parent(".fluid-width-video-wrapper").hide(),jQuery(this).text(" [+]"),jQuery(this).attr("title",_("Watch video here")));else{b=jQuery(this).prev().attr("href");var c=
"?";if(b.match(/youtube\.com/))if(-1<b.indexOf("playlist?list=")){var d=b.split("list=")[1].split(/[#&]/)[0];var e="www.youtube.com/embed/videoseries?list=";c="&"}else d=b.split("v=")[1].split(/[#&]/)[0],e="www.youtube.com/embed/";else b.match(/youtu\.be/)?(d=b.split(".be/")[1].split(/[#&]/)[0],e="www.youtube.com/embed/"):b.match(/vimeo/)&&(d=b.split(".com/")[1].split(/[#&]/)[0],e="player.vimeo.com/video/");var f=b.match(/.*\Wt=((\d+)m)?((\d+)s)?.*/);null==f?(c+="rel=0",f=b.match(/.*start=(\d+)/),
null!=f&&(c+="&start="+f[1])):c=c+"rel=0&start="+((f[2]?60*f[2]:0)+(f[4]?1*f[4]:0));f=b.match(/.*end=(\d+)/);null!=f&&(c+="&end="+f[1]);jQuery("<iframe/>",{id:"videoiframe"+a,width:640,height:400,src:location.protocol+"//"+e+d+(c+"&enablejsapi=1"),frameborder:0,allowfullscreen:1}).insertAfter(jQuery(this));jQuery(this).parent().fitVids();jQuery("<br/>").insertAfter(jQuery(this));jQuery(this).text(" [-]");jQuery(this).attr("title",_("Hide video"));jQuery(this).prev().attr("data-base")&&(e=jQuery(this).prev().attr("data-base").split("-"),
recclick(e[0],e[1],b,jQuery(this).prev().text()))}}function setupvideoembeds(a,b){jQuery("<span/>",{text:" [+]",title:_("Watch video here"),id:"videoembedbtn"+videoembedcounter,click:togglevideoembed,"class":"videoembedbtn"}).insertAfter(b);videoembedcounter++}function addNoopener(a,b){!b.rel&&b.target&&b.host!==window.location.host&&b.setAttribute("rel","noopener noreferrer")}
function addmultiselect(a,b){var c=jQuery(a).parent(),d=jQuery("#"+b).val(),e=jQuery("#"+b+" option[value="+d+"]").prop("disabled",!0).html();"null"!=d&&c.append('<div class="multiselitem"><span class="right"><a href="#" onclick="removemultiselect(this);return false;">Remove</a></span><input type="hidden" name="'+b+'[]" value="'+d+'"/>'+e+"</div>");jQuery("#"+b).val("null")}
function removemultiselect(a){a=jQuery(a).parent().parent();var b=a.find("input").val();a.parent().find("option[value="+b+"]").prop("disabled",!1);a.remove()}function hidefromcourselist(a,b,c){confirm("Are you SURE you want to hide this course from your course list?")&&jQuery.ajax({type:"GET",url:imasroot+"/admin/hidefromcourselist.php?cid="+b+"&type="+c}).done(function(b){"OK"==b&&(jQuery(a).closest("ul.courselist > li").slideUp(),jQuery("#unhidelink"+c).show())});return!1}
function rotateimg(a){var b=$(a).data("rotation")?($(a).data("rotation")+90)%360:90;$(a).data("rotation",b).css({transform:"rotate("+b+"deg)"});90==b%180?(b=($(a).width()-$(a).height())/2,0<b&&$(a).parent().css({"padding-top":b,"padding-bottom":b})):$(a).parent().css({"padding-top":0,"padding-bottom":0})}
jQuery(document).ready(function(a){a(window).on("message",function(b){if(b.originalEvent.data.match(/lti\.frameResize/))for(var c=JSON.parse(b.originalEvent.data),d=document.getElementsByTagName("iframe"),e=0;e<d.length;e++)if(d[e].contentWindow===b.originalEvent.source){a(d[e]).height(c.height);break}})});
jQuery(document).ready(function(a){a("a").each(setuptracklinks).each(addNoopener);a('a[href*="youtu"]').each(setupvideoembeds);a('a[href*="vimeo"]').each(setupvideoembeds);a("body").fitVids();a('a[target="_blank"]').each(function(){this.href.match(/youtu/)||this.href.match(/vimeo/)||a(this).append(' <img src="'+imasroot+'/img/extlink.png" alt="External link"/>')})});
jQuery.fn.isolatedScroll=function(){this.bind("mousewheel DOMMouseScroll",function(a){var b=a.wheelDelta||a.originalEvent&&a.originalEvent.wheelDelta||-a.detail,c=0<=this.scrollTop+jQuery(this).outerHeight()-this.scrollHeight,d=0>=this.scrollTop;(0>b&&c||0<b&&d)&&a.preventDefault()});return this};
jQuery(document).ready(function(a){for(var b=a(".fixedonscroll"),c=[],d=0;d<b.length;d++)c[d]=a(b[d]).offset().top,a(b[d]).height()>a(window).height()&&(c[d]=-1);0<b.length&&"left"==a(b[0]).css("float")&&a(window).scroll(function(){for(var d=a(window).scrollTop(),f=0;f<b.length;f++)d>c[f]&&0<c[f]?a(b[f]).css("position","fixed").css("top","5px"):a(b[f]).css("position","static")})});
function _(a){var b="undefined"!=typeof i18njs&&i18njs[a]?i18njs[a]:a;if(1<arguments.length)for(var c=1;c<arguments.length;c++)b=b.replace("$"+c,arguments[c]);return b}
(function(a){a.fn.fitVids=function(){return this.each(function(){a(this).find("iframe[src*='player.vimeo.com'],iframe[src*='youtube.com'],iframe[src*='youtube-nocookie.com']").each(function(){var b=a(this);if(0<b.closest(".textsegment").length)return!0;b.parentsUntil(".intro","table").each(function(){a(this).css("width","100%")});var c=b.attr("height")&&!isNaN(parseInt(b.attr("height"),10))?parseInt(b.attr("height"),10):b.height(),d=isNaN(parseInt(b.attr("width"),10))?b.width():parseInt(b.attr("width"),
10),c=c/d;b.attr("id")||b.attr("id","fitvid"+Math.floor(999999*Math.random()));b.wrap('<div class="fluid-width-video-wrapper"></div>').parent(".fluid-width-video-wrapper").css("padding-top",100*c+"%").wrap('<div class="video-wrapper-wrapper"></div>').parent(".video-wrapper-wrapper").css("max-width",d+"px");b.removeAttr("height").removeAttr("width").css("height","").css("width","")})})}})(window.jQuery||window.Zepto);
function setAltSelectors(a,b){console.log("looking for "+a);$(".alts."+a).parents(".altWrap").find(".altContentOn").removeClass("altContentOn").addClass("altContentOff");$(".alts."+a).parents(".altWrap").find("."+b).addClass("altContentOn").removeClass("altContentOff");$("select.alts."+a).val(a+":"+b);var c=new Date;c.setDate(c.getDate()+365);document.cookie="alt_store_"+a+"="+escape(b)+";expires="+c.toGMTString()+";path=/"}
jQuery(document).ready(function(a){a(".alts").on("change",function(){var a=this.value.split(":");1<a.length&&setAltSelectors(a[0],a[1])}).each(function(b,c){var d=c.value.split(":");null!=(co=readCookie("alt_store_"+d[0]))?setAltSelectors(d[0],co):a(c).hasClass("setDefault")&&setAltSelectors(d[0],d[1])});a(document).on("keydown",function(b){8!==b.which||a(b.target).is("input[type='text']:not([readonly]),input:not([type]):not([readonly]),input[type='password']:not([readonly]), textarea, [contenteditable='true']")||
b.preventDefault()});a("div.breadcrumb").attr("role","navigation").attr("aria-label",_("Navigation breadcrumbs"));a("div.cpmid,div.cp").attr("role","group").attr("aria-label",_("Control link group"));a("#centercontent").length&&(a("#centercontent").attr("role","main"),a(".midwrapper").removeAttr("role"))});
jQuery(document).ready(function(a){function b(b){var c=a("#headermobilemenulist");"true"==c.attr("aria-hidden")?(a("#headermobilemenulist").slideDown(50,function(){a("#headermobilemenulist").addClass("menuexpanded").removeAttr("style");c.attr("aria-hidden",!1);a("#topnavmenu").attr("aria-expanded",!0)}),a("#navlist").slideDown(100,function(){a("#navlist").addClass("menuexpanded").removeAttr("style")})):(a("#navlist").slideUp(100,function(){a("#navlist").removeClass("menuexpanded").removeAttr("style")}),
a("#headermobilemenulist").slideUp(50,function(){a("#headermobilemenulist").removeClass("menuexpanded").removeAttr("style");c.attr("aria-hidden",!0);a("#topnavmenu").attr("aria-expanded",!1)}));b.preventDefault()}a("#topnavmenu").on("click",b).on("keydown",function(a){13!==a.which&&32!=a.which||b(a)})});
+function(a){function b(b){var c=b.attr("data-target");c||(c=(c=b.attr("href"))&&/#[A-Za-z]/.test(c)&&c.replace(/.*(?=#[^\s]*$)/,""));return(c=c&&a(c))&&c.length?c:b.parent()}function c(c){c&&3===c.which||(a(".dropdown-backdrop").remove(),a('[data-toggle="dropdown"]').each(function(){var d=a(this),f=b(d),e={relatedTarget:this};!f.hasClass("open")||c&&"click"==c.type&&/input|textarea/i.test(c.target.tagName)&&a.contains(f[0],c.target)||(f.trigger(c=a.Event("hide.bs.dropdown",e)),c.isDefaultPrevented()||
(d.attr("aria-expanded","false"),f.removeClass("open").trigger("hidden.bs.dropdown",e)))}))}var d=function(b){a(b).on("click.bs.dropdown",this.toggle)};d.VERSION="3.3.5";d.prototype.toggle=function(d){var f=a(this);if(!f.is(".disabled, :disabled")){var e=b(f);d=e.hasClass("open");c();if(!d){if("ontouchstart"in document.documentElement&&!e.closest(".navbar-nav").length)a(document.createElement("div")).addClass("dropdown-backdrop").insertAfter(a(this)).on("click",c);var g={relatedTarget:this};e.trigger(d=
a.Event("show.bs.dropdown",g));if(d.isDefaultPrevented())return;f.trigger("focus").attr("aria-expanded","true");e.toggleClass("open").trigger("shown.bs.dropdown",g)}return!1}};d.prototype.keydown=function(c){if(/(38|40|27|32)/.test(c.which)&&!/input|textarea/i.test(c.target.tagName)){var d=a(this);c.preventDefault();c.stopPropagation();if(!d.is(".disabled, :disabled")){var e=b(d),f=e.hasClass("open");if(!f&&27!=c.which||f&&27==c.which)return 27==c.which&&e.find('[data-toggle="dropdown"]').trigger("focus"),
d.trigger("click");d=e.find(".dropdown-menu li:not(.disabled):visible a");d.length&&(e=d.index(c.target),38==c.which&&0<e&&e--,40==c.which&&e<d.length-1&&e++,~e||(e=0),d.eq(e).trigger("focus"))}}};var e=a.fn.dropdown;a.fn.dropdown=function(b){return this.each(function(){var c=a(this),e=c.data("bs.dropdown");e||c.data("bs.dropdown",e=new d(this));"string"==typeof b&&e[b].call(c)})};a.fn.dropdown.Constructor=d;a.fn.dropdown.noConflict=function(){a.fn.dropdown=e;return this};a(document).on("click.bs.dropdown.data-api",
c).on("click.bs.dropdown.data-api",".dropdown form",function(a){a.stopPropagation()}).on("click.bs.dropdown.data-api",'[data-toggle="dropdown"]',d.prototype.toggle).on("keydown.bs.dropdown.data-api",'[data-toggle="dropdown"]',d.prototype.keydown).on("keydown.bs.dropdown.data-api",".dropdown-menu",d.prototype.keydown)}(jQuery);

var pi=Math.PI,ln=Math.log,e=Math.E,arcsin=Math.asin,arccos=Math.acos,arctan=Math.atan,sec=function(a){return 1/Math.cos(a)},csc=function(a){return 1/Math.sin(a)},cot=function(a){return 1/Math.tan(a)},arcsec=function(a){return arccos(1/a)},arccsc=function(a){return arcsin(1/a)},arccot=function(a){return arctan(1/a)},sinh=function(a){return(Math.exp(a)-Math.exp(-a))/2},cosh=function(a){return(Math.exp(a)+Math.exp(-a))/2},tanh=function(a){return(Math.exp(a)-Math.exp(-a))/(Math.exp(a)+Math.exp(-a))},
sech=function(a){return 1/cosh(a)},csch=function(a){return 1/sinh(a)},coth=function(a){return 1/tanh(a)},arcsinh=function(a){return ln(a+Math.sqrt(a*a+1))},arccosh=function(a){return ln(a+Math.sqrt(a*a-1))},arctanh=function(a){return ln((1+a)/(1-a))/2},sech=function(a){return 1/cosh(a)},csch=function(a){return 1/sinh(a)},coth=function(a){return 1/tanh(a)},arcsech=function(a){return arccosh(1/a)},arccsch=function(a){return arcsinh(1/a)},arccoth=function(a){return arctanh(1/a)},sign=function(a){return 0==
a?0:0>a?-1:1},logten=function(a){return Math.LOG10E*Math.log(a)},sinn=function(a,c){return Math.pow(Math.sin(c),a)},cosn=function(a,c){return Math.pow(Math.cos(c),a)},tann=function(a,c){return Math.pow(Math.tan(c),a)},cscn=function(a,c){return 1/Math.pow(Math.sin(c),a)},secn=function(a,c){return 1/Math.pow(Math.cos(c),a)},cotn=function(a,c){return 1/Math.pow(Math.tan(c),a)};function factorial(a,c){null==c&&(c=1);for(var h=a-c;0<h;h-=c)a*=h;return 0>a?NaN:0==a?1:a}
function C(a,c){for(var h=1,f=0;f<c;f++)h*=(a-f)/(c-f);return h}function matchtolower(a){return a.toLowerCase()}function nthroot(a,c){return safepow(c,1/a)}function nthlogten(a,c){return Math.log(c)/Math.log(a)}var funcstoindexarr="sinh cosh tanh sech csch coth sqrt ln log sin cos tan sec csc cot abs root arcsin arccos arctan arcsec arccsc arccot arcsinh arccosh arctanh arcsech arccsch arccoth".split(" ");
function functoindex(a){for(var c=0;c<funcstoindexarr.length;c++)if(funcstoindexarr[c]==a)return"@"+c+"@"}function indextofunc(a,c){return funcstoindexarr[c]}function safepow(a,c){if(0>a&&Math.floor(c)!=c){for(var h=3;50>h;h+=2)if(1E-6>Math.abs(Math.round(h*c)-h*c))return 0==Math.round(h*c)%2?Math.pow(Math.abs(a),c):-1*Math.pow(Math.abs(a),c);return Math.sqrt(-1)}return Math.pow(a,c)}
function mathjs(a,c){a=a.replace("[","(");a=a.replace("]",")");a=a.replace(/root\s*(\d+)/,"root($1)");a=a.replace(/arc(sin|cos|tan|sec|csc|cot|sinh|cosh|tanh|sech|csch|coth)/gi,"$1^-1");a=a.replace(/(Sin|Cos|Tan|Sec|Csc|Cot|Arc|Abs|Log|Ln|Sqrt)/gi,matchtolower);if(null!=c){var h=c.split("|");a=a.replace(/(sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs|root)/g,functoindex);var f=RegExp("(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs|root)[(]","g");a=a.replace(f,"$1#(");f=new RegExp("("+
c+")("+c+")$","g");a=a.replace(f,"($1)($2)");f=new RegExp("("+c+")(a#|sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs|root|pi)","g");a=a.replace(f,"($1)$2");f=new RegExp("("+c+")("+c+")([^a-df-zA-Z#])","g");a=a.replace(f,"($1)($2)$3");f=new RegExp("([^a-df-zA-Z#])("+c+")([^a-df-zA-Z#])","g");a=a.replace(f,"$1($2)$3");f=new RegExp("([^a-df-zA-Z#(])("+c+")([^a-df-zA-Z#)])","g");a=a.replace(f,"$1($2)$3");f=new RegExp("^("+c+")([^a-df-zA-Z])","g");a=a.replace(f,"($1)$2");f=new RegExp("([^a-df-zA-Z])("+c+")$",
"g");a=a.replace(f,"$1($2)");a=a.replace(new RegExp("\\(("+c+")\\)","g"),function(a,b){for(var c=0;c<h.length;c++)if(h[c]==b)return"(@v"+c+"@)"});a=a.replace(/@(\d+)@/g,indextofunc)}a=a.replace(/([0-9])\s+([0-9])/g,"$1*$2");a=a.replace(/#/g,"");a=a.replace(/\s/g,"");a=a.replace(/log_([a-zA-Z\d\.]+)\(/g,"nthlog($1,");a=a.replace(/log_\(([a-zA-Z\/\d\.]+)\)\(/g,"nthlog($1,");a=a.replace(/log/g,"logten");-1!=a.indexOf("^-1")&&(a=a.replace(/sin\^-1/g,"arcsin"),a=a.replace(/cos\^-1/g,"arccos"),a=a.replace(/tan\^-1/g,
"arctan"),a=a.replace(/sec\^-1/g,"arcsec"),a=a.replace(/csc\^-1/g,"arccsc"),a=a.replace(/cot\^-1/g,"arccot"),a=a.replace(/sinh\^-1/g,"arcsinh"),a=a.replace(/cosh\^-1/g,"arccosh"),a=a.replace(/tanh\^-1/g,"arctanh"),a=a.replace(/sech\^-1/g,"arcsech"),a=a.replace(/csch\^-1/g,"arccsch"),a=a.replace(/coth\^-1/g,"arccoth"));a=a.replace(/(sin|cos|tan|sec|csc|cot)\^(\d+)\(/g,"$1n($2,");a=a.replace(/root\((\d+)\)\(/g,"nthroot($1,");a=a.replace(/([0-9])E([\-0-9])/g,"$1(EE)$2");a=a.replace(/^e$/g,"(E)");a=a.replace(/pi/g,
"(pi)");a=a.replace(/@v(\d+)@/g,function(a,b){return h[b]});a=a.replace(/^e([^a-zA-Z])/g,"(E)$1");a=a.replace(/([^a-zA-Z])e$/g,"$1(E)");a=a.replace(/([^a-zA-Z])e(?=[^a-zA-Z])/g,"$1(E)");a=a.replace(/([0-9])([\(a-zA-Z])/g,"$1*$2");a=a.replace(/(!)([0-9\(])/g,"$1*$2");a=a.replace(/([0-9])\*\(EE\)([\-0-9])/,"$1e$2");a=a.replace(/\)([\(0-9a-zA-Z]|\.\d+)/g,")*$1");for(var d,g,b,k;-1!=(f=a.indexOf("^"));){if(0==f)return"Error: missing argument";d=f-1;b=a.charAt(d);if("0"<=b&&"9">=b){for(d--;0<=d&&"0"<=
(b=a.charAt(d))&&"9">=b;)d--;if("."==b)for(d--;0<=d&&"0"<=(b=a.charAt(d))&&"9">=b;)d--}else if(")"==b){k=1;for(d--;0<=d&&0<k;)b=a.charAt(d),"("==b?k--:")"==b&&k++,d--;for(;0<=d&&("a"<=(b=a.charAt(d))&&"z">=b||"A"<=b&&"Z">=b);)d--}else if("a"<=b&&"z">=b||"A"<=b&&"Z">=b)for(d--;0<=d&&("a"<=(b=a.charAt(d))&&"z">=b||"A"<=b&&"Z">=b);)d--;else return"Error: incorrect syntax in "+a+" at position "+d;if(f==a.length-1)return"Error: missing argument";g=f+1;b=a.charAt(g);nch=a.charAt(g+1);if("0"<=b&&"9">=b||
"-"==b&&"("!=nch||"."==b){for(g++;g<a.length&&"0"<=(b=a.charAt(g))&&"9">=b;)g++;if("."==b)for(g++;g<a.length&&"0"<=(b=a.charAt(g))&&"9">=b;)g++}else if("("==b||"-"==b&&"("==nch)for("-"==b&&g++,k=1,g++;g<a.length&&0<k;)b=a.charAt(g),"("==b?k++:")"==b&&k--,g++;else if("a"<=b&&"z">=b||"A"<=b&&"Z">=b){for(g++;g<a.length&&("a"<=(b=a.charAt(g))&&"z">=b||"A"<=b&&"Z">=b);)g++;if("("==b&&a.slice(f+1,g).match(/^(sin|cos|tan|sec|csc|cot|logten|log|ln|exp|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|sqrt|abs|nthroot)$/))for(k=
1,g++;g<a.length&&0<k;)b=a.charAt(g),"("==b?k++:")"==b&&k--,g++}else return"Error: incorrect syntax in "+a+" at position "+g;a=a.slice(0,d+1)+"safepow("+a.slice(d+1,f)+","+a.slice(f+1,g)+")"+a.slice(g)}for(;-1!=(f=a.indexOf("!"));){if(0==f)return"Error: missing argument";d=f-1;b=a.charAt(d);if("0"<=b&&"9">=b){for(d--;0<=d&&"0"<=(b=a.charAt(d))&&"9">=b;)d--;if("."==b)for(d--;0<=d&&"0"<=(b=a.charAt(d))&&"9">=b;)d--}else if(")"==b){k=1;for(d--;0<=d&&0<k;)b=a.charAt(d),"("==b?k--:")"==b&&k++,d--;for(;0<=
d&&("a"<=(b=a.charAt(d))&&"z">=b||"A"<=b&&"Z">=b);)d--}else if("a"<=b&&"z">=b||"A"<=b&&"Z">=b)for(d--;0<=d&&("a"<=(b=a.charAt(d))&&"z">=b||"A"<=b&&"Z">=b);)d--;else return"Error: incorrect syntax in "+a+" at position "+d;a=a.slice(0,d+1)+"factorial("+a.slice(d+1,f)+")"+a.slice(f+1)}return a};



function checkComplete(b){if("undefined"!=typeof tinyMCE)try{tinyMCE.triggerSave()}catch(f){}if(!b.elements)return!0;for(var c=0;c<b.elements.length;c++){var a=b.elements[c];if("undefined"!=typeof a.type&&"undefined"!=typeof a.name&&""!=a.name&&!$(a).is(":not(:visible)"))if("text"==a.type||"textarea"==a.type||"password"==a.type||"file"==a.type){if(""==a.value&&0==$("#qs"+a.id.substr(2)+"-d:checked,#qs"+a.id.substr(2)+"-i:checked").length)return!1}else if(-1!=a.type.indexOf("select")){if(-1==a.selectedIndex)return!1}else if("radio"==
a.type){var d=b[a.name],e=!1;if(d.length)for(a=0;a<d.length&&!(e=d[a].checked);a++);else e=a.checked;if(!e)return!1}}return!0}function confirmSubmit(b){return checkComplete(b)?!0:confirm("Not all question parts have been answered.  Are you sure you want to submit this question?")}
function confirmSubmit2(b){return checkComplete(b)?!0:confirm("Not all questions have been answered completely.  If you are saving your answers for later, this is fine.  If you are submitting for grading, are you sure you want to submit now?")};



var ehcurel=null,ehclosetimer=0,ehddclosetimer=0,curehdd=null,eecurel=null;function showeh(a){if(null==eecurel){unhideeh(0);var c=document.getElementById(a),b=document.getElementById("eh");a!=ehcurel?(ehcurel=a,a=findPos(c),b.style.display="block",b.style.left=a[0]+"px",b.style.top=a[1]+c.offsetHeight+"px"):(b.style.display="none",ehcurel=null);c.focus()}}
function reshrinkeh(a){null==eecurel&&a==ehcurel&&(document.getElementById("ehdd").style.display="block",document.getElementById("eh").style.display="none",ehcurel=null,curehdd=a,unhideeh(0))}function unhideeh(a){ehcancelclosetimer()}function hideeh(a){null==ehcurel?ehddclosetimer=window.setTimeout(function(){curehdd=null;document.getElementById("ehdd").style.display="none"},250):ehclosetimer=window.setTimeout(reallyhideeh,250)}
function reallyhideeh(){document.getElementById("eh").style.display="none";ehcurel=null}function ehcancelclosetimer(){ehclosetimer&&(window.clearTimeout(ehclosetimer),ehclosetimer=null)}
function showehdd(a,c,b){if((null==eecurel||eecurel!=a)&&null!=document.getElementById("tips"+b)){ehddclosetimer&&a!=curehdd&&(window.clearTimeout(ehddclosetimer),ehddclosetimer=null);if(a!=ehcurel){var d=document.getElementById("ehdd"),e=document.getElementById(a),f=findPos(e);document.getElementById("ehddtext").innerHTML=c;document.getElementById("eh").innerHTML=document.getElementById("tips"+b).innerHTML;d.style.display="block";d.style.left=f[0]+"px";d.style.top=f[1]+e.offsetHeight+"px"}curehdd=
a}}function updateehpos(){if(curehdd||ehcurel){var a=document.getElementById("eh"),c=document.getElementById("ehdd"),b=document.getElementById(curehdd||ehcurel),d=findPos(b);a.style.left=d[0]+"px";a.style.top=d[1]+b.offsetHeight+"px";c.style.left=d[0]+"px";c.style.top=d[1]+b.offsetHeight+"px"}};

