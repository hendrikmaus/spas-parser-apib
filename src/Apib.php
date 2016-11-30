<?php

namespace Hmaus\Spas\Parser;

use Hmaus\Reynaldo\Elements\ApiHttpTransaction;
use Hmaus\Reynaldo\Elements\ApiParseResult;
use Hmaus\Reynaldo\Elements\ApiResource;
use Hmaus\Reynaldo\Elements\ApiResourceGroup;
use Hmaus\Reynaldo\Elements\ApiStateTransition;
use Hmaus\Reynaldo\Parser\RefractParser;

class Apib implements Parser
{
    /**
     * @var RefractParser
     */
    private $parser;

    public function __construct()
    {
        $this->parser = new RefractParser();
    }

    public function parse(array $description) : array
    {
        return $this->buildParsedRequests(
            $this->parser->parse($description)
        );
    }

    /**
     * Traverse the document and build parsed requests for spas
     *
     * @param ApiParseResult $parseResult
     * @return ParsedRequest[]
     */
    private function buildParsedRequests(ApiParseResult $parseResult) : array
    {
        $requests = [];

        foreach ($parseResult->getApi()->getResourceGroups() as $apiResourceGroup) {
            foreach ($apiResourceGroup->getResources() as $apiResource) {
                foreach ($apiResource->getTransitions() as $apiStateTransition) {
                    foreach ($apiStateTransition->getHttpTransactions() as $apiHttpTransaction) {
                        $this->processApiHttpTransactions(
                            $apiHttpTransaction,
                            $apiResourceGroup,
                            $apiResource,
                            $apiStateTransition,
                            $requests
                        );
                    }
                }
            }
        }

        return $requests;
    }

    /**
     * @param $apiHttpTransaction
     * @param $apiResourceGroup
     * @param $apiResource
     * @param $apiStateTransition
     * @param $requests
     * @return array
     */
    private function processApiHttpTransactions(
        ApiHttpTransaction $apiHttpTransaction,
        ApiResourceGroup $apiResourceGroup,
        ApiResource $apiResource,
        ApiStateTransition $apiStateTransition,
        array &$requests
    ) : array
    {
        $apiRequest = $apiHttpTransaction->getHttpRequest();
        $apiResponse = $apiHttpTransaction->getHttpResponse();
        $req = new SpasRequest();

        $req->setResourceGroup($apiResourceGroup);

        if ($apiResourceGroup->getTitle()) {
            $req->appendToName($apiResourceGroup->getTitle());
        }

        if ($apiResource->getTitle()) {
            $req->appendToName($apiResource->getTitle());
        }

        if ($apiStateTransition->getTitle()) {
            $req->appendToName($apiStateTransition->getTitle());
        }

        if ($apiHttpTransaction->getTitle()) {
            $req->appendToName($apiHttpTransaction->getTitle());
        }

        if ($apiRequest->getTitle()) {
            $req->appendToName($apiRequest->getTitle());
        }

        $hrefVarsFromStateTransition = $apiStateTransition->getHrefVariablesElement()->getAllVariables();
        $hrefVarsFromApiResource = $apiResource->getHrefVariablesElement()->getAllVariables();
        $hrefVars = array_merge($hrefVarsFromStateTransition, $hrefVarsFromApiResource);

        if ($hrefVars) {
            $hrefVarsForParamBag = [];

            foreach ($hrefVars as $hrefVarValueObject) {
                // Skip optional params that do not provide a default value
                // Using an example value can lead to undesired results
                if ($hrefVarValueObject->required === 'optional' && $hrefVarValueObject->default === null) {
                    continue;
                }

                $hrefVarsForParamBag[$hrefVarValueObject->name] =
                    $hrefVarValueObject->default
                        ? $hrefVarValueObject->default
                        : $hrefVarValueObject->example;
            }

            $req->params->add($hrefVarsForParamBag);
        }

        $req->setUriTemplate($apiResource->getHref());

        $req->headers->add($apiRequest->getHeaders());
        $req->setMethod($apiRequest->getMethod());
        $req->setEnabled(true);

        if ($apiRequest->hasMessageBody()) {
            $req->setContent($apiRequest->getMessageBodyAsset()->getBody());
        }

        $response = new SpasResponse();
        $response->setStatusCode($apiResponse->getStatusCode());
        $response->headers->add($apiResponse->getHeaders());

        if ($apiResponse->hasMessageBody()) {
            $response->setBody($apiResponse->getMessageBodyAsset()->getBody());
        }

        if ($apiResponse->hasMessageBodySchema()) {
            $response->setSchema($apiResponse->getMessageBodySchemaAsset()->getBody());
        }

        $req->setExpectedResponse($response);
        $requests[] = $req;

        return $requests;
    }
}
