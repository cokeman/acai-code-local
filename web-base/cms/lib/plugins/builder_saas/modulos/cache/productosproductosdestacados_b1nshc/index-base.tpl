<section id="id_{{ section_id }}" class="relative overflow-hidden nepe">
    <img class="w-full h-full object-cover object-center lazyload" data-src="/template/estandar/images/hondas2.svg" alt="nubes"/>
    <div class="relative bg-main-color">
        <div class="relative container max-w-7xl px-6 2xl:px-0 py-10 lg:py-20 mx-auto">
            {% if titulo %}
            <div data-field-type="headfield" data-field-label="Titulo" class="sweatgraphy text-main-color-dark text-5xl sm:text-6xl md:text-4xl lg:text-5xl xl:text-6xl 2xl:text-7xl max-w-2xl mx-auto text-center"></div>
            {% endif %}
            <div c-if="records" class="c-tns-wrapper sm:-mx-10 lg:-mx-6 mt-4" data-responsive="sm:2, lg:4" data-autoplay-timeout="8000" data-nav="true">
                <ul class="c-tns-container">
                    <li data-field-type="multiv2" data-field-label="Records" class="flex flex-col items-center sm:px-10 lg:px-6 py-6">
                        <div c-if="record.imagen" class="p-1/10 relative rounded-full overflow-hidden w-11/12 mx-auto">
                            <img class="absolute top-0 left-0 w-full h-full object-cover object-center lazyload" data-field-type="upload" data-field-label="Imagen" data-lazy="true" data-field-info1="titulo" data-field-width="800" alt="{{imagen.0.info1}}">
                        </div>
                        <div c-if="record.titulobloque" data-field-type="textfield" data-field-label="Titulo Bloque" class="sweatgraphy text-main-color-dark text-3xl line-clamp-1 text-center mt-10"></div>
                        <div c-if="record.textobloque" data-field-type="textbox" data-field-label="Texto Bloque" class="text-main-color-dark text-center mt-4"></div>
                    </li>
                </ul>
            </div>
            <div c-else class="c-tns-wrapper sm:-mx-10 lg:-mx-6 mt-4" data-responsive="sm:2, lg:4" data-autoplay-timeout="8000" data-nav="true">
                <ul class="c-tns-container">
                    <li c-for="producto in productos" c-where="'visible=1 and destacado=1'" class="sm:px-10 lg:px-6 py-6">
                        <bloqueproducto :producto="producto"></bloqueproducto>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <img class="w-full h-full object-cover object-center lazyload rotate-180" data-src="/template/estandar/images/hondas2.svg" alt="nubes"/>
</section>