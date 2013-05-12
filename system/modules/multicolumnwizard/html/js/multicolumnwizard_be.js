/**
 * Contao Open Source CMS
 *
 * @copyright   Andreas Schempp 2011, certo web & design GmbH 2011, MEN AT WORK 2013
 * @package     MultiColumnWizard
 * @license     GNU/LGPL
 * @info        tab is set to 4 whitespaces
 */
var MultiColumnWizard=new Class({Implements:[Options],options:{table:null,maxCount:0,minCount:0,uniqueFields:[]},operationLoadCallbacks:[],operationClickCallbacks:[],operationUpdateCallbacks:[],initialize:function(b){this.setOptions(b);this.options.table=document.id(this.options.table);if(window.Backend){Backend.getScrollOffset()}var a=this;this.options.table.getElement("tbody").getChildren("tr").each(function(d,c){d.getChildren("td.operations a").each(function(e){var f=e.get("rel");if(MultiColumnWizard.operationLoadCallbacks[f]){MultiColumnWizard.operationLoadCallbacks[f].each(function(g){g.pass([e,d],a)()})}if(a.operationLoadCallbacks[f]){a.operationLoadCallbacks[f].each(function(g){g.pass([e,d],a)()})}})});this.updateOperations()},updateOperations:function(){var a=this;this.options.table.getElement("tbody").getChildren("tr").each(function(c,b){c.getChildren("td.operations a").each(function(d){var e=d.get("rel");d.removeEvents("click");if(MultiColumnWizard.operationClickCallbacks[e]){MultiColumnWizard.operationClickCallbacks[e].each(function(f){d.addEvent("click",function(g){g.preventDefault();f.pass([d,c],a)()})})}if(a.operationClickCallbacks[e]){a.operationClickCallbacks[e].each(function(f){d.addEvent("click",function(g){g.preventDefault();f.pass([d,c],a)();a.updateFields(b)})})}d.addEvent("click",function(f){f.preventDefault();a.updateOperations.pass([d,c],a)()});if(MultiColumnWizard.operationUpdateCallbacks[e]){MultiColumnWizard.operationUpdateCallbacks[e].each(function(f){f.pass([d,c],a)()})}if(a.operationUpdateCallbacks[e]){a.operationUpdateCallbacks[e].each(function(f){f.pass([d,c],a)()})}})})},updateRowAttributes:function(b,a){a.getElements(".mcwUpdateFields *").each(function(e){if(e.hasClass("chzn-container")){e.destroy();return}if(typeOf(e.getProperty("name"))=="string"){var g=e.getProperty("name").match(/^([^\[]+)\[([0-9]+)\](.*)$/i);if(g){e.setProperty("name",g[1]+"["+b+"]"+g[3])}}if(typeOf(e.getProperty("id"))=="string"){var g=e.getProperty("id").match(/^(.+)_row[0-9]+_(.+)$/i);if(g){e.setProperty("id",g[1]+"_row"+b+"_"+g[2])}}if(typeOf(e.getProperty("onclick"))=="string"){var g=e.getProperty("onclick").match(/^(.+)_row[0-9]+_(.+)$/i);if(g){e.setProperty("onclick",g[1]+"_row"+b+"_"+g[2])}}if(typeOf(e.getProperty("for"))=="string"){var g=e.getProperty("for").match(/^(.+)_row[0-9]+_(.+)$/i);if(g){e.setProperty("for",g[1]+"_row"+b+"_"+g[2])}}switch(e.nodeName.toUpperCase()){case"SELECT":if(e.hasClass("tl_chosen")){new Chosen(e)}break;case"INPUT":if(e.getStyle("display").toLowerCase()=="none"){e.setStyle("display","inline")}if(typeOf(e.getProperty("id"))!="string"){e.destroy()}break;case"SCRIPT":var d="";var c=e.get("html").toString();var f=0;var h=c.search(/_row[0-9]+_/i);while(h>0){f=c.match(/(_row[0-9]+)+_/i)[0].length;d=d+c.substr(0,h)+"_row"+b+"_";c=c.substr(h+f);h=c.search(/_row[0-9]+_/i)}e.set("html",d+c);break}});return a},addOperationLoadCallback:function(a,b){if(!this.operationLoadCallbacks[a]){this.operationLoadCallbacks[a]=[]}this.operationLoadCallbacks[a].include(b)},addOperationUpdateCallback:function(a,b){if(!this.operationUpdateCallbacks[a]){this.operationUpdateCallbacks[a]=[]}this.operationLoadCallbacks[a].include(b)},addOperationClickCallback:function(a,b){if(!this.operationClickCallbacks[a]){this.operationClickCallbacks[a]=[]}this.operationClickCallbacks[a].include(b)},killAllTinyMCE:function(e,g){var d=g.getParent(".multicolumnwizard");if(d.getElements(".tinymce").length==0){return}var b=d.get("id");var c=new RegExp(b);var f=new Array();var a=0;tinyMCE.editors.each(function(i,h){if(i.editorId.match(c)!=null){f[a]=i.editorId;a++}});f.each(function(j,h){try{var i=tinyMCE.get(j);$(i.editorId).set("text",i.getContent());i.remove()}catch(k){console.log(k)}});d.getElements("span.mceEditor").each(function(i,h){console.log(i.getSiblings("script"));i.dispose()});d.getElements(".tinymce").each(function(i,h){i.getElements("script").each(function(k,j){k.dispose()})})},reinitTinyMCE:function(d,e,a){var c=null;if(a!=true){c=e.getParent(".multicolumnwizard")}else{c=e}if(c.getElements(".tinymce").length==0){return}var b=c.getElements(".tinymce textarea");b.each(function(g,f){tinyMCE.execCommand("mceAddControl",false,g.get("id"));tinyMCE.get(g.get("id")).show();$(g.get("id")).erase("required");$(tinyMCE.get(g.get("id")).editorContainer).getElements("iframe")[0].set("title","MultiColumnWizard - TinyMCE")})},reinitStylect:function(){if(window.Stylect){$$(".styled_select").each(function(b,a){b.dispose()});Stylect.convertSelects()}}});Object.append(MultiColumnWizard,{operationLoadCallbacks:{},operationClickCallbacks:{},operationUpdateCallbacks:{},addOperationLoadCallback:function(a,b){if(!MultiColumnWizard.operationLoadCallbacks[a]){MultiColumnWizard.operationLoadCallbacks[a]=[]}MultiColumnWizard.operationLoadCallbacks[a].include(b)},addOperationUpdateCallback:function(a,b){if(!MultiColumnWizard.operationUpdateCallbacks[a]){MultiColumnWizard.operationUpdateCallbacks[a]=[]}MultiColumnWizard.operationUpdateCallbacks[a].include(b)},addOperationClickCallback:function(a,b){if(!MultiColumnWizard.operationClickCallbacks[a]){MultiColumnWizard.operationClickCallbacks[a]=[]}MultiColumnWizard.operationClickCallbacks[a].include(b)},copyUpdate:function(b,c){var a=c.getSiblings().length+1;if(this.options.maxCount>0&&a>=this.options.maxCount){b.setStyle("display","none")}else{b.setStyle("display","inline")}},copyClick:function(b,d){this.killAllTinyMCE(b,d);var a=d.getSiblings().length+1;if(this.options.maxCount==0||(this.options.maxCount>0&&a<this.options.maxCount)){var e=d.clone(true,true);level=d.getAllPrevious().length;e=this.updateRowAttributes(++level,e);e.inject(d,"after");if(e.getElements("script").length>0){e.getElements("script").each(function(f){$exec(f.get("html"))})}var c=this;e.getAllNext().each(function(f){c.updateRowAttributes(++level,f)})}this.reinitTinyMCE(b,d,false);this.reinitStylect()},deleteUpdate:function(b,c){var a=c.getSiblings().length+1;if(this.options.minCount>0&&a<=this.options.minCount){b.setStyle("display","none")}else{b.setStyle("display","inline")}},deleteClick:function(b,e){this.killAllTinyMCE(b,e);var a=e.getParent(".multicolumnwizard");if(e.getSiblings().length>0){var d=e.getAllNext();level=e.getAllPrevious().length;e.destroy();var c=this;d.each(function(f){c.updateRowAttributes(level++,f)})}else{e.getElements("input,select,textarea").each(function(f){MultiColumnWizard.clearElementValue(f)})}this.reinitTinyMCE(b,a,true)},upClick:function(b,d){this.killAllTinyMCE(b,d);var c=d.getPrevious();if(c){var a=c.getAllPrevious().length;c=this.updateRowAttributes(99999,c);d=this.updateRowAttributes(a,d);c=this.updateRowAttributes(a+1,c);d.inject(c,"before")}this.reinitTinyMCE(b,d,false)},downClick:function(c,d){this.killAllTinyMCE(c,d);var b=d.getNext();if(b){var a=d.getAllPrevious().length;d=this.updateRowAttributes(99999,d);b=this.updateRowAttributes(a,b);d=this.updateRowAttributes(a+1,d);d.inject(b,"after")}this.reinitTinyMCE(c,d,false)},clearElementValue:function(a){if(a.get("type")=="checkbox"||a.get("type")=="radio"){a.checked=false}else{a.set("value","")}}});MultiColumnWizard.addOperationUpdateCallback("copy",MultiColumnWizard.copyUpdate);MultiColumnWizard.addOperationClickCallback("copy",MultiColumnWizard.copyClick);MultiColumnWizard.addOperationUpdateCallback("delete",MultiColumnWizard.deleteUpdate);MultiColumnWizard.addOperationClickCallback("delete",MultiColumnWizard.deleteClick);MultiColumnWizard.addOperationClickCallback("up",MultiColumnWizard.upClick);MultiColumnWizard.addOperationClickCallback("down",MultiColumnWizard.downClick);(function(Backend){if(!Backend)return;Backend.openModalSelectorOriginal=Backend.openModalSelector;Backend.openModalSelector=function(a){Backend.openModalSelectorOriginal(a);var b=null;var e=60;var d=new URI(a.url).getData("field")+"_parent";var c=setInterval(function(){e-=1;var f=window.frames;for(var g=0;g<f.length;g++){if(f[g].name=="simple-modal-iframe"){b=f[g];break}}if(b&&b.document.getElementById(d)){b.document.getElementById(d).set("id",a.id+"_parent");clearInterval(c);return}if(e<=0){clearInterval(c)}},500)};})(window.Backend);