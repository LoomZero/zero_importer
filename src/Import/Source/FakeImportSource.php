<?php

namespace Drupal\zero_importer\Import\Source;

use Drupal\zero_importer\Base\Source\ZImporterSourceBase;

class FakeImportSource extends ZImporterSourceBase {

  public function getIndex($asBatch = TRUE) {
    if ($asBatch) {
      return [
        'batch' => [
          [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
          ],
          [
            ['id' => 4],
            ['id' => 5],
            ['id' => 6],
          ],
          [
            ['id' => 7],
            ['id' => 8],
            ['id' => 9],
          ],
        ],
      ];
    } else {
      return [
        'index' => [
          ['id' => 1],
          ['id' => 2],
          ['id' => 3],
          ['id' => 4],
          ['id' => 5],
          ['id' => 6],
          ['id' => 7],
          ['id' => 8],
          ['id' => 9],
        ],
      ];
    }
  }

  public function getItem($id) {
    return [
      'id' => $id,
      'label' => 'Fake Item ' . $id,
      'field_text' => 'Text ' . $id,
    ];
  }

}
