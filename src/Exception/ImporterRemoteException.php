<?php

namespace Drupal\zero_importer\Exception;

class ImporterRemoteException extends ImporterSkipException implements ImporterRemoteThrowable {

  private string $url;
  private array $options;
  private string $method;

  /**
   * @param string $url
   * @param array $options
   * @param string $method
   *
   * @return $this
   */
  public function setRequestInfo(string $url, array $options, string $method = 'get'): self {
    $this->url = $url;
    $this->options = $options;
    $this->method = $method;
    return $this;
  }

  /**
   * @return string
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * @return array
   */
  public function getOptions(): array {
    return $this->options;
  }

  /**
   * @return string
   */
  public function getMethod(): string {
    return $this->method;
  }

}
