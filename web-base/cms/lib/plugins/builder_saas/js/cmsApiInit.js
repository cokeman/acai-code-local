class CmsApiClass {
    constructor() {
        this._traducciones = {};
        this.loadingTraducciones = false;
    }
    hook(hook, data, callback,options = {}) {
        try {
            if (!hook) throw new Error("No se ha definido el endpoint del hook");
            if (typeof data !== 'object' && typeof data !== 'null') throw new Error("Data no es un objeto, es un " + typeof data);
            if(window.FORCE_BASE_DOMAIN && !hook.startsWith("http")) {
                hook = window.FORCE_BASE_DOMAIN + hook;
            }
            return fetch(hook, {
                method: 'post',
                headers: new Headers({'X-Hooks-Token': hooksToken,'Content-Type': 'application/json', 'X-Acai-Accept-Language': language ? language : 'www'}),
                body: JSON.stringify(data ? data : {}),
                signal:options.signal
            }).then(function (res) {
                return res.json()
            }).then(function (resJson) {
                return typeof callback == 'function' ? callback(resJson) : resJson
            })
        } catch (error) {
            console.error(error);
            Promise.resolve(false)
        }
    }
    getSignal(signalName) {
        if(!this.signals[signalName] || (this.signals[signalName].signal.aborted)) {
            const controller = new AbortController();
            this.signals[signalName] = controller;
        }
        return this.signals[signalName];
    }
    t_var(text) {
        let slug = CmsApi.slugify(text);
        if(this._traducciones[slug]) {
          return this._traducciones[slug];
        }

        // Lo creamos con el texto pasado para evitar múltiples llamadas.
        this._traducciones[slug] = text;
        if (this.loadingTraducciones) return this._traducciones[slug];

        this.loadingTraducciones = true;
        // Enviamos a crear la traducción
        var signal = this.getSignal('traducciones');
        let callback = (response) => {
            this.loadingTraducciones = false;
            if(response['success'] == true) {
                this._traducciones = response['data'];
                console.log("Traducciones cargadas");
            } else {
                console.error('Problema al crear textos generales: ', response);
            }
        }
        try {
            
            var datatoSendToHook = { clave: slug, valor: text};
            if (window.REMOTE_URL) datatoSendToHook.remote = window.REMOTE_URL;

            this.hook('/hooks/traducciones/', datatoSendToHook,
            callback,
            {
            //    signal:signal
            });
        } catch {
            console.error('Problema al crear textos generales: ', text);
        }
        
        return this._traducciones[slug];
    }
    translate(text) {
        let slug = CmsApi.slugify(text);

        return new Promise((resolve, reject) => {
            if(this._traducciones[slug]) {
              return resolve(this._traducciones[slug]);
            }

            // Lo creamos con el texto pasado para evitar múltiples llamadas.
            this._traducciones[slug] = text;
            if (this.loadingTraducciones) return this._traducciones[slug];

            this.loadingTraducciones = true;
            // Enviamos a crear la traducción
            var signal = this.getSignal('traducciones');
            try {
                
                var datatoSendToHook = { clave: slug, valor: text};
                if (window.REMOTE_URL) datatoSendToHook.remote = window.REMOTE_URL;

                return this.hook('/hooks/traducciones/', datatoSendToHook,
                null,
                {
                //    signal:signal
                }).then((response) => {
                    return resolve(this._traducciones[slug]);
                });
            } catch {
                console.error('Problema al crear textos generales: ', text);
            }
            return resolve(this._traducciones[slug]);
        });
    }
    slugify(string) {
        // str.normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        // https://stackoverflow.com/questions/990904/remove-accents-diacritics-in-a-string-in-javascript
        const a = 'àáäâãåăæąçćčđďèéěėëêęğǵḧìíïîįłḿǹńňñòóöôœøṕŕřßşśšșťțùúüûǘůűūųẃẍÿýźžż·/_,:;'
        const b = 'aaaaaaaaacccddeeeeeeegghiiiiilmnnnnooooooprrsssssttuuuuuuuuuwxyyzzz______'
        const p = new RegExp(a.split('').join('|'), 'g')
        
        return string.toString().toLowerCase()
            .replace(/\s+/g, '_') // Replace spaces with -
            .replace(p, c => b.charAt(a.indexOf(c))) // Replace special characters
            .replace(/&/g, '_and_') // Replace & with 'and'
            .replace(/[^\w\-]+/g, '') // Remove all non-word characters
            .replace(/\_\_+/g, '_') // Replace multiple - with single -
            .replace(/^_+/, '') // Trim - from start of text
            .replace(/_+$/, '') // Trim - from end of text
            .toUpperCase()
    }
}
var CmsApi = new CmsApiClass();
CmsApi.signals = {};
//if(language !== 'www') window.addEventListener("load", () => { CmsApi.t_var('');});
