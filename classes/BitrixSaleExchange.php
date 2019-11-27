<?php

namespace Classes;


use GuzzleHttp\Client;
use Classes\Exceptions\FailException;
use Classes\Exceptions\AuthException;


/**
 * Class BitrixSaleExchange
 * @package Classes
 * @property \SimpleXMLElement $xml
 */
class BitrixSaleExchange
{
    const EXCHANGE_SCRIPT = 'bitrix/admin/1c_exchange.php';
    protected $client;
    protected $sessid;
    protected $xml;
    protected $params;
    protected $mode;
    protected $type;

    public function __construct($bitrix_uri, $login, $password, $params = [])
    {
        $this->client = new Client([
            'base_uri' => $bitrix_uri,
            'timeout' => 350.0,
            'auth' => [$login, $password],
            'cookies' => true,
            'debug' => true
        ]);

        $this->params = $params;
    }

    protected function decode($text)
    {
        return iconv('windows-1251', 'UTF-8', $text);
    }

    protected function checkSuccess(\GuzzleHttp\Psr7\Response $response)
    {
        $body = $response->getBody()->getContents();
        if (preg_match('/failure/m', $body)) {
            throw new FailException($this->decode($body));
        }
    }

    public function auth()
    {
        echo "AUTH\n";
        $params = $this->params + ['type' => $this->type, 'mode' => 'checkauth'];
        $response = $this->client->request('GET', self::EXCHANGE_SCRIPT, [
            'query' => $params
        ]);

        $step1 = (string)$response->getBody();

        if (!preg_match('/^sessid=(\S+)$/m', $step1, $matches)) {
            throw new AuthException($this->decode($step1));
        }
        $this->sessid = $matches[1];
    }


    public function init()
    {
        echo "INIT\n";
        $params = $this->params + ['type' => $this->type, 'mode' => 'init', 'sessid' => $this->sessid];
        $response = $this->client->request('GET',
            self::EXCHANGE_SCRIPT, [
                'query' => $params
            ]);
        $this->checkSuccess($response);
    }

    protected function setMode($mode)
    {
        $this->mode = $mode;
    }

    protected function setType($type)
    {
        $this->type = $type;
    }

    protected function sendSampleFile($name)
    {
        echo "FILE\n";
        $params = $this->params + ['type' => $this->type, 'mode' => 'file', 'sessid' => $this->sessid, 'filename' => $name];
        $response = $this->client->request('POST',
            self::EXCHANGE_SCRIPT, [
                'query' => $params,
                'body' => file_get_contents(__DIR__ . '/../samples/' . $name),
                'headers' => [
                    'Content-Type' => 'application/xml',
                ]
            ]);
        $this->checkSuccess($response);
    }

    protected function importWhileNotSuccess($name)
    {
        echo "IMPORT\n";
        $params = $this->params + ['type' => $this->type, 'mode' => 'import', 'sessid' => $this->sessid,'filename' => $name];
        while (true) {
            $response = $this->client->request('GET',
                self::EXCHANGE_SCRIPT, [
                    'query' => $params,
                ]);
            $body = (string)$response->getBody();
            echo $this->decode($body) . "\n";
            if(preg_match('/success/',$body)){
                break;
            }
            if(!preg_match('/progress/',$body)){
                throw new FailException($this->decode($body));
                break;
            }
        }
    }

    public function referencesExchange()
    {
        $this->setType('reference');
        //$this->auth();
        $this->init();
        //$this->sendSampleFile('references.xml');
        //$this->importWhileNotSuccess('references.xml');
    }
}
