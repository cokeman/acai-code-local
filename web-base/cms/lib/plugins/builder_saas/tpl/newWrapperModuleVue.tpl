<div id="newModulesVue" class="newWrapperContainerVue fixed top-0 h-full z-5000 overflow-scroll bg-gray-200 border-l border-gray-400 flex flex-col justify-between" :class="{'showed':active}">
    <div v-if="!onlyMarket" class="p-6 shadow-lg sticky top-0 z-10 flex-shrink-0" :class="{'bg-white':category != 2,'bg-purple-900':category == 2}">
        <div class="container relative mx-auto flex justify-between ">
            <div v-if="category != 1" class="text-gray-700 flex mr-8 overflow-hidden p-0 rounded-lg shadow border border-gray-300 w-full justify-between" :class="{'opacity-25 pointer-events-none select-none':category == 2}">
                <div class="flex relative">
                    <select v-model="folderSelected" class="appearance-none pl-4 pr-10 bg-gray-200 border-r border-gray-400 bg-white py-3">
                        <option value="">Todos</option>
                        <option v-for="folder in folders" :value="folder">{{folder}}</option>
                    </select>
                    <div class="absolute top-0 right-0 px-4 h-full flex items-center pointer-events-none"><i class="fa fa-chevron-down text-sm"></i></div>
                </div>
                <input v-model="searchTerm" type="text" placeholder="Buscar por nombre" class="px-8 w-full bg-white">
            </div>

            <div v-if="category == 1" class="text-gray-700 flex mr-8 overflow-hidden p-0 rounded-lg shadow border border-gray-300 w-full justify-between">
                <div class="flex relative">
                    <select v-model="domainSelected" @change="changeDomain" class="appearance-none pl-4 pr-10 bg-gray-200 border border-gray-300 shadow-md rounded-l-lg bg-white py-3 w-full bg-white">
                        <option value="">Elige web</option>
                        <option v-for="domain in domains" :value="domain.num">{{domain.domain}}</option>
                    </select>
                    <div class="absolute top-0 right-0 px-4 h-full flex items-center pointer-events-none"><i class="fa fa-chevron-down text-sm"></i></div>
                </div>
                <input v-model="searchTerm" type="text" placeholder="Buscar por nombre" class="px-8 w-full bg-white">
                
            </div>
            <div class="flex items-center justify-end flex-shrink-0">
                <button @click="category = 0" :class="{'bg-white text-gray-700':category != 0,'bg-theme text-white':category == 0}" class="rounded-l-lg relative flex flex-col items-start content-center py-2 px-3 space-x-2 border border-gray-300  shadow hover:bg-gray-300">
                    Biblioteca
                </button>
                <? if (@$CURRENT_USER["isSuperAdmin"] || @$CURRENT_USER["acceso_total"]){?>
                <button @click="category = 1" :class="{'bg-white text-gray-600':category != 1,'bg-theme text-white':category == 1}" class="relative flex flex-row items-center content-center py-2 px-3 space-x-2 border border-gray-300  shadow shover:bg-gray-300">
                    <svg class="svg-icon fill-current w-5 h-5 mr-4" viewBox="0 0 20 20">
                        <path d="M17.659,9.597h-1.224c-0.199-3.235-2.797-5.833-6.032-6.033V2.341c0-0.222-0.182-0.403-0.403-0.403S9.597,2.119,9.597,2.341v1.223c-3.235,0.2-5.833,2.798-6.033,6.033H2.341c-0.222,0-0.403,0.182-0.403,0.403s0.182,0.403,0.403,0.403h1.223c0.2,3.235,2.798,5.833,6.033,6.032v1.224c0,0.222,0.182,0.403,0.403,0.403s0.403-0.182,0.403-0.403v-1.224c3.235-0.199,5.833-2.797,6.032-6.032h1.224c0.222,0,0.403-0.182,0.403-0.403S17.881,9.597,17.659,9.597 M14.435,10.403h1.193c-0.198,2.791-2.434,5.026-5.225,5.225v-1.193c0-0.222-0.182-0.403-0.403-0.403s-0.403,0.182-0.403,0.403v1.193c-2.792-0.198-5.027-2.434-5.224-5.225h1.193c0.222,0,0.403-0.182,0.403-0.403S5.787,9.597,5.565,9.597H4.373C4.57,6.805,6.805,4.57,9.597,4.373v1.193c0,0.222,0.182,0.403,0.403,0.403s0.403-0.182,0.403-0.403V4.373c2.791,0.197,5.026,2.433,5.225,5.224h-1.193c-0.222,0-0.403,0.182-0.403,0.403S14.213,10.403,14.435,10.403"></path>
                    </svg>
                    Explorar
                </button>
                <? }?>
                <button @click="category = 2" :class="{'bg-white text-gray-600 border-gray-300':category != 2,'bg-pink-600 text-white border-pink-500':category == 2}" class="rounded-r-lg relative flex flex-row items-center content-center py-2 px-3 space-x-2 border   shadow shover:bg-gray-300">
                    <svg class="svg-icon fill-current w-5 h-5 mr-4" viewBox="0 0 20 20">
                        <path d="M17.671,13.945l0.003,0.002l1.708-7.687l-0.008-0.002c0.008-0.033,0.021-0.065,0.021-0.102c0-0.236-0.191-0.428-0.427-0.428H5.276L4.67,3.472L4.665,3.473c-0.053-0.175-0.21-0.306-0.403-0.306H1.032c-0.236,0-0.427,0.191-0.427,0.427c0,0.236,0.191,0.428,0.427,0.428h2.902l2.667,9.945l0,0c0.037,0.119,0.125,0.217,0.239,0.268c-0.16,0.26-0.257,0.562-0.257,0.891c0,0.943,0.765,1.707,1.708,1.707S10,16.068,10,15.125c0-0.312-0.09-0.602-0.237-0.855h4.744c-0.146,0.254-0.237,0.543-0.237,0.855c0,0.943,0.766,1.707,1.708,1.707c0.944,0,1.709-0.764,1.709-1.707c0-0.328-0.097-0.631-0.257-0.891C17.55,14.182,17.639,14.074,17.671,13.945 M15.934,6.583h2.502l-0.38,1.709h-2.312L15.934,6.583zM5.505,6.583h2.832l0.189,1.709H5.963L5.505,6.583z M6.65,10.854L6.192,9.146h2.429l0.19,1.708H6.65z M6.879,11.707h2.027l0.189,1.709H7.338L6.879,11.707z M8.292,15.979c-0.472,0-0.854-0.383-0.854-0.854c0-0.473,0.382-0.855,0.854-0.855s0.854,0.383,0.854,0.855C9.146,15.596,8.763,15.979,8.292,15.979 M11.708,13.416H9.955l-0.189-1.709h1.943V13.416z M11.708,10.854H9.67L9.48,9.146h2.228V10.854z M11.708,8.292H9.386l-0.19-1.709h2.512V8.292z M14.315,13.416h-1.753v-1.709h1.942L14.315,13.416zM14.6,10.854h-2.037V9.146h2.227L14.6,10.854z M14.884,8.292h-2.321V6.583h2.512L14.884,8.292z M15.978,15.979c-0.471,0-0.854-0.383-0.854-0.854c0-0.473,0.383-0.855,0.854-0.855c0.473,0,0.854,0.383,0.854,0.855C16.832,15.596,16.45,15.979,15.978,15.979 M16.917,13.416h-1.743l0.189-1.709h1.934L16.917,13.416z M15.458,10.854l0.19-1.708h2.218l-0.38,1.708H15.458z"></path>
                    </svg>
                    Market
                </button>
                <button @click="closeFull()" class="rounded-r-lg relative flex flex-col items-start content-center py-2 px-3 space-x-2  hover:text-black" :class="{'bg-white text-gray-500':category != 2,'bg-purple-900 text-purple-700':category == 2}">
                    <i class="fa fa-remove"></i>
                </button>
            </div>    
        </div>
    </div>
    
    <module-marketplace v-if="category == 2" :only_market="onlyMarket" :is_super_admin="<?=@$CURRENT_USER["isSuperAdmin"] ? "true" : "false";?>" @i-have-module="tengoElModulo" @buy-module="comprarModulo" @import-module="importarModulo" :modules="searchModules"></module-marketplace>
    

    <div class="h-full container relative mx-auto mb-32 relative z-0 pb-12" v-if="category != 2">

        <div class="relative mt-12">
            <div class="pb-8 ">
                <div v-if="category == 0">
                    <p class="font-bold text-center text-primary">Mi Biblioteca de módulos instalados</p>
                    <p class="text-lg text-center mx-auto">Selecciona el módulo que deseas añadir a esta página de los que tienes instalados en tu website</p>
                </div>
                <div v-if="category == 1" >
                    <p class="font-bold text-center text-primary">Explorador de módulos</p>
                    <p class="text-lg text-center mx-auto">Importa módulos de las distintas webs que gestionas a tu biblioteca</p>
                    <p class="bg-yellow-100 rounded-lg border border-yellow-300 p-4 text-yellow-700 mx-auto mt-4"><b>Importante</b> : La instalación de módulos de otra web puede generar incompatibilidades. Si el módulo instalado no funciona correctamente elimínalo y contacta con el departamento técnico para evaluar su integración.</p>
                </div>
            </div>
        </div>

        <div v-if="category == 1 && !domainSelected" class="relative">
            <div class="pb-12">
                <div class="bg-white rounded shadow px-12 py-20">
                    <img src="/lib/plugins/builder_saas/images/research.svg" alt="" class="w-full max-w-xs mb-4 mx-auto">
                    <p class="font-bold text-center text-primary">Primero elige una web</p>
                    <p class="text-lg text-left mt-6 max-w-3xl mx-auto">Para poder importar un módulo de otra web debes seleccionarla en el selector superior</p>
                </div>
            </div>
        </div>

        <div v-if="modules.length && category != 2" class="flex items-center justify-between">
            <div></div>
            <div class="text-lg w-full flex justify-center">
                <button @click="vistaSimple=0" class="mx-2 p-2 rounded border flex items-center" :class="{'bg-theme text-white':!vistaSimple}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-layout-grid  w-6 h-6 mr-4 stroke-current" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                      <rect x="4" y="4" width="6" height="6" rx="1" />
                      <rect x="14" y="4" width="6" height="6" rx="1" />
                      <rect x="4" y="14" width="6" height="6" rx="1" />
                      <rect x="14" y="14" width="6" height="6" rx="1" />
                    </svg>
                    <span>Vista imagen</span>
                </button>
                <button @click="vistaSimple=1" class="mx-2 p-2 rounded border flex items-center " :class="{'bg-theme text-white':vistaSimple}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-list-details w-6 h-6 mr-4 stroke-current" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                      <path d="M13 5h8" />
                      <path d="M13 9h5" />
                      <path d="M13 15h8" />
                      <path d="M13 19h5" />
                      <rect x="3" y="4" width="6" height="6" rx="1" />
                      <rect x="3" y="14" width="6" height="6" rx="1" />
                    </svg>
                    <span>Vista lista</span>
                </button>
            </div>
        </div>        


        
        <ul class="flex flex-wrap -mx-3 pb-32" v-if="!vistaSimple">
            <li v-for="module in searchModules" class="p-3 w-full lg:w-1/2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                  <div class="md:flex md:flex-col">
                    <div class="w-full bg-gray-100 p-4 relative hover:bg-gray-400">
                        
                        <div class="relative image relative rounded-lg shadow-lg bg-white overflow-hidden">
                            
                            <img v-if="!module.onlyAdminModule" class="h-full w-full object-contain object-center absolute top-0 left-0" :src="module.image">
                            <div v-if="module.onlyAdminModule" class="h-full w-full object-contain object-center absolute top-0 left-0 flex flex-col items-center justify-center">
                                <img class="w-48 h-48 object-contain" src="/lib/plugins/builder_saas/images/admin.svg">
                                <p class="text-gray-400 mt-4 uppercase text-center leading-tight">Módulo<br>administrador</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 border-t border-gray-300">
                      <div class="uppercase tracking-wide text-primary font-semibold">{{module.folder}}</div>
                      <span class="block tw mt-1 leading-tight font-medium text-black ">{{module.label}}</span>
                      <p class="mt-2 text-sm text-gray-500 leading-tight">{{module.description}}</p>
                    </div>
                    <div class="p-2 border-t border-gray-300 flex justify-between bg-gray-100">
                        
                        <div v-if="module.price" class="font-bold flex items-center px-6 text-5xl text-black flex-shrink-0">{{module.price | parsePrice}}<span class="text-5xl font-normal text-primary">€</span></div>
                        <div class="flex flex-col justify-end w-full">
                            <button v-if="category == 0" @click="anadirModulo(module)" class="bg-theme text-white rounded-l-lg rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                Añadir
                            </button>
                            <button v-if="(category == 1 || category == 2) && !tengoElModulo(module) && precioModulo(module)" @click="comprarModulo(module)" class="bg-theme text-white rounded-l-lg rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                <i class="fa fa-download"></i>&nbsp;&nbsp;Comprar
                            </button>
                            <button v-if="category == 2" @click="masInfoModulo(module)" class="bg-theme text-white rounded-l-lg rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                <i class="fa fa-download"></i>&nbsp;&nbsp;Mas info
                            </button>
                            <button v-if="(category == 1 || category == 2) && !tengoElModulo(module) && !precioModulo(module)" @click="importarModulo(module)" class="bg-theme text-white rounded-l-lg rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                <i class="fa fa-download"></i>&nbsp;&nbsp;Importar
                            </button>
                            <button v-if="(category == 1 || category == 2) && tengoElModulo(module)" disabled="true" class="bg-gray-300 text-gray-600 rounded-l-lg rounded-r-lg border border-gray-300 px-4 py-2">
                                <i class="fa fa-download"></i>&nbsp;&nbsp;Ya importado
                            </button>
                            <button v-if="0" class="bg-white text-gray-700 hover:bg-gray-300 rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                Previsualizar
                            </button>
                        </div>
                    </div>
                  </div>
                </div>
            </li>
        </ul>

        <ul class="flex flex-wrap -mx-2 mt-2 pb-32" v-if="vistaSimple">
            <li v-for="module in searchModules" class="p-2 w-full">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                  <div class="md:flex md:flex-col">
                    <div class="p-4 border-t border-gray-300 md:flex justify-between">
                        <div class="w-32 flex-shrink-0 mr-6">
                            <div class="relative image thumb relative rounded-lg shadow-lg bg-white overflow-hidden">
                            
                                <img v-if="!module.onlyAdminModule" class="h-full w-full object-contain object-center absolute top-0 left-0" :src="module.image">
                                <div v-if="module.onlyAdminModule" class="h-full w-full object-contain object-center absolute top-0 left-0 flex flex-col items-center justify-center">
                                    <img class="w-6 h-6 object-contain" src="/lib/plugins/builder_saas/images/admin.svg">
                                    <p class="text-gray-400 mt-4 text-xs uppercase text-center leading-tight">Módulo<br>administrador</p>
                                </div>
                            </div>
                        </div>
                        <div class="w-full flex justify-center items-start flex-col">
                            <div class="uppercase tracking-wide text-primary font-semibold">{{module.folder}}</div>
                            <span class="block tw mt-1 leading-tight font-medium text-black ">{{module.label}}</span>
                            <p class="mt-2 text-sm text-gray-500 leading-tight">{{module.description}}</p>
                        </div>

                        <div class="p-2 pl-6 border-l w-64 flex-shrink-0 items-end border-gray-300 flex flex-col justify-end ">
                            <div v-if="module.price" class="font-bold flex items-center px-6 text-4xl text-black flex-shrink-0">{{module.price | parsePrice}}<span class="text-4xl font-normal text-primary">€</span></div>
                            <div class="flex flex-col justify-end w-full">
                                <button v-if="category == 0" @click="anadirModulo(module)" class="block tw w-full bg-theme text-white rounded-l-lg rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                    Añadir
                                </button>
                                <button v-if="(category == 1 || category == 2) && !tengoElModulo(module) && precioModulo(module)" @click="comprarModulo(module)" class="block tw w-full bg-black text-white rounded-l-lg rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                    <i class="fa fa-download"></i>&nbsp;&nbsp;Comprar
                                </button>
                                <button v-if="category == 2" @click="masInfoModulo(module)" class="bg-theme mt-2 text-white rounded-l-lg rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                    <i class="fa fa-search"></i>&nbsp;&nbsp;Mas info
                                </button>
                                <button v-if="(category == 1 || category == 2) && !tengoElModulo(module) && !precioModulo(module)" @click="importarModulo(module)" class="block tw w-full bg-theme text-white rounded-l-lg rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                    <i class="fa fa-download"></i>&nbsp;&nbsp;Importar
                                </button>
                                <button v-if="(category == 1 || category == 2) && tengoElModulo(module)" disabled="true" class="block tw w-full bg-gray-300 text-gray-600 rounded-l-lg rounded-r-lg border border-gray-300 px-4 py-2">
                                    <i class="fa fa-download"></i>&nbsp;&nbsp;Ya importado
                                </button>
                                <button v-if="0" class="block tw w-full bg-white text-gray-700 hover:bg-gray-300 rounded-r-lg shadow border border-gray-400 px-4 py-2">
                                    Previsualizar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                  </div>
                </div>
            </li>
        </ul>
    </div>
