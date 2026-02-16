<?
require_once dirname(__FILE__)."/editor/LandingEditor.php";
if (!@$var["recordNum"]) continue;
if (!@$field) continue;
$data = dame_registros("builder_custom","num=".intval($var["recordNum"]),"num desc",1)[0];
if (!@$data) continue;
if (@$data){
    ?>
    <div class="wrapper landingSection seccion<?=$var["recordNum"];?> landingSection">
        <? echo LandingEditor::render(t($data,$field),$var["recordNum"]);?>
    </div>
    <link rel="stylesheet" href="/template/estandar/css/bootstrap.min.css">
    <link rel="stylesheet" href="/template/estandar/style.css?timestamp=<?=time();?>">
    <link rel="stylesheet" href="/template/estandar/modulos/seccion-custom/style.css?timestamp=<?=time();?>">
    <style>
        .wrapper.landingSection{min-height:100px;width:100%;position:relative;overflow:hidden;}
        .wrapper.landingSection .landing-section>div{width:100%;max-width:1440px;margin:0 auto;}
        .wrapper.landingSection .element{padding:10px;min-width:140px;min-height:140px;position: absolute;overflow: hidden;display:flex;align-items: center;justify-content: center;            }
        .wrapper.landingSection .element>span,.wrapper.landingSection .element>div{width:100%;}
        .wrapper.seccion{min-height:100px;width:100%;position:relative;}
        @media screen and (max-width: 1440px) {
            .wrapper.landingSection .landing-section>div{max-width:992px;}
        }
        @media screen and (max-width: 992px) {
            .wrapper.landingSection .landing-section>div{max-width:768px;}
        }
        
        @media screen and (max-width: 768px) {
            .wrapper.landingSection .landing-section>div{max-width:320px;}
        }
    </style>

    <?
}
?>