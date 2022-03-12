<?php

namespace App\Service;

use App\Exception\CardInfosException;
use App\Exception\FileNotExistException;
use App\Exception\NoFileException;
use App\Exception\RatesAcessKeyException;
use App\Exception\RatesAPIException;
use App\Exception\TransactionFieldsException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Transactions
{
    private mixed $fileName;
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $params;
    private array $transactions = [];
    private string|bool|null $rates = null;
    private Card $card;

    public function __construct(
        Card $card
    )
    {
        $this->card = $card;
        $this->params = $card->params;
    }

    /**
     * @param mixed $arg1
     * @return void
     */
    public function setFile(mixed $arg1)
    {
        $this->fileName = $arg1;
    }

    /**
     * @return mixed
     */
    public function getFile(): mixed
    {
        return $this->fileName;
    }

    /**
     * @return array
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * @return void
     */
    public function readTransactions()
    {
        try {
            $this->transactions = FileReader::ReadFile($this->fileName);
        } catch (FileNotExistException|NoFileException|TransactionFieldsException $e) {
            echo "Error:" . $e->getMessage();
        }
    }

    /**
     * @return array
     * @throws RatesAcessKeyException
     * @throws CardInfosException|RatesAPIException
     */
    public function handle(): array
    {
        $result = [];
        $this->readTransactions();

        foreach ($this->getTransactions() as $transaction) {
            $this->card->setBin($transaction->bin);
            $rate = $this->getRate($transaction->currency);
            $fixedAmount = $this->getFixedAmount($transaction->currency, $rate, $transaction->amount);
            $result[] = round($fixedAmount * $this->card->getCommissionRate(), 2);
        }
        return $result;
    }

    /**
     * @return string|false
     * @throws RatesAPIException
     */
    public function getRatesFromApi(): bool|string
    {
        $file_headers = @get_headers($this->params->get('rate_api'));

        if(!$file_headers ||
            $file_headers[0] == 'HTTP/1.1  404 NOT FOUND' ||
            $file_headers[0] == 'HTTP/1.1 400 Bad Request'
        ){
            throw new RatesAPIException;
        }
        return file_get_contents($this->params->get('rate_api'));
    }

    /**
     * @return string|false
     */
    public function getFixedAmount($currency, $rate, $amount): bool|string
    {
        $amountFixed = 0;
        if ($currency == 'EUR' || $rate == 0) {
            $amountFixed = $amount;
        }
        if ($currency != 'EUR' || $rate > 0) {
            $amountFixed = $amount / $rate;
        }
        return $amountFixed;
    }

    /**
     * @return string|false
     * @throws RatesAcessKeyException|RatesAPIException
     */
    public function getRate($currency): bool|string
    {
        if($this->rates == null){
            $this->rates = $this->getRatesFromApi();
        }

        $result = @json_decode($this->rates, true);

        if(array_key_exists("error", $result) && $result["error"]["type"] == "missing_access_key") {
            throw new RatesAcessKeyException;
        }

        return $result['rates'][$currency];
    }
}