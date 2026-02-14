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
            'actionName' => 'required|string',
            'actionType' => 'required|string',
            'modelClass' => 'nullable|string',
            'record_id' => 'nullable',
        ]);
        $actionType = data_get($validated, 'actionType');
        $modelClass = data_get($validated, 'modelClass');
        $actionName = data_get($validated, 'actionName');
        $recordId = data_get($validated, 'record_id');

        if (! class_exists($modelClass)) {
            return redirect()->back()->with('error', 'Model not found');
        }

        $record = $recordId ? $modelClass::find($recordId) : null;
        $controller = $record ? $record->getController() : app($modelClass)->getController();

        if (! $controller) {
            return redirect()->back()->with('error', 'Controller not found for the model');
        }

        $action = $controller->getAction($actionName);

        if (! $action) {
            return redirect()->back()->with('error', 'Action not found');
        }

        return $action->execute($request, $record);
    }
}
