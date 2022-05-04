<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1'>

    <title>Nayra Engine</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.5.1/chart.min.js"
        integrity="sha512-Wt1bJGtlnMtGP0dqNFH1xlkLBNpEodaiQ8ZN5JLA5wpc1sUlk/O5uuOMNgvzddzkpvZ9GLyYNa8w2s7rqiTk5Q=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <style>
        body {
            font-family: 'Courier Prime', 'Courier New', Courier, monospace;
        }

        .row {
            display: flex;
        }

        .panel {
            padding: 10px;
            margin: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div>Welcome to ProcessMaker/Nayra-Engine</div>
    <div class="row">
        <div class="panel">
            <b>Deployed</b>
            <?php
                foreach (glob('bpmn/*.bpmn', GLOB_NOSORT) as $file) {
                    echo '<div><a href="javascript:callProcess(' . htmlentities(json_encode(basename($file))) . ')">' . $file . '</a></div>';
                }
            ?>
        </div>
        <div class="panel">
            <div style="width:35vw">
                <canvas id="canvas"></canvas>
            </div>
        </div>
        <div class="panel">
            <div style="width:35vw">
                <canvas id="canvas1"></canvas>
            </div>
        </div>
    </div>

    <script>
        // chart 1
        const data = {
            labels: [],
            datasets: [{
                label: 'Tasks completed',
                data: [],
                borderColor: '#00e261',
                backgroundColor: '#00e26155',
                fill: true,
            }]
        };
        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                animation: false,
                plugins: {},
                scales: {
                    y: {
                        min: 0,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            },
        };
        const chart = new Chart(canvas, config);
        // pie segments colors
        const colors = [
            'lightblue',
            'lightgreen',
            'lightpink',
            'lightseagreen',
            'lightgray',
            'lightcoral',
            'lightgoldenrodyellow',
        ];
        // chart 2
        const data1 = {
            labels: [],
            datasets: [{
                label: 'Task completed',
                data: [],
                backgroundColor: colors,
                fill: true,
            }]
        };
        const config1 = {
            type: 'pie',
            data: data1,
            options: {
                responsive: true,
                animation: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Task execution times in seconds'
                    }
                },
                pieceLabel: {
                    fontColor: '#000'
                }
            },
        };
        const chart1 = new Chart(canvas1, config1);
        setInterval(() => {
            fetch('/monitor?metrics=labels,completed,task_ids,task_xtimes').then(response => response.json())
            .then(
                json => {
                    data.labels = json.labels;
                    data.datasets[0].data = json.completed;
                    chart.update();
                    data1.labels = json.task_ids;
                    data1.datasets[0].data = json.task_xtimes;
                    chart1.update();
                }
            );
        }, 1000);
        // POST actions to START A PROCESS
        function callProcess(bpmn) {
            fetch('/actions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: "START_PROCESS",
                    params: {
                        process_id: "PROCESS_1"
                    },
                    bpmn: bpmn
                })
            });
        }
    </script>
</body>

</html>