<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Util\Lifecycle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Customer\Rule\BillingCountryRule;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\System\Currency\Rule\CurrencyRule;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Payment\Handler\BillPaymentHandler;
use Netzkollektiv\EasyCredit\Payment\Handler\InstallmentPaymentHandler;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsService;
use Netzkollektiv\EasyCredit\Setting\SettingStruct;
use Netzkollektiv\EasyCredit\Migration\Migration171257360AddBillPaymentHandler;

class InstallUninstall
{
    private EntityRepository $systemConfigRepository;

    private $paymentMethodRepository;

    private $salesChannelRepository;

    private EntityRepository $countryRepository;

    private EntityRepository $currencyRepository;

    private PluginIdProvider $pluginIdProvider;

    private string $className;

    private SystemConfigService $systemConfig;

    public function __construct(
        EntityRepository $systemConfigRepository,
        $paymentMethodRepository,
        $salesChannelRepository,
        EntityRepository $countryRepository,
        EntityRepository $currencyRepository,
        PluginIdProvider $pluginIdProvider,
        SystemConfigService $systemConfig,
        string $className
    ) {
        $this->systemConfigRepository = $systemConfigRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->className = $className;
        $this->systemConfig = $systemConfig;
    }

    public function install(InstallContext $lifecycleContext): void
    {
        $this->addDefaultConfiguration($lifecycleContext);
        $this->addPaymentMethods($lifecycleContext->getContext());
        $this->fixLegacyScriptLocation($lifecycleContext->getCurrentShopwareVersion());
    }

    public function uninstall(UninstallContext $lifecycleContext): void
    {
        $this->removeConfiguration($lifecycleContext->getContext());
    }

    public function update(UpdateContext $lifecycleContext): void
    {
        $this->addDefaultConfiguration($lifecycleContext);
        $this->addPaymentMethods($lifecycleContext->getContext());
        $this->fixLegacyScriptLocation($lifecycleContext->getCurrentShopwareVersion());
    }

    private function addDefaultConfiguration($lifecycleContext): void
    {
        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('configurationKey', SettingsService::SYSTEM_CONFIG_DOMAIN));
        $existingSettings = $this->systemConfigRepository->search($criteria, $lifecycleContext->getContext());

