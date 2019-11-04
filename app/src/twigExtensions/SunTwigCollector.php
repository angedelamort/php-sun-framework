<?php

namespace sunframework\twigExtensions;


use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataFormatter\DataFormatter;
use Twig\Profiler\Profile;

/**
 * Class SunTwigCollector
 * @package sunframework\twigExtensions
 *
 * This is based on an obsolete TwigCollector from DebugBar.
 *
 * @see DebugBar\Bridge\Twig\TwigCollector;
 */
class SunTwigCollector extends DataCollector implements Renderable, AssetProvider {
    /** @var Profile  */
    private $twigProfile;
    /** @var DataFormatter  */
    private $dataFormatter;

    public function __construct(Profile $twigProfile) {
        $this->twigProfile = $twigProfile;
        $this->dataFormatter = new DataFormatter();
    }

    public function getAssets() {
        return array(
            'css' => 'widgets/templates/widget.css',
            'js' => 'widgets/templates/widget.js'
        );
    }

    public function collect() {
        $templates = [];
        $this->dumpProfile($this->twigProfile, $templates);

        return array(
            'nb_templates' => count($templates),
            'templates' => $templates,
            'accumulated_render_time' => 0,
            'accumulated_render_time_str' => '0 ms'
        );
    }

    private function dumpProfile(Profile $profile, &$templates) {
        $name = $profile->getTemplate();
        if (!$profile->isTemplate()) {
            $name = sprintf('%s::%s(%s)', $profile->getTemplate(), $profile->getType(), $profile->getName());
        }
        $duration = $profile->getDuration();
        $memory = $profile->getMemoryUsage();
        $item = ['name' => $name, 'type' => $profile->getType()];
        $index = count($templates);
        $templates[$index] = $item;

        foreach ($profile as $child) {
            $result = $this->dumpProfile($child, $templates);
            $duration += $result[0];
            $memory += $result[1];
        }

        $templates[$index]['render_time'] = $duration;
        $templates[$index]['render_time_str'] = $this->dataFormatter->formatDuration($duration);
        $templates[$index]['memory_str'] = $this->dataFormatter->formatBytes($memory);
        return [$duration, $memory];
    }

    public function getName() {
        return 'twig';
    }

    public function getWidgets() {
        return array(
            'twig' => array(
                'icon' => 'leaf',
                'widget' => 'PhpDebugBar.Widgets.TemplatesWidget',
                'map' => 'twig',
                'default' => json_encode(array('templates' => array())),
            ),
            'twig:badge' => array(
                'map' => 'twig.nb_templates',
                'default' => 0
            )
        );
    }
}