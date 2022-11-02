<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Remote;

use Drupal\zero_importer\Annotation\ZeroImporterRemote;
use Drupal\zero_importer\Exception\ImporterAuthException;
use Drupal\zero_util\Data\DataArray;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

/**
 * @ZeroImporterRemote(
 *   id = "drupal.cookie.auth",
 * )
 */
class DrupalCookieAuthRemote extends DefaultImporterRemote {

  public const STATE_LOGOUT = 'logout';
  public const STATE_PENDING = 'pending';
  public const STATE_LOGIN = 'login';

  protected static ?array $share_login = NULL;

  protected string $state = self::STATE_LOGOUT;
  protected ?array $login = NULL;

  public function getState() {
    if ($this->option('share')) {
      return self::$share_login['state'] ?? self::STATE_LOGOUT;
    }
    return $this->state;
  }

  public function setState(string $state) {
    if ($this->option('share')) {
      self::$share_login['state'] = $state;
    } else {
      $this->state = $state;
    }
  }

  public function setOptions(array $options = []): self {
    $options = array_replace_recursive([
      'login' => [
        'url' => '/user/login',
        'options' => [
          'query' => [
            '_format' => 'json',
          ],
        ],
      ],
    ], $options);

    // logout if the options for the remote have changed
    if (!DataArray::arrayEqual($options, $this->getOptions()) && $this->state === self::STATE_LOGIN) {
      $this->log('logout', [
        'type' => 'note',
        'message' => '[REMOTE] Logout user, because the options for remote have changed.',
        'placeholders' => [ 'options' => $options ],
      ]);
      $this->state = self::STATE_LOGOUT;
      $this->login = NULL;
    }

    return parent::setOptions($options);
  }

  public function request(Uri|string $path = NULL, array $options = [], string $method = 'get'): ResponseInterface {
    if ($this->getState() === self::STATE_LOGOUT) {
      $this->setState(self::STATE_PENDING);
      $login = $this->getLoginData();
      $this->client = new Client($this->getURIOptions([
        'timeout' => 60,
        'verify' => FALSE,
        'base_uri' => $this->getURI(),
        'headers' => ['X-CSRF-Token' => $login['response']['csrf_token']],
        'cookies' => $login['cookies'],
      ]));
    }
    return parent::request($path, $options, $method);
  }

  /**
   * @return array = [
   *     'response' => [
   *       'csrf_token' => 'asdfgc',
   *     ],
   *     'cookies' => CookieJar
   * ]
   */
  public function getLoginData(): array {
    if ($this->option('share')) {
      if (!isset(self::$share_login['data'])) {
        self::$share_login['data'] = $this->doLogin();
        $this->setState(self::STATE_LOGIN);
      }
      return self::$share_login['data'];
    } else {
      if ($this->login === NULL) {
        $this->login = $this->doLogin();
        $this->setState(self::STATE_LOGIN);
      }
      return $this->login;
    }
  }

  protected function doLogin(): array {
    $options = $this->getOptions();
    $cookies = new CookieJar();

    $options['login']['options'] = array_replace_recursive($options['login']['options'], [
      'cookies' => $cookies,
      'json' => [
        'name' => $options['user']['name'],
        'pass' => $options['user']['pass'],
      ],
      'importer' => [
        'exception' => ImporterAuthException::class,
      ],
    ]);
    $this->log('login', [
      'type' => 'note',
      'message' => '[REMOTE] Login as user "{{ options.user.name }}" ...',
      'placeholders' => [ 'options' => $options ],
    ]);
    $response = $this->getJSON($options['login']['url'], $options['login']['options'], 'post');
    if ($response && $cookies->count() && isset($response['csrf_token'])) {
      return [
        'response' => $response,
        'cookies' => $cookies,
      ];
    } else {
      throw (new ImporterAuthException('Could not authenticate against "' . $this->getURI($options['login']['url'])))->setRequestInfo($options['login']['url'], $options['login']['options'], 'post');
    }
  }

}
