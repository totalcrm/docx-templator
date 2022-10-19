TotalCRM DocxTemplator
=========

Installation
----
Install with Composer.

`composer require totalcrm/docx-templator`


##### Template.
![alt tag](https://habrastorage.org/files/0bf/dbf/f89/0bfdbff896ba45e1ac966c54abd050aa.png)

```php
<?php
    require 'vendor/autoload.php';
    
    use TotalCRM\DocxTemplator\Templator;
    use TotalCRM\DocxTemplator\Document\WordDocument;
    
    $cachePath = 'path/to/writable/directory/';
    $templator = new Templator($cachePath);
    
    // Enable debug mode to generate template with every render call.
    // $templator->debug = true;
    
    // Enable track mode to generate template with every original document change.
    // $templator->trackDocument = true;
    
    $documentPath = 'path/to/document.docx';
    $document = new WordDocument($documentPath);
    
    $values = array(
        'library' => 'Templator 0.1',
        'simpleValue' => 'I am simple value',
        'nested' => array(
            'firstValue' => 'First child value',
            'secondValue' => 'Second child value'
        ),
        'header' => 'test of a table row',
        'students' => array(
            array('id' => 1, 'name' => 'Student 1', 'mark' => '10'),
            array('id' => 2, 'name' => 'Student 2', 'mark' => '4'),
            array('id' => 3, 'name' => 'Student 3', 'mark' => '7')
        ),
        'maxMark' => 10,
        'todo' => array(
            'TODO 1',
            'TODO 2',
            'TODO 3'
        )
    );
    $result = $templator->render($document, $values);
    
    // Now you can get template result.
    // 1. HTTP Download
    $result->download();
    
    // Or
    // 2. Save to file
    $saved = $result->save(__DIR__ . '/static', 'result.docx');
    if ($saved === true) {
         echo 'Saved!';
    }
    
    // Or
    // 3. Buffer output
    echo $result->output();
```

##### Result.
![alt tag](https://habrastorage.org/files/290/6aa/6e6/2906aa6e6cba4fa08655b1f58463a4d8.png)