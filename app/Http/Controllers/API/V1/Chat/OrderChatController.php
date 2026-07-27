<?php

namespace App\Http\Controllers\API\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Chat\SendMessageRequest;
use App\Http\Resources\API\V1\Chat\ChatMessageCollection;
use App\Http\Resources\API\V1\Chat\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Helper\Helper;

class OrderChatController extends Controller
{
    use ApiResponse;

    /**
     * Get messages for a specific order.
     */
    public function getMessages($order_id)
    {
        try {
            $user = Auth::user();
            $order = Order::findOrFail($order_id);

            // Access control: User must be part of the order (customer, vendor, or delivery man)
            if ($user->id !== $order->user_id && 
                $user->id !== $order->vendor_id && 
                $user->id !== $order->delivery_man_id) {
                return $this->error([], 'Unauthorized to view this chat', 403);
            }

            $messages = ChatMessage::where('order_id', $order_id)
                ->where(function($query) use ($user) {
                    $query->where('sender_id', $user->id)
                          ->orWhere('receiver_id', $user->id);
                })
                ->with(['sender:id,name,avatar', 'receiver:id,name,avatar'])
                ->oldest()
                ->get();

            // Mark received messages as read
            ChatMessage::where('order_id', $order_id)
                ->where('receiver_id', $user->id)
                ->update(['is_read' => true]);

            return $this->success(new ChatMessageCollection($messages), 'Messages fetched successfully');
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Send a message.
     */
    public function sendMessage(SendMessageRequest $request)
    {
        try {
            $user = Auth::user();
            $order = Order::findOrFail($request->order_id);

            // Access control: Ensure both sender and receiver are valid for this order
            if (!$this->isParticipant($user->id, $order)) {
                return $this->error([], 'You are not a participant of this order', 403);
            }

            if (!$this->isParticipant($request->receiver_id, $order)) {
                return $this->error([], 'Receiver is not a participant of this order', 403);
            }

            if ($user->id === (int) $request->receiver_id) {
                return $this->error([], 'You cannot send a message to yourself', 400);
            }

            // Specific relationship checks based on requirement:
            // 1. Customer <-> Delivery Man
            // 2. Delivery Man <-> Vendor
            if (!$this->isValidChatRelationship($user, $request->receiver_id, $order)) {
                return $this->error([], 'Direct chat between these roles is not allowed for this order type', 403);
            }

            $filePaths = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $filePaths[] = Helper::fileUpload($file, 'chats');
                }
            }

            $message = new ChatMessage();
            $message->order_id = $request->order_id;
            $message->sender_id = $user->id;
            $message->receiver_id = $request->receiver_id;
            $message->message = $request->message;
            $message->files = $filePaths;
            $message->is_read = false;
            $message->save();

            return $this->success(new ChatMessageResource($message), 'Message sent successfully');
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    private function isParticipant($userId, $order) {
        return in_array($userId, [$order->user_id, $order->vendor_id, $order->delivery_man_id]);
    }

    private function isValidChatRelationship($sender, $receiverId, $order) {
        $participantIds = [
            'customer' => $order->user_id,
            'vendor' => $order->vendor_id,
            'delivery_man' => $order->delivery_man_id
        ];

        $receiver = \App\Models\User::find($receiverId);
        if (!$receiver) return false;

        $senderRole = $sender->user_type;
        $receiverRole = $receiver->user_type;

        // Requirement: 
        // 1. Customer <-> Delivery Man
        if (($senderRole === 'customer' && $receiverRole === 'delivery_man' && $receiverId === $order->delivery_man_id) ||
            ($senderRole === 'delivery_man' && $receiverRole === 'customer' && $receiverId === $order->user_id)) {
            return true;
        }

        // 2. Delivery Man <-> Vendor
        if (($senderRole === 'delivery_man' && $receiverRole === 'vendor' && $receiverId === $order->vendor_id) ||
            ($senderRole === 'vendor' && $receiverRole === 'delivery_man' && $receiverId === $order->delivery_man_id)) {
            return true;
        }

        return false;
    }
}
