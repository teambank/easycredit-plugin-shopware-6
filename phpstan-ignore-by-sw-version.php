<?php declare(strict_types = 1);

$config = [];

/* Compatibility Classes */
if (version_compare(str_replace('v','',getenv('SW_VERSION')), '6.5', '>=')) {
  $config['parameters']['excludePaths']['analyse'][] = getenv('PLUGIN_DIR').'/src/Compatibility';
}


if (version_compare(str_replace('v','',getenv('SW_VERSION')), '6.4.4', '<=')) {
  /* ignore flow completely, does not exist in early version of SW 6 */
  $config['parameters']['excludePaths']['analyse'][] = getenv('PLUGIN_DIR').'/src/Flow/Action/*';
  
} else if (version_compare(str_replace('v','',getenv('SW_VERSION')), '6.5', '<=')) {
  /* ignore stuff introduced with 6.5 */
  $config['parameters']['ignoreErrors'][] = [
    'messages' => [
      '#Access to undefined constant Shopware.Core.Framework.Event.OrderAware..ORDER#',
      '#invalid type Shopware.Core.Content.Flow.Dispatching.StorableFlow#'
    ],
    'path' => getenv('PLUGIN_DIR').'/src/Flow/Action/*'
  ];
} else {
  /* Flow builder was refactored with v6.5.0.0 */
  $config['parameters']['ignoreErrors'][] = [
    'message' => '#invalid type Shopware.Core.Framework.Event.FlowEvent#',
    'path' => getenv('PLUGIN_DIR').'/src/Flow/Action/*'
  ];
}

return $config;
