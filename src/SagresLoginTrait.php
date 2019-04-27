<?php


namespace ForceTower\UPassportLogin;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use League\OAuth2\Server\Exception\OAuthServerException;
use Throwable;

include "simple_html_dom.php";

trait SagresLoginTrait
{

    /**
     * Logs a App\User in using Sagres username and password via Passport
     *
     * @param Request $request
     *
     * @return Model|null
     */
    public function loginSagres(Request $request) {
        try {
            $username = $request->get('username');
            $password = $request->get('password');
            $institution = $request->get('institution');

            if ($username && $password && $institution) {
                $url = config('sagres.institutions.'.$institution.'.login', null);
                $form = config('sagres.institutions.'.$institution.'.form', null);
                $headers = config('sagres.headers');

                if (!$url || !$form) {
                    throw OAuthServerException::accessDenied('Institution is not supported');
                }

                $form['ctl00$PageContent$LoginPanel$UserName'] = $username;
                $form['ctl00$PageContent$LoginPanel$Password'] = $password;

                // Make request to Sagres
                $client = new Client(['cookies' => true]);
                $response = $client->post($url, [
                    'form_params' => $form,
                    'headers' => $headers
                ]);

                $code = $response->getStatusCode();
                // If request is successful
                if ($code >= 200 && $code < 300) {
                    $content = $response->getBody()->getContents();
                    $html = str_get_html($content);
                    $name = $html->find('span[class=usuario-nome]');
                    // If the trait is able to verify the user
                    if (count($name) > 0) {
                        // Find config fields
                        $userModel              = config('auth.providers.users.model');
                        $sagres_username_column = config('sagres.registration.username', 'username');
                        $sagres_password_column = config('sagres.registration.password', 'password');
                        $sagres_email_column    = config('sagres.registration.email', 'email');
                        $sagres_create_account  = config('sagres.registration.create_account', true);

                        // Find the user
                        $user = null;
                        // If it contains a @ it means user connected with email
                        $emailConnected = strpos($username, '@') !== false;
                        if ($emailConnected) {
                            $user = $userModel::where($sagres_email_column, $username)->first();
                        } else {
                            $user = $userModel::where($sagres_username_column, $username . '_' . $institution)->first();
                        }

                        // If user is not registered yet, create it with minimal information
                        if (!$user && $sagres_create_account) {
                            $user = new $userModel();

                            if ($emailConnected) {
                                $user->{$sagres_email_column} = $username;
                                $newUsername = explode('@', $username)[0];
                                $user->{$sagres_username_column} = $newUsername . '_' . $institution . '_email';
                            } else {
                                $user->{$sagres_username_column} = $username . '_' . $institution;
                            }

                            $user->{$sagres_password_column} = Hash::make($password);
                            $user->save();
                        }

                        return $user;
                    }
                }
            }
        } catch (Throwable $throwable) {
            throw OAuthServerException::accessDenied($throwable->getMessage());
        }
        return null;
    }
}