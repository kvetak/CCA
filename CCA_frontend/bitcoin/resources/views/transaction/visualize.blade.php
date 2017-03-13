@extends('layout')
@section('content')
    <script src="https://d3js.org/d3.v3.min.js" charset="utf-8"></script>
    <div class="page-header">
        <h1>Transaction visualization<br/><small>{{$transaction->getTxid()}}</small></h1>
    </div>
    <style type="text/css">
        .node circle {
            fill: #fff;
            stroke: steelblue;
            stroke-width: 2px;
        }

        .node {
            font: 10px arial;
            font-weight: bold;
        }

        .link {
            fill: none;
            stroke: #ccc;
            stroke-width: 1.5px;
        }

        .value {
            font-weight:bold;
            fill:green;
        }
    </style>
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Legend</h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-1">
                            <svg height="30" width="30"><circle cx="15" cy="15" r="8" stroke="steelblue" stroke-width="2" fill="lightsteelblue" /></svg>
                        </div>
                        <div style="line-height: 30px; vertical-align: middle;">
                            Output is unspent
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-1">
                            <svg height="30" width="30"><circle cx="15" cy="15" r="8" stroke="steelblue" stroke-width="2" fill="orange" /></svg>
                        </div>
                        <div style="line-height: 30px; vertical-align: middle;">
                            Output is spent - Click to show related transaction outputs
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" style="overflow-x: scroll;">
            <svg id="chart"></svg>
        </div>
    </div>

    <script type="text/javascript">
        /**
         * Vymazanie obsahu platna.
         */
        function init() {
            $('#chart').empty();
        }
        $(document).ready(function() {
            /**
             * Vypocet polomeru kruhu na zaklade ciastky.
             **/
            var radius = function(d) {
                return Math.min(25, Math.max(20 * (d.value / root.value), 5.0));
            };
            var currency = "{{$currency}}";
            var currencyUnit = "{{\App\Model\CurrencyType::currencyUnit($currency)}}";
            var depth ;
            var nodeCount;
            /**
             * Objekt grafu.
             **/
            var chart = $('#chart');
            /**
             * ID transakcie kde je pociatok grafu.
             */
            var rootTxId =  '{{$transaction->getTxid()}}';
            /**
             * Mapovanie uzlov grafu.
             * @type {Array}
             */
            var nodeMap = [];
            var root,
                w = 100,
                h = 10,
                i = 0,
                duration = 200;

            var tree = d3.layout.tree().size([h, w]);

            var diagonal = d3.svg.diagonal().projection(function(d) { return [d.y, d.x]; });

            var vis;

            var getRoot = function() {

                chart.empty();
                nodeCount = 0;
                depth = 1;

                vis = d3.select("#chart").append("svg:g").attr("transform", "translate(50, 0)");

                d3.json('/'+currency+'/transaction/' + rootTxId + '/outputs', function(json) {
                    nodeCount = json.children.length;
                    nodeMap[json.name] = json;

                    for (var i =0; i < json.children.length; ++i) {
                        var child = json.children[i];

                        child.children = [];

                        nodeMap[child.name] = child;
                    }

                    update(root = json);
                });
            };
            getRoot();

            function update(source) {
                /**
                 * Upravy vysky a hlbky grafu.
                 **/
                chart.width((250*depth)+w);
                chart.height(Math.max(window.innerHeight-350, h+(nodeCount*40)));

                var nodes = d3.layout.tree().size([chart.height(), chart.width()-160]).nodes(root).reverse();

                /**
                 * Aktualizacia uzlov.
                 **/
                var node = vis.selectAll("g.node").data(nodes, function(d) { return d.id || (d.id = ++i); });

                var nodeEnter = node.enter().append("svg:g").attr("class", "node").attr("transform", function(d) { return "translate(" + source.y + "," + source.x + ")"; });

                var func = function(nodeEnter) {
                    nodeEnter.append("svg:circle")
                            .attr("r", function(d) {
                                return radius(d);
                            }).style("fill", function(d) { return (d.redeemed_tx == null || d.redeemed_tx.length == 0) ? "lightsteelblue" : "#fff"; }).on("click", function(d) {
                        click(d, nodeEnter);
                    });

                    //Bitcoin Address
                    nodeEnter.append("svg:a").attr('xlink:href', function(d){
                        if(d.name == 'source'){
                            return;
                        }
                        return '{{getenv('BASE_URL')}}'+'/'+currency+'/address/'+d.name;
                    }).attr('target', '_blank').append("svg:text").attr("x", function(d) {
                        if (d.name == null) return 0;
                        return -(3 * d.name.length);
                    }).attr("y", function (d) {
                        return 13 + radius(d);
                    }).text(function(d) {
                        return d.name;
                    });

                    //Tag
                    nodeEnter.append("svg:a").attr('xlink:href', function(d){
                        if(d.url_tag != undefined && d.url_tag.length > 0){
                            return d.url_tag;
                        }
                    }).attr('target', '_blank').append("svg:text").attr("x", function(d) {
                        if (d.tag == null){
                            return 0;
                        }
                        return -(3 * d.tag.length);
                    }).attr("y", function (d) {
                        return 30 + radius(d);
                    }).text(function(d) {
                        if(d.tag != undefined && d.tag.length > 0){
                            return "Tag: " + d.tag;
                        }
                    });
                }(nodeEnter);

                /**
                 * Mnozstvo fin prostredkov presuvanych na adresu.
                 */
                nodeEnter.append("svg:text").attr("x", function(d) {
                    return radius(d) + 6;
                }).attr("y", function (d) {
                    return 4;
                }).text(function(d) {
                    return d.value + ' ' + currencyUnit;
                }).attr("class", "value");

                /**
                 * Presuny uzlov n nove pozicie.
                 */
                nodeEnter.transition()
                        .duration(duration)
                        .attr("transform", function(d) {
                            return "translate(" + d.y + "," + d.x + ")";
                        }).style("opacity", 1).select("circle").style("fill", function(d) {
                    return (d.redeemed_tx != null && d.redeemed_tx.length > 0) ? "orange" : "lightsteelblue";
                });

                node.transition()
                        .duration(duration)
                        .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; })
                        .style("opacity", 1);

                node.exit().transition()
                        .duration(duration)
                        .attr("transform", function(d) { return "translate(" + source.y + "," + (source.x + 500) + ")"; })
                        .style("opacity", 1e-6)
                        .remove();

                /**
                 * Aktualizacia hran medzi uzlami.
                 */
                var link = vis.selectAll("path.link")
                        .data(tree.links(nodes), function(d) { return d.target.id; });


                link.enter().insert("svg:path", "g")
                        .attr("class", "link")
                        .attr("d", function(d) {
                            var o = {x: source.x, y: source.y};
                            return diagonal({source: o, target: o});
                        })
                        .transition()
                        .duration(duration)
                        .attr("d", diagonal);


                link.transition()
                        .duration(duration)
                        .attr("d", diagonal);


                node.selectAll("circle").style("fill", function(d) {
                    return (d.redeemed_tx == null || d.redeemed_tx.length == 0) ? "lightsteelblue" : "orange";
                }).attr("r", function(d) {
                    return radius(d);
                });

                node.selectAll(".value").text(function(d) {
                    return d.value + ' ' + currencyUnit;
                });

            }

            var recursive = function(node, func) {
                func(node);
                if (node.children == null)
                    return;

                for (var i = 0; i < node.children.length; ++i) {
                    recursive(node.children[i], func);
                }
            };


            function click(node, nodeEnter) {
                if (node.redeemed_tx == null || node.redeemed_tx.length == 0) {
                    update(node);
                    return;
                }
                var tmp = node.redeemed_tx;
                node.redeemed_tx = [];
                for (var ti = 0; ti < tmp.length; ++ti) {
                    d3.json('/' + currency + '/transaction/' + tmp[ti] + '/outputs', function(json) {
                        if (node.rendered == null) {
                            if (node.depth == depth)
                                ++depth;
                            node.rendered = true;
                        }
                        node.children = json.children;
                        nodeCount += node.children.length;
                        update(node);
                    });
                }
            }
        });


    </script>

@stop