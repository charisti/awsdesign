<?php

namespace Drupal\Tests\leaflet\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\leaflet\LeafletService;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests that an icon whose size cannot be resolved is probed at most once.
 *
 * @coversDefaultClass \Drupal\leaflet\LeafletService
 * @group leaflet
 */
class LeafletIconSizeTest extends UnitTestCase {

  /**
   * The persistent cache store standing in for the real cache.leaflet bin.
   *
   * Shared by every service instance built in the test.
   *
   * @var array
   */
  protected $cacheStore = [];

  /**
   * The mocked http client.
   *
   * Expected to be hit exactly once for the whole test, regardless of how
   * many features or service instances use it.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cacheStore = [];
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException(new \RuntimeException('Simulated unreachable host.'));
  }

  /**
   * Builds a LeafletService instance sharing the test's mocked dependencies.
   *
   * A fresh instance stands in for a new request: its in-memory icon size
   * cache starts empty, so only the shared persistent cache backend can
   * prevent it from probing the icon again.
   *
   * @return \Drupal\leaflet\LeafletService
   *   The service.
   */
  protected function newService(): LeafletService {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')
      ->willReturnCallback(function (string $cid) {
        return $this->cacheStore[$cid] ?? FALSE;
      });
    $cache->method('set')
      ->willReturnCallback(function (string $cid, $data): void {
        $this->cacheStore[$cid] = (object) ['data' => $data];
      });

    $stream_wrapper_manager = $this->createMock(StreamWrapperManagerInterface::class);
    $stream_wrapper_manager->method('isValidScheme')->willReturn(FALSE);

    $file_url_generator = $this->createMock(FileUrlGeneratorInterface::class);
    $file_url_generator->method('generateAbsoluteString')->willReturnArgument(0);

    $request_stack = new RequestStack();
    $request_stack->push(Request::create('http://localhost/'));

    return new LeafletService(
      $this->createMock(AccountInterface::class),
      $this->createMock(GeoPHPInterface::class),
      $this->createMock(ModuleHandlerInterface::class),
      $this->createMock(LinkGeneratorInterface::class),
      $stream_wrapper_manager,
      $request_stack,
      $cache,
      $file_url_generator,
      $this->httpClient,
    );
  }

  /**
   * @covers ::setFeatureIconSizesIfEmptyOrInvalid
   */
  public function testUnresolvableIconIsProbedAtMostOnce(): void {
    // 0 for both x and y is how an empty Icon Size form field is represented
    // once stored: setSizeIfEmptyOrInvalid() only probes the icon when
    // intval() of x or y is 0, which is also what an empty string casts to.
    $build_feature = static fn () => [
      'icon' => [
        'iconUrl' => 'https://example.com/unresolvable-icon.png',
        'iconSize' => ['x' => 0, 'y' => 0],
      ],
    ];

    // Several markers on the same map share this icon: only the very first
    // lookup should reach the http client, the rest are served from the
    // service's own in-memory cache.
    $service = $this->newService();
    for ($i = 0; $i < 3; $i++) {
      $feature = $build_feature();
      $service->setFeatureIconSizesIfEmptyOrInvalid($feature);
      $this->assertSame(0, $feature['icon']['iconSize']['x']);
    }

    // A second instance stands in for a later request, with an empty
    // in-memory cache of its own. The persistent cache entry written above
    // must still stop it from probing the icon a second time.
    $feature = $build_feature();
    $this->newService()->setFeatureIconSizesIfEmptyOrInvalid($feature);
    $this->assertSame(0, $feature['icon']['iconSize']['x']);
  }

}
