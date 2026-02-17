<div id="navbarBuilder" class="fixed top-0 z-5000" >
    <div class=" bg-gray-800 p-2 shadow-lg">
        
        <div class="relative flex justify-between ">
            <div class="hidden-xs flex items-center justify-end flex-shrink-0">
                <? if ($CURRENT_USER["licencia"]){?>
                <button @click="toggleNewModuleModal()" :class="{'bg-theme':showed == 'NEWMODULES','bg-gray-700':showed != 'NEWMODULES','opacity-25 pointer-events-none':showed != null && showed != 'NEWMODULES'}" class="bg-gray-700 leading-none text-white rounded-l-lg relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black">
                    <svg viewBox="0 0 20 20" class="fill-current w-5 h-5 mr-2">
                            <path d="M13.388,9.624h-3.011v-3.01c0-0.208-0.168-0.377-0.376-0.377S9.624,6.405,9.624,6.613v3.01H6.613c-0.208,0-0.376,0.168-0.376,0.376s0.168,0.376,0.376,0.376h3.011v3.01c0,0.208,0.168,0.378,0.376,0.378s0.376-0.17,0.376-0.378v-3.01h3.011c0.207,0,0.377-0.168,0.377-0.376S13.595,9.624,13.388,9.624z M10,1.344c-4.781,0-8.656,3.875-8.656,8.656c0,4.781,3.875,8.656,8.656,8.656c4.781,0,8.656-3.875,8.656-8.656C18.656,5.219,14.781,1.344,10,1.344z M10,17.903c-4.365,0-7.904-3.538-7.904-7.903S5.635,2.096,10,2.096S17.903,5.635,17.903,10S14.365,17.903,10,17.903z"></path>
                        </svg>
                        <span class="leading-none" v-if="showed == 'NEWMODULES'">Cerrar&nbsp;</span>
                    <span class="leading-none">Añadir Módulo</span>
                </button>
                
                
                <button @click="toggleCustomColorsModal()" :class="{'bg-theme':showed == 'COLORS','bg-gray-700':showed != 'COLORS','opacity-25 pointer-events-none':showed != null && showed != 'COLORS'}" class="bg-gray-700 leading-none text-white  relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black">
                    <svg viewBox="0 0 20 20" class="fill-current w-5 h-5 mr-2">
                            <path d="M17.125,1.375H2.875c-0.828,0-1.5,0.672-1.5,1.5v11.25c0,0.828,0.672,1.5,1.5,1.5H7.75v2.25H6.625c-0.207,0-0.375,0.168-0.375,0.375s0.168,0.375,0.375,0.375h6.75c0.207,0,0.375-0.168,0.375-0.375s-0.168-0.375-0.375-0.375H12.25v-2.25h4.875c0.828,0,1.5-0.672,1.5-1.5V2.875C18.625,2.047,17.953,1.375,17.125,1.375z M11.5,17.875h-3v-2.25h3V17.875zM17.875,14.125c0,0.414-0.336,0.75-0.75,0.75H2.875c-0.414,0-0.75-0.336-0.75-0.75v-1.5h15.75V14.125z M17.875,11.875H2.125v-9c0-0.414,0.336-0.75,0.75-0.75h14.25c0.414,0,0.75,0.336,0.75,0.75V11.875z M10,14.125c0.207,0,0.375-0.168,0.375-0.375S10.207,13.375,10,13.375s-0.375,0.168-0.375,0.375S9.793,14.125,10,14.125z"></path>
                        </svg>
                        <span class="leading-none" v-if="showed == 'COLORS'">Cerrar&nbsp;</span>
                    <span class="leading-none">Colores del Módulo</span>
                </button>

                <button @click="toggleCustomMarginsModal()" :class="{'bg-theme':showed == 'MARGINS','bg-gray-700':showed != 'MARGINS','opacity-25 pointer-events-none':showed != null && showed != 'MARGINS'}" class="bg-gray-700 leading-none text-white  relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black rounded-r-lg">
                    <svg viewBox="0 0 20 20" class="fill-current w-5 h-5 mr-2">
                            <path d="M17.125,1.375H2.875c-0.828,0-1.5,0.672-1.5,1.5v11.25c0,0.828,0.672,1.5,1.5,1.5H7.75v2.25H6.625c-0.207,0-0.375,0.168-0.375,0.375s0.168,0.375,0.375,0.375h6.75c0.207,0,0.375-0.168,0.375-0.375s-0.168-0.375-0.375-0.375H12.25v-2.25h4.875c0.828,0,1.5-0.672,1.5-1.5V2.875C18.625,2.047,17.953,1.375,17.125,1.375z M11.5,17.875h-3v-2.25h3V17.875zM17.875,14.125c0,0.414-0.336,0.75-0.75,0.75H2.875c-0.414,0-0.75-0.336-0.75-0.75v-1.5h15.75V14.125z M17.875,11.875H2.125v-9c0-0.414,0.336-0.75,0.75-0.75h14.25c0.414,0,0.75,0.336,0.75,0.75V11.875z M10,14.125c0.207,0,0.375-0.168,0.375-0.375S10.207,13.375,10,13.375s-0.375,0.168-0.375,0.375S9.793,14.125,10,14.125z"></path>
                        </svg>
                        <span class="leading-none" v-if="showed == 'MARGINS'">Cerrar&nbsp;</span>
                    <span class="leading-none">Márgenes</span>
                </button>

                <? }else{ ?>
                <button @click="noLicence()" :class="{'bg-theme':showed == 'NEWMODULES','bg-gray-700':showed != 'NEWMODULES','opacity-25 pointer-events-none':showed != null && showed != 'NEWMODULES'}" class="bg-gray-700 leading-none text-white rounded-l-lg relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black">
                    <i class="fa fa-lock"></i>&nbsp;&nbsp;
                    <span class="leading-none">Añadir Módulo</span>
                </button>
                <button @click="noLicence()" :class="{'bg-theme':showed == 'COLORS','bg-gray-700':showed != 'COLORS','opacity-25 pointer-events-none':showed != null && showed != 'COLORS'}" class="bg-gray-700 leading-none text-white  relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black rounded-r-lg">
                    <i class="fa fa-lock"></i>&nbsp;&nbsp;
                    <span class="leading-none">Colores del Módulo</span>
                </button>
                <button @click="noLicence()" :class="{'bg-theme':showed == 'MARGINS','bg-gray-700':showed != 'MARGINS','opacity-25 pointer-events-none':showed != null && showed != 'COLORS'}" class="bg-gray-700 leading-none text-white  relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black rounded-r-lg">
                    <i class="fa fa-lock"></i>&nbsp;&nbsp;
                    <span class="leading-none">Márgenes</span>
                </button>
                <? }?>
                <!--<button @click="changeViewExpanded()" :class="{'opacity-25 pointer-events-none':showed != null}" class="tw-hidden xl:flex bg-gray-700 leading-none text-white rounded-r-lg relative justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black">
                    <svg v-if="expandedView" viewBox="0 0 20 20" class="fill-current w-5 h-5 mr-2">
                            <path  d="M18.935,18.509h-3.83c0-2.819-2.285-5.105-5.104-5.105s-5.105,2.286-5.105,5.105H1.066c-0.234,0-0.425,0.19-0.425,0.426c0,0.234,0.191,0.425,0.425,0.425h17.869c0.234,0,0.425-0.19,0.425-0.425C19.359,18.699,19.169,18.509,18.935,18.509 M5.746,18.509c0-2.351,1.905-4.254,4.254-4.254s4.255,1.903,4.255,4.254H5.746zM14.813,14.298l1.805-1.806c0.166-0.166,0.166-0.436,0-0.602c-0.166-0.167-0.436-0.167-0.602,0l-1.806,1.805c-0.165,0.166-0.165,0.436,0,0.603C14.378,14.463,14.647,14.463,14.813,14.298 M9.575,9.575v2.552c0,0.235,0.19,0.426,0.425,0.426s0.425-0.19,0.425-0.426V9.575c0-0.235-0.19-0.426-0.425-0.426S9.575,9.339,9.575,9.575 M5.187,14.298c0.167,0.165,0.436,0.165,0.603,0c0.166-0.167,0.166-0.437,0-0.603l-1.806-1.805c-0.167-0.167-0.435-0.167-0.602,0c-0.166,0.166-0.166,0.436,0,0.602L5.187,14.298z M7.448,4.044h0.851v2.127c0,0.235,0.19,0.425,0.425,0.425h2.553c0.234,0,0.426-0.19,0.426-0.425V4.044h0.851c0.234,0,0.425-0.19,0.425-0.425c0-0.117-0.047-0.224-0.124-0.301l-2.553-2.552C10.224,0.688,10.117,0.641,10,0.641S9.776,0.688,9.699,0.766L7.146,3.318C7.07,3.395,7.022,3.501,7.022,3.619C7.022,3.854,7.213,4.044,7.448,4.044 M10,1.667l1.525,1.525h-0.249c-0.234,0-0.425,0.191-0.425,0.426v2.127H9.149V3.619c0-0.235-0.19-0.426-0.425-0.426H8.475L10,1.667z"></path>
                        </svg>
                    <svg v-if="!expandedView" viewBox="0 0 20 20" class="fill-current w-5 h-5 mr-2" >
                            <path  d="M5.163,5.768c0.167,0.167,0.438,0.167,0.605,0c0.167-0.167,0.167-0.438,0-0.604L3.953,3.349c-0.167-0.167-0.438-0.167-0.604,0c-0.167,0.167-0.167,0.437,0,0.604L5.163,5.768z M14.837,5.768l1.814-1.814c0.167-0.167,0.167-0.438,0-0.604c-0.168-0.167-0.438-0.167-0.605,0l-1.813,1.814c-0.167,0.167-0.167,0.437,0,0.604C14.399,5.935,14.67,5.935,14.837,5.768 M10,4.014c0.236,0,0.428-0.191,0.428-0.428V1.021c0-0.236-0.192-0.428-0.428-0.428S9.572,0.785,9.572,1.021v2.565C9.572,3.823,9.764,4.014,10,4.014 M18.979,10h-3.848c0-2.833-2.297-5.131-5.131-5.131c-2.833,0-5.131,2.297-5.131,5.131H1.021c-0.236,0-0.428,0.191-0.428,0.428s0.192,0.428,0.428,0.428h17.957c0.236,0,0.428-0.191,0.428-0.428S19.215,10,18.979,10 M5.725,10c0-2.361,1.914-4.275,4.275-4.275S14.276,7.639,14.276,10H5.725zM12.565,15.985H11.71v-2.138c0-0.235-0.191-0.427-0.428-0.427H8.717c-0.236,0-0.428,0.191-0.428,0.427v2.138H7.435c-0.235,0-0.427,0.191-0.427,0.428c0,0.118,0.047,0.226,0.125,0.304l2.565,2.564c0.077,0.078,0.185,0.125,0.302,0.125s0.225-0.047,0.302-0.125l2.565-2.564c0.078-0.078,0.126-0.186,0.126-0.304C12.993,16.177,12.802,15.985,12.565,15.985 M10,18.374l-1.533-1.533h0.25c0.236,0,0.428-0.191,0.428-0.428v-2.138h1.709v2.138c0,0.236,0.192,0.428,0.428,0.428h0.251L10,18.374z"></path>
                        </svg>
                    <span class="leading-none">{{ expandedView ? 'Vista avanzada' : 'Vista simple'}}</span>
                </button>-->
                
            </div>  
            <div v-if="buttonGroups" class="hidden-sm hidden-xs flex tw items-center justify-center flex-shrink-0">
                <button v-for="(button, index) in buttonGroups" :class="{'opacity-25 pointer-events-none':showed != null}"  @click="button.callback()" class="bg-gray-700 leading-none text-white relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black" :class="{[button.classes]: true, 'rounded-l-lg': index === 0, 'rounded-r-lg': index === buttonGroups.length - 1}">
                    <span class="leading-none">{{button.label}}</span>
                </button>
            </div>
            <div class="flex items-center justify-end flex-shrink-0" >
                <button @click="toggleConfiguracion()" :class="{'bg-theme':showed == 'CONFIGURACION','bg-gray-700':showed != 'CONFIGURACION','opacity-25 pointer-events-none':showed != null && showed != 'CONFIGURACION'}" class="bg-gray-700 leading-none text-white rounded-l-lg relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black">
                    <svg viewBox="0 0 20 20" class="fill-current w-5 h-5 mr-2">
                            <path  d="M10.032,8.367c-1.112,0-2.016,0.905-2.016,2.018c0,1.111,0.904,2.014,2.016,2.014c1.111,0,2.014-0.902,2.014-2.014C12.046,9.271,11.143,8.367,10.032,8.367z M10.032,11.336c-0.525,0-0.953-0.427-0.953-0.951c0-0.526,0.427-0.955,0.953-0.955c0.524,0,0.951,0.429,0.951,0.955C10.982,10.909,10.556,11.336,10.032,11.336z"></path>
                            <path  d="M17.279,8.257h-0.785c-0.107-0.322-0.237-0.635-0.391-0.938l0.555-0.556c0.208-0.208,0.208-0.544,0-0.751l-2.254-2.257c-0.199-0.2-0.552-0.2-0.752,0l-0.556,0.557c-0.304-0.153-0.617-0.284-0.939-0.392V3.135c0-0.294-0.236-0.532-0.531-0.532H8.435c-0.293,0-0.531,0.237-0.531,0.532v0.784C7.582,4.027,7.269,4.158,6.966,4.311L6.409,3.754c-0.1-0.1-0.234-0.155-0.376-0.155c-0.141,0-0.275,0.055-0.375,0.155L3.403,6.011c-0.208,0.207-0.208,0.543,0,0.751l0.556,0.556C3.804,7.622,3.673,7.935,3.567,8.257H2.782c-0.294,0-0.531,0.238-0.531,0.531v3.19c0,0.295,0.237,0.531,0.531,0.531h0.787c0.105,0.318,0.236,0.631,0.391,0.938l-0.556,0.559c-0.208,0.207-0.208,0.545,0,0.752l2.254,2.254c0.208,0.207,0.544,0.207,0.751,0l0.558-0.559c0.303,0.154,0.616,0.285,0.938,0.391v0.787c0,0.293,0.238,0.531,0.531,0.531h3.191c0.295,0,0.531-0.238,0.531-0.531v-0.787c0.322-0.105,0.636-0.236,0.938-0.391l0.56,0.559c0.208,0.205,0.546,0.207,0.752,0l2.252-2.254c0.208-0.207,0.208-0.545,0.002-0.752l-0.559-0.559c0.153-0.303,0.285-0.615,0.389-0.938h0.789c0.295,0,0.532-0.236,0.532-0.531v-3.19C17.812,8.495,17.574,8.257,17.279,8.257z M16.747,11.447h-0.653c-0.241,0-0.453,0.164-0.514,0.398c-0.129,0.496-0.329,0.977-0.594,1.426c-0.121,0.209-0.089,0.473,0.083,0.645l0.463,0.465l-1.502,1.504l-0.465-0.463c-0.174-0.174-0.438-0.207-0.646-0.082c-0.447,0.262-0.927,0.463-1.427,0.594c-0.234,0.061-0.397,0.271-0.397,0.514V17.1H8.967v-0.652c0-0.242-0.164-0.453-0.397-0.514c-0.5-0.131-0.98-0.332-1.428-0.594c-0.207-0.123-0.472-0.09-0.646,0.082l-0.463,0.463L4.53,14.381l0.461-0.463c0.169-0.172,0.204-0.434,0.083-0.643c-0.266-0.461-0.467-0.939-0.596-1.43c-0.06-0.234-0.272-0.398-0.514-0.398H3.313V9.319h0.652c0.241,0,0.454-0.162,0.514-0.397c0.131-0.498,0.33-0.979,0.595-1.43c0.122-0.208,0.088-0.473-0.083-0.645L4.53,6.386l1.503-1.504l0.46,0.462c0.173,0.172,0.437,0.204,0.646,0.083c0.45-0.265,0.931-0.464,1.433-0.597c0.233-0.062,0.396-0.274,0.396-0.514V3.667h2.128v0.649c0,0.24,0.161,0.452,0.396,0.514c0.502,0.133,0.982,0.333,1.433,0.597c0.211,0.12,0.475,0.089,0.646-0.083l0.459-0.462l1.504,1.504l-0.463,0.463c-0.17,0.171-0.202,0.438-0.081,0.646c0.263,0.448,0.463,0.928,0.594,1.427c0.061,0.235,0.272,0.397,0.514,0.397h0.651V11.447z"></path>
                        </svg>
                    
                    <span class="leading-none" v-if="showed == 'CONFIGURACION'">Cerrar&nbsp;</span>
                    <span class="leading-none">Configuración</span>
                </button>
                <button @click="toggleFullPreview();" :class="{'bg-theme':showed == 'VERPAGINA','bg-gray-700':showed != 'VERPAGINA','opacity-25 pointer-events-none':showed != null && showed != 'VERPAGINA'}" class="btn-fullPreview bg-gray-700 leading-none text-white  relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black">
                    <svg viewBox="0 0 20 20" class="fill-current w-5 h-5 mr-2">
                            <path d="M10,6.978c-1.666,0-3.022,1.356-3.022,3.022S8.334,13.022,10,13.022s3.022-1.356,3.022-3.022S11.666,6.978,10,6.978M10,12.267c-1.25,0-2.267-1.017-2.267-2.267c0-1.25,1.016-2.267,2.267-2.267c1.251,0,2.267,1.016,2.267,2.267C12.267,11.25,11.251,12.267,10,12.267 M18.391,9.733l-1.624-1.639C14.966,6.279,12.563,5.278,10,5.278S5.034,6.279,3.234,8.094L1.609,9.733c-0.146,0.147-0.146,0.386,0,0.533l1.625,1.639c1.8,1.815,4.203,2.816,6.766,2.816s4.966-1.001,6.767-2.816l1.624-1.639C18.536,10.119,18.536,9.881,18.391,9.733 M16.229,11.373c-1.656,1.672-3.868,2.594-6.229,2.594s-4.573-0.922-6.23-2.594L2.41,10l1.36-1.374C5.427,6.955,7.639,6.033,10,6.033s4.573,0.922,6.229,2.593L17.59,10L16.229,11.373z"></path>
                        </svg>
                        <span class="leading-none" v-if="showed == 'VERPAGINA'">Cerrar&nbsp;</span>
                    <span class="leading-none">Ver página</span>
                </button>
                
                
                <button @click="save()" :class="{'opacity-25 pointer-events-none':showed != null}" class="bg-gray-700 leading-none text-white  relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black">
                    <span v-if="needToSave" class="block tw w-5 h-5 mr-2 border border-yellow-400 rounded-full flex items-center justify-center"><i  class="fa fa-warning text-yellow-400"></i></span>
                    <svg v-else viewBox="0 0 20 20" class="fill-current w-5 h-5 mr-2">
                        <path d="M17.064,4.656l-2.05-2.035C14.936,2.544,14.831,2.5,14.721,2.5H3.854c-0.229,0-0.417,0.188-0.417,0.417v14.167c0,0.229,0.188,0.417,0.417,0.417h12.917c0.229,0,0.416-0.188,0.416-0.417V4.952C17.188,4.84,17.144,4.733,17.064,4.656M6.354,3.333h7.917V10H6.354V3.333z M16.354,16.667H4.271V3.333h1.25v7.083c0,0.229,0.188,0.417,0.417,0.417h8.75c0.229,0,0.416-0.188,0.416-0.417V3.886l1.25,1.239V16.667z M13.402,4.688v3.958c0,0.229-0.186,0.417-0.417,0.417c-0.229,0-0.417-0.188-0.417-0.417V4.688c0-0.229,0.188-0.417,0.417-0.417C13.217,4.271,13.402,4.458,13.402,4.688"></path>
                    </svg>
                    <span class="leading-none">Guardar</span>
                </button>
                <button @click="salir()" :class="{'opacity-25 pointer-events-none':showed != null}"  class="bg-gray-700 leading-none text-white rounded-r-lg relative flex justify-center items-center py-2 px-3 space-x-2 border border-gray-900  shadow hover:bg-black">
                    <svg viewBox="0 0 20 20" class="fill-current w-5 h-5 mr-2">
                            <path d="M10.185,1.417c-4.741,0-8.583,3.842-8.583,8.583c0,4.74,3.842,8.582,8.583,8.582S18.768,14.74,18.768,10C18.768,5.259,14.926,1.417,10.185,1.417 M10.185,17.68c-4.235,0-7.679-3.445-7.679-7.68c0-4.235,3.444-7.679,7.679-7.679S17.864,5.765,17.864,10C17.864,14.234,14.42,17.68,10.185,17.68 M10.824,10l2.842-2.844c0.178-0.176,0.178-0.46,0-0.637c-0.177-0.178-0.461-0.178-0.637,0l-2.844,2.841L7.341,6.52c-0.176-0.178-0.46-0.178-0.637,0c-0.178,0.176-0.178,0.461,0,0.637L9.546,10l-2.841,2.844c-0.178,0.176-0.178,0.461,0,0.637c0.178,0.178,0.459,0.178,0.637,0l2.844-2.841l2.844,2.841c0.178,0.178,0.459,0.178,0.637,0c0.178-0.176,0.178-0.461,0-0.637L10.824,10z"></path>
                        </svg>
                    <span class="leading-none">Salir</span>
                </button>
            </div>    
        </div>
    </div>
    
