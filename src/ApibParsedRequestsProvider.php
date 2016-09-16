<?php

namespace Hmaus\Spas\Parser\Apib;

use Hmaus\Reynaldo\Elements\ApiHttpTransaction;
use Hmaus\Reynaldo\Elements\ApiParseResult;
use Hmaus\Reynaldo\Elements\ApiResource;
use Hmaus\Reynaldo\Elements\ApiResourceGroup;
use Hmaus\Reynaldo\Elements\ApiStateTransition;
use Hmaus\Reynaldo\Parser\RefractParser;
use Hmaus\SpasParser\ParsedRequest;
use Hmaus\SpasParser\Parser;
use Hmaus\SpasParser\SpasRequest;
use Hmaus\SpasParser\SpasResponse;

class ApibParsedRequestsProvider implements Parser
{
    /**
     * @var RefractParser
     */
    private $parser;

    public function __construct()
    {
        $this->parser = new RefractParser();
    }

    public function parse(array $description)
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
    private function buildParsedRequests(ApiParseResult $parseResult)
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
    )
    {
        $apiRequest = $apiHttpTransaction->getHttpRequest();
        $apiResponse = $apiHttpTransaction->getHttpResponse();
        $req = new SpasRequest();

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
                if ($hrefVarValueObject->default === null && $hrefVarValueObject->example === null) {
                    if ($hrefVarValueObject->dataType === 'string') {
                        $hrefVarValueObject->example = uniqid();
                    } elseif ($hrefVarValueObject->dataType === 'number') {
                        $hrefVarValueObject->example = rand(0, 1000);
                    } else {
                        $hrefVarValueObject->example = 'missing-example-value';
                    }
                }

                $hrefVarsForParamBag[$hrefVarValueObject->name] =
                    $hrefVarValueObject->default
                        ? $hrefVarValueObject->default
                        : $hrefVarValueObject->example;
            }

            $req->params->add($hrefVarsForParamBag);
        }

        $req->setHref($apiResource->getHref());

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

        $req->setResponse($response);
        $requests[] = $req;

        return $requests;
    }
}
