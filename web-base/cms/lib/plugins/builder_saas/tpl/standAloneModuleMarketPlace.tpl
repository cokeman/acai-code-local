<?
global $CURRENT_USER;

showHeader(true); 

if (!@$CURRENT_USER["licencia"]){
    showInterface("default/licence.php",false);
    die();
}

require_once __DIR__."/../funciones.php";

$apartado = CocoDB::get("apartados","enlace = '/'","num desc",1)[0];
if (!@$apartado) die("Error. No encuentro el apartado de inicio y lo necesito para funcionar");

?>
<div id="loading"></div>
<div id="moduleMarketPlaceWrapper" class="w-full h-screen bg-gray-900">
    <module-marketplace ref="market" v-if="searchModules" :only_market="true" :is_super_admin="<?=@$CURRENT_USER["isSuperAdmin"] ? "true" : "false";?>" @buy-module="comprarModulo" @import-module="importarModulo" :modules="searchModules"></module-marketplace>
    
</div>

<style type="text/css">
    #page-container.header-fixed-top{padding: 0px;}
    #newModulesVue{width: 100%;}
</style>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script src="<?=h($options["templatePath"]."/js/modulos.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/campos.js");?>"></script>

<script type="text/javascript">
    var MENU = "<?=@$apartado["tableName"];?>";
    var NUM = <?=@$apartado["num"];?>;
    var TEMPLATE = "<?=$options["templatePath"];?>";
    var websiteDomain = "<?=CDN_MODULES_WEBSITE_DOMAIN;?>";
    var APARTADO_DATA = {};
    var CURRENT_USER = <?=json_encode($CURRENT_USER_filtrado);?>;
    var loadingNode = document.getElementById("loading");

    function startLoading(){
    
        loadingNode.style.opacity = 1;
        loadingNode.style.pointerEvents = "all";
    }

    function stopLoading(){
        loadingNode.style.opacity = 0;
        loadingNode.style.pointerEvents = "none";
    }

    var marketPlaceModules = new Vue({
        el:"#moduleMarketPlaceWrapper",
        data:{
            modules:[],
            localModules:[]
        },
        computed:{
            searchModules(){
                if (!this.modules.length) return [];
                var result = this.modules;
                result = result.filter(rec => !rec.MJMLModule);
                return result;
            }
        },
        components:{
            'module-marketplace':httpVueLoader('/lib/plugins/builder_saas/tpl/componentes/moduleMarketPlace.vue?timestamp=' + (new Date().getTime()))
        },
        mounted(){
            this.init();
        },
        methods:{
            init(){
                this.downloadData(`admin.php?menu=${MENU}&action=edit&num=${NUM}&getWebModules=1`,(json) => {
                    this.localModules = json;

                    url = `getWebModules=1&domain=<?=CDN_MODULES_WEBSITE;?>&auxVar=1`;
                    
                    this.downloadData(`admin.php?menu=${MENU}&action=edit&num=${NUM}&` + url,(json) => {
                        
                        for (let index in json){
                    
                            var mod = json[index];
                            
                            var module = {};
                            module.builder = json[index];
                            module.builder.id = index;
                            module.data = [];
                            module.referencias = json[index].referencias;
                            
                            this.modules.push({
                                id:mod.id,
                                module:new Module(module,{left:document.createElement('div'),right:document.createElement('div')},true),
                                price:mod.price
                            });
                            
                        }

                        this.modules = this.modules.sort(this.compare);
                        this.modules = this.modules.sort((a,b) => { return a.onlyAdminModule ? 1 : -1; });
                        // this.modules = Object.values(json);
                    });
                });
            },
            compare:function( a, b,field = "fullLabel" ) {
              if ( a.fullLabel < b.fullLabel ){
                return -1;
              }
              if ( a.fullLabel > b.fullLabel ){
                return 1;
              }
              return 0;
            },
            tengoElModulo(module){
                return Object.keys(this.localModules).indexOf(module.id) > -1;
            },
            comprarModulo(module){ 
                swal({
                    title:"Comprar módulo",
                    text:"¿Deseas comprar el módulo seleccionado?. La página del proveedor seguro Stripe se abrirá en una nueva ventana para que puedas realizar el pago. Una vez completado recarga la página para que aparezca tu compra",
                    icon:"warning",
                    buttons:{
                      cancel: {
                        text: "No",
                        value: null,
                        visible: true,
                        className: "btn btn-default",
                        closeModal: true,
                      },
                      confirm: {
                        text: "SI",
                        value: true,
                        visible: true,
                        className: "btn btn-primary",
                        closeModal: true
                      }
                    }
                }).then((value) => {
                    if (value){
                        var mod = this.modules.find(rec => rec.id == module.id);
                        if (!mod) alert("Error al encontrar el modulo");
                        
                        document.getElementById("loading").style.opacity = 1;
                        document.getElementById("loading").style.pointerEvents = "all";

                        this.downloadData(`admin.php?menu=${MENU}&action=edit&num=${NUM}&payModule=` + mod.id + `&timestamp=` + (new Date().getTime()),(json) => {

                            document.getElementById("loading").style.opacity = 0;
                            document.getElementById("loading").style.pointerEvents = "auto";

                            if (json.data[0]) window.location.href = json.data[0];
                        });
                        
                        return true;
                    }else{
                        
                        return false;
                    }
                });

            },
            importarModulo(module){ 
                
                swal({
                    title:"Importar módulo",
                    text:"¿Deseas importar el módulo?",
                    icon:"warning",
                    buttons:{
                      cancel: {
                        text: "No",
                        value: null,
                        visible: true,
                        className: "btn btn-default",
                        closeModal: true,
                      },
                      confirm: {
                        text: "SI",
                        value: true,
                        visible: true,
                        className: "btn btn-primary",
                        closeModal: true
                      }
                    }
                }).then((value) => {
                    if (value){
                        var mod = this.modules.find(rec => rec.id == module.id);
                        if (!mod) {
                            alert("Error al encontrar el modulo");
                            return;
                        }
                        console.log(mod);
                        
                        mod.module.sendModuleToWeb('<?=CDN_MODULES_WEBSITE_DOMAIN;?>').then((data) => {
                            this.category = 0;

                            swal({
                                title:"OK!",
                                text:"'El módulo ha sido importado'",
                                icon:"success",
                                button : false,
                                timer:500
                            });

                            window.location.reload();

                        });
                        
                        return true;
                    }else{
                        
                        return false;
                    }
                });
            }

        }
    });
</script>
<?