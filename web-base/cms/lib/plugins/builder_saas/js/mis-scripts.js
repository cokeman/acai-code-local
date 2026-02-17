var jsonResult = {
    objects:[]
};
window.lastGroupId = null;

var removeIsActive = false;
var splitLeft = document.querySelector(".split.left>.wrapper>ul.list-modules");
var splitRight = document.querySelector(".split.right");
var splitsView = {left:splitLeft,right:splitRight};
var slideShowNode = splitRight.querySelector(".glide>div>ul.glide__slides");
var newModuleElement = document.querySelector(".split.right .newModule");
var customColorsElement = document.querySelector("#colorEditor");
var customMarginsElement = document.querySelector("#marginEditor");
var editModuleElement = document.querySelector(".split.right .editModule");
var dragSortParent = document.querySelector(".drag-sort-enable");
var editNode = null;
var botonSave = document.querySelector(".save-records");
var activeModule = null;
var buttonFullPreview = document.querySelector(".btn-fullPreview");
var buttonSaveAndExitPressed = false;
var editLoaded = false;
var indexEditTab = 0;
var websiteDomain = "https://" + CURRENT_USER["domain"]["domain"];
var percentSave = document.createElement("div");
var auxBridgeObject = null;
var glide = null;
var availableModules = [];
var availableModulesId = [];
var modules = [];
var localModules = [];
var allModulesLoaded = false;
var expandedView = 2;//localStorage.getItem("expandedView") ? localStorage.getItem("expandedView") : 1;
var loadingNode = document.getElementById("loading");
var newModules = null;
var navbarModule = null;
var PREVIEW_URL = websiteDomain + APARTADO_DATA.enlace + `?builderPreview=1&pruebas=1`;
//var QUILL = new QuillJS;

percentSave.classList.add("porcentaje");
document.body.appendChild(percentSave);

(async ()=> {
    startLoading();
    await loadRequiredModules(myConfig).then((respond) => {
        
        stopLoading();
        modules = respond["web"];
        localModules = respond["local"];
        
        // PRIMERO PONEMOS LOS ELEMENTOS DE LA BASE DE DATOS
        Rest.initGlobalCachedData().then(() => {

            for (cont in myConfig){

                var module = myConfig[cont];
                if (modules[module.modulo]){
                    module.builder = modules[module.modulo];    
                }else{
                    module.builder = localModules[module.modulo];
                }
                if (!module.builder){
                    swal("Espera!","Ha pasado algo extraño y no puedes continuar editando, ponte en contacto con nosotros para solucionar el problema","warning");
                    console.log("No encuentro el modulo " + module.modulo);
                    delete myConfig[cont];
                    continue;
                }
                module.builder.id = module.modulo;
                module.data = myConfig[cont]['config-vars'] || {};
                
                myConfig[cont] = new Module(module,splitsView,false,cont==0?false:true);

                myConfig[cont].add();
            }   

            if (window.innerWidth>720 && myConfig[0] && myConfig[0].builder) {
                myConfig[0].clickHandler();
            }
            loadEdit();
        });
    });
    
    window.onmessage = function(e){
        switch(e.data.key){
            case "selectBuilderModule":
                var moduleSelected = null;
                var cont = 0;
                
                for (const indexModule in myConfig){
                    if (myConfig[indexModule].oculto) continue;
                    if (e.data.indexNode == cont){
                        myConfig[indexModule].clickHandler(false,false);
                        myConfig[indexModule].node.scrollIntoViewIfNeeded();
                    }
                    cont+=1;
                }
                
                
            break;
        }
    };
    
    window.onbeforeunload = function(e) {
        if (!buttonSaveAndExitPressed && needToSave(true,true)){
            return 'Texto de aviso';    
        }
    };
    window.onkeydown = function(e){
        var evtobj = window.event? event : e;
//        if (evtobj.keyCode == 49 && (evtobj.ctrlKey || evtobj.metaKey)) {
//            e.preventDefault();
//            toggleEditTabs(0,true,splitRight.querySelector("[data-toggle-tab='0']"));
//        }
//        if (evtobj.keyCode == 50 && (evtobj.ctrlKey || evtobj.metaKey)) {
//            e.preventDefault();
//            toggleEditTabs(1,true,splitRight.querySelector("[data-toggle-tab='1']"));
//        }
        if (evtobj.keyCode == 83 && (evtobj.ctrlKey || evtobj.metaKey)) {
            e.preventDefault();
            e.stopPropagation();
            if (document.getElementById("modalCrearModulo") && document.getElementById("modalCrearModulo").classList.contains("abierto")){
                app.uploadModule(false);
            }else if (document.getElementById("modalLayout") && document.getElementById("modalLayout").classList.contains("abierto")){
                // ESTE YA NO SE USA
                appLayout.saveData(false);
            }else{
                document.activeElement.blur()
                if (NUM) saveRecord(false,false); else saveRecord(true,false);    
            }
            
            return false;
        }
//        if (evtobj.keyCode == 51 && (evtobj.ctrlKey || evtobj.metaKey)) {
//            e.preventDefault();
//            toggleEditTabs(3,true,splitRight.querySelector("[data-toggle-tab='3']"));
//        }
    }
    
    var newTabs = splitRight.querySelectorAll("li[role='presentation']>a");
    for (newTab of newTabs){
        newTab.addEventListener("click",function(e){
            activateTab(e.target.getAttribute("aria-controls"));
        });
    }
    
    
    if (document.querySelector(".split.right2")) changeViewExpanded(expandedView);
    
})();

if (typeof resizeIframe !== "function"){
    function resizeIframe(id,element) {
        var maxHeight        = 320;
        if (id){
            element = document.querySelector(id);
        }
        if (element){
            var iframeEl = element;
            if (iframeEl && iframeEl.contentWindow && iframeEl.contentWindow.document && iframeEl.contentWindow.document.body){
                var iframeBodyHeight = iframeEl.contentWindow.document.body.scrollHeight;
                // set height
                if (iframeBodyHeight > 0 && iframeBodyHeight <= maxHeight) {
                    iframeEl.height = iframeBodyHeight;
                    iframeEl.style.height = iframeBodyHeight+"px";
                }
                else {
                    iframeEl.height = maxHeight;
                    iframeEl.style.height = maxHeight+"px";
                }
            }    
        }else{
            var iframes = splitRight.querySelectorAll("iframe");
            for (iframeEl of iframes){
                resizeIframe(null,iframeEl);
            }
        }
        

    }
}

