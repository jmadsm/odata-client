<?php

namespace SaintSystems\OData;

use Closure;
use Illuminate\Support\LazyCollection;
use SaintSystems\OData\Exception\ODataException;
use SaintSystems\OData\Query\Builder;
use SaintSystems\OData\Query\Grammar;
use SaintSystems\OData\Query\IGrammar;
use SaintSystems\OData\Query\IProcessor;
use SaintSystems\OData\Query\Processor;

class ODataV4Client implements IODataClient
{
    /**
     * The base service URL. For example, "https://services.odata.org/V4/TripPinService/"
     * @var string
     */
    private $baseUrl;

    /**
     * The IAuthenticationProvider for authenticating request messages.
     * @var IAuthenticationProvider
     */
    private $authenticationProvider;

    /**
     * The IHttpProvider for sending HTTP requests.
     * @var IHttpProvider
     */
    private $httpProvider;

    /**
     * The query grammar implementation.
     *
     * @var IGrammar
     */
    protected $queryGrammar;

    /**
     * The query post processor implementation.
     *
     * @var IProcessor
     */
    protected $postProcessor;

    /**
     * The return type for the entities
     *
     * @var string
     */
    private $entityReturnType;

    /**
     * The page size
     *
     * @var int
     */
    private $pageSize;

    /**
     * The entityKey to be found
     *
     * @var mixed
     */
    private $entityKey;

