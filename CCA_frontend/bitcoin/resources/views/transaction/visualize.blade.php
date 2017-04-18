@extends('layout')
@section('content')
    <script type="text/javascript" src="/js/vis.js"></script>
    <div class="page-header">
        <h1>Transaction visualization<br/><small>{{$transaction->getTxid()}}</small></h1>
    </div>

    <div style="height:500px; width:100%;">
        <h3>Ctrl+click on node/edge to go to detail about transaction/address</h3>
        <div id="legend_div" style="height:100%; float:left; width:10%;"></div>
        <div id="mynetwork" style="height:100%; width:90%; float:right;"></div>
    </div>
    <script type="text/javascript">
        var nodes = null;
        var edges = null;
        var network = null;
        var legend_network=null;
        var currency = "{{\App\Model\CurrencyType::currencyUnit($currency)}}";

        var rootTxid = "{{$transaction->getTxid()}}";

        var transactions = {};
        var payments = [];
        var unspend_outputs = [];

        var transaction_id_map=[];
        var edge_id_map=[];

        var existing_edges = [];

        var transaction_node_id=100000;
        var edge_id=1000;
        var unspend_id=10;

        @foreach($graph->getTransactions() as $transaction)
            transactions["{{$transaction->getTxid()}}"]={
                node_id: transaction_node_id++,
                txid: "{{$transaction->getTxid()}}",
                coinbase: "{{$transaction->isCoinbase()}}",
                expanded_relations: false,
                unspend_outputs: false
            };
        @endforeach

        @foreach($graph->getPayments() as $payment)
            payments.push(
            {
                edge_id: edge_id++,
                address: "{{$payment->getAddress()}}",
                pays_from: "{{$payment->getPaysFrom()}}",
                pays_to: "{{$payment->getPaysTo()}}",
                value: "{{$payment->getValue()}}",
                vout: "{{$payment->getVout()}}"
            }
        );
        @endforeach

        @foreach($unspend_outputs as $output)
            unspend_outputs.push({
                node_id: unspend_id++,
                txid: "{{$output->getTransactionTxid()}}",
                address: "{{$output->getAddress()}}",
                value: "{{$output->getValue()}}"
        });
        @endforeach

        // Called when the Visualization API is loaded.
        function draw() {
            // Create a data table with nodes.
            nodes = new vis.DataSet();

            // Create a data table with links.
            edges = new vis.DataSet();

            for (var trans_key in transactions)
            {
                var obj = transactions[trans_key];
                add_node(obj);
            }

            payments.forEach(function(payment){
                add_payment(payment);
            });

            unspend_outputs.forEach(function(output){
               add_unspend_output(output);
            });

            // create a network
            var container = document.getElementById('mynetwork');
            var data = {
                nodes: nodes,
                edges: edges
            };
            var options = {
                width: "100%",
                height: "100%",
                nodes: {
                    scaling: {
                        min: 16,
                        max: 32
                    },
                    font: {
                        size:10
                    }
                },
                edges: {
                    color: "gray",
                    smooth: true,
                    arrows: "to",
                    length: 200,
                    font: {
                        size:10
                    }
                },
                physics:{
                    barnesHut:{gravitationalConstant:-30000},
                    stabilization: {iterations:2500}
                },

                groups: {
                    coinbaseTransaction: {
                        shape: 'dot',
                        color: "#00dd00",
                        size: 10
                    },

                    rootTransaction: {
                        shape: 'dot',
                        color: "#2b7ce9",
                        size: 30
                    },

                    transaction: {
                        shape: 'dot',
                        color: "#ff9900",
                        size: 20
                    },

                    expandedTransaction: {
                        shape: 'dot',
                        color: '#44ccbb',
                        size: 20
                    },

                    unspendOutput:{
                        shape: "square",
                        color: "#bb00bb",
                        size: 8
                    }
                }
            };
            network = new vis.Network(container, data, options);

            // legend
            var legend_div = document.getElementById('legend_div');
            var y = -200;
            var step = 70;

            var legend_nodes = new vis.DataSet();
            legend_nodes.add({id: 1, x: 0, y: y, label: 'Root transaction', group: 'rootTransaction', fixed: true,  physics:false});
            legend_nodes.add({id: 2, x: 0, y: y + step, label: 'Coinbase transaction', group: 'coinbaseTransaction', fixed: true,  physics:false});
            legend_nodes.add({id: 3, x: 0, y: y + 2*step, label: 'Normal transaction', group: 'transaction', fixed: true,  physics:false});
            legend_nodes.add({id: 4, x: 0, y: y + 3*step, label: 'Expanded transaction', group: 'expandedTransaction', fixed: true,  physics:false});
            legend_nodes.add({id: 5, x: 0, y: y + 4*step, label: 'Unspend output', group: 'unspendOutput', fixed: true,  physics:false});

            var legend_data = {
                nodes: legend_nodes,
                edges: new vis.DataSet()
            };

            legend_network = new vis.Network(legend_div, legend_data, options);

            network.on( 'click', function(properties){
                var clickedNodes = nodes.get(properties.nodes);
                var clickedEdges = edges.get(properties.edges);


                if (properties.event.pointers[0].ctrlKey)
                {
                    if (clickedNodes.length == 1){
                        window.open("/{{$currency}}/transaction/"+transaction_id_map[clickedNodes[0].id].txid);
                    }

                    else if (clickedEdges.length == 1){
                        window.open("/{{$currency}}/address/"+edge_id_map[clickedEdges[0].id].address);
                    }
                }
                else
                {
                    if (clickedNodes.length == 1)
                    {
                        expand_node(transaction_id_map[clickedNodes[0].id])
                    }
                }
            })
        }

        function add_node(node)
        {
            var group = null;

            if (node.txid == rootTxid){
                node.expanded_relations=true;
                group = "rootTransaction";
            } else if (node.coinbase){
                group = "coinbaseTransaction"
            } else {
                group = "transaction";
            }
            transaction_id_map[node.node_id]=node;
            nodes.add({id: node.node_id, label: node.txid, group: group});
        }

        function add_payment(payment)
        {
            edge_id_map[payment.edge_id]=payment;
            existing_edges.push(payment.pays_from +"-" + payment.vout + "-" + payment.pays_to);

            edges.add({id: payment.edge_id, from: transactions[payment.pays_from].node_id, to: transactions[payment.pays_to].node_id, label: payment.address + " (" + payment.value + " " +  currency + ")"});
        }

        function add_unspend_output(output)
        {
            transactions[output.txid].unspend_outputs=true;
            nodes.add({id: output.node_id, group: "unspendOutput"});

            edges.add({
                from:  transactions[output.txid].node_id,
                to: output.node_id,
                label: output.address + " (" + output.value + " " + currency + ")",
                length:50
            });
        }

        function expand_node(node)
        {
            if (!node.expanded_relations){
                node.expanded_relations=true;

                var display_node=nodes.get(node.node_id);
                if (display_node.group == "transaction") {
                    display_node.group = "expandedTransaction";
                    nodes.update(display_node);
                }

                $.get("/{{$currency}}/transaction/"+node.txid+"/relations",function (data){
                    add_new_data(JSON.parse(data));
                });
            }
        }

        function add_new_data(data)
        {
            var key;
            for (key in data.transactions){
                var entry = data.transactions[key];
                if (!transactions.hasOwnProperty(entry.txid)) {
                    var new_transaction=transactions[entry.txid] = {
                        node_id: transaction_node_id++,
                        txid: entry.txid,
                        coinbase: entry.coinbase,
                        expanded_relations: false,
                        unspend_outputs: false
                    };
                    add_node(new_transaction);
                }
            }

            for (key in data.payments)
            {
                var payment= data.payments[key];
                if ($.inArray(payment.pays_from + "-" + payment.vout + "-" + payment.pays_to,existing_edges) == -1) {
                    var new_payment = {
                        edge_id: edge_id++,
                        address: payment.address,
                        pays_from: payment.pays_from,
                        pays_to: payment.pays_to,
                        value: payment.value,
                        vout: payment.vout
                    };
                    payments.push(new_payment);
                    add_payment(payment);
                }
            }

            for (key in data.unused_outputs)
            {
                var output= data.unused_outputs[key];
                if (!transactions[output.txid].unspend_outputs)
                {
                    transactions[output.txid].unspend_outputs=true;
                    var new_uspend_output={
                        node_id: unspend_id++,
                        txid: output.txid,
                        address: output.address,
                        value: output.value
                    };
                    unspend_outputs.push(new_uspend_output);
                    add_unspend_output(new_uspend_output);
                }
            }
        }

        $("body").ready(function(){
            draw();
        })
    </script>

@stop