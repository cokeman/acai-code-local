<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* index.twig */
if (!class_exists('__TwigTemplate_95f4877b6d6bd0af520138a29d5efbd2ce0c2f239906cec6bb5b5e1d66b4553a')){
class __TwigTemplate_95f4877b6d6bd0af520138a29d5efbd2ce0c2f239906cec6bb5b5e1d66b4553a extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    public function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<section class=\"bg-white\">
    <div class=\"container mx-auto max-w-6xl px-6 py-10 lg:py-16\">
        <div class=\"flex flex-col-reverse lg:flex-row justify-center -mx-2\">
            ";
        // line 4
        $context["configuracion"] = CocoDB::get("configuracion", "", null, null, []);
        // line 5
        echo " ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($context["configuracion"]);
        foreach ($context['_seq'] as $context["_key"] => $context["configuracion"]) {
            echo " 
<div class=\"flex flex-col items-centerw-full lg:w-7/12 px-2 mt-6 lg:mt-0\">
                <div class=\"px-6\">
                    
                            ";
            // line 9
            $context["titulo_tag"] = ((($context["titulo_tag"] ?? null)) ? (($context["titulo_tag"] ?? null)) : ("P"));
            // line 10
            echo "                            ";
            echo (((((("<" . ($context["titulo_tag"] ?? null)) . " class=\"text-yellow-100 text-3xl lg:text-4xl font-light text-center leading-tight px-2\" c-if=\"titulo is not empty\" >") . ((($context["titulo"] ?? null)) ? (($context["titulo"] ?? null)) : (""))) . base64_decode("PC8=")) . ($context["titulo_tag"] ?? null)) . ">");
            echo "
                        
                    
";
            // line 13
            if ( !twig_test_empty(($context["textolargo"] ?? null))) {
                // line 14
                echo "<div class=\"text-gray-600 text-lg lg:text-2xl text-center mt-6\">
                        ";
                // line 15
                if (($context["textolargo"] ?? null)) {
                    echo " 
 
                            ";
                    // line 17
                    if (call_user_func_array($this->env->getFilter('isHTML')->getCallable(), [($context["textolargo"] ?? null)])) {
                        // line 18
                        echo "                                ";
                        echo ($context["textolargo"] ?? null);
                        echo " 
 
                            ";
                    } else {
                        // line 21
                        echo "                                ";
                        echo twig_nl2br(twig_escape_filter($this->env, ($context["textolargo"] ?? null), "html", null, true));
                        echo " 
 
                            ";
                    }
                    // line 24
                    echo "                        ";
                } else {
                    echo " 
 
                            ";
                    // line 26
                    echo twig_nl2br("");
                    echo " 
 
                        ";
                }
                // line 29
                echo "                    </div>
";
            }
            // line 31
            echo "
                </div>
                <form id=\"bbfv3ljth\" class=\"mt-6\" method=\"post\" enctype=\"multipart/form-data\">
                    
 ";
            // line 35
            $context["variables"] = ["nombre" => ["type" => "text", "required" => true], "apellidos" => ["type" => "text", "required" => true], "email" => ["type" => "email", "required" => true], "telefono" => ["type" => "text", "required" => true], "factura" => ["type" => "text", "required" => true], "dni_original" => ["type" => "text", "required" => true], "importe_factura" => ["type" => "text", "required" => true], "tipo_cliente" => ["type" => "select", "required" => true], "fecha_factura" => ["type" => "date", "required" => true], "dni_nuevo" => ["type" => "text", "required" => true], "nombre_nuevo" => ["type" => "text", "required" => true], "direccion_nuevo" => ["type" => "text", "required" => true], "codigo_postal_nuevo" => ["type" => "text", "required" => true], "pais_nuevo" => ["type" => "select", "required" => true], "provincia_nuevo" => ["type" => "text", "required" => true], "localidad_nuevo" => ["type" => "text", "required" => true], "newsletter" => ["type" => "checkbox"], "rgpd" => ["type" => "checkbox", "required" => true], "form" => ["type" => "hidden"]];
            echo " 

                    
 ";
            // line 38
            $context["form"] = call_user_func_array($this->env->getFunction('cocoForm')->getCallable(), [["id" => "bbfv3ljth", "tableName" => "solicitudes_cambio_titular", "mailRecord" => [0 => "correos", 1 => "SOLICITUD_CAMBIO_TITULAR"], "sendTo" => twig_get_attribute($this->env, $this->source,             // line 42
$context["configuracion"], "correo_admin", [], "any", false, false, false, 42), "sendToClient" => "email", "captcha" => false, "honeypot" => true, "messageOK_null" => "", "showImages" => false, "attachFiles" => false, "messageKO_null" => "", "action" => "null", "redirectTo" => "/gracias/", "emailMode" => "null", "emailB64" => false, "header_null" => "", "footer_null" => "", "styles_null" => "", "variables" =>             // line 57
($context["variables"] ?? null)]]);
            // line 59
            echo "
                    <div class=\"flex flex-wrap -mx-4\">
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 63
            echo twig_escape_filter($this->env, t_var("Nombre*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[nombre]\" placeholder=\"Escribe tu nombre\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 69
            echo twig_escape_filter($this->env, t_var("Apellidos*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[apellidos]\" placeholder=\"Escribe tus apellidos\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 75
            echo twig_escape_filter($this->env, t_var("Email*"), "html", null, true);
            echo "
                                <input type=\"email\" name=\"cocoForm[email]\" placeholder=\"Escribe tu email\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 81
            echo twig_escape_filter($this->env, t_var("Teléfono*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[telefono]\" placeholder=\"Escribe tu teléfono\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full px-4 lg:py-3 border-b\">
                            <p class=\"text-lg\">Datos originales de la factura:</p>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 90
            echo twig_escape_filter($this->env, t_var("Factura*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[factura]\" placeholder=\"Número de factura\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 96
            echo twig_escape_filter($this->env, t_var("DNI*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[dni_original]\" placeholder=\"Escribe tu DNI\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 102
            echo twig_escape_filter($this->env, t_var("Importe*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[importe_factura]\" placeholder=\"Importe de la factura\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 108
            echo twig_escape_filter($this->env, t_var("Tipo de cliente*"), "html", null, true);
            echo "
                                <select name=\"cocoForm[tipo_cliente]\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                                    <option selected=\"\" disabled=\"\">Selecciona tipología de cliente</option>
                                    <option value=\"Empresa\">Empresa</option>
                                    <option value=\"Cliente Final\">Cliente Final</option>
                                    <option value=\"Profesional\">Profesional</option>
                                    <option value=\"Profesional Revendedor\">Profesional Revendedor</option>
                                    <option value=\"Empresa Revendedora\">Empresa Revendedora</option>
                                </select>
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 121
            echo twig_escape_filter($this->env, t_var("Fecha de factura*"), "html", null, true);
            echo "
                                <input id=\"date\" type=\"date\" name=\"cocoForm[fecha_factura]\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full px-4 lg:py-3 border-b\">
                            <p class=\"text-lg\">Datos nuevos de la factura:</p>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 130
            echo twig_escape_filter($this->env, t_var("DNI*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[dni_nuevo]\" placeholder=\"Indique el DNI\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 136
            echo twig_escape_filter($this->env, t_var("Nombre*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[nombre_nuevo]\" placeholder=\"Indique el nombre\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 142
            echo twig_escape_filter($this->env, t_var("Dirección*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[direccion_nuevo]\" placeholder=\"Indique los dirección\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 148
            echo twig_escape_filter($this->env, t_var("Código postal*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[codigo_postal_nuevo]\" placeholder=\"Indique el código postal\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 154
            echo twig_escape_filter($this->env, t_var("País*"), "html", null, true);
            echo "
                                <select name=\"cocoForm[pais_nuevo]\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                                    <option value=\"ES\" selected=\"\">España</option>
                                </select>
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 162
            echo twig_escape_filter($this->env, t_var("Provincia*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[provincia_nuevo]\" placeholder=\"Indique la provincia\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                ";
            // line 168
            echo twig_escape_filter($this->env, t_var("Localidad*"), "html", null, true);
            echo "
                                <input type=\"text\" name=\"cocoForm[localidad_nuevo]\" placeholder=\"Indique la localidad\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <script>
                            /*
                            let selector_provincia = document.querySelector('._selector_provincia');
                            let selector_localidad = document.querySelector('._selector_localidad');
                            selector_provincia.addEventListener('change', () => {
                                let option_selected = selector_provincia.value;
                                option_selected = selector_provincia.querySelector('option[value=\"'+option_selected+'\"]').getAttribute('data-num');
                                if(option_selected) {
                                    CmsApi.hook('/hooks/search/', {params:JSON.stringify({'tab':'1f0948b3c55b6779947ecaecf21354df','filter_parent': option_selected})}).then(respuesta => 
                                    {
                                        if(respuesta && respuesta.resultado) {
                                            selector_localidad.innerHTML = '<option selected disabled>Indique la localidad</option>';
                                            respuesta.resultado.forEach(each => {
                                                const option = document.createElement(\"option\");
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
                        <!-- <captcha class=\"flex justify-satrt w-full text-center px-4 my-3\"></captcha> -->
                        <div class=\"px-6 mt-2\">
                            <label class=\"w-full flex items-center py-1\">
                                <input value=\"1\" name=\"cocoForm[newsletter]\" type=\"checkbox\" class=\"form-radio pr-4 mr-4 rounded-full\">
                                <span class=\"text-gray-600 text-xs lg:text-sm ml-2\">";
            // line 200
            echo t_var("Deseo recibir notificaciones de banana por eMail");
            echo "</span>
                            </label>
                            <label class=\"w-full flex items-center py-1\">
                                <input value=\"1\" name=\"cocoForm[rgpd]\" required=\"\" type=\"checkbox\" class=\"form-radio pr-4 mr-4 rounded-full\">
                                <span class=\"text-gray-600 text-xs lg:text-sm ml-2\">";
            // line 204
            echo t_var("Acepto las condiciones legales");
            echo "</span>
                            </label>
                        </div>
                    </div>
                    <button type=\"submit\" class=\"flex justify-center items-center bg-yellow-100 hover:bg-white text-white hover:text-yellow-100 md:text-lg lg:text-xl font-medium border border-yellow-100 rounded-full px-6 py-2 mt-6 mx-auto transition3s\">";
            // line 208
            echo twig_escape_filter($this->env, t_var("Enviar solicitud"), "html", null, true);
            echo "</button>
                <input name=\"cocoForm[form]\" type=\"hidden\" value=\"bbfv3ljth\"><div>

<div class=\"field seturecap\">

    <input type=\"text\" id=\"full_user_name\" name=\"cocoForm[full_user_name]\" placeholder=\"Nombre\" class=\"form-control\">

    <style>.seturecap {position: absolute;width: 1px;height: 1px;padding: 0;margin: -1px;overflow: hidden;clip: rect(0, 0, 0, 0);white-space: nowrap;border-width: 0;}</style>

</div>


</div></form>
            </div>
 ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['configuracion'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 222
        echo " 


            
";
        // line 226
        if (($context["imagen"] ?? null)) {
            // line 227
            echo "<div class=\"w-5/12 px-2 hidden lg:block\">
                <div class=\"p-1/8 relative\">
                    <img src=\"";
            // line 229
            echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('imagec')->getCallable(), [twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["imagen"] ?? null), 0, [], "any", false, false, false, 229), "urlPath", [], "any", false, false, false, 229), 1400]), "html", null, true);
            echo "\" alt=\"\" class=\"absolute top-0 left-0 w-full h-full object-cover object-center\">
                </div>
            </div>
";
        }
        // line 233
        echo "
        </div>
    </div>
</section>
";
    }

    public function getTemplateName()
    {
        return "index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  366 => 233,  359 => 229,  355 => 227,  353 => 226,  347 => 222,  326 => 208,  319 => 204,  312 => 200,  277 => 168,  268 => 162,  257 => 154,  248 => 148,  239 => 142,  230 => 136,  221 => 130,  209 => 121,  193 => 108,  184 => 102,  175 => 96,  166 => 90,  154 => 81,  145 => 75,  136 => 69,  127 => 63,  121 => 59,  119 => 57,  118 => 42,  117 => 38,  111 => 35,  105 => 31,  101 => 29,  95 => 26,  89 => 24,  82 => 21,  75 => 18,  73 => 17,  68 => 15,  65 => 14,  63 => 13,  56 => 10,  54 => 9,  44 => 5,  42 => 4,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<section class=\"bg-white\">
    <div class=\"container mx-auto max-w-6xl px-6 py-10 lg:py-16\">
        <div class=\"flex flex-col-reverse lg:flex-row justify-center -mx-2\">
            {% set configuracion = 'configuracion' | get('',null,null,{}) %}
 {% for configuracion in configuracion %} 
<div class=\"flex flex-col items-centerw-full lg:w-7/12 px-2 mt-6 lg:mt-0\">
                <div class=\"px-6\">
                    
                            {% set titulo_tag = titulo_tag ?: 'P' %}
                            {{ ('<' ~ titulo_tag ~ ' class=\"text-yellow-100 text-3xl lg:text-4xl font-light text-center leading-tight px-2\" c-if=\"titulo is not empty\" >' ~ (titulo ? titulo : '') ~ ('PC8=' | base64_decode) ~ titulo_tag ~ '>') | raw }}
                        
                    
{% if textolargo is not empty %}
<div class=\"text-gray-600 text-lg lg:text-2xl text-center mt-6\">
                        {% if textolargo %} 
 
                            {% if textolargo | isHTML %}
                                {{ textolargo | raw }} 
 
                            {% else %}
                                {{ textolargo | nl2br }} 
 
                            {% endif %}
                        {% else %} 
 
                            {{ \"\" | nl2br }} 
 
                        {% endif %}
                    </div>
{% endif %}

                </div>
                <form id=\"bbfv3ljth\" class=\"mt-6\" method=\"post\" enctype=\"multipart/form-data\">
                    
 {% set variables = {\"nombre\":{\"type\":\"text\",\"required\":true},\"apellidos\":{\"type\":\"text\",\"required\":true},\"email\":{\"type\":\"email\",\"required\":true},\"telefono\":{\"type\":\"text\",\"required\":true},\"factura\":{\"type\":\"text\",\"required\":true},\"dni_original\":{\"type\":\"text\",\"required\":true},\"importe_factura\":{\"type\":\"text\",\"required\":true},\"tipo_cliente\":{\"type\":\"select\",\"required\":true},\"fecha_factura\":{\"type\":\"date\",\"required\":true},\"dni_nuevo\":{\"type\":\"text\",\"required\":true},\"nombre_nuevo\":{\"type\":\"text\",\"required\":true},\"direccion_nuevo\":{\"type\":\"text\",\"required\":true},\"codigo_postal_nuevo\":{\"type\":\"text\",\"required\":true},\"pais_nuevo\":{\"type\":\"select\",\"required\":true},\"provincia_nuevo\":{\"type\":\"text\",\"required\":true},\"localidad_nuevo\":{\"type\":\"text\",\"required\":true},\"newsletter\":{\"type\":\"checkbox\"},\"rgpd\":{\"type\":\"checkbox\",\"required\":true},\"form\":{\"type\":\"hidden\"}} %} 

                    
 {% set form = cocoForm({
                        'id' : 'bbfv3ljth',
                        'tableName' : 'solicitudes_cambio_titular',
                        'mailRecord' : ['correos','SOLICITUD_CAMBIO_TITULAR'],
                        'sendTo' : configuracion.correo_admin,
                        'sendToClient' : 'email',
                        'captcha' : false,
                        'honeypot' : true,
                        'messageOK_null' : \"\",
                        'showImages' : false,
                        'attachFiles' : false,
                        'messageKO_null' : \"\",
                        'action' : 'null',
                        'redirectTo' : '/gracias/',
                        'emailMode' : 'null',
                        'emailB64' : false,
                        'header_null' : \"\",
                        'footer_null' : \"\",
                        'styles_null' : \"\",
                        'variables' : variables
                    }) %}

                    <div class=\"flex flex-wrap -mx-4\">
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Nombre*' | translate}}
                                <input type=\"text\" name=\"cocoForm[nombre]\" placeholder=\"Escribe tu nombre\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Apellidos*' | translate}}
                                <input type=\"text\" name=\"cocoForm[apellidos]\" placeholder=\"Escribe tus apellidos\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Email*' | translate}}
                                <input type=\"email\" name=\"cocoForm[email]\" placeholder=\"Escribe tu email\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Teléfono*' | translate}}
                                <input type=\"text\" name=\"cocoForm[telefono]\" placeholder=\"Escribe tu teléfono\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full px-4 lg:py-3 border-b\">
                            <p class=\"text-lg\">Datos originales de la factura:</p>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Factura*' | translate}}
                                <input type=\"text\" name=\"cocoForm[factura]\" placeholder=\"Número de factura\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'DNI*' | translate}}
                                <input type=\"text\" name=\"cocoForm[dni_original]\" placeholder=\"Escribe tu DNI\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Importe*' | translate}}
                                <input type=\"text\" name=\"cocoForm[importe_factura]\" placeholder=\"Importe de la factura\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Tipo de cliente*' | translate}}
                                <select name=\"cocoForm[tipo_cliente]\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                                    <option selected=\"\" disabled=\"\">Selecciona tipología de cliente</option>
                                    <option value=\"Empresa\">Empresa</option>
                                    <option value=\"Cliente Final\">Cliente Final</option>
                                    <option value=\"Profesional\">Profesional</option>
                                    <option value=\"Profesional Revendedor\">Profesional Revendedor</option>
                                    <option value=\"Empresa Revendedora\">Empresa Revendedora</option>
                                </select>
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Fecha de factura*' | translate}}
                                <input id=\"date\" type=\"date\" name=\"cocoForm[fecha_factura]\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full px-4 lg:py-3 border-b\">
                            <p class=\"text-lg\">Datos nuevos de la factura:</p>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'DNI*' | translate}}
                                <input type=\"text\" name=\"cocoForm[dni_nuevo]\" placeholder=\"Indique el DNI\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Nombre*' | translate}}
                                <input type=\"text\" name=\"cocoForm[nombre_nuevo]\" placeholder=\"Indique el nombre\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Dirección*' | translate}}
                                <input type=\"text\" name=\"cocoForm[direccion_nuevo]\" placeholder=\"Indique los dirección\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/2 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Código postal*' | translate}}
                                <input type=\"text\" name=\"cocoForm[codigo_postal_nuevo]\" placeholder=\"Indique el código postal\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'País*' | translate}}
                                <select name=\"cocoForm[pais_nuevo]\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                                    <option value=\"ES\" selected=\"\">España</option>
                                </select>
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Provincia*' | translate}}
                                <input type=\"text\" name=\"cocoForm[provincia_nuevo]\" placeholder=\"Indique la provincia\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <div class=\"w-full lg:w-1/3 px-4 lg:py-3\">
                            <label class=\"text-gray-900 text-sm lg:text-base font-medium\">
                                {{'Localidad*' | translate}}
                                <input type=\"text\" name=\"cocoForm[localidad_nuevo]\" placeholder=\"Indique la localidad\" required=\"\" class=\"bg-gray-100 text-sm placeholder-gray border border-gray rounded-md shadow-inner w-full focus:outline-none px-4 py-2 my-2\">
                            </label>
                        </div>
                        <script>
                            /*
                            let selector_provincia = document.querySelector('._selector_provincia');
                            let selector_localidad = document.querySelector('._selector_localidad');
                            selector_provincia.addEventListener('change', () => {
                                let option_selected = selector_provincia.value;
                                option_selected = selector_provincia.querySelector('option[value=\"'+option_selected+'\"]').getAttribute('data-num');
                                if(option_selected) {
                                    CmsApi.hook('/hooks/search/', {params:JSON.stringify({'tab':'1f0948b3c55b6779947ecaecf21354df','filter_parent': option_selected})}).then(respuesta => 
                                    {
                                        if(respuesta && respuesta.resultado) {
                                            selector_localidad.innerHTML = '<option selected disabled>Indique la localidad</option>';
                                            respuesta.resultado.forEach(each => {
                                                const option = document.createElement(\"option\");
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
                        <!-- <captcha class=\"flex justify-satrt w-full text-center px-4 my-3\"></captcha> -->
                        <div class=\"px-6 mt-2\">
                            <label class=\"w-full flex items-center py-1\">
                                <input value=\"1\" name=\"cocoForm[newsletter]\" type=\"checkbox\" class=\"form-radio pr-4 mr-4 rounded-full\">
                                <span class=\"text-gray-600 text-xs lg:text-sm ml-2\">{{ 'Deseo recibir notificaciones de banana por eMail' | translate | raw }}</span>
                            </label>
                            <label class=\"w-full flex items-center py-1\">
                                <input value=\"1\" name=\"cocoForm[rgpd]\" required=\"\" type=\"checkbox\" class=\"form-radio pr-4 mr-4 rounded-full\">
                                <span class=\"text-gray-600 text-xs lg:text-sm ml-2\">{{ 'Acepto las condiciones legales' | translate | raw }}</span>
                            </label>
                        </div>
                    </div>
                    <button type=\"submit\" class=\"flex justify-center items-center bg-yellow-100 hover:bg-white text-white hover:text-yellow-100 md:text-lg lg:text-xl font-medium border border-yellow-100 rounded-full px-6 py-2 mt-6 mx-auto transition3s\">{{ 'Enviar solicitud' | translate }}</button>
                <input name=\"cocoForm[form]\" type=\"hidden\" value=\"bbfv3ljth\"><div>

<div class=\"field seturecap\">

    <input type=\"text\" id=\"full_user_name\" name=\"cocoForm[full_user_name]\" placeholder=\"Nombre\" class=\"form-control\">

    <style>.seturecap {position: absolute;width: 1px;height: 1px;padding: 0;margin: -1px;overflow: hidden;clip: rect(0, 0, 0, 0);white-space: nowrap;border-width: 0;}</style>

</div>


</div></form>
            </div>
 {% endfor %} 


            
{% if imagen %}
<div class=\"w-5/12 px-2 hidden lg:block\">
                <div class=\"p-1/8 relative\">
                    <img src=\"{{ imagen.0.urlPath | imagec(1400) }}\" alt=\"\" class=\"absolute top-0 left-0 w-full h-full object-cover object-center\">
                </div>
            </div>
{% endif %}

        </div>
    </div>
</section>
", "index.twig", "/var/www/vhosts/ws.cocosolution.com/httpdocs/cms/lib/plugins/builder_saas/modulos/cache/formulariossolicitudcambiotitulardefactura_coic5g");
    }
}
}
$loader = new \Twig\Loader\FilesystemLoader(__DIR__);
$twig = new \Twig\Environment($loader, ['debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
global $globalBuilderFilters; 
 if (!@$globalBuilderFilters) $globalBuilderFilters = getBuilderFilters(); if (isset($globalBuilderFilters)) { foreach($globalBuilderFilters as $filter): $twig->addFilter($filter); endforeach; }
global $globalBuilderFunctions; 
 if (!@$globalBuilderFunctions) $globalBuilderFunctions = getBuilderFunctions(); if (isset($globalBuilderFunctions)) { foreach($globalBuilderFunctions as $filter): $twig->addFunction($filter); endforeach; }
$acaiResultData = new __TwigTemplate_95f4877b6d6bd0af520138a29d5efbd2ce0c2f239906cec6bb5b5e1d66b4553a($twig);
