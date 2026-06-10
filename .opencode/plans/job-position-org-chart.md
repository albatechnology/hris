# JobPosition Organization Chart Implementation

## 1. `app/Models/JobPosition.php`

Add `BelongsTo` import and `parent()` + `children()` relationships:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Add after `$fillable` and before `users()`:

public function parent(): BelongsTo
{
    return $this->belongsTo(self::class, 'parent_id');
}

public function children(): HasMany
{
    return $this->hasMany(self::class, 'parent_id');
}
```

## 2. `app/Http/Controllers/Api/JobPositionController.php`

Replace the empty `chartView()` with:

```php
public function chartView(int $companyId)
{
    $positions = JobPosition::tenanted()
        ->where('company_id', $companyId)
        ->with('users:id,name,email,job_position_id')
        ->get();

    $grouped = $positions->groupBy('parent_id');

    $buildTree = function ($parentId) use ($grouped) {
        $nodes = $grouped->get($parentId, collect());
        return $nodes->values()->map(fn($node) => [
            'id' => $node->id,
            'name' => $node->name,
            'code' => $node->code,
            'parent_id' => $node->parent_id,
            'users' => $node->users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]),
            'children' => $buildTree($node->id),
        ]);
    };

    return response()->json(['data' => $buildTree(null)]);
}
```

## 3. `routes/api.php`

Route already present at line 421:
```php
Route::get('job-positions/{company}/chart', [JobPositionController::class, 'chartView']);
```
This is before `apiResource` so it won't conflict. The URI `job-positions/{company}/chart` has 3 segments, while the apiResource show route `job-positions/{job_position}` has 2 segments — no collision.

## How it works

- Fetches all job positions + their users for the given company
- Groups positions by `parent_id` into a map
- Recursively builds tree starting from root nodes (`parent_id = null`)
- Each node includes `id`, `name`, `code`, `parent_id`, `users` (id/name/email), and `children`
- Response: `{ "data": [ ... tree nodes ... ] }`
