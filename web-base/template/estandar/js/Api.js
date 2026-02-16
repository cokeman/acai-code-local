import CocoParser from '../../main.js'

export default class Api {
  static user = null
  static website = null
  static token = null
  static server = 'https://ws.cocosolution.com'
  static defaultHeader = {
    'Content-type': 'application/json'
  }

  static send (url, header, body = {}, callback, server = Api.server, type) {
    Api.defaultHeader['X-Acai-Token'] = Api.token

    url = server + url
    var xhr = new XMLHttpRequest()
    
    xhr.addEventListener('readystatechange', function () {
      if (this.readyState === 4) {
        try{
          callback(JSON.parse(this.responseText))  
        }catch(error){
          callback(this.responseText)
        }
        
      }
    })

    xhr.open('POST', url)
    for (const key in Api.defaultHeader) {
      xhr.setRequestHeader(key, Api.defaultHeader[key])
    }

    if (header) {
      for (const key in header) {
        xhr.setRequestHeader(key, header[key])
      }
    }
    xhr.send(JSON.stringify(body))
  }

  static removeFile(path,callback){
    return new Promise((resolve, reject) => {
      if (!Api.token) {
        reject(new Error('No hay token'))
        return false
      }
      
      Api.send('/cms/lib/viewer_functions.php', {}, {
        action_ws: 'removeFileBuilder',
        token : Api.token,
        tokenHash : '123',
        path : path
      }, (response) => {
          if (!response.success) {
            console.log(response)
//            reject(new Error('Error desconocido'))
//            return false
          }
          callback(response)
      }, 'https://' + Api.website.domain)
    })
  }

  static sendFile(path,fileName,content,callback){
    return new Promise((resolve, reject) => {
      if (!Api.token) {
        reject(new Error('No hay token'))
        return false
      }
      
      Api.send('/cms/lib/viewer_functions.php', {}, {
        action_ws: 'saveFileBuilder',
        token : Api.token,
        tokenHash : '123',
        fileName : fileName,
        content : content,
        path : path
      }, (response) => {
          if (!response.success) {
            console.log(response)
            reject(new Error('Error desconocido'))
            return false
          }
          callback(response)
      }, 'https://' + Api.website.domain)
    })
  }

