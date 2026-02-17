<template>
    <div>
        <button type="button" @click="openGallery" class="hidden">Biblioteca de recursos</button>
        <ul class="rounded vue-uploads-wrapper overflow-auto" :id="id">
            <li v-if="!images || !images.length" class="relative">
                <div class="w-full flex justify-between text-gray-700 mb-1 px-4 ">

                    <input type="file" multiple ref="file" style="display:none;" @change="addFile">
                    <div @click.prevent="$refs.file.click()" class="relative w-full h-32 flex-shrink-0 text-gray-500 cursor-pointer flex items-center justify-center" @drop.prevent="addFile" @dragleave.prevent="hover = false" @dragover.prevent="hover = true" :class="{'bg-gray-400' : hover, 'bg-transparent' : !hover}">
                        Haz clic para añadir un archivo...
                    </div>
                </div>
            </li>
            <draggable v-model="images" @end="sortList">
                <li v-for="image of images" v-if="image.num" class="relative">
                    <div class="w-full border border-gray-400 p-1 border-dashed rounded-lg flex justify-between items-center text-gray-700 my-1 pr-4" @drop.prevent="addFile" @dragleave.prevent="hover = false" @dragover.prevent="hover = true" :class="{'bg-gray-400' : hover, 'bg-white' : !hover}">
                        <div class="relative w-20 h-20 flex-shrink-0 cursor-pointer rounded-lg overflow-hidden">
                            <img v-if="isImage(image)" :src="image.urlPath" :class="background_class ? background_class : 'bg-gray-100' " class=" border-4 border-white rounded shadow absolute top-0 left-0 w-full h-full object-contain object-center cursor-pointer" @click="viewPhoto(image)">
                            <a v-if="!isImage(image)" @click="window.open(image.urlPath)" class="absolute top-0 left-0 w-full h-full object-contain object-center cursor-pointer flex items-center justify-center text-gray-600 text-xs" target="_blank">Descargar</a>
                        </div>
                        <ul class="relative w-full items-center text-sm p-4 truncate">
                            <li v-if="field.infoLabels && field.infoLabels[0]" class="truncate">{{field.infoLabels[0]}} : {{image.info1}}</li>
                            <li v-if="field.infoLabels && field.infoLabels[1]" class="truncate">{{field.infoLabels[1]}} : {{image.info2}}</li>
                            <li v-if="field.infoLabels && field.infoLabels[2]" class="truncate">{{field.infoLabels[2]}} : {{image.info3}}</li>
                            <li v-if="field.infoLabels && field.infoLabels[3]" class="truncate">{{field.infoLabels[3]}} : {{image.info4}}</li>
                            <li v-if="field.infoLabels && field.infoLabels[4]" class="truncate">{{field.infoLabels[4]}} : {{image.info5}}</li>
                            
                        </ul>
                        <ul class="relative flex justify-end flex-shrink-0">
                            <li class="p-2 h-full flex items-center justify-center"><a @click="modifyLink(image)" class="text-gray-700"><i class="fa fa-pencil"></i></a></li>
                            <li class="p-2 h-full flex items-center justify-center"><a @click="removeLink(image)" class="text-gray-700"><i class="fa fa-remove"></i></a></li>
                            <li v-if="idiomas && Object.keys(idiomas).length > 1" class="p-2 h-full flex items-center justify-center"><a @click="idiomaLink(image)" class="vue-upload-thickbox text-gray-500"><i class="fa fa-globe"></i></a></li>
                            <li v-if="images && images.length > 1" class="p-2 h-full flex items-center justify-center cursor-pointer">
                                <span class="text-gray-400"><i class="fa fa-sort"></i></span>
                            </li>
                        </ul>
                    </div>
                </li>
            </draggable>

            <button v-if="add_button" @click="$emit('add_button_click')" class="bg-transparent hover:bg-white hover:text-black mb-1 px-2 border border-gray-400 py-1 text-gray-600 text-sm rounded-lg open-modal flex justify-between items-center" @click="openModal(tablename,fieldname,recordnum,presavetempid,id)">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 flex-shrink-0 mr-2 stroke-current icon icon-tabler icon-tabler-brand-facebook" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.2"  fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1" />
                    <polyline points="9 15 12 12 15 15" />
                    <line x1="12" y1="12" x2="12" y2="21" />
                </svg>
                <span class="w-full">{{ add_button_text ? add_button_text : 'Subir imagen' }}</span>
            </button>
        </ul>
    </div>
