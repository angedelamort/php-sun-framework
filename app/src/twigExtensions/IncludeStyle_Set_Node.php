<?php
namespace sunframework\twigExtensions;

class IncludeStyle_Set_Node extends \Twig\Node\Node
{
    private $settings;

    public function __construct($value, $settings, $line, $tag = null)
    {
        $this->settings = $settings;
        $nodes = ['src' => $value];
        $attributes = [];

        parent::__construct($nodes, $attributes, $line, $tag);
    }

    public function compile(\Twig\Compiler $compiler)
    {
        //ob_start();
        //var_dump($compiler);
        //$result = ob_get_clean();

        // TODO: get the data from the settings.
        // TODO: compile the scss or even minify it.
        // like a cache, so we need to check if file has changed to regenerate it?
        //error_log("-----------> Compiling Node $result\n");

        // SML: Master plan 2: Each template should register a file, and a StyleManager should handle the generation everytime it's needed.
        $includeFile = $this->getNode('src')->getAttribute('value'); // TODO: Not sure about this: in the samples, I see data, but it doesn't exists here.

        $compiler
            ->addDebugInfo($this)
            ->write('echo "<link rel=\"stylesheet\" href=\"'.$includeFile.'\" />"')
            ->raw(";\n");
    }
}