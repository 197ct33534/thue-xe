<?php
// app/Http/Controllers/VehicleController.php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with('category')
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('license_plate', 'like', "%{$request->search}%"));

        $vehicles = $query->orderBy('created_at', 'desc')->paginate(10);
        return response()->json($vehicles);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:vehicle_categories,id',
            'name' => 'required|string|max:255',
            'license_plate' => 'required|string|max:20|unique:vehicles',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'color' => 'nullable|string|max:50',
            'seats' => 'nullable|integer|min:1|max:50',
            'transmission' => 'in:manual,automatic',
            'fuel_type' => 'in:gasoline,diesel,electric,hybrid',
            'price_per_day' => 'required|numeric|min:0',
            'price_per_hour' => 'nullable|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'mileage_limit' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'thumbnail' => 'nullable|image|max:2048',
            'status' => 'in:available,rented,maintenance,inactive',
            'is_featured' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->all();
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('vehicles', 'public');
        }

        $vehicle = Vehicle::create($data);
        $vehicle->load('category');
        return response()->json($vehicle, 201);
    }

    public function show($id)
    {
        $vehicle = Vehicle::with('category')->findOrFail($id);
        return response()->json($vehicle);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:vehicle_categories,id',
            'name' => 'required|string|max:255',
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate,' . $id,
            // ... các rules khác tương tự store
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->all();
        if ($request->hasFile('thumbnail')) {
            // Xóa ảnh cũ
            if ($vehicle->thumbnail) {
                Storage::disk('public')->delete($vehicle->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('vehicles', 'public');
        }

        $vehicle->update($data);
        $vehicle->load('category');
        return response()->json($vehicle);
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        // Xóa ảnh
        if ($vehicle->thumbnail) {
            Storage::disk('public')->delete($vehicle->thumbnail);
        }
        $vehicle->delete();
        return response()->json(null, 204);
    }

    // Upload ảnh bổ sung
    public function uploadImages(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $images = [];
        
        foreach ($request->file('images') as $image) {
            $path = $image->store('vehicles/' . $id, 'public');
            $images[] = $path;
        }
        
        return response()->json(['images' => $images]);
    }
}