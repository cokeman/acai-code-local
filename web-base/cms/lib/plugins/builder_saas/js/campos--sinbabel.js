async function save() {
    try {
        percentSave.style.width = '0%';
        if(window.sincronizando_builder_custom) {
            swal('Un momento', 'Los datos están guardándose', 'warning');
            return;
        }
        window.sincronizando_builder_custom = true;
        let data = [];
        let updateList = [];
        let insertList = [];
        for (let modulo of myConfig) {
            const dataResult = modulo.fields.getDataResult();
            let newConfigVars = modulo.fields.prepareDataResult(modulo.builder.vars, modulo.data, updateList, insertList);
            // console.log({updateList:updateList});
            await prepareAndInsert(insertList);
            insertList = [];
            dataResult['config-vars'] = newConfigVars;
            data.push(dataResult);
        }
        delete window.sincronizando_builder_custom;
        await prepareAndUpdate(updateList);
        document.querySelector('.split.right .editor form [name="builder"]').value = JSON.stringify(data);
        for (let modulo of myConfig) {
            if (modulo.isActive) modulo.fields.redrawForm();
        }
        
    } catch (e) {
        console.error(e);
        delete window.sincronizando_builder_custom;
    }
    setTimeout(()=>{percentSave.style.width = '0%';}, 300);
}


async function prepareAndUpdate(updateList) {
    let records_to_send = {};
    percentSave.style.width = '0%';
    let percentSaveCont = 1;
    for (var i = 0; i < updateList.length; i++) {
        if(updateList[i].recordNum) {
            if(!records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum])
                records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum] = {};
            if(!records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].records)
                records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].records = {};
            records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].tableName = updateList[i].tableName;
            records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].where = {num: updateList[i].recordNum};
            records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].records[updateList[i].field] = updateList[i].value;
        }
    }
    const requests = [];
    for (let record in records_to_send) {
        requests.push(Rest.update(records_to_send[record].tableName, records_to_send[record].records, records_to_send[record].where));
    }
    await Promise.all(requests);
    percentSave.style.width = '100%';
}

/* Si se rompe fue Anael
async function prepareAndUpdate(updateList) {
    let records_to_send = {};
    percentSave.style.width = 100 / (Object.keys(records_to_send).length + 1) + '%';
    let percentSaveCont = 1;
    for (var i = 0; i < updateList.length; i++) {
        if(updateList[i].recordNum) {
            if(!records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum])
                records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum] = {};
            if(!records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].records)
                records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].records = {};
            records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].tableName = updateList[i].tableName;
            records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].where = {num: updateList[i].recordNum};
            records_to_send[updateList[i].tableName + '__' + updateList[i].recordNum].records[updateList[i].field] = updateList[i].value;
        }
    }
    for (let record in records_to_send) {
        percentSave.style.width = 100 / (Object.keys(records_to_send).length + 1) * percentSaveCont + '%';
        percentSaveCont++;
        await Rest.update(records_to_send[record].tableName, records_to_send[record].records, records_to_send[record].where);
    }
    percentSave.style.width = 100 + '%';
}*/

function handleNetErr(e) { console.error(e); };

async function prepareAndInsert(insertList) {
    let records_to_send = {};
    for (var i = 0; i < insertList.length; i++) {
        if(insertList[i].preSaveTempId) {
            if(!records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId])
                records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId] = {};
            if(!records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId].records)
                records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId].records = {};
            if(!records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId].referenceData)
                records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId].referenceData = [];
            records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId].preSaveTempId = insertList[i].preSaveTempId;
            records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId].tableName = insertList[i].tableName;
            if (insertList[i].ignoreField){
                if (!records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId])
                    records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId].records = {};
            }else{
                records_to_send[insertList[i].tableName + '__' + insertList[i].preSaveTempId].records[insertList[i].field] = insertList[i].value;
            }
        }
    }
    // console.log({records_to_send:records_to_send});
    percentSave.style.width = 100 / (Object.keys(records_to_send).length + 1) + '%';
    let percentSaveCont = 1;
    for (let record in records_to_send) {
        let newNum = 0;
        percentSave.style.width = 100 / (Object.keys(records_to_send).length + 1) * percentSaveCont + '%';
        percentSaveCont++;
        await Rest.insert(records_to_send[record].tableName, records_to_send[record].records, {return_last_id: true,preSaveTempId:records_to_send[record].preSaveTempId}).then((response) => newNum = response.data);
        for (var i = 0; i < insertList.length; i++) {
            if (insertList[i].preSaveTempId === records_to_send[record].preSaveTempId) {
                insertList[i].data.recordNum = newNum;
                insertList[i].data.tableName = insertList[i].tableName;
                insertList[i].data.preSaveTempId;

                insertList[i].data2.recordNum = newNum;
                insertList[i].data2.tableName = insertList[i].tableName;
                insertList[i].data2.preSaveTempId;
            }
        }
    }
    percentSave.style.width = 100 + '%';
}

class Field {
    constructor(mod, isForm = true, table = 'default') {
        this.module = mod;
        this.vars = mod.builder.vars;
        this.form = null;
        this.referenciada = mod.referenciada;
        this.section_id = mod.section_id;
        this.referencias = this.module.referencias;
        this.isForm = isForm;
        this.container = mod.viewPort.splitRight.querySelector('.wrapperContainer');
        this.dataResult = {};
        this.tablesCache = {};
        this.loadedExternalFiles = [];
        this.build();
        this.container.appendChild(this.form);
        
    }

