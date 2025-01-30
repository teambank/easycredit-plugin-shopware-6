<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Compatibility;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Shopware\Storefront\Theme\StorefrontPluginConfiguration\AbstractStorefrontPluginConfigurationFactory;
use Shopware\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Shopware\Storefront\Theme\StorefrontPluginConfiguration\FileCollection;

class PriorSw66ScriptsCompatibilityDecorator extends AbstractStorefrontPluginConfigurationFactory
{
    private AbstractStorefrontPluginConfigurationFactory $inner;
    private ContainerInterface $container;

    public function __construct(
        AbstractStorefrontPluginConfigurationFactory $inner,
        ContainerInterface $container
    ) {
        $this->inner = $inner;
        $this->container = $container;
    }

    public function getDecorated(): AbstractStorefrontPluginConfigurationFactory
    {
        return $this->inner;
    }

    public function createFromBundle(Bundle $bundle): StorefrontPluginConfiguration
    {
        $configuration = $this->inner->createFromBundle($bundle);

        if (\version_compare($this->container->getParameter('kernel.shopware_version'), '6.6.0', '>=')) {
            return $configuration;
        }

        if ($configuration->getTechnicalName() === 'EasyCreditRatenkauf'
            && \count($configuration->getScriptFiles()) === 2
        ) {
            // remove duplicate script file (sw 6.5 or lower generates script file directly in src/Resources/app/storefront/dist/storefront/js)
            $configuration->setScriptFiles(
                $configuration->getScriptFiles()
                    ->filter(fn ($scriptFile) => \mb_strpos($scriptFile->getFilePath(), 'easy-credit-ratenkauf/easy-credit-ratenkauf') === false)
            );
        }
        return $configuration;
    }

    public function createFromApp(string $appName, string $appPath): StorefrontPluginConfiguration
    {
	return $this->inner->createFromApp($appName, $appPath);
    }

    public function createFromThemeJson(string $name, array $data, string $path, bool $isFullpath = true): StorefrontPluginConfiguration {
        return $this->inner->createFromThemeJson($name, $data, $path, $isFullpath);
    }
}
