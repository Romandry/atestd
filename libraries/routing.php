<?php
include("libraries/TableEditor/lib/DataTables.php");

$requestUri = rtrim($_SERVER['REQUEST_URI'], '/');
$uriParts = explode('/', $requestUri);

if(isset($uriParts[1]) && !empty($uriParts[1])) {
    $uriParams = explode('?', $uriParts[1]);
}

require_once 'controllers/TransactionController.php';
$transactionController = new TransactionController($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if(isset($uriParams[0]) && !empty($uriParams[0])) {
            switch($uriParams[0]) {
                case 'transaction':
                    $transactionController->getTransactions();
                    break;
                case 'account':
                    $transactionController->getAccounts();
                    break;
                case 'currency':
                    $transactionController->getCurrency();
                    break;
                case 'chart':
                    $transactionController->getChartData();
                    break;
                default:
                    include_once "templates/main.php";
                    break;
            }
        }else {
            $transactionController->mainInit();
            include_once "templates/main.php";
        }
        break;
    case 'POST':
        if(isset($uriParams[0]) && !empty($uriParams[0])) {
            switch($uriParams[0]) {
                case 'upload':
                    $transactionController->uploadTransactions();
                    break;
                case 'account':
                    $transactionController->getAccounts();
                    break;
                default:
                    $transactionController->getTransactions();
                    break;
            }
        }
        break;
    case 'PUT':
        $transactionController->handlePut();
        break;
    case 'DELETE':
        $transactionController->handleDelete();
        break;
    default:
        http_response_code(405);
        echo "Method Not Allowed";
        break;
}
