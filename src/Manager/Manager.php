<?php

namespace Savks\EFilters\Manager;

use Closure;
use Http\Promise\Promise;
use Savks\EFilters\Builder\DSL\Query;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use stdClass;

use Elastic\Elasticsearch\{
    Exception\ClientResponseException,
    Exception\MissingParameterException,
    Exception\ServerResponseException,
    Response\Elasticsearch as ElasticsearchResponse,
    Client
};
use Savks\EFilters\Support\{
    AbstractResource,
    RequestConfig,
    RequestConfigContract,
    RequestTypes,
    ResourcesRepository
};

class Manager
{
    /**
     * @var Application
     */
    protected Application $app;

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var ResourcesRepository
     */
    protected ResourcesRepository $resources;

    /**
     * @var RequestConfigContract
     */
    protected RequestConfigContract $requestConfig;

    /**
     * @param Application $app
     * @param Client $client
     */
    public function __construct(Application $app, Client $client)
    {
        $this->app = $app;
        $this->client = $client;
        $this->requestConfig = new RequestConfig();
    }

    /**
     * @return string
     */
    public function defaultIndexName(): string
    {
        return $this->app['config']->get('efilter.default_index');
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param AbstractResource $resource
     * @param array $document
     * @return ElasticsearchResponse|Promise
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function save(AbstractResource $resource, array $document): ElasticsearchResponse|Promise
    {
        $response = $this->getClient()->index(
            $this->requestConfig->applyToRequest(RequestTypes::SAVE, [
                'id' => $document[$resource->key()],
                'index' => $resource->prepareIndexName(),
                'body' => $document,
            ])
        );

        $this->app['efilter.errors-handler']->processResponse(RequestTypes::SAVE, $response);

        return $response;
    }

    /**
     * @param AbstractResource $resource
     * @param array|Collection $documents
     * @return ElasticsearchResponse
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function bulkSave(AbstractResource $resource, array|Collection $documents): ElasticsearchResponse
    {
        $params = [];

        foreach ($documents as $document) {
            $params['body'][] = [
                'index' => [
                    '_id' => $document[$resource->key()],
                    '_index' => $resource->prepareIndexName(),
                ],
            ];

            $params['body'][] = $document;
        }

        $response = $this->getClient()->bulk(
            $this->requestConfig->applyToRequest(RequestTypes::BULK_SAVE, $params)
        );

        $this->app['efilter.errors-handler']->processResponse(RequestTypes::BULK_SAVE, $response);

        return $response;
    }

    /**
     * @param AbstractResource $resource
     * @param int|string $id
     * @return ElasticsearchResponse|Promise
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function delete(AbstractResource $resource, int|string $id): ElasticsearchResponse|Promise
    {
        return $this->getClient()->delete(
            $this->requestConfig->applyToRequest(RequestTypes::DELETE, [
                'id' => $id,
                'index' => $resource->prepareIndexName(),
            ])
        );
    }

    /**
     * @param AbstractResource $resource
     * @param array|Collection $ids
     * @return ElasticsearchResponse|Promise
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function bulkDelete(AbstractResource $resource, array|Collection $ids): ElasticsearchResponse|Promise
    {
        $params = [];

        foreach ($ids as $id) {
            $params['body'][] = [
                'delete' => [
                    '_id' => $id,
                    '_index' => $resource->prepareIndexName(),
                ],
            ];
        }

        return $this->getClient()->bulk(
            $this->requestConfig->applyToRequest(RequestTypes::BULK_DELETE, $params)
        );
    }

    /**
     * @param AbstractResource $resource
     * @param Query $query
     * @return ElasticsearchResponse|Promise
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function deleteByQuery(AbstractResource $resource, Query $query): ElasticsearchResponse|Promise
    {
        return $this->getClient()->deleteByQuery(
            $this->requestConfig->applyToRequest(RequestTypes::DELETE_BY_QUERY, [
                'index' => $resource->prepareIndexName(),
                'body' => $query->toArray(),
            ])
        );
    }

    /**
     * @param AbstractResource $resource
     * @return ElasticsearchResponse|Promise
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function truncate(AbstractResource $resource): ElasticsearchResponse|Promise
    {
        return $this->getClient()->deleteByQuery(
            $this->requestConfig->applyToRequest(RequestTypes::TRUNCATE, [
                'index' => $resource->prepareIndexName(),
                'body' => [
                    'query' => [
                        'match_all' => new stdClass(),
                    ],
                ],
            ])
        );
    }

    /**
     * @param Closure|RequestConfigContract $config
     * @param Closure $actionsCallback
     * @return self
     */
    public function withConfig(RequestConfigContract|Closure $config, Closure $actionsCallback): self
    {
        $oldConfig = $this->requestConfig;

        try {
            if ($config instanceof Closure) {
                $this->requestConfig = $config($oldConfig);
            } else {
                $this->requestConfig = $config;
            }

            $actionsCallback();
        } finally {
            $this->requestConfig = $oldConfig;
        }

        return $this;
    }

    /**
     * @param string $string
     * @return string
     */
    public function clearString(string $string): string
    {
        $string = \mb_strtolower(
            \preg_replace(
                "/[^а-яa-z\d\'\і\є\ї\ ]/ui",
                '',
                $string
            )
        );

        return \preg_replace(
            '/(\ ){2,}/',
            ' ',
            $string
        );
    }

    /**
     * @return ResourcesRepository
     */
    public function resources(): ResourcesRepository
    {
        if (! isset($this->resources)) {
            $this->resources = new ResourcesRepository(
                $this->app['config']->get('efilter.resources', [])
            );
        }

        return $this->resources;
    }
}
