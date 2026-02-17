
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
            if (CURRENT_USER["isSuperAdmin"]) console.log({new:newConfigVars,data:modulo.data});
            
            /*if (CURRENT_USER["isSuperAdmin"] && CURRENT_USER["num"] == 59){
                if (insertList.length) console.log(modulo.builder.id,insertList);
            }else{*/
                await prepareAndInsert(insertList);    
            /*}*/
            
            insertList = [];
            dataResult['config-vars'] = newConfigVars;

            data.push(dataResult);
        }
        
        /*if (CURRENT_USER["isSuperAdmin"] && CURRENT_USER["num"] == 59){
            console.log("Por aqui");
            return false;
        }else{*/
            await prepareAndUpdate(updateList);    
        /*}*/
        

        document.querySelector('.split.right .editor form [name="builder"]').value = JSON.stringify(data);

        for (let modulo of myConfig) {

            if (modulo.isActive) modulo.fields.redrawForm();
        }

        delete window.sincronizando_builder_custom;

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

    //if (CURRENT_USER["isSuperAdmin"]){
        requests.push(Rest.bulkUpdate(records_to_send));
    /*}else{
        for (let record in records_to_send) {
            requests.push(Rest.update(records_to_send[record].tableName, records_to_send[record].records, records_to_send[record].where));
        }
    }*/
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
        this.oculto = mod.oculto ? true : false;
        this.section_id = mod.section_id;
        this.referencias = this.module.referencias;
        this.isForm = isForm;
        this.container = mod.viewPort.splitRight.querySelector('.wrapperContainer');
        this.dataResult = {};
        
        this.choices = {};
        this.tablesCache = {};
        this.postDOMfields = {};
        this.loadedExternalFiles = [];
        this.hiddenFieldsContainsNames = [];
        this.builderVue = mod.builderVue ? mod.builderVue : null;
        
        
        this.build();
        
        if (this.module.isActive){
            // Sólo hacemos Build si el módulo está activo
            this.container.appendChild(this.form);
        }
        
        
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

        if (navbarModule){

            navbarModule.buttonGroups = [
                {id:"ocultar",classes:this.oculto ? "bg-red-400 hover:bg-red-600" : "",label:this.oculto ? "Mostrar Módulo" : "Ocultar Módulo",callback:() => {
                    this.oculto = !this.oculto;
                    navbarModule.buttonGroups.find(rec => rec.id == "ocultar").label = this.oculto ? "Mostrar Módulo" : "Ocultar Módulo";
                    navbarModule.buttonGroups.find(rec => rec.id == "ocultar").classes = this.oculto ? "bg-red-400 hover:bg-red-600" : "Ocultar Módulo";
                    this.module.oculto = this.oculto;
                    this.redrawVisibilityIcon();
                }}
            ];
            if (!this.referenciada){

                navbarModule.buttonGroups.push({
                    id:"referenciar",label:"Referenciar",callback:() => {
                        var exists = this.checkIfSectionExists();
                        this.buttonCallbackReference(null,exists);
                    }
                });

            }

            /*if (CURRENT_USER.num == 59){

                navbarModule.buttonGroups.push({
                    id:"gpt",label:"GPT",callback:() => {
                        let vars = ``;

                        for (const [key, varValue] of Object.entries(this.vars)) {
                            
                            if ((varValue.type == 'textfield' || varValue.type == 'textbox') && key.indexOf("_tag") !== false){
                                vars+= `,"${key}":{label:"${varValue.label}",value:""}`;
                            } else if (varValue.type == 'list' && varValue.options){
                                vars+= `,"${key}":{label:"${varValue.label}",options:"${Object.values(varValue.options.builder_custom).join(",")}",value:""}`;
                            } else if (varValue.type == 'headfield' ){
                                vars+= `,"${key}":{label:"${varValue.label} Encabezado 2",value:""}`;
                            }
                        }
                        vars = vars.slice(1);

                        let prompt = `
Rellena las claves "value" vacías del siguiente JSON de una sección en una landing page para una empresa de marketing llamada Coco Solution según el tipo de sección y campo. Habla sobre el portafolio. Devuelve un JSON válido :
{"Id":"${this.module.builder.id}","Nombre Módulo":"${this.module.label}","Descripción del Módulo":"${this.module.builder.description}","Campos":{${vars}}}
                        `;
                        console.log(prompt);
                        console.log(this);

                        const params = {
                            model:"text-davinci-002",
                            tokenValue:1000,
                            prompt: prompt 
                        };

                        const options = {
                            method: 'POST',
                            body: JSON.stringify( params )  
                        };
                        document.getElementById("loading").style.opacity = 1;
                        fetch( `admin.php?menu=${MENU}&action=edit&openAIModuleFill=1`, options )
                            .then( response => response.json() )
                            .then( response => {
                                document.getElementById("loading").style.opacity = 0;
                                var newData = response.data && response.data.choices && response.data.choices[0] ? response.data.choices[0].text : {};
                                try{
                                    newData = JSON.parse(newData);
                                }catch(error){
                                    console.log(error);
                                    newData = {};
                                }
                                console.log(newData);
                            } );
                    }
                });
            }*/

        }/*else{
            //Visibilidad
            var checkVisibility = document.createElement("input");
            checkVisibility.type = "checkbox";
            checkVisibility.id = "checkVisibility";
            checkVisibility.checked = this.oculto ? true : false;
            this.form.appendChild(checkVisibility);
            this.redrawVisibilityIcon();

            checkVisibility.addEventListener("change",(e) => {

                this.oculto = e.target.checked;
                this.module.oculto = this.oculto;
                this.redrawVisibilityIcon();
            });
        }*/

        await this.buildGroups(this.module.builder, this.form, null, this.module.data);

        //Eliminar
        this.form.addEventListener("click",(e) => {
            if (e.target.classList.contains("choices__item")) return;
            if (e.target.classList.contains("ck")) return;
            if (e.target.type == 'file') return;
            if (e.target.type == 'checkbox') return;
            e.preventDefault();
            e.stopPropagation();
            // console.log(this.module.data);
        });

        if (this.module.isActive) comprueba_idiomas();

        if(this.module.isActive){
            this.initializeAllPlugins();

        }
    }

    mountBuilderVue(Module,container){
        // console.log({"pepe":1,"activeBuilderModule":window.activeBuilderModule,"activeBuilderModule_Section_id":window.activeBuilderModule ? window.activeBuilderModule.section_id : null,"Module_Section_id":Module.section_id,"Module_isActive":Module.module.isActive})
        if ((window.activeBuilderModule && window.activeBuilderModule.section_id == Module.section_id) || !Module.module.isActive) return;
        // console.log("pepe","Entro perfectamente");
        Module.form.style.display = "none";
        
        var builderVueNode = document.getElementById("builderVue");

        if (!builderVueNode){
            builderVueNode = document.createElement('div');
            builderVueNode.id = "builderVue";
            builderVueNode.innerHTML = `<builder-vue ref="builderVue" v-if="active" :section_id="section_id" :active="active" @child-mounted="childMounted" @save-data="saveData" ></builder-vue>`;
            container.appendChild(builderVueNode);
        }
        
        builderVueNode.innerHTML = `<builder-vue ref="builderVue" v-if="active" :section_id="section_id" :active="active" @child-mounted="childMounted" @save-data="saveData" ></builder-vue>`;

        
        window.activeBuilderModule = new Vue({
            el:builderVueNode,
            data:{
                section_id:'',
                active:false
            },
            components:{
                'builder-vue':httpVueLoader(Module.builderVue + `?timestamp=` + (new Date().getTime()))
            },
            mounted(){
                this.section_id = Module.section_id;
                this.active = Module.module.isActive;

                this.$on("refresh_builder_preview",(data) => { 
                    // Para CUTEFILE
                    if (this.$refs.builderVue.$refs[data.ref]) this.$refs.builderVue.$refs[data.ref].reloadData();
                });  

                this.$on("refresh_builder_vue",(data) => { 
                    if (Module.module.data.records && this.$refs.builderVue && this.$refs.builderVue.$refs.recordsNode) this.$refs.builderVue.$refs.recordsNode.$forceUpdate();// this.$refs.builderVue.$forceUpdate();
                });                    
            },
            methods:{
                saveData(){
                    Object.keys(this.$refs.builderVue.data).map((key) => { 
                        if(this.$refs.builderVue.data[key].newValues) {
                            this.$refs.builderVue.data[key].value = this.$refs.builderVue.data[key].newValues.builder_custom.value; 
                        }
                    });
                    
                    Module.module.data = this.$refs.builderVue.data;

                    console.log("Transmuting Data from Vue to Builder");
                },
                childMounted(){
                    this.$refs.builderVue.data = Module.module.data;
                    this.$refs.builderVue.builder = Module.module.builder;
                },
                newRecord(data){
                    document.querySelector(".btn-nuevo").dispatchEvent(new Event("click"));
                },
                removeRecord(index){
                    swal({
                        title:"Seguro?",
                        text:"Estás seguro de eliminar el bloque?",
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
                            Module.module.data.records.splice(index,1);
                        }
                    });
                    
                },

                openCute(fieldname,record,isMulti = false,nodeRef = ''){
                    
                    document.getElementById("loading").style.opacity = 1;
                    if (Boolean(record[fieldname].recordNum)){
                        _openModal(`/lib/menus/modals/cutefile/index.php?menu=builder_custom&fieldName=${isMulti ? this.$refs.builderVue.builder.vars.records.vars[fieldname].relations.builder_custom : this.$refs.builderVue.builder.vars[fieldname].relations.builder_custom}&recordNum=${record[fieldname].recordNum}&action=gallery_plugin&callbackEvent=refresh_builder_preview&builder_ref=${nodeRef}`);
                    }else{
                        _openModal(`/lib/menus/modals/cutefile/index.php?menu=builder_custom&fieldName=${isMulti ? this.$refs.builderVue.builder.vars.records.vars[fieldname].relations.builder_custom : this.$refs.builderVue.builder.vars[fieldname].relations.builder_custom}&preSaveTempId=${record[fieldname].preSaveTempId}&action=gallery_plugin&callbackEvent=refresh_builder_preview&builder_ref=${nodeRef}`);
                    }
                }

            }
        })
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

        if (this.builderVue && !this.referenciada && this.module.isActive) {
            this.mountBuilderVue(this,this.container);
        }else if(this.module.isActive){
            // el 12 jun de 2024 se añadio esto :  if(this.module.isActive){ para evitar que no se actualizan las imagenes si vienes del cutefile
            // fue idea de Anael
            window.activeBuilderModule = null;
        }

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
                if (confirm("¿Estás seguro de eliminar el bloque?")){
                    let index = data.findIndex(a => a[Object.keys(a)[0]].preSaveTempId === eliminar.parentNode.id);
                    data.splice(index,1);
                    group.parentNode.removeChild(group);
                    needToSave();
                }
            });
            group.appendChild(eliminar);

            // Si es registro con padre le pongo el botón de MOVER
            var mover = document.createElement("button");
            mover.classList.add("btn-mover","btn","btn-primary");

            mover.innerHTML = "<i class='fa fa-chevron-up'></i>";
            mover.addEventListener("click",(e) => {

                e.preventDefault();
                e.stopPropagation();
                if (group.previousSibling.classList.contains("group-component")){
                    group.parentNode.insertBefore(group,group.previousSibling);
                    let index = data.findIndex(a => a[Object.keys(a)[0]].preSaveTempId === mover.parentNode.id);
                    data.splice(index-1,0,data[index]);
                    data.splice(index+1,1);
                    needToSave();
                }

                //needToSave();
            });
            group.appendChild(mover);

        }
        if (!parentKey && this.referenciada){
            this.drawReferenceButton(group);
        }
        if (record.tables){
            // Si el esquema tiene TABLAS añado los botones de selección
            var buttons = document.createElement("div");
            buttons.classList.add("button-group");
            if (!parentKey) buttons.classList.add("hidden","absolute","pointer-events-none");
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
                    this.redrawForm();

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
            if (fieldType === 'upload') {
                fieldType = 'uploadV2';
                record.vars[key].type = fieldType;
            }

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
                    dataField.push({});
                    needToSave();
                    this.buildGroups(field,multiGroup,key,dataField,selectors,dataField.length-1);

                    if (this.module.isActive) {
                        this.initializeAllPlugins();
                    }
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
                        
                        if (!this.isHiddenField(key)) this.setInputTranslateAttributes((elementIndex != null)?data[elementIndex][key]:data[key],inputElement,"textfield",field);

                        inputElement.placeholder = field.label;
                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "textfield",null,inputComponent);

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
                    case "headfield":
                        var inputElement = document.createElement("div");
                        inputElement.classList.add("headfield-wrapper","mb-8","flex","justify-between");
                        inputElement.name = field.label;

                        // CREAMOS EL SELECT Y LO CONECTAMOS AL CAMPO SIGUIENTE _TAG
                        var selectField = document.createElement("select");
                        selectField.classList.add("w-3/12","appearance-none","bg-gray-300","p-3","rounded-l","block","tw","border-r","border-gray-500","text-center");
                        var options = ["Párrafo","Encabezado 1","Encabezado 2","Encabezado 3","Encabezado 4","Encabezado 5","Encabezado 6"];
                        var optionsVal = ["P","H1","H2","H3","H4","H5","H6"];

                        var prevValue = "";
                        if (elementIndex != null && data[elementIndex][key + "_tag"]) {
                            prevValue = data[elementIndex][key + "_tag"].value;
                        }else if (data[key + "_tag"]){
                            prevValue = data[key + "_tag"].value;
                        }

                        for(const option in options){
                            var optionNode = document.createElement("option");
                            optionNode.classList.add("text-center","w-full");
                            optionNode.value = optionsVal[option];
                            optionNode.innerHTML = options[option];

                            if (optionNode.value == prevValue) { optionNode.selected = true; }
                            selectField.appendChild(optionNode);
                        }

                        selectField.setAttribute("data-connect-to",key + "_tag");

                        this.hiddenFieldsContainsNames.push(key + "_tag"); // Ocultamos el campo _tag

                        selectField.addEventListener("change",(ev) => {
                            // Si se cambia el select se cambia el campo perteneciente _tag
                            var destNode = inputComponent.querySelector("input[name='" + key + "_tag']");
                            destNode.value = ev.target.value;
                            destNode.dispatchEvent(new Event("change"));
                        });

                        inputElement.appendChild(selectField);

                        // CREAMOS EL INPUT PRINCIPAL
                        var inputElementAux = document.createElement("input");
                        inputElementAux.classList.add("w-full","p-3","rounded-r","block","tw","bg-white");
                        inputElementAux.type = "text";
                        inputElementAux.name = key;

                        this.setInputTranslateAttributes((elementIndex != null)?data[elementIndex][key]:data[key],inputElementAux,"textfield",field);

                        inputElementAux.placeholder = field.label;
                        inputElement.appendChild(inputElementAux);

                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElementAux, field, "textfield");

                        break;
                    case "link":

                        var inputElement = document.createElement("div");
                        inputElement.classList.add("link-wrapper","mb-8");
                        inputElement.name = field.label;
                        inputElement.setAttribute("name",key);

                        var inputElementAux = document.createElement("input");
                        inputElementAux.type = "text";
                        inputElementAux.name = key;
                        inputElementAux.placeholder = field.label;
                        inputElement.appendChild(inputElementAux);

                        this.setInputTranslateAttributes((elementIndex != null)?data[elementIndex][key]:data[key],inputElement,"textfield",field);

                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElementAux, field, "link");

                        break;
                    case "textbox":
                        var inputElement = document.createElement("textarea");
                        inputElement.classList.add("form-control");
                        inputElement.name = key;
                        inputElement.placeholder = field.label;
                        this.setInputTranslateAttributes((elementIndex != null)?data[elementIndex][key]:data[key],inputElement,"textbox",field);

                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "textbox");
                        this.adjustTextAreaHeight(inputElement);
                        break;
                    case "wysiwyg":
                        var inputElement = document.createElement('div');
                        inputElement.classList.add('ckeditor');
                        inputElement.setAttribute("name",key);
                        inputElement.setAttribute("contenteditable",true);
                        inputElement.setAttribute("placeholder",field.label);
                        inputElement.id = this.getID();

                        this.setInputTranslateAttributes((elementIndex != null)?data[elementIndex][key]:data[key],inputElement,"wysiwyg",field);

                        //inputElement.name = key;
                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "wysiwyg");

                        this.postDOMfields[inputElement.id] = {
                            field:field,
                            data:(elementIndex != null)?data[elementIndex][key]:data[key]
                        }
                        break;
                    case "list":
                        var inputElement = document.createElement("v-select");
                        inputElement.classList.add("mb-8", 'py-1', "bg-white",'rounded');
                        inputElement.name = key;
                        inputElement.label = field.label;
                        inputElement.id = this.getID();
                        if (field.multi) inputElement.multiple = true;

                        inputElement.setAttribute(":options","options");
                        inputElement.setAttribute("v-model","value");
                        if (field.multi) inputElement.setAttribute("multiple",true);
                        inputElement.setAttribute("tableSelected",tableSelected);
                        inputElement.setAttribute(":clearable",false);
                        inputElement.setAttribute(":reduce","record => record.value");
                        inputElement.setAttribute("placeholder",field.label);


