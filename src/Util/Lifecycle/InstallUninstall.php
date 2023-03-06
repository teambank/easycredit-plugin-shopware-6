<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Util\Lifecycle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Customer\Rule\BillingCountryRule;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\System\Currency\Rule\CurrencyRule;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Payment\Handler;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsService;
use Netzkollektiv\EasyCredit\Setting\SettingStruct;

class InstallUninstall
{
    /**
     * @var EntityRepository
     */
    private $systemConfigRepository;

    /**
     * @var EntityRepository
     */
    private $paymentMethodRepository;

    /**
     * @var EntityRepository
     */
    private $salesChannelRepository;

    /**
     * @var EntityRepository
     */
    private $countryRepository;

    /**
     * @var EntityRepository
     */
    private $currencyRepository;

    /**
     * @var PluginIdProvider
     */
    private $pluginIdProvider;

    /**
     * @var string
     */
    private $className;

    /**
     * @var SystemConfigService
     */
    private $systemConfig;

    public function __construct(
        EntityRepository $systemConfigRepository,
        $paymentMethodRepository,
        EntityRepository $salesChannelRepository,
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

    public function install(Context $context): void
    {
        $this->addDefaultConfiguration($context);
        $this->addPaymentMethods($context);
    }

    public function uninstall(Context $context): void
    {
        $this->removeConfiguration($context);
    }

    private function addDefaultConfiguration(Context $context): void
    {
        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('configurationKey', SettingsService::SYSTEM_CONFIG_DOMAIN));
        $existingSettings = $this->systemConfigRepository->search($criteria, $context);

        foreach ((new SettingStruct())->jsonSerialize() as $key => $value) {
            if ($value === null || $value === []) {
                continue;
            }

            $fullKey = SettingsService::SYSTEM_CONFIG_DOMAIN . $key;

            $sytemConfigCollection = $existingSettings->filter(function ($item) use ($fullKey) {
                return $item->getConfigurationKey() === $fullKey;
            })->getEntities();

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

        $ids = \array_map(static function ($id) {
            return ['id' => $id];
        }, $idSearchResult->getIds());

        $this->systemConfigRepository->delete($ids, $context);
    }

    private function addPaymentMethods(Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass($this->className, $context);

        $data = [
            'handlerIdentifier' => Handler::class,
            'name' => 'easyCredit-Ratenkauf',
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
            'availabilityRule' => [
                'name' => 'easyCredit-Ratenkauf - nur verfügbar in DE, bei Zahlung in EUR',
                'priority' => 1,
                'description' => 'Diese Verfügbarkeitsregel wurde automatisch bei Installation von easyCredit-Ratenkauf erstellt. Sie kann beliebig angepasst werden und wird bei Updates nicht überschrieben.',
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
                    ],
                ],
            ]
        ];

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('handlerIdentifier', Handler::class));

        $paymentMethodId = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
        if ($paymentMethodId !== null) {
            $data['id'] = $paymentMethodId;
        }

        $this->paymentMethodRepository->upsert([$data], $context);
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
