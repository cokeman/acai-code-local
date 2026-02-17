//initLocalDatabase();

/*function initLocalDatabase(){
    
    if (!dbList["cocosaas"]){
        console.log("La base de datos de CocoSaas no existe y la vamos a crear");
        nSQL().createDatabase({
            id: "cocosaas", 
            mode: "PERM", 
            tables: [ 
                {
                    name: "modules",
                    model: {
                        "id:uuid": {pk: true},
                        "path:string": {},
                        "description:string": {},
                        "editable:bool":{},
                        "label:string":{},
                        "vars:obj":{},
                        "wrapper:string":{},
                        "referencias:array":{},
                        "tables:array":{},
                        "thumbnail:string":{},
                        "web:bool":{},
                        "required:bool":{},
                        "local:bool":{},
                        "editable:bool":{}
                    }
                }
            ],
            version: 3
        }).then(() => {
            nSQL().useDatabase("cocosaas");
        }).catch(() => {
            console.log("Database error");
        });
    }else{
        nSQL().useDatabase("cocosaas");
    }
    
}*/

async function loadWebModules(noreset,domain = null){
    
    var url = "admin.php?menu="+MENU+"&action=edit&getWebModules=1";
    let result;
    if (domain){
        result = await makeRequest("POST", url, {domain:domain});    
    }else{
        result = await makeRequest("GET", url);
    }
    
    let json = null;
    try{
        json = JSON.parse(result);
    }catch(e){
        swal("Error");
        return [];
    }
    if (!json) return [];
    modules = json;
    
    
    // AHORA PONEMOS LOS DISPONIBLES EN NUEVOS

    for (const cont in modules){
        var module = {};
        module.builder = modules[cont];
        module.builder.id = cont;
        module.data = [];
        module.referencias = modules[cont].referencias;
        //module.referencias = [];
        availableModules[cont] = new Module(module,splitsView,true);
        availableModules[cont].add();
        availableModulesId.push(cont);
    }
    
    /*if (USER_PLUGINS["builder_saas"] && USER_PLUGINS["builder_saas"]["acceso_a_slide"] == 1){
        initSlideshow(modules,slideShowNode);
    }*/

    // QUITO LOS LOADING
    var spinners = splitRight.querySelectorAll(".tab-content #mismodulos .spinner,.tab-content #editables .spinner,.tab-content #mjml .spinner");
    for (spinner of spinners){
        spinner.style.display = "none";
    }
    return json;
}
async function loadLocalModules(noreset){
    
    var url = "admin.php?menu="+MENU+"&action=edit&getLocalModules=1";
    let result = await makeRequest("GET", url);
    let json = null;
    try{
        json = JSON.parse(result);
    }catch(e){
        swal("Error");
        return [];
    }
    if (!json) return [];
    localModules = json;
    
    /*nSQL("modules").query("upsert",Object.values(modules).map((reg) => { reg["local"] = true; return reg; })).exec().then(() => {
        console.log("Los datos BBDD en web han sido actualizados");
    })*/
    
    for (const cont in localModules){
        if (availableModulesId.includes(cont)) continue;
        var module = {};
        module.builder = localModules[cont];
        module.builder.id = cont;
        module.data = [];
        module.referencias = localModules[cont].referencias;
        //module.referencias = [];
        availableModules[cont] = new Module(module,splitsView,true);
        availableModules[cont].add();
    }
    /*if (USER_PLUGINS["builder_saas"] && USER_PLUGINS["builder_saas"]["acceso_a_slide"] == 1){
        initSlideshow(modules,slideShowNode);
    }*/
    // QUITO LOS LOADING
    var spinners = splitRight.querySelectorAll(".tab-content #generales .spinner,.tab-content #especiales .spinner,.tab-content #mjml .spinner");
    for (spinner of spinners){
        spinner.parentNode.removeChild(spinner);
    }
    return json;
}
async function loadAllModules(){
    var url = "admin.php?menu="+MENU+"&action=edit&getAllModules=1";
    let result = await makeRequest("GET", url);
    try{
        let json = JSON.parse(result);
        return json;
    }catch(e){
        swal("Error");
        return [];
    }
}
async function loadRequiredModules(requiredModules,full){
    let ids = [];
    for (module of requiredModules){
        if (!module.modulo && module.builder.id) module.modulo = module.builder.id;
        ids.push(module.modulo);
    }
    var url = "admin.php?menu="+MENU+"&action=edit&full=1&getRequiredModules=" + ids.join(",");
    if (full) url += "&full=1";
    let result = await makeRequest("GET", url);
    try{
        let json = JSON.parse(result);
        return json;
    }catch(e){
        swal("Error");
        return [];
    }
}


function makeRequest(method, url, data = null) {
    return new Promise(function (resolve, reject) {
        let xhr = new XMLHttpRequest();
        xhr.open(method, url);

        if (data){
            var formData = new FormData();
            for (const key in data){
                formData.append(key, data[key]);
            }
        }

        xhr.onload = function () {
            if (this.status >= 200 && this.status < 300) {
                resolve(xhr.response);
            } else {
                reject({
                    status: this.status,
                    statusText: xhr.statusText
                });
            }
        };
        xhr.onerror = function () {
            reject({
                status: this.status,
                statusText: xhr.statusText
            });
        };

        if (formData){
            xhr.send(formData);
        }else{
            xhr.send();    
        }
        
    });
}