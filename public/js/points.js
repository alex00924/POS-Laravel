$(document).ready(function () {
    let points_table_dom = $('#points_table');
    base_url = points_table_dom.data('url');
    
    let columns, targets = [];
    if (base_url === '/points') {
        columns = [
            { data: 'created_at', name: 'created_at'  },
            { data: 'points', name: 'points'},
            { data: 'used_with', name: 'used_with'},
            { data: 'action', name: 'action'},
            { data: 'action', name: 'action'}
        ];
        targets = [2, 3, 4];
    } else {
        columns = [
            { data: 'created_at', name: 'created_at'  },
            { data: 'points', name: 'points'},
            { data: 'action', name: 'action'},
        ];
        targets = [2];
    }
    //Points table
    
    points_table = points_table_dom.DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        ajax: base_url + '?business_location=' + $('#business_location').val(),
        columnDefs: [ {
            "targets": targets,
            "orderable": false,
            "searchable": false
        } ],
        columns: columns,
        "fnDrawCallback": function (oSettings) {
            let total_points = sum_table_col(points_table_dom, 'row-points');
            $('#footer_points_total').text(total_points);

            // There exists price column only in Total Points page
            if(base_url === '/points') {
                let total_price = sum_table_col(points_table_dom, 'total-point-price');
                $('#footer_total_price').text(total_price);
            }

            __currency_convert_recursively(points_table_dom);
        }
    });

    // get current state of points
    getBusinessPoints();
    function getBusinessPoints() {
        var business_location = $('#business_location').val();
        $.ajax({
            url: '/points/business_points',
            method: 'get',
            data: {'business_location':business_location},
            success: function (response) {
                let business_name = response.business_name;
                let remaining_points = response.business_points;
                let requested_points = response.requested_points;
                let approved_points = response.approved_points;
                let rejected_points = response.rejected_points;
                let business_points = Number.parseInt(remaining_points) + Number.parseInt(requested_points) + Number.parseInt(approved_points) + Number.parseInt(rejected_points);
                console.log('business_points', business_points);
                let label_remaining = "Remained Bullets (" + remaining_points + ")";
                let label_requested = "Requested Bullets (" + requested_points + ")";
                let label_approved = "Approved Bullets (" + approved_points + ")";
                let label_rejected = "Rejected Bullets (" + rejected_points + ")";
                // chart
                var chart = new CanvasJS.Chart("chartContainer", {
                    theme: "light2", // "light1", "light2", "dark1", "dark2"
                    exportEnabled: true,
                    animationEnabled: true,
                    title: {
                        text: "Current Bullets state of " + business_name
                    },
                    data: [{
                        type: "pie",
                        startAngle: 25,
                        toolTipContent: "<b>{label}</b>: {y}%",
                        showInLegend: "true",
                        legendText: "{label}",
                        indexLabelFontSize: 16,
                        indexLabel: "{label} - {y}%",
                        dataPoints: [
                            { y: calc_percentage(remaining_points, business_points), label: label_remaining },
                            { y: calc_percentage(requested_points, business_points), label: label_requested },
                            { y: calc_percentage(approved_points, business_points), label: label_approved },
                            { y: calc_percentage(rejected_points, business_points), label: label_rejected },
                        ]
                    }]
                });
                chart.render();
                //chart end
                // let zingchart_type = base_url.split('/');
                // draw_single_chart(zingchart_type[zingchart_type.length - 1], remaining_points, requested_points, approved_points, rejected_points);
            }
        });
    }

    function calc_percentage(real, total) {
        if( total === 0 )
            return 0;
        return Math.round(1000000 * real / total) / 10000;
    }

    function draw_single_chart(type, remained_points, requested_points, approved_points, rejected_points) {
        remained_points = '[' + remained_points + ']';
        requested_points = '[' + requested_points + ']';
        approved_points = '[' + approved_points + ']';
        rejected_points = '[' + rejected_points + ']';

        console.log('remained_points', remained_points);
        console.log('rejected_points', rejected_points);
        console.log('approved_points', approved_points);
        console.log('requested_points', requested_points);

        let myConfig = {
            type: "pie",
            plot: {
                borderColor: "#2B313B",
                borderWidth: 3,
                // slice: 90,
                valueBox: {
                    placement: 'out',
                    text: '%t\n%npv%',
                    fontFamily: "Open Sans"
                },
                tooltip: {
                    fontSize: '18',
                    fontFamily: "Open Sans",
                    padding: "5 10",
                    text: "%npv%"
                },
                animation: {
                    effect: 2,
                    method: 7,
                    speed: 500,
                    sequence: 1,
                    delay: 3000
                }
            },
            title: {
                fontColor: "#8e99a9",
                text: 'Requested Bullets',
                align: "left",
                offsetX: 10,
                fontFamily: "Open Sans",
                fontSize: 18
            },
            plotarea: {
                margin: "20 0 0 0"
            },
            series: [{
                values: remained_points,
                text: "Remained Bullets",
                backgroundColor: '#6d78ad',
                detached: type === 'points'
            }, {
                values: requested_points,
                text: "Requested Bullets",
                backgroundColor: '#51cda0',
                detached: type === 'requested'
            }, {
                values: approved_points,
                text: "Approved Bullets",
                backgroundColor: '#df7970',
                detached: type === 'approved'
            }, {
                values: rejected_points,
                text: 'Rejected Bullets',
                backgroundColor: '#4c9ca0',
                detached: type === 'rejected'
            }]
        };

        zingchart.render({
            id: 'single_chart',
            data: myConfig,
            height: '100%',
            width: '100%'
        });
    }

    let daterange_btn = $('#daterange-btn');
    //Date range as a button
    daterange_btn.daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#daterange-btn span').html(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            points_table.ajax.url( base_url + '?start_date=' + start.format('YYYY-MM-DD') +
                '&end_date=' + end.format('YYYY-MM-DD') ).load();
        }
    );

    // for now, not used
    $(document).on('click', '.redeem-row', function(e){
        e.preventDefault();
        let points_field = $('#redeem_points_field');
        $('#redeem_points_modal').modal('show');
        $('#row-id').val($(this).data('id'));
        points_field.val($(this).data('orig-value'));
        points_field.attr('max', $(this).data('orig-value'));
        points_field.prop('disabled', true);
    });
    // Process Redeem
    $(document).on('click', 'button#btn-redeem', function () {
        let points_input = $('#redeem_points_field');
        let points =  Number.parseInt(points_input.val());
        let max_points = Number.parseInt(points_input.prop('max'));
        if(isNaN(points) || points <= 0) {
            toastr['warning']('Please input valid amount.');
            return;
        }
        if(points > max_points ) {
            toastr['warning']('Maximum points is ' + max_points);
            return;
        }
        $('#redeem_points_modal').modal('hide');
        $.ajax({
            url: '/points/request-redeem/' + points,
            method: 'get',
            data: {'business_location': $('#business_location').val()},
            success: function (result) {
                if(result.status === 'success') {
                    points_input.attr('max', max_points - points);
                    points_input.val();
                    $('.current-points').html(max_points - points);
                    getBusinessPoints();
                    points_table.ajax.url( base_url + '?business_location=' + $('#business_location').val() ).load();
                }
                toastr[result.status](result.message);
            }
        });
    });
});