<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdvertisementRequest;
use App\Http\Resources\AdvertisementResource;
use App\Models\Advertisement;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $query = Advertisement::query()->with('media')->orderBy('sort_order')->latest();
        if (! $request->user()) $query->active();

        return ApiResponse::success(AdvertisementResource::collection($query->get()));
    }

    public function store(AdvertisementRequest $request)
    {
        $this->authorize('manage', Advertisement::class);
        return ApiResponse::success(new AdvertisementResource(Advertisement::with('media')->create($request->validated())), [], 201);
    }

    public function update(AdvertisementRequest $request, Advertisement $advertisement)
    {
        $this->authorize('manage', Advertisement::class);
        $advertisement->update($request->validated());
        return ApiResponse::success(new AdvertisementResource($advertisement->fresh('media')));
    }

    public function destroy(Advertisement $advertisement)
    {
        $this->authorize('manage', Advertisement::class);
        $advertisement->delete();
        return ApiResponse::success(['message' => 'تبلیغ حذف شد.']);
    }
}
