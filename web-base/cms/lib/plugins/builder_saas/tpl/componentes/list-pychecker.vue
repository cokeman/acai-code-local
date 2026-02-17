<template>
    <div v-if="typeof pagedata.pychecker !== 'undefined'">
        <div class="flex text-gray-600 items-center justify-between mt-8 px-4" :class="{'ml-12':pagedata.children && pagedata.children.length}">
            <h4>PyChecker : Auditoría técnica web</h4>  
            <a href="?menu=pychecker">Ver más</a>
        </div>
        <div class="relative cursor-pointer mt-4 p-4 bg-white rounded-lg shadow z-0 text-3xl" :class="{'ml-12':pagedata.children && pagedata.children.length,'loadingData opacity-25' : App.requestsLoaded.listPychecker !== true}">
            <div class="flex flex-wrap sm:flex-no-wrap sm:justify-between sm:items-end">
                <div class="w-full text-center flex justify-between">
                    <h2 class="text-center text-6xl w-32 m-0" :class="{'text-gray-400 border-gray-300':!pagedata.pychecker.score || pagedata.pychecker.score == 0,'text-red-500 border-red-300':pagedata.pychecker.score <= 60 && pagedata.pychecker.score > 0,'text-orange-500 border-orange-300':pagedata.pychecker.score > 60 && pagedata.pychecker.score <= 80,'text-green-500 border-green-300':pagedata.pychecker.score > 80}">{{pagedata.pychecker.score ? pagedata.pychecker.score : 0}}</h2>
                    <ul class="w-full p-0 text-gray-500 flex flex-wrap lg:justify-between text-2xl items-center">
                        <li class="w-full ml-4 text-left md:text-center mx-0 md:w-auto md:mx-8 md:my-2"><i class="fa fa-check text-green-500 inline-block text-center"></i> {{pagedata.pychecker.valid ? pagedata.pychecker.valid : 0}} tests válidos</li>
                        <li class="w-full ml-4 text-left md:text-center mx-0 md:w-auto md:mx-8 md:my-2"><i class="fa fa-warning text-yellow-500 inline-block text-center"></i> {{pagedata.pychecker.warning ? pagedata.pychecker.warning : 0}} tests con warning</li>
                        <li class="w-full ml-4 text-left md:text-center mx-0 md:w-auto md:mx-8 md:my-2"><i class="fa fa-times text-red-500 inline-block text-center"></i> {{pagedata.pychecker.error ? pagedata.pychecker.error : 0}} tests erróneos</li>
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
            
            if (PYCHECKER && !App.requestsSended["listPychecker"]) {
                App.requestsSended["listPychecker"] = true;
                this.downloadData('/admin.php?menu=pychecker&action=history&get=',(json) => {
                    var history = json.data || [];
                    App.requestsLoaded["listPychecker"] = true;
                    for (const index of history){
                        var page = this.searchLink(this.paginas,index.path);
                        if (!page) { continue;}
                        if (index.website && !page.pychecker) {
                            var report = this.generate_resume(index.report);
                            
                            page.pychecker = {score:parseInt(report.score),valid:report.success,warning:report.warning,error:report.error};

                        }
                    }
                });
                
            }
            
        },
        methods:{
            generate_resume: function (report) {
                const resume = {
                    success: 0,
                    warning: 0,
                    error: 0,
                    info: 0,
                    total: 0,
                    score: 0
                };
                for (const data of Object.values(report)) {
                    for (const report_data of Object.values(data)) {
                        resume[report_data.type] += 1;
                        switch (report_data.type) {
                            case 'success':
                                resume.total += 1;
                                resume.score += 1;
                                break;
                            case 'warning':
                                resume.total += 1;
                                resume.score += 0.5;
                                break;
                            case 'error':
                                resume.total += 1;
                            default:
                                break;
                        }
                    }
                }
                resume.score = parseInt(Math.ceil(resume.score / resume.total * 100));
                return resume;
            },
            requestPychecker(page){
                fetch('/admin.php?menu=pychecker&get=' + encodeURI(page.enlace))
                    .then(res => res.json())
                    .then(json => {
                    if (json.error) {
                        alert(json.error);
                        return false;
                    }
                    Swal.fire("Ok","Los datos han sido enviados a PyChecker para su análisis.","success");
                });
            }
        }
    }
</script>