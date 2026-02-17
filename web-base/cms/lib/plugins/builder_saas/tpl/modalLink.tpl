<div id="modalLinkBuilder" class="fixed top-0 left-0 w-full h-full z-5000 flex items-center justify-center hidden" v-if="showed">
    <div class="absolute top-0 left-0 w-full h-full bg-black opacity-75" ></div>
    <div class="absolute top-0 left-0 w-full h-full bg-black opacity-25" @click="searchTerm = '';showed = !showed"></div>
    <div class="container mx-auto max-w-4xl relative">
        <div class="bg-white rounded-lg shadow-lg p-12">
            <div class="relative flex justify-between bg-gray-100 border rounded shadow mb-4 w-full flex">
                <input type="text" class="appearance-none text-4xl p-4 w-full" v-model="searchTerm" placeholder="Buscar enlace">
                <div class="p-4 flex items-center justify-center flex-shrink-0 hover:bg-gray-200 cursor-pointer" @click="searchTerm = ''" v-if="searchTerm">
                    <svg class="fill-current text-gray-700 w-8 h-8 flex items-center justify-center"  viewBox="0 0 20 20">
                        <path d="M10.185,1.417c-4.741,0-8.583,3.842-8.583,8.583c0,4.74,3.842,8.582,8.583,8.582S18.768,14.74,18.768,10C18.768,5.259,14.926,1.417,10.185,1.417 M10.185,17.68c-4.235,0-7.679-3.445-7.679-7.68c0-4.235,3.444-7.679,7.679-7.679S17.864,5.765,17.864,10C17.864,14.234,14.42,17.68,10.185,17.68 M10.824,10l2.842-2.844c0.178-0.176,0.178-0.46,0-0.637c-0.177-0.178-0.461-0.178-0.637,0l-2.844,2.841L7.341,6.52c-0.176-0.178-0.46-0.178-0.637,0c-0.178,0.176-0.178,0.461,0,0.637L9.546,10l-2.841,2.844c-0.178,0.176-0.178,0.461,0,0.637c0.178,0.178,0.459,0.178,0.637,0l2.844-2.841l2.844,2.841c0.178,0.178,0.459,0.178,0.637,0c0.178-0.176,0.178-0.461,0-0.637L10.824,10z"></path>
                    </svg>
                </div>
            </div>
            <ul v-if="options.length" class="p-0 m-0">
                <li class="p-3 hover:bg-gray-300 rounded-lg mt-2 " :class="{'font-bold text-white bg-theme':option.selected,'hover:text-black text-gray-600 bg-gray-100 cursor-pointer ':!option.selected}" v-for="option,index in filterOptions" @click="selectData(option)">{{option.enlace}}</li>
            </ul>
            <div v-if="!options.length" class="p-8 text-gray-600 text-2xl text-center">
                <i class="fa fa-search"></i>&nbsp;Busca en el campo superior la página que quieres enlazar
            </div>
        </div>
    </div>
</div>
<script>

    var modalLinkBuilder = null;
    
    function startModalLinkBuilder() {
        modalLinkBuilder = new Vue({
            el:"#modalLinkBuilder",
            data:{
                showed:false,
                node:null,
                searchTerm:"",
                myValue:"",
                myOption:{},
                options:[]
            },
            computed:{
                filterOptions(){
                    return this.options;
                }
            },
            filters:{

            },
            watch:{
                searchTerm:function(newVal,oldVal){

                    if (newVal == oldVal) return;
                    if (!newVal) {
                        // Si no hay nada escrito
                        if (this.myValue){
                            // Si tengo un valor inicial
                            this.search(this.myValue,true);
                        }else{
                            // Si no lo tengo
                            this.search();    
                        }
                        
                        return;
                    }else{
                        // Si hay algo escrito
                        this.search(newVal);
                        return;
                    }
                    
                }
            },
            mounted() {
                this.init();
            },
            
            methods:{
                search:function(value,myOption = false){

                    this.getData(value ? value : '').then((json) => {
                        if (json.length) this.options = json;

                        if (myOption){
                            this.myValue = value;
                            var encontrada = this.options.find(rec => rec.num == value.split(",")[1] && rec.tableName == value.split(",")[0]);
                            if (encontrada) encontrada.selected = true;
                        }

                    });
                },
                getData(value){
                    return new Promise((resolve,reject) => {
                        this.downloadData(`admin.php?menu=${MENU}&action=edit&num=${NUM}&getWebsiteLinks=` + (value ? value : '') + `&slash=`,(json) => {
                            resolve(json);
                        });
                    });  
                },
                init: function(){
                    
                    // console.log("Modal Link initialized");
                },
                resetOption: function(){
                    console.log("Reset");
                    this.options = this.myOption ? [this.myOption] : [];
                },
                selectData(option){
                    
                    var value = option.tableName + ',' + option.num;
                    
                    if (this.node){
                        this.node.value = value;
                        this.node.dispatchEvent(new Event("change"));
                    }
                    this.searchTerm = "";
                    this.showed = false;
                },
                open: function(nodeElement){
                    this.showed = true;
                    
                    this.myOption = {};
                    if (nodeElement.value) {
                        this.search(nodeElement.value,true);
                    }else{
                        this.search();
                    }
                    
                    this.node = nodeElement;
                }
            }
        })
    }

    window.addEventListener("load", () => {
        document.querySelector("#modalLinkBuilder").classList.remove("hidden");
        startModalLinkBuilder();
    });
</script>
