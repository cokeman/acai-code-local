<div class="body">
    <div c-if="video">
        <div c-hidden="true" class="absolute top-0 left-0 w-full h-full object-cover object-center" data-field-type="upload" data-field-label="Video" alt=""></div>
    </div>
    <div class="golden-stage wf-section">
        <img c-if="logo" data-field-type="upload" data-field-label="Logo" width="600" alt="Logo Playa Padre" class="w-32 scorpio-logo" style="width:200px" />
        <div data-autoplay="true" data-loop="true" data-wf-ignore="true" class="background-video-hero w-background-video w-background-video-atom">
            <video autoplay="" loop=""  muted="" playsinline="" data-wf-ignore="true" data-object-fit="cover">
                <source src="{{video.0.urlPath}}" data-wf-ignore="true" />
                <source src="{{video.0.urlPath}}" data-wf-ignore="true" />
            </video>
        </div>
    </div>
</div>
