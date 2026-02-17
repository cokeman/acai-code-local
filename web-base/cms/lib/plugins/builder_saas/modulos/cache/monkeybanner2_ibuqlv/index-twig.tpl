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
if (!class_exists('__TwigTemplate_c3a30e269d8405bb584ccca52bb44a8cdb3c5f037a668f39357a6a71c40d465a')){
class __TwigTemplate_c3a30e269d8405bb584ccca52bb44a8cdb3c5f037a668f39357a6a71c40d465a extends Template
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
        echo "<div class=\"p-12 bg-gray-300 text-gray-600 text-center\">Mi nuevo módulo</div>";
    }

    public function getTemplateName()
    {
        return "index.twig";
    }

    public function getDebugInfo()
    {
        return array (  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<div class=\"p-12 bg-gray-300 text-gray-600 text-center\">Mi nuevo módulo</div>", "index.twig", "/var/www/vhosts/ws.cocosolution.com/httpdocs/cms/lib/plugins/builder_saas/modulos/cache/monkeybanner2_ibuqlv");
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
$acaiResultData = new __TwigTemplate_c3a30e269d8405bb584ccca52bb44a8cdb3c5f037a668f39357a6a71c40d465a($twig);
