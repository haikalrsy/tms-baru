<?php 
namespace App\Http\Controllers\Integration;
 
use App\Http\Controllers\Controller;
use App\Services\Integration\ERPSyncService;
use Illuminate\Http\Request;
 
class SyncController extends Controller
{
    public function __construct(private ERPSyncService $syncService) {}
 
    public function syncSalesOrders(Request $request)
    {
        $request->validate(['data' => 'required|array']);
        $result = $this->syncService->syncSalesOrders($request->data);
        return response()->json(['success'=>true,'data'=>$result]);
    }
 
    public function syncCustomers(Request $request)
    {
        $request->validate(['data' => 'required|array']);
        $result = $this->syncService->syncCustomers($request->data);
        return response()->json(['success'=>true,'data'=>$result]);
    }
 
    public function syncItems(Request $request)
    {
        $request->validate(['data' => 'required|array']);
        $result = $this->syncService->syncItems($request->data);
        return response()->json(['success'=>true,'data'=>$result]);
    }
}