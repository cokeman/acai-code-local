<style>
    
    
    .splitWrapper:not(.expandedView) .saveButtons{right:40px;}
    .splitWrapper:not(.expandedView) .split.right2{width:1px;transform:translateX(1px);}
    .splitWrapper:not(.expandedView) .split.right{width:calc(85% - 20px);}
    .splitWrapper:not(.expandedView) .split.right3 i{transform: rotate(180deg); }
    .splitWrapper:not(.expandedView) #frame{opacity: 0;}
    .splitWrapper:not(.expandedView) .split.right3{width:20px;background-color:#a0aec0;color:#fff;cursor:pointer;}
    @media screen and (min-width:1280px){
        .splitWrapper.expandedView .saveButtons{right:auto;left:330px;bottom:10px;}
        .splitWrapper.expandedView .split.left{width:300px;min-width:300px;}
        .splitWrapper.expandedView .split.right{
            width:35%; box-shadow: inset 0 10px 15px -3px rgba(0, 0, 0, 1), inset 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            min-width:470px;
        }
        .splitWrapper.expandedView .split.right.appVersion{
            width:calc(100% - 414px - 300px);
        }
        .splitWrapper.expandedView .group-component .labelForIframe + button.fa.fa-upload{
            background-color:#637084;color:#fff;
        }
        .splitWrapper.expandedView .referenciado .bg-white{background:none;}
        .splitWrapper.expandedView .group-component .labelForIframe{background:none;color:rgb(255 255 255 / 60%);}
        .splitWrapper.expandedView .split.right::after{
            content: "";
            display: block;
            width: 100%;
            background-color: #191f2b;
            height: 65px;
            bottom: 0px;
            width:35%; 
            position: fixed;
            left: 335px;
            opacity: .9;
            min-width: 470px;
            border-top: solid 1px #718096;
        }
        
        .splitWrapper.expandedView .split.right3{width:20px;background-color:#1a212c;color:#fff;cursor:pointer;}
        .splitWrapper .split.right3:hover{background-color:#2b313e;}

        .splitWrapper.expandedView .split.right2{width:100%;}
        .splitWrapper.expandedView .wrapperContainerPreview.full,.splitWrapper.expandedView .split.right .wrapperContainer{padding:15px;padding-bottom:60px;}
        .splitWrapper.expandedView .split.right .wrapperContainer > p > p{opacity: 0;}

        .splitWrapper.expandedView .split.right{background-color:#1a202c;}


        .splitWrapper.expandedView .split.right .wrapperContainer > h3{color:#fff;}

        .splitWrapper.expandedView .newWrapperContainer .bloque p{display:none;}
        .splitWrapper.expandedView .editWrapperContainer .bloque, .newWrapperContainer .bloque{
            width:50%;
        }

        .splitWrapper.expandedView .group-component:not(.parent-component)>.input-component, .multi-group .group-auto{
            border: solid 1px #718096;
            padding: 15px;
            /*background-color: #718096;*/
            background-color: #71809694;
        }
        .splitWrapper.expandedView .group-component>.button-group>a.active{
            background-color: transparent;
            border: transparent;
            color: transparent;
        }
        .splitWrapper.expandedView #checkVisibility::after{background:#1a202c;color:#fff;border-color:#fff;}
        .splitWrapper.expandedView .group-component>[data-exist]{background-color:#a0aec0;border:none;color:#1a202c;}
        .splitWrapper.expandedView .group-component:not(.parent-component)>.input-component{
            border-radius:5px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .splitWrapper.expandedView .group-component .btn.btn-primary:not(.btn-alt):not(label){
            background-color:#718096 !important; 
            border: solid 1px #718096 !important;
            color:#fff;
            padding: 6px 15px;
        }
        .splitWrapper.expandedView .group-component .labelForIframe + button.fa.fa-upload{right:15px;}
        .splitWrapper.expandedView #frame {
            -ms-zoom: .40;
            -moz-transform: scale(.40);
            -moz-transform-origin: 0 0;
            -o-transform: scale(.40);
            -o-transform-origin: 0 0;
            -webkit-transform: scale(.40);
            -webkit-transform-origin: 0 0;
        }
        .splitWrapper.expandedView #frame {
            width: 250%;
            height: 250%;
        }
        .splitWrapper.expandedView .appVersion #frame {
            -ms-zoom: 1;
            -moz-transform: scale(1);
            -moz-transform-origin: 0 0;
            -o-transform: scale(1);
            -o-transform-origin: 0 0;
            -webkit-transform: scale(1);
            -webkit-transform-origin: 0 0;
        }
        .splitWrapper.expandedView .appVersion #frame {
            width: 100%;
            height: 100%;
        }
    }
    @media screen and (max-width:1279px){
        .splitWrapper .split.right3,.splitWrapper .split.right2{display:none;}
        .splitWrapper:not(.expandedView) .split.right{width:85%;}
    }
</style>
<div class="split right3 flex relative items-center justify-center" onclick="changeViewExpanded()"><i class="fa fa-chevron-right"></i></div>
<div class="split right2 relative bg-white border-theme <?=$isAppVersion ? "appVersion" : "";?>">
    <iframe src="" id="frame" class="w-full h-full <?=$isAppVersion ? "appVersion" : "";?>" frameborder="0"></iframe>
</div> 