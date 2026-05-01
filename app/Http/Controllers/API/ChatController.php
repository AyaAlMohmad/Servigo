<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ChatController extends Controller
{
    public function checkChatButtonVisibility($targetUserId)
    {
        $currentUser = auth()->user();
        
        if (!$currentUser) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            return response()->json(['success' => false, 'message' => 'user_not_found'], 404);
        }

        $showButton = false;
        $buttonText = null;

        if ($currentUser->role === 'user' && $targetUser->role === 'provider') {
            $showButton = true;
            $buttonText = 'تواصل معي';
        }
        
        elseif ($currentUser->role === 'provider' && $targetUser->role === 'provider') {
            $showButton = true;
            $buttonText = 'تواصل معي';
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'show_chat_button' => $showButton,
                'button_text' => $buttonText
            ]
        ]);
    }


    public function startPrivateChat($providerId)
    {
        $currentUser = auth()->user();
        
        if (!$currentUser) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $provider = User::where('id', $providerId)
            ->where('role', 'provider')
            ->with('provider')
            ->first();
            
        if (!$provider) {
            return response()->json(['success' => false, 'message' => 'provider_not_found'], 404);
        }

        
        $existingChat = Chat::where(function($query) use ($currentUser, $provider) {
            $query->where('participant_one', $currentUser->id)
                  ->where('participant_two', $provider->id);
        })->orWhere(function($query) use ($currentUser, $provider) {
            $query->where('participant_one', $provider->id)
                  ->where('participant_two', $currentUser->id);
        })->first();

        if ($existingChat) {
            return response()->json([
                'success' => true,
                'data' => [
                    'chat_id' => $existingChat->id,
                    'is_existing' => true
                ]
            ]);
        }

        $chat = Chat::create([
            'type' => 'private',
            'participant_one' => $currentUser->id,
            'participant_two' => $provider->id
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'chat_id' => $chat->id,
                'is_existing' => false
            ]
        ], 201);
    }

    public function providerChatList()
    {
        $user = auth()->user();
        
        if (!$user || $user->role !== 'provider') {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $adminChats = Chat::where('type', 'admin_pinned')
            ->where(function($query) use ($user) {
                $query->where('participant_one', $user->id)
                      ->orWhere('participant_two', $user->id);
            })
            ->with(['messages' => function($q) {
                $q->latest()->limit(1);
            }, 'participantOne', 'participantTwo'])
            ->get()
            ->map(function($chat) use ($user) {
                $other = $chat->getOtherParticipant($user->id);
                return [
                    'id' => $chat->id,
                    'type' => 'admin_pinned',
                    'other_party' => [
                        'id' => $other->id,
                        'name' => $other->name,
                        'photo' => $this->getUserPhoto($other),
                    ],
                    'last_message' => $chat->messages->first()?->content ?? '',
                    'last_message_time' => $chat->messages->first()?->created_at,
                ];
            });

        $privateChats = Chat::where('type', 'private')
            ->where(function($query) use ($user) {
                $query->where('participant_one', $user->id)
                      ->orWhere('participant_two', $user->id);
            })
            ->with(['messages' => function($q) {
                $q->latest()->limit(1);
            }, 'participantOne', 'participantTwo'])
            ->get()
            ->map(function($chat) use ($user) {
                $other = $chat->getOtherParticipant($user->id);
                return [
                    'id' => $chat->id,
                    'type' => 'private',
                    'other_party' => [
                        'id' => $other->id,
                        'name' => $other->name,
                        'photo' => $this->getUserPhoto($other),
                        'role' => $other->role,
                    ],
                    'last_message' => $chat->messages->first()?->content ?? '',
                    'last_message_time' => $chat->messages->first()?->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'pinned_chats' => $adminChats,
                'chats' => $privateChats,
            ]
        ]);
    }

    public function customerChatList()
    {
        $user = auth()->user();
        
        if (!$user || $user->role !== 'user') {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $adminChats = Chat::where('type', 'admin_pinned')
            ->where(function($query) use ($user) {
                $query->where('participant_one', $user->id)
                      ->orWhere('participant_two', $user->id);
            })
            ->with(['messages' => function($q) {
                $q->latest()->limit(1);
            }, 'participantOne', 'participantTwo'])
            ->get()
            ->map(function($chat) use ($user) {
                $other = $chat->getOtherParticipant($user->id);
                return [
                    'id' => $chat->id,
                    'type' => 'admin_pinned',
                    'other_party' => [
                        'id' => $other->id,
                        'name' => $other->name,
                        'photo' => $this->getUserPhoto($other),
                    ],
                    'last_message' => $chat->messages->first()?->content ?? '',
                    'last_message_time' => $chat->messages->first()?->created_at,
                ];
            });

        $providerChats = Chat::where('type', 'private')
            ->where(function($query) use ($user) {
                $query->where('participant_one', $user->id)
                      ->orWhere('participant_two', $user->id);
            })
            ->with(['messages' => function($q) {
                $q->latest()->limit(1);
            }, 'participantOne', 'participantTwo'])
            ->get()
            ->filter(function($chat) use ($user) {
                $other = $chat->getOtherParticipant($user->id);
                return $other->role === 'provider';
            })
            ->map(function($chat) use ($user) {
                $other = $chat->getOtherParticipant($user->id);
                return [
                    'id' => $chat->id,
                    'type' => 'private',
                    'other_party' => [
                        'id' => $other->id,
                        'name' => $other->name,
                        'photo' => $this->getUserPhoto($other),
                    ],
                    'last_message' => $chat->messages->first()?->content ?? '',
                    'last_message_time' => $chat->messages->first()?->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'pinned_chats' => $adminChats,
                'chats' => $providerChats,
            ]
        ]);
    }

    public function getMessages($chatId)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $chat = Chat::with(['participantOne', 'participantTwo', 'messages.sender'])->find($chatId);
        
        if (!$chat) {
            return response()->json(['success' => false, 'message' => 'chat_not_found'], 404);
        }

        if ($chat->participant_one != $user->id && $chat->participant_two != $user->id) {
            return response()->json(['success' => false, 'message' => 'access_denied'], 403);
        }

        $otherParty = $chat->getOtherParticipant($user->id);
        
        $messages = $chat->messages()->get()->map(function($message) {
            return [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'content' => $message->content,
                'image_url' => $message->image_url,
                'video_url' => $message->video_url,
                'created_at' => $message->created_at->toDateTimeString(),
                'time' => $message->created_at->format('H:i'),
                'date' => $message->created_at->format('Y-m-d'),
                'is_mine' => $message->sender_id == auth()->id(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'chat_id' => $chat->id,
                'other_party' => [
                    'id' => $otherParty->id,
                    'name' => $otherParty->name,
                    'photo' => $this->getUserPhoto($otherParty),
                ],
                'messages' => $messages,
            ]
        ]);
    }

    public function sendMessage(Request $request, $chatId)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $request->validate([
            'content' => 'required_without_all:image,video|string|nullable',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:10240',
            'video' => 'nullable|mimes:mp4,mov,avi,mpg|max:51200',
        ]);

        $chat = Chat::find($chatId);
        if (!$chat) {
            return response()->json(['success' => false, 'message' => 'chat_not_found'], 404);
        }

        if ($chat->participant_one != $user->id && $chat->participant_two != $user->id) {
            return response()->json(['success' => false, 'message' => 'access_denied'], 403);
        }

        $imageUrl = null;
        $videoUrl = null;

        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $imageName = uniqid() . '_' . time() . '.' . $imageFile->getClientOriginalExtension();
            $imagePath = 'chats/images/' . $imageName;
            Storage::disk('public')->put($imagePath, file_get_contents($imageFile->getPathname()));
            $imageUrl = Storage::url($imagePath);
        }

        if ($request->hasFile('video')) {
            $videoFile = $request->file('video');
            $videoName = uniqid() . '_' . time() . '.' . $videoFile->getClientOriginalExtension();
            $videoPath = 'chats/videos/' . $videoName;
            Storage::disk('public')->put($videoPath, file_get_contents($videoFile->getPathname()));
            $videoUrl = Storage::url($videoPath);
        }

        $message = Message::create([
            'chat_id' => $chatId,
            'sender_id' => $user->id,
            'content' => $request->content,
            'image_url' => $imageUrl,
            'video_url' => $videoUrl,
        ]);

        $message->load('sender');

        return response()->json([
            'success' => true,
            'message' => 'message_sent',
            'data' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'content' => $message->content,
                'image_url' => $message->image_url,
                'video_url' => $message->video_url,
                'created_at' => $message->created_at->toDateTimeString(),
                'time' => $message->created_at->format('H:i'),
                'date' => $message->created_at->format('Y-m-d'),
                'is_mine' => true,
            ]
        ], 201);
    }

    private function getUserPhoto($user)
    {
        if ($user->role === 'provider' && $user->provider) {
            return $user->provider->id_photo_front ?? null;
        }
        
        return null;
    }
}