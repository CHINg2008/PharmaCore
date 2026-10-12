@extends('admin.layouts.app')

@push('page-css')
<style>
    .graph-wrapper {
        position: relative;
        width: 100%;
        height: 680px;
        background: #0f172a;
        border-radius: 12px;
        border: 1px solid #1e293b;
        overflow: hidden;
        box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);
    }

    #mynetwork {
        width: 100%;
        height: 100%;
    }

    .graph-toolbar {
        background: #1e293b;
        border-radius: 10px;
        padding: 10px 16px;
        border: 1px solid #334155;
    }

    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        user-select: none;
    }

    .stat-pill:hover {
        transform: translateY(-2px);
    }

    .stat-pill.active {
        box-shadow: 0 0 10px rgba(255,255,255,0.3);
        border-color: #fff;
    }

    .pill-all { background: #334155; color: #f8fafc; }
    .pill-sup { background: rgba(2, 132, 199, 0.2); color: #38bdf8; border-color: #0284c7; }
    .pill-prod { background: rgba(16, 185, 129, 0.2); color: #34d399; border-color: #10b981; }
    .pill-cat { background: rgba(147, 51, 234, 0.2); color: #c084fc; border-color: #9333ea; }
    .pill-sale { background: rgba(236, 72, 153, 0.2); color: #f472b6; border-color: #ec4899; }
    .pill-expired { background: rgba(239, 68, 68, 0.2); color: #f87171; border-color: #ef4444; }

    /* Node Inspector Panel */
    .inspector-panel {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 20px;
        color: #f8fafc;
        height: 680px;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }

    .inspector-prop-table {
        width: 100%;
        font-size: 0.84rem;
        margin-top: 10px;
    }

    .inspector-prop-table td {
        padding: 6px 8px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }

    .inspector-prop-table td:first-child {
        color: #94a3b8;
        font-weight: 600;
        width: 40%;
    }

    .inspector-prop-table td:last-child {
        color: #f1f5f9;
        font-family: monospace;
    }

    .neighbor-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        margin: 3px;
        border-radius: 6px;
        font-size: 0.78rem;
        background: #0f172a;
        border: 1px solid #334155;
        color: #e2e8f0;
        cursor: pointer;
        transition: all 0.15s;
    }

    .neighbor-pill:hover {
        background: #38bdf8;
        color: #0f172a;
        border-color: #38bdf8;
    }

    .cypher-box {
        background: #0b1120;
        border: 1px solid #334155;
        border-radius: 8px;
        padding: 10px;
        font-family: 'JetBrains Mono', Consolas, monospace;
        font-size: 0.78rem;
        color: #38bdf8;
        line-height: 1.5;
        overflow-x: auto;
        white-space: pre-wrap;
    }
</style>
<script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
@endpush

@push('page-header')
<div class="col-sm-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 15px;">
        <div>
            <h3 class="page-title text-primary"><i class="fe fe-activity mr-2"></i>Neo4j Interactive Graph Analytics</h3>
            <ul class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Neo4j Graph Topology</li>
            </ul>
        </div>
        <div class="d-flex align-items-center" style="gap: 10px;">
            <button class="btn btn-outline-primary btn-sm" onclick="toggleLayout()" id="btn-layout">
                <i class="fe fe-git-commit mr-1"></i> Switch to Hierarchical Flow
            </button>
            <button class="btn btn-outline-warning btn-sm" onclick="togglePhysics()" id="btn-physics">
                <i class="fe fe-pause mr-1"></i> Freeze Physics
            </button>
            <button class="btn btn-outline-success btn-sm" onclick="resetZoom()">
                <i class="fe fe-maximize-2 mr-1"></i> Fit / Center Graph
            </button>
            <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#cypherModal">
                <i class="fe fe-code mr-1"></i> Cypher Simulator
            </button>
        </div>
    </div>
</div>
@endpush

@section('content')
<!-- Metric Summary Row -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="graph-toolbar d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
            <!-- Filter Pills -->
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <span class="text-muted mr-1" style="font-size: 0.85rem;"><i class="fe fe-filter mr-1"></i>Filter Entities:</span>
                <span class="stat-pill pill-all active" onclick="filterGraph('all')">
                    All Nodes ({{ $stats['total_nodes'] }})
                </span>
                <span class="stat-pill pill-sup" onclick="filterGraph('supplier')">
                    🏭 Suppliers ({{ $stats['suppliers_count'] }})
                </span>
                <span class="stat-pill pill-prod" onclick="filterGraph('product')">
                    💊 Products ({{ $stats['products_count'] }})
                </span>
                <span class="stat-pill pill-cat" onclick="filterGraph('category')">
                    📁 Categories ({{ $stats['categories_count'] }})
                </span>
                <span class="stat-pill pill-sale" onclick="filterGraph('sale')">
                    🧾 Sales ({{ $stats['sales_count'] }})
                </span>
                @if($stats['expired_count'] > 0)
                <span class="stat-pill pill-expired" onclick="filterGraph('expired')">
                    ⚠️ Expired ({{ $stats['expired_count'] }})
                </span>
                @endif
            </div>

            <!-- Instant Search Bar -->
            <div class="d-flex align-items-center" style="min-width: 280px;">
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-dark border-secondary text-muted"><i class="fe fe-search"></i></span>
                    </div>
                    <input type="text" id="graphSearchInput" class="form-control bg-dark text-white border-secondary" placeholder="Search product, supplier..." onkeyup="searchGraph(this.value)">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()"><i class="fe fe-x"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Interactive Workspace -->
<div class="row">
    <!-- Left Graph Canvas -->
    <div class="col-lg-8 col-xl-9 mb-3">
        <div class="graph-wrapper">
            <div id="mynetwork"></div>

            <!-- In-Canvas Quick Legend Overlay -->
            <div style="position: absolute; bottom: 15px; left: 15px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); padding: 8px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); font-size: 0.75rem; color: #cbd5e1; z-index: 10; display: flex; gap: 14px; align-items: center;">
                <span class="d-flex align-items-center"><span style="display:inline-block; width:12px; height:12px; background:#0284c7; border-radius:2px; margin-right:5px;"></span> Supplier</span>
                <span class="d-flex align-items-center"><span style="display:inline-block; width:12px; height:12px; background:#10b981; border-radius:50%; margin-right:5px;"></span> Product</span>
                <span class="d-flex align-items-center"><span style="display:inline-block; width:0; height:0; border-left:6px solid transparent; border-right:6px solid transparent; border-bottom:12px solid #9333ea; margin-right:5px;"></span> Category</span>
                <span class="d-flex align-items-center"><span style="display:inline-block; color:#ec4899; font-size:14px; margin-right:5px;">★</span> Sale</span>
                <span class="d-flex align-items-center"><span style="display:inline-block; width:12px; height:12px; background:#ef4444; border-radius:50%; margin-right:5px;"></span> Expired</span>
            </div>

            <!-- Interactive Mode Hint -->
            <div style="position: absolute; top: 15px; right: 15px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); font-size: 0.75rem; color: #94a3b8; z-index: 10;">
                <i class="fe fe-info mr-1 text-primary"></i> <b>Click any node</b> to highlight neighbors & inspect properties
            </div>
        </div>
    </div>

    <!-- Right Node Inspector & Provenance Tracer -->
    <div class="col-lg-4 col-xl-3 mb-3">
        <div class="inspector-panel" id="inspectorPanel">
            <!-- Default Welcome State -->
            <div id="inspectorEmptyState" class="text-center my-auto py-5">
                <div style="font-size: 3rem; color: #475569; margin-bottom: 15px;">
                    <i class="fe fe-share-2"></i>
                </div>
                <h5 class="text-white font-weight-bold">Interactive Inspector</h5>
                <p class="text-muted" style="font-size: 0.84rem;">
                    Click any Node in the Neo4j graph network to inspect its graph properties, trace upstream suppliers, and view direct relationships.
                </p>
                <div class="p-2 bg-dark rounded border border-secondary text-left mt-3" style="font-size: 0.78rem;">
                    <div class="text-info font-weight-bold mb-1"><i class="fe fe-check mr-1"></i> Features Available:</div>
                    <div class="text-muted">• Real-time 1-Hop Neighborhood Highlighting</div>
                    <div class="text-muted">• One-click Supply Chain Provenance Trace</div>
                    <div class="text-muted">• Native openCypher Query Generation</div>
                </div>
            </div>

            <!-- Active Selected Node Details -->
            <div id="inspectorActiveState" style="display: none;">
                <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom border-secondary">
                    <div>
                        <span id="inspectBadge" class="badge badge-pill badge-primary mb-1">Entity</span>
                        <h5 id="inspectTitle" class="text-white font-weight-bold mb-0">Title</h5>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()" title="Deselect Node">
                        <i class="fe fe-x"></i>
                    </button>
                </div>

                <!-- Provenance Action -->
                <div class="mb-3">
                    <button class="btn btn-primary btn-block btn-sm" id="btnTraceProvenance" onclick="traceSupplyChain()">
                        <i class="fe fe-git-pull-request mr-1"></i> Trace Complete Supply Chain
                    </button>
                </div>

                <!-- Properties Table -->
                <h6 class="text-uppercase text-muted font-weight-bold" style="font-size: 0.72rem; letter-spacing: 0.8px;">
                    Node Properties (Neo4j Storage)
                </h6>
                <table class="inspector-prop-table mb-3" id="inspectPropTable">
                    <!-- Populated dynamically -->
                </table>

                <!-- Connected Neighbors -->
                <h6 class="text-uppercase text-muted font-weight-bold mt-2" style="font-size: 0.72rem; letter-spacing: 0.8px;">
                    Connected Edges (<span id="inspectNeighborCount">0</span>)
                </h6>
                <div id="inspectNeighborsList" class="mb-3" style="max-height: 140px; overflow-y: auto;">
                    <!-- Populated dynamically -->
                </div>

                <!-- Neo4j Cypher Representation -->
                <h6 class="text-uppercase text-muted font-weight-bold mt-2" style="font-size: 0.72rem; letter-spacing: 0.8px;">
                    Equivalent Cypher Query
                </h6>
                <div class="cypher-box" id="inspectCypherBox">
                    <!-- Populated dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Neo4j Cypher Simulator -->
<div class="modal fade" id="cypherModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="background: #0f172a; color: #f8fafc; border: 1px solid #334155;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-weight-bold text-white"><i class="fe fe-terminal text-info mr-2"></i>Neo4j Cypher Query Simulator</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size: 0.85rem;">
                    Select an official Cypher query below to execute a simulated pattern match on the visual canvas. The matched nodes will instantly isolate and highlight.
                </p>

                <div class="list-group list-group-flush mb-3">
                    <div class="list-group-item bg-dark border-secondary p-3 mb-2 rounded" style="cursor: pointer;" onclick="runSimulatedCypher(1)">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-info"><i class="fe fe-play mr-1"></i> Query 1: Full Supply Chain Path</strong>
                            <span class="badge badge-primary">Supplier ➔ Product ➔ Category</span>
                        </div>
                        <div class="cypher-box mt-1">MATCH (s:Supplier)-[:SUPPLIES]->(p:Product)-[:BELONGS_TO]->(c:Category) RETURN s, p, c LIMIT 25</div>
                    </div>

                    <div class="list-group-item bg-dark border-secondary p-3 mb-2 rounded" style="cursor: pointer;" onclick="runSimulatedCypher(2)">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-danger"><i class="fe fe-play mr-1"></i> Query 2: Product Batch Recalls (Expired Medications)</strong>
                            <span class="badge badge-danger">Expired Risk Path</span>
                        </div>
                        <div class="cypher-box mt-1">MATCH (s:Supplier)-[:SUPPLIES]->(p:Product) WHERE p.is_expired = true RETURN s, p</div>
                    </div>

                    <div class="list-group-item bg-dark border-secondary p-3 mb-2 rounded" style="cursor: pointer;" onclick="runSimulatedCypher(3)">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-success"><i class="fe fe-play mr-1"></i> Query 3: Sales Provenance (Dispensed Medications)</strong>
                            <span class="badge badge-success">Sale ➔ Product</span>
                        </div>
                        <div class="cypher-box mt-1">MATCH (sa:Sale)-[:SOLD_PRODUCT]->(p:Product) RETURN sa, p</div>
                    </div>

                    <div class="list-group-item bg-dark border-secondary p-3 mb-2 rounded" style="cursor: pointer;" onclick="runSimulatedCypher(4)">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-warning"><i class="fe fe-play mr-1"></i> Query 4: Depleted Stock Alert</strong>
                            <span class="badge badge-warning">Stock = 0</span>
                        </div>
                        <div class="cypher-box mt-1">MATCH (s:Supplier)-[:SUPPLIES]->(p:Product) WHERE p.quantity = 0 RETURN s, p</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('page-js')
<script type="text/javascript">
    // Raw Master Data
    const rawNodes = @json($nodes);
    const rawEdges = @json($edges);

    let nodesDataSet = new vis.DataSet(rawNodes);
    let edgesDataSet = new vis.DataSet(rawEdges);

    let network = null;
    let selectedNodeId = null;
    let isPhysicsRunning = true;
    let isHierarchical = false;
    let currentFilter = 'all';

    const container = document.getElementById('mynetwork');
    const data = { nodes: nodesDataSet, edges: edgesDataSet };

    // Group Color & Shape Palettes matching Neo4j Bloom
    const groupStyles = {
        category: {
            color: { background: '#9333ea', border: '#7e22ce', highlight: { background: '#a855f7', border: '#ffffff' } },
            shape: 'triangle',
            size: 24,
            font: { color: '#ffffff', face: 'Arial', size: 13, bold: true }
        },
        supplier: {
            color: { background: '#0284c7', border: '#0369a1', highlight: { background: '#38bdf8', border: '#ffffff' } },
            shape: 'box',
            font: { color: '#ffffff', face: 'Arial', size: 12, bold: true },
            margin: 10
        },
        product: {
            color: { background: '#10b981', border: '#059669', highlight: { background: '#34d399', border: '#ffffff' } },
            shape: 'dot',
            size: 20,
            font: { color: '#f8fafc', face: 'Arial', size: 12 }
        },
        product_expired: {
            color: { background: '#ef4444', border: '#b91c1c', highlight: { background: '#f87171', border: '#ffffff' } },
            shape: 'dot',
            size: 22,
            font: { color: '#fca5a5', face: 'Arial', size: 12, bold: true }
        },
        product_outstock: {
            color: { background: '#f59e0b', border: '#b45309', highlight: { background: '#fbbf24', border: '#ffffff' } },
            shape: 'dot',
            size: 20,
            font: { color: '#fde68a', face: 'Arial', size: 12 }
        },
        sale: {
            color: { background: '#ec4899', border: '#be185d', highlight: { background: '#f472b6', border: '#ffffff' } },
            shape: 'star',
            size: 22,
            font: { color: '#ffffff', face: 'Arial', size: 12, bold: true }
        }
    };

    const defaultOptions = {
        nodes: {
            borderWidth: 2,
            shadow: { enabled: true, color: 'rgba(0,0,0,0.4)', size: 8, x: 3, y: 3 }
        },
        edges: {
            width: 2,
            color: { color: '#475569', highlight: '#38bdf8', opacity: 0.8 },
            font: { color: '#94a3b8', size: 11, align: 'middle', background: '#0f172a', strokeWidth: 0 },
            arrows: { to: { enabled: true, scaleFactor: 0.6 } },
            smooth: { type: 'continuous' }
        },
        groups: groupStyles,
        physics: {
            solver: 'forceAtlas2Based',
            forceAtlas2Based: {
                gravitationalConstant: -40,
                centralGravity: 0.015,
                springLength: 85,
                springConstant: 0.08,
                damping: 0.4
            },
            maxVelocity: 40,
            timestep: 0.35,
            stabilization: { iterations: 120 }
        },
        interaction: {
            hover: true,
            tooltipDelay: 100,
            zoomView: true,
            dragView: true
        }
    };

    function initNetwork() {
        network = new vis.Network(container, data, defaultOptions);

        // Click Handler (Select & Highlight Neighborhood)
        network.on('click', function(params) {
            if (params.nodes.length > 0) {
                const nodeId = params.nodes[0];
                selectNodeAndHighlight(nodeId);
            } else {
                clearSelection();
            }
        });

        // Double Click (Direct Camera Focus)
        network.on('doubleClick', function(params) {
            if (params.nodes.length > 0) {
                network.focus(params.nodes[0], { scale: 1.4, animation: { duration: 600, easingFunction: 'easeInOutQuad' } });
            }
        });
    }

    initNetwork();

    // 1. Neighborhood Highlighting
    function selectNodeAndHighlight(nodeId) {
        selectedNodeId = nodeId;
        const node = rawNodes.find(n => n.id === nodeId);
        if (!node) return;

        // Find 1-Hop Connected Neighbors
        const connectedNodes = network.getConnectedNodes(nodeId);
        const allConnected = new Set([nodeId, ...connectedNodes]);
        const connectedEdges = new Set(network.getConnectedEdges(nodeId));

        // Update Opacities
        const updatedNodes = rawNodes.map(n => {
            const isConnected = allConnected.has(n.id);
            return {
                id: n.id,
                opacity: isConnected ? 1.0 : 0.15,
                font: { color: isConnected ? '#ffffff' : 'rgba(255,255,255,0.1)' }
            };
        });

        const updatedEdges = rawEdges.map(e => {
            const isConnected = connectedEdges.has(e.id);
            return {
                id: e.id,
                color: isConnected ? { color: '#38bdf8', opacity: 1.0 } : { color: '#334155', opacity: 0.08 }
            };
        });

        nodesDataSet.update(updatedNodes);
        edgesDataSet.update(updatedEdges);

        // Populate Right Inspector Panel
        showInspector(node, connectedNodes);
    }

    // 2. Clear Selection & Restore Full Opacity
    function clearSelection() {
        selectedNodeId = null;
        const restoredNodes = rawNodes.map(n => ({
            id: n.id,
            opacity: 1.0,
            font: { color: '#f8fafc' }
        }));
        const restoredEdges = rawEdges.map(e => ({
            id: e.id,
            color: { color: '#475569', opacity: 0.8 }
        }));

        nodesDataSet.update(restoredNodes);
        edgesDataSet.update(restoredEdges);

        document.getElementById('inspectorEmptyState').style.display = 'block';
        document.getElementById('inspectorActiveState').style.display = 'none';
    }

    // 3. Populate Inspector Panel
    function showInspector(node, connectedIds) {
        document.getElementById('inspectorEmptyState').style.display = 'none';
        document.getElementById('inspectorActiveState').style.display = 'block';

        const props = node.properties || {};
        const entity = props['Entity'] || node.group.toUpperCase();
        const title = props['Name'] || props['Medicine Name'] || props['Company'] || node.label.replace('\n', ' ');

        document.getElementById('inspectTitle').innerText = title;
        const badge = document.getElementById('inspectBadge');
        badge.innerText = `(:${entity})`;
        badge.className = `badge badge-pill badge-${entity === 'Supplier' ? 'info' : (entity === 'Product' ? 'success' : (entity === 'Category' ? 'purple' : 'warning'))} mb-1`;

        // Properties Table
        const propTable = document.getElementById('inspectPropTable');
        propTable.innerHTML = '';
        for (const [key, val] of Object.entries(props)) {
            propTable.innerHTML += `
                <tr>
                    <td>${key}</td>
                    <td>${val}</td>
                </tr>`;
        }

        // Neighbors List
        const neighborsList = document.getElementById('inspectNeighborsList');
        neighborsList.innerHTML = '';
        document.getElementById('inspectNeighborCount').innerText = connectedIds.length;

        connectedIds.forEach(neighborId => {
            const neighborNode = rawNodes.find(n => n.id === neighborId);
            if (neighborNode) {
                const nTitle = neighborNode.properties ? (neighborNode.properties['Name'] || neighborNode.properties['Medicine Name'] || neighborNode.properties['Company'] || neighborNode.label) : neighborNode.label;
                neighborsList.innerHTML += `
                    <span class="neighbor-pill" onclick="selectNodeAndHighlight(${neighborId})">
                        <i class="fe fe-arrow-right"></i> ${nTitle.split('\n')[0]}
                    </span>`;
            }
        });

        // Equivalent Cypher Generation
        const cypherBox = document.getElementById('inspectCypherBox');
        if (entity === 'Product') {
            cypherBox.innerText = `MATCH (s:Supplier)-[:SUPPLIES]->(p:Product {name: "${props['Medicine Name'] || 'Medicine'}"})-[:BELONGS_TO]->(c:Category)\nRETURN s, p, c;`;
        } else if (entity === 'Supplier') {
            cypherBox.innerText = `MATCH (s:Supplier {company: "${props['Company'] || 'Supplier'}"})-[:SUPPLIES]->(p:Product)\nRETURN s, count(p) AS supplied_batches;`;
        } else if (entity === 'Category') {
            cypherBox.innerText = `MATCH (p:Product)-[:BELONGS_TO]->(c:Category {name: "${props['Name'] || 'Category'}"})\nRETURN c, p;`;
        } else if (entity === 'Sale') {
            cypherBox.innerText = `MATCH (sa:Sale {id: ${props['ID'] || 1}})-[:SOLD_PRODUCT]->(p:Product)<-[:SUPPLIES]-(s:Supplier)\nRETURN sa, p, s;`;
        }
    }

    // 4. Supply Chain Provenance Tracer
    function traceSupplyChain() {
        if (!selectedNodeId) return;
        const node = rawNodes.find(n => n.id === selectedNodeId);
        if (!node) return;

        // Trace up to 2 hops of connected supply chain
        const hop1 = network.getConnectedNodes(selectedNodeId);
        let hop2 = [];
        hop1.forEach(id => {
            const h = network.getConnectedNodes(id);
            hop2 = hop2.concat(h);
        });

        const fullPathNodes = new Set([selectedNodeId, ...hop1, ...hop2]);

        const updatedNodes = rawNodes.map(n => ({
            id: n.id,
            opacity: fullPathNodes.has(n.id) ? 1.0 : 0.1,
            size: fullPathNodes.has(n.id) ? 26 : 14
        }));

        nodesDataSet.update(updatedNodes);
        network.fit({ nodes: Array.from(fullPathNodes), animation: { duration: 700 } });
    }

    // 5. Filter Graph by Entity Group
    function filterGraph(group) {
        currentFilter = group;
        document.querySelectorAll('.stat-pill').forEach(p => p.classList.remove('active'));
        event.target.classList.add('active');

        if (group === 'all') {
            nodesDataSet.clear();
            nodesDataSet.add(rawNodes);
            edgesDataSet.clear();
            edgesDataSet.add(rawEdges);
            resetZoom();
            return;
        }

        let filteredNodes = [];
        if (group === 'expired') {
            filteredNodes = rawNodes.filter(n => n.group === 'product_expired');
        } else if (group === 'product') {
            filteredNodes = rawNodes.filter(n => n.group === 'product' || n.group === 'product_expired' || n.group === 'product_outstock');
        } else {
            filteredNodes = rawNodes.filter(n => n.group === group);
        }

        const filteredNodeIds = new Set(filteredNodes.map(n => n.id));
        const filteredEdges = rawEdges.filter(e => filteredNodeIds.has(e.from) || filteredNodeIds.has(e.to));

        nodesDataSet.clear();
        nodesDataSet.add(filteredNodes);
        edgesDataSet.clear();
        edgesDataSet.add(filteredEdges);
        resetZoom();
    }

    // 6. Search Autocomplete & Focus
    function searchGraph(term) {
        term = term.toLowerCase().trim();
        if (!term) return;

        const match = rawNodes.find(n => {
            const lbl = n.label.toLowerCase();
            const propsStr = JSON.stringify(n.properties || {}).toLowerCase();
            return lbl.includes(term) || propsStr.includes(term);
        });

        if (match) {
            selectNodeAndHighlight(match.id);
            network.focus(match.id, { scale: 1.4, animation: { duration: 600, easingFunction: 'easeInOutQuad' } });
        }
    }

    function clearSearch() {
        document.getElementById('graphSearchInput').value = '';
        clearSelection();
        resetZoom();
    }

    // 7. Toggle Physics
    function togglePhysics() {
        isPhysicsRunning = !isPhysicsRunning;
        network.setOptions({ physics: { enabled: isPhysicsRunning } });
        const btn = document.getElementById('btn-physics');
        if (isPhysicsRunning) {
            btn.innerHTML = '<i class="fe fe-pause mr-1"></i> Freeze Physics';
            btn.className = 'btn btn-outline-warning btn-sm';
        } else {
            btn.innerHTML = '<i class="fe fe-play mr-1"></i> Resume Physics';
            btn.className = 'btn btn-warning btn-sm';
        }
    }

    // 8. Toggle Hierarchical / Tree Flow Layout
    function toggleLayout() {
        isHierarchical = !isHierarchical;
        const btn = document.getElementById('btn-layout');
        if (isHierarchical) {
            network.setOptions({
                layout: { hierarchical: { direction: 'UD', sortMethod: 'directed', levelSeparation: 120 } },
                physics: { enabled: false }
            });
            btn.innerHTML = '<i class="fe fe-cpu mr-1"></i> Switch to Dynamic Network';
            btn.className = 'btn btn-primary btn-sm';
        } else {
            network.setOptions({
                layout: { hierarchical: false },
                physics: defaultOptions.physics
            });
            btn.innerHTML = '<i class="fe fe-git-commit mr-1"></i> Switch to Hierarchical Flow';
            btn.className = 'btn btn-outline-primary btn-sm';
        }
        resetZoom();
    }

    // 9. Reset Zoom / Fit
    function resetZoom() {
        network.fit({ animation: { duration: 600, easingFunction: 'easeInOutQuad' } });
    }

    // 10. Run Simulated Cypher Query from Modal
    function runSimulatedCypher(queryId) {
        $('#cypherModal').modal('hide');
        let targetNodeIds = new Set();

        if (queryId === 1) {
            // Full supply chain
            rawNodes.forEach(n => {
                if (['supplier', 'product', 'category'].includes(n.group)) targetNodeIds.add(n.id);
            });
        } else if (queryId === 2) {
            // Expired path
            const expired = rawNodes.filter(n => n.group === 'product_expired');
            expired.forEach(e => {
                targetNodeIds.add(e.id);
                const conns = network.getConnectedNodes(e.id);
                conns.forEach(c => targetNodeIds.add(c));
            });
        } else if (queryId === 3) {
            // Sales path
            const sales = rawNodes.filter(n => n.group === 'sale');
            sales.forEach(s => {
                targetNodeIds.add(s.id);
                const conns = network.getConnectedNodes(s.id);
                conns.forEach(c => targetNodeIds.add(c));
            });
        } else if (queryId === 4) {
            // Depleted stock
            const depleted = rawNodes.filter(n => n.group === 'product_outstock');
            depleted.forEach(d => {
                targetNodeIds.add(d.id);
                const conns = network.getConnectedNodes(d.id);
                conns.forEach(c => targetNodeIds.add(c));
            });
        }

        const updatedNodes = rawNodes.map(n => ({
            id: n.id,
            opacity: targetNodeIds.has(n.id) ? 1.0 : 0.1,
            size: targetNodeIds.has(n.id) ? 24 : 14
        }));

        nodesDataSet.update(updatedNodes);
        network.fit({ nodes: Array.from(targetNodeIds), animation: { duration: 800 } });
    }
</script>
@endpush
