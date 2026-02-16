<?php
global $TABLE_PREFIX, $CURRENT_USER;
?>
<style>
    .idioma {
        transition: .2s all;
    }

    .idioma:hover .unlocked_icon {
        transition: .2s all;
        opacity: 1 !important;
        transform: rotate(12deg);
    }

    .idioma:hover .locked_icon {
        opacity: 0;
    }

    .idioma:hover {
        opacity: 1;
    }

    html {
        font-size: 18px;
    }

    #page-container.header-fixed-top {
        padding-top: 0px;
    }

    .swal2-popup {
        font-size: 1rem;
    }

    .lds-roller {
        display: inline-block;
        position: relative;
        width: 64px;
        height: 64px
    }

    .lds-roller div {
        animation: lds-roller 1.2s cubic-bezier(.5, 0, .5, 1) infinite;
        transform-origin: 32px 32px
    }

    .lds-roller div:after {
        content: " ";
        display: block;
        position: absolute;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #2292CC;
        margin: -3px 0 0 -3px
    }

    .lds-roller div:nth-child(1) {
        animation-delay: -.036s
    }

    .lds-roller div:nth-child(1):after {
        top: 50px;
        left: 50px
    }

    .lds-roller div:nth-child(2) {
        animation-delay: -.072s
    }

    .lds-roller div:nth-child(2):after {
        top: 54px;
        left: 45px
    }

    .lds-roller div:nth-child(3) {
        animation-delay: -.108s
    }

    .lds-roller div:nth-child(3):after {
        top: 57px;
        left: 39px
    }

    .lds-roller div:nth-child(4) {
        animation-delay: -.144s
    }

    .lds-roller div:nth-child(4):after {
        top: 58px;
        left: 32px
    }

    .lds-roller div:nth-child(5) {
        animation-delay: -.18s
    }

    .lds-roller div:nth-child(5):after {
        top: 57px;
        left: 25px
    }

    .lds-roller div:nth-child(6) {
        animation-delay: -.216s
    }

    .lds-roller div:nth-child(6):after {
        top: 54px;
        left: 19px
    }

    .lds-roller div:nth-child(7) {
        animation-delay: -.252s
    }

    .lds-roller div:nth-child(7):after {
        top: 50px;
        left: 14px
    }

    .lds-roller div:nth-child(8) {
        animation-delay: -.288s
    }

    .lds-roller div:nth-child(8):after {
        top: 45px;
        left: 10px
    }

    @keyframes lds-roller {
        0% {
            transform: rotate(0deg)
        }

        100% {
            transform: rotate(360deg)
        }
    }

    .hidePriceBlock {
        transform: translateY(calc(100% - 80px));
    }

    .priceBlock {
        transition: .3s all ease-in-out;
    }
    .transform{transform: none;}
    .rotate-180{transform: rotate(180deg);}
    .border-6{border-width: 5px;}
</style>

