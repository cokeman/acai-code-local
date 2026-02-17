async function crearModulo(modulo,preview){
    app.preview = preview;
    app.activateTab("cssModule");
    var modalCrearModulo = document.getElementById("modalCrearModulo");
    if (!modalCrearModulo) {
        modalCrearModulo = document.createElement("div");
        modalCrearModulo.id = "modalCrearModulo";
        document.body.appendChild(modalCrearModulo);
    }
    app.iframePaintSrc = app.iframePaintSrc.split("?")[0] + "?timestamp=" + new Date().getTime();

    if (!modalCrearModulo.classList.contains("abierto")) {

        app.opened = true;
        
        if (modulo && modulo.builder && !modulo.builder.htmlData){
            startLoading();
            var remoteModule = await loadRequiredModules([modulo],true);
            if (remoteModule["web"] && remoteModule["web"][modulo.builder.id]){
                refreshBuilderModule(modulo.builder.id,remoteModule["web"][modulo.builder.id]);
            }
            stopLoading();

        }
        if (!app.layout){
            startLoading();
            app.layout = await appLayout.loadLayoutData(20000);    
            stopLoading();
        }
        if (modulo && modulo.builder && modulo.builder.htmlData){

            var img = modulo.node.querySelector("img[data-src]");
            
            app.moduleId = modulo.builder.id;
            app.moduleLabel = modulo.builder.label;
            app.editMode = true;
            app.moduleDesc = modulo.builder.description;
            if (img){
                toDataUrl(img.src, function(myBase64) {
                    app.urlAvatar = myBase64;
                });
            }
            app.code = modulo.builder.htmlData || '';
            //if (modulo.builder.styleData) app.style = modulo.builder.styleData;
            app.style = modulo.builder.styleData || '';
            //if (modulo.builder.javascriptData) app.javascript = modulo.builder.javascriptData;
            app.javascript = modulo.builder.javascriptData || '';
            
            app.modulo = modulo;
            var lotengo = false;
            var modulosIzquierda = splitLeft.querySelectorAll("li");
            for (moduloIzq of modulosIzquierda){
                if (moduloIzq == modulo.node) lotengo = true;
            }
            for (cont in myConfig){
                if (myConfig[cont].builder.id == modulo.builder.id) lotengo = true;
            }
            app.getFullCode();  

            app.sePuedeEliminar = modulo.referencias ? false : myConfig.includes(modulo) ? false : lotengo ? false : true;


        }else{
            let date = new Date();
            app.moduleId = `module${date.getTime()}`;
            app.moduleLabel = `Módulo ${date.getTime()}`;
            app.editMode = false;
            app.modulo = null;
            app.sePuedeEliminar = false;
            app.moduleDesc = "Sin descripción";
            app.urlAvatar = `data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA+Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBkZWZhdWx0IHF1YWxpdHkK/9sAQwAIBgYHBgUIBwcHCQkICgwUDQwLCwwZEhMPFB0aHx4dGhwcICQuJyAiLCMcHCg3KSwwMTQ0NB8nOT04MjwuMzQy/9sAQwEJCQkMCwwYDQ0YMiEcITIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIy/8AAEQgBkAGQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A9MooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAr3Vw0PlpEgeWQ4UE4HuTUK3N1DPHHdRx7ZDtV4yeD6HNPvIo5nhTzTFNkmNh16c1F5l3ayxrcFJo3baGUYYHtxQBoVX89v7R+z4Gzyt+e+c4rJjS5uIftC2rtM2SJ/PAx+Hp7VbnkeO9eXHzrZlvoc0AadFYkUNwPKmjtHWTIJlNwDuHfI96ttCl7fzxzZMcKqFQEgZPOaANCo5XdNmyPflgDzjaPWqEkbD7LZNMWR2bcwPJUcgZplxarayQLG/7o3EZEZOdp5oA1qKz2hW91CeObcY4VUKoJAyec1UCtNcW0DuxEcsse7PJUAf04oA26KzkRbW/mhjby4jB5nqFOcZqoojje1kgjnDNKqtM/AkB68Z70AblRwu8kQaSPy2OcrnOKkrDgjEsemoSQCZc4OOOeKANyisqGyhku7uBg3lR7dibjhSRyaYrrNp1n57yPkn92gJMmM/yoA2KKyrRzD9vEcbokahkjc5xwTUNxaIuki4DMZXCs7Fid2SKANuisi4ElzfTIbVp0j2hR5uwLxnNN8qWSSxin3Kd0g+/k7cdMj24oA2aKoQRrbao8MWVjaEPtzwDnFX6ACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAgurVblF+ZkdDuR16g1FHZSeaklxctNsOVXaFAPrxVyigCidOb5o1uHW2Y5MQUfiM+lTG1U3XnE/L5XlbMds5qxRQBRTT2BRHuXeBCCsZUDp0ye9ST2jvN50E5hkI2sQoYEfSrVFAFRrBfs8caSMrxncsnU570z+zizK8k5eUSK5YrjIXoAO3Wr1FAFWe0Z5vOhmMMhG1iFBBH0pI7BY3t2Vz+5LE5GSxYdc1booAryWgkuXlZvlaHyiuO2euarnTZGjjVrtj5RBj+QYXHr61oUUAFUobDyfsv73PkF/4fvbvx4q7RQBDFb+Xczzbs+bt4x0wMVXXTikECJOVlhztfb69eKvUUAU4rMwSyymVpBIvzqVGWI/z0rMlEclokUN2zqzARwYG5ee/61v1GsESyGRYkDnqwUZ/OgCGa0dpzNBOYXYYf5Qwb060iWKxyW7K5/clic8liw5Oat0UAQ/Z/9N+07v8Aln5e3HvnOamoooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA//9k=`;
            app.code = document.getElementById("defaultTextCrear").innerHTML;
        }

        window.setTimeout(function(){ modalCrearModulo.classList.add("abierto");},10);
    }
    return true;
}
function cerrarCrearModulo(){
    var modalCrearModulo = document.getElementById("modalCrearModulo");
    if (!modalCrearModulo) return false;
    if (modalCrearModulo.classList.contains("abierto")) modalCrearModulo.classList.remove("abierto");
    app.code = document.getElementById("defaultTextCrear").innerHTML;
    app.style = ``;
    app.javascript = ``;
    stopLoading();
    document.getElementById('iframePaintEl').contentWindow.Vvveb.Builder.selectNode();
    app.opened = false;
    return true;
}

