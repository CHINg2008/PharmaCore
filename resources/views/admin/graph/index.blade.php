@extends('admin.layouts.app')

@push('page-css')
<style>
    #mynetwork {
        width: 100%;
        height: 600px;
        border: 1px solid lightgray;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
</style>
<script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
@endpush

@push('page-header')
<div class="col-sm-12">
    <h3 class="page-title">Neo4j Graph Database Visualization</h3>
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Graph Analytics</li>
    </ul>
</div>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Pharmacy Supply Chain Network</h4>
                <p class="text-muted mb-3">This interactive node-graph visualizes the database relationships exactly as they are modeled in a Neo4j Graph Database. You can drag nodes around to explore the supply chain from Suppliers to Products, Categories, and recent Sales.</p>
                
                <div class="d-flex align-items-center flex-wrap" style="gap: 15px;">
                    <strong class="mr-2">Legend:</strong>
                    <div class="d-flex align-items-center">
                        <span style="display:inline-block; width:16px; height:16px; background-color:#99ccff; border:2px solid #0033cc; margin-right:5px; border-radius:2px;"></span> 
                        Supplier (Box)
                    </div>
                    <div class="d-flex align-items-center">
                        <span style="display:inline-block; width:0; height:0; border-left:8px solid transparent; border-right:8px solid transparent; border-bottom:16px solid #ff9999; margin-right:5px;"></span> 
                        Category (Triangle)
                    </div>
                    <div class="d-flex align-items-center">
                        <span style="display:inline-block; width:16px; height:16px; background-color:#99ff99; border:2px solid #009900; margin-right:5px; border-radius:50%;"></span> 
                        Product (Circle)
                    </div>
                    <div class="d-flex align-items-center">
                        <span style="display:inline-block; font-size:18px; color:#cccc00; margin-right:5px;">⭐</span> 
                        Sale (Star)
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="mynetwork"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('page-js')
<script type="text/javascript">
    // create an array with nodes
    var nodes = new vis.DataSet(@json($nodes));

    // create an array with edges
    var edges = new vis.DataSet(@json($edges));

    // create a network
    var container = document.getElementById('mynetwork');

    var data = {
        nodes: nodes,
        edges: edges
    };
    var options = {
        nodes: {
            shape: 'dot',
            size: 20,
            font: {
                size: 14,
                color: '#333'
            },
            borderWidth: 2
        },
        edges: {
            width: 2,
            font: {
                size: 12,
                align: 'middle'
            },
            arrows: {
                to: {enabled: true, scaleFactor:0.5}
            }
        },
        groups: {
            category: {
                color: {background:'#ff9999', border:'#cc0000'},
                shape: 'triangle'
            },
            supplier: {
                color: {background:'#99ccff', border:'#0033cc'},
                shape: 'box'
            },
            product: {
                color: {background:'#99ff99', border:'#009900'}
            },
            sale: {
                color: {background:'#ffff99', border:'#cccc00'},
                shape: 'star'
            }
        },
        physics: {
            forceAtlas2Based: {
                gravitationalConstant: -50,
                centralGravity: 0.01,
                springLength: 100,
                springConstant: 0.08
            },
            maxVelocity: 50,
            solver: 'forceAtlas2Based',
            timestep: 0.35,
            stabilization: {iterations: 150}
        }
    };
    var network = new vis.Network(container, data, options);
</script>
@endpush
