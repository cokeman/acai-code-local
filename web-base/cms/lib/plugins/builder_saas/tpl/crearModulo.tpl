<link rel="stylesheet" href="<?=$options["templatePath"];?>/css/crearModulo.css?<?=time();?>">
<!--    MODAL CREAR MODULO-->
<div id="modalCrearModulo">
    <ul class="menu">
        <li>
            <i class="fa fa-times" v-on:click="cerrarCrearModulo()"></i>
        </li>
        <li v-if="!preview" v-for="opcion in opciones" :class="{active:opcion.elementActive == activeElement,hidden:opcion.elementActive == 'paintMode' && opcion.clase == 'hidden'}">
            <i :class="'fa ' + opcion.icon" v-on:click="activateTab(opcion.elementActive)"></i>
        </li>
    </ul>
    <div class="viewport">
        <div v-for="opcion in opciones" :id="opcion.elementActive" v-if="opcion.elementActive" :class="{hidden:opcion.elementActive != activeElement}">
            <div class="editor" :class="{full:fullScreenCode}" v-if="opcion.elementActive=='cssModule'">
                <ace-editor  v-model="code" theme="tomorrow_night" mode="html" tab-size="4"></ace-editor>
                <ul class="menu right">
                    <li class="relative" >
                        <i class="fa fa-cloud-download" v-on:click="extractCode"></i>
                        <span class="absolute top-0 right-0 w-64 p-6 mr-20 text-md text-right oculto bg-black z-10">Extraer landing</span>
                    </li>
                    <li  class="relative" v-if="opcion.elementActive=='cssModule'">
                        <i class="fa fa-road" v-on:click="convertPaths(code)"></i>
                        <span class="absolute top-0 right-0 w-64 p-6 mr-20 text-md text-right oculto bg-black z-10">Convertir rutas</span>
                    </li>
                    <li  class="relative" v-if="opcion.elementActive=='cssModule'">
                        <i class="fa fa-sun-o" v-on:click="beautify()"></i>
                        <span class="absolute top-0 right-0 w-64 p-6 mr-20 text-md text-right oculto bg-black z-10">Organizar código</span>
                    </li>
                    <li  class="relative" v-if="opcion.elementActive=='cssModule'">
                        <i class="fa fa-eye" v-on:click="iframeExternal()"></i>
                        <span class="absolute top-0 right-0 w-64 p-6 mr-20 text-md text-right oculto bg-black z-10">Ver web</span>
                    </li>
                </ul>
            </div>
            
            <? include(__DIR__."/componentes.tpl");?>

            <iframe v-if="opcion.elementActive=='tailModule'" :src="opened ? iframeDocTail : ''"></iframe>
            
            <div class="editorCSS full" v-if="opcion.elementActive=='styleModule'">
                <ace-editor  v-model="style" theme="tomorrow_night" mode="css" tab-size="4"></ace-editor>
            </div>
            
            <div class="editorCSS full" v-if="opcion.elementActive=='jsModule'">
                <ace-editor  v-model="javascript" theme="tomorrow_night" mode="javascript" tab-size="4"></ace-editor>
            </div>
            
            <iframe id="iframePaintEl" v-if="opcion.elementActive=='paintMode'" :src="opened ? iframePaintSrc : ''"></iframe>

            <div class="savePage" v-if="opcion.elementActive=='saveCreateModule'">
                <h3>{{editMode ? 'Editar' : 'Guardar'}} módulo</h3>
                <div class="separa-40"></div>
                <input type="file" ref="fileAvatar" @change="onFileChangeAvatar" style="display:none"/>
                <img :src="urlAvatar" @click="$refs.fileAvatar[0].click()" class="avatar">
                <div class="separa-40"></div>
                <button class="btn btn-default" @click="downloadImage">Capturar imagen del módulo</button>
                <div class="separa-20"></div>

                <div class="form-group">
                    <label class="sr-only">Identificador del módulo</label>
                    <div class="input-group">
                        <div class="input-group-addon">Identificador del módulo</div>
                        <input class="form-control" v-model="moduleId" v-bind:readonly="modulo" minlength="5" maxlength="40" @keyup="validateId" pattern="[A-Za-z0-9]+" placeholder="Identificador">
                    </div>
                </div>

                <div class="form-group">
                    <label class="sr-only">Nombre del módulo</label>
                    <div class="input-group">
                        <div class="input-group-addon">Nombre del módulo</div>
                        <input class="form-control" v-model="moduleLabel" placeholder="Nombre del módulo">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="sr-only">Descripción del módulo</label>
                    <div class="input-group">
                        <div class="input-group-addon">Descripción del módulo</div>
                        <input class="form-control" v-model="moduleDesc" placeholder="Descripción del módulo">
                    </div>
                </div>
                
                <a class="btn btn-primary" @click="uploadModule()">Guardar</a>
                <a class="btn btn-danger" @click="cerrarCrearModulo()">Cancelar</a>
                <a class="btn btn-danger pull-right" :class="{hidden : !sePuedeEliminar}" @click="deleteModule">Eliminar</a>
                <a class="btn btn-default pull-right" :class="{hidden : sePuedeEliminar}" @click="swal('Ups!','Este módulo no se puede eliminar actualmente, puede que lo estés utilizando','warning')">Eliminar</a>
                <div class="separa-40"></div>
            </div>
        </div>
    </div>
</div>
<!--   FIN MODAL CREAR MODULO-->

<template id="defaultTextCrear">
   
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-semibold mb-6 mt-6 text-gray-800">Bienvenido al editor de código</h1>
        <p class="mb-3 text-gray-600 text-xl">Desde esta sección podrás crear tus propios módulos e insertarlos en tu web directamente. Simplemente elimina este código y comienza a crear tu nuevo módulo.</p>
        <p class="text-gray-600 text-xl">A tu izquierda podrás visualizar la documentación de TailWindCSS. Un framework que permite trabajar sin tener que preocuparte por las hojas de estilos, todo a través de clases predefinidas.</p>
        <p class="text-gray-600 mt-5 text-xl">Podrás crear layout basados en GRID...</p>
        <!-- Three columns -->
        <div class="flex mb-4 mt-4">
            <div class="w-1/3 bg-gray-400 h-12"></div>
            <div class="w-1/3 bg-gray-500 h-12"></div>
            <div class="w-1/3 bg-gray-400 h-12"></div>
        </div>

        <p class="text-gray-600 mt-6 text-xl">O simples elementos tipo Card, entre otros...</p>
		<div class="flex flex-wrap -mx-2">
			<div class="px-2 w-1/3">
				<div class="rounded overflow-hidden shadow-lg mt-8">
		            <img class="w-full" src="https://www.tailwindcss.com/img/card-top.jpg" alt="Sunset in the mountains">
		            <div class="px-6 py-4">
		                <div class="font-bold text-xl mb-2">The Coldest Sunset</div>
		                <p class="text-gray-700 text-base">
		                    Lorem ipsum dolor sit amet, consectetur adipisicing elit. Voluptatibus quia, nulla! Maiores et perferendis eaque, exercitationem praesentium nihil.
		                </p>
		            </div>
		            <div class="px-6 py-4">
		                <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">#photography</span>
		                <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">#travel</span>
		                <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700">#winter</span>
		            </div>
		        </div>
			</div>
			
		</div>
        

        <div class="mt-10"></div>
    </div>

    
</template>