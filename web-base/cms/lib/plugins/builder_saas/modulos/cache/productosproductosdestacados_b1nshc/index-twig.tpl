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
if (!class_exists('__TwigTemplate_5288ddd17d0720430bf0c01056e89a4f09c9eae595b76fe3e83d4fe1e40f0129')){
class __TwigTemplate_5288ddd17d0720430bf0c01056e89a4f09c9eae595b76fe3e83d4fe1e40f0129 extends Template
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
        echo "<section id=\"id_";
        echo twig_escape_filter($this->env, ($context["section_id"] ?? null), "html", null, true);
        echo "\" class=\"relative overflow-hidden nepe\">
    <img class=\"w-full h-full object-cover object-center lazyload\" data-src=\"/template/estandar/images/hondas2.svg\" alt=\"nubes\">
    <div class=\"relative bg-main-color\">
        <div class=\"relative container max-w-7xl px-6 2xl:px-0 py-10 lg:py-20 mx-auto\">
            ";
        // line 5
        if (($context["titulo"] ?? null)) {
            // line 6
            echo "            
                            ";
            // line 7
            $context["titulo_tag"] = ((($context["titulo_tag"] ?? null)) ? (($context["titulo_tag"] ?? null)) : ("P"));
            // line 8
            echo "                            ";
            echo (((((("<" . ($context["titulo_tag"] ?? null)) . " class=\"sweatgraphy text-main-color-dark text-5xl sm:text-6xl md:text-4xl lg:text-5xl xl:text-6xl 2xl:text-7xl max-w-2xl mx-auto text-center\" >") . ((($context["titulo"] ?? null)) ? (($context["titulo"] ?? null)) : (""))) . base64_decode("PC8=")) . ($context["titulo_tag"] ?? null)) . ">");
            echo "
                        
            ";
        }
        // line 11
        echo "            
";
        // line 12
        if (($context["records"] ?? null)) {
            // line 13
            echo "<div class=\"c-tns-wrapper sm:-mx-10 lg:-mx-6 mt-4\" data-responsive=\"sm:2, lg:4\" data-autoplay-timeout=\"8000\" data-nav=\"true\">
                <ul class=\"c-tns-container\">
                    
";
            // line 16
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["records"] ?? null));
            $context['loop'] = [
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = 1 === $length;
            }
            foreach ($context['_seq'] as $context["_key"] => $context["record"]) {
                echo " ";
                $context["index"] = twig_get_attribute($this->env, $this->source, $context["loop"], "index0", [], "any", false, false, false, 16);
                // line 17
                echo "<li class=\"flex flex-col items-center sm:px-10 lg:px-6 py-6\">
                        
";
                // line 19
                if (twig_get_attribute($this->env, $this->source, $context["record"], "imagen", [], "any", false, false, false, 19)) {
                    // line 20
                    echo "<div class=\"p-1/10 relative rounded-full overflow-hidden w-11/12 mx-auto\">
                            <img data-src=\"";
                    // line 21
                    echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('imagec')->getCallable(), [twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["record"], "imagen", [], "any", false, false, false, 21), 0, [], "any", false, false, false, 21), "urlPath", [], "any", false, false, false, 21), 800]), "html", null, true);
                    echo "\" alt=\"";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["imagen"] ?? null), 0, [], "any", false, false, false, 21), "info1", [], "any", false, false, false, 21), "html", null, true);
                    echo "\" class=\"absolute top-0 left-0 w-full h-full object-cover object-center lazyload\">
                        </div>
";
                }
                // line 24
                echo "
                        
";
                // line 26
                if (twig_get_attribute($this->env, $this->source, $context["record"], "titulobloque", [], "any", false, false, false, 26)) {
                    // line 27
                    echo "<div class=\"sweatgraphy text-main-color-dark text-3xl line-clamp-1 text-center mt-10\">
                        ";
                    // line 28
                    if (twig_get_attribute($this->env, $this->source, $context["record"], "titulobloque", [], "any", false, false, false, 28)) {
                        echo " 
 
                            ";
                        // line 30
                        echo twig_get_attribute($this->env, $this->source, $context["record"], "titulobloque", [], "any", false, false, false, 30);
                        echo " 
 
                        ";
                    } else {
                        // line 32
                        echo " 
 
                            ";
                        // line 34
                        echo "";
                        echo " 
 
                        ";
                    }
                    // line 37
                    echo "                    </div>
";
                }
                // line 39
                echo "
                        
";
                // line 41
                if (twig_get_attribute($this->env, $this->source, $context["record"], "textobloque", [], "any", false, false, false, 41)) {
                    // line 42
                    echo "<div class=\"text-main-color-dark text-center mt-4\">
                        ";
                    // line 43
                    if (twig_get_attribute($this->env, $this->source, $context["record"], "textobloque", [], "any", false, false, false, 43)) {
                        echo " 
 
                            ";
                        // line 45
                        if (call_user_func_array($this->env->getFilter('isHTML')->getCallable(), [twig_get_attribute($this->env, $this->source, $context["record"], "textobloque", [], "any", false, false, false, 45)])) {
                            // line 46
                            echo "                                ";
                            echo twig_get_attribute($this->env, $this->source, $context["record"], "textobloque", [], "any", false, false, false, 46);
                            echo " 
 
                            ";
                        } else {
                            // line 49
                            echo "                                ";
                            echo twig_nl2br(twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["record"], "textobloque", [], "any", false, false, false, 49), "html", null, true));
                            echo " 
 
                            ";
                        }
                        // line 52
                        echo "                        ";
                    } else {
                        echo " 
 
                            ";
                        // line 54
                        echo twig_nl2br("");
                        echo " 
 
                        ";
                    }
                    // line 57
                    echo "                    </div>
