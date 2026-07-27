<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\SupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    use ApiResponse;

    public function store(SupportTicketRequest $request)
    {
        try {
            $ticket = new SupportTicket();
            $ticket->user_id = Auth::id();
            $ticket->name = $request->name;
            $ticket->email = $request->email;
            $ticket->subject = $request->subject;
            $ticket->phone = $request->phone;
            $ticket->message = $request->message;
            $ticket->status = 'pending';
            $ticket->save();

            // Notify Admin
            $admins = User::where('is_admin', true)->get();
            foreach ($admins as $admin) {
                NotificationService::send(
                    $admin->id,
                    'New Support Message',
                    "You have received a new support message from " . $request->name,
                    'support_message',
                    'admin',
                    [
                        'ticket_id' => $ticket->id,
                        'subject' => $ticket->subject,
                        'sender_name' => $ticket->name
                    ]
                );
            }

            return $this->success($ticket, 'Message sent successfully. We will contact you soon.');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while sending the message: ' . $e->getMessage(), 500);
        }
    }
}