function scrollIframeToIndex(index){
    var iframeView = document.querySelector(".split.right2 iframe");
    if (!iframeView) return;
    var cont = 0;
    
    for (const indexModule in myConfig){
        if (myConfig[indexModule].oculto) continue;
        
        if (index == indexModule){
            iframeView.contentWindow.postMessage({key:"scrollTo",indexNode:cont},'*');
        }else if(cont > index){
            break;
        }
        cont+=1;
    }
    
}

function startLoading(){
    
    loadingNode.style.opacity = 1;
    loadingNode.style.pointerEvents = "all";
}

function stopLoading(){
    loadingNode.style.opacity = 0;
    loadingNode.style.pointerEvents = "none";
}
function refreshBuilderModule(id,data){
    if (!data) return false;
    if (!id) return false;
    for (const cont in myConfig){
        if (myConfig[cont].builder && myConfig[cont].builder.id == id){
            for (const key in data){
                myConfig[cont].builder[key] = data[key];
            }
            myConfig[cont].render(false,true);
            if (myConfig[cont].isActive){
                myConfig[cont].renderEditView();
            }
        }
    }
    
    for (const key in data){
        if (!availableModules[id]) continue;
        try{
            availableModules[id].builder[key] = data[key];
            availableModules[id].render(false,true);    
        }catch(error){
            console.log({
                module:availableModules[id],
                error:error
            });        
        }
    }
    
}


function startModuleExtractor(url){
    if (!url) url="https://www.k-tuin.com/";
    var iframe = document.createElement("iframe");
    iframe.classList.add("iframeExtractor");
    iframe.src="/lib/plugins/builder_saas/extractor/playground.php?url="+url;
    document.body.appendChild(iframe);
}
function setModuleExtractor(html,css){
    console.log({html:html,css:css});
    var iframe = document.querySelector(".iframeExtractor");
    iframe.srcdoc = `${html}<style data-external>${css}</style>`;
    iframe.sandbox = "allow-scripts allow-modals allow-popups allow-same-origin allow-top-navigation allow-pointer-lock allow-forms";

}

function anadirModuloSlide(){
    var slide = document.querySelector(".glide__slide--active+li>img");
    if (slide){
        var forId = slide.getAttribute("data-id");
        for (const cont in availableModules){
            if (availableModules[cont].builder.id == forId){
                var that = availableModules[cont];
                var newModule = JSON.parse(JSON.stringify(that));
                newModule = new Module(newModule,that.viewPort.splitsView);

                newModule.add(that.newPositionItem);
                myConfig.push(newModule);
                for (let myConf of myConfig){
                    if (myConf.isActive) {
                        myConf.renderEditView();
                    }
                }
                break;
            }
        }
        needToSave();
    }
    
}

function parseBuilderModuleRootURL(url){
    if (!url) return null;
    let currentDomain = CURRENT_USER["domain"]["domain"].split("/")[0];
    
    if (url.indexOf(currentDomain)) url = url.replace(currentDomain,CURRENT_USER["domain"]["domain"]);

    return url;
}

function initSlideshow(SlideModules,target){
    if (!SlideModules) return;
    for (const cont in SlideModules){
        var liSlideShow = document.createElement("li");
        liSlideShow.classList.add("glide__slide");
        
        var imageSlide = document.createElement("img");
        imageSlide.src=SlideModules[cont].path + "/screenshot.jpg";
        imageSlide.setAttribute("data-label",SlideModules[cont].label);
        imageSlide.setAttribute("data-id",SlideModules[cont].id);
        imageSlide.addEventListener("click",function(e){
            
            var yaExiste = document.querySelector(".galeriaSlideShowAmpliada");
            if (yaExiste) yaExiste.parentNode.removeChild(yaExiste);
            var ampliada = document.createElement("img");
            ampliada.classList.add("galeriaSlideShowAmpliada");
            ampliada.src = e.target.src;
            ampliada.addEventListener("click",function(){
                if (ampliada.classList.contains("visible")) ampliada.classList.remove("visible");
                window.setTimeout(() => {this.parentNode.removeChild(this);},400);
            });
            window.setTimeout(() => {ampliada.classList.add("visible");},100);
            
            splitRight.appendChild(ampliada);
        });
        liSlideShow.appendChild(imageSlide);
        
        target.appendChild(liSlideShow);
    }
    
    
    if (!glide){
        glide = new Glide(".glide",{
            type: 'carousel',
            startAt: 1,
            gap:20,
            perView: 3
        }).mount();

        glide.on('move.after', function(e) {
            var yaExiste = document.querySelector(".galeriaSlideShowAmpliada");
            if (yaExiste){
                var slide = document.querySelector(".glide__slide--active+li>img");
                if (slide){
                    var forId = slide.getAttribute("data-id");
                    for (const cont in availableModules){
                        if (availableModules[cont].builder.id == forId){
                            var that = availableModules[cont];
                            yaExiste.src = that.builder.path + "/screenshot.jpg";
                            break;
                        }
                    }
                }
            }
        })
    }
    

}
function setController(){

    var precontrolador = splitRight.querySelector("[name=precontrolador]");
    var controlador = splitRight.querySelector("[name=controlador]");
    
    if (!controlador) return;
    if (myConfig.length>0){
        if (controlador.value=="" && CONTROLADOR_SCHEMA) {
            controlador.value = CONTROLADOR_SCHEMA;
        }
        
        if ((controlador.value == "" ) || ((controlador.value != "cms/lib/plugins/builder_saas/controlador.php") && (controlador.value != precontrolador.value ))){
            precontrolador.value = controlador.value;
            controlador.value = "cms/lib/plugins/builder_saas/controlador.php";
        }
    }else{
        if (controlador.value == "cms/lib/plugins/builder_saas/controlador.php"){
            controlador.value = precontrolador.value;
            precontrolador.value = "";
        }
        if (controlador.value == CONTROLADOR_SCHEMA) controlador.value = "";
    }
}

function needToSave(isNeeded = true,onlyReturnState = false){
    if (onlyReturnState){
        var saveButton = document.querySelector(".save-records");
        if (!saveButton.classList.contains("warning")) return false; else return true;
    }
    if (isNeeded){
        var saveButtons = document.querySelectorAll(".save-records");
        for(button of saveButtons){
            if (!button.classList.contains("warning")) button.classList.add("warning");    
        }
        var autoSave = splitRight.querySelector("input[name=autosaved]");
        if (autoSave){
            var num = splitRight.querySelector("[name=num][type=hidden]");
            if (autoSave.checked && num.value){
                saveRecord(false,false);
            }
        }else{
            console.log("No se encuentra el campo AutoSave");
        }
        if (navbarModule) navbarModule.needToSave = true;
    }else{
        var saveButtons = document.querySelectorAll(".save-records");
        for(button of saveButtons){
            if (button.classList.contains("warning")) button.classList.remove("warning");    
        }
        if (navbarModule) navbarModule.needToSave = false;
    }
}

