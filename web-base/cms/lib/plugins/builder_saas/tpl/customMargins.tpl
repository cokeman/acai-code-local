<div id="marginEditor" ref="marginEditor" class="hidden fixed top-0 bottom-0 h-full bg-gray-900 overflow-auto z-10 w-full" :class="{'showed':showed}">
   <div class="bg-black opacity-25 absolute top-0 left-0 w-full h-full"></div>
    <ul class="toolBar relative">
        <li><a href="javascript:void(0);" @click="toggle()"><i class="fa fa-chevron-up"></i>&nbsp;&nbsp;Volver</a></li>
    </ul>
    <div class="relative container mx-auto max-w-3xl px-4">
        <div class="flex flex-start items-center mt-12 lg:mt-20" v-if="selectedModule">
            <img :src="thumbnail" class="w-20 h-20 object-cover rounded-lg flex-shrink-0 mr-8 shadow-lg">
            <div>
                <h3 class="text-xl text-white font-bold" >${selectedModule.builder.label | parseName }</h3>
                <p class="text-gray-600 font-normal mt-2" >Editor de márgenes del módulo</p>
            </div>
        </div>
        <ul v-if="!loading" class="flex items-center justify-between -mx-4 mt-4 lg:mt-8">
            <li class="px-4 w-full"><button @click="reset()" class="py-2 px-6 border border-gray-700 hover:border-white text-white font-semibold block tw w-full bg-transparent rounded-lg"><i class="fa fa-remove"></i>&nbsp;&nbsp;Reiniciar</button></li>
            <li class="px-4 w-full"><button @click="copy()" class="py-2 px-6 border border-gray-700 hover:border-white text-white font-semibold block tw w-full bg-transparent rounded-lg"><i class="fa fa-clone"></i>&nbsp;&nbsp;Copiar</button></li>
            <li class="px-4 w-full"><button @click="paste()" class="py-2 px-6 border border-gray-700 hover:border-white text-white font-semibold block tw w-full bg-transparent rounded-lg"><i class="fa fa-clipboard"></i>&nbsp;&nbsp;Pegar</button></li>
        </ul>
        <ul v-if="!loading" class="flex flex-row-reverse justify-center mt-10 pt-10 border-t border-gray-700 border-dashed">
            <li class="w-32 lg:w-64 flex items-center flex-col">
                <svg xmlns="http://www.w3.org/2000/svg" class="mb-4 text-white w-10 h-10 stroke-current" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ffffff" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <rect x="3" y="4" width="18" height="12" rx="1" />
                  <line x1="7" y1="20" x2="17" y2="20" />
                  <line x1="9" y1="16" x2="9" y2="20" />
                  <line x1="15" y1="16" x2="15" y2="20" />
                </svg>

                <div class="w-full bg-gray-800 h-8 relative rounded-lg text-sm text-white text-center mb-2">
                    <select @change="saveSchema" v-model="selection.desktop.sup" class="bg-gray-800 border border-gray-900 appearance-none absolute rounded-lg text-center top-0 left-0 w-full h-full">
                        <option v-for="option in options" :value="option.value ? 'lg:' + option.value : ''">${option.label}</option>
                    </select>
                </div>
                
                <div class="w-full bg-gray-900 rounded-lg text-sm text-white text-center h-32 border-dashed border-2 border-gray-700 relative p-1">
                    <div class="relative h-full flex justify-around flex-col">
                        <div class="absolute top-0 left-0 w-full rounded-t opacity-50" :class="[selection.desktop.sup.includes('-mt') ? 'bg-red-500' : 'bg-green-500']" :style="'height:' + getPercent(selection.desktop.sup) + '%'"></div>
                        <div class="absolute bottom-0 left-0 w-full rounded-b opacity-50" :class="[selection.desktop.inf.includes('-mt') ? 'bg-red-500' : 'bg-green-500']" :style="'height:' + getPercent(selection.desktop.inf) + '%'"></div>
                    </div>
                </div>
                
                <div class="w-full bg-gray-800 relative rounded-lg h-8 text-sm text-white text-center mt-2">
                    <select @change="saveSchema" v-model="selection.desktop.inf" class="bg-gray-800 border border-gray-900 appearance-none absolute rounded-lg text-center top-0 left-0 w-full h-full">
                        <option v-for="option in options" :value="option.value ? 'lg:' + option.value : ''">${option.label}</option>
                    </select>
                </div>

            </li>
            <li class="w-4"></li>
            <li class="w-32 lg:w-64 flex items-center flex-col">
                <svg xmlns="http://www.w3.org/2000/svg" class="mb-4 text-white w-10 h-10 stroke-current" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ffffff" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <rect x="7" y="4" width="10" height="16" rx="1" />
                  <line x1="11" y1="5" x2="13" y2="5" />
                  <line x1="12" y1="17" x2="12" y2="17.01" />
                </svg>

                <div class="w-full bg-gray-800 h-8 relative rounded-lg text-sm text-white text-center mb-2">
                    <select @change="saveSchema" v-model="selection.mobile.sup" class="bg-gray-800 border border-gray-900 appearance-none absolute rounded-lg text-center top-0 left-0 w-full h-full">
                        <option v-for="option in options" :value="option.value">${option.label}</option>
                    </select>
                </div>
                
                <div class="w-full bg-gray-900 rounded-lg text-sm text-white text-center h-32 border-dashed border-2 border-gray-700 relative p-1">
                    <div class="relative h-full flex justify-around flex-col">
                        <div class="absolute top-0 left-0 w-full rounded-t opacity-50" :class="[selection.mobile.sup.includes('-mt') ? 'bg-red-500' : 'bg-green-500']" :style="'height:' + getPercent(selection.mobile.sup) + '%'"></div>
                        <div class="absolute bottom-0 left-0 w-full rounded-b opacity-50" :class="[selection.mobile.inf.includes('-mt') ? 'bg-red-500' : 'bg-green-500']" :style="'height:' + getPercent(selection.mobile.inf) + '%'"></div>
                    </div>
                </div>
                
                <div class="w-full bg-gray-800 relative rounded-lg h-8 text-sm text-white text-center mt-2">
                    <select @change="saveSchema" v-model="selection.mobile.inf" class="bg-gray-800 border border-gray-900 appearance-none absolute rounded-lg text-center top-0 left-0 w-full h-full">
                        <option v-for="option in options" :value="option.value">${option.label}</option>
                    </select>
                </div>
            </li>
        </ul>
        
        <input type="hidden" id="testing-code-margin" :value="marginSchema | parseJson">
        <p v-if="loading">Cargando márgenes</p>
        <p v-if="nonum">Para poder editar los márgenes primero debes guardar el registro.</p>
    </div>
</div>
