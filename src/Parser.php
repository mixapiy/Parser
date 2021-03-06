<?php

  declare(strict_types=1);

  namespace Xparse\Parser;

  use GuzzleHttp\Client;
  use GuzzleHttp\ClientInterface;
  use GuzzleHttp\Psr7\Request;
  use GuzzleHttp\RequestOptions;
  use GuzzleHttp\TransferStats;
  use Psr\Http\Message\RequestInterface;
  use Psr\Http\Message\ResponseInterface;
  use Xparse\ElementFinder\ElementFinder;

  /**
   *
   */
  class Parser implements ParserInterface {

    /**
     *
     * @var ElementFinder|null
     */
    protected $lastPage;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var ElementFinderFactoryInterface
     */
    protected $elementFinderFactory;

    /**
     * @var null|ResponseInterface
     */
    protected $lastResponse;


    /**
     * @param ClientInterface|null $client
     * @param ElementFinderFactoryInterface|null $elementFinderFactory
     */
    public function __construct(ClientInterface $client = null, ElementFinderFactoryInterface $elementFinderFactory = null) {
      if ($client === null) {
        $client = new Client([
          RequestOptions::ALLOW_REDIRECTS => true,
          RequestOptions::COOKIES => new \GuzzleHttp\Cookie\CookieJar(),
        ]);
      }

      $this->client = $client;

      if ($elementFinderFactory === null) {
        $elementFinderFactory = new ElementFinderFactory();
      }

      $this->elementFinderFactory = $elementFinderFactory;
    }


    /**
     * @param string $url
     * @param array $options
     * @return ElementFinder
     * @throws \InvalidArgumentException
     */
    public function get(string $url, array $options = []): ElementFinder {
      if ($url === '') {
        throw new \InvalidArgumentException('Url can\'t be empty');
      }
      return $this->send(
        new Request('GET', $url),
        $options
      );
    }


    /**
     * @param string $url
     * @param array $options
     * @return ElementFinder
     * @throws \InvalidArgumentException
     */
    public function post(string $url, array $options = []): ElementFinder {
      if ($url === '') {
        throw new \InvalidArgumentException('Url can\'t be empty');
      }
      return $this->send(
        new Request('POST', $url),
        $options
      );
    }


    /**
     * @return ElementFinder|null
     */
    public function getLastPage() {
      return $this->lastPage;
    }


    /**
     * @return ResponseInterface|null
     */
    public function getLastResponse() {
      return $this->lastResponse;
    }


    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface {
      return $this->client;
    }


    /**
     * @param RequestInterface $request
     * @param array $options
     * @return ElementFinder
     */
    public function send(RequestInterface $request, array $options = []): ElementFinder {

      $prevCallback = !empty($options['on_stats']) ? $options['on_stats'] : null;

      $effectiveUrl = '';
      $options['on_stats'] = function (TransferStats $stats) use (&$effectiveUrl, $prevCallback) {
        $effectiveUrl = $stats->getEffectiveUri()->__toString();
        if ($prevCallback !== null) {
          /** @var callable $prevCallback */
          $prevCallback($stats);
        }
      };

      $response = $this->client->send($request, $options);

      $guzzleEffectiveUrl = $response->getHeaderLine('X-GUZZLE-EFFECTIVE-URL');
      if ($guzzleEffectiveUrl !== '') {
        $effectiveUrl = $guzzleEffectiveUrl;
      }

      $elementFinder = $this->elementFinderFactory->create($response, $effectiveUrl);
      $this->lastPage = $elementFinder;
      $this->lastResponse = $response;

      return $elementFinder;
    }


    /**
     * @return ElementFinderFactoryInterface
     */
    public function getElementFinderFactory(): ElementFinderFactoryInterface {
      return $this->elementFinderFactory;
    }

  }
