<?php
global $CURRENT_USER;
?>
<link rel="stylesheet" href="<?=$options['templatePath'];?>/css/metas.css">
<script>
    window.addEventListener('load', function() {
        function strip_tags(str) {
            return str.replace(/(<([^>]+)>)/ig,"");
        }

        const enlace = document.querySelector('.form-group input[name="enlace"]');
        const mainField = document.querySelector('.form-group input[name="title"]')
                        || document.querySelector('.form-group input[name="titulo_alternativo"]')
                        || document.querySelector('.form-group input[name="name"]')
                        || document.querySelector('.form-group input[name="nombre"]')
                        || document.querySelector('.form-group input[name="titulo"]')
                        || document.querySelector('.form-group:not(.oculto) input[type="text"]');
        
        const descriptionField = document.querySelector('.form-group [name="descripcion_corta"]')
                        || document.querySelector('.form-group [name="subtitulo"]')
                        || document.querySelector('.form-group [name="subtitle"]')
                        || document.querySelector('.form-group [name="descripcion"]')
                        || document.querySelector('.form-group [name="content"]');

        const pageTitle = document.querySelector('input[name="titulo_de_pagina"]');
        const pageDescription = document.querySelector('[name="metatag_descripcion"]');

        function render() {
            return false;
            if (!enlace) return false;
            if (!pageTitle && !mainField) return false;

            let metatitle = pageTitle && pageTitle.value.length > 0 ? pageTitle : mainField;
            metatitle = strip_tags(metatitle.value);
            let metadescription = pageDescription && pageDescription.value.length > 0 ? pageDescription : descriptionField;
            if (metadescription) {
                metadescription = strip_tags(metadescription.value);
            }
            else {
                metadescription = 'Sin descripción';
            }

            const domain = '<?=$CURRENT_USER['domain']['domain'];?>';
            const fulldomain = 'https://<?=$CURRENT_USER['domain']['domain'];?>/';

            const html = `
            <div class="index-metadata__content">
                <div id="google" class="metadata-group__display is-active">
                    <h4 class="metadata-group__title"><span>Google</span></h4>
                    <div class="card-seo-google">
                        <span class="card-seo-google__title js-preview-title">${metatitle}</span>
                        <div class="card-seo-google__url">
                        <span class="card-seo-google__url-title js-preview-domain">${fulldomain}</span>
                        <span class="card-seo-google__url-arrow"></span>
                        </div>
                        <span class="card-seo-google__description js-preview-description">${metadescription}</span>
                    </div>
                </div>


                <div id="facebook" class="metadata-group__display is-active">
                    <h4 class="metadata-group__title"><span>Facebook</span></h4>
                    <div class="card-seo-facebook">
                        <div class="card-seo-facebook__image js-preview-image" style="background-image: url(&quot;&quot;);"></div>
                        <div class="card-seo-facebook__text">
                        <span class="card-seo-facebook__link js-preview-domain">${domain}</span>
                        <div class="card-seo-facebook__content">
                            <div style="margin-top:5px">
                            <div class="card-seo-facebook__title js-preview-title">${metatitle}</div>
                            </div>
                            <span class="card-seo-facebook__description js-preview-description">${metadescription}</span>
                        </div>
                        </div>
                    </div>
                </div>

                <div id="twitter" class="metadata-group__display is-active">
                    <h4 class="metadata-group__title"><span>Twitter</span></h4>
                    <div class="card-seo-twitter">
                        <div class="card-seo-twitter__image js-preview-image" style="background-image: url(&quot;&quot;);"></div>
                        <div class="card-seo-twitter__text">
                        <span class="card-seo-twitter__title js-preview-title">${metatitle}</span>
                        <span class="card-seo-twitter__description js-preview-description">${metadescription}</span>
                        <span class="card-seo-twitter__link js-preview-domain">${domain}</span>
                        </div>
                    </div>
                </div>
            </div>`;

            $('.metatags-block').remove();
            $('#page-content>.row>.col-md-12').append(`
            <div class="block metatags-block">
                <div class="block-title">
                    <h2>Previsualización de metatags</h2>
                </div>
                <div>
                    ${html}
                </div>
            </div>`);
        }
        //render();

        if (enlace) enlace.addEventListener('change', render);
        if (mainField) mainField.addEventListener('change', render);
        if (descriptionField) descriptionField.addEventListener('change', render);
        if (pageTitle) pageTitle.addEventListener('change', render);
        if (pageDescription) pageDescription.addEventListener('change', render);
    });
</script>