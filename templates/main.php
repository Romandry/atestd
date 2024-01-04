<!DOCTYPE html>
<html>
    <head>
        <?php include_once "templates/header.php"; ?>
        <?php include_once "templates/scripts.php"; ?>
    </head>
    
    
    <body>
    
        <div class="table-container-1">
            <div class="box-1">
                <label>Upload</label>
                <div class="block-1">
                    <?php include_once "templates/block_upload_data.php"; ?>
                </div>
            </div>    
            <div class="box-2">
                <label>Currency exchange rates</label>
                <div class="block-2">
                    <?php include_once "templates/block_rates.php"; ?>
                </div>
            </div>
        </div>
        
        <div class="table-container-3">
            <div class="box-3">
                <label>List of bank accounts</label>
                <div class="block-3">
                    <?php include_once "templates/block_accounts.php"; ?>
                </div>
            </div>
        </div>

        <div class="table-container-4">
            <div class="box-4">
                <label>Cash forecast</label>
                <div class="block-4">
                    <?php include_once "templates/block_chart.php"; ?>
                </div>
            </div>
        </div>
    
        <div class="table-container-5">
            <div class="box-5">
                <label>Transactions</label>
                <div class="block-5">
                    <?php include_once "templates/block_transactions.php"; ?>
                </div>
            </div>
        </div>
    </body>
</html>