    async build() {
        if (this.vars && Object.keys(this.vars).length === 0) return false;
        if (!this.form) {
            if (this.isForm) {
                this.form = document.createElement('form');
                this.form.method = 'post';
            }
            else {
                this.form = document.createElement('div');
            }
        }
        this.form = document.createElement('form');
        this.form.method = 'post';
        this.form.innerHTML = '';
        this.form.id = this.getID();
        await this.buildGroups(this.module.builder, this.form, null, this.module.data);
        
        //Eliminar
        this.form.addEventListener("click",(e) => {
            e.preventDefault();
            e.stopPropagation();
            // console.log(this.module.data);
        });
        
        if (this.module.isActive) comprueba_idiomas();   
    }

    checkAutoEnabled(data, nuevo, key, field, multiGroup) {
        if(field.autoEnabled) {
            field.autoEnabled = true;
            nuevo.classList.add('disabled');
            if(typeof data[key] !== 'string') data[key] = '';
            var inputElement = document.createElement("select");
            inputElement.classList.add("form-control","auto");
            inputElement.name = key;
            let index = 0;
            if(field.auto) {
                let optionsAuto = field.auto
                let cont = 0;
                for (let optionAuto in optionsAuto){
                    const option = document.createElement('option');
                    option.value = optionAuto;
                    option.innerHTML = optionsAuto[optionAuto].label;
                    inputElement.appendChild(option);
                    if(option.value === data[key]) index = cont;
                    cont++;
                }
            } else {
                console.error('No tiene options', field);
            }
            inputElement.addEventListener("change",(e) => {
                data[key] = e.target.selectedOptions[0].value;

            });
            inputElement.selectedIndex = index;
            inputElement.dispatchEvent(new Event('change'));
            var container_automatico = document.createElement("div");
            container_automatico.classList.add("group-auto");
            container_automatico.appendChild(inputElement);
            multiGroup.appendChild(container_automatico);
        } else if (field.autoEnabled === false){
            let selectAuto = multiGroup.querySelector('.auto');
            if(selectAuto)
                selectAuto.parentNode.removeChild(selectAuto);
            nuevo.classList.remove('disabled');
            delete field.autoEnabled;
            if(!data[key] || typeof data[key] === 'string' || !Array.isArray(data[key])) data[key] = [];
        }
    }

