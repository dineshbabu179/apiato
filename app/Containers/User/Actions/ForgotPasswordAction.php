<?php

namespace App\Containers\User\Actions;

use Apiato\Core\Foundation\Facades\Apiato;
use App\Containers\User\Mails\UserForgotPasswordMail;
use App\Ship\Exceptions\NotFoundException;
use App\Ship\Parents\Actions\Action;
use App\Ship\Parents\Requests\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class ForgotPasswordAction
 *
 * @author  Sebastian Weckend
 */
class ForgotPasswordAction extends Action
{

    /**
     * @param \App\Ship\Parents\Requests\Request $request
     */
    public function run(Request $request)
    {
        $user = Apiato::call('User@FindUserByEmailTask', [$request->email]);

        if(!$user){
            throw new ResourceNotFoundException("User Email ($request->email) not found.");
        }

        // generate token
        $token = Apiato::call('User@CreatePasswordResetTask', [$user]);

        $reseturl = $request->reseturl;

        // get last segment of the URL
        $url = explode('/', $reseturl);
        $lastSegment = $url[count($url)-1];

        // validate the allowed endpoint is being used
        if (!in_array($lastSegment, config('user-container.allowed-reset-password-urls'))) {
            throw new NotFoundException("The URL is incorrect ($reseturl)");
        }

        // send email
        Mail::send(new UserForgotPasswordMail($user, $token, $reseturl));
    }
}
