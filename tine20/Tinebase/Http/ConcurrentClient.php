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
        public float                                   $maxTimePerConnection = 10.0,
        public bool                                    $returnCurlGetInfo = false,
        public float                                   $maxTotalTime = 0.0,
        public bool                                    $resumable = false,
        public ?Tinebase_Http_CC_RequestLimitInterface $requestLimit = null
    ) {
        if (null === $this->requestLimit) {
            $this->requestLimit = new Tinebase_Http_CC_RequestClusterLimit(5, 50);
        }
    }

    /**
     * @param array<string|int, Tinebase_Http_CC_CurlRequest> $curlRequests
     * @return Generator<string|int, Tinebase_Http_CC_CurlResponse>
     */
    public function generateMultiCurlResponses(array $curlRequests): Generator
    {
        $startTime = microtime(true);
        $currentMaxExecutionTime = $startTime + self::LARGE_NUM;
        $maxExecutionTime = $startTime + ($this->maxTotalTime ?: self::LARGE_NUM);
        $this->lastCurlErrno = null;
        $executionTimeLeft = 1.0;
        $this->resumableRequests = $curlRequests;
        $hasEndlessRequest = false;
        $hasRequestTimeout = false;

        $raii = null;
        if (!$this->resumable) {
            $raii = new Tinebase_RAII(function() {
                if ($this->resumableCurlMulti) {
                    curl_multi_close($this->resumableCurlMulti);
                    $this->resumableCurlMulti = null;
                }
                $this->curlHandles = [];
                $this->processingRequests = [];
                $this->resumableRequests = [];
                $this->requestLimit->reset();
            });
        }

        if ($this->resuming) {
            $this->calcCurrentMaxExecutionTime($currentMaxExecutionTime, $hasEndlessRequest, $hasRequestTimeout, $startTime, $maxExecutionTime);
        } else {
            $this->resumableCurlMulti = curl_multi_init();
            $this->curlHandles = [];
            $this->processingRequests = [];
        }

        while ($executionTimeLeft > 0.0 && (!empty($this->resumableRequests) || !empty($this->processingRequests))) {
            while (!empty($this->resumableRequests) && $this->requestLimit->hasFreeCapacity()) {
                $request = null;
                $key = null;
                foreach ($this->resumableRequests as $key => $curlRequest) {
                    if ($this->requestLimit->checkKey($curlRequest->clusterKey)) {
                        $request = $curlRequest;
                        unset($this->resumableRequests[$key]);
                        break;
                    }
                }
                if (null === $request) {
                    break;
                }

                $request->timeStarted = microtime(true);
                $this->setCurrentMaxExecutionTime($currentMaxExecutionTime, $hasEndlessRequest, $hasRequestTimeout, $startTime, $maxExecutionTime, $request);

                if (false === ($handle = $request->createCurlHandle())) {
                    yield $key => new Tinebase_Http_CC_CurlResponse($request, curlErrorCode: false);
                    continue;
                }
                if (false === curl_setopt($handle, CURLOPT_RETURNTRANSFER, true)) {
                    yield $key => new Tinebase_Http_CC_CurlResponse($request, curlErrorCode: curl_errno($handle));
                    continue;
                }
                if (0 !== curl_multi_add_handle($this->resumableCurlMulti, $handle)) {
                    $this->lastCurlErrno = curl_multi_errno($this->resumableCurlMulti);
                    return;
                }

                if (CURLM_OK !== curl_multi_exec($this->resumableCurlMulti, $numExec)) {
                    $this->lastCurlErrno = curl_multi_errno($this->resumableCurlMulti);
                    return;
                }
                $this->curlHandles[$key] = $handle;
                $this->processingRequests[$key] = $request;
            }

            if (CURLM_OK !== curl_multi_exec($this->resumableCurlMulti, $numExec)) {
                $this->lastCurlErrno = curl_multi_errno($this->resumableCurlMulti);
                return;
            }

            $workDone = false;
            while (false !== ($msg = curl_multi_info_read($this->resumableCurlMulti))) {
                /** @var CurlHandle $handle */
                $handle = $msg['handle'];
                if (false === ($key = array_search($handle, $this->curlHandles, true))) {
                    if (0 !== curl_multi_remove_handle($this->resumableCurlMulti, $handle)) {
                        $this->lastCurlErrno = curl_multi_errno($this->resumableCurlMulti);
                        return;
                    }
                    continue;
                }
                $request = $this->processingRequests[$key];
                if (CURLE_OK === ($msg['result'] ?? null)) {
                    yield $key => new Tinebase_Http_CC_CurlResponse(
                        request: $request,
                        content: curl_multi_getcontent($handle),
                        curlInfo: $request->returnCurlGetInfo || ($this->returnCurlGetInfo && false !== $request->returnCurlGetInfo) ? curl_getinfo($handle): null
                    );
                } else {
                    yield $key => new Tinebase_Http_CC_CurlResponse($request, curlErrorCode: $msg['result']);
                }
                if (0 !== curl_multi_remove_handle($this->resumableCurlMulti, $handle)) {
                    $this->lastCurlErrno = curl_multi_errno($this->resumableCurlMulti);
                    return;
                }
                unset($this->processingRequests[$key]);
                unset($this->curlHandles[$key]);
                $this->requestLimit->freeKey($request->clusterKey);
                $workDone = true;
            }

            $hasEndlessRequest = false;
            $hasRequestTimeout = false;
            if (!empty($this->processingRequests)) {
                $this->calcCurrentMaxExecutionTime($currentMaxExecutionTime, $hasEndlessRequest, $hasRequestTimeout, $startTime, $maxExecutionTime);
                $executionTimeLeft = $currentMaxExecutionTime - microtime(true);
                if (!$workDone && $executionTimeLeft > 0.0) {
                    // we ignore errors here as that would come from underlying system select call
                    // the next curl_multi_* call will report errors if there would be any
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__. ' selecting for: ' . $executionTimeLeft);
                    curl_multi_select($this->resumableCurlMulti, $executionTimeLeft);
                }
            } else {
                // since there are no requests in process currently, no request timeout, we take general timeout here
                $executionTimeLeft = $maxExecutionTime - microtime(true);
            }
        }

        unset($raii);
    }

    /**
     * @return Generator<string|int, Tinebase_Http_CC_CurlResponse>
     */
    public function resume(): Generator
    {
        if (null !== $this->lastCurlErrno) {
            throw new Tinebase_Exception_UnexpectedValue('curl errno present, can\'t resume');
        }
        if (null === $this->resumableCurlMulti) {
            throw new Tinebase_Exception_UnexpectedValue('nothing to resume');
        }
        $this->resuming = true;
        $raii = new Tinebase_RAII(fn() => $this->resuming = false);
        yield from $this->generateMultiCurlResponses($this->resumableRequests);
        unset($raii);
    }

    protected function calcCurrentMaxExecutionTime(float &$currentMaxExecutionTime, bool &$hasEndlessRequest, bool &$hasRequestTimeout, float $startTime, float $maxExecutionTime): void
    {
        $hasEndlessRequest = false;
        $hasRequestTimeout = false;
        $currentMaxExecutionTime = $startTime + self::LARGE_NUM;
        foreach ($this->processingRequests as $request) {
            $this->setCurrentMaxExecutionTime($currentMaxExecutionTime, $hasEndlessRequest, $hasRequestTimeout, $startTime, $maxExecutionTime, $request);
        }
        if (empty($this->processingRequests)) {
            if (!$this->maxTimePerConnection) {
                $currentMaxExecutionTime = $maxExecutionTime;
            } else {
                $currentMaxExecutionTime = min($startTime + $this->maxTimePerConnection, $maxExecutionTime);
            }
        }
    }

    protected function setCurrentMaxExecutionTime(float &$currentMaxExecutionTime, bool &$hasEndlessRequest, bool &$hasRequestTimeout, float $startTime, float $maxExecutionTime, Tinebase_Http_CC_CurlRequest $request): void
    {
        if ($hasEndlessRequest) return;
        if (null !== $request->timeout) {
            $hasRequestTimeout = true;
            $currentMaxExecutionTime = ($hasEndlessRequest = 0.0 === $request->timeout) ? $startTime + self::LARGE_NUM : min($currentMaxExecutionTime, $request->timeStarted + $request->timeout);
        } elseif (!$hasRequestTimeout) {
            $currentMaxExecutionTime = min($currentMaxExecutionTime, $this->maxTimePerConnection ? min($maxExecutionTime, $request->timeStarted + $this->maxTimePerConnection) : $maxExecutionTime);
        }
    }

    /** @var array<string|int, CurlHandle> $curlHandles */
    protected array $curlHandles = [];
    /** @var array<string|int, Tinebase_Http_CC_CurlRequest> $processingRequests */
    protected array $processingRequests = [];
    /** @var array<int|string, Tinebase_Http_CC_CurlRequest>  */
    protected array $resumableRequests = [];
    protected bool $resuming = false;
    protected ?CurlMultiHandle $resumableCurlMulti = null;
    protected const LARGE_NUM = 100 * 1000 * 1000;

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