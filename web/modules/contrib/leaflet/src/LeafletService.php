<?php

namespace Drupal\leaflet;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a  LeafletService class.
 */
class LeafletService {

  use StringTranslationTrait;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The cache backend default service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The http client, NULL until the container has been rebuilt.
   *
   * @var \GuzzleHttp\ClientInterface|null
   */
  protected $httpClient;

  /**
   * Icon sizes already resolved in this request, keyed by cache prefix and url.
   *
   * A FALSE value means the size could not be determined.
   *
   * @var array
   */
  protected $iconSizes = [];

  /**
   * Seconds to wait for the connection when an icon has to be requested.
   */
  const ICON_CONNECT_TIMEOUT = 1;

  /**
   * Seconds to wait for the whole request when an icon has to be requested.
   */
  const ICON_TIMEOUT = 2;

  /**
   * Seconds to remember that an icon size could not be determined.
   */
  const ICON_FAILURE_TTL = 3600;

  /**
   * LeafletService constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The stream wrapper manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend leaflet service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \GuzzleHttp\ClientInterface|null $http_client
   *   The http client. Optional, so that a container compiled before this
   *   argument was added does not fatal before it is rebuilt. Until then, the
   *   size of an icon hosted elsewhere cannot be determined.
   */
  public function __construct(
    AccountInterface $current_user,
    GeoPHPInterface $geophp_wrapper,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    RequestStack $request_stack,
    CacheBackendInterface $cache,
    FileUrlGeneratorInterface $file_url_generator,
    ?ClientInterface $http_client = NULL,
  ) {
    $this->currentUser = $current_user;
    $this->geoPhpWrapper = $geophp_wrapper;
    $this->moduleHandler = $module_handler;
    $this->link = $link_generator;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->requestStack = $request_stack;
    $this->cache = $cache;
    $this->fileUrlGenerator = $file_url_generator;
    $this->httpClient = $http_client;
  }

  /**
   * Load all Leaflet required client files and return markup for a map.
   *
   * @param array $map
   *   The map settings array.
   * @param array $features
   *   The features array.
   * @param string $height
   *   The height value string.
   *
   * @return array
   *   The leaflet_map render array.
   */
  public function leafletRenderMap(array $map, array $features = [], $height = '400px') {
    $map_id = $map['id'] ?? Html::getUniqueId('leaflet_map');

    $attached_libraries = ['leaflet/general', 'leaflet/leaflet-drupal'];
    $cache_contexts = [];

    // Check for the definition of a vector type layer
    // and eventually add MapLibre GL Leaflet library.
    $map_layers = $map["layers"] ?? [];
    foreach ($map_layers as $layer) {
      if (isset($layer["type"]) &&  $layer["type"] === 'vector') {
        $attached_libraries[] = 'leaflet/maplibre-gl-leaflet';
        break;
      }
    }

    // Add the Leaflet Reset View library, if requested.
    if (isset($map['settings']['reset_map']) && is_array($map['settings']['reset_map']) && array_key_exists('control', $map['settings']['reset_map']) && $map['settings']['reset_map']['control']) {
      $attached_libraries[] = 'leaflet/leaflet.reset_map_view';
    }

    // Add the Leaflet Locate library, if requested.
    if (isset($map['settings']['locate']) && !empty($map['settings']['locate']['control'])) {
      $attached_libraries[] = 'leaflet/leaflet.locatecontrol';
    }

    // Add the Leaflet Geocoder library and functionalities, if requested,
    // and the user has access to Geocoder Api Enpoints.
    if (!empty($map['settings']['geocoder']['control'])) {
      $this->setGeocoderControlSettings($map['settings']['geocoder'], $attached_libraries);
      // Geocoder control visibility depends on user permissions.
      $cache_contexts[] = 'user.permissions';
    }

    // Add the Leaflet Fullscreen library, if requested.
    if (isset($map['settings']['fullscreen']) && !empty($map['settings']['fullscreen']['control'])) {
      $attached_libraries[] = 'leaflet/leaflet.fullscreen';
    }

    // Add the Leaflet Gesture Handling library, if requested.
    if (!empty($map['settings']['gestureHandling'])) {
      $attached_libraries[] = 'leaflet/leaflet.gesture_handling';
    }

    // Add the Leaflet Markercluster library and functionalities, if requested.
    if ($this->moduleHandler->moduleExists('leaflet_markercluster') && isset($map['settings']['leaflet_markercluster']) && $map['settings']['leaflet_markercluster']['control']) {
      $attached_libraries[] = 'leaflet_markercluster/leaflet-markercluster';
      $attached_libraries[] = 'leaflet_markercluster/leaflet-markercluster-drupal';
    }

    $settings[$map_id] = [
      'mapid' => $map_id,
      'map' => $map,
      // JS only works with arrays, make sure we have one with numeric keys.
      'features' => array_values($features),
    ];
    return [
      '#theme' => 'leaflet_map',
      '#map_id' => $map_id,
      '#height' => $height,
      '#map' => $map,
      '#attached' => [
        'library' => $attached_libraries,
        'drupalSettings' => [
          'leaflet' => $settings,
        ],
      ],
      '#cache' => [
        'contexts' => $cache_contexts,
      ],
    ];
  }

