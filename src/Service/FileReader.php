<?php

namespace App\Service;

use App\Exception\FileNotExistException;
use App\Exception\NoFileException;
use App\Exception\TransactionFieldsException;

class FileReader
{

    /**
     * @throws NoFileException
     * @throws FileNotExistException
     * @throws TransactionFieldsException
     */
    public static function ReadFile(mixed $fileName): array
    {
        if($fileName == null){
            throw new NoFileException;
        }

        if(!file_exists($fileName)){
            throw new FileNotExistException;
        }
        $result = [];

        $content = file_get_contents($fileName);
        $content = preg_replace("/(\R){2,}/", "$1", $content);
        $rows = explode("\n", $content);
        foreach ($rows as $row) {
            if (!empty($row)){
                $transaction = json_decode($row);

                if( !property_exists($transaction, "bin") ||
                    !property_exists($transaction, "amount") ||
                    !property_exists($transaction, "currency")
                ){
                    throw new TransactionFieldsException;
                }
                $result[] = $transaction;
            }
        }
        return $result;
    }
}