  static save (menu) {
    
    const resultVars = CocoParser.builderVars(menu.content)
    const content = resultVars.codeParsed
    const builderVars = resultVars.codeVars
    
    return new Promise((resolve, reject) => {
      switch(menu.section){
        case "assets":
        case "layout":
          window.bus.$emit("loading",{state:1,message:"Guardando estructura general..."})
          Api.send('/cms/lib/viewer_functions.php', {}, {
            action_ws: 'getLayoutData'
          }, (response) => {
            if (!response.result) {
              reject(new Error('Error desconocido'))
              return false
            }
            var data = response.data
            var key = menu.id.replace("layout:","")
            
            switch(key){
              case "header":
              case "footer":
                data[key] = menu.content
                data["real_" + key] = content
                break
              case "assets":
                data.librariesJSONt = JSON.parse(JSON.stringify(menu.libraries_top))
                data.librariesJSONb = JSON.parse(JSON.stringify(menu.libraries_bottom))
                break
              case "js":
                data.javascript = menu.content
                break
              case "css":
                data.style = menu.content
                break
              default:
                data[key] = menu.content
            }
            delete data.header_php
            delete data.footer_php
            Api.send('/admin.php?menu=apartados&acai-code=1&action=edit&saveLayoutData=1', {}, data, (response) => {
              window.bus.$emit("loading",{state:0})
              resolve(response);
            }, 'https://cms.cocosolution.com')

          }, 'https://' + Api.website.domain)
          break
        case "module":
        case "section":
          let endPointfolder = menu.section == "module" ? menu.id.split(":")[0] : 'custom-' + menu.table
          window.bus.$emit("loading",{state:1,message:"Guardando sección..."})
          switch(menu.type){
            case 'html':
              const dataNewModule = {
                description:menu.allData.description,
                editMode:true,
                html:menu.content,
                htmlParsed:content,
                id:menu.schema.id,
                image:menu.allData.imageData ? `data:image/jpeg;base64,` + menu.allData.imageData : `data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAoHBwkHBgoJCAkLCwoMDxkQDw4ODx4WFxIZJCAmJSMgIyIoLTkwKCo2KyIjMkQyNjs9QEBAJjBGS0U+Sjk/QD3/2wBDAQsLCw8NDx0QEB09KSMpPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT3/wgARCAK2A3QDAREAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAAAAECB//EABYBAQEBAAAAAAAAAAAAAAAAAAABA//aAAwDAQACEAMQAAAA53pAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIQhQCFKUAAAAAAGSFAIaKAAAAAAACEIUAApQAAADIANAAAAAAAAAAAAAAAAAAAAAwUAAAAhsAAAAAyAAAAaAAAAAAAMgAAAAhooAABkAFKAAAAAAAAAAAAAAAAAAAQgAAAAABSgAAAwUAAAA0AAAAAAQgAAAAAANAAEIAClAAAAAAAAAAAAAAAAAABCAAAAAAAFKAADIAAAABoAAAAAAyAAAAAAAUoAIQAA0AAAAAAAAAAAAAAAAAADBQAAAAAACGwACEAAAAIaKAAAAADBQAAAAAAAaABCAAGgAAAAAAAAAAAAAAAAADIAABCgAAAApQAYKAAAUoAAAAAAMgAAEKCmSgAAGgCEAANAAAAAAAAAAAAAAAAAAGQAADQABkAAA0ADIAAKUAAAAAAAGCgAA0AAZAABDYBCAAGgAAAAAAAAAAAAAAAAAQgAANAAAGCgAGgCEAABoAAAAyQGigAyAADQAABkAAGgCEAANAAAAAAAAAAAAAAAAAAhAAClAAAMgAA0AZAABoAAAAyAAUoIQAApQAACEAAKUEIAAaAAAAAAAAAAAAAAAAABkAAGgAAAQgABSghAAQ2AAAAYKAClBkAAFKAAAQgABSghAADQAAAAAAAAAAAAAAAAAIQAA0AAACEAAKUGQAAaAAAAMgAA0DIAANAAAAGQAClBCAAGgAAAAAAAAAAAAAAAAAQgABSgAAAyAAUoIQAA0AAACEAABoEIAAUoAAAMgAFKCEABSgAAAAAAAAAAAAAAAAAGQACGwAACEAAKUAyAADQAABkAAFKAZAAIbAAAIQAApQQgABoAAAAAAAAAAAAAAAAAAwUAAFKAAQgABSgGQAADQABkAAA0ADIAAIaKAAQgABSghAADQAAAAAAAAAAAAAAAAABkAAAEKUoIQAApQCEAAABSEKAAAaABCAAAEKDQBkAApQQgABoAAAAAAAAAAAAAAAAAAGQAAAADQMgAFKADBQAAAAAACGwAAZAAAAANAyAAUoIQAA0AAAAAAAAAAAAAAAAAAAYKAAAAaBkAApQADBQAAAAAAUoAABgoAAABSmQAClBCAAGgAAAAAAAAAAAAAAAAAAAYKAAADRCAAFKAADBQAAAAAUoAAABkAAAA0DIABSghAADQAAAAAAAAAAAAAAAAAAABkhQAAaBgoBDYAAAMgAAAAGgAAAAAQyUAAhsGQADQAMgAGgAAAAAAAAAAAAAAAAAAAACEICmgAZIU0AAAAAZABCgA0AAAAAACEICmgAZIUpQAQyUGgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADIBDYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABCAGgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZBoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAhDQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABT/8QAIhAAAQMEAwEBAQEAAAAAAAAAAQIRIAAQMDFAQVASYLAh/9oACAEBAAE/AP7NTjjPTjjuM70/mvX1IFqCsztTyB4L19SBoGhhJuC1DyXbGC1A4347tT4hQU8zqKfIJzA8NOUnMDI6inxyeAC4mcYyE8AROoo79cUInGB4R1FHfjHAE01fOBOQJr5GdWAJr4psCYK1FHfmAPQiQ0hAzA44D0IkYlaijvxTIcEyTie4VA/4DIZhdWoo78UyTgMhcyGExTcyTgMk3VqKO/FVuQwGSdXMRnTcyGsB1JF1aijvxTIYDIXVxE7uZDAZC6tRHimScBknVzIYDJNzJOAyTdWop8U5jJOrmSS2AyTwTqSdG6tRR35goKkZJgZAyJmIGQoKkdSTo3Ooo78ZWH6NBVzqSYGYLUFU9PgTE4XoKwp0bq1FHfopsZJ4wkcQwJ0bmKO/HOMUdSTEjgJDcITTdWoo79IUdSTIh8wGJmwjUxdWoo78r5mMADYGbEA9ANk+cRiLmKO/M+aY3CXoXamawD0A2JqamMADTWCqBzfNNcJoBoM1moBolNwKAb8SqAL/AKs3A/WG4/WkWH65qH9Fv//EABQRAQAAAAAAAAAAAAAAAAAAAND/2gAIAQIBAT8AFuf/xAAUEQEAAAAAAAAAAAAAAAAAAADQ/9oACAEDAQE/ABbn/9k=`,
                javascript:menu.allData.javascriptData || '',
                label:menu.allData.label,
                style:menu.allData.styleData || '',
                tailWind:true,
                vars:builderVars
              }
              
              Api.saveNewModule(dataNewModule)
              .then((response) => {
                if (response.error) {
                  reject(new Error('Error desconocido'))
                }
                window.bus.$emit("refresh-modules")
                resolve({})
              })
//              ANTES GUARDABAMOS EL ARCHIVO DIRECTAMENTE PERO NO SE ENVIABAN LAS VARIABLES
//              Api.sendFile('/modulos/' + endPointfolder + '/',`index.tpl`,Buffer.from(content).toString('base64'), (response) => {
//                if (!response.success) {
//                  reject(new Error('Error desconocido'))
//                }
//                Api.sendFile('/modulos/' + endPointfolder + '/',`index-base.tpl`,Buffer.from(menu.content).toString('base64'), (response) => {
//                  if (!response.success) {
//                    console.log(response)
//                    reject(new Error('Error desconocido'))
//                  }
//                  window.bus.$emit("loading",{state:0})
//                  resolve(response)
//                })
//              })
              break
            default:
              Api.sendFile('/modulos/' + endPointfolder + '/',menu.name,Buffer.from(content).toString('base64'), (response) => {
                if (!response.success) {
                  console.log(response)
                  reject(new Error('Error desconocido'))
                }
                window.bus.$emit("loading",{state:0})
                resolve(response)
              })
          }
          
          break;  
        default:
          resolve({})    
      }
    })
  }