//                        inputElement.setAttribute(":reduce","option => option.value ");

                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "list");

                        this.postDOMfields[inputElement.id] = {
                            field:field,
                            data:(elementIndex != null)?data[elementIndex][key]:data[key]
                        }

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
                    case 'uploadV2':
                        var newID = this.getID();
                        var labelIframe = document.createElement('label');
                        labelIframe.innerHTML = field.label;
                        labelIframe.classList.add("labelForIframe");
                        inputComponent.appendChild(labelIframe);

                        var buttonIframe = document.createElement("button");
                        buttonIframe.classList.add("fa","fa-upload","bg-gray-300","open-modal");
                        buttonIframe.setAttribute("data-table",tableSelected);
                        buttonIframe.setAttribute("data-for-id",newID);
                        buttonIframe.setAttribute("data-fieldName",field.relations[tableSelected]);
                        buttonIframe.setAttribute("data-recordNum",(dataField.recordNum)?dataField.recordNum:'');
                        buttonIframe.setAttribute("data-preSaveTempId",(dataField.preSaveTempId)?dataField.preSaveTempId:'');

                        inputComponent.appendChild(buttonIframe);

                        var inputElement = document.createElement("div");
                        
                        inputElement.classList.add("vue-upload","border","border-gray-400","p-2","bg-gray-300");
                    
                        inputElement.id = newID;

                        buttonIframe.addEventListener("click",function(){
                            document.getElementById("loading").style.opacity = 1;
                            var recordNum = this.getAttribute("data-recordNum");
                            if (Boolean(recordNum)){
                                _openModal(`/lib/menus/modals/cutefile/index.php?menu=${this.getAttribute("data-table")}&fieldName=${this.getAttribute("data-fieldName")}&recordNum=${this.getAttribute("data-recordNum")}&action=gallery_plugin&callbackFunc=closeGalleryBuilder("${this.getAttribute("data-for-id")}")`);
                            }else{
                                _openModal(`/lib/menus/modals/cutefile/index.php?menu=${this.getAttribute("data-table")}&fieldName=${this.getAttribute("data-fieldName")}&preSaveTempId=${this.getAttribute("data-preSaveTempId")}&action=gallery_plugin&callbackFunc=closeGalleryBuilder("${this.getAttribute("data-for-id")}")`);
                            }
                        });

                        inputElement.setAttribute("data-table",tableSelected);
                        inputElement.setAttribute("data-fieldName",field.relations[tableSelected]);
                        inputElement.setAttribute("data-recordNum",(dataField.recordNum)?dataField.recordNum:'');
                        inputElement.setAttribute("data-preSaveTempId",(dataField.preSaveTempId)?dataField.preSaveTempId:'');

                        this.listen((elementIndex != null)?data[elementIndex][key]:data[key], key, tableSelected, inputElement, field, "uploadV2");

                        this.postDOMfields[inputElement.id] = {
                            field:field,
                            data:(elementIndex != null)?data[elementIndex][key]:data[key]
                        }

                        break;
                }

                // VAMOS A OCULTAR CAMPOS INVISIBLES
                if (this.isHiddenField(key)) inputElement.style.display = "none";

                if (inputElement) {
                    if (fieldType != "uploadV2" && !this.isHiddenField(key) && tableSelected == "builder_custom"){
                        //console.log(inputElement);
                        var label = document.createElement("label");
                        label.classList.add("labelForIframe","capitalize");
                        label.innerHTML = inputElement.label || inputElement.getAttribute("placeholder") || inputElement.name;
                        inputComponent.appendChild(label);
                    }

                    inputComponent.appendChild(inputElement);
                }
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
        //if (this.module.isActive) console.log(group);
