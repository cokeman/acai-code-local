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
if (!class_exists('__TwigTemplate_27088fb213238da337473213bfbe8cb8d71bb9a77cf99335a30f7145eaeeb9df')){
class __TwigTemplate_27088fb213238da337473213bfbe8cb8d71bb9a77cf99335a30f7145eaeeb9df extends Template
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
        echo "<div class=\"body\">
    
";
        // line 3
        if (($context["video"] ?? null)) {
            // line 4
            echo "<div>
        
    </div>
";
        }
        // line 8
        echo "
    <div class=\"golden-stage wf-section\">
        
";
        // line 11
        if (($context["logo"] ?? null)) {
            // line 12
            echo "<img src=\"";
            echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('imagec')->getCallable(), [twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["logo"] ?? null), 0, [], "any", false, false, false, 12), "urlPath", [], "any", false, false, false, 12), 1600]), "html", null, true);
            echo "\" style=\"width:200px\" class=\"w-32 scorpio-logo\" alt=\"Logo Playa Padre\" width=\"600\">
";
        }
        // line 14
        echo "
        <div data-autoplay=\"true\" data-loop=\"true\" data-wf-ignore=\"true\" class=\"background-video-hero w-background-video w-background-video-atom\">
            <video autoplay=\"\" loop=\"\" muted=\"\" playsinline=\"\" data-wf-ignore=\"true\" data-object-fit=\"cover\">
                <source src=\"";
        // line 17
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["video"] ?? null), 0, [], "any", false, false, false, 17), "urlPath", [], "any", false, false, false, 17), "html", null, true);
        echo "\" data-wf-ignore=\"true\">
                <source src=\"";
        // line 18
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["video"] ?? null), 0, [], "any", false, false, false, 18), "urlPath", [], "any", false, false, false, 18), "html", null, true);
        echo "\" data-wf-ignore=\"true\">
            </video>
        </div>
    </div>
</div>
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
        return array (  71 => 18,  67 => 17,  62 => 14,  56 => 12,  54 => 11,  49 => 8,  43 => 4,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<div class=\"body\">
    
{% if video %}
<div>
        
    </div>
{% endif %}

    <div class=\"golden-stage wf-section\">
        
{% if logo %}
<img src=\"{{ logo.0.urlPath | imagec(1600) }}\" style=\"width:200px\" class=\"w-32 scorpio-logo\" alt=\"Logo Playa Padre\" width=\"600\">
{% endif %}

        <div data-autoplay=\"true\" data-loop=\"true\" data-wf-ignore=\"true\" class=\"background-video-hero w-background-video w-background-video-atom\">
            <video autoplay=\"\" loop=\"\" muted=\"\" playsinline=\"\" data-wf-ignore=\"true\" data-object-fit=\"cover\">
                <source src=\"{{video.0.urlPath}}\" data-wf-ignore=\"true\">
                <source src=\"{{video.0.urlPath}}\" data-wf-ignore=\"true\">
            </video>
        </div>
    </div>
</div>
", "index.twig", "/var/www/vhosts/ws.cocosolution.com/httpdocs/cms/lib/plugins/builder_saas/modulos/cache/generalbannerprincipal_nqkblr");
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
$acaiResultData = new __TwigTemplate_27088fb213238da337473213bfbe8cb8d71bb9a77cf99335a30f7145eaeeb9df($twig);
