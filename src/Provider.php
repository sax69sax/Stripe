<?php
namespace SocialiteProviders\Stripe;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use Illuminate\Http\Request;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * {@inheritdoc}
     */
    protected $scopes = ['read_write'];

    /**
     * @var User
     */
    protected $user;

    /**
     * Create a new provider instance.
     *
     * @param  Request  $request
     * @param  string  $clientId
     * @param  string  $clientSecret
     * @param  string  $redirectUrl
     * @return void
     */
    public function __construct(Request $request, $clientId, $clientSecret, $redirectUrl)
    {
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl);
        $this->user = new User();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://connect.stripe.com/oauth/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://connect.stripe.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.stripe.com/v1/account', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->clientSecret,
            ],
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return $this->user->setRaw($user)->map([
            'id' => $user['id'], 'nickname' => $user['display_name'],
            'name' => null, 'email' => $user['email'], 'avatar' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Get the access token, stripe public key and refresh token from the token response body.
     *
     * @param  string  $body
     * @return string
     */
    protected function parseAccessToken($body)
    {
        $body = json_decode($body, true);

        $this->user->stripe_publishable_key = $body['stripe_publishable_key'];
        $this->user->refresh_token = $body['refresh_token'];
        $this->user->access_token = $body['access_token'];
        $this->user->livemode = $body['livemode'];
        $this->user->stripe_user_id = $body['stripe_user_id'];

        return $body['access_token'];
    }
}
