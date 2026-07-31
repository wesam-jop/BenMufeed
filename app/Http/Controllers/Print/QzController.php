<?php

namespace App\Http\Controllers\Print;

use App\Http\Controllers\Controller;
use App\Services\QzSigningService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QzController extends Controller
{
    public function certificate(QzSigningService $qz): Response
    {
        return response($qz->certificate(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sign(Request $request, QzSigningService $qz): Response
    {
        $payload = $request->input('request', $request->getContent());

        if (! is_string($payload) || $payload === '') {
            abort(422, 'Missing request payload.');
        }

        return response($qz->sign($payload), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
