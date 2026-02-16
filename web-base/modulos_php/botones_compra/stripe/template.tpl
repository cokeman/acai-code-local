<script src="https://js.stripe.com/v3/"></script>
<form action="<?=$defaults["submit"]["action"];?>" method="post" id="payment-form">
    <div id="loadingStripe">
        <div class="spinner">
            <div class="rect1"></div>
            <div class="rect2"></div>
            <div class="rect3"></div>
            <div class="rect4"></div>
            <div class="rect5"></div>
        </div>
    </div>
    <div id="camposStripe" class="row">
        <? 
        if (@$defaults["formulario"]){
            ?><div class="col-md-12"><?
            echo t(array("contenido" => "{FORMULARIO_".strtoupper($defaults["formulario"])."}"), "contenido", array("tipo" => "fields"));
            ?></div><?
        }
        ?>
        <? if (@$defaults["template"]["fields"]){?>
            <? foreach($defaults["template"]["fields"] as $field){?>
                <? if (@$field["type"]=="hidden"){?>
                    <input type="<?=$field["type"];?>" name="dynamicForm[<?=parsea_enlace($field["name"]);?>]" <?=$field["required"] ? 'required=""' : '';?> value="<?=$field["value"];?>">
                <? }else{?>
                    <div class="col-md-12 text-left">
                        <div class="form-group margin-0">
                            <label for=""><?=$field["label"];?>*</label>
                            <input class="form-control" type="<?=$field["type"];?>" value='<?=$field["value"];?>' placeholder="<?=$field["label"];?>" name="dynamicForm[<?=parsea_enlace($field["name"]);?>]" <?=$field["required"] ? 'required=""' : '';?>>
                        </div>
                    </div>
                <?}?>
                
            <? }?>
        <? }?>
        <? if (@$defaults["template"]["label"]){?>
        <div class="separa-20"></div>
        <div class="col-md-12 text-left">
            <div class="form-group margin-0">
                <label for="card-element">
                    <?=$defaults["template"]["label"];?>
                </label>
            </div>
        </div>
        <? }?>
        <div class="clearfix"></div>
        <div class="col-md-12 text-left">
            <div class="form-row emailStr relative">
                <input type="email" name="dynamicForm[email]" id="stripeEmail" placeholder="<?=t_var("Correo electrónico");?>" class="StripeElement stripeCorreo full-width" required>
            </div>
        </div>
        <div class="separa-20"></div>
        <div class="col-md-12 text-left">
            <div class="form-row">
                <div id="card-element">
                    <!-- a Stripe Element will be inserted here. -->
                </div>
            </div>
        </div>
        <div class="separa-20"></div>
        <? if (@$defaults["template"]["checks"]){?>
        <div class="separa-20"></div>
        <div class="col-md-12 text-left">
            <? foreach($defaults["template"]["checks"] as $check):?>
            <div class="form-group margin-0">
                <div class="checks">
                    <div class="switch">
                        <input class="cmn-toggle cmn-toggle-round-flat" id="<?=$check["name"];?>" name="<?=$check["name"];?>" type="checkbox" <?=@$check["required"] ? "required" : "";?> >
                        <label for="<?=$check["name"];?>"></label>
                    </div>
                    <div><?=@$check["label"];?><?=@$check["required"] ? "*" : "";?></div>
                    <div class="clearfix"></div>
                </div>
            </div>
            <div class="separa-20"></div>
            <? endforeach;?>
            
            
        </div>
        <? }?>
        
        <div class="col-md-12 text-center">
           
            <!-- Used to display form errors -->
            <div id="card-errors" role="alert"></div>
            <div id="card-errors-2" role="alert"><?=t_var("Debes marcar los campos requeridos");?></div>
        
            <button id="botonCompra" type="button" class="<?=$defaults["template"]["clase"];?>"><?=$defaults["template"]["texto"];?></button>    
        </div>
        <div class="separa-10"></div>
        
    </div>
