<template>
    <div v-if="!data.isDragPlaceHolder">
        <div v-if="!vista_seo && ((is_admin) || (!is_admin && !data.admin_only_section))" class="optionsSelect  absolute h-full items-center justify-start -ml-3 hidden xl:flex">
            <input type="checkbox" class="checkbox w-8 h-8 rounded-full bg-theme border appearance-none" :value="data.num" v-model="selected" >
        </div>
        <div v-if="(is_admin) || (!is_admin && !data.admin_only_section)" class="m-8 p-8 pl-12 rounded-lg relative bg-gray-100 shadow-xl z-20 relative transitionAppearUp" :class="{'hover:bg-gray-200':!vista_seo,'opacity-100': parseInt(data[campo_visible]) || vista_seo, 'opacity-50': !parseInt(data[campo_visible]) && !vista_seo}">
            <div class="flex justify-between">
                <div v-if="!vista_seo && data.children && data.children.length" @click="store.toggleOpen(data)" class="absolute top-0 left-0 w-12  bg-gray-400 h-full rounded-l-lg shadow-inner flex justify-center items-center text-gray-600 text-xl"><i :class="{'fa fa-minus':data.open,'fa fa-plus':!data.open}"></i></div>
                <div v-if="!vista_seo && data.children && data.children.length" class="w-12 "></div>

                <!--<div v-if="MENU=='apartados' && !vista_seo" class="hidden md:block w-1/12 imagen bg-white overflow-hidden rounded-lg shadow relative h-32 mr-8">
                    <img :src="!external_link(data.enlace) ? data.thumbnail : 'https://cms.cocosolution.com/lib/plugins/builder_saas/images/external-link-symbol.png'" alt="" class="absolute w-full h-full object-center object-cover">

                </div>-->
                <div class="w-full items-center flex" :class="{'-mr-12':!vista_seo && data.children && data.children.length,'md:w-4/12':!vista_seo}" >
                    <div class="w-full break-all truncate ... pr-12">
                        <div class="flex items-center">
                            <h4 class="text-gray-800 text-2xl m-0" v-html="data[campo_name]"></h4>
                            <span v-if="vista_seo && data.aliases" @click="viewAliases(data.aliases)" class="hover:text-black mt-1 cursor-pointer block tw ml-3 text-base underline italic">( {{ data.aliases.length }} ) redirecciones</span>
                        </div>
                        <div class="clearfix"></div>
                        <a v-if="!vista_seo" :href="!external_link(data.enlace) ? 'https://' + userDomain + data.enlace : data.enlace" target="_blank" rel="noopener" class="active:no-underline focus:no-underline hover:underline text-xl m-0 truncate" >{{data.enlace}}</a>
                        <div v-if="view_squema" class="mt-2">
                            <div v-for="(field,index) of filterViewSchema(view_squema, menu)" v-if="data[index]" class="text-gray-600 font-thin text-xl m-0">
                                <i class="fa fa-bookmark-o text-gray-500"></i>&nbsp;<span>{{field.label}}</span> : {{tables_cache[field.optionsTablename] && tables_cache[field.optionsTablename][data[index]] ? tables_cache[field.optionsTablename][data[index]] : data[index] | parseField(field)}}
                            </div>
                        </div>
                        <span class="text-xs py-1 px-2 text-white bg-theme rounded" v-if="data.admin_only_section"><i class="fa fa-key"></i> Admin only</span>
                    </div>
                </div>


                <div class="hidden lg:flex w-5/12 items-center" v-if="!vista_seo">
                    <div class="">
                        <h4 class="mb-2 text-black ...">{{data.titulo_de_pagina}}</h4>
                        <h5 class="h-20 py-1 text-xl leading-tight overflow-hidden">{{data.metatag_descripcion}}</h5>
                    </div>
                </div>
                <!--<div class="hidden md:items-center md:flex">
                    <a v-if="typeof data.pychecker !== 'undefined' && data.enlace && !vista_seo" v-on:click.once="requestPychecker(data)" :class="{'text-gray-400 border-gray-300':data.pychecker.score == 0,'text-red-500 border-red-300':data.pychecker.score <= 60 && data.pychecker.score > 0,'text-orange-500 border-orange-300':data.pychecker.score > 60 && data.pychecker.score <= 80,'text-green-500 border-green-300':data.pychecker.score > 80}" class="w-20 flex items-center justify-center h-20 border-2 hover:text-black hover:no-underline ml-8 text-3xl remove rounded-full">{{data.pychecker.score}}</a>
                    <a v-if="!data.enlace" v-on:click="showAlert('El registro no dispone de enlace. Debes asignarle uno')" class="w-20 text-red-400 flex items-center justify-center h-20 border-2 border-red-200 hover:text-black hover:no-underline ml-8 text-3xl remove rounded-full"><i class="fa fa-warning"></i></a>
                </div>-->
                <a v-if="vista_seo" :href="!external_link(data.enlace) ? 'https://' + userDomain + data.enlace : data.enlace" target="_blank" rel="noopener" class="flex-shrink-0 active:no-underline focus:no-underline hover:underline text-xl m-0" >{{data.enlace}}</a>
                <a v-if="!vista_seo && !data.denegate_edit" class="w-20  flex items-center justify-center hover:text-black hover:no-underline lg:ml-8 text-3xl edit" :href="'?menu='+MENU+'&action=edit&num=' + data.num"><i class="fa fa-edit"></i></a>    
                <div v-if="!vista_seo" class="flex-wrap w-20 hidden lg:flex">
                    <a v-if="campo_visible!=''" class="fa fav text-3xl w-8 lg:w-20 py-4 flex items-center justify-center hover:no-underline" :class="{'fa-eye': parseInt(data[campo_visible]), 'fa-eye-slash text-gray-400': !parseInt(data[campo_visible])}" v-on:click="toggleVisible(data)" :data-valor="data[campo_visible]"></a>
                    <a v-if="data.children && data.children.length" class="fa fav w-4 lg:w-20 text-3xl text-gray-400 py-4 flex items-center justify-center hover:no-underline" :class="{'fa-share': parseInt(data.layout), 'fa-sort-amount-asc': !parseInt(data.layout)}" v-on:click="toggleLayout(data)" :data-valor="data.layout"></a>
                </div>
                <a v-if="!vista_seo && !disable_erase && !data.denegate_remove" class="w-20  flex items-center justify-center hover:text-black hover:no-underline text-3xl remove" v-on:click="eraseRecord(data.num)"><i class="fa fa-remove"></i></a>
                <a v-if="!vista_seo" class="w-20 text-gray-400 handle flex items-center justify-center hover:text-black hover:no-underline lg:ml-8 text-3xl remove"><i class="fa fa-bars"></i></a>
            </div>
            
            <div v-if="vista_seo">
                <list-pychecker :dato="data" :paginas="filtered_items" v-if="typeof PYCHECKER !== 'undefined' && PYCHECKER && data.enlace"></list-pychecker>
                <list-kwtracker :dato="data" :paginas="filtered_items" @update-data="updateDataKW" v-if="typeof KWTRACKER !== 'undefined' && KWTRACKER && data.enlace"></list-kwtracker>
                <list-linkbuilding :dato="data" :paginas="filtered_items" v-if="typeof LINKBUILDING !== 'undefined' && LINKBUILDING && data.enlace"></list-linkbuilding>
                <list-analytics :dato="data" :paginas="filtered_items" v-if="typeof ANALYTICS !== 'undefined' && ANALYTICS && data.enlace"></list-analytics>
                <list-leads :dato="data" :paginas="filtered_items" v-if="typeof LEADS !== 'undefined' && LEADS && data.enlace"></list-leads>
            </div>

        </div>
    </div>
