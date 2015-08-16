/*! EasyRecipe Plus 3.3.3077 Copyright (c) 2015 BoxHill LLC */
window.EASYRECIPE=window.EASYRECIPE||{},function(e){function t(){var t,i,n,a,l,r,o,u,h,p,m,f,v=d.selection.getNode();if(t=e(".ERGPAlignment input:radio[name=rbERGPAlign]:checked").val(),i=e(".ERGPSize input:radio[name=rbERGPSize]:checked").val(),h=e.trim(w.val()),p=e.trim(E.val()),m=e.trim(k.val()),f=""===m?"none":"custom",L.isEntryDialog){for(i in y.meta.sizes)y.meta.sizes.hasOwnProperty(i)&&(y.meta.sizes[i].url=y.baseurl+y.meta.sizes[i].file);o={sizes:y.meta.sizes},u={size:i,align:t,link:f,linkUrl:m},L.insertUploadedImage(o,u)}else if("full"===i?(n=y.meta.width,a=y.meta.height,l=y.meta.file):(n=y.meta.sizes[i].width,a=y.meta.sizes[i].height,l=y.meta.sizes[i].file),r="aligncenter"===t?'<p style="text-align:center">':"",r+='<img class="'+t+" size-"+i+" wp-image-"+y.id+'"',r+=' title="'+h+'"',r+=' alt="'+p+'"',r+=' width="'+n+'"',r+=' height="'+a+'"',r+=' src="'+y.baseurl+l+'"',r+=" />",r+="aligncenter"===t?"</p>":"","#document"===v.nodeName&&(v=e("body",c)[0]),"BODY"===v.nodeName.toUpperCase())e(v).hasClass("mceContentBody")||(v=e("body",c)[0]),e(v).append("&nbsp;"+r);else{for(;v.parentNode&&"BODY"!==v.parentNode.nodeName.toUpperCase();)v=v.parentNode;v.parentNode?"DIV"===v.nodeName.toUpperCase()||"SPAN"===v.nodeName.toUpperCase()?e(v,c).after(r):e(v,c).before(r):(v=e("body",c)[0],e(v).append(r))}s.dialog("close")}function i(t,i,n){return h=!1,typeof tinyMCE!==A&&tinyMCE.activeEditor&&!tinyMCE.activeEditor.isHidden()&&(h=!0),h?(d=tinyMCE.activeEditor,void(c=i.editorId===u?e("#"+u+"_ifr").contents():e("#mce_fullscreen_ifr").contents())):void alert("You must use the Visual Editor to add or update an EasyRecipe")}function n(){var t="html5,flash,silverlight,html4";v.hide(),m.hide(),l.show(),f.show(),g.text(""),w.val(""),E.val(""),k.val(""),s.dialog(_),D.hide(),e("#ERGPplupload").html(""),plupload.VERSION&&plupload.ua&&"1.5.4"==plupload.VERSION&&plupload.ua.gecko&&(t="flash,silverlight,html4"),Y=new plupload.Uploader({runtimes:t,browse_button:"btnERGPSelect",max_file_size:"10mb",container:"easyrecipeUpload",url:ajaxurl,flash_swf_url:L.wpurl+"/wp-includes/js/plupload/plupload.flash.swf",silverlight_xap_url:L.wpurl+"/wp-includes/js/plupload/plupload.silverlight.xap",multipart_params:{postID:p,action:"easyrecipeUpload"},filters:[{title:"Image files",extensions:"jpg,gif,png"}]}),Y.init(),Y.bind("FilesAdded",function(e,t){g.text("File: "+t[0].name),S.text(0),r.show(),l.hide(),v.show(),Y.start()}),Y.bind("UploadProgress",function(e,t){S.text(t.percent),r.progressbar("value",t.percent),100===t.percent&&D.show()}),Y.bind("Error",function(e,t){e.refresh()}),Y.bind("FileUploaded",function(t,i,n){var a,s,l,r,o,c;y=e.parseJSON(n.response),f.hide(),m.show(),y.meta.sizes.thumbnail?(a=y.meta.sizes.thumbnail.file,r=y.meta.sizes.thumbnail.width,o=y.meta.sizes.thumbnail.height):(a=y.meta.file,o=y.meta.height,r=y.meta.width),q.attr("src",y.baseurl+a),o=130*o/r,q.css("top",Math.floor((140-o)/2)),y.postID&&e('.ERGuestPost form input[name="postID"]').val(y.postID);for(s in O)O.hasOwnProperty(s)&&(l=O[s],y.meta.sizes[s]?(l.attr(F,!1),l.next("span").text(" ("+y.meta.sizes[s].width+" x "+y.meta.sizes[s].height+")")):(l.attr(F,!0),l.next("span").text("")));O.medium.attr(F)?b.attr(H,!0):O.medium.attr(H,!0),x.text(" ("+y.meta.width+" x "+y.meta.height+")"),c=y.meta,e("#divERGPFIName").text(c.file),e("#divERGPFIDim").text(c.width+" x "+c.height),e("#divERGPFISize").text(c.filesize),D.hide()}),Y.refresh()}function a(){var t,i,n,a=!1;t=e("#spnERGPNameErr"),i=e("#spnERGPURLErr"),n=e("#spnERGPEmailErr"),t.text(""),i.text(""),i.text(""),""===e.trim(G.val())&&(t.text("You must enter your name"),a=!0),""===e.trim(z.val())?(n.text("You must enter your email"),a=!0):B.test(e.trim(z.val()))||(n.text("You must enter a valid email address"),a=!0),""===e.trim(I.val())&&(i.text("You must enter your website URL"),a=!0),a||(P.hide(),C.show())}var s,l,r,o,c,u,d,h,p,m,f,v,g,y,w,E,k,b,x,R,P,C,G,z,I,T,N,U,L,S,q,D,_="open",A="undefined",j="click",Y=null,O={},F="disabled",H="checked",B=/[a-z0-9!#$%&'*+\/=?\^_`{|}~\-]+(?:\.[a-z0-9!#$%&'*+\/=?\^_`{|}~\-]+)*@(?:[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?/i;e(function(){var c;L=EASYRECIPE,s=e("#easyrecipeUpload"),R=e("#easyrecipeLinks"),s.dialog({autoOpen:!1,width:500,height:600,title:"Upload an image",modal:!0,appendTo:L.dialogs,dialogClass:"easyrecipeUpload",close:function(){e(".easyrecipeUpload").filter(function(){return""===e(this).text()}).remove(),Y&&(Y.destroy(),Y=null)},open:function(){l.show(),r.progressbar("value",0)}}),e('input[type="button"]',R).button(),R.dialog({autoOpen:!1,width:480,title:"Insert/edit link",modal:!0,dialogClass:"easyrecipeLinks",appendTo:L.dialogs,close:function(){e(".easyrecipeLinks").filter(function(){return""===e(this).text()}).remove()},open:function(){}}),m=e("#divERGPUploaded"),f=e("#divERGPUploadContainer"),v=e("#divERGPUploading"),g=e("#divERGPFile"),w=e("#inpERGPTitle"),E=e("#inpERGPAltText"),k=e("#inpERGPLinkURL"),l=e("#btnERGPSelect").button(),U=e("#cbERGPLTarget"),N=e("#fldERGPLTitle"),T=e("#fldERGPLURL"),C=e("#divERGPPost"),P=e("#divERGPData"),G=e("#fldERGPName"),z=e("#fldERGPEmail"),I=e("#fldERGPURL"),O.thumbnail=e('.ERGPSize input[value="thumbnail"]'),O.medium=e('.ERGPSize input[value="medium"]'),O.large=e('.ERGPSize input[value="large"]'),b=e('.ERGPSize input[value="full"]'),x=b.next("span"),o=e("#btnERGPInsert").button().on(j,t),r=e("#divERGPProgress").progressbar().hide(),e(window).bind("guestpostloaded",i),e(".wp-media-buttons").hide(),p=e("#ERGPpostID").val(),u=e(".wp-editor-wrap").attr("id").replace(/^wp-(.*)-wrap$/gi,"$1"),e(".ERGuestPost .ERGPButton").button(),e("#btnERGPContinue").on(j,a),q=e("#imgERGPThumb"),S=e("#spnERGComplete"),D=e("#divERGPSpinner"),L.isGuest=!0,L.doUpload=n,c=e("#inpERGPAuthor").val(),c&&(L.author='"'+c+'"')})}(jQuery),/*! EasyRecipe Plus 3.3.3077 Copyright (c) 2015 BoxHill LLC */
window.EASYRECIPE=window.EASYRECIPE||{},function(e){"use strict";var t,i,n,a,s,l,r={},o={},c="ontouchend"in document;t={timeToTriggerRiver:150,minRiverAJAXDuration:200,riverBottomThreshold:5,keySensitivity:100,lastSearch:"",textarea:"",rename:function(i){i.each(function(){var i,n=e(this),a=this,s=n.children();s.length>0&&t.rename(s),a.id&&(i=a.id.match(/^wp-link(.*)$/),a.id=null!=i?"er-link"+i[1]:"er-"+a.id)})},init:function(){function a(){var t=e.trim(r.url.val());t&&s!==t&&!/^(?:[a-z]+:|#|\?|\.|\/)/.test(t)&&(r.url.val("http://"+t),s=t)}var c,u,d;l=EASYRECIPE,l.erLink=t,d=e("#wp-link-wrap"),c=d.clone(),t.rename(c),c.find("#search-results").attr("id","#er-search-results"),c.find("#most-recent-results").attr("id","#er-most-recent-results"),c.find("#query-notice-message").attr("id","#er-query-notice-message"),d.after(c),u=e("#wp-link-backdrop").clone(),u[0].id="er-link-backdrop",c.before(u),e("#easyrecipeUI").find(".ui-dialog.easyrecipeEntry").on("focusin",function(){r.url.focus()}),r.wrap=c,r.dialog=e("#er-link"),r.backdrop=u,r.submit=e("#er-link-submit"),r.close=e("#er-link-close"),r.text=e("#er-link-text"),r.url=e("#er-link-url"),r.nonce=e("#_ajax_linking_nonce"),r.openInNewTab=e("#er-link-target"),r.search=e("#er-link-search"),o.search=new n(e("#er-search-results")),o.recent=new n(e("#er-most-recent-results")),o.elements=r.dialog.find(".query-results"),r.queryNotice=e("#er-query-notice-message"),r.queryNoticeTextDefault=r.queryNotice.find(".query-notice-default"),r.queryNoticeTextHint=r.queryNotice.find(".query-notice-hint"),r.dialog.keydown(t.keydown),r.dialog.keyup(t.keyup),r.submit.click(function(e){e.preventDefault(),t.close(r.wrap)}),r.close.add(r.backdrop).add("#er-link-cancel a").click(function(e){e.preventDefault(),t.close()}),e("#er-link-search-toggle").on("click",t.toggleInternalLinking),o.elements.on("river-select",t.updateFields),r.search.on("focus.erlink",function(){r.queryNoticeTextDefault.hide(),r.queryNoticeTextHint.removeClass("screen-reader-text").show()}).on("blur.erlink",function(){r.queryNoticeTextDefault.show(),r.queryNoticeTextHint.addClass("screen-reader-text").hide()}),r.search.keyup(function(){var e=this;window.clearTimeout(i),i=window.setTimeout(function(){t.searchInternalLinks.call(e)},500)}),r.url.on("paste",function(){setTimeout(a,0)}),r.url.on("blur",a)},open:function(i){e(document.body).addClass("modal-open"),t.range=null,r.wrap.show(),r.backdrop.show(),o.search.refresh(),o.recent.refresh(),o.recent.ul.children().length||o.recent.ajax(),s="",r.url.val(s),r.submit.val(wpLinkL10n.update),r.text.val(i||""),r.wrap.addClass("has-text-field"),e(document).trigger("erlink-open",r.wrap),c?r.url.focus().blur():r.url.focus()[0].select()},close:function(t){e(document.body).removeClass("modal-open"),r.backdrop.hide(),r.wrap.hide(),s=!1,e(document).trigger("erlink-close",t)},updateFields:function(e,t){r.url.val(t.children(".item-permalink").val())},searchInternalLinks:function(){var i,n=e(this),a=n.val();if(a.length>2){if(o.recent.hide(),o.search.show(),t.lastSearch==a)return;t.lastSearch=a,i=n.parent().find(".spinner").addClass("is-active"),o.search.change(a),o.search.ajax(function(){i.removeClass("is-active")})}else o.search.hide(),o.recent.show()},next:function(){o.search.next(),o.recent.next()},prev:function(){o.search.prev(),o.recent.prev()},keydown:function(i){var n,a,s=e.ui.keyCode;s.ESCAPE===i.keyCode?(t.close(),i.stopImmediatePropagation()):s.TAB===i.keyCode&&(a=i.target.id,"er-link-submit"!==a||i.shiftKey?"er-link-close"===a&&i.shiftKey&&(r.submit.focus(),i.preventDefault()):(r.close.focus(),i.preventDefault())),(i.keyCode===s.UP||i.keyCode===s.DOWN)&&(!document.activeElement||"er-link-title-field"!==document.activeElement.id&&"er-url-field"!==document.activeElement.id)&&(n=i.keyCode===s.UP?"prev":"next",clearInterval(t.keyInterval),t[n](),t.keyInterval=setInterval(t[n],t.keySensitivity),i.preventDefault())},keyup:function(i){var n=e.ui.keyCode;(i.which===n.UP||i.which===n.DOWN)&&(clearInterval(t.keyInterval),i.preventDefault())},delayedCallback:function(e,t){var i,n,a,s;return t?(setTimeout(function(){return n?e.apply(s,a):void(i=!0)},t),function(){return i?e.apply(this,arguments):(a=arguments,s=this,void(n=!0))}):e},toggleInternalLinking:function(e){var t=r.wrap.hasClass("search-panel-visible");r.wrap.toggleClass("search-panel-visible",!t),setUserSetting("wplink",t?"0":"1"),r[t?"url":"search"].focus(),e.preventDefault()}},n=function(t,i){var n=this;this.element=t,this.ul=t.children("ul"),this.contentHeight=t.children("#er-link-selector-height"),this.waiting=t.find(".river-waiting"),this.change(i),this.refresh(),e("#er-link .query-results, #er-link #er-link-selector").scroll(function(){n.maybeLoad()}),t.on("click","li",function(t){n.select(e(this),t)})},e.extend(n.prototype,{refresh:function(){this.deselect(),this.visible=this.element.is(":visible")},show:function(){this.visible||(this.deselect(),this.element.show(),this.visible=!0)},hide:function(){this.element.hide(),this.visible=!1},select:function(e,t){var i,n,a,s;e.hasClass("unselectable")||e==this.selected||(this.deselect(),this.selected=e.addClass("selected"),i=e.outerHeight(),n=this.element.height(),a=e.position().top,s=this.element.scrollTop(),0>a?this.element.scrollTop(s+a):a+i>n&&this.element.scrollTop(s+a-n+i),this.element.trigger("river-select",[e,t,this]))},deselect:function(){this.selected&&this.selected.removeClass("selected"),this.selected=!1},prev:function(){var e;this.visible&&this.selected&&(e=this.selected.prev("li"),e.length&&this.select(e))},next:function(){var t;this.visible&&(t=this.selected?this.selected.next("li"):e("li:not(.unselectable):first",this.element),t.length&&this.select(t))},ajax:function(e){var i=this,n=1==this.query.page?0:t.minRiverAJAXDuration,a=t.delayedCallback(function(t,n){i.process(t,n),e&&e(t,n)},n);this.query.ajax(a)},change:function(e){this.query&&this._search==e||(this._search=e,this.query=new a(e),this.element.scrollTop(0))},process:function(t,i){var n="",a=!0,s="",l=1==i.page;t?e.each(t,function(){s=a?"alternate":"",s+=this.title?"":" no-title",n+=s?'<li class="'+s+'">':"<li>",n+='<input type="hidden" class="item-permalink" value="'+this.permalink+'" />',n+='<span class="item-title">',n+=this.title?this.title:wpLinkL10n.noTitle,n+='</span><span class="item-info">'+this.info+"</span></li>",a=!a}):l&&(n+='<li class="unselectable no-matches-found"><span class="item-title"><em>'+wpLinkL10n.noMatchesFound+"</em></span></li>"),this.ul[l?"html":"append"](n)},maybeLoad:function(){var e=this,i=this.element,n=i.scrollTop()+i.height();!this.query.ready()||n<this.contentHeight.height()-t.riverBottomThreshold||setTimeout(function(){var n=i.scrollTop(),a=n+i.height();!e.query.ready()||a<e.contentHeight.height()-t.riverBottomThreshold||(e.waiting.addClass("is-active"),i.scrollTop(n+e.waiting.outerHeight()),e.ajax(function(){e.waiting.removeClass("is-active")}))},t.timeToTriggerRiver)}}),a=function(e){this.page=1,this.allLoaded=!1,this.querying=!1,this.search=e},e.extend(a.prototype,{ready:function(){return!(this.querying||this.allLoaded)},ajax:function(t){var i=this,n={action:"wp-link-ajax",page:this.page,_ajax_linking_nonce:r.nonce.val()};this.search&&(n.search=this.search),this.querying=!0,e.post(ajaxurl,n,function(e){i.page++,i.querying=!1,i.allLoaded=!e,t(e,n)},"json")}}),e(document).ready(t.init)}(jQuery);