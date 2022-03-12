<?php

namespace App\Tests;

use App\Exception\CardInfosException;
use App\Exception\FileNotExistException;
use App\Exception\NoFileException;
use App\Exception\RatesAcessKeyException;
use App\Exception\RatesAPIException;
use App\Exception\TransactionFieldsException;
use App\Service\Card;
use App\Service\Transactions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TransactionsTest extends TestCase
{
    private Transactions $transactions;
    private Card $card;

    protected function setUp(): void
    {
        parent::setUp();
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->card = new Card($parameterBag);
        $this->transactions = new Transactions($this->card);
    }

    public function testSomething(): void
    {
        $this->assertTrue(true);
    }

    public function testInstanceOfClass(){
        $this->assertInstanceOf(Transactions::class, $this->transactions);
    }

    public function testInstanceWithFile(){
        $file = "input.txt";
        $this->transactions->setFile($file);
        $this->assertInstanceOf(Transactions::class,  $this->transactions);
    }

    public function testSetAndGetFile(){
        $file = "input.txt";
        $this->transactions->setFile($file);
        $this->assertEquals($file, $this->transactions->getFile());
    }


    public function testNullFileExceptionWhenFileNotSet(){
        $this->expectException(NoFileException::class);
        $this->transactions->readTransactions();
    }

    public function testNullFileExceptionWhenFileSetAsNull(){
        $this->expectException(NoFileException::class);
        $this->transactions->setFile(null);
        $this->transactions->readTransactions();
    }

    public function testFileNotExistException(){
        $this->expectException(FileNotExistException::class);
        $this->transactions->setFile("input2.txt");
        $this->transactions->readTransactions();
    }



    protected function getInstanceWithFile($file): Transactions
    {
        $this->transactions->setFile($file);
        return $this->transactions;
    }

    public function testFileReadeFiledNumberOfItems(){

        $transactions = $this->getInstanceWithFile(dirname(__FILE__)."/inputs/testInputNumber.txt");

        $this->assertEmpty($transactions->getTransactions());
        $transactions->readTransactions();
        $this->assertCount(5, $transactions->getTransactions());
    }

    public function testFileReadeFiledFormatItems(){
        $this->expectException(TransactionFieldsException::class);
        $transactions = $this->getInstanceWithFile(dirname(__FILE__)."/inputs/testObjectFormatException.txt");
        $transactions->readTransactions();
    }


    public function testGetCardInstanceAndGetter(){
        $this->card->setBin(516793);
        $this->assertInstanceOf(Card::class, $this->card);
        $this->assertEquals(516793, $this->card->getBin());
    }

    public function testGetCardInfoBadBinException(){
        $this->card->setBin(10000);
        $this->expectException(CardInfosException::class);
        $this->card->getInfos();
    }


    /**
     * @throws CardInfosException
     */
    public function testIfCardInEuroCurrencies(){
        $this->card->setBin(45717360);
        $this->assertTrue((bool)$this->card->isEu());
    }

    /**
     * @throws CardInfosException
     */
    public function testIfCardInEuroCurrenciesWithFailedTest(){
        $this->card->setBin(516793);
        $this->assertFalse($this->card->isEu());
    }

    /**
     * @throws CardInfosException
     */
    public function testCommissionRate(){

        $this->card->setBin(516793);

        $this->assertEquals(0.46, $this->card->getCommissionRate());


        $this->card->setBin(45417360);


        $this->assertEquals(1.56, $this->card->getCommissionRate());
    }


    /**
     * @throws RatesAcessKeyException
     * @throws RatesAPIException
     */
    public function testApiRatesMocked(){
        $mockedResult = [
            "rates" => [
                "AED" => 4
            ]
        ];

        $transactions = $this->getMockBuilder(Transactions::class)
            ->addMethods(['getRatesFromApi'])->getMock();
        $transactions->method('getRatesFromApi')->willReturn(json_encode($mockedResult));

        $rate = $transactions->getRate("AED");
        $this->assertEquals(4, $rate);
    }


    public function testGetFixedAmount(){


        $this->assertEquals(2000, $this->transactions->getFixedAmount("EUR", 0, 2000));

        $this->assertEquals(200, $this->transactions->getFixedAmount("EUR", 10, 2000));
        $this->assertEquals(200, $this->transactions->getFixedAmount("AT", 10, 2000));
    }


    /**
     * @throws RatesAcessKeyException
     * @throws RatesAPIException
     * @throws CardInfosException
     */
    public function testHandleOneItemInFile(){
        $mockedResult = [
            "rates" => [
                "USD" => 1
            ]
        ];

        $transactions = $this->getMockBuilder(Transactions::class)
            ->addMethods(['getRatesFromApi'])->getMock();
        $transactions->method('getRatesFromApi')->willReturn(json_encode($mockedResult));
        $transactions->setFile(dirname(__FILE__)."/inputs/testOneItem.txt");

        $result = $transactions->handle();
        $this->assertCount(1, $result);
        $this->assertEquals(1.09, $result[0]);

    }

    /**
     * @throws RatesAcessKeyException
     * @throws CardInfosException
     * @throws RatesAPIException
     */
    public function testHandle(){
        $mockedRatesResult = [
            "rates" => [
                "EUR" => 2,
                "USD" => 1.4678,
                "JPY" => 123.45,
                "GBP" => 4.77901
            ]
        ];

        $expectResult = [
            1,
            0.46,
            1.56,
            2.38,
            47.79,
        ];

        $transactions = $this->getMockBuilder(Transactions::class)
            ->addMethods(['getRatesFromApi'])->getMock();
        $transactions->method('getRatesFromApi')->willReturn(json_encode($mockedRatesResult));
        $transactions->setFile(dirname(__FILE__)."/../input.txt");

        $result = $transactions->handle();
        $this->assertCount(5, $result);
        $this->assertEquals($expectResult, $result);

    }
}
