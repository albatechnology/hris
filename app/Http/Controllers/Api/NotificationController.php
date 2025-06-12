<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Notification\NotificationResource;
use App\Jobs\Timeoff\ReevaluateTimeOffDisciplineReward;
use App\Models\DatabaseNotification;
use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NotificationController extends BaseController
{
    public function index()
    {
        $data = QueryBuilder::for(DatabaseNotification::whereHas('notifiable', fn($q) => $q->where('id', auth('sanctum')->id()))->orderBy('created_at', 'desc'))
            ->allowedFilters([
                AllowedFilter::callback('type', fn(\Illuminate\Database\Eloquent\Builder $query, string $value) => $query->where('data->type', $value)),
                AllowedFilter::callback('message', fn(\Illuminate\Database\Eloquent\Builder $query, string $value) => $query->where('data->message', 'like', '%' . $value . '%')),
            ])
            ->allowedIncludes(['notifiable'])
            ->allowedSorts([
                'read_at',
                'created_at'
            ])
            ->paginate($this->per_page);

        return NotificationResource::collection($data);
    }

    public function countTotal(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'filter.type' => 'required|in:read,unread,all'
        ]);

        $query = DatabaseNotification::query();
        if ($request->filter['type'] == 'read') {
            $query->read();
        } elseif ($request->filter['type'] == 'unread') {
            $query->unread();
        }

        return response()->json(['message' => $query->count()]);
    }

    public function markAsRead(DatabaseNotification $notification)
    {
        $notification->markAsRead();
        return $this->updatedResponse();
    }

    public function show(DatabaseNotification $notification)
    {
        $notification->markAsRead();
        return new NotificationResource($notification);
    }

    public function destroy(DatabaseNotification $notification)
    {
        $notification->delete();

        return $this->deletedResponse();
    }

    public function test(string $token)
    {

        ReevaluateTimeOffDisciplineReward::dispatchSync("2025-04-30", "2025-01-01");
        die('OK');
        try {
            $eee = Crypt::decryptString($token);
            dd($eee);
        } catch (\Throwable $th) {
            dump('error 1');
            dump($th->getMessage());
        }

        $urldecode = urldecode($token);
        dump($urldecode);

        try {
            $dec = Crypt::decryptString($urldecode);
            dump('dec 2');
            dump($dec);
        } catch (\Throwable $th) {
            dump('error 2');
            dump($th->getMessage());
        }
        dd($token);
        // $title = $request->title ?? "Test Notification";
        // $body = $request->body ?? "This is a test notification";
        // $users = User::whereIn('id', $request->user_ids ?? [])->get(['id', 'name', 'fcm_token']);
        // foreach ($users as $user) {
        //     $user->notify(new TestNotification($title, $body));
        // }

        // return response()->json([
        //     'data' => [
        //         "title" => $request->title,
        //         "body" => $request->body,
        //         'users' => $users
        //     ]
        // ]);
    }
}
