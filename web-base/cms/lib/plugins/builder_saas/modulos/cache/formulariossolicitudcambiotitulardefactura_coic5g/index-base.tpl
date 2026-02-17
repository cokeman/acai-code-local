<section class="bg-white">
    <div class="container mx-auto max-w-6xl px-6 py-10 lg:py-16">
        <div class="flex flex-col-reverse lg:flex-row justify-center -mx-2">
            <div c-for="configuracion in configuracion" class="flex flex-col items-centerw-full lg:w-7/12 px-2 mt-6 lg:mt-0">
                <div class="px-6">
                    <span c-if="titulo is not empty" data-field-type="headfield" data-field-label="Titulo" class="text-yellow-100 text-3xl lg:text-4xl font-light text-center leading-tight px-2"></span>
                    <div c-if="textolargo is not empty" data-field-type="textbox" data-field-label="Texto Largo" class="text-gray-600 text-lg lg:text-2xl text-center mt-6"></div>
                </div>
                <c-form class="mt-6" method="post" tableName="'solicitudes_cambio_titular'" sendToClient="'email'" sendTo="configuracion.correo_admin" honeypot="true" redirectTo="/gracias/" enctype="multipart/form-data" mailRecord="['correos','SOLICITUD_CAMBIO_TITULAR']">
                    <div class="flex flex-wrap -mx-4">
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Nombre*' | translate}}
                                <input type="text" name="nombre" placeholder="Escribe tu nombre" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Apellidos*' | translate}}
                                <input type="text" name="apellidos" placeholder="Escribe tus apellidos" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Email*' | translate}}
                                <input type="email" name="email" placeholder="Escribe tu email" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Teléfono*' | translate}}
                                <input type="text" name="telefono" placeholder="Escribe tu teléfono" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full px-4 lg:py-3 border-b">
                            <p class="text-lg">Datos originales de la factura:</p>
                        </div>
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Factura*' | translate}}
                                <input type="text" name="factura" placeholder="Número de factura" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'DNI*' | translate}}
                                <input type="text" name="dni_original" placeholder="Escribe tu DNI" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/3 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Importe*' | translate}}
                                <input type="text" name="importe_factura" placeholder="Importe de la factura" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/3 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Tipo de cliente*' | translate}}
                                <select name="tipo_cliente" required  class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2">
                                    <option selected disabled>Selecciona tipología de cliente</option>
                                    <option value="Empresa">Empresa</option>
                                    <option value="Cliente Final">Cliente Final</option>
                                    <option value="Profesional">Profesional</option>
                                    <option value="Profesional Revendedor">Profesional Revendedor</option>
                                    <option value="Empresa Revendedora">Empresa Revendedora</option>
                                </select>
                            </label>
                        </div>
                        <div class="w-full lg:w-1/3 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Fecha de factura*' | translate}}
                                <input id="date" type="date" name="fecha_factura" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full px-4 lg:py-3 border-b">
                            <p class="text-lg">Datos nuevos de la factura:</p>
                        </div>
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'DNI*' | translate}}
                                <input type="text" name="dni_nuevo" placeholder="Indique el DNI" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Nombre*' | translate}}
                                <input type="text" name="nombre_nuevo" placeholder="Indique el nombre" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Dirección*' | translate}}
                                <input type="text" name="direccion_nuevo" placeholder="Indique los dirección" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/2 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Código postal*' | translate}}
                                <input type="text" name="codigo_postal_nuevo" placeholder="Indique el código postal" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/3 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'País*' | translate}}
                                <select name="pais_nuevo" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2">
                                    <option value="ES" selected>España</option>
                                </select>
                            </label>
                        </div>
                        <div class="w-full lg:w-1/3 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Provincia*' | translate}}
                                <input type="text" name="provincia_nuevo" placeholder="Indique la provincia" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <div class="w-full lg:w-1/3 px-4 lg:py-3">
                            <label class="text-gray-900 text-sm lg:text-base font-medium">
                                {{'Localidad*' | translate}}
                                <input type="text" name="localidad_nuevo" placeholder="Indique la localidad" required class="bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2" />
                            </label>
                        </div>
                        <script>
                            /*
                            let selector_provincia = document.querySelector('._selector_provincia');
                            let selector_localidad = document.querySelector('._selector_localidad');
                            selector_provincia.addEventListener('change', () => {
                                let option_selected = selector_provincia.value;
                                option_selected = selector_provincia.querySelector('option[value="'+option_selected+'"]').getAttribute('data-num');
                                if(option_selected) {
                                    CmsApi.hook('/hooks/search/', {params:JSON.stringify({'tab':'1f0948b3c55b6779947ecaecf21354df','filter_parent': option_selected})}).then(respuesta => 
                                    {
                                        if(respuesta && respuesta.resultado) {
                                            selector_localidad.innerHTML = '<option selected disabled>Indique la localidad</option>';
                                            respuesta.resultado.forEach(each => {
                                                const option = document.createElement("option");
                                                option.value = each.name;
                                                option.textContent = each.name; // label visible
                                                selector_localidad.appendChild(option);
                                            })
                                        }
                                    });
                                }
                            });
                            */
                        </script>
                        <!-- <captcha class="flex justify-satrt w-full text-center px-4 my-3"></captcha> -->
                        <div class="px-6 mt-2">
                            <label class="w-full flex items-center py-1">
                                <input value="1" name="newsletter" type="checkbox" class="form-radio pr-4 mr-4 rounded-full" />
                                <span class="text-gray-600 text-xs lg:text-sm ml-2">{{ 'Deseo recibir notificaciones de banana por eMail' | translate | raw }}</span>
                            </label>
                            <label class="w-full flex items-center py-1">
                                <input value="1" name="rgpd" required type="checkbox" class="form-radio pr-4 mr-4 rounded-full" />
                                <span class="text-gray-600 text-xs lg:text-sm ml-2">{{ 'Acepto las condiciones legales' | translate | raw }}</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="flex justify-center items-center bg-yellow-100 hover:bg-white text-white hover:text-yellow-100 md:text-lg lg:text-xl font-medium border border-yellow-100 rounded-full px-6 py-2 mt-6 mx-auto transition3s">{{ 'Enviar solicitud' | translate }}</button>
                </c-form>
            </div>

            <div class="w-5/12 px-2 hidden lg:block" c-if="imagen">
                <div class="p-1/8 relative">
                    <img class="absolute top-0 left-0 w-full h-full object-cover object-center" data-field-type="upload" data-field-label="Imagen" data-field-info1="titulo" data-field-width="1400" alt="" />
                </div>
            </div>
        </div>
    </div>
</section>
