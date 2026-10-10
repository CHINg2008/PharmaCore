<?php
$req = new \Illuminate\Http\Request();
$req->merge(['from_date' => '2020-01-01', 'to_date' => '2030-01-01']);
$c = new \App\Http\Controllers\Admin\SaleController();
$res = $c->generateReport($req);
echo count($res->getData()['sales']);
