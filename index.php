<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <script type="text/javascript" src="node_modules/jquery/dist/jquery.js"></script>
</head>
<body>
    <p>Press start button for running script</p>
    <button type="button" value="start">Start</button>
    <p>Число транзакций: </p>
    <p id="counter"></p>
    <p>Число транзакций: </p>
    <p id="lastTx"></p>
    <p>Хеш транзакций: </p>
    <p id="hash"></p>
    <script type="application/ecmascript">
        /**
         * JQuery script
         */
        class Start{

             constructor() {}
            
             post(input, id) {
                $.post("Main.php", {
                    type: "text",
                    data: input,
                    timeout: 0 // ms
                }, function (data, status) {
                    if(status) {
                        console.log("Data: " + data + "\nStatus: " + status)
                        $(id).html(data);
                    }
                });
            }

            getHash(tx) {
                 console.log("tx: " + tx)
                this.post
                ({
                    count: tx
                }, '#hash')
                return this.d;
            }


        }

        $(document).ready(function () {

            let app = new Start();
            let isExecuted = false;

            function countBalances(newstep, lasthash) {
                console.log("Start" + newstep);

                if (newstep === null) {

                    countBalances(0, "")
                }
                else {

                    console.log("Not equal :" + Number(Number(newstep) + Number(1)))
                    if (Number(Number(newstep) + Number(1)) >= 700) throw new Error("more");
                    let ltd = app.getHash(Number(Number(newstep) + Number(1)));
                    $("#counter").html(Number(Number(newstep) + Number(1)))
                    let checkBNV2 = setInterval(function () {
                        console.log($("#hash").html())
                        ltd = $("#hash").html();
                        if ($("#hash").html() === "") {
                            console.log("Counter wait")
                        } else {
                            console.log("New hash: " + ltd)
                            clearInterval(checkBNV2)

                            $("#hash").empty()
                            setTimeout(function () {
                                if(isExecuted === false) return; // stop init
                                countBalances(Number(Number(newstep) + Number(1)), ltd)
                            }, 150)

                        }
                    }, 100)
                }
            }

            $("button[value='start']").on("click", function (e, error) {
                if(!error) {
                    if(e.target.value === "start") {
                        console.log(e.target.innerText = "pause")
                        console.log(e.target.value = "pause")
                        if(isExecuted === false) {
                            isExecuted = true;
                            if($("#counter").text() === "") {
                                countBalances(null, null) // run condition
                            } else if ($("#counter").text() > 0) {
                                countBalances($("#counter").text(), null)
                            }
                        }
                    } else {
                        console.log(e.target.innerText = "start")
                        console.log(e.target.value = "start")
                        if(isExecuted === true) {
                            isExecuted = false;
                        }
                    }

                } else {
                    console.log(error)
                }
            });

        })
    </script>
</body>
</html>