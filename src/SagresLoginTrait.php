<?php


namespace ForceTower\UPassportLogin;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                $url = config('sagres.institutions.'.$institution.'.login');
                $form = config('sagres.institutions.'.$institution.'.form');
                $headers = config('sagres.headers');

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
                    if (count($name) > 0) {
                        Log::info('The extracted name is '.$name[0]);
                        $userModel = config('auth.providers.users.model');
                        $sagres_username_column = config('sagres.registration.username', 'username');
                        $user = $userModel::where($sagres_username_column, $username . '_' . $institution)->first();
                        Log::info('Model that will be returned '.$user);
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