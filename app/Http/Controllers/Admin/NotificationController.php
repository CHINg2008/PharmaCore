<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request)
    {
        if (auth()->check()) {
            auth()->user()->unreadNotifications->markAsRead();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'count' => 0]);
        }

        $notification = notify('All notifications marked as read');
        return back()->with($notification);
    }

    public function read(Request $request)
    {
        if (auth()->check()) {
            auth()->user()->unreadNotifications->markAsRead();
        }

        $redirect = $request->query('redirect');
        if ($redirect === 'outstock') {
            return redirect()->route('outstock');
        } elseif ($redirect === 'expired') {
            return redirect()->route('expired');
        } elseif ($redirect === 'products') {
            return redirect()->route('products.index');
        } elseif ($redirect === 'purchases') {
            return redirect()->route('purchases.index');
        }

        $notification = notify('Notification marked as read');
        return back()->with($notification);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (auth()->check()) {
            auth()->user()->notifications()->delete();
        }
        $notification = notify('Notifications cleared');
        return back()->with($notification);
    }
}
