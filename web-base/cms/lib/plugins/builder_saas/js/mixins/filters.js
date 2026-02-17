var filters = {
    "hook" : {
        description : `Permite ejecutar un hook como pipe.`,
        example: `{{'texto general' | hook('metodo sin /hooks/'[,param1,param2])}}`,
        replace: (param,variable) => {
            return `${variable}`;
        }
    },
    "translate" : {
        description : `Permiter añadir una cadena en textos generales.`,
        example: `{{'texto general' | translate}}`,
        replace: (param,variable) => {
            return `t_var(${variable})`;
        }
    },
    "not" : {
        description : `Permite añadir un texto si la variable no existe`,
        example: `{{title | not('Título de página')}}`,
        replace: (param,variable) => {
            if (param.includes("(") !== false){
                return `@${variable} ?: t_var(${param.replace('not(','').replace(')','')})`;
            }else{
                return variable;
            }
        }
    },
    "explode" : {
        description : `Permite separar un texto por un caracter y obtener el indice elegido. explode(string,int) `,
        example: `{{precio | explode(',',0)}}`,
        replace: (param,variable) => {
            
            if (param.includes("(") !== false && param.includes(",") !== false){
                var expt = /["'](.*)["'],([0-9])/g;
                var match = expt.exec(param);
                if (!match[2]) return variable;
                console.log(match);
                return `CustomCode::explode('${match[1].replace(".","_punto_")}',${variable},${match[2]})`;
                
            }else{
                return variable;
            }
        }
    },
    "discount" : {
        description : `Permite aplicar un descuento a una variable. Dentro se le debe aplicar una propiedad del mismo objeto que es la que tiene el descuento`,
        example: `{{producto.precio | discount(descuento)}}`,
        replace: (param,variable) => {
            return variable;
            if (param.includes("(") !== false){
                var propiedad = param.substr(param.indexOf("(")).replace("(","").replace(")","");
                var variable2 = variable.split(".")[0] + "." + propiedad;
                variable2 = variable2.replace("{{","");
                variable2 = `(${appParser.parseVariables3(variable2)[1]} / 100)`;
                variable = appParser.parseVariables3(variable)[1].replace("{{","").replace("}}","");
                
                return `floatval(${variable} - (${variable} * ${variable2}))`;
            }else{
                return variable;
            }
        }
    },
    "date" : {
        description : `Permite parsear una cadena en el formato de fecha`,
        example: `{{fecha | date('Y-m-d')}}`,
        replace: (param,variable) => {
            
            if (param.includes("(") !== false){
                return param.replace(`)`,`,strtotime(${variable}))`);
            }else{
                return `date('d-m-Y',strtotime(${variable}))`;
            }
        }
    },
    "getImage" : {
        description : `Permite obtener la url de la primera imagen de un array`,
        example: `{{logo | getImage}}`,
        replace: (param,variable) => {
            
            return `${variable}[0]["urlPath"]`;
        }
    },
    "uppercase" : {
        description : `Permite parsear una cadena para convertírla en mayúsculas`,
        example: `{{title | uppercase}}`,
        replace: (param,variable) => {
            
            return `mb_strtoupper(${variable})`;
        }
    },
    "halfSpan" : {
        description : `Permite dividir la cadena en 2`,
        example: `{{title | halfSpan('TEXTO EN MEDIO','CLASES SPAN')}}`,
        replace: (param,variable) => {
            if (param.includes("(") !== false){
                return `CustomCode::` + param.replace(`)`,`,${variable})`);
            }else{
                return `CustomCode::halfSpan('d-m-Y',${variable})`;
            }
            
        }
    },
    "imagec" : {
        description : `Permite parsear una cadena de una imagen, se le puede pasar el ancho máximo para que se redimensione`,
        example: `{{noticia.foto_principal[0].urlPath | imagec(ancho_max)}}`,
        replace: (param,variable) => {
            if (param.includes("(") !== false){
                return `CustomCode::`+ param.replace(`)`,`,${variable})`);
            }else{
                return `CustomCode::imagec(${variable})`;
            }
            
        }
    }
}