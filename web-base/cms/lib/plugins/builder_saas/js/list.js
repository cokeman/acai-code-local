th = treeHelper;
var appList = new Vue({
    el: '#page-content',
    components: {
        Tree: vueDraggableNestedTree.DraggableTree,
        'list-item':httpVueLoader('/lib/plugins/builder_saas/tpl/componentes/list-item.vue')
    },
    data: {
        pages:[],
        countPages:[],
        protocol:"https",
        newOrderValues : [],
        searchString : ``,
        campoVisible: ``,
        advancedSelection: ``,
        selectedRecords:[],
        campoName: `name`,
        vistaSEO:false,
        viewSquema:VIEW_SCHEMA,
        loaded:{},
        tablesCache:{},
        vistaSEOScores:[]
    },
    computed: {
        filteredItems() {
            if (!this.vistaSEO){
                return this.pages.filter((item,index) => {
                    if (item.children && item.children.length > 0){
                        var tieneHijos = item.children.filter(subitem => {
                            if (!subitem[this.campoName]) return true
                            let result = subitem[this.campoName].toLowerCase().indexOf(this.searchString.toLowerCase()) > -1;
                            for (field in this.viewSquema){
                                if (result) break;
                                result = subitem[field].toLowerCase().indexOf(this.searchString.toLowerCase()) > -1;
                            }
                            return result;
                        });
                        let result = tieneHijos.length ? tieneHijos : item[this.campoName].toLowerCase().indexOf(this.searchString.toLowerCase()) > -1;
                        for (field in this.viewSquema){
                            if (result) break;
                            result = item[field].toLowerCase().indexOf(this.searchString.toLowerCase()) > -1;
                        }
                        return result;
                    }else{
                        let result = item[this.campoName] ? item[this.campoName].toLowerCase().indexOf(this.searchString.toLowerCase()) > -1 : false;
                        for (field in this.viewSquema) {
                            if (result) break;
                            if (!item[field]) continue
                            result = item[field].toLowerCase().indexOf(this.searchString.toLowerCase()) > -1;
                        }
                        return result;
                    }   
                })
            }else{
                var fullPages = [];
                for (const page of this.pages){
                    fullPages.push(page)
                    if (page.children && page.children.length) fullPages = [...fullPages,...page.children];
                }

                return fullPages.filter((item,index) => {

                    if (item[this.campoVisible] == 0 && item.enlace !== "/") return false;
                    let result = item[this.campoName] ? item[this.campoName].toLowerCase().indexOf(this.searchString.toLowerCase()) > -1 : false;
                    for (field in this.viewSquema) {
                        if (result) break;
                        if (!item[field]) continue
                        result = item[field].toLowerCase().indexOf(this.searchString.toLowerCase()) > -1;
                    }
                    return result;
                });
            }
            
        }
    },
    watch:{
        vistaSEO:function(newval,oldval){
            if (newval == true){
                if (!this.$options.components['list-kwtracker']) {
                    Vue.component('list-kwtracker',httpVueLoader('/lib/plugins/builder_saas/tpl/componentes/list-kwtracker.vue'));
                }
                if (!this.$options.components['list-pychecker']) {
                    Vue.component('list-pychecker',httpVueLoader('/lib/plugins/builder_saas/tpl/componentes/list-pychecker.vue'));
                }
                
                if (!this.$options.components['list-linkbuilding']) {
                    Vue.component('list-linkbuilding',httpVueLoader('/lib/plugins/builder_saas/tpl/componentes/list-linkbuilding.vue'));
                }
                if (!this.$options.components['list-analytics']) {
                    Vue.component('list-analytics',httpVueLoader('/lib/plugins/builder_saas/tpl/componentes/list-analytics.vue'));
                }
                if (!this.$options.components['list-leads']) {
                    Vue.component('list-leads',httpVueLoader('/lib/plugins/builder_saas/tpl/componentes/list-leads.vue'));
                }
            }
        }
    },
    mounted(){
        window.addEventListener("keypress", function(e) {
            if (e.keyCode == 231) {
                e.preventDefault();
                this.$refs.search.focus();
            }
        }.bind(this)); 
        
        this.cacheViewSchema();
    },
    created:function(){
        this.makeOwnRequest((miJson) => { 
            this.pages = miJson;
            this.setRequiredFields();
            this.getThumbnails(this.pages);
        });
    },
    methods:{
        updateData(dataJson){
            switch(dataJson.type){
                case "KWTRACKER":
                    if (typeof dataJson.score !== 'undefined'){
                        
                        let data = {id:dataJson.subType,label:dataJson.subTypeLabel,score:dataJson.score};

                        if (!this.vistaSEOScores.find(r => r.id == dataJson.subType)){
                            this.vistaSEOScores.push(data);
                        }else{
                            this.vistaSEOScores.find(r => r.id == dataJson.subType) = data;
                        }
                    }
                break;
            }
            console.log(dataJson);
        },
        selectRecord(num,value){
            if (value){
                this.selectedRecords.push(num);
            }else{
                this.selectedRecords = this.selectedRecords.filter(r => r !== num);
            }
        },
        filterViewSchema(viewSquema, menu) {
            delete viewSquema['enlace'];
            if(menu == 'apartados') {
                delete viewSquema['name'];
            }
            return viewSquema;
        },
        advancedOption:function(){
            switch(this.advancedSelection){
                case "eliminar":
                    if (confirm("Confirma que desea eliminar " + this.selectedRecords.length + " registros")){
                        
                                var formData = new FormData();
                        
                                for (var i = 0; i < this.selectedRecords.length; i++) {
                                    formData.append('selectedRecords[]', this.selectedRecords[i]);
                                }

                                formData.append("_advancedAction","eraseRecords");
                                formData.append("_advancedActionSubmit","Ejecutar");
                                formData.append("_defaultAction","list");
                                formData.append("page",1);
                                formData.append("menu",MENU);
                        
                                this.builderNotHasReferences(this.selectedRecords).then((data) => {
                                    if (data.success){
                                        fetch('/admin.php', { method:"POST",body:formData})
                                            .then(response => response.text())
                                            .catch(error => console.error('Error:' + error))
                                            .then(response => document.location.href = "admin.php?menu=" + MENU)    
                                    }else{
                                        var enlacesString = "";
                                        for (const dat in data.data){
                                            enlacesString += `<br><br><a href='${data.data[dat].enlace}' target="_blank">${data.data[dat].enlace}</a> --- <a href='${data.data[dat].enlace_seccion}' target="_blank">Editar</a>`;
                                        }
                                        this.showAlert(`El apartado dispone de módulos referenciados desde otras secciones de tu web por lo que no podemos eliminarlo sin que elimines previamente las referencias en estos enlaces: ${enlacesString}.`);
                                    }
                                })
                    }else{
                        this.advancedSelection = "";
                    }
                break;
                case "duplicar":
                    if (confirm("Confirma que desea duplicar " + this.selectedRecords.length + " registros")){
                        var formData = new FormData();
                        
                        for (var i = 0; i < this.selectedRecords.length; i++) {
                            formData.append('selectedRecords[]', this.selectedRecords[i]);
                        }
                        
                        formData.append("_advancedAction","duplicateRecords");
                        formData.append("_advancedActionSubmit","Ejecutar");
                        formData.append("_defaultAction","list");
                        formData.append("page",1);
                        formData.append("menu",MENU);
                        fetch('/admin.php', { method:"POST",body:formData})
                            .then(response => response.text())
                            .catch(error => console.error('Error:' + error))
                            .then(response => document.location.href = "admin.php?menu=" + MENU)
                    }else{
                        this.advancedSelection = "";
                    }
                break;
            }
            
        },
        cacheViewSchema:function(){
            for (field in this.viewSquema){
                if (this.viewSquema[field]["optionsType"] && this.viewSquema[field]["optionsType"] == "table" && this.viewSquema[field]["optionsTablename"]){
                    if (this.tablesCache[field]) continue;

                    if (!this.tablesCache[field]) this.tablesCache[field] = {};
                    this.downloadData(`/lib/plugins/builder_saas/api/v1/index.php?tableName=${this.viewSquema[field]["optionsTablename"]}&path=`,(json) => {
                        var fieldAux = json.data[0].returnData["field"];
                        for (index in json.data){
                            this.tablesCache[fieldAux][json.data[index].num] = json.data[index].name;
                        }
                        for (page of this.pages){
                            page[fieldAux] = this.tablesCache[fieldAux][page[fieldAux]];
                        }
                    },{tableName:this.viewSquema[field]["optionsTablename"],method:"GET",ignoreSchema:true,token:`0d775395420d7f6a3f231a86a00e998c`,returnData:{field:field}});
                }
            }
        },
        toggleTableLayout(tableLayout){
            appLayout.editarLayout(tableLayout);  
        },
        setRequiredFields(){
            for (index in this.pages){
                var page = this.pages[index];
                
                // VISIBLE
                if (page.visible_en_el_menu) 
                    this.campoVisible = `visible_en_el_menu`
                else if (page.visible)
                    this.campoVisible = `visible`
                else if (page.oculto)
                    this.campoVisible = `oculto`
                    
                // NAME
                if (page.name) 
                    this.campoName = `name`
                else if (page.title)
                    this.campoName = `title`
                else if (page.titulo)
                    this.campoName = `titulo`
                else if (page.tag)
                    this.campoName = `tag`
            }  
        },
        sendNewOrder(node, store) {
            this.newOrderValues = [];
            this.setNewOrderVals(store.pure(store.rootData, true).children);
            this.makeOwnRequest(() => {},this.newOrderValues,`newOrder=1`);
        },
        startDrag:function(node,helper){
            return helper.event.target.classList.contains("handle");
        },
        setNewOrderVals(array,parentNum,depth,lineage,depthAnt,breadCrumb){
            if (!array) array = this.pages;
            if (!parentNum) parentNum = 0;
            if (!depth) depth = 0;
            if (!lineage) lineage = [];
            if (!breadCrumb) breadCrumb = [];
            if (typeof depthAnt == 'undefined') depthAnt = depth;
            for (const index in array){
                if (depth == depthAnt) {
                    lineage = [array[index].num];
                    breadCrumb = [array[index][this.campoName]];
                }else{
                    lineage = lineage.slice(0,depthAnt + 1);
                    lineage.push(array[index].num);
                    breadCrumb = breadCrumb.slice(0,depthAnt + 1);
                    breadCrumb.push(array[index][this.campoName]);
                }
                this.newOrderValues.push({num:array[index].num,parentNum:parentNum,depth:depth,lineage:":" + lineage.join(":") + ":",breadCrumb:breadCrumb.join(" : ")});
                array[index].parentNum = parentNum;
                array[index].depth = depth;
                array[index].lineage = lineage;
                array[index].breadCrumb = breadCrumb;
                
                if (array[index].children){
                    var prevDepth = depth;
                    
                    this.setNewOrderVals(array[index].children,array[index].num,depth + 1,lineage,prevDepth,breadCrumb);
                    
                }
            }
            
        },
        toggleLayout(page){
            var valor = parseInt(page.layout) ? 0 : 1;
            var num = page.num;
            this.$set(page,"layout",valor);
            let dataPost = {
                method: 'POST',
                body: JSON.stringify({cambia_check:1,num:num,campo:"layout",tabla:MENU,valor: valor}),
                headers:{
                    'Content-Type': 'application/json'
                }
            };
            fetch("lib/ajax.php",dataPost)
                .then(res => res.json())
                .then(json => {
                    if (json.error) {
                        console.log(json.error);
                        return false;
                    }
                    try{
                        this.$set(page,this.campoVisible,data);
                    }catch(error){
                        console.log("Error en la función de la consulta : ");
                        return false;
                    }
                })
                .catch(function(error) {
                    console.log("Error en la consulta : ");
                    return false;
                });
            return true;
        },
        eraseRecord(num){
                var message = "Estas seguro de eliminar el registro?";
                var isConfirmed = confirm(message);
                if (isConfirmed) {
                    this.builderNotHasReferences([num]).then((data) => {
                        if (data.success){
                            window.location="?menu=" +MENU+ "&action=erase&num=" + num;
                        }else{
                            var enlacesString = "";
                            for (const dat in data.data){
                                enlacesString += `<br><br><a href='${data.data[dat].enlace}' target="_blank">${data.data[dat].enlace}</a> --- <a href='${data.data[dat].enlace_seccion}' target="_blank">Editar</a>`;
                            }
                            this.showAlert(`El apartado dispone de módulos referenciados desde otras secciones de tu web por lo que no podemos eliminarlo sin que elimines previamente las referencias en estos enlaces: ${enlacesString}`);
                        }
                    })
                }else{
                    document.getElementById("loading").style.opacity = 0;
                }    
                                                   
        },
        searchNum: function(num,pages){
            if (!pages) pages = this.pages;
            
            for (index in pages){
                if (pages[index].num == num) return pages[index];
                if (pages[index].children){
                    var childExist = this.searchNum(pages[index].children,num);
                    if (childExist) return childExist;
                }
            }
            return this.pages[0];
            
        },
        getThumbnails(array,children = false){
            if (MENU!='apartados') return;
            let nums = [];
            for (const index in array){
                nums.push(array[index].num);
                if (array[index].children){
                    nums = nums.concat(this.getThumbnails(array[index].children,true));
                }
            }
            if (children) return nums;
            if (nums.length){
                this.makeOwnRequest((miJson) => {
                    for (index in miJson){   
                        const page = this.pages.find((el) => {
                            if (el.num === miJson[index].num) return true;
                            if (el.children) {
                                const hijo = el.children.find(el2 => el2.num === miJson[index].num);
                                if (hijo) this.$set(hijo,"thumbnail",miJson[index].thumbnail);
                            }
                            return false;
                        });
                        if (page) this.$set(page,"thumbnail",miJson[index].thumbnail);
                    }
                },{},`getPageThumbnail=${nums.join(",")}`);
            }
        },
        getPageThumbnail(page){
            if (!page.num) {
                console.log({error:page});
            }
            this.makeOwnRequest((miJson) => {
                for (index in miJson){   
                    this.$set(page,"thumbnail",miJson[index].thumbnail);
                }
            },{},`getPageThumbnail=${page.num}`);

        },
        sortObject:function (obj) {
            return Object.keys(obj).sort().reduce(function (result, key) {
                result[key] = obj[key];
                return result;
            }, {});
        },
        sortByKey: function(array, key) {
            return array.sort(function(a, b) {
                var x = a[key]; var y = b[key];
                return ((x < y) ? -1 : ((x > y) ? 1 : 0));
            });
        },
        showAlert(string){
            Swal.fire("Mensaje",string,"warning");
        },
        builderNotHasReferences(pageNums = []) {
            
            return new Promise((resolve,reject) => {
                this.makeOwnRequest((miJson) => {
                    if (miJson.result > 0){
                        // Existen referencias
                        resolve({error:true,data:miJson.data});
                    }else{
                        // No existen referencias
                        resolve({success:true});
                    }
                },{menu:MENU,pageNums:pageNums},`builderHasReferences=1`);
            })
        }
    }
});
function startLoading(){
    App.coreStartLoading();
}
function stopLoading(){
    App.coreStopLoading();
}
