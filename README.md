#CSS reader and writer

##Introduction
CSS reader and writer with full CSS3 support, already supporting huge parts of the current CSS4 spec. It supports media queries, comments, value optimization and more... It offers full Unicode support and can handle also large CSS sources.

##Installation
This is a composer package. See the [composer website](https://getcomposer.org/) for basic installation information.

Add the following line to your `composer.json` file:
```javascript
{
    "require": {
        "crossjoin/css": "1.0.*"
    }
}
```

###Requirements
- PHP 5.4+
- Multibyte String (mbstring) extension
- DOM extension (to extract CSS from HTML sources)

##Features
- Full CSS3 support
- Full Unicode support
- Support for huge parts of CSS4
- Supports at-rules with an unlimited level of nesting
- Supports CSS comments
- Can handle large, compressed CSS files
- Integrated checks for invalid CSS
- ...

##Basic usage

###Reading CSS
You can read the CSS information from different sources.

```php
// Read CSS file
$cssFileName = "path/to/file.css";
$reader = new \Crossjoin\Css\Reader\CssFile($cssFileName);

// Read CSS string
$cssString = "body { background:red; }";
$reader = new \Crossjoin\Css\Reader\CssFile($cssString);

// Extract CSS from <style> tags in a HTML file
// (multiple tags are merges to one style sheet)
$htmlFileName = "path/to/file.html";
$reader = new \Crossjoin\Css\Reader\HtmlFile($htmlFileName);

// Extract CSS from <style> tags in a HTML string
// (multiple tags are merges to one style sheet)
$htmlString = "<html><head><style>body{color:red;}</style></head><body></body></html>";
$reader = new \Crossjoin\Css\Reader\HtmlFile($htmlString);
```

###Charset detection
The charset of the CSS file is detected automatically (depending on Byte-Order-Markers or a charset rule within the CSS) and defaults to "UTF-8". You can change the default value by setting the environment encoding.

```php
// Sets the environment encoding of the referencing (!) document, e.g. if defined 
// in a link tag of an HTML page.
// This is used as a fall back value to determine the charset of the CSS file.
$reader->setEnvironmentEncoding("UTF-8");
```

###Writing CSS
The writer can create CSS in different formats.

```php
// Gets the CSS content in compact format (no comments, no line breaks,...)
$writer = new \Crossjoin\Css\Writer\Compact($reader->getStyleSheet());
$cssContent = $writer->getContent();

// Gets the CSS content in prettified format
// (with comments, line breaks, indentations,...)
$writer = new \Crossjoin\Css\Writer\Pretty($reader->getStyleSheet());
$cssContent = $writer->getContent();
```

###Error handling
While parsing the CSS source, the content is checked for invalid rules, selectors and declarations. Furthermore the reader and the writer do additional checks regarding the composition of the rules, e.g. invalid rule positions or unnecessary empty rules.

```php
$cssString = 'body{color:black} @charset "UTF-8"; @media print{@page{margin:1cm;}}';
$reader = new \Crossjoin\Css\Reader\CssString($cssString);
$writer = new \Crossjoin\Css\Writer\Pretty($reader->getStyleSheet());
$content = $writer->getContent();
$errors = $writer->getErrors();

print_r($content);
// body { color: black; }

print_r($errors);
// Array (
// [0] => Ignored @charset rule, because at wrong position in style sheet.
// [1] => Rule instance of type 'Crossjoin\Css\Format\Rule\AtPage\PageRule'
//        not allowed in conditional group rule of type
//        'Crossjoin\Css\Format\Rule\AtMedia\MediaRule'.
// [2] => Empty media at-rule '@media print' ignored.
// )
```

##Advanced usage
TODO

##To Do
- Additional checks for valid CSS rules, properties and values
- Additional optimizations of CSS properties and values
- Optimized auto-correction of invalid CSS, following the specs ([example](http://dev.w3.org/csswg/mediaqueries-4/#error-handling))
- Support for [CSS Paged Media Module Level 3](http://dev.w3.org/csswg/css-page-3/#at-page-rule) (page type selectors, margin at-rules)
- Support for "supports" in import at-rule ([not final](http://dev.w3.org/csswg/css-cascade-4/#at-ruledef-import))