</template>
<script>
    module.exports = {
        props:["data","store","is_admin","disable_erase","campo_visible","vista_seo","campo_name","view_squema","filtered_items","tables_cache"],
        data(){
            return{
                menu:null,
                selected:false,
            }
        },
        watch:{
            selected:function(newVal,oldVal){
                this.$emit("select_record",this.data.num,newVal)
            }
        },
        filters:{
            parseField:function(value,field){
                if (!field) return value;
                if (field.optionsTablename) return value;
                if (field && field.label.toLowerCase().includes("precio")){
                    return value + "€";    
                }
                return value;
            }
        },
        mounted(){
            this.menu = MENU;
        },
        methods:{
            viewAliases(aliases){
                Swal.fire({
                    title:"Redirecciones a la página",
                    html:`Existen ${aliases.length} redirecciones a esta página: <br><br> <ul class="p-0 m-0"><li class="text-lg bg-gray-200 p-1 rounded mb-1 text-left truncate"> · ${ aliases.join('</li><li class="text-lg text-left bg-gray-200 p-1 rounded mb-1 truncate"> · ') } </li></ul>`,
                    icon:"info"
                })
            },
            updateDataKW(scoresArray){
                
                const reducer = (acumm, curr) => acumm + curr;
                var score = scoresArray.map(r => r.score).reduce(reducer) / scoresArray.length;
                score = score.toFixed(2);
                
                this.$emit("update-data",{num:null,type:"KWTRACKER",subType:"keywords",subTypeLabel:"Keywords",score:score});
            },
            external_link(link){
                return link ? link.includes("https") !== false : '';
            },
            filterViewSchema(viewSquema, menu) {
                delete viewSquema['enlace'];
                if(menu == 'apartados') {
                    delete viewSquema['name'];
                }
                return viewSquema;
            },
            toggleVisible(page){
                var valor = parseInt(page[this.campo_visible]) ? 0 : 1;
                var num = page.num;
                this.$set(page,this.campo_visible,valor);
                let dataPost = {
                    method: 'POST',
                    body: JSON.stringify({cambia_check:1,num:num,campo:this.campo_visible,tabla:MENU,valor: valor}),
                    headers:{
                        'Content-Type': 'application/json'
                    }
                };
                fetch("lib/ajax.php",dataPost)
                    .then(res => res.json())
                    .then(json => {
                        if (json.error) {
                            console.log(json.error);
                            return false;
                        }
                        try{
                            this.$set(page,this.campo_visible,valor);
                        }catch(error){
                            console.log("Error en la función de la consulta : " + error);
                            return false;
                        }
                    })
                    .catch(function(error) {
                        console.log("Error en la consulta : ");
                        return false;
                    });
                
                return true;
            },
            eraseRecord(num){
                this.$emit("erase_record",num)
            }
        }
    }
</script>