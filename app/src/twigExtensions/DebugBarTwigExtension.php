<?php

namespace sunframework\twigExtensions;

use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

/**
 * Class DebugBarTwigExtension
 * @package sunframework\twigExtensions
 * @Note MUST have jquery included.
 */
class DebugBarTwigExtension extends AbstractExtension implements GlobalsInterface {

    /** @var DebugBar  */
    private $debugBar;
    private $sourceDirectory;
    private $baseUrl;
    private $cssFile;
    private $jsFile;
    /** @var JavascriptRenderer  */
    private $jsRenderer;

    public function __construct(DebugBar $debugBar, string $sourceDirectory, string $baseUrl) {
        $this->debugBar = $debugBar;
        $this->sourceDirectory = $sourceDirectory;
        $this->baseUrl = $baseUrl;

        if (!file_exists($this->sourceDirectory)) {
            mkdir($this->sourceDirectory, 0777, true);
        }

        $this->cssFile = $this->sourceDirectory . '/debugbar.css';
        $this->jsRenderer = $this->debugBar->getJavascriptRenderer();
        $this->jsRenderer->disableVendor('jquery');
        $this->jsRenderer->disableVendor('fontawesome');

        if (!file_exists($this->cssFile)) {
            $this->jsRenderer->dumpCssAssets($this->cssFile);
        }
        $this->cssFile = $this->baseUrl . '/debugbar.css';

        $this->jsFile = $this->sourceDirectory . '/debugbar.js';
        if (!file_exists($this->jsFile)) {
            $this->jsRenderer->dumpJsAssets($this->jsFile);
        }
        $this->jsFile = $baseUrl . '/debugbar.js';
    }

    public function getFunctions() : array {
        return [
            new TwigFunction('debugBarHeader', [$this, 'debugBarHeader'], ['is_safe' => ['html']]),
            new TwigFunction('debugBarFooter', [$this, 'debugBarFooter'], ['is_safe' => ['html']])
        ];
    }

    public function debugBarHeader() : string {
        return '<link rel="stylesheet" href="' . $this->cssFile . '" />';
    }

    public function debugBarFooter() : string {
        return '<script src="' . $this->jsFile . '"></script>' . $this->jsRenderer->render();
    }

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array An array of global variables
     */
    public function getGlobals() {
        return [
            'debugBar' => $this->debugBar,
            'debugBarRenderer' => $this->jsRenderer
        ];
    }
}