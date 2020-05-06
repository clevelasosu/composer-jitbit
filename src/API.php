<?php


namespace OSUCOE\JitBit;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\BadResponseException;

class API
{

    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function _request($method, $url, $json = '', $returnRequest = false)
    {
        $method = strtoupper($method);
        $validMethods = ['GET', 'POST', 'DELETE', 'PATCH', 'PUT'];
        if (!in_array($method, $validMethods)) {
            throw new JitBitException("Invalid Method: $method");
        }

        $request = new Request($method, $url, [], $json);

        if ($returnRequest) {
            return $request;
        }

        try {
            $res = $this->client->send($request);
            $body = json_decode($res->getBody()->getContents());
        } catch (BadResponseException $e) {
            $res = $e->getResponse();
            $body = json_decode($res->getBody()->getContents());

            if (isset($body->Errors)) {
                throw new JitBitException($body->Errors->description, $body->Errors->code, $e);
            } else {
                throw new JitBitException($e->getMessage(), $e->getCode(), $e);
            }
        } catch (\Exception $e) {
            throw new JitBitException($e->getMessage(), $e->getCode(), $e);
        }

        return $body;
    }

}