  /**
   * Get all available Leaflet map definitions.
   *
   * @param string $map
   *   The specific map definition string.
   *
   * @return array
   *   The leaflet maps definition array.
   */
  public function leafletMapGetInfo($map = NULL) {
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['leaflet_map_info'] = &drupal_static(__FUNCTION__);
    }
    $map_info = &$drupal_static_fast['leaflet_map_info'];

    if (empty($map_info)) {
      if ($cached = $this->cache->get('leaflet_map_info')) {
        $map_info = $cached->data;
      }
      else {
        $map_info = $this->moduleHandler->invokeAll('leaflet_map_info');

        // Let other modules alter the map info.
        $this->moduleHandler->alter('leaflet_map_info', $map_info);

        $this->cache->set('leaflet_map_info', $map_info);
      }
    }

    if (empty($map)) {
      return $map_info;
    }
    else {
      return $map_info[$map] ?? [];
    }

  }

  /**
   * Convert a geofield into an array of map points.
   *
   * The map points can then be fed into $this->leafletRenderMap().
   *
   * @param mixed $items
   *   A single value or array of geo values, each as a string in any of the
   *   supported formats or as an array of $item elements, each with an
   *   $item['wkt'] field.
   *
   * @return array
   *   The return array.
   */
  public function leafletProcessGeofield($items = []) {

    if (!is_array($items)) {
      $items = [$items];
    }
    $data = [];
    foreach ($items as $item) {
      // Auto-detect and parse the format (e.g. WKT, JSON etc.).
      /** @var \GeometryCollection $geom */
      if (!($geom = $this->geoPhpWrapper->load($item['wkt'] ?? $item))) {
        continue;
      }
      $data[] = $this->leafletProcessGeometry($geom);

    }
    return $data;
  }

  /**
   * Process the Geometry Collection.
   *
   * @param \Geometry $geom
   *   The Geometry.
   *
   * @return array
   *   The return array.
   */
  private function leafletProcessGeometry(\Geometry $geom) {
    $datum = ['type' => strtolower($geom->geometryType())];

    switch ($datum['type']) {
      case 'point':
        $datum = [
          'type' => 'point',
          'lat' => $geom->getY(),
          'lon' => $geom->getX(),
        ];
        break;

      case 'linestring':
        /** @var \GeometryCollection $geom */
        $components = $geom->getComponents();
        /** @var \Geometry $component */
        foreach ($components as $component) {
          $datum['points'][] = [
            'lat' => $component->getY(),
            'lon' => $component->getX(),
          ];
        }
        break;

      case 'polygon':
        /** @var \GeometryCollection $geom */
        $polygon_components = $geom->getComponents();
        /** @var \GeometryCollection $geom */
        foreach ($polygon_components as $k => $geom) {
          $points = $geom->getComponents();
          foreach ($points as $point) {
            $datum['points'][$k][] = [
              'lat' => $point->getY(),
              'lon' => $point->getX(),
            ];
          }
        }
        break;

      case 'multipolyline':
      case 'multilinestring':
        if ($datum['type'] == 'multilinestring') {
          $datum['type'] = 'multipolyline';
          $datum['multipolyline'] = TRUE;
        }
        /** @var \GeometryCollection $geom */
        $components = $geom->getComponents();
        /** @var \GeometryCollection $component */
        foreach ($components as $key => $component) {
          $subcomponents = $component->getComponents();
          /** @var \Geometry $subcomponent */
          foreach ($subcomponents as $subcomponent) {
            $datum['component'][$key]['points'][] = [
              'lat' => $subcomponent->getY(),
              'lon' => $subcomponent->getX(),
            ];
          }
          unset($subcomponent);
        }
        break;

      case 'multipolygon':
        /** @var \GeometryCollection $geom */
        $polygons = $geom->getComponents();
        /** @var \GeometryCollection $polygon */
        foreach ($polygons as $j => $polygon) {
          $polygon_components = $polygon->getComponents();
          foreach ($polygon_components as $k => $geom) {
            $points = $geom->getComponents();
            foreach ($points as $point) {
              $datum['points'][$j][$k][] = [
                'lat' => $point->getY(),
                'lon' => $point->getX(),
              ];
            }
          }
        }
        break;

      case 'geometrycollection':
      case 'multipoint':
        /** @var \GeometryCollection $geom */
        $components = $geom->getComponents();
        foreach ($components as $key => $component) {
          $datum['component'][$key] = $this->leafletProcessGeometry($component);
        }
        break;

    }
    return $datum;
  }

  /**
   * Leaflet Icon Documentation Link.
   *
   * @return \Drupal\Core\GeneratedLink
   *   The Leaflet Icon Documentation Link.
   */
  public function leafletIconDocumentationLink() {
    return $this->link->generate($this->t('Leaflet Icon Documentation'), Url::fromUri('https://leafletjs.com/reference.html#icon', [
      'absolute' => TRUE,
      'attributes' => ['target' => 'blank'],
    ]));
  }

  /**
   * Set Feature Icon Size & Shadow Size If Empty or Invalid.
   *
   * @param array $feature
   *   The feature.
   */
  public function setFeatureIconSizesIfEmptyOrInvalid(array &$feature) {
    $this->setSizeIfEmptyOrInvalid($feature, 'icon', 'iconUrl', 'iconSize', 'leaflet_iconsize_cache');
    $this->setSizeIfEmptyOrInvalid($feature, 'shadow', 'shadowUrl', 'shadowSize', 'leaflet_shadowsize_cache');
  }

  /**
   * Set Size If Empty or Invalid.
   *
   * @param array $feature
   *   The feature.
   * @param string $type
   *   The type.
   * @param string $urlKey
   *   The url key.
   * @param string $sizeKey
   *   The size key.
   * @param string $cachePrefix
   *   The cache prefix.
   */
  protected function setSizeIfEmptyOrInvalid(array &$feature, string $type, string $urlKey, string $sizeKey, string $cachePrefix) {
    $uri = $feature["icon"][$urlKey] ?? NULL;
    if (empty($uri) || !isset($feature["icon"][$sizeKey])
      || (intval($feature["icon"][$sizeKey]["x"]) !== 0 && intval($feature["icon"][$sizeKey]["y"]) !== 0)) {
      return;
    }

    $size = $this->getIconSize($uri, $this->generateAbsoluteString($uri), $cachePrefix);
    if (!$size) {
      return;
    }

    [$size_x, $size_y] = $size;
    if (empty($feature["icon"][$sizeKey]["x"]) && !empty($feature["icon"][$sizeKey]["y"])) {
      $feature["icon"][$sizeKey]["x"] = intval($feature["icon"][$sizeKey]["y"] * $size_x / $size_y);
    }
    elseif (!empty($feature["icon"][$sizeKey]["x"]) && empty($feature["icon"][$sizeKey]["y"])) {
      $feature["icon"][$sizeKey]["y"] = intval($feature["icon"][$sizeKey]["x"] * $size_y / $size_x);
    }
    else {
      $feature["icon"][$sizeKey]["x"] = $size_x;
      $feature["icon"][$sizeKey]["y"] = $size_y;
    }
  }

  /**
   * Get the intrinsic size of an icon, from the cache when possible.
   *
   * Both outcomes are cached, so that an icon that cannot be measured is not
   * looked up again for every feature of every map.
   *
   * @param string $uri
   *   The icon uri, as configured.
   * @param string $url
   *   The absolute icon url.
   * @param string $cachePrefix
   *   The cache prefix.
   *
   * @return array|null
   *   The [width, height] of the icon, or NULL if it could not be determined.
   */
  protected function getIconSize(string $uri, string $url, string $cachePrefix): ?array {
    $key = $cachePrefix . ':' . $url;
    if (isset($this->iconSizes[$key])) {
      return $this->iconSizes[$key] ?: NULL;
    }

    $cid = 'leaflet_map_icon_size:' . $key;
    if ($cached = $this->cache->get($cid)) {
      $this->iconSizes[$key] = $cached->data ?: FALSE;
      return $cached->data ?: NULL;
    }

    $size = $this->readIconSize($uri, $url);
    $this->iconSizes[$key] = $size ?: FALSE;
    $expire = $size ? Cache::PERMANENT : time() + self::ICON_FAILURE_TTL;
    $this->cache->set($cid, $size, $expire);

    return $size;
  }

  /**
   * Read the intrinsic size of an icon.
   *
   * @param string $uri
   *   The icon uri, as configured.
   * @param string $url
   *   The absolute icon url.
   *
   * @return array|null
   *   The [width, height] of the icon, or NULL if it could not be determined.
   */
  protected function readIconSize(string $uri, string $url): ?array {
    $path = $this->iconFilePath($uri, $url);
    if ($path !== NULL) {
      $contents = @file_get_contents($path);
    }
    elseif (!$this->isOwnHost($url)) {
      $contents = $this->requestIcon($url);
    }
    else {
      // Served by this site, but there is no file behind it. Requesting it
      // would only return our own 404 page.
      return NULL;
    }

    if ($contents === FALSE || $contents === NULL) {
      return NULL;
    }

    $fileParts = pathinfo($path ?? (parse_url($url, PHP_URL_PATH) ?: $url));
    if (isset($fileParts['extension']) && strtolower($fileParts['extension']) === 'svg') {
      // An svg without usable dimensions still gets the historical default.
      return $this->svgSize($contents) ?: [40, 40];
    }

    $size = @getimagesizefromstring($contents);

    return (!empty($size[0]) && !empty($size[1])) ? [$size[0], $size[1]] : NULL;
  }

  /**
   * Get the readable path of an icon served from the local file system.
   *
   * @param string $uri
   *   The icon uri, as configured.
   * @param string $url
   *   The absolute icon url.
   *
   * @return string|null
   *   The local path, or NULL if the icon is not a readable local file.
   */
  protected function iconFilePath(string $uri, string $url): ?string {
    // Stream wrappers know their own path, which may sit outside the docroot.
    $scheme = StreamWrapperManager::getScheme($uri);
    if ($scheme && $this->streamWrapperManager->isValidScheme($scheme)) {
      $wrapper = $this->streamWrapperManager->getViaUri($uri);
      $path = $wrapper instanceof LocalStream ? $wrapper->realpath() : FALSE;
      return ($path && is_file($path)) ? $path : NULL;
    }

    if (!$this->isOwnHost($url) || !defined('DRUPAL_ROOT')) {
      return NULL;
    }

    $path = rawurldecode(parse_url($url, PHP_URL_PATH) ?: '');
    $base_path = base_path();
    if ($base_path !== '/' && strpos($path, $base_path) === 0) {
      $path = substr($path, strlen($base_path) - 1);
    }

    $root = realpath(DRUPAL_ROOT);
    $real = $path ? realpath($root . '/' . ltrim($path, '/')) : FALSE;
    if (!$root || !$real || strpos($real, $root . DIRECTORY_SEPARATOR) !== 0) {
      return NULL;
    }

    return is_file($real) ? $real : NULL;
  }

  /**
   * Check whether a url is served by the host handling the current request.
   *
   * @param string $url
   *   The url to check.
   *
   * @return bool
   *   TRUE if the url points at this site, FALSE otherwise.
   */
  protected function isOwnHost(string $url): bool {
    if (!UrlHelper::isExternal($url)) {
      return TRUE;
    }
    $request = $this->requestStack->getCurrentRequest();

    return $request && strpos($url, $request->getSchemeAndHttpHost() . '/') === 0;
  }

  /**
   * Request an icon hosted elsewhere.
   *
   * @param string $url
   *   The absolute icon url.
   *
   * @return string|null
   *   The response body, or NULL if the icon could not be retrieved.
   */
  protected function requestIcon(string $url): ?string {
    if (!$this->httpClient) {
      return NULL;
    }

    try {
      $response = $this->httpClient->request('GET', $url, [
        'connect_timeout' => self::ICON_CONNECT_TIMEOUT,
        'timeout' => self::ICON_TIMEOUT,
      ]);
    }
    catch (\Throwable $e) {
      return NULL;
    }

    return (string) $response->getBody();
  }

  /**
   * Get the intrinsic size of an svg document.
   *
   * @param string $contents
   *   The svg markup.
   *
   * @return array|null
   *   The [width, height] of the document, or NULL if it declares neither
   *   usable width and height attributes nor a viewBox.
   */
  protected function svgSize(string $contents): ?array {
    $errors = libxml_use_internal_errors(TRUE);
    $xml = simplexml_load_string($contents);
    libxml_clear_errors();
    libxml_use_internal_errors($errors);

    if (!$xml) {
      return NULL;
    }

    $attr = $xml->attributes();
    $size = [];
    foreach (['width', 'height'] as $dimension) {
      // Relative lengths such as "100%" say nothing about the intrinsic size.
      $length = (string) ($attr->{$dimension} ?? '');
      $size[] = preg_match('/^\s*([0-9]*\.?[0-9]+)\s*(px)?\s*$/i', $length, $matches) ? (float) $matches[1] : 0;
    }

    if (empty($size[0]) || empty($size[1])) {
      $viewBox = preg_split('/[\s,]+/', trim((string) ($attr->viewBox ?? '')));
      $size = count($viewBox) === 4 ? [(float) $viewBox[2], (float) $viewBox[3]] : [0, 0];
    }

    return (empty($size[0]) || empty($size[1])) ? NULL : [intval($size[0]), intval($size[1])];
  }

  /**
   * Check if a file exists at the given URL.
   *
   * Nothing in the module calls this any more: icon sizes are resolved by
   * setSizeIfEmptyOrInvalid() without it. It is kept for callers outside the
   * module, and bounded the same way, so that an unresponsive host cannot hold
   * on to the request.
   *
   * @todo Decide whether to deprecate this, and in which release.
   *
   * @param string $fileUrl
   *   The URL of the file to check.
   *
   * @return bool
   *   TRUE if the file exists and is accessible, FALSE otherwise.
   */
  public function fileExists(string $fileUrl): bool {
    if ($this->iconFilePath($fileUrl, $this->generateAbsoluteString($fileUrl)) !== NULL) {
      return TRUE;
    }
    if ($this->isOwnHost($fileUrl) || !$this->httpClient) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->request('HEAD', $fileUrl, [
        'connect_timeout' => self::ICON_CONNECT_TIMEOUT,
        'timeout' => self::ICON_TIMEOUT,
      ]);
    }
    catch (\Throwable $e) {
      return FALSE;
    }

    return $response->getStatusCode() < 400;
  }

  /**
   * Check if an array has all values empty.
   *
   * @param array $array
   *   The array to check.
   *
   * @return bool
   *   The bool result.
   */
  public function multipleEmpty(array $array) {
    foreach ($array as $value) {
      if (empty($value)) {
        continue;
      }
      else {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Set Geocoder Controls Settings.
   *
   * @param array $geocoder_settings
   *   The geocoder settings.
   * @param array $attached_libraries
   *   The attached libraries.
   */
  public function setGeocoderControlSettings(array &$geocoder_settings, array &$attached_libraries): void {
    if ($this->moduleHandler->moduleExists('geocoder')
      && class_exists('\Drupal\geocoder\Controller\GeocoderApiEnpoints')
      && $geocoder_settings['control']
      && $this->currentUser->hasPermission('access geocoder api endpoints')) {
      $attached_libraries[] = 'leaflet/leaflet.geocoder';

      // Set the geocoder settings ['providers'] as the enabled ones.
      $enabled_providers = [];
      foreach ($geocoder_settings['settings']['providers'] as $plugin_id => $plugin) {
        if (!empty($plugin['checked'])) {
          $enabled_providers[] = $plugin_id;
        }
      }
      $geocoder_settings['settings']['providers'] = $enabled_providers;
      $geocoder_settings['settings']['options'] = [
        'options' => Json::decode($geocoder_settings['settings']['options']) ?? '',
      ];
    }
  }

  /**
   * Creates an absolute web-accessible URL string.
   *
   * This is a wrapper to the Drupal Core (9.3+) FileUrlGeneratorInterface
   * generateAbsoluteString method.
   *
   * @param string $uri
   *   The URI to a file for which we need an external URL, or the path to a
   *   shipped file.
   *
   * @return string
   *   An absolute string containing a URL that may be used to access the
   *   file.
   *
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   If a stream wrapper could not be found to generate an external URL.
   */
  public function generateAbsoluteString(string $uri): string {
    return $this->fileUrlGenerator->generateAbsoluteString($uri);
  }

}
