<?php

use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI === 'cli') {
  ini_set('memory_limit', '512M');
}

if ($simpletest_db = getenv('SIMPLETEST_DB')) {
  $parts = parse_url($simpletest_db);
  putenv(sprintf('DRUPAL_DB_NAME=%s', substr($parts['path'], 1)));
  putenv(sprintf('DRUPAL_DB_USER=%s', $parts['user']));
  putenv(sprintf('DRUPAL_DB_PASS=%s', $parts['pass']));
  putenv(sprintf('DRUPAL_DB_HOST=%s', $parts['host']));
}

$databases['default']['default'] = [
  'database' => getenv('DRUPAL_DB_NAME'),
  'username' => getenv('DRUPAL_DB_USER'),
  'password' => getenv('DRUPAL_DB_PASS'),
  'prefix' => '',
  'host' => getenv('DRUPAL_DB_HOST'),
  'port' => getenv('DRUPAL_DB_PORT') ?: 3306,
  'namespace' => 'Drupal\Core\Database\Driver\mysql',
  'driver' => 'mysql',
  'charset' => 'utf8mb4',
  'collation' => 'utf8mb4_swedish_ci',
];

$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT') ?: '000';

if ($ssl_ca_path = getenv('AZURE_SQL_SSL_CA_PATH')) {
  $databases['default']['default']['pdo'] = [
    \PDO::MYSQL_ATTR_SSL_CA => $ssl_ca_path,
    \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => FALSE,
  ];
  // Azure specific filesystem fixes.
  $settings['php_storage']['twig']['directory'] = '/tmp';
  $settings['php_storage']['twig']['secret'] = $settings['hash_salt'];
  $settings['file_chmod_directory'] = 16895;
  $settings['file_chmod_file'] = 16895;

  $config['system.performance']['cache']['page']['max_age'] = 86400;
}

// Only in Wodby environment.
// @see https://wodby.com/docs/stacks/drupal/#overriding-settings-from-wodbysettingsphp
if (isset($_SERVER['WODBY_APP_NAME'])) {
  // The include won't be added automatically if it's already there.
  include '/var/www/conf/wodby.settings.php';
}

$config['openid_connect.client.tunnistamo']['settings']['client_id'] = getenv('TUNNISTAMO_CLIENT_ID');
$config['openid_connect.client.tunnistamo']['settings']['client_secret'] = getenv('TUNNISTAMO_CLIENT_SECRET');

if(getenv('APP_ENV') == 'production'){
  $config['openid_connect.client.tunnistamo']['settings']['is_production'] = true;
  $config['openid_connect.client.tunnistamo']['settings']['environment_url'] = 'https://api.hel.fi/sso';
} else {
  if(getenv('APP_ENV') == 'development') {
    $config['openid_connect.client.tunnistamo']['settings']['environment_url'] = 'https://tunnistamo.test.hel.ninja';
  }
  $config['openid_connect.client.tunnistamo']['settings']['is_production'] = false;
}

$config['siteimprove.settings']['prepublish_enabled'] = TRUE;
$config['siteimprove.settings']['api_username'] = getenv('SITEIMPROVE_API_USERNAME');
$config['siteimprove.settings']['api_key'] = getenv('SITEIMPROVE_API_KEY');

$settings['matomo_site_id'] = getenv('MATOMO_SITE_ID');
$settings['siteimprove_id'] = getenv('SITEIMPROVE_ID');

// Drupal route(s).
$routes = (getenv('DRUPAL_ROUTES')) ? explode(',', getenv('DRUPAL_ROUTES')) : [];

foreach ($routes as $route) {
  $hosts[] = $host = parse_url($route)['host'];
  $trusted_host = str_replace('.', '\.', $host);
  $settings['trusted_host_patterns'][] = '^' . $trusted_host . '$';
}

$drush_options_uri = getenv('DRUSH_OPTIONS_URI');

if ($drush_options_uri && !in_array($drush_options_uri, $routes)) {
  $host = str_replace('.', '\.', parse_url($drush_options_uri)['host']);
  $settings['trusted_host_patterns'][] = '^' . $host . '$';
}

$settings['config_sync_directory'] = '../conf/cmi';
$settings['file_public_path'] = getenv('DRUPAL_FILES_PUBLIC') ?: 'sites/default/files';
$settings['file_private_path'] = getenv('DRUPAL_FILES_PRIVATE');
$settings['file_temp_path'] = getenv('DRUPAL_TMP_PATH') ?: '/tmp';

set_error_handler(['Drupal\error_page\ErrorPageErrorHandler', 'handleError']);
set_exception_handler([
  'Drupal\error_page\ErrorPageErrorHandler',
  'handleException',
]);
// Log the UUID in the Drupal logs.
$settings['error_page']['uuid'] = TRUE;
// Your templates are located in path/to/templates, one level above the webroot.
$settings['error_page']['template_dir'] = DRUPAL_ROOT . '/../error_templates';


