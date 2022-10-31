<?php

namespace Drupal\zero_importer\Exception;

use Throwable;

interface ImporterRemoteThrowable extends Throwable {

  /**
   * @param string $url
   * @param array $options
   * @param string $method
   *
   * @return $this
   */
  public function setRequestInfo(string $url, array $options, string $method = 'get'): self;

  /**
   * @return string
   */
  public function getUrl(): string;

  /**
   * @return array
   */
  public function getOptions(): array;

  /**
   * @return string
   */
  public function getMethod(): string;

}
