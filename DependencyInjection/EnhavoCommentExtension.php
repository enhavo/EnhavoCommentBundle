<?php

/*
 * This file is part of the enhavo package.
 *
 * (c) WE ARE INDEED GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enhavo\Bundle\CommentBundle\DependencyInjection;

use Enhavo\Bundle\ResourceBundle\DependencyInjection\PrependExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class EnhavoCommentExtension extends Extension implements PrependExtensionInterface
{
    use PrependExtensionTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $container->setParameter('enhavo_comment.submit_form.form', $config['submit_form']['form']);
        $container->setParameter('enhavo_comment.submit_form.validation_groups', $config['submit_form']['validation_groups']);
        $container->setParameter('enhavo_comment.publish_strategy.strategy', $config['publish_strategy']['strategy']);
        $container->setParameter('enhavo_comment.publish_strategy.options', $config['publish_strategy']['options']);
        $container->setParameter('enhavo_comment.subjects', $config['subjects']);

        $configFiles = [
            'services.yaml',
        ];

        foreach ($configFiles as $configFile) {
            $loader->load($configFile);
        }
    }

    protected function prependFiles(): array
    {
        return [
            __DIR__.'/../Resources/config/app/config.yaml',
            __DIR__.'/../Resources/config/resources/comment.yaml',
            __DIR__.'/../Resources/config/resources/thread_comment.yaml',
            __DIR__.'/../Resources/config/resources/thread.yaml',
        ];
    }
}
