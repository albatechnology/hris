<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Notification\NotificationResource;
use App\Models\DatabaseNotification;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NotificationController extends BaseController
{
    public function index()
    {
        $data = QueryBuilder::for(DatabaseNotification::whereHas('notifiable', fn ($q) => $q->where('id', auth('sanctum')->id()))->orderBy('created_at', 'desc'))
            ->allowedFilters([
                AllowedFilter::callback('type', fn (\Illuminate\Database\Eloquent\Builder $query, string $value) => $query->where('data->type', $value)),
                AllowedFilter::callback('message', fn (\Illuminate\Database\Eloquent\Builder $query, string $value) => $query->where('data->message', 'like', '%' . $value . '%')),
            ])
            ->allowedIncludes(['notifiable'])
            ->allowedSorts([
                'read_at', 'created_at'
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
}
