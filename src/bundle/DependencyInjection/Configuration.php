<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedisBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ezplatform_redis');

        $rootNode
            ->children()
                ->booleanNode('igbinary')
                    ->defaultFalse()
                    ->validate()
                        ->ifTrue(function ($igbinary) {
                            return $igbinary && !extension_loaded('igbinary');
                        })
                       ->thenInvalid('PHP extension "igbinary" is not installed!')
                    ->end()
                ->end()
                ->booleanNode('lzf')
                    ->defaultFalse()
                    ->validate()
                        ->ifTrue(function ($lzf) {
                            return $lzf && !extension_loaded('lzf');
                        })
                        ->thenInvalid('PHP extension "lzf" is not installed!')
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