function toggleMenuMobile(){
    splitLeft.parentNode.parentNode.classList.remove("toggled");
}
function activateTab(tabId,domain = null){
    if (splitRight.querySelectorAll(".tab-content #"+tabId+" .list-modules>li" && tabId!="mjml").length) return false;
    if (splitRight.querySelector(".tab-content #"+tabId+" .list-modules")) splitRight.querySelector(".tab-content #"+tabId+" .list-modules").innerHTML = "";
    if (splitRight.querySelector(".tab-content #"+tabId+" .spinner")) splitRight.querySelector(".tab-content #"+tabId+" .spinner").style.display = "block";
    
    switch(tabId){
        case "generales":
        case "especiales":
            loadLocalModules();
            break;
        case "mjml":
            var localModulesNodes = splitRight.querySelectorAll(".tab-content #mjml .list-modules");
            if (localModulesNodes){
                for (webmodule of localModulesNodes){
                    var lis = webmodule.querySelectorAll("li.bloque");
                    if (lis){
                        for (li of lis){
                            li.parentNode.removeChild(li);
                        }
                    }
                }
            }
            loadWebModules(true);
            loadLocalModules(true);
            
            break;
        default:
            loadWebModules(false,domain ? domain : '');
    }
    return true;
}
async function toggleNewModuleModal(forceClose = false){
    if (newModules && navbarModule){
        if (newModules.active || forceClose){
            navbarModule.disableShowed();
            newModules.close();
            window.activeBuilderModule = null; // RESETEAMOS EL VUE QUE HAYA CARGADO PARA QUE LO VUELVA A CARGAR CUANDO LO NECESITE
        }else{

            newModules.init();
        }
        return;
    }
    
    isActive = newModuleElement.classList.contains("visible");
    if (isActive || forceClose){
        if (document.querySelector(".split.right3")) document.querySelector(".split.right3").classList.remove("pointer-events-none");        
        splitRight.classList.remove("overflowHidden");
        newModuleElement.classList.remove("visible");
        
        
    }else{
        if (document.querySelector(".split.right3")) document.querySelector(".split.right3").classList.add("pointer-events-none");        
        if (!allModulesLoaded){
            activateTab(splitRight.querySelector("[role='tablist'] .active>a").getAttribute("aria-controls"));
        }
        newModuleElement.style.width = splitRight.offsetWidth + "px";
        splitRight.classList.add("overflowHidden");
        newModuleElement.classList.add("visible");

        var newModuleWebSelector = newModuleElement.querySelector("#newModuleWebSelector");
        if (newModuleWebSelector){ 
            newModuleWebSelector.selectedIndex = 0;
        }
        if (USER_PLUGINS["builder_saas"] && USER_PLUGINS["builder_saas"]["acceso_a_slide"] == 1){
            glide.update({ startAt: 1,gap:20,perView:3 });
        }
        
    }
    
}
async function toggleCustomColorsModal(forceClose = false){

    if (!appCustomColors) return;
    isActive = customColorsElement.classList.contains("visible");
    if (isActive || forceClose){
        if (document.querySelector(".split.right3")) document.querySelector(".split.right3").classList.remove("pointer-events-none");        
        splitRight.classList.remove("overflowHidden");
        appCustomColors.showed = false;
    }else{
        
        if (document.querySelector(".split.right3")) document.querySelector(".split.right3").classList.add("pointer-events-none");        
        if (!allModulesLoaded){
            activateTab(splitRight.querySelector("[role='tablist'] .active>a").getAttribute("aria-controls"));
        }
        
        appCustomColors.resize();
        splitRight.classList.add("overflowHidden");
        appCustomColors.showed = true;
    }
}

async function toggleCustomMarginsModal(forceClose = false){

    if (!appCustomMargins) return;
    
    isActive = customMarginsElement.classList.contains("visible");

    if (isActive || forceClose){
        if (document.querySelector(".split.right3")) document.querySelector(".split.right3").classList.remove("pointer-events-none");        
        splitRight.classList.remove("overflowHidden");
        appCustomMargins.showed = false;
    }else{
        
        if (document.querySelector(".split.right3")) document.querySelector(".split.right3").classList.add("pointer-events-none");        
        if (!allModulesLoaded){
            activateTab(splitRight.querySelector("[role='tablist'] .active>a").getAttribute("aria-controls"));
        }
        
        splitRight.classList.add("overflowHidden");
        appCustomMargins.showed = true;
    }
}
function toggleEditModuleModal(forceClose = false,data = {}){
    if (!data) forceClose = true;
    isActive = editModuleElement.classList.contains("visible");
    
    var wrapperEdit = editModuleElement.querySelector(".editWrapperContainer");
    wrapperEdit.innerHTML = "";
    
    if (isActive || forceClose){
        splitRight.classList.remove("overflowHidden");
        editModuleElement.classList.remove("visible");
    }else{
        editModuleElement.style.width = splitRight.offsetWidth + "px";
        splitRight.classList.add("overflowHidden");
        editModuleElement.classList.add("visible");
        var time = new Date();
        
        var iframe = document.createElement("iframe");
        iframe.id = "iframeEditModal";
        iframe.src="admin.php?menu="+data.tableName+"&action=edit&num="+data.num+"&standardEdit=1&timestamp="+time.getTime();
        
        var cssLink = document.createElement("link");
        cssLink.href = "lib/plugins/builder_saas/css/iframedit.css"; 
        cssLink.rel = "stylesheet"; 
        cssLink.type = "text/css"; 
        
        var jsLink = document.createElement("script");
        jsLink.src = "lib/plugins/builder_saas/js/iframedit.js"; 
        
        iframe.onload = function(){
            
            var innerDoc = iframe.contentDocument || iframe.contentWindow.document;
            innerDoc.body.appendChild(cssLink);
            innerDoc.body.appendChild(jsLink);
            
            window.setTimeout(() => {iframe.style.opacity = 1;},100);
        }
        wrapperEdit.appendChild(iframe);
    }
    
}

