var builderData = {
    "data-field-type" : {
        type:"ATTRIBUTE",
        description:`Determina que el elemento es editable desde el Builder para los clientes. Se puede añadir un elemento multi que da la posibilidad de añadir distintos bloques a los clientes. Dentro de cada multi se podrán poner campos de edición
<br><br>Nota : Los componentes de estos ejemplos pueden variar dependiendo del analizador léxico utilizado.`,
        example : `<div data-field-type="textfield" data-field-label="Label" >Elemento editable</div>

<div data-field-type="headfield" data-field-label="Titulo" >Elemento editable</div>

<div data-field-type="link" data-field-label="Enlace">Elemento editable</div>

<div data-field-type="textbox" data-field-label="Texto Largo">Elemento editable</div>

<div data-field-type="wysiwyg" data-field-label="Texto Largo enriquecido">Elemento editable</div>

<img data-field-type="upload" data-field-rand="true" data-field-label="Image" data-field-info1="titulo" data-field-width="ancho maximo en pixeles (opcional. Ej 1400)"\>

<div data-field-type="uploadBackground" data-field-label="Imagen de fondo">Elemento editable</div>

<div data-field-type="list" data-field-label="pruebas" data-list-options="opcion1,opcion2,|option3,4|opcion 4" ></div>
<div data-field-type="list" data-field-label="pruebas" data-list-table="nombredeTabla" data-list-value="campoValor" data-list-label="campoLabel">
    {{record.name}}
</div>

<div data-field-type="list"  data-field-label="Apartados" data-list-query="select num,name from cms_otros_contenidos" data-list-multi>
    <div v-for="contenido in otros_contenidos" v-where="num=$record">{{contenido.name}} </div>
</div> 

Si se desea que sea multi añadir el atributo data-list-multi

<div data-field-type="list" data-list-multi></div>

<div data-field-type="uploadMulti" data-field-label="Imagenes" data-field-info1="titulo" >
    <img src="{{uploadMulti.urlPath | imagec(700)}}"\>
</div>

<ul>
    NOTA : El Multi debe tener un nodo padre ( sin hermanos ).
    <li data-field-type="multiv2" data-field-label="Records">
        <div data-field-type="textfield" data-field-label="Label" >Elemento editable</div>
        <div data-field-type="textbox" data-field-label="Texto Largo">Elemento editable</div>
        <img data-field-type="upload" data-field-info1="titulo" data-field-label="Image" data-field-width="ancho maximo en pixeles (opcional. Ej 1400)"\>
    </li>   
</ul>



`,
        shortcode : ``,
        directLinks : {
            TextField : `<p c-if="label" data-field-type="textfield" data-field-label="Label" >Elemento editable</p>`,
            HeadField : `<p c-if="titulo" data-field-type="headfield" data-field-label="Titulo" >Elemento editable</p>`,
            'List (Options)' : `<div data-field-type="list" data-field-label="pruebas" data-list-options="opcion1,opcion2,|option3,4|opcion 4" ></div>`,
            'List (Table)' : `<div data-field-type="list" data-field-label="pruebas" data-list-table="nombredeTabla" data-list-value="campoValor" data-list-label="campoLabel">
        {{record.name}}
    </div>`,
            Link : `<a c-if="enlace_anchor" data-field-type="link" data-field-label="Enlace">Elemento editable</a>`,
            TextBox : `<div c-if="textolargo" data-field-type="textbox" data-field-label="Texto Largo">Elemento editable</div>`,
            Wysiwyg : `<div c-if="textolargoenriquecido" class="wysiwyg" data-field-type="wysiwyg" data-field-label="Texto Largo enriquecido">Elemento editable</div>`,
            Upload : `<div c-if="imagen.0.urlPath" class="p-1/6 relative"><img class="absolute top-0 left-0 w-full h-full object-cover object-center lazyload" data-field-type="upload" data-field-label="Imagen" data-lazy="true" data-field-info1="titulo" data-field-width="1400" alt=""\></div>`,
            UploadBackground : `<div c-if="imagendefondo.0.urlPath" class="bg-cover bg-center bg-no-repeat" data-field-type="uploadBackground" data-field-info1="titulo" data-field-label="Imagen de fondo">Elemento editable</div>`,
            UploadMulti : `<div c-if="imagenes" data-field-type="uploadMulti" data-field-label="Imagenes" data-field-info1="titulo" >
        <img src="{{uploadMulti.urlPath | imagec(700)}}"\>
    </div>`,
            Multi : `<ul>
            <li data-field-type="multiv2" data-field-label="Records">
                <div data-field-type="textfield" data-field-label="Label">Elemento editable</div>
                <div data-field-type="textbox" data-field-label="Texto Largo">Elemento editable</div>
                <div class="p-1/6 relative"><img class="absolute top-0 left-0 w-full h-full object-cover object-center lazyload" data-field-type="upload" data-field-label="Imagen" data-lazy="true" data-field-info1="titulo" data-field-width="1400" alt=""\></div>
            </li>
        </ul>`,
        },
        shortcuts: {
            'C-Form' : `<c-form tableName="" sendToClient="string campo que se usará como cliente" sendTo="string correos separados por coma" honeypot="true" captcha="true" mailRecord="['correos','SOLICITUD']" class="">
</c-form>`,
            Hook: `<hook endpoint="/hooks/nombre_del_hook/" result="almacen_del_resultado" :variable="variable" :variable_string="'mi string'"></hook>`,
            Dump: `<pre style="display:none">{{dump(thisrecord)}}</pre>`,
            TextoGeneral: `{{'' | translate}}`
        },
        replace : (el,prefixVar) => {
            // ACAI ANALYZER
            let attr = el.getAttribute("data-field-type");
            if (!attr) return el.outerHTML;
            el.removeAttribute("data-field-type");
            
            
            let label = el.getAttribute("data-field-label");
            if (!label) {
                label = "Untitled " + new Date().getTime();
            }else{
               el.removeAttribute("data-field-label"); 
            }
            
            let width = el.getAttribute("data-field-width");
            if (!width){
                width = 1600;
            }else{
                el.removeAttribute("data-field-width");
            }
            
            let infos = [];
            for (let i=1;i<5;i++){
                var info = el.getAttribute("data-field-info"+i);
                if (info){
                    infos.push(info);
                    el.removeAttribute("data-field-info"+i);
                }
            }
            

            const field = prefixVar ? `${prefixVar}["${appParser.cleanString(label)}"]` : `$${appParser.cleanString(label)}`;
            const field_anchor = prefixVar ? `${prefixVar}["${appParser.cleanString(label)}_anchor"]` : `$${appParser.cleanString(label)}_anchor`;
            const field_tag = prefixVar ? `${prefixVar}["${appParser.cleanString(label)}_tag"]` : `$${appParser.cleanString(label)}_tag`;
            
            let rand = el.getAttribute("data-field-rand");
            
            if (!rand){
                rand = ``;    
            }else{
                rand = `<? shuffle(${field});?>`;
            }
            
            switch(attr){
                case "multiv2":
                    let php1 = '|*' + window.btoa(`<? foreach(${field} as $index => $record){ ?>`) + '*|';
                    let php2 = '|*' + window.btoa(`<? } ?>`) + '*|';
                    let string = `${php1}${appParser.parseComponents(el.outerHTML,`$record`)}${php2}`;
                    el.outerHTML = string;
                    break;
                case "link":
                    if (el.tagName!='A'){
                        let php1 = '|*' + window.btoa(`<? echo ${field}; ?>`) + '*|';
                        let php2 = '|*' + window.btoa(`<? echo ${field_anchor}; ?>`) + '*|';
                        el.innerHTML = el.hasChildNodes() && Array.from(el.childNodes).filter(node => node.nodeType !== 3).length ? `<a href='${php1}'>${appParser.parseComponents(el.innerHTML,prefixVar)}</a>` : `<a href='${php1}'>${php2}</a>`;
                    }else{
                        el.setAttribute('href','|*' + window.btoa(`<? echo @${field}; ?>`) + '*|');
                        el.innerHTML = el.hasChildNodes() && Array.from(el.childNodes).filter(node => node.nodeType !== 3).length ? appParser.parseComponents(el.innerHTML,prefixVar) : '|*' + window.btoa(`<? echo @${field_anchor};?>`) + '*|';
                    }
                    break;
                case "uploadMulti":
                    let php1up = '|*' + window.btoa(`${rand}<? foreach(${field} as $index => $uploadMulti){ ?>`) + '*|';
                    let php2up = '|*' + window.btoa(`<? } ?>`) + '*|';
                    let resultVariables = appParser.parseVariables2(el.outerHTML);
                    let preStringVars = resultVariables[0];
                    let stringVars = resultVariables[1];
                    let stringup = `${php1up}${preStringVars}${stringVars}${php2up}`;
                    el.outerHTML = stringup;
                    break;
                case "list":
                    const isTable = el.hasAttribute("data-list-table");
                    
                    const tableSelect = el.getAttribute("data-list-table");
                    const valueSelect = el.getAttribute("data-list-value");
                    const labelSelect = el.getAttribute("data-list-label");
                    const querySelect = el.getAttribute("data-list-query");
                    
                    el.removeAttribute("data-list-table");
                    el.removeAttribute("data-list-options");
                    el.removeAttribute("data-list-value");
                    el.removeAttribute("data-list-label");
                    el.removeAttribute("data-list-multi");
                    el.removeAttribute("data-list-query");

                    let php1li = '|*' + window.btoa(`<? ${field} = array_filter(explode("\t",${field}));if (isset($record)) $auxRecord = $record; foreach(${field} as $index => $record){ ?>`) + '*|';
                    if (isTable) php1li += '|*' + window.btoa(`<? $schema = loadSchema("${tableSelect}"); if (@$schema) {$record = @dame_registros("${tableSelect}","${valueSelect}='$record'","num desc",1)[0];}else{ global $TABLE_PREFIX; $record = @mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."${tableSelect} WHERE ${valueSelect}='$record' LIMIT 1")[0]; } if (!$record) continue;?>`) + '*|';
                    
                    let php2li = '|*' + window.btoa(`<? }
                    if (isset($auxRecord)) $record = $auxRecord;?>`) + '*|';
                    let stringli = `${php1li}${appParser.parseComponents(el.outerHTML,`$record`)}${php2li}`;
                    el.outerHTML = stringli;
                    
                    
                    break;
                case "upload":
                    if (el.hasAttribute("src")){
                        el.setAttribute('src','|*' + window.btoa(`${rand}<? echo CustomCode::imagec(${width},${field}[0]['urlPath']);?>`) + '*|');
                    }else{
//                        let classString = el.getAttribute("class");
//                        let styleString = el.getAttribute("style");
//                        let altString = el.getAttribute("alt");
                        var srcAttr = "src";
                        var output = "";
                        attrs = el.attributes;
                        for(var i = attrs.length - 1; i >= 0; i--) {
                            if (attrs[i].name.toLowerCase() == "src") continue;
                            if (attrs[i].name.toLowerCase() == "data-lazy") { srcAttr="data-src"; continue;}
                            output += `${attrs[i].name}="${attrs[i].value}" `;
                        }
                        console.log({output:output});
                        let php = '|*' + window.btoa(`${rand}<? echo CustomCode::imagec(${width},${field}[0]['urlPath']); ?>`) + '*|';
                        el.outerHTML = `<img ${srcAttr}='${php}' ${output}>`;
                        
                    }
                    break;
                case "uploadBackground":
                    let php3 = '|*' + window.btoa(`${rand}<? echo CustomCode::imagec(${width},${field}[0]['urlPath']); ?>`) + '*|';
                    el.setAttribute('style',`background-image:url('${php3}')`);
                    break;
                case "headfield":
                    let outputh = "";

                    attrs = el.attributes;
                    for(var i = attrs.length - 1; i >= 0; i--) {
                        outputh += `${attrs[i].name}="${attrs[i].value}" `;
                    }
                    
                    let phph1 = '|*' + window.btoa(`<? echo ${field}; ?>`) + '*|';
                    let phph2 = '|*' + window.btoa(`<? echo '<'.${field_tag}.' ${outputh}>'; ?>`) + '*|';
                    let phph3 = '|*' + window.btoa(`<? echo '</'.${field_tag}.'>'; ?>`) + '*|';
                    
                    el.outerHTML = `<span>${phph2} ${phph1} ${phph3}</span>`;
                    
                    break;
                default:
                    //el.classList.add(`wed_${field.replace(/\$/g,"")}:${attr}`);
                    el.innerHTML = '|*' + window.btoa(`<? echo @${field} ? nl2br(${field}) : '${el.innerHTML}';?>`) + '*|';
            }
            return el;

        },
        replace2 : (el,prefixVar) => {
            // TWIG ANALYZER
            let attr = el.getAttribute("data-field-type");
            if (!attr) return el.outerHTML;
            el.removeAttribute("data-field-type");
            
            
            let label = el.getAttribute("data-field-label");
            if (!label) {
                label = "Untitled " + new Date().getTime();
            }else{
               el.removeAttribute("data-field-label"); 
            }
            
            let width = el.getAttribute("data-field-width");
            if (!width){
                width = 1600;
            }else{
                el.removeAttribute("data-field-width");
            }
            
            let infos = [];
            for (let i=1;i<5;i++){
                var info = el.getAttribute("data-field-info"+i);
                if (info){
                    infos.push(info);
                    el.removeAttribute("data-field-info"+i);
                }
            }
            

            const field = prefixVar ? `${prefixVar}.${appParser.cleanString(label)}` : `${appParser.cleanString(label)}`;
            const field_anchor = prefixVar ? `${prefixVar}.${appParser.cleanString(label)}_anchor` : `${appParser.cleanString(label)}_anchor`;
            const field_tag = prefixVar ? `${prefixVar}.${appParser.cleanString(label)}_tag` : `${appParser.cleanString(label)}_tag`;
            
            let rand = el.getAttribute("data-field-rand");
            
            if (!rand){
                rand = ``;    
            }else{
                rand = `{% ${field} = ${field} | shuffle %}\n`;
            }
            
            switch(attr){
                case "multiv2":
                    let php1 = `\n{% for record in ${field} %} {% set index = loop.index0 %}\n`;
                    let php2 = `\n{% endfor %}\n`;
                    
                    let string = `${php1}${appParser.parseComponents(el.outerHTML,`record`,2)}${php2}`;
                    el.outerHTML = string;
                    break;
                case "link":
                    if (el.tagName!='A'){
                        let php1 = `{{ ${field} }}`;
                        let php2 = `{{ ${field_anchor} }}`;
                        el.innerHTML = el.hasChildNodes() && Array.from(el.childNodes).filter(node => node.nodeType !== 3).length ? `<a href='${php1}'>${appParser.parseComponents(el.innerHTML,prefixVar)}</a>` : `<a href='${php1}'>${php2}</a>`;
                    }else{
                        el.setAttribute('href',`{{ ${field} }}`);
                        el.innerHTML = el.hasChildNodes() && Array.from(el.childNodes).filter(node => node.nodeType !== 3).length ? appParser.parseComponents(el.innerHTML,prefixVar) : `{{ ${field_anchor} }}`;
                    }
                    break;
                case "uploadMulti":
                    let php1up = `\n ${rand} \n {% for uploadMulti in ${field} %} \n {% set index = loop.index0 %} \n`;
                    let php2up = `\n {% endfor %} \n `;
                    
                    let stringup = `${php1up}${el.outerHTML}${php2up}`;
                    el.outerHTML = stringup;
                    break;
                case "list":
                    const isTable = el.hasAttribute("data-list-table");
                    
                    const tableSelect = el.getAttribute("data-list-table");
                    const valueSelect = el.getAttribute("data-list-value");
                    const labelSelect = el.getAttribute("data-list-label");
                    const querySelect = el.getAttribute("data-list-query");
                    
                    el.removeAttribute("data-list-table");
                    el.removeAttribute("data-list-options");
                    el.removeAttribute("data-list-value");
                    el.removeAttribute("data-list-label");
                    el.removeAttribute("data-list-multi");
                    el.removeAttribute("data-list-query");
                    
                    
                    let php1li = `\n 
                        {% set list_values = ${field} | trim | split("\t") %} \n
                    `;
                    
                    php1li += `\n 
                        {% if record %} \n 
                            {% set auxRecord = record %} \n 
                        {% endif %} \n
                    `;
                    
                    php1li += `\n 
                        
                        {% for record in list_values %} \n 
                            {% set index = loop.index0 %} \n 
                    `;        
                    if (isTable) {
                        php1li += `\n
                            {% if '${tableSelect}' | loadSchema %} \n 
                                {% set record = '${tableSelect}' | get([{'column':'${valueSelect}','operator':'=','value':record}]) %} \n 
                                {% set record = record.0 %} \n
                            {% else %} \n 
                                {% set record = 'cms_${tableSelect}' | get([{'column':'${valueSelect}','operator':'=','value':record}],'num desc',1,{'ignoreSchema':true}) %} 
                                {% set record = record.0 %} 
                            {% endif %}
                        `;
                    }
                    php2li = `\n
                        {% endfor %}\n
                        {% if auxRecord %} {% set record = auxRecord %} {% endif %}
                    `;                    
                    
                    /*let php1li = '|*' + window.btoa(`<? ${field} = array_filter(exp lode("\t",${field}));if (isset($record)) $auxRecord = $record; foreach(${field} as $index => $record){ ?>`) + '*|';
                    if (isTable) php1li += '|*' + window.btoa(`<? $schema = loadSchema("${tableSelect}"); if (@$schema) {$record = @dame_registros("${tableSelect}","${valueSelect}='$record'","num desc",1)[0];}else{ global $TABLE_PREFIX; $record = @mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."${tableSelect} WHERE ${valueSelect}='$record' LIMIT 1")[0]; } if (!$record) continue;?>`) + '*|';
                    
                    let php2li = '|*' + window.btoa(`<? }
                    if (isset($auxRecord)) $record = $auxRecord;?>`) + '*|';*/
                    let stringli = `${php1li}${appParser.parseComponents(el.outerHTML,`record`,2)}${php2li}`;
                    el.outerHTML = stringli;
                    
                    
                    break;
                case "upload":
                    
                    if (el.hasAttribute("src")){
                        el.setAttribute('src',`${rand}{{ ${field}.0.urlPath | imagec(${width}) }}`);
                    }else{
//                        let classString = el.getAttribute("class");
//                        let styleString = el.getAttribute("style");
//                        let altString = el.getAttribute("alt");
                        var srcAttr = "src";
                        var output = "";
                        attrs = el.attributes;
                        for(var i = attrs.length - 1; i >= 0; i--) {
                            if (attrs[i].name.toLowerCase() == "src") continue;
                            if (attrs[i].name.toLowerCase() == "data-lazy") { srcAttr="data-src"; continue;}
                            output += `${attrs[i].name}="${attrs[i].value}" `;
                        }
                        console.log({output:output});
                        let php = `${rand}{{ ${field}.0.urlPath | imagec(${width}) }}`;
                        el.outerHTML = `<img ${srcAttr}='${php}' ${output}>`;
                    }
                    break;
                case "uploadBackground":
                    let php3 = `${rand}{{ ${field}.0.urlPath | imagec(${width}) }}`;
                    el.setAttribute('style',`background-image:url('${php3}')`);
                    break;
                case "textbox":
                    //el.classList.add(`wed_${field.replace(/\$/g,"")}:${attr}`);
                    var filter = "nl2br";
                    var expre = new RegExp("<(\S*?)[^>]*>.*?</\1>|<.*?/>");
                    
                    el.innerHTML = `
                        {% if ${field} %} \n 
                            {% if ${field} | isHTML %}
                                {{ ${field} | raw }} \n 
                            {% else %}
                                {{ ${field} | nl2br }} \n 
                            {% endif %}
                        {% else %} \n 
                            {{ "${el.innerHTML.replace(/\x22/g, '\\\x22')}" | nl2br }} \n 
                        {% endif %}
                    `;
                    break;
                case "headfield":
                    let outputh = "";

                    attrs = el.attributes;
                    for(var i = attrs.length - 1; i >= 0; i--) {
                        outputh += `${attrs[i].name}="${attrs[i].value}" `;
                    }
                    let phph1 = `{{ ${field} }}`;
                    let phph2 = `{{ '<' ~ (${field_tag} ? ${field_tag} : 'P') ~ '${outputh}>' }}`;
                    let phph3 = `{{ '< /' ~ (${field_tag} ? ${field_tag} : 'P') ~ '>' }}`;
                    // ESTA CODIFICADO EN BASE64 PORQUE ACAI LO CONVIERTE A COMENTARIO POR EL ANALIZADOR LEXICO DE ACAI QUE YA NO DEBE DE ESTAR
                    let php4 = ``;
                    
                    if (prefixVar){
                        // {% set record = record|merge({'nombre_tag':record.nombre_tag ?: 'P'}) %}
                        phph4 = `
                            {% set ${prefixVar} = ${prefixVar}|merge({'${appParser.cleanString(label)}_tag': ${field_tag} ?: 'P'})  %}
                            {{ ('<' ~ ${field_tag} ~ ' ${outputh}>' ~ (${field} ? ${field} : '${el.innerHTML.replace(/\x22/g, '\\\x22')}') ~ ('PC8=' | base64_decode) ~ ${field_tag} ~ '>') | raw }}
                        `;    
                    }else{
                        phph4 = `
                            {% set ${field_tag} = ${field_tag} ?: 'P' %}
                            {{ ('<' ~ ${field_tag} ~ ' ${outputh}>' ~ (${field} ? ${field} : '${el.innerHTML.replace(/\x22/g, '\\\x22')}') ~ ('PC8=' | base64_decode) ~ ${field_tag} ~ '>') | raw }}
                        `; 
                    }
                    
                    
                    el.outerHTML = `${phph4}`;
                    
                    break;
                default:
                    //el.classList.add(`wed_${field.replace(/\$/g,"")}:${attr}`);
                    el.innerHTML = `
                        {% if ${field} %} \n 
                            {{ ${field} | raw }} \n 
                        {% else %} \n 
                            {{ "${el.innerHTML.replace(/\x22/g, '\\\x22')}" | raw }} \n 
                        {% endif %}
                    `;
            }
            return el;

        }
    }
}
