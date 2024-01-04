<?php
require_once 'config/config.php';
include("services/transactionService.php");

class TransactionController {
    private $transactionService;
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';
    
    public function __construct($db) {
        $this->transactionService = new TransactionService($db);
    }

    public function getTransactions($id = null) {
        if($id !== null) {
            echo "GET request for ID: $id";
        }else {
            $this->transactionService->getDataTransaction();
        }
    }

    public function mainInit() {
        $this->transactionService->updateDataAccounts();
    }

    public function getAccounts() {
        $this->transactionService->getDataAccount();
    }

    public function uploadTransactions() {
        $this->transactionService->uploadDataTransaction();
    }

    public function getCurrency() {
        $currencyEUR = $this->transactionService->getCurrencyValue(self::CURRENCY_EUR);
        $currencyUSD = $this->transactionService->getCurrencyValue(self::CURRENCY_USD);
        $data = array(
            'USD' => $currencyUSD,
            'EUR' => $currencyEUR
        );

        $jsonData = json_encode($data);
        header('Content-Type: application/json');
        echo $jsonData;
        exit;
    }

    public function getChartData() {
        $this->transactionService->getDataChart();
    }
    
    public function handlePut() {
        echo "PUT request";
    }

    public function handleDelete() {
        echo "DELETE request";
    }
}