//        if(this.module.isActive){
//            // LLAMA AL REPINTADO DEL WYSIWYG UNA VEZ ESTA RENDERIZADA LA VISTA
//            for (var inputElement of inputComponent.childNodes){
//                if (inputElement.classList.contains("ckeditor")){
//                    QUILL.create(inputElement);
//                }
//            }
//        }

        if (window.activeBuilderModule) window.activeBuilderModule.$emit('refresh_builder_vue');

    }

    isHiddenField(key){
        if (this.hiddenFieldsContainsNames){
            for (const hiddenName of this.hiddenFieldsContainsNames){
                if (key.indexOf(hiddenName) > -1){
                    return true;
                }
            }
        }
        return false;
    }

    buildCustomField(data, key, tableSelected, inputElement, field, fieldType){
        if (!field.admin) return false;
        inputElement.style.display = "none";

        if (field.customField) return true;

        console.log('Anael: Bloqueado a partir de aquí, porque hace llamadas relativas (Es decir, módulos de la era "cmsAdmin" en vez de "cms") y parece que solo lo utiliza un módulo. (seccion_custom (Absolut) que lo tiene indev)');
        alert('Ups, parece que ha cargado un módulo con un problema, por favor, póngase en contacto con Coco para solventarlo.');
        return;

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

    setInputTranslateAttributes(data,inputElement,type = "textfield",field){
        
        if (data.tableName && data.recordNum){
            inputElement.setAttribute("data-tr-table",data.tableName);
            inputElement.setAttribute("data-translate",true);
            inputElement.setAttribute("data-tr-num",data.recordNum);
            inputElement.setAttribute("data-tr-type",type);
            inputElement.setAttribute("data-tr-relation-fieldname",field && field.relations && field.relations.builder_custom ? field.relations.builder_custom : '');
        }
    }

    drawLinkInputElement(wrapper,input,bind = false){

        //if (!bind && !input.value) return;
        var value = input.value;
        var sep = input.value.split("|");
        var type = sep[0];
        var valueAux = sep[1] ? sep[1] : value;

        var opciones1 = [{label:"Enlace externo",value:""},{label:"Enlace a módulo",value:"1"},{label:"Enlace a página",value:"2"},{label:"Enlace a página v2(beta)",value:"3"}];

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
                inputAux.classList.add("form-control","link","z-50");
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
                inputAux.classList.add("form-control","link","z-50");
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
            case "3":
                var typeAux = 3;
                var inputAux = document.createElement("div");
                inputAux.classList.add("relative","w-1/2","flex-shrink-0","cursor-pointer");

                var inputAux2 = document.createElement("input");
                inputAux2.classList.add("form-control","link","bg-transparent","appearance-none","text-white","pointer-events-none");
                inputAux2.type = "text";
                inputAux2.value = valueAux ? valueAux : "";
                inputAux.appendChild(inputAux2);

                var inputAux3 = document.createElement("div");
                inputAux3.classList.add("z-10","p-4","flex","items-center","absolute","top-0","truncate","left-0","w-full","h-full","bg-gray-200","rounded","hover:bg-gray-300");

                inputAux.appendChild(inputAux3);
                if (valueAux){
                    modalLinkBuilder.getData(valueAux).then((json) => {
                        inputAux3.innerHTML = json.length > 0 ? json[0].enlace : "Indefinido";
                    });
                }
                inputAux.addEventListener("click",(e) => {
                    modalLinkBuilder.open(inputAux2);
                });

                inputAux2.addEventListener("change",(e) => {
                    e.stopPropagation();
                    if (e.target.value){
                        modalLinkBuilder.getData(e.target.value).then((json) => {
                            inputAux3.innerHTML = json.length > 0 ? json[0].enlace : "Indefinido";
                            inputAux.value = e.target.value;
                            inputAux.dispatchEvent(new Event("change"));
                        });
                    }else{
                        inputAux.value = "";
                        inputAux.dispatchEvent(new Event("change"));
                        inputAux3.innerHTML = "";
                    }

                });


                break;
            default:

                var inputAux = document.createElement("input");
                inputAux.classList.add("form-control","link");
                inputAux.type = "text";
                inputAux.placeholder = "http://www.enlace.com";

                // Ajuste por Anael, quería poder poner "tel:", "mailto:"
                // inputAux.value = (valueAux.indexOf("/") == -1) ? "" : valueAux;
                if(valueAux.indexOf("/") === 0
                    || valueAux.indexOf("javascript:") === 0
                    || valueAux.indexOf("mailto:") === 0
                    || valueAux.indexOf("tel:") === 0
                    || valueAux.indexOf("http://") === 0
                    || valueAux.indexOf("https://") === 0
                    ) {
                    inputAux.value = valueAux;
                } else {
                    inputAux.value = "";
                }
        }

        if (bind){

            input.value = typeAux ? typeAux + "|" + valueAux : valueAux;
            input.dispatchEvent(new Event('change'));

            // OCULTAMOS EL MUNDO O LO MOSTRAMOS
            if (wrapper.parentNode){

                var mundo = wrapper.parentNode.querySelector(".mundo");

                if (mundo){
                    if (typeAux) {
                        mundo.classList.add("hidden");
                    }else{
                        mundo.classList.remove("hidden");
                    }
                }
            }

            // BORRAMOS LAS TRADUCCIONES
            if (wrapper.getAttribute("data-translate")){
                var num = wrapper.getAttribute("data-tr-num");
                const response = Rest.delete(`traducciones`,`tableName = 'builder_custom' and recordNum=${num} and fieldName='${wrapper.getAttribute("name")}'`);
            }
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
        this.postDOMfields = {};
        this.build();
        if (this.builderVue) this.form.style.display = "none";

        this.container.appendChild(this.form);
    }

    checkIfSectionExists(){

        if (this.referencias){
            return this.referencias;
        }else{
            return false;
        }
    }

    redrawVisibilityIcon(){
        var iconVisibility = this.module.node.querySelector(".fa-eye-slash");
        var moduleNode = this.module.node;
        if (this.oculto){
            if (!iconVisibility.classList.contains("visible")) iconVisibility.classList.add("visible");
            if (!moduleNode.classList.contains("oculto")) moduleNode.classList.add("oculto");
        }else{
            if (iconVisibility.classList.contains("visible")) iconVisibility.classList.remove("visible");
            if (moduleNode.classList.contains("oculto")) moduleNode.classList.remove("oculto");
        }
    }

    listen(data, key, tableSelected, inputElement, field, type, dataParent = null,inputComponent) {
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
//                inputElement.addEventListener("text-change",(e) => {
//                    needToSave();
//                });
                break;
            case "list":
                if (inputElement.hasAttribute("multiple")){
                    if (data.newValues[tableSelected].value){
                        var values;

                        if (Array.isArray(data.newValues[tableSelected].value)){
                            values = data.newValues[tableSelected].value.filter(rec => rec.length>0);
                        }else{
                            values = data.newValues[tableSelected].value.split("\t").filter(rec => rec.length>0);
                        }

                        /*const optionsElement = inputElement.querySelectorAll("option");
                        for (const optionElement of optionsElement){
                            optionElement.selected = false;
                        }
                        for (const option of values){
                            if (!option || option == "") continue;
                            console.log(option);
                            const element = inputElement.querySelector("[value='"+option+"']");
                            if (element) element.selected = true;
                        }*/
                        data.value = values;
                    }
                }else{
                    if (data.newValues[tableSelected].value) {
                        inputElement.value = data.newValues[tableSelected].value;
                        //field.value = inputElement.value;
                        //inputElement.dispatchEvent(new Event("updateData"),inputElement.value);
                    }
                }
                break;
            default:
                if (data.newValues[tableSelected].value) {
                    inputElement.value = data.newValues[tableSelected].value;
                }
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
                        var target = e.target.querySelector(".ql-editor") ? e.target.querySelector(".ql-editor") : e.target;
                        data.newValues[tableSelected].value = target.innerHTML;
                        data.value = target.innerHTML;
//                        e.target.dispatchEvent(new Event("updateData"),e.target.value);
//                        console.log("pepe");
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
                    case "list":
                        if (e.target.hasAttribute("multiple")){
                            //const selected = e.target.querySelectorAll('option:checked');
                            //const values = Array.from(selected).map(el => el.value);
                            //console.log(values);
                            //data.value = "\t" + values.join("\t") + "\t";
                            data.value = "\t" + e.target.value.join("\t") + "\t";
                            data.newValues[tableSelected].value = data.value;
                        }else{
                            data.newValues[tableSelected].value = e.target.value;
                            data.value = e.target.value;
                        }
                        console.log('listen', data.value, e, e.target, e.target.value)
                        break;
                    case "uploadV2":
                        field.value = e.target.value;
                        e.target.dispatchEvent(new Event("updateData"),e.target.value);
                    default:
                        if (inputComponent && inputComponent.querySelector("[data-connect-to='"+key+"']")){
                            inputComponent.querySelector("[data-connect-to='"+key+"']").value=e.target.value;
                        }
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
            "oculto" : this.oculto,
            "config-vars": this.dataResult
        };
    }

    prepareDataResult(builder, fields, updateList, insertList) {
        let data = {};
        for (let field in fields) {
            if (builder[field] && builder[field].type && Array.isArray(fields[field])) {
                data[field] = [];
                for (let multiField in fields[field]) {
                    console.log('pepe', builder, field)
                    if (!builder[field] || !builder[field].vars){
                        Swal.fire("Error al guardar","El módulo " + this.module.builder.label + " está corrupto y no puede guardarse. Te rogamos lo elimines para poder proceder con el guardado","error");
                    }
                    data[field].push(this.prepareDataResult(builder[field].vars, fields[field][multiField], updateList, insertList));
                    
                }
            } else if(builder[field] && builder[field].type) {

                let table = fields[field].tableSelected;
                data[field] = {};
                // Cojo los valores que estén por defecto
                data[field].tableName = fields[field].tableName;
                data[field].recordNum = fields[field].recordNum;

                // Fix de caso lista multiple que intenta almacenar la lista en modo array cuando debe ser con \t
                if (builder[field].type == "list" && builder[field].multi && Array.isArray(fields[field].value)) fields[field].value = "\t" + fields[field].value.join("\t") + "\t";

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
                        
                        //if (CURRENT_USER["isSuperAdmin"] && CURRENT_USER["num"] == 59){

                            // ESTE AJUSTE SOLUCIONA EL PROBLEMA DE LA PERDIDA DE IMAGENES POR PRESAVE QUE SE PERDIAN AL AÑADIR UN NUEVO CAMPO AL SCHEMA DE
                            // !!!!!!!!!!!!!!! ANTES DE SEGUIR EXPORTA LA BBDD DE apartados,builder_custom y uploads !!!!!!!!!!!!!!!
                            let hermano = Object.entries(fields).find(r => typeof(r[1].recordNum) !== 'undefined' && r[1].recordNum !== null && r[1].preSaveTempId == fields[field].preSaveTempId);
                            if (hermano){
                                let hermanoKey = hermano[0];
                                hermano = hermano[1];
                                
                                data[field].recordNum = hermano.recordNum;
                                data[field].preSaveTempId = hermano.preSaveTempId;

                                fields[field].recordNum = hermano.recordNum;;
                                if (fields[field].newValues && fields[field].newValues.tableName) fields[field].newValues.recordNum = hermano.recordNum;

                                console.log(`He asignado al campo ${field} con preSaveTempId = '${fields[field].preSaveTempId}' el recordNum ${hermano.recordNum} perteneciente al campo ${hermanoKey} ya que era un campo nuevo y no estaba definido`);
                                
                                
                                continue;
                            }
                        //}
                                            
                        // data[field].recordNum = fields[field].preSaveTempId;
                        data[field].recordNum = undefined;
                        

                        insertList.push({
                            tableName: table,
                            ignoreField:builder[field].type == 'upload' || builder[field].type == 'uploadV2' ? true : false,
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
//                            case 'uploadV2':
//                                field.value = JSON.stringify(response.data[0][config.relations[table]]);
//                                field.dispatchEvent(new Event('change'));
//                            break;
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
//                            case 'uploadV2':
//                                field.value = '[]';
//                                field.dispatchEvent(new Event('change'));
//                            break;
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

    buttonCallbackReference(e,exists){
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
            //var boton = e.target;

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
                option.innerHTML = exists[cont].title + (exists[cont].disabled ? ' ( Referenciada )' : '');
                option.disabled = exists[cont].disabled ? true : false;
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
    }

    drawReferenceButton(group,classes = []){
        var exists = this.checkIfSectionExists();
        if (exists){
            var divReference = document.createElement("div");

            var button = document.createElement("a");
            button.setAttribute("data-exist",true);
            if (classes.length){
                for (const clase of classes){
                    button.classList.add(clase);
                }
            }

            button.addEventListener("click",(e) => {
                this.buttonCallbackReference(e,exists);

            });
            if (this.referenciada){

                let referenciaObj = this.referencias.find(rec => rec.num == this.referenciada || rec.num == (this.referenciada + "|1"));

                if (!button.classList.contains("active")) button.classList.add("active","btn-primary","my-12");
                button.innerHTML = `<i class='fa fa-lock'></i> Referenciada con "${referenciaObj && referenciaObj.title ? referenciaObj.title : MENU + ` (num = ${this.referenciada})`}"`;
                if (!this.form.classList.contains("referenciado")) this.form.classList.add("referenciado");
            }else{
                button.innerHTML = "Referenciar sección";
                if (this.form.classList.contains("referenciado")) this.form.classList.remove("referenciado");
            }
            group.appendChild(button);
            if (this.referenciada){
                let referenciaObj = this.referencias.find(rec => rec.num == this.referenciada);
                //Se comenta esto porque no funciona la solucion...
                var numReference = this.referenciada;
                if(referenciaObj && referenciaObj.num) {
                    numReference = referenciaObj.num;
                }
                var tableReference = MENU;
                if (referenciaObj && referenciaObj.num && referenciaObj.num.indexOf("|") > -1){
                    numReference = referenciaObj.num.split("|")[1];
                    tableReference = referenciaObj.num.split("|")[0];
                }
                console.log('builder:', {referenciaObj: referenciaObj, referenciada: this.referenciada});
                var linkReference = document.createElement("button");
                linkReference.addEventListener("click",() => {
                    window.open(`admin.php?menu=${tableReference}&action=edit&num=${numReference}`);
                });
                linkReference.innerHTML = `Editar página de ${referenciaObj && referenciaObj.title ? referenciaObj.title : MENU + ` (num = ${this.referenciada})`} en una ventana aparte`;
                linkReference.classList.add("inline-block","relative","mt-48","text-gray-600","hover:text-gray-800","p-0","bg-white");
                linkReference.target="_blank";
                linkReference.setAttribute("rel","noopener");
                group.appendChild(linkReference);
            }
        }
    }

    adjustTextAreaHeight(el){

        window.setTimeout(() => {if (el.value) el.style.height = el.scrollHeight + "px";},400);
    }

    vselectInit(el,field,data){

        this.postDOMfields[el.id].vueSelectInstance = new Vue({
            el:el,
            data: {
                options: [],
                value:null,
                field:null
            },
            components: {
                'v-select': VueSelect.VueSelect
            },
            created(){
                this.value = field.value;
                this.field = field;
                this.init();
            },
            watch:{
                value:function(newVal,oldValue){
                    el.value = newVal;
                    if(oldValue !== null) {
                        // el.value = newVal ? newVal : "";
                        el.dispatchEvent(new Event('change'))
                        needToSave()
                    };
                }
            },
            methods:{
                parse_options(response, tableOption, tableValue, tableLabel) {
                    // tableLabel = [{index: 'name'}, ' (', {index: 'codigo'}, ')'];
                    if(!response || !response.data) {
                        this.options = [];
                    }
                    let items = response.data;
                    let options = [
                    /*    {
                            value:'',
                            label:'Ninguno'
                        }*/
                    ];
                    for (var i = 0; i < items.length; i++) {
                        let new_item = {
                            value: items[i][tableValue],
                            label: typeof tableLabel == 'string'?items[i][tableLabel]:items[i][value_name]
                        };
                        if(typeof tableLabel == 'object' && tableLabel.forEach) {
                            new_item.label = '';
                            tableLabel.forEach(label_item => {
                                if(typeof label_item == 'object' && label_item.index) new_item.label += items[i][label_item.index];
                                if(typeof label_item == 'string') new_item.label += label_item;
                            })
                        }

                        options.push(new_item);
                    }
                    if (!options.find(rec => rec.value == '')){
                        this.options = [{value:'',label:'Ninguno'},...options];
                    }else{
                        this.options = options;    
                    }
                    
                    
                },
                init(){

                    this.value = data.value;
                    let tableSelected = el.getAttribute('tableSelected');
                    if(field.options) {
                        if (field.options[tableSelected]["tableName"]){
                            let tableOption = field.options[tableSelected]["tableName"];
                            let tableValue = field.options[tableSelected]["fieldValue"];
                            let tableLabel = field.options[tableSelected]["fieldLabel"];
                            //??? const $select = inputElement;

                            Rest.get(tableOption,'',tableLabel + ' ASC',3000).then(response => this.parse_options(response, tableOption, tableValue, tableLabel));
                        }else if (field.options[tableSelected]["query"]){

                            let tableQuery = field.options[tableSelected]["query"];

                            //??? const $select = inputElement;

                            const response = Rest.query(tableQuery).then(response => {

                                if(!response || !response.data || !response.data[0]) {
                                    this.options = [];
                                    return;
                                }
                                let tableValue = Object.keys(response.data[0])[0];
                                let tableLabel = Object.keys(response.data[0])[1];
                                this.parse_options(response, null, tableValue, tableLabel);
                            });
                        }else{
                            let response = {
                                data: []
                            };
                            for (let keyName in field.options[tableSelected]){
                                response.data.push({
                                    label: field.options[tableSelected][keyName],
                                    value: keyName
                                });
                            }
                            this.parse_options(response, null, 'value', 'label');
                        }
                    } else {
                        console.error('No tiene options', field);
                    }
                }
            }
        });
    }

    uploadsV2Init(el,field,data){
        if (this.builderVue) return;

        this.postDOMfields[el.id].vueInstance = new Vue({
            el:el,
            components: {
                vuedraggable
            },
            template:`
                <ul class="bg-gray-100 rounded vue-uploads-wrapper overflow-auto mb-8" id="${el.id}">
                    <li v-if="!images || !images.length" class="relative">
                        <div class="w-full flex justify-between text-gray-700 mb-1 px-4 bg-white">

                            <input type="file" multiple ref="file" style="display:none;" @change="addFile">
                            <div @click.prevent="$refs.file.click()" class="relative w-full h-32 flex-shrink-0 text-gray-500 cursor-pointer flex items-center justify-center" @drop.prevent="addFile" @dragleave.prevent="hover = false" @dragover.prevent="hover = true" :class="{'bg-gray-200' : hover, 'bg-white' : !hover}">
                                Haz clic para añadir un archivo...
                            </div>
                        </div>
                    </li>
                    <draggable v-model="images" @end="sortList" >
                        <li v-for="image of images" v-if="image.num" class="relative">
                            <div class="w-full flex justify-between items-center text-gray-700 mb-1 pr-4" @drop.prevent="addFile" @dragleave.prevent="hover = false" @dragover.prevent="hover = true" :class="{'bg-gray-200' : hover, 'bg-white' : !hover}">
                                <div class="relative w-20 h-20 bg-white flex-shrink-0 cursor-pointer">
                                    <img v-if="isImage(image)" :src="image.urlPath" class="bg-gray-100 border-4 border-white absolute top-0 left-0 w-full h-full object-contain object-center cursor-pointer" @click="viewPhoto(image)">
                                    <a v-if="!isImage(image)" @click="window.open(image.urlPath)" class="absolute top-0 left-0 w-full h-full object-contain object-center cursor-pointer flex items-center justify-center text-gray-600" target="_blank">Descargar</a>
                                </div>
                                <ul class="relative w-full items-center text-sm p-4 truncate">
                                    <li v-if="field.infoLabels && field.infoLabels[0]" class="truncate">{{field.infoLabels[0]}} : {{image.info1}}</li>
                                    <li v-if="field.infoLabels && field.infoLabels[1]" class="truncate">{{field.infoLabels[1]}} : {{image.info2}}</li>
                                    <li v-if="field.infoLabels && field.infoLabels[2]" class="truncate">{{field.infoLabels[2]}} : {{image.info3}}</li>
                                    <li v-if="field.infoLabels && field.infoLabels[3]" class="truncate">{{field.infoLabels[3]}} : {{image.info4}}</li>
                                    <li v-if="field.infoLabels && field.infoLabels[4]" class="truncate">{{field.infoLabels[4]}} : {{image.info5}}</li>
                                </ul>
                                <ul class="relative flex justify-end flex-shrink-0">
                                    <li class="p-2 h-full flex items-center justify-center"><a @click="modifyLink(image)" class="text-gray-700"><i class="fa fa-pencil"></i></a></li>
                                    <li class="p-2 h-full flex items-center justify-center"><a @click="removeLink(image)" class="text-gray-700"><i class="fa fa-remove"></i></a></li>
                                    <li v-if="Object.keys(idiomas).length > 1" class="p-2 h-full flex items-center justify-center"><a @click="idiomaLink(image)" class="vue-upload-thickbox text-gray-500"><i class="fa fa-globe"></i></a></li>
                                    <li v-if="images.length > 1" class="p-2 h-full flex items-center justify-center cursor-pointer">
                                        <span class="text-gray-400"><i class="fa fa-sort"></i></span>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </draggable>
                </ul>
            `,
            data:{
                images:[],
                idiomas:IDIOMAS,
                record:{},
                imageExtensions:["jpg","png","gif","jpeg","svg"],
                field:null,
                drag:false,
                files:[],
                hover:false
            },
            created(){
                this.images = field.value;
                this.field = field;
                el.addEventListener("updateData",(e) => {
                    console.log("Basura");
                    this.images = e.target.value;
                })
                this.record = {
                    tableName:el.getAttribute("data-table"),
                    fieldName:el.getAttribute("data-fieldname"),
                    recordNum:el.getAttribute("data-recordnum"),
                    type:'upload',
                    preSaveTempId:el.getAttribute("data-presavetempid"),
                    infoLabels:field && field.infoLabels ? JSON.stringify(field.infoLabels) : ""
                };
                this.reloadData();
            },
            methods:{

                addFile(e) {
                    this.hover = true;
                    let droppedFiles = e.target.type == 'file' ? e.target.files : e.dataTransfer.files;
                    if (!droppedFiles) return;
                    ([...droppedFiles]).forEach(async f => {
                        this.files.push(f);
                        this.upload(f);
                    });

                    /*if (this.files.length > 1){
                        swal('Un momento', 'Sólo puedes subir 1 archivo. Si deseas enviar más debes hacerlo desde la biblioteca de medios', 'warning');
                        this.hover = false;
                    }else{
                        this.upload();
                    }*/

                },
                upload(f) {

                    let formData = new FormData();
                    //this.files.forEach((f, x) => {
                        formData.append('file', f);
                    //});
                    let url = `/lib/menus/modals/plupload/multiupload/upload.php?menu=${this.record.tableName}&fieldName=${this.record.fieldName}`;

                    if (this.record.recordNum) {
                        url+=`&num=${this.record.recordNum}&preSaveTempId=${this.record.preSaveTempId}`;
                    } else if (this.record.preSaveTempId) {
                        url+=`&preSaveTempId=${this.record.preSaveTempId}`;
                    }

                    fetch(url, {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(res => {
                            console.log('done uploading', res);
                            this.reloadData();
                            this.hover = false;
                        })
                        .catch(e => {
                            console.error(JSON.stringify(e.message));
                            this.hover = false;
                        });

                },
                updateData(data){
                    this.images = data;
                },
                viewPhoto(image){
                    var viewPhotoNode = document.querySelector(".vue-upload-photo");
                    if (viewPhotoNode) viewPhotoNode.parentNode.removeChild(viewPhotoNode);
                    viewPhotoNode = document.createElement("div");
                    viewPhotoNode.classList.add("vue-upload-photo","fixed","top-0","left-0","w-full","h-full","z-5000","flex","items-center","justify-center");
                    viewPhotoNode.innerHTML = `
                        <div class="overlay absolute top-0 left-0 w-full h-full bg-black opacity-75 z-0"></div>
                        <div class="relative z-10 bg-white p-12 rounded"><img src="${image.urlPath}" class="h-screen max-w-6xl object-contain"></div>
                    `;
                    viewPhotoNode.addEventListener("click",function(e){ viewPhotoNode.parentNode.removeChild(viewPhotoNode); })
                    document.body.appendChild(viewPhotoNode);
                },
                async sortList(){
                    const requests = [];
                    for (const orderIndex in this.images){
                        this.images[orderIndex].order = orderIndex;
                        requests.push(Rest.update(`uploads`, {order:parseInt(orderIndex)}, {num:parseInt(this.images[orderIndex].num)},{ignoreSchema:true}));
                    }
                    await Promise.all(requests);
                    this.hover = false;
                },
                idiomaLink(image){
                    tb_show("Translate Upload Field", `?menu=${this.record.tableName}&action=translateModify&fieldName=${this.record.fieldName}&num=${this.record.recordNum}&preSaveTempId=${this.record.preSaveTempId}&uploadNum=${image.num}&type=${this.record.type}&TB_iframe=true&width=900&height=600&modal=true`);
                },
                modifyLink(image){
                    tb_show("Modify Upload Field", `?menu=${this.record.tableName}&action=uploadModify&fieldName=${this.record.fieldName}&num=${this.record.recordNum}&infoLabels=${this.record.infoLabels}&preSaveTempId=${this.record.preSaveTempId}&uploadNums=${image.num}&callbackFunc=closeGalleryBuilder("${el.id}")&elId=${el.id}TB_iframe=true&width=900&height=600&modal=true`);
                },
                isImage(image){
                    const extension = image.urlPath.substring(image.urlPath.lastIndexOf(".") + 1).toLowerCase();
                    return this.imageExtensions.indexOf(extension) > -1;
                },
                async reloadData(force){

                    let where = ``;
                    if (this.record && this.record.recordNum){
                        where = `tableName='${this.record.tableName}' and fieldName='${this.record.fieldName}' and recordNum=${this.record.recordNum}`;
                    }else if (this.record && this.record.preSaveTempId){
                        where = `tableName='${this.record.tableName}' and fieldName='${this.record.fieldName}' and preSaveTempId='${this.record.preSaveTempId}'`;
                    }else{
                        return;
                    }

                    const tableName = `uploads`;
                    Rest.deleteCache(tableName,where);
                    const response = await Rest.get(tableName,where,'`order` ASC');
                    if (!response.data) {
                        swal('Un momento', 'Ha ocurrido un error al eliminar la imagen', 'warning');
                        return;
                    }
                    this.images = response.data.map((rec) => { rec.urlPath = `https://${CURRENT_USER.domain.domain}${rec.urlPath.replace(`https://${CURRENT_USER.domain.domain}`,``)}`; return rec; });
                    this.files = [];
                    this.$forceUpdate();
                },
                removeLink(image){
                    swal({
                        title:"Seguro?",
                        text:"Estás seguro de eliminar el registro?",
                        icon:"warning",
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
                    }).then(async (value) => {
                        if (value){
                            const response = await Rest.delete(`uploads`,`num=${image.num}`);
                            if (!response.data) {
                                swal('Un momento', 'Ha ocurrido un error al eliminar la imagen', 'warning');
                                return;
                            }else{

                                swal({
                                    title:"OK!",
                                    text:"La imagen ha sido eliminada",
                                    icon:"success",
                                    button : false,
                                    timer:500
                                });
                            }
                            this.images.splice(this.images.indexOf(image),1);
                            this.files = [];
                        }
                    });

                }
            }
        });


    }

    ckEditorInit(node,field,data){

        if (node.prevEditor) {
            return false;
        }
        
        // Este timeout es un parche para que el selector de modulos vaya mas rapido y la idea de esta porqueria de parche fue de Anael.

        window.setTimeout(() => {
            CKEDITOR_COCO_Start(null,[node],(resultData) => {
                node.innerHTML = resultData;
                needToSave();
                node.dispatchEvent(new Event("keyup"));
            });    
        },100);

    }

    initializeAllPlugins(key){
        if (!this.module.isActive) return; // ESTO ES UN PARCHE PORQUE NO DEBERIA DE ENTRAR SI NO ES ACTIVO
        if (this.postDOMfields){
            for (const uploadFieldIndex in this.postDOMfields){
                if (key && uploadFieldIndex !== key) continue;
                const field = this.postDOMfields[uploadFieldIndex].field;
                const data = this.postDOMfields[uploadFieldIndex].data;
                const nodeId = document.getElementById(uploadFieldIndex)
                switch(field.type){
                    case "list":
                        /*if (!this.choices[uploadFieldIndex]) this.choices[uploadFieldIndex] = new Choices(nodeId);
                        console.log(nodeId.value);
                        this.choices[uploadFieldIndex].setChoices([
                            { num: '', name: 'Elige', disabled: true },
                            { num: nodeId.value, name: 'Valor original...' + nodeId.value, selected: true },
                            { num: 'Two', name: 'Label Two' },
                            { num: 'Three', name: 'Label Three' },
                          ],
                          'num',
                          'name',
                          false);
                        nodeId.addEventListener('search', (customEvent) => {
                            this.choices[uploadFieldIndex].clearChoices();
                            this.choices[uploadFieldIndex].setChoices(async () => {
                              try {
                                let productos = await Rest.get('productos').then(response => response.data);
                                let items = [];
                                let cont = 0;
                                for (var i = 0; i < productos.length; i++) {
                                    let producto = {
                                        num: productos[i].num,
                                        name: productos[i].name
                                    };
                                    if(productos[i].num == nodeId.value) {
                                        producto.selected = true;
                                    }
                                    if(cont < 100 || producto.selected) items.push(producto);
                                }
                                return items;
                              } catch (err) {
                                console.error(err);
                              }
                            }, 'num', 'name', false);
                        })*/
                        if (!this.postDOMfields[uploadFieldIndex].vueSelectInstance) this.vselectInit(nodeId,field,data)
                        break;
                    case "wysiwyg":
                        this.ckEditorInit(nodeId,field,data);
                        break;
                    case "uploadV2":
                        if (!this.postDOMfields[uploadFieldIndex].vueInstance) this.uploadsV2Init(nodeId,field,data)
                    break;
                }
            }
        }
    }
}
