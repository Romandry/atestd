<script defer class="init">
    const BASE_URL = '<?php echo BASE_URL;?>';
    
    $(document).ready(function() {
        var transaction = new DataTable.Editor({
            ajax: `${BASE_URL}/transaction`,
            fields: [
                {
                    label: 'Account:',
                    name: 'account'
                },
                {
                    label: 'Transaction No:',
                    name: 'transaction_no'
                },
                {
                    label: 'Amount:',
                    name: 'amount'
                },
                {
                    label: 'Currency:',
                    name: 'currency'
                },
                {
                    label: 'Date:',
                    name: 'date',
                    type: 'datetime'
                }
            ],
            table: '#transactions'
        });

        $('#transactions').DataTable({
            ajax: `${BASE_URL}/transaction`,
            buttons: [
                {extend: 'csv', text: 'Excel', className: 'custom-btn'},
                {extend: 'pdf'}
            ],
            columns: [
                {data: 'account'},
                {data: 'transaction_no', className: 'editable'},
                {data: 'amount', className: 'editable'},
                {data: 'currency'},
                {data: 'date', className: 'editable'},
                {
                    data: null,
                    defaultContent: '<i class="fa fa-trash"/>',
                    className: 'editor-delete dt-center',
                    orderable: false
                }
            ],
            dom: '<"top"lp><"clear"><"top"B>',
            order: [[1, 'asc']],
            select: {
                style: 'os',
                selector: 'td:first-child'
            }
        });
        
        $('#transactions').on('click', 'tbody td.editable', function (e) {
            transaction.inline(this);
        });
        $('#transactions').on('click', 'tbody td.editor-delete', function (e) {
            transaction.remove(this.parentNode, {
                title: 'Delete record',
                message: 'Are you sure you wish to delete this record?',
                buttons: 'Delete'
            });
        });


        var accounts = new DataTable.Editor({
            ajax: `${BASE_URL}/account`,
            fields: [
                {
                    label: 'Account:',
                    name: 'account'
                },
                {
                    label: 'Currency:',
                    name: 'currency'
                },
                {
                    label: 'Starting balance:',
                    name: 'starting_balance'
                },
                {
                    label: 'End balance:',
                    name: 'end_balance'
                },
                {
                    label: 'End balance (CHF):',
                    name: 'result_balance'
                }
            ],
            table: '#accounts'
        });

        $('#accounts').DataTable({
            ajax: `${BASE_URL}/account`,
            columns: [
                {data: 'account', className: 'editable'},
                {data: 'currency'},
                {data: 'starting_balance', className: 'editable'},
                {data: 'end_balance'},
                {data: 'result_balance'}
            ],
            dom: 'rt',
            order: [[1, 'asc']],
            select: {
                style: 'os',
                selector: 'td:first-child'
            }
        });
        
        $('#accounts').on('click', 'tbody td.editable', function (e) {
            accounts.inline(this);
        });
        $('#accounts').on('draw.dt', function () {
            fetchChartAndRender();
            $('#transactions').DataTable().ajax.reload();
        });
        
        
        
        var dropArea = $('#drop-area');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.on(eventName, preventDefaults);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.on(eventName, function() {
                dropArea.addClass('highlight');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.on(eventName, function() {
                dropArea.removeClass('highlight');
            });
        });

        dropArea.on('drop', function(e) {
            var files = e.originalEvent.dataTransfer.files;
            $('#file-name').html(files[0].name);
        });

        $.ajax({
            url: `${BASE_URL}/currency`,
            method: 'GET',
            dataType: 'json',
            success: function(response){
                $('#currencyValueUSD').text(response.USD);
                $('#currencyValueEUR').text(response.EUR);
            },
            error: function(){
                console.error('Error get data');
            }
        });
        
        

        var table_account = $('#accounts').DataTable();

        table_account.on('draw.dt', function() {
            var api = new $.fn.dataTable.Api(table_account);
            var header = $(api.table().header());

            header.find('tr.total-row').remove();

            var totals = [];

            api.columns().every(function() {
                var column = this;
                var columnIndex = column.index();

                if (columnIndex >= 5) {
                    return;
                }

                if (columnIndex >= 1 && columnIndex <= 5) {
                    var columnTotal = column.data().toArray().reduce(function(acc, value) {
                        var parsedValue = parseFloat(value.toString().replace(',', '.'));

                        return acc + (isNaN(parsedValue) ? 0 : parsedValue);
                    }, 0);

                    if (columnIndex === 1) {
                        totals.push('');
                    } else {
                        totals.push(typeof columnTotal === 'number' ? columnTotal.toFixed(2) : 0);
                    }
                }
            });

            var headerRow = $('<tr class="total-row"/>').appendTo(header);
            headerRow.append('<th>Total</th>');

            totals.forEach(function(total) {
                headerRow.append('<th>' + total + '</th>');
            });
        });

        $('.custom-btn').before('<span>Export full table </span>');



        function fetchChartAndRender() {
            fetch(`${BASE_URL}/chart`)
                .then(response => response.json())
                .then(data => {
                    const chartData = data.series.map(serie => ({
                        name: serie.name,
                        data: serie.data.map(point => ({
                            x: Date.parse(point.x),
                            y: point.y
                        }))
                    }));

                    Highcharts.chart('chartContainer', {
                        title: {
                            text: 'Cash forecast'
                        },
                        xAxis: {
                            type: 'datetime',
                            labels: {
                                formatter: function () {
                                    return Highcharts.dateFormat('%b %y', this.value);
                                }
                            }
                        },
                        yAxis: {
                            title: {
                                text: 'Amount'
                            }
                        },
                        series: chartData,
                        exporting: {
                            buttons: {
                                contextButton: {
                                    menuItems: [
                                        'downloadPNG',
                                        'downloadPDF',
                                        'downloadSVG'
                                    ]
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        fetchChartAndRender();
    });
</script>