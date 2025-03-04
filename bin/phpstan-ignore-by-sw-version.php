<?php

declare(strict_types=1);

if (getenv('SW_VERSION') === false) {
  throw new Exception('SW_VERSION env var needs to be defined');
}
if (getenv('PLUGIN_DIR') === false) {
  throw new Exception('PLUGIN_DIR env var needs to be defined');
}

if (!defined('DIR')) {
  define('DIR', getenv('PLUGIN_DIR'));
}

if (!function_exists('vcompare')) {
  function vcompare($operator, $version)
  {
    return \version_compare(str_replace('v', '', getenv('SW_VERSION')), $version, $operator);
  }
}

$config = [];

/* Compatibility Classes */
if (vcompare('>=', '6.5')) {
  $config['parameters']['excludePaths']['analyse'][] = DIR . '/src/Compatibility';
}

if (vcompare('<=', '6.4.4')) {
  /* ignore flow completely, does not exist in early version of SW 6 */
  $config['parameters']['excludePaths']['analyse'][] = DIR . '/src/Flow/Action/*';

  /* ignore, rule evaluation is skipped in SW <= 6.4.18 */
  $config['parameters']['excludePaths']['analyse'][] = DIR . '/src/Subscriber/PreventCartPersistDuringRuleEvaluation.php';
} else if (vcompare('<=', '6.5')) {
  /* ignore stuff introduced with 6.5 */
  $config['parameters']['ignoreErrors'][] = [
    'messages' => [
      '#Access to undefined constant Shopware.Core.Framework.Event.OrderAware..ORDER#',
      '#invalid type Shopware.Core.Content.Flow.Dispatching.StorableFlow#'
    ],
    'path' => DIR . '/src/Flow/Action/*'
  ];
} else {
  /* Flow builder was refactored with v6.5.0.0 */
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#invalid type Shopware.Core.Framework.Event.FlowEvent#',
    'path' => DIR . '/src/Flow/Action/*'
  ];
}

if (vcompare('<=', '6.4.6.0') || vcompare('>=', '6.5.1.0')) {
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#unknown class Shopware.Core.Framework.Event.FlowEvent#',
    'paths' => [
      DIR . '/src/Flow/Action/CaptureAction.php',
      DIR . '/src/Flow/Action/RefundAction.php'
    ]
  ];
}

if (vcompare('<', '6.4.18.0')) {
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#Shopware.Core.System.SalesChannel.Event.SalesChannelContextCreatedEvent#',
    'paths' => DIR . '/src/Payment/StorageInitializer.php '
  ];
}

if (version_compare(str_replace('v', '', getenv('SW_VERSION')), '6.5', '>=')) {
  // getHandler was changed to getPaymentHandler
  $config['parameters']['ignoreErrors'][] = [
    'messages' => [
      '#Call to function method_exists.. with Shopware.Core.Checkout.Payment.Cart.PaymentHandler.PaymentHandlerRegistry#',
      '#Call to an undefined method Shopware.Core.Checkout.Payment.Cart.PaymentHandler.PaymentHandlerRegistry::getHandler#'
    ],
    'path' => DIR . '/src/Helper/Payment.php'
  ];
}

// constructor changed > 6.5, handled in code using reflection
$config['parameters']['ignoreErrors'][] = [
  'message' => '#Class Shopware.Core.Checkout.Cart.Cart constructor invoked with#',
  'path' => DIR . '/src/Service/RuleEvaluator.php'
];

// false positive, phpstan does not recognize added key (foreach, by reference)
$config['parameters']['ignoreErrors'][] = [
  'message' => "#Offset \'id\' on array#",
  'path' => DIR . '/src/Util/Lifecycle/InstallUninstall.php'
];

return $config;
