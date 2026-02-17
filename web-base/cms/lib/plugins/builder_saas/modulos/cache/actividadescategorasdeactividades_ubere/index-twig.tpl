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
if (!class_exists('__TwigTemplate_c7804de6bea505460d3c0f1de220cac885a93d283bbc6784b405f4181ff19dac')){
class __TwigTemplate_c7804de6bea505460d3c0f1de220cac885a93d283bbc6784b405f4181ff19dac extends Template
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
        echo "\" class=\"relative\">
    <div class=\"container max-w-6xl px-6 xl:px-0 py-10 lg:py-20 mx-auto\">

    </div>
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
        return array (  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<section id=\"id_{{ section_id }}\" class=\"relative\">
    <div class=\"container max-w-6xl px-6 xl:px-0 py-10 lg:py-20 mx-auto\">

    </div>
</section>", "index.twig", "/var/www/vhosts/ws.cocosolution.com/httpdocs/cms/lib/plugins/builder_saas/modulos/cache/actividadescategorasdeactividades_ubere");
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
$acaiResultData = new __TwigTemplate_c7804de6bea505460d3c0f1de220cac885a93d283bbc6784b405f4181ff19dac($twig);