";
                }
                // line 59
                echo "
                    </li>
";
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['length'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['record'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 62
            echo "
                </ul>
            </div> ";
        } else {
            // line 64
            echo " <div class=\"c-tns-wrapper sm:-mx-10 lg:-mx-6 mt-4\" data-responsive=\"sm:2, lg:4\" data-autoplay-timeout=\"8000\" data-nav=\"true\">
                <ul class=\"c-tns-container\">
                    ";
            // line 66
            $context["productos"] = CocoDB::get("productos", "visible=1 and destacado=1", null, null, []);
            // line 67
            echo " ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["productos"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["producto"]) {
                echo " 
<li class=\"sm:px-10 lg:px-6 py-6\">
                        ";
                // line 69
                echo BuilderModule("bloqueproducto_i7aunn", ["producto" => $context["producto"]]);
                echo "
                    </li>
 ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['producto'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 71
            echo " 

                </ul>
            </div>
";
        }
        // line 76
        echo "
            
        </div>
    </div>
    <img class=\"w-full h-full object-cover object-center lazyload rotate-180\" data-src=\"/template/estandar/images/hondas2.svg\" alt=\"nubes\">
</section>";
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
        return array (  236 => 76,  229 => 71,  220 => 69,  212 => 67,  210 => 66,  206 => 64,  201 => 62,  185 => 59,  181 => 57,  175 => 54,  169 => 52,  162 => 49,  155 => 46,  153 => 45,  148 => 43,  145 => 42,  143 => 41,  139 => 39,  135 => 37,  129 => 34,  125 => 32,  119 => 30,  114 => 28,  111 => 27,  109 => 26,  105 => 24,  97 => 21,  94 => 20,  92 => 19,  88 => 17,  69 => 16,  64 => 13,  62 => 12,  59 => 11,  52 => 8,  50 => 7,  47 => 6,  45 => 5,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<section id=\"id_{{ section_id }}\" class=\"relative overflow-hidden nepe\">
    <img class=\"w-full h-full object-cover object-center lazyload\" data-src=\"/template/estandar/images/hondas2.svg\" alt=\"nubes\">
    <div class=\"relative bg-main-color\">
        <div class=\"relative container max-w-7xl px-6 2xl:px-0 py-10 lg:py-20 mx-auto\">
            {% if titulo %}
            
                            {% set titulo_tag = titulo_tag ?: 'P' %}
                            {{ ('<' ~ titulo_tag ~ ' class=\"sweatgraphy text-main-color-dark text-5xl sm:text-6xl md:text-4xl lg:text-5xl xl:text-6xl 2xl:text-7xl max-w-2xl mx-auto text-center\" >' ~ (titulo ? titulo : '') ~ ('PC8=' | base64_decode) ~ titulo_tag ~ '>') | raw }}
                        
            {% endif %}
            
{% if records %}
<div class=\"c-tns-wrapper sm:-mx-10 lg:-mx-6 mt-4\" data-responsive=\"sm:2, lg:4\" data-autoplay-timeout=\"8000\" data-nav=\"true\">
                <ul class=\"c-tns-container\">
                    
{% for record in records %} {% set index = loop.index0 %}
<li class=\"flex flex-col items-center sm:px-10 lg:px-6 py-6\">
                        
{% if record.imagen %}
<div class=\"p-1/10 relative rounded-full overflow-hidden w-11/12 mx-auto\">
                            <img data-src=\"{{ record.imagen.0.urlPath | imagec(800) }}\" alt=\"{{imagen.0.info1}}\" class=\"absolute top-0 left-0 w-full h-full object-cover object-center lazyload\">
                        </div>
{% endif %}

                        
{% if record.titulobloque %}
<div class=\"sweatgraphy text-main-color-dark text-3xl line-clamp-1 text-center mt-10\">
                        {% if record.titulobloque %} 
 
                            {{ record.titulobloque | raw }} 
 
                        {% else %} 
 
                            {{ \"\" | raw }} 
 
                        {% endif %}
                    </div>
{% endif %}

                        
{% if record.textobloque %}
<div class=\"text-main-color-dark text-center mt-4\">
                        {% if record.textobloque %} 
 
                            {% if record.textobloque | isHTML %}
                                {{ record.textobloque | raw }} 
 
                            {% else %}
                                {{ record.textobloque | nl2br }} 
 
                            {% endif %}
                        {% else %} 
 
                            {{ \"\" | nl2br }} 
 
                        {% endif %}
                    </div>
{% endif %}

                    </li>
{% endfor %}

                </ul>
            </div> {% else %} <div class=\"c-tns-wrapper sm:-mx-10 lg:-mx-6 mt-4\" data-responsive=\"sm:2, lg:4\" data-autoplay-timeout=\"8000\" data-nav=\"true\">
                <ul class=\"c-tns-container\">
                    {% set productos = 'productos' | get('visible=1 and destacado=1',null,null,{}) %}
 {% for producto in productos %} 
<li class=\"sm:px-10 lg:px-6 py-6\">
                        {{ 'bloqueproducto_i7aunn' | module({\"producto\":producto})|raw }}
                    </li>
 {% endfor %} 

                </ul>
            </div>
{% endif %}

            
        </div>
    </div>
    <img class=\"w-full h-full object-cover object-center lazyload rotate-180\" data-src=\"/template/estandar/images/hondas2.svg\" alt=\"nubes\">
</section>", "index.twig", "/var/www/vhosts/ws.cocosolution.com/httpdocs/cms/lib/plugins/builder_saas/modulos/cache/productosproductosdestacados_b1nshc");
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
$acaiResultData = new __TwigTemplate_5288ddd17d0720430bf0c01056e89a4f09c9eae595b76fe3e83d4fe1e40f0129($twig);
