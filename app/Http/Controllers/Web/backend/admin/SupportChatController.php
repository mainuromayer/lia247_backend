<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Events\NewSupportChatMessage;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Admin\SupportChatRequest;
use App\Models\SupportChat;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportChatController extends Controller
{
    /**
     * Display a listing of customers who have sent messages.
     */
    public function index()
    {
        $customers = User::where('user_type', 'customer')
            ->whereHas('supportChats')
            ->withCount(['supportChats as unread_count' => function ($query) {
                $query->where('sender_type', 'customer')->where('is_read_by_admin', false);
            }])
            ->with(['supportChats' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get();

        return view('backend.layout.support_chat.index', compact('customers'));
    }

    /**
     * Display the chat history with a specific customer.
     */
    public function show($id)
    {
        $customer = User::findOrFail($id);
        $messages = SupportChat::where('user_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read by admin
        SupportChat::where('user_id', $id)
            ->where('sender_type', 'customer')
            ->where('is_read_by_admin', false)
            ->update(['is_read_by_admin' => true]);

        return view('backend.layout.support_chat.show', compact('customer', 'messages'));
    }

    /**
     * Send a new message to the customer.
     */
    public function sendMessage(SupportChatRequest $request, $id)
    {
        try {
            $filePaths = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $customName = $originalName . '_' . time();
                    $filePath = Helper::fileUpload($file, 'support_chat', $customName);
                    if ($filePath) {
                        $filePaths[] = $filePath;
                    }
                }
            }

            $chat = new SupportChat();
            $chat->user_id = $id;
            $chat->admin_id = Auth::id();
            $chat->message = $request->message;
            $chat->files = empty($filePaths) ? null : $filePaths;
            $chat->sender_type = 'admin';
            $chat->save();

            // Broadcast the reply to the customer
            broadcast(new NewSupportChatMessage($chat))->toOthers();

            flash()->success('Reply sent successfully.');
            return back();

        } catch (Exception $e) {
            flash()->error('Failed to send reply: ' . $e->getMessage());
            return back();
        }
    }
}
