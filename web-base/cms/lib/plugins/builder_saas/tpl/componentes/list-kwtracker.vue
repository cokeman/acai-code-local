<template>
    <div v-if="typeof pagedata.kwtracker !== 'undefined'" class="border-t">
        <div class="flex text-gray-600 items-center justify-between mt-4">
            <h4 class="text-base">Keyword Tracker : Mejores palabras clave</h4>  
        </div>
        <div class="relative cursor-pointer mt-4 p-4 bg-white border-2 border-transparent hover:border-green-400 rounded-lg shadow z-0 text-3xl" @click="window.open('?menu=keyword_tracker')">
            <div class="flex flex-wrap sm:flex-no-wrap sm:justify-between sm:items-end">
                <div class="w-full text-center flex justify-between">
                    <h2 class="text-center text-6xl w-32 m-0" :class="{'text-gray-400 border-gray-300':!pagedata.kwtracker.score || pagedata.kwtracker.score == 0,'text-red-500 border-red-300':pagedata.kwtracker.score <= 60 && pagedata.kwtracker > 0,'text-orange-500 border-orange-300':pagedata.kwtracker.score > 60 && pagedata.kwtracker <= 80,'text-green-500 border-green-300':pagedata.kwtracker.score > 80}">{{pagedata.kwtracker.score ? pagedata.kwtracker.score : 0}}</h2>

                    <ul class="w-full p-0 text-2xl text-gray-500 flex flex-wrap lg:justify-start items-center">
                        <li v-for="keyword of pagedata.kwtracker.data" class="w-full ml-4 text-left md:text-center mx-0 md:w-auto md:mx-8 md:my-2"><span class="w-12 text-center" :class="{'text-green-400' : keyword.latest.position < 10,'text-orange-400' : keyword.latest.position >=10 && keyword.latest.position < 20,'text-red-500' : keyword.latest.position >= 20}">{{keyword.latest.position}}</span><span class="px-4">{{keyword.keyword}}</span></li>
                    </ul>

                </div> 
            </div>
        </div>
    </div>
</template>
<script>
    module.exports = {
        props:["dato","paginas"],
        data(){
            return{
                pagedata:{}
            }
        },
        created(){
            
            this.pagedata = this.dato;
            this.pagedata.viewUpdate = this.viewUpdate;
            if (KWTRACKER && typeof this.pagedata.kwtracker === 'undefined'){
                this.pagedata.kwtracker = {score:0,data:[]};
            }
            if (KWTRACKER && !App.requestsSended["listKWTracker"]) {
                App.requestsSended["listKWTracker"] = true;
                
                this.downloadData('/admin.php?menu=keyword_tracker&getKeywords=1&path=',(json) => {
                    this.parseKWTrackerData(json);
                });
            }
        },
        methods:{
            viewUpdate() {this.$forceUpdate()},
            parseKWTrackerData(json){
                
                var tracker = json.data || [];
                console.log(this.paginas);
                
                for (const index of tracker){
                    if (index.latest && index.latest.url){
                        
                        var url = index.latest.url.replace(`https://${userDomain}`,``);
                        var page = this.searchLink(this.paginas,url,true);
                        
                        if (!page) { continue;}
                        if (!page.kwtracker) page.kwtracker = {score:10,data:[]};
                        if (page.kwtracker.data.length<=3){
                            page.kwtracker.data.push(index);
                        }
                        
                    }
                }
                for (page of this.paginas){
                    if (page.kwtracker){
                        var score = 0;
                        var data = page.kwtracker.data;
                        if (!data.length) continue;
                        for (dat of data){
                            score+=(100 - dat.latest.position);
                        }
                        score = parseInt(score/data.length);
                        page.kwtracker.score = score;
                        page.viewUpdate();
                    }
                }
                this.$emit("update-data",this.paginas.map(r => r.kwtracker));
            }
        }
    }
</script>