<?php


namespace ForceTower\UPassportLogin;

use Illuminate\Contracts\Container\BindingResolutionException;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\PasswordGrant;

class SagresLoginGrantProvider extends PassportServiceProvider {
    public function boot() {
        $this->publishes([
            __DIR__.'/config/sagres.php' => config_path('sagres.php'),
        ]);
        if (file_exists(storage_path('oauth-private.key'))) {
            app(AuthorizationServer::class)->enableGrantType($this->makeRequestGrant(), Passport::tokensExpireIn());
        }
    }

    public function register() {
        //
    }

    /**
     * Create and configure a Password grant instance.
     *
     * @return SagresLoginRequestGrant
     * @throws BindingResolutionException
     */
    protected function makeRequestGrant()
    {
        $grant = new SagresLoginRequestGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );
        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        return $grant;
    }
}