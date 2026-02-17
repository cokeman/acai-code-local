var appLayout = new Vue({
    el: '#modalLayout',
    /*components: {
        'docs-module': httpVueLoader('/lib/plugins/builder_saas/tpl/componentes/componentes.vue')
    },*/
    data() {
        return {
            opened:false,
            opciones: [
                { visible:true,     label : "Libraries",  icon:"fa-code",         clase:null,     elementActive:"librariesModule2",  fileName:"recursos"},
                { visible:false,    label : "Code",       icon:"fa-code",         clase:"active", elementActive:"tableCodeModule",   fileName:"/index.tpl"},
                { visible:false,    label : "Style",      icon:"fa-code",         clase:null,     elementActive:"tableStyleModule",  fileName:"/style.css"},
                { visible:false,    label : "Javascript", icon:"fa-code",         clase:null,     elementActive:"tableJsModule",     fileName:"/script.js"},
                { visible:true,     label : "Header",     icon:"fa-code",         clase:"active", elementActive:"headerModule",      fileName:"header.tpl"},
                { visible:true,     label : "Footer",     icon:"fa-code",         clase:null,     elementActive:"footerModule",      fileName:"footer.tpl"},
                { visible:true,     label : "Mantenimiento",     icon:"fa-code",         clase:null,     elementActive:"mantenimientoModule",      fileName:"mantenimiento.tpl"},
                { visible:true,     label : "Custom CSS",icon:"fa-paint-brush",  clase:null,     elementActive:"styleModule",       fileName:"style.css"},
                { visible:true,     label : "Custom JS", icon:"fa-codepen",      clase:null,     elementActive:"jsModule",          fileName:"script.js"},
                { visible:true,     label : "Save",       icon:"fa-save",         clase:null,     elementActive:"saveCreateModule",  fileName:"Save Layout"},
                { visible:true,     label : "TailwindCSS",icon:"fa-book",         clase:null,     elementActive:"tailModule",        fileName:"Docs : Tailwind"},
                { visible:true,     label : "TailwindRun",icon:"fa-book",         clase:null,     elementActive:"tailRunModule",     fileName:"Docs : Tailwind Run"},
                { visible:true,     label : "Componentes",icon:"fa-book",         clase:null,     elementActive:"docsModule",        fileName:"Docs"}
            ],
            preview:false,
            codeModules:["headerModule","tableCodeModule","tableStyleModule","tableJsModule","footerModule","librariesModule","styleModule","jsModule","mantenimientoModule"],
            requestTimestamp:[],
            fullScreenIframe:false,
            sePuedeEliminar:false,
            fullScreenCode:true,
            vmodel:this.codeHeader,
            allowedExtensions : ["jpg","jpeg"], /* en minúsculas */
            iframeDocTail : "https://nerdcave.com/tailwind-cheat-sheet",
            iframeDocTailRun : "https://tailwind.run/new",
            iframeResultLink : `<link href="/css/tailwind.min.css" rel="stylesheet">`,
            codeHeader: ``,
            codeMantenimiento: ``,
            codeTable: ``,
            styleTable: ``,
            javascriptTable: ``,
            codeFooter : ``,
            libraries : ``,
            librariesJSONt : [],
            librariesJSONb : [],
            style: ``,
            javascript: ``,
            codeDOM:{},
            activeElement : "headerModule",
            urlAvatar : `data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA+Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBkZWZhdWx0IHF1YWxpdHkK/9sAQwAIBgYHBgUIBwcHCQkICgwUDQwLCwwZEhMPFB0aHx4dGhwcICQuJyAiLCMcHCg3KSwwMTQ0NB8nOT04MjwuMzQy/9sAQwEJCQkMCwwYDQ0YMiEcITIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIy/8AAEQgBkAGQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A9MooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAr3Vw0PlpEgeWQ4UE4HuTUK3N1DPHHdRx7ZDtV4yeD6HNPvIo5nhTzTFNkmNh16c1F5l3ayxrcFJo3baGUYYHtxQBoVX89v7R+z4Gzyt+e+c4rJjS5uIftC2rtM2SJ/PAx+Hp7VbnkeO9eXHzrZlvoc0AadFYkUNwPKmjtHWTIJlNwDuHfI96ttCl7fzxzZMcKqFQEgZPOaANCo5XdNmyPflgDzjaPWqEkbD7LZNMWR2bcwPJUcgZplxarayQLG/7o3EZEZOdp5oA1qKz2hW91CeObcY4VUKoJAyec1UCtNcW0DuxEcsse7PJUAf04oA26KzkRbW/mhjby4jB5nqFOcZqoojje1kgjnDNKqtM/AkB68Z70AblRwu8kQaSPy2OcrnOKkrDgjEsemoSQCZc4OOOeKANyisqGyhku7uBg3lR7dibjhSRyaYrrNp1n57yPkn92gJMmM/yoA2KKyrRzD9vEcbokahkjc5xwTUNxaIuki4DMZXCs7Fid2SKANuisi4ElzfTIbVp0j2hR5uwLxnNN8qWSSxin3Kd0g+/k7cdMj24oA2aKoQRrbao8MWVjaEPtzwDnFX6ACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAgurVblF+ZkdDuR16g1FHZSeaklxctNsOVXaFAPrxVyigCidOb5o1uHW2Y5MQUfiM+lTG1U3XnE/L5XlbMds5qxRQBRTT2BRHuXeBCCsZUDp0ye9ST2jvN50E5hkI2sQoYEfSrVFAFRrBfs8caSMrxncsnU570z+zizK8k5eUSK5YrjIXoAO3Wr1FAFWe0Z5vOhmMMhG1iFBBH0pI7BY3t2Vz+5LE5GSxYdc1booAryWgkuXlZvlaHyiuO2euarnTZGjjVrtj5RBj+QYXHr61oUUAFUobDyfsv73PkF/4fvbvx4q7RQBDFb+Xczzbs+bt4x0wMVXXTikECJOVlhztfb69eKvUUAU4rMwSyymVpBIvzqVGWI/z0rMlEclokUN2zqzARwYG5ee/61v1GsESyGRYkDnqwUZ/OgCGa0dpzNBOYXYYf5Qwb060iWKxyW7K5/clic8liw5Oat0UAQ/Z/9N+07v8Aln5e3HvnOamoooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA//9k=`,
            allowedExtensions : ["css","js"], 
            maxFileSize : 200000, 
            tableLayout : false,
            hasController : false,
            dataLoaded : false,
            needToSave : false
        }
    },

    mounted(){
        this.init()
        
    },
    watch:{
        fullScreenCode:function(newVal,oldVal){
            window.setTimeout(() => { 
                for (const children of this.$children){
                    children.editor.resize(); 
                }
            },10);
        },
        preview:function(newVal,oldVal){
            this.fullScreenIframe = this.preview;
        },
        codeHeader:function(newVal,oldVal){
            this.codeDOM.codeHeader = appParser.parseComponents(newVal);
            if (oldVal != newVal && this.dataLoaded) this.needToSave = true;
        },
        codeMantenimiento:function(newVal,oldVal){
            this.codeDOM.codeMantenimiento = appParser.parseComponents(newVal);
            if (oldVal != newVal && this.dataLoaded) this.needToSave = true;
        },
        codeTable:function(newVal,oldVal){
            this.codeDOM.codeTable = appParser.parseComponents(newVal);
            if (oldVal != newVal && this.dataLoaded) this.needToSave = true;
        },
        codeFooter:function(newVal,oldVal){
            this.codeDOM.codeFooter = appParser.parseComponents(newVal);
            if (oldVal != newVal && this.dataLoaded) this.needToSave = true;
        },
        styleTable:function(newVal,oldVal){ if (oldVal != newVal && this.dataLoaded) this.needToSave = true;},
        javascriptTable:function(newVal,oldVal){ if (oldVal != newVal && this.dataLoaded) this.needToSave = true;},
        style:function(newVal,oldVal){ if (oldVal != newVal && this.dataLoaded) this.needToSave = true;},
        javascript:function(newVal,oldVal){ if (oldVal != newVal && this.dataLoaded) this.needToSave = true;},
        libraries:function(newVal,oldVal){ if (oldVal != newVal && this.dataLoaded) this.needToSave = true;},
        activeElement:function(newVal,oldVal){
            this.setTitle();
        },
        opened:function(newVal,oldVal){
            this.setTitle();
        },
        needToSave:function(newVal,oldVal){
            this.setTitle();
        }
    },
    methods: {
        init: function(){
            this.loadAllModules().then((data) => {
                if (data.web) window.allModules = data.web; else window.allModules = {};
            });
        },
        loadAllModules:function(){
            return new Promise((resolve, reject) => {
                var url = "admin.php?menu="+MENU+"&action=edit&getAllModules=1";
                this.downloadData(url,(data) => {
                    resolve(data);
                });
            })
            
        },
        beautify:function(){
            switch(this.activeElement){
                case "headerModule": this.codeHeader = this.beautifyCode(this.codeHeader);break;
                case "mantenimientoModule": this.codeMantenimiento = this.beautifyCode(this.codeMantenimiento);break;
                case "footerModule": this.codeFooter = this.beautifyCode(this.codeFooter);break;
                case "tableCodeModule": this.codeTable = this.beautifyCode(this.codeTable);break;
            }
        },
        setTitle: function(){
            if (this.opened) {
                let titleString = "...Editando código";
                let preString = this.needToSave ? ` &#9889; ` : ``;
                for (opcion of this.opciones){
                    if (opcion.elementActive == this.activeElement) titleString = opcion.fileName;
                }
                if (this.tableLayout) App.title(preString + MENU + titleString); else App.title(preString + titleString);
            }else{
                App.title();
            }
        },
        orderList() {
            this.librariesJSON = this.librariesJSON.sort((one, two) => {
                return one.order - two.order;
            });
        },
        checkControlador(){
            if (this.tableLayout){
                if (!CONTROLADOR_SCHEMA.includes("builder_saas") && CONTROLADOR_SCHEMA!="") return true;
            }else{
                return false;
            }
            return false;
        },
        editarLayout:async function(tableLayout){
            this.dataLoaded = false;
            this.tableLayout = tableLayout ? true : false;
            this.hasController = this.checkControlador();
            if (tableLayout){
                let hiddenOptions = ["headerModule","footerModule","librariesModule2","styleModule","jsModule"];
                this.activeElement = "tableCodeModule";
                for (opcion of this.opciones){
                    if (hiddenOptions.includes(opcion.elementActive)) opcion.visible=false; else opcion.visible=true;
                }
            }else{
                this.activeElement = "headerModule";
                let hiddenOptions = ["tableCodeModule","tableStyleModule","tableJsModule"];
                for (opcion of this.opciones){
                    if (hiddenOptions.includes(opcion.elementActive)) opcion.visible=false; else opcion.visible=true;
                }
            }
            var modalLayout = document.getElementById("modalLayout");
            if (!modalLayout) {
                modalLayout = document.createElement("div");
                modalLayout.id = "modalLayout";
                document.body.appendChild(modalLayout);
            }
            if (!modalLayout.classList.contains("abierto")) {
                window.setTimeout(function(){ modalLayout.classList.add("abierto");},10);
            }
            if (tableLayout) {
                await this.loadTableData();
            }else{
                await this.loadLayoutData();
            }
            this.opened = true;
            
            return true;
        },
        cierraModal:function(){
            var modalLayout = document.getElementById("modalLayout");
            if (!modalLayout) return false;
            if (modalLayout.classList.contains("abierto")) modalLayout.classList.remove("abierto");
            
            if (typeof app === 'object'){
                app.codeHeader = null;
                app.codeMantenimiento = null;
                app.codeFooter = null;
                app.style = null;
                app.libraries = [];
            }
            this.opened = false;
            if (typeof stopLoading === 'function') stopLoading();
            return true;  
        },
        preCierre: async function(data,close){
            if (typeof close == 'undefined') close = true;
            if (data){
                if (this.tableLayout){
                    await this.saveTableData();
                }else{
                    await this.saveLayoutData();
                }
                if (close) {
                    this.cierraModal();
                }
            }else{
                if (close) {
                    this.cierraModal();
                }
            }
        },
        activateTab: function(el){
            this.activeElement = el;
            if (this.activeElement == "docsModule" && !this.$options.components['docs-module']) {
                Vue.component('docs-module', httpVueLoader.load('/lib/plugins/builder_saas/tpl/componentes/componentes.vue'));
            }
        },

        generateId:function(length) {
            var result           = '';
            var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var charactersLength = characters.length;
            for ( var i = 0; i < length; i++ ) {
                result += characters.charAt(Math.floor(Math.random() * charactersLength));
            }
            return result;
        },
        childOf:function(c,p) { 
            while((c=c.parentNode)&&c!==p); return !!c; 
        },
        isNode:function(o){
            return (
                typeof Node === "object" ? o instanceof Node : 
                o && typeof o === "object" && typeof o.nodeType === "number" && typeof o.nodeName==="string"
            );
        },
        getIframeCode:function(el){
            switch(el){
                case "headerModule":return this.iframeResultLink + this.getStyleCode() + this.getJavascriptCode() + this.getHeaderCode() + this.getRenderLibrariesTop() + this.getRenderLibrariesBottom();break;
                case "footerModule":return this.iframeResultLink + this.getStyleCode() + this.getJavascriptCode() + this.codeFooter  + this.getRenderLibrariesTop() + this.getRenderLibrariesBottom();break;
                case "styleModule":return this.iframeResultLink + this.getStyleCode() + this.getJavascriptCode() + this.getHeaderCode() + this.getRenderLibrariesTop() + this.getRenderLibrariesBottom();break;
                case "jsModule":return this.iframeResultLink + this.getStyleCode() + this.getJavascriptCode() + this.getHeaderCode() + this.getRenderLibrariesTop() + this.getRenderLibrariesBottom();break;
                case "librariesModule":return this.iframeResultLink + this.getStyleCode() + this.getJavascriptCode() + this.getHeaderCode() + this.codeFooter + this.getRenderLibrariesTop() + this.getRenderLibrariesBottom();break;
            }

        },
        getHeaderCode: function(){
            return this.codeHeader.replace(/([<?]+)(\s*)CustomCode::Menu(.*)(\s*)(.*)(\s*)([?>]+)/gi,'<li class="px-4"><a>MENU 1</a></li><li class="px-4"><a>MENU 2</a></li><li class="px-4"><a>MENU 3</a></li>');
        },
        getStyleCode: function(){
            return this.style ? `<style data-external>${this.style}</style>` : ``;
        },
        getJavascriptCode: function(){
            return this.javascript ? `<script data-external>${this.javascript}</script>` : ``;
        },
        saveData(close){
            if (typeof close == 'undefined') close = true;
            var data = {
                header:this.codeHeader,
                footer:this.codeFooter,
                style:this.style,
                javascript:this.javascript,
                mantenimiento:this.codeMantenimiento,
                libraries:this.libraries
            }
            this.needToSave = false;
            this.preCierre(data,close);
        },
        toDataUrl:function (url, callback) {
            var xhr = new XMLHttpRequest();
            xhr.onload = function() {
                var reader = new FileReader();
                reader.onloadend = function() {
                    callback(reader.result);
                }
                reader.readAsDataURL(xhr.response);
            };
            xhr.open('GET', url);
            xhr.responseType = 'blob';
            xhr.send();
        },
        loadTableData:async function(tiempo){
            
            if (!tiempo) tiempo = 3000;
            var url = "admin.php?menu="+MENU+"&action=edit&getTableData=1";
            var timestamp = new Date().getTime();
            
            if (this.requestTimestamp[url] && this.requestTimestamp[url]>(timestamp-tiempo)) {
                console.log({message:`Bloqueada petición de layout porque hace menos de ${tiempo/1000} segundos que se ejecutó la anterior`});
                this.requestTimestamp[url] = timestamp;
                window.setTimeout(() => {this.dataLoaded = true;},1000);
                return {code:this.codeTable,style:this.styleTable,javascript:this.javascriptTable};
            }
            this.requestTimestamp[url] = timestamp;
            let result = await this.makeRequest("GET", url);
            try{
                let json = JSON.parse(result);
                
                if (json["result"]) {
                    if (json["data"]["htmlData"]) this.codeTable = json["data"]["htmlData"];
                    if (json["data"]["styleData"]) this.styleTable = json["data"]["styleData"];
                    if (json["data"]["javascriptData"]) this.javascriptTable = json["data"]["javascriptData"];
                    window.setTimeout(() => {this.dataLoaded = true;},1000);
                    return json["data"];
                }else{
                    return [];
                }
            }catch(e){
                //swal("Error","No se pueden extraer los datos del layout","warning");
                return [];
            }

            return result;
        },
        loadLayoutData:async function(tiempo){
            if (!tiempo) tiempo = 3000;
            var url = "admin.php?menu="+MENU+"&action=edit&getLayoutData=1";
            var timestamp = new Date().getTime();
            
            if (this.requestTimestamp[url] && this.requestTimestamp[url]>(timestamp-tiempo)) {
                console.log({message:`Bloqueada petición de layout porque hace menos de ${tiempo/1000} segundos que se ejecutó la anterior`});
                this.requestTimestamp[url] = timestamp;
                window.setTimeout(() => {this.dataLoaded = true;},1000);
                return {header:this.codeHeader,footer:this.codeFooter,mantenimiento:this.codeMantenimiento,libraries:this.libraries,style:this.style,javascript:this.javascript,librariesJSONt:this.librariesJSONt,librariesJSONb:this.librariesJSONb};
            }
            this.requestTimestamp[url] = timestamp;
            let result = await this.makeRequest("GET", url);
            try{
                let json = JSON.parse(result);

                if (json["result"]) {
                    window.setTimeout(() => {this.dataLoaded = true;},1000);
                    if (json["data"]["header"]) this.codeHeader = json["data"]["header"];
                    if (json["data"]["mantenimiento"]) this.codeMantenimiento = json["data"]["mantenimiento"];
                    if (json["data"]["footer"]) this.codeFooter = json["data"]["footer"];
                    if (json["data"]["libraries"]) this.libraries = json["data"]["libraries"];
                    if (json["data"]["style"]) this.style = json["data"]["style"];
                    if (json["data"]["javascript"]) this.javascript = json["data"]["javascript"];
                    if (json["data"]["librariesJSONt"]) this.librariesJSONt = json["data"]["librariesJSONt"];
                    if (json["data"]["librariesJSONb"]) this.librariesJSONb = json["data"]["librariesJSONb"];
                    app.layout = json["data"];
                    
                    return json["data"];
                }else{
                    return [];
                }
            }catch(e){
                //swal("Error","No se pueden extraer los datos del layout","warning");
                return [];
            }

            return result;
        },
        saveLayoutData: function(){
            if (this.tableLayout){
                return false;
            }
            if (typeof startLoading === 'function') startLoading();
            
            var url = "admin.php?menu="+MENU+"&action=edit&saveLayoutData=1";
            var xhr = new XMLHttpRequest();
            var layoutData = {
                header:this.codeHeader,
                real_header:this.codeDOM.codeHeader,
                footer:this.codeFooter,
                real_footer:this.codeDOM.codeFooter,
                libraries:this.libraries,
                librariesJSONt:this.librariesJSONt,
                librariesJSONb:this.librariesJSONb,
                style:this.style,
                mantenimiento:this.codeMantenimiento,
                javascript:this.javascript,
                libraries_top:false,
                active:true
            };
            xhr.open('POST', url, true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.onload = function () {
                if (typeof stopLoading === 'function') stopLoading();
                if (typeof App.alert === 'function') App.alert(`Estructura General guardada con éxito`);
            };
            xhr.send(JSON.stringify(layoutData));
            return false;
        },
        saveTableData: function(){
            if (!this.tableLayout){
                return false;
            }
            if (typeof startLoading === 'function') startLoading();
            var url = "admin.php?menu="+MENU+"&action=edit&saveTableData=1";
            var xhr = new XMLHttpRequest();
            var layoutData = {
                html:this.codeTable,
                htmlParsed:this.codeDOM.codeTable,
                style:this.styleTable,
                javascript:this.javascriptTable
            };
            xhr.open('POST', url, true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.onload = function () {
                if (typeof stopLoading === 'function') stopLoading();
                if (typeof App.alert === 'function') App.alert(`Archivo ${MENU} guardado con éxito`);
            };
            xhr.send(JSON.stringify(layoutData));
            return false;
        },
        makeRequest: function (method, url) {
            return new Promise(function (resolve, reject) {
                let xhr = new XMLHttpRequest();
                xhr.open(method, url);
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
                xhr.send();
            });
        },

        array_move:function(arr, old_index, new_index) {
            if (new_index >= arr.length) {
                var k = new_index - arr.length + 1;
                while (k--) {
                    arr.push(undefined);
                }
            }
            arr.splice(new_index, 0, arr.splice(old_index, 1)[0]);
            arr = this.reset_order(arr);
            return arr; // for testing
        },
        reset_order:function(arr){
            arr = arr.map((element,index) => {
                element.order = index+1;
                return element;
            });
            return arr;
        },
        onFileChangeLibrarie(e) {
            const file = e.target.files[0];
            var reader  = new FileReader();
            reader.onloadend = (event) => {
                if (typeof startLoading === 'function') startLoading();
                var type = "css";
                if (file.name.includes(".js")) {
                    type="js";
                }else if (file.name.includes(".css")){
                    type="css";
                }else if (file.name.includes("js?")){
                    type="js";
                }else if (file.name.includes("css?")){
                    type="css";
                }
                var librarie = {
                    filename:file.name,
                    content:event.target.result,
                    type:type
                };
                var url = "admin.php?menu="+MENU+"&action=edit&saveLibrarie=1";

                var xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.onload = (event) => {
                    try{
                        if (!event.target.response["success"]){
                            swal("Error","Error enviando los datos","warning");
                        }else{
                            this.librariesJSONt.push({
                                num:new Date().getTime(),
                                url:`https://${CURRENT_USER["domain"]["domain"]}/template/estandar/${librarie.type}/${file.name}`
                            });
                        }
                    }catch(error){
                        console.log(error);
                        swal("Error","Error enviando los datos","warning");
                    }
                    if (typeof stopLoading === 'function') stopLoading();
                };
                xhr.responseType = "json";
                xhr.send(JSON.stringify(librarie));
            }
            reader.readAsText(file);
            
        },
        getRenderLibrariesTop(){
            
            var cadena = ``;
            if (this.librariesJSONt){
                for (index in this.librariesJSONt){
                    cadena+=this.librariesJSONt[index].url.includes("js") ? `<script src='${this.librariesJSONt[index].url}'></script>` : `<link rel='stylesheet' href='${this.librariesJSONt[index].url}'>`;
                }
            }
            return cadena;
        },
        getRenderLibrariesBottom(){
            var cadena = ``;
            if (this.librariesJSONb){
                for (index in this.librariesJSONb){
                    cadena+=this.librariesJSONb[index].url.includes("js") ? `<script src='${this.librariesJSONb[index].url}'></script>` : `<link rel='stylesheet' href='${this.librariesJSONb[index].url}'>`;
                }
            }
            return cadena;
        },
        getNameLibrarie:function(url){
            return url.substring(url.lastIndexOf("/")+1).split(".")[0];  
        },
        getImageLibrarie:function(url){
            return url.includes(".js") || url.includes("/js") ? `/${TEMPLATE}/images/js.svg` : `/${TEMPLATE}/images/css.svg`;
        },
        addLibrarieURL(){
            var input = document.createElement("input");
            input.classList.add("swal-content__input");
            swal({
                title:"Escribe la URL:", 
                content: input,
                closeOnClickOutside:true,
                showCancelButton:true,
                button : {
                    text: "OK",
                    value:input,
                    visible: true,
                    className: "btn btn-primary",
                    closeModal: false,
                }
            })
            .then((value) => {
                if (value && value.value){
                    this.librariesJSONt.push({num:new Date().getTime(),url:value.value});
                    swal.close();
                }
            });
        },
        removeLibrarie:function(num,type){
            swal({
                title:"Eliminar librería", 
                text: "Deseas eliminar la librería?",
                closeOnClickOutside:false,
                showCancelButton:true,
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
            })
            .then((value) => {
                if (value){
                    switch(type){
                        case `top`: this.librariesJSONt = this.librariesJSONt.filter(element => element.num!=num);break;
                        case `bottom`: this.librariesJSONb = this.librariesJSONb.filter(element => element.num!=num);break;
                    }
                }
            });
        }
    }
});
