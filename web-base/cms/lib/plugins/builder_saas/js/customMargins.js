var appCustomMargins = null;


function startCustomMarginsVue() {
    appCustomMargins = new Vue({
        el: "#marginEditor",
        delimiters: ['${', '}'],
        data: {
            showed: false,
            nonum:false,
            selectedModule: null,
            thumbnail:'',
            loading:false,
            options: [
                { label: "-100 %", value: "-mt-64", percent: 49 },
                { label: "-70 %",  value: "-mt-48", percent: 40 },
                { label: "-60 %",  value: "-mt-44", percent: 40 },
                { label: "-50 %",  value: "-mt-40", percent: 30 },
                { label: "-40 %",  value: "-mt-36", percent: 30 },
                { label: "-30 %",  value: "-mt-28", percent: 20 },
                { label: "-20 %",  value: "-mt-20", percent: 20 },
                { label: "-10 %",  value: "-mt-10", percent: 10 },
                { label: "-6 %",   value: "-mt-6",  percent: 5  },
                { label: "-2 %",   value: "-mt-2",  percent: 3  },
                { label: "0 %",    value: "",       percent: 1  },
                { label: "2 %",    value: "mt-2",   percent: 3  },
                { label: "6 %",    value: "mt-6",   percent: 5  },
                { label: "10 %",   value: "mt-10",  percent: 10 },
                { label: "20 %",   value: "mt-20",  percent: 20 },
                { label: "30 %",   value: "mt-28",  percent: 20 },
                { label: "40 %",   value: "mt-36",  percent: 30 },
                { label: "50 %",   value: "mt-40",  percent: 30 },
                { label: "60 %",   value: "mt-44",  percent: 40 },
                { label: "70 %",   value: "mt-48",  percent: 40 },
                { label: "100 %",  value: "mt-64",  percent: 49 },
            ],
            selection:{
                desktop:{
                    sup:'',
                    inf:''
                },
                mobile:{
                    sup:'',
                    inf:''
                },
            },
            marginSchema: {
                sup:[],
                inf:[]
            },
        },
        filters:{
            parseJson:function(value){
                return JSON.stringify(value);
            }
        },
        watch: {
            selection: {
                deep:true,
                handler:function(newValue,oldValue){
                    this.marginSchema.sup = [this.selection.mobile.sup,this.selection.desktop.sup];
                    this.marginSchema.inf = [this.selection.mobile.inf,this.selection.desktop.inf];
                }
            },
            marginSchema: {
                deep:true,
                handler:function(newValue,oldValue){
                    this.generatSelectionFromSchema();
                }
            },
            showed: function (newVal, oldVal) {
                if (this.showed) this.extractData();
            }
        },
        computed: {
        },
        mounted() {
            this.init();
        },
        methods: {
            init: function () {
                if (!NUM) this.nonum=true;
            },
            getPercent(type){
                type = type.replace("lg:","");
                var result = this.options.find(r => r.value == type);
                return result ? result.percent : 0;
            },
            generatSelectionFromSchema(){
                if (this.marginSchema.sup && this.marginSchema.sup[0]) this.selection.mobile.sup = this.marginSchema.sup[0];
                if (this.marginSchema.sup && this.marginSchema.sup[1]) this.selection.desktop.sup = this.marginSchema.sup[1];
                if (this.marginSchema.inf && this.marginSchema.inf[0]) this.selection.mobile.inf = this.marginSchema.inf[0];
                if (this.marginSchema.inf && this.marginSchema.inf[1]) this.selection.desktop.inf = this.marginSchema.inf[1];
            },
            toggle: function () {
                toggleCustomMarginsModal(true);
            },
            saveSchema: function () {
                this.$nextTick(() => { 
                    var url = "admin.php?menu="+MENU+"&action=edit&num=" + NUM + "&setMarginsFromModule=" + this.selectedModule.builder.id + "&section_id=" + this.selectedModule.section_id + "&";
                    this.downloadData(url,(data) => {
                        this.redrawIframe();
                    },{margins:this.marginSchema});    
                });
            },
            redrawIframe(){
                if (document.querySelector(".split.right2")){
                    var frame = document.getElementById("frame");
                    frame.src = frame.src;
                }
            },
            extractData: function () {
                this.loading = true;
                this.selection.desktop = { sup:'', inf:''};
                this.selection.mobile = { sup:'', inf:''};
                this.marginSchema = {
                    sup:[],
                    inf:[]
                };
                for (modAux of myConfig) {
                    if (modAux.isActive) this.selectedModule = modAux;
                }
                if (!this.selectedModule) return;
                if (!this.selectedModule.builder.htmlData) return;
                if (!this.selectedModule.section_id) return;

                this.thumbnail = document.querySelector(".list-modules.drag-sort-enable .bloque.active img").getAttribute("src");
                var parser = new DOMParser();
                var codeDOM = parser.parseFromString(this.selectedModule.builder.htmlData, 'text/html');

                if (!this.nonum){
                    // Get data from BBDD
                    console.log(this.selectedModule.section_id);
                    var url = "admin.php?menu="+MENU+"&action=edit&num=" + NUM + "&getMarginsFromModule=" + this.selectedModule.builder.id + "&section_id=" + this.selectedModule.section_id + "&";
                    this.downloadData(url,(data) => {
                        
                        if (data && data.sup) this.marginSchema.sup = data.sup;
                        if (data && data.inf) this.marginSchema.inf = data.inf;
                        
                        this.loading = false;
                    });
                }
            },
            copy() {
              let testingCodeToCopy = document.querySelector('#testing-code-margin')
              testingCodeToCopy.setAttribute('type', 'text')    
              testingCodeToCopy.select()

              try {
                var successful = document.execCommand('copy');
                var msg = successful ? 'successful' : 'unsuccessful';
                Swal.fire({icon: 'success', title:"Ok",text: 'Los márgenes han sido copiados',showConfirmButton: false,timer: 1000});
                
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
                            console.log(data)
                            if (Object.keys(data).indexOf("sup") > -1 && Object.keys(data).indexOf("inf") > -1){

                                this.marginSchema = JSON.parse(text);
                                this.saveSchema();
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
                  title: 'Reiniciar los márgenes',
                  icon: 'info',
                  text: '¿Deseas reiniciar los márgenes?',
                  showCancelButton: true,
                  focusConfirm: false,
                  confirmButtonText:'Si',
                  cancelButtonText:'No'
                }).then((value) => {
                    if (value.value){
                        this.selection = {
                            desktop:{
                                sup:'',
                                inf:''
                            },
                            mobile:{
                                sup:'',
                                inf:''
                            },
                        };

                        this.saveSchema();
                    }
                });
                
                
            }
        }
    })
}

window.addEventListener("load", () => startCustomMarginsVue());
