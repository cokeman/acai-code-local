{% set tienda = 'configuracion_tienda' | get('',null,null,{})[0] %}
{% set config = 'configuracion' | get('',null,null,{})[0] %}

{{ config.scripts_body | base64_decode | raw }}

 {% set tw_class = ['sticky top-0 navbar-wrapper aaaap-2 aaaalg:p-3 w-full z-30 bg-old-gradient--header transition3s'] %} 

 {% if thisrecord.tableName is same as('apartados_edu') %} {% set tw_class = tw_class|merge(['educacion']) %} {% endif %} 
 <nav class="{{tw_class|join(' ')}}">
    <style type="text/css" media="all">
        body {
            opacity: 1;
            transition: all 0.3s ease-in-out;
        }
        .bg-old-gradient--header {
            background: rgb(253, 194, 0);
            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #fe8401), color-stop(100%, rgba(242, 149, 0, 1)));
            background: -webkit-linear-gradient(top, rgba(253, 194, 0, 1) 0%, #fe8401 100%);
            background: -o-linear-gradient(top, rgba(253, 194, 0, 1) 0%, #fe8401 100%);
            background: -ms-linear-gradient(top, rgba(253, 194, 0, 1) 0%, #fe8401 100%);
            background: linear-gradient(to bottom, rgba(253, 194, 0, 1) 0%, #fe8401 100%);
        }
        /*
        .bg-old-gradient--header {
            background: linear-gradient(to bottom, #e21b1b 100%, #e21b1b) !important;
        }
        */
        
        
    </style>
    {{ 'nuevomenu_m7gx9k' | module({})|raw }}
    
{% if false %}
<div class="container-full sm:mx-auto px-2 lg:px-2 flex items-center justify-between text-white">
        <div class="flex items-center flex-row lg:flex-row flex-shrink-0">
            
{% if not request.modo_restringido %}
<div class="block mr-2 lg:mr-0">
                <button onclick="toggleCategory()" class="flex items-center px-3 py-2 border-2 border-white rounded focus:outline-none hover:bg-orange-600">
                    <svg class="fill-current h-3 w-3 text-white" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <title>Menu</title>
                        <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"></path>
                    </svg>
                </button>
            </div>
{% endif %}

            <div class="flex items-center flex-shrink-0 ml-2 lg:ml-4">
                <a href="/" aria-label="{{'Ir a portada' | translate}}">
                    <span class="sr-only">{{configuracionRecord.titulo_de_pagina}}</span>
                    <img src="/template/estandar/images/logo-bl.svg" alt="{{configuracionRecord.titulo_de_pagina}}" class="h-6 lg:h-8 lg:-mt-2">
                </a>
            </div>
            <ul class="hidden lg:flex items-center ml-2 lg:ml-4">
                <!--<li>
                    <a href="/aniversario-25/" class="block flex items-center justify-start p-2 text-white text-sm hover:text-black px-4 font-semibold">
                        <span>🎉 Aniversario</span>
                    </a>
                </li>-->
                <li class="">
                    <a href="/productos/" class="block flex items-center justify-start p-2 text-white text-sm hover:text-black px-4 rounded-lg font-semibold">
                        <span>Productos</span>
                    </a>
                </li>
                <li>
                    <a href="/nuestros-servicios/" class="block flex items-center justify-start p-2 text-white text-sm hover:text-black px-4">
                        <span>Servicios</span>
                    </a>
                </li>
                <li>
                    <a href="/b2b/" class="block flex items-center justify-start p-2 text-white text-sm hover:text-black px-4">
                        <span>Empresas</span>
                    </a>
                </li>
                <li>
                    <a href="/educacion/" class="block flex items-center justify-start p-2 text-white text-sm hover:text-black px-4">
                        <span>Educación</span>
                    </a>
                </li>
                <li>
                    <a href="/tiendas/" class="block flex items-center justify-start p-2 text-white text-sm hover:text-black px-4">
                        <span>Tiendas</span>
                    </a>
                </li>
                
                
                
                
                
            </ul>
        </div>
        <div class="navbar w-full block text-md font-normal flex w-auto justify-end lg:justify-between py-0">
            <div id="searchDataApp" ref="search" class="flex w-full lg:flex items-center justify-end lg:pr-4 text-black hidden fixed lg:relative top-0 left-0 px-2 lg:px-0 py-2 lg:py-0 bg-orange-400 lg:bg-transparent z-50 lg:z-0">
                <div class="relative bg-gray-200 rounded-lg lg:rounded-full border w-full lg:max-w-md flex items-center justify-between" :class="{'border-orange-400':!speaking,'border-red-600':speaking}">
                    <svg class="svg-icon w-6 h-6 fill-current text-gray-600 ml-4" viewBox="0 0 20 20">
                        <path d="M18.125,15.804l-4.038-4.037c0.675-1.079,1.012-2.308,1.01-3.534C15.089,4.62,12.199,1.75,8.584,1.75C4.815,1.75,1.982,4.726,2,8.286c0.021,3.577,2.908,6.549,6.578,6.549c1.241,0,2.417-0.347,3.44-0.985l4.032,4.026c0.167,0.166,0.43,0.166,0.596,0l1.479-1.478C18.292,16.234,18.292,15.968,18.125,15.804 M8.578,13.99c-3.198,0-5.716-2.593-5.733-5.71c-0.017-3.084,2.438-5.686,5.74-5.686c3.197,0,5.625,2.493,5.64,5.624C14.242,11.548,11.621,13.99,8.578,13.99 M16.349,16.981l-3.637-3.635c0.131-0.11,0.721-0.695,0.876-0.884l3.642,3.639L16.349,16.981z"></path>
                    </svg>
                    <label for="buscador" class="sr-only">{{'Buscador' | translate}}</label>

                    <input id="buscador" v-model="searchTerm" name="termino" type="text" class="appearance-none bg-transparent py-2 focus:outline-none px-4 w-full" :placeholder="placeholder">

                    <svg class="cursor-pointer hover:opacity-75 svg-icon w-6 h-6 fill-current text-gray-600 mr-4" @click="resetSearch(true)" viewBox="0 0 20 20">
                        <path d="M12.71,7.291c-0.15-0.15-0.393-0.15-0.542,0L10,9.458L7.833,7.291c-0.15-0.15-0.392-0.15-0.542,0c-0.149,0.149-0.149,0.392,0,0.541L9.458,10l-2.168,2.167c-0.149,0.15-0.149,0.393,0,0.542c0.15,0.149,0.392,0.149,0.542,0L10,10.542l2.168,2.167c0.149,0.149,0.392,0.149,0.542,0c0.148-0.149,0.148-0.392,0-0.542L10.542,10l2.168-2.168C12.858,7.683,12.858,7.44,12.71,7.291z M10,1.188c-4.867,0-8.812,3.946-8.812,8.812c0,4.867,3.945,8.812,8.812,8.812s8.812-3.945,8.812-8.812C18.812,5.133,14.867,1.188,10,1.188z M10,18.046c-4.444,0-8.046-3.603-8.046-8.046c0-4.444,3.603-8.046,8.046-8.046c4.443,0,8.046,3.602,8.046,8.046C18.046,14.443,14.443,18.046,10,18.046z"></path>
                    </svg>

                    <button @click="startStopSpeech()" v-if="navegadorEsChrome" class="cursor-pointer hover:opacity-75 svg-icon w-6 h-6 fill-current mr-4" :class="{ 'text-gray-600' : !speaking , 'text-red-600' : speaking , 'hidden' : !canSpeak }">
                        <i class="fa fa-microphone"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-row flex-shrink-0">
                <span class="lg:hidden menuLink relative menu block flex items-center py-2 lg:py-0 mr-0 lg:mr-4 px-2 hover:text-black text-xs text-right leading-tight">
                    <button onclick="searchDataApp.resetSearch()" class="hidden xl:block text-right">
                        Busca <br>
                        productos
                    </button>
                    <button onclick="searchDataApp.resetSearch()" class="relative">
                        <svg class="h-6 w-6 text-white fill-current svg-icon xl:ml-2" viewBox="0 0 20 20">
                            <path d="M19.129,18.164l-4.518-4.52c1.152-1.373,1.852-3.143,1.852-5.077c0-4.361-3.535-7.896-7.896-7.896
								c-4.361,0-7.896,3.535-7.896,7.896s3.535,7.896,7.896,7.896c1.934,0,3.705-0.698,5.078-1.853l4.52,4.519
								c0.266,0.268,0.699,0.268,0.965,0C19.396,18.863,19.396,18.431,19.129,18.164z M8.567,15.028c-3.568,0-6.461-2.893-6.461-6.461
								s2.893-6.461,6.461-6.461c3.568,0,6.46,2.893,6.46,6.461S12.135,15.028,8.567,15.028z"></path>
                        </svg>
                    </button>
                </span>
                
{% if not session.user %}
<a href="/login/" class="menuLink relative menu block flex items-center py-2 lg:py-0 mr-0 lg:mr-4 px-2 hover:text-black text-xs text-right leading-tight">
                    <button class="hidden xl:block text-right">
                        Entra <br>
                        o regístrate
                    </button>
                    <button class="relative">
                        <svg class="h-6 w-6 text-white fill-current svg-icon xl:ml-2" viewBox="0 0 20 20">
                            <path d="M10.001,9.658c-2.567,0-4.66-2.089-4.66-4.659c0-2.567,2.092-4.657,4.66-4.657s4.657,2.09,4.657,4.657
                            C14.658,7.569,12.569,9.658,10.001,9.658z M10.001,1.8c-1.765,0-3.202,1.437-3.202,3.2c0,1.766,1.437,3.202,3.202,3.202
                            c1.765,0,3.199-1.436,3.199-3.202C13.201,3.236,11.766,1.8,10.001,1.8z"></path>
                            <path d="M9.939,19.658c-0.091,0-0.179-0.017-0.268-0.051l-7.09-2.803c-0.276-0.108-0.461-0.379-0.461-0.678
                            c0-4.343,3.535-7.876,7.881-7.876c4.343,0,7.878,3.533,7.878,7.876c0,0.302-0.182,0.572-0.464,0.68l-7.213,2.801
                            C10.118,19.64,10.03,19.658,9.939,19.658z M3.597,15.639l6.344,2.507l6.464-2.512c-0.253-3.312-3.029-5.927-6.404-5.927
                            C6.623,9.707,3.848,12.326,3.597,15.639z"></path>
                            <path d="M9.939,19.658c0,0-0.003,0-0.006,0c-0.347-0.003-0.646-0.253-0.709-0.596L7.462,9.567
                            C7.389,9.172,7.65,8.79,8.046,8.718C8.442,8.643,8.82,8.906,8.894,9.301l1.076,5.796l1.158-5.741
                            c0.08-0.394,0.461-0.655,0.86-0.569c0.396,0.08,0.649,0.464,0.569,0.859l-1.904,9.427C10.585,19.413,10.286,19.658,9.939,19.658z"></path>
                        </svg>
                    </button>
                </a>
{% endif %}

                
{% if session.user %}
<span class="menuLink relative menu block flex items-center py-2 lg:py-0 mr-0 lg:mr-4 px-2 hover:text-black text-xs text-right leading-tight">
                    <button onclick="toggleSubmenu(this.parentNode)" class="hidden xl:block text-right">
                        Mi <br>
                        cuenta
                    </button>
                    <button onclick="toggleSubmenu(this.parentNode)" class="relative">
                        <svg class="h-6 w-6 text-white fill-current svg-icon xl:ml-2" viewBox="0 0 20 20">
                            <path d="M10.001,9.658c-2.567,0-4.66-2.089-4.66-4.659c0-2.567,2.092-4.657,4.66-4.657s4.657,2.09,4.657,4.657
                            C14.658,7.569,12.569,9.658,10.001,9.658z M10.001,1.8c-1.765,0-3.202,1.437-3.202,3.2c0,1.766,1.437,3.202,3.202,3.202
                            c1.765,0,3.199-1.436,3.199-3.202C13.201,3.236,11.766,1.8,10.001,1.8z"></path>
                            <path d="M9.939,19.658c-0.091,0-0.179-0.017-0.268-0.051l-7.09-2.803c-0.276-0.108-0.461-0.379-0.461-0.678
                            c0-4.343,3.535-7.876,7.881-7.876c4.343,0,7.878,3.533,7.878,7.876c0,0.302-0.182,0.572-0.464,0.68l-7.213,2.801
                            C10.118,19.64,10.03,19.658,9.939,19.658z M3.597,15.639l6.344,2.507l6.464-2.512c-0.253-3.312-3.029-5.927-6.404-5.927
                            C6.623,9.707,3.848,12.326,3.597,15.639z"></path>
                            <path d="M9.939,19.658c0,0-0.003,0-0.006,0c-0.347-0.003-0.646-0.253-0.709-0.596L7.462,9.567
                            C7.389,9.172,7.65,8.79,8.046,8.718C8.442,8.643,8.82,8.906,8.894,9.301l1.076,5.796l1.158-5.741
                            c0.08-0.394,0.461-0.655,0.86-0.569c0.396,0.08,0.649,0.464,0.569,0.859l-1.904,9.427C10.585,19.413,10.286,19.658,9.939,19.658z"></path>
                        </svg>
                    </button>
                    <div class="submenu hidden absolute top-100 right-0 w-64 text-left bg-white p-4 shadow-lg border-t-4 border-orange-500">
                        <ul>
                            <!--<li class="text-xs border-b border-gray-400 mb-2">
                                <span class="block pt-2 px-2  truncate text-gray-600 capitalize">{{session.user_bd.nombre}}</span>
                                <span class="block pb-4 px-2  truncate text-gray-600 lowercase">{{session.user_bd.correo}}</span>
                            </li>-->
                            {% set apartados = 'apartados' | get('parentNum=21 and visible_en_el_menu=1','siblingOrder ASC',null,{}) %}
 {% for apartado in apartados %} 
<li><a href="{{apartado.enlace}}" class="block flex items-center py-2 hover:bg-gray-200 px-2">{{apartado.name}}</a></li>
 {% endfor %} 

                            <li><a href="/?cerrarsesion=1" class="block flex items-center py-2 hover:bg-gray-200 px-2">Cerrar sesión</a></li>
                        </ul>
                    </div>
                </span>
{% endif %}

                
                
{% if tienda.tienda_activa %}
<span class="menuLink relative menu block flex items-center py-2 lg:py-0 mr-0 lg:mr-4 px-2 hover:text-black text-white text-xs text-right leading-tight">
                    <button onclick="toggleSubmenu(this.parentNode)" class="hidden xl:block text-right">
                        {{'Mi <br>
                        Carrito' | translate | raw }}
                    </button>
                    <button onclick="toggleSubmenu(this.parentNode)" class="relative">
                        <svg class="h-6 w-6 text-white fill-current svg-icon xl:ml-2" viewBox="0 0 20 20">
                            <path d="M9.727,7.292c0.078,0.078,0.186,0.125,0.304,0.125c0.119,0,0.227-0.048,0.304-0.125l1.722-1.722c0.078-0.078,0.126-0.186,0.126-0.305c0-0.237-0.192-0.43-0.431-0.43c-0.118,0-0.227,0.048-0.305,0.126l-0.986,0.987V1.822c0-0.237-0.193-0.43-0.431-0.43s-0.431,0.193-0.431,0.43v4.126L8.614,4.961C8.537,4.884,8.429,4.835,8.31,4.835c-0.238,0-0.43,0.193-0.43,0.43c0,0.119,0.048,0.227,0.126,0.305L9.727,7.292z M18.64,8.279H1.423c-0.475,0-0.861,0.385-0.861,0.86V10c0,0.476,0.386,0.861,0.861,0.861h0.172l1.562,7.421l0.008-0.002c0.047,0.187,0.208,0.328,0.41,0.328h12.912c0.201,0,0.362-0.142,0.409-0.328l0.009,0.002l1.562-7.421h0.173c0.475,0,0.86-0.386,0.86-0.861V9.139C19.5,8.664,19.114,8.279,18.64,8.279 M2.475,10.861h2.958l0.271,1.721H2.837L2.475,10.861z M3.018,13.443h2.823l0.271,1.722H3.38L3.018,13.443z M3.924,17.747l-0.362-1.722h2.687l0.271,1.722H3.924z M9.601,17.747H7.38l-0.271-1.722h2.491V17.747z M9.601,15.165H6.973l-0.271-1.722h2.899V15.165z M9.601,12.582H6.565l-0.271-1.721h3.307V12.582z M12.682,17.747h-2.22v-1.722h2.491L12.682,17.747z M13.09,15.165h-2.628v-1.722h2.899L13.09,15.165z M10.462,12.582v-1.721h3.307l-0.271,1.721H10.462z M16.139,17.747h-2.596l0.271-1.722H16.5L16.139,17.747z M16.683,15.165H13.95l0.271-1.722h2.823L16.683,15.165z M17.226,12.582h-2.867l0.271-1.721h2.958L17.226,12.582z M18.64,10H1.423V9.139H18.64V10z"></path>
                        </svg>
                        <span class="cesta-count block absolute top-0 right-0 w-4 h-4 -mr-2 bg-orange-600 text-white text-xs flex items-center justify-center rounded-full">0</span>
                    </button>
                    <div class="submenu hidden absolute top-100 right-0 w-64 text-left bg-white p-4 shadow-lg border-t-4 border-orange-500">
                        {{ 'cestawidgetcesta_3auuh' | module({})|raw }}
                    </div>
                </span>
{% endif %}

            </div>
        </div>
    </div>
{% endif %}

</nav>

{% if request.ver_mensaje_app %}
{{ 'appavisoweb_y45157' | module({})|raw }}
{% endif %}