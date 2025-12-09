<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\CustomerResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Teambank\EasyCreditApiV3\Model\TransactionInformation as EasyCreditTransaction;

class CustomerService
{

    public const EXPRESS_ACTIVE = 'easyCreditExpressActive';

    private AbstractRegisterRoute $registerRoute;


    private EntityRepository $countryRepository;

    private EntityRepository $salutationRepository;

    private SystemConfigService $systemConfigService;

    private  SalesChannelContextServiceInterface $contextService;

    public function __construct(
        AbstractRegisterRoute $registerRoute,
        SalesChannelContextServiceInterface $contextService,
        EntityRepository $countryRepository,
        EntityRepository $salutationRepository,
        SystemConfigService $systemConfigService
    ) {
        $this->registerRoute = $registerRoute;
        $this->contextService = $contextService;
        $this->countryRepository = $countryRepository;
        $this->salutationRepository = $salutationRepository;
        $this->systemConfigService = $systemConfigService;
    }

    public function handleExpress(EasyCreditTransaction $transaction, SalesChannelContext $context): SalesChannelContext
    {
        $context->getContext()->addExtension(self::EXPRESS_ACTIVE, new ArrayStruct());

        if ($context->getCustomer() === null) {
            $response = $this->registerCustomer($transaction, $context);
            $customerId = $response->getCustomer()->getId();
            $token = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);
        } else {
            $customerId = $context->getCustomer()->getId();
            $token = $context->getToken();
        }

        return $this->reinitializeContext($token, $customerId, $context);
    }

    private function registerCustomer(EasyCreditTransaction $transaction, SalesChannelContext $context): CustomerResponse
    {
        $context->getContext()->addExtension(self::EXPRESS_ACTIVE, new ArrayStruct());
        $customerDataBag = $this->getRegisterCustomerDataBag($transaction, $context);
        $response = $this->registerRoute->register($customerDataBag, $context, false);
        $context->getContext()->removeExtension(self::EXPRESS_ACTIVE);

        return $response;
    }

    private function getRegisterCustomerDataBag(EasyCreditTransaction $transaction, SalesChannelContext $salesChannelContext): RequestDataBag
    {
        $salutationId = $this->getSalutationId($salesChannelContext->getContext());

        $customer = $transaction->getTransaction()->getCustomer();
        $contact = $customer->getContact();
        $address = $transaction->getTransaction()->getOrderDetails()->getShippingAddress();

        return new RequestDataBag([
            'guest' => true,
            'storefrontUrl' => $this->getStorefrontUrl($salesChannelContext),
            'salutationId' => $salutationId,
            'email' => $contact->getEmail(),
            'firstName' => $address->getFirstName(),
            'lastName' => $address->getLastName(),
            'billingAddress' => $this->getBillingAddressFromTransaction($transaction, $salesChannelContext->getContext(), $salutationId),
            'acceptedDataProtection' => true
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function getBillingAddressFromTransaction(EasyCreditTransaction $transaction, Context $context, ?string $salutationId = null): array
    {
        $address = $transaction->getTransaction()->getOrderDetails()->getShippingAddress();
        $countryId = $this->getCountryId($address->getCountry(), $context);

        return [
            'firstName' => $address->getFirstName(),
            'lastName' => $address->getLastName(),
            'salutationId' => $salutationId,
            'street' => $address->getAddress(),
            'zipcode' => $address->getZip(),
            'countryId' => $countryId,
            'phoneNumber' => $transaction->getTransaction()->getCustomer()->getContact()->getMobilePhoneNumber(),
            'city' => $address->getCity()
        ];
    }

    private function getCountryId(string $code, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $code));

        return $this->countryRepository->searchIds($criteria, $context)->firstId();
    }

    private function getSalutationId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('salutationKey', 'not_specified'));

        $salutationId = $this->salutationRepository->searchIds($criteria, $context)->firstId();

        if ($salutationId !== null) {
            return $salutationId;
        }

        $salutationId = $this->salutationRepository->searchIds($criteria->resetFilters(), $context)->firstId();

        if ($salutationId === null) {
            throw new \RuntimeException('No salutation found in Shopware');
        }

        return $salutationId;
    }

    private function getStorefrontUrl(SalesChannelContext $salesChannelContext): string
    {
        $salesChannel = $salesChannelContext->getSalesChannel();
        $domainUrl = $this->systemConfigService->get('core.loginRegistration.doubleOptInDomain', $salesChannel->getId());

        if (\is_string($domainUrl) && $domainUrl !== '') {
            return $domainUrl;
        }

        $domains = $salesChannel->getDomains();
        if ($domains === null) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        $domain = $domains->first();
        if ($domain === null) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        return $domain->getUrl();
    }

    private function reinitializeContext(string $newToken, string $customerId, SalesChannelContext $context): SalesChannelContext
    {
        if ($newToken === null || $newToken === '') {
            if (\class_exists(RoutingException::class)) {
                throw RoutingException::missingRequestParameter(PlatformRequest::HEADER_CONTEXT_TOKEN);
            }
            if (\class_exists(MissingRequestParameterException::class)) {
                throw new MissingRequestParameterException(PlatformRequest::HEADER_CONTEXT_TOKEN);
            }
        }

        $newContext = $this->contextService->get(
            /** @phpstan-ignore-next-line */
            new SalesChannelContextServiceParameters(
                $context->getSalesChannel()->getId(),
                $newToken,
                $context->getContext()->getLanguageId(),
                $context->getCurrencyId(),
                $context->getDomainId(),
                $context->getContext(),
                $customerId
            )
        );
        $newContext->addState(...$context->getStates());

        return $newContext;
    }
}
