# 1. - `drupal.rest.api - DrupalRestApiSource`

- [1. - `drupal.rest.api - DrupalRestApiSource`](#1---drupalrestapi---drupalrestapisource)
  - [1.1. - Annotation](#11---annotation)
  - [1.2. - Handler](#12---handler)
    - [1.2.1. - Field Handler](#121---field-handler)
    - [1.2.2. - Type Handler](#122---type-handler)
  - [1.3. - Vordefinierte Handler](#13---vordefinierte-handler)
    - [1.3.1. - Entity Reference auf existierende Inhalte](#131---entity-reference-auf-existierende-inhalte)
      - [1.3.1.1. - *Beispiel 1: Entity Reference Field auf einen anderen Importierten Inhalt*](#1311---beispiel-1-entity-reference-field-auf-einen-anderen-importierten-inhalt)
      - [1.3.1.2. - *Beispiel 2: Entity Reference auf ein anderen Inhalt der bereits existiert*](#1312---beispiel-2-entity-reference-auf-ein-anderen-inhalt-der-bereits-existiert)
    - [1.3.2. - Entity Reference auf neue Inhalte](#132---entity-reference-auf-neue-inhalte)
      - [1.3.2.1. - *Beispiel 1: Taxonomy erstellen*](#1321---beispiel-1-taxonomy-erstellen)
      - [1.3.2.2. - *Beispiel 2: Media erstellen*](#1322---beispiel-2-media-erstellen)
  - [1.4. - How to use](#14---how-to-use)
    - [1.4.1. - Request](#141---request)
      - [1.4.1.1. - *Beispiel 1: Nutzung vom Importer aus*](#1411---beispiel-1-nutzung-vom-importer-aus)
      - [1.4.1.2. - *Beispiel 2: Nutzung vom Importer aus mit komplexen Werten*](#1412---beispiel-2-nutzung-vom-importer-aus-mit-komplexen-werten)
    - [1.4.2. - Importiere von einer anderen Drupal Seite](#142---importiere-von-einer-anderen-drupal-seite)

## 1.1. - Annotation

```php
import = {
  "id" = "drupal.rest.api", // Der Name der ImporterSource
  "logger" = {
    "url" = "@url", // log the url that will be requested
    "status" = "@url @status", // log the url and status after request
  },
  "url" = { // URL Object für die Source; Grundeinstellung, wird immer Benutzt wenn ein request() gemacht wird.
    "base" = "https://api.test", // Base url für request()
    "query" = { // Query options für request()
      "_format" = "json",
    }, // Options für request() @see "\GuzzleHttp\Client::get()"
    "options" = {
      "verify" = false,
    },
  },
  "index" = { // Index soll auch von der Source übernommen werden
    "url" = "/path/to/index", // Machen einen request() für den Index
    "items" = "items", // Nehme in der response den `items` key als Index
  },
  "setup" = { // Setup soll von der Source übernommen werden
    "url" = { // Machen einen request() von dem Indexeintrag aus
      "path" = "@data_url", // Nehme das Feld `data_url` aus dem Indexeintrag als path für den request()
      "query" = {}, // Überschreibe die query parameter
    },
    "force_option" = "force", // Ignoriere Import bedingung wenn die Option `--force` mitgegeben wird
    "hash" = "field_source_hash", // Erstelle einen Hash und vergleiche ihn mit `field_source_hash`; Importiere nur wenn unterschiedlich
  },
  "fields" = { // Import soll von der Source übernommen werden
    "includes" = { // Liste von Feldern die importiert werden
      "title",
      "status",
    },
    "includes_pattern" = "field_.*", // Pattern der bestimmt ob ein Feld importiert wird.
    "excludes" = { // Liste von Felder die nicht importiert werden (hat vorrang)
      "field_source_hash",
    },
    "excludes_pattern" = "field_source_.*" // Pattern der bestimmt ob ein Feld nicht importiert wird (hat vorrang)
  },
}
```

## 1.2. - Handler

### 1.2.1. - Field Handler

Key: `mapping.name.<field_name>`

Handler: 

```php
function(ZeroImporterInterface $importer, ContentEntityBase $entity, ImporterEntry $entry, string $field) {

}
```

### 1.2.2. - Type Handler

Key: `mapping.type.<field_type>`

Handler: 

```php
function(ZeroImporterInterface $importer, ContentEntityBase $entity, ImporterEntry $entry, string $field) {

}
```

## 1.3. - Vordefinierte Handler

### 1.3.1. - Entity Reference auf existierende Inhalte

Methode: `DrupalRestApiSource::entityReferenceLookupHandler(array|callable $props): callable`

#### 1.3.1.1. - *Beispiel 1: Entity Reference Field auf einen anderen Importierten Inhalt*

```php
class BeispielImporter extends ZeroImporterBase {

  protected function init() {
    $this->setHandler('mapping.name.field_clients', DrupalRestApiSource::entityReferenceLookupHandler([
      'field_source_id' => '@target_id',
    ]));
  }

}
```

> `field_clients` in diesem Fall ist eine Entity Reference auf einen anderen Inhalt. Als matching wird nur das `field_source_id` Feld benutzt. 

#### 1.3.1.2. - *Beispiel 2: Entity Reference auf ein anderen Inhalt der bereits existiert*

```php
class BeispielImporter extends ZeroImporterBase {

  protected function init() {
    $this->setHandler('mapping.name.field_clients', DrupalRestApiSource::entityReferenceLookupHandler([
      '@bundle' => '@target_bundle',
      'nid' => '@target_id',
    ]));
  }

}
```

### 1.3.2. - Entity Reference auf neue Inhalte

#### 1.3.2.1. - *Beispiel 1: Taxonomy erstellen*

```php
class BeispielImporter extends ZeroImporterBase {

  protected function init() {
    $this->setHandler('mapping.type.entity_reference', function(ZeroImporterInterface $importer, ContentEntityBase $entity, ImporterEntry $entry, string $field) {
      $items = [];
      switch ($entity->get($field)->getItemDefinition()->getSetting('target_type')) {
        case 'taxonomy_term':
          foreach ($entry->get($field) as $value) {
            $term = ImporterHelper::createEntity('taxonomy_term', [
              'vid' => $entry->get('vid.0.target_id'),
              'name' => $entry->get('name.0.value'),
            ]);
            $items[] = ['target_id' => $term->id()];
          }
          break;
      }
      $entity->set($field, $items);
    });
  }

}
```

> `ImporterHelper::createEntity()` versucht den Inhalt (in diesem Falle die Taxonomy) anhand des 2. Parameters zu laden. Kann er dies nicht erstellt er eine neue Taxonomy. Mit dem 3. Parameter lassen sich weitere Daten für die Taxonomy mitgeben.

> WICHTIG: Gebe immer den Bundle an  mit `@bundle` oder in diesem Fall mit `vid`.

#### 1.3.2.2. - *Beispiel 2: Media erstellen*

```php
class BeispielImporter extends ZeroImporterBase {

  protected function init() {
    $this->setHandler('mapping.type.entity_reference', function(ZeroImporterInterface $importer, ContentEntityBase $entity, ImporterEntry $entry, string $field) {
      $items = [];
      switch ($entity->get($field)->getItemDefinition()->getSetting('target_type')) {
        case 'media':
          foreach ($entry->get($field) as $value) {
            $media_entry = new ImporterEntry($data);
            $bundle = $media_entry->get('bundle.0.target_id');
            $media = ImporterHelper::createMedia($media_entry, [
              '@bundle' => $bundle,
              'field_source_id' => '@mid.0.value',
            ], '@' . ImporterHelper::getMediaSourceField($bundle) . '.0.url');

            if ($media !== NULL) {
              $items[] = ['target_id' => $media->id()];
            }
          }
          break;
      }
      $entity->set($field, $items);
    });
  }

}
```

> `ImporterHelper::createMedia()` kann `NULL` zurückgeben deswegen muss hier eine Bedingung geschrieben werden.
 
## 1.4. - How to use

### 1.4.1. - Request

```php
/**
 * @param string|array $options = [
 *     'full_url' => 'https://api.test/full/path/to/source?_format=json',
 *     'base' => 'https://api.test',
 *     'path' => '/full/path/to/source'
 *     'query' => [
 *       '_format' => 'json',
 *     ],
 *     'options' => [
 *       'verify' => FALSE,
 *     ],
 * ],
 *
 * @return mixed
 */
public function request($options = []) { ... }
```

- Wenn `full_url` definiert ist wird nur `full_url` und der `options` key benutzt.
- Wird `$options` als ein `string` angegeben ersetzt das `$options['path']`.

#### 1.4.1.1. - *Beispiel 1: Nutzung vom Importer aus*

```php
class BeispielImporter extends ZeroImporterBase {

  public function import(ContentEntityBase $entity, ImporterEntry $entry) {
    $data = $this->getSource()->request('/my/rest/endpoint');
    // some code
  }

}
```

#### 1.4.1.2. - *Beispiel 2: Nutzung vom Importer aus mit komplexen Werten*

```php
class BeispielImporter extends ZeroImporterBase {

  public function import(ContentEntityBase $entity, ImporterEntry $entry) {
    $data = $this->getSource()->request(['path' => '/my/rest/endpoint', 'query' => ['_format' => 'hal_json']]);
    // some code
  }

}
```

### 1.4.2. - Importiere von einer anderen Drupal Seite

- Installiere das Modul `rest` auf der Export Seite.
- Erstelle eine neue View und benutze den Display `REST export`.
- Füge alle Filter hinzu die du Brauchst und den Path für die View.
- Füge ein `ID` Feld und ein Custom Text Feld hinzu mit dem Inhalt `/node/{{ nid }}?_format=json`.
- Bearbeite bei **Format** die **Anzeige** Einstellungen und ändere den Alias vom Feld `nothing` zu `data_url`.
- Erstelle einen Importer in deinem Zielsystem und nutze die URL der View in der Annotation bei `import.index.url`.
- Füge die Annotation `import.setup.url.path` mit dem Wert `@data_url` hinzu.