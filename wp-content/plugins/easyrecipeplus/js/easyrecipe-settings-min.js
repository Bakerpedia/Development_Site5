/*! EasyRecipe Plus 3.3.3077 Copyright (c) 2015 BoxHill LLC */
window.EASYRECIPE=window.EASYRECIPE||{},function(e){function t(t){var i,o;if(N.hide(),"OK"===t.status)e("#ERDOK").show();else{for(I.show(),o=e("#ERDErrors",T),o.empty(),i=0;i<t.errors.length;i++)o.append(e("<p>"+t.errors[i]+"</p>"));T.show()}}function i(e){$.tabs({active:e})}function o(t,i){var o=i.newTab||e(i.tab);o.find("a").hasClass("ERSNoSave")?x.hide():x.show(),le=o.find("a").attr("href").replace("#divERS","")}function r(){return""===e.trim(Z.val())?(Z.showErrorRight("Please enter your first name"),!1):!0}function s(){return te.test(e.trim(_.val()))?!0:(_.showErrorRight("Oops! This doesn't seem to be a valid email address"),!1)}function a(){return""===e.trim(W.val())?(W.showErrorRight("Please tell us about the problem"),!1):!0}function n(){var i,o;e(".showError").dialog("destroy").remove(),o=r(),o=s()&&o,o=a()&&o,o&&(i={action:"easyrecipeSupport",inpName:encodeURIComponent(e.trim(Z.val())),inpEmail:encodeURIComponent(e.trim(_.val())),inpSubject:encodeURIComponent(e.trim(e("#inpERDSubject").val())),inpMessage:encodeURIComponent(e.trim(W.val())),sendDiagnostics:e("#cbERDSendDiagnostics").attr("checked")},I.hide(),T.hide(),N.show(),e.post(ajaxurl,i,t,"json"))}function l(){e("#divERSStyles ").find(".ERSStyle").removeClass("ERSStyleSelected"),e(this).addClass("ERSStyleSelected"),k.val(e("input",this).val())}function d(){e("#divERSPrintStyles").find(".ERSStyle").removeClass("ERSStyleSelected"),e(this).addClass("ERSStyleSelected"),j.val(e("input",this).val())}function c(){this.checked?L.removeClass("ERSNoSwoop"):L.addClass("ERSNoSwoop")}function u(){this.checked?z.removeClass("ERSNoSubscribe"):z.addClass("ERSNoSubscribe")}function p(t,o){var r=e.trim(A.val());return""===r||te.test(r)?o&&""===r?(i(de),A.showErrorRight("You need to tell us your email address",{clear:"#cbERSSubscribe"}),!1):!0:(i(de),A.showErrorRight("Oops! This doesn't seem to be a valid email address",{clear:"#cbERSSubscribe"}),!1)}function S(t,o){var r,s,a=e.trim(U.val());if(""!==a){if(!/^SW-\d{8}-/i.test(a))return i(de),U.showErrorLeft("That's not a valid Swoop ID"),!1;r=e("a",Y),s=e("a",Y).attr("href"),s=s.replace(/site_id=(.*)/gi,"site_id="+a),e("a",Y).attr("href",s),G.addClass(ae),Y.removeClass(ae)}else G.removeClass(ae),Y.addClass(ae);return o&&""===a?(i(de),U.showErrorLeft("You must enter a valid ID to enable Swoop"),!1):!0}function h(){var t,o,r,s=e.trim(K.val());if(K.val(s),""==s)return!0;if(r=re.test(s),!r&&!oe.test(s))return i(de),K.showErrorRight("This is not a valid license key"),!1;if(r){for(o="",t=0;8>t;t++)o+=s.substr(4*t,4)+"-";K.val(o.substr(0,39))}return!0}function v(t){var i,o,r=!0;e(".showError").dialog("destroy").remove(),r=h()&&r,Q.is(":checked")&&(r=p(null,!0)&&r),M.is(":checked")&&(r=S(null,!0)&&r),r?(o={General:0,Labels:1,Styles:2,PrintStyles:3,Fooderific:4,GuestPosts:5,Import:6,Support:7,Geeks:8},i=e(t.target),i.attr("action",i.attr("action")+"#"+o[le])):t.preventDefault()}function f(){B.html("Version check failed")}function E(t){if(!t.version||!t.msg)return void f();switch(B.html(t.msg),t.type){case"js":e("head").append(e('<script type="text/javascript">'+t.js+"</script>")),H[t.f]();break;case"html":e(t.dest).html(t.html)}}function R(t){e("div",V).addClass(ae),e("#divERS"+t.target.value+"Settings",V).removeClass(ae),"Ziplist"==t.target.value&&(""===e("#divERSZiplistSettings").find("input").val()?e("#divERSZiplistLink").removeClass(ae):e("#divERSZiplistLink").addClass(ae))}function m(){e("#divFDFirstRun").hide(),e("#divFDScanScheduled").show(),e("#divFDRetrieving").hide(),e("#divFDStatus").hide(),ee=(new Date).getTime()/1e3,e("#inpFDEnable").attr("checked","checked")}function w(){alert("Schedule failed")}function b(t){t&&(t.stopPropagation(),t.preventDefault()),e.ajax({url:ajaxurl,type:"post",data:{action:"easyrecipeScanSchedule"},success:m,error:w})}function g(){var e=confirm("You normally only need to run a site scan once but it won't do any harm to run it again\n\nPress OK to re-run a site scan");e&&b()}function y(t){var i,o,r=t.fields;for(i in r)r.hasOwnProperty(i)&&(o="#tdFD"+i,e(o).text(r[i]));e("#spnFDLASTSCAN").text(H.lastScan),e("#divFDRetrieving").hide(),e("#divFDStatus").show()}function D(){alert("Site statistics failed")}function C(){e.ajax({url:ne,type:"POST",dataType:"json",data:{site:H.wpurl,apikey:H.fdAPIKey},success:y,error:D})}function F(t){t.stopPropagation(),t.preventDefault(),e("#divFDScanScheduled").hide(),e("#divFDRetrieving").show(),C()}function P(){e("#inpFDEnable").removeAttr("checked"),X.submit()}var k,j,T,I,N,x,L,z,A,O,U,Y,G,K,$,V,W,Z,_,Q,M,B,H,q,J,X,ee,te=/^(?:[a-z0-9!#$%&'*+\/=?\^_`{|}~\-]+(?:\.[a-z0-9!#$%&'*+\/=?\^_`{|}~\-]+)*@(?:[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?)$/i,ie="click",oe=/^(?:[a-f\d]{4}-){7}[a-f\d]{4}$/i,re=/^[a-f\d]{32}$/i,se="change",ae="ERSDisplayNone",ne="http://www.fooderific.com/plugin/sitestats.php",le="General",de=0;e(function(){var t,i;H=EASYRECIPE,H.isWP39&&(H.dialogs=e("<div>").addClass("easyrecipeUI").prop("id","easyrecipeUI").appendTo("body")),q=e("#divERSStyles"),J=e("#divERSPrintStyles"),k=q.find(".ERSSelectedStyle"),j=J.find(".ERSSelectedPrintStyle"),O=e("#easyrecipeUpgrade"),I=e("#btnERSendSupport"),$=e("#divERSTabs"),$.tabs({active:window.location.hash?parseInt(window.location.hash.replace("#",""),10):0,beforeActivate:o}),e(".ERSStyleTabs").tabs({active:0}),B=e("#spnERSLatestVersion"),q.on(ie,".ERSStyle",l),J.on(ie,".ERSStyle",d),x=e("#divERSSave").find("input").button(),I.button().on(ie,n),L=e("#divERSSwoopSettings"),z=e("#trERSSubscribe"),M=e("#cbERSEnableSwoop").on(ie,c),Q=e("#cbERSSubscribe").on(ie,u),T=e("#ERDFail"),N=e("#ERDSending"),V=e(".ERSSaveButtons"),e('input[type="radio"]',V).on(se,R),A=e('.ERSSubscribe input[name="'+H.settingsName+'[erEmailAddress]"]').on(se,p),e(".ERSSubscribe").on(ie,function(){e(".ERSubscribeError").css("visibility","hidden")}),U=e('input[name="'+H.settingsName+'[swoopSiteID]"]',L),U.on("change",S),K=e('input[name="'+H.settingsName+'[licenseKey]"]').on(se,h),G=e("#divERSRegisterSwoop"),Y=e("#divERSLoginSwoop"),X=e("#frmERSForm"),X.on("submit",v),Z=e("#inpERDName").on(se,r),_=e("#inpERDEmail").on(se,s),W=e("#inpERDProblem").on(se,a),H.jQuery=e,e("#btnFDStartScan").button().on(ie,b),e("#spnFDRescan").on(ie,g),e("#btnFDContinue").button().on(ie,F),e("#divFDDisable").on(ie,P),e("#divFDRetrieving").hasClass("FDDisplayNone")||C(),t=1,i=e.support.cors?"json":"jsonp",e.ajax({url:"http://www.easyrecipeplugin.com/checkVersion.php",type:"GET",dataType:i,data:{a:"check",v:H.pluginVersion,k:H.license,u:H.wpurl,slug:H.slug,p:t},success:E,error:f}),$.show()})}(jQuery),function(e){function t(e){e.data.clear.off(i,t),e.data.errDiv.dialog("destroy"),e.data.errDiv.remove()}var i="focus",o="left",r="right";e.fn.showError=function(s,a){var n,l,d,c,u,p,S,h,v,f,E;for(a=a||{},a.clear=a.clear||[],"string"==typeof a.clear&&(a.clear=[a.clear]),S=e("#divShowError"),0===S.length&&(S=e('<div id="divShowError"></div>').appendTo("body")),p=this,n={position:r,container:null,clear:[]},e.extend(n,a),c=0;c<n.clear.length;c++)p=p.add(n.clear[c]);u='<span class="showError showError-'+n.position+'">',u+='<div class="showErrorLeftPtr"></div><div class="showErrorMsg">'+s,u+='</div><div class="showErrorRightPtr"></div></span>',u=e(u),d=n.position===o?{my:r,at:o,of:this}:{my:o,at:r,of:this},u.dialog({dialogClass:"showErrorDialog",draggable:!1,resizable:!1,autoOpen:!1,appendTo:S,closeText:"",width:"auto",position:d}),u.dialog("open"),"1.10.3"==e.ui.version&&(h=u.dialog("widget"),v=this.offset(),f=v.top+this.outerHeight()/2-12,E=v.left,l=u.outerWidth(),"right"===n.position?E+=this.outerWidth():E-=l,h.css({position:"absolute",top:f+"px",left:E+"px"})),p.on(i,{clear:p,errDiv:u},t)},e.fn.showErrorLeft=function(e,t){t=t||{},t.position=o,this.showError(e,t)},e.fn.showErrorRight=function(e,t){t=t||{},t.position=r,this.showError(e,t)}}(jQuery);