    /**
     * Constructs a new ODataV4Client.
     * @param string                  $baseUrl                The base service URL.
     * @param IAuthenticationProvider $authenticationProvider The IAuthenticationProvider for authenticating request messages.
     * @param IHttpProvider|null      $httpProvider           The IHttpProvider for sending requests.
     */
    public function __construct(
        $baseUrl,
        Callable $authenticationProvider = null,
        IHttpProvider $httpProvider = null
    ) {
        $this->setBaseUrl($baseUrl);
        $this->authenticationProvider = $authenticationProvider;
        $this->httpProvider = $httpProvider ?: new GuzzleHttpProvider();

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the OData abstractions
        // so we initialize these to their default values while starting.
        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();
    }

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return IGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new Grammar;
    }

    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
     *
     * @return IProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor();
    }

    /**
     * Gets the IAuthenticationProvider for authenticating requests.
     * @var IAuthenticationProvider
     *
     * @return Closure|IAuthenticationProvider
     */
    public function getAuthenticationProvider()
    {
        return $this->authenticationProvider;
    }

    /**
     * Gets the base URL for requests of the client.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Sets the base URL for requests of the client.
     * @param mixed $value
     *
     * @throws ODataException
     */
    public function setBaseUrl($value)
    {
        if (empty($value)) {
            throw new ODataException(Constants::BASE_URL_MISSING);
        }

        $this->baseUrl = rtrim($value, '/') . '/';
    }

    /**
     * Gets the IHttpProvider for sending HTTP requests.
     *
     * @return IHttpProvider
     */
    public function getHttpProvider()
    {
        return $this->httpProvider;
    }

    /**
     * Begin a fluent query against an odata service
     *
     * @param string $entitySet
     *
     * @return Builder
     */
    public function from($entitySet)
    {
        return $this->query()->from($entitySet);
    }

    /**
     * Begin a fluent query against an odata service
     *
     * @param array $properties
     *
     * @return Builder
     */
    public function select($properties = [])
    {
        $properties = is_array($properties) ? $properties : func_get_args();

        return $this->query()->select($properties);
    }

    /**
     * Get a new query builder instance.
     *
     * @return Builder
     */
    public function query()
    {
        return new Builder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Run a GET HTTP request against the service.
     *
     * @param string $requestUri
     * @param array  $bindings
     *
     * @return IODataRequest
     */
    public function get($requestUri, $bindings = [])
    {
        return $this->request(HttpMethod::GET, $requestUri);
    }

    /**
     * Run a GET HTTP request against the service.
     *
     * @param string $requestUri
     * @param array  $bindings
     * @param array  $skipToken
     *
     * @return IODataRequest
     */
    public function getNextPage($requestUri, $bindings = [])
    {
        return $this->request(HttpMethod::GET, $requestUri, $bindings);
    }

    /**
     * Run a GET HTTP request against the service and return a generator.
     *
     * @param string $requestUri
     * @param array  $bindings
     *
     * @return \Illuminate\Support\LazyCollection
     */
    public function cursor($requestUri, $bindings = [])
    {
        return LazyCollection::make(function() use($requestUri, $bindings) {

            $nextPage = $requestUri;

            while (!is_null($nextPage)) {
                list($data, $nextPage) = $this->getNextPage($nextPage, $bindings);

                if (!is_null($nextPage)) {
                    $nextPage = str_replace($this->baseUrl, '', $nextPage);
                }

                yield from $data;
            }
        });
    }



    /**
     * Run a POST request against the service.
     *
     * @param string $requestUri
     * @param mixed  $postData
     *
     * @return IODataRequest
     */
    public function post($requestUri, $postData)
    {
        return $this->request(HttpMethod::POST, $requestUri, $postData);
    }

    /**
     * Run a PATCH request against the service.
     *
     * @param string $requestUri
     * @param mixed  $body
     *
     * @return IODataRequest
     */
    public function patch($requestUri, $body, $ifMatch = null)
    {
        $this->setIfMatchHeader($ifMatch);

        return $this->request(HttpMethod::PATCH, $requestUri, $body);
    }

    /**
     * Run a DELETE request against the service.
     *
     * @param string $requestUri
     *
     * @return IODataRequest
     */
    public function delete($requestUri)
    {
        return $this->request(HttpMethod::DELETE, $requestUri);
    }

    /**
     * Return an ODataRequest
     *
     * @param string $method
     * @param string $requestUri
     * @param mixed  $body
     *
     * @return IODataRequest
     *
     * @throws ODataException
     */
    public function request($method, $requestUri, $body = null)
    {
        $request = new ODataRequest($method, $this->baseUrl.$requestUri, $this, $this->entityReturnType);

        if ($body) {
            $request->attachBody($body);
        }

        if ($method !== HttpMethod::PATCH) {
            $this->setIfMatchHeader(null);
        }

        return $request->execute();
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return IGrammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param  IGrammar  $grammar
     *
     * @return void
     */
    public function setQueryGrammar(IGrammar $grammar)
    {
        $this->queryGrammar = $grammar;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return IProcessor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor used by the connection.
     *
     * @param IProcessor $processor
     *
     * @return void
     */
    public function setPostProcessor(IProcessor $processor)
    {
        $this->postProcessor = $processor;
    }

    /**
     * Set the entity return type
     *
     * @param string $entityReturnType
     */
    public function setEntityReturnType($entityReturnType)
    {
        $this->entityReturnType = $entityReturnType;
    }

    /**
     * Set the odata.maxpagesize value of the request.
     *
     * @param int $pageSize
     *
     * @return IODataClient
     */
    public function setPageSize($pageSize) {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * Gets the page size
     *
     * @return int
     */
    public function getPageSize() {
        return $this->pageSize;
    }

    /**
     * Set the entityKey to be found.
     *
     * @param mixed $entityKey
     *
     * @return IODataClient
     */
    public function setEntityKey($entityKey) {
        $this->entityKey = $entityKey;
        return $this;
    }

    /**
     * Gets the entity key
     *
     * @return mixed
     */
    public function getEntityKey() {
        return $this->entityKey;
    }

    /**
     * Sets If-Match header on HttpProvider.
     *
     * @param string $value
     * @return void
     */
    protected function setIfMatchHeader(string $value = null)
    {
        $this->getHttpProvider()->setAdditionalHeader('If-Match', $value);
    }

    /**
     * Construct ODataV4Client for DSM
     *
     * @param string $tenantCompanyId       Tenant->api_company_id
     * @param string $tenantName            Tenant->api_tenant
     * @param string $tenantBaseUrl         Tenant->api_base_url
     * @param string $tenantUsername        Tenant->api_user
     * @param string $tenantPassword        Tenant->api_password
     * @param string $tenantApiVersion      Tenant->api_rest_version
     * @param boolean $verifySsl            Wether or not to verify ssl certificates
     * @return ODataV4Client
     */
    public static function dsmFactory(string $tenantCompanyId, string $tenantName, string $tenantBaseUrl, string $tenantUsername, string $tenantPassword, string $tenantApiVersion = 'beta', bool $verifySsl = true)
    {
        $provider = new GuzzleHttpProvider();
        if (!$verifySsl) {
            $provider->setExtraOptions(['verify' => false]);
        }

        return new static(
            rtrim($tenantBaseUrl, '/') . "/ODataV4",
            function ($request) use ($tenantUsername, $tenantPassword, $tenantName, $tenantCompanyId) {
                $request->headers = self::formatHeaders($request->headers);
                $request->headers['Authorization'] = 'Basic ' . base64_encode("{$tenantUsername}:{$tenantPassword}");

                $tenantQueryString = ((strpos($request->requestUri, '?') === false) ? '?': '&') . "tenant={$tenantName}&company={$tenantCompanyId}";
                $request->requestUri .= $tenantQueryString;
            },
            $provider
        );
    }

    public static function dsmFactoryFromTenantArray(array $tenant, bool $verifySsl = true)
    {
        return static::dsmFactory($tenant['api_company_id'], $tenant['api_tenant'], $tenant['api_base_url'], $tenant['api_user'], $tenant['api_password'], $tenant['api_rest_version'], $verifySsl);
    }

    /**
     * Formats headers correctly
     *
     * @param array $headers
     * @return array
     */
    protected static function formatHeaders(array $headers): array
    {
        $newHeaders = [];

        foreach ($headers as $key => $value) {
            $newHeaders[$key] = $value;
        }

        return $newHeaders;
    }
}
