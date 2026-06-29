<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Util;

use Composer\Autoload\ClassLoader;

final class VendorAutoloader
{
    private const API_NAMESPACE = 'Teambank\\EasyCreditApiV3\\';

    private const API_SRC = '/netzkollektiv/easycredit-api-v3-php/src';

    private static bool $registered = false;

    public static function register(string $pluginRoot): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        $vendorDir = $pluginRoot . '/vendor';
        $vendorAutoload = $vendorDir . '/autoload.php';
        if (!\is_file($vendorAutoload)) {
            return;
        }

        self::registerOnProjectClassLoader($vendorDir);

        if (self::isBundledDependencyAvailable()) {
            return;
        }

        // Only fall back to the plugin vendor autoload outside Shopware. Loading it inside
        // Shopware would register the plugin's shopware/core copy and shadow core classes.
        if (self::getProjectClassLoader($vendorDir) === null) {
            require_once $vendorAutoload;
        }
    }

    private static function registerOnProjectClassLoader(string $vendorDir): void
    {
        if (!\class_exists(ClassLoader::class) || !\method_exists(ClassLoader::class, 'getRegisteredLoaders')) {
            return;
        }

        $classLoader = self::getProjectClassLoader($vendorDir);
        if (!$classLoader instanceof ClassLoader) {
            return;
        }

        $apiSrc = $vendorDir . self::API_SRC;
        if (!\is_dir($apiSrc)) {
            return;
        }

        $classLoader->addPsr4(self::API_NAMESPACE, $apiSrc . '/', true);
    }

    private static function getProjectClassLoader(string $pluginVendorDir): ?ClassLoader
    {
        $pluginVendorRealpath = \realpath($pluginVendorDir);
        $loaders = ClassLoader::getRegisteredLoaders();

        foreach ($loaders as $vendorDir => $loader) {
            if ($pluginVendorRealpath !== false && \realpath($vendorDir) === $pluginVendorRealpath) {
                continue;
            }

            return $loader;
        }

        return null;
    }

    private static function isBundledDependencyAvailable(): bool
    {
        return \interface_exists(\Teambank\EasyCreditApiV3\Integration\StorageInterface::class, false)
            || \class_exists(\Teambank\EasyCreditApiV3\ApiException::class, false);
    }
}