    async buildGroups(record, node, parentKey, data, selectors = true,elementIndex = null,redraw = false) {
        var appendLast = [];
        var group = document.createElement("div");
        group.classList.add("group-component");
        group.id = this.getID();
        if (Array.isArray(data)){
            if (data && elementIndex && data[elementIndex][Object.keys(data[elementIndex])[0]]){
                if (data[elementIndex][Object.keys(data[elementIndex])[0]].preSaveTempId) group.id = data[elementIndex][Object.keys(data[elementIndex])[0]].preSaveTempId;
            }
        }else{
            if (data[Object.keys(data)[0]] && data[Object.keys(data)[0]].preSaveTempId) group.id = data[Object.keys(data)[0]].preSaveTempId;
        }
        if (parentKey){
            // Si es registro con padre le pongo el botón de ELIMINAR
            var eliminar = document.createElement("button");
            eliminar.classList.add("btn-eliminar","btn","btn-primary");
            eliminar.innerHTML = "<i class='fa fa-remove'></i>";
            eliminar.addEventListener("click",(e) => {
                
                e.preventDefault();
                e.stopPropagation();
                console.log('eliminar',data);
                let index = data.findIndex(a => a[Object.keys(a)[0]].preSaveTempId === eliminar.parentNode.id);
                data.splice(index,1);
                console.log('eliminar',data, index);
                group.parentNode.removeChild(group);
                needToSave();
            });
            group.appendChild(eliminar);
        }
        if (!parentKey){
            this.drawReferenceButton(group);
        }
        if (record.tables){
            // Si el esquema tiene TABLAS añado los botones de selección
            var buttons = document.createElement("div");
            buttons.classList.add("button-group");
            for (let table of record.tables){
                var button = document.createElement("a");
                button.setAttribute("data-table",table);
                button.innerHTML = table == "builder_custom" ? "Personalizado" : table;
                
                button.addEventListener("click",(e) => {
                    
                    var buttonTableSelection = e.target.getAttribute("data-table");
                    if (elementIndex == null) {
                        data.tableSelected = buttonTableSelection;
                    } else {
                        data[elementIndex].tableSelected = buttonTableSelection;
                    }
                    this.changeTableData(record, elementIndex == null ? data : data[elementIndex],buttonTableSelection,group.id);
                    needToSave();
                    this.buildGroups(this.module.builder, this.form, null, this.module.data,true,null,true);
                    
                });
                buttons.appendChild(button);
            }
            
            group.appendChild(buttons);
        } else {
            // Si no tiene tablas le seteo la custom por defecto
            // record.tables = ["builder_custom"];
        }
        // Creo el DIV input-component para meter todos los campos ahí ( excepto multi )
        var inputComponent = document.createElement("div");
        inputComponent.classList.add("input-component");
        var key_anterior = null;
        for (let key in record.vars){
            // SETEO LAS VARIABLES PRINCIPALES
            var field = record.vars[key]; // CAMPO DE CONFIGURACION
            var fieldType = field.type;
            //NUEVO 
            
            if (fieldType=="multi"){
                // SI ES MULTI MONTO TODO EL ROLLO Y HAGO LA RECURSIVA
                var multiGroup = document.createElement("div");
                multiGroup.classList.add("multi-group");
                var nuevo = document.createElement("button");
                nuevo.classList.add("btn-nuevo","btn","btn-primary");
                nuevo.innerHTML = "<i class='fa fa-plus'></i>";
                multiGroup.appendChild(nuevo);
                if(field.auto) {
                    var automatico = document.createElement("button");
                    automatico.classList.add("btn-automatico","btn","btn-primary");
                    automatico.innerHTML = "<i class='fa fa-magic'></i>";
                    automatico.addEventListener("click",(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        field.autoEnabled = !!!field.autoEnabled;
                        needToSave();
                        this.buildGroups(this.module.builder, this.form, null, this.module.data,true,null,true);
                    });
                    multiGroup.appendChild(automatico);
                }
                // Comprobamos si está activada la selección automática de registros
                if(field.auto) this.checkAutoEnabled(data, nuevo, key, field, multiGroup);
                // Si no existe la key, la seteamos, si existe, comprobamos que sea o no una string para activar la selección automática de registros
                if (!data[key]) {
                    data[key] = [];
                } else if (typeof data[key] === 'string') {
                    field.autoEnabled = true;
                }
                let dataField = data[key];
                // Añadimos el evento de nuevo según los datos comprobados de "data" IMPORTANTE: Tiene que ser después de comprobar data, no mover más arriba.
                nuevo.addEventListener("click",(e) => {
                    
                    e.preventDefault();
                    e.stopPropagation();
                    console.log({
                        data:data, key:key, datakey:data[key], dataField:dataField
                    });
                    dataField.push({});
                    needToSave();
                    this.buildGroups(field,multiGroup,key,dataField,selectors,dataField.length-1);
                });
                if (Array.isArray(dataField)){
                    for (let cont in dataField){
                        await this.buildGroups(field,multiGroup,key,dataField,selectors,cont);
                    }
                }
                appendLast.push(multiGroup);
            } else if(fieldType) {
                // SI ES CAMPO VAMOS A DARLE CAÑA
                if (elementIndex != null){
                    // EN EL CASO DE QUE ESTE DENTRO DE UN GRUPO Y POR CASUALIDAD
                    // EN EL CAMPO ANTERIOR EXISTA UNA TABLA SELECCIONADA DISTINTA A LA ACTUAL
                    // FORZAMOS LA TABLA SELECCIONADA DE ESTA KEY A LA MISMA QUE LA ANTERIOR
                    if (key_anterior!=null && data[elementIndex][key] && data[elementIndex][key].tableName != data[elementIndex][key_anterior].tableName){
                        data[elementIndex][key].tableName = data[elementIndex][key_anterior].tableName;
                        data[elementIndex][key].recordNum = data[elementIndex][key_anterior].recordNum;
                    }                   
                    // CAMPO DE LA BASE DE DATOS MULTIVALOR CON INDICE SELECCIONADO
                    var dataField = this.getData(data[elementIndex][key],field,tableSelected,record.tables,group.id);
                    
                    data[elementIndex][key] = dataField;
                }else{
                    // EN EL CASO DE QUE ESTE DENTRO DE UN GRUPO Y POR CASUALIDAD
                    // EN EL CAMPO ANTERIOR EXISTA UNA TABLA SELECCIONADA DISTINTA A LA ACTUAL
                    // FORZAMOS LA TABLA SELECCIONADA DE ESTA KEY A LA MISMA QUE LA ANTERIOR
                    if (key_anterior!=null && data[key] && data[key].tableName != data[key_anterior].tableName){
                        data[key].tableName = data[key_anterior].tableName;
                        data[key].recordNum = data[key_anterior].recordNum;
                    } 
                    
                    // CAMPO DE LA BASE DE DATOS 
                    var dataField = this.getData(data[key],field,tableSelected,record.tables,group.id);
                    data[key] = dataField;
                }
                
                var tableSelected = dataField.tableName; // TABLA SELECCIONADA
                if(buttons)
                    buttons.querySelector('[data-table="'+tableSelected+'"').classList.add('active');
                if (elementIndex != null)
                    data[elementIndex][key].tableSelected = tableSelected;
                else
                    data[key].tableSelected = tableSelected;
                // SI LA TABLA SELECCIONADA ES DISTINTA DE CUSTOM O MULTI
                // SETEO UN VALOR INVENTADO PARA QUE HAGA EL SELECT
                if (dataField.tableName!="builder_custom" && field.type!="multi") {
                    fieldType = "tableSelect";
                    
                }
                
                switch(fieldType){
                    case "tableSelect":
                        var existe = inputComponent.querySelector("[data-for-all]");
                        if (!existe){
                            if (!this.tablesCache[tableSelected]){
                                // Si no está cacheada la tabla la cacheamos
                                await Rest.get(tableSelected)
                                .then(records => {
                                    if (records.error) {
                                        alert(records.error);
                                        return false;
                                    }
                                    this.tablesCache[tableSelected] = records;
                                });
                            }
                            // Generamos el select de la tabla
                            var inputElement = this.generateSelectTable(this.tablesCache[tableSelected],tableSelected,key,dataField,field);
                            this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "tableSelect", (elementIndex != null)?data[elementIndex]:data);
                            
                        }
                        break;
                    case "customField":
                    case "textfield":
                        var inputElement = document.createElement("input");
                        inputElement.classList.add("form-control");
                        inputElement.type = "text";
                        inputElement.name = key;
                        
                        this.setInputTranslateAttributes((elementIndex != null)?data[elementIndex][key]:data[key],inputElement,"textfield");
                        
                        inputElement.placeholder = field.label;
                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "textfield");
                        this.buildCustomField((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "textfield")
                        break;
                    case "form":
                        var inputElement = document.createElement("select");
                        inputElement.classList.add("form-control");
                        inputElement.name = key;
                        let optionf = document.createElement('option');
                        optionf.value = '';
                        optionf.innerHTML = field.label + '...';
                        optionf.selected = true;
                        optionf.disabled = true;
                        inputElement.appendChild(optionf);
                        await Rest.get("_formularios", '', '', 1000, false,true)
                            .then(records => {
                                if (records.error) {
                                    alert(records.error);
                                    return false;
                                }
                                var datos = records.data;
                            
                                for (let cont in datos){
                                    const option = document.createElement('option');
                                    option.value = datos[cont]["identificador"];
                                    option.innerHTML = datos[cont]["title"];
                                    inputElement.appendChild(option);
                                }
                                
                            });
                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "form");
                        break;
                    case "link":
                        
                        var inputElement = document.createElement("div");
                        inputElement.classList.add("link-wrapper");
                        
                        var inputElementAux = document.createElement("input");
                        inputElementAux.type = "text";
                        inputElementAux.name = key;
                        inputElementAux.placeholder = field.label;
                        inputElement.appendChild(inputElementAux);
                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElementAux, field, "link");
                        
                        break;
                    case "textbox":
                        var inputElement = document.createElement("textarea");
                        inputElement.classList.add("form-control");
                        inputElement.name = key;
                        inputElement.placeholder = field.label;
                        this.setInputTranslateAttributes((elementIndex != null)?data[elementIndex][key]:data[key],inputElement,"textbox");
                        
                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "textbox");
                        this.adjustTextAreaHeight(inputElement);
                        break;
                    case "wysiwyg":
                        var inputElement = document.createElement('div');
                        inputElement.classList.add('ckeditor');
                        inputElement.setAttribute("name",key);
                        inputElement.setAttribute("contenteditable",true);
                        inputElement.setAttribute("placeholder",record.label);
                        
