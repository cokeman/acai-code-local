var appCustomColors = null;

String.prototype.indexOfRegex = function (regex, fromIndex) {
    var str = fromIndex ? this.substring(fromIndex) : this;
    var match = str.match(regex);
    return match ? str.indexOf(match[0]) + fromIndex : -1;
}

function startCustomColorsVue() {
    appCustomColors = new Vue({
        el: "#colorEditor",
        delimiters: ['${', '}'],
        data: {
            ignoredClassesRegx: [
                "text-(xs|lg|sm|md|xl|2xl|3xl|4xl|5xl|6xl|center|left|right)+",
                "border-(t|b|y|x|l|r)+-\\d+"
            ],
            preffixes: ["bg", "text", "placeholder","border"],
            loading:false,
            nonum:false,
            tailWindColors: ["transparent","white", "black", "gray", "red", "orange", "yellow", "green", "teal", "blue", "indigo", "purple", "pink"],
            colorSchema: {},
            thumbnail:'',
            selectedModule: null,
            showed: false,
            newCustom: {
                key1: "",
                key2: ""
            },
            showedColor: false,
            keyToEdit: null
        },
        filters:{
            parseName:function(value){
                return value.indexOf("/") > -1 ? value.split("/")[1].trim() : value.trim();
            },
            parseJson:function(value){
                return JSON.stringify(value);
            }
        },
        watch: {
            showed: function (newVal, oldVal) {
                if (this.showed) this.extractColors();
                this.resize();
            }
        },
        computed: {
            tailWindColorsComp: function () {
                var reg = `(${this.preffixes.join('|')})-[a-z]+-\\d{3}`;
                const filtered = Object.keys(this.colorSchema)
                    .filter(key => key.match(new RegExp(reg,'gi')))
                    .reduce((obj, key) => {
                        obj[key] = this.colorSchema[key];
                        return obj;
                    }, {});

                return filtered;
            },
            customColorsComp: function () {
                var reg = `(${this.preffixes.join('|')})-[a-z]+-\\d{3}`;
                const filtered = Object.keys(this.colorSchema)
                    .filter(key => !key.match(new RegExp(reg,'gi')))
                    .reduce((obj, key) => {
                        obj[key] = this.colorSchema[key];
                        return obj;
                    }, {});
                return filtered;
            },
            tailWindColorsFull: function () {
                var data = [];
                for (let color of this.tailWindColors) {
                    if (color == "white" || color == "black" || color == "transparent") {
                        data.push(color);
                        continue;
                    }
                    for (let num = 100; num <= 900; num += 100) {
                        data.push(color + "-" + num);
                    }
                }
                return data;
            }
        },
        mounted() {
            this.init();
        },
        methods: {
            init: function () {
                if (!NUM) this.nonum=true;
                window.setTimeout(() => {

                    this.resize();
                }, 400);
            },
            toggle: function () {
                toggleCustomColorsModal(true);
            },
            resize: function () {

                var splitRight = document.querySelector(".split.right");
                this.$refs.colorEditor.style.width = splitRight.offsetWidth + "px";
            },
            getBgColor: function (key) {
                if (key == 1000) return '';
                if (key == 2000) return '';
                var result = key;
                for (let preffix of this.preffixes) {
                    result = result.replace(preffix, "bg");
                }
                return result;
            },
            getColorClass: function (color, defaultColor) {
                return color ? color + " w-6 h-6" : defaultColor + " opacity-25 w-6 h-6";
            },
            getPreffix: function (key) {
                return key.indexOf("-") > -1 ? key.split("-")[0] : "";
            },
            addCustom: function () {
                Vue.set(this.colorSchema, this.newCustom.key1, this.newCustom.key2);
                this.newCustom = {
                    key1: "",
                    key2: ""
                };
                this.saveSchema();
            },
            selectColor: function (key1) {
                if (key1) {
                    this.keyToEdit = key1;
                } else {
                    this.keyToEdit = 1000;
                }

                this.showedColor = true;
            },
            changeCustomValue: function (key) {
                this.colorSchema[this.$refs.customKeyValue[0].value] = this.colorSchema[key];
                this.removeCustom(key, true);
            },
            removeCustom: function (key, hideConfirm = false) {
                if ((!hideConfirm && confirm("Deseas eliminar la sustitución?") || hideConfirm)) {
                    Vue.delete(this.colorSchema, key);
                    this.saveSchema();
                }
            },
            colorSelected: function (value) {
                if (!this.keyToEdit) return;

                if (this.keyToEdit !== 1000 && this.keyToEdit !== 2000 && value) {
                    Vue.set(this.colorSchema, this.keyToEdit, this.getPreffix(this.keyToEdit) + "-" + value);
                }
                if (this.keyToEdit == 1000 && value) {
                    this.newCustom.key2 = this.getPreffix(this.newCustom.key1) + "-" + value;
                }
                if (this.keyToEdit == 2000 && value) {
                    var color = value.split("-")[0];
                    for (const classes in this.colorSchema){
                        var value = classes;
                        for (const indexColor in this.tailWindColors){                            
                            if (classes.indexOf(this.tailWindColors[indexColor]) > -1) value = classes.replace(this.tailWindColors[indexColor],color);
                        }
                        Vue.set(this.colorSchema,classes,value);
                    }
                    this.saveSchema();
                }

                this.$forceUpdate();;
                this.keyToEdit = null;
                this.showedColor = false;

                this.saveSchema();
            },
            saveSchema: function () {
                var url = "admin.php?menu="+MENU+"&action=edit&num=" + NUM + "&setColorsFromModule=" + this.selectedModule.builder.id + "&section_id=" + this.selectedModule.section_id;
                this.downloadData(url,(data) => {
                    this.redrawIframe();
                },{colors:this.colorSchema});
            },
            extractColors: function () {
                this.loading = true;
                this.colorSchema = {};
                for (modAux of myConfig) {
                    if (modAux.isActive) this.selectedModule = modAux;
                }
                if (!this.selectedModule) return;
                if (!this.selectedModule.builder.htmlData) return;
                if (!this.selectedModule.section_id) return;
                this.thumbnail = document.querySelector(".list-modules.drag-sort-enable .bloque.active img").getAttribute("src");
                var parser = new DOMParser();
                var codeDOM = parser.parseFromString(this.selectedModule.builder.htmlData, 'text/html');

                // Extract all Classes
                var allClasses = [];

                var allElements = codeDOM.body.querySelectorAll('*');

                for (var i = 0; i < allElements.length; i++) {
                    var classes = allElements[i].className.toString().split(/\s+/);
                    for (var j = 0; j < classes.length; j++) {
                        var cls = classes[j];
                        if (cls && allClasses.indexOf(cls) === -1)
                            allClasses.push(cls);
                    }
                    
                    if (allElements[i].getAttribute("c-class")){
                        var cclasses = allElements[i].getAttribute("c-class").split(/(\s|,)+/);
                        for (var j = 0; j < cclasses.length; j++) {
                            var cls = cclasses[j];
                            if (cls && allClasses.indexOf(cls) === -1)
                                allClasses.push(cls);
                        }
                    }
                }

                // Filter ignored classes
                for (const ignored of this.ignoredClassesRegx){
                    var reg = new RegExp(ignored,'gi');                    
                    allClasses = allClasses.filter(rec => !rec.match(reg));
                }

                // Get All Tailwind Classes
                var filteredClasses = [];
                var reg = `(${this.preffixes.join('|')})-[a-z]+-\d{3}`;
                var result = allClasses.map(rec => rec.match(new RegExp(reg,'gi')) ? rec.match(new RegExp(reg,'gi')).join() : '');
                result = result.filter(rec => rec);
                filteredClasses = filteredClasses.concat(result);
                
                // Order array;
                filteredClasses = filteredClasses.sort().reverse();
                                
                // Second get all Custom Classes

                var customClasses = [];
                var reg = `(${this.preffixes.join('|')})-[a-z0-9A-Z_-]+`;
                var result = allClasses.map(rec => rec.match(new RegExp(reg,'gi')) ? rec.match(new RegExp(reg,'gi')).join() : '');
                result = result.filter(rec => rec && filteredClasses.indexOf(rec) <= -1);
                result = result ? result.sort().reverse() : result;
                filteredClasses = filteredClasses.concat(result);
                
                // Remove duplcate Clases
                var uniqueClasses = [];
                $.each(filteredClasses, function (i, el) {
                    if ($.inArray(el, uniqueClasses) === -1) uniqueClasses.push(el);
                });
                if (uniqueClasses) filteredClasses = uniqueClasses;       
                
                if (filteredClasses) {

                    
                    
                    // Get data from BBDD
                    var url = "admin.php?menu="+MENU+"&action=edit&num=" + NUM + "&getColorsFromModule=" + this.selectedModule.builder.id + "&section_id=" + this.selectedModule.section_id;
                    this.downloadData(url,(data) => {
                        
                        
                        var newColor = {};
                        for (const classes of filteredClasses) {
                            newColor[classes] = null;
                        }
                        for (const classes in data){
                            newColor[classes] = data[classes];
                        }

                        this.colorSchema = newColor;
                        
                        this.loading = false;
                    });
                }

            },
            redrawIframe(){
                if (document.querySelector(".split.right2")){
                    var frame = document.getElementById("frame");
                    frame.src = frame.src;
                }
            },
            copy() {
              let testingCodeToCopy = document.querySelector('#testing-code')
              testingCodeToCopy.setAttribute('type', 'text')    
              testingCodeToCopy.select()

              try {
                var successful = document.execCommand('copy');
                var msg = successful ? 'successful' : 'unsuccessful';
                Swal.fire({icon: 'success', title:"Ok",text: 'Los colores han sido copiados',showConfirmButton: false,timer: 1000});
                
              } catch (err) {
                Swal.fire({icon: 'warning', title:"Oops",text: 'Ha ocurrido un error en la copia',showConfirmButton: false,timer: 1000});
              }

              /* unselect the range */
              testingCodeToCopy.setAttribute('type', 'hidden')
              window.getSelection().removeAllRanges()
            },
            paste(){
                navigator.clipboard.readText()
                    .then(text => {
                        try {
                            var data = JSON.parse(text);
                            var keys = Object.keys(data);
                            var result = keys.filter(rec => !Boolean(rec.indexOfRegex(/(bg||text||placeholder||border)-[a-z]+-\d{3}/gi)));
                            if (result){
                                var cambiados = 0;
                                for (const color in this.colorSchema){
                                    if (data[color]) {
                                        Vue.set(this.colorSchema,color,data[color]);
                                        cambiados+=1;
                                    }
                                }
                                if (cambiados) {
                                    Swal.fire({icon: 'success', title:"Ok",text: 'Los colores del portapapeles han sido aplicados',showConfirmButton: false,timer: 1000});
                                    this.saveSchema();
                                }
                            }
                        } catch(error) {
                            console.log(error); 
                            Swal.fire({icon: 'warning', title:"Oops",text: 'Ha ocurrido un error en la copia',showConfirmButton: false,timer: 1000});
                        }
                        
                        
                    })
                    .catch(err => {
                        Swal.fire({icon: 'warning', title:"Opps",text: 'Ha ocurrido un error en la copia',showConfirmButton: false,timer: 1000});
                    });
            },
            reset(){
                Swal.fire({
                  title: 'Reiniciar los colores',
                  icon: 'info',
                  text: '¿Deseas reiniciar los colores?',
                  showCancelButton: true,
                  focusConfirm: false,
                  confirmButtonText:'Si',
                  cancelButtonText:'No'
                }).then((value) => {
                    if (value.value){
                        var newColor = {};
                        for (const classes in this.colorSchema) {
                            newColor[classes] = null;
                        }

                        this.colorSchema = newColor;    
                        this.saveSchema();
                    }
                });
                
                
            }
        }
    })
}

window.addEventListener("load", () => startCustomColorsVue());
