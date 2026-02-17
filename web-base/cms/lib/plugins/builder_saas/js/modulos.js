class Module {
    constructor(moduleData={},splits = {},availableModules = false, loadFields = true) {
        this.label = moduleData.builder.label;
        this.isAvailableModule = availableModules;
        this.isEditableModule = moduleData.builder.editable ? true : moduleData.isEditableModule ? true : false;
        this.isOnlyForAdmin = moduleData.builder.onlyAdminModule ? moduleData.builder.onlyAdminModule : moduleData.isOnlyForAdmin ? moduleData.isOnlyForAdmin : false;
        this.requiredPlugins = moduleData.builder.requiredPlugins ? moduleData.builder.requiredPlugins : '';
        this.isMJML = moduleData.builder.MJMLModule ? moduleData.builder.MJMLModule : moduleData.isMJML ? moduleData.isMJML : false;
        this.builderVue = parseBuilderModuleRootURL(moduleData.builder.builderVue ? moduleData.builder.builderVue : null);
        this.loadFields = loadFields;
        this.fields = null;
        this.builder = {
            id:moduleData.builder.id,
            path:parseBuilderModuleRootURL(moduleData.builder.path),
            thumbnail : moduleData.builder.thumbnail,
            tables : moduleData.builder.tables,
            label:moduleData.builder.label,
            description:moduleData.builder.description,
            vars:moduleData.builder.vars,
            htmlData:moduleData.builder.htmlData ? moduleData.builder.htmlData : null,
            styleData:moduleData.builder.styleData ? moduleData.builder.styleData : null,
            javascriptData:moduleData.builder.javascriptData ? moduleData.builder.javascriptData : null 
        };
        this.special = moduleData.builder.special;
        this.general = moduleData.builder.general;
        this.viewPort = {
            splitsView : splits,
            splitLeft : splits.left,
            splitRight : splits.right
        }
        this.urlUpload = TEMPLATE + "/dynamicImage.jpg?uploadFoto=1";
        this.data = moduleData.data;
        this.referenciada = moduleData.referenciada;
        this.oculto = moduleData.oculto ? true : false;
        this.section_id = moduleData.section_id;

        this.referencias = moduleData.referencias;
        this.isActive = false;
        this.allFields = [];
        
//        console.log(this.referencias)
        this.init();
    }
    /**
     * Inicializa el nodo para ser utilizado
     */
    init(){
        this.node = document.createElement("li");
        this.node.classList.add('bloque');
        if (this.oculto) this.node.classList.add('oculto');
        this.node.setAttribute('draggable', true);
        
        if (this.animation) {
            this.node.classList.add('wow');
            this.node.classList.add(this.animation);
            this.addClass('wow');
            this.addClass(this.animation);
        }
                
        var remove = document.createElement('i');
        remove.setAttribute("title","Eliminar módulo");
        remove.classList.add(...['fa','fa-remove']);
        this.node.appendChild(remove);
        
         
        
        var locked = document.createElement('i');
        locked.classList.add(...['fa','fa-lock']);
        if (this.referenciada) locked.classList.add("visible");
        this.node.appendChild(locked);
        
        var visible = document.createElement('i');
        visible.classList.add(...['fa','fa-eye-slash']);
        if (this.oculto) visible.classList.add("visible");
        this.node.appendChild(visible);
        
        var adminOnly = document.createElement('i');
        adminOnly.classList.add(...['fa','fa-key']);
        if (this.isOnlyForAdmin) adminOnly.classList.add("visible");
        this.node.appendChild(adminOnly);
        
        var addButton = document.createElement('i');
        addButton.classList.add(...['fa','fa-plus']);
        if (this.isAvailableModule) addButton.classList.add("visible");
        addButton.addEventListener("click",(e) => {
            var newModule = JSON.parse(JSON.stringify(this));
            newModule = new Module(newModule,this.viewPort.splitsView);

            newModule.add(this.newPositionItem);
            myConfig.push(newModule);
            for (let myConf of myConfig){
                if (myConf.isActive) {
                    myConf.renderEditView();
                }
            }
            setController();
            needToSave();
        });

        //if (CURRENT_USER["isAdmin"]){
        // DUPLICACION DE MODULOS
        var duplicateModule = document.createElement("i");
        duplicateModule.classList.add("fa","fa-clone");
        duplicateModule.setAttribute("title","Duplicar módulo");

        duplicateModule.addEventListener("click",(e) => {
            swal({
                    title:"Duplicar el módulo",
                    text:"¿Deseas duplicar este módulo en tu landing?",
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
                        let newModule = {...this};
                        //let newModule = Object.assign({}, this)
                        // Anael: Eliminamos las referencias a elementos del DOM que son los que provocan el error de referencia circular al hacer el JSON.stringify
                        
                        delete newModule.fields;
                        delete newModule.node;

                        newModule.data = {...this.data};

                        var sepSection = newModule.section_id.split("_");
                        newModule.section_id = sepSection[0] + "_" + (sepSection[1] ? (parseInt(sepSection[1])+1) : 1)

                        newModule = JSON.parse(JSON.stringify(newModule));

                        newModule = new Module(newModule,this.viewPort.splitsView);

                        this.cleanDuplicateVars(newModule.fields.vars,newModule.data);

                        newModule.add(myConfig.indexOf(this)+1);

                        console.log(newModule,this);

                        myConfig.splice(myConfig.indexOf(this)+1,0,newModule);

                        for (let myConf of myConfig){
                            if (myConf.isActive) {
                                myConf.renderEditView();
                            }
                        }
                        setController();
                        needToSave();
                    }
                })

            
        })
        
        this.node.appendChild(duplicateModule);
        // HASTA AQUI LA DUPLICACION
        //}

        // Si el módulo es externo se lo mando a la web
        if (!this.isAvailableModule && this.builder.path.indexOf(CURRENT_USER["domain"]["domain"]) <= -1) {
            var url = new URL(this.builder.path);
            //this.sendModuleToWeb(url.host);
            // AJuste para webs en carpetas
            if (CURRENT_USER["domain"]["domain"].indexOf("/") > -1){
                console.log("test 2");
                this.sendModuleToWeb(CURRENT_USER["domain"]["domain"]);
            }else{
                this.sendModuleToWeb(url.host);
                console.log("test");
            }
        }

        this.node.appendChild(addButton);
        
        /*if (CURRENT_USER["isAdmin"] && !ACAI_REFERER){
            var editableButton = document.createElement('i');
            editableButton.classList.add(...['fa','fa-code']);
            if (this.isEditableModule) editableButton.classList.add("visible");
            editableButton.addEventListener("click",(e) => {
                crearModulo(this);
            });
        
            this.node.appendChild(editableButton);
        }
        if (CURRENT_USER["isAdmin"] && !ACAI_REFERER){
            var previewModulo = document.createElement('i');
            previewModulo.classList.add(...['fa','fa-safari']);
            previewModulo.classList.add("visible");
            previewModulo.addEventListener("click",(e) => {
                crearModulo(this,true);
            });

            this.node.appendChild(previewModulo);
        }*/
        
        var image = document.createElement('img');
        // image.style.backgroundColor = this.stringToColour(this.builder.id);
        let domain = websiteDomain;
        let thumbUrl = this.builder.path + "/" + this.builder.thumbnail + '?v=' + APARTADO_DATA.updatedDate;

        if (this.data.layout && this.data.layout.newValues && this.data.layout.newValues.builder_custom && typeof this.data.layout.newValues.builder_custom.value != 'undefined'){
            thumbUrl = this.builder.path + "/thumbs/thumbnail_" + this.data.layout.newValues.builder_custom.value + '.jpg?v=' + APARTADO_DATA.updatedDate;
            // console.log(thumbUrl);
        }

//        let urlImage = "lib/plugins/builder_saas/dynamicImage.jpg?th=1";
        let module = this.builder.id;
        
        
//        if (APARTADO_LINK){
//            urlImage += "&url=" + Base64.encode(APARTADO_LINK);
//            this.urlUpload += "&url=" + Base64.encode(APARTADO_LINK);
//        }
//        if (domain){
//            urlImage += "&domain=" + Base64.encode(websiteDomain);
//            this.urlUpload += "&domain=" + Base64.encode(websiteDomain);
//        }
//        if (thumbUrl){
//            urlImage += "&thumburl=" + Base64.encode(thumbUrl);
//            this.urlUpload += "&thumburl=" + Base64.encode(thumbUrl);
//        }
//        if (module){
//            urlImage += "&module=" + Base64.encode(module);
//            this.urlUpload += "&module=" + Base64.encode(module);
//        }
//        if (REFRESH_THUMBS){
//            urlImage += "&refresh=1";
//        }
        image.src = "lib/plugins/builder_saas/images/puzzle.svg";
        image.classList.add("lazyload");
        let urlImage = thumbUrl;

        image.setAttribute("data-src",urlImage);

        
        
        
        /*if (this.builder.thumbnail) {
            var testImage = new Image();
            var path = this.builder.path + "/" + this.builder.thumbnail;
            testImage.onload = function(e) {
                image.src = testImage.src;
            }
            testImage.onerror = function(e){
                e.preventDefault();
                image.src = "lib/plugins/builder_saas/images/puzzle.svg";
                window.addEventListener('load', function () {
                    var sectionlinkElement = splitRight.querySelector(".wrapperContainerPreview.full [name=enlace]");
                    
                    if (typeof sectionlinkElement.value != "null"){
                        image.src = "lib/plugins/builder_saas/dynamicImage.php?url="+Base64.encode(websiteDomain+sectionlinkElement.value+"?pruebas=1&onlyModule="+testImage.id);
                    }
                    
                },false);
            }
            testImage.id = this.builder.id;
            testImage.src = this.builder.path + "/" + this.builder.thumbnail;
            
        }*/
        this.node.appendChild(image);
        
        if (this.special){
            // Anael: Eliminado esto porque estaba generando archivos relativos al CMS dando un 403
            /*
            var image = document.createElement('img');
            image.classList.add("lazyload","infoImage");
            image.src = "lib/plugins/builder_saas/modulos/"+module+"/thumbnail_info.jpg";
            this.node.appendChild(image);
            */
        }        
        
        if (CURRENT_USER["isAdmin"]){
            var uploadFoto = document.createElement("i");
            uploadFoto.classList.add("fa","fa-upload","fa-uploadfoto");
            this.uploadFoto(uploadFoto,image);
            this.node.appendChild(uploadFoto);
        } 
        
        var title = document.createElement('h4');
        title.innerHTML = this.builder.label;
        this.node.appendChild(title);
        
        var subtitle = document.createElement('p');
        subtitle.innerHTML = this.builder.description;
        this.node.appendChild(subtitle);
        
        if (!this.isAvailableModule && this.loadFields) {
            // Eliminamos el data_loaded de las keys si existe
            if (this.data){
                for (let key in this.data){
                    if (this.data[key] && typeof this.data[key].data_loaded !== 'undefined') delete this.data[key].data_loaded;
                }
            }
            this.fields = new Field(this);
        }
        
    }

    getID(){
        return '_' + Math.random().toString(36).substr(2, 9);
    }

    cleanDuplicateVars(fieldVars,newModule){
        // fieldVars = newModule.fields.vars
        for (const variable in fieldVars){
            
            var data = newModule[variable];
            var schemaData = fieldVars[variable];

            var varNewId = this.getID();
            
            switch(schemaData.type){
                case "multi":
                    for (const i in data){
                        this.cleanDuplicateVars(schemaData.vars,data[i])
                    }
                    break;
                case "uploadV2":


                    var imageNums = [];

                    for (const postDOMFieldKey in this.fields.postDOMfields){
                        
                        if (this.fields.postDOMfields[postDOMFieldKey].data.recordNum && this.fields.postDOMfields[postDOMFieldKey].data.recordNum != data.recordNum) continue;
                        if (this.fields.postDOMfields[postDOMFieldKey].data.preSaveTempId && this.fields.postDOMfields[postDOMFieldKey].data.preSaveTempId != data.preSaveTempId) continue;

                        if (this.fields.postDOMfields[postDOMFieldKey].vueInstance && this.fields.postDOMfields[postDOMFieldKey].vueInstance.images){

                            imageNums = this.fields.postDOMfields[postDOMFieldKey].vueInstance.images.map(r => r.num);
                        }
                        
                    }

                    data.preSaveTempId = varNewId;

                    for(const imageNum in imageNums){
                        var dataImage = {
                            menu:'builder_custom',
                            fieldName:schemaData.relations.builder_custom,
                            preSaveTempId:varNewId,
                            duplicate:parseInt(imageNums[imageNum])
                        };
                        var url = "/lib/menus/modals/cutefile/index.php?" + new URLSearchParams(dataImage).toString();

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-type': 'application/json; charset=UTF-8'
                            }
                        }).then((response) => {
                            return response.json();
                        }).then((dataResponse) => {
                            console.log(dataResponse);
                        });
                        //console.log(url);
                    }
                    
                    break;
                default:
            }

            delete data.recordNum;
            if (data.newValues && data.newValues.builder_custom) delete data.newValues.builder_custom.recordNum;



        }
    }

    uploadFoto(element,imageElement){
        if (typeof Dropzone == 'undefined') return;
        var myDropzone = new Dropzone(element, { url: this.urlUpload});
        myDropzone.on("complete", function(file) {
            imageElement.src = imageElement.src;
        });
    }
    
    stringToColour(str) {
        var hash = 0;
        for (var i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        var colour = '#';
        for (var i = 0; i < 3; i++) {
            var value = (hash >> (i * 8)) & 0xFF;
            colour += ('00' + value.toString(16)).substr(-2);
        }
        return colour;
    }
    /**
     * Renderiza el nodo de nuevo para accionar los eventos
     * @param {bool} bind 
     */
    render(bind = false,refreshImage = false){
        this.label = this.builder.label;
        this.node.querySelector("h4").innerHTML = this.builder.label.indexOf("/") > -1 ? this.builder.label.split("/")[1].trim() : this.builder.label;
        this.node.querySelector("p").innerHTML = this.builder.description;
        if (CURRENT_USER["isSuperAdmin"]) this.node.querySelector("p").innerHTML = this.builder.description + " #" + this.section_id;
        if (refreshImage) this.node.querySelector("img").src = this.node.querySelector("img").src + "&time=" + new Date().getTime();
        
        if (bind) {
            var remove = this.node.querySelector(".fa-remove");
            remove.addEventListener('click', (e) => {
                if (!parseInt(CURRENT_USER["licencia"])){
                    App.noLicence();
                }else{
                    swal({
                        title:"Eliminar módulo",
                        text:"¿Deseas eliminar el módulo de tu landing?",
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
                            this.delete();
                            setController();
                            needToSave();
                            
                            return false;
                        }else{
                            
                            return false;
                        }
                    });
                }
                e.stopPropagation();
                e.preventDefault();
            });
            
            this.node.addEventListener('click', (e) => {
//                this.clickHandler(true,IS_MAIL_MARKETING ? false : true);
//                if (IS_MAIL_MARKETING) scrollIframeToIndex(myConfig.find(rec => rec === this) ? myConfig.findIndex(rec => rec === this) : -1);
                this.clickHandler(true,false);
                scrollIframeToIndex(myConfig.find(rec => rec === this) ? myConfig.findIndex(rec => rec === this) : -1);
            });
        }
        this.node.addEventListener("drag", (e) => {
            this.handleDrag(e); 
        });
        this.node.addEventListener("dragend", (e) => {
            this.handleDragDrop(e);
        });
    }
    
    /**
    * Evento Arrastrar
    * @param {node} item
    */
    handleDrag(item){
        if (!parseInt(CURRENT_USER["licencia"])){
            return false;
        }

        if (this.isAvailableModule){
            const selectedItem = item.target,
                  list = this.viewPort.splitLeft,
                  x = event.clientX,
                  y = event.clientY;

            const elementHover = document.elementFromPoint(x, y);

            var skeleton = list.querySelector(".skeleton");

            if (elementHover.parentNode === list || elementHover === list){

                if (!skeleton){
                    var skeleton = selectedItem.cloneNode(true);
                    skeleton.classList.add(...['bloque','skeleton','drag-sort-active']);
                    list.appendChild(skeleton);
                }

                let swapItem = document.elementFromPoint(x, y) === null ? skeleton : document.elementFromPoint(x, y);
                if (list === swapItem.parentNode) {
                    swapItem = swapItem !== skeleton.nextSibling ? swapItem : swapItem.nextSibling;
                    list.insertBefore(skeleton, swapItem);
                    this.resetMyConfigOrder(skeleton);
                }
            }else{
                var rect = this.viewPort.splitLeft.getBoundingClientRect();
                var inSplitView = (x>=rect.x && x<=rect.x + rect.width && y>=rect.y && y<=rect.y + rect.height) ? true : false;
                if (skeleton && !inSplitView){
                    skeleton.parentNode.removeChild(skeleton);
                }
            }
        }else{
            const selectedItem = item.target,
                  list = selectedItem.parentNode,
                  x = event.clientX,
                  y = event.clientY;

            selectedItem.classList.add('drag-sort-active');
            let swapItem = document.elementFromPoint(x, y) === null ? selectedItem : document.elementFromPoint(x, y);
            if (list === swapItem.parentNode) {
                swapItem = swapItem !== selectedItem.nextSibling ? swapItem : swapItem.nextSibling;
                list.insertBefore(selectedItem, swapItem);
                this.resetMyConfigOrder();
            }
        }
    }
    
    resetMyConfigOrder(selectedItem = null){
        var configAux = [];
        Array.prototype.map.call(this.viewPort.splitLeft.children, (element,index) => {
            if(selectedItem === element)
                this.newPositionItem = index;
            Array.prototype.map.call(myConfig,(elementAux) => {
                if (element === elementAux.node) {
                    configAux.push(elementAux);
                }
            });
        });
        if (!selectedItem) myConfig = configAux;
    }
    
    /**
    * Evento Soltar
    * @param {node} item
    */
    handleDragDrop(item) {
        if (!parseInt(CURRENT_USER["licencia"])){
            App.noLicence();
            return false;
        }

        const x = event.clientX,
              y = event.clientY;
        
        if (this.isAvailableModule){
            
            var rect = this.viewPort.splitLeft.getBoundingClientRect();
            if (x>=rect.x && x<=rect.x + rect.width && y>=rect.y && y<=rect.y + rect.height){
                
                
                var newModule = JSON.parse(JSON.stringify(this));
                newModule = new Module(newModule,this.viewPort.splitsView);
                
                newModule.add(this.newPositionItem);
                myConfig.push(newModule);
                // this.viewPort.splitLeft.children = array_move(this.viewPort.splitLeft.children, this.viewPort.splitLeft.children.length - 1, this.newPositionItem);
                
                myConfig = array_move(myConfig, myConfig.length - 1, this.newPositionItem);
                for (let myConf of myConfig){
                    if (myConf.isActive) {
                        myConf.renderEditView();
                    }
                }
                
            }
        }else{
            item.target.classList.remove("drag-sort-active");
            console.log(myConfig);
        }
        delete this.newPositionItem;
        
        setController();
        needToSave();
    }
    
    
    /**
     * Añade el nodo a la vista como hijo de un parent
     */
    add(newPositionItem) {
        if (!this.isAvailableModule){
            this.viewPort.splitLeft.appendChild(this.node);
            if(typeof newPositionItem !== 'undefined')
                this.viewPort.splitLeft.insertBefore(this.node, this.viewPort.splitLeft.children[newPositionItem])
            this.render(true);    
        }else if (this.special){
            
            var target = this.viewPort.splitRight.querySelector(".newWrapperContainer #especiales ul.list-modules");
            target.appendChild(this.node);
            this.render(false); 
        }else if (this.general && !this.isEditableModule){
            
            var target = this.viewPort.splitRight.querySelector(".newWrapperContainer #generales ul.list-modules");
            target.appendChild(this.node);
            this.render(false); 
        }else if (this.isMJML){
            if ((parseInt(CURRENT_USER["isAdmin"])) || (!parseInt(CURRENT_USER["isAdmin"]) && !this.isOnlyForAdmin)){
                var target = this.viewPort.splitRight.querySelector(".newWrapperContainer #mjml ul.list-modules");
                target.appendChild(this.node);
                this.render(false); 
            }
        }else if (this.isEditableModule){
            
            if ((parseInt(CURRENT_USER["isAdmin"])) || (!parseInt(CURRENT_USER["isAdmin"]) && !this.isOnlyForAdmin)){
                
                
                var target = this.viewPort.splitRight.querySelector(".newWrapperContainer #editables ul.list-modules");
                if (this.builder.label.indexOf("/") > -1){
                    var fileName = this.builder.label.substring(this.builder.label.indexOf("/") + 1).trim();
                    var folderName = this.builder.label.split("/")[0].trim();
                    var folderNodeExist = this.viewPort.splitRight.querySelector(".newWrapperContainer #editables ul.list-modules ul[data-folder='" + folderName + "']");
                    if (!folderNodeExist){
                        var liNode = document.createElement("ul");
                        liNode.classList.add("w-full","flex","flex-wrap","justify-start");
                        liNode.setAttribute("data-folder",folderName);
                        liNode.innerHTML = '<h3 class="w-full text-white text-3xl font-bold my-20">' + folderName + '</h3>';
                        target.appendChild(liNode);
                        folderNodeExist = liNode;
                    }
                    folderNodeExist.appendChild(this.node);
                }else{
                    target.appendChild(this.node);    
                }
                
                this.render(false); 
            }
        }else {

            var target = this.viewPort.splitRight.querySelector(".newWrapperContainer #mismodulos ul.list-modules");
            if (target) target.appendChild(this.node);
            this.render(false); 
        }
    }
    
    /**
     * Marca el bloque como activo y manda a renderizar la vista ampliada
     * @param {boolean} bind     
     */
    clickHandler(bind,reloadIframe = true) {
        this.isActive = this.node.classList.contains('active');
        this._removeAllActive(this.node);
        this.viewPort.splitLeft.parentNode.parentNode.classList.add("toggled");
        if (bind) {
            toggleNewModuleModal(true);
            toggleCustomColorsModal(true);
        }
        toggleEditTabs(0,true,this.node);
        if (this.isActive) return false;
        this.node.classList.add('active');
        this.isActive = true;
        this.renderEditView(false,reloadIframe);
        activeModule = this;
    }
    
    removeActive(bind){
        this.isActive = this.node.classList.contains('active');
        this._removeAllActive(this.node);
        var toggled = this.viewPort.splitLeft.parentNode.parentNode;
        if (toggled.classList.contains("toggled")) toggled.classList.remove("toggled");
        if (bind) {
            toggleNewModuleModal(true);
            toggleCustomColorsModal(true);
        }
        if (this.node.classList.contains("active")) this.node.classList.remove('active');
        this.isActive = false;
        
        var container = this.viewPort.splitRight.querySelector(".wrapperContainer");
        var wrapper = this.viewPort.splitRight.querySelector(".wrapper");
        wrapper.classList.add("hidden");
        container.innerHTML = "";
    }
    
    /**
     * Activa el view edit
     * @param {boolean} append
     */
    renderEditView(append = false,reloadIframe = true) {
        
        var container = this.viewPort.splitRight.querySelector(".wrapperContainer");
        var wrapper = this.viewPort.splitRight.querySelector(".wrapper");
        wrapper.classList.add("hidden");
        if (!append) container.innerHTML = "";
        
        var title = document.createElement("h3");
        title.innerHTML = this.builder.label;
        container.appendChild(title);
        
        var description = document.createElement("p");
        description.innerHTML = `
            <p>${this.builder.description}</p>
        `;
        container.appendChild(description);

        this.fields = new Field(this);    
        
        wrapper.classList.remove("hidden");
        
        wrapper.addEventListener("change",function(){
            needToSave();
        });
        if (reloadIframe == true) this.renderIframeRightView();
    }
    
    renderIframeRightView(){

        if (!this.builder.id) return;
        var iframeView = document.querySelector(".split.right2 iframe");
        if (!iframeView) return;
        var index = myConfig.find(rec => rec === this) ? myConfig.findIndex(rec => rec === this) : -1;
        
        var sufix = `&menu=${MENU}`;
        if (NUM) sufix += `&num=${NUM}`;
        if (index > -1) sufix += `&index=${index}`
        if (IS_MAIL_MARKETING && !needToSave(true,true)){
            iframeView.src = PREVIEW_URL;
        }else{
            if (iframeView.classList.contains("appVersion")){
                iframeView.src = PREVIEW_URL + `&appversion=1`;  
            }else{
                iframeView.src = PREVIEW_URL;    
            }
            
//                iframeView.src = `${websiteDomain}/cms/lib/plugins/builder_saas/controlador.php?moduloBuilder=${this.builder.id}${sufix}`;    
        }
        
        
    }

    
    
    /**
     * Desactiva todos los nodos menos el pasado por parámetro
     * @param {node} butNode 
     */
    _removeAllActive(butNode) {
        var activeElements = Array.prototype.slice.call(document.querySelectorAll('.bloque.active'));
        for (const element of activeElements) {
            if (element === butNode) continue;
            element.classList.remove('active');
        }

        for (let myConf of myConfig){
            if (myConf.isActive && myConf !== this) myConf.isActive = false;
        }
    }
    
    /**
     * Elimina el nodo
     */
    delete() {
        if (this.isActive){
            var wrapper = this.viewPort.splitRight.querySelector(".wrapper");
            wrapper.classList.add("hidden");
        }
        this.node.parentNode.removeChild(this.node);
        this.resetMyConfigOrder();
        console.log(myConfig)
    }

    sendModuleToWeb(domain) {
        return new Promise((resolve,reject) => {
            var url = "admin.php?menu="+MENU+"&action=edit&generateModuleFromWebsite=" + domain;

            let data = this.builder;
            
            let moduleData = {
                description:data.description || '',
                editMode:data.editMode ? data.editMode : false,
                html: data.html ? data.html : '<div class="p-12 bg-gray-300 text-gray-600 text-center">Mi nuevo módulo</div>',
                htmlParsed: data.htmlParsed ? data.htmlParsed : null,
                image: data.image,
                id: data.id,
                javascript: data.javascript ? data.javascript : '',
                label: data.label,
                builderVue:this.builderVue ? this.builderVue : '',
                notParseComponents: data.notParseComponents ? data.notParseComponents : "0",
                onlyAdminModule: data.onlyAdminModule ? true : false,
                requiredPlugins: this.requiredPlugins ? this.requiredPlugins : "",
                MJMLModule: data.MJMLModule ? true : false,
                style: data.style ? data.style : '',
                tailWind: true,
                vars:data.vars ? data.vars : null
            }

            let body = JSON.stringify(moduleData);
            
            startLoading();
            fetch(url, {
                method: 'POST',
                body: body,
                headers: {
                    'Content-type': 'application/json; charset=UTF-8'
                }
            }).then((response) => {
                return response.json();
            }).then((data) => {
                console.log(data);
                stopLoading();
                if (data.error){
                    this.delete();
                    reject(data);
                }else{
                    resolve(data);
                }
                
            }).catch(function (error) {
                Swal.fire("Error","Ha ocurrido un error en la importación. La web de destino no está preparada para recoger este módulo.","warning");
                console.warn('Something went wrong.', error);
            });
        });
        
    }
} 