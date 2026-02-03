<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExecuteController extends Controller
{
    public function __invoke(Request $request)
    {
        // Valida os dados básicos
        $validated = $request->validate([
            'action' => 'required|string',
            'model' => 'required|string',
            'record_id' => 'nullable',
        ]);

        $modelClass = $validated['model'];
        $actionName = $validated['action'];
        $recordId = $validated['record_id'];

        if (!class_exists($modelClass)) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        $record = $recordId ? $modelClass::find($recordId) : null;
        $controller = $record ? $record->getController() : app($modelClass)->getController();

        if (!$controller) {
            return response()->json(['error' => 'Controller not found for the model'], 404);
        }

        $action = $controller->getAction($actionName);

        if (!$action) {
            return response()->json(['error' => 'Action not found'], 404);
        }

        return $action->execute($request, $record);
    }
}
