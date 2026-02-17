<section id="id_{{ section_id }}" class="relative overflow-hidden nepe">
    <img class="w-full h-full object-cover object-center lazyload" data-src="/template/estandar/images/hondas2.svg" alt="nubes">
    <div class="relative bg-main-color">
        <div class="relative container max-w-7xl px-6 2xl:px-0 py-10 lg:py-20 mx-auto">
            {% if titulo %}
            
                            {% set titulo_tag = titulo_tag ?: 'P' %}
                            {{ ('<' ~ titulo_tag ~ ' class="sweatgraphy text-main-color-dark text-5xl sm:text-6xl md:text-4xl lg:text-5xl xl:text-6xl 2xl:text-7xl max-w-2xl mx-auto text-center" >' ~ (titulo ? titulo : '') ~ ('PC8=' | base64_decode) ~ titulo_tag ~ '>') | raw }}
                        
            {% endif %}
            
{% if records %}
<div class="c-tns-wrapper sm:-mx-10 lg:-mx-6 mt-4" data-responsive="sm:2, lg:4" data-autoplay-timeout="8000" data-nav="true">
                <ul class="c-tns-container">
                    
{% for record in records %} {% set index = loop.index0 %}
<li class="flex flex-col items-center sm:px-10 lg:px-6 py-6">
                        
{% if record.imagen %}
<div class="p-1/10 relative rounded-full overflow-hidden w-11/12 mx-auto">
                            <img data-src="{{ record.imagen.0.urlPath | imagec(800) }}" alt="{{imagen.0.info1}}" class="absolute top-0 left-0 w-full h-full object-cover object-center lazyload">
                        </div>
{% endif %}

                        
{% if record.titulobloque %}
<div class="sweatgraphy text-main-color-dark text-3xl line-clamp-1 text-center mt-10">
                        {% if record.titulobloque %} 
 
                            {{ record.titulobloque | raw }} 
 
                        {% else %} 
 
                            {{ "" | raw }} 
 
                        {% endif %}
                    </div>
{% endif %}

                        
{% if record.textobloque %}
<div class="text-main-color-dark text-center mt-4">
                        {% if record.textobloque %} 
 
                            {% if record.textobloque | isHTML %}
                                {{ record.textobloque | raw }} 
 
                            {% else %}
                                {{ record.textobloque | nl2br }} 
 
                            {% endif %}
                        {% else %} 
 
                            {{ "" | nl2br }} 
 
                        {% endif %}
                    </div>
{% endif %}

                    </li>
{% endfor %}

                </ul>
            </div> {% else %} <div class="c-tns-wrapper sm:-mx-10 lg:-mx-6 mt-4" data-responsive="sm:2, lg:4" data-autoplay-timeout="8000" data-nav="true">
                <ul class="c-tns-container">
                    {% set productos = 'productos' | get('visible=1 and destacado=1',null,null,{}) %}
 {% for producto in productos %} 
<li class="sm:px-10 lg:px-6 py-6">
                        {{ 'bloqueproducto_i7aunn' | module({"producto":producto})|raw }}
                    </li>
 {% endfor %} 

                </ul>
            </div>
{% endif %}

            
        </div>
    </div>
    <img class="w-full h-full object-cover object-center lazyload rotate-180" data-src="/template/estandar/images/hondas2.svg" alt="nubes">
</section>