</div>
<script>
var DOMAINS = <?=json_encode(array_map(function($rec){ return ["num" => $rec["num"],"domain" => $rec["domain"]]; },$CURRENT_USER["domains"]));?>;

function startNewModulesVue() {
    newModules = new Vue({
        el:"#newModulesVue",
        data:{
            onlyMarket:false,
            prevRequestUrl:"",
            localModules:[],
            active:false,
            modules:[],
            folders:[],
            folderSelected:"",
            searchTerm:"",
            domainSelected:"",
            oldData:null,
            vistaSimple:null,
            domains:[],
            category:0,
            pago:0
        },
        components:{
            'module-marketplace':httpVueLoader('/lib/plugins/builder_saas/tpl/componentes/moduleMarketPlace.vue?timestamp=' + (new Date().getTime()))
        },
        computed:{
            searchModules(){
                var result = this.modules;
                if (this.folderSelected) result = result.filter(rec => rec.folder == this.folderSelected);
                if (this.searchTerm) result = result.filter(rec => rec.label.toLowerCase().indexOf(this.searchTerm.toLowerCase()) > -1);
                if (MENU == 'mail_marketing') {
                    result = result.filter(rec => rec.MJMLModule);
                }else{
                    result = result.filter(rec => !rec.MJMLModule);
                }
                return result;
            }
        },
        watch:{
            'category':function(newVal,oldVal){
                if (newVal == 1 && oldVal == 2){
                    this.domainSelected = "";
                    return;
                }
                if (newVal !== oldVal) this.init();
            }/*,
            'domainSelected':function(newVal,oldVal){
                if (newVal == 384) return;
                if (newVal !== oldVal) this.init();
            }*/,
            'vistaSimple':function(newVal,oldVal){
                if (newVal) {
                    localStorage.setItem("modulos-vista-simple",newVal);
                }else{
                    localStorage.removeItem("modulos-vista-simple");
                }
            }
        },
        filters:{
            parsePrice(value){
                return parseFloat(value).toFixed(2);
            }
        },
        mounted() {
            this.domains = DOMAINS;
            // this.domains = this.domains.filter(rec => rec.num != 384);
            //this.init();
        },
        methods:{
            close:function(){
                this.active=false;
            },
            closeFull:function(){
                toggleNewModuleModal();
            },
            anadirModulo(modulo){
                modulo.module.add();
                myConfig.push(modulo.module);
                this.init(true);
                swal({
                    title:"OK!",
                    text:"'El módulo ha sido añadido'",
                    icon:"success",
                    button : false,
                    timer:500
                });
                var splitNode = document.querySelector(".split.left");
                splitNode.scrollTop = splitNode.scrollHeight;
                
                for (let myConf of myConfig){
                    if (myConf.isActive) {
                        myConf.renderEditView();
                    }
                }
                setController();
                needToSave();
            },
            masInfoModulo(module){
                console.log("pepe");
            },
            comprarModulo(module){
                var dom = this.domains.find(rec => rec.num == this.domainSelected);
                swal({
                    title:"Comprar módulo",
                    text:"¿Deseas comprar el módulo seleccionado?. La página del proveedor seguro Stripe se abrirá en una nueva ventana para que puedas realizar el pago. Una vez completado podrás hacer click en 'Añadir Módulo' y ver tu nuevo módulo en la lista.",
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
                        var mod = this.modules.find(rec => rec.id == module.id);
                        if (!mod) alert("Error al encontrar el modulo");
                        
                        this.downloadData(`admin.php?menu=${MENU}&action=edit&num=${NUM}&payModule=` + mod.id + `&timestamp=` + (new Date().getTime()),(json) => {
                            if (json.data[0]) window.open(json.data[0]);
                            
                            // this.oldData = json;
                            // this.rebuildModuleList(json);
                        });
                        
                        return true;
                    }else{
                        
                        return false;
                    }
                });
                
            },
            importarModulo(module){
                
                var dom = this.domains.find(rec => rec.num == this.domainSelected);
                swal({
                    title:"Importar módulo",
                    text:"¿Deseas importar el módulo de la web " + dom.domain + "?",
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
                        var mod = this.modules.find(rec => rec.id == module.id);
                        if (!mod) alert("Error al encontrar el modulo");
                        
                        mod.module.sendModuleToWeb(dom.domain).then((data) => {
                            this.category = 0;

                            swal({
                                title:"OK!",
                                text:"'El módulo ha sido importado'",
                                icon:"success",
                                button : false,
                                timer:500
                            });

                        });
                        
                        return true;
                    }else{
                        
                        return false;
                    }
                });
                
            },
            init: function(withOldData = false){
                

                if (localStorage.getItem("modulos-vista-simple") && localStorage.getItem("modulos-vista-simple") != this.vistaSimple ) this.vistaSimple = localStorage.getItem("modulos-vista-simple");

                this.active = true;
                this.modules = [];
                var url = 'getWebModules=1';
                //if (this.category == 1) url = 'getLocalModules=1';
                if (this.category == 1) url = `getWebModules=1&domain=${this.domainSelected}&auxVar=1`;
                if (this.category == 1 && !this.domainSelected) return;
                
                if (this.category == 2) {
                    this.domainSelected = CDN_MODULES_WEBSITE;
                    url = `getWebModules=1&domain=${this.domainSelected}&auxVar=1`;
                }

                if(withOldData && this.oldData) {
                    this.rebuildModuleList(this.oldData);
                } else {
                    this.downloadData(`admin.php?menu=${MENU}&action=edit&num=${NUM}&` + url,(json) => {
                        this.oldData = json;
                        if (url == 'getWebModules=1') {
                            this.localModules = json;
                        }
                        this.rebuildModuleList(json);
                    });
                }

                /*if (!this.localModules.length && url != 'getWebModules=1') {
                    this.downloadData(`admin.php?menu=${MENU}&action=edit&num=${NUM}&getWebModules=1`,(json) => {
                        this.localModules = json;
                        this.rebuildModuleList(json);
                    });
                } */               
                
                
                
                
            },
            changeDomain(){
                if (this.domainSelected == 384) return;
                //if (newVal !== oldVal) this.init();
                this.init();
            },
            tengoElModulo(module){
                
                return Object.keys(this.localModules).indexOf(module.id) > -1;
            },
            rebuildModuleList(json,forceSend = false) {
                


                this.folderSelected = "";
                this.searchTerm = "";
                this.folders = [];
                
                for (let index in json){
                    
                    var mod = json[index];
                    //if (mod.onlyAdminModule) continue;

                    if (mod.label.indexOf("/") > -1 && this.folders.indexOf(mod.label.split("/")[0].trim()) < 0){
                        this.folders.push(mod.label.split("/")[0].trim());    
                    }else if (mod.label.indexOf("/") < 0 && this.folders.indexOf("General") < 0) {
                        this.folders.push("General");
                    }
                    
                    var module = {};
                    module.builder = json[index];
                    module.builder.id = index;
                    module.data = [];
                    module.referencias = json[index].referencias;
                    
                    mod.path = parseBuilderModuleRootURL(mod.path);
                    
                    this.modules.push({
                        id:mod.id,
                        onlyAdminModule:mod.onlyAdminModule,
                        folder:mod.label.indexOf("/") > -1 ? mod.label.split("/")[0].trim() : "General",
                        label:mod.label.indexOf("/") > -1 ? mod.label.split("/")[1].trim() : mod.label,
                        fullLabel:mod.label,
                        MJMLModule:mod.MJMLModule,
                        description:mod.description,
                        image:mod.path + "/" + mod.thumbnail + '?v=' + APARTADO_DATA.updatedDate,
                        module:this.onlyMarket ? [] : new Module(module,splitsView,this.category && !forceSend ? true : false),
                        //module:new Module(module,splitsView,this.category ? false : false),
                        price:mod.price || 0
                    });
                    
                }

                this.modules = this.modules.sort(this.compare);
                this.modules = this.modules.sort((a,b) => { return a.onlyAdminModule ? 1 : -1; });
            },
            compare:function( a, b,field = "fullLabel" ) {
              if ( a.fullLabel < b.fullLabel ){
                return -1;
              }
              if ( a.fullLabel > b.fullLabel ){
                return 1;
              }
              return 0;
            },
            precioModulo(module){
                if (<?= @$CURRENT_USER["isSuperAdmin"] ? "true" : "false"; ?>) {
                    return 0;
                }
                if (module.price) return module.price;
                return 0;
            }

        }
    })
}
    
window.addEventListener("load", () => startNewModulesVue());
</script>
<style>
    #newModulesVue{font-size:20px;width:calc(100% - 330px);transition: all .3s ease-in-out;}
    #newModulesVue:not(.showed){pointer-events: none;
    transform: translateY(-120%);}
    #newModulesVue .container{max-width:1000px}
    #newModulesVue .image{width:400px;margin:0 auto;}
    #newModulesVue .image.thumb{width:100%;margin:0 auto;}
    #newModulesVue .image::after{content:"";display:block;padding-top:100%;}
    .btn-primary.rounded-xs{border-radius:5px;}
    .z-5000{z-index:5000;}
    .bloque{width:auto;transform: none;position: relative}
    .bloque .fa-remove{position: absolute;border:none;top:10px;right:10px;color:rgba(0,0,0,0.2);display:block;opacity:1;float:none;margin:0px;pointer-events: all;}
    .bloque .fa-lock{right:35px;top:12px;}

</style>