</form>

<style>
    .cmn-toggle{position:absolute;margin-left:-9999px;width:0px;height:0px;z-index:-1;}.cmn-toggle+label{display:block;position:relative;cursor:pointer;outline:0;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}input.cmn-toggle-round-flat+label{padding:2px;width:60px;height:30px;background-color:#ddd;-webkit-border-radius:30px;-moz-border-radius:30px;-ms-border-radius:30px;-o-border-radius:30px;border-radius:30px;-webkit-transition:background .4s;-moz-transition:background .4s;-o-transition:background .4s;transition:background .4s}input.cmn-toggle-round-flat+label:after,input.cmn-toggle-round-flat+label:before{display:block;position:absolute;content:""}input.cmn-toggle-round-flat+label:before{top:2px;left:2px;bottom:2px;right:2px;background-color:#fff;-webkit-border-radius:30px;-moz-border-radius:30px;-ms-border-radius:30px;-o-border-radius:30px;border-radius:30px;-webkit-transition:background .4s;-moz-transition:background .4s;-o-transition:background .4s;transition:background .4s}input.cmn-toggle-round-flat+label:after{top:4px;left:4px;bottom:4px;width:22px;background-color:#ddd;-webkit-border-radius:22px;-moz-border-radius:22px;-ms-border-radius:22px;-o-border-radius:22px;border-radius:22px;-webkit-transition:margin .4s,background .4s;-moz-transition:margin .4s,background .4s;-o-transition:margin .4s,background .4s;transition:margin .4s,background .4s}input.cmn-toggle-round-flat:checked+label{background-color:#f3ab00}input.cmn-toggle-round-flat:checked+label:after{margin-left:30px;background-color:#f3ab00}input.cmn-toggle-yes-no+label{padding:2px;width:130px;height:60px}input.cmn-toggle-yes-no+label:after,input.cmn-toggle-yes-no+label:before{display:block;position:absolute;top:0;left:0;bottom:0;right:0;color:#fff;font-size:14px;text-align:center;line-height:60px}input.cmn-toggle-yes-no+label:before{background-color:#ddd;content:attr(data-off);-webkit-transition:-webkit-transform .5s;-moz-transition:-moz-transform .5s;-o-transition:-o-transform .5s;transition:transform .5s;-webkit-backface-visibility:hidden;-moz-backface-visibility:hidden;-ms-backface-visibility:hidden;-o-backface-visibility:hidden;backface-visibility:hidden}input.cmn-toggle-yes-no+label:after{background-color:#8ce196;content:attr(data-on);-webkit-transition:-webkit-transform .5s;-moz-transition:-moz-transform .5s;-o-transition:-o-transform .5s;transition:transform .5s;-webkit-transform:rotateY(180deg);-moz-transform:rotateY(180deg);-ms-transform:rotateY(180deg);-o-transform:rotateY(180deg);transform:rotateY(180deg);-webkit-backface-visibility:hidden;-moz-backface-visibility:hidden;-ms-backface-visibility:hidden;-o-backface-visibility:hidden;backface-visibility:hidden}input.cmn-toggle-yes-no:checked+label:before{-webkit-transform:rotateY(180deg);-moz-transform:rotateY(180deg);-ms-transform:rotateY(180deg);-o-transform:rotateY(180deg);transform:rotateY(180deg)}input.cmn-toggle-yes-no:checked+label:after{-webkit-transform:rotateY(0);-moz-transform:rotateY(0);-ms-transform:rotateY(0);-o-transform:rotateY(0);transform:rotateY(0)}.switch{width:70px;display:block;float:left}.switch+div{display:block;text-align:left;font-size:14px;height:33px;line-height:18px;color:#666}@media screen and (max-width:768px){.switch{margin-bottom:25px}.switch+div{line-height:12px;font-size:10px;}}
    #card-errors-2{font-size:14px;color:red;display:none;}
    #card-errors{font-size:14px;color:red}
</style>