  static saveNewModule (data) {
    return new Promise((resolve, reject) => {
      let moduleData = {
        description:data.description,
        editMode:data.editMode ? data.editMode : false,
        html: data.html ? data.html : '<div class="p-12 bg-gray-300 text-gray-600 text-center">Mi nuevo módulo</div>',
        htmlParsed: data.htmlParsed ? data.htmlParsed : null,
        image: data.image,
        id: data.id ? data.id : Api.validateId(data.label) + '_' + Math.random().toString(36).substring(7),
        javascript: data.javascript ? data.javascript : '',
        label: data.label,
        style: data.style ? data.style : '',
        tailWind: true,
        vars:data.vars ? data.vars : null
      }
      
      window.bus.$emit("loading",{state:1,loadingMessage:data.id ? 'Editando módulo' : 'Creando módulo...'})
      
      Api.send('/admin.php?menu=apartados&action=edit&generateModuleFromString=1', {}, moduleData, (response) => {
        window.bus.$emit("loading",{state:0})
        resolve(response);
      }, 'https://cms.cocosolution.com')
      
    })
  }

  static deleteModule(menu){
    return new Promise((resolve, reject) => {
      if (confirm("Estás seguro que deseas eliminar el módulo? Si existe en algún apartado no podrás recuperarlo")){
        window.bus.$emit("loading",{state:1,loadingMessage:'Eliminando el módulo...'})
        Api.send('/admin.php?menu=apartados&action=edit&json=1&deleteModule=' + menu.id.split(":")[0], {}, {data:null}, (response) => {
          window.bus.$emit("loading",{state:0})
          resolve(response);
        }, 'https://cms.cocosolution.com')
      }
    })
  }

  static validateId (cadena) {
    if (cadena){
      return cadena.replace(/\W/g,'').replace(/_/g,'').toLowerCase()
    }else{
      this.moduleId = this.moduleId.replace(/\W/g, '')
    }
  }

  static getModules () {
    return new Promise((resolve, reject) => {
      if (!Api.token) {
        reject(new Error('No hay token'))
        return false
      }
      Api.send('/cms/lib/viewer_functions.php', {}, {
        action_ws: 'getModuleSchemas',
        token : Api.token,
        tokenHash : '123'
      }, (response) => {
        if (!response.result) {
          reject(new Error('Error desconocido'))
          return false
        }
        const data = []
        for (const index in response.modules){
          let module = response.modules[index]
          data.push({
            name: module.label,
            id: module.id,
            enlace: module.path,
            schema: module,
            section: 'moduleFolder',
            saved: true,
            isFolder: true
          })
        }
        resolve(data)
        
        
      }, 'https://' + Api.website.domain)
    })
  }

