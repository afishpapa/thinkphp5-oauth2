<?php

namespace app\index\controller;

use auth2\Entities\UserEntity;
use auth2\Repositories\AuthCodeRepository;
use auth2\Repositories\RefreshTokenRepository;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use League\OAuth2\Server\AuthorizationServer;
use auth2\Repositories\AccessTokenRepository;
use auth2\Repositories\ClientRepository;
use auth2\Repositories\ScopeRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use think\Controller;
use think\Db;
use think\Request;
use think\Response;
use think\Session;

class Auth extends Controller
{

    private $authorizationServer;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        // 以下五个Repository需要自己实现
        $clientRepository = new ClientRepository(); // instance of ClientRepositoryInterface
        $scopeRepository = new ScopeRepository(); // instance of ScopeRepositoryInterface
        $accessTokenRepository = new AccessTokenRepository(); // instance of AccessTokenRepositoryInterface
        $authCodeRepository = new AuthCodeRepository(); // instance of AuthCodeRepositoryInterface
        $refreshTokenRepository = new RefreshTokenRepository(); // instance of RefreshTokenRepositoryInterface
        //私钥
        $privateKey = ROOT_PATH . '/private.key';
        $encryptionKey = 'lxZFUEsBCJ2Yb14IF2ygAHI5N4+ZAUXXaSeeJm6+twsUmIen'; // base64_encode(random_bytes(32))

        // 实例化AuthorizationServer
        $authorizationServer = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $privateKey,
            $encryptionKey
        );

        // 启用 client credentials grant
        $authorizationServer->enableGrantType(
            new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
            new \DateInterval('PT2H') // access tokens will expire after 1 hour
        );

        // 启用 authentication code grant
        $grant = new \League\OAuth2\Server\Grant\AuthCodeGrant(
            $authCodeRepository,
            $refreshTokenRepository,
            new \DateInterval('PT10M') // authorization codes will expire after 10 minutes
        );
        $grant->setRefreshTokenTTL(new \DateInterval('P1M'));
        $authorizationServer->enableGrantType(
            $grant,
            new \DateInterval('PT2H') // access tokens will expire after 1 hour
        );

        // 启用 Refresh token grant
        $grant = new \League\OAuth2\Server\Grant\RefreshTokenGrant($refreshTokenRepository);
        $grant->setRefreshTokenTTL(new \DateInterval('P1M')); // new refresh tokens will expire after 1 month
        $authorizationServer->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // new access tokens will expire after an hour
        );
        $this->authorizationServer = $authorizationServer;
    }

    /**
     * 引导用户跳转登录
     */
    public function authorize()
    {
        //实例化 Psr\Http\Message\ServerRequestInterface
        $request = ServerRequestFactory::fromGlobals();
        $authRequest = $this->authorizationServer->validateAuthorizationRequest($request);
        //保存session
        Session::set('auth_request', serialize($authRequest));
        return $this->fetch('login');
    }

    /**
     * 验证登录
     */
    public function login(Request $request)
    {
        if (!$request->isPost()) {
            $this->error('错误请求');
        }
        //用户登录
        $user = Db::table('oauth_users')->where(['username' => $request->post('username'), 'password' => $request->post('password')])->find();
        if (empty($user)) {
            $this->error('密码错误');
        }
        $authRequest = unserialize(Session::get('auth_request'));
        //设置openid
        $authRequest->setUser(new UserEntity($user['openid'])); // an instance of UserEntityInterface
        Session::set('auth_request', serialize($authRequest));
        return $this->fetch('approve');
    }

    /**
     * 引导用户授权
     */
    public function approve(Request $request)
    {
        $q = $request->get();
        if (is_null($approve = $q['approve'])) {
            $this->error('错误请求');
        }
        $authRequest = unserialize(Session::get('auth_request'));
        $authRequest->setAuthorizationApproved((bool)$approve);
        $response = new \Laminas\Diactoros\Response();
        try {
            $psrResponse = $this->authorizationServer->completeAuthorizationRequest($authRequest, $response);
        } catch (OAuthServerException $e) {
            //用户拒绝授权
            return convertResponsePsr2Tp($e->generateHttpResponse($response));
        }
        return convertResponsePsr2Tp($psrResponse);
    }


    /**
     * 获取access token
     */
    public function token(Request $request)
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new \Laminas\Diactoros\Response();
        try {
            $response = $this->authorizationServer->respondToAccessTokenRequest($request, $response);
        } catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
            return response($exception->getMessage());
        } catch (\Exception $exception) {
            return response($exception->getMessage());
        }
        return convertResponsePsr2Tp($response);
    }

    /**
     * 刷新access token
     */
    public function refresh(Request $request){
        $request = ServerRequestFactory::fromGlobals();
        $response = new \Laminas\Diactoros\Response();
        try {
            $response = $this->authorizationServer->respondToAccessTokenRequest($request, $response);
        } catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
            return response($exception->getHint());
        } catch (\Exception $exception) {
            return response($exception->getMessage());
        }
        return convertResponsePsr2Tp($response);
    }

    /**
     * 验证access token
     */
    public function check()
    {
        $accessTokenRepository = new AccessTokenRepository(); // instance of AccessTokenRepositoryInterface
        // 初始化资源服务器
        $server = new ResourceServer(
            $accessTokenRepository,
            ROOT_PATH . '/public.key'
        );
        $request = ServerRequestFactory::fromGlobals();
        $response = new \Laminas\Diactoros\Response();
        try {
            $request = $server->validateAuthenticatedRequest($request);
        } catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
            return convertResponsePsr2Tp($exception->generateHttpResponse($response));
        } catch (\Exception $exception) {
            return convertResponsePsr2Tp((new OAuthServerException($exception->getMessage(), 0, 'unknown_error', 500))
                ->generateHttpResponse($response));
        }
        $attr = $request->getAttributes();
        //第三方的client_id
        $oauth_client_id = $attr['oauth_client_id'];
        //用户的openid
        $oauth_user_id = $attr['oauth_user_id'];
        //权限
        $oauth_scopes = $attr['oauth_scopes'];

        //业务逻辑
        //...
    }


}