<div id="page-content">
    <div id="app" class="container mx-auto pb-16 text-center bg-white">
        <h1 class=" text-center font-semibold text-2xl mt-10">Multiidiomas V2</h1>
        <p class="inline-block px-10 py-4 text-gray-700 text-lg font-medium">Aumenta la accesibilidad y la audiencia de tu sitio ofreciendo una experiencia de navegación multilingüe. Para desbloquear esta poderosa capacidad, simplemente selecciona los idiomas que deseas habilitar en tu sitio web y finaliza la compra.</p>
        <div class="flex justify-between items-center mb-4 mt-8">
            <ul class="flex flex-wrap w-full -mx-4">
                <li v-for="idioma,index in idiomas" :key="index" @click="idioma.locked ? selectIdioma(index) : ''" :class="{'border':!idioma.checked,'border-6 border-green-400 opacity-1':idioma.checked == true,'opacity-50':idioma.locked && !idioma.checked}" class="idioma hover:opacity-1 bg-white cursor-pointer w-1/6 p-3 m-4 px-6  shadow-xl rounded-lg relative">
                    <svg v-if="idioma.locked" class="locked_icon absolute right-0 top-0 lg:m-2 m-1 lg:w-8 lg:h-8 w-6 h-6" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#B1B4B5" d="M376.749 349.097c-13.531 0-24.5-10.969-24.5-24.5V181.932c0-48.083-39.119-87.203-87.203-87.203c-48.083 0-87.203 39.119-87.203 87.203v82.977c0 13.531-10.969 24.5-24.5 24.5s-24.5-10.969-24.5-24.5v-82.977c0-75.103 61.1-136.203 136.203-136.203s136.203 61.1 136.203 136.203v142.665c0 13.531-10.969 24.5-24.5 24.5" />
                        <path fill="#FFB636" d="M414.115 497.459H115.977c-27.835 0-50.4-22.565-50.4-50.4V274.691c0-27.835 22.565-50.4 50.4-50.4h298.138c27.835 0 50.4 22.565 50.4 50.4v172.367c0 27.836-22.565 50.401-50.4 50.401" />
                        <path fill="#FFD469" d="M109.311 456.841h-2.525c-7.953 0-14.4-6.447-14.4-14.4V279.309c0-7.953 6.447-14.4 14.4-14.4h2.525c7.953 0 14.4 6.447 14.4 14.4v163.132c0 7.953-6.447 14.4-14.4 14.4" />
                    </svg>
                    <svg v-if="idioma.locked" class="unlocked_icon opacity-0 absolute right-0 top-0 m-1 lg:m-2 lg:w-8 lg:h-8  w-6 h-6" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#AAB8C2" d="M18 0c-4.612 0-8.483 3.126-9.639 7.371l3.855 1.052A5.999 5.999 0 0 1 18 4a6 6 0 0 1 6 6v10h4V10c0-5.522-4.477-10-10-10" />
                        <path fill="#FFAC33" d="M31 32a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4V20a4 4 0 0 1 4-4h18a4 4 0 0 1 4 4z" />
                    </svg>
                    <svg v-if="!idioma.locked" fill="green" class="absolute right-0 top-0 m-1 lg:m-2 lg:w-8 lg:h-8  w-6 h-6" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 117.72 117.72" style="enable-background:new 0 0 117.72 117.72" xml:space="preserve"><g><path class="st0" d="M58.86,0c9.13,0,17.77,2.08,25.49,5.79c-3.16,2.5-6.09,4.9-8.82,7.21c-5.2-1.89-10.81-2.92-16.66-2.92 c-13.47,0-25.67,5.46-34.49,14.29c-8.83,8.83-14.29,21.02-14.29,34.49c0,13.47,5.46,25.66,14.29,34.49 c8.83,8.83,21.02,14.29,34.49,14.29s25.67-5.46,34.49-14.29c8.83-8.83,14.29-21.02,14.29-34.49c0-3.2-0.31-6.34-0.9-9.37 c2.53-3.3,5.12-6.59,7.77-9.85c2.08,6.02,3.21,12.49,3.21,19.22c0,16.25-6.59,30.97-17.24,41.62 c-10.65,10.65-25.37,17.24-41.62,17.24c-16.25,0-30.97-6.59-41.62-17.24C6.59,89.83,0,75.11,0,58.86 c0-16.25,6.59-30.97,17.24-41.62S42.61,0,58.86,0L58.86,0z M31.44,49.19L45.8,49l1.07,0.28c2.9,1.67,5.63,3.58,8.18,5.74 c1.84,1.56,3.6,3.26,5.27,5.1c5.15-8.29,10.64-15.9,16.44-22.9c6.35-7.67,13.09-14.63,20.17-20.98l1.4-0.54H114l-3.16,3.51 C101.13,30,92.32,41.15,84.36,52.65C76.4,64.16,69.28,76.04,62.95,88.27l-1.97,3.8l-1.81-3.87c-3.34-7.17-7.34-13.75-12.11-19.63 c-4.77-5.88-10.32-11.1-16.79-15.54L31.44,49.19L31.44,49.19z"/></g></svg>
                    <div class="w-full relative lg:px-5 flex-shrink-0">
                        <img class="w-full h-full rounded-full" :src="'<?= $options['templatePath']; ?>/images/'+idioma.prefix+'.svg'">
                    </div>
                    <p class=" text-center font-semibold lg:text-2xl mt-3">{{idioma.name}}</p>
                </li>
            </ul>
        </div>
        <div ref="priceBlock" :class="{'hidePriceBlock' : toggledPrice}" class="priceBlock fixed cursor-pointer right-0 bottom-0 w-64 bg-white rounded-tl-lg border-2 border-black ">
            <div class="py-3 px-10 flex-col items-center" @click="idiomas_selected.length ? toggledPrice = !toggledPrice : ''">
                <svg :class="{'transform rotate-180':!toggledPrice}" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 block mx-auto"  viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M7 11l5 -5l5 5" />
                    <path d="M7 17l5 -5l5 5" />
                </svg>

                <span v-if="!idiomas_selected.length" class="font-semibold text-lg mt-2">Seleccione idiomas</span>
                <span v-if="idiomas_selected.length" class="font-semibold text-lg mt-2">Comprar {{idiomas_selected.length}} idiomas</span>
            </div>
            <div v-if="idiomas_selected.length" class="border-t border-b px-4 py-4">
                <ul class="flex flex-col">
                    <li v-if="!pluginPurchased" class="text-sm pb-2 w-full flex items-center ">
                        <div class="w-1/2">Adquisición de plugin base</div><span class="w-1/2 font-semibold">140€</span>
                    </li>
                    <li class="text-sm pb-2 w-full flex items-center " v-for="idioma in idiomas_selected">
                        <div class="w-1/2">{{idioma.name}}</div><span class="w-1/2 font-semibold">{{idioma.precio}}€</span>
                    </li>
                </ul>
            </div>
            <div v-if="idiomas_selected.length" class="px-4 w-full text-right text-xl my-4 font-semibold">Subtotal: <span>{{totalPrice}}€</span></div>
            <div v-if="idiomas_selected.length" class="px-4">
                <div @click="pay" class="rounded-lg cursor-pointer bg-green-500 text-green-100 text-xl py-2 px-2 my-3 ">Pagar {{totalPrice}}€</div>
            </div>
        </div>
    </div>
</div>
<script src="<?= $options['templatePath']; ?>/js/app.js?t=<?= time(); ?>"></script>