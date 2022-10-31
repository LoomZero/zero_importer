- [1. - Wie erstelle ich einen Importer](#1---wie-erstelle-ich-einen-importer)
  - [1.1. - Annotationfelder](#11---annotationfelder)
    - [1.1.1. - Dynamische Annotationsfelder](#111---dynamische-annotationsfelder)
    - [1.1.2. - `id [string]` (Pflichtfeld)](#112---id-string-pflichtfeld)
    - [1.1.3. - `entity_type [string]` (Pflichtfeld)](#113---entity_type-string-pflichtfeld)
    - [1.1.4. - `options [array<string, mixed>]`](#114---options-arraystring-mixed)
    - [1.1.5. - `load [array<string, string>]`](#115---load-arraystring-string)
    - [1.1.6. - `import [array]`](#116---import-array)
    - [1.1.7. - `after [array]`](#117---after-array)
  - [1.2. - Methoden und Prozess](#12---methoden-und-prozess)
    - [1.2.1. - `index(): array`](#121---index-array)
    - [1.2.2. - `load(ImporterEntry $entry): ?ContentEntityBase`](#122---loadimporterentry-entry-contententitybase)
    - [1.2.3. - `create(ImporterEntry $entry): ContentEntityBase`](#123---createimporterentry-entry-contententitybase)
    - [1.2.4. - `setup(ImporterEntry $entry, ContentEntityBase $entity): ?ImporterEntry`](#124---setupimporterentry-entry-contententitybase-entity-importerentry)
    - [1.2.5. - `import(ContentEntityBase $entity, ImporterEntry $entry)`](#125---importcontententitybase-entity-importerentry-entry)
    - [1.2.6. - `save(ContentEntityBase $entity, ImporterEntry $entry)`](#126---savecontententitybase-entity-importerentry-entry)
    - [1.2.7. - `after()`](#127---after)
- [2. - Auswertung des Imports](#2---auswertung-des-imports)
  - [2.1. - Wie kann ich die Results beeinflussen von einem Importer?](#21---wie-kann-ich-die-results-beeinflussen-von-einem-importer)
    - [2.1.1. - *Beispiel 1: Entity hinzufügen*](#211---beispiel-1-entity-hinzufügen)
    - [2.1.2. - *Beispiel 2: Entity Entfernen*](#212---beispiel-2-entity-entfernen)
  - [2.2. - Wie kann ich die Results auswerten?](#22---wie-kann-ich-die-results-auswerten)
    - [2.2.1. - *Beispiel 1: Unpublish alle Inhalte die nicht im Index sind*](#221---beispiel-1-unpublish-alle-inhalte-die-nicht-im-index-sind)
    - [2.2.2. - *Beispiel 2: Update alle Nodes nach dem Import*](#222---beispiel-2-update-alle-nodes-nach-dem-import)
    - [2.2.3. - *Beispiel 3: Update alle Nodes die vom Importer geskipped wurden*](#223---beispiel-3-update-alle-nodes-die-vom-importer-geskipped-wurden)
- [3. - Best Practice](#3---best-practice)
  - [3.1. - Vorhandene ImporterSource](#31---vorhandene-importersource)
    - [3.1.1. - `drupal.rest.api`](#311---drupalrestapi)
    - [3.1.2. - Wie erstelle ich einen eigenen ImporterSource](#312---wie-erstelle-ich-einen-eigenen-importersource)
  - [3.2. - Wie überspringe ich einen Inhalt](#32---wie-überspringe-ich-einen-inhalt)
    - [3.2.1. - *Beispiel 1: `index` filter*](#321---beispiel-1-index-filter)
    - [3.2.2. - *Beispiel 2: `setup`*](#322---beispiel-2-setup)
    - [3.2.3. - *Beispiel 3: `ImporterSkipException`*](#323---beispiel-3-importerskipexception)
    - [3.2.4. - *Beispiel 4: `ImporterIgnoreException`*](#324---beispiel-4-importerignoreexception)

# 1. - Wie erstelle ich einen Importer

- Erstelle eine Class in dem Ordner `src\Plugin\Zero\Importer`
- Gehe sicher das die neue Class mit dieser Class `ZeroImporterBase` erweitert wird (`extends`)
- Füge die Annotation `@ZeroImporter` hinzu

## 1.1. - Annotationfelder

*Volles Beispiel:*
```php
/**
 * @ZeroImporter(
 *   id = "mein-importer",              // (Pflicht) Importer id
 *   entity_type = "node",              // (Pflicht) Entity Type der importiert wird
 *   options = {                        // Options die vom Command an den Importer übergeben werden kann
 *     "force" = false,
 *   },
 *   load = {                           // Simple definierung wie existierende Inhalte geladen werden sollen (->load())
 *     "{{ _def.type.bundle }}" = "{{ type }}",
 *     "field_source_id" = "{{ nid }}",
 *   },
 *   logger = {                         // Definiert default Logger
 *     "file" = {                       // Erstellt eine Log File
 *       "path" = "logs/importer.log",  // Path zur Log File startet auf Project Root (web/..)
 *       "date" = "Y-m-d",              // Datum welches der Datei prepended wird
 *       "channel" = "custom-channel",  // Channel für den Logger (Default: [id])
 *     },
 *   },
 *   source = {
 *     "id" = "drupal.rest.api",        // Definiert die Importerart
 *     ...                              // Zusätzlich options abhängig vom Importer
 *   },
 *   after = {                          // Definiert aufräum Arbeiten
 *     "id" = "unpublish.other",        // Unpublish alle Inhalte die nicht Importiert wurden und "field_source_id" gefüllt haben.
 *     "exists" = {
 *       "field_source_id",
 *     },
 *   },
 * )
 */
 class BeispielImporter extends ZeroImporterBase {}
```

> - Dieser Importer kann über den Command `drush importer-execute mein-importer` ausgeführt werden.
> - Er versucht Entities vom Type `node` zu erstellen.
> - Der Importer kann mit einer Option `force` ausgeführt werden z.B. `drush importer-execute mein-importer --force`.
> - Der Importer wird zum Laden der Nodes den `bundle` vom Index `type` prüfen und für die `field_source_id` den Index `nid` erwarten.
> - Nach dem Import wird er die Nodes die nicht geladen oder erstellt wurden und das Feld `field_source_id` gefüllt ist unpublishen.

### 1.1.1. - Dynamische Annotationsfelder

Um ein Dynamisches Feld zu füllen kann die Method `init()` benutzt werden und `$this->annotation` beschrieben werden.

*Beispiel:*

```php
class BeispielImporter extends ZeroImporterBase {

  protected function init() {
    $this->annotation['source']['remote']['url'] = 'https://api.test';
  }

}
```

### 1.1.2. - `id [string]` (Pflichtfeld)

Name des importers und key für den Command (`drush importer-execute <id>`).

*Beispiel:*

```php
id = "mein-importer",
```

```
drush importer-execute mein-importer
```

### 1.1.3. - `entity_type [string]` (Pflichtfeld)

Der Entity Type der als Ziel benutzt wird z.B. `node`.

*Beispiel:*

```php
entity_type = "node",
```

### 1.1.4. - `options [array<string, mixed>]`

Dynamische optionen die für den Command benutzt wird.

*Beispiel:*

```php
options = {
  "force" = false,
},
```

### 1.1.5. - `load [array<string, string>]`

Der `load` Key bestimmt wie der Importer Inhalte wiedererkennt.

*Beispiel:*

```php
load = {
  "{{ _def.type.bundle }}" = "{{ type }}",
  "field_source_id" = "{{ nid }}",
},
```

In diesem Beispiel wird, ein Inhalt geladen anhand von `{{ _def.type.bundle }}` und `field_source_id`.

> - Der Platzhalter `{{ _def.type.bundle }}` wird wie folgt übersetzt:
>   - Fängt der Platzhalter mit `_` an? Dann suche einen Handler der mit dem ersten key heißt => `placeholder._def`
>   - Gibt es diesen Handler nicht? Dann suche nach dem allgemeinen Handler `placeholder`
>   - Gibt es diesen nicht? Dann versuche den Platzhalter Anhand des `ImporterEntry` aufzulösen.
>   - Gibt es diesen nicht? Dann ersetze den Platzhalter mit `''` (`string(0)`)
>   - Fängt der Platzhalter nicht mit `_` an? Dann versuche den Platzhalter mit dem `ImporterEntry` aufzulösen.
> - In unserem Fall ist der Platzhalter `_def` immer definiert und ermöglicht Daten aus dem Importer zu erhalten:
>   - `_def.type` - Kann dazu benutzt werden keys von der Entity Definition zu erhalten (`$importer->getLookup()->getEntityDefinition()->getKey('bundle')`)
>   - `_def.annotation` - Kann dazu benutzt werden Daten von der Annotation zu ziehen.
>   - `_def.option` - Kann dazu benutzt werden Daten von den Options zu ziehen.

Findet der Importer keinen Inhalt, erstellt er eine neuen Inhalt anhand der `load` keys.

> - Methode zum überschreiben `load(ImporterEntry $entry): ?ContentEntityBase`
> - Methode zum überschreiben von erstellen von neuen Inhalten `create(ImporterEntry $entry): ContentEntityBase`
>   - Sollte die Erstellung nicht möglich sein, kann eine `ImporterSkipException` geworfen werde.

### 1.1.6. - `source [array]`

Der `source` key wird als Option an den Prozess weitergegeben der über `source.id` definiert ist.

### 1.1.7. - `after [array]`

Der `after` Key bestimmt was nach dem Import passieren soll.

> - Methode zum überschreiben `after()`

## 1.2. - Methoden und Prozess

Der gesammte Import Prozess kann über die Methode `execute(array $options = [])` überschrieben werden.
Im weiteren gehen wir durch den gesammten Prozess und welche Methoden benutzt werden.

### 1.2.1. - `index(): array`

Die `index(): array` Methode muss ein `array` zurückgeben, jeder Arrayeintrag ist dabei ein eigener Inhalt der Importiert werden soll. Die Annotation Keys `source.id` und `source.index` bestimmen wie der `index` standardmäig aufgebaut wird.

### 1.2.2. - `load(ImporterEntry $entry): ?ContentEntityBase`

Nach dem `index` wird jeder Eintrag von dem Array einzelnd verarbeitet.

### 1.2.3. - `create(ImporterEntry $entry): ContentEntityBase`

Wird bei `load` kein Inhalt zurückgegeben, wird die `create` Methode benutzt. Diese soll eine neuen Inhalt erstellen der in das System importiert werden soll.

### 1.2.4. - `prepare(ImporterEntry $entry, ContentEntityBase $entity): ?ImporterEntry`

Beim `prepare` sollen die Daten vorbereitet werden. Wenn im `prepare` ein `ImporterEntry` zurückgegeben wird, dann wird der Import gestartet. Wenn `NULL` zurückgegeben wird, wird der Import übersprungen und der Inhalt ignoriert.

### 1.2.5. - `import(ContentEntityBase $entity, ImporterEntry $entry)`

### 1.2.6. - `save(ContentEntityBase $entity, ImporterEntry $entry)`

Der Inhalt wird gespeichert.

### 1.2.7. - `after(ImporterResult $result)`

Nach dem alle Inhalte abgearbeitet wurden, können hier Prozesse nach dem Import ausgeführt werden. Definiert wird dieser Prozess über den `after` key.

# 2. - Auswertung des Imports

## 2.1. - Wie kann ich die Results beeinflussen von einem Importer?

### 2.1.1. - *Beispiel 1: Entity hinzufügen*

```php
class BeispielImporter extends ZeroImporterBase {

  public function import(ContentEntityBase $entity, ImporterEntry $entry) {
    $this->result()->addEntity($entity, ['added' => TRUE]);
  }

}
```

> Füge eine Entity zum Result hinzu. Sollte diese Entity schon im Result enthalten sein, wird der zweite Parameter gemerged.
> Es ist vom Vorteil einen Parameter mitzugeben, um später zu erkennen wieso dieser Inhalt im Result enthalten ist.

### 2.1.2. - *Beispiel 2: Entity Entfernen*

```php
class BeispielImporter extends ZeroImporterBase {

  public function import(ContentEntityBase $entity, ImporterEntry $entry) {
    $this->result()->removeEntity($entity);
  }

}
```

## 2.2. - Wie kann ich die Results auswerten?

Typischerweise kann man die Results in der `after(ImporterResult $result)` Methode auswerten.

### 2.2.1. - *Beispiel 1: Unpublish alle Inhalte die nicht im Index sind*

```php
class BeispielImporter extends ZeroImporterBase {

  public function after(ImporterResult $result) {
    $lookup = $this->getLookup();
    $query = $lookup->getStorage()->getQuery();
    $ids = $result->ids($this->annotation()['entity_type']); // Bekomme eine Liste von IDs wo der EntityType mit dem Importer übereinstimmt
    if (count($ids)) {
      $query->condition($lookup->getEntityDefinition()->getKey('id'), $ids, 'NOT IN');
      $ids = $query->execute();

      foreach ($ids as $id) {
        $entity = $lookup->getStorage()->load($id);
        $entity->set('status', 0);
        $entity->save();
      }
    }
  }

}
```

### 2.2.2. - *Beispiel 2: Update alle Nodes nach dem Import*

```php
class BeispielImporter extends ZeroImporterBase {

  public function after(ImporterResult $result) {
    $result->each(function(array $item, int $index, ImporterResult $result) {
      $entity = $result->load($item);
      $entity->set('status', 1);
      $entity->save();
    });
  }

}
```

### 2.2.3. - *Beispiel 3: Update alle Nodes die vom Importer geskipped wurden*

```php
class BeispielImporter extends ZeroImporterBase {

  public function after(ImporterResult $result) {
    $items = $result->mapFilter(function(array $item) {
      return $item['data']['skip'] ?? NULL;
    });
    foreach ($items as $item) {
      $entity = $result->load($item);
      // ... some code ...
    }
  }

}
```

# 3. - Best Practice

## 3.1. - Vorhandene ImporterSource

### 3.1.1. - `drupal.rest.api`

Diese Source kann benutzt werden um von einer Drupal Rest API Inhalte einfach zu Importieren. [Documentation ->](/docs/readme.drupal.rest.api.md)

### 3.1.2. - Wie erstelle ich einen eigenen ImporterSource



## 3.2. - Wie überspringe ich einen Inhalt

- Kann über die Daten im Index bestimmt werden das der Inhalt übersprungen werden sollte, sollte der Inhalt entweder in der `index` Methode gefiltert oder im `prepare` mit `NULL` returned werden.

- An jedem Anderen Punkt vor der `save` Methode kann die Exception `ImporterSkipException`  geworfen werden.
- Soll ein Inhalt nicht beachtet werden und damit auch im Result als nicht Importiert gelten (Damit er unpublished wird z.B.) dann kann die Exception `ImporterIgnoreException` genutzt werden

### 3.2.1. - *Beispiel 1: `index` filter*

```php
class BeispielImporter extends ZeroImporterBase {

  public function index(): array {
    $index = parent::index();
    return array_filter($index, function($item) {
      return $item->type === 'ok';
    });
  }

}
```

### 3.2.2. - *Beispiel 2: `prepare`*

```php
class BeispielImporter extends ZeroImporterBase {

  public function prepare(ImporterEntry $entry, ContentEntityBase $entity): ?ImporterEntry {
    if ($entry->get('type') === 'not-ok') return NULL; // wird übersprungen
    return $entry; // wird importiert
  }

}
```

### 3.2.3. - *Beispiel 3: `ImporterSkipException`*

```php
class BeispielImporter extends ZeroImporterBase {

  public function import(ContentEntityBase $entity, ImporterEntry $entry) {
    if (/* something is wrong */) {
      throw new ImporterSkipException('Dieser Inhalt ist schlecht.');
    }
  }

}
```

### 3.2.4. - *Beispiel 4: `ImporterIgnoreException`*

```php
class BeispielImporter extends ZeroImporterBase {

  public function import(ContentEntityBase $entity, ImporterEntry $entry) {
    if (/* something is wrong */) {
      throw new ImporterIgnoreException('Dieser Inhalt existiert eigentlich nicht.');
    }
  }

}
```
