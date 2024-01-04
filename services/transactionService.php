<?php

use DataTables\Editor;
use DataTables\Editor\Field;
use DataTables\Editor\Format;
use DataTables\Editor\Validate;
use DataTables\Editor\ValidateOptions;

class TransactionService {
    private $db;
    const START_BALANCE = 0;

    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getDataTransaction() {
        try{
            Editor::inst($this->db, 'transactions')
                ->fields(
                    Field::inst('account')
                        ->validator(Validate::notEmpty(ValidateOptions::inst()
                            ->message('An account name is required')
                        )),
                    Field::inst('transaction_no')
                        ->validator(Validate::notEmpty(ValidateOptions::inst()
                            ->message('A transaction_no name is required')
                        )),
                    Field::inst('amount'),
                    Field::inst('currency'),
                    Field::inst('date')
                        ->validator(Validate::dateFormat('Y-m-d H:i:s'))
                        ->getFormatter(Format::dateSqlToFormat('Y-m-d H:i:s'))
                        ->setFormatter(Format::dateFormatToSql('Y-m-d H:i:s'))
                )
                ->debug(true)
                ->process($_POST)
                ->json();

        }catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        $conn = null;
    }
    
    private function upgradeAccountRow() {
        
    }
    
    public function getDataAccount() {
        try{
            if(Editor::action($_POST) === Editor::ACTION_EDIT) {
                
            }
            
            
            Editor::inst($this->db, 'accounts')
                ->fields(
                    Field::inst('account'),
                    Field::inst('currency'),
                    Field::inst('starting_balance'),
                    Field::inst('end_balance'),
                    Field::inst('result_balance')
                )
                ->on('preGet', function($editor, $id) {
//                    $this->updateDataAccounts();
                })
                ->on( 'preEdit', function($editor, $id, &$values) {
                    if(isset($id) && intval($id) > 0) {
                        $sql = 'SELECT `account`, `currency` FROM accounts WHERE id=' . intval($id);
                        $result = $this->db->sql($sql)->fetch();
                    }
                    if(isset($values['account']) && !empty($values['account']) && $result) {
                        $this->db->update(
                            'transactions', 
                            array(
                                'account' => $values['account']
                            ),
                            array(
                                'account' => $result['account']
                            )
                        );
                    }
                    if(isset($values['starting_balance']) && !empty($values['starting_balance']) && $result) {
//                        $currencyRate = $result['currency'] === MAIN_CURRENCY ? 1 : $this->getCurrencyValue($result['currency']);
                        $currencyRate = $this->getCurrencyValue($result['currency']);
                        $sql = '
                            UPDATE `accounts` 
                            SET `result_balance` = (' . doubleval($values['starting_balance']) .' + `end_balance`) * ' . $currencyRate . ' 
                            WHERE id='.intval($id);
                        $this->db->sql($sql);
                    }
                    
                })
                ->debug(true)
                ->process($_POST)
                ->json();

        }catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        $conn = null;
    }
    
    public function getDataChart() {
        try{
            $sql = "
            SELECT `account`, SUM(amount) AS `total_amount`, DATE_FORMAT(date, '%Y-%m') AS `month_year`, `currency`
            FROM transactions
            GROUP BY account, DATE_FORMAT(date, '%Y-%m')
            
            UNION
            
            SELECT 'Combined' AS `account`, SUM(amount) AS `total_amount`, DATE_FORMAT(date, '%Y-%m') AS `month_year`, `currency`
            FROM transactions
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ";
            $result = $this->db->sql($sql)->fetchAll();
            
            $sql = "SELECT `starting_balance`, `account` FROM accounts";
            $resStartBalance = $this->db->sql($sql)->fetchAll();
            $startBalance = array();
            $sumStartBalance = 0;
            for ($i = 0; $i < count($resStartBalance); ++$i) {
                $startBalance[$resStartBalance[$i]['account']] = $resStartBalance[$i]['starting_balance'];
                $sumStartBalance += $resStartBalance[$i]['starting_balance'];
            }
            
            $data = array();
            for ($j = 0; $j < count($result); ++$j) {
                $account =   $result[$j]['account'];
                $monthYear = $result[$j]['month_year'];
                $amount =    (float)$result[$j]['total_amount'] + (isset($startBalance[$account]) ? $startBalance[$account] : $sumStartBalance);

                if (!isset($data[$account])) {
                    $data[$account] = array();
                }

                if (!isset($data[$account][$monthYear])) {
                    $data[$account][$monthYear] = 0;
                }

                $data[$account][$monthYear] += $amount;
            }

            $categories = array();
            $series = array();

            foreach ($data as $account => $values) {
                $accountData = array(
                    'name' => $account,
                    'data' => array()
                );
                
                foreach ($values as $monthYear => $amount) {
                    if (!in_array($monthYear, $categories)) {
                        $categories[] = $monthYear;
                    }
                    
                    $accountData['data'][] = array(
                        'x' => $monthYear,
                        'y' => (float)$amount
                    );
                }

                $series[] = $accountData;
            }

            $result = array(
                'categories' => $categories,
                'series' => $series
            );

            echo json_encode($result);
        }catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        $conn = null;
    }
    
    public function getCurrencyValue($currency, $amount = 1) {
        if(ENV !== 'prod') {
            return 1; // For Developing
        }
        if($currency === MAIN_CURRENCY) {
            return 1;
        }

        $apiUrl = API_URL . '?from=' . $currency . '&to=' . MAIN_CURRENCY . '&amount=' . $amount . '&access_key=' . API_KEY;
        $response = file_get_contents($apiUrl);

        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data !== null && isset($data['result'])) {
                return $data['result'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function updateDataAccounts() {
        $this->db->delete('accounts');

        $sql = 'SELECT `account`, `currency`, SUM(`amount`) AS sum_amount FROM transactions GROUP BY account';
        $result = $this->db->sql($sql)->fetchAll();

        for ($j = 0; $j < count($result); ++$j) {
            $this->db->insert('accounts', array(
                'account'          => $result[$j]['account'],
                'currency'         => $result[$j]['currency'],
                'starting_balance' => self::START_BALANCE,
                'end_balance'      => $result[$j]['sum_amount'],
                'result_balance'   => (self::START_BALANCE + $result[$j]['sum_amount']) * $this->getCurrencyValue($result[$j]['currency'])
            ));
        }
    }
    
    public function uploadDataTransaction() {
        $firstLine = true;
        if(isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {

            $this->db->delete('transactions');
            
            $file = fopen($_FILES['file']['tmp_name'], "r");

            while(($data = fgetcsv($file, 1000, ",")) !== FALSE) {
                if($firstLine) {
                    $firstLine = false;
                    continue;
                }
                if($data[4] !== '0000-00-00 00:00:00') {
                    $this->db->insert('transactions', array(
                        'account'        => $data[0],
                        'transaction_no' => $data[1],
                        'amount'         => doubleval($data[2]),
                        'currency'       => $data[3],
                        'date'           => $data[4]
                    ));
                }
            }
            
            $this->updateDataAccounts();

            fclose($file);
            header('Location: '. BASE_URL);
        }else {
            echo "Error uploading file.";
        }
        exit;
    }
}