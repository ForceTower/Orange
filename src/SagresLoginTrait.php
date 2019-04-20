<?php


namespace ForceTower\UPassportLogin;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Server\Exception\OAuthServerException;
use Throwable;

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

            Log::info('Attempt to login user '.$username);

            if ($username && $password && $institution) {
                $url = config('sagres.institutions.'.$institution.'.login');
                $form = config('sagres.institutions.'.$institution.'.form');
                $headers = config('sagres.headers');

                $form['ctl00$PageContent$LoginPanel$UserName'] = $username;
                $form['ctl00$PageContent$LoginPanel$Password'] = $password;

                Log::info('This is how the form looks like:');
                Log::info($form);

                // Make request to Sagres
                $client = new Client(['cookies' => true]);
                $response = $client->post($url, [
                    'form_params' => $form,
                    'headers' => $headers
                ]);

                $code = $response->getStatusCode();
                // If request is successful
                if ($code >= 200 && $code < 300) {
                    Log::info('Sagres response successful');
                    $content = $response->getBody()->getContents();
                    $html = str_get_html($content);
                    $name = $html->find('span[class=usuario-nome]');
                    Log::info('The extracted name is '.$name);
                    if ($name) {
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