if ($reverse_proxy_address = getenv('DRUPAL_REVERSE_PROXY_ADDRESS')) {
  $reverse_proxy_address = explode(',', $reverse_proxy_address);

  if (isset($_SERVER['REMOTE_ADDR'])) {
    $reverse_proxy_address[] = $_SERVER['REMOTE_ADDR'];
  }
  $settings['reverse_proxy'] = TRUE;
  $settings['reverse_proxy_addresses'] = $reverse_proxy_address;
  $settings['reverse_proxy_trusted_headers'] = Request::HEADER_X_FORWARDED_ALL;
  $settings['reverse_proxy_host_header'] = 'X_FORWARDED_HOST';
}

if (file_exists(__DIR__ . '/all.settings.php')) {
  include __DIR__ . '/all.settings.php';
}

if ($env = getenv('APP_ENV')) {
  if (file_exists(__DIR__ . '/' . $env . '.settings.php')) {
    include __DIR__ . '/' . $env . '.settings.php';
  }

  if (file_exists(__DIR__ . '/' . $env . '.services.yml')) {
    $settings['container_yamls'][] = __DIR__ . '/' . $env . '.services.yml';
  }

  if (file_exists(__DIR__ . '/local.services.yml')) {
    $settings['container_yamls'][] = __DIR__ . '/local.services.yml';
  }

  if (file_exists(__DIR__ . '/local.settings.php')) {
    include __DIR__ . '/local.settings.php';
  }
}

if ($blob_storage_name = getenv('AZURE_BLOB_STORAGE_NAME')) {
  $schemes = [
    'azure' => [
      'driver' => 'helfi_azure',
      'config' => [
        'name' => $blob_storage_name,
        'key' => getenv('AZURE_BLOB_STORAGE_KEY'),
        'container' => getenv('AZURE_BLOB_STORAGE_CONTAINER'),
        'endpointSuffix' => 'core.windows.net',
        'protocol' => 'https',
      ],
      'cache' => TRUE,
    ],
  ];
  $config['helfi_azure_fs.settings']['use_blob_storage'] = TRUE;
  $settings['flysystem'] = $schemes;
}


if ($varnish_host = getenv('DRUPAL_VARNISH_HOST')) {
  $config['varnish_purger.settings.default']['hostname'] = $varnish_host;
  $config['varnish_purger.settings.varnish_purge_all']['hostname'] = $varnish_host;

  if (!isset($config['system.performance']['cache']['page']['max_age'])) {
    $config['system.performance']['cache']['page']['max_age'] = 86400;
  }
}

if ($varnish_port = getenv('DRUPAL_VARNISH_PORT')) {
  $config['varnish_purger.settings.default']['port'] = $varnish_port;
  $config['varnish_purger.settings.varnish_purge_all']['port'] = $varnish_port;
}

$config['varnish_purger.settings.default']['headers'] = [
  [
    'field' => 'Cache-Tags',
    'value' => '[invalidation:expression]',
  ],
];

$config['varnish_purger.settings.varnish_purge_all']['headers'] = [
  [
    'field' => 'X-VC-Purge-Method',
    'value' => 'regex',
  ],
];

if ($varnish_purge_key = getenv('VARNISH_PURGE_KEY')) {
  // Configuration doesn't know about existing config yet so we can't
  // just append new headers to an already existing headers array here.
  // If you have configured any extra headers in your purge settings
  // you must add them here as well.
  // @todo Replace this with config override service?
  $config['varnish_purger.settings.default']['headers'][] = [
    'field' => 'X-VC-Purge-Key',
    'value' => $varnish_purge_key,
  ];
  $config['varnish_purger.settings.varnish_purge_all']['headers'][] = [
    'field' => 'X-VC-Purge-Key',
    'value' => $varnish_purge_key,
  ];
}

if ($stage_file_proxy_origin = getenv('STAGE_FILE_PROXY_ORIGIN')) {
  $config['stage_file_proxy.settings']['origin'] = $stage_file_proxy_origin;
  $config['stage_file_proxy.settings']['origin_dir'] = getenv('STAGE_FILE_PROXY_ORIGIN_DIR') ?: 'test';
  $config['stage_file_proxy.settings']['hotlink'] = FALSE;
  $config['stage_file_proxy.settings']['use_imagecache_root'] = FALSE;
}

if (
  ($redis_host = getenv('REDIS_HOST')) &&
  file_exists('modules/contrib/redis/example.services.yml') &&
  extension_loaded('redis')
) {
  // Redis namespace is not available until redis module is enabled, so
  // we have to manually register it in order to enable the module and have
  // this configuration when the module is installed, but not yet enabled.
  $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');
  $redis_port = getenv('REDIS_PORT') ?: 6379;

  // Force SSL on azure.
  if (getenv('AZURE_SQL_SSL_CA_PATH')) {
    $redis_host = 'tls://' . $redis_host;
  }

  if ($redis_prefix = getenv('REDIS_PREFIX')) {
    $settings['cache_prefix']['default'] = $redis_prefix;
  }

  if ($redis_password = getenv('REDIS_PASSWORD')) {
    $settings['redis.connection']['password'] = $redis_password;
  }
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = $redis_host;
  $settings['redis.connection']['port'] = $redis_port;
  $settings['cache']['default'] = 'cache.backend.redis';
  $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';
  // Register redis services to make sure we don't get a non-existent service
  // error while trying to enable the module.
  $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';
}