  static getModuleData (menu) {
    return new Promise((resolve, reject) => {
      Api.send('/cms/lib/viewer_functions.php', {}, {
        action_ws: 'getModuleSchemas',
        token : Api.token,
        ids : [menu.id],
        tokenHash : '123',
        full : 1,
        withImage : 1
      }, (response) => {
        if (!response.result) {
          reject(new Error('Error desconocido'))
          return false
        }
        var module = response.modules[menu.id] ? response.modules[menu.id] : {}
        if (module){
          const data = []
          data.push({
            id: menu.id + ':html',
            name: menu.id.split("_")[0] + '.html',
            content: module.htmlData || '',
            table:menu.id,
            type: 'html',
            schema: menu.schema,
            allData: module,
            section:"module",
            saved: true
          })
          data.push({
            id: menu.id + ':css',
            name: 'style.css',
            content: module.styleData || '',
            table:menu.id,
            allData: module,
            type: 'css',
            color:'#86c8ea',
            section:"module",
            saved: true
          })
          data.push({
            id: menu.id + ':js',
            name: 'script.js',
            content: module.javascriptData || '',
            table:menu.id,
            allData: module,
            type: 'js',
            color:'#e2ea86',
            section:"module",
            saved: true
          })
          resolve(data)
        }
        
      }, 'https://' + Api.website.domain)
    })
  }

  static getTables () {
    return new Promise((resolve, reject) => {
      if (!Api.token) {
        reject(new Error('No hay token'))
        return false
      }
      Api.send('/api/schemas/', {
        'Authorization': 'Bearer ' + Api.token
      }, {
        action: 'getSchemaTables',
        type: 'acai'
      }, (response) => {
        if (!response.success) {
          reject(new Error('Error desconocido'))
          return false
        }
        const tables = response.data.filter(schema => !!schema.enlace)
          .map(table => {
            return {
              name: table.menuName,
              id: table.tableName,
              enlace: table.enlace,
              schema: table,
              saved: true,
              isFolder: true
            }
          })
        resolve(tables)
      })
    })
  }

  static getLayout () {
    return new Promise((resolve, reject) => {
      if (!Api.token) {
        reject(new Error('No hay token'))
        return false
      }
      Api.send('/cms/lib/viewer_functions.php', {}, {
        action_ws: 'getLayoutData',
        getImageFolder : true
      }, (response) => {
        if (!response.result) {
          reject(new Error('Error desconocido'))
          return false
        }
        const data = []
        
        
        data.push({name:"header.html",id:"layout:header",saved:true,content:response.data.header,type:`html`,section:"layout"})
        data.push({name:"footer.html",id:"layout:footer",saved:true,content:response.data.footer,type:`html`,section:"layout"})
        data.push({name:"custom-js.js",id:"layout:js",saved:true,content:response.data.javascript,type:`js`,color:'#e2ea86',section:"layout"})
        data.push({name:"custom-style.css",id:"layout:css",saved:true,content:response.data.style,type:`css`,color:'#86c8ea',section:"layout"})
        data.push({name:"mantenimiento.html",id:"layout:mantenimiento",saved:true,icon:`clock`,content:response.data.mantenimiento,type:`html`,section:"layout"})
        data.push({name:"Assets",id:"layout:assets",saved:true,section:"assets",libraries_top:response.data.librariesJSONt,libraries_bottom:response.data.librariesJSONb,image_folder:response.data.imageFolder,icon:`image`,color:'#ea8686'})
        
        resolve(data)
      }, 'https://' + Api.website.domain)
      
    })
  }

  static getTableData (menu) {
    const table = menu.id
    return new Promise((resolve, reject) => {
      if (!Api.token) {
        reject(new Error('No hay token'))
        return false
      }
      Api.send('/cms/lib/viewer_functions.php', {}, {
        action_ws: 'getTableData',
        menu: table
      }, (response) => {
        if (!response.result) {
          reject(new Error('Error desconocido'))
          return false
        }

        const data = []
//        if (response.data.htmlData) {
          data.push({
            id: table + ':html',
            name: 'index.html',
            content: response.data.htmlData || '',
            table,
            type: 'html',
            schema: menu.schema,
            section:"section",
            saved: true
          })
//        }
//        if (response.data.styleData) {
          data.push({
            id: table + ':css',
            name: 'style.css',
            content: response.data.styleData || '',
            table,
            type: 'css',
            color:'#86c8ea',
            section:"section",
            saved: true
          })
//        }
//        if (response.data.javascriptData) {
          data.push({
            id: table + ':js',
            name: 'scripts.js',
            content: response.data.javascriptData || '',
            table,
            type: 'js',
            color:'#e2ea86',
            section:"section",
            saved: true
          })
//        }
        resolve(data)
      }, 'https://' + Api.website.domain)
    })
  }

  static isLoggedIn () {
    return !!Api.user && !!Api.token
  }

  static hasWebsiteSelected () {
    return !!Api.website
  }
}
