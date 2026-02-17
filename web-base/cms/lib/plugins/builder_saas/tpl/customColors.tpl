<div id="colorEditor" ref="colorEditor" class="hidden fixed top-0 bottom-0 h-full bg-gray-900 overflow-auto z-10" :class="{'showed':showed}">
   <div class="bg-black opacity-25 absolute top-0 left-0 w-full h-full"></div>
    <ul class="toolBar relative">
        <li><a href="javascript:void(0);" @click="toggle()"><i class="fa fa-chevron-up"></i>&nbsp;&nbsp;Volver</a></li>
    </ul>
    <div class="relative container mx-auto max-w-3xl px-4">
        <div class="flex flex-start items-center mt-12 lg:mt-20" v-if="selectedModule">
            <img :src="thumbnail" class="w-20 h-20 object-cover rounded-lg flex-shrink-0 mr-8 shadow-lg">
            <div>
                <h3 class="text-xl text-white font-bold" >${selectedModule.builder.label | parseName }</h3>
                <p class="text-gray-600 font-normal mt-2" >Editor de colores del módulo</p>
            </div>
        </div>
        <ul v-if="!loading" class="flex items-center justify-between -mx-4 mt-4 mb-12 lg:mt-8 lg:mb-20">
            <li class="px-4 w-full"><button @click="reset()" class="py-2 px-6 border border-gray-700 hover:border-white text-white font-semibold block tw w-full bg-transparent rounded-lg"><i class="fa fa-remove"></i>&nbsp;&nbsp;Reiniciar</button></li>
            <li class="px-4 w-full"><button @click="copy()" class="py-2 px-6 border border-gray-700 hover:border-white text-white font-semibold block tw w-full bg-transparent rounded-lg"><i class="fa fa-clone"></i>&nbsp;&nbsp;Copiar</button></li>
            <li class="px-4 w-full"><button @click="paste()" class="py-2 px-6 border border-gray-700 hover:border-white text-white font-semibold block tw w-full bg-transparent rounded-lg"><i class="fa fa-clipboard"></i>&nbsp;&nbsp;Pegar</button></li>
            <li class="px-4 w-full"><button @click="selectColor(2000)" class="py-2 px-6 border border-gray-700 hover:border-white text-white font-semibold flex items-center justify-center tw w-full bg-transparent rounded-lg"><i class="w-4 h-4 bg-red-400 rounded-full inline-block"></i>&nbsp;&nbsp;Tono</button></li>
        </ul>
        <ul v-if="!loading">
            <li v-for="(key2,key1) in tailWindColorsComp" class="flex lg:justify-between items-center mb-4">
                <span :class="getBgColor(key1)" class="w-8 h-8 rounded-full block tw shadow-lg flex-shrink-0"></span>
                <input type="text" readonly="true" :placeholder="key1" :value="key1" class="py-2 px-4 appearance-none bg-black opacity-50 placeholder-gray-800 text-white font-normal border border-gray-700 shadow-lg w-full max-w-lg mx-4 rounded-full">
                <div class="w-full hidden lg:block border-b border-gray-700 border-dashed"></div>
                <input type="text" readonly="true" :placeholder="key1" :value="key2" class="py-2 px-4 appearance-none bg-black opacity-50 placeholder-gray-800 text-white font-normal border border-gray-700 shadow-lg w-full max-w-lg mx-4 rounded-full">
                <span @click="selectColor(key1)" class="w-8 h-8 flex-shrink-0 flex justify-center items-center"><span :class="getBgColor(getColorClass(key2,key1))" class="hover:scale-125 border border-white border-dashed hover:opacity-100 cursor-pointer transition-3s rounded-full block tw shadow-lg"></span></span>
            </li>
        </ul>
        <input type="hidden" id="testing-code" :value="colorSchema | parseJson">
        <p v-if="loading">Cargando colores</p>
        <p v-if="nonum">Para poder editar los colores primero debes guardar el registro.</p>
    </div>
    <div v-if="!loading" class="mt-10 py-10 relative">
        <div class="bg-black opacity-25 absolute top-0 left-0 w-full h-full"></div>
        <div class="container mx-auto max-w-3xl relative px-4">
            <ul>
                <li v-for="(key2,key1) in customColorsComp"  class="flex justify-between items-center mb-4">
                    <input type="text" readonly="true" ref="customKeyValue" @change="changeCustomValue(key1)" placeholder="bg-yellow-500" :value="key1" class="py-2 px-4 appearance-non bg-black opacity-50 placeholder-gray-800 text-white font-normal border border-gray-700 shadow-lg w-full max-w-lg mr-4 rounded-full">
                    <div class="w-full hidden lg:block border-b border-gray-700 border-dashed"></div>
                    <input type="text" readonly="true" placeholder="bg-yellow-500" :value="key2" class="py-2 px-4 appearance-none bg-black opacity-50 placeholder-gray-800 text-white font-normal border border-gray-700 shadow-lg w-full max-w-lg mx-4 rounded-full">
                    <span @click="selectColor(key1)" :class="key2 ? getBgColor(key2) : key1" class="border border-dashed border-white hover:scale-125 hover:opacity-100 cursor-pointer  transition-3s w-8 h-8 rounded-full block tw shadow-lg flex-shrink-0"></span>
                    <span @click="removeCustom(key1)" class="ml-4 bg-gray-600 hover:scale-125 hover:opacity-100 cursor-pointer bg-white transition-3s w-8 h-8 rounded-full block tw shadow-lg flex-shrink-0 font-bold flex items-center justify-center text-gray-900 "><i class="fa fa-minus"></i></span>

                </li>
                <li class="flex justify-between items-center mb-4">
                    <input type="text" v-model="newCustom.key1" placeholder="bg-yellow-500" class="py-2 px-4 appearance-none bg-gray-700 opacity-50 placeholder-gray-500 text-white font-normal border border-black shadow-lg w-full max-w-lg mr-4 rounded-full">
                    <div class="w-full hidden lg:block border-b border-gray-700 border-dashed"></div>
                    <input type="text" v-model="newCustom.key2" readonly="true" placeholder="bg-yellow-500" class="py-2 px-4 appearance-non bg-black opacity-50 placeholder-gray-800 text-white font-normal border border-gray-700 shadow-lg w-full max-w-lg mx-4 rounded-full">
                    <span @click="if (newCustom.key1) { selectColor() }else{ return }" :class="newCustom.key2 ? getBgColor(newCustom.key2) : 'bg-white'" class="hover:scale-125 hover:opacity-100 cursor-pointer transition-3s w-8 h-8 rounded-full block tw shadow-lg flex-shrink-0"></span>
                    <span @click="addCustom" v-if="newCustom.key1 && newCustom.key2" class="ml-4 bg-gray-600 border border-dashed border-black hover:scale-125 hover:opacity-100 cursor-pointer bg-white transition-3s w-8 h-8 rounded-full block tw shadow-lg flex-shrink-0 font-bold flex items-center justify-center text-gray-900 "><i class="fa fa-plus"></i></span>
                </li>
            </ul>
        </div>
    </div>
    <transition name="slide-fade">
    <div v-if="showedColor" @click="colorSelected()" class="absolute top-0 w-full w-full right-0 h-full transition-3s">
        <div class="absolute top-0 w-full lg:w-1/2 right-0 h-full lg:border-l border-gray-600 ">
            <div class="absolute top-0 w-full right-0 opacity-75 h-full bg-gray-900"></div>    
            <div class="absolute top-0 w-full right-0 opacity-50 h-full bg-black"></div>    
            <div class="absolute top-0 w-full right-0 h-full overflow-auto p-20">
                <ul class="relative">
                    <li v-for="color in tailWindColorsFull" @click="colorSelected(color)":class="{ 'border bg-gray-900 border-gray-600' : 'bg-' + color == getBgColor(colorSchema[keyToEdit] ? colorSchema[keyToEdit] : keyToEdit) }" class="cursor-pointer hover:bg-gray-900 p-1 rounded-full text-md flex justify-start mb-1 items-center text-white">
                        <span :class="'bg-' + color" class="mr-2 hover:scale-125 border border-gray-800 hover:opacity-100 cursor-pointer transition-3s w-8 h-8 rounded-full block tw shadow-lg flex-shrink-0"></span>
                        <span>${color}</span>
                    </li>
                </ul>
                
            </div>    
            
        </div>
    </div>
    </transition>
</div>
