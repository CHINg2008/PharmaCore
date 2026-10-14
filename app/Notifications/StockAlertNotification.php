<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class StockAlertNotification extends Notification
{
    use Queueable;

    private $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        if (is_array($this->data)) {
            $productName = $this->data['product_name'] ?? $this->data['product'] ?? 'Medicine';
            $quantity = $this->data['quantity'] ?? 0;
            $expiryDate = $this->data['expiry_date'] ?? null;
            $purchaseId = $this->data['purchase_id'] ?? $this->data['id'] ?? null;
            $image = $this->data['image'] ?? 'default.png';
            $alertType = $this->data['alert_type'] ?? null;
        } else {
            $productName = $this->data->product ?? 'Medicine';
            $quantity = $this->data->quantity ?? 0;
            $expiryDate = $this->data->expiry_date ?? null;
            $purchaseId = $this->data->id ?? null;
            $image = $this->data->image ?? 'default.png';
            $alertType = $this->data->alert_type ?? null;
        }

        if (!$alertType) {
            if ($expiryDate && \Carbon\Carbon::parse($expiryDate)->isPast()) {
                $alertType = 'Expired Product';
            } elseif ($quantity <= 0) {
                $alertType = 'Out of Stock';
            } else {
                $alertType = 'Low Stock Warning';
            }
        }

        return [
            'product_name' => $productName,
            'quantity'     => $quantity,
            'expiry_date'  => $expiryDate,
            'purchase_id'  => $purchaseId,
            'alert_type'   => $alertType,
            'image'        => $image,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'product_name'=>$this->data->product,
            'quantity'=>$this->data->quantity,
        ]);
    }
}
