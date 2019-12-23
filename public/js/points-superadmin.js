$(document).ready(function () {
    let points_table_dom = $('#points_table');
    let base_url = points_table_dom.data('url');

    let columns, targets = [];
    if (base_url.endsWith('/points')) {
        getBusinessesPoints();

        columns = [
            { data: 'business_name', name: 'business_name'  },
            { data: 'owner_name', name: 'owner_name'},
            { data: 'total_points', name: 'total_points'},
            { data: 'remained_points', name: 'remained_points'},
            { data: 'requested_points', name: 'requested_points'},
            { data: 'approved_points', name: 'approved_points'},
            { data: 'rejected_points', name: 'rejected_points'}
        ];
        targets = [];
    } else if (base_url.endsWith('/requested')){
        columns = [
            { data: 'date', name: 'date'},
            { data: 'business_name', name: 'business_name'  },
            { data: 'owner_name', name: 'owner_name'},
            { data: 'points', name: 'points'},
            { data: 'action', name: 'action'}
        ];
        targets = [4];
    } else {
        columns = [
            { data: 'date', name: 'date'},
            { data: 'business_name', name: 'business_name'  },
            { data: 'owner_name', name: 'owner_name'},
            { data: 'points', name: 'points'}
        ];
        targets = [];
    }

    //Points table
    points_table = points_table_dom.DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        ajax: base_url,
        columnDefs: [ {
            "targets": targets,
            "orderable": false,
            "searchable": false
        } ],
        columns: columns,
    });

    function draw_business_points_chart(chartLabels, chartColors, chartData) {
        new Chart(document.getElementById("pie-chart"), {
            type: 'pie',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: "",
                    backgroundColor: chartColors,
                    data: chartData
                }]
            },
            options: {
                title: {
                    display: true,
                    text: 'Remained Bullets of businesses'
                }
            }
        });
    }

    function getBusinessesPoints() {
        $.ajax({
            url: '/superadmin/points/businesses_points',
            method: 'get',
            data: {},
            success: function (response) {
                let chartLabels = [];
                let chartColors = [];
                let chartData = [];
                let chartLabel = '';
                let chartColor = '';
                let chartDatum = '';
                let color_model = [
                    '#FF5722',
                    '#FFA726',
                    '#D84315',
                    '#00E676',
                    '#0091EA',
                    '#651FFF',
                    '#0288D1',
                    '#512DA8',
                    '#b71c1c',
                    '#DD2C00',
                    '#4CAF50',
                    '#3E2723',
                    '#03A9F4',
                    '#FF5722',
                    '#CDDC39',
                    '#CDDC39'
                ];
                for (let i = 0; i < response.length; i++) {
                    chartLabels[i] = response[i].name;
                    chartData[i] = response[i].points;
                    chartColors[i] = color_model[i % 16];
                }
                draw_business_points_chart(chartLabels, chartColors, chartData);
            }
        });
    }


    //Show approve modal
    $(document).on('click', '.approve_redeem', function () {
        let redeem_id = $(this).data('id');
        let points = $(this).data('points');
        let business_name = $(this).data('name');

        $('.modal-business-name').html(business_name);
        $('.modal-redeem-points').html(points);
        $('.button-redeem').data('id', redeem_id);

        $('#approve_redeem_modal').modal('show');
    });

    //Approve/Reject points
    $(document).on('click', '.button-redeem', function () {
        let redeem_id = $(this).data('id');
        let redeem_mode = $(this).data('method');
        if (redeem_mode === 'cancel') {
            $('#approve_redeem_modal').modal('hide');
            return;
        }
        let redeem_row = 'tr[data-id=' + redeem_id + ']';
        let url = redeem_mode === 'approve' ? '/superadmin/approve-redeem/' : '/superadmin/reject-redeem/';
        $.ajax({
            url: url + redeem_id,
            method: 'get',
            data:{},
            success: function (result) {
                toastr[result.status](result.message);
                $(redeem_row).remove();
            },
            complete: function () {
                $('#approve_redeem_modal').modal('hide');
            }
        });
    });
});