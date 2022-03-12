<?php

namespace App\Service;

use App\Exception\CardInfosException;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Card
{
    private string $bin;
    public ParameterBagInterface $params;

    public function __construct(
        ParameterBagInterface $params
    )
    {
        $this->params = $params;
    }

    public function getBin(): string
    {
        return $this->bin;
    }

    public function setBin($bin)
    {
        $this->bin = $bin;
    }

    /**
     * @throws CardInfosException
     */
    public function getInfos()
    {
        $binResults = null;
        $url = $this->params->get('bin_api') . $this->getBin();
        $file_headers = @get_headers($url);

        if(!$file_headers ||
            $file_headers[0] == 'HTTP/1.1  404 NOT FOUND' ||
            $file_headers[0] == 'HTTP/1.1 400 Bad Request'
        ){
            throw new CardInfosException;
        }

        try{
            $binResults = file_get_contents($url);
        } catch(Exception $e){
            dd($e->getMessage());
        }

        if (!$binResults) {
            throw new CardInfosException;
        }

        return json_decode($binResults);
    }

    /**
     * @throws CardInfosException
     */
    public function getCommissionRate(): float
    {
        $isEu = $this->isEu();
        return ($isEu ? 0.01 : 0.02);
    }

    /**
     * @throws CardInfosException
     */
    public function isEu(): string
    {
        $infoCard = $this->getInfos();
        $alpha2 = $infoCard->country->alpha2;
        $map = $this->params->get('eu_countries');
        return in_array($alpha2, $map);
    }

}