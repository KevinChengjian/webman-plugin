<?php

namespace vendor\nasus\webman\src\Utils;

use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\Clock\FrozenClock;
use Nasus\Webman\Exception\JwtTokenException;

class Jwt
{
    /**
     * 签发者
     * @var string
     */
    private string $iss;

    /**
     * 主题
     * @var string
     */
    private string $sub;

    /**
     * 受众
     * @var string
     */
    private string $iat;

    /**
     * Token有效时间(秒)
     * @var int
     */
    private int $accessExp;

    /**
     * RefreshToken有效时间(秒)
     * @var int
     */
    private int $refreshExp;

    /**
     * 签名方法
     * @var string
     */
    private string $alg;

    /**
     * jwt key
     * @var string
     */
    private string $secret;

    /**
     * 时区
     * @var string
     */
    private string $timeZone = 'Asia/Shanghai';

    /**
     * @param $config
     */
    public function __construct($config = [])
    {
        $this->config($config);
    }

    /**
     * @param array $config
     * @return $this
     */
    public function config(array $config): static
    {
        $this->iss = $config['iss'] ?? '';
        $this->sub = $config['sub'] ?? '';
        $this->iat = $config['iat'] ?? '';
        $this->accessExp = $config['access_exp'] ?? 7200;
        $this->refreshExp = $config['refresh_exp'] ?? 86400;
        $this->alg = $config['alg'] ?? 'HS256';
        $this->secret = $config['secret'] ?? '';
        $this->timeZone = $config['time_zone'] ?? 'Asia/Shanghai';
        return $this;
    }

    /**
     * @return Configuration
     */
    private function getConfiguration(): Configuration
    {
        return Configuration::forSymmetricSigner($this->signer($this->alg), InMemory::plainText($this->secret));
    }

    /**
     * @param array $claim
     * @return string
     */
    public function accessToken(array $claim = [], $jti = null): string
    {
        $configuration = $this->getConfiguration();

        $builder = $configuration->builder()
            ->issuedBy($this->iss)
            ->permittedFor($this->iat)
            ->relatedTo($this->sub)
            ->canOnlyBeUsedAfter(Carbon::now()->setTimezone(new \DateTimeZone($this->timeZone))->toDateTimeImmutable())
            ->issuedAt(Carbon::now()->setTimezone(new \DateTimeZone($this->timeZone))->toDateTimeImmutable())
            ->expiresAt(Carbon::now()->setTimezone(new \DateTimeZone($this->timeZone))->addSeconds($this->accessExp)->toDateTimeImmutable());

        $builder = $jti ? $builder->identifiedBy($jti) : $builder;

        foreach ($claim as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        return $builder->getToken($configuration->signer(), $configuration->signingKey())->toString();
    }

    /**
     * @param string $token
     * @return mixed[]
     */
    public function verifyToken(string $token)
    {
        try {
            $configuration = $this->getConfiguration();
            $verifyToken = $configuration->parser()->parse(trim(str_ireplace('Bearer', '', $token)));
            if (!($verifyToken instanceof UnencryptedToken)) {
                throw new JwtTokenException('invalid token');
            }

            $validator = new Validator();
            // verify issuer
            if (!$validator->validate($verifyToken, new IssuedBy($this->iss))) {
                throw new JwtTokenException('invalid issuer token');
            }

            // verify aud
            if (!$validator->validate($verifyToken, new PermittedFor($this->iat))) {
                throw new JwtTokenException('invalid audience token');
            }

            if (!$validator->validate($verifyToken, new RelatedTo($this->sub))) {
                throw new JwtTokenException('invalid sub token');
            }

            //验证是否过期
            $now = new FrozenClock(new DateTimeImmutable('', new \DateTimeZone($this->timeZone)));
            if (!$validator->validate($verifyToken, new LooseValidAt($now))) {
                throw new JwtTokenException('token expired');
            }

            return $verifyToken->claims()->all();
        } catch (CannotDecodeContent|InvalidTokenStructure|UnsupportedHeaderFound|JwtTokenException $exception) {
            throw new JwtTokenException($exception instanceof JwtTokenException
                ? 'invalid token'
                : $exception->getMessage()
            );
        }
    }

    /**
     * @param $type
     * @return Sha256|Sha512
     */
    private function signer($type)
    {
        return match ($type) {
            'HS256' => new Sha256(),
            'HS512' => new Sha512(),
        };
    }
}