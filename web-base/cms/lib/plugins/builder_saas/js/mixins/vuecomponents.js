var vuecomponents = {
    "breadCrumb" : {
        type : "TAG",
        tags : ["TWIG","ACAI"],
        description : `Muestra el breadcrumb de la página. Nota las variables que se emitan en v-prevlinks ( o c-prevlinks ) deben precargarse con un v-for para que el sistema encuentre el registro a incorporar`,
        example : [
            {
                lexer:"ACAI",
                code:`<div v-for="apartado in apartados" v-where="num=3">\n\t<breadCrumb v-record="thisrecord" v-prevlinks="[apartado]"></breadCrumb>\n</div>`
            },
            {
                lexer:"TWIG",
                code:`<div c-for="apartado in apartados" c-where="num=3">\n\t<breadCrumb c-record="thisrecord" c-prevlinks="[apartado]"></breadCrumb>\n</div>`
            }
        ],
        replace2 : (el) => {
            let titulo = `thisrecord`;
            let prevLinks = [];
            if (el.getAttribute("c-record")){
                titulo = el.getAttribute("c-record");
                el.removeAttribute("c-record");
            }
            if (el.getAttribute("c-prevlinks")){
                prevLinks = el.getAttribute("c-prevlinks");
                el.removeAttribute("c-prevlinks");
            }
            var data = document.createElement("div");
            data.classList = el.classList;
            data.attributes = el.attributes;
            data.innerHTML = `{{ ${titulo} | muestra_breadcrumb(${prevLinks ? prevLinks : '[]'},'w-full breadcrumb-v2') }}`;
            el.parentNode.replaceChild(data,el);
            return el;
        },
        replace : (el) => {
            let titulo = `$thisrecord`;
            let prevLinks = [];
            if (el.getAttribute("v-record")){
                let resultVariables = appParser.parseVariables3(el.getAttribute("v-record"));
                if (resultVariables[1]){
                    titulo = resultVariables[1];
                }
                el.removeAttribute("v-record");
            }
            if (el.getAttribute("v-prevlinks")){
                let prevLinksString = el.getAttribute("v-prevlinks").replace("[","").replace("]","");
                prevLinks = prevLinksString.split(",").map(e => `$${e}`);
            }
            var data = document.createElement("div");
            data.classList = el.classList;
            data.attributes = el.attributes;

            data.innerHTML = '|*' + window.btoa(`<?=muestra_breadcrumb(${titulo},[${prevLinks.join(",")}],'w-full breadcrumb-v2');?>`) + '*|';
            el.parentNode.replaceChild(data,el);
            return el;
        }
    },
    "socialBar" : {
        type : "TAG",
        description : `Añade un bloque para compartir la págian en redes sociales`,
        example : [
            {
                lexer:"ACAI",
                code:`<socialBar v-title="thisrecord.title"></socialBar>`
            },
            {
                lexer:"TWIG",
                code:`<socialBar c-title="thisrecord.title"></socialBar>`
            }
        ],
        replace2 : (el) => {
            let titulo = `thisrecord.title`;
            if (el.getAttribute("c-title")){
                titulo = el.getAttribute("c-title");
                el.removeAttribute("c-title");
            }
            var data = document.createElement("div");
            data.classList = el.classList;
            data.attributes = el.attributes;

            data.innerHTML = `{{ 'redes_sociales' | module({'url':"https://#{server.HTTP_HOST}#{server.REQUEST_URI}",'titulo':${titulo}}) | raw }}`;

            el.parentNode.replaceChild(data,el);
            return el;
        },
        replace : (el) => {
            let titulo = `t($thisrecord, "title")`;
            if (el.getAttribute("v-title")){
                let resultVariables = appParser.parseVariables3(el.getAttribute("v-title"));
                if (resultVariables[1]){
                    titulo = resultVariables[1];
                }
                el.removeAttribute("v-title");
            }
            var data = document.createElement("div");
            data.classList = el.classList;
            data.attributes = el.attributes;

            data.innerHTML = '|*' + window.btoa(`<?=BuilderModule("redes_sociales", array("url" => urlencode(protocol()."://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]), "titulo" => ${titulo}));?>`) + '*|';
            el.parentNode.replaceChild(data,el);
            return el;
        }
    },
    "menu" : {
        type:"TAG",
        description : `Reemplaza el elemento por el menú principal de la web. Se pueden añadir clases al UL y al LI así como añadir el nivel de profundidad del menú`,
        example : [
            {
                lexer:"ACAI",
                code:`<menu detph="0" class="navbar my-0 lg:mt-0 w-full flex-grow lg:flex lg:justify-end lg:w-auto text-white hidden lg:block transition3s">
<li class="uppercase block px-4 py-2 lg:py-0 hover:text-blue-400 navbar-link">Opción</li>
<li class="uppercase block px-4 py-2 lg:py-0 hover:text-blue-400 navbar-link">Opción</li>
<li class="uppercase block px-4 py-2 lg:py-0 hover:text-blue-400 navbar-link">Opción</li>
</menu>`
            },
            {
                lexer:"TWIG",
                code:`<menu detph="0" class="navbar my-0 lg:mt-0 w-full flex-grow lg:flex lg:justify-end lg:w-auto text-white hidden lg:block transition3s">
<li class="uppercase block px-4 py-2 lg:py-0 hover:text-blue-400 navbar-link">Opción</li>
<li class="uppercase block px-4 py-2 lg:py-0 hover:text-blue-400 navbar-link">Opción</li>
<li class="uppercase block px-4 py-2 lg:py-0 hover:text-blue-400 navbar-link">Opción</li>
</menu>`
            }
        ],
        subattr : [],
        shortcode : `tmenu`,
        replace2: (el) => {
            return appParser.components["menu"].replace(el,2);
        },
        replace : (el,type = null) => {
            let depth = el.getAttribute("depth") ? el.getAttribute("depth") : 0;
            let liNode = el.querySelector("li");
            if (!liNode) return el;

            let aNode = liNode.querySelector("a");
            let liAClass = aNode ? aNode.classList.toString() : ``;

            let liClass = liNode ? liNode.classList.toString() : ``;
            let ulClass = el.classList.toString();

            let submenuUl = liNode.querySelector("ul");

            if (submenuUl){
                submenuUlClass = submenuUl.classList.toString();
                submenuLi = submenuUl.querySelector("li");
                submenuLiClass = submenuLi ? submenuLi.classList.toString() : ``;
            }else{
                submenuUlClass = '';
                submenuLiClass = '';
            }
            let php = '|*' + window.btoa(`<? CustomCode::Menu(${depth},["aClass" => "${liAClass}","liClass" => "${liClass}","submenuUlClass" => "${submenuUlClass}","submenuLiClass" => "${submenuLiClass}"]); ?>`) + '*|';
            switch(type){
                case 2:
                    php = `{% set tw_apartados = menu(${depth},{'aClass':'${liAClass}','liClass':'${liClass}','submenuUlClass':'${submenuUlClass}','submenuLiClass':'${submenuLiClass}'}) %}`;
                    break;
                default :
            }
            let string = `<ul class='${ulClass}'>${php}</ul>`;
            el.outerHTML = string;
            return el;
        }
    },
    "v-class": {
        type:"ATTRIBUTE",
        description:`Permite añadir clases de forma dinámica. Las clases se añaden a través de un objeto poniendo como key las clases en un string y valor la condicion. `,
        example:[
            {
                lexer:"ACAI",
                code:`<h1 class="clase1" v-class="{'active' : thisrecord.num == categoria.num,'hidden' : thisrecord.num == 32 || thisrecord.num == 45}">Texto de prueba</h1>`
            },
            {
                lexer:"TWIG",
                code:`<h1 class="clase1" c-class="{'active' : thisrecord.num == categoria.num,'hidden' : thisrecord.num == 32 || thisrecord.num == 45}">Texto de prueba</h1>`
            }
        ],
        shortcode:``,
        replace: (el) => {
            let attr = el.getAttribute("v-class");
            if (!attr) return el;
            el.removeAttribute("v-class");
            var strings = attr.match(/'([^']+)'/g);
            var cadenaConv = attr;
            var stringsBase64 = [];
            var lexBase64 = [];
            for (const stringCad of strings){
                let base64 = window.btoa(stringCad.substring(1,stringCad.length-1));
                stringsBase64.push(base64);
                cadenaConv = cadenaConv.replace(stringCad,`'${base64}'`);
            }
            cadenaConv = cadenaConv.replace(/\s+\|\s+/g,`|`)
            cadenaConv = cadenaConv.replace(/'(\s?):(\s?)/g,`' : `);
            var result = cadenaConv.substring(1,cadenaConv.length-1).split(" ").map((rec) => {
                var lex = appParser.lexicalExpressionAnalysis(`{{${rec}}}`,true);
                var aux = lex.replace("{{","").replace("}}","");
                if (aux != rec) lexBase64.push(window.btoa(lex));
                return aux == rec ? rec : window.btoa(lex);
            })
            string = result.join(" ");

            var sepJsonProperties = string.split(",");
            var newString = [];
            if (sepJsonProperties){

                for (const jsonPropertie of sepJsonProperties){
                    newString.push(`"${jsonPropertie.split(":")[0]}" : "${jsonPropertie.split(":")[1]}"`);
                }
            }
            string = `{${newString.join(",")}}`;



            if (stringsBase64){
                for (const base of stringsBase64){
                    string = string.replace(base,window.atob(base));
                }
            }
            if (lexBase64){
                for (const base of lexBase64){
                    string = string.replace(base,window.atob(base));
                }
            }

            try{
                var stringPHP = ``;
                var objetoString = JSON.parse(string);
                for (obj in objetoString){
                    stringPHP+='|*' + window.btoa(`<? if (${objetoString[obj]}) { echo ' '.${obj}; };?>`) + '*|';
                }
                if (stringPHP){
                    let claseActual = el ? el.classList.toString() : ``;
                    claseActual+=stringPHP;
                    el.setAttribute("class",claseActual);
                }
            }catch(error){
                console.error(error);
            }

            return el;
        }
    },
    "v-form": {
        type:"TAG",
        description:`Permite generar un formulario del sistema personalizado:`,
        example:[
            {
                lexer:"ACAI",
                code:`<v-form class="py-2 px-4 bg-teal-400 font-bold text-white text-xl rounded"></v-form>

Atributos:
    - tableName ( String ) : Tabla de destino donde se almacenarán los registros ( la tabla debe estar creada en el cms ). La tabla debe contener los campos url además de los campos del formulario que se quieran almacenar
    - mailRecord ( Array ) : ['correos','SOLICITUD'] -> Tabla de correos y key ( campo "identificador" ) del correo a enviar. Campos necesarios de la tabla : identificador, asunto, cuerpo
    - sendTo ( String ) : Correos a los que se va a enviar el formulario ( separados por coma )
    - sendToClient ( String ) : Se define poniendo el campo que se usará con el email del cliente.
    - captcha ( Boolean ) : Si se define, se aplicará el captcha ( requiere datos rellenos en configuración ). Se puede añadir el elemento <captcha/> o se añadirá por defecto al final
    - showImages ( Boolean ) : Muestra el correo con los archivos subidos en thumbnail en lugar de enlaces
    - attachFiles ( Boolean ) : Adjunta los archivos enviados por el formulario al correo.
    - messageOK ( String ) : Mensaje que se muestra al enviar el formulario. Por defecto : Mensaje Enviado ( texto general )
    - messageKO ( String ) : Mensaje que se muestra no rellenar el formulario correctamente. Por defecto : Campos requeridos ( texto general )
    - action ( String ) : Si se define con la ruta de un Hook este se enviará directamente al hook en lugar de pasar por el sistema normal y devolverá el resultado a la variable form
    - redirectTo ( String ) : Si se define el formulario redireccionará al destino
    - header ( String ) : Define el html de la cabecera del email
    - footer ( String ) : Define el html del footer del email
    - styles ( String ) : Define los estilos para el email

Campos ( type ):
    - <input name="nombreDelCampo" type="text"> : Campo de tipo texto / textField. Acepta los atributos : required
    - <input name="nombreDelCampo" type="hidden"> : Campo oculto / textField. Acepta los atributos : required
    - <input name="nombreDelCampo" type="checkbox"> : Campo check / checkbox. Acepta los atributos : required
    - <textarea name="nombreDelCampo"></textarea> : Campo textbox / textbox. Acepta los atributos : required
    - <select name="nombreDelCampo"></select> : Campo selector / list. Acepta los atributos : required
    - <input multiple type="file" name="nombreDelCampo[]" > : Campo file, si quieres enviar la url del archivo en el correo 'nombreDelCampo_text' : required

`           },
            {
                lexer:"TWIG",
                code:`<c-form class="py-2 px-4 bg-teal-400 font-bold text-white text-xl rounded"></c-form>

Atributos:
    - tableName ( String ) : Tabla de destino donde se almacenarán los registros ( la tabla debe estar creada en el cms ). La tabla debe contener los campos url además de los campos del formulario que se quieran almacenar
    - mailRecord ( Array ) : ['correos','SOLICITUD'] -> Tabla de correos y key ( campo "identificador" ) del correo a enviar. Campos necesarios de la tabla : identificador, asunto, cuerpo
    - sendTo ( String ) : Correos a los que se va a enviar el formulario ( separados por coma )
    - sendToClient ( String ) : Se define poniendo el campo que se usará con el email del cliente.
    - captcha ( Boolean ) : Si se define, se aplicará el captcha ( requiere datos rellenos en configuración ). Se puede añadir el elemento <captcha/> o se añadirá por defecto al final
    - honeypot ( Boolean ) : Si se define, creará una variable "full_user_name" que en caso de ser rellenada CocoParser la dará como captcha fallido.
    - showImages ( Boolean ) : Muestra el correo con los archivos subidos en thumbnail en lugar de enlaces
    - attachFiles ( Boolean ) : Adjunta los archivos enviados por el formulario al correo.
    - messageOK ( String ) : Mensaje que se muestra al enviar el formulario. Por defecto : Mensaje Enviado ( texto general )
    - messageKO ( String ) : Mensaje que se muestra no rellenar el formulario correctamente. Por defecto : Campos requeridos ( texto general )
    - action ( String ) : Si se define con la ruta de un Hook este se enviará directamente al hook en lugar de pasar por el sistema normal y devolverá el resultado a la variable form
    - redirectTo ( String ) : Si se define el formulario redireccionará al destino
    - emailMode ( String ) : Si se define el formulario enviará el correo en formato TWIG
    - emailB64 ( Boolean ) : Si se define el formulario descodificará el correo en base64 para imprimirlo ( campo código )
    - redirect ( Variable ) : Si se define el formulario redireccionará al destino definido en formato TWiG
    - header ( String ) : Define el html de la cabecera del email
    - footer ( String ) : Define el html del footer del email
    - styles ( String ) : Define los estilos para el email

Campos ( type ):
    - <input name="nombreDelCampo" type="text"> : Campo de tipo texto / textField. Acepta los atributos : required
    - <input name="nombreDelCampo" type="hidden"> : Campo oculto / textField. Acepta los atributos : required
    - <input name="nombreDelCampo" type="checkbox"> : Campo check / checkbox. Acepta los atributos : required
    - <textarea name="nombreDelCampo"></textarea> : Campo textbox / textbox. Acepta los atributos : required
    - <select name="nombreDelCampo"></select> : Campo selector / list. Acepta los atributos : required

`
            }
        ],
        shortcode:``,
        replace: (el ,type = '') => {
            var number = Math.random() // 0.9394456857981651
            var id = number.toString(36).substr(2, 9); // 'xtis06h6'

            var form = document.createElement("form");
            form.id = id;
            if (el.hasAttribute("class")) form.setAttribute("class",el.getAttribute("class"));
            if (el.hasAttribute("method")) form.setAttribute("method",el.getAttribute("method"));
            if (el.hasAttribute("multipart")) form.setAttribute("multipart",el.getAttribute("multipart"));
            if (el.hasAttribute("enctype")) form.setAttribute("enctype",el.getAttribute("enctype"));

            var nodeId = document.createElement("input");
            nodeId.name = `form`;
            nodeId.value = id;
            nodeId.type = "hidden";

            el.appendChild(nodeId);

            form.appendChild(el.cloneNode(true));

            let keys = [];
            form.querySelectorAll('select, input, textarea').forEach(element => {
                keys.push(element.getAttribute('name'));
            });

            // Ahora vamos a determinar el tipo de campo que es cada uno
            let object = {};
            for (const key of keys){

                var node = el.querySelector(`[name="${key}"]`);
                if (!node) continue;
                if (key.split("[")[1]){
                    node.name = `cocoForm[${key.split("[")[0]}]${key.substring(key.indexOf("["))}`;
                }else{
                    node.name = `cocoForm[${key}]`;
                }


                if (node.getAttribute("type")){
                    object[key] = { type:node.getAttribute("type") };
                }else{
                    switch(node.tagName.toLowerCase()){
                        case "textarea": object[key] = { type: "textarea" }; break;
                        case "select": object[key] = { type: "select" }; break;
                    }
                }
                if (node.hasAttribute("required")) object[key].required = true;
            }

            // Ahora vamos a setear todas las variables de los atributos
            let tableName = el.hasAttribute("tableName") ? el.getAttribute("tableName") : null;
            let mailRecord = el.hasAttribute("mailRecord") ? el.getAttribute("mailRecord") : null;
            let sendTo = el.hasAttribute("sendTo") ? el.getAttribute("sendTo") : null;
            let header = el.hasAttribute("header") ? el.getAttribute("header") : null;
            let footer = el.hasAttribute("footer") ? el.getAttribute("footer") : null;
            let styles = el.hasAttribute("styles") ? el.getAttribute("styles") : null;
            let sendToClient = el.hasAttribute("sendToClient") ? el.getAttribute("sendToClient") : null;
            let captcha = el.hasAttribute("captcha") ? true : false;
            let honeypot = el.hasAttribute("honeypot") ? true : false;
            let messageOK = el.hasAttribute("messageOK") ? el.getAttribute("messageOK") : null;
            let showImages = el.hasAttribute("showImages") ? true : false;
            let attachFiles = el.hasAttribute("attachFiles") ? true : false;
            let messageKO = el.hasAttribute("messageKO") ? el.getAttribute("messageKO") : null;
            let action = el.hasAttribute("action") ? el.getAttribute("action") : null;
            let redirectTo = el.hasAttribute("redirectTo") ? el.getAttribute("redirectTo") : null;
            let emailMode = el.hasAttribute("emailMode") ? el.getAttribute("emailMode") : null;
            let emailB64 = el.hasAttribute("emailB64") ? el.getAttribute("emailB64") : null;

            // Ahora vamos a insertar el PHP que llama a la clase CocoForms
            let PHP = "|*" + window.btoa(`
            <?
                $variables = json_decode('${JSON.stringify(object)}',true);
                $form = CocoParser::cocoForm([
                    "id" => "${id}",
                    "tableName${!tableName ? "_null" : ""}" => ${tableName && tableName.indexOf("|*") > -1 ? window.atob(tableName.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : '"' + tableName + '"'},
                    "mailRecord${!mailRecord ? "_null" : ""}" => ${mailRecord && mailRecord.indexOf("|*") > -1 ? window.atob(mailRecord.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : mailRecord },
                    "sendTo${!sendTo ? "_null" : ""}" => ${sendTo && sendTo.indexOf("|*") > -1 ? window.atob(sendTo.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : '"' + sendTo + '"'},
                    "sendToClient${!sendToClient ? "_null" : ""}" => ${sendToClient && sendToClient.indexOf("|*") > -1 ? window.atob(sendToClient.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : '"' + sendToClient + '"'},
                    "captcha" => ${captcha ? true : false},
                    "honeypot" => ${honeypot ? true : false},
                    "messageOK${!messageOK ? "_null" : ""}" => ${messageOK && messageOK.indexOf("|*") > -1 ? window.atob(messageOK.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : '"' + messageOK + '"'},
                    "showImages" => ${showImages ? true : false},
                    "attachFiles" => ${attachFiles ? true : false},
                    "messageKO${!messageKO ? "_null" : ""}" => ${messageKO && messageKO.indexOf("|*") > -1 ? window.atob(messageKO.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : '"' + messageKO + '"'},
                    "action" => "${action}",
                    "redirectTo" => "${redirectTo}",
                    "emailMode" => "${emailMode}",
                    "emailB64" => "${emailB64 ? true : false}",
                    "header${!header ? "_null" : ""}" => ${header && header.indexOf("|*") > -1 ? window.atob(header.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : '"' + header + '"'},
                    "footer${!footer ? "_null" : ""}" => ${footer && footer.indexOf("|*") > -1 ? window.atob(footer.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : '"' + footer + '"'},
                    "styles${!styles ? "_null" : ""}" => ${styles && styles.indexOf("|*") > -1 ? window.atob(styles.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : '"' + styles + '"'},
                    "variables" => $variables
                ]);
            ?>
            `) + "*|";
            switch(type){
                case 2:
                    PHP = `
                    \n {% set variables = ${JSON.stringify(object)} %} \n
                    \n {% set form = cocoForm({
                        'id' : '${id}',
                        'tableName${!tableName ? "_null" : ""}' : ${tableName ? tableName : '""'},
                        'mailRecord${!mailRecord ? "_null" : ""}' : ${mailRecord && mailRecord.indexOf("|*") > -1 ? window.atob(mailRecord.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,``).replace(`;?>`,``) : mailRecord },
                        'sendTo${!sendTo ? "_null" : ""}' : ${sendTo ? sendTo : '""'},
                        'sendToClient${!sendToClient ? "_null" : ""}' : ${sendToClient? sendToClient : '""'},
                        'captcha' : ${captcha ? true : false},
                        'honeypot' : ${honeypot ? true : false},
                        'messageOK${!messageOK ? "_null" : ""}' : ${messageOK ? messageOK : '""'},
                        'showImages' : ${showImages ? true : false},
                        'attachFiles' : ${attachFiles ? true : false},
                        'messageKO${!messageKO ? "_null" : ""}' : ${messageKO ? messageKO : '""'},
                        'action' : '${action}',
                        'redirectTo' : ${el.hasAttribute("redirect") ? el.getAttribute("redirect") : "'" + redirectTo + "'"},
                        'emailMode' : ${el.hasAttribute("emailMode") ? el.getAttribute("emailMode") : "'" + emailMode + "'"},
                        'emailB64' : ${el.hasAttribute("emailB64") ? true : false},
                        'header${!header ? "_null" : ""}' : ${header ? header : '""'},
                        'footer${!footer ? "_null" : ""}' : ${footer ? footer : '""'},
                        'styles${!styles ? "_null" : ""}' : ${styles ? styles : '""'},
                        'variables' : variables
                    }) %}
`;
                    break;
            }

            if (captcha){
                let existeCaptcha = el.querySelector("captcha");
                let phpCaptcha = `
<? global $configuracionRecord;?>
    <? if (hasRecaptcha()) {?>
        <div class="g-recaptcha flex justify-center" data-sitekey="<?=$configuracionRecord["site_key_recaptcha"];?>"></div>
    <? }else{?>
        <div>
            <img src="/captcha.php" style="width: 210px; display: block; margin: 20px auto;">
            <input type="text" class="form-control" name="captcha" style="max-width: 210px;" placeholder="Captcha">
        </div>
    <? }?>

`;
                let newNodeCaptcha = document.createElement("div");
                newNodeCaptcha.innerHTML = "|*" + window.btoa(phpCaptcha) + "*|";

                switch(type){
                    case 2:
                        phpCaptcha = `
\n {% set configuracionRecord = 'configuracion' | get('num != 0','num desc',1) %} {% set configuracionRecord = configuracionRecord.0 %} \n
\n {% if hasRecaptcha() %} \n
    <div class="g-recaptcha flex justify-center" data-sitekey="{{ configuracionRecord.site_key_recaptcha }}"></div>
\n {% else %} \n
    <div>
        <img src="/captcha.php" style="width: 210px; display: block; margin: 20px auto;">
        <input type="text" class="form-control" name="captcha" style="max-width: 210px;" placeholder="Captcha">
    </div>
\n {% endif %} \n
`;
                        newNodeCaptcha.innerHTML = phpCaptcha;
                        break;
                }

                if (existeCaptcha){
                    if (existeCaptcha.hasAttribute("class")) newNodeCaptcha.setAttribute("class",existeCaptcha.getAttribute("class"));
                    existeCaptcha.parentNode.replaceChild(newNodeCaptcha,existeCaptcha);
                }else{
                    newNodeCaptcha.classList.add("text-center","my-8","flex","justify-center","w-full");
                    el.appendChild(newNodeCaptcha);
                }

            }else{
                let existeCaptcha = el.querySelector("captcha");
                if (existeCaptcha) existeCaptcha.parentNode.removeChild(existeCaptcha);
            }

            if (honeypot){
                let phpHoneypot = `
<div class="field seturecap">
    <input type="text" id="full_user_name" name="cocoForm[full_user_name]" placeholder="Nombre" class="form-control">
    <style>.seturecap {position: absolute;width: 1px;height: 1px;padding: 0;margin: -1px;overflow: hidden;clip: rect(0, 0, 0, 0);white-space: nowrap;border-width: 0;}</style>
</div>
`;
                let newNodeHoneypot = document.createElement("div");
                newNodeHoneypot.innerHTML = "|*" + window.btoa(phpHoneypot) + "*|";

                switch(type){
                    case 2:
                        phpHoneypot = `
\n<div class="field seturecap">
\n    <input type="text" id="full_user_name" name="cocoForm[full_user_name]" placeholder="Nombre" class="form-control">
\n    <style>.seturecap {position: absolute;width: 1px;height: 1px;padding: 0;margin: -1px;overflow: hidden;clip: rect(0, 0, 0, 0);white-space: nowrap;border-width: 0;}</style>
\n</div>
\n
`;
                        newNodeHoneypot.innerHTML = phpHoneypot;
                        break;
                }

                el.appendChild(newNodeHoneypot);
            }

            form.innerHTML = PHP + el.innerHTML;

            el.parentNode.replaceChild(form,el);

            return el;
        }
    },
    "v-model": {
        type:"ATTRIBUTE",
        description:`Permite añadir un formulario preestablecido en la base de datos
type:inline,modal
widget:opcional (widget los campos son full width y no muestra los label)
`,
        example:[
            {
                lexer:"ACAI",
                code:`<form v-model="FORMULARIO_SOLICITAR" class="py-2 px-4 bg-teal-400 font-bold text-white text-xl rounded" type="inline"></form>`
            },
            {
                lexer:"TWIG",
                code:`<form c-model="FORMULARIO_SOLICITAR" class="py-2 px-4 bg-teal-400 font-bold text-white text-xl rounded" type="inline"></form>`
            }
        ],
        shortcode:``,
        replace: (el) => {
            let liClass = el ? el.classList.toString() : ``;
            let vmodel = el.getAttribute("v-model") ? el.getAttribute("v-model") : 'FORMULARIO_SOLICITAR';
            let tipo = el.getAttribute("type") ? el.getAttribute("type") : 'inline';
            let widget = el.getAttribute("widget") ? `,"widget" => "true"` : ``;
            el.outerHTML = '|*' +  window.btoa(`<?=t(array("contenido" => "{${vmodel}}"), "contenido", array("clases" => "${liClass}","tipo" => "${tipo}","ip" => @$_SERVER["REMOTE_ADDR"]${widget}));?>`) + '*|';
            return el;
        }
    },
    "v-repeat" : {
        type:"ATTRIBUTE",
        description:`Repite el elemento el numero de veces que ese desee`,
        example : [
            {
                lexer:"ACAI",
                code:`<div v-repeat="10">Elemento a repetir</div>`
            },
            {
                lexer:"TWIG",
                code:`<div v-repeat="10">Elemento a repetir</div>`
            }
        ],
        subattr : [],
        shortcode : ``,
        replace : (el) => {
            let vrepeat = el.getAttribute("v-repeat");
            if (!vrepeat) return el;
            el.removeAttribute("v-repeat");

            let string = ``;
            let iterations = parseInt(vrepeat);

            if (!iterations) return el;
            for (let i = 0;i<iterations;i++){
                string+=el.outerHTML;
            }
            el.outerHTML = string;
            return el;
            //return string;
        }
    },
    "v-hidden" : {
        type:"ATTRIBUTE",
        description:`Oculta el elemento en el resultado`,
        example : [
            {
                lexer:"ACAI",
                code:`<div v-hidden="true">Elemento a ocultar</div>`
            },
            {
                lexer:"TWIG",
                code:`<div c-hidden="true">Elemento a ocultar</div>`
            }
        ],
        subattr : [],
        shortcode : ``,
        replace : (el,attr = "v-hidden") => {
            console.log('qwe:', el, attr)
            let vhidden = el.getAttribute(attr);
            if (!vhidden) return el;
            el.removeAttribute(attr);
            return null;
        }
    },
    "v-for" : {
        type:"ATTRIBUTE",
        description : `Genera una busqueda en la base de datos y repite el elemento con el numero de resultados. Se pueden poner variables basicas en el formato de VUEjs`,
        subattr : [`v-where`,`v-order`,`v-limit`],
        example : [
            {
                lexer:"ACAI",
                code:`<div v-for="contenido in otros_contenidos" v-where="visible_en_el_menu=1">
<a href="{{contenido.enlace}}">{{contenido.name}}</a>
</div>

<!-- También se puede recorrer un objeto multivalores -->

<div v-for="redes in configuracion.redes_sociales">
<a href="{{redes.enlace}}">{{redes.nombre}}</a>
</div>

<!-- También se puede recorrer un campo tipo list multi añadiendo a la variable _bd -->

<div v-for="categoria in productos.otras_categorias_bd">
<a href="{{categoria.enlace}}">{{categoria.nombre}}</a>
</div>

<!-- También se puede coger el índice -->

<div v-for="(categoria,index) in productos.otras_categorias_bd">
<a href="{{categoria.enlace}}">{{index}}</a>
</div>

<!-- También se puede iterar un número determinado de veces -->

<div v-for="index in 10">{{index}}</div>

<!-- También se puede utilizar para acceder a un registro único tipo configuracion -->

<div v-for="configuracion in configuracion" v-limit="1">{{configuracion.titulo_de_pagina}}</div>

<!-- Si se desea, se puede realizar un simple v-for para almacenar datos en una variable concreta. Este elemento no se pintará en el resultado pero se quedará la variable almacenada para usarla más adelante.-->

<div v-for="producto in productos" v-where="categoria={{thisrecord.num}}" v-limit="1"></div>
<div v-if="producto">
    <div v-for="producto in productos" v-where="categoria={{thisrecord.num}}">
        <!-- mostramos el bloque de producto -->
    </div>
</div>
<div v-if="!producto">
    <h1> No se encuentran los productos </h1>
</div>
`
            },
            {
                lexer:"TWIG",
                code:`<div c-for="contenido in otros_contenidos" c-where="'visible_en_el_menu=1'">
<a href="{{contenido.enlace}}">{{contenido.name}}</a>
</div>

<!-- También se puede recorrer un objeto multivalores -->

<div c-for="redes in configuracion.redes_sociales">
<a href="{{redes.enlace}}">{{redes.nombre}}</a>
</div>

<!-- También se puede recorrer un campo tipo list multi añadiendo a la variable _bd -->

<div c-for="categoria in productos.otras_categorias_bd">
<a href="{{categoria.enlace}}">{{categoria.nombre}}</a>
</div>

<!-- También se puede coger el índice -->

<div c-for="categoria in productos.otras_categorias_bd">
<a href="{{categoria.enlace}}">{{index}}</a>
</div>

<!-- También se puede iterar un número determinado de veces -->

<div c-for="variable in 1..10">{{variable}}</div>

<!-- También se puede utilizar para acceder a un registro único tipo configuracion -->

<div c-for="configuracion in configuracion" c-limit="1">{{configuracion.titulo_de_pagina}}</div>

<!-- Si se desea, se puede realizar un simple c-for para almacenar datos en una variable concreta. Este elemento no se pintará en el resultado pero se quedará la variable almacenada para usarla más adelante.-->

<div c-for="producto in productos" c-where="'categoria=' + thisrecord.num" c-limit="1">
    <h1> {{ producto.title }} </h1>
</div>
<div c-else>
    <h1> No se encuentran los productos </h1>
</div>
`
            }
        ],
        shortcode : ``,
        replace : (el) => {
            let vfor = el.getAttribute("v-for");
            if (!vfor) return el;
            el.removeAttribute("v-for");
            let where = el.getAttribute("v-where");
            if (!where) {
                where = "";
            }else {
                whereVars = appParser.parseVariables2(where);

                if (whereVars[1] && whereVars[1].includes("|*")){

                    var match = whereVars[1].match(/(\|\*)([a-z0-9A-Z+.=\/]+)(\*\|)/g);
                    where = whereVars[1];
                    if (match){
                        for (m of match){
                            where = where.replace(m,window.atob(m.replace(`|*`,``).replace(`*|`,``)).replace(`<?php echo `,`".`).replace(`;?>`,`."`));
                        }
                    }
                }
            }

            el.removeAttribute("v-where");

            let order = el.getAttribute("v-order");
            if (!order) order = "";
            el.removeAttribute("v-order");

            let limit = el.getAttribute("v-limit");
            if (!limit) limit = "";
            el.removeAttribute("v-limit");

            let tableName = vfor.split(" in ")[1].trim();
            if (!tableName) return el;
            let variable = vfor.split(" in ")[0].trim();
            if (!variable) return el;
            let index = "cont";
            if (variable.startsWith("(")){
                if (variable.includes(",")){
                    var sepVariables = variable.matchAll(/\(\s?([a-z0-9A-Z]+)\s?,?\s?([a-z0-9A-Z]+)\s?\)/g);
                    sepVariables = Array.from(sepVariables);
                    if (sepVariables[0]){
                        if (sepVariables[0][2]){
                            variable = sepVariables[0][1];
                            index = sepVariables[0][2];
                        }else{
                            variable = sepVariables[0][1];
                        }
                    }
                }else{
                    variable = variable.replace("(","").replace(")","").trim();
                }
            }

            let resultVariables = appParser.parseVariables2(el.outerHTML);
            let preString = resultVariables[0];
            let string = resultVariables[1];

            let result = `\n${preString}`;

            if (el.innerHTML != ""){
                if (tableName.includes(".") === false) {
                    let newLimit = 3000;
                    if (Number.isInteger(limit)){
                        newLimit = parseInt(limit);
                    }else if (limit!=""){
                        newLimit = limit;
                    }

                    if (!Number.isNaN(Number(tableName))){
                        result += '|*' + window.btoa(`<? for ($${variable}=0;$${variable}<${tableName};$${variable}++){ ?>`) + '*|';
                    }else{
                        result += '|*' + window.btoa(`<? if (@loadSchema("${tableName}")) { $${tableName}_result = dame_registros("${tableName}","${where}","${order}",${newLimit}); }else{ $${tableName}_result = @$${tableName} ?: []; }?>`) + '*|';
                        result += '|*' + window.btoa(`<? foreach($${tableName}_result as $${index} => $${variable}){ ?>`) + '*|';
                    }
                    result += `${string}\n`;
                    result += '|*' + window.btoa(`<? } ?>`) + '*|';
                }else{
                    result += '|*' + window.btoa(`<? if (is_numeric($${tableName.replace(`.`,`["`)}"])){ ?>`) + '*|';
                        result += '|*' + window.btoa(`<? for ($${variable}=0;$${variable}<${appParser.lexicalExpressionAnalysis(`{{${tableName}}}`,true).replace("{{","").replace("}}","")};$${variable}++){ ?>`) + '   *|';
                        result += `${string}\n`;
                        result += '|*' + window.btoa(`<? } ?>`) + '*|';
                    result += '|*' + window.btoa(`<? }else{ ?>`) + '*|';
                        result += '|*' + window.btoa(`<? $${variable}_result = !is_array($${tableName.replace(`.`,`["`)}"]) ? @json_decode($${tableName.replace(`.`,`["`)}"],true) : $${tableName.replace(`.`,`["`)}"];?>`) + '*|';
                        result += '|*' + window.btoa(`<? foreach($${variable}_result as $${index} => $${variable}){ ?>`) + '*|';
                        result += `${string}\n`;
                        result += '|*' + window.btoa(`<? } ?>`) + '*|';
                    result += '|*' + window.btoa(`<? } ?>`) + '*|';
                }

            }else{
                result += '|*' + window.btoa(`<? $${variable} = @dame_registros("${tableName}","${where}","${order}",${limit ? parseInt(limit) : 1})[0]; ?>`) + '*|';
            }


            el.outerHTML = result;
            return el;


        },
    },
    "v-selected" : {
        type:"ATTRIBUTE",
        description : `Asigna el atributo selected basado en un condicional`,
        example : [
            {
                lexer:"ACAI",
                code:`<select name='select'>
    <option value="1" v-selected="true">
</select>`
            },
            {
                lexer:"TWIG",
                code:`<select name='select'>
    <option value="1" c-selected="true">
</select>`
            }
        ],
        subattr : [],
        shortcode : ``,
        replace : (el) => { return appParser.components["v-if"].replace(el,"v-selected"); }
    },
    "v-required" : {
        type:"ATTRIBUTE",
        description : `Asigna el atributo required basado en un condicional`,
        example : [
            {
                lexer:"ACAI",
                code:`<input type="checkbox" v-required="true">`
            },
            {
                lexer:"TWIG",
                code:`<input type="checkbox" c-required="true">`
            }
        ],
        subattr : [],
        shortcode : ``,
        replace : (el) => { return appParser.components["v-if"].replace(el,"v-required"); }
    },
    "v-checked" : {
        type:"ATTRIBUTE",
        description : `Asigna el atributo checked basado en un condicional`,
        example : [
            {
                lexer:"ACAI",
                code:`<input type="checkbox" v-checked="true">`
            },
            {
                lexer:"TWIG",
                code:`<input type="checkbox" c-checked="true">`
            }
        ],
        subattr : [],
        shortcode : ``,
        replace : (el) => { return appParser.components["v-if"].replace(el,"v-checked"); }
    },
    "v-if" : {
        type:"ATTRIBUTE",
        description : `Es un condicional que permite que un elemento se muestre o no. Se permiten operadores y variables de forma básica`,
        example : [
            {
                lexer:"ACAI",
                code:`<div v-if="noticia.num%2 == 0 && !$configuracion.pagina_publicada">Elemento condicional</div>`
            },
            {
                lexer:"TWIG",
                code:`<div c-if="noticia.num%2 == 0 && !configuracion.pagina_publicada">Elemento condicional</div>`
            }
        ],
        subattr : [],
        shortcode : ``,
        replace : (el,attributeString = "v-if") => {
            let attr = el.getAttribute(attributeString);
            if (!attr) return el;
            if (attributeString == "v-if") el.removeAttribute(attributeString);

            let lexicalTranslation = appParser.lexicalExpressionAnalysis(`{{${attr}}}`,true);

            let string;
            let preString;
            if (lexicalTranslation === `{{${attr}}}`){
                attr = attr.replace("||","^^");
                let resultVariables = appParser.parseVariables3(attr);
                preString = resultVariables[0];
                string = resultVariables[1];
                string = string.replace("^^","||");
            }else{
                preString = ``;
                string = lexicalTranslation;
            }

            var result = `\n${preString}\n`;
            if (attributeString == "v-if"){
                result += '|*' + window.btoa(`<? if (${string}){?>`) + '*|';
                result += el.outerHTML;
                result += '|*' + window.btoa(`<?}?>`) + '*|';
            }else{
                el.removeAttribute(attributeString);
                el.setAttribute(attributeString.replace("v-",""),attributeString.replace("v-",""));
                const elementoConAtribute = el.outerHTML.replace("v-", "");
                el.removeAttribute(attributeString.replace("v-",""));
                const elementoSinAtribute = el.outerHTML;

                result += '|*' + window.btoa(`<? if (${string}){?>`) + '*|';
                result += elementoConAtribute;
                result += '|*' + window.btoa(`<?}else{?>`) + '*|';
                result += elementoSinAtribute;
                result += '|*' + window.btoa(`<?}?>`) + '*|';
            }
            el.outerHTML = result;
            return el;

        }
    },
    "c-for":{
        type:"ATTRIBUTE",
        recursiveSiblings(el, result = ""){
            if (el.nextElementSibling && el.nextElementSibling.hasAttribute("c-else")){
                let next = el.nextElementSibling;
                if (next.hasAttribute("c-else")) {
                    next.removeAttribute("c-else");
                    result = `${result} {% else %} ${next.outerHTML}`;
                }
                var newEl = this.recursiveSiblings(next,result);
                next.parentNode.removeChild(next);
                return newEl;
            }
            return result;
        },
        replace2 : (el,attributeString = "c-for") => {
            let vfor = el.getAttribute("c-for");
            if (!vfor) return el;
            el.removeAttribute("c-for");

            let where = el.getAttribute("c-where");
            if (!where) where = "''";
            el.removeAttribute("c-where");

            let order = el.getAttribute("c-order");
            if (!order) order = "null";
            el.removeAttribute("c-order");

            let limit = el.getAttribute("c-limit");
            if (!limit) limit = "null";
            el.removeAttribute("c-limit");

            let options = el.getAttribute("c-options");
            if (!options) options = "{}";
            el.removeAttribute("c-options");

            let result = ``;

            if (window.tables){
                let tableName = vfor.split(" in ")[1].trim();
                let tables = window.tables.map(table => table.tableName);
                if (tables.indexOf(tableName) > -1){
                    result += `{% set ${tableName} = '${tableName}' | get(${where},${order},${limit},${options}) %}`;
                }
            }

            result += `\n {% for ${vfor} %} \n`;
            result += el.outerHTML;
            result += appParser.components["c-for"].recursiveSiblings(el);
            result += `\n {% endfor %} \n`;

            el.outerHTML = result;
            return el;
        }
    },
    /* TWIG */
    "set" : {
        type:"TAG",
        description : `SOLO TWIG : Setea una o varias variables`,
        example : [
            {
                lexer:"ACAI",
                code:`<!-- No disponible para este analizador -->`
            },
            {
                lexer:"TWIG",
                code:`<set :variable1="request.num" :variable2="'cadena'">`
            }
        ],
        replace2 : (el) => {
            var result = "";
            var variables = [];
            for(var i = 0, l = el.attributes.length; i < l; ++i){
                var varName  = el.attributes.item(i).nodeName;
                var varValue = el.attributes.item(i).nodeValue;

                if (varName.startsWith(":")){
                    variables.push({name:varName.substring(1),value:varValue});
                }
            }
            if (variables.length){
                for (const variable of variables){
                    result += `\n {% set ${variable.name} = ${variable.value} %} \n`;
                }
            }
            el.outerHTML = result;
            return el;
        }
    },
    "c-required" : {
        type:"ATTRIBUTE",
        replace2 : (el) => {
            if (!el.hasAttribute("c-required")) return el;
            var attr = el.getAttribute("c-required");
            el.removeAttribute("c-required");
            const elementoSinAtributo = el.outerHTML;
            el.setAttribute("required",true);
            const elementoConAtributo = el.outerHTML;
            el.outerHTML = `{% if ${attr} %} ${elementoConAtributo} {% else %} ${elementoSinAtributo} {% endif %}`;
            return el;
        }
    },
    "c-checked" : {
        type:"ATTRIBUTE",
        replace2 : (el) => {
            if (!el.hasAttribute("c-checked")) return el;
            var attr = el.getAttribute("c-checked");
            el.removeAttribute("c-checked");
            const elementoSinAtributo = el.outerHTML;
            el.setAttribute("checked",true);
            const elementoConAtributo = el.outerHTML;
            el.outerHTML = `{% if ${attr} %} ${elementoConAtributo} {% else %} ${elementoSinAtributo} {% endif %}`;
            return el;
        }
    },
    "c-selected" : {
        type:"ATTRIBUTE",
        replace2 : (el) => {
            if (!el.hasAttribute("c-selected")) return el;
            var attr = el.getAttribute("c-selected");
            el.removeAttribute("c-selected");
            const elementoSinAtributo = el.outerHTML;
            el.setAttribute("selected",true);
            const elementoConAtributo = el.outerHTML;
            el.outerHTML = `{% if ${attr} %} ${elementoConAtributo} {% else %} ${elementoSinAtributo} {% endif %}`;
            return el;
        }
    },
    "c-if" : {
        type:"ATTRIBUTE",
        recursiveSiblings(el, result = ""){
            if (el.nextElementSibling && (el.nextElementSibling.hasAttribute("c-else") || el.nextElementSibling.getAttribute("c-else-if"))){
                let next = el.nextElementSibling;
                if (next.hasAttribute("c-else")) {
                    next.removeAttribute("c-else");
                    result = `${result} {% else %} ${next.outerHTML}`;
                }
                if (next.getAttribute("c-else-if")) {
                    let elseAttr = next.getAttribute("c-else-if");
                    next.removeAttribute("c-else-if");
                    result = `${result} {% elseif ${elseAttr} %} ${next.outerHTML}`;
                }
                var newEl = this.recursiveSiblings(next,result);
                next.parentNode.removeChild(next);
                return newEl;
            }
            return result;
        },
        replace2 : (el,attributeString = "c-if") => {

            let attr = el.getAttribute(attributeString);
            if (!attr) return el;
            if (attributeString == "c-if") el.removeAttribute(attributeString);

            result = `\n{% if ${attr} %}\n`;

            result += el.outerHTML;
            result += appParser.components["c-if"].recursiveSiblings(el);

            result += `\n{% endif %}\n`;

            el.outerHTML = result;
            return el;
        }
    },
    "c-hidden" : {
        type:"ATTRIBUTE",
        replace2 : (el) => { return appParser.components["v-hidden"].replace(el,"c-hidden"); }
    },
    "c-repeat" : {
        type:"ATTRIBUTE",
        replace2 : (el) => {
            let vrepeat = el.getAttribute("c-repeat");
            if (!vrepeat) return el;
            el.removeAttribute("c-repeat");
            el.outerHTML = `{% for index in ${vrepeat} %} ${el.outerHTML} {% endfor %}`;
            return el;
        }
    },
    "c-class" : {
        type:"ATTRIBUTE",
        replace2 : (el) => {
            let attr = el.getAttribute("c-class");
            if (!attr) return el;
            el.removeAttribute("c-class");

            attr = attr.substring(1,attr.length-1);
            var result = [];
            var regex = /\\'|'(?:\\'|[^'])*'|(\,)/g;
            replaced = attr.replace(regex, function(m, group1) {
                if (!group1) return m;
                else return "###";
            });
            var splited = replaced.split("###");
            for (const spli of splited){
                var regex2 = /\\'|'(?:\\'|[^'])*'|(\:)/g;
                replaced2 = spli.replace(regex2, function(m, group1) {
                    if (!group1) return m;
                    else return "###";
                });
                var sep = replaced2.split("###");
                result.push({valor:sep[0].trim(),condicion:sep[1].trim()});
            }
            let result2 = "";
            let classString = el.hasAttribute("class") ? el.getAttribute("class") : "";
            if (el.hasAttribute("class")) el.removeAttribute("class");

            result2 += `\n {% set tw_class = ['${classString}'] %} \n`;

            for (res of result){
                result2 += `\n {% if ${res.condicion} %} {% set tw_class = tw_class|merge([${res.valor}]) %} {% endif %} \n `;
            }
            console.log(result2);
            el.setAttribute(`class`,`{{tw_class|join(' ')}}`);
            el.outerHTML = result2 + el.outerHTML;
            return el;
        }
    },
    "c-model": {
        type:"ATTRIBUTE",
        replace2: (el) => {
            let liClass = el ? el.classList.toString() : ``;
            let vmodel = el.getAttribute("c-model") ? el.getAttribute("c-model") : 'FORMULARIO_SOLICITAR';
            let tipo = el.getAttribute("type") ? el.getAttribute("type") : 'inline';
            let widget = el.getAttribute("widget") ? `,'widget':true` : ``;
            el.outerHTML = `{{ { 'contenido' : '{${vmodel}}' } | translateDB('contenido',{'clases':'${liClass}','tipo':'${tipo}'${widget}}) | raw }}`;
            return el;
        }
    },
    "c-form": {
        type:"TAG",
        replace2: (el) => {
            return appParser.components["v-form"].replace(el,2);
        }
    }
}
