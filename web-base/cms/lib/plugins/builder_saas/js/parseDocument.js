class appParser {
    /* PARSEO LEXICO */

    static lexicalExpressionAnalysis (string,onlyParseVariables = false) {
        /*
            12/12/2019 - Expongo el problema:
                - Açai está analizando léxicamente el contenido de un archivo CSS cuando se guarda
                - Al analizarlo léxicamente se queda en un bucle muuuuy grande
                - ¿Por qué analiza CSS o JS? No se sabe
                - El DOMParser es un intento de solución. Problema nuevo:
                    - Si en el CSS o JS se escribe HTML (aunque sea entre comillas) vuelve a reventar

        var parser = new DOMParser();
        var codeDOM = parser.parseFromString(string, 'text/html');

        if (codeDOM.body.childElementCount === 0) return string*/
        const lexer = new Lexer(function (char) {});
        const L = {
            variable: '\\!?\\$?[a-zA-Z][a-zA-Z0-9_]*',
            subindice: '(\\.[a-zA-Z0-9_]+)*',
            espacio: '\\s*',
            entero: '[0-9]+',
            float: '[0-9]+\\.[0-9]*',
        }

        L.string = (index = 1) => '(?<quote' + index + '>["\'])\\\\?.*?\\k<quote' + index + '>'
        L.string = (index = 1) => '(["\'])\\\\?.*?(["\'])'

        L.valor = (index = 1) => '(' + [
            '(' + L.variable + L.subindice + ')',
            '(' + L.float + ')',
            '(' + L.entero + ')',
            '(' + L.string(index) + ')'
        ].join('|') + ')'
        L.valor_espacio = (index = 1) => L.espacio + L.valor(index) + L.espacio

        L.funcion_base = '((\\!\\s|\\!)?[a-zA-Z][a-zA-Z0-9_]+)'
        L.funcion_con_parentesis = L.funcion_base + L.espacio + '\\(' + L.espacio + '\\)'
        L.funcion_x_parametros = (params) => {
            return (index) => {
                let regex = L.funcion_base + L.espacio + '\\(' + L.valor_espacio(index)
                if (params > 1) {
                    for (let i = 1; i < params; i++) {
                        regex += ',' + L.valor_espacio(index + i)
                    }
                }
                regex += '\\)'
                return regex
            }
        }
        L.funcion_1_parametro = L.funcion_x_parametros(1)
        L.funcion_2_parametros = L.funcion_x_parametros(2)
        L.funcion_3_parametros = L.funcion_x_parametros(3)

        L.funcion = '(' + [L.funcion_base, L.funcion_con_parentesis, L.funcion_1_parametro(10), L.funcion_2_parametros(30)]
                .map(x => '(' + x +')')
                .join('|') + ')'

        L.inicio_expresion = '\\{\\{'
        L.fin_expresion = '\\}\\}'

        L.echo = L.inicio_expresion + L.valor_espacio() + L.fin_expresion
        L.expresion = L.inicio_expresion + L.valor_espacio(1) + '\\|' + L.espacio + L.funcion + L.espacio + L.fin_expresion
        L.expresion_funcion_base = L.inicio_expresion + L.valor_espacio(1) + '\\|' + L.espacio + L.funcion_base + L.espacio + L.fin_expresion
        L.expresion_funcion_base_2_pipes = L.inicio_expresion + L.valor_espacio(1) + '\\|' + L.espacio + L.funcion_base + L.espacio + '\\|' + L.espacio + L.funcion_base + L.espacio + L.fin_expresion
        L.expresion_funcion_parentesis = L.inicio_expresion + L.valor_espacio(1) + '\\|' + L.espacio + L.funcion_con_parentesis + L.espacio + L.fin_expresion
        L.expresion_funcion_1_parametro = L.inicio_expresion + L.valor_espacio(1) + '\\|' + L.espacio + L.funcion_1_parametro(2) + L.espacio + L.fin_expresion
        L.expresion_funcion_2_parametros = L.inicio_expresion + L.valor_espacio(1) + '\\|' + L.espacio + L.funcion_2_parametros(2) + L.espacio + L.fin_expresion
        L.expresion_funcion_3_parametros = L.inicio_expresion + L.valor_espacio(1) + '\\|' + L.espacio + L.funcion_3_parametros(2) + L.espacio + L.fin_expresion

        const lex = (...indexes) => {
            let regex = ''
            for (const index of indexes) {
                regex += L[index]
            }
            return new RegExp(regex)
        }

        const isExactMatch = (re, valor) => {
            const matches = valor.match(re)
            return matches && valor == matches[0]
        }

        const getVariable = (valor,forceTranslate) => {
            const re = lex('variable', 'subindice')
            let valorLastVar
            if (isExactMatch(re, valor)) {
                let prefix = '';
                let isNegative = valor.indexOf('!')>-1 ? true : false;
                valor = valor.replace('!','');
                prefix = isNegative ? '!' : '';
                
                valor = '$' + valor.trim().replace('$', '');
                
                valor = valor.split('.')
                const isSingleVariable = valor.length === 1

                valorLastVar = valor[(valor.length-1)];
                valor = valor.map((v, i) => {
                    if (i === 0) {
                        if (v.toLowerCase() === "$request") v = `${prefix}$_REQUEST`;
                        if (v.toLowerCase() === "$server") v = `${prefix}$_SERVER`;
                        return v
                    }
                    if (i >= (valor.length-1)){
                        return ""
                    }
                    if (isFinite(v)) {
                        return '[' + v + ']'
                    }
                    return '[\'' + v + '\']'
                }).join('')

                if (!isSingleVariable) {
                    return forceTranslate ? `${prefix}t(${valor},'${valorLastVar}',[],${forceTranslate})` : `${prefix}t(${valor},'${valorLastVar}')`
                }

                return `${prefix}${valor}`;
            }
            return `${valor}`;
        }

        const filterGroups = groups => {
            groups = groups.filter(x => {
                x = x ? x.trim() : x
                if (typeof x === 'undefined') return false
                if (isExactMatch(lex('subindice'), x)) return false
                if (x === '"' || x === "'") return false
                return true
            })
            groups.splice(1, 1)
            return groups
        }

        const functionIsFilter = (name, ...params) => {
            if (filters[name]) {
                switch(name){
                    case "hook":
                        if (!params[1]) return null;
                        const orderedParams = [params[1],params[0],...params.slice(2)];
                        return `hook(${orderedParams.join(",")})`;
                        break;
                    default:
                        const restOfParams = params.slice(1).join(',')
                        const param = `${name}(${restOfParams})`
                        const variable = params[0]
                        return filters[name].replace(param, variable)
                }
            }
            return null
        }

        lexer.addRule(lex('echo'), function (lexeme, ...groups) {
            groups = filterGroups(groups)
            const valor = getVariable(groups[0])
            return {
                from: lexeme,
                to: `${valor}`
            }
        })

        lexer.addRule(lex('expresion_funcion_base'), function (lexeme, ...groups) {
            groups = filterGroups(groups)
            const valor = getVariable(groups[0])
            const func = groups[1]
            const filteredFunction = functionIsFilter(func, valor)
            return {
                from: lexeme,
                to: filteredFunction || `${func}(${valor})`
            }
        })
        
        lexer.addRule(lex('expresion_funcion_base_2_pipes'), function (lexeme, ...groups) {
            groups = filterGroups(groups)
            const valor = getVariable(groups[0])
            const func = groups[1]
            const func2 = groups[2]
            return {
                from: lexeme,
                to: `${func2}(${func}(${valor}))`
            }
        })

        lexer.addRule(lex('expresion_funcion_parentesis'), function (lexeme, ...groups) {
            groups = filterGroups(groups)
            const valor = getVariable(groups[0])
            const func = groups[1]
            const filteredFunction = functionIsFilter(func, valor)
            return {
                from: lexeme,
                to: filteredFunction || `${func}(${valor})`
            }
        })

        lexer.addRule(lex('expresion_funcion_1_parametro'), function (lexeme, ...groups) {
            groups = filterGroups(groups)
            const valor = getVariable(groups[0])
            const func = groups[1]
            const param = getVariable(groups[2])
            switch(func){
                case "translate":
                    return {
                        from: lexeme,
                        to: getVariable(groups[0],param)
                    }
                break;
            }

            const filteredFunction = functionIsFilter(func, valor, param)
            return {
                from: lexeme,
                to: filteredFunction || `${func}(${valor}, ${param})`
            }
        })

        lexer.addRule(lex('expresion_funcion_2_parametros'), function (lexeme, ...groups) {
            groups = filterGroups(groups)
            const valor = getVariable(groups[0])
            const func = groups[1]
            const param1 = getVariable(groups[2])
            const param2 = getVariable(groups[4])
            const filteredFunction = functionIsFilter(func, valor, param1, param2)
            return {
                from: lexeme,
                to: filteredFunction || `${func}(${valor}, ${param1}, ${param2})`
            }
        })

        lexer.addRule(lex('expresion_funcion_3_parametros'), function (lexeme, ...groups) {
            groups = filterGroups(groups)
            const valor = getVariable(groups[0])
            const func = groups[1]
            const param1 = getVariable(groups[2])
            const param2 = getVariable(groups[4])
            const param3 = getVariable(groups[6])
            const filteredFunction = functionIsFilter(func, valor, param1, param2, param3)
            return {
                from: lexeme,
                to: filteredFunction || `${func}(${valor}, ${param1}, ${param2}, ${param3})`
            }
        })

        lexer.input = string;
        let token
        while (token = lexer.lex()) {
            if (!onlyParseVariables) {
                token.to = '|*' + window.btoa(`<?php echo ${token.to};?>`) + '*|'
            }
            string = string.replace(token.from, token.to)
        }
        return string
    }

    /* PARSEO ANTIGUO */
    static parseVariables3(string){
        let preString = ``;

        // VAMOS A PARSEAR LAS VARIABLES
        string = string.replace(/`/g,"'");
        let variables = string.match(/(\$?)([A-Za-z0-9_]*)(\.+)([a-zA-Z0-9_|\s\(\'\)]*)/g,string);
        for (const index in variables){
            let variable = appParser.parseFilters(variables[index]);
            let variablePHP = ``;
            if (variable.includes(`]`) !== false){
                variablePHP = variable.replace(`{{`,`@$`).replace(`.`,`["`).replace(`}}`,`"]`);
            }else{
                variablePHP = variable.replace(`{{`,`t(@$`).replace(`.`,`,"`).replace(`}}`,`")`);
            }
            variablePHP = variablePHP.replace(`request`,`_REQUEST`);
            variablePHP = variablePHP.replace(`get`,`_GET`);
            variablePHP = variablePHP.replace(`session`,`_SESSION`);
            variablePHP = variablePHP.replace(`server`,`_SERVER`);
            string = string.replace(variables[index],`${variablePHP}`);
        }

        return [preString,string];
    }

    static parseVariables2(string){
        let preString = ``;
        //console.log({string:string});
        // VAMOS A PARSEAR LAS VARIABLES
        //let variables = string.match(/(\{\{)(\$?)([A-Za-z0-9_\[\]]*)(\.+)([a-zA-Z0-9_|\s\(\'\)]*)(\}\})/g,string);
        /*let traducciones = string.matchAll(/\{\{["'](.*)["']\s?[\:\|]+\s?translate\s?\}\}/g,string);
        traducciones = Array.from(traducciones);
        if (traducciones){
            for (const traduccion of traducciones){
                if (traduccion[1]) string=string.replace(traduccion[0],'|*' + window.btoa(`<? echo t_var('${traduccion[1]}'); ?>`) + '*|');
            }
        }*/

        let variables = string.match(/(\{\{)(\$?)([A-Za-z0-9_*)(\.+)([\[\]]+)(\.+)([a-zA-Z0-9_\-|\.\<\>\,\s\(\'\)]*)(\}\})/g,string);

        for (const index in variables){
            let variable = appParser.parseFilters(variables[index]);

            let variablePHP = ``;
            if (variable.includes(`]`) !== false){
                variablePHP = variable.replace(`[`,`"][`);
                variablePHP = variablePHP.replace(`.`,`["`);
                variablePHP = `<? echo ` + variablePHP.replace(`{{`,`@$`).replace(`.`,`["`).replace(`}}`,`"]`) + `; ?>`;

                // ESTA VERSION TENIA EL T PERO NO FUNCIONABA CON VARIOS []
                //variablePHP = `<? echo ` + variable.replace(`{{`,`t(@$`).replace(`].`,`],"`).replace(`}}`,`")`) + `; ?>`;
                //variablePHP = variablePHP.replace(`[`,`"][`);
                //variablePHP = variablePHP.replace(`.`,`["`);
                variablePHP = '|*' + window.btoa(variablePHP) + '*|';
            }else{
                variablePHP = `<? echo ` + variable.replace(`{{`,`t(@$`).replace(`.`,`,"`).replace(`}}`,`")`) + `; ?>`;
                variablePHP = '|*' + window.btoa(variablePHP) + '*|';
            }

            string = string.replace(variables[index],`${variablePHP}`);
        }
        return [preString,string];
    }
    static parseFilters(variable){
        var sepVariable = variable.split(`|`);
        var variableFinal = `{{${sepVariable[0].replace(`{{`,``).replace(`}}`,``).trim()}}}`;
        for(const indexVar in sepVariable){
            if (!indexVar) continue;
            var param = sepVariable[indexVar].trim().replace(`}}`,``);
            var key = param.split(`(`)[0];
            if (appParser.filters[key]){
                variableFinal = appParser.filters[key].replace(param,variableFinal);
            }
        }
        return variableFinal;
    }
    schemaExists(tableName){
        if (!tableName) return false;
        for (const index in ALL_SCHEMAS){
            const schema = ALL_SCHEMAS[index];
            if (schema.tableName == tableName) return schema;
        }
    }
    static parseComponents(code,prefixVar,type = 0){
        switch(type){
            case 2:
                // TWIG
                //alert("Los componentes aun no están activos en TWIG. Por favor, utiliza otro analizador léxico si los vas a utilizar. Activos ( HOOKS, MODULOS y todos los BUILDERDATA excepto LIST )");
                console.log("Executing Parser with TWIG system parser...");
                
                var parser = new DOMParser();
                var codeDOM = parser.parseFromString(code, 'text/html');
                // HOOKS
                codeDOM = this.extractHooks(code,codeDOM,(result,endpoint,variablesModule) => {
                    return `{% set ${result} = '${endpoint}' | hook({${variablesModule.join(",")}}) %}`;
                },(nodeName,nodeValue) => {
                    return `"${nodeName.substring(1)}":${nodeValue}`; 
                }, 2);
                
                // TABLES
                if (window.tables){
                    codeDOM = this.extractTables(code,codeDOM,(module,variablesModule) => {
                        return `{{ 'custom-${module}' | module({${variablesModule.join(",")}})|raw }}`;
                    },(nodeName,nodeValue) => {
                        return `"${nodeName.substring(1)}":${nodeValue}`; 
                    },2);
                }
                // MODULES
                if (window.allModules){
                    codeDOM = this.extractModules(code,codeDOM,(module,variablesModule) => {
                        return `{{ '${module}' | module({${variablesModule.join(",")}})|raw }}`;
                    },(nodeName,nodeValue) => {
                        return `"${nodeName.substring(1)}":${nodeValue}`; 
                    },2);
                }
                // BUILDER DATA
                codeDOM = this.extractBuilderData(code,codeDOM,prefixVar,2);
                
                // COMPONENTS
                codeDOM = this.extractComponents(code,codeDOM,prefixVar,2);
                                
                //var codeParsed = this.parseDocumentFromString(codeDOM.querySelector("body").innerHTML); -> Versión antes del < que se convertía a &gt;
                var codeParsed = this.parseDocumentFromString(codeDOM.querySelector("body").innerHTML);
                codeParsed = codeParsed.replace(/\&gt;/g,">",codeParsed);
                codeParsed = codeParsed.replace(/\&lt;/g,"<",codeParsed);
                console.log(codeParsed);
                return codeParsed;
                
                break;
            case 1:
                // NO PARSE
                console.log("Executing Parser but NO parsing components...");
                return code;
                break;
            default:
                // ACAI LEX
                console.log("Executing Parser with ACAI system parser...");
                return this.parseComponentsAcai(code,prefixVar);
        }
    }
    static parseComponentsAcai(code,prefixVar){
        code = appParser.lexicalExpressionAnalysis(code)
        var parser = new DOMParser();
        var codeDOM = parser.parseFromString(code, 'text/html');
        
        // HOOKS
        codeDOM = this.extractHooks(code,codeDOM,(result,endpoint,variablesModule) => {
            return `|*` + window.btoa(`<? $${result} = hook('${endpoint}',[${variablesModule.join(",")}]); ?>`) + `*|`;
        },(nodeName,nodeValue) => {
            return `"${nodeName.substring(1)}" => @${nodeValue}`; 
        });
        
        // MODULES
        if (window.allModules){
            codeDOM = this.extractModules(code,codeDOM,(module,variablesModule) => {
                return `|*` + window.btoa(`<? echo BuilderModule('${module}',[${variablesModule.join(",")}]); ?>`) + `*|`;
            },(nodeName,nodeValue) => {
                return `"${nodeName.substring(1)}" => @${nodeValue}`; 
            });
        }
        
        codeDOM = this.extractBuilderData(code,codeDOM,prefixVar);
        
        codeDOM = this.extractComponents(code,codeDOM,prefixVar);

        
        /*Array.prototype.slice.call(document.querySelectorAll('.ace_attribute-name')).map(function (el) {
            if (el.innerText.includes(`v-`) !== false) window.setTimeout(() => {el.classList.add('bg-red-900');}1000);
        });*/
        var codeParsed = this.parseDocumentFromString(this.parseVariables2(codeDOM.querySelector("body").innerHTML).join(""));
        return codeParsed;
    }
    static cleanString(cadena){
        return cadena.replace(/\W/g,'').toLowerCase();
    }
    static parseDocumentFromString(data){
        //console.log(data);
        return data;
    }

    static childOf(c,p) {
        while((c=c.parentNode)&&c!==p); return !!c;
    }
    
    static getAssignedVars(resultados){
        if (!resultados){
            console.log("No se encuentra el schema del módulo",resultados);
            return resultados;
        }
        Object.keys(resultados).forEach((rec) => {
            if (resultados[rec]["vars"]){
                var data = {
                    vars:this.getAssignedVars(resultados[rec]["vars"])
                }
                resultados[rec] = data;
            }else{
                if (resultados[rec]["relations"] && resultados[rec]["relations"]["builder_custom"]){
                    resultados[rec] = resultados[rec]["relations"]["builder_custom"];
                }
            }
        })
        return resultados;
    }
    

    static generateBuilderVars(code,parseType = 0,previousSchema = null){
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
            list:"list",
            upload:"upload",
            uploadBackground:"upload",
            uploadMulti:"upload",
            multi:"multi",
            multiv2:"multi",
            textbox:"textbox",
            headfield:"headfield",
            wysiwyg:"wysiwyg"
        };
        
        var preAssignedVars = null;
        if (previousSchema) preAssignedVars = this.getAssignedVars(previousSchema);
        
        console.log(preAssignedVars);
        
        for (const element of all) {

            const type = element.getAttribute("data-field-type");
            if (!type) continue;
            const label = element.getAttribute("data-field-label");
            if (!label) continue;
            const field = label.replace(/\W/g,'').replace(/_/g,'').toLowerCase();
            
            const listInfos = [];
            for(var i=1;i<=5;i++){
                var aux = element.getAttribute('data-field-info'+i);
                if (aux) listInfos.push(aux);
            }

            const listOptions = element.getAttribute('data-list-options');
            const listTable = element.getAttribute('data-list-table');
            const listValue = element.getAttribute('data-list-value');
            const listLabel = element.getAttribute('data-list-label');
            const listQuery = element.getAttribute('data-list-query');
            const listIsMulti = element.hasAttribute('data-list-multi');

            const listOptionsObject = {
                builder_custom: {}
            };

            if (listOptions) {
                listOptionsObject.builder_custom.options = listOptions;
            }

            if (listQuery) {
                listOptionsObject.builder_custom.query = listQuery;
            }

            if (listTable && listValue && listLabel) {
                listOptionsObject.builder_custom.tableName = listTable;
                listOptionsObject.builder_custom.fieldValue = listValue;
                listOptionsObject.builder_custom.fieldLabel = listLabel;
            }

            if (!resultados[field]){
                if (multi.el){
                    if (this.childOf(element,multi.el)){
                        resultados[multi.field]["vars"][field] = {
                            field:field,
                            label:label,
                            type:typesRelations[type]
                        };
                        
                        if (preAssignedVars[multi.field] && preAssignedVars[multi.field]["vars"] && preAssignedVars[multi.field]["vars"][field]) resultados[multi.field]["vars"][field].prevFieldName = preAssignedVars[multi.field]["vars"][field];
                        
                        switch(type){
                            case "list":
                                resultados[multi.field]["vars"][field]["options"] = listOptionsObject;
                                if (listIsMulti) resultados[multi.field]['vars'][field]['multi'] = true;
                                break;
                            case "headfield":
                                resultados[multi.field]["vars"][field + "_tag"] = {
                                    field:field+"_tag",
                                    label:label+" Encabezado",
                                    type:'textfield'
                                };
                                if (preAssignedVars[multi.field] && preAssignedVars[multi.field]["vars"] && preAssignedVars[multi.field]["vars"][field + "_tag"]) resultados[multi.field]["vars"][field + "_tag"].prevFieldName = preAssignedVars[multi.field]["vars"][field + "_tag"];
                                break;
                            case "link":
                                resultados[multi.field]["vars"][field + "_anchor"] = {
                                    field:field+"_anchor",
                                    label:label+" Texto",
                                    type:'textfield'
                                };
                                if (preAssignedVars[multi.field] && preAssignedVars[multi.field]["vars"] && preAssignedVars[multi.field]["vars"][field + "_anchor"]) resultados[multi.field]["vars"][field + "_anchor"].prevFieldName = preAssignedVars[multi.field]["vars"][field + "_anchor"];
                                break;
                            case "upload":
                            case "uploadBackground":
                            case "uploadMulti":
                                resultados[multi.field]["vars"][field]["infos"] = listInfos;
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
                        
                        if (preAssignedVars[field]) resultados[field].prevFieldName = preAssignedVars[field];
                        
                        switch(type){
                            case "list":
                                resultados[field]["options"] = listOptionsObject;
                                if (listIsMulti) resultados[field]['multi'] = true;
                                break;
                            case "headfield":
                                resultados[field+"_tag"] = {
                                    field:field+"_tag",
                                    label:label+" Encabezado",
                                    type:'textfield'
                                };
                                if (preAssignedVars[field + "_tag"]) resultados[field + "_tag"].prevFieldName = preAssignedVars[field + "_tag"];
                                break;
                            case "link":
                                resultados[field+"_anchor"] = {
                                    field:field+"_anchor",
                                    label:label+" Texto",
                                    type:'textfield'
                                };
                                if (preAssignedVars[field + "_anchor"]) resultados[field + "_anchor"].prevFieldName = preAssignedVars[field + "_anchor"];
                                break;
                            case "upload":
                            case "uploadBackground":
                            case "uploadMulti":
                                resultados[field]["infos"] = listInfos;
                                break;
                        }

                    }

                }else{
                    resultados[field] = {
                        field:field,
                        label:label,
                        type:typesRelations[type]
                    };
                    
                    if (preAssignedVars[field]) resultados[field].prevFieldName = preAssignedVars[field];
                    
                    switch(type){
                        case "multi":
                        case "multiv2":
                            multi = {field:field,el:element,label:label};
                            resultados[field]["vars"] = {};
                            break;
                        case "list":
                            resultados[field]["options"] = listOptionsObject;
                            if (listIsMulti) resultados[field]['multi'] = true;
                            break;
                        case "headfield":
                            resultados[field+"_tag"] = {
                                field:field+"_tag",
                                label:label+" Encabezado",
                                type:'textfield'
                            };
                            if (preAssignedVars[field + "_tag"]) resultados[field + "_tag"].prevFieldName = preAssignedVars[field + "_tag"];
                            break;
                        case "link":
                            resultados[field+"_anchor"] = {
                                field:field+"_anchor",
                                label:label+" Texto",
                                type:'textfield'
                            };
                            if (preAssignedVars[field + "_anchor"]) resultados[field + "_anchor"].prevFieldName = preAssignedVars[field + "_anchor"];
                            break;
                        case "upload":
                        case "uploadBackground":
                        case "uploadMulti":
                            resultados[field]["infos"] = listInfos;
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
        var all = codeDOM.querySelectorAll("[data-field-translate]");
        for (const element of all) {
            let cadena = element.innerHTML;
            element.innerHTML = `<?=t_var("${cadena}");?>`;
        }
        var codeParsed = codeDOM.querySelector("body").innerHTML;
        codeParsed = this.parseComponents(codeParsed,null,parseType);
        
        //Para campos tipo TAG propusimos la solución de mandar los tipo _tag al final pero al final se desestimó por nemias
        //resultados = this.ordenaResultados(resultados);
        
        return {codeParsed:codeParsed,codeVars:resultados};
    }
    static ordenaResultados(resultados){
        
        Object.keys(resultados).forEach((rec) => {
            if (resultados[rec]["vars"]){
                resultados[rec]["vars"] = this.ordenaResultados(resultados[rec]["vars"]);
            }else{
                if (rec.indexOf("_tag") > -1){
                    var aux = resultados[rec];
                    delete resultados[rec];
                    resultados[rec] = aux;
                }
            }
        })
        return resultados;
    }
    static extractHooks(code,codeDOM,replacementNode,replacementVar, type = null){
        // HOOKS
        forLoop: for (var i=0;i<10;i++){
            var hookNodes = codeDOM.getElementsByTagName("hook");
            if (hookNodes){
                for (let hookNode of hookNodes){
                    let result = hookNode.getAttribute("result");
                    hookNode.removeAttribute("result");
                    if (!result) continue;
                    let endpoint = hookNode.getAttribute("endpoint");
                    hookNode.removeAttribute("endpoint");
                    if (!endpoint) continue;

                    var variablesModule = [];
                    var replacement = document.createElement('div');
                    for(var i = 0, l = hookNode.attributes.length; i < l; ++i){
                        var nodeName  = hookNode.attributes.item(i).nodeName;
                        var nodeValue = type ? hookNode.attributes.item(i).nodeValue : appParser.lexicalExpressionAnalysis(`{{${hookNode.attributes.item(i).nodeValue}}}`,true);

                        if (nodeName.startsWith(":")){
                            console.warn("** Recordatorio : Las variables de los hooks no pueden ser camelcase **");
                            variablesModule.push(replacementVar(nodeName,nodeValue));
                        } else if (nodeName == "remote"){
                            variablesModule.push(replacementVar(":remote",'"' + window.remoteDomain + '"'));
                        } else{
                            replacement.setAttribute(nodeName, nodeValue);
                        }
                    }
                    replacement.innerHTML = replacementNode(result,endpoint,variablesModule);
                    hookNode.parentNode.replaceChild(replacement, hookNode);
                }
            }else{
                break forLoop;
            }
        }
        return codeDOM;
    }
    static extractComponents(code,codeDOM,prefixVar,type = ""){
        for (const component in this.components){
            // HACEMOS UN BUCLE PARA CONFIRMAR QUE SE PARSEA TODO
            forLoop: for (let i=0;i<10;i++){
                switch(this.components[component].type){
                    case "TAG":
                        var MatchElements = codeDOM.querySelectorAll(component)
                        break;
                    case "ATTRIBUTE":
                        var MatchElements = codeDOM.querySelectorAll("["+component+"]")
                        break;
                }
                if (MatchElements){
                    for (let match of MatchElements){
                        var auxType = type;
                        if ( Object.keys(this.components[component]).indexOf(`replace${auxType}`) <= -1 && Object.keys(this.components[component]).indexOf(`replace`) > -1 ) {
                            //console.log(`No se encuentra un método para ${component} con el tipo ${auxType} en este analizador por lo que utilizaremos el estandar`);
                            //auxType = "";
                            console.log(`No se encuentra un método para ${component} con el tipo ${auxType} en este analizador pero aunque existe en el estandar no lo utilizaremos`);
                            continue;
                        }else if (Object.keys(this.components[component]).indexOf(`replace${auxType}`) <= -1 && Object.keys(this.components[component]).indexOf(`replace`) <= -1){
                            console.log(`No se encuentra un método para ${component} en este analizador ni el estandar`);
                            continue;
                        }
                        let matchAux = this.components[component][`replace${auxType}`](match);
                        if (!matchAux) match.parentNode.removeChild(match); else match = matchAux;
                    }
                }else{
                    break forLoop;
                }

            }

        }
        return codeDOM;
    }
    static extractBuilderData(code,codeDOM,prefixVar,type = ""){
        
        for (const data in this.builderData){
            // HACEMOS UN BUCLE PARA CONFIRMAR QUE SE PARSEA TODO
            forLoop: for (let i=0;i<10;i++){
                switch(this.builderData[data].type){
                    case "TAG":
                        var MatchElements = codeDOM.querySelectorAll(data)
                        if (MatchElements){
                            for (let match of MatchElements){
                                if ( Object.keys(this.builderData[data]).indexOf(`replace${type}`) <= -1 ) { 
                                    console.log(`No se encuentra un método para ${data} en este analizador`); 
                                    continue; 
                                }
                                //let matchAux = this.builderData[data].replace(match,prefixVar);
                                let matchAux = this.builderData[data][`replace${type}`](match,prefixVar);
                                if (!matchAux) match.parentNode.removeChild(match); else match = matchAux;
                            }
                        }else{
                            break forLoop;
                        }
                        break;
                    case "ATTRIBUTE":
                        // LUEGO LOS DEMAS
                        var MatchElements = codeDOM.querySelectorAll("["+data+"]")
                        if (MatchElements){
                            for (let match of MatchElements){
                                if ( Object.keys(this.builderData[data]).indexOf(`replace${type}`) <= -1 ) { 
                                    console.log(`No se encuentra un método para ${data} en este analizador`); 
                                    continue; 
                                }
                                //let matchAux = this.builderData[data].replace(match,prefixVar);
                                let matchAux = this.builderData[data][`replace${type}`](match,prefixVar);
                                if (!matchAux) match.parentNode.removeChild(match); else match = matchAux;
                            }
                        }else{
                            break forLoop;
                        }
                }
            }
        }
        return codeDOM;
    }
    static extractModules(code,codeDOM,replacementNode,replacementVar,type = null){
        if (window.allModules){
            // ESTE FOR SIRVE PARA QUE PRIMERO COMPRUEBE LOS NOMBRES SEPARANDO EL _ Y DESPUES CON EL NOMBRE COMPLETO
            for (let tries = 0;tries<=1;tries++){
                for (const module in window.allModules){
                    var moduleName = !tries ? module.split("_")[0] : module; // AQUI ESTA LA MAGIA DEL FOR INICIAL CON _
                    forLoop: for (var i=0;i<10;i++){
                        var nodeModules = codeDOM.getElementsByTagName(moduleName);
                        if (nodeModules){
                            for (const nodeModule of nodeModules){
                                try{
                                    var variablesModule = [];
                                    var replacement = document.createElement('div');
                                    replacement.classList.add("module-replacement");
                                    for(var i = 0, l = nodeModule.attributes.length; i < l; ++i){
                                        var nodeName  = nodeModule.attributes.item(i).nodeName;
                                        var nodeValue = type ? nodeModule.attributes.item(i).nodeValue : appParser.lexicalExpressionAnalysis(`{{${nodeModule.attributes.item(i).nodeValue}}}`,true);
//                                        var nodeValue = nodeModule.attributes.item(i).nodeValue;
                                        if (nodeName.startsWith(":")){
                                            variablesModule.push(replacementVar(nodeName,nodeValue));
                                        }else if (nodeName == "remote"){
                                            variablesModule.push(replacementVar(":remote",'"' + window.remoteDomain + '"'));
                                        }else{
                                            replacement.setAttribute(nodeName, nodeValue);
                                        }
                                    }
                                    
                                    replacement.innerHTML = nodeModule.innerHTML;
                                    replacement.innerHTML = replacementNode(module,variablesModule);
                                    nodeModule.parentNode.replaceChild(replacement, nodeModule);
                                }catch(error){
                                    console.log("No he podido parsear el componente");
                                    console.log(error);
                                    break forLoop;
                                }
                            }
                        }else{
                            break forLoop;
                        }
                    }
                }
            }
            // Hacemos esto para que no haya un div dando por saco y que el módulo esté al mismo nivel de donde se pone
            if (nodeModules){
                var moduleReplacements = codeDOM.querySelectorAll(".module-replacement");
                if (moduleReplacements){
                    for (const moduleReplacement of moduleReplacements){
                        moduleReplacement.outerHTML = moduleReplacement.innerHTML;
                    }
                }
            }
        }
        return codeDOM;
    }
    
    static extractTables(code,codeDOM,replacementNode,replacementVar,type = null){
        
        if (window.tables){
            var tables = {};
            var tablesAux = window.tables.filter(rec => rec.enlace);
            for (const table of tablesAux){ tables[table.tableName] = {}; }
            
            for (const module in tables){
                var moduleName = module; // AQUI ESTA LA MAGIA DEL FOR INICIAL CON _
                forLoop: for (var i=0;i<10;i++){
                    var nodeModules = codeDOM.getElementsByTagName(moduleName);
                    if (nodeModules){
                        for (const nodeModule of nodeModules){
                            try{
                                var variablesModule = [];
                                var replacement = document.createElement('div');
                                replacement.classList.add("module-replacement");
                                for(var i = 0, l = nodeModule.attributes.length; i < l; ++i){
                                    var nodeName  = nodeModule.attributes.item(i).nodeName;
                                    var nodeValue = type ? nodeModule.attributes.item(i).nodeValue : appParser.lexicalExpressionAnalysis(`{{${nodeModule.attributes.item(i).nodeValue}}}`,true);
//                                        var nodeValue = nodeModule.attributes.item(i).nodeValue;

                                    if (nodeName.startsWith(":")){
                                        variablesModule.push(replacementVar(nodeName,nodeValue));
                                    }else{
                                        replacement.setAttribute(nodeName, nodeValue);
                                    }
                                }

                                replacement.innerHTML = nodeModule.innerHTML;
                                replacement.innerHTML = replacementNode(module,variablesModule);
                                nodeModule.parentNode.replaceChild(replacement, nodeModule);
                            }catch(error){
                                console.log("No he podido parsear el componente");
                                break forLoop;
                            }
                        }
                    }else{
                        break forLoop;
                    }
                }
            }
            
            // Hacemos esto para que no haya un div dando por saco y que el módulo esté al mismo nivel de donde se pone
            if (nodeModules){
                var moduleReplacements = codeDOM.querySelectorAll(".module-replacement");
                if (moduleReplacements){
                    for (const moduleReplacement of moduleReplacements){
                        moduleReplacement.outerHTML = moduleReplacement.innerHTML;
                    }
                }
            }
        }
        return codeDOM;
    }
};
appParser.builderData = builderData;
appParser.components = vuecomponents;
appParser.filters = filters;
window.appParser = appParser;

//console.log(window.app);

window.onkeydown = function(e){
    var evtobj = window.event? event : e;

    if (evtobj.shiftKey && evtobj.keyCode == 186 && (evtobj.ctrlKey || evtobj.metaKey)) {
        window.bus.$emit("write-text",{text:"Anael es marica"});
    }
}