async function toggleEditTabs(index,toolbar = true,object = null){
    
    window.activeBuilderModule = null; // RESETEAMOS EL VUE QUE HAYA CARGADO PARA QUE LO VUELVA A CARGAR CUANDO LO NECESITE
    
    toggleNewModuleModal(true);
    toggleCustomColorsModal(true);
    toggleEditModuleModal(true);
    
    if (index==0 && navbarModule){
        buttonFullPreview.classList.add("active");
    }
    if (index!=4 && buttonFullPreview.classList.contains("active")) {
        
        if (expandedView == 2) {
            changeViewExpanded(expandedView);
            
        }
        if (document.querySelector(".split.right3")) document.querySelector(".split.right3").classList.remove("hidden");
        buttonFullPreview.classList.remove("active");
        if (navbarModule) navbarModule.disableShowed();
    }
    if (object){
        if (object.hasAttribute("data-no-module")){
            
            for (module of myConfig){
                module._removeAllActive(object);
            }
            if (!object.classList.contains("active")) object.classList.add("active");
        }
    }
    
    var lists = splitRight.querySelectorAll(".wrapper .toolBar>li.toggleWrapper");
    
    var views = splitRight.querySelectorAll(".wrapperContainerPreview,.wrapperContainer");
    
    for (view of views){
        
        if (!view.classList.contains("hidden")) 
            view.classList.add("hidden");
    }
    var viewSelected = document.querySelector("[data-tab-id='"+index+"']");
    viewSelected.classList.remove("hidden");
    
    if (toolbar){
        for (list of lists){
            var listElement = list.querySelector("a");
            if (listElement.classList.contains("active")) 
                listElement.classList.remove("active");
        }
        var listSelected = splitRight.querySelector("[data-toggle-tab='"+index+"']");
        if (listSelected) {
            listSelected.classList.add("active");
        }
        
        var toolbar = splitRight.querySelector(".wrapper .toolBar");
        if (toolbar.classList.contains("hidden")) toolbar.classList.remove("hidden");
    }else{
        var toolbar = splitRight.querySelector(".wrapper .toolBar");
        if (!toolbar.classList.contains("hidden")) toolbar.classList.add("hidden");
    }
    
    var wrapperRight = splitRight.querySelector(".wrapper");
    if (wrapperRight.classList.contains("hidden")){
        wrapperRight.classList.remove("hidden");
    }
    objectInserted = externalViews(index);
    indexEditTab = index;
    if (index == 2) {
        // ES POSIBLE QUE HAYA QUE ALMACENAR EL CAMPO BUILDER PARA PONERLO DESPUES DE LA CARGA
        loadEdit(true);
    }
    
}

async function externalViews(index){
    
    var view = document.querySelector("[data-tab-id='"+index+"']");
    switch(index){
        case 1:
            
            var iframe = document.createElement("iframe");
            iframe.src = "";
            if (view){
                view.innerHTML = "";
                view.appendChild(iframe);
            }
            
            var sectionlinkElement = splitRight.querySelector(".wrapperContainerPreview.full [name=enlace]");
            if (!sectionlinkElement.value){
                swal("Espera!","Antes de visualizar debes guardar los cambios. La próxima vez te prometo que intentaré dejarte verlo","warning");
                toggleEditTabs(0,true);
                return false;
            }
            
            if (needToSave(true,true)){
                
                await swal({
                    title:"Espera!",
                    text:"Necesitas guardar los cambios para poder verlos. Deseas guardar ahora?",
                    icon:"warning",
                    buttons:{
                      cancel: {
                        text: "No",
                        value: null,
                        visible: true,
                        className: "btn btn-default",
                        closeModal: true,
                      },
                      confirm: {
                        text: "SI",
                        value: true,
                        visible: true,
                        className: "btn btn-primary",
                        closeModal: true
                      }
                    }
                }).then((value) => {
                    if (value){
                        if (NUM) saveRecord(false,false); else saveRecord(true,false);
                    }else{
                        toggleEditTabs(0,true);
                        return false;
                    }
                });
            }
            
            if (sectionlinkElement){
                var link = sectionlinkElement.value + "?pruebas=1&onlyModule=" + activeModule.builder.id;
            }else{
                var link = "/?pruebas=1&onlyModule=" + activeModule.builder.id;
            }
            iframe.src = websiteDomain + link;
            
            return iframe;
        break;
        case 2:
            
            var wrapper = document.createElement("div");
            wrapper.appendChild(editNode);
            
            if (view){
                view.innerHTML = "";
                view.appendChild(wrapper);
            }
            comprueba_idiomas();
            
            return wrapper;
            break;
        case 3:
            
            var textArea = document.createElement("div");
            textArea.id = "cssModule";
            if (view){
                view.innerHTML = "";
                view.appendChild(textArea);
            }
            var editorCode = ace.edit("cssModule",{
                autoScrollEditorIntoView: true,
                copyWithEmptySelection: true,
                enableBasicAutocompletion: true
            });
            editorCode.setTheme("ace/theme/monokai");
            editorCode.getSession().setMode("ace/mode/less");
            editorCode.setShowFoldWidgets(false);
            
            xhr = new XMLHttpRequest();
            var date = new Date();
            var url = "admin.php?menu="+MENU+"&action=edit&getCSS=" + activeModule.builder.id;
            xhr.open("POST", url);
            xhr.onload = (data) => {
                editorCode.setValue(data.target.response,1);
                editorCode.focus();
                editorCode.on("blur",function(e){
                    saveCSS(editorCode.getValue());
                });
                editorCode.commands.addCommand({
                    name: 'save',
                    bindKey: {win: "Ctrl-S", mac: "Command-S"},
                    exec: function(editor) {
                        saveCSS(editorCode.getValue());
                    }
                })
            };
            xhr.send();
            return editorCode;
            break;
        case 4:
        case 5:
            if (expandedView == 2) {
               // document.querySelector(".splitWrapper").classList.remove("expandedView");
                
            }
            //if (document.querySelector(".split.right3")) document.querySelector(".split.right3").classList.add("hidden");
            var sectionlinkElement = document.querySelector(".wrapperContainerPreview.full [name=enlace]");

            var iframe = document.createElement("iframe");
            iframe.src = "";
            if (view){
                view.innerHTML = "";
                view.appendChild(iframe);
            }
            if (!sectionlinkElement.value){
                swal("Espera","Antes de visualizar debes guardar los cambios. La próxima vez te prometo que intentaré dejarte verlo","warning");
                if (myConfig.length){
                    var changed = false;
                    for (cont in myConfig){
                        if (myConfig[cont].isActive) {
                            myConfig[cont].clickHandler();
                            changed = true;
                        }
                    }
                    if (!changed) toggleEditTabs(2,false);
                }else{
                    toggleEditTabs(2,false);
                }
                return false;
            }
            if (needToSave(true,true)){
                
                await swal({
                    title:"Espera!",
                    text:"Necesitas guardar los cambios para poder verlos. Deseas guardar ahora?",
                    icon:"warning",
                    buttons:{
                      cancel: {
                        text: "No",
                        value: null,
                        visible: true,
                        className: "btn btn-default",
                        closeModal: true,
                      },
                      confirm: {
                        text: "SI",
                        value: true,
                        visible: true,
                        className: "btn btn-primary",
                        closeModal: true
                      }
                    }
                }).then((value) => {
                    if (value){
                        if (NUM) saveRecord(false,false); else saveRecord(true,false);    
                    }else{
                        if (myConfig.length){
                            var changed = false;
                            for (cont in myConfig){
                                if (myConfig[cont].isActive) {
                                    myConfig[cont].clickHandler();
                                    changed = true;
                                }
                            }
                            if (!changed) toggleEditTabs(2,false);
                        }else{
                            toggleEditTabs(2,false);
                        }
                        return false;
                    }
                });
            }
            if (sectionlinkElement){
                var link = sectionlinkElement.value;
                console.log(sectionlinkElement);    
            }else{
                var link = "/";
            }
            
            iframe.src = websiteDomain + link + "?pruebas=1";
            if(index == 5) {
                window.open(iframe.src, '_blank');
                return;
            }
            return iframe;
            break;
    }
    
    return false;
}
function _openModal(href){
    document.body.classList.add('no-scroll');
            
    const currentModal = document.getElementById('modal-full');
    if (currentModal) currentModal.parentNode.removeChild(currentModal);
    const div = document.createElement('div');
    div.id = 'modal-full';

    const iframe = document.createElement('iframe');
    iframe.src = href;
    div.appendChild(iframe);
    iframe.onload = () => {
        div.classList.add('opened');
        stopLoading();
    };

    document.body.appendChild(div);
}

