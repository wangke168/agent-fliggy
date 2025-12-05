<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FliggyWebhookController extends Controller
{
    /**
     * Handle Fliggy product change notifications.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleProductChange(Request $request)
    {
        // Log the incoming request for debugging
        Log::channel('fliggy')->info('Product Change Webhook Received:', $request->all());

        // According to the documentation, we need to respond with "success"
        // A real implementation would have business logic here to process the data.

        // For example, you might want to dispatch a job to update your local product database
        // dispatch(new \App\Jobs\UpdateFliggyProduct($request->all()));

        return response('success', 200)
                  ->header('Content-Type', 'text/plain');
    }

    /**
     * Handle Fliggy order status notifications.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleOrderStatus(Request $request)
    {
        // Log the incoming request for debugging
        Log::channel('fliggy')->info('Order Status Webhook Received:', $request->all());

        // According to the documentation, we need to respond with "success"
        // A real implementation would have business logic here to process the data.

        // For example, you might want to dispatch a job to update your local order database
        // dispatch(new \App\Jobs\UpdateFliggyOrder($request->all()));

        return response('success', 200)
                  ->header('Content-Type', 'text/plain');
    }
}
