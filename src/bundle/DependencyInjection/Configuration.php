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
                ->scalarNode('serializer')
                    ->defaultValue('native')
                    ->validate()
                        ->ifNotInArray(['native', 'igbinary'])
                        ->thenInvalid('Invalid serializer %s')
                        ->ifTrue(function ($value) {
                            if ($value === 'igbinary') {
                                return !extension_loaded('igbinary');
                            }
                        })
                        ->thenInvalid('PHP extension "igbinary" is not installed!')
                    ->end()
                ->end()
                ->scalarNode('compressor')
                    ->defaultValue('none')
                    ->validate()
                        ->ifNotInArray(['none', 'lzf'])
                        ->thenInvalid('Invalid compressor %s')
                        ->ifTrue(function ($value) {
                            if ($value === 'lzf') {
                                return !extension_loaded('lzf');
                            }
                        })
                        ->thenInvalid('PHP extension "lzf" is not installed!')
                    ->end()
                ->end()
                ->scalarNode('marshaller')
                    ->defaultValue('default')
                    ->validate()
                        ->ifNotInArray(['default'])
                        ->thenInvalid('Invalid marshaller %s')
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
