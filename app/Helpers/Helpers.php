<?php

if(!function_exists('route_is')){
    function route_is($route=null){
        if(Request::routeIs($route)){
            return true;
        }else{
            return false;
        }
    }
}

if(!function_exists('route_is')){
    function route_is($routes=[]){
        foreach($routes as $route){
            if(Request::routeIs($route)){
                return true;
            }else{
                return false;
            }
        }
    }
}

if(!function_exists('notify')){
    function notify($message , $type='success'){
        return array(
            'message'=> $message,
            'alert-type' => $type,
        );
    }
}

if(!function_exists('alert')){
    function alert($message , $type='success'){
        return array(
            'message'=> $message,
            'alert-type' => $type,
        );
    }
}

if(!function_exists('syncInventoryAlertNotifications')){
    function syncInventoryAlertNotifications(){
        if(!auth()->check()){
            return;
        }

        $user = auth()->user();

        // 1. Depleted items (quantity <= 0)
        $outOfStock = \App\Models\Purchase::where('quantity', '<=', 0)->get();
        // 2. Expired items (expiry_date <= today)
        $today = \Carbon\Carbon::today()->toDateString();
        $expired = \App\Models\Purchase::where('expiry_date', '<=', $today)->get();
        // 3. Critically low stock (quantity > 0 and quantity <= 10)
        $criticalLow = \App\Models\Purchase::where('quantity', '>', 0)->where('quantity', '<=', 10)->get();

        $existingNotifs = $user->notifications()->get();
        $existingKeys = [];
        foreach($existingNotifs as $n){
            $data = is_array($n->data) ? $n->data : json_decode($n->data, true);
            $pid = $data['purchase_id'] ?? null;
            $type = $data['alert_type'] ?? null;
            if($pid && $type){
                $existingKeys[$pid . '_' . $type] = true;
            }
        }

        foreach($outOfStock as $item){
            $key = $item->id . '_Out of Stock';
            if(!isset($existingKeys[$key])){
                $user->notify(new \App\Notifications\StockAlertNotification([
                    'product_name' => $item->product,
                    'quantity'     => $item->quantity,
                    'expiry_date'  => $item->expiry_date,
                    'purchase_id'  => $item->id,
                    'alert_type'   => 'Out of Stock',
                    'image'        => $item->image ?? 'default.png',
                ]));
                $existingKeys[$key] = true;
            }
        }

        foreach($expired as $item){
            $key = $item->id . '_Expired Product';
            if(!isset($existingKeys[$key])){
                $user->notify(new \App\Notifications\StockAlertNotification([
                    'product_name' => $item->product,
                    'quantity'     => $item->quantity,
                    'expiry_date'  => $item->expiry_date,
                    'purchase_id'  => $item->id,
                    'alert_type'   => 'Expired Product',
                    'image'        => $item->image ?? 'default.png',
                ]));
                $existingKeys[$key] = true;
            }
        }

        foreach($criticalLow as $item){
            $key = $item->id . '_Low Stock Warning';
            if(!isset($existingKeys[$key])){
                $user->notify(new \App\Notifications\StockAlertNotification([
                    'product_name' => $item->product,
                    'quantity'     => $item->quantity,
                    'expiry_date'  => $item->expiry_date,
                    'purchase_id'  => $item->id,
                    'alert_type'   => 'Low Stock Warning',
                    'image'        => $item->image ?? 'default.png',
                ]));
                $existingKeys[$key] = true;
            }
        }
    }
}
