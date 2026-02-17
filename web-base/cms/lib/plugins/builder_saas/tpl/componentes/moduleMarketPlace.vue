<template>
    <div class=" h-full overflow-auto bg-gray-900" ref="marketRef">
        <!--<div v-if="!is_super_admin" class="bg-white rounded shadow px-12 py-20">
            <img src="/lib/views/pluginsSelector/images/icon.png" alt="" class="w-full max-w-sm mx-auto">
            <p class="font-bold text-center text-primary">MarketPlace aún no disponible</p>
            <p class="text-lg text-left mt-6 max-w-3xl mx-auto">Estamos realizando una serie de módulos. Tanto si eres Marketer, SEO, Desarrollador o propietario del negocio en breve podrás añadir módulos a este CMS con los que podrás hacer tu día a día algo más fácil.</p>
        </div>-->
        <div v-show="only_market" class="container mx-auto max-w-6xl text-white flex flex-wrap justify-between items-center px-4 xl:px-0 pt-8 lg:pt-10 lg:pb-4">
            <div class="w-full lg:w-5/12"><h3 class="font-bold text-3xl leading-none">Marketplace de <br><span class="text-primary-600">Módulos Acai</span></h3></div>
            <div class="w-full lg:w-7/12">
                <p class="opacity-50 text-lg">Amplia las capacidades de tus webs añadiendo nuevos módulos que puedas insertar y personalizar a tu gusto. Cualquier módulo adquirido podrá ser utilizado en todas tus webs tantas veces como desees sin límite. </p>
            </div>
        </div>

        <div v-show="!loading || only_market" class=" pb-12">
            <div v-if="!selectedModule.id_module" class="container mx-auto max-w-6xl px-4 xl:px-0 text-white  pt-6">
                <ul class="hidden lg:block m-0 p-0">
                    <li v-for="destacado in destacados">
                        <div class="bg-gray-800 rounded-lg shadow-lg relative overflow-hidden">
                            <img :src="destacado.banner_destacado[0].urlPath" class="absolute top-0 left-0 w-full h-full object-cover">
                            <div class="relative p-12 max-w-lg">
                                <p class="text-xl font-bold leading-snug max-w-sm">{{ destacado.titulo }}</p>
                                <p v-if="destacado.descripcion_corta" class="leading-snug mt-4 opacity-50 text-sm font-bold max-w-sm">{{ destacado.descripcion_corta }}</p>
                                <p class="text-xl leading-none mt-6 text-gray-600 line-through" v-if="destacado.price_old">{{ destacado.price_old }}€</p>
                                <p class="text-3xl leading-none font-bold text-primary-600">{{ destacado.price }}€</p>
                                <div class="mt-6 flex">
                                    
                                    <button v-if="!$parent.tengoElModulo({id:destacado.id_module}) && canIBuy(destacado) && !tengoComprado(destacado)" @click="buyModule(destacado)" class="bg-primary-600 hover:bg-black text-white px-6 py-2 text-xl hover:text-white rounded-lg border border-primary-600 mr-4">Comprar</button>
                                    <button v-if="!$parent.tengoElModulo({id:destacado.id_module}) && tengoComprado(destacado)" @click="importarModulo(destacado)" class="bg-green-600 hover:bg-black text-white px-6 py-2 text-xl hover:text-white rounded-lg border border-green-600 mr-4">Instalar</button>
                                    <button v-if="$parent.tengoElModulo({id:destacado.id_module})" @click="importarModulo(destacado)" class="bg-primary-700 hover:bg-black text-white px-6 py-2 text-xl hover:text-white rounded-lg border border-primary-700 mr-4">Actualizar</button>
                                    <button @click="viewModule(destacado)" class="bg-transparent text-gray-600 hover:bg-black hover:text-white hover:border-white border border-gray-600 px-6 py-2 text-xl rounded-lg">Más info </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg mt-6 mb-6 flex flex-wrap">
                    <div v-for="modulo in pricingModules" class="flex w-full lg:w-1/2 xl:w-1/3">
                        <div class="bg-gray-700 shadow-lg rounded-lg m-3 flex flex-col">
                            <div @click="viewModule(modulo)" class="p-1/6 relative rounded-t-lg overflow-hidden cursor-pointer hover:opacity-75 flex-shrink-0">
                                <img :src="modulo.imagen[0].urlPath" class="absolute top-0 left-0 w-full h-full object-cover">
                            </div>
                            <div class="p-6 text-base flex flex-col h-full justify-between">
                                <p class="text-sm leading-snug flex-shrink-0">{{ modulo.titulo }}</p>
                                <p v-if="modulo.descripcion_corta" class="text-xs leading-snug mt-4 opacity-50">{{ modulo.descripcion_corta }}</p>
                                <div class="flex-shrink-0">
                                    <div class="flex flex-wrap items-center mt-4">
                                        <p class="text-lg leading-none font-bold text-primary-600 mr-2">{{ modulo.price }}€</p>
                                        <p class="text-xs leading-none text-gray-600 line-through" v-if="modulo.price_old">{{ modulo.price_old }}€</p>
                                    </div>
                                    <div class="mt-4 flex">
                                        <button v-if="!$parent.tengoElModulo({id:modulo.id_module}) && canIBuy(modulo) && !tengoComprado(modulo)" @click="buyModule(modulo)" class="bg-primary-600 hover:bg-black text-white px-6 py-2 text-xs hover:text-white rounded-lg border border-primary-600 mr-4">Comprar</button>
                                        <button v-if="!$parent.tengoElModulo({id:modulo.id_module}) && tengoComprado(modulo)" @click="importarModulo(modulo)" class="bg-green-600 hover:bg-black text-white px-6 py-2 text-xs hover:text-white rounded-lg border border-green-600 mr-4">Instalar</button>
                                        <button v-if="$parent.tengoElModulo({id:modulo.id_module})" @click="importarModulo(modulo)" class="bg-primary-700 hover:bg-black text-white px-6 py-2 text-xs hover:text-white rounded-lg border border-primary-700 mr-4">Actualizar</button>
                                        <button @click="viewModule(modulo)" class="bg-transparent text-gray-600 hover:bg-black hover:text-white hover:border-white border border-gray-600 px-6 py-2 text-xs rounded-lg">Más info </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="selectedModule.id_module" class="container mx-auto max-w-6xl px-4 xl:px-0 text-white pt-6">
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg mb-6 flex flex-wrap">
                    <div class="w-full lg:w-7/12">
                        <div class="p-1/6 relative rounded-lg overflow-hidden">
                            <img :src="selectedModule.imagen[0].urlPath" class="absolute top-0 left-0 w-full h-full object-cover">
                        </div>
                    </div>
                    <div class="w-full lg:w-5/12">
                        <div class="relative py-6 px-12 max-w-lg">
                            <p class="text-xl font-bold leading-snug max-w-sm">{{ selectedModule.titulo }}</p>
                            <p class="text-xl leading-none mt-6 text-gray-600 line-through" v-if="selectedModule.price_old">{{ selectedModule.price_old }}€</p>
                            <p class="text-3xl leading-none font-bold text-primary-600">{{ selectedModule.price }}€</p>
                            <div class="mt-6 flex flex-wrap">
                                <button v-if="!$parent.tengoElModulo({id:selectedModule.id_module}) && canIBuy(selectedModule) && !tengoComprado(selectedModule)" @click="buyModule(selectedModule)" class="mb-4 bg-primary-600 hover:bg-black text-white px-6 py-2 text-xl hover:text-white rounded-lg border border-primary-600 mr-4">Comprar</button>
                                <button v-if="!$parent.tengoElModulo({id:selectedModule.id_module}) && tengoComprado(selectedModule)" @click="importarModulo(selectedModule)" class="mb-4 bg-green-600 hover:bg-black text-white px-6 py-2 text-xl hover:text-white rounded-lg border border-green-600 mr-4">Instalar</button>
                                <button v-if="$parent.tengoElModulo({id:selectedModule.id_module})" @click="importarModulo(selectedModule)" class="mb-4 bg-primary-700 hover:bg-black text-white px-6 py-2 text-xl hover:text-white rounded-lg border border-primary-700 mr-4">Actualizar</button>
                                

                                
                                <button @click="selectedModule = {}" class="mb-4 bg-transparent text-gray-600 hover:bg-black hover:text-white hover:border-white border border-gray-600 px-6 py-2 text-xl rounded-lg">Volver </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 mb-6 flex flex-wrap -mx-2">
                    <div class="w-full lg:w-8/12 px-2">
                        <div class="bg-gray-800 p-6 rounded-lg shadow-lg flex flex-wrap">
                            <div v-html="selectedModule.content"></div>
                        </div>
                    </div>
                    <div class="w-full lg:w-4/12 px-2">
                        <div class="bg-gray-800 p-6 rounded-lg shadow-lg flex flex-wrap">
                            <div v-for="modulo in randomModules" class="w-full">
                                <div @click="viewModule(modulo)" class="bg-gray-700 shadow-lg rounded-lg m-3 cursor-pointer hover:opacity-75 ">
                                    <div class="p-1/6 relative rounded-t-lg overflow-hidden">
                                        <img :src="modulo.imagen[0].urlPath" class="absolute top-0 left-0 w-full h-full object-cover">
                                    </div>
                                    <div class="p-6 text-base">
                                        <p class="text-sm leading-snug">{{ modulo.titulo }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style type="text/css" scoped>
    p{color: inherit;}
    .bg-gray-900{background-color: #17102A}
    .bg-gray-800{background-color: #1F1B35}
    .bg-gray-700{background-color: #251E42}
    .text-gray-600{color: #C7BEFE}
    .text-gray-700{color: #968AD6}
    .text-gray-800{color: #A36EFF}
    .text-primary-600{color: #FF4E85}
    .bg-primary-600{background: linear-gradient(to bottom,#EE0CB7 0%,#960076 100%);}
    .bg-primary-700{    background: linear-gradient(rgb(12 172 238) 0%, rgb(0 55 150) 100%);}
    .bg-green-600{background: linear-gradient(rgb(140 217 100) 0%, rgb(8 111 26) 100%);}
    .bg-primary-600:hover{background: inherit;background-color: #000}
    .border-primary-600{border-color: #DC00A7;}
    .border-primary-700{border-color: rgb(69 88 231);}
    .border-green-600{border-color: rgb(77 169 6);}
    .border-gray-600{border-color: #C7BEFE;}
    .p-1\/6::after{
        content: "";
        display: block;
        padding-top: 50%;
    }
</style>
<script>
    module.exports = {
        props:["is_super_admin","modules","only_market"],
        data(){
            return{
                loading:false,
                pricingModules:[],
                destacados:[],
                selectedModule:{},
                randomModules:[]
            }
        },
        watch:{
            
        },
        mounted(){
            this.init();
        },
        methods:{
            init(){
                this.loading = true;
                document.getElementById("loading").style.opacity = 1;
                this.downloadData(`admin.php?menu=${MENU}&action=edit&num=${NUM}&getPricingModules=1&`,(json) => {
                    this.loading = false;
                    document.getElementById("loading").style.opacity = 0;
                    this.pricingModules = json.filter(rec => !parseInt(rec.destacado));
                    this.destacados = json.filter(rec => parseInt(rec.destacado));
                });
            },
            tengoComprado(modulo){
                return this.modules.find(rec => rec.id == modulo.id_module && !rec.price);
            },
            canIBuy(modulo){

                var existModuleInCdn = this.modules.find(rec => rec.id == modulo.id_module);
                //var purchasedModules = this.modules.find(rec => rec.id == modulo.id_module);

                return this.modules.find(rec => rec.id == modulo.id_module);
            },
            importarModulo(modulo){
                this.$emit("import-module",{id:modulo.id_module});
            },
            viewModule(modulo){
                const shuffled = JSON.parse(JSON.stringify(this.pricingModules.filter(rec => rec.id_module != modulo.id_module))).sort(() => 0.5 - Math.random());
                this.randomModules = shuffled.slice(0, 3);

                this.selectedModule = modulo;
                this.$refs.marketRef.scrollTo(0,0);
            },
            buyModule(modulo){
                this.$emit("buy-module",{id:modulo.id_module});
            }
        }
    };
</script>