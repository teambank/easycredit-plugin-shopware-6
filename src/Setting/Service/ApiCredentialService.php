<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Setting\Service;

use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Teambank\EasyCreditApiV3\ApiException;
use Teambank\EasyCreditApiV3\Integration\ApiCredentialsInvalidException;
use Teambank\EasyCreditApiV3\Integration\ApiCredentialsNotActiveException;

class ApiCredentialService implements ApiCredentialServiceInterface
{
    private IntegrationFactory $integrationFactory;

    public function __construct(
        IntegrationFactory $integrationFactory
    ) {
        $this->integrationFactory = $integrationFactory;
    }

    /**
     * @throws ApiCredentialsInvalidException
     */
    public function testApiCredentials(string $webshopId, string $apiPassword, string $apiSignature = null): bool
    {
        if (!$webshopId || !$apiPassword) {
            throw new ApiCredentialsInvalidException();
        }

        $checkout = $this->integrationFactory->createCheckout(null, false);
        $checkout->verifyCredentials($webshopId, $apiPassword, $apiSignature);

        return true;
    }
}
