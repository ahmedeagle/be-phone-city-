<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriberRequest;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Response;

class SubscriberController extends Controller
{
    public function store(StoreSubscriberRequest $request)
    {
        $subscriber = Subscriber::updateOrCreate(
            ['phone' => $request->phone],
            [
                'source' => $request->source ?? 'popup',
                'is_active' => true,
            ]
        );

        return Response::success(
            __('تم الاشتراك بنجاح! سنرسل لك أفضل العروض والكوبونات'),
            ['id' => $subscriber->id],
            201
        );
    }
}
