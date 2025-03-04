<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit;

require_once __DIR__ . '/../vendor/autoload.php';

use Netzkollektiv\EasyCredit\Util\Lifecycle\ActivateDeactivate;
use Netzkollektiv\EasyCredit\Util\Lifecycle\InstallUninstall;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Netzkollektiv\EasyCredit\Compatibility\EntityCompilerPass;
use Netzkollektiv\EasyCredit\Compatibility\Capabilities;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Contracts\Service\Attribute\Required;

class EasyCreditRatenkauf extends Plugin
{

    public const ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TRANSACTION_ID = 'easycredit_transaction_id';
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TECHNICAL_TRANSACTION_ID = 'easycredit_technical_transaction_id';

    /**
     * @var ActivateDeactivate
     */
    protected $activateDeactivate;

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        $loader->load('compatibility.xml');
        $loader->load('easycredit_payment.xml');

        if ((new Capabilities($container->getParameter('kernel.shopware_version')))->hasFlowBuilder()) {
            $loader->load('flow.xml');
        }
        $loader->load('setting.xml');
        $loader->load('rule.xml');

        $container->addCompilerPass(new EntityCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 500);

        $this->loadPackagesConfig($container);
    }

    protected function loadPackagesConfig($container)
    {
        $locator = new FileLocator('Resources/config');

        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);

        $configLoader = new DelegatingLoader($resolver);

        $confDir = \rtrim($this->getPath(), '/') . '/Resources/config';

        $configLoader->load($confDir . '/{packages}/*.yaml', 'glob');
    }

    public function install(InstallContext $installContext): void
    {
        (new InstallUninstall(
            $this->container->get('system_config.repository'),
            $this->container->get('payment_method.repository'),
            $this->container->get('country.repository'),
            $this->container->get('currency.repository'),
            $this->container->get(PluginIdProvider::class),
            $this->container->get(SystemConfigService::class),
            static::class
        ))->install($installContext);

        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            parent::uninstall($uninstallContext);

            return;
        }

        (new InstallUninstall(
            $this->container->get('system_config.repository'),
            $this->container->get('payment_method.repository'),
            $this->container->get('country.repository'),
            $this->container->get('currency.repository'),
            $this->container->get(PluginIdProvider::class),
            $this->container->get(SystemConfigService::class),
            static::class
        ))->uninstall($uninstallContext);

        parent::uninstall($uninstallContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        (new InstallUninstall(
            $this->container->get('system_config.repository'),
            $this->container->get('payment_method.repository'),
            $this->container->get('country.repository'),
            $this->container->get('currency.repository'),
            $this->container->get(PluginIdProvider::class),
            $this->container->get(SystemConfigService::class),
            static::class
        ))->update($updateContext);

        parent::update($updateContext);
    }

    /**
     * @Required
     */
    #[Required]
    public function setActivateDeactivate(ActivateDeactivate $activateDeactivate): void
    {
        $this->activateDeactivate = $activateDeactivate;
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        $this->activateDeactivate->activate($activateContext->getContext());
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);

        $this->activateDeactivate->deactivate($deactivateContext->getContext());
    }
}
