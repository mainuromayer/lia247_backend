<?php

namespace App\Http\Controllers\API\V1\Customer\Chat;

use App\Events\NewOrderChatMessage;
use App\Events\NewRideChatMessage;
use App\Events\NewSupportChatMessage;
use App\Events\OrderMessagesRead;
use App\Events\RideMessagesRead;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Chat\SendMessageRequest;
use App\Http\Requests\API\V1\Chat\SendRideMessageRequest;
use App\Http\Requests\API\V1\Chat\SupportMessageRequest;
use App\Http\Resources\API\V1\Chat\ChatMessageResource;
use App\Http\Resources\Customer\SupportChat\SupportChatResource;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\Ride;
use App\Models\SupportChat;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    use ApiResponse;
    /**
     * Send a message related to an order.
     */
    public function sendOrderMessage(SendMessageRequest $request)
    {
        try {
            $user = auth('api')->user() ?? Auth::user();
            $order = Order::find($request->order_id);

            if (!$order) {
                return $this->error([], 'Order not found', 404);
            }

            // Check if current user is a participant
            if (!in_array($user->id, [$order->user_id, $order->vendor_id, $order->delivery_man_id]) && !$user->is_admin) {
                return $this->error([], 'You are not a participant in this order', 403);
            }

            // Check if sender and receiver are the same
            if ((int) $user->id === (int) $request->receiver_id) {
                return $this->error([], 'You cannot send a message to yourself', 400);
            }

            // Check if receiver is a participant
            if (!in_array((int) $request->receiver_id, [$order->user_id, $order->vendor_id, $order->delivery_man_id])) {
                return $this->error([], 'Receiver is not a participant in this order', 403);
            }

            $filePaths = [];
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $filesArray = is_array($files) ? $files : [$files];

                foreach ($filesArray as $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $customName = $originalName . '_' . time();
                    $filePath = Helper::fileUpload($file, 'order_chat', $customName);
                    if ($filePath) {
                        $filePaths[] = $filePath;
                    }
                }
            }

            $message = ChatMessage::create([
                'order_id' => $order->id,
                'sender_id' => $user->id,
                'receiver_id' => $request->receiver_id,
                'message' => $request->message,
                'files' => empty($filePaths) ? null : $filePaths,
                'is_read' => false,
            ]);

            broadcast(new NewOrderChatMessage($message->load('sender', 'receiver')))->toOthers();

            return $this->success(new ChatMessageResource($message), 'Message sent successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while sending the message' . $e->getMessage());
        }
    }

    /**
     * Get messages for a specific conversation in an order.
     */
    public function getOrderMessages(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required',
                'receiver_id' => 'required',
            ]);

            $user = auth('api')->user() ?? Auth::user();
            $orderId = $request->order_id;
            $receiverId = $request->receiver_id;

            $messages = ChatMessage::where('order_id', $orderId)
                ->where(function ($query) use ($user, $receiverId) {
                    $query->where(function ($q) use ($user, $receiverId) {
                        $q->where('sender_id', $user->id)->where('receiver_id', $receiverId);
                    })->orWhere(function ($q) use ($user, $receiverId) {
                        $q->where('sender_id', $receiverId)->where('receiver_id', $user->id);
                    });
                })
                ->with(['sender', 'receiver'])
                ->oldest()
                ->get();

            return $this->success(ChatMessageResource::collection($messages), 'Messages fetched successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching messages' . $e->getMessage());
        }
    }

    /**
     * Mark messages in a conversation as read.
     */
    public function markOrderMessageAsRead(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required',
                'receiver_id' => 'required',
            ]);

            $user = auth('api')->user() ?? Auth::user();
            $orderId = $request->order_id;
            $receiverId = $request->receiver_id;

            ChatMessage::where('order_id', $orderId)
                ->where('sender_id', $receiverId)
                ->where('receiver_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            broadcast(new OrderMessagesRead($orderId, $user->id, $receiverId))->toOthers();

            return $this->success([], 'Messages marked as read successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while updating read status' . $e->getMessage());
        }
    }

    /**
     * Get list of participants in an order chat.
     */
    public function getOrderParticipants(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required',
            ]);

            $order = Order::with(['user', 'vendor', 'deliveryMan'])->find($request->order_id);

            if (!$order) {
                return $this->error([], 'Order not found', 404);
            }

            $participants = [
                'customer' => [
                    'user_id' => $order->user->id ?? null,
                    'name' => $order->user->name ?? 'Customer',
                    'image' => $order->user->avatar ?? null,
                ],
                'vendor' => $order->vendor ? [
                    'user_id' => $order->vendor->id,
                    'name' => $order->vendor->name,
                    'image' => $order->vendor->avatar,
                ] : null,
                'delivery_man' => $order->deliveryMan ? [
                    'user_id' => $order->deliveryMan->id,
                    'name' => $order->deliveryMan->name,
                    'image' => $order->deliveryMan->avatar,
                ] : null,
            ];

            return $this->success($participants, 'Participants fetched successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching participants' . $e->getMessage());
        }
    }

    /**
     * Common support message logic.
     */
    public function sendSupportMessage(SupportMessageRequest $request)
    {
        try {
            $filePaths = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $customName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
                    $filePath = Helper::fileUpload($file, 'support_chat', $customName);
                    if ($filePath) $filePaths[] = $filePath;
                }
            }

            $message = SupportChat::create([
                'user_id' => auth('api')->id() ?? Auth::id(),
                'message' => $request->message,
                'files' => empty($filePaths) ? null : $filePaths,
                'sender_type' => 'customer',
            ]);

            broadcast(new NewSupportChatMessage($message))->toOthers();

            return $this->success(new SupportChatResource($message), 'Support message sent successfully', 201);
        } catch (Exception $e) {
            return $this->error([], 'An error occurred while sending support message' . $e->getMessage());
        }
    }

    /**
     * Fetch support message history.
     */
    public function getSupportMessages()
    {
        try {
            $userId = auth('api')->id() ?? Auth::id();
            $messages = SupportChat::where('user_id', $userId)
                ->orWhere('admin_id', $userId)
                ->latest()
                ->get();

            return $this->success(SupportChatResource::collection($messages), 'Support messages fetched successfully');
        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching support messages' . $e->getMessage());
        }
    }

    /**
     * Send a message related to a ride.
     */
    public function sendRideMessage(SendRideMessageRequest $request)
    {
        try {
            $user = auth('api')->user() ?? Auth::user();
            $ride = Ride::find($request->ride_id);

            if (!$ride) {
                return $this->error([], 'Ride not found', 404);
            }

            // Check if current user is a participant
            if (!in_array($user->id, [$ride->user_id, $ride->rider_id]) && !$user->is_admin) {
                return $this->error([], 'You are not a participant in this ride', 403);
            }

            // Check if sender and receiver are the same
            if ((int) $user->id === (int) $request->receiver_id) {
                return $this->error([], 'You cannot send a message to yourself', 400);
            }

            // Check if receiver is a participant
            if (!in_array((int) $request->receiver_id, [$ride->user_id, $ride->rider_id])) {
                return $this->error([], 'Receiver is not a participant in this ride', 403);
            }

            $filePaths = [];
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $filesArray = is_array($files) ? $files : [$files];

                foreach ($filesArray as $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $customName = $originalName . '_' . time();
                    $filePath = Helper::fileUpload($file, 'ride_chat', $customName);
                    if ($filePath) {
                        $filePaths[] = $filePath;
                    }
                }
            }

            $message = ChatMessage::create([
                'ride_id' => $ride->id,
                'sender_id' => $user->id,
                'receiver_id' => $request->receiver_id,
                'message' => $request->message,
                'files' => empty($filePaths) ? null : $filePaths,
                'is_read' => false,
            ]);

            broadcast(new NewRideChatMessage($message->load('sender', 'receiver')))->toOthers();

            return $this->success(new ChatMessageResource($message), 'Message sent successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while sending the message' . $e->getMessage());
        }
    }

    /**
     * Get messages for a specific conversation in a ride.
     */
    public function getRideMessages(Request $request)
    {
        try {
            $request->validate([
                'ride_id' => 'required',
                'receiver_id' => 'required',
            ]);

            $user = auth('api')->user() ?? Auth::user();
            $rideId = $request->ride_id;
            $receiverId = $request->receiver_id;

            $messages = ChatMessage::where('ride_id', $rideId)
                ->where(function ($query) use ($user, $receiverId) {
                    $query->where(function ($q) use ($user, $receiverId) {
                        $q->where('sender_id', $user->id)->where('receiver_id', $receiverId);
                    })->orWhere(function ($q) use ($user, $receiverId) {
                        $q->where('sender_id', $receiverId)->where('receiver_id', $user->id);
                    });
                })
                ->with(['sender', 'receiver'])
                ->oldest()
                ->get();

            return $this->success(ChatMessageResource::collection($messages), 'Messages fetched successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching messages' . $e->getMessage());
        }
    }

    /**
     * Mark messages in a conversation as read.
     */
    public function markRideMessageAsRead(Request $request)
    {
        try {
            $request->validate([
                'ride_id' => 'required',
                'receiver_id' => 'required',
            ]);

            $user = auth('api')->user() ?? Auth::user();
            $rideId = $request->ride_id;
            $receiverId = $request->receiver_id;

            ChatMessage::where('ride_id', $rideId)
                ->where('sender_id', $receiverId)
                ->where('receiver_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            broadcast(new RideMessagesRead($rideId, $user->id, $receiverId))->toOthers();

            return $this->success([], 'Messages marked as read successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while updating read status' . $e->getMessage());
        }
    }

    /**
     * Get list of participants in a ride chat.
     */
    public function getRideParticipants(Request $request)
    {
        try {
            $request->validate([
                'ride_id' => 'required',
            ]);

            $ride = \App\Models\Ride::with(['user', 'rider'])->find($request->ride_id);

            if (!$ride) {
                return $this->error([], 'Ride not found', 404);
            }

            $participants = [
                'customer' => [
                    'user_id' => $ride->user->id ?? null,
                    'name' => $ride->user->name ?? 'Customer',
                    'image' => $ride->user->avatar ?? null,
                ],
                'rider' => $ride->rider ? [
                    'user_id' => $ride->rider->id,
                    'name' => $ride->rider->name,
                    'image' => $ride->rider->avatar,
                ] : null,
            ];

            return $this->success($participants, 'Participants fetched successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching participants' . $e->getMessage());
        }
    }

    /**
     * Get list of conversations for a vendor (chats with delivery men).
     */
    public function getVendorConversations(Request $request)
    {
        try {
            $user = auth('api')->user() ?? Auth::user();

            if ($user->user_type !== 'vendor' && $user->role !== 'vendor') {
                return $this->error([], 'Only vendors can access this list', 403);
            }

            $search = $request->query('search');

            // Get all unique delivery man IDs who have orders with this vendor
            $deliveryManIds = Order::where('vendor_id', $user->id)
                ->whereNotNull('delivery_man_id')
                ->distinct()
                ->pluck('delivery_man_id')
                ->toArray();

            $deliveryMenQuery = User::whereIn('id', $deliveryManIds);

            if ($search) {
                $deliveryMenQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $deliveryMen = $deliveryMenQuery->get();

            $conversations = [];

            foreach ($deliveryMen as $deliveryMan) {
                // Get the last chat message between this vendor and this delivery man (across any order)
                $lastMessage = ChatMessage::where(function ($query) use ($user, $deliveryMan) {
                    $query->where(function ($q) use ($user, $deliveryMan) {
                        $q->where('sender_id', $user->id)->where('receiver_id', $deliveryMan->id);
                    })->orWhere(function ($q) use ($user, $deliveryMan) {
                        $q->where('sender_id', $deliveryMan->id)->where('receiver_id', $user->id);
                    });
                })
                ->latest()
                ->first();

                // Get count of unread messages sent by this delivery man to the vendor
                $unreadCount = ChatMessage::where('sender_id', $deliveryMan->id)
                    ->where('receiver_id', $user->id)
                    ->where('is_read', false)
                    ->count();

                // Get latest order between this vendor and this delivery man to use as sorting fallback
                $latestOrder = Order::where('vendor_id', $user->id)
                    ->where('delivery_man_id', $deliveryMan->id)
                    ->latest()
                    ->first();

                // Get all orders handled by this delivery man for this vendor
                $deliveryManOrders = [];
                $ordersList = Order::where('vendor_id', $user->id)
                    ->where('delivery_man_id', $deliveryMan->id)
                    ->latest()
                    ->get();

                foreach ($ordersList as $ord) {
                     // Check if there are any unread messages from the delivery man to the vendor for this specific order
                     $orderUnreadCount = ChatMessage::where('order_id', $ord->id)
                         ->where('sender_id', $deliveryMan->id)
                         ->where('receiver_id', $user->id)
                         ->where('is_read', false)
                         ->count();

                     $deliveryManOrders[] = [
                         'id' => $ord->id,
                         'order_no' => $ord->order_no,
                         'status' => $ord->status,
                         'is_read' => $orderUnreadCount === 0,
                         'created_at' => $ord->created_at->format('Y-m-d H:i:s'),
                     ];
                }

                $sortTimestamp = $lastMessage ? $lastMessage->created_at : ($latestOrder ? $latestOrder->created_at : now());

                $senderRole = null;
                $receiverRole = null;
                if ($lastMessage) {
                    if ($lastMessage->sender_id == $user->id) {
                        $senderRole = $user->user_type ?? $user->role ?? 'vendor';
                        $receiverRole = $deliveryMan->user_type ?? $deliveryMan->role ?? 'delivery_man';
                    } else {
                        $senderRole = $deliveryMan->user_type ?? $deliveryMan->role ?? 'delivery_man';
                        $receiverRole = $user->user_type ?? $user->role ?? 'vendor';
                    }
                }

                $conversations[] = [
                    'delivery_man' => [
                        'id' => $deliveryMan->id,
                        'name' => $deliveryMan->name,
                        'avatar' => $deliveryMan->avatar ? asset($deliveryMan->avatar) : null,
                        'phone' => $deliveryMan->phone,
                        'role' => $deliveryMan->user_type ?? $deliveryMan->role ?? 'delivery_man',
                        'orders' => $deliveryManOrders,
                        'last_message' => $lastMessage ? [
                            'id' => $lastMessage->id,
                            'message' => $lastMessage->message,
                            'files' => $lastMessage->files,
                            'sender_id' => $lastMessage->sender_id,
                            'sender_role' => $senderRole,
                            'receiver_id' => $lastMessage->receiver_id,
                            'receiver_role' => $receiverRole,
                            'is_read' => $lastMessage->is_read,
                            'created_at' => $lastMessage->created_at->format('Y-m-d H:i:s'),
                        ] : null,
                    ],
                    'unread_count' => $unreadCount,
                    'sort_timestamp' => $sortTimestamp,
                ];
            }

            // Sort conversations by sort_timestamp descending (latest message first)
            usort($conversations, function ($a, $b) {
                return $b['sort_timestamp'] <=> $a['sort_timestamp'];
            });

            // Clean up sort_timestamp before sending response
            foreach ($conversations as &$conversation) {
                unset($conversation['sort_timestamp']);
            }

            return $this->success($conversations, 'Conversation list fetched successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching conversation list: ' . $e->getMessage(), 500);
        }
    }
}