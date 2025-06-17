<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Atara\ContactRequest;
use App\Mail\Atara\AtaraContactMail;
use Illuminate\Support\Facades\Mail;

class AtaraController extends BaseController
{
    public function contact(ContactRequest $request)
    {
        // Mail::to('info@atara.id')->bcc(['poedi@albatech.id', 'albaprogrammer2@gmail.com'])->sendNow(new AtaraContactMail($request));
        Mail::to('albaprogrammer2@gmail.com')->sendNow(new AtaraContactMail($request));

        return $this->okResponse();
    }
}
