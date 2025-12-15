<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Tourist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class HotelController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $query = Hotel::with('tourist');

        if ($user && !$user->isSuperAdmin() && $user->tourist_id) {
            $query->where('tourist_id', $user->tourist_id);
        }

        $hotels = $query->latest()->paginate(10);
        return view('saas.hotels.index', compact('hotels'));
    }

    public function create(): View
    {
        $tourists = Tourist::all();
        return view('saas.hotels.create', compact('tourists'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hotel_name' => 'required|string|max:255',
            'hotel_code' => 'required|string|unique:hotel,hotel_code',
            'tourist_id' => 'required|integer|exists:tourist,id',
            'room_types' => 'nullable|array',
            'room_types.*.roomtype' => 'required_with:room_types|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $hotel = Hotel::create($validated);

            if (isset($validated['room_types'])) {
                foreach ($validated['room_types'] as $roomTypeData) {
                    $hotel->roomTypes()->create([
                        'roomtype' => $roomTypeData['roomtype'],
                        'tourist_id' => $hotel->tourist_id,
                    ]);
                }
            }
        });

        return redirect()->route('saas.hotels.index')->with('success', '酒店及房型已成功创建！');
    }

    public function edit(Hotel $hotel): View
    {
        $this->authorize('view', $hotel);
        $hotel->load('roomTypes');
        $tourists = Tourist::all();
        return view('saas.hotels.edit', compact('hotel', 'tourists'));
    }

    public function update(Request $request, Hotel $hotel): RedirectResponse
    {
        $this->authorize('update', $hotel);

        $validated = $request->validate([
            'hotel_name' => 'required|string|max:255',
            'hotel_code' => 'required|string|unique:hotel,hotel_code,' . $hotel->id,
            'tourist_id' => 'required|integer|exists:tourist,id',
            'room_types' => 'nullable|array',
            'room_types.*.roomtype' => 'required_with:room_types|string|max:255',
        ]);

        DB::transaction(function () use ($hotel, $validated) {
            $hotel->update($validated);

            // Sync room types
            $hotel->roomTypes()->delete();
            if (isset($validated['room_types'])) {
                foreach ($validated['room_types'] as $roomTypeData) {
                     $hotel->roomTypes()->create([
                        'roomtype' => $roomTypeData['roomtype'],
                        'tourist_id' => $hotel->tourist_id,
                    ]);
                }
            }
        });

        return redirect()->route('saas.hotels.index')->with('success', '酒店及房型已成功更新！');
    }

    // API endpoint to get room types for a hotel
    public function getRoomTypes(Hotel $hotel): JsonResponse
    {
        $this->authorize('view', $hotel);
        return response()->json($hotel->roomTypes);
    }
}