function toDataUrl(url, callback) {
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
}


var app = new Vue({
    el: '#modalCrearModulo',
    data: {
        opciones: [
            { label : "Code",icon:"fa-code",clase:"active",elementActive:"cssModule"},
            { label : "Style",icon:"fa-paint-brush",clase:null,elementActive:"styleModule"},
            { label : "Javascript",icon:"fa-codepen",clase:null,elementActive:"jsModule"},
            { label : "Paint",icon:"fa-delicious",clase:(USER_PLUGINS["vvvebjs"] && USER_PLUGINS["vvvebjs"]["enabled"] == 1) ? "" : "hidden",elementActive:"paintMode"},
            { label : "Save",icon:"fa-save",clase:null,elementActive:"saveCreateModule"},
            { label : "TailwindCSS",icon:"fa-book",clase:null,elementActive:"tailModule"},
            { label : "Componentes",icon:"fa-book",clase:null,elementActive:"docsModule"}
        ],
        DocsPages:[
            {
                title:"Componentes",
                data:appParser.components
            },
            {
                title:"Filtros",
                data:appParser.filters
            },
            {
                title:"BuilderData",
                data:appParser.builderData
            }
        ],
        docsActivated:0,
        preview:false,
        fullScreenIframe:false,
        sePuedeEliminar:false,
        fullScreenCode:true,
        allowedExtensions : ["jpg","jpeg"], /* en minúsculas */
        maxFileSize : 200000, /* en Bytes */
        iframeDocTail : "https://nerdcave.com/tailwind-cheat-sheet",
        iframeResultLink : `<link href="/${TEMPLATE}/css/tailwind.min.css" rel="stylesheet">`,
        code: document.getElementById("defaultTextCrear").innerHTML,
        codeDOM : null,
        codeVars : null,
        codeParsed : null,
        win:null,
        cachePath:``,
        opened:false,
        style: ``,
        javascript:``,
        activeElement : "cssModule",
        urlAvatar : `data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA+Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBkZWZhdWx0IHF1YWxpdHkK/9sAQwAIBgYHBgUIBwcHCQkICgwUDQwLCwwZEhMPFB0aHx4dGhwcICQuJyAiLCMcHCg3KSwwMTQ0NB8nOT04MjwuMzQy/9sAQwEJCQkMCwwYDQ0YMiEcITIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIy/8AAEQgBkAGQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A9MooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAr3Vw0PlpEgeWQ4UE4HuTUK3N1DPHHdRx7ZDtV4yeD6HNPvIo5nhTzTFNkmNh16c1F5l3ayxrcFJo3baGUYYHtxQBoVX89v7R+z4Gzyt+e+c4rJjS5uIftC2rtM2SJ/PAx+Hp7VbnkeO9eXHzrZlvoc0AadFYkUNwPKmjtHWTIJlNwDuHfI96ttCl7fzxzZMcKqFQEgZPOaANCo5XdNmyPflgDzjaPWqEkbD7LZNMWR2bcwPJUcgZplxarayQLG/7o3EZEZOdp5oA1qKz2hW91CeObcY4VUKoJAyec1UCtNcW0DuxEcsse7PJUAf04oA26KzkRbW/mhjby4jB5nqFOcZqoojje1kgjnDNKqtM/AkB68Z70AblRwu8kQaSPy2OcrnOKkrDgjEsemoSQCZc4OOOeKANyisqGyhku7uBg3lR7dibjhSRyaYrrNp1n57yPkn92gJMmM/yoA2KKyrRzD9vEcbokahkjc5xwTUNxaIuki4DMZXCs7Fid2SKANuisi4ElzfTIbVp0j2hR5uwLxnNN8qWSSxin3Kd0g+/k7cdMj24oA2aKoQRrbao8MWVjaEPtzwDnFX6ACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAgurVblF+ZkdDuR16g1FHZSeaklxctNsOVXaFAPrxVyigCidOb5o1uHW2Y5MQUfiM+lTG1U3XnE/L5XlbMds5qxRQBRTT2BRHuXeBCCsZUDp0ye9ST2jvN50E5hkI2sQoYEfSrVFAFRrBfs8caSMrxncsnU570z+zizK8k5eUSK5YrjIXoAO3Wr1FAFWe0Z5vOhmMMhG1iFBBH0pI7BY3t2Vz+5LE5GSxYdc1booAryWgkuXlZvlaHyiuO2euarnTZGjjVrtj5RBj+QYXHr61oUUAFUobDyfsv73PkF/4fvbvx4q7RQBDFb+Xczzbs+bt4x0wMVXXTikECJOVlhztfb69eKvUUAU4rMwSyymVpBIvzqVGWI/z0rMlEclokUN2zqzARwYG5ee/61v1GsESyGRYkDnqwUZ/OgCGa0dpzNBOYXYYf5Qwb060iWKxyW7K5/clic8liw5Oat0UAQ/Z/9N+07v8Aln5e3HvnOamoooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA//9k=`,
        modulo:null,
        moduleId : null,
        moduleLabel : null,
        moduleDesc : null,
        editMode : false,
        fullCode: null,
        iframePaintSrc : "lib/plugins/vvvebjs/editor.php"
    },
    mounted(){
        this.init()
    },
    watch:{
        fullScreenCode:function(newVal,oldVal){
            window.setTimeout(() => { this.$children[0].editor.resize(); },10);
        },
        moduleId:function(newVal,oldVal){
            this.validateId();
        },
        preview:function(newVal,oldVal){
            this.fullScreenIframe = this.preview;
        },
        code:function(newVal,oldVal){
            if (document.getElementById('iframePaintEl').contentWindow &&
                document.getElementById('iframePaintEl').contentWindow.Vvveb &&
                document.getElementById('iframePaintEl').contentWindow.Vvveb.Builder
            ) document.getElementById('iframePaintEl').contentWindow.Vvveb.Builder._loadIframe();
            this.parseDocumentFromString(this.code);
            this.getFullCode();   
        },
        style:function(newVal,oldVal){
            console.log("pepe");
        },
        cachePath:function(newVal,oldVal){
            localStorage.setItem("cachePath",newVal);
        }
    },
    methods: {
        init: async function(){
            let date = new Date();
            if (!this.moduleId) this.moduleId = `module-${date.getTime()}`;
            if (!this.moduleLabel) this.moduleLabel = `Módulo ${date.getTime()}`;

            if (!this.cachePath && localStorage.getItem("cachePath")) this.cachePath = localStorage.getItem("cachePath");
        },
        iframeExternal(){
            var sectionlinkElement = splitRight.querySelector(".wrapperContainerPreview.full [name=enlace]");
            if (sectionlinkElement){
                var link = sectionlinkElement.value + "?pruebas=1&onlyModule2=" + activeModule.builder.id;
            }else{
                var link = "/?pruebas=1&onlyModule2=" + activeModule.builder.id;
            }
            var value = websiteDomain + link;
            this.win = window.open(value,'preview');
        },
        activateDocTab:function(index){
            this.docsActivated = index;
        },
        cerrarCrearModulo: function(data,close){
            if (typeof close == 'undefined') close = true;

            if (data){
                startLoading();
                xhr = new XMLHttpRequest();
                var url = `admin.php?menu=${MENU}&action=edit${NUM ? '&num=' + NUM : ''}&generateModuleFromString=1`;
                xhr.open("POST", url, true);
                xhr.setRequestHeader("Content-type", "application/json");
                xhr.onreadystatechange = () => { 
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        var json = JSON.parse(xhr.responseText);

                        if (this.modulo){
                            let dataModule = {
                                htmlData:json.data.html,
                                styleData:json.data.style,
                                javascriptData:json.data.javascript,
                                label:this.moduleLabel,
                                description:this.moduleDesc
                            }
                            if (json.data && json.data.config && json.data.config.vars){
                                dataModule["vars"] = json.data.config.vars;
                            }

                            refreshBuilderModule(this.moduleId,dataModule);

                        }else{
                            var module = {};
                            module.data = [];
                            module.referencias = undefined;

                            module.builder = json.data.config;
                            module.builder.id = this.moduleId;

                            var firstKey = Object.keys(availableModules)[0];
                            var pathDefault = availableModules[firstKey].builder.path;
                            var newPath = pathDefault.replace(availableModules[firstKey].builder.id,this.moduleId);
                            module.builder.path = newPath;
                            module.builder.htmlData = json.data.html;
                            module.builder.styleData = json.data.style;
                            module.builder.javascriptData = json.data.javascript;

                            if (json.data && json.data.config && json.data.config.vars){
                                module.builder.vars = json.data.config.vars;
                            }

                            availableModules[this.moduleId] = new Module(module,splitsView,true);
                            availableModules[this.moduleId].add();
                            availableModulesId.push(this.moduleId);                            

                        }

                    }
                    if (close) {
                        cerrarCrearModulo();
                    }else{
                        stopLoading();
                    }
                }
                var dataString = JSON.stringify(data);
                xhr.send(dataString);
            }else{
                if (close) {
                    cerrarCrearModulo();
                }else{
                    stopLoading();
                }
            }

        },
        activateTab: function(el){
            if (this.activeElement == "paintMode"){

                try{
                    var undoIndex = document.getElementById('iframePaintEl').contentWindow.Vvveb.Undo.undoIndex;
                }catch(error){
                    var undoIndex = 0;
                }
                if (undoIndex >= 0){
                    swal({
                        title:"Cambio realizado", 
                        text: "Has modificado el código desde el editor gráfico. Deseas guardar esta modificación?",
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
                            var parser = new DOMParser();
                            var codeDOM = document.getElementById('iframePaintEl').contentWindow.Vvveb.Builder.getHtml();
                            var matched = codeDOM.match(/<body[^>]*>([\w|\W]*)<\/body>/im);
                            this.code = matched[1];
                        }else{
                            document.getElementById('iframePaintEl').contentWindow.Vvveb.Builder._loadIframe();
                        }
                    });
                }
            }
            this.activeElement = el;
        },
        extractCode: function(el){
            var div = document.createElement("div");
            div.classList.add("p-4");
            var input = document.createElement("input");
            input.classList.add("swal-content__input");
            input.value=this.cachePath;

            var label = document.createElement("label");
            label.classList.add("swal2-checkbox","mt-4");
            var checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.id = "swal2-checkbox";
            var span = document.createElement("span");
            span.classList.add("swal2-label","ml-4");
            span.innerHTML = "Eliminar los scripts";
            label.appendChild(checkbox);
            label.appendChild(span);

            div.appendChild(input);
            div.appendChild(label);

            swal({
                title:"Escribe la URL a extraer:", 
                content: div,
                closeOnClickOutside:true,
                showCancelButton:true,
                button : {
                    text: "OK",
                    value:{input:input,checkbox:checkbox},
                    visible: true,
                    className: "btn btn-primary",
                    closeModal: false,
                }
            })
                .then((value) => {
                if (value.input.value){
                    removeScripts = value.checkbox.checked;
                    value = value.input.value;
                    this.cachePath = value;
                    if (value.includes("http")){
                        startLoading();
                        fetch('https://node.cocosolution.com/extract/?type=all&url=' + encodeURI(value))
                            .then((response) => {
                            return response.json();
                        })
                            .then((myJson) => {
                            this.parseExtract(myJson,value,removeScripts);
                            swal.close();
                            stopLoading();
                        }).catch(function(e) {
                            console.log(e);
                            stopLoading();
                            swal.close();
                            swal("Error","Ha ocurrido un error en la solicitud","warning");
                        });
                    }else{
                        swal.close();
                        swal("Error","La web de destino sólo puede ser HTTPS","warning");
                    }
                }else{
                    swal.close();
                }
            });
        },
        parseExtract: function(myJson,value,removeScripts){

            var parser = new DOMParser();
            var codeDOM = parser.parseFromString(myJson.html, 'text/html');
            var body = codeDOM.querySelector("body");

            if (removeScripts){
                var scripts = codeDOM.querySelectorAll("script");   
                for (script of scripts){
                    script.parentNode.removeChild(script);
                }
            }else{
                var scripts = codeDOM.querySelectorAll("head script");
                for (script of scripts){
                    body.appendChild(script);
                }
            }
            var styleInLine = body.querySelectorAll("[style]");
            for (style of styleInLine){
                var background = style.style.backgroundImage.replace(`"`,`'`).replace(` `,``).replace(`&quot;`,`'`);
                if (!background.includes("http") && background.includes("url")){
                    var url = background.match(/url\(["']?([^"']*)["']?\)/)[1];
                    var urlParsed = new URL(value);
                    if (!url.startsWith('//') && !url.startsWith('/') && !url.startsWith('http')) {
                        url = "/" + url;
                    }
                    style.setAttribute("style",`background-image:url('https://${urlParsed.host}${url}')`);
                    console.log(style.style.backgroundImage);
                }

            }
            var links = body.querySelectorAll("a[href]");
            for (link of links){
                link.href="javascript:void(0);";
            }
            if (myJson.image) this.urlAvatar = `data:image/jpeg;base64,` + myJson.image;
            //this.code = beautify(body.innerHTML, { indent_size: 2, space_in_empty_paren: true });
            this.code = body.innerHTML;
            this.code = this.code.replace("&quot;","");
            this.style = myJson.css;  
        },
        beautify:function(){
            switch(this.activeElement){
                case "cssModule": this.code = this.beautifyCode(this.code);break;
            }
        },
        validateId:function(cadena){
            if (cadena){
                return cadena.replace(/\W/g,'').toLowerCase();   
            }else{
                this.moduleId = this.moduleId.replace(/\W/g, '');    
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
        parseDocumentFromString: function(code){
            var parser = new DOMParser();
            var codeDOM = parser.parseFromString(code, 'text/html');

            var all = codeDOM.querySelectorAll("[data-field-type]"); 
            var resultados = {};
            var padreActual = null;
            var cache = "";
            var multi = {field:null,el:null,label:null};
            var typesRelations = {
                textfield:"textfield",
                link:"link",
                upload:"upload",
                uploadBackground:"upload",
                uploadMulti:"upload",
                multi:"multi",
                multiv2:"multi",
                textbox:"textbox",
                wysiwyg:"wysiwyg"
            };

            for (const element of all) {
                
                const type = element.getAttribute("data-field-type");
                if (!type) continue;
                const label = element.getAttribute("data-field-label");
                if (!label) continue;
                const field = this.validateId(label);
                if (!resultados[field]){
                    if (multi.el){
                        
                        if (this.childOf(element,multi.el)){
                            resultados[multi.field]["vars"][field] = {
                                field:field,
                                label:label,
                                type:typesRelations[type]
                            };
                            switch(type){
                                case "link":
                                    resultados[multi.field]["vars"][field + "_anchor"] = {
                                        field:field+"_anchor",
                                        label:label+" Texto",
                                        type:'textfield'
                                    }; 
                                    break;
                            }
                        }else{
                            // EN CASO DE QUE NO SEA HIJO DEL MULTI, MONTAMOS EL MULTI Y LO RESETEAMOS
                            // PERO PRIMERO VAMOS A ELIMINAR LOS HIJOS DIRECTOS DEL MULTI QUE NO DISPONGAN DE VARIABLES


                            for (const hijoMulti of multi.el.parentNode.children){
                                if (!hijoMulti.querySelector("[data-field-type]")) hijoMulti.parentNode.removeChild(hijoMulti);
                            }
                            multi = {field:null,el:null,label:null};

                            resultados[field] = {
                                field:field,
                                label:label,
                                type:typesRelations[type]
                            }; 
                            switch(type){
                                case "link":
                                    resultados[field+"_anchor"] = {
                                        field:field+"_anchor",
                                        label:label+" Texto",
                                        type:'textfield'
                                    }; 
                                    break;
                            }

                        }

                    }else{
                        resultados[field] = {
                            field:field,
                            label:label,
                            type:typesRelations[type]
                        };  
                        switch(type){
                            case "multi":
                            case "multiv2":
                                multi = {field:field,el:element,label:label};
                                resultados[field]["vars"] = {};
                                break;
                            case "link":
                                resultados[field+"_anchor"] = {
                                    field:field+"_anchor",
                                    label:label+" Texto",
                                    type:'textfield'
                                }; 
                                break;
                        }
                    }
                    if (multi.el){

                        switch(type){
                            case "multi":
                            case "multiv2":
                                multi = {field:field,el:element,label:label};
                                resultados[field]["vars"] = {};
                                break;
                        }
                    }

                }else{
                    element.parentNode.removeChild(element);
                }

            } 
            // EN CASO DE QUE EL ULTIMO ELEMENTO SEA UN HIJO REPETIMOS EL PASO ANTERIOR PARA LIMPIAR ESTE MULTI
            if (multi.el){

                for (const hijoMulti of multi.el.parentNode.children){
                    if (!hijoMulti.querySelector("[data-field-type]")) hijoMulti.parentNode.removeChild(hijoMulti);
                }
                
                multi = {field:null,el:null,label:null};
            }
            //console.log({builderJSON_DATA:resultados});
            var all = codeDOM.querySelectorAll("[data-field-translate]"); 
            for (const element of all) {
                let cadena = element.innerHTML;
                element.innerHTML = `<?=t_var("${cadena}");?>`;
            }
            var codeParsed = codeDOM.querySelector("body").innerHTML;
            codeParsed = appParser.parseComponents(codeParsed);

            this.codeParsed = codeParsed;
            
            this.codeVars = resultados;
        },
        getFullCode:function(){

            let result = `<html><head>`;
            if (this.layout) result += !this.layout.librariesJSONt && !this.layout.librariesJSONb ? this.iframeResultLink : ``;

            if (this.layout){
                result += appLayout.getRenderLibrariesTop();
                //result += this.layout.libraries ? this.layout.libraries : ``;
            }            
            if (this.layout && this.layout.style) result += `<style data-external>${this.layout.style}</style>`;
            result += `</head><body>`;
            result += `<style data-external>${this.style}</style>`;
            result += `<script data-external>${this.javascript}</script>`;
            result += this.getFullCodeWithPaths(this.code);
            if (this.layout){
                result += appLayout.getRenderLibrariesBottom();
            }
            result += `</body></html>`;
            this.fullCode = result;

        },
        getFullCodeWithPaths:function(code){
            var value = "https://" + CURRENT_USER["domain"]["domain"];
            
            var parser = new DOMParser();
            var codeDOM = parser.parseFromString(code, 'text/html');
            var urlParsed = new URL(value);

            // ARREGLAMOS LOS BACKGROUND IMAGE
            var backgroundStyles = codeDOM.querySelectorAll("[style]");
            for (style of backgroundStyles){
                var match = style.style.backgroundImage.match(/url\(["']?([^"']*)["']?\)/);
                if (!match) continue;
                var url = match[1];

                if (url.startsWith(value)){   
                    url = url.replace(value,``);
                }
                if (!url.startsWith('//') && !url.startsWith('/') && !url.startsWith('http')) {
                    url = "/" + url;
                }
                style.setAttribute("style",`background-image:url('https://${urlParsed.host}${url}')`);
            }
            // ARREGLAMOS LAS IMAGENES
            var images = codeDOM.querySelectorAll("img[src]");
            for (image of images){
                var url = image.getAttribute("src");
                if (url.startsWith(value)){   
                    url = url.replace(value,``);
                }
                if (!url.startsWith('//') && !url.startsWith('/') && !url.startsWith('http')) {
                    url = "/" + url;
                }
                image.setAttribute("src",`https://${urlParsed.host}${url}`);
            }
            return codeDOM.body.innerHTML;
            //return codeDOM.outerHTML;
        },
        getStyleCode: function(){
            let result = this.style ? `<style data-external>${this.style}</style>`: ``;
            if (this.layout){
                result += this.layout.style ? `<style data-external>${this.layout.style}</style>` : ``;
            }
            return result;
        },
        onFileChangeAvatar(e) {
            const file = e.target.files[0];
            var reader  = new FileReader();
            reader.onloadend = () => {
                this.urlAvatar = reader.result;
            }
            var extension = file.name.match(/\.[0-9a-z]+$/i);
            var size = file.size;
            if (!this.allowedExtensions.includes(extension[0].replace(".","").toLowerCase()) || size > this.maxFileSize){
                swal("Error!","La imagen debe ser formato JPG y no puede exceder los 200Kb","warning");
                return false;
            }else{
                reader.readAsDataURL(file);    
            }
        },
        downloadImage:async function(){
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
                        saveRecord(false,false);
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
            var value = websiteDomain + link;
            startLoading();

            fetch('https://node.cocosolution.com/extract/?type=image',{
                method:"POST",
                headers:{"Content-Type":"application/x-www-form-urlencoded;charset=UTF-8"},
                body:`html=${encodeURIComponent(this.fullCode)}`
            })
                .then((response) => {
                return response.json();
            })
                .then((myJson) => {
                if (myJson.image) this.urlAvatar = `data:image/jpeg;base64,` + myJson.image;
                swal.close();
                stopLoading();
                return true;
            }).catch(function(e) {
                console.log(e);
                stopLoading();
                swal.close();
                swal("Error","Ha ocurrido un error en la solicitud","warning");
                return false;
            });  
        },
        uploadModule(close){
            if (!this.urlAvatar || !this.moduleId || !this.moduleLabel || !this.code){
                swal("Error!","Debes rellenar todos los campos","warning");
                return false;
            }
            console.log(this);
            var data = {
                image:this.urlAvatar,
                tailWind: true,
                id:this.moduleId,
                label:this.moduleLabel,
                description:this.moduleDesc,
                vars:JSON.parse(JSON.stringify(this.codeVars)),
                html:this.code,
                htmlParsed:this.codeParsed,
                style:this.style,
                javascript:this.javascript,
                editMode:this.editMode
            }

            this.cerrarCrearModulo(data,close);
        },
        deleteModule(){
            if (!this.modulo || !this.modulo.builder) return null;
            swal({
                title:"Eliminar el módulo", 
                text: "Deseas eliminar el módulo?",
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
                    startLoading();
                    xhr = new XMLHttpRequest();
                    var url = `admin.php?menu=${MENU}&action=edit${NUM ? '&num=' + NUM : ''}&deleteModule=${this.modulo.builder.id}`;
                    xhr.open("POST", url, true);
                    xhr.setRequestHeader("Content-type", "application/json");
                    xhr.onreadystatechange = () => { 
                        if (xhr.readyState == 4 && xhr.status == 200 && xhr.responseText == "ok") {

                            this.modulo.node.parentNode.removeChild(this.modulo.node);
                            if (availableModules[this.modulo.builder.id]) delete availableModules[this.modulo.builder.id];
                            if (modules[this.modulo.builder.id]) delete modules[this.modulo.builder.id];
                            if (localModules[this.modulo.builder.id]) delete localModules[this.modulo.builder.id];
                            var modulosIzquierda = splitLeft.querySelectorAll("li");
                            for (moduloIzq of modulosIzquierda){
                                if (moduloIzq == this.modulo.node) {
                                    moduloIzq.parentNode.removeChild(moduloIzq);

                                }
                            }
                            for (cont in myConfig){
                                if (myConfig[cont].builder.id == this.modulo.builder.id) myConfig[cont].delete();
                            }
                            var glideslide = document.querySelector(".glide__slide>li[data-id='"+this.moduleId+"']");
                            glideslide.parentNode.removeChild(glideslide);
                            needToSave();
                        }

                        cerrarCrearModulo();
                    }
                    xhr.send();
                }else{
                    document.getElementById('iframePaintEl').contentWindow.Vvveb.Builder._loadIframe();
                }
            });

        }
    }
});

//if (CURRENT_USER["isAdmin"]) crearModulo();