function reloadIframe(){};

function closeGalleryBuilder(nodeId){
    for (const cont in myConfig){
        if (myConfig[cont].isActive){
            if (myConfig[cont].fields && myConfig[cont].fields.postDOMfields &&  myConfig[cont].fields.postDOMfields[nodeId]){
                myConfig[cont].fields.postDOMfields[nodeId].vueInstance.reloadData(true);
            }
        }        
    }
}
function toggleFullPreview(objeto){
    if (myConfig.length == 0) return;
    myConfig[0]._removeAllActive();
    if (objeto && !objeto.classList.contains("active")){
        objeto.classList.add("active");
    }
    toggleEditTabs(4,false,objeto);
}
async function linkToWeb(objeto){
    var sectionlinkElement = splitRight.querySelector(".wrapperContainerPreview.full [name=enlace]");
    if (!sectionlinkElement.value){
        swal("Espera!","Antes de visualizar debes guardar los cambios. La próxima vez te prometo que intentaré dejarte verlo","warning");
        toggleEditTabs(0,true);
        return false;
    }

    if (needToSave(true,true)){

        await swal({
            title:"Espera!",
            text:"Necesitas guardar los cambios para poder verlos. Deseas guardar ahora?",
            icon:"warning",
            buttons:{
              cancel: {
                text: "No",
                value: null,
                visible: true,
                className: "btn btn-default",
                closeModal: true,
              },
              confirm: {
                text: "SI",
                value: true,
                visible: true,
                className: "btn btn-primary",
                closeModal: true
              }
            }
        }).then((value) => {
            if (value){
                if (NUM) saveRecord(false,false); else saveRecord(true,false);    
            }else{
                toggleEditTabs(0,true);
                return false;
            }
        });
    }

    
    var link = sectionlinkElement.value + "?pruebas=1";
    
    window.open(websiteDomain + link);
}

function saveCSS(data){
    xhr2 = new XMLHttpRequest();
    var url = "admin.php?menu="+MENU+"&action=edit&setCSS=" + activeModule.builder.id;
    var param = 'data=' + Base64.encode(data);;
    xhr2.open("POST",url,true);
    xhr2.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr2.onload = (respond) => {
        console.log(respond.target.response);
    };
    xhr2.send(param);
}