        foreach ((new SettingStruct())->jsonSerialize() as $key => $value) {
            if ($value === null || $value === []) {
                continue;
            }
            if ($key === 'widgetSelectorProductListing' && $lifecycleContext instanceof UpdateContext) {
                $value = ''; // do not activate listing widget in existing installations
            }

            $fullKey = SettingsService::SYSTEM_CONFIG_DOMAIN . $key;

            $sytemConfigCollection = $existingSettings->filter(fn($item) => $item->getConfigurationKey() === $fullKey)->getEntities();

            if (\count($sytemConfigCollection) === 0) {
                $this->systemConfig->set($fullKey, $value);
            }
        }
    }

    private function removeConfiguration(Context $context): void
    {
        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('configurationKey', SettingsService::SYSTEM_CONFIG_DOMAIN));
        $idSearchResult = $this->systemConfigRepository->searchIds($criteria, $context);

        $ids = \array_map(static fn($id) => ['id' => $id], $idSearchResult->getIds());

        $this->systemConfigRepository->delete($ids, $context);
    }

    private function getAvailabilityRule ($name, $context) {
        return [
            'availabilityRule' => [
                'name' => $name.' - nur verfügbar in DE, bei Zahlung in EUR',
                'priority' => 1,
                'description' => 'Diese Verfügbarkeitsregel wurde automatisch bei Installation von ' . $name . ' erstellt. 
                    Sie kann beliebig angepasst werden und wird bei Updates nicht überschrieben.',
                'conditions' => [
                    [
                        'type' => (new AndRule())->getName(),
                        'children' => [
                            [
                                'type' => (new BillingCountryRule())->getName(),
                                'value' => [
                                    'operator' => BillingCountryRule::OPERATOR_EQ,
                                    'countryIds' => $this->getCountryIds(['DE'], $context),
                                ],
                            ],
                            [
                                'type' => (new CurrencyRule())->getName(),
                                'value' => [
                                    'operator' => CurrencyRule::OPERATOR_EQ,
                                    'currencyIds' => $this->getCurrencyIds(['EUR'], $context),
                                ],
                            ]
                        ],
                    ]
                ]
            ]
        ];
    }

    private function addPaymentMethods(Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass($this->className, $context);

        $data = [
            [
                'handlerIdentifier' => InstallmentPaymentHandler::class,
                'name' => 'easyCredit-Ratenkauf',
                'technicalName' => 'easycredit_ratenkauf',
                'position' => -100,
                'pluginId' => $pluginId,
                'translations' => [
                    'de-DE' => [
                        'description' => 'easyCredit-Ratenkauf - Einfach. Fair. In Raten zahlen.',
                    ],
                    'en-GB' => [
                        'description' => 'easyCredit-Ratenkauf - Easy. Fair. Pay by installments.',
                    ],
                ],
            ],
            [
                'handlerIdentifier' => BillPaymentHandler::class,
                'name' => 'easyCredit-Rechnung',
                'technicalName' => 'easycredit_rechnung',
                'position' => -100,
                'pluginId' => $pluginId,
                'translations' => [
                    'de-DE' => [
                        'description' => 'easyCredit-Rechnung - jetzt kaufen, in 30 Tagen bezahlen',
                    ],
                    'en-GB' => [
                        'description' => 'easyCredit-Rechnung - buy now, pay in 30 days',
                    ],
                ]
            ]
        ];

        foreach ($data as &$method) {
            $method = \array_merge($method, $this->getAvailabilityRule($method['name'], $context));

            $handlerIdentifiers = [
                $method['handlerIdentifier']
            ];
            if ($method['handlerIdentifier'] === InstallmentPaymentHandler::class) {
                $handlerIdentifiers[] = Migration171257360AddBillPaymentHandler::LEGACY_HANDLER_IDENTIFIER;
            }
            $criteria = (new Criteria())
                ->addFilter(new EqualsAnyFilter('handlerIdentifier', $handlerIdentifiers));

            if (isset($data[0]['id'])) { // if installment exists, but billPayment does not, this must be an update => leave billPayment inactive
                $method['active'] = false;
            }

            $paymentMethodId = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
            if ($paymentMethodId !== null) {
                $method['id'] = $paymentMethodId;
            }

            $this->paymentMethodRepository->upsert([$method], $context);
        }


    }

    protected function fixLegacyScriptLocation($swVersion) {
        if (\version_compare($swVersion, '6.6.0', '>=')) {
            return;
        }

        $pluginDir = \dirname(__FILE__).'/../..';
        $scriptsDir = $pluginDir . '/Resources/app/storefront/dist/storefront/js';
        $legacyFilePath = $scriptsDir . '/easy-credit-ratenkauf.js';
        $newFilePath = $scriptsDir . '/easy-credit-ratenkauf/easy-credit-ratenkauf.js';
        if (\file_exists($newFilePath)) {
            if (!\file_exists($legacyFilePath)) {
              \rename($newFilePath, $legacyFilePath); // move the shipped file to legacy location
            } else {
              \unlink($newFilePath); // delete the >= SW6.6 file to prevent duplication
            }
        }
    }

    protected function getCountryIds(array $countryIsos, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('iso', $countryIsos));

        /** @var string[] $countryIds */
        $countryIds = $this->countryRepository->searchIds($criteria, $context)->getIds();

        if (empty($countryIds)) {
            // if country does not exist, enter invalid uuid so rule always fails. empty is not allowed
            return [Uuid::randomHex()];
        }

        return $countryIds;
    }

    protected function getCurrencyIds(array $currencyCodes, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('isoCode', $currencyCodes));

        /** @var string[] $currencyIds */
        $currencyIds = $this->currencyRepository->searchIds($criteria, $context)->getIds();

        if (empty($currencyIds)) {
            // if currency does not exist, enter invalid uuid so rule always fails. empty is not allowed
            return [Uuid::randomHex()];
        }

        return $currencyIds;
    }
}
