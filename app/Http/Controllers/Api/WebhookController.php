<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookConfig;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index()
    {
        return response()->json(WebhookConfig::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url',
            'active' => 'boolean',
            'events' => 'required|array',
            'events.*' => 'in:stock.alert,sale.created,stock.moved',
            'secret' => 'nullable|string|max:255',
        ]);

        $webhook = WebhookConfig::create($data);
        return response()->json($webhook, 201);
    }

    public function update(Request $request, WebhookConfig $webhookConfig)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'url' => 'sometimes|url',
            'active' => 'sometimes|boolean',
            'events' => 'sometimes|array',
            'events.*' => 'in:stock.alert,sale.created,stock.moved',
            'secret' => 'nullable|string|max:255',
        ]);

        $webhookConfig->update($data);
        return response()->json($webhookConfig);
    }

    public function destroy(WebhookConfig $webhookConfig)
    {
        $webhookConfig->delete();
        return response()->json(['message' => 'Webhook supprimé.']);
    }
}
