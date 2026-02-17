<link rel="stylesheet" href="/lib/plugins/builder_saas/css/layoutEditor.css">
<!--    MODAL EDITAR LAYOUT-->
<div id="modalLayout">
    <ul class="menu pl-0 bg-gray-900">
        <li v-on:click="preCierre()" class="hover:bg-gray-800 hover:text-gray-300">
            <i class="fa fa-times"></i>&nbsp;&nbsp;Cancelar
        </li>
        <li v-if="!preview && opcion.visible" v-for="opcion in opciones" class="hover:bg-gray-800 hover:text-gray-300" :class="{'active bg-gray-800 text-white':opcion.elementActive == activeElement}" v-on:click="activateTab(opcion.elementActive)">
            <i :class="'fa ' + opcion.icon"></i>&nbsp;&nbsp;{{opcion.label}}
        </li>
    </ul>
    <div class="viewport">
        <div v-for="opcion in opciones" :id="opcion.elementActive" v-if="opcion.elementActive" :class="{hidden:opcion.elementActive != activeElement}">
            <div class="p-4 absolute bottom-0 w-full z-20" v-if="hasController" @click="hasController = !hasController">
                <div class="p-4 text-white text-xl bg-red-600 rounded-lg cursor-pointer relative"><i class="fa fa-warning"></i>&nbsp;&nbsp;&nbsp;Esta sección dispone de un controlador propio, si genera un diseño de sección este se sustituirá por el nuevo diseño aplicado.<i class="fa fa-remove absolute top-0 right-0 p-4 text-2xl text-white"></i></div>    
            </div>
            
            <div class="editor" :class="{full:fullScreenCode}" v-if="codeModules.includes(opcion.elementActive)">
                <ace-editor  @keydown.ctrl.83.prevent="saveData(false)" @keydown.meta.83.prevent="saveData(false)" v-model="codeTable" theme="tomorrow_night" mode="html" tab-size="4" v-if="opcion.elementActive=='tableCodeModule'"></ace-editor>
                <ace-editor  @keydown.ctrl.83.prevent="saveData(false)" @keydown.meta.83.prevent="saveData(false)" v-model="styleTable" theme="tomorrow_night" mode="css" tab-size="4" v-if="opcion.elementActive=='tableStyleModule'"></ace-editor>
                <ace-editor  @keydown.ctrl.83.prevent="saveData(false)" @keydown.meta.83.prevent="saveData(false)" v-model="javascriptTable" theme="tomorrow_night" mode="javascript" tab-size="4" v-if="opcion.elementActive=='tableJsModule'"></ace-editor>
                <ace-editor  @keydown.ctrl.83.prevent="saveData(false)" @keydown.meta.83.prevent="saveData(false)" v-model="codeHeader" theme="tomorrow_night" mode="html" tab-size="4" v-if="opcion.elementActive=='headerModule'"></ace-editor>
                <ace-editor  @keydown.ctrl.83.prevent="saveData(false)" @keydown.meta.83.prevent="saveData(false)" v-model="codeFooter" theme="tomorrow_night" mode="html" tab-size="4" v-if="opcion.elementActive=='footerModule'"></ace-editor>
                <ace-editor  @keydown.ctrl.83.prevent="saveData(false)" @keydown.meta.83.prevent="saveData(false)" v-model="codeMantenimiento" theme="tomorrow_night" mode="html" tab-size="4" v-if="opcion.elementActive=='mantenimientoModule'"></ace-editor>
                <ace-editor  @keydown.ctrl.83.prevent="saveData(false)" @keydown.meta.83.prevent="saveData(false)" v-model="libraries" theme="tomorrow_night" mode="html" tab-size="4" v-if="opcion.elementActive=='librariesModule'"></ace-editor>
                <ace-editor  @keydown.ctrl.83.prevent="saveData(false)" @keydown.meta.83.prevent="saveData(false)" v-model="style" theme="tomorrow_night" mode="css" tab-size="4" v-if="opcion.elementActive=='styleModule'"></ace-editor>
                <ace-editor  @keydown.ctrl.83.prevent="saveData(false)" @keydown.meta.83.prevent="saveData(false)" v-model="javascript" theme="tomorrow_night" mode="javascript" tab-size="4" v-if="opcion.elementActive=='jsModule'"></ace-editor>
                <ul class="menu right pl-0">
                    <li :class="{active:fullScreenCode}">
                        <i class="fa fa-expand" v-on:click="fullScreenCode = !fullScreenCode"></i>
                        <span class="absolute top-0 right-0 w-64 p-6 mr-20 text-md text-right oculto bg-black z-10">Pantalla completa</span>
                    </li>
                    <li  class="relative" v-if="opcion.elementActive=='headerModule' || opcion.elementActive=='headerModule' || opcion.elementActive=='footerModule'">
                        <i class="fa fa-road" v-on:click="convertPaths(opcion.elementActive=='headerModule' ? codeHeader : codeFooter,opcion.elementActive)"></i>
                        <span class="absolute top-0 right-0 w-64 p-6 mr-20 text-md text-right oculto bg-black z-10">Convertir rutas</span>
                    </li>
                    <li  class="relative" v-if="opcion.elementActive=='headerModule' || opcion.elementActive=='footerModule' || opcion.elementActive=='tableCodeModule'">
                        <i class="fa fa-sun-o" v-on:click="beautify()"></i>
                        <span class="absolute top-0 right-0 w-64 p-6 mr-20 text-md text-right oculto bg-black z-10">Organizar código</span>
                    </li>
                </ul>
            </div>
            
            <docs-module v-if="opcion.elementActive=='docsModule'"></docs-module>

            <div id="filemanager" v-if="opcion.elementActive=='librariesModule2'" class="h-full flex flex-col bg-gray-300 overflow-auto">
                <div class="bg-gray-200 p-10 h-auto">
                    <div class="w-full mb-8 flex">
                        <input type="file" ref="fileLibrarie" @change="onFileChangeLibrarie" style="display:none"/>
                        <button class="bg-gray-500 mr-5 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded" @click="$refs.fileLibrarie[0].click()"><i class="fa fa-upload"></i>&nbsp;&nbsp;Subir librería</button>
                        <button class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded" v-on:click="addLibrarieURL"><i class="fa fa-globe"></i>&nbsp;&nbsp;Añadir URL</button>
                    </div>
                    <h3 class="text-2xl mt-8 mb-4">Librerías al comienzo de la página</h3>
                    <p class="mb-8">Puedes arrastrar las distintas librerías para ordenarlas</p>
                    <draggable class="file-list -mx-5 flex flex-wrap mh-40 " :list="librariesJSONt" group="librarie" >
                        
                        <div class="w-full lg:w-1/2 xl:w-1/3 mb-8 px-5 flex h-32"  v-for="element,index in librariesJSONt" :key="element.num">
                            <img class="h-32 w-32 flex-none bg-cover rounded-l text-center overflow-hidden object-cover object-center bg-gray-400 p-10" :class="{'bg-indigo-700':element.url.includes('.js') || element.url.includes('/js'),'bg-pink-600':element.url.includes('.css') || element.url.includes('/css')}" :src="getImageLibrarie(element.url)"/>
                            <div class="h-32 relative border-r w-full border-b border-l border-gray-400 bg-white rounded-r p-4 leading-normal overflow-x-hidden">
                                <div class="text-gray-900 font-bold text-xl mb-2">{{getNameLibrarie(element.url)}}</div>
                                <p class="text-gray-700 text-base truncate">{{element.url}}</p>
                                <i class="z-10 fa text-gray-300 absolute top-0 right-0 fa-remove p-2 cursor-pointer" v-on:click="removeLibrarie(element.num,'top')"></i>
                            </div>
                        </div>
                    </draggable>
                </div>
                <div class="bg-gray-300 p-10 h-auto" :class="{hidden:!librariesJSONt && !librariesJSONb}">
                    <h3 class="text-2xl mb-4">Librerías al final</h3>
                    <p class="mb-8">Puedes arrastrar las distintas librerías para ordenarlas</p>
                    <draggable class="file-list -mx-5 flex flex-wrap mh-40 " :list="librariesJSONb" group="librarie" >
                        <div class="w-full lg:w-1/2 xl:w-1/3 mb-8 px-5 flex h-32"  v-for="element,index in librariesJSONb" :key="element.num">
                            <img class="h-32 w-32 flex-none bg-cover rounded-l text-center overflow-hidden object-cover object-center bg-gray-400 p-10" :class="{'bg-indigo-700':element.url.includes('.js') || element.url.includes('/js'),'bg-pink-600':element.url.includes('.css') || element.url.includes('/css')}" :src="getImageLibrarie(element.url)"/>
                            <div class="h-32 relative border-r w-full border-b border-l border-gray-400 bg-white rounded-r p-4 leading-normal overflow-x-hidden">
                                <div class="text-gray-900 font-bold text-xl mb-2">{{getNameLibrarie(element.url)}}</div>
                                <p class="text-gray-700 text-base truncate">{{element.url}}</p>
                                <i class="z-10 fa text-gray-300 absolute top-0 right-0 fa-remove p-2 cursor-pointer" v-on:click="removeLibrarie(element.num,'bottom')"></i>
                            </div>
                        </div>
                    </draggable>
                    
                </div>
            </div>

            <iframe v-if="opcion.elementActive=='tailModule'" :src="opened ? iframeDocTail : ''"></iframe>
            <iframe v-if="opcion.elementActive=='tailRunModule'" :src="opened ? iframeDocTailRun : ''"></iframe>
            
            <iframe id="iframePaintEl" v-if="opcion.elementActive=='paintMode'" :src="opened ? iframePaintSrc : ''"></iframe>

            <div class="savePage" v-if="opcion.elementActive=='saveCreateModule'">
                <h3 class="font-normal text-3xl mb-8">Recuerda añadir estas directivas en tu .htaccess</h3>
                <pre class="bg-gray-400 border border-gray-400 text-gray-800 p-4">&lt;IfModule mod_headers.c&gt;
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Headers "Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"
&lt;/IfModule&gt;

RewriteRule ^custom-builder-style.css cms/lib/plugins/builder_saas/replace_code.php?getStyle=1 [L]
RewriteRule ^custom-builder-javascript.js cms/lib/plugins/builder_saas/replace_code.php?getJavascript=1 [L]</pre>
                <div class="mt-8">
                    <a class="btn btn-primary py-2 px-4" @click="saveData()">Guardar</a>
                    <a class="btn btn-danger py-2 px-4" @click="preCierre()">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!--   FIN MODAL EDITAR LAYOUT-->