async function saveRecord(returnList = false,returnModal = true){
    await save();
    
    var form = document.querySelector(".split.right .editor FORM");
    
    var codigos = document.querySelectorAll(".split.right .textarea-codigo");
    for (codigo of codigos){
        codigo.value = Base64.encode(codigo.value);
    }
    
    var url = form.action,
        xhr = new XMLHttpRequest();
    
    var formData = new FormData(form);

    if (window.lastGroupId){
        formData.append("__lastGroupId",window.lastGroupId);
        window.lastGroupId = null;
    }
    
    xhr.open("POST", url);
    
    xhr.onload = (data) => {
        for (codigo of codigos){
            codigo.value = Base64.decode(codigo.value);
        }
        if (!data.target.responseText){
            for(modAux of myConfig){
                if (modAux.isActive) saveModule(modAux.builder.id);
            }
            needToSave(false);
            if (!returnList){
                if (returnModal){
                    
                    swal({
                        title:"OK!",
                        text:"'El registro ha sido guardado'",
                        icon:"success",
                        button : false,
                        timer:500
                    });
                }
                if (indexEditTab == 1 || indexEditTab == 4){
                    var iframes = splitRight.querySelectorAll("iframe");
                    for (iframe of iframes){
                        iframe.src = iframe.src;
                    }

                    
                }
            }else{
                buttonSaveAndExitPressed = true;
                document.location.href = "admin.php?menu="+MENU+"&saved=1";
            }
            if (document.querySelector(".split.right2")){
                var frame = document.getElementById("frame");
                frame.src = PREVIEW_URL;
                frame.addEventListener("load",() => {
                    
                    var index = myConfig.find(rec => rec.isActive === true) ? myConfig.findIndex(rec => rec.isActive === true) : -1;
                    scrollIframeToIndex(index);
                })
                
                
            }
        }else{
            swal("Error!",data.target.responseText,"warning");
        }
        
    };
    xhr.send(formData);
}
async function saveModule(module){
    xhr2 = new XMLHttpRequest();
    var url = "admin.php?menu="+MENU+"&action=edit&saveModule=" + module;
    xhr2.open("POST",url,true);
    xhr2.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr2.onload = (respond) => {
        console.log(respond.target.response);
    };
    xhr2.send();
}
function loadEditPrevious(){
    editNode = document.createElement("div");
    var formEditNode = document.createElement("form");
    formEditNode.method = "POST";
    formEditNode.action = "/admin.php";

    var inputElement = document.createElement("input");
    inputElement.type = "hidden";
    inputElement.setAttribute("value",MENU);
    inputElement.name = "menu";
    formEditNode.appendChild(inputElement);

    var inputElement = document.createElement("input");
    inputElement.type = "hidden";
    inputElement.setAttribute("value",PRESAVETEMPID);
    inputElement.name = "preSaveTempId";
    formEditNode.appendChild(inputElement);

    var inputElement = document.createElement("input");
    inputElement.type = "hidden";
    inputElement.setAttribute("value","save");
    inputElement.name = "_defaultAction";
    formEditNode.appendChild(inputElement);


    for (const schemaField in SCHEMA){
        if (!SCHEMA[schemaField]) continue;
        if (SCHEMA[schemaField].type && SCHEMA[schemaField].type !== 'upload'){
            var inputElement = document.createElement("input");
            switch(SCHEMA[schemaField].type){
                case "date":
                    var datesNode = document.createElement("div");
                    
                    var array = ["year","mon","day",'hour','min','sec'];
                    var fecha = new Date();
                    var arrayData = ['YYYY','MM','DD','HH','mm','ss'];
                    for (const arr in array){
                        var datesNodeMon = document.createElement("input");
                        datesNodeMon.type = "hidden";
                        datesNodeMon.setAttribute("name",schemaField + ":" + array[arr]);
                        datesNodeMon.setAttribute("value",APARTADO_DATA[schemaField] ? moment(APARTADO_DATA[schemaField]).format(arrayData[arr]) : moment().format(arrayData[arr]));
                        datesNode.appendChild(datesNodeMon);
                    }
                    formEditNode.appendChild(datesNode);
                    inputElement.type = "hidden";
                    inputElement.setAttribute("value",APARTADO_DATA[schemaField] ? APARTADO_DATA[schemaField] : "");
                    break;
                case "checkbox":
                case "parentCategory":
                    inputElement.type = "hidden";
                    inputElement.setAttribute("value",APARTADO_DATA[schemaField] ? APARTADO_DATA[schemaField] : "0");
                    break;    
                default:
                    inputElement.type = "hidden";
                    inputElement.setAttribute("value",APARTADO_DATA[schemaField] ? APARTADO_DATA[schemaField] : "");
            }

            inputElement.name = schemaField;
            formEditNode.appendChild(inputElement);
        }
    }
    editNode.appendChild(formEditNode);
    
    var spinner = document.createElement("div");
    spinner.classList.add("spinner");
    var cube1 = document.createElement("div");
    cube1.classList.add("cube1");
    spinner.appendChild(cube1);
    var cube2 = document.createElement("div");
    cube2.classList.add("cube2");
    spinner.appendChild(cube2);
    
    editNode.appendChild(spinner);
    
    checkIfNewRecord();
    externalViews(2);
    
}
function loadEdit(force){
    if (editLoaded) return;
    
    if (!force){
        loadEditPrevious();
        return;
    }else if (force && splitRight.querySelector("#page-content")){
        return;
    }
    
    
    var time = new Date(),
        params = "menu=" + MENU + "&action=edit&num=" + NUM + "&getEdit=1&" + time.getTime(),
        xhr = new XMLHttpRequest();
    
    
    xhr.open("POST", "admin.php",true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onload = (data) => {
        
        data = data.target.responseText;
        
        var parser = new DOMParser();
        var doc = parser.parseFromString(data, "text/html");
        
        var element = doc.querySelector("#page-content"); 
        
        if (!element) return;
        
        var scriptsNodes = element.getElementsByTagName("script");
        if (scriptsNodes){
            for (const scriptNode of scriptsNodes){
                if (scriptNode.text.indexOf("nuevo_registro") <= -1) continue;
                eval(scriptNode.text);
                
                var multicampoNodes = element.querySelectorAll(".multicampo");
                if (multicampoNodes){
                    for (const multicampoNode of multicampoNodes){
                        var clase = multicampoNode.getAttribute("class").replace("multicampo","").trim();
                        multicampoNode.querySelector(".btn.suma").removeAttribute("onclick");
                        multicampoNode.querySelector(".btn.suma").addEventListener("click",(e) => { nuevo_registro(`.${clase}`); });
                        
                        var filas = multicampoNode.querySelectorAll(".fila");
                        if (filas){
                            for (const fila of filas){
                                var claseFila = fila.getAttribute("class").replace("fila","").trim();
                                var eliminarNodes = fila.querySelectorAll(".btn.eliminar");
                                if (eliminarNodes){
                                    for (eliminarNode of eliminarNodes){
                                        eliminarNode.removeAttribute("onclick");
                                        eliminarNode.addEventListener("click",(e) => { 
                                            fila.parentNode.removeChild(fila);
                                            parsea_valores(`.${clase}`);
                                        });
                                    }
                                }
                            }
                        }
                    }
                } 
                
                
            }
        }
        
        var creadoPor = element.querySelector(".creadopor");
        creadoPor.parentNode.removeChild(creadoPor);
        
        var iframes = element.querySelectorAll(".form-group iframe");
        for (iframe of iframes){
            
            var objeto = iframe.parentNode.parentNode;
            if (iframe.id == "galeria_de_fotos_iframe" || iframe.id == "archivos_adjuntos_iframe"){
                objeto.classList.add("builder");
                iframe.parentNode.removeChild(iframe);    
            }
            
        }
        
        var iframes = element.querySelectorAll("[name='builder'],.ckeditor");
        for (iframe of iframes){
            var objeto = iframe.parentNode.parentNode;
            objeto.classList.add("builder");
        }
        
        var selects = element.querySelectorAll(".select-select2");
        for (select of selects){
            select.classList.remove("select-select2");
            select.classList.add("form-control");
        }
        
        // SELECCION DE FECHA
        if ($(element).find('.input-datepicker-close').length) $(element).find('.input-datepicker-close').datepicker({weekStart: 1}).on('changeDate', function(e){ $(this).datepicker('hide'); });
        if ($(element).find('.input-timepicker').length) $(element).find('.input-timepicker').timepicker({ minuteStep: 1,showSeconds: true,showMeridian: false });
        
        $(element).find(".fecha").each(function(){
            var mes = $(this).find("[name*=':mon']").val();
            var dia = $(this).find("[name*=':day']").val();
            var ano = $(this).find("[name*=':year']").val();
            var hora = $(this).find("[name*=':hour']").val();
            var minuto = $(this).find("[name*=':min']").val();
            var segundo = $(this).find("[name*=':sec']").val();
            var tarde = $(this).find("[name*=':isPM'] option:selected").html();
            var objeto = this;

            $(this).find(".input-datepicker-close").val(dia+"/"+mes+"/"+ano).change(function(){
                var sep = $(this).val().split("/");

                $(objeto).find("[name*=':day']").val(parseInt(sep[0]));
                $(objeto).find("[name*=':mon']").val(parseInt(sep[1]));
                $(objeto).find("[name*=':year']").val(parseInt(sep[2]));

            });
            $(this).find(".input-timepicker").val(hora+":"+minuto+":"+segundo+" "+tarde).change(function(){
                //var sepa = $(this).val().split(" ");
                var sep = $(this).val().split(":");
                $(objeto).find("[name*=':hour']").val(parseInt(sep[0]));
                $(objeto).find("[name*=':min']").val(parseInt(sep[1]));
                $(objeto).find("[name*=':sec']").val(parseInt(sep[2]));
            });


        });
        /*console.log(selects);*/
        
        var wysiwygNodes = element.querySelectorAll("[data-translate-type='wysiwyg']");
        if (wysiwygNodes){
            
            for (wysiwygNode of wysiwygNodes){
                CKEDITOR_COCO_Start(null,[wysiwygNode],(resultData) => {
                    wysiwygNode.innerHTML = resultData;
                    needToSave();
                });
            }
        }
        
        var formActions = element.querySelector(".form-actions");
        formActions.parentNode.removeChild(formActions);
        
        element.addEventListener("change",function(){
            needToSave();
        });
        
        
        
        editNode = element;
        
        var readonly = element.querySelectorAll("[data-disable-readonly]");
        for (read of readonly){
            read.removeAttribute("readonly");
        }        
        
        checkIfNewRecord();
        
        document.getElementById("nombrePagina").innerHTML = element.querySelector("#page-content [name='"+CAMPO_TITLE+"']").value;
        
        externalViews(2);
        resizeIframe();
        
        // Para abrir el modal de uploads en el tab de configuración
        document.querySelectorAll("#page-content .form-group iframe + br + div > .btn-primary.open-modal").forEach((anchor) => {
            anchor.addEventListener("click", (event) => {
                event.preventDefault();
		startLoading();
                _openModal(anchor.href);
            });
        });

        /*initThumbnailLoading();*/
        
    };
    xhr.send(params);
}

function checkIfNewRecord(){
    
    if (myConfig.length === 0) {
        var botonConfiguracion = document.querySelector('[data-toggle-tab="2"]');
        botonConfiguracion.classList.add("active");
        toggleEditTabs(2,false);

        if (!parseInt(CURRENT_USER["licencia"])){
            return false;
        }


        var num = splitRight.querySelector("[type=hidden][name=num]");
        if (!num.value){
            swal({
                title:"Pon un nombre a la sección:", 
                content: "input",
                closeOnClickOutside:false,
                button : {
                    text: "OK",
                    value: true,
                    visible: true,
                    className: "btn btn-primary",
                    closeModal: false,
                }
            })
            .then((value) => {
                if (value){
                    var nombre = splitRight.querySelector("#page-content [name='"+CAMPO_TITLE+"']");
                    if (!nombre) swal("Error","El nombre no existe","warning");
                    nombre.value = value;
                    document.getElementById("nombrePagina").innerHTML = value;
                    swal.close();
                    if (myConfig.length === 0) toggleNewModuleModal();
                }
            });
        }
    }

    
}

/*function initThumbnailLoading(){
    console.log("Inicio thumbnail");
    var modulesBloqueNodes = document.querySelectorAll("li.bloque:not([data-no-module])");
    let sectionlinkElement = splitRight.querySelector(".wrapperContainerPreview.full [name=enlace]");
    
    for (const bloqueNode of modulesBloqueNodes){
        
        let image = bloqueNode.querySelector("img[data-thumb-url]");
        let domain = image.getAttribute("data-domain");
        let thumbUrl = image.getAttribute("data-thumb-url");
        let urlImage = "lib/plugins/builder_saas/dynamicImage.php?th=1";
        let module = image.getAttribute("data-module");
        
        if (sectionlinkElement && sectionlinkElement.value){
            urlImage += "&url=" + Base64.encode(sectionlinkElement.value);
        }
        if (domain){
            urlImage += "&domain=" + Base64.encode(websiteDomain);
        }
        if (thumbUrl){
            urlImage += "&thumburl=" + Base64.encode(thumbUrl);
        }
        if (module){
            urlImage += "&module=" + Base64.encode(module);
        }
        if (REFRESH_THUMBS){
            urlImage += "&refresh=1";
        }
        image.src = urlImage;
    }
    
}*/

function removeModules(){
    
    removeIsActive = splitLeft.parentNode.parentNode.classList.contains("removeActive");
    if (removeIsActive) 
        splitLeft.parentNode.parentNode.classList.remove("removeActive"); 
    else
        splitLeft.parentNode.parentNode.classList.add("removeActive"); 
}
/* JSON */
function parseJson(string = true){
    var objects = jsonResult.objects;
    /*var res = {
        objects: jsonResult.objects.map(e => e.object())
    };*/
    if (!string) return res;
    return JSON.stringify(jsonResult);
}
function array_move(arr, old_index, new_index) {
    if (new_index >= arr.length) {
        var k = new_index - arr.length + 1;
        while (k--) {
            arr.push(undefined);
        }
    }
    arr.splice(new_index, 0, arr.splice(old_index, 1)[0]);
    return arr; // for testing
};
function insertAfter(newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}
async function comprueba_idiomas_vista(){
    
    var grupos = splitRight.querySelectorAll(".group-component");
    if (!grupos && !grupos.length) return false;
    
    for (grupo of grupos){
        var campos = grupo.querySelectorAll("[data-translate]");
        if (!campos || !campos.length) continue;

        var groupRecord = {
            tableName : null,
            recordNum : null
        }
        for (campo of campos){
            if (campo.parentNode.classList.contains("premundo")) continue;
            record = {
                fieldName: campo.getAttribute("name"),
                tableName: campo.getAttribute("data-tr-table"),
                recordNum: campo.getAttribute("data-tr-num"),
                relationsFieldName: campo.getAttribute("data-tr-relation-fieldname"),
                preSaveTempId: "",
                type: campo.getAttribute("data-tr-type")
            };
            groupRecord.tableName = record.tableName;
            groupRecord.recordNum = record.recordNum;
            
            var wrapperTranslate = document.createElement("div");

            wrapperTranslate.classList.add("premundo","vacio");

            var link = document.createElement("div");
            link.classList.add("mundo");
            
            var a = document.createElement("a");
            a.href = `?menu=${record.tableName}&action=translateModify&relationsFieldName=${record.relationsFieldName}&fieldName=${record.fieldName}&num=${record.recordNum}&type=${record.type}&TB_iframe=true&width=900&height=600&modal=true`;
            a.classList.add("thickbox");
            a.innerHTML = `<i class='fa fa-globe'></i>`;
            link.appendChild(a);
            
            wrapperTranslate.appendChild(link);

            campo.parentNode.insertBefore(wrapperTranslate, campo);
            wrapperTranslate.appendChild(campo);
            
            tb_init('a.thickbox');

        }
        /*
        await Rest.get("traducciones", `tableName="${groupRecord.tableName}" and recordNum="${groupRecord.recordNum}"`, '', 1000, false,true)
            .then(records => {
                if (records.error) {
                    alert(records.error);
                    return false;
                }
                var datos = records.data;
                
                for (let record of datos){
                    var campo = grupo.querySelector(`[name='${record.fieldName}']`);
                    
                    if (campo && campo.parentNode && campo.parentNode.classList.contains("vacio")) campo.parentNode.classList.remove("vacio");
                }
            });*/
    }
        
    
}
async function comprueba_idiomas() {
    // AUN EN JQUERY HAY QUE PASARLO
    
    tengoIdiomas = false;
    
    for (idioma in IDIOMAS){
        if (IDIOMAS[idioma] !== "www") tengoIdiomas = true;
    }
    
    if (!tengoIdiomas) return;
    
    /*var iframes = splitRight.querySelectorAll("iframe");
    for (iframe of iframes){
        iframe.src = iframe.src;
    }*/
    
    var wrapperEditor = splitRight.querySelector(".full.editor");
    if (wrapperEditor.classList.contains("hidden")) {
        comprueba_idiomas_vista();
        return false;
    }
    
    var campos = wrapperEditor.querySelectorAll("[data-translate]");
    for (campo of campos){
        if (!campo.parentNode.classList.contains("premundo")) {
            campo.parentNode.classList.add("premundo","vacio");
        }else{
            var mundoHijos = campo.parentNode.querySelectorAll(".mundo");
            for (let mundoHijo of mundoHijos){
                campo.parentNode.removeChild(mundoHijo);
            }
        }
        
        record = {
            fieldName: campo.getAttribute("data-translate-field"),
            tableName: wrapperEditor.querySelector("[name=menu]").value,
            recordNum: wrapperEditor.querySelector("[name=num]") ? wrapperEditor.querySelector("[name=num]").value : "",
            preSaveTempId: wrapperEditor.getAttribute("[name=preSaveTempId]") ? wrapperEditor.getAttribute("[name=preSaveTempId]").value : "",
            type: campo.getAttribute("data-translate-type")
        };
        
        drawWorldTranslate(record,campo);
    }
    if (MENU && NUM && myConfig.length){
        await Rest.get("traducciones", 'tableName="' + MENU + '" and recordNum="' + NUM + '"', '', 1000, false,true)
            .then(records => {
                if (records.error) {
                    alert(records.error);
                    return false;
                }
                var datos = records.data;
                for (let record of datos){
                    
                    var campo = wrapperEditor.querySelector(`[data-translate-field='${record.fieldName}']`);
                    
                    if (!campo) continue;
                    if (campo.parentNode.classList.contains("premundo") && !campo.parentNode.classList.contains("vacio")) continue;
                    if (campo.parentNode.classList.contains("premundo") && campo.parentNode.classList.contains("vacio")){
                        campo.parentNode.classList.remove("vacio");
                    }
                    record["type"] = campo.getAttribute("data-translate-type");
                    
                    drawWorldTranslate(record,campo);    
                    
                }
            });
    }
    tb_init('a.thickbox, area.thickbox, input.thickbox');
    
}
function drawWorldTranslate(data,campo){
    if(data["preSaveTempId"] == 'null' || data["preSaveTempId"] == null) data["preSaveTempId"] = '';
    var modifyUrl = "?menu=" + MENU +
        "&action=translateModify" +
        "&fieldName=" + data["fieldName"] +
        "&num=" + data["recordNum"] +
        "&preSaveTempId=" + data["preSaveTempId"] +
        "&type=" + data["type"] +
        "&TB_iframe=true&width=900&height=600&modal=true";

    var posicion = "top:0px;right:20px;";
    if (data["type"] == "wysiwyg") posicion = "top:17px;right:20px;";
    if (data["type"] != "textfield") posicion = "top:20px;right:20px;";
    if (data["type"] == "codigo") posicion = "top:3px;right:3px;";
    if (data["type"] == "multitext") posicion = "top:auto;bottom:7px;right:20px;";
    if (data["type"] == "multitextv2") posicion = "top:auto;top:30px;right:35px;";
    
    campo.insertAdjacentHTML('beforebegin', `
        <div class='mundo' style='position:absolute;${posicion}font-size:24px;z-index:100;'>
            <a href='${modifyUrl}' class='thickbox'>
                <i class='fa fa-globe'></i>
            </a>
        </div>
    `); 
}

function loadJS(url, implementationCode, locationNode){
    var scriptTag = document.createElement('script');
    scriptTag.src = url;

    scriptTag.onload = implementationCode;
    scriptTag.onreadystatechange = implementationCode;

    locationNode.appendChild(scriptTag);
}
    
function changeViewExpanded(state = null){
    if (state) {
        expandedView = state;
    }else if (expandedView == 1){
        expandedView = 2;
    }else {
        expandedView = 1;
    }

    localStorage.setItem("expandedView",expandedView);

    if (expandedView == 2) {
        document.querySelector(".splitWrapper").classList.add("expandedView");
    }else{
        document.querySelector(".splitWrapper").classList.remove("expandedView");
    }
}
