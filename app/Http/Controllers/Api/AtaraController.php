<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Atara\ContactRequest;
use App\Mail\Atara\AtaraContactMail;
use Illuminate\Support\Facades\Mail;

class AtaraController extends BaseController
{
    public function contact(ContactRequest $request)
    {
        Mail::to('info@atara.id')->bcc(['poedi@albatech.id'])->queue(new AtaraContactMail($request->validated()));

        return $this->okResponse();
    }
}
