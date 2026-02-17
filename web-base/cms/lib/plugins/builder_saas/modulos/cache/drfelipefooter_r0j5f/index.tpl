<footer>
    <section id="footer" class="relative">
        

<? if (@$imagendefondo){?><img class="absolute top-0 left-0 w-full h-full object-center object-cover bg-cover image-box" alt="" style="background-image:url('<? echo CustomCode::imagec(1600,$imagendefondo[0]['urlPath']); ?>')"><?}?>
        

<? if (!@$imagendefondo){?><img class="absolute top-0 left-0 w-full h-full object-center object-cover bg-cover image-box" src="/template/estandar/images/footer.jpg" alt=""><?}?>
        <div class="container mx-auto z-10">
            <div class="lg:flex block pt-20 lg:px-16 pb-20 text-white">
                <div class="lg:w-1/3 lg:text-left text-center lg:p-0 py-4">
                    <p>Dirección:C/Italia nº14, Las Palmas de Gran Canaria 35007</p>
                    <p>Correo electrónico: felipecastillos@gmail.com</p>
                    <p>Teléfono: +34 687 655 291 ó +34 928 368 696</p>
                </div>
                <div class="lg:w-1/3 lg:p-0 py-4">
                    

<? if (@$logo){?><img src="<? echo CustomCode::imagec(150,$logo[0]['urlPath']);?>" class="m-auto h-24"><?}?>
                    

<? if (!@$logo){?><img src="/template/estandar/images/logo.png" class="m-auto h-24"><?}?>
                </div>
                <div class="lg:w-1/3 text-center lg:p-0 py-4">
                    <div class="lg:flex block lg:justify-end">
                        

<? if (@$texto){?><p class="lg:mr-2 my-auto text-center"><? echo nl2br($texto);?></p><?}?>
                        

<? if (@$enlace_anchor){?><a href="<? echo @$enlace; ?>" class="inline-block rounded border text-black bg-white border-white hover:border-black py-2 px-8 hover:text-white hover:bg-black"><? echo @$enlace_anchor;?></a><?}?>
                    </div>
                    <div class="lg:flex block lg:justify-end">
                        <span class="hover:text-black"><i class="lg:text-3xl py-8 pl-4 text-2xl fab fa-facebook-square"></i></span>
                        <span class="hover:text-black"><i class="lg:text-3xl py-8 pl-4 text-2xl fab fa-instagram"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="pol">
        <div class="flex py-8 bg-black text-white justify-center">
            <p class="mx-4"><a href="#" class="hover:text-brown">AVISO LEGAL</a></p>
            <p class="mx-2">|</p>
            <p class="mx-2"><a href="#" class="hover:text-brown">POLÍTICA DE COOKIES</a></p>
            <p class="mx-2">|</p>
            <p class="mx-2"><a href="#" class="hover:text-brown">CONTACTO</a></p>
        </div>
    </section>
</footer>