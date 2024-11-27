<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Teambank\EasyCreditApiV3\Model\Shopsystem;
use Netzkollektiv\EasyCredit\Helper\MetaDataProvider;

class SystemBuilder
{
    private MetaDataProvider $metaDataProvider;
 
    public function __construct(
        MetaDataProvider $metaDataProvider
    ) {
        $this->metaDataProvider = $metaDataProvider;
    }

    public function getSystemVendor(): string
    {
        return 'Shopware';
    }

    public function getSystemVersion(): string
    {
        return $this->metaDataProvider->getShopwareVersion();
    }

    public function getModuleVersion(): ?string
    {
        $json = \file_get_contents(__DIR__ . '/../../composer.json');
        if ($json !== false) {
            $json = \json_decode($json);
            if (isset($json->version)) {
                return $json->version;
            }
        }
        return '';
    }

    public function build () {
        return new Shopsystem([
            'shopSystemManufacturer' => \implode(' ',[$this->getSystemVendor(),$this->getSystemVersion()]),
            'shopSystemModuleVersion' => $this->getModuleVersion()
        ]);
    }
}
