<div class="body">
    
{% if video %}
<div>
        
    </div>
{% endif %}

    <div class="golden-stage wf-section">
        
{% if logo %}
<img src="{{ logo.0.urlPath | imagec(1600) }}" style="width:200px" class="w-32 scorpio-logo" alt="Logo Playa Padre" width="600">
{% endif %}

        <div data-autoplay="true" data-loop="true" data-wf-ignore="true" class="background-video-hero w-background-video w-background-video-atom">
            <video autoplay="" loop="" muted="" playsinline="" data-wf-ignore="true" data-object-fit="cover">
                <source src="{{video.0.urlPath}}" data-wf-ignore="true">
                <source src="{{video.0.urlPath}}" data-wf-ignore="true">
            </video>
        </div>
    </div>
</div>
