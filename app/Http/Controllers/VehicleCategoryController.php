<?php
// app/Http/Controllers/VehicleCategoryController.php
namespace App\Http\Controllers;

use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleCategoryController extends Controller
{
    // Danh sách
    public function index()
    {
        $categories = VehicleCategory::orderBy('display_order')->get();
        return response()->json($categories);
    }

    // Chi tiết
    public function show($id)
    {
        $category = VehicleCategory::findOrFail($id);
        return response()->json($category);
    }

    // Tạo mới
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:vehicle_categories',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'display_order' => 'integer',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = VehicleCategory::create($request->all());
        return response()->json($category, 201);
    }

    // Cập nhật
    public function update(Request $request, $id)
    {
        $category = VehicleCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:vehicle_categories,name,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'display_order' => 'integer',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category->update($request->all());
        return response()->json($category);
    }

    // Xóa (soft delete hoặc hard delete)
    public function destroy($id)
    {
        $category = VehicleCategory::findOrFail($id);
        $category->delete();
        return response()->json(null, 204);
    }
}