                        this.setInputTranslateAttributes((elementIndex != null)?data[elementIndex][key]:data[key],inputElement,"wysiwyg");
                        
                        //inputElement.name = key;
                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "wysiwyg");
                        
                        break;
                    case "list":
                        var inputElement = document.createElement("select");
                        inputElement.classList.add("form-control");
                        inputElement.name = key;
                        let option = document.createElement('option');
                        option.value = '';
                        option.innerHTML = field.label + '...';
                        option.selected = true;
                        option.disabled = true;
                        inputElement.appendChild(option);
                        if(field.options) {
                            for (let keyName in field.options[tableSelected]){
                                const option = document.createElement('option');
                                option.value = keyName;
                                option.innerHTML = field.options[tableSelected][keyName];
                                inputElement.appendChild(option);
                            }
                        } else {
                            console.error('No tiene options', field);
                        }
                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "list");
                        
                        break;
                    case 'upload':
                        var labelIframe = document.createElement('label');
                        labelIframe.innerHTML = field.label;
                        labelIframe.classList.add("labelForIframe");
                        inputComponent.appendChild(labelIframe);
                        
                        var buttonIframe = document.createElement("button");
                        buttonIframe.classList.add("fa","fa-upload","btn-primary","open-modal");
                        buttonIframe.setAttribute("data-table",tableSelected);
                        buttonIframe.setAttribute("data-fieldName",field.relations[tableSelected]);
                        buttonIframe.setAttribute("data-recordNum",(dataField.recordNum)?dataField.recordNum:'');
                        buttonIframe.setAttribute("data-preSaveTempId",(dataField.preSaveTempId)?dataField.preSaveTempId:'');
                        
                        buttonIframe.addEventListener("click",function(){
                            document.getElementById("loading").style.opacity = 1;
                            var recordNum = this.getAttribute("data-recordNum");
                            if (Boolean(recordNum)){
                                _openModal(`/lib/menus/modals/cutefile/index.php?menu=${this.getAttribute("data-table")}&fieldName=${this.getAttribute("data-fieldName")}&recordNum=${this.getAttribute("data-recordNum")}&action=gallery_plugin`);
                            }else{
                                _openModal(`/lib/menus/modals/cutefile/index.php?menu=${this.getAttribute("data-table")}&fieldName=${this.getAttribute("data-fieldName")}&preSaveTempId=${this.getAttribute("data-preSaveTempId")}&action=gallery_plugin`);
                            }
                        });
                        
                        inputComponent.appendChild(buttonIframe);
                        
                        
                        if (this.module.isActive){
                            var inputElement = document.createElement('iframe');    
                        }else{
                            var inputElement = document.createElement('div');    
                        }
                        
                        let id = group.id;
                        //inputElement.id = id + '_iframe';
                        if(dataField.recordNum){
                            var src = `?menu=builder_custom&action=uploadList&fieldName=${field.relations[tableSelected]}&num=${dataField.recordNum}&preSaveTempId=&idTemp=${dataField.preSaveTempId}`;
                        }else{
                            var src = `?menu=builder_custom&action=uploadList&fieldName=${field.relations[tableSelected]}&num=&preSaveTempId=${dataField.preSaveTempId}`;
                        }
                        if (field.infoLabels) src+=`&infoLabels=`+JSON.stringify(field.infoLabels);
                        src+=`&schema=`+JSON.stringify(field);
                        
                        inputElement.setAttribute("src",src);
                        inputElement.frameborder = '0';
                        inputElement.classList.add('iframe','iframeUpload');
                        
                        break;
                }
                if (inputElement) inputComponent.appendChild(inputElement);
            } else {
                if (field.vars) {
                    if(!data[key])
                        data[key] = {};
                    group.classList.add('parent-component');
                    this.buildGroups(field, inputComponent, parentKey, data[key]);
                }
            }
            key_anterior = key;
        }
        
        // Meto todo en group y por último los MULTI que me haya encontrado
        if (redraw) node.innerHTML = "";
        group.appendChild(inputComponent);
        node.appendChild(group);
        for(let append of appendLast){
            node.appendChild(append);
        }
        if (field.customField && inputElement) field.customField.init(inputElement);
    }
    
    buildCustomField(data, key, tableSelected, inputElement, field, fieldType){
        if (!field.admin) return false;
        inputElement.style.display = "none";
        
        if (field.customField) return true;
        var path = `/lib/plugins/builder_saas/modulos/${this.module.builder.id}/`;
        var filesArray = field.admin.includeFiles.map((rec) => { return `${path}${rec}`;});
        var filesArrayAux = [];
        for (let cont in filesArray){
            if (!this.loadedExternalFiles.includes(filesArray[cont])){
                filesArrayAux.push(filesArray[cont]);
                this.loadedExternalFiles.push(filesArray[cont]);
            }
        }
        if (field.customField){
            field.customField.init(inputElement);
        }
        if (filesArrayAux.length){
            
            var ScriptLoader = new cScriptLoader(filesArrayAux,(file) => { 
                var sepPath = file.replace(path,``).split("/");
                var sepExtension = sepPath[sepPath.length-1].split(".");
                if (sepExtension[1]=="js"){
                    if (data.recordNum){
                        eval("field.customField = new " + sepExtension[0] + "(inputElement,data,field)");    
                    }else{
                        eval("field.customField = new " + sepExtension[0] + "(inputElement)");
                    }
                    auxBridgeObject = field.customField;
                    field.customField.init(inputElement);
                }
            });

            ScriptLoader.loadFiles();
        }
    }
    
    setInputTranslateAttributes(data,inputElement,type = "textfield"){
        if (data.tableName && data.recordNum){
            inputElement.setAttribute("data-tr-table",data.tableName);    
            inputElement.setAttribute("data-translate",true);
            inputElement.setAttribute("data-tr-num",data.recordNum); 
            inputElement.setAttribute("data-tr-type",type); 
        }
    }
    
    drawLinkInputElement(wrapper,input,bind = false){
        
        //if (!bind && !input.value) return;
        var value = input.value;
        var sep = input.value.split("|");
        var type = sep[0];
        var valueAux = sep[1] ? sep[1] : value;
        
        var opciones1 = [{label:"Enlace externo",value:""},{label:"Enlace landing",value:"1"},{label:"Enlace a Página",value:"2"}];
        
        var linkType = document.createElement("select");
        linkType.classList.add("form-control");
        
        for (let opcion of opciones1){
            var optionNode = document.createElement("option");
            optionNode.value=opcion.value;
            optionNode.innerHTML=opcion.label;
            optionNode.selected=opcion.value == type;
            linkType.appendChild(optionNode);    
        }
        
        wrapper.innerHTML = "";
        wrapper.appendChild(input);
        wrapper.appendChild(linkType);
                
        var linkValue = this.drawLinkInputElementValue(wrapper,input,linkType,type,valueAux);
        wrapper.appendChild(linkValue);
        
        linkType.addEventListener("change",(e) => {
            linkValue = this.drawLinkInputElementValue(wrapper,input,linkType,e.target.value,valueAux,true);
        });
        
        return true;
    }
    
    drawLinkInputElementValue(wrapper,input,linkType,type,valueAux,bind = false){
        
        switch(type){
            case "1":
                var opciones = [];
                for (let cont in myConfig){
                    opciones.push({label:myConfig[cont].label,value:"#" + myConfig[cont].section_id});
                }
                
                var inputAux = document.createElement("select");
                var typeAux = 1;
                inputAux.classList.add("form-control","link");
                for (let opcion of opciones){
                    var optionNode = document.createElement("option");
                    optionNode.value=opcion.value;
                    optionNode.innerHTML=opcion.label;
                    optionNode.selected=valueAux == optionNode.value;
                    if (valueAux == optionNode.value) opcionEncontrada = true;
                    inputAux.appendChild(optionNode);    
                }
                if (!opcionEncontrada) valueAux = opciones[0].value;
                break;
            case "2":
                var opciones = [];
                
                for (let cont in links){
                    opciones.push({label:links[cont].label,value:links[cont].value});
                }
                
                var inputAux = document.createElement("select");
                var typeAux = 2;
                inputAux.classList.add("form-control","link");
                var opcionEncontrada = false;
                for (let opcion of opciones){
                    var optionNode = document.createElement("option");
                    optionNode.value=opcion.value;
                    optionNode.innerHTML=opcion.label;
                    optionNode.selected=valueAux == optionNode.value;
                    if (valueAux == optionNode.value) opcionEncontrada = true;
                    inputAux.appendChild(optionNode);    
                }
                if (!opcionEncontrada) valueAux = opciones[0].value;
                break;
            default:
                var inputAux = document.createElement("input");
                inputAux.classList.add("form-control","link");
                inputAux.type = "text";
                inputAux.placeholder = "http://www.enlace.com";
                
                inputAux.value = (valueAux.indexOf("/") == -1) ? "" : valueAux;
        }  
        if (bind){
            input.value = typeAux ? typeAux + "|" + valueAux : valueAux;
            input.dispatchEvent(new Event('change'));
        }
        inputAux.addEventListener("change",(e) => {
            input.value = typeAux ? typeAux + "|" + e.target.value : e.target.value;
            input.dispatchEvent(new Event('change'));
        });
        
        return inputAux;
    }
    
    redrawForm(){
        if (!this.form.parentNode) return;
        var lockReferenciada = this.module.node.querySelector(".fa-lock");
        if (this.referenciada){
            if (!lockReferenciada.classList.contains("visible")) lockReferenciada.classList.add("visible");
        }else{
            if (lockReferenciada.classList.contains("visible")) lockReferenciada.classList.remove("visible");
        }
        this.form.parentNode.removeChild(this.form);
        this.build();
        
        this.container.appendChild(this.form);
    }
    
    checkIfSectionExists(){
        if (this.referencias){
            return this.referencias;    
        }else{
            return false;
        }
    }

    listen(data, key, tableSelected, inputElement, field, type, dataParent = null) {
        let eventType = 'change';
        switch (type) {
            case "tableSelect":
                // No hace falta porque ya se encarga de hacer la selección la función generateSelectTable
                break;
            case "link":
                if (data.newValues[tableSelected].value) inputElement.value = data.newValues[tableSelected].value;
                this.drawLinkInputElement(inputElement.parentNode,inputElement);
                break;
            case "wysiwyg":
                eventType = 'keyup';
                if (data.newValues[tableSelected].value) inputElement.innerHTML = data.newValues[tableSelected].value;
                //Parche para el needToSave en WISYWIG
                inputElement.addEventListener("blur",(e) => {
                    needToSave();
                });
                break;
            default:
                if (data.newValues[tableSelected].value) inputElement.value = data.newValues[tableSelected].value;
        }
        inputElement.addEventListener(eventType,(e) => {
            try {
                switch (type) {
                    case "tableSelect":
                        data.newValues[tableSelected].recordNum = e.target.value;
                        data.recordNum = e.target.value;
                        for(let fieldData in dataParent) {
                            if (Array.isArray(dataParent[fieldData])) continue;
                            if (typeof dataParent[fieldData] === 'string') continue;
                            if (!dataParent[fieldData].newValues) dataParent[fieldData].newValues = {};
                            if (!dataParent[fieldData].newValues[tableSelected]) dataParent[fieldData].newValues[tableSelected] = {};
                            dataParent[fieldData].newValues[tableSelected].recordNum = e.target.value;
                            dataParent[fieldData].recordNum = e.target.value;
                        }
                        needToSave();
                        break;
                    case "wysiwyg":
                        data.newValues[tableSelected].value = e.target.innerHTML;
                        data.value = e.target.innerHTML;
                        break;
                    case "textbox":
                        data.newValues[tableSelected].value = e.target.value;
                        data.value = e.target.value;
                        break;
                    case "link":
                        data.newValues[tableSelected].value = e.target.value;
                        data.value = e.target.value;
                        this.drawLinkInputElement(inputElement.parentNode,inputElement);
                        break;
                    default:
                        data.newValues[tableSelected].value = e.target.value;
                        data.value = e.target.value;
                        
                }
            } catch (e) {
                //Silenciado, pero esto no debería ocurrir
                console.log(data, tableSelected); console.error(e);
            }
        });
        
        
        
        if(data && data[key] && typeof data[key] === 'string') field.autoEnabled = true;
        if(typeof data.data_loaded === 'undefined') {
            this.retrieveFieldData(data, key, tableSelected, inputElement, field);            
            data.data_loaded = true;
        }
    }

    generateSelectTable(records,tableSelected,keyName,dataField,field){
        // Genera el select de tabla con los registros pasados
        var groupSelect = document.createElement("div");
        groupSelect.classList.add("row");
        
        var wrapperSelect = document.createElement("div");
        wrapperSelect.classList.add("col-xs-8");
        
        var select = document.createElement("select");
        select.setAttribute("data-for-all",true);
        select.setAttribute("data-table",tableSelected);
        select.classList.add("form-control","col-xs-8");
        select.name = keyName;
        let option = document.createElement('option');
        option.value = '';
        option.innerHTML = 'Seleccione una';
        option.selected = true;
        option.disabled = true;
        select.appendChild(option);
        for (const regRecord of records.data) {
            const title = regRecord.title || regRecord.name || regRecord.nombre || regRecord.titulo || regRecord[Object.keys(regRecord)[0]];
            const option = document.createElement('option');
            option.value = regRecord.num;
            option.innerHTML = title;
            if (parseInt(dataField.recordNum) === parseInt(regRecord.num)) option.selected = true;
            if (parseInt(dataField.value) === parseInt(regRecord.num)) option.selected = true;
            select.appendChild(option);
        }
        
        var wrapperButton = document.createElement("div");
        wrapperButton.classList.add("col-xs-4");
        
        var button = document.createElement("button");
        button.classList.add("btn","btn-default","btn-block");
        button.innerHTML = "Editar Seleccion";
        
        button.addEventListener("click",function(e){
            var seleccion = this.parentNode.parentNode.querySelector("select");
            if (!seleccion.value){
                swal({title:"Error",text:"Debes seleccionar primero un registro",buttons:{cancel:{className:"btn btn-primary"}},icon:"warning"});
            }else{
                toggleEditModuleModal(false,{num:seleccion.value,tableName:seleccion.getAttribute("data-table")});
            }
        });
        
        wrapperSelect.appendChild(select);
        wrapperButton.appendChild(button);
        groupSelect.appendChild(wrapperSelect);
        groupSelect.appendChild(wrapperButton);
        return groupSelect;
    }

    changeTableData(record, data, tableSelected, idGroup){
        /*if (Array.isArray(data)){
            var aux = {};
            for (let field in data){
                aux[field] = data[field];
            }
            data = aux;
        }*/
        for (let field in data){
            if(!record.vars[field] || record.vars[field].type === 'multi') continue;
            if (data[field][0]) continue;
            if (data[field].newValues){
                data[field].tableName = typeof data[field].newValues[tableSelected] !== 'undefined' ? data[field].newValues[tableSelected].tableName : tableSelected;
                data[field].recordNum = typeof data[field].newValues[tableSelected] !== 'undefined' ? data[field].newValues[tableSelected].recordNum : null;
                data[field].preSaveTempId = typeof data[field].newValues[tableSelected] !== 'undefined' ? data[field].newValues[tableSelected].preSaveTempId : idGroup;
            }else{
                data[field] = {
                    tableName:tableSelected,
                    recordNum:null,
                    preSaveTempId:idGroup
                }
            }
        }
    }

    getDataResult(){
        return {
            "modulo": this.module.builder.id,
            "section_id" : this.section_id,
            "referenciada" : this.referenciada,
            "config-vars": this.dataResult
        };
    }

    prepareDataResult(builder, fields, updateList, insertList) {
        let data = {};
        for (let field in fields) {
            if (Array.isArray(fields[field])) {
                data[field] = [];
                for (let multiField in fields[field]) {
                    data[field].push(this.prepareDataResult(builder[field].vars, fields[field][multiField], updateList, insertList));
                }
            } else if(builder[field] && builder[field].type) {
                let table = fields[field].tableSelected;
                data[field] = {};
                // Cojo los valores que estén por defecto
                data[field].tableName = fields[field].tableName;
                data[field].recordNum = fields[field].recordNum;
                
                // Si tiene valores nuevos de la tabla seleccionada, lo cojo
                if(fields[field].newValues && fields[field].newValues[table] && typeof fields[field].newValues[table].value !== 'undefined') {
                    updateList.push({
                        tableName: table,
                        recordNum: fields[field].recordNum,
                        field: builder[field].relations[table],
                        value: fields[field].newValues[table].value
                    });
                    // data[field].value = fields[field].newValues[table].value;
                }
                // Si la tabla seleccionada no es la original, elimino el num y cambio la selección
                if(table && fields[field].tableName && table !== fields[field].tableName) {
                    data[field].tableName = table;
                    if (fields[field].newValues[table] && fields[field].newValues[table].recordNum)
                        data[field].recordNum = fields[field].newValues[table].recordNum;
                    else
                        delete data[field].recordNum;
                }
                if(!data[field].tableName || !data[field].recordNum || data[field].recordNum !== fields[field].newValues[table].recordNum) {
                    data[field].tableName = fields[field].tableSelected;
                    if (data[field].tableName !== 'builder_custom' && fields[field].newValues && fields[field].newValues[table])
                        data[field].recordNum = fields[field].newValues[table].recordNum;
                    else if (typeof fields[field] !== 'string' && !data[field].recordNum && (builder[field].type !== 'list' /*&& builder[field].type !== 'upload'*/)) {
                        // data[field].recordNum = fields[field].preSaveTempId;
                        data[field].recordNum = undefined;
                        insertList.push({
                            tableName: table,
                            ignoreField:builder[field].type == 'upload' ? true : false,
                            preSaveTempId: fields[field].preSaveTempId,
                            field: builder[field].relations[table],
                            value: fields[field].newValues[table].value || '',
                            data: fields[field],
                            data2: data[field]
                        });
                    } else {
                        data[field] = fields[field];
                    }
                }
            } else {
                if (builder[field] && builder[field].vars) {
                    if(!fields[field])
                        fields[field] = {};
                    data[field] = this.prepareDataResult(builder[field].vars, fields[field], updateList, insertList);
                }
            }
        }
        return data;
    }

    getData(data, field, tableSelected, tables, idGroup){
        if(Array.isArray(data) && data[Object.keys(data)[0]]){
            console.log("Mal formado");
        }
        if (typeof data == "undefined"){
            /*if (tables.length>1){
                var tableDefault = "builder_custom";
                for (let table of tables){
                    if (table!=tableDefault) {
                        tableDefault = table;
                        break;
                    }
                }
                data = { tableName:tableDefault, recordNum:null };
            }else{*/
                data = { tableName:"builder_custom", recordNum:null };
            /*}*/
            
        }
        if (!data.newValues) data.newValues = {};
        for (let table of tables){
            if (!data.newValues[table] && data.tableName == table) data.newValues[table] = { tableName:data.tableName, recordNum:data.recordNum };
        }
        if (!data.preSaveTempId) data.preSaveTempId = idGroup;

        return data;
    }

    /**
     * Pide todos los datos para poder hacer el edit.
     */
    retrieveFieldData(data,key,table,field,config){
        // try {
            if(data && key && table && field && data.tableName && data.recordNum && data.tableName == 'builder_custom') {
                Rest.get(data.tableName, 'num=' + data.recordNum, '', 1, true).then((response) => {
                    if(config.relations[table]) {
                        switch(config.type) {
                            case 'wysiwyg':
                                field.innerHTML = response.data[0][config.relations[table]];
                                field.dispatchEvent(new Event('keyup'));
                            break;
                            default:
                                field.value = response.data[0][config.relations[table]];
                                field.dispatchEvent(new Event('change'));
                        }
                    } else {
                        switch(config.type) {
                            case 'wysiwyg':
                                field.innerHTML = '';
                                field.dispatchEvent(new Event('keyup'));
                            break;
                            default:
                                field.value = '';
                                field.dispatchEvent(new Event('change'));
                        }
                    }
                });
            }
        // } catch(e) {
        //     console.log(e,data,key,table,field);
        // }
    }

    getID() {
        return '_' + Math.random().toString(36).substr(2, 9);
    }
    
    drawReferenceButton(group){
        var exists = this.checkIfSectionExists();    
        if (exists){
            var button = document.createElement("a");
            button.setAttribute("data-exist",true);

            button.addEventListener("click",(e) => {
                if (this.referenciada){
                    swal({
                        title:"Seguro?",
                        text:"Estás seguro de desactivar la referencia?",
                        content:select,
                        buttons:{
                          cancel: {
                            text: "No disculpa",
                            value: null,
                            visible: true,
                            className: "btn btn-default",
                            closeModal: true,
                          },
                          confirm: {
                            text: "Si, seguro",
                            value: true,
                            visible: true,
                            className: "btn btn-primary",
                            closeModal: true
                          }
                        }
                    }).then((value) => {
                        if (value){
                            this.referenciada = null;
                            this.module.referenciada = null;
                            this.redrawForm();
                        }
                    });

                }else{
                    var boton = e.target;

                    var select = document.createElement("select");
                    select.classList.add("form-control");

                    let option = document.createElement('option');
                    option.value = '';
                    option.innerHTML = "Selecciona referencia";
                    option.selected = true;
                    option.disabled = true;
                    select.appendChild(option);

                    for (cont in exists){
                        let option = document.createElement('option');
                        option.value = exists[cont].num;
                        option.innerHTML = exists[cont].title;
                        select.appendChild(option);
                    }

                    swal({
                        title:"Referenciar seleccion",
                        text:"Puedes hacer que esta sección sea un alias ( referencia de otra) para no estar repitiendo el contenido. Deberás tener en cuenta que no podrás modificar el contenido de una referencia.",
                        content:select,
                        buttons:{
                          cancel: {
                            text: "No deseo referenciar",
                            value: null,
                            visible: true,
                            className: "btn btn-default",
                            closeModal: true,
                          },
                          confirm: {
                            text: "Ok, vamos allá",
                            value: true,
                            visible: true,
                            className: "btn btn-primary",
                            closeModal: true
                          }
                        }
                    }).then((value) => {
                        var swalNode = document.querySelector(".swal-content select");
                        if (value){
                            if (swalNode.value) {
                                this.referenciada = swalNode.value;
                                this.module.referenciada = swalNode.value;
                                this.redrawForm();
                            }else{
                                swal({
                                    title:"Error",
                                    text:"Debes seleccionar una sección",
                                    icon:"warning",
                                    buttons:{
                                      confirm: {
                                        text: "Ok",
                                        value: true,
                                        visible: true,
                                        className: "btn btn-primary",
                                        closeModal: true
                                      }
                                    }
                                });
                            }    
                        }

                    });

                }
                needToSave();
            });
            if (this.referenciada){
                if (!button.classList.contains("active")) button.classList.add("active","btn-primary");
                button.innerHTML = "<i class='fa fa-lock'></i> Referenciada";
                if (!this.form.classList.contains("referenciado")) this.form.classList.add("referenciado");
            }else{
                button.innerHTML = "Referenciar sección";
                if (this.form.classList.contains("referenciado")) this.form.classList.remove("referenciado");
            }
            group.appendChild(button);
        }
    }
    
    adjustTextAreaHeight(el){        
        
        window.setTimeout(() => {if (el.value) el.style.height = el.scrollHeight + "px";},400);
    }
}