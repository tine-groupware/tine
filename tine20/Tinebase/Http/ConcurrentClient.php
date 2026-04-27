<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Http
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_Http_ConcurrentClient
{
    public ?int $lastCurlErrno = null;

    public function __construct(
        public int $maxConnections = 10,
        public int $maxConnectionPerCluster = 0,
        public float $connectionTimeoutSeconds = 10.0,
        public bool $returnCurlGetInfo = false,
        public ?Tinebase_Http_CC_ClusterLimitInterface $clusterLimit = null
    ) {
    }

    /**
     * @param array<string|int, Tinebase_Http_CC_CurlRequest> $curlRequests
     * @return Generator<string|int, Tinebase_Http_CC_CurlResponse>
     */
    public function generateMultiCurlResponses(array $curlRequests): Generator
    {
        $this->lastCurlErrno = null;

        $multiCurl = curl_multi_init();
        $numExec = 0;
        /** @var array<string|int, CurlHandle> $curlHandles */
        $curlHandles = [];
        /** @var array<string|int, Tinebase_Http_CC_CurlRequest> $processedRequests */
        $processedRequests = [];
        reset($curlRequests);

        while (!empty($curlRequests) || $numExec > 0) {
            while ($numExec < $this->maxConnections && !empty($curlRequests)) {
                $key = null;
                if (null !== $this->clusterLimit) {
                    $request = null;
                    foreach ($curlRequests as $key => $curlRequest) {
                        if ($this->clusterLimit->checkKey($curlRequest->clusterKey)) {
                            $request = $curlRequest;
                            unset($curlRequests[$key]);
                            break;
                        }
                    }
                    if (null === $request) {
                        break;
                    }
                } else {
                    $key = key($curlRequests);
                    $request = current($curlRequests);
                    $curlRequests = array_slice($curlRequests, 1, preserve_keys: true);
                }
                if (false === ($handle = $request->createCurlHandle())) {
                    yield $key => new Tinebase_Http_CC_CurlResponse($request, curlErrorCode: false);
                    continue;
                }
                if (false === curl_setopt($handle, CURLOPT_RETURNTRANSFER, true)) {
                    yield $key => new Tinebase_Http_CC_CurlResponse($request, curlErrorCode: curl_errno($handle));
                    continue;
                }
                if (0 !== curl_multi_add_handle($multiCurl, $handle)) {
                    $this->lastCurlErrno = curl_multi_errno($multiCurl);
                    return;
                }
                if (CURLM_OK !== curl_multi_exec($multiCurl, $numExec)) {
                    $this->lastCurlErrno = curl_multi_errno($multiCurl);
                    return;
                }
                $curlHandles[$key] = $handle;
                $processedRequests[$key] = $request;
            }

            if (CURLM_OK !== curl_multi_exec($multiCurl, $numExec)) {
                $this->lastCurlErrno = curl_multi_errno($multiCurl);
                return;
            }

            $workDone = false;
            while (false !== ($msg = curl_multi_info_read($multiCurl))) {
                /** @var CurlHandle $handle */
                $handle = $msg['handle'];
                if (false === ($key = array_search($handle, $curlHandles, true))) {
                    if (0 !== curl_multi_remove_handle($multiCurl, $handle)) {
                        $this->lastCurlErrno = curl_multi_errno($multiCurl);
                        return;
                    }
                    continue;
                }
                $request = $processedRequests[$key];
                if (CURLE_OK === ($msg['result'] ?? null)) {
                    yield $key => new Tinebase_Http_CC_CurlResponse(
                        request: $request,
                        content: curl_multi_getcontent($handle),
                        curlInfo: $request->returnCurlGetInfo || ($this->returnCurlGetInfo && false !== $request->returnCurlGetInfo) ? curl_getinfo($handle): null
                    );
                } else {
                    yield $key => new Tinebase_Http_CC_CurlResponse($request, curlErrorCode: $msg['result']);
                }
                if (0 !== curl_multi_remove_handle($multiCurl, $handle)) {
                    $this->lastCurlErrno = curl_multi_errno($multiCurl);
                    return;
                }
                unset($processedRequests[$key]);
                unset($curlHandles[$key]);
                $workDone = true;
                --$numExec;
            }

            if (!$workDone) {
                usleep(1000);
            }
        }
    }

    /* a Amp implementation, composer require amphp/http-client
    see Tinebase/HelperTests for api usage

    protected Amp\Http\Client\HttpClientBuilder $httpClientBuilder;

     public function __construct(
        protected int $maxConnections = 10,
        protected float $connectionTimeoutSeconds = 10.0,
    ) {
        $staticKey = (string)spl_object_id($this);

        $this->httpClientBuilder = (new HttpClientBuilder)
            ->usingPool(
                Amp\Http\Client\Connection\StreamLimitingPool::byStaticKey(
                    Amp\Http\Client\Connection\ConnectionLimitingPool::byAuthority(\PHP_INT_MAX),
                    new Amp\Sync\LocalKeyedSemaphore($this->maxConnections),
                    $staticKey
                )
            );
    }

    public function getHttpClientBuild(): Amp\Http\Client\HttpClientBuilder
    {
        return $this->httpClientBuilder;
    }

    / * *
     * @ param array $requests<Amp\Http\Client\Request>
     * @ return Generator<Amp\Http\HttpResponse>

    public function generateResponses(array $requests)
    {
        $client = $this->httpClientBuilder->build();
        / * * @var array<int, Amp\Future<Amp\Http\HttpResponse>> $futures * /
        $futures = [];
        foreach ($requests as $request) {
            $futures[] = Amp\async(fn() => $client->request(
                $request,
                0.0 === $this->connectionTimeoutSeconds
                    ? new Amp\NullCancellation : new Amp\TimeoutCancellation($this->connectionTimeoutSeconds)
            ));
        }

        do {
            $result = Amp\Future\awaitAnyN(1, $futures);
            unset($futures[array_key_first($result)]);
            yield current($result);
        } while (!empty($futures));
    }
    */
}