</div>
<script>
    
function startNavbarModule() {
    navbarModule = new Vue({
        el:"#navbarBuilder",
        data:{
            needToSave:false,
            showed:null,
            buttonGroups:[],
            expandedView:false
        },
        computed:{
            
        },
        filters:{
            
        },
        watch:{
            showed:function(){
                if (document.querySelector(".expandedView")) this.expandedView = false; else this.expandedView = true;
            }
        },
        mounted() {
            this.init();
        },
        methods:{
            init: function(){
                
                this.expandedView = document.querySelector(".splitWrapper").classList.contains("expandedView") ? true : false;
                
            },
            disableShowed:function(){

                this.showed = null;
            },
            toggleNewModuleModal:function(){
                this.showed = this.showed == "NEWMODULES" ? null : "NEWMODULES";
                if (this.showed) {
                    toggleNewModuleModal(); 
                } else {
                    toggleNewModuleModal(true);
                    for (let myConf of myConfig){
                        if (myConf.isActive) {
                            myConf.renderEditView();
                            break;
                        }
                    }
                }
            },
            noLicence(){
                App.noLicence();
            },
            toggleCustomColorsModal:function(){
                toggleCustomColorsModal(this.showed == "COLORS" ? true : false);
                this.showed = this.showed == "COLORS" ? null : "COLORS";
                
            },
            toggleCustomMarginsModal:function(){
                toggleCustomMarginsModal(this.showed == "MARGINS" ? true : false);
                this.showed = this.showed == "MARGINS" ? null : "MARGINS";
                
            },
            toggleConfiguracion:function(){
                myConfig[0]._removeAllActive();                
                if (this.showed && this.showed == "CONFIGURACION"){
                    myConfig[0].clickHandler();
                    this.showed = null;
                }
                else{
                    toggleEditTabs(2,false);    
                    this.showed = "CONFIGURACION";
                }
            },
            changeViewExpanded:function(){
                this.expandedView = !this.expandedView;
                changeViewExpanded();
            },
            toggleFullPreview:function(){
                
                myConfig[0]._removeAllActive();                
                if (this.showed && this.showed == "VERPAGINA"){
                    myConfig[0].clickHandler();
                    this.showed = null;
                }
                else{
                    toggleEditTabs(4,false,null);
                    this.showed = "VERPAGINA";
                }

            },
            salir:function(){
                document.location.href = "?menu=" + MENU;
            },
            save:function(){
                if (NUM) saveRecord(false,false); else saveRecord(true,false);    
            }

        }
    })
}
    
