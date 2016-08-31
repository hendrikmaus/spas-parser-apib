# spas-parser-apib
Concrete implementation of an API Blueprint Refract parser to use with [spas](https://github.com/hendrikmaus/spas)

## Details
Spas is a tool to test an API description against a given environment.  
As spas itself is description language agnostic, it relies on different concrete implementations
of [spas-parser](https://github.com/hendrikmaus/spas-parser) which defines the common interfaces.

Using this package, `hmaus/spas-parser-apib`, you get the chance to use API Blueprint as an input
for your tests.

## Installation
The recommended way to install, is using composer:

```bash
composer require hmaus/spas-parser-apib
```

## Usage
Pass it to spas as CLI option:

```bash
--request_provider "\Hmaus\Spas\Parser\Apib\ApibParsedRequestsProvider"
```