</template>
<script>
    module.exports = {
        props:['tablename','fieldname','recordnum','presavetempid','field','builder_field','root_builder_vue','add_button','add_button_text', 'add_button_click','reference','background_class'],
        components: {
            vuedraggable
        },
        data(){
            return{
                images:[],
                idiomas:IDIOMAS,
                referencia:'',
                record:{},
                id:'',
                imageExtensions:["jpg","png","gif","jpeg","svg"],
                field:null,
                drag:false,
                files:[],
                hover:false
            }
        },
        created(){
            this.id = this.makeid(5);
            this.images = this.field.value;
            this.field = this.field;
            this.referencia = this.reference ? this.reference : 'upload_imagen_' + this.makeid(5);

            this.field.infoLabels = this.builder_field.infoLabels;

            this.record = {
                tableName:this.tablename,
                fieldName:this.fieldname,
                recordNum:this.recordnum,
                type:'upload',
                preSaveTempId:this.presavetempid,
                infoLabels:this.field && this.field.infoLabels ? JSON.stringify(this.field.infoLabels) : ""
            };

            this.reloadData();
        },
        mounted() {
            this.$on("refresh_builder_preview", (data) => {
                console.log(data);
            });
        },
        methods:{
            openGallery () {
                _openModal(`/lib/menus/modals/cutefile/index.php?menu=builder_custom&fieldName=${this.fieldname}&recordNum=${this.recordnum}&action=gallery_plugin&callbackEvent=refresh_builder_preview&builder_ref=${this.referencia}`);
            },
            addFile(e) {
                this.hover = true;
                let droppedFiles = e.target.type == 'file' ? e.target.files : e.dataTransfer.files;
                if (!droppedFiles) return;
                ([...droppedFiles]).forEach(async f => {
                    this.files.push(f);
                    this.upload(f);
                });

            },
            upload(f) {
                let formData = new FormData();
                formData.append('file', f);
                let url = `/lib/menus/modals/plupload/multiupload/upload.php?menu=${this.record.tableName}&fieldName=${this.record.fieldName}`;

                if (this.record.recordNum) {
                    url+=`&num=${this.record.recordNum}&preSaveTempId=${this.record.preSaveTempId}`;
                } else if (this.record.preSaveTempId) {
                    url+=`&preSaveTempId=${this.record.preSaveTempId}`;
                }

                fetch(url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(res => {
                        console.log('done uploading', res);
                        this.reloadData();
                        this.hover = false;
                    })
                    .catch(e => {
                        console.error(JSON.stringify(e.message));
                        this.hover = false;
                    });

            },
            updateData(data){
                this.images = data;
            },
            viewPhoto(image){
                var viewPhotoNode = document.querySelector(".vue-upload-photo");
                if (viewPhotoNode) viewPhotoNode.parentNode.removeChild(viewPhotoNode);
                viewPhotoNode = document.createElement("div");
                viewPhotoNode.classList.add("vue-upload-photo","fixed","top-0","left-0","w-full","h-full","z-5000","flex","items-center","justify-center");
                viewPhotoNode.innerHTML = `
                    <div class="overlay absolute top-0 left-0 w-full h-full bg-black opacity-75 z-0"></div>
                    <div class="relative z-10 p-12 rounded"><img src="${image.urlPath}" class="h-screen max-w-6xl object-contain"></div>
                `;
                viewPhotoNode.addEventListener("click",function(e){ viewPhotoNode.parentNode.removeChild(viewPhotoNode); })
                document.body.appendChild(viewPhotoNode);
            },
            async sortList(){
                const requests = [];
                for (const orderIndex in this.images){
                    this.images[orderIndex].order = orderIndex;
                    requests.push(Rest.update(`uploads`, {order:parseInt(orderIndex)}, {num:parseInt(this.images[orderIndex].num)},{ignoreSchema:true}));
                }
                await Promise.all(requests);
                this.hover = false;
            },
            idiomaLink(image){
                tb_show("Translate Upload Field", `?menu=${this.record.tableName}&action=translateModify&fieldName=${this.record.fieldName}&num=${this.record.recordNum ? this.record.recordNum : ''}&preSaveTempId=${this.record.preSaveTempId}&uploadNum=${image.num}&type=${this.record.type}&TB_iframe=true&width=900&height=600&modal=true`);
            },
            modifyLink(image){
                console.log(this.record);
                tb_show("Modify Upload Field", `?menu=${this.record.tableName}&action=uploadModify&fieldName=${this.record.fieldName}&num=${this.record.recordNum ? this.record.recordNum : ''}&infoLabels=${this.record.infoLabels}&preSaveTempId=${this.record.preSaveTempId}&uploadNums=${image.num}&callbackEvent=refresh_builder_preview&builder_ref=${this.referencia}&TB_iframe=true&width=900&height=600&modal=true`);

                /* 
                callbackEvent=refresh_builder_preview&builder_fieldname=${this.referencia}
                */

            },
            isImage(image){
                const extension = image.urlPath.substring(image.urlPath.lastIndexOf(".") + 1).toLowerCase();
                return this.imageExtensions.indexOf(extension) > -1;
            },
            async reloadData(force){
                console.log(`Reload Image Data ( ${this.record && this.record.recordNum ? this.record.recordNum : 'no def.'} )`);
                let where = ``;
                if (this.record && this.record.recordNum){
                    where = `tableName='${this.record.tableName}' and fieldName='${this.record.fieldName}' and recordNum=${this.record.recordNum}`;
                }else if (this.record && this.record.preSaveTempId){
                    where = `tableName='${this.record.tableName}' and fieldName='${this.record.fieldName}' and preSaveTempId='${this.record.preSaveTempId}'`;
                }else{
                    return;
                }

                const tableName = `uploads`;
                Rest.deleteCache(tableName,where);
                const response = await Rest.get(tableName,where,'`order` ASC');
                if (!response.data) {
                    swal('Un momento', 'Ha ocurrido un error al eliminar la imagen', 'warning');
                    return;
                }
                this.images = response.data.map((rec) => { rec.urlPath = `https://${CURRENT_USER.domain.domain}${rec.urlPath.replace(`https://${CURRENT_USER.domain.domain}`,``)}`; return rec; });
                this.files = [];
                this.$forceUpdate();
            },
            removeLink(image){
                swal({
                    title:"Seguro?",
                    text:"Estás seguro de eliminar el registro?",
                    icon:"warning",
                    buttons:{
                      cancel: {
                        text: "No disculpa",
                        value: null,
                        visible: true,
                        className: "btn btn-default",
                        closeModal: true,
                      },
                      confirm: {
                        text: "Si, seguro",
                        value: true,
                        visible: true,
                        className: "btn btn-primary",
                        closeModal: true
                      }
                    }
                }).then(async (value) => {
                    if (value){
                        const response = await Rest.delete(`uploads`,`num=${image.num}`);
                        if (!response.data) {
                            swal('Un momento', 'Ha ocurrido un error al eliminar la imagen', 'warning');
                            return;
                        }else{

                            swal({
                                title:"OK!",
                                text:"La imagen ha sido eliminada",
                                icon:"success",
                                button : false,
                                timer:500
                            });
                        }
                        this.images.splice(this.images.indexOf(image),1);
                        this.files = [];
                    }
                });

            },
            makeid(length){
                var result           = '';
                var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                var charactersLength = characters.length;
                for ( var i = 0; i < length; i++ ) {
                  result += characters.charAt(Math.floor(Math.random() * charactersLength));
               }
               return result;
            }
        }
    };
</script>
