# Behat W3C WebDriver Fix (Moodle 3.0.5 / Selenium 3.141.59 / Chrome 94)

## Root cause
`instaclick/php-webdriver` (used by `behat/mink-selenium2-driver` 1.1.1) only recognizes the legacy Wire Protocol element key `ELEMENT` in `Container::webDriverElement()`. Chrome 94 + Selenium 3.141.59 returns elements using the W3C key `element-6066-11e4-a52e-4f735466cecf`. This causes all `driver->find()` to return empty arrays, making `behat_hooks::before_scenario()` fail with "not a behat test site".

## Fix
File: `vendor/instaclick/php-webdriver/lib/WebDriver/Container.php`  
Method: `webDriverElement()`  
Change: Support both `ELEMENT` (legacy) and `element-6066-11e4-a52e-4f735466cecf` (W3C) keys.

```php
protected function webDriverElement($value)
{
    $valueArray = (array) $value;
    $w3cKey = 'element-6066-11e4-a52e-4f735466cecf';
    if (array_key_exists('ELEMENT', $valueArray)) {
        $elementId = $value['ELEMENT'];
    } elseif (array_key_exists($w3cKey, $valueArray)) {
        $elementId = $value[$w3cKey];
    } else {
        return null;
    }
    return new Element($this->getElementPath($elementId), $elementId);
}
```

## Symptoms that led to this bug
- `//html` XPath returns count=0 via driver->find() even though page title is visible
- `getContent()` works (returns source) but element-level XPath queries all return 0
- Manual WebDriver REST API calls find elements fine
- Error message: "The base URL (...) is not a behat test site"