window.addEventListener("load", () => startNavbarModule());
</script>
<style>
    #navbarBuilder{width:calc(100% - 35px);}
    .z-5000{z-index:5000;}
    .toolBar{display:none;}
    #page-content{padding-top:54px !important;background-color:#fff;}
    .saveButtons,.split.right3{display:none;}
    .splitWrapper.expandedView .split.right::after{display:none !important;}
    .list-modules-conf,.split.left .separator>.pull-right,.wrapperContainer>h3,.wrapperContainer>h3+p{display: none;}
    .wrapperContainer>form{padding-top:0px;}
    #colorEditor{width:calc(100% - 35px) !important;left:auto;}
    #newModulesVue,#colorEditor.showed,#marginEditor.showed{margin-top:54px;}
    .wrapperContainerPreview{
        background-color:#edf2f7;
        width: calc(100vw - 330px) !important;
        position: fixed;
        z-index: 1000;
        margin:0 auto;
        padding: 0px !important;
        padding-bottom:100px !important;
        max-width: none !important;
    }
    .expandedView #checkVisibility{right:auto;left:20px;}
    .wrapperContainerPreview #page-content{max-width:1000px;background:none;margin:0 auto;}
    @media screen and (max-width:1280px){
        .tw-hidden{display:none;}
    }
</style>
